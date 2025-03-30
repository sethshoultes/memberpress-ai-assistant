<?php
/**
 * Diagnostic Tool for MemberPress AI Assistant
 *
 * @package MemberPress AI Assistant
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Diagnostic Tool for MemberPress AI Assistant
 * 
 * Provides diagnostic capabilities and system status checks
 */
class MPAI_Diagnostic_Tool extends MPAI_Base_Tool {
    /**
     * Constructor
     */
    public function __construct() {
        $this->name = 'diagnostic';
        $this->description = 'Run various diagnostic tests and status checks for the MemberPress AI Assistant';
    }
    
    /**
     * Get tool definition for AI function calling
     *
     * @return array Tool definition
     */
    public function get_tool_definition() {
        return [
            'name' => 'run_diagnostic',
            'description' => 'Run diagnostic tests and status checks for the MemberPress AI Assistant',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'test_type' => [
                        'type' => 'string',
                        'enum' => ['openai_connection', 'anthropic_connection', 'memberpress_connection', 'wordpress_info', 'plugin_status', 'all'],
                        'description' => 'The type of diagnostic test to run'
                    ],
                    'api_key' => [
                        'type' => 'string',
                        'description' => 'Optional API key to use for the test (otherwise uses the stored key)'
                    ],
                ],
                'required' => ['test_type']
            ],
        ];
    }
    
    /**
     * Execute the diagnostic tool
     *
     * @param array $parameters Tool parameters
     * @return array Result of the diagnostic test
     */
    public function execute($parameters) {
        if (!isset($parameters['test_type'])) {
            return [
                'success' => false,
                'message' => 'Missing required parameter: test_type',
                'available_tests' => ['openai_connection', 'anthropic_connection', 'memberpress_connection', 'wordpress_info', 'plugin_status', 'all']
            ];
        }
        
        $test_type = sanitize_text_field($parameters['test_type']);
        $result = [];
        
        switch ($test_type) {
            case 'openai_connection':
                $result = $this->test_openai_connection($parameters);
                break;
                
            case 'anthropic_connection':
                $result = $this->test_anthropic_connection($parameters);
                break;
                
            case 'memberpress_connection':
                $result = $this->test_memberpress_connection($parameters);
                break;
                
            case 'wordpress_info':
                $result = $this->get_wordpress_info();
                break;
                
            case 'plugin_status':
                $result = $this->get_plugin_status();
                break;
                
            case 'all':
                $result = [
                    'openai' => $this->test_openai_connection($parameters),
                    'anthropic' => $this->test_anthropic_connection($parameters),
                    'memberpress' => $this->test_memberpress_connection($parameters),
                    'wordpress' => $this->get_wordpress_info(),
                    'plugin_status' => $this->get_plugin_status(),
                ];
                break;
                
            default:
                $result = [
                    'success' => false,
                    'message' => 'Invalid test type: ' . $test_type,
                    'available_tests' => ['openai_connection', 'anthropic_connection', 'memberpress_connection', 'wordpress_info', 'plugin_status', 'all']
                ];
        }
        
        return $result;
    }
    
    /**
     * Test OpenAI API connection
     *
     * @param array $parameters Tool parameters
     * @return array Test results
     */
    public function test_openai_connection($parameters) {
        // Get API key from parameters or use the saved one
        $api_key = isset($parameters['api_key']) ? sanitize_text_field($parameters['api_key']) : get_option('mpai_api_key', '');
        
        if (empty($api_key)) {
            return [
                'success' => false,
                'message' => 'OpenAI API key is not configured',
                'status' => 'unconfigured'
            ];
        }
        
        // Test OpenAI API
        $endpoint = 'https://api.openai.com/v1/models';
        
        $response = wp_remote_get(
            $endpoint,
            array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type' => 'application/json',
                ),
                'timeout' => 30,
                'sslverify' => true,
            )
        );
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => 'Connection error: ' . $response->get_error_message(),
                'status' => 'connection_error'
            ];
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($status_code !== 200) {
            $error_message = isset($data['error']['message']) ? $data['error']['message'] : 'Invalid API key or API error';
            return [
                'success' => false,
                'message' => 'API Error (' . $status_code . '): ' . $error_message,
                'status' => 'api_error',
                'code' => $status_code
            ];
        }
        
        // Get available models
        $models = [];
        foreach ($data['data'] as $model) {
            $models[] = $model['id'];
        }
        
        // Return only chat models
        $chat_models = array_filter($models, function($model) {
            return strpos($model, 'gpt-') === 0 || strpos($model, 'text-') === 0;
        });
        
        return [
            'success' => true,
            'message' => 'OpenAI API connection successful',
            'status' => 'connected',
            'models_count' => count($data['data']),
            'chat_models' => array_values($chat_models)
        ];
    }
    
    /**
     * Test Anthropic API connection
     *
     * @param array $parameters Tool parameters
     * @return array Test results
     */
    public function test_anthropic_connection($parameters) {
        // Get API key from parameters or use the saved one
        $api_key = isset($parameters['api_key']) ? sanitize_text_field($parameters['api_key']) : get_option('mpai_anthropic_api_key', '');
        $model = !empty($parameters['model']) ? sanitize_text_field($parameters['model']) : 'claude-3-haiku-20240307';
        
        if (empty($api_key)) {
            return [
                'success' => false,
                'message' => 'Anthropic API key is not configured',
                'status' => 'unconfigured'
            ];
        }
        
        // Make a simple request to the Anthropic API
        $response = wp_remote_post(
            'https://api.anthropic.com/v1/messages',
            array(
                'headers' => array(
                    'x-api-key' => $api_key,
                    'anthropic-version' => '2023-06-01',
                    'Content-Type' => 'application/json',
                ),
                'body' => json_encode(array(
                    'model' => $model,
                    'messages' => array(
                        array(
                            'role' => 'user',
                            'content' => 'Hi, this is a test message from MemberPress AI Assistant. Please respond with "Connection successful" to verify API connectivity.'
                        )
                    ),
                    'max_tokens' => 50
                )),
                'timeout' => 30,
                'sslverify' => true,
            )
        );
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => 'Connection error: ' . $response->get_error_message(),
                'status' => 'connection_error'
            ];
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($status_code !== 200) {
            $error_message = isset($data['error']['message']) ? $data['error']['message'] : 'Invalid API key or API error';
            return [
                'success' => false,
                'message' => 'API Error (' . $status_code . '): ' . $error_message,
                'status' => 'api_error',
                'code' => $status_code
            ];
        }
        
        // Get available models (would require an additional API call to list models)
        $available_models = [
            'claude-3-opus-20240229',
            'claude-3-sonnet-20240229',
            'claude-3-haiku-20240307',
            'claude-2.1',
            'claude-2.0'
        ];
        
        return [
            'success' => true,
            'message' => 'Anthropic API connection successful',
            'status' => 'connected',
            'response_text' => $data['content'][0]['text'],
            'current_model' => $model,
            'available_models' => $available_models
        ];
    }
    
    /**
     * Test MemberPress API connection
     *
     * @param array $parameters Tool parameters
     * @return array Test results
     */
    public function test_memberpress_connection($parameters) {
        // Check if MemberPress is active
        if (!class_exists('MeprAppCtrl')) {
            return [
                'success' => false,
                'message' => 'MemberPress plugin is not active',
                'status' => 'inactive',
                'plugin_exists' => false
            ];
        }
        
        // Check if Developer Tools is active
        $dev_tools_active = class_exists('MeprRestRoutes');
        if (!$dev_tools_active) {
            return [
                'success' => false,
                'message' => 'MemberPress Developer Tools plugin is not active',
                'status' => 'missing_dev_tools',
                'plugin_exists' => true
            ];
        }
        
        // Get API key from parameters or use the saved one
        $api_key = isset($parameters['api_key']) ? sanitize_text_field($parameters['api_key']) : get_option('mpai_memberpress_api_key', '');
        
        if (empty($api_key)) {
            return [
                'success' => false,
                'message' => 'MemberPress API key is not configured',
                'status' => 'unconfigured',
                'plugin_exists' => true,
                'dev_tools_active' => true
            ];
        }
        
        // Test MemberPress API
        $base_url = site_url('/wp-json/mp/v1/');
        $endpoint = $base_url . 'memberships';
        
        $args = array(
            'method' => 'GET',
            'headers' => array(
                'Authorization' => $api_key,
                'Content-Type' => 'application/json',
            ),
            'timeout' => 30,
        );
        
        $response = wp_remote_request($endpoint, $args);
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => 'Connection error: ' . $response->get_error_message(),
                'status' => 'connection_error',
                'plugin_exists' => true,
                'dev_tools_active' => true
            ];
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($status_code !== 200) {
            $error_message = 'Invalid API key or API error';
            if (is_array($data) && isset($data['message'])) {
                $error_message = $data['message'];
            }
            
            return [
                'success' => false,
                'message' => 'API Error (' . $status_code . '): ' . $error_message,
                'status' => 'api_error',
                'code' => $status_code,
                'plugin_exists' => true,
                'dev_tools_active' => true
            ];
        }
        
        // Check for memberships
        $membership_count = is_array($data) ? count($data) : 0;
        $memberships = [];
        
        if (is_array($data) && $membership_count > 0) {
            foreach ($data as $membership) {
                if (isset($membership['title'])) {
                    $price = isset($membership['price']) ? $membership['price'] : 'N/A';
                    $memberships[] = [
                        'title' => $membership['title'],
                        'price' => $price
                    ];
                }
            }
        }
        
        return [
            'success' => true,
            'message' => 'MemberPress API connection successful',
            'status' => 'connected',
            'plugin_exists' => true,
            'dev_tools_active' => true,
            'membership_count' => $membership_count,
            'memberships' => $memberships
        ];
    }
    
    /**
     * Get WordPress information
     *
     * @return array WordPress information
     */
    public function get_wordpress_info() {
        global $wpdb;
        
        $wp_version = get_bloginfo('version');
        $home_url = home_url();
        $site_url = site_url();
        $admin_url = admin_url();
        $rest_url = rest_url();
        $ajax_url = admin_url('admin-ajax.php');
        
        // PHP info
        $php_version = phpversion();
        $php_memory_limit = ini_get('memory_limit');
        $php_max_execution_time = ini_get('max_execution_time');
        $php_post_max_size = ini_get('post_max_size');
        
        // WordPress info
        $active_theme = wp_get_theme();
        $active_plugins = get_option('active_plugins');
        $plugin_count = count($active_plugins);
        
        // Database info
        $db_name = defined('DB_NAME') ? DB_NAME : 'Unknown';
        $db_host = defined('DB_HOST') ? DB_HOST : 'Unknown';
        $db_prefix = $wpdb->prefix;
        $mpai_tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}mpai_%'", ARRAY_N);
        
        // Server info
        $server_software = isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'Unknown';
        
        return [
            'success' => true,
            'wordpress' => [
                'version' => $wp_version,
                'home_url' => $home_url,
                'site_url' => $site_url,
                'admin_url' => $admin_url,
                'rest_url' => $rest_url,
                'ajax_url' => $ajax_url,
                'theme' => $active_theme->get('Name') . ' ' . $active_theme->get('Version'),
                'plugin_count' => $plugin_count
            ],
            'php' => [
                'version' => $php_version,
                'memory_limit' => $php_memory_limit,
                'max_execution_time' => $php_max_execution_time,
                'post_max_size' => $php_post_max_size
            ],
            'database' => [
                'name' => $db_name,
                'host' => $db_host,
                'prefix' => $db_prefix,
                'mpai_tables' => count($mpai_tables)
            ],
            'server' => [
                'software' => $server_software
            ]
        ];
    }
    
    /**
     * Get plugin status information
     *
     * @return array Plugin status information
     */
    public function get_plugin_status() {
        // Check plugin versions
        $mpai_version = defined('MPAI_VERSION') ? MPAI_VERSION : 'Unknown';
        $memberpress_active = class_exists('MeprAppCtrl');
        $memberpress_version = 'Not active';
        if ($memberpress_active && defined('MEPR_VERSION')) {
            $memberpress_version = MEPR_VERSION;
        }
        $dev_tools_active = class_exists('MeprRestRoutes');
        
        // Check API configurations
        $openai_api_configured = !empty(get_option('mpai_api_key', ''));
        $anthropic_api_configured = !empty(get_option('mpai_anthropic_api_key', ''));
        $memberpress_api_configured = !empty(get_option('mpai_memberpress_api_key', ''));
        
        // Get selected models
        $openai_model = get_option('mpai_model', 'gpt-4o');
        $anthropic_model = get_option('mpai_anthropic_model', 'claude-3-opus-20240229');
        
        // Get primary API setting
        $primary_api = get_option('mpai_primary_api', 'openai');
        
        // Check tool status
        $wp_cli_enabled = get_option('mpai_enable_wp_cli_tool', true);
        $memberpress_info_enabled = get_option('mpai_enable_memberpress_info_tool', true);
        
        // Check if database tables exist
        global $wpdb;
        $table_conversations = $wpdb->prefix . 'mpai_conversations';
        $table_messages = $wpdb->prefix . 'mpai_messages';
        $conversations_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_conversations}'") === $table_conversations;
        $messages_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_messages}'") === $table_messages;
        
        return [
            'success' => true,
            'plugin' => [
                'version' => $mpai_version,
                'memberpress_active' => $memberpress_active,
                'memberpress_version' => $memberpress_version,
                'dev_tools_active' => $dev_tools_active
            ],
            'api_config' => [
                'openai_configured' => $openai_api_configured,
                'anthropic_configured' => $anthropic_api_configured,
                'memberpress_configured' => $memberpress_api_configured,
                'primary_api' => $primary_api,
                'openai_model' => $openai_model,
                'anthropic_model' => $anthropic_model
            ],
            'tools' => [
                'wp_cli_enabled' => $wp_cli_enabled,
                'memberpress_info_enabled' => $memberpress_info_enabled
            ],
            'database' => [
                'tables_exist' => ($conversations_exists && $messages_exists),
                'conversations_table' => $conversations_exists,
                'messages_table' => $messages_exists
            ]
        ];
    }
}