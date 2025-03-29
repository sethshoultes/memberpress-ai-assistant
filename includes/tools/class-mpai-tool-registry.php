<?php
/**
 * Tool Registry Class
 *
 * Manages the registration and retrieval of tools that agents can use.
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Tool Registry for MemberPress AI Assistant
 */
class MPAI_Tool_Registry {
    /**
     * Registered tools
     *
     * @var array
     */
    private $tools = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->register_core_tools();
    }
    
    /**
     * Register a tool
     *
     * @param string $tool_id Tool identifier
     * @param MPAI_Base_Tool $tool Tool instance
     * @return bool Success status
     */
    public function register_tool($tool_id, $tool) {
        if (isset($this->tools[$tool_id])) {
            error_log("MPAI: Tool with ID {$tool_id} already registered");
            return false;
        }
        
        $this->tools[$tool_id] = $tool;
        return true;
    }
    
    /**
     * Get a tool by ID
     *
     * @param string $tool_id Tool identifier
     * @return MPAI_Base_Tool|null Tool instance or null if not found
     */
    public function get_tool($tool_id) {
        return isset($this->tools[$tool_id]) ? $this->tools[$tool_id] : null;
    }
    
    /**
     * Get all available tools
     *
     * @return array All registered tools
     */
    public function get_available_tools() {
        return $this->tools;
    }
    
    /**
     * Register all core tools
     */
    private function register_core_tools() {
        // Register OpenAI tool
        if (class_exists('MPAI_OpenAI_Tool')) {
            $openai_tool = new MPAI_OpenAI_Tool();
            $this->register_tool('openai', $openai_tool);
        }
        
        // Register WordPress tool if class exists
        if (class_exists('MPAI_WordPress_Tool')) {
            $wp_tool = new MPAI_WordPress_Tool();
            $this->register_tool('wordpress', $wp_tool);
        }
        
        // Register WP-CLI tool if class exists and CLI commands are enabled
        if (class_exists('MPAI_WP_CLI_Tool') && get_option('mpai_enable_cli_commands', false)) {
            $wpcli_tool = new MPAI_WP_CLI_Tool();
            $this->register_tool('wpcli', $wpcli_tool);
        }
        
        // Register MemberPress tool if class exists and MemberPress is active
        if (class_exists('MPAI_MemberPress_Tool') && class_exists('MeprAppCtrl')) {
            $memberpress_tool = new MPAI_MemberPress_Tool();
            $this->register_tool('memberpress', $memberpress_tool);
        }
        
        // Register File System tool if class exists
        if (class_exists('MPAI_FileSystem_Tool')) {
            $fs_tool = new MPAI_FileSystem_Tool();
            $this->register_tool('filesystem', $fs_tool);
        }
        
        // Allow plugins to register additional tools
        do_action('mpai_register_tools', $this);
    }
}