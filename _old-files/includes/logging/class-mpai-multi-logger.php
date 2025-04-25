<?php
/**
 * MemberPress AI Assistant - Multi Logger
 *
 * Logger implementation that dispatches to multiple loggers
 *
 * @package MemberPress AI Assistant
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Logger implementation that dispatches to multiple loggers
 */
class MPAI_Multi_Logger implements MPAI_Logger {

    /**
     * Array of loggers
     *
     * @var array
     */
    protected $loggers = array();

    /**
     * Constructor
     *
     * @param array $loggers Array of MPAI_Logger instances.
     */
    public function __construct( array $loggers = array() ) {
        foreach ( $loggers as $logger ) {
            $this->add_logger( $logger );
        }
    }

    /**
     * Add a logger to the collection
     *
     * @param MPAI_Logger $logger Logger instance.
     * @return MPAI_Multi_Logger This instance for method chaining.
     */
    public function add_logger( MPAI_Logger $logger ) {
        $this->loggers[] = $logger;
        return $this;
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
    public function log( $level, $message, array $context = array() ) {
        foreach ( $this->loggers as $logger ) {
            $logger->log( $level, $message, $context );
        }
    }
}