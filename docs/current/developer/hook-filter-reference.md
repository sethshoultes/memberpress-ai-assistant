# MemberPress AI Assistant: Hook & Filter Reference

**Version:** 1.0.0  
**Last Updated:** 2025-04-06  
**Status:** 🚧 In Progress  
**Audience:** 👩‍💻 Developers  
**Difficulty:** 🔴 Advanced  
**Reading Time:** ⏱️ 25 minutes

## Overview

This comprehensive reference documents all available hooks and filters in the MemberPress AI Assistant plugin. These extension points allow developers to customize, extend, and integrate with the plugin's functionality.

## Table of Contents

1. [Using Hooks and Filters](#using-hooks-and-filters)
2. [Hook Categories](#hook-categories)
3. [Action Hooks](#action-hooks)
4. [Filter Hooks](#filter-hooks)
5. [Hook Usage Examples](#hook-usage-examples)
6. [Hook Execution Order](#hook-execution-order)
7. [Deprecated Hooks](#deprecated-hooks)
8. [Hook Best Practices](#hook-best-practices)

## Using Hooks and Filters

### Understanding WordPress Hooks

The MemberPress AI Assistant follows WordPress conventions for hooks and filters:

- **Action Hooks**: Allow you to execute custom code at specific points in the execution flow
- **Filter Hooks**: Allow you to modify data as it's being processed

### Implementation Examples

**Adding an Action Hook:**

```php
// Execute code when the AI Assistant generates a response
add_action('memberpress_ai_after_response_generation', 'my_custom_function', 10, 2);

function my_custom_function($response, $query) {
    // Custom code here
    error_log('AI Response generated for query: ' . $query);
}
```

**Adding a Filter Hook:**

```php
// Modify the AI prompt before it's sent to the AI service
add_filter('memberpress_ai_modify_prompt', 'my_prompt_modifier', 10, 2);

function my_prompt_modifier($prompt, $context) {
    // Modify the prompt
    $prompt .= " Please focus specifically on membership issues.";
    return $prompt;
}
```

## Hook Categories

Hooks in the MemberPress AI Assistant are organized into the following categories:

| Category | Description | Common Uses |
|----------|-------------|-------------|
| Initialization | Plugin setup and loading | Configure services, register custom storage |
| Request Processing | Handling user queries | Modify queries, add context, log requests |
| Response Generation | AI service interaction | Modify prompts, handle services, process responses |
| UI/Display | Interface rendering | Customize interface, add elements, modify display |
| Data Access | Content and membership data | Add data sources, modify access permissions |
| Caching | Response cache system | Configure caching, invalidate cache, modify storage |
| Logging | Event and error logging | Add loggers, filter log content, format logs |
| Security | Permission and data safety | Modify permissions, validate requests, filter data |
| Integration | Third-party connections | Add providers, modify service connections |

## Action Hooks

### Initialization Hooks

#### `memberpress_ai_init`
Fired when the AI Assistant is initialized.

**Parameters:**
- `$ai_assistant` (object) - The main plugin instance

**Usage:**
```php
add_action('memberpress_ai_init', 'my_init_function');
function my_init_function($ai_assistant) {
    // Initialize custom functionality
}
```

#### `memberpress_ai_loaded`
Fired after all components have been loaded.

**Parameters:**
- `$ai_assistant` (object) - The main plugin instance

**Usage:**
```php
add_action('memberpress_ai_loaded', 'my_loaded_function');
function my_loaded_function($ai_assistant) {
    // Register additional components
}
```

### Request Processing Hooks

#### `memberpress_ai_before_process_query`
Fired before a user query is processed.

**Parameters:**
- `$query` (string) - The user's original query
- `$user_id` (int) - The ID of the user making the request

**Usage:**
```php
add_action('memberpress_ai_before_process_query', 'my_pre_process_function', 10, 2);
function my_pre_process_function($query, $user_id) {
    // Pre-process the query
    error_log("User $user_id submitted query: $query");
}
```

#### `memberpress_ai_after_process_query`
Fired after a user query has been processed but before sending to AI service.

**Parameters:**
- `$processed_query` (array) - The processed query data
- `$original_query` (string) - The original user query
- `$user_id` (int) - The ID of the user making the request

**Usage:**
```php
add_action('memberpress_ai_after_process_query', 'my_post_process_function', 10, 3);
function my_post_process_function($processed_query, $original_query, $user_id) {
    // Access or modify the processed query
}
```

### Response Generation Hooks

#### `memberpress_ai_before_request_response`
Fired before sending the request to the AI service.

**Parameters:**
- `$prompt` (string) - The formatted prompt
- `$context` (array) - The context data for the request
- `$service` (string) - The AI service being used

**Usage:**
```php
add_action('memberpress_ai_before_request_response', 'my_pre_request_function', 10, 3);
function my_pre_request_function($prompt, $context, $service) {
    // Log or modify the request
    error_log("Sending request to $service");
}
```

#### `memberpress_ai_after_response_generation`
Fired after receiving response from the AI service.

**Parameters:**
- `$response` (string) - The AI generated response
- `$query` (string) - The original query
- `$service` (string) - The AI service used

**Usage:**
```php
add_action('memberpress_ai_after_response_generation', 'my_post_response_function', 10, 3);
function my_post_response_function($response, $query, $service) {
    // Process or log the response
    update_user_meta(get_current_user_id(), 'last_ai_response', $response);
}
```

### UI/Display Hooks

#### `memberpress_ai_before_render_chat_interface`
Fired before rendering the chat interface.

**Parameters:**
- `$interface_settings` (array) - Chat interface settings

**Usage:**
```php
add_action('memberpress_ai_before_render_chat_interface', 'my_pre_render_function');
function my_pre_render_function($interface_settings) {
    // Prepare for interface rendering
}
```

#### `memberpress_ai_after_render_chat_interface`
Fired after rendering the chat interface.

**Parameters:**
- `$interface_id` (string) - The ID of the rendered interface

**Usage:**
```php
add_action('memberpress_ai_after_render_chat_interface', 'my_post_render_function');
function my_post_render_function($interface_id) {
    // Add additional elements or scripts
    echo '<div class="my-custom-element" data-interface="' . esc_attr($interface_id) . '"></div>';
}
```

### Data Access Hooks

#### `memberpress_ai_before_data_access`
Fired before accessing membership data.

**Parameters:**
- `$data_type` (string) - The type of data being accessed
- `$query_params` (array) - Query parameters for data access
- `$user_id` (int) - The user ID requesting the data

**Usage:**
```php
add_action('memberpress_ai_before_data_access', 'my_data_access_function', 10, 3);
function my_data_access_function($data_type, $query_params, $user_id) {
    // Log or restrict data access
    if ($data_type === 'sensitive_data' && !current_user_can('administrator')) {
        wp_die('Access denied');
    }
}
```

#### `memberpress_ai_after_data_access`
Fired after data has been retrieved.

**Parameters:**
- `$data` (mixed) - The retrieved data
- `$data_type` (string) - The type of data accessed
- `$query_params` (array) - The query parameters used

**Usage:**
```php
add_action('memberpress_ai_after_data_access', 'my_post_data_access_function', 10, 3);
function my_post_data_access_function($data, $data_type, $query_params) {
    // Process or log the data access
    error_log("Data of type $data_type was accessed");
}
```

### Caching Hooks

#### `memberpress_ai_before_cache_response`
Fired before a response is cached.

**Parameters:**
- `$response` (string) - The response to be cached
- `$query` (string) - The original query
- `$cache_key` (string) - The generated cache key

**Usage:**
```php
add_action('memberpress_ai_before_cache_response', 'my_pre_cache_function', 10, 3);
function my_pre_cache_function($response, $query, $cache_key) {
    // Manipulate or log cache operations
    error_log("Caching response with key: $cache_key");
}
```

#### `memberpress_ai_after_cache_response`
Fired after a response has been cached.

**Parameters:**
- `$cache_key` (string) - The cache key used
- `$cache_result` (bool) - Whether caching was successful

**Usage:**
```php
add_action('memberpress_ai_after_cache_response', 'my_post_cache_function', 10, 2);
function my_post_cache_function($cache_key, $cache_result) {
    // Handle cache result
    if (!$cache_result) {
        error_log("Failed to cache response with key: $cache_key");
    }
}
```

### Logging Hooks

#### `memberpress_ai_log_event`
Fired when an event is logged.

**Parameters:**
- `$event_type` (string) - The type of event
- `$event_data` (array) - Data associated with the event
- `$log_level` (string) - The logging level (info, warning, error)

**Usage:**
```php
add_action('memberpress_ai_log_event', 'my_log_function', 10, 3);
function my_log_function($event_type, $event_data, $log_level) {
    // Process or forward logs
    if ($log_level === 'error') {
        // Send alert to admin
        wp_mail(get_option('admin_email'), 'AI Assistant Error', print_r($event_data, true));
    }
}
```

### Security Hooks

#### `memberpress_ai_verify_request`
Fired during request verification.

**Parameters:**
- `$is_valid` (bool) - Whether the request is valid
- `$request_data` (array) - The request data
- `$user_id` (int) - The user ID making the request

**Usage:**
```php
add_action('memberpress_ai_verify_request', 'my_verify_function', 10, 3);
function my_verify_function($is_valid, $request_data, $user_id) {
    // Additional validation logic
    if (!$is_valid) {
        error_log("Invalid request from user $user_id: " . print_r($request_data, true));
    }
}
```

## Filter Hooks

### Initialization Filters

#### `memberpress_ai_default_settings`
Filter the default plugin settings.

**Parameters:**
- `$default_settings` (array) - The default settings

**Usage:**
```php
add_filter('memberpress_ai_default_settings', 'my_default_settings_filter');
function my_default_settings_filter($default_settings) {
    // Modify default settings
    $default_settings['cache_duration'] = 86400; // 24 hours
    return $default_settings;
}
```

#### `memberpress_ai_service_providers`
Filter the available AI service providers.

**Parameters:**
- `$providers` (array) - The list of service providers

**Usage:**
```php
add_filter('memberpress_ai_service_providers', 'my_providers_filter');
function my_providers_filter($providers) {
    // Add custom provider
    $providers['my_custom_provider'] = [
        'name' => 'My Custom AI Service',
        'class' => 'My_Custom_Provider_Class',
    ];
    return $providers;
}
```

### Request Processing Filters

#### `memberpress_ai_query_context`
Filter the context data added to a query.

**Parameters:**
- `$context` (array) - The context data
- `$query` (string) - The original query
- `$user_id` (int) - The user ID making the request

**Usage:**
```php
add_filter('memberpress_ai_query_context', 'my_context_filter', 10, 3);
function my_context_filter($context, $query, $user_id) {
    // Add custom context data
    $context['custom_data'] = get_user_meta($user_id, 'custom_ai_preference', true);
    return $context;
}
```

#### `memberpress_ai_process_query`
Filter the query before processing.

**Parameters:**
- `$query` (string) - The user's query
- `$user_id` (int) - The user ID making the request

**Usage:**
```php
add_filter('memberpress_ai_process_query', 'my_query_filter', 10, 2);
function my_query_filter($query, $user_id) {
    // Modify the query
    $query = str_replace(['bad_word1', 'bad_word2'], '[redacted]', $query);
    return $query;
}
```

### Response Generation Filters

#### `memberpress_ai_modify_prompt`
Filter the prompt before sending to AI service.

**Parameters:**
- `$prompt` (string) - The formatted prompt
- `$context` (array) - The context data
- `$service` (string) - The AI service being used

**Usage:**
```php
add_filter('memberpress_ai_modify_prompt', 'my_prompt_filter', 10, 3);
function my_prompt_filter($prompt, $context, $service) {
    // Customize the prompt
    if ($service === 'openai') {
        $prompt = "Using concise language: " . $prompt;
    }
    return $prompt;
}
```

#### `memberpress_ai_modify_response`
Filter the response from the AI service.

**Parameters:**
- `$response` (string) - The AI generated response
- `$query` (string) - The original query
- `$service` (string) - The AI service used

**Usage:**
```php
add_filter('memberpress_ai_modify_response', 'my_response_filter', 10, 3);
function my_response_filter($response, $query, $service) {
    // Modify the response
    $response = str_replace('Disclaimer:', '<em>Disclaimer:</em>', $response);
    $response .= "<p class='ai-footer'>Generated by MemberPress AI Assistant</p>";
    return $response;
}
```

### UI/Display Filters

#### `memberpress_ai_interface_settings`
Filter the chat interface settings.

**Parameters:**
- `$settings` (array) - Interface settings

**Usage:**
```php
add_filter('memberpress_ai_interface_settings', 'my_interface_settings_filter');
function my_interface_settings_filter($settings) {
    // Modify interface settings
    $settings['theme'] = 'dark';
    $settings['position'] = 'top-right';
    return $settings;
}
```

#### `memberpress_ai_render_response`
Filter the HTML rendering of a response.

**Parameters:**
- `$html` (string) - The HTML representation of the response
- `$response` (string) - The raw response
- `$query` (string) - The original query

**Usage:**
```php
add_filter('memberpress_ai_render_response', 'my_render_filter', 10, 3);
function my_render_filter($html, $response, $query) {
    // Modify the rendered HTML
    $html = '<div class="custom-response-wrapper">' . $html . '</div>';
    return $html;
}
```

### Data Access Filters

#### `memberpress_ai_data_sources`
Filter the available data sources.

**Parameters:**
- `$data_sources` (array) - The registered data sources

**Usage:**
```php
add_filter('memberpress_ai_data_sources', 'my_data_sources_filter');
function my_data_sources_filter($data_sources) {
    // Add custom data source
    $data_sources['custom_data'] = [
        'class' => 'My_Custom_Data_Source',
        'priority' => 50,
    ];
    return $data_sources;
}
```

#### `memberpress_ai_can_access_data`
Filter whether a user can access specific data.

**Parameters:**
- `$can_access` (bool) - Whether access is allowed
- `$data_type` (string) - The type of data
- `$user_id` (int) - The user ID requesting access

**Usage:**
```php
add_filter('memberpress_ai_can_access_data', 'my_access_filter', 10, 3);
function my_access_filter($can_access, $data_type, $user_id) {
    // Custom access logic
    if ($data_type === 'revenue_data' && !user_can($user_id, 'manage_options')) {
        return false;
    }
    return $can_access;
}
```

### Caching Filters

#### `memberpress_ai_cache_expiration`
Filter the cache expiration time.

**Parameters:**
- `$expiration` (int) - Cache expiration in seconds
- `$query_type` (string) - The type of query being cached

**Usage:**
```php
add_filter('memberpress_ai_cache_expiration', 'my_cache_expiration_filter', 10, 2);
function my_cache_expiration_filter($expiration, $query_type) {
    // Modify expiration time
    if ($query_type === 'membership_stats') {
        return 3600; // 1 hour for membership stats
    }
    return $expiration;
}
```

#### `memberpress_ai_should_cache_response`
Filter whether a response should be cached.

**Parameters:**
- `$should_cache` (bool) - Whether to cache the response
- `$query` (string) - The original query
- `$response` (string) - The response to cache

**Usage:**
```php
add_filter('memberpress_ai_should_cache_response', 'my_should_cache_filter', 10, 3);
function my_should_cache_filter($should_cache, $query, $response) {
    // Custom caching logic
    if (strpos($query, 'password') !== false || strlen($response) < 20) {
        return false; // Don't cache password queries or very short responses
    }
    return $should_cache;
}
```

## Hook Usage Examples

### Example 1: Adding Custom Data to AI Context

This example demonstrates how to add custom data to the AI context to improve responses:

```php
/**
 * Add custom membership statistics to AI context
 */
add_filter('memberpress_ai_query_context', 'add_custom_stats_to_context', 10, 3);
function add_custom_stats_to_context($context, $query, $user_id) {
    // Only add stats if query seems to be asking about performance
    if (
        stripos($query, 'performance') !== false || 
        stripos($query, 'statistics') !== false || 
        stripos($query, 'growth') !== false
    ) {
        // Get custom stats from your tracking system
        $custom_stats = get_option('my_membership_stats', []);
        
        if (!empty($custom_stats)) {
            $context['custom_stats'] = [
                'growth_rate' => $custom_stats['growth_rate'] ?? 0,
                'engagement_score' => $custom_stats['engagement_score'] ?? 0,
                'member_satisfaction' => $custom_stats['satisfaction'] ?? 0,
            ];
        }
    }
    
    return $context;
}
```

### Example 2: Customizing the Chat Interface

This example shows how to modify the chat interface appearance:

```php
/**
 * Customize the AI Assistant interface based on membership level
 */
add_filter('memberpress_ai_interface_settings', 'customize_interface_by_membership');
function customize_interface_by_membership($settings) {
    // Get current user
    $current_user_id = get_current_user_id();
    
    // If not logged in, return default settings
    if (!$current_user_id) {
        return $settings;
    }
    
    // Get MemberPress membership
    $active_memberships = memberpress_get_user_active_memberships($current_user_id);
    
    // Customize based on membership level
    if (!empty($active_memberships)) {
        foreach ($active_memberships as $membership) {
            if (strpos($membership->name, 'Premium') !== false) {
                // Premium styling
                $settings['theme'] = 'premium';
                $settings['icon'] = 'premium-assistant-icon';
                $settings['welcome_message'] = 'Welcome to your Premium AI Assistant!';
                break;
            } else if (strpos($membership->name, 'Basic') !== false) {
                // Basic styling
                $settings['theme'] = 'basic';
                $settings['welcome_message'] = 'Welcome to your Basic AI Assistant!';
            }
        }
    }
    
    return $settings;
}
```

### Example 3: Logging AI Activity

This example demonstrates how to log AI activity for analysis:

```php
/**
 * Log all AI queries and responses for later analysis
 */
add_action('memberpress_ai_after_response_generation', 'log_ai_activity', 10, 3);
function log_ai_activity($response, $query, $service) {
    global $wpdb;
    
    // Create a custom table for logging if it doesn't exist
    $table_name = $wpdb->prefix . 'ai_assistant_logs';
    
    // Ensure data is properly sanitized
    $data = [
        'user_id' => get_current_user_id(),
        'query' => sanitize_text_field($query),
        'response_length' => strlen($response),
        'service' => sanitize_text_field($service),
        'timestamp' => current_time('mysql'),
        'page' => isset($_SERVER['HTTP_REFERER']) ? esc_url_raw($_SERVER['HTTP_REFERER']) : '',
    ];
    
    // Insert into database
    $wpdb->insert($table_name, $data);
    
    // Optionally trigger custom event for analytics
    do_action('my_custom_ai_log', $data);
}
```

## Hook Execution Order

Understanding the order in which hooks execute is important for proper integration. Here's the typical execution sequence for a query:

1. `memberpress_ai_init` (Action)
2. `memberpress_ai_loaded` (Action)
3. `memberpress_ai_verify_request` (Action)
4. `memberpress_ai_process_query` (Filter)
5. `memberpress_ai_before_process_query` (Action)
6. `memberpress_ai_query_context` (Filter)
7. `memberpress_ai_can_access_data` (Filter) - Multiple times as needed
8. `memberpress_ai_before_data_access` (Action) - Multiple times as needed
9. `memberpress_ai_after_data_access` (Action) - Multiple times as needed
10. `memberpress_ai_after_process_query` (Action)
11. `memberpress_ai_modify_prompt` (Filter)
12. `memberpress_ai_before_request_response` (Action)
13. `memberpress_ai_after_response_generation` (Action)
14. `memberpress_ai_modify_response` (Filter)
15. `memberpress_ai_should_cache_response` (Filter)
16. `memberpress_ai_before_cache_response` (Action) - If caching
17. `memberpress_ai_after_cache_response` (Action) - If caching
18. `memberpress_ai_render_response` (Filter)
19. `memberpress_ai_before_render_chat_interface` (Action)
20. `memberpress_ai_interface_settings` (Filter)
21. `memberpress_ai_after_render_chat_interface` (Action)

## Deprecated Hooks

The following hooks are deprecated and will be removed in future versions:

| Hook | Deprecated Version | Replacement | Notes |
|------|-------------------|-------------|-------|
| `memberpress_ai_format_query` | 1.2.0 | `memberpress_ai_process_query` | Functionality expanded in new hook |
| `mepr_ai_get_context` | 1.0.5 | `memberpress_ai_query_context` | Renamed for consistency |
| `mepr_ai_service_result` | 1.1.0 | `memberpress_ai_modify_response` | Renamed for clarity |

For backwards compatibility, deprecated hooks are still supported but will trigger a deprecation notice in debug mode.

## Hook Best Practices

When working with MemberPress AI Assistant hooks, follow these best practices:

1. **Check Hook Availability**:
   ```php
   if (function_exists('is_plugin_active') && is_plugin_active('memberpress-ai-assistant/memberpress-ai-assistant.php')) {
       // Add your hook implementations here
   }
   ```

2. **Use Appropriate Priority**:
   - Default WordPress priority is 10
   - Use lower numbers (1-9) to run before default hooks
   - Use higher numbers (11+) to run after default hooks
   
3. **Validate Data**:
   - Always validate and sanitize data coming from hooks
   - Don't assume data structure will remain the same
   
4. **Handle Errors Gracefully**:
   ```php
   add_filter('memberpress_ai_modify_response', 'my_response_modifier', 10, 3);
   function my_response_modifier($response, $query, $service) {
       try {
           // Your modification code
           $modified_response = process_response($response);
           return $modified_response;
       } catch (Exception $e) {
           // Log error and return original to prevent breaking
           error_log('AI response modification error: ' . $e->getMessage());
           return $response;
       }
   }
   ```

5. **Respect Performance**:
   - Keep hook callbacks efficient
   - Avoid expensive operations in frequently called hooks
   - Consider caching results of resource-intensive operations

---

*This reference is regularly updated as new hooks are added or modified. Last updated: April 6, 2025.*