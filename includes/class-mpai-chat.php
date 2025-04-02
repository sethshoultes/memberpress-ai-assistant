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
            error_log('MPAI Chat: Constructor started');
            
            // Make sure all required classes are loaded
            if (!class_exists('MPAI_API_Router')) {
                error_log('MPAI Chat: MPAI_API_Router class not found, attempting to load');
                if (file_exists(MPAI_PLUGIN_DIR . 'includes/class-mpai-api-router.php')) {
                    require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-api-router.php';
                    error_log('MPAI Chat: MPAI_API_Router file loaded');
                } else {
                    error_log('MPAI Chat: MPAI_API_Router file not found at: ' . MPAI_PLUGIN_DIR . 'includes/class-mpai-api-router.php');
                    throw new Exception('Required class file MPAI_API_Router not found');
                }
            }
            
            if (!class_exists('MPAI_MemberPress_API')) {
                error_log('MPAI Chat: MPAI_MemberPress_API class not found, attempting to load');
                if (file_exists(MPAI_PLUGIN_DIR . 'includes/class-mpai-memberpress-api.php')) {
                    require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-memberpress-api.php';
                    error_log('MPAI Chat: MPAI_MemberPress_API file loaded');
                } else {
                    error_log('MPAI Chat: MPAI_MemberPress_API file not found at: ' . MPAI_PLUGIN_DIR . 'includes/class-mpai-memberpress-api.php');
                    throw new Exception('Required class file MPAI_MemberPress_API not found');
                }
            }
            
            if (!class_exists('MPAI_Context_Manager')) {
                error_log('MPAI Chat: MPAI_Context_Manager class not found, attempting to load');
                if (file_exists(MPAI_PLUGIN_DIR . 'includes/class-mpai-context-manager.php')) {
                    require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-context-manager.php';
                    error_log('MPAI Chat: MPAI_Context_Manager file loaded');
                } else {
                    error_log('MPAI Chat: MPAI_Context_Manager file not found at: ' . MPAI_PLUGIN_DIR . 'includes/class-mpai-context-manager.php');
                    throw new Exception('Required class file MPAI_Context_Manager not found');
                }
            }
            
            // Now create instances
            try {
                error_log('MPAI Chat: Creating API Router instance');
                $this->api_router = new MPAI_API_Router();
                error_log('MPAI Chat: API Router instance created successfully');
            } catch (Throwable $e) {
                error_log('MPAI Chat: Error creating API Router instance: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
                error_log('MPAI Chat: ' . $e->getTraceAsString());
                throw new Exception('Failed to initialize API Router: ' . $e->getMessage());
            }
            
            try {
                error_log('MPAI Chat: Creating MemberPress API instance');
                $this->memberpress_api = new MPAI_MemberPress_API();
                error_log('MPAI Chat: MemberPress API instance created successfully');
            } catch (Throwable $e) {
                error_log('MPAI Chat: Error creating MemberPress API instance: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
                error_log('MPAI Chat: ' . $e->getTraceAsString());
                throw new Exception('Failed to initialize MemberPress API: ' . $e->getMessage());
            }
            
            try {
                error_log('MPAI Chat: Creating Context Manager instance');
                $this->context_manager = new MPAI_Context_Manager();
                error_log('MPAI Chat: Context Manager instance created successfully');
            } catch (Throwable $e) {
                error_log('MPAI Chat: Error creating Context Manager instance: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
                error_log('MPAI Chat: ' . $e->getTraceAsString());
                throw new Exception('Failed to initialize Context Manager: ' . $e->getMessage());
            }
            
            // Set this instance in the context manager to enable message extraction
            try {
                error_log('MPAI Chat: Setting chat instance in context manager');
                if (method_exists($this->context_manager, 'set_chat_instance')) {
                    $this->context_manager->set_chat_instance($this);
                    error_log('MPAI Chat: Chat instance set in context manager successfully');
                } else {
                    error_log('MPAI Chat: set_chat_instance method not found in context manager');
                }
            } catch (Throwable $e) {
                error_log('MPAI Chat: Error setting chat instance in context manager: ' . $e->getMessage());
                // Continue even if this fails
            }
            
            try {
                error_log('MPAI Chat: Loading conversation history');
                $this->load_conversation();
                error_log('MPAI Chat: Conversation history loaded successfully');
            } catch (Throwable $e) {
                error_log('MPAI Chat: Error loading conversation history: ' . $e->getMessage());
                error_log('MPAI Chat: ' . $e->getTraceAsString());
                // Not throwing here since we can continue without conversation history
            }
            
            error_log('MPAI Chat: Constructor completed successfully');
        } catch (Throwable $e) {
            error_log('MPAI Chat: CRITICAL ERROR in constructor: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            error_log('MPAI Chat: ' . $e->getTraceAsString());
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
        error_log('MPAI Chat: Getting fresh system prompt');
        
        // Make sure the memberpress API instance is fresh
        if (!isset($this->memberpress_api) || !is_object($this->memberpress_api)) {
            error_log('MPAI Chat: Recreating MemberPress API instance for fresh data');
            $this->memberpress_api = new MPAI_MemberPress_API();
        }
        
        // Force a refresh of the MemberPress data - we don't want cached values
        error_log('MPAI Chat: Fetching fresh MemberPress data summary');
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
            error_log('MPAI Chat: process_message started with message: ' . $message);
            
            // Check for plugin-related queries and add a guidance message
            $plugin_keywords = ['recently installed', 'recently activated', 'plugin history', 'plugin log', 'plugin activity', 'when was plugin', 'installed recently', 'activated recently', 'what plugins', 'which plugins'];
            $inject_plugin_guidance = false;
            
            // Special handling for wp plugin list command - ADDED April 2, 2025 for debugging
            $is_wp_plugin_list_command = (strtolower(trim($message)) === 'wp plugin list');
            if ($is_wp_plugin_list_command) {
                error_log('MPAI Chat: CRITICAL! Detected direct wp plugin list command - ' . date('H:i:s'));
                
                // DIRECT EXECUTION - Skip AI entirely for this specific command
                try {
                    error_log('MPAI Chat: Attempting direct execution of wp plugin list...');
                    
                    // Initialize the context manager if needed
                    if (!isset($this->context_manager)) {
                        $this->context_manager = new MPAI_Context_Manager();
                        error_log('MPAI Chat: Created new Context Manager for direct execution');
                    }
                    
                    // Execute the command directly
                    $plugin_list_output = $this->context_manager->run_command('wp plugin list');
                    error_log('MPAI Chat: Direct execution successful, output length: ' . strlen($plugin_list_output));
                    
                    // Create a response with the direct output, using a clean code block format
                    // that's easier for our JavaScript to parse
                    
                    // Extract actual table data if it's a JSON string
                    if (strpos($plugin_list_output, '{"success":true,"tool":"wp_cli","command_type":"plugin_list","result":') === 0) {
                        error_log('MPAI Chat: Plugin list is in JSON format, extracting tabular data');
                        try {
                            $decoded = json_decode($plugin_list_output, true);
                            if (json_last_error() === JSON_ERROR_NONE && isset($decoded['result'])) {
                                $plugin_list_output = $decoded['result'];
                                error_log('MPAI Chat: Successfully extracted tabular data from plugin list JSON');
                            }
                        } catch (Exception $e) {
                            error_log('MPAI Chat: Error decoding plugin list JSON: ' . $e->getMessage());
                        }
                    }
                    
                    $ai_response = "Here is the current list of plugins:\n\n";
                    $ai_response .= "```\n" . trim($plugin_list_output) . "\n```\n\n";
                    $ai_response .= "This information was generated directly from your WordPress database.";
                    
                    // Save this message and response
                    $this->save_message($message, $ai_response);
                    
                    error_log('MPAI Chat: Direct handling complete for wp plugin list command');
                    
                    // Return the response immediately in a format that the AJAX handler expects
                    return array(
                        'success' => true,
                        'message' => $ai_response,
                    );
                } catch (Exception $e) {
                    error_log('MPAI Chat: Error in direct execution of wp plugin list: ' . $e->getMessage());
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
                    error_log('MPAI Chat: Detected plugin-related query, will inject guidance');
                    break;
                }
            }
            
            foreach ($best_selling_keywords as $keyword) {
                if (stripos($message, $keyword) !== false) {
                    $inject_best_selling_guidance = true;
                    error_log('MPAI Chat: Detected best-selling membership query, will inject guidance');
                    break;
                }
            }
            
            foreach ($active_subscriptions_keywords as $keyword) {
                if (stripos($message, $keyword) !== false) {
                    $inject_active_subscriptions_guidance = true;
                    error_log('MPAI Chat: Detected active subscriptions query, will inject guidance');
                    break;
                }
            }
            
            // First check if we have all required dependencies initialized
            if (!isset($this->api_router) || !is_object($this->api_router)) {
                error_log('MPAI Chat: API Router not initialized, attempting to create');
                try {
                    if (class_exists('MPAI_API_Router')) {
                        $this->api_router = new MPAI_API_Router();
                        error_log('MPAI Chat: API Router created successfully in process_message');
                    } else {
                        error_log('MPAI Chat: MPAI_API_Router class not available');
                        return array(
                            'success' => false,
                            'message' => 'Internal error: API Router not available'
                        );
                    }
                } catch (Throwable $e) {
                    error_log('MPAI Chat: Failed to create API Router: ' . $e->getMessage());
                    return array(
                        'success' => false,
                        'message' => 'Failed to initialize API router: ' . $e->getMessage()
                    );
                }
            }
            
            // Initialize conversation if empty
            try {
                if (empty($this->conversation)) {
                    error_log('MPAI Chat: Conversation is empty, initializing with system prompt');
                    $system_prompt = $this->get_system_prompt();
                    error_log('MPAI Chat: Got system prompt of length: ' . strlen($system_prompt));
                    $this->conversation = array(
                        array('role' => 'system', 'content' => $system_prompt)
                    );
                    error_log('MPAI Chat: Conversation initialized with system prompt');
                }
                
                // If this is a plugin-related query, add a system message reminder
                if ($inject_plugin_guidance) {
                    error_log('MPAI Chat: Adding plugin logs guidance message to conversation');
                    $plugin_guidance = "CRITICAL INSTRUCTION: This query is about plugin history or activity. You MUST respond by calling the plugin_logs tool to get accurate information from the database. DO NOT provide any general advice or alternative methods. DO NOT use wp_api or try to guess plugin history. Format your response using ONLY the JSON format shown below.\n\n";
                    $plugin_guidance .= "For general plugin activity, use exactly:\n```json\n{\"tool\": \"plugin_logs\", \"parameters\": {\"days\": 30}}\n```\n\n";
                    $plugin_guidance .= "For queries about recently activated plugins, use exactly:\n```json\n{\"tool\": \"plugin_logs\", \"parameters\": {\"action\": \"activated\", \"days\": 30}}\n```\n\n";
                    $plugin_guidance .= "For queries about recently installed plugins, use exactly:\n```json\n{\"tool\": \"plugin_logs\", \"parameters\": {\"action\": \"installed\", \"days\": 30}}\n```\n\n";
                    $plugin_guidance .= "DO NOT explain what you're doing or wrap your tool call in prose - ONLY return the JSON block.";
                    
                    $this->conversation[] = array('role' => 'system', 'content' => $plugin_guidance);
                    error_log('MPAI Chat: Enhanced plugin logs guidance message added');
                }
                
                // If this is a best-selling membership query, add a system message reminder
                if ($inject_best_selling_guidance) {
                    error_log('MPAI Chat: Adding best-selling membership guidance message to conversation');
                    $best_selling_guidance = "CRITICAL INSTRUCTION: This query is about best-selling or popular memberships. You MUST respond by calling the memberpress_info tool with type=best_selling to get accurate information from the database. DO NOT provide any general advice or theoretical explanations. Format your response using the JSON format shown below.\n\n";
                    $best_selling_guidance .= "For best-selling memberships, use exactly:\n```json\n{\"tool\": \"memberpress_info\", \"parameters\": {\"type\": \"best_selling\"}}\n```\n\n";
                    $best_selling_guidance .= "After executing the tool call, explain the results to the user.";
                    
                    $this->conversation[] = array('role' => 'system', 'content' => $best_selling_guidance);
                    error_log('MPAI Chat: Enhanced best-selling membership guidance message added');
                }
                
                // If this is an active subscriptions query, add a system message reminder
                if ($inject_active_subscriptions_guidance) {
                    error_log('MPAI Chat: Adding active subscriptions guidance message to conversation');
                    $active_subscriptions_guidance = "CRITICAL INSTRUCTION: This query is about active subscriptions or current members. You MUST respond by calling the memberpress_info tool with type=active_subscriptions to get accurate information from the database. DO NOT provide any general advice or theoretical explanations. Format your response using the JSON format shown below.\n\n";
                    $active_subscriptions_guidance .= "For active subscriptions, use exactly:\n```json\n{\"tool\": \"memberpress_info\", \"parameters\": {\"type\": \"active_subscriptions\"}}\n```\n\n";
                    $active_subscriptions_guidance .= "After executing the tool call, explain the results to the user.";
                    
                    $this->conversation[] = array('role' => 'system', 'content' => $active_subscriptions_guidance);
                    error_log('MPAI Chat: Enhanced active subscriptions guidance message added');
                }
            } catch (Throwable $e) {
                error_log('MPAI Chat: Error initializing conversation: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
                error_log('MPAI Chat: ' . $e->getTraceAsString());
                // Continue without system prompt if it fails
                if (empty($this->conversation)) {
                    $this->conversation = array();
                    error_log('MPAI Chat: Created empty conversation array');
                }
            }
            
            // Check if the previous message was from the assistant and contained a WP-CLI fallback message
            try {
                error_log('MPAI Chat: Checking for previous WP-CLI fallback messages');
                $prev_assistant_message = null;
                $has_wp_cli_fallback = false;
                
                if (count($this->conversation) >= 2) {
                    $prev_assistant_index = count($this->conversation) - 1;
                    if (isset($this->conversation[$prev_assistant_index]['role']) && $this->conversation[$prev_assistant_index]['role'] == 'assistant') {
                        $prev_assistant_message = $this->conversation[$prev_assistant_index]['content'];
                        if (is_string($prev_assistant_message) && strpos($prev_assistant_message, 'WP-CLI is not available in this browser environment') !== false) {
                            $has_wp_cli_fallback = true;
                            error_log('MPAI Chat: Found WP-CLI fallback message');
                        }
                    }
                }
                
                // If the previous message had a WP-CLI fallback suggestion, add a system message
                if ($has_wp_cli_fallback) {
                    error_log('MPAI Chat: Adding WP-CLI fallback reminder');
                    $system_reminder = "IMPORTANT: WP-CLI is not available in browser environment. You MUST use the wp_api tool instead of wp_cli for operations. ";
                    $system_reminder .= "For example, to create a post use: {\"tool\": \"wp_api\", \"parameters\": {\"action\": \"create_post\", \"title\": \"...\", \"content\": \"...\"}}";
                    
                    $this->conversation[] = array('role' => 'system', 'content' => $system_reminder);
                    error_log('MPAI Chat: Added system reminder about WP-CLI fallback');
                }
            } catch (Throwable $e) {
                error_log('MPAI Chat: Error checking for WP-CLI fallback: ' . $e->getMessage());
                // Continue even if this fails
            }
            
            // Add user message to conversation
            try {
                error_log('MPAI Chat: Adding user message to conversation');
                $this->conversation[] = array('role' => 'user', 'content' => $message);
                error_log('MPAI Chat: User message added to conversation');
            } catch (Throwable $e) {
                error_log('MPAI Chat: Error adding user message to conversation: ' . $e->getMessage());
                // Initialize conversation with just the user message if adding fails
                $this->conversation = array(
                    array('role' => 'user', 'content' => $message)
                );
                error_log('MPAI Chat: Created new conversation with only user message');
            }
            
            // Get response using the API Router
            try {
                error_log('MPAI Chat: Generating chat completion using API Router');
                $response = $this->api_router->generate_completion($this->conversation);
                error_log('MPAI Chat: Received response from API Router');
            } catch (Throwable $e) {
                error_log('MPAI Chat: Error generating completion: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
                error_log('MPAI Chat: ' . $e->getTraceAsString());
                return array(
                    'success' => false,
                    'message' => 'Error generating AI response: ' . $e->getMessage()
                );
            }
            
            // Handle different response formats
            try {
                error_log('MPAI Chat: Checking response format');
                if (is_wp_error($response)) {
                    error_log('MPAI Chat: API returned WP_Error: ' . $response->get_error_message());
                    return array(
                        'success' => false,
                        'message' => 'AI Assistant Error: ' . $response->get_error_message(),
                    );
                }
                error_log('MPAI Chat: Response is not a WP_Error');
            } catch (Throwable $e) {
                error_log('MPAI Chat: Error checking if response is WP_Error: ' . $e->getMessage());
                // Continue processing in case it's not a WP_Error
            }
            
            // Handle array response (structured with tool calls)
            try {
                error_log('MPAI Chat: Processing array response format');
                if (is_array($response) && isset($response['message'])) {
                    error_log('MPAI Chat: Response has message field');
                    $message_content = $response['message'];
                    $has_tool_calls = isset($response['tool_calls']) && !empty($response['tool_calls']);
                    error_log('MPAI Chat: Response has tool calls: ' . ($has_tool_calls ? 'yes' : 'no'));
                    
                    // Check if this response looks like it contains a blog post or page
                    // and add a marker if it does
                    $modified_content = $message_content;
                    
                    try {
                        error_log('MPAI Chat: Checking for content patterns');
                        // Check for blog post content patterns
                        if (preg_match('/(?:#+\s*Title:?|Title:)\s*([^\n]+)/i', $message_content) ||
                            (preg_match('/^#+\s*([^\n]+)/i', $message_content) && 
                             preg_match('/introduction|summary|overview|content|body|conclusion/i', $message_content))) {
                            
                            // This looks like a blog post or article
                            if (method_exists($this, 'add_content_marker')) {
                                $modified_content = $this->add_content_marker($message_content, 'blog-post');
                                error_log('MPAI Chat: Added blog-post marker to response');
                            } else {
                                error_log('MPAI Chat: add_content_marker method not available');
                            }
                        }
                        
                        // Check for page content patterns
                        if (strpos(strtolower($message), 'create a page') !== false && 
                            (preg_match('/(?:#+\s*Title:?|Title:)\s*([^\n]+)/i', $message_content) ||
                            preg_match('/^#+\s*([^\n]+)/i', $message_content))) {
                            
                            // This looks like a page
                            if (method_exists($this, 'add_content_marker')) {
                                $modified_content = $this->add_content_marker($message_content, 'page');
                                error_log('MPAI Chat: Added page marker to response');
                            } else {
                                error_log('MPAI Chat: add_content_marker method not available for page');
                            }
                        }
                        
                        // Check for membership content patterns
                        if (strpos(strtolower($message), 'membership') !== false && 
                            (strpos(strtolower($message_content), 'membership') !== false) &&
                            preg_match('/(?:title|name):\s*([^\n]+)/i', $message_content)) {
                                
                            // This looks like a membership
                            if (method_exists($this, 'add_content_marker')) {
                                $modified_content = $this->add_content_marker($message_content, 'membership');
                                error_log('MPAI Chat: Added membership marker to response');
                            } else {
                                error_log('MPAI Chat: add_content_marker method not available for membership');
                            }
                        }
                    } catch (Throwable $pattern_e) {
                        error_log('MPAI Chat: Error checking content patterns: ' . $pattern_e->getMessage());
                        // Continue without adding marker
                    }
                    
                    // Add assistant response to conversation
                    try {
                        error_log('MPAI Chat: Adding assistant response to conversation');
                        $this->conversation[] = array('role' => 'assistant', 'content' => $modified_content);
                        error_log('MPAI Chat: Added assistant response to conversation');
                    } catch (Throwable $conv_e) {
                        error_log('MPAI Chat: Error adding assistant response to conversation: ' . $conv_e->getMessage());
                        // Continue even if this fails
                    }
                    
                    // Save conversation to database
                    try {
                        error_log('MPAI Chat: Saving message to database');
                        $this->save_message($message, $modified_content);
                        error_log('MPAI Chat: Message saved to database');
                    } catch (Throwable $save_e) {
                        error_log('MPAI Chat: Error saving message to database: ' . $save_e->getMessage());
                        // Continue even if save fails
                    }
                    
                    if ($has_tool_calls) {
                        try {
                            error_log('MPAI Chat: Processing tool calls from structured response');
                            // Process tool calls from structure
                            $processed_response = $this->process_structured_tool_calls($message_content, $response['tool_calls']);
                            error_log('MPAI Chat: Tool calls processed successfully');
                        } catch (Throwable $tool_e) {
                            error_log('MPAI Chat: Error processing tool calls: ' . $tool_e->getMessage() . ' in ' . $tool_e->getFile() . ' on line ' . $tool_e->getLine());
                            error_log('MPAI Chat: ' . $tool_e->getTraceAsString());
                            $processed_response = $message_content;
                            // Continue with original content if processing fails
                        }
                    } else {
                        // Just process the message content
                        try {
                            error_log('MPAI Chat: Processing content for tool calls');
                            $processed_response = $this->process_tool_calls($message_content);
                            error_log('MPAI Chat: Processing content for commands');
                            $processed_response = $this->process_commands($processed_response);
                            error_log('MPAI Chat: Content processing completed successfully');
                        } catch (Throwable $proc_e) {
                            error_log('MPAI Chat: Error processing content: ' . $proc_e->getMessage());
                            $processed_response = $message_content;
                            // Continue with original content if processing fails
                        }
                    }
                    
                    error_log('MPAI Chat: Returning successful response');
                    return array(
                        'success' => true,
                        'message' => $processed_response,
                        'raw_response' => $message_content,
                        'api_used' => isset($response['api']) ? $response['api'] : 'unknown',
                    );
                } else {
                    error_log('MPAI Chat: Response is not an array with message field');
                }
            } catch (Throwable $arr_e) {
                error_log('MPAI Chat: Error processing array response: ' . $arr_e->getMessage());
                // Continue to next format check
            }
            
            // Handle simple string response
            try {
                error_log('MPAI Chat: Checking for string response');
                if (is_string($response)) {
                    error_log('MPAI Chat: Response is a string');
                    
                    // Add assistant response to conversation
                    try {
                        error_log('MPAI Chat: Adding string response to conversation');
                        $this->conversation[] = array('role' => 'assistant', 'content' => $response);
                        error_log('MPAI Chat: Added string response to conversation');
                    } catch (Throwable $str_conv_e) {
                        error_log('MPAI Chat: Error adding string response to conversation: ' . $str_conv_e->getMessage());
                        // Continue even if this fails
                    }
                    
                    // Save conversation to database
                    try {
                        error_log('MPAI Chat: Saving string message to database');
                        $this->save_message($message, $response);
                        error_log('MPAI Chat: String message saved to database');
                    } catch (Throwable $str_save_e) {
                        error_log('MPAI Chat: Error saving string message to database: ' . $str_save_e->getMessage());
                        // Continue even if save fails
                    }
                    
                    // Process any tool calls in the response
                    try {
                        error_log('MPAI Chat: Processing string response for tool calls');
                        $processed_response = $this->process_tool_calls($response);
                        error_log('MPAI Chat: Processing string response for commands');
                        $processed_response = $this->process_commands($processed_response);
                        error_log('MPAI Chat: String response processing completed');
                    } catch (Throwable $str_proc_e) {
                        error_log('MPAI Chat: Error processing string response: ' . $str_proc_e->getMessage());
                        $processed_response = $response;
                        // Continue with original response if processing fails
                    }
                    
                    error_log('MPAI Chat: Returning successful string response');
                    return array(
                        'success' => true,
                        'message' => $processed_response,
                        'raw_response' => $response,
                    );
                } else {
                    error_log('MPAI Chat: Response is not a string: ' . gettype($response));
                }
            } catch (Throwable $str_e) {
                error_log('MPAI Chat: Error processing string response: ' . $str_e->getMessage());
                // Continue to fallback
            }
            
            // Fallback for unrecognized response format
            error_log('MPAI Chat: Unrecognized response format, using fallback');
            return array(
                'success' => true,
                'message' => 'The assistant responded in an unexpected format. Please try rephrasing your request.',
                'raw_response' => (is_string($response) ? $response : (is_array($response) ? json_encode($response) : 'Unknown response format')),
            );
            
        } catch (Throwable $e) {
            error_log('MPAI Chat: CRITICAL ERROR in process_message: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            error_log('MPAI Chat: ' . $e->getTraceAsString());
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
                    error_log('MPAI: Unescaped plugin path for structured tool call: ' . $parameters['plugin']);
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
            
            // Clean up any escaped slashes in plugin paths
            if (isset($tool_call['parameters']) && isset($tool_call['parameters']['plugin'])) {
                $tool_call['parameters']['plugin'] = str_replace('\\/', '/', $tool_call['parameters']['plugin']);
                error_log('MPAI: Unescaped plugin path for tool call: ' . $tool_call['parameters']['plugin']);
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
            error_log('MPAI: No conversation history available');
            return null;
        }
        
        $messages_copy = $this->conversation;
        $messages_copy = array_reverse($messages_copy);
        
        foreach ($messages_copy as $message) {
            if (isset($message['role']) && $message['role'] === 'assistant' && 
                isset($message['content']) && !empty($message['content'])) {
                error_log('MPAI: Found latest assistant message with length ' . strlen($message['content']));
                return $message;
            }
        }
        
        error_log('MPAI: No assistant messages found in conversation history');
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
            error_log('MPAI: No conversation history available');
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
                    error_log('MPAI: Found previous assistant message with length ' . strlen($message['content']));
                    return $message;
                }
            }
        }
        
        // If only one assistant message was found, just return that
        if ($found_assistant_messages == 1) {
            error_log('MPAI: Only one assistant message found, returning it');
            return $this->get_latest_assistant_message();
        }
        
        error_log('MPAI: No previous assistant message found in conversation history');
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
            error_log('MPAI: No conversation history available');
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
                    error_log('MPAI: Found message with ' . $type . ' marker, length: ' . strlen($message['content']));
                    
                    // Create a copy of the message
                    $cleaned_message = $message;
                    
                    // Remove the marker from the content before returning
                    $cleaned_message['content'] = preg_replace($marker_pattern, '', $cleaned_message['content']);
                    
                    // Trim any extra whitespace that might be left
                    $cleaned_message['content'] = trim($cleaned_message['content']);
                    
                    error_log('MPAI: Cleaned marker from content, new length: ' . strlen($cleaned_message['content']));
                    return $cleaned_message;
                }
            }
        }
        
        error_log('MPAI: No message with ' . $type . ' marker found in conversation history');
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
                    error_log('MPAI Chat: Processing memberpress_info tool result');
                    $parsed = json_decode($result['result'], true);
                    
                    if (json_last_error() == JSON_ERROR_NONE) {
                        if (isset($parsed['result'])) {
                            // Return just the actual result data
                            error_log('MPAI Chat: Found standard result structure in memberpress_info tool');
                            return $parsed['result'];
                        } else if (isset($parsed['command_type']) && $parsed['command_type'] == 'system_info') {
                            // Handle system_info case specifically
                            error_log('MPAI Chat: Found system_info command type in memberpress_info tool');
                            
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
                    error_log('MPAI Chat: Error processing memberpress_info result: ' . $e->getMessage());
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
     * This function completely resets the conversation state and history,
     * including context and any cached values.
     *
     * @return bool Success status
     */
    public function reset_conversation() {
        global $wpdb;
        
        error_log('MPAI Chat: Starting complete conversation reset');
        
        try {
            // Reset internal conversation array first
            $this->conversation = array();
            error_log('MPAI Chat: Reset internal conversation array');
            
            // Reset any cached system prompt
            if (isset($this->system_prompt)) {
                unset($this->system_prompt);
                error_log('MPAI Chat: Cleared cached system prompt');
            }
            
            // Get current user ID
            $user_id = get_current_user_id();
            if (empty($user_id)) {
                error_log('MPAI Chat: No user ID available for conversation reset');
                return false;
            }
            
            // Get conversation ID
            $conversation_id = $this->get_current_conversation_id();
            if (empty($conversation_id)) {
                error_log('MPAI Chat: No conversation ID available for reset');
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
            error_log('MPAI Chat: Deleted ' . ($deleted !== false ? $deleted : '0') . ' messages from database');
            
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
            error_log('MPAI Chat: Updated conversation with new ID: ' . $new_conversation_id . ', result: ' . ($updated !== false ? $updated : 'failed'));
            
            // Clear any cached context in the context manager
            if (isset($this->context_manager) && is_object($this->context_manager)) {
                if (method_exists($this->context_manager, 'reset_context')) {
                    $this->context_manager->reset_context();
                    error_log('MPAI Chat: Reset context manager');
                } else {
                    error_log('MPAI Chat: Context manager does not have reset_context method');
                }
            } else {
                error_log('MPAI Chat: Context manager not available for reset');
            }
            
            // If we have an API router, try to reset its state too
            if (isset($this->api_router) && is_object($this->api_router)) {
                if (method_exists($this->api_router, 'reset_state')) {
                    $this->api_router->reset_state();
                    error_log('MPAI Chat: Reset API router state');
                } else {
                    error_log('MPAI Chat: API router does not have reset_state method');
                }
            } else {
                error_log('MPAI Chat: API router not available for reset');
            }
            
            // Initialize a new conversation with a system prompt
            // This ensures we start with a clean state for next messages
            try {
                $system_prompt = $this->get_system_prompt();
                $this->conversation = array(
                    array('role' => 'system', 'content' => $system_prompt)
                );
                error_log('MPAI Chat: Initialized new conversation with fresh system prompt');
            } catch (Throwable $e) {
                error_log('MPAI Chat: Error initializing new conversation: ' . $e->getMessage());
                // If reinitializing fails, at least leave with an empty conversation
                $this->conversation = array();
            }
            
            error_log('MPAI Chat: Conversation reset completed successfully');
            return true;
            
        } catch (Throwable $e) {
            error_log('MPAI Chat: Error in reset_conversation: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            error_log('MPAI Chat: ' . $e->getTraceAsString());
            
            // Try to at least reset the internal state
            $this->conversation = array();
            
            return false;
        }
    }
}