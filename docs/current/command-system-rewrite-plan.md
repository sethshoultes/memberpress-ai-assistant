# Command System Rewrite Plan: KISS and DRY Approach

## Problem Statement

The current MemberPress AI Assistant command system is fragile, relying on multiple layers of validation and complex orchestration that often breaks when components are updated. System information commands like PHP version queries fail frequently due to restrictive validation and insufficient recovery mechanisms.

## Core Principles

1. **KISS (Keep It Simple, Stupid)**: Simplify the command validation and execution flow
2. **DRY (Don't Repeat Yourself)**: Consolidated command handling with minimal duplication
3. **Permissive by Default**: Allow commands unless explicitly dangerous
4. **No Fallbacks or Simulation**: Direct execution with clear error handling

## Architecture Overview

```
User Request → Command Parser → Security Filter → Direct Execution → Response
```

This streamlined approach replaces the current fragmented system:

```
User Request → Agent Orchestrator → Validation Agent → Tool Registry → Tool Implementation → Command Execution → Response
```

## Detailed Implementation Plan

### 1. Create a Centralized Command Handler

```php
class MPAI_Command_Handler {
    // Single point of command execution
    public function execute_command($command, $parameters = []) {
        // 1. Parse command
        // 2. Apply security filters
        // 3. Execute directly
        // 4. Return result
    }
}
```

### 2. Replace Validation with Security Filtering

Instead of a whitelist approach, use a blacklist of dangerous patterns:

```php
private function is_dangerous_command($command) {
    $dangerous_patterns = [
        '/rm\s+-rf/i',                   // Recursive file deletion
        '/DROP\s+TABLE/i',               // SQL table drops
        '/system\s*\(/i',                // PHP system calls
        '/(?:curl|wget)\s+.*-o/i',       // File downloads
        '/eval\s*\(/i',                  // PHP code evaluation
        '/<\?php/i',                     // PHP code inclusion
        '/>(\\/dev\\/null|\\/dev\\/zero)/i', // Redirects to system devices
    ];
    
    foreach ($dangerous_patterns as $pattern) {
        if (preg_match($pattern, $command)) {
            return true;
        }
    }
    
    return false;
}
```

### 3. Implement Direct Command Execution

```php
private function execute_wp_cli_command($command) {
    // Clean the command
    $command = $this->sanitize_command($command);
    
    // Execute directly
    $output = [];
    $return_code = 0;
    exec($command, $output, $return_code);
    
    return [
        'success' => ($return_code === 0),
        'output' => implode("\n", $output),
        'return_code' => $return_code
    ];
}
```

### 4. Implement Clean Command Sanitization

```php
private function sanitize_command($command) {
    // Remove shell special characters
    $command = preg_replace('/[;&|><$]/', '', $command);
    
    // Ensure WP CLI commands start with "wp"
    if (strpos($command, 'wp ') !== 0 && strpos($command, 'php ') !== 0) {
        $command = 'wp ' . $command;
    }
    
    return trim($command);
}
```

### 5. Create a New File Structure

```
/includes/
  /commands/
    class-mpai-command-handler.php       # Main command handler
    class-mpai-command-sanitizer.php     # Command sanitization
    class-mpai-command-security.php      # Security filtering
    class-mpai-wp-cli-executor.php       # WP-CLI execution
    class-mpai-php-executor.php          # PHP command execution
```

### 6. Refactor Agent Orchestrator

```php
class MPAI_Agent_Orchestrator {
    private $command_handler;
    
    public function __construct() {
        $this->command_handler = new MPAI_Command_Handler();
    }
    
    public function process_request($user_message, $user_id = 0) {
        // Extract command from message
        $command = $this->extract_command($user_message);
        
        // Execute command directly
        $result = $this->command_handler->execute_command($command);
        
        // Return result
        return [
            'success' => $result['success'],
            'message' => $result['output'],
            'source' => 'command_handler'
        ];
    }
}
```

### 7. Implement System Command Detectors

```php
class MPAI_Command_Detector {
    public function detect_command_type($message) {
        if (preg_match('/php.*version|what.*php.*version/i', $message)) {
            return [
                'type' => 'php_version',
                'command' => 'php -v'
            ];
        }
        
        if (preg_match('/plugin.*list|list.*plugin/i', $message)) {
            return [
                'type' => 'plugin_list',
                'command' => 'wp plugin list'
            ];
        }
        
        // More command type detection...
    }
}
```

## Implementation Steps

1. **Create Base Classes**:
   - Create the new command handler with basic structure
   - Implement the security filter with blacklist approach
   - Create command sanitization methods

2. **Build Direct Executors**:
   - Implement WP-CLI executor
   - Implement PHP command executor
   - Implement WordPress API executor

3. **Create Command Detection**:
   - Build natural language command detector
   - Map common queries to appropriate commands

4. **Integrate with Agent System**:
   - Update orchestrator to use new command system
   - Remove validation agent dependencies
   - Update tool registry to use direct executors

5. **Update Plugin Entry Points**:
   - Update AJAX handlers to use new command system
   - Update tool implementations to use new executors

## Testing Plan

1. **Basic Command Testing**:
   - Test PHP version queries
   - Test plugin listing and status
   - Test WordPress core commands

2. **Edge Case Testing**:
   - Test commands with special characters
   - Test command variants and misspellings
   - Test long and complex commands

3. **Security Testing**:
   - Verify dangerous commands are blocked
   - Test command injection attempts
   - Verify sanitization is effective

4. **Integration Testing**:
   - Test with AI agent system
   - Test with MemberPress features
   - Test through web interface

## Expected Benefits

1. **Simplified Architecture**: Single flow for command handling instead of fragmented approach
2. **Greater Reliability**: Less complex validation means fewer points of failure
3. **Better Maintainability**: Easier to update and extend with new commands
4. **Improved User Experience**: More consistent command execution with clearer error messages

## Migration Strategy

Rather than refactoring the existing system, the plan is to build this new command system in parallel and gradually switch over components. This minimizes risk and allows for proper testing before full deployment.

1. First implement the core command handler and security components
2. Create adapters to connect the new system with existing components
3. Switch over high-priority commands (like PHP version and plugin queries) first
4. Gradually migrate other commands as they are tested and validated
5. Finally, remove the old validation and execution system

By implementing this plan, we will create a more robust, maintainable command system that better serves users' needs while maintaining security and performance.