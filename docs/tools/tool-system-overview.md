# Tool System Overview

This document provides a comprehensive overview of the Tool System in the MemberPress AI Assistant plugin.

## Introduction

The Tool System is a core component of the MemberPress AI Assistant plugin that enables the AI to perform actions on your WordPress site. It allows the AI assistant to execute commands, access data, and interact with WordPress and MemberPress functionality.

## Architecture

The Tool System consists of several components:

### Tool Registry

The Tool Registry is the central component of the Tool System. It is responsible for:

- Registering tools
- Retrieving tools by name
- Managing tool metadata
- Providing a centralized access point for tools

The Tool Registry is implemented in the `MPAI_Tool_Registry` class, which follows the Singleton pattern to ensure that only one instance exists throughout the application lifecycle.

### Base Tool

The Base Tool provides a common interface for all tools. It defines the methods that all tools must implement and provides default implementations for common functionality.

The Base Tool is implemented in the `MPAI_Base_Tool` class, which is an abstract class that all tool implementations must extend.

### Tool Implementations

Tool Implementations are the concrete implementations of specific tools. Each tool implementation focuses on a particular functionality, such as executing WP-CLI commands or accessing the WordPress API.

The plugin includes several built-in tool implementations:

- **WP-CLI Tool**: Executes WordPress CLI commands
- **WordPress API Tool**: Provides access to WordPress API functions
- **Plugin Logs Tool**: Retrieves and analyzes plugin logs

### Tool Call Detection

Tool Call Detection is the mechanism that identifies tool calls in AI responses. It parses the AI's response to identify when the AI is attempting to use a tool, extracts the tool name and parameters, and initiates the tool execution.

Tool Call Detection is implemented in both JavaScript (for client-side detection) and PHP (for server-side detection).

## Tool Registration

Tools are registered with the Tool Registry during plugin initialization. The registration process involves:

1. Creating an instance of the tool
2. Registering the tool with the Tool Registry
3. Configuring the tool's metadata

```php
// Get the Tool Registry instance
$tool_registry = MPAI_Tool_Registry::get_instance();

// Register the WP-CLI Tool
$wpcli_tool = new MPAI_WP_CLI_Tool();
$tool_registry->register_tool('wpcli', $wpcli_tool);

// Register the WordPress API Tool
$wp_api_tool = new MPAI_WP_API_Tool();
$tool_registry->register_tool('wp_api', $wp_api_tool);

// Register the Plugin Logs Tool
$plugin_logs_tool = new MPAI_Plugin_Logs_Tool();
$tool_registry->register_tool('plugin_logs', $plugin_logs_tool);
```

## Tool Execution

Tool Execution is the process of running a tool with specific parameters. The execution process involves:

1. Retrieving the tool from the Tool Registry
2. Validating the parameters
3. Executing the tool with the provided parameters
4. Processing the tool's response
5. Returning the response to the AI assistant

```php
public function execute_tool($tool_name, $parameters) {
    // Get the tool from the registry
    $tool = $this->get_tool($tool_name);
    
    // Check if the tool exists
    if (!$tool) {
        return new WP_Error('tool_not_found', "Tool '{$tool_name}' not found.");
    }
    
    // Execute the tool
    $result = $tool->execute($parameters);
    
    return $result;
}
```

## Tool Call Detection

Tool Call Detection is implemented in both JavaScript and PHP to ensure that tool calls are detected regardless of where the AI's response is processed.

### JavaScript Implementation

The JavaScript implementation uses a combination of regex patterns and DOM parsing to identify tool calls in the AI's response:

```javascript
function processToolCalls(response) {
    // Check for tool call markup
    const toolCallRegex = /<tool:([^>]+)>([\s\S]*?)<\/tool>/g;
    let match;
    let hasToolCalls = false;
    
    // Create a temporary div to parse HTML
    const $temp = $('<div>').html(response);
    
    // Look for structured tool calls
    const $toolCalls = $temp.find('.mpai-tool-call');
    
    if ($toolCalls.length > 0) {
        // Process structured tool calls in the DOM
        hasToolCalls = true;
        
        $toolCalls.each(function() {
            const $toolCall = $(this);
            const toolId = $toolCall.attr('id');
            const toolName = $toolCall.data('tool-name');
            const parametersStr = $toolCall.data('tool-parameters');
            
            // Parse parameters
            let parameters = {};
            try {
                parameters = JSON.parse(parametersStr);
            } catch (e) {
                console.error('Failed to parse tool parameters:', e);
            }
            
            // Execute the tool
            executeToolCall(toolName, parameters, toolId);
        });
    } else {
        // Check for string-based tool calls
        while ((match = toolCallRegex.exec(response)) !== null) {
            hasToolCalls = true;
            
            const toolName = match[1];
            const parametersStr = match[2];
            
            // Generate a unique ID for this tool call
            const toolId = 'mpai-tool-' + Date.now() + '-' + Math.floor(Math.random() * 1000);
            
            // Parse parameters
            let parameters = {};
            try {
                parameters = JSON.parse(parametersStr);
            } catch (e) {
                console.error('Failed to parse tool parameters:', e);
            }
            
            // Execute the tool
            executeToolCall(toolName, parameters, toolId);
        }
    }
    
    return hasToolCalls;
}
```

### PHP Implementation

The PHP implementation uses similar techniques to identify tool calls in the AI's response:

```php
public function detect_tool_calls($response) {
    // Check for tool call markup
    $tool_calls = [];
    
    // Pattern for tool calls
    $pattern = '/<tool:([^>]+)>([\s\S]*?)<\/tool>/';
    
    // Find all matches
    preg_match_all($pattern, $response, $matches, PREG_SET_ORDER);
    
    foreach ($matches as $match) {
        $tool_name = $match[1];
        $parameters_str = $match[2];
        
        // Parse parameters
        $parameters = json_decode($parameters_str, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Log error
            error_log("Failed to parse tool parameters: " . json_last_error_msg());
            continue;
        }
        
        // Add to tool calls
        $tool_calls[] = [
            'name' => $tool_name,
            'parameters' => $parameters
        ];
    }
    
    return $tool_calls;
}
```

## Security Considerations

The Tool System includes several security measures:

### Command Validation

For tools that execute commands (like the WP-CLI Tool), commands are validated before execution to ensure they are safe to run. The validation process checks for:

- Dangerous commands (e.g., `wp db drop`)
- Commands that require elevated privileges
- Commands that could potentially harm the site

### Permission Checks

Tools check user permissions before execution to ensure that only authorized users can execute certain tools. Permission checks include:

- WordPress capability checks
- Role-based access control
- Custom permission rules

### Rate Limiting

The Tool System includes rate limiting to prevent abuse. Rate limiting restricts the number of tool executions that can be performed within a certain time period.

### Logging

All tool executions are logged for auditing purposes. Logs include:

- The tool that was executed
- The parameters that were provided
- The user who initiated the execution
- The time of execution
- The result of the execution

## Extending the Tool System

The Tool System is designed to be extensible, allowing developers to create custom tools for specific needs. To create a custom tool:

1. Create a new class that extends `MPAI_Base_Tool`
2. Implement the required methods
3. Register the tool with the Tool Registry

```php
class My_Custom_Tool extends MPAI_Base_Tool {
    public function execute($parameters) {
        // Implement tool execution logic
        
        // Return the result
        return $result;
    }
}

// Register the custom tool
$tool_registry = MPAI_Tool_Registry::get_instance();
$custom_tool = new My_Custom_Tool();
$tool_registry->register_tool('custom_tool', $custom_tool);
```

## Best Practices

1. Validate all parameters before execution
2. Implement proper error handling
3. Return meaningful error messages
4. Log all tool executions
5. Follow the security guidelines
6. Use descriptive tool names
7. Document tool parameters and return values

## Related Documentation

- [Tool Registry](./tool-registry.md)
- [Base Tool Class](./base-tool.md)
- [Tool Call Detection](./tool-call-detection.md)
- [Available Tools](./available-tools.md)
- [Creating Custom Tools](./custom-tools.md)
- [Tool Security](./tool-security.md)