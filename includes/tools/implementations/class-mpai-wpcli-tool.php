<?php
/**
 * Tool for executing WP-CLI commands
 * 
 * SECURITY NOTE: This implementation now uses the new MPAI_WP_CLI_Executor which follows
 * a permissive blacklist security approach rather than a restrictive whitelist. This is
 * more user-friendly while still providing protection against dangerous commands.
 *
 * @package MemberPress AI Assistant
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tool for executing WP-CLI commands - Wrapper for new implementation
 */
class MPAI_WP_CLI_Tool extends MPAI_Base_Tool {
	/**
	 * WP-CLI executor instance
	 * @var MPAI_WP_CLI_Executor
	 */
	private $executor;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->name = 'WP-CLI Tool';
		$this->description = 'Executes WordPress CLI commands securely';
		
		// Initialize the executor
		$this->init_executor();
	}
	
	/**
	 * Initialize the WP-CLI executor
	 */
	private function init_executor() {
		// Load the executor class if needed
		if (!class_exists('MPAI_WP_CLI_Executor')) {
			$executor_file = dirname(dirname(dirname(__FILE__))) . '/commands/class-mpai-wp-cli-executor.php';
			if (file_exists($executor_file)) {
				require_once $executor_file;
				error_log('MPAI_WP_CLI_Tool (Wrapper): Loaded WP-CLI Executor class');
			} else {
				error_log('MPAI_WP_CLI_Tool (Wrapper): Could not find WP-CLI Executor file');
				return;
			}
		}
		
		// Initialize the executor
		$this->executor = new MPAI_WP_CLI_Executor();
		error_log('MPAI_WP_CLI_Tool (Wrapper): Initialized WP-CLI Executor');
	}
	
	/**
	 * Get parameters for function calling
	 *
	 * @return array Parameters schema
	 */
	public function get_parameters() {
		return [
			'command' => [
				'type' => 'string',
				'description' => 'The WP-CLI command to execute',
				'required' => true
			],
			'timeout' => [
				'type' => 'integer',
				'description' => 'Execution timeout in seconds (max 60)',
				'default' => 30,
				'min' => 1,
				'max' => 60
			],
			'format' => [
				'type' => 'string',
				'description' => 'Output format',
				'enum' => ['text', 'json', 'array'],
				'default' => 'text'
			],
			'skip_cache' => [
				'type' => 'boolean',
				'description' => 'Skip cache and execute command directly',
				'default' => false
			]
		];
	}
	
	/**
	 * Get required parameters
	 *
	 * @return array List of required parameter names
	 */
	public function get_required_parameters() {
		return ['command'];
	}
	
	/**
	 * Execute the tool implementation with validated parameters
	 *
	 * @param array $parameters Validated parameters for the tool
	 * @return mixed Command result
	 * @throws Exception If command validation fails or execution error
	 */
	protected function execute_tool( $parameters ) {
		error_log('MPAI_WP_CLI_Tool: Executing tool with command: ' . $parameters['command']);
		
		// Make sure the executor is initialized
		if (!$this->executor) {
			error_log('MPAI_WP_CLI_Tool: Executor not initialized, initializing now');
			$this->init_executor();
			
			// If still not initialized, throw an exception
			if (!$this->executor) {
				error_log('MPAI_WP_CLI_Tool: Failed to initialize executor');
				throw new Exception('Failed to initialize WP-CLI executor');
			}
		}
		
		try {
			// Execute the command using the executor
			error_log('MPAI_WP_CLI_Tool: Calling executor->execute()');
			$result = $this->executor->execute($parameters['command'], $parameters);
			error_log('MPAI_WP_CLI_Tool: Executed command, result type: ' . gettype($result));
			
			// Special case for wp plugin list command - enhance logging
			if (trim($parameters['command']) === 'wp plugin list') {
				error_log('MPAI_WP_CLI_Tool: wp plugin list command handled, raw result: ' . substr(json_encode($result), 0, 200) . '...');
			}
			
			// Handle the result format
			if (is_array($result) && isset($result['output'])) {
				error_log('MPAI_WP_CLI_Tool: Result has output field, type: ' . gettype($result['output']));
				
				// Check if output is a string to avoid strlen() errors
				if (!is_string($result['output'])) {
					// If output is an array or object, convert to string
					if (is_array($result['output']) || is_object($result['output'])) {
						error_log('MPAI_WP_CLI_Tool: Converting array/object output to JSON string');
						$result['output'] = json_encode($result['output']);
					} else {
						// For any other non-string type, convert to string
						error_log('MPAI_WP_CLI_Tool: Converting non-string output to string: ' . gettype($result['output']));
						$result['output'] = (string)$result['output'];
					}
				}
				
				// Return just the output for backward compatibility
				return $result['output'];
			} else {
				// For non-array results or missing output, return as is
				error_log('MPAI_WP_CLI_Tool: Returning result directly, type: ' . gettype($result));
				return $result;
			}
		} catch (Exception $e) {
			error_log('MPAI_WP_CLI_Tool ERROR: Exception in execute_tool: ' . $e->getMessage());
			throw $e; // Re-throw to be handled by the caller
		}
	}
}