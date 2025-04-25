<?php
/**
 * Settings Class
 *
 * Simple settings handler for MemberPress AI Assistant
 * 
 * @package MemberPress AI Assistant
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

class MPAI_Settings {
    /**
     * Settings definitions
     * 
     * @var array
     */
    private $settings_definitions = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        // Define settings
        $this->define_settings();
        
        // Register settings during admin_init
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Define all settings
     */
    private function define_settings() {
        $settings = array(
            // OpenAI API settings
            'mpai_api_key' => array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '',
                'section' => 'mpai_api_settings',
                'title' => __('OpenAI API Key', 'memberpress-ai-assistant'),
                'callback' => 'render_api_key_field',
            ),
            'mpai_model' => array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'gpt-4o',
                'section' => 'mpai_api_settings',
                'title' => __('OpenAI Model', 'memberpress-ai-assistant'),
                'callback' => 'render_model_field',
            ),
            'mpai_temperature' => array(
                'type' => 'number',
                'sanitize_callback' => array($this, 'sanitize_float'),
                'default' => 0.7,
                'section' => 'mpai_api_settings',
                'title' => __('OpenAI Temperature', 'memberpress-ai-assistant'),
                'callback' => 'render_temperature_field',
            ),
            
            // Anthropic API settings
            'mpai_anthropic_api_key' => array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '',
                'section' => 'mpai_api_settings',
                'title' => __('Anthropic API Key', 'memberpress-ai-assistant'),
                'callback' => 'render_anthropic_api_key_field',
            ),
            'mpai_anthropic_model' => array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'claude-3-opus-20240229',
                'section' => 'mpai_api_settings',
                'title' => __('Anthropic Model', 'memberpress-ai-assistant'),
                'callback' => 'render_anthropic_model_field',
            ),
            'mpai_anthropic_temperature' => array(
                'type' => 'number',
                'sanitize_callback' => array($this, 'sanitize_float'),
                'default' => 0.7,
                'section' => 'mpai_api_settings',
                'title' => __('Anthropic Temperature', 'memberpress-ai-assistant'),
                'callback' => 'render_anthropic_temperature_field',
            ),
            
            // Provider Selection
            'mpai_primary_api' => array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'openai',
                'section' => 'mpai_api_settings',
                'title' => __('Primary AI Provider', 'memberpress-ai-assistant'),
                'callback' => 'render_primary_api_field',
            ),
            
            // Chat Interface settings
            'mpai_chat_position' => array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'bottom-right',
                'section' => 'mpai_chat_settings',
                'title' => __('Chat Position', 'memberpress-ai-assistant'),
                'callback' => 'render_chat_position_field',
            ),
            'mpai_welcome_message' => array(
                'type' => 'string',
                'sanitize_callback' => 'wp_kses_post',
                'default' => __('Hi there! I\'m your MemberPress AI Assistant. How can I help you today?', 'memberpress-ai-assistant'),
                'section' => 'mpai_chat_settings',
                'title' => __('Welcome Message', 'memberpress-ai-assistant'),
                'callback' => 'render_welcome_message_field',
            ),
            
            // Debug settings
            'mpai_enable_console_logging' => array(
                'type' => 'boolean',
                'sanitize_callback' => 'boolval',
                'default' => false,
                'section' => 'mpai_debug_settings',
                'title' => __('Enable Console Logging', 'memberpress-ai-assistant'),
                'callback' => 'render_enable_console_logging_field',
            ),
            'mpai_console_log_level' => array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'info',
                'section' => 'mpai_debug_settings',
                'title' => __('Console Log Level', 'memberpress-ai-assistant'),
                'callback' => 'render_console_log_level_field',
            ),
        );
        
        // Filter settings fields
        $this->settings_definitions = apply_filters('MPAI_HOOK_FILTER_settings_fields', $settings);
    }
    
    /**
     * Get settings definitions
     * 
     * @return array
     */
    public function get_settings_definitions() {
        return $this->settings_definitions;
    }

    /**
     * Register all settings
     */
    public function register_settings() {
        // Register all settings
        foreach ($this->settings_definitions as $option_name => $args) {
            register_setting('mpai_settings', $option_name, array(
                'type' => $args['type'],
                'sanitize_callback' => $args['sanitize_callback'],
                'default' => $args['default'],
            ));
        }
        
        // Define settings tabs/sections
        $sections = [
            'mpai_api_settings' => [
                'title' => __('AI API Settings', 'memberpress-ai-assistant'),
                'callback' => array($this, 'render_api_section_description')
            ],
            'mpai_chat_settings' => [
                'title' => __('Chat Interface Settings', 'memberpress-ai-assistant'),
                'callback' => array($this, 'render_chat_section_description')
            ],
            'mpai_debug_settings' => [
                'title' => __('Debug Settings', 'memberpress-ai-assistant'),
                'callback' => array($this, 'render_debug_section_description')
            ]
        ];
        
        // Filter settings tabs
        $filtered_sections = apply_filters('MPAI_HOOK_FILTER_settings_tabs', $sections);
        
        // Add Settings Sections
        foreach ($filtered_sections as $id => $section) {
            add_settings_section(
                $id,
                $section['title'],
                $section['callback'],
                'mpai_settings'
            );
        }
        
        // Add all settings fields
        foreach ($this->settings_definitions as $option_name => $args) {
            add_settings_field(
                $option_name,
                $args['title'],
                array($this, $args['callback']),
                'mpai_settings',
                $args['section']
            );
        }
    }
    
    // Section descriptions
    
    public function render_api_section_description() {
        echo '<p>' . __('Configure the AI APIs used by MemberPress AI Assistant.', 'memberpress-ai-assistant') . '</p>';
    }
    
    public function render_chat_section_description() {
        echo '<p>' . __('Configure how the chat interface appears and behaves.', 'memberpress-ai-assistant') . '</p>';
    }
    
    public function render_debug_section_description() {
        echo '<p>' . __('Settings for debugging and troubleshooting.', 'memberpress-ai-assistant') . '</p>';
    }
    
    // Field renderers
    
    public function render_api_key_field() {
        $api_key = get_option('mpai_api_key', '');
        echo '<input type="password" id="mpai_api_key" name="mpai_api_key" value="' . esc_attr($api_key) . '" class="regular-text">';
        echo '<p class="description">' . __('Enter your OpenAI API key.', 'memberpress-ai-assistant') . '</p>';
        
        // Add test connection button with inline fix for result display
        echo '<div id="openai-api-status" class="mpai-api-status">
            <span class="mpai-api-status-icon"></span>
            <span class="mpai-api-status-text">Not Checked</span>
            <button type="button" id="mpai-test-openai-api" class="button button-secondary">Test Connection</button>
            <div id="mpai-openai-test-result" class="mpai-test-result" style="display:none;"></div>
        </div>
        <script>
        // Fix for OpenAI test button result display
        jQuery(document).ready(function($) {
            $("#mpai-test-openai-api").on("click", function() {
                // Show the result container
                $("#mpai-openai-test-result").show(); 
                
                // Make sure it has a min-height so it\'s visible while loading
                $("#mpai-openai-test-result").css("min-height", "20px");
            });
        });
        </script>';
    }
    
    public function render_model_field() {
        $model = get_option('mpai_model', 'gpt-4o');
        $models = array(
            'gpt-4o' => 'GPT-4o',
            'gpt-4-turbo' => 'GPT-4 Turbo',
            'gpt-4' => 'GPT-4',
            'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
        );
        
        echo '<select id="mpai_model" name="mpai_model">';
        foreach ($models as $value => $label) {
            echo '<option value="' . esc_attr($value) . '" ' . selected($model, $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __('Select the OpenAI model to use.', 'memberpress-ai-assistant') . '</p>';
    }
    
    public function render_temperature_field() {
        $temperature = get_option('mpai_temperature', 0.7);
        echo '<input type="text" id="mpai_temperature" name="mpai_temperature" value="' . esc_attr($temperature) . '" class="small-text">';
        echo '<p class="description">' . __('Controls randomness: 0.0 is deterministic, 1.0 is very random.', 'memberpress-ai-assistant') . '</p>';
    }
    
    public function render_anthropic_api_key_field() {
        $api_key = get_option('mpai_anthropic_api_key', '');
        echo '<input type="password" id="mpai_anthropic_api_key" name="mpai_anthropic_api_key" value="' . esc_attr($api_key) . '" class="regular-text">';
        echo '<p class="description">' . __('Enter your Anthropic API key.', 'memberpress-ai-assistant') . '</p>';
        
        // Add test connection button
        echo '<div id="anthropic-api-status" class="mpai-api-status">
            <span class="mpai-api-status-icon"></span>
            <span class="mpai-api-status-text">Not Checked</span>
            <button type="button" id="mpai-test-anthropic-api" class="button button-secondary">Test Connection</button>
            <div id="mpai-anthropic-test-result" class="mpai-test-result" style="display:none;"></div>
        </div>';
    }
    
    public function render_anthropic_model_field() {
        $model = get_option('mpai_anthropic_model', 'claude-3-opus-20240229');
        $models = array(
            'claude-3-opus-20240229' => 'Claude 3 Opus',
            'claude-3-sonnet-20240229' => 'Claude 3 Sonnet',
            'claude-3-haiku-20240307' => 'Claude 3 Haiku',
            'claude-2.1' => 'Claude 2.1',
            'claude-2.0' => 'Claude 2.0',
        );
        
        echo '<select id="mpai_anthropic_model" name="mpai_anthropic_model">';
        foreach ($models as $value => $label) {
            echo '<option value="' . esc_attr($value) . '" ' . selected($model, $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __('Select the Anthropic model to use.', 'memberpress-ai-assistant') . '</p>';
    }
    
    public function render_anthropic_temperature_field() {
        $temperature = get_option('mpai_anthropic_temperature', 0.7);
        echo '<input type="text" id="mpai_anthropic_temperature" name="mpai_anthropic_temperature" value="' . esc_attr($temperature) . '" class="small-text">';
        echo '<p class="description">' . __('Controls randomness: 0.0 is deterministic, 1.0 is very random.', 'memberpress-ai-assistant') . '</p>';
    }
    
    public function render_primary_api_field() {
        $primary_api = get_option('mpai_primary_api', 'openai');
        $providers = array(
            'openai' => 'OpenAI',
            'anthropic' => 'Anthropic (Claude)',
        );
        
        echo '<div class="mpai-provider-selection">';
        foreach ($providers as $value => $label) {
            echo '<label class="mpai-provider-option"><input type="radio" name="mpai_primary_api" value="' . esc_attr($value) . '" ' . checked($primary_api, $value, false) . '> ' . esc_html($label) . '</label>';
        }
        echo '</div>';
        
        echo '<p class="description">' . __('Select which AI provider to use as the primary source.', 'memberpress-ai-assistant') . '</p>';
    }
    
    public function render_chat_position_field() {
        $position = get_option('mpai_chat_position', 'bottom-right');
        $positions = array(
            'bottom-right' => __('Bottom Right', 'memberpress-ai-assistant'),
            'bottom-left' => __('Bottom Left', 'memberpress-ai-assistant'),
            'top-right' => __('Top Right', 'memberpress-ai-assistant'),
            'top-left' => __('Top Left', 'memberpress-ai-assistant'),
        );
        
        echo '<select id="mpai_chat_position" name="mpai_chat_position">';
        foreach ($positions as $value => $label) {
            echo '<option value="' . esc_attr($value) . '" ' . selected($position, $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __('Select where the chat interface should appear.', 'memberpress-ai-assistant') . '</p>';
    }
    
    public function render_welcome_message_field() {
        $welcome_message = get_option('mpai_welcome_message', __('Hi there! I\'m your MemberPress AI Assistant. How can I help you today?', 'memberpress-ai-assistant'));
        echo '<textarea id="mpai_welcome_message" name="mpai_welcome_message" rows="3" class="large-text">' . esc_textarea($welcome_message) . '</textarea>';
        echo '<p class="description">' . __('The message displayed when the chat is first opened.', 'memberpress-ai-assistant') . '</p>';
    }
    
    public function render_enable_console_logging_field() {
        $enabled = get_option('mpai_enable_console_logging', false);
        echo '<label><input type="checkbox" id="mpai_enable_console_logging" name="mpai_enable_console_logging" value="1" ' . checked($enabled, true, false) . '> ' . __('Enable detailed logging to browser console', 'memberpress-ai-assistant') . '</label>';
        
        // Add status indicator
        echo '<div class="mpai-debug-control">
            <span id="mpai-console-logging-status" class="mpai-status-badge ' . ($enabled ? 'active' : 'inactive') . '">' . ($enabled ? 'ENABLED' : 'DISABLED') . '</span>
            <button type="button" id="mpai-test-console-logging" class="button button-secondary">Test Console Logging</button>
            <div id="mpai-console-test-result" class="mpai-test-result" style="display:none;"></div>
        </div>';
    }
    
    public function render_console_log_level_field() {
        $log_level = get_option('mpai_console_log_level', 'info');
        $levels = array(
            'error' => __('Error (Minimal)', 'memberpress-ai-assistant'),
            'warn' => __('Warning', 'memberpress-ai-assistant'),
            'info' => __('Info (Recommended)', 'memberpress-ai-assistant'),
            'debug' => __('Debug (Verbose)', 'memberpress-ai-assistant'),
        );
        
        echo '<select id="mpai_console_log_level" name="mpai_console_log_level">';
        foreach ($levels as $value => $label) {
            echo '<option value="' . esc_attr($value) . '" ' . selected($log_level, $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __('Select the level of detail for console logs.', 'memberpress-ai-assistant') . '</p>';
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
}