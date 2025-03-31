<?php
/**
 * Direct AJAX Handler
 * 
 * Simple handler that bypasses WordPress's admin-ajax.php
 */

// Load WordPress core
require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/wp-load.php');

// Check if user is logged in and is admin
if (!current_user_can('manage_options')) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(array(
        'success' => false,
        'message' => 'Permission denied'
    ));
    exit;
}

// Output header for JSON
header('Content-Type: application/json');

// Check for required action
if (empty($_POST['action'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(array(
        'success' => false,
        'message' => 'Missing action parameter'
    ));
    exit;
}

// Process different actions
$action = sanitize_text_field($_POST['action']);

switch ($action) {
    case 'test_simple':
        // Simple test that always succeeds
        echo json_encode(array(
            'success' => true,
            'message' => 'Direct AJAX handler is working',
            'data' => array(
                'time' => current_time('mysql'),
                'user_id' => get_current_user_id(),
                'received_data' => $_POST
            )
        ));
        break;
        
    case 'test_nonce':
        // Test nonce verification
        if (empty($_POST['nonce'])) {
            echo json_encode(array(
                'success' => false,
                'message' => 'Missing nonce parameter'
            ));
            break;
        }
        
        $nonce = sanitize_text_field($_POST['nonce']);
        $verified = wp_verify_nonce($nonce, 'mpai_nonce');
        $verified_alt = wp_verify_nonce($nonce, 'mpai_settings_nonce');
        
        echo json_encode(array(
            'success' => ($verified || $verified_alt),
            'message' => ($verified || $verified_alt) ? 'Nonce verified successfully' : 'Nonce verification failed',
            'data' => array(
                'nonce_provided' => $nonce,
                'verified' => $verified ? 'Yes ('.$verified.')' : 'No (0)',
                'verified_alt' => $verified_alt ? 'Yes ('.$verified_alt.')' : 'No (0)',
                'new_test_nonce' => wp_create_nonce('mpai_nonce')
            )
        ));
        break;
        
    case 'test_openai':
        // Test OpenAI API (simplified version)
        if (empty($_POST['api_key'])) {
            echo json_encode(array(
                'success' => false,
                'message' => 'API key is required'
            ));
            break;
        }
        
        $api_key = sanitize_text_field($_POST['api_key']);
        
        // Make a simple request to the OpenAI API
        $response = wp_remote_get(
            'https://api.openai.com/v1/models',
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
            echo json_encode(array(
                'success' => false,
                'message' => $response->get_error_message()
            ));
            break;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($status_code !== 200) {
            $error_message = isset($data['error']['message']) ? $data['error']['message'] : 'Invalid API key or API error';
            echo json_encode(array(
                'success' => false,
                'message' => $error_message
            ));
            break;
        }
        
        echo json_encode(array(
            'success' => true,
            'message' => 'OpenAI API connection successful!',
            'data' => array(
                'models_count' => count($data['data']),
                'first_model' => !empty($data['data']) ? $data['data'][0]['id'] : 'none'
            )
        ));
        break;
        
    case 'test_anthropic':
        // Test Anthropic API
        if (empty($_POST['api_key'])) {
            echo json_encode(array(
                'success' => false,
                'data' => 'API key is required'
            ));
            break;
        }
        
        $api_key = sanitize_text_field($_POST['api_key']);
        $model = !empty($_POST['model']) ? sanitize_text_field($_POST['model']) : 'claude-3-opus-20240229';
        
        // Make a simple request to the Anthropic API
        $response = wp_remote_post(
            'https://api.anthropic.com/v1/messages',
            array(
                'headers' => array(
                    'x-api-key' => $api_key,
                    'anthropic-version' => '2023-06-01',
                    'Content-Type' => 'application/json',
                ),
                'body' => json_encode(array(
                    'model' => $model,
                    'messages' => array(
                        array(
                            'role' => 'user',
                            'content' => 'Hello, I am testing the MemberPress AI Assistant connection to Anthropic. Please respond with a very brief welcome message.'
                        )
                    ),
                    'max_tokens' => 150
                )),
                'timeout' => 30,
                'sslverify' => true,
            )
        );
        
        if (is_wp_error($response)) {
            echo json_encode(array(
                'success' => false,
                'data' => $response->get_error_message()
            ));
            break;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($status_code !== 200) {
            $error_message = isset($data['error']['message']) ? $data['error']['message'] : 'Invalid API key or API error';
            echo json_encode(array(
                'success' => false,
                'data' => 'API Error (' . $status_code . '): ' . $error_message
            ));
            break;
        }
        
        // Save the API key and model to options if they've changed
        if (get_option('mpai_anthropic_api_key') !== $api_key) {
            update_option('mpai_anthropic_api_key', $api_key);
        }
        
        if (get_option('mpai_anthropic_model') !== $model) {
            update_option('mpai_anthropic_model', $model);
        }
        
        echo json_encode(array(
            'success' => true,
            'data' => 'Anthropic API connection successful! Response: ' . $data['content'][0]['text']
        ));
        break;
        
    case 'test_memberpress':
        // Test MemberPress API (simplified version)
        // API key is now optional - direct database access is used
        
        // Check if MemberPress is active
        if (!class_exists('MeprAppCtrl')) {
            echo json_encode(array(
                'success' => false,
                'data' => 'MemberPress plugin is not active'
            ));
            break;
        }
        
        // Developer Tools is no longer required
        
        // Store the API key if provided (for backward compatibility)
        if (!empty($_POST['api_key'])) {
            $api_key = sanitize_text_field($_POST['api_key']);
            update_option('mpai_memberpress_api_key', $api_key);
        }
        
        // Use direct database access instead of API
        global $wpdb;
        
        // Try to get memberships
        $memberships = array();
        
        // Try using MemberPress class if available
        if (class_exists('MeprProduct')) {
            // Get all membership products
            $products = get_posts(array(
                'post_type' => 'memberpressproduct',
                'numberposts' => -1,
                'post_status' => 'publish'
            ));
            
            foreach ($products as $product) {
                $mepr_product = new MeprProduct($product->ID);
                $memberships[] = array(
                    'id' => $product->ID,
                    'title' => $product->post_title,
                    'price' => $mepr_product->price
                );
            }
        } else {
            // Use WP database directly
            $products = $wpdb->get_results("SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = 'memberpressproduct' AND post_status = 'publish'");
            
            foreach ($products as $product) {
                $price = get_post_meta($product->ID, '_mepr_product_price', true);
                $memberships[] = array(
                    'id' => $product->ID,
                    'title' => $product->post_title,
                    'price' => $price
                );
            }
        }
        
        $count = count($memberships);
        
        echo json_encode(array(
            'success' => true,
            'data' => 'MemberPress database access successful! Found ' . $count . ' membership(s).',
            'memberships' => $memberships
        ));
        break;
        
    case 'mpai_run_diagnostic':
        // Run diagnostic test
        if (empty($_POST['test_type'])) {
            echo json_encode(array(
                'success' => false,
                'data' => 'Test type is required'
            ));
            break;
        }
        
        $test_type = sanitize_text_field($_POST['test_type']);
        
        // Create diagnostic tool instance
        if (!class_exists('MPAI_Diagnostic_Tool')) {
            // First, make sure MPAI_Base_Tool is loaded
            if (!class_exists('MPAI_Base_Tool')) {
                $base_tool_path = dirname(dirname(__FILE__)) . '/tools/class-mpai-base-tool.php';
                if (file_exists($base_tool_path)) {
                    require_once $base_tool_path;
                } else {
                    echo json_encode(array(
                        'success' => false,
                        'data' => 'Base tool class not found'
                    ));
                    break;
                }
            }
            
            // Now load the diagnostic tool
            $tool_path = dirname(dirname(__FILE__)) . '/tools/implementations/class-mpai-diagnostic-tool.php';
            if (file_exists($tool_path)) {
                require_once $tool_path;
            } else {
                echo json_encode(array(
                    'success' => false,
                    'data' => 'Diagnostic tool class file not found at: ' . $tool_path
                ));
                break;
            }
        }
        
        $diagnostic_tool = new MPAI_Diagnostic_Tool();
        
        // Get optional API key if provided
        $parameters = array(
            'test_type' => $test_type
        );
        
        if (!empty($_POST['api_key'])) {
            $parameters['api_key'] = sanitize_text_field($_POST['api_key']);
        }
        
        // Execute the diagnostic test
        $result = $diagnostic_tool->execute($parameters);
        
        echo json_encode(array(
            'success' => true,
            'data' => $result
        ));
        break;
        
    default:
        // Unknown action
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(array(
            'success' => false,
            'message' => 'Unknown action: ' . $action
        ));
}

exit;