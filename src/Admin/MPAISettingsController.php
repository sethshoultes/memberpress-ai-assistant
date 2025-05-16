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
use MemberpressAiAssistant\Admin\MPAISettingsRenderer;

/**
 * Class for handling MemberPress AI Assistant settings page and tabs
 * 
 * This class manages the settings page, registers settings with WordPress Settings API,
 * and handles form submissions for the MemberPress AI Assistant plugin.
 */
class MPAISettingsController extends AbstractService {
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
     * Settings renderer instance
     *
     * @var MPAISettingsRenderer
     */
    protected $settings_renderer;

    /**
     * Settings page slug
     *
     * @var string
     */
    protected $page_slug = 'mpai_settings';

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
     * @param MPAISettingsRenderer $settings_renderer Settings renderer instance
     * @param mixed $logger Logger instance
     */
    public function __construct(
        MPAISettingsStorage $settings_storage,
        MPAISettingsValidator $settings_validator,
        MPAIAdminMenu $admin_menu,
        MPAISettingsRenderer $settings_renderer,
        $logger = null
    ) {
        // Call parent constructor with a default name or determine dynamically
        parent::__construct('settings_controller', $logger);

        // Assign dependencies from constructor
        $this->settings_storage = $settings_storage;
        $this->settings_validator = $settings_validator;
        $this->admin_menu = $admin_menu;
        $this->settings_renderer = $settings_renderer;
        
        // Define tabs
        $this->tabs = [
            'general' => \__('General', 'memberpress-ai-assistant'),
            'api' => \__('API Settings', 'memberpress-ai-assistant'),
            'chat' => \__('Chat Settings', 'memberpress-ai-assistant'),
            'access' => \__('Access Control', 'memberpress-ai-assistant'),
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
     * Add hooks and filters for this service
     *
     * @return void
     */
    protected function addHooks(): void {
        // Register settings
        \add_action('admin_init', [$this, 'register_settings']);
        
        // Override the admin menu render_settings_page method
        \add_action('admin_menu', [$this, 'override_settings_page_render'], 20);
        
        // Handle form submissions
        \add_action('admin_post_mpai_update_settings', [$this, 'handle_form_submission']);
    }

    /**
     * Override the admin menu render_settings_page method
     *
     * @return void
     */
    public function override_settings_page_render(): void {
        // Remove the default render method
        \remove_action('admin_menu', [$this->admin_menu, 'render_settings_page']);
        
        // Add our render method to the admin_menu class
        \add_filter('mpai_render_settings_page', [$this, 'render_settings_page']);
    }

    /**
     * Register settings with WordPress Settings API
     *
     * @return void
     */
    public function register_settings(): void {
        // Ensure settings_storage is available before using it
        if (!$this->settings_storage) {
            $this->log('Error: Settings Storage not available in register_settings', ['level' => 'error']);
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

        // Register General section
        \add_settings_section(
            'mpai_general_section',
            \__('General Settings', 'memberpress-ai-assistant'),
            [$this->settings_renderer, 'render_general_section'],
            $this->page_slug
        );
        
        // Register API Settings section
        $this->register_api_settings_section();

        // Register Chat Settings section
        \add_settings_section(
            'mpai_chat_section',
            \__('Chat Interface Settings', 'memberpress-ai-assistant'),
            [$this->settings_renderer, 'render_chat_section'],
            $this->page_slug
        );

        // Register Access Control section
        \add_settings_section(
            'mpai_access_section',
            \__('Access Control Settings', 'memberpress-ai-assistant'),
            [$this->settings_renderer, 'render_access_section'],
            $this->page_slug
        );

        // Add fields to General section
        \add_settings_field(
            'mpai_chat_enabled',
            \__('Enable Chat Interface', 'memberpress-ai-assistant'),
            [$this->settings_renderer, 'render_chat_enabled_field'],
            $this->page_slug,
            'mpai_general_section'
        );

        // Add fields to Chat Settings section
        \add_settings_field(
            'mpai_chat_location',
            \__('Chat Interface Location', 'memberpress-ai-assistant'),
            [$this->settings_renderer, 'render_chat_location_field'],
            $this->page_slug,
            'mpai_chat_section'
        );

        \add_settings_field(
            'mpai_chat_position',
            \__('Chat Interface Position', 'memberpress-ai-assistant'),
            [$this->settings_renderer, 'render_chat_position_field'],
            $this->page_slug,
            'mpai_chat_section'
        );

        // Add fields to Access Control section
        \add_settings_field(
            'mpai_user_roles',
            \__('User Roles with Access', 'memberpress-ai-assistant'),
            [$this->settings_renderer, 'render_user_roles_field'],
            $this->page_slug,
            'mpai_access_section'
        );
    }

    /**
     * Render the settings page
     *
     * @return void
     */
    public function render_settings_page(): void {
        // Delegate to the renderer
        $this->settings_renderer->render_settings_page();
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
        // Register API Settings section
        \add_settings_section(
            'mpai_api_section',
            \__('API Settings', 'memberpress-ai-assistant'),
            [$this->settings_renderer, 'render_api_section'],
            $this->page_slug
        );
        
        // Add OpenAI API Key field
        \add_settings_field(
            'mpai_openai_api_key',
            \__('OpenAI API Key', 'memberpress-ai-assistant'),
            [$this->settings_renderer, 'render_openai_api_key_field'],
            $this->page_slug,
            'mpai_api_section'
        );
        
        // Add OpenAI Model field
        \add_settings_field(
            'mpai_openai_model',
            \__('OpenAI Model', 'memberpress-ai-assistant'),
            [$this->settings_renderer, 'render_openai_model_field'],
            $this->page_slug,
            'mpai_api_section'
        );
        
        // Add OpenAI Temperature field
        \add_settings_field(
            'mpai_openai_temperature',
            \__('OpenAI Temperature', 'memberpress-ai-assistant'),
            [$this->settings_renderer, 'render_openai_temperature_field'],
            $this->page_slug,
            'mpai_api_section'
        );
        
        // Add OpenAI Max Tokens field
        \add_settings_field(
            'mpai_openai_max_tokens',
            \__('OpenAI Max Tokens', 'memberpress-ai-assistant'),
            [$this->settings_renderer, 'render_openai_max_tokens_field'],
            $this->page_slug,
            'mpai_api_section'
        );
        
        // Add Anthropic API Key field
        \add_settings_field(
            'mpai_anthropic_api_key',
            \__('Anthropic API Key', 'memberpress-ai-assistant'),
            [$this->settings_renderer, 'render_anthropic_api_key_field'],
            $this->page_slug,
            'mpai_api_section'
        );
        
        // Add Anthropic Model field
        \add_settings_field(
            'mpai_anthropic_model',
            \__('Anthropic Model', 'memberpress-ai-assistant'),
            [$this->settings_renderer, 'render_anthropic_model_field'],
            $this->page_slug,
            'mpai_api_section'
        );
        
        // Add Anthropic Temperature field
        \add_settings_field(
            'mpai_anthropic_temperature',
            \__('Anthropic Temperature', 'memberpress-ai-assistant'),
            [$this->settings_renderer, 'render_anthropic_temperature_field'],
            $this->page_slug,
            'mpai_api_section'
        );
        
        // Add Anthropic Max Tokens field
        \add_settings_field(
            'mpai_anthropic_max_tokens',
            \__('Anthropic Max Tokens', 'memberpress-ai-assistant'),
            [$this->settings_renderer, 'render_anthropic_max_tokens_field'],
            $this->page_slug,
            'mpai_api_section'
        );
        
        // Add Primary AI Provider field
        \add_settings_field(
            'mpai_primary_api',
            \__('Primary AI Provider', 'memberpress-ai-assistant'),
            [$this->settings_renderer, 'render_primary_api_field'],
            $this->page_slug,
            'mpai_api_section'
        );
    }
}