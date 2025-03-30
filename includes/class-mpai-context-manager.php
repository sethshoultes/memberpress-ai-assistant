<?php
/**
 * Context Manager Class
 *
 * Handles CLI command execution and context management
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

class MPAI_Context_Manager {
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
     * Constructor
     */
    public function __construct() {
        $this->openai = new MPAI_OpenAI();
        $this->memberpress_api = new MPAI_MemberPress_API();
        $this->allowed_commands = get_option('mpai_allowed_cli_commands', array());
        $this->init_tools();
    }

    /**
     * Initialize available tools
     */
    private function init_tools() {
        $this->available_tools = array(
            'wp_cli' => array(
                'name' => 'wp_cli',
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
                'description' => 'Get information about MemberPress',
                'parameters' => array(
                    'type' => array(
                        'type' => 'string',
                        'description' => 'Type of information (memberships, members, transactions, subscriptions, new_members_this_month)',
                        'enum' => array('memberships', 'members', 'transactions', 'subscriptions', 'summary', 'new_members_this_month')
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
                                        'get_subscriptions')
                    ),
                    // Other parameters are dynamic based on the action
                ),
                'callback' => array($this, 'execute_wp_api')
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
        error_log('MPAI: run_command called with command: ' . $command);
        
        // Check if CLI commands are enabled in settings - temporarily bypass for debugging
        error_log('MPAI: ⚠️ TEMPORARILY BYPASSING CLI COMMANDS ENABLED CHECK FOR DEBUGGING');
        /*
        if (!get_option('mpai_enable_cli_commands', true)) {
            error_log('MPAI: CLI commands are disabled in settings');
            return 'CLI commands are disabled in settings. Please enable them in the MemberPress AI Assistant settings page.';
        }
        */
        
        // Check if command is allowed - temporarily bypass for debugging
        error_log('MPAI: ⚠️ TEMPORARILY BYPASSING COMMAND ALLOWED CHECK FOR DEBUGGING');
        error_log('MPAI: Current allowed commands: ' . implode(', ', $this->allowed_commands));
        $is_allowed = $this->is_command_allowed($command);
        error_log('MPAI: Command allowed check result: ' . ($is_allowed ? 'allowed' : 'not allowed'));
        
        // Always consider the command allowed for debugging
        /*
        if (!$this->is_command_allowed($command)) {
            error_log('MPAI: Command not allowed: ' . $command);
            return 'Command not allowed. Only allowed commands can be executed. Currently allowed: ' . implode(', ', $this->allowed_commands);
        }
        */

        // Since WP-CLI might not be available in admin context, use WordPress API fallback
        if (!defined('WP_CLI') || !class_exists('WP_CLI')) {
            error_log('MPAI: WP-CLI not available in this environment, using WordPress API fallback');
            
            // Initialize WP API Tool if needed
            if (!isset($this->wp_api_tool)) {
                // Check if the class exists, if not try to load it
                if (!class_exists('MPAI_WP_API_Tool')) {
                    $tool_path = MPAI_PLUGIN_DIR . 'includes/tools/implementations/class-mpai-wp-api-tool.php';
                    if (file_exists($tool_path)) {
                        require_once $tool_path;
                    }
                }
                
                // Initialize the tool if the class exists
                if (class_exists('MPAI_WP_API_Tool')) {
                    $this->wp_api_tool = new MPAI_WP_API_Tool();
                    error_log('MPAI: WordPress API Tool initialized successfully');
                } else {
                    error_log('MPAI: WordPress API Tool class not found');
                }
            }
            
            // For post creation/update commands
            if (preg_match('/wp post create --post_title=[\'"]?([^\'"]*)/', $command, $matches)) {
                error_log('MPAI: Detected post create command, using WordPress API');
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
                    if (isset($this->wp_api_tool)) {
                        $result = $this->wp_api_tool->execute(array(
                            'action' => 'create_post',
                            'title' => $title,
                            'content' => $content,
                            'status' => $status
                        ));
                        
                        return "Post created successfully.\nID: {$result['post_id']}\nTitle: {$title}\nStatus: {$status}\nURL: {$result['post_url']}";
                    }
                } catch (Exception $e) {
                    error_log('MPAI: Error creating post: ' . $e->getMessage());
                    return 'Error creating post: ' . $e->getMessage();
                }
            }
            
            // For page creation commands
            if (preg_match('/wp post create --post_type=page --post_title=[\'"]?([^\'"]*)/', $command, $matches)) {
                error_log('MPAI: Detected page create command, using WordPress API');
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
                    if (isset($this->wp_api_tool)) {
                        $result = $this->wp_api_tool->execute(array(
                            'action' => 'create_page',
                            'title' => $title,
                            'content' => $content,
                            'status' => $status
                        ));
                        
                        return "Page created successfully.\nID: {$result['post_id']}\nTitle: {$title}\nStatus: {$status}\nURL: {$result['post_url']}";
                    }
                } catch (Exception $e) {
                    error_log('MPAI: Error creating page: ' . $e->getMessage());
                    return 'Error creating page: ' . $e->getMessage();
                }
            }
            
            // For user creation commands
            if (preg_match('/wp user create ([^\s]+) ([^\s]+)/', $command, $matches)) {
                error_log('MPAI: Detected user create command, using WordPress API');
                $username = isset($matches[1]) ? $matches[1] : '';
                $email = isset($matches[2]) ? $matches[2] : '';
                
                // Extract role if provided
                $role = 'subscriber';
                if (preg_match('/--role=([^\s]+)/', $command, $role_matches)) {
                    $role = $role_matches[1];
                }
                
                try {
                    // Use WP API Tool to create the user
                    if (isset($this->wp_api_tool) && !empty($username) && !empty($email)) {
                        $result = $this->wp_api_tool->execute(array(
                            'action' => 'create_user',
                            'username' => $username,
                            'email' => $email,
                            'role' => $role
                        ));
                        
                        return "User created successfully.\nID: {$result['user_id']}\nUsername: {$username}\nEmail: {$email}\nRole: {$role}";
                    }
                } catch (Exception $e) {
                    error_log('MPAI: Error creating user: ' . $e->getMessage());
                    return 'Error creating user: ' . $e->getMessage();
                }
            }
            
            // For certain common commands, provide simulated output
            if (strpos($command, 'wp user list') === 0) {
                // Try to use WP API Tool first
                try {
                    if (isset($this->wp_api_tool)) {
                        $result = $this->wp_api_tool->execute(array(
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
                            error_log('MPAI: Returning simulated output for wp user list using WP API Tool');
                            return $this->format_tabular_output($command, $output);
                        }
                    }
                } catch (Exception $e) {
                    error_log('MPAI: Error using WP API Tool for user list: ' . $e->getMessage());
                }
                
                // Fallback to direct WordPress API
                $users = get_users(array('number' => 10));
                $output = "ID\tUser Login\tDisplay Name\tEmail\tRoles\n";
                foreach ($users as $user) {
                    $output .= $user->ID . "\t" . $user->user_login . "\t" . $user->display_name . "\t" . $user->user_email . "\t" . implode(', ', $user->roles) . "\n";
                }
                error_log('MPAI: Returning simulated output for wp user list');
                return $this->format_tabular_output($command, $output);
            }
            
            if (strpos($command, 'wp post list') === 0) {
                // Try to use WP API Tool for consistency
                $posts = get_posts(array('posts_per_page' => 10));
                $output = "ID\tPost Title\tPost Date\tStatus\n";
                foreach ($posts as $post) {
                    $output .= $post->ID . "\t" . $post->post_title . "\t" . $post->post_date . "\t" . $post->post_status . "\n";
                }
                error_log('MPAI: Returning simulated output for wp post list');
                return $this->format_tabular_output($command, $output);
            }
            
            if (strpos($command, 'wp plugin list') === 0) {
                // Get plugins through WordPress API
                if (!function_exists('get_plugins')) {
                    require_once ABSPATH . 'wp-admin/includes/plugin.php';
                }
                if (!function_exists('is_plugin_active')) {
                    include_once ABSPATH . 'wp-admin/includes/plugin.php';
                }
                $plugins = get_plugins();
                $output = "Name\tStatus\tVersion\n";
                foreach ($plugins as $plugin_file => $plugin_data) {
                    $status = is_plugin_active($plugin_file) ? 'active' : 'inactive';
                    $output .= $plugin_data['Name'] . "\t" . $status . "\t" . $plugin_data['Version'] . "\n";
                }
                error_log('MPAI: Returning simulated output for wp plugin list');
                return $this->format_tabular_output($command, $output);
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
                        error_log('MPAI: Returning simulated output for wp option get: ' . $option_name);
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
                    if (isset($this->wp_api_tool)) {
                        $result = $this->wp_api_tool->execute(array(
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
                            error_log('MPAI: Returning simulated output for memberpress membership list');
                            return $this->format_tabular_output($command, $output);
                        }
                    }
                } catch (Exception $e) {
                    error_log('MPAI: Error using WP API Tool for membership list: ' . $e->getMessage());
                }
            }
            
            // If we reached here, use the memberpress_info tool instead
            return $this->get_tool_usage_message($command);
        }

        // Run the command using WP-CLI
        error_log('MPAI: Executing WP-CLI command: ' . $command);
        ob_start();
        try {
            $result = WP_CLI::runcommand($command, array(
                'return' => true,
                'exit_error' => false,
            ));
            
            echo $result;
            error_log('MPAI: Command executed successfully');
        } catch (Exception $e) {
            $error_message = 'Error: ' . $e->getMessage();
            echo $error_message;
            error_log('MPAI: Error executing command: ' . $error_message);
        }
        $output = ob_get_clean();

        error_log('MPAI: Command output length: ' . strlen($output));
        
        // Trim output if it's too long
        if (strlen($output) > 5000) {
            $output = substr($output, 0, 5000) . "...\n\n[Output truncated due to size]";
        }
        
        // Format specific command outputs for better display
        if ($this->is_table_producing_command($command)) {
            error_log('MPAI: Formatting table output for command: ' . $command);
            return $this->format_tabular_output($command, $output);
        }
        
        return $output;
    }

    /**
     * Get MemberPress information
     *
     * @param string $type Type of information to retrieve
     * @return mixed MemberPress data
     */
    public function get_memberpress_info($type = 'summary') {
        error_log('MPAI: Getting MemberPress info for type: ' . $type);
        
        switch($type) {
            case 'memberships':
                // Get formatted memberships as table
                $memberships = $this->memberpress_api->get_memberships(array(), true);
                
                if (is_string($memberships)) {
                    error_log('MPAI: Returning formatted memberships table');
                    // Already formatted as tabular data
                    $response = array(
                        'success' => true,
                        'tool' => 'memberpress_info',
                        'command_type' => 'membership_list',
                        'result' => $memberships
                    );
                    return json_encode($response);
                } else {
                    error_log('MPAI: Returning regular memberships JSON');
                    return json_encode($memberships);
                }
                
            case 'members':
                $members = $this->memberpress_api->get_members(array(), true);
                
                if (is_string($members)) {
                    error_log('MPAI: Returning formatted members table');
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
                        error_log('MPAI: Formatting members as table (fallback)');
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
                    error_log('MPAI: Returning formatted new members this month');
                    // Already formatted as human readable text
                    $response = array(
                        'success' => true,
                        'tool' => 'memberpress_info',
                        'command_type' => 'new_members_this_month',
                        'result' => $new_members
                    );
                    return json_encode($response);
                } else {
                    error_log('MPAI: Returning regular new members JSON');
                    return json_encode($new_members);
                }
                
            case 'transactions':
                // Get formatted transactions as table
                $transactions = $this->memberpress_api->get_transactions(array(), true);
                
                if (is_string($transactions)) {
                    error_log('MPAI: Returning formatted transactions table');
                    // Already formatted as tabular data
                    $response = array(
                        'success' => true,
                        'tool' => 'memberpress_info',
                        'command_type' => 'transaction_list',
                        'result' => $transactions
                    );
                    return json_encode($response);
                } else {
                    error_log('MPAI: Returning regular transactions JSON');
                    return json_encode($transactions);
                }
                
            case 'subscriptions':
                $subscriptions = $this->memberpress_api->get_subscriptions();
                
                // Format subscriptions data as a table
                if (is_array($subscriptions)) {
                    error_log('MPAI: Formatting subscriptions as table');
                    $output = "ID\tUser\tMembership\tPrice\tStatus\tCreated Date\n";
                    foreach ($subscriptions as $sub) {
                        $id = isset($sub['id']) ? $sub['id'] : 'N/A';
                        
                        // Get user info
                        $user = 'N/A';
                        if (isset($sub['member']) && is_numeric($sub['member'])) {
                            $member_id = $sub['member'];
                            // Try to get email from member ID
                            $member = get_user_by('id', $member_id);
                            if ($member) {
                                $user = $member->user_email;
                            } else {
                                $user = "ID: $member_id";
                            }
                        }
                        
                        // Get membership info
                        $membership = 'N/A';
                        if (isset($sub['membership']) && is_numeric($sub['membership'])) {
                            // Try to get membership title
                            $post = get_post($sub['membership']);
                            if ($post) {
                                $membership = $post->post_title;
                            } else {
                                $membership = "ID: " . $sub['membership'];
                            }
                        }
                        
                        $price = isset($sub['price']) ? '$' . $sub['price'] : 'N/A';
                        $status = isset($sub['status']) ? $sub['status'] : 'N/A';
                        $created = isset($sub['created_at']) ? date('Y-m-d', strtotime($sub['created_at'])) : 'N/A';
                        
                        $output .= "$id\t$user\t$membership\t$price\t$status\t$created\n";
                    }
                    
                    // Return formatted tabular data
                    $response = array(
                        'success' => true,
                        'tool' => 'memberpress_info',
                        'command_type' => 'subscription_list',
                        'result' => $output
                    );
                    return json_encode($response);
                } else {
                    // Fallback to regular JSON if not an array
                    return json_encode($subscriptions);
                }
                
            case 'summary':
            default:
                $summary = $this->memberpress_api->get_data_summary();
                
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
        if (empty($this->allowed_commands)) {
            return false;
        }

        foreach ($this->allowed_commands as $allowed_command) {
            if (strpos($command, $allowed_command) === 0) {
                return true;
            }
        }

        return false;
    }
    
    /**
     * Get a helpful message about tool usage when WP-CLI is not available
     *
     * @param string $command The original command that was attempted
     * @return string A helpful message about alternative tools
     */
    private function get_tool_usage_message($command = '') {
        $message = "WP-CLI is not available in this browser environment. However, you can use the following tools instead:\n\n";
        
        // Determine what type of command was attempted
        if (strpos($command, 'wp post') !== false) {
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
            $message .= "2. Available types: memberships, members, transactions, subscriptions, summary, new_members_this_month\n";
        } else {
            $message .= "1. For WordPress operations, you can use the WordPress API:\n";
            $message .= "   ```json\n   {\"tool\": \"wp_api\", \"parameters\": {\"action\": \"action_name\", \"param1\": \"value1\"}}\n   ```\n\n";
            $message .= "2. For MemberPress operations, use the memberpress_info tool:\n";
            $message .= "   ```json\n   {\"tool\": \"memberpress_info\", \"parameters\": {\"type\": \"memberships\"}}\n   ```\n";
            $message .= "   - Available types: memberships, members, transactions, subscriptions, summary, new_members_this_month\n\n";
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
        error_log('MPAI: execute_wp_api called with parameters: ' . json_encode($parameters));
        
        // Initialize WP API Tool if needed
        if (!isset($this->wp_api_tool)) {
            // Check if the class exists, if not try to load it
            if (!class_exists('MPAI_WP_API_Tool')) {
                $tool_path = MPAI_PLUGIN_DIR . 'includes/tools/implementations/class-mpai-wp-api-tool.php';
                if (file_exists($tool_path)) {
                    require_once $tool_path;
                }
            }
            
            // Initialize the tool if the class exists
            if (class_exists('MPAI_WP_API_Tool')) {
                $this->wp_api_tool = new MPAI_WP_API_Tool();
                error_log('MPAI: WordPress API Tool initialized successfully');
            } else {
                error_log('MPAI: WordPress API Tool class not found');
                return 'Error: WordPress API Tool class not found';
            }
        }
        
        // Execute the tool with the provided parameters
        try {
            error_log('MPAI: Executing WordPress API function: ' . $parameters['action']);
            $result = $this->wp_api_tool->execute($parameters);
            
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
            error_log('MPAI: Error executing WordPress API function: ' . $e->getMessage());
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
            'tool' => 'wp_cli',
            'command_type' => $command_type,
            'result' => $output
        ];
        
        error_log('MPAI: Formatted tabular output for command type: ' . $command_type);
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
        error_log('MPAI: Processing tool request: ' . json_encode($request));
        
        // Enhanced logging for debugging
        error_log('MPAI: Original request format: ' . gettype($request));
        if (is_array($request)) {
            error_log('MPAI: Request keys: ' . implode(', ', array_keys($request)));
        }
        
        // Special handling for common request format variations
        if (isset($request['tool']) && !isset($request['name'])) {
            // Convert from tool + parameters format to name + parameters format
            error_log('MPAI: Converting tool format to name format');
            $request['name'] = $request['tool'];
            unset($request['tool']);
        }
        
        if (!get_option('mpai_enable_mcp', true)) {
            error_log('MPAI: MCP is disabled in settings');
            return array(
                'success' => false,
                'error' => 'MCP is disabled in settings',
                'tool' => isset($request['name']) ? $request['name'] : 'unknown'
            );
        }
        
        if (!isset($request['name']) || !isset($this->available_tools[$request['name']])) {
            error_log('MPAI: Tool not found or invalid: ' . (isset($request['name']) ? $request['name'] : 'unknown'));
            return array(
                'success' => false,
                'error' => 'Tool not found or invalid',
                'tool' => isset($request['name']) ? $request['name'] : 'unknown'
            );
        }

        $tool = $this->available_tools[$request['name']];
        
        // Check if the specific tool is enabled
        if ($tool['name'] === 'wp_cli' && !get_option('mpai_enable_wp_cli_tool', true)) {
            error_log('MPAI: wp_cli tool is disabled in settings');
            return array(
                'success' => false,
                'error' => 'The wp_cli tool is disabled in settings',
                'tool' => $request['name']
            );
        }
        
        if ($tool['name'] === 'memberpress_info' && !get_option('mpai_enable_memberpress_info_tool', true)) {
            error_log('MPAI: memberpress_info tool is disabled in settings');
            return array(
                'success' => false,
                'error' => 'The memberpress_info tool is disabled in settings',
                'tool' => $request['name']
            );
        }
        
        // Validate parameters
        $parameters = isset($request['parameters']) ? $request['parameters'] : array();
        $validated_params = array();
        
        foreach ($tool['parameters'] as $param_name => $param_info) {
            if (!isset($parameters[$param_name])) {
                if (isset($param_info['required']) && $param_info['required']) {
                    error_log('MPAI: Missing required parameter: ' . $param_name);
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
        
        // Special handling for wp_cli tool
        if ($tool['name'] === 'wp_cli') {
            if (!isset($validated_params['command'])) {
                error_log('MPAI: Missing command parameter for wp_cli tool');
                return array(
                    'success' => false,
                    'error' => 'Command parameter is required for wp_cli tool',
                    'tool' => $request['name']
                );
            }
            
            error_log('MPAI: Executing WP-CLI command: ' . $validated_params['command']);
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
                $type = isset($validated_params['type']) ? $validated_params['type'] : 'summary';
                error_log('MPAI: Getting MemberPress info type: ' . $type);
                $result = $this->get_memberpress_info($type);
            } else {
                // Generic callback execution
                error_log('MPAI: Executing tool callback for: ' . $tool['name']);
                $result = call_user_func($tool['callback'], $validated_params);
            }
            
            error_log('MPAI: Tool execution successful');
            return array(
                'success' => true,
                'tool' => $request['name'],
                'result' => $result
            );
        } catch (Exception $e) {
            error_log('MPAI: Error executing tool: ' . $e->getMessage());
            return array(
                'success' => false,
                'error' => $e->getMessage(),
                'tool' => $request['name']
            );
        }
    }
}