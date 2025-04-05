<?php
/**
 * Command Handler
 *
 * Centralized command processing system for MemberPress AI Assistant
 *
 * @package MemberPress AI Assistant
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Command Handler Class
 */
class MPAI_Command_Handler {

    /**
     * WP-CLI executor
     *
     * @var object
     */
    private $wp_cli_executor;

    /**
     * PHP executor
     *
     * @var object
     */
    private $php_executor;

    /**
     * Command sanitizer
     *
     * @var object
     */
    private $sanitizer;

    /**
     * Security filter
     *
     * @var object
     */
    private $security;

    /**
     * Command detector
     *
     * @var object
     */
    private $detector;

    /**
     * Constructor
     */
    public function __construct() {
        error_log('MPAI COMMAND: Initializing Command Handler');

        // Load dependencies
        $this->load_dependencies();

        // Initialize components
        $this->initialize_components();
    }


    /**
     * Load dependencies
     */
    private function load_dependencies() {
        $base_path = dirname(__FILE__);
        
        // Load classes if they don't exist
        if (!class_exists('MPAI_Command_Sanitizer')) {
            include_once $base_path . '/class-mpai-command-sanitizer.php';
        }
        
        if (!class_exists('MPAI_Command_Security')) {
            include_once $base_path . '/class-mpai-command-security.php';
        }
        
        if (!class_exists('MPAI_WP_CLI_Executor')) {
            include_once $base_path . '/class-mpai-wp-cli-executor.php';
        }
        
        if (!class_exists('MPAI_PHP_Executor')) {
            include_once $base_path . '/class-mpai-php-executor.php';
        }
        
        if (!class_exists('MPAI_Command_Detector')) {
            include_once $base_path . '/class-mpai-command-detector.php';
        }
    }

    /**
     * Initialize components
     */
    private function initialize_components() {
        // Initialize command sanitizer
        $this->sanitizer = new MPAI_Command_Sanitizer();
        
        // Initialize security filter
        $this->security = new MPAI_Command_Security();
        
        // Initialize command executors
        $this->wp_cli_executor = new MPAI_WP_CLI_Executor();
        $this->php_executor = new MPAI_PHP_Executor();
        
        // Initialize command detector
        $this->detector = new MPAI_Command_Detector();
    }

    /**
     * Execute a command
     *
     * @param string|array $command Command to execute or message to parse
     * @param array $parameters Additional parameters
     * @return array Execution result
     */
    public function execute_command($command, $parameters = []) {
        try {
            error_log('MPAI COMMAND: Executing command: ' . (is_string($command) ? $command : json_encode($command)));
            
            // If command is a string and seems to be a natural language query, parse it
            if (is_string($command) && !$this->is_direct_command($command)) {
                $detected = $this->detector->detect_command($command);
                if ($detected) {
                    error_log('MPAI COMMAND: Detected command: ' . $detected['command'] . ' (type: ' . $detected['type'] . ')');
                    $command = $detected['command'];
                    // Merge any detected parameters
                    if (isset($detected['parameters'])) {
                        $parameters = array_merge($parameters, $detected['parameters']);
                    }
                }
            }
            
            // If command is an array, extract command and parameters
            if (is_array($command)) {
                if (isset($command['command'])) {
                    // Extract parameters if present
                    if (isset($command['parameters'])) {
                        $parameters = array_merge($parameters, $command['parameters']);
                    }
                    // Get the actual command string
                    $command = $command['command'];
                } else {
                    throw new Exception('Invalid command format');
                }
            }
            
            // Sanitize the command
            $sanitized_command = $this->sanitizer->sanitize($command);
            error_log('MPAI COMMAND: Sanitized command: ' . $sanitized_command);
            
            // Check if command is dangerous
            if ($this->security->is_dangerous($sanitized_command)) {
                error_log('MPAI COMMAND ERROR: Dangerous command blocked: ' . $sanitized_command);
                return [
                    'success' => false,
                    'output' => 'Command blocked for security reasons',
                    'error' => 'Security block: potential dangerous command',
                ];
            }
            
            // Execute the command based on its type
            if ($this->is_php_command($sanitized_command)) {
                return $this->php_executor->execute($sanitized_command, $parameters);
            } else {
                // Default to WP-CLI execution
                return $this->wp_cli_executor->execute($sanitized_command, $parameters);
            }
        } catch (Exception $e) {
            error_log('MPAI COMMAND ERROR: Command execution error: ' . $e->getMessage());
            return [
                'success' => false,
                'output' => 'Error executing command: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check if a string is a direct command rather than a query
     *
     * @param string $command Command string
     * @return bool Whether it's a direct command
     */
    private function is_direct_command($command) {
        // If it starts with wp or php, it's likely a direct command
        return (strpos($command, 'wp ') === 0 || strpos($command, 'php ') === 0);
    }

    /**
     * Check if a command is a PHP command
     *
     * @param string $command Command string
     * @return bool Whether it's a PHP command
     */
    private function is_php_command($command) {
        return (strpos($command, 'php ') === 0);
    }

    /**
     * Process a natural language request and execute appropriate command
     *
     * @param string $message User message
     * @param array $context Additional context
     * @return array Processing result
     */
    public function process_request($message, $context = []) {
        try {
            error_log('MPAI COMMAND: Processing request: ' . $message);
            
            // Detect command from message
            $detected = $this->detector->detect_command($message);
            
            if (!$detected) {
                error_log('MPAI COMMAND WARNING: No command detected in message');
                return [
                    'success' => false,
                    'output' => 'I couldn\'t determine what command to run for that request.',
                    'error' => 'No command detected',
                ];
            }
            
            error_log('MPAI COMMAND: Detected command: ' . $detected['command'] . ' (type: ' . $detected['type'] . ')');
            
            // Execute the detected command
            $result = $this->execute_command($detected['command'], $detected['parameters'] ?? []);
            
            // Add detected information to the result
            $result['detected_type'] = $detected['type'];
            $result['detected_command'] = $detected['command'];
            
            return $result;
        } catch (Exception $e) {
            error_log('MPAI COMMAND ERROR: Request processing error: ' . $e->getMessage());
            return [
                'success' => false,
                'output' => 'Error processing request: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }
}