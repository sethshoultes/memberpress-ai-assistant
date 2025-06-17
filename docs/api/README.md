# MemberPress Copilot API Documentation

## Overview

The MemberPress Copilot provides a comprehensive API system built on modern PHP architecture patterns. The API is designed around four main layers that work together to provide intelligent membership management capabilities.

## API Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                      Public API Layer                       │
│  ┌─────────────────┐  ┌──────────────────┐  ┌─────────────┐ │
│  │  REST API       │  │   AJAX API       │  │ JavaScript  │ │
│  │  Endpoints      │  │   Handlers       │  │ Client API  │ │
│  └─────────────────┘  └──────────────────┘  └─────────────┘ │
└─────────────────────────────────────────────────────────────┘
┌─────────────────────────────────────────────────────────────┐
│                    Core Component APIs                      │
│  ┌─────────────────┐  ┌──────────────────┐  ┌─────────────┐ │
│  │   Agent API     │  │    Tool API      │  │ Service API │ │
│  │   (Execution)   │  │   (Operations)   │  │ (Business)  │ │
│  └─────────────────┘  └──────────────────┘  └─────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

## Core API Components

| Component | Purpose | Extension Points |
|-----------|---------|------------------|
| **[Agent API](agent-api.md)** | AI agent creation and orchestration | Custom agents, scoring logic |
| **[Tool API](tool-api.md)** | Modular operations with caching | Custom tools, parameter validation |
| **[Service API](service-api.md)** | Business logic and integrations | Custom services, hooks |
| **[REST API](rest-endpoints.md)** | HTTP endpoints and client libraries | Custom endpoints, middleware |

## Core Concepts

### Dependency Injection

The API uses a ServiceLocator pattern for dependency management:

```php
// Access the global service locator
global $mpai_service_locator;

// Get services
$memberpress = $mpai_service_locator->get('memberpress');
$orchestrator = $mpai_service_locator->get('orchestrator');

// Register custom services
$mpai_service_locator->register('my_service', function() {
    return new MyCustomService();
});
```

### Agent-Based Processing

All user requests are processed through the agent system:

```php
// Manual agent orchestration
$orchestrator = $mpai_service_locator->get('orchestrator');
$response = $orchestrator->processRequest([
    'message' => 'Create a new membership',
    'context' => ['user_id' => 123]
]);
```

### Tool-Based Operations

Tools provide modular, cacheable operations:

```php
// Direct tool execution
$tool_registry = $mpai_service_locator->get('tool_registry');
$memberpress_tool = $tool_registry->get('memberpress_tool');

$result = $memberpress_tool->execute([
    'action' => 'create_membership',
    'name' => 'Premium Plan',
    'price' => 29.99
]);
```

## Quick Start

### 1. Basic Integration

```php
<?php
// Check if MemberPress Copilot is active
if (class_exists('MemberpressAiAssistant\DI\ServiceLocator')) {
    global $mpai_service_locator;
    
    // Use the chat interface service
    $chat_service = $mpai_service_locator->get('chat_interface');
    $response = $chat_service->processMessage('Show me membership stats');
    
    echo $response['message'];
}
```

### 2. Creating a Custom Agent

```php
<?php
namespace YourPlugin\Agents;

use MemberpressAiAssistant\Interfaces\AgentInterface;

class MyCustomAgent implements AgentInterface {
    public function canHandle(array $context): bool {
        return strpos($context['message'], 'custom') !== false;
    }
    
    public function execute(array $context): array {
        return [
            'success' => true,
            'response' => 'Custom response from my agent'
        ];
    }
    
    public function getScore(array $context): float {
        return $this->canHandle($context) ? 0.8 : 0.0;
    }
}

// Register the agent
add_action('init', function() {
    global $mpai_service_locator;
    $agent_registry = $mpai_service_locator->get('agent_registry');
    $agent_registry->register('my_custom_agent', new MyCustomAgent());
});
```

### 3. Creating a Custom Tool

```php
<?php
namespace YourPlugin\Tools;

use MemberpressAiAssistant\Interfaces\ToolInterface;

class MyCustomTool implements ToolInterface {
    public function execute(array $parameters): array {
        return [
            'success' => true,
            'result' => 'Tool execution completed',
            'data' => $parameters
        ];
    }
    
    public function getSchema(): array {
        return [
            'name' => 'my_custom_tool',
            'description' => 'Performs custom operations',
            'parameters' => [
                'input' => [
                    'type' => 'string',
                    'description' => 'Input parameter',
                    'required' => true
                ]
            ]
        ];
    }
}

// Register the tool
add_action('init', function() {
    global $mpai_service_locator;
    $tool_registry = $mpai_service_locator->get('tool_registry');
    $tool_registry->register('my_custom_tool', new MyCustomTool());
});
```

## Extension Points

### WordPress Hooks

```php
// Modify agent behavior
add_filter('mpai_agent_context', function($context) {
    $context['custom_data'] = 'value';
    return $context;
});

// Add custom tool validation
add_filter('mpai_tool_parameters', function($parameters, $tool_name) {
    if ($tool_name === 'my_tool') {
        // Custom validation logic
    }
    return $parameters;
}, 10, 2);

// Custom service registration
add_action('mpai_services_registered', function($service_locator) {
    $service_locator->register('my_service', new MyService());
});
```

### JavaScript Events

```javascript
// Listen for chat responses
document.addEventListener('mpai:chat_response', function(event) {
    console.log('Chat response:', event.detail);
});

// Custom chat processing
document.addEventListener('mpai:before_send', function(event) {
    // Modify message before sending
    event.detail.message += ' [custom suffix]';
});
```

## Security

### Authentication & Authorization

- All admin operations require `manage_options` capability
- User-specific operations check appropriate permissions
- API keys are validated for external integrations
- Nonce verification for all form submissions

### Data Sanitization

```php
// Input sanitization is automatic
$sanitized_input = $this->sanitize_input($user_input);

// Output escaping for display
echo esc_html($response_message);
```

## Performance

### Caching Strategy

- **Tool Results**: Cached using CachedToolWrapper
- **Agent Responses**: Cached based on context hash
- **Service Data**: WordPress transients for temporary data
- **Database Queries**: Object caching where available

### Optimization Patterns

```php
// Lazy loading of services
$service = $mpai_service_locator->get('expensive_service');

// Batch processing for multiple operations
$tool->executeBatch($multiple_requests);

// Memory management for large datasets
$result = $service->processLargeDataset($data, ['batch_size' => 100]);
```

## Development

### Testing

```bash
# Run API tests
composer test:api

# Test specific agent
./vendor/bin/phpunit tests/Unit/Agents/MyCustomAgentTest.php

# Integration tests
composer test:integration
```

### Debugging

```php
// Enable debug mode
define('MPAI_DEBUG_MODE', true);

// Check service registration
$definitions = $mpai_service_locator->getDefinitions();
var_dump(array_keys($definitions));

// Monitor agent selection
add_action('mpai_agent_selected', function($agent_name, $score) {
    error_log("Selected agent: {$agent_name} (score: {$score})");
}, 10, 2);
```

## API Reference

- **[Agent API](agent-api.md)** - Creating and managing AI agents
- **[Tool API](tool-api.md)** - Building modular operations
- **[Service API](service-api.md)** - Business logic services
- **[REST Endpoints](rest-endpoints.md)** - HTTP API and client libraries

## Support

For API questions and support:

- 📚 Check the detailed API documentation in this directory
- 🐛 Report issues on GitHub
- 💬 Join the developer community forum
- 📧 Contact enterprise support for custom development

---

**Next Steps:**
- Review the [Agent API documentation](agent-api.md) for creating custom agents
- Explore the [Tool API documentation](tool-api.md) for building operations
- Check out [REST endpoints](rest-endpoints.md) for client integration