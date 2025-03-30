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
            if ($wpdb->get_var("SHOW TABLES LIKE '{$table_messages}'") !== $table_messages) {
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
            if ($wpdb->get_var("SHOW TABLES LIKE '{$table_conversations}'") !== $table_conversations) {
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
                
                if ($result === false) {
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
        $system_prompt .= "1. ALWAYS use the wp_cli tool to run WP-CLI commands (like 'wp user list' or 'wp post list')\n";
        $system_prompt .= "2. ALWAYS use the memberpress_info tool to get MemberPress-specific data\n";
        $system_prompt .= "3. DO NOT simply suggest commands - actually execute them using the tool format above\n\n";
        
        $system_prompt .= "Your task is to provide helpful information about MemberPress and assist with managing membership data. ";
        $system_prompt .= "You can and should run WP-CLI commands where appropriate using the wp_cli tool.";
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
            if (isset($tool_call['type']) && $tool_call['type'] === 'function' && isset($tool_call['function'])) {
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
                if (strpos($processed_message, "I'll use the {$function['name']} tool") !== false ||
                    strpos($processed_message, "Using the {$function['name']} tool") !== false) {
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
            $tables_created['conversations'] = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
            $tables_created['messages'] = $wpdb->get_var("SHOW TABLES LIKE '{$table_messages}'") === $table_messages;
            
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
            
            if (json_last_error() !== JSON_ERROR_NONE || !isset($tool_call['tool'])) {
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
                (strpos($result['result'], '{') === 0 && substr($result['result'], -1) === '}')) {
                // Try to parse the inner JSON to see if it's already properly formatted
                $inner_result = json_decode($result['result'], true);
                if (json_last_error() === JSON_ERROR_NONE && isset($inner_result['success']) && isset($inner_result['command_type'])) {
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
                // Standard JSON result formatting
                $result_block = "```\n" . $this->format_result_content($result) . "\n```";
                
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
        // Check for tabular data pattern in the result
        if (isset($result['result']) && is_string($result['result']) && 
            strpos($result['result'], "\t") !== false && strpos($result['result'], "\n") !== false) {
            // This looks like tabular data
            return $result['result'];
        }
        
        // Specific handling for MemberPress information
        if (isset($result['tool']) && $result['tool'] === 'memberpress_info' && isset($result['result'])) {
            // Try to parse the result
            if (is_string($result['result'])) {
                $parsed = json_decode($result['result'], true);
                if (json_last_error() === JSON_ERROR_NONE && isset($parsed['result'])) {
                    // Return just the actual result data
                    return $parsed['result'];
                }
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
            if (strpos($command, $allowed_command) === 0) {
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