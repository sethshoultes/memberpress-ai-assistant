<?php
/**
 * Settings View Service
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Services\Settings;

use MemberpressAiAssistant\Abstracts\AbstractService;
use MemberpressAiAssistant\Interfaces\ServiceInterface;
use MemberpressAiAssistant\Interfaces\SettingsViewInterface;
use MemberpressAiAssistant\Interfaces\SettingsModelInterface;
use MemberpressAiAssistant\DI\ServiceLocator;

/**
 * Service for rendering MemberPress AI Assistant settings UI components
 * 
 * This class handles the rendering of the settings page, tabs, and form fields
 * for the MemberPress AI Assistant plugin. It receives all data through method
 * parameters and contains no data fetching or business logic.
 * 
 * It adapts the original MPAISettingsView to work with the DI system.
 */
class SettingsViewService extends AbstractService implements ServiceInterface, SettingsViewInterface {
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
     * Whether service is in degraded mode
     *
     * @var bool
     */
    protected $degradedMode = false;

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
        
        // Validate dependencies before proceeding
        if (!$this->validateDependencies()) {
            $this->log('Service booted in degraded mode due to missing dependencies');
            return;
        }
        
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
     * @param SettingsModelService $model Settings model
     * @return void
     */
    public function render_page(string $current_tab, array $tabs, string $page_slug, SettingsModelInterface $model): void {
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
    public function render_tabs(string $current_tab, array $tabs): void {
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
     * @param SettingsModelService $model Settings model
     * @return void
     */
    public function render_form(string $current_tab, string $page_slug, SettingsModelInterface $model): void {
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
     * @param SettingsModelService $model Settings model
     * @return void
     */
    public function render_fields($current_tab, $page_slug, $model) {
        echo '<table class="form-table" role="presentation">';
        
        // Output section and fields based on current tab
        switch ($current_tab) {
            case 'general':
                do_settings_sections($page_slug);
                break;
                
            case 'chat':
                $this->render_section('mpai_chat_section', $page_slug);
                break;
                
            case 'access':
                $this->render_section('mpai_access_section', $page_slug);
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
    public function render_error(string $message): void {
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
    public function render_general_section(): void {
        echo '<p>' . esc_html__('Configure general settings for the MemberPress AI Assistant.', 'memberpress-ai-assistant') . '</p>';
    }
    
    /**
     * Render the chat section description
     *
     * @return void
     */
    public function render_chat_section(): void {
        echo '<p>' . esc_html__('Configure how the chat interface appears and behaves.', 'memberpress-ai-assistant') . '</p>';
    }
    
    /**
     * Render the access section description
     *
     * @return void
     */
    public function render_access_section(): void {
        echo '<p>' . esc_html__('Control which user roles can access the AI Assistant chat interface.', 'memberpress-ai-assistant') . '</p>';
    }
    
    
    /**
     * Render the chat enabled field
     *
     * @param bool $value Field value
     * @return void
     */
    public function render_chat_enabled_field(bool $value): void {
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
    public function render_log_level_field(string $value): void {
        $log_levels = [
            'none' => __('None (Disable All Logging)', 'memberpress-ai-assistant'),
            'error' => __('Error (Minimal)', 'memberpress-ai-assistant'),
            'warning' => __('Warning', 'memberpress-ai-assistant'),
            'info' => __('Info (Recommended)', 'memberpress-ai-assistant'),
            'debug' => __('Debug', 'memberpress-ai-assistant'),
            'trace' => __('Trace (Verbose)', 'memberpress-ai-assistant'),
            'minimal' => __('Minimal', 'memberpress-ai-assistant'),
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
                <li><?php esc_html_e('Minimal: Basic logging information', 'memberpress-ai-assistant'); ?></li>
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
    public function render_chat_location_field(string $value): void {
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
    public function render_chat_position_field(string $value): void {
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
    public function render_user_roles_field(array $value): void {
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
}