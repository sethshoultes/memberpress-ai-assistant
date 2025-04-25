<?php
/**
 * Test file for the WP-CLI tool
 *
 * This file contains tests to verify that the WP-CLI tool is working correctly.
 * It tests the standardized 'wpcli' tool ID.
 *
 * @package MemberPress AI Assistant
 */

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Test the WP-CLI tool
 * 
 * @return array Test results
 */
function mpai_test_wpcli_tool() {
    $results = [
        'success' => true,
        'message' => 'All tests passed',
        'tests' => []
    ];

    // Test 1: Check if the tool registry is available
    if (!class_exists('MPAI_Tool_Registry')) {
        $results['success'] = false;
        $results['message'] = 'MPAI_Tool_Registry class not found';
        $results['tests'][] = [
            'name' => 'Tool Registry Check',
            'success' => false,
            'message' => 'MPAI_Tool_Registry class not found'
        ];
        return $results;
    }

    $results['tests'][] = [
        'name' => 'Tool Registry Check',
        'success' => true,
        'message' => 'MPAI_Tool_Registry class found'
    ];

    // Initialize the tool registry
    $tool_registry = new MPAI_Tool_Registry();

    // Test 2: Check if the 'wpcli' tool is registered
    $wpcli_tool = $tool_registry->get_tool('wpcli');
    if (!$wpcli_tool) {
        $results['success'] = false;
        $results['message'] = 'wpcli tool not found in tool registry';
        $results['tests'][] = [
            'name' => 'wpcli Tool Registration Check',
            'success' => false,
            'message' => 'wpcli tool not found in tool registry'
        ];
        return $results;
    }

    $results['tests'][] = [
        'name' => 'wpcli Tool Registration Check',
        'success' => true,
        'message' => 'wpcli tool found in tool registry'
    ];

    // Only the standardized 'wpcli' tool ID is supported

    // Test 4: Execute a simple command using the 'wpcli' tool
    try {
        $result = $wpcli_tool->execute([
            'command' => 'wp core version'
        ]);

        if (!$result || !is_string($result) || !preg_match('/\d+\.\d+(\.\d+)?/', $result)) {
            $results['success'] = false;
            $results['message'] = 'Failed to execute command using wpcli tool';
            $results['tests'][] = [
                'name' => 'wpcli Tool Execution Check',
                'success' => false,
                'message' => 'Failed to execute command using wpcli tool'
            ];
            return $results;
        }

        $results['tests'][] = [
            'name' => 'wpcli Tool Execution Check',
            'success' => true,
            'message' => 'Successfully executed command using wpcli tool: ' . $result
        ];
    } catch (Exception $e) {
        $results['success'] = false;
        $results['message'] = 'Exception when executing command using wpcli tool: ' . $e->getMessage();
        $results['tests'][] = [
            'name' => 'wpcli Tool Execution Check',
            'success' => false,
            'message' => 'Exception: ' . $e->getMessage()
        ];
        return $results;
    }

    // Only the standardized 'wpcli' tool ID is supported

    return $results;
}

/**
 * Run the WP-CLI tool tests and display the results
 */
function mpai_run_wpcli_tool_tests() {
    $results = mpai_test_wpcli_tool();

    echo '<div class="mpai-test-results">';
    echo '<h2>WP-CLI Tool Tests</h2>';
    
    if ($results['success']) {
        echo '<p class="success">All tests passed!</p>';
    } else {
        echo '<p class="error">Tests failed: ' . esc_html($results['message']) . '</p>';
    }

    echo '<table class="mpai-test-table">';
    echo '<thead><tr><th>Test</th><th>Result</th><th>Message</th></tr></thead>';
    echo '<tbody>';

    foreach ($results['tests'] as $test) {
        $class = $test['success'] ? 'success' : 'error';
        $result = $test['success'] ? 'Passed' : 'Failed';
        
        echo '<tr class="' . $class . '">';
        echo '<td>' . esc_html($test['name']) . '</td>';
        echo '<td>' . esc_html($result) . '</td>';
        echo '<td>' . esc_html($test['message']) . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
    echo '</div>';

    return $results;
}

// Add the test to the admin menu if we're in the admin area
if (is_admin()) {
    add_action('admin_menu', function() {
        add_submenu_page(
            'memberpress-ai-assistant',
            'WP-CLI Tool Tests',
            'WP-CLI Tool Tests',
            'manage_options',
            'mpai-wpcli-tool-tests',
            'mpai_run_wpcli_tool_tests'
        );
    });
}