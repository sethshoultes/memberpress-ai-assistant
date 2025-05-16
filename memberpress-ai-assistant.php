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
        $this->container = new \MemberpressAiAssistant\DI\Container();
        
        // Make container available globally for tools that need it
        global $mpai_container;
        $mpai_container = $this->container;
        
        // Add debug hook to enable debug mode
        add_filter('mpai_debug_mode', '__return_true');
        
        // Log container initialization
        if (apply_filters('mpai_debug_mode', false)) {
            error_log('MPAI: Initializing container');
        }
        
        // Register service provider
        $service_provider = new \MemberpressAiAssistant\DI\ServiceProvider();
        
        try {
            $service_provider->register($this->container);
            
            if (apply_filters('mpai_debug_mode', false)) {
                // Log registered services
                $services = $service_provider->getServices();
                error_log('MPAI: Registered services: ' . implode(', ', array_keys($services)));
            }
        } catch (\Exception $e) {
            error_log('MPAI: Error in service registration: ' . $e->getMessage());
            
            // Add admin notice about service registration error
            add_action('admin_notices', function() use ($e) {
                echo '<div class="notice notice-error">';
                echo '<p><strong>' . esc_html__('MemberPress AI Assistant Error', 'memberpress-ai-assistant') . '</strong></p>';
                echo '<p>' . esc_html__('Service registration failed. Please check the debug log for details.', 'memberpress-ai-assistant') . '</p>';
                echo '</div>';
            });
        }
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

// Register emergency settings page handler
add_action('admin_menu', function() {
    add_submenu_page(
        'memberpress',
        __('AI Assistant', 'memberpress-ai-assistant'),
        __('AI Assistant', 'memberpress-ai-assistant'),
        'manage_options',
        'mpai-settings',
        'memberpress_ai_assistant_emergency_settings'
    );
}, 999); // Very high priority to override the regular handler

function memberpress_ai_assistant_emergency_settings() {
    echo '<div class="wrap">';
    echo '<h1>' . esc_html__('MemberPress AI Assistant Settings', 'memberpress-ai-assistant') . '</h1>';
    echo '<div class="notice notice-warning">';
    echo '<p><strong>' . esc_html__('Settings system is being repaired', 'memberpress-ai-assistant') . '</strong></p>';
    echo '<p>' . esc_html__('The settings system is currently being repaired to fix memory issues. Basic functionality is provided.', 'memberpress-ai-assistant') . '</p>';
    echo '</div>';
    echo '<form method="post" action="">';
    echo '<table class="form-table" role="presentation">';
    echo '<tr><th scope="row">' . esc_html__('Enable AI Assistant', 'memberpress-ai-assistant') . '</th>';
    echo '<td><fieldset><legend class="screen-reader-text"><span>' . esc_html__('Enable AI Assistant', 'memberpress-ai-assistant') . '</span></legend>';
    echo '<label for="mpai_enabled"><input name="mpai_enabled" type="checkbox" id="mpai_enabled" value="1" checked="checked">' . esc_html__('Enable', 'memberpress-ai-assistant') . '</label>';
    echo '<p class="description">' . esc_html__('Enable or disable the AI assistant functionality.', 'memberpress-ai-assistant') . '</p>';
    echo '</fieldset></td></tr>';
    echo '</table>';
    echo '<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="' . esc_attr__('Save Changes', 'memberpress-ai-assistant') . '" /></p>';
    echo '</form>';
    echo '</div>';
}

// EMERGENCY OVERRIDE - Disable normal initialization to prevent memory issues

// Add a notice about emergency mode
add_action('admin_notices', function() {
    // Only show on relevant pages
    if (!is_admin() || !current_user_can('manage_options')) {
        return;
    }
    
    echo '<div class="notice notice-error">';
    echo '<p><strong>' . esc_html__('MemberPress AI Assistant in Emergency Mode', 'memberpress-ai-assistant') . '</strong></p>';
    echo '<p>' . esc_html__('The plugin is currently running in emergency mode due to memory issues. Only basic functionality is available.', 'memberpress-ai-assistant') . '</p>';
    echo '<p>' . esc_html__('The development team has been notified and is working on a fix.', 'memberpress-ai-assistant') . '</p>';
    echo '</div>';
});

// Initialize only critical parts of the plugin
function memberpress_ai_assistant_emergency_init() {
    // Register our emergency admin page
    add_action('admin_menu', 'memberpress_ai_assistant_register_admin_page');
    
    // Log initialization in emergency mode
    error_log('MPAI: Initialized in emergency mode due to memory issues');
}

// Register the emergency admin menu
function memberpress_ai_assistant_register_admin_page() {
    add_submenu_page(
        'memberpress',
        __('AI Assistant', 'memberpress-ai-assistant'),
        __('AI Assistant', 'memberpress-ai-assistant'),
        'manage_options',
        'mpai-settings',
        'memberpress_ai_assistant_emergency_settings'
    );
}

// Start the plugin in emergency mode
memberpress_ai_assistant_emergency_init();