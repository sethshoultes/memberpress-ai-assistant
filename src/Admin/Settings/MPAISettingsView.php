<?php
/**
 * MemberPress AI Assistant Settings View
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Admin\Settings;

/**
 * Class for rendering MemberPress AI Assistant settings UI components
 * 
 * This class handles the rendering of the settings page, tabs, and form fields
 * for the MemberPress AI Assistant plugin. It receives all data through method
 * parameters and contains no data fetching or business logic.
 */
class MPAISettingsView {
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
    }
    
    /**
     * Render the settings page
     *
     * @param string $current_tab Current tab
     * @param array $tabs Available tabs
     * @param string $page_slug Page slug
     * @param MPAISettingsModel $model Settings model
     * @return void
     */
    public function render_page($current_tab, $tabs, $page_slug, $model) {
        try {
            // Check for required variables
            if (empty($tabs)) {
                $this->render_error(__('Error: Required template variables are missing.', 'memberpress-ai-assistant'));
                return;
            }
            
            // Ensure the tab is valid
            if (!isset($tabs[$current_tab])) {
                $current_tab = 'general';
            }
            
            // Start output
            echo '<div class="wrap">';
            echo '<h1>' . esc_html__('MemberPress AI Assistant Settings', 'memberpress-ai-assistant') . '</h1>';
            
            // Display settings updated message if needed
            if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
                echo '<div class="notice notice-success is-dismissible"><p>' .
                    esc_html__('Settings saved successfully.', 'memberpress-ai-assistant') .
                    '</p></div>';
            }
            
            // Render tabs
            $this->render_tabs($current_tab, $tabs);
            
            // Render form
            $this->render_form($current_tab, $page_slug, $model);
            
            echo '</div>';
        } catch (\Exception $e) {
            $this->log_error('Error rendering settings page: ' . $e->getMessage());
            $this->render_error(__('An error occurred while rendering the settings page. Please try again later or contact support.', 'memberpress-ai-assistant'));
        }
    }
    
    /**
     * Render the settings tabs
     *
     * @param string $current_tab Current tab
     * @param array $tabs Available tabs
     * @return void
     */
    public function render_tabs($current_tab, $tabs) {
        echo '<h2 class="nav-tab-wrapper">';
        
        foreach ($tabs as $tab_id => $tab_name) {
            $active = ($current_tab === $tab_id) ? 'nav-tab-active' : '';
            $url = add_query_arg([
                'page' => 'mpai-settings',
                'tab' => $tab_id,
            ], admin_url('admin.php'));
            
            echo '<a href="' . esc_url($url) . '" class="nav-tab ' . esc_attr($active) . '">' .
                esc_html($tab_name) . '</a>';
        }
        
        echo '</h2>';
    }
    
    /**
     * Render the settings form
     *
     * @param string $current_tab Current tab
     * @param string $page_slug Page slug
     * @param MPAISettingsModel $model Settings model
     * @return void
     */
    public function render_form($current_tab, $page_slug, $model) {
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        
        // Add hidden fields
        echo '<input type="hidden" name="action" value="mpai_update_settings" />';
        echo '<input type="hidden" name="tab" value="' . esc_attr($current_tab) . '" />';
        
        // Add WordPress nonce field
        wp_nonce_field($page_slug . '-options');
        
        // Render settings fields for the current tab
        $this->render_fields($current_tab, $page_slug, $model);
        
        // Render submit button
        $this->render_submit_button();
        
        echo '</form>';
    }
    
    /**
     * Render the settings fields for the current tab
     *
     * @param string $current_tab Current tab
     * @param string $page_slug Page slug
     * @param MPAISettingsModel $model Settings model
     * @return void
     */
    public function render_fields($current_tab, $page_slug, $model) {
        echo '<table class="form-table" role="presentation">';
        
        // Output section and fields based on current tab
        switch ($current_tab) {
            case 'general':
                do_settings_sections($page_slug);
                break;
                
            case 'api':
                $this->render_section('mpai_api_section', $page_slug);
                break;
                
            case 'chat':
                $this->render_section('mpai_chat_section', $page_slug);
                break;
                
            case 'access':
                $this->render_section('mpai_access_section', $page_slug);
                break;
                
            case 'consent':
                $this->render_section('mpai_consent_section', $page_slug);
                break;
                
            default:
                do_settings_sections($page_slug);
                break;
        }
        
        echo '</table>';
    }
    
    /**
     * Render a specific settings section
     *
     * @param string $section_id Section ID
     * @param string $page_slug Page slug
     * @return void
     */
    public function render_section($section_id, $page_slug) {
        global $wp_settings_sections, $wp_settings_fields;
        
        if (!isset($wp_settings_sections[$page_slug][$section_id])) {
            return;
        }
        
        $section = $wp_settings_sections[$page_slug][$section_id];
        
        // Output section header
        if ($section['title']) {
            echo '<h2>' . esc_html($section['title']) . '</h2>';
        }
        
        // Output section description
        if ($section['callback']) {
            call_user_func($section['callback']);
        }
        
        // Output section fields
        if (isset($wp_settings_fields[$page_slug][$section_id])) {
            do_settings_fields($page_slug, $section_id);
        }
    }
    
    /**
     * Render the form submit button
     *
     * @return void
     */
    public function render_submit_button() {
        echo '<p class="submit">';
        echo '<input type="submit" name="submit" id="submit" class="button button-primary" value="' . 
            esc_attr__('Save Changes', 'memberpress-ai-assistant') . '" />';
        echo '</p>';
    }
    
    /**
     * Render an error message
     *
     * @param string $message Error message
     * @return void
     */
    public function render_error($message) {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('MemberPress AI Assistant Settings', 'memberpress-ai-assistant') . '</h1>';
        echo '<div class="notice notice-error"><p>' . esc_html($message) . '</p></div>';
        echo '</div>';
    }
    
    /**
     * Render the general section description
     *
     * @return void
     */
    public function render_general_section() {
        echo '<p>' . esc_html__('Configure general settings for the MemberPress AI Assistant.', 'memberpress-ai-assistant') . '</p>';
    }
    
    /**
     * Render the chat section description
     *
     * @return void
     */
    public function render_chat_section() {
        echo '<p>' . esc_html__('Configure how the chat interface appears and behaves.', 'memberpress-ai-assistant') . '</p>';
    }
    
    /**
     * Render the access section description
     *
     * @return void
     */
    public function render_access_section() {
        echo '<p>' . esc_html__('Control which user roles can access the AI Assistant chat interface.', 'memberpress-ai-assistant') . '</p>';
    }
    
    /**
     * Render the API section description
     *
     * @return void
     */
    public function render_api_section() {
        echo '<p>' . esc_html__('Configure API settings for AI providers. Enter your API keys and select the models to use.', 'memberpress-ai-assistant') . '</p>';
    }
    
    /**
     * Render the OpenAI API key field
     *
     * @param string $value Field value
     * @return void
     */
    public function render_openai_api_key_field($value) {
        ?>
        <div class="mpai-api-key-field">
            <input type="password" id="mpai_openai_api_key" name="mpai_settings[openai_api_key]" value="<?php echo esc_attr($value); ?>" class="regular-text code">
            <button type="button" class="button button-secondary mpai-test-connection" data-provider="openai">
                <?php esc_html_e('Test Connection', 'memberpress-ai-assistant'); ?>
            </button>
            <span class="mpai-test-result" id="mpai-openai-test-result"></span>
        </div>
        <p class="description">
            <?php esc_html_e('Enter your OpenAI API key. This will be stored securely in your WordPress database.', 'memberpress-ai-assistant'); ?>
        </p>
        <?php
    }
    
    /**
     * Render the Anthropic API key field
     *
     * @param string $value Field value
     * @return void
     */
    public function render_anthropic_api_key_field($value) {
        ?>
        <div class="mpai-api-key-field">
            <input type="password" id="mpai_anthropic_api_key" name="mpai_settings[anthropic_api_key]" value="<?php echo esc_attr($value); ?>" class="regular-text code">
            <button type="button" class="button button-secondary mpai-test-connection" data-provider="anthropic">
                <?php esc_html_e('Test Connection', 'memberpress-ai-assistant'); ?>
            </button>
            <span class="mpai-test-result" id="mpai-anthropic-test-result"></span>
        </div>
        <p class="description">
            <?php esc_html_e('Enter your Anthropic API key. This will be stored securely in your WordPress database.', 'memberpress-ai-assistant'); ?>
        </p>
        <?php
    }
    
    
    /**
     * Render the chat enabled field
     *
     * @param bool $value Field value
     * @return void
     */
    public function render_chat_enabled_field($value) {
        ?>
        <label for="mpai_chat_enabled">
            <input type="checkbox" id="mpai_chat_enabled" name="mpai_settings[chat_enabled]" value="1" <?php checked($value, true); ?> />
            <?php esc_html_e('Enable the AI Assistant chat interface', 'memberpress-ai-assistant'); ?>
        </label>
        <p class="description">
            <?php esc_html_e('When enabled, the chat interface will be available based on the location settings below.', 'memberpress-ai-assistant'); ?>
        </p>
        <?php
    }
    
    /**
     * Render the log level field
     *
     * @param string $value Field value
     * @return void
     */
    public function render_log_level_field($value) {
        $log_levels = [
            'none' => __('None (Disable All Logging)', 'memberpress-ai-assistant'),
            'error' => __('Error (Minimal)', 'memberpress-ai-assistant'),
            'warning' => __('Warning', 'memberpress-ai-assistant'),
            'info' => __('Info (Recommended)', 'memberpress-ai-assistant'),
            'debug' => __('Debug', 'memberpress-ai-assistant'),
            'trace' => __('Trace (Verbose)', 'memberpress-ai-assistant'),
        ];
        ?>
        <select id="mpai_log_level" name="mpai_settings[log_level]">
            <?php foreach ($log_levels as $level => $label) : ?>
                <option value="<?php echo esc_attr($level); ?>" <?php selected($value, $level); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">
            <?php esc_html_e('Select the logging level. Higher levels include more detailed logs but may impact performance.', 'memberpress-ai-assistant'); ?>
            <ul>
                <li><?php esc_html_e('None: Completely disable all logging', 'memberpress-ai-assistant'); ?></li>
                <li><?php esc_html_e('Error: Only critical errors', 'memberpress-ai-assistant'); ?></li>
                <li><?php esc_html_e('Warning: Errors and warnings', 'memberpress-ai-assistant'); ?></li>
                <li><?php esc_html_e('Info: Normal operational information', 'memberpress-ai-assistant'); ?></li>
                <li><?php esc_html_e('Debug: Detailed information for troubleshooting', 'memberpress-ai-assistant'); ?></li>
                <li><?php esc_html_e('Trace: Very verbose debugging information', 'memberpress-ai-assistant'); ?></li>
            </ul>
        </p>
        <?php
    }
    
    /**
     * Render the chat location field
     *
     * @param string $value Field value
     * @return void
     */
    public function render_chat_location_field($value) {
        ?>
        <select id="mpai_chat_location" name="mpai_settings[chat_location]">
            <option value="admin_only" <?php selected($value, 'admin_only'); ?>>
                <?php esc_html_e('Admin Area Only', 'memberpress-ai-assistant'); ?>
            </option>
            <option value="frontend" <?php selected($value, 'frontend'); ?>>
                <?php esc_html_e('Frontend Only', 'memberpress-ai-assistant'); ?>
            </option>
            <option value="both" <?php selected($value, 'both'); ?>>
                <?php esc_html_e('Both Admin and Frontend', 'memberpress-ai-assistant'); ?>
            </option>
        </select>
        <p class="description">
            <?php esc_html_e('Choose where the chat interface should be available.', 'memberpress-ai-assistant'); ?>
        </p>
        <?php
    }
    
    /**
     * Render the chat position field
     *
     * @param string $value Field value
     * @return void
     */
    public function render_chat_position_field($value) {
        ?>
        <select id="mpai_chat_position" name="mpai_settings[chat_position]">
            <option value="bottom_right" <?php selected($value, 'bottom_right'); ?>>
                <?php esc_html_e('Bottom Right', 'memberpress-ai-assistant'); ?>
            </option>
            <option value="bottom_left" <?php selected($value, 'bottom_left'); ?>>
                <?php esc_html_e('Bottom Left', 'memberpress-ai-assistant'); ?>
            </option>
            <option value="top_right" <?php selected($value, 'top_right'); ?>>
                <?php esc_html_e('Top Right', 'memberpress-ai-assistant'); ?>
            </option>
            <option value="top_left" <?php selected($value, 'top_left'); ?>>
                <?php esc_html_e('Top Left', 'memberpress-ai-assistant'); ?>
            </option>
        </select>
        <p class="description">
            <?php esc_html_e('Choose the position of the chat interface on the screen.', 'memberpress-ai-assistant'); ?>
        </p>
        <?php
    }
    
    /**
     * Render the user roles field
     *
     * @param array $value Field value
     * @return void
     */
    public function render_user_roles_field($value) {
        $wp_roles = wp_roles();
        $roles = $wp_roles->get_names();
        ?>
        <fieldset>
            <legend class="screen-reader-text">
                <?php esc_html_e('User Roles with Access', 'memberpress-ai-assistant'); ?>
            </legend>
            <?php foreach ($roles as $role_slug => $role_name) : ?>
                <label for="mpai_user_role_<?php echo esc_attr($role_slug); ?>">
                    <input type="checkbox" 
                           id="mpai_user_role_<?php echo esc_attr($role_slug); ?>" 
                           name="mpai_settings[user_roles][]" 
                           value="<?php echo esc_attr($role_slug); ?>" 
                           <?php checked(in_array($role_slug, $value), true); ?> />
                    <?php echo esc_html($role_name); ?>
                </label><br>
            <?php endforeach; ?>
        </fieldset>
        <p class="description">
            <?php esc_html_e('Select which user roles can access the AI Assistant chat interface.', 'memberpress-ai-assistant'); ?>
        </p>
        <?php
    }
    
    
    /**
     * Render the Primary API Provider field
     *
     * @param string $value Field value
     * @return void
     */
    public function render_primary_api_field($value) {
        $providers = [
            'openai' => __('OpenAI', 'memberpress-ai-assistant'),
            'anthropic' => __('Anthropic (Claude)', 'memberpress-ai-assistant'),
        ];
        ?>
        <div class="mpai-provider-selection">
            <?php foreach ($providers as $provider_value => $label) : ?>
                <label class="mpai-provider-option">
                    <input type="radio" name="mpai_settings[primary_api]" value="<?php echo esc_attr($provider_value); ?>" <?php checked($value, $provider_value); ?>>
                    <?php echo esc_html($label); ?>
                </label>
            <?php endforeach; ?>
        </div>
        <p class="description"><?php esc_html_e('Select which AI provider to use as the primary source.', 'memberpress-ai-assistant'); ?></p>
        <?php
        
        // Add JavaScript for conditional display logic
        $this->render_provider_selection_js();
    }
    
    /**
     * Render JavaScript for provider selection conditional display logic
     *
     * @return void
     */
    public function render_provider_selection_js() {
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Function to toggle provider-specific settings
            function toggleProviderSettings() {
                var selectedProvider = $('input[name="mpai_settings[primary_api]"]:checked').val();
                
                // Hide all provider-specific settings first
                $('.openai-specific-setting, .anthropic-specific-setting').closest('tr').hide();
                
                // Show settings for the selected provider
                if (selectedProvider === 'openai') {
                    $('.openai-specific-setting').closest('tr').show();
                } else if (selectedProvider === 'anthropic') {
                    $('.anthropic-specific-setting').closest('tr').show();
                }
            }
            
            // Add classes to provider-specific settings
            $('#mpai_openai_api_key').closest('tr').addClass('openai-specific-setting');
            $('#mpai_openai_model').closest('tr').addClass('openai-specific-setting');
            $('#mpai_openai_temperature').closest('tr').addClass('openai-specific-setting');
            $('#mpai_openai_max_tokens').closest('tr').addClass('openai-specific-setting');
            
            $('#mpai_anthropic_api_key').closest('tr').addClass('anthropic-specific-setting');
            $('#mpai_anthropic_model').closest('tr').addClass('anthropic-specific-setting');
            $('#mpai_anthropic_temperature').closest('tr').addClass('anthropic-specific-setting');
            $('#mpai_anthropic_max_tokens').closest('tr').addClass('anthropic-specific-setting');
            
            // Run once on page load
            toggleProviderSettings();
            
            // Run when provider selection changes
            $('input[name="mpai_settings[primary_api]"]').on('change', function() {
                toggleProviderSettings();
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render the OpenAI model field
     *
     * @param string $value Field value
     * @return void
     */
    public function render_openai_model_field($value) {
        $models = [
            'gpt-4o' => __('GPT-4o', 'memberpress-ai-assistant'),
            'gpt-4-turbo' => __('GPT-4 Turbo', 'memberpress-ai-assistant'),
            'gpt-4' => __('GPT-4', 'memberpress-ai-assistant'),
            'gpt-3.5-turbo' => __('GPT-3.5 Turbo', 'memberpress-ai-assistant'),
        ];
        
        ?>
        <select id="mpai_openai_model" name="mpai_settings[openai_model]">
            <?php foreach ($models as $model_value => $label) : ?>
                <option value="<?php echo esc_attr($model_value); ?>" <?php selected($value, $model_value); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">
            <?php esc_html_e('Select the OpenAI model to use for AI operations.', 'memberpress-ai-assistant'); ?>
        </p>
        <?php
    }
    
    /**
     * Render the Anthropic model field
     *
     * @param string $value Field value
     * @return void
     */
    public function render_anthropic_model_field($value) {
        $models = [
            'claude-3-opus-20240229' => __('Claude 3 Opus', 'memberpress-ai-assistant'),
            'claude-3-sonnet-20240229' => __('Claude 3 Sonnet', 'memberpress-ai-assistant'),
            'claude-3-haiku-20240307' => __('Claude 3 Haiku', 'memberpress-ai-assistant'),
            'claude-2.1' => __('Claude 2.1', 'memberpress-ai-assistant'),
            'claude-2.0' => __('Claude 2.0', 'memberpress-ai-assistant'),
        ];
        
        ?>
        <select id="mpai_anthropic_model" name="mpai_settings[anthropic_model]">
            <?php foreach ($models as $model_value => $label) : ?>
                <option value="<?php echo esc_attr($model_value); ?>" <?php selected($value, $model_value); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">
            <?php esc_html_e('Select the Anthropic model to use for AI operations.', 'memberpress-ai-assistant'); ?>
        </p>
        <?php
    }
    
    /**
     * Render the OpenAI temperature field
     *
     * @param float $value Field value
     * @return void
     */
    public function render_openai_temperature_field($value) {
        ?>
        <input type="range" id="mpai_openai_temperature" name="mpai_settings[openai_temperature]"
               min="0" max="1" step="0.1" value="<?php echo esc_attr($value); ?>"
               oninput="document.getElementById('mpai_openai_temperature_value').textContent = this.value;">
        <span id="mpai_openai_temperature_value"><?php echo esc_html($value); ?></span>
        <p class="description">
            <?php esc_html_e('Adjust the temperature for OpenAI responses. Lower values (closer to 0) make responses more focused and deterministic, while higher values (closer to 1) make responses more creative and diverse.', 'memberpress-ai-assistant'); ?>
        </p>
        <?php
    }
    
    /**
     * Render the OpenAI max tokens field
     *
     * @param int $value Field value
     * @return void
     */
    public function render_openai_max_tokens_field($value) {
        ?>
        <input type="number" id="mpai_openai_max_tokens" name="mpai_settings[openai_max_tokens]"
               min="1" max="8192" step="1" value="<?php echo esc_attr($value); ?>" class="small-text">
        <p class="description">
            <?php esc_html_e('Set the maximum number of tokens for OpenAI responses. This limits the length of the generated text.', 'memberpress-ai-assistant'); ?>
        </p>
        <?php
    }
    
    /**
     * Render the Anthropic temperature field
     *
     * @param float $value Field value
     * @return void
     */
    public function render_anthropic_temperature_field($value) {
        ?>
        <input type="range" id="mpai_anthropic_temperature" name="mpai_settings[anthropic_temperature]"
               min="0" max="1" step="0.1" value="<?php echo esc_attr($value); ?>"
               oninput="document.getElementById('mpai_anthropic_temperature_value').textContent = this.value;">
        <span id="mpai_anthropic_temperature_value"><?php echo esc_html($value); ?></span>
        <p class="description">
            <?php esc_html_e('Adjust the temperature for Anthropic responses. Lower values (closer to 0) make responses more focused and deterministic, while higher values (closer to 1) make responses more creative and diverse.', 'memberpress-ai-assistant'); ?>
        </p>
        <?php
    }
    
    /**
     * Render the Anthropic max tokens field
     *
     * @param int $value Field value
     * @return void
     */
    public function render_anthropic_max_tokens_field($value) {
        ?>
        <input type="number" id="mpai_anthropic_max_tokens" name="mpai_settings[anthropic_max_tokens]"
               min="1" max="100000" step="1" value="<?php echo esc_attr($value); ?>" class="small-text">
        <p class="description">
            <?php esc_html_e('Set the maximum number of tokens for Anthropic responses. This limits the length of the generated text.', 'memberpress-ai-assistant'); ?>
        </p>
        <?php
    }
    
    
    /**
     * Log an error message
     *
     * @param string $message Error message
     * @return void
     */
    private function log_error($message) {
        if ($this->logger) {
            $this->logger->error($message);
        }
    }
}