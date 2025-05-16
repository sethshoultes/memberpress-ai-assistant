<?php
/**
 * Tests for the SystemAgent class
 *
 * @package MemberpressAiAssistant\Tests\Unit\Agents
 */

namespace MemberpressAiAssistant\Tests\Unit\Agents;

use MemberpressAiAssistant\Tests\TestCase;
use MemberpressAiAssistant\Agents\SystemAgent;

/**
 * Test case for SystemAgent
 */
class SystemAgentTest extends TestCase {
    /**
     * Agent instance
     *
     * @var SystemAgent
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
        $this->agent = new SystemAgent($this->loggerMock);
    }

    /**
     * Test getAgentName method
     */
    public function testGetAgentName(): void {
        $this->assertEquals('System Agent', $this->agent->getAgentName());
    }

    /**
     * Test getAgentDescription method
     */
    public function testGetAgentDescription(): void {
        $this->assertEquals(
            'Specialized agent for handling system configuration, diagnostics, plugin management, and performance monitoring.',
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
        $this->assertStringContainsString('system operations assistant', $systemPrompt);
        $this->assertStringContainsString('system configuration and settings', $systemPrompt);
        $this->assertStringContainsString('diagnostics and troubleshooting', $systemPrompt);
        $this->assertStringContainsString('managing plugins', $systemPrompt);
        $this->assertStringContainsString('monitoring system performance', $systemPrompt);
    }

    /**
     * Test getCapabilities method
     */
    public function testGetCapabilities(): void {
        $capabilities = $this->agent->getCapabilities();
        
        $this->assertIsArray($capabilities);
        $this->assertNotEmpty($capabilities);
        
        // Check for specific capabilities
        $this->assertArrayHasKey('get_system_info', $capabilities);
        $this->assertArrayHasKey('run_diagnostics', $capabilities);
        $this->assertArrayHasKey('update_system_config', $capabilities);
        $this->assertArrayHasKey('get_system_config', $capabilities);
        $this->assertArrayHasKey('list_plugins', $capabilities);
        $this->assertArrayHasKey('activate_plugin', $capabilities);
        $this->assertArrayHasKey('deactivate_plugin', $capabilities);
        $this->assertArrayHasKey('update_plugin', $capabilities);
        $this->assertArrayHasKey('get_plugin_info', $capabilities);
        $this->assertArrayHasKey('monitor_performance', $capabilities);
        $this->assertArrayHasKey('optimize_system', $capabilities);
        $this->assertArrayHasKey('clear_cache', $capabilities);
        
        // Check capability metadata
        $this->assertIsArray($capabilities['get_system_info']['metadata']);
        $this->assertEquals('Get system information', $capabilities['get_system_info']['metadata']['description']);
        $this->assertIsArray($capabilities['get_system_info']['metadata']['parameters']);
    }

    /**
     * Test processRequest method with get_system_info intent
     */
    public function testProcessRequestWithGetSystemInfoIntent(): void {
        $request = [
            'intent' => 'get_system_info',
        ];
        
        $context = ['user_id' => 123];
        
        // Set up logger expectations
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with(
                $this->stringContains('Processing request with System Agent'),
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
        $this->assertEquals('System information retrieved successfully', $response['message']);
        
        $this->assertIsArray($response['data']);
        $this->assertArrayHasKey('php_version', $response['data']);
        $this->assertArrayHasKey('wordpress_version', $response['data']);
        $this->assertArrayHasKey('server_software', $response['data']);
        $this->assertArrayHasKey('database_version', $response['data']);
        $this->assertArrayHasKey('memory_limit', $response['data']);
        $this->assertArrayHasKey('operating_system', $response['data']);
        
        $this->assertEquals(PHP_VERSION, $response['data']['php_version']);
        $this->assertEquals(PHP_OS, $response['data']['operating_system']);
    }

    /**
     * Test processRequest method with run_diagnostics intent
     */
    public function testProcessRequestWithRunDiagnosticsIntent(): void {
        $request = [
            'intent' => 'run_diagnostics',
            'component' => 'database',
        ];
        
        $context = ['user_id' => 123];
        
        // Process the request
        $response = $this->agent->processRequest($request, $context);
        
        // Assert the response
        $this->assertIsArray($response);
        $this->assertEquals('success', $response['status']);
        $this->assertEquals('Diagnostics completed successfully', $response['message']);
        $this->assertEquals('database', $response['data']['component']);
        $this->assertArrayHasKey('tests_run', $response['data']);
        $this->assertArrayHasKey('tests_passed', $response['data']);
        $this->assertArrayHasKey('tests_failed', $response['data']);
        $this->assertArrayHasKey('issues', $response['data']);
        $this->assertIsArray($response['data']['issues']);
    }

    /**
     * Test processRequest method with update_system_config intent
     */
    public function testProcessRequestWithUpdateSystemConfigIntent(): void {
        $request = [
            'intent' => 'update_system_config',
            'config_key' => 'debug_mode',
            'config_value' => 'true',
        ];
        
        $context = ['user_id' => 123];
        
        // Process the request
        $response = $this->agent->processRequest($request, $context);
        
        // Assert the response
        $this->assertIsArray($response);
        $this->assertEquals('success', $response['status']);
        $this->assertEquals('System configuration updated successfully', $response['message']);
        $this->assertEquals('debug_mode', $response['data']['config_key']);
        $this->assertEquals('true', $response['data']['config_value']);
        $this->assertArrayHasKey('updated_at', $response['data']);
    }

    /**
     * Test processRequest method with get_system_config intent
     */
    public function testProcessRequestWithGetSystemConfigIntent(): void {
        $request = [
            'intent' => 'get_system_config',
            'config_key' => 'debug_mode',
        ];
        
        $context = ['user_id' => 123];
        
        // Process the request
        $response = $this->agent->processRequest($request, $context);
        
        // Assert the response
        $this->assertIsArray($response);
        $this->assertEquals('success', $response['status']);
        $this->assertEquals('System configuration retrieved successfully', $response['message']);
        $this->assertEquals('debug_mode', $response['data']['config_key']);
        $this->assertArrayHasKey('config_value', $response['data']);
        $this->assertArrayHasKey('last_updated', $response['data']);
    }

    /**
     * Test processRequest method with list_plugins intent
     */
    public function testProcessRequestWithListPluginsIntent(): void {
        $request = [
            'intent' => 'list_plugins',
            'status' => 'active',
        ];
        
        $context = ['user_id' => 123];
        
        // Process the request
        $response = $this->agent->processRequest($request, $context);
        
        // Assert the response
        $this->assertIsArray($response);
        $this->assertEquals('success', $response['status']);
        $this->assertEquals('Plugins retrieved successfully', $response['message']);
        $this->assertArrayHasKey('plugins', $response['data']);
        $this->assertIsArray($response['data']['plugins']);
        $this->assertGreaterThan(0, count($response['data']['plugins']));
        $this->assertArrayHasKey('total', $response['data']);
        $this->assertArrayHasKey('active', $response['data']);
        $this->assertArrayHasKey('inactive', $response['data']);
        $this->assertArrayHasKey('update_available', $response['data']);
    }

    /**
     * Test processRequest method with activate_plugin intent
     */
    public function testProcessRequestWithActivatePluginIntent(): void {
        $request = [
            'intent' => 'activate_plugin',
            'plugin_slug' => 'hello-dolly',
        ];
        
        $context = ['user_id' => 123];
        
        // Process the request
        $response = $this->agent->processRequest($request, $context);
        
        // Assert the response
        $this->assertIsArray($response);
        $this->assertEquals('success', $response['status']);
        $this->assertEquals('Plugin activated successfully', $response['message']);
        $this->assertEquals('hello-dolly', $response['data']['plugin_slug']);
        $this->assertArrayHasKey('activated_at', $response['data']);
    }

    /**
     * Test processRequest method with deactivate_plugin intent
     */
    public function testProcessRequestWithDeactivatePluginIntent(): void {
        $request = [
            'intent' => 'deactivate_plugin',
            'plugin_slug' => 'akismet',
        ];
        
        $context = ['user_id' => 123];
        
        // Process the request
        $response = $this->agent->processRequest($request, $context);
        
        // Assert the response
        $this->assertIsArray($response);
        $this->assertEquals('success', $response['status']);
        $this->assertEquals('Plugin deactivated successfully', $response['message']);
        $this->assertEquals('akismet', $response['data']['plugin_slug']);
        $this->assertArrayHasKey('deactivated_at', $response['data']);
    }

    /**
     * Test processRequest method with update_plugin intent
     */
    public function testProcessRequestWithUpdatePluginIntent(): void {
        $request = [
            'intent' => 'update_plugin',
            'plugin_slug' => 'akismet',
        ];
        
        $context = ['user_id' => 123];
        
        // Process the request
        $response = $this->agent->processRequest($request, $context);
        
        // Assert the response
        $this->assertIsArray($response);
        $this->assertEquals('success', $response['status']);
        $this->assertEquals('Plugin updated successfully', $response['message']);
        $this->assertEquals('akismet', $response['data']['plugin_slug']);
        $this->assertArrayHasKey('old_version', $response['data']);
        $this->assertArrayHasKey('new_version', $response['data']);
        $this->assertArrayHasKey('updated_at', $response['data']);
    }

    /**
     * Test processRequest method with get_plugin_info intent
     */
    public function testProcessRequestWithGetPluginInfoIntent(): void {
        $request = [
            'intent' => 'get_plugin_info',
            'plugin_slug' => 'memberpress',
        ];
        
        $context = ['user_id' => 123];
        
        // Process the request
        $response = $this->agent->processRequest($request, $context);
        
        // Assert the response
        $this->assertIsArray($response);
        $this->assertEquals('success', $response['status']);
        $this->assertEquals('Plugin information retrieved successfully', $response['message']);
        $this->assertEquals('memberpress', $response['data']['slug']);
        $this->assertArrayHasKey('name', $response['data']);
        $this->assertArrayHasKey('version', $response['data']);
        $this->assertArrayHasKey('author', $response['data']);
        $this->assertArrayHasKey('description', $response['data']);
        $this->assertArrayHasKey('requires_wp', $response['data']);
        $this->assertArrayHasKey('requires_php', $response['data']);
        $this->assertArrayHasKey('active', $response['data']);
        $this->assertArrayHasKey('update_available', $response['data']);
    }

    /**
     * Test processRequest method with monitor_performance intent
     */
    public function testProcessRequestWithMonitorPerformanceIntent(): void {
        $request = [
            'intent' => 'monitor_performance',
            'metrics' => ['cpu', 'memory'],
            'duration' => 30,
        ];
        
        $context = ['user_id' => 123];
        
        // Process the request
        $response = $this->agent->processRequest($request, $context);
        
        // Assert the response
        $this->assertIsArray($response);
        $this->assertEquals('success', $response['status']);
        $this->assertEquals('Performance monitoring completed successfully', $response['message']);
        $this->assertEquals(['cpu', 'memory'], $response['data']['metrics']);
        $this->assertEquals(30, $response['data']['duration']);
        $this->assertArrayHasKey('results', $response['data']);
        $this->assertIsArray($response['data']['results']);
        $this->assertArrayHasKey('cpu', $response['data']['results']);
        $this->assertArrayHasKey('memory', $response['data']['results']);
        $this->assertArrayHasKey('timestamp', $response['data']);
    }

    /**
     * Test processRequest method with optimize_system intent
     */
    public function testProcessRequestWithOptimizeSystemIntent(): void {
        $request = [
            'intent' => 'optimize_system',
            'component' => 'database',
        ];
        
        $context = ['user_id' => 123];
        
        // Process the request
        $response = $this->agent->processRequest($request, $context);
        
        // Assert the response
        $this->assertIsArray($response);
        $this->assertEquals('success', $response['status']);
        $this->assertEquals('System optimization completed successfully', $response['message']);
        $this->assertEquals('database', $response['data']['component']);
        $this->assertArrayHasKey('optimizations_performed', $response['data']);
        $this->assertIsArray($response['data']['optimizations_performed']);
        $this->assertArrayHasKey('performance_improvement', $response['data']);
        $this->assertArrayHasKey('completed_at', $response['data']);
    }

    /**
     * Test processRequest method with clear_cache intent
     */
    public function testProcessRequestWithClearCacheIntent(): void {
        $request = [
            'intent' => 'clear_cache',
            'cache_type' => 'object',
        ];
        
        $context = ['user_id' => 123];
        
        // Process the request
        $response = $this->agent->processRequest($request, $context);
        
        // Assert the response
        $this->assertIsArray($response);
        $this->assertEquals('success', $response['status']);
        $this->assertEquals('Cache cleared successfully', $response['message']);
        $this->assertEquals('object', $response['data']['cache_type']);
        $this->assertArrayHasKey('items_removed', $response['data']);
        $this->assertArrayHasKey('space_freed', $response['data']);
        $this->assertArrayHasKey('cleared_at', $response['data']);
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
        // Test with system-related request
        $request = [
            'message' => 'I need to check system configuration and optimize performance',
        ];
        
        $score = $this->agent->getSpecializationScore($request);
        
        // Score should be high for system-related request
        $this->assertGreaterThan(50, $score);
        
        // Test with non-system-related request
        $request = [
            'message' => 'I need to create a new blog post with images',
        ];
        
        $score = $this->agent->getSpecializationScore($request);
        
        // Score should be low for non-system-related request
        $this->assertLessThan(30, $score);
    }
}