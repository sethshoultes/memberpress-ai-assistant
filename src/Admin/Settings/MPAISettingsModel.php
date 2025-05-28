<?php
/**
 * MemberPress AI Assistant Settings Model
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Admin\Settings;

/**
 * Class for handling MemberPress AI Assistant settings
 * 
 * This class combines storage and validation functionality for settings.
 * It handles reading/writing settings to a single serialized option,
 * validates all settings values, and provides methods for getting and setting values.
 */
class MPAISettingsModel {
    /**
     * Option name for storing settings
     *
     * @var string
     */
    private $option_name = 'mpai_settings';

    /**
     * Default settings values
     *
     * @var array
     */
    private $defaults = [
        // General settings
        'chat_enabled' => true,
        'log_level' => 'info', // Options: none, error, warning, info, debug, trace, minimal
        
        // Chat settings
        'chat_location' => 'admin_only',
        'chat_position' => 'bottom_right',
        
        // Access settings
        'user_roles' => ['administrator'],
        
        // API settings
        'openai_api_key' => '',
        'openai_model' => 'gpt-4o',
        'openai_temperature' => 0.7,
        'openai_max_tokens' => 1000,
        'anthropic_api_key' => '',
        'anthropic_model' => 'claude-3-opus-20240229',
        'anthropic_temperature' => 0.7,
        'anthropic_max_tokens' => 1000,
        'primary_api' => 'openai',
    ];

    /**
     * Current settings
     *
     * @var array
     */
    private $settings = [];

    /**
     * Logger instance
     *
     * @var mixed
     */
    private $logger;

    /**
     * Constructor
     *
     * @param mixed $logger Logger instance
     */
    public function __construct($logger = null) {
        $this->logger = $logger;
        $this->load_settings();
    }

    /**
     * Get a setting value
     *
     * @param string $key Setting key
     * @param mixed $default Default value if setting not found
     * @return mixed Setting value
     */
    public function get($key, $default = null) {
        if (isset($this->settings[$key])) {
            return $this->settings[$key];
        }
        
        if (isset($this->defaults[$key])) {
            return $this->defaults[$key];
        }
        
        return $default;
    }

    /**
     * Set a setting value
     *
     * @param string $key Setting key
     * @param mixed $value Setting value
     * @param bool $save Whether to save settings to database
     * @return bool Whether the setting was set successfully
     */
    public function set($key, $value, $save = true) {
        $this->settings[$key] = $value;
        
        if ($save) {
            return $this->save();
        }
        
        return true;
    }

    /**
     * Save settings to WordPress options
     *
     * @return bool Whether the settings were saved successfully
     */
    public function save() {
        $result = update_option($this->option_name, $this->settings);
        
        if ($this->logger) {
            if ($result) {
                $this->logger->info('Settings saved to WordPress options');
            } else {
                $this->logger->error('Failed to save settings to WordPress options');
            }
        }
        
        return $result;
    }

    /**
     * Get all settings
     *
     * @return array All settings
     */
    public function get_all() {
        return $this->settings;
    }

    /**
     * Update multiple settings at once
     *
     * @param array $settings Settings to update
     * @param bool $save Whether to save settings to database
     * @return bool Whether the settings were updated successfully
     */
    public function update($settings, $save = true) {
        $validated = $this->validate($settings);
        
        foreach ($validated as $key => $value) {
            $this->settings[$key] = $value;
        }
        
        if ($save) {
            return $this->save();
        }
        
        return true;
    }

    /**
     * Reset settings to defaults
     *
     * @param bool $save Whether to save settings to database
     * @return bool Whether the settings were reset successfully
     */
    public function reset($save = true) {
        $this->settings = $this->defaults;
        
        if ($save) {
            return $this->save();
        }
        
        return true;
    }

    /**
     * Validate all settings
     *
     * @param array $settings Settings to validate
     * @return array Validated settings
     */
    public function validate($settings) {
        $validated = [];
        
        // Validate each setting
        foreach ($settings as $key => $value) {
            switch ($key) {
                case 'chat_enabled':
                    $validated[$key] = $this->validate_boolean($value);
                    break;
                    
                case 'log_level':
                    $validated[$key] = $this->validate_log_level($value);
                    break;
                    
                case 'chat_location':
                    $validated[$key] = $this->validate_chat_location($value);
                    break;
                    
                case 'chat_position':
                    $validated[$key] = $this->validate_chat_position($value);
                    break;
                    
                case 'user_roles':
                    $validated[$key] = $this->validate_user_roles($value);
                    break;
                    
                case 'openai_api_key':
                case 'anthropic_api_key':
                    $validated[$key] = $this->validate_api_key($value);
                    break;
                    
                case 'openai_model':
                    $validated[$key] = $this->validate_openai_model($value);
                    break;
                    
                case 'anthropic_model':
                    $validated[$key] = $this->validate_anthropic_model($value);
                    break;
                    
                case 'openai_temperature':
                case 'anthropic_temperature':
                    $validated[$key] = $this->validate_temperature($value);
                    break;
                    
                case 'openai_max_tokens':
                case 'anthropic_max_tokens':
                    $validated[$key] = $this->validate_max_tokens($value);
                    break;
                    
                case 'primary_api':
                    $validated[$key] = $this->validate_primary_api($value);
                    break;
                    
                default:
                    // For unknown settings, just pass through
                    $validated[$key] = $value;
                    break;
            }
        }
        
        if ($this->logger) {
            $this->logger->info('Settings validated', [
                'original' => $settings,
                'validated' => $validated
            ]);
        }
        
        return $validated;
    }

    /**
     * Load settings from WordPress options
     *
     * @return void
     */
    private function load_settings() {
        // Get settings from options
        $saved_settings = get_option($this->option_name, []);
        
        // Merge with defaults
        $this->settings = wp_parse_args($saved_settings, $this->defaults);
        
        if ($this->logger) {
            $this->logger->info('Settings loaded from WordPress options');
        }
    }

    /**
     * Validate a boolean setting
     *
     * @param mixed $value Value to validate
     * @return bool Validated boolean value
     */
    private function validate_boolean($value) {
        if (is_string($value)) {
            $value = strtolower($value);
            return in_array($value, ['true', 'yes', '1', 'on'], true);
        }
        
        return (bool) $value;
    }

    /**
     * Validate a chat location setting
     *
     * @param mixed $value Value to validate
     * @return string Validated chat location
     */
    private function validate_chat_location($value) {
        $valid_locations = ['admin_only', 'frontend', 'both'];
        
        if (in_array($value, $valid_locations, true)) {
            return $value;
        }
        
        if ($this->logger) {
            $this->logger->warning('Invalid chat location', [
                'value' => $value,
                'valid_locations' => $valid_locations
            ]);
        }
        
        return 'admin_only';
    }

    /**
     * Validate a chat position setting
     *
     * @param mixed $value Value to validate
     * @return string Validated chat position
     */
    private function validate_chat_position($value) {
        $valid_positions = ['bottom_right', 'bottom_left', 'top_right', 'top_left'];
        
        if (in_array($value, $valid_positions, true)) {
            return $value;
        }
        
        if ($this->logger) {
            $this->logger->warning('Invalid chat position', [
                'value' => $value,
                'valid_positions' => $valid_positions
            ]);
        }
        
        return 'bottom_right';
    }

    /**
     * Validate user roles setting
     *
     * @param mixed $value Value to validate
     * @return array Validated user roles
     */
    private function validate_user_roles($value) {
        if (!is_array($value)) {
            if ($this->logger) {
                $this->logger->warning('User roles is not an array', [
                    'value' => $value
                ]);
            }
            return ['administrator'];
        }
        
        $wp_roles = wp_roles();
        $valid_roles = array_keys($wp_roles->get_names());
        
        $validated = array_filter($value, function($role) use ($valid_roles) {
            return in_array($role, $valid_roles, true);
        });
        
        if (empty($validated)) {
            if ($this->logger) {
                $this->logger->warning('No valid roles selected, defaulting to administrator');
            }
            return ['administrator'];
        }
        
        return array_values($validated);
    }

    /**
     * Validate an API key
     *
     * @param mixed $value Value to validate
     * @return string Validated API key
     */
    private function validate_api_key($value) {
        // Just ensure it's a string and trim it, handling null values
        return trim((string) ($value ?? ''));
    }

    /**
     * Validate OpenAI model
     *
     * @param mixed $value Value to validate
     * @return string Validated OpenAI model
     */
    private function validate_openai_model($value) {
        $valid_models = ['gpt-4o', 'gpt-4-turbo', 'gpt-4', 'gpt-3.5-turbo'];
        
        if (in_array($value, $valid_models, true)) {
            return $value;
        }
        
        if ($this->logger) {
            $this->logger->warning('Invalid OpenAI model', [
                'value' => $value,
                'valid_models' => $valid_models
            ]);
        }
        
        return 'gpt-4o';
    }

    /**
     * Validate Anthropic model
     *
     * @param mixed $value Value to validate
     * @return string Validated Anthropic model
     */
    private function validate_anthropic_model($value) {
        $valid_models = ['claude-3-opus-20240229', 'claude-3-sonnet-20240229', 'claude-3-haiku-20240307'];
        
        if (in_array($value, $valid_models, true)) {
            return $value;
        }
        
        if ($this->logger) {
            $this->logger->warning('Invalid Anthropic model', [
                'value' => $value,
                'valid_models' => $valid_models
            ]);
        }
        
        return 'claude-3-opus-20240229';
    }

    /**
     * Validate temperature setting
     *
     * @param mixed $value Value to validate
     * @return float Validated temperature
     */
    private function validate_temperature($value) {
        $value = (float) $value;
        
        // Ensure temperature is between 0.0 and 1.0
        $value = max(0.0, min(1.0, $value));
        
        return $value;
    }

    /**
     * Validate max tokens setting
     *
     * @param mixed $value Value to validate
     * @return int Validated max tokens
     */
    private function validate_max_tokens($value) {
        $value = (int) $value;
        
        // Ensure max tokens is positive
        $value = max(1, $value);
        
        return $value;
    }

    /**
     * Validate primary API setting
     *
     * @param mixed $value Value to validate
     * @return string Validated primary API
     */
    private function validate_primary_api($value) {
        $valid_providers = ['openai', 'anthropic'];
        
        if (in_array($value, $valid_providers, true)) {
            return $value;
        }
        
        if ($this->logger) {
            $this->logger->warning('Invalid primary API provider', [
                'value' => $value,
                'valid_providers' => $valid_providers
            ]);
        }
        
        return 'openai';
    }
    
    /**
     * Validate log level setting
     *
     * @param mixed $value Value to validate
     * @return string Validated log level
     */
    private function validate_log_level($value) {
        $valid_levels = ['none', 'error', 'warning', 'info', 'debug', 'trace', 'minimal'];
        
        if (in_array($value, $valid_levels, true)) {
            return $value;
        }
        
        if ($this->logger) {
            $this->logger->warning('Invalid log level', [
                'value' => $value,
                'valid_levels' => $valid_levels
            ]);
        }
        
        return 'info';
    }

    /**
     * Check if chat is enabled
     *
     * @return bool Whether chat is enabled
     */
    public function is_chat_enabled() {
        return (bool) $this->get('chat_enabled', true);
    }

    /**
     * Get the chat location
     *
     * @return string Chat location
     */
    public function get_chat_location() {
        return $this->get('chat_location', 'admin_only');
    }

    /**
     * Get the chat position
     *
     * @return string Chat position
     */
    public function get_chat_position() {
        return $this->get('chat_position', 'bottom_right');
    }

    /**
     * Get the user roles that can access the chat
     *
     * @return array User roles
     */
    public function get_user_roles() {
        return $this->get('user_roles', ['administrator']);
    }

    /**
     * Check if a user role can access the chat
     *
     * @param string $role User role to check
     * @return bool Whether the role can access the chat
     */
    public function can_role_access_chat($role) {
        $roles = $this->get_user_roles();
        return in_array($role, $roles);
    }

    /**
     * Get the OpenAI API key
     *
     * @return string OpenAI API key
     */
    public function get_openai_api_key() {
        return $this->get('openai_api_key', '');
    }

    /**
     * Get the OpenAI model
     *
     * @return string OpenAI model
     */
    public function get_openai_model() {
        return $this->get('openai_model', 'gpt-4o');
    }

    /**
     * Get the OpenAI temperature
     *
     * @return float OpenAI temperature
     */
    public function get_openai_temperature() {
        return (float) $this->get('openai_temperature', 0.7);
    }

    /**
     * Get the OpenAI max tokens
     *
     * @return int OpenAI max tokens
     */
    public function get_openai_max_tokens() {
        return (int) $this->get('openai_max_tokens', 1000);
    }

    /**
     * Get the Anthropic API key
     *
     * @return string Anthropic API key
     */
    public function get_anthropic_api_key() {
        return $this->get('anthropic_api_key', '');
    }

    /**
     * Get the Anthropic model
     *
     * @return string Anthropic model
     */
    public function get_anthropic_model() {
        return $this->get('anthropic_model', 'claude-3-opus-20240229');
    }

    /**
     * Get the Anthropic temperature
     *
     * @return float Anthropic temperature
     */
    public function get_anthropic_temperature() {
        return (float) $this->get('anthropic_temperature', 0.7);
    }

    /**
     * Get the Anthropic max tokens
     *
     * @return int Anthropic max tokens
     */
    public function get_anthropic_max_tokens() {
        return (int) $this->get('anthropic_max_tokens', 1000);
    }

    /**
     * Get the primary API provider
     *
     * @return string Primary API provider
     */
    public function get_primary_api() {
        return $this->get('primary_api', 'openai');
    }

    
    /**
     * Get the log level
     *
     * @return string Log level
     */
    public function get_log_level() {
        $log_level = $this->get('log_level', 'info');
        return $log_level;
    }
}