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
	 * Input validator instance
	 * @var MPAI_Input_Validator
	 */
	protected $validator;
	
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
	 * Initialize the input validator
	 * 
	 * @return MPAI_Input_Validator The validator instance
	 */
	protected function init_validator() {
		// Load the input validator class if not already loaded
		if (!class_exists('MPAI_Input_Validator')) {
			require_once dirname(dirname(__FILE__)) . '/class-mpai-input-validator.php';
		}
		
		// Create validator instance
		$this->validator = new MPAI_Input_Validator();
		
		// Load validation rules from parameter schema if available
		if (method_exists($this, 'get_parameters')) {
			$parameter_schema = [
				'properties' => $this->get_parameters()
			];
			
			// Set required parameters if defined
			if (method_exists($this, 'get_required_parameters')) {
				$parameter_schema['required'] = $this->get_required_parameters();
			}
			
			$this->validator->load_from_schema($parameter_schema);
		}
		
		return $this->validator;
	}
	
	/**
	 * Validate parameters
	 *
	 * @param array $parameters Parameters to validate
	 * @return array Validated and sanitized parameters with defaults applied
	 * @throws Exception If validation fails
	 */
	protected function validate_parameters($parameters) {
		// Ensure validator is initialized
		if (!$this->validator) {
			$this->init_validator();
		}
		
		// Apply defaults to parameters
		$parameters = $this->validator->apply_defaults($parameters);
		
		// Validate parameters
		$validation_result = $this->validator->validate($parameters);
		
		// If validation fails, throw an exception
		if (!$validation_result['valid']) {
			$error_messages = [];
			foreach ($validation_result['errors'] as $field => $errors) {
				$error_messages[] = implode(', ', $errors);
			}
			throw new Exception('Parameter validation failed: ' . implode('; ', $error_messages));
		}
		
		// Return validated and sanitized data
		return $validation_result['data'];
	}
	
	/**
	 * Execute the tool with parameters
	 *
	 * @param array $parameters Parameters for the tool
	 * @return mixed Tool result
	 */
	public function execute($parameters) {
		try {
			// Initialize validator if not already done
			if (!$this->validator) {
				$this->init_validator();
			}
			
			// Validate and sanitize parameters
			$validated_parameters = $this->validate_parameters($parameters);
			
			// Execute tool implementation with validated parameters
			return $this->execute_tool($validated_parameters);
		} catch (Exception $e) {
			// Log the error
			if (function_exists('error_log')) {
				error_log('MPAI Tool Error: ' . $e->getMessage());
			}
			
			// Return error information
			return [
				'success' => false,
				'message' => $e->getMessage()
			];
		}
	}
	
	/**
	 * Execute the tool implementation with validated parameters
	 *
	 * @param array $parameters Validated parameters for the tool
	 * @return mixed Tool result
	 */
	abstract protected function execute_tool($parameters);
}
