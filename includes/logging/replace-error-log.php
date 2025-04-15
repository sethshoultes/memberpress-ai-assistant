<?php
/**
 * MemberPress AI Assistant - Replace error_log Utility
 *
 * This file provides utility functions to replace direct error_log calls
 * with our unified logger system
 *
 * @package MemberPress AI Assistant
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Replace all error_log calls in a specific file
 *
 * @param string $file_path        Path to the file to modify.
 * @param bool   $create_backup    Whether to create a backup of the original file.
 * @param bool   $use_regex        Whether to use regex for replacement (more accurate but slower).
 * @return array Result information: success status and counts.
 */
function mpai_replace_error_logs_in_file( $file_path, $create_backup = true, $use_regex = true ) {
    if ( ! file_exists( $file_path ) ) {
        return array(
            'success' => false,
            'message' => "File does not exist: $file_path",
            'count' => 0,
        );
    }

    // Read file contents
    $content = file_get_contents( $file_path );
    if ( false === $content ) {
        return array(
            'success' => false,
            'message' => "Could not read file: $file_path",
            'count' => 0,
        );
    }

    // Create backup if requested
    if ( $create_backup ) {
        $backup_path = $file_path . '.bak';
        if ( ! file_put_contents( $backup_path, $content ) ) {
            return array(
                'success' => false,
                'message' => "Could not create backup file: $backup_path",
                'count' => 0,
            );
        }
    }

    $count = 0;
    $new_content = $content;

    if ( $use_regex ) {
        // Use regex replacement for better accuracy
        // This pattern matches error_log calls with different parameter styles
        // 1. error_log('string');
        // 2. error_log("string");
        // 3. error_log("string" . $var);
        // 4. error_log(sprintf(...));
        $pattern = '/error_log\s*\(\s*(([\'"])((?:MPAI: )?.*?)\2|([^\)]+))\s*\)\s*;/';
        
        $new_content = preg_replace_callback(
            $pattern,
            function( $matches ) use ( &$count ) {
                $count++;
                
                // Extract the log message
                if ( ! empty( $matches[3] ) ) {
                    // Direct string in quotes
                    $message = $matches[3];
                    $has_prefix = ( strpos( $message, 'MPAI: ' ) === 0 );
                    
                    if ( $has_prefix ) {
                        // Remove the MPAI: prefix since the logger adds it
                        $message = substr( $message, 6 );
                    }
                    
                    // Use the same quote style as the original
                    $quote = $matches[2];
                    return "mpai_log_info($quote$message$quote);";
                } else {
                    // Complex expression (like sprintf or concatenation)
                    $message = $matches[1];
                    return "mpai_log_info($message);";
                }
            },
            $content,
            -1,
            $replaced_count
        );
        
        $count = $replaced_count;
    } else {
        // Simple string replacement (less accurate but faster)
        $new_content = str_replace( 'error_log(', 'mpai_log_info(', $content, $count );
    }

    // Write modified content if there were changes
    if ( $count > 0 ) {
        if ( ! file_put_contents( $file_path, $new_content ) ) {
            return array(
                'success' => false,
                'message' => "Could not write to file: $file_path",
                'count' => $count,
            );
        }
    }

    return array(
        'success' => true,
        'message' => "Replaced $count error_log calls in $file_path",
        'count' => $count,
    );
}

/**
 * Replace error_log calls in all PHP files in a directory
 *
 * @param string $directory       Directory to process.
 * @param bool   $recursive       Whether to recursively process subdirectories.
 * @param bool   $create_backups  Whether to create backups of original files.
 * @param bool   $use_regex       Whether to use regex for replacement.
 * @param array  $exclude_dirs    Directories to exclude.
 * @param array  $exclude_files   Files to exclude.
 * @return array Result information: success status, counts and list of processed files.
 */
function mpai_replace_error_logs_in_directory( $directory, $recursive = true, $create_backups = true, $use_regex = true, $exclude_dirs = array(), $exclude_files = array() ) {
    if ( ! is_dir( $directory ) ) {
        return array(
            'success' => false,
            'message' => "Directory does not exist: $directory",
            'total_count' => 0,
            'files_processed' => 0,
            'files_with_changes' => 0,
            'details' => array(),
        );
    }

    $details = array();
    $total_count = 0;
    $files_with_changes = 0;

    // Get PHP files in the directory
    $files = glob( rtrim( $directory, '/' ) . '/*.php' );
    
    // Process each file
    foreach ( $files as $file ) {
        // Skip excluded files
        $filename = basename( $file );
        if ( in_array( $filename, $exclude_files ) ) {
            continue;
        }
        
        // Process the file
        $result = mpai_replace_error_logs_in_file( $file, $create_backups, $use_regex );
        $details[ $file ] = $result;
        
        if ( $result['success'] && $result['count'] > 0 ) {
            $total_count += $result['count'];
            $files_with_changes++;
        }
    }
    
    // Process subdirectories if recursive
    if ( $recursive ) {
        $subdirectories = glob( rtrim( $directory, '/' ) . '/*', GLOB_ONLYDIR );
        
        foreach ( $subdirectories as $subdir ) {
            // Skip excluded directories
            $dirname = basename( $subdir );
            if ( in_array( $dirname, $exclude_dirs ) ) {
                continue;
            }
            
            // Process the subdirectory
            $subdir_result = mpai_replace_error_logs_in_directory( $subdir, true, $create_backups, $use_regex, $exclude_dirs, $exclude_files );
            
            if ( $subdir_result['success'] ) {
                $total_count += $subdir_result['total_count'];
                $files_with_changes += $subdir_result['files_with_changes'];
                $details = array_merge( $details, $subdir_result['details'] );
            }
        }
    }
    
    return array(
        'success' => true,
        'message' => "Replaced $total_count error_log calls in $files_with_changes files",
        'total_count' => $total_count,
        'files_processed' => count( $details ),
        'files_with_changes' => $files_with_changes,
        'details' => $details,
    );
}

/**
 * Restore backups of files modified by the error_log replacement function
 *
 * @param string $directory    Directory to process.
 * @param bool   $recursive    Whether to recursively process subdirectories.
 * @return array Result information: success status and counts.
 */
function mpai_restore_error_log_backups( $directory, $recursive = true ) {
    if ( ! is_dir( $directory ) ) {
        return array(
            'success' => false,
            'message' => "Directory does not exist: $directory",
            'restored' => 0,
        );
    }

    $restored = 0;
    $details = array();

    // Find backup files in the directory
    $backup_files = glob( rtrim( $directory, '/' ) . '/*.php.bak' );
    
    // Process each backup file
    foreach ( $backup_files as $backup_file ) {
        $original_file = substr( $backup_file, 0, -4 ); // Remove .bak extension
        
        // Read backup content
        $content = file_get_contents( $backup_file );
        if ( false === $content ) {
            $details[ $backup_file ] = "Could not read backup file";
            continue;
        }
        
        // Restore original file
        if ( ! file_put_contents( $original_file, $content ) ) {
            $details[ $backup_file ] = "Could not restore original file";
            continue;
        }
        
        // Remove backup file
        if ( ! unlink( $backup_file ) ) {
            $details[ $backup_file ] = "Original file restored but could not remove backup file";
            continue;
        }
        
        $details[ $backup_file ] = "Successfully restored";
        $restored++;
    }
    
    // Process subdirectories if recursive
    if ( $recursive ) {
        $subdirectories = glob( rtrim( $directory, '/' ) . '/*', GLOB_ONLYDIR );
        
        foreach ( $subdirectories as $subdir ) {
            $subdir_result = mpai_restore_error_log_backups( $subdir, true );
            
            if ( $subdir_result['success'] ) {
                $restored += $subdir_result['restored'];
                $details = array_merge( $details, $subdir_result['details'] );
            }
        }
    }
    
    return array(
        'success' => true,
        'message' => "Restored $restored files from backups",
        'restored' => $restored,
        'details' => $details,
    );
}

/**
 * Register a WP-CLI command to replace error_log calls
 */
function mpai_register_replace_error_log_cli_command() {
    if ( defined( 'WP_CLI' ) && WP_CLI ) {
        WP_CLI::add_command( 'mpai replace-error-logs', function( $args, $assoc_args ) {
            $directory = isset( $args[0] ) ? $args[0] : MPAI_PLUGIN_DIR;
            $recursive = isset( $assoc_args['recursive'] ) ? (bool) $assoc_args['recursive'] : true;
            $create_backups = isset( $assoc_args['backup'] ) ? (bool) $assoc_args['backup'] : true;
            $use_regex = isset( $assoc_args['regex'] ) ? (bool) $assoc_args['regex'] : true;
            
            WP_CLI::log( "Replacing error_log calls in $directory..." );
            
            $result = mpai_replace_error_logs_in_directory( $directory, $recursive, $create_backups, $use_regex );
            
            if ( $result['success'] ) {
                WP_CLI::success( $result['message'] );
            } else {
                WP_CLI::error( $result['message'] );
            }
        }, array(
            'shortdesc' => 'Replace error_log calls with mpai_log_info.',
            'synopsis' => array(
                array(
                    'type'        => 'positional',
                    'name'        => 'directory',
                    'description' => 'Directory to process.',
                    'optional'    => true,
                ),
                array(
                    'type'        => 'assoc',
                    'name'        => 'recursive',
                    'description' => 'Whether to recursively process subdirectories.',
                    'optional'    => true,
                    'default'     => true,
                ),
                array(
                    'type'        => 'assoc',
                    'name'        => 'backup',
                    'description' => 'Whether to create backups of original files.',
                    'optional'    => true,
                    'default'     => true,
                ),
                array(
                    'type'        => 'assoc',
                    'name'        => 'regex',
                    'description' => 'Whether to use regex for replacement.',
                    'optional'    => true,
                    'default'     => true,
                ),
            ),
        ) );
        
        WP_CLI::add_command( 'mpai restore-error-log-backups', function( $args, $assoc_args ) {
            $directory = isset( $args[0] ) ? $args[0] : MPAI_PLUGIN_DIR;
            $recursive = isset( $assoc_args['recursive'] ) ? (bool) $assoc_args['recursive'] : true;
            
            WP_CLI::log( "Restoring files from backups in $directory..." );
            
            $result = mpai_restore_error_log_backups( $directory, $recursive );
            
            if ( $result['success'] ) {
                WP_CLI::success( $result['message'] );
            } else {
                WP_CLI::error( $result['message'] );
            }
        }, array(
            'shortdesc' => 'Restore files from backups created by replace-error-logs.',
            'synopsis' => array(
                array(
                    'type'        => 'positional',
                    'name'        => 'directory',
                    'description' => 'Directory to process.',
                    'optional'    => true,
                ),
                array(
                    'type'        => 'assoc',
                    'name'        => 'recursive',
                    'description' => 'Whether to recursively process subdirectories.',
                    'optional'    => true,
                    'default'     => true,
                ),
            ),
        ) );
    }
}

// Register WP-CLI commands if WP-CLI is available
add_action( 'init', 'mpai_register_replace_error_log_cli_command' );