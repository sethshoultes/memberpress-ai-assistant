<?php
/**
 * Agent Orchestrator Class
 * 
 * Handles routing and coordination between different specialized agents
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Agent Orchestrator
 */
class MPAI_Agent_Orchestrator {
	/**
	 * Instance of this class (singleton)
	 *
	 * @var MPAI_Agent_Orchestrator
	 */
	private static $instance = null;
	
	/**
	 * Whether the orchestrator has been fully initialized
	 *
	 * @var bool
	 */
	private static $initialized = false;
	
	/**
	 * Available agents
	 *
	 * @var array
	 */
	private $agents = [];
	
	/**
	 * Tool registry
	 *
	 * @var MPAI_Tool_Registry
	 */
	private $tool_registry;
	
	/**
	 * Whether the SDK integration is available
	 * 
	 * @var bool
	 */
	private $sdk_available = false;
	
	/**
	 * Whether the SDK integration is initialized
	 * 
	 * @var bool
	 */
	private $sdk_initialized = false;
	
	/**
	 * SDK integration instance
	 * 
	 * @var MPAI_SDK_Integration
	 */
	private $sdk_integration = null;
	
	/**
	 * Error recovery system instance
	 *
	 * @var MPAI_Error_Recovery
	 */
	private $error_recovery = null;
	
	/**
	 * Logger instance
	 * 
	 * @var object
	 */
	private $logger = null;
	
	/**
	 * Get instance (singleton pattern)
	 *
	 * @return MPAI_Agent_Orchestrator
	 */
	public static function get_instance() {
		if (self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	/**
	 * Constructor
	 */
	private function __construct() {
		// Initialize logger
		$this->logger = $this->get_default_logger();
		
		// Prevent duplicate initialization
		if (self::$initialized) {
			$this->logger->debug('Orchestrator already initialized, skipping');
			return;
		}
		
		// Initialize error recovery system if available
		if (class_exists('MPAI_Error_Recovery')) {
			$this->error_recovery = mpai_init_error_recovery();
			$this->logger->info('Error recovery system initialized');
		} else {
			$this->logger->warning('Error recovery system not available');
		}
		
		// Initialize the tool registry
		$this->tool_registry = new MPAI_Tool_Registry();
		
		// Register all available tools
		$this->register_tools();
		
		// Initialize the new command system
		$this->init_command_system();
		
		// Initialize SDK integration
		$this->init_sdk_integration();
		
		// Register all core agents
		$this->register_core_agents();
		
		// Mark as initialized
		self::$initialized = true;
	}
	
	/**
	 * Get default logger
	 * 
	 * @return object Default logger
	 */
	private function get_default_logger() {
		// Create a simple logger class instead of using stdClass with function properties
		// This is safer across PHP versions
		$logger = new class {
			public function info($message, $context = []) {
				error_log('MPAI ORCHESTRATOR INFO: ' . $message);
			}
			
			public function warning($message, $context = []) {
				error_log('MPAI ORCHESTRATOR WARNING: ' . $message);
			}
			
			public function error($message, $context = []) {
				error_log('MPAI ORCHESTRATOR ERROR: ' . $message);
			}
			
			public function debug($message, $context = []) {
				error_log('MPAI ORCHESTRATOR DEBUG: ' . $message);
			}
		};
		
		return $logger;
	}
	
	/**
	 * Command adapter for new command system
	 * @var object
	 */
	private $command_adapter = null;

	/**
	 * Register tools
	 */
	private function register_tools() {
		// Content Generation Tools - anticipated from roadmap
		$content_generator_path = plugin_dir_path(dirname(__FILE__)) . 'tools/implementations/class-mpai-content-generator-tool.php';
		if (file_exists($content_generator_path)) {
			$this->tool_registry->register_tool_definition(
				'content_generator',
				'MPAI_Content_Generator_Tool',
				$content_generator_path
			);
		}
		
		// Analytics Tools - anticipated from roadmap
		$analytics_path = plugin_dir_path(dirname(__FILE__)) . 'tools/implementations/class-mpai-analytics-tool.php';
		if (file_exists($analytics_path)) {
			$this->tool_registry->register_tool_definition(
				'analytics',
				'MPAI_Analytics_Tool',
				$analytics_path
			);
		}
		
		// Standard tools using lazy loading approach
		
		// Register CommandTool definition
		if (class_exists('MPAI_Command_Tool')) {
			$this->tool_registry->register_tool_definition(
				'command',
				'MPAI_Command_Tool'
			);
		}
		
		// Register WordPress Tool definition
		$wp_tool_path = plugin_dir_path(dirname(__FILE__)) . 'tools/implementations/class-mpai-wordpress-tool.php';
		if (file_exists($wp_tool_path)) {
			$this->tool_registry->register_tool_definition(
				'wordpress',
				'MPAI_WordPress_Tool',
				$wp_tool_path
			);
		}
		
		// Register Content_Tool definition
		$content_tool_path = plugin_dir_path(dirname(__FILE__)) . 'tools/implementations/class-mpai-content-tool.php';
		if (file_exists($content_tool_path)) {
			$this->tool_registry->register_tool_definition(
				'content',
				'MPAI_Content_Tool',
				$content_tool_path
			);
		}
		
		// Register MemberPress Tool definition
		$memberpress_tool_path = plugin_dir_path(dirname(__FILE__)) . 'tools/implementations/class-mpai-memberpress-tool.php';
		if (file_exists($memberpress_tool_path)) {
			$this->tool_registry->register_tool_definition(
				'memberpress',
				'MPAI_MemberPress_Tool',
				$memberpress_tool_path
			);
		}
		
		// Register Search Tool definition
		$search_tool_path = plugin_dir_path(dirname(__FILE__)) . 'tools/implementations/class-mpai-search-tool.php';
		if (file_exists($search_tool_path)) {
			$this->tool_registry->register_tool_definition(
				'search',
				'MPAI_Search_Tool',
				$search_tool_path
			);
		}
		
		// Register Embedding Tool definition
		$embedding_tool_path = plugin_dir_path(dirname(__FILE__)) . 'tools/implementations/class-mpai-embedding-tool.php';
		if (file_exists($embedding_tool_path)) {
			$this->tool_registry->register_tool_definition(
				'embed',
				'MPAI_Embedding_Tool',
				$embedding_tool_path
			);
		}
		
		// Register Security Tool definition
		$security_tool_path = plugin_dir_path(dirname(__FILE__)) . 'tools/implementations/class-mpai-security-tool.php';
		if (file_exists($security_tool_path)) {
			$this->tool_registry->register_tool_definition(
				'security',
				'MPAI_Security_Tool',
				$security_tool_path
			);
		}
		
		// Register WP-CLI Tool definition
		$wpcli_tool_path = plugin_dir_path(dirname(__FILE__)) . 'tools/implementations/class-mpai-wpcli-tool.php';
		if (file_exists($wpcli_tool_path)) {
			$this->tool_registry->register_tool_definition(
				'wpcli',
				'MPAI_WP_CLI_Tool',
				$wpcli_tool_path
			);
		}
		
		// Register WP API Tool definition
		$wp_api_tool_path = plugin_dir_path(dirname(__FILE__)) . 'tools/implementations/class-mpai-wp-api-tool.php';
		if (file_exists($wp_api_tool_path)) {
			$this->tool_registry->register_tool_definition(
				'wp_api',
				'MPAI_WP_API_Tool',
				$wp_api_tool_path
			);
		}
		
		// Register Diagnostic Tool definition
		$diagnostic_tool_path = plugin_dir_path(dirname(__FILE__)) . 'tools/implementations/class-mpai-diagnostic-tool.php';
		if (file_exists($diagnostic_tool_path)) {
			$this->tool_registry->register_tool_definition(
				'diagnostic',
				'MPAI_Diagnostic_Tool',
				$diagnostic_tool_path
			);
		}
		
		// Register Plugin Logs Tool definition
		$plugin_logs_tool_path = plugin_dir_path(dirname(__FILE__)) . 'tools/implementations/class-mpai-plugin-logs-tool.php';
		if (file_exists($plugin_logs_tool_path)) {
			$this->tool_registry->register_tool_definition(
				'plugin_logs',
				'MPAI_Plugin_Logs_Tool',
				$plugin_logs_tool_path
			);
		}
	}
	
	/**
	 * Initialize the new command system
	 */
	private function init_command_system() {
		try {
			// Check if the new command system is available
			$adapter_path = plugin_dir_path( dirname( __FILE__ ) ) . 'commands/class-mpai-command-adapter.php';
			
			if ( ! file_exists( $adapter_path ) ) {
				error_log( 'MPAI: New command system not available: ' . $adapter_path );
				return false;
			}
			
			// Include the adapter file
			require_once $adapter_path;
			
			// Check if adapter class is available
			if ( ! class_exists( 'MPAI_Command_Adapter' ) ) {
				error_log( 'MPAI: Command adapter class not found' );
				return false;
			}
			
			// Initialize the command adapter
			$this->command_adapter = new MPAI_Command_Adapter( $this->tool_registry );
			error_log( 'MPAI: Initialized command adapter' );
			
			// Register the adapter as a tool for WP-CLI commands
			if ( $this->command_adapter->register_as_tool( $this->tool_registry ) ) {
				error_log( 'MPAI: Registered command adapter as tool' );
				
				// Replace the standard WP-CLI tool with our new implementation
				// Get the new implementation and register it as the primary tools
				$wpcli_new_tool = $this->tool_registry->get_tool( 'wpcli_new' );
				if ($wpcli_new_tool) {
					$this->tool_registry->register_tool( 'wpcli', $wpcli_new_tool );
					// For backward compatibility, also register under the wp_cli name
					$this->tool_registry->register_tool( 'wp_cli', $wpcli_new_tool );
					error_log( 'MPAI: Replaced standard WP-CLI tools with new implementation' );
				} else {
					error_log( 'MPAI: Could not get wpcli_new tool' );
				}
				
				return true;
			}
			
			return false;
		} catch ( Exception $e ) {
			error_log( 'MPAI: Error initializing command system: ' . $e->getMessage() );
			return false;
		}
	}
	
	/**
	 * Initialize SDK integration
	 * 
	 * @return bool Whether initialization was successful
	 */
	private function init_sdk_integration() {
		try {
			// Check if SDK files exist
			$sdk_path = plugin_dir_path( __FILE__ ) . 'sdk/class-mpai-sdk-integration.php';
			if ( ! file_exists( $sdk_path ) ) {
				error_log( 'MPAI: Warning - SDK integration file not found: ' . $sdk_path );
				return false;
			}
			
			// Include SDK files
			require_once $sdk_path;
			
			// Check if SDK Integration class is available
			if ( class_exists( 'MPAI_SDK_Integration' ) ) {
				// Create SDK integration instance
				$this->sdk_integration = new MPAI_SDK_Integration();
				$this->sdk_available = true;
				
				// Initialize the SDK integration
				if ( $this->sdk_integration->init() ) {
					// Check if initialization was successful
					$this->sdk_initialized = $this->sdk_integration->is_initialized();
					
					if ( $this->sdk_initialized ) {
						// SDK integration initialized
						
						// Register existing agents with SDK
						foreach ( $this->agents as $agent_id => $agent_instance ) {
							$this->sdk_integration->register_agent( $agent_id, $agent_instance );
						}
					} else {
						error_log( 'MPAI: Warning - SDK integration failed to initialize: ' . $this->sdk_integration->get_error() );
					}
					
					return $this->sdk_initialized;
				} else {
					error_log( 'MPAI: Warning - SDK integration class not found' );
					return false;
				}
			}
		} catch ( Exception $e ) {
			error_log( 'MPAI: Error initializing SDK: ' . $e->getMessage() );
			return false;
		}
	}
	
	/**
	 * Register an agent
	 * 
	 * @param string $agent_id Unique agent identifier
	 * @param object $agent_instance Agent instance
	 * @return bool Success status
	 */
	public function register_agent( $agent_id, $agent_instance ) {
		if ( isset( $this->agents[$agent_id] ) ) {
			error_log( "MPAI: Warning - Agent with ID {$agent_id} already registered" );
			return false;
		}
		
		$this->agents[$agent_id] = $agent_instance;
		
		// Register with SDK if available
		if ( $this->sdk_initialized && $this->sdk_integration ) {
			try {
				$this->sdk_integration->register_agent( $agent_id, $agent_instance );
				// Agent registered with SDK
			} catch ( Exception $e ) {
				error_log( "MPAI: Warning - Failed to register agent {$agent_id} with SDK: " . $e->getMessage() );
			}
		}
		
		return true;
	}
	
	/**
	 * Process a user request and route to appropriate agent
	 * 
	 * @param string $user_message User message
	 * @param int $user_id User ID
	 * @return array Response data
	 */
	public function process_request( $user_message, $user_id = 0 ) {
		// If error recovery system is available, use it for robust error handling
		if ($this->error_recovery) {
			// Define the main processing function
			$process_func = function() use ($user_message, $user_id) {
				// Get user context
				$user_context = $this->get_user_context( $user_id );
				
				// Log the request
				error_log( "MPAI: Processing request - User ID: " . $user_id . ", Using SDK: " . ($this->sdk_initialized ? "Yes" : "No"));
				
				// If SDK is initialized, use it for processing
				if ( $this->sdk_initialized && $this->sdk_integration ) {
					return $this->process_with_sdk( $user_message, $user_id, $user_context );
				}
				
				// Otherwise use the traditional processing method
				return $this->process_with_traditional_method( $user_message, $user_id, $user_context );
			};
			
			// Define fallback processing function that uses traditional method
			$fallback_func = function() use ($user_message, $user_id) {
				$user_context = $this->get_user_context( $user_id );
				error_log("MPAI: Fallback processing with traditional method");
				return $this->process_with_traditional_method( $user_message, $user_id, $user_context );
			};
			
			// Create an error with proper context for the error recovery system
			$error = $this->error_recovery->create_agent_error(
				'orchestrator', 
				'agent_processing', 
				'Agent request processing with recovery', 
				['user_id' => $user_id]
			);
			
			// Process with error recovery
			$result = $this->error_recovery->handle_error(
				$error, 
				'request_processing', 
				$process_func, 
				[], 
				$fallback_func, 
				[]
			);
			
			// If result is a WP_Error, format it appropriately for the user
			if (is_wp_error($result)) {
				error_log("MPAI: Error recovery failed, returning formatted error");
				$error_message = $this->error_recovery->format_error_for_display($result);
				return [
					'success' => false,
					'message' => $error_message,
					'error' => $result->get_error_message(),
				];
			}
			
			return $result;
		} else {
			// Traditional error handling if error recovery is not available
			try {
				// Get user context
				$user_context = $this->get_user_context( $user_id );
				
				// Log the request
				error_log( "MPAI: Processing request - User ID: " . $user_id . ", Using SDK: " . ($this->sdk_initialized ? "Yes" : "No"));
				
				// If SDK is initialized, use it for processing
				if ( $this->sdk_initialized && $this->sdk_integration ) {
					return $this->process_with_sdk( $user_message, $user_id, $user_context );
				}
				
				// Otherwise use the traditional processing method
				return $this->process_with_traditional_method( $user_message, $user_id, $user_context );
			} catch ( Exception $e ) {
				error_log( "MPAI: Error processing request: " . $e->getMessage() );
				
				return [
					'success' => false,
					'message' => "Sorry, I couldn't process that request: " . $e->getMessage(),
					'error' => $e->getMessage(),
				];
			}
		}
	}
	
	/**
	 * Process request with SDK integration
	 * 
	 * @param string $user_message User message
	 * @param int $user_id User ID
	 * @param array $user_context User context data
	 * @return array Response data
	 */
	private function process_with_sdk( $user_message, $user_id = 0, $user_context = [] ) {
		try {
			// Ensure the tool registry is properly initialized and available
			$this->ensure_tool_registry();
			
			// Create intent data with tool registry information and enhanced system context
			$intent_data = [
				'message' => $user_message,
				'user_context' => $user_context,
				'tools' => $this->get_available_tools_info(), // Add available tools info
			];
			
			// Enhanced system information for better handling of system queries
			$intent_data['system_info'] = $this->get_enhanced_system_info();
			
			// Log all tools being passed to SDK
			error_log("MPAI: Passing " . count($this->tool_registry->get_available_tools()) . " tools to SDK integration");
			
			// Make sure the SDK integration has the updated tool_registry
			if (method_exists($this->sdk_integration, 'update_tool_registry')) {
				$this->sdk_integration->update_tool_registry($this->tool_registry);
				error_log("MPAI: Updated tool registry in SDK integration");
			}
			
			// Add a pre-processing step for specific queries to improve handling
			$this->preprocess_system_queries($user_message, $intent_data);
			
			// Process the request with the SDK
			$sdk_result = $this->sdk_integration->process_request( $intent_data, $user_id );
			
			// Verify the response - if it contains an error about missing tools, try recovery
			if (is_array($sdk_result) && isset($sdk_result['success']) && !$sdk_result['success'] && 
				isset($sdk_result['error']) && strpos($sdk_result['error'], 'Tool') !== false) {
				
				error_log("MPAI: SDK response indicates tool access issue, attempting recovery");
				
				// Refresh the tool registry and try again
				$this->ensure_tool_registry(true); // Force refresh
				
				if (method_exists($this->sdk_integration, 'update_tool_registry')) {
					$this->sdk_integration->update_tool_registry($this->tool_registry);
					error_log("MPAI: Re-updated tool registry in SDK integration during recovery");
				}
				
				// Try again with refreshed tools
				$sdk_result = $this->sdk_integration->process_request( $intent_data, $user_id );
			}
			
			// Update memory with results
			$this->update_memory( $user_id, ['original_message' => $user_message], $sdk_result );
			
			// Log the successful completion
			error_log( "MPAI: Successfully processed request with SDK" );
			
			return $sdk_result;
		} catch ( Exception $e ) {
			error_log( "MPAI: Error processing with SDK: " . $e->getMessage() );
			
			// Fall back to traditional method if SDK processing fails
			error_log( "MPAI: Falling back to traditional processing method" );
			return $this->process_with_traditional_method( $user_message, $user_id, $user_context );
		}
	}
	
	/**
	 * Ensure tool registry is properly initialized
	 * 
	 * @param bool $force_refresh Whether to force a refresh of the registry
	 */
	private function ensure_tool_registry($force_refresh = false) {
		if (!isset($this->tool_registry) || empty($this->tool_registry) || $force_refresh) {
			error_log("MPAI: " . ($force_refresh ? "Forcing refresh of" : "Initializing") . " tool registry");
			
			// Create a new registry if needed
			if (!isset($this->tool_registry) || $force_refresh) {
				try {
					$this->tool_registry = new MPAI_Tool_Registry();
					error_log("MPAI: Created new tool registry instance");
					
					// Store in global variable for emergency recovery
					global $mpai_tool_registry;
					$mpai_tool_registry = $this->tool_registry;
					
				} catch (Exception $e) {
					error_log("MPAI: Error creating tool registry: " . $e->getMessage());
					
					// Try to recover from existing global registry
					global $mpai_tool_registry;
					if ($mpai_tool_registry && $mpai_tool_registry instanceof MPAI_Tool_Registry) {
						$this->tool_registry = $mpai_tool_registry;
						error_log("MPAI: Recovered tool registry from global variable");
					}
				}
			}
			
			// Register all available tools
			$this->register_tools();
			
			// Verify that essential tools are available
			$available_tools = $this->tool_registry->get_available_tools();
			$essential_tools = ['wpcli', 'wp_api', 'plugin_logs'];
			$missing_tools = [];
			
			foreach ($essential_tools as $tool_id) {
				if (!isset($available_tools[$tool_id])) {
					$missing_tools[] = $tool_id;
					// Try to load the tool directly
					$this->load_tool_directly($tool_id);
				}
			}
			
			// Re-check after direct loading attempt
			if (!empty($missing_tools)) {
				$available_tools = $this->tool_registry->get_available_tools();
				$still_missing = [];
				
				foreach ($missing_tools as $tool_id) {
					if (!isset($available_tools[$tool_id])) {
						$still_missing[] = $tool_id;
					}
				}
				
				if (!empty($still_missing)) {
					error_log("MPAI: Warning - Essential tools still missing after recovery: " . implode(', ', $still_missing));
				} else {
					error_log("MPAI: Successfully recovered all missing tools");
				}
			}
			
			error_log("MPAI: Tool registry contains " . count($available_tools) . " tools");
		}
	}
	
	/**
	 * Attempt to load a tool implementation directly
	 *
	 * @param string $tool_id Tool ID to load
	 * @return bool Success status
	 */
	private function load_tool_directly($tool_id) {
		$tool_map = [
			'wpcli' => 'MPAI_WP_CLI_Tool',
			'wp_api' => 'MPAI_WP_API_Tool',
			'diagnostic' => 'MPAI_Diagnostic_Tool',
			'plugin_logs' => 'MPAI_Plugin_Logs_Tool'
		];
		
		if (!isset($tool_map[$tool_id])) {
			return false;
		}
		
		$class_name = $tool_map[$tool_id];
		
		// Check if class already exists
		if (class_exists($class_name)) {
			try {
				$tool = new $class_name();
				$this->tool_registry->register_tool($tool_id, $tool);
				error_log("MPAI: Directly registered tool {$tool_id} from existing class");
				return true;
			} catch (Exception $e) {
				error_log("MPAI: Error creating tool instance for {$tool_id}: " . $e->getMessage());
				return false;
			}
		}
		
		// Try to find and include the file
		$base_paths = [
			MPAI_PLUGIN_DIR . 'includes/tools/implementations/',
			dirname(__FILE__) . '/../tools/implementations/',
			dirname(dirname(__FILE__)) . '/tools/implementations/'
		];
		
		foreach ($base_paths as $base_path) {
			$file_path = $base_path . 'class-' . strtolower(str_replace('_', '-', $tool_id)) . '-tool.php';
			$alt_file_path = $base_path . 'class-mpai-' . strtolower(str_replace('_', '-', $tool_id)) . '-tool.php';
			
			if (file_exists($file_path)) {
				try {
					require_once $file_path;
					error_log("MPAI: Loaded tool file from: {$file_path}");
					break;
				} catch (Exception $e) {
					error_log("MPAI: Error loading tool file {$file_path}: " . $e->getMessage());
				}
			} elseif (file_exists($alt_file_path)) {
				try {
					require_once $alt_file_path;
					error_log("MPAI: Loaded tool file from: {$alt_file_path}");
					break;
				} catch (Exception $e) {
					error_log("MPAI: Error loading tool file {$alt_file_path}: " . $e->getMessage());
				}
			}
		}
		
		// Check if class now exists and create instance
		if (class_exists($class_name)) {
			try {
				$tool = new $class_name();
				$this->tool_registry->register_tool($tool_id, $tool);
				error_log("MPAI: Directly registered tool {$tool_id} after loading class file");
				return true;
			} catch (Exception $e) {
				error_log("MPAI: Error creating tool instance for {$tool_id} after loading: " . $e->getMessage());
				return false;
			}
		}
		
		return false;
	}
	
	/**
	 * Get enhanced system information for queries
	 * 
	 * @return array System information
	 */
	private function get_enhanced_system_info() {
		$system_info = [
			'php_version' => phpversion(),
			'wordpress_version' => get_bloginfo('version'),
			'is_multisite' => is_multisite(),
			'active_plugins_count' => count(get_option('active_plugins')),
			'memory_limit' => ini_get('memory_limit'),
			'max_execution_time' => ini_get('max_execution_time'),
			'upload_max_filesize' => ini_get('upload_max_filesize'),
			'post_max_size' => ini_get('post_max_size'),
		];
		
		// Add recent plugin activity info if available
		if (function_exists('mpai_init_plugin_logger')) {
			$plugin_logger = mpai_init_plugin_logger();
			if ($plugin_logger) {
				try {
					$recent_logs = $plugin_logger->get_logs([
						'limit' => 5,
						'orderby' => 'date_time',
						'order' => 'DESC',
					]);
					
					if (!empty($recent_logs)) {
						$system_info['recent_plugin_activity'] = [];
						foreach ($recent_logs as $log) {
							$system_info['recent_plugin_activity'][] = [
								'plugin' => $log['plugin_name'],
								'action' => $log['action'],
								'time' => $log['date_time']
							];
						}
					}
				} catch (Exception $e) {
					error_log("MPAI: Error getting recent plugin logs for system info: " . $e->getMessage());
				}
			}
		}
		
		return $system_info;
	}
	
	/**
	 * Get PHP info directly using PHP functions
	 * 
	 * @return string Formatted PHP information
	 */
	private function get_php_info_directly() {
		// Get PHP version and other information
		$php_version = phpversion();
		$php_uname = php_uname();
		$php_sapi = php_sapi_name();
		
		// Get PHP configuration
		$memory_limit = ini_get('memory_limit');
		$max_execution_time = ini_get('max_execution_time');
		$upload_max_filesize = ini_get('upload_max_filesize');
		$post_max_size = ini_get('post_max_size');
		$max_input_vars = ini_get('max_input_vars');
		
		// Get loaded extensions
		$extensions = get_loaded_extensions();
		sort($extensions);
		$extensions_str = implode(', ', array_slice($extensions, 0, 10));
		
		// Format the output
		$php_info = "PHP Information:\n\n";
		$php_info .= "PHP Version: $php_version\n";
		$php_info .= "System: $php_uname\n";
		$php_info .= "SAPI: $php_sapi\n";
		$php_info .= "\nImportant Settings:\n";
		$php_info .= "memory_limit: $memory_limit\n";
		$php_info .= "max_execution_time: $max_execution_time seconds\n";
		$php_info .= "upload_max_filesize: $upload_max_filesize\n";
		$php_info .= "post_max_size: $post_max_size\n";
		$php_info .= "max_input_vars: $max_input_vars\n";
		$php_info .= "\nLoaded Extensions (first 10): $extensions_str\n";
		
		return $php_info;
	}
	
	/**
	 * Preprocess system queries for improved handling
	 * 
	 * @param string $user_message User message
	 * @param array &$intent_data Intent data to modify
	 */
	private function preprocess_system_queries($user_message, &$intent_data) {
		$user_message_lower = strtolower($user_message);
		
		// Check for PHP version queries using even more comprehensive patterns
		$php_version_patterns = [
			'/php.*version/i',
			'/version.*php/i',
			'/php.*info/i',
			'/what.*php.*version/i',
			'/which.*php.*version/i',
			'/php\s+([-]{1,2}v|info)/i',
			'/phpinfo/i',
			'/what.*version.*php/i',
			'/installed.*php.*version/i',
			'/php.*installed/i',
			'/check.*php.*version/i',
			'/show.*php.*version/i',
			'/tell.*php.*version/i',
			'/get.*php.*version/i',
			'/display.*php.*version/i'
		];
		
		$is_php_query = false;
		foreach ($php_version_patterns as $pattern) {
			if (preg_match($pattern, $user_message)) {
				$is_php_query = true;
				break;
			}
		}
		
		if ($is_php_query) {
			error_log("MPAI: Detected PHP version query, using new command system if available");
			
			// Try to use the new command system if available
			if ($this->command_adapter) {
				try {
					// Execute the PHP version command directly
					$result = $this->command_adapter->execute_tool('wpcli', ['command' => 'php -v']);
					
					if (is_array($result) && isset($result['output'])) {
						// Use the result from the new command system
						$php_info = $result['output'];
						error_log("MPAI: Used new command system for PHP version query");
					} else {
						// Fall back to direct PHP info if result is unexpected
						$php_info = $result;
					}
				} catch (Exception $e) {
					// Fall back to direct PHP info on error
					error_log("MPAI: Error using new command system: " . $e->getMessage());
					$php_info = $this->get_php_info_directly();
				}
			} else {
				// Use direct PHP info if command adapter not available
				$php_info = $this->get_php_info_directly();
			}
			
			// Add information about different PHP configurations to handle variations
			$intent_data['enhanced_php_info'] = $php_info;
			$intent_data['php_version'] = phpversion();
			$intent_data['php_config'] = [
				'version' => phpversion(),
				'memory_limit' => ini_get('memory_limit'),
				'max_execution_time' => ini_get('max_execution_time'),
				'sapi' => php_sapi_name(),
				'upload_max_filesize' => ini_get('upload_max_filesize'),
				'post_max_size' => ini_get('post_max_size'),
				'max_input_vars' => ini_get('max_input_vars')
			];
			
			// Provide the recommended command for getting PHP version directly
			$intent_data['php_version_commands'] = [
				'direct' => 'php -v',
				'wp_cli' => 'wp eval \'echo PHP_VERSION;\'',
				'wp_php' => 'wp php info'
			];
			
			// Directly add to message for better handling
			$intent_data['message'] .= "\n\nSystem Information: " . $php_info;
		}
		
		// Check for plugin activity queries with enhanced patterns
		$plugin_query_patterns = [
			'/recent.*(?:plugin|activat|installat|addon)/i',
			'/(?:plugin|activat|installat|addon).*recent/i',
			'/what.*plugin/i',
			'/which.*plugin/i',
			'/list.*plugin/i',
			'/show.*plugin/i',
			'/plugin.*(?:status|info|log|activit)/i'
		];
		
		$is_plugin_query = false;
		foreach ($plugin_query_patterns as $pattern) {
			if (preg_match($pattern, $user_message)) {
				$is_plugin_query = true;
				break;
			}
		}
		
		if ($is_plugin_query) {
			error_log("MPAI: Detected plugin-related query, gathering plugin information");
			
			// Ensure plugin functions are available
			if (!function_exists('get_plugins')) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			
			if (!function_exists('is_plugin_active')) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			
			// Get active plugins
			$all_plugins = get_plugins();
			$active_plugins = get_option('active_plugins');
			
			// Prepare plugin summary info
			$plugin_summary = "Plugin Information:\n\n";
			$plugin_summary .= "Total Plugins: " . count($all_plugins) . "\n";
			$plugin_summary .= "Active Plugins: " . count($active_plugins) . "\n";
			$plugin_summary .= "Inactive Plugins: " . (count($all_plugins) - count($active_plugins)) . "\n\n";
			
			// Add list of active plugins
			$plugin_summary .= "Active Plugins:\n";
			$count = 0;
			
			foreach ($active_plugins as $plugin) {
				if (isset($all_plugins[$plugin]) && $count < 10) {
					$plugin_data = $all_plugins[$plugin];
					$plugin_summary .= "- {$plugin_data['Name']} v{$plugin_data['Version']}\n";
					$count++;
				}
			}
			
			if (count($active_plugins) > 10) {
				$plugin_summary .= "... and " . (count($active_plugins) - 10) . " more\n";
			}
			
			// Ensure plugin logger is accessible and data is available
			if (function_exists('mpai_init_plugin_logger')) {
				$plugin_logger = mpai_init_plugin_logger();
				if ($plugin_logger) {
					try {
						// Ensure the table exists and has data
						if (method_exists($plugin_logger, 'maybe_create_table')) {
							$plugin_logger->maybe_create_table();
						}
						
						// Get recent logs
						if (method_exists($plugin_logger, 'get_logs')) {
							$logs = $plugin_logger->get_logs(array(
								'limit' => 10,
								'date_from' => date('Y-m-d H:i:s', strtotime('-30 days')),
								'orderby' => 'date_time',
								'order' => 'DESC',
							));
							
							if (!empty($logs)) {
								$plugin_summary .= "\nRecent Plugin Activity:\n";
								
								foreach ($logs as $log) {
									$action = ucfirst($log['action']);
									$plugin_name = $log['plugin_name'];
									$date = date('M j, Y', strtotime($log['date_time']));
									$plugin_summary .= "- {$plugin_name}: {$action} on {$date}\n";
								}
							}
						}
					} catch (Exception $e) {
						error_log("MPAI: Error accessing plugin logs: " . $e->getMessage());
					}
				}
			}
			
			// Add plugin information to intent data
			$intent_data['enhanced_plugin_info'] = $plugin_summary;
			
			// Directly add to message for better handling
			$intent_data['message'] .= "\n\nPlugin Information: " . $plugin_summary;
		}
	}
	
	/**
	 * Get available tools information for inclusion in SDK context
	 * 
	 * @return array Information about available tools
	 */
	private function get_available_tools_info() {
		$tools_info = [];
		
		if (!$this->tool_registry) {
			return $tools_info;
		}
		
		$available_tools = $this->tool_registry->get_available_tools();
		
		foreach ($available_tools as $tool_id => $tool_instance) {
			$tools_info[$tool_id] = [
				'name' => method_exists($tool_instance, 'get_name') ? $tool_instance->get_name() : $tool_id,
				'description' => method_exists($tool_instance, 'get_description') ? $tool_instance->get_description() : '',
				'parameters' => method_exists($tool_instance, 'get_parameters') ? $tool_instance->get_parameters() : [],
			];
		}
		
		return $tools_info;
	}
	
	/**
	 * Process request with traditional method
	 * 
	 * @param string $user_message User message
	 * @param int $user_id User ID
	 * @param array $user_context User context data
	 * @return array Response data
	 */
	private function process_with_traditional_method( $user_message, $user_id = 0, $user_context = [] ) {
		try {
			// Determine primary intent and agent
			$intent_data = $this->parse_intent( $user_message );
			$primary_agent_id = $intent_data['primary_agent'];
			
			// Check if agent exists
			if ( ! isset( $this->agents[$primary_agent_id] ) ) {
				throw new Exception( "Agent {$primary_agent_id} not found" );
			}
			
			// Get the primary agent
			$primary_agent = $this->agents[$primary_agent_id];
			
			// Process the request with the primary agent
			$result = $primary_agent->process_request( $intent_data, $user_context );
			
			// Update memory with results
			$this->update_memory( $user_id, $intent_data, $result );
			
			// Log the successful completion
			error_log( "MPAI: Successfully processed request for agent {$primary_agent_id}" );
			
			return [
				'success' => true,
				'message' => $result['message'],
				'data' => isset( $result['data'] ) ? $result['data'] : [],
				'agent' => $primary_agent_id,
			];
		} catch ( Exception $e ) {
			error_log( "MPAI: Error processing with traditional method: " . $e->getMessage() );
			
			throw $e; // Re-throw to be caught by the main process_request method
		}
	}
	
	/**
	 * Update memory with request/response data
	 * 
	 * @param int $user_id User ID
	 * @param array $intent_data Intent data
	 * @param array $result Processing result
	 */
	private function update_memory( $user_id = 0, $intent_data = [], $result = [] ) {
		// For now, just store in user meta
		if ( $user_id > 0 ) {
			// Get existing memory
			$memory = get_user_meta( $user_id, 'mpai_memory', true );
			if ( ! is_array( $memory ) ) {
				$memory = [];
			}
			
			// Add new entry
			$memory[] = [
				'timestamp' => current_time( 'mysql' ),
				'intent' => $intent_data,
				'result' => $result,
			];
			
			// Limit memory size
			if ( count( $memory ) > 10 ) {
				$memory = array_slice( $memory, -10 );
			}
			
			// Save updated memory
			update_user_meta( $user_id, 'mpai_memory', $memory );
		}
	}
	
	/**
	 * Get user context data
	 * 
	 * @param int $user_id User ID
	 * @return array User context data
	 */
	private function get_user_context( $user_id = 0 ) {
		$context = [
			'user_id' => $user_id,
			'current_time' => current_time( 'mysql' ),
			'site_name' => get_bloginfo( 'name' ),
		];
		
		// Add user-specific context if user is logged in
		if ( $user_id > 0 ) {
			$user = get_userdata( $user_id );
			if ( $user ) {
				$context['user_name'] = $user->display_name;
				$context['user_email'] = $user->user_email;
				$context['user_roles'] = $user->roles;
				
				// Get memory
				$memory = get_user_meta( $user_id, 'mpai_memory', true );
				if ( is_array( $memory ) ) {
					$context['memory'] = $memory;
				}
				
				// Get preferences
				$preferences = get_user_meta( $user_id, 'mpai_preferences', true );
				if ( is_array( $preferences ) ) {
					$context['preferences'] = $preferences;
				}
			}
		}
		
		return $context;
	}
	
	/**
	 * Parse intent from user message
	 * 
	 * @param string $message User message
	 * @return array Intent data
	 */
	private function parse_intent( $message ) {
		// Default intent data
		$intent_data = [
			'original_message' => $message,
			'primary_agent' => 'memberpress', // Default to MemberPress agent
			'tool_calls' => [],
		];
		
		// Determine primary intent
		$intent = $this->determine_primary_intent( $message );
		
		// Set primary agent based on intent
		$intent_data['primary_agent'] = $intent;
		
		return $intent_data;
	}
	
	/**
	 * Determine primary intent from user message
	 * 
	 * @param string $message User message
	 * @param array $context Additional context
	 * @return string Intent identifier
	 */
	private function determine_primary_intent( $message, $context = [] ) {
		// Default to memberpress management
		if ( empty( $message ) ) {
			return 'memberpress_management';
		}
		
		// Use enhanced agent scoring system
		$agent_scores = $this->get_agent_confidence_scores($message, $context);
		
		// Apply weighted selection algorithm with confidence threshold
		$primary_agent = $this->select_agent_with_confidence($agent_scores, $message);
		
		// Log detailed scoring results for debugging
		error_log("MPAI: Agent scores: " . json_encode($agent_scores));
		error_log("MPAI: Selected primary agent: {$primary_agent} with score: {$agent_scores[$primary_agent]}");
		
		return $primary_agent;
	}
	
	/**
	 * Get confidence scores for all available agents
	 * 
	 * @param string $message User message
	 * @param array $context Additional context
	 * @return array Associative array of agent_id => confidence_score
	 */
	private function get_agent_confidence_scores($message, $context = []) {
		$agent_scores = [];
		
		// Calculate scores for each agent
		foreach ( $this->agents as $agent_id => $agent ) {
			// Get base confidence score from agent's evaluate_request method
			$base_score = $agent->evaluate_request($message, $context);
			
			// Apply contextual modifiers
			$modified_score = $this->apply_contextual_modifiers($agent_id, $base_score, $message, $context);
			
			// Store the final score
			$agent_scores[$agent_id] = $modified_score;
		}
		
		return $agent_scores;
	}
	
	/**
	 * Apply contextual modifiers to the base confidence score
	 * 
	 * @param string $agent_id Agent identifier
	 * @param int $base_score Base confidence score (0-100)
	 * @param string $message User message
	 * @param array $context Additional context
	 * @return int Modified confidence score (0-100)
	 */
	private function apply_contextual_modifiers($agent_id, $base_score, $message, $context = []) {
		$modified_score = $base_score;
		
		// 1. Conversation continuity - boost score if this is a follow-up to previous interaction
		if (isset($context['memory']) && is_array($context['memory'])) {
			// Check if the last interaction was with this agent
			$last_memory = end($context['memory']);
			if ($last_memory && 
				isset($last_memory['result']['agent']) && 
				$last_memory['result']['agent'] === $agent_id) {
				// Apply a significant boost for conversation continuity
				$modified_score += 15;
			}
		}
		
		// 2. Agent specialization - boost score for specialized agents over general ones
		if ($agent_id !== 'memberpress') {
			// Give a slight boost to specialized agents over the default agent
			$modified_score += 5;
		}
		
		// 3. Performance history - adjust based on past performance
		if (isset($context['agent_performance']) && 
			isset($context['agent_performance'][$agent_id])) {
			$performance = $context['agent_performance'][$agent_id];
			if (isset($performance['success_rate']) && $performance['success_rate'] > 0.8) {
				// Boost for agents with high success rate
				$modified_score += 10;
			} elseif (isset($performance['success_rate']) && $performance['success_rate'] < 0.4) {
				// Penalty for agents with low success rate
				$modified_score -= 10;
			}
		}
		
		// Cap at 0-100 range
		return max(0, min($modified_score, 100));
	}
	
	/**
	 * Select the most appropriate agent based on confidence scores
	 * 
	 * @param array $agent_scores Associative array of agent_id => confidence_score
	 * @param string $message Original user message
	 * @return string Selected agent ID
	 */
	private function select_agent_with_confidence($agent_scores, $message) {
		// Find highest scoring agent
		$highest_score = 0;
		$primary_agent = 'memberpress'; // Default if no high scores
		
		foreach ( $agent_scores as $agent_id => $score ) {
			if ( $score > $highest_score ) {
				$highest_score = $score;
				$primary_agent = $agent_id;
			}
		}
		
		// Apply confidence threshold - if no agent scores high enough, fall back to default
		$confidence_threshold = 30; // Minimum score to be confident in selection
		
		if ($highest_score < $confidence_threshold) {
			// No agent is confident enough, use default memberpress agent
			error_log("MPAI: No agent met confidence threshold of {$confidence_threshold}, falling back to default");
			return 'memberpress';
		}
		
		// Check for agents with similar scores and apply tiebreaker logic
		$close_scores = [];
		$similarity_threshold = 10; // Consider scores within this range as similar
		
		foreach ($agent_scores as $agent_id => $score) {
			if ($score >= ($highest_score - $similarity_threshold)) {
				$close_scores[$agent_id] = $score;
			}
		}
		
		// If multiple agents have similar high scores, apply tiebreakers
		if (count($close_scores) > 1) {
			// Log tied agents
			error_log("MPAI: Multiple agents with similar scores: " . json_encode($close_scores));
			
			// Tiebreaker 1: Prefer specialized agents over general ones
			if (isset($close_scores['memberpress']) && count($close_scores) > 1) {
				// If default agent is tied with specialized agents, prefer specialized
				unset($close_scores['memberpress']);
				$primary_agent = array_keys($close_scores)[0];
			}
			
			// Tiebreaker 2: If still tied, use content analysis (length of message, complexity)
			if (count($close_scores) > 1) {
				// More complex, longer messages might benefit from more specialized agents
				if (strlen($message) > 100) {
					// For longer messages, prefer specialized over general agents
					foreach (array_keys($close_scores) as $agent_id) {
						if ($agent_id !== 'memberpress') {
							$primary_agent = $agent_id;
							break;
						}
					}
				}
			}
		}
		
		return $primary_agent;
	}
	
	/**
	 * Register all core agents
	 */
	private function register_core_agents() {
		// Discover all agent files
		$this->discover_agents();
		
		// Manually register any core agents that require special handling
		// (only if they weren't discovered automatically)
		if (!isset($this->agents['memberpress'])) {
			$this->register_memberpress_agent();
		}
		
		if (!isset($this->agents['command_validation'])) {
			$this->register_command_validation_agent();
		}
	}
	
	/**
	 * Discover and register available agents
	 */
	private function discover_agents() {
		$agents_dir = plugin_dir_path(__FILE__) . 'specialized/';
		$agent_files = glob($agents_dir . 'class-mpai-*.php');
		
		foreach ($agent_files as $agent_file) {
			// Load agent file if not already loaded
			if (!class_exists(basename($agent_file, '.php'))) {
				require_once $agent_file;
			}
			
			// Extract class name from filename
			$filename = basename($agent_file, '.php');
			$class_name = str_replace('class-', '', $filename);
			$class_name = str_replace('-', '_', $class_name);
			$class_name = strtoupper($class_name);
			
			// Create agent instance if class exists
			if (class_exists($class_name)) {
				$agent = new $class_name($this->tool_registry, $this->logger);
				$agent_id = strtolower(str_replace('MPAI_', '', $class_name));
				$agent_id = str_replace('_agent', '', $agent_id);
				
				// Apply security validation
				if ($this->validate_agent($agent_id, $agent)) {
					$this->register_agent($agent_id, $agent);
					error_log("MPAI: Discovered and registered agent: " . $agent_id);
				} else {
					error_log("MPAI: Agent failed security validation: " . $agent_id);
				}
			}
		}
		
		// Apply filter to allow modifications
		$this->agents = apply_filters('mpai_available_agents', $this->agents);
	}
	
	/**
	 * Validate agent according to security framework
	 * 
	 * @param string $agent_id
	 * @param object $agent
	 * @return bool Whether agent passes validation
	 */
	private function validate_agent($agent_id, $agent) {
		// Check agent has required methods
		if (!method_exists($agent, 'get_capabilities') ||
			!method_exists($agent, 'get_name') ||
			!method_exists($agent, 'get_description')) {
			return false;
		}
		
		// Check agent has valid capabilities structure
		$capabilities = $agent->get_capabilities();
		if (!is_array($capabilities)) {
			return false;
		}
		
		// Check agent implements the MPAI_Agent interface
		if (!($agent instanceof MPAI_Agent)) {
			return false;
		}
		
		// Check agent has process_request method
		if (!method_exists($agent, 'process_request')) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * Register Command Validation agent
	 */
	private function register_command_validation_agent() {
		// Check if the class exists
		if (!class_exists('MPAI_Command_Validation_Agent')) {
			$agent_path = plugin_dir_path(__FILE__) . 'specialized/class-mpai-command-validation-agent.php';
			if (file_exists($agent_path)) {
				require_once $agent_path;
			}
		}
		
		// Create and register the agent if available
		if (class_exists('MPAI_Command_Validation_Agent')) {
			$validation_agent = new MPAI_Command_Validation_Agent($this->tool_registry, $this->logger);
			$this->register_agent('command_validation', $validation_agent);
			// Command Validation Agent registered
		} else {
			error_log('MPAI: Warning - Command Validation Agent class not found');
		}
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
			error_log( "MPAI: Cannot execute background task: SDK not initialized" );
			return false;
		}
		
		try {
			// Execute the task using the SDK integration
			$this->sdk_integration->execute_background_task( $thread_id, $assistant_id, $task_id, $user_id );
			return true;
		} catch ( Exception $e ) {
			error_log( "MPAI: Error executing background task: " . $e->getMessage() );
			
			// Update task status with error
			$task_info = get_option( "mpai_task_{$task_id}", [] );
			$task_info['status'] = 'failed';
			$task_info['error'] = $e->getMessage();
			$task_info['completed_at'] = current_time( 'mysql' );
			update_option( "mpai_task_{$task_id}", $task_info );
			
			return false;
		}
	}
	
	/**
	 * Handle a handoff from one agent to another
	 * 
	 * @param string $from_agent_id Source agent ID
	 * @param string $to_agent_id Target agent ID
	 * @param array $handoff_data Handoff data
	 * @param int $user_id User ID
	 * @return array Handoff result
	 */
	public function handle_handoff( $from_agent_id, $to_agent_id, $handoff_data, $user_id = 0 ) {
		// Create agent message
		$message = new MPAI_Agent_Message(
			$from_agent_id,
			$to_agent_id,
			'handoff',
			isset($handoff_data['message']) ? $handoff_data['message'] : '',
			$handoff_data
		);
		
		// Security validation of the message
		if (!$this->validate_agent_message($message)) {
			error_log("MPAI: Agent message failed security validation during handoff");
			throw new Exception("Security validation failed for agent message");
		}
		
		// Check if SDK integration can handle this
		if ( $this->sdk_initialized && $this->sdk_integration ) {
			try {
				// Add some context to the handoff data
				$handoff_data['user_id'] = $user_id;
				$handoff_data['from_agent'] = $from_agent_id;
				$handoff_data['to_agent'] = $to_agent_id;
				$handoff_data['message_object'] = $message->to_array();
				
				// Execute the handoff using the SDK
				$handoff_result = $this->sdk_integration->handle_handoff( $from_agent_id, $to_agent_id, $handoff_data, $user_id );
				
				error_log( "MPAI: Successfully handled handoff with SDK from {$from_agent_id} to {$to_agent_id}" );
				
				return $handoff_result;
			} catch ( Exception $e ) {
				error_log( "MPAI: Error handling handoff with SDK: " . $e->getMessage() );
				// Fall back to traditional handoff if SDK fails
			}
		}
		
		// Traditional handoff using the agent message format
		error_log( "MPAI: Performing traditional handoff from {$from_agent_id} to {$to_agent_id}" );
		
		// Get user context
		$user_context = $this->get_user_context( $user_id );
		
		// Check if target agent exists
		if ( ! isset( $this->agents[$to_agent_id] ) ) {
			throw new Exception( "Target agent {$to_agent_id} not found" );
		}
		
		// Get the target agent
		$target_agent = $this->agents[$to_agent_id];
		
		// Check if the target agent has a process_message method
		if (method_exists($target_agent, 'process_message')) {
			// Process using the message format
			$result = $target_agent->process_message($message, $user_context);
		} else {
			// Fall back to intent-based format
			$intent_data = [
				'intent' => 'handoff',
				'primary_agent' => $to_agent_id,
				'original_message' => $message->get_content(),
				'handoff_data' => $message->get_metadata(),
				'from_agent' => $from_agent_id
			];
			
			// Process using the traditional method
			$result = $target_agent->process_request($intent_data, $user_context);
		}
		
		// Update memory
		$this->update_memory($user_id, ['message' => $message->to_array()], $result);
		
		return $result;
	}
	
	/**
	 * Validate agent message for security
	 *
	 * @param MPAI_Agent_Message $message
	 * @return bool
	 */
	private function validate_agent_message($message) {
		// Check required fields
		if (empty($message->get_sender()) || empty($message->get_receiver())) {
			return false;
		}
		
		// Check that agents exist
		if (!isset($this->agents[$message->get_sender()]) || 
			!isset($this->agents[$message->get_receiver()])) {
			return false;
		}
		
		// Check for dangerous content patterns
		$content = $message->get_content();
		if (preg_match('/(?:<script|javascript:|eval\(|base64)/i', $content)) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * Run an agent with specific parameters
	 * 
	 * @param string $agent_id Agent ID
	 * @param array $params Agent parameters
	 * @param int $user_id User ID
	 * @return array Agent result
	 */
	public function run_agent( $agent_id, $params = [], $user_id = 0 ) {
		// Check if SDK integration can handle this
		if ( $this->sdk_initialized && $this->sdk_integration ) {
			try {
				// Add user ID to the parameters
				$params['user_id'] = $user_id;
				
				// Execute the agent run using the SDK
				$run_result = $this->sdk_integration->run_agent( $agent_id, $params, $user_id );
				
				error_log( "MPAI: Successfully started running agent with SDK: {$agent_id}" );
				
				return $run_result;
			} catch ( Exception $e ) {
				error_log( "MPAI: Error starting running agent with SDK: " . $e->getMessage() );
				// Fall back to traditional processing
			}
		}
		
		// Traditional direct agent execution
		if ( ! isset( $this->agents[$agent_id] ) ) {
			throw new Exception( "Agent {$agent_id} not found" );
		}
		
		// Get user context
		$user_context = $this->get_user_context( $user_id );
		
		// Create intent data
		$intent_data = [
			'intent' => 'direct_run',
			'primary_agent' => $agent_id,
			'params' => $params
		];
		
		// Get the agent
		$agent = $this->agents[$agent_id];
		
		// Process the request with the agent
		$result = $agent->process_request( $intent_data, $user_context );
		
		// Update memory
		$this->update_memory( $user_id, $intent_data, $result );
		
		return $result;
	}
}