<?php
/**
 * Tool Registry Class
 *
 * Manages registration and retrieval of tools
 *
 * @package MemberPress AI Assistant
 * @subpackage MemberPress_AI_Assistant/includes/tools
 */

// Prevent direct access.
if (!defined('ABSPATH')) {
    die;
}

/**
 * Tool Registry Class
 *
 * Manages registration and retrieval of tools
 */
class MPAI_Tool_Registry {
    /**
     * Registered tools
     *
     * @var array
     */
    private $tools = array();

    /**
     * Tool definitions
     *
     * @var array
     */
    private $tool_definitions = array();

    /**
     * Instance of this class (singleton)
     *
     * @var MPAI_Tool_Registry
     */
    private static $instance = null;

    /**
     * Constructor
     */
    public function __construct() {
        // Check if MPAI_Hooks class exists before using it
        if (class_exists('MPAI_Hooks')) {
            // Register the tool registry initialization action
            MPAI_Hooks::register_hook(
                'MPAI_HOOK_ACTION_tool_registry_init',
                'Action after tool registry initialization',
                [],
                '1.7.0',
                'tools'
            );
        }
        
        // Register core tools
        $this->register_core_tools();
        
        // Fire the action regardless of whether the hook was registered
        do_action('MPAI_HOOK_ACTION_tool_registry_init');
        
        // Make this instance globally available
        global $mpai_tool_registry;
        $mpai_tool_registry = $this;
    }

    /**
     * Get instance (singleton pattern)
     *
     * @return MPAI_Tool_Registry
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Register core tools
     */
    public function register_core_tools() {
        // This method will be called to register built-in tools
        // It can be extended in the future
        mpai_log_debug('Registering core tools', 'tool-registry');
    }

    /**
     * Register a tool definition
     *
     * @param string $tool_id Tool identifier
     * @param string $class_name Tool class name
     * @param string $file_path Optional file path to include
     * @return bool Success status
     */
    public function register_tool_definition($tool_id, $class_name, $file_path = '') {
        if (isset($this->tool_definitions[$tool_id])) {
            mpai_log_warning("Tool definition '{$tool_id}' already registered", 'tool-registry');
            return false;
        }

        $this->tool_definitions[$tool_id] = array(
            'class' => $class_name,
            'file' => $file_path
        );

        mpai_log_debug("Registered tool definition: {$tool_id}", 'tool-registry');
        return true;
    }

    /**
     * Register a tool instance
     *
     * @param string $tool_id Tool identifier
     * @param object $tool_instance Tool instance
     * @return bool Success status
     */
    public function register_tool($tool_id, $tool_instance) {
        // Check if MPAI_Hooks class exists before using it
        if (class_exists('MPAI_Hooks')) {
            // Register the tool registration action
            MPAI_Hooks::register_hook(
                'MPAI_HOOK_ACTION_register_tool',
                'Action when a tool is registered to the system',
                [
                    'tool_id' => ['type' => 'string', 'description' => 'The tool identifier'],
                    'tool_instance' => ['type' => 'object', 'description' => 'The tool instance']
                ],
                '1.7.0',
                'tools'
            );
        }
        
        // Fire the action regardless of whether the hook was registered
        do_action('MPAI_HOOK_ACTION_register_tool', $tool_id, $tool_instance);
        
        if (isset($this->tools[$tool_id])) {
            mpai_log_warning("Tool '{$tool_id}' already registered, replacing", 'tool-registry');
        }

        $this->tools[$tool_id] = $tool_instance;
        mpai_log_debug("Registered tool instance: {$tool_id}", 'tool-registry');
        
        return true;
    }

    /**
     * Get a tool instance
     *
     * @param string $tool_id Tool identifier
     * @return object|null Tool instance or null if not found
     */
    public function get_tool($tool_id) {
        // Check if tool is already instantiated
        if (isset($this->tools[$tool_id])) {
            return $this->tools[$tool_id];
        }

        // Check if we have a definition for this tool
        if (isset($this->tool_definitions[$tool_id])) {
            $definition = $this->tool_definitions[$tool_id];
            
            // Include the file if provided
            if (!empty($definition['file']) && file_exists($definition['file'])) {
                require_once $definition['file'];
            }
            
            // Check if the class exists
            if (class_exists($definition['class'])) {
                try {
                    // Create a new instance
                    $tool = new $definition['class']();
                    
                    // Register the tool
                    $this->register_tool($tool_id, $tool);
                    
                    return $tool;
                } catch (Exception $e) {
                    mpai_log_error("Error creating tool '{$tool_id}': " . $e->getMessage(), 'tool-registry');
                }
            } else {
                mpai_log_error("Tool class '{$definition['class']}' not found", 'tool-registry');
            }
        }

        return null;
    }

    /**
     * Get all available tools
     *
     * @return array Registered tools
     */
    public function get_available_tools() {
        // Check if MPAI_Hooks class exists before using it
        if (class_exists('MPAI_Hooks')) {
            // Register the available tools filter
            MPAI_Hooks::register_filter(
                'MPAI_HOOK_FILTER_available_tools',
                'Filter the list of available tools',
                $this->tools,
                ['tools' => ['type' => 'array', 'description' => 'Array of available tool instances']],
                '1.7.0',
                'tools'
            );
        }
        
        // Apply the filter regardless of whether the hook was registered
        $tools = apply_filters('MPAI_HOOK_FILTER_available_tools', $this->tools);
        
        return $tools;
    }

    /**
     * Check if a user has capability to use a specific tool
     *
     * @param string $tool_id Tool identifier
     * @param int $user_id User ID
     * @return bool Whether user has capability
     */
    public function check_tool_capability($tool_id, $user_id = 0) {
        if ($user_id === 0) {
            $user_id = get_current_user_id();
        }
        
        // Default to true for admin users
        $has_capability = current_user_can('manage_options');
        
        // Check if MPAI_Hooks class exists before using it
        if (class_exists('MPAI_Hooks')) {
            // Register the tool capability check filter
            MPAI_Hooks::register_filter(
                'MPAI_HOOK_FILTER_tool_capability_check',
                'Filter whether a user has capability to use a specific tool',
                $has_capability,
                [
                    'tool_id' => ['type' => 'string', 'description' => 'The tool identifier'],
                    'user_id' => ['type' => 'integer', 'description' => 'The user ID']
                ],
                '1.7.0',
                'tools'
            );
        }
        
        // Apply the filter regardless of whether the hook was registered
        $has_capability = apply_filters('MPAI_HOOK_FILTER_tool_capability_check', $has_capability, $tool_id, $user_id);
        
        return $has_capability;
    }
}
