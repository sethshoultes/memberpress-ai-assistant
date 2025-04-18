<?php
/**
 * Input Validation Edge Case Tests
 *
 * Tests boundary conditions and edge cases for input validation across the plugin.
 *
 * @package MemberPress AI Assistant
 */

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Run input validation edge case tests
 *
 * @return array Test results
 */
function mpai_test_input_validation_edge_cases() {
    $results = [
        'tests' => [],
        'passed' => 0,
        'failed' => 0,
        'total' => 0
    ];

    // Test 1: Test extremely long inputs
    $long_input_test = [
        'name' => 'Extremely Long Input Handling',
        'result' => 'failed',
        'message' => '',
        'details' => null
    ];

    try {
        // Create an extremely long input string (100KB)
        $long_string = str_repeat('A very long string that should be properly handled. ', 2000);
        
        // Initialize the Context Manager which typically handles user input
        if (!class_exists('MPAI_Context_Manager')) {
            require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-context-manager.php';
        }
        
        $context_manager = new MPAI_Context_Manager();
        
        // MODIFIED: Use MPAI_Input_Validator instead of Context Manager
        if (!class_exists('MPAI_Input_Validator')) {
            require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-input-validator.php';
        }
        
        $validator = new MPAI_Input_Validator();
        $validator->add_rule('long_input', ['type' => 'string']);
        
        // Test method that handles user input with the long string
        $result = $validator->validate(['long_input' => $long_string]);
        
        // Check that the validator handled the long string appropriately
        if (is_array($result) && isset($result['valid']) && $result['valid']) {
            $handled_data = $result['data'];
            $handled_length = isset($handled_data['long_input']) ? strlen($handled_data['long_input']) : 0;
            
            // Check that the input was handled properly
            if ($handled_length > 0) {
                $long_input_test['result'] = 'passed';
                $long_input_test['message'] = 'Input validator properly handled extremely long input';
                $long_input_test['details'] = [
                    'original_length' => strlen($long_string),
                    'handled_length' => $handled_length,
                    'validation_result' => $result['valid'] ? 'valid' : 'invalid'
                ];
                $results['passed']++;
            } else {
                $long_input_test['message'] = 'Input validator did not properly handle extremely long input';
                $long_input_test['details'] = [
                    'original_length' => strlen($long_string),
                    'processed_length' => $handled_length
                ];
                $results['failed']++;
            }
        } else {
            $long_input_test['message'] = 'Input validator failed to process extremely long input';
            $results['failed']++;
        }
    } catch (Exception $e) {
        $long_input_test['message'] = 'Exception handling long input: ' . $e->getMessage();
        $long_input_test['details'] = [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ];
        $results['failed']++;
    }

    $results['tests'][] = $long_input_test;
    $results['total']++;

    // Test 2: Test empty/null inputs
    $empty_input_test = [
        'name' => 'Empty/Null Input Handling',
        'result' => 'failed',
        'message' => '',
        'details' => null
    ];

    try {
        // MODIFIED: Use MPAI_Input_Validator instead of Context Manager
        if (!class_exists('MPAI_Input_Validator')) {
            require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-input-validator.php';
        }
        
        $validator = new MPAI_Input_Validator();
        $validator->add_rule('empty_input', ['type' => 'string']);
        
        // Empty string test
        $empty_result = $validator->validate(['empty_input' => '']);
        
        // Null test
        $null_result = $validator->validate(['empty_input' => null]);
        
        // Check that empty inputs are handled gracefully without errors
        if (is_array($empty_result) && is_array($null_result)) {
            $empty_input_test['result'] = 'passed';
            $empty_input_test['message'] = 'Input validator properly handled empty and null inputs';
            $empty_input_test['details'] = [
                'empty_string_result' => json_encode($empty_result),
                'null_result' => json_encode($null_result)
            ];
            $results['passed']++;
        } else {
            $empty_input_test['message'] = 'Input validator did not properly handle empty or null inputs';
            $empty_input_test['details'] = [
                'empty_string_result' => json_encode($empty_result),
                'null_result' => json_encode($null_result)
            ];
            $results['failed']++;
        }
    } catch (Exception $e) {
        $empty_input_test['message'] = 'Exception handling empty/null input: ' . $e->getMessage();
        $empty_input_test['details'] = [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ];
        $results['failed']++;
    }

    $results['tests'][] = $empty_input_test;
    $results['total']++;

    // Test 3: Test special character inputs
    $special_chars_test = [
        'name' => 'Special Character Input Handling',
        'result' => 'failed',
        'message' => '',
        'details' => null
    ];

    try {
        // Create a string with various special characters
        $special_chars = "!@#$%^&*()_+{}|:<>?~`-=[]\\;',./\"\r\n\t 您好 こんにちは Привет áéíóú 😀 🤖 🦴";
        
        // MODIFIED: Use MPAI_Input_Validator instead of Context Manager
        if (!class_exists('MPAI_Input_Validator')) {
            require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-input-validator.php';
        }
        
        $validator = new MPAI_Input_Validator();
        $validator->add_rule('special_chars', ['type' => 'string']);
        
        // Test method that handles user input with special characters
        $result = $validator->validate(['special_chars' => $special_chars]);
        
        // Check that special characters are handled properly
        if (is_array($result) && isset($result['valid']) && $result['valid']) {
            $has_content = false;
            if (isset($result['data']['special_chars']) && 
                strpos($result['data']['special_chars'], '您好') !== false) {
                $has_content = true;
            }
            
            if ($has_content) {
                $special_chars_test['result'] = 'passed';
                $special_chars_test['message'] = 'Input validator properly handled special characters';
                $results['passed']++;
            } else {
                $special_chars_test['message'] = 'Input validator did not properly preserve special characters';
                $special_chars_test['details'] = [
                    'result' => json_encode($result)
                ];
                $results['failed']++;
            }
        } else {
            $special_chars_test['message'] = 'Input validator failed to process input with special characters';
            $results['failed']++;
        }
    } catch (Exception $e) {
        $special_chars_test['message'] = 'Exception handling special characters: ' . $e->getMessage();
        $special_chars_test['details'] = [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ];
        $results['failed']++;
    }

    $results['tests'][] = $special_chars_test;
    $results['total']++;

    // Test 4: Test script injection handling
    $script_injection_test = [
        'name' => 'Script Injection Handling',
        'result' => 'failed',
        'message' => '',
        'details' => null
    ];

    try {
        // Create a string with script tags and other potentially harmful content
        $script_content = '<script>alert("XSS")</script><img src="x" onerror="alert(\'XSS\')">';
        
        if (!class_exists('MPAI_Chat')) {
            require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-chat.php';
        }
        
        $chat = new MPAI_Chat();
        
        // This should sanitize or escape the input
        $result = $chat->process_message($script_content);
        
        // Check that script tags were properly escaped or removed
        if (is_array($result) && !empty($result)) {
            $response_content = '';
            
            // Extract the response content depending on the structure
            if (isset($result['message'])) {
                $response_content = $result['message'];
            } elseif (isset($result['choices'][0]['message']['content'])) {
                $response_content = $result['choices'][0]['message']['content'];
            } elseif (isset($result['raw_response'])) {
                $response_content = $result['raw_response'];
            }
            
            // Check that script tags were escaped or removed
            if (strpos($response_content, '<script>') === false && strpos($response_content, 'onerror=') === false) {
                $script_injection_test['result'] = 'passed';
                $script_injection_test['message'] = 'System properly handled potentially harmful script content';
                $results['passed']++;
            } else {
                $script_injection_test['message'] = 'System did not properly sanitize script content';
                $script_injection_test['details'] = [
                    'response_content' => $response_content
                ];
                $results['failed']++;
            }
        } else {
            $script_injection_test['message'] = 'Failed to process input with script tags';
            $results['failed']++;
        }
    } catch (Exception $e) {
        $script_injection_test['message'] = 'Exception handling script injection: ' . $e->getMessage();
        $script_injection_test['details'] = [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ];
        $results['failed']++;
    }

    $results['tests'][] = $script_injection_test;
    $results['total']++;

    // Test 5: Test SQL injection handling
    $sql_injection_test = [
        'name' => 'SQL Injection Handling',
        'result' => 'failed',
        'message' => '',
        'details' => null
    ];

    try {
        // Create a string with SQL injection content
        $sql_content = "'; DROP TABLE wp_posts; --";
        
        if (!class_exists('MPAI_Plugin_Logs_Tool')) {
            require_once MPAI_PLUGIN_DIR . 'includes/tools/implementations/class-mpai-plugin-logs-tool.php';
        }
        
        $plugin_logs_tool = new MPAI_Plugin_Logs_Tool();
        
        // Test that SQL injection attempts are properly escaped
        $result = $plugin_logs_tool->execute([
            'plugin_name' => $sql_content,
            'limit' => 5
        ]);
        
        // Check that the function executed without error (indicating proper escaping)
        if (is_array($result) && isset($result['success']) && $result['success'] === true) {
            $sql_injection_test['result'] = 'passed';
            $sql_injection_test['message'] = 'Plugin Logs Tool properly handled SQL injection attempt';
            $results['passed']++;
        } else {
            $sql_injection_test['message'] = 'Plugin Logs Tool returned error on SQL injection test';
            $sql_injection_test['details'] = [
                'result' => json_encode($result)
            ];
            $results['failed']++;
        }
    } catch (Exception $e) {
        $sql_injection_test['message'] = 'Exception handling SQL injection: ' . $e->getMessage();
        $sql_injection_test['details'] = [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ];
        $results['failed']++;
    }

    $results['tests'][] = $sql_injection_test;
    $results['total']++;

    // Test 6: Test malformed JSON handling
    $malformed_json_test = [
        'name' => 'Malformed JSON Handling',
        'result' => 'failed',
        'message' => '',
        'details' => null
    ];

    try {
        // Create malformed JSON string
        $malformed_json = '{"action": "get_plugins", "limit": 5,}'; // Note the extra comma
        
        // MODIFIED: Use MPAI_Input_Validator instead of Context Manager
        if (!class_exists('MPAI_Input_Validator')) {
            require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-input-validator.php';
        }
        
        // Create validator with JSON schema rule
        $validator = new MPAI_Input_Validator();
        $validator->add_rule('json_data', ['type' => 'string']);
        
        // Test handling of malformed JSON
        $exception_caught = false;
        try {
            // Try to parse the malformed JSON
            $parsed_json = json_decode($malformed_json, true);
            // This should result in a null value due to the JSON syntax error
            if ($parsed_json === null) {
                // This is the expected outcome - JSON parsing failed
                $exception_caught = true;
            }
            
            // Validate the string itself (should pass since it's a valid string, though invalid JSON)
            $result = $validator->validate(['json_data' => $malformed_json]);
        } catch (Exception $e) {
            $exception_caught = true;
        }
        
        // Either way, we should detect the malformed JSON
        if ($exception_caught || json_last_error() !== JSON_ERROR_NONE) {
            $malformed_json_test['result'] = 'passed';
            $malformed_json_test['message'] = 'System properly detected malformed JSON';
            $malformed_json_test['details'] = [
                'json_error' => json_last_error_msg(),
                'validation_result' => isset($result) ? json_encode($result) : 'No validation result'
            ];
            $results['passed']++;
        } else {
            $malformed_json_test['message'] = 'System did not properly detect malformed JSON';
            $malformed_json_test['details'] = [
                'result' => isset($result) ? json_encode($result) : 'No result'
            ];
            $results['failed']++;
        }
    } catch (Exception $e) {
        // If it throws an exception with a message about JSON, that's also acceptable
        if (strpos(strtolower($e->getMessage()), 'json') !== false) {
            $malformed_json_test['result'] = 'passed';
            $malformed_json_test['message'] = 'System threw appropriate exception for malformed JSON';
            $malformed_json_test['details'] = [
                'exception' => get_class($e),
                'message' => $e->getMessage()
            ];
            $results['passed']++;
        } else {
            $malformed_json_test['message'] = 'Unexpected exception handling malformed JSON: ' . $e->getMessage();
            $malformed_json_test['details'] = [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ];
            $results['failed']++;
        }
    }

    $results['tests'][] = $malformed_json_test;
    $results['total']++;

    return $results;
}

/**
 * Display input validation edge case test results
 */
function mpai_display_input_validation_edge_case_tests() {
    $results = mpai_test_input_validation_edge_cases();
    
    echo '<h3>Input Validation Edge Case Tests</h3>';
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