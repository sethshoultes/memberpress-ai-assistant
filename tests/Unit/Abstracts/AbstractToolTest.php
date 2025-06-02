<?php
/**
 * Tests for the AbstractTool class
 *
 * @package MemberpressAiAssistant\Tests\Unit\Abstracts
 */

namespace MemberpressAiAssistant\Tests\Unit\Abstracts;

use MemberpressAiAssistant\Tests\TestCase;
use MemberpressAiAssistant\Abstracts\AbstractTool;

/**
 * Test case for AbstractTool
 */
class AbstractToolTest extends TestCase {
    /**
     * Test tool instance
     *
     * @var AbstractTool
     */
    private $tool;

    /**
     * Set up the test
     */
    protected function setUp(): void {
        parent::setUp();
        
        // Create a concrete implementation of the abstract class for testing
        $this->tool = new class('TestTool', 'Test tool for unit testing') extends AbstractTool {
            /**
             * Get the parameters for this tool
             *
             * @return array
             */
            protected function getParameters(): array {
                return [
                    'param1' => [
                        'type' => 'string',
                        'description' => 'Test parameter 1',
                        'required' => true,
                    ],
                    'param2' => [
                        'type' => 'integer',
                        'description' => 'Test parameter 2',
                        'required' => false,
                    ],
                ];
            }
            
            /**
             * Validate the parameters for this tool
             *
             * @param array $parameters The parameters to validate
             * @return bool|array True if valid, array of errors if invalid
             */
            protected function validateParameters(array $parameters) {
                $errors = [];
                
                // Check required parameters
                if (!isset($parameters['param1'])) {
                    $errors[] = 'param1 is required';
                }
                
                // Check parameter types
                if (isset($parameters['param2']) && !is_int($parameters['param2'])) {
                    $errors[] = 'param2 must be an integer';
                }
                
                return empty($errors) ? true : $errors;
            }
            
            /**
             * Execute the tool with the given parameters
             *
             * @param array $parameters The parameters for the tool execution
             * @return array The result of the tool execution
             */
            public function execute(array $parameters): array {
                // Validate parameters
                $validation = $this->validateParameters($parameters);
                if ($validation !== true) {
                    return [
                        'status' => 'error',
                        'message' => 'Invalid parameters',
                        'errors' => $validation,
                    ];
                }
                
                // Execute the tool
                $result = [
                    'status' => 'success',
                    'message' => 'Tool executed successfully',
                    'data' => $parameters,
                ];
                
                // Log execution
                $this->logExecution($parameters, $result);
                
                return $result;
            }
            
            /**
             * Expose protected methods for testing
             */
            public function exposeValidateParameters(array $parameters) {
                return $this->validateParameters($parameters);
            }
            
            public function exposeLogExecution(array $parameters, array $result): void {
                $this->logExecution($parameters, $result);
            }
        };
    }

    /**
     * Test getToolName method
     */
    public function testGetToolName(): void {
        $this->assertEquals('TestTool', $this->tool->getToolName());
    }

    /**
     * Test getToolDescription method
     */
    public function testGetToolDescription(): void {
        $this->assertEquals('Test tool for unit testing', $this->tool->getToolDescription());
    }

    /**
     * Test getToolDefinition method
     */
    public function testGetToolDefinition(): void {
        $definition = $this->tool->getToolDefinition();
        
        $this->assertIsArray($definition);
        $this->assertArrayHasKey('name', $definition);
        $this->assertArrayHasKey('description', $definition);
        $this->assertArrayHasKey('parameters', $definition);
        
        $this->assertEquals('TestTool', $definition['name']);
        $this->assertEquals('Test tool for unit testing', $definition['description']);
        
        $this->assertIsArray($definition['parameters']);
        $this->assertArrayHasKey('param1', $definition['parameters']);
        $this->assertArrayHasKey('param2', $definition['parameters']);
        
        $this->assertEquals('string', $definition['parameters']['param1']['type']);
        $this->assertEquals('Test parameter 1', $definition['parameters']['param1']['description']);
        $this->assertTrue($definition['parameters']['param1']['required']);
        
        $this->assertEquals('integer', $definition['parameters']['param2']['type']);
        $this->assertEquals('Test parameter 2', $definition['parameters']['param2']['description']);
        $this->assertFalse($definition['parameters']['param2']['required']);
    }

    /**
     * Test validateParameters method
     */
    public function testValidateParameters(): void {
        // Test with valid parameters
        $validParams = [
            'param1' => 'test value',
            'param2' => 42,
        ];
        
        $result = $this->tool->exposeValidateParameters($validParams);
        $this->assertTrue($result);
        
        // Test with missing required parameter
        $invalidParams1 = [
            'param2' => 42,
        ];
        
        $result = $this->tool->exposeValidateParameters($invalidParams1);
        $this->assertIsArray($result);
        $this->assertContains('param1 is required', $result);
        
        // Test with invalid parameter type
        $invalidParams2 = [
            'param1' => 'test value',
            'param2' => 'not an integer',
        ];
        
        $result = $this->tool->exposeValidateParameters($invalidParams2);
        $this->assertIsArray($result);
        $this->assertContains('param2 must be an integer', $result);
    }

    /**
     * Test execute method
     */
    public function testExecute(): void {
        // Test with valid parameters
        $validParams = [
            'param1' => 'test value',
            'param2' => 42,
        ];
        
        $result = $this->tool->execute($validParams);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('data', $result);
        
        $this->assertEquals('success', $result['status']);
        $this->assertEquals('Tool executed successfully', $result['message']);
        $this->assertEquals($validParams, $result['data']);
        
        // Test with invalid parameters
        $invalidParams = [
            'param2' => 'not an integer',
        ];
        
        $result = $this->tool->execute($invalidParams);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('errors', $result);
        
        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Invalid parameters', $result['message']);
        $this->assertIsArray($result['errors']);
        $this->assertCount(2, $result['errors']);
    }

    /**
     * Test logExecution method
     */
    public function testLogExecution(): void {
        // Create a mock logger
        $loggerMock = $this->getMockWithExpectations('stdClass', ['info']);
        
        // Create a tool with the mock logger
        $tool = new class('TestTool', 'Test tool for unit testing', $loggerMock) extends AbstractTool {
            protected function getParameters(): array {
                return [];
            }
            
            public function execute(array $parameters): array {
                return [];
            }
            
            public function exposeLogExecution(array $parameters, array $result): void {
                $this->logExecution($parameters, $result);
            }
        };
        
        // Set up expectations for the logger
        $loggerMock->expects($this->once())
            ->method('info')
            ->with(
                $this->equalTo('Executed tool TestTool'),
                $this->equalTo([
                    'parameters' => ['param' => 'value'],
                    'result' => ['status' => 'success'],
                ])
            );
        
        // Call the method
        $tool->exposeLogExecution(['param' => 'value'], ['status' => 'success']);
    }
}