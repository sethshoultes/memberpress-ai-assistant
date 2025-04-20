# WP-CLI Tool Implementation

This document provides a comprehensive guide to the WP-CLI tool implementation in the MemberPress AI Assistant plugin.

## Overview

The WP-CLI tool allows the AI assistant to execute WordPress CLI commands. It is implemented in the `MPAI_WP_CLI_Tool` class and is registered with the tool registry as 'wpcli'.

## Tool ID Standardization

**Important**: Only the 'wpcli' tool ID is supported. Legacy tool IDs ('wpcli_new' and 'wp_cli') have been removed to ensure consistency and prevent confusion.

## Implementation Details

### Class Structure

The WP-CLI tool is implemented in the `MPAI_WP_CLI_Tool` class, which extends the `MPAI_Base_Tool` class. The class is located in the `includes/tools/implementations/class-mpai-wpcli-tool.php` file.

```php
class MPAI_WP_CLI_Tool extends MPAI_Base_Tool {
    // Implementation details
}
```

### Registration

The WP-CLI tool is registered with the tool registry in the `MPAI_Tool_Registry` class:

```php
public function register_default_tools() {
    // Register other tools
    
    // Register WP-CLI tool
    $this->register_tool('wpcli', new MPAI_WP_CLI_Tool());
    
    // Register other tools
}
```

### Execution

The WP-CLI tool executes WordPress CLI commands using the `WP_CLI` class. It validates the command before execution to ensure it is safe to run.

```php
public function execute($parameters) {
    // Validate parameters
    if (!isset($parameters['command'])) {
        return new WP_Error('missing_parameter', 'The command parameter is required.');
    }
    
    $command = $parameters['command'];
    
    // Validate command
    $validation_result = $this->validate_command($command);
    if (is_wp_error($validation_result)) {
        return $validation_result;
    }
    
    // Execute command
    $result = $this->execute_command($command);
    
    return $result;
}
```

### Command Validation

The WP-CLI tool validates commands before execution to ensure they are safe to run. It uses the `MPAI_Command_Validation_Agent` class to validate commands.

```php
private function validate_command($command) {
    // Get command validation agent
    $agent_orchestrator = MPAI_Agent_Orchestrator::get_instance();
    $validation_agent = $agent_orchestrator->get_agent('command_validation');
    
    // Validate command
    $validation_result = $validation_agent->validate_command($command);
    
    return $validation_result;
}
```

### Command Execution

The WP-CLI tool executes commands using the `WP_CLI` class. It captures the output of the command and returns it to the AI assistant.

```php
private function execute_command($command) {
    // Start output buffering
    ob_start();
    
    // Execute command
    WP_CLI::run_command(explode(' ', $command));
    
    // Get output
    $output = ob_get_clean();
    
    return $output;
}
```

## Security Considerations

### Command Validation

The WP-CLI tool validates commands before execution to ensure they are safe to run. It checks for:

- Dangerous commands (e.g., `wp db drop`)
- Commands that require elevated privileges
- Commands that could potentially harm the site

### Permission Checks

The WP-CLI tool checks user permissions before executing commands. Only users with the appropriate capabilities can execute WP-CLI commands.

### Logging

The WP-CLI tool logs all command executions for auditing purposes. This helps track who executed which commands and when.

## Usage Examples

### List WordPress Users

```json
{
  "name": "wpcli",
  "parameters": {
    "command": "user list --fields=ID,user_login,user_email,roles"
  }
}
```

### Get WordPress Version

```json
{
  "name": "wpcli",
  "parameters": {
    "command": "core version"
  }
}
```

### List MemberPress Memberships

```json
{
  "name": "wpcli",
  "parameters": {
    "command": "memberpress membership list"
  }
}
```

## Troubleshooting

### Common Issues

#### Command Not Found

If you receive a "Command not found" error, ensure that:

1. The command is a valid WP-CLI command
2. The command is available in your WordPress installation
3. The command is properly formatted

#### Permission Denied

If you receive a "Permission denied" error, ensure that:

1. The user has the appropriate capabilities to execute the command
2. The command does not require elevated privileges
3. The command is not blocked by the command validation agent

#### Execution Failed

If the command execution fails, check:

1. The command syntax
2. The WordPress error logs
3. The MemberPress AI Assistant logs

## Best Practices

1. Always validate user input before constructing WP-CLI commands
2. Use the most specific command possible to accomplish the task
3. Handle errors gracefully and provide helpful error messages
4. Log all command executions for auditing purposes
5. Use the command validation agent to ensure commands are safe to run

## Related Documentation

- [WP-CLI Documentation](https://wp-cli.org/)
- [Command Validation Agent](../agents/command-validation-agent.md)
- [Tool Security](./tool-security.md)
- [Tool Registry](./tool-registry.md)