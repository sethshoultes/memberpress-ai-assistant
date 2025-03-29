<?php
/**
 * Test file for the Agent System
 *
 * This file is used to test that the agent system components can be loaded properly.
 * Run it from the WordPress CLI with: wp eval-file includes/test-agent-system.php
 */

// Make sure we're in a WordPress environment
if (!defined('ABSPATH')) {
    die('This file must be run from WordPress');
}

// Define paths to required files
$base_dir = plugin_dir_path(dirname(__FILE__));
$agent_dir = $base_dir . 'includes/agents/';
$tools_dir = $base_dir . 'includes/tools/';

// Files to load
$files = [
    'interface' => $agent_dir . 'interfaces/interface-mpai-agent.php',
    'base_agent' => $agent_dir . 'class-mpai-base-agent.php',
    'memberpress_agent' => $agent_dir . 'specialized/class-mpai-memberpress-agent.php',
    'agent_orchestrator' => $agent_dir . 'class-mpai-agent-orchestrator.php',
    'base_tool' => $tools_dir . 'class-mpai-base-tool.php',
    'tool_registry' => $tools_dir . 'class-mpai-tool-registry.php',
    'wpcli_tool' => $tools_dir . 'implementations/class-mpai-wpcli-tool.php',
];

// Check if all files exist
$missing_files = [];
foreach ($files as $name => $path) {
    if (!file_exists($path)) {
        $missing_files[$name] = $path;
    }
}

if (!empty($missing_files)) {
    echo "Missing files:\n";
    foreach ($missing_files as $name => $path) {
        echo "- {$name}: {$path}\n";
    }
    die("Please make sure all required files exist before running this test.\n");
}

// Load the files
require_once $files['interface'];
require_once $files['base_tool'];
require_once $files['wpcli_tool'];
require_once $files['tool_registry'];
require_once $files['base_agent'];
require_once $files['memberpress_agent'];
require_once $files['agent_orchestrator'];

// Check if classes are loaded
$required_classes = [
    'MPAI_Agent',
    'MPAI_Base_Tool',
    'MPAI_WP_CLI_Tool',
    'MPAI_Tool_Registry',
    'MPAI_Base_Agent',
    'MPAI_MemberPress_Agent',
    'MPAI_Agent_Orchestrator',
];

$missing_classes = [];
foreach ($required_classes as $class) {
    if (!class_exists($class)) {
        $missing_classes[] = $class;
    }
}

if (!empty($missing_classes)) {
    echo "Missing classes:\n";
    foreach ($missing_classes as $class) {
        echo "- {$class}\n";
    }
    die("Please make sure all required classes are properly defined.\n");
}

// Test creating instances
try {
    echo "Testing class instantiation...\n";
    
    // Create tool registry
    $tool_registry = new MPAI_Tool_Registry();
    echo "✓ MPAI_Tool_Registry successfully instantiated\n";
    
    // Create MemberPress agent
    $memberpress_agent = new MPAI_MemberPress_Agent($tool_registry);
    echo "✓ MPAI_MemberPress_Agent successfully instantiated\n";
    
    // Create orchestrator
    $orchestrator = new MPAI_Agent_Orchestrator();
    echo "✓ MPAI_Agent_Orchestrator successfully instantiated\n";
    
    // Check agent capabilities
    $capabilities = $memberpress_agent->get_capabilities();
    echo "MemberPress Agent capabilities: " . implode(', ', array_keys($capabilities)) . "\n";
    
    // Check available agents
    $available_agents = $orchestrator->get_available_agents();
    if (empty($available_agents)) {
        echo "Warning: No agents registered with the orchestrator\n";
    } else {
        echo "Available agents: " . implode(', ', array_keys($available_agents)) . "\n";
    }
    
    // Test intent analysis with sample queries
    $sample_queries = [
        "List all memberships",
        "Show me recent transactions",
        "Create a new coupon for 20% off",
        "How many active subscribers do I have?",
    ];
    
    echo "\nTesting request processing with sample queries...\n";
    foreach ($sample_queries as $query) {
        echo "Query: \"{$query}\"\n";
        try {
            $result = $orchestrator->process_request($query);
            if (isset($result['success']) && $result['success']) {
                echo "✓ Processed by {$result['agent']} agent\n";
                echo "✓ Response: " . (strlen($result['message']) > 100 ? substr($result['message'], 0, 97) . '...' : $result['message']) . "\n";
            } else {
                echo "✗ Processing failed: " . (isset($result['message']) ? $result['message'] : 'Unknown error') . "\n";
            }
        } catch (Exception $e) {
            echo "✗ Error: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }
    
    echo "All tests completed.\n";
    
} catch (Exception $e) {
    echo "Error during testing: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}