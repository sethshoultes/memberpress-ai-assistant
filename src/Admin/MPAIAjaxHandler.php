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
        add_action('wp_ajax_mpai_test_api_connection', [$this, 'handleTestApiConnection']);
    }

    /**
     * Handle test API connection AJAX request
     *
     * @return void
     */
    public function handleTestApiConnection(): void {
        // Check nonce
        if (!check_ajax_referer('mpai_settings_nonce', '_wpnonce', false)) {
            wp_send_json_error([
                'message' => __('Security check failed. Please refresh the page and try again.', 'memberpress-ai-assistant')
            ]);
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('You do not have permission to perform this action.', 'memberpress-ai-assistant')
            ]);
        }
        
        // Get service type
        $service_type = isset($_POST['service']) ? sanitize_text_field($_POST['service']) : '';
        
        if (!in_array($service_type, ['openai', 'anthropic'])) {
            wp_send_json_error([
                'message' => __('Invalid service type.', 'memberpress-ai-assistant')
            ]);
        }
        
        // Get key manager
        global $mpai_service_locator;
        if (!$mpai_service_locator || !$mpai_service_locator->has('key_manager')) {
            wp_send_json_error([
                'message' => __('Key manager service not available.', 'memberpress-ai-assistant')
            ]);
        }
        
        $key_manager = $mpai_service_locator->get('key_manager');
        
        // Test connection
        $result = $key_manager->test_api_connection($service_type);
        
        // Log the result for debugging
        error_log("MPAI Debug - Test connection result: " . json_encode($result));
        
        if ($result['success']) {
            wp_send_json_success([
                'message' => $result['message'],
                'data' => $result
            ]);
        } else {
            wp_send_json_error([
                'message' => $result['message'],
                'data' => $result
            ]);
        }
    }
}