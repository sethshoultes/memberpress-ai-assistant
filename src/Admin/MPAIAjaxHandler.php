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
        
        // Add AJAX handler for saving consent
        add_action('wp_ajax_mpai_save_consent', [$this, 'handle_save_consent']);
    }
    
    /**
     * Handle AJAX request to save user consent
     *
     * @return void
     */
    public function handle_save_consent(): void {
        $this->log('Processing consent form AJAX submission');
        
        // Check nonce
        if (!isset($_POST['mpai_consent_nonce']) || !wp_verify_nonce($_POST['mpai_consent_nonce'], 'mpai_consent_nonce')) {
            $this->log('Consent form nonce verification failed', ['error' => true]);
            wp_send_json_error(['message' => __('Security check failed.', 'memberpress-ai-assistant')]);
            return;
        }
        
        // Check if consent was given
        if (!isset($_POST['mpai_consent']) || $_POST['mpai_consent'] != '1') {
            $this->log('Consent not provided', ['error' => true]);
            wp_send_json_error(['message' => __('You must agree to the terms to continue.', 'memberpress-ai-assistant')]);
            return;
        }
        
        // Get current user ID
        $user_id = get_current_user_id();
        
        if (empty($user_id)) {
            $this->log('Cannot save consent - no user ID available', ['error' => true]);
            wp_send_json_error(['message' => __('User not logged in.', 'memberpress-ai-assistant')]);
            return;
        }
        
        // Get consent manager
        $consent_manager = \MemberpressAiAssistant\Admin\MPAIConsentManager::getInstance();
        
        // Save consent
        $consent_manager->saveUserConsent($user_id, true);
        
        $this->log('User consent saved successfully via AJAX');
        
        // Return success response
        wp_send_json_success([
            'message' => __('Thank you for agreeing to the terms. You can now use the MemberPress AI Assistant.', 'memberpress-ai-assistant'),
            'redirect' => admin_url('admin.php?page=mpai-settings')
        ]);
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
        
        // Check if user has consented
        $consent_manager = MPAIConsentManager::getInstance();
        if (!$consent_manager->hasUserConsented()) {
            wp_send_json_error([
                'message' => __('You must agree to the terms before using the AI Assistant.', 'memberpress-ai-assistant'),
                'redirect' => admin_url('admin.php?page=mpai-welcome')
            ]);
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