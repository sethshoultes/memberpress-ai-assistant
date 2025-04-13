<?php
/**
 * Settings Registry Class
 * 
 * Implements the new modular settings framework for the admin UI overhaul
 *
 * @package MemberPress AI Assistant
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class MPAI_Settings_Registry
 * 
 * Responsible for registering and rendering settings in a modular fashion
 */
class MPAI_Settings_Registry {
    /**
     * Store settings tabs
     * 
     * @var array
     */
    private $tabs = [];

    /**
     * Store settings groups
     * 
     * @var array
     */
    private $settings_groups = [];

    /**
     * Store settings fields
     * 
     * @var array
     */
    private $settings = [];

    /**
     * Constructor
     */
    public function __construct() {
        // Add hook to register settings
        add_action('admin_init', [$this, 'register_settings_with_wordpress']);
    }

    /**
     * Register a settings tab
     * 
     * @param string   $id       Tab ID
     * @param string   $title    Tab title
     * @param callable $callback Optional custom renderer for tab content
     * @param array    $args     Additional arguments (icon, description, etc.)
     * 
     * @return MPAI_Settings_Registry Self reference for chaining
     */
    public function register_tab($id, $title, $callback = null, $args = []) {
        $this->tabs[$id] = [
            'title' => $title,
            'callback' => $callback,
            'icon' => isset($args['icon']) ? $args['icon'] : '',
            'description' => isset($args['description']) ? $args['description'] : ''
        ];
        
        return $this;
    }

    /**
     * Register a settings group
     * 
     * @param string $tab_id   Tab ID
     * @param string $group_id Group ID
     * @param string $title    Group title
     * 
     * @return MPAI_Settings_Registry Self reference for chaining
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
     * @param string $type     Field type (text, select, checkbox, etc.)
     * @param array  $args     Field arguments
     * 
     * @return MPAI_Settings_Registry Self reference for chaining
     */
    public function register_setting($tab_id, $group_id, $field_id, $title, $type, $args = []) {
        // Ensure tab and group exist
        if (!isset($this->tabs[$tab_id])) {
            $this->register_tab($tab_id, ucfirst($tab_id));
        }
        
        if (!isset($this->settings_groups[$tab_id][$group_id])) {
            $this->register_setting_group($tab_id, $group_id, ucfirst($group_id));
        }
        
        // Add the field to the group
        $this->settings_groups[$tab_id][$group_id]['fields'][$field_id] = [
            'title' => $title,
            'type' => $type,
            'args' => $args
        ];
        
        // Store for WordPress registration
        $this->settings[$tab_id][] = [
            'field_id' => $field_id,
            'type' => $type,
            'args' => $args
        ];
        
        return $this;
    }

    /**
     * Register settings with WordPress
     */
    public function register_settings_with_wordpress() {
        foreach ($this->settings as $tab_id => $fields) {
            // Register the option group
            $option_group = 'mpai_' . $tab_id . '_options';
            $page_slug = 'mpai_' . $tab_id . '_page';
            
            // Register sections for each group
            if (isset($this->settings_groups[$tab_id])) {
                foreach ($this->settings_groups[$tab_id] as $group_id => $group) {
                    $section_id = 'mpai_' . $tab_id . '_' . $group_id . '_section';
                    
                    // Add the settings section
                    add_settings_section(
                        $section_id,
                        $group['title'],
                        function() use ($group) {
                            // Optional section description
                            if (isset($group['description'])) {
                                echo '<p class="description">' . esc_html($group['description']) . '</p>';
                            }
                        },
                        $page_slug
                    );
                    
                    // Add fields to this section
                    if (!empty($group['fields'])) {
                        foreach ($group['fields'] as $field_id => $field) {
                            $option_name = 'mpai_' . $field_id;
                            
                            // Add the settings field
                            add_settings_field(
                                'field_' . $field_id,
                                $field['title'],
                                [$this, 'render_field_callback'],
                                $page_slug,
                                $section_id,
                                [
                                    'tab_id' => $tab_id,
                                    'field_id' => $field_id,
                                    'field' => $field,
                                    'label_for' => $option_name
                                ]
                            );
                        }
                    }
                }
            }
            
            // Register each field setting
            foreach ($fields as $field) {
                $option_name = 'mpai_' . $field['field_id'];
                $args = isset($field['args']['register_args']) ? $field['args']['register_args'] : [];
                
                // Set default sanitization based on type
                if (!isset($args['sanitize_callback'])) {
                    switch ($field['type']) {
                        case 'checkbox':
                            $args['sanitize_callback'] = function($value) {
                                return (bool) $value;
                            };
                            break;
                        case 'number':
                            $args['sanitize_callback'] = 'absint';
                            break;
                        case 'text':
                        case 'select':
                        default:
                            $args['sanitize_callback'] = 'sanitize_text_field';
                            break;
                    }
                }
                
                // Register with WordPress
                register_setting($option_group, $option_name, $args);
            }
        }
    }
    
    /**
     * Render field callback for WordPress Settings API
     * 
     * @param array $args Field arguments
     */
    public function render_field_callback($args) {
        if (!isset($args['field_id']) || !isset($args['field']) || !isset($args['tab_id'])) {
            return;
        }
        
        $field_id = $args['field_id'];
        $field = $args['field'];
        $tab_id = $args['tab_id'];
        
        $option_name = 'mpai_' . $field_id;
        $value = get_option($option_name);
        $field_args = isset($field['args']) ? $field['args'] : [];
        
        // Get field description if provided
        $description = isset($field_args['description']) ? $field_args['description'] : '';
        
        // Render the field based on type
        switch ($field['type']) {
            case 'text':
                $this->render_text_field($option_name, $value, $field_args);
                break;
            case 'textarea':
                $this->render_textarea_field($option_name, $value, $field_args);
                break;
            case 'checkbox':
                $this->render_checkbox_field($option_name, $value, $field_args);
                break;
            case 'select':
                $this->render_select_field($option_name, $value, $field_args);
                break;
            case 'radio':
                $this->render_radio_field($option_name, $value, $field_args);
                break;
            case 'color':
                $this->render_color_field($option_name, $value, $field_args);
                break;
            case 'custom':
                if (isset($field_args['render_callback']) && is_callable($field_args['render_callback'])) {
                    call_user_func($field_args['render_callback'], $option_name, $value, $field_args);
                } else {
                    echo '<p class="description">' . 
                         esc_html__('Custom field has no render callback.', 'memberpress-ai-assistant') . 
                         '</p>';
                }
                break;
            default:
                echo '<p class="description">' . 
                     esc_html__('Unknown field type.', 'memberpress-ai-assistant') . 
                     '</p>';
                break;
        }
        
        // Show description if available
        if (!empty($description)) {
            echo '<p class="description">' . esc_html($description) . '</p>';
        }
    }

    /**
     * Render the settings page
     */
    public function render_settings_page() {
        // Get current tab
        $current_tab = $this->get_current_tab();
        
        // Add necessary scripts
        $this->enqueue_settings_scripts();
        
        ?>
        <div class="wrap mpai-settings-wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="mpai-settings-container">
                <?php if (count($this->tabs) > 1): ?>
                    <!-- Tabs navigation -->
                    <div class="nav-tab-wrapper mpai-tabs">
                        <?php $this->render_tabs_navigation($current_tab); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Tab content -->
                <div class="mpai-tab-content">
                    <?php 
                    // Display tab description if available
                    if (!empty($this->tabs[$current_tab]['description'])) {
                        echo '<div class="mpai-tab-description">';
                        echo wp_kses_post($this->tabs[$current_tab]['description']);
                        echo '</div>';
                    }
                    
                    // Render tab content
                    $this->render_tab_content($current_tab); 
                    ?>
                </div>
            </div>
            
            <!-- Add tooltips JavaScript -->
            <script>
                // Initialize tooltips when document is ready
                (function($) {
                    // Toggle help content visibility
                    $('.mpai-toggle-help').on('click', function(e) {
                        e.preventDefault();
                        var $helpContent = $(this).next('.mpai-help-content');
                        
                        if ($helpContent.is(':visible')) {
                            $helpContent.slideUp();
                            $(this).text('<?php _e('Show more help', 'memberpress-ai-assistant'); ?>');
                        } else {
                            $helpContent.slideDown();
                            $(this).text('<?php _e('Hide help', 'memberpress-ai-assistant'); ?>');
                        }
                    });
                    
                    // Initialize tooltips if jQuery UI is available
                    if ($.fn.tooltip) {
                        $('.mpai-tooltip').tooltip({
                            position: { my: "left+10 center", at: "right center" }
                        });
                    }
                })(jQuery);
            </script>
        </div>
        <?php
    }
    
    /**
     * Enqueue necessary scripts and styles for the settings page
     */
    private function enqueue_settings_scripts() {
        // Enqueue jQuery UI for tooltips
        wp_enqueue_script('jquery-ui-tooltip');
        
        // Enqueue WordPress color picker
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        
        // Add custom styles
        ?>
        <style>
            /* Settings page styles */
            .mpai-settings-container {
                margin: 20px 0;
            }
            
            .mpai-tabs {
                margin-bottom: 20px;
            }
            
            .mpai-tabs .nav-tab {
                display: flex;
                align-items: center;
            }
            
            .mpai-tabs .dashicons {
                margin-right: 5px;
            }
            
            .mpai-tab-description {
                background: #fff;
                border-left: 4px solid #2271b1;
                box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
                margin: 0 0 20px;
                padding: 12px;
            }
            
            .mpai-tab-content {
                background: #fff;
                border: 1px solid #ccd0d4;
                box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
                padding: 20px;
                margin-bottom: 20px;
            }
            
            .mpai-settings-group {
                margin-bottom: 30px;
            }
            
            .mpai-settings-group h2 {
                font-size: 16px;
                padding-bottom: 10px;
                border-bottom: 1px solid #eee;
                margin-bottom: 15px;
            }
            
            .mpai-field-wrapper {
                position: relative;
            }
            
            .mpai-field-help {
                margin-top: 8px;
            }
            
            .mpai-help-content {
                background: #f9f9f9;
                border-left: 4px solid #2271b1;
                margin: 10px 0;
                padding: 10px;
            }
            
            .mpai-toggle-help {
                text-decoration: none;
                color: #2271b1;
            }
            
            .mpai-tooltip {
                cursor: help;
                color: #777;
                vertical-align: middle;
                margin-left: 4px;
            }
            
            /* UI improvements for specific field types */
            .mpai-field-checkbox label {
                display: flex;
                align-items: center;
            }
            
            .mpai-field-checkbox input[type="checkbox"] {
                margin-right: 8px;
            }
            
            .ui-tooltip {
                padding: 8px;
                position: absolute;
                z-index: 9999;
                max-width: 300px;
                background: #333;
                color: white;
                border-radius: 3px;
                box-shadow: 0 0 5px rgba(0, 0, 0, 0.2);
                font-size: 12px;
            }
        </style>
        <?php
    }

    /**
     * Get the current tab from URL or default to first tab
     * 
     * @return string Current tab ID
     */
    private function get_current_tab() {
        $default_tab = empty($this->tabs) ? 'general' : array_keys($this->tabs)[0];
        return isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : $default_tab;
    }

    /**
     * Render tabs navigation
     * 
     * @param string $current_tab Current active tab
     */
    private function render_tabs_navigation($current_tab) {
        // Get current page URL
        $current_url = remove_query_arg('settings-updated'); // Remove any success message
        
        foreach ($this->tabs as $tab_id => $tab) {
            $active_class = ($tab_id === $current_tab) ? 'nav-tab-active' : '';
            
            // Generate URL with tab parameter
            $tab_url = add_query_arg('tab', $tab_id, $current_url);
            
            // Add icon if available
            $icon_html = '';
            if (!empty($tab['icon'])) {
                $icon_html = '<span class="dashicons ' . esc_attr($tab['icon']) . '"></span> ';
            }
            
            echo '<a href="' . esc_url($tab_url) . '" class="nav-tab ' . $active_class . '">' . 
                 $icon_html . '<span class="tab-label">' . esc_html($tab['title']) . '</span></a>';
        }
    }

    /**
     * Render the content for the current tab
     * 
     * @param string $current_tab Current active tab
     */
    private function render_tab_content($current_tab) {
        // Check if tab exists
        if (!isset($this->tabs[$current_tab])) {
            echo '<div class="notice notice-error"><p>' . 
                 esc_html__('Tab not found.', 'memberpress-ai-assistant') . '</p></div>';
            return;
        }
        
        // If tab has a custom renderer, use it
        if (isset($this->tabs[$current_tab]['callback']) && is_callable($this->tabs[$current_tab]['callback'])) {
            call_user_func($this->tabs[$current_tab]['callback']);
            return;
        }
        
        // Default rendering of settings groups
        $this->render_settings_form($current_tab);
    }

    /**
     * Render the settings form for a tab
     * 
     * @param string $current_tab Current active tab
     */
    private function render_settings_form($current_tab) {
        $option_group = 'mpai_' . $current_tab . '_options';
        $page_slug = 'mpai_' . $current_tab . '_page';
        
        // Start form
        echo '<form method="post" action="options.php" class="mpai-settings-form">';
        
        // Output nonce, action, and option page fields
        settings_fields($option_group);
        
        // If there are no groups for this tab, show a message
        if (!isset($this->settings_groups[$current_tab]) || empty($this->settings_groups[$current_tab])) {
            echo '<div class="notice notice-warning"><p>' . 
                 esc_html__('No settings found for this tab.', 'memberpress-ai-assistant') . '</p></div>';
        } else {
            // Use WordPress settings API to output sections and fields
            do_settings_sections($page_slug);
        }
        
        // Add submit button
        submit_button();
        
        // End form
        echo '</form>';
    }

    /**
     * Render a settings group
     * 
     * @param string $tab_id   Tab ID
     * @param string $group_id Group ID
     * @param array  $group    Group data
     */
    private function render_settings_group($tab_id, $group_id, $group) {
        ?>
        <div class="mpai-settings-group">
            <h2><?php echo esc_html($group['title']); ?></h2>
            <table class="form-table">
                <tbody>
                    <?php
                    if (empty($group['fields'])) {
                        echo '<tr><td colspan="2">' . 
                             esc_html__('No fields in this group.', 'memberpress-ai-assistant') . 
                             '</td></tr>';
                    } else {
                        foreach ($group['fields'] as $field_id => $field) {
                            $this->render_field($tab_id, $field_id, $field);
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render a settings field
     * 
     * @param string $tab_id   Tab ID
     * @param string $field_id Field ID
     * @param array  $field    Field data
     */
    private function render_field($tab_id, $field_id, $field) {
        $option_name = 'mpai_' . $field_id;
        $value = get_option($option_name);
        $args = $field['args'];
        
        // Get field description if provided
        $description = isset($args['description']) ? $args['description'] : '';
        
        // Get tooltip if provided
        $tooltip = isset($args['tooltip']) ? $args['tooltip'] : '';
        
        // Get help text
        $help = isset($args['help']) ? $args['help'] : '';
        
        // Get CSS class
        $field_class = isset($args['field_class']) ? $args['field_class'] : '';
        
        ?>
        <tr class="mpai-field mpai-field-<?php echo esc_attr($field['type']); ?> <?php echo esc_attr($field_class); ?>">
            <th scope="row">
                <label for="<?php echo esc_attr($option_name); ?>">
                    <?php echo esc_html($field['title']); ?>
                    <?php if (!empty($tooltip)): ?>
                        <span class="mpai-tooltip dashicons dashicons-editor-help" title="<?php echo esc_attr($tooltip); ?>"></span>
                    <?php endif; ?>
                </label>
            </th>
            <td>
                <div class="mpai-field-wrapper">
                    <?php
                    switch ($field['type']) {
                        case 'text':
                            $this->render_text_field($option_name, $value, $args);
                            break;
                        case 'textarea':
                            $this->render_textarea_field($option_name, $value, $args);
                            break;
                        case 'checkbox':
                            $this->render_checkbox_field($option_name, $value, $args);
                            break;
                        case 'select':
                            $this->render_select_field($option_name, $value, $args);
                            break;
                        case 'radio':
                            $this->render_radio_field($option_name, $value, $args);
                            break;
                        case 'color':
                            $this->render_color_field($option_name, $value, $args);
                            break;
                        case 'custom':
                            if (isset($args['render_callback']) && is_callable($args['render_callback'])) {
                                call_user_func($args['render_callback'], $option_name, $value, $args);
                            } else {
                                echo '<p class="description">' . 
                                     esc_html__('Custom field has no render callback.', 'memberpress-ai-assistant') . 
                                     '</p>';
                            }
                            break;
                        default:
                            echo '<p class="description">' . 
                                 esc_html__('Unknown field type.', 'memberpress-ai-assistant') . 
                                 '</p>';
                            break;
                    }
                    
                    // Show description if available
                    if (!empty($description)) {
                        echo '<p class="description">' . wp_kses_post($description) . '</p>';
                    }
                    
                    // Show detailed help if available
                    if (!empty($help)) {
                        ?>
                        <div class="mpai-field-help">
                            <a href="#" class="mpai-toggle-help"><?php _e('Show more help', 'memberpress-ai-assistant'); ?></a>
                            <div class="mpai-help-content" style="display: none;">
                                <?php echo wp_kses_post($help); ?>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </td>
        </tr>
        <?php
    }

    /**
     * Render a text field
     * 
     * @param string $name  Field name
     * @param mixed  $value Field value
     * @param array  $args  Field arguments
     */
    private function render_text_field($name, $value, $args) {
        $placeholder = isset($args['placeholder']) ? $args['placeholder'] : '';
        $class = isset($args['class']) ? $args['class'] : 'regular-text';
        
        ?>
        <input type="text" 
               name="<?php echo esc_attr($name); ?>" 
               id="<?php echo esc_attr($name); ?>" 
               value="<?php echo esc_attr($value); ?>" 
               class="<?php echo esc_attr($class); ?>" 
               placeholder="<?php echo esc_attr($placeholder); ?>"
               <?php if (isset($args['required']) && $args['required']) echo 'required'; ?>>
        <?php
    }

    /**
     * Render a textarea field
     * 
     * @param string $name  Field name
     * @param mixed  $value Field value
     * @param array  $args  Field arguments
     */
    private function render_textarea_field($name, $value, $args) {
        $placeholder = isset($args['placeholder']) ? $args['placeholder'] : '';
        $rows = isset($args['rows']) ? $args['rows'] : 5;
        $cols = isset($args['cols']) ? $args['cols'] : 50;
        
        ?>
        <textarea name="<?php echo esc_attr($name); ?>" 
                  id="<?php echo esc_attr($name); ?>" 
                  rows="<?php echo esc_attr($rows); ?>" 
                  cols="<?php echo esc_attr($cols); ?>" 
                  placeholder="<?php echo esc_attr($placeholder); ?>"
                  <?php if (isset($args['required']) && $args['required']) echo 'required'; ?>><?php echo esc_textarea($value); ?></textarea>
        <?php
    }

    /**
     * Render a checkbox field
     * 
     * @param string $name  Field name
     * @param mixed  $value Field value
     * @param array  $args  Field arguments
     */
    private function render_checkbox_field($name, $value, $args) {
        $checked = !empty($value);
        $label = isset($args['checkbox_label']) ? $args['checkbox_label'] : '';
        
        ?>
        <label for="<?php echo esc_attr($name); ?>">
            <input type="checkbox" 
                   name="<?php echo esc_attr($name); ?>" 
                   id="<?php echo esc_attr($name); ?>" 
                   value="1" 
                   <?php checked($checked); ?>>
            <?php echo esc_html($label); ?>
        </label>
        <?php
    }

    /**
     * Render a select field
     * 
     * @param string $name  Field name
     * @param mixed  $value Field value
     * @param array  $args  Field arguments
     */
    private function render_select_field($name, $value, $args) {
        if (!isset($args['options']) || !is_array($args['options'])) {
            echo '<p class="description">' . 
                 esc_html__('No options provided for select field.', 'memberpress-ai-assistant') . 
                 '</p>';
            return;
        }
        
        ?>
        <select name="<?php echo esc_attr($name); ?>" 
                id="<?php echo esc_attr($name); ?>"
                <?php if (isset($args['required']) && $args['required']) echo 'required'; ?>>
            <?php
            foreach ($args['options'] as $option_value => $option_label) {
                ?>
                <option value="<?php echo esc_attr($option_value); ?>" 
                        <?php selected($value, $option_value); ?>>
                    <?php echo esc_html($option_label); ?>
                </option>
                <?php
            }
            ?>
        </select>
        <?php
    }

    /**
     * Render a radio field
     * 
     * @param string $name  Field name
     * @param mixed  $value Field value
     * @param array  $args  Field arguments
     */
    private function render_radio_field($name, $value, $args) {
        if (!isset($args['options']) || !is_array($args['options'])) {
            echo '<p class="description">' . 
                 esc_html__('No options provided for radio field.', 'memberpress-ai-assistant') . 
                 '</p>';
            return;
        }
        
        foreach ($args['options'] as $option_value => $option_label) {
            ?>
            <label>
                <input type="radio" 
                       name="<?php echo esc_attr($name); ?>" 
                       value="<?php echo esc_attr($option_value); ?>" 
                       <?php checked($value, $option_value); ?>>
                <?php echo esc_html($option_label); ?>
            </label>
            <br>
            <?php
        }
    }

    /**
     * Render a color field
     * 
     * @param string $name  Field name
     * @param mixed  $value Field value
     * @param array  $args  Field arguments
     */
    private function render_color_field($name, $value, $args) {
        // Ensure color picker scripts are enqueued
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        
        // Add initialization script
        wp_add_inline_script('wp-color-picker', '
            jQuery(document).ready(function($) {
                $(".mpai-color-picker").wpColorPicker();
            });
        ');
        
        ?>
        <input type="text" 
               name="<?php echo esc_attr($name); ?>" 
               id="<?php echo esc_attr($name); ?>" 
               value="<?php echo esc_attr($value); ?>" 
               class="mpai-color-picker" 
               data-default-color="<?php echo isset($args['default']) ? esc_attr($args['default']) : ''; ?>">
        <?php
    }

    /**
     * Get all registered tabs
     * 
     * @return array Registered tabs
     */
    public function get_tabs() {
        return $this->tabs;
    }

    /**
     * Get all registered settings groups
     * 
     * @return array Registered settings groups
     */
    public function get_settings_groups() {
        return $this->settings_groups;
    }

    /**
     * Get all registered settings
     * 
     * @return array Registered settings
     */
    public function get_settings() {
        return $this->settings;
    }
}