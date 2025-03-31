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
     * Get available OpenAI models
     *
     * @return array Available OpenAI models
     */
    public function get_available_models() {
        return array(
            'gpt-4o' => 'GPT-4o',
            'gpt-4-turbo' => 'GPT-4 Turbo',
            'gpt-4' => 'GPT-4',
            'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
        );
    }
    
    /**
     * Get available Anthropic models
     *
     * @return array Available Anthropic models
     */
    public function get_available_anthropic_models() {
        return array(
            'claude-3-opus-20240229' => 'Claude 3 Opus',
            'claude-3-sonnet-20240229' => 'Claude 3 Sonnet',
            'claude-3-haiku-20240307' => 'Claude 3 Haiku',
            'claude-2.1' => 'Claude 2.1',
            'claude-2.0' => 'Claude 2.0',
        );
    }
    
    /**
     * Get available API providers
     *
     * @return array Available API providers
     */
    public function get_available_api_providers() {
        return array(
            'openai' => 'OpenAI',
            'anthropic' => 'Anthropic (Claude)',
        );
    }
}