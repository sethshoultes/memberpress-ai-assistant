<?php
/**
 * Diagnostics Page Class
 * 
 * Handles all diagnostic functionality in a dedicated page
 *
 * @package MemberPress AI Assistant
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class MPAI_Diagnostics_Page
 * 
 * Responsible for diagnostics page rendering and functionality
 */
class MPAI_Diagnostics_Page {
    /**
     * Constructor
     */
    public function __construct() {
        // Register as a separate submenu page - using admin menu instance
        add_action('init', [$this, 'register_page']);
        
        // Register AJAX handlers
        add_action('wp_ajax_mpai_run_diagnostics', [$this, 'handle_ajax_run_diagnostics']);
        add_action('wp_ajax_mpai_test_error_recovery', [$this, 'handle_test_error_recovery']);
        
        // Enqueue required assets
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Register the diagnostics page
     */
    public function register_page() {
        // Access the global admin menu instance, if it's loaded
        global $mpai_admin_menu;
        
        if ($mpai_admin_menu && method_exists($mpai_admin_menu, 'register_page')) {
            $mpai_admin_menu->register_page(
                __('Diagnostics', 'memberpress-ai-assistant'),
                __('Diagnostics', 'memberpress-ai-assistant'),
                'manage_options',
                'memberpress-ai-assistant-diagnostics',
                [$this, 'render_page']
            );
        }
    }

    /**
     * Enqueue page assets
     * 
     * @param string $hook Current admin page hook
     */
    public function enqueue_assets($hook) {
        // Only load on our page
        if (strpos($hook, 'memberpress-ai-assistant-diagnostics') === false) {
            return;
        }
        
        // Enqueue scripts and styles
        wp_enqueue_style(
            'mpai-diagnostics-css',
            MPAI_PLUGIN_URL . 'assets/css/diagnostics.css',
            [],
            MPAI_VERSION
        );
        
        wp_enqueue_script(
            'mpai-diagnostics-js',
            MPAI_PLUGIN_URL . 'assets/js/diagnostics.js',
            ['jquery'],
            MPAI_VERSION,
            true
        );
        
        // Pass data to script
        wp_localize_script(
            'mpai-diagnostics-js',
            'mpai_diagnostics',
            [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mpai_diagnostics_nonce'),
                'tests' => $this->get_available_tests()
            ]
        );
    }

    /**
     * Render the diagnostics page
     */
    public function render_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('MemberPress AI Assistant Diagnostics', 'memberpress-ai-assistant'); ?></h1>
            
            <div class="mpai-diagnostics-container">
                <div class="mpai-diagnostics-intro">
                    <p><?php _e('Run diagnostic tests to check the health of your MemberPress AI Assistant installation.', 'memberpress-ai-assistant'); ?></p>
                </div>
                
                <div class="mpai-diagnostics-categories">
                    <h2><?php _e('System Information', 'memberpress-ai-assistant'); ?></h2>
                    <div class="mpai-system-info-container">
                        <?php $this->render_system_info(); ?>
                    </div>
                    
                    <h2><?php _e('Diagnostic Tests', 'memberpress-ai-assistant'); ?></h2>
                    <div class="mpai-test-categories">
                        <?php $this->render_test_categories(); ?>
                    </div>
                </div>
                
                <div class="mpai-test-results">
                    <h2><?php _e('Test Results', 'memberpress-ai-assistant'); ?></h2>
                    <div id="mpai-test-results-container">
                        <p class="mpai-empty-results"><?php _e('Run a test to see results.', 'memberpress-ai-assistant'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render system information
     */
    private function render_system_info() {
        $info = $this->get_system_info();
        
        ?>
        <table class="widefat mpai-system-info-table">
            <thead>
                <tr>
                    <th><?php _e('Setting', 'memberpress-ai-assistant'); ?></th>
                    <th><?php _e('Value', 'memberpress-ai-assistant'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($info as $label => $value): ?>
                <tr>
                    <td><?php echo esc_html($label); ?></td>
                    <td>
                        <?php 
                        if (is_array($value) && isset($value['value'], $value['status'])) {
                            echo '<span class="mpai-status-' . esc_attr($value['status']) . '">';
                            echo esc_html($value['value']);
                            echo '</span>';
                            
                            if (isset($value['message'])) {
                                echo ' <span class="mpai-status-message">' . esc_html($value['message']) . '</span>';
                            }
                        } else {
                            echo esc_html($value);
                        }
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Get system information
     * 
     * @return array System information
     */
    private function get_system_info() {
        global $wp_version;
        
        // Get WordPress info
        $info = [
            __('WordPress Version', 'memberpress-ai-assistant') => $wp_version,
            __('PHP Version', 'memberpress-ai-assistant') => phpversion(),
            __('Plugin Version', 'memberpress-ai-assistant') => MPAI_VERSION,
        ];
        
        // Add MemberPress detection status
        $has_memberpress = class_exists('MeprAppCtrl');
        $info[__('MemberPress', 'memberpress-ai-assistant')] = [
            'value' => $has_memberpress ? __('Detected', 'memberpress-ai-assistant') : __('Not Detected', 'memberpress-ai-assistant'),
            'status' => $has_memberpress ? 'good' : 'warning',
            'message' => !$has_memberpress ? __('MemberPress is recommended for full functionality.', 'memberpress-ai-assistant') : ''
        ];
        
        // Add OpenAI API connection status
        $openai_api_key = get_option('mpai_api_key', '');
        $info[__('OpenAI API', 'memberpress-ai-assistant')] = [
            'value' => !empty($openai_api_key) ? __('Configured', 'memberpress-ai-assistant') : __('Not Configured', 'memberpress-ai-assistant'),
            'status' => !empty($openai_api_key) ? 'good' : 'warning',
            'message' => empty($openai_api_key) ? __('API key required for AI functionality.', 'memberpress-ai-assistant') : ''
        ];
        
        // Add Anthropic API connection status
        $anthropic_api_key = get_option('mpai_anthropic_api_key', '');
        $info[__('Anthropic API', 'memberpress-ai-assistant')] = [
            'value' => !empty($anthropic_api_key) ? __('Configured', 'memberpress-ai-assistant') : __('Not Configured', 'memberpress-ai-assistant'),
            'status' => !empty($anthropic_api_key) ? 'good' : 'warning',
            'message' => empty($anthropic_api_key) ? __('API key required for Claude AI functionality.', 'memberpress-ai-assistant') : ''
        ];
        
        // Add primary API provider
        $primary_api = get_option('mpai_primary_api', 'openai');
        $has_primary_key = $primary_api === 'openai' ? !empty($openai_api_key) : !empty($anthropic_api_key);
        $info[__('Primary AI Provider', 'memberpress-ai-assistant')] = [
            'value' => $primary_api === 'openai' ? __('OpenAI (GPT)', 'memberpress-ai-assistant') : __('Anthropic (Claude)', 'memberpress-ai-assistant'),
            'status' => $has_primary_key ? 'good' : 'warning',
            'message' => !$has_primary_key ? __('Primary provider API key not configured.', 'memberpress-ai-assistant') : ''
        ];
        
        // Add platform info
        $info[__('Operating System', 'memberpress-ai-assistant')] = PHP_OS;
        $info[__('Server Software', 'memberpress-ai-assistant')] = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
        $info[__('Memory Limit', 'memberpress-ai-assistant')] = ini_get('memory_limit');
        $info[__('Max Execution Time', 'memberpress-ai-assistant')] = ini_get('max_execution_time') . 's';
        
        // Add curl info
        $info[__('cURL Enabled', 'memberpress-ai-assistant')] = [
            'value' => function_exists('curl_version') ? __('Yes', 'memberpress-ai-assistant') : __('No', 'memberpress-ai-assistant'),
            'status' => function_exists('curl_version') ? 'good' : 'error',
            'message' => !function_exists('curl_version') ? __('cURL is required for API communication.', 'memberpress-ai-assistant') : ''
        ];
        
        if (function_exists('curl_version')) {
            $curl_version = curl_version();
            $info[__('cURL Version', 'memberpress-ai-assistant')] = $curl_version['version'];
            $info[__('SSL Version', 'memberpress-ai-assistant')] = $curl_version['ssl_version'];
        }
        
        return $info;
    }

    /**
     * Render test categories
     */
    private function render_test_categories() {
        $tests = $this->get_available_tests();
        
        if (empty($tests)) {
            echo '<p>' . __('No diagnostic tests available.', 'memberpress-ai-assistant') . '</p>';
            return;
        }
        
        foreach ($tests as $category => $category_tests) {
            ?>
            <div class="mpai-test-category">
                <h3><?php echo esc_html($category); ?></h3>
                <div class="mpai-test-list">
                    <?php foreach ($category_tests as $test_id => $test): ?>
                    <div class="mpai-test-item">
                        <button class="button mpai-run-test" data-test="<?php echo esc_attr($test_id); ?>">
                            <?php echo esc_html($test['name']); ?>
                        </button>
                        <span class="mpai-test-description"><?php echo esc_html($test['description']); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php
        }
    }

    /**
     * Get available diagnostic tests
     * 
     * @return array Available tests
     */
    private function get_available_tests() {
        return [
            __('API Connectivity', 'memberpress-ai-assistant') => [
                'openai_connection' => [
                    'name' => __('OpenAI API Connection', 'memberpress-ai-assistant'),
                    'description' => __('Tests the connection to OpenAI API.', 'memberpress-ai-assistant'),
                    'callback' => [$this, 'test_openai_connection']
                ],
                'anthropic_connection' => [
                    'name' => __('Anthropic API Connection', 'memberpress-ai-assistant'),
                    'description' => __('Tests the connection to Anthropic API.', 'memberpress-ai-assistant'),
                    'callback' => [$this, 'test_anthropic_connection']
                ]
            ],
            __('MemberPress Integration', 'memberpress-ai-assistant') => [
                'memberpress_detection' => [
                    'name' => __('MemberPress Detection', 'memberpress-ai-assistant'),
                    'description' => __('Verifies that MemberPress is properly detected.', 'memberpress-ai-assistant'),
                    'callback' => [$this, 'test_memberpress_detection']
                ]
            ],
            __('System Features', 'memberpress-ai-assistant') => [
                'error_recovery' => [
                    'name' => __('Error Recovery System', 'memberpress-ai-assistant'),
                    'description' => __('Tests the Error Recovery System functionality.', 'memberpress-ai-assistant'),
                    'callback' => [$this, 'test_error_recovery']
                ],
                'console_logging' => [
                    'name' => __('Console Logging System', 'memberpress-ai-assistant'),
                    'description' => __('Tests the Console Logging System functionality.', 'memberpress-ai-assistant'),
                    'callback' => [$this, 'test_console_logging']
                ]
            ]
        ];
    }

    /**
     * Handle AJAX request to run diagnostics
     */
    public function handle_ajax_run_diagnostics() {
        // Verify nonce
        check_ajax_referer('mpai_diagnostics_nonce', 'nonce');
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        // Get test to run
        $test_id = isset($_POST['test_id']) ? sanitize_text_field($_POST['test_id']) : '';
        
        if (empty($test_id)) {
            wp_send_json_error('No test specified');
        }
        
        // Get all tests
        $tests = $this->get_available_tests();
        $test = null;
        
        // Find the requested test
        foreach ($tests as $category => $category_tests) {
            if (isset($category_tests[$test_id])) {
                $test = $category_tests[$test_id];
                break;
            }
        }
        
        if (!$test) {
            wp_send_json_error('Test not found');
        }
        
        // Check if test has a callback
        if (!isset($test['callback']) || !is_callable($test['callback'])) {
            wp_send_json_error('Test callback not defined');
        }
        
        // Run the test
        $result = call_user_func($test['callback']);
        
        // Return result
        wp_send_json_success([
            'name' => $test['name'],
            'result' => $result
        ]);
    }

    /**
     * Test OpenAI connection
     * 
     * @return array Test result
     */
    public function test_openai_connection() {
        $api_key = get_option('mpai_api_key', '');
        
        if (empty($api_key)) {
            return [
                'status' => 'warning',
                'message' => __('OpenAI API key not configured.', 'memberpress-ai-assistant'),
                'details' => [
                    'api_key_configured' => false
                ]
            ];
        }
        
        // Load OpenAI class if needed
        if (!class_exists('MPAI_OpenAI')) {
            require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-openai.php';
        }
        
        try {
            $openai = new MPAI_OpenAI();
            $model = get_option('mpai_model', 'gpt-4o');
            
            // Make a simple completion request
            $response = $openai->complete([
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a system diagnostic tool.'],
                    ['role' => 'user', 'content' => 'Respond with "Connection successful" if you receive this message.']
                ],
                'max_tokens' => 50,
                'temperature' => 0
            ]);
            
            if (empty($response)) {
                return [
                    'status' => 'error',
                    'message' => __('No response received from OpenAI.', 'memberpress-ai-assistant'),
                    'details' => [
                        'api_key_configured' => true,
                        'error' => 'Empty response'
                    ]
                ];
            }
            
            return [
                'status' => 'success',
                'message' => __('Successfully connected to OpenAI API.', 'memberpress-ai-assistant'),
                'details' => [
                    'api_key_configured' => true,
                    'model_used' => $model,
                    'response' => $response
                ]
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => __('Error connecting to OpenAI API:', 'memberpress-ai-assistant') . ' ' . $e->getMessage(),
                'details' => [
                    'api_key_configured' => true,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]
            ];
        }
    }

    /**
     * Test Anthropic connection
     * 
     * @return array Test result
     */
    public function test_anthropic_connection() {
        $api_key = get_option('mpai_anthropic_api_key', '');
        
        if (empty($api_key)) {
            return [
                'status' => 'warning',
                'message' => __('Anthropic API key not configured.', 'memberpress-ai-assistant'),
                'details' => [
                    'api_key_configured' => false
                ]
            ];
        }
        
        // Load Anthropic class if needed
        if (!class_exists('MPAI_Anthropic')) {
            require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-anthropic.php';
        }
        
        try {
            $anthropic = new MPAI_Anthropic();
            $model = get_option('mpai_anthropic_model', 'claude-3-opus-20240229');
            
            // Make a simple completion request
            $response = $anthropic->complete([
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a system diagnostic tool.'],
                    ['role' => 'user', 'content' => 'Respond with "Connection successful" if you receive this message.']
                ],
                'max_tokens' => 50,
                'temperature' => 0
            ]);
            
            if (empty($response)) {
                return [
                    'status' => 'error',
                    'message' => __('No response received from Anthropic.', 'memberpress-ai-assistant'),
                    'details' => [
                        'api_key_configured' => true,
                        'error' => 'Empty response'
                    ]
                ];
            }
            
            return [
                'status' => 'success',
                'message' => __('Successfully connected to Anthropic API.', 'memberpress-ai-assistant'),
                'details' => [
                    'api_key_configured' => true,
                    'model_used' => $model,
                    'response' => $response
                ]
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => __('Error connecting to Anthropic API:', 'memberpress-ai-assistant') . ' ' . $e->getMessage(),
                'details' => [
                    'api_key_configured' => true,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]
            ];
        }
    }

    /**
     * Test MemberPress detection
     * 
     * @return array Test result
     */
    public function test_memberpress_detection() {
        // Check for MemberPress class definitions
        $has_memberpress = false;
        $memberpress_classes = [
            'MeprAppCtrl',
            'MeprOptions',
            'MeprUser',
            'MeprProduct',
            'MeprTransaction',
            'MeprSubscription'
        ];
        
        $detected_classes = [];
        
        foreach ($memberpress_classes as $class) {
            if (class_exists($class)) {
                $has_memberpress = true;
                $detected_classes[] = $class;
            }
        }
        
        // Check for MemberPress constants
        $memberpress_constants = [
            'MEPR_VERSION',
            'MEPR_PLUGIN_NAME',
            'MEPR_PATH',
            'MEPR_URL'
        ];
        
        $detected_constants = [];
        
        foreach ($memberpress_constants as $constant) {
            if (defined($constant)) {
                $has_memberpress = true;
                $detected_constants[] = $constant;
            }
        }
        
        // Check if the plugin is active
        $plugin_active = false;
        if (function_exists('is_plugin_active')) {
            $plugin_active = is_plugin_active('memberpress/memberpress.php');
            if ($plugin_active) {
                $has_memberpress = true;
            }
        }
        
        if (!$has_memberpress) {
            return [
                'status' => 'warning',
                'message' => __('MemberPress is not detected.', 'memberpress-ai-assistant'),
                'details' => [
                    'memberpress_detected' => false,
                    'plugin_active' => $plugin_active,
                    'detected_classes' => $detected_classes,
                    'detected_constants' => $detected_constants
                ]
            ];
        }
        
        return [
            'status' => 'success',
            'message' => __('MemberPress is properly detected.', 'memberpress-ai-assistant'),
            'details' => [
                'memberpress_detected' => true,
                'plugin_active' => $plugin_active,
                'detected_classes' => $detected_classes,
                'detected_constants' => $detected_constants
            ]
        ];
    }

    /**
     * Test error recovery system
     * 
     * @return array Test result
     */
    public function test_error_recovery() {
        // Check if error recovery class exists
        if (!class_exists('MPAI_Error_Recovery')) {
            $error_recovery_file = MPAI_PLUGIN_DIR . 'includes/class-mpai-error-recovery.php';
            
            if (!file_exists($error_recovery_file)) {
                return [
                    'status' => 'error',
                    'message' => __('Error Recovery System file not found.', 'memberpress-ai-assistant'),
                    'details' => [
                        'file_path' => $error_recovery_file,
                        'file_exists' => false
                    ]
                ];
            }
            
            // Try to include the file
            require_once $error_recovery_file;
            
            if (!class_exists('MPAI_Error_Recovery')) {
                return [
                    'status' => 'error',
                    'message' => __('Error Recovery System class not found after loading file.', 'memberpress-ai-assistant'),
                    'details' => [
                        'file_path' => $error_recovery_file,
                        'file_exists' => true,
                        'class_exists' => false
                    ]
                ];
            }
        }
        
        // Check for test file
        $test_file = MPAI_PLUGIN_DIR . 'test/test-error-recovery.php';
        
        if (!file_exists($test_file)) {
            return [
                'status' => 'error',
                'message' => __('Error Recovery test file not found.', 'memberpress-ai-assistant'),
                'details' => [
                    'test_file_path' => $test_file,
                    'test_file_exists' => false
                ]
            ];
        }
        
        // Include test file and run tests
        require_once $test_file;
        
        if (!function_exists('mpai_test_error_recovery')) {
            return [
                'status' => 'error',
                'message' => __('Error Recovery test function not found.', 'memberpress-ai-assistant'),
                'details' => [
                    'test_file_path' => $test_file,
                    'test_file_exists' => true,
                    'function_exists' => false
                ]
            ];
        }
        
        // Run the tests
        try {
            $result = mpai_test_error_recovery();
            
            return [
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message'],
                'details' => $result
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => __('Error running Error Recovery tests:', 'memberpress-ai-assistant') . ' ' . $e->getMessage(),
                'details' => [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]
            ];
        }
    }

    /**
     * Handle AJAX request to test error recovery
     */
    public function handle_test_error_recovery() {
        check_ajax_referer('mpai_nonce', 'nonce');
        
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
                    wp_send_json_error([
                        'message' => 'Error recovery test function not found',
                        'success' => false
                    ]);
                }
            } else {
                wp_send_json_error([
                    'message' => 'Error recovery test file not found at: ' . $test_file,
                    'success' => false
                ]);
            }
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => 'Error running tests: ' . $e->getMessage(),
                'success' => false
            ]);
        }
    }

    /**
     * Test console logging system
     * 
     * @return array Test result
     */
    public function test_console_logging() {
        // Check console logging configuration
        $enabled = get_option('mpai_enable_console_logging', '0') === '1';
        $log_level = get_option('mpai_console_log_level', 'info');
        $log_api_calls = get_option('mpai_log_api_calls', '0') === '1';
        $log_tool_usage = get_option('mpai_log_tool_usage', '0') === '1';
        $log_agent_activity = get_option('mpai_log_agent_activity', '0') === '1';
        $log_timing = get_option('mpai_log_timing', '0') === '1';
        
        // Check if the logger script exists
        $logger_file = MPAI_PLUGIN_URL . 'assets/js/mpai-logger.js';
        $logger_file_path = MPAI_PLUGIN_DIR . 'assets/js/mpai-logger.js';
        
        $file_exists = file_exists($logger_file_path);
        
        return [
            'status' => $file_exists ? 'success' : 'error',
            'message' => $file_exists 
                ? __('Console logging system is properly configured.', 'memberpress-ai-assistant')
                : __('Console logging system script not found.', 'memberpress-ai-assistant'),
            'details' => [
                'enabled' => $enabled,
                'log_level' => $log_level,
                'categories' => [
                    'api_calls' => $log_api_calls,
                    'tool_usage' => $log_tool_usage,
                    'agent_activity' => $log_agent_activity,
                    'timing' => $log_timing
                ],
                'logger_file' => $logger_file,
                'file_exists' => $file_exists
            ]
        ];
    }
}