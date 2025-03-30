<?php
/**
 * Test script for creating a post using wp_api tool
 */

// Load WordPress
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';

// Load the Context Manager class
require_once __DIR__ . '/includes/class-mpai-context-manager.php';

// Create a Context Manager instance
$context_manager = new MPAI_Context_Manager();

// Create a tool request
$tool_request = array(
    'name' => 'wp_api',
    'parameters' => array(
        'action' => 'create_post',
        'title' => 'Test Post Created Successfully',
        'content' => 'This is a test post created using the wp_api tool.',
        'status' => 'draft'
    )
);

try {
    // Process the tool request
    $result = $context_manager->process_tool_request($tool_request);
    
    echo "Result: " . print_r($result, true);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}