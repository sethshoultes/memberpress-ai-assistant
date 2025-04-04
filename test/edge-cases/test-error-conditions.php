<?php
/**
 * Error Conditions Edge Case Tests
 *
 * Tests system handling of various error conditions and edge cases.
 *
 * @package MemberPress AI Assistant
 */

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Run error conditions edge case tests
 *
 * @return array Test results
 */
function mpai_test_error_conditions_edge_cases() {
    $results = [
        'tests' => [],
        'passed' => 0,
        'failed' => 0,
        'total' => 0
    ];

    // Test 1: Test API authentication error handling
    $api_auth_error_test = [
        'name' => 'API Authentication Error Handling',
        'result' => 'failed',
        'message' => '',
        'details' => null
    ];

    try {
        // We'll use the Error Recovery System to handle API authentication errors
        if (!class_exists('MPAI_Error_Recovery')) {
            require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-error-recovery.php';
        }
        
        if (!function_exists('mpai_init_error_recovery')) {
            function mpai_init_error_recovery() {
                return new MPAI_Error_Recovery();
            }
        }
        
        $recovery_system = mpai_init_error_recovery();
        
        // Simulate authentication errors for different APIs
        $openai_auth_error = new Exception('Incorrect API key provided. You can find your API key at https://platform.openai.com/account/api-keys.');
        $anthropic_auth_error = new Exception('Authentication error: Invalid API key.');
        
        // Attempt to recover from the authentication errors
        $openai_recovery = $recovery_system->handle_api_error($openai_auth_error, 'openai_completion', ['model' => 'gpt-4']);
        $anthropic_recovery = $recovery_system->handle_api_error($anthropic_auth_error, 'anthropic_completion', ['model' => 'claude-3-opus-20240229']);
        
        // Check if the errors were correctly identified as authentication issues
        $openai_is_auth_error = false;
        $anthropic_is_auth_error = false;
        
        // For OpenAI
        if (is_array($openai_recovery) && isset($openai_recovery['error_type'])) {
            $openai_is_auth_error = stripos($openai_recovery['error_type'], 'auth') !== false || 
                                  stripos($openai_recovery['error_type'], 'key') !== false;
        }
        
        // For Anthropic
        if (is_array($anthropic_recovery) && isset($anthropic_recovery['error_type'])) {
            $anthropic_is_auth_error = stripos($anthropic_recovery['error_type'], 'auth') !== false || 
                                     stripos($anthropic_recovery['error_type'], 'key') !== false;
        }
        
        if ($openai_is_auth_error || $anthropic_is_auth_error) {
            $api_auth_error_test['result'] = 'passed';
            $api_auth_error_test['message'] = 'Error Recovery System correctly identified authentication errors';
            $api_auth_error_test['details'] = [
                'openai_recovery' => $openai_recovery,
                'anthropic_recovery' => $anthropic_recovery,
                'openai_is_auth_error' => $openai_is_auth_error,
                'anthropic_is_auth_error' => $anthropic_is_auth_error
            ];
            $results['passed']++;
        } else {
            $api_auth_error_test['message'] = 'Error Recovery System did not correctly identify authentication errors';
            $api_auth_error_test['details'] = [
                'openai_recovery' => $openai_recovery,
                'anthropic_recovery' => $anthropic_recovery,
                'openai_is_auth_error' => $openai_is_auth_error,
                'anthropic_is_auth_error' => $anthropic_is_auth_error
            ];
            $results['failed']++;
        }
    } catch (Exception $e) {
        $api_auth_error_test['message'] = 'Exception testing API authentication error handling: ' . $e->getMessage();
        $api_auth_error_test['details'] = [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ];
        $results['failed']++;
    }

    $results['tests'][] = $api_auth_error_test;
    $results['total']++;

    // Test 2: Test API unavailable error handling
    $api_unavailable_test = [
        'name' => 'API Unavailable Error Handling',
        'result' => 'failed',
        'message' => '',
        'details' => null
    ];

    try {
        // We'll use the Error Recovery System to handle API connectivity errors
        if (!class_exists('MPAI_Error_Recovery')) {
            require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-error-recovery.php';
        }
        
        if (!function_exists('mpai_init_error_recovery')) {
            function mpai_init_error_recovery() {
                return new MPAI_Error_Recovery();
            }
        }
        
        $recovery_system = mpai_init_error_recovery();
        
        // Simulate different types of connectivity errors
        $connection_error = new Exception('cURL error 6: Could not resolve host');
        $server_error = new Exception('Server error: 502 Bad Gateway');
        $service_down = new Exception('Service temporarily unavailable');
        
        // Attempt to recover from the errors
        $connection_recovery = $recovery_system->handle_api_error($connection_error, 'api_call', ['model' => 'gpt-4']);
        $server_recovery = $recovery_system->handle_api_error($server_error, 'api_call', ['model' => 'gpt-4']);
        $service_recovery = $recovery_system->handle_api_error($service_down, 'api_call', ['model' => 'gpt-4']);
        
        // Check if at least one error type was handled correctly with a retry or fallback strategy
        $has_retry_or_fallback = false;
        
        foreach ([$connection_recovery, $server_recovery, $service_recovery] as $recovery) {
            if (is_array($recovery) && isset($recovery['status'])) {
                $has_retry = strpos(json_encode($recovery), 'retry') !== false;
                $has_fallback = strpos(json_encode($recovery), 'fallback') !== false;
                
                if ($has_retry || $has_fallback) {
                    $has_retry_or_fallback = true;
                    break;
                }
            }
        }
        
        if ($has_retry_or_fallback) {
            $api_unavailable_test['result'] = 'passed';
            $api_unavailable_test['message'] = 'Error Recovery System provided retry or fallback strategy for API unavailability';
            $api_unavailable_test['details'] = [
                'connection_recovery' => $connection_recovery,
                'server_recovery' => $server_recovery,
                'service_recovery' => $service_recovery
            ];
            $results['passed']++;
        } else {
            $api_unavailable_test['message'] = 'Error Recovery System did not provide retry or fallback strategy for API unavailability';
            $api_unavailable_test['details'] = [
                'connection_recovery' => $connection_recovery,
                'server_recovery' => $server_recovery,
                'service_recovery' => $service_recovery
            ];
            $results['failed']++;
        }
    } catch (Exception $e) {
        $api_unavailable_test['message'] = 'Exception testing API unavailable error handling: ' . $e->getMessage();
        $api_unavailable_test['details'] = [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ];
        $results['failed']++;
    }

    $results['tests'][] = $api_unavailable_test;
    $results['total']++;

    // Test 3: Test Tool Execution Failure Handling
    $tool_failure_test = [
        'name' => 'Tool Execution Failure Handling',
        'result' => 'failed',
        'message' => '',
        'details' => null
    ];

    try {
        // We'll test how the system handles tool execution failures
        if (!class_exists('MPAI_Context_Manager')) {
            require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-context-manager.php';
        }
        
        $context_manager = new MPAI_Context_Manager();
        
        // Try to execute a tool that doesn't exist
        $non_existent_tool_result = $context_manager->execute_tool('non_existent_tool', '{}');
        
        // Try to execute a valid tool with invalid parameters
        $invalid_params_result = $context_manager->execute_tool('wp_api', '{"action": "invalid_action"}');
        
        // Check that both error cases return appropriate error information
        $non_existent_handled = is_array($non_existent_tool_result) && 
                               (isset($non_existent_tool_result['error']) || isset($non_existent_tool_result['success']) && $non_existent_tool_result['success'] === false);
        
        $invalid_params_handled = is_array($invalid_params_result) && 
                                (isset($invalid_params_result['error']) || isset($invalid_params_result['success']) && $invalid_params_result['success'] === false);
        
        if ($non_existent_handled && $invalid_params_handled) {
            $tool_failure_test['result'] = 'passed';
            $tool_failure_test['message'] = 'System properly handled tool execution failures';
            $tool_failure_test['details'] = [
                'non_existent_tool_result' => $non_existent_tool_result,
                'invalid_params_result' => $invalid_params_result
            ];
            $results['passed']++;
        } else {
            $tool_failure_test['message'] = 'System did not properly handle tool execution failures';
            $tool_failure_test['details'] = [
                'non_existent_tool_result' => $non_existent_tool_result,
                'invalid_params_result' => $invalid_params_result,
                'non_existent_handled' => $non_existent_handled,
                'invalid_params_handled' => $invalid_params_handled
            ];
            $results['failed']++;
        }
    } catch (Exception $e) {
        $tool_failure_test['message'] = 'Exception testing tool execution failures: ' . $e->getMessage();
        $tool_failure_test['details'] = [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ];
        $results['failed']++;
    }

    $results['tests'][] = $tool_failure_test;
    $results['total']++;

    // Test 4: Test Database Connection Error Handling
    $db_error_test = [
        'name' => 'Database Connection Error Handling',
        'result' => 'failed',
        'message' => '',
        'details' => null
    ];

    try {
        // We'll simulate a database connection error when using the Plugin Logs Tool
        if (!class_exists('MPAI_Plugin_Logs_Tool')) {
            require_once MPAI_PLUGIN_DIR . 'includes/tools/implementations/class-mpai-plugin-logs-tool.php';
        }
        
        // Create a mock Plugin Logger class that simulates a database error
        class Mock_MPAI_Plugin_Logger {
            public function get_logs($args) {
                throw new Exception('Database connection error: MySQL server has gone away');
            }
            
            public function count_logs($args) {
                throw new Exception('Database connection error: MySQL server has gone away');
            }
            
            public function get_activity_summary($days) {
                throw new Exception('Database connection error: MySQL server has gone away');
            }
        }
        
        // Create a test instance of the Plugin Logs Tool
        $plugin_logs_tool = new MPAI_Plugin_Logs_Tool();
        
        // Save the original mpai_init_plugin_logger function
        $original_function = null;
        if (function_exists('mpai_init_plugin_logger')) {
            $original_function = 'mpai_init_plugin_logger';
        }
        
        // Override the mpai_init_plugin_logger function to return our mock logger
        function mpai_init_plugin_logger() {
            return new Mock_MPAI_Plugin_Logger();
        }
        
        // Execute the tool with the mock logger
        $result = $plugin_logs_tool->execute(['limit' => 5]);
        
        // Restore the original function if needed
        if ($original_function) {
            function mpai_init_plugin_logger() {
                global $original_function;
                return call_user_func($original_function);
            }
        }
        
        // Check that the tool handled the database error gracefully
        if (is_array($result) && isset($result['success']) && $result['success'] === false && isset($result['message'])) {
            $db_error_test['result'] = 'passed';
            $db_error_test['message'] = 'Plugin Logs Tool gracefully handled database connection error';
            $db_error_test['details'] = [
                'error_result' => $result
            ];
            $results['passed']++;
        } else {
            $db_error_test['message'] = 'Plugin Logs Tool did not handle database connection error properly';
            $db_error_test['details'] = [
                'result' => $result
            ];
            $results['failed']++;
        }
    } catch (Exception $e) {
        // If the error was not caught by the tool and bubbled up to here, that's a failure
        $db_error_test['message'] = 'Uncaught exception during database error test: ' . $e->getMessage();
        $db_error_test['details'] = [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ];
        $results['failed']++;
    }

    $results['tests'][] = $db_error_test;
    $results['total']++;

    // Test 5: Test State Corruption Handling
    $state_corruption_test = [
        'name' => 'State Corruption Handling',
        'result' => 'failed',
        'message' => '',
        'details' => null
    ];

    try {
        // We'll test how the State Validation System handles state corruption
        if (!class_exists('MPAI_State_Validator')) {
            require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-state-validator.php';
        }
        
        if (!function_exists('mpai_init_state_validator')) {
            function mpai_init_state_validator() {
                return new MPAI_State_Validator();
            }
        }
        
        $validator = mpai_init_state_validator();
        
        // Create a test component with invalid state
        $test_component = new stdClass();
        $test_component->name = 'test_component';
        $test_component->status = 'invalid'; // Invalid status
        
        // Define validation rules
        $rules = [
            'status' => ['active', 'inactive', 'pending'] // Valid status values
        ];
        
        // Validate the component
        $validation_result = $validator->validate_component('test', $test_component, $rules);
        
        // Check that the validation correctly identified the state corruption
        if ($validation_result === false) {
            $state_corruption_test['result'] = 'passed';
            $state_corruption_test['message'] = 'State Validator correctly detected state corruption';
            $results['passed']++;
        } else {
            $state_corruption_test['message'] = 'State Validator failed to detect state corruption';
            $state_corruption_test['details'] = [
                'validation_result' => $validation_result
            ];
            $results['failed']++;
        }
    } catch (Exception $e) {
        // Some validators might throw exceptions for invalid state
        if (strpos(strtolower($e->getMessage()), 'validation') !== false || 
            strpos(strtolower($e->getMessage()), 'state') !== false || 
            strpos(strtolower($e->getMessage()), 'invalid') !== false) {
            
            $state_corruption_test['result'] = 'passed';
            $state_corruption_test['message'] = 'State Validator threw appropriate exception for state corruption';
            $state_corruption_test['details'] = [
                'exception_message' => $e->getMessage()
            ];
            $results['passed']++;
        } else {
            $state_corruption_test['message'] = 'Unexpected exception during state validation: ' . $e->getMessage();
            $state_corruption_test['details'] = [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ];
            $results['failed']++;
        }
    }

    $results['tests'][] = $state_corruption_test;
    $results['total']++;

    // Test 6: Test Invalid API Response Handling
    $invalid_response_test = [
        'name' => 'Invalid API Response Handling',
        'result' => 'failed',
        'message' => '',
        'details' => null
    ];

    try {
        // We'll test how the API Response Validation handles invalid responses
        if (!class_exists('MPAI_Chat')) {
            require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-chat.php';
        }
        
        $chat = new MPAI_Chat();
        
        // Access the protected validate_response method via reflection
        $reflection = new ReflectionClass($chat);
        $validate_method = null;
        
        // Look for response validation methods
        foreach (['validate_response', 'validate_api_response', 'check_response'] as $method_name) {
            if ($reflection->hasMethod($method_name)) {
                $validate_method = $reflection->getMethod($method_name);
                $validate_method->setAccessible(true);
                break;
            }
        }
        
        if ($validate_method) {
            // Create invalid response examples
            $empty_response = [];
            $malformed_response = ['choices' => 'not an array'];
            $incomplete_response = ['choices' => []];
            
            // Test validation
            $empty_result = $validate_method->invoke($chat, $empty_response);
            $malformed_result = $validate_method->invoke($chat, $malformed_response);
            $incomplete_result = $validate_method->invoke($chat, $incomplete_response);
            
            // Check that invalid responses were detected
            $validation_success = false;
            
            if (
                ($empty_result === false || (is_array($empty_result) && isset($empty_result['error']))) &&
                ($malformed_result === false || (is_array($malformed_result) && isset($malformed_result['error']))) &&
                ($incomplete_result === false || (is_array($incomplete_result) && isset($incomplete_result['error'])))
            ) {
                $validation_success = true;
            }
            
            if ($validation_success) {
                $invalid_response_test['result'] = 'passed';
                $invalid_response_test['message'] = 'API response validation correctly detected invalid responses';
                $invalid_response_test['details'] = [
                    'empty_result' => $empty_result,
                    'malformed_result' => $malformed_result,
                    'incomplete_result' => $incomplete_result
                ];
                $results['passed']++;
            } else {
                $invalid_response_test['message'] = 'API response validation failed to detect some invalid responses';
                $invalid_response_test['details'] = [
                    'empty_result' => $empty_result,
                    'malformed_result' => $malformed_result,
                    'incomplete_result' => $incomplete_result
                ];
                $results['failed']++;
            }
        } else {
            $invalid_response_test['message'] = 'Could not find response validation method in Chat class';
            $results['failed']++;
        }
    } catch (Exception $e) {
        $invalid_response_test['message'] = 'Exception testing API response validation: ' . $e->getMessage();
        $invalid_response_test['details'] = [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ];
        $results['failed']++;
    }

    $results['tests'][] = $invalid_response_test;
    $results['total']++;

    return $results;
}

/**
 * Display error conditions edge case test results
 */
function mpai_display_error_conditions_edge_case_tests() {
    $results = mpai_test_error_conditions_edge_cases();
    
    echo '<h3>Error Conditions Edge Case Tests</h3>';
    echo '<div class="mpai-test-results">';
    echo '<p>Tests Run: ' . $results['total'] . ', ';
    echo 'Passed: ' . $results['passed'] . ', ';
    echo 'Failed: ' . $results['failed'] . '</p>';
    
    echo '<table class="mpai-test-table">';
    echo '<thead><tr><th>Test</th><th>Result</th><th>Message</th><th>Details</th></tr></thead>';
    echo '<tbody>';
    
    foreach ($results['tests'] as $test) {
        $result_class = $test['result'] === 'passed' ? 'test-passed' : 'test-failed';
        echo '<tr class="' . $result_class . '">';
        echo '<td>' . esc_html($test['name']) . '</td>';
        echo '<td>' . esc_html(ucfirst($test['result'])) . '</td>';
        echo '<td>' . esc_html($test['message']) . '</td>';
        echo '<td>';
        
        if (!empty($test['details'])) {
            echo '<button type="button" class="button toggle-details">Show Details</button>';
            echo '<div class="test-details" style="display:none; margin-top:10px;">';
            echo '<pre>' . esc_html(print_r($test['details'], true)) . '</pre>';
            echo '</div>';
        } else {
            echo 'No details available';
        }
        
        echo '</td>';
        echo '</tr>';
    }
    
    echo '</tbody></table>';
    echo '</div>';
    
    // Add JavaScript to toggle details
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.toggle-details').on('click', function() {
            var $details = $(this).next('.test-details');
            if ($details.is(':visible')) {
                $details.hide();
                $(this).text('Show Details');
            } else {
                $details.show();
                $(this).text('Hide Details');
            }
        });
    });
    </script>
    <?php
    
    return $results;
}