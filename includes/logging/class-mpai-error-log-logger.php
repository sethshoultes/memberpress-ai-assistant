<?php
/**
 * MemberPress AI Assistant - Error Log Logger
 *
 * Logger implementation that uses PHP's error_log()
 *
 * @package MemberPress AI Assistant
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Logger implementation that sends messages to PHP's error_log
 */
class MPAI_Error_Log_Logger extends MPAI_Abstract_Logger {

    /**
     * Prefix for log messages
     *
     * @var string
     */
    protected $prefix = 'MPAI: ';

    /**
     * Constructor
     *
     * @param string $minimum_level Minimum log level to record.
     * @param string $prefix        Optional prefix for log messages.
     */
    public function __construct( $minimum_level = 'debug', $prefix = 'MPAI: ' ) {
        parent::__construct( $minimum_level );
        $this->prefix = $prefix;
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

        $formatted_message = $this->format_message( $level, $message, $context );
        error_log( $this->prefix . $formatted_message );
    }
}