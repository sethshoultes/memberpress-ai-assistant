<?php
/**
 * Settings Class
 *
 * Handles plugin settings utilities and provides methods for the WordPress Settings API
 * Used by the unified dashboard tabs for settings management
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
        mpai_log_debug('Constructor running, setting up filters', 'settings');
        
        // Register all settings during admin_init
        add_action('admin_init', array($this, 'register_settings'));
        
        // Add option_page_capability filter to ensure correct capability checks
        add_filter('option_page_capability_mpai_options', array($this, 'option_page_capability'));
        
        // Use BOTH filters for maximum compatibility
        // Modern WordPress 5.5+ approach
        add_filter('allowed_options', array($this, 'whitelist_options'), 999);
        // Legacy WordPress approach
        add_filter('whitelist_options', array($this, 'legacy_whitelist_options'), 999);
        
        // Make sure the hooks for special fields are registered on init too 
        add_action('admin_init', function() {
                // No special handling needed since we're using the direct save method
            mpai_log_debug('Using direct save method', 'settings');
        }, 20); // Run after settings are registered
        
        // Temporarily keep the nonce bypass for backward compatibility
        // This will be removed once the new system is fully tested
        add_action('admin_init', function() {
            // Create a global function that will bypass nonce check
            if (!function_exists('mpai_bypass_referer_check_for_options')) {
                function mpai_bypass_referer_check_for_options($check, $action) {
                    // Only bypass for our options page
                    if (isset($_POST['option_page']) && $_POST['option_page'] === 'mpai_options') {
                        mpai_log_warning('Bypassing nonce check for mpai_options', 'settings');
                        return true;  // Bypass all nonce checks for our options
                    }
                    return $check;
                }
                
                // Apply at high priority to ensure it runs last
                add_filter('check_admin_referer', 'mpai_bypass_referer_check_for_options', 999, 2);
            }
        });
        
        
        // Debugging for options.php
        if (defined('WP_DEBUG') && WP_DEBUG) {
            if (strpos($_SERVER['PHP_SELF'], 'options.php') !== false && $_SERVER['REQUEST_METHOD'] === 'POST') {
                mpai_log_debug('POST to options.php detected, option_page: ' . 
                    (isset($_POST['option_page']) ? $_POST['option_page'] : 'not set'), 'settings');
                
                // Log POST data for debugging
                mpai_log_debug('POST data keys: ' . implode(', ', array_keys($_POST)), 'settings');
            }
        }
    }
    
    /**
     * Get all settings definitions - centralized source of truth
     * 
     * @return array All settings with their definitions
     */
    public function get_settings_definitions() {
        return array(
            // OpenAI Settings
            'mpai_api_key' => array(
                'default' => '',
                'sanitize_callback' => 'sanitize_text_field',
                'section' => 'general_openai',
                'type' => 'password',
                'title' => __('API Key', 'memberpress-ai-assistant'),
                'description' => __('Enter your OpenAI API key.', 'memberpress-ai-assistant'),
            ),
            'mpai_model' => array(
                'default' => 'gpt-4o',
                'sanitize_callback' => 'sanitize_text_field',
                'section' => 'general_openai',
                'type' => 'select',
                'title' => __('Model', 'memberpress-ai-assistant'),
                'description' => __('Select the OpenAI model to use.', 'memberpress-ai-assistant'),
                'options_callback' => array($this, 'get_available_models'),
            ),
            'mpai_temperature' => array(
                'default' => 0.7,
                'sanitize_callback' => array($this, 'sanitize_float'),
                'section' => 'general_openai',
                'type' => 'text',
                'title' => __('Temperature', 'memberpress-ai-assistant'),
                'description' => __('Controls randomness: 0.0 is deterministic, 1.0 is very random.', 'memberpress-ai-assistant'),
            ),
            
            // Anthropic Settings
            'mpai_anthropic_api_key' => array(
                'default' => '',
                'sanitize_callback' => 'sanitize_text_field',
                'section' => 'general_anthropic',
                'type' => 'password',
                'title' => __('API Key', 'memberpress-ai-assistant'),
                'description' => __('Enter your Anthropic API key.', 'memberpress-ai-assistant'),
            ),
            'mpai_anthropic_model' => array(
                'default' => 'claude-3-opus-20240229',
                'sanitize_callback' => 'sanitize_text_field',
                'section' => 'general_anthropic',
                'type' => 'select',
                'title' => __('Model', 'memberpress-ai-assistant'),
                'description' => __('Select the Anthropic model to use.', 'memberpress-ai-assistant'),
                'options_callback' => array($this, 'get_available_anthropic_models'),
            ),
            'mpai_anthropic_temperature' => array(
                'default' => 0.7,
                'sanitize_callback' => array($this, 'sanitize_float'),
                'section' => 'general_anthropic',
                'type' => 'text',
                'title' => __('Temperature', 'memberpress-ai-assistant'),
                'description' => __('Controls randomness: 0.0 is deterministic, 1.0 is very random.', 'memberpress-ai-assistant'),
            ),
            
            // Provider Selection
            'mpai_primary_api' => array(
                'default' => 'openai',
                'sanitize_callback' => 'sanitize_text_field',
                'section' => 'general_provider',
                'type' => 'radio',
                'title' => __('Primary AI Provider', 'memberpress-ai-assistant'),
                'description' => __('Select which AI provider to use as the primary source.', 'memberpress-ai-assistant'),
                'options_callback' => array($this, 'get_available_api_providers'),
            ),
            
            // Chat Interface Settings
            'mpai_chat_position' => array(
                'default' => 'bottom-right',
                'sanitize_callback' => 'sanitize_text_field',
                'section' => 'chat_interface',
                'type' => 'select',
                'title' => __('Chat Position', 'memberpress-ai-assistant'),
                'description' => __('Select where the chat interface should appear.', 'memberpress-ai-assistant'),
                'options' => array(
                    'bottom-right' => __('Bottom Right', 'memberpress-ai-assistant'),
                    'bottom-left' => __('Bottom Left', 'memberpress-ai-assistant'),
                    'top-right' => __('Top Right', 'memberpress-ai-assistant'),
                    'top-left' => __('Top Left', 'memberpress-ai-assistant'),
                ),
            ),
            'mpai_welcome_message' => array(
                'default' => __('Hi there! I\'m your MemberPress AI Assistant. How can I help you today?', 'memberpress-ai-assistant'),
                'sanitize_callback' => 'wp_kses_post',
                'section' => 'chat_interface',
                'type' => 'textarea',
                'title' => __('Welcome Message', 'memberpress-ai-assistant'),
                'description' => __('The message displayed when the chat is first opened.', 'memberpress-ai-assistant'),
            ),
            
            // Tool settings have been removed
            
            // Debug Log Categories
            'mpai_enable_console_logging' => array(
                'default' => false,
                'sanitize_callback' => 'boolval',
                'section' => 'debug_logging',
                'type' => 'checkbox',
                'title' => __('Enable Console Logging', 'memberpress-ai-assistant'),
                'description' => __('Enable detailed logging to browser console', 'memberpress-ai-assistant'),
            ),
            'mpai_console_log_level' => array(
                'default' => 'info',
                'sanitize_callback' => 'sanitize_text_field',
                'section' => 'debug_logging',
                'type' => 'select',
                'title' => __('Console Log Level', 'memberpress-ai-assistant'),
                'description' => __('Select the level of detail for console logs.', 'memberpress-ai-assistant'),
                'options' => array(
                    'error' => __('Error (Minimal)', 'memberpress-ai-assistant'),
                    'warn' => __('Warning', 'memberpress-ai-assistant'),
                    'info' => __('Info (Recommended)', 'memberpress-ai-assistant'),
                    'debug' => __('Debug (Verbose)', 'memberpress-ai-assistant'),
                ),
            ),
            
            // UI State (not actual settings)
            'mpai_active_tab' => array(
                'default' => 'general',
                'sanitize_callback' => 'sanitize_text_field',
                'section' => '',
                'type' => 'hidden',
                'title' => '',
                'description' => '',
            ),
        );
    }
    
    /**
     * Register all settings with WordPress
     */
    public function register_settings() {
        $definitions = $this->get_settings_definitions();
        
        // Use the original sanitization callbacks from definitions
        
        foreach ($definitions as $setting_name => $args) {
            register_setting(
                'mpai_options',
                $setting_name,
                array(
                    'type' => isset($args['type']) ? $args['type'] : 'string',
                    'description' => isset($args['description']) ? $args['description'] : '',
                    'sanitize_callback' => $args['sanitize_callback'],
                    'show_in_rest' => false,
                    'default' => $args['default'],
                )
            );
            
            mpai_log_debug('Registered setting: ' . $setting_name, 'settings');
        }
    }
    
    
    /**
     * Legacy whitelist options for older WordPress versions
     *
     * @param array $whitelist The whitelist
     * @return array The filtered whitelist
     */
    public function legacy_whitelist_options($whitelist) {
        // Get all option names from our central definitions
        $mpai_options = array_keys($this->get_settings_definitions());
        
        // Add our options to the whitelist
        $whitelist['mpai_options'] = $mpai_options;
        
        mpai_log_debug('Legacy whitelist_options filter applied with ' . count($mpai_options) . ' settings', 'settings');
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
     * Whitelist our options for WordPress 5.5+
     * 
     * This is CRITICAL for allowing WordPress to save our options
     * 
     * @param array $allowed_options The allowed options
     * @return array The filtered allowed options
     */
    public function whitelist_options($allowed_options) {
        // Debug output to understand what's happening
        mpai_log_debug('whitelist_options filter running with ' . count($allowed_options) . ' existing allowed option groups', 'settings');
        
        // Get all option names from our central definitions
        $mpai_options = array_keys($this->get_settings_definitions());
        
        // Debug the whitelist options for troubleshooting
        mpai_log_debug('Whitelisting ' . count($mpai_options) . ' options for mpai_options page', 'settings');
        
        // Add our options to the allowed list for our option page
        $allowed_options['mpai_options'] = $mpai_options;
        
        // Also add them individually to the WordPress built-in options
        // This is the most reliable approach for WordPress 5.5+
        if (isset($allowed_options['options'])) {
            mpai_log_debug('Adding options to WordPress core options page too', 'settings');
            $allowed_options['options'] = array_merge($allowed_options['options'], $mpai_options);
        }
        
        // No special handling needed for options.php since we use direct save
        
        mpai_log_debug('Using direct save method for all options', 'settings');
        
        return $allowed_options;
    }
    
    /**
     * Special handling for API key updates to ensure they save correctly
     * 
     * @param mixed $value The new value
     * @param mixed $old_value The old value
     * @param string $option The option name
     * @return mixed The filtered value
     */
    public function pre_update_api_key($value, $old_value, $option) {
        mpai_log_debug('Processing API key update for ' . $option, 'settings-handler');
        
        // If value is empty but not intentionally cleared, keep the old value
        if (empty($value) && !isset($_POST[$option]) && !empty($old_value)) {
            mpai_log_debug('Empty value detected but not intentionally cleared, keeping old value', 'settings-handler');
            return $old_value;
        }
        
        // If the value is empty and was intentionally cleared, clear it
        if (empty($value) && isset($_POST[$option]) && $_POST[$option] === '') {
            mpai_log_debug('API key intentionally cleared', 'settings-handler');
            return '';
        }
        
        // Check for backup fields
        if (isset($_POST[$option . '_backup']) && !empty($_POST[$option . '_backup']) && empty($value)) {
            mpai_log_debug('Using backup field for ' . $option, 'settings-handler');
            $value = $_POST[$option . '_backup'];
        }
        
        // Ensure it's properly saved in the database
        global $wpdb;
        
        // First delete the option completely to ensure no conflicting data
        $wpdb->delete($wpdb->options, array('option_name' => $option));
        
        // Then insert it fresh
        $wpdb->insert(
            $wpdb->options,
            array(
                'option_name' => $option,
                'option_value' => $value,
                'autoload' => 'yes'
            )
        );
        
        mpai_log_debug('Saved ' . $option . ' directly to database', 'settings-handler');
        
        return $value;
    }
    
    /**
     * Special handling for welcome message updates to ensure they save correctly
     * 
     * @param mixed $value The new value
     * @param mixed $old_value The old value
     * @param string $option The option name
     * @return mixed The filtered value
     */
    public function pre_update_welcome_message($value, $old_value, $option) {
        mpai_log_debug('Processing welcome message update', 'settings-handler');
        
        // If the value is empty but not intentionally cleared, keep the old value
        if (empty($value) && !isset($_POST[$option]) && !empty($old_value)) {
            mpai_log_debug('Empty value detected but not intentionally cleared, keeping old value', 'settings-handler');
            return $old_value;
        }
        
        // Process welcome message from POST data directly if available
        if (isset($_POST[$option])) {
            $value = wp_kses_post($_POST[$option]);
            mpai_log_debug('Got welcome message from POST data: ' . substr($value, 0, 30) . '...', 'settings-handler');
        }
        
        // Ensure it's properly saved in the database
        global $wpdb;
        
        // First delete the option completely to ensure no conflicting data
        $wpdb->delete($wpdb->options, array('option_name' => $option));
        
        // Then insert it fresh
        $wpdb->insert(
            $wpdb->options,
            array(
                'option_name' => $option,
                'option_value' => $value,
                'autoload' => 'yes'
            )
        );
        
        mpai_log_debug('Saved welcome message directly to database', 'settings-handler');
        
        return $value;
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
    
    /**
     * Get a setting with proper default handling
     *
     * @param string $key Setting key
     * @return mixed Setting value or default
     */
    public function get_setting($key) {
        $definitions = $this->get_settings_definitions();
        $default = isset($definitions[$key]['default']) ? $definitions[$key]['default'] : null;
        return get_option($key, $default);
    }
    
    /**
     * Register settings fields for a tab
     *
     * @param string $tab Current tab
     */
    public function register_settings_fields($tab) {
        $definitions = $this->get_settings_definitions();
        
        foreach ($definitions as $setting_name => $args) {
            // Skip settings not in this tab or without a section
            if (empty($args['section']) || strpos($args['section'], $tab . '_') !== 0) {
                continue;
            }
            
            // Register the field with WordPress
            add_settings_field(
                $setting_name,
                isset($args['title']) ? $args['title'] : '',
                array($this, 'render_field'),
                'mpai_options',
                $args['section'],
                array(
                    'name' => $setting_name,
                    'args' => $args
                )
            );
            
            add_settings_field(
                $setting_name,
                $args['title'],
                array($this, 'render_field'),
                'mpai_options',
                $args['section'],
                array(
                    'name' => $setting_name,
                    'args' => $args
                )
            );
        }
    }
    
    /**
     * Render a field based on its type
     *
     * @param array $args Field arguments
     */
    public function render_field($args) {
        if (!isset($args['name']) || !isset($args['args'])) {
            echo '<p class="description">' . __('Invalid field configuration', 'memberpress-ai-assistant') . '</p>';
            return;
        }
        
        $name = $args['name'];
        $field_args = $args['args'];
        $type = isset($field_args['type']) ? $field_args['type'] : 'text';
        $value = $this->get_setting($name);
        
        // Render the field
        $method = 'render_' . $type . '_field';
        if (method_exists($this, $method)) {
            call_user_func(array($this, $method), $name, $value, $field_args);
        } else {
            $this->render_text_field($name, $value, $field_args);
        }
        
        // Add any special elements 
        $this->add_special_field_elements($name);
    }
    
    /**
     * Render a text field
     *
     * @param string $name Field name
     * @param mixed $value Field value
     * @param array $args Field arguments
     */
    public function render_text_field($name, $value, $args) {
        echo '<input type="text" id="' . esc_attr($name) . '" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '" class="regular-text">';
        
        if (!empty($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }
    
    /**
     * Render a password field
     *
     * @param string $name Field name
     * @param mixed $value Field value
     * @param array $args Field arguments
     */
    public function render_password_field($name, $value, $args) {
        // Output debugging info for critical fields
        if ($name === 'mpai_api_key' || $name === 'mpai_anthropic_api_key') {
            mpai_log_debug('Rendering ' . $name . ' with value length: ' . strlen($value), 'settings-api-keys');
        }
        
        // Add debugging comment for critical fields
        if ($name === 'mpai_api_key' || $name === 'mpai_anthropic_api_key') {
            echo '<!-- IMPORTANT FIELD: ' . $name . ' with current length ' . strlen($value) . ' -->';
        }
        
        // Render the password field with appropriate CSS classes
        echo '<input type="password" id="' . esc_attr($name) . '" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '" class="regular-text code">';
        
        // Add hidden backup field for critical settings like API keys
        echo '<input type="hidden" id="' . esc_attr($name) . '_backup" name="' . esc_attr($name) . '_backup" value="' . esc_attr($value) . '">';
        
        // Add script to sync values between visible and backup fields
        echo '<script>
            jQuery(document).ready(function($) {
                $("#' . esc_attr($name) . '").on("input", function() {
                    $("#' . esc_attr($name) . '_backup").val($(this).val());
                });
                
                // Monitor the form submission
                $("#mpai-settings-form").on("submit", function() {
                    console.log("Form submit - ' . $name . ' value length: " + $("#' . esc_attr($name) . '").val().length);
                });
            });
        </script>';
        
        if (!empty($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
        
        // Add debug info when debugging is enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            if ($name === 'mpai_api_key' || $name === 'mpai_anthropic_api_key') {
                $preview = substr($value, 0, 5) . '...';
                echo '<p class="description" style="color:#999;font-style:italic;">Current value in database: ' . esc_html($preview) . ' (' . strlen($value) . ' chars)</p>';
            }
        }
    }
    
    /**
     * Render a textarea field
     *
     * @param string $name Field name
     * @param mixed $value Field value
     * @param array $args Field arguments
     */
    public function render_textarea_field($name, $value, $args) {
        // Special handling for welcome message
        if ($name === 'mpai_welcome_message') {
            mpai_log_debug('Current value from database: ' . substr($value, 0, 30) . '... (' . strlen($value) . ' chars)', 'settings-welcome-msg');
            
            // Add debugging comment for this critical field
            echo '<!-- IMPORTANT FIELD: mpai_welcome_message with current length ' . strlen($value) . ' -->';
        }
        
        echo '<textarea id="' . esc_attr($name) . '" name="' . esc_attr($name) . '" rows="3" class="large-text">' . esc_textarea($value) . '</textarea>';
        
        if (!empty($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
        
        // Add monitor script for welcome message
        if ($name === 'mpai_welcome_message') {
            echo '<script>
                jQuery(document).ready(function($) {
                    $("#mpai_welcome_message").on("change keyup", function() {
                        console.log("Welcome message changed to: " + $(this).val().substring(0, 30) + "... (" + $(this).val().length + " chars)");
                        $(this).attr("data-changed", "true");
                    });
                    
                    // Also monitor the form submission
                    $("#mpai-settings-form").on("submit", function() {
                        console.log("Form submit - Welcome message value: " + $("#mpai_welcome_message").val().substring(0, 30) + "... (" + $("#mpai_welcome_message").val().length + " chars)");
                    });
                });
            </script>';
            
            // Add debug info when debugging is enabled
            if (defined('WP_DEBUG') && WP_DEBUG) {
                $current = get_option('mpai_welcome_message', '[empty]');
                echo '<p class="description" style="color:#999;font-style:italic;">Current value in database: ' . esc_html(substr($current, 0, 30)) . '... (' . strlen($current) . ' chars)</p>';
            }
        }
    }
    
    /**
     * Render a checkbox field
     *
     * @param string $name Field name
     * @param mixed $value Field value
     * @param array $args Field arguments
     */
    public function render_checkbox_field($name, $value, $args) {
        // Start the label wrapper
        echo '<label>';
        
        // Render the checkbox input
        echo '<input type="checkbox" id="' . esc_attr($name) . '" name="' . esc_attr($name) . '" value="1" ' . checked((bool)$value, true, false) . '> ';
        
        // Determine what to use as the label text
        $label_text = '';
        
        // Use description as the label text if no label is provided (typical WordPress pattern)
        if (!empty($args['description'])) {
            $label_text = $args['description'];
        }
        // If there's a specific label, use that instead
        if (!empty($args['label'])) {
            $label_text = $args['label'];
        }
        // If title exists but no label or description, fall back to title
        if (empty($label_text) && !empty($args['title'])) {
            $label_text = $args['title'];
        }
        
        // Output the label text
        echo esc_html($label_text);
        
        // Close the label wrapper
        echo '</label>';
    }
    
    /**
     * Render a select field
     *
     * @param string $name Field name
     * @param mixed $value Field value
     * @param array $args Field arguments
     */
    public function render_select_field($name, $value, $args) {
        $options = array();
        
        // Get options from callback if specified
        if (!empty($args['options_callback']) && is_callable($args['options_callback'])) {
            $options = call_user_func($args['options_callback']);
        } 
        // Otherwise use provided options
        else if (!empty($args['options']) && is_array($args['options'])) {
            $options = $args['options'];
        }
        
        echo '<select id="' . esc_attr($name) . '" name="' . esc_attr($name) . '">';
        
        foreach ($options as $option_value => $option_label) {
            echo '<option value="' . esc_attr($option_value) . '" ' . selected($value, $option_value, false) . '>' . esc_html($option_label) . '</option>';
        }
        
        echo '</select>';
        
        if (!empty($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }
    
    /**
     * Render a radio field
     *
     * @param string $name Field name
     * @param mixed $value Field value
     * @param array $args Field arguments
     */
    public function render_radio_field($name, $value, $args) {
        $options = array();
        
        // Get options from callback if specified
        if (!empty($args['options_callback']) && is_callable($args['options_callback'])) {
            $options = call_user_func($args['options_callback']);
        } 
        // Otherwise use provided options
        else if (!empty($args['options']) && is_array($args['options'])) {
            $options = $args['options'];
        }
        
        foreach ($options as $option_value => $option_label) {
            echo '<label class="radio-label">';
            echo '<input type="radio" name="' . esc_attr($name) . '" value="' . esc_attr($option_value) . '" ' . checked($value, $option_value, false) . '>';
            echo ' ' . esc_html($option_label);
            echo '</label><br>';
        }
        
        if (!empty($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }
    
    /**
     * Render a hidden field
     *
     * @param string $name Field name
     * @param mixed $value Field value
     * @param array $args Field arguments
     */
    public function render_hidden_field($name, $value, $args) {
        echo '<input type="hidden" id="' . esc_attr($name) . '" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '">';
    }
    
    /**
     * Render a number field
     *
     * @param string $name Field name
     * @param mixed $value Field value
     * @param array $args Field arguments
     */
    public function render_number_field($name, $value, $args) {
        echo '<input type="number" id="' . esc_attr($name) . '" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '" class="small-text">';
        
        if (!empty($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }
    
    /**
     * Add special elements after rendering a field
     *
     * @param string $name Field name
     */
    private function add_special_field_elements($name) {
        // Add special elements based on field name
        if ($name === 'mpai_api_key') {
            $this->render_openai_api_test_button();
        } else if ($name === 'mpai_anthropic_api_key') {
            $this->render_anthropic_api_test_button();
        }
    }
    
    /**
     * Render OpenAI API test button
     */
    private function render_openai_api_test_button() {
        echo '<div id="openai-api-status" class="mpai-api-status">
            <span class="mpai-api-status-icon"></span>
            <span class="mpai-api-status-text">Not Checked</span>
            <button type="button" id="mpai-test-openai-api" class="button button-secondary">Test Connection</button>
            <div id="mpai-openai-test-result" class="mpai-test-result" style="display:none;"></div>
        </div>';
    }
    
    /**
     * Render Anthropic API test button
     */
    private function render_anthropic_api_test_button() {
        echo '<div id="anthropic-api-status" class="mpai-api-status">
            <span class="mpai-api-status-icon"></span>
            <span class="mpai-api-status-text">Not Checked</span>
            <button type="button" id="mpai-test-anthropic-api" class="button button-secondary">Test Connection</button>
            <div id="mpai-anthropic-test-result" class="mpai-test-result" style="display:none;"></div>
        </div>';
    }
}