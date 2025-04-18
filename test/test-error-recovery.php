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
        
        // Test 1: Basic Error Creation
        $test_start = microtime(true);
        try {
            // Create an error using the create_error method
            $api_error = $error_recovery->create_error(
                MPAI_Error_Recovery::ERROR_TYPE_API,
                'test_api_error',
                'This is a test API error',
                ['api' => 'test_api', 'endpoint' => 'test_endpoint'],
                MPAI_Error_Recovery::SEVERITY_ERROR
            );
            
            // Verify error is created correctly
            $error_data = $api_error->get_error_data();
            $error_success = (
                $api_error instanceof WP_Error &&
                $api_error->get_error_code() === 'test_api_error' &&
                isset($error_data['type']) && $error_data['type'] === MPAI_Error_Recovery::ERROR_TYPE_API &&
                isset($error_data['severity']) && $error_data['severity'] === MPAI_Error_Recovery::SEVERITY_ERROR
            );
            
            $results['data']['tests']['error_creation'] = [
                'success' => $error_success,
                'message' => $error_success ? 'Error creation test passed' : 'Error creation test failed',
                'details' => [
                    'error_code' => $api_error->get_error_code(),
                    'error_message' => $api_error->get_error_message(),
                    'error_data' => $error_data
                ]
            ];
        } catch (Exception $e) {
            $results['data']['tests']['error_creation'] = [
                'success' => false,
                'message' => 'Error creation test failed: ' . $e->getMessage()
            ];
        }
        $results['data']['timing']['error_creation'] = microtime(true) - $test_start;
        
        // Test 2: Error Recovery with Retry
        $test_start = microtime(true);
        try {
            // Create an error with retry strategy
            $retry_error = $error_recovery->create_error(
                MPAI_Error_Recovery::ERROR_TYPE_API,
                'retry_test',
                'This is a retry test error'
            );
            
            // Mock retry functions
            $retry_count = 0;
            $retry_callback = function() use (&$retry_count) {
                $retry_count++;
                // Return success on the second attempt
                if ($retry_count >= 2) {
                    return 'Retry success';
                }
                return new WP_Error('retry_fail', 'Retry failed on attempt ' . $retry_count);
            };
            
            // Handle the error with retry
            $retry_result = $error_recovery->handle_error(
                $retry_error,
                'test_component',
                $retry_callback
            );
            
            $retry_success = ($retry_result === 'Retry success' && $retry_count === 2);
            
            $results['data']['tests']['retry_mechanism'] = [
                'success' => $retry_success,
                'message' => $retry_success ? 'Retry mechanism test passed' : 'Retry mechanism test failed',
                'details' => [
                    'retry_count' => $retry_count,
                    'result' => $retry_result
                ]
            ];
        } catch (Exception $e) {
            $results['data']['tests']['retry_mechanism'] = [
                'success' => false,
                'message' => 'Retry mechanism test failed: ' . $e->getMessage()
            ];
        }
        $results['data']['timing']['retry_mechanism'] = microtime(true) - $test_start;
        
        // Test 3: Fallback Mechanism
        $test_start = microtime(true);
        try {
            // Create an error that will need fallback
            $fallback_error = $error_recovery->create_error(
                MPAI_Error_Recovery::ERROR_TYPE_API,
                'fallback_test',
                'This is a fallback test error'
            );
            
            // Mock retry and fallback functions
            $retry_callback = function() {
                // Always fail to force fallback
                return new WP_Error('retry_fail', 'Retry always fails for fallback test');
            };
            
            $fallback_callback = function() {
                return 'Fallback success';
            };
            
            // Handle the error with fallback
            $fallback_result = $error_recovery->handle_error(
                $fallback_error,
                'test_component',
                $retry_callback,
                [],
                $fallback_callback
            );
            
            $fallback_success = ($fallback_result === 'Fallback success');
            
            $results['data']['tests']['fallback_mechanism'] = [
                'success' => $fallback_success,
                'message' => $fallback_success ? 'Fallback mechanism test passed' : 'Fallback mechanism test failed',
                'details' => [
                    'result' => $fallback_result
                ]
            ];
        } catch (Exception $e) {
            $results['data']['tests']['fallback_mechanism'] = [
                'success' => false,
                'message' => 'Fallback mechanism test failed: ' . $e->getMessage()
            ];
        }
        $results['data']['timing']['fallback_mechanism'] = microtime(true) - $test_start;
        
        // Test 4: Circuit Breaker
        $test_start = microtime(true);
        try {
            // Create an error type and component for circuit breaker
            $circuit_test_type = MPAI_Error_Recovery::ERROR_TYPE_TOOL;
            $circuit_test_component = 'circuit_breaker_test';
            
            // Force the circuit breaker to trip by creating errors
            $circuit_breaker_errors = [];
            
            // Get the circuit breaker threshold from the recovery strategy
            $strategy = $error_recovery->get_recovery_strategy($circuit_test_type);
            $threshold = isset($strategy['circuit_breaker']['threshold']) ? 
                        $strategy['circuit_breaker']['threshold'] : 3;
                        
            // Create threshold + 1 errors to ensure the circuit breaker trips
            for ($i = 0; $i < $threshold + 1; $i++) {
                $circuit_breaker_errors[] = $error_recovery->create_error(
                    $circuit_test_type,
                    'circuit_test',
                    'Circuit breaker test error #' . ($i + 1),
                    ['component' => $circuit_test_component]
                );
                
                // Force error counting for the component
                $error_recovery->handle_error(
                    $circuit_breaker_errors[$i],
                    $circuit_test_component,
                    function() { return new WP_Error('always_fails', 'Always fails'); }
                );
            }
            
            // Now check if the circuit breaker is tripped
            $is_tripped = $error_recovery->is_circuit_breaker_tripped($circuit_test_type, $circuit_test_component);
            
            $results['data']['tests']['circuit_breaker'] = [
                'success' => $is_tripped,
                'message' => $is_tripped ? 'Circuit breaker test passed' : 'Circuit breaker test failed',
                'details' => [
                    'threshold' => $threshold,
                    'errors_created' => count($circuit_breaker_errors),
                    'circuit_tripped' => $is_tripped
                ]
            ];
        } catch (Exception $e) {
            $results['data']['tests']['circuit_breaker'] = [
                'success' => false,
                'message' => 'Circuit breaker test failed: ' . $e->getMessage()
            ];
        }
        $results['data']['timing']['circuit_breaker'] = microtime(true) - $test_start;
        
        // Test 5: Error Formatting
        $test_start = microtime(true);
        try {
            // Create an error to format
            $format_error = $error_recovery->create_error(
                MPAI_Error_Recovery::ERROR_TYPE_VALIDATION,
                'format_test',
                'This is a validation error for formatting test',
                ['field' => 'test_field', 'value' => 'invalid_value'],
                MPAI_Error_Recovery::SEVERITY_WARNING
            );
            
            // Format the error for display
            $formatted_error = $error_recovery->format_error_for_display($format_error);
            $formatted_error_with_debug = $error_recovery->format_error_for_display($format_error, true);
            
            $formatting_success = (
                !empty($formatted_error) && 
                strpos($formatted_error, 'Invalid input provided') !== false &&
                !empty($formatted_error_with_debug) &&
                strpos($formatted_error_with_debug, 'Debug Information') !== false
            );
            
            $results['data']['tests']['error_formatting'] = [
                'success' => $formatting_success,
                'message' => $formatting_success ? 'Error formatting test passed' : 'Error formatting test failed',
                'details' => [
                    'formatted_error' => $formatted_error,
                    'formatted_with_debug' => $formatted_error_with_debug
                ]
            ];
        } catch (Exception $e) {
            $results['data']['tests']['error_formatting'] = [
                'success' => false,
                'message' => 'Error formatting test failed: ' . $e->getMessage()
            ];
        }
        $results['data']['timing']['error_formatting'] = microtime(true) - $test_start;
        
        // Success
        $results['success'] = true;
        $results['message'] = 'Error recovery tests completed successfully';
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