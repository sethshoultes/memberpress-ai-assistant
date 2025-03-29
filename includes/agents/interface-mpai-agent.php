<?php
/**
 * Agent Interface
 *
 * Defines the interface that all MemberPress AI agents must implement.
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Interface for all MemberPress AI agents
 */
interface MPAI_Agent {
    /**
     * Process a user request
     *
     * @param array $intent_data Intent data from orchestrator
     * @param array $context User context
     * @return array Response data
     */
    public function process_request($intent_data, $context = []);
    
    /**
     * Get agent name
     *
     * @return string Agent name
     */
    public function get_name();
    
    /**
     * Get agent description
     *
     * @return string Agent description
     */
    public function get_description();
    
    /**
     * Get agent capabilities
     *
     * @return array Agent capabilities
     */
    public function get_capabilities();
}