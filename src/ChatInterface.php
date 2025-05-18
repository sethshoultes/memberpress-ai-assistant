<?php
/**
 * Chat Interface Handler
 *
 * Handles the registration, enqueuing, and rendering of the chat interface.
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant;

/**
 * Class ChatInterface
 *
 * Manages the chat interface functionality.
 */
class ChatInterface {
    /**
     * Instance of this class
     *
     * @var ChatInterface
     */
    private static $instance = null;

    /**
     * Get the singleton instance of this class
     *
     * @return ChatInterface
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Register hooks
        add_action('wp_enqueue_scripts', [$this, 'registerAssets']);
        add_action('admin_enqueue_scripts', [$this, 'registerAdminAssets']);
        add_action('wp_footer', [$this, 'renderChatInterface']);
        add_action('admin_footer', [$this, 'renderAdminChatInterface']);
        
        // Register REST API endpoints
        add_action('rest_api_init', [$this, 'registerRestRoutes']);
    }

    /**
     * Register chat interface assets for frontend
     */
    public function registerAssets() {
        // Only load on appropriate pages
        if (!$this->shouldLoadChatInterface()) {
            return;
        }

        // Register styles
        wp_register_style(
            'mpai-chat',
            MPAI_PLUGIN_URL . 'assets/css/chat.css',
            [],
            MPAI_VERSION
        );

        // Register scripts
        // Register response formatting modules first
        wp_register_script(
            'mpai-xml-processor',
            MPAI_PLUGIN_URL . 'assets/js/xml-processor.js',
            [],
            MPAI_VERSION,
            true
        );
        
        wp_register_script(
            'mpai-data-handler',
            MPAI_PLUGIN_URL . 'assets/js/data-handler-minimal.js',
            [],
            MPAI_VERSION,
            true
        );
        
        wp_register_script(
            'mpai-text-formatter',
            MPAI_PLUGIN_URL . 'assets/js/text-formatter.js',
            [],
            MPAI_VERSION,
            true
        );
        
        // Register main chat script with dependencies
        wp_register_script(
            'mpai-chat',
            MPAI_PLUGIN_URL . 'assets/js/chat.js',
            ['mpai-xml-processor', 'mpai-data-handler', 'mpai-text-formatter'],
            MPAI_VERSION,
            true
        );

        // Enqueue assets
        wp_enqueue_style('mpai-chat');
        wp_enqueue_script('mpai-xml-processor');
        wp_enqueue_script('mpai-data-handler');
        wp_enqueue_script('mpai-text-formatter');
        wp_enqueue_script('mpai-chat');
        
        // Add WordPress dashicons for icons
        wp_enqueue_style('dashicons');

        // Localize script with configuration
        wp_localize_script('mpai-chat', 'mpai_chat_config', $this->getChatConfig());
        
        // Add REST API nonce
        wp_localize_script('mpai-chat', 'mpai_nonce', wp_create_nonce('wp_rest'));
    }

    /**
     * Register chat interface assets for admin
     *
     * @param string $hook_suffix The current admin page
     */
    public function registerAdminAssets($hook_suffix) {
        // Only load on appropriate admin pages
        if (!$this->shouldLoadAdminChatInterface($hook_suffix)) {
            return;
        }

        // Register styles
        wp_register_style(
            'mpai-chat-admin',
            MPAI_PLUGIN_URL . 'assets/css/chat.css',
            [],
            MPAI_VERSION
        );

        // Register scripts
        // Register response formatting modules first
        wp_register_script(
            'mpai-xml-processor-admin',
            MPAI_PLUGIN_URL . 'assets/js/xml-processor.js',
            [],
            MPAI_VERSION,
            true
        );
        
        wp_register_script(
            'mpai-data-handler-admin',
            MPAI_PLUGIN_URL . 'assets/js/data-handler-minimal.js',
            [],
            MPAI_VERSION,
            true
        );
        
        wp_register_script(
            'mpai-text-formatter-admin',
            MPAI_PLUGIN_URL . 'assets/js/text-formatter.js',
            [],
            MPAI_VERSION,
            true
        );
        
        // Register main chat script with dependencies
        wp_register_script(
            'mpai-chat-admin',
            MPAI_PLUGIN_URL . 'assets/js/chat.js',
            ['mpai-xml-processor-admin', 'mpai-data-handler-admin', 'mpai-text-formatter-admin'],
            MPAI_VERSION,
            true
        );

        // Enqueue assets
        wp_enqueue_style('mpai-chat-admin');
        wp_enqueue_script('mpai-xml-processor-admin');
        wp_enqueue_script('mpai-data-handler-admin');
        wp_enqueue_script('mpai-text-formatter-admin');
        wp_enqueue_script('mpai-chat-admin');

        // Localize script with configuration
        wp_localize_script('mpai-chat-admin', 'mpai_chat_config', $this->getChatConfig(true));
        
        // Add REST API nonce
        wp_localize_script('mpai-chat-admin', 'mpai_nonce', wp_create_nonce('wp_rest'));
    }

    /**
     * Render the chat interface on frontend
     */
    public function renderChatInterface() {
        // Only render on appropriate pages
        if (!$this->shouldLoadChatInterface()) {
            return;
        }

        // Include the chat interface template
        $this->includeChatTemplate();
    }

    /**
     * Render the chat interface on admin pages
     */
    public function renderAdminChatInterface() {
        // Only render on appropriate admin pages
        if (!$this->shouldLoadAdminChatInterface(get_current_screen()->id)) {
            return;
        }

        // Include the chat interface template
        $this->includeChatTemplate();
    }

    /**
     * Include the chat interface template
     */
    private function includeChatTemplate() {
        $template_path = MPAI_PLUGIN_DIR . 'templates/chat-interface.php';
        
        if (file_exists($template_path)) {
            include $template_path;
        }
    }

    /**
     * Register REST API routes for the chat interface
     */
    public function registerRestRoutes() {
        register_rest_route('memberpress-ai/v1', '/chat', [
            'methods' => 'POST',
            'callback' => [$this, 'processChatRequest'],
            'permission_callback' => [$this, 'checkChatPermissions'],
            'args' => [
                'message' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'conversation_id' => [
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);
    }

    /**
     * Process a chat request from the REST API
     *
     * @param \WP_REST_Request $request The REST request
     * @return \WP_REST_Response The REST response
     */
    public function processChatRequest($request) {
        // Get request parameters
        $message = $request->get_param('message');
        $conversation_id = $request->get_param('conversation_id');
        $load_history = (bool)$request->get_param('load_history');
        $clear_history = (bool)$request->get_param('clear_history');
        $user_logged_in = (bool)$request->get_param('user_logged_in');
        
        // Get the current user ID if logged in
        $user_id = is_user_logged_in() ? get_current_user_id() : 0;

        // Enable error reporting for debugging
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        try {
            // Log the request for debugging
            error_log('MPAI Debug - Chat request received: ' . $message);
            
            // Handle clear history request
            if ($clear_history && !empty($conversation_id) && $user_id > 0) {
                error_log('MPAI Debug - Clearing history for conversation: ' . $conversation_id);
                $this->clearUserConversationHistory($user_id, $conversation_id);
                
                // Return success response
                return rest_ensure_response([
                    'status' => 'success',
                    'message' => 'History cleared successfully',
                    'conversation_id' => null,
                    'timestamp' => time()
                ]);
            }
            
            // For logged-in users, try to get their conversation ID from user metadata
            if ($user_id > 0 && empty($conversation_id)) {
                $saved_conversation_id = $this->getUserConversationId($user_id);
                if ($saved_conversation_id) {
                    $conversation_id = $saved_conversation_id;
                    error_log('MPAI Debug - Using saved conversation ID for user: ' . $conversation_id);
                }
            }
            
            // Get the service locator
            global $mpai_service_locator;
            
            // Log service locator status
            error_log('MPAI Debug - Service locator available: ' . (isset($mpai_service_locator) ? 'Yes' : 'No'));
            
            if (!isset($mpai_service_locator)) {
                throw new \Exception('Service locator not available');
            }
            
            // Load context if conversation_id is provided
            if (!empty($conversation_id) && $mpai_service_locator->has('agent_orchestrator')) {
                $orchestrator = $mpai_service_locator->get('agent_orchestrator');
                $contextManager = $orchestrator->getContextManager();
                
                // Try to load context for this conversation
                $contextManager->loadContext('conversation_' . $conversation_id);
                error_log('MPAI Debug - Loaded context for conversation: ' . $conversation_id);
                
                // If this is just a history load request, return the history without processing a message
                if ($load_history) {
                    error_log('MPAI Debug - Processing history load request for conversation: ' . $conversation_id);
                    
                    $history = [];
                    $rawHistory = $contextManager->getConversationHistory($conversation_id);
                    
                    error_log('MPAI Debug - Raw history: ' . ($rawHistory ? 'Available' : 'Not available') .
                              ($rawHistory ? ' (' . count($rawHistory) . ' items)' : ''));
                    
                    if (is_array($rawHistory)) {
                        foreach ($rawHistory as $historyItem) {
                            if (isset($historyItem['sender'], $historyItem['content'])) {
                                $history[] = [
                                    'role' => $historyItem['sender'] === 'user' ? 'user' : 'assistant',
                                    'content' => $historyItem['content'],
                                    'timestamp' => $historyItem['timestamp'] ?? time()
                                ];
                            }
                        }
                        
                        error_log('MPAI Debug - Processed history: ' . count($history) . ' items');
                        error_log('MPAI Debug - First history item structure: ' . json_encode(array_keys(reset($rawHistory) ?: [])));
                    } else {
                        error_log('MPAI Debug - No raw history available to process');
                    }
                    
                    $response = [
                        'status' => 'success',
                        'message' => '',
                        'conversation_id' => $conversation_id,
                        'timestamp' => time(),
                        'history' => $history
                    ];
                    
                    error_log('MPAI Debug - Returning history response with ' . count($history) . ' items');
                    return rest_ensure_response($response);
                }
            }
            
            // Check if we should use the agent orchestrator directly
            $useAgentOrchestrator = false;
            
            // WordPress-specific keywords that should use the agent orchestrator directly
            $wpKeywords = ['plugin', 'wordpress', 'theme', 'wp-', 'admin', 'dashboard', 'memberpress'];
            
            // Check if the message contains WordPress-specific keywords
            foreach ($wpKeywords as $keyword) {
                if (stripos($message, $keyword) !== false) {
                    $useAgentOrchestrator = true;
                    error_log('MPAI Debug - WordPress keyword detected: ' . $keyword . '. Using agent orchestrator directly.');
                    break;
                }
            }
            
            // Try to use the LLM services first
            if ($mpai_service_locator->has('llm.chat_adapter') && !$useAgentOrchestrator) {
                try {
                    // Get the LLM chat adapter
                    $chatAdapter = $mpai_service_locator->get('llm.chat_adapter');
                    
                    // Process the request with the LLM chat adapter
                    error_log('MPAI Debug - Processing request with LLM chat adapter');
                    $response = $chatAdapter->processRequest($message, $conversation_id);
                    
                    // Check if the response contains an error message
                    if (isset($response['status']) && $response['status'] === 'error') {
                        error_log('MPAI Debug - LLM chat adapter returned error: ' . ($response['debug_message'] ?? 'Unknown error'));
                        throw new \Exception('LLM chat adapter error: ' . ($response['debug_message'] ?? 'Unknown error'));
                    }
                    
                    error_log('MPAI Debug - LLM chat adapter response: ' . json_encode($response));
                    
                    // Get the conversation ID from the response or use the existing one
                    $conversation_id = $response['conversation_id'] ?? $conversation_id;
                    
                    // Save conversation ID for logged-in users
                    if ($user_id > 0 && !empty($conversation_id)) {
                        $this->saveUserConversationId($user_id, $conversation_id);
                        error_log('MPAI Debug - Saved conversation ID for user: ' . $conversation_id);
                    }
                    
                    // Get conversation history
                    $history = [];
                    if (!empty($conversation_id) && $mpai_service_locator->has('agent_orchestrator')) {
                        $orchestrator = $mpai_service_locator->get('agent_orchestrator');
                        $contextManager = $orchestrator->getContextManager();
                        
                        // Get conversation history
                        $rawHistory = $contextManager->getConversationHistory($conversation_id);
                        if (is_array($rawHistory)) {
                            foreach ($rawHistory as $historyItem) {
                                if (isset($historyItem['sender'], $historyItem['content'])) {
                                    $history[] = [
                                        'role' => $historyItem['sender'] === 'user' ? 'user' : 'assistant',
                                        'content' => $historyItem['content'],
                                        'timestamp' => $historyItem['timestamp'] ?? time()
                                    ];
                                }
                            }
                            
                            error_log('MPAI Debug - Processed history items: ' . count($history));
                        }
                        
                        // Persist context after processing
                        $contextManager->persistContext('conversation_' . $conversation_id);
                        error_log('MPAI Debug - Persisted context for conversation: ' . $conversation_id);
                        error_log('MPAI Debug - Transient key that will be used: mpai_' . $conversation_id);
                    }
                    
                    // Format plugin list as a table if this is a plugin list response
                    if (isset($response['data']) && isset($response['data']['plugins']) && is_array($response['data']['plugins'])) {
                        $response['message'] = $this->formatPluginListAsTable($response['data']['plugins'], $response['data']);
                    }
                    
                    // Add history to the response
                    $response['history'] = $history;
                    
                    // Return the response
                    return rest_ensure_response($response);
                } catch (\Exception $e) {
                    // Log the error
                    error_log('MPAI Debug - Error using LLM chat adapter: ' . $e->getMessage());
                    error_log('MPAI Debug - Falling back to agent orchestrator');
                    
                    // Fall back to the agent orchestrator
                    $useAgentOrchestrator = true;
                }
            }
            
            // Fall back to the agent orchestrator
            if ($useAgentOrchestrator || $mpai_service_locator->has('agent_orchestrator')) {
                // Get the orchestrator service
                $orchestrator = $mpai_service_locator->get('agent_orchestrator');
                
                // Process the request
                $request_data = [
                    'message' => $message,
                ];
                
                // Use the orchestrator to process the request
                error_log('MPAI Debug - Processing request with orchestrator');
                $response = $orchestrator->processUserRequest($request_data, $conversation_id);
                error_log('MPAI Debug - Orchestrator response: ' . json_encode($response));
                
                // Get the conversation ID from the response or use the existing one
                $conversation_id = $response['conversation_id'] ?? $conversation_id;
                
                // Save conversation ID for logged-in users
                if ($user_id > 0 && !empty($conversation_id)) {
                    $this->saveUserConversationId($user_id, $conversation_id);
                    error_log('MPAI Debug - Saved conversation ID for user: ' . $conversation_id);
                }
                
                // Get conversation history
                $history = [];
                if (!empty($conversation_id)) {
                    $contextManager = $orchestrator->getContextManager();
                    
                    // Get conversation history
                    $rawHistory = $contextManager->getConversationHistory($conversation_id);
                    if (is_array($rawHistory)) {
                        foreach ($rawHistory as $historyItem) {
                            if (isset($historyItem['sender'], $historyItem['content'])) {
                                $history[] = [
                                    'role' => $historyItem['sender'] === 'user' ? 'user' : 'assistant',
                                    'content' => $historyItem['content'],
                                    'timestamp' => $historyItem['timestamp'] ?? time()
                                ];
                            }
                        }
                        
                        error_log('MPAI Debug - Processed history items: ' . count($history));
                    }
                    
                    // Persist context after processing
                    $contextManager->persistContext('conversation_' . $conversation_id);
                    error_log('MPAI Debug - Persisted context for conversation: ' . $conversation_id);
                    error_log('MPAI Debug - Transient key that will be used: mpai_' . $conversation_id);
                }
                
                // Format plugin list as a table if this is a plugin list response
                $message = $response['message'] ?? $response['content'] ?? 'No response message';
                
                // Check if this is a plugin list response
                if (isset($response['data']) && isset($response['data']['plugins']) && is_array($response['data']['plugins'])) {
                    $message = $this->formatPluginListAsTable($response['data']['plugins'], $response['data']);
                }
                
                // Return the response with history
                return rest_ensure_response([
                    'status' => 'success',
                    'message' => $message,
                    'conversation_id' => $conversation_id,
                    'timestamp' => time(),
                    'history' => $history
                ]);
            } else {
                // Fallback to test response if no services are available
                error_log('MPAI Debug - No chat services available, using fallback response');
                $response = [
                    'status' => 'success',
                    'message' => 'This is a test response from the chat interface. Your message was: ' . $message,
                    'conversation_id' => $conversation_id ?: 'new_conversation_' . time(),
                    'timestamp' => time(),
                ];
                
                return rest_ensure_response($response);
            }
        } catch (\Exception $e) {
            // Log the error
            if (function_exists('error_log')) {
                error_log('MPAI Chat Error: ' . $e->getMessage());
            }

            // Return error response
            return new \WP_Error(
                'mpai_chat_error',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    /**
     * Check if the user has permission to use the chat
     *
     * @param \WP_REST_Request $request The REST request
     * @return bool|WP_Error True if the user has permission, WP_Error otherwise
     */
    public function checkChatPermissions($request) {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return new \WP_Error(
                'mpai_not_logged_in',
                __('You must be logged in to use the chat.', 'memberpress-ai-assistant'),
                ['status' => 401]
            );
        }

        // Check if user has access to MemberPress content
        // This can be customized based on your specific requirements
        if (function_exists('current_user_can') && !current_user_can('read')) {
            return new \WP_Error(
                'mpai_insufficient_permissions',
                __('You do not have permission to use the chat.', 'memberpress-ai-assistant'),
                ['status' => 403]
            );
        }

        return true;
    }

    /**
     * Check if the chat interface should be loaded on the current page
     *
     * @return bool True if the chat interface should be loaded
     */
    private function shouldLoadChatInterface() {
        // Don't load in admin
        if (is_admin()) {
            return false;
        }

        // Don't load in REST API requests
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return false;
        }

        // Don't load in AJAX requests
        if (wp_doing_ajax()) {
            return false;
        }

        // For testing purposes, always return true
        return true;
    }

    /**
     * Check if the chat interface should be loaded on the current admin page
     *
     * @param string $hook_suffix The current admin page hook suffix
     * @return bool True if the chat interface should be loaded
     */
    private function shouldLoadAdminChatInterface($hook_suffix) {
        // For testing purposes, always return true
        return true;
    }

    /**
     * Get the chat configuration
     *
     * @param bool $is_admin Whether the chat is being loaded in admin
     * @return array The chat configuration
     */
    private function getChatConfig($is_admin = false) {
        $config = [
            'apiEndpoint' => rest_url('memberpress-ai/v1/chat'),
            'debug' => defined('WP_DEBUG') && WP_DEBUG,
            'maxMessages' => 50,
            'autoOpen' => false,
        ];
        
        // Add user login status to the config
        wp_localize_script('mpai-chat', 'mpai_user_logged_in', is_user_logged_in());
        
        // If user is logged in, get their conversation ID
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            $conversation_id = $this->getUserConversationId($user_id);
            if ($conversation_id) {
                $config['conversationId'] = $conversation_id;
            }
        }

        // Allow filtering
        return apply_filters('mpai_chat_config', $config, $is_admin);
    }
    
    /**
     * Get the user's conversation ID from user metadata
     *
     * @param int $user_id The user ID
     * @return string|null The conversation ID or null if not found
     */
    private function getUserConversationId($user_id) {
        return get_user_meta($user_id, 'mpai_conversation_id', true);
    }
    
    /**
     * Save the user's conversation ID to user metadata
     *
     * @param int $user_id The user ID
     * @param string $conversation_id The conversation ID
     * @return bool True if successful, false otherwise
     */
    private function saveUserConversationId($user_id, $conversation_id) {
        return update_user_meta($user_id, 'mpai_conversation_id', $conversation_id);
    }
    
    /**
     * Clear the user's conversation history
     *
     * @param int $user_id The user ID
     * @param string $conversation_id The conversation ID to clear
     * @return bool True if successful, false otherwise
     */
    private function clearUserConversationHistory($user_id, $conversation_id) {
        // Delete the user's conversation ID from metadata
        delete_user_meta($user_id, 'mpai_conversation_id');
        
        // If we have a context manager, clear the conversation context
        global $mpai_service_locator;
        if (isset($mpai_service_locator) && $mpai_service_locator->has('agent_orchestrator')) {
            $orchestrator = $mpai_service_locator->get('agent_orchestrator');
            $contextManager = $orchestrator->getContextManager();
            
            // Clear the conversation context
            return $contextManager->clearConversationContext($conversation_id);
        }
        
        return true;
    }
    
    /**
     * Format plugin list as a nice-looking table
     *
     * @param array $plugins List of plugins
     * @param array $summary Summary data (total, active, inactive, update_available)
     * @return string Formatted table
     */
    private function formatPluginListAsTable(array $plugins, array $summary): string {
        // Start with a header
        $output = "# Installed WordPress Plugins\n\n";
        
        // Add summary information
        $output .= "**Total Plugins:** {$summary['total']}  \n";
        $output .= "**Active:** {$summary['active']}  \n";
        $output .= "**Inactive:** {$summary['inactive']}  \n";
        $output .= "**Updates Available:** {$summary['update_available']}  \n\n";
        
        // Create table header
        $output .= "| Name | Status | Version | Update Available |\n";
        $output .= "|------|--------|---------|------------------|\n";
        
        // Add each plugin to the table
        foreach ($plugins as $plugin) {
            $name = $plugin['name'];
            $status = ucfirst($plugin['status']);
            $version = $plugin['version'];
            
            // Format update information
            $update = $plugin['update_available']
                ? "Yes (v{$plugin['new_version']})"
                : "No";
            
            // Add row to table
            $output .= "| $name | $status | $version | $update |\n";
        }
        
        return $output;
    }
}