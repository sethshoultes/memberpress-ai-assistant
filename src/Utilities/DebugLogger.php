<?php
/**
 * Debug Logger
 *
 * Provides a function to log debug messages that respects the log level setting.
 *
 * @package MemberpressAiAssistant\Utilities
 */

namespace MemberpressAiAssistant\Utilities;

// Don't load this file directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Log a debug message
 * This function respects the log level setting
 * 
 * @param string $message The message to log
 * @return void
 */
function debug_log($message) {
    // Check if debug logs should be suppressed
    if (class_exists('\\MemberpressAiAssistant\\Utilities\\LoggingUtility') &&
        LoggingUtility::shouldSuppressDebugLogs()) {
        // Don't log debug messages when suppressed
        return;
    }
    
    // Log the message
    error_log($message);
}

/**
 * Function to replace all error_log calls with debug_log
 * This is used to modify the code to use our debug_log function
 * 
 * @param string $file_path The path to the file to modify
 * @return bool Whether the file was modified
 */
function replace_error_log_with_debug_log($file_path) {
    // Check if the file exists
    if (!file_exists($file_path)) {
        return false;
    }
    
    // Get the file contents
    $contents = file_get_contents($file_path);
    
    // Replace \MemberpressAiAssistant\Utilities\debug_log('MPAI Debug with debug_log('MPAI Debug
    $new_contents = str_replace(
        "\MemberpressAiAssistant\Utilities\debug_log('MPAI Debug",
        "\\MemberpressAiAssistant\\Utilities\\debug_log('MPAI Debug",
        $contents
    );
    
    // Replace \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug with debug_log("MPAI Debug
    $new_contents = str_replace(
        '\MemberpressAiAssistant\Utilities\debug_log("MPAI Debug',
        '\\MemberpressAiAssistant\\Utilities\\debug_log("MPAI Debug',
        $new_contents
    );
    
    // Check if the file was modified
    if ($new_contents === $contents) {
        return false;
    }
    
    // Write the new contents to the file
    file_put_contents($file_path, $new_contents);
    
    return true;
}