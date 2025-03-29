<?php
/**
 * Memory Manager Class
 *
 * Manages agent memory and user context.
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Memory Manager for MemberPress AI Assistant
 */
class MPAI_Memory_Manager {
    /**
     * Constructor
     */
    public function __construct() {
        // Create tables if they don't exist
        $this->maybe_create_tables();
    }
    
    /**
     * Maybe create necessary database tables
     */
    private function maybe_create_tables() {
        global $wpdb;
        
        $table_memory = $wpdb->prefix . 'mpai_agent_memory';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_memory}'") != $table_memory) {
            $this->create_tables();
        }
    }
    
    /**
     * Create database tables
     *
     * @return bool Success status
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $table_memory = $wpdb->prefix . 'mpai_agent_memory';
        
        $sql = "CREATE TABLE {$table_memory} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            memory_key varchar(100) NOT NULL,
            memory_value longtext NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_memory (user_id, memory_key)
        ) {$charset_collate};";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($sql);
        
        // Check if table was created
        return $wpdb->get_var("SHOW TABLES LIKE '{$table_memory}'") == $table_memory;
    }
    
    /**
     * Get user context
     *
     * @param int $user_id User ID
     * @return array User context
     */
    public function get_context($user_id) {
        global $wpdb;
        
        $table_memory = $wpdb->prefix . 'mpai_agent_memory';
        
        // Get all memory entries for the user
        $memory_entries = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT memory_key, memory_value FROM {$table_memory} WHERE user_id = %d",
                $user_id
            ),
            ARRAY_A
        );
        
        $context = [];
        
        if (!empty($memory_entries)) {
            foreach ($memory_entries as $entry) {
                $context[$entry['memory_key']] = json_decode($entry['memory_value'], true);
            }
        }
        
        return $context;
    }
    
    /**
     * Update user context
     *
     * @param int $user_id User ID
     * @param string $message User message
     * @param array $result Agent result
     * @return bool Success status
     */
    public function update_context($user_id, $message, $result) {
        global $wpdb;
        
        $table_memory = $wpdb->prefix . 'mpai_agent_memory';
        
        // Add/update conversation history
        $history = $this->get_conversation_history($user_id);
        
        // Add new conversation entries
        $history[] = [
            'role' => 'user',
            'content' => $message,
            'timestamp' => time()
        ];
        
        if (isset($result['message'])) {
            $history[] = [
                'role' => 'assistant',
                'content' => $result['message'],
                'timestamp' => time()
            ];
        }
        
        // Limit history size (keep last 20 exchanges)
        if (count($history) > 40) { // 20 exchanges = 40 messages (user + assistant)
            $history = array_slice($history, -40);
        }
        
        // Save conversation history
        $this->set_memory_value($user_id, 'conversation_history', $history);
        
        // Save agent context if provided
        if (isset($result['data']['context']) && is_array($result['data']['context'])) {
            foreach ($result['data']['context'] as $key => $value) {
                $this->set_memory_value($user_id, $key, $value);
            }
        }
        
        return true;
    }
    
    /**
     * Get conversation history
     *
     * @param int $user_id User ID
     * @return array Conversation history
     */
    private function get_conversation_history($user_id) {
        $context = $this->get_context($user_id);
        
        return isset($context['conversation_history']) ? $context['conversation_history'] : [];
    }
    
    /**
     * Set memory value
     *
     * @param int $user_id User ID
     * @param string $key Memory key
     * @param mixed $value Memory value
     * @return bool Success status
     */
    public function set_memory_value($user_id, $key, $value) {
        global $wpdb;
        
        $table_memory = $wpdb->prefix . 'mpai_agent_memory';
        
        // Encode value as JSON
        $encoded_value = json_encode($value);
        
        // Check if entry exists
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table_memory} WHERE user_id = %d AND memory_key = %s",
                $user_id,
                $key
            )
        );
        
        if ($exists) {
            // Update existing entry
            $result = $wpdb->update(
                $table_memory,
                ['memory_value' => $encoded_value],
                [
                    'user_id' => $user_id,
                    'memory_key' => $key
                ]
            );
        } else {
            // Insert new entry
            $result = $wpdb->insert(
                $table_memory,
                [
                    'user_id' => $user_id,
                    'memory_key' => $key,
                    'memory_value' => $encoded_value
                ]
            );
        }
        
        return $result !== false;
    }
    
    /**
     * Clear memory for a user
     *
     * @param int $user_id User ID
     * @return bool Success status
     */
    public function clear_memory($user_id) {
        global $wpdb;
        
        $table_memory = $wpdb->prefix . 'mpai_agent_memory';
        
        $result = $wpdb->delete(
            $table_memory,
            ['user_id' => $user_id]
        );
        
        return $result !== false;
    }
}