<?php
/**
 * Tests for the AgentFactory class
 *
 * @package MemberpressAiAssistant\Tests\Unit\Factory
 */

namespace MemberpressAiAssistant\Tests\Unit\Factory;

use MemberpressAiAssistant\Tests\TestCase;
use MemberpressAiAssistant\Factory\AgentFactory;
use MemberpressAiAssistant\DI\Container;
use MemberpressAiAssistant\Registry\AgentRegistry;
use MemberpressAiAssistant\Interfaces\AgentInterface;

/**
 * Test case for AgentFactory
 */
class AgentFactoryTest extends TestCase {
    /**
     * Container mock
     *
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $containerMock;

    /**
     * Registry mock
     *
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $registryMock;

    /**
     * Agent factory instance
     *
     * @var AgentFactory
     */
    private $factory;

    /**
     * Set up the test
     */
    protected function setUp(): void {
        parent::setUp();
        
        // Create mocks
        $this->containerMock = $this->getMockWithExpectations(Container::class);
        $this->registryMock = $this->getMockWithExpectations(AgentRegistry::class);
        
        // Create factory instance
        $this->factory = new AgentFactory($this->containerMock, $this->registryMock);
    }

    /**
     * Test createAgent method
     */
    public function testCreateAgent(): void {
        // Create a mock agent
        $agentMock = $this->getMockWithExpectations(AgentInterface::class);
        
        // Set up expectations
        $this->containerMock->expects($this->once())
            ->method('make')
            ->with('TestAgent', [])
            ->willReturn($agentMock);
        
        // Call the method
        $result = $this->factory->createAgent('TestAgent');
        
        // Assert the result
        $this->assertSame($agentMock, $result);
    }

    /**
     * Test createAgent method with parameters
     */
    public function testCreateAgentWithParameters(): void {
        // Create a mock agent
        $agentMock = $this->getMockWithExpectations(AgentInterface::class);
        
        // Parameters to pass
        $parameters = ['logger' => 'test_logger'];
        
        // Set up expectations
        $this->containerMock->expects($this->once())
            ->method('make')
            ->with('TestAgent', $parameters)
            ->willReturn($agentMock);
        
        // Call the method
        $result = $this->factory->createAgent('TestAgent', $parameters);
        
        // Assert the result
        $this->assertSame($agentMock, $result);
    }

    /**
     * Test createAgentByType method
     */
    public function testCreateAgentByType(): void {
        // Create a mock agent
        $agentMock = $this->getMockWithExpectations(AgentInterface::class);
        
        // Set up expectations for getAvailableAgentTypes
        $this->factory = $this->getMockBuilder(AgentFactory::class)
            ->setConstructorArgs([$this->containerMock, $this->registryMock])
            ->onlyMethods(['getAvailableAgentTypes', 'createAgent'])
            ->getMock();
        
        $this->factory->expects($this->once())
            ->method('getAvailableAgentTypes')
            ->willReturn(['test_type' => 'TestAgent']);
        
        $this->factory->expects($this->once())
            ->method('createAgent')
            ->with('TestAgent', [])
            ->willReturn($agentMock);
        
        // Call the method
        $result = $this->factory->createAgentByType('test_type');
        
        // Assert the result
        $this->assertSame($agentMock, $result);
    }

    /**
     * Test createAgentByType method with invalid type
     */
    public function testCreateAgentByTypeWithInvalidType(): void {
        // Set up expectations for getAvailableAgentTypes
        $this->factory = $this->getMockBuilder(AgentFactory::class)
            ->setConstructorArgs([$this->containerMock, $this->registryMock])
            ->onlyMethods(['getAvailableAgentTypes'])
            ->getMock();
        
        $this->factory->expects($this->once())
            ->method('getAvailableAgentTypes')
            ->willReturn(['test_type' => 'TestAgent']);
        
        // Expect exception
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Agent type 'invalid_type' not found");
        
        // Call the method with invalid type
        $this->factory->createAgentByType('invalid_type');
    }

    /**
     * Test createAndRegisterAgent method
     */
    public function testCreateAndRegisterAgent(): void {
        // Create a mock agent
        $agentMock = $this->getMockWithExpectations(AgentInterface::class);
        
        // Set up expectations
        $this->factory = $this->getMockBuilder(AgentFactory::class)
            ->setConstructorArgs([$this->containerMock, $this->registryMock])
            ->onlyMethods(['createAgent'])
            ->getMock();
        
        $this->factory->expects($this->once())
            ->method('createAgent')
            ->with('TestAgent', [])
            ->willReturn($agentMock);
        
        $this->registryMock->expects($this->once())
            ->method('registerAgent')
            ->with($agentMock)
            ->willReturn(true);
        
        // Call the method
        $result = $this->factory->createAndRegisterAgent('TestAgent');
        
        // Assert the result
        $this->assertSame($agentMock, $result);
    }

    /**
     * Test createAndRegisterAgent method with registration failure
     */
    public function testCreateAndRegisterAgentWithRegistrationFailure(): void {
        // Create a mock agent
        $agentMock = $this->getMockWithExpectations(AgentInterface::class);
        
        // Set up expectations
        $this->factory = $this->getMockBuilder(AgentFactory::class)
            ->setConstructorArgs([$this->containerMock, $this->registryMock])
            ->onlyMethods(['createAgent'])
            ->getMock();
        
        $this->factory->expects($this->once())
            ->method('createAgent')
            ->with('TestAgent', [])
            ->willReturn($agentMock);
        
        $this->registryMock->expects($this->once())
            ->method('registerAgent')
            ->with($agentMock)
            ->willReturn(false);
        
        // Expect exception
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Failed to register agent 'TestAgent'");
        
        // Call the method
        $this->factory->createAndRegisterAgent('TestAgent');
    }

    /**
     * Test validateAgentClass method with valid class
     */
    public function testValidateAgentClassWithValidClass(): void {
        // Create a mock class that implements AgentInterface
        $validAgentClass = 'MemberpressAiAssistant\Tests\Fixtures\ValidAgent';
        
        // Define the class for testing
        if (!class_exists($validAgentClass)) {
            eval('
                namespace MemberpressAiAssistant\Tests\Fixtures;
                
                use MemberpressAiAssistant\Interfaces\AgentInterface;
                
                class ValidAgent implements AgentInterface {
                    public function getAgentName(): string { return "ValidAgent"; }
                    public function getAgentDescription(): string { return "Valid agent for testing"; }
                    public function getSpecializationScore(array $request): float { return 0.0; }
                    public function processRequest(array $request, array $context): array { return []; }
                    public function getSystemPrompt(): string { return ""; }
                    public function getCapabilities(): array { return []; }
                }
            ');
        }
        
        // Call the method
        $result = $this->factory->validateAgentClass($validAgentClass);
        
        // Assert the result
        $this->assertTrue($result);
    }

    /**
     * Test validateAgentClass method with non-existent class
     */
    public function testValidateAgentClassWithNonExistentClass(): void {
        // Expect exception
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Agent class 'NonExistentClass' does not exist");
        
        // Call the method with non-existent class
        $this->factory->validateAgentClass('NonExistentClass');
    }

    /**
     * Test validateAgentClass method with class that doesn't implement AgentInterface
     */
    public function testValidateAgentClassWithInvalidInterface(): void {
        // Create a mock class that doesn't implement AgentInterface
        $invalidAgentClass = 'MemberpressAiAssistant\Tests\Fixtures\InvalidAgent';
        
        // Define the class for testing
        if (!class_exists($invalidAgentClass)) {
            eval('
                namespace MemberpressAiAssistant\Tests\Fixtures;
                
                class InvalidAgent {
                    public function someMethod() {}
                }
            ');
        }
        
        // Expect exception
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Class 'MemberpressAiAssistant\Tests\Fixtures\InvalidAgent' does not implement AgentInterface");
        
        // Call the method with invalid class
        $this->factory->validateAgentClass($invalidAgentClass);
    }

    /**
     * Test validateAgentClass method with abstract class
     */
    public function testValidateAgentClassWithAbstractClass(): void {
        // Create a mock abstract class that implements AgentInterface
        $abstractAgentClass = 'MemberpressAiAssistant\Tests\Fixtures\AbstractTestAgent';
        
        // Define the class for testing
        if (!class_exists($abstractAgentClass)) {
            eval('
                namespace MemberpressAiAssistant\Tests\Fixtures;
                
                use MemberpressAiAssistant\Interfaces\AgentInterface;
                
                abstract class AbstractTestAgent implements AgentInterface {
                    public function getAgentName(): string { return "AbstractTestAgent"; }
                }
            ');
        }
        
        // Expect exception
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Agent class 'MemberpressAiAssistant\Tests\Fixtures\AbstractTestAgent' is abstract and cannot be instantiated");
        
        // Call the method with abstract class
        $this->factory->validateAgentClass($abstractAgentClass);
    }

    /**
     * Test getAvailableAgentTypes method
     */
    public function testGetAvailableAgentTypes(): void {
        // Define a function to mock WordPress filter
        if (!function_exists('apply_filters')) {
            function apply_filters($tag, $value) {
                if ($tag === 'mpai_agent_types') {
                    return [
                        'test_type' => 'TestAgent',
                        'another_type' => 'AnotherAgent',
                    ];
                }
                return $value;
            }
        }
        
        // Call the method
        $result = $this->factory->getAvailableAgentTypes();
        
        // Assert the result
        $this->assertIsArray($result);
        $this->assertArrayHasKey('test_type', $result);
        $this->assertEquals('TestAgent', $result['test_type']);
        $this->assertArrayHasKey('another_type', $result);
        $this->assertEquals('AnotherAgent', $result['another_type']);
    }
}