<?php
/**
 * Base abstract class for all agents
 *
 * @package MemberPress AI Assistant
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base abstract class for all agents
 */
abstract class MPAI_Base_Agent implements MPAI_Agent {
	/**
	 * Unique identifier
	 * @var string
	 */
	protected $id;
	
	/**
	 * Display name
	 * @var string
	 */
	protected $name;
	
	/**
	 * Description
	 * @var string
	 */
	protected $description;
	
	/**
	 * List of capabilities
	 * @var array
	 */
	protected $capabilities = [];
	
	/**
	 * Tool registry instance
	 * @var MPAI_Tool_Registry
	 */
	protected $tool_registry;
	
	/**
	 * Logger instance
	 * @var object
	 */
	protected $logger;
	
	/**
	 * Constructor
	 *
	 * @param object $tool_registry Tool registry
	 * @param object $logger Logger
	 */
	public function __construct( $tool_registry = null, $logger = null ) {
		$this->tool_registry = $tool_registry;
		$this->logger = $logger ?: $this->get_default_logger();
	}
	
	/**
	 * Get a default logger if none provided
	 *
	 * @return object Logger instance
	 */
	protected function get_default_logger() {
		return (object) [
			'info'    => function( $message, $context = [] ) { error_log( 'MPAI INFO: ' . $message ); },
			'warning' => function( $message, $context = [] ) { error_log( 'MPAI WARNING: ' . $message ); },
			'error'   => function( $message, $context = [] ) { error_log( 'MPAI ERROR: ' . $message ); },
		];
	}
	
	/**
	 * Get agent name
	 *
	 * @return string Agent name
	 */
	public function get_name() {
		return $this->name;
	}
	
	/**
	 * Get agent description
	 *
	 * @return string Agent description
	 */
	public function get_description() {
		return $this->description;
	}
	
	/**
	 * Get agent capabilities
	 *
	 * @return array List of capabilities
	 */
	public function get_capabilities() {
		return $this->capabilities;
	}
	
	/**
	 * Execute a tool with parameters
	 *
	 * @param string $tool_id Tool identifier
	 * @param array $parameters Tool parameters
	 * @return mixed Tool result
	 * @throws Exception If tool not found or execution fails
	 */
	protected function execute_tool( $tool_id, $parameters ) {
		if ( ! $this->tool_registry ) {
			throw new Exception( 'Tool registry not available' );
		}
		
		$tool = $this->tool_registry->get_tool( $tool_id );
		
		if ( ! $tool ) {
			throw new Exception( "Tool {$tool_id} not found" );
		}
		
		$this->logger->info( "Executing tool {$tool_id}" );
		
		try {
			return $tool->execute( $parameters );
		} catch ( Exception $e ) {
			$this->logger->error( "Tool execution failed: " . $e->getMessage() );
			throw $e;
		}
	}
	
	/**
	 * Generate a summary of the actions taken
	 *
	 * @param array $results Results from actions
	 * @param array $intent_data Original intent data
	 * @return string Human-readable summary
	 */
	protected function generate_summary( $results, $intent_data ) {
		// Try to use OpenAI if available
		if ( class_exists( 'MPAI_OpenAI' ) ) {
			$openai = new MPAI_OpenAI();
			
			$system_prompt = "You are a helpful assistant summarizing actions taken by an AI agent. ";
			$system_prompt .= "Create a concise, human-readable summary of the actions taken and their results. ";
			$system_prompt .= "Be specific about what was accomplished.";
			
			$user_prompt = "Original request: {$intent_data['original_message']}\n\n";
			$user_prompt .= "Actions and results:\n" . json_encode( $results, JSON_PRETTY_PRINT );
			
			$messages = [
				['role' => 'system', 'content' => $system_prompt],
				['role' => 'user', 'content' => $user_prompt]
			];
			
			$response = $openai->generate_chat_completion( $messages );
			
			if ( ! is_wp_error( $response ) ) {
				return $response;
			}
		}
		
		// Fallback to simple summary if OpenAI not available
		$summary = "Executed " . count( $results ) . " actions in response to: {$intent_data['original_message']}\n";
		
		foreach ( $results as $idx => $result ) {
			$status = isset( $result['status'] ) && 'success' === $result['status'] ? '✓' : '✗';
			$description = isset( $result['description'] ) ? $result['description'] : "Action #{$idx}";
			$summary .= "- {$status} {$description}\n";
		}
		
		return $summary;
	}
}
