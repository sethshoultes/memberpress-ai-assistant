<?php
/**
 * MemberPress AI Assistant Admin Menu Handler
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Admin;

use MemberpressAiAssistant\Abstracts\AbstractService;

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
     * Constructor
     *
     * @param string $name Service name
     * @param mixed $logger Logger instance
     */
    public function __construct(string $name = 'admin_menu', $logger = null) {
        parent::__construct($name, $logger);
    }

    /**
     * {@inheritdoc}
     */
    public function register($container): void {
        // Register this service with the container
        $container->singleton('admin_menu', function() {
            return $this;
        });

        // Log registration
        $this->log('Admin menu service registered');
    }

    /**
     * {@inheritdoc}
     */
    public function boot(): void {
        parent::boot();
        
        // Add hooks
        $this->addHooks();
        
        // Log boot
        $this->log('Admin menu service booted');
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
        add_submenu_page(
            $this->parent_menu_slug,
            __('AI Assistant', 'memberpress-ai-assistant'),
            __('AI Assistant', 'memberpress-ai-assistant'),
            'manage_options',
            $this->menu_slug,
            [$this, 'render_settings_page']
        );
        
        $this->log('Registered as MemberPress submenu');
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
        
        $this->log('Registered as top-level menu');
    }

    /**
     * Render the settings page
     *
     * @return void
     */
    public function render_settings_page(): void {
        // This will be implemented in a separate class
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('MemberPress AI Assistant Settings', 'memberpress-ai-assistant') . '</h1>';
        echo '<p>' . esc_html__('Configure your MemberPress AI Assistant settings here.', 'memberpress-ai-assistant') . '</p>';
        echo '</div>';
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