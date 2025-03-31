<?php
/**
 * Test script for command validation agent
 */

// Load WordPress
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';

// Load the agent classes
require_once __DIR__ . '/includes/agents/interfaces/interface-mpai-agent.php';
require_once __DIR__ . '/includes/agents/class-mpai-base-agent.php';
require_once __DIR__ . '/includes/agents/specialized/class-mpai-command-validation-agent.php';

// Create the validation agent
$validation_agent = new MPAI_Command_Validation_Agent();

echo "Testing command validation agent...\n\n";

// Test cases:
// 1. Valid plugin path in wp_api format
// 2. Incorrect plugin path in wp_api format
// 3. Plugin slug without path in wp_api format
// 4. Valid plugin path in wp_cli format
// 5. Plugin slug in wp_cli format
// 6. Tool call format

$test_cases = [
    [
        'name' => 'Valid plugin path (API)',
        'command_type' => 'wp_api',
        'command_data' => [
            'action' => 'activate_plugin',
            'plugin' => 'memberpress-coachkit/main.php'
        ]
    ],
    [
        'name' => 'Incorrect plugin path (API)',
        'command_type' => 'wp_api',
        'command_data' => [
            'action' => 'activate_plugin',
            'plugin' => 'memberpress-coachkit/memberpress-coachkit.php'
        ]
    ],
    [
        'name' => 'Plugin slug without path (API)',
        'command_type' => 'wp_api',
        'command_data' => [
            'action' => 'activate_plugin',
            'plugin' => 'memberpress-coachkit'
        ]
    ],
    [
        'name' => 'Valid plugin path (CLI)',
        'command_type' => 'wp_cli',
        'command_data' => [
            'command' => 'wp plugin activate memberpress-coachkit/main.php'
        ]
    ],
    [
        'name' => 'Plugin slug only (CLI)',
        'command_type' => 'wp_cli',
        'command_data' => [
            'command' => 'wp plugin activate memberpress-coachkit'
        ]
    ],
    [
        'name' => 'Tool call format',
        'command_type' => 'tool_call',
        'command_data' => [
            'name' => 'wp_api',
            'parameters' => [
                'action' => 'activate_plugin',
                'plugin' => 'memberpress-coachkit'
            ]
        ]
    ]
];

// Process each test case
foreach ($test_cases as $test_case) {
    echo "Testing: " . $test_case['name'] . "\n";
    echo "Input: " . json_encode($test_case['command_data']) . "\n";
    
    // Prepare validation request
    $intent_data = [
        'command_type' => $test_case['command_type'],
        'command_data' => $test_case['command_data'],
        'original_message' => 'Test validation request',
    ];
    
    // Process the request
    $result = $validation_agent->process_request($intent_data);
    
    // Display result
    echo "Success: " . ($result['success'] ? 'true' : 'false') . "\n";
    echo "Message: " . $result['message'] . "\n";
    if ($result['success']) {
        echo "Validated Command: " . json_encode($result['validated_command']) . "\n";
    }
    
    echo "\n---------------------------\n\n";
}

echo "Validation testing complete!\n";