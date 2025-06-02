<?php
/**
 * Cached Tool Wrapper
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Services;

use MemberpressAiAssistant\Abstracts\AbstractService;
use MemberpressAiAssistant\Interfaces\ToolInterface;
use MemberpressAiAssistant\Cache\AdvancedCacheStrategy;
use MemberpressAiAssistant\Cache\CacheWarmer;

/**
 * Service for caching tool execution results
 *
 * Provides a wrapper around tool execution that caches results based on input parameters,
 * with configurable TTL for different tool types and selective cache bypassing for
 * non-cacheable operations.
 *
 * Integrates with AdvancedCacheStrategy for TTL determination, CacheWarmer for proactive
 * cache warming, and ConfigurationService for centralized configuration management.
 */
class CachedToolWrapper extends AbstractService {
    /**
     * Cache service instance
     *
     * @var CacheService
     */
    protected $cacheService;

    /**
     * Logger instance
     *
     * @var mixed
     */
    protected $logger;

    /**
     * Advanced cache strategy instance
     *
     * @var AdvancedCacheStrategy
     */
    protected $cacheStrategy;

    /**
     * Cache warmer instance
     *
     * @var CacheWarmer
     */
    protected $cacheWarmer;

    /**
     * Configuration service instance
     *
     * @var ConfigurationService
     */
    protected $configService;

    /**
     * Default TTL in seconds (5 minutes)
     *
     * @var int
     */
    protected $defaultTtl = 300;

    /**
     * Debug mode flag
     *
     * @var bool
     */
    protected $debug = false;

    /**
     * Constructor
     *
     * @param CacheService $cacheService Cache service instance
     * @param AdvancedCacheStrategy $cacheStrategy Advanced cache strategy instance
     * @param CacheWarmer $cacheWarmer Cache warmer instance
     * @param ConfigurationService $configService Configuration service instance
     * @param mixed $logger Logger instance
     */
    public function __construct(
        CacheService $cacheService,
        AdvancedCacheStrategy $cacheStrategy,
        CacheWarmer $cacheWarmer,
        ConfigurationService $configService,
        $logger = null
    ) {
        // Call parent constructor with a name for this service
        parent::__construct($logger);
        
        $this->cacheService = $cacheService;
        $this->cacheStrategy = $cacheStrategy;
        $this->cacheWarmer = $cacheWarmer;
        $this->configService = $configService;

        // Set default TTL from configuration service
        $this->defaultTtl = $this->configService->getDefaultTtl();
    }

    /**
     * {@inheritdoc}
     */
    public function register($container): void {
        // Register this service with the container
        $container->register('cached_tool_wrapper', function() {
            return $this;
        });

        // Log registration
        $this->log('Cached tool wrapper service registered');
    }

    /**
     * {@inheritdoc}
     */
    public function boot(): void {
        parent::boot();

        // Log boot
        $this->log('Cached tool wrapper service booted');
    }

    /**
     * Set the default TTL configuration for different tool types
     *
     * @deprecated Use ConfigurationService instead
     * @return void
     */
    protected function setDefaultTtlConfig(): void {
        $this->log('setDefaultTtlConfig is deprecated. Use ConfigurationService instead.', [
            'level' => 'warning'
        ]);
        // No-op as this is now handled by ConfigurationService
    }

    /**
     * Set the default non-cacheable operations
     *
     * @deprecated Use ConfigurationService instead
     * @return void
     */
    protected function setDefaultNonCacheableOperations(): void {
        $this->log('setDefaultNonCacheableOperations is deprecated. Use ConfigurationService instead.', [
            'level' => 'warning'
        ]);
        // No-op as this is now handled by ConfigurationService
    }

    /**
     * Set the TTL configuration for a specific tool type
     *
     * @deprecated Use ConfigurationService::setToolTtlConfig() instead
     * @param string $toolType The tool type (class name without namespace)
     * @param array $config TTL configuration for the tool type
     * @return self
     */
    public function setTtlConfig(string $toolType, array $config): self {
        $this->log('setTtlConfig is deprecated. Use ConfigurationService::setToolTtlConfig() instead.', [
            'level' => 'warning'
        ]);
        $this->configService->setToolTtlConfig($toolType, $config);
        return $this;
    }

    /**
     * Set the default TTL for all tool types
     *
     * @deprecated Use ConfigurationService::setDefaultTtl() instead
     * @param int $ttl TTL in seconds
     * @return self
     */
    public function setDefaultTtl(int $ttl): self {
        $this->log('setDefaultTtl is deprecated. Use ConfigurationService::setDefaultTtl() instead.', [
            'level' => 'warning'
        ]);
        $this->configService->setDefaultTtl($ttl);
        $this->defaultTtl = $ttl;
        return $this;
    }

    /**
     * Add a non-cacheable operation
     *
     * @deprecated Use ConfigurationService::addNonCacheableOperation() instead
     * @param string $operation The operation in format "ToolType.operation_name"
     * @return self
     */
    public function addNonCacheableOperation(string $operation): self {
        $this->log('addNonCacheableOperation is deprecated. Use ConfigurationService::addNonCacheableOperation() instead.', [
            'level' => 'warning'
        ]);
        $this->configService->addNonCacheableOperation($operation);
        return $this;
    }

    /**
     * Remove a non-cacheable operation
     *
     * @deprecated Use ConfigurationService::removeNonCacheableOperation() instead
     * @param string $operation The operation in format "ToolType.operation_name"
     * @return self
     */
    public function removeNonCacheableOperation(string $operation): self {
        $this->log('removeNonCacheableOperation is deprecated. Use ConfigurationService::removeNonCacheableOperation() instead.', [
            'level' => 'warning'
        ]);
        $this->configService->removeNonCacheableOperation($operation);
        return $this;
    }

    /**
     * Set the non-cacheable operations
     *
     * @deprecated Use ConfigurationService::setNonCacheableOperations() instead
     * @param array $operations Array of operations in format "ToolType.operation_name"
     * @return self
     */
    public function setNonCacheableOperations(array $operations): self {
        $this->log('setNonCacheableOperations is deprecated. Use ConfigurationService::setNonCacheableOperations() instead.', [
            'level' => 'warning'
        ]);
        $this->configService->setNonCacheableOperations($operations);
        return $this;
    }

    /**
     * Enable or disable debug mode
     *
     * @param bool $debug Whether to enable debug mode
     * @return self
     */
    public function setDebug(bool $debug): self {
        $this->debug = $debug;
        return $this;
    }

    /**
     * Execute a tool with caching
     *
     * This method wraps the tool execution with caching functionality.
     * If the result is already cached, it returns the cached result.
     * Otherwise, it executes the tool and caches the result.
     *
     * @param ToolInterface $tool The tool to execute
     * @param array $parameters The parameters for the tool execution
     * @return array The result of the tool execution
     */
    public function execute(ToolInterface $tool, array $parameters): array {
        // Get tool type (class name without namespace)
        $toolType = $this->getToolType($tool);
        
        // Get operation from parameters if available
        $operation = $parameters['operation'] ?? 'default';
        
        // Check if this operation should be cached
        if (!$this->isCacheable($toolType, $operation)) {
            // Log non-cacheable operation
            $this->log('Non-cacheable operation', [
                'tool' => $toolType,
                'operation' => $operation,
            ]);
            
            // Execute the tool without caching
            return $tool->execute($parameters);
        }
        
        // Generate cache key
        $cacheKey = $this->generateCacheKey($tool, $parameters);
        
        // Try to get from cache
        $cachedResult = $this->cacheService->get($cacheKey);
        
        // Determine the strategy for this operation
        $strategy = $this->cacheStrategy->determineStrategy($toolType, $operation);
        
        if ($cachedResult !== null) {
            // Track cache hit for metrics
            $this->cacheStrategy->trackCacheHit($strategy);
            
            // Log cache hit
            $this->log('Cache hit for tool execution', [
                'tool' => $toolType,
                'operation' => $operation,
                'cache_key' => $cacheKey,
                'strategy' => $strategy,
            ]);
            
            return $cachedResult;
        }
        
        // Track cache miss for metrics
        $this->cacheStrategy->trackCacheMiss($strategy);
        
        // Log cache miss
        $this->log('Cache miss for tool execution', [
            'tool' => $toolType,
            'operation' => $operation,
            'cache_key' => $cacheKey,
            'strategy' => $strategy,
        ]);
        
        // Execute the tool
        $result = $tool->execute($parameters);
        
        // Cache the result
        $ttl = $this->getTtl($toolType, $operation);
        $this->cacheService->set($cacheKey, $result, $ttl);
        
        // Log cache store
        $this->log('Cached tool execution result', [
            'tool' => $toolType,
            'operation' => $operation,
            'cache_key' => $cacheKey,
            'ttl' => $ttl,
            'strategy' => $strategy,
        ]);
        
        // Register this operation for warming if appropriate
        $this->registerForWarming($toolType, $operation, $parameters);
        
        return $result;
    }

    /**
     * Get the tool type (class name without namespace)
     *
     * @param ToolInterface $tool The tool
     * @return string The tool type
     */
    protected function getToolType(ToolInterface $tool): string {
        $className = get_class($tool);
        $parts = explode('\\', $className);
        return end($parts);
    }

    /**
     * Check if an operation is cacheable
     *
     * @param string $toolType The tool type
     * @param string $operation The operation
     * @return bool Whether the operation is cacheable
     */
    protected function isCacheable(string $toolType, string $operation): bool {
        // If debug mode is enabled, don't cache
        if ($this->debug) {
            return false;
        }
        
        // Use ConfigurationService to check if operation is cacheable
        return $this->configService->isOperationCacheable($toolType, $operation);
    }

    /**
     * Generate a cache key for a tool execution
     *
     * @param ToolInterface $tool The tool
     * @param array $parameters The parameters for the tool execution
     * @return string The cache key
     */
    protected function generateCacheKey(ToolInterface $tool, array $parameters): string {
        $toolType = $this->getToolType($tool);
        $operation = $parameters['operation'] ?? 'default';
        
        // Create a stable representation of parameters for the cache key
        $paramHash = md5(json_encode($parameters));
        
        return 'tool_execution_' . $toolType . '_' . $operation . '_' . $paramHash;
    }

    /**
     * Get the TTL for a tool operation
     *
     * @param string $toolType The tool type
     * @param string $operation The operation
     * @return int TTL in seconds
     */
    protected function getTtl(string $toolType, string $operation): int {
        // Use AdvancedCacheStrategy to calculate TTL
        return $this->cacheStrategy->calculateTtl($toolType, $operation);
    }

    /**
     * Log a message
     *
     * @param string $message The message to log
     * @param array $context Additional context data
     * @return void
     */
    protected function log(string $message, array $context = []): void {
        if ($this->logger) {
            $this->logger->info($message, $context);
        }
    }

    /**
     * Register an operation for cache warming
     *
     * @param string $toolType The tool type
     * @param string $operation The operation
     * @param array $parameters The parameters for the operation
     * @return void
     */
    protected function registerForWarming(string $toolType, string $operation, array $parameters): void {
        // Skip if warming is not enabled
        if (!$this->configService->isWarmingEnabled()) {
            return;
        }
        
        // Create operation key
        $operationKey = $toolType . '.' . $operation;
        
        // Check if this operation is configured for warming
        $warmingOperation = $this->configService->getWarmingOperation($operationKey);
        if ($warmingOperation !== null) {
            // Register this operation for warming
            $this->cacheWarmer->addWarmingOperation($operationKey, [
                'priority' => $warmingOperation['priority'] ?? 50,
                'params' => $parameters,
                'frequency' => $warmingOperation['frequency'] ?? 'daily',
            ]);
            
            $this->log('Registered operation for cache warming', [
                'tool' => $toolType,
                'operation' => $operation,
                'priority' => $warmingOperation['priority'] ?? 50,
            ]);
        }
    }

    /**
     * Invalidate cache for a specific tool type
     *
     * @param string $toolType The tool type
     * @return int Number of cache entries invalidated
     */
    public function invalidateToolCache(string $toolType): int {
        $pattern = 'tool_execution_' . $toolType;
        $count = $this->cacheService->deletePattern($pattern);
        
        if ($count > 0) {
            // Notify cache warmer about invalidation
            if ($this->cacheWarmer) {
                $this->cacheWarmer->warmHighPriorityCache();
            }
            
            if ($this->logger) {
                $this->logger->info('Invalidated tool cache', [
                    'tool' => $toolType,
                    'count' => $count,
                ]);
            }
        }
        
        return $count;
    }

    /**
     * Invalidate cache for a specific tool operation
     *
     * @param string $toolType The tool type
     * @param string $operation The operation
     * @return int Number of cache entries invalidated
     */
    public function invalidateOperationCache(string $toolType, string $operation): int {
        $pattern = 'tool_execution_' . $toolType . '_' . $operation;
        $count = $this->cacheService->deletePattern($pattern);
        
        if ($count > 0) {
            // Notify cache warmer about invalidation
            if ($this->cacheWarmer) {
                $operationKey = $toolType . '.' . $operation;
                $this->cacheWarmer->warmOperation($operationKey);
            }
            
            if ($this->logger) {
                $this->logger->info('Invalidated operation cache', [
                    'tool' => $toolType,
                    'operation' => $operation,
                    'count' => $count,
                ]);
            }
        }
        
        return $count;
    }

    /**
     * Invalidate all tool execution caches
     *
     * @return int Number of cache entries invalidated
     */
    public function invalidateAllToolCaches(): int {
        $pattern = 'tool_execution_';
        $count = $this->cacheService->deletePattern($pattern);
        
        if ($count > 0) {
            // Notify cache warmer about invalidation
            if ($this->cacheWarmer) {
                $this->cacheWarmer->warmHighPriorityCache();
            }
            
            if ($this->logger) {
                $this->logger->info('Invalidated all tool caches', [
                    'count' => $count,
                ]);
            }
        }
        
        return $count;
    }
}