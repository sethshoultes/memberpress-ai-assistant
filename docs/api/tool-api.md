# Tool API Documentation

## Overview

The Tool API provides a standardized interface for creating reusable operations in the MemberPress Copilot. Tools are stateless, cacheable components that perform specific tasks and can be used by agents to fulfill user requests.

## Core Concepts

### Tool Interface

All tools must implement the `ToolInterface` which defines the core contract:

```php
namespace MemberpressAiAssistant\Interfaces;

interface ToolInterface {
    public function getToolName(): string;
    public function getToolDescription(): string;
    public function getToolDefinition(): array;
    public function execute(array $parameters): array;
}
```

### Abstract Tool Base Class

The `AbstractTool` class provides a robust foundation with:

- **Parameter Validation**: JSON Schema-based parameter validation
- **Caching Support**: Integration with the caching system
- **Error Handling**: Comprehensive error handling and logging
- **Normalization**: Parameter normalization and type coercion

## Creating Custom Tools

### Step 1: Extend AbstractTool

```php
<?php
namespace MyPlugin\Tools;

use MemberpressAiAssistant\Abstracts\AbstractTool;

class CustomTool extends AbstractTool {
    public function __construct($logger = null) {
        parent::__construct(
            'custom_operation',
            'Performs custom operations for my plugin',
            $logger
        );
    }
}
```

### Step 2: Define Parameters

Tools use JSON Schema for parameter definition:

```php
protected function getParameters(): array {
    return [
        'type' => 'object',
        'properties' => [
            'operation' => [
                'type' => 'string',
                'enum' => ['create', 'read', 'update', 'delete'],
                'description' => 'The operation to perform'
            ],
            'entity_id' => [
                'type' => 'integer',
                'minimum' => 1,
                'description' => 'The ID of the entity to operate on'
            ],
            'data' => [
                'type' => 'object',
                'properties' => [
                    'title' => ['type' => 'string'],
                    'description' => ['type' => 'string'],
                    'active' => ['type' => 'boolean', 'default' => true]
                ],
                'description' => 'Data for the operation'
            ]
        ],
        'required' => ['operation'],
        'additionalProperties' => false
    ];
}

protected function getParameterSchema(): array {
    return $this->getParameters();
}
```

### Step 3: Implement Execution Logic

```php
protected function executeInternal(array $parameters): array {
    $operation = $parameters['operation'];
    
    switch ($operation) {
        case 'create':
            return $this->createEntity($parameters);
        case 'read':
            return $this->readEntity($parameters);
        case 'update':
            return $this->updateEntity($parameters);
        case 'delete':
            return $this->deleteEntity($parameters);
        default:
            return [
                'success' => false,
                'error' => 'unsupported_operation',
                'message' => "Operation '{$operation}' is not supported"
            ];
    }
}

private function createEntity(array $parameters): array {
    try {
        $data = $parameters['data'] ?? [];
        
        // Validate required fields for creation
        if (empty($data['title'])) {
            return [
                'success' => false,
                'error' => 'missing_title',
                'message' => 'Title is required for entity creation'
            ];
        }
        
        // Perform creation logic
        $entity_id = $this->performCreation($data);
        
        return [
            'success' => true,
            'data' => [
                'entity_id' => $entity_id,
                'message' => 'Entity created successfully'
            ]
        ];
        
    } catch (\Exception $e) {
        return [
            'success' => false,
            'error' => 'creation_failed',
            'message' => 'Failed to create entity: ' . $e->getMessage()
        ];
    }
}
```

## Parameter Validation

### JSON Schema Validation

The system uses JSON Schema for robust parameter validation:

```php
protected function getParameterSchema(): array {
    return [
        'type' => 'object',
        'properties' => [
            'user_id' => [
                'type' => 'integer',
                'minimum' => 1,
                'description' => 'WordPress user ID'
            ],
            'email' => [
                'type' => 'string',
                'format' => 'email',
                'description' => 'User email address'
            ],
            'membership_level' => [
                'type' => 'string',
                'enum' => ['basic', 'premium', 'enterprise'],
                'default' => 'basic',
                'description' => 'Membership level'
            ],
            'expiration_date' => [
                'type' => 'string',
                'format' => 'date-time',
                'description' => 'Membership expiration date'
            ],
            'metadata' => [
                'type' => 'object',
                'additionalProperties' => true,
                'description' => 'Additional metadata'
            ]
        ],
        'required' => ['user_id', 'email'],
        'additionalProperties' => false
    ];
}
```

### Custom Validation Logic

For complex validation scenarios:

```php
protected function validateParameters(array $parameters) {
    $errors = [];
    
    // Custom validation logic
    if (isset($parameters['user_id'])) {
        $user = get_user_by('id', $parameters['user_id']);
        if (!$user) {
            $errors[] = 'User not found';
        }
    }
    
    if (isset($parameters['expiration_date'])) {
        $expiration = strtotime($parameters['expiration_date']);
        if ($expiration < time()) {
            $errors[] = 'Expiration date cannot be in the past';
        }
    }
    
    return empty($errors) ? true : $errors;
}
```

## Tool Registration

### Using Tool Registry

```php
global $mpai_service_locator;
$tool_registry = $mpai_service_locator->get('tool_registry');

// Register a tool
$custom_tool = new CustomTool();
$tool_registry->register($custom_tool);

// Register with caching
$cached_tool = new CachedToolWrapper($custom_tool, $cache_service);
$tool_registry->register($cached_tool);
```

### Service Provider Registration

```php
<?php
namespace MyPlugin\DI\Providers;

use MemberpressAiAssistant\DI\ServiceProvider;

class ToolServiceProvider extends ServiceProvider {
    public function register($container): void {
        // Register the tool
        $container->register('custom_tool', function() {
            return new CustomTool();
        });
        
        // Register with the tool registry
        $container->register('tool_registry_registration', function($container) {
            $tool_registry = $container->get('tool_registry');
            $custom_tool = $container->get('custom_tool');
            
            $tool_registry->register($custom_tool);
        });
    }
}
```

## Caching System

### Cached Tool Wrapper

The `CachedToolWrapper` provides transparent caching for tool operations:

```php
use MemberpressAiAssistant\Services\CachedToolWrapper;

// Wrap a tool with caching
$base_tool = new MemberPressTool();
$cached_tool = new CachedToolWrapper(
    $base_tool,
    $cache_service,
    $cache_strategy,
    $cache_warmer,
    $config_service
);

// Execute with caching
$result = $cached_tool->execute($parameters);
```

### Cache Configuration

```php
// Configure caching behavior
$cached_tool->setCacheOptions([
    'ttl' => 300, // 5 minutes
    'cache_key_prefix' => 'custom_tool_',
    'bypass_cache_operations' => ['delete', 'update'],
    'cache_warm_operations' => ['list', 'search']
]);
```

### Cache Key Generation

```php
protected function generateCacheKey(array $parameters): string {
    // Create a stable cache key from parameters
    $key_data = [
        'tool' => $this->getToolName(),
        'operation' => $parameters['operation'] ?? 'default',
        'params' => $this->normalizeForCaching($parameters)
    ];
    
    return 'tool_' . md5(json_encode($key_data));
}

private function normalizeForCaching(array $parameters): array {
    // Remove non-cacheable parameters
    $cacheable = $parameters;
    unset($cacheable['timestamp'], $cacheable['nonce']);
    
    // Sort for consistent key generation
    ksort($cacheable);
    
    return $cacheable;
}
```

## Built-in Tools

### MemberPressTool

Handles MemberPress-specific operations:

```php
// Usage example
$memberpress_tool = $tool_registry->get('memberpress');

$result = $memberpress_tool->execute([
    'operation' => 'create_membership',
    'name' => 'Premium Membership',
    'price' => 29.99,
    'terms' => 'monthly',
    'access_rules' => [
        [
            'content_type' => 'post', 
            'content_ids' => [123],
            'rule_type' => 'include'
        ]
    ]
]);
```

**Supported Operations:**
- `create_membership`, `update_membership`, `delete_membership`
- `list_memberships`, `get_membership`
- `create_access_rule`, `update_access_rule`, `delete_access_rule`
- `manage_pricing` - Advanced pricing management
- `associate_user_with_membership`, `disassociate_user_from_membership`
- `get_user_memberships`, `update_user_role`, `get_user_permissions`
- Batch operations: `batch_*` versions of all operations

**Advanced Usage with Batch Processing:**

```php
// Batch create multiple memberships
$result = $memberpress_tool->execute([
    'operation' => 'batch_create_memberships',
    'batch_params' => [
        [
            'name' => 'Basic Plan',
            'price' => 9.99,
            'terms' => 'monthly'
        ],
        [
            'name' => 'Premium Plan',
            'price' => 19.99,
            'terms' => 'monthly'
        ]
    ]
]);
```

### ContentTool

Handles WordPress content operations and formatting:

```php
$content_tool = $tool_registry->get('content');

$result = $content_tool->execute([
    'operation' => 'format_content',
    'content' => 'Raw content here...',
    'format_type' => 'html',
    'formatting_options' => [
        'headings' => true,
        'lists' => true,
        'tables' => true,
        'code_blocks' => true
    ]
]);
```

**Supported Operations:**
- `format_content` - Content formatting and conversion
- `organize_content` - Content organization and structuring
- `manage_media` - Media file management
- `optimize_seo` - SEO optimization for content
- `manage_revisions` - Content revision management

**Advanced Content Formatting:**

```php
// Organize content into sections
$result = $content_tool->execute([
    'operation' => 'organize_content',
    'content' => 'Long content...',
    'sections' => [
        [
            'title' => 'Introduction',
            'content' => 'Intro content...',
            'order' => 1
        ],
        [
            'title' => 'Main Content',
            'content' => 'Main content...',
            'order' => 2
        ]
    ]
]);
```

### WordPressTool

Handles general WordPress operations:

```php
$wordpress_tool = $tool_registry->get('wordpress');

$result = $wordpress_tool->execute([
    'operation' => 'get_user_info',
    'user_id' => 123
]);
```

**Supported Operations:**
- `get_user_info`, `update_user_info`, `create_user`
- `get_site_info`, `get_plugin_info`
- `manage_user_roles`, `get_user_capabilities`

## Batch Processing

### Batch Operations

Tools can support batch processing for improved performance:

```php
protected function executeInternal(array $parameters): array {
    $operation = $parameters['operation'];
    
    // Check if this is a batch operation
    if (strpos($operation, 'batch_') === 0) {
        return $this->processBatchOperation($parameters);
    }
    
    // Handle single operation
    return $this->processSingleOperation($parameters);
}

private function processBatchOperation(array $parameters): array {
    $batch_operation = substr($parameters['operation'], 6); // Remove 'batch_'
    $items = $parameters['items'] ?? [];
    
    $results = [];
    $errors = [];
    
    foreach ($items as $index => $item) {
        try {
            $item_params = array_merge($parameters, $item);
            $item_params['operation'] = $batch_operation;
            
            $result = $this->processSingleOperation($item_params);
            $results[$index] = $result;
            
        } catch (\Exception $e) {
            $errors[$index] = $e->getMessage();
        }
    }
    
    return [
        'success' => empty($errors),
        'data' => [
            'results' => $results,
            'errors' => $errors,
            'processed' => count($results),
            'failed' => count($errors)
        ]
    ];
}
```

### Using BatchProcessor

```php
use MemberpressAiAssistant\Batch\BatchProcessor;

$batch_processor = new BatchProcessor();

// Add items to batch
$batch_processor->addItem('create_membership', [
    'title' => 'Basic Plan',
    'price' => 9.99
]);

$batch_processor->addItem('create_membership', [
    'title' => 'Premium Plan', 
    'price' => 19.99
]);

// Process batch
$results = $batch_processor->process($memberpress_tool);
```

## Error Handling

### Standard Error Response Format

```php
return [
    'success' => false,
    'error' => 'error_code',
    'message' => 'Human-readable error message',
    'details' => [
        'validation_errors' => $validation_errors,
        'system_error' => $exception_message
    ]
];
```

### Custom Exceptions

```php
use MemberpressAiAssistant\Exceptions\ToolException;

protected function executeInternal(array $parameters): array {
    try {
        // Tool logic here
        return $this->performOperation($parameters);
        
    } catch (ToolException $e) {
        return [
            'success' => false,
            'error' => $e->getCode(),
            'message' => $e->getMessage(),
            'details' => $e->getDetails()
        ];
    } catch (\Exception $e) {
        return [
            'success' => false,
            'error' => 'unexpected_error',
            'message' => 'An unexpected error occurred',
            'details' => ['system_message' => $e->getMessage()]
        ];
    }
}
```

## Performance Optimization

### Caching Strategies

1. **Result Caching**: Cache expensive operation results
2. **Validation Caching**: Cache parameter validation results
3. **Conditional Caching**: Cache based on operation type

```php
// Configure caching per operation
protected function getCacheConfig(string $operation): array {
    $config = [
        'read' => ['ttl' => 300, 'enabled' => true],
        'list' => ['ttl' => 180, 'enabled' => true],
        'create' => ['ttl' => 0, 'enabled' => false],
        'update' => ['ttl' => 0, 'enabled' => false],
        'delete' => ['ttl' => 0, 'enabled' => false]
    ];
    
    return $config[$operation] ?? ['ttl' => 60, 'enabled' => true];
}
```

### Lazy Loading

```php
protected function executeInternal(array $parameters): array {
    // Only load expensive resources when needed
    if ($this->requiresDatabase($parameters)) {
        $this->initializeDatabaseConnection();
    }
    
    if ($this->requiresExternalAPI($parameters)) {
        $this->initializeAPIClient();
    }
    
    return $this->performOperation($parameters);
}
```

## Testing Tools

### Unit Testing

```php
use PHPUnit\Framework\TestCase;

class CustomToolTest extends TestCase {
    private $tool;
    
    public function setUp(): void {
        $this->tool = new CustomTool();
    }
    
    public function testExecuteWithValidParameters(): void {
        $parameters = [
            'operation' => 'create',
            'data' => ['title' => 'Test Entity']
        ];
        
        $result = $this->tool->execute($parameters);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
    }
    
    public function testExecuteWithInvalidParameters(): void {
        $parameters = [
            'operation' => 'invalid_operation'
        ];
        
        $result = $this->tool->execute($parameters);
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }
}
```

### Integration Testing

```php
class ToolIntegrationTest extends TestCase {
    public function testToolRegistration(): void {
        global $mpai_service_locator;
        $tool_registry = $mpai_service_locator->get('tool_registry');
        
        $custom_tool = new CustomTool();
        $tool_registry->register($custom_tool);
        
        $registered_tool = $tool_registry->get('custom_operation');
        $this->assertInstanceOf(CustomTool::class, $registered_tool);
    }
    
    public function testCachedToolWrapper(): void {
        $base_tool = new CustomTool();
        $cached_tool = new CachedToolWrapper($base_tool, $cache_service);
        
        $parameters = ['operation' => 'read', 'id' => 1];
        
        // First call should execute and cache
        $result1 = $cached_tool->execute($parameters);
        
        // Second call should return cached result
        $result2 = $cached_tool->execute($parameters);
        
        $this->assertEquals($result1, $result2);
    }
}
```

## Best Practices

### Tool Design
1. **Stateless Design**: Tools should be stateless and reusable
2. **Single Responsibility**: Each tool should handle a specific domain
3. **Parameter Validation**: Always validate input parameters
4. **Error Handling**: Provide clear, actionable error messages

### Performance
1. **Cache Results**: Cache expensive operations appropriately
2. **Validate Early**: Fail fast with parameter validation
3. **Lazy Loading**: Load resources only when needed
4. **Batch Operations**: Support batch processing for bulk operations

### Integration
1. **Use Service Locator**: Access dependencies through the service locator
2. **Follow Standards**: Use consistent response formats and error codes
3. **Log Operations**: Log tool execution for debugging and monitoring
4. **Handle Dependencies**: Properly inject and manage dependencies

### Security
1. **Validate Input**: Always validate and sanitize input parameters
2. **Check Permissions**: Verify user permissions before operations
3. **Escape Output**: Properly escape output data
4. **Secure Caching**: Don't cache sensitive information

This Tool API provides a powerful and flexible foundation for creating reusable operations within the MemberPress Copilot system.