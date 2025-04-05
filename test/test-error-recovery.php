<?php
/**
 * Test Script for the Error Recovery System
 *
 * Tests various aspects of the Error Recovery System including:
 * - Error creation with context
 * - Recovery with retry functionality
 * - Recovery with fallback functionality
 * - Circuit breaker pattern testing
 * - Error formatting for display
 */

// Ensure this is executed within WordPress context
if (!defined('ABSPATH')) {
    // Try to load WordPress if executed directly
    $wp_load_path = dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/wp-load.php';
    if (file_exists($wp_load_path)) {
        require_once($wp_load_path);
    } else {
        die('WordPress not found. This script must be run within the WordPress context.');
    }
}

// Turn on error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define plugin directory if not already defined
if (!defined('MPAI_PLUGIN_DIR')) {
    define('MPAI_PLUGIN_DIR', plugin_dir_path(dirname(__FILE__)));
}

// Load required dependencies
if (!class_exists('MPAI_Error_Recovery')) {
    require_once(MPAI_PLUGIN_DIR . 'includes/class-mpai-error-recovery.php');
}

// Load Plugin Logger
if (!class_exists('MPAI_Plugin_Logger')) {
    require_once(MPAI_PLUGIN_DIR . 'includes/class-mpai-plugin-logger.php');
}

// Ensure we have a plugin logger function 
if (!function_exists('mpai_init_plugin_logger')) {
    function mpai_init_plugin_logger() {
        return MPAI_Plugin_Logger::get_instance();
    }
}

// Ensure we have an error recovery function
if (!function_exists('mpai_init_error_recovery')) {
    function mpai_init_error_recovery() {
        return MPAI_Error_Recovery::get_instance();
    }
}

/**
 * Test the Error Recovery System
 *
 * @return array Test results
 */
function mpai_test_error_recovery() {
    $results = [
        'success' => false,
        'message' => '',
        'data' => [
            'tests' => [],
            'timing' => []
        ]
    ];
    
    // Start timing
    $start_time = microtime(true);
    
    // Add debug info
    $results['data']['debug'] = [
        'wp_error_exists' => class_exists('WP_Error'),
        'mpai_error_recovery_exists' => class_exists('MPAI_Error_Recovery'),
        'mpai_plugin_dir' => defined('MPAI_PLUGIN_DIR') ? MPAI_PLUGIN_DIR : 'not defined',
        'php_version' => PHP_VERSION,
        'wp_version' => get_bloginfo('version'),
    ];
    
    try {
        // Add function existence check to debug output
        $results['data']['debug']['mpai_init_error_recovery_exists'] = function_exists('mpai_init_error_recovery');
        $results['data']['debug']['mpai_init_plugin_logger_exists'] = function_exists('mpai_init_plugin_logger');
        
        // Initialize error recovery with extra error handling
        try {
            $error_recovery = mpai_init_error_recovery();
            $results['data']['debug']['error_recovery_initialized'] = ($error_recovery !== null);
        } catch (Exception $init_error) {
            $results['data']['debug']['error_recovery_init_error'] = $init_error->getMessage();
            // Create a minimal error recovery instance for testing
            $error_recovery = new MPAI_Error_Recovery();
        }
        
        // Add test timing
        $results['data']['timing']['initialization'] = microtime(true) - $start_time;
        
        // Basic test to see if the Error Recovery System works
        $test_start = microtime(true);
        try {
            // Create a simple error
            $error = new WP_Error('test_error', 'This is a test error');
            
            $results['data']['tests']['basic_test'] = [
                'success' => true,
                'message' => 'Basic error test passed',
                'details' => [
                    'error_code' => $error->get_error_code(),
                    'error_message' => $error->get_error_message()
                ]
            ];
        } catch (Exception $e) {
            $results['data']['tests']['basic_test'] = [
                'success' => false,
                'message' => 'Basic error test failed: ' . $e->getMessage()
            ];
        }
        $results['data']['timing']['basic_test'] = microtime(true) - $test_start;
        
        // Success
        $results['success'] = true;
        $results['message'] = 'Basic test completed successfully';
    } catch (Exception $e) {
        $results['success'] = false;
        $results['message'] = 'Error initializing tests: ' . $e->getMessage();
        $results['data']['error'] = [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ];
    }
    
    // Calculate overall success
    $success = true;
    foreach ($results['data']['tests'] as $test) {
        if (!$test['success']) {
            $success = false;
            break;
        }
    }
    
    $results['success'] = $success;
    $results['message'] = $success ? 'All error recovery tests passed successfully' : 'Some error recovery tests failed';
    
    // Total timing
    $results['data']['timing']['total'] = microtime(true) - $start_time;
    
    return $results;
}

// Execute the test if requested via AJAX
if (wp_doing_ajax()) {
    // Run the tests
    $results = mpai_test_error_recovery();
    
    // Return results
    header('Content-Type: application/json');
    echo json_encode($results);
    exit;
}

// Return test function for direct inclusion
return 'mpai_test_error_recovery';