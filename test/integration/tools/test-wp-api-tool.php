<?php
/**
 * Integration tests for WordPress API Tool
 *
 * @package MemberPress AI Assistant
 */

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Run integration tests for WordPress API Tool
 *
 * @return array Test results
 */
function mpai_test_wp_api_tool() {
    $results = [
        'tests' => [],
        'passed' => 0,
        'failed' => 0,
        'total' => 0
    ];

    // Ensure the MPAI_WP_API_Tool class is loaded
    if (!class_exists('MPAI_WP_API_Tool')) {
        $tool_path = MPAI_PLUGIN_DIR . 'includes/tools/implementations/class-mpai-wp-api-tool.php';
        if (file_exists($tool_path)) {
            require_once $tool_path;
        } else {
            $results['tests'][] = [
                'name' => 'WordPress API Tool Class Loading',
                'result' => 'failed',
                'message' => 'Could not find WordPress API tool class file'
            ];
            $results['failed']++;
            $results['total']++;
            return $results;
        }
    }

    // Test 1: Tool instance creation
    $instance_test = [
        'name' => 'WordPress API Tool Instance Creation',
        'result' => 'failed',
        'message' => ''
    ];

    try {
        $wp_api_tool = new MPAI_WP_API_Tool();
        if ($wp_api_tool instanceof MPAI_WP_API_Tool) {
            $instance_test['result'] = 'passed';
            $instance_test['message'] = 'Successfully created WordPress API tool instance';
            $results['passed']++;
        } else {
            $instance_test['message'] = 'Failed to create WordPress API tool instance';
            $results['failed']++;
        }
    } catch (Exception $e) {
        $instance_test['message'] = 'Exception creating WordPress API tool instance: ' . $e->getMessage();
        $results['failed']++;
    }

    $results['tests'][] = $instance_test;
    $results['total']++;

    // Test 2: Tool properties
    $properties_test = [
        'name' => 'WordPress API Tool Properties',
        'result' => 'failed',
        'message' => ''
    ];

    try {
        $name = $wp_api_tool->get_name();
        $description = $wp_api_tool->get_description();

        if (!empty($name) && !empty($description)) {
            $properties_test['result'] = 'passed';
            $properties_test['message'] = "Tool has valid properties - Name: $name, Description: $description";
            $results['passed']++;
        } else {
            $properties_test['message'] = 'Tool has missing or invalid properties';
            $results['failed']++;
        }
    } catch (Exception $e) {
        $properties_test['message'] = 'Exception accessing tool properties: ' . $e->getMessage();
        $results['failed']++;
    }

    $results['tests'][] = $properties_test;
    $results['total']++;

    // Test 3: Execute with invalid parameters
    $invalid_params_test = [
        'name' => 'WordPress API Tool Invalid Parameters',
        'result' => 'failed',
        'message' => ''
    ];

    try {
        $wp_api_tool->execute([]);
        $invalid_params_test['message'] = 'Tool accepted execution without action parameter - should have thrown an exception';
        $results['failed']++;
    } catch (Exception $e) {
        $invalid_params_test['result'] = 'passed';
        $invalid_params_test['message'] = 'Tool correctly rejected execution without action parameter';
        $results['passed']++;
    }

    $results['tests'][] = $invalid_params_test;
    $results['total']++;

    // Test 4: Execute with invalid action
    $invalid_action_test = [
        'name' => 'WordPress API Tool Invalid Action',
        'result' => 'failed',
        'message' => ''
    ];

    try {
        $wp_api_tool->execute(['action' => 'invalid_action_xyz']);
        $invalid_action_test['message'] = 'Tool accepted execution with invalid action - should have thrown an exception';
        $results['failed']++;
    } catch (Exception $e) {
        $invalid_action_test['result'] = 'passed';
        $invalid_action_test['message'] = 'Tool correctly rejected execution with invalid action';
        $results['passed']++;
    }

    $results['tests'][] = $invalid_action_test;
    $results['total']++;

    // Test 5: Execute get_plugins action
    $get_plugins_test = [
        'name' => 'WordPress API Tool Get Plugins',
        'result' => 'failed',
        'message' => ''
    ];

    try {
        $result = $wp_api_tool->execute(['action' => 'get_plugins']);
        
        if (is_array($result) && isset($result['success']) && $result['success'] === true && isset($result['plugins'])) {
            $get_plugins_test['result'] = 'passed';
            $get_plugins_test['message'] = "Tool correctly executed get_plugins action, found " . count($result['plugins']) . " plugins";
            $results['passed']++;
        } else {
            $get_plugins_test['message'] = 'Tool execution for get_plugins returned invalid format';
            $results['failed']++;
        }
    } catch (Exception $e) {
        $get_plugins_test['message'] = 'Exception executing get_plugins action: ' . $e->getMessage();
        $results['failed']++;
    }

    $results['tests'][] = $get_plugins_test;
    $results['total']++;

    // Test 6: Execute get_plugins with table format
    $get_plugins_table_test = [
        'name' => 'WordPress API Tool Get Plugins Table Format',
        'result' => 'failed',
        'message' => ''
    ];

    try {
        $result = $wp_api_tool->execute([
            'action' => 'get_plugins',
            'format' => 'table'
        ]);
        
        if (is_array($result) && isset($result['success']) && $result['success'] === true && isset($result['table_data'])) {
            $get_plugins_table_test['result'] = 'passed';
            $get_plugins_table_test['message'] = "Tool correctly executed get_plugins with table format";
            $results['passed']++;
        } else {
            $get_plugins_table_test['message'] = 'Tool execution for get_plugins with table format returned invalid format';
            $results['failed']++;
        }
    } catch (Exception $e) {
        $get_plugins_table_test['message'] = 'Exception executing get_plugins with table format: ' . $e->getMessage();
        $results['failed']++;
    }

    $results['tests'][] = $get_plugins_table_test;
    $results['total']++;

    // Test 7: Execute get_users action
    $get_users_test = [
        'name' => 'WordPress API Tool Get Users',
        'result' => 'failed',
        'message' => ''
    ];

    try {
        $result = $wp_api_tool->execute([
            'action' => 'get_users',
            'limit' => 2 // Limit to 2 users for testing
        ]);
        
        if (is_array($result) && isset($result['success']) && $result['success'] === true && isset($result['users'])) {
            $get_users_test['result'] = 'passed';
            $get_users_test['message'] = "Tool correctly executed get_users action, found " . count($result['users']) . " users";
            $results['passed']++;
        } else {
            $get_users_test['message'] = 'Tool execution for get_users returned invalid format';
            $results['failed']++;
        }
    } catch (Exception $e) {
        $get_users_test['message'] = 'Exception executing get_users action: ' . $e->getMessage();
        $results['failed']++;
    }

    $results['tests'][] = $get_users_test;
    $results['total']++;

    // Test 8: Create and get post
    $create_post_test = [
        'name' => 'WordPress API Tool Create/Get Post',
        'result' => 'failed',
        'message' => ''
    ];

    $post_id = null;
    try {
        // Create a test post
        $test_title = 'MPAI Test Post ' . time();
        $result = $wp_api_tool->execute([
            'action' => 'create_post',
            'title' => $test_title,
            'content' => 'This is a test post created by the integration test.',
            'status' => 'draft'
        ]);
        
        if (is_array($result) && isset($result['success']) && $result['success'] === true && isset($result['post_id'])) {
            $post_id = $result['post_id'];
            
            // Now try to get the post
            $get_result = $wp_api_tool->execute([
                'action' => 'get_post',
                'post_id' => $post_id
            ]);
            
            if (is_array($get_result) && isset($get_result['success']) && $get_result['success'] === true && 
                isset($get_result['post']) && $get_result['post']['post_title'] === $test_title) {
                
                $create_post_test['result'] = 'passed';
                $create_post_test['message'] = "Tool correctly created and retrieved post with ID: $post_id";
                $results['passed']++;
            } else {
                $create_post_test['message'] = 'Tool created post but failed to retrieve it correctly';
                $results['failed']++;
            }
        } else {
            $create_post_test['message'] = 'Tool failed to create test post';
            $results['failed']++;
        }
    } catch (Exception $e) {
        $create_post_test['message'] = 'Exception during post creation/retrieval: ' . $e->getMessage();
        $results['failed']++;
    }

    // Clean up - remove test post if it was created
    if ($post_id) {
        wp_delete_post($post_id, true);
    }

    $results['tests'][] = $create_post_test;
    $results['total']++;

    // Test 9: Update post
    $update_post_test = [
        'name' => 'WordPress API Tool Update Post',
        'result' => 'failed',
        'message' => ''
    ];

    $post_id = null;
    try {
        // Create a test post
        $test_title = 'MPAI Update Test Post ' . time();
        $result = $wp_api_tool->execute([
            'action' => 'create_post',
            'title' => $test_title,
            'content' => 'This is a test post for updating.',
            'status' => 'draft'
        ]);
        
        if (is_array($result) && isset($result['success']) && $result['success'] === true && isset($result['post_id'])) {
            $post_id = $result['post_id'];
            
            // Now try to update the post
            $updated_title = $test_title . ' (Updated)';
            $update_result = $wp_api_tool->execute([
                'action' => 'update_post',
                'post_id' => $post_id,
                'title' => $updated_title
            ]);
            
            if (is_array($update_result) && isset($update_result['success']) && $update_result['success'] === true && 
                isset($update_result['post']) && $update_result['post']['post_title'] === $updated_title) {
                
                $update_post_test['result'] = 'passed';
                $update_post_test['message'] = "Tool correctly updated post with ID: $post_id";
                $results['passed']++;
            } else {
                $update_post_test['message'] = 'Tool failed to update test post';
                $results['failed']++;
            }
        } else {
            $update_post_test['message'] = 'Tool failed to create test post for update';
            $results['failed']++;
        }
    } catch (Exception $e) {
        $update_post_test['message'] = 'Exception during post update: ' . $e->getMessage();
        $results['failed']++;
    }

    // Clean up - remove test post if it was created
    if ($post_id) {
        wp_delete_post($post_id, true);
    }

    $results['tests'][] = $update_post_test;
    $results['total']++;

    // Test 10: Edge case - create post with empty title
    $empty_title_test = [
        'name' => 'WordPress API Tool Create Post with Empty Title',
        'result' => 'failed',
        'message' => ''
    ];

    $post_id = null;
    try {
        $result = $wp_api_tool->execute([
            'action' => 'create_post',
            'title' => '', // Empty title
            'content' => 'This is a test post with an empty title.',
            'status' => 'draft'
        ]);
        
        if (is_array($result) && isset($result['success']) && $result['success'] === true && isset($result['post_id'])) {
            $post_id = $result['post_id'];
            
            // Check if the post was created with a default title
            $post = get_post($post_id);
            if ($post && !empty($post->post_title)) {
                $empty_title_test['result'] = 'passed';
                $empty_title_test['message'] = "Tool correctly handled empty title case with default title: " . $post->post_title;
                $results['passed']++;
            } else {
                $empty_title_test['message'] = 'Tool created post with empty title';
                $results['failed']++;
            }
        } else {
            $empty_title_test['message'] = 'Tool failed to create test post with empty title';
            $results['failed']++;
        }
    } catch (Exception $e) {
        $empty_title_test['message'] = 'Exception during post creation with empty title: ' . $e->getMessage();
        $results['failed']++;
    }

    // Clean up - remove test post if it was created
    if ($post_id) {
        wp_delete_post($post_id, true);
    }

    $results['tests'][] = $empty_title_test;
    $results['total']++;

    // Test 11: Create page (when MemberPress installed)
    $create_page_test = [
        'name' => 'WordPress API Tool Create Page',
        'result' => 'failed',
        'message' => ''
    ];

    $page_id = null;
    try {
        $test_title = 'MPAI Test Page ' . time();
        $result = $wp_api_tool->execute([
            'action' => 'create_page',
            'title' => $test_title,
            'content' => 'This is a test page created by the integration test.',
            'status' => 'draft'
        ]);
        
        if (is_array($result) && isset($result['success']) && $result['success'] === true && isset($result['post_id'])) {
            $page_id = $result['post_id'];
            
            // Verify it's a page
            $page = get_post($page_id);
            if ($page && $page->post_type === 'page') {
                $create_page_test['result'] = 'passed';
                $create_page_test['message'] = "Tool correctly created page with ID: $page_id";
                $results['passed']++;
            } else {
                $create_page_test['message'] = 'Tool created post but not with page post type';
                $results['failed']++;
            }
        } else {
            $create_page_test['message'] = 'Tool failed to create test page';
            $results['failed']++;
        }
    } catch (Exception $e) {
        $create_page_test['message'] = 'Exception during page creation: ' . $e->getMessage();
        $results['failed']++;
    }

    // Clean up - remove test page if it was created
    if ($page_id) {
        wp_delete_post($page_id, true);
    }

    $results['tests'][] = $create_page_test;
    $results['total']++;

    // Test 12: Activate/Deactivate plugin (if current user has capability)
    if (current_user_can('activate_plugins')) {
        $plugin_test = [
            'name' => 'WordPress API Tool Plugin Activation/Deactivation',
            'result' => 'pending',
            'message' => 'Checking for test plugin'
        ];

        try {
            // Get available plugins
            $all_plugins = get_plugins();
            $test_plugin = null;
            
            // Load plugin functions if needed 
            if (!function_exists('is_plugin_active')) {
                $plugin_php_path = ABSPATH . 'wp-admin/includes/plugin.php';
                if (file_exists($plugin_php_path)) {
                    require_once $plugin_php_path;
                } else {
                    // Try alternative path
                    $alt_path = WP_PLUGIN_DIR . '/../../../wp-admin/includes/plugin.php';
                    if (file_exists($alt_path)) {
                        require_once $alt_path;
                    } else {
                        throw new Exception('Could not load required plugin.php file');
                    }
                }
            }
            
            // Define a list of safe test plugins in order of preference
            $safe_test_plugins = [
                'hello.php', // Hello Dolly
                'akismet/akismet.php', // Akismet
                'health-check/health-check.php', // Health Check
                'query-monitor/query-monitor.php', // Query Monitor
            ];
            
            // Try to find a safe test plugin
            $test_plugin = null;
            
            // First, check our safe list
            foreach ($safe_test_plugins as $plugin_file) {
                if (isset($all_plugins[$plugin_file])) {
                    $test_plugin = $plugin_file;
                    error_log('MPAI Test: Found safe test plugin: ' . $plugin_file);
                    break;
                }
            }
            
            // If no safe plugin found, look for any non-critical plugin
            if (!$test_plugin) {
                foreach ($all_plugins as $plugin_file => $plugin_data) {
                    // Skip MemberPress and this plugin to avoid disrupting the test
                    if (
                        stripos($plugin_file, 'memberpress') === false && 
                        stripos($plugin_file, 'memberpress-ai-assistant') === false &&
                        stripos($plugin_file, 'woocommerce') === false &&
                        $plugin_file !== 'index.php'
                    ) {
                        $test_plugin = $plugin_file;
                        error_log('MPAI Test: Using non-critical plugin: ' . $plugin_file);
                        break;
                    }
                }
            }
            
            // Final fallback - just test error handling if no suitable plugin found
            if (!$test_plugin) {
                error_log('MPAI Test: No suitable plugin found, testing parameter validation only');
                
                // Create a test that validates the parameter checking
                $plugin_test['result'] = 'pending';
                $plugin_test['message'] = "Testing parameter validation only - no suitable test plugin available";
                
                // Test invalid parameter handling
                try {
                    // Try with empty plugin parameter (should trigger error)
                    $wp_api_tool->execute([
                        'action' => 'activate_plugin',
                        'plugin' => ''
                    ]);
                    
                    // If we get here, it didn't throw an exception as expected
                    $plugin_test['result'] = 'failed';
                    $plugin_test['message'] = "Tool failed to reject empty plugin parameter";
                    $results['failed']++;
                } catch (Exception $e) {
                    // Expected behavior - it should reject empty plugin parameter
                    if (strpos($e->getMessage(), 'Plugin parameter is required') !== false) {
                        $plugin_test['result'] = 'passed';
                        $plugin_test['message'] = "Tool correctly rejected empty plugin parameter";
                        $results['passed']++;
                    } else {
                        $plugin_test['result'] = 'failed';
                        $plugin_test['message'] = "Tool threw unexpected error: " . $e->getMessage();
                        $results['failed']++;
                    }
                }
            }
            
            if ($test_plugin) {
                error_log('MPAI Test: Using plugin "' . $test_plugin . '" for activation/deactivation test');
                // Remember initial plugin state
                $initially_active = is_plugin_active($test_plugin);
                
                if ($initially_active) {
                    // Test deactivation
                    $result = $wp_api_tool->execute([
                        'action' => 'deactivate_plugin',
                        'plugin' => $test_plugin
                    ]);
                    
                    if (
                        is_array($result) && 
                        isset($result['success']) && 
                        $result['success'] === true && 
                        !is_plugin_active($test_plugin)
                    ) {
                        // Now reactivate it
                        $activate_result = $wp_api_tool->execute([
                            'action' => 'activate_plugin',
                            'plugin' => $test_plugin
                        ]);
                        
                        if (
                            is_array($activate_result) && 
                            isset($activate_result['success']) && 
                            $activate_result['success'] === true && 
                            is_plugin_active($test_plugin)
                        ) {
                            $plugin_test['result'] = 'passed';
                            $plugin_test['message'] = "Tool correctly deactivated and reactivated plugin: " . $test_plugin;
                            $results['passed']++;
                        } else {
                            $plugin_test['result'] = 'failed';
                            $plugin_test['message'] = "Tool deactivated plugin but failed to reactivate it";
                            $results['failed']++;
                            
                            // Try to reactivate the plugin to restore initial state
                            activate_plugin($test_plugin);
                        }
                    } else {
                        $plugin_test['result'] = 'failed';
                        $plugin_test['message'] = "Tool failed to deactivate plugin: " . $test_plugin;
                        $results['failed']++;
                    }
                } else {
                    // Test activation
                    $result = $wp_api_tool->execute([
                        'action' => 'activate_plugin',
                        'plugin' => $test_plugin
                    ]);
                    
                    if (
                        is_array($result) && 
                        isset($result['success']) && 
                        $result['success'] === true && 
                        is_plugin_active($test_plugin)
                    ) {
                        // Now deactivate it
                        $deactivate_result = $wp_api_tool->execute([
                            'action' => 'deactivate_plugin',
                            'plugin' => $test_plugin
                        ]);
                        
                        if (
                            is_array($deactivate_result) && 
                            isset($deactivate_result['success']) && 
                            $deactivate_result['success'] === true && 
                            !is_plugin_active($test_plugin)
                        ) {
                            $plugin_test['result'] = 'passed';
                            $plugin_test['message'] = "Tool correctly activated and deactivated plugin: " . $test_plugin;
                            $results['passed']++;
                        } else {
                            $plugin_test['result'] = 'failed';
                            $plugin_test['message'] = "Tool activated plugin but failed to deactivate it";
                            $results['failed']++;
                            
                            // Try to deactivate the plugin to restore initial state
                            deactivate_plugins($test_plugin);
                        }
                    } else {
                        $plugin_test['result'] = 'failed';
                        $plugin_test['message'] = "Tool failed to activate plugin: " . $test_plugin;
                        $results['failed']++;
                    }
                }
            } else {
                $plugin_test['result'] = 'skipped';
                $plugin_test['message'] = "No suitable test plugin found";
                // Don't count skipped tests in passed or failed
            }
        } catch (Exception $e) {
            $plugin_test['result'] = 'failed';
            $plugin_test['message'] = 'Exception during plugin activation/deactivation: ' . $e->getMessage();
            $results['failed']++;
        }

        $results['tests'][] = $plugin_test;
        if ($plugin_test['result'] !== 'skipped') {
            $results['total']++;
        }
    }

    // Check if MemberPress is active for MemberPress-specific tests
    $is_memberpress_active = class_exists('MeprOptions');
    
    if ($is_memberpress_active) {
        // Test 13: Get MemberPress memberships
        $get_memberships_test = [
            'name' => 'WordPress API Tool Get MemberPress Memberships',
            'result' => 'failed',
            'message' => ''
        ];

        try {
            $result = $wp_api_tool->execute([
                'action' => 'get_memberships'
            ]);
            
            if (is_array($result) && isset($result['success']) && $result['success'] === true && isset($result['memberships'])) {
                $get_memberships_test['result'] = 'passed';
                $get_memberships_test['message'] = "Tool correctly executed get_memberships action, found " . count($result['memberships']) . " memberships";
                $results['passed']++;
            } else {
                $get_memberships_test['message'] = 'Tool execution for get_memberships returned invalid format';
                $results['failed']++;
            }
        } catch (Exception $e) {
            $get_memberships_test['message'] = 'Exception executing get_memberships action: ' . $e->getMessage();
            $results['failed']++;
        }

        $results['tests'][] = $get_memberships_test;
        $results['total']++;
    }

    return $results;
}

/**
 * Run and display WordPress API tool tests
 */
function mpai_run_wp_api_tool_tests() {
    $results = mpai_test_wp_api_tool();
    
    echo '<h3>WordPress API Tool Integration Tests</h3>';
    echo '<div class="mpai-test-results">';
    echo '<p>Tests Run: ' . $results['total'] . ', ';
    echo 'Passed: ' . $results['passed'] . ', ';
    echo 'Failed: ' . $results['failed'] . '</p>';
    
    echo '<table class="mpai-test-table">';
    echo '<thead><tr><th>Test</th><th>Result</th><th>Message</th></tr></thead>';
    echo '<tbody>';
    
    foreach ($results['tests'] as $test) {
        $result_class = 'test-' . $test['result'];
        echo '<tr class="' . $result_class . '">';
        echo '<td>' . esc_html($test['name']) . '</td>';
        echo '<td>' . esc_html(ucfirst($test['result'])) . '</td>';
        echo '<td>' . esc_html($test['message']) . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody></table>';
    echo '</div>';
    
    return $results;
}