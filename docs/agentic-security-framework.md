# Agentic Security Framework for MemberPress AI Assistant

## Overview

This document outlines a comprehensive security framework for the MemberPress AI Assistant, focusing on the agentic aspects of the system. As AI agents increasingly perform actions on behalf of users and interact with sensitive systems, proper security controls are essential to prevent misuse, protect data, and ensure compliance with best practices.

## Security Principles

1. **Least Privilege** - Agents should operate with minimal permissions required for their specific functions
2. **Multi-layer Validation** - Multiple validation checks at different layers of the system
3. **Command Whitelisting** - Explicit whitelisting of allowed operations and commands
4. **Audit Trail** - Comprehensive logging of all agent actions and decisions
5. **Sandboxing** - Isolation of agent execution environments
6. **User Confirmation** - Requiring explicit user confirmation for sensitive operations
7. **Rate Limiting** - Preventing abuse through appropriate rate limits
8. **Secure Communication** - Ensuring all communication is encrypted and authenticated

## Current Security Measures

The current implementation includes some security measures, but lacks a comprehensive agentic security model:

- **Command Validation Agent** - Validates commands before execution
- **WP-CLI Whitelist** - Limited set of approved WP-CLI commands
- **Admin Permission Checks** - Basic WordPress admin capability checks
- **Input Sanitization** - Standard WordPress input sanitization
- **Nonce Verification** - CSRF protection for requests

## Agentic Security Implementation Plan

### 1. Agent Authentication & Authorization Framework

**Purpose:** Establish a robust identity and permission model for agents

**Implementation:**
```php
class MPAI_Agent_Security {
    /**
     * Agent roles with associated permissions
     */
    private $agent_roles = [
        'content_agent' => [
            'capabilities' => ['create_posts', 'edit_posts', 'publish_posts'],
            'allowed_tools' => ['wp_api', 'openai', 'anthropic']
        ],
        'system_agent' => [
            'capabilities' => ['manage_options', 'install_plugins'],
            'allowed_tools' => ['wp_cli', 'diagnostic', 'system_info']
        ],
        'member_agent' => [
            'capabilities' => ['read_member_data', 'list_members'],
            'allowed_tools' => ['memberpress_info']
        ],
        'support_agent' => [
            'capabilities' => ['read_system_info', 'route_to_support'],
            'allowed_tools' => ['diagnostic', 'memberpress_info', 'route_to_support']
        ]
    ];
    
    /**
     * Check if an agent has a specific capability
     *
     * @param string $agent_id The agent identifier
     * @param string $capability The capability to check
     * @return bool Whether the agent has the capability
     */
    public function agent_can($agent_id, $capability) {
        $agent_type = $this->get_agent_type($agent_id);
        
        if (!$agent_type || !isset($this->agent_roles[$agent_type])) {
            return false;
        }
        
        return in_array($capability, $this->agent_roles[$agent_type]['capabilities']);
    }
    
    /**
     * Check if an agent can use a specific tool
     *
     * @param string $agent_id The agent identifier
     * @param string $tool The tool name
     * @return bool Whether the agent can use the tool
     */
    public function agent_can_use_tool($agent_id, $tool) {
        $agent_type = $this->get_agent_type($agent_id);
        
        if (!$agent_type || !isset($this->agent_roles[$agent_type])) {
            return false;
        }
        
        return in_array($tool, $this->agent_roles[$agent_type]['allowed_tools']);
    }
    
    /**
     * Get an agent's type based on its ID
     *
     * @param string $agent_id The agent identifier
     * @return string|false The agent type or false if not found
     */
    private function get_agent_type($agent_id) {
        // Implement agent type lookup logic
        // This might involve checking a registry or parsing the agent ID
        return false;
    }
}
```

### 2. Command Execution Sandbox

**Purpose:** Create isolated environments for command execution

**Implementation:**
```php
class MPAI_Command_Sandbox {
    /**
     * Execute a command in a sandboxed environment
     *
     * @param string $command The command to execute
     * @param array $parameters Command parameters
     * @param string $agent_id The agent identifier
     * @return array Command execution result
     */
    public function execute_sandboxed($command, $parameters, $agent_id) {
        // Verify agent permission
        $security = new MPAI_Agent_Security();
        if (!$security->agent_can_use_tool($agent_id, $command)) {
            return [
                'success' => false,
                'message' => 'Agent does not have permission to use this tool',
                'code' => 'permission_denied'
            ];
        }
        
        // Log the execution attempt
        $this->log_execution_attempt($command, $parameters, $agent_id);
        
        // Validate command parameters
        $validator = new MPAI_Command_Validator();
        $validation_result = $validator->validate($command, $parameters);
        
        if (!$validation_result['valid']) {
            return [
                'success' => false,
                'message' => 'Command validation failed: ' . $validation_result['reason'],
                'code' => 'validation_failed'
            ];
        }
        
        // Apply command-specific constraints
        $constraints = $this->get_command_constraints($command);
        $constrained_parameters = $this->apply_constraints($parameters, $constraints);
        
        // Execute in appropriate environment
        $runner = $this->get_command_runner($command);
        $result = $runner->run($constrained_parameters);
        
        // Log the execution result
        $this->log_execution_result($command, $parameters, $result, $agent_id);
        
        return $result;
    }
    
    /**
     * Get command-specific constraints
     *
     * @param string $command The command
     * @return array Constraints for the command
     */
    private function get_command_constraints($command) {
        $constraints = [
            'wp_cli' => [
                'allowed_commands' => [
                    'wp post list',
                    'wp user list',
                    'wp plugin list',
                    'wp option get',
                    // Other allowed WP-CLI commands
                ],
                'max_runtime' => 30, // seconds
                'resource_limits' => [
                    'memory' => '256M',
                    'processes' => 1
                ]
            ],
            'wp_api' => [
                'allowed_actions' => [
                    'create_post',
                    'update_post',
                    'get_posts',
                    'get_users',
                    // Other allowed API actions
                ],
                'max_items' => 100
            ],
            // Other command constraints
        ];
        
        return isset($constraints[$command]) ? $constraints[$command] : [];
    }
    
    /**
     * Apply constraints to parameters
     *
     * @param array $parameters Original parameters
     * @param array $constraints Constraints to apply
     * @return array Constrained parameters
     */
    private function apply_constraints($parameters, $constraints) {
        // Implement constraint application logic
        // This might involve filtering parameters, adding limits, etc.
        return $parameters;
    }
    
    /**
     * Log an execution attempt
     *
     * @param string $command The command
     * @param array $parameters Command parameters
     * @param string $agent_id The agent identifier
     */
    private function log_execution_attempt($command, $parameters, $agent_id) {
        // Implement logging logic
    }
    
    /**
     * Log an execution result
     *
     * @param string $command The command
     * @param array $parameters Command parameters
     * @param array $result Execution result
     * @param string $agent_id The agent identifier
     */
    private function log_execution_result($command, $parameters, $result, $agent_id) {
        // Implement logging logic
    }
    
    /**
     * Get the appropriate command runner
     *
     * @param string $command The command
     * @return object Command runner
     */
    private function get_command_runner($command) {
        // Implement runner selection logic
        return new stdClass();
    }
}
```

### 3. Advanced Command Validation System

**Purpose:** Enhance the existing command validation with deeper analysis

**Implementation:**
```php
class MPAI_Command_Validator {
    /**
     * Command patterns that need special validation
     */
    private $sensitive_patterns = [
        // File system access patterns
        'file_access' => [
            '/wp\s+media\s+import/',
            '/wp\s+import/',
            '/wp\s+export/',
            '/wp\s+db\s+export/',
            '/wp\s+db\s+import/'
        ],
        // User management patterns
        'user_management' => [
            '/wp\s+user\s+create/',
            '/wp\s+user\s+delete/',
            '/wp\s+user\s+add-role/',
            '/wp\s+user\s+set-role/'
        ],
        // Plugin management patterns
        'plugin_management' => [
            '/wp\s+plugin\s+install/',
            '/wp\s+plugin\s+activate/',
            '/wp\s+plugin\s+deactivate/',
            '/wp\s+plugin\s+delete/'
        ],
        // Settings patterns
        'settings_changes' => [
            '/wp\s+option\s+update/',
            '/wp\s+option\s+add/',
            '/wp\s+option\s+delete/'
        ]
    ];
    
    /**
     * Command whitelist
     */
    private $command_whitelist = [
        'wp_cli' => [
            // Standard info commands
            'wp',
            'wp help',
            'wp core version',
            'wp core verify-checksums',
            
            // Plugin management (restricted)
            'wp plugin list',
            'wp plugin status',
            
            // Content management
            'wp post list',
            'wp post get',
            'wp post meta list',
            
            // User management (restricted)
            'wp user list',
            'wp user get',
            
            // Option management (restricted)
            'wp option get',
            'wp option list',
            
            // MemberPress specific
            'wp mepr',
            'wp mepr subscriptions list',
            'wp mepr members list',
            'wp mepr transactions list'
        ],
        'wp_api' => [
            // Post operations
            'get_posts',
            'get_post',
            'create_post',
            'update_post',
            
            // User operations (restricted)
            'get_users',
            'get_user',
            
            // MemberPress operations
            'get_memberships',
            'get_membership',
            'get_transactions',
            'get_members'
        ]
    ];
    
    /**
     * Validate a command
     *
     * @param string $command_type The command type
     * @param array $parameters Command parameters
     * @return array Validation result
     */
    public function validate($command_type, $parameters) {
        // Check if command type is allowed
        if (!isset($this->command_whitelist[$command_type])) {
            return [
                'valid' => false,
                'reason' => 'Command type not allowed'
            ];
        }
        
        // For WP CLI, validate the command string
        if ($command_type === 'wp_cli' && isset($parameters['command'])) {
            return $this->validate_wp_cli_command($parameters['command']);
        }
        
        // For WP API, validate the action
        if ($command_type === 'wp_api' && isset($parameters['action'])) {
            return $this->validate_wp_api_action($parameters['action'], $parameters);
        }
        
        // For other command types
        return [
            'valid' => true
        ];
    }
    
    /**
     * Validate a WP-CLI command
     *
     * @param string $command The WP-CLI command
     * @return array Validation result
     */
    private function validate_wp_cli_command($command) {
        // Check command whitelist
        $allowed = false;
        foreach ($this->command_whitelist['wp_cli'] as $allowed_command) {
            if (strpos($command, $allowed_command) === 0) {
                $allowed = true;
                break;
            }
        }
        
        if (!$allowed) {
            return [
                'valid' => false,
                'reason' => 'Command not in whitelist'
            ];
        }
        
        // Check for sensitive patterns
        foreach ($this->sensitive_patterns as $category => $patterns) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $command)) {
                    return [
                        'valid' => false,
                        'reason' => "Command matches sensitive pattern: $category"
                    ];
                }
            }
        }
        
        // Additional command-specific validation
        if (strpos($command, 'wp option') === 0) {
            if (strpos($command, 'wp option get') !== 0 && 
                strpos($command, 'wp option list') !== 0) {
                return [
                    'valid' => false,
                    'reason' => 'Only get and list operations are allowed for options'
                ];
            }
        }
        
        return [
            'valid' => true
        ];
    }
    
    /**
     * Validate a WP API action
     *
     * @param string $action The action
     * @param array $parameters Action parameters
     * @return array Validation result
     */
    private function validate_wp_api_action($action, $parameters) {
        // Check action whitelist
        if (!in_array($action, $this->command_whitelist['wp_api'])) {
            return [
                'valid' => false,
                'reason' => 'API action not allowed'
            ];
        }
        
        // Additional action-specific validation
        if ($action === 'update_post' && isset($parameters['post_status'])) {
            $allowed_statuses = ['draft', 'pending', 'private', 'publish'];
            if (!in_array($parameters['post_status'], $allowed_statuses)) {
                return [
                    'valid' => false,
                    'reason' => 'Invalid post status'
                ];
            }
        }
        
        return [
            'valid' => true
        ];
    }
}
```

### 4. Comprehensive Audit System

**Purpose:** Track all agent actions in detail for security analysis and compliance

**Implementation:**
```php
class MPAI_Security_Audit {
    /**
     * Log an agent action
     *
     * @param array $data Action data
     * @return int|false The ID of the logged action, or false on failure
     */
    public function log_action($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'mpai_security_audit';
        
        // Sanitize and validate input
        $sanitized = [
            'agent_id' => isset($data['agent_id']) ? sanitize_text_field($data['agent_id']) : '',
            'action' => isset($data['action']) ? sanitize_text_field($data['action']) : '',
            'target' => isset($data['target']) ? sanitize_text_field($data['target']) : '',
            'params' => isset($data['params']) ? json_encode($data['params']) : '{}',
            'result' => isset($data['result']) ? sanitize_text_field($data['result']) : '',
            'status' => isset($data['status']) ? sanitize_text_field($data['status']) : 'unknown',
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'user_id' => get_current_user_id(),
            'timestamp' => current_time('mysql')
        ];
        
        $result = $wpdb->insert($table, $sanitized);
        
        if ($result) {
            // Log critical actions to WordPress error log
            if ($this->is_critical_action($data['action'])) {
                error_log(sprintf(
                    'MPAI CRITICAL ACTION: Agent %s performed %s on %s with result %s',
                    $sanitized['agent_id'],
                    $sanitized['action'],
                    $sanitized['target'],
                    $sanitized['result']
                ));
            }
            
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Check if an action is considered critical
     *
     * @param string $action The action
     * @return bool Whether the action is critical
     */
    private function is_critical_action($action) {
        $critical_actions = [
            'wp_cli_execute',
            'update_plugin',
            'activate_plugin',
            'deactivate_plugin',
            'update_option',
            'create_user',
            'update_user_role',
            'delete_post',
            'import_data',
            'export_data'
        ];
        
        return in_array($action, $critical_actions);
    }
    
    /**
     * Get audit logs with filtering
     *
     * @param array $filters Filters to apply
     * @param int $limit Number of logs to return
     * @param int $offset Offset for pagination
     * @return array Audit logs
     */
    public function get_logs($filters = [], $limit = 50, $offset = 0) {
        global $wpdb;
        $table = $wpdb->prefix . 'mpai_security_audit';
        
        $query = "SELECT * FROM $table WHERE 1=1";
        $query_args = [];
        
        // Apply filters
        if (!empty($filters['agent_id'])) {
            $query .= " AND agent_id = %s";
            $query_args[] = $filters['agent_id'];
        }
        
        if (!empty($filters['action'])) {
            $query .= " AND action = %s";
            $query_args[] = $filters['action'];
        }
        
        if (!empty($filters['status'])) {
            $query .= " AND status = %s";
            $query_args[] = $filters['status'];
        }
        
        if (!empty($filters['user_id'])) {
            $query .= " AND user_id = %d";
            $query_args[] = intval($filters['user_id']);
        }
        
        if (!empty($filters['date_start'])) {
            $query .= " AND timestamp >= %s";
            $query_args[] = $filters['date_start'];
        }
        
        if (!empty($filters['date_end'])) {
            $query .= " AND timestamp <= %s";
            $query_args[] = $filters['date_end'];
        }
        
        // Add ordering and limits
        $query .= " ORDER BY timestamp DESC LIMIT %d OFFSET %d";
        $query_args[] = intval($limit);
        $query_args[] = intval($offset);
        
        if (!empty($query_args)) {
            $query = $wpdb->prepare($query, $query_args);
        }
        
        return $wpdb->get_results($query, ARRAY_A);
    }
    
    /**
     * Generate a security report
     *
     * @param string $period Time period for the report
     * @return array Report data
     */
    public function generate_report($period = 'last_week') {
        // Implement reporting logic
        return [];
    }
}
```

### 5. User Approval System

**Purpose:** Require user confirmation for sensitive operations

**Implementation:**
```php
class MPAI_User_Approvals {
    /**
     * Actions that require approval
     */
    private $approval_required_actions = [
        'activate_plugin',
        'deactivate_plugin',
        'install_plugin',
        'update_core',
        'create_user',
        'update_user_role',
        'import_data',
        'export_data',
        'delete_content',
        'bulk_operations'
    ];
    
    /**
     * Check if an action requires user approval
     *
     * @param string $action The action
     * @param array $parameters Action parameters
     * @return bool Whether approval is required
     */
    public function requires_approval($action, $parameters) {
        // Check direct matches
        if (in_array($action, $this->approval_required_actions)) {
            return true;
        }
        
        // Check pattern-based matches
        if (strpos($action, 'wp_cli_execute') === 0) {
            $command = isset($parameters['command']) ? $parameters['command'] : '';
            
            // Check for plugin management commands
            if (preg_match('/wp\s+plugin\s+(install|activate|deactivate|delete)/', $command)) {
                return true;
            }
            
            // Check for user management commands
            if (preg_match('/wp\s+user\s+(create|delete|add-role|set-role)/', $command)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Create a pending approval
     *
     * @param string $action The action
     * @param array $parameters Action parameters
     * @param string $agent_id The agent identifier
     * @return int|false The approval ID, or false on failure
     */
    public function create_approval($action, $parameters, $agent_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'mpai_pending_approvals';
        
        $result = $wpdb->insert(
            $table,
            [
                'action' => sanitize_text_field($action),
                'parameters' => json_encode($parameters),
                'agent_id' => sanitize_text_field($agent_id),
                'user_id' => get_current_user_id(),
                'status' => 'pending',
                'created' => current_time('mysql'),
                'expires' => date('Y-m-d H:i:s', strtotime('+1 hour')),
                'approval_token' => wp_generate_password(32, false)
            ]
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Approve a pending action
     *
     * @param int $approval_id The approval ID
     * @param string $token The approval token
     * @return array Result of the approval
     */
    public function approve_action($approval_id, $token) {
        global $wpdb;
        $table = $wpdb->prefix . 'mpai_pending_approvals';
        
        // Get the pending approval
        $approval = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE id = %d AND status = 'pending'", $approval_id),
            ARRAY_A
        );
        
        if (!$approval) {
            return [
                'success' => false,
                'message' => 'Approval not found or already processed'
            ];
        }
        
        // Verify token
        if ($approval['approval_token'] !== $token) {
            return [
                'success' => false,
                'message' => 'Invalid approval token'
            ];
        }
        
        // Check expiration
        if (strtotime($approval['expires']) < time()) {
            $wpdb->update(
                $table,
                ['status' => 'expired'],
                ['id' => $approval_id]
            );
            
            return [
                'success' => false,
                'message' => 'Approval request has expired'
            ];
        }
        
        // Mark as approved
        $wpdb->update(
            $table,
            [
                'status' => 'approved',
                'approved_at' => current_time('mysql')
            ],
            ['id' => $approval_id]
        );
        
        // Execute the approved action
        $parameters = json_decode($approval['parameters'], true);
        
        // Execute the action based on type
        // This would integrate with the command execution system
        
        return [
            'success' => true,
            'message' => 'Action approved and executed'
        ];
    }
    
    /**
     * Reject a pending action
     *
     * @param int $approval_id The approval ID
     * @param string $token The approval token
     * @return array Result of the rejection
     */
    public function reject_action($approval_id, $token) {
        global $wpdb;
        $table = $wpdb->prefix . 'mpai_pending_approvals';
        
        // Get the pending approval
        $approval = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE id = %d AND status = 'pending'", $approval_id),
            ARRAY_A
        );
        
        if (!$approval) {
            return [
                'success' => false,
                'message' => 'Approval not found or already processed'
            ];
        }
        
        // Verify token
        if ($approval['approval_token'] !== $token) {
            return [
                'success' => false,
                'message' => 'Invalid approval token'
            ];
        }
        
        // Mark as rejected
        $wpdb->update(
            $table,
            [
                'status' => 'rejected',
                'approved_at' => current_time('mysql')
            ],
            ['id' => $approval_id]
        );
        
        return [
            'success' => true,
            'message' => 'Action rejected'
        ];
    }
}
```

### 6. Rate Limiting System

**Purpose:** Prevent abuse through appropriate rate limits on agent actions

**Implementation:**
```php
class MPAI_Rate_Limiter {
    /**
     * Rate limits for different actions
     */
    private $rate_limits = [
        'default' => [
            'limit' => 100,
            'window' => 3600 // 1 hour
        ],
        'api_calls' => [
            'limit' => 20,
            'window' => 60 // 1 minute
        ],
        'wp_cli' => [
            'limit' => 30,
            'window' => 3600 // 1 hour
        ],
        'tool_execute' => [
            'limit' => 50,
            'window' => 3600 // 1 hour
        ],
        'support_routing' => [
            'limit' => 5,
            'window' => 86400 // 1 day
        ]
    ];
    
    /**
     * Check if an action is rate limited
     *
     * @param string $action The action
     * @param string $agent_id The agent identifier
     * @param string $user_id The user identifier
     * @return array Check result
     */
    public function check_rate_limit($action, $agent_id, $user_id) {
        $limit_key = $this->get_limit_key($action);
        $rate_limit = isset($this->rate_limits[$limit_key]) ? 
            $this->rate_limits[$limit_key] : 
            $this->rate_limits['default'];
        
        $count = $this->get_action_count($action, $agent_id, $user_id, $rate_limit['window']);
        
        if ($count >= $rate_limit['limit']) {
            return [
                'limited' => true,
                'message' => 'Rate limit exceeded',
                'reset_in' => $this->get_reset_time($action, $agent_id, $user_id),
                'limit' => $rate_limit['limit'],
                'count' => $count
            ];
        }
        
        return [
            'limited' => false,
            'count' => $count,
            'limit' => $rate_limit['limit'],
            'remaining' => $rate_limit['limit'] - $count
        ];
    }
    
    /**
     * Record an action for rate limiting
     *
     * @param string $action The action
     * @param string $agent_id The agent identifier
     * @param string $user_id The user identifier
     * @return bool Whether the action was recorded
     */
    public function record_action($action, $agent_id, $user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'mpai_rate_limits';
        
        $result = $wpdb->insert(
            $table,
            [
                'action' => sanitize_text_field($action),
                'agent_id' => sanitize_text_field($agent_id),
                'user_id' => $user_id,
                'timestamp' => current_time('mysql')
            ]
        );
        
        return $result !== false;
    }
    
    /**
     * Get the count of actions within a time window
     *
     * @param string $action The action
     * @param string $agent_id The agent identifier
     * @param string $user_id The user identifier
     * @param int $window The time window in seconds
     * @return int The action count
     */
    private function get_action_count($action, $agent_id, $user_id, $window) {
        global $wpdb;
        $table = $wpdb->prefix . 'mpai_rate_limits';
        $time_threshold = date('Y-m-d H:i:s', time() - $window);
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE action = %s AND agent_id = %s AND user_id = %s AND timestamp > %s",
            $action,
            $agent_id,
            $user_id,
            $time_threshold
        ));
        
        return intval($count);
    }
    
    /**
     * Get the rate limit key for an action
     *
     * @param string $action The action
     * @return string The rate limit key
     */
    private function get_limit_key($action) {
        if (strpos($action, 'openai_') === 0 || strpos($action, 'anthropic_') === 0) {
            return 'api_calls';
        }
        
        if (strpos($action, 'wp_cli_') === 0) {
            return 'wp_cli';
        }
        
        if (strpos($action, 'tool_') === 0) {
            return 'tool_execute';
        }
        
        if (strpos($action, 'support_') === 0) {
            return 'support_routing';
        }
        
        return 'default';
    }
    
    /**
     * Get the reset time for a rate limited action
     *
     * @param string $action The action
     * @param string $agent_id The agent identifier
     * @param string $user_id The user identifier
     * @return int Seconds until reset
     */
    private function get_reset_time($action, $agent_id, $user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'mpai_rate_limits';
        
        $limit_key = $this->get_limit_key($action);
        $window = isset($this->rate_limits[$limit_key]) ?
            $this->rate_limits[$limit_key]['window'] :
            $this->rate_limits['default']['window'];
        
        $oldest_timestamp = $wpdb->get_var($wpdb->prepare(
            "SELECT MIN(timestamp) FROM $table WHERE action = %s AND agent_id = %s AND user_id = %s",
            $action,
            $agent_id,
            $user_id
        ));
        
        if (!$oldest_timestamp) {
            return 0;
        }
        
        $reset_time = strtotime($oldest_timestamp) + $window - time();
        return max(0, $reset_time);
    }
}
```

### 7. Security Dashboard

**Purpose:** Provide administrators with visibility into agent actions and security events

**Implementation:**
```php
// Admin page implementation for security dashboard
add_action('admin_menu', 'mpai_add_security_dashboard');

function mpai_add_security_dashboard() {
    add_submenu_page(
        'memberpress-ai-assistant-settings',
        'AI Security Dashboard',
        'Security Dashboard',
        'manage_options',
        'mpai-security-dashboard',
        'mpai_render_security_dashboard'
    );
}

function mpai_render_security_dashboard() {
    // Security dashboard HTML
    ?>
    <div class="wrap">
        <h1>MemberPress AI Assistant Security Dashboard</h1>
        
        <div class="mpai-security-tabs">
            <ul class="nav-tab-wrapper">
                <li><a href="#recent-activities" class="nav-tab nav-tab-active">Recent Activities</a></li>
                <li><a href="#pending-approvals" class="nav-tab">Pending Approvals</a></li>
                <li><a href="#security-log" class="nav-tab">Security Log</a></li>
                <li><a href="#rate-limits" class="nav-tab">Rate Limits</a></li>
                <li><a href="#security-settings" class="nav-tab">Security Settings</a></li>
            </ul>
            
            <div id="recent-activities" class="tab-content">
                <h2>Recent Agent Activities</h2>
                
                <?php
                $audit = new MPAI_Security_Audit();
                $recent_logs = $audit->get_logs([], 10, 0);
                
                if (empty($recent_logs)) {
                    echo '<p>No recent activities found.</p>';
                } else {
                    ?>
                    <table class="wp-list-table widefat striped">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Agent</th>
                                <th>Action</th>
                                <th>Target</th>
                                <th>Result</th>
                                <th>User</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_logs as $log): ?>
                            <tr>
                                <td><?php echo esc_html($log['timestamp']); ?></td>
                                <td><?php echo esc_html($log['agent_id']); ?></td>
                                <td><?php echo esc_html($log['action']); ?></td>
                                <td><?php echo esc_html($log['target']); ?></td>
                                <td><?php echo esc_html($log['status']); ?></td>
                                <td><?php echo get_user_by('id', $log['user_id'])->user_login; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php
                }
                ?>
            </div>
            
            <div id="pending-approvals" class="tab-content" style="display: none;">
                <h2>Pending Action Approvals</h2>
                
                <?php
                global $wpdb;
                $table = $wpdb->prefix . 'mpai_pending_approvals';
                $pending_approvals = $wpdb->get_results(
                    "SELECT * FROM $table WHERE status = 'pending' ORDER BY created DESC",
                    ARRAY_A
                );
                
                if (empty($pending_approvals)) {
                    echo '<p>No pending approvals found.</p>';
                } else {
                    ?>
                    <table class="wp-list-table widefat striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Action</th>
                                <th>Agent</th>
                                <th>User</th>
                                <th>Created</th>
                                <th>Expires</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_approvals as $approval): ?>
                            <tr>
                                <td><?php echo esc_html($approval['id']); ?></td>
                                <td><?php echo esc_html($approval['action']); ?></td>
                                <td><?php echo esc_html($approval['agent_id']); ?></td>
                                <td><?php echo get_user_by('id', $approval['user_id'])->user_login; ?></td>
                                <td><?php echo esc_html($approval['created']); ?></td>
                                <td><?php echo esc_html($approval['expires']); ?></td>
                                <td>
                                    <a href="#" class="button button-primary approve-action" data-id="<?php echo esc_attr($approval['id']); ?>" data-token="<?php echo esc_attr($approval['approval_token']); ?>">Approve</a>
                                    <a href="#" class="button reject-action" data-id="<?php echo esc_attr($approval['id']); ?>" data-token="<?php echo esc_attr($approval['approval_token']); ?>">Reject</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php
                }
                ?>
            </div>
            
            <!-- Other tab content sections -->
        </div>
    </div>
    <?php
}
```

## Database Schema

Create necessary tables to support the security framework:

```sql
CREATE TABLE {$wpdb->prefix}mpai_security_audit (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    agent_id VARCHAR(50) NOT NULL,
    action VARCHAR(100) NOT NULL,
    target VARCHAR(255) DEFAULT NULL,
    params LONGTEXT DEFAULT NULL,
    result VARCHAR(50) DEFAULT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'unknown',
    ip_address VARCHAR(45) DEFAULT NULL,
    user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
    timestamp DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY agent_id (agent_id),
    KEY action (action),
    KEY user_id (user_id),
    KEY timestamp (timestamp)
) {$charset_collate};

CREATE TABLE {$wpdb->prefix}mpai_pending_approvals (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    action VARCHAR(100) NOT NULL,
    parameters LONGTEXT NOT NULL,
    agent_id VARCHAR(50) NOT NULL,
    user_id BIGINT(20) UNSIGNED NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    created DATETIME NOT NULL,
    expires DATETIME NOT NULL,
    approved_at DATETIME DEFAULT NULL,
    approval_token VARCHAR(64) NOT NULL,
    PRIMARY KEY (id),
    KEY status (status),
    KEY user_id (user_id),
    KEY expires (expires)
) {$charset_collate};

CREATE TABLE {$wpdb->prefix}mpai_rate_limits (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    action VARCHAR(100) NOT NULL,
    agent_id VARCHAR(50) NOT NULL,
    user_id BIGINT(20) UNSIGNED NOT NULL,
    timestamp DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY action (action),
    KEY agent_id (agent_id),
    KEY user_id (user_id),
    KEY timestamp (timestamp)
) {$charset_collate};
```

## Integration with Existing System

### 1. Update Agent Orchestrator

Integrate security framework into the agent orchestrator:

```php
// In class-mpai-agent-orchestrator.php

// Add security framework dependencies
private $security;
private $validator;
private $sandbox;
private $audit;
private $approvals;
private $rate_limiter;

/**
 * Constructor
 */
public function __construct() {
    // Initialize security components
    require_once MPAI_PLUGIN_DIR . 'includes/security/class-mpai-agent-security.php';
    require_once MPAI_PLUGIN_DIR . 'includes/security/class-mpai-command-validator.php';
    require_once MPAI_PLUGIN_DIR . 'includes/security/class-mpai-command-sandbox.php';
    require_once MPAI_PLUGIN_DIR . 'includes/security/class-mpai-security-audit.php';
    require_once MPAI_PLUGIN_DIR . 'includes/security/class-mpai-user-approvals.php';
    require_once MPAI_PLUGIN_DIR . 'includes/security/class-mpai-rate-limiter.php';
    
    $this->security = new MPAI_Agent_Security();
    $this->validator = new MPAI_Command_Validator();
    $this->sandbox = new MPAI_Command_Sandbox();
    $this->audit = new MPAI_Security_Audit();
    $this->approvals = new MPAI_User_Approvals();
    $this->rate_limiter = new MPAI_Rate_Limiter();
    
    // Existing initialization code
}

/**
 * Execute a tool through an agent with security checks
 *
 * @param string $agent_id The agent identifier
 * @param string $tool The tool to execute
 * @param array $parameters Tool parameters
 * @return array Result of tool execution
 */
public function execute_tool($agent_id, $tool, $parameters) {
    // 1. Check agent permission
    if (!$this->security->agent_can_use_tool($agent_id, $tool)) {
        return [
            'success' => false,
            'message' => 'Agent does not have permission to use this tool',
            'code' => 'permission_denied'
        ];
    }
    
    // 2. Check rate limits
    $user_id = get_current_user_id();
    $rate_check = $this->rate_limiter->check_rate_limit($tool, $agent_id, $user_id);
    
    if ($rate_check['limited']) {
        return [
            'success' => false,
            'message' => $rate_check['message'],
            'reset_in' => $rate_check['reset_in'],
            'code' => 'rate_limited'
        ];
    }
    
    // 3. Validate the command
    $validation = $this->validator->validate($tool, $parameters);
    
    if (!$validation['valid']) {
        return [
            'success' => false,
            'message' => 'Validation failed: ' . $validation['reason'],
            'code' => 'validation_failed'
        ];
    }
    
    // 4. Check if approval is required
    if ($this->approvals->requires_approval($tool, $parameters)) {
        $approval_id = $this->approvals->create_approval($tool, $parameters, $agent_id);
        
        return [
            'success' => false,
            'message' => 'This action requires user approval',
            'approval_id' => $approval_id,
            'code' => 'approval_required'
        ];
    }
    
    // 5. Log the execution attempt
    $this->audit->log_action([
        'agent_id' => $agent_id,
        'action' => $tool,
        'target' => isset($parameters['command']) ? $parameters['command'] : (isset($parameters['action']) ? $parameters['action'] : ''),
        'params' => $parameters,
        'status' => 'attempt'
    ]);
    
    // 6. Execute in sandbox
    $result = $this->sandbox->execute_sandboxed($tool, $parameters, $agent_id);
    
    // 7. Log the execution result
    $this->audit->log_action([
        'agent_id' => $agent_id,
        'action' => $tool,
        'target' => isset($parameters['command']) ? $parameters['command'] : (isset($parameters['action']) ? $parameters['action'] : ''),
        'params' => $parameters,
        'result' => isset($result['message']) ? $result['message'] : '',
        'status' => $result['success'] ? 'success' : 'error'
    ]);
    
    // 8. Record for rate limiting
    $this->rate_limiter->record_action($tool, $agent_id, $user_id);
    
    return $result;
}
```

### 2. Update Context Manager

Integrate security framework with context manager:

```php
// In class-mpai-context-manager.php

/**
 * Execute a tool with security checks
 *
 * @param string $tool The tool name
 * @param array $parameters Tool parameters
 * @return array Execution result
 */
public function execute_tool($tool, $parameters) {
    // Get current agent ID from context
    $agent_id = $this->get_current_agent_id();
    
    // Use agent orchestrator to execute with security
    $orchestrator = new MPAI_Agent_Orchestrator();
    return $orchestrator->execute_tool($agent_id, $tool, $parameters);
}

/**
 * Get the current agent ID from context
 *
 * @return string The current agent ID
 */
private function get_current_agent_id() {
    // Default to standard agent if not specified
    return isset($this->context['agent_id']) ? $this->context['agent_id'] : 'standard_agent';
}
```

## Security Settings

Add security settings to the MemberPress AI Assistant settings page:

```php
// In settings-page.php

/**
 * Render security settings
 */
public function render_security_settings() {
    ?>
    <h3><?php _e('AI Security', 'memberpress-ai-assistant'); ?></h3>
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="mpai_security_mode"><?php _e('Security Mode', 'memberpress-ai-assistant'); ?></label>
            </th>
            <td>
                <select name="mpai_security_mode" id="mpai_security_mode">
                    <option value="standard" <?php selected(get_option('mpai_security_mode', 'standard'), 'standard'); ?>><?php _e('Standard', 'memberpress-ai-assistant'); ?></option>
                    <option value="strict" <?php selected(get_option('mpai_security_mode', 'standard'), 'strict'); ?>><?php _e('Strict', 'memberpress-ai-assistant'); ?></option>
                    <option value="paranoid" <?php selected(get_option('mpai_security_mode', 'standard'), 'paranoid'); ?>><?php _e('Paranoid', 'memberpress-ai-assistant'); ?></option>
                </select>
                <p class="description">
                    <?php _e('Standard: Basic security checks. Strict: Requires approvals for sensitive actions. Paranoid: Maximum restrictions and logging.', 'memberpress-ai-assistant'); ?>
                </p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="mpai_enable_audit"><?php _e('Security Audit Log', 'memberpress-ai-assistant'); ?></label>
            </th>
            <td>
                <label>
                    <input type="checkbox" name="mpai_enable_audit" id="mpai_enable_audit" value="1" <?php checked(get_option('mpai_enable_audit', '1')); ?> />
                    <?php _e('Enable comprehensive audit logging of AI agent actions', 'memberpress-ai-assistant'); ?>
                </label>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="mpai_require_approval"><?php _e('Require Approvals', 'memberpress-ai-assistant'); ?></label>
            </th>
            <td>
                <label>
                    <input type="checkbox" name="mpai_require_approval" id="mpai_require_approval" value="1" <?php checked(get_option('mpai_require_approval', '1')); ?> />
                    <?php _e('Require explicit user approval for sensitive operations', 'memberpress-ai-assistant'); ?>
                </label>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="mpai_enable_rate_limiting"><?php _e('Rate Limiting', 'memberpress-ai-assistant'); ?></label>
            </th>
            <td>
                <label>
                    <input type="checkbox" name="mpai_enable_rate_limiting" id="mpai_enable_rate_limiting" value="1" <?php checked(get_option('mpai_enable_rate_limiting', '1')); ?> />
                    <?php _e('Enable rate limiting for agent actions', 'memberpress-ai-assistant'); ?>
                </label>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="mpai_command_whitelist"><?php _e('Command Whitelist', 'memberpress-ai-assistant'); ?></label>
            </th>
            <td>
                <textarea name="mpai_command_whitelist" id="mpai_command_whitelist" rows="5" class="large-text code"><?php echo esc_textarea(get_option('mpai_command_whitelist', '')); ?></textarea>
                <p class="description">
                    <?php _e('Enter allowed commands one per line. Leave empty to use default whitelist.', 'memberpress-ai-assistant'); ?>
                </p>
            </td>
        </tr>
    </table>
    <?php
}
```

## Implementation Plan

### Phase 1: Security Foundation (1-2 weeks)

1. Create base security classes
2. Implement database schema
3. Add audit logging
4. Add command validation enhancement

### Phase 2: Integration & UI (1-2 weeks)

1. Integrate with agent orchestrator
2. Implement security dashboard
3. Add security settings
4. Create approval workflows

### Phase 3: Advanced Features (2-3 weeks)

1. Implement rate limiting
2. Add advanced sandboxing
3. Create agent authentication framework
4. Implement security reports

### Phase 4: Testing & Refinement (1-2 weeks)

1. Security testing
2. Performance optimization
3. Documentation
4. User experience refinement

## Security Best Practices

1. **Regular Audits** - Periodically review security logs and perform security assessments
2. **Principle of Least Privilege** - Ensure agents only have access to what they absolutely need
3. **Defense in Depth** - Multiple layers of security controls
4. **Secure Communication** - Use HTTPS for all external API calls
5. **Input Validation** - Thorough validation of all inputs
6. **Monitoring** - Active monitoring of security logs for anomalies
7. **Regular Updates** - Keep all components updated
8. **Security Training** - Ensure administrators understand the security implications of agent systems

## Conclusion

This agentic security framework provides a comprehensive approach to securing the MemberPress AI Assistant. By implementing robust authentication, authorization, validation, sandboxing, and audit mechanisms, we can ensure that the AI agents operate securely and reliably.

The framework is designed to be modular and extensible, allowing for easy integration with existing components and future enhancements. By following the implementation plan, we can gradually build out the security capabilities while maintaining compatibility with the current system.