<?php
/**
 * Test script for creating a post via Chat interface
 */

// Load WordPress
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';

// Load required classes
require_once __DIR__ . '/includes/class-mpai-openai.php';
require_once __DIR__ . '/includes/class-mpai-api-router.php';
require_once __DIR__ . '/includes/class-mpai-context-manager.php';
require_once __DIR__ . '/includes/class-mpai-memberpress-api.php';
require_once __DIR__ . '/includes/class-mpai-chat.php';

// Set up a fake admin user context if needed
if (!function_exists('wp_get_current_user')) {
    function wp_get_current_user() {
        return new stdClass();
    }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id() {
        return 1; // Assume admin user
    }
}

// Create a chat instance
$chat = new MPAI_Chat();

// Process a message to create a post
$message = 'Create a new blog post titled "Test Post" with the content "Test content".';
$response = $chat->process_message($message);

// Output the response
echo "Response status: " . ($response['success'] ? 'Success' : 'Failed') . "\n\n";
echo "Response message:\n" . $response['message'] . "\n\n";

// If there's a raw response, show it too
if (isset($response['raw_response'])) {
    echo "Raw response:\n" . $response['raw_response'] . "\n\n";
}

// If there's an API used, show it
if (isset($response['api_used'])) {
    echo "API used: " . $response['api_used'] . "\n";
}