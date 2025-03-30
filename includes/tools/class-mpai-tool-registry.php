<?php
/**
 * Registry for all available tools
 *
 * @package MemberPress AI Assistant
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registry for all available tools
 */
class MPAI_Tool_Registry {
	/**
	 * Registered tools
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
	 * Register a new tool
	 *
	 * @param string $tool_id Unique tool identifier
	 * @param object $tool Tool instance
	 * @return bool Success status
	 */
	public function register_tool( $tool_id, $tool ) {
		if ( isset( $this->tools[$tool_id] ) ) {
			return false;
		}
		
		$this->tools[$tool_id] = $tool;
		return true;
	}
	
	/**
	 * Get a tool by ID
	 *
	 * @param string $tool_id Tool identifier
	 * @return object|null Tool instance or null if not found
	 */
	public function get_tool( $tool_id ) {
		return isset( $this->tools[$tool_id] ) ? $this->tools[$tool_id] : null;
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
		// Register WP-CLI tool
		$wp_cli_tool = $this->get_wp_cli_tool_instance();
		if ( $wp_cli_tool ) {
			$this->register_tool( 'wpcli', $wp_cli_tool );
		}
		
		// Register WordPress API tool
		$wp_api_tool = $this->get_wp_api_tool_instance();
		if ( $wp_api_tool ) {
			$this->register_tool( 'wp_api', $wp_api_tool );
		}
		
		// Register diagnostic tool
		$diagnostic_tool = $this->get_diagnostic_tool_instance();
		if ( $diagnostic_tool ) {
			$this->register_tool( 'diagnostic', $diagnostic_tool );
		}
		
		// Register other tools as needed...
		// Database tool, Content tool, etc.
	}
	
	/**
	 * Get WordPress API tool instance
	 * 
	 * @return object|null Tool instance
	 */
	private function get_wp_api_tool_instance() {
		// Check if the WordPress API tool class exists
		if ( ! class_exists( 'MPAI_WP_API_Tool' ) ) {
			$tool_path = plugin_dir_path( __FILE__ ) . 'implementations/class-mpai-wp-api-tool.php';
			if ( file_exists( $tool_path ) ) {
				require_once $tool_path;
				if ( class_exists( 'MPAI_WP_API_Tool' ) ) {
					return new MPAI_WP_API_Tool();
				}
			}
			return null;
		}
		
		return new MPAI_WP_API_Tool();
	}
	
	/**
	 * Get WP-CLI tool instance
	 *
	 * @return object|null Tool instance or null if WP-CLI not available
	 */
	private function get_wp_cli_tool_instance() {
		// Check if the WP-CLI tool class exists
		if ( ! class_exists( 'MPAI_WP_CLI_Tool' ) ) {
			$tool_path = plugin_dir_path( __FILE__ ) . 'implementations/class-mpai-wpcli-tool.php';
			if ( file_exists( $tool_path ) ) {
				require_once $tool_path;
				if ( class_exists( 'MPAI_WP_CLI_Tool' ) ) {
					return new MPAI_WP_CLI_Tool();
				}
			}
			return null;
		}
		
		return new MPAI_WP_CLI_Tool();
	}
	
	/**
	 * Get Diagnostic tool instance
	 *
	 * @return object|null Tool instance
	 */
	private function get_diagnostic_tool_instance() {
		// Check if the Diagnostic tool class exists
		if ( ! class_exists( 'MPAI_Diagnostic_Tool' ) ) {
			$tool_path = plugin_dir_path( __FILE__ ) . 'implementations/class-mpai-diagnostic-tool.php';
			if ( file_exists( $tool_path ) ) {
				require_once $tool_path;
				if ( class_exists( 'MPAI_Diagnostic_Tool' ) ) {
					return new MPAI_Diagnostic_Tool();
				}
			}
			return null;
		}
		
		return new MPAI_Diagnostic_Tool();
	}
}
