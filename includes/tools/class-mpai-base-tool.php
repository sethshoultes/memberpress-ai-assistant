<?php
/**
 * Base abstract class for all tools
 *
 * @package MemberPress AI Assistant
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base abstract class for all tools
 */
abstract class MPAI_Base_Tool {
	/**
	 * Tool name
	 * @var string
	 */
	protected $name;
	
	/**
	 * Tool description
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
	 * Execute the tool with parameters
	 *
	 * @param array $parameters Parameters for the tool
	 * @return mixed Tool result
	 */
	abstract public function execute( $parameters );
}
