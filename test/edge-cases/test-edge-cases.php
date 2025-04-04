<?php
/**
 * Edge Case Test Suite
 *
 * Main entry point for the Edge Case Test Suite
 *
 * @package MemberPress AI Assistant
 */

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

// Include individual test files
require_once MPAI_PLUGIN_DIR . 'test/edge-cases/test-input-validation.php';
require_once MPAI_PLUGIN_DIR . 'test/edge-cases/test-resource-limits.php';
require_once MPAI_PLUGIN_DIR . 'test/edge-cases/test-error-conditions.php';

/**
 * Run all edge case tests
 *
 * @return array Combined test results
 */
function mpai_run_edge_case_tests() {
    $combined_results = [
        'tests' => [],
        'total' => 0,
        'passed' => 0,
        'failed' => 0,
        'skipped' => 0,
        'test_results' => []
    ];
    
    // Run Input Validation tests
    $input_validation_results = mpai_test_input_validation_edge_cases();
    $combined_results['test_results']['input_validation'] = $input_validation_results;
    $combined_results['tests'] = array_merge($combined_results['tests'], $input_validation_results['tests']);
    $combined_results['total'] += $input_validation_results['total'];
    $combined_results['passed'] += $input_validation_results['passed'];
    $combined_results['failed'] += $input_validation_results['failed'];
    
    // Run Resource Limits tests
    $resource_limits_results = mpai_test_resource_limits_edge_cases();
    $combined_results['test_results']['resource_limits'] = $resource_limits_results;
    $combined_results['tests'] = array_merge($combined_results['tests'], $resource_limits_results['tests']);
    $combined_results['total'] += $resource_limits_results['total'];
    $combined_results['passed'] += $resource_limits_results['passed'];
    $combined_results['failed'] += $resource_limits_results['failed'];
    
    // Run Error Conditions tests
    $error_conditions_results = mpai_test_error_conditions_edge_cases();
    $combined_results['test_results']['error_conditions'] = $error_conditions_results;
    $combined_results['tests'] = array_merge($combined_results['tests'], $error_conditions_results['tests']);
    $combined_results['total'] += $error_conditions_results['total'];
    $combined_results['passed'] += $error_conditions_results['passed'];
    $combined_results['failed'] += $error_conditions_results['failed'];
    
    // Count skipped tests
    foreach ($combined_results['tests'] as $test) {
        if (isset($test['result']) && $test['result'] === 'skipped') {
            $combined_results['skipped']++;
        }
    }
    
    return $combined_results;
}

/**
 * Display all edge case test results
 */
function mpai_display_all_edge_case_tests() {
    $results = mpai_run_edge_case_tests();
    
    echo '<div class="mpai-test-container">';
    echo '<h2>Edge Case Test Suite</h2>';
    
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
    
    // Run individual tests with accordion
    echo '<div class="mpai-accordion">';
    
    // Input Validation Tests
    echo '<div class="mpai-accordion-item">';
    echo '<div class="mpai-accordion-header">Input Validation Tests <span class="mpai-test-count">' . 
         $results['test_results']['input_validation']['passed'] . '/' . $results['test_results']['input_validation']['total'] . 
         ' passed</span></div>';
    echo '<div class="mpai-accordion-content">';
    mpai_display_input_validation_edge_case_tests();
    echo '</div></div>';
    
    // Resource Limits Tests
    echo '<div class="mpai-accordion-item">';
    echo '<div class="mpai-accordion-header">Resource Limits Tests <span class="mpai-test-count">' . 
         $results['test_results']['resource_limits']['passed'] . '/' . $results['test_results']['resource_limits']['total'] . 
         ' passed</span></div>';
    echo '<div class="mpai-accordion-content">';
    mpai_display_resource_limits_edge_case_tests();
    echo '</div></div>';
    
    // Error Conditions Tests
    echo '<div class="mpai-accordion-item">';
    echo '<div class="mpai-accordion-header">Error Conditions Tests <span class="mpai-test-count">' . 
         $results['test_results']['error_conditions']['passed'] . '/' . $results['test_results']['error_conditions']['total'] . 
         ' passed</span></div>';
    echo '<div class="mpai-accordion-content">';
    mpai_display_error_conditions_edge_case_tests();
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

// Add function to add the tests to the diagnostics page
function mpai_add_edge_case_tests_to_diagnostics() {
    add_action('mpai_run_diagnostics', 'mpai_display_edge_case_tests_section');
}

/**
 * Display edge case tests section in diagnostics
 */
function mpai_display_edge_case_tests_section() {
    ?>
    <!-- Edge Case Test Suite Section -->
    <div class="mpai-debug-section">
        <h4><?php _e('Edge Case Test Suite', 'memberpress-ai-assistant'); ?></h4>
        <p><?php _e('Test system handling of boundary conditions, unusual inputs, and error scenarios.', 'memberpress-ai-assistant'); ?></p>
        
        <button type="button" id="mpai-run-edge-case-tests" class="button button-primary"><?php _e('Run Edge Case Tests', 'memberpress-ai-assistant'); ?></button>
        
        <div id="mpai-edge-case-results" class="mpai-debug-results" style="display: none; margin-top: 20px;">
            <div id="mpai-edge-case-output" class="mpai-test-container"></div>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Edge Case Test Suite
        $('#mpai-run-edge-case-tests').on('click', function() {
            var $resultsContainer = $('#mpai-edge-case-results');
            var $outputContainer = $('#mpai-edge-case-output');
            
            $resultsContainer.show();
            $outputContainer.html('<p>Running edge case tests, please wait...</p>');
            
            // Make an AJAX request to run the edge case tests
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'mpai_run_edge_case_tests',
                    nonce: mpai_data.nonce
                },
                success: function(response) {
                    try {
                        // Display the test results
                        if (response.success) {
                            $outputContainer.html(response.data);
                        } else {
                            $outputContainer.html('<div class="notice notice-error"><p>Error running edge case tests: ' + 
                                (response.data || 'Unknown error') + '</p></div>');
                        }
                        
                        // Initialize the accordion functionality
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
                    } catch (e) {
                        $outputContainer.html('<div class="notice notice-error"><p>Error processing test results: ' + e.message + '</p></div>');
                    }
                },
                error: function(xhr, status, error) {
                    $outputContainer.html('<div class="notice notice-error"><p>AJAX error: ' + error + '</p></div>');
                }
            });
        });
    });
    </script>
    <?php
}

// Initialize edge case tests
mpai_add_edge_case_tests_to_diagnostics();