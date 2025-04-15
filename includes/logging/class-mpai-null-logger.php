<?php
/**
 * MemberPress AI Assistant - Null Logger
 *
 * Logger implementation that discards all messages
 *
 * @package MemberPress AI Assistant
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Logger implementation that discards all messages (null logger pattern)
 */
class MPAI_Null_Logger implements MPAI_Logger {

    /**
     * Log an emergency message
     *
     * @param string $message The log message.
     * @param array  $context Additional context data.
     * @return void
     */
    public function emergency( $message, array $context = array() ) {
        // Do nothing
    }

    /**
     * Log an alert message
     *
     * @param string $message The log message.
     * @param array  $context Additional context data.
     * @return void
     */
    public function alert( $message, array $context = array() ) {
        // Do nothing
    }

    /**
     * Log a critical message
     *
     * @param string $message The log message.
     * @param array  $context Additional context data.
     * @return void
     */
    public function critical( $message, array $context = array() ) {
        // Do nothing
    }

    /**
     * Log an error message
     *
     * @param string $message The log message.
     * @param array  $context Additional context data.
     * @return void
     */
    public function error( $message, array $context = array() ) {
        // Do nothing
    }

    /**
     * Log a warning message
     *
     * @param string $message The log message.
     * @param array  $context Additional context data.
     * @return void
     */
    public function warning( $message, array $context = array() ) {
        // Do nothing
    }

    /**
     * Log a notice message
     *
     * @param string $message The log message.
     * @param array  $context Additional context data.
     * @return void
     */
    public function notice( $message, array $context = array() ) {
        // Do nothing
    }

    /**
     * Log an informational message
     *
     * @param string $message The log message.
     * @param array  $context Additional context data.
     * @return void
     */
    public function info( $message, array $context = array() ) {
        // Do nothing
    }

    /**
     * Log a debug message
     *
     * @param string $message The log message.
     * @param array  $context Additional context data.
     * @return void
     */
    public function debug( $message, array $context = array() ) {
        // Do nothing
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
        // Do nothing
    }
}