<?php
/**
 * Register integration tests with MemberPress AI Assistant
 *
 * @package MemberPress AI Assistant
 */

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

// Include test files
require_once MPAI_PLUGIN_DIR . 'test/integration/test-tool-execution.php';
require_once MPAI_PLUGIN_DIR . 'test/integration/diagnostics-section.php';

/**
 * Register all integration tests
 */
function mpai_register_integration_tests() {
    // Add Tool Execution tests to diagnostics
    mpai_add_tool_execution_tests_to_diagnostics();
    
    // Add action to load integration tests at init
    add_action('init', 'mpai_load_integration_tests');
    
    // Register AJAX handler for running integration tests
    add_action('wp_ajax_mpai_run_tool_integration_tests', 'mpai_ajax_run_tool_integration_tests');
}

/**
 * AJAX handler for running tool integration tests
 */
function mpai_ajax_run_tool_integration_tests() {
    // Check nonce
    check_ajax_referer('mpai_nonce', 'nonce');
    
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    ob_start();
    mpai_display_all_tool_execution_tests();
    $output = ob_get_clean();
    
    wp_send_json_success($output);
}

/**
 * Load integration tests
 */
function mpai_load_integration_tests() {
    // Only load in admin context
    if (!is_admin()) {
        return;
    }
    
    // Add integration test menu if user has capabilities
    if (current_user_can('manage_options')) {
        add_action('admin_menu', 'mpai_add_integration_tests_menu');
    }
}

/**
 * Add integration tests admin menu
 */
function mpai_add_integration_tests_menu() {
    // Add as a submenu page under MemberPress AI settings
    add_submenu_page(
        'mpai-settings', // Parent slug
        'Integration Tests', // Page title
        'Integration Tests', // Menu title
        'manage_options', // Capability
        'mpai-integration-tests', // Menu slug
        'mpai_display_integration_tests_page' // Callback function
    );
}

/**
 * Display the integration tests page
 */
function mpai_display_integration_tests_page() {
    ?>
    <div class="wrap">
        <h1>MemberPress AI Assistant Integration Tests</h1>
        
        <div class="notice notice-info">
            <p>These tests verify that MemberPress AI Assistant tools and components work correctly with the WordPress environment and external systems.</p>
            <p><strong>Note:</strong> Running these tests may create temporary data (posts, pages, etc.) that will be cleaned up afterward.</p>
        </div>
        
        <div class="card">
            <?php mpai_display_all_tool_execution_tests(); ?>
        </div>
    </div>
    <?php
}

// Register integration tests
mpai_register_integration_tests();