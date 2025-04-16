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
            mpai_log_debug('Loaded MPAI_Command_Handler class', 'command-adapter');
        }
        
        $this->command_handler = new MPAI_Command_Handler();
        mpai_log_debug('Initialized command handler', 'command-adapter');
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
        mpai_log_debug('Executing tool through adapter: ' . $tool_id, 'command-adapter');
        
        switch ($tool_id) {
            case 'wpcli':
            case 'wp_cli':
                if (isset($parameters['command'])) {
                    $result = $this->command_handler->execute_command($parameters['command'], $parameters);
                    
                    if (!$result['success']) {
                        throw new Exception('Command execution failed: ' . ($result['error'] ?? 'Unknown error'));
                    }
                    
                    // Handle different return format preferences
                    if (isset($parameters['format']) && $parameters['format'] === 'full') {
                        // Return the full result object
                        return $result;
                    } else {
                        // Default to just returning the output for backward compatibility
                        return $result['output'];
                    }
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
        mpai_log_debug('Processing request through adapter: ' . $message, 'command-adapter');
        
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
            mpai_log_error('Cannot register as tool: Tool registry not provided', 'command-adapter');
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
                return $this->execute_tool($parameters);
            }
            
            protected function execute_tool($parameters) {
                return $this->adapter->execute_tool('wpcli', $parameters);
            }
            
            public function get_parameters() {
                return [
                    'command' => [
                        'type' => 'string',
                        'description' => 'The WP-CLI command to execute',
                        'required' => true
                    ],
                    'timeout' => [
                        'type' => 'integer',
                        'description' => 'Execution timeout in seconds (max 60)',
                        'default' => 30
                    ],
                    'format' => [
                        'type' => 'string',
                        'description' => 'Output format',
                        'enum' => ['text', 'json', 'array'],
                        'default' => 'text'
                    ]
                ];
            }
            
            public function get_required_parameters() {
                return ['command'];
            }
        };
        
        // Register the tool
        $tool_registry->register_tool('wpcli_new', $wpcli_tool);
        mpai_log_debug('Registered command adapter as wpcli_new tool', 'command-adapter');
        
        return true;
    }
}