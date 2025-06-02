<?php
/**
 * Settings View Service
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Services\Settings;

use MemberpressAiAssistant\Abstracts\AbstractService;
use MemberpressAiAssistant\Interfaces\ServiceInterface;
use MemberpressAiAssistant\DI\ServiceLocator;
use MemberpressAiAssistant\Admin\Settings\MPAISettingsModel;

/**
 * Service for rendering MemberPress AI Assistant settings UI components
 * 
 * This class handles the rendering of the settings page, tabs, and form fields
 * for the MemberPress AI Assistant plugin. It receives all data through method
 * parameters and contains no data fetching or business logic.
 * 
 * It adapts the original MPAISettingsView to work with the DI system.
 */
class SettingsViewService extends AbstractService implements ServiceInterface {
    /**
     * Constructor
     *
     * @param string $name Service name
     * @param mixed $logger Logger instance
     */
    public function __construct(string $name = 'settings.view', $logger = null) {
        parent::__construct($name, $logger);
        
        // Set dependencies
        $this->dependencies = ['logger'];
    }

    /**
     * Register the service with the service locator
     *
     * @param ServiceLocator $serviceLocator The service locator
     * @return void
     */
    public function register($serviceLocator): void {
        $this->log('Registering settings view service');
        
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
        
        // The view service is mostly passive, so minimal boot implementation
        // Add any hooks or filters needed
        $this->addHooks();
    }

    /**
     * Add hooks and filters for this service
     *
     * @return void
     */
    protected function addHooks(): void {
        // No hooks needed for the view service as it's mostly passive
        // The controller will call the view methods directly
    }

    /**
     * Render the settings page
     *
     * @param string $current_tab Current tab
     * @param array $tabs Available tabs
     * @param string $page_slug Page slug
     * @param MPAISettingsModel|SettingsModelService $model Settings model
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
     * @param MPAISettingsModel|SettingsModelService $model Settings model
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
     * @param MPAISettingsModel|SettingsModelService $model Settings model
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
        echo '<p>' . esc_html__('Configure API settings for AI providers. API keys are stored securely using Split Key Storage.', 'memberpress-ai-assistant') . '</p>';
    }
    
    /**
     * Render the consent section description
     *
     * @return void
     */
    public function render_consent_section() {
        echo '<p>' . esc_html__('Configure consent settings for the MemberPress AI Assistant. These settings control how user consent is managed.', 'memberpress-ai-assistant') . '</p>';
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
     * Render the OpenAI API Key field
     *
     * @param string $value Field value
     * @return void
     */
    public function render_openai_api_key_field($value) {
        ?>
        <input type="password" id="mpai_openai_api_key" name="mpai_settings[openai_api_key]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php esc_html_e('Enter your OpenAI API key. This will be stored securely using Split Key Storage.', 'memberpress-ai-assistant'); ?></p>
        
        <div id="openai-api-status" class="mpai-api-status">
            <span class="mpai-api-status-icon"></span>
            <span class="mpai-api-status-text"><?php esc_html_e('Not Checked', 'memberpress-ai-assistant'); ?></span>
            <button type="button" id="mpai-test-openai-api" class="button button-secondary"><?php esc_html_e('Test Connection', 'memberpress-ai-assistant'); ?></button>
            <div id="mpai-openai-test-result" class="mpai-test-result" style="display:none;"></div>
        </div>
        <?php
    }
    
    /**
     * Render the Anthropic API Key field
     *
     * @param string $value Field value
     * @return void
     */
    public function render_anthropic_api_key_field($value) {
        ?>
        <input type="password" id="mpai_anthropic_api_key" name="mpai_settings[anthropic_api_key]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php esc_html_e('Enter your Anthropic API key. This will be stored securely using Split Key Storage.', 'memberpress-ai-assistant'); ?></p>
        
        <div id="anthropic-api-status" class="mpai-api-status">
            <span class="mpai-api-status-icon"></span>
            <span class="mpai-api-status-text"><?php esc_html_e('Not Checked', 'memberpress-ai-assistant'); ?></span>
            <button type="button" id="mpai-test-anthropic-api" class="button button-secondary"><?php esc_html_e('Test Connection', 'memberpress-ai-assistant'); ?></button>
            <div id="mpai-anthropic-test-result" class="mpai-test-result" style="display:none;"></div>
        </div>
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
     * Render the consent required field
     *
     * @param bool $value Field value
     * @return void
     */
    public function render_consent_required_field($value) {
        ?>
        <label for="mpai_consent_required">
            <input type="checkbox" id="mpai_consent_required" name="mpai_settings[consent_required]" value="1" <?php checked($value, true); ?> />
            <?php esc_html_e('Require users to consent before using the AI Assistant', 'memberpress-ai-assistant'); ?>
        </label>
        <p class="description">
            <?php esc_html_e('When enabled, users will be required to agree to the terms before using the AI Assistant.', 'memberpress-ai-assistant'); ?>
        </p>
        <?php
    }
    
    /**
     * Render the consent form preview field
     *
     * @return void
     */
    public function render_consent_form_preview_field() {
        ?>
        <div class="mpai-consent-preview">
            <p><?php esc_html_e('This is a preview of the consent form that users will see:', 'memberpress-ai-assistant'); ?></p>
            <div class="mpai-consent-preview-frame">
                <iframe src="<?php echo esc_url(admin_url('admin.php?page=mpai-consent-preview')); ?>" width="100%" height="400" style="border: 1px solid #ddd; background: #fff;"></iframe>
            </div>
            <p class="description">
                <?php esc_html_e('The consent form template can be customized by adding a filter to the "mpai_consent_form_template" hook.', 'memberpress-ai-assistant'); ?>
            </p>
        </div>
        <?php
    }
    
    /**
     * Render the reset all consents field
     *
     * @return void
     */
    public function render_reset_all_consents_field() {
        $reset_url = wp_nonce_url(
            add_query_arg(
                [
                    'page' => 'mpai-settings',
                    'tab' => 'consent',
                    'action' => 'mpai_reset_all_consents',
                ],
                admin_url('admin.php')
            ),
            'mpai_reset_all_consents_nonce'
        );
        ?>
        <div class="mpai-reset-consents">
            <p><?php esc_html_e('This will reset consent status for all users. They will need to agree to the terms again before using the AI Assistant.', 'memberpress-ai-assistant'); ?></p>
            <a href="<?php echo esc_url($reset_url); ?>" class="button button-secondary mpai-reset-consents-button" onclick="return confirm('<?php esc_attr_e('Are you sure you want to reset consent for all users? This action cannot be undone.', 'memberpress-ai-assistant'); ?>');">
                <?php esc_html_e('Reset All User Consents', 'memberpress-ai-assistant'); ?>
            </a>
        </div>
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