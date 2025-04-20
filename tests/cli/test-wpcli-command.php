<?php
/**
 * Test command for the WP-CLI tool
 * 
 * This file contains a WP-CLI command to test the WP-CLI tool implementation.
 * 
 * @package MemberPress AI Assistant
 */

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

// Register the command if WP-CLI is available
if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('mpai test-wpcli', 'MPAI_Test_WPCLI_Command');
}

/**
 * Test the WP-CLI tool implementation
 */
class MPAI_Test_WPCLI_Command {
    /**
     * Test the WP-CLI tool implementation
     * 
     * ## OPTIONS
     * 
     * [--tool-id=<tool-id>]
     * : The tool ID to test. Default: wpcli
     * ---
     * default: wpcli
     * options:
     *   - wpcli
     * ---
     * 
     * [--command=<command>]
     * : The command to execute. Default: wp core version
     * ---
     * default: wp core version
     * ---
     * 
     * ## EXAMPLES
     * 
     *     wp mpai test-wpcli
     *     wp mpai test-wpcli --tool-id=wpcli
     *     wp mpai test-wpcli --command="wp plugin list"
     * 
     * @param array $args Command arguments
     * @param array $assoc_args Command associated arguments
     */
    public function __invoke($args, $assoc_args) {
        // Parse arguments
        $tool_id = isset($assoc_args['tool-id']) ? $assoc_args['tool-id'] : 'wpcli';
        $command = isset($assoc_args['command']) ? $assoc_args['command'] : 'wp core version';
        
        WP_CLI::log('Testing WP-CLI tool implementation');
        WP_CLI::log('Tool ID: ' . $tool_id);
        WP_CLI::log('Command: ' . $command);
        
        // Load required files
        $this->load_required_files();
        
        // Initialize the tool registry
        $tool_registry = new MPAI_Tool_Registry();
        
        // Get the tool
        $tool = $tool_registry->get_tool($tool_id);
        
        if (!$tool) {
            WP_CLI::error('Tool not found: ' . $tool_id);
            return;
        }
        
        WP_CLI::log('Tool found: ' . get_class($tool));
        
        // Execute the command
        try {
            WP_CLI::log('Executing command...');
            $result = $tool->execute([
                'command' => $command
            ]);
            
            WP_CLI::log('Command executed successfully');
            WP_CLI::log('Result:');
            WP_CLI::log($result);
            
            WP_CLI::success('Test completed successfully');
        } catch (Exception $e) {
            WP_CLI::error('Error executing command: ' . $e->getMessage());
        }
    }
    
    /**
     * Load required files
     */
    private function load_required_files() {
        $base_path = dirname(dirname(__FILE__));
        
        // Load tool registry
        if (!class_exists('MPAI_Tool_Registry')) {
            $tool_registry_path = $base_path . '/tools/class-mpai-tool-registry.php';
            if (file_exists($tool_registry_path)) {
                require_once $tool_registry_path;
            } else {
                WP_CLI::error('Tool registry file not found: ' . $tool_registry_path);
                return;
            }
        }
        
        // Load base tool
        if (!class_exists('MPAI_Base_Tool')) {
            $base_tool_path = $base_path . '/tools/class-mpai-base-tool.php';
            if (file_exists($base_tool_path)) {
                require_once $base_tool_path;
            } else {
                WP_CLI::error('Base tool file not found: ' . $base_tool_path);
                return;
            }
        }
        
        // Load WP-CLI tool
        if (!class_exists('MPAI_WP_CLI_Tool')) {
            $wpcli_tool_path = $base_path . '/tools/implementations/class-mpai-wpcli-tool.php';
            if (file_exists($wpcli_tool_path)) {
                require_once $wpcli_tool_path;
            } else {
                WP_CLI::error('WP-CLI tool file not found: ' . $wpcli_tool_path);
                return;
            }
        }
        
        // Load WP-CLI executor
        if (!class_exists('MPAI_WP_CLI_Executor')) {
            $wpcli_executor_path = $base_path . '/commands/class-mpai-wp-cli-executor.php';
            if (file_exists($wpcli_executor_path)) {
                require_once $wpcli_executor_path;
            } else {
                WP_CLI::error('WP-CLI executor file not found: ' . $wpcli_executor_path);
                return;
            }
        }
        
        // Load command adapter
        if (!class_exists('MPAI_Command_Adapter')) {
            $command_adapter_path = $base_path . '/commands/class-mpai-command-adapter.php';
            if (file_exists($command_adapter_path)) {
                require_once $command_adapter_path;
            } else {
                WP_CLI::error('Command adapter file not found: ' . $command_adapter_path);
                return;
            }
        }
        
        // Load command handler
        if (!class_exists('MPAI_Command_Handler')) {
            $command_handler_path = $base_path . '/commands/class-mpai-command-handler.php';
            if (file_exists($command_handler_path)) {
                require_once $command_handler_path;
            } else {
                WP_CLI::error('Command handler file not found: ' . $command_handler_path);
                return;
            }
        }
    }
}