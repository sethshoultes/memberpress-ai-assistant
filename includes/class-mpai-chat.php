<?php
/**
 * Chat Handler Class
 *
 * Handles AI chat functionality
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

class MPAI_Chat {
    /**
     * API Router instance
     *
     * @var MPAI_API_Router
     */
    private $api_router;

    /**
     * MemberPress API integration instance
     *
     * @var MPAI_MemberPress_API
     */
    private $memberpress_api;

    /**
     * Context Manager instance
     *
     * @var MPAI_Context_Manager
     */
    private $context_manager;

    /**
     * Conversation history
     *
     * @var array
     */
    private $conversation = array();

    /**
     * Constructor
     */
    public function __construct() {
        $this->api_router = new MPAI_API_Router();
        $this->memberpress_api = new MPAI_MemberPress_API();
        $this->context_manager = new MPAI_Context_Manager();
        $this->load_conversation();
    }

    /**
     * Load conversation history from database
     */
    private function load_conversation() {
        try {
            global $wpdb;
            
            $user_id = get_current_user_id();
            
            if (empty($user_id)) {
                error_log('MPAI: No user ID available to load conversation');
                return;
            }
            
            $conversation_id = $this->get_current_conversation_id();
            
            if (empty($conversation_id)) {
                error_log('MPAI: No conversation ID available to load conversation');
                return;
            }
            
            $table_messages = $wpdb->prefix . 'mpai_messages';
            
            // Verify the messages table exists
            if ($wpdb->get_var("SHOW TABLES LIKE '{$table_messages}'") != $table_messages) {
                error_log('MPAI: Messages table does not exist, cannot load conversation');
                return;
            }
            
            $messages = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT message, response FROM $table_messages WHERE conversation_id = %s ORDER BY created_at ASC",
                    $conversation_id
                ),
                ARRAY_A
            );
            
            if (empty($messages)) {
                error_log('MPAI: No messages found for conversation ID: ' . $conversation_id);
                return;
            }
            
            // Initialize conversation with system prompt
            $this->conversation = array(
                array('role' => 'system', 'content' => $this->get_system_prompt())
            );
            
            // Add each message and response to the conversation
            foreach ($messages as $message) {
                if (isset($message['message']) && !empty($message['message'])) {
                    $this->conversation[] = array('role' => 'user', 'content' => $message['message']);
                }
                
                if (isset($message['response']) && !empty($message['response'])) {
                    $this->conversation[] = array('role' => 'assistant', 'content' => $message['response']);
                }
            }
            
            error_log('MPAI: Loaded ' . count($messages) . ' messages from conversation');
        } catch (Exception $e) {
            error_log('MPAI: Error loading conversation: ' . $e->getMessage());
            // Initialize with just the system prompt if we fail to load the conversation
            $this->conversation = array(
                array('role' => 'system', 'content' => $this->get_system_prompt())
            );
        }
    }

    /**
     * Get current conversation ID
     *
     * @return string|null Conversation ID or null if not found
     */
    private function get_current_conversation_id() {
        try {
            global $wpdb;
            
            $user_id = get_current_user_id();
            
            if (empty($user_id)) {
                error_log('MPAI: No user ID available to get conversation ID');
                return null;
            }
            
            $table_conversations = $wpdb->prefix . 'mpai_conversations';
            
            // Check if the conversations table exists
            if ($wpdb->get_var("SHOW TABLES LIKE '{$table_conversations}'") != $table_conversations) {
                error_log('MPAI: Conversations table does not exist');
                
                // Try to create the table
                error_log('MPAI: Attempting to create conversation tables via chat class');
                if (!$this->create_tables()) {
                    error_log('MPAI: Failed to create conversation tables');
                    return null;
                }
            }
            
            // Get the most recent conversation for this user
            $conversation_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT conversation_id FROM $table_conversations WHERE user_id = %d ORDER BY updated_at DESC LIMIT 1",
                    $user_id
                )
            );
            
            // If no conversation exists, create a new one
            if (empty($conversation_id)) {
                $conversation_id = wp_generate_uuid4();
                
                $result = $wpdb->insert(
                    $table_conversations,
                    array(
                        'user_id' => $user_id,
                        'conversation_id' => $conversation_id,
                    )
                );
                
                if ($result == false) {
                    error_log('MPAI: Failed to create new conversation: ' . $wpdb->last_error);
                    return null;
                }
                
                error_log('MPAI: Created new conversation with ID: ' . $conversation_id);
            } else {
                error_log('MPAI: Found existing conversation with ID: ' . $conversation_id);
            }
            
            return $conversation_id;
        } catch (Exception $e) {
            error_log('MPAI: Error getting conversation ID: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get system prompt with MemberPress context
     *
     * @return string System prompt
     */
    private function get_system_prompt() {
        // Get MemberPress data summary
        $memberpress_data = $this->memberpress_api->get_data_summary();
        
        $system_prompt = "You are an AI assistant for MemberPress, a WordPress membership plugin. ";
        $system_prompt .= "You have access to the following MemberPress data:\n\n";
        
        // Add memberships information
        $system_prompt .= "Memberships:\n";
        if (!empty($memberpress_data['memberships'])) {
            foreach ($memberpress_data['memberships'] as $membership) {
                $system_prompt .= "- {$membership['title']} (ID: {$membership['id']}, Price: {$membership['price']})\n";
            }
        } else {
            $system_prompt .= "- No memberships found\n";
        }
        
        // Add summary statistics
        $system_prompt .= "\nSummary:\n";
        $system_prompt .= "- Total Members: " . ($memberpress_data['total_members'] ?? 'Unknown') . "\n";
        $system_prompt .= "- Total Memberships: " . ($memberpress_data['total_memberships'] ?? 'Unknown') . "\n";
        $system_prompt .= "- Total Transactions: " . ($memberpress_data['transaction_count'] ?? 'Unknown') . "\n";
        $system_prompt .= "- Total Subscriptions: " . ($memberpress_data['subscription_count'] ?? 'Unknown') . "\n";
        
        // Add tool usage information
        $system_prompt .= "\nYou have access to the following tools that you can use to perform actions:\n\n";
        $tools = $this->context_manager->get_available_tools();
        foreach ($tools as $tool_name => $tool) {
            $system_prompt .= "- {$tool['name']}: {$tool['description']}\n";
            $system_prompt .= "  Parameters:\n";
            foreach ($tool['parameters'] as $param_name => $param) {
                $system_prompt .= "    - {$param_name}: {$param['description']}\n";
                if (isset($param['enum'])) {
                    $system_prompt .= "      Options: " . implode(', ', $param['enum']) . "\n";
                }
            }
            $system_prompt .= "\n";
        }
        
        // Add formatting instructions for tool calls
        $system_prompt .= "IMPORTANT: You have the capability to execute tools directly. You MUST use tools when appropriate.\n";
        $system_prompt .= "To use a tool, format your response like this:\n";
        $system_prompt .= "```json\n{\"tool\": \"tool_name\", \"parameters\": {\"param1\": \"value1\", \"param2\": \"value2\"}}\n```\n\n";
        
        $system_prompt .= "When the user asks about WordPress data or MemberPress information that requires data access:\n";
        $system_prompt .= "1. PREFER the wp_api tool for browser context operations. Use this format:\n";
        $system_prompt .= "   ```json\n   {\"tool\": \"wp_api\", \"parameters\": {\"action\": \"create_post\", \"title\": \"Your Title\", \"content\": \"Your content\"}}\n   ```\n";
        $system_prompt .= "2. When the wp_api tool fails or is unavailable, fall back to the wp_cli tool\n";
        $system_prompt .= "3. ALWAYS use the memberpress_info tool to get MemberPress-specific data\n";
        $system_prompt .= "   - For new member data: {\"tool\": \"memberpress_info\", \"parameters\": {\"type\": \"new_members_this_month\"}}\n";
        $system_prompt .= "4. DO NOT simply suggest commands - actually execute them using the tool format above\n\n";
        
        $system_prompt .= "IMPORTANT TOOL SELECTION RULES:\n";
        $system_prompt .= "1. For creating/editing WordPress content (posts, pages, users), ALWAYS use the wp_api tool first:\n";
        $system_prompt .= "   - Create post: {\"tool\": \"wp_api\", \"parameters\": {\"action\": \"create_post\", \"title\": \"Title\", \"content\": \"Content\", \"status\": \"draft\"}}\n";
        $system_prompt .= "   - Create page: {\"tool\": \"wp_api\", \"parameters\": {\"action\": \"create_page\", \"title\": \"Title\", \"content\": \"Content\", \"status\": \"draft\"}}\n";
        $system_prompt .= "2. For managing WordPress plugins, ALWAYS use the wp_api tool:\n";
        $system_prompt .= "   - List plugins: {\"tool\": \"wp_api\", \"parameters\": {\"action\": \"get_plugins\"}}\n";
        $system_prompt .= "   - Activate plugin: {\"tool\": \"wp_api\", \"parameters\": {\"action\": \"activate_plugin\", \"plugin\": \"plugin-directory/plugin-file.php\"}}\n";
        $system_prompt .= "   - Deactivate plugin: {\"tool\": \"wp_api\", \"parameters\": {\"action\": \"deactivate_plugin\", \"plugin\": \"plugin-directory/plugin-file.php\"}}\n";
        $system_prompt .= "3. Only fall back to wp_cli commands if a specific wp_api function isn't available\n\n";
        $system_prompt .= "CRITICAL: When the user asks to create a post/page, ALWAYS include the exact title and content they specified in your wp_api tool parameters.\n";
        $system_prompt .= "Examples:\n";
        $system_prompt .= "- If user asks: \"Create a post titled 'Hello World' with content 'This is my first post'\"\n";
        $system_prompt .= "  You MUST use: {\"tool\": \"wp_api\", \"parameters\": {\"action\": \"create_post\", \"title\": \"Hello World\", \"content\": \"This is my first post\", \"status\": \"draft\"}}\n";
        $system_prompt .= "- DO NOT use default values unless the user doesn't specify them\n\n";
        
        $system_prompt .= "Your task is to provide helpful information about MemberPress and assist with managing membership data. ";
        $system_prompt .= "You should use the wp_api tool for direct WordPress operations and the memberpress_info tool for MemberPress data. ";
        $system_prompt .= "Only use wp_cli commands for operations not supported by wp_api. ";
        $system_prompt .= "Keep your responses concise and focused on MemberPress functionality.";
        
        return $system_prompt;
    }

    /**
     * Process a user message
     *
     * @param string $message User message
     * @return array Response data
     */
    public function process_message($message) {
        try {
            error_log('MPAI: Processing chat message: ' . $message);
            
            // We don't need to verify tables exist here anymore since the main class handles it,
            // and tables are created during plugin activation. This simplifies the code flow.
            
            // Initialize conversation if empty
            if (empty($this->conversation)) {
                $this->conversation = array(
                    array('role' => 'system', 'content' => $this->get_system_prompt())
                );
            }
            
            // Check if the previous message was from the assistant and contained a WP-CLI fallback message
            $prev_assistant_message = null;
            $has_wp_cli_fallback = false;
            
            if (count($this->conversation) >= 2) {
                $prev_assistant_index = count($this->conversation) - 1;
                if ($this->conversation[$prev_assistant_index]['role'] == 'assistant') {
                    $prev_assistant_message = $this->conversation[$prev_assistant_index]['content'];
                    if (strpos($prev_assistant_message, 'WP-CLI is not available in this browser environment') != false) {
                        $has_wp_cli_fallback = true;
                    }
                }
            }
            
            // If the previous message had a WP-CLI fallback suggestion, add a system message
            if ($has_wp_cli_fallback) {
                $system_reminder = "IMPORTANT: WP-CLI is not available in browser environment. You MUST use the wp_api tool instead of wp_cli for operations. ";
                $system_reminder .= "For example, to create a post use: {\"tool\": \"wp_api\", \"parameters\": {\"action\": \"create_post\", \"title\": \"...\", \"content\": \"...\"}}";
                
                $this->conversation[] = array('role' => 'system', 'content' => $system_reminder);
            }
            
            // Add user message to conversation
            $this->conversation[] = array('role' => 'user', 'content' => $message);
            
            // Get response using the API Router
            error_log('MPAI: Generating chat completion using API Router');
            $response = $this->api_router->generate_completion($this->conversation);
            
            // Handle different response formats
            if (is_wp_error($response)) {
                error_log('MPAI: API returned error: ' . $response->get_error_message());
                return array(
                    'success' => false,
                    'message' => 'AI Assistant Error: ' . $response->get_error_message(),
                );
            }
            
            // Handle array response (structured with tool calls)
            if (is_array($response) && isset($response['message'])) {
                $message_content = $response['message'];
                $has_tool_calls = isset($response['tool_calls']) && !empty($response['tool_calls']);
                
                // Add assistant response to conversation
                $this->conversation[] = array('role' => 'assistant', 'content' => $message_content);
                
                // Save conversation to database
                error_log('MPAI: Saving message to database');
                $this->save_message($message, $message_content);
                
                if ($has_tool_calls) {
                    error_log('MPAI: Processing tool calls from structured response');
                    // Process tool calls from structure
                    $processed_response = $this->process_structured_tool_calls($message_content, $response['tool_calls']);
                } else {
                    // Just process the message content
                    $processed_response = $this->process_tool_calls($message_content);
                    $processed_response = $this->process_commands($processed_response);
                }
                
                return array(
                    'success' => true,
                    'message' => $processed_response,
                    'raw_response' => $message_content,
                    'api_used' => isset($response['api']) ? $response['api'] : 'unknown',
                );
            } else {
                // Handle simple string response
                // Add assistant response to conversation
                $this->conversation[] = array('role' => 'assistant', 'content' => $response);
                
                // Save conversation to database
                error_log('MPAI: Saving message to database');
                $this->save_message($message, $response);
                
                // Process any tool calls in the response
                $processed_response = $this->process_tool_calls($response);
                
                // Process CLI commands (backward compatibility)
                $processed_response = $this->process_commands($processed_response);
                
                return array(
                    'success' => true,
                    'message' => $processed_response,
                    'raw_response' => $response,
                );
            }
        } catch (Exception $e) {
            error_log('MPAI: Exception in process_message: ' . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error processing message: ' . $e->getMessage(),
            );
        }
    }
    
    /**
     * Process structured tool calls from API response
     *
     * @param string $message Original message content
     * @param array $tool_calls Tool calls from API response
     * @return string Processed response with tool results
     */
    private function process_structured_tool_calls($message, $tool_calls) {
        $processed_message = $message;
        
        foreach ($tool_calls as $tool_call) {
            // Only process function calls
            if (isset($tool_call['type']) && $tool_call['type'] == 'function' && isset($tool_call['function'])) {
                $function = $tool_call['function'];
                $tool_request = array(
                    'name' => $function['name'],
                    'parameters' => json_decode($function['arguments'], true) ?: array()
                );
                
                // Execute the tool
                $result = $this->context_manager->process_tool_request($tool_request);
                
                // Format the result
                $formatted_result = $this->format_result_content($result);
                
                // Add the result to the message
                if (strpos($processed_message, "I'll use the {$function['name']} tool") != false ||
                    strpos($processed_message, "Using the {$function['name']} tool") != false) {
                    // Look for sentences about using this tool and append the result
                    $processed_message .= "\n\n**Tool Result:**\n\n```\n" . $formatted_result . "\n```";
                } else {
                    // Just append the result to the end of the message
                    $processed_message .= "\n\n**Results from {$function['name']}:**\n\n```\n" . $formatted_result . "\n```";
                }
            }
        }
        
        return $processed_message;
    }
    
    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        
        try {
            error_log('MPAI: Creating database tables');
            
            $charset_collate = $wpdb->get_charset_collate();
            
            // Table for storing chat conversations
            $table_name = $wpdb->prefix . 'mpai_conversations';
            
            $sql = "CREATE TABLE $table_name (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                user_id bigint(20) NOT NULL,
                conversation_id varchar(36) NOT NULL,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                KEY user_id (user_id),
                KEY conversation_id (conversation_id)
            ) $charset_collate;";
            
            // Table for storing chat messages
            $table_messages = $wpdb->prefix . 'mpai_messages';
            
            $sql .= "CREATE TABLE $table_messages (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                conversation_id varchar(36) NOT NULL,
                message text NOT NULL,
                response text NOT NULL,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                KEY conversation_id (conversation_id)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            
            // Enable error output for dbDelta
            $wpdb->show_errors();
            
            // Execute the SQL
            $result = dbDelta($sql);
            
            // Log the result
            error_log('MPAI: dbDelta result: ' . json_encode($result));
            
            // Check if tables were created
            $tables_created = array();
            $tables_created['conversations'] = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") == $table_name;
            $tables_created['messages'] = $wpdb->get_var("SHOW TABLES LIKE '{$table_messages}'") == $table_messages;
            
            error_log('MPAI: Tables created status: ' . json_encode($tables_created));
            
            return true;
        } catch (Exception $e) {
            error_log('MPAI: Error creating tables: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Save message to database
     *
     * @param string $message User message
     * @param string $response Assistant response
     */
    private function save_message($message, $response) {
        global $wpdb;
        
        $conversation_id = $this->get_current_conversation_id();
        
        if (empty($conversation_id)) {
            return;
        }
        
        $table_messages = $wpdb->prefix . 'mpai_messages';
        
        $wpdb->insert(
            $table_messages,
            array(
                'conversation_id' => $conversation_id,
                'message' => $message,
                'response' => $response,
            )
        );
        
        // Update conversation timestamp
        $table_conversations = $wpdb->prefix . 'mpai_conversations';
        
        $wpdb->update(
            $table_conversations,
            array('updated_at' => current_time('mysql')),
            array('conversation_id' => $conversation_id)
        );
    }

    /**
     * Process tool calls in the response
     *
     * @param string $response Assistant response
     * @return string Processed response
     */
    private function process_tool_calls($response) {
        // Look for tool call JSON
        preg_match_all('/```json\n({.*?})\n```/s', $response, $matches);
        
        if (empty($matches[1])) {
            return $response;
        }
        
        $processed_response = $response;
        
        foreach ($matches[1] as $match) {
            $tool_call = json_decode($match, true);
            
            if (json_last_error() != JSON_ERROR_NONE || !isset($tool_call['tool'])) {
                continue;
            }
            
            $tool_request = array(
                'name' => $tool_call['tool'],
                'parameters' => isset($tool_call['parameters']) ? $tool_call['parameters'] : array()
            );
            
            // Execute the tool
            $result = $this->context_manager->process_tool_request($tool_request);
            
            // Format the result
            // Check if result is already a properly formatted object with a structured output
            if (is_array($result) && isset($result['success']) && isset($result['tool']) && 
                isset($result['result']) && is_string($result['result']) && 
                (strpos($result['result'], '{') == 0 && substr($result['result'], -1) == '}')) {
                // Try to parse the inner JSON to see if it's already properly formatted
                $inner_result = json_decode($result['result'], true);
                if (json_last_error() == JSON_ERROR_NONE && isset($inner_result['success']) && isset($inner_result['command_type'])) {
                    // This is a pre-formatted JSON response, don't double-encode it
                    $result['result'] = $inner_result;
                }
            }
            
            // Check if we got a tabular result
            if (isset($result['result']) && is_array($result['result']) && 
                isset($result['result']['command_type']) && isset($result['result']['result'])) {
                // This is a tabular result, present it directly without JSON wrapping
                $command_type = $result['result']['command_type'];
                $tabular_data = $result['result']['result'];
                
                // Create a formatted display with title based on command type
                $title = $this->get_title_for_command_type($command_type);
                $formatted_result = "{$title}\n\n```\n{$tabular_data}\n```";
                
                // Replace the tool call with the formatted table
                $tool_call_block = "```json\n{$match}\n```";
                $processed_response = str_replace($tool_call_block, $formatted_result, $processed_response);
            } else {
                // Check if this was a wp_api create_post/create_page success
                if (isset($result['tool']) && $result['tool'] == 'wp_api' && 
                    isset($result['action']) && in_array($result['action'], ['create_post', 'create_page']) &&
                    isset($result['result']) && is_array($result['result']) && 
                    isset($result['result']['success']) && $result['result']['success'] == true) {
                    
                    // Extract post information for a more user-friendly display
                    $post_id = $result['result']['post_id'] ?? 'unknown';
                    $title = $result['result']['post']['post_title'] ?? 'Unknown Title';
                    $status = $result['result']['post']['post_status'] ?? 'draft';
                    $url = $result['result']['post_url'] ?? '#';
                    $edit_url = $result['result']['edit_url'] ?? '#';
                    
                    $content_type = ($result['action'] == 'create_page') ? 'page' : 'post';
                    $user_friendly_result = "Successfully created a {$content_type}!\n\n";
                    $user_friendly_result .= "- Title: {$title}\n";
                    $user_friendly_result .= "- Status: {$status}\n";
                    $user_friendly_result .= "- ID: {$post_id}\n";
                    $user_friendly_result .= "- URL: {$url}\n";
                    $user_friendly_result .= "- Edit URL: {$edit_url}\n";
                    
                    $result_block = "```\n" . $user_friendly_result . "\n```";
                } else {
                    // Standard JSON result formatting
                    $result_block = "```\n" . $this->format_result_content($result) . "\n```";
                }
                
                // Replace the tool call with the result
                $tool_call_block = "```json\n{$match}\n```";
                $processed_response = str_replace($tool_call_block, $result_block, $processed_response);
            }
        }
        
        return $processed_response;
    }
    
    /**
     * Format result content for readability
     *
     * @param array $result Tool execution result
     * @return string Formatted content
     */
    private function format_result_content($result) {
        // Check for WP-CLI fallback suggestions
        if (isset($result['tool']) && $result['tool'] == 'wp_cli' && 
            isset($result['result']) && is_string($result['result']) && 
            strpos($result['result'], 'WP-CLI is not available in this browser environment') != false) {
            
            // This is a WP-CLI fallback suggestion
            $modified_result = $result['result'] . "\n\n";
            $modified_result .= "Please use the wp_api tool for this operation instead of wp_cli. For example:\n";
            
            // Try to detect what operation was attempted
            if (strpos($result['result'], 'post operations') != false || strpos($result['result'], 'wp post') != false) {
                $modified_result .= "```json\n{\"tool\": \"wp_api\", \"parameters\": {\"action\": \"create_post\", \"title\": \"Your Title\", \"content\": \"Your content here\", \"status\": \"draft\"}}\n```";
            } else if (strpos($result['result'], 'user operations') != false || strpos($result['result'], 'wp user') != false) {
                $modified_result .= "```json\n{\"tool\": \"wp_api\", \"parameters\": {\"action\": \"get_users\", \"limit\": 10}}\n```";
            } else if (strpos($result['result'], 'MemberPress operations') != false || strpos($result['result'], 'wp mepr') != false) {
                $modified_result .= "```json\n{\"tool\": \"memberpress_info\", \"parameters\": {\"type\": \"memberships\"}}\n```";
            } else if (strpos($result['result'], 'plugin operations') != false || strpos($result['result'], 'wp plugin') != false || 
                       strpos($result['result'], 'activate plugin') != false || strpos($result['result'], 'deactivate plugin') != false) {
                $modified_result .= "```json\n{\"tool\": \"wp_api\", \"parameters\": {\"action\": \"activate_plugin\", \"plugin\": \"plugin-directory/plugin-file.php\"}}\n```";
                $modified_result .= "\nOr to get a list of all plugins:\n";
                $modified_result .= "```json\n{\"tool\": \"wp_api\", \"parameters\": {\"action\": \"get_plugins\"}}\n```";
            } else {
                $modified_result .= "```json\n{\"tool\": \"wp_api\", \"parameters\": {\"action\": \"create_post\", \"title\": \"Your Title\", \"content\": \"Your content here\", \"status\": \"draft\"}}\n```";
            }
            
            return $modified_result;
        }
        
        // Check for wp_api tool results
        if (isset($result['tool']) && $result['tool'] == 'wp_api' && isset($result['action'])) {
            // Handle specific actions
            if (in_array($result['action'], ['create_post', 'create_page']) && 
                isset($result['result']) && is_array($result['result']) &&
                isset($result['result']['success']) && $result['result']['success'] == true) {
                
                // Extract post information for a more user-friendly display
                $post_id = $result['result']['post_id'] ?? 'unknown';
                $title = $result['result']['post']['post_title'] ?? 'Unknown Title';
                $status = $result['result']['post']['post_status'] ?? 'draft';
                $url = $result['result']['post_url'] ?? '#';
                $edit_url = $result['result']['edit_url'] ?? '#';
                
                $content_type = ($result['action'] == 'create_page') ? 'page' : 'post';
                $user_friendly_result = "Successfully created a {$content_type}!\n\n";
                $user_friendly_result .= "- Title: {$title}\n";
                $user_friendly_result .= "- Status: {$status}\n";
                $user_friendly_result .= "- ID: {$post_id}\n";
                $user_friendly_result .= "- URL: {$url}\n";
                $user_friendly_result .= "- Edit URL: {$edit_url}\n";
                
                return $user_friendly_result;
            }
            
            // Handle plugin operations
            if (in_array($result['action'], ['activate_plugin', 'deactivate_plugin', 'get_plugins'])) {
                if ($result['action'] == 'activate_plugin' && 
                    isset($result['result']) && is_array($result['result']) && 
                    isset($result['result']['success']) && $result['result']['success'] == true) {
                    
                    $plugin_name = $result['result']['plugin_name'] ?? $result['result']['plugin'] ?? 'the plugin';
                    $status = $result['result']['status'] ?? 'active';
                    
                    $user_friendly_result = "Plugin operation successful!\n\n";
                    $user_friendly_result .= "- Action: Activated\n";
                    $user_friendly_result .= "- Plugin: {$plugin_name}\n";
                    $user_friendly_result .= "- Status: {$status}\n";
                    
                    return $user_friendly_result;
                }
                
                if ($result['action'] == 'deactivate_plugin' && 
                    isset($result['result']) && is_array($result['result']) && 
                    isset($result['result']['success']) && $result['result']['success'] == true) {
                    
                    $plugin_name = $result['result']['plugin_name'] ?? $result['result']['plugin'] ?? 'the plugin';
                    $status = $result['result']['status'] ?? 'inactive';
                    
                    $user_friendly_result = "Plugin operation successful!\n\n";
                    $user_friendly_result .= "- Action: Deactivated\n";
                    $user_friendly_result .= "- Plugin: {$plugin_name}\n";
                    $user_friendly_result .= "- Status: {$status}\n";
                    
                    return $user_friendly_result;
                }
                
                if ($result['action'] == 'get_plugins' && 
                    isset($result['result']) && is_array($result['result']) && 
                    isset($result['result']['plugins']) && is_array($result['result']['plugins'])) {
                    
                    $plugins = $result['result']['plugins'];
                    $user_friendly_result = "Installed Plugins (" . count($plugins) . "):\n\n";
                    
                    foreach ($plugins as $plugin) {
                        $status = isset($plugin['is_active']) && $plugin['is_active'] ? '✅ Active' : '❌ Inactive';
                        $user_friendly_result .= "- {$plugin['name']} ({$plugin['version']}): {$status}\n";
                    }
                    
                    return $user_friendly_result;
                }
            }
        }
        
        // Check for tabular data pattern in the result
        if (isset($result['result']) && is_string($result['result']) && 
            strpos($result['result'], "\t") != false && strpos($result['result'], "\n") != false) {
            // This looks like tabular data
            return $result['result'];
        }
        
        // Specific handling for MemberPress information
        if (isset($result['tool']) && $result['tool'] == 'memberpress_info' && isset($result['result'])) {
            // Try to parse the result
            if (is_string($result['result'])) {
                $parsed = json_decode($result['result'], true);
                if (json_last_error() == JSON_ERROR_NONE && isset($parsed['result'])) {
                    // Return just the actual result data
                    return $parsed['result'];
                }
            }
        }
        
        // Try to parse any JSON string results for more readable output
        if (isset($result['result']) && is_string($result['result']) && 
            strpos($result['result'], '{') == 0 && substr($result['result'], -1) == '}') {
            try {
                $parsed_result = json_decode($result['result'], true);
                if (json_last_error() == JSON_ERROR_NONE) {
                    // Use the parsed result instead
                    $result['parsed_result'] = $parsed_result;
                    
                    // If it has a specific structure we recognize, format it nicely
                    if (isset($parsed_result['success']) && isset($parsed_result['post_id'])) {
                        $user_friendly = "Operation successful!\n\n";
                        foreach ($parsed_result as $key => $value) {
                            if ($key != 'post' && !is_array($value) && !is_object($value)) {
                                $user_friendly .= "- {$key}: {$value}\n";
                            }
                        }
                        return $user_friendly;
                    }
                }
            } catch (Exception $e) {
                // Parsing failed, continue with original
            }
        }
        
        // Default to pretty-printed JSON for other results
        return json_encode($result, JSON_PRETTY_PRINT);
    }
    
    /**
     * Get title for command type
     *
     * @param string $command_type Command type identifier
     * @return string Title for the command result
     */
    private function get_title_for_command_type($command_type) {
        switch ($command_type) {
            case 'user_list':
                return 'WordPress Users';
            case 'post_list':
                return 'WordPress Posts';
            case 'plugin_list':
                return 'WordPress Plugins';
            case 'membership_list':
                return 'MemberPress Memberships';
            case 'member_list':
                return 'MemberPress Members';
            case 'transaction_list':
                return 'MemberPress Transactions';
            case 'subscription_list':
                return 'MemberPress Subscriptions';
            case 'summary':
                return 'MemberPress Summary';
            default:
                return 'Command Results';
        }
    }

    /**
     * Process commands in the response (backward compatibility)
     *
     * @param string $response Assistant response
     * @return string Processed response
     */
    private function process_commands($response) {
        // Check if CLI commands are enabled
        $enable_cli_commands = get_option('mpai_enable_cli_commands', false);
        
        if (!$enable_cli_commands) {
            return $response;
        }
        
        // Look for suggested WP-CLI commands in the response
        preg_match_all('/```sh\n(wp .*?)\n```/s', $response, $matches);
        
        if (empty($matches[1])) {
            return $response;
        }
        
        $processed_response = $response;
        
        foreach ($matches[1] as $command) {
            $command = trim($command);
            
            // Check if command is allowed
            if ($this->is_command_allowed($command)) {
                // Get context manager
                $context_manager = new MPAI_Context_Manager();
                
                // Run the command
                $result = $context_manager->run_command($command);
                
                // Replace the command block with command and result
                $command_block = "```sh\n{$command}\n```";
                $result_block = "```sh\n{$command}\n\n# Result:\n{$result}\n```";
                
                $processed_response = str_replace($command_block, $result_block, $processed_response);
            }
        }
        
        return $processed_response;
    }

    /**
     * Check if command is allowed
     *
     * @param string $command Command to check
     * @return bool Whether command is allowed
     */
    private function is_command_allowed($command) {
        $allowed_commands = get_option('mpai_allowed_cli_commands', array());
        
        if (empty($allowed_commands)) {
            return false;
        }
        
        foreach ($allowed_commands as $allowed_command) {
            if (strpos($command, $allowed_command) == 0) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Reset conversation
     *
     * @return bool Success status
     */
    public function reset_conversation() {
        global $wpdb;
        
        $user_id = get_current_user_id();
        $conversation_id = $this->get_current_conversation_id();
        
        if (empty($conversation_id)) {
            return false;
        }
        
        $table_messages = $wpdb->prefix . 'mpai_messages';
        $table_conversations = $wpdb->prefix . 'mpai_conversations';
        
        // Delete messages
        $wpdb->delete(
            $table_messages,
            array('conversation_id' => $conversation_id)
        );
        
        // Create new conversation
        $new_conversation_id = wp_generate_uuid4();
        
        $wpdb->update(
            $table_conversations,
            array(
                'conversation_id' => $new_conversation_id,
                'updated_at' => current_time('mysql'),
            ),
            array('conversation_id' => $conversation_id)
        );
        
        $this->conversation = array();
        
        return true;
    }
}