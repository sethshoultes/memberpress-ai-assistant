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
     * ## EXAMPLES
     *
     * wp mpai chat "How many active memberships do we have?"
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
        
        WP_CLI::log('Processing message...');
        
        try {
            // Initialize chat
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
            // Initialize context manager
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
        } catch (Exception $e) {
            WP_CLI::error('Error executing command: ' . $e->getMessage());
        }
    }
}

// Register WP-CLI commands
WP_CLI::add_command('mpai', 'MPAI_CLI_Commands');