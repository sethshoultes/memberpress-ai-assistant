<?php
/**
 * MemberPress AI Assistant Admin Menu Handler
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Admin;

use MemberpressAiAssistant\Abstracts\AbstractService;
use MemberpressAiAssistant\Services\Settings\SettingsControllerService;

/**
 * Class for handling MemberPress AI Assistant admin menu
 * 
 * This class handles the registration of admin menus based on whether
 * MemberPress is active, and implements menu highlighting filters.
 */
class MPAIAdminMenu extends AbstractService {
    /**
     * Menu slug for the plugin
     *
     * @var string
     */
    protected $menu_slug = 'mpai-settings';

    /**
     * Parent menu slug when MemberPress is active
     *
     * @var string
     */
    protected $parent_menu_slug = 'memberpress';
    
    /**
     * Settings controller instance (for new MVC architecture)
     *
     * @var SettingsControllerService|null
     */
    protected ?SettingsControllerService $settings_controller = null;

    /**
     * Constructor
     *
     * @param string $name Service name
     * @param mixed $logger Logger instance
     */
    public function __construct(string $name = 'admin_menu', $logger = null) {
        parent::__construct($name, $logger);
    }
    
    /**
     * Set the settings controller (for new MVC architecture)
     *
     * @param SettingsControllerService $settings_controller Settings controller instance
     * @return void
     */
    public function set_settings_controller(SettingsControllerService $settings_controller): void {
        $this->settings_controller = $settings_controller;
        $this->logWithLevel('New MVC settings controller set in admin menu', 'info');
    }

    /**
     * {@inheritdoc}
     */
    public function register($serviceLocator): void {
        // Store reference to this for use in closures
        $self = $this;
        
        // Register this service with the service locator
        $serviceLocator->register('admin_menu', function() use ($self) {
            return $self;
        });
        
        // Add dependencies to the dependencies array
        $this->dependencies = [
            'settings_controller', // Only need the new MVC controller as a dependency
        ];

        // Log registration
        $this->log('Admin menu service registered', ['level' => 'info']);
    }

    /**
     * {@inheritdoc}
     */
    public function boot(): void {
        parent::boot();
        
        // Add hooks
        $this->addHooks();
        
        // Log boot
        $this->log('Admin menu service booted', ['level' => 'info']);
    }

    /**
     * Add hooks and filters for this service
     *
     * @return void
     */
    protected function addHooks(): void {
        // Add admin menu
        add_action('admin_menu', [$this, 'register_admin_menu']);
        
        // Add menu highlighting filters
        add_filter('parent_file', [$this, 'highlight_parent_menu']);
        add_filter('submenu_file', [$this, 'highlight_submenu'], 10, 2); // Specify 2 arguments
    }

    /**
     * Register admin menu based on MemberPress availability
     *
     * @return void
     */
    public function register_admin_menu(): void {
        if (mpai_is_memberpress_active()) {
            // Add as submenu under MemberPress
            $this->register_memberpress_submenu();
        } else {
            // Add as top-level menu
            $this->register_top_level_menu();
        }
    }

    /**
     * Register as submenu under MemberPress
     *
     * @return void
     */
    protected function register_memberpress_submenu(): void {
        // Add the main settings page
        add_submenu_page(
            $this->parent_menu_slug,
            __('AI Assistant', 'memberpress-ai-assistant'),
            __('AI Assistant', 'memberpress-ai-assistant'),
            'manage_options',
            $this->menu_slug,
            [$this, 'render_settings_page']
        );
        
        // Add the welcome page (hidden from menu)
        add_submenu_page(
            null, // Hidden from menu
            __('AI Assistant Welcome', 'memberpress-ai-assistant'),
            __('AI Assistant Welcome', 'memberpress-ai-assistant'),
            'manage_options',
            'mpai-welcome',
            [$this, 'render_welcome_page']
        );
        
        $this->log('Registered as MemberPress submenu', ['level' => 'info']);
    }

    /**
     * Register as top-level menu
     *
     * @return void
     */
    protected function register_top_level_menu(): void {
        add_menu_page(
            __('MemberPress AI', 'memberpress-ai-assistant'),
            __('MemberPress AI', 'memberpress-ai-assistant'),
            'manage_options',
            $this->menu_slug,
            [$this, 'render_settings_page'],
            'dashicons-admin-generic',
            30
        );
        
        // Add the welcome page (hidden from menu)
        add_submenu_page(
            null, // Hidden from menu
            __('AI Assistant Welcome', 'memberpress-ai-assistant'),
            __('AI Assistant Welcome', 'memberpress-ai-assistant'),
            'manage_options',
            'mpai-welcome',
            [$this, 'render_welcome_page']
        );
        
        $this->log('Registered as top-level menu', ['level' => 'info']);
    }

    /**
     * Render the settings page
     *
     * @return void
     */
    public function render_settings_page(): void {
        try {
            // Ensure chat interface assets are enqueued if user has consented
            $this->ensure_chat_assets_enqueued();
            
            // Use the MVC controller if available
            if ($this->settings_controller) {
                $this->logWithLevel('Using MVC settings controller for rendering', 'info');
                $this->settings_controller->render_page();
                return;
            }
            
            // If controller is not available, show fallback
            $this->logWithLevel('Settings controller not available in admin menu', 'error');
            $this->render_fallback_settings_page();
            
        } catch (\Exception $e) {
            $this->logWithLevel('Unhandled exception in render_settings_page: ' . $e->getMessage(), 'error');
            $this->logWithLevel('Exception class: ' . get_class($e), 'error');
            $this->logWithLevel('Exception trace: ' . $e->getTraceAsString(), 'debug');
            $this->render_fallback_settings_page();
        } catch (\Error $error) {
            $this->logWithLevel('Critical PHP error in render_settings_page: ' . $error->getMessage(), 'error');
            $this->logWithLevel('Error class: ' . get_class($error), 'error');
            $this->logWithLevel('Error trace: ' . $error->getTraceAsString(), 'debug');
            $this->render_fallback_settings_page();
        }
    }
    
    /**
     * Render a fallback settings page when the regular one can't be displayed
     *
     * @return void
     */
    protected function render_fallback_settings_page(): void {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('MemberPress AI Assistant Settings', 'memberpress-ai-assistant') . '</h1>';
        
        echo '<div class="notice notice-error">';
        echo '<p><strong>' . esc_html__('Settings System Error', 'memberpress-ai-assistant') . '</strong></p>';
        echo '<p>' . esc_html__('The MemberPress AI Assistant settings system could not be initialized properly.', 'memberpress-ai-assistant') . '</p>';
        echo '<p>' . esc_html__('Please try the following:', 'memberpress-ai-assistant') . '</p>';
        echo '<ul style="list-style-type: disc; margin-left: 20px;">';
        echo '<li>' . esc_html__('Refresh this page', 'memberpress-ai-assistant') . '</li>';
        echo '<li>' . esc_html__('Deactivate and reactivate the plugin', 'memberpress-ai-assistant') . '</li>';
        echo '<li>' . esc_html__('Contact MemberPress support if the issue persists', 'memberpress-ai-assistant') . '</li>';
        echo '</ul>';
        echo '</div>';
        
        echo '</div>';
        
        $this->logWithLevel('Fallback settings page rendered due to initialization error', 'warning');
    }
    
    /**
     * Ensure chat interface assets are properly enqueued
     *
     * Assets are now always enqueued on settings pages to support both:
     * - Inline consent form AJAX functionality (requires assets before consent)
     * - Chat interface functionality (requires assets after consent)
     *
     * @return void
     */
    protected function ensure_chat_assets_enqueued(): void {
        try {
            // Get current screen information for page detection
            $current_screen = get_current_screen();
            $hook_suffix = $current_screen ? $current_screen->id : 'unknown';
            
            $this->logWithLevel('Checking if chat assets should be enqueued for hook: ' . $hook_suffix, 'debug');
            
            // Get the chat interface instance to check if we should load assets
            $chat_interface = \MemberpressAiAssistant\ChatInterface::getInstance();
            
            // Use the existing shouldLoadAdminChatInterface logic to determine if we're on the right page
            $reflection = new \ReflectionClass($chat_interface);
            $shouldLoadMethod = $reflection->getMethod('shouldLoadAdminChatInterface');
            $shouldLoadMethod->setAccessible(true);
            $should_load_on_page = $shouldLoadMethod->invoke($chat_interface, $hook_suffix);
            
            if (!$should_load_on_page) {
                $this->logWithLevel('Not on a page that requires chat assets, skipping enqueuing', 'debug');
                return;
            }
            
            $this->logWithLevel('On settings page - enqueuing chat assets for universal functionality', 'info');
            
            // Check consent status for logging purposes
            $consent_manager = \MemberpressAiAssistant\Admin\MPAIConsentManager::getInstance();
            $has_consented = $consent_manager->hasUserConsented();
            
            $this->logWithLevel('User consent status: ' . ($has_consented ? 'consented' : 'not consented'), 'debug');
            $this->logWithLevel('Assets needed for: ' . ($has_consented ? 'chat interface' : 'consent form AJAX + future chat interface'), 'debug');
            
            // Always enqueue assets on settings page (needed for both consent form and chat interface)
            $this->logWithLevel('Triggering chat asset registration for hook: ' . $hook_suffix, 'debug');
            
            // Manually trigger admin asset registration
            $chat_interface->registerAdminAssets($hook_suffix);
            
            $this->logWithLevel('Chat assets enqueued successfully for universal functionality', 'info');
            
        } catch (\ReflectionException $e) {
            $this->logWithLevel('Error accessing shouldLoadAdminChatInterface method: ' . $e->getMessage(), 'error');
            $this->logWithLevel('Falling back to basic asset enqueuing', 'warning');
            
            // Fallback: enqueue assets if we can't determine page status
            try {
                $chat_interface = \MemberpressAiAssistant\ChatInterface::getInstance();
                $current_screen = get_current_screen();
                $hook_suffix = $current_screen ? $current_screen->id : 'unknown';
                $chat_interface->registerAdminAssets($hook_suffix);
                $this->logWithLevel('Fallback asset enqueuing completed', 'info');
            } catch (\Exception $fallback_error) {
                $this->logWithLevel('Fallback asset enqueuing failed: ' . $fallback_error->getMessage(), 'error');
            }
            
        } catch (\Exception $e) {
            $this->logWithLevel('Unexpected error in ensure_chat_assets_enqueued: ' . $e->getMessage(), 'error');
            $this->logWithLevel('Error trace: ' . $e->getTraceAsString(), 'debug');
        }
    }

    /**
     * Render the welcome page with consent form
     *
     * @return void
     */
    public function render_welcome_page(): void {
        // Check if user has already consented
        $consent_manager = \MemberpressAiAssistant\Admin\MPAIConsentManager::getInstance();
        if ($consent_manager->hasUserConsented()) {
            // User has already consented, redirect to settings
            wp_redirect(admin_url('admin.php?page=mpai-settings'));
            exit;
        }
        
        // Include the welcome page template
        $template_path = MPAI_PLUGIN_DIR . 'templates/welcome-page.php';
        
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="wrap">';
            echo '<h1>' . esc_html__('MemberPress AI Assistant Welcome', 'memberpress-ai-assistant') . '</h1>';
            echo '<div class="notice notice-error">';
            echo '<p>' . esc_html__('Welcome page template not found.', 'memberpress-ai-assistant') . '</p>';
            echo '</div>';
            echo '</div>';
        }
    }

    /**
     * Highlight the parent menu when on plugin pages
     *
     * @param string $parent_file The parent file
     * @return string The filtered parent file
     */
    public function highlight_parent_menu(string $parent_file): string {
        global $plugin_page;
        
        if ($plugin_page === $this->menu_slug && mpai_is_memberpress_active()) {
            return $this->parent_menu_slug;
        }
        
        return $parent_file;
    }

    /**
     * Highlight the correct submenu item when on the AI Assistant settings page.
     *
     * @param string|null $submenu_file The current submenu file.
     * @param string $parent_file The parent menu file.
     * @return string|null The modified submenu file.
     */
    public function highlight_submenu(?string $submenu_file, string $parent_file): ?string {
        global $plugin_page;

        // Check if we are on the MemberPress parent menu
        if ($parent_file === 'memberpress') {
            // Check if the current page is the AI Assistant settings page
            if ($plugin_page === $this->menu_slug) { // Use $this->menu_slug
                // Set the submenu file to our settings page slug to highlight it
                $submenu_file = $this->menu_slug; // Use $this->menu_slug
            }
        }

        return $submenu_file;
    }
    
    /**
     * Log service activity
     *
     * @param string $message The log message
     * @param array $context The log context
     * @return void
     */
    protected function log(string $message, array $context = []): void {
        // Add log level from context if available, default to info
        $level = isset($context['level']) ? $context['level'] : 'info';
        
        // Remove level from context to avoid duplication
        if (isset($context['level'])) {
            unset($context['level']);
        }
        
        $this->logWithLevel($message, $level, $context);
    }
    
    /**
     * Enhanced logging method with level support
     *
     * @param string $message The message to log
     * @param string $level The log level (debug, info, warning, error)
     * @param array $context Additional context data
     * @return void
     */
    protected function logWithLevel(string $message, string $level = 'info', array $context = []): void {
        if (!$this->logger) {
            return;
        }
        
        // Add service identifier to message
        $prefixedMessage = '[MPAIAdminMenu] ' . $message;
        
        // Merge service name into context
        $mergedContext = array_merge(['service' => $this->getServiceName()], $context);
        
        switch ($level) {
            case 'error':
                $this->logger->error($prefixedMessage, $mergedContext);
                break;
            case 'warning':
                $this->logger->warning($prefixedMessage, $mergedContext);
                break;
            case 'debug':
                // Check if debug method exists, otherwise fall back to info
                if (method_exists($this->logger, 'debug')) {
                    $this->logger->debug($prefixedMessage, $mergedContext);
                } else {
                    $this->logger->info('DEBUG: ' . $prefixedMessage, $mergedContext);
                }
                break;
            case 'info':
            default:
                $this->logger->info($prefixedMessage, $mergedContext);
                break;
        }
    }
}

/**
 * Check if MemberPress is active
 *
 * @return bool Whether MemberPress is active
 */
function mpai_is_memberpress_active(): bool {
    if (!function_exists('is_plugin_active')) {
        include_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    
    return is_plugin_active('memberpress/memberpress.php');
}