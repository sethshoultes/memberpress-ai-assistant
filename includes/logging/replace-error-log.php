<?php
/**
 * Error Log Replacement Script
 *
 * This script helps convert direct error_log() calls to standardized mpai_log_* functions.
 * It can be used both manually or through WP-CLI.
 */

// Make sure we're in WordPress
if (!defined('ABSPATH')) {
    die('This script must be run within WordPress');
}

/**
 * Convert an error_log call to an mpai_log call
 *
 * @param string $file_path Path to the file
 * @param bool $dry_run Whether to actually make changes or just report
 * @param string $component Optional component name to use for all logs
 * @return array Statistics about conversions
 */
function mpai_convert_error_logs($file_path, $dry_run = true, $component = '') {
    // Validate the file exists
    if (!file_exists($file_path)) {
        return [
            'success' => false,
            'message' => "File does not exist: {$file_path}",
            'file' => $file_path,
            'converted' => 0,
            'skipped' => 0,
        ];
    }

    // Read the file content
    $content = file_get_contents($file_path);
    if ($content === false) {
        return [
            'success' => false,
            'message' => "Could not read file: {$file_path}",
            'file' => $file_path,
            'converted' => 0,
            'skipped' => 0,
        ];
    }

    // Initialize statistics
    $stats = [
        'success' => true,
        'message' => '',
        'file' => $file_path,
        'converted' => 0,
        'skipped' => 0,
    ];

    // Process the file line by line
    $lines = explode("\n", $content);
    $new_lines = [];
    $in_block_comment = false;

    foreach ($lines as $line) {
        // Check if we're in a block comment
        if (strpos($line, '/*') !== false && strpos($line, '*/') === false) {
            $in_block_comment = true;
        }
        if (strpos($line, '*/') !== false) {
            $in_block_comment = false;
        }

        // Skip if in comment
        if ($in_block_comment || preg_match('/^\s*\/\//', $line) || preg_match('/^\s*\*/', $line)) {
            $new_lines[] = $line;
            continue;
        }

        // Check for error_log
        if (preg_match('/error_log\s*\(\s*[\'"]MPAI(?:[:\s]+)?(.*?)[\'"](?:\s*\.\s*(.*?))?\s*\)/', $line, $matches)) {
            // Determine log level based on message content
            $log_level = 'debug'; // Default level
            $message = isset($matches[1]) ? $matches[1] : '';
            $context = isset($matches[2]) ? $matches[2] : '';

            // Try to determine log level from message
            $message_lower = strtolower($message);
            if (strpos($message_lower, 'error') !== false || strpos($message_lower, 'fatal') !== false || 
                strpos($message_lower, 'exception') !== false || strpos($message_lower, 'fail') !== false) {
                $log_level = 'error';
            } elseif (strpos($message_lower, 'warn') !== false) {
                $log_level = 'warning';
            } elseif (strpos($message_lower, 'info') !== false || strpos($message_lower, 'init') !== false || 
                      strpos($message_lower, 'success') !== false) {
                $log_level = 'info';
            }

            // Determine component name
            $extracted_component = '';
            if (empty($component)) {
                // Extract component from file path
                $path_parts = explode('/', $file_path);
                $file_name = end($path_parts);
                $file_parts = explode('.', $file_name);
                
                // Try to get a sensible component name
                if (count($file_parts) > 1) {
                    $extracted_component = str_replace(['class-mpai-', 'mpai-'], '', $file_parts[0]);
                    
                    // Remove 'class' prefix if it exists
                    $extracted_component = str_replace('class-', '', $extracted_component);
                    
                    // Try to clean up common patterns
                    if (strpos($extracted_component, '-') !== false) {
                        $components = explode('-', $extracted_component);
                        $extracted_component = $components[0];
                    }
                }
            }
            
            $component_name = !empty($component) ? $component : $extracted_component;
            
            // Handle different syntax patterns
            if (!empty($context)) {
                // For cases where there's concatenation
                $new_line = str_replace(
                    "error_log('MPAI" . (strpos($message, ':') === 0 ? '' : ': ') . "$message' . $context)",
                    "mpai_log_$log_level('$message' . $context, '$component_name')",
                    $line
                );
            } else {
                // For simple cases
                $new_line = str_replace(
                    "error_log('MPAI" . (strpos($message, ':') === 0 ? '' : ': ') . "$message')",
                    "mpai_log_$log_level('$message', '$component_name')",
                    $line
                );
            }
            
            if ($new_line !== $line) {
                $stats['converted']++;
                $new_lines[] = $new_line;
            } else {
                $stats['skipped']++;
                $new_lines[] = $line;
            }
        } else {
            $new_lines[] = $line;
        }
    }

    // Update the file if not in dry run mode
    if (!$dry_run && $stats['converted'] > 0) {
        file_put_contents($file_path, implode("\n", $new_lines));
        $stats['message'] = "Converted {$stats['converted']} error_log calls in {$file_path}";
    } else if ($dry_run && $stats['converted'] > 0) {
        $stats['message'] = "Would convert {$stats['converted']} error_log calls in {$file_path} (dry run)";
    } else {
        $stats['message'] = "No error_log calls converted in {$file_path}";
    }

    return $stats;
}

/**
 * Process a directory recursively to convert error_log calls
 *
 * @param string $dir_path Path to the directory
 * @param bool $dry_run Whether to actually make changes or just report
 * @param string $component Optional component name to use for all logs
 * @return array Statistics about conversions
 */
function mpai_convert_error_logs_in_dir($dir_path, $dry_run = true, $component = '') {
    // Validate the directory exists
    if (!is_dir($dir_path)) {
        return [
            'success' => false,
            'message' => "Directory does not exist: {$dir_path}",
            'converted_total' => 0,
            'skipped_total' => 0,
            'files_processed' => 0,
            'files_with_error_logs' => 0,
        ];
    }

    // Initialize statistics
    $stats = [
        'success' => true,
        'message' => '',
        'converted_total' => 0,
        'skipped_total' => 0,
        'files_processed' => 0,
        'files_with_error_logs' => 0,
        'file_stats' => [],
    ];

    // Get all PHP files in directory
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir_path, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $file_path = $file->getRealPath();
            $file_stats = mpai_convert_error_logs($file_path, $dry_run, $component);
            
            $stats['files_processed']++;
            $stats['converted_total'] += $file_stats['converted'];
            $stats['skipped_total'] += $file_stats['skipped'];
            
            if ($file_stats['converted'] > 0) {
                $stats['files_with_error_logs']++;
                $stats['file_stats'][$file_path] = $file_stats;
            }
        }
    }

    // Update summary message
    if ($dry_run) {
        $stats['message'] = "Would convert {$stats['converted_total']} error_log calls in {$stats['files_with_error_logs']} files (dry run)";
    } else {
        $stats['message'] = "Converted {$stats['converted_total']} error_log calls in {$stats['files_with_error_logs']} files";
    }

    return $stats;
}

// Add functions for WP-CLI usage
if (defined('WP_CLI') && WP_CLI) {
    /**
     * WP-CLI command to convert error_log calls
     */
    class MPAI_Error_Log_Conversion_Command {
        /**
         * Convert error_log calls to standardized mpai_log_* functions
         *
         * ## OPTIONS
         *
         * [--file=<file>]
         * : Process a specific file
         *
         * [--dir=<dir>]
         * : Process a directory recursively
         *
         * [--component=<component>]
         * : Optional component name to use for all logs
         *
         * [--dry-run]
         * : Don't make any changes, just report what would be done
         *
         * ## EXAMPLES
         *
         * wp mpai convert_error_logs --dir=includes --dry-run
         * wp mpai convert_error_logs --file=includes/class-mpai-admin.php --component=admin
         */
        public function convert_error_logs($args, $assoc_args) {
            $dry_run = isset($assoc_args['dry-run']);
            $component = isset($assoc_args['component']) ? $assoc_args['component'] : '';

            if (isset($assoc_args['file'])) {
                $file_path = $assoc_args['file'];
                $stats = mpai_convert_error_logs($file_path, $dry_run, $component);
                
                if ($stats['success']) {
                    WP_CLI::success($stats['message']);
                } else {
                    WP_CLI::error($stats['message']);
                }
            } else if (isset($assoc_args['dir'])) {
                $dir_path = $assoc_args['dir'];
                $stats = mpai_convert_error_logs_in_dir($dir_path, $dry_run, $component);
                
                if ($stats['success']) {
                    WP_CLI::success($stats['message']);
                    
                    if ($stats['files_with_error_logs'] > 0) {
                        WP_CLI::line("\nFiles with error_log calls:");
                        foreach ($stats['file_stats'] as $file_path => $file_stats) {
                            WP_CLI::line("- {$file_path}: {$file_stats['converted']} converted");
                        }
                    }
                } else {
                    WP_CLI::error($stats['message']);
                }
            } else {
                WP_CLI::error('You must specify either --file or --dir');
            }
        }
    }

    // Register the command
    WP_CLI::add_command('mpai convert_error_logs', 'MPAI_Error_Log_Conversion_Command');
}