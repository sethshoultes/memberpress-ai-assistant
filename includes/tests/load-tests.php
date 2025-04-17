<?php
/**
 * Test Loader
 * 
 * Loads test files (non-diagnostic tests, since diagnostic tests have been moved to a separate plugin)
 * 
 * @package MemberPress AI Assistant
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// This file is kept as a placeholder for future non-diagnostic tests
// All diagnostic tests have been moved to the memberpress-ai-assistant-diagnostics plugin

// Add hooks to allow plugins and themes to register tests (kept for backward compatibility)
// The action hook mpai_register_diagnostic_tests has been moved to the diagnostics plugin
// This function is kept to avoid errors in case any third-party code calls it
function mpai_register_diagnostic_tests() {
    // This hook is now managed by the diagnostics plugin
    do_action('mpai_register_core_tests');
}
add_action('init', 'mpai_register_diagnostic_tests', 20);