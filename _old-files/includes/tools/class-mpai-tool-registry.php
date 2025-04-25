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
	 * Tool definitions for lazy loading
	 * @var array
	 */
	private $tool_definitions = [];
	
	/**
	 * Loaded tools tracking
	 * @var array
	 */
	private $loaded_tools = [];
	
	/**
	 * Constructor
	 */
	public function __construct() {
		// Fire action after tool registry initialization
		do_action('MPAI_HOOK_ACTION_tool_registry_init', $this);
		
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
		
		// Fire action when a tool is registered
		do_action('MPAI_HOOK_ACTION_register_tool', $tool_id, $tool, $this);
		
		$this->tools[$tool_id] = $tool;
		$this->loaded_tools[$tool_id] = true;
		return true;
	}
	
	/**
	 * Register a tool definition for lazy loading
	 *
	 * @param string $tool_id Unique tool identifier
	 * @param string $class_name Tool class name
	 * @param string $file_path Optional file path to include if class isn't loaded
	 * @return bool Success status
	 */
	public function register_tool_definition( $tool_id, $class_name, $file_path = null ) {
		if ( isset( $this->tools[$tool_id] ) || isset( $this->tool_definitions[$tool_id] ) ) {
			return false;
		}
		
		$this->tool_definitions[$tool_id] = [
			'class' => $class_name,
			'file' => $file_path
		];
		
		return true;
	}
	
	/**
	 * Get a tool by ID
	 *
	 * @param string $tool_id Tool identifier
	 * @return object|null Tool instance or null if not found
	 */
	public function get_tool( $tool_id ) {
		// Return already loaded tool if available
		if ( isset( $this->tools[$tool_id] ) ) {
			return $this->tools[$tool_id];
		}
		
		// Check if tool definition exists
		if ( !isset( $this->tool_definitions[$tool_id] ) ) {
			return null;
		}
		
		// Load the tool file if provided
		$definition = $this->tool_definitions[$tool_id];
		if ( !empty( $definition['file'] ) && file_exists( $definition['file'] ) ) {
			require_once $definition['file'];
		}
		
		// Check if class exists
		if ( !class_exists( $definition['class'] ) ) {
			return null;
		}
		
		// Create instance and store
		$tool = new $definition['class']();
		$this->tools[$tool_id] = $tool;
		$this->loaded_tools[$tool_id] = true;
		
		return $tool;
	}
	
	/**
	 * Get all available tools
	 *
	 * @return array All registered tools
	 */
	public function get_available_tools() {
		// Return combination of loaded tools and definitions
		$tools = [];
		
		// Add loaded tools
		foreach ( $this->tools as $tool_id => $tool ) {
			$tools[$tool_id] = $tool;
		}
		
		// Add tool definitions for tools not yet loaded
		foreach ( $this->tool_definitions as $tool_id => $definition ) {
			if ( !isset( $this->tools[$tool_id] ) ) {
				// Create placeholder with basic info
				$tools[$tool_id] = [
					'id' => $tool_id,
					'class' => $definition['class'],
					'loaded' => false,
					'status' => 'not_loaded'
				];
			}
		}
		
		// Filter the list of available tools
		return apply_filters('MPAI_HOOK_FILTER_available_tools', $tools, $this);
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
		
		// Register plugin logs tool
		$plugin_logs_tool = $this->get_plugin_logs_tool_instance();
		if ( $plugin_logs_tool ) {
			$this->register_tool( 'plugin_logs', $plugin_logs_tool );
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
	
	/**
	 * Get Plugin Logs tool instance
	 *
	 * @return object|null Tool instance
	 */
	private function get_plugin_logs_tool_instance() {
		// Check if the Plugin Logs tool class exists
		if ( ! class_exists( 'MPAI_Plugin_Logs_Tool' ) ) {
			$tool_path = plugin_dir_path( __FILE__ ) . 'implementations/class-mpai-plugin-logs-tool.php';
			if ( file_exists( $tool_path ) ) {
				require_once $tool_path;
				if ( class_exists( 'MPAI_Plugin_Logs_Tool' ) ) {
					return new MPAI_Plugin_Logs_Tool();
				}
			}
			return null;
		}
		
		return new MPAI_Plugin_Logs_Tool();
	}
}
