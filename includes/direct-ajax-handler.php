<?php
/**
 * Direct AJAX Handler
 * 
 * Simple handler that bypasses WordPress's admin-ajax.php
 */

// Load WordPress core
require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/wp-load.php');

// Check if user is logged in (only for certain actions)
if (isset($_POST['action']) && $_POST['action'] === 'plugin_logs') {
    // For plugin_logs, only require being logged in
    if (!is_user_logged_in()) {
        header('HTTP/1.1 403 Forbidden');
        echo json_encode(array(
            'success' => false,
            'message' => 'Permission denied - must be logged in'
        ));
        exit;
    }
} else {
    // For all other actions, require admin privileges
    if (!current_user_can('manage_options')) {
        header('HTTP/1.1 403 Forbidden');
        echo json_encode(array(
            'success' => false,
            'message' => 'Permission denied - admin privileges required'
        ));
        exit;
    }
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
    case 'plugin_logs':
        // Direct plugin logs handler for AI tool calls
        if (!is_user_logged_in()) {
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(array(
                'success' => false,
                'message' => 'User must be logged in'
            ));
            exit;
        }

        // Include the plugin logger
        require_once(dirname(__FILE__) . '/class-mpai-plugin-logger.php');
        $plugin_logger = mpai_init_plugin_logger();

        if (!$plugin_logger) {
            echo json_encode(array(
                'success' => false,
                'message' => 'Failed to initialize plugin logger'
            ));
            exit;
        }

        // Get parameters
        $action_type = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : '';
        $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
        
        // Get logs
        $args = array(
            'action'    => $action_type,
            'date_from' => date('Y-m-d H:i:s', strtotime("-{$days} days")),
            'orderby'   => 'date_time',
            'order'     => 'DESC',
            'limit'     => 25
        );
        
        $logs = $plugin_logger->get_logs($args);
        
        // Count logs by action
        $summary = array(
            'total' => count($logs),
            'installed' => 0,
            'updated' => 0,
            'activated' => 0,
            'deactivated' => 0,
            'deleted' => 0
        );
        
        foreach ($logs as $log) {
            if (isset($log['action']) && isset($summary[$log['action']])) {
                $summary[$log['action']]++;
            }
        }
        
        // Format logs with time_ago
        foreach ($logs as &$log) {
            $timestamp = strtotime($log['date_time']);
            $log['time_ago'] = human_time_diff($timestamp, current_time('timestamp')) . ' ago';
        }
        
        echo json_encode(array(
            'success' => true,
            'tool' => 'plugin_logs',
            'summary' => $summary,
            'time_period' => "past {$days} days",
            'logs' => $logs,
            'total' => count($logs),
            'result' => "Plugin logs for the past {$days} days: " . count($logs) . " entries found"
        ));
        break;
    
    case 'test_simple':
        // Check if this is a wp_api create_membership request
        if (isset($_POST['wp_api_action']) && $_POST['wp_api_action'] === 'create_membership') {
            // This is a create_membership request
            try {
                // Check if MemberPress is active
                if (!class_exists('MeprOptions')) {
                    echo json_encode(array(
                        'success' => false,
                        'message' => 'MemberPress is not active'
                    ));
                    break;
                }
                
                // Get parameters with defaults
                $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : 'New Membership';
                $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
                $price = isset($_POST['price']) ? floatval($_POST['price']) : 0.00;
                $period = isset($_POST['period']) ? intval($_POST['period']) : 1;
                $period_type = isset($_POST['period_type']) ? sanitize_text_field($_POST['period_type']) : 'month';
                $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'publish';
                
                // Create the membership
                $post_data = array(
                    'post_title' => $title,
                    'post_content' => $description,
                    'post_status' => $status,
                    'post_type' => 'memberpressproduct',
                );
                
                // Insert the post
                $product_id = wp_insert_post($post_data);
                
                if (is_wp_error($product_id)) {
                    echo json_encode(array(
                        'success' => false,
                        'message' => 'Failed to create membership: ' . $product_id->get_error_message()
                    ));
                    break;
                }
                
                // Set price
                update_post_meta($product_id, '_mepr_product_price', $price);
                
                // Set billing type (default to one-time for simplicity)
                $billing_type = isset($_POST['billing_type']) ? sanitize_text_field($_POST['billing_type']) : 'recurring';
                update_post_meta($product_id, '_mepr_product_period_type', $period_type);
                update_post_meta($product_id, '_mepr_product_period', $period);
                update_post_meta($product_id, '_mepr_billing_type', $billing_type);
                
                // Get edit URL
                $edit_url = admin_url("post.php?post={$product_id}&action=edit");
                
                // Return success with membership details
                echo json_encode(array(
                    'success' => true,
                    'product_id' => $product_id,
                    'title' => $title,
                    'price' => $price,
                    'period' => $period,
                    'period_type' => $period_type,
                    'billing_type' => $billing_type,
                    'edit_url' => $edit_url,
                    'message' => "Membership '{$title}' created successfully"
                ));
                
            } catch (Exception $e) {
                echo json_encode(array(
                    'success' => false,
                    'message' => 'Error creating membership: ' . $e->getMessage()
                ));
            }
            break;
        }
        
        // Check if this is a message update request
        if (isset($_POST['is_update_message']) && $_POST['is_update_message'] === 'true') {
            // This is a message update request
            if (empty($_POST['message_id'])) {
                echo json_encode(array(
                    'success' => false,
                    'message' => 'Missing message_id parameter'
                ));
                break;
            }
            
            if (empty($_POST['content'])) {
                echo json_encode(array(
                    'success' => false,
                    'message' => 'Missing content parameter'
                ));
                break;
            }
            
            // Handle message update without nonce check
            try {
                $message_id = sanitize_text_field($_POST['message_id']);
                $content = $_POST['content']; // Don't sanitize HTML
                
                // Update the message in the database directly
                global $wpdb;
                $table_messages = $wpdb->prefix . 'mpai_messages';
                
                // Check if message exists first
                $message_exists = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*) FROM $table_messages WHERE id = %d",
                        $message_id
                    )
                );
                
                if ($message_exists) {
                    // Update the message
                    $result = $wpdb->update(
                        $table_messages,
                        array('response' => $content),
                        array('id' => $message_id)
                    );
                    
                    if ($result === false) {
                        echo json_encode(array(
                            'success' => false,
                            'message' => 'Database error updating message',
                            'error' => $wpdb->last_error
                        ));
                    } else {
                        echo json_encode(array(
                            'success' => true,
                            'message' => 'Message updated successfully',
                            'message_id' => $message_id
                        ));
                    }
                } else {
                    echo json_encode(array(
                        'success' => false,
                        'message' => 'Message not found in database',
                        'message_id' => $message_id
                    ));
                }
            } catch (Exception $e) {
                echo json_encode(array(
                    'success' => false,
                    'message' => 'Exception updating message: ' . $e->getMessage()
                ));
            }
        } else {
            // Regular simple test response
            echo json_encode(array(
                'success' => true,
                'message' => 'Direct AJAX handler is working',
                'data' => array(
                    'time' => current_time('mysql'),
                    'user_id' => get_current_user_id(),
                    'received_data' => $_POST
                )
            ));
        }
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
        
    case 'test_console_logging':
        // Test console logging action
        $log_level = isset($_POST['log_level']) ? sanitize_text_field($_POST['log_level']) : 'info';
        
        // Process enable_logging without excessive debug logging
        $enable_logging_raw = isset($_POST['enable_logging']) ? $_POST['enable_logging'] : '1';
        
        // Convert to proper boolean value for internal use, but save as string '0' or '1'
        if ($enable_logging_raw === '1' || $enable_logging_raw === 1 || $enable_logging_raw === 'true' || $enable_logging_raw === true) {
            $enable_logging = true;
            $enable_logging_stored = '1';
        } else {
            $enable_logging = false;
            $enable_logging_stored = '0';
        }
        
        // Check if setting has changed to avoid unnecessary database updates
        $current_setting = get_option('mpai_enable_console_logging', '1');
        $setting_changed = ($current_setting !== $enable_logging_stored);
        
        // Save settings to options if needed
        if (isset($_POST['save_settings']) && $_POST['save_settings']) {
            // Avoid unnecessary updates
            if ($setting_changed) {
                // Always save as string values '1' or '0' for consistency
                update_option('mpai_enable_console_logging', $enable_logging_stored);
            }
            
            // Process log level
            $current_log_level = get_option('mpai_console_log_level', 'info');
            if ($current_log_level !== $log_level) {
                update_option('mpai_console_log_level', $log_level);
            }
            
            // Save category settings - only if explicitly provided (no logging)
            if (isset($_POST['log_api_calls'])) {
                update_option('mpai_log_api_calls', $_POST['log_api_calls'] ? '1' : '0');
            }
            if (isset($_POST['log_tool_usage'])) {
                update_option('mpai_log_tool_usage', $_POST['log_tool_usage'] ? '1' : '0');
            }
            if (isset($_POST['log_agent_activity'])) {
                update_option('mpai_log_agent_activity', $_POST['log_agent_activity'] ? '1' : '0');
            }
            if (isset($_POST['log_timing'])) {
                update_option('mpai_log_timing', $_POST['log_timing'] ? '1' : '0');
            }
        }
        
        // Return test data and current settings
        echo json_encode(array(
            'success' => true,
            'message' => 'Console log test completed successfully',
            'test_result' => array(
                'timestamp' => current_time('mysql'),
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown',
                'level_tested' => $log_level,
                'random_id' => 'test_' . rand(1000, 9999)
            ),
            'current_settings' => array(
                'enabled' => get_option('mpai_enable_console_logging', '1'),
                'log_level' => get_option('mpai_console_log_level', 'info'),
                'categories' => array(
                    'api_calls' => get_option('mpai_log_api_calls', '1'),
                    'tool_usage' => get_option('mpai_log_tool_usage', '1'),
                    'agent_activity' => get_option('mpai_log_agent_activity', '1'),
                    'timing' => get_option('mpai_log_timing', '1')
                )
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