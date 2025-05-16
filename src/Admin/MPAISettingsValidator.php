<?php
/**
 * MemberPress AI Assistant Settings Validator
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Admin;

use MemberpressAiAssistant\Abstracts\AbstractService;

/**
 * Class for validating and sanitizing MemberPress AI Assistant settings
 * 
 * This class provides methods for validating different types of settings
 * and ensures that all settings conform to expected formats and values.
 */
class MPAISettingsValidator extends AbstractService {
    /**
     * Valid chat locations
     *
     * @var array
     */
    protected $valid_locations = ['admin_only', 'frontend', 'both'];

    /**
     * Valid chat positions
     *
     * @var array
     */
    protected $valid_positions = ['bottom_right', 'bottom_left', 'top_right', 'top_left'];

    /**
     * Constructor
     *
     * @param string $name Service name
     * @param mixed $logger Logger instance
     */
    public function __construct(string $name = 'settings_validator', $logger = null) {
        parent::__construct($name, $logger);
    }

    /**
     * {@inheritdoc}
     */
    public function register($container): void {
        // Register this service with the container
        $container->singleton('settings_validator', function() {
            return $this;
        });

        // Log registration
        $this->log('Settings validator service registered');
    }

    /**
     * {@inheritdoc}
     */
    public function boot(): void {
        parent::boot();
        
        // Add hooks
        $this->addHooks();
        
        // Log boot
        $this->log('Settings validator service booted');
    }

    /**
     * Add hooks and filters for this service
     *
     * @return void
     */
    protected function addHooks(): void {
        // Add filter to validate settings before they are saved
        add_filter('mpai_validate_settings', [$this, 'validate_settings'], 10, 1);
    }

    /**
     * Validate all settings
     *
     * @param array $settings Settings to validate
     * @return array Validated settings
     */
    public function validate_settings(array $settings): array {
        $validated = [];
        
        // Validate chat_enabled
        if (isset($settings['chat_enabled'])) {
            $validated['chat_enabled'] = $this->validate_boolean($settings['chat_enabled']);
        }
        
        // Validate chat_location
        if (isset($settings['chat_location'])) {
            $validated['chat_location'] = $this->validate_chat_location($settings['chat_location']);
        }
        
        // Validate chat_position
        if (isset($settings['chat_position'])) {
            $validated['chat_position'] = $this->validate_chat_position($settings['chat_position']);
        }
        
        // Validate user_roles
        if (isset($settings['user_roles'])) {
            $validated['user_roles'] = $this->validate_user_roles($settings['user_roles']);
        }
        
        // Log validation
        $this->log('Settings validated', [
            'original' => $settings,
            'validated' => $validated
        ]);
        
        return $validated;
    }

    /**
     * Validate a boolean setting
     *
     * @param mixed $value Value to validate
     * @return bool Validated boolean value
     */
    public function validate_boolean($value): bool {
        // Convert various inputs to boolean
        if (is_string($value)) {
            $value = strtolower($value);
            return in_array($value, ['true', 'yes', '1', 'on'], true);
        }
        
        return (bool) $value;
    }

    /**
     * Validate a string setting
     *
     * @param mixed $value Value to validate
     * @param array $valid_values Valid values for this string
     * @param string $default Default value if validation fails
     * @return string Validated string value
     */
    public function validate_string($value, array $valid_values, string $default): string {
        // Ensure value is a string
        $value = is_string($value) ? $value : (string) $value;
        
        // Check if value is in valid values
        if (in_array($value, $valid_values, true)) {
            return $value;
        }
        
        // Log invalid value
        $this->log('Invalid string value', [
            'value' => $value,
            'valid_values' => $valid_values,
            'default' => $default,
            'level' => 'warning'
        ]);
        
        return $default;
    }

    /**
     * Validate an array setting
     *
     * @param mixed $value Value to validate
     * @param array $valid_values Valid values for array items
     * @param array $default Default value if validation fails
     * @return array Validated array value
     */
    public function validate_array($value, array $valid_values = [], array $default = []): array {
        // Ensure value is an array
        if (!is_array($value)) {
            // Log invalid value
            $this->log('Invalid array value', [
                'value' => $value,
                'level' => 'warning'
            ]);
            
            return $default;
        }
        
        // If no valid values specified, return the array as is
        if (empty($valid_values)) {
            return $value;
        }
        
        // Filter array to only include valid values
        $validated = array_filter($value, function($item) use ($valid_values) {
            return in_array($item, $valid_values, true);
        });
        
        // If no valid items, return default
        if (empty($validated)) {
            // Log invalid array items
            $this->log('No valid array items', [
                'value' => $value,
                'valid_values' => $valid_values,
                'default' => $default,
                'level' => 'warning'
            ]);
            
            return $default;
        }
        
        return array_values($validated);
    }

    /**
     * Validate chat_enabled setting
     *
     * @param mixed $value Value to validate
     * @return bool Validated boolean value
     */
    public function validate_chat_enabled($value): bool {
        return $this->validate_boolean($value);
    }

    /**
     * Validate chat_location setting
     *
     * @param mixed $value Value to validate
     * @return string Validated chat location
     */
    public function validate_chat_location($value): string {
        return $this->validate_string($value, $this->valid_locations, 'admin_only');
    }

    /**
     * Validate chat_position setting
     *
     * @param mixed $value Value to validate
     * @return string Validated chat position
     */
    public function validate_chat_position($value): string {
        return $this->validate_string($value, $this->valid_positions, 'bottom_right');
    }

    /**
     * Validate user_roles setting
     *
     * @param mixed $value Value to validate
     * @return array Validated user roles
     */
    public function validate_user_roles($value): array {
        // Get all valid WordPress roles
        $wp_roles = wp_roles();
        $valid_roles = array_keys($wp_roles->get_names());
        
        // Validate roles
        $validated = $this->validate_array($value, $valid_roles, ['administrator']);
        
        // Ensure at least one role is selected
        if (empty($validated)) {
            $validated = ['administrator'];
            
            // Log default role assignment
            $this->log('No valid roles selected, defaulting to administrator', [
                'level' => 'warning'
            ]);
        }
        
        return $validated;
    }

    /**
     * Get valid chat locations
     *
     * @return array Valid chat locations
     */
    public function get_valid_locations(): array {
        return $this->valid_locations;
    }

    /**
     * Get valid chat positions
     *
     * @return array Valid chat positions
     */
    public function get_valid_positions(): array {
        return $this->valid_positions;
    }
}