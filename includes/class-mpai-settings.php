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
     *
     * Sets up all needed filters for WordPress Settings API integration
     */
    public function __construct() {
        // Add log to track constructor execution
        error_log('MPAI_Settings: Constructor running, setting up filters');
        
        // Add option_page_capability filter to ensure correct capability checks
        add_filter('option_page_capability_mpai_options', array($this, 'option_page_capability'));
        
        // CRITICAL: This whitelist filter is required for WordPress 5.5+ to save options
        add_filter('allowed_options', array($this, 'whitelist_options'), 999);
        
        // Also add the legacy whitelist_options filter for older WordPress versions
        add_filter('whitelist_options', array($this, 'legacy_whitelist_options'), 999);
        
        // Track when the options.php page is loaded, so we can debug settings saving
        if (strpos($_SERVER['PHP_SELF'], 'options.php') !== false) {
            error_log('MPAI_Settings: options.php is being processed');
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                error_log('MPAI_Settings: POST to options.php detected, option_page: ' . 
                    (isset($_POST['option_page']) ? $_POST['option_page'] : 'not set'));
            }
        }
    }
    
    /**
     * Legacy whitelist options for older WordPress versions
     *
     * @param array $whitelist The whitelist
     * @return array The filtered whitelist
     */
    public function legacy_whitelist_options($whitelist) {
        // Get all our options
        $mpai_options = array(
            'mpai_api_key',
            'mpai_model',
            'mpai_temperature',
            'mpai_anthropic_api_key',
            'mpai_anthropic_model',
            'mpai_anthropic_temperature',
            'mpai_primary_api',
            'mpai_enable_chat',
            'mpai_chat_position',
            'mpai_show_on_all_pages',
            'mpai_welcome_message',
            'mpai_enable_mcp',
            'mpai_enable_cli_commands',
            'mpai_enable_wp_cli_tool',
            'mpai_enable_memberpress_info_tool',
            'mpai_enable_plugin_logs_tool',
            'mpai_enable_console_logging',
            'mpai_console_log_level',
            'mpai_log_api_calls',
            'mpai_log_tool_usage',
            'mpai_log_agent_activity',
            'mpai_log_timing',
            'mpai_active_tab'
        );
        
        $whitelist['mpai_options'] = $mpai_options;
        
        error_log('MPAI_Settings: Legacy whitelist_options filter applied');
        return $whitelist;
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
     * This is CRITICAL for allowing WordPress to save our options
     * 
     * @param array $allowed_options The allowed options
     * @return array The filtered allowed options
     */
    public function whitelist_options($allowed_options) {
        // Debug output to understand what's happening
        error_log('MPAI: whitelist_options filter running with ' . count($allowed_options) . ' existing allowed option groups');
        
        // Get the full list of our option names
        // IMPORTANT: Every field must be listed here exactly as it appears in name attributes
        $mpai_options = array(
            'mpai_api_key',
            'mpai_model',
            'mpai_temperature',
            'mpai_anthropic_api_key',
            'mpai_anthropic_model',
            'mpai_anthropic_temperature',
            'mpai_primary_api',
            'mpai_enable_chat',
            'mpai_chat_position',
            'mpai_show_on_all_pages',
            'mpai_welcome_message',
            'mpai_enable_mcp',
            'mpai_enable_cli_commands',
            'mpai_enable_wp_cli_tool',
            'mpai_enable_memberpress_info_tool',
            'mpai_enable_plugin_logs_tool',
            'mpai_enable_console_logging',
            'mpai_console_log_level',
            'mpai_log_api_calls',
            'mpai_log_tool_usage',
            'mpai_log_agent_activity',
            'mpai_log_timing',
            'mpai_active_tab' // Extra field to track the active tab
        );
        
        // Debug the whitelist options for troubleshooting
        error_log('MPAI: Whitelisting ' . count($mpai_options) . ' options for mpai_options page');
        
        // Add our options to the allowed list for our option page
        $allowed_options['mpai_options'] = $mpai_options;
        
        // Also add them individually to the WordPress built-in options
        // This is the most reliable approach for WordPress 5.5+
        if (isset($allowed_options['options'])) {
            error_log('MPAI: Adding options to WordPress core options page too');
            $allowed_options['options'] = array_merge($allowed_options['options'], $mpai_options);
        }
        
        // Also register them with the legacy whitelist_options filter
        // This works for some WordPress versions
        add_filter('whitelist_options', function($whitelist) use ($mpai_options) {
            $whitelist['mpai_options'] = $mpai_options;
            return $whitelist;
        });
        
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