<?php
/**
 * Replace Debug Logs
 *
 * This script replaces all \MemberpressAiAssistant\Utilities\debug_log('MPAI Debug') calls with debug_log('MPAI Debug')
 * to respect the log level setting.
 *
 * @package MemberpressAiAssistant\Utilities
 */

// Don't load this file directly
if (!defined('ABSPATH')) {
    exit;
}

// Include the DebugLogger file
require_once __DIR__ . '/DebugLogger.php';

/**
 * Function to recursively scan a directory for PHP files
 * 
 * @param string $dir The directory to scan
 * @param array $results The results array
 * @return array The PHP files found
 */
function scan_dir_for_php_files($dir, &$results = []) {
    $files = scandir($dir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        $path = $dir . '/' . $file;
        
        if (is_dir($path)) {
            scan_dir_for_php_files($path, $results);
        } else if (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
            $results[] = $path;
        }
    }
    
    return $results;
}

/**
 * Function to replace all error_log calls with debug_log
 * 
 * @param string $plugin_dir The plugin directory
 * @return int The number of files modified
 */
function replace_all_debug_logs($plugin_dir) {
    // Get all PHP files
    $files = scan_dir_for_php_files($plugin_dir);
    
    // Count the number of files modified
    $modified_count = 0;
    
    // Replace error_log with debug_log in each file
    foreach ($files as $file) {
        if (\MemberpressAiAssistant\Utilities\replace_error_log_with_debug_log($file)) {
            $modified_count++;
        }
    }
    
    return $modified_count;
}

// Run the script
$plugin_dir = defined('MPAI_PLUGIN_DIR') ? MPAI_PLUGIN_DIR : dirname(dirname(__DIR__));
$modified_count = replace_all_debug_logs($plugin_dir);

// Log the results
error_log("Replaced debug logs in {$modified_count} files");