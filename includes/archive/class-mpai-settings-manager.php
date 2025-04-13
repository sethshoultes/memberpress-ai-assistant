<?php
/**
 * Settings Manager Class
 * 
 * Centralizes all plugin settings management with proper tab navigation
 *
 * @package MemberPress AI Assistant
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class MPAI_Settings_Manager
 * 
 * Responsible for settings management, registration, and UI rendering
 */
class MPAI_Settings_Manager {
    /**
     * Settings tabs
     * 
     * @var array
     */
    private $tabs = [];

    /**
     * Settings groups within tabs
     * 
     * @var array
     */
    private $settings_groups = [];

    /**
     * Current active tab
     * 
     * @var string
     */
    private $current_tab = '';

    /**
     * Constructor
     */
    public function __construct() {
        // Register default tabs
        $this->register_tab('api', __('API Settings', 'memberpress-ai-assistant'), [$this, 'render_api_tab']);
        $this->register_tab('chat', __('Chat Interface', 'memberpress-ai-assistant'), [$this, 'render_chat_tab']);
        $this->register_tab('cli', __('CLI Commands', 'memberpress-ai-assistant'), [$this, 'render_cli_tab']);
        $this->register_tab('tools', __('AI Tools', 'memberpress-ai-assistant'), [$this, 'render_tools_tab']);
        $this->register_tab('advanced', __('Advanced', 'memberpress-ai-assistant'), [$this, 'render_advanced_tab']);
        
        // Register settings
        add_action('admin_init', [$this, 'register_settings']);
        
        // Get the current active tab
        $this->current_tab = $this->get_current_tab();
    }

    /**
     * Register a settings tab
     * 
     * @param string   $id       Tab ID
     * @param string   $title    Tab title
     * @param callable $callback Tab rendering callback
     * 
     * @return MPAI_Settings_Manager Self reference for chaining
     */
    public function register_tab($id, $title, $callback) {
        $this->tabs[$id] = [
            'title' => $title,
            'callback' => $callback
        ];
        
        return $this;
    }

    /**
     * Register a settings group within a tab
     * 
     * @param string $tab_id   Tab ID
     * @param string $group_id Group ID
     * @param string $title    Group title
     * 
     * @return MPAI_Settings_Manager Self reference for chaining
     */
    public function register_setting_group($tab_id, $group_id, $title) {
        if (!isset($this->settings_groups[$tab_id])) {
            $this->settings_groups[$tab_id] = [];
        }
        
        $this->settings_groups[$tab_id][$group_id] = [
            'title' => $title,
            'fields' => []
        ];
        
        return $this;
    }

    /**
     * Register a setting field
     * 
     * @param string $tab_id   Tab ID
     * @param string $group_id Group ID
     * @param string $field_id Field ID
     * @param string $title    Field title
     * @param string $type     Field type
     * @param array  $args     Additional field arguments
     * 
     * @return MPAI_Settings_Manager Self reference for chaining
     */
    public function register_setting($tab_id, $group_id, $field_id, $title, $type, $args = []) {
        // Ensure the group exists
        if (!isset($this->settings_groups[$tab_id][$group_id])) {
            $this->register_setting_group($tab_id, $group_id, $group_id);
        }
        
        // Add the field to the group
        $this->settings_groups[$tab_id][$group_id]['fields'][$field_id] = [
            'title' => $title,
            'type' => $type,
            'args' => $args
        ];
        
        // Register with WordPress Settings API
        if ($type !== 'custom') {
            register_setting('mpai_' . $tab_id . '_options', 'mpai_' . $field_id, isset($args['sanitize_callback']) ? $args['sanitize_callback'] : null);
        }
        
        return $this;
    }

    /**
     * Register all settings with WordPress
     */
    public function register_settings() {
        // API Settings
        $this->register_setting_group('api', 'providers', __('API Providers', 'memberpress-ai-assistant'))
             ->register_setting_group('api', 'openai', __('OpenAI Settings', 'memberpress-ai-assistant'))
             ->register_setting_group('api', 'anthropic', __('Anthropic Settings', 'memberpress-ai-assistant'));

        // Register primary API provider setting
        $this->register_setting('api', 'providers', 'primary_api', __('Primary API Provider', 'memberpress-ai-assistant'), 'select', [
            'options' => [
                'openai' => __('OpenAI (GPT)', 'memberpress-ai-assistant'),
                'anthropic' => __('Anthropic (Claude)', 'memberpress-ai-assistant')
            ],
            'default' => 'openai',
            'description' => __('Select which AI provider to use as the primary service.', 'memberpress-ai-assistant')
        ]);

        // OpenAI settings
        $this->register_setting('api', 'openai', 'api_key', __('OpenAI API Key', 'memberpress-ai-assistant'), 'password', [
            'sanitize_callback' => 'sanitize_text_field',
            'description' => __('Enter your OpenAI API key.', 'memberpress-ai-assistant')
        ]);
        
        $this->register_setting('api', 'openai', 'model', __('OpenAI Model', 'memberpress-ai-assistant'), 'select', [
            'options' => [
                'gpt-4o' => __('GPT-4o (Recommended)', 'memberpress-ai-assistant'),
                'gpt-4' => __('GPT-4', 'memberpress-ai-assistant'),
                'gpt-3.5-turbo' => __('GPT-3.5 Turbo', 'memberpress-ai-assistant')
            ],
            'default' => 'gpt-4o',
            'description' => __('Select the OpenAI model to use.', 'memberpress-ai-assistant')
        ]);

        // Anthropic settings
        $this->register_setting('api', 'anthropic', 'anthropic_api_key', __('Anthropic API Key', 'memberpress-ai-assistant'), 'password', [
            'sanitize_callback' => 'sanitize_text_field',
            'description' => __('Enter your Anthropic API key.', 'memberpress-ai-assistant')
        ]);
        
        $this->register_setting('api', 'anthropic', 'anthropic_model', __('Claude Model', 'memberpress-ai-assistant'), 'select', [
            'options' => [
                'claude-3-opus-20240229' => __('Claude 3 Opus', 'memberpress-ai-assistant'),
                'claude-3-sonnet-20240229' => __('Claude 3 Sonnet', 'memberpress-ai-assistant'),
                'claude-3-haiku-20240307' => __('Claude 3 Haiku', 'memberpress-ai-assistant')
            ],
            'default' => 'claude-3-opus-20240229',
            'description' => __('Select the Anthropic Claude model to use.', 'memberpress-ai-assistant')
        ]);
        
        // Chat Interface settings
        $this->register_setting_group('chat', 'display', __('Display Options', 'memberpress-ai-assistant'))
             ->register_setting_group('chat', 'content', __('Content', 'memberpress-ai-assistant'));

        $this->register_setting('chat', 'display', 'enable_chat', __('Enable Chat Interface', 'memberpress-ai-assistant'), 'checkbox', [
            'default' => true,
            'description' => __('Show floating chat bubble in admin.', 'memberpress-ai-assistant')
        ]);
        
        $this->register_setting('chat', 'display', 'chat_position', __('Chat Position', 'memberpress-ai-assistant'), 'select', [
            'options' => [
                'bottom-right' => __('Bottom Right', 'memberpress-ai-assistant'),
                'bottom-left' => __('Bottom Left', 'memberpress-ai-assistant'),
                'top-right' => __('Top Right', 'memberpress-ai-assistant'),
                'top-left' => __('Top Left', 'memberpress-ai-assistant')
            ],
            'default' => 'bottom-right',
            'description' => __('Choose where the chat bubble should appear.', 'memberpress-ai-assistant')
        ]);
        
        $this->register_setting('chat', 'display', 'show_on_all_pages', __('Display Scope', 'memberpress-ai-assistant'), 'checkbox', [
            'default' => true,
            'description' => __('If unchecked, the chat will only appear on MemberPress admin pages.', 'memberpress-ai-assistant')
        ]);
        
        $this->register_setting('chat', 'content', 'welcome_message', __('Welcome Message', 'memberpress-ai-assistant'), 'textarea', [
            'default' => __('Hi there! I\'m your MemberPress AI Assistant. How can I help you today?', 'memberpress-ai-assistant'),
            'sanitize_callback' => 'wp_kses_post',
            'description' => __('The welcome message shown when the chat is opened.', 'memberpress-ai-assistant')
        ]);
        
        // CLI Command settings
        $this->register_setting_group('cli', 'commands', __('WP-CLI Commands', 'memberpress-ai-assistant'));
        
        $this->register_setting('cli', 'commands', 'enable_cli_commands', __('Enable CLI Commands', 'memberpress-ai-assistant'), 'checkbox', [
            'default' => true,
            'description' => __('Allow running WP-CLI commands through the AI Assistant.', 'memberpress-ai-assistant')
        ]);
        
        // Tool settings
        $this->register_setting_group('tools', 'configuration', __('AI Tool Configuration', 'memberpress-ai-assistant'));
        
        $this->register_setting('tools', 'configuration', 'enable_mcp', __('Enable MCP', 'memberpress-ai-assistant'), 'checkbox', [
            'default' => true,
            'description' => __('Allow the AI assistant to use tools via Model Context Protocol.', 'memberpress-ai-assistant')
        ]);
        
        $this->register_setting('tools', 'configuration', 'enable_wp_cli_tool', __('WP CLI Tool', 'memberpress-ai-assistant'), 'checkbox', [
            'default' => true,
            'description' => __('Allows the AI to execute WP-CLI commands.', 'memberpress-ai-assistant')
        ]);
        
        $this->register_setting('tools', 'configuration', 'enable_memberpress_info_tool', __('MemberPress Info Tool', 'memberpress-ai-assistant'), 'checkbox', [
            'default' => true,
            'description' => __('Allows the AI to fetch MemberPress data.', 'memberpress-ai-assistant')
        ]);
        
        // Advanced settings
        $this->register_setting_group('advanced', 'openai_advanced', __('OpenAI Advanced Settings', 'memberpress-ai-assistant'))
             ->register_setting_group('advanced', 'anthropic_advanced', __('Anthropic Advanced Settings', 'memberpress-ai-assistant'));
        
        $this->register_setting('advanced', 'openai_advanced', 'temperature', __('Temperature', 'memberpress-ai-assistant'), 'number', [
            'default' => 0.7,
            'min' => 0,
            'max' => 2,
            'step' => 0.1,
            'description' => __('Controls randomness: lower values make responses more focused and deterministic (0-2).', 'memberpress-ai-assistant')
        ]);
        
        $this->register_setting('advanced', 'openai_advanced', 'max_tokens', __('Max Tokens', 'memberpress-ai-assistant'), 'number', [
            'default' => 2048,
            'min' => 1,
            'max' => 16000,
            'step' => 1,
            'description' => __('Maximum number of tokens to generate in the response.', 'memberpress-ai-assistant')
        ]);
        
        $this->register_setting('advanced', 'anthropic_advanced', 'anthropic_temperature', __('Temperature', 'memberpress-ai-assistant'), 'number', [
            'default' => 0.7,
            'min' => 0,
            'max' => 1,
            'step' => 0.01,
            'description' => __('Controls randomness: lower values make responses more focused and deterministic (0-1).', 'memberpress-ai-assistant')
        ]);
        
        $this->register_setting('advanced', 'anthropic_advanced', 'anthropic_max_tokens', __('Max Tokens', 'memberpress-ai-assistant'), 'number', [
            'default' => 2048,
            'min' => 1,
            'max' => 4096,
            'step' => 1,
            'description' => __('Maximum number of tokens to generate in the response.', 'memberpress-ai-assistant')
        ]);
    }

    /**
     * Get the current active tab
     * 
     * @return string Current tab ID
     */
    private function get_current_tab() {
        // Get the first tab as default
        $default_tab = array_keys($this->tabs)[0] ?? 'api';
        
        // Get the current tab from URL parameter
        return isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : $default_tab;
    }

    /**
     * Render the settings page
     */
    public function render_settings_page() {
        // Determine current tab
        $current_tab = $this->current_tab;
        
        // Output settings form
        ?>
        <div class="wrap mpai-settings-page">
            <h1><?php _e('MemberPress AI Assistant Settings', 'memberpress-ai-assistant'); ?></h1>
            
            <?php settings_errors('mpai_messages'); ?>
            
            <form method="post" action="options.php">
                <?php
                // Render tab navigation
                $this->render_tab_navigation($current_tab);
                
                // Settings fields for the current tab
                settings_fields('mpai_' . $current_tab . '_options');
                
                // Check if tab has a custom callback
                if (isset($this->tabs[$current_tab]['callback']) && is_callable($this->tabs[$current_tab]['callback'])) {
                    call_user_func($this->tabs[$current_tab]['callback']);
                } else {
                    // Default rendering of settings groups
                    $this->render_settings_groups($current_tab);
                }
                
                // Submit button
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render the tab navigation
     * 
     * @param string $current_tab Current active tab
     */
    private function render_tab_navigation($current_tab) {
        echo '<h2 class="nav-tab-wrapper">';
        
        foreach ($this->tabs as $tab_id => $tab) {
            $active_class = ($tab_id === $current_tab) ? 'nav-tab-active' : '';
            $url = add_query_arg('tab', $tab_id);
            
            echo '<a href="' . esc_url($url) . '" class="nav-tab ' . $active_class . '">' . esc_html($tab['title']) . '</a>';
        }
        
        echo '</h2>';
    }

    /**
     * Render settings groups for a tab
     * 
     * @param string $tab_id Tab ID
     */
    private function render_settings_groups($tab_id) {
        // Check if this tab has settings groups
        if (!isset($this->settings_groups[$tab_id])) {
            echo '<p>' . __('No settings available for this tab.', 'memberpress-ai-assistant') . '</p>';
            return;
        }
        
        // Loop through each settings group
        foreach ($this->settings_groups[$tab_id] as $group_id => $group) {
            echo '<div class="mpai-settings-group">';
            echo '<h3>' . esc_html($group['title']) . '</h3>';
            echo '<table class="form-table">';
            
            // Loop through each field in the group
            foreach ($group['fields'] as $field_id => $field) {
                $this->render_setting_field('mpai_' . $field_id, $field);
            }
            
            echo '</table>';
            echo '</div>';
        }
    }

    /**
     * Render a setting field
     * 
     * @param string $field_id Field ID
     * @param array  $field    Field data
     */
    private function render_setting_field($field_id, $field) {
        $title = $field['title'];
        $type = $field['type'];
        $args = $field['args'];
        $value = get_option($field_id, isset($args['default']) ? $args['default'] : '');
        $description = isset($args['description']) ? $args['description'] : '';
        
        echo '<tr>';
        echo '<th scope="row"><label for="' . esc_attr($field_id) . '">' . esc_html($title) . '</label></th>';
        echo '<td>';
        
        switch ($type) {
            case 'text':
                echo '<input type="text" name="' . esc_attr($field_id) . '" id="' . esc_attr($field_id) . '" value="' . esc_attr($value) . '" class="regular-text" />';
                break;
                
            case 'password':
                echo '<input type="password" name="' . esc_attr($field_id) . '" id="' . esc_attr($field_id) . '" value="' . esc_attr($value) . '" class="regular-text" />';
                break;
                
            case 'textarea':
                echo '<textarea name="' . esc_attr($field_id) . '" id="' . esc_attr($field_id) . '" rows="5" class="large-text">' . esc_textarea($value) . '</textarea>';
                break;
                
            case 'checkbox':
                echo '<label>';
                echo '<input type="checkbox" name="' . esc_attr($field_id) . '" id="' . esc_attr($field_id) . '" value="1" ' . checked(1, $value, false) . ' />';
                echo ' ' . $description . '</label>';
                $description = ''; // Already used in the label
                break;
                
            case 'select':
                echo '<select name="' . esc_attr($field_id) . '" id="' . esc_attr($field_id) . '">';
                
                if (isset($args['options']) && is_array($args['options'])) {
                    foreach ($args['options'] as $option_value => $option_label) {
                        echo '<option value="' . esc_attr($option_value) . '" ' . selected($value, $option_value, false) . '>' . esc_html($option_label) . '</option>';
                    }
                }
                
                echo '</select>';
                break;
                
            case 'number':
                $min = isset($args['min']) ? ' min="' . esc_attr($args['min']) . '"' : '';
                $max = isset($args['max']) ? ' max="' . esc_attr($args['max']) . '"' : '';
                $step = isset($args['step']) ? ' step="' . esc_attr($args['step']) . '"' : '';
                
                echo '<input type="number" name="' . esc_attr($field_id) . '" id="' . esc_attr($field_id) . '" value="' . esc_attr($value) . '"' . $min . $max . $step . ' class="regular-text" />';
                break;
                
            case 'custom':
                if (isset($args['callback']) && is_callable($args['callback'])) {
                    call_user_func($args['callback'], $field_id, $value, $args);
                } else {
                    echo '<p>' . __('Custom field callback not defined.', 'memberpress-ai-assistant') . '</p>';
                }
                break;
                
            default:
                echo '<input type="text" name="' . esc_attr($field_id) . '" id="' . esc_attr($field_id) . '" value="' . esc_attr($value) . '" class="regular-text" />';
                break;
        }
        
        if (!empty($description) && $type !== 'checkbox') {
            echo '<p class="description">' . $description . '</p>';
        }
        
        echo '</td>';
        echo '</tr>';
    }

    /**
     * Render tab content for API tab
     */
    public function render_api_tab() {
        // Use the default rendering for API tab
        $this->render_settings_groups('api');
    }

    /**
     * Render tab content for Chat Interface tab
     */
    public function render_chat_tab() {
        // Use the default rendering for Chat tab
        $this->render_settings_groups('chat');
    }

    /**
     * Render tab content for CLI Commands tab
     */
    public function render_cli_tab() {
        // Use the default rendering for CLI tab
        $this->render_settings_groups('cli');
        
        // Additional custom interface for allowed commands
        ?>
        <div class="mpai-settings-group">
            <h3><?php _e('Allowed Commands', 'memberpress-ai-assistant'); ?></h3>
            <div id="mpai-allowed-commands">
                <?php
                $allowed_commands = get_option('mpai_allowed_cli_commands', ['wp user list', 'wp post list', 'wp plugin list']);
                
                if (!is_array($allowed_commands)) {
                    $allowed_commands = [];
                }
                
                foreach ($allowed_commands as $command) {
                    ?>
                    <div class="mpai-command-row">
                        <input type="text" name="mpai_allowed_cli_commands[]" value="<?php echo esc_attr($command); ?>" class="regular-text" />
                        <button type="button" class="button mpai-remove-command"><?php _e('Remove', 'memberpress-ai-assistant'); ?></button>
                    </div>
                    <?php
                }
                ?>
            </div>
            <button type="button" class="button mpai-add-command"><?php _e('Add Command', 'memberpress-ai-assistant'); ?></button>
            <p class="description"><?php _e('Specify the allowed WP-CLI commands. The AI will only be able to execute these commands. Use prefixes like "wp user" to allow all user-related commands.', 'memberpress-ai-assistant'); ?></p>
            
            <script>
            jQuery(document).ready(function($) {
                // Add command
                $('.mpai-add-command').on('click', function() {
                    var newRow = '<div class="mpai-command-row"><input type="text" name="mpai_allowed_cli_commands[]" value="" class="regular-text" /> <button type="button" class="button mpai-remove-command"><?php echo esc_js(__('Remove', 'memberpress-ai-assistant')); ?></button></div>';
                    $('#mpai-allowed-commands').append(newRow);
                });
                
                // Remove command
                $(document).on('click', '.mpai-remove-command', function() {
                    $(this).closest('.mpai-command-row').remove();
                });
            });
            </script>
        </div>
        <?php
    }

    /**
     * Render tab content for AI Tools tab
     */
    public function render_tools_tab() {
        // Use the default rendering for Tools tab
        $this->render_settings_groups('tools');
    }

    /**
     * Render tab content for Advanced tab
     */
    public function render_advanced_tab() {
        // Use the default rendering for Advanced tab
        $this->render_settings_groups('advanced');
    }
}