<?php
/**
 * Tool for executing WP-CLI commands
 *
 * @package MemberPress AI Assistant
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tool for executing WP-CLI commands
 */
class MPAI_WP_CLI_Tool extends MPAI_Base_Tool {
	/**
	 * Allowlist of permitted command prefixes
	 * @var array
	 */
	private $allowed_command_prefixes = [
		'wp plugin list',
		'wp plugin update',
		'wp theme list',
		'wp theme update',
		'wp post list',
		'wp post get',
		'wp user list',
		'wp user get',
		'wp option get',
		'wp core version',
		'wp core verify-checksums',
		'wp mepr',  // MemberPress commands
	];
	
	/**
	 * Execution timeout in seconds
	 * @var int
	 */
	private $execution_timeout = 30;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->name = 'WP-CLI Tool';
		$this->description = 'Executes WordPress CLI commands securely';
		
		// Allow plugins to extend the allowed commands
		$this->allowed_command_prefixes = apply_filters( 'mpai_allowed_cli_commands', $this->allowed_command_prefixes );
	}
	
	/**
	 * Execute a WP-CLI command
	 *
	 * @param array $parameters Parameters for the tool
	 * @return mixed Command result
	 * @throws Exception If command validation fails or execution error
	 */
	public function execute( $parameters ) {
		if ( ! isset( $parameters['command'] ) ) {
			throw new Exception( 'Command parameter is required' );
		}
		
		$command = $parameters['command'];
		error_log('MPAI_WP_CLI_Tool: Executing command: ' . $command);
		
		// Validate the command
		if ( ! $this->validate_command( $command ) ) {
			throw new Exception( 'Command validation failed: not in allowlist' );
		}
		
		// Special handling for specific commands that should use internal WordPress functions
		if (strpos($command, 'wp plugin list') === 0) {
			return $this->handle_plugin_list_command($command, $parameters);
		}
		
		// Set execution timeout
		$timeout = isset( $parameters['timeout'] ) ? 
			min( (int) $parameters['timeout'], 60 ) : 
			$this->execution_timeout;
		
		// Check if WP-CLI is available
		if (!class_exists('WP_CLI')) {
			error_log('MPAI_WP_CLI_Tool: WP-CLI not available, using alternative methods');
			return $this->handle_command_without_wpcli($command, $parameters);
		}
		
		// Build the command
		$wp_cli_command = $this->build_command( $command );
		
		// Execute the command
		$output = [];
		$return_var = 0;
		$last_line = exec( $wp_cli_command, $output, $return_var );
		
		if ( $return_var !== 0 ) {
			throw new Exception( 'Command execution failed with code ' . $return_var . ': ' . implode( "\n", $output ) );
		}
		
		// Parse the output based on requested format
		$format = isset( $parameters['format'] ) ? $parameters['format'] : 'text';
		
		return $this->parse_output( $output, $format );
	}
	
	/**
	 * Handle plugin list command using internal WordPress functions
	 *
	 * @param string $command The original command
	 * @param array $parameters Additional parameters
	 * @return string Formatted plugin list output
	 */
	private function handle_plugin_list_command($command, $parameters) {
		error_log('MPAI_WP_CLI_Tool: Using internal WordPress functions for plugin list');
		
		// Load WP API Tool to use the enhanced get_plugins method
		if (!class_exists('MPAI_WP_API_Tool')) {
			$tool_path = dirname(__FILE__) . '/class-mpai-wp-api-tool.php';
			if (file_exists($tool_path)) {
				require_once $tool_path;
			} else {
				error_log('MPAI_WP_CLI_Tool: Could not find WP API Tool file');
				throw new Exception('Could not load WP API Tool for plugin list');
			}
		}
		
		// Initialize WP API Tool
		$wp_api_tool = new MPAI_WP_API_Tool();
		
		// Extract any flags from the command (--status=active, etc.)
		$status_filter = null;
		if (preg_match('/--status=(\w+)/', $command, $matches)) {
			$status_filter = $matches[1];
		}
		
		// Call get_plugins with appropriate parameters
		$api_params = array(
			'action' => 'get_plugins',
			'format' => 'table',  // Always return tabular format for CLI commands
		);
		
		if ($status_filter) {
			$api_params['status'] = $status_filter;
		}
		
		// Get timestamp for generated output
		$current_time = date('H:i:s');
		error_log('MPAI_WP_CLI_Tool: Generated at ' . $current_time);
		
		// Execute the API call to get plugins
		$result = $wp_api_tool->execute($api_params);
		
		// Format output
		if (is_array($result) && isset($result['table_data'])) {
			// Already formatted as table
			return $result['table_data'];
		} elseif (is_array($result) && isset($result['plugins'])) {
			// Format plugins as table
			$plugins = $result['plugins'];
			$output = "Name\tStatus\tVersion\tLast Activity (Generated at $current_time)\n";
			
			foreach ($plugins as $plugin) {
				// Apply status filter if specified
				if ($status_filter && $plugin['status'] !== $status_filter) {
					continue;
				}
				
				$name = $plugin['name'];
				$status = $plugin['status'];
				$version = $plugin['version'];
				$activity = isset($plugin['last_activity']) ? $plugin['last_activity'] : 'No recent activity';
				
				$output .= "$name\t$status\t$version\t$activity\n";
			}
			
			return $output;
		} else {
			// Return raw result if not in expected format
			return json_encode($result);
		}
	}
	
	/**
	 * Handle commands when WP-CLI is not available
	 *
	 * @param string $command The command to handle
	 * @param array $parameters Additional parameters
	 * @return mixed Result of the alternative command handling
	 */
	private function handle_command_without_wpcli($command, $parameters) {
		// Extract the command type
		$command_parts = explode(' ', trim($command));
		
		// Handle different command types
		if (count($command_parts) >= 3 && $command_parts[0] === 'wp' && $command_parts[1] === 'plugin') {
			// wp plugin commands
			if ($command_parts[2] === 'list') {
				return $this->handle_plugin_list_command($command, $parameters);
			}
		}
		
		// Default message for unsupported commands
		return "The AI assistant cannot directly run WP-CLI commands on your server. Command '$command' cannot be executed directly.\n"
			 . "Please use WordPress API tools instead (wp_api, memberpress_info, plugin_logs, etc.).";
	}
	
	/**
	 * Validate that a command is allowed
	 *
	 * @param string $command WP-CLI command
	 * @return bool Whether command is valid
	 */
	private function validate_command( $command ) {
		// Sanitize the command
		$sanitized_command = $this->sanitize_command( $command );
		
		// Check against allowlist
		foreach ( $this->allowed_command_prefixes as $prefix ) {
			if ( strpos( $sanitized_command, $prefix ) === 0 ) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Sanitize a command to prevent injection
	 *
	 * @param string $command Command to sanitize
	 * @return string Sanitized command
	 */
	private function sanitize_command( $command ) {
		// Remove potentially dangerous characters
		$command = preg_replace( '/[;&|><]/', '', $command );
		
		// Ensure command starts with 'wp '
		if ( strpos( $command, 'wp ' ) !== 0 ) {
			$command = 'wp ' . $command;
		}
		
		return trim( $command );
	}
	
	/**
	 * Build the full command with proper escaping
	 *
	 * @param string $command WP-CLI command
	 * @return string Full bash command
	 */
	private function build_command( $command ) {
		// Format as JSON for easier parsing if possible
		if ( strpos( $command, '--format=' ) === false && strpos( $command, 'help' ) === false ) {
			$command .= ' --format=json';
		}
		
		// Escape the command
		$escaped_command = escapeshellcmd( $command );
		
		// Add timeout
		$full_command = "timeout {$this->execution_timeout}s {$escaped_command}";
		
		return $full_command;
	}
	
	/**
	 * Parse command output into usable format
	 *
	 * @param array $output Command output lines
	 * @param string $format Desired output format
	 * @return mixed Parsed output
	 */
	private function parse_output( $output, $format ) {
		$raw_output = implode( "\n", $output );
		
		switch ( $format ) {
			case 'json':
				$decoded = json_decode( $raw_output, true );
				if ( $decoded && json_last_error() === JSON_ERROR_NONE ) {
					return $decoded;
				}
				// Fall through to array if not valid JSON
				
			case 'array':
				return $output;
				
			case 'text':
			default:
				return $raw_output;
		}
	}
}
