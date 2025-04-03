<?php
/**
 * Command Sanitizer
 *
 * Sanitizes commands to ensure they are safe and properly formatted
 *
 * @package MemberPress AI Assistant
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Command Sanitizer Class
 */
class MPAI_Command_Sanitizer {
    /**
     * Logger instance
     *
     * @var object
     */
    private $logger;

    /**
     * Constructor
     */
    public function __construct() {
        // Initialize logger
        $this->logger = $this->get_default_logger();
    }

    /**
     * Get default logger
     *
     * @return object Default logger
     */
    private function get_default_logger() {
        return (object) [
            'info'    => function( $message ) { error_log( 'MPAI SANITIZER INFO: ' . $message ); },
            'warning' => function( $message ) { error_log( 'MPAI SANITIZER WARNING: ' . $message ); },
            'error'   => function( $message ) { error_log( 'MPAI SANITIZER ERROR: ' . $message ); },
        ];
    }

    /**
     * Sanitize a command
     *
     * @param string $command Command to sanitize
     * @return string Sanitized command
     */
    public function sanitize($command) {
        // Trim whitespace
        $command = trim($command);
        
        // Remove potentially dangerous shell characters
        $command = $this->remove_dangerous_characters($command);
        
        // Ensure WP CLI commands are properly formatted
        $command = $this->format_wp_command($command);
        
        return $command;
    }

    /**
     * Remove dangerous characters from a command
     *
     * @param string $command Command to clean
     * @return string Cleaned command
     */
    private function remove_dangerous_characters($command) {
        // Remove shell metacharacters
        $command = preg_replace('/[;&|><`$]/', '', $command);
        
        // Remove any backticks
        $command = str_replace('`', '', $command);
        
        // Remove any double quotes around the entire command
        $command = preg_replace('/^"(.*)"$/', '$1', $command);
        
        // Remove any single quotes around the entire command
        $command = preg_replace("/^'(.*)'$/", '$1', $command);
        
        return $command;
    }

    /**
     * Format WP command to ensure it starts with wp or php
     *
     * @param string $command Command to format
     * @return string Formatted command
     */
    private function format_wp_command($command) {
        // If command doesn't start with wp or php, prepend wp
        if (strpos($command, 'wp ') !== 0 && strpos($command, 'php ') !== 0) {
            // Check if it might be a WP CLI command without the wp prefix
            if (preg_match('/^(plugin|theme|post|user|option|core|site|db)\s+/', $command)) {
                $command = 'wp ' . $command;
                $this->logger->info('Added wp prefix to command: ' . $command);
            }
        }
        
        return $command;
    }

    /**
     * Clean parameters for command execution
     *
     * @param array $parameters Parameters to clean
     * @return array Cleaned parameters
     */
    public function clean_parameters($parameters) {
        $clean_params = [];
        
        foreach ($parameters as $key => $value) {
            // Skip null or empty values
            if ($value === null || $value === '') {
                continue;
            }
            
            // Clean string values
            if (is_string($value)) {
                $value = $this->remove_dangerous_characters($value);
            }
            
            // Clean arrays recursively
            if (is_array($value)) {
                $value = $this->clean_parameters($value);
            }
            
            $clean_params[$key] = $value;
        }
        
        return $clean_params;
    }
}