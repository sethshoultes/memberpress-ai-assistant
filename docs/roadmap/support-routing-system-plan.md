# MemberPress Support Routing System Plan

## Overview

This document outlines a plan to implement a tiered support routing system for MemberPress. The system will first leverage Docsbot as a documentation resource, then route to MemberPress official customer support when needed for account-specific issues, billing questions, or complex technical problems.

## Goals

1. Provide seamless knowledge access through automated documentation searching via Docsbot
2. Maintain context across all support tiers (AI Assistant → Docsbot → Human Support)
3. Reduce support burden by only escalating genuine needs to human agents
4. Track support interactions to improve AI capabilities over time
5. Provide clear expectations to users about support response times
6. Create a smooth user experience across all support channels

## System Components

### 1. Docsbot Integration (Tier 1)

**Purpose:** Integrate with Docsbot's Chat Agent API to provide documentation-based answers before escalating to human support

**Implementation:**
- Create a Docsbot connector to interact with their Chat Agent API
- Implement conversation history tracking for contextual queries
- Provide access to MemberPress documentation, knowledge base, and guides
- Analyze response quality to determine if further escalation is needed

```php
class MPAI_Docsbot_Connector {
    private $team_id;
    private $bot_id;
    private $api_endpoint;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->team_id = get_option('mpai_docsbot_team_id', '');
        $this->bot_id = get_option('mpai_docsbot_bot_id', '');
        $this->api_endpoint = "https://api.docsbot.ai/teams/{$this->team_id}/bots/{$this->bot_id}/chat-agent";
    }
    
    /**
     * Send a query to Docsbot Chat Agent
     *
     * @param string $question User's question
     * @param string $conversation_id Unique conversation ID
     * @param array $options Additional options
     * @return array|WP_Error Response from Docsbot or error
     */
    public function query($question, $conversation_id = null, $options = []) {
        if (empty($this->team_id) || empty($this->bot_id)) {
            return new WP_Error('missing_config', 'Docsbot configuration is incomplete');
        }
        
        // Generate conversation ID if not provided
        if (empty($conversation_id)) {
            $conversation_id = wp_generate_uuid4();
        }
        
        // Build request payload
        $payload = [
            'conversationId' => $conversation_id,
            'question' => $question,
            'stream' => isset($options['stream']) ? (bool)$options['stream'] : false,
            'tools' => [
                'human_escalation' => isset($options['enable_escalation']) ? (bool)$options['enable_escalation'] : true,
                'followup_rating' => isset($options['enable_feedback']) ? (bool)$options['enable_feedback'] : true,
                'document_retriever' => isset($options['retrieve_sources']) ? (bool)$options['retrieve_sources'] : true
            ]
        ];
        
        // Add metadata if available
        if (!empty($options['metadata'])) {
            $payload['metadata'] = $options['metadata'];
        }
        
        // Make API request
        $response = wp_remote_post(
            $this->api_endpoint,
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . get_option('mpai_docsbot_api_key', '')
                ],
                'body' => json_encode($payload),
                'timeout' => 30
            ]
        );
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            return new WP_Error(
                'docsbot_api_error',
                'Error connecting to Docsbot: ' . wp_remote_retrieve_response_message($response),
                ['status' => $status_code]
            );
        }
        
        return json_decode(wp_remote_retrieve_body($response), true);
    }
    
    /**
     * Determine if Docsbot response requires human escalation
     *
     * @param array $response Docsbot response
     * @return bool True if human support is needed
     */
    public function needs_human_support($response) {
        if (empty($response) || is_wp_error($response)) {
            return true; // Escalate on error
        }
        
        // Check for explicit support_escalation in response
        foreach ($response as $item) {
            if (isset($item['type']) && $item['type'] === 'support_escalation') {
                return true;
            }
        }
        
        // Check confidence level if available
        foreach ($response as $item) {
            if (isset($item['type']) && $item['type'] === 'lookup_answer') {
                // If confidence data is available and below threshold
                if (isset($item['confidence']) && $item['confidence'] < 0.7) {
                    return true;
                }
                
                // If no relevant sources found
                if (isset($item['sources']) && empty($item['sources'])) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Handle streaming response from Docsbot
     * 
     * @param string $question User's question
     * @param string $conversation_id Conversation ID
     * @param callable $callback Function to call with each chunk
     * @return bool Success status
     */
    public function stream_query($question, $conversation_id, $callback) {
        // Implementation for streaming response handling
        // This would use a non-blocking HTTP client to handle chunks
        // For WordPress, we might need a custom implementation or use a library
        
        // Example implementation placeholder
        return true;
    }
}
```

### 2. Support Detection System (Tier 2)

**Purpose:** Identify when a conversation should be routed to human support after Docsbot has been tried

**Implementation:**
- Create a specialized detection agent that analyzes Docsbot responses and conversation context
- Implement criteria for support routing:
  - Docsbot unable to provide satisfactory answer
  - Explicit user requests for human support
  - Account-specific issues requiring admin access
  - Licensing or purchase-related questions
  - Multiple failed attempts to solve the same problem

```php
class MPAI_Support_Detector {
    /**
     * Check if a conversation should be routed to human support
     *
     * @param array $conversation The conversation history
     * @param array $docsbot_response The latest response from Docsbot
     * @return bool|array False if no support needed, or array with reason if support needed
     */
    public function should_route_to_support($conversation, $docsbot_response = null) {
        // First check if Docsbot indicated need for human support
        if (!empty($docsbot_response)) {
            $docsbot = new MPAI_Docsbot_Connector();
            if ($docsbot->needs_human_support($docsbot_response)) {
                return [
                    'reason' => 'docsbot_escalation',
                    'confidence' => 0.9
                ];
            }
        }
        
        // Check for explicit support requests
        if ($this->contains_support_request($conversation)) {
            return [
                'reason' => 'explicit_request',
                'confidence' => 0.9
            ];
        }
        
        // Check for repeated failed attempts
        if ($this->detect_repeated_issues($conversation)) {
            return [
                'reason' => 'repeated_issues',
                'confidence' => 0.8
            ];
        }
        
        // Check for complex technical issues
        if ($this->detect_complex_issue($conversation)) {
            return [
                'reason' => 'complex_issue',
                'confidence' => 0.7
            ];
        }
        
        // Check for account-specific issues
        if ($this->detect_account_issue($conversation)) {
            return [
                'reason' => 'account_specific',
                'confidence' => 0.8
            ];
        }
        
        return false;
    }
    
    // Implementation of individual detection methods
    // ...
}
```

### 3. Support Integration Module (Tier 3)

**Purpose:** Handle the actual integration with MemberPress's support system

**Implementation:**
- Create a secure API connector to MemberPress support platform
- Implement OAuth or API key-based authentication
- Support two integration methods:
  1. Direct ticket creation in MemberPress support system
  2. Guided handoff to support form with pre-filled information
- Include conversation history from both AI Assistant and Docsbot

```php
class MPAI_Support_Connector {
    private $api_key;
    private $api_endpoint;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->api_key = get_option('mpai_support_api_key', '');
        $this->api_endpoint = 'https://memberpress.com/api/support/v1/tickets';
    }
    
    /**
     * Create a support ticket
     *
     * @param array $ticket_data Ticket information
     * @param array $conversation_history Full conversation history
     * @param array $docsbot_interactions Docsbot interaction history
     * @return array|WP_Error Result of ticket creation
     */
    public function create_ticket($ticket_data, $conversation_history = [], $docsbot_interactions = []) {
        // Validate required fields
        if (empty($ticket_data['subject']) || empty($ticket_data['message'])) {
            return new WP_Error('missing_fields', 'Subject and message are required');
        }
        
        // Add system information
        $ticket_data['system_info'] = $this->get_system_info();
        
        // Add conversation history
        if (!empty($conversation_history)) {
            $ticket_data['ai_conversation'] = $this->format_conversation_history($conversation_history);
        }
        
        // Add Docsbot interactions
        if (!empty($docsbot_interactions)) {
            $ticket_data['docsbot_interactions'] = $this->format_docsbot_interactions($docsbot_interactions);
        }
        
        // Make API request
        $response = wp_remote_post(
            $this->api_endpoint,
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->api_key,
                    'Content-Type' => 'application/json'
                ],
                'body' => json_encode($ticket_data),
                'timeout' => 30
            ]
        );
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code !== 201) {
            return new WP_Error(
                'api_error',
                isset($body['message']) ? $body['message'] : 'Unknown API error',
                ['status' => $status_code]
            );
        }
        
        return $body;
    }
    
    /**
     * Generate a pre-filled support form URL
     *
     * @param array $data Form data to pre-fill
     * @param array $conversation_history Full conversation history
     * @param array $docsbot_interactions Docsbot interaction history
     * @return string Support form URL
     */
    public function get_support_form_url($data, $conversation_history = [], $docsbot_interactions = []) {
        $base_url = 'https://memberpress.com/support/';
        
        // Include AI conversation summary
        if (!empty($conversation_history)) {
            $data['ai_conversation'] = $this->format_conversation_history($conversation_history, true);
        }
        
        // Include Docsbot interaction summary
        if (!empty($docsbot_interactions)) {
            $data['docsbot_summary'] = $this->format_docsbot_interactions($docsbot_interactions, true);
        }
        
        $query_args = [
            'subject' => isset($data['subject']) ? urlencode($data['subject']) : '',
            'message' => isset($data['message']) ? urlencode($data['message']) : '',
            'email' => isset($data['email']) ? urlencode($data['email']) : '',
            'source' => 'mpai_assistant',
            'conv_id' => isset($data['conversation_id']) ? $data['conversation_id'] : '',
            'support_path' => 'ai_docsbot'
        ];
        
        return add_query_arg($query_args, $base_url);
    }
    
    /**
     * Format conversation history for inclusion in ticket
     * 
     * @param array $history Conversation history
     * @param bool $summarize Whether to summarize for URL parameters
     * @return string Formatted conversation history
     */
    private function format_conversation_history($history, $summarize = false) {
        // Implementation to format conversation history
        // ...
    }
    
    /**
     * Format Docsbot interactions for inclusion in ticket
     * 
     * @param array $interactions Docsbot interactions
     * @param bool $summarize Whether to summarize for URL parameters
     * @return string Formatted Docsbot interactions
     */
    private function format_docsbot_interactions($interactions, $summarize = false) {
        // Implementation to format Docsbot interactions
        // ...
    }
    
    /**
     * Get system information for the ticket
     *
     * @return array System information
     */
    private function get_system_info() {
        // Get system info from Site Health if available
        if (class_exists('MPAI_Site_Health')) {
            $site_health = new MPAI_Site_Health();
            return $site_health->get_complete_info();
        }
        
        // Fallback to basic info
        return [
            'wordpress_version' => get_bloginfo('version'),
            'mepr_version' => defined('MEPR_VERSION') ? MEPR_VERSION : 'Unknown',
            'mpai_version' => defined('MPAI_VERSION') ? MPAI_VERSION : 'Unknown',
            'php_version' => phpversion()
        ];
    }
}
```

### 4. User Interface Components

**Purpose:** Provide a seamless interface for support escalation across all tiers

**Implementation:**
- Update the chat interface with clear transitions between AI, Docsbot, and human support
- Implement a staged support transition dialog:
  1. "Check Documentation" button to query Docsbot
  2. "Get Human Support" button when Docsbot cannot resolve the issue
- Design indicators for which support tier is currently active
- Provide conversation history continuity between tiers

```php
// Add to class-mpai-chat-interface.php
public function render_support_transition_dialog() {
    ?>
    <div id="mpai-support-dialog" class="mpai-modal" style="display: none;">
        <div class="mpai-modal-content">
            <span class="mpai-modal-close">&times;</span>
            <h2>Support Options</h2>
            
            <div class="mpai-support-tiers">
                <div class="mpai-tier mpai-tier-docsbot">
                    <h3>Check MemberPress Documentation</h3>
                    <p>Let Docsbot search our knowledge base for answers to your question.</p>
                    <button id="mpai-check-docs" class="button button-primary">Check Documentation</button>
                    <div id="mpai-docsbot-results" style="display: none;"></div>
                </div>
                
                <div class="mpai-tier mpai-tier-human" style="display: none;">
                    <h3>Connect with MemberPress Support</h3>
                    <p>It looks like you might need assistance from our support team.</p>
                    
                    <div class="mpai-support-options">
                        <div class="mpai-support-option">
                            <h4>Create Support Ticket</h4>
                            <p>Send your conversation directly to MemberPress Support.</p>
                            <form id="mpai-direct-support-form">
                                <input type="email" id="mpai-support-email" placeholder="Your email address" required>
                                <button type="submit" class="button button-primary">Create Ticket</button>
                            </form>
                        </div>
                        
                        <div class="mpai-support-separator">OR</div>
                        
                        <div class="mpai-support-option">
                            <h4>Go to Support Portal</h4>
                            <p>Visit the MemberPress support portal with your conversation details.</p>
                            <button id="mpai-goto-support" class="button">Open Support Portal</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mpai-support-conversation-summary">
                <h3>Conversation Summary</h3>
                <div id="mpai-conversation-summary"></div>
            </div>
        </div>
    </div>
    <?php
}
```

### 5. Agent Routing Tools

**Purpose:** Allow the AI to directly initiate the appropriate support routing tier

**Implementation:**
- Create a new set of tools for the AI to use:
  1. `query_documentation`: Tool to check Docsbot when the AI needs documentation help
  2. `route_to_support`: Tool to escalate to human support when needed
- Allow the AI to analyze Docsbot responses and determine next steps
- Implement feedback collection at each routing stage

```php
// Add to context manager
private function get_documentation_tool_definition() {
    return [
        'name' => 'query_documentation',
        'description' => 'Query the MemberPress documentation using Docsbot to find answers from official docs',
        'parameters' => [
            'type' => 'object',
            'properties' => [
                'query' => [
                    'type' => 'string',
                    'description' => 'The documentation query to send to Docsbot'
                ],
                'conversation_id' => [
                    'type' => 'string',
                    'description' => 'The unique conversation ID (optional)'
                ]
            ],
            'required' => ['query']
        ]
    ];
}

// Add to context manager
private function get_support_routing_tool_definition() {
    return [
        'name' => 'route_to_support',
        'description' => 'Route the current conversation to MemberPress human support when documentation cannot resolve the issue',
        'parameters' => [
            'type' => 'object',
            'properties' => [
                'reason' => [
                    'type' => 'string',
                    'enum' => [
                        'docs_insufficient', 
                        'account_specific',
                        'repeated_attempts',
                        'user_requested',
                        'licensing_issue'
                    ],
                    'description' => 'The reason for routing to support'
                ],
                'summary' => [
                    'type' => 'string',
                    'description' => 'A brief summary of the issue for support staff'
                ],
                'priority' => [
                    'type' => 'string',
                    'enum' => ['low', 'medium', 'high'],
                    'default' => 'medium',
                    'description' => 'The priority level of the issue'
                ],
                'docsbot_conversation_id' => [
                    'type' => 'string',
                    'description' => 'The Docsbot conversation ID if available'
                ]
            ],
            'required' => ['reason', 'summary']
        ]
    ];
}

// Add to execution handler
public function execute_query_documentation($parameters) {
    // Validate parameters
    if (!isset($parameters['query'])) {
        return [
            'success' => false,
            'message' => 'Missing required query parameter'
        ];
    }
    
    // Initialize Docsbot connector
    $docsbot = new MPAI_Docsbot_Connector();
    
    // Get conversation ID or generate new one
    $conversation_id = isset($parameters['conversation_id']) ? $parameters['conversation_id'] : wp_generate_uuid4();
    
    // Query Docsbot
    $response = $docsbot->query($parameters['query'], $conversation_id);
    
    if (is_wp_error($response)) {
        return [
            'success' => false,
            'message' => $response->get_error_message()
        ];
    }
    
    // Store Docsbot interaction in the conversation
    $this->store_docsbot_interaction($conversation_id, $parameters['query'], $response);
    
    // Process response
    $formatted_response = $this->format_docsbot_response($response);
    
    return [
        'success' => true,
        'message' => 'Documentation query successful',
        'conversation_id' => $conversation_id,
        'response' => $formatted_response,
        'needs_human_support' => $docsbot->needs_human_support($response)
    ];
}

// Add to execution handler
public function execute_route_to_support($parameters) {
    // Validate parameters
    if (!isset($parameters['reason']) || !isset($parameters['summary'])) {
        return [
            'success' => false,
            'message' => 'Missing required parameters'
        ];
    }
    
    // Get current conversation
    $conversation_id = isset($this->chat_instance) ? $this->chat_instance->get_conversation_id() : 0;
    
    // Get Docsbot conversation if available
    $docsbot_conversation_id = isset($parameters['docsbot_conversation_id']) ? $parameters['docsbot_conversation_id'] : null;
    
    // Log the support routing attempt
    $this->log_support_routing([
        'conversation_id' => $conversation_id,
        'docsbot_conversation_id' => $docsbot_conversation_id,
        'reason' => $parameters['reason'],
        'summary' => $parameters['summary'],
        'priority' => isset($parameters['priority']) ? $parameters['priority'] : 'medium'
    ]);
    
    // Return success to trigger UI support dialog
    return [
        'success' => true,
        'message' => 'Support routing initiated',
        'conversation_id' => $conversation_id,
        'support_options' => [
            'direct_ticket' => $this->has_support_api_key(),
            'portal_url' => 'https://memberpress.com/support/'
        ]
    ];
}
```

### 6. Analytics and Feedback System

**Purpose:** Track all support interactions across tiers and improve AI capabilities

**Implementation:**
- Create database tables for tracking all support stages:
  1. AI Assistant interactions
  2. Docsbot queries and responses
  3. Human support escalations
- Implement analytics dashboard with support routing patterns across tiers
- Add feedback collection after each tier of interaction

```php
class MPAI_Support_Analytics {
    /**
     * Log a Docsbot interaction
     *
     * @param array $data Interaction data
     * @return int|false The ID of the logged interaction, or false on failure
     */
    public function log_docsbot_interaction($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'mpai_docsbot_interactions';
        
        $result = $wpdb->insert(
            $table,
            [
                'conversation_id' => isset($data['conversation_id']) ? sanitize_text_field($data['conversation_id']) : '',
                'docsbot_conversation_id' => isset($data['docsbot_conversation_id']) ? sanitize_text_field($data['docsbot_conversation_id']) : '',
                'user_id' => get_current_user_id(),
                'query' => isset($data['query']) ? sanitize_textarea_field($data['query']) : '',
                'was_helpful' => isset($data['was_helpful']) ? intval($data['was_helpful']) : null,
                'escalated_to_human' => isset($data['escalated_to_human']) ? intval($data['escalated_to_human']) : 0,
                'timestamp' => current_time('mysql')
            ]
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Log a support routing event
     *
     * @param array $data Event data
     * @return int|false The ID of the logged event, or false on failure
     */
    public function log_routing_event($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'mpai_support_routings';
        
        $result = $wpdb->insert(
            $table,
            [
                'conversation_id' => isset($data['conversation_id']) ? sanitize_text_field($data['conversation_id']) : '',
                'docsbot_conversation_id' => isset($data['docsbot_conversation_id']) ? sanitize_text_field($data['docsbot_conversation_id']) : '',
                'user_id' => get_current_user_id(),
                'reason' => isset($data['reason']) ? sanitize_text_field($data['reason']) : '',
                'summary' => isset($data['summary']) ? sanitize_textarea_field($data['summary']) : '',
                'priority' => isset($data['priority']) ? sanitize_text_field($data['priority']) : 'medium',
                'ticket_id' => isset($data['ticket_id']) ? sanitize_text_field($data['ticket_id']) : '',
                'method' => isset($data['method']) ? sanitize_text_field($data['method']) : 'direct',
                'timestamp' => current_time('mysql')
            ]
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Get support routing statistics
     *
     * @param array $filters Optional filters
     * @return array Statistics
     */
    public function get_support_stats($filters = []) {
        global $wpdb;
        
        // Docsbot statistics
        $docsbot_table = $wpdb->prefix . 'mpai_docsbot_interactions';
        $docsbot_stats = $wpdb->get_row(
            "SELECT 
                COUNT(*) as total_queries,
                SUM(CASE WHEN was_helpful = 1 THEN 1 ELSE 0 END) as helpful_responses,
                SUM(CASE WHEN was_helpful = 0 THEN 1 ELSE 0 END) as unhelpful_responses,
                SUM(CASE WHEN escalated_to_human = 1 THEN 1 ELSE 0 END) as escalated_queries
             FROM $docsbot_table",
            ARRAY_A
        );
        
        // Human support statistics
        $support_table = $wpdb->prefix . 'mpai_support_routings';
        
        // Count by reason
        $reason_stats = $wpdb->get_results(
            "SELECT reason, COUNT(*) as count 
             FROM $support_table 
             GROUP BY reason 
             ORDER BY count DESC"
        );
        
        // Count by day
        $date_stats = $wpdb->get_results(
            "SELECT DATE(timestamp) as date, COUNT(*) as count 
             FROM $support_table 
             GROUP BY DATE(timestamp) 
             ORDER BY date DESC 
             LIMIT 30"
        );
        
        // Get total count
        $total_count = $wpdb->get_var("SELECT COUNT(*) FROM $support_table");
        
        return [
            'docsbot' => $docsbot_stats,
            'human_support' => [
                'total' => $total_count,
                'by_reason' => $reason_stats,
                'by_date' => $date_stats
            ]
        ];
    }
    
    /**
     * Log user feedback after Docsbot interaction
     *
     * @param array $data Feedback data
     * @return int|false The ID of the feedback entry, or false on failure
     */
    public function log_docsbot_feedback($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'mpai_docsbot_interactions';
        
        // Update existing interaction with feedback
        $result = $wpdb->update(
            $table,
            [
                'was_helpful' => isset($data['was_helpful']) ? intval($data['was_helpful']) : null,
                'feedback_comments' => isset($data['comments']) ? sanitize_textarea_field($data['comments']) : ''
            ],
            [
                'id' => intval($data['interaction_id'])
            ]
        );
        
        return $result !== false;
    }
    
    /**
     * Log user feedback after support routing
     *
     * @param array $data Feedback data
     * @return int|false The ID of the feedback entry, or false on failure
     */
    public function log_support_feedback($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'mpai_support_feedback';
        
        $result = $wpdb->insert(
            $table,
            [
                'routing_id' => isset($data['routing_id']) ? intval($data['routing_id']) : 0,
                'rating' => isset($data['rating']) ? intval($data['rating']) : 0,
                'comments' => isset($data['comments']) ? sanitize_textarea_field($data['comments']) : '',
                'resolved' => isset($data['resolved']) ? intval($data['resolved']) : 0,
                'timestamp' => current_time('mysql')
            ]
        );
        
        return $result ? $wpdb->insert_id : false;
    }
}
```

## Database Schema

Create necessary tables to track all support interactions:

```sql
-- Docsbot interactions
CREATE TABLE {$wpdb->prefix}mpai_docsbot_interactions (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    conversation_id VARCHAR(50) NOT NULL,
    docsbot_conversation_id VARCHAR(50) DEFAULT NULL,
    user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
    query TEXT NOT NULL,
    was_helpful TINYINT(1) NULL DEFAULT NULL,
    feedback_comments TEXT DEFAULT NULL,
    escalated_to_human TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    timestamp DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY conversation_id (conversation_id),
    KEY user_id (user_id),
    KEY timestamp (timestamp)
) {$charset_collate};

-- Human support routings
CREATE TABLE {$wpdb->prefix}mpai_support_routings (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    conversation_id VARCHAR(50) NOT NULL,
    docsbot_conversation_id VARCHAR(50) DEFAULT NULL,
    user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
    reason VARCHAR(50) NOT NULL,
    summary TEXT NOT NULL,
    priority VARCHAR(20) NOT NULL DEFAULT 'medium',
    ticket_id VARCHAR(50) DEFAULT NULL,
    method VARCHAR(20) NOT NULL DEFAULT 'direct',
    timestamp DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY conversation_id (conversation_id),
    KEY user_id (user_id),
    KEY reason (reason),
    KEY timestamp (timestamp)
) {$charset_collate};

-- Support feedback
CREATE TABLE {$wpdb->prefix}mpai_support_feedback (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    routing_id BIGINT(20) UNSIGNED NOT NULL,
    rating TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    comments TEXT DEFAULT NULL,
    resolved TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    timestamp DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY routing_id (routing_id)
) {$charset_collate};
```

## Settings Integration

Add tiered support routing settings to the admin interface:

```php
// Add to settings page
public function render_support_settings() {
    ?>
    <h2><?php _e('Support Routing System', 'memberpress-ai-assistant'); ?></h2>
    
    <h3><?php _e('Docsbot Integration', 'memberpress-ai-assistant'); ?></h3>
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="mpai_enable_docsbot"><?php _e('Enable Docsbot Integration', 'memberpress-ai-assistant'); ?></label>
            </th>
            <td>
                <label>
                    <input type="checkbox" name="mpai_enable_docsbot" id="mpai_enable_docsbot" value="1" <?php checked(get_option('mpai_enable_docsbot', '1')); ?> />
                    <?php _e('Use Docsbot to search MemberPress documentation before escalating to human support', 'memberpress-ai-assistant'); ?>
                </label>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="mpai_docsbot_team_id"><?php _e('Docsbot Team ID', 'memberpress-ai-assistant'); ?></label>
            </th>
            <td>
                <input type="text" name="mpai_docsbot_team_id" id="mpai_docsbot_team_id" value="<?php echo esc_attr(get_option('mpai_docsbot_team_id', '')); ?>" class="regular-text" />
                <p class="description">
                    <?php _e('Your Docsbot team ID from the Docsbot dashboard', 'memberpress-ai-assistant'); ?>
                </p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="mpai_docsbot_bot_id"><?php _e('Docsbot Bot ID', 'memberpress-ai-assistant'); ?></label>
            </th>
            <td>
                <input type="text" name="mpai_docsbot_bot_id" id="mpai_docsbot_bot_id" value="<?php echo esc_attr(get_option('mpai_docsbot_bot_id', '')); ?>" class="regular-text" />
                <p class="description">
                    <?php _e('Your Docsbot bot ID from the Docsbot dashboard', 'memberpress-ai-assistant'); ?>
                </p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="mpai_docsbot_api_key"><?php _e('Docsbot API Key', 'memberpress-ai-assistant'); ?></label>
            </th>
            <td>
                <input type="password" name="mpai_docsbot_api_key" id="mpai_docsbot_api_key" value="<?php echo esc_attr(get_option('mpai_docsbot_api_key', '')); ?>" class="regular-text" />
                <p class="description">
                    <?php _e('Your Docsbot API key for authentication', 'memberpress-ai-assistant'); ?>
                </p>
            </td>
        </tr>
    </table>
    
    <h3><?php _e('Human Support Routing', 'memberpress-ai-assistant'); ?></h3>
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="mpai_enable_support_routing"><?php _e('Enable Human Support Routing', 'memberpress-ai-assistant'); ?></label>
            </th>
            <td>
                <label>
                    <input type="checkbox" name="mpai_enable_support_routing" id="mpai_enable_support_routing" value="1" <?php checked(get_option('mpai_enable_support_routing', '1')); ?> />
                    <?php _e('Allow escalation to human support when Docsbot cannot resolve the issue', 'memberpress-ai-assistant'); ?>
                </label>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="mpai_support_api_key"><?php _e('Support API Key', 'memberpress-ai-assistant'); ?></label>
            </th>
            <td>
                <input type="password" name="mpai_support_api_key" id="mpai_support_api_key" value="<?php echo esc_attr(get_option('mpai_support_api_key', '')); ?>" class="regular-text" />
                <p class="description">
                    <?php _e('API key for direct ticket creation in MemberPress Support system. Leave empty to use the portal redirect method.', 'memberpress-ai-assistant'); ?>
                </p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="mpai_support_auto_detection"><?php _e('Automatic Detection', 'memberpress-ai-assistant'); ?></label>
            </th>
            <td>
                <label>
                    <input type="checkbox" name="mpai_support_auto_detection" id="mpai_support_auto_detection" value="1" <?php checked(get_option('mpai_support_auto_detection', '1')); ?> />
                    <?php _e('Automatically detect when a conversation should be routed to human support', 'memberpress-ai-assistant'); ?>
                </label>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="mpai_support_default_email"><?php _e('Default Support Email', 'memberpress-ai-assistant'); ?></label>
            </th>
            <td>
                <input type="email" name="mpai_support_default_email" id="mpai_support_default_email" value="<?php echo esc_attr(get_option('mpai_support_default_email', '')); ?>" class="regular-text" />
                <p class="description">
                    <?php _e('Default email to use for support tickets (optional).', 'memberpress-ai-assistant'); ?>
                </p>
            </td>
        </tr>
    </table>
    <?php
}
```

## AI Agent Prompt Updates

Update the AI agent prompts to include knowledge about the tiered support routing system:

```
# Support Routing Capabilities

You now have access to a tiered support system with the following capabilities:

## Tier 1: Documentation Search

You can search the MemberPress documentation using Docsbot by using the query_documentation tool:

```json
{
  "tool": "query_documentation",
  "parameters": {
    "query": "How do I set up recurring subscriptions in MemberPress?",
    "conversation_id": "optional-conversation-id"
  }
}
```

Use this tool when:
1. The user has a question that might be answered in the documentation
2. You need to confirm specific MemberPress features or functionality
3. You want to provide official documentation references to the user

## Tier 2: Human Support Escalation

When Docsbot documentation cannot resolve the issue, you can escalate to human support using the route_to_support tool:

```json
{
  "tool": "route_to_support",
  "parameters": {
    "reason": "docs_insufficient",
    "summary": "User is experiencing an issue with recurring subscriptions not renewing correctly. Documentation doesn't cover their specific error scenario.",
    "priority": "medium",
    "docsbot_conversation_id": "optional-docsbot-conversation-id"
  }
}
```

Escalate to human support when:
1. Docsbot cannot provide a satisfactory answer
2. The issue is specific to the user's account or license
3. The user explicitly asks to speak with support
4. The question involves billing, refunds, or account access
5. You've tried documentation but the user needs more specific help

When routing to support:
1. Always try Docsbot documentation search first (Tier 1)
2. Explain to the user that you're connecting them with MemberPress support
3. Provide a concise summary of their issue
4. Be honest about the limitations of automated help for their specific issue
5. Set expectations about response times from human support
```

## Implementation Phases

### Phase 1: Docsbot Integration (Tier 1)

1. Create the MPAI_Docsbot_Connector class
2. Implement Docsbot API communication
3. Add Docsbot integration settings
4. Create query_documentation tool for the AI
5. Implement response parsing and evaluation

### Phase 2: Support Detection System (Tier 2)

1. Create database tables for tracking Docsbot interactions
2. Implement UI for Docsbot responses in chat
3. Develop the support detector to analyze Docsbot results
4. Create analytics for Docsbot usage

### Phase 3: Human Support Integration (Tier 3)

1. Implement secure API connection to MemberPress support
2. Create UI for human support escalation
3. Implement the route_to_support tool for AI
4. Add conversation and context transfer to human support

### Phase 4: Analytics & Refinement

1. Create support routing analytics dashboard
2. Implement feedback collection system for all tiers
3. Create reporting across both Docsbot and human support
4. Refine the tiered approach based on usage patterns

## Success Metrics

1. Percentage of issues resolved by Docsbot without human intervention
2. Reduction in direct support tickets for issues covered in documentation
3. Improved context quality for human support staff
4. Reduced time to resolution across all support tiers
5. Positive user feedback on the tiered support experience

## Technical Requirements

1. Secure API communication with both Docsbot and MemberPress support
2. GDPR-compliant handling of user conversation data
3. Proper authentication for both Docsbot and support ticket creation
4. Efficient context transfer between tiers
5. Reliable detection of when to escalate from Docsbot to human support

## Risk Management

1. **Risk:** Docsbot API authentication failures
   **Mitigation:** Implement fallback to direct human support with clear error messaging

2. **Risk:** Over-reliance on Docsbot for issues requiring human help
   **Mitigation:** Clear escalation criteria and quick paths to human support when needed

3. **Risk:** Incomplete context when moving between tiers
   **Mitigation:** Comprehensive context passing between all systems

4. **Risk:** User privacy concerns with conversation sharing
   **Mitigation:** Clear consent flow, option to review shared information

5. **Risk:** Support system unavailability
   **Mitigation:** Graceful degradation with alternative support routes

## Conclusion

This tiered support routing system enhances the MemberPress AI Assistant by providing multiple levels of assistance:

1. AI Assistant provides immediate help for common questions
2. Docsbot integration adds comprehensive documentation search 
3. Human support escalation ensures all issues can be resolved

By implementing this tiered approach, we can ensure users get the appropriate level of help for their specific needs while optimizing support resources. The system is designed to integrate seamlessly with both Docsbot and MemberPress's existing support infrastructure while providing valuable analytics to continuously improve all support tiers.