<?php
/**
 * Interface that all agents must implement
 *
 * @package MemberPress AI Assistant
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface that all agents must implement
 */
interface MPAI_Agent {
	/**
	 * Process a user request
	 *
	 * @param array $intent_data Intent data from orchestrator
	 * @param array $context User context
	 * @return array Response data
	 */
	public function process_request( $intent_data, $context = [] );
	
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
	 * @return array List of capabilities
	 */
	public function get_capabilities();
}
