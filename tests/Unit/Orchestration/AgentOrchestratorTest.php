<?php
/**
 * Tests for the AgentOrchestrator class
 *
 * @package MemberpressAiAssistant\Tests\Unit\Orchestration
 */

namespace MemberpressAiAssistant\Tests\Unit\Orchestration;

use MemberpressAiAssistant\Tests\TestCase;
use MemberpressAiAssistant\Orchestration\AgentOrchestrator;
use MemberpressAiAssistant\Orchestration\ContextManager;
use MemberpressAiAssistant\Orchestration\MessageProtocol;
use MemberpressAiAssistant\Factory\AgentFactory;
use MemberpressAiAssistant\Registry\AgentRegistry;
use MemberpressAiAssistant\Interfaces\AgentInterface;

/**
 * Test case for AgentOrchestrator
 */
class AgentOrchestratorTest extends TestCase {
    /**
     * Agent registry mock
     *
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $agentRegistryMock;

    /**
     * Agent factory mock
     *
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $agentFactoryMock;

    /**
     * Context manager mock
     *
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $contextManagerMock;

    /**
     * Logger mock
     *
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerMock;

    /**
     * Orchestrator instance
     *
     * @var AgentOrchestrator
     */
    private $orchestrator;

    /**
     * Set up the test
     */
    protected function setUp(): void {
        parent::setUp();
        
        // Create mocks
        $this->agentRegistryMock = $this->getMockWithExpectations(AgentRegistry::class);
        $this->agentFactoryMock = $this->getMockWithExpectations(AgentFactory::class);
        $this->contextManagerMock = $this->getMockWithExpectations(ContextManager::class);
        $this->loggerMock = $this->getMockWithExpectations('stdClass', ['info', 'warning', 'error']);
        
        // Create orchestrator instance
        $this->orchestrator = new AgentOrchestrator(
            $this->agentRegistryMock,
            $this->agentFactoryMock,
            $this->contextManagerMock,
            $this->loggerMock
        );
    }

    /**
     * Test processUserRequest method with successful agent selection
     */
    public function testProcessUserRequestWithSuccessfulAgentSelection(): void {
        // Create a mock agent
        $agentMock = $this->getMockWithExpectations(AgentInterface::class);
        $agentMock->method('getAgentName')->willReturn('TestAgent');
        
        // Create a mock request
        $request = ['message' => 'Test message'];
        
        // Set up expectations for context manager
        $this->contextManagerMock->expects($this->once())
            ->method('getContext')
            ->with('conversation_data', ContextManager::SCOPE_CONVERSATION, $this->anything())
            ->willReturn(['some_context' => 'value']);
        
        $this->contextManagerMock->expects($this->once())
            ->method('getEntitiesByConversation')
            ->with($this->anything())
            ->willReturn([]);
        
        $this->contextManagerMock->expects($this->once())
            ->method('getConversationHistory')
            ->with($this->anything())
            ->willReturn([]);
        
        // Set up expectations for agent registry
        $this->agentRegistryMock->expects($this->once())
            ->method('findAgentsBySpecialization')
            ->with($this->anything(), 0)
            ->willReturn([
                'TestAgent' => [
                    'agent' => $agentMock,
                    'score' => 75.0,
                ],
            ]);
        
        // Set up expectations for agent
        $agentMock->expects($this->once())
            ->method('processRequest')
            ->with($this->anything(), $this->anything())
            ->willReturn([
                'status' => 'success',
                'message' => 'Response from TestAgent',
                'agent' => 'TestAgent',
            ]);
        
        // Call the method
        $response = $this->orchestrator->processUserRequest($request);
        
        // Assert the response
        $this->assertEquals('success', $response['status']);
        $this->assertEquals('Response from TestAgent', $response['message']);
        $this->assertEquals('TestAgent', $response['agent']);
    }

    /**
     * Test processUserRequest method with no suitable agent
     */
    public function testProcessUserRequestWithNoSuitableAgent(): void {
        // Create a mock request
        $request = ['message' => 'Test message'];
        
        // Set up expectations for context manager
        $this->contextManagerMock->expects($this->once())
            ->method('getContext')
            ->with('conversation_data', ContextManager::SCOPE_CONVERSATION, $this->anything())
            ->willReturn(['some_context' => 'value']);
        
        $this->contextManagerMock->expects($this->once())
            ->method('getEntitiesByConversation')
            ->with($this->anything())
            ->willReturn([]);
        
        $this->contextManagerMock->expects($this->once())
            ->method('getConversationHistory')
            ->with($this->anything())
            ->willReturn([]);
        
        // Set up expectations for agent registry
        $this->agentRegistryMock->expects($this->once())
            ->method('findAgentsBySpecialization')
            ->with($this->anything(), 0)
            ->willReturn([]);
        
        // Call the method
        $response = $this->orchestrator->processUserRequest($request);
        
        // Assert the response
        $this->assertEquals('error', $response['status']);
        $this->assertEquals('No suitable agent found for this request', $response['message']);
    }

    /**
     * Test processUserRequest method with invalid request
     */
    public function testProcessUserRequestWithInvalidRequest(): void {
        // Create an invalid request (missing message)
        $request = ['invalid' => 'request'];
        
        // Call the method
        $response = $this->orchestrator->processUserRequest($request);
        
        // Assert the response
        $this->assertEquals('error', $response['status']);
        $this->assertStringContainsString('Error processing request', $response['message']);
    }

    /**
     * Test processUserRequest method with agent delegation
     */
    public function testProcessUserRequestWithAgentDelegation(): void {
        // Create mock agents
        $agent1Mock = $this->getMockWithExpectations(AgentInterface::class);
        $agent1Mock->method('getAgentName')->willReturn('Agent1');
        
        $agent2Mock = $this->getMockWithExpectations(AgentInterface::class);
        $agent2Mock->method('getAgentName')->willReturn('Agent2');
        
        // Create a mock request
        $request = ['message' => 'Test message'];
        
        // Set up expectations for context manager
        $this->contextManagerMock->expects($this->once())
            ->method('getContext')
            ->with('conversation_data', ContextManager::SCOPE_CONVERSATION, $this->anything())
            ->willReturn(['some_context' => 'value']);
        
        $this->contextManagerMock->expects($this->once())
            ->method('getEntitiesByConversation')
            ->with($this->anything())
            ->willReturn([]);
        
        $this->contextManagerMock->expects($this->once())
            ->method('getConversationHistory')
            ->with($this->anything())
            ->willReturn([]);
        
        // Set up expectations for agent registry
        $this->agentRegistryMock->expects($this->once())
            ->method('findAgentsBySpecialization')
            ->with($this->anything(), 0)
            ->willReturn([
                'Agent1' => [
                    'agent' => $agent1Mock,
                    'score' => 75.0,
                ],
                'Agent2' => [
                    'agent' => $agent2Mock,
                    'score' => 50.0,
                ],
            ]);
        
        // Set up expectations for first agent (delegating to second agent)
        $agent1Mock->expects($this->once())
            ->method('processRequest')
            ->with($this->anything(), $this->anything())
            ->willReturn([
                'status' => 'delegating',
                'message' => 'Delegating to Agent2',
                'agent' => 'Agent1',
                'delegate_to' => 'Agent2',
                'delegation_reason' => 'Agent2 is better suited for this task',
            ]);
        
        // Set up expectations for agent registry to get delegate agent
        $this->agentRegistryMock->expects($this->once())
            ->method('getAgent')
            ->with('Agent2')
            ->willReturn($agent2Mock);
        
        // Set up expectations for second agent
        $agent2Mock->expects($this->once())
            ->method('processRequest')
            ->with($this->anything(), $this->anything())
            ->willReturn([
                'status' => 'success',
                'message' => 'Response from Agent2',
                'agent' => 'Agent2',
            ]);
        
        // Call the method
        $response = $this->orchestrator->processUserRequest($request);
        
        // Assert the response
        $this->assertEquals('success', $response['status']);
        $this->assertEquals('Response from Agent2', $response['message']);
        $this->assertEquals('Agent2', $response['agent']);
        $this->assertEquals('Agent1', $response['delegated_from']);
        $this->assertEquals('Agent2 is better suited for this task', $response['delegation_reason']);
    }

    /**
     * Test processUserRequest method with result aggregation
     */
    public function testProcessUserRequestWithResultAggregation(): void {
        // Create mock agents
        $agent1Mock = $this->getMockWithExpectations(AgentInterface::class);
        $agent1Mock->method('getAgentName')->willReturn('Agent1');
        
        $agent2Mock = $this->getMockWithExpectations(AgentInterface::class);
        $agent2Mock->method('getAgentName')->willReturn('Agent2');
        
        // Create a mock request with aggregation flag
        $request = [
            'message' => 'Test message',
            'aggregate_results' => true,
        ];
        
        // Set up expectations for context manager
        $this->contextManagerMock->expects($this->once())
            ->method('getContext')
            ->with('conversation_data', ContextManager::SCOPE_CONVERSATION, $this->anything())
            ->willReturn(['some_context' => 'value']);
        
        $this->contextManagerMock->expects($this->once())
            ->method('getEntitiesByConversation')
            ->with($this->anything())
            ->willReturn([]);
        
        $this->contextManagerMock->expects($this->once())
            ->method('getConversationHistory')
            ->with($this->anything())
            ->willReturn([]);
        
        // Set up expectations for agent registry
        $this->agentRegistryMock->expects($this->once())
            ->method('findAgentsBySpecialization')
            ->with($this->anything(), 0)
            ->willReturn([
                'Agent1' => [
                    'agent' => $agent1Mock,
                    'score' => 75.0,
                ],
                'Agent2' => [
                    'agent' => $agent2Mock,
                    'score' => 50.0,
                ],
            ]);
        
        // Set up expectations for agents
        $agent1Mock->expects($this->once())
            ->method('processRequest')
            ->with($this->anything(), $this->anything())
            ->willReturn([
                'status' => 'success',
                'message' => 'Response from Agent1',
                'agent' => 'Agent1',
                'data' => ['key1' => 'value1'],
            ]);
        
        $agent2Mock->expects($this->once())
            ->method('processRequest')
            ->with($this->anything(), $this->anything())
            ->willReturn([
                'status' => 'success',
                'message' => 'Response from Agent2',
                'agent' => 'Agent2',
                'data' => ['key2' => 'value2'],
            ]);
        
        // Call the method
        $response = $this->orchestrator->processUserRequest($request);
        
        // Assert the response
        $this->assertEquals('success', $response['status']);
        $this->assertEquals('Aggregated response from multiple agents', $response['message']);
        $this->assertEquals('orchestrator', $response['agent']);
        $this->assertArrayHasKey('aggregated_data', $response);
        $this->assertArrayHasKey('Agent1', $response['aggregated_data']);
        $this->assertArrayHasKey('Agent2', $response['aggregated_data']);
        $this->assertEquals(['key1' => 'value1'], $response['aggregated_data']['Agent1']);
        $this->assertEquals(['key2' => 'value2'], $response['aggregated_data']['Agent2']);
    }

    /**
     * Test conversation management methods
     */
    public function testConversationManagementMethods(): void {
        // Test createNewConversation
        $conversationId = $this->orchestrator->createNewConversation();
        $this->assertNotEmpty($conversationId);
        $this->assertEquals($conversationId, $this->orchestrator->getConversationId());
        
        // Test setConversationId
        $newConversationId = 'test_conversation_id';
        $this->orchestrator->setConversationId($newConversationId);
        $this->assertEquals($newConversationId, $this->orchestrator->getConversationId());
        
        // Test clearConversation
        $this->contextManagerMock->expects($this->once())
            ->method('clearConversationContext')
            ->with($newConversationId)
            ->willReturn(true);
        
        $result = $this->orchestrator->clearConversation();
        $this->assertTrue($result);
    }

    /**
     * Test getStatistics method
     */
    public function testGetStatistics(): void {
        // Set up expectations for context manager
        $this->contextManagerMock->expects($this->once())
            ->method('getContextStats')
            ->willReturn([
                'global_items' => 5,
                'conversation_items' => 10,
                'request_items' => 3,
            ]);
        
        // Set conversation ID
        $conversationId = 'test_conversation_id';
        $this->orchestrator->setConversationId($conversationId);
        
        // Call the method
        $stats = $this->orchestrator->getStatistics();
        
        // Assert the result
        $this->assertIsArray($stats);
        $this->assertEquals($conversationId, $stats['conversation_id']);
        $this->assertArrayHasKey('agent_selection_history', $stats);
        $this->assertArrayHasKey('delegation_stack', $stats);
        $this->assertArrayHasKey('context_stats', $stats);
        $this->assertEquals(5, $stats['context_stats']['global_items']);
        $this->assertEquals(10, $stats['context_stats']['conversation_items']);
        $this->assertEquals(3, $stats['context_stats']['request_items']);
    }
}