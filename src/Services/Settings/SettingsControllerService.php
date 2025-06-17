<?php
/**
 * Settings Controller Service
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Services\Settings;

use MemberpressAiAssistant\Abstracts\AbstractService;
use MemberpressAiAssistant\Interfaces\ServiceInterface;
use MemberpressAiAssistant\Interfaces\SettingsControllerInterface;
use MemberpressAiAssistant\DI\ServiceLocator;

/**
 * Service for handling MemberPress Copilot settings page and tabs
 * 
 * This class coordinates between the Model and View components,
 * handles WordPress hooks and user interactions, and contains
 * business logic for the MemberPress Copilot settings.
 * 
 * It adapts the original MPAISettingsController to work with the DI system.
 */
class SettingsControllerService extends AbstractService implements ServiceInterface, SettingsControllerInterface {
    /**
     * Settings model instance
     *
     * @var SettingsModelService
     */
    private $model;

    /**
     * Settings view instance
     *
     * @var SettingsViewService
     */
    private $view;

    /**
     * Settings page slug
     *
     * @var string
     */
    private $page_slug = 'mpai-settings';

    /**
     * Settings tabs
     *
     * @var array
     */
    private $tabs = [];

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
    public function __construct(string $name = 'settings.controller', $logger = null) {
        parent::__construct($name, $logger);
        
        // Set dependencies
        $this->dependencies = ['logger', 'settings.model', 'settings.view'];
        
        // Define tabs
        $this->tabs = [
            'general' => __('General', 'memberpress-ai-assistant'),
            'chat' => __('Chat Settings', 'memberpress-ai-assistant'),
            'access' => __('Access Control', 'memberpress-ai-assistant'),
        ];
    }

    /**
     * Register the service with the service locator
     *
     * @param ServiceLocator $serviceLocator The service locator
     * @return void
     */
    public function register($serviceLocator): void {
        $this->log('Registering settings controller service');
        
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
        
        // Get dependencies from service locator
        $this->model = $this->serviceLocator->get('settings.model');
        $this->view = $this->serviceLocator->get('settings.view');
        
        if (!$this->model || !$this->view) {
            if ($this->logger) {
                $this->logger->error('Failed to get required dependencies for settings controller service');
            }
            return;
        }
        
        // Initialize the controller
        $this->init();
    }

    /**
     * Initialize the controller
     *
     * @return void
     */
    public function init() {
        // Register settings
        add_action('admin_init', [$this, 'register_settings']);
        
        // Handle form submissions
        add_action('admin_post_mpai_update_settings', [$this, 'handle_form_submission']);
        
        // Enqueue scripts and styles
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        
        $this->log('Settings controller initialized');
    }

    /**
     * Render the settings page
     *
     * @return void
     */
    public function render_page(): void {
        try {
            // Add comprehensive logging
            error_log('[MPAI Debug] SettingsController: render_page() called');
            error_log('[MPAI Debug] SettingsController: Current page: ' . (isset($_GET['page']) ? $_GET['page'] : 'none'));
            error_log('[MPAI Debug] SettingsController: Current screen: ' . (get_current_screen() ? get_current_screen()->id : 'unknown'));
            
            error_log('[MPAI Debug] SettingsController: Authenticated admin user accessing settings');
            
            // Get current tab
            $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
            error_log('[MPAI Debug] SettingsController: Current tab: ' . $current_tab);
            
            // Render the page
            error_log('[MPAI Debug] SettingsController: About to render settings page view...');
            $this->view->render_page($current_tab, $this->tabs, $this->page_slug, $this->model);
            error_log('[MPAI Debug] SettingsController: Settings page view rendered successfully');
            
        } catch (\Exception $e) {
            error_log('[MPAI Debug] SettingsController: Exception in render_page: ' . $e->getMessage());
            $this->log_error('Error rendering settings page: ' . $e->getMessage());
            $this->view->render_error(__('An error occurred while rendering the settings page. Please try again later or contact support.', 'memberpress-ai-assistant'));
        }
    }

    /**
     * Register settings with WordPress Settings API
     *
     * @return void
     */
    public function register_settings(): void {
        // Register setting
        register_setting(
            $this->page_slug,
            'mpai_settings',
            [
                'sanitize_callback' => [$this, 'sanitize_settings'],
                'default' => $this->model->get_all(),
            ]
        );
        
        // Register sections and fields
        $this->register_general_section();
        $this->register_chat_section();
        $this->register_access_section();
    }

    /**
     * Register general section and fields
     *
     * @return void
     */
    protected function register_general_section() {
        // Register General section
        add_settings_section(
            'mpai_general_section',
            __('General Settings', 'memberpress-ai-assistant'),
            [$this->view, 'render_general_section'],
            $this->page_slug
        );
        
        // Add fields to General section
        add_settings_field(
            'mpai_chat_enabled',
            __('Enable Chat Interface', 'memberpress-ai-assistant'),
            [$this, 'render_chat_enabled_field'],
            $this->page_slug,
            'mpai_general_section'
        );

        add_settings_field(
            'mpai_log_level',
            __('Log Level', 'memberpress-ai-assistant'),
            [$this, 'render_log_level_field'],
            $this->page_slug,
            'mpai_general_section'
        );
    }


    /**
     * Register chat section and fields
     *
     * @return void
     */
    protected function register_chat_section() {
        // Register Chat Settings section
        add_settings_section(
            'mpai_chat_section',
            __('Chat Interface Settings', 'memberpress-ai-assistant'),
            [$this->view, 'render_chat_section'],
            $this->page_slug
        );
        
        // Add fields to Chat Settings section
        add_settings_field(
            'mpai_chat_location',
            __('Chat Interface Location', 'memberpress-ai-assistant'),
            [$this, 'render_chat_location_field'],
            $this->page_slug,
            'mpai_chat_section'
        );

        add_settings_field(
            'mpai_chat_position',
            __('Chat Interface Position', 'memberpress-ai-assistant'),
            [$this, 'render_chat_position_field'],
            $this->page_slug,
            'mpai_chat_section'
        );
    }

    /**
     * Register access section and fields
     *
     * @return void
     */
    protected function register_access_section() {
        // Register Access Control section
        add_settings_section(
            'mpai_access_section',
            __('Access Control Settings', 'memberpress-ai-assistant'),
            [$this->view, 'render_access_section'],
            $this->page_slug
        );
        
        // Add fields to Access Control section
        add_settings_field(
            'mpai_user_roles',
            __('User Roles with Access', 'memberpress-ai-assistant'),
            [$this, 'render_user_roles_field'],
            $this->page_slug,
            'mpai_access_section'
        );
    }


    /**
     * Handle form submission
     *
     * @return void
     */
    public function handle_form_submission(): void {
        // Check nonce
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], $this->page_slug . '-options')) {
            wp_die(__('Security check failed. Please try again.', 'memberpress-ai-assistant'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'memberpress-ai-assistant'));
        }
        
        // Get settings from POST
        $settings = isset($_POST['mpai_settings']) ? $_POST['mpai_settings'] : [];
        
        // Update settings
        $this->model->update($settings);
        
        // Trigger action for other components to react to settings update
        do_action('memberpress_ai_assistant_update_settings', $settings);
        
        // Redirect back to settings page with success message
        wp_redirect(add_query_arg(['page' => 'mpai-settings', 'settings-updated' => 'true'], admin_url('admin.php')));
        exit;
    }

    /**
     * Sanitize settings before saving
     *
     * @param array $input The settings input
     * @return array The sanitized settings
     */
    public function sanitize_settings(array $input): array {
        // The model handles validation and sanitization
        return $input;
    }




    /**
     * Render the chat enabled field
     *
     * @return void
     */
    public function render_chat_enabled_field(): void {
        $value = $this->model->is_chat_enabled();
        $this->view->render_chat_enabled_field($value);
    }

    /**
     * Render the log level field
     *
     * @return void
     */
    public function render_log_level_field(): void {
        $value = $this->model->get_log_level();
        $this->view->render_log_level_field($value);
    }

    /**
     * Render the chat location field
     *
     * @return void
     */
    public function render_chat_location_field(): void {
        $value = $this->model->get_chat_location();
        $this->view->render_chat_location_field($value);
    }

    /**
     * Render the chat position field
     *
     * @return void
     */
    public function render_chat_position_field(): void {
        $value = $this->model->get_chat_position();
        $this->view->render_chat_position_field($value);
    }

    /**
     * Render the user roles field
     *
     * @return void
     */
    public function render_user_roles_field(): void {
        $value = $this->model->get_user_roles();
        $this->view->render_user_roles_field($value);
    }



    /**
     * Get the settings tabs
     *
     * @return array
     */
    public function get_tabs(): array {
        return $this->tabs;
    }

    /**
     * Get the settings page slug
     *
     * @return string
     */
    public function get_page_slug(): string {
        return $this->page_slug;
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
     * Enqueue scripts and styles for the settings page
     *
     * @param string $hook_suffix The current admin page
     * @return void
     */
    public function enqueue_scripts($hook_suffix) {
        // Only enqueue on the settings page
        if ($hook_suffix !== 'memberpress_page_mpai-settings') {
            return;
        }
        
        // Enqueue settings CSS
        wp_enqueue_style(
            'mpai-settings',
            MPAI_PLUGIN_URL . 'assets/css/settings.css',
            [],
            MPAI_VERSION
        );
        
        // Enqueue settings JS
        wp_enqueue_script(
            'mpai-settings',
            MPAI_PLUGIN_URL . 'assets/js/settings.js',
            ['jquery'],
            MPAI_VERSION,
            true
        );
        
        // Add settings data
        wp_localize_script('mpai-settings', 'mpai_settings', [
            'nonce' => wp_create_nonce('mpai_settings_nonce'),
            'ajaxurl' => admin_url('admin-ajax.php')
        ]);
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