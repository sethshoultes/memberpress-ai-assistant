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

### `MPAI_HOOK_ACTION_tool_registry_init`

**Description:** Fires after tool registry initialization.

**Parameters:**
- `$registry` (MPAI_Tool_Registry): The tool registry instance.

**Example:**
```php
add_action('MPAI_HOOK_ACTION_tool_registry_init', function($registry) {
    // Code to run after tool registry initialization
    error_log('Tool registry initialized with ' . count($registry->get_available_tools()) . ' tools');
});
```

### `MPAI_HOOK_ACTION_register_tool`

**Description:** Fires when a tool is registered to the system.

**Parameters:**
- `$tool_id` (string): The ID of the registered tool.
- `$tool` (object): The tool instance.
- `$registry` (MPAI_Tool_Registry): The tool registry instance.

**Example:**
```php
add_action('MPAI_HOOK_ACTION_register_tool', function($tool_id, $tool, $registry) {
    // Code to run when a tool is registered
    error_log('Tool registered: ' . $tool_id);
}, 10, 3);
```

### `MPAI_HOOK_ACTION_before_tool_execution`

**Description:** Fires before any tool is executed.

**Parameters:**
- `$tool_name` (string): The name of the tool being executed.
- `$parameters` (array): The parameters for the tool execution.
- `$tool` (MPAI_Base_Tool): The tool instance.

**Example:**
```php
add_action('MPAI_HOOK_ACTION_before_tool_execution', function($tool_name, $parameters, $tool) {
    // Code to run before tool execution
    error_log('Executing tool: ' . $tool_name . ' with parameters: ' . json_encode($parameters));
}, 10, 3);
```

### `MPAI_HOOK_ACTION_after_tool_execution`

**Description:** Fires after tool execution with tool name, parameters, and result.

**Parameters:**
- `$tool_name` (string): The name of the tool that was executed.
- `$parameters` (array): The parameters used for the tool execution.
- `$result` (mixed): The result of the tool execution.
- `$tool` (MPAI_Base_Tool): The tool instance.

**Example:**
```php
add_action('MPAI_HOOK_ACTION_after_tool_execution', function($tool_name, $parameters, $result, $tool) {
    // Code to run after tool execution
    error_log('Tool execution completed: ' . $tool_name . ' with result: ' . json_encode($result));
}, 10, 4);
```

### `MPAI_HOOK_FILTER_tool_parameters` (Filter)

**Description:** Filters tool parameters before execution.

**Parameters:**
- `$parameters` (array): The parameters to filter.
- `$tool_name` (string): The name of the tool.
- `$tool` (MPAI_Base_Tool): The tool instance.

**Example:**
```php
add_filter('MPAI_HOOK_FILTER_tool_parameters', function($parameters, $tool_name, $tool) {
    // Modify tool parameters
    if ($tool_name === 'wp_api' && isset($parameters['post_type'])) {
        // Add additional parameters for specific tools
        $parameters['post_status'] = 'publish';
    }
    return $parameters;
}, 10, 3);
```

### `MPAI_HOOK_FILTER_tool_execution_result` (Filter)

**Description:** Filters tool execution result.

**Parameters:**
- `$result` (mixed): The result to filter.
- `$tool_name` (string): The name of the tool.
- `$parameters` (array): The parameters used for execution.
- `$tool` (MPAI_Base_Tool): The tool instance.

**Example:**
```php
add_filter('MPAI_HOOK_FILTER_tool_execution_result', function($result, $tool_name, $parameters, $tool) {
    // Modify tool execution result
    if ($tool_name === 'search' && is_array($result) && !empty($result)) {
        // Add additional information to search results
        $result['total_count'] = count($result['items']);
        $result['search_time'] = microtime(true);
    }
    return $result;
}, 10, 4);
```

### `MPAI_HOOK_FILTER_available_tools` (Filter)

**Description:** Filters the list of available tools.

**Parameters:**
- `$tools` (array): The tools array.
- `$registry` (MPAI_Tool_Registry): The tool registry instance.

**Example:**
```php
add_filter('MPAI_HOOK_FILTER_available_tools', function($tools, $registry) {
    // Modify available tools
    // For example, remove a specific tool
    if (isset($tools['sensitive_tool'])) {
        unset($tools['sensitive_tool']);
    }
    return $tools;
}, 10, 2);
```

### `MPAI_HOOK_FILTER_tool_capability_check` (Filter)

**Description:** Filters whether a user has capability to use a specific tool.

**Parameters:**
- `$can_use` (bool): Whether the user can use the tool.
- `$tool_name` (string): The name of the tool.
- `$parameters` (array): The parameters for the tool execution.
- `$tool` (MPAI_Base_Tool): The tool instance.

**Example:**
```php
add_filter('MPAI_HOOK_FILTER_tool_capability_check', function($can_use, $tool_name, $parameters, $tool) {
    // Modify capability check
    if ($tool_name === 'wp_api' && isset($parameters['action']) && $parameters['action'] === 'delete_post') {
        // Only allow users with delete_posts capability to delete posts
        $can_use = current_user_can('delete_posts');
    }
    return $can_use;
}, 10, 4);
```

## Agent System Hooks

### `MPAI_HOOK_ACTION_register_agent`

**Description:** Fires when an agent is registered to the system.

**Parameters:**
- `$agent_id` (string): The ID of the registered agent.
- `$agent_instance` (object): The agent instance.
- `$orchestrator` (MPAI_Agent_Orchestrator): The agent orchestrator instance.

**Example:**
```php
add_action('MPAI_HOOK_ACTION_register_agent', function($agent_id, $agent_instance, $orchestrator) {
    // Code to run when an agent is registered
    error_log('Agent registered: ' . $agent_id);
}, 10, 3);
```

### `MPAI_HOOK_ACTION_before_agent_process`

**Description:** Fires before agent processes a request.

**Parameters:**
- `$agent_id` (string): The ID of the agent.
- `$params` (array): The parameters for the agent.
- `$user_id` (int): The user ID.
- `$context` (array): The user context.
- `$orchestrator` (MPAI_Agent_Orchestrator): The agent orchestrator instance.

**Example:**
```php
add_action('MPAI_HOOK_ACTION_before_agent_process', function($agent_id, $params, $user_id, $context, $orchestrator) {
    // Code to run before agent processes a request
    error_log('Agent ' . $agent_id . ' processing request for user ' . $user_id);
}, 10, 5);
```

### `MPAI_HOOK_ACTION_after_agent_process`

**Description:** Fires after agent processes a request.

**Parameters:**
- `$agent_id` (string): The ID of the agent.
- `$params` (array): The parameters for the agent.
- `$user_id` (int): The user ID.
- `$result` (array): The result of the agent processing.
- `$orchestrator` (MPAI_Agent_Orchestrator): The agent orchestrator instance.

**Example:**
```php
add_action('MPAI_HOOK_ACTION_after_agent_process', function($agent_id, $params, $user_id, $result, $orchestrator) {
    // Code to run after agent processes a request
    error_log('Agent ' . $agent_id . ' completed processing for user ' . $user_id . ' with result: ' . json_encode($result));
}, 10, 5);
```

### `MPAI_HOOK_FILTER_agent_capabilities` (Filter)

**Description:** Filters agent capabilities.

**Parameters:**
- `$capabilities` (array): The agent capabilities.
- `$agent_id` (string): The ID of the agent.
- `$agent` (object): The agent instance.
- `$orchestrator` (MPAI_Agent_Orchestrator): The agent orchestrator instance.

**Example:**
```php
add_filter('MPAI_HOOK_FILTER_agent_capabilities', function($capabilities, $agent_id, $agent, $orchestrator) {
    // Modify agent capabilities
    if ($agent_id === 'memberpress_agent') {
        // Add additional capabilities for MemberPress agent
        $capabilities['can_manage_subscriptions'] = true;
    }
    return $capabilities;
}, 10, 4);
```

### `MPAI_HOOK_FILTER_agent_validation` (Filter)

**Description:** Filters agent validation results.

**Parameters:**
- `$is_valid` (bool): Whether the agent is valid.
- `$agent_id` (string): The ID of the agent.
- `$agent` (object): The agent instance.
- `$orchestrator` (MPAI_Agent_Orchestrator): The agent orchestrator instance.

**Example:**
```php
add_filter('MPAI_HOOK_FILTER_agent_validation', function($is_valid, $agent_id, $agent, $orchestrator) {
    // Modify agent validation
    if ($agent_id === 'custom_agent' && !method_exists($agent, 'custom_required_method')) {
        // Invalidate agent if it doesn't have a required method
        $is_valid = false;
    }
    return $is_valid;
}, 10, 4);
```

### `MPAI_HOOK_FILTER_agent_scoring` (Filter)

**Description:** Filters confidence scores for agent selection.

**Parameters:**
- `$scores` (array): The agent confidence scores.
- `$message` (string): The user message.
- `$context` (array): The user context.
- `$orchestrator` (MPAI_Agent_Orchestrator): The agent orchestrator instance.

**Example:**
```php
add_filter('MPAI_HOOK_FILTER_agent_scoring', function($scores, $message, $context, $orchestrator) {
    // Modify agent scoring
    if (stripos($message, 'membership') !== false) {
        // Boost score for MemberPress agent when message contains 'membership'
        if (isset($scores['memberpress_agent'])) {
            $scores['memberpress_agent'] += 0.2;
        }
    }
    return $scores;
}, 10, 4);
```

### `MPAI_HOOK_FILTER_agent_handoff` (Filter)

**Description:** Filters agent handoff behavior.

**Parameters:**
- `$selected_agent_id` (string): The ID of the selected agent.
- `$agent_scores` (array): The agent confidence scores.
- `$message` (string): The user message.
- `$orchestrator` (MPAI_Agent_Orchestrator): The agent orchestrator instance.

**Example:**
```php
add_filter('MPAI_HOOK_FILTER_agent_handoff', function($selected_agent_id, $agent_scores, $message, $orchestrator) {
    // Modify agent handoff
    if ($selected_agent_id === 'general_agent' && stripos($message, 'subscription') !== false) {
        // Hand off to MemberPress agent for subscription-related queries
        return 'memberpress_agent';
    }
    return $selected_agent_id;
}, 10, 4);
```

## Content and UI Hooks

### `MPAI_HOOK_FILTER_generated_content` (Filter)

**Description:** Filter any AI-generated content before use.

**Parameters:**
- `$content` (string): The generated content.
- `$content_type` (string): The type of content being generated.

**Example:**
```php
add_filter('MPAI_HOOK_FILTER_generated_content', function($content, $content_type) {
    // Modify generated content
    if ($content_type === 'blog_post') {
        // Add a disclaimer to blog posts
        $content .= "\n\nDisclaimer: This content was generated with AI assistance.";
    }
    return $content;
}, 10, 2);
```

### `MPAI_HOOK_FILTER_content_template` (Filter)

**Description:** Filter content templates before filling with data.

**Parameters:**
- `$template` (string): The content template.

**Example:**
```php
add_filter('MPAI_HOOK_FILTER_content_template', function($template) {
    // Modify content template
    // For example, add custom placeholders or modify existing ones
    return str_replace('{{standard_footer}}', '{{custom_footer}}', $template);
});
```

### `MPAI_HOOK_FILTER_content_formatting` (Filter)

**Description:** Filter content formatting rules.

**Parameters:**
- `$rules` (array): The formatting rules.

**Example:**
```php
add_filter('MPAI_HOOK_FILTER_content_formatting', function($rules) {
    // Modify formatting rules
    // For example, change the paragraph wrapper
    if (isset($rules['paragraph'])) {
        $rules['paragraph']['wrapper'] = '<!-- wp:paragraph {"className":"custom-paragraph"} --><p class="custom-paragraph">%s</p><!-- /wp:paragraph -->';
    }
    
    // Add a new formatting rule
    $rules['custom_block'] = [
        'tag' => 'div',
        'wrapper' => '<!-- wp:custom-block --><div class="custom-block">%s</div><!-- /wp:custom-block -->'
    ];
    
    return $rules;
});
```

### `MPAI_HOOK_FILTER_blog_post_content` (Filter)

**Description:** Filter blog post content before creation.

**Parameters:**
- `$post_data` (array): The post data.
- `$xml_content` (string): The original XML content.

**Example:**
```php
add_filter('MPAI_HOOK_FILTER_blog_post_content', function($post_data, $xml_content) {
    // Modify post data
    // For example, add a category or tag
    if (!isset($post_data['tags'])) {
        $post_data['tags'] = ['ai-generated'];
    }
    
    // Modify the title
    if (isset($post_data['title'])) {
        $post_data['title'] = '[AI] ' . $post_data['title'];
    }
    
    return $post_data;
}, 10, 2);
```

### `MPAI_HOOK_ACTION_before_content_save`

**Description:** Action before saving generated content.

**Parameters:**
- `$content` (string): The content to be saved.
- `$content_type` (string): The type of content being saved.

**Example:**
```php
add_action('MPAI_HOOK_ACTION_before_content_save', function($content, $content_type) {
    // Code to run before saving content
    error_log('About to save ' . $content_type . ' content: ' . substr($content, 0, 50) . '...');
    
    // You could also perform validation or preprocessing here
}, 10, 2);
```

### `MPAI_HOOK_ACTION_after_content_save`

**Description:** Action after saving generated content.

**Parameters:**
- `$content` (string): The saved content.
- `$content_type` (string): The type of content that was saved.
- `$content_id` (int): The ID of the saved content.

**Example:**
```php
add_action('MPAI_HOOK_ACTION_after_content_save', function($content, $content_type, $content_id) {
    // Code to run after saving content
    error_log('Saved ' . $content_type . ' content with ID: ' . $content_id);
    
    // You could also perform additional actions like sending notifications
    if ($content_type === 'blog_post') {
        do_action('custom_notify_new_post', $content_id);
    }
}, 10, 3);
```

### `MPAI_HOOK_FILTER_content_type` (Filter)

**Description:** Filter the detected content type from AI responses.

**Parameters:**
- `$detected_type` (string): The detected content type.
- `$block_type` (string): The original block type.
- `$content` (string): The content.

**Example:**
```php
add_filter('MPAI_HOOK_FILTER_content_type', function($detected_type, $block_type, $content) {
    // Modify detected content type
    // For example, detect a custom content type based on content patterns
    if (strpos($content, 'RECIPE:') === 0) {
        return 'recipe';
    }
    
    return $detected_type;
}, 10, 3);
```

### `MPAI_HOOK_FILTER_content_marker` (Filter)

**Description:** Filter content markers used in XML parsing.

**Parameters:**
- `$markers` (array): The content markers.

**Example:**
```php
add_filter('MPAI_HOOK_FILTER_content_marker', function($markers) {
    // Modify content markers
    // For example, change the marker for post title
    $markers['title'] = 'custom-title';
    
    // Add a new marker
    $markers['subtitle'] = 'post-subtitle';
    
    return $markers;
});
```

### `MPAI_HOOK_FILTER_admin_menu_items` (Filter)

**Description:** Filter admin menu items before registration.

**Parameters:**
- `$menu_items` (array): The menu items.
- `$has_memberpress` (bool): Whether MemberPress is active.

**Example:**
```php
add_filter('MPAI_HOOK_FILTER_admin_menu_items', function($menu_items, $has_memberpress) {
    // Modify menu items
    // For example, add a new submenu item
    $menu_items[] = [
        'type' => 'submenu',
        'parent' => $has_memberpress ? 'memberpress' : 'memberpress-ai-assistant',
        'page_title' => 'Custom Reports',
        'menu_title' => 'Custom Reports',
        'capability' => 'manage_options',
        'menu_slug' => 'mpai-custom-reports',
        'callback' => 'my_plugin_render_reports_page'
    ];
    
    return $menu_items;
}, 10, 2);
```

### `MPAI_HOOK_FILTER_admin_capabilities` (Filter)

**Description:** Filter capabilities required for admin functions.

**Parameters:**
- `$capability` (string): The capability.
- `$menu_slug` (string): The menu slug.

**Example:**
```php
add_filter('MPAI_HOOK_FILTER_admin_capabilities', function($capability, $menu_slug) {
    // Modify required capabilities
    // For example, require a different capability for a specific page
    if ($menu_slug === 'memberpress-ai-assistant-logs') {
        return 'view_mpai_logs';
    }
    
    return $capability;
}, 10, 2);
```

### `MPAI_HOOK_FILTER_settings_fields` (Filter)

**Description:** Filter settings fields before display.

**Parameters:**
- `$fields` (array): The settings fields.

**Example:**
```php
add_filter('MPAI_HOOK_FILTER_settings_fields', function($fields) {
    // Modify settings fields
    // For example, add a new setting
    $fields['mpai_custom_setting'] = [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => 'default value',
        'section' => 'mpai_api_settings',
        'title' => 'Custom Setting',
        'callback' => 'render_custom_setting_field',
    ];
    
    return $fields;
});
```

### `MPAI_HOOK_FILTER_settings_tabs` (Filter)

**Description:** Filter settings tabs before display.

**Parameters:**
- `$tabs` (array): The settings tabs.

**Example:**
```php
add_filter('MPAI_HOOK_FILTER_settings_tabs', function($tabs) {
    // Modify settings tabs
    // For example, add a new tab
    $tabs['mpai_custom_tab'] = [
        'title' => 'Custom Features',
        'callback' => 'render_custom_tab_description'
    ];
    
    return $tabs;
});
```

### `MPAI_HOOK_ACTION_before_display_settings`

**Description:** Action before displaying settings page.

**Parameters:** None

**Example:**
```php
add_action('MPAI_HOOK_ACTION_before_display_settings', function() {
    // Code to run before displaying settings
    // For example, add a notice at the top of the page
    echo '<div class="notice notice-info"><p>Settings last updated: ' . date('Y-m-d H:i:s') . '</p></div>';
});
```

### `MPAI_HOOK_ACTION_after_display_settings`

**Description:** Action after displaying settings page.

**Parameters:** None

**Example:**
```php
add_action('MPAI_HOOK_ACTION_after_display_settings', function() {
    // Code to run after displaying settings
    // For example, add custom JavaScript
    echo '<script>
        jQuery(document).ready(function($) {
            console.log("Settings page loaded");
        });
    </script>';
});
```

### `MPAI_HOOK_FILTER_dashboard_sections` (Filter)

**Description:** Filter dashboard page sections.

**Parameters:**
- `$sections` (array): The dashboard sections.

**Example:**
```php
add_filter('MPAI_HOOK_FILTER_dashboard_sections', function($sections) {
    // Modify dashboard sections
    // For example, add a new section
    $sections['custom_stats'] = [
        'title' => 'Custom Statistics',
        'callback' => 'render_custom_stats_section',
        'priority' => 30
    ];
    
    return $sections;
});
```

### `MPAI_HOOK_FILTER_chat_interface_render` (Filter)

**Description:** Filter chat interface rendering.

**Parameters:**
- `$content` (string): The chat interface HTML.
- `$position` (string): The chat position.
- `$welcome_message` (string): The welcome message.

**Example:**
```php
add_filter('MPAI_HOOK_FILTER_chat_interface_render', function($content, $position, $welcome_message) {
    // Modify chat interface HTML
    // For example, add a custom class or attribute
    $content = str_replace('class="mpai-chat-container', 'class="mpai-chat-container custom-theme', $content);
    
    // Or add a custom element
    $content .= '<div class="mpai-branding">Powered by My Company</div>';
    
    return $content;
}, 10, 3);
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