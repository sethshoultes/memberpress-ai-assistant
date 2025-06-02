<?php
/**
 * Tests for the AgentOrchestrator class
 *
 * @package MemberpressAiAssistant\Tests\Orchestration
 */

namespace MemberpressAiAssistant\Tests\Orchestration;

use MemberpressAiAssistant\Orchestration\AgentOrchestrator;
use MemberpressAiAssistant\Orchestration\ContextManager;
use MemberpressAiAssistant\Factory\AgentFactory;
use MemberpressAiAssistant\Registry\AgentRegistry;
use MemberpressAiAssistant\Services\CacheService;
use MemberpressAiAssistant\Tests\TestCase;
use MemberpressAiAssistant\Tests\Fixtures\MockFactory;

/**
 * Class AgentOrchestratorTest
 */
class AgentOrchestratorTest extends TestCase {
    /**
     * Test that agent responses are cached
     */
    public function testAgentResponseCaching() {
        // Create mock dependencies
        $logger = MockFactory::createMock(\stdClass::class);
        $logger->method('info')->willReturn(null);
        $logger->method('error')->willReturn(null);
        
        $agentRegistry = MockFactory::createMock(AgentRegistry::class);
        $agentFactory = MockFactory::createMock(AgentFactory::class);
        $contextManager = MockFactory::createMock(ContextManager::class);
        
        // Create a real cache service
        $cacheService = new CacheService('cache_service', $logger);
        
        // Create the orchestrator with cache service
        $orchestrator = new AgentOrchestrator(
            $agentRegistry,
            $agentFactory,
            $contextManager,
            $logger,
            $cacheService
        );
        
        // Set a shorter cache TTL for testing
        $orchestrator->setDefaultCacheTtl(60);
        
        // Create a mock agent that returns a predictable response
        $mockAgent = MockFactory::createMockAgent('test_agent', 100);
        $mockAgent->method('processRequest')->willReturn([
            'status' => 'success',
            'message' => 'This is a test response',
            'agent' => 'test_agent',
            'timestamp' => time(),
        ]);
        
        // Mock the agent registry to return our mock agent
        $agentRegistry->method('findAgentsBySpecialization')->willReturn([
            'test_agent' => [
                'agent' => $mockAgent,
                'score' => 100,
            ],
        ]);
        
        // Create a test request
        $request = [
            'message' => 'Test message',
        ];
        
        // Process the request for the first time (should not be cached)
        $response1 = $orchestrator->processUserRequest($request, 'test_conversation');
        
        // Verify the response
        $this->assertSame('success', $response1['status']);
        $this->assertSame('This is a test response', $response1['message']);
        
        // Process the same request again (should be cached)
        $response2 = $orchestrator->processUserRequest($request, 'test_conversation');
        
        // Verify the response is the same
        $this->assertSame($response1, $response2);
        
        // Verify that the cache service has a hit
        $metrics = $cacheService->getMetrics();
        $this->assertGreaterThan(0, $metrics['hits']);
        
        // Change the context to invalidate the cache
        $contextManager->method('getContext')->willReturn(['new_context' => 'value']);
        
        // Process the request again (should not be cached due to context change)
        $response3 = $orchestrator->processUserRequest($request, 'test_conversation');
        
        // Verify the response is still the same (our mock agent returns the same response)
        $this->assertSame($response1, $response3);
        
        // But the cache should have a miss
        $metrics = $cacheService->getMetrics();
        $this->assertGreaterThan(1, $metrics['misses']);
    }
    
    /**
     * Test that the orchestrator works without a cache service
     */
    public function testOrchestratorWithoutCache() {
        // Create mock dependencies
        $logger = MockFactory::createMock(\stdClass::class);
        $logger->method('info')->willReturn(null);
        $logger->method('error')->willReturn(null);
        
        $agentRegistry = MockFactory::createMock(AgentRegistry::class);
        $agentFactory = MockFactory::createMock(AgentFactory::class);
        $contextManager = MockFactory::createMock(ContextManager::class);
        
        // Create the orchestrator without cache service
        $orchestrator = new AgentOrchestrator(
            $agentRegistry,
            $agentFactory,
            $contextManager,
            $logger
        );
        
        // Create a mock agent that returns a predictable response
        $mockAgent = MockFactory::createMockAgent('test_agent', 100);
        $mockAgent->method('processRequest')->willReturn([
            'status' => 'success',
            'message' => 'This is a test response',
            'agent' => 'test_agent',
            'timestamp' => time(),
        ]);
        
        // Mock the agent registry to return our mock agent
        $agentRegistry->method('findAgentsBySpecialization')->willReturn([
            'test_agent' => [
                'agent' => $mockAgent,
                'score' => 100,
            ],
        ]);
        
        // Create a test request
        $request = [
            'message' => 'Test message',
        ];
        
        // Process the request
        $response = $orchestrator->processUserRequest($request, 'test_conversation');
        
        // Verify the response
        $this->assertSame('success', $response['status']);
        $this->assertSame('This is a test response', $response['message']);
    }
}