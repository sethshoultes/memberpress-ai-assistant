<?php
/**
 * MemberPress AI Assistant - Error Recovery System
 *
 * Provides standardized error handling and recovery mechanisms
 * for graceful degradation when errors occur.
 *
 * @package MemberPress AI Assistant
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Error Recovery System
 *
 * Provides standardized error handling and recovery mechanisms.
 */
class MPAI_Error_Recovery {
    /**
     * Error types with severity levels
     */
    const ERROR_TYPE_API = 'api';
    const ERROR_TYPE_TOOL = 'tool';
    const ERROR_TYPE_AGENT = 'agent';
    const ERROR_TYPE_DATABASE = 'database';
    const ERROR_TYPE_PERMISSION = 'permission';
    const ERROR_TYPE_VALIDATION = 'validation';
    const ERROR_TYPE_TIMEOUT = 'timeout';
    const ERROR_TYPE_RESOURCE = 'resource';
    const ERROR_TYPE_SYSTEM = 'system';
    const ERROR_TYPE_NETWORK = 'network';
    
    /**
     * Severity levels
     */
    const SEVERITY_CRITICAL = 'critical'; // System cannot function, no recovery possible
    const SEVERITY_ERROR = 'error';       // Component failed, recovery may be possible
    const SEVERITY_WARNING = 'warning';   // Issue detected but operation can continue
    const SEVERITY_INFO = 'info';         // Informational message about recovery
    
    /**
     * Instance of this class
     *
     * @var MPAI_Error_Recovery
     */
    private static $instance = null;
    
    /**
     * Logger instance
     *
     * @var MPAI_Plugin_Logger
     */
    private $logger = null;
    
    /**
     * Recovery strategies by component
     *
     * @var array
     */
    private $recovery_strategies = [];
    
    /**
     * Fallback components
     *
     * @var array
     */
    private $fallbacks = [];
    
    /**
     * Error counters for circuit breaking
     *
     * @var array
     */
    private $error_counters = [];
    
    /**
     * Circuit breaker states
     *
     * @var array
     */
    private $circuit_breakers = [];
    
    /**
     * Get instance of this class
     *
     * @return MPAI_Error_Recovery
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Initialize logger
        if (class_exists('MPAI_Plugin_Logger')) {
            $this->logger = mpai_init_plugin_logger();
        }
        
        // Register default recovery strategies
        $this->register_default_strategies();
        
        // Register default fallbacks
        $this->register_default_fallbacks();
    }
    
    /**
     * Register default recovery strategies
     */
    private function register_default_strategies() {
        // API recovery strategies
        $this->recovery_strategies[self::ERROR_TYPE_API] = [
            'retry' => true,
            'max_retries' => 3,
            'retry_delay' => 1, // in seconds
            'fallback_available' => true,
            'circuit_breaker' => [
                'threshold' => 5,
                'reset_time' => 300, // 5 minutes
            ],
        ];
        
        // Tool recovery strategies
        $this->recovery_strategies[self::ERROR_TYPE_TOOL] = [
            'retry' => true,
            'max_retries' => 2,
            'alternative_tools' => true,
            'circuit_breaker' => [
                'threshold' => 3,
                'reset_time' => 600, // 10 minutes
            ],
        ];
        
        // Agent recovery strategies
        $this->recovery_strategies[self::ERROR_TYPE_AGENT] = [
            'retry' => true,
            'max_retries' => 1,
            'fallback_available' => true,
            'degraded_mode' => true,
        ];
        
        // Database recovery strategies
        $this->recovery_strategies[self::ERROR_TYPE_DATABASE] = [
            'retry' => true,
            'max_retries' => 3,
            'retry_delay' => 2,
            'in_memory_fallback' => true,
        ];
    }
    
    /**
     * Register default fallbacks
     */
    private function register_default_fallbacks() {
        // API fallbacks
        $this->fallbacks[self::ERROR_TYPE_API] = [
            'openai' => 'anthropic',
            'anthropic' => 'openai',
        ];
        
        // Tool fallbacks
        $this->fallbacks[self::ERROR_TYPE_TOOL] = [
            'wp_cli' => 'wp_api',
            'wp_api' => 'wordpress',
        ];
    }
    
    /**
     * Create an error object with standardized format and enhanced context
     *
     * @param string $type Error type
     * @param string $code Error code
     * @param string $message Error message
     * @param array $context Additional context
     * @param string $severity Severity level
     * @return WP_Error
     */
    public function create_error($type, $code, $message, $context = [], $severity = self::SEVERITY_ERROR) {
        // Create a unique error ID for tracking
        $error_id = uniqid('mpai_error_');
        
        // Add standard metadata
        $error_data = [
            'error_id' => $error_id,
            'type' => $type,
            'severity' => $severity,
            'timestamp' => current_time('mysql'),
            'context' => $context,
        ];
        
        // Add to error counter for circuit breaking
        $this->increment_error_counter($type, $code);
        
        // Log the error
        $this->log_error($type, $code, $message, $error_data);
        
        // Create WP_Error object
        return new WP_Error($code, $message, $error_data);
    }
    
    /**
     * Log an error with appropriate severity
     *
     * @param string $type Error type
     * @param string $code Error code
     * @param string $message Error message
     * @param array $data Error data
     */
    private function log_error($type, $code, $message, $data) {
        $severity = isset($data['severity']) ? $data['severity'] : self::SEVERITY_ERROR;
        
        $log_message = sprintf(
            'MPAI Error [%s] %s: %s (Error ID: %s)',
            strtoupper($type),
            $code,
            $message,
            $data['error_id']
        );
        
        // Log with appropriate method based on severity
        error_log($log_message);
        
        // Add to plugin logger if available
        if ($this->logger) {
            // Convert our severity to logger actions
            switch ($severity) {
                case self::SEVERITY_CRITICAL:
                    // Log critical errors using public methods only
                    $error_log_data = [
                        'error_id' => $data['error_id'],
                        'type' => $type,
                        'code' => $code,
                        'message' => $message,
                        'severity' => $severity,
                        'context' => isset($data['context']) ? $data['context'] : [],
                    ];
                    
                    // Use error_log instead of trying to access the private method
                    error_log('MPAI Critical Error: ' . json_encode($error_log_data));
                    break;
                    
                case self::SEVERITY_ERROR:
                    // Log error using public methods only
                    $error_log_data = [
                        'error_id' => $data['error_id'],
                        'type' => $type,
                        'code' => $code,
                        'message' => $message,
                        'context' => isset($data['context']) ? $data['context'] : [],
                    ];
                    
                    // Use error_log instead of trying to access the private method
                    error_log('MPAI Error: ' . json_encode($error_log_data));
                    break;
                    
                default:
                    // Log warnings and info as regular events
                    break;
            }
        }
    }
    
    /**
     * Handle an error with appropriate recovery strategy
     *
     * @param WP_Error $error The error to handle
     * @param string $component The component that failed
     * @param callable $retry_callback Function to call for retry
     * @param array $retry_args Arguments for retry callback
     * @param callable $fallback_callback Function to call for fallback
     * @param array $fallback_args Arguments for fallback callback
     * @return mixed Result from successful retry, fallback, or the original error
     */
    public function handle_error($error, $component, $retry_callback = null, $retry_args = [], $fallback_callback = null, $fallback_args = []) {
        if (!is_wp_error($error)) {
            return $error;
        }
        
        $error_data = $error->get_error_data();
        $error_type = isset($error_data['type']) ? $error_data['type'] : self::ERROR_TYPE_SYSTEM;
        
        // Check if circuit breaker is tripped
        if ($this->is_circuit_breaker_tripped($error_type, $component)) {
            $fallback_message = "Circuit breaker tripped for {$component}, using fallback";
            error_log('MPAI: ' . $fallback_message);
            
            // Skip retry and go straight to fallback
            if ($fallback_callback && is_callable($fallback_callback)) {
                return call_user_func_array($fallback_callback, $fallback_args);
            }
            
            // No fallback available, return error with circuit breaker info
            $error->add_data(['circuit_breaker_tripped' => true]);
            return $error;
        }
        
        // Get recovery strategy for this error type
        $strategy = $this->get_recovery_strategy($error_type);
        
        // Try retry if enabled and callback provided
        if ($strategy['retry'] && $retry_callback && is_callable($retry_callback)) {
            $max_retries = $strategy['max_retries'];
            $retry_delay = isset($strategy['retry_delay']) ? $strategy['retry_delay'] : 0;
            
            for ($retry = 1; $retry <= $max_retries; $retry++) {
                // Log retry attempt
                error_log("MPAI: Retrying {$component} after error (attempt {$retry}/{$max_retries})");
                
                // Add delay if specified
                if ($retry_delay > 0) {
                    sleep($retry_delay);
                }
                
                // Attempt retry
                $result = call_user_func_array($retry_callback, $retry_args);
                
                // Check if retry was successful
                if (!is_wp_error($result)) {
                    // Success, reset error counter
                    $this->reset_error_counter($error_type, $component);
                    return $result;
                }
            }
        }
        
        // Retry failed or not available, try fallback if enabled and callback provided
        if (isset($strategy['fallback_available']) && $strategy['fallback_available'] && $fallback_callback && is_callable($fallback_callback)) {
            error_log("MPAI: Using fallback for {$component} after retry failure");
            return call_user_func_array($fallback_callback, $fallback_args);
        }
        
        // No recovery possible, return the original error
        return $error;
    }
    
    /**
     * Get recovery strategy for a specific error type
     *
     * @param string $type Error type
     * @return array Recovery strategy
     */
    public function get_recovery_strategy($type) {
        if (isset($this->recovery_strategies[$type])) {
            return $this->recovery_strategies[$type];
        }
        
        // Default strategy
        return [
            'retry' => false,
            'max_retries' => 0,
            'fallback_available' => false,
        ];
    }
    
    /**
     * Get fallback component for a specific component
     *
     * @param string $type Error type
     * @param string $component Component that failed
     * @return string|null Fallback component or null if none available
     */
    public function get_fallback($type, $component) {
        if (isset($this->fallbacks[$type]) && isset($this->fallbacks[$type][$component])) {
            return $this->fallbacks[$type][$component];
        }
        
        return null;
    }
    
    /**
     * Register a recovery strategy for an error type
     *
     * @param string $type Error type
     * @param array $strategy Recovery strategy
     * @return bool Success
     */
    public function register_recovery_strategy($type, $strategy) {
        $this->recovery_strategies[$type] = $strategy;
        return true;
    }
    
    /**
     * Register a fallback for a component
     *
     * @param string $type Error type
     * @param string $component Component name
     * @param string $fallback Fallback component
     * @return bool Success
     */
    public function register_fallback($type, $component, $fallback) {
        if (!isset($this->fallbacks[$type])) {
            $this->fallbacks[$type] = [];
        }
        
        $this->fallbacks[$type][$component] = $fallback;
        return true;
    }
    
    /**
     * Increment error counter for circuit breaking
     *
     * @param string $type Error type
     * @param string $component Component name
     */
    private function increment_error_counter($type, $component) {
        $key = "{$type}_{$component}";
        
        if (!isset($this->error_counters[$key])) {
            $this->error_counters[$key] = [
                'count' => 0,
                'first_error' => time(),
                'last_error' => time(),
            ];
        }
        
        $this->error_counters[$key]['count']++;
        $this->error_counters[$key]['last_error'] = time();
        
        // Check if we should trip the circuit breaker
        $this->check_circuit_breaker($type, $component);
    }
    
    /**
     * Reset error counter for a component
     *
     * @param string $type Error type
     * @param string $component Component name
     */
    private function reset_error_counter($type, $component) {
        $key = "{$type}_{$component}";
        
        if (isset($this->error_counters[$key])) {
            $this->error_counters[$key]['count'] = 0;
        }
        
        // Reset circuit breaker if tripped
        if ($this->is_circuit_breaker_tripped($type, $component)) {
            $this->reset_circuit_breaker($type, $component);
        }
    }
    
    /**
     * Check if circuit breaker should be tripped
     *
     * @param string $type Error type
     * @param string $component Component name
     */
    private function check_circuit_breaker($type, $component) {
        // Only check if we have a strategy with circuit breaker
        if (!isset($this->recovery_strategies[$type]) || !isset($this->recovery_strategies[$type]['circuit_breaker'])) {
            return;
        }
        
        $key = "{$type}_{$component}";
        $strategy = $this->recovery_strategies[$type];
        $threshold = $strategy['circuit_breaker']['threshold'];
        
        // Trip circuit breaker if error count exceeds threshold
        if (isset($this->error_counters[$key]) && $this->error_counters[$key]['count'] >= $threshold) {
            $this->trip_circuit_breaker($type, $component);
        }
    }
    
    /**
     * Trip a circuit breaker for a component
     *
     * @param string $type Error type
     * @param string $component Component name
     */
    private function trip_circuit_breaker($type, $component) {
        $key = "{$type}_{$component}";
        
        // Get reset time from strategy
        $reset_time = 600; // Default 10 minutes
        if (isset($this->recovery_strategies[$type]) && isset($this->recovery_strategies[$type]['circuit_breaker']['reset_time'])) {
            $reset_time = $this->recovery_strategies[$type]['circuit_breaker']['reset_time'];
        }
        
        $this->circuit_breakers[$key] = [
            'tripped' => true,
            'tripped_at' => time(),
            'reset_at' => time() + $reset_time,
        ];
        
        error_log("MPAI: Circuit breaker tripped for {$component} of type {$type}, will reset in {$reset_time} seconds");
    }
    
    /**
     * Check if a circuit breaker is tripped
     *
     * @param string $type Error type
     * @param string $component Component name
     * @return bool True if circuit breaker is tripped
     */
    public function is_circuit_breaker_tripped($type, $component) {
        $key = "{$type}_{$component}";
        
        if (!isset($this->circuit_breakers[$key])) {
            return false;
        }
        
        // Check if the circuit breaker has reset time
        if (time() >= $this->circuit_breakers[$key]['reset_at']) {
            $this->reset_circuit_breaker($type, $component);
            return false;
        }
        
        return $this->circuit_breakers[$key]['tripped'];
    }
    
    /**
     * Reset a circuit breaker
     *
     * @param string $type Error type
     * @param string $component Component name
     */
    private function reset_circuit_breaker($type, $component) {
        $key = "{$type}_{$component}";
        
        if (isset($this->circuit_breakers[$key])) {
            $this->circuit_breakers[$key]['tripped'] = false;
            error_log("MPAI: Circuit breaker reset for {$component} of type {$type}");
        }
    }
    
    /**
     * Format error for user display
     *
     * @param WP_Error $error The error to format
     * @param bool $include_debug Whether to include debug information
     * @return string Formatted error message
     */
    public function format_error_for_display($error, $include_debug = false) {
        if (!is_wp_error($error)) {
            return '';
        }
        
        $message = $error->get_error_message();
        $code = $error->get_error_code();
        $data = $error->get_error_data();
        
        // Basic error message
        $formatted = "Error: {$message}";
        
        // Add user-friendly explanations based on error type
        if (isset($data['type'])) {
            switch ($data['type']) {
                case self::ERROR_TYPE_API:
                    $formatted = "The AI service is currently unavailable. {$message}";
                    break;
                    
                case self::ERROR_TYPE_TOOL:
                    $formatted = "An error occurred while performing an operation. {$message}";
                    break;
                    
                case self::ERROR_TYPE_AGENT:
                    $formatted = "The AI assistant encountered an issue. {$message}";
                    break;
                    
                case self::ERROR_TYPE_PERMISSION:
                    $formatted = "You don't have permission to perform this action. {$message}";
                    break;
                    
                case self::ERROR_TYPE_VALIDATION:
                    $formatted = "Invalid input provided. {$message}";
                    break;
                    
                case self::ERROR_TYPE_TIMEOUT:
                    $formatted = "The operation timed out. Please try again. {$message}";
                    break;
                    
                case self::ERROR_TYPE_RESOURCE:
                    $formatted = "System resources are currently limited. {$message}";
                    break;
                    
                case self::ERROR_TYPE_NETWORK:
                    $formatted = "Network connection issue. {$message}";
                    break;
            }
        }
        
        // Add debug information if requested
        if ($include_debug && current_user_can('manage_options')) {
            $debug_info = [];
            $debug_info[] = "Error Code: {$code}";
            
            if (isset($data['error_id'])) {
                $debug_info[] = "Error ID: {$data['error_id']}";
            }
            
            if (isset($data['type'])) {
                $debug_info[] = "Type: {$data['type']}";
            }
            
            if (isset($data['severity'])) {
                $debug_info[] = "Severity: {$data['severity']}";
            }
            
            if (isset($data['timestamp'])) {
                $debug_info[] = "Time: {$data['timestamp']}";
            }
            
            if (isset($data['circuit_breaker_tripped']) && $data['circuit_breaker_tripped']) {
                $debug_info[] = "Circuit Breaker: Tripped";
            }
            
            $formatted .= "\n\nDebug Information:\n" . implode("\n", $debug_info);
        }
        
        return $formatted;
    }
    
    /**
     * Check if an error is recoverable
     *
     * @param WP_Error $error The error to check
     * @return bool True if error is recoverable
     */
    public function is_recoverable($error) {
        if (!is_wp_error($error)) {
            return false;
        }
        
        $data = $error->get_error_data();
        
        // Check severity
        if (isset($data['severity']) && $data['severity'] === self::SEVERITY_CRITICAL) {
            return false;
        }
        
        // Check if circuit breaker is tripped
        if (isset($data['circuit_breaker_tripped']) && $data['circuit_breaker_tripped']) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Create standardized API error
     *
     * @param string $api_name API name (openai, anthropic, etc)
     * @param string $code Error code
     * @param string $message Error message
     * @param array $context Additional context
     * @return WP_Error
     */
    public function create_api_error($api_name, $code, $message, $context = []) {
        // Add API-specific context
        $api_context = array_merge(
            [
                'api' => $api_name,
                'endpoint' => isset($context['endpoint']) ? $context['endpoint'] : 'unknown',
                'status_code' => isset($context['status_code']) ? $context['status_code'] : 0,
            ],
            $context
        );
        
        return $this->create_error(self::ERROR_TYPE_API, $code, $message, $api_context);
    }
    
    /**
     * Create standardized tool error
     *
     * @param string $tool_name Tool name
     * @param string $code Error code
     * @param string $message Error message
     * @param array $context Additional context
     * @return WP_Error
     */
    public function create_tool_error($tool_name, $code, $message, $context = []) {
        // Add tool-specific context
        $tool_context = array_merge(
            [
                'tool' => $tool_name,
                'arguments' => isset($context['arguments']) ? $context['arguments'] : [],
            ],
            $context
        );
        
        return $this->create_error(self::ERROR_TYPE_TOOL, $code, $message, $tool_context);
    }
    
    /**
     * Create standardized agent error
     *
     * @param string $agent_name Agent name
     * @param string $code Error code
     * @param string $message Error message
     * @param array $context Additional context
     * @return WP_Error
     */
    public function create_agent_error($agent_name, $code, $message, $context = []) {
        // Add agent-specific context
        $agent_context = array_merge(
            [
                'agent' => $agent_name,
                'user_id' => isset($context['user_id']) ? $context['user_id'] : get_current_user_id(),
            ],
            $context
        );
        
        return $this->create_error(self::ERROR_TYPE_AGENT, $code, $message, $agent_context);
    }
}

/**
 * Initialize the error recovery system
 *
 * @return MPAI_Error_Recovery
 */
function mpai_init_error_recovery() {
    return MPAI_Error_Recovery::get_instance();
}