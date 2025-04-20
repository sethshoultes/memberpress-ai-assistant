# Tool Call Detection

This document provides a comprehensive guide to the Tool Call Detection system in the MemberPress AI Assistant plugin.

## Overview

The Tool Call Detection system is responsible for identifying when the AI assistant is attempting to use a tool, extracting the tool name and parameters, and initiating the tool execution. It is a critical component of the Tool System that enables the AI assistant to perform actions on your WordPress site.

## Implementation

The Tool Call Detection system is implemented in both JavaScript (for client-side detection) and PHP (for server-side detection) to ensure that tool calls are detected regardless of where the AI's response is processed.

### Tool Call Formats

The system supports multiple formats for tool calls:

#### XML-Style Format

```
<tool:tool_name>{"param1": "value1", "param2": "value2"}</tool>
```

#### JSON Format

```json
{
  "name": "tool_name",
  "parameters": {
    "param1": "value1",
    "param2": "value2"
  }
}
```

#### HTML Format (DOM-Based)

```html
<div id="mpai-tool-123" class="mpai-tool-call" data-tool-name="tool_name" data-tool-parameters='{"param1": "value1", "param2": "value2"}'>
    <div class="mpai-tool-call-header">
        <span class="mpai-tool-call-name">tool_name</span>
        <span class="mpai-tool-call-status mpai-tool-call-processing">Processing</span>
    </div>
    <div class="mpai-tool-call-parameters">
        <pre>{"param1": "value1", "param2": "value2"}</pre>
    </div>
    <div class="mpai-tool-call-result"></div>
</div>
```

### JavaScript Implementation

The JavaScript implementation is responsible for detecting tool calls in the AI's response on the client side. It uses a combination of regex patterns and DOM parsing to identify tool calls.

#### Key Components

- **MPAI_Tools.processToolCalls**: The main function that processes the AI's response and detects tool calls
- **MPAI_Tools.executeToolCall**: Executes a detected tool call
- **MPAI_Tools.createToolCallHTML**: Creates HTML markup for a tool call

#### Process Flow

1. The AI's response is received by the chat interface
2. The response is passed to `MPAI_Tools.processToolCalls`
3. The function checks for tool calls using regex patterns and DOM parsing
4. If tool calls are detected, they are extracted and executed
5. The response is updated with the tool call results

#### Code Example

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
            
            // Skip if this tool call has already been processed
            if (processedToolCalls.has(toolId)) {
                return;
            }
            
            // Mark as processed
            processedToolCalls.add(toolId);
            
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
            
            // Skip if this exact command has already been processed (using the full match as key)
            if (processedToolCalls.has(match[0])) {
                continue;
            }
            
            // Mark as processed
            processedToolCalls.add(match[0]);
            
            // Parse parameters
            let parameters = {};
            try {
                parameters = JSON.parse(parametersStr);
            } catch (e) {
                console.error('Failed to parse tool parameters:', e);
            }
            
            // Create HTML for the tool call
            const toolCallHtml = createToolCallHTML(toolName, parameters, toolId);
            
            // Replace the tool call markup with the HTML
            response = response.replace(match[0], toolCallHtml);
            
            // Execute the tool
            executeToolCall(toolName, parameters, toolId);
        }
    }
    
    // If tool calls were found and processed, update the response
    if (hasToolCalls && !$toolCalls.length) {
        // Add the processed message
        if (messagesModule) {
            messagesModule.addMessage('assistant', response);
        }
    }
    
    return hasToolCalls;
}
```

### PHP Implementation

The PHP implementation is responsible for detecting tool calls in the AI's response on the server side. It uses similar techniques to identify tool calls.

#### Key Components

- **MPAI_Tool_Call_Detector**: The class that handles tool call detection
- **MPAI_Tool_Call_Detector::detect_tool_calls**: The main method that detects tool calls in a response
- **MPAI_Tool_Call_Detector::execute_tool_calls**: Executes detected tool calls

#### Process Flow

1. The AI's response is received by the server
2. The response is passed to `MPAI_Tool_Call_Detector::detect_tool_calls`
3. The method checks for tool calls using regex patterns
4. If tool calls are detected, they are extracted and executed
5. The response is updated with the tool call results

#### Code Example

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
            'parameters' => $parameters,
            'original' => $match[0]
        ];
    }
    
    return $tool_calls;
}
```

## Tool ID Standardization

**Important**: Only standardized tool IDs are supported. Legacy tool IDs have been removed to ensure consistency and prevent confusion.

### Standardized Tool IDs

- **wpcli**: The WP-CLI tool for executing WordPress CLI commands
- **wp_api**: The WordPress API tool for accessing WordPress API functions
- **plugin_logs**: The Plugin Logs tool for retrieving and analyzing plugin logs

### Legacy Tool IDs (Removed)

- **wpcli_new**: Legacy ID for the WP-CLI tool
- **wp_cli**: Legacy ID for the WP-CLI tool

## Tool Execution

Once a tool call is detected, the Tool Call Detection system initiates the tool execution process:

1. The tool name is used to retrieve the tool from the Tool Registry
2. The parameters are validated
3. The tool is executed with the provided parameters
4. The result is processed and formatted
5. The response is updated with the tool call result

### JavaScript Implementation

```javascript
function executeToolCall(toolName, parameters, toolId) {
    // Construct the tool request
    const toolRequest = {
        name: toolName,
        parameters: parameters
    };
    
    // Execute the tool via AJAX
    $.ajax({
        url: mpai_chat_data.ajax_url,
        type: 'POST',
        data: {
            action: 'mpai_execute_tool',
            tool_request: JSON.stringify(toolRequest),
            nonce: mpai_chat_data.nonce
        },
        success: function(response) {
            // Process the response
            // ...
        },
        error: function(xhr, status, error) {
            // Handle errors
            // ...
        }
    });
}
```

### PHP Implementation

```php
public function execute_tool_calls($tool_calls, $response) {
    // Get the Tool Registry
    $tool_registry = MPAI_Tool_Registry::get_instance();
    
    foreach ($tool_calls as $tool_call) {
        $tool_name = $tool_call['name'];
        $parameters = $tool_call['parameters'];
        $original = $tool_call['original'];
        
        // Get the tool
        $tool = $tool_registry->get_tool($tool_name);
        
        if (!$tool) {
            // Tool not found
            $error_message = "Tool '{$tool_name}' not found.";
            $response = str_replace($original, $error_message, $response);
            continue;
        }
        
        // Execute the tool
        $result = $tool->execute($parameters);
        
        // Process the result
        // ...
        
        // Update the response
        $response = str_replace($original, $result_html, $response);
    }
    
    return $response;
}
```

## Security Considerations

The Tool Call Detection system includes several security measures:

### Input Validation

All tool calls are validated before execution to ensure they are properly formatted and contain valid parameters.

### Tool Validation

The system checks that the requested tool exists and is registered with the Tool Registry.

### Parameter Validation

Tool parameters are validated to ensure they meet the requirements of the tool.

### Permission Checks

The system checks that the user has the necessary permissions to execute the requested tool.

### Rate Limiting

The system includes rate limiting to prevent abuse by limiting the number of tool calls that can be executed within a certain time period.

## Troubleshooting

### Common Issues

#### Tool Call Not Detected

If a tool call is not being detected, check:

1. The tool call format is correct
2. The tool name is valid
3. The parameters are properly formatted JSON

#### Tool Execution Failed

If a tool execution fails, check:

1. The tool exists and is registered
2. The parameters are valid
3. The user has the necessary permissions
4. The server logs for more detailed error information

## Best Practices

1. Use the standardized tool call formats
2. Provide all required parameters
3. Handle errors gracefully
4. Log tool calls for debugging and auditing
5. Follow the security guidelines

## Related Documentation

- [Tool System Overview](./tool-system-overview.md)
- [Tool Registry](./tool-registry.md)
- [Base Tool Class](./base-tool.md)
- [Available Tools](./available-tools.md)
- [Tool Security](./tool-security.md)