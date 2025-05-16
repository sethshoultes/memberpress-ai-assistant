<?php
/**
 * Tests for the ValidationAgent class
 *
 * @package MemberpressAiAssistant\Tests\Unit\Agents
 */

namespace MemberpressAiAssistant\Tests\Unit\Agents;

use MemberpressAiAssistant\Tests\TestCase;
use MemberpressAiAssistant\Agents\ValidationAgent;

/**
 * Test case for ValidationAgent
 */
class ValidationAgentTest extends TestCase {
    /**
     * Agent instance
     *
     * @var ValidationAgent
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
        $this->agent = new ValidationAgent($this->loggerMock);
    }

    /**
     * Test getAgentName method
     */
    public function testGetAgentName(): void {
        $this->assertEquals('Validation Agent', $this->agent->getAgentName());
    }

    /**
     * Test getAgentDescription method
     */
    public function testGetAgentDescription(): void {
        $this->assertEquals(
            'Specialized agent for handling input validation, sanitization, permission verification, and security policy enforcement.',
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
        $this->assertStringContainsString('security and validation assistant', $systemPrompt);
        $this->assertStringContainsString('validating and sanitizing user inputs', $systemPrompt);
        $this->assertStringContainsString('verifying user permissions', $systemPrompt);
        $this->assertStringContainsString('enforcing security policies', $systemPrompt);
        $this->assertStringContainsString('identifying and mitigating potential security vulnerabilities', $systemPrompt);
    }

    /**
     * Test getCapabilities method
     */
    public function testGetCapabilities(): void {
        $capabilities = $this->agent->getCapabilities();
        
        $this->assertIsArray($capabilities);
        $this->assertNotEmpty($capabilities);
        
        // Check for specific capabilities
        $this->assertArrayHasKey('validate_input', $capabilities);
        $this->assertArrayHasKey('sanitize_input', $capabilities);
        $this->assertArrayHasKey('verify_permission', $capabilities);
        $this->assertArrayHasKey('check_access', $capabilities);
        $this->assertArrayHasKey('enforce_policy', $capabilities);
        $this->assertArrayHasKey('validate_form', $capabilities);
        $this->assertArrayHasKey('check_csrf_token', $capabilities);
        $this->assertArrayHasKey('validate_request', $capabilities);
        $this->assertArrayHasKey('scan_for_vulnerabilities', $capabilities);
        $this->assertArrayHasKey('generate_secure_token', $capabilities);
        
        // Check capability metadata
        $this->assertIsArray($capabilities['validate_input']['metadata']);
        $this->assertEquals('Validate user input', $capabilities['validate_input']['metadata']['description']);
        $this->assertIsArray($capabilities['validate_input']['metadata']['parameters']);
    }

    /**
     * Test processRequest method with validate_input intent
     */
    public function testProcessRequestWithValidateInputIntent(): void {
        $request = [
            'intent' => 'validate_input',
            'input' => 'test@example.com',
            'validation_rules' => [
                'required' => true,
                'min_length' => 5,
                'max_length' => 100,
                'pattern' => '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
            ],
        ];
        
        $context = ['user_id' => 123];
        
        // Set up logger expectations
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with(
                $this->stringContains('Processing request with Validation Agent'),
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
        $this->assertEquals('Input is valid', $response['message']);
        
        $this->assertIsArray($response['data']);
        $this->assertArrayHasKey('input', $response['data']);
        $this->assertArrayHasKey('is_valid', $response['data']);
        $this->assertArrayHasKey('errors', $response['data']);
        $this->assertArrayHasKey('validated_at', $response['data']);
        
        $this->assertEquals('test@example.com', $response['data']['input']);
        $this->assertTrue($response['data']['is_valid']);
        $this->assertEmpty($response['data']['errors']);
    }

    /**
     * Test processRequest method with validate_input intent (invalid input)
     */
    public function testProcessRequestWithValidateInputIntentInvalid(): void {
        $request = [
            'intent' => 'validate_input',
            'input' => 'not-an-email',
            'validation_rules' => [
                'required' => true,
                'min_length' => 5,
                'max_length' => 100,
                'pattern' => '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
            ],
        ];
        
        $context = ['user_id' => 123];
        
        // Process the request
        $response = $this->agent->processRequest($request, $context);
        
        // Assert the response
        $this->assertIsArray($response);
        $this->assertEquals('error', $response['status']);
        $this->assertEquals('Input validation failed', $response['message']);
        $this->assertEquals('not-an-email', $response['data']['input']);
        $this->assertFalse($response['data']['is_valid']);
        $this->assertNotEmpty($response['data']['errors']);
    }

    /**
     * Test processRequest method with sanitize_input intent
     */
    public function testProcessRequestWithSanitizeInputIntent(): void {
        $request = [
            'intent' => 'sanitize_input',
            'input' => '<script>alert("XSS")</script>Hello World',
            'sanitization_type' => 'text',
        ];
        
        $context = ['user_id' => 123];
        
        // Process the request
        $response = $this->agent->processRequest($request, $context);
        
        // Assert the response
        $this->assertIsArray($response);
        $this->assertEquals('success', $response['status']);
        $this->assertEquals('Input sanitized successfully', $response['message']);
        $this->assertEquals('<script>alert("XSS")</script>Hello World', $response['data']['original_input']);
        $this->assertEquals('Hello World', $response['data']['sanitized_input']);
        $this->assertEquals('text', $response['data']['sanitization_type']);
    }

    /**
     * Test processRequest method with verify_permission intent
     */
    public function testProcessRequestWithVerifyPermissionIntent(): void {
        $request = [
            'intent' => 'verify_permission',
            'user_id' => 1,
            'permission' => 'manage_options',
            'context' => ['page' => 'settings'],
        ];
        
        $context = ['user_id' => 123];
        
        // Process the request
        $response = $this->agent->processRequest($request, $context);
        
        // Assert the response
        $this->assertIsArray($response);
        $this->assertEquals('success', $response['status']);
        $this->assertEquals('User has the required permission', $response['message']);
        $this->assertEquals(1, $response['data']['user_id']);
        $this->assertEquals('manage_options', $response['data']['permission']);
        $this->assertTrue($response['data']['has_permission']);
    }

    /**
     * Test processRequest method with check_access intent
     */
    public function testProcessRequestWithCheckAccessIntent(): void {
        $request = [
            'intent' => 'check_access',
            'user_id' => 1,
            'resource_id' => 42,
            'resource_type' => 'premium_content',
        ];
        
        $context = ['user_id' => 123];
        
        // Process the request
        $response = $this->agent->processRequest($request, $context);
        
        // Assert the response
        $this->assertIsArray($response);
        $this->assertEquals('success', $response['status']);
        $this->assertEquals('User has access to the resource', $response['message']);
        $this->assertEquals(1, $response['data']['user_id']);
        $this->assertEquals(42, $response['data']['resource_id']);
        $this->assertEquals('premium_content', $response['data']['resource_type']);
        $this->assertTrue($response['data']['has_access']);
    }

    /**
     * Test processRequest method with enforce_policy intent
     */
    public function testProcessRequestWithEnforcePolicyIntent(): void {
        $request = [
            'intent' => 'enforce_policy',
            'policy_name' => 'password_strength',
            'context' => ['user_id' => 123],
        ];
        
        $context = ['user_id' => 123];
        
        // Process the request
        $response = $this->agent->processRequest($request, $context);
        
        // Assert the response
        $this->assertIsArray($response);
        $this->assertEquals('success', $response['status']);
        $this->assertEquals('Security policy enforced successfully', $response['message']);
        $this->assertEquals('password_strength', $response['data']['policy_name']);
        $this->assertIsArray($response['data']['actions']);
        $this->assertNotEmpty($response['data']['actions']);
    }

    /**
     * Test processRequest method with validate_form intent
     */
    public function testProcessRequestWithValidateFormIntent(): void {
        $request = [
            'intent' => 'validate_form',
            'form_data' => [
                'username' => 'testuser',
                'email' => 'test@example.com',
                'password' => 'securepassword123',
            ],
            'form_type' => 'registration',
        ];
        
        $context = ['user_id' => 123];
        
        // Process the request
        $response = $this->agent->processRequest($request, $context);
        
        // Assert the response
        $this->assertIsArray($response);
        $this->assertEquals('success', $response['status']);
        $this->assertEquals('Form validation successful', $response['message']);
        $this->assertEquals('registration', $response['data']['form_type']);
        $this->assertTrue($response['data']['is_valid']);
        $this->assertEmpty($response['data']['errors']);
    }

    /**
     * Test processRequest method with check_csrf_token intent
     */
    public function testProcessRequestWithCheckCsrfTokenIntent(): void {
        $request = [
            'intent' => 'check_csrf_token',
            'token' => '1234567890abcdef1234567890abcdef1234567890abcdef1234567890abcdef',
            'action' => 'update_post',
        ];
        
        $context = ['user_id' => 123];
        
        // Process the request
        $response = $this->agent->processRequest($request, $context);
        
        // Assert the response
        $this->assertIsArray($response);
        $this->assertEquals('success', $response['status']);
        $this->assertEquals('CSRF token is valid', $response['message']);
        $this->assertEquals('update_post', $response['data']['action']);
        $this->assertTrue($response['data']['is_valid']);
    }

    /**
     * Test processRequest method with validate_request intent
     */
    public function testProcessRequestWithValidateRequestIntent(): void {
        $request = [
            'intent' => 'validate_request',
            'request_data' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ],
            'endpoint' => '/api/v1/users',
        ];
        
        $context = ['user_id' => 123];
        
        // Process the request
        $response = $this->agent->processRequest($request, $context);
        
        // Assert the response
        $this->assertIsArray($response);
        $this->assertEquals('success', $response['status']);
        $this->assertEquals('API request is valid', $response['message']);
        $this->assertEquals('/api/v1/users', $response['data']['endpoint']);
        $this->assertTrue($response['data']['is_valid']);
        $this->assertEmpty($response['data']['errors']);
    }

    /**
     * Test processRequest method with scan_for_vulnerabilities intent
     */
    public function testProcessRequestWithScanForVulnerabilitiesIntent(): void {
        $request = [
            'intent' => 'scan_for_vulnerabilities',
            'target' => 'comment-form',
            'scan_type' => 'advanced',
        ];
        
        $context = ['user_id' => 123];
        
        // Process the request
        $response = $this->agent->processRequest($request, $context);
        
        // Assert the response
        $this->assertIsArray($response);
        $this->assertEquals('success', $response['status']);
        $this->assertEquals('Vulnerability scan completed', $response['message']);
        $this->assertEquals('comment-form', $response['data']['target']);
        $this->assertEquals('advanced', $response['data']['scan_type']);
        $this->assertIsArray($response['data']['vulnerabilities']);
        $this->assertGreaterThan(0, $response['data']['vulnerabilities_count']);
    }

    /**
     * Test processRequest method with generate_secure_token intent
     */
    public function testProcessRequestWithGenerateSecureTokenIntent(): void {
        $request = [
            'intent' => 'generate_secure_token',
            'token_type' => 'api_key',
            'expiration' => 86400, // 24 hours
        ];
        
        $context = ['user_id' => 123];
        
        // Process the request
        $response = $this->agent->processRequest($request, $context);
        
        // Assert the response
        $this->assertIsArray($response);
        $this->assertEquals('success', $response['status']);
        $this->assertEquals('Secure token generated successfully', $response['message']);
        $this->assertArrayHasKey('token', $response['data']);
        $this->assertEquals('api_key', $response['data']['token_type']);
        $this->assertArrayHasKey('expires_at', $response['data']);
        $this->assertArrayHasKey('generated_at', $response['data']);
        
        // Token should be 64 characters (32 bytes in hex)
        $this->assertEquals(64, strlen($response['data']['token']));
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
        // Test with validation-related request
        $request = [
            'message' => 'I need to validate user input and check permissions for security',
        ];
        
        $score = $this->agent->getSpecializationScore($request);
        
        // Score should be high for validation-related request
        $this->assertGreaterThan(50, $score);
        
        // Test with non-validation-related request
        $request = [
            'message' => 'I need to create a new blog post with images',
        ];
        
        $score = $this->agent->getSpecializationScore($request);
        
        // Score should be low for non-validation-related request
        $this->assertLessThan(30, $score);
    }
}