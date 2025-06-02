<?php
/**
 * SDK Agent Adapter
 *
 * @package MemberPress AI Assistant
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adapter to convert between MemberPress agents and SDK formats
 */
class MPAI_SDK_Agent_Adapter {
	/**
	 * Agent ID
	 * 
	 * @var string
	 */
	private $agent_id;
	
	/**
	 * Agent instance
	 * 
	 * @var MPAI_Base_Agent
	 */
	private $agent_instance;
	
	/**
	 * SDK configuration
	 * 
	 * @var array
	 */
	private $sdk_config;
	
	/**
	 * Constructor
	 * 
	 * @param string $agent_id Agent ID.
	 * @param MPAI_Base_Agent $agent_instance Agent instance.
	 */
	public function __construct( $agent_id, $agent_instance ) {
		$this->agent_id = $agent_id;
		$this->agent_instance = $agent_instance;
		$this->sdk_config = $this->generate_sdk_config();
	}
	
	/**
	 * Get SDK configuration for this agent
	 * 
	 * @return array SDK configuration.
	 */
	public function get_sdk_config() {
		return $this->sdk_config;
	}
	
	/**
	 * Generate SDK configuration from agent instance
	 * 
	 * @return array SDK configuration.
	 */
	private function generate_sdk_config() {
		$agent_name = $this->agent_instance->get_name();
		$agent_description = $this->agent_instance->get_description();
		$agent_capabilities = $this->agent_instance->get_capabilities();
		
		// Format capabilities as a string
		$capabilities_text = '';
		foreach ( $agent_capabilities as $capability => $description ) {
			$capabilities_text .= "- {$description}\n";
		}
		
		// Generate system prompt / instructions
		$instructions = "You are {$agent_name}, an AI assistant specialized in helping with MemberPress and WordPress tasks.\n\n";
		$instructions .= "Description: {$agent_description}\n\n";
		$instructions .= "Your capabilities include:\n{$capabilities_text}\n";
		$instructions .= "Respond in a helpful, informative manner. Focus on providing accurate information and taking appropriate actions based on the user's request. ";
		$instructions .= "If you don't know the answer, be honest about it rather than making up information.";
		
		// Create SDK configuration
		$sdk_config = [
			'id' => $this->agent_id,
			'name' => $agent_name,
			'description' => $agent_description,
			'model' => 'gpt-4o', // Default model
			'instructions' => $instructions,
			'capabilities' => $agent_capabilities,
			'tools' => $this->get_agent_tools(),
		];
		
		return $sdk_config;
	}
	
	/**
	 * Get tools available to this agent
	 * 
	 * @return array Tool configurations for SDK.
	 */
	private function get_agent_tools() {
		// In the real implementation, this would map to the specific tools
		// that the agent has access to. For now, returning an empty array
		// which means the agent will have no special tools.
		return [];
	}
	
	/**
	 * Convert a message to SDK format
	 * 
	 * @param array $message Message data.
	 * @return array SDK message format.
	 */
	public function convert_to_sdk_message( $message ) {
		return [
			'role' => isset( $message['role'] ) ? $message['role'] : 'user',
			'content' => isset( $message['content'] ) ? $message['content'] : '',
		];
	}
	
	/**
	 * Convert SDK result to our format
	 * 
	 * @param array $sdk_result SDK result data.
	 * @return array Our result format.
	 */
	public function convert_from_sdk_result( $sdk_result ) {
		// Extract the core information from SDK result
		$result = [
			'success' => true,
			'message' => isset( $sdk_result['response'] ) ? $sdk_result['response'] : '',
			'data' => $sdk_result,
			'agent' => $this->agent_id,
		];
		
		// Add tool usage information if available
		if ( isset( $sdk_result['tool_calls'] ) && ! empty( $sdk_result['tool_calls'] ) ) {
			$result['tool_calls'] = $sdk_result['tool_calls'];
		}
		
		return $result;
	}
}