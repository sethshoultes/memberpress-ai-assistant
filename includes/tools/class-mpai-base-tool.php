<?php
/**
 * Base Tool Class
 *
 * Abstract class that all tools must extend.
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Abstract base class for all MemberPress AI tools
 */
abstract class MPAI_Base_Tool {
    /**
     * Tool name
     *
     * @var string
     */
    protected $name;
    
    /**
     * Tool description
     *
     * @var string
     */
    protected $description;
    
    /**
     * Get tool name
     *
     * @return string Tool name
     */
    public function get_name() {
        return $this->name;
    }
    
    /**
     * Get tool description
     *
     * @return string Tool description
     */
    public function get_description() {
        return $this->description;
    }
    
    /**
     * Execute the tool
     *
     * @param array $parameters Tool parameters
     * @return mixed Execution result
     */
    abstract public function execute($parameters);
    
    /**
     * Get parameters schema in JSON Schema format
     *
     * @return array Schema for the tool parameters
     */
    abstract public function get_parameters_schema();
}