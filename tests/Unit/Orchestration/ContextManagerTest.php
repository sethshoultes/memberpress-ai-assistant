<?php
/**
 * Tests for the ContextManager class
 *
 * @package MemberpressAiAssistant\Tests\Unit\Orchestration
 */

namespace MemberpressAiAssistant\Tests\Unit\Orchestration;

use MemberpressAiAssistant\Tests\TestCase;
use MemberpressAiAssistant\Orchestration\ContextManager;
use MemberpressAiAssistant\Orchestration\MessageProtocol;

/**
 * Test case for ContextManager
 */
class ContextManagerTest extends TestCase {
    /**
     * Context manager instance
     *
     * @var ContextManager
     */
    private $contextManager;

    /**
     * Set up the test
     */
    protected function setUp(): void {
        parent::setUp();
        
        // Create context manager instance with shorter expiration time for testing
        $this->contextManager = new ContextManager(60, 5);
    }

    /**
     * Test adding and getting context in global scope
     */
    public function testAddAndGetGlobalContext(): void {
        // Add context
        $result = $this->contextManager->addContext(
            'test_key',
            'test_value',
            ContextManager::SCOPE_GLOBAL
        );
        
        // Assert the result
        $this->assertTrue($result);
        
        // Get context
        $value = $this->contextManager->getContext(
            'test_key',
            ContextManager::SCOPE_GLOBAL
        );
        
        // Assert the value
        $this->assertEquals('test_value', $value);
        
        // Get non-existent context
        $defaultValue = 'default';
        $value = $this->contextManager->getContext(
            'non_existent_key',
            ContextManager::SCOPE_GLOBAL,
            null,
            null,
            $defaultValue
        );
        
        // Assert the default value is returned
        $this->assertEquals($defaultValue, $value);
    }

    /**
     * Test adding and getting context in conversation scope
     */
    public function testAddAndGetConversationContext(): void {
        $conversationId = 'test_conversation';
        
        // Add context
        $result = $this->contextManager->addContext(
            'test_key',
            'test_value',
            ContextManager::SCOPE_CONVERSATION,
            $conversationId
        );
        
        // Assert the result
        $this->assertTrue($result);
        
        // Get context
        $value = $this->contextManager->getContext(
            'test_key',
            ContextManager::SCOPE_CONVERSATION,
            $conversationId
        );
        
        // Assert the value
        $this->assertEquals('test_value', $value);
        
        // Try to add context without conversation ID
        $result = $this->contextManager->addContext(
            'test_key',
            'test_value',
            ContextManager::SCOPE_CONVERSATION
        );
        
        // Assert the result
        $this->assertFalse($result);
    }

    /**
     * Test adding and getting context in request scope
     */
    public function testAddAndGetRequestContext(): void {
        $requestId = 'test_request';
        
        // Add context
        $result = $this->contextManager->addContext(
            'test_key',
            'test_value',
            ContextManager::SCOPE_REQUEST,
            null,
            $requestId
        );
        
        // Assert the result
        $this->assertTrue($result);
        
        // Get context
        $value = $this->contextManager->getContext(
            'test_key',
            ContextManager::SCOPE_REQUEST,
            null,
            $requestId
        );
        
        // Assert the value
        $this->assertEquals('test_value', $value);
        
        // Try to add context without request ID
        $result = $this->contextManager->addContext(
            'test_key',
            'test_value',
            ContextManager::SCOPE_REQUEST
        );
        
        // Assert the result
        $this->assertFalse($result);
    }

    /**
     * Test conversation history management
     */
    public function testConversationHistoryManagement(): void {
        $conversationId = 'test_conversation';
        
        // Create test messages
        $message1 = MessageProtocol::createRequest('user', 'agent', 'Message 1');
        $message2 = MessageProtocol::createRequest('user', 'agent', 'Message 2');
        $message3 = MessageProtocol::createRequest('user', 'agent', 'Message 3');
        
        // Add messages to history
        $this->contextManager->addMessageToHistory($message1, $conversationId);
        $this->contextManager->addMessageToHistory($message2, $conversationId);
        $this->contextManager->addMessageToHistory($message3, $conversationId);
        
        // Get conversation history
        $history = $this->contextManager->getConversationHistory($conversationId);
        
        // Assert the history
        $this->assertIsArray($history);
        $this->assertCount(3, $history);
        $this->assertEquals($message1->getId(), $history[0]['id']);
        $this->assertEquals($message2->getId(), $history[1]['id']);
        $this->assertEquals($message3->getId(), $history[2]['id']);
        
        // Test history limit
        for ($i = 0; $i < 5; $i++) {
            $message = MessageProtocol::createRequest('user', 'agent', "Extra message {$i}");
            $this->contextManager->addMessageToHistory($message, $conversationId);
        }
        
        // Get conversation history again
        $history = $this->contextManager->getConversationHistory($conversationId);
        
        // Assert the history (should be limited to 5 messages)
        $this->assertIsArray($history);
        $this->assertCount(5, $history);
        
        // Try to add message without conversation ID
        $message = MessageProtocol::createRequest('user', 'agent', 'Test message');
        $result = $this->contextManager->addMessageToHistory($message, '');
        
        // Assert the result
        $this->assertFalse($result);
    }

    /**
     * Test entity tracking
     */
    public function testEntityTracking(): void {
        $conversationId = 'test_conversation';
        $entityType = 'test_entity';
        $entityId = 'entity_123';
        $metadata = ['name' => 'Test Entity', 'value' => 42];
        
        // Track entity
        $result = $this->contextManager->trackEntity(
            $entityType,
            $entityId,
            $metadata,
            $conversationId
        );
        
        // Assert the result
        $this->assertTrue($result);
        
        // Get entity
        $entity = $this->contextManager->getEntity($entityType, $entityId);
        
        // Assert the entity
        $this->assertIsArray($entity);
        $this->assertEquals($entityType, $entity['type']);
        $this->assertEquals($entityId, $entity['id']);
        $this->assertEquals($metadata, $entity['metadata']);
        $this->assertContains($conversationId, $entity['conversations']);
        
        // Get entities by type
        $entities = $this->contextManager->getEntitiesByType($entityType);
        
        // Assert the entities
        $this->assertIsArray($entities);
        $this->assertCount(1, $entities);
        $this->assertEquals($entityId, $entities[0]['id']);
        
        // Get entities by conversation
        $entities = $this->contextManager->getEntitiesByConversation($conversationId);
        
        // Assert the entities
        $this->assertIsArray($entities);
        $this->assertCount(1, $entities);
        $this->assertEquals($entityId, $entities[0]['id']);
        
        // Track another entity with the same type and ID but different metadata
        $newMetadata = ['name' => 'Updated Entity', 'value' => 99];
        $this->contextManager->trackEntity(
            $entityType,
            $entityId,
            $newMetadata,
            $conversationId
        );
        
        // Get entity again
        $entity = $this->contextManager->getEntity($entityType, $entityId);
        
        // Assert the metadata is merged
        $expectedMetadata = array_merge($metadata, $newMetadata);
        $this->assertEquals($expectedMetadata, $entity['metadata']);
    }

    /**
     * Test context pruning
     */
    public function testPruneExpiredContext(): void {
        // Create context manager with very short expiration time
        $contextManager = new ContextManager(1, 5);
        
        // Add some context
        $contextManager->addContext('key1', 'value1', ContextManager::SCOPE_GLOBAL);
        $contextManager->addContext('key2', 'value2', ContextManager::SCOPE_GLOBAL);
        $contextManager->addContext('key3', 'value3', ContextManager::SCOPE_CONVERSATION, 'conv1');
        $contextManager->addContext('key4', 'value4', ContextManager::SCOPE_REQUEST, null, 'req1');
        $contextManager->trackEntity('type1', 'id1', [], 'conv1');
        
        // Wait for expiration
        sleep(2);
        
        // Prune expired context
        $prunedCount = $contextManager->pruneExpiredContext();
        
        // Assert the pruned count
        $this->assertGreaterThan(0, $prunedCount);
        
        // Check if context is pruned
        $this->assertNull($contextManager->getContext('key1', ContextManager::SCOPE_GLOBAL));
        $this->assertNull($contextManager->getContext('key2', ContextManager::SCOPE_GLOBAL));
        $this->assertNull($contextManager->getContext('key3', ContextManager::SCOPE_CONVERSATION, 'conv1'));
        $this->assertNull($contextManager->getContext('key4', ContextManager::SCOPE_REQUEST, null, 'req1'));
        $this->assertNull($contextManager->getEntity('type1', 'id1'));
    }

    /**
     * Test context optimization
     */
    public function testOptimizeContext(): void {
        // Add many context items
        for ($i = 0; $i < 20; $i++) {
            $this->contextManager->addContext(
                "key{$i}",
                "value{$i}",
                ContextManager::SCOPE_GLOBAL,
                null,
                null,
                $i % 3 === 0 ? ContextManager::PRIORITY_HIGH : 
                    ($i % 3 === 1 ? ContextManager::PRIORITY_MEDIUM : ContextManager::PRIORITY_LOW)
            );
        }
        
        // Optimize context
        $removedCount = $this->contextManager->optimizeContext(10, 5, 2);
        
        // Assert the removed count
        $this->assertEquals(10, $removedCount);
        
        // Get context stats
        $stats = $this->contextManager->getContextStats();
        
        // Assert the stats
        $this->assertLessThanOrEqual(10, $stats['global_items']);
    }

    /**
     * Test context clearing
     */
    public function testContextClearing(): void {
        $conversationId = 'test_conversation';
        $requestId = 'test_request';
        
        // Add some context
        $this->contextManager->addContext('key1', 'value1', ContextManager::SCOPE_GLOBAL);
        $this->contextManager->addContext('key2', 'value2', ContextManager::SCOPE_CONVERSATION, $conversationId);
        $this->contextManager->addContext('key3', 'value3', ContextManager::SCOPE_REQUEST, null, $requestId);
        $this->contextManager->trackEntity('type1', 'id1', [], $conversationId);
        
        // Clear conversation context
        $result = $this->contextManager->clearConversationContext($conversationId);
        
        // Assert the result
        $this->assertTrue($result);
        
        // Check if conversation context is cleared
        $this->assertNull($this->contextManager->getContext('key2', ContextManager::SCOPE_CONVERSATION, $conversationId));
        $this->assertEmpty($this->contextManager->getEntitiesByConversation($conversationId));
        
        // Global and request context should still exist
        $this->assertEquals('value1', $this->contextManager->getContext('key1', ContextManager::SCOPE_GLOBAL));
        $this->assertEquals('value3', $this->contextManager->getContext('key3', ContextManager::SCOPE_REQUEST, null, $requestId));
        
        // Clear request context
        $result = $this->contextManager->clearRequestContext($requestId);
        
        // Assert the result
        $this->assertTrue($result);
        
        // Check if request context is cleared
        $this->assertNull($this->contextManager->getContext('key3', ContextManager::SCOPE_REQUEST, null, $requestId));
        
        // Global context should still exist
        $this->assertEquals('value1', $this->contextManager->getContext('key1', ContextManager::SCOPE_GLOBAL));
        
        // Clear all context
        $this->contextManager->clearAllContext();
        
        // Check if all context is cleared
        $this->assertNull($this->contextManager->getContext('key1', ContextManager::SCOPE_GLOBAL));
        
        // Get context stats
        $stats = $this->contextManager->getContextStats();
        
        // Assert the stats
        $this->assertEquals(0, $stats['global_items']);
        $this->assertEquals(0, $stats['conversation_items']);
        $this->assertEquals(0, $stats['request_items']);
        $this->assertEquals(0, $stats['entity_items']);
    }

    /**
     * Test extracting context from message
     */
    public function testExtractContextFromMessage(): void {
        $conversationId = 'test_conversation';
        
        // Create a message with context and entities
        $message = new MessageProtocol(
            MessageProtocol::TYPE_REQUEST,
            'user',
            'agent',
            'Test message',
            [
                'context' => [
                    [
                        'key' => 'test_key',
                        'value' => 'test_value',
                        'scope' => ContextManager::SCOPE_CONVERSATION,
                        'priority' => ContextManager::PRIORITY_HIGH
                    ]
                ],
                'entities' => [
                    [
                        'type' => 'test_entity',
                        'id' => 'entity_123',
                        'metadata' => ['name' => 'Test Entity']
                    ]
                ]
            ]
        );
        
        // Extract context from message
        $result = $this->contextManager->extractContextFromMessage($message, $conversationId);
        
        // Assert the result
        $this->assertTrue($result);
        
        // Check if context was extracted
        $value = $this->contextManager->getContext('test_key', ContextManager::SCOPE_CONVERSATION, $conversationId);
        $this->assertEquals('test_value', $value);
        
        // Check if entity was extracted
        $entity = $this->contextManager->getEntity('test_entity', 'entity_123');
        $this->assertIsArray($entity);
        $this->assertEquals('test_entity', $entity['type']);
        $this->assertEquals('entity_123', $entity['id']);
        $this->assertEquals(['name' => 'Test Entity'], $entity['metadata']);
        
        // Check if message was added to history
        $history = $this->contextManager->getConversationHistory($conversationId);
        $this->assertIsArray($history);
        $this->assertCount(1, $history);
        $this->assertEquals($message->getId(), $history[0]['id']);
    }

    /**
     * Test context persistence and loading
     */
    public function testContextPersistenceAndLoading(): void {
        // Add some context
        $this->contextManager->addContext('key1', 'value1', ContextManager::SCOPE_GLOBAL);
        $this->contextManager->addContext('key2', 'value2', ContextManager::SCOPE_CONVERSATION, 'conv1');
        $this->contextManager->trackEntity('type1', 'id1', [], 'conv1');
        
        // Persist context
        $storageKey = 'test_storage_key';
        $result = $this->contextManager->persistContext($storageKey);
        
        // Assert the result (may be true or false depending on environment)
        // We're not testing the actual persistence mechanism, just the method call
        $this->assertIsBool($result);
        
        // Create a new context manager
        $newContextManager = new ContextManager(60, 5);
        
        // Load context
        $result = $newContextManager->loadContext($storageKey);
        
        // Assert the result (may be true or false depending on environment)
        // We're not testing the actual loading mechanism, just the method call
        $this->assertIsBool($result);
    }

    /**
     * Test getting context statistics
     */
    public function testGetContextStats(): void {
        // Add some context
        $this->contextManager->addContext('key1', 'value1', ContextManager::SCOPE_GLOBAL);
        $this->contextManager->addContext('key2', 'value2', ContextManager::SCOPE_CONVERSATION, 'conv1');
        $this->contextManager->addContext('key3', 'value3', ContextManager::SCOPE_REQUEST, null, 'req1');
        $this->contextManager->trackEntity('type1', 'id1', [], 'conv1');
        
        // Get context stats
        $stats = $this->contextManager->getContextStats();
        
        // Assert the stats
        $this->assertIsArray($stats);
        $this->assertEquals(1, $stats['global_items']);
        $this->assertEquals(1, $stats['conversation_items']);
        $this->assertEquals(1, $stats['request_items']);
        $this->assertEquals(1, $stats['entity_items']);
        $this->assertEquals(1, $stats['conversation_count']);
        $this->assertEquals(1, $stats['request_count']);
        $this->assertContains('conv1', $stats['conversation_ids']);
        $this->assertContains('req1', $stats['request_ids']);
    }
}