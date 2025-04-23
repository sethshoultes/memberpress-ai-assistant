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
        
        // Tool Call Detector - add before tools module
        wp_enqueue_script(
            'mpai-tool-call-detector',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/modules/mpai-tool-call-detector.js',
            array('jquery', 'mpai-logger-js'),
            $this->version . '.' . time(), // Add timestamp for cache busting
            true
        );
        
        // 4. Tools module (depends on messages, formatters, and tool call detector)
        wp_enqueue_script(
            'mpai-chat-tools',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/modules/mpai-chat-tools.js',
            array('jquery', 'mpai-logger-js', 'mpai-chat-formatters', 'mpai-chat-messages', 'mpai-tool-call-detector'),
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
        
        // 7. Message processor module for handling JSON in messages
        wp_enqueue_script(
            'mpai-message-processor',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/modules/mpai-message-processor.js',
            array(
                'jquery', 
                'mpai-logger-js', 
                'mpai-chat-messages', 
                'mpai-chat-tools',
                'mpai-tool-call-detector'
            ),
            $this->version . '.' . time(), // Add timestamp for cache busting
            true
        );
        
        // 8. Finally, enqueue the main chat interface loader
        wp_enqueue_script(
            $this->plugin_name . '-chat',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/modules/chat-interface-loader.js',
            array(
                'jquery', 
                'mpai-logger-js', 
                'mpai-chat-formatters', 
                'mpai-chat-ui-utils', 
                'mpai-chat-messages',
                'mpai-tool-call-detector',
                'mpai-chat-tools', 
                'mpai-chat-history',
                'mpai-blog-formatter',
                'mpai-message-processor'
            ),
            $this->version . '.' . time(), // Add timestamp for cache busting
            true
        );

        // Get logger settings with debug info
        mpai_log_debug('Getting logger settings for chat interface', 'chat-interface');
        mpai_log_debug('mpai_enable_console_logging = ' . get_option('mpai_enable_console_logging', '1'), 'chat-interface');
        
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
        mpai_log_debug('Chat interface logger settings: ' . json_encode($logger_settings), 'chat-interface');
        
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
     * Check if user has consented to terms
     *
     * @return bool
     */
    public function check_consent() {
        $consent_manager = MPAI_Consent_Manager::get_instance();
        return $consent_manager->has_user_consented();
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
        
        // Check if user has consented to terms
        if (!$this->check_consent()) {
            wp_send_json_error('User has not consented to terms');
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
            // Process XML content before sending to client
            if (class_exists('MPAI_XML_Display_Handler')) {
                $xml_handler = new MPAI_XML_Display_Handler();
                
                // Check if response contains XML blog post format
                if ($xml_handler->contains_xml_blog_post($response)) {
                    mpai_log_debug('XML content detected in response, pre-processing before sending to client', 'chat-interface');
                    
                    // Create a message array for the XML handler
                    $message_data = array(
                        'role' => 'assistant',
                        'content' => $response
                    );
                    
                    // Process the XML content
                    $processed_response = $xml_handler->process_xml_content($response, $message_data);
                    
                    // Extract the blog post title for logging
                    preg_match('/<post-title>(.*?)<\/post-title>/s', $response, $title_match);
                    $title = isset($title_match[1]) ? trim($title_match[1]) : 'Unknown Title';
                    
                    mpai_log_debug('Processed XML content for blog post: ' . $title, 'chat-interface');
                    
                    // Use the processed response
                    $response = $processed_response;
                }
            }
            
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
        
        // Check if user has consented to terms
        if (!$this->check_consent()) {
            wp_send_json_error('User has not consented to terms');
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
                    
                    mpai_log_debug('Cleared database messages for user ' . $user_id, 'chat-interface');
                }
            }
            
            // 3. Force chat class to reset if it exists
            if (class_exists('MPAI_Chat')) {
                $chat = new MPAI_Chat();
                if (method_exists($chat, 'reset_conversation')) {
                    $chat->reset_conversation();
                    mpai_log_debug('Reset conversation in chat class', 'chat-interface');
                }
            }
            
            mpai_log_debug('Chat history fully cleared from all storage locations', 'chat-interface');
            
            wp_send_json_success(array(
                'message' => __('Chat history cleared', 'memberpress-ai-assistant'),
            ));
            
        } catch (Exception $e) {
            mpai_log_error('Error clearing chat history: ' . $e->getMessage(), 'chat-interface', array(
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ));
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
        
        // Check if user has consented to terms
        if (!$this->check_consent()) {
            wp_send_json_error('User has not consented to terms');
        }

        // Get the conversation history
        $user_id = get_current_user_id();
        $history = get_user_meta($user_id, 'mpai_conversation_history', true);

        // If history is empty, return an empty array
        if (empty($history)) {
            $history = array();
        }

        // Process any XML content in the history
        if (class_exists('MPAI_XML_Display_Handler')) {
            $xml_handler = new MPAI_XML_Display_Handler();
            
            foreach ($history as $key => $message) {
                // Only process assistant messages
                if (isset($message['role']) && $message['role'] === 'assistant') {
                    $content = $message['content'];
                    
                    // Check if content contains XML blog post format
                    if ($xml_handler->contains_xml_blog_post($content)) {
                        mpai_log_debug('XML content detected in history message, processing', 'chat-interface');
                        
                        // Process the XML content
                        $processed_content = $xml_handler->process_xml_content($content, $message);
                        
                        // Update the message content
                        $history[$key]['content'] = $processed_content;
                        
                        mpai_log_debug('Processed XML content in history message', 'chat-interface');
                    }
                }
            }
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

        // Process XML content in the response before saving to history
        $processed_response = $response;
        if (class_exists('MPAI_XML_Display_Handler')) {
            $xml_handler = new MPAI_XML_Display_Handler();
            
            // Check if response contains XML blog post format
            if ($xml_handler->contains_xml_blog_post($response)) {
                mpai_log_debug('XML content detected in response, processing before saving to history', 'chat-interface');
                
                // Create a message array for the XML handler
                $message_data = array(
                    'role' => 'assistant',
                    'content' => $response
                );
                
                // Process the XML content
                $processed_response = $xml_handler->process_xml_content($response, $message_data);
                
                mpai_log_debug('Processed XML content before saving to history', 'chat-interface');
            }
        }

        // Add assistant response
        $history[] = array(
            'role' => 'assistant',
            'content' => $processed_response,
            'timestamp' => time(),
        );

        // Limit history size (keep last 50 messages)
        if (count($history) > 50) {
            $history = array_slice($history, -50);
        }

        update_user_meta($user_id, 'mpai_conversation_history', $history);
    }
}