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

        // Enable error reporting for debugging
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        try {
            // Log the request for debugging
            error_log('MPAI Debug - Chat request received: ' . $message);
            
            // Get the service locator
            global $mpai_service_locator;
            
            // Log service locator status
            error_log('MPAI Debug - Service locator available: ' . (isset($mpai_service_locator) ? 'Yes' : 'No'));
            
            if (!isset($mpai_service_locator)) {
                throw new \Exception('Service locator not available');
            }
            
            // Try to use the LLM services first
            if ($mpai_service_locator->has('llm.chat_adapter')) {
                // Get the LLM chat adapter
                $chatAdapter = $mpai_service_locator->get('llm.chat_adapter');
                
                // Process the request with the LLM chat adapter
                error_log('MPAI Debug - Processing request with LLM chat adapter');
                $response = $chatAdapter->processRequest($message, $conversation_id);
                error_log('MPAI Debug - LLM chat adapter response: ' . json_encode($response));
                
                // Return the response
                return rest_ensure_response($response);
            }
            // Fall back to the agent orchestrator if LLM services are not available
            else if ($mpai_service_locator->has('agent_orchestrator')) {
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
                
                // Return the response
                return rest_ensure_response([
                    'status' => 'success',
                    'message' => $response['message'] ?? $response['content'] ?? 'No response message',
                    'conversation_id' => $response['conversation_id'] ?? $conversation_id,
                    'timestamp' => time(),
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

        // Allow filtering
        return apply_filters('mpai_chat_config', $config, $is_admin);
    }
}