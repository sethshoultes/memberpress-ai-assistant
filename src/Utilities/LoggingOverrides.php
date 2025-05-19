<?php
/**
 * Logging Overrides
 *
 * Provides functions to intercept and filter debug logs.
 *
 * @package MemberpressAiAssistant\Utilities
 */

namespace MemberpressAiAssistant\Utilities;

// Don't load this file directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Function to check if a debug log should be suppressed
 *
 * @param string $message The log message
 * @return bool Whether the log should be suppressed
 */
function should_suppress_debug_log($message) {
    // Check if this is a debug log
    if (is_string($message) && strpos($message, 'MPAI Debug') === 0) {
        // Check if debug logs are suppressed
        if (LoggingUtility::$suppressDebugLogs) {
            return true;
        }
    }
    
    return false;
}

/**
 * Override the error_log function for MPAI Debug logs
 * This is called from the main plugin file
 */
function override_error_log() {
    // We can't actually override the built-in error_log function
    // So we'll use output buffering to capture and filter the logs
    
    // Start output buffering
    ob_start(function($buffer) {
        // Filter out debug logs if they should be suppressed
        $lines = explode("\n", $buffer);
        $filtered_lines = [];
        
        foreach ($lines as $line) {
            if (!should_suppress_debug_log($line)) {
                $filtered_lines[] = $line;
            }
        }
        
        return implode("\n", $filtered_lines);
    });
}