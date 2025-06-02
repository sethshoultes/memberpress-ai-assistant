<?php
/**
 * Tests for the AbstractAgent class
 *
 * @package MemberpressAiAssistant\Tests\Unit\Abstracts
 */

namespace MemberpressAiAssistant\Tests\Unit\Abstracts;

use MemberpressAiAssistant\Tests\TestCase;
use MemberpressAiAssistant\Abstracts\AbstractAgent;

/**
 * Test case for AbstractAgent
 */
class AbstractAgentTest extends TestCase {
    /**
     * Test agent instance
     *
     * @var AbstractAgent
     */
    private $agent;

    /**
     * Set up the test
     */
    protected function setUp(): void {
        parent::setUp();
        
        // Create a concrete implementation of the abstract class for testing
        $this->agent = new class() extends AbstractAgent {
            public function getAgentName(): string {
                return 'TestAgent';
            }
            
            public function getAgentDescription(): string {
                return 'Test agent for unit testing';
            }
            
            public function getSystemPrompt(): string {
                return 'You are a test agent for unit testing.';
            }
            
            // Expose protected methods for testing
            public function exposeCalculateIntentMatchScore(array $request): float {
                return $this->calculateIntentMatchScore($request);
            }
            
            public function exposeCalculateEntityRelevanceScore(array $request): float {
                return $this->calculateEntityRelevanceScore($request);
            }
            
            public function exposeCalculateCapabilityMatchScore(array $request): float {
                return $this->calculateCapabilityMatchScore($request);
            }
            
            public function exposeCalculateContextContinuityScore(array $request): float {
                return $this->calculateContextContinuityScore($request);
            }
            
            public function exposeApplyScoreMultipliers(float $score, array $request): float {
                return $this->applyScoreMultipliers($score, $request);
            }
            
            public function exposeRemember(string $key, $value): void {
                $this->remember($key, $value);
            }
            
            public function exposeRecall(string $key, $default = null) {
                return $this->recall($key, $default);
            }
            
            public function exposeForget(string $key, bool $fromLongTerm = false): void {
                $this->forget($key, $fromLongTerm);
            }
            
            public function exposeRememberLongTerm(string $key, $value): void {
                $this->rememberLongTerm($key, $value);
            }
            
            public function exposeGetShortTermMemory(): array {
                return $this->getShortTermMemory();
            }
            
            public function exposeGetLongTermMemory(): array {
                return $this->getLongTermMemory();
            }
            
            public function exposeClearShortTermMemory(): void {
                $this->clearShortTermMemory();
            }
            
            public function exposeAddCapability(string $capability, array $metadata = []): void {
                $this->addCapability($capability, $metadata);
            }
            
            public function exposeRemoveCapability(string $capability): void {
                $this->removeCapability($capability);
            }
            
            public function exposeHasCapability(string $capability): bool {
                return $this->hasCapability($capability);
            }
            
            public function exposeGetCapabilityMetadata(string $capability): ?array {
                return $this->getCapabilityMetadata($capability);
            }
            
            // Override methods that would typically interact with external systems
            protected function loadLongTermMemory(): void {
                $this->longTermMemory = [
                    'test_key' => 'test_value',
                ];
            }
            
            protected function saveLongTermMemory(): void {
                // Do nothing in tests
            }
        };
    }

    /**
     * Test getSpecializationScore method
     */
    public function testGetSpecializationScore(): void {
        $request = ['message' => 'Test message'];
        $score = $this->agent->getSpecializationScore($request);
        
        // Score should be between 0 and 100
        $this->assertGreaterThanOrEqual(0, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    /**
     * Test processRequest method
     */
    public function testProcessRequest(): void {
        $request = ['message' => 'Test message'];
        $context = ['user_id' => 123];
        
        $response = $this->agent->processRequest($request, $context);
        
        // Base implementation should return an error
        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('error', $response['status']);
        $this->assertArrayHasKey('message', $response);
        $this->assertEquals('Not implemented', $response['message']);
        
        // Context should be set
        $this->assertEquals($context, $this->agent->getContext());
    }

    /**
     * Test context management methods
     */
    public function testContextManagement(): void {
        $context = ['user_id' => 123];
        $this->agent->setContext($context);
        
        // Test getContext
        $this->assertEquals($context, $this->agent->getContext());
        
        // Test updateContext
        $contextUpdate = ['session_id' => 'abc123'];
        $this->agent->updateContext($contextUpdate);
        
        $expectedContext = array_merge($context, $contextUpdate);
        $this->assertEquals($expectedContext, $this->agent->getContext());
    }

    /**
     * Test capability management methods
     */
    public function testCapabilityManagement(): void {
        // Test adding a capability
        $this->agent->exposeAddCapability('test_capability', ['priority' => 'high']);
        $this->assertTrue($this->agent->exposeHasCapability('test_capability'));
        
        // Test getting capabilities
        $capabilities = $this->agent->getCapabilities();
        $this->assertArrayHasKey('test_capability', $capabilities);
        
        // Test getting capability metadata
        $metadata = $this->agent->exposeGetCapabilityMetadata('test_capability');
        $this->assertIsArray($metadata);
        $this->assertArrayHasKey('priority', $metadata);
        $this->assertEquals('high', $metadata['priority']);
        
        // Test removing a capability
        $this->agent->exposeRemoveCapability('test_capability');
        $this->assertFalse($this->agent->exposeHasCapability('test_capability'));
    }

    /**
     * Test short-term memory management
     */
    public function testShortTermMemory(): void {
        // Test remembering a value
        $this->agent->exposeRemember('test_key', 'test_value');
        
        // Test recalling a value
        $value = $this->agent->exposeRecall('test_key');
        $this->assertEquals('test_value', $value);
        
        // Test forgetting a value
        $this->agent->exposeForget('test_key');
        $value = $this->agent->exposeRecall('test_key');
        $this->assertNull($value);
        
        // Test memory limit
        for ($i = 0; $i < 15; $i++) {
            $this->agent->exposeRemember("key_{$i}", "value_{$i}");
        }
        
        $memory = $this->agent->exposeGetShortTermMemory();
        // Should only keep the most recent entries (default limit is 10)
        $this->assertLessThanOrEqual(10, count($memory));
        
        // Test clearing memory
        $this->agent->exposeClearShortTermMemory();
        $memory = $this->agent->exposeGetShortTermMemory();
        $this->assertEmpty($memory);
    }

    /**
     * Test long-term memory management
     */
    public function testLongTermMemory(): void {
        // Test loading long-term memory (set in setUp)
        $memory = $this->agent->exposeGetLongTermMemory();
        $this->assertArrayHasKey('test_key', $memory);
        $this->assertEquals('test_value', $memory['test_key']);
        
        // Test remembering a long-term value
        $this->agent->exposeRememberLongTerm('new_key', 'new_value');
        $memory = $this->agent->exposeGetLongTermMemory();
        $this->assertArrayHasKey('new_key', $memory);
        $this->assertEquals('new_value', $memory['new_key']);
        
        // Test recalling a long-term value
        $value = $this->agent->exposeRecall('new_key');
        $this->assertEquals('new_value', $value);
        
        // Test forgetting a long-term value
        $this->agent->exposeForget('new_key', true);
        $memory = $this->agent->exposeGetLongTermMemory();
        $this->assertArrayNotHasKey('new_key', $memory);
    }

    /**
     * Test scoring component methods
     */
    public function testScoringComponents(): void {
        $request = ['message' => 'Test message'];
        
        // Test individual scoring components
        $intentScore = $this->agent->exposeCalculateIntentMatchScore($request);
        $this->assertIsFloat($intentScore);
        
        $entityScore = $this->agent->exposeCalculateEntityRelevanceScore($request);
        $this->assertIsFloat($entityScore);
        
        $capabilityScore = $this->agent->exposeCalculateCapabilityMatchScore($request);
        $this->assertIsFloat($capabilityScore);
        
        $contextScore = $this->agent->exposeCalculateContextContinuityScore($request);
        $this->assertIsFloat($contextScore);
        
        // Test score multipliers
        $score = 50.0;
        $adjustedScore = $this->agent->exposeApplyScoreMultipliers($score, $request);
        $this->assertIsFloat($adjustedScore);
    }
}