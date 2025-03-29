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
		
		// Validate the command
		if ( ! $this->validate_command( $command ) ) {
			throw new Exception( 'Command validation failed: not in allowlist' );
		}
		
		// Set execution timeout
		$timeout = isset( $parameters['timeout'] ) ? 
			min( (int) $parameters['timeout'], 60 ) : 
			$this->execution_timeout;
		
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
