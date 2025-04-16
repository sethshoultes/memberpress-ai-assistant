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
				mpai_log_debug('Loaded WP-CLI Executor class', 'wpcli-tool-wrapper');
			} else {
				mpai_log_error('Could not find WP-CLI Executor file', 'wpcli-tool-wrapper');
				return;
			}
		}
		
		// Initialize the executor
		$this->executor = new MPAI_WP_CLI_Executor();
		mpai_log_debug('Initialized WP-CLI Executor', 'wpcli-tool-wrapper');
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
		mpai_log_debug('Executing tool with command: ' . $parameters['command'], 'wpcli-tool');
		
		// Make sure the executor is initialized
		if (!$this->executor) {
			mpai_log_debug('Executor not initialized, initializing now', 'wpcli-tool');
			$this->init_executor();
			
			// If still not initialized, throw an exception
			if (!$this->executor) {
				mpai_log_error('Failed to initialize executor', 'wpcli-tool');
				throw new Exception('Failed to initialize WP-CLI executor');
			}
		}
		
		try {
			// Execute the command using the executor
			mpai_log_debug('Calling executor->execute()', 'wpcli-tool');
			$result = $this->executor->execute($parameters['command'], $parameters);
			mpai_log_debug('Executed command, result type: ' . gettype($result), 'wpcli-tool');
			
			// Special case for wp plugin list command - enhance logging
			if (trim($parameters['command']) === 'wp plugin list') {
				mpai_log_debug('wp plugin list command handled, raw result: ' . substr(json_encode($result), 0, 200) . '...', 'wpcli-tool');
			}
			
			// Handle the result format
			if (is_array($result) && isset($result['output'])) {
				mpai_log_debug('Result has output field, type: ' . gettype($result['output']), 'wpcli-tool');
				
				// Check if output is a string to avoid strlen() errors
				if (!is_string($result['output'])) {
					// If output is an array or object, convert to string
					if (is_array($result['output']) || is_object($result['output'])) {
						mpai_log_debug('Converting array/object output to JSON string', 'wpcli-tool');
						$result['output'] = json_encode($result['output']);
					} else {
						// For any other non-string type, convert to string
						mpai_log_debug('Converting non-string output to string: ' . gettype($result['output']), 'wpcli-tool');
						$result['output'] = (string)$result['output'];
					}
				}
				
				// Return just the output for backward compatibility
				return $result['output'];
			} else {
				// For non-array results or missing output, return as is
				mpai_log_debug('Returning result directly, type: ' . gettype($result), 'wpcli-tool');
				return $result;
			}
		} catch (Exception $e) {
			mpai_log_error('Exception in execute_tool: ' . $e->getMessage(), 'wpcli-tool', array(
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ));
			throw $e; // Re-throw to be handled by the caller
		}
	}
}