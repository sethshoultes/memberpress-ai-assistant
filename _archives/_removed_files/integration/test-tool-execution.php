<?php
/**
 * Tool Execution Integration Tests
 *
 * Executes integration tests for all tools to verify end-to-end operations
 *
 * @package MemberPress AI Assistant
 */

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

// Include individual tool test files
require_once MPAI_PLUGIN_DIR . 'test/integration/tools/test-wpcli-tool.php';
require_once MPAI_PLUGIN_DIR . 'test/integration/tools/test-wp-api-tool.php';
require_once MPAI_PLUGIN_DIR . 'test/integration/tools/test-plugin-logs-tool.php';

/**
 * Run all tool execution integration tests
 * 
 * @return array Combined test results
 */
function mpai_run_all_tool_execution_tests() {
    $combined_results = [
        'tests' => [],
        'total' => 0,
        'passed' => 0,
        'failed' => 0,
        'skipped' => 0,
        'tool_results' => []
    ];
    
    // Run WP-CLI tool tests
    $wpcli_results = mpai_test_wpcli_tool();
    $combined_results['tool_results']['wpcli'] = $wpcli_results;
    $combined_results['tests'] = array_merge($combined_results['tests'], $wpcli_results['tests']);
    $combined_results['total'] += $wpcli_results['total'];
    $combined_results['passed'] += $wpcli_results['passed'];
    $combined_results['failed'] += $wpcli_results['failed'];
    
    // Run WP API tool tests
    $wp_api_results = mpai_test_wp_api_tool();
    $combined_results['tool_results']['wp_api'] = $wp_api_results;
    $combined_results['tests'] = array_merge($combined_results['tests'], $wp_api_results['tests']);
    $combined_results['total'] += $wp_api_results['total'];
    $combined_results['passed'] += $wp_api_results['passed'];
    $combined_results['failed'] += $wp_api_results['failed'];
    
    // Run Plugin Logs tool tests
    $plugin_logs_results = mpai_test_plugin_logs_tool();
    $combined_results['tool_results']['plugin_logs'] = $plugin_logs_results;
    $combined_results['tests'] = array_merge($combined_results['tests'], $plugin_logs_results['tests']);
    $combined_results['total'] += $plugin_logs_results['total'];
    $combined_results['passed'] += $plugin_logs_results['passed'];
    $combined_results['failed'] += $plugin_logs_results['failed'];
    
    // Count skipped tests
    foreach ($combined_results['tests'] as $test) {
        if (isset($test['result']) && $test['result'] === 'skipped') {
            $combined_results['skipped']++;
        }
    }
    
    return $combined_results;
}

/**
 * Run and display all tool execution tests
 */
function mpai_display_all_tool_execution_tests() {
    $results = mpai_run_all_tool_execution_tests();
    
    echo '<div class="mpai-test-container">';
    echo '<h2>Tool Execution Integration Tests</h2>';
    
    echo '<div class="mpai-test-summary">';
    echo '<p><strong>Summary:</strong> ' . $results['total'] . ' tests run, ';
    echo '<span class="tests-passed">' . $results['passed'] . ' passed</span>, ';
    echo '<span class="tests-failed">' . $results['failed'] . ' failed</span>';
    if ($results['skipped'] > 0) {
        echo ', <span class="tests-skipped">' . $results['skipped'] . ' skipped</span>';
    }
    echo '</p>';
    
    // Calculate pass percentage
    $pass_percentage = $results['total'] > 0 ? round(($results['passed'] / $results['total']) * 100) : 0;
    echo '<div class="mpai-progress-bar">';
    echo '<div class="mpai-progress-value" style="width: ' . $pass_percentage . '%;">' . $pass_percentage . '%</div>';
    echo '</div>';
    echo '</div>';
    
    // Run individual tests
    echo '<div class="mpai-accordion">';
    
    // WP-CLI Tool Tests
    echo '<div class="mpai-accordion-item">';
    echo '<div class="mpai-accordion-header">WP-CLI Tool Tests <span class="mpai-test-count">' . 
         $results['tool_results']['wpcli']['passed'] . '/' . $results['tool_results']['wpcli']['total'] . 
         ' passed</span></div>';
    echo '<div class="mpai-accordion-content">';
    mpai_run_wpcli_tool_tests();
    echo '</div></div>';
    
    // WordPress API Tool Tests
    echo '<div class="mpai-accordion-item">';
    echo '<div class="mpai-accordion-header">WordPress API Tool Tests <span class="mpai-test-count">' . 
         $results['tool_results']['wp_api']['passed'] . '/' . $results['tool_results']['wp_api']['total'] . 
         ' passed</span></div>';
    echo '<div class="mpai-accordion-content">';
    mpai_run_wp_api_tool_tests();
    echo '</div></div>';
    
    // Plugin Logs Tool Tests
    echo '<div class="mpai-accordion-item">';
    echo '<div class="mpai-accordion-header">Plugin Logs Tool Tests <span class="mpai-test-count">' . 
         $results['tool_results']['plugin_logs']['passed'] . '/' . $results['tool_results']['plugin_logs']['total'] . 
         ' passed</span></div>';
    echo '<div class="mpai-accordion-content">';
    mpai_run_plugin_logs_tool_tests();
    echo '</div></div>';
    
    echo '</div>'; // Close accordion
    echo '</div>'; // Close test container
    
    // Include JavaScript for accordion functionality
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var accordionHeaders = document.querySelectorAll('.mpai-accordion-header');
        
        accordionHeaders.forEach(function(header) {
            header.addEventListener('click', function() {
                this.classList.toggle('active');
                var content = this.nextElementSibling;
                if (content.style.maxHeight) {
                    content.style.maxHeight = null;
                } else {
                    content.style.maxHeight = content.scrollHeight + 'px';
                }
            });
        });
    });
    </script>
    <style>
    .mpai-test-container {
        max-width: 100%;
        margin: 20px 0;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
    }
    .mpai-test-summary {
        background: #f9f9f9;
        padding: 15px;
        border-radius: 4px;
        margin-bottom: 20px;
    }
    .mpai-progress-bar {
        height: 20px;
        background-color: #e0e0e0;
        border-radius: 10px;
        overflow: hidden;
        margin-top: 10px;
    }
    .mpai-progress-value {
        height: 100%;
        background-color: #46b450;
        color: white;
        text-align: center;
        line-height: 20px;
        font-size: 12px;
        border-radius: 10px;
    }
    .mpai-accordion {
        margin-top: 20px;
    }
    .mpai-accordion-item {
        margin-bottom: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        overflow: hidden;
    }
    .mpai-accordion-header {
        background-color: #f1f1f1;
        padding: 12px 15px;
        cursor: pointer;
        font-weight: bold;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .mpai-accordion-header:hover {
        background-color: #e9e9e9;
    }
    .mpai-accordion-header.active {
        background-color: #e0e0e0;
    }
    .mpai-test-count {
        font-size: 0.9em;
        color: #555;
    }
    .mpai-accordion-content {
        padding: 0 15px;
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease-out;
    }
    .tests-passed {
        color: #46b450;
        font-weight: bold;
    }
    .tests-failed {
        color: #dc3232;
        font-weight: bold;
    }
    .tests-skipped {
        color: #f0ad4e;
        font-weight: bold;
    }
    .mpai-test-table {
        width: 100%;
        border-collapse: collapse;
        margin: 15px 0;
    }
    .mpai-test-table th, .mpai-test-table td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
    }
    .mpai-test-table th {
        background-color: #f5f5f5;
    }
    .test-passed {
        background-color: rgba(70, 180, 80, 0.1);
    }
    .test-failed {
        background-color: rgba(220, 50, 50, 0.1);
    }
    .test-skipped {
        background-color: rgba(240, 173, 78, 0.1);
    }
    .test-pending {
        background-color: rgba(0, 150, 220, 0.1);
    }
    </style>
    <?php
    
    return $results;
}

// Add the test to the diagnostics page
function mpai_add_tool_execution_tests_to_diagnostics() {
    add_action('mpai_run_diagnostics', 'mpai_display_all_tool_execution_tests');
}