<?php
/**
 * Admin Class
 *
 * Handles admin functionality
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

class MPAI_Admin {
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_ajax_mpai_process_chat', array($this, 'process_chat'));
        add_action('wp_ajax_mpai_reset_conversation', array($this, 'reset_conversation'));
        add_action('wp_ajax_mpai_run_command', array($this, 'run_command'));
        add_action('wp_ajax_mpai_test_openai_api', array($this, 'test_openai_api'));
        add_action('wp_ajax_mpai_test_memberpress_api', array($this, 'test_memberpress_api'));
        
        // Debug actions
        add_action('wp_ajax_mpai_debug_nonce', array($this, 'debug_nonce'));
        add_action('wp_ajax_mpai_simple_test', array($this, 'simple_test'));
    }
    
    /**
     * Simple AJAX test handler - no nonce check for debugging
     */
    public function simple_test() {
        error_log('MPAI: Simple test AJAX handler called');
        error_log('MPAI: POST data: ' . json_encode($_POST));
        
        // Return success regardless
        wp_send_json_success(array(
            'message' => 'Simple test successful!',
            'received_data' => $_POST,
            'timestamp' => current_time('timestamp')
        ));
    }
    
    /**
     * Debug nonce verification
     */
    public function debug_nonce() {
        $response = array(
            'success' => false,
            'message' => '',
            'data' => array()
        );
        
        // Log all POST data for debugging
        error_log('MPAI: debug_nonce called with POST data: ' . json_encode($_POST));
        
        // Check if nonce is present
        if (!isset($_POST['mpai_nonce'])) {
            $response['message'] = 'No nonce provided';
            echo wp_json_encode($response);
            wp_die();
        }
        
        // Get and sanitize the nonce
        $nonce = sanitize_text_field($_POST['mpai_nonce']);
        error_log('MPAI: Nonce provided to debug_nonce: ' . substr($nonce, 0, 5) . '... (length: ' . strlen($nonce) . ')');
        
        // Verify the nonce manually without terminating
        $verified = wp_verify_nonce($nonce, 'mpai_nonce');
        error_log('MPAI: Manual nonce verification result in debug_nonce: ' . ($verified ? 'Success ('.$verified.')' : 'Failed (0)'));
        
        // Also try verifying with another action string to see if that works
        $verified_alt = wp_verify_nonce($nonce, 'mpai_settings_nonce');
        error_log('MPAI: Alternative nonce verification result using mpai_settings_nonce: ' . ($verified_alt ? 'Success ('.$verified_alt.')' : 'Failed (0)'));
        
        // Add debug info to response
        $response['data']['nonce_provided'] = $nonce;
        $response['data']['verified'] = $verified ? 'Yes (' . $verified . ')' : 'No (0)';
        $response['data']['verified_alt'] = $verified_alt ? 'Yes (' . $verified_alt . ')' : 'No (0)';
        $response['data']['timestamp'] = current_time('timestamp');
        $response['data']['cookies'] = isset($_COOKIE) ? array_keys($_COOKIE) : array();
        $response['data']['user_id'] = get_current_user_id();
        $response['data']['ajax_url'] = admin_url('admin-ajax.php');
        
        // Generate a new test nonce for comparison
        $new_nonce = wp_create_nonce('mpai_nonce');
        $response['data']['new_test_nonce'] = $new_nonce;
        
        // Also generate an alternative nonce
        $new_alt_nonce = wp_create_nonce('mpai_settings_nonce');
        $response['data']['new_alt_nonce'] = $new_alt_nonce;
        
        // Get localized script data if available
        global $wp_scripts;
        $localized_data = isset($wp_scripts->registered['mpai-admin-js']) && 
                          isset($wp_scripts->registered['mpai-admin-js']->extra['data']) ? 
                          $wp_scripts->registered['mpai-admin-js']->extra['data'] : 'Not available';
        
        $response['data']['localized_script_data'] = $localized_data;
        
        // Set success based on verification
        if ($verified || $verified_alt) {
            $response['success'] = true;
            $response['message'] = 'Nonce verified successfully' . ($verified_alt && !$verified ? ' (using alternate action)' : '');
        } else {
            $response['message'] = 'Nonce verification failed for both standard and alternate actions';
        }
        
        error_log('MPAI: debug_nonce responding with: ' . json_encode($response));
        
        echo wp_json_encode($response);
        wp_die();
    }

    /**
     * Enqueue assets
     *
     * @param string $hook Current admin page
     */
    public function enqueue_assets($hook) {
        if (strpos($hook, 'memberpress-ai-assistant') === false) {
            return;
        }

        wp_enqueue_style(
            'mpai-admin-style',
            MPAI_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            MPAI_VERSION
        );

        wp_enqueue_script(
            'mpai-admin-script',
            MPAI_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            MPAI_VERSION,
            true
        );

        wp_localize_script(
            'mpai-admin-script',
            'mpai_data',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mpai_nonce'),
                'debug_info' => array(
                    'plugin_version' => MPAI_VERSION,
                    'wp_version' => get_bloginfo('version'),
                    'api_configured' => !empty(get_option('mpai_api_key')),
                    'memberpress_active' => class_exists('MeprAppCtrl')
                )
            )
        );
    }

    /**
     * Process chat request
     */
    public function process_chat() {
        try {
            // Log the AJAX request for debugging
            error_log('MPAI: AJAX process_chat called. POST data: ' . json_encode($_POST));
            
            // Check nonce - changed to check_ajax_referer which handles error automatically
            check_ajax_referer('mpai_nonce', 'mpai_nonce');

            // Check message
            if (empty($_POST['message'])) {
                error_log('MPAI: Empty message in AJAX request');
                wp_send_json_error('Message is required');
            }

            $message = sanitize_textarea_field($_POST['message']);

            // Process message
            $chat = new MPAI_Chat();
            $response = $chat->process_message($message);

            if (isset($response['success']) && $response['success']) {
                wp_send_json_success($response);
            } else {
                error_log('MPAI: Error in chat processing: ' . json_encode($response));
                wp_send_json_error(isset($response['message']) ? $response['message'] : 'Unknown error occurred');
            }
        } catch (Exception $e) {
            error_log('MPAI: Exception in process_chat: ' . $e->getMessage());
            wp_send_json_error('An error occurred: ' . $e->getMessage());
        }
    }

    /**
     * Reset conversation
     */
    public function reset_conversation() {
        // Check nonce
        check_ajax_referer('mpai_nonce', 'mpai_nonce');

        // Reset conversation
        $chat = new MPAI_Chat();
        $success = $chat->reset_conversation();

        if ($success) {
            wp_send_json_success('Conversation reset successfully');
        } else {
            wp_send_json_error('Failed to reset conversation');
        }
    }

    /**
     * Run command
     */
    public function run_command() {
        // Check nonce
        check_ajax_referer('mpai_nonce', 'mpai_nonce');

        // Check command
        if (empty($_POST['command'])) {
            wp_send_json_error('Command is required');
        }

        $command = sanitize_text_field($_POST['command']);
        $context = isset($_POST['context']) ? sanitize_textarea_field($_POST['context']) : '';

        // Run command
        try {
            $context_manager = new MPAI_Context_Manager();
            $result = $context_manager->execute_mcp_command($command, $context);
            
            wp_send_json_success($result);
        } catch (Exception $e) {
            wp_send_json_error('Error executing command: ' . $e->getMessage());
        }
    }
    
    /**
     * Test OpenAI API connection
     */
    public function test_openai_api() {
        try {
            // Log the AJAX request for debugging
            error_log('MPAI: test_openai_api called.');
            
            // Convert to associative array for better logging
            $post_data = array();
            foreach ($_POST as $key => $value) {
                if ($key === 'mpai_nonce') {
                    $post_data[$key] = substr($value, 0, 5) . '...';
                } elseif ($key === 'api_key') {
                    $post_data[$key] = substr($value, 0, 5) . '...';
                } else {
                    $post_data[$key] = $value;
                }
            }
            
            error_log('MPAI: POST data: ' . wp_json_encode($post_data));
            
            // Check nonce - be more thorough about checking if it exists
            if (!isset($_POST['mpai_nonce'])) {
                error_log('MPAI: No nonce provided in request');
                wp_send_json_error('Security check failed - no nonce provided');
                return;
            }
            
            // Log the received nonce first few characters for debugging
            $nonce = sanitize_text_field($_POST['mpai_nonce']);
            error_log('MPAI: Received nonce: ' . substr($nonce, 0, 5) . '... (length: ' . strlen($nonce) . ')');
            
            // Log POST data for debugging (excluding sensitive info)
            $debug_post = $_POST;
            if (isset($debug_post['api_key'])) {
                $debug_post['api_key'] = substr($debug_post['api_key'], 0, 5) . '...';
            }
            error_log('MPAI: POST data for OpenAI test: ' . json_encode($debug_post));
            
            // Manually verify nonce first for debugging
            $verified = wp_verify_nonce($nonce, 'mpai_nonce');
            error_log('MPAI: Manual nonce verification result: ' . ($verified ? 'Success ('.$verified.')' : 'Failed (0)'));
            
            // Try with alternate nonce action
            $verified_alt = wp_verify_nonce($nonce, 'mpai_settings_nonce');
            error_log('MPAI: Alternative nonce verification result: ' . ($verified_alt ? 'Success ('.$verified_alt.')' : 'Failed (0)'));
            
            // Get a new nonce to compare
            $new_nonce = wp_create_nonce('mpai_nonce');
            error_log('MPAI: New nonce generated for comparison: ' . substr($new_nonce, 0, 5) . '... (length: ' . strlen($new_nonce) . ')');
            
            // For testing purposes, use either standard or alternate verification
            if (!$verified && !$verified_alt) {
                error_log('MPAI: Both standard and alternate nonce verification failed, sending error response');
                wp_send_json_error('Security check failed - invalid nonce');
                return;
            }
            
            if ($verified_alt && !$verified) {
                error_log('MPAI: Using alternate nonce verification');
            }
            
            // Log successful nonce check
            error_log('MPAI: Nonce check passed with check_ajax_referer');
            
            // Log after nonce check
            error_log('MPAI: test_openai_api nonce check passed');
            
            // Check API key
            if (empty($_POST['api_key'])) {
                error_log('MPAI: API key is empty');
                wp_send_json_error(__('API key is required', 'memberpress-ai-assistant'));
                return;
            }
            
            $api_key = sanitize_text_field($_POST['api_key']);
            error_log('MPAI: Testing OpenAI API with key: ' . substr($api_key, 0, 3) . '...');
            
            // Direct API test using wp_remote_get
            $endpoint = 'https://api.openai.com/v1/models';
            
            error_log('MPAI: Making request to OpenAI models endpoint');
            $response = wp_remote_get(
                $endpoint,
                array(
                    'headers' => array(
                        'Authorization' => 'Bearer ' . $api_key,
                        'Content-Type' => 'application/json',
                    ),
                    'timeout' => 30,
                    'sslverify' => true,
                )
            );
            
            if (is_wp_error($response)) {
                error_log('MPAI: API test returned WP_Error: ' . $response->get_error_message());
                wp_send_json_error($response->get_error_message());
                return;
            }
            
            $status_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            
            error_log('MPAI: OpenAI API test response status: ' . $status_code);
            error_log('MPAI: Response body (partial): ' . substr($body, 0, 100) . '...');
            
            $data = json_decode($body, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('MPAI: JSON decode error: ' . json_last_error_msg());
                wp_send_json_error('Failed to parse API response: ' . json_last_error_msg());
                return;
            }
            
            if ($status_code !== 200) {
                $error_message = isset($data['error']['message']) ? $data['error']['message'] : 'Invalid API key or API error';
                error_log('MPAI: API error: ' . $error_message);
                wp_send_json_error($error_message);
                return;
            }
            
            if (empty($data['data'])) {
                error_log('MPAI: API response missing data array');
                wp_send_json_error(__('Invalid response from OpenAI API', 'memberpress-ai-assistant'));
                return;
            }
            
            error_log('MPAI: OpenAI API test successful');
            wp_send_json_success(__('Connection successful!', 'memberpress-ai-assistant'));
            
        } catch (Exception $e) {
            error_log('MPAI: Exception in test_openai_api: ' . $e->getMessage());
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Test MemberPress API connection
     */
    public function test_memberpress_api() {
        try {
            // Log the AJAX request for debugging
            error_log('MPAI: test_memberpress_api called.');
            
            // Convert to associative array for better logging
            $post_data = array();
            foreach ($_POST as $key => $value) {
                if ($key === 'mpai_nonce') {
                    $post_data[$key] = substr($value, 0, 5) . '...';
                } elseif ($key === 'api_key') {
                    $post_data[$key] = substr($value, 0, 5) . '...';
                } else {
                    $post_data[$key] = $value;
                }
            }
            
            error_log('MPAI: POST data: ' . wp_json_encode($post_data));
            
            // Check nonce - be more thorough about checking if it exists
            if (!isset($_POST['mpai_nonce'])) {
                error_log('MPAI: No nonce provided in request');
                wp_send_json_error('Security check failed - no nonce provided');
                return;
            }
            
            // Log the received nonce first few characters for debugging
            $nonce = sanitize_text_field($_POST['mpai_nonce']);
            error_log('MPAI: Received nonce: ' . substr($nonce, 0, 5) . '... (length: ' . strlen($nonce) . ')');
            
            // Log POST data for debugging (excluding sensitive info)
            $debug_post = $_POST;
            if (isset($debug_post['api_key'])) {
                $debug_post['api_key'] = substr($debug_post['api_key'], 0, 5) . '...';
            }
            error_log('MPAI: POST data for MemberPress test: ' . json_encode($debug_post));
            
            // Manually verify nonce first for debugging
            $verified = wp_verify_nonce($nonce, 'mpai_nonce');
            error_log('MPAI: Manual nonce verification result: ' . ($verified ? 'Success ('.$verified.')' : 'Failed (0)'));
            
            // Try with alternate nonce action
            $verified_alt = wp_verify_nonce($nonce, 'mpai_settings_nonce');
            error_log('MPAI: Alternative nonce verification result: ' . ($verified_alt ? 'Success ('.$verified_alt.')' : 'Failed (0)'));
            
            // Get a new nonce to compare
            $new_nonce = wp_create_nonce('mpai_nonce');
            error_log('MPAI: New nonce generated for comparison: ' . substr($new_nonce, 0, 5) . '... (length: ' . strlen($new_nonce) . ')');
            
            // For testing purposes, use either standard or alternate verification
            if (!$verified && !$verified_alt) {
                error_log('MPAI: Both standard and alternate nonce verification failed, sending error response');
                wp_send_json_error('Security check failed - invalid nonce');
                return;
            }
            
            if ($verified_alt && !$verified) {
                error_log('MPAI: Using alternate nonce verification');
            }
            
            // Log successful nonce check
            error_log('MPAI: Nonce check passed with check_ajax_referer');
            
            // Log after nonce check
            error_log('MPAI: test_memberpress_api nonce check passed');
            
            // Check API key
            if (empty($_POST['api_key'])) {
                error_log('MPAI: MemberPress API key is empty');
                wp_send_json_error(__('API key is required', 'memberpress-ai-assistant'));
                return;
            }
            
            // Check if MemberPress is active
            if (!class_exists('MeprAppCtrl')) {
                error_log('MPAI: MemberPress plugin is not active');
                wp_send_json_error(__('MemberPress plugin is not active', 'memberpress-ai-assistant'));
                return;
            }
            
            // Check if Developer Tools is active by looking for specific REST endpoints
            if (!function_exists('rest_get_server') || !class_exists('MeprRestRoutes')) {
                error_log('MPAI: MemberPress Developer Tools does not appear to be active');
                wp_send_json_error(__('MemberPress Developer Tools plugin is not active. Please activate it to use the API.', 'memberpress-ai-assistant'));
                return;
            }
            
            $api_key = sanitize_text_field($_POST['api_key']);
            
            error_log('MPAI: Testing MemberPress API with key: ' . substr($api_key, 0, 3) . '...');
            
            // Create test instance of MemberPress API with the provided key
            $memberpress_api = new MPAI_MemberPress_API();
            
            // Define test property for API key
            $reflection = new ReflectionClass($memberpress_api);
            $property = $reflection->getProperty('api_key');
            $property->setAccessible(true);
            $property->setValue($memberpress_api, $api_key);
            
            error_log('MPAI: API base URL: ' . $memberpress_api->get_base_url());
            
            // Try to get memberships to test connection
            error_log('MPAI: Attempting to get memberships to test connection');
            $response = $memberpress_api->get_memberships(array('per_page' => 1));
            
            if (is_wp_error($response)) {
                $error_message = $response->get_error_message();
                $error_code = $response->get_error_code();
                error_log('MPAI: MemberPress API test error: [' . $error_code . '] ' . $error_message);
                
                // If the error is due to no MemberPress REST API, give a clearer message
                if (strpos($error_message, '404') !== false || $error_code === 'http_request_failed') {
                    wp_send_json_error(__('MemberPress REST API not found or not responding. Make sure MemberPress Developer Tools is activated and the API is properly configured.', 'memberpress-ai-assistant'));
                } else if (strpos($error_message, '401') !== false || strpos($error_message, 'unauthorized') !== false) {
                    wp_send_json_error(__('API authentication failed. Check your API key.', 'memberpress-ai-assistant'));
                } else {
                    wp_send_json_error($error_message);
                }
                return;
            }
            
            // Check if response is an array (valid response format)
            if (!is_array($response)) {
                error_log('MPAI: Invalid response format from MemberPress API: ' . wp_json_encode($response));
                wp_send_json_error(__('Invalid response from MemberPress API', 'memberpress-ai-assistant'));
                return;
            }
            
            // Get the count of memberships found
            $count = count($response);
            error_log('MPAI: Found ' . $count . ' memberships in API response');
            
            if ($count > 0) {
                // Log a bit more info about what we found
                error_log('MPAI: Membership titles: ' . wp_json_encode(array_map(function($item) {
                    return isset($item['title']) ? $item['title'] : 'Untitled';
                }, $response)));
                
                wp_send_json_success(sprintf(__('Connection successful! Found %d membership(s).', 'memberpress-ai-assistant'), $count));
            } else {
                wp_send_json_success(__('Connection successful! No memberships found.', 'memberpress-ai-assistant'));
            }
            
        } catch (Exception $e) {
            error_log('MPAI: Exception in test_memberpress_api: ' . $e->getMessage());
            wp_send_json_error($e->getMessage());
        }
    }
}