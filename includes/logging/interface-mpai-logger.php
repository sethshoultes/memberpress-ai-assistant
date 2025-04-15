<?php
/**
 * MemberPress AI Assistant - Logger Interface
 *
 * Defines the contract for all logger implementations
 *
 * @package MemberPress AI Assistant
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Interface for logger implementations.
 */
interface MPAI_Logger {

    /**
     * Log an emergency message
     *
     * @param string $message The log message.
     * @param array  $context Additional context data.
     * @return void
     */
    public function emergency( $message, array $context = array() );

    /**
     * Log an alert message
     *
     * @param string $message The log message.
     * @param array  $context Additional context data.
     * @return void
     */
    public function alert( $message, array $context = array() );

    /**
     * Log a critical message
     *
     * @param string $message The log message.
     * @param array  $context Additional context data.
     * @return void
     */
    public function critical( $message, array $context = array() );

    /**
     * Log an error message
     *
     * @param string $message The log message.
     * @param array  $context Additional context data.
     * @return void
     */
    public function error( $message, array $context = array() );

    /**
     * Log a warning message
     *
     * @param string $message The log message.
     * @param array  $context Additional context data.
     * @return void
     */
    public function warning( $message, array $context = array() );

    /**
     * Log a notice message
     *
     * @param string $message The log message.
     * @param array  $context Additional context data.
     * @return void
     */
    public function notice( $message, array $context = array() );

    /**
     * Log an informational message
     *
     * @param string $message The log message.
     * @param array  $context Additional context data.
     * @return void
     */
    public function info( $message, array $context = array() );

    /**
     * Log a debug message
     *
     * @param string $message The log message.
     * @param array  $context Additional context data.
     * @return void
     */
    public function debug( $message, array $context = array() );

    /**
     * Log a message at a specific level
     *
     * @param string $level   Log level.
     * @param string $message The log message.
     * @param array  $context Additional context data.
     * @return void
     */
    public function log( $level, $message, array $context = array() );
}