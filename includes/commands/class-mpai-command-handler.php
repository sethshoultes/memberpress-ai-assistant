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
        mpai_log_debug('Initializing Command Handler', 'command-handler');

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
        mpai_log_debug('Initializing components', 'command-handler');
        
        // Initialize command sanitizer with error handling
        try {
            $this->sanitizer = new MPAI_Command_Sanitizer();
            mpai_log_debug('Successfully initialized Command Sanitizer', 'command-handler');
        } catch (Exception $e) {
            mpai_log_error('Failed to initialize Command Sanitizer: ' . $e->getMessage(), 'command-handler', array(
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ));
            // Create a minimal fallback sanitizer
            $this->sanitizer = new class {
                public function sanitize($command) {
                    return trim(preg_replace('/[;&|><]/', '', $command));
                }
            };
        }
        
        // Initialize security filter with error handling
        try {
            $this->security = new MPAI_Command_Security();
            mpai_log_debug('Successfully initialized Command Security filter', 'command-handler');
        } catch (Exception $e) {
            mpai_log_error('Failed to initialize Command Security filter: ' . $e->getMessage(), 'command-handler', array(
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ));
            // Create a minimal fallback security filter
            $this->security = new class {
                public function is_dangerous($command) {
                    // Basic security check for dangerous patterns
                    $dangerous_patterns = ['/rm\s+-rf/i', '/DROP\s+TABLE/i', '/system\s*\(/i'];
                    foreach ($dangerous_patterns as $pattern) {
                        if (preg_match($pattern, $command)) {
                            return true;
                        }
                    }
                    return false;
                }
            };
        }
        
        // Initialize WP-CLI executor with enhanced error handling
        try {
            $this->wp_cli_executor = new MPAI_WP_CLI_Executor();
            mpai_log_debug('Successfully initialized WP-CLI executor', 'command-handler');
        } catch (Throwable $e) {
            mpai_log_error('Failed to initialize WP-CLI executor: ' . $e->getMessage(), 'command-handler', array(
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ));
            
            // Create a fallback executor that handles the wp plugin list special case
            $this->wp_cli_executor = new class {
                public function execute($command, $parameters = []) {
                    // Special handling for wp plugin list
                    if (trim($command) === 'wp plugin list') {
                        mpai_log_debug('Using fallback wp plugin list handler', 'command-handler');
                        
                        // Load plugin functions if needed
                        if (!function_exists('get_plugins')) {
                            require_once ABSPATH . 'wp-admin/includes/plugin.php';
                        }
                        
                        // Get plugins and format output
                        $all_plugins = get_plugins();
                        $active_plugins = get_option('active_plugins');
                        
                        $output = "NAME\tSTATUS\tVERSION\tDESCRIPTION\n";
                        foreach ($all_plugins as $plugin_path => $plugin_data) {
                            $plugin_status = in_array($plugin_path, $active_plugins) ? 'active' : 'inactive';
                            $name = $plugin_data['Name'];
                            $version = $plugin_data['Version'];
                            $description = isset($plugin_data['Description']) && is_string($plugin_data['Description']) ? 
                                          (strlen($plugin_data['Description']) > 40 ? 
                                          substr($plugin_data['Description'], 0, 40) . '...' : 
                                          $plugin_data['Description']) : '';
                            
                            $output .= "$name\t$plugin_status\t$version\t$description\n";
                        }
                        
                        return [
                            'success' => true,
                            'output' => $output,
                            'command' => 'wp plugin list',
                            'command_type' => 'plugin_list',
                            'result' => $output
                        ];
                    }
                    
                    return [
                        'success' => false,
                        'output' => 'WP-CLI executor could not be initialized. Command: ' . $command,
                        'error' => 'Executor initialization failed'
                    ];
                }
            };
        }
        
        // Initialize PHP executor with enhanced error handling
        try {
            $this->php_executor = new MPAI_PHP_Executor();
            mpai_log_debug('Successfully initialized PHP executor', 'command-handler');
        } catch (Throwable $e) {
            mpai_log_error('Failed to initialize PHP executor: ' . $e->getMessage(), 'command-handler', array(
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ));
            
            // Create a fallback executor
            $this->php_executor = new class {
                public function execute($command, $parameters = []) {
                    // Special handling for php -v
                    if (trim($command) === 'php -v' || trim($command) === 'php --version') {
                        $php_version = phpversion();
                        return [
                            'success' => true,
                            'output' => "PHP $php_version",
                            'command' => $command
                        ];
                    }
                    
                    return [
                        'success' => false,
                        'output' => 'PHP executor could not be initialized. Command: ' . $command,
                        'error' => 'Executor initialization failed'
                    ];
                }
            };
        }
        
        // Initialize command detector with error handling
        try {
            $this->detector = new MPAI_Command_Detector();
            mpai_log_debug('Successfully initialized Command Detector', 'command-handler');
        } catch (Throwable $e) {
            mpai_log_error('Failed to initialize Command Detector: ' . $e->getMessage(), 'command-handler', array(
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ));
            
            // Create a minimal fallback detector
            $this->detector = new class {
                private $logger;
                
                public function __construct() {
                    $this->logger = new class() {
                        public function info($msg) { mpai_log_info($msg, 'fallback-detector'); }
                        public function warning($msg) { mpai_log_warning($msg, 'fallback-detector'); }
                        public function error($msg) { mpai_log_error($msg, 'fallback-detector'); }
                        public function debug($msg) { mpai_log_debug($msg, 'fallback-detector'); }
                    };
                }
                
                public function detect_command($message) {
                    $this->logger->info('Using fallback command detector');
                    
                    // Handle special cases directly
                    if (strpos($message, 'plugin list') !== false || strpos($message, 'list plugins') !== false) {
                        return [
                            'type' => 'plugin_list',
                            'command' => 'wp plugin list',
                            'parameters' => []
                        ];
                    }
                    
                    // Direct commands
                    if (strpos($message, 'wp ') === 0 || strpos($message, 'php ') === 0) {
                        return [
                            'type' => 'explicit',
                            'command' => $message,
                            'parameters' => []
                        ];
                    }
                    
                    return false;
                }
            };
        }
        
        mpai_log_debug('Component initialization complete', 'command-handler');
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
            mpai_log_debug('Executing command: ' . (is_string($command) ? $command : json_encode($command)), 'command-handler');
            
            // If command is a string and seems to be a natural language query, parse it
            if (is_string($command) && !$this->is_direct_command($command)) {
                $detected = $this->detector->detect_command($command);
                if ($detected) {
                    mpai_log_debug('Detected command: ' . $detected['command'] . ' (type: ' . $detected['type'] . ')', 'command-handler');
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
            mpai_log_debug('Sanitized command: ' . $sanitized_command, 'command-handler');
            
            // Check if command is dangerous
            if ($this->security->is_dangerous($sanitized_command)) {
                mpai_log_error('Dangerous command blocked: ' . $sanitized_command, 'command-handler');
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
            mpai_log_error('Command execution error: ' . $e->getMessage(), 'command-handler', array(
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ));
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
            mpai_log_debug('Processing request: ' . $message, 'command-handler');
            
            // Detect command from message
            $detected = $this->detector->detect_command($message);
            
            if (!$detected) {
                mpai_log_warning('No command detected in message', 'command-handler');
                return [
                    'success' => false,
                    'output' => 'I couldn\'t determine what command to run for that request.',
                    'error' => 'No command detected',
                ];
            }
            
            mpai_log_debug('Detected command: ' . $detected['command'] . ' (type: ' . $detected['type'] . ')', 'command-handler');
            
            // Execute the detected command
            $result = $this->execute_command($detected['command'], $detected['parameters'] ?? []);
            
            // Add detected information to the result
            $result['detected_type'] = $detected['type'];
            $result['detected_command'] = $detected['command'];
            
            return $result;
        } catch (Exception $e) {
            mpai_log_error('Request processing error: ' . $e->getMessage(), 'command-handler', array(
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ));
            return [
                'success' => false,
                'output' => 'Error processing request: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }
}