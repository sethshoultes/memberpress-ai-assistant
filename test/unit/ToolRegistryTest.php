<?php
/**
 * Test case for the Tool Registry
 *
 * @package MemberPress AI Assistant
 */

class ToolRegistryTest extends WP_UnitTestCase {
    /**
     * Test tool registration and retrieval
     */
    public function testToolRegistration() {
        // Create registry
        $registry = new MPAI_Tool_Registry();
        
        // Create a mock tool
        $mock_tool = $this->getMockBuilder(stdClass::class)
            ->addMethods(['execute'])
            ->getMock();
        
        // Register the mock tool
        $result = $registry->register_tool('test_tool', $mock_tool);
        $this->assertTrue($result);
        
        // Try to register the same tool again
        $result = $registry->register_tool('test_tool', $mock_tool);
        $this->assertFalse($result);
        
        // Get the tool
        $tool = $registry->get_tool('test_tool');
        $this->assertSame($mock_tool, $tool);
        
        // Get a non-existent tool
        $tool = $registry->get_tool('non_existent');
        $this->assertNull($tool);
    }
    
    /**
     * Test tool definition registration and lazy loading
     */
    public function testToolDefinitions() {
        // Create registry
        $registry = new MPAI_Tool_Registry();
        
        // Create a test class for tool definition
        if (!class_exists('TestTool')) {
            class TestTool {
                public function execute() {
                    return 'Test executed';
                }
            }
        }
        
        // Register a tool definition
        $result = $registry->register_tool_definition('lazy_tool', 'TestTool');
        $this->assertTrue($result);
        
        // Check available tools include the lazy tool
        $tools = $registry->get_available_tools();
        $this->assertArrayHasKey('lazy_tool', $tools);
        $this->assertEquals('TestTool', $tools['lazy_tool']['class']);
        $this->assertFalse($tools['lazy_tool']['loaded']);
        
        // Get the lazy tool (triggering lazy loading)
        $tool = $registry->get_tool('lazy_tool');
        $this->assertInstanceOf('TestTool', $tool);
        
        // Check available tools now show the lazy tool as loaded
        $tools = $registry->get_available_tools();
        $this->assertInstanceOf('TestTool', $tools['lazy_tool']);
    }
    
    /**
     * Test tool definition with file loading
     */
    public function testToolDefinitionWithFile() {
        // Create a temporary test file
        $temp_file = sys_get_temp_dir() . '/test_file_tool.php';
        $class_code = '<?php
            class TestFileTool {
                public function execute() {
                    return "File tool executed";
                }
            }
        ?>';
        file_put_contents($temp_file, $class_code);
        
        // Create registry
        $registry = new MPAI_Tool_Registry();
        
        // Register a tool definition with file path
        $result = $registry->register_tool_definition('file_tool', 'TestFileTool', $temp_file);
        $this->assertTrue($result);
        
        // Get the tool (triggering lazy loading and file inclusion)
        $tool = $registry->get_tool('file_tool');
        $this->assertInstanceOf('TestFileTool', $tool);
        
        // Clean up the temporary file
        @unlink($temp_file);
    }
    
    /**
     * Test tool definition with non-existent file
     */
    public function testToolDefinitionWithNonExistentFile() {
        // Create registry
        $registry = new MPAI_Tool_Registry();
        
        // Register a tool definition with non-existent file path
        $result = $registry->register_tool_definition('non_existent_file_tool', 'NonExistentTool', '/path/to/non/existent/file.php');
        $this->assertTrue($result);
        
        // Try to get the tool
        $tool = $registry->get_tool('non_existent_file_tool');
        $this->assertNull($tool);
    }
    
    /**
     * Test tool definition with non-existent class
     */
    public function testToolDefinitionWithNonExistentClass() {
        // Create registry
        $registry = new MPAI_Tool_Registry();
        
        // Register a tool definition with non-existent class
        $result = $registry->register_tool_definition('non_existent_class_tool', 'NonExistentClass');
        $this->assertTrue($result);
        
        // Try to get the tool
        $tool = $registry->get_tool('non_existent_class_tool');
        $this->assertNull($tool);
    }
}