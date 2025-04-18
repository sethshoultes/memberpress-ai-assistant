<?php
/**
 * Admin Menu Class
 * 
 * Centralizes all admin menu registration to solve menu highlighting issues
 *
 * @package MemberPress AI Assistant
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class MPAI_Admin_Menu
 * 
 * Responsible for all admin menu registration and management
 */
class MPAI_Admin_Menu {
    /**
     * Store menu pages for registration
     * 
     * @var array
     */
    private $pages = [];

    /**
     * Parent menu slug
     * 
     * @var string
     */
    private $parent_slug = '';

    /**
     * Main menu slug
     * 
     * @var string
     */
    private $main_slug = 'memberpress-ai-assistant';

    /**
     * Whether MemberPress is active
     * 
     * @var bool
     */
    private $has_memberpress = false;

    /**
     * Constructor
     */
    public function __construct() {
        // Determine correct parent slug once at initialization
        $this->has_memberpress = $this->detect_memberpress();
        $this->parent_slug = $this->determine_parent_slug();
        
        // Register hooks with appropriate priorities
        add_action('admin_menu', [$this, 'register_all_menu_items'], 20);
        
        // Add menu highlighting filters
        add_filter('parent_file', [$this, 'filter_parent_file']);
        add_filter('submenu_file', [$this, 'filter_submenu_file']);
    }

    /**
     * Properly detect if MemberPress is active
     * 
     * @return bool Whether MemberPress is active
     */
    private function detect_memberpress() {
        // Use the centralized MemberPress detection system
        return mpai_is_memberpress_active();
    }

    /**
     * Determine the parent slug for menu registration
     * 
     * @return string Parent menu slug
     */
    private function determine_parent_slug() {
        // If MemberPress is active, use its menu as parent
        if ($this->has_memberpress) {
            return 'memberpress';
        }
        
        // Otherwise, use our own top-level menu
        return $this->main_slug;
    }

    /**
     * Register a page to be added to the admin menu
     * 
     * @param string   $title      Page title
     * @param string   $menu_title Menu title
     * @param string   $capability Required capability
     * @param string   $slug       Menu slug
     * @param callable $callback   Page render callback
     * @param int      $position   Menu position
     * 
     * @return MPAI_Admin_Menu Self reference for chaining
     */
    public function register_page($title, $menu_title, $capability, $slug, $callback, $position = null) {
        // Store page registration for later processing
        $this->pages[] = [
            'title' => $title,
            'menu_title' => $menu_title,
            'capability' => $capability,
            'slug' => $slug,
            'callback' => $callback,
            'position' => $position
        ];
        
        return $this;
    }

    /**
     * Register all menu items when WordPress admin menu is being built
     */
    public function register_all_menu_items() {
        // Register main page first
        $this->register_main_page();
        
        // Then register all submenu pages using correct parent
        foreach ($this->pages as $page) {
            $this->register_submenu_page($page);
        }
    }

    /**
     * Register the main plugin page
     */
    private function register_main_page() {
        if ($this->has_memberpress) {
            // If MemberPress is active, add as a submenu to MemberPress
            add_submenu_page(
                'memberpress',
                __('AI Assistant', 'memberpress-ai-assistant'),
                __('AI Assistant', 'memberpress-ai-assistant'),
                'manage_options',
                $this->main_slug,
                [$this, 'render_main_page']
            );
        } else {
            // If MemberPress is not active, add as a top-level menu
            add_menu_page(
                __('MemberPress AI', 'memberpress-ai-assistant'),
                __('MemberPress AI', 'memberpress-ai-assistant'),
                'manage_options',
                $this->main_slug,
                [$this, 'render_main_page'],
                MPAI_PLUGIN_URL . 'assets/images/memberpress-logo.svg',
                30
            );
        }
        
        // Settings page
        add_submenu_page(
            $this->parent_slug,
            __('Settings', 'memberpress-ai-assistant'),
            __('Settings', 'memberpress-ai-assistant'),
            'manage_options',
            'memberpress-ai-assistant-settings',
            function() {
                require_once MPAI_PLUGIN_DIR . 'includes/settings-page.php';
            }
        );
        
        // Diagnostics page
        add_submenu_page(
            $this->parent_slug,
            __('Diagnostics', 'memberpress-ai-assistant'),
            __('Diagnostics', 'memberpress-ai-assistant'),
            'manage_options',
            'memberpress-ai-assistant-diagnostics',
            function() {
                // This page is handled by the diagnostics plugin
                do_action('mpai_render_diagnostics');
            }
        );
    }

    /**
     * Render the main plugin page
     */
    public function render_main_page() {
        // Include the dashboard page file
        require_once MPAI_PLUGIN_DIR . 'includes/dashboard-page.php';
    }

    /**
     * Register a submenu page
     * 
     * @param array $page Page data
     */
    private function register_submenu_page($page) {
        // Don't re-register settings page
        if ($page['slug'] === 'memberpress-ai-assistant-settings') {
            return;
        }
        
        // Always use correct parent slug determined at construction
        add_submenu_page(
            $this->parent_slug,
            $page['title'],
            $page['menu_title'],
            $page['capability'],
            $page['slug'],
            $page['callback'],
            $page['position']
        );
    }

    /**
     * Filter the parent_file to highlight the correct top-level menu
     * 
     * @param string $parent_file The current parent file
     * @return string The modified parent file
     */
    public function filter_parent_file($parent_file) {
        global $plugin_page;
        
        // Check if we're on one of our pages
        if (strpos($plugin_page, 'memberpress-ai-assistant') === 0) {
            return $this->parent_slug;
        }
        
        return $parent_file;
    }

    /**
     * Filter the submenu_file to highlight the correct submenu
     * 
     * @param string $submenu_file The current submenu file
     * @return string The modified submenu file
     */
    public function filter_submenu_file($submenu_file) {
        global $plugin_page;
        
        // Check if we're on one of our pages and ensure it's highlighted
        if (strpos($plugin_page, 'memberpress-ai-assistant') === 0) {
            return $plugin_page;
        }
        
        return $submenu_file;
    }
}