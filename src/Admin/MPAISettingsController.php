<?php
/**
 * MemberPress AI Assistant Settings Controller
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Admin;

use MemberpressAiAssistant\Abstracts\AbstractService;
// Add missing use statements for dependencies
use MemberpressAiAssistant\Admin\MPAISettingsStorage;
use MemberpressAiAssistant\Admin\MPAISettingsValidator;
use MemberpressAiAssistant\Admin\MPAIAdminMenu;
use MemberpressAiAssistant\Admin\MPAIConsentManager;
use MemberpressAiAssistant\Interfaces\SettingsProviderInterface;
use MemberpressAiAssistant\Interfaces\SettingsCoordinatorInterface;

/**
 * Class for handling MemberPress AI Assistant settings page and tabs
 * 
 * This class manages the settings page, registers settings with WordPress Settings API,
 * and handles form submissions for the MemberPress AI Assistant plugin.
 */
class MPAISettingsController extends AbstractService implements SettingsProviderInterface {
    /**
     * Settings storage instance
     *
     * @var MPAISettingsStorage
     */
    protected $settings_storage;

    /**
     * Settings validator instance
     *
     * @var MPAISettingsValidator
     */
    protected $settings_validator;

    /**
     * Admin menu instance
     *
     * @var MPAIAdminMenu
     */
    protected $admin_menu;

    /**
     * Settings coordinator instance
     *
     * @var SettingsCoordinatorInterface
     */
    protected $settings_coordinator;
    
    /**
     * Consent manager instance
     *
     * @var MPAIConsentManager
     */
    protected $consent_manager;

    /**
     * Settings page slug
     *
     * @var string
     */
    protected $page_slug = 'mpai-settings';

    /**
     * Settings tabs
     *
     * @var array
     */
    protected $tabs = [];

    /**
     * Constructor
     *
     * @param MPAISettingsStorage $settings_storage Settings storage instance
     * @param MPAISettingsValidator $settings_validator Settings validator instance
     * @param MPAIAdminMenu $admin_menu Admin menu instance
     * @param mixed $logger Logger instance
     */
    public function __construct(
        MPAISettingsStorage $settings_storage,
        MPAISettingsValidator $settings_validator,
        MPAIAdminMenu $admin_menu,
        $logger = null
    ) {
        // Call parent constructor with a default name or determine dynamically
        parent::__construct('settings_controller', $logger);

        // Assign dependencies from constructor
        $this->settings_storage = $settings_storage;
        $this->settings_validator = $settings_validator;
        $this->admin_menu = $admin_menu;
        
        // Get the consent manager instance
        $this->consent_manager = MPAIConsentManager::getInstance();
        
        // Define tabs
        $this->tabs = [
            'general' => \__('General', 'memberpress-ai-assistant'),
            'api' => \__('API Settings', 'memberpress-ai-assistant'),
            'chat' => \__('Chat Settings', 'memberpress-ai-assistant'),
            'access' => \__('Access Control', 'memberpress-ai-assistant'),
            'consent' => \__('Consent', 'memberpress-ai-assistant'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function register($container): void {
        // Register this service with the container using its class name
        // The ServiceProvider already handles aliasing if needed via determineServiceName
        $container->singleton(self::class, function() {
            return $this;
        });

        // Add dependencies to the dependencies array
        $this->dependencies = [
            'settings_coordinator',
        ];

        // Log registration
        $this->log('Settings controller service registered');
    }

    /**
     * {@inheritdoc}
     */
    public function boot(): void {
        parent::boot();
        
        // Add hooks
        $this->addHooks();
        
        // Log boot
        $this->log('Settings controller service booted');
    }
    
    /**
     * Set the settings coordinator
     *
     * @param SettingsCoordinatorInterface $settings_coordinator Settings coordinator instance
     * @return void
     */
    public function set_settings_coordinator(SettingsCoordinatorInterface $settings_coordinator): void {
        $this->settings_coordinator = $settings_coordinator;
    }

    /**
     * Add hooks and filters for this service
     *
     * @return void
     */
    protected function addHooks(): void {
        // Register settings
        \add_action('admin_init', [$this, 'register_settings']);
        
        // Handle form submissions
        \add_action('admin_post_mpai_update_settings', [$this, 'handle_form_submission']);
        
        // Handle reset all consents action
        \add_action('admin_init', [$this, 'handle_reset_all_consents']);
        
        // Register consent preview page
        \add_action('admin_menu', [$this, 'register_consent_preview_page'], 30);
    }

    /**
     * Register settings with WordPress Settings API
     *
     * @return void
     */
    public function register_settings(): void {
        // Ensure settings_storage is available before using it
        if (!$this->settings_storage) {
            // Optionally, add a WordPress admin notice here
            \add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>MemberPress AI Assistant Error: Settings Storage service failed to load. Settings cannot be registered.</p></div>';
            });
            return; // Prevent fatal error
        }
        
        // Register setting
        \register_setting(
            $this->page_slug,
            'mpai_settings',
            [
                'sanitize_callback' => [$this, 'sanitize_settings'],
                'default' => $this->settings_storage->get_all_settings(),
            ]
        );

        // Check if coordinator is available
        if (!$this->settings_coordinator) {
            $this->log('Error: Settings coordinator not available when registering settings', 'error');
            return;
        }
        
        try {
            // Get renderer from coordinator
            $renderer = $this->settings_coordinator->getRenderer();
            
            // Register General section
            \add_settings_section(
                'mpai_general_section',
                \__('General Settings', 'memberpress-ai-assistant'),
                [$renderer, 'render_general_section'],
                $this->page_slug
            );
        
        // Register API Settings section
        $this->register_api_settings_section();
        
        // Register Consent Settings section
        $this->register_consent_settings_section();

            // Register Chat Settings section
            \add_settings_section(
                'mpai_chat_section',
                \__('Chat Interface Settings', 'memberpress-ai-assistant'),
                [$renderer, 'render_chat_section'],
                $this->page_slug
            );

            // Register Access Control section
            \add_settings_section(
                'mpai_access_section',
                \__('Access Control Settings', 'memberpress-ai-assistant'),
                [$renderer, 'render_access_section'],
                $this->page_slug
            );

            // Add fields to General section
            \add_settings_field(
                'mpai_chat_enabled',
                \__('Enable Chat Interface', 'memberpress-ai-assistant'),
                [$renderer, 'render_chat_enabled_field'],
                $this->page_slug,
                'mpai_general_section'
            );

            // Add fields to Chat Settings section
            \add_settings_field(
                'mpai_chat_location',
                \__('Chat Interface Location', 'memberpress-ai-assistant'),
                [$renderer, 'render_chat_location_field'],
                $this->page_slug,
                'mpai_chat_section'
            );

            \add_settings_field(
                'mpai_chat_position',
                \__('Chat Interface Position', 'memberpress-ai-assistant'),
                [$renderer, 'render_chat_position_field'],
                $this->page_slug,
                'mpai_chat_section'
            );

            // Add fields to Access Control section
            \add_settings_field(
                'mpai_user_roles',
                \__('User Roles with Access', 'memberpress-ai-assistant'),
                [$renderer, 'render_user_roles_field'],
                $this->page_slug,
                'mpai_access_section'
            );
        } catch (\Exception $e) {
            $this->log('Error getting renderer when registering settings: ' . $e->getMessage(), 'error');
            \add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>MemberPress AI Assistant Error: Could not register settings properly. Settings page may not function correctly.</p></div>';
            });
        }
    }

    /**
     * Render the settings page
     *
     * @return void
     */
    public function render_settings_page(): void {
        // Check if coordinator is available
        if (!$this->settings_coordinator) {
            $this->log('Error: Settings coordinator not available in controller', 'error');
            echo '<div class="notice notice-error"><p>';
            echo esc_html__('Error: Settings coordinator not available. Please try again later or contact support.', 'memberpress-ai-assistant');
            echo '</p></div>';
            return;
        }
        
        // Get renderer from coordinator
        $renderer = $this->settings_coordinator->getRenderer();
        
        // Delegate to the renderer
        if ($renderer) {
            try {
                $renderer->render_settings_page();
            } catch (\Exception $e) {
                $this->log('Error during settings page rendering: ' . $e->getMessage(), 'error');
                echo '<div class="notice notice-error"><p>';
                echo esc_html__('An error occurred while rendering the settings page. Please try again later or contact support.', 'memberpress-ai-assistant');
                echo '</p></div>';
            }
        } else {
            // Fallback if renderer not available
            $this->log('Renderer not available, using fallback', 'warning');
            $this->render_fallback_settings_page();
        }
    }

    /**
     * Render a fallback settings page when the renderer is not available
     *
     * @return void
     */
    protected function render_fallback_settings_page(): void {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('MemberPress AI Assistant Settings', 'memberpress-ai-assistant') . '</h1>';
        echo '<div class="notice notice-warning"><p>';
        echo esc_html__('The settings renderer is not available. Basic functionality is provided.', 'memberpress-ai-assistant');
        echo '</p></div>';
        
        // Display basic settings form if storage is available
        if ($this->settings_storage) {
            // Basic settings form implementation
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            echo '<input type="hidden" name="action" value="mpai_update_settings" />';
            wp_nonce_field($this->get_page_slug() . '-options');
            
            // Display some basic settings
            echo '<table class="form-table" role="presentation">';
            // ... basic settings fields ...
            echo '</table>';
            
            echo '<p class="submit">';
            echo '<input type="submit" name="submit" id="submit" class="button button-primary" value="' .
                esc_attr__('Save Changes', 'memberpress-ai-assistant') . '" />';
            echo '</p>';
            echo '</form>';
        } else {
            echo '<p>' . esc_html__('Settings storage is not available. Please try again later or contact support.', 'memberpress-ai-assistant') . '</p>';
        }
        
        echo '</div>';
    }

    /**
     * Enhanced logging method
     *
     * @param string $message The message to log
     * @param string $level The log level (info, warning, error)
     * @return void
     */
    protected function log($message, $level = 'info'): void {
        if ($this->logger) {
            if ($level === 'error') {
                $this->logger->error($message);
            } else if ($level === 'warning') {
                $this->logger->warning($message);
            } else {
                $this->logger->info($message);
            }
        }
    }

    // Rendering methods have been moved to MPAISettingsRenderer

    /**
     * Sanitize settings before saving
     *
     * @param array $input The settings input
     * @return array The sanitized settings
     */
    public function sanitize_settings($input): array {
        // Use the settings validator to validate and sanitize the input
        $sanitized_input = $this->settings_validator->validate_settings($input);
        
        // Update settings in the storage
        $this->settings_storage->update_settings($sanitized_input);
        
        // Log settings update
        $this->log('Settings updated', ['settings' => $sanitized_input]);
        
        return $sanitized_input;
    }

    /**
     * Handle form submission
     *
     * @return void
     */
    public function handle_form_submission(): void {
        // Check nonce
        if (!isset($_POST['_wpnonce']) || !\wp_verify_nonce($_POST['_wpnonce'], $this->page_slug . '-options')) {
            \wp_die(\__('Security check failed. Please try again.', 'memberpress-ai-assistant'));
        }
        
        // Check permissions
        if (!\current_user_can('manage_options')) {
            \wp_die(\__('You do not have sufficient permissions to access this page.', 'memberpress-ai-assistant'));
        }
        
        // Get settings from POST
        $settings = isset($_POST['mpai_settings']) ? $_POST['mpai_settings'] : [];
        
        // Sanitize and save settings
        $sanitized_settings = $this->sanitize_settings($settings);
        
        // Trigger action for other components to react to settings update
        \do_action('memberpress_ai_assistant_update_settings', $sanitized_settings);
        
        // Redirect back to settings page with success message
        \wp_redirect(\add_query_arg(['page' => 'mpai-settings', 'settings-updated' => 'true'], \admin_url('admin.php')));
        exit;
    }

    /**
     * Get the tabs
     *
     * @return array The tabs
     */
    public function get_tabs(): array {
        return $this->tabs;
    }

    /**
     * Get the page slug
     *
     * @return string The page slug
     */
    public function get_page_slug(): string {
        return $this->page_slug;
    }
    
    // Removed redundant render_section method as it's already implemented in MPAISettingsRenderer
    
    /**
     * Register API settings section and fields
     *
     * @return void
     */
    protected function register_api_settings_section(): void {
        // Check if coordinator is available
        if (!$this->settings_coordinator) {
            $this->log('Error: Settings coordinator not available when registering API settings', 'error');
            return;
        }
        
        try {
            // Get renderer from coordinator
            $renderer = $this->settings_coordinator->getRenderer();
            
            // Register API Settings section
            \add_settings_section(
                'mpai_api_section',
                \__('API Settings', 'memberpress-ai-assistant'),
                [$renderer, 'render_api_section'],
                $this->page_slug
            );
            
            // Add OpenAI API Key field
            \add_settings_field(
                'mpai_openai_api_key',
                \__('OpenAI API Key', 'memberpress-ai-assistant'),
                [$renderer, 'render_openai_api_key_field'],
                $this->page_slug,
                'mpai_api_section'
            );
        
            // Add OpenAI Model field
            \add_settings_field(
                'mpai_openai_model',
                \__('OpenAI Model', 'memberpress-ai-assistant'),
                [$renderer, 'render_openai_model_field'],
                $this->page_slug,
                'mpai_api_section'
            );
            
            // Add OpenAI Temperature field
            \add_settings_field(
                'mpai_openai_temperature',
                \__('OpenAI Temperature', 'memberpress-ai-assistant'),
                [$renderer, 'render_openai_temperature_field'],
                $this->page_slug,
                'mpai_api_section'
            );
            
            // Add OpenAI Max Tokens field
            \add_settings_field(
                'mpai_openai_max_tokens',
                \__('OpenAI Max Tokens', 'memberpress-ai-assistant'),
                [$renderer, 'render_openai_max_tokens_field'],
                $this->page_slug,
                'mpai_api_section'
            );
            
            // Add Anthropic API Key field
            \add_settings_field(
                'mpai_anthropic_api_key',
                \__('Anthropic API Key', 'memberpress-ai-assistant'),
                [$renderer, 'render_anthropic_api_key_field'],
                $this->page_slug,
                'mpai_api_section'
            );
            
            // Add Anthropic Model field
            \add_settings_field(
                'mpai_anthropic_model',
                \__('Anthropic Model', 'memberpress-ai-assistant'),
                [$renderer, 'render_anthropic_model_field'],
                $this->page_slug,
                'mpai_api_section'
            );
            
            // Add Anthropic Temperature field
            \add_settings_field(
                'mpai_anthropic_temperature',
                \__('Anthropic Temperature', 'memberpress-ai-assistant'),
                [$renderer, 'render_anthropic_temperature_field'],
                $this->page_slug,
                'mpai_api_section'
            );
            
            // Add Anthropic Max Tokens field
            \add_settings_field(
                'mpai_anthropic_max_tokens',
                \__('Anthropic Max Tokens', 'memberpress-ai-assistant'),
                [$renderer, 'render_anthropic_max_tokens_field'],
                $this->page_slug,
                'mpai_api_section'
            );
            
            // Add Primary AI Provider field
            \add_settings_field(
                'mpai_primary_api',
                \__('Primary AI Provider', 'memberpress-ai-assistant'),
                [$renderer, 'render_primary_api_field'],
                $this->page_slug,
                'mpai_api_section'
            );
        } catch (\Exception $e) {
            $this->log('Error getting renderer when registering API settings: ' . $e->getMessage(), 'error');
        }
    }
    
    /**
     * Register consent settings section and fields
     *
     * @return void
     */
    protected function register_consent_settings_section(): void {
        // Check if coordinator is available
        if (!$this->settings_coordinator) {
            $this->log('Error: Settings coordinator not available when registering consent settings', 'error');
            return;
        }
        
        try {
            // Get renderer from coordinator
            $renderer = $this->settings_coordinator->getRenderer();
            
            // Register Consent Settings section
            \add_settings_section(
                'mpai_consent_section',
                \__('Consent Settings', 'memberpress-ai-assistant'),
                [$renderer, 'render_consent_section'],
                $this->page_slug
            );
            
            // Add Consent Required field
            \add_settings_field(
                'mpai_consent_required',
                \__('Require User Consent', 'memberpress-ai-assistant'),
                [$renderer, 'render_consent_required_field'],
                $this->page_slug,
                'mpai_consent_section'
            );
            
            // Add Consent Form Preview field
            \add_settings_field(
                'mpai_consent_form_preview',
                \__('Consent Form Preview', 'memberpress-ai-assistant'),
                [$renderer, 'render_consent_form_preview_field'],
                $this->page_slug,
                'mpai_consent_section'
            );
            
            // Add Reset All Consents field
            \add_settings_field(
                'mpai_reset_all_consents',
                \__('Reset All User Consents', 'memberpress-ai-assistant'),
                [$renderer, 'render_reset_all_consents_field'],
                $this->page_slug,
                'mpai_consent_section'
            );
        } catch (\Exception $e) {
            $this->log('Error getting renderer when registering consent settings: ' . $e->getMessage(), 'error');
        }
    }
    
    /**
     * Handle reset all consents action
     *
     * @return void
     */
    public function handle_reset_all_consents(): void {
        // Check if we're on the settings page and the reset action is requested
        if (!isset($_GET['page']) || $_GET['page'] !== 'mpai-settings' ||
            !isset($_GET['action']) || $_GET['action'] !== 'mpai_reset_all_consents' ||
            !isset($_GET['tab']) || $_GET['tab'] !== 'consent') {
            return;
        }
        
        // Verify nonce
        if (!isset($_GET['_wpnonce']) || !\wp_verify_nonce($_GET['_wpnonce'], 'mpai_reset_all_consents_nonce')) {
            \add_settings_error(
                'mpai_messages',
                'mpai_reset_consents_error',
                \__('Security check failed. Please try again.', 'memberpress-ai-assistant'),
                'error'
            );
            return;
        }
        
        // Check permissions
        if (!\current_user_can('manage_options')) {
            \add_settings_error(
                'mpai_messages',
                'mpai_reset_consents_error',
                \__('You do not have sufficient permissions to perform this action.', 'memberpress-ai-assistant'),
                'error'
            );
            return;
        }
        
        // Reset all consents
        MPAIConsentManager::resetAllConsents();
        
        // Add success message
        \add_settings_error(
            'mpai_messages',
            'mpai_reset_consents_success',
            \__('All user consents have been reset successfully.', 'memberpress-ai-assistant'),
            'updated'
        );
        
        // Redirect to remove the action from the URL
        \wp_redirect(\add_query_arg([
            'page' => 'mpai-settings',
            'tab' => 'consent',
            'settings-updated' => 'true'
        ], \admin_url('admin.php')));
        exit;
    }
    
    /**
     * Register consent preview page
     *
     * This adds a hidden admin page that displays the consent form for preview purposes
     *
     * @return void
     */
    public function register_consent_preview_page(): void {
        // Add a hidden submenu page for the consent form preview
        \add_submenu_page(
            null, // No parent menu
            \__('Consent Form Preview', 'memberpress-ai-assistant'),
            \__('Consent Form Preview', 'memberpress-ai-assistant'),
            'manage_options',
            'mpai-consent-preview',
            [$this, 'render_consent_preview_page']
        );
    }
    
    /**
     * Render the consent preview page
     *
     * @return void
     */
    public function render_consent_preview_page(): void {
        // Get the consent manager instance
        $consent_manager = MPAIConsentManager::getInstance();
        
        // Output minimal header
        echo '<!DOCTYPE html>';
        echo '<html>';
        echo '<head>';
        echo '<meta charset="utf-8">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
        echo '<title>' . \esc_html__('Consent Form Preview', 'memberpress-ai-assistant') . '</title>';
        \wp_head();
        echo '</head>';
        echo '<body class="mpai-consent-preview-body">';
        
        // Render the consent form
        $consent_manager->renderConsentForm();
        
        // Output minimal footer
        \wp_footer();
        echo '</body>';
        echo '</html>';
        exit;
    }
}