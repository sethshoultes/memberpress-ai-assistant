<?php
/**
 * CLI Commands Class
 *
 * Handles WP-CLI commands for the plugin
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Skip if WP_CLI isn't available
if (!defined('WP_CLI') || !WP_CLI) {
    return;
}

// Initialize CLI logging with minimal error_log overhead
function mpai_cli_log($message, $level = 'info') {
    // Only log errors and warnings to error_log to reduce overhead
    if ($level === 'error' || $level === 'warning') {
        error_log('MPAI CLI: ' . $message);
    }
    
    // Always output to console
    if ($level === 'error') {
        WP_CLI::error($message);
    } elseif ($level === 'warning') {
        WP_CLI::warning($message);
    } elseif ($level === 'success') {
        WP_CLI::success($message);
    } else {
        WP_CLI::log($message);
    }
}

// Minimal initialization log
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('MPAI CLI: Initialized MemberPress AI Assistant CLI commands');
}

/**
 * WP-CLI commands for MemberPress AI Assistant
 */
class MPAI_CLI_Commands extends WP_CLI_Command {
    /**
     * Generate insights from MemberPress data using AI.
     *
     * ## OPTIONS
     *
     * [--prompt=<prompt>]
     * : The prompt to use for generating insights. Default: "Analyze MemberPress data and provide key insights"
     *
     * [--format=<format>]
     * : The output format (json or text). Default: text
     *
     * ## EXAMPLES
     *
     * wp mpai insights
     * wp mpai insights --prompt="What are the top selling memberships?"
     * wp mpai insights --format=json
     *
     * @param array $args Command arguments.
     * @param array $assoc_args Command associated arguments.
     */
    public function insights($args, $assoc_args) {
        $prompt = WP_CLI\Utils\get_flag_value($assoc_args, 'prompt', 'Analyze MemberPress data and provide key insights');
        $format = WP_CLI\Utils\get_flag_value($assoc_args, 'format', 'text');
        
        mpai_cli_log('Generating insights...');
        
        try {
            // Get MemberPress API data
            if (!class_exists('MPAI_MemberPress_API')) {
                // Try to load the class
                $api_file = dirname(dirname(__FILE__)) . '/class-mpai-memberpress-api.php';
                if (file_exists($api_file)) {
                    require_once $api_file;
                }
                
                if (!class_exists('MPAI_MemberPress_API')) {
                    mpai_cli_log('Failed to load MPAI_MemberPress_API class', 'error');
                    return;
                }
            }
            
            $memberpress_api = new MPAI_MemberPress_API();
            $data_summary = $memberpress_api->get_data_summary();
            
            // Generate insights using OpenAI
            if (!class_exists('MPAI_OpenAI')) {
                // Try to load the class
                $openai_file = dirname(dirname(__FILE__)) . '/class-mpai-openai.php';
                if (file_exists($openai_file)) {
                    require_once $openai_file;
                }
                
                if (!class_exists('MPAI_OpenAI')) {
                    mpai_cli_log('Failed to load MPAI_OpenAI class', 'error');
                    return;
                }
            }
            
            $openai = new MPAI_OpenAI();
            mpai_cli_log('Processing request...');
            $insights = $openai->generate_memberpress_completion($prompt, $data_summary);
            
            if (is_wp_error($insights)) {
                mpai_cli_log($insights->get_error_message(), 'error');
                return;
            }
            
            if ($format === 'json') {
                WP_CLI::log(json_encode(array('insights' => $insights)));
            } else {
                WP_CLI::log("\n" . $insights);
            }
            
            mpai_cli_log('Insights generated successfully.', 'success');
        } catch (Exception $e) {
            mpai_cli_log('Error generating insights: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * Get recommendations for WP-CLI commands.
     *
     * ## OPTIONS
     *
     * <prompt>
     * : The task you want to accomplish.
     *
     * ## EXAMPLES
     *
     * wp mpai recommend "How do I list all transactions from last month?"
     *
     * @param array $args Command arguments.
     * @param array $assoc_args Command associated arguments.
     */
    public function recommend($args, $assoc_args) {
        if (empty($args[0])) {
            WP_CLI::error('Prompt is required.');
            return;
        }
        
        $prompt = $args[0];
        
        WP_CLI::log('Generating recommendations...');
        
        try {
            // Initialize context manager
            $context_manager = new MPAI_Context_Manager();
            
            // Get recommendations
            $recommendations = $context_manager->get_command_recommendations($prompt);
            
            if (is_wp_error($recommendations)) {
                WP_CLI::error($recommendations->get_error_message());
                return;
            }
            
            WP_CLI::log("\n" . $recommendations);
            
            WP_CLI::success('Recommendations generated successfully.');
        } catch (Exception $e) {
            WP_CLI::error('Error generating recommendations: ' . $e->getMessage());
        }
    }

    /**
     * Initialize a new conversation with the AI assistant.
     *
     * ## OPTIONS
     *
     * <message>
     * : The initial message to send to the AI assistant.
     *
     * [--agent=<agent>]
     * : Optional agent to use (memberpress, content, system, etc.)
     *
     * ## EXAMPLES
     *
     * wp mpai chat "How many active memberships do we have?"
     * wp mpai chat "List all active subscriptions" --agent=memberpress
     *
     * @param array $args Command arguments.
     * @param array $assoc_args Command associated arguments.
     */
    public function chat($args, $assoc_args) {
        if (empty($args[0])) {
            WP_CLI::error('Message is required.');
            return;
        }
        
        $message = $args[0];
        $agent = WP_CLI\Utils\get_flag_value($assoc_args, 'agent', '');
        
        WP_CLI::log('Processing message...');
        
        try {
            // Check if using new agent system or legacy chat
            if (class_exists('MPAI_Agent_Orchestrator') || $this->load_agent_system()) {
                // Use agent system if available
                $orchestrator = new MPAI_Agent_Orchestrator();
                
                // Process the message
                $result = $orchestrator->process_request($message);
                
                if (isset($result['success']) && $result['success']) {
                    WP_CLI::log("\nAgent: " . ucfirst($result['agent']));
                    WP_CLI::log("Response: " . $result['message']);
                } else {
                    WP_CLI::error('Error: ' . (isset($result['message']) ? $result['message'] : 'Unknown error'));
                }
            } else {
                // Fallback to legacy chat
                $chat = new MPAI_Chat();
                
                // Reset conversation to start fresh
                $chat->reset_conversation();
                
                // Process message
                $response = $chat->process_message($message);
                
                if (isset($response['success']) && $response['success']) {
                    WP_CLI::log("\nAI Assistant: " . $response['message']);
                } else {
                    WP_CLI::error('Error: ' . $response['message']);
                }
            }
        } catch (Exception $e) {
            WP_CLI::error('Error processing message: ' . $e->getMessage());
        }
    }

    /**
     * Run a command and analyze the output with AI.
     *
     * ## OPTIONS
     *
     * <command>
     * : The command to run.
     *
     * [--context=<context>]
     * : Optional context to help AI understand the command.
     *
     * ## EXAMPLES
     *
     * wp mpai run "wp user list --role=subscriber"
     * wp mpai run "wp post list --post_status=publish" --context="I want to see how many published posts we have"
     *
     * @param array $args Command arguments.
     * @param array $assoc_args Command associated arguments.
     */
    public function run($args, $assoc_args) {
        if (empty($args[0])) {
            WP_CLI::error('Command is required.');
            return;
        }
        
        $command = $args[0];
        $context = WP_CLI\Utils\get_flag_value($assoc_args, 'context', '');
        
        WP_CLI::log('Running command: ' . $command);
        
        try {
            // Check if using new agent system or legacy context manager
            if (class_exists('MPAI_Agent_Orchestrator') || $this->load_agent_system()) {
                // Use agent system if available
                $orchestrator = new MPAI_Agent_Orchestrator();
                
                // Create a natural language request for the command
                $request = "Run this command and explain the results: {$command}";
                if (!empty($context)) {
                    $request .= ". Context: {$context}";
                }
                
                // Process the request
                $result = $orchestrator->process_request($request);
                
                if (isset($result['success']) && $result['success']) {
                    WP_CLI::log("\nCommand Output and Analysis:\n" . $result['message']);
                } else {
                    WP_CLI::error('Error: ' . (isset($result['message']) ? $result['message'] : 'Unknown error'));
                }
            } else {
                // Fallback to legacy context manager
                $context_manager = new MPAI_Context_Manager();
                
                // Execute command
                $result = $context_manager->execute_mcp_command($command, $context);
                
                if (isset($result['success']) && $result['success']) {
                    WP_CLI::log("\nCommand Output:\n" . $result['output']);
                    
                    if (!empty($result['insights'])) {
                        WP_CLI::log("\nAI Insights:\n" . $result['insights']);
                    }
                } else {
                    WP_CLI::error('Error: ' . $result['message']);
                }
            }
        } catch (Exception $e) {
            WP_CLI::error('Error executing command: ' . $e->getMessage());
        }
    }
    
    /**
     * Process a request through the agent system.
     *
     * ## OPTIONS
     *
     * <request>
     * : The natural language request to process
     *
     * [--agent=<agent>]
     * : The specific agent to use (memberpress, content, system, etc.)
     *
     * [--format=<format>]
     * : Output format (json, text)
     * ---
     * default: text
     * options:
     *   - json
     *   - text
     * ---
     *
     * ## EXAMPLES
     *
     * wp mpai process "List all active memberships"
     * wp mpai process "Create a new coupon for 20% off" --agent=memberpress
     * wp mpai process "Get recent transactions" --format=json
     */
    public function process($args, $assoc_args) {
        if (empty($args[0])) {
            WP_CLI::error('Request is required');
            return;
        }

        $request = $args[0];
        $agent = WP_CLI\Utils\get_flag_value($assoc_args, 'agent', null);
        $format = WP_CLI\Utils\get_flag_value($assoc_args, 'format', 'text');

        WP_CLI::log(sprintf('Processing request: "%s"', $request));

        // Check if the agent system is loaded or can be loaded
        if (!class_exists('MPAI_Agent_Orchestrator') && !$this->load_agent_system()) {
            WP_CLI::error('Agent System not available. Make sure all required files exist.');
            return;
        }

        try {
            // Process the request using the agent orchestrator
            $orchestrator = new MPAI_Agent_Orchestrator();
            
            // If specific agent is requested, validate it exists
            if ($agent) {
                $available_agents = $orchestrator->get_available_agents();
                if (!isset($available_agents[$agent])) {
                    WP_CLI::error(sprintf('Agent "%s" not found. Available agents: %s', 
                        $agent, 
                        implode(', ', array_keys($available_agents))
                    ));
                    return;
                }
                
                // Note: We would need to modify the orchestrator to accept a specific agent
                // For now, we'll just note that a specific agent was requested
                WP_CLI::log(sprintf('Note: Requested agent "%s" (currently the orchestrator determines the best agent)', $agent));
            }
            
            // Process the request
            $result = $orchestrator->process_request($request);
            
            // Output the result based on format
            if ($format === 'json') {
                WP_CLI::log(json_encode($result, JSON_PRETTY_PRINT));
            } else {
                if (isset($result['success']) && $result['success']) {
                    WP_CLI::success(sprintf("Request processed successfully using the %s agent.", $result['agent']));
                    WP_CLI::log("\nResult:\n" . $result['message']);
                } else {
                    WP_CLI::error(isset($result['message']) ? $result['message'] : 'Unknown error occurred.');
                }
            }
        } catch (Exception $e) {
            WP_CLI::error($e->getMessage());
        }
    }

    /**
     * Execute a MemberPress CLI command.
     *
     * ## OPTIONS
     *
     * <command>
     * : The MemberPress command to run
     *
     * [--explain]
     * : Generate an explanation of the command output
     *
     * [--format=<format>]
     * : Output format (json, text)
     * ---
     * default: text
     * options:
     *   - json
     *   - text
     * ---
     *
     * ## EXAMPLES
     *
     * wp mpai mepr "membership list"
     * wp mpai mepr "transaction list --limit=5" --explain
     * wp mpai mepr "subscription list --status=active" --format=json
     */
    public function mepr($args, $assoc_args) {
        if (empty($args[0])) {
            WP_CLI::error('Command is required');
            return;
        }
        
        $command = $args[0];
        $explain = WP_CLI\Utils\get_flag_value($assoc_args, 'explain', false);
        $format = WP_CLI\Utils\get_flag_value($assoc_args, 'format', 'text');
        
        // Ensure command starts with wp mepr
        if (strpos($command, 'wp mepr') !== 0) {
            $command = 'wp mepr-' . $command;
        }
        
        WP_CLI::log(sprintf('Running command: %s', $command));
        
        // Check if the agent system is loaded or can be loaded
        if (!class_exists('MPAI_MemberPress_Agent') && !$this->load_agent_system()) {
            WP_CLI::error('Agent System not available. Make sure all required files exist.');
            return;
        }
        
        try {
            // Initialize tool registry and agent
            $tool_registry = new MPAI_Tool_Registry();
            $memberpress_agent = new MPAI_MemberPress_Agent($tool_registry);
            
            // Run the command
            $result = $memberpress_agent->run_mepr_command($command);
            
            // Format the result
            if (is_array($result)) {
                $formatted_result = json_encode($result, JSON_PRETTY_PRINT);
            } else {
                $formatted_result = $result;
            }
            
            // Output the result based on format
            if ($format === 'json') {
                WP_CLI::log($formatted_result);
            } else {
                WP_CLI::success("Command executed successfully");
                WP_CLI::log("\nResult:\n" . $formatted_result);
                
                // Generate explanation if requested
                if ($explain && class_exists('MPAI_OpenAI')) {
                    WP_CLI::log("\nGenerating explanation...");
                    
                    $openai = new MPAI_OpenAI();
                    $prompt = "Explain the following output from a MemberPress CLI command '{$command}':\n\n{$formatted_result}";
                    
                    $explanation = $openai->generate_simple_completion($prompt);
                    
                    if (!is_wp_error($explanation)) {
                        WP_CLI::log("\nExplanation:\n" . $explanation);
                    } else {
                        WP_CLI::warning("Could not generate explanation: " . $explanation->get_error_message());
                    }
                }
            }
        } catch (Exception $e) {
            WP_CLI::error($e->getMessage());
        }
    }
    
    /**
     * Try to load the agent system
     *
     * @return bool Whether the agent system was loaded successfully
     */
    private function load_agent_system() {
        // Try to load the orchestrator
        $orchestrator_path = plugin_dir_path(__FILE__) . '../agents/class-mpai-agent-orchestrator.php';
        $interface_path = plugin_dir_path(__FILE__) . '../agents/interfaces/interface-mpai-agent.php';
        $base_agent_path = plugin_dir_path(__FILE__) . '../agents/class-mpai-base-agent.php';
        $memberpress_agent_path = plugin_dir_path(__FILE__) . '../agents/specialized/class-mpai-memberpress-agent.php';
        
        $tool_registry_path = plugin_dir_path(__FILE__) . '../tools/class-mpai-tool-registry.php';
        $base_tool_path = plugin_dir_path(__FILE__) . '../tools/class-mpai-base-tool.php';
        $wpcli_tool_path = plugin_dir_path(__FILE__) . '../tools/implementations/class-mpai-wpcli-tool.php';
        
        // Check if all required files exist
        if (!file_exists($orchestrator_path) || 
            !file_exists($interface_path) || 
            !file_exists($base_agent_path) || 
            !file_exists($memberpress_agent_path) ||
            !file_exists($tool_registry_path) ||
            !file_exists($base_tool_path) ||
            !file_exists($wpcli_tool_path)) {
            return false;
        }
        
        // Load required files
        require_once $interface_path;
        require_once $base_agent_path;
        require_once $base_tool_path;
        require_once $wpcli_tool_path;
        require_once $tool_registry_path;
        require_once $memberpress_agent_path;
        require_once $orchestrator_path;
        
        return class_exists('MPAI_Agent_Orchestrator');
    }
    
    /**
     * List all available WP-CLI commands for MemberPress AI Assistant.
     *
     * ## EXAMPLES
     *
     * wp mpai help
     *
     * @param array $args Command arguments.
     * @param array $assoc_args Command associated arguments.
     */
    public function help($args, $assoc_args) {
        WP_CLI::log("\nMemberPress AI Assistant WP-CLI Commands:");
        WP_CLI::log("==========================================\n");
        
        // Commands list with descriptions
        $commands = array(
            'insights'  => 'Generate insights from MemberPress data using AI',
            'recommend' => 'Get recommendations for WP-CLI commands',
            'chat'      => 'Initialize a new conversation with the AI assistant',
            'run'       => 'Run a command and analyze the output with AI',
            'process'   => 'Process a request through the agent system',
            'mepr'      => 'Execute a MemberPress CLI command',
            'help'      => 'Show this help message'
        );
        
        // Print each command with its description
        foreach ($commands as $command => $description) {
            WP_CLI::log(sprintf("wp mpai %-12s %s", $command, $description));
        }
        
        WP_CLI::log("\nFor more details on a specific command, run:");
        WP_CLI::log("wp help mpai <command>");
        
        // Check if command registration is working properly
        mpai_cli_log("Checking command registration status...", 'debug');
        
        // Try to get all commands
        $all_commands = array();
        if (method_exists('WP_CLI', 'get_root_command')) {
            $root_command = WP_CLI::get_root_command();
            if (method_exists($root_command, 'get_subcommands')) {
                $all_commands = $root_command->get_subcommands();
            }
        }
        
        $mpai_registered = isset($all_commands['mpai']) ? 'Yes' : 'No';
        mpai_cli_log("MPAI command registration status: " . $mpai_registered, 'debug');
        
        WP_CLI::success("Help information displayed successfully.");
    }
    
    /**
     * Diagnostic command to check the status of the MemberPress AI Assistant system.
     *
     * ## EXAMPLES
     *
     * wp mpai diagnostic
     *
     * @param array $args Command arguments.
     * @param array $assoc_args Command associated arguments.
     */
    public function diagnostic($args, $assoc_args) {
        mpai_cli_log("Running MemberPress AI Assistant Diagnostic", 'info');
        
        // Check MemberPress
        mpai_cli_log("Checking MemberPress installation...");
        $has_memberpress = false;
        
        // Check for MemberPress class definitions
        $classes_to_check = [
            'MeprAppCtrl',
            'MeprOptions',
            'MeprUser',
            'MeprProduct',
            'MeprTransaction',
            'MeprSubscription'
        ];
        
        foreach ($classes_to_check as $class) {
            if (class_exists($class)) {
                mpai_cli_log("Found MemberPress class: " . $class, 'debug');
                $has_memberpress = true;
                break;
            }
        }
        
        if ($has_memberpress) {
            mpai_cli_log("MemberPress is installed and active.", 'success');
        } else {
            mpai_cli_log("MemberPress is not detected. Some functionality may be limited.", 'warning');
        }
        
        // Check MPAI classes
        mpai_cli_log("Checking MPAI core classes...");
        $required_classes = [
            'MPAI_OpenAI' => dirname(dirname(__FILE__)) . '/class-mpai-openai.php',
            'MPAI_Chat' => dirname(dirname(__FILE__)) . '/class-mpai-chat.php',
            'MPAI_MemberPress_API' => dirname(dirname(__FILE__)) . '/class-mpai-memberpress-api.php',
            'MPAI_Context_Manager' => dirname(dirname(__FILE__)) . '/class-mpai-context-manager.php'
        ];
        
        $missing_classes = [];
        
        foreach ($required_classes as $class => $file) {
            if (!class_exists($class)) {
                mpai_cli_log("Class not loaded: " . $class, 'debug');
                
                if (file_exists($file)) {
                    require_once $file;
                    if (class_exists($class)) {
                        mpai_cli_log("Successfully loaded class from file: " . $class, 'debug');
                    } else {
                        mpai_cli_log("Failed to load class after including file: " . $class, 'warning');
                        $missing_classes[] = $class;
                    }
                } else {
                    mpai_cli_log("Class file not found: " . $file, 'warning');
                    $missing_classes[] = $class;
                }
            } else {
                mpai_cli_log("Class already loaded: " . $class, 'debug');
            }
        }
        
        if (empty($missing_classes)) {
            mpai_cli_log("All required MPAI classes are available.", 'success');
        } else {
            mpai_cli_log("Missing required classes: " . implode(', ', $missing_classes), 'error');
        }
        
        // Check plugins directory
        mpai_cli_log("Checking plugin directory...");
        if (defined('MPAI_PLUGIN_DIR')) {
            mpai_cli_log("Plugin directory: " . MPAI_PLUGIN_DIR, 'debug');
            if (file_exists(MPAI_PLUGIN_DIR)) {
                mpai_cli_log("Plugin directory exists.", 'success');
            } else {
                mpai_cli_log("Plugin directory does not exist.", 'error');
            }
        } else {
            mpai_cli_log("MPAI_PLUGIN_DIR constant not defined.", 'error');
        }
        
        // Overall status
        if ($has_memberpress && empty($missing_classes) && defined('MPAI_PLUGIN_DIR') && file_exists(MPAI_PLUGIN_DIR)) {
            mpai_cli_log("MemberPress AI Assistant system appears to be working correctly.", 'success');
        } else {
            mpai_cli_log("MemberPress AI Assistant system has issues that need to be addressed.", 'warning');
        }
    }
}

/**
 * Initialize CLI commands - register early and reliably
 */
function mpai_initialize_cli_commands() {
    // Check if we're able to register commands
    if (!class_exists('WP_CLI')) {
        error_log('MPAI CLI: WP_CLI class not available for command registration');
        return;
    }
    
    if (!class_exists('MPAI_CLI_Commands')) {
        error_log('MPAI CLI: MPAI_CLI_Commands class not available for registration');
        return;
    }
    
    // Register the command
    try {
        WP_CLI::add_command('mpai', 'MPAI_CLI_Commands');
        mpai_cli_log('Successfully registered MemberPress AI CLI commands', 'success');
    } catch (Exception $e) {
        error_log('MPAI CLI: Error registering commands: ' . $e->getMessage());
    }
}

// Try multiple registration approaches to ensure commands are available
if (defined('WP_CLI') && WP_CLI) {
    // Immediate registration
    mpai_initialize_cli_commands();
    
    // Also register on plugins_loaded hook for reliability
    if (function_exists('add_action')) {
        add_action('plugins_loaded', 'mpai_initialize_cli_commands', 20);
    }
    
    // Fallback registration via WP-CLI hook
    if (method_exists('WP_CLI', 'add_hook')) {
        WP_CLI::add_hook('before_wp_load', 'mpai_initialize_cli_commands');
    }
}