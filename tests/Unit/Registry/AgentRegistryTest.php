<?php
/**
 * Tests for the AgentRegistry class
 *
 * @package MemberpressAiAssistant\Tests\Unit\Registry
 */

namespace MemberpressAiAssistant\Tests\Unit\Registry;

use MemberpressAiAssistant\Tests\TestCase;
use MemberpressAiAssistant\Registry\AgentRegistry;
use MemberpressAiAssistant\Interfaces\AgentInterface;

/**
 * Test case for AgentRegistry
 */
class AgentRegistryTest extends TestCase {
    /**
     * Registry instance
     *
     * @var AgentRegistry
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
        $this->registry = AgentRegistry::getInstance($this->loggerMock);
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
        $reflection = new \ReflectionClass(AgentRegistry::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null, null);
    }

    /**
     * Test getInstance method
     */
    public function testGetInstance(): void {
        // Get an instance
        $instance1 = AgentRegistry::getInstance();
        
        // Get another instance
        $instance2 = AgentRegistry::getInstance();
        
        // They should be the same instance
        $this->assertSame($instance1, $instance2);
    }

    /**
     * Test registerAgent method
     */
    public function testRegisterAgent(): void {
        // Create a mock agent
        $agentMock = $this->getMockWithExpectations(AgentInterface::class);
        $agentMock->method('getAgentName')->willReturn('TestAgent');
        
        // Register the agent
        $result = $this->registry->registerAgent($agentMock);
        
        // Assert the result
        $this->assertTrue($result);
        
        // Check if the agent is registered
        $this->assertTrue($this->registry->hasAgent('TestAgent'));
    }

    /**
     * Test registerAgent method with duplicate agent
     */
    public function testRegisterAgentWithDuplicate(): void {
        // Create a mock agent
        $agentMock = $this->getMockWithExpectations(AgentInterface::class);
        $agentMock->method('getAgentName')->willReturn('TestAgent');
        
        // Register the agent
        $this->registry->registerAgent($agentMock);
        
        // Try to register it again
        $result = $this->registry->registerAgent($agentMock);
        
        // Assert the result
        $this->assertFalse($result);
    }

    /**
     * Test unregisterAgent method
     */
    public function testUnregisterAgent(): void {
        // Create a mock agent
        $agentMock = $this->getMockWithExpectations(AgentInterface::class);
        $agentMock->method('getAgentName')->willReturn('TestAgent');
        
        // Register the agent
        $this->registry->registerAgent($agentMock);
        
        // Unregister the agent
        $result = $this->registry->unregisterAgent('TestAgent');
        
        // Assert the result
        $this->assertTrue($result);
        
        // Check if the agent is unregistered
        $this->assertFalse($this->registry->hasAgent('TestAgent'));
    }

    /**
     * Test unregisterAgent method with non-existent agent
     */
    public function testUnregisterAgentWithNonExistent(): void {
        // Try to unregister a non-existent agent
        $result = $this->registry->unregisterAgent('NonExistentAgent');
        
        // Assert the result
        $this->assertFalse($result);
    }

    /**
     * Test getAgent method
     */
    public function testGetAgent(): void {
        // Create a mock agent
        $agentMock = $this->getMockWithExpectations(AgentInterface::class);
        $agentMock->method('getAgentName')->willReturn('TestAgent');
        
        // Register the agent
        $this->registry->registerAgent($agentMock);
        
        // Get the agent
        $agent = $this->registry->getAgent('TestAgent');
        
        // Assert the result
        $this->assertSame($agentMock, $agent);
    }

    /**
     * Test getAgent method with non-existent agent
     */
    public function testGetAgentWithNonExistent(): void {
        // Try to get a non-existent agent
        $agent = $this->registry->getAgent('NonExistentAgent');
        
        // Assert the result
        $this->assertNull($agent);
    }

    /**
     * Test getAllAgents method
     */
    public function testGetAllAgents(): void {
        // Create mock agents
        $agent1Mock = $this->getMockWithExpectations(AgentInterface::class);
        $agent1Mock->method('getAgentName')->willReturn('Agent1');
        
        $agent2Mock = $this->getMockWithExpectations(AgentInterface::class);
        $agent2Mock->method('getAgentName')->willReturn('Agent2');
        
        // Register the agents
        $this->registry->registerAgent($agent1Mock);
        $this->registry->registerAgent($agent2Mock);
        
        // Get all agents
        $agents = $this->registry->getAllAgents();
        
        // Assert the result
        $this->assertIsArray($agents);
        $this->assertCount(2, $agents);
        $this->assertArrayHasKey('Agent1', $agents);
        $this->assertArrayHasKey('Agent2', $agents);
        $this->assertSame($agent1Mock, $agents['Agent1']);
        $this->assertSame($agent2Mock, $agents['Agent2']);
    }

    /**
     * Test findBestAgentForRequest method
     */
    public function testFindBestAgentForRequest(): void {
        // Create mock agents with different scores
        $agent1Mock = $this->getMockWithExpectations(AgentInterface::class);
        $agent1Mock->method('getAgentName')->willReturn('Agent1');
        $agent1Mock->method('getSpecializationScore')->willReturn(50.0);
        
        $agent2Mock = $this->getMockWithExpectations(AgentInterface::class);
        $agent2Mock->method('getAgentName')->willReturn('Agent2');
        $agent2Mock->method('getSpecializationScore')->willReturn(75.0);
        
        $agent3Mock = $this->getMockWithExpectations(AgentInterface::class);
        $agent3Mock->method('getAgentName')->willReturn('Agent3');
        $agent3Mock->method('getSpecializationScore')->willReturn(25.0);
        
        // Register the agents
        $this->registry->registerAgent($agent1Mock);
        $this->registry->registerAgent($agent2Mock);
        $this->registry->registerAgent($agent3Mock);
        
        // Find the best agent
        $request = ['message' => 'Test message'];
        $bestAgent = $this->registry->findBestAgentForRequest($request);
        
        // Assert the result
        $this->assertSame($agent2Mock, $bestAgent);
    }

    /**
     * Test findBestAgentForRequest method with no agents
     */
    public function testFindBestAgentForRequestWithNoAgents(): void {
        // Find the best agent with no agents registered
        $request = ['message' => 'Test message'];
        $bestAgent = $this->registry->findBestAgentForRequest($request);
        
        // Assert the result
        $this->assertNull($bestAgent);
    }

    /**
     * Test findAgentsByCapability method
     */
    public function testFindAgentsByCapability(): void {
        // Create mock agents with different capabilities
        $agent1Mock = $this->getMockWithExpectations(AgentInterface::class);
        $agent1Mock->method('getAgentName')->willReturn('Agent1');
        $agent1Mock->method('getCapabilities')->willReturn(['capability1' => []]);
        
        $agent2Mock = $this->getMockWithExpectations(AgentInterface::class);
        $agent2Mock->method('getAgentName')->willReturn('Agent2');
        $agent2Mock->method('getCapabilities')->willReturn(['capability1' => [], 'capability2' => []]);
        
        $agent3Mock = $this->getMockWithExpectations(AgentInterface::class);
        $agent3Mock->method('getAgentName')->willReturn('Agent3');
        $agent3Mock->method('getCapabilities')->willReturn(['capability2' => []]);
        
        // Register the agents
        $this->registry->registerAgent($agent1Mock);
        $this->registry->registerAgent($agent2Mock);
        $this->registry->registerAgent($agent3Mock);
        
        // Find agents by capability
        $agents = $this->registry->findAgentsByCapability('capability1');
        
        // Assert the result
        $this->assertIsArray($agents);
        $this->assertCount(2, $agents);
        $this->assertArrayHasKey('Agent1', $agents);
        $this->assertArrayHasKey('Agent2', $agents);
        $this->assertSame($agent1Mock, $agents['Agent1']);
        $this->assertSame($agent2Mock, $agents['Agent2']);
    }

    /**
     * Test findAgentsBySpecialization method
     */
    public function testFindAgentsBySpecialization(): void {
        // Create mock agents with different scores
        $agent1Mock = $this->getMockWithExpectations(AgentInterface::class);
        $agent1Mock->method('getAgentName')->willReturn('Agent1');
        $agent1Mock->method('getSpecializationScore')->willReturn(50.0);
        
        $agent2Mock = $this->getMockWithExpectations(AgentInterface::class);
        $agent2Mock->method('getAgentName')->willReturn('Agent2');
        $agent2Mock->method('getSpecializationScore')->willReturn(75.0);
        
        $agent3Mock = $this->getMockWithExpectations(AgentInterface::class);
        $agent3Mock->method('getAgentName')->willReturn('Agent3');
        $agent3Mock->method('getSpecializationScore')->willReturn(25.0);
        
        // Register the agents
        $this->registry->registerAgent($agent1Mock);
        $this->registry->registerAgent($agent2Mock);
        $this->registry->registerAgent($agent3Mock);
        
        // Find agents by specialization
        $request = ['message' => 'Test message'];
        $agents = $this->registry->findAgentsBySpecialization($request, 50.0);
        
        // Assert the result
        $this->assertIsArray($agents);
        $this->assertCount(2, $agents);
        $this->assertArrayHasKey('Agent1', $agents);
        $this->assertArrayHasKey('Agent2', $agents);
        
        // Check if they are sorted by score descending
        $keys = array_keys($agents);
        $this->assertEquals('Agent2', $keys[0]);
        $this->assertEquals('Agent1', $keys[1]);
    }
}