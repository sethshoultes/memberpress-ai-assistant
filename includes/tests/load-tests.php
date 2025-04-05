<?php
/**
 * Test Loader
 * 
 * Loads all diagnostic test files
 * 
 * @package MemberPress AI Assistant
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define the directory containing test files
$tests_dir = MPAI_PLUGIN_DIR . 'includes/tests';

// Include the system info test
if (file_exists($tests_dir . '/system-info-test.php')) {
    include_once $tests_dir . '/system-info-test.php';
}

// Add hooks to allow plugins and themes to register tests
function mpai_register_diagnostic_tests() {
    do_action('mpai_register_diagnostic_tests');
}
add_action('init', 'mpai_register_diagnostic_tests', 20);