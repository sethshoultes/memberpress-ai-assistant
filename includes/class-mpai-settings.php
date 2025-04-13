<?php
/**
 * Settings Class
 *
 * Handles plugin settings utilities and provides methods for the standard WordPress
 * Settings API implementation in settings-page.php
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
        // Settings are registered in settings-page.php using standard WordPress Settings API
        
        // Add option_page_capability filter if not already added
        if (!has_filter('option_page_capability_mpai_options', array($this, 'option_page_capability'))) {
            add_filter('option_page_capability_mpai_options', array($this, 'option_page_capability'));
        }
        
        // Add allowed_options filter if not already added
        if (!has_filter('allowed_options', array($this, 'whitelist_options'))) {
            add_filter('allowed_options', array($this, 'whitelist_options'), 999);
        }
    }
    
    /**
     * Filter for option_page_capability
     * 
     * @param string $capability The capability
     * @return string The filtered capability
     */
    public function option_page_capability($capability) {
        return 'manage_options';
    }
    
    /**
     * Whitelist our options for WordPress
     * 
     * @param array $allowed_options The allowed options
     * @return array The filtered allowed options
     */
    public function whitelist_options($allowed_options) {
        // Get the full list of our option names
        $mpai_options = array(
            'mpai_api_key',
            'mpai_model',
            'mpai_anthropic_api_key',
            'mpai_anthropic_model',
            'mpai_primary_api',
            'mpai_enable_chat',
            'mpai_chat_position',
            'mpai_show_on_all_pages',
            'mpai_welcome_message',
            'mpai_enable_mcp',
            'mpai_enable_cli_commands',
            'mpai_enable_console_logging',
            'mpai_console_log_level',
            'mpai_log_api_calls',
            'mpai_log_tool_usage',
            'mpai_log_agent_activity',
            'mpai_log_timing'
        );
        
        // Add our options to the allowed list
        $allowed_options['mpai_options'] = $mpai_options;
        
        // Also add them to the core option page for maximum compatibility
        if (isset($allowed_options['options'])) {
            $allowed_options['options'] = array_merge($allowed_options['options'], $mpai_options);
        }
        
        return $allowed_options;
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