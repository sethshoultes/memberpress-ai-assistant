<?php
/**
 * MemberPress AI Assistant AJAX Handler
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Admin;

use MemberpressAiAssistant\Abstracts\AbstractService;

/**
 * Class for handling AJAX requests for the MemberPress AI Assistant
 */
class MPAIAjaxHandler extends AbstractService {
    /**
     * Constructor
     *
     * @param string $name Service name
     * @param mixed $logger Logger instance
     */
    public function __construct(string $name = 'ajax_handler', $logger = null) {
        parent::__construct($name, $logger);
    }

    /**
     * {@inheritdoc}
     */
    public function register($serviceLocator): void {
        // Register this service with the service locator
        $serviceLocator->register('ajax_handler', function() {
            return $this;
        });
        
        // Log registration
        $this->log('AJAX handler service registered');
    }

    /**
     * {@inheritdoc}
     */
    public function boot(): void {
        parent::boot();
        
        // Add hooks
        $this->addHooks();
        
        // Log boot
        $this->log('AJAX handler service booted');
    }

    /**
     * Add hooks and filters for this service
     *
     * @return void
     */
    protected function addHooks(): void {
        // Add AJAX handlers
        add_action('wp_ajax_mpai_test_api_connection', [$this, 'handle_test_api_connection']);
        
        // Add AJAX handler for processing chat messages
        add_action('wp_ajax_mpai_process_chat', [$this, 'handle_process_chat']);
        
        // Add AJAX handler for chat requests (new modular system)
        add_action('wp_ajax_mpai_chat_request', [$this, 'handle_chat_request']);
        
        
        // Add AJAX handler for getting chat interface HTML
        add_action('wp_ajax_mpai_get_chat_interface', [$this, 'handle_get_chat_interface']);
    }
    
    
    /**
     * Handle AJAX request to get chat interface HTML
     *
     * @return void
     */
    public function handle_get_chat_interface(): void {
        $this->log('Processing request for chat interface HTML');
        
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mpai_ajax_nonce')) {
            $this->log('Chat interface request nonce verification failed', ['error' => true]);
            wp_send_json_error(['message' => __('Security check failed.', 'memberpress-ai-assistant')]);
            return;
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            $this->log('Chat interface request from non-logged-in user', ['error' => true]);
            wp_send_json_error(['message' => __('You must be logged in to access the chat interface.', 'memberpress-ai-assistant')]);
            return;
        }
        
        try {
            // Get ChatInterface instance to ensure assets are properly configured
            $chat_interface = \MemberpressAiAssistant\ChatInterface::getInstance();
            
            // Start output buffering to capture the template output
            ob_start();
            
            // Include the chat interface template with proper context
            $chat_template_path = MPAI_PLUGIN_DIR . 'templates/chat-interface-ajax.php';
            
            // Fallback to regular template if AJAX-specific template doesn't exist
            if (!file_exists($chat_template_path)) {
                $chat_template_path = MPAI_PLUGIN_DIR . 'templates/chat-interface.php';
            }
            
            if (file_exists($chat_template_path)) {

            
                // DIAGNOSTIC: Log template inclusion process

            
                $this->log('[CHAT RENDER DIAGNOSIS] Including chat interface template', [

            
                    'template_path' => $chat_template_path,

            
                    'file_exists' => true,

            
                    'is_readable' => is_readable($chat_template_path),

            
                    'file_size' => filesize($chat_template_path),

            
                    'output_buffer_level_before' => ob_get_level()

            
                ]);

            
                

            
                // Set up template variables for AJAX context
                $is_ajax_context = true;
                $chat_config = $this->getChatConfigForAjax();
                
                include $chat_template_path;
                $html = ob_get_clean();
                
                $this->log('Chat interface HTML generated successfully');
                
                // Get required CSS and JS assets
                $assets = $this->getChatInterfaceAssets();
                
                // Return the HTML with assets
                wp_send_json_success([
                    'html' => $html,
                    'assets' => $assets,
                    'config' => $chat_config,
                    'message' => __('Chat interface loaded successfully.', 'memberpress-ai-assistant')
                ]);
            } else {
                ob_end_clean();
                $this->log('Chat interface template not found: ' . $chat_template_path, ['error' => true]);
                wp_send_json_error(['message' => __('Chat interface template not found.', 'memberpress-ai-assistant')]);
            }
        } catch (\Exception $e) {
            // Clean up output buffer in case of error
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            $this->log('Exception while generating chat interface HTML: ' . $e->getMessage(), ['error' => true]);
            wp_send_json_error(['message' => __('An error occurred while loading the chat interface.', 'memberpress-ai-assistant')]);
        }
    }
    
    /**
     * Get chat configuration for AJAX context
     *
     * @return array
     */
    private function getChatConfigForAjax(): array {
        $chat_interface = \MemberpressAiAssistant\ChatInterface::getInstance();
        
        // Use reflection to access private method
        $reflection = new \ReflectionClass($chat_interface);
        $method = $reflection->getMethod('getChatConfig');
        $method->setAccessible(true);
        
        return $method->invoke($chat_interface, true); // true for admin context
    }
    
    /**
     * Get required assets for chat interface
     *
     * @return array
     */
    private function getChatInterfaceAssets(): array {
        return [
            'css' => [
                'mpai-chat' => MPAI_PLUGIN_URL . 'assets/css/chat.css?v=' . MPAI_VERSION,
                'mpai-blog-post' => MPAI_PLUGIN_URL . 'assets/css/blog-post.css?v=' . MPAI_VERSION,
                'mpai-table-styles' => MPAI_PLUGIN_URL . 'assets/css/mpai-table-styles.css?v=' . MPAI_VERSION,
                'dashicons' => includes_url('css/dashicons.min.css')
            ],
            'js' => [
                'jquery' => includes_url('js/jquery/jquery.min.js'),
                'mpai-xml-processor' => MPAI_PLUGIN_URL . 'assets/js/xml-processor.js?v=' . MPAI_VERSION,
                'mpai-data-handler' => MPAI_PLUGIN_URL . 'assets/js/data-handler-minimal.js?v=' . MPAI_VERSION,
                'mpai-text-formatter' => MPAI_PLUGIN_URL . 'assets/js/text-formatter.js?v=' . MPAI_VERSION,
                'mpai-blog-formatter' => MPAI_PLUGIN_URL . 'assets/js/blog-formatter.js?v=' . MPAI_VERSION,
                'mpai-chat' => MPAI_PLUGIN_URL . 'assets/js/chat.js?v=' . MPAI_VERSION
            ]
        ];
    }
    
    /**
     * Handle AJAX request to process chat messages
     *
     * @return void
     */
    public function handle_process_chat(): void {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mpai_chat_nonce')) {
            wp_send_json_error(['message' => __('Security check failed.', 'memberpress-ai-assistant')]);
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in to use the chat.', 'memberpress-ai-assistant')]);
        }
        
        
        // Get message
        $message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
        
        if (empty($message)) {
            wp_send_json_error(['message' => __('Message cannot be empty.', 'memberpress-ai-assistant')]);
        }
        
        // Process the message (delegate to ChatInterface)
        global $mpai_service_locator;
        if (!isset($mpai_service_locator) || !$mpai_service_locator->has('chat_interface')) {
            wp_send_json_error(['message' => __('Chat interface not available.', 'memberpress-ai-assistant')]);
        }
        
        $chat_interface = $mpai_service_locator->get('chat_interface');
        
        // Create a mock request object
        $request = new \stdClass();
        $request->get_param = function($param) use ($message) {
            if ($param === 'message') {
                return $message;
            }
            return null;
        };
        
        // Process the request
        $response = $chat_interface->processChatRequest($request);
        
        // Send the response
        if (is_wp_error($response)) {
            wp_send_json_error(['message' => $response->get_error_message()]);
        } else {
            wp_send_json_success($response->get_data());
        }
    }
    
    /**
     * Handle AJAX request for chat messages (new modular system)
     *
     * @return void
     */
    public function handle_chat_request(): void {
        $this->log('Processing chat request via new modular system');
        
        // Check nonce if available
        if (isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'mpai_ajax_nonce')) {
            $this->log('Chat request nonce verification failed', ['error' => true]);
            wp_send_json_error(['message' => __('Security check failed.', 'memberpress-ai-assistant')]);
            return;
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            $this->log('Chat request from non-logged-in user', ['error' => true]);
            wp_send_json_error(['message' => __('You must be logged in to use the chat.', 'memberpress-ai-assistant')]);
            return;
        }
        
        // Get the endpoint and data
        $endpoint = isset($_POST['endpoint']) ? sanitize_text_field($_POST['endpoint']) : '';
        $data_json = isset($_POST['data']) ? $_POST['data'] : '{}';
        
        // Parse the data
        $data = json_decode($data_json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->log('Invalid JSON data in chat request', ['error' => true]);
            wp_send_json_error(['message' => __('Invalid request data.', 'memberpress-ai-assistant')]);
            return;
        }
        
        // Get message from data
        $message = isset($data['message']) ? sanitize_text_field($data['message']) : '';
        
        if (empty($message)) {
            $this->log('Empty message in chat request', ['error' => true]);
            wp_send_json_error(['message' => __('Message cannot be empty.', 'memberpress-ai-assistant')]);
            return;
        }
        
        $this->log('Processing chat message: ' . substr($message, 0, 50) . '...');
        
        // Process the message (delegate to ChatInterface)
        global $mpai_service_locator;
        if (!isset($mpai_service_locator) || !$mpai_service_locator->has('chat_interface')) {
            $this->log('Chat interface service not available', ['error' => true]);
            wp_send_json_error(['message' => __('Chat interface not available.', 'memberpress-ai-assistant')]);
            return;
        }
        
        $chat_interface = $mpai_service_locator->get('chat_interface');
        
        // Create a mock request object
        $request = new \stdClass();
        $request->get_param = function($param) use ($data) {
            return isset($data[$param]) ? $data[$param] : null;
        };
        
        try {
            // Process the request
            $response = $chat_interface->processChatRequest($request);
            
            // Send the response
            if (is_wp_error($response)) {
                $this->log('Chat processing error: ' . $response->get_error_message(), ['error' => true]);
                wp_send_json_error(['message' => $response->get_error_message()]);
            } else {
                $this->log('Chat request processed successfully');
                wp_send_json_success($response->get_data());
            }
        } catch (\Exception $e) {
            $this->log('Exception in chat processing: ' . $e->getMessage(), ['error' => true]);
            wp_send_json_error(['message' => __('An error occurred while processing your message.', 'memberpress-ai-assistant')]);
        }
    }
    
    /**
     * Handle AJAX request to test API connection
     *
     * @return void
     */
    public function handle_test_api_connection(): void {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mpai_settings_nonce')) {
            wp_send_json_error(['message' => __('Security check failed.', 'memberpress-ai-assistant')]);
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'memberpress-ai-assistant')]);
        }
        
        // Get provider and API key
        $provider = isset($_POST['provider']) ? sanitize_text_field($_POST['provider']) : '';
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        
        if (empty($provider) || empty($api_key)) {
            wp_send_json_error(['message' => __('Provider or API key is missing.', 'memberpress-ai-assistant')]);
        }
        
        // Get key manager from service locator
        global $mpai_service_locator;
        if (!isset($mpai_service_locator) || !$mpai_service_locator->has('key_manager')) {
            wp_send_json_error(['message' => __('Key manager not available.', 'memberpress-ai-assistant')]);
        }
        
        $key_manager = $mpai_service_locator->get('key_manager');
        
        // Temporarily set the API key for testing
        add_filter('mpai_override_api_key_' . $provider, function() use ($api_key) {
            return $api_key;
        });
        
        // Test the connection
        $result = $key_manager->test_api_connection($provider);
        
        // Send the result
        if ($result['success']) {
            wp_send_json_success(['message' => $result['message']]);
        } else {
            wp_send_json_error(['message' => $result['message']]);
        }
    }
}