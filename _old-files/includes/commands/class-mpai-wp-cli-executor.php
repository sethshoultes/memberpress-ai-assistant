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
     * Check if a command is MemberPress related
     * 
     * @param string $command Command to check
     * @return bool Whether the command is MemberPress related
     */
    private function is_memberpress_command($command) {
        // Check for common MemberPress command patterns
        if (strpos($command, 'wp mepr') === 0 || 
            strpos($command, 'mepr-') !== false ||
            strpos($command, 'memberpress') !== false) {
            return true;
        }
        
        // Look for specific command patterns that relate to MemberPress
        if (preg_match('/create_membership|membership\s+create|coupon\s+create|transaction\s+create|subscription\s+create/i', $command)) {
            return true;
        }
        
        return false;
    }

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
            
            public function warning($message) {
                mpai_log_warning($message, 'wp-cli');
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
    		
    		// Check for MemberPress command first
    		if ($this->is_memberpress_command($command)) {
    			$this->logger->info('Handling as MemberPress command');
    			return $this->handle_memberpress_command($command, $parameters);
    		}
    		// Check for plugin logs command - this should take precedence over other commands
    		else if ($this->is_plugin_logs_query($command)) {
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
            
            // Additional plugin handlers would be here...
        }
        
        return [
            'success' => false,
            'output' => 'Unsupported plugin command: ' . $action,
            'error' => 'Unsupported plugin command',
            'command' => 'wp plugin ' . $action,
            'method' => 'wp_api'
        ];
    }
    
    /**
     * Validate a command for security
     *
     * @param string $command Command to validate
     * @return bool Whether the command is safe to execute
     */
    private function validate_command($command) {
        // Check against dangerous patterns
        foreach ($this->dangerous_patterns as $pattern) {
            if (preg_match($pattern, $command)) {
                $this->logger->error("Command blocked by security validation pattern: $pattern", [
                    'command' => $command,
                    'pattern' => $pattern
                ]);
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Check if a command is a plugin logs query
     *
     * @param string $command Command to check
     * @return bool Whether the command is a plugin logs query
     */
    private function is_plugin_logs_query($command) {
        return (strpos($command, 'plugin logs') !== false || 
                strpos($command, 'wp plugin-logs') !== false ||
                strpos($command, 'wp plugin logs') !== false);
    }
    
    /**
     * Check if a command is a PHP version query
     *
     * @param string $command Command to check
     * @return bool Whether the command is a PHP version query
     */
    private function is_php_version_query($command) {
        return (strpos($command, 'php version') !== false || 
                strpos($command, 'php info') !== false ||
                strpos($command, 'phpinfo') !== false);
    }
    
    /**
     * Check if a command is a plugin query
     *
     * @param string $command Command to check
     * @return bool Whether the command is a plugin query
     */
    private function is_plugin_query($command) {
        return (strpos($command, 'wp plugin') === 0 || 
                strpos($command, 'plugin ') === 0);
    }
    
    /**
     * Check if a command is a system query
     *
     * @param string $command Command to check
     * @return bool Whether the command is a system query
     */
    private function is_system_query($command) {
        return (strpos($command, 'wp core') === 0 || 
                strpos($command, 'core ') === 0 ||
                strpos($command, 'wp system') === 0 ||
                strpos($command, 'system ') === 0);
    }
    
    /**
     * Get PHP version information
     *
     * @return array PHP version information
     */
    private function get_php_version_info() {
        $version = phpversion();
        $modules = get_loaded_extensions();
        $extensions = implode(', ', $modules);
        
        $output = "PHP Version: $version\n\n";
        $output .= "Loaded Extensions:\n$extensions\n\n";
        $output .= "PHP Configuration:\n";
        $output .= "memory_limit: " . ini_get('memory_limit') . "\n";
        $output .= "max_execution_time: " . ini_get('max_execution_time') . "\n";
        $output .= "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
        $output .= "post_max_size: " . ini_get('post_max_size') . "\n";
        
        return [
            'success' => true,
            'output' => $output,
            'command' => 'php info',
            'method' => 'wp_api',
            'version' => $version
        ];
    }
    
    /**
     * Handle plugin logs command
     *
     * @param array $parameters Command parameters
     * @return array Plugin logs
     */
    private function handle_plugin_logs_command($parameters = []) {
        // Check if we have a plugin logs tool registered
        if (class_exists('MPAI_Plugin_Logs_Tool')) {
            $logs_tool = new MPAI_Plugin_Logs_Tool();
            
            // Extract parameters
            $days = isset($parameters['days']) ? intval($parameters['days']) : 30;
            $limit = isset($parameters['limit']) ? intval($parameters['limit']) : 10;
            $type = isset($parameters['type']) ? $parameters['type'] : 'all';
            
            // Prepare tool parameters
            $tool_params = [
                'days' => $days,
                'limit' => $limit,
                'type' => $type
            ];
            
            // Execute the plugin logs tool
            try {
                $logs = $logs_tool->execute($tool_params);
                
                return [
                    'success' => true,
                    'output' => $logs,
                    'command' => 'plugin logs',
                    'method' => 'plugin_logs_tool'
                ];
            } catch (Exception $e) {
                return [
                    'success' => false,
                    'output' => 'Error retrieving plugin logs: ' . $e->getMessage(),
                    'error' => $e->getMessage(),
                    'command' => 'plugin logs'
                ];
            }
        } else {
            // Fallback to a basic message if the tool is not available
            return [
                'success' => false,
                'output' => 'Plugin logs tool not available. Please ensure the plugin is properly configured.',
                'error' => 'Plugin logs tool not available',
                'command' => 'plugin logs'
            ];
        }
    }
    
    /**
     * Format output based on requested format
     *
     * @param mixed $output Output to format
     * @param string $format Requested format
     * @return mixed Formatted output
     */
    private function format_output($output, $format) {
        if ($format === 'json') {
            $json_output = is_array($output) ? json_encode($output) : $output;
            return $json_output;
        }
        
        if ($format === 'array' && is_array($output)) {
            return $output;
        }
        
        // Default to text
        if (is_array($output)) {
            return implode("\n", $output);
        }
        
        return $output;
    }
    
    /**
     * Handle MemberPress commands using direct service methods
     * 
     * @param string $command Command to execute
     * @param array $parameters Additional parameters
     * @return array Execution result
     */
    private function handle_memberpress_command($command, $parameters = []) {
        $this->logger->info('Handling MemberPress command with direct service: ' . $command);
        
        // Load the MemberPress service
        if (!class_exists('MPAI_MemberPress_Service')) {
            require_once dirname(dirname(__FILE__)) . '/class-mpai-memberpress-service.php';
        }
        
        $service = new MPAI_MemberPress_Service();
        
        // Parse the command and its arguments
        $args = $this->parse_command_args($command);
        $output = '';
        $success = true;
        
        // Determine what type of MemberPress command this is - support various formats
        if (preg_match('/mepr-membership\s+create|membership\s+create|memberpress\s+create_membership|create_membership|memberpress\s+membership\s+create|wp\s+mepr\s+membership\s+create/i', $command)) {
            // Extract the name parameter - supports various formats
            $name = 'New Membership';
            if (isset($args['name'])) {
                $name = $args['name'];
            } else if (preg_match('/--name=[\'"]?([^\'"]+)[\'"]?/i', $command, $name_matches)) {
                $name = $name_matches[1];
            }
            
            // Extract the price parameter
            $price = 0;
            if (isset($args['price'])) {
                $price = floatval($args['price']);
            } else if (preg_match('/--price=[\'"]?([0-9.]+)[\'"]?/i', $command, $price_matches)) {
                $price = floatval($price_matches[1]);
            }
            
            // Extract the period type parameter (supports period, period_type, and billing_type)
            $period_type = 'month';
            
            $this->logger->debug('Command for period extraction: ' . $command);
            if (isset($args['period'])) {
                $period_type = $args['period'];
                $this->logger->debug('Found period in args: ' . $period_type);
            } else if (isset($args['period_type'])) {
                $period_type = $args['period_type'];
                $this->logger->debug('Found period_type in args: ' . $period_type);
            } else if (isset($args['billing_type'])) {
                $period_type = $args['billing_type'];
                $this->logger->debug('Found billing_type in args: ' . $period_type);
            } else if (preg_match('/--period=[\'"]?([a-z]+)[\'"]?/i', $command, $period_matches)) {
                $period_type = $period_matches[1];
                $this->logger->debug('Found period in regex: ' . $period_type);
            } else if (preg_match('/--billing-type=[\'"]?([a-z]+)[\'"]?/i', $command, $billing_matches)) {
                $period_type = $billing_matches[1];
                $this->logger->debug('Found billing-type in regex: ' . $period_type);
            } else if (preg_match('/--billing_type=[\'"]?([a-z]+)[\'"]?/i', $command, $billing_type_matches)) {
                $period_type = $billing_type_matches[1];
                $this->logger->debug('Found billing_type in regex: ' . $period_type);
            }
            
            // Extract the period (number) parameter - support both 'interval' and 'period_num' as parameter names
            $period = 1;
            $this->logger->debug('Args for interval extraction: ' . json_encode($args));
            
            if (isset($args['interval'])) {
                $period = intval($args['interval']);
                $this->logger->debug('Found interval in args: ' . $period);
            } else if (isset($args['period_num'])) {
                $period = intval($args['period_num']);
                $this->logger->debug('Found period_num in args: ' . $period);
            } else if (preg_match('/--interval=[\'"]?([0-9]+)[\'"]?/i', $command, $interval_matches)) {
                $period = intval($interval_matches[1]);
                $this->logger->debug('Found interval in regex: ' . $period);
            } else if (preg_match('/--period_num=[\'"]?([0-9]+)[\'"]?/i', $command, $period_num_matches)) {
                $period = intval($period_num_matches[1]);
                $this->logger->debug('Found period_num in regex: ' . $period);
            } else if (preg_match('/--period-num=[\'"]?([0-9]+)[\'"]?/i', $command, $period_num_matches)) {
                $period = intval($period_num_matches[1]);
                $this->logger->debug('Found period-num in regex: ' . $period);
            }
            
            $this->logger->info("Creating membership with name: $name, price: $price, period: $period $period_type");
            
            // Log all command information for debugging
            $this->logger->debug('Original command: ' . $command);
            $this->logger->debug('Parsed parameters: ' . json_encode([
                'name' => $name,
                'price' => $price,
                'period' => $period,
                'period_type' => $period_type,
                'raw_args' => $args
            ]));
            
            $result = $service->create_membership([
                'name' => $name,
                'price' => $price,
                'period' => $period,
                'period_type' => $period_type
            ]);
            
            if (is_wp_error($result)) {
                $success = false;
                $output = $result->get_error_message();
            } else {
                $output = json_encode([
                    'id' => $result->ID,
                    'name' => $result->post_title,
                    'price' => $result->price,
                    'period' => $period,
                    'period_type' => $result->period_type,
                    'message' => "Successfully created membership '{$result->post_title}' with ID {$result->ID}"
                ]);
            }
        }
        else if (preg_match('/mepr-coupon\s+create|coupon\s+create/i', $command)) {
            $code = isset($args['code']) ? $args['code'] : 'COUPON' . rand(1000, 9999);
            $type = isset($args['type']) ? $args['type'] : 'percent';
            $amount = isset($args['amount']) ? floatval($args['amount']) : 10;
            
            $this->logger->info("Creating coupon with code: $code, type: $type, amount: $amount");
            
            $result = $service->create_coupon([
                'code' => $code,
                'discount_type' => $type,
                'discount_amount' => $amount
            ]);
            
            if (is_wp_error($result)) {
                $success = false;
                $output = $result->get_error_message();
            } else {
                $output = json_encode([
                    'id' => $result->ID,
                    'code' => $result->post_title,
                    'discount_type' => $result->discount_type,
                    'discount_amount' => $result->discount_amount
                ]);
            }
        }
        else if (preg_match('/mepr-user\s+add-to-membership|user\s+add/i', $command)) {
            $user_id = isset($args['user']) ? intval($args['user']) : 0;
            $membership_id = isset($args['membership']) ? intval($args['membership']) : 0;
            $status = isset($args['status']) ? $args['status'] : 'active';
            
            if (!$user_id || !$membership_id) {
                $success = false;
                $output = 'User ID and membership ID are required';
            } else {
                $this->logger->info("Adding user $user_id to membership $membership_id with status $status");
                
                $result = $service->add_user_to_membership($user_id, $membership_id, [
                    'status' => $status
                ]);
                
                if (is_wp_error($result)) {
                    $success = false;
                    $output = $result->get_error_message();
                } else {
                    $output = json_encode([
                        'subscription_id' => $result->id,
                        'user_id' => $result->user_id,
                        'membership_id' => $result->product_id,
                        'status' => $result->status
                    ]);
                }
            }
        }
        else if (preg_match('/mepr-transaction\s+create|transaction\s+create/i', $command)) {
            $user_id = isset($args['user']) ? intval($args['user']) : 0;
            $membership_id = isset($args['membership']) ? intval($args['membership']) : 0;
            $amount = isset($args['amount']) ? floatval($args['amount']) : null;
            $status = isset($args['status']) ? $args['status'] : 'complete';
            
            if (!$user_id || !$membership_id) {
                $success = false;
                $output = 'User ID and membership ID are required';
            } else {
                $this->logger->info("Creating transaction for user $user_id, membership $membership_id");
                
                $txn_args = [
                    'user_id' => $user_id,
                    'product_id' => $membership_id,
                    'status' => $status
                ];
                
                if ($amount !== null) {
                    $txn_args['amount'] = $amount;
                }
                
                $result = $service->create_transaction($txn_args);
                
                if (is_wp_error($result)) {
                    $success = false;
                    $output = $result->get_error_message();
                } else {
                    $output = json_encode([
                        'transaction_id' => $result->id,
                        'user_id' => $result->user_id,
                        'membership_id' => $result->product_id,
                        'amount' => $result->amount,
                        'status' => $result->status
                    ]);
                }
            }
        }
        else if (preg_match('/mepr-membership\s+list|membership\s+list/i', $command)) {
            $this->logger->info("Listing memberships");
            
            $result = $service->get_memberships();
            
            if (is_wp_error($result)) {
                $success = false;
                $output = $result->get_error_message();
            } else {
                $output = json_encode($result);
            }
        }
        else {
            // For other MemberPress commands, rely on the exec method for now
            $this->logger->info("Unhandled MemberPress command, falling back to exec: $command");
            
            // Execute the command using standard WP-CLI
            return $this->execute_standard_command($command, $parameters);
        }
        
        return [
            'success' => $success,
            'output' => $output,
            'command' => $command,
            'method' => 'memberpress_service'
        ];
    }
    
    /**
     * Execute a standard command using WP-CLI
     * 
     * @param string $command Command to execute
     * @param array $parameters Additional parameters
     * @return array Execution result
     */
    private function execute_standard_command($command, $parameters = []) {
        // Build the command
        $wp_cli_command = $this->build_command($command, $parameters);
        
        // Execute the command
        $output = [];
        $return_var = 0;
        $this->logger->info('Executing standard command: ' . $wp_cli_command);
        $last_line = exec($wp_cli_command, $output, $return_var);
        
        // Format the output
        $format = isset($parameters['format']) ? $parameters['format'] : 'text';
        $formatted_output = $this->format_output($output, $format);
        
        return [
            'success' => ($return_var === 0),
            'output' => $formatted_output,
            'return_code' => $return_var,
            'command' => $command
        ];
    }
    
    /**
     * Parse command arguments
     * 
     * @param string $command Command string
     * @return array Parsed arguments
     */
    private function parse_command_args($command) {
        $args = [];
        
        $this->logger->debug('Parsing command: ' . $command);
        
        // Clean up the command, remove extra whitespace
        $command = trim($command);
        
        // Match arguments in the format --key=value or --key='value' or --key="value"
        preg_match_all('/--([a-zA-Z0-9_-]+)=(?:([^\s\'"][^\s]*)|\'([^\']*?)\'|"([^"]*?)")/', $command, $matches);
        
        if (!empty($matches[1])) {
            for ($i = 0; $i < count($matches[1]); $i++) {
                $key = $matches[1][$i];
                // Convert key to lowercase for consistency
                $key = strtolower(str_replace('-', '_', $key));
                
                // Get the value from whichever capture group matched
                $value = !empty($matches[2][$i]) ? $matches[2][$i] : 
                         (!empty($matches[3][$i]) ? $matches[3][$i] : 
                         (!empty($matches[4][$i]) ? $matches[4][$i] : ''));
                         
                $args[$key] = $value;
                $this->logger->debug("Found arg with = format: $key = $value");
            }
        }
        
        // Additionally match format where value is in next param: --key value
        preg_match_all('/\s--([a-zA-Z0-9_-]+)\s+([^\s-][^\s]*|\'[^\']*?\'|"[^"]*?")/', $command, $space_matches);
        
        if (!empty($space_matches[1])) {
            for ($i = 0; $i < count($space_matches[1]); $i++) {
                $key = $space_matches[1][$i];
                // Convert key to lowercase for consistency
                $key = strtolower(str_replace('-', '_', $key));
                
                $value = $space_matches[2][$i];
                
                // Remove quotes if present
                if ((substr($value, 0, 1) === "'" && substr($value, -1) === "'") || 
                    (substr($value, 0, 1) === '"' && substr($value, -1) === '"')) {
                    $value = substr($value, 1, -1);
                }
                
                // Only add if not already set from --key=value format
                if (!isset($args[$key])) {
                    $args[$key] = $value;
                    $this->logger->debug("Found arg with space format: $key = $value");
                }
            }
        }
        
        // Look for standard positional arguments after the main command
        // Example: wp mepr-membership create "Premium Plan" 19.99 month 1
        if (preg_match('/(?:mepr-membership\s+create|membership\s+create|create_membership|memberpress\s+create_membership)\s+["\']?([^"\']+)["\']?\s+([0-9.]+)(?:\s+([a-z]+))?(?:\s+([0-9]+))?/i', $command, $pos_matches)) {
            if (!isset($args['name']) && !empty($pos_matches[1])) {
                $args['name'] = trim($pos_matches[1]);
                $this->logger->debug("Found positional name: " . $args['name']);
            }
            if (!isset($args['price']) && !empty($pos_matches[2])) {
                $args['price'] = $pos_matches[2];
                $this->logger->debug("Found positional price: " . $args['price']);
            }
            if (!isset($args['period']) && !empty($pos_matches[3])) {
                $args['period'] = $pos_matches[3];
                $this->logger->debug("Found positional period: " . $args['period']);
            }
            if (!isset($args['interval']) && !empty($pos_matches[4])) {
                $args['interval'] = $pos_matches[4];
                $this->logger->debug("Found positional interval: " . $args['interval']);
            }
        }
        
        // Special handling for memberpress create_membership command format
        if (preg_match('/memberpress\s+create_membership/i', $command)) {
            // Extract values directly using regex for this format
            if (!isset($args['name']) && preg_match('/--name=[\'"]?([^\'"]+)[\'"]?/i', $command, $name_matches)) {
                $args['name'] = $name_matches[1];
                $this->logger->debug("Found direct name match: " . $args['name']);
            }
            
            if (!isset($args['price']) && preg_match('/--price=[\'"]?([0-9.]+)[\'"]?/i', $command, $price_matches)) {
                $args['price'] = $price_matches[1];
                $this->logger->debug("Found direct price match: " . $args['price']);
            }
            
            if (!isset($args['interval']) && preg_match('/--interval=[\'"]?([0-9]+)[\'"]?/i', $command, $interval_matches)) {
                $args['interval'] = $interval_matches[1];
                $this->logger->debug("Found direct interval match: " . $args['interval']);
            }
            
            if (!isset($args['period']) && preg_match('/--period=[\'"]?([a-z]+)[\'"]?/i', $command, $period_matches)) {
                $args['period'] = $period_matches[1];
                $this->logger->debug("Found direct period match: " . $args['period']);
            }
        }
        
        // Handle special cases for wp-cli and common commands
        // Map interval to period if needed
        if (isset($args['interval']) && !isset($args['period_num'])) {
            $args['period_num'] = $args['interval'];
            $this->logger->debug("Mapped interval to period_num: " . $args['period_num']);
        }
        
        // Map period to period_type if needed
        if (isset($args['period']) && !isset($args['period_type'])) {
            $args['period_type'] = $args['period'];
            $this->logger->debug("Mapped period to period_type: " . $args['period_type']);
        }
        
        // Map period to billing_type for backwards compatibility
        if (isset($args['period']) && !isset($args['billing_type'])) {
            $args['billing_type'] = $args['period'];
            $this->logger->debug("Mapped period to billing_type: " . $args['billing_type']);
        }
        
        $this->logger->debug('Final parsed command args: ' . json_encode($args));
        
        return $args;
    }
    
    /**
     * Build a CLI command with parameters
     *
     * @param string $command Base command
     * @param array $parameters Command parameters
     * @return string Full command
     */
    private function build_command($command, $parameters = []) {
        // Build the command
        $wp_cli_command = $command;
        
        // Add timeout
        $timeout = isset($parameters['timeout']) ? min((int)$parameters['timeout'], 60) : $this->timeout;
        
        // Add format if specified
        if (isset($parameters['format']) && !preg_match('/--format=/', $command)) {
            $wp_cli_command .= ' --format=' . escapeshellarg($parameters['format']);
        }
        
        // Handle different OS
        if (stripos(PHP_OS, 'WIN') === 0) {
            // Windows - timeout command is different
            $command = "timeout /t {$timeout} /nobreak > nul & {$wp_cli_command}";
        } else {
            // Linux/Mac - use standard timeout command
            $command = "timeout {$timeout}s {$wp_cli_command}";
        }
        
        return $command;
    }
    
    /**
     * Get a list of plugins directly from WordPress
     *
     * @param array $parameters Command parameters
     * @return array Plugin list
     */
    private function get_plugin_list($parameters = []) {
        // Ensure plugin functions are available
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        // Get plugins
        $all_plugins = get_plugins();
        $active_plugins = get_option('active_plugins');
        
        // Format output as a table by default
        $format = isset($parameters['format']) ? $parameters['format'] : 'table';
        
        // Handle different formats
        if ($format === 'json') {
            $plugins = [];
            foreach ($all_plugins as $plugin_path => $plugin_data) {
                $plugins[] = array_merge($plugin_data, [
                    'path' => $plugin_path,
                    'status' => in_array($plugin_path, $active_plugins) ? 'active' : 'inactive'
                ]);
            }
            return [
                'success' => true,
                'output' => json_encode($plugins),
                'command' => 'wp plugin list',
                'method' => 'wp_api'
            ];
        }
        
        // Default text format
        $output = "NAME\tSTATUS\tVERSION\tAUTHOR\tDESCRIPTION\n";
        
        foreach ($all_plugins as $plugin_path => $plugin_data) {
            $plugin_status = in_array($plugin_path, $active_plugins) ? 'active' : 'inactive';
            
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
            'method' => 'wp_api'
        ];
    }
}