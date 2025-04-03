<?php
/**
 * Tool for executing WP-CLI commands
 *
 * @package MemberPress AI Assistant
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tool for executing WP-CLI commands
 */
class MPAI_WP_CLI_Tool extends MPAI_Base_Tool {
	/**
	 * Allowlist of permitted command prefixes
	 * @var array
	 */
	private $allowed_command_prefixes = [
		'wp plugin list',
		'wp plugin update',
		'wp plugin status',
		'wp plugin info',
		'wp theme list',
		'wp theme update',
		'wp post list',
		'wp post get',
		'wp user list',
		'wp user get',
		'wp option get',
		'wp core version',
		'wp core verify-checksums',
		'wp core check-update',
		'wp mepr',  // MemberPress commands
		'wp php info', // PHP information commands
		'wp php version', // PHP version command 
		'wp site health', // Site health commands
		'wp db info', // Database info
		'wp maintenance-mode', // Maintenance mode status
		'wp system-info', // System information
		'wp plugins', // General plugins commands
		'wp plugin recent', // Recently activated plugins
		'php --version', // Direct PHP version command
		'php -v', // Short PHP version command
		'wp eval', // PHP evaluation commands (for retrieving PHP_VERSION, etc.)
	];
	
	/**
	 * Execution timeout in seconds
	 * @var int
	 */
	private $execution_timeout = 30;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->name = 'WP-CLI Tool';
		$this->description = 'Executes WordPress CLI commands securely';
		
		// Allow plugins to extend the allowed commands
		$this->allowed_command_prefixes = apply_filters( 'mpai_allowed_cli_commands', $this->allowed_command_prefixes );
	}
	
	/**
	 * Execute a WP-CLI command
	 *
	 * @param array $parameters Parameters for the tool
	 * @return mixed Command result
	 * @throws Exception If command validation fails or execution error
	 */
	public function execute( $parameters ) {
		if ( ! isset( $parameters['command'] ) ) {
			throw new Exception( 'Command parameter is required' );
		}
		
		$command = $parameters['command'];
		error_log('MPAI_WP_CLI_Tool: Executing command: ' . $command);
		
		// Validate the command
		if ( ! $this->validate_command( $command ) ) {
			throw new Exception( 'Command validation failed: not in allowlist' );
		}
		
		// Special handling for specific commands that should use internal WordPress functions
		if (strpos($command, 'wp plugin list') === 0) {
			return $this->handle_plugin_list_command($command, $parameters);
		}
		
		// Set execution timeout
		$timeout = isset( $parameters['timeout'] ) ? 
			min( (int) $parameters['timeout'], 60 ) : 
			$this->execution_timeout;
		
		// Check if WP-CLI is available
		if (!class_exists('WP_CLI')) {
			error_log('MPAI_WP_CLI_Tool: WP-CLI not available, using alternative methods');
			return $this->handle_command_without_wpcli($command, $parameters);
		}
		
		// Build the command
		$wp_cli_command = $this->build_command( $command );
		
		// Execute the command
		$output = [];
		$return_var = 0;
		$last_line = exec( $wp_cli_command, $output, $return_var );
		
		if ( $return_var !== 0 ) {
			throw new Exception( 'Command execution failed with code ' . $return_var . ': ' . implode( "\n", $output ) );
		}
		
		// Parse the output based on requested format
		$format = isset( $parameters['format'] ) ? $parameters['format'] : 'text';
		
		return $this->parse_output( $output, $format );
	}
	
	/**
	 * Handle plugin list command using internal WordPress functions
	 *
	 * @param string $command The original command
	 * @param array $parameters Additional parameters
	 * @return string Formatted plugin list output
	 */
	private function handle_plugin_list_command($command, $parameters) {
		error_log('MPAI_WP_CLI_Tool: Using internal WordPress functions for plugin list at ' . date('H:i:s'));
		
		// Load WP API Tool to use the enhanced get_plugins method
		if (!class_exists('MPAI_WP_API_Tool')) {
			$tool_path = dirname(__FILE__) . '/class-mpai-wp-api-tool.php';
			if (file_exists($tool_path)) {
				require_once $tool_path;
				error_log('MPAI_WP_CLI_Tool: Loaded WP API Tool class at ' . date('H:i:s'));
			} else {
				error_log('MPAI_WP_CLI_Tool: Could not find WP API Tool file');
				throw new Exception('Could not load WP API Tool for plugin list');
			}
		}
		
		// Initialize WP API Tool
		$wp_api_tool = new MPAI_WP_API_Tool();
		error_log('MPAI_WP_CLI_Tool: Created WP API Tool instance at ' . date('H:i:s'));
		
		// Extract any flags from the command (--status=active, etc.)
		$status_filter = null;
		if (preg_match('/--status=(\w+)/', $command, $matches)) {
			$status_filter = $matches[1];
			error_log('MPAI_WP_CLI_Tool: Detected status filter: ' . $status_filter);
		}
		
		// Call get_plugins with appropriate parameters
		$api_params = array(
			'action' => 'get_plugins',
			'format' => 'table',  // Always return tabular format for CLI commands
		);
		
		if ($status_filter) {
			$api_params['status'] = $status_filter;
		}
		
		// Get timestamp for generated output
		$current_time = date('H:i:s');
		error_log('MPAI_WP_CLI_Tool: Generating plugin list at ' . $current_time);
		
		// Execute the API call to get plugins
		$result = $wp_api_tool->execute($api_params);
		
		if (is_array($result)) {
			error_log('MPAI_WP_CLI_Tool: WP API returned array result with keys: ' . implode(', ', array_keys($result)));
		} else {
			error_log('MPAI_WP_CLI_Tool: WP API did not return array result, type: ' . gettype($result));
		}
		
		// Format output
		if (is_array($result) && isset($result['table_data'])) {
			// Already formatted as table
			error_log('MPAI_WP_CLI_Tool: Found table_data in result, length: ' . strlen($result['table_data']));
			error_log('MPAI_WP_CLI_Tool: Table data preview: ' . substr($result['table_data'], 0, 100));
			return $result['table_data'];
		} elseif (is_array($result) && isset($result['plugins'])) {
			// Format plugins as table
			$plugins = $result['plugins'];
			error_log('MPAI_WP_CLI_Tool: Formatting ' . count($plugins) . ' plugins as table');
			
			// Use clean consistent formatting with tabs as separators
			$output = "Name\tStatus\tVersion\tLast Activity\n";
			
			foreach ($plugins as $plugin) {
				// Apply status filter if specified
				if ($status_filter && $plugin['status'] !== $status_filter) {
					continue;
				}
				
				$name = $plugin['name'];
				$status = $plugin['status'];
				$version = $plugin['version'];
				$activity = isset($plugin['last_activity']) ? $plugin['last_activity'] : 'No recent activity';
				
				$output .= "$name\t$status\t$version\t$activity\n";
			}
			
			error_log('MPAI_WP_CLI_Tool: Formatted table output length: ' . strlen($output));
			error_log('MPAI_WP_CLI_Tool: Table output preview: ' . substr($output, 0, 100));
			return $output;
		} else {
			// Return raw result if not in expected format
			error_log('MPAI_WP_CLI_Tool: Unexpected result format, returning JSON');
			return "Name\tStatus\tVersion\tLast Activity\nNo plugin data available\tinactive\t-\tError retrieving data";
		}
	}
	
	/**
	 * Handle commands when WP-CLI is not available
	 *
	 * @param string $command The command to handle
	 * @param array $parameters Additional parameters
	 * @return mixed Result of the alternative command handling
	 */
	private function handle_command_without_wpcli($command, $parameters) {
		// Extract the command type
		$command_parts = explode(' ', trim($command));
		
		// Log the command being processed
		error_log('MPAI_WP_CLI_Tool: Processing command without WP-CLI: ' . $command);
		
		// Check for special case patterns regardless of command structure
		if (stripos($command, 'recent') !== false && stripos($command, 'plugin') !== false) {
			error_log('MPAI_WP_CLI_Tool: Detected request for recent plugins');
			return $this->handle_recent_plugins_command($command, $parameters);
		}
		
		if (stripos($command, 'plugin') !== false && 
		   (stripos($command, 'status') !== false || stripos($command, 'info') !== false)) {
			error_log('MPAI_WP_CLI_Tool: Detected plugin status/info request');
			return $this->handle_plugin_status_command($command, $parameters);
		}
		
		// Handle different command types based on structure
		if (count($command_parts) >= 3 && $command_parts[0] === 'wp') {
			// WP Plugin commands
			if ($command_parts[1] === 'plugin') {
				// wp plugin commands
				if ($command_parts[2] === 'list') {
					return $this->handle_plugin_list_command($command, $parameters);
				}
				
				if ($command_parts[2] === 'status') {
					return $this->handle_plugin_status_command($command, $parameters);
				}
			}
			
			// PHP Info commands
			if ($command_parts[1] === 'php' && $command_parts[2] === 'info') {
				return $this->handle_php_info_command($command, $parameters);
			}
			
			// Site health commands
			if (($command_parts[1] === 'site' && $command_parts[2] === 'health') ||
				$command_parts[1] === 'system-info') {
				return $this->handle_site_health_command($command, $parameters);
			}
			
			// Database info commands
			if ($command_parts[1] === 'db' && $command_parts[2] === 'info') {
				return $this->handle_db_info_command($command, $parameters);
			}
		}
		
		// Still handle common query patterns regardless of command structure
		if (stripos($command, 'php') !== false && stripos($command, 'version') !== false) {
			return $this->handle_php_info_command($command, $parameters);
		}
		
		// Default message for unsupported commands
		return "The AI assistant cannot directly run WP-CLI commands on your server. Command '$command' cannot be executed directly.\n"
			 . "Please use WordPress API tools instead (wp_api, memberpress_info, plugin_logs, etc.).";
	}
	
	/**
	 * Handle recent plugins command
	 *
	 * @param string $command The original command
	 * @param array $parameters Additional parameters
	 * @return string Formatted recent plugins output
	 */
	private function handle_recent_plugins_command($command, $parameters) {
		error_log('MPAI_WP_CLI_Tool: Getting recent plugins');
		
		// Ensure plugin functions are available
		if (!function_exists('get_plugins')) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		
		if (!function_exists('is_plugin_active')) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		
		$output = "Recent Plugin Activity:\n\n";
		
		// Get all plugins
		$all_plugins = get_plugins();
		
		// Get recently activated/deactivated plugins
		$recently_activated = get_option('recently_activated', array());
		
		// Try to get plugin logs if the plugin logger is available
		$plugin_logs_available = false;
		$recent_plugin_logs = array();
		
		if (function_exists('mpai_init_plugin_logger')) {
			try {
				error_log('MPAI_WP_CLI_Tool: Attempting to get data from plugin logger');
				$plugin_logger = mpai_init_plugin_logger();
				
				if ($plugin_logger) {
					// Get logs from the last 30 days, prioritizing recent activations
					$logs = $plugin_logger->get_logs(array(
						'limit' => 30,
						'date_from' => date('Y-m-d H:i:s', strtotime('-30 days')),
						'orderby' => 'date_time',
						'order' => 'DESC',
					));
					
					if (!empty($logs)) {
						$plugin_logs_available = true;
						$recent_plugin_logs = $logs;
						error_log('MPAI_WP_CLI_Tool: Got ' . count($logs) . ' logs from plugin logger');
					} else {
						// Try to create the table and seed it if it doesn't exist
						if (method_exists($plugin_logger, 'maybe_create_table')) {
							$plugin_logger->maybe_create_table(true);
							
							// Try to get logs again
							$logs = $plugin_logger->get_logs(array(
								'limit' => 30,
								'date_from' => date('Y-m-d H:i:s', strtotime('-30 days')),
								'orderby' => 'date_time',
								'order' => 'DESC',
							));
							
							if (!empty($logs)) {
								$plugin_logs_available = true;
								$recent_plugin_logs = $logs;
								error_log('MPAI_WP_CLI_Tool: Got ' . count($logs) . ' logs after table creation');
							}
						}
					}
				}
			} catch (Exception $e) {
				error_log('MPAI_WP_CLI_Tool: Error getting plugin logs: ' . $e->getMessage());
			}
		}
		
		// If we have plugin logs, show those first
		if ($plugin_logs_available && !empty($recent_plugin_logs)) {
			// Filter to recent activations first
			$recent_activations = array_filter($recent_plugin_logs, function($log) {
				return $log['action'] === 'activated';
			});
			
			if (!empty($recent_activations)) {
				$output .= "Recently Activated Plugins:\n";
				$shown_logs = array();
				
				foreach ($recent_activations as $log) {
					// Skip if we've already shown this plugin's activation
					if (isset($shown_logs[$log['plugin_slug']])) continue;
					
					$plugin_name = $log['plugin_name'];
					$date = date('F j, Y', strtotime($log['date_time']));
					$time_ago = human_time_diff(strtotime($log['date_time']), time()) . ' ago';
					$version = !empty($log['plugin_version']) ? " v{$log['plugin_version']}" : "";
					$output .= "- {$plugin_name}{$version}: Activated {$time_ago} ({$date})\n";
					
					// Mark this plugin's activation as shown
					$shown_logs[$log['plugin_slug']] = true;
					
					// Limit to 5 entries
					if (count($shown_logs) >= 5) break;
				}
				
				$output .= "\n";
			}
			
			// Show recent deactivations next
			$recent_deactivations = array_filter($recent_plugin_logs, function($log) {
				return $log['action'] === 'deactivated';
			});
			
			if (!empty($recent_deactivations)) {
				$output .= "Recently Deactivated Plugins:\n";
				$shown_logs = array();
				
				foreach ($recent_deactivations as $log) {
					// Skip if we've already shown this plugin's deactivation
					if (isset($shown_logs[$log['plugin_slug']])) continue;
					
					$plugin_name = $log['plugin_name'];
					$date = date('F j, Y', strtotime($log['date_time']));
					$time_ago = human_time_diff(strtotime($log['date_time']), time()) . ' ago';
					$version = !empty($log['plugin_version']) ? " v{$log['plugin_version']}" : "";
					$output .= "- {$plugin_name}{$version}: Deactivated {$time_ago} ({$date})\n";
					
					// Mark this plugin's deactivation as shown
					$shown_logs[$log['plugin_slug']] = true;
					
					// Limit to 5 entries
					if (count($shown_logs) >= 5) break;
				}
				
				$output .= "\n";
			}
			
			// Show other recent plugin activity
			$other_activities = array_filter($recent_plugin_logs, function($log) {
				return !in_array($log['action'], ['activated', 'deactivated']);
			});
			
			if (!empty($other_activities)) {
				$output .= "Other Recent Plugin Activity:\n";
				$shown_logs = array();
				
				foreach ($other_activities as $log) {
					// Skip if we've already shown this plugin activity
					$plugin_key = $log['plugin_slug'] . '_' . $log['action'];
					if (isset($shown_logs[$plugin_key])) continue;
					
					$action = ucfirst($log['action']);
					$plugin_name = $log['plugin_name'];
					$time_ago = human_time_diff(strtotime($log['date_time']), time()) . ' ago';
					$output .= "- {$plugin_name}: {$action} {$time_ago}\n";
					
					// Mark this plugin's action as shown
					$shown_logs[$plugin_key] = true;
					
					// Limit to 5 entries
					if (count($shown_logs) >= 5) break;
				}
				
				$output .= "\n";
			}
		}
		// Show recently deactivated plugins from WordPress option if no logs are available
		else if (!empty($recently_activated)) {
			$output .= "Recently Deactivated Plugins (from WordPress):\n";
			$count = 0;
			
			foreach ($recently_activated as $plugin => $time) {
				if (isset($all_plugins[$plugin])) {
					$plugin_data = $all_plugins[$plugin];
					$plugin_name = $plugin_data['Name'];
					$version = $plugin_data['Version'];
					$time_str = human_time_diff($time, time()) . ' ago';
					$output .= "- {$plugin_name} v{$version}: Deactivated {$time_str}\n";
					
					if (++$count >= 5) break;
				}
			}
			
			$output .= "\n";
		}
		
		// If no plugin logs or recently activated plugins found
		if (!$plugin_logs_available && empty($recently_activated)) {
			$output .= "No recent plugin activity detected in logs.\n";
			$output .= "Active plugins based on current WordPress state:\n\n";
			
			// Get active plugins
			$active_plugins = get_option('active_plugins', array());
			
			foreach ($active_plugins as $plugin) {
				if (isset($all_plugins[$plugin])) {
					$plugin_data = $all_plugins[$plugin];
					$plugin_name = $plugin_data['Name'];
					$version = $plugin_data['Version'];
					$output .= "- $plugin_name (v$version)\n";
				}
			}
		}
		
		return $output;
	}
	
	/**
	 * Handle PHP info command using internal PHP functions
	 *
	 * @param string $command The original command
	 * @param array $parameters Additional parameters
	 * @return string Formatted PHP info output
	 */
	private function handle_php_info_command($command, $parameters) {
		error_log('MPAI_WP_CLI_Tool: Getting PHP info');
		
		// Get PHP version
		$php_version = phpversion();
		$php_uname = php_uname();
		$php_sapi = php_sapi_name();
		
		// Get PHP configuration settings
		$memory_limit = ini_get('memory_limit');
		$max_execution_time = ini_get('max_execution_time');
		$upload_max_filesize = ini_get('upload_max_filesize');
		$post_max_size = ini_get('post_max_size');
		$max_input_vars = ini_get('max_input_vars');
		
		// Get loaded extensions
		$loaded_extensions = get_loaded_extensions();
		sort($loaded_extensions);
		$extensions_str = implode(', ', array_slice($loaded_extensions, 0, 15)) . '...';
		
		// Format into a table
		$output = "PHP Information:\n\n";
		$output .= "PHP Version: $php_version\n";
		$output .= "System: $php_uname\n";
		$output .= "SAPI: $php_sapi\n";
		$output .= "\nImportant Settings:\n";
		$output .= "memory_limit: $memory_limit\n";
		$output .= "max_execution_time: $max_execution_time seconds\n";
		$output .= "upload_max_filesize: $upload_max_filesize\n";
		$output .= "post_max_size: $post_max_size\n";
		$output .= "max_input_vars: $max_input_vars\n";
		$output .= "\nExtensions: $extensions_str\n";
		
		return $output;
	}
	
	/**
	 * Handle site health command using WordPress functions
	 *
	 * @param string $command The original command
	 * @param array $parameters Additional parameters
	 * @return string Formatted site health output
	 */
	private function handle_site_health_command($command, $parameters) {
		error_log('MPAI_WP_CLI_Tool: Getting site health info');
		
		global $wp_version;
		global $wpdb;
		
		$output = "WordPress Site Health Information:\n\n";
		
		// WordPress core information
		$output .= "WordPress Version: $wp_version\n";
		$output .= "Site URL: " . get_site_url() . "\n";
		$output .= "Home URL: " . get_home_url() . "\n";
		$output .= "Is Multisite: " . (is_multisite() ? 'Yes' : 'No') . "\n";
		
		// Database information
		$output .= "\nDatabase:\n";
		$db_version = $wpdb->db_version();
		$output .= "MySQL Version: $db_version\n";
		$output .= "Database Prefix: " . $wpdb->prefix . "\n";
		
		// Theme information
		$theme = wp_get_theme();
		$output .= "\nActive Theme:\n";
		$output .= "Name: " . $theme->get('Name') . "\n";
		$output .= "Version: " . $theme->get('Version') . "\n";
		
		// Plugin counts
		if (!function_exists('get_plugins')) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$all_plugins = get_plugins();
		$active_plugins = get_option('active_plugins');
		
		$output .= "\nPlugin Status:\n";
		$output .= "Active Plugins: " . count($active_plugins) . "\n";
		$output .= "Total Plugins: " . count($all_plugins) . "\n";
		
		// Recently activated plugins
		$recently_activated = get_option('recently_activated');
		if (!empty($recently_activated)) {
			$output .= "\nRecently Activated Plugins:\n";
			$count = 0;
			foreach ($recently_activated as $plugin => $time) {
				if ($count++ < 5) {
					$plugin_data = isset($all_plugins[$plugin]) ? $all_plugins[$plugin] : array('Name' => $plugin);
					$plugin_name = isset($plugin_data['Name']) ? $plugin_data['Name'] : $plugin;
					$time_str = human_time_diff(time(), $time) . ' ago';
					$output .= "- $plugin_name (Deactivated $time_str)\n";
				}
			}
		}
		
		return $output;
	}
	
	/**
	 * Handle database info command
	 *
	 * @param string $command The original command
	 * @param array $parameters Additional parameters
	 * @return string Formatted database info output
	 */
	private function handle_db_info_command($command, $parameters) {
		error_log('MPAI_WP_CLI_Tool: Getting database info');
		
		global $wpdb;
		
		$output = "Database Information:\n\n";
		
		// Get database information
		$db_version = $wpdb->db_version();
		$db_name = defined('DB_NAME') ? DB_NAME : 'unknown';
		$db_host = defined('DB_HOST') ? DB_HOST : 'unknown';
		$db_user = defined('DB_USER') ? DB_USER : 'unknown';
		$db_charset = defined('DB_CHARSET') ? DB_CHARSET : $wpdb->charset;
		$db_collate = $wpdb->collate;
		
		$output .= "MySQL Version: $db_version\n";
		$output .= "Database Name: $db_name\n";
		$output .= "Database Host: $db_host\n";
		$output .= "Database User: $db_user\n";
		$output .= "Database Charset: $db_charset\n";
		$output .= "Database Collation: " . ($db_collate ?: 'Not Set') . "\n";
		$output .= "Table Prefix: " . $wpdb->prefix . "\n";
		
		// Get table statistics
		$tables = $wpdb->get_results('SHOW TABLES', ARRAY_N);
		$table_count = count($tables);
		
		$output .= "\nTable Statistics:\n";
		$output .= "Total Tables: $table_count\n";
		
		// Get some basic WordPress table counts
		$post_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts}");
		$user_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}");
		$comment_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->comments}");
		$option_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->options}");
		
		$output .= "Posts Table Count: $post_count\n";
		$output .= "Users Table Count: $user_count\n";
		$output .= "Comments Table Count: $comment_count\n";
		$output .= "Options Table Count: $option_count\n";
		
		return $output;
	}
	
	/**
	 * Handle plugin status command
	 *
	 * @param string $command The original command
	 * @param array $parameters Additional parameters
	 * @return string Formatted plugin status output
	 */
	private function handle_plugin_status_command($command, $parameters) {
		error_log('MPAI_WP_CLI_Tool: Getting plugin status');
		
		// Ensure plugin functions are available
		if (!function_exists('get_plugins')) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		
		if (!function_exists('is_plugin_active')) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		
		// System information first for PHP version queries
		$php_version = phpversion();
		$wp_version = get_bloginfo('version');
		
		$output = "WordPress System Status:\n\n";
		$output .= "PHP Version: $php_version\n";
		$output .= "WordPress Version: $wp_version\n";
		
		// Add more PHP environment information
		$output .= "\nPHP Information:\n";
		$output .= "Memory Limit: " . ini_get('memory_limit') . "\n";
		$output .= "Max Execution Time: " . ini_get('max_execution_time') . " seconds\n";
		$output .= "Upload Max Filesize: " . ini_get('upload_max_filesize') . "\n";
		$output .= "Post Max Size: " . ini_get('post_max_size') . "\n";
		
		// Get all plugins
		$all_plugins = get_plugins();
		$active_plugins = get_option('active_plugins');
		$recently_activated = get_option('recently_activated');
		
		// Check for available updates
		$update_plugins = get_site_transient('update_plugins');
		$updates_available = array();
		if ($update_plugins && !empty($update_plugins->response)) {
			$updates_available = $update_plugins->response;
		}
		
		// Plugin statistics
		$output .= "\nPlugin Status Information:\n";
		$output .= "Total Plugins: " . count($all_plugins) . "\n";
		$output .= "Active Plugins: " . count($active_plugins) . "\n";
		$output .= "Inactive Plugins: " . (count($all_plugins) - count($active_plugins)) . "\n";
		$output .= "Updates Available: " . count($updates_available) . "\n";
		
		// Get recent activity from plugin logs
		$plugin_logs_available = false;
		$recent_plugin_logs = array();
		
		if (function_exists('mpai_init_plugin_logger')) {
			try {
				error_log('MPAI_WP_CLI_Tool: Attempting to get data from plugin logger for plugin status');
				$plugin_logger = mpai_init_plugin_logger();
				
				if ($plugin_logger) {
					// Get logs from the last 30 days
					$logs = $plugin_logger->get_logs(array(
						'limit' => 20, // Increased limit to get more activity
						'date_from' => date('Y-m-d H:i:s', strtotime('-30 days')),
						'orderby' => 'date_time',
						'order' => 'DESC',
					));
					
					if (!empty($logs)) {
						$plugin_logs_available = true;
						$recent_plugin_logs = $logs;
						error_log('MPAI_WP_CLI_Tool: Got ' . count($logs) . ' logs from plugin logger for status');
					} else {
						// Try to create the table and seed it if it doesn't exist
						if (method_exists($plugin_logger, 'maybe_create_table')) {
							$plugin_logger->maybe_create_table(true);
							
							// Try to get logs again
							$logs = $plugin_logger->get_logs(array(
								'limit' => 20,
								'date_from' => date('Y-m-d H:i:s', strtotime('-30 days')),
								'orderby' => 'date_time',
								'order' => 'DESC',
							));
							
							if (!empty($logs)) {
								$plugin_logs_available = true;
								$recent_plugin_logs = $logs;
								error_log('MPAI_WP_CLI_Tool: Got ' . count($logs) . ' logs after table creation');
							}
						}
					}
				}
			} catch (Exception $e) {
				error_log('MPAI_WP_CLI_Tool: Error getting plugin logs for status: ' . $e->getMessage());
			}
		}
		
		// Show recently activated plugins from logs
		if ($plugin_logs_available && !empty($recent_plugin_logs)) {
			// Filter to recent activations first
			$recent_activations = array_filter($recent_plugin_logs, function($log) {
				return $log['action'] === 'activated';
			});
			
			if (!empty($recent_activations)) {
				$output .= "\nRecently Activated Plugins:\n";
				$shown_logs = array();
				
				foreach ($recent_activations as $log) {
					// Skip if we've already shown this plugin's activation
					if (isset($shown_logs[$log['plugin_slug']])) continue;
					
					$plugin_name = $log['plugin_name'];
					$time_ago = human_time_diff(strtotime($log['date_time']), time()) . ' ago';
					$version = !empty($log['plugin_version']) ? " v{$log['plugin_version']}" : "";
					$output .= "- {$plugin_name}{$version}: Activated {$time_ago}\n";
					
					// Mark this plugin's activation as shown
					$shown_logs[$log['plugin_slug']] = true;
					
					// Limit to 5 entries
					if (count($shown_logs) >= 5) break;
				}
			}
		}
		
		// Show recently deactivated plugins
		if (!empty($recently_activated) || ($plugin_logs_available && !empty($recent_plugin_logs))) {
			$output .= "\nRecently Deactivated Plugins:\n";
			$shown_deactivations = array();
			
			// First check from plugin logs
			if ($plugin_logs_available && !empty($recent_plugin_logs)) {
				$recent_deactivations = array_filter($recent_plugin_logs, function($log) {
					return $log['action'] === 'deactivated';
				});
				
				foreach ($recent_deactivations as $log) {
					// Skip if we've already shown this plugin's deactivation
					$plugin_slug = $log['plugin_slug'];
					if (isset($shown_deactivations[$plugin_slug])) continue;
					
					$plugin_name = $log['plugin_name'];
					$time_ago = human_time_diff(strtotime($log['date_time']), time()) . ' ago';
					$version = !empty($log['plugin_version']) ? " v{$log['plugin_version']}" : "";
					$output .= "- {$plugin_name}{$version}: Deactivated {$time_ago}\n";
					
					// Mark this plugin as shown
					$shown_deactivations[$plugin_slug] = true;
					
					// Limit to 5 entries
					if (count($shown_deactivations) >= 5) break;
				}
			}
			
			// Add from WordPress option if needed
			if (count($shown_deactivations) < 5 && !empty($recently_activated)) {
				foreach ($recently_activated as $plugin => $time) {
					if (count($shown_deactivations) >= 5) break;
					
					// Skip if we've already shown this plugin
					$plugin_slug = dirname($plugin);
					if (isset($shown_deactivations[$plugin_slug])) continue;
					
					if (isset($all_plugins[$plugin])) {
						$plugin_data = $all_plugins[$plugin];
						$plugin_name = $plugin_data['Name'];
						$version = $plugin_data['Version'];
						$time_str = human_time_diff($time, time()) . ' ago';
						$output .= "- {$plugin_name} v{$version}: Deactivated {$time_str}\n";
						
						// Mark this plugin as shown
						$shown_deactivations[$plugin_slug] = true;
					}
				}
			}
		}
		
		// Plugins needing updates
		if (!empty($updates_available)) {
			$output .= "\nPlugins Needing Updates:\n";
			foreach ($updates_available as $plugin_file => $plugin_data) {
				if (isset($all_plugins[$plugin_file])) {
					$plugin_info = $all_plugins[$plugin_file];
					$plugin_name = $plugin_info['Name'];
					$current_version = $plugin_info['Version'];
					$new_version = $plugin_data->new_version;
					$output .= "- $plugin_name: $current_version → $new_version\n";
				}
			}
		}
		
		// List active plugins
		$output .= "\nActive Plugins:\n";
		$count = 0;
		
		// Sort plugins by name for better readability
		$plugin_names = array();
		foreach ($active_plugins as $plugin) {
			if (isset($all_plugins[$plugin])) {
				$plugin_info = $all_plugins[$plugin];
				$plugin_names[$plugin] = $plugin_info['Name'];
			}
		}
		
		// Sort by plugin name
		asort($plugin_names);
		
		// Output the formatted plugins list
		foreach ($plugin_names as $plugin => $name) {
			if ($count++ < 15 && isset($all_plugins[$plugin])) {
				$plugin_info = $all_plugins[$plugin];
				$plugin_name = $plugin_info['Name'];
				$version = $plugin_info['Version'];
				
				// Check if we have log data about this plugin
				$activity_info = '';
				if ($plugin_logs_available) {
					foreach ($recent_plugin_logs as $log) {
						if ($log['plugin_name'] === $plugin_name) {
							$activity_info = ' - ' . ucfirst($log['action']) . ' ' . 
								human_time_diff(strtotime($log['date_time']), time()) . ' ago';
							break;
						}
					}
				}
				
				$output .= "- $plugin_name v$version$activity_info\n";
			}
		}
		
		if (count($active_plugins) > 15) {
			$output .= "... and " . (count($active_plugins) - 15) . " more\n";
		}
		
		return $output;
	}
	
	/**
	 * Validate that a command is allowed
	 *
	 * @param string $command WP-CLI command
	 * @return bool Whether command is valid
	 */
	private function validate_command( $command ) {
		// Sanitize the command
		$sanitized_command = $this->sanitize_command( $command );
		
		// Check against allowlist
		foreach ( $this->allowed_command_prefixes as $prefix ) {
			if ( strpos( $sanitized_command, $prefix ) === 0 ) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Sanitize a command to prevent injection
	 *
	 * @param string $command Command to sanitize
	 * @return string Sanitized command
	 */
	private function sanitize_command( $command ) {
		// Remove potentially dangerous characters
		$command = preg_replace( '/[;&|><]/', '', $command );
		
		// Ensure command starts with 'wp '
		if ( strpos( $command, 'wp ' ) !== 0 ) {
			$command = 'wp ' . $command;
		}
		
		return trim( $command );
	}
	
	/**
	 * Build the full command with proper escaping
	 *
	 * @param string $command WP-CLI command
	 * @return string Full bash command
	 */
	private function build_command( $command ) {
		// Format as JSON for easier parsing if possible
		if ( strpos( $command, '--format=' ) === false && strpos( $command, 'help' ) === false ) {
			$command .= ' --format=json';
		}
		
		// Escape the command
		$escaped_command = escapeshellcmd( $command );
		
		// Add timeout
		$full_command = "timeout {$this->execution_timeout}s {$escaped_command}";
		
		return $full_command;
	}
	
	/**
	 * Parse command output into usable format
	 *
	 * @param array $output Command output lines
	 * @param string $format Desired output format
	 * @return mixed Parsed output
	 */
	private function parse_output( $output, $format ) {
		$raw_output = implode( "\n", $output );
		
		switch ( $format ) {
			case 'json':
				$decoded = json_decode( $raw_output, true );
				if ( $decoded && json_last_error() === JSON_ERROR_NONE ) {
					return $decoded;
				}
				// Fall through to array if not valid JSON
				
			case 'array':
				return $output;
				
			case 'text':
			default:
				return $raw_output;
		}
	}
}
