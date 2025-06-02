<?php
/**
 * Cached Tool Wrapper Integration Test
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Tests;

use MemberpressAiAssistant\Services\CachedToolWrapper;
use MemberpressAiAssistant\Services\CacheService;
use MemberpressAiAssistant\Services\ConfigurationService;
use MemberpressAiAssistant\Cache\AdvancedCacheStrategy;
use MemberpressAiAssistant\Cache\CacheWarmer;
use MemberpressAiAssistant\Interfaces\ToolInterface;
use PHPUnit\Framework\TestCase;

/**
 * Integration test for the CachedToolWrapper and its related components
 * 
 * This test focuses on the integration between CachedToolWrapper and:
 * - AdvancedCacheStrategy for determining caching strategies and TTLs
 * - CacheWarmer for proactive cache warming
 * - ConfigurationService for centralized configuration management
 */
class CachedToolWrapperIntegrationTest extends TestCase {
    /**
     * Test tool instance
     *
     * @var ToolInterface
     */
    private $tool;

    /**
     * Cache service instance
     *
     * @var CacheService
     */
    private $cacheService;

    /**
     * Advanced cache strategy instance
     *
     * @var AdvancedCacheStrategy
     */
    private $cacheStrategy;

    /**
     * Cache warmer instance
     *
     * @var CacheWarmer
     */
    private $cacheWarmer;

    /**
     * Configuration service instance
     *
     * @var ConfigurationService
     */
    private $configService;

    /**
     * Cached tool wrapper instance
     *
     * @var CachedToolWrapper
     */
    private $wrapper;

    /**
     * Set up the test environment
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();

        // Create a mock tool
        $this->tool = $this->createMock(ToolInterface::class);
        $this->tool->method('getToolName')->willReturn('TestTool');
        $this->tool->method('getToolDescription')->willReturn('Test tool for integration tests');
        $this->tool->method('getToolDefinition')->willReturn([
            'name' => 'test_tool',
            'description' => 'Test tool for integration tests',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'operation' => [
                        'type' => 'string',
                        'description' => 'The operation to perform',
                    ],
                ],
            ],
        ]);

        // Create a mock cache service
        $this->cacheService = $this->createMock(CacheService::class);

        // Create a real configuration service
        $this->configService = new ConfigurationService('test_config');

        // Create a real advanced cache strategy with the mock cache service
        $this->cacheStrategy = new AdvancedCacheStrategy($this->cacheService);

        // Create a mock cache warmer
        $this->cacheWarmer = $this->createMock(CacheWarmer::class);

        // Create the wrapper with all dependencies
        $this->wrapper = new CachedToolWrapper(
            $this->cacheService,
            $this->cacheStrategy,
            $this->cacheWarmer,
            $this->configService
        );
    }

    /**
     * Test that CachedToolWrapper correctly uses AdvancedCacheStrategy to determine caching strategies and TTLs
     *
     * @return void
     */
    public function testCachedToolWrapperUsesAdvancedCacheStrategy(): void {
        // Set up the tool to return a test result
        $testResult = [
            'status' => 'success',
            'message' => 'Test operation completed successfully',
            'data' => ['test' => 'data'],
        ];
        $this->tool->method('execute')->willReturn($testResult);

        // Set up the cache service to return null (cache miss)
        $this->cacheService->method('get')->willReturn(null);

        // Set up a spy on the cache strategy to verify it's called
        $cacheStrategySpy = $this->createPartialMock(AdvancedCacheStrategy::class, ['determineStrategy', 'calculateTtl']);
        $cacheStrategySpy->expects($this->once())->method('determineStrategy')
            ->with('TestTool', 'test_operation')
            ->willReturn('medium_lived');
        $cacheStrategySpy->expects($this->once())->method('calculateTtl')
            ->with('TestTool', 'test_operation')
            ->willReturn(300);

        // Replace the cache strategy in the wrapper
        $reflectionProperty = new \ReflectionProperty(CachedToolWrapper::class, 'cacheStrategy');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->wrapper, $cacheStrategySpy);

        // Execute the tool
        $this->wrapper->execute($this->tool, ['operation' => 'test_operation']);
    }

    /**
     * Test that CachedToolWrapper properly registers operations with CacheWarmer
     *
     * @return void
     */
    public function testCachedToolWrapperRegistersOperationsWithCacheWarmer(): void {
        // Set up the tool to return a test result
        $testResult = [
            'status' => 'success',
            'message' => 'Test operation completed successfully',
            'data' => ['test' => 'data'],
        ];
        $this->tool->method('execute')->willReturn($testResult);

        // Set up the cache service to return null (cache miss)
        $this->cacheService->method('get')->willReturn(null);

        // Configure the ConfigurationService to enable warming and add a warming operation
        $this->configService->setWarmingEnabled(true);
        $this->configService->addWarmingOperation('TestTool.test_operation', [
            'priority' => 80,
            'params' => [],
            'frequency' => 'hourly',
        ]);

        // Set up the cache warmer to expect addWarmingOperation to be called
        $this->cacheWarmer->expects($this->once())->method('addWarmingOperation')
            ->with(
                'TestTool.test_operation',
                $this->callback(function($config) {
                    return isset($config['priority']) && isset($config['params']) && isset($config['frequency']);
                })
            );

        // Execute the tool
        $this->wrapper->execute($this->tool, ['operation' => 'test_operation']);
    }

    /**
     * Test that CachedToolWrapper correctly uses ConfigurationService for configuration
     *
     * @return void
     */
    public function testCachedToolWrapperUsesConfigurationService(): void {
        // Set up the tool to return a test result
        $testResult = [
            'status' => 'success',
            'message' => 'Test operation completed successfully',
            'data' => ['test' => 'data'],
        ];
        $this->tool->method('execute')->willReturn($testResult);

        // Set up the cache service to return null (cache miss)
        $this->cacheService->method('get')->willReturn(null);

        // Configure the ConfigurationService with custom TTL and non-cacheable operations
        $this->configService->setToolTtlConfig('TestTool', [
            'default' => 600,
            'test_operation' => 1800,
        ]);
        $this->configService->addNonCacheableOperation('TestTool.non_cacheable_operation');

        // Cache service should be called with the custom TTL for cacheable operations
        $this->cacheService->expects($this->once())->method('set')->with(
            $this->anything(),
            $testResult,
            1800 // The custom TTL from ConfigurationService
        );

        // Execute the tool with a cacheable operation
        $this->wrapper->execute($this->tool, ['operation' => 'test_operation']);

        // Reset expectations
        $this->cacheService = $this->createMock(CacheService::class);
        $reflectionProperty = new \ReflectionProperty(CachedToolWrapper::class, 'cacheService');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->wrapper, $this->cacheService);

        // Cache service should not be called for non-cacheable operations
        $this->cacheService->expects($this->never())->method('get');
        $this->cacheService->expects($this->never())->method('set');

        // Execute the tool with a non-cacheable operation
        $this->wrapper->execute($this->tool, ['operation' => 'non_cacheable_operation']);
    }

    /**
     * Test the end-to-end flow of caching with all components working together
     *
     * @return void
     */
    public function testEndToEndCachingFlow(): void {
        // Set up the tool to return a test result
        $testResult = [
            'status' => 'success',
            'message' => 'Test operation completed successfully',
            'data' => ['test' => 'data'],
        ];
        $this->tool->method('execute')->willReturn($testResult);

        // Create real instances of all components for end-to-end testing
        $cacheService = $this->createMock(CacheService::class);
        $configService = new ConfigurationService('test_config');
        $cacheStrategy = new AdvancedCacheStrategy($cacheService);
        $cacheWarmer = $this->createMock(CacheWarmer::class);

        // Configure the services
        $configService->setToolTtlConfig('TestTool', [
            'default' => 600,
            'test_operation' => 1800,
        ]);
        $configService->setWarmingEnabled(true);
        $configService->addWarmingOperation('TestTool.test_operation', [
            'priority' => 90,
            'params' => [],
            'frequency' => 'hourly',
        ]);
        $configService->setToolOperationCharacteristics('TestTool', 'test_operation', [
            'volatility' => 'low',
            'access_frequency' => 'high',
            'resource_intensity' => 'medium',
        ]);

        // Create a new wrapper with the real components
        $wrapper = new CachedToolWrapper(
            $cacheService,
            $cacheStrategy,
            $cacheWarmer,
            $configService
        );

        // First execution - cache miss
        $cacheService->method('get')->willReturn(null);
        $cacheService->expects($this->once())->method('set')->with(
            $this->anything(),
            $testResult,
            $this->anything()
        );
        $cacheWarmer->expects($this->once())->method('addWarmingOperation');

        $result1 = $wrapper->execute($this->tool, ['operation' => 'test_operation']);
        $this->assertEquals($testResult, $result1);

        // Reset mock to simulate a second execution with cache hit
        $cacheService = $this->createMock(CacheService::class);
        $cacheService->method('get')->willReturn($testResult);
        $cacheService->expects($this->never())->method('set');

        $reflectionProperty = new \ReflectionProperty(CachedToolWrapper::class, 'cacheService');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($wrapper, $cacheService);

        // Second execution - cache hit
        $this->tool->expects($this->never())->method('execute');
        $result2 = $wrapper->execute($this->tool, ['operation' => 'test_operation']);
        $this->assertEquals($testResult, $result2);
    }

    /**
     * Test that cache invalidation correctly interacts with CacheWarmer
     *
     * @return void
     */
    public function testCacheInvalidationWithCacheWarmer(): void {
        // Set up the cache service to return a count of invalidated items
        $this->cacheService->method('deletePattern')->willReturn(5);

        // Set up the cache warmer to expect warmHighPriorityCache to be called
        $this->cacheWarmer->expects($this->once())->method('warmHighPriorityCache');

        // Invalidate all tool caches
        $count = $this->wrapper->invalidateAllToolCaches();
        $this->assertEquals(5, $count);

        // Reset the cache warmer mock
        $this->cacheWarmer = $this->createMock(CacheWarmer::class);
        $reflectionProperty = new \ReflectionProperty(CachedToolWrapper::class, 'cacheWarmer');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->wrapper, $this->cacheWarmer);

        // Set up the cache warmer to expect warmOperation to be called
        $this->cacheWarmer->expects($this->once())->method('warmOperation')
            ->with('TestTool.test_operation');

        // Invalidate specific operation cache
        $count = $this->wrapper->invalidateOperationCache('TestTool', 'test_operation');
        $this->assertEquals(5, $count);
    }

    /**
     * Test that debug mode correctly bypasses caching
     *
     * @return void
     */
    public function testDebugModeBypasses(): void {
        // Set up the tool to return a test result
        $testResult = [
            'status' => 'success',
            'message' => 'Test operation completed successfully',
            'data' => ['test' => 'data'],
        ];
        $this->tool->method('execute')->willReturn($testResult);

        // Set up the cache service
        $this->cacheService->expects($this->never())->method('get');
        $this->cacheService->expects($this->never())->method('set');

        // Enable debug mode
        $this->wrapper->setDebug(true);

        // Execute the tool
        $result = $this->wrapper->execute($this->tool, ['operation' => 'test_operation']);
        $this->assertEquals($testResult, $result);

        // Disable debug mode
        $this->wrapper->setDebug(false);

        // Reset cache service expectations
        $this->cacheService = $this->createMock(CacheService::class);
        $reflectionProperty = new \ReflectionProperty(CachedToolWrapper::class, 'cacheService');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->wrapper, $this->cacheService);

        // Cache service should be called now
        $this->cacheService->expects($this->once())->method('get');
        $this->cacheService->method('get')->willReturn(null);
        $this->cacheService->expects($this->once())->method('set');

        // Execute the tool again
        $this->wrapper->execute($this->tool, ['operation' => 'test_operation']);
    }
}