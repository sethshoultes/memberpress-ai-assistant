<?php
/**
 * Manual Error Log Replacement Script
 *
 * This script specifically uses the conversion functions to convert files
 * without requiring WordPress or WP-CLI.
 */

// Include the core conversion functions
require_once __DIR__ . '/replace-error-log.php';

// Define a simplified run function that doesn't require WordPress
function mpai_run_error_log_conversion($file_path, $dry_run = false, $component = '') {
    // Use a simplified version of the mpai_convert_error_logs function
    // that doesn't require WordPress
    
    // Validate the file exists
    if (!file_exists($file_path)) {
        echo "File does not exist: {$file_path}\n";
        return false;
    }

    // Read the file content
    $content = file_get_contents($file_path);
    if ($content === false) {
        echo "Could not read file: {$file_path}\n";
        return false;
    }

    // Process the file line by line
    $lines = explode("\n", $content);
    $new_lines = [];
    $in_block_comment = false;
    $converted = 0;
    $skipped = 0;

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
                $converted++;
                $new_lines[] = $new_line;
            } else {
                $skipped++;
                $new_lines[] = $line;
            }
        } else {
            $new_lines[] = $line;
        }
    }

    // Update the file if not in dry run mode
    if (!$dry_run && $converted > 0) {
        file_put_contents($file_path, implode("\n", $new_lines));
        echo "Converted {$converted} error_log calls in {$file_path}\n";
    } else if ($dry_run && $converted > 0) {
        echo "Would convert {$converted} error_log calls in {$file_path} (dry run)\n";
    } else {
        echo "No error_log calls converted in {$file_path}\n";
    }

    return $converted > 0;
}

// Run the conversion on specific files
$files_to_convert = [
    '../agents/class-mpai-agent-orchestrator.php' => 'orchestrator',
    '../class-mpai-admin.php' => 'admin',
    '../class-mpai-memberpress-api.php' => 'memberpress-api',
    '../direct-ajax-handler.php' => 'direct-ajax'
];

// Convert each file
foreach ($files_to_convert as $file => $component) {
    $file_path = __DIR__ . '/' . $file;
    echo "Processing {$file_path}...\n";
    mpai_run_error_log_conversion($file_path, false, $component);
}

echo "All files processed.\n";