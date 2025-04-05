<?php
/**
 * Diagnostics System
 * 
 * Provides a unified framework for registering, managing, and running diagnostic tests
 * 
 * @package MemberPress AI Assistant
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

class MPAI_Diagnostics {
    // Registry of all diagnostic tests
    private static $tests = [];
    
    // Registry of all diagnostic categories
    private static $categories = [];
    
    /**
     * Register a diagnostic test
     * 
     * @param array $test_data Test configuration
     * @return bool Success
     */
    public static function register_test($test_data) {
        // Validate required fields
        if (!isset($test_data['id']) || !isset($test_data['name']) || !isset($test_data['category'])) {
            error_log('MPAI ERROR: Invalid test registration data');
            return false;
        }
        
        // Add to registry
        self::$tests[$test_data['id']] = $test_data;
        return true;
    }
    
    /**
     * Register a diagnostic category
     * 
     * @param array $category_data Category configuration
     * @return bool Success
     */
    public static function register_category($category_data) {
        // Validate required fields
        if (!isset($category_data['id']) || !isset($category_data['name'])) {
            error_log('MPAI ERROR: Invalid category registration data');
            return false;
        }
        
        // Add to registry
        self::$categories[$category_data['id']] = $category_data;
        return true;
    }
    
    /**
     * Get all registered tests
     * 
     * @return array Tests
     */
    public static function get_tests() {
        return self::$tests;
    }
    
    /**
     * Get all registered categories
     * 
     * @return array Categories
     */
    public static function get_categories() {
        // If no categories are registered, use a default set
        if (empty(self::$categories)) {
            return [
                'core' => [
                    'id' => 'core',
                    'name' => __('Core System', 'memberpress-ai-assistant'),
                    'description' => __('Tests for core system functionality', 'memberpress-ai-assistant')
                ],
                'api' => [
                    'id' => 'api',
                    'name' => __('API Connections', 'memberpress-ai-assistant'),
                    'description' => __('Tests for API connectivity and functionality', 'memberpress-ai-assistant')
                ],
                'tools' => [
                    'id' => 'tools',
                    'name' => __('AI Tools', 'memberpress-ai-assistant'),
                    'description' => __('Tests for AI tool functionality', 'memberpress-ai-assistant')
                ],
                'integration' => [
                    'id' => 'integration',
                    'name' => __('Integration Tests', 'memberpress-ai-assistant'),
                    'description' => __('Tests for integration with WordPress and external systems', 'memberpress-ai-assistant')
                ]
            ];
        }
        
        return self::$categories;
    }
    
    /**
     * Get tests by category
     * 
     * @param string $category Category ID
     * @return array Tests in category
     */
    public static function get_tests_by_category($category) {
        return array_filter(self::$tests, function($test) use ($category) {
            return $test['category'] === $category;
        });
    }
    
    /**
     * Get a specific test
     * 
     * @param string $test_id Test ID
     * @return array|null Test data or null if not found
     */
    public static function get_test($test_id) {
        return isset(self::$tests[$test_id]) ? self::$tests[$test_id] : null;
    }
    
    /**
     * Register core diagnostic tests
     */
    public static function register_core_tests() {
        // Register System Information test
        self::register_test([
            'id' => 'system-info',
            'category' => 'core',
            'name' => __('System Information', 'memberpress-ai-assistant'),
            'description' => __('Get detailed information about your WordPress and PHP environment', 'memberpress-ai-assistant'),
            'test_callback' => 'mpai_run_system_info_test',
            'doc_url' => 'system-information.md'
        ]);
        
        // Register OpenAI API Connection test
        self::register_test([
            'id' => 'openai-connection',
            'category' => 'api',
            'name' => __('OpenAI API Connection', 'memberpress-ai-assistant'),
            'description' => __('Test connection to the OpenAI API', 'memberpress-ai-assistant'),
            'test_callback' => 'mpai_test_openai_connection',
            'doc_url' => 'api-connections.md#openai'
        ]);
        
        // Register Anthropic API Connection test
        self::register_test([
            'id' => 'anthropic-connection',
            'category' => 'api',
            'name' => __('Anthropic API Connection', 'memberpress-ai-assistant'),
            'description' => __('Test connection to the Anthropic API', 'memberpress-ai-assistant'),
            'test_callback' => 'mpai_test_anthropic_connection',
            'doc_url' => 'api-connections.md#anthropic'
        ]);
        
        // Register Error Recovery System test
        self::register_test([
            'id' => 'error-recovery',
            'category' => 'core',
            'name' => __('Error Recovery System', 'memberpress-ai-assistant'),
            'description' => __('Test the Error Recovery System functionality', 'memberpress-ai-assistant'),
            'test_callback' => 'mpai_test_error_recovery',
            'direct_url' => 'test/test-error-recovery-page.php',
            'doc_url' => 'error-recovery-system.md'
        ]);
    }
    
    /**
     * Run a diagnostic test
     * 
     * @param string $test_id Test ID
     * @param array $params Test parameters
     * @return array Test results
     */
    public static function run_test($test_id, $params = []) {
        $test = self::get_test($test_id);
        
        if (!$test || !isset($test['test_callback']) || !is_callable($test['test_callback'])) {
            return [
                'success' => false,
                'message' => __('Invalid test ID or callback', 'memberpress-ai-assistant'),
                'test_id' => $test_id
            ];
        }
        
        try {
            // Start timing
            $start_time = microtime(true);
            
            // Call the test callback
            $result = call_user_func($test['test_callback'], $params);
            
            // End timing
            $end_time = microtime(true);
            $total_time = $end_time - $start_time;
            
            // Add timing if not already included
            if (!isset($result['timing'])) {
                $result['timing'] = [
                    'start' => $start_time,
                    'end' => $end_time,
                    'total' => $total_time,
                ];
            }
            
            // Add test metadata
            $result['test_id'] = $test_id;
            $result['test_name'] = $test['name'];
            
            return $result;
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error running test: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'test_id' => $test_id,
                'test_name' => $test['name'],
            ];
        }
    }
    
    /**
     * Run all tests in a category
     * 
     * @param string $category_id Category ID
     * @param array $params Test parameters
     * @return array Test results
     */
    public static function run_category_tests($category_id, $params = []) {
        $tests = self::get_tests_by_category($category_id);
        $results = [];
        
        foreach ($tests as $test_id => $test) {
            $results[$test_id] = self::run_test($test_id, $params);
        }
        
        return [
            'success' => true,
            'category_id' => $category_id,
            'category_name' => self::get_categories()[$category_id]['name'],
            'results' => $results
        ];
    }
    
    /**
     * Run all registered tests
     * 
     * @param array $params Test parameters
     * @return array Test results
     */
    public static function run_all_tests($params = []) {
        $results = [];
        
        foreach (self::$tests as $test_id => $test) {
            $results[$test_id] = self::run_test($test_id, $params);
        }
        
        return [
            'success' => true,
            'results' => $results
        ];
    }
    
    /**
     * Initialize diagnostic system and register tests
     */
    public static function init() {
        // Register core tests
        self::register_core_tests();
        
        // Allow plugins and themes to register tests
        do_action('mpai_register_diagnostic_tests');
        
        // Register AJAX handlers for running tests
        add_action('wp_ajax_mpai_run_diagnostic_test', [self::class, 'ajax_run_test']);
        add_action('wp_ajax_mpai_run_category_tests', [self::class, 'ajax_run_category_tests']);
        add_action('wp_ajax_mpai_run_all_tests', [self::class, 'ajax_run_all_tests']);
    }
    
    /**
     * AJAX handler for running a single diagnostic test
     */
    public static function ajax_run_test() {
        // Check nonce for security
        check_ajax_referer('mpai_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized access', 'memberpress-ai-assistant')]);
            return;
        }
        
        $test_id = isset($_POST['test_id']) ? sanitize_key($_POST['test_id']) : '';
        if (empty($test_id)) {
            wp_send_json_error(['message' => __('Missing test ID', 'memberpress-ai-assistant')]);
            return;
        }
        
        $result = self::run_test($test_id, $_POST);
        wp_send_json_success($result);
    }
    
    /**
     * AJAX handler for running all tests in a category
     */
    public static function ajax_run_category_tests() {
        // Check nonce for security
        check_ajax_referer('mpai_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized access', 'memberpress-ai-assistant')]);
            return;
        }
        
        $category_id = isset($_POST['category_id']) ? sanitize_key($_POST['category_id']) : '';
        if (empty($category_id)) {
            wp_send_json_error(['message' => __('Missing category ID', 'memberpress-ai-assistant')]);
            return;
        }
        
        $result = self::run_category_tests($category_id, $_POST);
        wp_send_json_success($result);
    }
    
    /**
     * AJAX handler for running all diagnostic tests
     */
    public static function ajax_run_all_tests() {
        // Check nonce for security
        check_ajax_referer('mpai_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized access', 'memberpress-ai-assistant')]);
            return;
        }
        
        $result = self::run_all_tests($_POST);
        wp_send_json_success($result);
    }
    
    /**
     * Render the diagnostic interface
     * 
     * @param bool $force Force rendering even if duplicate prevention is in place
     */
    public static function render_interface($force = false) {
        // Check for duplicate rendering if not forced
        if (!$force) {
            // Check if we're rendering based on a tab URL parameter
            $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : '';
            if ($tab === 'diagnostic') {
                error_log('MPAI: Diagnostics interface render skipped - explicit tab=diagnostic parameter');
                return;
            }
            
            // Create a static flag to make sure we only render once per page load
            static $interface_rendered = false;
            if ($interface_rendered) {
                error_log('MPAI: Diagnostics interface already rendered once, skipping duplicate render');
                return;
            }
            
            // Mark as rendered
            $interface_rendered = true;
        }
        
        // Include template for diagnostic interface
        include MPAI_PLUGIN_DIR . 'includes/templates/diagnostic-interface.php';
    }
}

// Initialize diagnostics system
MPAI_Diagnostics::init();