<?php
/**
 * Theme and Block Validation Test Script
 * 
 * This script tests the extended validation functionality for WordPress themes, blocks, and patterns.
 * It checks if the validation agent correctly identifies and validates theme, block, and pattern commands.
 */

// Load WordPress
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

// Check if MemberPress AI Assistant plugin is active
if (!class_exists('MemberPress_AI_Assistant')) {
    die('MemberPress AI Assistant plugin is not active.');
}

// Load required files for testing
$plugin_dir = plugin_dir_path(__FILE__);
require_once $plugin_dir . 'includes/class-mpai-openai.php';
require_once $plugin_dir . 'includes/class-mpai-anthropic.php';
require_once $plugin_dir . 'includes/class-mpai-api-router.php';
require_once $plugin_dir . 'includes/class-mpai-memberpress-api.php';
require_once $plugin_dir . 'includes/class-mpai-chat.php';
require_once $plugin_dir . 'includes/class-mpai-context-manager.php';
require_once $plugin_dir . 'includes/agents/interfaces/interface-mpai-agent.php';
require_once $plugin_dir . 'includes/agents/class-mpai-base-agent.php';
require_once $plugin_dir . 'includes/agents/specialized/class-mpai-command-validation-agent.php';

echo "Testing Theme, Block, and Pattern Validation Functionality\n";
echo "========================================================\n\n";

// Create validation agent instance
$validation_agent = new MPAI_Command_Validation_Agent();

// Test theme validation
echo "TESTING THEME VALIDATION:\n";
echo "-------------------------\n";

$theme_tests = [
    // Format: [command_type, command_data, expected_success, description]
    ['wp_cli', ['command' => 'wp theme activate twentytwentythree'], true, 'Valid theme with exact match'],
    ['wp_cli', ['command' => 'wp theme activate twentytwenty'], true, 'Valid theme with exact match'],
    ['wp_cli', ['command' => 'wp theme activate twenty-twenty-three'], false, 'Invalid theme with incorrect format'],
    ['wp_cli', ['command' => 'wp theme activate Twenty Twenty-Three'], true, 'Valid theme with different casing'],
    ['wp_cli', ['command' => 'wp theme list'], true, 'Theme list command should bypass validation'],
    ['wp_cli', ['command' => 'wp theme activate nonexistent-theme'], false, 'Invalid theme that doesn\'t exist'],
];

foreach ($theme_tests as $index => $test) {
    list($command_type, $command_data, $expected_success, $description) = $test;
    
    $intent_data = [
        'command_type' => $command_type,
        'command_data' => $command_data,
        'original_message' => 'Test validation request',
    ];
    
    $result = $validation_agent->process_request($intent_data);
    
    $actual_success = isset($result['success']) ? $result['success'] : false;
    $status = ($actual_success === $expected_success) ? 'PASS' : 'FAIL';
    
    echo "Test #" . ($index + 1) . " [$status]: $description\n";
    echo "  Command: " . $command_data['command'] . "\n";
    echo "  Result: " . (isset($result['message']) ? $result['message'] : 'No message') . "\n";
    if (isset($result['validated_command']) && isset($result['validated_command']['command']) && 
        $result['validated_command']['command'] != $command_data['command']) {
        echo "  Corrected to: " . $result['validated_command']['command'] . "\n";
    }
    echo "\n";
}

// Test block validation
echo "TESTING BLOCK VALIDATION:\n";
echo "------------------------\n";

$block_tests = [
    // Format: [command_type, command_data, expected_success, description]
    ['wp_cli', ['command' => 'wp block unregister core/paragraph'], true, 'Valid block with exact match'],
    ['wp_cli', ['command' => 'wp block unregister paragraph'], true, 'Valid block without namespace'],
    ['wp_cli', ['command' => 'wp block unregister core/nonexistent'], false, 'Invalid block that doesn\'t exist'],
    ['wp_cli', ['command' => 'wp block list'], true, 'Block list command should bypass validation'],
];

foreach ($block_tests as $index => $test) {
    list($command_type, $command_data, $expected_success, $description) = $test;
    
    $intent_data = [
        'command_type' => $command_type,
        'command_data' => $command_data,
        'original_message' => 'Test validation request',
    ];
    
    $result = $validation_agent->process_request($intent_data);
    
    $actual_success = isset($result['success']) ? $result['success'] : false;
    $status = ($actual_success === $expected_success) ? 'PASS' : 'FAIL';
    
    echo "Test #" . ($index + 1) . " [$status]: $description\n";
    echo "  Command: " . $command_data['command'] . "\n";
    echo "  Result: " . (isset($result['message']) ? $result['message'] : 'No message') . "\n";
    if (isset($result['validated_command']) && isset($result['validated_command']['command']) && 
        $result['validated_command']['command'] != $command_data['command']) {
        echo "  Corrected to: " . $result['validated_command']['command'] . "\n";
    }
    echo "\n";
}

echo "Tests completed! Please verify the results above.\n";