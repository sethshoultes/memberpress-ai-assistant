<?php
/**
 * Tests for the MessageProtocol class
 *
 * @package MemberpressAiAssistant\Tests\Unit\Orchestration
 */

namespace MemberpressAiAssistant\Tests\Unit\Orchestration;

use MemberpressAiAssistant\Tests\TestCase;
use MemberpressAiAssistant\Orchestration\MessageProtocol;

/**
 * Test case for MessageProtocol
 */
class MessageProtocolTest extends TestCase {
    /**
     * Test constructor and basic getters
     */
    public function testConstructorAndGetters(): void {
        // Create a message
        $type = MessageProtocol::TYPE_REQUEST;
        $sender = 'test_sender';
        $recipient = 'test_recipient';
        $content = 'Test message content';
        $metadata = ['key' => 'value'];
        $references = ['ref_key' => 'ref_value'];
        
        $message = new MessageProtocol(
            $type,
            $sender,
            $recipient,
            $content,
            $metadata,
            $references
        );
        
        // Test getters
        $this->assertEquals($type, $message->getType());
        $this->assertEquals($sender, $message->getSender());
        $this->assertEquals($recipient, $message->getRecipient());
        $this->assertEquals($content, $message->getContent());
        $this->assertEquals($metadata, $message->getMetadata());
        $this->assertEquals($references, $message->getReferences());
        
        // Test ID and timestamp
        $this->assertNotEmpty($message->getId());
        $this->assertIsString($message->getId());
        $this->assertStringStartsWith('msg_', $message->getId());
        
        $this->assertIsInt($message->getTimestamp());
        $this->assertGreaterThan(0, $message->getTimestamp());
    }

    /**
     * Test createRequest static method
     */
    public function testCreateRequest(): void {
        $sender = 'test_sender';
        $recipient = 'test_recipient';
        $content = 'Test request content';
        $metadata = ['key' => 'value'];
        
        $message = MessageProtocol::createRequest(
            $sender,
            $recipient,
            $content,
            $metadata
        );
        
        $this->assertEquals(MessageProtocol::TYPE_REQUEST, $message->getType());
        $this->assertEquals($sender, $message->getSender());
        $this->assertEquals($recipient, $message->getRecipient());
        $this->assertEquals($content, $message->getContent());
        $this->assertEquals($metadata, $message->getMetadata());
        $this->assertEmpty($message->getReferences());
    }

    /**
     * Test createResponse static method
     */
    public function testCreateResponse(): void {
        $sender = 'test_sender';
        $recipient = 'test_recipient';
        $content = 'Test response content';
        $requestId = 'req_123';
        $metadata = ['key' => 'value'];
        
        $message = MessageProtocol::createResponse(
            $sender,
            $recipient,
            $content,
            $requestId,
            $metadata
        );
        
        $this->assertEquals(MessageProtocol::TYPE_RESPONSE, $message->getType());
        $this->assertEquals($sender, $message->getSender());
        $this->assertEquals($recipient, $message->getRecipient());
        $this->assertEquals($content, $message->getContent());
        $this->assertEquals($metadata, $message->getMetadata());
        
        $references = $message->getReferences();
        $this->assertArrayHasKey('request_id', $references);
        $this->assertEquals($requestId, $references['request_id']);
    }

    /**
     * Test createDelegation static method
     */
    public function testCreateDelegation(): void {
        $sender = 'test_sender';
        $recipient = 'test_recipient';
        $content = 'Test delegation content';
        $metadata = ['key' => 'value'];
        
        $message = MessageProtocol::createDelegation(
            $sender,
            $recipient,
            $content,
            $metadata
        );
        
        $this->assertEquals(MessageProtocol::TYPE_DELEGATION, $message->getType());
        $this->assertEquals($sender, $message->getSender());
        $this->assertEquals($recipient, $message->getRecipient());
        $this->assertEquals($content, $message->getContent());
        $this->assertEquals($metadata, $message->getMetadata());
        $this->assertEmpty($message->getReferences());
    }

    /**
     * Test createBroadcast static method
     */
    public function testCreateBroadcast(): void {
        $sender = 'test_sender';
        $content = 'Test broadcast content';
        $metadata = ['key' => 'value'];
        
        $message = MessageProtocol::createBroadcast(
            $sender,
            $content,
            $metadata
        );
        
        $this->assertEquals(MessageProtocol::TYPE_BROADCAST, $message->getType());
        $this->assertEquals($sender, $message->getSender());
        $this->assertEquals(MessageProtocol::RECIPIENT_BROADCAST, $message->getRecipient());
        $this->assertEquals($content, $message->getContent());
        $this->assertEquals($metadata, $message->getMetadata());
        $this->assertEmpty($message->getReferences());
        
        // Test isBroadcast method
        $this->assertTrue($message->isBroadcast());
    }

    /**
     * Test createError static method
     */
    public function testCreateError(): void {
        $sender = 'test_sender';
        $recipient = 'test_recipient';
        $error = 'Test error message';
        $metadata = ['key' => 'value'];
        $references = ['request_id' => 'req_123'];
        
        $message = MessageProtocol::createError(
            $sender,
            $recipient,
            $error,
            $metadata,
            $references
        );
        
        $this->assertEquals(MessageProtocol::TYPE_ERROR, $message->getType());
        $this->assertEquals($sender, $message->getSender());
        $this->assertEquals($recipient, $message->getRecipient());
        $this->assertEquals($error, $message->getContent());
        $this->assertEquals($metadata, $message->getMetadata());
        $this->assertEquals($references, $message->getReferences());
    }

    /**
     * Test validate method
     */
    public function testValidate(): void {
        // Create a valid message
        $message = MessageProtocol::createRequest(
            'test_sender',
            'test_recipient',
            'Test content'
        );
        
        // Test validation
        $this->assertTrue($message->validate());
        
        // Create an invalid response message (missing request_id reference)
        $invalidMessage = new MessageProtocol(
            MessageProtocol::TYPE_RESPONSE,
            'test_sender',
            'test_recipient',
            'Test content'
        );
        
        // Test validation
        $this->assertFalse($invalidMessage->validate());
    }

    /**
     * Test toArray and fromArray methods
     */
    public function testToArrayAndFromArray(): void {
        // Create a message
        $originalMessage = MessageProtocol::createRequest(
            'test_sender',
            'test_recipient',
            'Test content',
            ['key' => 'value']
        );
        
        // Convert to array
        $array = $originalMessage->toArray();
        
        // Test array structure
        $this->assertIsArray($array);
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('type', $array);
        $this->assertArrayHasKey('sender', $array);
        $this->assertArrayHasKey('recipient', $array);
        $this->assertArrayHasKey('timestamp', $array);
        $this->assertArrayHasKey('content', $array);
        $this->assertArrayHasKey('metadata', $array);
        $this->assertArrayHasKey('references', $array);
        
        // Create message from array
        $recreatedMessage = MessageProtocol::fromArray($array);
        
        // Test recreated message
        $this->assertInstanceOf(MessageProtocol::class, $recreatedMessage);
        $this->assertEquals($originalMessage->getId(), $recreatedMessage->getId());
        $this->assertEquals($originalMessage->getType(), $recreatedMessage->getType());
        $this->assertEquals($originalMessage->getSender(), $recreatedMessage->getSender());
        $this->assertEquals($originalMessage->getRecipient(), $recreatedMessage->getRecipient());
        $this->assertEquals($originalMessage->getTimestamp(), $recreatedMessage->getTimestamp());
        $this->assertEquals($originalMessage->getContent(), $recreatedMessage->getContent());
        $this->assertEquals($originalMessage->getMetadata(), $recreatedMessage->getMetadata());
        $this->assertEquals($originalMessage->getReferences(), $recreatedMessage->getReferences());
        
        // Test fromArray with invalid data
        $invalidArray = [
            'type' => MessageProtocol::TYPE_REQUEST,
            // Missing required fields
        ];
        
        $nullMessage = MessageProtocol::fromArray($invalidArray);
        $this->assertNull($nullMessage);
    }

    /**
     * Test toJson and fromJson methods
     */
    public function testToJsonAndFromJson(): void {
        // Create a message
        $originalMessage = MessageProtocol::createRequest(
            'test_sender',
            'test_recipient',
            'Test content',
            ['key' => 'value']
        );
        
        // Convert to JSON
        $json = $originalMessage->toJson();
        
        // Test JSON string
        $this->assertIsString($json);
        $this->assertNotEmpty($json);
        
        // Create message from JSON
        $recreatedMessage = MessageProtocol::fromJson($json);
        
        // Test recreated message
        $this->assertInstanceOf(MessageProtocol::class, $recreatedMessage);
        $this->assertEquals($originalMessage->getId(), $recreatedMessage->getId());
        $this->assertEquals($originalMessage->getType(), $recreatedMessage->getType());
        $this->assertEquals($originalMessage->getSender(), $recreatedMessage->getSender());
        $this->assertEquals($originalMessage->getRecipient(), $recreatedMessage->getRecipient());
        $this->assertEquals($originalMessage->getTimestamp(), $recreatedMessage->getTimestamp());
        $this->assertEquals($originalMessage->getContent(), $recreatedMessage->getContent());
        $this->assertEquals($originalMessage->getMetadata(), $recreatedMessage->getMetadata());
        $this->assertEquals($originalMessage->getReferences(), $recreatedMessage->getReferences());
        
        // Test fromJson with invalid JSON
        $invalidJson = '{invalid json}';
        $nullMessage = MessageProtocol::fromJson($invalidJson);
        $this->assertNull($nullMessage);
    }

    /**
     * Test isResponseTo method
     */
    public function testIsResponseTo(): void {
        $requestId = 'req_123';
        
        // Create a response message
        $message = MessageProtocol::createResponse(
            'test_sender',
            'test_recipient',
            'Test content',
            $requestId
        );
        
        // Test isResponseTo
        $this->assertTrue($message->isResponseTo($requestId));
        $this->assertFalse($message->isResponseTo('different_id'));
        
        // Test with non-response message
        $requestMessage = MessageProtocol::createRequest(
            'test_sender',
            'test_recipient',
            'Test content'
        );
        
        $this->assertFalse($requestMessage->isResponseTo($requestId));
    }

    /**
     * Test metadata methods
     */
    public function testMetadataMethods(): void {
        // Create a message with metadata
        $message = MessageProtocol::createRequest(
            'test_sender',
            'test_recipient',
            'Test content',
            ['key1' => 'value1']
        );
        
        // Test getMetadataValue
        $this->assertEquals('value1', $message->getMetadataValue('key1'));
        $this->assertEquals('default', $message->getMetadataValue('non_existent', 'default'));
        
        // Test setMetadata
        $message->setMetadata('key2', 'value2');
        $this->assertEquals('value2', $message->getMetadataValue('key2'));
        
        // Test method chaining
        $returnValue = $message->setMetadata('key3', 'value3');
        $this->assertSame($message, $returnValue);
    }

    /**
     * Test addReference method
     */
    public function testAddReference(): void {
        // Create a message
        $message = MessageProtocol::createRequest(
            'test_sender',
            'test_recipient',
            'Test content'
        );
        
        // Test addReference
        $message->addReference('ref_key', 'ref_value');
        $references = $message->getReferences();
        $this->assertArrayHasKey('ref_key', $references);
        $this->assertEquals('ref_value', $references['ref_key']);
        
        // Test method chaining
        $returnValue = $message->addReference('another_key', 'another_value');
        $this->assertSame($message, $returnValue);
    }
}