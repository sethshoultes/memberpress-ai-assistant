# CachedToolWrapper

The `CachedToolWrapper` is a service that provides caching functionality for tool execution results in the MemberPress AI Assistant system. It wraps tool execution with caching capabilities, improving performance by avoiding redundant tool executions.

## Features

- **Tool Execution Caching**: Cache tool execution results based on input parameters
- **Configurable TTL**: Set different Time-To-Live (TTL) values for different tool types and operations
- **Selective Cache Bypassing**: Configure specific operations that should not be cached
- **Cache Invalidation**: Methods to invalidate cache for specific tools, operations, or all tools
- **Logging**: Comprehensive logging for cache hits, misses, and invalidations

## Usage

### Basic Usage

```php
// Get the tool registry
$toolRegistry = ToolRegistry::getInstance();

// Get the cache service
$cacheService = $container->make('cache');

// Get the logger
$logger = $container->make('logger');

// Create the cached tool wrapper
$cachedToolWrapper = new CachedToolWrapper($cacheService, $logger);

// Get a tool
$contentTool = $toolRegistry->getTool('content');

// Define parameters for the tool
$parameters = [
    'operation' => 'format_content',
    'content' => 'This is some example content to format.',
    'format_type' => 'markdown',
];

// Execute the tool with caching
$result = $cachedToolWrapper->execute($contentTool, $parameters);
```

### Configuring TTL

You can configure different TTL values for different tool types and operations:

```php
// Set default TTL for all tools (10 minutes)
$cachedToolWrapper->setDefaultTtl(600);

// Set TTL configuration for a specific tool type
$cachedToolWrapper->setTtlConfig('ContentTool', [
    'default' => 300,             // Default TTL for ContentTool operations (5 minutes)
    'format_content' => 600,      // TTL for format_content operation (10 minutes)
    'optimize_seo' => 1800,       // TTL for optimize_seo operation (30 minutes)
]);
```

### Configuring Non-Cacheable Operations

Some operations should not be cached, especially write operations that modify data:

```php
// Add a non-cacheable operation
$cachedToolWrapper->addNonCacheableOperation('WordPressTool.update_post');

// Remove a non-cacheable operation
$cachedToolWrapper->removeNonCacheableOperation('WordPressTool.update_post');

// Set all non-cacheable operations
$cachedToolWrapper->setNonCacheableOperations([
    'ContentTool.manage_revisions',
    'MemberPressTool.create_membership',
    'MemberPressTool.update_membership',
    'MemberPressTool.delete_membership',
    'WordPressTool.create_post',
    'WordPressTool.update_post',
    'WordPressTool.delete_post',
]);
```

### Cache Invalidation

You can invalidate cache entries when data changes:

```php
// Invalidate cache for a specific tool type
$cachedToolWrapper->invalidateToolCache('ContentTool');

// Invalidate cache for a specific operation
$cachedToolWrapper->invalidateOperationCache('MemberPressTool', 'get_membership');

// Invalidate all tool caches
$cachedToolWrapper->invalidateAllToolCaches();
```

### Debug Mode

You can enable debug mode to bypass caching during development:

```php
// Enable debug mode
$cachedToolWrapper->setDebug(true);

// Disable debug mode
$cachedToolWrapper->setDebug(false);
```

## Integration with DI Container

The `CachedToolWrapper` is automatically registered with the DI container when placed in the `Services` directory. You can access it from the container:

```php
// Get the cached tool wrapper from the container
$cachedToolWrapper = $container->make('cached_tool_wrapper');
```

## Benefits

Using the `CachedToolWrapper` provides several benefits:

1. **Improved Performance**: Avoid redundant tool executions for the same parameters
2. **Reduced API Calls**: For tools that make external API calls, caching reduces the number of calls
3. **Lower Resource Usage**: Less CPU and memory usage for expensive operations
4. **Configurable Caching**: Fine-grained control over what gets cached and for how long
5. **Transparent Integration**: Existing code doesn't need to be modified to benefit from caching

## Implementation Details

The `CachedToolWrapper` uses the `CacheService` for storing and retrieving cached data. It generates cache keys based on the tool name, operation, and input parameters. The wrapper also includes logging for cache hits, misses, and invalidations to help with debugging and monitoring.

## Example

See the `CachedToolExample.php` file for a complete example of how to use the `CachedToolWrapper`.

## Testing

See the `CachedToolWrapperManualTest.php` file for examples of how to test the `CachedToolWrapper`.