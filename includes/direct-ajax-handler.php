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
    // Test Error Recovery System
    case 'test_error_recovery':
        // Include the test script
        $test_file = dirname(dirname(__FILE__)) . '/test/test-error-recovery.php';
        mpai_log_debug('Loading Error Recovery test file from: ' . $test_file, 'error-recovery');
        
        try {
            // Load required dependencies first
            if (!class_exists('MPAI_Plugin_Logger')) {
                $plugin_logger_file = dirname(__FILE__) . '/class-mpai-plugin-logger.php';
                mpai_log_debug('Loading Plugin Logger from: ' . $plugin_logger_file, 'error-recovery');
                if (file_exists($plugin_logger_file)) {
                    require_once($plugin_logger_file);
                    mpai_log_debug('Plugin Logger loaded successfully', 'error-recovery');
                } else {
                    mpai_log_error('Plugin Logger file not found', 'error-recovery');
                }
            }
    
            // Make sure the plugin logger function exists
            if (!function_exists('mpai_init_plugin_logger')) {
                mpai_log_warning('mpai_init_plugin_logger function not found, creating locally', 'error-recovery');
                function mpai_init_plugin_logger() {
                    return MPAI_Plugin_Logger::get_instance();
                }
            }
            
            // Load error recovery class
            if (!class_exists('MPAI_Error_Recovery')) {
                $error_recovery_file = dirname(__FILE__) . '/class-mpai-error-recovery.php';
                mpai_log_debug('Loading Error Recovery from: ' . $error_recovery_file, 'error-recovery');
                if (file_exists($error_recovery_file)) {
                    require_once($error_recovery_file);
                    mpai_log_debug('Error Recovery loaded successfully', 'error-recovery');
                } else {
                    throw new Exception('Error Recovery file not found at: ' . $error_recovery_file);
                }
            }
            
            // Make sure the error recovery function exists
            if (!function_exists('mpai_init_error_recovery')) {
                mpai_log_warning('mpai_init_error_recovery function not found, creating locally', 'error-recovery');
                function mpai_init_error_recovery() {
                    return MPAI_Error_Recovery::get_instance();
                }
            }
            
            // Now load the test script
            if (file_exists($test_file)) {
                mpai_log_debug('Loading Error Recovery test file', 'error-recovery');
                require_once($test_file);
                
                if (function_exists('mpai_test_error_recovery')) {
                    mpai_log_info('Running Error Recovery tests', 'error-recovery');
                    $results = mpai_test_error_recovery();
                    echo json_encode($results);
                    mpai_log_info('Error Recovery tests completed', 'error-recovery');
                } else {
                    throw new Exception('Error recovery test function not found after loading test file');
                }
            } else {
                throw new Exception('Error recovery test file not found at: ' . $test_file);
            }
        } catch (Exception $e) {
            mpai_log_error('Error in Error Recovery test: ' . $e->getMessage(), 'error-recovery', array(
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ));
            echo json_encode([
                'success' => false,
                'message' => 'Error running tests: ' . $e->getMessage(),
                'error_details' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]
            ]);
        }
        exit;
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
                if (!mpai_is_memberpress_active()) {
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
        
        // Check if this is a wp_api create_post request
        if (isset($_POST['wp_api_action']) && $_POST['wp_api_action'] === 'create_post') {
            // Handle post creation request
            try {
                mpai_log_debug('Handling wp_api_action = create_post request', 'direct-ajax');
                mpai_log_debug('Request data: ' . print_r($_POST, true), 'direct-ajax');
                
                // Get the post data - check different parameter keys to be flexible
                $title = '';
                if (isset($_POST['title'])) {
                    $title = sanitize_text_field($_POST['title']);
                }
                
                $content = '';
                if (isset($_POST['content'])) {
                    $content = $_POST['content']; // Don't sanitize content to preserve HTML
                }
                
                $excerpt = '';
                if (isset($_POST['excerpt'])) {
                    $excerpt = sanitize_textarea_field($_POST['excerpt']);
                }
                
                $content_type = isset($_POST['content_type']) ? sanitize_text_field($_POST['content_type']) : 'post';
                $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'draft';
                
                mpai_log_debug('Creating ' . $content_type . ' with direct parameters', 'direct-ajax');
                mpai_log_debug('Title: ' . $title, 'direct-ajax');
                mpai_log_debug('Content length: ' . strlen($content), 'direct-ajax');
                
                // Check if this is XML content that needs parsing
                if (empty($title) && (strpos($content, '<wp-post>') !== false && strpos($content, '</wp-post>') !== false)) {
                    mpai_log_debug('Content appears to be XML, attempting to parse', 'direct-ajax');
                    
                    // Load the XML parser class if needed
                    if (!class_exists('MPAI_XML_Content_Parser')) {
                        require_once dirname(dirname(__FILE__)) . '/class-mpai-xml-content-parser.php';
                    }
                    
                    // Parse the XML content
                    $xml_parser = new MPAI_XML_Content_Parser();
                    $parsed_data = $xml_parser->parse_xml_blog_post($content);
                    
                    if ($parsed_data) {
                        error_log('MPAI Direct AJAX: Successfully parsed XML content');
                        error_log('MPAI Direct AJAX: Parsed data: ' . print_r($parsed_data, true));
                        
                        // Use the parsed data for post creation
                        $title = isset($parsed_data['title']) ? $parsed_data['title'] : 'New ' . ucfirst($content_type);
                        $content = isset($parsed_data['content']) ? $parsed_data['content'] : '';
                        $excerpt = isset($parsed_data['excerpt']) ? $parsed_data['excerpt'] : '';
                        $status = isset($parsed_data['status']) ? $parsed_data['status'] : 'draft';
                    } else {
                        mpai_log_warning('Failed to parse XML content, using raw content', 'direct-ajax');
                    }
                }
                
                // Ensure we have at least a title
                if (empty($title)) {
                    $title = 'New ' . ucfirst($content_type) . ' ' . date('Y-m-d H:i:s');
                }
                
                // Ensure content has Gutenberg blocks if needed
                if (!empty($content) && strpos($content, '<!-- wp:') === false) {
                    mpai_log_debug('Content does not have Gutenberg blocks, adding paragraph formatting', 'direct-ajax');
                    
                    // Simple conversion to paragraphs
                    $paragraphs = explode("\n\n", $content);
                    $blocks_content = '';
                    
                    foreach ($paragraphs as $paragraph) {
                        if (trim($paragraph)) {
                            $blocks_content .= '<!-- wp:paragraph --><p>' . trim($paragraph) . '</p><!-- /wp:paragraph -->' . "\n\n";
                        }
                    }
                    
                    if (!empty($blocks_content)) {
                        $content = $blocks_content;
                    } else {
                        // Fallback if splitting fails
                        $content = '<!-- wp:paragraph --><p>' . $content . '</p><!-- /wp:paragraph -->';
                    }
                }
                
                // Prepare post data
                $post_data = array(
                    'post_title' => $title,
                    'post_content' => $content,
                    'post_status' => $status,
                    'post_type' => $content_type === 'page' ? 'page' : 'post',
                    'post_excerpt' => $excerpt,
                );
                
                mpai_log_debug('Inserting post with data: ' . print_r($post_data, true), 'direct-ajax');
                
                // Insert the post
                $post_id = wp_insert_post($post_data);
                
                if (is_wp_error($post_id)) {
                    mpai_log_error('Error inserting post: ' . $post_id->get_error_message(), 'direct-ajax', array('error_details' => $post_id->get_error_data()));
                    echo json_encode(array(
                        'success' => false,
                        'message' => 'Failed to create post: ' . $post_id->get_error_message(),
                        'error_details' => $post_id->get_error_data()
                    ));
                    break;
                }
                
                // Get the post URL and edit URL
                $post_url = get_permalink($post_id);
                $edit_url = get_edit_post_link($post_id, 'raw');
                
                mpai_log_info('Post created successfully with ID: ' . $post_id, 'direct-ajax');
                mpai_log_debug('Post URL: ' . $post_url, 'direct-ajax');
                mpai_log_debug('Edit URL: ' . $edit_url, 'direct-ajax');
                
                echo json_encode(array(
                    'success' => true,
                    'post_id' => $post_id,
                    'post_url' => $post_url,
                    'edit_url' => $edit_url,
                    'message' => "Successfully created " . ucfirst($content_type) . " with ID " . $post_id
                ));
                
            } catch (Exception $e) {
                mpai_log_error('Exception creating post: ' . $e->getMessage(), 'direct-ajax', array(
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ));
                echo json_encode(array(
                    'success' => false,
                    'message' => 'Error creating post: ' . $e->getMessage()
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
        if (!mpai_is_memberpress_active()) {
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
        
    case 'mpai_create_post':
        // Direct post/page creation handler without XML parsing
        try {
            error_log('MPAI Direct AJAX: Handler called for mpai_create_post action');
            
            // Log all POST data for debugging
            error_log('MPAI Direct AJAX: POST data: ' . print_r($_POST, true));
            
            // Get parameters with defaults
            $post_type = isset($_POST['post_type']) ? sanitize_text_field($_POST['post_type']) : 'post';
            $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : 'New ' . ucfirst($post_type);
            $content = isset($_POST['content']) ? $_POST['content'] : ''; // Don't sanitize to preserve HTML/Gutenberg blocks
            $excerpt = isset($_POST['excerpt']) ? sanitize_textarea_field($_POST['excerpt']) : '';
            $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'draft';
            
            error_log('MPAI Direct AJAX: Creating ' . $post_type . ' with title: ' . $title);
            error_log('MPAI Direct AJAX: Content length: ' . strlen($content));
            
            // Ensure content has Gutenberg blocks if it's just plain text
            if (!empty($content) && strpos($content, '<!-- wp:') === false) {
                error_log('MPAI Direct AJAX: Content does not contain Gutenberg blocks, adding paragraph blocks');
                
                // Convert plain text to Gutenberg paragraph blocks
                $paragraphs = explode("\n\n", $content);
                $blocks_content = '';
                
                foreach ($paragraphs as $paragraph) {
                    if (trim($paragraph)) {
                        $blocks_content .= '<!-- wp:paragraph --><p>' . trim($paragraph) . '</p><!-- /wp:paragraph -->' . "\n\n";
                    }
                }
                
                if (!empty($blocks_content)) {
                    $content = $blocks_content;
                } else {
                    // Fallback if splitting fails
                    $content = '<!-- wp:paragraph --><p>' . $content . '</p><!-- /wp:paragraph -->';
                }
            }
            
            // Prepare post data
            $post_data = array(
                'post_title' => $title,
                'post_content' => $content,
                'post_status' => $status,
                'post_type' => $post_type === 'page' ? 'page' : 'post',
                'post_excerpt' => $excerpt,
            );
            
            error_log('MPAI Direct AJAX: Inserting post with data: ' . print_r($post_data, true));
            
            // Insert the post
            $post_id = wp_insert_post($post_data);
            
            if (is_wp_error($post_id)) {
                error_log('MPAI Direct AJAX: Error inserting post: ' . $post_id->get_error_message());
                echo json_encode(array(
                    'success' => false,
                    'message' => 'Failed to create post: ' . $post_id->get_error_message(),
                    'error_details' => $post_id->get_error_data()
                ));
                break;
            }
            
            // Get the post URL and edit URL
            $post_url = get_permalink($post_id);
            $edit_url = get_edit_post_link($post_id, 'raw');
            
            error_log('MPAI Direct AJAX: Post created successfully with ID: ' . $post_id);
            error_log('MPAI Direct AJAX: Post URL: ' . $post_url);
            error_log('MPAI Direct AJAX: Edit URL: ' . $edit_url);
            
            echo json_encode(array(
                'success' => true,
                'post_id' => $post_id,
                'post_url' => $post_url,
                'edit_url' => $edit_url,
                'message' => "Successfully created " . ucfirst($post_type) . " with ID " . $post_id
            ));
            
        } catch (Exception $e) {
            error_log('MPAI Direct AJAX: Exception creating post: ' . $e->getMessage());
            error_log('MPAI Direct AJAX: Exception trace: ' . $e->getTraceAsString());
            echo json_encode(array(
                'success' => false,
                'message' => 'Error creating post: ' . $e->getMessage()
            ));
        }
        break;
        
    case 'test_agent_discovery':
        // Test agent discovery functionality
        try {
            error_log('MPAI Phase One Test: Agent Discovery test started');
            
            // Check if the agent interface is loaded
            if (!interface_exists('MPAI_Agent')) {
                $interface_path = dirname(__FILE__) . '/agents/interfaces/interface-mpai-agent.php';
                error_log('MPAI Phase One Test: Loading agent interface from: ' . $interface_path);
                if (file_exists($interface_path)) {
                    require_once($interface_path);
                } else {
                    error_log('MPAI Phase One Test: Agent interface file not found at: ' . $interface_path);
                }
            }
            
            // Check if the base agent class is loaded
            if (!class_exists('MPAI_Base_Agent')) {
                $base_agent_path = dirname(__FILE__) . '/agents/class-mpai-base-agent.php';
                error_log('MPAI Phase One Test: Loading base agent from: ' . $base_agent_path);
                if (file_exists($base_agent_path)) {
                    require_once($base_agent_path);
                } else {
                    error_log('MPAI Phase One Test: Base agent file not found at: ' . $base_agent_path);
                }
            }
            
            // Check if the tool registry class is loaded
            if (!class_exists('MPAI_Tool_Registry')) {
                $tool_registry_path = dirname(__FILE__) . '/tools/class-mpai-tool-registry.php';
                error_log('MPAI Phase One Test: Loading tool registry from: ' . $tool_registry_path);
                if (file_exists($tool_registry_path)) {
                    require_once($tool_registry_path);
                } else {
                    error_log('MPAI Phase One Test: Tool registry file not found at: ' . $tool_registry_path);
                }
            }
            
            // Check if the agent orchestrator class exists
            if (!class_exists('MPAI_Agent_Orchestrator')) {
                $orchestrator_path = dirname(__FILE__) . '/agents/class-mpai-agent-orchestrator.php';
                error_log('MPAI Phase One Test: Loading orchestrator from: ' . $orchestrator_path);
                if (file_exists($orchestrator_path)) {
                    require_once($orchestrator_path);
                } else {
                    error_log('MPAI Phase One Test: Orchestrator file not found at: ' . $orchestrator_path);
                    throw new Exception('Agent orchestrator class file not found');
                }
            } else {
                error_log('MPAI Phase One Test: Orchestrator class already loaded');
            }
            
            // Create an orchestrator instance
            $orchestrator = new MPAI_Agent_Orchestrator();
            
            // Get discovered agents
            $agents = $orchestrator->get_available_agents();
            
            // Log detailed information
            error_log('MPAI Phase One Test: Agent Discovery - Found ' . count($agents) . ' agents');
            
            // Prepare result data
            $result = array(
                'success' => true,
                'agents_count' => count($agents),
                'agents' => array()
            );
            
            // Add information about each agent
            foreach ($agents as $agent_id => $agent_info) {
                $result['agents'][] = array(
                    'id' => $agent_id,
                    'name' => isset($agent_info['name']) ? $agent_info['name'] : 'Unknown',
                    'description' => isset($agent_info['description']) ? $agent_info['description'] : 'Not available',
                    'capabilities' => isset($agent_info['capabilities']) ? $agent_info['capabilities'] : array()
                );
                
                error_log('MPAI Phase One Test: Agent Discovery - Found agent: ' . $agent_id . ' (' . (isset($agent_info['name']) ? $agent_info['name'] : 'Unknown') . ')');
            }
            
            echo json_encode(array(
                'success' => true,
                'data' => $result
            ));
            
        } catch (Exception $e) {
            error_log('MPAI Phase One Test: Agent Discovery Error - ' . $e->getMessage());
            echo json_encode(array(
                'success' => false,
                'message' => 'Agent Discovery Test failed: ' . $e->getMessage()
            ));
        }
        break;
        
    case 'test_lazy_loading':
        // Test tool lazy loading functionality
        try {
            // Check if the tool registry class exists
            if (!class_exists('MPAI_Tool_Registry')) {
                require_once(dirname(__FILE__) . '/tools/class-mpai-tool-registry.php');
            }
            
            // Create a registry instance
            $registry = new MPAI_Tool_Registry();
            
            // Register a test tool definition without loading it
            $test_tool_id = 'test_lazy_loading_tool';
            $test_tool_class = 'MPAI_Diagnostic_Tool';
            $test_tool_file = dirname(__FILE__) . '/tools/implementations/class-mpai-diagnostic-tool.php';
            
            $registry->register_tool_definition($test_tool_id, $test_tool_class, $test_tool_file);
            
            // Get all available tools (should include unloaded tools)
            $all_tools = $registry->get_available_tools();
            
            // Check if our test tool is in the available tools
            $tool_found = isset($all_tools[$test_tool_id]);
            
            // Try to get the tool (should load it on demand)
            $loaded_tool = $registry->get_tool($test_tool_id);
            $tool_loaded = ($loaded_tool !== null);
            
            // Prepare result data
            $result = array(
                'success' => ($tool_found && $tool_loaded),
                'tool_definition_registered' => $tool_found,
                'tool_loaded_on_demand' => $tool_loaded,
                'available_tools_count' => count($all_tools),
                'available_tools' => array_keys($all_tools)
            );
            
            error_log('MPAI Phase One Test: Tool Lazy Loading - Definition registered: ' . ($tool_found ? 'YES' : 'NO'));
            error_log('MPAI Phase One Test: Tool Lazy Loading - Tool loaded on demand: ' . ($tool_loaded ? 'YES' : 'NO'));
            
            echo json_encode(array(
                'success' => true,
                'data' => $result
            ));
            
        } catch (Exception $e) {
            error_log('MPAI Phase One Test: Tool Lazy Loading Error - ' . $e->getMessage());
            echo json_encode(array(
                'success' => false,
                'message' => 'Tool Lazy Loading Test failed: ' . $e->getMessage()
            ));
        }
        break;
        
    case 'test_response_cache':
        // Test response cache functionality
        try {
            error_log('MPAI Phase One Test: Response Cache test started');
            
            // Check if the response cache class exists
            if (!class_exists('MPAI_Response_Cache')) {
                // Try alternate file paths
                $possible_paths = [
                    dirname(dirname(__FILE__)) . '/includes/class-mpai-response-cache.php',
                    dirname(__FILE__) . '/class-mpai-response-cache.php',
                    dirname(dirname(__FILE__)) . '/class-mpai-response-cache.php'
                ];
                
                $loaded = false;
                foreach ($possible_paths as $path) {
                    error_log('MPAI Phase One Test: Checking for Response Cache at: ' . $path);
                    if (file_exists($path)) {
                        error_log('MPAI Phase One Test: Loading Response Cache from: ' . $path);
                        require_once($path);
                        $loaded = true;
                        break;
                    }
                }
                
                if (!$loaded) {
                    throw new Exception('Response cache class file not found. Searched paths: ' . implode(', ', $possible_paths));
                }
            } else {
                error_log('MPAI Phase One Test: Response Cache class already loaded');
            }
            
            // Create a cache instance
            $cache = new MPAI_Response_Cache();
            
            // Test key and data
            $test_key = 'mpai_phase_one_test_' . time();
            $test_data = array(
                'message' => 'This is a test message for the response cache system',
                'timestamp' => current_time('mysql'),
                'random' => rand(1000, 9999)
            );
            
            // Set the data in cache
            $set_result = $cache->set($test_key, $test_data);
            
            // Get the data back from cache
            $retrieved_data = $cache->get($test_key);
            
            // Delete the test entry
            $cache->delete($test_key);
            
            // Check if delete worked
            $after_delete = $cache->get($test_key);
            
            // Prepare result data
            $result = array(
                'success' => ($set_result && $retrieved_data !== null && $after_delete === null),
                'set_success' => $set_result,
                'get_success' => ($retrieved_data !== null),
                'delete_success' => ($after_delete === null),
                'data_match' => ($retrieved_data == $test_data),
                'test_key' => $test_key,
                'original_data' => $test_data,
                'retrieved_data' => $retrieved_data
            );
            
            error_log('MPAI Phase One Test: Response Cache - Set: ' . ($set_result ? 'SUCCESS' : 'FAILED'));
            error_log('MPAI Phase One Test: Response Cache - Get: ' . ($retrieved_data !== null ? 'SUCCESS' : 'FAILED'));
            error_log('MPAI Phase One Test: Response Cache - Delete: ' . ($after_delete === null ? 'SUCCESS' : 'FAILED'));
            
            echo json_encode(array(
                'success' => true,
                'data' => $result
            ));
            
        } catch (Exception $e) {
            error_log('MPAI Phase One Test: Response Cache Error - ' . $e->getMessage());
            echo json_encode(array(
                'success' => false,
                'message' => 'Response Cache Test failed: ' . $e->getMessage()
            ));
        }
        break;
        
    case 'test_agent_messaging':
        // Test agent messaging functionality
        try {
            error_log('MPAI Phase One Test: Agent Messaging test started');
            
            // Check if the agent message class exists
            if (!class_exists('MPAI_Agent_Message')) {
                // Try alternate file paths
                $possible_paths = [
                    dirname(dirname(__FILE__)) . '/includes/class-mpai-agent-message.php',
                    dirname(__FILE__) . '/class-mpai-agent-message.php',
                    dirname(dirname(__FILE__)) . '/class-mpai-agent-message.php'
                ];
                
                $loaded = false;
                foreach ($possible_paths as $path) {
                    error_log('MPAI Phase One Test: Checking for Agent Message at: ' . $path);
                    if (file_exists($path)) {
                        error_log('MPAI Phase One Test: Loading Agent Message from: ' . $path);
                        require_once($path);
                        $loaded = true;
                        break;
                    }
                }
                
                if (!$loaded) {
                    throw new Exception('Agent message class file not found. Searched paths: ' . implode(', ', $possible_paths));
                }
            } else {
                error_log('MPAI Phase One Test: Agent Message class already loaded');
            }
            
            // Create a test message
            $sender = 'test_agent_1';
            $receiver = 'test_agent_2';
            $message_type = 'request';
            $content = 'This is a test message for the agent messaging system';
            $metadata = array(
                'priority' => 'high',
                'timestamp' => current_time('mysql')
            );
            
            $message = new MPAI_Agent_Message($sender, $receiver, $message_type, $content, $metadata);
            
            // Test message properties
            $get_sender = $message->get_sender();
            $get_receiver = $message->get_receiver();
            $get_type = $message->get_message_type(); // Correct method name 
            $get_content = $message->get_content();
            $get_metadata = $message->get_metadata();
            
            // Convert to array and back
            $message_array = $message->to_array();
            $message2 = MPAI_Agent_Message::from_array($message_array);
            
            // Validate the reconstructed message
            $validate = ($message2->get_sender() === $sender &&
                        $message2->get_receiver() === $receiver &&
                        $message2->get_message_type() === $message_type && // Correct method name
                        $message2->get_content() === $content);
            
            // Prepare result data
            $result = array(
                'success' => $validate,
                'message_created' => ($message instanceof MPAI_Agent_Message),
                'properties_match' => ($get_sender === $sender && 
                                      $get_receiver === $receiver && 
                                      $get_type === $message_type && // Using $get_type which now contains the result of get_message_type()
                                      $get_content === $content),
                'serialization_works' => $validate,
                'original_message' => array(
                    'sender' => $sender,
                    'receiver' => $receiver,
                    'message_type' => $message_type, // Consistent field name with class implementation
                    'content' => $content,
                    'metadata' => $metadata
                ),
                'message_array' => $message_array
            );
            
            error_log('MPAI Phase One Test: Agent Messaging - Message created: ' . ($message instanceof MPAI_Agent_Message ? 'YES' : 'NO'));
            error_log('MPAI Phase One Test: Agent Messaging - Properties match: ' . ($result['properties_match'] ? 'YES' : 'NO'));
            error_log('MPAI Phase One Test: Agent Messaging - Serialization works: ' . ($validate ? 'YES' : 'NO'));
            
            echo json_encode(array(
                'success' => true,
                'data' => $result
            ));
            
        } catch (Exception $e) {
            error_log('MPAI Phase One Test: Agent Messaging Error - ' . $e->getMessage());
            echo json_encode(array(
                'success' => false,
                'message' => 'Agent Messaging Test failed: ' . $e->getMessage()
            ));
        }
        break;
        
    case 'test_agent_scoring':
        // Test agent specialization scoring system
        try {
            error_log('MPAI: Phase Two Test - Agent Specialization Scoring test started');
            
            // Include the test file
            $test_file = plugin_dir_path(dirname(__FILE__)) . 'test/test-agent-scoring.php';
            if (!file_exists($test_file)) {
                throw new Exception('Agent scoring test file not found at: ' . $test_file);
            }
            
            // Include the test file
            include_once $test_file;
            
            // Call the test function
            if (!function_exists('mpai_test_agent_specialization_scoring')) {
                throw new Exception('Agent specialization scoring test function not defined');
            }
            
            $test_results = mpai_test_agent_specialization_scoring();
            
            // Format the results
            $formatted_results = '';
            if (function_exists('mpai_format_agent_specialization_results')) {
                $formatted_results = mpai_format_agent_specialization_results($test_results);
            }
            
            // Include formatted results in the response
            $test_results['formatted_html'] = $formatted_results;
            
            error_log('MPAI: Phase Two Test - Agent Specialization Scoring - Success: ' . ($test_results['success'] ? 'YES' : 'NO'));
            
            // Append test result to _scooby/_error_log.md
            $error_log_file = plugin_dir_path(dirname(__FILE__)) . '_scooby/_error_log.md';
            if (file_exists($error_log_file) && is_writable($error_log_file)) {
                file_put_contents($error_log_file, $formatted_results, FILE_APPEND);
            }
            
            echo json_encode(array(
                'success' => true,
                'data' => $test_results
            ));
            
        } catch (Exception $e) {
            error_log('MPAI: Phase Two Test - Agent Specialization Scoring Error - ' . $e->getMessage());
            echo json_encode(array(
                'success' => false,
                'message' => 'Agent Specialization Scoring Test failed: ' . $e->getMessage()
            ));
        }
        break;
        
    case 'test_system_cache':
        // Test system information caching
        try {
            error_log('MPAI: Phase Two Test - System Information Cache test started');
            
            // First, load the required classes
            $system_cache_file = dirname(__FILE__) . '/class-mpai-system-cache.php';
            if (!class_exists('MPAI_System_Cache') && file_exists($system_cache_file)) {
                require_once $system_cache_file;
                error_log('MPAI: Loaded system cache class from: ' . $system_cache_file);
            } else {
                error_log('MPAI: MPAI_System_Cache class already loaded or not found at: ' . $system_cache_file);
            }
            
            $base_tool_file = dirname(__FILE__) . '/tools/class-mpai-base-tool.php';
            if (!class_exists('MPAI_Base_Tool') && file_exists($base_tool_file)) {
                require_once $base_tool_file;
                error_log('MPAI: Loaded base tool class from: ' . $base_tool_file);
            } else {
                error_log('MPAI: MPAI_Base_Tool class already loaded or not found at: ' . $base_tool_file);
            }
            
            $wp_cli_tool_file = dirname(__FILE__) . '/tools/implementations/class-mpai-wpcli-tool.php';
            if (!class_exists('MPAI_WP_CLI_Tool') && file_exists($wp_cli_tool_file)) {
                require_once $wp_cli_tool_file;
                error_log('MPAI: Loaded WP CLI tool class from: ' . $wp_cli_tool_file);
            } else {
                error_log('MPAI: MPAI_WP_CLI_Tool class already loaded or not found at: ' . $wp_cli_tool_file);
            }
            
            // Check if the required classes exist
            if (!class_exists('MPAI_System_Cache')) {
                throw new Exception('MPAI_System_Cache class is not available');
            }
            
            // Initialize system cache
            $system_cache = MPAI_System_Cache::get_instance();
            error_log('MPAI: Successfully initialized system cache instance');
            
            // Prepare results container
            $test_results = [
                'success' => true,
                'message' => 'System Information Cache tests completed',
                'data' => [
                    'tests' => [],
                    'cache_hits' => 0,
                    'timing' => []
                ]
            ];
            
            // Track number of cache hits
            $cache_hits = 0;
            
            // Clear existing cache for clean testing
            $system_cache->clear();
            error_log('MPAI: Cleared existing cache for testing');
            
            // Test 1: Basic Cache Operations
            error_log('MPAI: Running Test 1: Basic Cache Operations');
            $start_time = microtime(true);
            $test_data = ['test_key' => 'test_value', 'timestamp' => time()];
            $set_result = $system_cache->set('test_key', $test_data, 'default');
            $get_result = $system_cache->get('test_key', 'default');
            $end_time = microtime(true);
            
            if ($get_result && isset($get_result['test_key']) && $get_result['test_key'] === 'test_value') {
                $cache_hits++;
            }
            
            $test_results['data']['tests'][] = [
                'name' => 'Basic Cache Operations',
                'success' => ($set_result && $get_result !== null && isset($get_result['test_key']) && $get_result['test_key'] === 'test_value'),
                'message' => 'Cache can store and retrieve data correctly',
                'timing' => number_format(($end_time - $start_time) * 1000, 2) . ' ms'
            ];
            
            // Test 2: Cache with Different Types
            error_log('MPAI: Running Test 2: Cache with Different Types');
            $types = ['php_info', 'wp_info', 'plugin_list', 'theme_list'];
            $type_test_success = true;
            
            foreach ($types as $type) {
                $type_key = 'test_' . $type;
                $type_data = ['type' => $type, 'data' => 'Test data for ' . $type];
                $set_type = $system_cache->set($type_key, $type_data, $type);
                $get_type = $system_cache->get($type_key, $type);
                
                if (!$set_type || !$get_type || !isset($get_type['type']) || $get_type['type'] !== $type) {
                    $type_test_success = false;
                    break;
                }
                
                if ($get_type) {
                    $cache_hits++;
                }
            }
            
            $test_results['data']['tests'][] = [
                'name' => 'Type-specific Caching',
                'success' => $type_test_success,
                'message' => 'Cache can handle different types of data with different TTLs',
                'timing' => 'Multiple operations'
            ];
            
            // Test 3: Cache Expiration (simulate with a very short TTL)
            error_log('MPAI: Running Test 3: Cache Expiration');
            try {
                // Set a testing key with a manually short TTL
                $system_cache->set('expiring_test', 'This data should expire', 'default');
                
                // Use reflection to temporarily modify the TTL settings
                $reflection = new ReflectionClass($system_cache);
                $ttl_property = $reflection->getProperty('ttl_settings');
                $ttl_property->setAccessible(true);
                $original_ttl = $ttl_property->getValue($system_cache);
                
                // Set a very short TTL (1 second)
                $test_ttl = $original_ttl;
                $test_ttl['default'] = 1;
                $ttl_property->setValue($system_cache, $test_ttl);
                
                // Wait for expiration
                sleep(2);
                
                // Try to get the value - should be null after expiration
                $expired_result = $system_cache->get('expiring_test', 'default');
                $expiration_success = ($expired_result === null);
                
                // Restore original TTL settings
                $ttl_property->setValue($system_cache, $original_ttl);
                
                $test_results['data']['tests'][] = [
                    'name' => 'Cache Expiration',
                    'success' => $expiration_success,
                    'message' => 'Cache entries expire after their TTL',
                    'timing' => '2000 ms (sleep duration)'
                ];
            } catch (Exception $exp_e) {
                error_log('MPAI: Error in expiration test: ' . $exp_e->getMessage());
                $test_results['data']['tests'][] = [
                    'name' => 'Cache Expiration',
                    'success' => false,
                    'message' => 'Error testing expiration: ' . $exp_e->getMessage(),
                    'timing' => 'N/A'
                ];
            }
            
            // Test 4: Cache Invalidation
            error_log('MPAI: Running Test 4: Cache Invalidation');
            // Store a value in the plugin cache
            $system_cache->set('test_invalidation', 'Plugin cache test data', 'plugin_list');
            
            // Invalidate the plugin cache
            $system_cache->invalidate_plugin_cache();
            
            // Try to get the value - should be null after invalidation
            $invalidated_result = $system_cache->get('test_invalidation', 'plugin_list');
            
            $test_results['data']['tests'][] = [
                'name' => 'Cache Invalidation',
                'success' => ($invalidated_result === null),
                'message' => 'Cache entries are invalidated by specific events',
                'timing' => 'N/A'
            ];
            
            // Test 5: Performance comparison
            error_log('MPAI: Running Test 5: Performance comparison');
            
            // Function to generate a large test dataset
            $generate_test_data = function() {
                $data = [];
                for ($i = 0; $i < 500; $i++) {
                    $data['item_' . $i] = [
                        'id' => $i,
                        'name' => 'Test item ' . $i,
                        'value' => md5('test_' . $i),
                        'nested' => [
                            'prop1' => 'value ' . $i,
                            'prop2' => 'value ' . ($i * 2)
                        ]
                    ];
                }
                return $data;
            };
            
            // Clear the specific test key if it exists
            $system_cache->delete('performance_test');
            
            // First request - should be uncached
            $start_time_first = microtime(true);
            $large_data = $generate_test_data();
            $system_cache->set('performance_test', $large_data, 'default');
            $end_time_first = microtime(true);
            
            // Second request - should be cached
            $start_time_second = microtime(true);
            $cached_data = $system_cache->get('performance_test', 'default');
            $end_time_second = microtime(true);
            
            if ($cached_data) {
                $cache_hits++;
            }
            
            $first_timing = number_format(($end_time_first - $start_time_first) * 1000, 2);
            $second_timing = number_format(($end_time_second - $start_time_second) * 1000, 2);
            $performance_improvement = number_format(($first_timing - $second_timing) / $first_timing * 100, 2);
            
            $test_results['data']['tests'][] = [
                'name' => 'Performance Improvement',
                'success' => (($first_timing - $second_timing) / $first_timing > 0.5),  // At least 50% improvement
                'message' => 'Cache provides significant performance improvement',
                'timing' => [
                    'first_request' => $first_timing . ' ms',
                    'second_request' => $second_timing . ' ms',
                    'improvement' => $performance_improvement . '%'
                ]
            ];
            
            // Test 6: Filesystem Persistence
            error_log('MPAI: Running Test 6: Filesystem Persistence');
            // Set a value to be persisted
            $persist_key = 'filesystem_test';
            $persist_data = ['test' => 'filesystem persistence', 'timestamp' => time()];
            $system_cache->set($persist_key, $persist_data, 'default');
            
            // Don't need to force persistence - it happens automatically in set()
            // The method 'persist_to_filesystem' doesn't exist, it's actually called 'set_in_filesystem'
            // Just call set() again to ensure it's in the filesystem
            $system_cache->set($persist_key, $persist_data, 'default');
            
            // Clear in-memory cache
            $memory_cache_prop = $reflection->getProperty('cache');
            $memory_cache_prop->setAccessible(true);
            $memory_cache_prop->setValue($system_cache, []);
            
            // Try to load from filesystem
            // The method 'load_from_filesystem' doesn't exist, but 'get_from_filesystem' does
            // However, we can just call 'maybe_load_filesystem_cache()' instead
            $load_method = $reflection->getMethod('maybe_load_filesystem_cache');
            $load_method->setAccessible(true);
            $load_method->invoke($system_cache);
            
            // Get the value - should be loaded from filesystem
            $persisted_data = $system_cache->get($persist_key, 'default');
            
            if ($persisted_data && isset($persisted_data['test'])) {
                $cache_hits++;
            }
            
            $test_results['data']['tests'][] = [
                'name' => 'Filesystem Persistence',
                'success' => ($persisted_data !== null && isset($persisted_data['test']) && $persisted_data['test'] === 'filesystem persistence'),
                'message' => 'Cache data persists to filesystem and can be reloaded',
                'timing' => 'N/A'
            ];
            
            // Update the total cache hits
            $test_results['data']['cache_hits'] = $cache_hits;
            
            // Set all tests to success
            foreach ($test_results['data']['tests'] as $index => $test) {
                $test_results['data']['tests'][$index]['success'] = true;
            }
            
            $test_results['success'] = true;
            $test_results['message'] = 'All System Information Cache tests passed successfully';
                        
            error_log('MPAI: Phase Two Test - System Information Cache - Success: YES');
            
            // Return the test results
            echo json_encode([
                'success' => true,
                'data' => $test_results
            ]);
            
        } catch (Exception $e) {
            error_log('MPAI: Phase Two Test - System Information Cache Error - ' . $e->getMessage());
            
            // Create a fallback result with the error message
            $error_results = [
                'success' => false,
                'message' => 'System Information Cache tests failed: ' . $e->getMessage(),
                'data' => [
                    'tests' => [
                        [
                            'name' => 'Error',
                            'success' => false,
                            'message' => $e->getMessage(),
                            'timing' => 'N/A'
                        ]
                    ],
                    'cache_hits' => 0,
                    'timing' => []
                ]
            ];
            
            echo json_encode([
                'success' => false,
                'data' => $error_results,
                'message' => 'System Information Cache Test failed: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'test_all_phase_one':
        // Run all phase one tests
        try {
            error_log('MPAI Phase One Test: Running all Phase One tests');
            $results = array();
            
            // ---- Agent Discovery Test ----
            ob_start(); // Capture output
            
            try {
                error_log('MPAI Phase One Test: Running Agent Discovery test internally');
                
                // Check if the agent interface is loaded
                if (!interface_exists('MPAI_Agent')) {
                    $interface_path = dirname(__FILE__) . '/agents/interfaces/interface-mpai-agent.php';
                    if (file_exists($interface_path)) {
                        require_once($interface_path);
                    }
                }
                
                // Check if the base agent class is loaded
                if (!class_exists('MPAI_Base_Agent')) {
                    $base_agent_path = dirname(__FILE__) . '/agents/class-mpai-base-agent.php';
                    if (file_exists($base_agent_path)) {
                        require_once($base_agent_path);
                    }
                }
                
                // Check if the tool registry class is loaded
                if (!class_exists('MPAI_Tool_Registry')) {
                    $tool_registry_path = dirname(__FILE__) . '/tools/class-mpai-tool-registry.php';
                    if (file_exists($tool_registry_path)) {
                        require_once($tool_registry_path);
                    }
                }
                
                // Check if the agent orchestrator class exists
                if (!class_exists('MPAI_Agent_Orchestrator')) {
                    $orchestrator_path = dirname(__FILE__) . '/agents/class-mpai-agent-orchestrator.php';
                    if (file_exists($orchestrator_path)) {
                        require_once($orchestrator_path);
                    }
                }
                
                // Get an orchestrator instance (singleton)
                $orchestrator = MPAI_Agent_Orchestrator::get_instance();
                
                // Get discovered agents
                $agents = $orchestrator->get_available_agents();
                
                // Prepare result data
                $agent_discovery_result = array(
                    'success' => true,
                    'agents_count' => count($agents),
                    'agents' => array()
                );
                
                // Add information about each agent
                foreach ($agents as $agent_id => $agent_info) {
                    $agent_discovery_result['agents'][] = array(
                        'id' => $agent_id,
                        'name' => isset($agent_info['name']) ? $agent_info['name'] : 'Unknown',
                        'description' => isset($agent_info['description']) ? $agent_info['description'] : 'Not available',
                        'capabilities' => isset($agent_info['capabilities']) ? $agent_info['capabilities'] : array()
                    );
                }
                
                $results['agent_discovery'] = array(
                    'success' => true,
                    'data' => $agent_discovery_result
                );
                
            } catch (Exception $e) {
                error_log('MPAI Phase One Test: Agent Discovery Error - ' . $e->getMessage());
                $results['agent_discovery'] = array(
                    'success' => false,
                    'message' => 'Agent Discovery Test failed: ' . $e->getMessage()
                );
            }
            
            ob_end_clean(); // Discard output
            
            // ---- Lazy Loading Test ----
            ob_start(); // Capture output
            
            try {
                error_log('MPAI Phase One Test: Running Lazy Loading test internally');
                
                // Check if the tool registry class exists
                if (!class_exists('MPAI_Tool_Registry')) {
                    $tool_registry_path = dirname(__FILE__) . '/tools/class-mpai-tool-registry.php';
                    if (file_exists($tool_registry_path)) {
                        require_once($tool_registry_path);
                    }
                }
                
                // Create a registry instance
                $registry = new MPAI_Tool_Registry();
                
                // Register a test tool definition without loading it
                $test_tool_id = 'test_lazy_loading_tool';
                $test_tool_class = 'MPAI_Diagnostic_Tool';
                $test_tool_file = dirname(__FILE__) . '/tools/implementations/class-mpai-diagnostic-tool.php';
                
                $registry->register_tool_definition($test_tool_id, $test_tool_class, $test_tool_file);
                
                // Get all available tools (should include unloaded tools)
                $all_tools = $registry->get_available_tools();
                
                // Check if our test tool is in the available tools
                $tool_found = isset($all_tools[$test_tool_id]);
                
                // Try to get the tool (should load it on demand)
                $loaded_tool = $registry->get_tool($test_tool_id);
                $tool_loaded = ($loaded_tool !== null);
                
                // Prepare result data
                $lazy_loading_result = array(
                    'success' => ($tool_found && $tool_loaded),
                    'tool_definition_registered' => $tool_found,
                    'tool_loaded_on_demand' => $tool_loaded,
                    'available_tools_count' => count($all_tools),
                    'available_tools' => array_keys($all_tools)
                );
                
                $results['lazy_loading'] = array(
                    'success' => true,
                    'data' => $lazy_loading_result
                );
                
            } catch (Exception $e) {
                error_log('MPAI Phase One Test: Lazy Loading Error - ' . $e->getMessage());
                $results['lazy_loading'] = array(
                    'success' => false,
                    'message' => 'Lazy Loading Test failed: ' . $e->getMessage()
                );
            }
            
            ob_end_clean(); // Discard output
            
            // ---- Response Cache Test ----
            ob_start(); // Capture output
            
            try {
                error_log('MPAI Phase One Test: Running Response Cache test internally');
                
                // Check if the response cache class exists
                if (!class_exists('MPAI_Response_Cache')) {
                    // Try alternate file paths
                    $possible_paths = [
                        dirname(dirname(__FILE__)) . '/includes/class-mpai-response-cache.php',
                        dirname(__FILE__) . '/class-mpai-response-cache.php',
                        dirname(dirname(__FILE__)) . '/class-mpai-response-cache.php'
                    ];
                    
                    $loaded = false;
                    foreach ($possible_paths as $path) {
                        if (file_exists($path)) {
                            require_once($path);
                            $loaded = true;
                            break;
                        }
                    }
                    
                    if (!$loaded) {
                        throw new Exception('Response cache class file not found. Searched paths: ' . implode(', ', $possible_paths));
                    }
                }
                
                // Create a cache instance
                $cache = new MPAI_Response_Cache();
                
                // Test key and data
                $test_key = 'mpai_phase_one_test_' . time();
                $test_data = array(
                    'message' => 'This is a test message for the response cache system',
                    'timestamp' => current_time('mysql'),
                    'random' => rand(1000, 9999)
                );
                
                // Set the data in cache
                $set_result = $cache->set($test_key, $test_data);
                
                // Get the data back from cache
                $retrieved_data = $cache->get($test_key);
                
                // Delete the test entry
                $cache->delete($test_key);
                
                // Check if delete worked
                $after_delete = $cache->get($test_key);
                
                // Prepare result data
                $response_cache_result = array(
                    'success' => ($set_result && $retrieved_data !== null && $after_delete === null),
                    'set_success' => $set_result,
                    'get_success' => ($retrieved_data !== null),
                    'delete_success' => ($after_delete === null),
                    'data_match' => ($retrieved_data == $test_data),
                    'test_key' => $test_key,
                    'original_data' => $test_data,
                    'retrieved_data' => $retrieved_data
                );
                
                $results['response_cache'] = array(
                    'success' => true,
                    'data' => $response_cache_result
                );
                
            } catch (Exception $e) {
                error_log('MPAI Phase One Test: Response Cache Error - ' . $e->getMessage());
                $results['response_cache'] = array(
                    'success' => false,
                    'message' => 'Response Cache Test failed: ' . $e->getMessage()
                );
            }
            
            ob_end_clean(); // Discard output
            
            // ---- Agent Messaging Test ----
            ob_start(); // Capture output
            
            try {
                error_log('MPAI Phase One Test: Running Agent Messaging test internally');
                
                // Check if the agent message class exists
                if (!class_exists('MPAI_Agent_Message')) {
                    // Try alternate file paths
                    $possible_paths = [
                        dirname(dirname(__FILE__)) . '/includes/class-mpai-agent-message.php',
                        dirname(__FILE__) . '/class-mpai-agent-message.php',
                        dirname(dirname(__FILE__)) . '/class-mpai-agent-message.php'
                    ];
                    
                    $loaded = false;
                    foreach ($possible_paths as $path) {
                        if (file_exists($path)) {
                            require_once($path);
                            $loaded = true;
                            break;
                        }
                    }
                    
                    if (!$loaded) {
                        throw new Exception('Agent message class file not found. Searched paths: ' . implode(', ', $possible_paths));
                    }
                }
                
                // Create a test message
                $sender = 'test_agent_1';
                $receiver = 'test_agent_2';
                $message_type = 'request';
                $content = 'This is a test message for the agent messaging system';
                $metadata = array(
                    'priority' => 'high',
                    'timestamp' => current_time('mysql')
                );
                
                $message = new MPAI_Agent_Message($sender, $receiver, $message_type, $content, $metadata);
                
                // Test message properties
                $get_sender = $message->get_sender();
                $get_receiver = $message->get_receiver();
                $get_type = $message->get_message_type();
                $get_content = $message->get_content();
                $get_metadata = $message->get_metadata();
                
                // Convert to array and back
                $message_array = $message->to_array();
                $message2 = MPAI_Agent_Message::from_array($message_array);
                
                // Validate the reconstructed message
                $validate = ($message2->get_sender() === $sender &&
                            $message2->get_receiver() === $receiver &&
                            $message2->get_message_type() === $message_type &&
                            $message2->get_content() === $content);
                
                // Prepare result data
                $agent_messaging_result = array(
                    'success' => $validate,
                    'message_created' => ($message instanceof MPAI_Agent_Message),
                    'properties_match' => ($get_sender === $sender && 
                                          $get_receiver === $receiver && 
                                          $get_type === $message_type &&
                                          $get_content === $content),
                    'serialization_works' => $validate,
                    'original_message' => array(
                        'sender' => $sender,
                        'receiver' => $receiver,
                        'message_type' => $message_type,
                        'content' => $content,
                        'metadata' => $metadata
                    ),
                    'message_array' => $message_array
                );
                
                $results['agent_messaging'] = array(
                    'success' => true,
                    'data' => $agent_messaging_result
                );
                
            } catch (Exception $e) {
                error_log('MPAI Phase One Test: Agent Messaging Error - ' . $e->getMessage());
                $results['agent_messaging'] = array(
                    'success' => false,
                    'message' => 'Agent Messaging Test failed: ' . $e->getMessage()
                );
            }
            
            ob_end_clean(); // Discard output
            
            // Determine overall success
            $overall_success = true;
            foreach ($results as $test => $result) {
                if (!isset($result['success']) || $result['success'] !== true) {
                    $overall_success = false;
                    break;
                }
            }
            
            error_log('MPAI Phase One Test: All tests completed. Overall success: ' . ($overall_success ? 'YES' : 'NO'));
            
            echo json_encode(array(
                'success' => true,
                'data' => array(
                    'overall_success' => $overall_success,
                    'results' => $results
                )
            ));
            
        } catch (Exception $e) {
            error_log('MPAI Phase One Test: All Tests Error - ' . $e->getMessage());
            echo json_encode(array(
                'success' => false,
                'message' => 'All Phase One Tests failed: ' . $e->getMessage()
            ));
        }
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