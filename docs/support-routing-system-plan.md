# MemberPress Support Routing System Plan

## Overview

This document outlines a plan to implement an agent routing system that can connect users to MemberPress official customer support when the AI assistant cannot adequately address their questions or issues.

## Goals

1. Provide seamless escalation to human support when the AI reaches its limits
2. Maintain context from the AI conversation when transferring to support
3. Reduce support burden by only escalating genuine needs
4. Track escalations to improve AI capabilities over time
5. Provide clear expectations to users about support response times

## System Components

### 1. Support Detection System

**Purpose:** Identify when a conversation should be routed to human support

**Implementation:**
- Create a specialized detection agent that monitors conversations
- Implement criteria for support routing:
  - Multiple failed attempts to solve the same problem
  - Explicit user requests for human support
  - Complex technical issues beyond AI capabilities
  - Account-specific issues requiring admin access
  - Licensing or purchase-related questions

```php
class MPAI_Support_Detector {
    /**
     * Check if a conversation should be routed to support
     *
     * @param array $conversation The conversation history
     * @return bool|array False if no support needed, or array with reason if support needed
     */
    public function should_route_to_support($conversation) {
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

### 2. Support Integration Module

**Purpose:** Handle the actual integration with MemberPress's support system

**Implementation:**
- Create a secure API connector to MemberPress support platform
- Implement OAuth or API key-based authentication
- Support two integration methods:
  1. Direct ticket creation in MemberPress support system
  2. Guided handoff to support form with pre-filled information

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
     * @return array|WP_Error Result of ticket creation
     */
    public function create_ticket($ticket_data) {
        // Validate required fields
        if (empty($ticket_data['subject']) || empty($ticket_data['message'])) {
            return new WP_Error('missing_fields', 'Subject and message are required');
        }
        
        // Add system information
        $ticket_data['system_info'] = $this->get_system_info();
        
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
     * @return string Support form URL
     */
    public function get_support_form_url($data) {
        $base_url = 'https://memberpress.com/support/';
        $query_args = [
            'subject' => isset($data['subject']) ? urlencode($data['subject']) : '',
            'message' => isset($data['message']) ? urlencode($data['message']) : '',
            'email' => isset($data['email']) ? urlencode($data['email']) : '',
            'source' => 'mpai_assistant',
            'conv_id' => isset($data['conversation_id']) ? $data['conversation_id'] : ''
        ];
        
        return add_query_arg($query_args, $base_url);
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

### 3. User Interface Components

**Purpose:** Provide a seamless interface for support escalation

**Implementation:**
- Add a "Get Human Support" button to the chat interface
- Implement a support transition dialog with options:
  1. Create ticket directly (requires email)
  2. Open support portal in new tab
- Design a conversation summary generator for concise context

```php
// Add to class-mpai-chat-interface.php
public function render_support_transition_dialog() {
    ?>
    <div id="mpai-support-dialog" class="mpai-modal" style="display: none;">
        <div class="mpai-modal-content">
            <span class="mpai-modal-close">&times;</span>
            <h2>Connect with MemberPress Support</h2>
            
            <p>It looks like you might need assistance from the MemberPress support team.</p>
            
            <div class="mpai-support-options">
                <div class="mpai-support-option">
                    <h3>Create Support Ticket</h3>
                    <p>Send your conversation and question directly to MemberPress Support.</p>
                    <form id="mpai-direct-support-form">
                        <input type="email" id="mpai-support-email" placeholder="Your email address" required>
                        <button type="submit" class="button button-primary">Create Ticket</button>
                    </form>
                </div>
                
                <div class="mpai-support-separator">OR</div>
                
                <div class="mpai-support-option">
                    <h3>Go to Support Portal</h3>
                    <p>Visit the MemberPress support portal with your conversation details.</p>
                    <button id="mpai-goto-support" class="button">Open Support Portal</button>
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

### 4. Agent Routing Tool

**Purpose:** Allow the AI to directly initiate the support routing process

**Implementation:**
- Create a new tool definition for the AI
- Allow the AI to trigger support routing with context
- Implement feedback collection when routing to support

```php
// Add to context manager
private function get_support_routing_tool_definition() {
    return [
        'name' => 'route_to_support',
        'description' => 'Route the current conversation to MemberPress human support when AI cannot resolve the issue',
        'parameters' => [
            'type' => 'object',
            'properties' => [
                'reason' => [
                    'type' => 'string',
                    'enum' => [
                        'complex_issue', 
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
                ]
            ],
            'required' => ['reason', 'summary']
        ]
    ];
}

// Add to context manager
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
    
    // Log the support routing attempt
    $this->log_support_routing([
        'conversation_id' => $conversation_id,
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

### 5. Analytics and Feedback System

**Purpose:** Track support escalations and improve AI capabilities

**Implementation:**
- Create database tables for tracking support routing
- Implement analytics dashboard for support routing patterns
- Add feedback collection after support interaction

```php
class MPAI_Support_Analytics {
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
                'conversation_id' => isset($data['conversation_id']) ? intval($data['conversation_id']) : 0,
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
    public function get_routing_stats($filters = []) {
        global $wpdb;
        $table = $wpdb->prefix . 'mpai_support_routings';
        
        // Count by reason
        $reason_stats = $wpdb->get_results(
            "SELECT reason, COUNT(*) as count 
             FROM $table 
             GROUP BY reason 
             ORDER BY count DESC"
        );
        
        // Count by day
        $date_stats = $wpdb->get_results(
            "SELECT DATE(timestamp) as date, COUNT(*) as count 
             FROM $table 
             GROUP BY DATE(timestamp) 
             ORDER BY date DESC 
             LIMIT 30"
        );
        
        // Get total count
        $total_count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        
        return [
            'total' => $total_count,
            'by_reason' => $reason_stats,
            'by_date' => $date_stats
        ];
    }
    
    /**
     * Log user feedback after support routing
     *
     * @param array $data Feedback data
     * @return int|false The ID of the feedback entry, or false on failure
     */
    public function log_feedback($data) {
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

Create necessary tables to track support routing:

```sql
CREATE TABLE {$wpdb->prefix}mpai_support_routings (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    conversation_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
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

Add support routing settings to the admin interface:

```php
// Add to settings page
public function render_support_settings() {
    ?>
    <h3><?php _e('Support Routing', 'memberpress-ai-assistant'); ?></h3>
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="mpai_enable_support_routing"><?php _e('Enable Support Routing', 'memberpress-ai-assistant'); ?></label>
            </th>
            <td>
                <label>
                    <input type="checkbox" name="mpai_enable_support_routing" id="mpai_enable_support_routing" value="1" <?php checked(get_option('mpai_enable_support_routing', '1')); ?> />
                    <?php _e('Allow the AI to route conversations to MemberPress Support', 'memberpress-ai-assistant'); ?>
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
                    <?php _e('Automatically detect when a conversation should be routed to support', 'memberpress-ai-assistant'); ?>
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

Update the AI agent prompts to include knowledge about the support routing capability:

```
# Support Routing Capability

You now have the ability to route conversations to human MemberPress support staff when needed. Use this capability judiciously when:

1. The user has a complex technical issue you cannot solve
2. The issue is specific to their account or license
3. The user explicitly asks to speak with support
4. You've made multiple attempts to solve their issue without success
5. The question involves billing, refunds, or account access

To route to support, use the route_to_support tool:

```json
{
  "tool": "route_to_support",
  "parameters": {
    "reason": "complex_issue",
    "summary": "User is experiencing an issue with recurring subscriptions not renewing correctly. Database shows completed payments but access is not granted.",
    "priority": "medium"
  }
}
```

When routing to support:
1. Explain to the user that you're connecting them with MemberPress support
2. Provide a concise summary of their issue
3. Be honest about your limitations for their specific issue
4. Set expectations about response times from human support
```

## Implementation Phases

### Phase 1: Foundation

1. Create database tables for support routing and feedback
2. Implement UI components for support transition
3. Create base support detection and connector classes
4. Add settings for support routing configuration

### Phase 2: AI Integration

1. Implement the AI tool for support routing
2. Update AI prompts with support routing knowledge
3. Create the support routing execution handler
4. Implement conversation summary generator

### Phase 3: MemberPress API Integration

1. Implement secure API connection to MemberPress support
2. Add direct ticket creation functionality
3. Implement system information attachment to tickets
4. Add support for conversation history inclusion

### Phase 4: Analytics & Refinement

1. Create support routing analytics dashboard
2. Implement feedback collection after support routing
3. Create reporting for common support routing reasons
4. Refine detection algorithm based on feedback

## Success Metrics

1. Reduction in support tickets for issues the AI can handle
2. Increase in user satisfaction with support process
3. Improved context quality for support staff
4. Reduced time to resolution for escalated issues
5. Positive feedback on support routing experience

## Technical Requirements

1. Secure API communication with MemberPress support
2. GDPR-compliant handling of user conversation data
3. Proper authentication for support ticket creation
4. Efficient conversation summarization for support context
5. Reliable detection of support-worthy issues

## Risk Management

1. **Risk:** API authentication failures leading to unavailable support routing
   **Mitigation:** Implement fallback to support portal with pre-filled information

2. **Risk:** Over-routing of issues that AI could handle
   **Mitigation:** Regular analysis of routing patterns, refinement of detection criteria

3. **Risk:** Incomplete context when routing to support
   **Mitigation:** Comprehensive conversation summarization, system info inclusion

4. **Risk:** User privacy concerns with conversation sharing
   **Mitigation:** Clear consent flow, option to review shared information

5. **Risk:** Support system unavailability
   **Mitigation:** Queuing mechanism for failed routing attempts, status monitoring

## Conclusion

This support routing system will enhance the MemberPress AI Assistant by providing a seamless transition to human support when needed. By implementing intelligent detection, proper context sharing, and a smooth user experience, we can ensure users get the help they need while still leveraging the AI's capabilities for suitable issues.

The system is designed to integrate closely with MemberPress's existing support infrastructure while providing valuable analytics to continuously improve both the AI assistant and the support routing process.