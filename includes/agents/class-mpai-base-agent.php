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
			'info'    => function( $message, $context = [] ) { mpai_log_debug( $message, 'base-agent', $context ); },
			'warning' => function( $message, $context = [] ) { mpai_log_warning( $message, 'base-agent', $context ); },
			'error'   => function( $message, $context = [] ) { mpai_log_error( $message, 'base-agent', $context ); },
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
	 * Keywords for request evaluation
	 * @var array
	 */
	protected $keywords = [];
	
	/**
	 * Evaluate ability to handle this request
	 *
	 * @param string $message User message
	 * @param array $context Additional context
	 * @return int Score from 0-100
	 */
	public function evaluate_request($message, $context = []) {
		// Base implementation using weighted scoring algorithm
		$confidence_score = 0;
		$message_lower = strtolower($message);
		
		// 1. Keyword matching (basic matching)
		foreach ($this->keywords as $keyword => $weight) {
			if (strpos($message_lower, $keyword) !== false) {
				$confidence_score += $weight;
			}
		}
		
		// 2. Context awareness (if provided)
		if (!empty($context)) {
			// Check for previous successful interactions with this agent
			if (isset($context['memory']) && is_array($context['memory'])) {
				foreach ($context['memory'] as $memory_item) {
					if (isset($memory_item['result']) && 
						isset($memory_item['result']['agent']) && 
						$memory_item['result']['agent'] === $this->id &&
						isset($memory_item['result']['success']) && 
						$memory_item['result']['success'] === true) {
						// Add points for previous successful interactions
						$confidence_score += 5; // Modest bonus for past success
					}
				}
			}
			
			// Check for user preferences
			if (isset($context['preferences']) && 
				isset($context['preferences']['preferred_agent']) && 
				$context['preferences']['preferred_agent'] === $this->id) {
				$confidence_score += 10; // Significant bonus for being the preferred agent
			}
		}
		
		// 3. Capability-based scoring
		$capability_score = $this->evaluate_capability_match($message, $context);
		$confidence_score += $capability_score;
		
		// Log the detailed scoring process if debug mode is enabled
		$this->log_scoring_details($message, $confidence_score, $capability_score, $context);
		
		// Cap at 100
		return min($confidence_score, 100);
	}
	
	/**
	 * Evaluate how well the agent's capabilities match the request
	 *
	 * @param string $message User message
	 * @param array $context Additional context
	 * @return int Capability match score (0-50)
	 */
	protected function evaluate_capability_match($message, $context = []) {
		$capability_score = 0;
		$message_lower = strtolower($message);
		
		// Check each capability for relevance to the message
		foreach ($this->capabilities as $capability_key => $capability_description) {
			// Convert capability key and description to relevant terms for matching
			$capability_terms = array_merge(
				$this->extract_terms($capability_key),
				$this->extract_terms($capability_description)
			);
			
			// Score based on term matching
			foreach ($capability_terms as $term) {
				if (strpos($message_lower, $term) !== false) {
					$capability_score += 10; // Significant boost for direct capability match
					break; // Only count each capability once
				}
			}
		}
		
		return min($capability_score, 50); // Cap capability component at 50
	}
	
	/**
	 * Extract searchable terms from a string
	 *
	 * @param string $text Text to extract terms from
	 * @return array List of terms
	 */
	protected function extract_terms($text) {
		// Convert from camelCase or snake_case
		$text = strtolower($text);
		$text = str_replace('_', ' ', $text);
		$text = preg_replace('/([a-z])([A-Z])/', '$1 $2', $text);
		
		// Split into words and filter out common words
		$words = explode(' ', $text);
		$common_words = ['and', 'or', 'the', 'a', 'an', 'in', 'on', 'at', 'to', 'for', 'with', 'by'];
		
		return array_filter($words, function($word) use ($common_words) {
			return !in_array($word, $common_words) && strlen($word) > 2;
		});
	}
	
	/**
	 * Log detailed scoring information for debugging
	 *
	 * @param string $message Original message
	 * @param int $total_score Total confidence score
	 * @param int $capability_score Capability-based score component
	 * @param array $context Context used for scoring
	 */
	protected function log_scoring_details($message, $total_score, $capability_score, $context) {
		if (!defined('MPAI_DEBUG') || !MPAI_DEBUG) {
			return;
		}
		
		$log_data = [
			'agent' => $this->id,
			'message' => substr($message, 0, 50) . (strlen($message) > 50 ? '...' : ''),
			'total_score' => $total_score,
			'keyword_score' => $total_score - $capability_score,
			'capability_score' => $capability_score,
			'capabilities' => array_keys($this->capabilities),
			'keywords' => array_keys($this->keywords)
		];
		
		mpai_log_debug('Agent scoring details: ' . json_encode($log_data), 'agent-scoring');
	}
	
	/**
	 * Process an agent message
	 *
	 * @param MPAI_Agent_Message $message
	 * @param array $context User context
	 * @return array Response data
	 */
	public function process_message($message, $context = []) {
		// Implementation in base agent - convert to legacy format
		$intent_data = [
			'intent' => $message->get_message_type(),
			'primary_agent' => $this->id,
			'original_message' => $message->get_content(),
			'metadata' => $message->get_metadata(),
			'from_agent' => $message->get_sender()
		];
		
		return $this->process_request($intent_data, $context);
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
		// Check if the tool registry is available
		if (!$this->tool_registry) {
			$this->logger->error("Tool registry not available when attempting to execute {$tool_id}");
			
			// Try to recover by getting a new tool registry instance
			try {
				$this->tool_registry = new MPAI_Tool_Registry();
				$this->logger->info("Tool registry instance created");
			} catch (Exception $e) {
				$this->logger->error("Failed to create new tool registry: " . $e->getMessage());
			}
			
			// If still not available after recovery attempt
			if (!$this->tool_registry) {
				// Try to recover by using global registry if available
				global $mpai_tool_registry;
				if ($mpai_tool_registry && $mpai_tool_registry instanceof MPAI_Tool_Registry) {
					$this->logger->info("Recovered tool registry from global variable");
					$this->tool_registry = $mpai_tool_registry;
				} else {
					throw new Exception('Tool registry not available and all recovery attempts failed');
				}
			}
			$this->logger->info("Tool registry recovered successfully");
		}
		
		// Get the tool from the registry
		$tool = $this->tool_registry->get_tool($tool_id);
		
		// If tool not found, try more recovery steps for critical tools
		if (!$tool) {
			$this->logger->warning("Tool {$tool_id} not found on first attempt, trying recovery");
			
			// Re-initialize the tool registry completely
			try {
				$this->tool_registry = new MPAI_Tool_Registry();
				
				// Force register core tools
				if (method_exists($this->tool_registry, 'register_core_tools')) {
					$this->tool_registry->register_core_tools();
				}
				
				// Try to get the tool again
				$tool = $this->tool_registry->get_tool($tool_id);
			} catch (Exception $e) {
				$this->logger->error("Failed to re-initialize tool registry: " . $e->getMessage());
			}
			
			// If still no tool, try to load it directly
			if (!$tool) {
				$this->logger->warning("Tool {$tool_id} still not available, trying direct implementation loading");
				$tool = $this->load_tool_directly($tool_id);
			}
			
			if ($tool) {
				$this->logger->info("Tool {$tool_id} recovered successfully");
			} else {
				// Last resort - try to handle known tool types directly
				$fallback_result = $this->handle_tool_fallback($tool_id, $parameters);
				if ($fallback_result !== null) {
					$this->logger->info("Used fallback handler for {$tool_id}");
					return $fallback_result;
				}
				
				// Log available tools for debugging
				$available_tools = $this->tool_registry->get_available_tools();
				$available_tool_ids = array_keys($available_tools);
				$this->logger->error("Tool {$tool_id} not found in registry after all recovery attempts");
				$this->logger->info("Available tools: " . implode(', ', $available_tool_ids));
				
				throw new Exception("Tool {$tool_id} not found. Available tools: " . implode(', ', $available_tool_ids));
			}
		}
		
		$this->logger->info("Executing tool {$tool_id}");
		
		// Execute the tool with parameters
		try {
			return $tool->execute($parameters);
		} catch (Exception $e) {
			$this->logger->error("Tool execution failed: " . $e->getMessage());
			
			// Try fallback for critical tools on execution failure
			$fallback_result = $this->handle_tool_fallback($tool_id, $parameters);
			if ($fallback_result !== null) {
				$this->logger->info("Used fallback execution for {$tool_id} after failure");
				return $fallback_result;
			}
			
			throw $e;
		}
	}
	
	/**
	 * Attempt to load a tool implementation directly
	 *
	 * @param string $tool_id Tool ID to load
	 * @return object|null Tool instance or null if not found
	 */
	private function load_tool_directly($tool_id) {
		$tool_map = [
			'wpcli' => 'MPAI_WP_CLI_Tool',
			'wp_api' => 'MPAI_WP_API_Tool',
			'diagnostic' => 'MPAI_Diagnostic_Tool',
			'plugin_logs' => 'MPAI_Plugin_Logs_Tool'
		];
		
		if (!isset($tool_map[$tool_id])) {
			return null;
		}
		
		$class_name = $tool_map[$tool_id];
		
		// Check if class already exists
		if (class_exists($class_name)) {
			$this->logger->info("Class {$class_name} already loaded, creating instance");
			return new $class_name();
		}
		
		// Try to find and include the file
		$base_paths = [
			MPAI_PLUGIN_DIR . 'includes/tools/implementations/',
			dirname(dirname(__FILE__)) . '/tools/implementations/',
			dirname(dirname(dirname(__FILE__))) . '/tools/implementations/'
		];
		
		foreach ($base_paths as $base_path) {
			$file_path = $base_path . 'class-' . strtolower(str_replace('_', '-', $tool_id)) . '-tool.php';
			$alt_file_path = $base_path . 'class-mpai-' . strtolower(str_replace('_', '-', $tool_id)) . '-tool.php';
			
			if (file_exists($file_path)) {
				require_once $file_path;
				$this->logger->info("Loaded tool file from: {$file_path}");
				break;
			} elseif (file_exists($alt_file_path)) {
				require_once $alt_file_path;
				$this->logger->info("Loaded tool file from: {$alt_file_path}");
				break;
			}
		}
		
		// Check if class now exists and create instance
		if (class_exists($class_name)) {
			return new $class_name();
		}
		
		return null;
	}
	
	/**
	 * Handle tool fallbacks for critical tools
	 *
	 * @param string $tool_id Tool ID
	 * @param array $parameters Tool parameters
	 * @return mixed|null Fallback result or null if no fallback available
	 */
	private function handle_tool_fallback($tool_id, $parameters) {
		switch ($tool_id) {
			case 'plugin_logs':
				return $this->plugin_logs_fallback($parameters);
				
			case 'wpcli':
				if (isset($parameters['command'])) {
					// Handle PHP version commands directly
					if (preg_match('/php.*version|php\s+([-]{1,2}v|info)/i', $parameters['command'])) {
						return $this->php_version_fallback();
					}
					
					// Handle plugin status commands directly
					if (preg_match('/(?:active|installed).*plugins/i', $parameters['command']) ||
						preg_match('/plugin.*(?:status|info)/i', $parameters['command'])) {
						return $this->plugin_status_fallback();
					}
				}
				break;
		}
		
		return null;
	}
	
	/**
	 * Plugin logs fallback implementation
	 *
	 * @param array $parameters Tool parameters
	 * @return array Fallback plugin logs data
	 */
	private function plugin_logs_fallback($parameters) {
		// Create plugin logs tool manually without registry
		if (class_exists('MPAI_Plugin_Logs_Tool')) {
			$tool = new MPAI_Plugin_Logs_Tool();
			try {
				return $tool->execute($parameters);
			} catch (Exception $e) {
				$this->logger->error("Fallback plugin logs execution failed: " . $e->getMessage());
			}
		}
		
		// Basic fallback response
		return [
			'success' => true,
			'message' => 'Retrieved plugin logs (limited data available)',
			'summary' => [
				'total' => 0,
				'activated' => 0,
				'deactivated' => 0,
				'installed' => 0,
				'updated' => 0
			],
			'plugins' => [],
			'logs' => [],
			'is_fallback' => true
		];
	}
	
	/**
	 * PHP version fallback implementation
	 *
	 * @return string PHP version information
	 */
	private function php_version_fallback() {
		$output = "PHP Information:\n\n";
		$output .= "PHP Version: " . phpversion() . "\n";
		$output .= "System: " . php_uname() . "\n";
		$output .= "SAPI: " . php_sapi_name() . "\n";
		$output .= "\nImportant Settings:\n";
		$output .= "memory_limit: " . ini_get('memory_limit') . "\n";
		$output .= "max_execution_time: " . ini_get('max_execution_time') . " seconds\n";
		$output .= "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
		$output .= "post_max_size: " . ini_get('post_max_size') . "\n";
		$output .= "max_input_vars: " . ini_get('max_input_vars') . "\n";
		
		$extensions = get_loaded_extensions();
		sort($extensions);
		$output .= "\nExtensions: " . implode(', ', array_slice($extensions, 0, 15)) . "...\n";
		
		return $output;
	}
	
	/**
	 * Plugin status fallback implementation
	 *
	 * @return string Plugin status information
	 */
	private function plugin_status_fallback() {
		// Ensure plugin functions are available
		if (!function_exists('get_plugins')) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		
		if (!function_exists('is_plugin_active')) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		
		$output = "WordPress Plugin Status:\n\n";
		$output .= "PHP Version: " . phpversion() . "\n";
		$output .= "WordPress Version: " . get_bloginfo('version') . "\n\n";
		
		$all_plugins = get_plugins();
		$active_plugins = get_option('active_plugins');
		
		$output .= "Plugin Statistics:\n";
		$output .= "Total Plugins: " . count($all_plugins) . "\n";
		$output .= "Active Plugins: " . count($active_plugins) . "\n";
		$output .= "Inactive Plugins: " . (count($all_plugins) - count($active_plugins)) . "\n\n";
		
		$output .= "Active Plugins:\n";
		$count = 0;
		
		foreach ($active_plugins as $plugin) {
			if (isset($all_plugins[$plugin]) && $count < 10) {
				$plugin_data = $all_plugins[$plugin];
				$output .= "- {$plugin_data['Name']} v{$plugin_data['Version']}\n";
				$count++;
			}
		}
		
		if (count($active_plugins) > 10) {
			$output .= "... and " . (count($active_plugins) - 10) . " more\n";
		}
		
		return $output;
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
