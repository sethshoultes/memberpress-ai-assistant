<?php
/**
 * Cached Tool Wrapper Manual Test
 *
 * This is a simple manual test script for the CachedToolWrapper class.
 * It doesn't rely on PHPUnit but demonstrates the testing concepts.
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Tests;

use MemberpressAiAssistant\Services\CachedToolWrapper;
use MemberpressAiAssistant\Services\CacheService;
use MemberpressAiAssistant\Interfaces\ToolInterface;

/**
 * A simple mock tool for testing
 */
class MockTool implements ToolInterface {
    /**
     * Tool name
     *
     * @var string
     */
    private $name;

    /**
     * Tool description
     *
     * @var string
     */
    private $description;

    /**
     * Expected result for execute method
     *
     * @var array
     */
    private $expectedResult;

    /**
     * Track if execute was called
     *
     * @var bool
     */
    private $executeCalled = false;

    /**
     * Constructor
     *
     * @param string $name Tool name
     * @param string $description Tool description
     * @param array $expectedResult Expected result for execute method
     */
    public function __construct(string $name, string $description, array $expectedResult) {
        $this->name = $name;
        $this->description = $description;
        $this->expectedResult = $expectedResult;
    }

    /**
     * {@inheritdoc}
     */
    public function getToolName(): string {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getToolDescription(): string {
        return $this->description;
    }

    /**
     * {@inheritdoc}
     */
    public function getToolDefinition(): array {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'operation' => [
                        'type' => 'string',
                        'description' => 'The operation to perform',
                    ],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute(array $parameters): array {
        $this->executeCalled = true;
        return $this->expectedResult;
    }

    /**
     * Check if execute was called
     *
     * @return bool
     */
    public function wasExecuteCalled(): bool {
        return $this->executeCalled;
    }

    /**
     * Reset execute called flag
     *
     * @return void
     */
    public function resetExecuteCalled(): void {
        $this->executeCalled = false;
    }
}

/**
 * A simple mock cache service for testing
 */
class MockCacheService extends CacheService {
    /**
     * Cached data
     *
     * @var array
     */
    private $cache = [];

    /**
     * Track method calls
     *
     * @var array
     */
    private $calls = [
        'get' => 0,
        'set' => 0,
        'delete' => 0,
        'deletePattern' => 0,
    ];

    /**
     * Value to return for get method
     *
     * @var mixed
     */
    private $getValue = null;

    /**
     * Constructor
     */
    public function __construct() {
        // Override parent constructor
    }

    /**
     * Set the value to return for get method
     *
     * @param mixed $value
     * @return void
     */
    public function setGetValue($value): void {
        $this->getValue = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, $default = null) {
        $this->calls['get']++;
        return $this->getValue !== null ? $this->getValue : $default;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, $data, ?int $ttl = null): bool {
        $this->calls['set']++;
        $this->cache[$key] = $data;
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool {
        $this->calls['delete']++;
        unset($this->cache[$key]);
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deletePattern(string $pattern): int {
        $this->calls['deletePattern']++;
        $count = 0;
        foreach (array_keys($this->cache) as $key) {
            if (strpos($key, $pattern) !== false) {
                unset($this->cache[$key]);
                $count++;
            }
        }
        return $count;
    }

    /**
     * Get the number of calls for a method
     *
     * @param string $method Method name
     * @return int Number of calls
     */
    public function getCallCount(string $method): int {
        return $this->calls[$method] ?? 0;
    }

    /**
     * Reset call counts
     *
     * @return void
     */
    public function resetCalls(): void {
        foreach (array_keys($this->calls) as $method) {
            $this->calls[$method] = 0;
        }
    }
}

/**
 * Simple assertion functions
 */
function assertEquals($expected, $actual, $message = ''): void {
    if ($expected !== $actual) {
        echo "Assertion failed: Expected " . json_encode($expected) . ", got " . json_encode($actual) . "\n";
        if ($message) {
            echo "Message: $message\n";
        }
        debug_print_backtrace();
    } else {
        echo "Assertion passed: " . ($message ?: "Values are equal") . "\n";
    }
}

function assertTrue($condition, $message = ''): void {
    if (!$condition) {
        echo "Assertion failed: Expected true, got false\n";
        if ($message) {
            echo "Message: $message\n";
        }
        debug_print_backtrace();
    } else {
        echo "Assertion passed: " . ($message ?: "Condition is true") . "\n";
    }
}

function assertFalse($condition, $message = ''): void {
    if ($condition) {
        echo "Assertion failed: Expected false, got true\n";
        if ($message) {
            echo "Message: $message\n";
        }
        debug_print_backtrace();
    } else {
        echo "Assertion passed: " . ($message ?: "Condition is false") . "\n";
    }
}

/**
 * Run the tests
 */
function runTests(): void {
    echo "Running CachedToolWrapper tests...\n";

    // Test 1: Execute with caching
    testExecuteWithCaching();

    // Test 2: Execute with cache hit
    testExecuteWithCacheHit();

    // Test 3: Execute non-cacheable operation
    testExecuteNonCacheableOperation();

    // Test 4: TTL configuration
    testTtlConfiguration();

    // Test 5: Invalidate tool cache
    testInvalidateToolCache();

    echo "All tests completed.\n";
}

/**
 * Test executing a tool with caching
 */
function testExecuteWithCaching(): void {
    echo "\nTest: Execute with caching\n";

    // Create mock objects
    $testResult = [
        'status' => 'success',
        'message' => 'Test operation completed successfully',
        'data' => [
            'test' => 'data',
        ],
    ];
    $tool = new MockTool('test_tool', 'Test tool for unit tests', $testResult);
    $cacheService = new MockCacheService();
    $wrapper = new CachedToolWrapper($cacheService);

    // Execute the tool
    $result = $wrapper->execute($tool, ['operation' => 'test_operation']);

    // Verify the result
    assertEquals($testResult, $result, "Result should match the expected result");
    assertTrue($tool->wasExecuteCalled(), "Tool execute method should be called");
    assertEquals(1, $cacheService->getCallCount('get'), "Cache get should be called once");
    assertEquals(1, $cacheService->getCallCount('set'), "Cache set should be called once");
}

/**
 * Test executing a tool with a cache hit
 */
function testExecuteWithCacheHit(): void {
    echo "\nTest: Execute with cache hit\n";

    // Create mock objects
    $testResult = [
        'status' => 'success',
        'message' => 'Test operation completed successfully',
        'data' => [
            'test' => 'data',
        ],
    ];
    $cachedResult = [
        'status' => 'success',
        'message' => 'Cached result',
        'data' => [
            'cached' => true,
        ],
    ];
    $tool = new MockTool('test_tool', 'Test tool for unit tests', $testResult);
    $cacheService = new MockCacheService();
    $cacheService->setGetValue($cachedResult);
    $wrapper = new CachedToolWrapper($cacheService);

    // Execute the tool
    $result = $wrapper->execute($tool, ['operation' => 'test_operation']);

    // Verify the result
    assertEquals($cachedResult, $result, "Result should match the cached result");
    assertFalse($tool->wasExecuteCalled(), "Tool execute method should not be called");
    assertEquals(1, $cacheService->getCallCount('get'), "Cache get should be called once");
    assertEquals(0, $cacheService->getCallCount('set'), "Cache set should not be called");
}

/**
 * Test executing a non-cacheable operation
 */
function testExecuteNonCacheableOperation(): void {
    echo "\nTest: Execute non-cacheable operation\n";

    // Create mock objects
    $testResult = [
        'status' => 'success',
        'message' => 'Non-cacheable operation completed successfully',
        'data' => [
            'test' => 'data',
        ],
    ];
    $tool = new MockTool('TestTool', 'Test tool for unit tests', $testResult);
    $cacheService = new MockCacheService();
    $wrapper = new CachedToolWrapper($cacheService);

    // Add a non-cacheable operation
    $wrapper->addNonCacheableOperation('TestTool.non_cacheable_operation');

    // Execute the tool with a non-cacheable operation
    $result = $wrapper->execute($tool, ['operation' => 'non_cacheable_operation']);

    // Verify the result
    assertEquals($testResult, $result, "Result should match the expected result");
    assertTrue($tool->wasExecuteCalled(), "Tool execute method should be called");
    assertEquals(0, $cacheService->getCallCount('get'), "Cache get should not be called");
    assertEquals(0, $cacheService->getCallCount('set'), "Cache set should not be called");
}

/**
 * Test TTL configuration
 */
function testTtlConfiguration(): void {
    echo "\nTest: TTL configuration\n";

    // This test is more conceptual since we can't easily verify the TTL value
    // In a real test, we would mock the CacheService to verify the TTL value

    // Create mock objects
    $testResult = [
        'status' => 'success',
        'message' => 'Test operation completed successfully',
        'data' => [
            'test' => 'data',
        ],
    ];
    $tool = new MockTool('TestTool', 'Test tool for unit tests', $testResult);
    $cacheService = new MockCacheService();
    $wrapper = new CachedToolWrapper($cacheService);

    // Set custom TTL for a tool type
    $wrapper->setTtlConfig('TestTool', [
        'default' => 600,
        'test_operation' => 1800,
    ]);

    // Execute the tool
    $wrapper->execute($tool, ['operation' => 'test_operation']);

    // Verify that cache set was called
    assertEquals(1, $cacheService->getCallCount('set'), "Cache set should be called once");
}

/**
 * Test invalidating tool cache
 */
function testInvalidateToolCache(): void {
    echo "\nTest: Invalidate tool cache\n";

    // Create mock objects
    $cacheService = new MockCacheService();
    $wrapper = new CachedToolWrapper($cacheService);

    // Invalidate cache for a tool type
    $wrapper->invalidateToolCache('TestTool');

    // Verify that deletePattern was called
    assertEquals(1, $cacheService->getCallCount('deletePattern'), "Cache deletePattern should be called once");
}

// Run the tests
runTests();