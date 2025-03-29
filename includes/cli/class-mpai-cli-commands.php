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

if (!defined('WP_CLI') || !WP_CLI) {
    return;
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
        
        WP_CLI::log('Generating insights...');
        
        try {
            // Get MemberPress API data
            $memberpress_api = new MPAI_MemberPress_API();
            $data_summary = $memberpress_api->get_data_summary();
            
            // Generate insights using OpenAI
            $openai = new MPAI_OpenAI();
            $insights = $openai->generate_memberpress_completion($prompt, $data_summary);
            
            if (is_wp_error($insights)) {
                WP_CLI::error($insights->get_error_message());
                return;
            }
            
            if ($format === 'json') {
                WP_CLI::log(json_encode(array('insights' => $insights)));
            } else {
                WP_CLI::log("\n" . $insights);
            }
            
            WP_CLI::success('Insights generated successfully.');
        } catch (Exception $e) {
            WP_CLI::error('Error generating insights: ' . $e->getMessage());
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

// Register WP-CLI commands
WP_CLI::add_command('mpai', 'MPAI_CLI_Commands');