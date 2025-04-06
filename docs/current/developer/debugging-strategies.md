# Debugging Strategies Documentation

**Version:** 1.0.0  
**Last Updated:** 2025-04-06  
**Status:** ✅ Stable  
**Owner:** Developer Documentation Team

## Overview

This document provides comprehensive strategies for debugging the MemberPress AI Assistant plugin. It covers approaches to identifying, isolating, and resolving issues across different components of the system, with a special focus on AI-specific debugging challenges.

## Table of Contents

1. [Development Environment Setup](#development-environment-setup)
2. [Logging System](#logging-system)
3. [Error Handling System](#error-handling-system)
4. [Debugging WordPress Hooks](#debugging-wordpress-hooks)
5. [API Debugging](#api-debugging)
6. [UI Debugging](#ui-debugging)
7. [Performance Debugging](#performance-debugging)
8. [Common Issues and Solutions](#common-issues-and-solutions)
9. [Advanced Debugging Techniques](#advanced-debugging-techniques)
10. [Debugging Tools Reference](#debugging-tools-reference)

## Development Environment Setup

### Enabling Debug Mode

Add these lines to your `wp-config.php` file:

```php
// Enable WordPress debugging
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', true);

// Enable script debugging
define('SCRIPT_DEBUG', true);

// MemberPress AI Assistant specific debugging
define('MPAI_DEBUG', true);
define('MPAI_DEBUG_LOG', true);
```

### XDebug Configuration

For interactive debugging with XDebug:

1. Install XDebug for your PHP version
2. Add to your `php.ini`:

```ini
[XDebug]
xdebug.mode = debug
xdebug.start_with_request = yes
xdebug.client_port = 9003
xdebug.client_host = 127.0.0.1
xdebug.idekey = VSCODE  # Or your IDE key
xdebug.log = /path/to/xdebug.log
```

### Local Development Tools

Install these tools for more effective debugging:

```bash
# Install Query Monitor plugin
wp plugin install query-monitor --activate

# Install WP-CLI debugging tools
wp package install wp-cli/doctor-command

# Install our debugging tools
composer require --dev mpai/debug-toolkit
```

## Logging System

### Using the MPAI Logger

The plugin includes a dedicated logging system:

```php
// Import the logger
use MPAI\Logger;

// Basic logging
Logger::info('Processing request', ['user_id' => 123]);
Logger::error('API request failed', ['error' => $error]);

// Context-specific logs
Logger::api('Response received', ['response' => $response]);
Logger::tool('Tool executed', ['tool' => 'example_tool', 'params' => $params]);
Logger::chat('Chat processed', ['request' => $request, 'response' => $response]);

// Log levels
Logger::debug('Detailed debugging info');  // Only in debug mode
Logger::info('General information');
Logger::warning('Warning conditions');
Logger::error('Error conditions');
Logger::critical('Critical conditions');
```

### Log File Locations

Logs are stored in these locations:

- **General logs**: `wp-content/uploads/mpai-logs/mpai-general.log`
- **API logs**: `wp-content/uploads/mpai-logs/mpai-api.log`
- **Error logs**: `wp-content/uploads/mpai-logs/mpai-error.log`

### Log Analysis Techniques

For analyzing log files:

```bash
# Find all errors
grep -i "error" wp-content/uploads/mpai-logs/mpai-general.log

# Track a user's activity
grep -i "user_id.*123" wp-content/uploads/mpai-logs/mpai-general.log

# Check API response times
grep -i "api response time" wp-content/uploads/mpai-logs/mpai-api.log | \
awk '{print $NF}' | sort -n | \
awk '{sum+=$1; count+=1} END {print "Min: " $1 " Max: " $count " Avg: " sum/count}'

# Monitor specific tool usage
grep -i "tool executed.*example_tool" wp-content/uploads/mpai-logs/mpai-general.log
```

### Enabling Debug Level Logs

Debug logs are disabled by default. Enable them:

```php
// In your wp-config.php
define('MPAI_DEBUG_LEVEL', 'debug');  // Options: debug, info, warning, error, critical

// Or dynamically:
MPAI_Logger::set_level('debug');
```

## Error Handling System

### Understanding the Error Recovery System

The plugin uses a sophisticated error recovery system:

```php
// Import the error recovery system
use MPAI\Error_Recovery;

// Basic usage
try {
    // Some operation that might fail
    $result = $this->risky_operation();
} catch (Exception $e) {
    // Log the error
    Logger::error('Operation failed', ['error' => $e->getMessage()]);
    
    // Return a friendly WP_Error
    return new WP_Error('operation_failed', 'The operation failed: ' . $e->getMessage());
}

// Using the retry mechanism
$result = Error_Recovery::get_instance()->execute_with_retry(
    function() {
        // Operation that might fail transiently
        return $this->api_request();
    },
    [
        'max_retries' => 3,
        'base_delay' => 1000,  // milliseconds
        'jitter' => 0.25,      // random factor
    ]
);

// Using with fallback
$result = Error_Recovery::get_instance()->execute_with_fallback(
    function() {
        // Primary operation
        return $this->primary_operation();
    },
    function() {
        // Fallback operation
        return $this->fallback_operation();
    }
);
```

### Error Catalog System

The plugin maintains an error catalog for standardized error handling:

```php
// Import the error catalog
use MPAI\Error_Catalog;

// Get a standardized error
$error = Error_Catalog::get_error('api_connection_failed', [
    'api' => 'openai',
    'url' => $url,
    'status' => $status_code
]);

// Check if an error is a known error
if (Error_Catalog::is_known_error($error_code)) {
    // Handle known error
} else {
    // Handle unknown error
}

// Get user-friendly error message
$user_message = Error_Catalog::get_user_message($error);
```

### Debugging Error Handling

To debug the error handling system:

```php
// Enable debug mode for error recovery
define('MPAI_ERROR_RECOVERY_DEBUG', true);

// Track specific error types
add_action('mpai_error', function($error_code, $error_message, $context) {
    if ($error_code === 'api_connection_failed') {
        error_log('API Connection Failure: ' . $error_message);
        error_log('Context: ' . print_r($context, true));
    }
}, 10, 3);

// Simulate errors for testing
if (defined('MPAI_SIMULATE_ERRORS') && MPAI_SIMULATE_ERRORS) {
    add_filter('mpai_pre_api_request', function($request) {
        // Return a simulated error for testing
        return new WP_Error('simulated_error', 'This is a simulated error');
    });
}
```

## Debugging WordPress Hooks

### Hook Debugging

The plugin uses many WordPress hooks. Debug them with:

```php
// Debug a specific action
add_action('mpai_before_process_request', function($request_data, $context) {
    error_log('mpai_before_process_request called');
    error_log('Request: ' . print_r($request_data, true));
    error_log('Context: ' . print_r($context, true));
}, 999, 2);  // High priority to run after other callbacks

// Debug a specific filter
add_filter('mpai_chat_context', function($context, $user_id, $request_type) {
    error_log('mpai_chat_context filter applied');
    error_log('Before: ' . print_r($context, true));
    
    // Your debugging logic here
    
    error_log('After: ' . print_r($context, true));
    return $context;
}, 999, 3);

// List all callbacks for a hook
function mpai_debug_list_hook_callbacks($hook_name) {
    global $wp_filter;
    
    if (isset($wp_filter[$hook_name])) {
        echo "<h3>Callbacks for: $hook_name</h3>";
        echo "<pre>";
        print_r($wp_filter[$hook_name]);
        echo "</pre>";
    } else {
        echo "<p>No callbacks found for: $hook_name</p>";
    }
}
```

### Hook Inspector Tool

The plugin includes a hook inspection tool:

```php
// Import the hook inspector
use MPAI\Debug\Hook_Inspector;

// Start tracking a hook
Hook_Inspector::track_hook('mpai_process_chat_request');

// Get hook execution results
$results = Hook_Inspector::get_results('mpai_process_chat_request');
```

### Debug Hook List

Important hooks to monitor when debugging:

| Hook Name | Type | Description |
|-----------|------|-------------|
| `mpai_before_process_request` | Action | Called before processing a chat request |
| `mpai_after_process_request` | Action | Called after processing a chat request |
| `mpai_chat_context` | Filter | Modifies the context data for chat requests |
| `mpai_system_message` | Filter | Modifies the system message for the AI |
| `mpai_generate_completion` | Filter | Allows intercepting the API request |
| `mpai_process_response` | Filter | Modifies the AI's response before returning |
| `mpai_tool_registry` | Filter | Modifies the available tools |
| `mpai_error` | Action | Called when an error occurs |

## API Debugging

### Debugging API Requests

To debug AI API requests:

```php
// Enable API request/response logging
define('MPAI_LOG_API_REQUESTS', true);

// Inspect API requests before they're sent
add_filter('mpai_pre_api_request', function($request_data, $provider) {
    error_log('API Request to ' . $provider . ':');
    error_log(json_encode($request_data, JSON_PRETTY_PRINT));
    return $request_data;
}, 10, 2);

// Inspect API responses
add_filter('mpai_post_api_response', function($response, $provider) {
    error_log('API Response from ' . $provider . ':');
    
    if (is_wp_error($response)) {
        error_log('Error: ' . $response->get_error_message());
    } else {
        error_log(json_encode($response, JSON_PRETTY_PRINT));
    }
    
    return $response;
}, 10, 2);
```

### API Mock System

For testing without real API calls:

```php
// Enable the mock API system
define('MPAI_USE_MOCK_API', true);

// Register mock API responses
add_filter('mpai_mock_api_response', function($response, $request, $provider) {
    // Check for specific request patterns
    if (strpos($request['prompt'], 'test question') !== false) {
        return [
            'response' => 'This is a mock response for the test question',
            'model' => 'mock-gpt-4',
            'usage' => [
                'prompt_tokens' => 10,
                'completion_tokens' => 8,
                'total_tokens' => 18
            ]
        ];
    }
    
    // Default mock response
    return [
        'response' => 'This is a generic mock response',
        'model' => 'mock-gpt-4',
        'usage' => [
            'prompt_tokens' => 5,
            'completion_tokens' => 4,
            'total_tokens' => 9
        ]
    ];
}, 10, 3);
```

### API Performance Monitoring

Monitor API performance:

```php
// Track API request timing
add_action('mpai_api_request_complete', function($provider, $duration_ms, $tokens) {
    error_log(sprintf(
        'API Request to %s: %.2f ms, %d tokens',
        $provider,
        $duration_ms,
        $tokens
    ));
    
    // Store metrics for analysis
    $metrics = get_option('mpai_api_metrics', []);
    $metrics[] = [
        'timestamp' => time(),
        'provider' => $provider,
        'duration_ms' => $duration_ms,
        'tokens' => $tokens
    ];
    
    // Keep only the last 100 records
    if (count($metrics) > 100) {
        $metrics = array_slice($metrics, -100);
    }
    
    update_option('mpai_api_metrics', $metrics);
}, 10, 3);

// Calculate API metrics
function mpai_get_api_metrics() {
    $metrics = get_option('mpai_api_metrics', []);
    
    if (empty($metrics)) {
        return [];
    }
    
    $providers = [];
    foreach ($metrics as $record) {
        $provider = $record['provider'];
        
        if (!isset($providers[$provider])) {
            $providers[$provider] = [
                'count' => 0,
                'total_duration' => 0,
                'total_tokens' => 0,
                'min_duration' => PHP_INT_MAX,
                'max_duration' => 0
            ];
        }
        
        $providers[$provider]['count']++;
        $providers[$provider]['total_duration'] += $record['duration_ms'];
        $providers[$provider]['total_tokens'] += $record['tokens'];
        $providers[$provider]['min_duration'] = min($providers[$provider]['min_duration'], $record['duration_ms']);
        $providers[$provider]['max_duration'] = max($providers[$provider]['max_duration'], $record['duration_ms']);
    }
    
    // Calculate averages
    foreach ($providers as &$data) {
        $data['avg_duration'] = $data['total_duration'] / $data['count'];
        $data['avg_tokens'] = $data['total_tokens'] / $data['count'];
    }
    
    return $providers;
}
```

## UI Debugging

### JavaScript Console Logging

The plugin includes a structured console logging system:

```javascript
// Basic logging
MPAI.debug.log('General message');
MPAI.debug.info('Information', {context: 'some data'});
MPAI.debug.warn('Warning message');
MPAI.debug.error('Error occurred', {error: errorObj});

// Component-specific logging
MPAI.debug.component('ChatInterface', 'Initializing chat');
MPAI.debug.component('API', 'Sending request', {endpoint: '/chat/completions'});

// Group related logs
MPAI.debug.group('Processing chat request');
MPAI.debug.log('Step 1: Validating input');
MPAI.debug.log('Step 2: Building context');
MPAI.debug.log('Step 3: Sending to API');
MPAI.debug.groupEnd();

// Timing operations
MPAI.debug.time('API Request');
// ... operation ...
MPAI.debug.timeEnd('API Request'); // Outputs: "API Request: 1200ms"
```

### DOM Debugging

For debugging UI issues:

```javascript
// Log DOM structure
MPAI.debug.dom('#mpai-chat-container');

// Watch for DOM changes
MPAI.debug.watchDOM('#mpai-chat-responses', {
    childList: true,
    subtree: true
});

// Highlight element
MPAI.debug.highlight('#mpai-chat-input');
```

### State Debugging

For debugging application state:

```javascript
// Log current state
MPAI.debug.state();

// Watch for state changes
MPAI.debug.watchState('chatHistory');

// Simulate state
MPAI.debug.setState({
    chatHistory: [
        {role: 'user', content: 'Test message'},
        {role: 'assistant', content: 'Test response'}
    ]
});
```

### Network Request Debugging

Monitor AJAX requests:

```javascript
// Log all AJAX requests
MPAI.debug.network(true);

// Mock AJAX response
MPAI.debug.mockAjax('process_chat', {
    success: true,
    data: {
        response: 'This is a test response',
        source: 'mock'
    }
});
```

## Performance Debugging

### Identifying Performance Issues

Techniques for finding performance bottlenecks:

```php
// Add performance logging points
function mpai_performance_log($label, $start_time = null) {
    static $times = array();
    
    if ($start_time === null) {
        // Start timing
        $times[$label] = microtime(true);
        error_log("MPAI Performance: Starting $label");
    } else {
        // End timing
        $duration = microtime(true) - $start_time;
        $memory = memory_get_usage() / 1024 / 1024; // MB
        error_log(sprintf(
            "MPAI Performance: %s completed in %.4f seconds, Memory: %.2f MB",
            $label,
            $duration,
            $memory
        ));
        
        if ($duration > 1.0) {
            // Log warning for slow operations
            error_log("MPAI Performance WARNING: $label took more than 1 second!");
        }
        
        return $duration;
    }
    
    return $times[$label] ?? null;
}

// Usage
$start = mpai_performance_log('process_chat_request');
// ... operation ...
$duration = mpai_performance_log('process_chat_request', $start);

// Check memory usage
function mpai_memory_usage() {
    $mem_usage = memory_get_usage(true);
    
    if ($mem_usage < 1024) {
        $mem_usage .= ' bytes';
    } elseif ($mem_usage < 1048576) {
        $mem_usage = round($mem_usage / 1024, 2) . ' KB';
    } else {
        $mem_usage = round($mem_usage / 1048576, 2) . ' MB';
    }
    
    error_log("MPAI Memory Usage: $mem_usage");
}
```

### Query Debugging

For database query performance:

```php
// Monitor WordPress database queries
add_filter('query', function($query) {
    if (strpos($query, 'mpai_') !== false) {
        error_log("MPAI DB Query: $query");
    }
    return $query;
});

// Track slow queries
add_action('query', function($query) {
    static $start_time;
    
    if (strpos($query, 'mpai_') !== false) {
        if ($start_time === null) {
            $start_time = microtime(true);
        } else {
            $duration = microtime(true) - $start_time;
            if ($duration > 0.1) {
                error_log("MPAI Slow Query ($duration sec): $query");
            }
            $start_time = null;
        }
    }
});
```

### Cache Debugging

For cache-related issues:

```php
// Debug system cache
add_action('mpai_cache_set', function($key, $group, $data, $expiration) {
    error_log("MPAI Cache Set: $key ($group), expires in $expiration seconds");
}, 10, 4);

add_action('mpai_cache_get', function($key, $group, $found) {
    $status = $found ? 'HIT' : 'MISS';
    error_log("MPAI Cache Get: $key ($group) - $status");
}, 10, 3);

// Force cache flush for debugging
function mpai_debug_flush_cache() {
    MPAI_System_Cache::get_instance()->flush();
    error_log("MPAI Cache: Forced flush of all caches");
}

// Cache analyzer - find cache usage patterns
function mpai_analyze_cache_keys() {
    global $wpdb;
    
    $transients = $wpdb->get_results(
        "SELECT option_name, option_value 
         FROM $wpdb->options 
         WHERE option_name LIKE '_transient_mpai_%'
         ORDER BY option_name"
    );
    
    $stats = array(
        'total' => count($transients),
        'by_type' => array(),
        'sizes' => array()
    );
    
    foreach ($transients as $transient) {
        $key = str_replace('_transient_mpai_', '', $transient->option_name);
        
        // Identify cache type
        $type = 'unknown';
        if (strpos($key, 'context_') === 0) $type = 'context';
        if (strpos($key, 'api_') === 0) $type = 'api';
        if (strpos($key, 'system_') === 0) $type = 'system';
        
        if (!isset($stats['by_type'][$type])) {
            $stats['by_type'][$type] = 0;
        }
        $stats['by_type'][$type]++;
        
        // Calculate size
        $size = strlen($transient->option_value);
        $stats['sizes'][$key] = round($size / 1024, 2) . ' KB';
    }
    
    return $stats;
}
```

## Common Issues and Solutions

### API Connection Issues

For debugging API connection problems:

```php
// Check API connectivity
function mpai_test_api_connection($provider = 'openai') {
    $api_router = MPAI_API_Router::get_instance();
    
    // Simple test request
    $result = $api_router->test_connection($provider);
    
    if (is_wp_error($result)) {
        error_log("MPAI API Connection Test FAILED for $provider: " . $result->get_error_message());
        
        // Check common issues
        if (strpos($result->get_error_message(), 'cURL error 28') !== false) {
            error_log('MPAI API Issue: Request timeout - check network connectivity');
        } elseif (strpos($result->get_error_message(), 'SSL certificate') !== false) {
            error_log('MPAI API Issue: SSL certificate problem - check server SSL configuration');
        } elseif (strpos($result->get_error_message(), 'Could not resolve host') !== false) {
            error_log('MPAI API Issue: DNS resolution problem - check DNS configuration');
        } elseif (strpos($result->get_error_message(), 'Unauthorized') !== false) {
            error_log('MPAI API Issue: Authentication failed - check API key');
        }
        
        return false;
    } else {
        error_log("MPAI API Connection Test SUCCESSFUL for $provider");
        return true;
    }
}

// Test API key validity
function mpai_verify_api_key($provider = 'openai') {
    switch ($provider) {
        case 'openai':
            $api_key = get_option('mpai_openai_api_key', '');
            if (empty($api_key)) {
                return new WP_Error('empty_api_key', 'OpenAI API key is not configured');
            }
            
            // Check key format
            if (!preg_match('/^sk-[a-zA-Z0-9]{32,}$/', $api_key)) {
                return new WP_Error('invalid_api_key_format', 'OpenAI API key has invalid format');
            }
            break;
            
        case 'anthropic':
            $api_key = get_option('mpai_anthropic_api_key', '');
            if (empty($api_key)) {
                return new WP_Error('empty_api_key', 'Anthropic API key is not configured');
            }
            
            // Check key format
            if (!preg_match('/^sk-ant-[a-zA-Z0-9]{24,}$/', $api_key)) {
                return new WP_Error('invalid_api_key_format', 'Anthropic API key has invalid format');
            }
            break;
    }
    
    // Test API with the key
    return mpai_test_api_connection($provider);
}
```

### Context Management Issues

For debugging context issues:

```php
// Analyze context size
function mpai_analyze_context_size($context) {
    $json = json_encode($context);
    $size = strlen($json);
    $token_estimate = $size / 4; // Rough estimate: 4 chars per token
    
    error_log(sprintf(
        "MPAI Context Analysis: Size: %.2f KB, Est. Tokens: %d",
        $size / 1024,
        $token_estimate
    ));
    
    // Check if approaching token limits
    if ($token_estimate > 4000) {
        error_log("MPAI Context WARNING: Approaching token limit, context may be too large");
    }
    
    // Analyze context sections
    foreach ($context as $key => $value) {
        $section_json = json_encode($value);
        $section_size = strlen($section_json);
        $section_tokens = $section_size / 4;
        
        error_log(sprintf(
            "MPAI Context Section '%s': %.2f KB, Est. Tokens: %d",
            $key,
            $section_size / 1024,
            $section_tokens
        ));
    }
}

// Debug context pruning
add_filter('mpai_prune_context', function($context, $max_tokens) {
    error_log("MPAI Context Pruning: Max tokens: $max_tokens");
    
    $before = json_encode($context);
    $before_size = strlen($before);
    $before_tokens = $before_size / 4;
    
    error_log(sprintf(
        "MPAI Context Before Pruning: %.2f KB, Est. Tokens: %d",
        $before_size / 1024,
        $before_tokens
    ));
    
    // Let the normal pruning happen
    $pruned = $context; // This would normally be pruned
    
    $after = json_encode($pruned);
    $after_size = strlen($after);
    $after_tokens = $after_size / 4;
    
    error_log(sprintf(
        "MPAI Context After Pruning: %.2f KB, Est. Tokens: %d (Reduced by %.2f%%)",
        $after_size / 1024,
        $after_tokens,
        ($before_size - $after_size) / $before_size * 100
    ));
    
    return $pruned;
}, 999, 2);
```

### Tool Execution Issues

For debugging tool issues:

```php
// Debug tool execution
add_action('mpai_before_tool_execution', function($tool_name, $parameters) {
    error_log("MPAI Tool Execution: $tool_name");
    error_log("Parameters: " . json_encode($parameters, JSON_PRETTY_PRINT));
}, 10, 2);

add_action('mpai_after_tool_execution', function($tool_name, $parameters, $result) {
    error_log("MPAI Tool Result: $tool_name");
    
    if (is_wp_error($result)) {
        error_log("Tool Error: " . $result->get_error_message());
    } else {
        error_log("Result: " . json_encode($result, JSON_PRETTY_PRINT));
    }
}, 10, 3);

// Test a specific tool
function mpai_test_tool($tool_name, $parameters = array()) {
    $tool_registry = MPAI_Tool_Registry::get_instance();
    $tool = $tool_registry->get_tool($tool_name);
    
    if (!$tool) {
        return new WP_Error('tool_not_found', "Tool '$tool_name' not found");
    }
    
    error_log("MPAI Tool Test: Testing '$tool_name'");
    error_log("Parameters: " . json_encode($parameters, JSON_PRETTY_PRINT));
    
    // Execute the tool
    $start_time = microtime(true);
    $result = $tool->execute($parameters);
    $execution_time = microtime(true) - $start_time;
    
    error_log(sprintf("MPAI Tool Test: Execution time: %.4f seconds", $execution_time));
    
    if (is_wp_error($result)) {
        error_log("MPAI Tool Test ERROR: " . $result->get_error_message());
    } else {
        error_log("MPAI Tool Test SUCCESS: " . json_encode($result, JSON_PRETTY_PRINT));
    }
    
    return $result;
}
```

## Advanced Debugging Techniques

### State Diffing

For tracking system state changes:

```php
class MPAI_State_Differ {
    private static $snapshots = array();
    
    /**
     * Take a state snapshot
     * 
     * @param string $label Label for the snapshot
     * @return void
     */
    public static function snapshot($label) {
        self::$snapshots[$label] = self::get_current_state();
        error_log("MPAI State Snapshot: '$label' saved");
    }
    
    /**
     * Compare two state snapshots
     * 
     * @param string $label1 First snapshot label
     * @param string $label2 Second snapshot label
     * @return array State differences
     */
    public static function diff($label1, $label2) {
        if (!isset(self::$snapshots[$label1]) || !isset(self::$snapshots[$label2])) {
            error_log("MPAI State Diff Error: Snapshots not found");
            return false;
        }
        
        $state1 = self::$snapshots[$label1];
        $state2 = self::$snapshots[$label2];
        
        $diff = self::compare_states($state1, $state2);
        
        error_log("MPAI State Diff: '$label1' vs '$label2'");
        error_log(print_r($diff, true));
        
        return $diff;
    }
    
    /**
     * Get the current plugin state
     * 
     * @return array Current state
     */
    private static function get_current_state() {
        return array(
            'options' => self::get_plugin_options(),
            'cache' => self::get_cache_state(),
            'hooks' => self::get_hooks_state(),
            'memory' => memory_get_usage(),
            'timestamp' => microtime(true)
        );
    }
    
    /**
     * Get plugin options
     * 
     * @return array Plugin options
     */
    private static function get_plugin_options() {
        global $wpdb;
        
        $options = array();
        $results = $wpdb->get_results("
            SELECT option_name, option_value 
            FROM $wpdb->options 
            WHERE option_name LIKE 'mpai_%'
        ");
        
        foreach ($results as $option) {
            $options[$option->option_name] = $option->option_value;
        }
        
        return $options;
    }
    
    /**
     * Get cache state
     * 
     * @return array Cache state
     */
    private static function get_cache_state() {
        global $wpdb;
        
        $cache = array();
        $results = $wpdb->get_results("
            SELECT option_name 
            FROM $wpdb->options 
            WHERE option_name LIKE '_transient_mpai_%'
        ");
        
        foreach ($results as $transient) {
            $key = str_replace('_transient_mpai_', '', $transient->option_name);
            $cache[$key] = true;
        }
        
        return $cache;
    }
    
    /**
     * Get hooks state
     * 
     * @return array Hooks state
     */
    private static function get_hooks_state() {
        global $wp_filter;
        
        $hooks = array();
        
        foreach ($wp_filter as $hook_name => $hook_obj) {
            if (strpos($hook_name, 'mpai_') === 0) {
                $hooks[$hook_name] = count($hook_obj->callbacks);
            }
        }
        
        return $hooks;
    }
    
    /**
     * Compare two states
     * 
     * @param array $state1 First state
     * @param array $state2 Second state
     * @return array State differences
     */
    private static function compare_states($state1, $state2) {
        $diff = array();
        
        // Options diff
        $options_diff = array();
        foreach ($state2['options'] as $key => $value) {
            if (!isset($state1['options'][$key])) {
                $options_diff[$key] = array('added' => $value);
            } elseif ($state1['options'][$key] !== $value) {
                $options_diff[$key] = array(
                    'before' => $state1['options'][$key],
                    'after' => $value
                );
            }
        }
        
        foreach ($state1['options'] as $key => $value) {
            if (!isset($state2['options'][$key])) {
                $options_diff[$key] = array('removed' => $value);
            }
        }
        
        if (!empty($options_diff)) {
            $diff['options'] = $options_diff;
        }
        
        // Cache diff
        $cache_diff = array();
        foreach ($state2['cache'] as $key => $value) {
            if (!isset($state1['cache'][$key])) {
                $cache_diff[$key] = 'added';
            }
        }
        
        foreach ($state1['cache'] as $key => $value) {
            if (!isset($state2['cache'][$key])) {
                $cache_diff[$key] = 'removed';
            }
        }
        
        if (!empty($cache_diff)) {
            $diff['cache'] = $cache_diff;
        }
        
        // Hooks diff
        $hooks_diff = array();
        foreach ($state2['hooks'] as $hook => $count) {
            if (!isset($state1['hooks'][$hook])) {
                $hooks_diff[$hook] = array('added' => $count);
            } elseif ($state1['hooks'][$hook] !== $count) {
                $hooks_diff[$hook] = array(
                    'before' => $state1['hooks'][$hook],
                    'after' => $count
                );
            }
        }
        
        foreach ($state1['hooks'] as $hook => $count) {
            if (!isset($state2['hooks'][$hook])) {
                $hooks_diff[$hook] = array('removed' => $count);
            }
        }
        
        if (!empty($hooks_diff)) {
            $diff['hooks'] = $hooks_diff;
        }
        
        // Memory diff
        $memory_diff = $state2['memory'] - $state1['memory'];
        $diff['memory'] = sprintf(
            "%.2f KB (%s%.2f%%)",
            $memory_diff / 1024,
            $memory_diff > 0 ? '+' : '',
            ($memory_diff / $state1['memory']) * 100
        );
        
        // Time diff
        $diff['time'] = sprintf(
            "%.4f seconds",
            $state2['timestamp'] - $state1['timestamp']
        );
        
        return $diff;
    }
}

// Usage
MPAI_State_Differ::snapshot('before_request');
// ... operation ...
MPAI_State_Differ::snapshot('after_request');
MPAI_State_Differ::diff('before_request', 'after_request');
```

### Request/Response Tracing

For tracking complete request/response cycles:

```php
class MPAI_Request_Tracer {
    private static $trace_id = null;
    private static $trace_data = array();
    
    /**
     * Start a new trace
     * 
     * @param string $request User request
     * @return string Trace ID
     */
    public static function start_trace($request) {
        self::$trace_id = uniqid('trace_');
        self::$trace_data = array(
            'id' => self::$trace_id,
            'request' => $request,
            'start_time' => microtime(true),
            'events' => array(),
            'api_calls' => array(),
            'tool_executions' => array(),
            'response' => null,
            'end_time' => null,
            'duration' => null
        );
        
        self::add_event('trace_started', 'Trace started');
        
        error_log("MPAI Trace Started: " . self::$trace_id);
        
        return self::$trace_id;
    }
    
    /**
     * Add an event to the trace
     * 
     * @param string $type Event type
     * @param string $message Event message
     * @param array $data Event data
     */
    public static function add_event($type, $message, $data = array()) {
        if (self::$trace_id === null) {
            return;
        }
        
        $event = array(
            'type' => $type,
            'message' => $message,
            'data' => $data,
            'timestamp' => microtime(true),
            'relative_time' => microtime(true) - self::$trace_data['start_time']
        );
        
        self::$trace_data['events'][] = $event;
    }
    
    /**
     * Record an API call
     * 
     * @param string $provider API provider
     * @param array $request Request data
     * @param mixed $response Response data
     * @param float $duration Duration in seconds
     */
    public static function record_api_call($provider, $request, $response, $duration) {
        if (self::$trace_id === null) {
            return;
        }
        
        $api_call = array(
            'provider' => $provider,
            'request' => $request,
            'response' => $response,
            'duration' => $duration,
            'timestamp' => microtime(true),
            'relative_time' => microtime(true) - self::$trace_data['start_time']
        );
        
        self::$trace_data['api_calls'][] = $api_call;
        self::add_event('api_call', "API call to $provider");
    }
    
    /**
     * Record a tool execution
     * 
     * @param string $tool_name Tool name
     * @param array $parameters Tool parameters
     * @param mixed $result Tool result
     * @param float $duration Duration in seconds
     */
    public static function record_tool_execution($tool_name, $parameters, $result, $duration) {
        if (self::$trace_id === null) {
            return;
        }
        
        $tool_execution = array(
            'tool_name' => $tool_name,
            'parameters' => $parameters,
            'result' => $result,
            'duration' => $duration,
            'timestamp' => microtime(true),
            'relative_time' => microtime(true) - self::$trace_data['start_time']
        );
        
        self::$trace_data['tool_executions'][] = $tool_execution;
        self::add_event('tool_execution', "Tool execution: $tool_name");
    }
    
    /**
     * End the trace
     * 
     * @param mixed $response Final response
     * @return array Complete trace data
     */
    public static function end_trace($response) {
        if (self::$trace_id === null) {
            return null;
        }
        
        self::$trace_data['response'] = $response;
        self::$trace_data['end_time'] = microtime(true);
        self::$trace_data['duration'] = self::$trace_data['end_time'] - self::$trace_data['start_time'];
        
        self::add_event('trace_ended', 'Trace ended');
        
        error_log(sprintf(
            "MPAI Trace Ended: %s (%.4f seconds)",
            self::$trace_id,
            self::$trace_data['duration']
        ));
        
        // Save trace for later analysis
        $traces = get_option('mpai_request_traces', array());
        $traces[self::$trace_id] = self::$trace_data;
        
        // Keep only last 10 traces
        if (count($traces) > 10) {
            $traces = array_slice($traces, -10, null, true);
        }
        
        update_option('mpai_request_traces', $traces);
        
        $trace_data = self::$trace_data;
        
        // Reset tracer
        self::$trace_id = null;
        self::$trace_data = array();
        
        return $trace_data;
    }
    
    /**
     * Get a saved trace
     * 
     * @param string $trace_id Trace ID
     * @return array|null Trace data or null if not found
     */
    public static function get_trace($trace_id) {
        $traces = get_option('mpai_request_traces', array());
        
        return isset($traces[$trace_id]) ? $traces[$trace_id] : null;
    }
    
    /**
     * List all saved traces
     * 
     * @return array Trace summary list
     */
    public static function list_traces() {
        $traces = get_option('mpai_request_traces', array());
        $summary = array();
        
        foreach ($traces as $id => $trace) {
            $summary[] = array(
                'id' => $id,
                'request' => substr($trace['request'], 0, 50) . (strlen($trace['request']) > 50 ? '...' : ''),
                'start_time' => date('Y-m-d H:i:s', (int)$trace['start_time']),
                'duration' => $trace['duration'],
                'api_calls' => count($trace['api_calls']),
                'tool_executions' => count($trace['tool_executions'])
            );
        }
        
        return $summary;
    }
}

// Set up hooks to automatically trace requests
add_action('mpai_before_process_request', function($request_data, $context) {
    MPAI_Request_Tracer::start_trace($request_data);
}, 10, 2);

add_filter('mpai_after_process_request', function($response, $request_data, $context) {
    MPAI_Request_Tracer::end_trace($response);
    return $response;
}, 10, 3);

add_action('mpai_api_request', function($provider, $request) {
    $start_time = microtime(true);
    
    add_filter('mpai_api_response', function($response) use ($provider, $request, $start_time) {
        $duration = microtime(true) - $start_time;
        MPAI_Request_Tracer::record_api_call($provider, $request, $response, $duration);
        return $response;
    }, 10, 1);
}, 10, 2);

add_action('mpai_before_tool_execution', function($tool_name, $parameters) {
    $start_time = microtime(true);
    
    add_action('mpai_after_tool_execution', function($tool_name_after, $parameters_after, $result) use ($tool_name, $parameters, $start_time) {
        if ($tool_name === $tool_name_after) {
            $duration = microtime(true) - $start_time;
            MPAI_Request_Tracer::record_tool_execution($tool_name, $parameters, $result, $duration);
        }
    }, 10, 3);
}, 10, 2);
```

## Debugging Tools Reference

### Command Line Debugging Tools

Use WP-CLI for debugging:

```bash
# Check plugin health
wp mpai health-check

# Test API connection
wp mpai test-api openai

# Flush caches
wp mpai flush-cache

# View recent errors
wp mpai show-errors --count=10

# Run a test request
wp mpai test-request "What is MemberPress?"

# Diagnose tool issues
wp mpai test-tool example_tool '{"param1": "test"}'

# Check memory usage
wp mpai memory-usage

# Show active hooks
wp mpai list-hooks
```

### Browser Debugging Extensions

Tools to help debug in the browser:

1. **Query Monitor Plugin**
   - Adds a debug bar with extensive WordPress debug info
   - Shows hooks, queries, HTTP requests, etc.

2. **Redux DevTools**
   - If you're using Redux for state management
   - Install through Chrome/Firefox extensions

3. **MemberPress AI Assistant Debug Panel**
   - Enable with `define('MPAI_SHOW_DEBUG_PANEL', true);`
   - Provides plugin-specific debugging tools in the admin area

4. **Local Storage Explorer**
   - The plugin stores some debug information in localStorage
   - Access via browser developer tools > Application > Local Storage

### Debug Mode Features

When `MPAI_DEBUG` is enabled:

1. **Extended Logging**
   - Detailed logs in `wp-content/uploads/mpai-logs/`
   - Console logs in browser developer tools

2. **Debug Overlays**
   - Visual indicators for API calls and tool executions
   - Timing information displayed in UI

3. **State Inspector**
   - Access via `?mpai-debug=state` query parameter
   - Shows current plugin state, caches, and configuration

4. **Trace Viewer**
   - Access via `?mpai-debug=traces` query parameter
   - Shows detailed traces of recent requests

5. **Mock Mode**
   - Enable with `define('MPAI_USE_MOCK_API', true);`
   - Simulates API responses without making real API calls

## Document Revision History

| Date | Version | Changes |
|------|---------|---------|
| 2025-04-06 | 1.0.0 | Initial document creation |