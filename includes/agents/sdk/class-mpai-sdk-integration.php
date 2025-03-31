<?php
/**
 * OpenAI SDK Integration Class
 *
 * Handles integration with OpenAI Assistants API
 *
 * @package MemberPress AI Assistant
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * OpenAI SDK Integration Class
 */
class MPAI_SDK_Integration {
	/**
	 * Tool registry instance
	 * @var MPAI_Tool_Registry
	 */
	private $tool_registry;
	
	/**
	 * Context manager instance
	 * @var MPAI_Context_Manager
	 */
	private $context_manager;
	
	/**
	 * OpenAI API instance
	 * @var MPAI_OpenAI
	 */
	private $openai;
	
	/**
	 * Logger instance
	 * @var object
	 */
	private $logger;
	
	/**
	 * Registered agents
	 * @var array
	 */
	private $registered_agents = [];
	
	/**
	 * Available tools
	 * @var array
	 */
	private $available_tools = [];
	
	/**
	 * Error message
	 * @var string
	 */
	private $error = '';
	
	/**
	 * Is initialized flag
	 * @var bool
	 */
	private $initialized = false;
	
	/**
	 * Constructor
	 *
	 * @param MPAI_Tool_Registry $tool_registry Tool registry
	 * @param MPAI_Context_Manager $context_manager Context manager
	 * @param object $logger Logger
	 */
	public function __construct( $tool_registry = null, $context_manager = null, $logger = null ) {
		$this->tool_registry = $tool_registry;
		$this->context_manager = $context_manager;
		$this->logger = $logger ?: $this->get_default_logger();
		
		// Initialize OpenAI API
		if ( class_exists( 'MPAI_OpenAI' ) ) {
			$this->openai = new MPAI_OpenAI();
		} else {
			$this->error = 'OpenAI API class not found';
			$this->logger->error( $this->error );
			return;
		}
		
		// Initialize the SDK (register default assistants)
		$this->initialized = $this->initialize();
	}
	
	/**
	 * Get default logger if none provided
	 *
	 * @return object Logger
	 */
	private function get_default_logger() {
		return (object) [
			'info'    => function( $message, $context = [] ) { error_log( 'MPAI SDK INFO: ' . $message ); },
			'warning' => function( $message, $context = [] ) { error_log( 'MPAI SDK WARNING: ' . $message ); },
			'error'   => function( $message, $context = [] ) { error_log( 'MPAI SDK ERROR: ' . $message ); },
		];
	}
	
	/**
	 * Initialize the SDK integration
	 *
	 * @return bool Success
	 */
	private function initialize() {
		try {
			// Prepare available tools from tool registry
			if ( $this->tool_registry ) {
				$this->prepare_available_tools();
			}
			
			// Everything is set up
			$this->logger->info( 'SDK integration initialized successfully' );
			return true;
		} catch ( Exception $e ) {
			$this->error = 'SDK initialization failed: ' . $e->getMessage();
			$this->logger->error( $this->error );
			return false;
		}
	}
	
	/**
	 * Check if SDK is initialized
	 *
	 * @return bool Initialization status
	 */
	public function is_initialized() {
		return $this->initialized;
	}
	
	/**
	 * Get error message
	 *
	 * @return string Error message
	 */
	public function get_error() {
		return $this->error;
	}
	
	/**
	 * Prepare tool definitions for OpenAI assistants
	 */
	private function prepare_available_tools() {
		$all_tools = $this->tool_registry->get_available_tools();
		
		foreach ( $all_tools as $tool_id => $tool_instance ) {
			// Convert tool to OpenAI function format
			$tool_definition = $this->convert_tool_to_openai_function( $tool_id, $tool_instance );
			
			if ( $tool_definition ) {
				$this->available_tools[$tool_id] = $tool_definition;
			}
		}
		
		$this->logger->info( 'Prepared ' . count( $this->available_tools ) . ' tools for OpenAI Assistants' );
	}
	
	/**
	 * Convert a tool to OpenAI function format
	 *
	 * @param string $tool_id Tool ID
	 * @param object $tool_instance Tool instance
	 * @return array Tool definition for OpenAI
	 */
	private function convert_tool_to_openai_function( $tool_id, $tool_instance ) {
		$name = $tool_instance->get_name() ?: $tool_id;
		$description = $tool_instance->get_description() ?: 'Execute ' . $name;
		
		// Get parameters from tool if available
		$parameters = method_exists( $tool_instance, 'get_parameters' ) 
			? $tool_instance->get_parameters() 
			: [
				'command' => [
					'type' => 'string',
					'description' => 'Command or parameters to execute',
				]
			];
		
		// Build function definition
		return [
			'type' => 'function',
			'function' => [
				'name' => $tool_id,
				'description' => $description,
				'parameters' => [
					'type' => 'object',
					'properties' => $parameters,
					'required' => array_keys( $parameters ),
				]
			]
		];
	}
	
	/**
	 * Register an agent with the SDK
	 *
	 * @param string $agent_id Agent ID
	 * @param MPAI_Agent $agent_instance Agent instance
	 * @return bool Success
	 */
	public function register_agent( $agent_id, $agent_instance ) {
		if ( ! $this->initialized ) {
			$this->logger->warning( 'Cannot register agent, SDK not initialized' );
			return false;
		}
		
		// Check if agent already registered
		if ( isset( $this->registered_agents[$agent_id] ) ) {
			$this->logger->warning( "Agent {$agent_id} already registered" );
			return false;
		}
		
		try {
			// Get agent capabilities and tools
			$agent_tools = $this->get_agent_tools( $agent_instance );
			
			// Store agent information
			$this->registered_agents[$agent_id] = [
				'id' => $agent_id,
				'instance' => $agent_instance,
				'name' => $agent_instance->get_name(),
				'description' => $agent_instance->get_description(),
				'capabilities' => $agent_instance->get_capabilities(),
				'tools' => $agent_tools,
				'assistant_id' => get_option( "mpai_assistant_id_{$agent_id}", '' )
			];
			
			// If we don't have an assistant ID yet, create it
			if ( empty( $this->registered_agents[$agent_id]['assistant_id'] ) ) {
				$assistant_id = $this->create_openai_assistant( $agent_id, $agent_instance, $agent_tools );
				
				if ( $assistant_id ) {
					$this->registered_agents[$agent_id]['assistant_id'] = $assistant_id;
					update_option( "mpai_assistant_id_{$agent_id}", $assistant_id );
				}
			}
			
			$this->logger->info( "Agent {$agent_id} registered successfully" );
			return true;
		} catch ( Exception $e ) {
			$this->logger->error( "Failed to register agent {$agent_id}: " . $e->getMessage() );
			return false;
		}
	}
	
	/**
	 * Check if agent is registered
	 *
	 * @param string $agent_id Agent ID
	 * @return bool Is registered
	 */
	public function is_agent_registered( $agent_id ) {
		return isset( $this->registered_agents[$agent_id] );
	}
	
	/**
	 * Get tools for an agent
	 *
	 * @param MPAI_Agent $agent_instance Agent instance
	 * @return array Tool definitions
	 */
	private function get_agent_tools( $agent_instance ) {
		$agent_tools = [];
		
		// Get capabilities from agent
		$capabilities = $agent_instance->get_capabilities();
		
		// Map capabilities to tools
		foreach ( $this->available_tools as $tool_id => $tool_definition ) {
			// Include all tools for agent
			$agent_tools[] = $tool_definition;
		}
		
		// Always include function_call capability
		return $agent_tools;
	}
	
	/**
	 * Create an OpenAI Assistant for an agent
	 *
	 * @param string $agent_id Agent ID
	 * @param MPAI_Agent $agent_instance Agent instance
	 * @param array $agent_tools Agent tools
	 * @return string|false Assistant ID or false on failure
	 */
	private function create_openai_assistant( $agent_id, $agent_instance, $agent_tools ) {
		try {
			// Build OpenAI API request
			$endpoint = 'https://api.openai.com/v1/assistants';
			
			$request_body = [
				'name' => $agent_instance->get_name(),
				'description' => $agent_instance->get_description(),
				'model' => get_option( 'mpai_model', 'gpt-4o' ),
				'instructions' => $this->build_assistant_instructions( $agent_id, $agent_instance ),
				'tools' => $agent_tools,
				'metadata' => [
					'agent_id' => $agent_id,
					'plugin' => 'memberpress_ai_assistant',
				]
			];
			
			// Send request to OpenAI
			$api_key = get_option( 'mpai_api_key', '' );
			
			$response = wp_remote_post(
				$endpoint,
				[
					'headers' => [
						'Authorization' => 'Bearer ' . $api_key,
						'Content-Type' => 'application/json',
						'OpenAI-Beta' => 'assistants=v1'
					],
					'body' => json_encode( $request_body ),
					'timeout' => 30,
				]
			);
			
			// Handle response
			if ( is_wp_error( $response ) ) {
				$this->logger->error( 'Failed to create assistant: ' . $response->get_error_message() );
				return false;
			}
			
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			
			if ( isset( $body['id'] ) ) {
				$this->logger->info( "Created assistant for {$agent_id} with ID: " . $body['id'] );
				return $body['id'];
			} else {
				$error = isset( $body['error'] ) ? $body['error']['message'] : 'Unknown error';
				$this->logger->error( "Failed to create assistant: " . $error );
				return false;
			}
		} catch ( Exception $e ) {
			$this->logger->error( 'Error creating assistant: ' . $e->getMessage() );
			return false;
		}
	}
	
	/**
	 * Build instructions for an OpenAI Assistant
	 *
	 * @param string $agent_id Agent ID
	 * @param MPAI_Agent $agent_instance Agent instance
	 * @return string Instructions
	 */
	private function build_assistant_instructions( $agent_id, $agent_instance ) {
		$instructions = "You are " . $agent_instance->get_name() . ", an AI assistant specialized in MemberPress functionality. ";
		$instructions .= $agent_instance->get_description() . "\n\n";
		
		// Add capabilities
		$capabilities = $agent_instance->get_capabilities();
		if ( ! empty( $capabilities ) ) {
			$instructions .= "You have the following capabilities:\n";
			
			foreach ( $capabilities as $capability_id => $capability_desc ) {
				$instructions .= "- {$capability_desc}\n";
			}
			
			$instructions .= "\n";
		}
		
		// Add tool usage guidance
		$instructions .= "When handling requests, use the available tools to perform actions. ";
		$instructions .= "Provide clear and helpful responses based on the tool outputs.\n\n";
		
		// Add MemberPress-specific guidance
		$instructions .= "Important guidelines:\n";
		$instructions .= "1. Always follow WordPress and MemberPress best practices.\n";
		$instructions .= "2. For any MemberPress-specific functionality, use the relevant tools.\n";
		$instructions .= "3. If you're unsure about certain details, ask clarifying questions.\n";
		$instructions .= "4. Provide step-by-step explanations for complex tasks.\n";
		
		return $instructions;
	}
	
	/**
	 * Process a user request using OpenAI Assistants API
	 *
	 * @param string $user_message User message
	 * @param int $user_id User ID
	 * @param array $user_context User context
	 * @return array Response data
	 */
	public function process_request( $user_message, $user_id = null, $user_context = [] ) {
		if ( ! $this->initialized ) {
			throw new Exception( 'SDK not initialized' );
		}
		
		// Determine which agent to use
		$agent_id = $this->determine_agent_for_message( $user_message, $user_context );
		
		if ( ! isset( $this->registered_agents[$agent_id] ) ) {
			throw new Exception( "Agent {$agent_id} not found or not registered" );
		}
		
		$agent_data = $this->registered_agents[$agent_id];
		
		// Check if we have an assistant ID
		if ( empty( $agent_data['assistant_id'] ) ) {
			throw new Exception( "No assistant ID found for agent {$agent_id}" );
		}
		
		try {
			// Create or retrieve a thread for this user
			$thread_id = $this->get_user_thread( $user_id );
			
			// Add the user message to the thread
			$this->add_message_to_thread( $thread_id, $user_message, 'user' );
			
			// Run the assistant on the thread
			$run_id = $this->run_assistant_on_thread( $thread_id, $agent_data['assistant_id'] );
			
			// Wait for the run to complete and process tools
			$this->process_run( $thread_id, $run_id );
			
			// Get the assistant's response
			$response = $this->get_assistant_response( $thread_id );
			
			// Process response and return formatted result
			return [
				'success' => true,
				'message' => $response,
				'agent' => $agent_id,
				'data' => [
					'thread_id' => $thread_id,
					'run_id' => $run_id,
				]
			];
		} catch ( Exception $e ) {
			$this->logger->error( "Error processing request: " . $e->getMessage() );
			
			return [
				'success' => false,
				'message' => "Sorry, I encountered an error: " . $e->getMessage(),
				'error' => $e->getMessage(),
			];
		}
	}
	
	/**
	 * Determine the appropriate agent for a message
	 *
	 * @param string $message User message
	 * @param array $context User context
	 * @return string Agent ID
	 */
	private function determine_agent_for_message( $message, $context = [] ) {
		// For now, always return the first registered agent
		// In a more complex implementation, this would analyze the message
		$agent_ids = array_keys( $this->registered_agents );
		
		if ( empty( $agent_ids ) ) {
			return 'memberpress'; // Default fallback
		}
		
		return $agent_ids[0];
	}
	
	/**
	 * Get or create a thread for a user
	 *
	 * @param int $user_id User ID
	 * @return string Thread ID
	 */
	private function get_user_thread( $user_id ) {
		// If we have a thread ID stored for this user, use it
		if ( $user_id ) {
			$thread_id = get_user_meta( $user_id, 'mpai_thread_id', true );
			
			if ( ! empty( $thread_id ) ) {
				return $thread_id;
			}
		}
		
		// Otherwise create a new thread
		$thread_id = $this->create_new_thread();
		
		// Store thread ID for this user if provided
		if ( $user_id ) {
			update_user_meta( $user_id, 'mpai_thread_id', $thread_id );
		}
		
		return $thread_id;
	}
	
	/**
	 * Create a new thread
	 *
	 * @return string Thread ID
	 */
	private function create_new_thread() {
		try {
			$endpoint = 'https://api.openai.com/v1/threads';
			
			$response = wp_remote_post(
				$endpoint,
				[
					'headers' => [
						'Authorization' => 'Bearer ' . get_option( 'mpai_api_key', '' ),
						'Content-Type' => 'application/json',
						'OpenAI-Beta' => 'assistants=v1'
					],
					'body' => json_encode( [] ),  // Empty body for default thread
					'timeout' => 30,
				]
			);
			
			if ( is_wp_error( $response ) ) {
				throw new Exception( 'Failed to create thread: ' . $response->get_error_message() );
			}
			
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			
			if ( isset( $body['id'] ) ) {
				$this->logger->info( "Created new thread with ID: " . $body['id'] );
				return $body['id'];
			} else {
				$error = isset( $body['error'] ) ? $body['error']['message'] : 'Unknown error';
				throw new Exception( "Failed to create thread: " . $error );
			}
		} catch ( Exception $e ) {
			$this->logger->error( 'Error creating thread: ' . $e->getMessage() );
			throw $e;
		}
	}
	
	/**
	 * Add a message to a thread
	 *
	 * @param string $thread_id Thread ID
	 * @param string $content Message content
	 * @param string $role Message role (user or assistant)
	 * @return string Message ID
	 */
	private function add_message_to_thread( $thread_id, $content, $role = 'user' ) {
		try {
			$endpoint = "https://api.openai.com/v1/threads/{$thread_id}/messages";
			
			$response = wp_remote_post(
				$endpoint,
				[
					'headers' => [
						'Authorization' => 'Bearer ' . get_option( 'mpai_api_key', '' ),
						'Content-Type' => 'application/json',
						'OpenAI-Beta' => 'assistants=v1'
					],
					'body' => json_encode( [
						'role' => $role,
						'content' => $content,
					] ),
					'timeout' => 30,
				]
			);
			
			if ( is_wp_error( $response ) ) {
				throw new Exception( 'Failed to add message: ' . $response->get_error_message() );
			}
			
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			
			if ( isset( $body['id'] ) ) {
				$this->logger->info( "Added message to thread {$thread_id}" );
				return $body['id'];
			} else {
				$error = isset( $body['error'] ) ? $body['error']['message'] : 'Unknown error';
				throw new Exception( "Failed to add message: " . $error );
			}
		} catch ( Exception $e ) {
			$this->logger->error( 'Error adding message: ' . $e->getMessage() );
			throw $e;
		}
	}
	
	/**
	 * Run an assistant on a thread
	 *
	 * @param string $thread_id Thread ID
	 * @param string $assistant_id Assistant ID
	 * @return string Run ID
	 */
	private function run_assistant_on_thread( $thread_id, $assistant_id ) {
		try {
			$endpoint = "https://api.openai.com/v1/threads/{$thread_id}/runs";
			
			$response = wp_remote_post(
				$endpoint,
				[
					'headers' => [
						'Authorization' => 'Bearer ' . get_option( 'mpai_api_key', '' ),
						'Content-Type' => 'application/json',
						'OpenAI-Beta' => 'assistants=v1'
					],
					'body' => json_encode( [
						'assistant_id' => $assistant_id,
					] ),
					'timeout' => 30,
				]
			);
			
			if ( is_wp_error( $response ) ) {
				throw new Exception( 'Failed to start run: ' . $response->get_error_message() );
			}
			
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			
			if ( isset( $body['id'] ) ) {
				$this->logger->info( "Started run {$body['id']} on thread {$thread_id}" );
				return $body['id'];
			} else {
				$error = isset( $body['error'] ) ? $body['error']['message'] : 'Unknown error';
				throw new Exception( "Failed to start run: " . $error );
			}
		} catch ( Exception $e ) {
			$this->logger->error( 'Error starting run: ' . $e->getMessage() );
			throw $e;
		}
	}
	
	/**
	 * Process a run, handling tool calls
	 *
	 * @param string $thread_id Thread ID
	 * @param string $run_id Run ID
	 * @return array Run information
	 */
	private function process_run( $thread_id, $run_id ) {
		$max_retries = 60; // 5 minutes total with 5-second retry intervals
		$retries = 0;
		
		while ( $retries < $max_retries ) {
			try {
				// Get run status
				$run_status = $this->get_run_status( $thread_id, $run_id );
				
				$this->logger->info( "Run status: " . $run_status['status'] );
				
				// Check if run is complete
				if ( $run_status['status'] === 'completed' ) {
					return $run_status;
				}
				
				// Check if run requires action (tool calls)
				if ( $run_status['status'] === 'requires_action' ) {
					$this->handle_tool_calls( $thread_id, $run_id, $run_status );
					// Continue polling after handling tools
				}
				
				// Check for failed or canceled status
				if ( in_array( $run_status['status'], [ 'failed', 'cancelled', 'expired' ] ) ) {
					$error = isset( $run_status['last_error'] ) ? $run_status['last_error'] : 'Unknown error';
					throw new Exception( "Run failed: " . $error );
				}
				
				// Wait before checking again
				sleep( 5 );
				$retries++;
			} catch ( Exception $e ) {
				$this->logger->error( 'Error processing run: ' . $e->getMessage() );
				throw $e;
			}
		}
		
		throw new Exception( 'Run timed out after ' . ( $max_retries * 5 ) . ' seconds' );
	}
	
	/**
	 * Get the status of a run
	 *
	 * @param string $thread_id Thread ID
	 * @param string $run_id Run ID
	 * @return array Run information
	 */
	private function get_run_status( $thread_id, $run_id ) {
		try {
			$endpoint = "https://api.openai.com/v1/threads/{$thread_id}/runs/{$run_id}";
			
			$response = wp_remote_get(
				$endpoint,
				[
					'headers' => [
						'Authorization' => 'Bearer ' . get_option( 'mpai_api_key', '' ),
						'Content-Type' => 'application/json',
						'OpenAI-Beta' => 'assistants=v1'
					],
					'timeout' => 30,
				]
			);
			
			if ( is_wp_error( $response ) ) {
				throw new Exception( 'Failed to get run status: ' . $response->get_error_message() );
			}
			
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			
			if ( isset( $body['status'] ) ) {
				return $body;
			} else {
				$error = isset( $body['error'] ) ? $body['error']['message'] : 'Unknown error';
				throw new Exception( "Failed to get run status: " . $error );
			}
		} catch ( Exception $e ) {
			$this->logger->error( 'Error getting run status: ' . $e->getMessage() );
			throw $e;
		}
	}
	
	/**
	 * Handle tool calls from a run
	 *
	 * @param string $thread_id Thread ID
	 * @param string $run_id Run ID
	 * @param array $run_status Run status
	 */
	private function handle_tool_calls( $thread_id, $run_id, $run_status ) {
		try {
			if ( ! isset( $run_status['required_action'] ) || 
				 ! isset( $run_status['required_action']['submit_tool_outputs'] ) ||
				 ! isset( $run_status['required_action']['submit_tool_outputs']['tool_calls'] ) ) {
				throw new Exception( 'No tool calls found in run status' );
			}
			
			$tool_calls = $run_status['required_action']['submit_tool_outputs']['tool_calls'];
			$tool_outputs = [];
			
			foreach ( $tool_calls as $tool_call ) {
				$call_id = $tool_call['id'];
				$function_name = $tool_call['function']['name'];
				$function_args = json_decode( $tool_call['function']['arguments'], true );
				
				$this->logger->info( "Processing tool call: {$function_name}" );
				
				// Execute the tool
				$output = $this->execute_tool( $function_name, $function_args );
				
				// Format the output
				$tool_outputs[] = [
					'tool_call_id' => $call_id,
					'output' => json_encode( $output ),
				];
			}
			
			// Submit tool outputs
			$this->submit_tool_outputs( $thread_id, $run_id, $tool_outputs );
		} catch ( Exception $e ) {
			$this->logger->error( 'Error handling tool calls: ' . $e->getMessage() );
			throw $e;
		}
	}
	
	/**
	 * Execute a tool by name
	 *
	 * @param string $tool_name Tool name
	 * @param array $args Tool arguments
	 * @return mixed Tool output
	 */
	private function execute_tool( $tool_name, $args ) {
		try {
			// Special handling for common commands that might fail in browser environment
			if ($tool_name === 'wpcli') {
				// Use WordPress API tool for common operations
				try {
					// Check if request is to create a post
					if (isset($args['command']) && strpos($args['command'], 'wp post create') !== false) {
						if (strpos($args['command'], '--post_type=page') !== false) {
							return $this->use_wp_api_tool('create_page', $args);
						} else {
							return $this->use_wp_api_tool('create_post', $args);
						}
					}
					
					// Check if request is to get a post/page
					if (isset($args['command']) && strpos($args['command'], 'wp post get') !== false) {
						return $this->use_wp_api_tool('get_post', $args);
					}
					
					// Check if request is for user operations
					if (isset($args['command']) && strpos($args['command'], 'wp user list') !== false) {
						return $this->use_wp_api_tool('get_users', $args);
					}
					
					// Check if request is for MemberPress-specific operations
					if (isset($args['command']) && strpos($args['command'], 'wp mepr-membership list') !== false) {
						return $this->use_wp_api_tool('get_memberships', $args);
					} else if (isset($args['command']) && strpos($args['command'], 'wp mepr-membership create') !== false) {
						return $this->use_wp_api_tool('create_membership', $args);
					} else if (isset($args['command']) && strpos($args['command'], 'wp mepr-transaction list') !== false) {
						return $this->use_wp_api_tool('get_transactions', $args);
					} else if (isset($args['command']) && strpos($args['command'], 'wp mepr-subscription list') !== false) {
						return $this->use_wp_api_tool('get_subscriptions', $args);
					}
				} catch (Exception $e) {
					$this->logger->warning('Failed to use WordPress API tool: ' . $e->getMessage());
					// Fall back to direct implementations below
				}
				
				// Fall back to direct implementations if WP API tool fails
				// Create post
				if (isset($args['command']) && strpos($args['command'], 'wp post create') !== false) {
					return $this->execute_wp_post_create($args);
				}
				
				// Create page
				if (isset($args['command']) && strpos($args['command'], 'wp post create --post_type=page') !== false) {
					$args['post_type'] = 'page';
					return $this->execute_wp_post_create($args);
				}
				
				// MemberPress operations
				if (isset($args['command']) && strpos($args['command'], 'wp mepr') !== false) {
					return $this->execute_memberpress_operation($args);
				}
			}
			
			// Check if tool exists in registry
			$tool_instance = $this->tool_registry->get_tool( $tool_name );
			
			if ( ! $tool_instance ) {
				return "Error: Tool {$tool_name} not found";
			}
			
			// Execute the tool
			$result = $tool_instance->execute( $args );
			
			return $result;
		} catch ( Exception $e ) {
			$this->logger->error( "Error executing tool {$tool_name}: " . $e->getMessage() );
			return "Error executing tool: " . $e->getMessage();
		}
	}
	
	/**
	 * Use WordPress API tool for operations
	 *
	 * @param string $action The action to perform
	 * @param array $args Command arguments
	 * @return string|array Result message
	 */
	private function use_wp_api_tool($action, $args) {
		// Get the WordPress API tool
		$wp_api_tool = $this->tool_registry->get_tool('wp_api');
		
		if (!$wp_api_tool) {
			throw new Exception('WordPress API tool not available');
		}
		
		$command = isset($args['command']) ? $args['command'] : '';
		$parameters = ['action' => $action];
		
		// Parse parameters based on action
		switch ($action) {
			case 'create_post':
			case 'create_page':
				// Extract post title
				preg_match('/--post_title=[\'"]([^\'"]+)[\'"]/', $command, $title_matches);
				$parameters['title'] = isset($title_matches[1]) ? $title_matches[1] : 'New Post';
				
				// Extract post content
				preg_match('/--post_content=[\'"]([^\'"]+)[\'"]/', $command, $content_matches);
				$parameters['content'] = isset($content_matches[1]) ? $content_matches[1] : '';
				
				// Extract post status
				preg_match('/--post_status=[\'"]?([^\'" ]+)[\'"]?/', $command, $status_matches);
				$parameters['status'] = isset($status_matches[1]) ? $status_matches[1] : 'draft';
				
				// Set post type for page
				if ($action === 'create_page') {
					$parameters['post_type'] = 'page';
				}
				break;
				
			case 'get_post':
				// Extract post ID
				preg_match('/wp post get (\d+)/', $command, $id_matches);
				if (isset($id_matches[1])) {
					$parameters['post_id'] = intval($id_matches[1]);
				} else {
					throw new Exception('Post ID not found in command');
				}
				break;
				
			case 'get_users':
				// Extract limit if present
				preg_match('/--limit=(\d+)/', $command, $limit_matches);
				if (isset($limit_matches[1])) {
					$parameters['limit'] = intval($limit_matches[1]);
				}
				
				// Extract role if present
				preg_match('/--role=([a-z]+)/', $command, $role_matches);
				if (isset($role_matches[1])) {
					$parameters['role'] = $role_matches[1];
				}
				break;
				
			case 'get_memberships':
				// Extract limit if present
				preg_match('/--limit=(\d+)/', $command, $limit_matches);
				if (isset($limit_matches[1])) {
					$parameters['limit'] = intval($limit_matches[1]);
				}
				break;
				
			case 'create_membership':
				// Extract membership title
				preg_match('/--name=[\'"]([^\'"]+)[\'"]/', $command, $name_matches);
				$parameters['title'] = isset($name_matches[1]) ? $name_matches[1] : 'New Membership';
				
				// Extract price
				preg_match('/--price=([0-9.]+)/', $command, $price_matches);
				$parameters['price'] = isset($price_matches[1]) ? floatval($price_matches[1]) : 9.99;
				
				// Extract period
				preg_match('/--period=([a-z]+)/', $command, $period_matches);
				$parameters['period_type'] = isset($period_matches[1]) ? $period_matches[1] : 'month';
				
				// Set billing type
				$parameters['billing_type'] = 'recurring';
				break;
				
			case 'get_transactions':
			case 'get_subscriptions':
				// Extract limit if present
				preg_match('/--limit=(\d+)/', $command, $limit_matches);
				if (isset($limit_matches[1])) {
					$parameters['limit'] = intval($limit_matches[1]);
				}
				
				// Extract status if present
				preg_match('/--status=([a-z]+)/', $command, $status_matches);
				if (isset($status_matches[1])) {
					$parameters['status'] = $status_matches[1];
				}
				break;
		}
		
		// Execute the tool
		$result = $wp_api_tool->execute($parameters);
		
		// Format results as needed
		if (is_array($result) && isset($result['message'])) {
			return $result['message'];
		} elseif (is_array($result)) {
			return json_encode($result, JSON_PRETTY_PRINT);
		} else {
			return $result;
		}
	}
	
	/**
	 * Execute WordPress post creation directly
	 *
	 * @param array $args Command arguments
	 * @return string Result message
	 */
	private function execute_wp_post_create($args) {
		$command = $args['command'];
		
		// Extract post title
		preg_match('/--post_title=[\'"]([^\'"]+)[\'"]/', $command, $title_matches);
		$title = isset($title_matches[1]) ? $title_matches[1] : 'New Post';
		
		// Extract post content
		preg_match('/--post_content=[\'"]([^\'"]+)[\'"]/', $command, $content_matches);
		$content = isset($content_matches[1]) ? $content_matches[1] : '';
		
		// Extract post status
		preg_match('/--post_status=[\'"]?([^\'" ]+)[\'"]?/', $command, $status_matches);
		$status = isset($status_matches[1]) ? $status_matches[1] : 'draft';
		
		// Extract post type
		$post_type = isset($args['post_type']) ? $args['post_type'] : 'post';
		
		// Create post
		$post_data = array(
			'post_title'    => $title,
			'post_content'  => $content,
			'post_status'   => $status,
			'post_type'     => $post_type,
		);
		
		$post_id = wp_insert_post($post_data);
		
		if (is_wp_error($post_id)) {
			return "Error creating post: " . $post_id->get_error_message();
		}
		
		$post_url = get_edit_post_link($post_id, '');
		
		return "Successfully created {$post_type} with ID {$post_id}. You can edit it here: {$post_url}";
	}
	
	/**
	 * Execute MemberPress operations directly
	 *
	 * @param array $args Command arguments
	 * @return string|array Result message
	 */
	private function execute_memberpress_operation($args) {
		$command = $args['command'];
		
		// Check if MemberPress is active
		if (!class_exists('MeprOptions')) {
			return "Error: MemberPress is not active or not installed.";
		}
		
		// Handle different MemberPress operations
		if (strpos($command, 'wp mepr-membership list') !== false) {
			// List memberships
			return $this->get_memberpress_memberships();
		} elseif (strpos($command, 'wp mepr-transaction list') !== false) {
			// List transactions
			return $this->get_memberpress_transactions();
		} elseif (strpos($command, 'wp mepr-subscription list') !== false) {
			// List subscriptions
			return $this->get_memberpress_subscriptions();
		} elseif (strpos($command, 'wp mepr-membership create') !== false) {
			// Create membership
			return $this->create_memberpress_membership($command);
		}
		
		return "The requested MemberPress operation could not be executed directly. Please try a different approach.";
	}
	
	/**
	 * Get MemberPress memberships
	 *
	 * @return string Formatted memberships list
	 */
	private function get_memberpress_memberships() {
		$args = array(
			'post_type' => 'memberpressproduct',
			'posts_per_page' => -1,
			'post_status' => 'publish'
		);
		
		$memberships = get_posts($args);
		
		if (empty($memberships)) {
			return "No memberships found.";
		}
		
		$output = "ID\tTitle\tPrice\tStatus\n";
		
		foreach ($memberships as $membership) {
			$mepr_options = MeprOptions::fetch();
			$product = new MeprProduct($membership->ID);
			$price = $product->price;
			$status = $membership->post_status;
			
			$output .= "{$membership->ID}\t{$membership->post_title}\t{$mepr_options->currency_symbol}{$price}\t{$status}\n";
		}
		
		return $output;
	}
	
	/**
	 * Get MemberPress transactions
	 *
	 * @return string Formatted transactions list
	 */
	private function get_memberpress_transactions() {
		global $wpdb;
		$mepr_db = new MeprDb();
		
		$transactions = $wpdb->get_results(
			"SELECT id, user_id, product_id, amount, status, created_at
			 FROM {$mepr_db->transactions}
			 ORDER BY created_at DESC
			 LIMIT 20"
		);
		
		if (empty($transactions)) {
			return "No transactions found.";
		}
		
		$output = "ID\tUser\tMembership\tAmount\tStatus\tDate\n";
		
		foreach ($transactions as $txn) {
			$user = get_user_by('id', $txn->user_id);
			$username = $user ? $user->user_email : "User #{$txn->user_id}";
			
			$membership = get_post($txn->product_id);
			$membership_title = $membership ? $membership->post_title : "Product #{$txn->product_id}";
			
			$mepr_options = MeprOptions::fetch();
			$amount = $mepr_options->currency_symbol . $txn->amount;
			$date = date('Y-m-d', strtotime($txn->created_at));
			
			$output .= "{$txn->id}\t{$username}\t{$membership_title}\t{$amount}\t{$txn->status}\t{$date}\n";
		}
		
		return $output;
	}
	
	/**
	 * Get MemberPress subscriptions
	 *
	 * @return string Formatted subscriptions list
	 */
	private function get_memberpress_subscriptions() {
		global $wpdb;
		$mepr_db = new MeprDb();
		
		$subscriptions = $wpdb->get_results(
			"SELECT id, user_id, product_id, status, created_at
			 FROM {$mepr_db->subscriptions}
			 ORDER BY created_at DESC
			 LIMIT 20"
		);
		
		if (empty($subscriptions)) {
			return "No subscriptions found.";
		}
		
		$output = "ID\tUser\tMembership\tStatus\tDate\n";
		
		foreach ($subscriptions as $sub) {
			$user = get_user_by('id', $sub->user_id);
			$username = $user ? $user->user_email : "User #{$sub->user_id}";
			
			$membership = get_post($sub->product_id);
			$membership_title = $membership ? $membership->post_title : "Product #{$sub->product_id}";
			
			$date = date('Y-m-d', strtotime($sub->created_at));
			
			$output .= "{$sub->id}\t{$username}\t{$membership_title}\t{$sub->status}\t{$date}\n";
		}
		
		return $output;
	}
	
	/**
	 * Create MemberPress membership
	 *
	 * @param string $command Command to parse
	 * @return string Result message
	 */
	private function create_memberpress_membership($command) {
		// Extract membership title
		preg_match('/--name=[\'"]([^\'"]+)[\'"]/', $command, $name_matches);
		$title = isset($name_matches[1]) ? $name_matches[1] : 'New Membership';
		
		// Extract price
		preg_match('/--price=([0-9.]+)/', $command, $price_matches);
		$price = isset($price_matches[1]) ? floatval($price_matches[1]) : 9.99;
		
		// Extract period
		preg_match('/--period=([a-z]+)/', $command, $period_matches);
		$period = isset($period_matches[1]) ? $period_matches[1] : 'month';
		
		// Create membership
		$post_data = array(
			'post_title'    => $title,
			'post_content'  => '',
			'post_status'   => 'publish',
			'post_type'     => 'memberpressproduct',
		);
		
		$product_id = wp_insert_post($post_data);
		
		if (is_wp_error($product_id)) {
			return "Error creating membership: " . $product_id->get_error_message();
		}
		
		// Set product meta
		update_post_meta($product_id, '_mepr_product_price', $price);
		update_post_meta($product_id, '_mepr_billing_type', 'recurring');
		update_post_meta($product_id, '_mepr_product_period', 1);
		update_post_meta($product_id, '_mepr_product_period_type', $period);
		
		$edit_url = admin_url("post.php?post={$product_id}&action=edit");
		
		return "Successfully created membership '{$title}' with ID {$product_id} at price {$price} per {$period}. You can edit it here: {$edit_url}";
	}
	
	/**
	 * Submit tool outputs back to OpenAI
	 *
	 * @param string $thread_id Thread ID
	 * @param string $run_id Run ID
	 * @param array $tool_outputs Tool outputs
	 */
	private function submit_tool_outputs( $thread_id, $run_id, $tool_outputs ) {
		try {
			$endpoint = "https://api.openai.com/v1/threads/{$thread_id}/runs/{$run_id}/submit_tool_outputs";
			
			$response = wp_remote_post(
				$endpoint,
				[
					'headers' => [
						'Authorization' => 'Bearer ' . get_option( 'mpai_api_key', '' ),
						'Content-Type' => 'application/json',
						'OpenAI-Beta' => 'assistants=v1'
					],
					'body' => json_encode( [
						'tool_outputs' => $tool_outputs,
					] ),
					'timeout' => 30,
				]
			);
			
			if ( is_wp_error( $response ) ) {
				throw new Exception( 'Failed to submit tool outputs: ' . $response->get_error_message() );
			}
			
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			
			if ( isset( $body['id'] ) ) {
				$this->logger->info( "Submitted tool outputs for run {$run_id}" );
				return true;
			} else {
				$error = isset( $body['error'] ) ? $body['error']['message'] : 'Unknown error';
				throw new Exception( "Failed to submit tool outputs: " . $error );
			}
		} catch ( Exception $e ) {
			$this->logger->error( 'Error submitting tool outputs: ' . $e->getMessage() );
			throw $e;
		}
	}
	
	/**
	 * Get the assistant's response from a thread
	 *
	 * @param string $thread_id Thread ID
	 * @return string Assistant response
	 */
	private function get_assistant_response( $thread_id ) {
		try {
			$endpoint = "https://api.openai.com/v1/threads/{$thread_id}/messages";
			
			$response = wp_remote_get(
				$endpoint,
				[
					'headers' => [
						'Authorization' => 'Bearer ' . get_option( 'mpai_api_key', '' ),
						'Content-Type' => 'application/json',
						'OpenAI-Beta' => 'assistants=v1'
					],
					'timeout' => 30,
				]
			);
			
			if ( is_wp_error( $response ) ) {
				throw new Exception( 'Failed to get messages: ' . $response->get_error_message() );
			}
			
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			
			if ( isset( $body['data'] ) && is_array( $body['data'] ) ) {
				// Get the first assistant message (should be the most recent)
				foreach ( $body['data'] as $message ) {
					if ( $message['role'] === 'assistant' ) {
						// Extract the content
						if ( isset( $message['content'][0]['text']['value'] ) ) {
							return $message['content'][0]['text']['value'];
						}
					}
				}
				
				return "No assistant response found";
			} else {
				$error = isset( $body['error'] ) ? $body['error']['message'] : 'Unknown error';
				throw new Exception( "Failed to get messages: " . $error );
			}
		} catch ( Exception $e ) {
			$this->logger->error( 'Error getting assistant response: ' . $e->getMessage() );
			throw $e;
		}
	}
	
	/**
	 * Handle agent handoff between different specialized agents
	 *
	 * @param string $from_agent_id Source agent ID
	 * @param string $to_agent_id Target agent ID
	 * @param string $message User message to handoff
	 * @param array $context Context data
	 * @return array Handoff result
	 */
	public function handle_agent_handoff( $from_agent_id, $to_agent_id, $message, $context = [] ) {
		if ( ! $this->initialized ) {
			throw new Exception( 'SDK not initialized' );
		}
		
		// Check if both agents are registered
		if ( ! isset( $this->registered_agents[$from_agent_id] ) ) {
			throw new Exception( "Source agent {$from_agent_id} not registered" );
		}
		
		if ( ! isset( $this->registered_agents[$to_agent_id] ) ) {
			throw new Exception( "Target agent {$to_agent_id} not registered" );
		}
		
		try {
			// Get the assistant IDs
			$from_assistant_id = $this->registered_agents[$from_agent_id]['assistant_id'];
			$to_assistant_id = $this->registered_agents[$to_agent_id]['assistant_id'];
			
			// Get or create a thread for this context
			$thread_id = isset( $context['thread_id'] ) ? $context['thread_id'] : $this->create_new_thread();
			
			// Add a handoff message to the thread
			$handoff_message = "Handoff from {$from_agent_id} to {$to_agent_id}: {$message}";
			$this->add_message_to_thread( $thread_id, $handoff_message, 'user' );
			
			// Run the target assistant on the thread
			$run_id = $this->run_assistant_on_thread( $thread_id, $to_assistant_id );
			
			// Wait for the run to complete and process tools
			$this->process_run( $thread_id, $run_id );
			
			// Get the assistant's response
			$response = $this->get_assistant_response( $thread_id );
			
			// Return formatted result
			return [
				'success' => true,
				'agent' => $to_agent_id,
				'message' => $response,
				'data' => [
					'thread_id' => $thread_id,
					'run_id' => $run_id,
					'from_agent' => $from_agent_id,
				]
			];
		} catch ( Exception $e ) {
			$this->logger->error( "Error handling agent handoff: " . $e->getMessage() );
			
			return [
				'success' => false,
				'message' => "Handoff failed: " . $e->getMessage(),
				'error' => $e->getMessage(),
			];
		}
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
		if ( ! $this->initialized ) {
			throw new Exception( 'SDK not initialized' );
		}
		
		// Check if agent is registered
		if ( ! isset( $this->registered_agents[$agent_id] ) ) {
			throw new Exception( "Agent {$agent_id} not registered" );
		}
		
		try {
			// Generate a unique task ID
			$task_id = uniqid( 'task_' );
			
			// Create a new thread for this task
			$thread_id = $this->create_new_thread();
			
			// Add task description and parameters to the thread
			$task_message = "Task: {$task_description}\n\nParameters: " . json_encode( $parameters );
			$this->add_message_to_thread( $thread_id, $task_message, 'user' );
			
			// Schedule the task to run in the background using WP Cron
			wp_schedule_single_event( 
				time(), 
				'mpai_run_background_task', 
				[
					'thread_id' => $thread_id,
					'assistant_id' => $this->registered_agents[$agent_id]['assistant_id'],
					'task_id' => $task_id,
					'user_id' => $user_id,
				]
			);
			
			// Store task information in options
			$task_info = [
				'id' => $task_id,
				'agent_id' => $agent_id,
				'description' => $task_description,
				'parameters' => $parameters,
				'thread_id' => $thread_id,
				'status' => 'scheduled',
				'started_at' => current_time( 'mysql' ),
				'user_id' => $user_id,
			];
			
			update_option( "mpai_task_{$task_id}", $task_info );
			
			$this->logger->info( "Scheduled background task {$task_id} for agent {$agent_id}" );
			
			return [
				'success' => true,
				'task_id' => $task_id,
				'status' => 'scheduled',
				'agent' => $agent_id,
				'thread_id' => $thread_id,
			];
		} catch ( Exception $e ) {
			$this->logger->error( "Error starting running agent: " . $e->getMessage() );
			
			return [
				'success' => false,
				'message' => "Failed to start task: " . $e->getMessage(),
				'error' => $e->getMessage(),
			];
		}
	}
	
	/**
	 * Execute a background task
	 *
	 * @param string $thread_id Thread ID
	 * @param string $assistant_id Assistant ID
	 * @param string $task_id Task ID
	 * @param int $user_id User ID
	 */
	public function execute_background_task( $thread_id, $assistant_id, $task_id, $user_id = 0 ) {
		try {
			$this->logger->info( "Executing background task {$task_id}" );
			
			// Update task status
			$task_info = get_option( "mpai_task_{$task_id}", [] );
			$task_info['status'] = 'running';
			update_option( "mpai_task_{$task_id}", $task_info );
			
			// Run the assistant on the thread
			$run_id = $this->run_assistant_on_thread( $thread_id, $assistant_id );
			
			// Process the run
			$this->process_run( $thread_id, $run_id );
			
			// Get the response
			$response = $this->get_assistant_response( $thread_id );
			
			// Update task status and result
			$task_info['status'] = 'completed';
			$task_info['completed_at'] = current_time( 'mysql' );
			$task_info['result'] = $response;
			$task_info['run_id'] = $run_id;
			
			update_option( "mpai_task_{$task_id}", $task_info );
			
			// Notify user if needed (could be implemented with email, notifications, etc.)
			if ( $user_id ) {
				// Store result in user meta for retrieval
				update_user_meta( $user_id, "mpai_task_result_{$task_id}", $response );
			}
			
			$this->logger->info( "Completed background task {$task_id}" );
		} catch ( Exception $e ) {
			$this->logger->error( "Error executing background task: " . $e->getMessage() );
			
			// Update task status with error
			$task_info = get_option( "mpai_task_{$task_id}", [] );
			$task_info['status'] = 'failed';
			$task_info['error'] = $e->getMessage();
			update_option( "mpai_task_{$task_id}", $task_info );
		}
	}
	
	/**
	 * Get task status and result
	 *
	 * @param string $task_id Task ID
	 * @return array Task information
	 */
	public function get_task_info( $task_id ) {
		return get_option( "mpai_task_{$task_id}", [
			'success' => false,
			'message' => "Task {$task_id} not found",
		] );
	}
}

// Add an action hook to handle background task execution
add_action( 'mpai_run_background_task', function( $args ) {
	if ( ! is_array( $args ) ) {
		error_log( 'MPAI SDK ERROR: Invalid arguments for background task' );
		return;
	}
	
	$thread_id = isset( $args['thread_id'] ) ? $args['thread_id'] : '';
	$assistant_id = isset( $args['assistant_id'] ) ? $args['assistant_id'] : '';
	$task_id = isset( $args['task_id'] ) ? $args['task_id'] : '';
	$user_id = isset( $args['user_id'] ) ? $args['user_id'] : 0;
	
	if ( empty( $thread_id ) || empty( $assistant_id ) || empty( $task_id ) ) {
		error_log( 'MPAI SDK ERROR: Missing required arguments for background task' );
		return;
	}
	
	// Get orchestrator and have it execute the task
	$orchestrator = new MPAI_Agent_Orchestrator();
	$orchestrator->execute_background_task( $thread_id, $assistant_id, $task_id, $user_id );
}, 10, 1 );