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
            
            // Register the history retention filter
            MPAI_Hooks::register_filter(
                'MPAI_HOOK_FILTER_history_retention',
                'Filter history retention settings',
                30, // Default 30 days
                ['days' => ['type' => 'integer', 'description' => 'Number of days to retain history']],
                '1.7.0',
                'history'
            );
            
            // Apply the filter
            $retention_days = apply_filters('MPAI_HOOK_FILTER_history_retention', 30);
            
            // Get messages with retention period
            $retention_date = date('Y-m-d H:i:s', strtotime("-{$retention_days} days"));
            
            $messages = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT message, response FROM $table_messages WHERE conversation_id = %s AND created_at >= %s ORDER BY created_at ASC",
                    $conversation_id,
                    $retention_date
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
                    // Process XML content in the response if needed
                    $processed_response = $message['response'];
                    if (class_exists('MPAI_XML_Display_Handler')) {
                        $xml_handler = new MPAI_XML_Display_Handler();
                        
                        // Check if response contains XML blog post format
                        if ($xml_handler->contains_xml_blog_post($message['response'])) {
                            mpai_log_debug('XML content detected in loaded message, processing', 'chat');
                            
                            // Create a message array for the XML handler
                            $message_data = array(
                                'role' => 'assistant',
                                'content' => $message['response']
                            );
                            
                            // Process the XML content
                            $processed_response = $xml_handler->process_xml_content($message['response'], $message_data);
                            
                            mpai_log_debug('Processed XML content in loaded message', 'chat');
                        }
                    }
                    
                    $this->conversation[] = array('role' => 'assistant', 'content' => $processed_response);
                }
            }
            
            mpai_log_debug('Loaded ' . count($messages) . ' messages from conversation', 'chat');
            
            // Register the conversation history filter
            MPAI_Hooks::register_filter(
                'MPAI_HOOK_FILTER_chat_conversation_history',
                'Filter the conversation history',
                $this->conversation,
                ['conversation' => ['type' => 'array', 'description' => 'The conversation history array']],
                '1.7.0',
                'chat'
            );
            
            // Apply the filter
            $this->conversation = apply_filters('MPAI_HOOK_FILTER_chat_conversation_history', $this->conversation);
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
        $system_prompt .= "2. When the wp_api tool fails or is unavailable, fall back to the wpcli tool\n";
        $system_prompt .= "3. ALWAYS use the memberpress_info tool to get MemberPress-specific data\n";
        $system_prompt .= "   - For new member data: {\"tool\": \"memberpress_info\", \"parameters\": {\"type\": \"new_members_this_month\"}}\n";
        $system_prompt .= "   - For best-selling memberships: {\"tool\": \"memberpress_info\", \"parameters\": {\"type\": \"best_selling\"}}\n";
        $system_prompt .= "   - For active subscriptions: {\"tool\": \"memberpress_info\", \"parameters\": {\"type\": \"active_subscriptions\"}}\n";
        $system_prompt .= "   - For all subscriptions: {\"tool\": \"memberpress_info\", \"parameters\": {\"type\": \"subscriptions\"}}\n";
        $system_prompt .= "   - For WordPress and server information: {\"tool\": \"memberpress_info\", \"parameters\": {\"type\": \"system_info\"}}\n";
        $system_prompt .= "   - For complete data with system info: {\"tool\": \"memberpress_info\", \"parameters\": {\"type\": \"all\", \"include_system_info\": true}}\n";
        $system_prompt .= "   - For creating a membership: {\"tool\": \"memberpress_info\", \"parameters\": {\"type\": \"create\", \"name\": \"Gold Membership\", \"price\": 29.99, \"period_type\": \"month\"}}\n";
        $system_prompt .= "CRITICAL - MEMBERSHIP TOOL FORMAT: When creating memberships, follow this EXACT format with proper quoting and numerical values:\n";
        $system_prompt .= "```json\n";
        $system_prompt .= "{\"tool\": \"memberpress_info\", \"parameters\": {\"type\": \"create\", \"name\": \"Gold Membership\", \"price\": 29.99, \"period_type\": \"month\"}}\n";
        $system_prompt .= "```\n";
        $system_prompt .= "YOU MUST include all these parameters exactly as shown with correct types:\n";
        $system_prompt .= "- type: Must be \"create\" (string)\n";
        $system_prompt .= "- name: The membership name (string)\n";
        $system_prompt .= "- price: The membership price (number, not a string)\n";
        $system_prompt .= "- period_type: The billing period (string: \"month\", \"year\", \"lifetime\", etc.)\n";
        $system_prompt .= "   - The system_info type uses WordPress Site Health API for comprehensive diagnostics\n";
        $system_prompt .= "4. DO NOT simply suggest commands - actually execute them using the tool format above\n\n";
        
        $system_prompt .= "CRITICAL TOOL SELECTION RULES:\n";
        $system_prompt .= "1. For creating/editing WordPress content (posts, pages, users), ALWAYS use the wp_api tool first:\n";
        $system_prompt .= "   - Create post: {\"tool\": \"wp_api\", \"parameters\": {\"action\": \"create_post\", \"title\": \"Title\", \"content\": \"Content\", \"status\": \"draft\"}}\n";
        $system_prompt .= "   - Create page: {\"tool\": \"wp_api\", \"parameters\": {\"action\": \"create_page\", \"title\": \"Title\", \"content\": \"Content\", \"status\": \"draft\"}}\n";
        $system_prompt .= "2. For managing WordPress plugins and themes, ALWAYS use the wp_api tool:\n";
        $system_prompt .= "   - List plugins: {\"tool\": \"wp_api\", \"parameters\": {\"action\": \"get_plugins\"}}\n";
        $system_prompt .= "   - Activate plugin: {\"tool\": \"wp_api\", \"parameters\": {\"action\": \"activate_plugin\", \"plugin\": \"plugin-directory/main-file.php\"}}\n";
        $system_prompt .= "   - Example for MemberPress CoachKit: {\"tool\": \"wp_api\", \"parameters\": {\"action\": \"activate_plugin\", \"plugin\": \"memberpress-coachkit/main.php\"}}\n";
        $system_prompt .= "   - Deactivate plugin: {\"tool\": \"wp_api\", \"parameters\": {\"action\": \"deactivate_plugin\", \"plugin\": \"plugin-directory/main-file.php\"}}\n";
        $system_prompt .= "   - List themes: {\"tool\": \"wp_api\", \"parameters\": {\"action\": \"get_themes\"}}\n";
        $system_prompt .= "   - Activate theme: {\"tool\": \"wp_api\", \"parameters\": {\"action\": \"activate_theme\", \"theme\": \"twentytwentythree\"}}\n";
        $system_prompt .= "3. ██████ MOST IMPORTANT RULE - PLUGIN AND THEME TOOLS ██████\n";
        $system_prompt .= "   For plugin and theme related questions, use these specific tools:\n";
        $system_prompt .= "   a) For current plugin status, use wp_api with get_plugins action.\n";
        $system_prompt .= "   b) For plugin management, use wp_api with activate_plugin or deactivate_plugin actions.\n";
        $system_prompt .= "   c) For current theme status, use wp_api with get_themes action.\n";
        $system_prompt .= "   d) For theme management, use wp_api with activate_theme action.\n\n";
        $system_prompt .= "   IMPORTANT: When using tools, ALWAYS wait for the tool's response before providing your own analysis.\n";
        $system_prompt .= "   DO NOT try to predict what the tool will return - wait for the actual data.\n";
        $system_prompt .= "   NOTE: Questions about plugin history are handled directly by the system without using tools.\n";
        $system_prompt .= "   b) FOR QUESTIONS ABOUT CURRENT USERS, ALWAYS USE THE WP_API TOOL WITH GET_USERS ACTION:\n";
        $system_prompt .= "      For example: \"List all users\", \"Show me WordPress users\", \"Who are the site users?\"\n";
        $system_prompt .= "      ```json\n";
        $system_prompt .= "      {\"tool\": \"wp_api\", \"parameters\": {\"action\": \"get_users\", \"limit\": 10}}\n";
        $system_prompt .= "      ```\n\n";
        $system_prompt .= "   Examples of plugin management:\n";
        $system_prompt .= "   - List plugins: {\"tool\": \"wp_api\", \"parameters\": {\"action\": \"get_plugins\"}}\n";
        $system_prompt .= "   - Activate plugin: {\"tool\": \"wp_api\", \"parameters\": {\"action\": \"activate_plugin\", \"plugin\": \"plugin-directory/main-file.php\"}}\n";
        $system_prompt .= "   - Deactivate plugin: {\"tool\": \"wp_api\", \"parameters\": {\"action\": \"deactivate_plugin\", \"plugin\": \"plugin-directory/main-file.php\"}}\n";
        $system_prompt .= "4. Only fall back to wpcli commands if a specific wp_api function isn't available\n\n";
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
        $system_prompt .= "IMPORTANT: Questions about plugin history, recently installed plugins, or recently activated plugins are handled directly by the system without using tools. ";
        $system_prompt .= "You don't need to use any tools for these queries - the system will automatically show the plugin history when these questions are asked.\n\n";
        $system_prompt .= "IMPORTANT ABOUT MEMBERSHIP CREATION: When asked to create a MemberPress membership level, ALWAYS use the memberpress_info tool with type=create. ";
        $system_prompt .= "DO NOT respond that you can't create memberships - you have this capability through the memberpress_info tool.\n\n";
        
        $system_prompt .= "CRITICAL - MEMBERSHIP TOOL FORMAT: When creating memberships, follow this EXACT format with proper quoting and no whitespace in parameter names:\n";
        $system_prompt .= "```json\n";
        $system_prompt .= "{\"tool\": \"memberpress_info\", \"parameters\": {\"type\": \"create\", \"name\": \"Gold Membership\", \"price\": 29.99, \"period_type\": \"month\"}}\n";
        $system_prompt .= "```\n";
        $system_prompt .= "YOU MUST include all these parameters exactly as shown with precise types:\n";
        $system_prompt .= "- type: Must be \"create\" (string in quotes)\n";
        $system_prompt .= "- name: The membership name (string in quotes)\n";
        $system_prompt .= "- price: The membership price (number WITHOUT quotes, never a string)\n";
        $system_prompt .= "- period_type: The billing period (string in quotes: \"month\", \"year\", \"lifetime\", etc.)\n\n";
        $system_prompt .= "IMPORTANT FORMATTING RULES:\n";
        $system_prompt .= "1. The price parameter must be a numeric value without quotes (29.99, not \"29.99\")\n";
        $system_prompt .= "2. All string parameters must be in double quotes (\"name\", not name)\n";
        $system_prompt .= "3. The JSON must be properly formatted with no trailing commas\n";
        $system_prompt .= "4. Parameter names must match exactly as shown (\"period_type\", not \"periodType\" or \"period-type\")\n";
        $system_prompt .= "5. Do not add extra whitespace or formatting in the JSON\n\n";
        
        $system_prompt .= "Example conversation for creating a membership:\n";
        $system_prompt .= "User: Create a new membership level called 'Premium' priced at $25 per month\n";
        $system_prompt .= "Assistant: I'll create a new monthly membership level for you.\n\n";
        $system_prompt .= "{\"tool\": \"memberpress_info\", \"parameters\": {\"type\": \"create\", \"name\": \"Premium\", \"price\": 25, \"period_type\": \"month\", \"period\": 1}}\n\n";
        $system_prompt .= "I've created a new membership level called 'Premium' priced at $25 per month. You can view and further customize this membership in MemberPress > Memberships.\n\n";
        $system_prompt .= "Only use wpcli commands for operations not supported by wp_api. ";
        $system_prompt .= "Keep your responses concise and focused on MemberPress functionality.";
        
        // Register the system prompt filter
        MPAI_Hooks::register_filter(
            'MPAI_HOOK_FILTER_system_prompt',
            'Filter to modify the system prompt',
            $system_prompt,
            ['system_prompt' => ['type' => 'string', 'description' => 'The system prompt to filter']],
            '1.7.0',
            'chat'
        );
        
        // Apply the filter
        $system_prompt = apply_filters('MPAI_HOOK_FILTER_system_prompt', $system_prompt);
        
        return $system_prompt;
    }

    /**
     * Process a user message
     *
     * @param string $message User message
     * @return array Response data
     */
    /**
     * Central registry of query types and their keywords
     * This allows for easier management and extension of direct handlers
     *
     * @return array Query types with their keywords and handler methods
     */
    private function get_query_registry() {
        return [
            'plugin_history' => [
                'keywords' => [
                    'plugin history', 'plugin activation history', 'plugin logs', 'plugin activity',
                    'activated plugins', 'deactivated plugins', 'plugin activation', 'plugin deactivation',
                    'what plugins were activated', 'what plugins were deactivated', 'show plugin history'
                ],
                'handler' => 'get_direct_plugin_history',
                'description' => 'Shows plugin activation and deactivation history'
            ],
            'best_selling_memberships' => [
                'keywords' => [
                    'best-selling membership', 'best selling membership', 'top selling membership',
                    'popular membership', 'best performing membership', 'top membership',
                    'most popular membership', 'most sold membership'
                ],
                'handler' => 'get_direct_best_selling_memberships',
                'description' => 'Shows best-selling memberships'
            ],
            'active_subscriptions' => [
                'keywords' => [
                    'active subscription', 'current subscription', 'active member',
                    'current member', 'active membership', 'active membership list'
                ],
                'handler' => 'get_direct_active_subscriptions',
                'description' => 'Shows active subscriptions'
            ],
            'user_list' => [
                'keywords' => [
                    'list users', 'get users', 'show users', 'all users',
                    'site users', 'wordpress users', 'wp users', 'user list'
                ],
                'handler' => 'get_direct_user_list',
                'description' => 'Shows WordPress users'
            ],
            'post_list' => [
                'keywords' => [
                    'list posts', 'show posts', 'get posts', 'all posts',
                    'post list', 'wp post list', 'blog posts'
                ],
                'handler' => 'get_direct_post_list',
                'description' => 'Shows WordPress posts'
            ],
            'plugin_list' => [
                'keywords' => [
                    'wp plugin list', 'list plugins', 'show plugins', 'plugins',
                    'installed plugins', 'all plugins', 'plugin list'
                ],
                'handler' => 'get_direct_plugin_list_formatted',
                'description' => 'Shows WordPress plugins'
            ],
            'theme_list' => [
                'keywords' => [
                    'list themes', 'show themes', 'get themes', 'all themes',
                    'installed themes', 'theme list', 'wp theme list', 'themes'
                ],
                'handler' => 'get_direct_theme_list',
                'description' => 'Shows WordPress themes'
            ],
            'activate_theme' => [
                'keywords' => [
                    'activate theme', 'switch theme', 'change theme', 'use theme',
                    'set theme', 'switch to theme', 'change to theme', 'activate the theme',
                    'switch to the theme', 'change to the theme', 'use the theme'
                ],
                'handler' => 'activate_theme_by_name',
                'description' => 'Activates a WordPress theme'
            ],
            'activate_plugin' => [
                'keywords' => [
                    'activate plugin', 'enable plugin', 'turn on plugin', 'start plugin',
                    'activate the plugin', 'enable the plugin', 'turn on the plugin'
                ],
                'handler' => 'activate_plugin_by_name',
                'description' => 'Activates a WordPress plugin'
            ],
            'deactivate_plugin' => [
                'keywords' => [
                    'deactivate plugin', 'disable plugin', 'turn off plugin', 'stop plugin',
                    'deactivate the plugin', 'disable the plugin', 'turn off the plugin'
                ],
                'handler' => 'deactivate_plugin_by_name',
                'description' => 'Deactivates a WordPress plugin'
            ]
        ];
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
            
            // DIRECT HANDLERS FOR COMMON QUERIES
            // These handlers bypass the AI completely for specific query types
            
            // Get the query registry
            $query_registry = $this->get_query_registry();
            
            // Check each query type
            foreach ($query_registry as $query_type => $query_info) {
                if ($this->message_matches_keywords($message, $query_info['keywords'])) {
                    $handler_method = $query_info['handler'];
                    mpai_log_debug('DIRECT HANDLER: Detected ' . $query_type . ' request', 'chat');
                    
                    // Call the handler method
                    if (method_exists($this, $handler_method)) {
                        return $this->$handler_method();
                    } else {
                        mpai_log_error('Handler method ' . $handler_method . ' does not exist', 'chat');
                    }
                }
            }
            
            // Register and fire the before process message hook
            MPAI_Hooks::register_hook(
                'MPAI_HOOK_ACTION_before_process_message',
                'Fires before processing a user message',
                ['message' => ['type' => 'string', 'description' => 'The user message being processed']],
                '1.7.0',
                'chat'
            );
            do_action('MPAI_HOOK_ACTION_before_process_message', $message);
            
            // Register the message content filter
            MPAI_Hooks::register_filter(
                'MPAI_HOOK_FILTER_message_content',
                'Filter message content before sending to AI',
                $message,
                ['message' => ['type' => 'string', 'description' => 'The message content to filter']],
                '1.7.0',
                'chat'
            );
            
            // Apply the filter to the message
            $message = apply_filters('MPAI_HOOK_FILTER_message_content', $message);
            
            // Check for plugin-related queries and add a guidance message
            $plugin_keywords = ['recently installed', 'recently activated', 'plugin history', 'plugin log', 'plugin activity', 'when was plugin', 'installed recently', 'activated recently', 'what plugins', 'which plugins'];
            $inject_plugin_guidance = false;

            // Check for user-related queries and add a guidance message
            $user_keywords = ['list users', 'get users', 'show users', 'all users', 'site users', 'wordpress users', 'wp users', 'user list'];
            $inject_user_guidance = false;
            
            // Check for post-related queries and add a guidance message
            $post_keywords = ['list posts', 'show posts', 'get posts', 'all posts', 'post list', 'wp post list', 'blog posts'];
            $inject_post_guidance = false;
            
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
                        strpos($plugin_list_output, '{"success":true,"tool":"wpcli","command_type":"plugin_list","result":') === 0) {
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
            
            // Active subscriptions queries are now handled directly by the query registry
            
            foreach ($plugin_keywords as $keyword) {
                if (stripos($message, $keyword) !== false) {
                    $inject_plugin_guidance = true;
                    mpai_log_debug('Detected plugin-related query, will inject guidance', 'chat');
                    break;
                }
            }

            foreach ($user_keywords as $keyword) {
                if (stripos($message, $keyword) !== false) {
                    $inject_user_guidance = true;
                    mpai_log_debug('Detected user-related query, will inject guidance', 'chat');
                    break;
                }
            }
            
            foreach ($post_keywords as $keyword) {
                if (stripos($message, $keyword) !== false) {
                    $inject_post_guidance = true;
                    mpai_log_debug('Detected post-related query, will inject guidance', 'chat');
                    break;
                }
            }
            
            // Best-selling membership queries are now handled directly by the query registry
            
            // Active subscriptions queries are now handled directly by the query registry
            
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
                
                // Best-selling membership queries are now handled directly by the query registry
                
                // Active subscriptions queries are now handled directly by the query registry

                // If this is a user-related query, add a system message reminder with direct DB solution
                if ($inject_user_guidance) {
                    mpai_log_debug('Adding direct DB user list guidance message to conversation', 'chat');
                    
                    // Execute a direct DB query for users
                    global $wpdb;
                    $results = $wpdb->get_results("SELECT ID, user_login, user_email, user_registered FROM {$wpdb->users} ORDER BY ID ASC LIMIT 10");
                    
                    $user_list = "";
                    if (!empty($results)) {
                        $user_list = "Here are the WordPress users:\n\n";
                        
                        // Create HTML table
                        $user_list .= "<table border=\"1\" cellpadding=\"5\" cellspacing=\"0\" style=\"border-collapse: collapse;\">\n";
                        $user_list .= "  <thead>\n";
                        $user_list .= "    <tr style=\"background-color: #f2f2f2;\">\n";
                        $user_list .= "      <th>ID</th>\n";
                        $user_list .= "      <th>Username</th>\n";
                        $user_list .= "      <th>Email</th>\n";
                        $user_list .= "      <th>Registration Date</th>\n";
                        $user_list .= "      <th>Links</th>\n";
                        $user_list .= "    </tr>\n";
                        $user_list .= "  </thead>\n";
                        $user_list .= "  <tbody>\n";
                        
                        foreach ($results as $user) {
                            $edit_url = admin_url('user-edit.php?user_id=' . $user->ID);
                            $author_url = get_author_posts_url($user->ID);
                            $reg_date = date('Y-m-d', strtotime($user->user_registered));
                            
                            // Format with HTML table rows
                            $user_list .= "    <tr>\n";
                            $user_list .= "      <td>{$user->ID}</td>\n";
                            $user_list .= "      <td>{$user->user_login}</td>\n";
                            $user_list .= "      <td>{$user->user_email}</td>\n";
                            $user_list .= "      <td>{$reg_date}</td>\n";
                            $user_list .= "      <td><a href=\"{$edit_url}\" target=\"_blank\">Edit</a> &bull; <a href=\"{$author_url}\" target=\"_blank\">Posts</a></td>\n";
                            $user_list .= "    </tr>\n";
                        }
                        
                        $user_list .= "  </tbody>\n";
                        $user_list .= "</table>";
                    } else {
                        $user_list = "No users found.";
                    }
                    
                    $user_guidance = "CRITICAL INSTRUCTION: This query is about listing or viewing WordPress users. I'm providing you with the DIRECT DATABASE RESULTS below. RESPOND WITH THIS EXACT DATA, do not try to use any tools:\n\n";
                    $user_guidance .= $user_list;
                    $user_guidance .= "\n\nReturn these exact results to the user. DO NOT use any tools.";
                    
                    $this->conversation[] = array('role' => 'system', 'content' => $user_guidance);
                    mpai_log_debug('Enhanced direct DB user list guidance message added', 'chat');
                }

                // If this is a post-related query, add a system message reminder with direct DB solution
                if ($inject_post_guidance) {
                    mpai_log_debug('Adding direct DB post list guidance message to conversation', 'chat');
                    
                    // Execute a direct DB query for posts
                    global $wpdb;
                    $results = $wpdb->get_results("SELECT ID, post_title, post_date, post_status FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish' ORDER BY post_date DESC LIMIT 10");
                    
                    $post_list = "";
                    if (!empty($results)) {
                        $post_list = "Here are the latest posts:\n\n";
                        
                        // Create HTML table
                        $post_list .= "<table border=\"1\" cellpadding=\"5\" cellspacing=\"0\" style=\"border-collapse: collapse;\">\n";
                        $post_list .= "  <thead>\n";
                        $post_list .= "    <tr style=\"background-color: #f2f2f2;\">\n";
                        $post_list .= "      <th>ID</th>\n";
                        $post_list .= "      <th>Title</th>\n";
                        $post_list .= "      <th>Status</th>\n";
                        $post_list .= "      <th>Date</th>\n";
                        $post_list .= "      <th>Links</th>\n";
                        $post_list .= "    </tr>\n";
                        $post_list .= "  </thead>\n";
                        $post_list .= "  <tbody>\n";
                        
                        foreach ($results as $post) {
                            $post_url = get_permalink($post->ID);
                            $edit_url = get_edit_post_link($post->ID, 'raw');
                            $date = date('Y-m-d', strtotime($post->post_date));
                            
                            // Format with HTML table rows
                            $post_list .= "    <tr>\n";
                            $post_list .= "      <td>{$post->ID}</td>\n";
                            $post_list .= "      <td><a href=\"{$post_url}\" target=\"_blank\">{$post->post_title}</a></td>\n";
                            $post_list .= "      <td>{$post->post_status}</td>\n";
                            $post_list .= "      <td>{$date}</td>\n";
                            $post_list .= "      <td><a href=\"{$edit_url}\" target=\"_blank\">Edit</a></td>\n";
                            $post_list .= "    </tr>\n";
                        }
                        
                        $post_list .= "  </tbody>\n";
                        $post_list .= "</table>";
                    } else {
                        $post_list = "No posts found.";
                    }
                    
                    $post_guidance = "CRITICAL INSTRUCTION: This query is about listing or viewing WordPress posts. I'm providing you with the DIRECT DATABASE RESULTS below. RESPOND WITH THIS EXACT DATA, do not try to use any tools:\n\n";
                    $post_guidance .= $post_list;
                    $post_guidance .= "\n\nReturn these exact results to the user. DO NOT use any tools.";
                    
                    $this->conversation[] = array('role' => 'system', 'content' => $post_guidance);
                    mpai_log_debug('Enhanced direct DB post list guidance message added', 'chat');
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
            
            // Create a user context array that can be filtered
            $user_context = array(
                'user_id' => get_current_user_id(),
                'conversation_id' => $this->get_current_conversation_id(),
                'timestamp' => current_time('timestamp'),
                'is_admin' => current_user_can('manage_options')
            );
            
            // Register the user context filter
            MPAI_Hooks::register_filter(
                'MPAI_HOOK_FILTER_user_context',
                'Filter user context data sent with messages',
                $user_context,
                ['user_context' => ['type' => 'array', 'description' => 'The user context data']],
                '1.7.0',
                'chat'
            );
            
            // Apply the filter
            $user_context = apply_filters('MPAI_HOOK_FILTER_user_context', $user_context);
            
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
                        
                        // Process XML content in the response before adding to conversation
                        $processed_content = $modified_content;
                        if (class_exists('MPAI_XML_Display_Handler')) {
                            $xml_handler = new MPAI_XML_Display_Handler();
                            
                            // Check if response contains XML blog post format
                            if ($xml_handler->contains_xml_blog_post($modified_content)) {
                                mpai_log_debug('XML content detected in response, processing before adding to conversation', 'chat');
                                
                                // Create a message array for the XML handler
                                $message_data = array(
                                    'role' => 'assistant',
                                    'content' => $modified_content
                                );
                                
                                // Process the XML content
                                $processed_content = $xml_handler->process_xml_content($modified_content, $message_data);
                                
                                mpai_log_debug('Processed XML content before adding to conversation', 'chat');
                            }
                        }
                        
                        $this->conversation[] = array('role' => 'assistant', 'content' => $processed_content);
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
                    
                    // Register the response content filter
                    MPAI_Hooks::register_filter(
                        'MPAI_HOOK_FILTER_response_content',
                        'Filter AI response before returning to user',
                        $processed_response,
                        [
                            'response' => ['type' => 'string', 'description' => 'The AI response content'],
                            'message' => ['type' => 'string', 'description' => 'The original user message']
                        ],
                        '1.7.0',
                        'chat'
                    );
                    
                    // Apply the filter
                    $processed_response = apply_filters('MPAI_HOOK_FILTER_response_content', $processed_response, $message);
                    
                    mpai_log_debug('Returning successful response', 'chat');
                    
                    $result = array(
                        'success' => true,
                        'message' => $processed_response,
                        'raw_response' => $message_content,
                        'api_used' => isset($response['api']) ? $response['api'] : 'unknown',
                    );
                    
                    // Register and fire the after process message hook
                    MPAI_Hooks::register_hook(
                        'MPAI_HOOK_ACTION_after_process_message',
                        'Fires after message is processed',
                        [
                            'message' => ['type' => 'string', 'description' => 'The user message that was processed'],
                            'response' => ['type' => 'array', 'description' => 'The AI response']
                        ],
                        '1.7.0',
                        'chat'
                    );
                    do_action('MPAI_HOOK_ACTION_after_process_message', $message, $result);
                    
                    return $result;
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
                        
                        // Process XML content in the response before adding to conversation
                        $processed_content = $response;
                        if (class_exists('MPAI_XML_Display_Handler')) {
                            $xml_handler = new MPAI_XML_Display_Handler();
                            
                            // Check if response contains XML blog post format
                            if ($xml_handler->contains_xml_blog_post($response)) {
                                mpai_log_debug('XML content detected in string response, processing before adding to conversation', 'chat');
                                
                                // Create a message array for the XML handler
                                $message_data = array(
                                    'role' => 'assistant',
                                    'content' => $response
                                );
                                
                                // Process the XML content
                                $processed_content = $xml_handler->process_xml_content($response, $message_data);
                                
                                mpai_log_debug('Processed XML content in string response before adding to conversation', 'chat');
                            }
                        }
                        
                        $this->conversation[] = array('role' => 'assistant', 'content' => $processed_content);
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
                        $this->save_message($message, $processed_content);
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
                    
                    // Register the response content filter
                    MPAI_Hooks::register_filter(
                        'MPAI_HOOK_FILTER_response_content',
                        'Filter AI response before returning to user',
                        $processed_response,
                        [
                            'response' => ['type' => 'string', 'description' => 'The AI response content'],
                            'message' => ['type' => 'string', 'description' => 'The original user message']
                        ],
                        '1.7.0',
                        'chat'
                    );
                    
                    // Apply the filter
                    $processed_response = apply_filters('MPAI_HOOK_FILTER_response_content', $processed_response, $message);
                    
                    mpai_log_debug('Returning successful string response', 'chat');
                    
                    $result = array(
                        'success' => true,
                        'message' => $processed_response,
                        'raw_response' => $response,
                    );
                    
                    // Register and fire the after process message hook
                    MPAI_Hooks::register_hook(
                        'MPAI_HOOK_ACTION_after_process_message',
                        'Fires after message is processed',
                        [
                            'message' => ['type' => 'string', 'description' => 'The user message that was processed'],
                            'response' => ['type' => 'array', 'description' => 'The AI response']
                        ],
                        '1.7.0',
                        'chat'
                    );
                    do_action('MPAI_HOOK_ACTION_after_process_message', $message, $result);
                    
                    return $result;
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
                
                // Clean up any escaped slashes in theme names
                if (isset($parameters['theme'])) {
                    $parameters['theme'] = str_replace('\\/', '/', $parameters['theme']);
                    mpai_log_debug('Unescaped theme name for structured tool call: ' . $parameters['theme'], 'chat');
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
        
        // Register and fire the before save history hook
        MPAI_Hooks::register_hook(
            'MPAI_HOOK_ACTION_before_save_history',
            'Action before saving chat history',
            [
                'message' => ['type' => 'string', 'description' => 'The user message'],
                'response' => ['type' => 'string', 'description' => 'The AI response']
            ],
            '1.7.0',
            'history'
        );
        do_action('MPAI_HOOK_ACTION_before_save_history', $message, $response);
        
        // Process XML content in the response before saving to database
        $processed_response = $response;
        if (class_exists('MPAI_XML_Display_Handler')) {
            $xml_handler = new MPAI_XML_Display_Handler();
            
            // Check if response contains XML blog post format
            if ($xml_handler->contains_xml_blog_post($response)) {
                mpai_log_debug('XML content detected in response, processing before saving to database', 'chat');
                
                // Create a message array for the XML handler
                $message_data = array(
                    'role' => 'assistant',
                    'content' => $response
                );
                
                // Process the XML content
                $processed_response = $xml_handler->process_xml_content($response, $message_data);
                
                mpai_log_debug('Processed XML content before saving to database', 'chat');
            }
        }
        
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
                'response' => $processed_response,
            )
        );
        
        // Update conversation timestamp
        $table_conversations = $wpdb->prefix . 'mpai_conversations';
        
        $wpdb->update(
            $table_conversations,
            array('updated_at' => current_time('mysql')),
            array('conversation_id' => $conversation_id)
        );
        
        // Register and fire the after save history hook
        MPAI_Hooks::register_hook(
            'MPAI_HOOK_ACTION_after_save_history',
            'Action after saving chat history',
            [
                'message' => ['type' => 'string', 'description' => 'The user message'],
                'response' => ['type' => 'string', 'description' => 'The AI response']
            ],
            '1.7.0',
            'history'
        );
        do_action('MPAI_HOOK_ACTION_after_save_history', $message, $response);
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
            
            if (json_last_error() != JSON_ERROR_NONE) {
                mpai_log_error('Failed to parse JSON tool call: ' . json_last_error_msg() . ' for: ' . $match, 'chat');
                continue;
            }
            
            // Log the entire tool call structure for debugging
            mpai_log_debug('Detected tool call JSON: ' . json_encode($tool_call), 'chat');
            
            // Both 'tool' and 'tool_name' are supported formats
            $tool_name = null;
            if (isset($tool_call['tool'])) {
                $tool_name = $tool_call['tool'];
                mpai_log_debug('Tool name extracted from "tool" field: ' . $tool_name, 'chat');
            } else if (isset($tool_call['name'])) {
                $tool_name = $tool_call['name'];
                mpai_log_debug('Tool name extracted from "name" field: ' . $tool_name, 'chat');
            } else {
                mpai_log_warning('No tool name found in JSON', 'chat');
                continue; // Skip if no tool name found
            }
            
            // Extract parameters, with support for different parameter formats
            $parameters = array();
            if (isset($tool_call['parameters'])) {
                $parameters = $tool_call['parameters'];
                mpai_log_debug('Parameters extracted directly from "parameters" field', 'chat');
            } else if (isset($tool_call['args'])) {
                $parameters = $tool_call['args'];
                mpai_log_debug('Parameters extracted from "args" field', 'chat');
            }
            
            // Log extracted parameters
            mpai_log_debug('Extracted parameters: ' . json_encode($parameters), 'chat');
            
            // Clean up any escaped slashes in plugin paths
            if (isset($parameters['plugin'])) {
                $parameters['plugin'] = str_replace('\\/', '/', $parameters['plugin']);
                mpai_log_debug('Unescaped plugin path for tool call: ' . $parameters['plugin'], 'chat');
            }
            
            // Clean up any escaped slashes in theme names
            if (isset($parameters['theme'])) {
                $parameters['theme'] = str_replace('\\/', '/', $parameters['theme']);
                mpai_log_debug('Unescaped theme name for tool call: ' . $parameters['theme'], 'chat');
            }
            
            // Special handling for membership creation
            if ($tool_name === 'memberpress_info' && isset($parameters['type']) && $parameters['type'] === 'create') {
                mpai_log_debug('Membership creation detected - EXTRA VALIDATION', 'chat');
                
                // Validate required parameters
                if (!isset($parameters['name']) || empty($parameters['name']) || $parameters['name'] === 'New Membership') {
                    mpai_log_error('CRITICAL ERROR: Missing or default name for membership creation', 'chat');
                    // Do not continue - return error instead
                    $error_message = "Error: Membership creation requires a specific name. The default 'New Membership' is not allowed.";
                    $tool_call_block = "```json\n{$match}\n```";
                    $error_block = "```\nERROR: {$error_message}\n```";
                    $processed_response = str_replace($tool_call_block, $error_block, $processed_response);
                    continue; // Skip executing this tool
                }
                
                // Validate price
                if (!isset($parameters['price']) || floatval($parameters['price']) <= 0) {
                    mpai_log_error('CRITICAL ERROR: Missing or invalid price for membership creation', 'chat');
                    // Do not continue - return error instead
                    $error_message = "Error: Membership creation requires a valid price greater than zero.";
                    $tool_call_block = "```json\n{$match}\n```";
                    $error_block = "```\nERROR: {$error_message}\n```";
                    $processed_response = str_replace($tool_call_block, $error_block, $processed_response);
                    continue; // Skip executing this tool
                }
                
                // Log the parameters for debugging
                mpai_log_debug('Membership creation parameters validated', 'chat');
                mpai_log_debug('Name: ' . $parameters['name'], 'chat');
                mpai_log_debug('Price: ' . $parameters['price'] . ' (type: ' . gettype($parameters['price']) . ')', 'chat');
                
                // Ensure price is always a number
                if (is_string($parameters['price'])) {
                    $parameters['price'] = floatval($parameters['price']);
                    mpai_log_debug('Converted price from string to number: ' . $parameters['price'], 'chat');
                }
            }
            
            $tool_request = array(
                'name' => $tool_name,
                'parameters' => $parameters
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
        
        // Check if this is a plugin_logs tool result with formatted output
        if (isset($result['tool']) && $result['tool'] == 'plugin_logs' && isset($result['formatted_output'])) {
            mpai_log_debug('Using formatted output for plugin_logs tool result', 'chat');
            return $result['formatted_output'];
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
                
                // Handle theme activation
                if ($result['action'] == 'activate_theme' &&
                    isset($result['result']) && is_array($result['result']) &&
                    isset($result['result']['success']) && $result['result']['success'] == true) {
                    
                    $theme_name = $result['result']['theme_name'] ?? $result['result']['theme'] ?? 'the theme';
                    $status = $result['result']['status'] ?? 'active';
                    
                    $user_friendly_result = "Theme operation successful!\n\n";
                    $user_friendly_result .= "- Action: Activated\n";
                    $user_friendly_result .= "- Theme: {$theme_name}\n";
                    $user_friendly_result .= "- Status: {$status}\n";
                    
                    return $user_friendly_result;
                }
                
                // Handle theme listing
                if ($result['action'] == 'get_themes' &&
                    isset($result['result']) && is_array($result['result']) &&
                    isset($result['result']['themes']) && is_array($result['result']['themes'])) {
                    
                    $themes = $result['result']['themes'];
                    $user_friendly_result = "Installed Themes (" . count($themes) . "):\n\n";
                    
                    foreach ($themes as $theme) {
                        $status = isset($theme['is_active']) && $theme['is_active'] ? '✅ Active' : '❌ Inactive';
                        $user_friendly_result .= "- {$theme['name']} ({$theme['version']}): {$status}\n";
                    }
                    
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
        
        // Register the allowed commands filter
        MPAI_Hooks::register_filter(
            'MPAI_HOOK_FILTER_allowed_commands',
            'Filter allowed commands in chat',
            $allowed_commands,
            ['allowed_commands' => ['type' => 'array', 'description' => 'Array of allowed command names']],
            '1.7.0',
            'chat'
        );
        
        // Apply the filter
        $allowed_commands = apply_filters('MPAI_HOOK_FILTER_allowed_commands', $allowed_commands);
        
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
        
        // Register and fire the before clear history hook
        MPAI_Hooks::register_hook(
            'MPAI_HOOK_ACTION_before_clear_history',
            'Action before clearing chat history',
            [],
            '1.7.0',
            'history'
        );
        do_action('MPAI_HOOK_ACTION_before_clear_history');
        
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
            
            // Register and fire the after clear history hook
            MPAI_Hooks::register_hook(
                'MPAI_HOOK_ACTION_after_clear_history',
                'Action after clearing chat history',
                [],
                '1.7.0',
                'history'
            );
            do_action('MPAI_HOOK_ACTION_after_clear_history');
            
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
    
    /**
     * Get direct plugin history without using AI
     *
     * This method bypasses the AI and directly fetches and formats plugin history
     *
     * @return array Response data with formatted plugin history
     */
    /**
     * Get plugin activation and deactivation history directly
     *
     * This method bypasses the AI and directly fetches and formats plugin history
     *
     * @return array Response data with formatted plugin history
     */
    private function get_direct_plugin_history() {
        mpai_log_debug('Getting direct plugin history', 'chat');
        
        try {
            // Ensure context manager is initialized
            if (!isset($this->context_manager) || !is_object($this->context_manager)) {
                mpai_log_debug('Creating Context Manager instance for plugin logs', 'chat');
                $this->context_manager = new MPAI_Context_Manager();
            }
            
            // Set up parameters for plugin logs query
            $parameters = [
                'days' => 30,
                'limit' => 100,
                'summary_only' => false
            ];
            
            // Call the execute_plugin_logs method directly
            mpai_log_debug('Calling execute_plugin_logs directly', 'chat');
            $result = $this->context_manager->execute_plugin_logs($parameters);
            
            if (!$result || !is_array($result) || !isset($result['logs'])) {
                mpai_log_error('Failed to get plugin logs from context manager', 'chat');
                return [
                    'success' => false,
                    'message' => 'Error: Failed to retrieve plugin logs.'
                ];
            }
            
            $logs = $result['logs'];
            $summary = isset($result['summary']) ? $result['summary'] : [];
            $time_period = isset($result['time_period']) ? $result['time_period'] : 'past 30 days';
            
            // Format the output as HTML table
            $output = "<h2>Plugin Activity Log</h2>";
            $output .= "<p>Showing plugin activity for the {$time_period}</p>";
            
            // Add summary section
            $output .= "<h3>Summary</h3>";
            $output .= "<ul>";
            $output .= "<li>Total activities: " . (isset($summary['total']) ? $summary['total'] : '0') . "</li>";
            $output .= "<li>Installations: " . (isset($summary['installed']) ? $summary['installed'] : '0') . "</li>";
            $output .= "<li>Updates: " . (isset($summary['updated']) ? $summary['updated'] : '0') . "</li>";
            $output .= "<li>Activations: " . (isset($summary['activated']) ? $summary['activated'] : '0') . "</li>";
            $output .= "<li>Deactivations: " . (isset($summary['deactivated']) ? $summary['deactivated'] : '0') . "</li>";
            $output .= "<li>Deletions: " . (isset($summary['deleted']) ? $summary['deleted'] : '0') . "</li>";
            $output .= "</ul>";
            
            // Add detailed logs section as a table
            $output .= "<h3>Recent Activity</h3>";
            
            if (count($logs) > 0) {
                $output .= "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
                $output .= "<thead><tr style='background-color: #f2f2f2;'>";
                $output .= "<th>Action</th><th>Plugin</th><th>Version</th><th>When</th><th>User</th>";
                $output .= "</tr></thead><tbody>";
                
                foreach ($logs as $log) {
                    $action_verb = ucfirst($log['action']);
                    $plugin_name = $log['plugin_name'];
                    $version = $log['plugin_version'];
                    $time_ago = isset($log['time_ago']) ? $log['time_ago'] : human_time_diff(strtotime($log['date_time']), current_time('timestamp')) . ' ago';
                    $user = isset($log['user_login']) && !empty($log['user_login']) ? $log['user_login'] : 'system';
                    
                    $output .= "<tr>";
                    $output .= "<td>{$action_verb}</td>";
                    $output .= "<td>{$plugin_name}</td>";
                    $output .= "<td>{$version}</td>";
                    $output .= "<td>{$time_ago}</td>";
                    $output .= "<td>{$user}</td>";
                    $output .= "</tr>";
                }
                
                $output .= "</tbody></table>";
            } else {
                $output .= "<p>No plugin activity found for the specified criteria.</p>";
            }
            
            // Save this as a message/response pair
            $this->save_message("Show plugin history", $output);
            
            return [
                'success' => true,
                'message' => $output
            ];
            
        } catch (Exception $e) {
            mpai_log_error('Error getting direct plugin history: ' . $e->getMessage(), 'chat', [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Error retrieving plugin history: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Helper method to check if a message contains any of the given keywords
     *
     * @param string $message The message to check
     * @param array $keywords Array of keywords to look for
     * @return bool True if any keyword is found in the message
     */
    private function message_matches_keywords($message, $keywords) {
        foreach ($keywords as $keyword) {
            if (stripos($message, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Get direct best-selling memberships without using AI
     *
     * @return array Response data with formatted best-selling memberships
     */
    private function get_direct_best_selling_memberships() {
        mpai_log_debug('Getting direct best-selling memberships', 'chat');
        
        try {
            // Make sure the memberpress API instance is fresh
            if (!isset($this->memberpress_api) || !is_object($this->memberpress_api)) {
                mpai_log_debug('Creating MemberPress API instance', 'chat');
                $this->memberpress_api = new MPAI_MemberPress_API();
            }
            
            // Get best-selling memberships data
            // Note: The method is singular in the API class, not plural
            try {
                $best_selling = $this->memberpress_api->get_best_selling_membership();
                mpai_log_debug('Successfully retrieved best-selling memberships data', 'chat');
            } catch (Exception $api_e) {
                mpai_log_error('Error calling get_best_selling_membership: ' . $api_e->getMessage(), 'chat', array(
                    'file' => $api_e->getFile(),
                    'line' => $api_e->getLine(),
                    'trace' => $api_e->getTraceAsString()
                ));
                
                // Use fallback data
                $best_selling = $this->get_fallback_best_selling_data();
                mpai_log_debug('Using fallback best-selling memberships data', 'chat');
            }
            
            if (empty($best_selling)) {
                $output = "<h2>Best-Selling Memberships</h2>";
                $output .= "<p>No membership sales data available.</p>";
                
                // Save this as a message/response pair
                $this->save_message("Show best-selling memberships", $output);
                
                return array(
                    'success' => true,
                    'message' => $output
                );
            }
            
            // Format the output as HTML table
            $output = "<h2>Best-Selling Memberships</h2>";
            $output .= "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
            $output .= "<thead><tr style='background-color: #f2f2f2;'>";
            $output .= "<th>Rank</th><th>Membership</th><th>Price</th><th>Sales</th><th>Revenue</th>";
            $output .= "</tr></thead><tbody>";
            
            $rank = 1;
            foreach ($best_selling as $membership) {
                $title = isset($membership['title']) ? $membership['title'] : 'Unknown';
                $price = isset($membership['price']) ? '$' . number_format($membership['price'], 2) : 'N/A';
                $sales = isset($membership['sales']) ? number_format($membership['sales']) : '0';
                $revenue = isset($membership['revenue']) ? '$' . number_format($membership['revenue'], 2) : '$0.00';
                
                $output .= "<tr>";
                $output .= "<td>{$rank}</td>";
                $output .= "<td>{$title}</td>";
                $output .= "<td>{$price}</td>";
                $output .= "<td>{$sales}</td>";
                $output .= "<td>{$revenue}</td>";
                $output .= "</tr>";
                
                $rank++;
            }
            
            $output .= "</tbody></table>";
            
            // Save this as a message/response pair
            $this->save_message("Show best-selling memberships", $output);
            
            return array(
                'success' => true,
                'message' => $output
            );
            
        } catch (Exception $e) {
            mpai_log_error('Error getting best-selling memberships: ' . $e->getMessage(), 'chat', array(
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ));
            
            return array(
                'success' => false,
                'message' => 'Error retrieving best-selling memberships: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Get direct active subscriptions without using AI
     *
     * @return array Response data with formatted active subscriptions
     */
    private function get_direct_active_subscriptions() {
        mpai_log_debug('Getting direct active subscriptions', 'chat');
        
        try {
            // Make sure the memberpress API instance is fresh
            if (!isset($this->memberpress_api) || !is_object($this->memberpress_api)) {
                mpai_log_debug('Creating MemberPress API instance', 'chat');
                $this->memberpress_api = new MPAI_MemberPress_API();
            }
            
            // Get active subscriptions data
            $subscriptions = $this->memberpress_api->get_active_subscriptions();
            
            if (empty($subscriptions)) {
                $output = "<h2>Active Subscriptions</h2>";
                $output .= "<p>No active subscriptions found.</p>";
                
                // Save this as a message/response pair
                $this->save_message("Show active subscriptions", $output);
                
                return array(
                    'success' => true,
                    'message' => $output
                );
            }
            
            // Format the output as HTML table
            $output = "<h2>Active Subscriptions</h2>";
            $output .= "<p>Total active subscriptions: " . count($subscriptions) . "</p>";
            $output .= "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
            $output .= "<thead><tr style='background-color: #f2f2f2;'>";
            $output .= "<th>ID</th><th>User</th><th>Membership</th><th>Status</th><th>Created</th><th>Expires</th>";
            $output .= "</tr></thead><tbody>";
            
            foreach ($subscriptions as $sub) {
                $id = isset($sub['id']) ? $sub['id'] : 'N/A';
                $user = isset($sub['user_login']) ? $sub['user_login'] : 'Unknown';
                $membership = isset($sub['product_name']) ? $sub['product_name'] : 'Unknown';
                $status = isset($sub['status']) ? ucfirst($sub['status']) : 'Unknown';
                $created = isset($sub['created_at']) ? date('Y-m-d', strtotime($sub['created_at'])) : 'N/A';
                $expires = isset($sub['expires_at']) ? date('Y-m-d', strtotime($sub['expires_at'])) : 'N/A';
                
                $output .= "<tr>";
                $output .= "<td>{$id}</td>";
                $output .= "<td>{$user}</td>";
                $output .= "<td>{$membership}</td>";
                $output .= "<td>{$status}</td>";
                $output .= "<td>{$created}</td>";
                $output .= "<td>{$expires}</td>";
                $output .= "</tr>";
            }
            
            $output .= "</tbody></table>";
            
            // Save this as a message/response pair
            $this->save_message("Show active subscriptions", $output);
            
            return array(
                'success' => true,
                'message' => $output
            );
            
        } catch (Exception $e) {
            mpai_log_error('Error getting active subscriptions: ' . $e->getMessage(), 'chat', array(
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ));
            
            return array(
                'success' => false,
                'message' => 'Error retrieving active subscriptions: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Get direct user list without using AI
     *
     * @return array Response data with formatted user list
     */
    private function get_direct_user_list() {
        mpai_log_debug('Getting direct user list', 'chat');
        
        try {
            // Execute a direct DB query for users
            global $wpdb;
            $results = $wpdb->get_results("SELECT ID, user_login, user_email, user_registered FROM {$wpdb->users} ORDER BY ID ASC LIMIT 10");
            
            if (empty($results)) {
                $output = "<h2>WordPress Users</h2>";
                $output .= "<p>No users found.</p>";
                
                // Save this as a message/response pair
                $this->save_message("Show users", $output);
                
                return array(
                    'success' => true,
                    'message' => $output
                );
            }
            
            // Format the output as HTML table
            $output = "<h2>WordPress Users</h2>";
            $output .= "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
            $output .= "<thead><tr style='background-color: #f2f2f2;'>";
            $output .= "<th>ID</th><th>Username</th><th>Email</th><th>Registration Date</th><th>Links</th>";
            $output .= "</tr></thead><tbody>";
            
            foreach ($results as $user) {
                $edit_url = admin_url('user-edit.php?user_id=' . $user->ID);
                $author_url = get_author_posts_url($user->ID);
                $reg_date = date('Y-m-d', strtotime($user->user_registered));
                
                $output .= "<tr>";
                $output .= "<td>{$user->ID}</td>";
                $output .= "<td>{$user->user_login}</td>";
                $output .= "<td>{$user->user_email}</td>";
                $output .= "<td>{$reg_date}</td>";
                $output .= "<td><a href='{$edit_url}' target='_blank'>Edit</a> &bull; <a href='{$author_url}' target='_blank'>Posts</a></td>";
                $output .= "</tr>";
            }
            
            $output .= "</tbody></table>";
            
            // Save this as a message/response pair
            $this->save_message("Show users", $output);
            
            return array(
                'success' => true,
                'message' => $output
            );
            
        } catch (Exception $e) {
            mpai_log_error('Error getting user list: ' . $e->getMessage(), 'chat', array(
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ));
            
            return array(
                'success' => false,
                'message' => 'Error retrieving user list: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Get direct post list without using AI
     *
     * @return array Response data with formatted post list
     */
    private function get_direct_post_list() {
        mpai_log_debug('Getting direct post list', 'chat');
        
        try {
            // Execute a direct DB query for posts
            global $wpdb;
            $results = $wpdb->get_results("SELECT ID, post_title, post_date, post_status FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish' ORDER BY post_date DESC LIMIT 10");
            
            if (empty($results)) {
                $output = "<h2>WordPress Posts</h2>";
                $output .= "<p>No posts found.</p>";
                
                // Save this as a message/response pair
                $this->save_message("Show posts", $output);
                
                return array(
                    'success' => true,
                    'message' => $output
                );
            }
            
            // Format the output as HTML table
            $output = "<h2>WordPress Posts</h2>";
            $output .= "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
            $output .= "<thead><tr style='background-color: #f2f2f2;'>";
            $output .= "<th>ID</th><th>Title</th><th>Status</th><th>Date</th><th>Links</th>";
            $output .= "</tr></thead><tbody>";
            
            foreach ($results as $post) {
                $post_url = get_permalink($post->ID);
                $edit_url = get_edit_post_link($post->ID, 'raw');
                $date = date('Y-m-d', strtotime($post->post_date));
                
                $output .= "<tr>";
                $output .= "<td>{$post->ID}</td>";
                $output .= "<td><a href='{$post_url}' target='_blank'>{$post->post_title}</a></td>";
                $output .= "<td>{$post->post_status}</td>";
                $output .= "<td>{$date}</td>";
                $output .= "<td><a href='{$edit_url}' target='_blank'>Edit</a></td>";
                $output .= "</tr>";
            }
            
            $output .= "</tbody></table>";
            
            // Save this as a message/response pair
            $this->save_message("Show posts", $output);
            
            return array(
                'success' => true,
                'message' => $output
            );
            
        } catch (Exception $e) {
            mpai_log_error('Error getting post list: ' . $e->getMessage(), 'chat', array(
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ));
            
            return array(
                'success' => false,
                'message' => 'Error retrieving post list: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Get direct plugin list formatted without using AI
     *
     * @return array Response data with formatted plugin list
     */
    private function get_direct_plugin_list_formatted() {
        mpai_log_debug('Getting formatted plugin list', 'chat');
        
        try {
            // Get raw plugin list
            $plugin_list_output = $this->get_direct_plugin_list();
            
            // Format the output
            $output = "<h2>WordPress Plugins</h2>";
            
            // Ensure plugin_list_output is a string
            if (!is_string($plugin_list_output)) {
                if (is_array($plugin_list_output) || is_object($plugin_list_output)) {
                    $plugin_list_output = json_encode($plugin_list_output, JSON_PRETTY_PRINT);
                } else {
                    $plugin_list_output = (string)$plugin_list_output;
                }
            }
            
            // Convert tabular data to HTML table
            $lines = explode("\n", trim($plugin_list_output));
            
            if (count($lines) > 0) {
                $headers = explode("\t", $lines[0]);
                
                $output .= "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
                $output .= "<thead><tr style='background-color: #f2f2f2;'>";
                
                foreach ($headers as $header) {
                    $output .= "<th>{$header}</th>";
                }
                
                $output .= "</tr></thead><tbody>";
                
                // Skip header row
                for ($i = 1; $i < count($lines); $i++) {
                    if (empty(trim($lines[$i]))) {
                        continue;
                    }
                    
                    $cells = explode("\t", $lines[$i]);
                    $output .= "<tr>";
                    
                    foreach ($cells as $cell) {
                        $output .= "<td>{$cell}</td>";
                    }
                    
                    $output .= "</tr>";
                }
                
                $output .= "</tbody></table>";
            } else {
                $output .= "<p>No plugins found.</p>";
            }
            
            // Save this as a message/response pair
            $this->save_message("Show plugins", $output);
            
            return array(
                'success' => true,
                'message' => $output
            );
            
        } catch (Exception $e) {
            mpai_log_error('Error getting formatted plugin list: ' . $e->getMessage(), 'chat', array(
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ));
            
            return array(
                'success' => false,
                'message' => 'Error retrieving plugin list: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Get fallback best-selling memberships data
     *
     * This method provides sample data when the API method fails
     *
     * @return array Sample best-selling memberships data
     */
    private function get_fallback_best_selling_data() {
        mpai_log_debug('Generating fallback best-selling memberships data', 'chat');
        
        // Get all membership products
        $args = array(
            'post_type' => 'memberpressproduct',
            'posts_per_page' => 10,
            'post_status' => 'publish'
        );
        
        $memberships = get_posts($args);
        
        if (empty($memberships)) {
            mpai_log_debug('No membership products found for fallback data', 'chat');
            return array();
        }
        
        // Create sample data with realistic random numbers
        $results = array();
        foreach ($memberships as $index => $membership) {
            // Get price
            $price = get_post_meta($membership->ID, '_mepr_product_price', true);
            
            // Generate a random number for sample sales
            $sample_sales = rand(10, 500);
            
            // Use post date to influence randomness - newer products might have fewer sales
            $post_date = strtotime($membership->post_date);
            $days_old = (time() - $post_date) / (60 * 60 * 24);
            // Adjust sales based on age - newer products might have lower sales numbers
            $sales_adjustment = min($days_old / 30, 5); // Up to 5x multiplier for older products
            $sample_sales = intval($sample_sales * (1 + $sales_adjustment / 10));
            
            // Calculate sample revenue
            $sample_revenue = $price * $sample_sales;
            
            $results[] = array(
                'rank' => $index + 1,
                'product_id' => $membership->ID,
                'title' => $membership->post_title,
                'price' => $price,
                'sales' => $sample_sales,
                'revenue' => $sample_revenue,
                'is_sample_data' => true // Flag to indicate this is sample data
            );
        }
        
        // Sort by the sample sales
        usort($results, function($a, $b) {
            return $b['sales'] - $a['sales'];
        });
        
        // Update ranks after sorting
        foreach ($results as $index => $result) {
            $results[$index]['rank'] = $index + 1;
        }
        
        // Limit to top 5 best sellers after sorting
        $results = array_slice($results, 0, 5);
        
        mpai_log_debug('Generated fallback data for ' . count($results) . ' memberships', 'chat');
        return $results;
    }
    
    /**
     * Get direct theme list without using AI
     *
     * @return array Response data with formatted theme list
     */
    private function get_direct_theme_list() {
        mpai_log_debug('Getting direct theme list', 'chat');
        
        try {
            // Get all themes
            $themes = wp_get_themes();
            $current_theme = wp_get_theme();
            
            if (empty($themes)) {
                $output = "<h2>WordPress Themes</h2>";
                $output .= "<p>No themes found.</p>";
                
                // Save this as a message/response pair
                $this->save_message("Show themes", $output);
                
                return array(
                    'success' => true,
                    'message' => $output
                );
            }
            
            // Format the output as HTML table
            $output = "<h2>WordPress Themes</h2>";
            $output .= "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
            $output .= "<thead><tr style='background-color: #f2f2f2;'>";
            $output .= "<th>Name</th><th>Version</th><th>Status</th><th>Author</th><th>Description</th>";
            $output .= "</tr></thead><tbody>";
            
            foreach ($themes as $theme_slug => $theme) {
                $name = $theme->get('Name');
                $version = $theme->get('Version');
                $author = $theme->get('Author');
                $description = $theme->get('Description');
                $status = ($current_theme->get_stylesheet() === $theme->get_stylesheet()) ? '✅ Active' : 'Inactive';
                
                $output .= "<tr>";
                $output .= "<td>{$name}</td>";
                $output .= "<td>{$version}</td>";
                $output .= "<td>{$status}</td>";
                $output .= "<td>{$author}</td>";
                $output .= "<td>{$description}</td>";
                $output .= "</tr>";
            }
            
            $output .= "</tbody></table>";
            
            // Save this as a message/response pair
            $this->save_message("Show themes", $output);
            
            return array(
                'success' => true,
                'message' => $output
            );
            
        } catch (Exception $e) {
            mpai_log_error('Error getting theme list: ' . $e->getMessage(), 'chat', array(
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ));
            
            return array(
                'success' => false,
                'message' => 'Error retrieving theme list: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Activate a theme by name
     *
     * @return array Response data with activation result
     */
    private function activate_theme_by_name() {
        mpai_log_debug('Activating theme by name from message', 'chat');
        
        try {
            // Get the user's message
            $message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
            
            if (empty($message)) {
                return array(
                    'success' => false,
                    'message' => 'No message provided to extract theme name'
                );
            }
            
            // Extract theme name from message
            $theme_name = $this->extract_theme_name_from_message($message);
            
            if (empty($theme_name)) {
                return array(
                    'success' => false,
                    'message' => 'Could not determine which theme to activate. Please specify the theme name.'
                );
            }
            
            mpai_log_debug('Extracted theme name: ' . $theme_name, 'chat');
            
            // Get all themes
            $themes = wp_get_themes();
            $theme_to_activate = null;
            $theme_stylesheet = '';
            
            // First try exact match
            foreach ($themes as $theme_slug => $theme) {
                if (strtolower($theme->get('Name')) === strtolower($theme_name)) {
                    $theme_to_activate = $theme;
                    $theme_stylesheet = $theme_slug;
                    break;
                }
            }
            
            // If no exact match, try partial match
            if (!$theme_to_activate) {
                foreach ($themes as $theme_slug => $theme) {
                    if (stripos($theme->get('Name'), $theme_name) !== false) {
                        $theme_to_activate = $theme;
                        $theme_stylesheet = $theme_slug;
                        break;
                    }
                }
            }
            
            // If still no match, try matching the slug
            if (!$theme_to_activate) {
                foreach ($themes as $theme_slug => $theme) {
                    if (stripos($theme_slug, str_replace(' ', '', strtolower($theme_name))) !== false) {
                        $theme_to_activate = $theme;
                        $theme_stylesheet = $theme_slug;
                        break;
                    }
                }
            }
            
            if (!$theme_to_activate) {
                return array(
                    'success' => false,
                    'message' => "Theme '{$theme_name}' not found. Please check the theme name and try again."
                );
            }
            
            // Check if theme is already active
            $current_theme = wp_get_theme();
            if ($current_theme->get_stylesheet() === $theme_stylesheet) {
                return array(
                    'success' => true,
                    'message' => "Theme '{$theme_to_activate->get('Name')}' is already active."
                );
            }
            
            // Activate the theme
            switch_theme($theme_stylesheet);
            
            // Verify activation
            $new_theme = wp_get_theme();
            $success = ($new_theme->get_stylesheet() === $theme_stylesheet);
            
            if ($success) {
                $output = "<h2>Theme Activation</h2>";
                $output .= "<p>Successfully activated theme: <strong>{$theme_to_activate->get('Name')}</strong></p>";
                $output .= "<ul>";
                $output .= "<li><strong>Version:</strong> {$theme_to_activate->get('Version')}</li>";
                $output .= "<li><strong>Author:</strong> {$theme_to_activate->get('Author')}</li>";
                $output .= "<li><strong>Theme URI:</strong> {$theme_to_activate->get('ThemeURI')}</li>";
                $output .= "</ul>";
                
                // Save this as a message/response pair
                $this->save_message($message, $output);
                
                return array(
                    'success' => true,
                    'message' => $output
                );
            } else {
                return array(
                    'success' => false,
                    'message' => "Failed to activate theme '{$theme_to_activate->get('Name')}'. Please try again or check for errors."
                );
            }
            
        } catch (Exception $e) {
            mpai_log_error('Error activating theme: ' . $e->getMessage(), 'chat', array(
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ));
            
            return array(
                'success' => false,
                'message' => 'Error activating theme: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Extract theme name from user message
     *
     * @param string $message User message
     * @return string|null Theme name or null if not found
     */
    private function extract_theme_name_from_message($message) {
        // Common theme name patterns
        $patterns = [
            // "activate theme Twenty Twenty-Three"
            '/(?:activate|switch|change|use|set)(?:\s+to)?(?:\s+the)?\s+theme\s+([a-zA-Z0-9\s\-]+)(?:\.|\!|\?|$)/i',
            
            // "switch to Twenty Twenty-Three theme"
            '/(?:activate|switch|change|use|set)(?:\s+to)?(?:\s+the)?\s+([a-zA-Z0-9\s\-]+)\s+theme(?:\.|\!|\?|$)/i',
            
            // "make Twenty Twenty-Three the active theme"
            '/make\s+([a-zA-Z0-9\s\-]+)(?:\s+the)?\s+active\s+theme/i',
            
            // "set active theme to Twenty Twenty-Three"
            '/set\s+(?:the\s+)?active\s+theme\s+(?:to\s+)?([a-zA-Z0-9\s\-]+)(?:\.|\!|\?|$)/i'
        ];
        
        // Try each pattern
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                return trim($matches[1]);
            }
        }
        
        // Handle common WordPress theme names directly
        $common_themes = [
            'twenty twenty-three' => 'Twenty Twenty-Three',
            'twentytwentythree' => 'Twenty Twenty-Three',
            'twenty23' => 'Twenty Twenty-Three',
            'twenty twenty-two' => 'Twenty Twenty-Two',
            'twentytwentytwo' => 'Twenty Twenty-Two',
            'twenty22' => 'Twenty Twenty-Two',
            'twenty twenty-one' => 'Twenty Twenty-One',
            'twentytwentyone' => 'Twenty Twenty-One',
            'twenty21' => 'Twenty Twenty-One',
            'twenty twenty' => 'Twenty Twenty',
            'twentytwenty' => 'Twenty Twenty',
            'twenty20' => 'Twenty Twenty',
            'twenty nineteen' => 'Twenty Nineteen',
            'twentynineteen' => 'Twenty Nineteen',
            'twenty19' => 'Twenty Nineteen',
            'twenty seventeen' => 'Twenty Seventeen',
            'twentyseventeen' => 'Twenty Seventeen',
            'twenty17' => 'Twenty Seventeen',
            'twenty sixteen' => 'Twenty Sixteen',
            'twentysixteen' => 'Twenty Sixteen',
            'twenty16' => 'Twenty Sixteen',
            'twenty fifteen' => 'Twenty Fifteen',
            'twentyfifteen' => 'Twenty Fifteen',
            'twenty15' => 'Twenty Fifteen',
            'twenty fourteen' => 'Twenty Fourteen',
            'twentyfourteen' => 'Twenty Fourteen',
            'twenty14' => 'Twenty Fourteen',
            'twenty thirteen' => 'Twenty Thirteen',
            'twentythirteen' => 'Twenty Thirteen',
            'twenty13' => 'Twenty Thirteen',
            'twenty twelve' => 'Twenty Twelve',
            'twentytwelve' => 'Twenty Twelve',
            'twenty12' => 'Twenty Twelve',
            'twenty eleven' => 'Twenty Eleven',
            'twentyeleven' => 'Twenty Eleven',
            'twenty11' => 'Twenty Eleven',
            'twenty ten' => 'Twenty Ten',
            'twentyten' => 'Twenty Ten',
            'twenty10' => 'Twenty Ten'
        ];
        
        $message_lower = strtolower($message);
        foreach ($common_themes as $theme_key => $theme_name) {
            if (strpos($message_lower, $theme_key) !== false) {
                return $theme_name;
            }
        }
        
        // If no match found, return null
        return null;
    }
    
    /**
     * Activate a plugin by name
     *
     * @return array Response data with activation result
     */
    private function activate_plugin_by_name() {
        mpai_log_debug('Activating plugin by name from message', 'chat');
        
        try {
            // Get the user's message
            $message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
            
            if (empty($message)) {
                return array(
                    'success' => false,
                    'message' => 'No message provided to extract plugin name'
                );
            }
            
            // Extract plugin name from message
            $plugin_name = $this->extract_plugin_name_from_message($message);
            
            if (empty($plugin_name)) {
                return array(
                    'success' => false,
                    'message' => 'Could not determine which plugin to activate. Please specify the plugin name.'
                );
            }
            
            mpai_log_debug('Extracted plugin name: ' . $plugin_name, 'chat');
            
            // Ensure plugin functions are available
            if (!function_exists('get_plugins')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
            
            // Get all plugins
            $all_plugins = get_plugins();
            $active_plugins = get_option('active_plugins');
            $plugin_to_activate = null;
            $plugin_file = '';
            
            // First try exact match on plugin name
            foreach ($all_plugins as $plugin_file_path => $plugin_data) {
                if (strtolower($plugin_data['Name']) === strtolower($plugin_name)) {
                    $plugin_to_activate = $plugin_data;
                    $plugin_file = $plugin_file_path;
                    break;
                }
            }
            
            // If no exact match, try partial match on plugin name
            if (!$plugin_to_activate) {
                foreach ($all_plugins as $plugin_file_path => $plugin_data) {
                    if (stripos($plugin_data['Name'], $plugin_name) !== false) {
                        $plugin_to_activate = $plugin_data;
                        $plugin_file = $plugin_file_path;
                        break;
                    }
                }
            }
            
            // If still no match, try matching the plugin file path
            if (!$plugin_to_activate) {
                foreach ($all_plugins as $plugin_file_path => $plugin_data) {
                    if (stripos($plugin_file_path, str_replace(' ', '-', strtolower($plugin_name))) !== false) {
                        $plugin_to_activate = $plugin_data;
                        $plugin_file = $plugin_file_path;
                        break;
                    }
                }
            }
            
            if (!$plugin_to_activate) {
                return array(
                    'success' => false,
                    'message' => "Plugin '{$plugin_name}' not found. Please check the plugin name and try again."
                );
            }
            
            // Check if plugin is already active
            if (in_array($plugin_file, $active_plugins)) {
                return array(
                    'success' => true,
                    'message' => "Plugin '{$plugin_to_activate['Name']}' is already active."
                );
            }
            
            // Activate the plugin
            $result = activate_plugin($plugin_file);
            
            if (is_wp_error($result)) {
                return array(
                    'success' => false,
                    'message' => "Failed to activate plugin '{$plugin_to_activate['Name']}': " . $result->get_error_message()
                );
            }
            
            $output = "<h2>Plugin Activation</h2>";
            $output .= "<p>Successfully activated plugin: <strong>{$plugin_to_activate['Name']}</strong></p>";
            $output .= "<ul>";
            $output .= "<li><strong>Version:</strong> {$plugin_to_activate['Version']}</li>";
            $output .= "<li><strong>Author:</strong> {$plugin_to_activate['Author']}</li>";
            $output .= "<li><strong>Plugin URI:</strong> {$plugin_to_activate['PluginURI']}</li>";
            $output .= "</ul>";
            
            // Save this as a message/response pair
            $this->save_message($message, $output);
            
            return array(
                'success' => true,
                'message' => $output
            );
            
        } catch (Exception $e) {
            mpai_log_error('Error activating plugin: ' . $e->getMessage(), 'chat', array(
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ));
            
            return array(
                'success' => false,
                'message' => 'Error activating plugin: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Deactivate a plugin by name
     *
     * @return array Response data with deactivation result
     */
    private function deactivate_plugin_by_name() {
        mpai_log_debug('Deactivating plugin by name from message', 'chat');
        
        try {
            // Get the user's message
            $message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
            
            if (empty($message)) {
                return array(
                    'success' => false,
                    'message' => 'No message provided to extract plugin name'
                );
            }
            
            // Extract plugin name from message
            $plugin_name = $this->extract_plugin_name_from_message($message, 'deactivate');
            
            if (empty($plugin_name)) {
                return array(
                    'success' => false,
                    'message' => 'Could not determine which plugin to deactivate. Please specify the plugin name.'
                );
            }
            
            mpai_log_debug('Extracted plugin name: ' . $plugin_name, 'chat');
            
            // Ensure plugin functions are available
            if (!function_exists('get_plugins')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
            
            // Get all plugins
            $all_plugins = get_plugins();
            $active_plugins = get_option('active_plugins');
            $plugin_to_deactivate = null;
            $plugin_file = '';
            
            // First try exact match on plugin name
            foreach ($all_plugins as $plugin_file_path => $plugin_data) {
                if (strtolower($plugin_data['Name']) === strtolower($plugin_name)) {
                    $plugin_to_deactivate = $plugin_data;
                    $plugin_file = $plugin_file_path;
                    break;
                }
            }
            
            // If no exact match, try partial match on plugin name
            if (!$plugin_to_deactivate) {
                foreach ($all_plugins as $plugin_file_path => $plugin_data) {
                    if (stripos($plugin_data['Name'], $plugin_name) !== false) {
                        $plugin_to_deactivate = $plugin_data;
                        $plugin_file = $plugin_file_path;
                        break;
                    }
                }
            }
            
            // If still no match, try matching the plugin file path
            if (!$plugin_to_deactivate) {
                foreach ($all_plugins as $plugin_file_path => $plugin_data) {
                    if (stripos($plugin_file_path, str_replace(' ', '-', strtolower($plugin_name))) !== false) {
                        $plugin_to_deactivate = $plugin_data;
                        $plugin_file = $plugin_file_path;
                        break;
                    }
                }
            }
            
            if (!$plugin_to_deactivate) {
                return array(
                    'success' => false,
                    'message' => "Plugin '{$plugin_name}' not found. Please check the plugin name and try again."
                );
            }
            
            // Check if plugin is already inactive
            if (!in_array($plugin_file, $active_plugins)) {
                return array(
                    'success' => true,
                    'message' => "Plugin '{$plugin_to_deactivate['Name']}' is already inactive."
                );
            }
            
            // Deactivate the plugin
            deactivate_plugins($plugin_file);
            
            // Verify deactivation
            $active_plugins_after = get_option('active_plugins');
            $success = !in_array($plugin_file, $active_plugins_after);
            
            if ($success) {
                $output = "<h2>Plugin Deactivation</h2>";
                $output .= "<p>Successfully deactivated plugin: <strong>{$plugin_to_deactivate['Name']}</strong></p>";
                $output .= "<ul>";
                $output .= "<li><strong>Version:</strong> {$plugin_to_deactivate['Version']}</li>";
                $output .= "<li><strong>Author:</strong> {$plugin_to_deactivate['Author']}</li>";
                $output .= "<li><strong>Plugin URI:</strong> {$plugin_to_deactivate['PluginURI']}</li>";
                $output .= "</ul>";
                
                // Save this as a message/response pair
                $this->save_message($message, $output);
                
                return array(
                    'success' => true,
                    'message' => $output
                );
            } else {
                return array(
                    'success' => false,
                    'message' => "Failed to deactivate plugin '{$plugin_to_deactivate['Name']}'. Please try again or check for errors."
                );
            }
            
        } catch (Exception $e) {
            mpai_log_error('Error deactivating plugin: ' . $e->getMessage(), 'chat', array(
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ));
            
            return array(
                'success' => false,
                'message' => 'Error deactivating plugin: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Extract plugin name from user message
     *
     * @param string $message User message
     * @param string $action Action type ('activate' or 'deactivate')
     * @return string|null Plugin name or null if not found
     */
    private function extract_plugin_name_from_message($message, $action = 'activate') {
        $action_words = ($action === 'activate') ?
            '(?:activate|enable|turn\s+on|start)' :
            '(?:deactivate|disable|turn\s+off|stop)';
        
        // Common plugin name patterns
        $patterns = [
            // "activate plugin WooCommerce"
            "/{$action_words}(?:\\s+the)?\\s+plugin\\s+([a-zA-Z0-9\\s\\-\\_\\.]+)(?:\\.|\!|\\?|$)/i",
            
            // "activate WooCommerce plugin"
            "/{$action_words}(?:\\s+the)?\\s+([a-zA-Z0-9\\s\\-\\_\\.]+)\\s+plugin(?:\\.|\!|\\?|$)/i",
            
            // "enable WooCommerce"
            "/{$action_words}(?:\\s+the)?\\s+([a-zA-Z0-9\\s\\-\\_\\.]+)(?:\\.|\!|\\?|$)/i"
        ];
        
        // Try each pattern
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                return trim($matches[1]);
            }
        }
        
        // Handle common plugin names directly
        $common_plugins = [
            'woocommerce' => 'WooCommerce',
            'memberpress' => 'MemberPress',
            'elementor' => 'Elementor',
            'yoast' => 'Yoast SEO',
            'yoast seo' => 'Yoast SEO',
            'wordpress seo' => 'Yoast SEO',
            'contact form 7' => 'Contact Form 7',
            'cf7' => 'Contact Form 7',
            'akismet' => 'Akismet',
            'jetpack' => 'Jetpack',
            'wpforms' => 'WPForms',
            'wp forms' => 'WPForms',
            'gravity forms' => 'Gravity Forms',
            'wordfence' => 'Wordfence',
            'classic editor' => 'Classic Editor'
        ];
        
        $message_lower = strtolower($message);
        foreach ($common_plugins as $plugin_key => $plugin_name) {
            if (strpos($message_lower, $plugin_key) !== false) {
                return $plugin_name;
            }
        }
        
        // If no match found, return null
        return null;
    }
}
