<?php
/**
 * Tests for the ContentAgent class
 *
 * @package MemberpressAiAssistant\Tests\Unit\Agents
 */

namespace MemberpressAiAssistant\Tests\Unit\Agents;

use MemberpressAiAssistant\Tests\TestCase;
use MemberpressAiAssistant\Agents\ContentAgent;

/**
 * Test case for ContentAgent
 */
class ContentAgentTest extends TestCase {
    /**
     * Agent instance
     *
     * @var ContentAgent
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
        $this->agent = new ContentAgent($this->loggerMock);
    }

    /**
     * Test getAgentName method
     */
    public function testGetAgentName(): void {
        $this->assertEquals('Content Agent', $this->agent->getAgentName());
    }

    /**
     * Test getAgentDescription method
     */
    public function testGetAgentDescription(): void {
        $this->assertEquals(
            'Specialized agent for handling content creation, formatting, media management, and content organization.',
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
        $this->assertStringContainsString('content management assistant', $systemPrompt);
        $this->assertStringContainsString('blog post creation and formatting', $systemPrompt);
        $this->assertStringContainsString('media assets', $systemPrompt);
        $this->assertStringContainsString('organizing content', $systemPrompt);
    }

    /**
     * Test getCapabilities method
     */
    public function testGetCapabilities(): void {
        $capabilities = $this->agent->getCapabilities();
        
        $this->assertIsArray($capabilities);
        $this->assertNotEmpty($capabilities);
        
        // Check for specific capabilities
        $this->assertArrayHasKey('create_post', $capabilities);
        $this->assertArrayHasKey('update_post', $capabilities);
        $this->assertArrayHasKey('delete_post', $capabilities);
        $this->assertArrayHasKey('get_post', $capabilities);
        $this->assertArrayHasKey('list_posts', $capabilities);
        $this->assertArrayHasKey('upload_media', $capabilities);
        $this->assertArrayHasKey('get_media', $capabilities);
        $this->assertArrayHasKey('delete_media', $capabilities);
        $this->assertArrayHasKey('list_media', $capabilities);
        $this->assertArrayHasKey('organize_content', $capabilities);
        $this->assertArrayHasKey('format_content', $capabilities);
        
        // Check capability metadata
        $this->assertIsArray($capabilities['create_post']['metadata']);
        $this->assertEquals('Create a new blog post', $capabilities['create_post']['metadata']['description']);
        $this->assertIsArray($capabilities['create_post']['metadata']['parameters']);
    }

    /**
     * Test processRequest method with create_post intent
     */
    public function testProcessRequestWithCreatePostIntent(): void {
        $request = [
            'intent' => 'create_post',
            'title' => 'Test Blog Post',
            'content' => 'This is a test blog post content.',
            'categories' => ['Test Category'],
            'tags' => ['test', 'blog'],
            'status' => 'draft',
        ];
        
        $context = ['user_id' => 123];
        
        // Set up logger expectations
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with(
                $this->stringContains('Processing request with Content Agent'),
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
        $this->assertEquals('Blog post created successfully', $response['message']);
        
        $this->assertIsArray($response['data']);
        $this->assertArrayHasKey('id', $response['data']);
        $this->assertArrayHasKey('title', $response['data']);
        $this->assertArrayHasKey('status', $response['data']);
        $this->assertArrayHasKey('created_at', $response['data']);
        
        $this->assertEquals('Test Blog Post', $response['data']['title']);
        $this->assertEquals('draft', $response['data']['status']);
    }

    /**
     * Test processRequest method with update_post intent
     */
    public function testProcessRequestWithUpdatePostIntent(): void {
        $request = [
            'intent' => 'update_post',
            'id' => 1001,
            'title' => 'Updated Blog Post',
            'content' => 'This is an updated blog post content.',
            'categories' => ['Updated Category'],
            'tags' => ['updated', 'blog'],
            'status' => 'published',
        ];
        
        $context = ['user_id' => 123];
        
        // Process the request
        $response = $this->agent->processRequest($request, $context);
        
        // Assert the response
        $this->assertIsArray($response);
        $this->assertEquals('success', $response['status']);
        $this->assertEquals('Blog post updated successfully', $response['message']);
        $this->assertEquals(1001, $response['data']['id']);
    }

    /**
     * Test processRequest method with delete_post intent
     */
    public function testProcessRequestWithDeletePostIntent(): void {
        $request = [
            'intent' => 'delete_post',
            'id' => 1001,
        ];
        
        $context = ['user_id' => 123];
        
        // Process the request
        $response = $this->agent->processRequest($request, $context);
        
        // Assert the response
        $this->assertIsArray($response);
        $this->assertEquals('success', $response['status']);
        $this->assertEquals('Blog post deleted successfully', $response['message']);
        $this->assertEquals(1001, $response['data']['id']);
    }

    /**
     * Test processRequest method with get_post intent
     */
    public function testProcessRequestWithGetPostIntent(): void {
        $request = [
            'intent' => 'get_post',
            'id' => 1001,
        ];
        
        $context = ['user_id' => 123];
        
        // Process the request
        $response = $this->agent->processRequest($request, $context);
        
        // Assert the response
        $this->assertIsArray($response);
        $this->assertEquals('success', $response['status']);
        $this->assertEquals('Blog post retrieved successfully', $response['message']);
        $this->assertEquals(1001, $response['data']['id']);
        $this->assertArrayHasKey('title', $response['data']);
        $this->assertArrayHasKey('content', $response['data']);
        $this->assertArrayHasKey('status', $response['data']);
        $this->assertArrayHasKey('categories', $response['data']);
        $this->assertArrayHasKey('tags', $response['data']);
    }

    /**
     * Test processRequest method with list_posts intent
     */
    public function testProcessRequestWithListPostsIntent(): void {
        $request = [
            'intent' => 'list_posts',
            'limit' => 5,
            'offset' => 0,
            'status' => 'published',
        ];
        
        $context = ['user_id' => 123];
        
        // Process the request
        $response = $this->agent->processRequest($request, $context);
        
        // Assert the response
        $this->assertIsArray($response);
        $this->assertEquals('success', $response['status']);
        $this->assertEquals('Blog posts retrieved successfully', $response['message']);
        $this->assertArrayHasKey('posts', $response['data']);
        $this->assertIsArray($response['data']['posts']);
        $this->assertGreaterThan(0, count($response['data']['posts']));
        $this->assertEquals(5, $response['data']['limit']);
        $this->assertEquals(0, $response['data']['offset']);
    }

    /**
     * Test processRequest method with upload_media intent
     */
    public function testProcessRequestWithUploadMediaIntent(): void {
        $request = [
            'intent' => 'upload_media',
            'file' => 'base64_encoded_file_content',
            'title' => 'Test Image',
            'description' => 'This is a test image',
            'alt_text' => 'Test image alt text',
        ];
        
        $context = ['user_id' => 123];
        
        // Process the request
        $response = $this->agent->processRequest($request, $context);
        
        // Assert the response
        $this->assertIsArray($response);
        $this->assertEquals('success', $response['status']);
        $this->assertEquals('Media uploaded successfully', $response['message']);
        $this->assertArrayHasKey('id', $response['data']);
        $this->assertArrayHasKey('title', $response['data']);
        $this->assertArrayHasKey('url', $response['data']);
        $this->assertEquals('Test Image', $response['data']['title']);
    }

    /**
     * Test processRequest method with get_media intent
     */
    public function testProcessRequestWithGetMediaIntent(): void {
        $request = [
            'intent' => 'get_media',
            'id' => 2001,
        ];
        
        $context = ['user_id' => 123];
        
        // Process the request
        $response = $this->agent->processRequest($request, $context);
        
        // Assert the response
        $this->assertIsArray($response);
        $this->assertEquals('success', $response['status']);
        $this->assertEquals('Media retrieved successfully', $response['message']);
        $this->assertEquals(2001, $response['data']['id']);
        $this->assertArrayHasKey('title', $response['data']);
        $this->assertArrayHasKey('description', $response['data']);
        $this->assertArrayHasKey('alt_text', $response['data']);
        $this->assertArrayHasKey('url', $response['data']);
        $this->assertArrayHasKey('type', $response['data']);
    }

    /**
     * Test processRequest method with delete_media intent
     */
    public function testProcessRequestWithDeleteMediaIntent(): void {
        $request = [
            'intent' => 'delete_media',
            'id' => 2001,
        ];
        
        $context = ['user_id' => 123];
        
        // Process the request
        $response = $this->agent->processRequest($request, $context);
        
        // Assert the response
        $this->assertIsArray($response);
        $this->assertEquals('success', $response['status']);
        $this->assertEquals('Media deleted successfully', $response['message']);
        $this->assertEquals(2001, $response['data']['id']);
    }

    /**
     * Test processRequest method with list_media intent
     */
    public function testProcessRequestWithListMediaIntent(): void {
        $request = [
            'intent' => 'list_media',
            'limit' => 5,
            'offset' => 0,
            'type' => 'image',
        ];
        
        $context = ['user_id' => 123];
        
        // Process the request
        $response = $this->agent->processRequest($request, $context);
        
        // Assert the response
        $this->assertIsArray($response);
        $this->assertEquals('success', $response['status']);
        $this->assertEquals('Media files retrieved successfully', $response['message']);
        $this->assertArrayHasKey('media', $response['data']);
        $this->assertIsArray($response['data']['media']);
        $this->assertGreaterThan(0, count($response['data']['media']));
        $this->assertEquals(5, $response['data']['limit']);
        $this->assertEquals(0, $response['data']['offset']);
    }

    /**
     * Test processRequest method with organize_content intent
     */
    public function testProcessRequestWithOrganizeContentIntent(): void {
        $request = [
            'intent' => 'organize_content',
            'content_ids' => [1001, 1002, 1003],
            'categories' => ['Category A', 'Category B'],
            'tags' => ['tag1', 'tag2', 'tag3'],
        ];
        
        $context = ['user_id' => 123];
        
        // Process the request
        $response = $this->agent->processRequest($request, $context);
        
        // Assert the response
        $this->assertIsArray($response);
        $this->assertEquals('success', $response['status']);
        $this->assertEquals('Content organized successfully', $response['message']);
        $this->assertEquals([1001, 1002, 1003], $response['data']['content_ids']);
        $this->assertEquals(['Category A', 'Category B'], $response['data']['categories']);
        $this->assertEquals(['tag1', 'tag2', 'tag3'], $response['data']['tags']);
    }

    /**
     * Test processRequest method with format_content intent
     */
    public function testProcessRequestWithFormatContentIntent(): void {
        $content = 'This is some unformatted content that needs to be formatted properly.';
        $request = [
            'intent' => 'format_content',
            'content' => $content,
            'format_type' => 'html',
        ];
        
        $context = ['user_id' => 123];
        
        // Process the request
        $response = $this->agent->processRequest($request, $context);
        
        // Assert the response
        $this->assertIsArray($response);
        $this->assertEquals('success', $response['status']);
        $this->assertEquals('Content formatted successfully', $response['message']);
        $this->assertEquals(strlen($content), $response['data']['original_length']);
        $this->assertGreaterThan($response['data']['original_length'], $response['data']['formatted_length']);
        $this->assertEquals('html', $response['data']['format_type']);
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
        // Test with content-related request
        $request = [
            'message' => 'I need to create a new blog post with images and format it properly',
        ];
        
        $score = $this->agent->getSpecializationScore($request);
        
        // Score should be high for content-related request
        $this->assertGreaterThan(50, $score);
        
        // Test with non-content-related request
        $request = [
            'message' => 'I need to manage my membership subscriptions and payments',
        ];
        
        $score = $this->agent->getSpecializationScore($request);
        
        // Score should be low for non-content-related request
        $this->assertLessThan(30, $score);
    }
}