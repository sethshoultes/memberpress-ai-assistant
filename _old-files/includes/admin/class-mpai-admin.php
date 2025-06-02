<?php
/**
 * Admin Class
 *
 * Handles all admin-related functionality including menu registration
 * and rendering of admin pages
 *
 * @package MemberPress AI Assistant
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

class MPAI_Admin {
    /**
     * Constructor
     */
    public function __construct() {
        // Add admin menu
        add_action('admin_menu', array($this, 'register_admin_menu'));
        
        // Admin assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // Initialize settings
        require_once MPAI_PLUGIN_DIR . 'includes/admin/class-mpai-settings.php';
        new MPAI_Settings();
        
        // Add AJAX handlers
        add_action('wp_ajax_mpai_test_api_connection', array($this, 'test_api_connection_ajax'));
    }

    /**
     * Register admin menu items
     */
    public function register_admin_menu() {
        // Check if MemberPress is active
        $has_memberpress = mpai_is_memberpress_active();
        
        // Main page slug
        $main_page_slug = 'memberpress-ai-assistant';
        
        // Define menu items
        $menu_items = [];
        
        if ($has_memberpress) {
            // If MemberPress is active, add as a submenu to MemberPress
            $menu_items[] = [
                'type' => 'submenu',
                'parent' => 'memberpress',
                'page_title' => __('AI Assistant', 'memberpress-ai-assistant'),
                'menu_title' => __('AI Assistant', 'memberpress-ai-assistant'),
                'capability' => 'manage_options',
                'menu_slug' => $main_page_slug,
                'callback' => array($this, 'render_admin_page')
            ];
        } else {
            // If MemberPress is not active, add as a top-level menu
            $menu_items[] = [
                'type' => 'menu',
                'page_title' => __('MemberPress AI', 'memberpress-ai-assistant'),
                'menu_title' => __('MemberPress AI', 'memberpress-ai-assistant'),
                'capability' => 'manage_options',
                'menu_slug' => $main_page_slug,
                'callback' => array($this, 'render_admin_page'),
                'icon' => MPAI_PLUGIN_URL . 'assets/images/memberpress-logo.svg',
                'position' => 30
            ];
        }
        
        // Filter menu items
        $menu_items = apply_filters('MPAI_HOOK_FILTER_admin_menu_items', $menu_items, $has_memberpress);
        
        // Register menu items
        foreach ($menu_items as $item) {
            // Filter capability
            $capability = apply_filters('MPAI_HOOK_FILTER_admin_capabilities', $item['capability'], $item['menu_slug']);
            
            if ($item['type'] === 'submenu') {
                add_submenu_page(
                    $item['parent'],
                    $item['page_title'],
                    $item['menu_title'],
                    $capability,
                    $item['menu_slug'],
                    $item['callback']
                );
            } else {
                add_menu_page(
                    $item['page_title'],
                    $item['menu_title'],
                    $capability,
                    $item['menu_slug'],
                    $item['callback'],
                    $item['icon'],
                    $item['position']
                );
            }
        }
    }

    /**
     * Render admin page
     */
    public function render_admin_page() {
        // Check if user has consented to terms
        $consent_manager = MPAI_Consent_Manager::get_instance();
        
        // If consent is not given, show the consent form
        if (!$consent_manager->has_user_consented()) {
            $consent_manager->render_consent_form();
            return;
        }
        
        // Fire action before displaying settings page
        do_action('MPAI_HOOK_ACTION_before_display_settings');
        
        // User has consented, show the admin page
        require_once MPAI_PLUGIN_DIR . 'includes/admin/views/admin-page.php';
        
        // Fire action after displaying settings page
        do_action('MPAI_HOOK_ACTION_after_display_settings');
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our admin pages
        if (strpos($hook, 'memberpress-ai-assistant') === false) {
            return;
        }
        
        // Enqueue CSS
        wp_enqueue_style(
            'mpai-admin-css',
            MPAI_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            MPAI_VERSION
        );
        
        // Dashicons
        wp_enqueue_style('dashicons');
        
        // Enqueue JS
        wp_enqueue_script(
            'mpai-admin-js',
            MPAI_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            MPAI_VERSION,
            true
        );
        
        // Pass data to JS
        wp_localize_script(
            'mpai-admin-js',
            'mpai_data',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mpai_nonce'),
                'plugin_url' => MPAI_PLUGIN_URL,
                'testing_openai' => __('Testing OpenAI API connection...', 'memberpress-ai-assistant'),
                'testing_anthropic' => __('Testing Anthropic API connection...', 'memberpress-ai-assistant'),
                'connection_success' => __('Connection successful!', 'memberpress-ai-assistant'),
                'connection_error' => __('Connection failed: ', 'memberpress-ai-assistant')
            )
        );
    }
    
    /**
     * Test API connection via AJAX
     */
    public function test_api_connection_ajax() {
        // Logging for debugging
        mpai_log_debug('test_api_connection_ajax called', 'admin');
        
        // Check nonce
        check_ajax_referer('mpai_nonce', 'nonce');
        
        // Check if user has permission
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
            return;
        }
        
        // Get API provider from request
        $provider = isset($_POST['provider']) ? sanitize_text_field($_POST['provider']) : '';
        mpai_log_debug('Provider: ' . $provider, 'admin');
        
        if (!in_array($provider, array('openai', 'anthropic'))) {
            wp_send_json_error('Invalid provider: ' . $provider);
            return;
        }
        
        // Make sure the API class files are loaded
        if (!class_exists('MPAI_OpenAI') || !class_exists('MPAI_Anthropic')) {
            mpai_log_debug('API classes not loaded, attempting to load them', 'admin');
            
            // Load API Integration Classes if not already loaded
            if (!class_exists('MPAI_OpenAI')) {
                $openai_file = MPAI_PLUGIN_DIR . 'includes/class-mpai-openai.php';
                if (file_exists($openai_file)) {
                    require_once $openai_file;
                    mpai_log_debug('Loaded OpenAI class file', 'admin');
                } else {
                    mpai_log_error('OpenAI class file not found: ' . $openai_file, 'admin');
                    wp_send_json_error('OpenAI API handler file not found');
                    return;
                }
            }
            
            if (!class_exists('MPAI_Anthropic')) {
                $anthropic_file = MPAI_PLUGIN_DIR . 'includes/class-mpai-anthropic.php';
                if (file_exists($anthropic_file)) {
                    require_once $anthropic_file;
                    mpai_log_debug('Loaded Anthropic class file', 'admin');
                } else {
                    mpai_log_error('Anthropic class file not found: ' . $anthropic_file, 'admin');
                    wp_send_json_error('Anthropic API handler file not found');
                    return;
                }
            }
        }
        
        // Test the API connection
        if ($provider === 'openai') {
            // OpenAI connection test
            $api_key = get_option('mpai_api_key', '');
            $model = get_option('mpai_model', 'gpt-4o');
            
            mpai_log_debug('OpenAI API key exists: ' . (!empty($api_key) ? 'Yes (length: ' . strlen($api_key) . ')' : 'No'), 'admin');
            mpai_log_debug('OpenAI model: ' . $model, 'admin');
            
            if (empty($api_key)) {
                wp_send_json_error(__('API key is not set', 'memberpress-ai-assistant'));
                return;
            }
            
            // Create an instance of the OpenAI API handler
            if (class_exists('MPAI_OpenAI')) {
                try {
                    mpai_log_debug('Creating OpenAI instance', 'admin');
                    $openai = new MPAI_OpenAI($api_key);
                    
                    // Test the connection with a simple request
                    mpai_log_debug('Testing OpenAI connection', 'admin');
                    $result = $openai->test_connection($model);
                    mpai_log_debug('OpenAI test result: ' . json_encode($result), 'admin');
                    
                    // Add debug logging for the response content
                    if (isset($result['response'])) {
                        error_log('MPAI: OpenAI response content: ' . $result['response']);
                    }
                    
                    if (isset($result['success']) && $result['success']) {
                        // Send the actual API response content directly
                        $response_content = isset($result['response']) ? $result['response'] : 'Connection successful';
                        error_log('MPAI: Sending response content: ' . $response_content);
                        echo json_encode(array(
                            'success' => true,
                            'data' => $response_content
                        ));
                        wp_die();
                    } else {
                        wp_send_json_error(isset($result['error']) ? $result['error'] : __('Unknown error', 'memberpress-ai-assistant'));
                    }
                } catch (Exception $e) {
                    mpai_log_error('Exception in OpenAI test: ' . $e->getMessage(), 'admin');
                    wp_send_json_error('Error: ' . $e->getMessage());
                }
            } else {
                mpai_log_error('OpenAI class still not available after loading attempt', 'admin');
                wp_send_json_error(__('OpenAI API handler not available', 'memberpress-ai-assistant'));
            }
        } else {
            // Anthropic connection test
            $api_key = get_option('mpai_anthropic_api_key', '');
            $model = get_option('mpai_anthropic_model', 'claude-3-opus-20240229');
            
            mpai_log_debug('Anthropic API key exists: ' . (!empty($api_key) ? 'Yes (length: ' . strlen($api_key) . ')' : 'No'), 'admin');
            mpai_log_debug('Anthropic model: ' . $model, 'admin');
            
            if (empty($api_key)) {
                wp_send_json_error(__('API key is not set', 'memberpress-ai-assistant'));
                return;
            }
            
            // Create an instance of the Anthropic API handler
            if (class_exists('MPAI_Anthropic')) {
                try {
                    mpai_log_debug('Creating Anthropic instance', 'admin');
                    $anthropic = new MPAI_Anthropic($api_key);
                    
                    // Test the connection with a simple request
                    mpai_log_debug('Testing Anthropic connection', 'admin');
                    $result = $anthropic->test_connection($model);
                    mpai_log_debug('Anthropic test result: ' . json_encode($result), 'admin');
                    
                    if (isset($result['success']) && $result['success']) {
                        // Send the actual API response content as the data payload
                        $response_content = isset($result['response']) ? $result['response'] : 'Connection successful';
                        wp_send_json_success($response_content);
                    } else {
                        wp_send_json_error(isset($result['error']) ? $result['error'] : __('Unknown error', 'memberpress-ai-assistant'));
                    }
                } catch (Exception $e) {
                    mpai_log_error('Exception in Anthropic test: ' . $e->getMessage(), 'admin');
                    wp_send_json_error('Error: ' . $e->getMessage());
                }
            } else {
                mpai_log_error('Anthropic class still not available after loading attempt', 'admin');
                wp_send_json_error(__('Anthropic API handler not available', 'memberpress-ai-assistant'));
            }
        }
    }
}