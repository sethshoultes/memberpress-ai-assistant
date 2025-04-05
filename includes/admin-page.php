<?php
/**
 * Admin pages registration for MemberPress AI Assistant
 *
 * @package MemberPress AI Assistant
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register admin pages for test scripts
 */
function mpai_register_test_admin_pages() {
    // Only register test pages if WP_DEBUG is enabled or MPAI_TEST_PAGES is true
    if ( ! ( defined( 'WP_DEBUG' ) && WP_DEBUG ) && ! ( defined( 'MPAI_TEST_PAGES' ) && MPAI_TEST_PAGES ) ) {
        return;
    }
    
    // Tool execution test page
    add_submenu_page(
        null, // Hidden from menu
        'Tool Execution Tests',
        'Tool Execution Tests',
        'manage_options',
        'mpai-test-tool-execution',
        'mpai_test_tool_execution_page'
    );
    
    // Edge case test page
    add_submenu_page(
        null, // Hidden from menu
        'Edge Case Tests',
        'Edge Case Tests',
        'manage_options',
        'mpai-test-edge-cases',
        'mpai_test_edge_cases_page'
    );
    
    // Input validator test page
    add_submenu_page(
        null, // Hidden from menu
        'Input Validator Tests',
        'Input Validator Tests',
        'manage_options',
        'mpai-test-input-validator',
        'mpai_test_input_validator_page'
    );
}
add_action('admin_menu', 'mpai_register_test_admin_pages', 99);

/**
 * Tool execution test page callback
 */
function mpai_test_tool_execution_page() {
    // Include the test file
    $test_file = MPAI_PLUGIN_DIR . 'test/integration/test-tool-execution.php';
    if (file_exists($test_file)) {
        include_once $test_file;
        if (function_exists('mpai_run_all_tool_execution_tests')) {
            mpai_run_all_tool_execution_tests();
        } else {
            echo '<div class="wrap"><h1>Tool Execution Tests</h1><p>Test function not found.</p></div>';
        }
    } else {
        echo '<div class="wrap"><h1>Tool Execution Tests</h1><p>Test file not found.</p></div>';
    }
}

/**
 * Edge case test page callback
 */
function mpai_test_edge_cases_page() {
    // Include the test file
    $test_file = MPAI_PLUGIN_DIR . 'test/edge-cases/test-edge-cases.php';
    if (file_exists($test_file)) {
        include_once $test_file;
        if (function_exists('mpai_run_edge_case_tests')) {
            mpai_run_edge_case_tests();
        } else {
            echo '<div class="wrap"><h1>Edge Case Tests</h1><p>Test function not found.</p></div>';
        }
    } else {
        echo '<div class="wrap"><h1>Edge Case Tests</h1><p>Test file not found.</p></div>';
    }
}

/**
 * Input validator test page callback
 */
function mpai_test_input_validator_page() {
    // Include the test file
    $test_file = MPAI_PLUGIN_DIR . 'test/test-input-validator.php';
    if (file_exists($test_file)) {
        include_once $test_file;
        // The test file will output its own content
    } else {
        echo '<div class="wrap"><h1>Input Validator Tests</h1><p>Test file not found.</p></div>';
    }
}