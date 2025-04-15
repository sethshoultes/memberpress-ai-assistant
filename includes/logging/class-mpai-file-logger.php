<?php
/**
 * MemberPress AI Assistant - File Logger
 *
 * Logger implementation that writes to a file
 *
 * @package MemberPress AI Assistant
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Logger implementation that writes to a log file
 */
class MPAI_File_Logger extends MPAI_Abstract_Logger {

    /**
     * Log file path
     *
     * @var string
     */
    protected $log_file;

    /**
     * Max file size in bytes before rotation
     *
     * @var int
     */
    protected $max_file_size;

    /**
     * Number of log files to keep during rotation
     *
     * @var int
     */
    protected $max_files;

    /**
     * Constructor
     *
     * @param string $log_file      Path to log file.
     * @param string $minimum_level Minimum log level to record.
     * @param int    $max_file_size Maximum file size in bytes before rotation.
     * @param int    $max_files     Number of log files to keep during rotation.
     */
    public function __construct( $log_file, $minimum_level = 'debug', $max_file_size = 5242880, $max_files = 5 ) {
        parent::__construct( $minimum_level );
        
        $this->log_file = $log_file;
        $this->max_file_size = max( 1024, intval( $max_file_size ) );
        $this->max_files = max( 1, intval( $max_files ) );
        
        // Ensure log directory exists
        $log_dir = dirname( $log_file );
        if ( ! file_exists( $log_dir ) ) {
            wp_mkdir_p( $log_dir );
        }
    }

    /**
     * Check if log file needs rotation
     *
     * @return bool Whether the file needs rotation.
     */
    protected function needs_rotation() {
        if ( ! file_exists( $this->log_file ) ) {
            return false;
        }
        
        return filesize( $this->log_file ) >= $this->max_file_size;
    }

    /**
     * Rotate log files
     *
     * @return bool Whether rotation was successful.
     */
    protected function rotate_logs() {
        // Remove oldest log file if it exists
        $oldest_file = $this->log_file . '.' . $this->max_files;
        if ( file_exists( $oldest_file ) ) {
            @unlink( $oldest_file );
        }
        
        // Shift each log file up one number
        for ( $i = $this->max_files - 1; $i > 0; $i-- ) {
            $old_file = $this->log_file . '.' . $i;
            $new_file = $this->log_file . '.' . ( $i + 1 );
            
            if ( file_exists( $old_file ) ) {
                @rename( $old_file, $new_file );
            }
        }
        
        // Rename current log file
        if ( file_exists( $this->log_file ) ) {
            @rename( $this->log_file, $this->log_file . '.1' );
        }
        
        return true;
    }

    /**
     * Log a message at a specific level
     *
     * @param string $level   Log level.
     * @param string $message The log message.
     * @param array  $context Additional context data.
     * @return void
     */
    public function log( $level, $message, array $context = array() ) {
        if ( ! $this->should_log( $level ) ) {
            return;
        }
        
        // Check for file rotation
        if ( $this->needs_rotation() ) {
            $this->rotate_logs();
        }
        
        $formatted_message = $this->format_message( $level, $message, $context );
        $formatted_message .= PHP_EOL;
        
        // Try to write to log file
        $result = @file_put_contents( $this->log_file, $formatted_message, FILE_APPEND | LOCK_EX );
        
        // If we couldn't write to the file, fallback to error_log
        if ( false === $result ) {
            error_log( 'MPAI: Failed to write to log file: ' . $this->log_file );
            error_log( 'MPAI: ' . $formatted_message );
        }
    }
}