<?php
/**
 * Integration tests for WP-CLI Tool
 *
 * @package MemberPress AI Assistant
 */

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Run integration tests for WP-CLI Tool
 *
 * @return array Test results
 */
function mpai_test_wpcli_tool() {
    $results = [
        'tests' => [],
        'passed' => 0,
        'failed' => 0,
        'total' => 0
    ];

    // Ensure the MPAI_WP_CLI_Tool class is loaded
    if (!class_exists('MPAI_WP_CLI_Tool')) {
        $tool_path = MPAI_PLUGIN_DIR . 'includes/tools/implementations/class-mpai-wpcli-tool.php';
        if (file_exists($tool_path)) {
            require_once $tool_path;
        } else {
            $results['tests'][] = [
                'name' => 'WP-CLI Tool Class Loading',
                'result' => 'failed',
                'message' => 'Could not find WP-CLI tool class file'
            ];
            $results['failed']++;
            $results['total']++;
            return $results;
        }
    }

    // Test 1: Tool instance creation
    $instance_test = [
        'name' => 'WP-CLI Tool Instance Creation',
        'result' => 'failed',
        'message' => ''
    ];

    try {
        $wpcli_tool = new MPAI_WP_CLI_Tool();
        if ($wpcli_tool instanceof MPAI_WP_CLI_Tool) {
            $instance_test['result'] = 'passed';
            $instance_test['message'] = 'Successfully created WP-CLI tool instance';
            $results['passed']++;
        } else {
            $instance_test['message'] = 'Failed to create WP-CLI tool instance';
            $results['failed']++;
        }
    } catch (Exception $e) {
        $instance_test['message'] = 'Exception creating WP-CLI tool instance: ' . $e->getMessage();
        $results['failed']++;
    }

    $results['tests'][] = $instance_test;
    $results['total']++;

    // Test 2: Tool properties
    $properties_test = [
        'name' => 'WP-CLI Tool Properties',
        'result' => 'failed',
        'message' => ''
    ];

    try {
        $name = $wpcli_tool->get_name();
        $description = $wpcli_tool->get_description();

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
        'name' => 'WP-CLI Tool Invalid Parameters',
        'result' => 'failed',
        'message' => ''
    ];

    try {
        $wpcli_tool->execute([]);
        $invalid_params_test['message'] = 'Tool accepted execution without command parameter - should have thrown an exception';
        $results['failed']++;
    } catch (Exception $e) {
        $invalid_params_test['result'] = 'passed';
        $invalid_params_test['message'] = 'Tool correctly rejected execution without command parameter';
        $results['passed']++;
    }

    $results['tests'][] = $invalid_params_test;
    $results['total']++;

    // Test 4: Execute with invalid command
    $invalid_command_test = [
        'name' => 'WP-CLI Tool Invalid Command',
        'result' => 'failed',
        'message' => ''
    ];

    try {
        $wpcli_tool->execute(['command' => 'invalid_command_xyz']);
        $invalid_command_test['message'] = 'Tool accepted execution with invalid command - should have thrown an exception';
        $results['failed']++;
    } catch (Exception $e) {
        $invalid_command_test['result'] = 'passed';
        $invalid_command_test['message'] = 'Tool correctly rejected execution with invalid command';
        $results['passed']++;
    }

    $results['tests'][] = $invalid_command_test;
    $results['total']++;

    // Test 5: Execute valid command - core version
    $core_version_test = [
        'name' => 'WP-CLI Tool Core Version Command',
        'result' => 'failed',
        'message' => ''
    ];

    try {
        $result = $wpcli_tool->execute(['command' => 'wp core version']);
        
        if (is_string($result) && preg_match('/\d+\.\d+(\.\d+)?/', $result)) {
            $core_version_test['result'] = 'passed';
            $core_version_test['message'] = "Tool correctly executed core version command, got: $result";
            $results['passed']++;
        } else {
            $core_version_test['message'] = 'Tool execution for core version returned invalid format';
            $results['failed']++;
        }
    } catch (Exception $e) {
        $core_version_test['message'] = 'Exception executing core version command: ' . $e->getMessage();
        $results['failed']++;
    }

    $results['tests'][] = $core_version_test;
    $results['total']++;

    // Test 6: PHP version fallback
    $php_version_test = [
        'name' => 'WP-CLI Tool PHP Version Fallback',
        'result' => 'failed',
        'message' => ''
    ];

    try {
        $result = $wpcli_tool->execute(['command' => 'wp php version']);
        
        if (is_string($result) && stripos($result, 'PHP Version') !== false) {
            $php_version_test['result'] = 'passed';
            $php_version_test['message'] = "Tool correctly executed PHP version fallback";
            $results['passed']++;
        } else {
            $php_version_test['message'] = 'Tool execution for PHP version returned invalid format';
            $results['failed']++;
        }
    } catch (Exception $e) {
        $php_version_test['message'] = 'Exception executing PHP version command: ' . $e->getMessage();
        $results['failed']++;
    }

    $results['tests'][] = $php_version_test;
    $results['total']++;

    // Test 7: Plugin list command
    $plugin_list_test = [
        'name' => 'WP-CLI Tool Plugin List Command',
        'result' => 'failed',
        'message' => ''
    ];

    try {
        $result = $wpcli_tool->execute(['command' => 'wp plugin list']);
        
        if (is_string($result) && (stripos($result, 'Name') !== false || stripos($result, 'Plugin') !== false)) {
            $plugin_list_test['result'] = 'passed';
            $plugin_list_test['message'] = "Tool correctly executed plugin list command";
            $results['passed']++;
        } else {
            $plugin_list_test['message'] = 'Tool execution for plugin list returned invalid format';
            $results['failed']++;
        }
    } catch (Exception $e) {
        $plugin_list_test['message'] = 'Exception executing plugin list command: ' . $e->getMessage();
        $results['failed']++;
    }

    $results['tests'][] = $plugin_list_test;
    $results['total']++;

    // Test 8: Plugin recent command
    $plugin_recent_test = [
        'name' => 'WP-CLI Tool Plugin Recent Command',
        'result' => 'failed',
        'message' => ''
    ];

    try {
        $result = $wpcli_tool->execute(['command' => 'wp plugin recent']);
        
        if (is_string($result) && (stripos($result, 'Recent Plugin Activity') !== false)) {
            $plugin_recent_test['result'] = 'passed';
            $plugin_recent_test['message'] = "Tool correctly executed plugin recent command";
            $results['passed']++;
        } else {
            $plugin_recent_test['message'] = 'Tool execution for plugin recent returned invalid format';
            $results['failed']++;
        }
    } catch (Exception $e) {
        $plugin_recent_test['message'] = 'Exception executing plugin recent command: ' . $e->getMessage();
        $results['failed']++;
    }

    $results['tests'][] = $plugin_recent_test;
    $results['total']++;

    // Test 9: Command timeout handling
    $timeout_test = [
        'name' => 'WP-CLI Tool Command Timeout Handling',
        'result' => 'failed',
        'message' => ''
    ];

    try {
        $result = $wpcli_tool->execute([
            'command' => 'wp plugin list',
            'timeout' => 1 // Very short timeout
        ]);
        
        $timeout_test['result'] = 'passed';
        $timeout_test['message'] = "Tool correctly handled timeout parameter";
        $results['passed']++;
    } catch (Exception $e) {
        // This may or may not throw an exception depending on implementation
        if (stripos($e->getMessage(), 'timeout') !== false) {
            $timeout_test['result'] = 'passed';
            $timeout_test['message'] = "Tool correctly handled timeout parameter (threw timeout exception)";
            $results['passed']++;
        } else {
            $timeout_test['message'] = 'Exception unrelated to timeout: ' . $e->getMessage();
            $results['failed']++;
        }
    }

    $results['tests'][] = $timeout_test;
    $results['total']++;

    // Test 10: Command result caching
    $caching_test = [
        'name' => 'WP-CLI Tool Command Result Caching',
        'result' => 'failed',
        'message' => ''
    ];

    try {
        // First execution to potentially cache
        $first_result = $wpcli_tool->execute(['command' => 'wp core version']);
        
        // Second execution should use cache if implemented
        $start_time = microtime(true);
        $second_result = $wpcli_tool->execute(['command' => 'wp core version']);
        $execution_time = microtime(true) - $start_time;
        
        // If second execution is much faster, cache is working
        // Or if result contains cache indicator
        if ($execution_time < 0.01 || (is_string($second_result) && stripos($second_result, 'CACHED') !== false)) {
            $caching_test['result'] = 'passed';
            $caching_test['message'] = "Tool correctly implemented result caching";
            $results['passed']++;
        } else {
            $caching_test['message'] = 'Tool does not appear to implement result caching';
            $results['failed']++;
        }
    } catch (Exception $e) {
        $caching_test['message'] = 'Exception testing caching: ' . $e->getMessage();
        $results['failed']++;
    }

    $results['tests'][] = $caching_test;
    $results['total']++;

    // Test 11: Skip cache parameter
    $skip_cache_test = [
        'name' => 'WP-CLI Tool Skip Cache Parameter',
        'result' => 'failed',
        'message' => ''
    ];

    try {
        // Execute with skip_cache parameter
        $result = $wpcli_tool->execute([
            'command' => 'wp core version',
            'skip_cache' => true
        ]);
        
        // This test is mostly checking if the parameter is accepted
        $skip_cache_test['result'] = 'passed';
        $skip_cache_test['message'] = "Tool correctly handled skip_cache parameter";
        $results['passed']++;
    } catch (Exception $e) {
        $skip_cache_test['message'] = 'Exception testing skip_cache parameter: ' . $e->getMessage();
        $results['failed']++;
    }

    $results['tests'][] = $skip_cache_test;
    $results['total']++;

    // Test 12: Edge case - whitespace in command
    $whitespace_test = [
        'name' => 'WP-CLI Tool Whitespace Handling',
        'result' => 'failed',
        'message' => ''
    ];

    try {
        $result = $wpcli_tool->execute(['command' => '  wp core version  ']);
        
        if (is_string($result) && preg_match('/\d+\.\d+(\.\d+)?/', $result)) {
            $whitespace_test['result'] = 'passed';
            $whitespace_test['message'] = "Tool correctly handled whitespace in command";
            $results['passed']++;
        } else {
            $whitespace_test['message'] = 'Tool failed to handle whitespace in command';
            $results['failed']++;
        }
    } catch (Exception $e) {
        $whitespace_test['message'] = 'Exception handling whitespace in command: ' . $e->getMessage();
        $results['failed']++;
    }

    $results['tests'][] = $whitespace_test;
    $results['total']++;

    return $results;
}

/**
 * Run and display WP-CLI tool tests
 */
function mpai_run_wpcli_tool_tests() {
    $results = mpai_test_wpcli_tool();
    
    echo '<h3>WP-CLI Tool Integration Tests</h3>';
    echo '<div class="mpai-test-results">';
    echo '<p>Tests Run: ' . $results['total'] . ', ';
    echo 'Passed: ' . $results['passed'] . ', ';
    echo 'Failed: ' . $results['failed'] . '</p>';
    
    echo '<table class="mpai-test-table">';
    echo '<thead><tr><th>Test</th><th>Result</th><th>Message</th></tr></thead>';
    echo '<tbody>';
    
    foreach ($results['tests'] as $test) {
        $result_class = $test['result'] === 'passed' ? 'test-passed' : 'test-failed';
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