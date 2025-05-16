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
     * DI Container
     *
     * @var \MemberpressAiAssistant\DI\Container
     */
    private $container;

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

        // Initialize DI container
        $this->init_container();

        // Initialize services
        $this->init_services();

        // Load text domain
        load_plugin_textdomain('memberpress-ai-assistant', false, dirname(plugin_basename(MPAI_PLUGIN_FILE)) . '/languages');
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
        $this->container = new \MemberpressAiAssistant\DI\Container();
        
        // Make container available globally for tools that need it
        global $mpai_container;
        $mpai_container = $this->container;
        
        // Register service provider
        $service_provider = new \MemberpressAiAssistant\DI\ServiceProvider();
        $service_provider->register($this->container);
    }

    /**
     * Initialize services
     */
    private function init_services() {
        // Initialize services from the container
        
        // Initialize the ToolRegistry
        $logger = $this->container->bound('logger') ? $this->container->make('logger') : null;
        $toolRegistry = \MemberpressAiAssistant\Registry\ToolRegistry::getInstance($logger);
        
        // Discover and register tools
        $toolRegistry->discoverTools();
        
        // Register the ToolRegistry with the container
        $this->container->singleton('tool_registry', function() use ($toolRegistry) {
            return $toolRegistry;
        });
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
function memberpress_ai_assistant() {
    return MemberpressAiAssistant::instance();
}

// Start the plugin
memberpress_ai_assistant();