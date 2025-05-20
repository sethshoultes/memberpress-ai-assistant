<?php
/**
 * Plugin Name: MemberPress AI Assistant
 * Plugin URI: https://memberpress.com/
 * Description: AI-powered assistant for MemberPress that helps manage memberships through natural language.
 * Version: 1.0.0
 * Author: MemberPress
 * Author URI: https://memberpress.com/
 * Text Domain: memberpress-ai-assistant
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 *
 * @package MemberpressAiAssistant
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Temporarily increase memory limit while we're fixing the core issues
@ini_set('memory_limit', '512M');

// Define plugin constants
define('MPAI_PLUGIN_FILE', __FILE__);
define('MPAI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MPAI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MPAI_VERSION', '1.0.0');

// Include debug logger early to intercept debug logs
require_once MPAI_PLUGIN_DIR . 'src/Utilities/DebugLogger.php';

// Run the script to replace all error_log calls with debug_log
// This only needs to be run once, so we'll check if it's already been run
if (!get_option('mpai_debug_logs_replaced')) {
    // Include the script to replace debug logs
    require_once MPAI_PLUGIN_DIR . 'src/Utilities/replace_debug_logs.php';
    
    // Mark as run
    update_option('mpai_debug_logs_replaced', true);
}

/**
 * Main plugin class
 */
class MemberpressAiAssistant {
    /**
     * Instance of the plugin
     *
     * @var MemberpressAiAssistant
     */
    private static $instance = null;

    /**
     * Service Locator
     *
     * @var \MemberpressAiAssistant\DI\ServiceLocator
     */
    private $serviceLocator;

    /**
     * Get the singleton instance of the plugin
     *
     * @return MemberpressAiAssistant
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Load autoloader
        $this->load_autoloader();

        // Initialize the plugin
        add_action('plugins_loaded', [$this, 'init'], 10);

        // Register activation and deactivation hooks
        register_activation_hook(MPAI_PLUGIN_FILE, [$this, 'activate']);
        register_deactivation_hook(MPAI_PLUGIN_FILE, [$this, 'deactivate']);
        
        // Handle redirection after plugin activation
        add_action('admin_init', [$this, 'maybe_redirect_after_activation']);
        
        // Register the welcome page
        add_action('admin_menu', [$this, 'register_welcome_page']);
    }

    /**
     * Load Composer autoloader
     */
    private function load_autoloader() {
        if (file_exists(MPAI_PLUGIN_DIR . 'vendor/autoload.php')) {
            require_once MPAI_PLUGIN_DIR . 'vendor/autoload.php';
        } else {
            // Fallback autoloader if Composer is not available
            spl_autoload_register(function ($class) {
                // Check if the class is in our namespace
                if (strpos($class, 'MemberpressAiAssistant\\') !== 0) {
                    return;
                }

                // Convert namespace to file path
                $class_path = str_replace('MemberpressAiAssistant\\', '', $class);
                $class_path = str_replace('\\', DIRECTORY_SEPARATOR, $class_path);
                $file = MPAI_PLUGIN_DIR . 'src' . DIRECTORY_SEPARATOR . $class_path . '.php';

                // Include the file if it exists
                if (file_exists($file)) {
                    require_once $file;
                }
            });
        }
    }

    /**
     * Initialize the plugin
     */
    public function init() {
        // Use a static flag to ensure we only initialize once per request
        static $initialized = false;
        
        if ($initialized) {
            return;
        }
        
        // Check if MemberPress is active
        if (!$this->check_dependencies()) {
            return;
        }

        // Initialize DI container first
        $this->init_container();

        // Register service providers
        $this->register_service_providers();
        
        // Initialize admin services to make settings model available
        $this->init_admin_services();
        
        // Now initialize logging utility (settings model is available)
        $this->init_logging();

        // Enable debug mode if filter is set
        if (apply_filters('mpai_debug_mode', false)) {
            $this->enable_debug_mode();
        }

        // Initialize remaining services
        $this->init_remaining_services();

        // Load text domain
        load_plugin_textdomain('memberpress-ai-assistant', false, dirname(plugin_basename(MPAI_PLUGIN_FILE)) . '/languages');
        
        // Mark as initialized
        $initialized = true;
    }
    
    /**
     * Initialize admin services
     */
    private function init_admin_services() {
        // Get the logger
        $logger = $this->serviceLocator->has('logger') ? $this->serviceLocator->get('logger') : null;
        
        // Register and initialize admin services
        $admin_services_registrar = new \MemberpressAiAssistant\Services\AdminServicesRegistrar('admin_services_registrar', $logger);
        $admin_services_registrar->register($this->serviceLocator);
        $admin_services_registrar->boot();
    }
    
    /**
     * Initialize remaining services
     */
    private function init_remaining_services() {
        // Get the logger
        $logger = $this->serviceLocator->has('logger') ? $this->serviceLocator->get('logger') : null;
        
        // Register key manager service
        $key_manager = new \MemberpressAiAssistant\Admin\MPAIKeyManager('key_manager', $logger);
        $key_manager->register($this->serviceLocator);
        $key_manager->boot();
        
        // Register AJAX handler service
        $ajax_handler = new \MemberpressAiAssistant\Admin\MPAIAjaxHandler('ajax_handler', $logger);
        $ajax_handler->register($this->serviceLocator);
        $ajax_handler->boot();
        
        // Register cache service
        $cache_service = new \MemberpressAiAssistant\Services\CacheService('cache', $logger);
        $cache_service->register($this->serviceLocator);
        $cache_service->boot();
        
        // Register MemberPress service
        $memberpress_service = new \MemberpressAiAssistant\Services\MemberPressService('memberpress', $logger);
        $memberpress_service->register($this->serviceLocator);
        $memberpress_service->boot();
        
        // Register orchestrator service
        $orchestrator_service = new \MemberpressAiAssistant\Services\OrchestratorService('orchestrator', $logger);
        $orchestrator_service->register($this->serviceLocator);
        $orchestrator_service->boot();
        
        // Register chat interface service
        $chat_interface_service = new \MemberpressAiAssistant\Services\ChatInterfaceService('chat_interface', $logger);
        $chat_interface_service->register($this->serviceLocator);
        $chat_interface_service->boot();
    }
    
    /**
     * Initialize logging utility
     */
    private function init_logging() {
        // Use a static flag to ensure we only initialize logging once per request
        static $logging_initialized = false;
        
        if ($logging_initialized) {
            return;
        }
        
        // Get settings model if available
        $settings_model = null;
        if (isset($this->serviceLocator) && $this->serviceLocator->has('settings.model')) {
            $settings_model = $this->serviceLocator->get('settings.model');
        }
        
        // First, try to get log level from settings
        $log_level = \MemberpressAiAssistant\Utilities\LoggingUtility::LEVEL_INFO; // Default to INFO
        
        if ($settings_model !== null) {
            $setting_level = $settings_model->get_log_level();
            
            // Map special values
            if ($setting_level === 'minimal') {
                $log_level = \MemberpressAiAssistant\Utilities\LoggingUtility::LEVEL_ERROR;
            }
            else if ($setting_level === 'none') {
                $log_level = \MemberpressAiAssistant\Utilities\LoggingUtility::LEVEL_NONE;
            }
            else if (!empty($setting_level)) {
                $log_level = $setting_level;
            }
        }
        // Only use WP_DEBUG to set log level if no setting is available
        else if (defined('WP_DEBUG') && WP_DEBUG) {
            $log_level = \MemberpressAiAssistant\Utilities\LoggingUtility::LEVEL_DEBUG;
        }
        
        // Allow filtering of log level
        $log_level = apply_filters('mpai_log_level', $log_level);
        
        // Initialize the logging utility
        \MemberpressAiAssistant\Utilities\LoggingUtility::init($log_level, false);
        
        // Only log if not in "none" mode
        if ($log_level !== \MemberpressAiAssistant\Utilities\LoggingUtility::LEVEL_NONE) {
            \MemberpressAiAssistant\Utilities\LoggingUtility::error('Plugin initialized with log level: ' . $log_level);
        }
        
        // Mark logging as initialized
        $logging_initialized = true;
    }
    
    /**
     * Enable debug mode
     */
    public function enable_debug_mode() {
        // Enable detailed logging
        if (!defined('WP_DEBUG')) {
            define('WP_DEBUG', true);
        }
        
        if (!defined('WP_DEBUG_LOG')) {
            define('WP_DEBUG_LOG', true);
        }
        
        // Get settings model if available
        $settings_model = null;
        if (isset($this->serviceLocator) && $this->serviceLocator->has('settings.model')) {
            $settings_model = $this->serviceLocator->get('settings.model');
        }
        
        // Check if logging is disabled
        if ($settings_model !== null && $settings_model->get_log_level() === 'none') {
            // Respect the "none" setting even in debug mode
            error_log('Debug mode requested but logging is disabled by settings');
            return;
        }
        
        // Set logging utility to debug level
        \MemberpressAiAssistant\Utilities\LoggingUtility::init(
            \MemberpressAiAssistant\Utilities\LoggingUtility::LEVEL_DEBUG,
            false
        );
        
        // Only log if not in "none" mode
        if (\MemberpressAiAssistant\Utilities\LoggingUtility::getLogLevel() !== \MemberpressAiAssistant\Utilities\LoggingUtility::LEVEL_NONE) {
            \MemberpressAiAssistant\Utilities\LoggingUtility::info('Debug mode enabled');
        }
        
        // Add admin notice about debug mode
        add_action('admin_notices', function() {
            // Only show on plugin pages
            $screen = get_current_screen();
            if (!$screen || strpos($screen->id, 'mpai') === false) {
                return;
            }
            
            echo '<div class="notice notice-warning">';
            echo '<p><strong>' . esc_html__('MemberPress AI Assistant Debug Mode', 'memberpress-ai-assistant') . '</strong></p>';
            echo '<p>' . esc_html__('Debug mode is enabled. Detailed logs are being written to the WordPress debug log.', 'memberpress-ai-assistant') . '</p>';
            echo '</div>';
        });
    }

    /**
     * Check if required dependencies are available
     *
     * @return bool
     */
    private function check_dependencies() {
        // Check if MemberPress is active
        if (!function_exists('is_plugin_active')) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        // Define the MemberPress plugin file
        $memberpress_plugin = 'memberpress/memberpress.php';

        // Check if MemberPress is installed and active
        if (!is_plugin_active($memberpress_plugin)) {
            add_action('admin_notices', function() {
                echo '<div class="error"><p>';
                echo sprintf(
                    __('MemberPress AI Assistant requires MemberPress to be installed and activated. <a href="%s">Install MemberPress</a>', 'memberpress-ai-assistant'),
                    admin_url('plugin-install.php?tab=plugin-information&plugin=memberpress')
                );
                echo '</p></div>';
            });
            return false;
        }

        return true;
    }

    /**
     * Initialize the DI container
     */
    private function init_container() {
        // Use a static flag to ensure we only initialize the container once per request
        static $container_initialized = false;
        
        if ($container_initialized) {
            return;
        }
        
        $this->serviceLocator = new \MemberpressAiAssistant\DI\ServiceLocator();
        
        // Make service locator available globally for tools that need it
        global $mpai_service_locator;
        $mpai_service_locator = $this->serviceLocator;
        
        // Only enable debug mode if explicitly requested
        // add_filter('mpai_debug_mode', '__return_true');
        
        // Only log if not in "none" mode
        if (\MemberpressAiAssistant\Utilities\LoggingUtility::getLogLevel() !== \MemberpressAiAssistant\Utilities\LoggingUtility::LEVEL_NONE) {
            // Log service locator initialization
            \MemberpressAiAssistant\Utilities\LoggingUtility::error('Initializing service locator');
            
            // Register core services
            $this->register_core_services();
            
            // Log registered services
            $definitions = $this->serviceLocator->getDefinitions();
            \MemberpressAiAssistant\Utilities\LoggingUtility::error('Registered services: ' . implode(', ', array_keys($definitions)));
        } else {
            // Just register core services without logging
            $this->register_core_services();
        }
        
        // Mark container as initialized
        $container_initialized = true;
    }
    
    /**
     * Register core services with the service locator
     */
    private function register_core_services() {
        // Register logger service
        $this->serviceLocator->register('logger', function() {
            // Simple logger implementation
            return new class {
                public function info($message, array $context = []) {
                    // Log to WordPress debug log if enabled
                    if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                        error_log(sprintf('[INFO] %s: %s %s',
                            date('Y-m-d H:i:s'),
                            $message,
                            !empty($context) ? json_encode($context) : ''
                        ));
                    }
                }

                public function error($message, array $context = []) {
                    // Log to WordPress debug log if enabled
                    if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                        error_log(sprintf('[ERROR] %s: %s %s',
                            date('Y-m-d H:i:s'),
                            $message,
                            !empty($context) ? json_encode($context) : ''
                        ));
                    }
                }
                
                public function warning($message, array $context = []) {
                    // Log to WordPress debug log if enabled
                    if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                        error_log(sprintf('[WARNING] %s: %s %s',
                            date('Y-m-d H:i:s'),
                            $message,
                            !empty($context) ? json_encode($context) : ''
                        ));
                    }
                }
                
                public function debug($message, array $context = []) {
                    // Log to WordPress debug log if enabled
                    if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                        error_log(sprintf('[DEBUG] %s: %s %s',
                            date('Y-m-d H:i:s'),
                            $message,
                            !empty($context) ? json_encode($context) : ''
                        ));
                    }
                }
            };
        });
    }

    // init_services method has been replaced by init_admin_services and init_remaining_services

    /**
     * Plugin activation hook
     */
    public function activate() {
        // Create necessary database tables
        // Set up initial configuration
        
        // Reset all user consents upon activation
        \MemberpressAiAssistant\Admin\MPAIConsentManager::resetAllConsents();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set a transient to redirect after activation
        set_transient('mpai_activation_redirect', true, 30);
    }
    
    /**
     * Redirect after plugin activation
     */
    public function maybe_redirect_after_activation() {
        // Check if we should redirect after activation
        if (get_transient('mpai_activation_redirect')) {
            // Delete the transient
            delete_transient('mpai_activation_redirect');
            
            // Make sure this is not an AJAX, cron, or other system request
            if (!wp_doing_ajax() && !wp_doing_cron() && !defined('DOING_AUTOSAVE') && !defined('WP_INSTALLING')) {
                // Redirect to the welcome page
                wp_safe_redirect(admin_url('admin.php?page=mpai-welcome'));
                exit;
            }
        }
    }
    
    /**
     * Register the welcome page
     * This page is hidden from the menu but accessible via URL
     */
    public function register_welcome_page() {
        add_submenu_page(
            null, // No parent menu - hidden page
            __('Welcome to MemberPress AI Assistant', 'memberpress-ai-assistant'),
            __('Welcome', 'memberpress-ai-assistant'),
            'manage_options',
            'mpai-welcome',
            [$this, 'render_welcome_page']
        );
    }
    
    /**
     * Render the welcome page
     */
    public function render_welcome_page() {
        // Get the consent manager
        $consent_manager = \MemberpressAiAssistant\Admin\MPAIConsentManager::getInstance();
        
        // Include the welcome page template
        include plugin_dir_path(__FILE__) . 'templates/welcome-page.php';
    }

    /**
     * Plugin deactivation hook
     */
    public function deactivate() {
        // Clean up if necessary
        
        // Reset all user consents
        \MemberpressAiAssistant\Admin\MPAIConsentManager::resetAllConsents();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Register service providers
     */
    private function register_service_providers() {
        // Use a static flag to ensure we only register service providers once per request
        static $providers_registered = false;
        
        if ($providers_registered) {
            return;
        }
        
        // Get the logger
        $logger = $this->serviceLocator->has('logger') ? $this->serviceLocator->get('logger') : null;
        
        // Register configuration service provider
        $config_service = new \MemberpressAiAssistant\Services\ConfigurationService('configuration', $logger);
        $config_service->register($this->serviceLocator);
        $config_service->boot();
        
        // Register LLM service provider
        $llm_provider = new \MemberpressAiAssistant\DI\Providers\LlmServiceProvider();
        $llm_provider->register($this->serviceLocator);
        $llm_provider->boot($this->serviceLocator);
        
        // Register Tool Registry provider
        $tool_registry_provider = new \MemberpressAiAssistant\DI\Providers\ToolRegistryProvider();
        $tool_registry_provider->register($this->serviceLocator);
        $tool_registry_provider->boot($this->serviceLocator);
        
        // Only log if not in "none" mode
        if (\MemberpressAiAssistant\Utilities\LoggingUtility::getLogLevel() !== \MemberpressAiAssistant\Utilities\LoggingUtility::LEVEL_NONE) {
            \MemberpressAiAssistant\Utilities\LoggingUtility::error('Service providers registered');
        }
        
        // Mark providers as registered
        $providers_registered = true;
    }
}

// Initialize the plugin
MemberpressAiAssistant::instance();

// Include the API key test script
if (file_exists(__DIR__ . '/test-api-keys.php')) {
    include_once __DIR__ . '/test-api-keys.php';
}