<?php
/**
 * Plugin Name: MemberPress AI Assistant
 * Plugin URI: https://memberpress.com/memberpress-ai-assistant
 * Description: AI-powered chat assistant for MemberPress that helps with membership management, troubleshooting, and WordPress CLI command execution.
 * Version: 1.5.4
 * Author: MemberPress
 * Author URI: https://memberpress.com
 * Text Domain: memberpress-ai-assistant
 * Domain Path: /languages
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 */

// SUPER DIRECT PLUGIN LOGS HANDLER - First check if we're being called directly
// This is a special case to handle the plugin_logs tool directly, bypassing WordPress entirely
if (isset($_REQUEST['direct_plugin_logs']) && $_REQUEST['direct_plugin_logs'] === 'true') {
    error_log('MPAI: Direct plugin logs endpoint called');
    
    // Set headers for JSON response
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    
    try {
        // We need to bootstrap WordPress minimally
        define('SHORTINIT', true);
        require_once('../../../wp-load.php');
        
        // Define our plugin constants
        define('MPAI_PLUGIN_DIR', plugin_dir_path(__FILE__));
        define('MPAI_PLUGIN_URL', plugin_dir_url(__FILE__));
        
        // Load the plugin logger
        require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-plugin-logger.php';
        
        // Initialize the plugin logger
        $plugin_logger = mpai_init_plugin_logger();
        
        if (!$plugin_logger) {
            echo json_encode([
                'success' => false,
                'error' => 'Failed to initialize plugin logger'
            ]);
            exit;
        }
        
        // Get parameters with defaults
        $action = isset($_REQUEST['action_type']) ? $_REQUEST['action_type'] : '';
        $days = isset($_REQUEST['days']) ? intval($_REQUEST['days']) : 30;
        $limit = isset($_REQUEST['limit']) ? intval($_REQUEST['limit']) : 25;
        
        // Get logs
        $args = [
            'action'    => $action,
            'date_from' => date('Y-m-d H:i:s', strtotime("-{$days} days")),
            'orderby'   => 'date_time',
            'order'     => 'DESC',
            'limit'     => $limit
        ];
        
        $logs = $plugin_logger->get_logs($args);
        
        // Count logs by action
        $summary = [
            'total' => count($logs),
            'installed' => 0,
            'updated' => 0,
            'activated' => 0,
            'deactivated' => 0,
            'deleted' => 0
        ];
        
        foreach ($logs as $log) {
            if (isset($log['action']) && isset($summary[$log['action']])) {
                $summary[$log['action']]++;
            }
        }
        
        // Format logs with time_ago
        foreach ($logs as &$log) {
            $timestamp = strtotime($log['date_time']);
            $log['time_ago'] = human_time_diff($timestamp, current_time('timestamp')) . ' ago';
        }
        
        // Output the response
        echo json_encode([
            'success' => true,
            'tool' => 'plugin_logs',
            'summary' => $summary,
            'time_period' => "past {$days} days",
            'logs' => $logs,
            'total' => count($logs),
            'result' => "Plugin logs for the past {$days} days: " . count($logs) . " entries found"
        ]);
        exit;
    } catch (Exception $e) {
        error_log('MPAI: Error in direct plugin logs endpoint: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Error retrieving plugin logs: ' . $e->getMessage()
        ]);
        exit;
    }
}

// If this is a normal WordPress request, continue as usual
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('MPAI_VERSION', '1.5.4');
define('MPAI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MPAI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MPAI_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Class MemberPress_AI_Assistant
 * 
 * Main plugin class responsible for initializing the plugin
 */
class MemberPress_AI_Assistant {

    /**
     * Instance of this class.
     *
     * @var object
     */
    protected static $instance = null;
    
    /**
     * Whether MemberPress is available
     *
     * @var bool
     */
    private $has_memberpress = false;
    
    /**
     * Whether to use the new menu system
     * 
     * @var bool
     */
    private $use_new_menu_system = false;

    /**
     * Initialize the plugin.
     */
    private function __construct() {
        // Load required files
        $this->load_dependencies();
        
        // Initialize the new Admin Menu system (simpler approach)
        $this->init_admin_menu();
        
        // Initialize plugin components
        add_action('init', array($this, 'init_plugin_components'));
        
        // Check if MemberPress is active - now we run this at a later priority to ensure MemberPress is loaded
        add_action('plugins_loaded', array($this, 'check_memberpress'), 15);
        
        // Admin assets (but not menu which is handled by new system)
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Enqueue admin menu icon styles on all admin pages
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_menu_styles'));
        
        // Fix menu highlighting for settings page
        add_action('admin_head', array($this, 'fix_global_menu_highlighting'));
        
        // Process consent form submissions
        add_action('admin_init', array($this, 'process_consent_form'));
        
        // Handle redirection after plugin activation
        add_action('admin_init', array($this, 'maybe_redirect_after_activation'));
        
        // Add chat interface to admin footer
        add_action('admin_footer', array($this, 'render_chat_interface'));
        
        // Initialize REST API
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        
        // Register AJAX handlers
        add_action('wp_ajax_mpai_process_chat', array($this, 'process_chat_ajax'));
        add_action('wp_ajax_mpai_clear_chat_history', array($this, 'clear_chat_history_ajax'));
        add_action('wp_ajax_mpai_get_chat_history', array($this, 'get_chat_history_ajax'));
        add_action('wp_ajax_mpai_save_consent', array($this, 'save_consent_ajax'));
        
        // Plugin Logger AJAX handlers
        add_action('wp_ajax_mpai_get_plugin_logs', array($this, 'get_plugin_logs_ajax'));
        add_action('wp_ajax_mpai_get_plugin_log_details', array($this, 'get_plugin_log_details_ajax'));
        add_action('wp_ajax_mpai_export_plugin_logs', array($this, 'export_plugin_logs_ajax'));
        add_action('wp_ajax_mpai_update_plugin_logging_setting', array($this, 'update_plugin_logging_setting_ajax'));
        
        // Special AI assistant plugin logs handler with no nonce check
        add_action('wp_ajax_mpai_ai_plugin_logs', array($this, 'get_ai_plugin_logs_ajax'));
        
        // Error Recovery System test handler
        add_action('wp_ajax_mpai_test_error_recovery', array($this, 'test_error_recovery_ajax'));
        
        // Register activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Initialize the new Admin Menu system
     */
    private function init_admin_menu() {
        // Set flag to disable legacy menu
        $this->use_new_menu_system = true;
        
        // Create admin menu instance
        global $mpai_admin_menu;
        $mpai_admin_menu = new MPAI_Admin_Menu();
        
        // Add admin menu stylesheet
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_menu_styles'));
    }

    /**
     * Return an instance of this class.
     *
     * @return object A single instance of this class.
     */
    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }

    /**
     * Check if MemberPress is active, store status and display upsell notice if needed
     */
    public function check_memberpress() {
        // Start with the assumption that MemberPress is not active
        $has_memberpress = false;
        
        // Check for MemberPress class definitions
        $classes_to_check = [
            'MeprAppCtrl',
            'MeprOptions',
            'MeprUser',
            'MeprProduct',
            'MeprTransaction',
            'MeprSubscription'
        ];
        
        foreach ($classes_to_check as $class) {
            if (class_exists($class)) {
                $has_memberpress = true;
                break;
            }
        }
        
        // Check for MemberPress constants
        $constants_to_check = [
            'MEPR_VERSION',
            'MEPR_PLUGIN_NAME',
            'MEPR_PATH',
            'MEPR_URL'
        ];
        
        foreach ($constants_to_check as $constant) {
            if (defined($constant)) {
                $has_memberpress = true;
                break;
            }
        }
        
        // Check if the MemberPress plugin is active (the most reliable method)
        if (function_exists('is_plugin_active') && is_plugin_active('memberpress/memberpress.php')) {
            $has_memberpress = true;
        }
        
        // Check if MemberPress API exists
        if (class_exists('MeprApi') || class_exists('MeprRestApi')) {
            $has_memberpress = true;
        }
        
        // Also check if the 'memberpress' admin menu exists as a last resort
        global $menu;
        if (is_array($menu)) {
            foreach ($menu as $item) {
                if (isset($item[2]) && $item[2] === 'memberpress') {
                    $has_memberpress = true;
                    break;
                }
            }
        }
        
        // Special case for settings page - Always force MemberPress detection on settings page
        // This ensures the MemberPress menu is highlighted and visible
        global $pagenow;
        if (($pagenow === 'admin.php' && isset($_GET['page']) && $_GET['page'] === 'memberpress-ai-assistant-settings') ||
            (isset($plugin_page) && $plugin_page === 'memberpress-ai-assistant-settings')) {
            $has_memberpress = true;
            error_log('MPAI DEBUG: Force-enabling MemberPress detection on settings page for menu highlight');
        }
        
        // Store the result
        $this->has_memberpress = $has_memberpress;
        
        // Display upsell notice if MemberPress is not active
        if (!$this->has_memberpress) {
            add_action('admin_notices', array($this, 'memberpress_upsell_notice'));
        }
    }

    /**
     * Display MemberPress upsell notice
     */
    public function memberpress_upsell_notice() {
        if (isset($_GET['page']) && (strpos($_GET['page'], 'memberpress-ai-assistant') === 0)) {
            ?>
            <div class="notice notice-info is-dismissible mpai-upsell-notice">
                <h3><?php _e('Enhance Your AI Assistant with MemberPress', 'memberpress-ai-assistant'); ?></h3>
                <p><?php _e('You\'re currently using the standalone version of MemberPress AI Assistant. Upgrade to the full MemberPress platform to unlock advanced membership management features including:', 'memberpress-ai-assistant'); ?></p>
                <ul class="mpai-upsell-features">
                    <li><?php _e('Create and sell membership levels', 'memberpress-ai-assistant'); ?></li>
                    <li><?php _e('Protect content with flexible rules', 'memberpress-ai-assistant'); ?></li>
                    <li><?php _e('Process payments and subscriptions', 'memberpress-ai-assistant'); ?></li>
                    <li><?php _e('Access detailed reporting', 'memberpress-ai-assistant'); ?></li>
                    <li><?php _e('Unlock all AI Assistant features', 'memberpress-ai-assistant'); ?></li>
                </ul>
                <p><a href="https://memberpress.com/plans/?utm_source=ai_assistant&utm_medium=plugin&utm_campaign=upsell" class="button button-primary" target="_blank"><?php _e('Learn More About MemberPress', 'memberpress-ai-assistant'); ?></a></p>
            </div>
            <style>
                .mpai-upsell-notice {
                    border-left-color: #2271b1;
                    padding: 10px 15px;
                }
                .mpai-upsell-notice h3 {
                    margin-top: 0.5em;
                    margin-bottom: 0.5em;
                }
                .mpai-upsell-features {
                    list-style-type: disc;
                    padding-left: 20px;
                    margin-bottom: 15px;
                }
            </style>
            <?php
        }
    }
    
    /**
     * Globally highlight the parent menu for our plugin pages
     * This is a central function that will be called from multiple places
     * to ensure consistent menu highlighting
     * 
     * @param string $parent_file The parent file
     * @return string Modified parent file
     */
    public function highlight_parent_menu($parent_file) {
        global $plugin_page, $submenu_file;
        
        // Debug info to help diagnose issues
        error_log('MPAI DEBUG: highlight_parent_menu - plugin_page: ' . $plugin_page);
        error_log('MPAI DEBUG: highlight_parent_menu - parent_file before: ' . $parent_file);
        error_log('MPAI DEBUG: highlight_parent_menu - has_memberpress: ' . ($this->has_memberpress ? 'true' : 'false'));
        
        // If we're on our settings page or any plugin page
        if ($plugin_page === 'memberpress-ai-assistant-settings' || 
            $plugin_page === 'memberpress-ai-assistant') {
            
            // Set the correct parent file based on whether MemberPress is active
            if ($this->has_memberpress) {
                // If MemberPress is active, set parent to the MemberPress menu
                $parent_file = 'memberpress';
                
                // Also set the submenu file for proper submenu highlighting
                if ($plugin_page === 'memberpress-ai-assistant-settings') {
                    $GLOBALS['submenu_file'] = 'memberpress-ai-assistant-settings';
                } else {
                    $GLOBALS['submenu_file'] = 'memberpress-ai-assistant';
                }
            } else {
                // If MemberPress is not active, set parent to our own top-level menu
                $parent_file = 'memberpress-ai-assistant';
            }
            
            error_log('MPAI DEBUG: highlight_parent_menu - parent_file after: ' . $parent_file);
        }
        
        return $parent_file;
    }

    /**
     * Load required dependencies
     */
    private function load_dependencies() {
        // Load Error Recovery System first for exception handling
        require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-error-recovery.php';
        
        // Load State Validation System for state consistency
        require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-state-validator.php';
        
        // API Integration Classes
        require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-openai.php';
        require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-anthropic.php';
        require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-api-router.php';
        require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-memberpress-api.php';
        
        // Functionality Classes
        require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-chat.php';
        require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-context-manager.php';
        require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-plugin-logger.php';
        require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-xml-content-parser.php';
        
        // New Admin Menu Class
        require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-admin-menu.php';
        
        // Legacy Admin and Settings (for backward compatibility)
        require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-admin.php';
        require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-settings.php';
        
        // Chat Interface
        require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-chat-interface.php';
        
        // Agent System
        $this->load_agent_system();
        
        // Integration Tests
        if (is_admin() && file_exists(MPAI_PLUGIN_DIR . 'test/integration/register-integration-tests.php')) {
            require_once MPAI_PLUGIN_DIR . 'test/integration/register-integration-tests.php';
        }
        
        // Load the new diagnostics system
        if (is_admin() && file_exists(MPAI_PLUGIN_DIR . 'includes/class-mpai-diagnostics.php')) {
            require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-diagnostics.php';
            
            // Load test files
            if (file_exists(MPAI_PLUGIN_DIR . 'includes/tests/load-tests.php')) {
                require_once MPAI_PLUGIN_DIR . 'includes/tests/load-tests.php';
            }
        }
        
        // CLI Commands - always load to ensure early initialization
        // The CLI commands file itself handles WP-CLI availability checks
        require_once MPAI_PLUGIN_DIR . 'includes/cli/class-mpai-cli-commands.php';
    }
    
    /**
     * Load agent system components
     */
    private function load_agent_system() {
        // Load agent interface
        require_once MPAI_PLUGIN_DIR . 'includes/agents/interfaces/interface-mpai-agent.php';
        
        // Load base tool class
        if (!class_exists('MPAI_Base_Tool')) {
            require_once MPAI_PLUGIN_DIR . 'includes/tools/class-mpai-base-tool.php';
        }
        
        // Load tool registry
        if (!class_exists('MPAI_Tool_Registry')) {
            require_once MPAI_PLUGIN_DIR . 'includes/tools/class-mpai-tool-registry.php';
        }
        
        // Load tool implementations
        $tool_dir = MPAI_PLUGIN_DIR . 'includes/tools/implementations/';
        if (file_exists($tool_dir)) {
            foreach (glob($tool_dir . 'class-mpai-*.php') as $tool_file) {
                require_once $tool_file;
            }
        }
        
        // Load base agent class
        require_once MPAI_PLUGIN_DIR . 'includes/agents/class-mpai-base-agent.php';
        
        // Load specialized agents
        $agents_dir = MPAI_PLUGIN_DIR . 'includes/agents/specialized/';
        if (file_exists($agents_dir)) {
            foreach (glob($agents_dir . 'class-mpai-*.php') as $agent_file) {
                require_once $agent_file;
            }
        }
        
        // Load SDK integration
        $sdk_dir = MPAI_PLUGIN_DIR . 'includes/agents/sdk/';
        if (file_exists($sdk_dir . 'class-mpai-sdk-integration.php')) {
            require_once $sdk_dir . 'class-mpai-sdk-integration.php';
        }
        
        // Finally, load the orchestrator
        require_once MPAI_PLUGIN_DIR . 'includes/agents/class-mpai-agent-orchestrator.php';
    }

    /**
     * Add admin menu items
     */
    public function add_admin_menu() {
        // Skip if using the new menu system
        if ($this->use_new_menu_system) {
            return;
        }
        
        // Force a memberpress check right before creating menus
        $this->check_memberpress();
        
        // Main menu page slug - pointing to dashboard page
        $main_page_slug = 'memberpress-ai-assistant';
        
        if ($this->has_memberpress) {
            // If MemberPress is active, add as a submenu to MemberPress
            $main_page = add_submenu_page(
                'memberpress', // Parent menu slug
                __('AI Assistant', 'memberpress-ai-assistant'), // Page title
                __('AI Assistant', 'memberpress-ai-assistant'), // Menu title
                'manage_options', // Capability
                $main_page_slug, // Menu slug points to dashboard
                array($this, 'display_admin_page') // Use dashboard page as the main page
            );
        } else {
            // If MemberPress is not active, add as a top-level menu
            $main_page = add_menu_page(
                __('MemberPress AI', 'memberpress-ai-assistant'), // Page title
                __('MemberPress AI', 'memberpress-ai-assistant'), // Menu title
                'manage_options', // Capability
                $main_page_slug, // Menu slug points to dashboard
                array($this, 'display_admin_page'), // Use dashboard page as the main page
                MPAI_PLUGIN_URL . 'assets/images/memberpress-logo.svg', // Icon
                30 // Position
            );
            
            // Add a submenu item for the dashboard to match parent
            add_submenu_page(
                $main_page_slug, 
                __('Dashboard', 'memberpress-ai-assistant'),
                __('Dashboard', 'memberpress-ai-assistant'),
                'manage_options',
                $main_page_slug, 
                array($this, 'display_admin_page')
            );
        }
        
        // Critical fix: ALWAYS add settings under memberpress when it's active
        $settings_parent = $this->has_memberpress ? 'memberpress' : $main_page_slug;
        
        // Add the settings page as a submenu
        $settings_page = add_submenu_page(
            $settings_parent, // Use memberpress as parent when it's active
            __('Settings', 'memberpress-ai-assistant'),
            __('Settings', 'memberpress-ai-assistant'),
            'manage_options',
            'memberpress-ai-assistant-settings',
            array($this, 'display_settings_page')
        );
        
        // Add hook for settings page load
        add_action('load-' . $settings_page, array($this, 'settings_page_load'));
    }
    
    /**
     * Settings page load hook
     */
    public function settings_page_load() {
        // Register a late-running action to fix menu highlighting
        add_action('admin_head', array($this, 'fix_settings_page_menu_highlight'), 9999);
        
        // Add filter for parent file to fix menu highlighting
        add_filter('parent_file', array($this, 'highlight_parent_menu'), 999);
        
        // Register settings
        register_setting('mpai_options', 'mpai_api_key');
        register_setting('mpai_options', 'mpai_model');
        register_setting('mpai_options', 'mpai_temperature', array(
            'sanitize_callback' => function($value) {
                return floatval($value);
            },
        ));
        register_setting('mpai_options', 'mpai_max_tokens', array(
            'sanitize_callback' => 'absint',
        ));
        register_setting('mpai_options', 'mpai_enable_chat', array(
            'sanitize_callback' => function($value) {
                return (bool) $value;
            },
        ));
        register_setting('mpai_options', 'mpai_chat_position', array(
            'sanitize_callback' => 'sanitize_text_field',
        ));
        register_setting('mpai_options', 'mpai_show_on_all_pages', array(
            'sanitize_callback' => function($value) {
                return (bool) $value;
            },
        ));
        register_setting('mpai_options', 'mpai_welcome_message', array(
            'sanitize_callback' => 'wp_kses_post',
        ));
    }

    /**
     * Display main admin page
     */
    public function display_admin_page() {
        error_log('MPAI DEBUG: Displaying main admin dashboard page');
        require_once MPAI_PLUGIN_DIR . 'includes/dashboard-page.php';
    }
    
    /**
     * Process consent form submission from dashboard page
     */
    public function process_consent_form() {
        error_log('MPAI DEBUG: Checking for consent form submission');
        
        // Check if the consent form was submitted
        if (isset($_POST['mpai_save_consent']) && isset($_POST['mpai_consent'])) {
            // Verify nonce
            if (!isset($_POST['mpai_consent_nonce']) || !wp_verify_nonce($_POST['mpai_consent_nonce'], 'mpai_consent_nonce')) {
                error_log('MPAI ERROR: Consent form nonce verification failed');
                add_settings_error('mpai_messages', 'mpai_consent_error', __('Security check failed.', 'memberpress-ai-assistant'), 'error');
                return;
            }
            
            // Save consent to options
            update_option('mpai_consent_given', true);
            error_log('MPAI DEBUG: User consent saved successfully');
            
            // Add a transient message
            add_settings_error(
                'mpai_messages', 
                'mpai_consent_success', 
                __('Thank you for agreeing to the terms. You can now use the MemberPress AI Assistant.', 'memberpress-ai-assistant'), 
                'updated'
            );
            
            // Redirect to remove POST data and show the dashboard (which is now our main page)
            wp_redirect(admin_url('admin.php?page=memberpress-ai-assistant&consent=given'));
            exit;
        }
    }

    /**
     * Fix menu highlighting for settings page
     * This function runs in admin_head to directly manipulate the global variables
     * responsible for menu highlighting
     */
    public function fix_settings_page_menu_highlight() {
        // Skip if using the new menu system
        if ($this->use_new_menu_system) {
            return;
        }
        
        global $parent_file, $submenu_file, $plugin_page;
        
        // Check current page and tab
        $is_settings_page = ($plugin_page === 'memberpress-ai-assistant-settings');
        $is_diagnostic_tab = isset($_GET['tab']) && $_GET['tab'] === 'diagnostic';
        
        // Only run on our settings page (or diagnostic tab)
        if (!$is_settings_page && !$is_diagnostic_tab) {
            return;
        }
        
        // Force MemberPress detection
        $this->check_memberpress();
        $this->has_memberpress = true; // Force this to be true for menu highlighting
        
        // Set the global variables directly
        $parent_file = 'memberpress';
        $submenu_file = 'memberpress-ai-assistant-settings';
        
        // Add JavaScript to ensure menu is visible
        echo "<script>
            jQuery(document).ready(function($) {
                // Function to fix menu highlighting that can be called multiple times
                function fixMenu() {
                    // Ensure MemberPress menu is highlighted and expanded
                    $('#toplevel_page_memberpress')
                        .addClass('wp-has-current-submenu wp-menu-open')
                        .removeClass('wp-not-current-submenu');
                    
                    $('#toplevel_page_memberpress > a')
                        .addClass('wp-has-current-submenu wp-menu-open')
                        .removeClass('wp-not-current-submenu');
                    
                    // Find and highlight our submenu item
                    $('#toplevel_page_memberpress .wp-submenu li a[href*=\"memberpress-ai-assistant-settings\"]')
                        .parent().addClass('current');
                    
                    // Make all elements visible
                    $('#toplevel_page_memberpress').show();
                    $('#toplevel_page_memberpress .wp-submenu').show();
                }
                
                // Run the fix immediately
                fixMenu();
                
                // Also run when a tab is clicked
                $('.nav-tab').on('click', function() {
                    setTimeout(fixMenu, 50);
                });
                
                // Also run after any AJAX calls
                $(document).ajaxComplete(function() {
                    fixMenu();
                });
                
                // Run again after a delay to ensure it applies
                setTimeout(fixMenu, 100);
                
                // Special handling for diagnostic tab
                " . ($is_diagnostic_tab ? "
                // We're on the diagnostic tab, force it to be visible
                $('.nav-tab[href=\"#tab-diagnostic\"]').click();
                // Add a MutationObserver to detect tab display changes
                var observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.attributeName === 'style') {
                            fixMenu();
                        }
                    });
                });
                
                // Start observing the tab content display changes
                if (document.getElementById('tab-diagnostic')) {
                    observer.observe(document.getElementById('tab-diagnostic'), {
                        attributes: true
                    });
                }
                " : "") . "
            });
        </script>";
    }
    
    /**
     * Display settings page
     */
    public function display_settings_page() {
        // Make sure MemberPress status is checked
        $this->check_memberpress();
        
        require_once MPAI_PLUGIN_DIR . 'includes/settings-page.php';
    }

    /**
     * Render chat interface in admin footer
     */
    public function render_chat_interface() {
        // Check if we're on MemberPress admin pages that might have conflicts
        $skip_rendering = false;
        
        // MemberPress product (membership level) editing/creation pages
        if (isset($_GET['page']) && $_GET['page'] === 'memberpress-products' && 
            (isset($_GET['action']) && ($_GET['action'] === 'edit' || $_GET['action'] === 'new'))) {
            $skip_rendering = true;
        }
        
        // MemberPress rules editing/creation pages
        if (isset($_GET['page']) && $_GET['page'] === 'memberpress-rules' && 
            (isset($_GET['action']) && ($_GET['action'] === 'edit' || $_GET['action'] === 'new'))) {
            $skip_rendering = true;
        }
        
        // MemberPress coupons editing/creation pages
        if (isset($_GET['page']) && $_GET['page'] === 'memberpress-coupons' && 
            (isset($_GET['action']) && ($_GET['action'] === 'edit' || $_GET['action'] === 'new'))) {
            $skip_rendering = true;
        }
        
        // MemberPress transactions and subscriptions editing pages
        if (isset($_GET['page']) && ($_GET['page'] === 'memberpress-trans' || $_GET['page'] === 'memberpress-subscriptions') && 
            (isset($_GET['action']) && $_GET['action'] === 'edit')) {
            $skip_rendering = true;
        }
        
        // For compatibility, don't render the chat interface on these MemberPress pages
        if ($skip_rendering) {
            return;
        }
        
        require_once MPAI_PLUGIN_DIR . 'includes/chat-interface.php';
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Check if we're on MemberPress admin pages that might have conflicts
        $skip_loading = false;
        
        // MemberPress product (membership level) editing/creation pages
        if (isset($_GET['page']) && $_GET['page'] === 'memberpress-products' && 
            (isset($_GET['action']) && ($_GET['action'] === 'edit' || $_GET['action'] === 'new'))) {
            $skip_loading = true;
        }
        
        // MemberPress rules editing/creation pages
        if (isset($_GET['page']) && $_GET['page'] === 'memberpress-rules' && 
            (isset($_GET['action']) && ($_GET['action'] === 'edit' || $_GET['action'] === 'new'))) {
            $skip_loading = true;
        }
        
        // MemberPress coupons editing/creation pages
        if (isset($_GET['page']) && $_GET['page'] === 'memberpress-coupons' && 
            (isset($_GET['action']) && ($_GET['action'] === 'edit' || $_GET['action'] === 'new'))) {
            $skip_loading = true;
        }
        
        // MemberPress transactions and subscriptions editing pages
        if (isset($_GET['page']) && ($_GET['page'] === 'memberpress-trans' || $_GET['page'] === 'memberpress-subscriptions') && 
            (isset($_GET['action']) && $_GET['action'] === 'edit')) {
            $skip_loading = true;
        }
        
        // For compatibility, don't load our scripts on these MemberPress pages
        if ($skip_loading) {
            return;
        }
        
        // Load chat interface assets on all other admin pages
        wp_enqueue_style('dashicons');
        
        wp_enqueue_style(
            'mpai-chat-css',
            MPAI_PLUGIN_URL . 'assets/css/chat-interface.css',
            array('dashicons'),
            MPAI_VERSION
        );

        // Load the logger script first, so it's available for other scripts
        wp_enqueue_script(
            'mpai-logger-js',
            MPAI_PLUGIN_URL . 'assets/js/mpai-logger.js',
            array('jquery'),
            MPAI_VERSION,
            true
        );

        // Load modular JavaScript files in the correct order
        wp_enqueue_script(
            'mpai-chat-formatters',
            MPAI_PLUGIN_URL . 'assets/js/modules/mpai-chat-formatters.js',
            array('jquery', 'mpai-logger-js'),
            MPAI_VERSION,
            true
        );

        wp_enqueue_script(
            'mpai-chat-ui-utils',
            MPAI_PLUGIN_URL . 'assets/js/modules/mpai-chat-ui-utils.js',
            array('jquery', 'mpai-logger-js'),
            MPAI_VERSION,
            true
        );

        wp_enqueue_script(
            'mpai-chat-messages',
            MPAI_PLUGIN_URL . 'assets/js/modules/mpai-chat-messages.js',
            array('jquery', 'mpai-logger-js', 'mpai-chat-formatters', 'mpai-chat-ui-utils'),
            MPAI_VERSION,
            true
        );

        wp_enqueue_script(
            'mpai-chat-tools',
            MPAI_PLUGIN_URL . 'assets/js/modules/mpai-chat-tools.js',
            array('jquery', 'mpai-logger-js', 'mpai-chat-formatters', 'mpai-chat-messages'),
            MPAI_VERSION,
            true
        );

        wp_enqueue_script(
            'mpai-chat-history',
            MPAI_PLUGIN_URL . 'assets/js/modules/mpai-chat-history.js',
            array('jquery', 'mpai-logger-js', 'mpai-chat-messages'),
            MPAI_VERSION,
            true
        );
        
        wp_enqueue_script(
            'mpai-blog-formatter',
            MPAI_PLUGIN_URL . 'assets/js/modules/mpai-blog-formatter.js',
            array('jquery', 'mpai-logger-js', 'mpai-chat-messages', 'mpai-chat-tools'),
            MPAI_VERSION,
            true
        );

        // Load the main chat interface loader script
        wp_enqueue_script(
            'mpai-chat-js',
            MPAI_PLUGIN_URL . 'assets/js/modules/chat-interface-loader.js',
            array(
                'jquery', 
                'mpai-logger-js', 
                'mpai-chat-formatters', 
                'mpai-chat-ui-utils', 
                'mpai-chat-messages', 
                'mpai-chat-tools', 
                'mpai-chat-history',
                'mpai-blog-formatter'
            ),
            MPAI_VERSION,
            true
        );

        // Create nonces for JavaScript
        $mpai_nonce = wp_create_nonce('mpai_nonce');
        $chat_nonce = wp_create_nonce('mpai_chat_nonce');
        
        // Log the nonces we're passing to JS (first few chars only for security)
        // Generated nonces for JS

        // Get logger settings - ensure we're passing consistent types
        $log_enabled = get_option('mpai_enable_console_logging', '0');
        // Console logging setting
        
        $logger_settings = array(
            'enabled' => ($log_enabled === '1') ? true : false, // Convert to boolean
            'log_level' => get_option('mpai_console_log_level', 'info'), // Default to info level
            'categories' => array(
                'api_calls' => get_option('mpai_log_api_calls', '0') === '1',
                'tool_usage' => get_option('mpai_log_tool_usage', '0') === '1',
                'agent_activity' => get_option('mpai_log_agent_activity', '0') === '1',
                'timing' => get_option('mpai_log_timing', '0') === '1',
                'ui' => true // Always enable UI logging
            )
        );
        
        // Pass settings to all scripts through mpai_data
        $shared_data = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => $mpai_nonce,
            'plugin_url' => MPAI_PLUGIN_URL,
            'logger' => $logger_settings
        );

        // Localize the logger script with settings
        wp_localize_script(
            'mpai-logger-js',
            'mpai_data',
            $shared_data
        );
        
        wp_localize_script(
            'mpai-chat-js',
            'mpai_chat_data',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => $chat_nonce,
                'mpai_nonce' => $mpai_nonce, // Add the regular nonce for tool execution
                'plugin_url' => MPAI_PLUGIN_URL, // Add plugin URL for direct AJAX handlers
                'strings' => array(
                    'send_message' => __('Send message', 'memberpress-ai-assistant'),
                    'typing' => __('MemberPress AI is typing...', 'memberpress-ai-assistant'),
                    'welcome_message' => get_option('mpai_welcome_message', __('Hi there! I\'m your MemberPress AI Assistant. How can I help you today?', 'memberpress-ai-assistant')),
                    'error_message' => __('Sorry, there was an error processing your request. Please try again.', 'memberpress-ai-assistant'),
                ),
                'tools_enabled' => array(
                    'mcp' => get_option('mpai_enable_mcp', true) ? true : false,
                    'cli_commands' => get_option('mpai_enable_cli_commands', true) ? true : false,
                    'wp_cli_tool' => get_option('mpai_enable_wp_cli_tool', true) ? true : false,
                    'memberpress_info_tool' => get_option('mpai_enable_memberpress_info_tool', true) ? true : false,
                    'plugin_logs_tool' => get_option('mpai_enable_plugin_logs_tool', true) ? true : false
                )
            )
        );

        // Only load admin page specific assets on our admin pages
        if (strpos($hook, 'memberpress-ai-assistant') !== false) {
            wp_enqueue_style(
                'mpai-admin-css',
                MPAI_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                MPAI_VERSION
            );

            wp_enqueue_script(
                'mpai-admin-js',
                MPAI_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery', 'mpai-logger-js'),
                MPAI_VERSION,
                true
            );

            // Add additional data specific to admin pages
            $admin_data = array_merge($shared_data, array(
                'rest_url' => rest_url('mpai/v1/'),
                'page' => $hook
            ));

            wp_localize_script(
                'mpai-admin-js',
                'mpai_data',
                $admin_data
            );
        }
    }
    
    /**
     * Enqueue admin menu styles - handles the icon size in admin menu
     */
    public function enqueue_admin_menu_styles() {
        // Load the admin menu styles on all admin pages
        wp_enqueue_style(
            'mpai-admin-menu-css',
            MPAI_PLUGIN_URL . 'assets/css/admin-menu.css',
            array(),
            MPAI_VERSION
        );
    }
    
    /**
     * Fix menu highlighting globally by using JavaScript
     * This runs on all admin pages but only applies fixes when needed
     */
    public function fix_global_menu_highlighting() {
        // Only run in admin
        if (!is_admin()) {
            return;
        }
        
        // Skip if using the new menu system which has its own highlighting
        if ($this->use_new_menu_system) {
            return;
        }
        
        // Get the current page from URL
        $current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        
        // Special handling for our settings page (including any tabs within it)
        if ($current_page === 'memberpress-ai-assistant-settings') {
            // Force check MemberPress status
            $this->check_memberpress();
            
            if ($this->has_memberpress) {
                // Add JavaScript to fix menu highlighting
                ?>
                <script type="text/javascript">
                jQuery(document).ready(function($) {
                    // Ensure MemberPress menu is highlighted and expanded
                    $('#toplevel_page_memberpress')
                        .addClass('wp-has-current-submenu wp-menu-open')
                        .removeClass('wp-not-current-submenu');
                    
                    $('#toplevel_page_memberpress > a')
                        .addClass('wp-has-current-submenu wp-menu-open')
                        .removeClass('wp-not-current-submenu');
                    
                    // Highlight our AI Assistant submenu item
                    $('#toplevel_page_memberpress .wp-submenu li a[href*="memberpress-ai-assistant-settings"]')
                        .parent().addClass('current');
                    
                    // Handle tab switching to maintain menu highlighting
                    $('.nav-tab').on('click', function() {
                        // Reapply menu highlighting when tabs are clicked
                        setTimeout(function() {
                            $('#toplevel_page_memberpress')
                                .addClass('wp-has-current-submenu wp-menu-open')
                                .removeClass('wp-not-current-submenu');
                            
                            $('#toplevel_page_memberpress > a')
                                .addClass('wp-has-current-submenu wp-menu-open')
                                .removeClass('wp-not-current-submenu');
                            
                            $('#toplevel_page_memberpress .wp-submenu li a[href*="memberpress-ai-assistant-settings"]')
                                .parent().addClass('current');
                        }, 50);
                    });
                });
                </script>
                <?php
            }
        }
    }

    /**
     * Process chat message via AJAX
     */
    public function process_chat_ajax() {
        try {
            // Check nonce for security
            check_ajax_referer('mpai_chat_nonce', 'nonce');
            
            error_log('MPAI: AJAX process_chat_ajax started');

            // Only allow logged-in users with appropriate capabilities
            if (!current_user_can('edit_posts')) {
                error_log('MPAI: AJAX unauthorized access attempt');
                wp_send_json_error('Unauthorized access');
                return;
            }

            // Get the message from the request
            if (!isset($_POST['message'])) {
                error_log('MPAI: AJAX No message provided');
                wp_send_json_error('No message provided');
                return;
            }
            
            $message = sanitize_text_field($_POST['message']);
            
            if (empty($message)) {
                error_log('MPAI: AJAX Empty message');
                wp_send_json_error('Message cannot be empty');
                return;
            }
            
            error_log('MPAI: AJAX Processing message: ' . $message);

            try {
                // Process the message using the chat handler
                if (!class_exists('MPAI_Chat')) {
                    error_log('MPAI: AJAX MPAI_Chat class not found');
                    require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-chat.php';
                    
                    if (!class_exists('MPAI_Chat')) {
                        error_log('MPAI: AJAX Failed to load MPAI_Chat class even after requiring file');
                        wp_send_json_error('Internal error: MPAI_Chat class not available');
                        return;
                    }
                    
                    error_log('MPAI: AJAX MPAI_Chat class loaded successfully');
                }
                
                try {
                    error_log('MPAI: AJAX Creating MPAI_Chat instance');
                    $chat = new MPAI_Chat();
                    error_log('MPAI: AJAX MPAI_Chat instance created successfully');
                } catch (Throwable $chat_instance_error) {
                    error_log('MPAI: AJAX Fatal error creating MPAI_Chat instance: ' . $chat_instance_error->getMessage() . ' in ' . $chat_instance_error->getFile() . ' on line ' . $chat_instance_error->getLine());
                    error_log('MPAI: AJAX Stack trace: ' . $chat_instance_error->getTraceAsString());
                    wp_send_json_error('Error initializing chat system. Check error logs for details.');
                    return;
                }
                
                try {
                    error_log('MPAI: AJAX Calling process_message on MPAI_Chat instance');
                    $response_data = $chat->process_message($message);
                    error_log('MPAI: AJAX process_message completed successfully');
                } catch (Throwable $process_error) {
                    error_log('MPAI: AJAX Error in process_message: ' . $process_error->getMessage() . ' in ' . $process_error->getFile() . ' on line ' . $process_error->getLine());
                    error_log('MPAI: AJAX Stack trace: ' . $process_error->getTraceAsString());
                    throw $process_error; // Re-throw to be caught by the outer catch
                }

                // For debugging
                error_log('MPAI: AJAX response data: ' . (is_array($response_data) ? json_encode($response_data) : (is_object($response_data) ? 'Object of class ' . get_class($response_data) : (string)$response_data)));

                // Extract response content for saving to history
                if (is_array($response_data) && isset($response_data['message'])) {
                    $response_content = $response_data['message'];
                } else if (is_array($response_data) && isset($response_data['success']) && isset($response_data['raw_response'])) {
                    $response_content = $response_data['raw_response'];
                } else if (is_string($response_data)) {
                    $response_content = $response_data;
                } else {
                    $response_content = 'Invalid response format';
                    error_log('MPAI: AJAX Invalid response format, setting default response content');
                }
                
                // Always save to user meta to ensure chat history is available
                try {
                    error_log('MPAI: AJAX Saving message to history');
                    $this->save_message_to_history($message, $response_content);
                    error_log('MPAI: AJAX Message saved to history successfully');
                } catch (Throwable $history_error) {
                    error_log('MPAI: AJAX Error saving message to history: ' . $history_error->getMessage());
                    // Continue even if history saving fails
                }
                
                // Log whether we're using database storage
                error_log('MPAI: AJAX Using database storage: ' . ($this->is_using_database_storage(false) ? 'yes' : 'no'));

                // Standardize the response format to ensure consistent structure
                if ($response_data) {
                    if (is_array($response_data) && isset($response_data['success'])) {
                        // If it's already in the expected format with success flag
                        if ($response_data['success']) {
                            error_log('MPAI: AJAX Sending success response');
                            wp_send_json_success(array(
                                'response' => isset($response_data['message']) ? $response_data['message'] : 'Success but no message provided',
                            ));
                        } else {
                            error_log('MPAI: AJAX Sending error response from response_data');
                            wp_send_json_error(isset($response_data['message']) ? $response_data['message'] : 'Unknown error occurred');
                        }
                    } else if (is_string($response_data)) {
                        // Just a direct string response
                        error_log('MPAI: AJAX Sending success response with string');
                        wp_send_json_success(array(
                            'response' => $response_data,
                        ));
                    } else {
                        // Invalid response format - log and return error
                        error_log('MPAI: AJAX Invalid response format: ' . print_r($response_data, true));
                        wp_send_json_success(array(
                            'response' => 'Response received but in unexpected format. Check error logs for details.',
                        ));
                    }
                } else {
                    error_log('MPAI: AJAX Empty response data');
                    wp_send_json_error('Failed to get response from AI service');
                }
            } catch (Throwable $e) {
                error_log('MPAI: AJAX Exception in inner try/catch of process_chat_ajax: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
                error_log('MPAI: AJAX Stack trace: ' . $e->getTraceAsString());
                wp_send_json_error('Error processing message: ' . $e->getMessage());
            }
        } catch (Throwable $e) {
            // Catch absolutely everything at the top level to prevent 500 errors
            error_log('MPAI: AJAX CRITICAL ERROR in process_chat_ajax: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            error_log('MPAI: AJAX Stack trace: ' . $e->getTraceAsString());
            wp_send_json_error('A system error occurred. Please check the server logs for more information.');
        }
    }
    
    /**
     * Check if we're using database storage for chat history
     * 
     * @param bool $attempt_creation Whether to attempt creating tables if they don't exist
     * @return bool Whether using database storage
     */
    private function is_using_database_storage($attempt_creation = true) {
        global $wpdb;
        $table_conversations = $wpdb->prefix . 'mpai_conversations';
        $table_messages = $wpdb->prefix . 'mpai_messages';
        
        // Check if tables exist
        $conversations_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_conversations}'") === $table_conversations;
        $messages_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_messages}'") === $table_messages;
        
        // If tables don't exist and should try to create them
        if (!$conversations_exists || !$messages_exists) {
            error_log('MPAI: Database tables missing. Conversations: ' . ($conversations_exists ? 'exists' : 'missing') . 
                     ', Messages: ' . ($messages_exists ? 'exists' : 'missing'));
            
            if ($attempt_creation) {
                error_log('MPAI: Attempting to create missing database tables');
                $tables_created = $this->create_database_tables();
                
                if ($tables_created) {
                    error_log('MPAI: Successfully created database tables');
                    return true;
                } else {
                    error_log('MPAI: Failed to create database tables. Falling back to user meta storage');
                    return false;
                }
            }
        }
        
        return $conversations_exists && $messages_exists;
    }
    
    /**
     * AJAX handler for getting plugin logs
     */
    public function get_plugin_logs_ajax() {
        // Check nonce for security
        check_ajax_referer('mpai_nonce', 'nonce');
        
        // Allow all logged-in users to access plugin logs for AI Assistant
        // This is needed so Claude can get plugin info regardless of user role
        if (!is_user_logged_in()) {
            wp_send_json_error('Unauthorized access');
            return;
        }
        
        try {
            // Initialize the plugin logger
            $plugin_logger = mpai_init_plugin_logger();
            
            // Get filter parameters
            $action = isset($_POST['log_action']) ? sanitize_text_field($_POST['log_action']) : '';
            $plugin_name = isset($_POST['plugin_name']) ? sanitize_text_field($_POST['plugin_name']) : '';
            $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
            $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
            $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 10;
            
            // Calculate date range
            $date_from = '';
            if ($days > 0) {
                $date_from = date('Y-m-d H:i:s', strtotime("-{$days} days"));
            }
            
            // Prepare query arguments
            $args = array(
                'plugin_name' => $plugin_name,
                'action'      => $action,
                'date_from'   => $date_from,
                'orderby'     => 'date_time',
                'order'       => 'DESC',
                'limit'       => $per_page,
                'offset'      => ($page - 1) * $per_page,
            );
            
            // Get logs
            $logs = $plugin_logger->get_logs($args);
            
            // Get total count for pagination
            $count_args = array(
                'plugin_name' => $plugin_name,
                'action'      => $action,
                'date_from'   => $date_from,
            );
            $total = $plugin_logger->count_logs($count_args);
            
            // Get summary data
            $summary_days = $days > 0 ? $days : 365; // If all time, limit to 1 year for summary
            $summary = $plugin_logger->get_activity_summary($summary_days);
            
            // Count by action type 
            $action_counts = array(
                'total'       => 0,
                'installed'   => 0,
                'updated'     => 0,
                'activated'   => 0,
                'deactivated' => 0,
                'deleted'     => 0
            );
            
            if (isset($summary['action_counts']) && is_array($summary['action_counts'])) {
                foreach ($summary['action_counts'] as $count_data) {
                    if (isset($count_data['action']) && isset($count_data['count'])) {
                        $action_counts[$count_data['action']] = intval($count_data['count']);
                        $action_counts['total'] += intval($count_data['count']);
                    }
                }
            }
            
            wp_send_json_success(array(
                'logs'    => $logs,
                'total'   => $total,
                'summary' => $action_counts,
            ));
        } catch (Exception $e) {
            wp_send_json_error('Error retrieving plugin logs: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX handler for getting plugin log details
     */
    public function get_plugin_log_details_ajax() {
        // Check nonce for security
        check_ajax_referer('mpai_nonce', 'nonce');
        
        // Only allow logged-in users with appropriate capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized access');
            return;
        }
        
        try {
            // Initialize the plugin logger
            $plugin_logger = mpai_init_plugin_logger();
            
            // Get log ID
            $log_id = isset($_POST['log_id']) ? intval($_POST['log_id']) : 0;
            
            if ($log_id <= 0) {
                wp_send_json_error('Invalid log ID');
                return;
            }
            
            // Get log details
            $logs = $plugin_logger->get_logs(array(
                'id' => $log_id,
                'limit' => 1,
            ));
            
            if (empty($logs)) {
                wp_send_json_error('Log not found');
                return;
            }
            
            wp_send_json_success($logs[0]);
        } catch (Exception $e) {
            wp_send_json_error('Error retrieving log details: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX handler for exporting plugin logs to CSV
     */
    public function export_plugin_logs_ajax() {
        // Check nonce for security
        check_ajax_referer('mpai_nonce', 'nonce');
        
        // Only allow logged-in users with appropriate capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized access');
            return;
        }
        
        try {
            // Initialize the plugin logger
            $plugin_logger = mpai_init_plugin_logger();
            
            // Get filter parameters
            $action = isset($_POST['log_action']) ? sanitize_text_field($_POST['log_action']) : '';
            $plugin_name = isset($_POST['plugin_name']) ? sanitize_text_field($_POST['plugin_name']) : '';
            $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
            
            // Calculate date range
            $date_from = '';
            if ($days > 0) {
                $date_from = date('Y-m-d H:i:s', strtotime("-{$days} days"));
            }
            
            // Prepare export arguments
            $args = array(
                'plugin_name' => $plugin_name,
                'action'      => $action,
                'date_from'   => $date_from,
                'orderby'     => 'date_time',
                'order'       => 'DESC',
                'limit'       => 1000, // Limit to 1000 records for export
            );
            
            // Generate CSV data
            $csv_data = $plugin_logger->export_csv($args);
            
            wp_send_json_success($csv_data);
        } catch (Exception $e) {
            wp_send_json_error('Error exporting plugin logs: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX handler for updating plugin logging setting
     */
    public function update_plugin_logging_setting_ajax() {
        // Check nonce for security
        check_ajax_referer('mpai_nonce', 'nonce');
        
        // Only allow logged-in users with appropriate capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized access');
            return;
        }
        
        try {
            // Get setting value
            $enabled = isset($_POST['enabled']) ? (bool) intval($_POST['enabled']) : true;
            
            // Update the setting
            update_option('mpai_enable_plugin_logging', $enabled);
            
            wp_send_json_success(array(
                'message' => $enabled ? 'Plugin logging enabled' : 'Plugin logging disabled',
            ));
        } catch (Exception $e) {
            wp_send_json_error('Error updating setting: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX handler for testing the Error Recovery System
     */
    public function test_error_recovery_ajax() {
        // Check nonce for security
        check_ajax_referer('mpai_nonce', 'nonce');
        
        // Only allow logged-in users with appropriate capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized access');
            return;
        }
        
        try {
            // Include the test script
            $test_file = MPAI_PLUGIN_DIR . 'test/test-error-recovery.php';
            if (file_exists($test_file)) {
                require_once($test_file);
                
                if (function_exists('mpai_test_error_recovery')) {
                    $results = mpai_test_error_recovery();
                    wp_send_json($results);
                } else {
                    wp_send_json_error(array(
                        'message' => 'Error recovery test function not found',
                        'success' => false
                    ));
                }
            } else {
                wp_send_json_error(array(
                    'message' => 'Error recovery test file not found at: ' . $test_file,
                    'success' => false
                ));
            }
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => 'Error running tests: ' . $e->getMessage(),
                'success' => false
            ));
        }
    }
    
    /**
     * AJAX handler for running edge case tests
     */
    public function run_edge_case_tests_ajax() {
        // Check nonce for security
        check_ajax_referer('mpai_nonce', 'nonce');
        
        // Only allow logged-in users with appropriate capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized access');
            return;
        }
        
        try {
            // Ensure the Edge Case Test Suite file is included
            if (!function_exists('mpai_display_all_edge_case_tests')) {
                require_once MPAI_PLUGIN_DIR . 'test/edge-cases/test-edge-cases.php';
            }
            
            if (function_exists('mpai_display_all_edge_case_tests')) {
                ob_start();
                mpai_display_all_edge_case_tests();
                $output = ob_get_clean();
                
                wp_send_json_success($output);
            } else {
                wp_send_json_error('Edge Case Test Suite functions not found');
            }
        } catch (Exception $e) {
            wp_send_json_error('Error running edge case tests: ' . $e->getMessage());
        }
    }

    /**
     * Clear chat history via AJAX
     */
    public function clear_chat_history_ajax() {
        global $wpdb;
        
        // Check nonce for security
        check_ajax_referer('mpai_chat_nonce', 'nonce');

        // Only allow logged-in users with appropriate capabilities
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Unauthorized access');
        }

        try {
            // 1. Clear the user meta conversation history (legacy storage)
            $user_id = get_current_user_id();
            delete_user_meta($user_id, 'mpai_conversation_history');
            
            // 2. Clear the database table messages
            $table_conversations = $wpdb->prefix . 'mpai_conversations';
            $table_messages = $wpdb->prefix . 'mpai_messages';
            
            // First check if tables exist
            $conversations_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_conversations}'") === $table_conversations;
            $messages_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_messages}'") === $table_messages;
            
            if ($conversations_exists && $messages_exists) {
                // Get the user's conversations
                $conversations = $wpdb->get_col(
                    $wpdb->prepare(
                        "SELECT conversation_id FROM $table_conversations WHERE user_id = %d",
                        $user_id
                    )
                );
                
                // Delete all messages for these conversations
                if (!empty($conversations)) {
                    foreach ($conversations as $conversation_id) {
                        $wpdb->delete(
                            $table_messages,
                            array('conversation_id' => $conversation_id)
                        );
                    }
                    
                    error_log('MPAI: Cleared database messages for user ' . $user_id);
                }
            }
            
            // 3. Force chat class to reset if it exists
            if (class_exists('MPAI_Chat')) {
                $chat = new MPAI_Chat();
                if (method_exists($chat, 'reset_conversation')) {
                    $chat->reset_conversation();
                    error_log('MPAI: Reset conversation in chat class');
                }
            }
            
            error_log('MPAI: Chat history fully cleared from all storage locations');
            
            wp_send_json_success(array(
                'message' => __('Chat history cleared', 'memberpress-ai-assistant'),
            ));
            
        } catch (Exception $e) {
            error_log('MPAI: Error clearing chat history: ' . $e->getMessage());
            wp_send_json_error('Error clearing chat history: ' . $e->getMessage());
        }
    }
    
    /**
     * Get chat history via AJAX
     */
    public function get_chat_history_ajax() {
        // Check nonce for security
        check_ajax_referer('mpai_chat_nonce', 'nonce');

        // Only allow logged-in users with appropriate capabilities
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Unauthorized access');
        }

        // Get the conversation history
        $user_id = get_current_user_id();
        $history = get_user_meta($user_id, 'mpai_conversation_history', true);

        // If history is empty, return an empty array
        if (empty($history)) {
            $history = array();
        }

        wp_send_json_success(array(
            'history' => $history,
        ));
    }

    /**
     * Save user consent via AJAX
     */
    public function save_consent_ajax() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mpai_chat_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
            return;
        }
        
        // Get current user ID
        $user_id = get_current_user_id();
        
        // Check if user has already consented
        $has_consented = get_user_meta($user_id, 'mpai_has_consented', true);
        
        // Only allow setting consent to true, not revoking it
        if (!$has_consented) {
            // Update user meta - only allow setting to true
            update_user_meta($user_id, 'mpai_has_consented', true);
            
            // Return success
            wp_send_json_success(array(
                'message' => 'Consent saved',
                'consent' => true
            ));
        } else {
            // User has already consented, cannot change
            wp_send_json_success(array(
                'message' => 'User has already consented',
                'consent' => true
            ));
        }
    }

    /**
     * Save message to conversation history
     */
    private function save_message_to_history($message, $response) {
        try {
            $user_id = get_current_user_id();
            
            if (empty($user_id)) {
                error_log('MPAI: Cannot save chat history - no user ID available');
                return false;
            }
            
            $history = get_user_meta($user_id, 'mpai_conversation_history', true);
    
            if (empty($history) || !is_array($history)) {
                $history = array();
                error_log('MPAI: Initializing new chat history for user ' . $user_id);
            }
    
            // Add user message
            $history[] = array(
                'role' => 'user',
                'content' => $message,
                'timestamp' => time(),
            );
    
            // Add assistant response
            $history[] = array(
                'role' => 'assistant',
                'content' => $response,
                'timestamp' => time(),
            );
    
            // Limit history size (keep last 50 messages)
            if (count($history) > 50) {
                $history = array_slice($history, -50);
            }
    
            $result = update_user_meta($user_id, 'mpai_conversation_history', $history);
            
            if ($result) {
                error_log('MPAI: Successfully saved chat history for user ' . $user_id . ' (' . count($history) . ' messages)');
                return true;
            } else {
                error_log('MPAI: Failed to save chat history for user ' . $user_id);
                return false;
            }
        } catch (Exception $e) {
            error_log('MPAI: Exception in save_message_to_history: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        // Register REST API endpoints
        register_rest_route('mpai/v1', '/chat', array(
            'methods' => 'POST',
            'callback' => array($this, 'process_chat_request'),
            'permission_callback' => array($this, 'check_api_permissions'),
        ));

        register_rest_route('mpai/v1', '/run-command', array(
            'methods' => 'POST',
            'callback' => array($this, 'process_command_request'),
            'permission_callback' => array($this, 'check_api_permissions'),
        ));
    }

    /**
     * Check API permissions
     */
    public function check_api_permissions() {
        return current_user_can('manage_options');
    }

    /**
     * Process chat request via REST API
     */
    public function process_chat_request($request) {
        $params = $request->get_params();
        
        if (empty($params['message'])) {
            return new WP_Error('missing_message', 'Message is required', array('status' => 400));
        }
        
        // Initialize chat handler
        $chat = new MPAI_Chat();
        
        // Process the chat request
        $response = $chat->process_message($params['message']);
        
        return rest_ensure_response($response);
    }

    /**
     * Process command request
     */
    public function process_command_request($request) {
        $params = $request->get_params();
        
        if (empty($params['command'])) {
            return new WP_Error('missing_command', 'Command is required', array('status' => 400));
        }
        
        // Initialize context manager
        $context_manager = new MPAI_Context_Manager();
        
        // Process the command
        $response = $context_manager->run_command($params['command']);
        
        return rest_ensure_response($response);
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Set default options
        $this->set_default_options();
        
        // Create tables if needed
        $this->create_database_tables();
        
        // Initialize agent system
        $this->initialize_agent_system();
        
        // Clear rewrite rules
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
                // Redirect to the dashboard page
                wp_safe_redirect(admin_url('admin.php?page=memberpress-ai-assistant'));
                exit;
            }
        }
    }
    
    /**
     * Initialize the agent system
     */
    private function initialize_agent_system() {
        try {
            // Load dependencies first
            $this->load_agent_system();
            
            // Create directories for SDK if they don't exist
            $sdk_dir = MPAI_PLUGIN_DIR . 'includes/agents/sdk';
            if (!file_exists($sdk_dir)) {
                wp_mkdir_p($sdk_dir);
            }
            
            // Set default OpenAI options for the agent system
            $model = get_option('mpai_model', 'gpt-4o');
            update_option('mpai_agent_system_model', $model);
            update_option('mpai_agent_system_version', MPAI_VERSION);
            update_option('mpai_agent_system_initialized', true);
            
            // Get an instance of the agent orchestrator (singleton)
            // This will trigger agent registration and SDK initialization
            $orchestrator = MPAI_Agent_Orchestrator::get_instance();
            
            // Log success
            // Agent system initialized
            return true;
        } catch (Exception $e) {
            error_log('MPAI: Error initializing agent system: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create database tables if they don't exist
     * 
     * @return bool Success status
     */
    public function create_database_tables() {
        try {
            global $wpdb;
            
            $charset_collate = $wpdb->get_charset_collate();
            
            // Table for storing chat conversations
            $table_conversations = $wpdb->prefix . 'mpai_conversations';
            
            $sql = "CREATE TABLE $table_conversations (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                user_id bigint(20) NOT NULL,
                conversation_id varchar(36) NOT NULL,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                KEY user_id (user_id),
                KEY conversation_id (conversation_id)
            ) $charset_collate;";
            
            // Table for storing chat messages
            $table_messages = $wpdb->prefix . 'mpai_messages';
            
            $sql .= "CREATE TABLE $table_messages (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                conversation_id varchar(36) NOT NULL,
                message text NOT NULL,
                response text NOT NULL,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                KEY conversation_id (conversation_id)
            ) $charset_collate;";
            
            // Load dbDelta function
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            
            // Enable error output for dbDelta
            $wpdb->show_errors();
            
            // Execute the SQL
            $result = dbDelta($sql);
            
            // Log the result
            error_log('MPAI: Database tables creation result: ' . json_encode($result));
            
            // Check if tables were created
            $tables_created = array();
            $tables_created['conversations'] = $wpdb->get_var("SHOW TABLES LIKE '{$table_conversations}'") === $table_conversations;
            $tables_created['messages'] = $wpdb->get_var("SHOW TABLES LIKE '{$table_messages}'") === $table_messages;
            
            error_log('MPAI: Tables created status: ' . json_encode($tables_created));
            
            return $tables_created['conversations'] && $tables_created['messages'];
        } catch (Exception $e) {
            error_log('MPAI: Error creating database tables: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear rewrite rules
        flush_rewrite_rules();
        
        // Reset all user consents upon deactivation
        global $wpdb;
        
        // Delete consent meta for all users
        $wpdb->delete(
            $wpdb->usermeta,
            array('meta_key' => 'mpai_has_consented')
        );
        
        error_log('MPAI: All user consents have been reset upon plugin deactivation');
    }

    /**
     * Initialize plugin components
     */
    public function init_plugin_components() {
        // Initialize plugin logger
        mpai_init_plugin_logger();
        
        // Initialize error recovery system
        if (function_exists('mpai_init_error_recovery')) {
            mpai_init_error_recovery();
        }
        
        // Initialize state validator
        if (function_exists('mpai_init_state_validator')) {
            mpai_init_state_validator();
        }
        
        // Only initialize agent system once per page load
        // Use singleton pattern to avoid duplicate initializations
        if (class_exists('MPAI_Agent_Orchestrator')) {
            // Get the singleton instance instead of creating a new one
            $orchestrator = MPAI_Agent_Orchestrator::get_instance();
            if (method_exists($orchestrator, 'get_available_agents')) {
                $orchestrator->get_available_agents();
            }
        }
        
        // Load Edge Case Test Suite in admin
        if (is_admin() && file_exists(MPAI_PLUGIN_DIR . 'test/edge-cases/test-edge-cases.php')) {
            require_once MPAI_PLUGIN_DIR . 'test/edge-cases/test-edge-cases.php';
            
            // Register AJAX handler for running edge case tests
            add_action('wp_ajax_mpai_run_edge_case_tests', array($this, 'run_edge_case_tests_ajax'));
        }
    }
    
    private function set_default_options() {
        $default_options = array(
            // OpenAI Settings
            'api_key' => '',
            'model' => 'gpt-4o',
            'temperature' => 0.7,
            'max_tokens' => 2048,
            
            // Anthropic Settings
            'anthropic_api_key' => '',
            'anthropic_model' => 'claude-3-opus-20240229',
            'anthropic_temperature' => 0.7,
            'anthropic_max_tokens' => 2048,
            
            // API Router Settings
            'primary_api' => 'openai',
            
            // Chat Interface Settings
            'enable_chat' => true,
            'chat_position' => 'bottom-right',
            'show_on_all_pages' => true,
            'welcome_message' => 'Hi there! I\'m your MemberPress AI Assistant. How can I help you today?',
            
            // MCP and CLI settings
            'enable_mcp' => true,
            'enable_cli_commands' => true,
            'enable_wp_cli_tool' => true,
            'enable_memberpress_info_tool' => true,
            'enable_plugin_logs_tool' => true,
            'allowed_cli_commands' => array('wp user list', 'wp post list', 'wp plugin list'),
            
            // Agent system settings
            'agent_system_enabled' => true,
            'agent_system_version' => MPAI_VERSION,
            'agent_system_model' => 'gpt-4o',
            
            // Plugin logger settings
            'enable_plugin_logging' => true,
            'plugin_logs_retention_days' => 90,
        );
        
        foreach ($default_options as $option => $value) {
            if (get_option('mpai_' . $option) === false) {
                update_option('mpai_' . $option, $value);
            }
        }
    }
}

// Initialize the plugin
add_action('plugins_loaded', array('MemberPress_AI_Assistant', 'get_instance'));