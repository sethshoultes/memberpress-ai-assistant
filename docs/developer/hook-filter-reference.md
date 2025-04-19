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

## Tool Execution Hooks

### `MPAI_HOOK_ACTION_before_tool_execution`

**Description:** Fires before any tool is executed with tool name and parameters.

**Parameters:**
- `$tool_name` (string): The name of the tool being executed.
- `$parameters` (array): The validated tool parameters.

**Example:**
```php
add_action('MPAI_HOOK_ACTION_before_tool_execution', function($tool_name, $parameters) {
    // Code to run before tool execution
    error_log('Executing tool: ' . $tool_name . ' with parameters: ' . json_encode($parameters));
}, 10, 2);
```

### `MPAI_HOOK_ACTION_after_tool_execution`

**Description:** Fires after tool execution with tool name, parameters, and result.

**Parameters:**
- `$tool_name` (string): The name of the tool that was executed.
- `$parameters` (array): The parameters used for execution.
- `$result` (mixed): The tool execution result.

**Example:**
```php
add_action('MPAI_HOOK_ACTION_after_tool_execution', function($tool_name, $parameters, $result) {
    // Code to run after tool execution
    error_log('Tool execution completed: ' . $tool_name . ' with result: ' . json_encode($result));
}, 10, 3);
```

### `MPAI_HOOK_FILTER_tool_parameters` (Filter)

**Description:** Filters tool parameters before execution.

**Parameters:**
- `$parameters` (array): The tool parameters.
- `$tool_name` (string): The name of the tool being executed.

**Example:**
```php
add_filter('MPAI_HOOK_FILTER_tool_parameters', function($parameters, $tool_name) {
    // Modify parameters for a specific tool
    if ($tool_name === 'content_generator') {
        $parameters['add_timestamp'] = true;
    }
    return $parameters;
}, 10, 2);
```

### `MPAI_HOOK_FILTER_tool_execution_result` (Filter)

**Description:** Filters tool execution result.

**Parameters:**
- `$result` (mixed): The tool execution result.
- `$tool_name` (string): The name of the tool that was executed.
- `$parameters` (array): The parameters used for execution.

**Example:**
```php
add_filter('MPAI_HOOK_FILTER_tool_execution_result', function($result, $tool_name, $parameters) {
    // Add additional information to the result
    if (is_array($result) && $tool_name === 'content_generator') {
        $result['generated_by'] = 'Custom Extension';
    }
    return $result;
}, 10, 3);
```

### `MPAI_HOOK_FILTER_available_tools` (Filter)

**Description:** Filters the list of available tools.

**Parameters:**
- `$tools` (array): Array of available tool instances.

**Example:**
```php
add_filter('MPAI_HOOK_FILTER_available_tools', function($tools) {
    // Add a custom tool
    if (class_exists('My_Custom_Tool')) {
        $tools['my_custom_tool'] = new My_Custom_Tool();
    }
    return $tools;
});
```

### `MPAI_HOOK_ACTION_tool_registry_init`

**Description:** Fires after tool registry initialization.

**Parameters:** None

**Example:**
```php
add_action('MPAI_HOOK_ACTION_tool_registry_init', function() {
    // Code to run after tool registry initialization
    // This is a good place to register custom tools
    global $mpai_tool_registry;
    if ($mpai_tool_registry) {
        $mpai_tool_registry->register_tool_definition('my_tool', 'My_Tool_Class', '/path/to/my-tool-class.php');
    }
});
```

### `MPAI_HOOK_ACTION_register_tool`

**Description:** Fires when a tool is registered to the system.

**Parameters:**
- `$tool_id` (string): The tool identifier.
- `$tool_instance` (object): The tool instance.

**Example:**
```php
add_action('MPAI_HOOK_ACTION_register_tool', function($tool_id, $tool_instance) {
    // Code to run when a tool is registered
    error_log('Tool registered: ' . $tool_id);
}, 10, 2);
```

### `MPAI_HOOK_FILTER_tool_capability_check` (Filter)

**Description:** Filters whether a user has capability to use a specific tool.

**Parameters:**
- `$has_capability` (bool): Whether the user has capability.
- `$tool_id` (string): The tool identifier.
- `$user_id` (int): The user ID.

**Example:**
```php
add_filter('MPAI_HOOK_FILTER_tool_capability_check', function($has_capability, $tool_id, $user_id) {
    // Allow editors to use all tools
    if (user_can($user_id, 'edit_posts')) {
        return true;
    }
    return $has_capability;
}, 10, 3);
```

## Agent System Hooks

### `MPAI_HOOK_FILTER_agent_capabilities` (Filter)

**Description:** Filters agent capabilities.

**Parameters:**
- `$capabilities` (array): The agent capabilities.
- `$agent_id` (string): The agent identifier.

**Example:**
```php
add_filter('MPAI_HOOK_FILTER_agent_capabilities', function($capabilities, $agent_id) {
    // Add a custom capability to a specific agent
    if ($agent_id === 'content_agent') {
        $capabilities['custom_capability'] = true;
    }
    return $capabilities;
}, 10, 2);
```

### `MPAI_HOOK_ACTION_before_agent_process`

**Description:** Fires before agent processes a request.

**Parameters:**
- `$message` (string): The user message.
- `$user_id` (int): The user ID.
- `$context` (array): The user context.

**Example:**
```php
add_action('MPAI_HOOK_ACTION_before_agent_process', function($message, $user_id, $context) {
    // Code to run before agent processes a request
    error_log('Agent processing request: ' . $message);
}, 10, 3);
```

### `MPAI_HOOK_ACTION_after_agent_process`

**Description:** Fires after agent processes a request.

**Parameters:**
- `$message` (string): The user message.
- `$user_id` (int): The user ID.
- `$context` (array): The user context.
- `$result` (array): The processing result.

**Example:**
```php
add_action('MPAI_HOOK_ACTION_after_agent_process', function($message, $user_id, $context, $result) {
    // Code to run after agent processes a request
    error_log('Agent processed request with result: ' . json_encode($result));
}, 10, 4);
```

### `MPAI_HOOK_FILTER_agent_validation` (Filter)

**Description:** Filters agent validation results.

**Parameters:**
- `$validation_result` (array): The validation result array.
- `$agent_id` (string): The agent identifier.
- `$agent` (object): The agent instance.

**Example:**
```php
add_filter('MPAI_HOOK_FILTER_agent_validation', function($validation_result, $agent_id, $agent) {
    // Add custom validation rules
    if ($agent_id === 'custom_agent' && !method_exists($agent, 'custom_method')) {
        $validation_result['is_valid'] = false;
        $validation_result['errors'][] = 'Agent does not implement custom_method';
    }
    return $validation_result;
}, 10, 3);
```

### `MPAI_HOOK_FILTER_agent_scoring` (Filter)

**Description:** Filters confidence scores for agent selection.

**Parameters:**
- `$score` (int): The confidence score (0-100).
- `$agent_id` (string): The agent identifier.
- `$message` (string): The user message.
- `$context` (array): The context data.

**Example:**
```php
add_filter('MPAI_HOOK_FILTER_agent_scoring', function($score, $agent_id, $message, $context) {
    // Boost score for a specific agent when certain keywords are present
    if ($agent_id === 'membership_agent' && stripos($message, 'subscription') !== false) {
        return min($score + 20, 100);
    }
    return $score;
}, 10, 4);
```

### `MPAI_HOOK_ACTION_register_agent`

**Description:** Fires when an agent is registered to the system.

**Parameters:**
- `$agent_id` (string): The agent identifier.
- `$agent_instance` (object): The agent instance.

**Example:**
```php
add_action('MPAI_HOOK_ACTION_register_agent', function($agent_id, $agent_instance) {
    // Code to run when an agent is registered
    error_log('Agent registered: ' . $agent_id);
}, 10, 2);
```

### `MPAI_HOOK_FILTER_agent_handoff` (Filter)

**Description:** Filters agent handoff behavior.

**Parameters:**
- `$handoff_data` (array): The handoff data.
- `$from_agent_id` (string): The source agent identifier.
- `$to_agent_id` (string): The target agent identifier.
- `$user_id` (int): The user ID.

**Example:**
```php
add_filter('MPAI_HOOK_FILTER_agent_handoff', function($handoff_data, $from_agent_id, $to_agent_id, $user_id) {
    // Add additional context to handoff data
    $handoff_data['custom_context'] = 'Additional information for handoff';
    return $handoff_data;
}, 10, 4);
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

### Creating a Custom Tool

See the example custom tool extension plugin at `wp-content/plugins/memberpress-ai-assistant-custom-tool/memberpress-ai-assistant-custom-tool.php` for a complete example of how to create and register a custom tool using the hook system.

### Creating a Complete Extension Plugin

See the example extension plugin at `wp-content/plugins/memberpress-ai-assistant-example-extension/memberpress-ai-assistant-example-extension.php` for a complete example of how to use these hooks and filters in a real-world scenario.