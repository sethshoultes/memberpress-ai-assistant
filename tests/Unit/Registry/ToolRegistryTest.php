<?php
/**
 * Tests for the ToolRegistry class
 *
 * @package MemberpressAiAssistant\Tests\Unit\Registry
 */

namespace MemberpressAiAssistant\Tests\Unit\Registry;

use MemberpressAiAssistant\Tests\TestCase;
use MemberpressAiAssistant\Registry\ToolRegistry;
use MemberpressAiAssistant\Interfaces\ToolInterface;

/**
 * Test case for ToolRegistry
 */
class ToolRegistryTest extends TestCase {
    /**
     * Registry instance
     *
     * @var ToolRegistry
     */
    private $registry;

    /**
     * Logger mock
     *
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerMock;

    /**
     * Set up the test
     */
    protected function setUp(): void {
        parent::setUp();
        
        // Create a logger mock
        $this->loggerMock = $this->getMockWithExpectations('stdClass', ['info', 'warning', 'error']);
        
        // Reset the singleton instance
        $this->resetSingleton();
        
        // Create registry instance
        $this->registry = ToolRegistry::getInstance($this->loggerMock);
    }

    /**
     * Tear down the test
     */
    protected function tearDown(): void {
        // Reset the singleton instance
        $this->resetSingleton();
        
        parent::tearDown();
    }

    /**
     * Reset the singleton instance using reflection
     */
    private function resetSingleton(): void {
        $reflection = new \ReflectionClass(ToolRegistry::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null, null);
    }

    /**
     * Test getInstance method
     */
    public function testGetInstance(): void {
        // Get an instance
        $instance1 = ToolRegistry::getInstance();
        
        // Get another instance
        $instance2 = ToolRegistry::getInstance();
        
        // They should be the same instance
        $this->assertSame($instance1, $instance2);
    }

    /**
     * Test registerTool method
     */
    public function testRegisterTool(): void {
        // Create a mock tool
        $toolMock = $this->getMockWithExpectations(ToolInterface::class);
        $toolMock->method('getToolName')->willReturn('TestTool');
        
        // Register the tool
        $result = $this->registry->registerTool($toolMock);
        
        // Assert the result
        $this->assertTrue($result);
        
        // Check if the tool is registered
        $this->assertTrue($this->registry->hasTool('TestTool'));
    }

    /**
     * Test registerTool method with duplicate tool
     */
    public function testRegisterToolWithDuplicate(): void {
        // Create a mock tool
        $toolMock = $this->getMockWithExpectations(ToolInterface::class);
        $toolMock->method('getToolName')->willReturn('TestTool');
        
        // Register the tool
        $this->registry->registerTool($toolMock);
        
        // Try to register it again
        $result = $this->registry->registerTool($toolMock);
        
        // Assert the result
        $this->assertFalse($result);
    }

    /**
     * Test unregisterTool method
     */
    public function testUnregisterTool(): void {
        // Create a mock tool
        $toolMock = $this->getMockWithExpectations(ToolInterface::class);
        $toolMock->method('getToolName')->willReturn('TestTool');
        
        // Register the tool
        $this->registry->registerTool($toolMock);
        
        // Unregister the tool
        $result = $this->registry->unregisterTool('TestTool');
        
        // Assert the result
        $this->assertTrue($result);
        
        // Check if the tool is unregistered
        $this->assertFalse($this->registry->hasTool('TestTool'));
    }

    /**
     * Test unregisterTool method with non-existent tool
     */
    public function testUnregisterToolWithNonExistent(): void {
        // Try to unregister a non-existent tool
        $result = $this->registry->unregisterTool('NonExistentTool');
        
        // Assert the result
        $this->assertFalse($result);
    }

    /**
     * Test getTool method
     */
    public function testGetTool(): void {
        // Create a mock tool
        $toolMock = $this->getMockWithExpectations(ToolInterface::class);
        $toolMock->method('getToolName')->willReturn('TestTool');
        
        // Register the tool
        $this->registry->registerTool($toolMock);
        
        // Get the tool
        $tool = $this->registry->getTool('TestTool');
        
        // Assert the result
        $this->assertSame($toolMock, $tool);
    }

    /**
     * Test getTool method with non-existent tool
     */
    public function testGetToolWithNonExistent(): void {
        // Try to get a non-existent tool
        $tool = $this->registry->getTool('NonExistentTool');
        
        // Assert the result
        $this->assertNull($tool);
    }

    /**
     * Test getAllTools method
     */
    public function testGetAllTools(): void {
        // Create mock tools
        $tool1Mock = $this->getMockWithExpectations(ToolInterface::class);
        $tool1Mock->method('getToolName')->willReturn('Tool1');
        
        $tool2Mock = $this->getMockWithExpectations(ToolInterface::class);
        $tool2Mock->method('getToolName')->willReturn('Tool2');
        
        // Register the tools
        $this->registry->registerTool($tool1Mock);
        $this->registry->registerTool($tool2Mock);
        
        // Get all tools
        $tools = $this->registry->getAllTools();
        
        // Assert the result
        $this->assertIsArray($tools);
        $this->assertCount(2, $tools);
        $this->assertArrayHasKey('Tool1', $tools);
        $this->assertArrayHasKey('Tool2', $tools);
        $this->assertSame($tool1Mock, $tools['Tool1']);
        $this->assertSame($tool2Mock, $tools['Tool2']);
    }

    /**
     * Test findBestToolForTask method
     */
    public function testFindBestToolForTask(): void {
        // Create mock tools with different descriptions
        $tool1Mock = $this->getMockWithExpectations(ToolInterface::class);
        $tool1Mock->method('getToolName')->willReturn('Tool1');
        $tool1Mock->method('getToolDescription')->willReturn('A tool for managing content');
        
        $tool2Mock = $this->getMockWithExpectations(ToolInterface::class);
        $tool2Mock->method('getToolName')->willReturn('Tool2');
        $tool2Mock->method('getToolDescription')->willReturn('A tool for managing users and memberships');
        
        // Register the tools
        $this->registry->registerTool($tool1Mock);
        $this->registry->registerTool($tool2Mock);
        
        // Find the best tool for a content-related task
        $bestTool = $this->registry->findBestToolForTask('I need to create some content');
        
        // Assert the result
        $this->assertSame($tool1Mock, $bestTool);
        
        // Find the best tool for a membership-related task
        $bestTool = $this->registry->findBestToolForTask('I need to manage memberships');
        
        // Assert the result
        $this->assertSame($tool2Mock, $bestTool);
    }

    /**
     * Test findToolsByCapability method
     */
    public function testFindToolsByCapability(): void {
        // Create mock tools with different capabilities
        $tool1Mock = $this->getMockWithExpectations(ToolInterface::class);
        $tool1Mock->method('getToolName')->willReturn('Tool1');
        $tool1Mock->method('getToolDefinition')->willReturn([
            'name' => 'Tool1',
            'description' => 'Tool 1 description',
            'parameters' => [],
            'capabilities' => ['content', 'search'],
        ]);
        
        $tool2Mock = $this->getMockWithExpectations(ToolInterface::class);
        $tool2Mock->method('getToolName')->willReturn('Tool2');
        $tool2Mock->method('getToolDefinition')->willReturn([
            'name' => 'Tool2',
            'description' => 'Tool 2 description',
            'parameters' => [],
            'capabilities' => ['user', 'membership', 'search'],
        ]);
        
        // Register the tools
        $this->registry->registerTool($tool1Mock);
        $this->registry->registerTool($tool2Mock);
        
        // Find tools by capability
        $tools = $this->registry->findToolsByCapability('content');
        
        // Assert the result
        $this->assertIsArray($tools);
        $this->assertCount(1, $tools);
        $this->assertArrayHasKey('Tool1', $tools);
        
        // Find tools by another capability
        $tools = $this->registry->findToolsByCapability('search');
        
        // Assert the result
        $this->assertIsArray($tools);
        $this->assertCount(2, $tools);
        $this->assertArrayHasKey('Tool1', $tools);
        $this->assertArrayHasKey('Tool2', $tools);
    }

    /**
     * Test findToolsByParameter method
     */
    public function testFindToolsByParameter(): void {
        // Create mock tools with different parameters
        $tool1Mock = $this->getMockWithExpectations(ToolInterface::class);
        $tool1Mock->method('getToolName')->willReturn('Tool1');
        $tool1Mock->method('getToolDefinition')->willReturn([
            'name' => 'Tool1',
            'description' => 'Tool 1 description',
            'parameters' => [
                'properties' => [
                    'content_id' => [
                        'type' => 'integer',
                        'description' => 'Content ID',
                    ],
                    'title' => [
                        'type' => 'string',
                        'description' => 'Content title',
                    ],
                ],
            ],
        ]);
        
        $tool2Mock = $this->getMockWithExpectations(ToolInterface::class);
        $tool2Mock->method('getToolName')->willReturn('Tool2');
        $tool2Mock->method('getToolDefinition')->willReturn([
            'name' => 'Tool2',
            'description' => 'Tool 2 description',
            'parameters' => [
                'properties' => [
                    'user_id' => [
                        'type' => 'integer',
                        'description' => 'User ID',
                    ],
                    'title' => [
                        'type' => 'string',
                        'description' => 'User title',
                    ],
                ],
            ],
        ]);
        
        // Register the tools
        $this->registry->registerTool($tool1Mock);
        $this->registry->registerTool($tool2Mock);
        
        // Find tools by parameter
        $tools = $this->registry->findToolsByParameter('content_id');
        
        // Assert the result
        $this->assertIsArray($tools);
        $this->assertCount(1, $tools);
        $this->assertArrayHasKey('Tool1', $tools);
        
        // Find tools by another parameter
        $tools = $this->registry->findToolsByParameter('title');
        
        // Assert the result
        $this->assertIsArray($tools);
        $this->assertCount(2, $tools);
        $this->assertArrayHasKey('Tool1', $tools);
        $this->assertArrayHasKey('Tool2', $tools);
    }

    /**
     * Test discoverTools method with WordPress filters
     */
    public function testDiscoverToolsWithWordPressFilters(): void {
        // Define a function to mock WordPress filter for core tools
        if (!function_exists('apply_filters')) {
            function apply_filters($tag, $value) {
                if ($tag === 'mpai_core_tool_classes') {
                    return ['MockCoreToolClass'];
                } elseif ($tag === 'mpai_plugin_tool_classes') {
                    return ['MockPluginToolClass'];
                }
                return $value;
            }
        }
        
        // Mock class_exists to return true for our mock classes
        if (!function_exists('class_exists')) {
            function class_exists($class) {
                return in_array($class, ['MockCoreToolClass', 'MockPluginToolClass']);
            }
        }
        
        // Create a mock registry with mocked methods
        $registryMock = $this->getMockBuilder(ToolRegistry::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['registerCoreTools', 'discoverPluginTools', 'scanDirectoryForTools'])
            ->getMock();
        
        // Set up expectations
        $registryMock->expects($this->once())
            ->method('registerCoreTools')
            ->willReturn(2);
        
        $registryMock->expects($this->once())
            ->method('discoverPluginTools')
            ->willReturn(1);
        
        $registryMock->expects($this->once())
            ->method('scanDirectoryForTools')
            ->with('test_directory')
            ->willReturn(3);
        
        // Call the method
        $result = $registryMock->discoverTools('test_directory');
        
        // Assert the result
        $this->assertEquals(6, $result);
    }
}