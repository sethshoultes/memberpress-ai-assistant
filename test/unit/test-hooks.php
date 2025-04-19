<?php
/**
 * Test hook and filter functionality
 * 
 * This is a demonstration file showing how to use the hooks and filters
 * rather than a proper unit test.
 */

// Plugin initialization hooks
function test_plugin_init_hooks() {
    add_action('MPAI_HOOK_ACTION_before_plugin_init', function() {
        echo "Before plugin initialization\n";
    });
    
    add_action('MPAI_HOOK_ACTION_after_plugin_init', function() {
        echo "After plugin initialization\n";
    });
}
test_plugin_init_hooks();

// System prompt filter
function test_system_prompt_filter() {
    add_filter('MPAI_HOOK_FILTER_system_prompt', function($prompt) {
        echo "Original prompt: " . substr($prompt, 0, 50) . "...\n";
        return $prompt . "\n\nADDITIONAL INSTRUCTIONS: Always be extra helpful.";
    });
}
test_system_prompt_filter();

// Message content filter
function test_message_content_filter() {
    add_filter('MPAI_HOOK_FILTER_message_content', function($message) {
        echo "Filtering message: " . $message . "\n";
        return $message . " (filtered)";
    });
}
test_message_content_filter();

// History retention filter
function test_history_retention_filter() {
    add_filter('MPAI_HOOK_FILTER_history_retention', function($days) {
        echo "Changing retention from {$days} days to 60 days\n";
        return 60;
    });
}
test_history_retention_filter();

// User context filter
function test_user_context_filter() {
    add_filter('MPAI_HOOK_FILTER_user_context', function($context) {
        echo "Adding custom data to user context\n";
        $context['custom_key'] = 'custom_value';
        return $context;
    });
}
test_user_context_filter();

// Allowed commands filter
function test_allowed_commands_filter() {
    add_filter('MPAI_HOOK_FILTER_allowed_commands', function($commands) {
        echo "Adding 'wp theme list' to allowed commands\n";
        $commands[] = 'wp theme list';
        return $commands;
    });
}
test_allowed_commands_filter();

// Response content filter
function test_response_content_filter() {
    add_filter('MPAI_HOOK_FILTER_response_content', function($response, $message) {
        echo "Filtering response for message: " . $message . "\n";
        return $response . "\n\n---\nPowered by MemberPress AI";
    }, 10, 2);
}
test_response_content_filter();

// Before process message hook
function test_before_process_message_hook() {
    add_action('MPAI_HOOK_ACTION_before_process_message', function($message) {
        echo "Before processing message: " . $message . "\n";
    });
}
test_before_process_message_hook();

// After process message hook
function test_after_process_message_hook() {
    add_action('MPAI_HOOK_ACTION_after_process_message', function($message, $result) {
        echo "After processing message: " . $message . "\n";
        echo "Response success: " . ($result['success'] ? 'true' : 'false') . "\n";
    }, 10, 2);
}
test_after_process_message_hook();

// Before save history hook
function test_before_save_history_hook() {
    add_action('MPAI_HOOK_ACTION_before_save_history', function($message, $response) {
        echo "Before saving history - Message: " . substr($message, 0, 30) . "...\n";
    }, 10, 2);
}
test_before_save_history_hook();

// After save history hook
function test_after_save_history_hook() {
    add_action('MPAI_HOOK_ACTION_after_save_history', function($message, $response) {
        echo "After saving history - Message: " . substr($message, 0, 30) . "...\n";
    }, 10, 2);
}
test_after_save_history_hook();

// Before clear history hook
function test_before_clear_history_hook() {
    add_action('MPAI_HOOK_ACTION_before_clear_history', function() {
        echo "Before clearing history\n";
    });
}
test_before_clear_history_hook();

// After clear history hook
function test_after_clear_history_hook() {
    add_action('MPAI_HOOK_ACTION_after_clear_history', function() {
        echo "After clearing history\n";
    });
}
test_after_clear_history_hook();

echo "All hooks and filters registered successfully!\n";
echo "To test them, use the MemberPress AI Assistant chat interface.\n";