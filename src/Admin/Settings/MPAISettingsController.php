<?php
/**
 * MemberPress AI Assistant Settings Controller
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Admin\Settings;

use MemberpressAiAssistant\Admin\MPAIConsentManager;

/**
 * Class for handling MemberPress AI Assistant settings page and tabs
 * 
 * This class coordinates between the Model and View components,
 * handles WordPress hooks and user interactions, and contains
 * business logic for the MemberPress AI Assistant settings.
 */
class MPAISettingsController {
    /**
     * Settings model instance
     *
     * @var MPAISettingsModel
     */
    private $model;

    /**
     * Settings view instance
     *
     * @var MPAISettingsView
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
     * Logger instance
     *
     * @var mixed
     */
    private $logger;

    /**
     * Constructor
     *
     * @param MPAISettingsModel $model Settings model instance
     * @param MPAISettingsView $view Settings view instance
     * @param mixed $logger Logger instance
     */
    public function __construct($model, $view, $logger = null) {
        $this->model = $model;
        $this->view = $view;
        $this->logger = $logger;
        
        // Define tabs
        $this->tabs = [
            'general' => __('General', 'memberpress-ai-assistant'),
            'chat' => __('Chat Settings', 'memberpress-ai-assistant'),
            'access' => __('Access Control', 'memberpress-ai-assistant'),
            // Removed consent tab as it's now handled automatically
            // Removed API tab as we now use LiteLLM proxy
        ];
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
        
        // Handle reset all consents action
        add_action('admin_init', [$this, 'handle_reset_all_consents']);
        
        // Enqueue scripts and styles
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        
        if ($this->logger) {
            $this->logger->info('Settings controller initialized');
        }
    }

    /**
     * Render the settings page
     *
     * @return void
     */
    public function render_page() {
        try {
            // Get current tab
            $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
            
            // Render the page
            $this->view->render_page($current_tab, $this->tabs, $this->page_slug, $this->model);
        } catch (\Exception $e) {
            $this->log_error('Error rendering settings page: ' . $e->getMessage());
            $this->view->render_error(__('An error occurred while rendering the settings page. Please try again later or contact support.', 'memberpress-ai-assistant'));
        }
    }

    /**
     * Register settings with WordPress Settings API
     *
     * @return void
     */
    public function register_settings() {
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
        
        // Add log level field
        add_settings_field(
            'mpai_log_level',
            __('Log Level', 'memberpress-ai-assistant'),
            [$this, 'render_log_level_field'],
            $this->page_slug,
            'mpai_general_section'
        );
    }

    // Removed API section - now using LiteLLM proxy with embedded credentials

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
    public function handle_form_submission() {
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
    public function sanitize_settings($input) {
        // The model handles validation and sanitization
        return $input;
    }

    /**
     * Handle reset all consents action
     *
     * @return void
     */
    public function handle_reset_all_consents() {
        // Check if we're on the settings page and the reset action is requested
        if (!isset($_GET['page']) || $_GET['page'] !== 'mpai-settings' ||
            !isset($_GET['action']) || $_GET['action'] !== 'mpai_reset_all_consents') {
            return;
        }
        
        // Verify nonce
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'mpai_reset_all_consents_nonce')) {
            add_settings_error(
                'mpai_messages',
                'mpai_reset_consents_error',
                __('Security check failed. Please try again.', 'memberpress-ai-assistant'),
                'error'
            );
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            add_settings_error(
                'mpai_messages',
                'mpai_reset_consents_error',
                __('You do not have sufficient permissions to perform this action.', 'memberpress-ai-assistant'),
                'error'
            );
            return;
        }
        
        // Reset all consents
        MPAIConsentManager::resetAllConsents();
        
        // Add success message
        add_settings_error(
            'mpai_messages',
            'mpai_reset_consents_success',
            __('All user consents have been reset successfully. Users will need to consent again upon accessing the AI Assistant.', 'memberpress-ai-assistant'),
            'updated'
        );
        
        // Redirect to remove the action from the URL
        wp_redirect(add_query_arg([
            'page' => 'mpai-settings',
            'settings-updated' => 'true'
        ], admin_url('admin.php')));
        exit;
    }

    /**
     * Render the chat enabled field
     *
     * @return void
     */
    public function render_chat_enabled_field() {
        $value = $this->model->is_chat_enabled();
        $this->view->render_chat_enabled_field($value);
    }
    
    /**
     * Render the log level field
     *
     * @return void
     */
    public function render_log_level_field() {
        $value = $this->model->get_log_level();
        $this->view->render_log_level_field($value);
    }

    /**
     * Render the chat location field
     *
     * @return void
     */
    public function render_chat_location_field() {
        $value = $this->model->get_chat_location();
        $this->view->render_chat_location_field($value);
    }

    /**
     * Render the chat position field
     *
     * @return void
     */
    public function render_chat_position_field() {
        $value = $this->model->get_chat_position();
        $this->view->render_chat_position_field($value);
    }

    /**
     * Render the user roles field
     *
     * @return void
     */
    public function render_user_roles_field() {
        $value = $this->model->get_user_roles();
        $this->view->render_user_roles_field($value);
    }

    // Removed API key and provider configuration methods - now using LiteLLM proxy


    /**
     * Get the settings tabs
     *
     * @return array
     */
    public function get_tabs() {
        return $this->tabs;
    }

    /**
     * Get the settings page slug
     *
     * @return string
     */
    public function get_page_slug() {
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
}