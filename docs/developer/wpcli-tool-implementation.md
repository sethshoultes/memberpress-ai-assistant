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

The WP-CLI tool executes WordPress CLI commands using the `MPAI_WP_CLI_Executor` class. It validates the command before execution to ensure it is safe to run.

```php
protected function execute_tool($parameters) {
    // Validate parameters
    if (!isset($parameters['command'])) {
        throw new Exception('The command parameter is required.');
    }
    
    $command = $parameters['command'];
    
    // Execute the command using the executor
    $result = $this->executor->execute($command, $parameters);
    
    return $result;
}
```

### Command Validation

The WP-CLI tool validates commands before execution to ensure they are safe to run. It uses the `MPAI_Command_Validation_Agent` class to validate commands.

The validation process checks for:

- Dangerous commands (e.g., `wp db drop`)
- Commands that require elevated privileges
- Commands that could potentially harm the site

### Security Considerations

The WP-CLI tool includes several security measures:

1. **Command Validation**: All commands are validated before execution to ensure they are safe to run.
2. **Permission Checks**: Only users with the appropriate capabilities can execute WP-CLI commands.
3. **Logging**: All command executions are logged for auditing purposes.

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

## Legacy Tool IDs

Legacy tool IDs ('wpcli_new' and 'wp_cli') have been removed from the codebase. The AJAX handler will reject requests using these legacy tool IDs with an appropriate error message.

If you encounter code that still uses these legacy tool IDs, update it to use 'wpcli' instead.