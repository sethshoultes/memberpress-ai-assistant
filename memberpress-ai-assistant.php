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
        // Check if MemberPress is active
        if (!$this->check_dependencies()) {
            return;
        }

        // Enable debug mode if filter is set
        if (apply_filters('mpai_debug_mode', false)) {
            $this->enable_debug_mode();
        }

        // Initialize DI container
        $this->init_container();

        // Initialize services
        $this->init_services();

        // Load text domain
        load_plugin_textdomain('memberpress-ai-assistant', false, dirname(plugin_basename(MPAI_PLUGIN_FILE)) . '/languages');
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
        
        // Log initialization events
        error_log('MPAI: Debug mode enabled');
        
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
        $this->serviceLocator = new \MemberpressAiAssistant\DI\ServiceLocator();
        
        // Make service locator available globally for tools that need it
        global $mpai_service_locator;
        $mpai_service_locator = $this->serviceLocator;
        
        // Add debug hook to enable debug mode
        add_filter('mpai_debug_mode', '__return_true');
        
        // Log service locator initialization
        if (apply_filters('mpai_debug_mode', false)) {
            error_log('MPAI: Initializing service locator');
        }
        
        // Register core services
        $this->register_core_services();
        
        if (apply_filters('mpai_debug_mode', false)) {
            // Log registered services
            $definitions = $this->serviceLocator->getDefinitions();
            error_log('MPAI: Registered services: ' . implode(', ', array_keys($definitions)));
        }
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

    /**
     * Initialize services
     */
    private function init_services() {
        // Initialize services from the service locator
        
        // Initialize the ToolRegistry
        $logger = $this->serviceLocator->has('logger') ? $this->serviceLocator->get('logger') : null;
        $toolRegistry = \MemberpressAiAssistant\Registry\ToolRegistry::getInstance($logger);
        
        // Discover and register tools
        $toolRegistry->discoverTools();
        
        // Register the ToolRegistry with the service locator
        $this->serviceLocator->register('tool_registry', function() use ($toolRegistry) {
            return $toolRegistry;
        });
        
        // Register and initialize admin services
        $admin_services_registrar = new \MemberpressAiAssistant\Services\AdminServicesRegistrar('admin_services_registrar', $logger);
        $admin_services_registrar->register($this->serviceLocator);
        $admin_services_registrar->boot();
        
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
     * Plugin activation hook
     */
    public function activate() {
        // Create necessary database tables
        // Set up initial configuration
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation hook
     */
    public function deactivate() {
        // Clean up if necessary
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}

// Initialize the plugin

// Initialize the plugin
MemberpressAiAssistant::instance();