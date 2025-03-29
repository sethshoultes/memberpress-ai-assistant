<?php
/**
 * Plugin Name: MemberPress AI Assistant
 * Plugin URI: https://example.com/memberpress-ai-assistant
 * Description: AI-powered chat assistant for MemberPress that helps with membership management, troubleshooting, and WordPress CLI command execution.
 * Version: 1.0.0
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

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('MPAI_VERSION', '1.0.0');
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
     * Initialize the plugin.
     */
    private function __construct() {
        // Check if MemberPress is active
        add_action('admin_init', array($this, 'check_memberpress'));
        
        // Load required files
        $this->load_dependencies();
        
        // Initialize admin section
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Add chat interface to admin footer
        add_action('admin_footer', array($this, 'render_chat_interface'));
        
        // Initialize REST API
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        
        // Register AJAX handlers
        add_action('wp_ajax_mpai_process_chat', array($this, 'process_chat_ajax'));
        add_action('wp_ajax_mpai_clear_chat_history', array($this, 'clear_chat_history_ajax'));
        add_action('wp_ajax_mpai_get_chat_history', array($this, 'get_chat_history_ajax'));
        
        // Register activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
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
     * Check if MemberPress is active, if not, display notice
     */
    public function check_memberpress() {
        if (!class_exists('MeprAppCtrl')) {
            add_action('admin_notices', array($this, 'memberpress_missing_notice'));
        }
    }

    /**
     * Display MemberPress missing notice
     */
    public function memberpress_missing_notice() {
        ?>
        <div class="error">
            <p><?php _e('MemberPress AI Assistant requires MemberPress to be installed and activated.', 'memberpress-ai-assistant'); ?></p>
        </div>
        <?php
    }

    /**
     * Load required dependencies
     */
    private function load_dependencies() {
        // API Integration Classes
        require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-openai.php';
        require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-memberpress-api.php';
        
        // Functionality Classes
        require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-chat.php';
        require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-context-manager.php';
        
        // Admin and Settings
        require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-admin.php';
        require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-settings.php';
        
        // Chat Interface
        require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-chat-interface.php';
        
        // CLI Commands
        if (defined('WP_CLI') && WP_CLI) {
            require_once MPAI_PLUGIN_DIR . 'includes/cli/class-mpai-cli-commands.php';
        }
    }

    /**
     * Add admin menu items
     */
    public function add_admin_menu() {
        add_submenu_page(
            'memberpress',
            __('AI Assistant', 'memberpress-ai-assistant'),
            __('AI Assistant', 'memberpress-ai-assistant'),
            'manage_options',
            'memberpress-ai-assistant',
            array($this, 'display_admin_page')
        );
        
        // Register the settings page and handle options registration
        $settings_page = add_submenu_page(
            'memberpress-ai-assistant',
            __('Settings', 'memberpress-ai-assistant'),
            __('Settings', 'memberpress-ai-assistant'),
            'manage_options',
            'memberpress-ai-assistant-settings',
            array($this, 'display_settings_page')
        );
        
        // Make sure settings are properly registered for this page
        add_action('load-' . $settings_page, array($this, 'settings_page_load'));
    }
    
    /**
     * Settings page load hook
     */
    public function settings_page_load() {
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
        require_once MPAI_PLUGIN_DIR . 'includes/admin-page.php';
    }

    /**
     * Display settings page
     */
    public function display_settings_page() {
        require_once MPAI_PLUGIN_DIR . 'includes/settings-page.php';
    }

    /**
     * Render chat interface in admin footer
     */
    public function render_chat_interface() {
        require_once MPAI_PLUGIN_DIR . 'includes/chat-interface.php';
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Load chat interface assets on all admin pages
        wp_enqueue_style('dashicons');
        
        wp_enqueue_style(
            'mpai-chat-css',
            MPAI_PLUGIN_URL . 'assets/css/chat-interface.css',
            array('dashicons'),
            MPAI_VERSION
        );

        wp_enqueue_script(
            'mpai-chat-js',
            MPAI_PLUGIN_URL . 'assets/js/chat-interface.js',
            array('jquery'),
            MPAI_VERSION,
            true
        );

        wp_localize_script(
            'mpai-chat-js',
            'mpai_chat_data',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mpai_chat_nonce'),
                'strings' => array(
                    'send_message' => __('Send message', 'memberpress-ai-assistant'),
                    'typing' => __('MemberPress AI is typing...', 'memberpress-ai-assistant'),
                    'welcome_message' => get_option('mpai_welcome_message', __('Hi there! I\'m your MemberPress AI Assistant. How can I help you today?', 'memberpress-ai-assistant')),
                    'error_message' => __('Sorry, there was an error processing your request. Please try again.', 'memberpress-ai-assistant'),
                ),
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
                array('jquery'),
                MPAI_VERSION,
                true
            );

            wp_localize_script(
                'mpai-admin-js',
                'mpai_data',
                array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'rest_url' => rest_url('mpai/v1/'),
                    'nonce' => wp_create_nonce('mpai_nonce'),
                    'page' => $hook,
                    'plugin_url' => MPAI_PLUGIN_URL,
                )
            );
        }
    }

    /**
     * Process chat message via AJAX
     */
    public function process_chat_ajax() {
        // Check nonce for security
        check_ajax_referer('mpai_chat_nonce', 'nonce');

        // Only allow logged-in users with appropriate capabilities
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Unauthorized access');
        }

        // Get the message from the request
        if (!isset($_POST['message'])) {
            wp_send_json_error('No message provided');
            return;
        }
        
        $message = sanitize_text_field($_POST['message']);
        
        if (empty($message)) {
            wp_send_json_error('Message cannot be empty');
            return;
        }

        try {
            // Process the message using the chat handler
            $chat = new MPAI_Chat();
            $response_data = $chat->process_message($message);

            // For debugging
            error_log('MPAI: AJAX response data: ' . json_encode($response_data));

            // Extract response content for saving to history
            if (is_array($response_data) && isset($response_data['message'])) {
                $response_content = $response_data['message'];
            } else if (is_array($response_data) && isset($response_data['success']) && isset($response_data['raw_response'])) {
                $response_content = $response_data['raw_response'];
            } else if (is_string($response_data)) {
                $response_content = $response_data;
            } else {
                $response_content = 'Invalid response format';
            }
            
            // Always save to user meta to ensure chat history is available
            $this->save_message_to_history($message, $response_content);
            
            // Log whether we're using database storage
            error_log('MPAI: Using database storage: ' . ($this->is_using_database_storage() ? 'yes' : 'no'));

            // Standardize the response format to ensure consistent structure
            if ($response_data) {
                if (is_array($response_data) && isset($response_data['success'])) {
                    // If it's already in the expected format with success flag
                    if ($response_data['success']) {
                        wp_send_json_success(array(
                            'response' => isset($response_data['message']) ? $response_data['message'] : 'Success but no message provided',
                        ));
                    } else {
                        wp_send_json_error(isset($response_data['message']) ? $response_data['message'] : 'Unknown error occurred');
                    }
                } else if (is_string($response_data)) {
                    // Just a direct string response
                    wp_send_json_success(array(
                        'response' => $response_data,
                    ));
                } else {
                    // Invalid response format - log and return error
                    error_log('MPAI: Invalid response format: ' . print_r($response_data, true));
                    wp_send_json_success(array(
                        'response' => 'Response received but in unexpected format. Check error logs for details.',
                    ));
                }
            } else {
                wp_send_json_error('Failed to get response from AI service');
            }
        } catch (Exception $e) {
            error_log('MPAI: Exception in process_chat_ajax: ' . $e->getMessage());
            wp_send_json_error('Error: ' . $e->getMessage());
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
     * Clear chat history via AJAX
     */
    public function clear_chat_history_ajax() {
        // Check nonce for security
        check_ajax_referer('mpai_chat_nonce', 'nonce');

        // Only allow logged-in users with appropriate capabilities
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Unauthorized access');
        }

        // Clear the conversation history
        $user_id = get_current_user_id();
        delete_user_meta($user_id, 'mpai_conversation_history');

        wp_send_json_success(array(
            'message' => __('Chat history cleared', 'memberpress-ai-assistant'),
        ));
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
        
        // Clear rewrite rules
        flush_rewrite_rules();
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
    }

    /**
     * Set default options
     */
    private function set_default_options() {
        $default_options = array(
            'api_key' => '',
            'model' => 'gpt-4o',
            'temperature' => 0.7,
            'max_tokens' => 2048,
            'enable_chat' => true,
            'chat_position' => 'bottom-right',
            'show_on_all_pages' => true,
            'welcome_message' => 'Hi there! I\'m your MemberPress AI Assistant. How can I help you today?'
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