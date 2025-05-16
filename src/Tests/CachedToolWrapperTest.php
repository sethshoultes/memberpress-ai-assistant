<?php
/**
 * Cached Tool Wrapper Test
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Tests;

use MemberpressAiAssistant\Services\CachedToolWrapper;
use MemberpressAiAssistant\Services\CacheService;
use MemberpressAiAssistant\Interfaces\ToolInterface;
use PHPUnit\Framework\TestCase;

/**
 * Test case for the CachedToolWrapper class
 */
class CachedToolWrapperTest extends TestCase {
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
        $this->tool->method('getToolName')->willReturn('test_tool');
        $this->tool->method('getToolDescription')->willReturn('Test tool for unit tests');
        $this->tool->method('getToolDefinition')->willReturn([
            'name' => 'test_tool',
            'description' => 'Test tool for unit tests',
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

        // Create the wrapper
        $this->wrapper = new CachedToolWrapper($this->cacheService);
    }

    /**
     * Test executing a tool with caching
     *
     * @return void
     */
    public function testExecuteWithCaching(): void {
        // Set up the tool to return a test result
        $testResult = [
            'status' => 'success',
            'message' => 'Test operation completed successfully',
            'data' => [
                'test' => 'data',
            ],
        ];
        $this->tool->method('execute')->willReturn($testResult);

        // Set up the cache service to return null (cache miss) and then store the result
        $this->cacheService->method('get')->willReturn(null);
        $this->cacheService->expects($this->once())->method('set')->with(
            $this->anything(),
            $testResult,
            $this->anything()
        );

        // Execute the tool
        $result = $this->wrapper->execute($this->tool, ['operation' => 'test_operation']);

        // Verify the result
        $this->assertEquals($testResult, $result);
    }

    /**
     * Test executing a tool with a cache hit
     *
     * @return void
     */
    public function testExecuteWithCacheHit(): void {
        // Set up the tool to return a test result (should not be called)
        $this->tool->expects($this->never())->method('execute');

        // Set up the cache service to return a cached result
        $cachedResult = [
            'status' => 'success',
            'message' => 'Cached result',
            'data' => [
                'cached' => true,
            ],
        ];
        $this->cacheService->method('get')->willReturn($cachedResult);

        // Execute the tool
        $result = $this->wrapper->execute($this->tool, ['operation' => 'test_operation']);

        // Verify the result is the cached result
        $this->assertEquals($cachedResult, $result);
    }

    /**
     * Test executing a non-cacheable operation
     *
     * @return void
     */
    public function testExecuteNonCacheableOperation(): void {
        // Set up the tool to return a test result
        $testResult = [
            'status' => 'success',
            'message' => 'Non-cacheable operation completed successfully',
            'data' => [
                'test' => 'data',
            ],
        ];
        $this->tool->method('execute')->willReturn($testResult);
        $this->tool->method('getToolName')->willReturn('TestTool');

        // Add a non-cacheable operation
        $this->wrapper->addNonCacheableOperation('TestTool.non_cacheable_operation');

        // Cache service should not be called for get or set
        $this->cacheService->expects($this->never())->method('get');
        $this->cacheService->expects($this->never())->method('set');

        // Execute the tool with a non-cacheable operation
        $result = $this->wrapper->execute($this->tool, ['operation' => 'non_cacheable_operation']);

        // Verify the result
        $this->assertEquals($testResult, $result);
    }

    /**
     * Test setting and getting TTL configuration
     *
     * @return void
     */
    public function testTtlConfiguration(): void {
        // Set custom TTL for a tool type
        $this->wrapper->setTtlConfig('TestTool', [
            'default' => 600,
            'test_operation' => 1800,
        ]);

        // Set up the tool to return a test result
        $testResult = [
            'status' => 'success',
            'message' => 'Test operation completed successfully',
            'data' => [
                'test' => 'data',
            ],
        ];
        $this->tool->method('execute')->willReturn($testResult);
        $this->tool->method('getToolName')->willReturn('TestTool');

        // Cache service should be called with the custom TTL
        $this->cacheService->expects($this->once())->method('set')->with(
            $this->anything(),
            $testResult,
            1800 // The custom TTL for test_operation
        );

        // Execute the tool
        $this->wrapper->execute($this->tool, ['operation' => 'test_operation']);
    }

    /**
     * Test invalidating cache for a specific tool type
     *
     * @return void
     */
    public function testInvalidateToolCache(): void {
        // Cache service should be called with the correct pattern
        $this->cacheService->expects($this->once())->method('deletePattern')->with(
            'tool_execution_TestTool'
        )->willReturn(5);

        // Invalidate cache for the tool type
        $count = $this->wrapper->invalidateToolCache('TestTool');

        // Verify the count
        $this->assertEquals(5, $count);
    }

    /**
     * Test invalidating cache for a specific operation
     *
     * @return void
     */
    public function testInvalidateOperationCache(): void {
        // Cache service should be called with the correct pattern
        $this->cacheService->expects($this->once())->method('deletePattern')->with(
            'tool_execution_TestTool_test_operation'
        )->willReturn(2);

        // Invalidate cache for the operation
        $count = $this->wrapper->invalidateOperationCache('TestTool', 'test_operation');

        // Verify the count
        $this->assertEquals(2, $count);
    }

    /**
     * Test invalidating all tool caches
     *
     * @return void
     */
    public function testInvalidateAllToolCaches(): void {
        // Cache service should be called with the correct pattern
        $this->cacheService->expects($this->once())->method('deletePattern')->with(
            'tool_execution_'
        )->willReturn(10);

        // Invalidate all tool caches
        $count = $this->wrapper->invalidateAllToolCaches();

        // Verify the count
        $this->assertEquals(10, $count);
    }
}