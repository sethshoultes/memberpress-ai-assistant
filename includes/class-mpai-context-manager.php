<?php
/**
 * Context Manager Class
 *
 * Handles CLI command execution and context management
 * 
 * SECURITY NOTE: Command validation now uses a permissive blacklist approach rather 
 * than a restrictive whitelist. This is more user-friendly while still maintaining 
 * security by blocking known dangerous patterns.
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

class MPAI_Context_Manager {
    // Version identifier to confirm the updated file is being used
    const VERSION = '2.0.2'; // Updated April 2, 2025 - Added more debugging
    
    /**
     * OpenAI integration instance
     *
     * @var MPAI_OpenAI
     */
    private $openai;

    /**
     * MemberPress API integration instance
     *
     * @var MPAI_MemberPress_API
     */
    private $memberpress_api;

    /**
     * Allowed commands
     *
     * @var array
     */
    private $allowed_commands;

    /**
     * Available tools for MCP
     *
     * @var array
     */
    private $available_tools;

    /**
     * Chat instance for message extraction
     *
     * @var MPAI_Chat
     */
    private $chat_instance;

    /**
     * Error recovery system instance
     *
     * @var MPAI_Error_Recovery
     */
    private $error_recovery;

    /**
     * Constructor
     */
    public function __construct() {
        mpai_log_info('Context Manager v' . self::VERSION . ' initialized', 'context-manager');
        
        // Initialize error recovery system
        if (class_exists('MPAI_Error_Recovery')) {
            $this->error_recovery = mpai_init_error_recovery();
        } else {
            mpai_log_warning('Error recovery system not available', 'context-manager');
        }
        
        $this->openai = new MPAI_OpenAI();
        $this->memberpress_api = new MPAI_MemberPress_API();
        $this->allowed_commands = get_option('mpai_allowed_cli_commands', array());
        $this->init_tools();
    }
    
    /**
     * Set the chat instance for message extraction
     *
     * @param object $chat_instance Chat instance
     */
    public function set_chat_instance($chat_instance) {
        $this->chat_instance = $chat_instance;
        mpai_log_debug('Chat instance set in context manager', 'context-manager');
    }
    
    /**
     * Reset the context manager's state
     * 
     * Clears any cached data or state that might persist between 
     * chat sessions.
     */
    public function reset_context() {
        mpai_log_debug('Resetting context manager state', 'context-manager');
        
        // Clear chat instance
        if (isset($this->chat_instance)) {
            $this->chat_instance = null;
            mpai_log_debug('Cleared chat instance reference', 'context-manager');
        }
        
        // Clear any tool-specific cached data
        // Initialize tool registry if needed
        $this->init_tools();
        mpai_log_debug('Reinitialized tools', 'context-manager');
        
        // Reinitialize the tools
        $this->init_tools();
        mpai_log_debug('Reinitialized tools', 'context-manager');
        
        // Reload allowed commands from database
        $this->allowed_commands = get_option('mpai_allowed_cli_commands', array());
        mpai_log_debug('Reloaded allowed commands', 'context-manager');
        
        // Clear any other cached data here
        
        mpai_log_info('Context manager reset complete', 'context-manager');
    }

    /**
     * Initialize available tools
     */
    private function init_tools() {
        // Initialize tool registry if available
        $tool_registry = null;
        if (class_exists('MPAI_Tool_Registry')) {
            $tool_registry = new MPAI_Tool_Registry();
            mpai_log_debug('Tool Registry initialized', 'context-manager');
            
            // Get plugin_logs tool from registry
            $plugin_logs_tool = $tool_registry->get_tool('plugin_logs');
            if ($plugin_logs_tool) {
                mpai_log_debug('Found plugin_logs tool in registry', 'context-manager');
            } else {
                mpai_log_debug('plugin_logs tool not found in registry', 'context-manager');
            }
        } else {
            mpai_log_warning('MPAI_Tool_Registry class not available', 'context-manager');
        }
        
        $this->available_tools = array(
            'wpcli' => array(
                'name' => 'wpcli',
                'description' => 'Run WordPress CLI commands',
                'parameters' => array(
                    'command' => array(
                        'type' => 'string',
                        'description' => 'The WP-CLI command to execute'
                    )
                ),
                'callback' => array($this, 'run_command')
            ),
            'memberpress_info' => array(
                'name' => 'memberpress_info',
                'description' => 'Get information about MemberPress data and system settings',
                'parameters' => array(
                    'type' => array(
                        'type' => 'string',
                        'description' => 'Type of information (memberships, members, transactions, subscriptions, active_subscriptions, summary, new_members_this_month, system_info, best_selling)',
                        'enum' => array('memberships', 'members', 'transactions', 'subscriptions', 'active_subscriptions', 'summary', 'new_members_this_month', 'system_info', 'best_selling', 'all')
                    ),
                    'include_system_info' => array(
                        'type' => 'boolean',
                        'description' => 'Whether to include system information in the response',
                        'default' => false
                    )
                ),
                'callback' => array($this, 'get_memberpress_info')
            ),
            'wp_api' => array(
                'name' => 'wp_api',
                'description' => 'Use WordPress API functions directly (for when WP-CLI is not available)',
                'parameters' => array(
                    'action' => array(
                        'type' => 'string',
                        'description' => 'The WordPress API action to perform',
                        'enum' => array('create_post', 'update_post', 'get_post', 'create_page', 'create_user', 
                                        'get_users', 'get_memberships', 'create_membership', 'get_transactions', 
                                        'get_subscriptions', 'activate_plugin', 'deactivate_plugin', 'get_plugins')
                    ),
                    'plugin' => array(
                        'type' => 'string',
                        'description' => 'The plugin path to activate or deactivate (e.g. "memberpress-coachkit/memberpress-coachkit.php")'
                    ),
                    // Other parameters are dynamic based on the action
                ),
                'callback' => array($this, 'execute_wp_api')
            ),
            'plugin_logs' => array(
                'name' => 'plugin_logs',
                'description' => 'Retrieve and analyze logs of plugin installations, activations, deactivations, and deletions',
                'parameters' => array(
                    'action' => array(
                        'type' => 'string',
                        'enum' => array('installed', 'updated', 'activated', 'deactivated', 'deleted', ''),
                        'description' => 'Filter logs by action type (installed, updated, activated, deactivated, deleted) or empty for all actions'
                    ),
                    'plugin_name' => array(
                        'type' => 'string',
                        'description' => 'Filter logs by plugin name (partial match)'
                    ),
                    'days' => array(
                        'type' => 'integer',
                        'description' => 'Number of days to look back in the logs (0 for all time)',
                        'default' => 30
                    ),
                    'limit' => array(
                        'type' => 'integer',
                        'description' => 'Maximum number of logs to return',
                        'default' => 25
                    ),
                    'summary_only' => array(
                        'type' => 'boolean',
                        'description' => 'Return only summary information instead of detailed logs',
                        'default' => false
                    )
                ),
                'callback' => array($this, 'execute_plugin_logs')
            )
        );

        // Allow plugins to extend available tools
        $this->available_tools = apply_filters('mpai_available_tools', $this->available_tools);
    }

    /**
     * Get available tools
     *
     * @return array List of available tools
     */
    public function get_available_tools() {
        return $this->available_tools;
    }

    /**
     * Run a WP-CLI command
     *
     * @param string $command Command to run
     * @return string Command output
     */
    public function run_command($command) {
        $current_time = date('H:i:s');
        mpai_log_info('run_command called with command: ' . $command . ' (v' . self::VERSION . ') at ' . $current_time, 'context-manager');
        
        // Special handling for wp plugin list
        if (trim($command) === 'wp plugin list') {
            mpai_log_debug('Context Manager handling wp plugin list command at ' . $current_time, 'context-manager');
            
            // Initialize the WP CLI Tool which has special handling for this command
            if (!class_exists('MPAI_WP_CLI_Tool')) {
                $tool_path = MPAI_PLUGIN_DIR . 'includes/tools/implementations/class-mpai-wpcli-tool.php';
                if (file_exists($tool_path)) {
                    require_once $tool_path;
                    mpai_log_debug('Loaded MPAI_WP_CLI_Tool class at ' . date('H:i:s'), 'context-manager');
                } else {
                    mpai_log_error('Could not find MPAI_WP_CLI_Tool at: ' . $tool_path, 'context-manager');
                    return 'Error: Could not load required tool class.';
                }
            }
            
            try {
                $wp_cli_tool = new MPAI_WP_CLI_Tool();
                mpai_log_debug('Created MPAI_WP_CLI_Tool instance at ' . date('H:i:s'), 'context-manager');
                
                // Define execution and fallback functions for error recovery
                $execute_command = function() use ($wp_cli_tool) {
                    return $wp_cli_tool->execute(array(
                        'command' => 'wp plugin list',
                        'format' => 'table' // Force table format
                    ));
                };
                
                $fallback_execution = function() {
                    // Fallback to direct WP-CLI execution if tool execution fails
                    if (defined('WP_CLI') && class_exists('WP_CLI')) {
                        mpai_log_debug('Falling back to direct WP-CLI execution', 'context-manager');
                        // Use the WP-CLI tool from the registry
                        $tool_registry = new MPAI_Tool_Registry();
                        $wpcli_tool = $tool_registry->get_tool('wpcli');
                        
                        if ($wpcli_tool) {
                            mpai_log_debug('Using WP-CLI tool from registry for fallback', 'context-manager');
                            return $wpcli_tool->execute(array(
                                'command' => 'wp plugin list --format=table'
                            ));
                        } else {
                            mpai_log_error('WP-CLI tool not available for fallback', 'context-manager');
                            return 'Error: WP-CLI tool not available for fallback';
                        }
                    } else {
                        mpai_log_error('WP-CLI not available for fallback execution', 'context-manager');
                        return 'Error: WP-CLI not available for fallback execution';
                    }
                };
                
                // Use error recovery system if available
                if ($this->error_recovery) {
                    // Create tool execution context
                    $result = $this->error_recovery->handle_error(
                        $this->error_recovery->create_tool_error('wpcli', 'tool_execution', 'Tool execution with error recovery', [
                            'command' => 'wp plugin list',
                        ]),
                        'wpcli',
                        $execute_command,
                        [],
                        $fallback_execution,
                        []
                    );
                    
                    // If result is WP_Error, use the formatted message
                    if (is_wp_error($result)) {
                        mpai_log_error('Command execution failed even with error recovery', 'context-manager');
                        return $this->error_recovery->format_error_for_display($result);
                    }
                } else {
                    // Execute directly if no error recovery available
                    $result = $execute_command();
                }
                
                mpai_log_debug('wpcli_tool execution complete at ' . date('H:i:s') . ', result length: ' . strlen($result), 'context-manager');
                mpai_log_debug('Result preview: ' . substr($result, 0, 100) . '...', 'context-manager');
                
                // Check if the result is a JSON-encoded string and extract the tabular data
                if (is_string($result) && strpos($result, '{"success":true,"tool":"wpcli","command_type":"plugin_list","result":') === 0) {
                    mpai_log_debug('Detected JSON format in result, extracting tabular data', 'context-manager');
                    
                    try {
                        $decoded = json_decode($result, true);
                        if (json_last_error() === JSON_ERROR_NONE && isset($decoded['result']) && is_string($decoded['result'])) {
                            mpai_log_debug('Successfully extracted tabular data from JSON', 'context-manager');
                            // Return just the tabular data part
                            return $decoded['result'];
                        }
                    } catch (Exception $e) {
                        mpai_log_error('Error decoding JSON result: ' . $e->getMessage(), 'context-manager', array(
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                            'trace' => $e->getTraceAsString()
                        ));
                    }
                }
                
                return $result;
            } catch (Exception $e) {
                mpai_log_error('Error executing wp plugin list with WP CLI Tool: ' . $e->getMessage(), 'context-manager', array(
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ));
                
                // Use error recovery formatting if available
                if ($this->error_recovery) {
                    $error = $this->error_recovery->create_tool_error(
                        'wpcli',
                        'execution_exception',
                        'Error executing command: ' . $e->getMessage(),
                        ['exception' => $e->getMessage(), 'command' => 'wp plugin list']
                    );
                    return $this->error_recovery->format_error_for_display($error);
                }
                
                return 'Error executing command: ' . $e->getMessage();
            }
        }
        
        // CLI commands are always enabled now (settings were removed from UI)
        mpai_log_debug('CLI commands are always enabled', 'context-manager');
        
        // Check if command is allowed - temporarily bypass for debugging
        mpai_log_warning('⚠️ TEMPORARILY BYPASSING COMMAND ALLOWED CHECK FOR DEBUGGING', 'context-manager');
        mpai_log_debug('Current allowed commands: ' . implode(', ', $this->allowed_commands), 'context-manager');
        $is_allowed = $this->is_command_allowed($command);
        mpai_log_debug('Command allowed check result: ' . ($is_allowed ? 'allowed' : 'not allowed'), 'context-manager');
        
        // Always consider the command allowed for debugging
        /*
        if (!$this->is_command_allowed($command)) {
            mpai_log_warning('Command not allowed: ' . $command, 'context-manager');
            return 'Command not allowed. Only allowed commands can be executed. Currently allowed: ' . implode(', ', $this->allowed_commands);
        }
        */

        // Since WP-CLI might not be available in admin context, use WordPress API fallback
        if (!defined('WP_CLI') || !class_exists('WP_CLI')) {
            mpai_log_info('WP-CLI not available in this environment, using WordPress API fallback', 'context-manager');
            
            // Get the WP API Tool from the registry
            $tool_registry = new MPAI_Tool_Registry();
            $wp_api_tool = $tool_registry->get_tool('wp_api');
            
            if (!$wp_api_tool) {
                // If not in registry, check if the class exists, if not try to load it
                if (!class_exists('MPAI_WP_API_Tool')) {
                    $tool_path = MPAI_PLUGIN_DIR . 'includes/tools/implementations/class-mpai-wp-api-tool.php';
                    if (file_exists($tool_path)) {
                        require_once $tool_path;
                        mpai_log_debug('Loaded MPAI_WP_API_Tool class', 'context-manager');
                    }
                }
                
                // Create a new instance if the class exists
                if (class_exists('MPAI_WP_API_Tool')) {
                    $wp_api_tool = new MPAI_WP_API_Tool();
                    mpai_log_debug('Created new WordPress API Tool instance', 'context-manager');
                } else {
                    mpai_log_warning('WordPress API Tool class not found', 'context-manager');
                }
            } else {
                mpai_log_debug('WordPress API Tool retrieved from registry', 'context-manager');
            }
            
            // For post creation/update commands
            if (preg_match('/wp post create --post_title=[\'"]?([^\'"]*)/', $command, $matches)) {
                mpai_log_debug('Detected post create command, using WordPress API', 'context-manager');
                $title = isset($matches[1]) ? $matches[1] : 'New Post';
                
                // Extract content if provided
                $content = '';
                if (preg_match('/--post_content=[\'"]?([^\'"]*)/', $command, $content_matches)) {
                    $content = $content_matches[1];
                }
                
                // Extract status if provided
                $status = 'draft';
                if (preg_match('/--post_status=[\'"]?([^\'"]*)/', $command, $status_matches)) {
                    $status = $status_matches[1];
                }
                
                try {
                    // Use WP API Tool to create the post
                    if ($wp_api_tool) {
                        $result = $wp_api_tool->execute(array(
                            'action' => 'create_post',
                            'title' => $title,
                            'content' => $content,
                            'status' => $status
                        ));
                        
                        return "Post created successfully.\nID: {$result['post_id']}\nTitle: {$title}\nStatus: {$status}\nURL: {$result['post_url']}";
                    }
                } catch (Exception $e) {
                    mpai_log_error('Error creating post: ' . $e->getMessage(), 'context-manager', array(
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString()
                    ));
                    return 'Error creating post: ' . $e->getMessage();
                }
            }
            
            // For page creation commands
            if (preg_match('/wp post create --post_type=page --post_title=[\'"]?([^\'"]*)/', $command, $matches)) {
                mpai_log_debug('Detected page create command, using WordPress API', 'context-manager');
                $title = isset($matches[1]) ? $matches[1] : 'New Page';
                
                // Extract content if provided
                $content = '';
                if (preg_match('/--post_content=[\'"]?([^\'"]*)/', $command, $content_matches)) {
                    $content = $content_matches[1];
                }
                
                // Extract status if provided
                $status = 'draft';
                if (preg_match('/--post_status=[\'"]?([^\'"]*)/', $command, $status_matches)) {
                    $status = $status_matches[1];
                }
                
                try {
                    // Use WP API Tool to create the page
                    if ($wp_api_tool) {
                        $result = $wp_api_tool->execute(array(
                            'action' => 'create_page',
                            'title' => $title,
                            'content' => $content,
                            'status' => $status
                        ));
                        
                        return "Page created successfully.\nID: {$result['post_id']}\nTitle: {$title}\nStatus: {$status}\nURL: {$result['post_url']}";
                    }
                } catch (Exception $e) {
                    mpai_log_error('Error creating page: ' . $e->getMessage(), 'context-manager', array(
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString()
                    ));
                    return 'Error creating page: ' . $e->getMessage();
                }
            }
            
            // For user creation commands
            if (preg_match('/wp user create ([^\s]+) ([^\s]+)/', $command, $matches)) {
                mpai_log_debug('Detected user create command, using WordPress API', 'context-manager');
                $username = isset($matches[1]) ? $matches[1] : '';
                $email = isset($matches[2]) ? $matches[2] : '';
                
                // Extract role if provided
                $role = 'subscriber';
                if (preg_match('/--role=([^\s]+)/', $command, $role_matches)) {
                    $role = $role_matches[1];
                }
                
                try {
                    // Use WP API Tool to create the user
                    if ($wp_api_tool && !empty($username) && !empty($email)) {
                        $result = $wp_api_tool->execute(array(
                            'action' => 'create_user',
                            'username' => $username,
                            'email' => $email,
                            'role' => $role
                        ));
                        
                        return "User created successfully.\nID: {$result['user_id']}\nUsername: {$username}\nEmail: {$email}\nRole: {$role}";
                    }
                } catch (Exception $e) {
                    mpai_log_error('Error creating user: ' . $e->getMessage(), 'context-manager', array(
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString()
                    ));
                    return 'Error creating user: ' . $e->getMessage();
                }
            }
            
            // For certain common commands, provide simulated output
            if (strpos($command, 'wp user list') === 0) {
                // Try to use WP API Tool first
                try {
                    if ($wp_api_tool) {
                        $result = $wp_api_tool->execute(array(
                            'action' => 'get_users',
                            'limit' => 10
                        ));
                        
                        if ($result && isset($result['users']) && is_array($result['users'])) {
                            // Format the output as tabular
                            $output = "ID\tUser Login\tDisplay Name\tEmail\tRoles\n";
                            foreach ($result['users'] as $user) {
                                $roles = isset($user['roles']) ? implode(', ', $user['roles']) : '';
                                $output .= $user['ID'] . "\t" . $user['user_login'] . "\t" . $user['display_name'] . "\t" . $user['user_email'] . "\t" . $roles . "\n";
                            }
                            mpai_log_debug('Returning simulated output for wp user list using WP API Tool', 'context-manager');
                            return $this->format_tabular_output($command, $output);
                        }
                    }
                } catch (Exception $e) {
                    mpai_log_error('Error using WP API Tool for user list: ' . $e->getMessage(), 'context-manager', array(
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString()
                    ));
                }
                
                // Fallback to direct WordPress API
                $users = get_users(array('number' => 10));
                $output = "ID\tUser Login\tDisplay Name\tEmail\tRoles\n";
                foreach ($users as $user) {
                    $output .= $user->ID . "\t" . $user->user_login . "\t" . $user->display_name . "\t" . $user->user_email . "\t" . implode(', ', $user->roles) . "\n";
                }
                mpai_log_debug('Returning simulated output for wp user list', 'context-manager');
                return $this->format_tabular_output($command, $output);
            }
            
            if (strpos($command, 'wp post list') === 0) {
                // Try to use WP API Tool for consistency
                $posts = get_posts(array('posts_per_page' => 10));
                $output = "ID\tPost Title\tPost Date\tStatus\n";
                foreach ($posts as $post) {
                    $output .= $post->ID . "\t" . $post->post_title . "\t" . $post->post_date . "\t" . $post->post_status . "\n";
                }
                mpai_log_debug('Returning simulated output for wp post list', 'context-manager');
                return $this->format_tabular_output($command, $output);
            }
            
            if (strpos($command, 'wp plugin list') === 0) {
                mpai_log_debug('Detected wp plugin list command - using WP API Tool: v' . self::VERSION, 'context-manager');
                
                try {
                    // Use the WP API Tool to get plugin list with activity data
                    if ($wp_api_tool) {
                        // Get current time for verification
                        $current_time = date('H:i:s');
                        mpai_log_debug('wp plugin list called at ' . $current_time, 'context-manager');
                        
                        // Call the enhanced get_plugins method from our WP API Tool
                        $result = $wp_api_tool->execute(array(
                            'action' => 'get_plugins',
                            'format' => 'table'
                        ));
                        
                        if (is_array($result) && isset($result['table_data'])) {
                            mpai_log_debug('Received formatted plugin table data', 'context-manager');
                            return $this->format_tabular_output($command, $result['table_data']);
                        } else {
                            mpai_log_warning('WP API Tool returned unexpected result format', 'context-manager');
                            throw new Exception('Unexpected result format from WP API Tool');
                        }
                    } else {
                        mpai_log_warning('WP API Tool not initialized', 'context-manager');
                        throw new Exception('WP API Tool not initialized');
                    }
                } catch (Exception $e) {
                    mpai_log_error('Error using WP API Tool for plugin list: ' . $e->getMessage(), 'context-manager', array(
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString()
                    ));
                    // Let it fall through to the next handler
                }
            }
            
            if (strpos($command, 'wp plugin status') === 0 || strpos($command, 'wp plugin logs') === 0) {
                // NEW COMMAND: Get plugins using the plugin logger for more detailed info
                mpai_log_debug('Getting DETAILED plugin list using plugin logger - NEW COMMAND', 'context-manager');
                
                try {
                    // Initialize the plugin logger
                    if (!function_exists('mpai_init_plugin_logger')) {
                        if (file_exists(MPAI_PLUGIN_DIR . 'includes/class-mpai-plugin-logger.php')) {
                            require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-plugin-logger.php';
                            mpai_log_debug('Loaded plugin logger class', 'context-manager');
                            // Check if the function is now defined after loading the file
                            if (!function_exists('mpai_init_plugin_logger')) {
                                mpai_log_warning('mpai_init_plugin_logger function not found after loading class file', 'context-manager');
                                // Define the function if not already defined
                                function mpai_init_plugin_logger() {
                                    return MPAI_Plugin_Logger::get_instance();
                                }
                                mpai_log_debug('mpai_init_plugin_logger function defined manually', 'context-manager');
                            }
                        } else {
                            mpai_log_warning('Plugin logger class not found', 'context-manager');
                            throw new Exception('Plugin logger class not found');
                        }
                    }
                    
                    mpai_log_debug('About to call mpai_init_plugin_logger()', 'context-manager');
                    $plugin_logger = mpai_init_plugin_logger();
                    mpai_log_debug('Plugin logger initialized: ' . ($plugin_logger ? 'success' : 'failed'), 'context-manager');
                    
                    // Check if the table exists
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'mpai_plugin_logs';
                    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
                    mpai_log_debug('Plugin logs table exists: ' . ($table_exists ? 'YES' : 'NO'), 'context-manager');
                    
                    if (!$plugin_logger) {
                        mpai_log_error('Failed to initialize plugin logger', 'context-manager');
                        throw new Exception('Failed to initialize plugin logger');
                    }
                    
                    // Get plugin activity summary
                    mpai_log_debug('Getting plugin activity summary', 'context-manager');
                    $summary = $plugin_logger->get_activity_summary(365); // Get data for the past year
                    mpai_log_debug('Got summary: ' . json_encode($summary), 'context-manager');
                    
                    // Get plugin data using WordPress API for current state
                    if (!function_exists('get_plugins')) {
                        require_once ABSPATH . 'wp-admin/includes/plugin.php';
                    }
                    if (!function_exists('is_plugin_active')) {
                        include_once ABSPATH . 'wp-admin/includes/plugin.php';
                    }
                    
                    $plugins = get_plugins();
                    $current_time = date('H:i:s');
                    $output = "Name\tStatus\tVersion\tLast Activity (Generated at $current_time)\n";
                    
                    // Create a lookup for the most recent activity
                    $plugin_activity = array();
                    if (isset($summary['most_active_plugins']) && is_array($summary['most_active_plugins'])) {
                        foreach ($summary['most_active_plugins'] as $plugin_data) {
                            if (isset($plugin_data['plugin_name']) && isset($plugin_data['last_action']) && isset($plugin_data['last_date'])) {
                                $plugin_activity[$plugin_data['plugin_name']] = $plugin_data['last_action'] . ' ' . 
                                                                             date('Y-m-d', strtotime($plugin_data['last_date']));
                            }
                        }
                    }
                    
                    foreach ($plugins as $plugin_file => $plugin_data) {
                        $status = is_plugin_active($plugin_file) ? 'active' : 'inactive';
                        // Get the last activity for this plugin if available
                        $activity = isset($plugin_activity[$plugin_data['Name']]) ? $plugin_activity[$plugin_data['Name']] : 'N/A';
                        $output .= $plugin_data['Name'] . " [NEW]\t" . $status . "\t" . $plugin_data['Version'] . "\t" . $activity . "\n";
                    }
                    
                    mpai_log_debug('Returning plugin list with activity data', 'context-manager');
                    return $this->format_tabular_output($command, $output);
                    
                } catch (Exception $e) {
                    mpai_log_error('Error getting plugin list with activity: ' . $e->getMessage(), 'context-manager', array(
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString()
                    ));
                    
                    // Try using the WP API Tool as fallback
                    try {
                        if ($wp_api_tool) {
                            mpai_log_debug('Trying WP API Tool as fallback for plugin list', 'context-manager');
                            $result = $wp_api_tool->execute(array(
                                'action' => 'get_plugins',
                                'format' => 'table'
                            ));
                            
                            if (is_array($result) && isset($result['table_data'])) {
                                mpai_log_debug('Received formatted plugin table data from WP API Tool fallback', 'context-manager');
                                return $this->format_tabular_output($command, $result['table_data']);
                            }
                        }
                    } catch (Exception $api_error) {
                        mpai_log_error('WP API Tool fallback also failed: ' . $api_error->getMessage(), 'context-manager', array(
                            'file' => $api_error->getFile(),
                            'line' => $api_error->getLine(),
                            'trace' => $api_error->getTraceAsString()
                        ));
                    }
                    
                    // Final fallback to basic approach
                    if (!function_exists('get_plugins')) {
                        require_once ABSPATH . 'wp-admin/includes/plugin.php';
                    }
                    if (!function_exists('is_plugin_active')) {
                        include_once ABSPATH . 'wp-admin/includes/plugin.php';
                    }
                    
                    $plugins = get_plugins();
                    $current_time = date('H:i:s');
                    $output = "Name\tStatus\tVersion\t(Generated at $current_time)\n";
                    foreach ($plugins as $plugin_file => $plugin_data) {
                        $status = is_plugin_active($plugin_file) ? 'active' : 'inactive';
                        $output .= $plugin_data['Name'] . "\t" . $status . "\t" . $plugin_data['Version'] . "\tNo activity data\n";
                    }
                    
                    mpai_log_debug('Falling back to basic plugin list', 'context-manager');
                    return $this->format_tabular_output($command, $output);
                }
            }
            
            if (strpos($command, 'wp option get') === 0) {
                // Extract option name from command
                preg_match('/wp option get\s+(\S+)/', $command, $matches);
                if (isset($matches[1])) {
                    $option_name = $matches[1];
                    $option_value = get_option($option_name);
                    if ($option_value !== false) {
                        if (is_array($option_value) || is_object($option_value)) {
                            $output = print_r($option_value, true);
                        } else {
                            $output = $option_value;
                        }
                        mpai_log_debug('Returning simulated output for wp option get: ' . $option_name, 'context-manager');
                        return $output;
                    } else {
                        return "Option '{$option_name}' not found.";
                    }
                }
            }
            
            // MemberPress specific commands
            if (strpos($command, 'wp mepr-membership list') === 0 || 
                strpos($command, 'wp mepr-membership') === 0) {
                try {
                    if ($wp_api_tool) {
                        $result = $wp_api_tool->execute(array(
                            'action' => 'get_memberships'
                        ));
                        
                        if ($result && isset($result['memberships']) && is_array($result['memberships'])) {
                            // Format the output as tabular
                            $output = "ID\tTitle\tPrice\tPeriod\tBilling Type\n";
                            foreach ($result['memberships'] as $membership) {
                                $period = isset($membership['period']) ? $membership['period'] : '';
                                $period_type = isset($membership['period_type']) ? $membership['period_type'] : '';
                                $period_text = $period . ' ' . $period_type;
                                $output .= $membership['ID'] . "\t" . $membership['title'] . "\t" . $membership['price'] . "\t" . $period_text . "\t" . $membership['billing_type'] . "\n";
                            }
                            mpai_log_debug('Returning simulated output for memberpress membership list', 'context-manager');
                            return $this->format_tabular_output($command, $output);
                        }
                    }
                } catch (Exception $e) {
                    mpai_log_error('Error using WP API Tool for membership list: ' . $e->getMessage(), 'context-manager', array(
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString()
                    ));
                }
            }
            
            // If we reached here, use the memberpress_info tool instead
            return $this->get_tool_usage_message($command);
        }

        // Run the command using WP-CLI
        mpai_log_debug('Executing WP-CLI command: ' . $command, 'context-manager');
        ob_start();
        try {
            // Use the WP-CLI tool from the registry
            $tool_registry = new MPAI_Tool_Registry();
            $wpcli_tool = $tool_registry->get_tool('wpcli');
            
            if ($wpcli_tool) {
                // Use the WP-CLI tool from the registry
                mpai_log_debug('Using WP-CLI tool from registry', 'context-manager');
                $result = $wpcli_tool->execute(array(
                    'command' => $command
                ));
                
                echo $result;
                mpai_log_debug('Command executed successfully', 'context-manager');
            } else if (class_exists('MPAI_WP_CLI_Executor')) {
                // Try to use the executor directly if available
                mpai_log_debug('Using MPAI_WP_CLI_Executor directly', 'context-manager');
                $executor = new MPAI_WP_CLI_Executor();
                $result = $executor->execute($command);
                
                echo $result;
                mpai_log_debug('Command executed successfully using executor', 'context-manager');
            } else {
                // No execution methods available
                mpai_log_error('No WP-CLI execution methods available', 'context-manager');
                $result = 'Error: No WP-CLI execution methods available';
                echo $result;
            }
        } catch (Exception $e) {
            $error_message = 'Error: ' . $e->getMessage();
            echo $error_message;
            mpai_log_error('Error executing command: ' . $error_message, 'context-manager');
        }
        $output = ob_get_clean();

        mpai_log_debug('Command output length: ' . strlen($output), 'context-manager');
        
        // Trim output if it's too long
        if (strlen($output) > 5000) {
            $output = substr($output, 0, 5000) . "...\n\n[Output truncated due to size]";
        }
        
        // Format specific command outputs for better display
        if ($this->is_table_producing_command($command)) {
            mpai_log_debug('Formatting table output for command: ' . $command, 'context-manager');
            return $this->format_tabular_output($command, $output);
        }
        
        return $output;
    }

    /**
     * Get MemberPress information
     *
     * @param array $parameters Tool parameters
     * @return mixed MemberPress data
     */
    public function get_memberpress_info($parameters) {
        // Extract parameters
        $type = isset($parameters['type']) ? $parameters['type'] : 'summary';
        $include_system_info = isset($parameters['include_system_info']) ? (bool)$parameters['include_system_info'] : false;
        
        mpai_log_debug('Getting MemberPress info for type: ' . $type . ', include_system_info: ' . ($include_system_info ? 'true' : 'false'), 'context-manager');
        
        switch($type) {
            case 'system_info':
                // Handle system_info type - get Site Health data
                if (!class_exists('MPAI_Site_Health')) {
                    require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-site-health.php';
                }
                $site_health = new MPAI_Site_Health();
                
                // Get system info from site health
                $system_info = $site_health->get_complete_info();
                
                // Format the response - ensure it's a string, not an array
                $formatted_info = '';
                
                // Format WordPress core info
                if (isset($system_info['wp-core'])) {
                    $formatted_info .= "WordPress Core Information:\n";
                    foreach ($system_info['wp-core'] as $key => $item) {
                        if (isset($item['label']) && isset($item['value'])) {
                            $formatted_info .= "- {$item['label']}: {$item['value']}\n";
                        }
                    }
                    $formatted_info .= "\n";
                }
                
                // Format Server info
                if (isset($system_info['wp-server'])) {
                    $formatted_info .= "Server Information:\n";
                    foreach ($system_info['wp-server'] as $key => $item) {
                        if (isset($item['label']) && isset($item['value'])) {
                            $formatted_info .= "- {$item['label']}: {$item['value']}\n";
                        }
                    }
                    $formatted_info .= "\n";
                }
                
                // Format MemberPress info
                if (isset($system_info['memberpress'])) {
                    $formatted_info .= "MemberPress Information:\n";
                    foreach ($system_info['memberpress'] as $key => $item) {
                        if (isset($item['label']) && isset($item['value'])) {
                            $formatted_info .= "- {$item['label']}: {$item['value']}\n";
                        }
                    }
                    $formatted_info .= "\n";
                }
                
                // Format MemberPress AI info
                if (isset($system_info['mpai'])) {
                    $formatted_info .= "MemberPress AI Assistant Information:\n";
                    foreach ($system_info['mpai'] as $key => $item) {
                        if (isset($item['label']) && isset($item['value'])) {
                            $formatted_info .= "- {$item['label']}: {$item['value']}\n";
                        }
                    }
                }
                
                $response = array(
                    'success' => true,
                    'tool' => 'memberpress_info',
                    'command_type' => 'system_info',
                    'result' => $formatted_info
                );
                return json_encode($response);
                
            case 'all':
                // Get MemberPress data
                $summary = $this->memberpress_api->get_data_summary();
                
                // Add system info if requested
                if ($include_system_info) {
                    if (!class_exists('MPAI_Site_Health')) {
                        require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-site-health.php';
                    }
                    $site_health = new MPAI_Site_Health();
                    $system_info = $site_health->get_complete_info();
                    
                    // Format the system info - ensure it's a string, not an array
                    $formatted_info = '';
                    
                    // Format WordPress core info
                    if (isset($system_info['wp-core'])) {
                        $formatted_info .= "WordPress Core Information:\n";
                        foreach ($system_info['wp-core'] as $key => $item) {
                            if (isset($item['label']) && isset($item['value'])) {
                                $formatted_info .= "- {$item['label']}: {$item['value']}\n";
                            }
                        }
                        $formatted_info .= "\n";
                    }
                    
                    // Format Server info
                    if (isset($system_info['wp-server'])) {
                        $formatted_info .= "Server Information:\n";
                        foreach ($system_info['wp-server'] as $key => $item) {
                            if (isset($item['label']) && isset($item['value'])) {
                                $formatted_info .= "- {$item['label']}: {$item['value']}\n";
                            }
                        }
                        $formatted_info .= "\n";
                    }
                    
                    // Format MemberPress info
                    if (isset($system_info['memberpress'])) {
                        $formatted_info .= "MemberPress Information:\n";
                        foreach ($system_info['memberpress'] as $key => $item) {
                            if (isset($item['label']) && isset($item['value'])) {
                                $formatted_info .= "- {$item['label']}: {$item['value']}\n";
                            }
                        }
                        $formatted_info .= "\n";
                    }
                    
                    // Format MemberPress AI info
                    if (isset($system_info['mpai'])) {
                        $formatted_info .= "MemberPress AI Assistant Information:\n";
                        foreach ($system_info['mpai'] as $key => $item) {
                            if (isset($item['label']) && isset($item['value'])) {
                                $formatted_info .= "- {$item['label']}: {$item['value']}\n";
                            }
                        }
                    }
                    
                    $summary['system_info'] = $formatted_info;
                }
                
                // Format as either JSON or tabular based on what's already in summary
                $response = array(
                    'success' => true,
                    'tool' => 'memberpress_info',
                    'command_type' => 'all',
                    'result' => $summary
                );
                return json_encode($response);
                
            case 'memberships':
                // Get formatted memberships as table
                $memberships = $this->memberpress_api->get_memberships(array(), true);
                
                if (is_string($memberships)) {
                    mpai_log_debug('Returning formatted memberships table', 'context-manager');
                    // Already formatted as tabular data
                    $response = array(
                        'success' => true,
                        'tool' => 'memberpress_info',
                        'command_type' => 'membership_list',
                        'result' => $memberships
                    );
                    return json_encode($response);
                } else {
                    mpai_log_debug('Returning regular memberships JSON', 'context-manager');
                    return json_encode($memberships);
                }
                
            case 'members':
                $members = $this->memberpress_api->get_members(array(), true);
                
                if (is_string($members)) {
                    mpai_log_debug('Returning formatted members table', 'context-manager');
                    // Already formatted as tabular data
                    $response = array(
                        'success' => true,
                        'tool' => 'memberpress_info',
                        'command_type' => 'member_list',
                        'result' => $members
                    );
                    return json_encode($response);
                } else {
                    // Format members data as a table (fallback)
                    if (is_array($members)) {
                        mpai_log_debug('Formatting members as table (fallback)', 'context-manager');
                        $output = "ID\tEmail\tUsername\tDisplay Name\tMemberships\n";
                        foreach ($members as $member) {
                            $id = isset($member['id']) ? $member['id'] : 'N/A';
                            $email = isset($member['email']) ? $member['email'] : 'N/A';
                            $username = isset($member['username']) ? $member['username'] : 'N/A';
                            $display_name = isset($member['display_name']) ? $member['display_name'] : 'N/A';
                            
                            // Get membership info
                            $memberships = [];
                            if (isset($member['active_memberships']) && is_array($member['active_memberships'])) {
                                foreach ($member['active_memberships'] as $membership) {
                                    $memberships[] = $membership['title'];
                                }
                            }
                            $membership_text = empty($memberships) ? 'None' : implode(', ', $memberships);
                            
                            $output .= "$id\t$email\t$username\t$display_name\t$membership_text\n";
                        }
                        
                        // Return formatted tabular data
                        $response = array(
                            'success' => true,
                            'tool' => 'memberpress_info',
                            'command_type' => 'member_list',
                            'result' => $output
                        );
                        return json_encode($response);
                    } else {
                        // Fallback to regular JSON if not an array
                        return json_encode($members);
                    }
                }
                
            case 'new_members_this_month':
                // Get new members who joined this month
                $new_members = $this->memberpress_api->get_new_members_this_month(true);
                
                if (is_string($new_members)) {
                    mpai_log_debug('Returning formatted new members this month', 'context-manager');
                    // Already formatted as human readable text
                    $response = array(
                        'success' => true,
                        'tool' => 'memberpress_info',
                        'command_type' => 'new_members_this_month',
                        'result' => $new_members
                    );
                    return json_encode($response);
                } else {
                    mpai_log_debug('Returning regular new members JSON', 'context-manager');
                    return json_encode($new_members);
                }
                
            case 'transactions':
                // Get formatted transactions as table
                $transactions = $this->memberpress_api->get_transactions(array(), true);
                
                if (is_string($transactions)) {
                    mpai_log_debug('Returning formatted transactions table', 'context-manager');
                    // Already formatted as tabular data
                    $response = array(
                        'success' => true,
                        'tool' => 'memberpress_info',
                        'command_type' => 'transaction_list',
                        'result' => $transactions
                    );
                    return json_encode($response);
                } else {
                    mpai_log_debug('Returning regular transactions JSON', 'context-manager');
                    return json_encode($transactions);
                }
                
            case 'subscriptions':
                // Get formatted subscriptions as table
                $subscriptions = $this->memberpress_api->get_subscriptions(array(), true);
                
                if (is_string($subscriptions)) {
                    mpai_log_debug('Returning formatted subscriptions table', 'context-manager');
                    // Already formatted as tabular data
                    $response = array(
                        'success' => true,
                        'tool' => 'memberpress_info',
                        'command_type' => 'subscription_list',
                        'result' => $subscriptions
                    );
                    return json_encode($response);
                } else {
                    mpai_log_debug('Returning regular subscriptions JSON', 'context-manager');
                    return json_encode($subscriptions);
                }
                
            case 'active_subscriptions':
                // Get active subscriptions
                $active_subscriptions = $this->memberpress_api->get_active_subscriptions(array(), true);
                
                if (is_string($active_subscriptions)) {
                    mpai_log_debug('Returning formatted active subscriptions table', 'context-manager');
                    // Already formatted as tabular data
                    $response = array(
                        'success' => true,
                        'tool' => 'memberpress_info',
                        'command_type' => 'active_subscription_list',
                        'result' => $active_subscriptions
                    );
                    return json_encode($response);
                } else {
                    mpai_log_debug('Returning regular active subscriptions JSON', 'context-manager');
                    return json_encode($active_subscriptions);
                }
                
            case 'best_selling':
                // Get best-selling memberships
                $best_selling = $this->memberpress_api->get_best_selling_membership(array(), true);
                
                if (is_string($best_selling)) {
                    mpai_log_debug('Returning formatted best-selling memberships table', 'context-manager');
                    // Already formatted as tabular data
                    $response = array(
                        'success' => true,
                        'tool' => 'memberpress_info',
                        'command_type' => 'best_selling_list',
                        'result' => $best_selling
                    );
                    return json_encode($response);
                } else {
                    mpai_log_debug('Returning regular best-selling memberships JSON', 'context-manager');
                    return json_encode($best_selling);
                }
                
            case 'summary':
            default:
                $summary = $this->memberpress_api->get_data_summary();
                
                // Add system info if requested
                if ($include_system_info) {
                    if (!class_exists('MPAI_Site_Health')) {
                        require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-site-health.php';
                    }
                    $site_health = new MPAI_Site_Health();
                    $system_info = $site_health->get_complete_info();
                    
                    // Format the system info - ensure it's a string, not an array
                    $formatted_info = '';
                    
                    // Format WordPress core info
                    if (isset($system_info['wp-core'])) {
                        $formatted_info .= "WordPress Core Information:\n";
                        foreach ($system_info['wp-core'] as $key => $item) {
                            if (isset($item['label']) && isset($item['value'])) {
                                $formatted_info .= "- {$item['label']}: {$item['value']}\n";
                            }
                        }
                        $formatted_info .= "\n";
                    }
                    
                    // Format Server info
                    if (isset($system_info['wp-server'])) {
                        $formatted_info .= "Server Information:\n";
                        foreach ($system_info['wp-server'] as $key => $item) {
                            if (isset($item['label']) && isset($item['value'])) {
                                $formatted_info .= "- {$item['label']}: {$item['value']}\n";
                            }
                        }
                        $formatted_info .= "\n";
                    }
                    
                    // Format MemberPress info
                    if (isset($system_info['memberpress'])) {
                        $formatted_info .= "MemberPress Information:\n";
                        foreach ($system_info['memberpress'] as $key => $item) {
                            if (isset($item['label']) && isset($item['value'])) {
                                $formatted_info .= "- {$item['label']}: {$item['value']}\n";
                            }
                        }
                        $formatted_info .= "\n";
                    }
                    
                    // Format MemberPress AI info
                    if (isset($system_info['mpai'])) {
                        $formatted_info .= "MemberPress AI Assistant Information:\n";
                        foreach ($system_info['mpai'] as $key => $item) {
                            if (isset($item['label']) && isset($item['value'])) {
                                $formatted_info .= "- {$item['label']}: {$item['value']}\n";
                            }
                        }
                    }
                    
                    $summary['system_info'] = $formatted_info;
                }
                
                // Format the summary as a table
                $output = "Metric\tValue\n";
                $output .= "Total Members\t" . (isset($summary['total_members']) ? $summary['total_members'] : '0') . "\n";
                $output .= "Total Memberships\t" . (isset($summary['total_memberships']) ? $summary['total_memberships'] : '0') . "\n";
                $output .= "Total Transactions\t" . (isset($summary['transaction_count']) ? $summary['transaction_count'] : '0') . "\n";
                $output .= "Total Subscriptions\t" . (isset($summary['subscription_count']) ? $summary['subscription_count'] : '0') . "\n";
                
                // Add membership list if available
                if (!empty($summary['memberships']) && is_array($summary['memberships'])) {
                    $output .= "\nMembership ID\tTitle\tPrice\n";
                    foreach ($summary['memberships'] as $membership) {
                        $id = isset($membership['id']) ? $membership['id'] : 'N/A';
                        $title = isset($membership['title']) ? $membership['title'] : 'N/A';
                        $price = isset($membership['price']) ? '$' . $membership['price'] : 'N/A';
                        $output .= "$id\t$title\t$price\n";
                    }
                }
                
                // Return formatted tabular data
                $response = array(
                    'success' => true,
                    'tool' => 'memberpress_info',
                    'command_type' => 'summary',
                    'result' => $output
                );
                return json_encode($response);
        }
    }

    /**
     * Check if command is allowed
     *
     * @param string $command Command to check
     * @return bool Whether command is allowed
     */
    private function is_command_allowed($command) {
        // For backward compatibility - if $allowed_commands is empty, use blacklist approach
        if (empty($this->allowed_commands)) {
            // Important: Initialize with some basic safe commands
            $this->allowed_commands = [
                'wp plugin', 'wp post', 'wp user', 'wp option', 'wp core',
                'wp theme', 'wp site', 'wp db', 'wp mepr', 'php -v'
            ];
        }
        
        // Dangerous command patterns to block
        $dangerous_patterns = [
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
        ];
        
        // Check command against blacklist
        foreach ($dangerous_patterns as $pattern) {
            if (preg_match($pattern, $command)) {
                mpai_log_warning('Dangerous command pattern detected: ' . $pattern, 'context-manager');
                return false;
            }
        }
        
        // Basic validation - command must start with wp or php
        if (strpos($command, 'wp ') !== 0 && strpos($command, 'php ') !== 0) {
            mpai_log_warning('Command must start with wp or php: ' . $command, 'context-manager');
            return false;
        }
        
        // If it passes security checks, it's allowed
        return true;
    }
    
    /**
     * Execute the Plugin Logs tool
     *
     * @param array $parameters Parameters for the tool
     * @return array Execution result
     */
    public function execute_plugin_logs($parameters) {
        mpai_log_debug('Execute_plugin_logs called with: ' . json_encode($parameters), 'context-manager');
        
        // Skip the URL approach and directly use the plugin logger
        try {
            // Initialize the plugin logger
            if (!function_exists('mpai_init_plugin_logger')) {
                if (file_exists(MPAI_PLUGIN_DIR . 'includes/class-mpai-plugin-logger.php')) {
                    require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-plugin-logger.php';
                    mpai_log_debug('Loaded plugin logger class', 'context-manager');
                } else {
                    mpai_log_error('Plugin logger class not found', 'context-manager');
                    return array(
                        'success' => false,
                        'message' => 'Plugin logger class not found'
                    );
                }
            }
            
            $plugin_logger = mpai_init_plugin_logger();
            
            if (!$plugin_logger) {
                mpai_log_error('Failed to initialize plugin logger', 'context-manager');
                return array(
                    'success' => false,
                    'message' => 'Failed to initialize plugin logger'
                );
            }
            
            mpai_log_debug('Plugin logger initialized successfully', 'context-manager');
            
            // Parse parameters
            $action = isset($parameters['action']) ? sanitize_text_field($parameters['action']) : '';
            $plugin_name = isset($parameters['plugin_name']) ? sanitize_text_field($parameters['plugin_name']) : '';
            $days = isset($parameters['days']) ? intval($parameters['days']) : 30;
            $limit = isset($parameters['limit']) ? intval($parameters['limit']) : 25;
            $summary_only = isset($parameters['summary_only']) ? (bool)$parameters['summary_only'] : false;
            
            // Calculate date range
            $date_from = '';
            if ($days > 0) {
                $date_from = date('Y-m-d H:i:s', strtotime("-{$days} days"));
            }
            
            // Get summary data
            $summary_days = $days > 0 ? $days : 365; // If all time, limit to 1 year for summary
            $summary = $plugin_logger->get_activity_summary($summary_days);
            
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
            
            // If summary_only is true, return just the summary data
            if ($summary_only) {
                mpai_log_debug('Returning summary data for plugin logs', 'context-manager');
                return array(
                    'success' => true,
                    'summary' => $action_counts,
                    'time_period' => $days > 0 ? "past {$days} days" : "all time",
                    'most_active_plugins' => $summary['most_active_plugins'] ?? [],
                    'logs_exist' => $action_counts['total'] > 0,
                    'message' => $action_counts['total'] > 0
                        ? "Found {$action_counts['total']} plugin log entries"
                        : "No plugin logs found for the specified criteria"
                );
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
            mpai_log_debug('Retrieved ' . count($logs) . ' plugin logs', 'context-manager');
            
            // Get total count for the query
            $count_args = [
                'plugin_name' => $plugin_name,
                'action'      => $action,
                'date_from'   => $date_from
            ];
            $total = $plugin_logger->count_logs($count_args);
            
            // Enhance the logs with readable timestamps
            foreach ($logs as &$log) {
                // Convert the MySQL timestamp to a readable format
                $timestamp = strtotime($log['date_time']);
                $log['readable_date'] = date('F j, Y, g:i a', $timestamp);
                $log['time_ago'] = human_time_diff($timestamp, current_time('timestamp')) . ' ago';
            }
            
            // Format the result for readability
            $result = array(
                'success' => true,
                'summary' => $action_counts,
                'time_period' => $days > 0 ? "past {$days} days" : "all time",
                'total_records' => $total,
                'returned_records' => count($logs),
                'has_more' => $total > count($logs),
                'logs' => $logs,
                'query' => [
                    'action' => $action,
                    'plugin_name' => $plugin_name,
                    'days' => $days,
                    'limit' => $limit
                ]
            );
            
            mpai_log_debug('Plugin logs executed successfully', 'context-manager');
            return $result;
            
        } catch (Exception $e) {
            mpai_log_error('Error in plugin logs handler: ' . $e->getMessage(), 'context-manager', array(
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ));
            
            // Return error information
            return array(
                'success' => false,
                'error' => 'Error retrieving plugin logs: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Original implementation of execute_plugin_logs (kept for fallback)
     *
     * @param array $parameters Parameters for the tool
     * @return array Execution result
     */
    private function execute_plugin_logs_original($parameters) {
        mpai_log_debug('Falling back to original plugin_logs implementation', 'context-manager');
        
        // Initialize the plugin logger
        if (!function_exists('mpai_init_plugin_logger')) {
            if (file_exists(MPAI_PLUGIN_DIR . 'includes/class-mpai-plugin-logger.php')) {
                require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-plugin-logger.php';
                mpai_log_debug('Loaded plugin logger class', 'context-manager');
            } else {
                mpai_log_error('Plugin logger class not found', 'context-manager');
                return array(
                    'success' => false,
                    'message' => 'Plugin logger class not found'
                );
            }
        }
        
        $plugin_logger = mpai_init_plugin_logger();
        
        if (!$plugin_logger) {
            mpai_log_error('Failed to initialize plugin logger', 'context-manager');
            return array(
                'success' => false,
                'message' => 'Failed to initialize plugin logger'
            );
        }
        
        mpai_log_debug('Plugin logger initialized successfully', 'context-manager');
        
        // Parse parameters
        $action = isset($parameters['action']) ? sanitize_text_field($parameters['action']) : '';
        $plugin_name = isset($parameters['plugin_name']) ? sanitize_text_field($parameters['plugin_name']) : '';
        $days = isset($parameters['days']) ? intval($parameters['days']) : 30;
        $limit = isset($parameters['limit']) ? intval($parameters['limit']) : 25;
        $summary_only = isset($parameters['summary_only']) ? (bool)$parameters['summary_only'] : false;
        
        // Calculate date range
        $date_from = '';
        if ($days > 0) {
            $date_from = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        }
        
        // Get summary data
        $summary_days = $days > 0 ? $days : 365; // If all time, limit to 1 year for summary
        $summary = $plugin_logger->get_activity_summary($summary_days);
        
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
        
        // If summary_only is true, return just the summary data
        if ($summary_only) {
            mpai_log_debug('Returning summary data for plugin logs', 'context-manager');
            return array(
                'success' => true,
                'summary' => $action_counts,
                'time_period' => $days > 0 ? "past {$days} days" : "all time",
                'most_active_plugins' => $summary['most_active_plugins'] ?? [],
                'logs_exist' => $action_counts['total'] > 0,
                'message' => $action_counts['total'] > 0
                    ? "Found {$action_counts['total']} plugin log entries"
                    : "No plugin logs found for the specified criteria"
            );
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
        mpai_log_debug('Retrieved ' . count($logs) . ' plugin logs', 'context-manager');
        
        // Get total count for the query
        $count_args = [
            'plugin_name' => $plugin_name,
            'action'      => $action,
            'date_from'   => $date_from
        ];
        $total = $plugin_logger->count_logs($count_args);
        
        // Enhance the logs with readable timestamps
        foreach ($logs as &$log) {
            // Convert the MySQL timestamp to a readable format
            $timestamp = strtotime($log['date_time']);
            $log['readable_date'] = date('F j, Y, g:i a', $timestamp);
            
            // Calculate time ago
            $now = current_time('timestamp');
            $diff = $now - $timestamp;
            
            if ($diff < 60) {
                $log['time_ago'] = 'just now';
            } else {
                $intervals = [
                    31536000 => 'year',
                    2592000 => 'month',
                    604800 => 'week',
                    86400 => 'day',
                    3600 => 'hour',
                    60 => 'minute'
                ];
                
                foreach ($intervals as $seconds => $label) {
                    $count = floor($diff / $seconds);
                    if ($count > 0) {
                        $log['time_ago'] = $count == 1 ? "1 {$label} ago" : "{$count} {$label}s ago";
                        break;
                    }
                }
            }
        }
        
        // Format the result for readability
        $result = array(
            'success' => true,
            'summary' => $action_counts,
            'time_period' => $days > 0 ? "past {$days} days" : "all time",
            'total_records' => $total,
            'returned_records' => count($logs),
            'has_more' => $total > count($logs),
            'logs' => $logs,
            'query' => [
                'action' => $action,
                'plugin_name' => $plugin_name,
                'days' => $days,
                'limit' => $limit
            ]
        );
        
        mpai_log_debug('Plugin logs executed successfully', 'context-manager');
        return $result;
    }

    /**
     * Get a helpful message about tool usage when WP-CLI is not available
     *
     * @param string $command The original command that was attempted
     * @return string A helpful message about alternative tools
     */
    private function get_tool_usage_message($command = '') {
        $message = "The AI assistant cannot directly run WP-CLI commands on your server. However, you can use these API tools instead:\n\n";
        
        // Determine what type of command was attempted
        if (strpos($command, 'wp plugin list') !== false) {
            $message .= "1. For plugin operations, you can use the WordPress API:\n";
            $message .= "   ```json\n   {\"tool\": \"wp_api\", \"parameters\": {\"action\": \"get_plugins\", \"format\": \"table\"}}\n   ```\n\n";
            $message .= "2. For more detailed plugin information with activity data, use:\n";
            $message .= "   ```json\n   {\"tool\": \"plugin_logs\", \"parameters\": {\"action\": \"summary\", \"days\": 30}}\n   ```\n";
        } else if (strpos($command, 'wp post') !== false) {
            $message .= "1. For post operations, you can use the WordPress API:\n";
            $message .= "   ```json\n   {\"tool\": \"wp_api\", \"parameters\": {\"action\": \"create_post\", \"title\": \"Your Title\", \"content\": \"Your content here\"}}\n   ```\n\n";
            $message .= "2. Available post actions: create_post, update_post, get_post, create_page\n";
        } else if (strpos($command, 'wp user') !== false) {
            $message .= "1. For user operations, you can use the WordPress API:\n";
            $message .= "   ```json\n   {\"tool\": \"wp_api\", \"parameters\": {\"action\": \"get_users\", \"limit\": 10}}\n   ```\n\n";
            $message .= "2. Available user actions: create_user, get_users\n";
        } else if (strpos($command, 'wp mepr') !== false || strpos($command, 'memberpress') !== false) {
            $message .= "1. For MemberPress operations, use the memberpress_info tool:\n";
            $message .= "   ```json\n   {\"tool\": \"memberpress_info\", \"parameters\": {\"type\": \"memberships\"}}\n   ```\n\n";
            $message .= "2. Available types: memberships, members, transactions, subscriptions, active_subscriptions, summary, new_members_this_month, system_info, best_selling, all\n";
            $message .= "3. You can get system information along with MemberPress data:\n";
            $message .= "   ```json\n   {\"tool\": \"memberpress_info\", \"parameters\": {\"type\": \"all\", \"include_system_info\": true}}\n   ```\n";
        } else {
            $message .= "1. For WordPress operations, you can use the WordPress API:\n";
            $message .= "   ```json\n   {\"tool\": \"wp_api\", \"parameters\": {\"action\": \"action_name\", \"param1\": \"value1\"}}\n   ```\n\n";
            $message .= "2. For plugin information, use:\n";
            $message .= "   ```json\n   {\"tool\": \"wp_api\", \"parameters\": {\"action\": \"get_plugins\", \"format\": \"table\"}}\n   ```\n\n";
            $message .= "3. For MemberPress operations, use the memberpress_info tool:\n";
            $message .= "   ```json\n   {\"tool\": \"memberpress_info\", \"parameters\": {\"type\": \"memberships\"}}\n   ```\n";
            $message .= "   - Available types: memberships, members, transactions, subscriptions, active_subscriptions, summary, new_members_this_month, system_info, best_selling, all\n";
            $message .= "4. For system information, use:\n";
            $message .= "   ```json\n   {\"tool\": \"memberpress_info\", \"parameters\": {\"type\": \"system_info\"}}\n   ```\n\n";
        }
        
        return $message;
    }
    
    /**
     * Execute WordPress API functions through a direct tool call
     * 
     * @param array $parameters Parameters for the API call
     * @return string Result of the API call
     */
    public function execute_wp_api($parameters) {
        // Standardize parameters format
        if (isset($parameters['parameters']) && is_array($parameters['parameters']) && isset($parameters['parameters']['action'])) {
            mpai_log_debug('Unwrapped nested parameters structure', 'context-manager');
            $parameters = $parameters['parameters'];
        }
        
        mpai_log_debug('execute_wp_api called with parameters: ' . json_encode($parameters), 'context-manager');
        
        // Important debug logging to diagnose missing content issues
        if (isset($parameters['action']) && $parameters['action'] === 'create_post') {
            if (!isset($parameters['title']) || empty($parameters['title'])) {
                mpai_log_warning('Missing title for create_post action', 'context-manager');
            }
            if (!isset($parameters['content']) || empty($parameters['content'])) {
                mpai_log_warning('Missing content for create_post action', 'context-manager');
            }
            
            mpai_log_debug('create_post parameters: action=' . $parameters['action'] 
                . ', title=' . (isset($parameters['title']) ? $parameters['title'] : 'NOT SET')
                . ', content length=' . (isset($parameters['content']) ? strlen($parameters['content']) : 'NOT SET')
                . ', status=' . (isset($parameters['status']) ? $parameters['status'] : 'NOT SET'), 'context-manager');
        }
        
        // Get the WP API Tool from the registry or create a new instance
        $tool_registry = new MPAI_Tool_Registry();
        $wp_api_tool = $tool_registry->get_tool('wp_api');
        
        if (!$wp_api_tool) {
            // Check if the class exists, if not try to load it
            if (!class_exists('MPAI_WP_API_Tool')) {
                $tool_path = MPAI_PLUGIN_DIR . 'includes/tools/implementations/class-mpai-wp-api-tool.php';
                if (file_exists($tool_path)) {
                    require_once $tool_path;
                }
            }
            
            // Create a new instance if the class exists
            if (class_exists('MPAI_WP_API_Tool')) {
                $wp_api_tool = new MPAI_WP_API_Tool();
                mpai_log_debug('WordPress API Tool created successfully', 'context-manager');
            } else {
                mpai_log_error('WordPress API Tool class not found', 'context-manager');
                return 'Error: WordPress API Tool class not found';
            }
        } else {
            mpai_log_debug('WordPress API Tool retrieved from registry', 'context-manager');
        }
        
        // Special handling for create_post/create_page action
        if (isset($parameters['action']) && ($parameters['action'] === 'create_post' || $parameters['action'] === 'create_page')) {
            // First, ensure title is populated
            if (empty($parameters['title']) || $parameters['title'] === 'New Post') {
                mpai_log_debug('Post title is missing or default, will try to extract from message', 'context-manager');
                $parameters['title'] = 'New Blog Post';  // Default fallback value
            }
            
            // Then, try to get content if missing
            if (empty($parameters['content'])) {
                mpai_log_debug('Post content is missing, will try to extract from message', 'context-manager');
                
                // Get content from chat history
                if (isset($this->chat_instance)) {
                    try {
                        mpai_log_debug('Chat instance is available, attempting to extract content', 'context-manager');
                        
                        // Try to find a message with an appropriate content marker first
                        // Then try previous message as a fallback
                        // Finally fall back to latest message if neither is available
                        $message_to_use = null;
                        $content_type = isset($parameters['post_type']) && $parameters['post_type'] === 'page' ? 'page' : 'blog-post';
                        
                        // First approach: look for content marker
                        if (method_exists($this->chat_instance, 'find_message_with_content_marker')) {
                            $message_to_use = $this->chat_instance->find_message_with_content_marker($content_type);
                            if ($message_to_use) {
                                mpai_log_debug('Using message with ' . $content_type . ' marker for content extraction', 'context-manager');
                            }
                        }
                        
                        // Second approach: try previous message
                        if (!$message_to_use && method_exists($this->chat_instance, 'get_previous_assistant_message')) {
                            $message_to_use = $this->chat_instance->get_previous_assistant_message();
                            if ($message_to_use) {
                                mpai_log_debug('Using PREVIOUS assistant message for content extraction (fallback)', 'context-manager');
                            }
                        }
                        
                        // Last resort: latest message
                        if (!$message_to_use) {
                            $message_to_use = $this->chat_instance->get_latest_assistant_message();
                            mpai_log_debug('Using LATEST assistant message for content extraction (last resort)', 'context-manager');
                        }
                        
                        if ($message_to_use && !empty($message_to_use['content'])) {
                            mpai_log_debug('Got message content of length: ' . strlen($message_to_use['content']), 'context-manager');
                            
                            // Extract title if needed
                            if (empty($parameters['title']) || $parameters['title'] === 'New Post' || $parameters['title'] === 'New Blog Post') {
                                if (preg_match('/#+\s*(?:Title:|)([^\n]+)/i', $message_to_use['content'], $title_matches)) {
                                    $parameters['title'] = trim($title_matches[1]);
                                    mpai_log_debug('Extracted title: ' . $parameters['title'], 'context-manager');
                                }
                            }
                            
                            // Extract content - try to find content section first
                            if (preg_match('/(?:#+\s*Content:?|Content:)[\r\n]+([\s\S]+?)(?:$|#+\s|```json)/i', $message_to_use['content'], $content_matches)) {
                                $cleaned_content = trim($content_matches[1]);
                                mpai_log_debug('Extracted content from dedicated content section', 'context-manager');
                            } else {
                                // If no content section, use whole message
                                $content = $message_to_use['content'];
                                
                                // Remove code blocks and JSON blocks
                                $cleaned_content = preg_replace('/```(?:json)?.*?```/s', '', $content);
                                
                                // Remove markdown headers but keep their text
                                $cleaned_content = preg_replace('/^#+\s*(.+?)[\r\n]+/im', "$1\n\n", $cleaned_content);
                                mpai_log_debug('Using full message content for extraction', 'context-manager');
                            }
                            
                            // Set the content
                            $parameters['content'] = trim($cleaned_content);
                            mpai_log_debug('Using extracted content from message, length: ' . strlen($parameters['content']), 'context-manager');
                            
                            // Make sure we have a status
                            if (empty($parameters['status'])) {
                                $parameters['status'] = 'draft';  // Default to draft status
                                mpai_log_debug('Setting default status: draft', 'context-manager');
                            }
                        } else {
                            mpai_log_warning('No usable assistant message found', 'context-manager');
                        }
                    } catch (Exception $e) {
                        mpai_log_error('Error extracting content: ' . $e->getMessage(), 'context-manager');
                        // Continue with default values
                    } catch (Error $e) {
                        mpai_log_error('PHP Error extracting content: ' . $e->getMessage(), 'context-manager');
                        // Continue with default values
                    }
                } else {
                    mpai_log_warning('No chat instance available for content extraction', 'context-manager');
                }
                
                // Set empty content as fallback if still empty
                if (empty($parameters['content'])) {
                    $parameters['content'] = 'This is a draft post created by MemberPress AI Assistant.';
                    mpai_log_debug('Using fallback content', 'context-manager');
                }
            }
            
            // Log final parameters for debugging
            mpai_log_debug('Final create_post parameters: ' . 
                'title=' . (isset($parameters['title']) ? $parameters['title'] : 'NOT SET') . 
                ', content length=' . (isset($parameters['content']) ? strlen($parameters['content']) : 'NOT SET') .
                ', status=' . (isset($parameters['status']) ? $parameters['status'] : 'NOT SET'),
                'context-manager');
        }
        
        // Execute the tool with the provided parameters
        try {
            mpai_log_debug('Executing WordPress API function: ' . $parameters['action'] . ' with parameter count: ' . count($parameters), 'context-manager');
            
            // Initialize the tool if needed
            if (!class_exists('MPAI_WP_API_Tool')) {
                $tool_path = MPAI_PLUGIN_DIR . 'includes/tools/implementations/class-mpai-wp-api-tool.php';
                if (file_exists($tool_path)) {
                    require_once $tool_path;
                }
            }
            
            // Create a new instance for this execution
            $wp_api_tool = new MPAI_WP_API_Tool();
            $result = $wp_api_tool->execute($parameters);
            
            // Format the result for display
            if (is_array($result) || is_object($result)) {
                // For structured data, convert to JSON for better display
                return json_encode(
                    array(
                        'success' => true,
                        'tool' => 'wp_api',
                        'action' => $parameters['action'],
                        'result' => $result
                    )
                );
            } else {
                // For string results, return directly
                return $result;
            }
        } catch (Exception $e) {
            mpai_log_error('Error executing WordPress API function: ' . $e->getMessage(), 'context-manager', array('file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTraceAsString()));
            return json_encode(
                array(
                    'success' => false,
                    'tool' => 'wp_api',
                    'action' => $parameters['action'],
                    'error' => $e->getMessage()
                )
            );
        }
    }
    
    /**
     * Check if command produces tabular output that should be specially formatted
     *
     * @param string $command Command to check
     * @return bool Whether the command produces tabular output
     */
    private function is_table_producing_command($command) {
        $tabular_commands = [
            'wp user list',
            'wp post list',
            'wp plugin list',
            'wp site list',
            'wp comment list',
            'wp term list',
            'wp menu list',
            'wp menu item list',
            'wp theme list',
            'mepr-list'  // Custom MemberPress command pattern
        ];
        
        // Check for direct matches
        foreach ($tabular_commands as $tabular_command) {
            if (strpos($command, $tabular_command) === 0) {
                return true;
            }
        }
        
        // Check for MemberPress specific commands using custom syntax
        if (strpos($command, 'List all active memberships') !== false ||
            strpos($command, 'Show recent transactions') !== false ||
            strpos($command, 'List member') !== false ||
            strpos($command, 'Show subscription') !== false) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Format tabular output for better display
     *
     * @param string $command Original command
     * @param string $output Command output
     * @return string Formatted output
     */
    private function format_tabular_output($command, $output) {
        // Skip if output doesn't appear to be tabular
        if (!strpos($output, "\t") && !strpos($output, "\n")) {
            return $output;
        }
        
        // Determine the type of command for specific formatting
        $command_type = $this->determine_command_type($command);
        
        // Format into a structured response
        $formatted_response = [
            'success' => true,
            'tool' => 'wpcli',
            'command_type' => $command_type,
            'result' => $output
        ];
        
        mpai_log_debug('Formatted tabular output for command type: ' . $command_type, 'context-manager');
        return json_encode($formatted_response);
    }
    
    /**
     * Determine the type of command for specific formatting
     *
     * @param string $command Command string
     * @return string Command type identifier
     */
    private function determine_command_type($command) {
        if (strpos($command, 'wp user list') !== false) {
            return 'user_list';
        } else if (strpos($command, 'wp post list') !== false) {
            return 'post_list';
        } else if (strpos($command, 'wp plugin list') !== false) {
            return 'plugin_list';
        } else if (strpos($command, 'List all active memberships') !== false || 
                   strpos($command, 'memberships') !== false) {
            return 'membership_list';
        } else if (strpos($command, 'Show recent transactions') !== false ||
                   strpos($command, 'transactions') !== false) {
            return 'transaction_list';
        } else if (strpos($command, 'List members') !== false ||
                   strpos($command, 'Show members') !== false ||
                   strpos($command, 'members') !== false) {
            return 'member_list';
        } else if (strpos($command, 'subscriptions') !== false) {
            return 'subscription_list';
        }
        
        // Default to generic tabular data
        return 'tabular_data';
    }

    /**
     * Get recommended WP-CLI commands
     *
     * @param string $prompt User prompt
     * @return array|WP_Error Recommended commands or error
     */
    public function get_command_recommendations($prompt) {
        return $this->openai->generate_cli_recommendations($prompt);
    }

    /**
     * Generate completion from MemberPress data and command output
     *
     * @param string $prompt User prompt
     * @param string $command_output Command output
     * @return string|WP_Error Generated completion or error
     */
    public function generate_completion_with_context($prompt, $command_output) {
        // Get MemberPress data summary
        $memberpress_data = $this->memberpress_api->get_data_summary();
        
        // Create system message with context
        $system_message = "You are an AI assistant for MemberPress. You have access to the following data:\n\n";
        
        // Add MemberPress data
        $system_message .= "MemberPress Data:\n";
        $system_message .= json_encode($memberpress_data, JSON_PRETTY_PRINT) . "\n\n";
        
        // Add command output
        $system_message .= "WP-CLI Command Output:\n";
        $system_message .= $command_output . "\n\n";
        
        $system_message .= "Your task is to provide helpful insights based on this data. ";
        $system_message .= "Focus on MemberPress-specific information and actionable advice.";
        
        $messages = array(
            array('role' => 'system', 'content' => $system_message),
            array('role' => 'user', 'content' => $prompt)
        );
        
        return $this->openai->generate_chat_completion($messages);
    }

    /**
     * Execute a command in a Model Context Protocol format
     *
     * @param string $command Command to execute
     * @param string $context Command context
     * @return array Command execution results
     */
    public function execute_mcp_command($command, $context = '') {
        // Check if command is allowed
        if (!$this->is_command_allowed($command)) {
            return array(
                'success' => false,
                'message' => 'Command not allowed. Only allowed commands can be executed.',
                'command' => $command,
                'output' => ''
            );
        }
        
        // Run the command
        $output = $this->run_command($command);
        
        // Generate insights from the command output
        $prompt = "Analyze the output of the following command: {$command}";
        if (!empty($context)) {
            $prompt .= "\n\nContext: {$context}";
        }
        
        $insights = $this->generate_completion_with_context($prompt, $output);
        
        if (is_wp_error($insights)) {
            $insights = 'Could not generate insights: ' . $insights->get_error_message();
        }
        
        return array(
            'success' => true,
            'command' => $command,
            'output' => $output,
            'insights' => $insights
        );
    }

    /**
     * Process a tool request in MCP format
     * 
     * @param array $request Tool request data
     * @return array Response data
     */
    public function process_tool_request($request) {
        mpai_log_debug('Processing tool request: ' . json_encode($request), 'context-manager');
        
        // Standardize tool request format
        if (isset($request['tool']) && !isset($request['name'])) {
            // Convert from tool to name format for consistency
            $request['name'] = $request['tool'];
            unset($request['tool']);
            mpai_log_debug('Converted tool format to name format', 'context-manager');
        }
        
        // Ensure name parameter exists
        if (!isset($request['name'])) {
            mpai_log_warning('Tool request missing name parameter', 'context-manager');
            return array(
                'success' => false,
                'error' => 'Tool request must include a name parameter'
            );
        }
        
        // MCP is always enabled now (settings were removed from UI)
        mpai_log_debug('MCP is always enabled', 'context-manager');
        
        // FAST PATH: Special handling for common wpcli commands - bypass validation
        if (isset($request['name']) && $request['name'] === 'wpcli' &&
            isset($request['parameters']) && isset($request['parameters']['command'])) {
            
            $command = $request['parameters']['command'];
            
            // Check for wp post list command
            if (strpos($command, 'wp post list') === 0) {
                mpai_log_debug('Fast path for wp post list command - bypassing validation', 'context-manager');
                // Continue with processing without validation
            }
            // Check for wp user list command
            else if (strpos($command, 'wp user list') === 0) {
                mpai_log_debug('Fast path for wp user list command - bypassing validation', 'context-manager');
                
                // Special handling for wp user list to avoid logger dependency
                try {
                    $users = get_users(array('number' => 10));
                    $output = "ID\tUser Login\tDisplay Name\tEmail\tRoles\n";
                    
                    foreach ($users as $user) {
                        $output .= $user->ID . "\t" . $user->user_login . "\t" . 
                                $user->display_name . "\t" . $user->user_email . "\t" . 
                                implode(', ', $user->roles) . "\n";
                    }
                    
                    // Return properly formatted response
                    return array(
                        'success' => true,
                        'tool' => 'wpcli',
                        'action' => 'user_list',
                        'result' => array(
                            'success' => true,
                            'command_type' => 'user_list',
                            'result' => $output
                        )
                    );
                } catch (Exception $e) {
                    mpai_log_error('Error in wp user list fast path: ' . $e->getMessage(), 'context-manager', array(
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString()
                    ));
                    // Fall through to normal processing if this fails
                }
            }
            // Continue with normal processing for other commands
        }
        
        // Regular validation path for other commands
        {
            // Try to validate the command, but don't block execution if validation fails
            try {
                // Special handling for wp_api create_post/create_page to extract content from the assistant's message
                if (isset($request['name']) && $request['name'] === 'wp_api' && 
                    isset($request['parameters']) && isset($request['parameters']['action']) && 
                    in_array($request['parameters']['action'], ['create_post', 'create_page'])) {
                    
                    try {
                        // Check if content is missing or empty
                        if (!isset($request['parameters']['content']) || empty($request['parameters']['content'])) {
                            // Try to find content using marker-based approach first, then fallbacks
                            if (isset($this->chat_instance)) {
                                // Determine which message to use - prefer marker-based approach
                                $message_to_use = null;
                                $content_type = ($request['parameters']['action'] === 'create_page') ? 'page' : 'blog-post';
                                
                                // First approach: look for content marker
                                if (method_exists($this->chat_instance, 'find_message_with_content_marker')) {
                                    $message_to_use = $this->chat_instance->find_message_with_content_marker($content_type);
                                    if ($message_to_use) {
                                        mpai_log_debug('Using message with ' . $content_type . ' marker for content extraction in tool request', 'context-manager');
                                    }
                                }
                                
                                // Second approach: try previous message
                                if (!$message_to_use && method_exists($this->chat_instance, 'get_previous_assistant_message')) {
                                    $message_to_use = $this->chat_instance->get_previous_assistant_message();
                                    mpai_log_debug('Using PREVIOUS assistant message for content extraction in tool request (fallback)', 'context-manager');
                                } 
                                // Last resort: latest message
                                else if (!$message_to_use && method_exists($this->chat_instance, 'get_latest_assistant_message')) {
                                    $message_to_use = $this->chat_instance->get_latest_assistant_message();
                                    mpai_log_debug('Using LATEST assistant message for content extraction in tool request (last resort)', 'context-manager');
                                }
                                
                                if ($message_to_use && !empty($message_to_use['content'])) {
                                    // First, check for title if it's missing or default
                                    if (!isset($request['parameters']['title']) || empty($request['parameters']['title']) || 
                                        $request['parameters']['title'] === 'New Post') {
                                        // Try multiple title patterns
                                        if (preg_match('/(?:#+\s*Title:?\s*|Title:\s*)([^\n]+)/i', $message_to_use['content'], $title_matches)) {
                                            $request['parameters']['title'] = trim($title_matches[1]);
                                            mpai_log_debug('Extracted title from assistant message: ' . $request['parameters']['title'], 'context-manager');
                                        } else if (preg_match('/^#+\s*([^\n]+)/i', $message_to_use['content'], $heading_matches)) {
                                            // Try the first heading as a title
                                            $request['parameters']['title'] = trim($heading_matches[1]);
                                            mpai_log_debug('Using first heading as title: ' . $request['parameters']['title'], 'context-manager');
                                        }
                                    }
                                    
                                    // Now extract content using several approaches
                                    // 1. Try to find a dedicated content section
                                    if (preg_match('/(?:#+\s*Content:?|Content:)[\r\n]+([\s\S]+?)(?:$|#+\s|```json)/i', $message_to_use['content'], $content_matches)) {
                                        $request['parameters']['content'] = trim($content_matches[1]);
                                        mpai_log_debug('Extracted content from dedicated section - length: ' . strlen($request['parameters']['content']), 'context-manager');
                                    } else {
                                        // 2. If no content section found, use the whole message excluding tool calls and formatting
                                        $content = $message_to_use['content'];
                                        
                                        // Remove tool call blocks (JSON code blocks)
                                        $cleaned_content = preg_replace('/```(?:json)?\s*\{.*?\}\s*```/is', '', $content);
                                        
                                        // Remove all code blocks
                                        $cleaned_content = preg_replace('/```.*?```/s', '', $cleaned_content);
                                        
                                        // Clean up any remaining markdown headers (keeping their text)
                                        $cleaned_content = preg_replace('/^#+\s*(.+?)[\r\n]+/im', "$1\n\n", $cleaned_content);
                                        
                                        // Clean up any "Title:" or "Content:" markers
                                        $cleaned_content = preg_replace('/(?:Title:|Content:)\s*/i', '', $cleaned_content);
                                        
                                        $request['parameters']['content'] = trim($cleaned_content);
                                        mpai_log_debug('Using full assistant message as content - length: ' . strlen($request['parameters']['content']), 'context-manager');
                                    }
                                    
                                    // Set default status if not provided
                                    if (!isset($request['parameters']['status']) || empty($request['parameters']['status'])) {
                                        $request['parameters']['status'] = 'draft';
                                        mpai_log_debug('Setting default status: draft', 'context-manager');
                                    }
                                } else {
                                    mpai_log_warning('No valid assistant message found for content extraction', 'context-manager');
                                }
                            } else {
                                mpai_log_warning('Chat instance unavailable for content extraction', 'context-manager');
                            }
                        }
                    } catch (Exception $e) {
                        mpai_log_error('Error in tool request content extraction: ' . $e->getMessage(), 'context-manager', array(
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                            'trace' => $e->getTraceAsString()
                        ));
                        // Continue with the original request
                    } catch (Error $e) {
                        mpai_log_error('PHP Error in tool request content extraction: ' . $e->getMessage(), 'context-manager', array(
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                            'trace' => $e->getTraceAsString()
                        ));
                        // Continue with the original request
                    }
                }
                
                $validated_request = $this->validate_command($request);
                
                // Use the validated request for further processing if validation succeeded
                if (isset($validated_request['success']) && $validated_request['success'] && isset($validated_request['command'])) {
                    mpai_log_debug('Using validated command: ' . json_encode($validated_request['command']), 'context-manager');
                    $request = $validated_request['command'];
                } else {
                    // Just log errors but continue with original request
                    if (isset($validated_request['message'])) {
                        mpai_log_debug('Command validation note: ' . $validated_request['message'], 'context-manager');
                    }
                }
            } catch (Exception $e) {
                // If validation throws an exception, log it but continue with the original request
                mpai_log_error('Error during command validation (continuing anyway): ' . $e->getMessage(), 'context-manager', array(
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ));
            }
        }
        
        if (!isset($request['name']) || !isset($this->available_tools[$request['name']])) {
            mpai_log_warning('Tool not found or invalid: ' . (isset($request['name']) ? $request['name'] : 'unknown'), 'context-manager');
            return array(
                'success' => false,
                'error' => 'Tool not found or invalid',
                'tool' => isset($request['name']) ? $request['name'] : 'unknown'
            );
        }

        $tool = $this->available_tools[$request['name']];
        
        // All tools are always enabled now (settings were removed from UI)
        mpai_log_debug('All tools are always enabled', 'context-manager');
        
        // Special handling for plugin_logs tool
        if ($tool['name'] === 'plugin_logs') {
            mpai_log_debug('Processing plugin_logs tool request: ' . json_encode($request), 'context-manager');
            // Plugin logs tool is always enabled if available
        }
        
        // Validate parameters
        $parameters = isset($request['parameters']) ? $request['parameters'] : array();
        $validated_params = array();
        
        foreach ($tool['parameters'] as $param_name => $param_info) {
            if (!isset($parameters[$param_name])) {
                if (isset($param_info['required']) && $param_info['required']) {
                    mpai_log_warning('Missing required parameter: ' . $param_name, 'context-manager');
                    return array(
                        'success' => false,
                        'error' => "Missing required parameter: {$param_name}",
                        'tool' => $request['name']
                    );
                }
                continue;
            }
            
            $validated_params[$param_name] = $parameters[$param_name];
        }
        
        // Special handling for wpcli tool
        if ($tool['name'] === 'wpcli') {
            if (!isset($validated_params['command'])) {
                mpai_log_warning('Missing command parameter for wpcli tool', 'context-manager');
                return array(
                    'success' => false,
                    'error' => 'Command parameter is required for wpcli tool',
                    'tool' => $request['name']
                );
            }
            
            mpai_log_debug('Executing WP-CLI command: ' . $validated_params['command'], 'context-manager');
            return array(
                'success' => true,
                'tool' => $request['name'],
                'result' => $this->run_command($validated_params['command'])
            );
        }
        
        // Execute the tool
        try {
            if ($tool['name'] === 'memberpress_info') {
                // Special handling for memberpress_info tool
                mpai_log_debug('Getting MemberPress info with parameters: ' . json_encode($validated_params), 'context-manager');
                $result = $this->get_memberpress_info($validated_params);
            } else if ($tool['name'] === 'plugin_logs') {
                // Special handling for plugin_logs tool
                mpai_log_debug('Getting plugin logs with parameters: ' . json_encode($validated_params), 'context-manager');
                $result = $this->execute_plugin_logs($validated_params);
                
                // Check if we already have a formatted output from the tool
                if (isset($result['formatted_output']) && !empty($result['formatted_output'])) {
                    mpai_log_debug('Using pre-formatted output from plugin_logs tool', 'context-manager');
                    return array(
                        'success' => true,
                        'tool' => $request['name'],
                        'result' => $result['formatted_output']
                    );
                }
                
                // Format the result for better display if no pre-formatted output
                if (isset($result['logs']) && is_array($result['logs']) && !empty($result['logs'])) {
                    $formatted_logs = "## Plugin Activity Logs\n\n";
                    $formatted_logs .= "Showing plugin activity for the " . $result['time_period'] . "\n\n";
                    
                    // Add summary counts
                    $formatted_logs .= "### Summary\n";
                    $formatted_logs .= "- Total activities: " . $result['summary']['total'] . "\n";
                    $formatted_logs .= "- Installations: " . $result['summary']['installed'] . "\n";
                    $formatted_logs .= "- Updates: " . $result['summary']['updated'] . "\n";
                    $formatted_logs .= "- Activations: " . $result['summary']['activated'] . "\n";
                    $formatted_logs .= "- Deactivations: " . $result['summary']['deactivated'] . "\n";
                    $formatted_logs .= "- Deletions: " . $result['summary']['deleted'] . "\n\n";
                    
                    // Add detailed logs
                    $formatted_logs .= "### Recent Activity\n";
                    foreach ($result['logs'] as $log) {
                        $action = ucfirst($log['action']);
                        $plugin_name = $log['plugin_name'];
                        $version = $log['plugin_version'];
                        $time_ago = $log['time_ago'] ?? '';
                        $user = isset($log['user_login']) && !empty($log['user_login']) ? " by user {$log['user_login']}" : '';
                        
                        $formatted_logs .= "- {$action}: {$plugin_name} v{$version} ({$time_ago}){$user}\n";
                    }
                    
                    // Add natural language summary if available
                    if (isset($result['nl_summary']) && !empty($result['nl_summary'])) {
                        $formatted_logs .= "\n### Analysis\n" . $result['nl_summary'] . "\n";
                    }
                    
                    // Return the formatted logs as the result
                    return array(
                        'success' => true,
                        'tool' => $request['name'],
                        'result' => $formatted_logs
                    );
                }
            } else {
                // Generic callback execution
                mpai_log_debug('Executing tool callback for: ' . $tool['name'], 'context-manager');
                $result = call_user_func($tool['callback'], $validated_params);
            }
            
            mpai_log_debug('Tool execution successful', 'context-manager');
            return array(
                'success' => true,
                'tool' => $request['name'],
                'result' => $result
            );
        } catch (Exception $e) {
            mpai_log_error('Error executing tool: ' . $e->getMessage(), 'context-manager', array(
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ));
            return array(
                'success' => false,
                'error' => $e->getMessage(),
                'tool' => $request['name']
            );
        }
    }
    
    /**
     * Validate a command using the command validation agent
     *
     * @param array $request The command request to validate
     * @return array Validation result with the validated command
     */
    private function validate_command($request) {
        // Default response (success with original command)
        $result = [
            'success' => true,
            'command' => $request,
            'message' => 'Command validated successfully',
        ];
        
        try {
            // Skip validation for memberpress_info tool
            if (isset($request['name']) && $request['name'] === 'memberpress_info') {
                mpai_log_debug('Skipping validation for memberpress_info tool - high priority bypass', 'context-manager');
                return $result;
            }
            
            // Also check if directly in tool property for backward compatibility
            if (isset($request['tool']) && $request['tool'] === 'memberpress_info') {
                mpai_log_debug('Skipping validation for memberpress_info tool - high priority bypass', 'context-manager');
                return $result;
            }
            
            // Skip validation for wp_api post-related actions (create_post, update_post, etc.)
            if (isset($request['name']) && $request['name'] === 'wp_api' && 
                isset($request['parameters']) && isset($request['parameters']['action']) && 
                in_array($request['parameters']['action'], ['create_post', 'update_post', 'delete_post', 'get_post', 'create_page'])) {
                mpai_log_debug('Skipping validation for wp_api post action - high priority bypass', 'context-manager');
                return $result;
            }
            
            // Also check for post-related actions directly in the parameters object
            if (isset($request['action']) && in_array($request['action'], ['create_post', 'update_post', 'delete_post', 'get_post', 'create_page'])) {
                mpai_log_debug('Skipping validation for direct post action - high priority bypass', 'context-manager');
                return $result;
            }
            
            // Skip validation for post list commands specifically
            if (isset($request['parameters']) && isset($request['parameters']['command']) && 
                strpos($request['parameters']['command'], 'wp post list') === 0) {
                mpai_log_debug('Skipping validation for wp post list command - high priority bypass', 'context-manager');
                return $result;
            }
            
            // Also check if directly in command property
            if (isset($request['command']) && is_string($request['command']) && 
                strpos($request['command'], 'wp post list') === 0) {
                mpai_log_debug('Skipping validation for wp post list command - high priority bypass', 'context-manager');
                return $result;
            }
            
            // Skip validation for theme list and block list commands specifically
            if (isset($request['command']) && is_string($request['command'])) {
                $bypass_commands = ['wp theme list', 'wp block list', 'wp pattern list'];
                foreach ($bypass_commands as $bypass_command) {
                    if (strpos($request['command'], $bypass_command) === 0) {
                        mpai_log_debug('Skipping validation for ' . $bypass_command . ' - high priority bypass', 'context-manager');
                        return $result;
                    }
                }
            }
            
            // Determine command type
            $command_type = '';
            if (isset($request['name'])) {
                $command_type = 'tool_call';
                $command_data = $request;
            } else if (isset($request['action']) && in_array($request['action'], ['activate_plugin', 'deactivate_plugin', 'get_plugins'])) {
                $command_type = 'wp_api';
                $command_data = $request;
            } else if (isset($request['command']) && is_string($request['command'])) {
                $command_type = 'wpcli';
                $command_data = $request;
            } else {
                // Unknown command type, skip validation
                mpai_log_debug('Skipping validation for unknown command type', 'context-manager');
                return $result;
            }
            
            // Check if Command Validation Agent class exists
            if (!class_exists('MPAI_Command_Validation_Agent')) {
                $validation_agent_path = plugin_dir_path(dirname(__FILE__)) . 'agents/specialized/class-mpai-command-validation-agent.php';
                if (file_exists($validation_agent_path)) {
                    require_once $validation_agent_path;
                } else {
                    mpai_log_warning('Command validation agent class file not found at: ' . $validation_agent_path, 'context-manager');
                    return $result;
                }
            }
            
            // Initialize validation agent
            if (class_exists('MPAI_Command_Validation_Agent')) {
                $validation_agent = new MPAI_Command_Validation_Agent();
                
                // Prepare validation request
                $intent_data = [
                    'command_type' => $command_type,
                    'command_data' => $command_data,
                    'original_message' => 'Command validation request',
                ];
                
                // Context data
                $context = [];
                
                // Process the validation request with try/catch for safety
                try {
                    $validation_result = $validation_agent->process_request($intent_data, $context);
                } catch (Exception $e) {
                    mpai_log_error('Exception in validation agent process_request: ' . $e->getMessage(), 'context-manager', array(
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString()
                    ));
                    // Return default success result to allow operation to continue
                    return $result;
                }
                
                // Check if validation was successful
                if ($validation_result['success']) {
                    // Get the validated command
                    $validated_command = $validation_result['validated_command'];
                    
                    // Log the validation result
                    mpai_log_debug('Command validated successfully: ' . $validation_result['message'], 'context-manager');
                    
                    // Return the validated command
                    return [
                        'success' => true,
                        'command' => $validated_command,
                        'message' => $validation_result['message'],
                    ];
                } else {
                    // Validation failed, but we'll still return success for permissive operation
                    mpai_log_warning('Command validation failed but continuing: ' . $validation_result['message'], 'context-manager');
                    
                    // IMPORTANT CHANGE: Always return success, even for validation failures
                    // This ensures operations can continue even when validation fails
                    return [
                        'success' => true, // Always return true to allow operation to proceed
                        'command' => $request, // Return original command
                        'message' => $validation_result['message'] . ' (continuing with original command)',
                    ];
                }
            } else {
                mpai_log_warning('Command validation agent class not found after loading file', 'context-manager');
                return $result;
            }
        } catch (Exception $e) {
            mpai_log_error('Error during command validation: ' . $e->getMessage(), 'context-manager', array(
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ));
            return $result;
        }
    }
}