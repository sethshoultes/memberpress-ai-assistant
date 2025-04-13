<?php
/**
 * The chat interface functionality of the plugin.
 *
 * @package    MemberPress_AI_Assistant
 * @subpackage MemberPress_AI_Assistant/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * The chat interface functionality of the plugin.
 *
 * Handles the chat widget UI in the WordPress admin.
 *
 * @package    MemberPress_AI_Assistant
 * @subpackage MemberPress_AI_Assistant/includes
 */
class MPAI_Chat_Interface {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name    The name of this plugin.
     * @param    string    $version        The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the chat interface.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style('dashicons');
        
        wp_enqueue_style(
            $this->plugin_name . '-chat',
            plugin_dir_url(dirname(__FILE__)) . 'assets/css/chat-interface.css',
            array('dashicons'),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the chat interface.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        // First enqueue the logger with a debug message to ensure it's properly loading
        wp_enqueue_script(
            'mpai-logger-js',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/mpai-logger.js',
            array('jquery'),
            $this->version . '.' . time(), // Add timestamp to force cache refresh
            false // Load in header instead of footer to ensure it's available early
        );
        
        // Add inline script to check if logger is loaded
        wp_add_inline_script('mpai-logger-js', 'console.log("MPAI: Logger script loaded in chat interface context");');
        
        // Enqueue modular scripts in proper dependency order
        
        // 1. Formatters (no dependencies except jQuery and logger)
        wp_enqueue_script(
            'mpai-chat-formatters',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/modules/mpai-chat-formatters.js',
            array('jquery', 'mpai-logger-js'),
            $this->version . '.' . time(), // Add timestamp to force cache refresh
            true
        );
        
        // 2. UI Utils (no dependencies except jQuery and logger)
        wp_enqueue_script(
            'mpai-chat-ui-utils',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/modules/mpai-chat-ui-utils.js',
            array('jquery', 'mpai-logger-js'),
            $this->version . '.' . time(), // Add timestamp for cache busting
            true
        );
        
        // 3. Messages module (depends on formatters and UI utils)
        wp_enqueue_script(
            'mpai-chat-messages',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/modules/mpai-chat-messages.js',
            array('jquery', 'mpai-logger-js', 'mpai-chat-formatters', 'mpai-chat-ui-utils'),
            $this->version . '.' . time(), // Add timestamp for cache busting
            true
        );
        
        // 4. Tools module (depends on messages and formatters)
        wp_enqueue_script(
            'mpai-chat-tools',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/modules/mpai-chat-tools.js',
            array('jquery', 'mpai-logger-js', 'mpai-chat-formatters', 'mpai-chat-messages'),
            $this->version . '.' . time(), // Add timestamp for cache busting
            true
        );
        
        // 5. History module (depends on messages)
        wp_enqueue_script(
            'mpai-chat-history',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/modules/mpai-chat-history.js',
            array('jquery', 'mpai-logger-js', 'mpai-chat-messages'),
            $this->version . '.' . time(), // Add timestamp for cache busting
            true
        );
        
        // 6. Blog formatter module (depends on messages and tools)
        wp_enqueue_script(
            'mpai-blog-formatter',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/modules/mpai-blog-formatter.js',
            array('jquery', 'mpai-logger-js', 'mpai-chat-messages', 'mpai-chat-tools'),
            $this->version . '.' . time(), // Add timestamp for cache busting
            true
        );
        
        // 7. Finally, enqueue the main chat interface loader
        wp_enqueue_script(
            $this->plugin_name . '-chat',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/modules/chat-interface-loader.js',
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
            $this->version . '.' . time(), // Add timestamp for cache busting
            true
        );

        // Get logger settings with debug info
        error_log('MPAI: Getting logger settings for chat interface');
        error_log('MPAI: mpai_enable_console_logging = ' . get_option('mpai_enable_console_logging', '1'));
        
        // Ensure we're providing consistent string values for all boolean options
        $logger_settings = array(
            'enabled' => get_option('mpai_enable_console_logging', '1'),
            'log_level' => get_option('mpai_console_log_level', 'info'),
            'categories' => array(
                'api_calls' => '1',
                'tool_usage' => '1',
                'agent_activity' => '1',
                'timing' => '1',
                'ui' => '1' // Always enable UI logging
            )
        );
        
        // Log the settings for debugging
        error_log('MPAI: Chat interface logger settings: ' . json_encode($logger_settings));
        
        wp_localize_script(
            $this->plugin_name . '-chat',
            'mpai_chat_data',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mpai_chat_nonce'),
                'logger' => $logger_settings,
                'strings' => array(
                    'send_message' => __('Send message', 'memberpress-ai-assistant'),
                    'typing' => __('MemberPress AI is typing...', 'memberpress-ai-assistant'),
                    'welcome_message' => __('Hi there! I\'m your MemberPress AI Assistant. How can I help you today?', 'memberpress-ai-assistant'),
                    'error_message' => __('Sorry, there was an error processing your request. Please try again.', 'memberpress-ai-assistant'),
                ),
            )
        );
    }

    /**
     * Render the chat interface.
     *
     * @since    1.0.0
     */
    public function render() {
        include_once plugin_dir_path(dirname(__FILE__)) . 'includes/chat-interface.php';
    }

    /**
     * Process a chat message via AJAX.
     *
     * @since    1.0.0
     */
    public function process_chat_message() {
        // Check nonce for security
        check_ajax_referer('mpai_chat_nonce', 'nonce');

        // Only allow logged-in users with appropriate capabilities
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Unauthorized access');
        }

        // Get the message from the request
        $message = sanitize_text_field($_POST['message']);
        
        if (empty($message)) {
            wp_send_json_error('Message cannot be empty');
        }

        // Process the message using the OpenAI service
        $openai = new MPAI_OpenAI();
        $response = $openai->generate_chat_completion($message);

        // Return the response
        if ($response) {
            // Save message to history
            $this->save_message_to_history($message, $response);
            
            wp_send_json_success(array(
                'response' => $response,
            ));
        } else {
            wp_send_json_error('Failed to get response from AI service');
        }
    }

    /**
     * Clear chat history via AJAX.
     *
     * @since    1.0.0
     */
    public function clear_chat_history() {
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
     * Get chat history via AJAX.
     *
     * @since    1.0.0
     */
    public function get_chat_history() {
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
     * Save message to conversation history.
     * 
     * @param string $message The user message
     * @param string $response The AI response
     */
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
        
        // Get the consent value
        $consent = isset($_POST['consent']) ? (bool) $_POST['consent'] : false;
        
        // Get current user ID
        $user_id = get_current_user_id();
        
        // Update user meta
        update_user_meta($user_id, 'mpai_has_consented', $consent);
        
        // Return success
        wp_send_json_success(array(
            'message' => 'Consent saved',
            'consent' => $consent
        ));
    }

    private function save_message_to_history($message, $response) {
        $user_id = get_current_user_id();
        $history = get_user_meta($user_id, 'mpai_conversation_history', true);

        if (empty($history)) {
            $history = array();
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

        update_user_meta($user_id, 'mpai_conversation_history', $history);
    }
}