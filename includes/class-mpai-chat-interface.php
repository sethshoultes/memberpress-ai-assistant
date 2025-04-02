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
        // First enqueue the logger
        wp_enqueue_script(
            'mpai-logger-js',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/mpai-logger.js',
            array('jquery'),
            $this->version,
            true
        );
        
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
        
        // 6. Finally, enqueue the main chat interface loader
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
                'mpai-chat-history'
            ),
            $this->version . '.' . time(), // Add timestamp for cache busting
            true
        );

        wp_localize_script(
            $this->plugin_name . '-chat',
            'mpai_chat_data',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mpai_chat_nonce'),
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