<?php
/**
 * Settings Class
 *
 * Handles plugin settings
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

class MPAI_Settings {
    /**
     * Constructor
     */
    public function __construct() {
        // Settings are now registered in the main plugin class
    }

    /**
     * Sanitize float value
     *
     * @param string $value Value to sanitize
     * @return float Sanitized value
     */
    public function sanitize_float($value) {
        return floatval($value);
    }

    /**
     * Sanitize boolean value
     *
     * @param string $value Value to sanitize
     * @return bool Sanitized value
     */
    public function sanitize_bool($value) {
        return (bool) $value;
    }

    /**
     * Sanitize CLI commands
     *
     * @param array $commands Commands to sanitize
     * @return array Sanitized commands
     */
    public function sanitize_cli_commands($commands) {
        if (!is_array($commands)) {
            return array();
        }
        
        $sanitized_commands = array();
        
        foreach ($commands as $command) {
            $sanitized_command = sanitize_text_field($command);
            
            if (!empty($sanitized_command)) {
                $sanitized_commands[] = $sanitized_command;
            }
        }
        
        return $sanitized_commands;
    }

    /**
     * Get available models
     *
     * @return array Available models
     */
    public function get_available_models() {
        return array(
            'gpt-4o' => 'GPT-4o',
            'gpt-4-turbo' => 'GPT-4 Turbo',
            'gpt-4' => 'GPT-4',
            'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
        );
    }
}