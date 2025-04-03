<?php
/**
 * Command Adapter
 *
 * Adapter to integrate the new command system with the existing agent system
 *
 * @package MemberPress AI Assistant
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Command Adapter Class
 */
class MPAI_Command_Adapter {

    /**
     * Command handler instance
     *
     * @var MPAI_Command_Handler
     */
    private $command_handler;

    /**
     * Tool registry instance
     *
     * @var MPAI_Tool_Registry
     */
    private $tool_registry;

    /**
     * Constructor
     * 
     * @param MPAI_Tool_Registry $tool_registry Tool registry
     */
    public function __construct($tool_registry = null) {
        // Store the tool registry
        $this->tool_registry = $tool_registry;
        
        // Initialize the command handler
        $this->initialize_command_handler();
    }


    /**
     * Initialize the command handler
     */
    private function initialize_command_handler() {
        $base_path = dirname(__FILE__);
        
        // Load and initialize command handler
        if (!class_exists('MPAI_Command_Handler')) {
            include_once $base_path . '/class-mpai-command-handler.php';
            error_log('MPAI ADAPTER: Loaded MPAI_Command_Handler class');
        }
        
        $this->command_handler = new MPAI_Command_Handler();
        error_log('MPAI ADAPTER: Initialized command handler');
    }

    /**
     * Execute a command using the new command system
     *
     * @param string $tool_id Tool ID (wp_cli, plugin_logs, etc.)
     * @param array $parameters Tool parameters
     * @return mixed Command execution result
     * @throws Exception If command execution fails
     */
    public function execute_tool($tool_id, $parameters) {
        error_log('MPAI ADAPTER: Executing tool through adapter: ' . $tool_id);
        
        switch ($tool_id) {
            case 'wpcli':
            case 'wp_cli':
                if (isset($parameters['command'])) {
                    $result = $this->command_handler->execute_command($parameters['command'], $parameters);
                    
                    if (!$result['success']) {
                        throw new Exception('Command execution failed: ' . ($result['error'] ?? 'Unknown error'));
                    }
                    
                    return $result['output'];
                } else {
                    throw new Exception('Missing required parameter: command');
                }
                break;
            
            // Pass through other tools to the original tool registry
            default:
                if ($this->tool_registry) {
                    $tool = $this->tool_registry->get_tool($tool_id);
                    
                    if (!$tool) {
                        throw new Exception('Tool not found: ' . $tool_id);
                    }
                    
                    return $tool->execute($parameters);
                } else {
                    throw new Exception('Tool registry not available');
                }
                break;
        }
    }

    /**
     * Process a natural language request
     *
     * @param string $message User message
     * @param array $context Additional context
     * @return array Processing result
     */
    public function process_request($message, $context = []) {
        error_log('MPAI ADAPTER: Processing request through adapter: ' . $message);
        
        // Process the request using the command handler
        $result = $this->command_handler->process_request($message, $context);
        
        // Format the result for the agent system
        return [
            'success' => $result['success'],
            'message' => $result['output'],
            'data' => $result,
            'source' => 'command_adapter',
        ];
    }

    /**
     * Add this adapter as a tool to the tool registry
     *
     * @param MPAI_Tool_Registry $tool_registry Tool registry
     */
    public function register_as_tool($tool_registry) {
        if (!$tool_registry) {
            error_log('MPAI ADAPTER ERROR: Cannot register as tool: Tool registry not provided');
            return false;
        }
        
        // Create a tool wrapper for the WP-CLI tool
        $wpcli_tool = new class($this) extends MPAI_Base_Tool {
            private $adapter;
            
            public function __construct($adapter) {
                $this->adapter = $adapter;
                $this->name = 'WP-CLI Tool (New)';
                $this->description = 'Executes WordPress CLI commands securely using the new command system';
            }
            
            public function execute($parameters) {
                return $this->adapter->execute_tool('wpcli', $parameters);
            }
        };
        
        // Register the tool
        $tool_registry->register_tool('wpcli_new', $wpcli_tool);
        error_log('MPAI ADAPTER: Registered command adapter as wpcli_new tool');
        
        return true;
    }
}