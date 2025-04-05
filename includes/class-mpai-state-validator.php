<?php
/**
 * MemberPress AI Assistant - State Validation System
 *
 * Provides state validation and consistent system state monitoring
 * to prevent corruption and ensure reliability.
 *
 * @package MemberPress AI Assistant
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * State Validation System
 *
 * Ensures system state consistency through validation and monitoring.
 */
class MPAI_State_Validator {
    /**
     * Instance of this class
     *
     * @var MPAI_State_Validator
     */
    private static $instance = null;
    
    /**
     * Logger instance
     *
     * @var MPAI_Plugin_Logger
     */
    private $logger = null;
    
    /**
     * Error recovery system
     *
     * @var MPAI_Error_Recovery
     */
    private $error_recovery = null;
    
    /**
     * Invariant assertions
     *
     * @var array
     */
    private $invariants = [];
    
    /**
     * Component state monitoring
     *
     * @var array
     */
    private $component_state = [];
    
    /**
     * State validation rules by component
     *
     * @var array
     */
    private $validation_rules = [];
    
    /**
     * Pre-conditions for operations
     *
     * @var array
     */
    private $preconditions = [];
    
    /**
     * Post-conditions for operations
     *
     * @var array
     */
    private $postconditions = [];
    
    /**
     * Get instance of this class
     *
     * @return MPAI_State_Validator
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
        
        // Initialize error recovery
        if (class_exists('MPAI_Error_Recovery')) {
            $this->error_recovery = mpai_init_error_recovery();
        }
        
        // Register default invariants
        $this->register_default_invariants();
        
        // Register default validation rules
        $this->register_default_validation_rules();
    }
    
    /**
     * Register default invariants
     */
    private function register_default_invariants() {
        // Basic system invariants
        $this->invariants['system'] = [
            'plugin_dir_exists' => function() {
                return defined('MPAI_PLUGIN_DIR') && file_exists(MPAI_PLUGIN_DIR);
            },
            'plugin_url_defined' => function() {
                return defined('MPAI_PLUGIN_URL');
            },
            'wp_includes_exists' => function() {
                return defined('WPINC') && file_exists(ABSPATH . WPINC);
            }
        ];
        
        // API system invariants
        $this->invariants['api'] = [
            'api_router_singleton' => function() {
                if (!class_exists('MPAI_API_Router')) {
                    return true; // Not applicable if class doesn't exist
                }
                
                // Check if get_instance method exists and returns same instance
                if (method_exists('MPAI_API_Router', 'get_instance')) {
                    $instance1 = MPAI_API_Router::get_instance();
                    $instance2 = MPAI_API_Router::get_instance();
                    return $instance1 === $instance2;
                }
                
                return true;
            },
            'primary_api_setting_valid' => function() {
                $primary_api = get_option('mpai_primary_api', 'openai');
                return in_array($primary_api, ['openai', 'anthropic']);
            }
        ];
        
        // Agent system invariants
        $this->invariants['agents'] = [
            'orchestrator_singleton' => function() {
                if (!class_exists('MPAI_Agent_Orchestrator')) {
                    return true; // Not applicable if class doesn't exist
                }
                
                // Check if get_instance method exists and returns same instance
                if (method_exists('MPAI_Agent_Orchestrator', 'get_instance')) {
                    $instance1 = MPAI_Agent_Orchestrator::get_instance();
                    $instance2 = MPAI_Agent_Orchestrator::get_instance();
                    return $instance1 === $instance2;
                }
                
                return true;
            }
        ];
        
        // Tool system invariants
        $this->invariants['tools'] = [
            'tool_registry_singleton' => function() {
                if (!class_exists('MPAI_Tool_Registry')) {
                    return true; // Not applicable if class doesn't exist
                }
                
                // Check if get_instance method exists and returns same instance
                if (method_exists('MPAI_Tool_Registry', 'get_instance')) {
                    $instance1 = MPAI_Tool_Registry::get_instance();
                    $instance2 = MPAI_Tool_Registry::get_instance();
                    return $instance1 === $instance2;
                }
                
                return true;
            }
        ];
    }
    
    /**
     * Register default validation rules
     */
    private function register_default_validation_rules() {
        // API client validation rules
        $this->validation_rules['api_client'] = [
            'api_key_not_empty' => function($api_client) {
                if (method_exists($api_client, 'get_api_key')) {
                    $api_key = $api_client->get_api_key();
                    return !empty($api_key);
                }
                return true;
            },
            'model_is_valid' => function($api_client) {
                if (method_exists($api_client, 'get_model') && method_exists($api_client, 'get_available_models')) {
                    $model = $api_client->get_model();
                    $available_models = $api_client->get_available_models();
                    return in_array($model, $available_models);
                }
                return true;
            }
        ];
        
        // API router validation rules
        $this->validation_rules['api_router'] = [
            'primary_api_exists' => function($api_router) {
                if (method_exists($api_router, 'get_primary_api')) {
                    $primary_api = $api_router->get_primary_api();
                    return !empty($primary_api);
                }
                return true;
            },
            'fallback_api_exists' => function($api_router) {
                if (method_exists($api_router, 'get_fallback_api')) {
                    $fallback_api = $api_router->get_fallback_api();
                    return $fallback_api !== null;
                }
                return true;
            }
        ];
        
        // Agent orchestrator validation rules
        $this->validation_rules['agent_orchestrator'] = [
            'agent_count_valid' => function($orchestrator) {
                if (method_exists($orchestrator, 'get_available_agents')) {
                    $agents = $orchestrator->get_available_agents();
                    return is_array($agents) && count($agents) > 0;
                }
                return true;
            },
            'agent_registry_valid' => function($orchestrator) {
                if (property_exists($orchestrator, 'agents')) {
                    $agents = $orchestrator->agents;
                    return is_array($agents);
                }
                return true;
            }
        ];
        
        // Tool registry validation rules
        $this->validation_rules['tool_registry'] = [
            'tool_count_valid' => function($registry) {
                if (method_exists($registry, 'get_available_tools')) {
                    $tools = $registry->get_available_tools();
                    return is_array($tools) && count($tools) > 0;
                }
                return true;
            }
        ];
    }
    
    /**
     * Verify system invariants
     *
     * @param string $component Component to check, or null for all components
     * @return bool True if all invariants are satisfied
     */
    public function verify_invariants($component = null) {
        $failed_invariants = [];
        
        // Check all invariants if component not specified
        if ($component === null) {
            foreach ($this->invariants as $comp => $rules) {
                foreach ($rules as $name => $validator) {
                    if (!$validator()) {
                        $failed_invariants[] = "{$comp}: {$name}";
                    }
                }
            }
        } else if (isset($this->invariants[$component])) {
            // Check only specified component
            foreach ($this->invariants[$component] as $name => $validator) {
                if (!$validator()) {
                    $failed_invariants[] = "{$component}: {$name}";
                }
            }
        }
        
        // Log any failed invariants
        if (!empty($failed_invariants)) {
            error_log('MPAI: System invariants violated: ' . implode(', ', $failed_invariants));
            
            // Log to plugin logger if available
            if ($this->logger) {
                $this->logger->insert_log(
                    'mpai',
                    'MPAI Assistant',
                    '',
                    '',
                    'error',
                    [
                        'message' => 'System invariants violated',
                        'failed_invariants' => $failed_invariants
                    ]
                );
            }
            
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate component state
     *
     * @param string $component_type Component type
     * @param object $component Component instance
     * @return bool|WP_Error True if valid or WP_Error
     */
    public function validate_component($component_type, $component) {
        if (!isset($this->validation_rules[$component_type])) {
            // No validation rules for this component type
            return true;
        }
        
        $failed_rules = [];
        
        // Apply validation rules
        foreach ($this->validation_rules[$component_type] as $rule_name => $validator) {
            if (!$validator($component)) {
                $failed_rules[] = $rule_name;
            }
        }
        
        // Check if any rules failed
        if (!empty($failed_rules)) {
            $message = "Component {$component_type} failed validation: " . implode(', ', $failed_rules);
            error_log('MPAI: ' . $message);
            
            // Create error through error recovery if available
            if ($this->error_recovery) {
                return $this->error_recovery->create_error(
                    MPAI_Error_Recovery::ERROR_TYPE_VALIDATION,
                    'component_validation_failed',
                    $message,
                    [
                        'component_type' => $component_type,
                        'failed_rules' => $failed_rules
                    ]
                );
            }
            
            // Fallback to WP_Error if error recovery not available
            return new WP_Error('component_validation_failed', $message);
        }
        
        return true;
    }
    
    /**
     * Register an invariant assertion
     *
     * @param string $component Component name
     * @param string $name Invariant name
     * @param callable $validator Validation function
     * @return bool Success
     */
    public function register_invariant($component, $name, $validator) {
        if (!isset($this->invariants[$component])) {
            $this->invariants[$component] = [];
        }
        
        $this->invariants[$component][$name] = $validator;
        return true;
    }
    
    /**
     * Register a validation rule
     *
     * @param string $component_type Component type
     * @param string $rule_name Rule name
     * @param callable $validator Validation function
     * @return bool Success
     */
    public function register_validation_rule($component_type, $rule_name, $validator) {
        if (!isset($this->validation_rules[$component_type])) {
            $this->validation_rules[$component_type] = [];
        }
        
        $this->validation_rules[$component_type][$rule_name] = $validator;
        return true;
    }
    
    /**
     * Register a pre-condition for an operation
     *
     * @param string $component Component name
     * @param string $operation Operation name
     * @param callable $validator Validation function
     * @return bool Success
     */
    public function register_precondition($component, $operation, $validator) {
        $key = "{$component}_{$operation}";
        
        if (!isset($this->preconditions[$key])) {
            $this->preconditions[$key] = [];
        }
        
        $this->preconditions[$key][] = $validator;
        return true;
    }
    
    /**
     * Register a post-condition for an operation
     *
     * @param string $component Component name
     * @param string $operation Operation name
     * @param callable $validator Validation function
     * @return bool Success
     */
    public function register_postcondition($component, $operation, $validator) {
        $key = "{$component}_{$operation}";
        
        if (!isset($this->postconditions[$key])) {
            $this->postconditions[$key] = [];
        }
        
        $this->postconditions[$key][] = $validator;
        return true;
    }
    
    /**
     * Check pre-conditions for an operation
     *
     * @param string $component Component name
     * @param string $operation Operation name
     * @param array $args Operation arguments
     * @return bool|WP_Error True if pre-conditions satisfied or WP_Error
     */
    public function check_preconditions($component, $operation, $args = []) {
        $key = "{$component}_{$operation}";
        
        if (!isset($this->preconditions[$key]) || empty($this->preconditions[$key])) {
            // No pre-conditions to check
            return true;
        }
        
        $failed_conditions = [];
        
        // Check each pre-condition
        foreach ($this->preconditions[$key] as $index => $validator) {
            if (!$validator($args)) {
                $failed_conditions[] = "pre-condition {$index}";
            }
        }
        
        // Check if any pre-conditions failed
        if (!empty($failed_conditions)) {
            $message = "Pre-conditions not met for {$component}.{$operation}: " . implode(', ', $failed_conditions);
            error_log('MPAI: ' . $message);
            
            // Create error through error recovery if available
            if ($this->error_recovery) {
                return $this->error_recovery->create_error(
                    MPAI_Error_Recovery::ERROR_TYPE_VALIDATION,
                    'preconditions_failed',
                    $message,
                    [
                        'component' => $component,
                        'operation' => $operation,
                        'failed_conditions' => $failed_conditions
                    ]
                );
            }
            
            // Fallback to WP_Error if error recovery not available
            return new WP_Error('preconditions_failed', $message);
        }
        
        return true;
    }
    
    /**
     * Check post-conditions after an operation
     *
     * @param string $component Component name
     * @param string $operation Operation name
     * @param mixed $result Operation result
     * @param array $args Operation arguments
     * @return bool|WP_Error True if post-conditions satisfied or WP_Error
     */
    public function check_postconditions($component, $operation, $result, $args = []) {
        $key = "{$component}_{$operation}";
        
        if (!isset($this->postconditions[$key]) || empty($this->postconditions[$key])) {
            // No post-conditions to check
            return true;
        }
        
        $failed_conditions = [];
        
        // Check each post-condition
        foreach ($this->postconditions[$key] as $index => $validator) {
            if (!$validator($result, $args)) {
                $failed_conditions[] = "post-condition {$index}";
            }
        }
        
        // Check if any post-conditions failed
        if (!empty($failed_conditions)) {
            $message = "Post-conditions not met for {$component}.{$operation}: " . implode(', ', $failed_conditions);
            error_log('MPAI: ' . $message);
            
            // Create error through error recovery if available
            if ($this->error_recovery) {
                return $this->error_recovery->create_error(
                    MPAI_Error_Recovery::ERROR_TYPE_VALIDATION,
                    'postconditions_failed',
                    $message,
                    [
                        'component' => $component,
                        'operation' => $operation,
                        'failed_conditions' => $failed_conditions
                    ]
                );
            }
            
            // Fallback to WP_Error if error recovery not available
            return new WP_Error('postconditions_failed', $message);
        }
        
        return true;
    }
    
    /**
     * Monitor component state
     *
     * @param string $component Component name
     * @param array $state Current state
     * @return bool True if state is consistent
     */
    public function monitor_component_state($component, $state) {
        // Store previous state
        $previous_state = isset($this->component_state[$component]) ? $this->component_state[$component] : null;
        
        // Update stored state
        $this->component_state[$component] = $state;
        
        // No previous state to compare
        if ($previous_state === null) {
            return true;
        }
        
        // Check for inconsistencies
        $inconsistencies = [];
        
        // Compare basic properties that should never change
        $immutable_properties = [
            // Add component-specific immutable properties here
        ];
        
        // Component-specific immutable properties
        switch ($component) {
            case 'agent_orchestrator':
                $immutable_properties = ['instance_id', 'version'];
                break;
                
            case 'api_router':
                $immutable_properties = ['instance_id'];
                break;
                
            case 'tool_registry':
                $immutable_properties = ['instance_id'];
                break;
        }
        
        // Check immutable properties
        foreach ($immutable_properties as $property) {
            if (isset($previous_state[$property]) && isset($state[$property]) && $previous_state[$property] !== $state[$property]) {
                $inconsistencies[] = "Immutable property {$property} changed from {$previous_state[$property]} to {$state[$property]}";
            }
        }
        
        // Log inconsistencies if any found
        if (!empty($inconsistencies)) {
            error_log('MPAI: State inconsistencies detected for ' . $component . ': ' . implode(', ', $inconsistencies));
            
            // Log to plugin logger if available
            if ($this->logger) {
                $this->logger->insert_log(
                    'mpai',
                    'MPAI Assistant',
                    '',
                    '',
                    'error',
                    [
                        'message' => 'State inconsistencies detected',
                        'component' => $component,
                        'inconsistencies' => $inconsistencies
                    ]
                );
            }
            
            return false;
        }
        
        return true;
    }
    
    /**
     * Get current component state
     *
     * @param string $component Component name
     * @return array|null Current state or null if not monitored
     */
    public function get_component_state($component) {
        return isset($this->component_state[$component]) ? $this->component_state[$component] : null;
    }
    
    /**
     * Verify operation with pre and post conditions
     *
     * @param string $component Component name
     * @param string $operation Operation name
     * @param callable $operation_callback The operation to execute
     * @param array $args Operation arguments
     * @return mixed|WP_Error Operation result or error
     */
    public function verify_operation($component, $operation, $operation_callback, $args = []) {
        // Check pre-conditions
        $precondition_result = $this->check_preconditions($component, $operation, $args);
        if (is_wp_error($precondition_result)) {
            return $precondition_result;
        }
        
        // Execute the operation
        $result = call_user_func_array($operation_callback, $args);
        
        // Check post-conditions
        $postcondition_result = $this->check_postconditions($component, $operation, $result, $args);
        if (is_wp_error($postcondition_result)) {
            return $postcondition_result;
        }
        
        return $result;
    }
    
    /**
     * Assert that a condition is true, or log an error
     *
     * @param bool $condition The condition to check
     * @param string $message Error message if condition is false
     * @param array $context Additional context for error
     * @return bool True if assertion passed, false otherwise
     */
    public function assert($condition, $message, $context = []) {
        if (!$condition) {
            error_log('MPAI: Assertion failed: ' . $message);
            
            // Log to plugin logger if available
            if ($this->logger) {
                $this->logger->insert_log(
                    'mpai',
                    'MPAI Assistant',
                    '',
                    '',
                    'error',
                    [
                        'message' => 'Assertion failed: ' . $message,
                        'context' => $context
                    ]
                );
            }
            
            return false;
        }
        
        return true;
    }
}

/**
 * Initialize the state validator
 *
 * @return MPAI_State_Validator
 */
function mpai_init_state_validator() {
    return MPAI_State_Validator::get_instance();
}