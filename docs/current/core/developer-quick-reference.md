# Developer Quick Reference

**Status:** ✅ Maintained  
**Version:** 1.0.0  
**Last Updated:** April 2024  
**Type:** Reference

This document provides a quick reference guide for common development tasks in the MemberPress AI Assistant plugin. Use this guide to quickly find relevant documentation, code patterns, and best practices.

## Table of Contents

- [Getting Started](#getting-started)
- [Architecture Overview](#architecture-overview)
- [Common Development Tasks](#common-development-tasks)
- [Code Patterns](#code-patterns)
- [Testing and Debugging](#testing-and-debugging)
- [Documentation](#documentation)
- [Extension Points](#extension-points)

## Getting Started

### Environment Setup

1. Clone the repository
2. Install WordPress and MemberPress if needed
3. Review the [Developer Guide](developer-guide.md) for detailed setup instructions

### Key Resources

- [_0_START_HERE_.md](../../_0_START_HERE_.md) - Primary entry point for new developers
- [system-map.md](system-map.md) - Complete system architecture overview
- [implementation-status.md](implementation-status.md) - Current status of all features

## Architecture Overview

The MemberPress AI Assistant is organized into these major components:

- **Core System**: Base plugin functionality
- **Agent System**: AI agent implementation and orchestration
- **Tool System**: Tools that can be executed by agents
- **XML Content System**: Structured content generation
- **UI Components**: Admin and frontend interfaces

For detailed architecture:
- [System Map](system-map.md)
- [Agent System](unified-agent-system.md)
- [Tool Implementation](tool-implementation-map.md)
- [XML Content System](unified-xml-content-system.md)

## Common Development Tasks

### Creating a New Agent

1. Extend `MPAI_Base_Agent` class and implement `MPAI_Agent` interface
2. Register your agent with the Agent Orchestrator
3. Add any specialized methods needed for your agent's domain

```php
class MPAI_My_Custom_Agent extends MPAI_Base_Agent implements MPAI_Agent {
    public function get_name() {
        return 'My Custom Agent';
    }
    
    public function can_handle_request($request) {
        // Determine if this agent can handle the request
        return strpos($request, 'my_domain') !== false;
    }
    
    protected function process_request($request) {
        // Process the request and return a response
        return "Response from My Custom Agent";
    }
}
```

Documentation: [Agent System](../agent-system/comprehensive-agent-system-guide.md)

### Creating a New Tool

1. Create a new class extending `MPAI_Base_Tool`
2. Implement required methods (get_name, get_description, get_tool_definition, execute)
3. Register your tool with the Tool Registry

```php
class MPAI_My_Custom_Tool extends MPAI_Base_Tool {
    public function get_name() {
        return 'My Custom Tool';
    }
    
    public function get_description() {
        return 'Description of what my tool does';
    }
    
    public function get_tool_definition() {
        return [
            'name' => 'my_custom_tool',
            'description' => 'Description for AI',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'param1' => [
                        'type' => 'string',
                        'description' => 'Description of parameter'
                    ]
                ]
            ]
        ];
    }
    
    public function execute($parameters) {
        // Tool implementation
        return ['result' => 'Tool executed successfully'];
    }
}
```

Documentation: [Tool Implementation Map](tool-implementation-map.md)

### Updating the System Prompt

1. Locate the `get_system_message()` method in `MPAI_Chat` class
2. Add your tool documentation or special instructions

Documentation: [Chat System](../../../includes/class-mpai-chat.php)

### Working with XML Content

1. Use the `MPAI_XML_Content_Parser` class to parse XML content
2. Follow the XML format specification for structured content

```php
$xml_parser = new MPAI_XML_Content_Parser();
$parsed_data = $xml_parser->parse_xml_blog_post($xml_content);

if ($parsed_data) {
    // $parsed_data contains title, content, excerpt, etc.
}
```

Documentation: [XML Content System](unified-xml-content-system.md)

## Code Patterns

### Error Handling

```php
try {
    // Risky operation
} catch (Exception $e) {
    error_log('MPAI: Error in component - ' . $e->getMessage());
    return new WP_Error('mpai_error', $e->getMessage());
}
```

### Logging

```php
// PHP Server-side logging
error_log('MPAI: Informational message about operation');

// JavaScript Client-side logging
if (window.mpaiLogger) {
    window.mpaiLogger.info('Operation completed', 'category');
    window.mpaiLogger.error('Operation failed', 'category', { details: 'Error info' });
}
```

Documentation: [Console Logging System](console-logging-system.md)

### Nonce Verification

```php
if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mpai_action')) {
    wp_send_json_error(['message' => 'Security check failed']);
}
```

### Tool Call Detection

```php
$tool_calls = $this->extract_tool_calls($ai_response);
foreach ($tool_calls as $tool_call) {
    $result = $this->context_manager->execute_tool_call($tool_call);
    // Process tool results
}
```

Documentation: [Tool Call Detection](tool-call-detection.md)

## Testing and Debugging

### Manual Testing

Follow the test procedures outlined in the test directory:
- [Test Procedures](../../../test/test-procedures.md)

### Common Debug Techniques

1. Enable WordPress debugging:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

2. Use console logging for JavaScript debugging:
```javascript
window.mpaiLogger.startTimer('operation_name');
// Operation to time
window.mpaiLogger.endTimer('operation_name');
```

3. Test tool execution directly:
```php
$tool = new MPAI_My_Custom_Tool();
$result = $tool->execute(['param1' => 'value1']);
```

## Documentation

### Creating New Documentation

1. Choose the appropriate template from [templates](../../templates/)
2. Follow the documentation standards in [the template README](../../templates/README.md)
3. Include status indicators, version, and last updated date
4. Add table of contents for longer documents
5. Update the [Documentation Map](documentation-map.md) if needed

### Updating Existing Documentation

1. Maintain the existing format and structure
2. Update the "Last Updated" date
3. Add version history entries if significant changes are made
4. Verify all links and cross-references

## Extension Points

### Key Hooks

- `mpai_before_process_message` - Before processing a chat message
- `mpai_after_process_message` - After processing a chat message
- `mpai_before_tool_execution` - Before executing a tool
- `mpai_after_tool_execution` - After executing a tool

### Example Filter Usage

```php
add_filter('mpai_system_message', 'my_custom_system_message', 10, 1);

function my_custom_system_message($system_message) {
    // Modify system message
    return $system_message . "\nAdditional instructions...";
}
```

### Example Action Usage

```php
add_action('mpai_after_tool_execution', 'my_custom_tool_hook', 10, 3);

function my_custom_tool_hook($tool_name, $parameters, $result) {
    // Do something after tool execution
}
```

## Related Documentation

- [System Map](system-map.md) - System architecture overview
- [Tool Implementation Map](tool-implementation-map.md) - Guide for implementing tools
- [Agent System Documentation](../agent-system/comprehensive-agent-system-guide.md) - Comprehensive agent system guide
- [XML Content System](unified-xml-content-system.md) - XML content format and usage