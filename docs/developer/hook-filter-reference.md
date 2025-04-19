# MemberPress AI Assistant Hook and Filter Reference

This document provides a comprehensive reference for all hooks and filters available in the MemberPress AI Assistant plugin. These hooks and filters allow developers to extend and customize the plugin's functionality.

## Plugin Initialization Hooks

### `MPAI_HOOK_ACTION_before_plugin_init`

**Description:** Fires before the plugin initialization process begins.

**Parameters:** None

**Example:**
```php
add_action('MPAI_HOOK_ACTION_before_plugin_init', function() {
    // Code to run before plugin initialization
});
```

### `MPAI_HOOK_ACTION_loaded_dependencies`

**Description:** Fires after all plugin dependencies have been loaded.

**Parameters:** None

**Example:**
```php
add_action('MPAI_HOOK_ACTION_loaded_dependencies', function() {
    // Code to run after dependencies are loaded
});
```

### `MPAI_HOOK_ACTION_after_plugin_init`

**Description:** Fires after the plugin has been fully initialized.

**Parameters:** None

**Example:**
```php
add_action('MPAI_HOOK_ACTION_after_plugin_init', function() {
    // Code to run after plugin initialization
});
```

### `MPAI_HOOK_FILTER_default_options` (Filter)

**Description:** Filters the default plugin options.

**Parameters:**
- `$options` (array): The default options array.

**Example:**
```php
add_filter('MPAI_HOOK_FILTER_default_options', function($options) {
    // Modify default options
    $options['custom_option'] = 'custom_value';
    return $options;
});
```

### `MPAI_HOOK_FILTER_plugin_capabilities` (Filter)

**Description:** Filters the plugin capabilities.

**Parameters:**
- `$capabilities` (array): The capabilities array.

**Example:**
```php
add_filter('MPAI_HOOK_FILTER_plugin_capabilities', function($capabilities) {
    // Add or modify capabilities
    $capabilities['custom_capability'] = true;
    return $capabilities;
});
```

## Chat Processing Hooks

### `MPAI_HOOK_ACTION_before_process_message`

**Description:** Fires before a user message is processed.

**Parameters:**
- `$message` (string): The user message being processed.

**Example:**
```php
add_action('MPAI_HOOK_ACTION_before_process_message', function($message) {
    // Code to run before processing a message
    error_log('Processing message: ' . $message);
});
```

### `MPAI_HOOK_ACTION_after_process_message`

**Description:** Fires after a user message has been processed.

**Parameters:**
- `$message` (string): The user message that was processed.
- `$response` (array): The AI response array.

**Example:**
```php
add_action('MPAI_HOOK_ACTION_after_process_message', function($message, $response) {
    // Code to run after processing a message
    error_log('Message processed. Success: ' . ($response['success'] ? 'true' : 'false'));
}, 10, 2);
```

### `MPAI_HOOK_FILTER_system_prompt` (Filter)

**Description:** Filters the system prompt sent to the AI.

**Parameters:**
- `$system_prompt` (string): The system prompt to filter.

**Example:**
```php
add_filter('MPAI_HOOK_FILTER_system_prompt', function($system_prompt) {
    // Modify the system prompt
    return $system_prompt . "\n\nADDITIONAL INSTRUCTIONS: Always be extra helpful.";
});
```

### `MPAI_HOOK_FILTER_chat_conversation_history` (Filter)

**Description:** Filters the conversation history array.

**Parameters:**
- `$conversation` (array): The conversation history array.

**Example:**
```php
add_filter('MPAI_HOOK_FILTER_chat_conversation_history', function($conversation) {
    // Modify the conversation history
    // For example, limit the number of messages
    if (count($conversation) > 10) {
        // Keep only the system prompt and the last 9 messages
        $system_prompt = $conversation[0];
        $last_messages = array_slice($conversation, -9);
        $conversation = array_merge([$system_prompt], $last_messages);
    }
    return $conversation;
});
```

### `MPAI_HOOK_FILTER_message_content` (Filter)

**Description:** Filters message content before sending to AI.

**Parameters:**
- `$message` (string): The message content to filter.

**Example:**
```php
add_filter('MPAI_HOOK_FILTER_message_content', function($message) {
    // Modify the message content
    // For example, add a prefix to all messages
    return "[Customer Query] " . $message;
});
```

### `MPAI_HOOK_FILTER_response_content` (Filter)

**Description:** Filters AI response before returning to user.

**Parameters:**
- `$response` (string): The AI response content.
- `$message` (string): The original user message.

**Example:**
```php
add_filter('MPAI_HOOK_FILTER_response_content', function($response, $message) {
    // Modify the response content
    // For example, add a footer to all responses
    return $response . "\n\n---\nPowered by MemberPress AI";
}, 10, 2);
```

### `MPAI_HOOK_FILTER_user_context` (Filter)

**Description:** Filters user context data sent with messages.

**Parameters:**
- `$user_context` (array): The user context data.

**Example:**
```php
add_filter('MPAI_HOOK_FILTER_user_context', function($user_context) {
    // Add custom data to user context
    $user_context['last_login'] = get_user_meta($user_context['user_id'], 'last_login', true);
    $user_context['custom_role'] = 'example_extension_user';
    return $user_context;
});
```

### `MPAI_HOOK_FILTER_allowed_commands` (Filter)

**Description:** Filters allowed commands in chat.

**Parameters:**
- `$allowed_commands` (array): Array of allowed command names.

**Example:**
```php
add_filter('MPAI_HOOK_FILTER_allowed_commands', function($allowed_commands) {
    // Add custom allowed commands
    $allowed_commands[] = 'wp theme list';
    return $allowed_commands;
});
```

## History Management Hooks

### `MPAI_HOOK_ACTION_before_save_history`

**Description:** Action before saving chat history.

**Parameters:**
- `$message` (string): The user message.
- `$response` (string): The AI response.

**Example:**
```php
add_action('MPAI_HOOK_ACTION_before_save_history', function($message, $response) {
    // Code to run before saving history
    error_log('Saving chat history for message: ' . substr($message, 0, 30) . '...');
}, 10, 2);
```

### `MPAI_HOOK_ACTION_after_save_history`

**Description:** Action after saving chat history.

**Parameters:**
- `$message` (string): The user message.
- `$response` (string): The AI response.

**Example:**
```php
add_action('MPAI_HOOK_ACTION_after_save_history', function($message, $response) {
    // Code to run after saving history
    error_log('Chat history saved for message: ' . substr($message, 0, 30) . '...');
}, 10, 2);
```

### `MPAI_HOOK_ACTION_before_clear_history`

**Description:** Action before clearing chat history.

**Parameters:** None

**Example:**
```php
add_action('MPAI_HOOK_ACTION_before_clear_history', function() {
    // Code to run before clearing history
    error_log('About to clear chat history');
});
```

### `MPAI_HOOK_ACTION_after_clear_history`

**Description:** Action after clearing chat history.

**Parameters:** None

**Example:**
```php
add_action('MPAI_HOOK_ACTION_after_clear_history', function() {
    // Code to run after clearing history
    error_log('Chat history cleared');
});
```

### `MPAI_HOOK_FILTER_history_retention` (Filter)

**Description:** Filter history retention settings.

**Parameters:**
- `$days` (integer): Number of days to retain history.

**Example:**
```php
add_filter('MPAI_HOOK_FILTER_history_retention', function($days) {
    // Modify retention period
    // For example, extend to 60 days
    return 60;
});
```

## Usage Examples

### Adding a Custom Footer to All AI Responses

```php
add_filter('MPAI_HOOK_FILTER_response_content', function($response, $message) {
    // Add a footer to all AI responses
    if (stripos($message, 'membership') !== false) {
        return $response . "\n\n---\nNeed more help with your membership? Contact our support team at support@example.com";
    }
    return $response;
}, 10, 2);
```

### Extending History Retention Period

```php
add_filter('MPAI_HOOK_FILTER_history_retention', function($days) {
    // Extend history retention to 60 days
    return 60;
});
```

### Logging All User Messages

```php
add_action('MPAI_HOOK_ACTION_before_process_message', function($message) {
    // Log all user messages
    error_log('User asked: ' . $message);
});
```

### Creating a Complete Extension Plugin

See the example extension plugin at `wp-content/plugins/memberpress-ai-assistant-example-extension/memberpress-ai-assistant-example-extension.php` for a complete example of how to use these hooks and filters in a real-world scenario.