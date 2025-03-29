<?php
/**
 * Main orchestrator for the agent system
 *
 * @package MemberPress AI Assistant
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main orchestrator for the agent system
 */
class MPAI_Agent_Orchestrator {
	/**
	 * Registry of available agents
	 * @var array
	 */
	private $agents = [];
	
	/**
	 * Tool registry instance
	 * @var MPAI_Tool_Registry
	 */
	private $tool_registry;
	
	/**
	 * Logger instance
	 * @var object
	 */
	private $logger;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->tool_registry = $this->get_tool_registry();
		$this->logger = $this->get_logger();
		
		// Register core agents
		$this->register_core_agents();
	}
	
	/**
	 * Get tool registry instance
	 *
	 * @return MPAI_Tool_Registry Tool registry
	 */
	private function get_tool_registry() {
		// Check if Tool Registry class exists
		if ( ! class_exists( 'MPAI_Tool_Registry' ) ) {
			$tool_registry_path = plugin_dir_path( __FILE__ ) . '../tools/class-mpai-tool-registry.php';
			if ( file_exists( $tool_registry_path ) ) {
				require_once $tool_registry_path;
			}
		}
		
		if ( class_exists( 'MPAI_Tool_Registry' ) ) {
			return new MPAI_Tool_Registry();
		}
		
		return null;
	}
	
	/**
	 * Get default logger
	 *
	 * @return object Logger
	 */
	private function get_logger() {
		return (object) [
			'info'    => function( $message, $context = [] ) { error_log( 'MPAI INFO: ' . $message ); },
			'warning' => function( $message, $context = [] ) { error_log( 'MPAI WARNING: ' . $message ); },
			'error'   => function( $message, $context = [] ) { error_log( 'MPAI ERROR: ' . $message ); },
		];
	}
	
	/**
	 * Register a new agent
	 *
	 * @param string $agent_id Unique identifier for the agent
	 * @param MPAI_Agent $agent_instance Instance of the agent
	 * @return bool Success status
	 */
	public function register_agent( $agent_id, $agent_instance ) {
		if ( isset( $this->agents[$agent_id] ) ) {
			$this->logger->warning( "Agent with ID {$agent_id} already registered" );
			return false;
		}
		
		$this->agents[$agent_id] = $agent_instance;
		return true;
	}
	
	/**
	 * Process a user request
	 *
	 * @param string $user_message The natural language request
	 * @param int $user_id User ID
	 * @return array Response data
	 */
	public function process_request( $user_message, $user_id = null ) {
		try {
			// Get user context (implemented later)
			$user_context = $this->get_user_context( $user_id );
			
			// Analyze intent
			$intent_data = $this->determine_intent( $user_message, $user_context );
			
			// Find appropriate agent(s)
			$primary_agent_id = $intent_data['primary_agent'];
			
			// Dispatch to primary agent
			if ( ! isset( $this->agents[$primary_agent_id] ) ) {
				throw new Exception( "Agent {$primary_agent_id} not found" );
			}
			
			$result = $this->agents[$primary_agent_id]->process_request( $intent_data, $user_context );
			
			// Update memory with results (implemented later)
			$this->update_memory( $user_id, $intent_data, $result );
			
			// Log the successful completion
			$this->logger->info( "Successfully processed request for agent {$primary_agent_id}" );
			
			return [
				'success' => true,
				'message' => $result['message'],
				'data' => isset( $result['data'] ) ? $result['data'] : [],
				'agent' => $primary_agent_id,
			];
		} catch ( Exception $e ) {
			$this->logger->error( "Error processing request: " . $e->getMessage() );
			
			return [
				'success' => false,
				'message' => "Sorry, I couldn't process that request: " . $e->getMessage(),
				'error' => $e->getMessage(),
			];
		}
	}
	
	/**
	 * Get user context from memory
	 *
	 * @param int $user_id User ID
	 * @return array User context
	 */
	private function get_user_context( $user_id ) {
		// This would be expanded to retrieve context from a proper storage system
		return [];
	}
	
	/**
	 * Update memory with results
	 *
	 * @param int $user_id User ID
	 * @param array $intent_data Intent data
	 * @param array $result Result data
	 */
	private function update_memory( $user_id, $intent_data, $result ) {
		// This would be expanded to store in a proper storage system
	}
	
	/**
	 * Determine the user's intent and which agent should handle it
	 *
	 * @param string $message User message
	 * @param array $context User context
	 * @return array Intent data including primary agent
	 */
	private function determine_intent( $message, $context = [] ) {
		$intent_category = $this->analyze_intent( $message );
		
		// Map intent category to primary agent
		$agent_map = [
			'memberpress_management' => 'memberpress',
			'content_creation' => 'content',
			'system_management' => 'system',
			'security_audit' => 'security',
			'analytics' => 'analytics',
			'general_question' => 'memberpress', // Default to MemberPress agent for general questions
		];
		
		$primary_agent = isset( $agent_map[$intent_category] ) ? $agent_map[$intent_category] : 'memberpress';
		
		// If preferred agent isn't available, use MemberPress agent as fallback
		if ( ! isset( $this->agents[$primary_agent] ) ) {
			$primary_agent = 'memberpress';
		}
		
		return [
			'intent' => $intent_category,
			'primary_agent' => $primary_agent,
			'original_message' => $message,
			'context' => $context,
			'timestamp' => time(),
		];
	}
	
	/**
	 * Analyze intent from message
	 *
	 * @param string $message User message
	 * @return string Intent category
	 */
	private function analyze_intent( $message ) {
		$message = strtolower( $message );
		
		// Check for MemberPress-specific keywords
		$memberpress_keywords = ['membership', 'subscription', 'transaction', 'mepr', 'memberpress', 'coupon'];
		foreach ( $memberpress_keywords as $keyword ) {
			if ( strpos( $message, $keyword ) !== false ) {
				return 'memberpress_management';
			}
		}
		
		// Simple keyword matching for other intents
		if ( strpos( $message, 'content' ) !== false || strpos( $message, 'post' ) !== false || strpos( $message, 'page' ) !== false ) {
			return 'content_creation';
		} else if ( strpos( $message, 'plugin' ) !== false || strpos( $message, 'update' ) !== false || strpos( $message, 'install' ) !== false ) {
			return 'system_management';
		} else if ( strpos( $message, 'security' ) !== false || strpos( $message, 'hack' ) !== false || strpos( $message, 'protect' ) !== false ) {
			return 'security_audit';
		} else if ( strpos( $message, 'report' ) !== false || strpos( $message, 'stat' ) !== false || strpos( $message, 'analytic' ) !== false ) {
			return 'analytics';
		}
		
		// Default to memberpress management
		return 'memberpress_management';
	}
	
	/**
	 * Register all core agents
	 */
	private function register_core_agents() {
		// Register the MemberPress agent
		$this->register_memberpress_agent();
		
		// Other agents would be registered here
	}
	
	/**
	 * Register MemberPress agent
	 */
	private function register_memberpress_agent() {
		// Check if the class exists
		if ( ! class_exists( 'MPAI_MemberPress_Agent' ) ) {
			$agent_path = plugin_dir_path( __FILE__ ) . 'specialized/class-mpai-memberpress-agent.php';
			if ( file_exists( $agent_path ) ) {
				require_once $agent_path;
			}
		}
		
		// Create and register the agent if available
		if ( class_exists( 'MPAI_MemberPress_Agent' ) ) {
			$memberpress_agent = new MPAI_MemberPress_Agent( $this->tool_registry, $this->logger );
			$this->register_agent( 'memberpress', $memberpress_agent );
		}
	}
	
	/**
	 * Get list of available agents and their capabilities
	 *
	 * @return array Agent information
	 */
	public function get_available_agents() {
		$result = [];
		
		foreach ( $this->agents as $agent_id => $agent ) {
			$result[$agent_id] = [
				'name' => $agent->get_name(),
				'description' => $agent->get_description(),
				'capabilities' => $agent->get_capabilities(),
			];
		}
		
		return $result;
	}
}