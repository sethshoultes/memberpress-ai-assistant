<?php
/**
 * WP-CLI Executor
 *
 * Executes WP-CLI commands
 * 
 * SECURITY NOTE: This implementation uses a permissive blacklist approach rather than
 * a restrictive whitelist. This follows the Command System Rewrite Plan's KISS principle
 * and is more user-friendly while still maintaining security by blocking dangerous patterns.
 *
 * @package MemberPress AI Assistant
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * WP-CLI Executor Class
 */
class MPAI_WP_CLI_Executor {

    /**
     * Execution timeout in seconds
     *
     * @var int
     */
    private $timeout = 30;

    /**
     * Logger instance
     *
     * @var object
     */
    private $logger;

    /**
     * List of dangerous command patterns to block
     * 
     * Following the Command System Rewrite Plan that adopts a permissive-by-default approach,
     * we block commands that are explicitly dangerous rather than starting with an allowlist.
     * 
     * @var array
     */
    private $dangerous_patterns = [
        '/rm\s+-rf/i',                   // Recursive file deletion
        '/DROP\s+TABLE/i',               // SQL table drops
        '/system\s*\(/i',                // PHP system calls
        '/(?:curl|wget)\s+.*-o/i',       // File downloads
        '/eval\s*\(/i',                  // PHP code evaluation
        '/<\?php/i',                     // PHP code inclusion
        '/>(\\/dev\\/null|\\/dev\\/zero)/i', // Redirects to system devices
        '/:(){ :|:& };:/i',              // Fork bomb
        '/sudo /i',                      // Sudo commands
        '/shutdown/i',                   // System shutdown
        '/reboot/i',                     // System reboot
        '/mkfs/i',                       // Filesystem formatting
        '/dd\s+if/i',                    // Disk operations
        '/shred/i',                      // Secure deletion
        '/chmod\s+777/i',                // Insecure permissions
        '/chmod\s+-R/i',                 // Recursive permission changes
        '/chown\s+-R/i',                 // Recursive ownership changes
        '/alias\s+/i',                   // Shell alias commands
        '/exec\s*\(/i',                  // PHP exec function
        '/passthru\s*\(/i',              // PHP passthru function
        '/shell_exec\s*\(/i',            // PHP shell_exec function
        '/popen\s*\(/i',                 // PHP popen function
        '/proc_open\s*\(/i',             // PHP proc_open function
        '/pcntl_exec\s*\(/i',            // PHP pcntl_exec function
        '/\|\s*bash/i',                  // Piping to bash
        '/`.*`/i',                       // Backtick command execution
        '/>\s*\/etc\/passwd/i',          // Writing to /etc/passwd
        '/>\s*\/etc\/shadow/i',          // Writing to /etc/shadow
        '/>\s*\/etc\/hosts/i',           // Writing to /etc/hosts
    ];

    /**
     * Constructor
     */
    public function __construct() {
        // Initialize logger
        $this->init_logger();
        
        // Allow plugins to extend the dangerous patterns list
        $this->dangerous_patterns = apply_filters('mpai_blocked_cli_patterns', $this->dangerous_patterns);
    }
    
    /**
     * Initialize logger
     */
    private function init_logger() {
        // Create a simple logger class
        $this->logger = new class() {
            public function info($message) {
                mpai_log_info($message, 'wp-cli');
            }
            
            public function error($message) {
                mpai_log_error($message, 'wp-cli');
            }
            
            public function debug($message) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    mpai_log_debug($message, 'wp-cli');
                }
            }
        };
    }


    /**
     * Execute a WP-CLI command
     *
     * @param string $command Command to execute
     * @param array $parameters Additional parameters
     * @return array Execution result
     */
    public function execute($command, $parameters = []) {
    	$this->logger->info('Executing WP-CLI command: ' . $command);
    	
    	// Validate the command for security
    	if (!$this->validate_command($command)) {
    		$this->logger->error('Command security validation failed: ' . $command);
    		return [
    			'success' => false,
    			'output' => 'Command could not be executed for security reasons. Please try a different command.',
    			'error' => 'Command blocked by security validation',
    			'command' => $command
    		];
    	}
    	try {
    		mpai_log_info('Executing command: ' . $command, 'wp-cli');
    		
    		// Set custom timeout if provided
    		if (isset($parameters['timeout'])) {
    			$this->timeout = min((int)$parameters['timeout'], 60); // Max 60 seconds
    		}
    		
    		// Parse command type and route to appropriate handler
    		$command = trim($command);
    		$this->logger->info('Analyzing command type: ' . $command);
    		
    		// Check for plugin logs command first - this should take precedence
    		if ($this->is_plugin_logs_query($command)) {
    			$this->logger->info('Handling as plugin logs command');
    			return $this->handle_plugin_logs_command($parameters);
    		} else if ($this->is_php_version_query($command)) {
    			$this->logger->info('Handling as PHP version query');
    			return $this->get_php_version_info();
    		} elseif ($this->is_plugin_query($command)) {
    			$this->logger->info('Handling as plugin command');
    			return $this->handle_plugin_command($command, $parameters);
    		} elseif ($this->is_system_query($command)) {
    			$this->logger->info('Handling as system command');
    			return $this->handle_system_command($command, $parameters);
    		}
    		
    		$this->logger->info('Handling as general WP-CLI command');
    		
    		// Check for special cases that should bypass WP-CLI
    		if (trim($command) === 'wp plugin list' ||
    			trim($command) === 'wp plugins' ||
    			trim($command) === 'plugins') {
    			// Special handling for plugin list command
    			$this->logger->info('Using direct WordPress API for plugin list');
    			return $this->get_plugin_list($parameters);
    		}
    		
    		// Check if exec() is available or if we're in safe mode
    		if (!function_exists('exec') || ini_get('safe_mode')) {
    			$this->logger->warning('exec() function is disabled or safe mode is enabled, using WP-CLI API fallback');
    			return $this->execute_with_wp_cli_api($command, $parameters);
    		}
    		
    		// Build the command
    		$wp_cli_command = $this->build_command($command, $parameters);
    		
    		// Execute the command
    		$output = [];
    		$return_var = 0;
    		$this->logger->info('Executing: ' . $wp_cli_command);
    		$last_line = exec($wp_cli_command, $output, $return_var);
    		
    		// Handle the result
    		if ($return_var !== 0) {
    			mpai_log_error('Command failed with code ' . $return_var . ': ' . implode("\n", $output), 'wp-cli');
    			return [
    				'success' => false,
    				'output' => implode("\n", $output),
    				'return_code' => $return_var,
    				'command' => $command
    			];
    		}
    		
    		// Format the output based on the requested format
    		$format = isset($parameters['format']) ? $parameters['format'] : 'text';
    		$formatted_output = $this->format_output($output, $format);
    		
    		return [
    			'success' => true,
    			'output' => $formatted_output,
    			'return_code' => $return_var,
    			'command' => $command
    		];
    	} catch (Exception $e) {
    		$this->logger->error('Command execution error: ' . $e->getMessage());
    		return [
    			'success' => false,
    			'output' => 'Error executing command: ' . $e->getMessage(),
    			'error' => $e->getMessage(),
    			'command' => $command
    		];
    	}
    }
    
    /**
     * Execute a command using WordPress API functions directly
     *
     * This is a replacement for the WP-CLI API method that doesn't rely on the WP_CLI class
     *
     * @param string $command Command to execute
     * @param array $parameters Additional parameters
     * @return array Execution result
     */
    private function execute_with_wp_cli_api($command, $parameters = []) {
    	$this->logger->info('Executing command with WordPress API: ' . $command);
    	
    	// Parse the command to extract the command type and arguments
    	$command = trim($command);
    	
    	// Remove 'wp ' prefix if present
    	if (strpos($command, 'wp ') === 0) {
    		$command = substr($command, 3);
    	}
    	
    	// Split the command into parts
    	$parts = explode(' ', $command);
    	$command_type = $parts[0] ?? '';
    	
    	// Handle different command types
    	switch ($command_type) {
    		case 'plugin':
    			return $this->handle_plugin_command_direct($parts);
    		case 'user':
    			return $this->handle_user_command_direct($parts);
    		case 'post':
    			return $this->handle_post_command_direct($parts);
    		case 'option':
    			return $this->handle_option_command_direct($parts);
    		case 'site':
    		case 'core':
    			return $this->handle_system_command_direct($parts);
    		case 'theme':
    			return $this->handle_theme_command_direct($parts);
    		case 'menu':
    			return $this->handle_menu_command_direct($parts);
    		case 'comment':
    			return $this->handle_comment_command_direct($parts);
    		case 'db':
    			return $this->handle_db_command_direct($parts);
    		default:
    			$this->logger->warning('Unsupported command type: ' . $command_type);
    			return [
    				'success' => false,
    				'output' => 'Unsupported command type: ' . $command_type,
    				'error' => 'Unsupported command type',
    				'command' => $command,
    				'method' => 'wp_api'
    			];
    	}
    }
    
    /**
     * Handle plugin commands directly using WordPress API
     *
     * @param array $command_parts Command parts
     * @return array Result
     */
    private function handle_plugin_command_direct($command_parts) {
    	$action = $command_parts[1] ?? '';
    	
    	// Ensure plugin functions are available
    	if (!function_exists('get_plugins')) {
    		require_once ABSPATH . 'wp-admin/includes/plugin.php';
    	}
    	
    	// Ensure plugin activation functions are available
    	if (!function_exists('activate_plugin') || !function_exists('deactivate_plugins')) {
    		require_once ABSPATH . 'wp-admin/includes/plugin.php';
    	}
    	
    	switch ($action) {
    		case 'list':
    			// Get plugins
    			$all_plugins = get_plugins();
    			$active_plugins = get_option('active_plugins');
    			
    			// Parse additional parameters
    			$status_filter = null;
    			$format = 'table';
    			
    			for ($i = 2; $i < count($command_parts); $i++) {
    				if (strpos($command_parts[$i], '--status=') === 0) {
    					$status_filter = str_replace('--status=', '', $command_parts[$i]);
    				} else if (strpos($command_parts[$i], '--format=') === 0) {
    					$format = str_replace('--format=', '', $command_parts[$i]);
    				}
    			}
    			
    			// Format output as a table
    			$output = "NAME\tSTATUS\tVERSION\tAUTHOR\tDESCRIPTION\n";
    			
    			foreach ($all_plugins as $plugin_path => $plugin_data) {
    				$plugin_status = in_array($plugin_path, $active_plugins) ? 'active' : 'inactive';
    				
    				// Apply status filter if specified
    				if ($status_filter !== null && $plugin_status !== $status_filter) {
    					continue;
    				}
    				
    				$name = isset($plugin_data['Name']) ? $plugin_data['Name'] : 'Unknown';
    				$version = isset($plugin_data['Version']) ? $plugin_data['Version'] : '';
    				$author = isset($plugin_data['Author']) ? $plugin_data['Author'] : '';
    				$description = isset($plugin_data['Description']) && is_string($plugin_data['Description']) ?
    							  (strlen($plugin_data['Description']) > 40 ?
    							  substr($plugin_data['Description'], 0, 40) . '...' :
    							  $plugin_data['Description']) : '';
    				
    				$output .= "$name\t$plugin_status\t$version\t$author\t$description\n";
    			}
    			
    			return [
    				'success' => true,
    				'output' => $output,
    				'command' => 'wp plugin list',
    				'method' => 'wp_api',
    				'command_type' => 'plugin_list',
    				'result' => $output
    			];
    			
    		case 'status':
    			// Get plugin information
    			$all_plugins = get_plugins();
    			$active_plugins = get_option('active_plugins');
    			
    			$output = "Plugin Statistics:\n";
    			$output .= "Total Plugins: " . count($all_plugins) . "\n";
    			$output .= "Active Plugins: " . count($active_plugins) . "\n";
    			$output .= "Inactive Plugins: " . (count($all_plugins) - count($active_plugins)) . "\n\n";
    			
    			// List active plugins
    			$output .= "Active Plugins:\n";
    			foreach ($active_plugins as $plugin) {
    				if (isset($all_plugins[$plugin])) {
    					$plugin_data = $all_plugins[$plugin];
    					$output .= "- {$plugin_data['Name']} v{$plugin_data['Version']}\n";
    				}
    			}
    			
    			// List inactive plugins
    			$output .= "\nInactive Plugins:\n";
    			foreach ($all_plugins as $plugin_path => $plugin_data) {
    				if (!in_array($plugin_path, $active_plugins)) {
    					$output .= "- {$plugin_data['Name']} v{$plugin_data['Version']}\n";
    				}
    			}
    			
    			return [
    				'success' => true,
    				'output' => $output,
    				'command' => 'wp plugin status',
    				'method' => 'wp_api'
    			];
    			
    		case 'is-active':
    			// Check if plugin name is provided
    			if (!isset($command_parts[2])) {
    				return [
    					'success' => false,
    					'output' => 'Plugin name is required',
    					'error' => 'Missing plugin name',
    					'command' => 'wp plugin is-active',
    					'method' => 'wp_api'
    				];
    			}
    			
    			$plugin_name = $command_parts[2];
    			
    			// Find the plugin file path
    			$plugin_file = null;
    			$all_plugins = get_plugins();
    			
    			// Check if the provided name is a direct plugin file path
    			if (isset($all_plugins[$plugin_name])) {
    				$plugin_file = $plugin_name;
    			} else {
    				// Try to find the plugin by name
    				foreach ($all_plugins as $path => $data) {
    					if (strtolower($data['Name']) === strtolower($plugin_name) ||
    						strpos(strtolower($path), strtolower($plugin_name)) !== false) {
    						$plugin_file = $path;
    						break;
    					}
    				}
    			}
    			
    			if (!$plugin_file) {
    				return [
    					'success' => false,
    					'output' => "Plugin '{$plugin_name}' not found.",
    					'error' => 'Plugin not found',
    					'command' => 'wp plugin is-active ' . $plugin_name,
    					'method' => 'wp_api'
    				];
    			}
    			
    			// Check if plugin is active
    			$active_plugins = get_option('active_plugins');
    			$is_active = in_array($plugin_file, $active_plugins);
    			
    			$output = $is_active ? "Plugin '{$plugin_name}' is active." : "Plugin '{$plugin_name}' is not active.";
    			
    			return [
    				'success' => true,
    				'output' => $output,
    				'command' => 'wp plugin is-active ' . $plugin_name,
    				'method' => 'wp_api'
    			];
    			
    		case 'get':
    			// Check if plugin name is provided
    			if (!isset($command_parts[2])) {
    				return [
    					'success' => false,
    					'output' => 'Plugin name is required',
    					'error' => 'Missing plugin name',
    					'command' => 'wp plugin get',
    					'method' => 'wp_api'
    				];
    			}
    			
    			$plugin_name = $command_parts[2];
    			
    			// Find the plugin file path
    			$plugin_file = null;
    			$all_plugins = get_plugins();
    			
    			// Check if the provided name is a direct plugin file path
    			if (isset($all_plugins[$plugin_name])) {
    				$plugin_file = $plugin_name;
    			} else {
    				// Try to find the plugin by name
    				foreach ($all_plugins as $path => $data) {
    					if (strtolower($data['Name']) === strtolower($plugin_name) ||
    						strpos(strtolower($path), strtolower($plugin_name)) !== false) {
    						$plugin_file = $path;
    						break;
    					}
    				}
    			}
    			
    			if (!$plugin_file) {
    				return [
    					'success' => false,
    					'output' => "Plugin '{$plugin_name}' not found.",
    					'error' => 'Plugin not found',
    					'command' => 'wp plugin get ' . $plugin_name,
    					'method' => 'wp_api'
    				];
    			}
    			
    			// Get plugin details
    			$plugin_data = $all_plugins[$plugin_file];
    			$active_plugins = get_option('active_plugins');
    			$is_active = in_array($plugin_file, $active_plugins);
    			
    			// Format output
    			$output = "Plugin: {$plugin_data['Name']}\n";
    			$output .= "Version: {$plugin_data['Version']}\n";
    			$output .= "Status: " . ($is_active ? 'active' : 'inactive') . "\n";
    			$output .= "Author: {$plugin_data['Author']}\n";
    			$output .= "Description: {$plugin_data['Description']}\n";
    			$output .= "Path: {$plugin_file}\n";
    			
    			return [
    				'success' => true,
    				'output' => $output,
    				'command' => 'wp plugin get ' . $plugin_name,
    				'method' => 'wp_api'
    			];
    			
    		default:
    			return [
    				'success' => false,
    				'output' => 'Unsupported plugin command: ' . $action,
    				'error' => 'Unsupported plugin command',
    				'command' => 'wp plugin ' . $action,
    				'method' => 'wp_api'
    			];
    	}
    }
    
    /**
     * Handle user commands directly using WordPress API
     *
     * @param array $command_parts Command parts
     * @return array Result
     */
    private function handle_user_command_direct($command_parts) {
    	$action = $command_parts[1] ?? '';
    	
    	switch ($action) {
    		case 'list':
    			// Default arguments
    			$args = ['number' => 10];
    			
    			// Parse additional parameters
    			for ($i = 2; $i < count($command_parts); $i++) {
    				if (strpos($command_parts[$i], '--number=') === 0) {
    					$limit = intval(str_replace('--number=', '', $command_parts[$i]));
    					$args['number'] = $limit;
    				} else if (strpos($command_parts[$i], '--role=') === 0) {
    					$role = str_replace('--role=', '', $command_parts[$i]);
    					$args['role'] = $role;
    				} else if (strpos($command_parts[$i], '--orderby=') === 0) {
    					$orderby = str_replace('--orderby=', '', $command_parts[$i]);
    					$args['orderby'] = $orderby;
    				} else if (strpos($command_parts[$i], '--order=') === 0) {
    					$order = str_replace('--order=', '', $command_parts[$i]);
    					$args['order'] = $order;
    				}
    			}
    			
    			// Get users
    			$users = get_users($args);
    			
    			// Format output as a table
    			$output = "ID\tUSER_LOGIN\tDISPLAY_NAME\tEMAIL\tROLES\tREGISTERED\n";
    			
    			foreach ($users as $user) {
    				$registered = date('Y-m-d', strtotime($user->user_registered));
    				$output .= $user->ID . "\t" . $user->user_login . "\t" .
    						  $user->display_name . "\t" . $user->user_email . "\t" .
    						  implode(', ', $user->roles) . "\t" . $registered . "\n";
    			}
    			
    			return [
    				'success' => true,
    				'output' => $output,
    				'command' => 'wp user list',
    				'method' => 'wp_api',
    				'command_type' => 'user_list',
    				'result' => $output
    			];
    		
    		case 'get':
    			// Check if user ID is provided
    			if (!isset($command_parts[2])) {
    				return [
    					'success' => false,
    					'output' => 'User ID is required',
    					'error' => 'Missing user ID',
    					'command' => 'wp user get',
    					'method' => 'wp_api'
    				];
    			}
    			
    			$user_id = intval($command_parts[2]);
    			$user = get_userdata($user_id);
    			
    			if (!$user) {
    				return [
    					'success' => false,
    					'output' => "User with ID {$user_id} not found.",
    					'error' => 'User not found',
    					'command' => 'wp user get ' . $user_id,
    					'method' => 'wp_api'
    				];
    			}
    			
    			// Format user details
    			$output = "ID: {$user->ID}\n";
    			$output .= "Username: {$user->user_login}\n";
    			$output .= "Email: {$user->user_email}\n";
    			$output .= "Display Name: {$user->display_name}\n";
    			$output .= "Roles: " . implode(', ', $user->roles) . "\n";
    			$output .= "Registered: " . date('Y-m-d', strtotime($user->user_registered)) . "\n";
    			
    			// Get user meta
    			$output .= "\nUser Meta:\n";
    			$user_meta = get_user_meta($user_id);
    			$meta_to_show = ['first_name', 'last_name', 'nickname', 'description'];
    			
    			foreach ($meta_to_show as $meta_key) {
    				if (isset($user_meta[$meta_key][0])) {
    					$output .= "- {$meta_key}: {$user_meta[$meta_key][0]}\n";
    				}
    			}
    			
    			return [
    				'success' => true,
    				'output' => $output,
    				'command' => 'wp user get ' . $user_id,
    				'method' => 'wp_api'
    			];
    		
    		case 'count':
    			// Parse additional parameters
    			$args = [];
    			
    			for ($i = 2; $i < count($command_parts); $i++) {
    				if (strpos($command_parts[$i], '--role=') === 0) {
    					$role = str_replace('--role=', '', $command_parts[$i]);
    					$args['role'] = $role;
    				}
    			}
    			
    			// Count users
    			$count = count_users();
    			$total = $count['total_users'];
    			
    			// Format output
    			$output = "Total users: {$total}\n\n";
    			$output .= "User roles:\n";
    			
    			foreach ($count['avail_roles'] as $role => $count) {
    				$output .= "- {$role}: {$count}\n";
    			}
    			
    			return [
    				'success' => true,
    				'output' => $output,
    				'command' => 'wp user count',
    				'method' => 'wp_api'
    			];
    			
    		default:
    			return [
    				'success' => false,
    				'output' => 'Unsupported user command: ' . $action,
    				'error' => 'Unsupported user command',
    				'command' => 'wp user ' . $action,
    				'method' => 'wp_api'
    			];
    	}
    }
    
    /**
     * Handle post commands directly using WordPress API
     *
     * @param array $command_parts Command parts
     * @return array Result
     */
    private function handle_post_command_direct($command_parts) {
    	$action = $command_parts[1] ?? '';
    	
    	switch ($action) {
    		case 'list':
    			// Default arguments
    			$args = [
    				'posts_per_page' => 10,
    				'post_type' => 'post',
    				'post_status' => 'publish'
    			];
    			
    			// Parse additional parameters
    			for ($i = 2; $i < count($command_parts); $i++) {
    				if (strpos($command_parts[$i], '--posts_per_page=') === 0) {
    					$limit = intval(str_replace('--posts_per_page=', '', $command_parts[$i]));
    					$args['posts_per_page'] = $limit;
    				} else if (strpos($command_parts[$i], '--post_type=') === 0) {
    					$post_type = str_replace('--post_type=', '', $command_parts[$i]);
    					$args['post_type'] = $post_type;
    				} else if (strpos($command_parts[$i], '--post_status=') === 0) {
    					$status = str_replace('--post_status=', '', $command_parts[$i]);
    					$args['post_status'] = $status;
    				} else if (strpos($command_parts[$i], '--orderby=') === 0) {
    					$orderby = str_replace('--orderby=', '', $command_parts[$i]);
    					$args['orderby'] = $orderby;
    				} else if (strpos($command_parts[$i], '--order=') === 0) {
    					$order = str_replace('--order=', '', $command_parts[$i]);
    					$args['order'] = $order;
    				}
    			}
    			
    			// Get posts
    			$posts = get_posts($args);
    			
    			// Format output as a table
    			$output = "ID\tPOST_TITLE\tPOST_DATE\tSTATUS\tAUTHOR\tPOST_TYPE\n";
    			
    			foreach ($posts as $post) {
    				$author = get_the_author_meta('display_name', $post->post_author);
    				$output .= $post->ID . "\t" . $post->post_title . "\t" .
    						  $post->post_date . "\t" . $post->post_status . "\t" .
    						  $author . "\t" . $post->post_type . "\n";
    			}
    			
    			return [
    				'success' => true,
    				'output' => $output,
    				'command' => 'wp post list',
    				'method' => 'wp_api',
    				'command_type' => 'post_list',
    				'result' => $output
    			];
    			
    		case 'get':
    			// Check if post ID is provided
    			if (!isset($command_parts[2])) {
    				return [
    					'success' => false,
    					'output' => 'Post ID is required',
    					'error' => 'Missing post ID',
    					'command' => 'wp post get',
    					'method' => 'wp_api'
    				];
    			}
    			
    			$post_id = intval($command_parts[2]);
    			$post = get_post($post_id);
    			
    			if (!$post) {
    				return [
    					'success' => false,
    					'output' => "Post with ID {$post_id} not found.",
    					'error' => 'Post not found',
    					'command' => 'wp post get ' . $post_id,
    					'method' => 'wp_api'
    				];
    			}
    			
    			// Format post details
    			$author = get_the_author_meta('display_name', $post->post_author);
    			$output = "ID: {$post->ID}\n";
    			$output .= "Title: {$post->post_title}\n";
    			$output .= "Status: {$post->post_status}\n";
    			$output .= "Type: {$post->post_type}\n";
    			$output .= "Author: {$author}\n";
    			$output .= "Date: {$post->post_date}\n";
    			$output .= "Modified: {$post->post_modified}\n";
    			$output .= "Content:\n{$post->post_content}\n";
    			
    			return [
    				'success' => true,
    				'output' => $output,
    				'command' => 'wp post get ' . $post_id,
    				'method' => 'wp_api'
    			];
    			
    		case 'count':
    			// Default arguments
    			$args = [
    				'post_type' => 'post',
    				'post_status' => 'publish'
    			];
    			
    			// Parse additional parameters
    			for ($i = 2; $i < count($command_parts); $i++) {
    				if (strpos($command_parts[$i], '--post_type=') === 0) {
    					$post_type = str_replace('--post_type=', '', $command_parts[$i]);
    					$args['post_type'] = $post_type;
    				} else if (strpos($command_parts[$i], '--post_status=') === 0) {
    					$status = str_replace('--post_status=', '', $command_parts[$i]);
    					$args['post_status'] = $status;
    				}
    			}
    			
    			// Count posts
    			$count = wp_count_posts($args['post_type']);
    			
    			// Format output
    			$output = "Post counts for type '{$args['post_type']}':\n\n";
    			
    			foreach ($count as $status => $count) {
    				$output .= "{$status}: {$count}\n";
    			}
    			
    			return [
    				'success' => true,
    				'output' => $output,
    				'command' => 'wp post count',
    				'method' => 'wp_api'
    			];
    			
    		case 'meta':
    			// Check if post ID is provided
    			if (!isset($command_parts[2]) || $command_parts[2] !== 'list' || !isset($command_parts[3])) {
    				return [
    					'success' => false,
    					'output' => 'Usage: wp post meta list <post_id>',
    					'error' => 'Invalid command format',
    					'command' => 'wp post meta',
    					'method' => 'wp_api'
    				];
    			}
    			
    			$post_id = intval($command_parts[3]);
    			$post = get_post($post_id);
    			
    			if (!$post) {
    				return [
    					'success' => false,
    					'output' => "Post with ID {$post_id} not found.",
    					'error' => 'Post not found',
    					'command' => 'wp post meta list ' . $post_id,
    					'method' => 'wp_api'
    				];
    			}
    			
    			// Get post meta
    			$meta = get_post_meta($post_id);
    			
    			if (empty($meta)) {
    				return [
    					'success' => true,
    					'output' => "No meta found for post {$post_id}.",
    					'command' => 'wp post meta list ' . $post_id,
    					'method' => 'wp_api'
    				];
    			}
    			
    			// Format output as a table
    			$output = "META_KEY\tMETA_VALUE\n";
    			
    			foreach ($meta as $key => $values) {
    				foreach ($values as $value) {
    					// Format value for display
    					if (is_array($value) || is_object($value)) {
    						$display_value = 'Array/Object';
    					} else if (strlen($value) > 50) {
    						$display_value = substr($value, 0, 47) . '...';
    					} else {
    						$display_value = $value;
    					}
    					
    					$output .= "{$key}\t{$display_value}\n";
    				}
    			}
    			
    			return [
    				'success' => true,
    				'output' => $output,
    				'command' => 'wp post meta list ' . $post_id,
    				'method' => 'wp_api'
    			];
    			
    		default:
    			return [
    				'success' => false,
    				'output' => 'Unsupported post command: ' . $action,
    				'error' => 'Unsupported post command',
    				'command' => 'wp post ' . $action,
    				'method' => 'wp_api'
    			];
    	}
    }
    
    /**
     * Handle option commands directly using WordPress API
     *
     * @param array $command_parts Command parts
     * @return array Result
     */
    private function handle_option_command_direct($command_parts) {
    	$action = $command_parts[1] ?? '';
    	
    	switch ($action) {
    		case 'get':
    			$option_name = $command_parts[2] ?? '';
    			if (empty($option_name)) {
    				return [
    					'success' => false,
    					'output' => 'Option name is required',
    					'error' => 'Missing option name',
    					'command' => 'wp option get',
    					'method' => 'wp_api'
    				];
    			}
    			
    			$option_value = get_option($option_name);
    			if ($option_value === false) {
    				return [
    					'success' => false,
    					'output' => "Option '{$option_name}' not found.",
    					'error' => 'Option not found',
    					'command' => 'wp option get ' . $option_name,
    					'method' => 'wp_api'
    				];
    			}
    			
    			// Check for format parameter
    			$format = 'var_export';
    			for ($i = 3; $i < count($command_parts); $i++) {
    				if (strpos($command_parts[$i], '--format=') === 0) {
    					$format = str_replace('--format=', '', $command_parts[$i]);
    				}
    			}
    			
    			// Format the output based on the requested format
    			if (is_array($option_value) || is_object($option_value)) {
    				switch ($format) {
    					case 'json':
    						$output = json_encode($option_value, JSON_PRETTY_PRINT);
    						break;
    					case 'var_export':
    					default:
    						$output = var_export($option_value, true);
    						break;
    				}
    			} else {
    				$output = $option_value;
    			}
    			
    			return [
    				'success' => true,
    				'output' => $output,
    				'command' => 'wp option get ' . $option_name,
    				'method' => 'wp_api'
    			];
    			
    		case 'list':
    			global $wpdb;
    			
    			// Parse parameters
    			$search = '';
    			$limit = 20;
    			
    			for ($i = 2; $i < count($command_parts); $i++) {
    				if (strpos($command_parts[$i], '--search=') === 0) {
    					$search = str_replace('--search=', '', $command_parts[$i]);
    				} else if (strpos($command_parts[$i], '--limit=') === 0) {
    					$limit = intval(str_replace('--limit=', '', $command_parts[$i]));
    				}
    			}
    			
    			// Build query
    			$query = "SELECT option_name, option_value FROM {$wpdb->options}";
    			$params = [];
    			
    			if (!empty($search)) {
    				$query .= " WHERE option_name LIKE %s";
    				$params[] = '%' . $wpdb->esc_like($search) . '%';
    			}
    			
    			$query .= " ORDER BY option_name LIMIT %d";
    			$params[] = $limit;
    			
    			// Execute query
    			$options = $wpdb->get_results($wpdb->prepare($query, $params), ARRAY_A);
    			
    			if (empty($options)) {
    				return [
    					'success' => true,
    					'output' => 'No options found.',
    					'command' => 'wp option list',
    					'method' => 'wp_api'
    				];
    			}
    			
    			// Format output as a table
    			$output = "OPTION_NAME\tOPTION_VALUE\n";
    			
    			foreach ($options as $option) {
    				$value = $option['option_value'];
    				// Truncate long values
    				if (strlen($value) > 50) {
    					$value = substr($value, 0, 47) . '...';
    				}
    				$output .= "{$option['option_name']}\t{$value}\n";
    			}
    			
    			return [
    				'success' => true,
    				'output' => $output,
    				'command' => 'wp option list',
    				'method' => 'wp_api',
    				'command_type' => 'option_list',
    				'result' => $output
    			];
    			
    		case 'exists':
    			$option_name = $command_parts[2] ?? '';
    			if (empty($option_name)) {
    				return [
    					'success' => false,
    					'output' => 'Option name is required',
    					'error' => 'Missing option name',
    					'command' => 'wp option exists',
    					'method' => 'wp_api'
    				];
    			}
    			
    			$exists = get_option($option_name) !== false;
    			$output = $exists ? "Option '{$option_name}' exists." : "Option '{$option_name}' does not exist.";
    			
    			return [
    				'success' => true,
    				'output' => $output,
    				'command' => 'wp option exists ' . $option_name,
    				'method' => 'wp_api'
    			];
    			
    		case 'update':
    			$option_name = $command_parts[2] ?? '';
    			$option_value = $command_parts[3] ?? '';
    			
    			if (empty($option_name)) {
    				return [
    					'success' => false,
    					'output' => 'Option name is required',
    					'error' => 'Missing option name',
    					'command' => 'wp option update',
    					'method' => 'wp_api'
    				];
    			}
    			
    			if (empty($option_value)) {
    				return [
    					'success' => false,
    					'output' => 'Option value is required',
    					'error' => 'Missing option value',
    					'command' => 'wp option update ' . $option_name,
    					'method' => 'wp_api'
    				];
    			}
    			
    			// Try to determine if the value is a JSON string
    			$decoded_value = json_decode($option_value, true);
    			if (json_last_error() === JSON_ERROR_NONE) {
    				$option_value = $decoded_value;
    			}
    			
    			$result = update_option($option_name, $option_value);
    			$output = $result ? "Option '{$option_name}' updated." : "Error updating option '{$option_name}'.";
    			
    			return [
    				'success' => $result,
    				'output' => $output,
    				'command' => 'wp option update ' . $option_name . ' ' . $option_value,
    				'method' => 'wp_api'
    			];
    			
    		default:
    			return [
    				'success' => false,
    				'output' => 'Unsupported option command: ' . $action,
    				'error' => 'Unsupported option command',
    				'command' => 'wp option ' . $action,
    				'method' => 'wp_api'
    			];
    	}
    }
    
    /**
     * Handle system commands directly using WordPress API
     *
     * @param array $command_parts Command parts
     * @return array Result
     */
    private function handle_system_command_direct($command_parts) {
    	$command_type = $command_parts[0] ?? '';
    	$action = $command_parts[1] ?? '';
    	
    	if ($command_type === 'core' && $action === 'version') {
    		// Get WordPress version
    		$wp_version = get_bloginfo('version');
    		return [
    			'success' => true,
    			'output' => "WordPress version: $wp_version",
    			'wp_version' => $wp_version,
    			'command' => 'wp core version',
    			'method' => 'wp_api'
    		];
    	}
    	
    	return [
    		'success' => false,
    		'output' => 'Unsupported system command: ' . $command_type . ' ' . $action,
    		'error' => 'Unsupported system command',
    		'command' => 'wp ' . $command_type . ' ' . $action,
    		'method' => 'wp_api'
    	];
    }

    /**
     * Check if command is a PHP version query
     *
     * @param string $command Command to check
     * @return bool Whether command is a PHP version query
     */
    private function is_php_version_query($command) {
        $php_version_patterns = [
            '/php.*version/i',
            '/php\s+[-]{1,2}v/i',
            '/php\s+info/i',
            '/phpinfo/i',
            '/wp\s+eval\s+[\'"]?echo\s+PHP_VERSION/i',
            '/wp\s+php\s+info/i',
            '/wp\s+php\s+version/i'
        ];
        
        foreach ($php_version_patterns as $pattern) {
            if (preg_match($pattern, $command)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if command is a plugin query
     *
     * @param string $command Command to check
     * @return bool Whether command is a plugin query
     */
    private function is_plugin_query($command) {
        $command = strtolower(trim($command));
        return (strpos($command, 'wp plugin') === 0 ||
                strpos($command, 'plugin') === 0 ||
                strpos($command, 'plugins') === 0 ||
                strpos($command, 'wp plugins') === 0);
    }
    
    /**
     * Check if command is a plugin logs query
     *
     * @param string $command Command to check
     * @return bool Whether command is a plugin logs query
     */
    private function is_plugin_logs_query($command) {
        $command = strtolower(trim($command));
        return (strpos($command, 'wp plugin logs') === 0 ||
                strpos($command, 'plugin logs') === 0 ||
                strpos($command, 'wp plugin_logs') === 0 ||
                strpos($command, 'plugin_logs') === 0);
    }

    /**
     * Check if command is a system query
     *
     * @param string $command Command to check
     * @return bool Whether command is a system query
     */
    private function is_system_query($command) {
        $system_patterns = [
            '/wp\s+system-info/i',
            '/wp\s+site\s+health/i',
            '/wp\s+core\s+version/i',
            '/wp\s+core\s+/i',         // Any core command
            '/wp\s+db\s+info/i',
            '/wp\s+db\s+/i',           // Any db command
            '/wp\s+option\s+/i',       // Any option command
            '/wp\s+post\s+/i',         // Any post command
            '/wp\s+user\s+/i',         // Any user command
            '/wp\s+theme\s+/i',        // Any theme command
            '/wp\s+config\s+/i',       // Any config command
            '/wp\s+maintenance-mode/i',
            '/wp\s+cron\s+/i',         // Any cron command
            '/wp\s+menu\s+/i',         // Any menu command
            '/wp\s+comment\s+/i',      // Any comment command
            '/wp\s+taxonomy\s+/i',     // Any taxonomy command
            '/wp\s+term\s+/i',         // Any term command
            '/wp\s+widget\s+/i',       // Any widget command
            '/wp\s+sidebar\s+/i'       // Any sidebar command
        ];
        
        foreach ($system_patterns as $pattern) {
            if (preg_match($pattern, $command)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get PHP version and configuration information
     *
     * @return array PHP version information
     */
    private function get_php_version_info() {
        $this->logger->info('Getting PHP version information');
        
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
        $loaded_extensions = get_loaded_extensions();
        sort($loaded_extensions);
        $extensions_str = implode(', ', array_slice($loaded_extensions, 0, 15)) . '...';
        
        // Format the output
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
        
        return [
            'success' => true,
            'output' => $output,
            'php_version' => $php_version,
            'command' => 'php -v'
        ];
    }

    /**
     * Handle plugin-related commands
     *
     * @param string $command Command to execute
     * @param array $parameters Additional parameters
     * @return array Execution result
     */
    private function handle_plugin_command($command, $parameters = []) {
        $this->logger->info('Handling plugin command: ' . $command);
        
        // Normalize command for better pattern matching
        $command_lower = strtolower(trim($command));
        
        // Handle plugin logs commands - this should take precedence over other plugin commands
        if ($this->is_plugin_logs_query($command_lower)) {
            $this->logger->info('Detected plugin logs command, redirecting to direct plugin logs handler');
            return $this->handle_plugin_logs_command($parameters);
        }
        
        // Handle plugin list commands
        if (preg_match('/wp\s+plugins?\s+list/i', $command) ||
            $command_lower === 'plugins' ||
            $command_lower === 'wp plugins' ||
            $command_lower === 'plugin list' ||
            $command_lower === 'wp plugin list') {
            $this->logger->info('Getting plugin list');
            return $this->get_plugin_list($parameters);
        }
        
        // Handle plugin status or info commands
        if (preg_match('/wp\s+plugins?\s+(status|info)/i', $command) ||
            preg_match('/plugins?\s+(status|info)/i', $command)) {
            $this->logger->info('Getting plugin status');
            return $this->get_plugin_status($parameters);
        }
        
        // For other plugin commands, execute directly
        $this->logger->info('Executing plugin command directly: ' . $command);
        $wp_cli_command = $this->build_command($command, $parameters);
        $output = [];
        $return_var = 0;
        exec($wp_cli_command, $output, $return_var);
        
        return [
            'success' => ($return_var === 0),
            'output' => implode("\n", $output),
            'return_code' => $return_var,
            'command' => $command
        ];
    }
    
    /**
     * Handle plugin logs commands by directly accessing the plugin logger
     *
     * @param array $parameters Additional parameters
     * @return array Plugin logs data
     */
    private function handle_plugin_logs_command($parameters = []) {
        $this->logger->info('Handling plugin logs command directly');
        
        // Initialize the plugin logger
        if (!function_exists('mpai_init_plugin_logger')) {
            if (file_exists(MPAI_PLUGIN_DIR . 'includes/class-mpai-plugin-logger.php')) {
                require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-plugin-logger.php';
                $this->logger->debug('Loaded plugin logger class');
            } else {
                $this->logger->error('Plugin logger class not found');
                return [
                    'success' => false,
                    'output' => 'Error: Plugin logger class not found.',
                    'command' => 'plugin_logs'
                ];
            }
        }
        
        $plugin_logger = mpai_init_plugin_logger();
        
        if (!$plugin_logger) {
            $this->logger->error('Failed to initialize plugin logger');
            return [
                'success' => false,
                'output' => 'Error: Failed to initialize plugin logger.',
                'command' => 'plugin_logs'
            ];
        }
        
        // Extract parameters
        $action = isset($parameters['action']) ? $parameters['action'] : '';
        $plugin_name = isset($parameters['plugin_name']) ? $parameters['plugin_name'] : '';
        $days = isset($parameters['days']) ? intval($parameters['days']) : 30;
        $limit = isset($parameters['limit']) ? intval($parameters['limit']) : 25;
        
        // Calculate date range
        $date_from = '';
        if ($days > 0) {
            $date_from = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        }
        
        // Get summary data
        $summary = $plugin_logger->get_activity_summary($days);
        
        // Create a simplified summary
        $action_counts = [
            'total' => 0,
            'installed' => 0,
            'updated' => 0,
            'activated' => 0,
            'deactivated' => 0,
            'deleted' => 0
        ];
        
        if (isset($summary['action_counts']) && is_array($summary['action_counts'])) {
            foreach ($summary['action_counts'] as $count_data) {
                if (isset($count_data['action']) && isset($count_data['count'])) {
                    $action_counts[$count_data['action']] = intval($count_data['count']);
                    $action_counts['total'] += intval($count_data['count']);
                }
            }
        }
        
        // Prepare query arguments for detailed logs
        $args = [
            'plugin_name' => $plugin_name,
            'action'      => $action,
            'date_from'   => $date_from,
            'orderby'     => 'date_time',
            'order'       => 'DESC',
            'limit'       => $limit
        ];
        
        // Get logs
        $logs = $plugin_logger->get_logs($args);
        $this->logger->debug('Retrieved ' . count($logs) . ' plugin logs');
        
        // Enhance the logs with readable timestamps
        foreach ($logs as &$log) {
            $timestamp = strtotime($log['date_time']);
            $log['time_ago'] = human_time_diff($timestamp, current_time('timestamp')) . ' ago';
        }
        
        // Format the output for display
        $output = "Plugin Logs for the past {$days} days:\n\n";
        
        $output .= "Summary:\n";
        $output .= "- Total logs: " . $action_counts['total'] . "\n";
        $output .= "- Installed: " . $action_counts['installed'] . "\n";
        $output .= "- Activated: " . $action_counts['activated'] . "\n";
        $output .= "- Deactivated: " . $action_counts['deactivated'] . "\n";
        $output .= "- Updated: " . $action_counts['updated'] . "\n";
        $output .= "- Deleted: " . $action_counts['deleted'] . "\n\n";
        
        if (count($logs) > 0) {
            $output .= "Recent Activity:\n";
            foreach ($logs as $log) {
                $action = ucfirst($log['action']);
                $plugin_name = $log['plugin_name'];
                $version = $log['plugin_version'];
                $time_ago = $log['time_ago'];
                $user = isset($log['user_login']) && !empty($log['user_login']) ? $log['user_login'] : '';
                
                $output .= "- {$action}: {$plugin_name} v{$version} ({$time_ago})";
                if (!empty($user)) {
                    $output .= " by user {$user}";
                }
                $output .= "\n";
            }
        } else {
            $output .= "No plugin activity found for the specified criteria.\n";
        }
        
        $this->logger->info('Successfully retrieved plugin logs');
        
        return [
            'success' => true,
            'output' => $output,
            'command' => 'plugin_logs',
            'logs' => $logs,
            'summary' => $action_counts,
            'time_period' => "past {$days} days"
        ];
    }

    /**
     * Get list of plugins
     *
     * @param array $parameters Additional parameters
     * @return array Plugin list
     */
    private function get_plugin_list($parameters) {
        $this->logger->info('Getting plugin list from WordPress API');
        
        // Ensure plugin functions are available
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        // Get plugins
        $all_plugins = get_plugins();
        $active_plugins = get_option('active_plugins');
        
        // Filter by status if requested
        $status = isset($parameters['status']) ? $parameters['status'] : null;
        $this->logger->info('Status filter: ' . ($status ?: 'none'));
        
        // Format output - headers in the same format as WP-CLI would output
        if (isset($parameters['format']) && $parameters['format'] === 'json') {
            // Format as JSON array
            $plugins_json = [];
            foreach ($all_plugins as $plugin_path => $plugin_data) {
                $plugin_status = in_array($plugin_path, $active_plugins) ? 'active' : 'inactive';
                
                // Skip if status filter doesn't match
                if ($status && $plugin_status !== $status) {
                    continue;
                }
                
                $plugins_json[] = [
                    'name' => $plugin_data['Name'],
                    'status' => $plugin_status,
                    'version' => $plugin_data['Version'],
                    'description' => $plugin_data['Description'],
                    'path' => $plugin_path
                ];
            }
            
            return [
                'success' => true,
                'output' => json_encode($plugins_json),
                'command' => 'wp plugin list',
                'plugins' => $all_plugins
            ];
        } else {
            // Format as text table 
            $output = "NAME\tSTATUS\tVERSION\tDESCRIPTION\n";
            
            foreach ($all_plugins as $plugin_path => $plugin_data) {
                $plugin_status = in_array($plugin_path, $active_plugins) ? 'active' : 'inactive';
                
                // Skip if status filter doesn't match
                if ($status && $plugin_status !== $status) {
                    continue;
                }
                
                $name = $plugin_data['Name'];
                $version = $plugin_data['Version'];
                // Make sure description is a string before using strlen
                $description = isset($plugin_data['Description']) && is_string($plugin_data['Description']) ? 
                               (strlen($plugin_data['Description']) > 40 ? 
                               substr($plugin_data['Description'], 0, 40) . '...' : 
                               $plugin_data['Description']) : '';
                
                $output .= "$name\t$plugin_status\t$version\t$description\n";
            }
            
            return [
                'success' => true,
                'output' => $output,
                'command' => 'wp plugin list',
                'plugins' => $all_plugins
            ];
        }
    }

    /**
     * Get plugin status and information
     *
     * @param array $parameters Additional parameters
     * @return array Plugin status information
     */
    private function get_plugin_status($parameters) {
        $this->logger->info('Getting plugin status');
        
        // Ensure plugin functions are available
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        // Get system information
        $php_version = phpversion();
        $wp_version = get_bloginfo('version');
        
        $output = "WordPress System Status:\n\n";
        $output .= "PHP Version: $php_version\n";
        $output .= "WordPress Version: $wp_version\n";
        
        // Get plugin information
        $all_plugins = get_plugins();
        $active_plugins = get_option('active_plugins');
        
        $output .= "\nPlugin Statistics:\n";
        $output .= "Total Plugins: " . count($all_plugins) . "\n";
        $output .= "Active Plugins: " . count($active_plugins) . "\n";
        $output .= "Inactive Plugins: " . (count($all_plugins) - count($active_plugins)) . "\n\n";
        
        // List active plugins
        $output .= "Active Plugins:\n";
        foreach ($active_plugins as $plugin) {
            if (isset($all_plugins[$plugin])) {
                $plugin_data = $all_plugins[$plugin];
                $output .= "- {$plugin_data['Name']} v{$plugin_data['Version']}\n";
            }
        }
        
        return [
            'success' => true,
            'output' => $output,
            'command' => 'wp plugin status',
            'php_version' => $php_version,
            'wp_version' => $wp_version,
            'plugins' => $all_plugins
        ];
    }

    /**
     * Handle system-related commands
     *
     * @param string $command Command to execute
     * @param array $parameters Additional parameters
     * @return array Execution result
     */
    private function handle_system_command($command, $parameters = []) {
        $this->logger->info('Handling system command: ' . $command);
        
        if (preg_match('/wp\s+core\s+version/i', $command)) {
            // Get WordPress version
            $wp_version = get_bloginfo('version');
            return [
                'success' => true,
                'output' => "WordPress version: $wp_version",
                'wp_version' => $wp_version,
                'command' => $command
            ];
        }
        
        if (preg_match('/wp\s+db\s+info/i', $command)) {
            // Get database information
            return $this->get_database_info();
        }
        
        if (preg_match('/wp\s+site\s+health/i', $command) || preg_match('/wp\s+system-info/i', $command)) {
            // Get site health information
            return $this->get_site_health_info();
        }
        
        // For any other system command, execute directly
        $wp_cli_command = $this->build_command($command, $parameters);
        $output = [];
        $return_var = 0;
        exec($wp_cli_command, $output, $return_var);
        
        return [
            'success' => ($return_var === 0),
            'output' => implode("\n", $output),
            'return_code' => $return_var,
            'command' => $command
        ];
    }

    /**
     * Get database information
     *
     * @return array Database information
     */
    private function get_database_info() {
        global $wpdb;
        
        $db_version = $wpdb->db_version();
        $db_name = defined('DB_NAME') ? DB_NAME : 'unknown';
        $db_host = defined('DB_HOST') ? DB_HOST : 'unknown';
        $db_user = defined('DB_USER') ? DB_USER : 'unknown';
        $db_charset = defined('DB_CHARSET') ? DB_CHARSET : $wpdb->charset;
        $db_collate = $wpdb->collate;
        
        $output = "Database Information:\n\n";
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
        
        return [
            'success' => true,
            'output' => $output,
            'command' => 'wp db info',
            'db_version' => $db_version,
            'table_count' => $table_count
        ];
    }

    /**
     * Get site health information
     *
     * @return array Site health information
     */
    private function get_site_health_info() {
        global $wp_version;
        global $wpdb;
        
        $output = "WordPress Site Health Information:\n\n";
        
        // WordPress core information
        $output .= "WordPress Version: $wp_version\n";
        $output .= "Site URL: " . get_site_url() . "\n";
        $output .= "Home URL: " . get_home_url() . "\n";
        $output .= "Is Multisite: " . (is_multisite() ? 'Yes' : 'No') . "\n";
        
        // PHP information
        $output .= "\nPHP Information:\n";
        $output .= "PHP Version: " . phpversion() . "\n";
        $output .= "Memory Limit: " . ini_get('memory_limit') . "\n";
        $output .= "Max Execution Time: " . ini_get('max_execution_time') . " seconds\n";
        $output .= "Upload Max Filesize: " . ini_get('upload_max_filesize') . "\n";
        
        // Database information
        $output .= "\nDatabase Information:\n";
        $db_version = $wpdb->db_version();
        $output .= "MySQL Version: $db_version\n";
        $output .= "Database Prefix: " . $wpdb->prefix . "\n";
        
        // Theme information
        $theme = wp_get_theme();
        $output .= "\nActive Theme:\n";
        $output .= "Name: " . $theme->get('Name') . "\n";
        $output .= "Version: " . $theme->get('Version') . "\n";
        
        // Plugin information
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $all_plugins = get_plugins();
        $active_plugins = get_option('active_plugins');
        
        $output .= "\nPlugin Status:\n";
        $output .= "Active Plugins: " . count($active_plugins) . "\n";
        $output .= "Total Plugins: " . count($all_plugins) . "\n";
        
        return [
            'success' => true,
            'output' => $output,
            'command' => 'wp site health',
            'wp_version' => $wp_version,
            'php_version' => phpversion()
        ];
    }

    /**
     * Build a WP-CLI command with proper parameters and escaping
     *
     * @param string $command Base command
     * @param array $parameters Additional parameters
     * @return string Full command line
     */
    private function build_command($command, $parameters = []) {
        // Ensure command starts with wp
        if (strpos($command, 'wp ') !== 0 && strpos($command, 'php ') !== 0) {
            $command = 'wp ' . $command;
        }
        
        // Add format parameter if not specifically set in command
        if (strpos($command, '--format=') === false && strpos($command, 'help') === false) {
            // Default to JSON format for easier parsing
            $command .= ' --format=json';
        }
        
        // Escape the command
        $escaped_command = escapeshellcmd($command);
        
        // Add timeout
        $timeout = isset($parameters['timeout']) ? min((int)$parameters['timeout'], 60) : $this->timeout;
        $full_command = "timeout {$timeout}s {$escaped_command}";
        
        return $full_command;
    }

    /**
     * Check if a command contains dangerous patterns
     *
     * @param string $command WP-CLI command
     * @return bool Whether command is safe (true = safe, false = dangerous)
     */
    private function validate_command($command) {
        // Sanitize the command
        $sanitized_command = $this->sanitize_command($command);
        
        // Check against blacklist of dangerous patterns
        foreach ($this->dangerous_patterns as $pattern) {
            if (preg_match($pattern, $sanitized_command)) {
                $this->logger->error('Dangerous command pattern detected: ' . $pattern);
                return false;
            }
        }
        
        // Additional basic safety check
        if (strpos($sanitized_command, 'wp ') !== 0 && strpos($sanitized_command, 'php ') !== 0) {
            $this->logger->error('Command must start with wp or php: ' . $sanitized_command);
            return false;
        }
        
        // Command passed all security checks
        return true;
    }
    
    /**
     * Sanitize a command to prevent injection
     *
     * @param string $command Command to sanitize
     * @return string Sanitized command
     */
    private function sanitize_command($command) {
        // Remove potentially dangerous characters
        $command = preg_replace('/[;&|><]/', '', $command);
        
        // Ensure command starts with 'wp ' or 'php '
        if (strpos($command, 'wp ') !== 0 && strpos($command, 'php ') !== 0) {
            $command = 'wp ' . $command;
        }
        
        return trim($command);
    }
    
    /**
     * Format command output based on requested format
     *
     * @param array|string $output Command output lines
     * @param string $format Desired output format
     * @return mixed Formatted output
     */
    private function format_output($output, $format) {
        // Improved type safety handling
        $this->logger->info('Formatting output of type: ' . gettype($output));
        
        // Handle different input types
        if (is_string($output)) {
            // If it's already a string and format is text, return directly
            if ($format === 'text') {
                return $output;
            }
            
            // For other formats, convert to array
            $output = [$output];
        } elseif (is_object($output)) {
            // Convert objects to JSON strings
            $this->logger->info('Converting object to JSON');
            return json_encode($output);
        } elseif (!is_array($output)) {
            // For any other type, convert to string then array
            $this->logger->warning('Converting unexpected type to string: ' . gettype($output));
            $output = [(string)$output];
        }
        
        // Now $output should be an array
        if (empty($output)) {
            return '';
        }
        
        $raw_output = implode("\n", $output);
        
        switch ($format) {
            case 'json':
                // Try to parse the output as JSON
                $decoded = json_decode($raw_output, true);
                if ($decoded !== null && json_last_error() === JSON_ERROR_NONE) {
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
    /**
     * Handle theme commands directly using WordPress API
     *
     * @param array $command_parts Command parts
     * @return array Result
     */
    private function handle_theme_command_direct($command_parts) {
    	$action = $command_parts[1] ?? '';
    	
    	switch ($action) {
    		case 'list':
    			// Get all themes
    			$themes = wp_get_themes();
    			
    			// Get current theme
    			$current_theme = wp_get_theme();
    			
    			// Format output as a table
    			$output = "NAME\tSTATUS\tVERSION\tAUTHOR\n";
    			
    			foreach ($themes as $theme_name => $theme) {
    				$status = ($current_theme->get_stylesheet() === $theme->get_stylesheet()) ? 'active' : 'inactive';
    				$name = $theme->get('Name');
    				$version = $theme->get('Version');
    				$author = $theme->get('Author');
    				
    				$output .= "$name\t$status\t$version\t$author\n";
    			}
    			
    			return [
    				'success' => true,
    				'output' => $output,
    				'command' => 'wp theme list',
    				'method' => 'wp_api',
    				'command_type' => 'theme_list',
    				'result' => $output
    			];
    			
    		case 'status':
    			// Get current theme
    			$current_theme = wp_get_theme();
    			
    			// Format output
    			$output = "Current theme:\n";
    			$output .= "Name: " . $current_theme->get('Name') . "\n";
    			$output .= "Version: " . $current_theme->get('Version') . "\n";
    			$output .= "Author: " . $current_theme->get('Author') . "\n";
    			$output .= "Description: " . $current_theme->get('Description') . "\n";
    			
    			return [
    				'success' => true,
    				'output' => $output,
    				'command' => 'wp theme status',
    				'method' => 'wp_api'
    			];
    			
    		default:
    			return [
    				'success' => false,
    				'output' => 'Unsupported theme command: ' . $action,
    				'error' => 'Unsupported theme command',
    				'command' => 'wp theme ' . $action,
    				'method' => 'wp_api'
    			];
    	}
    }
    
    /**
     * Handle menu commands directly using WordPress API
     *
     * @param array $command_parts Command parts
     * @return array Result
     */
    private function handle_menu_command_direct($command_parts) {
    	$action = $command_parts[1] ?? '';
    	
    	switch ($action) {
    		case 'list':
    			// Get all menus
    			$menus = wp_get_nav_menus();
    			
    			if (empty($menus)) {
    				return [
    					'success' => true,
    					'output' => 'No menus found.',
    					'command' => 'wp menu list',
    					'method' => 'wp_api'
    				];
    			}
    			
    			// Format output as a table
    			$output = "ID\tNAME\tCOUNT\tSLUG\tLOCATIONS\n";
    			
    			foreach ($menus as $menu) {
    				$locations = [];
    				$menu_locations = get_nav_menu_locations();
    				
    				foreach ($menu_locations as $location => $menu_id) {
    					if ($menu_id == $menu->term_id) {
    						$locations[] = $location;
    					}
    				}
    				
    				$locations_str = empty($locations) ? 'none' : implode(', ', $locations);
    				$output .= "{$menu->term_id}\t{$menu->name}\t{$menu->count}\t{$menu->slug}\t{$locations_str}\n";
    			}
    			
    			return [
    				'success' => true,
    				'output' => $output,
    				'command' => 'wp menu list',
    				'method' => 'wp_api',
    				'command_type' => 'menu_list',
    				'result' => $output
    			];
    			
    		default:
    			return [
    				'success' => false,
    				'output' => 'Unsupported menu command: ' . $action,
    				'error' => 'Unsupported menu command',
    				'command' => 'wp menu ' . $action,
    				'method' => 'wp_api'
    			];
    	}
    }
    
    /**
     * Handle comment commands directly using WordPress API
     *
     * @param array $command_parts Command parts
     * @return array Result
     */
    private function handle_comment_command_direct($command_parts) {
    	$action = $command_parts[1] ?? '';
    	
    	switch ($action) {
    		case 'list':
    			// Get comments
    			$args = ['number' => 10, 'status' => 'approve'];
    			
    			// Parse additional parameters
    			for ($i = 2; $i < count($command_parts); $i++) {
    				if (strpos($command_parts[$i], '--number=') === 0) {
    					$limit = intval(str_replace('--number=', '', $command_parts[$i]));
    					$args['number'] = $limit;
    				} else if (strpos($command_parts[$i], '--status=') === 0) {
    					$status = str_replace('--status=', '', $command_parts[$i]);
    					$args['status'] = $status;
    				}
    			}
    			
    			$comments = get_comments($args);
    			
    			if (empty($comments)) {
    				return [
    					'success' => true,
    					'output' => 'No comments found.',
    					'command' => 'wp comment list',
    					'method' => 'wp_api'
    				];
    			}
    			
    			// Format output as a table
    			$output = "ID\tAUTHOR\tPOST_ID\tDATE\tSTATUS\n";
    			
    			foreach ($comments as $comment) {
    				$output .= "{$comment->comment_ID}\t{$comment->comment_author}\t{$comment->comment_post_ID}\t{$comment->comment_date}\t{$comment->comment_approved}\n";
    			}
    			
    			return [
    				'success' => true,
    				'output' => $output,
    				'command' => 'wp comment list',
    				'method' => 'wp_api',
    				'command_type' => 'comment_list',
    				'result' => $output
    			];
    			
    		default:
    			return [
    				'success' => false,
    				'output' => 'Unsupported comment command: ' . $action,
    				'error' => 'Unsupported comment command',
    				'command' => 'wp comment ' . $action,
    				'method' => 'wp_api'
    			];
    	}
    }
    
    /**
     * Handle database commands directly using WordPress API
     *
     * @param array $command_parts Command parts
     * @return array Result
     */
    private function handle_db_command_direct($command_parts) {
    	$action = $command_parts[1] ?? '';
    	
    	switch ($action) {
    		case 'info':
    			global $wpdb;
    			
    			// Get database information
    			$db_version = $wpdb->db_version();
    			$db_name = defined('DB_NAME') ? DB_NAME : 'unknown';
    			$db_host = defined('DB_HOST') ? DB_HOST : 'unknown';
    			$db_user = defined('DB_USER') ? DB_USER : 'unknown';
    			$db_charset = defined('DB_CHARSET') ? DB_CHARSET : $wpdb->charset;
    			$db_collate = $wpdb->collate;
    			
    			$output = "Database Information:\n\n";
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
    			
    			return [
    				'success' => true,
    				'output' => $output,
    				'command' => 'wp db info',
    				'method' => 'wp_api',
    				'command_type' => 'db_info',
    				'result' => $output
    			];
    			
    		case 'tables':
    			global $wpdb;
    			
    			// Get all tables
    			$tables = $wpdb->get_results('SHOW TABLES', ARRAY_N);
    			
    			if (empty($tables)) {
    				return [
    					'success' => true,
    					'output' => 'No tables found.',
    					'command' => 'wp db tables',
    					'method' => 'wp_api'
    				];
    			}
    			
    			// Format output as a list
    			$output = "Database Tables:\n\n";
    			
    			foreach ($tables as $table) {
    				$output .= $table[0] . "\n";
    			}
    			
    			return [
    				'success' => true,
    				'output' => $output,
    				'command' => 'wp db tables',
    				'method' => 'wp_api'
    			];
    			
    		case 'size':
    			global $wpdb;
    			
    			// Get database size
    			$size_query = $wpdb->get_row("SELECT SUM(data_length + index_length) / 1024 / 1024 as size FROM information_schema.TABLES WHERE table_schema = '" . DB_NAME . "'");
    			
    			if ($size_query) {
    				$size = round($size_query->size, 2);
    				$output = "Database Size: {$size} MB";
    			} else {
    				$output = "Unable to determine database size.";
    			}
    			
    			return [
    				'success' => true,
    				'output' => $output,
    				'command' => 'wp db size',
    				'method' => 'wp_api'
    			];
    			
    		default:
    			return [
    				'success' => false,
    				'output' => 'Unsupported db command: ' . $action,
    				'error' => 'Unsupported db command',
    				'command' => 'wp db ' . $action,
    				'method' => 'wp_api'
    			];
    	}
    }
}