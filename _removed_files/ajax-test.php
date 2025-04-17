<?php
/**
 * AJAX Test Script
 * 
 * Simple test script to directly test AJAX functionality
 */

// Load WordPress
// Calculate the path to wp-load.php
$wp_load_path = dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . '/wp-load.php';

// Verify path exists
if (!file_exists($wp_load_path)) {
    echo "Error: wp-load.php not found at {$wp_load_path}<br>";
    // Try alternative relative path
    $wp_load_path = '../../../../wp-load.php';
    echo "Trying alternative path: {$wp_load_path}<br>";
}

require_once($wp_load_path);

// Check if user is logged in and is admin
if (!current_user_can('manage_options')) {
    wp_die('You do not have sufficient permissions to access this page.');
}

// Output header for JSON
header('Content-Type: application/json');

// Output debug information
$debug = array(
    'time' => current_time('mysql'),
    'user_id' => get_current_user_id(),
    'is_admin' => current_user_can('manage_options'),
    'site_url' => site_url(),
    'ajax_url' => admin_url('admin-ajax.php'),
    'admin_ajax_file' => ABSPATH . 'wp-admin/admin-ajax.php',
    'admin_ajax_exists' => file_exists(ABSPATH . 'wp-admin/admin-ajax.php'),
    'server' => $_SERVER,
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'php_version' => phpversion(),
    'wp_version' => get_bloginfo('version'),
    'request' => array(
        'get' => $_GET,
        'post' => $_POST
    ),
    'wp_ajax_hooks' => array()
);

// Check for all registered AJAX actions
global $wp_filter;
if (isset($wp_filter['wp_ajax_mpai_simple_test'])) {
    $debug['wp_ajax_hooks']['simple_test'] = 'Registered';
} else {
    $debug['wp_ajax_hooks']['simple_test'] = 'Not registered';
}

if (isset($wp_filter['wp_ajax_mpai_debug_nonce'])) {
    $debug['wp_ajax_hooks']['debug_nonce'] = 'Registered';
} else {
    $debug['wp_ajax_hooks']['debug_nonce'] = 'Not registered';
}

if (isset($wp_filter['wp_ajax_mpai_test_openai_api'])) {
    $debug['wp_ajax_hooks']['openai_api'] = 'Registered';
} else {
    $debug['wp_ajax_hooks']['openai_api'] = 'Not registered';
}

if (isset($wp_filter['wp_ajax_mpai_test_memberpress_api'])) {
    $debug['wp_ajax_hooks']['memberpress_api'] = 'Registered';
} else {
    $debug['wp_ajax_hooks']['memberpress_api'] = 'Not registered';
}

// Test creating a nonce
$debug['nonce'] = array(
    'test_nonce' => wp_create_nonce('mpai_nonce'),
    'settings_nonce' => wp_create_nonce('mpai_settings_nonce'),
);

// Manual AJAX test
$debug['direct_ajax_test'] = 'Not run';

// Perform a manual AJAX test if requested
if (isset($_GET['run_ajax_test'])) {
    ob_start();
    
    // Set up the POST parameters
    $_POST['action'] = 'mpai_simple_test';
    $_POST['test_data'] = 'This is a direct test';
    
    // Include the admin-ajax.php file directly
    try {
        include(ABSPATH . 'wp-admin/admin-ajax.php');
    } catch (Exception $e) {
        $debug['direct_ajax_test'] = 'Error: ' . $e->getMessage();
    }
    
    $output = ob_get_clean();
    $debug['direct_ajax_test'] = $output;
}

// Output the debug info as JSON
echo json_encode($debug, JSON_PRETTY_PRINT);