<?php
/**
 * Tests for the MemberPressAgent class
 *
 * @package MemberpressAiAssistant\Tests\Unit\Agents
 */

namespace MemberpressAiAssistant\Tests\Unit\Agents;

use MemberpressAiAssistant\Tests\TestCase;
use MemberpressAiAssistant\Agents\MemberPressAgent;

/**
 * Test case for MemberPressAgent
 */
class MemberPressAgentTest extends TestCase {
    /**
     * Agent instance
     *
     * @var MemberPressAgent
     */
    private $agent;

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
        
        // Create agent instance
        $this->agent = new MemberPressAgent($this->loggerMock);
    }

    /**
     * Test getAgentName method
     */
    public function testGetAgentName(): void {
        $this->assertEquals('MemberPress Agent', $this->agent->getAgentName());
    }

    /**
     * Test getAgentDescription method
     */
    public function testGetAgentDescription(): void {
        $this->assertEquals(
            'Specialized agent for handling MemberPress membership operations, pricing, terms, and access rules.',
            $this->agent->getAgentDescription()
        );
    }

    /**
     * Test getSystemPrompt method
     */
    public function testGetSystemPrompt(): void {
        $systemPrompt = $this->agent->getSystemPrompt();
        
        $this->assertIsString($systemPrompt);
        $this->assertNotEmpty($systemPrompt);
        $this->assertStringContainsString('MemberPress operations assistant', $systemPrompt);
        $this->assertStringContainsString('membership creation and management', $systemPrompt);
        $this->assertStringContainsString('pricing and terms', $systemPrompt);
        $this->assertStringContainsString('access rules and permissions', $systemPrompt);
    }

    /**
     * Test getCapabilities method
     */
    public function testGetCapabilities(): void {
        $capabilities = $this->agent->getCapabilities();
        
        $this->assertIsArray($capabilities);
        $this->assertNotEmpty($capabilities);
        
        // Check for specific capabilities
        $this->assertArrayHasKey('create_membership', $capabilities);
        $this->assertArrayHasKey('update_membership', $capabilities);
        $this->assertArrayHasKey('delete_membership', $capabilities);
        $this->assertArrayHasKey('get_membership', $capabilities);
        $this->assertArrayHasKey('list_memberships', $capabilities);
        $this->assertArrayHasKey('create_access_rule', $capabilities);
        $this->assertArrayHasKey('update_access_rule', $capabilities);
        $this->assertArrayHasKey('delete_access_rule', $capabilities);
        $this->assertArrayHasKey('manage_pricing', $capabilities);
        
        // Check capability metadata
        $this->assertIsArray($capabilities['create_membership']['metadata']);
        $this->assertEquals('Create a new membership', $capabilities['create_membership']['metadata']['description']);
        $this->assertIsArray($capabilities['create_membership']['metadata']['parameters']);
    }

    /**
     * Test processRequest method with create_membership intent
     */
    public function testProcessRequestWithCreateMembershipIntent(): void {
        $request = [
            'intent' => 'create_membership',
            'name' => 'Test Membership',
            'price' => 19.99,
            'terms' => 'monthly',
        ];
        
        $context = ['user_id' => 123];
        
        // Set up logger expectations
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with(
                $this->stringContains('Processing request with MemberPress Agent'),
                $this->anything()
            );
        
        // Process the request
        $response = $this->agent->processRequest($request, $context);
        
        // Assert the response
        $this->assertIsArray($response);
        $this->assertArrayHasKey('status', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('data', $response);
        
        $this->assertEquals('success', $response['status']);
        $this->assertEquals('Membership created successfully', $response['message']);
        
        $this->assertIsArray($response['data']);
        $this->assertArrayHasKey('id', $response['data']);
        $this->assertArrayHasKey('name', $response['data']);
        $this->assertArrayHasKey('created_at', $response['data']);
        
        $this->assertEquals('Test Membership', $response['data']['name']);
    }

    /**
     * Test processRequest method with update_membership intent
     */
    public function testProcessRequestWithUpdateMembershipIntent(): void {
        $request = [
            'intent' => 'update_membership',
            'id' => 1001,
            'name' => 'Updated Membership',
            'price' => 29.99,
        ];
        
        $context = ['user_id' => 123];
        
        // Process the request
        $response = $this->agent->processRequest($request, $context);
        
        // Assert the response
        $this->assertIsArray($response);
        $this->assertEquals('success', $response['status']);
        $this->assertEquals('Membership updated successfully', $response['message']);
        $this->assertEquals(1001, $response['data']['id']);
    }

    /**
     * Test processRequest method with delete_membership intent
     */
    public function testProcessRequestWithDeleteMembershipIntent(): void {
        $request = [
            'intent' => 'delete_membership',
            'id' => 1001,
        ];
        
        $context = ['user_id' => 123];
        
        // Process the request
        $response = $this->agent->processRequest($request, $context);
        
        // Assert the response
        $this->assertIsArray($response);
        $this->assertEquals('success', $response['status']);
        $this->assertEquals('Membership deleted successfully', $response['message']);
        $this->assertEquals(1001, $response['data']['id']);
    }

    /**
     * Test processRequest method with get_membership intent
     */
    public function testProcessRequestWithGetMembershipIntent(): void {
        $request = [
            'intent' => 'get_membership',
            'id' => 1001,
        ];
        
        $context = ['user_id' => 123];
        
        // Process the request
        $response = $this->agent->processRequest($request, $context);
        
        // Assert the response
        $this->assertIsArray($response);
        $this->assertEquals('success', $response['status']);
        $this->assertEquals('Membership retrieved successfully', $response['message']);
        $this->assertEquals(1001, $response['data']['id']);
        $this->assertArrayHasKey('name', $response['data']);
        $this->assertArrayHasKey('price', $response['data']);
        $this->assertArrayHasKey('terms', $response['data']);
    }

    /**
     * Test processRequest method with list_memberships intent
     */
    public function testProcessRequestWithListMembershipsIntent(): void {
        $request = [
            'intent' => 'list_memberships',
            'limit' => 5,
            'offset' => 0,
        ];
        
        $context = ['user_id' => 123];
        
        // Process the request
        $response = $this->agent->processRequest($request, $context);
        
        // Assert the response
        $this->assertIsArray($response);
        $this->assertEquals('success', $response['status']);
        $this->assertEquals('Memberships retrieved successfully', $response['message']);
        $this->assertArrayHasKey('memberships', $response['data']);
        $this->assertIsArray($response['data']['memberships']);
        $this->assertGreaterThan(0, count($response['data']['memberships']));
        $this->assertEquals(5, $response['data']['limit']);
        $this->assertEquals(0, $response['data']['offset']);
    }

    /**
     * Test processRequest method with create_access_rule intent
     */
    public function testProcessRequestWithCreateAccessRuleIntent(): void {
        $request = [
            'intent' => 'create_access_rule',
            'membership_id' => 1001,
            'content_type' => 'post',
            'content_ids' => [1, 2, 3],
            'rule_type' => 'allow',
        ];
        
        $context = ['user_id' => 123];
        
        // Process the request
        $response = $this->agent->processRequest($request, $context);
        
        // Assert the response
        $this->assertIsArray($response);
        $this->assertEquals('success', $response['status']);
        $this->assertEquals('Access rule created successfully', $response['message']);
        $this->assertEquals(1001, $response['data']['membership_id']);
    }

    /**
     * Test processRequest method with update_access_rule intent
     */
    public function testProcessRequestWithUpdateAccessRuleIntent(): void {
        $request = [
            'intent' => 'update_access_rule',
            'id' => 2001,
            'membership_id' => 1001,
            'content_type' => 'post',
            'content_ids' => [1, 2, 3, 4],
            'rule_type' => 'deny',
        ];
        
        $context = ['user_id' => 123];
        
        // Process the request
        $response = $this->agent->processRequest($request, $context);
        
        // Assert the response
        $this->assertIsArray($response);
        $this->assertEquals('success', $response['status']);
        $this->assertEquals('Access rule updated successfully', $response['message']);
        $this->assertEquals(2001, $response['data']['id']);
    }

    /**
     * Test processRequest method with delete_access_rule intent
     */
    public function testProcessRequestWithDeleteAccessRuleIntent(): void {
        $request = [
            'intent' => 'delete_access_rule',
            'id' => 2001,
        ];
        
        $context = ['user_id' => 123];
        
        // Process the request
        $response = $this->agent->processRequest($request, $context);
        
        // Assert the response
        $this->assertIsArray($response);
        $this->assertEquals('success', $response['status']);
        $this->assertEquals('Access rule deleted successfully', $response['message']);
        $this->assertEquals(2001, $response['data']['id']);
    }

    /**
     * Test processRequest method with manage_pricing intent
     */
    public function testProcessRequestWithManagePricingIntent(): void {
        $request = [
            'intent' => 'manage_pricing',
            'membership_id' => 1001,
            'price' => 29.99,
            'billing_type' => 'recurring',
            'billing_frequency' => 'monthly',
        ];
        
        $context = ['user_id' => 123];
        
        // Process the request
        $response = $this->agent->processRequest($request, $context);
        
        // Assert the response
        $this->assertIsArray($response);
        $this->assertEquals('success', $response['status']);
        $this->assertEquals('Pricing updated successfully', $response['message']);
        $this->assertEquals(1001, $response['data']['membership_id']);
        $this->assertEquals(29.99, $response['data']['price']);
        $this->assertEquals('recurring', $response['data']['billing_type']);
        $this->assertEquals('monthly', $response['data']['billing_frequency']);
    }

    /**
     * Test processRequest method with unknown intent
     */
    public function testProcessRequestWithUnknownIntent(): void {
        $request = [
            'intent' => 'unknown_intent',
        ];
        
        $context = ['user_id' => 123];
        
        // Process the request
        $response = $this->agent->processRequest($request, $context);
        
        // Assert the response
        $this->assertIsArray($response);
        $this->assertEquals('error', $response['status']);
        $this->assertEquals('Unknown intent: unknown_intent', $response['message']);
    }

    /**
     * Test getSpecializationScore method
     */
    public function testGetSpecializationScore(): void {
        // Test with membership-related request
        $request = [
            'message' => 'I need to create a new membership with monthly pricing',
        ];
        
        $score = $this->agent->getSpecializationScore($request);
        
        // Score should be high for membership-related request
        $this->assertGreaterThan(50, $score);
        
        // Test with non-membership-related request
        $request = [
            'message' => 'I need to create a new blog post',
        ];
        
        $score = $this->agent->getSpecializationScore($request);
        
        // Score should be low for non-membership-related request
        $this->assertLessThan(30, $score);
    }
}