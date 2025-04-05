# MemberPress AI Assistant Tool Implementation Map

## Overview

This document provides a comprehensive guide for implementing new tools in the MemberPress AI Assistant system. It details all components that need to be updated when adding new tool functionality.

## 1. Tool Implementation Workflow

When implementing a new tool in the MemberPress AI Assistant system, the following components must be updated:

```
┌─────────────────────┐     ┌─────────────────────┐     ┌─────────────────────┐
│ 1. Create Tool Class│────►│ 2. Register Tool    │────►│ 3. Update Context   │
│    Implementation   │     │    in Registry      │     │    Manager          │
└─────────────────────┘     └─────────────────────┘     └─────────────────────┘
          │                           │                           │
          ▼                           ▼                           ▼
┌─────────────────────┐     ┌─────────────────────┐     ┌─────────────────────┐
│ 4. Update System    │     │ 5. Add Client-side  │     │ 6. Create Tests     │
│    Prompt           │◄────│    Integration      │◄────│                     │
└─────────────────────┘     └─────────────────────┘     └─────────────────────┘
```

## 2. Detailed Implementation Steps

### 2.1 Create Tool Class Implementation

**File Location**: `/includes/tools/implementations/class-mpai-{tool-name}-tool.php`

**Steps**:
1. Create a new PHP class that extends `MPAI_Base_Tool` abstract class
2. Implement required methods:
   - Constructor: Set `$this->name` and `$this->description`
   - `get_tool_definition()`: Define JSON schema for AI providers
   - `execute($parameters)`: Implement the tool's functionality

**Example Template**:
```php
<?php
/**
 * {Tool Name} Tool
 *
 * {Tool Description}
 *
 * @package MemberPress AI Assistant
 */

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * {Tool Name} Tool
 */
class MPAI_{ToolName}_Tool extends MPAI_Base_Tool {
    /**
     * Constructor
     */
    public function __construct() {
        $this->name = '{Tool Name}';
        $this->description = '{Tool Description}';
    }
    
    /**
     * Get tool definition for AI function calling
     *
     * @return array Tool definition
     */
    public function get_tool_definition() {
        return [
            'name' => '{tool_id}',
            'description' => '{Detailed tool description for AI}',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'param1' => [
                        'type' => 'string',
                        'description' => 'Description of param1'
                    ],
                    'param2' => [
                        'type' => 'integer',
                        'description' => 'Description of param2'
                    ],
                    // Add more parameters as needed
                ],
                'required' => ['param1'] // List required parameters
            ],
        ];
    }

    /**
     * Execute the tool
     *
     * @param array $parameters Parameters for execution
     * @return mixed Execution result
     */
    public function execute($parameters) {
        // Validate parameters
        if (!isset($parameters['param1'])) {
            throw new Exception('param1 is required');
        }
        
        // Implement tool functionality
        $result = [
            'success' => true,
            'tool' => '{tool_id}',
            'result' => 'Tool execution results here'
        ];
        
        return $result;
    }
}
```

### 2.2 Register Tool in Registry

**File Location**: `/includes/tools/class-mpai-tool-registry.php`

**Steps**:
1. Add a new getter method to retrieve your tool instance
2. Update the `register_core_tools()` method to register your tool

**Example Implementation**:
```php
/**
 * Get {Tool Name} tool instance
 *
 * @return object|null Tool instance
 */
private function get_{tool_name}_tool_instance() {
    // Check if the tool class exists
    if (!class_exists('MPAI_{ToolName}_Tool')) {
        $tool_path = plugin_dir_path(__FILE__) . 'implementations/class-mpai-{tool-name}-tool.php';
        if (file_exists($tool_path)) {
            require_once $tool_path;
            if (class_exists('MPAI_{ToolName}_Tool')) {
                return new MPAI_{ToolName}_Tool();
            }
        }
        return null;
    }
    
    return new MPAI_{ToolName}_Tool();
}

/**
 * Register all core tools
 */
private function register_core_tools() {
    // Existing code...
    
    // Register {Tool Name} tool
    ${tool_name}_tool = $this->get_{tool_name}_tool_instance();
    if (${tool_name}_tool) {
        $this->register_tool('{tool_id}', ${tool_name}_tool);
    }
    
    // Register other tools as needed...
}
```

### 2.3 Update Context Manager

**File Location**: `/includes/class-mpai-context-manager.php`

**Steps**:
1. Add your tool definition to the `$available_tools` array in `init_tools()`
2. Define parameters and callback function for the tool

**Example Implementation**:
```php
/**
 * Initialize available tools
 */
private function init_tools() {
    // Initialize tool registry if available
    // ...existing code...
    
    $this->available_tools = array(
        // ...existing tools...
        
        '{tool_id}' => array(
            'name' => '{tool_id}',
            'description' => '{Tool Description}',
            'parameters' => array(
                'param1' => array(
                    'type' => 'string',
                    'description' => 'Description of param1'
                ),
                'param2' => array(
                    'type' => 'integer',
                    'description' => 'Description of param2'
                ),
                // Add more parameters as needed
            ),
            'callback' => array($this, 'execute_{tool_name}')
        ),
    );

    // Allow plugins to extend available tools
    $this->available_tools = apply_filters('mpai_available_tools', $this->available_tools);
}

/**
 * Execute {Tool Name}
 *
 * @param array $parameters Tool parameters
 * @return mixed Execution result
 */
public function execute_{tool_name}($parameters) {
    // Get tool from registry
    $tool = null;
    if (class_exists('MPAI_Tool_Registry')) {
        $registry = new MPAI_Tool_Registry();
        $tool = $registry->get_tool('{tool_id}');
    }
    
    if ($tool) {
        try {
            return $tool->execute($parameters);
        } catch (Exception $e) {
            error_log('MPAI: Error executing {tool_id} tool: ' . $e->getMessage());
            return [
                'success' => false,
                'tool' => '{tool_id}',
                'error' => $e->getMessage()
            ];
        }
    }
    
    // Fallback implementation if tool not found in registry
    // ...
}
```

### 2.4 Update System Prompt

**File Location**: `/includes/class-mpai-chat.php`

**Steps**:
1. Locate the `get_system_prompt()` method
2. Add documentation for your new tool
3. Provide examples and use cases to guide the AI

**Example Implementation**:
```php
/**
 * Get system prompt for AI
 *
 * @return string The system prompt
 */
private function get_system_prompt() {
    $system_prompt = "You are MemberPress AI Assistant, a helpful AI assistant...";
    
    // Existing tools documentation...
    
    // Add documentation for your new tool
    $system_prompt .= "\n\n## {Tool Name} Tool\n";
    $system_prompt .= "Use the {tool_id} tool to {description of what the tool does}.\n";
    $system_prompt .= "Example usage:\n";
    $system_prompt .= "```json\n";
    $system_prompt .= "{\n";
    $system_prompt .= "  \"tool\": \"{tool_id}\",\n";
    $system_prompt .= "  \"parameters\": {\n";
    $system_prompt .= "    \"param1\": \"example value\",\n";
    $system_prompt .= "    \"param2\": 42\n";
    $system_prompt .= "  }\n";
    $system_prompt .= "}\n";
    $system_prompt .= "```\n";
    $system_prompt .= "When to use: {explain when this tool should be used vs. other tools}\n";
    
    return $system_prompt;
}
```

### 2.5 Add Client-side Integration (if needed)

**File Location**: `/assets/js/modules/mpai-chat-tools.js`

If your tool requires special client-side processing, update the JavaScript modules:

**Example Implementation**:
```javascript
/**
 * Handle specialized tool result formatting
 *
 * @param {string} toolName - The name of the tool
 * @param {Object} result - The result returned by the tool
 * @return {string} Formatted result for display
 */
function formatToolResult(toolName, result) {
    // Existing tools...
    
    // Format results for your new tool
    if (toolName === '{tool_id}') {
        let formattedResult = '';
        
        if (result.success) {
            // Format successful result
            formattedResult = `${result.result}`;
        } else {
            // Format error result
            formattedResult = `Error: ${result.error || 'Unknown error'}`;
        }
        
        return formattedResult;
    }
    
    // Default formatting for other tools
    return JSON.stringify(result, null, 2);
}
```

### 2.6 Create Tests

**File Location**: `/test/test-{tool-name}.php`

Create a test file to verify your tool works correctly:

**Example Implementation**:
```php
<?php
/**
 * Test file for {Tool Name} tool
 */

// Check if WordPress is loaded
if (!defined('ABSPATH')) {
    include_once(__DIR__ . '/../../../wp-load.php');
}

// Include required files
require_once MPAI_PLUGIN_DIR . 'includes/tools/class-mpai-tool-registry.php';
require_once MPAI_PLUGIN_DIR . 'includes/tools/implementations/class-mpai-{tool-name}-tool.php';

// Create tool instance
$tool = new MPAI_{ToolName}_Tool();

// Test with valid parameters
$valid_params = array(
    'param1' => 'test value',
    'param2' => 42,
);
echo "Testing with valid parameters:\n";
$result = $tool->execute($valid_params);
echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";

// Test with invalid parameters
$invalid_params = array(
    // Missing required parameters
);
echo "Testing with invalid parameters:\n";
try {
    $result = $tool->execute($invalid_params);
    echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
} catch (Exception $e) {
    echo "Error (expected): " . $e->getMessage() . "\n";
}
```

## 3. System Integration Points

### 3.1 Tool Execution Flow

1. User sends message via chat interface → `assets/js/chat-interface.js` or modular equivalent (`/assets/js/modules/chat-interface-loader.js`)
2. AJAX handler `process_chat_ajax()` receives the request in `memberpress-ai-assistant.php`
3. `MPAI_Chat::process_message()` sends request to AI via `MPAI_API_Router`
4. AI response may include tool calls detected by:
   - The pattern matching in `MPAI_Chat::process_tool_calls()`
   - Structured tool calls in `MPAI_Chat::process_structured_tool_calls()`
5. Tool calls are executed via `MPAI_Context_Manager::process_tool_request()`
6. Results are formatted and added to the AI response by `MPAI_Chat::format_result_content()`
7. Response is returned to client and displayed by the JavaScript modules

### 3.2 Model Context Protocol (MCP) Integration

The MemberPress AI Assistant implements the Model Context Protocol which provides a standardized way for tools to be defined, discovered, and accessed by AI models:

1. **Tool Definition Stage**:
   - Each tool provides its schema through `get_tool_definition()`
   - The Context Manager collects and formats these for the AI model
   - Both OpenAI and Anthropic formats are supported

2. **Tool Discovery**:
   - Tools are registered in the Tool Registry
   - The Context Manager includes available tools in the system prompt
   - AI models can see available tools through the system context

3. **Tool Execution Stage**:
   - AI models request tool execution through structured formats
   - The Context Manager routes requests to the appropriate tool
   - Results are formatted and returned to the AI

### 3.3 Agent System Integration

For tools that require agent capabilities:

1. **Agent Access to Tools**:
   - Agents access tools through the Context Manager
   - The Agent Orchestrator coordinates tool access across agents
   - Specialized agents can override default tool behavior

2. **Agent-Tool Workflow**:
   - The Agent Orchestrator receives tool requests from the AI
   - It delegates to specialized agents based on domain
   - Agents call tools via Context Manager and return results
   - Results are formatted and incorporated into the AI's response

3. **Agent-Specific Tool Implementation**:
   - For tools that require specialized agent knowledge, implement:
     - A method in the specialized agent class
     - Registration with the Agent Orchestrator
     - Tool definition that includes agent capability requirements

### 3.4 Tool Definition Format Compatibility

Tools must support both OpenAI and Anthropic APIs:

- **OpenAI Format**:
  ```json
  {
    "type": "function",
    "function": {
      "name": "tool_id",
      "arguments": "{\"param1\":\"value\",\"param2\":42}"
    }
  }
  ```

- **Anthropic Format** (in the response):
  ```json
  {
    "tool": "tool_id",
    "parameters": {
      "param1": "value",
      "param2": 42
    }
  }
  ```

Your tool's `get_tool_definition()` should provide the schema that accommodates both APIs.

## 4. Testing Checklist

- [ ] Tool class correctly extends `MPAI_Base_Tool`
- [ ] All required methods are implemented
- [ ] Tool is properly registered in `MPAI_Tool_Registry`
- [ ] Context Manager has correct tool definition
- [ ] System prompt includes clear documentation for the tool
- [ ] Tool executes successfully with valid parameters
- [ ] Tool handles invalid parameters gracefully
- [ ] Client-side processing works if needed
- [ ] Tool integrates with the agent system if applicable
- [ ] Tests cover main functionality and edge cases
- [ ] Both OpenAI and Anthropic format compatibility verified
- [ ] Error handling is comprehensive and user-friendly

## 5. Example: XML Blog Post Tool

The recently implemented XML Blog Post formatting feature demonstrates the full integration pattern:

1. **Backend Components**:
   - `class-mpai-xml-content-parser.php`: Parses XML formatted blog content
   - Enhanced `MPAI_WP_API_Tool` with XML detection and processing

2. **Frontend Components**:
   - `mpai-blog-formatter.js`: Client-side module for detecting and handling XML blog posts

3. **Integration Points**:
   - Content markers added in `MPAI_Chat::process_message()`
   - XML processing in `MPAI_WP_API_Tool::create_post()`
   - Frontend integration with "Create Post" button through `MPAI_BlogFormatter.addCreatePostButton()`

## 6. Security Considerations

When implementing tools, follow these security best practices:

1. **Input Validation**:
   - Always validate all input parameters
   - Use type checking and sanitization
   - Never trust user input, even when filtered through the AI

2. **Permission Verification**:
   - Check user capabilities before performing actions
   - Implement the principle of least privilege
   - Document required permissions in tool descriptions

3. **Data Handling**:
   - Sanitize output to prevent XSS
   - Use prepared statements for database queries
   - Avoid exposing sensitive information

4. **Error Handling**:
   - Provide informative but secure error messages
   - Log detailed errors for debugging
   - Gracefully handle unexpected inputs

5. **Tool Access Control**:
   - Consider which users should have access to which tools
   - Implement tool-specific permission checks
   - Use WordPress capabilities system for access control

## 7. Recommendations for Future Development

1. **Standardize Tool Response Format**:
   - Implement a consistent response structure across all tools
   - Include: `success` flag, `tool` name, `result` data, `error` messages if needed

2. **Tool Registration Enhancements**:
   - Add auto-discovery of tool implementations
   - Support tool categories for better organization

3. **Improve Client-Side Processing**:
   - Create a standard client-side component registry for tool-specific UI elements
   - Implement standardized formatting handlers per tool type

4. **Error Handling Improvements**:
   - Add detailed error logging for tool execution failures
   - Implement retry mechanisms for transient errors

5. **Performance Optimization**:
   - Lazy-load tool classes only when needed
   - Cache tool definitions to reduce overhead

6. **Agent-Tool Interaction Enhancements**:
   - Develop a formal tool capability requirements system
   - Implement tool chaining for complex operations
   - Support cooperative multi-agent tool usage

## 8. Documentation Updates

When adding a new tool:

1. **Update System Map**:
   - Add the new tool to `/docs/current/system-map.md`
   - Document its relationships with other components

2. **Update Main Documentation**:
   - Add tool documentation to the main README
   - Create specific documentation if the tool is complex

3. **Update Changelog**:
   - Add an entry to CHANGELOG.md
   - Reference the new tool and its capabilities

4. **Consider User Documentation**:
   - Update user-facing documentation if applicable
   - Create examples of how the tool can be used

## 9. Conclusion

This tool implementation map provides a comprehensive guide for developers to extend the MemberPress AI Assistant with new tools. By following these guidelines, you'll ensure proper integration with the existing architecture and maintain consistency across the system.

The MemberPress AI Assistant's modular architecture with its Tool Registry, Context Manager, and Agent System facilitates easy extension with new tools. Each new tool follows a standardized pattern for implementation, registration, and integration with both backend and frontend components.

For additional details, refer to the system map in `/docs/current/system-map.md` and other documentation in the `/docs/current/` directory.