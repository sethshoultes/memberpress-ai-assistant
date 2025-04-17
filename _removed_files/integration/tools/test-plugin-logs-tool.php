<?php
/**
 * Integration tests for Plugin Logs Tool
 *
 * @package MemberPress AI Assistant
 */

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Run integration tests for Plugin Logs Tool
 *
 * @return array Test results
 */
function mpai_test_plugin_logs_tool() {
    $results = [
        'tests' => [],
        'passed' => 0,
        'failed' => 0,
        'total' => 0
    ];

    // Ensure the MPAI_Plugin_Logs_Tool class is loaded
    if (!class_exists('MPAI_Plugin_Logs_Tool')) {
        $tool_path = MPAI_PLUGIN_DIR . 'includes/tools/implementations/class-mpai-plugin-logs-tool.php';
        if (file_exists($tool_path)) {
            require_once $tool_path;
        } else {
            $results['tests'][] = [
                'name' => 'Plugin Logs Tool Class Loading',
                'result' => 'failed',
                'message' => 'Could not find Plugin Logs tool class file'
            ];
            $results['failed']++;
            $results['total']++;
            return $results;
        }
    }

    // Test 1: Tool instance creation
    $instance_test = [
        'name' => 'Plugin Logs Tool Instance Creation',
        'result' => 'failed',
        'message' => ''
    ];

    try {
        $plugin_logs_tool = new MPAI_Plugin_Logs_Tool();
        if ($plugin_logs_tool instanceof MPAI_Plugin_Logs_Tool) {
            $instance_test['result'] = 'passed';
            $instance_test['message'] = 'Successfully created Plugin Logs tool instance';
            $results['passed']++;
        } else {
            $instance_test['message'] = 'Failed to create Plugin Logs tool instance';
            $results['failed']++;
        }
    } catch (Exception $e) {
        $instance_test['message'] = 'Exception creating Plugin Logs tool instance: ' . $e->getMessage();
        $results['failed']++;
    }

    $results['tests'][] = $instance_test;
    $results['total']++;

    // Test 2: Tool properties
    $properties_test = [
        'name' => 'Plugin Logs Tool Properties',
        'result' => 'failed',
        'message' => ''
    ];

    try {
        $name = $plugin_logs_tool->get_name();
        $description = $plugin_logs_tool->get_description();

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

    // Test 3: Tool definition for AI function calling
    $definition_test = [
        'name' => 'Plugin Logs Tool Definition',
        'result' => 'failed',
        'message' => ''
    ];

    try {
        if (method_exists($plugin_logs_tool, 'get_tool_definition')) {
            $definition = $plugin_logs_tool->get_tool_definition();
            
            if (is_array($definition) && isset($definition['function']['name']) && $definition['function']['name'] === 'get_plugin_logs') {
                $definition_test['result'] = 'passed';
                $definition_test['message'] = 'Tool has valid AI function definition';
                $results['passed']++;
            } else {
                $definition_test['message'] = 'Tool has invalid or incomplete AI function definition';
                $results['failed']++;
            }
        } else {
            $definition_test['message'] = 'Tool does not implement get_tool_definition method';
            $results['failed']++;
        }
    } catch (Exception $e) {
        $definition_test['message'] = 'Exception getting tool definition: ' . $e->getMessage();
        $results['failed']++;
    }

    $results['tests'][] = $definition_test;
    $results['total']++;

    // Test 4: Tool parameters for OpenAI function calling
    $parameters_test = [
        'name' => 'Plugin Logs Tool Parameters',
        'result' => 'failed',
        'message' => ''
    ];

    try {
        if (method_exists($plugin_logs_tool, 'get_parameters')) {
            $parameters = $plugin_logs_tool->get_parameters();
            
            if (is_array($parameters) && isset($parameters['action']) && isset($parameters['plugin_name'])) {
                $parameters_test['result'] = 'passed';
                $parameters_test['message'] = 'Tool has valid parameter definitions';
                $results['passed']++;
            } else {
                $parameters_test['message'] = 'Tool has invalid or incomplete parameter definitions';
                $results['failed']++;
            }
        } else {
            $parameters_test['message'] = 'Tool does not implement get_parameters method';
            $results['failed']++;
        }
    } catch (Exception $e) {
        $parameters_test['message'] = 'Exception getting tool parameters: ' . $e->getMessage();
        $results['failed']++;
    }

    $results['tests'][] = $parameters_test;
    $results['total']++;

    // Test 5: Basic execution with no parameters (summary)
    $basic_execution_test = [
        'name' => 'Plugin Logs Tool Basic Execution',
        'result' => 'failed',
        'message' => ''
    ];

    try {
        $result = $plugin_logs_tool->execute([]);
        
        if (
            is_array($result) && 
            isset($result['success']) && 
            $result['success'] === true && 
            isset($result['summary'])
        ) {
            $basic_execution_test['result'] = 'passed';
            $basic_execution_test['message'] = 'Tool successfully executed with default parameters';
            $results['passed']++;
        } else {
            $basic_execution_test['message'] = 'Tool execution with default parameters returned invalid or incomplete result';
            $results['failed']++;
        }
    } catch (Exception $e) {
        $basic_execution_test['message'] = 'Exception executing tool with default parameters: ' . $e->getMessage();
        $results['failed']++;
    }

    $results['tests'][] = $basic_execution_test;
    $results['total']++;

    // Test 6: Execution with summary_only parameter
    $summary_only_test = [
        'name' => 'Plugin Logs Tool Summary Only',
        'result' => 'failed',
        'message' => ''
    ];

    try {
        $result = $plugin_logs_tool->execute([
            'summary_only' => true
        ]);
        
        if (
            is_array($result) && 
            isset($result['success']) && 
            $result['success'] === true && 
            isset($result['summary']) && 
            !isset($result['logs']) &&
            !isset($result['plugins'])
        ) {
            $summary_only_test['result'] = 'passed';
            $summary_only_test['message'] = 'Tool successfully executed with summary_only parameter';
            $results['passed']++;
        } else {
            $summary_only_test['message'] = 'Tool execution with summary_only parameter returned invalid or unexpected result';
            $results['failed']++;
        }
    } catch (Exception $e) {
        $summary_only_test['message'] = 'Exception executing tool with summary_only parameter: ' . $e->getMessage();
        $results['failed']++;
    }

    $results['tests'][] = $summary_only_test;
    $results['total']++;

    // Test 7: Execution with action filter
    $action_filter_test = [
        'name' => 'Plugin Logs Tool Action Filter',
        'result' => 'failed',
        'message' => ''
    ];

    try {
        // First check without filter to see if we have logs
        $all_logs = $plugin_logs_tool->execute([]);
        $has_logs = isset($all_logs['logs']) && is_array($all_logs['logs']) && count($all_logs['logs']) > 0;
        
        if ($has_logs) {
            // We have logs to filter
            $actions = ['installed', 'activated', 'deactivated', 'deleted', 'updated'];
            $filtered = false;
            
            foreach ($actions as $action) {
                $result = $plugin_logs_tool->execute([
                    'action' => $action
                ]);
                
                if (
                    is_array($result) && 
                    isset($result['success']) && 
                    $result['success'] === true && 
                    isset($result['query']['action']) && 
                    $result['query']['action'] === $action
                ) {
                    $filtered = true;
                    break;
                }
            }
            
            if ($filtered) {
                $action_filter_test['result'] = 'passed';
                $action_filter_test['message'] = 'Tool successfully filtered logs by action';
                $results['passed']++;
            } else {
                $action_filter_test['message'] = 'Tool failed to properly filter logs by action';
                $results['failed']++;
            }
        } else {
            // No logs to filter - just verify the action parameter is accepted
            $result = $plugin_logs_tool->execute([
                'action' => 'activated'
            ]);
            
            if (
                is_array($result) && 
                isset($result['success']) && 
                $result['success'] === true && 
                isset($result['query']['action']) && 
                $result['query']['action'] === 'activated'
            ) {
                $action_filter_test['result'] = 'passed';
                $action_filter_test['message'] = 'Tool successfully accepted action filter (no logs to filter)';
                $results['passed']++;
            } else {
                $action_filter_test['message'] = 'Tool failed to properly accept action filter';
                $results['failed']++;
            }
        }
    } catch (Exception $e) {
        $action_filter_test['message'] = 'Exception executing tool with action filter: ' . $e->getMessage();
        $results['failed']++;
    }

    $results['tests'][] = $action_filter_test;
    $results['total']++;

    // Test 8: Execution with plugin_name filter
    $plugin_name_filter_test = [
        'name' => 'Plugin Logs Tool Plugin Name Filter',
        'result' => 'failed',
        'message' => ''
    ];

    try {
        // Use a common plugin name that's likely to exist
        $common_plugins = ['memberpress', 'wordpress', 'wp'];
        $filtered = false;
        
        foreach ($common_plugins as $plugin_name) {
            $result = $plugin_logs_tool->execute([
                'plugin_name' => $plugin_name
            ]);
            
            if (
                is_array($result) && 
                isset($result['success']) && 
                $result['success'] === true && 
                isset($result['query']['plugin_name']) && 
                $result['query']['plugin_name'] === $plugin_name
            ) {
                $filtered = true;
                break;
            }
        }
        
        if ($filtered) {
            $plugin_name_filter_test['result'] = 'passed';
            $plugin_name_filter_test['message'] = 'Tool successfully filtered logs by plugin name';
            $results['passed']++;
        } else {
            $plugin_name_filter_test['message'] = 'Tool failed to properly filter logs by plugin name';
            $results['failed']++;
        }
    } catch (Exception $e) {
        $plugin_name_filter_test['message'] = 'Exception executing tool with plugin name filter: ' . $e->getMessage();
        $results['failed']++;
    }

    $results['tests'][] = $plugin_name_filter_test;
    $results['total']++;

    // Test 9: Execution with days parameter
    $days_parameter_test = [
        'name' => 'Plugin Logs Tool Days Parameter',
        'result' => 'failed',
        'message' => ''
    ];

    try {
        $result = $plugin_logs_tool->execute([
            'days' => 7 // Last week
        ]);
        
        if (
            is_array($result) && 
            isset($result['success']) && 
            $result['success'] === true && 
            isset($result['query']['days']) && 
            $result['query']['days'] === 7 &&
            isset($result['time_period']) && 
            strpos($result['time_period'], '7 days') !== false
        ) {
            $days_parameter_test['result'] = 'passed';
            $days_parameter_test['message'] = 'Tool successfully processed days parameter';
            $results['passed']++;
        } else {
            $days_parameter_test['message'] = 'Tool failed to properly process days parameter';
            $results['failed']++;
        }
    } catch (Exception $e) {
        $days_parameter_test['message'] = 'Exception executing tool with days parameter: ' . $e->getMessage();
        $results['failed']++;
    }

    $results['tests'][] = $days_parameter_test;
    $results['total']++;

    // Test 10: Execution with limit parameter
    $limit_parameter_test = [
        'name' => 'Plugin Logs Tool Limit Parameter',
        'result' => 'failed',
        'message' => ''
    ];

    try {
        $result = $plugin_logs_tool->execute([
            'limit' => 5 // Only 5 results
        ]);
        
        if (
            is_array($result) && 
            isset($result['success']) && 
            $result['success'] === true && 
            isset($result['query']['limit']) && 
            $result['query']['limit'] === 5
        ) {
            // Check if returned logs are limited
            $returned_count = isset($result['returned_records']) ? $result['returned_records'] : 
                              (isset($result['logs']) ? count($result['logs']) : 0);
            
            if ($returned_count <= 5) {
                $limit_parameter_test['result'] = 'passed';
                $limit_parameter_test['message'] = 'Tool successfully processed limit parameter';
                $results['passed']++;
            } else {
                $limit_parameter_test['message'] = 'Tool accepted limit parameter but returned more records than specified';
                $results['failed']++;
            }
        } else {
            $limit_parameter_test['message'] = 'Tool failed to properly process limit parameter';
            $results['failed']++;
        }
    } catch (Exception $e) {
        $limit_parameter_test['message'] = 'Exception executing tool with limit parameter: ' . $e->getMessage();
        $results['failed']++;
    }

    $results['tests'][] = $limit_parameter_test;
    $results['total']++;

    // Test 11: Multiple parameter combination
    $combined_parameters_test = [
        'name' => 'Plugin Logs Tool Combined Parameters',
        'result' => 'failed',
        'message' => ''
    ];

    try {
        $result = $plugin_logs_tool->execute([
            'action' => 'activated',
            'days' => 30,
            'limit' => 3
        ]);
        
        if (
            is_array($result) && 
            isset($result['success']) && 
            $result['success'] === true && 
            isset($result['query']['action']) && 
            $result['query']['action'] === 'activated' &&
            isset($result['query']['days']) && 
            $result['query']['days'] === 30 &&
            isset($result['query']['limit']) && 
            $result['query']['limit'] === 3
        ) {
            // Check if returned logs are limited and filtered
            $returned_count = isset($result['returned_records']) ? $result['returned_records'] : 
                              (isset($result['logs']) ? count($result['logs']) : 0);
            
            if ($returned_count <= 3) {
                $combined_parameters_test['result'] = 'passed';
                $combined_parameters_test['message'] = 'Tool successfully processed combined parameters';
                $results['passed']++;
            } else {
                $combined_parameters_test['message'] = 'Tool accepted combined parameters but returned more records than specified';
                $results['failed']++;
            }
        } else {
            $combined_parameters_test['message'] = 'Tool failed to properly process combined parameters';
            $results['failed']++;
        }
    } catch (Exception $e) {
        $combined_parameters_test['message'] = 'Exception executing tool with combined parameters: ' . $e->getMessage();
        $results['failed']++;
    }

    $results['tests'][] = $combined_parameters_test;
    $results['total']++;

    // Test 12: Check time formatting
    $time_format_test = [
        'name' => 'Plugin Logs Tool Time Formatting',
        'result' => 'failed',
        'message' => ''
    ];

    try {
        // First check if we have any logs
        $all_logs = $plugin_logs_tool->execute([]);
        $has_logs = (isset($all_logs['logs']) && is_array($all_logs['logs']) && count($all_logs['logs']) > 0) ||
                    (isset($all_logs['plugins']) && is_array($all_logs['plugins']) && count($all_logs['plugins']) > 0);
        
        if ($has_logs) {
            $has_time_ago = false;
            
            // Check logs array first
            if (isset($all_logs['logs']) && is_array($all_logs['logs'])) {
                foreach ($all_logs['logs'] as $log) {
                    if (isset($log['time_ago']) && !empty($log['time_ago'])) {
                        $has_time_ago = true;
                        break;
                    }
                }
            }
            
            // Check plugins array if needed
            if (!$has_time_ago && isset($all_logs['plugins']) && is_array($all_logs['plugins'])) {
                foreach ($all_logs['plugins'] as $plugin) {
                    if (isset($plugin['logs']) && is_array($plugin['logs'])) {
                        foreach ($plugin['logs'] as $log) {
                            if (isset($log['time_ago']) && !empty($log['time_ago'])) {
                                $has_time_ago = true;
                                break 2;
                            }
                        }
                    }
                }
            }
            
            if ($has_time_ago) {
                $time_format_test['result'] = 'passed';
                $time_format_test['message'] = 'Tool correctly formats time_ago in logs';
                $results['passed']++;
            } else {
                $time_format_test['message'] = 'Tool does not include time_ago formatting in logs';
                $results['failed']++;
            }
        } else {
            // No logs to check - skip this test
            $time_format_test['result'] = 'skipped';
            $time_format_test['message'] = 'No logs available to check time formatting';
            // Don't count skipped tests in passed or failed
        }
    } catch (Exception $e) {
        $time_format_test['message'] = 'Exception checking time formatting: ' . $e->getMessage();
        $results['failed']++;
    }

    $results['tests'][] = $time_format_test;
    if ($time_format_test['result'] !== 'skipped') {
        $results['total']++;
    }

    return $results;
}

/**
 * Run and display Plugin Logs tool tests
 */
function mpai_run_plugin_logs_tool_tests() {
    $results = mpai_test_plugin_logs_tool();
    
    echo '<h3>Plugin Logs Tool Integration Tests</h3>';
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