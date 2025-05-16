<?php
/**
 * MemberPress AI Assistant Settings Renderer
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Admin;

use MemberpressAiAssistant\Abstracts\AbstractService;
use MemberpressAiAssistant\Interfaces\SettingsProviderInterface;
use MemberpressAiAssistant\Interfaces\SettingsRendererInterface;
use MemberpressAiAssistant\Interfaces\SettingsCoordinatorInterface;

/**
 * Class for rendering MemberPress AI Assistant settings page and form fields
 *
 * This class handles the rendering of the settings page, tabs, and form fields
 * for the MemberPress AI Assistant plugin.
 */
class MPAISettingsRenderer extends AbstractService implements SettingsRendererInterface {
    /**
     * Settings storage instance
     *
     * @var MPAISettingsStorage
     */
    protected $settings_storage;

    /**
     * Settings coordinator instance
     *
     * @var SettingsCoordinatorInterface
     */
    protected $settings_coordinator;

    /**
     * Constructor
     *
     * @param string $name Service name
     * @param mixed $logger Logger instance
     */
    public function __construct(string $name = 'settings_renderer', $logger = null) {
        parent::__construct($name, $logger);
    }

    /**
     * {@inheritdoc}
     */
    public function register($container): void {
        // Register this service with the container
        $container->singleton('settings_renderer', function() {
            return $this;
        });

        // Add dependencies to the dependencies array
        $this->dependencies = [
            'settings_coordinator',
        ];

        // Log registration
        $this->log('Settings renderer service registered');
    }

    /**
     * Set dependencies after they've been registered
     *
     * @param SettingsCoordinatorInterface $settings_coordinator The settings coordinator service
     * @return void
     */
    public function set_dependencies(SettingsCoordinatorInterface $settings_coordinator): void {
        $this->settings_coordinator = $settings_coordinator;
        // Get the storage from the coordinator
        $this->settings_storage = $settings_coordinator->getStorage();
    }

    /**
     * {@inheritdoc}
     */
    public function boot(): void {
        parent::boot();
        
        // Add hooks
        $this->addHooks();
        
        // Log boot
        $this->log('Settings renderer service booted');
    }
    
    /**
     * Get the settings provider from the coordinator
     *
     * @return SettingsProviderInterface|null
     */
    protected function getSettingsProvider(): ?SettingsProviderInterface {
        if (!$this->settings_coordinator) {
            $this->log('Warning: Settings coordinator not available when trying to get provider', ['level' => 'warning']);
            return null;
        }
        
        $provider = $this->settings_coordinator->getController();
        
        if (!$provider) {
            $this->log('Warning: Settings provider not available from coordinator', ['level' => 'warning']);
        }
        
        return $provider;
    }

    /**
     * Add hooks and filters for this service
     *
     * @return void
     */
    protected function addHooks(): void {
        // Add hooks for rendering form fields
        add_action('mpai_render_settings_tabs', [$this, 'render_settings_tabs']);
        add_action('mpai_render_settings_fields', [$this, 'render_settings_fields']);
        add_action('mpai_render_submit_button', [$this, 'render_submit_button']);
    }

    /**
     * Render the settings page
     *
     * @return void
     */
    public function render_settings_page(): void {
        // Check if coordinator is available
        if (!$this->settings_coordinator) {
            $this->log('Error: Settings coordinator not available in renderer', ['level' => 'error']);
            echo '<div class="notice notice-error"><p>';
            echo esc_html__('Error: Settings coordinator not available. Please try again later or contact support.', 'memberpress-ai-assistant');
            echo '</p></div>';
            return;
        }
        
        // Get provider from coordinator
        $provider = $this->settings_coordinator->getController();
        
        if (!$provider) {
            $this->log('Error: Settings provider not available from coordinator', ['level' => 'error']);
            echo '<div class="notice notice-error"><p>';
            echo esc_html__('Error: Settings provider not available. Please try again later or contact support.', 'memberpress-ai-assistant');
            echo '</p></div>';
            return;
        }
        
        try {
            // Get current tab from the provider
            $tabs = $provider->get_tabs();
            $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
            
            // Ensure the tab is valid
            if (!isset($tabs[$current_tab])) {
                $current_tab = 'general';
            }
            
            // Set up template variables with explicit context
            $renderer = $this;
            
            // Create template variables array with explicit context
            $template_vars = [
                'renderer' => $renderer,
                'provider' => $provider,
                'current_tab' => $current_tab,
                'tabs' => $tabs
            ];
            
            // Log template variables
            $this->log('Rendering settings page with variables: ' .
                json_encode(array_keys($template_vars)));
            
            // Pass variables directly to the template without extract()
            $this->render_template($template_vars);
        } catch (\Exception $e) {
            $this->log('Error preparing template variables: ' . $e->getMessage(), ['level' => 'error']);
            echo '<div class="notice notice-error"><p>';
            echo esc_html__('An error occurred while preparing the settings page. Please try again later or contact support.', 'memberpress-ai-assistant');
            echo '</p></div>';
        }
    }
    
    /**
     * Render the template with explicit variables
     *
     * @param array $vars Template variables
     * @return void
     */
    protected function render_template(array $vars): void {
        $template_path = MPAI_PLUGIN_DIR . 'templates/settings-page.php';
        
        if (!file_exists($template_path)) {
            $this->log('Error: Template file not found: ' . $template_path, ['level' => 'error']);
            echo '<div class="notice notice-error"><p>';
            echo esc_html__('Error: Settings template file not found. Please reinstall the plugin or contact support.', 'memberpress-ai-assistant');
            echo '</p></div>';
            return;
        }
        
        try {
            // Include the template with variables in scope
            include($template_path);
        } catch (\Exception $e) {
            $this->log('Error rendering template: ' . $e->getMessage(), ['level' => 'error']);
            echo '<div class="notice notice-error"><p>';
            echo esc_html__('An error occurred while rendering the settings page. Please try again later or contact support.', 'memberpress-ai-assistant');
            echo '</p></div>';
        }
    }

    /**
     * Render the settings form
     *
     * @param string $current_tab Current tab
     * @return void
     */
    public function render_settings_form(string $current_tab): void {
        // Get the provider from the coordinator
        $provider = $this->getSettingsProvider();
        
        if (!$provider) {
            echo '<div class="notice notice-error"><p>';
            echo esc_html__('Error: Settings provider not available. Please try again later.', 'memberpress-ai-assistant');
            echo '</p></div>';
            return;
        }
        
        $page_slug = $provider->get_page_slug();
        
        // Start the form
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        
        // Add hidden fields
        echo '<input type="hidden" name="action" value="mpai_update_settings" />';
        echo '<input type="hidden" name="tab" value="' . esc_attr($current_tab) . '" />';
        
        // Add WordPress nonce field
        wp_nonce_field($page_slug . '-options');
        
        // Render settings fields for the current tab
        $this->render_settings_fields($current_tab);
        
        // Render submit button
        $this->render_submit_button();
        
        // End the form
        echo '</form>';
    }

    /**
     * Render the settings tabs
     *
     * @param string $current_tab Current tab
     * @param array $tabs Available tabs
     * @return void
     */
    public function render_settings_tabs(string $current_tab, array $tabs): void {
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
     * Render the settings fields for the current tab
     *
     * @param string $current_tab Current tab
     * @return void
     */
    public function render_settings_fields(string $current_tab): void {
        // Get the provider from the coordinator
        $provider = $this->getSettingsProvider();
        
        if (!$provider) {
            echo '<div class="notice notice-error"><p>';
            echo esc_html__('Error: Settings provider not available. Please try again later.', 'memberpress-ai-assistant');
            echo '</p></div>';
            return;
        }
        
        $page_slug = $provider->get_page_slug();
        
        echo '<table class="form-table" role="presentation">';
        
        // Output section and fields based on current tab
        switch ($current_tab) {
            case 'general':
                do_settings_sections($page_slug);
                break;
                
            case 'api':
                // Only show the API section
                $this->render_section('mpai_api_section', $page_slug);
                break;
                
            case 'chat':
                // Only show the chat section
                $this->render_section('mpai_chat_section', $page_slug);
                break;
                
            case 'access':
                // Only show the access section
                $this->render_section('mpai_access_section', $page_slug);
                break;
                
            case 'consent':
                // Log that we're rendering the consent section
                $this->log('Rendering consent section for tab: ' . $current_tab, ['tab' => $current_tab]);
                
                // Only show the consent section
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
    protected function render_section(string $section_id, string $page_slug): void {
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
    public function render_submit_button(): void {
        echo '<p class="submit">';
        echo '<input type="submit" name="submit" id="submit" class="button button-primary" value="' . 
            esc_attr__('Save Changes', 'memberpress-ai-assistant') . '" />';
        echo '</p>';
    }

    /**
     * Render the general section
     *
     * @return void
     */
    public function render_general_section(): void {
        echo '<p>' . esc_html__('Configure general settings for the MemberPress AI Assistant.', 'memberpress-ai-assistant') . '</p>';
    }

    /**
     * Render the chat section
     *
     * @return void
     */
    public function render_chat_section(): void {
        echo '<p>' . esc_html__('Configure how the chat interface appears and behaves.', 'memberpress-ai-assistant') . '</p>';
    }

    /**
     * Render the access section
     *
     * @return void
     */
    public function render_access_section(): void {
        echo '<p>' . esc_html__('Control which user roles can access the AI Assistant chat interface.', 'memberpress-ai-assistant') . '</p>';
    }
    
    /**
     * Render the API section
     *
     * @return void
     */
    public function render_api_section(): void {
        echo '<p>' . esc_html__('Configure API settings for AI providers. API keys are stored securely using Split Key Storage.', 'memberpress-ai-assistant') . '</p>';
    }
    
    /**
     * Render the OpenAI API Key field
     *
     * @return void
     */
    public function render_openai_api_key_field(): void {
        $api_key = $this->settings_storage->get_setting('openai_api_key', '');
        ?>
        <input type="password" id="mpai_openai_api_key" name="mpai_settings[openai_api_key]" value="<?php echo esc_attr($api_key); ?>" class="regular-text">
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
     * @return void
     */
    public function render_anthropic_api_key_field(): void {
        $api_key = $this->settings_storage->get_setting('anthropic_api_key', '');
        ?>
        <input type="password" id="mpai_anthropic_api_key" name="mpai_settings[anthropic_api_key]" value="<?php echo esc_attr($api_key); ?>" class="regular-text">
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
     * @return void
     */
    public function render_primary_api_field(): void {
        $primary_api = $this->settings_storage->get_setting('primary_api', 'openai');
        $providers = [
            'openai' => __('OpenAI', 'memberpress-ai-assistant'),
            'anthropic' => __('Anthropic (Claude)', 'memberpress-ai-assistant'),
        ];
        ?>
        <div class="mpai-provider-selection">
            <?php foreach ($providers as $value => $label) : ?>
                <label class="mpai-provider-option">
                    <input type="radio" name="mpai_settings[primary_api]" value="<?php echo esc_attr($value); ?>" <?php checked($primary_api, $value); ?>>
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
    public function render_provider_selection_js(): void {
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
     * Render the chat enabled field
     *
     * @return void
     */
    public function render_chat_enabled_field(): void {
        $chat_enabled = $this->settings_storage->is_chat_enabled();
        ?>
        <label for="mpai_chat_enabled">
            <input type="checkbox" id="mpai_chat_enabled" name="mpai_settings[chat_enabled]" value="1" <?php checked($chat_enabled, true); ?> />
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
     * @return void
     */
    public function render_chat_location_field(): void {
        $chat_location = $this->settings_storage->get_chat_location();
        ?>
        <select id="mpai_chat_location" name="mpai_settings[chat_location]">
            <option value="admin_only" <?php selected($chat_location, 'admin_only'); ?>>
                <?php esc_html_e('Admin Area Only', 'memberpress-ai-assistant'); ?>
            </option>
            <option value="frontend" <?php selected($chat_location, 'frontend'); ?>>
                <?php esc_html_e('Frontend Only', 'memberpress-ai-assistant'); ?>
            </option>
            <option value="both" <?php selected($chat_location, 'both'); ?>>
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
     * @return void
     */
    public function render_chat_position_field(): void {
        $chat_position = $this->settings_storage->get_chat_position();
        ?>
        <select id="mpai_chat_position" name="mpai_settings[chat_position]">
            <option value="bottom_right" <?php selected($chat_position, 'bottom_right'); ?>>
                <?php esc_html_e('Bottom Right', 'memberpress-ai-assistant'); ?>
            </option>
            <option value="bottom_left" <?php selected($chat_position, 'bottom_left'); ?>>
                <?php esc_html_e('Bottom Left', 'memberpress-ai-assistant'); ?>
            </option>
            <option value="top_right" <?php selected($chat_position, 'top_right'); ?>>
                <?php esc_html_e('Top Right', 'memberpress-ai-assistant'); ?>
            </option>
            <option value="top_left" <?php selected($chat_position, 'top_left'); ?>>
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
     * @return void
     */
    public function render_user_roles_field(): void {
        $user_roles = $this->settings_storage->get_user_roles();
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
                           <?php checked(in_array($role_slug, $user_roles), true); ?> />
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
     * Render the OpenAI model field
     *
     * @return void
     */
    public function render_openai_model_field(): void {
        $model = $this->settings_storage->get_setting('openai_model', 'gpt-4o');
        $models = [
            'gpt-4o' => \__('GPT-4o', 'memberpress-ai-assistant'),
            'gpt-4-turbo' => \__('GPT-4 Turbo', 'memberpress-ai-assistant'),
            'gpt-4' => \__('GPT-4', 'memberpress-ai-assistant'),
            'gpt-3.5-turbo' => \__('GPT-3.5 Turbo', 'memberpress-ai-assistant'),
        ];
        
        ?>
        <select id="mpai_openai_model" name="mpai_settings[openai_model]">
            <?php foreach ($models as $value => $label) : ?>
                <option value="<?php echo \esc_attr($value); ?>" <?php \selected($model, $value); ?>>
                    <?php echo \esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">
            <?php \esc_html_e('Select the OpenAI model to use for AI operations.', 'memberpress-ai-assistant'); ?>
        </p>
        <?php
    }
    
    /**
     * Render the Anthropic model field
     *
     * @return void
     */
    public function render_anthropic_model_field(): void {
        $model = $this->settings_storage->get_setting('anthropic_model', 'claude-3-opus-20240229');
        $models = [
            'claude-3-opus-20240229' => \__('Claude 3 Opus', 'memberpress-ai-assistant'),
            'claude-3-sonnet-20240229' => \__('Claude 3 Sonnet', 'memberpress-ai-assistant'),
            'claude-3-haiku-20240307' => \__('Claude 3 Haiku', 'memberpress-ai-assistant'),
            'claude-2.1' => \__('Claude 2.1', 'memberpress-ai-assistant'),
            'claude-2.0' => \__('Claude 2.0', 'memberpress-ai-assistant'),
        ];
        
        ?>
        <select id="mpai_anthropic_model" name="mpai_settings[anthropic_model]">
            <?php foreach ($models as $value => $label) : ?>
                <option value="<?php echo \esc_attr($value); ?>" <?php \selected($model, $value); ?>>
                    <?php echo \esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">
            <?php \esc_html_e('Select the Anthropic model to use for AI operations.', 'memberpress-ai-assistant'); ?>
        </p>
        <?php
    }
    
    /**
     * Render the OpenAI temperature field
     *
     * @return void
     */
    public function render_openai_temperature_field(): void {
        $temperature = $this->settings_storage->get_openai_temperature();
        ?>
        <input type="range" id="mpai_openai_temperature" name="mpai_settings[openai_temperature]"
               min="0" max="1" step="0.1" value="<?php echo esc_attr($temperature); ?>"
               oninput="document.getElementById('mpai_openai_temperature_value').textContent = this.value;">
        <span id="mpai_openai_temperature_value"><?php echo esc_html($temperature); ?></span>
        <p class="description">
            <?php esc_html_e('Adjust the temperature for OpenAI responses. Lower values (closer to 0) make responses more focused and deterministic, while higher values (closer to 1) make responses more creative and diverse.', 'memberpress-ai-assistant'); ?>
        </p>
        <?php
    }
    
    /**
     * Render the OpenAI max tokens field
     *
     * @return void
     */
    public function render_openai_max_tokens_field(): void {
        $max_tokens = $this->settings_storage->get_openai_max_tokens();
        ?>
        <input type="number" id="mpai_openai_max_tokens" name="mpai_settings[openai_max_tokens]"
               min="1" max="8192" step="1" value="<?php echo esc_attr($max_tokens); ?>" class="small-text">
        <p class="description">
            <?php esc_html_e('Set the maximum number of tokens for OpenAI responses. This limits the length of the generated text.', 'memberpress-ai-assistant'); ?>
        </p>
        <?php
    }
    
    /**
     * Render the Anthropic temperature field
     *
     * @return void
     */
    public function render_anthropic_temperature_field(): void {
        $temperature = $this->settings_storage->get_anthropic_temperature();
        ?>
        <input type="range" id="mpai_anthropic_temperature" name="mpai_settings[anthropic_temperature]"
               min="0" max="1" step="0.1" value="<?php echo esc_attr($temperature); ?>"
               oninput="document.getElementById('mpai_anthropic_temperature_value').textContent = this.value;">
        <span id="mpai_anthropic_temperature_value"><?php echo esc_html($temperature); ?></span>
        <p class="description">
            <?php esc_html_e('Adjust the temperature for Anthropic responses. Lower values (closer to 0) make responses more focused and deterministic, while higher values (closer to 1) make responses more creative and diverse.', 'memberpress-ai-assistant'); ?>
        </p>
        <?php
    }
    
    /**
     * Render the Anthropic max tokens field
     *
     * @return void
     */
    public function render_anthropic_max_tokens_field(): void {
        $max_tokens = $this->settings_storage->get_anthropic_max_tokens();
        ?>
        <input type="number" id="mpai_anthropic_max_tokens" name="mpai_settings[anthropic_max_tokens]"
               min="1" max="100000" step="1" value="<?php echo esc_attr($max_tokens); ?>" class="small-text">
        <p class="description">
            <?php esc_html_e('Set the maximum number of tokens for Anthropic responses. This limits the length of the generated text.', 'memberpress-ai-assistant'); ?>
        </p>
        <?php
    }
    /**
     * Render the consent section
     *
     * @return void
     */
    public function render_consent_section(): void {
        echo '<p>' . esc_html__('Configure consent settings for the MemberPress AI Assistant. These settings control how user consent is managed.', 'memberpress-ai-assistant') . '</p>';
    }
    
    /**
     * Render the consent required field
     *
     * @return void
     */
    public function render_consent_required_field(): void {
        $consent_required = $this->settings_storage->is_consent_required();
        ?>
        <label for="mpai_consent_required">
            <input type="checkbox" id="mpai_consent_required" name="mpai_settings[consent_required]" value="1" <?php checked($consent_required, true); ?> />
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
    public function render_consent_form_preview_field(): void {
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
    public function render_reset_all_consents_field(): void {
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
     * Enhanced logging method
     *
     * @param string $message The message to log
     * @param array $context Additional context data
     * @return void
     */
    protected function log($message, array $context = []): void {
        if ($this->logger) {
            $level = isset($context['level']) ? $context['level'] : 'info';
            unset($context['level']);
            
            if ($level === 'error') {
                $this->logger->error($message, $context);
            } else if ($level === 'warning') {
                $this->logger->warning($message, $context);
            } else {
                $this->logger->info($message, $context);
            }
        }
    }
}