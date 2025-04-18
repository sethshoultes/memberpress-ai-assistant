<?php
/**
 * Direct Test Script for the Error Recovery System
 *
 * This is a simplified standalone test file that can be accessed directly
 * to debug errors in the Error Recovery System.
 */

// Set header for raw output
header('Content-Type: text/plain');

// Turn on error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Error Recovery System Direct Test ===\n\n";

try {
    // Try to load WordPress
    $wp_load_path = dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/wp-load.php';
    
    echo "Looking for WordPress at: $wp_load_path\n";
    
    if (file_exists($wp_load_path)) {
        echo "WordPress found, loading...\n";
        require_once($wp_load_path);
        echo "WordPress loaded successfully\n";
    } else {
        echo "ERROR: WordPress not found at $wp_load_path\n";
        die();
    }

    // Define plugin directory if not already defined
    if (!defined('MPAI_PLUGIN_DIR')) {
        define('MPAI_PLUGIN_DIR', plugin_dir_path(dirname(__FILE__)));
    }
    
    echo "Plugin directory: " . MPAI_PLUGIN_DIR . "\n";
    
    // Load dependencies
    echo "\nLoading dependencies:\n";
    
    // Load Error Recovery System
    $error_recovery_file = MPAI_PLUGIN_DIR . 'includes/class-mpai-error-recovery.php';
    echo "Loading Error Recovery from: $error_recovery_file\n";
    
    if (file_exists($error_recovery_file)) {
        require_once($error_recovery_file);
        echo "Error Recovery loaded successfully\n";
    } else {
        echo "ERROR: Error Recovery file not found\n";
    }
    
    // Load Plugin Logger
    $plugin_logger_file = MPAI_PLUGIN_DIR . 'includes/class-mpai-plugin-logger.php';
    echo "Loading Plugin Logger from: $plugin_logger_file\n";
    
    if (file_exists($plugin_logger_file)) {
        require_once($plugin_logger_file);
        echo "Plugin Logger loaded successfully\n";
    } else {
        echo "ERROR: Plugin Logger file not found\n";
    }
    
    // Check class existence
    echo "\nChecking classes:\n";
    echo "MPAI_Error_Recovery exists: " . (class_exists('MPAI_Error_Recovery') ? 'Yes' : 'No') . "\n";
    echo "MPAI_Plugin_Logger exists: " . (class_exists('MPAI_Plugin_Logger') ? 'Yes' : 'No') . "\n";
    echo "WP_Error exists: " . (class_exists('WP_Error') ? 'Yes' : 'No') . "\n";
    
    // Define functions if needed
    echo "\nDefining functions if needed:\n";
    
    if (!function_exists('mpai_init_plugin_logger')) {
        echo "Defining mpai_init_plugin_logger function\n";
        function mpai_init_plugin_logger() {
            return MPAI_Plugin_Logger::get_instance();
        }
    } else {
        echo "mpai_init_plugin_logger already defined\n";
    }
    
    if (!function_exists('mpai_init_error_recovery')) {
        echo "Defining mpai_init_error_recovery function\n";
        function mpai_init_error_recovery() {
            return MPAI_Error_Recovery::get_instance();
        }
    } else {
        echo "mpai_init_error_recovery already defined\n";
    }
    
    // Run basic tests
    echo "\nRunning basic tests:\n";
    
    // Test WP_Error creation
    echo "Testing WP_Error creation: ";
    $test_error = new WP_Error('test_error', 'This is a test error');
    echo "Success - " . $test_error->get_error_message() . "\n";
    
    // Test Plugin Logger initialization
    echo "Testing Plugin Logger initialization: ";
    $plugin_logger = mpai_init_plugin_logger();
    echo ($plugin_logger ? "Success" : "Failed") . "\n";
    
    // Test Error Recovery initialization
    echo "Testing Error Recovery initialization: ";
    $error_recovery = mpai_init_error_recovery();
    echo ($error_recovery ? "Success" : "Failed") . "\n";
    
    // Test Error Creation
    echo "Testing Error Creation: ";
    $custom_error = $error_recovery->create_error('test', 'test_code', 'Test error message');
    echo ($custom_error && is_wp_error($custom_error) ? "Success" : "Failed") . "\n";
    
    echo "\n=== Test Completed Successfully ===\n";
    
} catch (Exception $e) {
    echo "\nERROR: Exception occurred during testing:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}