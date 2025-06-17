<?php
/**
 * Settings Model Service
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Services\Settings;

use MemberpressAiAssistant\Abstracts\AbstractService;
use MemberpressAiAssistant\Interfaces\ServiceInterface;
use MemberpressAiAssistant\Interfaces\SettingsModelInterface;
use MemberpressAiAssistant\DI\ServiceLocator;

/**
 * Service for handling MemberPress Copilot settings
 * 
 * This class combines storage and validation functionality for settings
 * with the service architecture required by the DI system.
 * It handles reading/writing settings to a single serialized option,
 * validates all settings values, and provides methods for getting and setting values.
 */
class SettingsModelService extends AbstractService implements ServiceInterface, SettingsModelInterface {
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
    ];

    /**
     * Current settings
     *
     * @var array
     */
    private $settings = [];

    /**
     * Whether service is in degraded mode
     *
     * @var bool
     */
    protected $degradedMode = false;

    /**
     * Constructor
     *
     * @param string $name Service name
     * @param mixed $logger Logger instance
     */
    public function __construct(string $name = 'settings.model', $logger = null) {
        parent::__construct($name, $logger);
        
        // Set dependencies
        $this->dependencies = ['logger'];
        
        // Load settings
        $this->load_settings();
    }

    /**
     * Register the service with the service locator
     *
     * @param ServiceLocator $serviceLocator The service locator
     * @return void
     */
    public function register($serviceLocator): void {
        $this->log('Registering settings model service');
        
        // Register this service with the service locator
        $serviceLocator->register($this->getServiceName(), function() {
            return $this;
        });
    }

    /**
     * Boot the service
     *
     * @return void
     */
    public function boot(): void {
        parent::boot();
        
        // Validate dependencies before proceeding
        if (!$this->validateDependencies()) {
            $this->log('Service booted in degraded mode due to missing dependencies');
            return;
        }
        
        // Add any hooks or filters needed
        $this->addHooks();
    }

    /**
     * Add hooks and filters for this service
     *
     * @return void
     */
    protected function addHooks(): void {
        // Add any hooks or filters needed for the settings model
        // For example, you might want to add a hook to save settings on plugin deactivation
        add_action('admin_init', [$this, 'maybe_upgrade_settings']);
    }

    /**
     * Check if settings need to be upgraded and perform upgrade if needed
     *
     * @return void
     */
    public function maybe_upgrade_settings(): void {
        // This is a placeholder for any settings upgrade logic
        // that might be needed in the future
    }

    /**
     * Get a setting value
     *
     * @param string $key Setting key
     * @param mixed $default Default value if setting not found
     * @return mixed Setting value
     */
    public function get(string $key, $default = null) {
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
    public function set(string $key, $value, bool $save = true): bool {
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
    public function save(): bool {
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
    public function get_all(): array {
        return $this->settings;
    }

    /**
     * Update multiple settings at once
     *
     * @param array $settings Settings to update
     * @param bool $save Whether to save settings to database
     * @return bool Whether the settings were updated successfully
     */
    public function update(array $settings, bool $save = true): bool {
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
    public function reset(bool $save = true): bool {
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
    public function validate(array $settings): array {
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
    public function is_chat_enabled(): bool {
        return (bool) $this->get('chat_enabled', true);
    }

    /**
     * Get the chat location
     *
     * @return string Chat location
     */
    public function get_chat_location(): string {
        return $this->get('chat_location', 'admin_only');
    }

    /**
     * Get the chat position
     *
     * @return string Chat position
     */
    public function get_chat_position(): string {
        return $this->get('chat_position', 'bottom_right');
    }

    /**
     * Get the user roles that can access the chat
     *
     * @return array User roles
     */
    public function get_user_roles(): array {
        return $this->get('user_roles', ['administrator']);
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
     * Validate service dependencies
     *
     * @return bool True if all dependencies are available
     */
    protected function validateDependencies(): bool {
        foreach ($this->dependencies as $dependency) {
            if (!$this->serviceLocator || !$this->serviceLocator->has($dependency)) {
                $this->handleMissingDependency($dependency);
                return false;
            }
        }
        return true;
    }

    /**
     * Handle missing dependency with graceful degradation
     *
     * @param string $dependency Missing dependency name
     * @return void
     */
    protected function handleMissingDependency(string $dependency): void {
        $message = sprintf(
            'Missing required dependency: %s for service: %s', 
            $dependency, 
            $this->getServiceName()
        );
        
        if ($this->logger) {
            $this->logger->error($message, [
                'service' => $this->getServiceName(),
                'missing_dependency' => $dependency,
                'available_dependencies' => $this->serviceLocator ? 
                    array_keys($this->serviceLocator->getServices()) : []
            ]);
        }
        
        // Set degraded mode flag for graceful degradation
        $this->setDegradedMode(true);
    }

    /**
     * Execute operation with error handling
     *
     * @param callable $operation Operation to execute
     * @param string $context Context description for error logging
     * @param mixed $default Default value to return on error
     * @return mixed Operation result or default value
     */
    protected function executeWithErrorHandling(callable $operation, string $context, $default = null) {
        try {
            return $operation();
        } catch (\Exception $e) {
            $this->handleError($e, $context);
            return $default;
        }
    }

    /**
     * Handle errors with comprehensive logging
     *
     * @param \Exception $e Exception to handle
     * @param string $context Context description
     * @return void
     */
    protected function handleError(\Exception $e, string $context): void {
        $message = sprintf('Error in %s: %s', $context, $e->getMessage());
        
        if ($this->logger) {
            $this->logger->error($message, [
                'exception' => $e,
                'context' => $context,
                'service' => $this->getServiceName(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Set degraded mode flag
     *
     * @param bool $degraded Whether service is in degraded mode
     * @return void
     */
    protected function setDegradedMode(bool $degraded): void {
        $this->degradedMode = $degraded;
        
        if ($this->logger && $degraded) {
            $this->logger->warning('Service entering degraded mode', [
                'service' => $this->getServiceName()
            ]);
        }
    }

    /**
     * Check if service is in degraded mode
     *
     * @return bool True if in degraded mode
     */
    protected function isDegradedMode(): bool {
        return $this->degradedMode ?? false;
    }






    /**
     * Get the log level
     *
     * @return string Log level
     */
    public function get_log_level(): string {
        return $this->get('log_level', 'info');
    }
}