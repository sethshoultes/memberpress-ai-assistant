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
        
    case 'test_memberpress':
        // Test MemberPress API (simplified version)
        if (empty($_POST['api_key'])) {
            echo json_encode(array(
                'success' => false,
                'message' => 'API key is required'
            ));
            break;
        }
        
        // Check if MemberPress is active
        if (!class_exists('MeprAppCtrl')) {
            echo json_encode(array(
                'success' => false,
                'message' => 'MemberPress plugin is not active'
            ));
            break;
        }
        
        // Check if Developer Tools is active
        if (!class_exists('MeprRestRoutes')) {
            echo json_encode(array(
                'success' => false,
                'message' => 'MemberPress Developer Tools plugin is not active'
            ));
            break;
        }
        
        $api_key = sanitize_text_field($_POST['api_key']);
        $base_url = site_url('/wp-json/mp/v1/');
        $endpoint = $base_url . 'memberships';
        
        // Make a request to the MemberPress API
        $response = wp_remote_get(
            $endpoint,
            array(
                'headers' => array(
                    'Authorization' => $api_key,
                    'Content-Type' => 'application/json',
                ),
                'timeout' => 30,
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
            $error_message = isset($data['message']) ? $data['message'] : 'Invalid API key or API error';
            echo json_encode(array(
                'success' => false,
                'message' => 'API Error (' . $status_code . '): ' . $error_message
            ));
            break;
        }
        
        echo json_encode(array(
            'success' => true,
            'message' => 'MemberPress API connection successful! Found ' . count($data) . ' membership(s).',
            'data' => array(
                'memberships_count' => count($data),
                'first_membership' => !empty($data) ? $data[0]['title'] : 'none'
            )
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