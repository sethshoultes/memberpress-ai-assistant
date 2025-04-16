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
        try {
            mpai_log_debug('Constructor started', 'chat');
            
            // Make sure all required classes are loaded
            if (!class_exists('MPAI_API_Router')) {
                mpai_log_debug('MPAI_API_Router class not found, attempting to load', 'chat');
                if (file_exists(MPAI_PLUGIN_DIR . 'includes/class-mpai-api-router.php')) {
                    require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-api-router.php';
                    mpai_log_debug('MPAI_API_Router file loaded', 'chat');
                } else {
                    mpai_log_error('MPAI_API_Router file not found at: ' . MPAI_PLUGIN_DIR . 'includes/class-mpai-api-router.php', 'chat');
                    throw new Exception('Required class file MPAI_API_Router not found');
                }
            }
            
            if (!class_exists('MPAI_MemberPress_API')) {
                mpai_log_debug('MPAI_MemberPress_API class not found, attempting to load', 'chat');
                if (file_exists(MPAI_PLUGIN_DIR . 'includes/class-mpai-memberpress-api.php')) {
                    require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-memberpress-api.php';
                    mpai_log_debug('MPAI_MemberPress_API file loaded', 'chat');
                } else {
                    mpai_log_error('MPAI_MemberPress_API file not found at: ' . MPAI_PLUGIN_DIR . 'includes/class-mpai-memberpress-api.php', 'chat');
                    throw new Exception('Required class file MPAI_MemberPress_API not found');
                }
            }
            
            if (!class_exists('MPAI_Context_Manager')) {
                mpai_log_debug('MPAI_Context_Manager class not found, attempting to load', 'chat');
                if (file_exists(MPAI_PLUGIN_DIR . 'includes/class-mpai-context-manager.php')) {
                    require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-context-manager.php';
                    mpai_log_debug('MPAI_Context_Manager file loaded', 'chat');
                } else {
                    mpai_log_error('MPAI_Context_Manager file not found at: ' . MPAI_PLUGIN_DIR . 'includes/class-mpai-context-manager.php', 'chat');
                    throw new Exception('Required class file MPAI_Context_Manager not found');
                }
            }
            
            // Now create instances
            try {
                mpai_log_debug('Creating API Router instance', 'chat');
                $this->api_router = new MPAI_API_Router();
                mpai_log_debug('API Router instance created successfully', 'chat');
            } catch (Throwable $e) {
                mpai_log_error('Error creating API Router instance: ' . $e->getMessage(), 'chat', array(
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ));
                throw new Exception('Failed to initialize API Router: ' . $e->getMessage());
            }
            
            try {
                mpai_log_debug('Creating MemberPress API instance', 'chat');
                $this->memberpress_api = new MPAI_MemberPress_API();
                mpai_log_debug('MemberPress API instance created successfully', 'chat');
            } catch (Throwable $e) {
                mpai_log_error('Error creating MemberPress API instance: ' . $e->getMessage(), 'chat', array(
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ));
                throw new Exception('Failed to initialize MemberPress API: ' . $e->getMessage());
            }
            
            try {
                mpai_log_debug('Creating Context Manager instance', 'chat');
                $this->context_manager = new MPAI_Context_Manager();
                mpai_log_debug('Context Manager instance created successfully', 'chat');
            } catch (Throwable $e) {
                mpai_log_error('Error creating Context Manager instance: ' . $e->getMessage(), 'chat', array(
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ));
                throw new Exception('Failed to initialize Context Manager: ' . $e->getMessage());
            }
            
            // Set this instance in the context manager to enable message extraction
            try {
                mpai_log_debug('Setting chat instance in context manager', 'chat');
                if (method_exists($this->context_manager, 'set_chat_instance')) {
                    $this->context_manager->set_chat_instance($this);
                    mpai_log_debug('Chat instance set in context manager successfully', 'chat');
                } else {
                    mpai_log_warning('set_chat_instance method not found in context manager', 'chat');
                }
            } catch (Throwable $e) {
                mpai_log_error('Error setting chat instance in context manager: ' . $e->getMessage(), 'chat', array(
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ));
                // Continue even if this fails
            }
            
            try {
                mpai_log_debug('Loading conversation history', 'chat');
                $this->load_conversation();
                mpai_log_debug('Conversation history loaded successfully', 'chat');
            } catch (Throwable $e) {
                mpai_log_error('Error loading conversation history: ' . $e->getMessage(), 'chat', array(
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ));
                // Not throwing here since we can continue without conversation history
            }
            
            mpai_log_debug('Constructor completed successfully', 'chat');
        } catch (Throwable $e) {
            mpai_log_error('CRITICAL ERROR in constructor: ' . $e->getMessage(), 'chat', array(
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ));
            throw $e; // Re-throw to be caught by caller
        }
    }

    /**
     * Load conversation history from database
     */
    private function load_conversation() {
        try {
            global $wpdb;
            
            $user_id = get_current_user_id();
            
            if (empty($user_id)) {
                mpai_log_debug('No user ID available to load conversation', 'chat');
                return;
            }
            
            $conversation_id = $this->get_current_conversation_id();
            
            if (empty($conversation_id)) {
                mpai_log_debug('No conversation ID available to load conversation', 'chat');
                return;
            }
            
            $table_messages = $wpdb->prefix . 'mpai_messages';
            
            // Verify the messages table exists
            if ($wpdb->get_var("SHOW TABLES LIKE '{$table_messages}'") != $table_messages) {
                mpai_log_warning('Messages table does not exist, cannot load conversation', 'chat');
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
                mpai_log_debug('No messages found for conversation ID: ' . $conversation_id, 'chat');
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
            
            mpai_log_debug('Loaded ' . count($messages) . ' messages from conversation', 'chat');
        } catch (Exception $e) {
            mpai_log_error('Error loading conversation: ' . $e->getMessage(), 'chat', array(
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ));
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
                mpai_log_debug('No user ID available to get conversation ID', 'chat');
                return null;
            }
            
            $table_conversations = $wpdb->prefix . 'mpai_conversations';
            
            // Check if the conversations table exists
            if ($wpdb->get_var("SHOW TABLES LIKE '{$table_conversations}'") != $table_conversations) {
                mpai_log_warning('Conversations table does not exist', 'chat');
                
                // Try to create the table
                mpai_log_debug('Attempting to create conversation tables via chat class', 'chat');
                if (!$this->create_tables()) {
                    mpai_log_error('Failed to create conversation tables', 'chat');
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
                    mpai_log_error('Failed to create new conversation: ' . $wpdb->last_error, 'chat');
                    return null;
                }
                
                mpai_log_debug('Created new conversation with ID: ' . $conversation_id, 'chat');
            } else {
                mpai_log_debug('Found existing conversation with ID: ' . $conversation_id, 'chat');
            }
            
            return $conversation_id;
        } catch (Exception $e) {
            mpai_log_error('Error getting conversation ID: ' . $e->getMessage(), 'chat', array(
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ));
            return null;
        }
    }

    /**
     * Get system prompt with MemberPress context
     *
     * @return string System prompt
     */
    private function get_system_prompt() {
        mpai_log_debug('Getting fresh system prompt', 'chat');
        
        // Make sure the memberpress API instance is fresh
        if (!isset($this->memberpress_api) || !is_object($this->memberpress_api)) {
            mpai_log_debug('Recreating MemberPress API instance for fresh data', 'chat');
            $this->memberpress_api = new MPAI_MemberPress_API();
        }
        
        // Force a refresh of the MemberPress data - we don't want cached values
        mpai_log_debug('Fetching fresh MemberPress data summary', 'chat');
        $memberpress_data = $this->memberpress_api->get_data_summary(true); // Pass true to force refresh
        
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
        $system_prompt .= "   - For best-selling memberships: {\"tool\": \"memberpress_info\", \"parameters\": {\"type\": \"best_selling\"}}\n";
        $system_prompt .= "   - For active subscriptions: {\"tool\": \"memberpress_info\", \"parameters\": {\"type\": \"active_subscriptions\"}}\n";
        $system_prompt .= "   - For all subscriptions: {\"tool\": \"memberpress_info\", \"parameters\": {\"type\": \"subscriptions\"}}\n";
        $system_prompt .= "   - For WordPress and server information: {\"tool\": \"memberpress_info\", \"parameters\": {\"type\": \"system_info\"}}\n";
        $system_prompt .= "   - For complete data with system info: {\"tool\": \"memberpress_info\", \"parameters\": {\"type\": \"all\", \"include_system_info\": true}}\n";
        $system_prompt .= "   - The system_info type uses WordPress Site Health API for comprehensive diagnostics\n";
        $system_prompt .= "4. DO NOT simply suggest commands - actually execute them using the tool format above\n\n";
        
        $system_prompt .= "CRITICAL TOOL SELECTION RULES:\n";
        $system_prompt .= "1. For creating/editing WordPress content (posts, pages, users), ALWAYS use the wp_api tool first:\n";
        $system_prompt .= "   - Create post: {\"tool\": \"wp_api\", \"parameters\": {\"action\": \"create_post\", \"title\": \"Title\", \"content\": \"Content\", \"status\": \"draft\"}}\n";
        $system_prompt .= "   - Create page: {\"tool\": \"wp_api\", \"parameters\": {\"action\": \"create_page\", \"title\": \"Title\", \"content\": \"Content\", \"status\": \"draft\"}}\n";
        $system_prompt .= "2. For managing WordPress plugins, ALWAYS use the wp_api tool:\n";
        $system_prompt .= "   - List plugins: {\"tool\": \"wp_api\", \"parameters\": {\"action\": \"get_plugins\"}}\n";
        $system_prompt .= "   - Activate plugin: {\"tool\": \"wp_api\", \"parameters\": {\"action\": \"activate_plugin\", \"plugin\": \"plugin-directory/main-file.php\"}}\n";
        $system_prompt .= "   - Example for MemberPress CoachKit: {\"tool\": \"wp_api\", \"parameters\": {\"action\": \"activate_plugin\", \"plugin\": \"memberpress-coachkit/main.php\"}}\n";
        $system_prompt .= "   - Deactivate plugin: {\"tool\": \"wp_api\", \"parameters\": {\"action\": \"deactivate_plugin\", \"plugin\": \"plugin-directory/main-file.php\"}}\n";
        $system_prompt .= "3. ██████ MOST IMPORTANT RULE ██████\n";
        $system_prompt .= "   FOR ANY QUESTIONS ABOUT PLUGIN HISTORY, INSTALLED PLUGINS, OR ACTIVATED PLUGINS, YOU MUST CALL THE PLUGIN_LOGS TOOL.\n";
        $system_prompt .= "   DO NOT provide generic advice or alternative methods when asked about plugin activity.\n";
        $system_prompt .= "   FORMAT YOUR RESPONSE AS ONLY THE JSON TOOL CALL WITHOUT EXPLANATORY TEXT. For example:\n";
        $system_prompt .= "   ```json\n";
        $system_prompt .= "   {\"tool\": \"plugin_logs\", \"parameters\": {\"action\": \"activated\", \"days\": 30}}\n";
        $system_prompt .= "   ```\n\n";
        $system_prompt .= "   Examples of when to use plugin_logs tool:\n";
        $system_prompt .= "   - View recent plugin activity: {\"tool\": \"plugin_logs\", \"parameters\": {\"days\": 30}}\n";
        $system_prompt .= "   - View recently activated plugins: {\"tool\": \"plugin_logs\", \"parameters\": {\"action\": \"activated\", \"days\": 30}}\n";
        $system_prompt .= "   - View recently installed plugins: {\"tool\": \"plugin_logs\", \"parameters\": {\"action\": \"installed\", \"days\": 30}}\n";
        $system_prompt .= "   - View plugin history for specific plugin: {\"tool\": \"plugin_logs\", \"parameters\": {\"plugin_name\": \"MemberPress\", \"days\": 90}}\n";
        $system_prompt .= "4. Only fall back to wp_cli commands if a specific wp_api function isn't available\n\n";
        $system_prompt .= "CRITICAL: When the user asks to create a post/page, ALWAYS include the exact title and content they specified in your wp_api tool parameters.\n";
        $system_prompt .= "Examples:\n";
        $system_prompt .= "- If user asks: \"Create a post titled 'Hello World' with content 'This is my first post'\"\n";
        $system_prompt .= "  You MUST use: {\"tool\": \"wp_api\", \"parameters\": {\"action\": \"create_post\", \"title\": \"Hello World\", \"content\": \"This is my first post\", \"status\": \"draft\"}}\n";
        $system_prompt .= "- DO NOT use default values unless the user doesn't specify them\n\n";
        
        // Add blog post XML formatting instructions
        $system_prompt .= "IMPORTANT: When asked to create a blog post, always write the post content in the following XML format:\n";
        $system_prompt .= "<wp-post>\n";
        $system_prompt .= "  <post-title>Your Post Title Here</post-title>\n";
        $system_prompt .= "  <post-content>\n";
        $system_prompt .= "    <block type=\"paragraph\">This is a paragraph block of content.</block>\n";
        $system_prompt .= "    <block type=\"heading\" level=\"2\">This is a heading block</block>\n";
        $system_prompt .= "    <block type=\"paragraph\">Another paragraph block with content.</block>\n";
        $system_prompt .= "    <!-- Add more blocks as needed -->\n";
        $system_prompt .= "  </post-content>\n";
        $system_prompt .= "  <post-excerpt>A brief summary of the post.</post-excerpt>\n";
        $system_prompt .= "  <post-status>draft</post-status> <!-- draft or publish -->\n";
        $system_prompt .= "</wp-post>\n\n";
        $system_prompt .= "This XML format ensures the post will be correctly processed by WordPress Gutenberg. Available block types include:\n";
        $system_prompt .= "- paragraph: For regular text content\n";
        $system_prompt .= "- heading: For headings (use level attribute: 2 for H2, 3 for H3, etc.)\n";
        $system_prompt .= "- list: For unordered lists (wrap list items in <item> tags)\n";
        $system_prompt .= "- ordered-list: For ordered/numbered lists (wrap list items in <item> tags)\n";
        $system_prompt .= "- quote: For block quotes\n";
        $system_prompt .= "- code: For code blocks\n";
        $system_prompt .= "- image: For image URLs (place URL as the block content)\n\n";
        
        $system_prompt .= "Your task is to provide helpful information about MemberPress and assist with managing membership data. ";
        $system_prompt .= "You should use the wp_api tool for direct WordPress operations and the memberpress_info tool for MemberPress data. ";
        $system_prompt .= "CRITICAL INSTRUCTION: When the user asks about plugin history, recently installed plugins, or recently activated plugins, ALWAYS use the plugin_logs tool to get accurate information from the database. ";
        $system_prompt .= "When responding to these plugin-related queries, FORMAT YOUR RESPONSE AS ONLY THE JSON TOOL CALL WITHOUT ANY OTHER TEXT - just provide the exact JSON format shown in the examples, e.g.:\n";
        $system_prompt .= "```json\n{\"tool\": \"plugin_logs\", \"parameters\": {\"action\": \"activated\", \"days\": 30}}\n```\n\n";
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
            mpai_log_debug('process_message started with message: ' . $message, 'chat');
            
            // Check for plugin-related queries and add a guidance message
            $plugin_keywords = ['recently installed', 'recently activated', 'plugin history', 'plugin log', 'plugin activity', 'when was plugin', 'installed recently', 'activated recently', 'what plugins', 'which plugins'];
            $inject_plugin_guidance = false;
            
            // Special handling for common WP-CLI commands - enhanced for reliability
            $lower_message = strtolower(trim($message));
            $is_wp_plugin_list_command = ($lower_message === 'wp plugin list' || 
                                          $lower_message === 'list plugins' || 
                                          $lower_message === 'show plugins' || 
                                          $lower_message === 'plugins');
                                          
            if ($is_wp_plugin_list_command) {
                mpai_log_debug('CRITICAL! Detected plugin list command: "' . $message . '" - ' . date('H:i:s'), 'chat');
                
                // DIRECT EXECUTION - Skip AI entirely for this specific command
                try {
                    mpai_log_debug('Attempting direct execution of wp plugin list...', 'chat');
                    
                    // Use a try/catch with multiple fallback methods
                    try {
                        // First attempt: Use context manager
                        mpai_log_debug('Using primary method (context manager)', 'chat');
                        if (!isset($this->context_manager)) {
                            $this->context_manager = new MPAI_Context_Manager();
                            mpai_log_debug('Created new Context Manager for direct execution', 'chat');
                        }
                        
                        // Execute the command directly
                        $plugin_list_output = $this->context_manager->run_command('wp plugin list');
                        mpai_log_debug('Direct execution successful, output length: ' . strlen($plugin_list_output), 'chat');
                    } catch (Throwable $e) {
                        // Second attempt: Use WP-CLI executor directly
                        mpai_log_debug('Primary method failed, using fallback method (direct executor)', 'chat');
                        
                        // Include necessary files
                        $executor_file = MPAI_PLUGIN_DIR . '/includes/commands/class-mpai-wp-cli-executor.php';
                        if (file_exists($executor_file)) {
                            require_once $executor_file;
                            
                            if (class_exists('MPAI_WP_CLI_Executor')) {
                                $executor = new MPAI_WP_CLI_Executor();
                                $result = $executor->execute('wp plugin list');
                                
                                if (is_array($result) && isset($result['output'])) {
                                    $plugin_list_output = $result['output'];
                                } else {
                                    $plugin_list_output = is_string($result) ? $result : json_encode($result);
                                }
                                
                                mpai_log_debug('Fallback executor successful, output length: ' . strlen($plugin_list_output), 'chat');
                            } else {
                                // Third attempt: Use direct WordPress API
                                mpai_log_debug('Second method failed, using final fallback (WordPress API)', 'chat');
                                $plugin_list_output = $this->get_direct_plugin_list();
                            }
                        } else {
                            // Third attempt: Use direct WordPress API
                            mpai_log_debug('Second method failed, using final fallback (WordPress API)', 'chat');
                            $plugin_list_output = $this->get_direct_plugin_list();
                        }
                    }
                    
                    // Extract actual table data if it's a JSON string
                    if (is_string($plugin_list_output) && 
                        strpos($plugin_list_output, '{"success":true,"tool":"wp_cli","command_type":"plugin_list","result":') === 0) {
                        mpai_log_debug('Plugin list is in JSON format, extracting tabular data', 'chat');
                        try {
                            $decoded = json_decode($plugin_list_output, true);
                            if (json_last_error() === JSON_ERROR_NONE && isset($decoded['result'])) {
                                $plugin_list_output = $decoded['result'];
                                mpai_log_debug('Successfully extracted tabular data from plugin list JSON', 'chat');
                            }
                        } catch (Throwable $e) {
                            mpai_log_error('Error decoding plugin list JSON: ' . $e->getMessage(), 'chat', array(
                                'file' => $e->getFile(),
                                'line' => $e->getLine(),
                                'trace' => $e->getTraceAsString()
                            ));
                        }
                    }
                    
                    // Ensure plugin_list_output is a string
                    if (!is_string($plugin_list_output)) {
                        if (is_array($plugin_list_output) || is_object($plugin_list_output)) {
                            $plugin_list_output = json_encode($plugin_list_output, JSON_PRETTY_PRINT);
                        } else {
                            $plugin_list_output = (string)$plugin_list_output;
                        }
                    }
                    
                    $ai_response = "Here is the current list of plugins:\n\n";
                    $ai_response .= "```\n" . trim($plugin_list_output) . "\n```\n\n";
                    $ai_response .= "This information was generated directly from your WordPress database.";
                    
                    // Save this message and response
                    $this->save_message($message, $ai_response);
                    
                    mpai_log_debug('Direct handling complete for wp plugin list command', 'chat');
                    
                    // Return the response immediately in a format that the AJAX handler expects
                    return array(
                        'success' => true,
                        'message' => $ai_response,
                    );
                } catch (Throwable $e) {
                    mpai_log_error('Error in direct execution of wp plugin list: ' . $e->getMessage(), 'chat', array(
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString()
                    ));
                    // Trace is already included in the mpai_log_error call above
                    // Continue with normal processing if direct execution fails
                }
            }
            
            // Check for best-selling membership queries
            $best_selling_keywords = ['best-selling membership', 'best selling membership', 'top selling membership', 'popular membership', 'best performing membership', 'top membership', 'most popular membership', 'most sold membership'];
            $inject_best_selling_guidance = false;
            
            // Check for active subscriptions queries
            $active_subscriptions_keywords = ['active subscription', 'current subscription', 'active member', 'current member', 'active membership', 'active membership list'];
            $inject_active_subscriptions_guidance = false;
            
            foreach ($plugin_keywords as $keyword) {
                if (stripos($message, $keyword) !== false) {
                    $inject_plugin_guidance = true;
                    mpai_log_debug('Detected plugin-related query, will inject guidance', 'chat');
                    break;
                }
            }
            
            foreach ($best_selling_keywords as $keyword) {
                if (stripos($message, $keyword) !== false) {
                    $inject_best_selling_guidance = true;
                    mpai_log_debug('Detected best-selling membership query, will inject guidance', 'chat');
                    break;
                }
            }
            
            foreach ($active_subscriptions_keywords as $keyword) {
                if (stripos($message, $keyword) !== false) {
                    $inject_active_subscriptions_guidance = true;
                    mpai_log_debug('Detected active subscriptions query, will inject guidance', 'chat');
                    break;
                }
            }
            
            // First check if we have all required dependencies initialized
            if (!isset($this->api_router) || !is_object($this->api_router)) {
                mpai_log_debug('API Router not initialized, attempting to create', 'chat');
                try {
                    if (class_exists('MPAI_API_Router')) {
                        $this->api_router = new MPAI_API_Router();
                        mpai_log_debug('API Router created successfully in process_message', 'chat');
                    } else {
                        mpai_log_error('MPAI_API_Router class not available', 'chat');
                        return array(
                            'success' => false,
                            'message' => 'Internal error: API Router not available'
                        );
                    }
                } catch (Throwable $e) {
                    mpai_log_error('Failed to create API Router: ' . $e->getMessage(), 'chat', array(
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString()
                    ));
                    return array(
                        'success' => false,
                        'message' => 'Failed to initialize API router: ' . $e->getMessage()
                    );
                }
            }
            
            // Initialize conversation if empty
            try {
                if (empty($this->conversation)) {
                    mpai_log_debug('Conversation is empty, initializing with system prompt', 'chat');
                    $system_prompt = $this->get_system_prompt();
                    mpai_log_debug('Got system prompt of length: ' . strlen($system_prompt), 'chat');
                    $this->conversation = array(
                        array('role' => 'system', 'content' => $system_prompt)
                    );
                    mpai_log_debug('Conversation initialized with system prompt', 'chat');
                }
                
                // If this is a plugin-related query, add a system message reminder
                if ($inject_plugin_guidance) {
                    mpai_log_debug('Adding plugin logs guidance message to conversation', 'chat');
                    $plugin_guidance = "CRITICAL INSTRUCTION: This query is about plugin history or activity. You MUST respond by calling the plugin_logs tool to get accurate information from the database. DO NOT provide any general advice or alternative methods. DO NOT use wp_api or try to guess plugin history. Format your response using ONLY the JSON format shown below.\n\n";
                    $plugin_guidance .= "For general plugin activity, use exactly:\n```json\n{\"tool\": \"plugin_logs\", \"parameters\": {\"days\": 30}}\n```\n\n";
                    $plugin_guidance .= "For queries about recently activated plugins, use exactly:\n```json\n{\"tool\": \"plugin_logs\", \"parameters\": {\"action\": \"activated\", \"days\": 30}}\n```\n\n";
                    $plugin_guidance .= "For queries about recently installed plugins, use exactly:\n```json\n{\"tool\": \"plugin_logs\", \"parameters\": {\"action\": \"installed\", \"days\": 30}}\n```\n\n";
                    $plugin_guidance .= "DO NOT explain what you're doing or wrap your tool call in prose - ONLY return the JSON block.";
                    
                    $this->conversation[] = array('role' => 'system', 'content' => $plugin_guidance);
                    mpai_log_debug('Enhanced plugin logs guidance message added', 'chat');
                }
                
                // If this is a best-selling membership query, add a system message reminder
                if ($inject_best_selling_guidance) {
                    mpai_log_debug('Adding best-selling membership guidance message to conversation', 'chat');
                    $best_selling_guidance = "CRITICAL INSTRUCTION: This query is about best-selling or popular memberships. You MUST respond by calling the memberpress_info tool with type=best_selling to get accurate information from the database. DO NOT provide any general advice or theoretical explanations. Format your response using the JSON format shown below.\n\n";
                    $best_selling_guidance .= "For best-selling memberships, use exactly:\n```json\n{\"tool\": \"memberpress_info\", \"parameters\": {\"type\": \"best_selling\"}}\n```\n\n";
                    $best_selling_guidance .= "After executing the tool call, explain the results to the user.";
                    
                    $this->conversation[] = array('role' => 'system', 'content' => $best_selling_guidance);
                    mpai_log_debug('Enhanced best-selling membership guidance message added', 'chat');
                }
                
                // If this is an active subscriptions query, add a system message reminder
                if ($inject_active_subscriptions_guidance) {
                    mpai_log_debug('Adding active subscriptions guidance message to conversation', 'chat');
                    $active_subscriptions_guidance = "CRITICAL INSTRUCTION: This query is about active subscriptions or current members. You MUST respond by calling the memberpress_info tool with type=active_subscriptions to get accurate information from the database. DO NOT provide any general advice or theoretical explanations. Format your response using the JSON format shown below.\n\n";
                    $active_subscriptions_guidance .= "For active subscriptions, use exactly:\n```json\n{\"tool\": \"memberpress_info\", \"parameters\": {\"type\": \"active_subscriptions\"}}\n```\n\n";
                    $active_subscriptions_guidance .= "After executing the tool call, explain the results to the user.";
                    
                    $this->conversation[] = array('role' => 'system', 'content' => $active_subscriptions_guidance);
                    mpai_log_debug('Enhanced active subscriptions guidance message added', 'chat');
                }
            } catch (Throwable $e) {
                mpai_log_error('Error initializing conversation: ' . $e->getMessage(), 'chat', array(
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ));
                // Continue without system prompt if it fails
                if (empty($this->conversation)) {
                    $this->conversation = array();
                    mpai_log_debug('Created empty conversation array', 'chat');
                }
            }
            
            // Check if the previous message was from the assistant and contained a WP-CLI fallback message
            try {
                mpai_log_debug('Checking for previous WP-CLI fallback messages', 'chat');
                $prev_assistant_message = null;
                $has_wp_cli_fallback = false;
                
                if (count($this->conversation) >= 2) {
                    $prev_assistant_index = count($this->conversation) - 1;
                    if (isset($this->conversation[$prev_assistant_index]['role']) && $this->conversation[$prev_assistant_index]['role'] == 'assistant') {
                        $prev_assistant_message = $this->conversation[$prev_assistant_index]['content'];
                        if (is_string($prev_assistant_message) && strpos($prev_assistant_message, 'WP-CLI is not available in this browser environment') !== false) {
                            $has_wp_cli_fallback = true;
                            mpai_log_debug('Found WP-CLI fallback message', 'chat');
                        }
                    }
                }
                
                // If the previous message had a WP-CLI fallback suggestion, add a system message
                if ($has_wp_cli_fallback) {
                    mpai_log_debug('Adding WP-CLI fallback reminder', 'chat');
                    $system_reminder = "IMPORTANT: WP-CLI is not available in browser environment. You MUST use the wp_api tool instead of wp_cli for operations. ";
                    $system_reminder .= "For example, to create a post use: {\"tool\": \"wp_api\", \"parameters\": {\"action\": \"create_post\", \"title\": \"...\", \"content\": \"...\"}}";
                    
                    $this->conversation[] = array('role' => 'system', 'content' => $system_reminder);
                    mpai_log_debug('Added system reminder about WP-CLI fallback', 'chat');
                }
            } catch (Throwable $e) {
                mpai_log_error('Error checking for WP-CLI fallback: ' . $e->getMessage(), 'chat', array(
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ));
                // Continue even if this fails
            }
            
            // Add user message to conversation
            try {
                mpai_log_debug('Adding user message to conversation', 'chat');
                $this->conversation[] = array('role' => 'user', 'content' => $message);
                mpai_log_debug('User message added to conversation', 'chat');
            } catch (Throwable $e) {
                mpai_log_error('Error adding user message to conversation: ' . $e->getMessage(), 'chat', array(
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ));
                // Initialize conversation with just the user message if adding fails
                $this->conversation = array(
                    array('role' => 'user', 'content' => $message)
                );
                mpai_log_debug('Created new conversation with only user message', 'chat');
            }
            
            // Get response using the API Router
            try {
                mpai_log_debug('Generating chat completion using API Router', 'chat');
                $response = $this->api_router->generate_completion($this->conversation);
                mpai_log_debug('Received response from API Router', 'chat');
            } catch (Throwable $e) {
                mpai_log_error('Error generating completion: ' . $e->getMessage(), 'chat', array(
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ));
                return array(
                    'success' => false,
                    'message' => 'Error generating AI response: ' . $e->getMessage()
                );
            }
            
            // Handle different response formats
            try {
                mpai_log_debug('Checking response format', 'chat');
                if (is_wp_error($response)) {
                    mpai_log_error('API returned WP_Error: ' . $response->get_error_message(), 'chat');
                    return array(
                        'success' => false,
                        'message' => 'AI Assistant Error: ' . $response->get_error_message(),
                    );
                }
                mpai_log_debug('Response is not a WP_Error', 'chat');
            } catch (Throwable $e) {
                mpai_log_error('Error checking if response is WP_Error: ' . $e->getMessage(), 'chat', array(
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ));
                // Continue processing in case it's not a WP_Error
            }
            
            // Handle array response (structured with tool calls)
            try {
                mpai_log_debug('Processing array response format', 'chat');
                if (is_array($response) && isset($response['message'])) {
                    mpai_log_debug('Response has message field', 'chat');
                    $message_content = $response['message'];
                    $has_tool_calls = isset($response['tool_calls']) && !empty($response['tool_calls']);
                    mpai_log_debug('Response has tool calls: ' . ($has_tool_calls ? 'yes' : 'no'), 'chat');
                    
                    // Check if this response looks like it contains a blog post or page
                    // and add a marker if it does
                    $modified_content = $message_content;
                    
                    try {
                        mpai_log_debug('Checking for content patterns', 'chat');
                        
                        // Check for XML formatted blog post first
                        if (preg_match('/<wp-post>.*?<\/wp-post>/s', $message_content)) {
                            // This is an XML formatted blog post
                            if (method_exists($this, 'add_content_marker')) {
                                $modified_content = $this->add_content_marker($message_content, 'blog-post');
                                mpai_log_debug('Added blog-post marker to XML-formatted response', 'chat');
                            } else {
                                mpai_log_warning('add_content_marker method not available', 'chat');
                            }
                        }
                        // Check for traditional blog post content patterns as fallback
                        else if (preg_match('/(?:#+\s*Title:?|Title:)\s*([^\n]+)/i', $message_content) ||
                            (preg_match('/^#+\s*([^\n]+)/i', $message_content) && 
                             preg_match('/introduction|summary|overview|content|body|conclusion/i', $message_content))) {
                            
                            // This looks like a traditional blog post or article
                            if (method_exists($this, 'add_content_marker')) {
                                $modified_content = $this->add_content_marker($message_content, 'blog-post');
                                mpai_log_debug('Added blog-post marker to traditional response', 'chat');
                            } else {
                                mpai_log_warning('add_content_marker method not available', 'chat');
                            }
                        }
                        
                        // Check for page content patterns
                        if (strpos(strtolower($message), 'create a page') !== false && 
                            (preg_match('/(?:#+\s*Title:?|Title:)\s*([^\n]+)/i', $message_content) ||
                            preg_match('/^#+\s*([^\n]+)/i', $message_content))) {
                            
                            // This looks like a page
                            if (method_exists($this, 'add_content_marker')) {
                                $modified_content = $this->add_content_marker($message_content, 'page');
                                mpai_log_debug('Added page marker to response', 'chat');
                            } else {
                                mpai_log_warning('add_content_marker method not available for page', 'chat');
                            }
                        }
                        
                        // Check for membership content patterns
                        if (strpos(strtolower($message), 'membership') !== false && 
                            (strpos(strtolower($message_content), 'membership') !== false) &&
                            preg_match('/(?:title|name):\s*([^\n]+)/i', $message_content)) {
                                
                            // This looks like a membership
                            if (method_exists($this, 'add_content_marker')) {
                                $modified_content = $this->add_content_marker($message_content, 'membership');
                                mpai_log_debug('Added membership marker to response', 'chat');
                            } else {
                                mpai_log_warning('add_content_marker method not available for membership', 'chat');
                            }
                        }
                    } catch (Throwable $pattern_e) {
                        mpai_log_error('Error checking content patterns: ' . $pattern_e->getMessage(), 'chat');
                        // Continue without adding marker
                    }
                    
                    // Add assistant response to conversation
                    try {
                        mpai_log_debug('Adding assistant response to conversation', 'chat');
                        $this->conversation[] = array('role' => 'assistant', 'content' => $modified_content);
                        mpai_log_debug('Added assistant response to conversation', 'chat');
                    } catch (Throwable $conv_e) {
                        mpai_log_error('Error adding assistant response to conversation: ' . $conv_e->getMessage(), 'chat', array(
                            'file' => $conv_e->getFile(),
                            'line' => $conv_e->getLine(),
                            'trace' => $conv_e->getTraceAsString()
                        ));
                        // Continue even if this fails
                    }
                    
                    // Save conversation to database
                    try {
                        mpai_log_debug('Saving message to database', 'chat');
                        $this->save_message($message, $modified_content);
                        mpai_log_debug('Message saved to database', 'chat');
                    } catch (Throwable $save_e) {
                        mpai_log_error('Error saving message to database: ' . $save_e->getMessage(), 'chat', array(
                            'file' => $save_e->getFile(),
                            'line' => $save_e->getLine(),
                            'trace' => $save_e->getTraceAsString()
                        ));
                        // Continue even if save fails
                    }
                    
                    if ($has_tool_calls) {
                        try {
                            mpai_log_debug('Processing tool calls from structured response', 'chat');
                            // Process tool calls from structure
                            $processed_response = $this->process_structured_tool_calls($message_content, $response['tool_calls']);
                            mpai_log_debug('Tool calls processed successfully', 'chat');
                        } catch (Throwable $tool_e) {
                            mpai_log_error('Error processing tool calls: ' . $tool_e->getMessage(), 'chat', array(
                                'file' => $tool_e->getFile(),
                                'line' => $tool_e->getLine(),
                                'trace' => $tool_e->getTraceAsString()
                            ));
                            $processed_response = $message_content;
                            // Continue with original content if processing fails
                        }
                    } else {
                        // Just process the message content
                        try {
                            mpai_log_debug('Processing content for tool calls', 'chat');
                            $processed_response = $this->process_tool_calls($message_content);
                            mpai_log_debug('Processing content for commands', 'chat');
                            $processed_response = $this->process_commands($processed_response);
                            mpai_log_debug('Content processing completed successfully', 'chat');
                        } catch (Throwable $proc_e) {
                            mpai_log_error('Error processing content: ' . $proc_e->getMessage(), 'chat', array(
                                'file' => $proc_e->getFile(),
                                'line' => $proc_e->getLine(),
                                'trace' => $proc_e->getTraceAsString()
                            ));
                            $processed_response = $message_content;
                            // Continue with original content if processing fails
                        }
                    }
                    
                    mpai_log_debug('Returning successful response', 'chat');
                    return array(
                        'success' => true,
                        'message' => $processed_response,
                        'raw_response' => $message_content,
                        'api_used' => isset($response['api']) ? $response['api'] : 'unknown',
                    );
                } else {
                    mpai_log_debug('Response is not an array with message field', 'chat');
                }
            } catch (Throwable $arr_e) {
                mpai_log_error('Error processing array response: ' . $arr_e->getMessage(), 'chat', array(
                    'file' => $arr_e->getFile(),
                    'line' => $arr_e->getLine(),
                    'trace' => $arr_e->getTraceAsString()
                ));
                // Continue to next format check
            }
            
            // Handle simple string response
            try {
                mpai_log_debug('Checking for string response', 'chat');
                if (is_string($response)) {
                    mpai_log_debug('Response is a string', 'chat');
                    
                    // Add assistant response to conversation
                    try {
                        mpai_log_debug('Adding string response to conversation', 'chat');
                        $this->conversation[] = array('role' => 'assistant', 'content' => $response);
                        mpai_log_debug('Added string response to conversation', 'chat');
                    } catch (Throwable $str_conv_e) {
                        mpai_log_error('Error adding string response to conversation: ' . $str_conv_e->getMessage(), 'chat', array(
                            'file' => $str_conv_e->getFile(),
                            'line' => $str_conv_e->getLine(),
                            'trace' => $str_conv_e->getTraceAsString()
                        ));
                        // Continue even if this fails
                    }
                    
                    // Save conversation to database
                    try {
                        mpai_log_debug('Saving string message to database', 'chat');
                        $this->save_message($message, $response);
                        mpai_log_debug('String message saved to database', 'chat');
                    } catch (Throwable $str_save_e) {
                        mpai_log_error('Error saving string message to database: ' . $str_save_e->getMessage(), 'chat', array(
                            'file' => $str_save_e->getFile(),
                            'line' => $str_save_e->getLine(),
                            'trace' => $str_save_e->getTraceAsString()
                        ));
                        // Continue even if save fails
                    }
                    
                    // Process any tool calls in the response
                    try {
                        mpai_log_debug('Processing string response for tool calls', 'chat');
                        $processed_response = $this->process_tool_calls($response);
                        mpai_log_debug('Processing string response for commands', 'chat');
                        $processed_response = $this->process_commands($processed_response);
                        mpai_log_debug('String response processing completed', 'chat');
                    } catch (Throwable $str_proc_e) {
                        mpai_log_error('Error processing string response: ' . $str_proc_e->getMessage(), 'chat', array(
                            'file' => $str_proc_e->getFile(),
                            'line' => $str_proc_e->getLine(),
                            'trace' => $str_proc_e->getTraceAsString()
                        ));
                        $processed_response = $response;
                        // Continue with original response if processing fails
                    }
                    
                    mpai_log_debug('Returning successful string response', 'chat');
                    return array(
                        'success' => true,
                        'message' => $processed_response,
                        'raw_response' => $response,
                    );
                } else {
                    mpai_log_debug('Response is not a string: ' . gettype($response), 'chat');
                }
            } catch (Throwable $str_e) {
                mpai_log_error('Error processing string response: ' . $str_e->getMessage(), 'chat', array(
                    'file' => $str_e->getFile(),
                    'line' => $str_e->getLine(),
                    'trace' => $str_e->getTraceAsString()
                ));
                // Continue to fallback
            }
            
            // Fallback for unrecognized response format
            mpai_log_debug('Unrecognized response format, using fallback', 'chat');
            return array(
                'success' => true,
                'message' => 'The assistant responded in an unexpected format. Please try rephrasing your request.',
                'raw_response' => (is_string($response) ? $response : (is_array($response) ? json_encode($response) : 'Unknown response format')),
            );
            
        } catch (Throwable $e) {
            mpai_log_error('CRITICAL ERROR in process_message: ' . $e->getMessage(), 'chat', array(
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ));
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
                $parameters = json_decode($function['arguments'], true) ?: array();
                
                // Clean up any escaped slashes in plugin paths
                if (isset($parameters['plugin'])) {
                    $parameters['plugin'] = str_replace('\\/', '/', $parameters['plugin']);
                    mpai_log_debug('Unescaped plugin path for structured tool call: ' . $parameters['plugin'], 'chat');
                }
                
                $tool_request = array(
                    'name' => $function['name'],
                    'parameters' => $parameters
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
            mpai_log_debug('Creating database tables', 'db-manager');
            
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
            mpai_log_debug('dbDelta result: ' . json_encode($result), 'db-manager');
            
            // Check if tables were created
            $tables_created = array();
            $tables_created['conversations'] = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") == $table_name;
            $tables_created['messages'] = $wpdb->get_var("SHOW TABLES LIKE '{$table_messages}'") == $table_messages;
            
            mpai_log_debug('Tables created status: ' . json_encode($tables_created), 'db-manager');
            
            return true;
        } catch (Exception $e) {
            mpai_log_error('Error creating tables: ' . $e->getMessage(), 'db-manager', array(
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ));
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
            
            // Clean up any escaped slashes in plugin paths
            if (isset($tool_call['parameters']) && isset($tool_call['parameters']['plugin'])) {
                $tool_call['parameters']['plugin'] = str_replace('\\/', '/', $tool_call['parameters']['plugin']);
                mpai_log_debug('Unescaped plugin path for tool call: ' . $tool_call['parameters']['plugin'], 'chat');
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
     * Get the latest assistant message from conversation history
     *
     * @return array|null The latest assistant message or null if none found
     */
    public function get_latest_assistant_message() {
        if (empty($this->conversation)) {
            mpai_log_debug('No conversation history available', 'chat');
            return null;
        }
        
        $messages_copy = $this->conversation;
        $messages_copy = array_reverse($messages_copy);
        
        foreach ($messages_copy as $message) {
            if (isset($message['role']) && $message['role'] === 'assistant' && 
                isset($message['content']) && !empty($message['content'])) {
                mpai_log_debug('Found latest assistant message with length ' . strlen($message['content']), 'chat');
                return $message;
            }
        }
        
        mpai_log_debug('No assistant messages found in conversation history', 'chat');
        return null;
    }
    
    /**
     * Get the previous assistant message (i.e., the second most recent)
     * This is useful when creating posts, as the most recent message is typically 
     * "Let me publish that" and the actual content is in the previous message
     *
     * @return array|null The previous assistant message or null if not found
     */
    public function get_previous_assistant_message() {
        if (empty($this->conversation)) {
            mpai_log_debug('No conversation history available', 'chat');
            return null;
        }
        
        $messages_copy = $this->conversation;
        $messages_copy = array_reverse($messages_copy);
        
        $found_assistant_messages = 0;
        
        foreach ($messages_copy as $message) {
            if (isset($message['role']) && $message['role'] === 'assistant' && 
                isset($message['content']) && !empty($message['content'])) {
                
                $found_assistant_messages++;
                
                // We want the second assistant message (the previous one)
                if ($found_assistant_messages == 2) {
                    mpai_log_debug('Found previous assistant message with length ' . strlen($message['content']), 'chat');
                    return $message;
                }
            }
        }
        
        // If only one assistant message was found, just return that
        if ($found_assistant_messages == 1) {
            mpai_log_debug('Only one assistant message found, returning it', 'chat');
            return $this->get_latest_assistant_message();
        }
        
        mpai_log_debug('No previous assistant message found in conversation history', 'chat');
        return null;
    }
    
    /**
     * Find a message with specific content type marker
     * This looks for markers like #create-blog-post-<timestamp>, #create-page-<timestamp>, etc.
     *
     * @param string $type Content type to look for (e.g., 'blog-post', 'page', 'membership')
     * @return array|null The message with the marker or null if not found
     */
    public function find_message_with_content_marker($type) {
        if (empty($this->conversation)) {
            mpai_log_debug('No conversation history available', 'chat');
            return null;
        }
        
        $messages_copy = $this->conversation;
        $messages_copy = array_reverse($messages_copy);
        
        $marker_pattern = '/<!--\s*#create-' . preg_quote($type, '/') . '-\d+\s*-->/i';
        
        foreach ($messages_copy as $message) {
            if (isset($message['role']) && $message['role'] === 'assistant' && 
                isset($message['content']) && !empty($message['content'])) {
                
                // Check if this message has the marker we're looking for
                if (preg_match($marker_pattern, $message['content'])) {
                    mpai_log_debug('Found message with ' . $type . ' marker, length: ' . strlen($message['content']), 'chat');
                    
                    // Create a copy of the message
                    $cleaned_message = $message;
                    
                    // Remove the marker from the content before returning
                    $cleaned_message['content'] = preg_replace($marker_pattern, '', $cleaned_message['content']);
                    
                    // Trim any extra whitespace that might be left
                    $cleaned_message['content'] = trim($cleaned_message['content']);
                    
                    mpai_log_debug('Cleaned marker from content, new length: ' . strlen($cleaned_message['content']), 'chat');
                    return $cleaned_message;
                }
            }
        }
        
        mpai_log_debug('No message with ' . $type . ' marker found in conversation history', 'chat');
        return null;
    }
    
    /**
     * Add content marker to a message
     * This will add a marker like #create-blog-post-<timestamp> to assistant messages
     * that contain specific types of content
     *
     * @param string $response The response message to modify
     * @param string $type The type of content ('blog-post', 'page', 'membership', etc.)
     * @return string The modified response with the content marker
     */
    private function add_content_marker($response, $type) {
        $timestamp = time();
        $marker = "<!-- #create-{$type}-{$timestamp} -->";
        
        // We'll add the marker at the very end of the content
        return $response . "\n\n" . $marker;
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
                $modified_result .= "```json\n{\"tool\": \"wp_api\", \"parameters\": {\"action\": \"activate_plugin\", \"plugin\": \"memberpress-coachkit/main.php\"}}\n```";
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
                try {
                    mpai_log_debug('Processing memberpress_info tool result', 'chat');
                    $parsed = json_decode($result['result'], true);
                    
                    if (json_last_error() == JSON_ERROR_NONE) {
                        if (isset($parsed['result'])) {
                            // Return just the actual result data
                            mpai_log_debug('Found standard result structure in memberpress_info tool', 'chat');
                            return $parsed['result'];
                        } else if (isset($parsed['command_type']) && $parsed['command_type'] == 'system_info') {
                            // Handle system_info case specifically
                            mpai_log_debug('Found system_info command type in memberpress_info tool', 'chat');
                            
                            // Format the system info into a readable text format
                            $output = "### System Information Summary\n\n";
                            
                            // WordPress core info
                            if (isset($parsed['wp-core'])) {
                                $output .= "**WordPress:**\n";
                                foreach ($parsed['wp-core'] as $key => $item) {
                                    if (isset($item['label']) && isset($item['value'])) {
                                        $output .= "- {$item['label']}: {$item['value']}\n";
                                    }
                                }
                                $output .= "\n";
                            }
                            
                            // Server info
                            if (isset($parsed['wp-server'])) {
                                $output .= "**Server:**\n";
                                foreach ($parsed['wp-server'] as $key => $item) {
                                    if (isset($item['label']) && isset($item['value'])) {
                                        $output .= "- {$item['label']}: {$item['value']}\n";
                                    }
                                }
                                $output .= "\n";
                            }
                            
                            // MemberPress info
                            if (isset($parsed['memberpress'])) {
                                $output .= "**MemberPress:**\n";
                                foreach ($parsed['memberpress'] as $key => $item) {
                                    if (isset($item['label']) && isset($item['value'])) {
                                        $output .= "- {$item['label']}: {$item['value']}\n";
                                    }
                                }
                                $output .= "\n";
                            }
                            
                            // MemberPress AI info
                            if (isset($parsed['mpai'])) {
                                $output .= "**MemberPress AI Assistant:**\n";
                                foreach ($parsed['mpai'] as $key => $item) {
                                    if (isset($item['label']) && isset($item['value'])) {
                                        $output .= "- {$item['label']}: {$item['value']}\n";
                                    }
                                }
                            }
                            
                            return $output;
                        }
                    }
                } catch (Exception $e) {
                    mpai_log_error('Error processing memberpress_info result: ' . $e->getMessage(), 'chat', array(
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString()
                    ));
                    // Fall through to default handling
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
        // CLI commands are always enabled now (settings were removed from UI)
        mpai_log_debug('CLI commands are always enabled in chat', 'command-processor');
        
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
     * This function completely resets the conversation state and history,
     * including context and any cached values.
     *
     * @return bool Success status
     */
    public function reset_conversation() {
        global $wpdb;
        
        mpai_log_debug('Starting complete conversation reset', 'chat');
        
        try {
            // Reset internal conversation array first
            $this->conversation = array();
            mpai_log_debug('Reset internal conversation array', 'chat');
            
            // Reset any cached system prompt
            if (isset($this->system_prompt)) {
                unset($this->system_prompt);
                mpai_log_debug('Cleared cached system prompt', 'chat');
            }
            
            // Get current user ID
            $user_id = get_current_user_id();
            if (empty($user_id)) {
                mpai_log_warning('No user ID available for conversation reset', 'chat');
                return false;
            }
            
            // Get conversation ID
            $conversation_id = $this->get_current_conversation_id();
            if (empty($conversation_id)) {
                mpai_log_warning('No conversation ID available for reset', 'chat');
                return false;
            }
            
            // Table names
            $table_messages = $wpdb->prefix . 'mpai_messages';
            $table_conversations = $wpdb->prefix . 'mpai_conversations';
            
            // Delete all messages for this conversation
            $deleted = $wpdb->delete(
                $table_messages,
                array('conversation_id' => $conversation_id)
            );
            mpai_log_debug('Deleted ' . ($deleted !== false ? $deleted : '0') . ' messages from database', 'chat');
            
            // Create new conversation ID
            $new_conversation_id = wp_generate_uuid4();
            
            // Update conversation with new ID
            $updated = $wpdb->update(
                $table_conversations,
                array(
                    'conversation_id' => $new_conversation_id,
                    'updated_at' => current_time('mysql'),
                ),
                array('conversation_id' => $conversation_id)
            );
            mpai_log_debug('Updated conversation with new ID: ' . $new_conversation_id . ', result: ' . ($updated !== false ? $updated : 'failed'), 'chat');
            
            // Clear any cached context in the context manager
            if (isset($this->context_manager) && is_object($this->context_manager)) {
                if (method_exists($this->context_manager, 'reset_context')) {
                    $this->context_manager->reset_context();
                    mpai_log_debug('Reset context manager', 'chat');
                } else {
                    mpai_log_warning('Context manager does not have reset_context method', 'chat');
                }
            } else {
                mpai_log_warning('Context manager not available for reset', 'chat');
            }
            
            // If we have an API router, try to reset its state too
            if (isset($this->api_router) && is_object($this->api_router)) {
                if (method_exists($this->api_router, 'reset_state')) {
                    $this->api_router->reset_state();
                    mpai_log_debug('Reset API router state', 'chat');
                } else {
                    mpai_log_warning('API router does not have reset_state method', 'chat');
                }
            } else {
                mpai_log_warning('API router not available for reset', 'chat');
            }
            
            // Initialize a new conversation with a system prompt
            // This ensures we start with a clean state for next messages
            try {
                $system_prompt = $this->get_system_prompt();
                $this->conversation = array(
                    array('role' => 'system', 'content' => $system_prompt)
                );
                mpai_log_debug('Initialized new conversation with fresh system prompt', 'chat');
            } catch (Throwable $e) {
                mpai_log_error('Error initializing new conversation: ' . $e->getMessage(), 'chat', array(
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ));
                // If reinitializing fails, at least leave with an empty conversation
                $this->conversation = array();
            }
            
            mpai_log_debug('Conversation reset completed successfully', 'chat');
            return true;
            
        } catch (Throwable $e) {
            mpai_log_error('Error in reset_conversation: ' . $e->getMessage(), 'chat', array(
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ));
            
            // Try to at least reset the internal state
            $this->conversation = array();
            
            return false;
        }
    }
    
    /**
     * Get plugin list directly from WordPress API
     * This is a fallback method for wp plugin list command
     * 
     * @return string Formatted plugin list
     */
    private function get_direct_plugin_list() {
        mpai_log_debug('Getting direct plugin list using WordPress functions', 'chat');
        
        // Ensure plugin functions are available
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        // Get plugins
        $all_plugins = get_plugins();
        $active_plugins = get_option('active_plugins');
        
        // Format output as a table
        $output = "NAME\tSTATUS\tVERSION\tDESCRIPTION\n";
        
        foreach ($all_plugins as $plugin_path => $plugin_data) {
            $plugin_status = in_array($plugin_path, $active_plugins) ? 'active' : 'inactive';
            $name = isset($plugin_data['Name']) ? $plugin_data['Name'] : 'Unknown';
            $version = isset($plugin_data['Version']) ? $plugin_data['Version'] : '';
            
            // Make sure description is a string before using strlen
            $description = '';
            if (isset($plugin_data['Description']) && is_string($plugin_data['Description'])) {
                $description = (strlen($plugin_data['Description']) > 40) ? 
                               substr($plugin_data['Description'], 0, 40) . '...' : 
                               $plugin_data['Description'];
            }
            
            $output .= "$name\t$plugin_status\t$version\t$description\n";
        }
        
        mpai_log_debug('Direct plugin list fetched, ' . count($all_plugins) . ' plugins found', 'chat');
        return $output;
    }
}
