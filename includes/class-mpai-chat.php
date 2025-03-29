<?php
/**
 * Chat Handler Class
 *
 * Handles AI chat functionality
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

class MPAI_Chat {
    /**
     * OpenAI integration instance
     *
     * @var MPAI_OpenAI
     */
    private $openai;

    /**
     * MemberPress API integration instance
     *
     * @var MPAI_MemberPress_API
     */
    private $memberpress_api;

    /**
     * Conversation history
     *
     * @var array
     */
    private $conversation = array();

    /**
     * Constructor
     */
    public function __construct() {
        $this->openai = new MPAI_OpenAI();
        $this->memberpress_api = new MPAI_MemberPress_API();
        $this->load_conversation();
    }

    /**
     * Load conversation history from database
     */
    private function load_conversation() {
        try {
            global $wpdb;
            
            $user_id = get_current_user_id();
            
            if (empty($user_id)) {
                error_log('MPAI: No user ID available to load conversation');
                return;
            }
            
            $conversation_id = $this->get_current_conversation_id();
            
            if (empty($conversation_id)) {
                error_log('MPAI: No conversation ID available to load conversation');
                return;
            }
            
            $table_messages = $wpdb->prefix . 'mpai_messages';
            
            // Verify the messages table exists
            if ($wpdb->get_var("SHOW TABLES LIKE '{$table_messages}'") !== $table_messages) {
                error_log('MPAI: Messages table does not exist, cannot load conversation');
                return;
            }
            
            $messages = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT message, response FROM $table_messages WHERE conversation_id = %s ORDER BY created_at ASC",
                    $conversation_id
                ),
                ARRAY_A
            );
            
            if (empty($messages)) {
                error_log('MPAI: No messages found for conversation ID: ' . $conversation_id);
                return;
            }
            
            // Initialize conversation with system prompt
            $this->conversation = array(
                array('role' => 'system', 'content' => $this->get_system_prompt())
            );
            
            // Add each message and response to the conversation
            foreach ($messages as $message) {
                if (isset($message['message']) && !empty($message['message'])) {
                    $this->conversation[] = array('role' => 'user', 'content' => $message['message']);
                }
                
                if (isset($message['response']) && !empty($message['response'])) {
                    $this->conversation[] = array('role' => 'assistant', 'content' => $message['response']);
                }
            }
            
            error_log('MPAI: Loaded ' . count($messages) . ' messages from conversation');
        } catch (Exception $e) {
            error_log('MPAI: Error loading conversation: ' . $e->getMessage());
            // Initialize with just the system prompt if we fail to load the conversation
            $this->conversation = array(
                array('role' => 'system', 'content' => $this->get_system_prompt())
            );
        }
    }

    /**
     * Get current conversation ID
     *
     * @return string|null Conversation ID or null if not found
     */
    private function get_current_conversation_id() {
        try {
            global $wpdb;
            
            $user_id = get_current_user_id();
            
            if (empty($user_id)) {
                error_log('MPAI: No user ID available to get conversation ID');
                return null;
            }
            
            $table_conversations = $wpdb->prefix . 'mpai_conversations';
            
            // Check if the conversations table exists
            if ($wpdb->get_var("SHOW TABLES LIKE '{$table_conversations}'") !== $table_conversations) {
                error_log('MPAI: Conversations table does not exist');
                
                // Try to create the table
                error_log('MPAI: Attempting to create conversation tables via chat class');
                if (!$this->create_tables()) {
                    error_log('MPAI: Failed to create conversation tables');
                    return null;
                }
            }
            
            // Get the most recent conversation for this user
            $conversation_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT conversation_id FROM $table_conversations WHERE user_id = %d ORDER BY updated_at DESC LIMIT 1",
                    $user_id
                )
            );
            
            // If no conversation exists, create a new one
            if (empty($conversation_id)) {
                $conversation_id = wp_generate_uuid4();
                
                $result = $wpdb->insert(
                    $table_conversations,
                    array(
                        'user_id' => $user_id,
                        'conversation_id' => $conversation_id,
                    )
                );
                
                if ($result === false) {
                    error_log('MPAI: Failed to create new conversation: ' . $wpdb->last_error);
                    return null;
                }
                
                error_log('MPAI: Created new conversation with ID: ' . $conversation_id);
            } else {
                error_log('MPAI: Found existing conversation with ID: ' . $conversation_id);
            }
            
            return $conversation_id;
        } catch (Exception $e) {
            error_log('MPAI: Error getting conversation ID: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get system prompt with MemberPress context
     *
     * @return string System prompt
     */
    private function get_system_prompt() {
        // Get MemberPress data summary
        $memberpress_data = $this->memberpress_api->get_data_summary();
        
        $system_prompt = "You are an AI assistant for MemberPress, a WordPress membership plugin. ";
        $system_prompt .= "You have access to the following MemberPress data:\n\n";
        
        // Add memberships information
        $system_prompt .= "Memberships:\n";
        if (!empty($memberpress_data['memberships'])) {
            foreach ($memberpress_data['memberships'] as $membership) {
                $system_prompt .= "- {$membership['title']} (ID: {$membership['id']}, Price: {$membership['price']})\n";
            }
        } else {
            $system_prompt .= "- No memberships found\n";
        }
        
        // Add summary statistics
        $system_prompt .= "\nSummary:\n";
        $system_prompt .= "- Total Members: " . ($memberpress_data['total_members'] ?? 'Unknown') . "\n";
        $system_prompt .= "- Total Memberships: " . ($memberpress_data['total_memberships'] ?? 'Unknown') . "\n";
        $system_prompt .= "- Total Transactions: " . ($memberpress_data['transaction_count'] ?? 'Unknown') . "\n";
        $system_prompt .= "- Total Subscriptions: " . ($memberpress_data['subscription_count'] ?? 'Unknown') . "\n";
        
        $system_prompt .= "\nYour task is to provide helpful information about MemberPress and assist with managing membership data. ";
        $system_prompt .= "You can recommend WP-CLI commands where appropriate, and help with MemberPress API usage. ";
        $system_prompt .= "Keep your responses concise and focused on MemberPress functionality.";
        
        return $system_prompt;
    }

    /**
     * Process a user message
     *
     * @param string $message User message
     * @return array Response data
     */
    public function process_message($message) {
        try {
            error_log('MPAI: Processing chat message: ' . $message);
            
            // We don't need to verify tables exist here anymore since the main class handles it,
            // and tables are created during plugin activation. This simplifies the code flow.
            
            // Initialize conversation if empty
            if (empty($this->conversation)) {
                $this->conversation = array(
                    array('role' => 'system', 'content' => $this->get_system_prompt())
                );
            }
            
            // Add user message to conversation
            $this->conversation[] = array('role' => 'user', 'content' => $message);
            
            // Get response from OpenAI
            error_log('MPAI: Generating chat completion');
            $response = $this->openai->generate_chat_completion($this->conversation);
            
            if (is_wp_error($response)) {
                error_log('MPAI: OpenAI returned error: ' . $response->get_error_message());
                return array(
                    'success' => false,
                    'message' => 'AI Assistant Error: ' . $response->get_error_message(),
                );
            }
            
            // Add assistant response to conversation
            $this->conversation[] = array('role' => 'assistant', 'content' => $response);
            
            // Save conversation to database
            error_log('MPAI: Saving message to database');
            $this->save_message($message, $response);
            
            // Process any commands in the response
            $processed_response = $this->process_commands($response);
            
            return array(
                'success' => true,
                'message' => $processed_response,
                'raw_response' => $response,
            );
        } catch (Exception $e) {
            error_log('MPAI: Exception in process_message: ' . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error processing message: ' . $e->getMessage(),
            );
        }
    }
    
    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        
        try {
            error_log('MPAI: Creating database tables');
            
            $charset_collate = $wpdb->get_charset_collate();
            
            // Table for storing chat conversations
            $table_name = $wpdb->prefix . 'mpai_conversations';
            
            $sql = "CREATE TABLE $table_name (
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
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            
            // Enable error output for dbDelta
            $wpdb->show_errors();
            
            // Execute the SQL
            $result = dbDelta($sql);
            
            // Log the result
            error_log('MPAI: dbDelta result: ' . json_encode($result));
            
            // Check if tables were created
            $tables_created = array();
            $tables_created['conversations'] = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
            $tables_created['messages'] = $wpdb->get_var("SHOW TABLES LIKE '{$table_messages}'") === $table_messages;
            
            error_log('MPAI: Tables created status: ' . json_encode($tables_created));
            
            return true;
        } catch (Exception $e) {
            error_log('MPAI: Error creating tables: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Save message to database
     *
     * @param string $message User message
     * @param string $response Assistant response
     */
    private function save_message($message, $response) {
        global $wpdb;
        
        $conversation_id = $this->get_current_conversation_id();
        
        if (empty($conversation_id)) {
            return;
        }
        
        $table_messages = $wpdb->prefix . 'mpai_messages';
        
        $wpdb->insert(
            $table_messages,
            array(
                'conversation_id' => $conversation_id,
                'message' => $message,
                'response' => $response,
            )
        );
        
        // Update conversation timestamp
        $table_conversations = $wpdb->prefix . 'mpai_conversations';
        
        $wpdb->update(
            $table_conversations,
            array('updated_at' => current_time('mysql')),
            array('conversation_id' => $conversation_id)
        );
    }

    /**
     * Process commands in the response
     *
     * @param string $response Assistant response
     * @return string Processed response
     */
    private function process_commands($response) {
        // Check if CLI commands are enabled
        $enable_cli_commands = get_option('mpai_enable_cli_commands', false);
        
        if (!$enable_cli_commands) {
            return $response;
        }
        
        // Look for suggested WP-CLI commands in the response
        preg_match_all('/```sh\n(wp .*?)\n```/s', $response, $matches);
        
        if (empty($matches[1])) {
            return $response;
        }
        
        $processed_response = $response;
        
        foreach ($matches[1] as $command) {
            $command = trim($command);
            
            // Check if command is allowed
            if ($this->is_command_allowed($command)) {
                // Get context manager
                $context_manager = new MPAI_Context_Manager();
                
                // Run the command
                $result = $context_manager->run_command($command);
                
                // Replace the command block with command and result
                $command_block = "```sh\n{$command}\n```";
                $result_block = "```sh\n{$command}\n\n# Result:\n{$result}\n```";
                
                $processed_response = str_replace($command_block, $result_block, $processed_response);
            }
        }
        
        return $processed_response;
    }

    /**
     * Check if command is allowed
     *
     * @param string $command Command to check
     * @return bool Whether command is allowed
     */
    private function is_command_allowed($command) {
        $allowed_commands = get_option('mpai_allowed_cli_commands', array());
        
        if (empty($allowed_commands)) {
            return false;
        }
        
        foreach ($allowed_commands as $allowed_command) {
            if (strpos($command, $allowed_command) === 0) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Reset conversation
     *
     * @return bool Success status
     */
    public function reset_conversation() {
        global $wpdb;
        
        $user_id = get_current_user_id();
        $conversation_id = $this->get_current_conversation_id();
        
        if (empty($conversation_id)) {
            return false;
        }
        
        $table_messages = $wpdb->prefix . 'mpai_messages';
        $table_conversations = $wpdb->prefix . 'mpai_conversations';
        
        // Delete messages
        $wpdb->delete(
            $table_messages,
            array('conversation_id' => $conversation_id)
        );
        
        // Create new conversation
        $new_conversation_id = wp_generate_uuid4();
        
        $wpdb->update(
            $table_conversations,
            array(
                'conversation_id' => $new_conversation_id,
                'updated_at' => current_time('mysql'),
            ),
            array('conversation_id' => $conversation_id)
        );
        
        $this->conversation = array();
        
        return true;
    }
}