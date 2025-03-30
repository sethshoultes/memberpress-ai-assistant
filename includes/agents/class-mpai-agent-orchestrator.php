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
	 * Context manager
	 * @var MPAI_Context_Manager
	 */
	private $context_manager;
	
	/**
	 * SDK integration instance
	 * @var MPAI_SDK_Integration
	 */
	private $sdk_integration = null;
	
	/**
	 * Whether SDK is initialized
	 * @var bool
	 */
	private $sdk_initialized = false;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->tool_registry = $this->get_tool_registry();
		$this->logger = $this->get_logger();
		$this->context_manager = $this->get_context_manager();
		
		// Register core agents
		$this->register_core_agents();
		
		// Initialize OpenAI Agents SDK integration
		$this->initialize_sdk();
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
			'log'     => function( $level, $message, $context = [] ) { error_log( "MPAI {$level}: " . $message ); },
		];
	}
	
	/**
	 * Get context manager
	 *
	 * @return MPAI_Context_Manager Context manager
	 */
	private function get_context_manager() {
		if ( ! class_exists( 'MPAI_Context_Manager' ) ) {
			$context_manager_path = plugin_dir_path( dirname( __FILE__ ) ) . 'class-mpai-context-manager.php';
			if ( file_exists( $context_manager_path ) ) {
				require_once $context_manager_path;
			}
		}
		
		if ( class_exists( 'MPAI_Context_Manager' ) ) {
			return new MPAI_Context_Manager();
		}
		
		return null;
	}
	
	/**
	 * Initialize SDK integration
	 *
	 * @return bool Whether SDK was initialized
	 */
	private function initialize_sdk() {
		try {
			// Check if SDK files exist
			$sdk_path = plugin_dir_path( __FILE__ ) . 'sdk/class-mpai-sdk-integration.php';
			if ( ! file_exists( $sdk_path ) ) {
				$this->logger->warning( 'SDK integration file not found: ' . $sdk_path );
				return false;
			}
			
			// Include SDK files
			require_once $sdk_path;
			
			// Create SDK integration instance
			if ( class_exists( 'MPAI_SDK_Integration' ) ) {
				$this->sdk_integration = new MPAI_SDK_Integration( 
					$this->tool_registry, 
					$this->context_manager, 
					$this->logger 
				);
				
				// Check if initialization was successful
				$this->sdk_initialized = $this->sdk_integration->is_initialized();
				
				if ( $this->sdk_initialized ) {
					$this->logger->info( 'SDK integration initialized successfully' );
					
					// Register existing agents with SDK
					foreach ( $this->agents as $agent_id => $agent_instance ) {
						$this->sdk_integration->register_agent( $agent_id, $agent_instance );
					}
				} else {
					$this->logger->warning( 'SDK integration failed to initialize: ' . $this->sdk_integration->get_error() );
				}
				
				return $this->sdk_initialized;
			} else {
				$this->logger->warning( 'SDK integration class not found' );
				return false;
			}
		} catch ( Exception $e ) {
			$this->logger->error( 'Error initializing SDK: ' . $e->getMessage() );
			return false;
		}
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
		
		// If SDK is initialized, also register the agent with the SDK
		if ( $this->sdk_initialized && $this->sdk_integration ) {
			try {
				$this->sdk_integration->register_agent( $agent_id, $agent_instance );
				$this->logger->info( "Agent {$agent_id} registered with SDK" );
			} catch ( Exception $e ) {
				$this->logger->warning( "Failed to register agent {$agent_id} with SDK: " . $e->getMessage() );
				// Continue even if SDK registration fails
			}
		}
		
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
			// Get user context
			$user_context = $this->get_user_context( $user_id );
			
			// Log the request
			$this->logger->info( "Processing request", [
				'user_id' => $user_id,
				'message' => $user_message,
				'using_sdk' => $this->sdk_initialized,
			] );
			
			// If SDK is initialized, use it for processing
			if ( $this->sdk_initialized && $this->sdk_integration ) {
				return $this->process_with_sdk( $user_message, $user_id, $user_context );
			}
			
			// Otherwise use the traditional processing method
			return $this->process_with_traditional_method( $user_message, $user_id, $user_context );
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
	 * Process a request using the SDK
	 *
	 * @param string $user_message The natural language request
	 * @param int $user_id User ID
	 * @param array $user_context User context data
	 * @return array Response data
	 */
	private function process_with_sdk( $user_message, $user_id, $user_context ) {
		try {
			// Use the SDK integration to process the request
			$sdk_result = $this->sdk_integration->process_request(
				$user_message,
				$user_id,
				$user_context
			);
			
			// Update memory with results
			$this->update_memory( $user_id, ['original_message' => $user_message], $sdk_result );
			
			// Log the successful completion
			$this->logger->info( "Successfully processed request with SDK", [
				'user_id' => $user_id,
				'agent' => isset( $sdk_result['agent'] ) ? $sdk_result['agent'] : 'unknown',
			] );
			
			return $sdk_result;
		} catch ( Exception $e ) {
			$this->logger->error( "Error processing with SDK: " . $e->getMessage() );
			
			// Fall back to traditional method if SDK processing fails
			$this->logger->info( "Falling back to traditional processing method" );
			return $this->process_with_traditional_method( $user_message, $user_id, $user_context );
		}
	}
	
	/**
	 * Process a request using the traditional method
	 *
	 * @param string $user_message The natural language request
	 * @param int $user_id User ID
	 * @param array $user_context User context data
	 * @return array Response data
	 */
	private function process_with_traditional_method( $user_message, $user_id, $user_context ) {
		try {
			// Analyze intent
			$intent_data = $this->determine_intent( $user_message, $user_context );
			
			// Find appropriate agent(s)
			$primary_agent_id = $intent_data['primary_agent'];
			
			// Dispatch to primary agent
			if ( ! isset( $this->agents[$primary_agent_id] ) ) {
				throw new Exception( "Agent {$primary_agent_id} not found" );
			}
			
			$result = $this->agents[$primary_agent_id]->process_request( $intent_data, $user_context );
			
			// Update memory with results
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
			$this->logger->error( "Error processing with traditional method: " . $e->getMessage() );
			
			throw $e; // Re-throw to be caught by the main process_request method
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
			$agent_info = [
				'name' => $agent->get_name(),
				'description' => $agent->get_description(),
				'capabilities' => $agent->get_capabilities(),
			];
			
			// Add SDK status if available
			if ( $this->sdk_initialized && $this->sdk_integration ) {
				$agent_info['sdk_enabled'] = $this->sdk_integration->is_agent_registered( $agent_id );
				$agent_info['sdk_status'] = $this->sdk_initialized ? 'active' : 'inactive';
			}
			
			$result[$agent_id] = $agent_info;
		}
		
		return $result;
	}
	
	/**
	 * Execute a background task
	 *
	 * @param string $thread_id Thread ID
	 * @param string $assistant_id Assistant ID
	 * @param string $task_id Task ID
	 * @param int $user_id User ID
	 * @return bool Success status
	 */
	public function execute_background_task( $thread_id, $assistant_id, $task_id, $user_id = 0 ) {
		if ( ! $this->sdk_initialized || ! $this->sdk_integration ) {
			$this->logger->error( "Cannot execute background task: SDK not initialized" );
			return false;
		}
		
		try {
			// Execute the task using the SDK integration
			$this->sdk_integration->execute_background_task( $thread_id, $assistant_id, $task_id, $user_id );
			return true;
		} catch ( Exception $e ) {
			$this->logger->error( "Error executing background task: " . $e->getMessage() );
			
			// Update task status with error
			$task_info = get_option( "mpai_task_{$task_id}", [] );
			$task_info['status'] = 'failed';
			$task_info['error'] = $e->getMessage();
			update_option( "mpai_task_{$task_id}", $task_info );
			
			return false;
		}
	}
	
	/**
	 * Handle agent handoff
	 *
	 * @param string $from_agent_id Source agent ID
	 * @param string $to_agent_id Target agent ID
	 * @param string $message User message
	 * @param array $context Context data
	 * @return array Handoff result
	 */
	public function handle_agent_handoff( $from_agent_id, $to_agent_id, $message, $context = [] ) {
		// Check if both agents exist
		if ( ! isset( $this->agents[$from_agent_id] ) ) {
			throw new Exception( "Source agent {$from_agent_id} not found" );
		}
		
		if ( ! isset( $this->agents[$to_agent_id] ) ) {
			throw new Exception( "Target agent {$to_agent_id} not found" );
		}
		
		// Handle using SDK if available
		if ( $this->sdk_initialized && $this->sdk_integration ) {
			try {
				$handoff_result = $this->sdk_integration->handle_agent_handoff(
					$from_agent_id,
					$to_agent_id,
					$message,
					$context
				);
				
				$this->logger->info( "Successfully handled handoff with SDK from {$from_agent_id} to {$to_agent_id}" );
				
				return $handoff_result;
			} catch ( Exception $e ) {
				$this->logger->error( "Error handling handoff with SDK: " . $e->getMessage() );
				// Fall back to traditional handoff if SDK fails
			}
		}
		
		// Traditional handoff (simple re-routing)
		$this->logger->info( "Performing traditional handoff from {$from_agent_id} to {$to_agent_id}" );
		
		// Create intent data for target agent
		$intent_data = [
			'intent' => 'handoff',
			'primary_agent' => $to_agent_id,
			'original_message' => $message,
			'from_agent' => $from_agent_id,
			'context' => $context,
			'timestamp' => time(),
		];
		
		// Process with target agent
		$result = $this->agents[$to_agent_id]->process_request( $intent_data, $context );
		
		return [
			'success' => true,
			'agent' => $to_agent_id,
			'message' => $result['message'],
			'data' => isset( $result['data'] ) ? $result['data'] : [],
		];
	}
	
	/**
	 * Start a running agent for a long-running task
	 *
	 * @param string $agent_id Agent ID
	 * @param string $task_description Task description
	 * @param array $parameters Task parameters
	 * @param int $user_id User ID
	 * @return array Task information
	 */
	public function start_running_agent( $agent_id, $task_description, $parameters = [], $user_id = 0 ) {
		// Check if agent exists
		if ( ! isset( $this->agents[$agent_id] ) ) {
			throw new Exception( "Agent {$agent_id} not found" );
		}
		
		// Use SDK if available
		if ( $this->sdk_initialized && $this->sdk_integration ) {
			try {
				$run_result = $this->sdk_integration->start_running_agent(
					$agent_id,
					$task_description,
					$parameters,
					$user_id
				);
				
				$this->logger->info( "Successfully started running agent with SDK: {$agent_id}" );
				
				return $run_result;
			} catch ( Exception $e ) {
				$this->logger->error( "Error starting running agent with SDK: " . $e->getMessage() );
				// Fall back to traditional processing
			}
		}
		
		// Simple implementation for traditional method
		// In a real implementation, this would use WP Cron or similar
		$task_id = uniqid( 'task_' );
		
		// Create intent data
		$intent_data = [
			'intent' => 'background_task',
			'primary_agent' => $agent_id,
			'task_id' => $task_id,
			'original_message' => $task_description,
			'parameters' => $parameters,
			'timestamp' => time(),
		];
		
		// Process the task directly (blocking)
		// In a real implementation, this would be asynchronous
		$result = $this->agents[$agent_id]->process_request( $intent_data, [] );
		
		return [
			'success' => true,
			'task_id' => $task_id,
			'status' => 'completed', // Since it's processed immediately
			'result' => $result,
		];
	}
}