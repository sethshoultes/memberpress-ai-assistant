<?php
/**
 * MemberPress AI Assistant - Abstract Logger
 *
 * Base implementation of logger functionality
 *
 * @package MemberPress AI Assistant
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Abstract logger class that provides common functionality
 */
abstract class MPAI_Abstract_Logger implements MPAI_Logger {

    /**
     * Available log levels
     *
     * @var array
     */
    protected $levels = array(
        'emergency' => 0,
        'alert'     => 1,
        'critical'  => 2,
        'error'     => 3,
        'warning'   => 4,
        'notice'    => 5,
        'info'      => 6,
        'debug'     => 7,
    );

    /**
     * Current minimum level to log
     *
     * @var string
     */
    protected $minimum_level = 'debug';

    /**
     * Constructor
     *
     * @param string $minimum_level Minimum log level to record.
     */
    public function __construct( $minimum_level = 'debug' ) {
        if ( array_key_exists( $minimum_level, $this->levels ) ) {
            $this->minimum_level = $minimum_level;
        }
    }

    /**
     * Check if a given level should be logged
     *
     * @param string $level Log level to check.
     * @return bool Whether the level should be logged.
     */
    protected function should_log( $level ) {
        return $this->levels[ $level ] <= $this->levels[ $this->minimum_level ];
    }

    /**
     * Format context data for logging
     *
     * @param array $context Context data to format.
     * @return string Formatted context string.
     */
    protected function format_context( array $context ) {
        if ( empty( $context ) ) {
            return '';
        }

        // Special handling for exceptions
        if ( isset( $context['exception'] ) && $context['exception'] instanceof \Exception ) {
            $exception = $context['exception'];
            $context['exception'] = array(
                'class'   => get_class( $exception ),
                'message' => $exception->getMessage(),
                'code'    => $exception->getCode(),
                'file'    => $exception->getFile(),
                'line'    => $exception->getLine(),
                'trace'   => $exception->getTraceAsString(),
            );
        }

        return wp_json_encode( $context );
    }

    /**
     * Format the log message
     *
     * @param string $level   Log level.
     * @param string $message Log message.
     * @param array  $context Additional context data.
     * @return string Formatted log message.
     */
    protected function format_message( $level, $message, array $context = array() ) {
        $timestamp = current_time( 'mysql' );
        $formatted_context = $this->format_context( $context );
        
        $log_message = "[$timestamp][$level] $message";
        
        if ( ! empty( $formatted_context ) ) {
            $log_message .= " Context: $formatted_context";
        }
        
        return $log_message;
    }

    /**
     * Log an emergency message
     *
     * @param string $message The log message.
     * @param array  $context Additional context data.
     * @return void
     */
    public function emergency( $message, array $context = array() ) {
        $this->log( 'emergency', $message, $context );
    }

    /**
     * Log an alert message
     *
     * @param string $message The log message.
     * @param array  $context Additional context data.
     * @return void
     */
    public function alert( $message, array $context = array() ) {
        $this->log( 'alert', $message, $context );
    }

    /**
     * Log a critical message
     *
     * @param string $message The log message.
     * @param array  $context Additional context data.
     * @return void
     */
    public function critical( $message, array $context = array() ) {
        $this->log( 'critical', $message, $context );
    }

    /**
     * Log an error message
     *
     * @param string $message The log message.
     * @param array  $context Additional context data.
     * @return void
     */
    public function error( $message, array $context = array() ) {
        $this->log( 'error', $message, $context );
    }

    /**
     * Log a warning message
     *
     * @param string $message The log message.
     * @param array  $context Additional context data.
     * @return void
     */
    public function warning( $message, array $context = array() ) {
        $this->log( 'warning', $message, $context );
    }

    /**
     * Log a notice message
     *
     * @param string $message The log message.
     * @param array  $context Additional context data.
     * @return void
     */
    public function notice( $message, array $context = array() ) {
        $this->log( 'notice', $message, $context );
    }

    /**
     * Log an informational message
     *
     * @param string $message The log message.
     * @param array  $context Additional context data.
     * @return void
     */
    public function info( $message, array $context = array() ) {
        $this->log( 'info', $message, $context );
    }

    /**
     * Log a debug message
     *
     * @param string $message The log message.
     * @param array  $context Additional context data.
     * @return void
     */
    public function debug( $message, array $context = array() ) {
        $this->log( 'debug', $message, $context );
    }

    /**
     * Log a message at a specific level
     *
     * @param string $level   Log level.
     * @param string $message The log message.
     * @param array  $context Additional context data.
     * @return void
     */
    public abstract function log( $level, $message, array $context = array() );
}