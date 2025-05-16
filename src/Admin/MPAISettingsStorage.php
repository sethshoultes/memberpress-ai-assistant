<?php
/**
 * MemberPress AI Assistant Settings Storage
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Admin;

use MemberpressAiAssistant\Abstracts\AbstractService;

/**
 * Class for handling MemberPress AI Assistant settings storage
 * 
 * This class handles reading/writing settings to a single serialized option
 * and provides methods for getting, setting, and deleting settings.
 */
class MPAISettingsStorage extends AbstractService {
    /**
     * Option name for storing settings
     *
     * @var string
     */
    protected $option_name = 'mpai_settings';

    /**
     * Default settings values
     *
     * @var array
     */
    protected $defaults = [
        // General settings
        'chat_enabled' => true,
        
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
    protected $settings = [];

    /**
     * Constructor
     *
     * @param string $name Service name
     * @param mixed $logger Logger instance
     */
    public function __construct(string $name = 'settings_storage', $logger = null) {
        parent::__construct($name, $logger);
        
        // Load settings
        $this->load_settings();
    }

    /**
     * {@inheritdoc}
     */
    public function register($container): void {
        // Register this service with the container
        $container->singleton('settings_storage', function() {
            return $this;
        });

        // Log registration
        $this->log('Settings storage service registered');
    }

    /**
     * {@inheritdoc}
     */
    public function boot(): void {
        parent::boot();
        
        // Add hooks
        $this->addHooks();
        
        // Log boot
        $this->log('Settings storage service booted');
    }

    /**
     * Add hooks and filters for this service
     *
     * @return void
     */
    protected function addHooks(): void {
        // Hook to save settings when they are updated
        \add_action('memberpress_ai_assistant_update_settings', [$this, 'save_settings']);
    }

    /**
     * Load settings from WordPress options
     *
     * @return void
     */
    protected function load_settings(): void {
        // Get settings from options
        $saved_settings = \get_option($this->option_name, []);
        
        // Merge with defaults
        $this->settings = \wp_parse_args($saved_settings, $this->defaults);
        
        // Log settings loading
        $this->log('Settings loaded from WordPress options');
    }

    /**
     * Save settings to WordPress options
     *
     * @return bool Whether the settings were saved successfully
     */
    public function save_settings(): bool {
        $result = \update_option($this->option_name, $this->settings);
        
        // Log settings saving
        if ($result) {
            $this->log('Settings saved to WordPress options');
        } else {
            $this->log('Failed to save settings to WordPress options', ['level' => 'error']);
        }
        
        return $result;
    }

    /**
     * Get all settings
     *
     * @return array All settings
     */
    public function get_all_settings(): array {
        return $this->settings;
    }

    /**
     * Update multiple settings at once
     *
     * @param array $settings Settings to update
     * @param bool $save Whether to save settings to database
     * @return bool Whether the settings were updated successfully
     */
    public function update_settings(array $settings, bool $save = true): bool {
        // Update settings
        foreach ($settings as $key => $value) {
            $this->settings[$key] = $value;
        }
        
        // Save settings if requested
        if ($save) {
            return $this->save_settings();
        }
        
        return true;
    }

    /**
     * Reset settings to defaults
     *
     * @param bool $save Whether to save settings to database
     * @return bool Whether the settings were reset successfully
     */
    public function reset_settings(bool $save = true): bool {
        // Reset settings to defaults
        $this->settings = $this->defaults;
        
        // Save settings if requested
        if ($save) {
            return $this->save_settings();
        }
        
        return true;
    }

    /**
     * Get a setting value
     *
     * @param string $key Setting key
     * @param mixed $default Default value if setting not found
     * @return mixed Setting value
     */
    public function get_setting(string $key, $default = null) {
        // Check if setting exists
        if (isset($this->settings[$key])) {
            return $this->settings[$key];
        }
        
        // Check if default exists
        if (isset($this->defaults[$key])) {
            return $this->defaults[$key];
        }
        
        // Return provided default
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
    public function set_setting(string $key, $value, bool $save = true): bool {
        // Set setting
        $this->settings[$key] = $value;
        
        // Save settings if requested
        if ($save) {
            return $this->save_settings();
        }
        
        return true;
    }

    /**
     * Delete a setting
     *
     * @param string $key Setting key
     * @param bool $save Whether to save settings to database
     * @return bool Whether the setting was deleted successfully
     */
    public function delete_setting(string $key, bool $save = true): bool {
        // Check if setting exists
        if (isset($this->settings[$key])) {
            // Remove setting
            unset($this->settings[$key]);
            
            // Save settings if requested
            if ($save) {
                return $this->save_settings();
            }
            
            return true;
        }
        
        return false;
    }

    /**
     * Check if a setting exists
     *
     * @param string $key Setting key
     * @return bool Whether the setting exists
     */
    public function has_setting(string $key): bool {
        return isset($this->settings[$key]);
    }

    /**
     * Get the chat enabled setting
     *
     * @return bool Whether chat is enabled
     */
    public function is_chat_enabled(): bool {
        return (bool) $this->get_setting('chat_enabled', true);
    }

    /**
     * Set the chat enabled setting
     *
     * @param bool $enabled Whether chat is enabled
     * @param bool $save Whether to save settings to database
     * @return bool Whether the setting was set successfully
     */
    public function set_chat_enabled(bool $enabled, bool $save = true): bool {
        return $this->set_setting('chat_enabled', $enabled, $save);
    }

    /**
     * Get the chat location setting
     *
     * @return string Chat location ('admin_only', 'frontend', 'both')
     */
    public function get_chat_location(): string {
        return $this->get_setting('chat_location', 'admin_only');
    }

    /**
     * Set the chat location setting
     *
     * @param string $location Chat location ('admin_only', 'frontend', 'both')
     * @param bool $save Whether to save settings to database
     * @return bool Whether the setting was set successfully
     */
    public function set_chat_location(string $location, bool $save = true): bool {
        // Validate location
        $valid_locations = ['admin_only', 'frontend', 'both'];
        if (!in_array($location, $valid_locations)) {
            $this->log('Invalid chat location', [
                'location' => $location,
                'valid_locations' => $valid_locations,
                'level' => 'warning'
            ]);
            return false;
        }
        
        return $this->set_setting('chat_location', $location, $save);
    }

    /**
     * Get the chat position setting
     *
     * @return string Chat position ('bottom_right', 'bottom_left', 'top_right', 'top_left')
     */
    public function get_chat_position(): string {
        return $this->get_setting('chat_position', 'bottom_right');
    }

    /**
     * Set the chat position setting
     *
     * @param string $position Chat position ('bottom_right', 'bottom_left', 'top_right', 'top_left')
     * @param bool $save Whether to save settings to database
     * @return bool Whether the setting was set successfully
     */
    public function set_chat_position(string $position, bool $save = true): bool {
        // Validate position
        $valid_positions = ['bottom_right', 'bottom_left', 'top_right', 'top_left'];
        if (!in_array($position, $valid_positions)) {
            $this->log('Invalid chat position', [
                'position' => $position,
                'valid_positions' => $valid_positions,
                'level' => 'warning'
            ]);
            return false;
        }
        
        return $this->set_setting('chat_position', $position, $save);
    }

    /**
     * Get the user roles setting
     *
     * @return array User roles that can access the chat
     */
    public function get_user_roles(): array {
        return $this->get_setting('user_roles', ['administrator']);
    }

    /**
     * Set the user roles setting
     *
     * @param array $roles User roles that can access the chat
     * @param bool $save Whether to save settings to database
     * @return bool Whether the setting was set successfully
     */
    public function set_user_roles(array $roles, bool $save = true): bool {
        return $this->set_setting('user_roles', $roles, $save);
    }

    /**
     * Add a user role to the user roles setting
     *
     * @param string $role User role to add
     * @param bool $save Whether to save settings to database
     * @return bool Whether the role was added successfully
     */
    public function add_user_role(string $role, bool $save = true): bool {
        $roles = $this->get_user_roles();
        
        // Check if role already exists
        if (in_array($role, $roles)) {
            return true;
        }
        
        // Add role
        $roles[] = $role;
        
        // Update setting
        return $this->set_setting('user_roles', $roles, $save);
    }

    /**
     * Remove a user role from the user roles setting
     *
     * @param string $role User role to remove
     * @param bool $save Whether to save settings to database
     * @return bool Whether the role was removed successfully
     */
    public function remove_user_role(string $role, bool $save = true): bool {
        $roles = $this->get_user_roles();
        
        // Check if role exists
        $key = array_search($role, $roles);
        if ($key === false) {
            return true;
        }
        
        // Remove role
        unset($roles[$key]);
        $roles = array_values($roles);
        
        // Update setting
        return $this->set_setting('user_roles', $roles, $save);
    }

    /**
     * Check if a user role can access the chat
     *
     * @param string $role User role to check
     * @return bool Whether the role can access the chat
     */
    public function can_role_access_chat(string $role): bool {
        $roles = $this->get_user_roles();
        return in_array($role, $roles);
    }

    /**
     * Get the OpenAI API key
     *
     * @return string OpenAI API key
     */
    public function get_openai_api_key(): string {
        return $this->get_setting('openai_api_key', '');
    }

    /**
     * Set the OpenAI API key
     *
     * @param string $api_key OpenAI API key
     * @param bool $save Whether to save settings to database
     * @return bool Whether the setting was set successfully
     */
    public function set_openai_api_key(string $api_key, bool $save = true): bool {
        return $this->set_setting('openai_api_key', $api_key, $save);
    }

    /**
     * Get the OpenAI model
     *
     * @return string OpenAI model
     */
    public function get_openai_model(): string {
        return $this->get_setting('openai_model', 'gpt-4o');
    }

    /**
     * Set the OpenAI model
     *
     * @param string $model OpenAI model
     * @param bool $save Whether to save settings to database
     * @return bool Whether the setting was set successfully
     */
    public function set_openai_model(string $model, bool $save = true): bool {
        return $this->set_setting('openai_model', $model, $save);
    }

    /**
     * Get the Anthropic API key
     *
     * @return string Anthropic API key
     */
    public function get_anthropic_api_key(): string {
        return $this->get_setting('anthropic_api_key', '');
    }

    /**
     * Set the Anthropic API key
     *
     * @param string $api_key Anthropic API key
     * @param bool $save Whether to save settings to database
     * @return bool Whether the setting was set successfully
     */
    public function set_anthropic_api_key(string $api_key, bool $save = true): bool {
        return $this->set_setting('anthropic_api_key', $api_key, $save);
    }

    /**
     * Get the Anthropic model
     *
     * @return string Anthropic model
     */
    public function get_anthropic_model(): string {
        return $this->get_setting('anthropic_model', 'claude-3-opus-20240229');
    }

    /**
     * Set the Anthropic model
     *
     * @param string $model Anthropic model
     * @param bool $save Whether to save settings to database
     * @return bool Whether the setting was set successfully
     */
    public function set_anthropic_model(string $model, bool $save = true): bool {
        return $this->set_setting('anthropic_model', $model, $save);
    }

    /**
     * Get the primary API provider
     *
     * @return string Primary API provider ('openai', 'anthropic')
     */
    public function get_primary_api(): string {
        return $this->get_setting('primary_api', 'openai');
    }

    /**
     * Set the primary API provider
     *
     * @param string $provider Primary API provider ('openai', 'anthropic')
     * @param bool $save Whether to save settings to database
     * @return bool Whether the setting was set successfully
     */
    public function set_primary_api(string $provider, bool $save = true): bool {
        // Validate provider
        $valid_providers = ['openai', 'anthropic'];
        if (!in_array($provider, $valid_providers)) {
            $this->log('Invalid primary API provider', [
                'provider' => $provider,
                'valid_providers' => $valid_providers,
                'level' => 'warning'
            ]);
            return false;
        }
        
        return $this->set_setting('primary_api', $provider, $save);
    }

    /**
     * Get the OpenAI temperature setting
     *
     * @return float OpenAI temperature (0.0 to 1.0)
     */
    public function get_openai_temperature(): float {
        return (float) $this->get_setting('openai_temperature', 0.7);
    }

    /**
     * Set the OpenAI temperature setting
     *
     * @param float $temperature OpenAI temperature (0.0 to 1.0)
     * @param bool $save Whether to save settings to database
     * @return bool Whether the setting was set successfully
     */
    public function set_openai_temperature(float $temperature, bool $save = true): bool {
        // Validate temperature range
        $temperature = max(0.0, min(1.0, $temperature));
        
        return $this->set_setting('openai_temperature', $temperature, $save);
    }

    /**
     * Get the OpenAI max tokens setting
     *
     * @return int OpenAI max tokens
     */
    public function get_openai_max_tokens(): int {
        return (int) $this->get_setting('openai_max_tokens', 1000);
    }

    /**
     * Set the OpenAI max tokens setting
     *
     * @param int $max_tokens OpenAI max tokens
     * @param bool $save Whether to save settings to database
     * @return bool Whether the setting was set successfully
     */
    public function set_openai_max_tokens(int $max_tokens, bool $save = true): bool {
        // Ensure max_tokens is positive
        $max_tokens = max(1, $max_tokens);
        
        return $this->set_setting('openai_max_tokens', $max_tokens, $save);
    }

    /**
     * Get the Anthropic temperature setting
     *
     * @return float Anthropic temperature (0.0 to 1.0)
     */
    public function get_anthropic_temperature(): float {
        return (float) $this->get_setting('anthropic_temperature', 0.7);
    }

    /**
     * Set the Anthropic temperature setting
     *
     * @param float $temperature Anthropic temperature (0.0 to 1.0)
     * @param bool $save Whether to save settings to database
     * @return bool Whether the setting was set successfully
     */
    public function set_anthropic_temperature(float $temperature, bool $save = true): bool {
        // Validate temperature range
        $temperature = max(0.0, min(1.0, $temperature));
        
        return $this->set_setting('anthropic_temperature', $temperature, $save);
    }

    /**
     * Get the Anthropic max tokens setting
     *
     * @return int Anthropic max tokens
     */
    public function get_anthropic_max_tokens(): int {
        return (int) $this->get_setting('anthropic_max_tokens', 1000);
    }

    /**
     * Set the Anthropic max tokens setting
     *
     * @param int $max_tokens Anthropic max tokens
     * @param bool $save Whether to save settings to database
     * @return bool Whether the setting was set successfully
     */
    public function set_anthropic_max_tokens(int $max_tokens, bool $save = true): bool {
        // Ensure max_tokens is positive
        $max_tokens = max(1, $max_tokens);
        
        return $this->set_setting('anthropic_max_tokens', $max_tokens, $save);
    }
}