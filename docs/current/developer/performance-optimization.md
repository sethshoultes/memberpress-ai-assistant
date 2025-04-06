# Performance Optimization Guide

**Version:** 1.0.0  
**Last Updated:** 2025-04-06  
**Status:** ✅ Stable  
**Owner:** Developer Documentation Team

## Overview

This guide outlines strategies and best practices for optimizing the performance of the MemberPress AI Assistant plugin. It covers methods for improving response times, reducing resource usage, and ensuring a smooth user experience even under high loads.

## Table of Contents

1. [Understanding Performance Considerations](#understanding-performance-considerations)
2. [Response Time Optimization](#response-time-optimization)
3. [Memory Usage Optimization](#memory-usage-optimization)
4. [Database Optimization](#database-optimization)
5. [Caching Strategies](#caching-strategies)
6. [API Communication Optimization](#api-communication-optimization)
7. [Frontend Performance](#frontend-performance)
8. [Context Management Optimization](#context-management-optimization)
9. [Batching and Chunking](#batching-and-chunking)
10. [Performance Testing](#performance-testing)
11. [Performance Monitoring](#performance-monitoring)

## Understanding Performance Considerations

The MemberPress AI Assistant operates at the intersection of WordPress and AI services, creating unique performance challenges:

### Key Performance Metrics

- **Response Time**: Time from user query to AI response
- **Memory Usage**: RAM consumed during plugin operation
- **API Latency**: Time spent waiting for AI provider responses
- **Database Load**: Impact on the WordPress database
- **Frontend Rendering**: Browser-side performance
- **Concurrency Handling**: Performance under multiple simultaneous users

### Common Performance Bottlenecks

1. **API Communication**: Delays in external AI API requests
2. **Context Building**: Gathering and processing context data for AI
3. **Large Response Processing**: Handling lengthy AI responses
4. **Database Queries**: Inefficient or excessive database access
5. **JavaScript Processing**: Heavy client-side processing
6. **Memory Exhaustion**: PHP memory limits exceeded during processing

## Response Time Optimization

### Measuring Response Time

Track various stages of request processing:

```php
function mpai_measure_response_time() {
    // Define measurement points
    $measurements = array(
        'start' => microtime(true),
        'context_built' => 0,
        'api_request_sent' => 0,
        'api_response_received' => 0,
        'response_processed' => 0,
        'complete' => 0
    );
    
    // Measure context building
    add_action('mpai_context_built', function() use (&$measurements) {
        $measurements['context_built'] = microtime(true);
    });
    
    // Measure API request
    add_action('mpai_api_request_sent', function() use (&$measurements) {
        $measurements['api_request_sent'] = microtime(true);
    });
    
    // Measure API response
    add_action('mpai_api_response_received', function() use (&$measurements) {
        $measurements['api_response_received'] = microtime(true);
    });
    
    // Measure response processing
    add_action('mpai_response_processed', function() use (&$measurements) {
        $measurements['response_processed'] = microtime(true);
    });
    
    // Complete measurement
    add_action('mpai_request_complete', function() use (&$measurements) {
        $measurements['complete'] = microtime(true);
        
        // Calculate durations
        $durations = array(
            'context_building' => $measurements['context_built'] - $measurements['start'],
            'api_wait' => $measurements['api_response_received'] - $measurements['api_request_sent'],
            'response_processing' => $measurements['response_processed'] - $measurements['api_response_received'],
            'total' => $measurements['complete'] - $measurements['start']
        );
        
        // Log or store measurements
        update_option('mpai_last_response_times', $durations);
    });
    
    return $measurements;
}
```

### Optimizing Each Stage

#### Context Building Optimization

```php
// Prioritize context data by importance
function mpai_optimize_context_building($context) {
    // Set priorities for context sections
    $priorities = array(
        'user_query' => 1,        // Highest priority
        'conversation_history' => 2,
        'user_info' => 3,
        'current_page' => 4,
        'memberpress_data' => 5,
        'system_info' => 6        // Lowest priority
    );
    
    // If approaching token limits, remove lower priority sections
    if (mpai_estimate_tokens($context) > MPAI_MAX_CONTEXT_TOKENS * 0.8) {
        uasort($context, function($a, $b) use ($priorities) {
            $a_priority = $priorities[key($a)] ?? 999;
            $b_priority = $priorities[key($b)] ?? 999;
            return $a_priority <=> $b_priority;
        });
        
        // Truncate lowest priority sections first
        $token_count = 0;
        foreach ($context as $key => $value) {
            $section_tokens = mpai_estimate_tokens($value);
            $token_count += $section_tokens;
            
            if ($token_count > MPAI_MAX_CONTEXT_TOKENS) {
                unset($context[$key]);
            }
        }
    }
    
    return $context;
}

// Use lazy loading for expensive context data
function mpai_lazy_load_context_data() {
    add_filter('mpai_chat_context', function($context, $user_id) {
        // Only add expensive data when needed
        if (strpos($context['user_query'], 'membership') !== false) {
            $context['memberpress_data'] = mpai_get_membership_data($user_id);
        }
        
        return $context;
    }, 10, 2);
}
```

#### API Communication Optimization

```php
// Implement request caching
function mpai_cache_similar_requests($request, $cache_key = '') {
    // Generate cache key if not provided
    if (empty($cache_key)) {
        $cache_key = 'mpai_req_' . md5($request['prompt'] . json_encode($request['context']));
    }
    
    // Check cache first
    $cached = get_transient($cache_key);
    if ($cached !== false) {
        return $cached;
    }
    
    // If not cached, make the actual request
    $response = mpai_make_api_request($request);
    
    // Cache the response (30 minutes)
    set_transient($cache_key, $response, 30 * MINUTE_IN_SECONDS);
    
    return $response;
}

// Use appropriate API models based on complexity
function mpai_select_optimal_model($request) {
    $tokens = mpai_estimate_tokens($request['prompt'] . json_encode($request['context']));
    
    // Use smaller/faster models for simple requests
    if ($tokens < 1000 && !mpai_is_complex_query($request['prompt'])) {
        return 'gpt-3.5-turbo'; // Faster, cheaper model
    }
    
    // Use more capable models for complex requests
    return 'gpt-4'; // More powerful but slower model
}
```

## Memory Usage Optimization

### Memory Profiling

```php
function mpai_profile_memory_usage() {
    $memory_points = array();
    
    // Start measurement
    $memory_points['start'] = memory_get_usage();
    
    // Monitor key points
    add_action('mpai_context_built', function() use (&$memory_points) {
        $memory_points['after_context'] = memory_get_usage();
    });
    
    add_action('mpai_api_response_received', function() use (&$memory_points) {
        $memory_points['after_api'] = memory_get_usage();
    });
    
    add_action('mpai_request_complete', function() use (&$memory_points) {
        $memory_points['end'] = memory_get_usage();
        
        // Calculate deltas
        $memory_usage = array(
            'context_building' => $memory_points['after_context'] - $memory_points['start'],
            'api_processing' => $memory_points['after_api'] - $memory_points['after_context'],
            'response_processing' => $memory_points['end'] - $memory_points['after_api'],
            'total' => $memory_points['end'] - $memory_points['start']
        );
        
        // Log memory usage
        error_log('MPAI Memory Usage: ' . json_encode($memory_usage));
    });
    
    return $memory_points;
}
```

### Memory Optimization Techniques

```php
// Clean up large objects when finished
function mpai_cleanup_memory() {
    add_action('mpai_request_complete', function() {
        global $mpai_large_objects;
        
        // Free memory by unsetting large objects
        if (isset($mpai_large_objects) && is_array($mpai_large_objects)) {
            foreach ($mpai_large_objects as $object_name) {
                if (isset($GLOBALS[$object_name])) {
                    unset($GLOBALS[$object_name]);
                }
            }
        }
        
        // Force garbage collection in PHP 7.3+
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    });
}

// Stream responses to avoid memory buildup
function mpai_stream_large_responses($response) {
    // Check if response is large
    if (strlen($response) > 500000) { // ~500KB
        // Break into smaller chunks
        $chunks = str_split($response, 50000); // 50KB chunks
        
        // Send chunks with flush
        foreach ($chunks as $chunk) {
            echo $chunk;
            flush();
            
            // Small delay to prevent browser buffering
            usleep(10000); // 10ms
        }
        exit;
    }
    
    return $response;
}
```

## Database Optimization

### Query Optimization

```php
// Use efficient queries
function mpai_optimize_database_queries() {
    // Use specific fields instead of SELECT *
    function mpai_get_chat_history($user_id, $limit = 10) {
        global $wpdb;
        $table = $wpdb->prefix . 'mpai_chat_history';
        
        // Only select needed fields
        return $wpdb->get_results($wpdb->prepare(
            "SELECT id, user_id, message, response, created_at 
             FROM $table 
             WHERE user_id = %d 
             ORDER BY created_at DESC 
             LIMIT %d",
            $user_id,
            $limit
        ));
    }
    
    // Use proper indexing
    function mpai_ensure_database_indexes() {
        global $wpdb;
        $table = $wpdb->prefix . 'mpai_chat_history';
        
        // Check if index exists
        $index_exists = $wpdb->get_results(
            "SHOW INDEX FROM $table WHERE Key_name = 'user_id_index'"
        );
        
        // Create index if needed
        if (empty($index_exists)) {
            $wpdb->query("CREATE INDEX user_id_index ON $table (user_id)");
        }
    }
}

// Implement database cleanup
function mpai_schedule_database_cleanup() {
    // Register cleanup hook
    add_action('mpai_daily_cleanup', 'mpai_cleanup_old_chat_history');
    
    // Schedule event if not already scheduled
    if (!wp_next_scheduled('mpai_daily_cleanup')) {
        wp_schedule_event(time(), 'daily', 'mpai_daily_cleanup');
    }
    
    // Cleanup function
    function mpai_cleanup_old_chat_history() {
        global $wpdb;
        $table = $wpdb->prefix . 'mpai_chat_history';
        
        // Delete records older than 30 days
        $days_to_keep = apply_filters('mpai_history_retention_days', 30);
        $date_threshold = date('Y-m-d H:i:s', strtotime("-$days_to_keep days"));
        
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $table WHERE created_at < %s",
            $date_threshold
        ));
    }
}
```

### Custom Tables Optimization

```php
// Optimize table structure
function mpai_optimize_custom_tables() {
    global $wpdb;
    $table = $wpdb->prefix . 'mpai_chat_history';
    
    // Implement table partitioning for large installations
    if (mpai_is_large_installation()) {
        $wpdb->query("ALTER TABLE $table PARTITION BY RANGE (TO_DAYS(created_at)) (
            PARTITION p_current VALUES LESS THAN (TO_DAYS(NOW())),
            PARTITION p_old VALUES LESS THAN MAXVALUE
        )");
    }
    
    // Optimize table periodically
    add_action('mpai_weekly_maintenance', function() use ($wpdb, $table) {
        $wpdb->query("OPTIMIZE TABLE $table");
    });
    
    if (!wp_next_scheduled('mpai_weekly_maintenance')) {
        wp_schedule_event(time(), 'weekly', 'mpai_weekly_maintenance');
    }
}
```

## Caching Strategies

### Implementing Caching

```php
// Multi-level caching system
class MPAI_Cache {
    // Memory cache for current request
    private static $memory_cache = array();
    
    // Get item with multi-level fallback
    public static function get($key, $group = 'default') {
        // Check memory cache first (fastest)
        $memory_key = $group . '__' . $key;
        if (isset(self::$memory_cache[$memory_key])) {
            return self::$memory_cache[$memory_key];
        }
        
        // Check object cache next (medium speed)
        $cached = wp_cache_get($key, 'mpai_' . $group);
        if (false !== $cached) {
            // Store in memory cache for future use
            self::$memory_cache[$memory_key] = $cached;
            return $cached;
        }
        
        // Finally check transients (slowest but persistent)
        $transient_key = 'mpai_' . $group . '_' . $key;
        $transient = get_transient($transient_key);
        if (false !== $transient) {
            // Store in faster caches for future use
            wp_cache_set($key, $transient, 'mpai_' . $group);
            self::$memory_cache[$memory_key] = $transient;
            return $transient;
        }
        
        return false;
    }
    
    // Set item in all cache levels
    public static function set($key, $data, $group = 'default', $expiration = 3600) {
        // Store in memory
        $memory_key = $group . '__' . $key;
        self::$memory_cache[$memory_key] = $data;
        
        // Store in object cache
        wp_cache_set($key, $data, 'mpai_' . $group);
        
        // Store in transient
        $transient_key = 'mpai_' . $group . '_' . $key;
        set_transient($transient_key, $data, $expiration);
    }
    
    // Delete from all cache levels
    public static function delete($key, $group = 'default') {
        // Remove from memory
        $memory_key = $group . '__' . $key;
        if (isset(self::$memory_cache[$memory_key])) {
            unset(self::$memory_cache[$memory_key]);
        }
        
        // Remove from object cache
        wp_cache_delete($key, 'mpai_' . $group);
        
        // Remove from transients
        $transient_key = 'mpai_' . $group . '_' . $key;
        delete_transient($transient_key);
    }
}
```

### Effective Cache Usage

```php
// Cache context data
function mpai_cache_user_context($user_id) {
    // Generate context key
    $context_key = 'user_context_' . $user_id;
    
    // Try to get from cache
    $context = MPAI_Cache::get($context_key, 'contexts');
    if (false !== $context) {
        return $context;
    }
    
    // If not in cache, generate context
    $context = mpai_generate_user_context($user_id);
    
    // Cache for 5 minutes (contexts may change frequently)
    MPAI_Cache::set($context_key, $context, 'contexts', 5 * MINUTE_IN_SECONDS);
    
    return $context;
}

// Implement cache invalidation
function mpai_invalidate_user_caches($user_id) {
    // When user data changes, invalidate related caches
    add_action('profile_update', function($user_id) {
        MPAI_Cache::delete('user_context_' . $user_id, 'contexts');
    });
    
    // When membership status changes
    add_action('mepr-event-transaction-completed', function($transaction) {
        $user_id = $transaction->user_id;
        MPAI_Cache::delete('user_context_' . $user_id, 'contexts');
    });
}
```

## API Communication Optimization

### Efficient API Usage

```php
// Use appropriate token counts
function mpai_optimize_token_usage($prompt, $context) {
    // Estimate tokens
    $prompt_tokens = mpai_estimate_tokens($prompt);
    $context_tokens = mpai_estimate_tokens(json_encode($context));
    $total_tokens = $prompt_tokens + $context_tokens;
    
    // If approaching limits, reduce context
    if ($total_tokens > MPAI_MAX_INPUT_TOKENS * 0.9) {
        // Calculate how much to reduce
        $target_reduction = $total_tokens - (MPAI_MAX_INPUT_TOKENS * 0.8);
        
        // Reduce context (not the prompt)
        $context = mpai_reduce_context($context, $target_reduction);
    }
    
    return array(
        'prompt' => $prompt,
        'context' => $context
    );
}

// Choose appropriate endpoint based on request
function mpai_select_optimal_endpoint($request_data) {
    // Simple/short responses can use completion endpoints
    if (mpai_is_simple_query($request_data['prompt'])) {
        return 'completions';
    }
    
    // More complex requests use chat endpoints
    return 'chat/completions';
}
```

### Parallel Requests

```php
// Handle multiple AI requests in parallel
function mpai_parallel_requests($requests) {
    // Set up curl multi handler
    $curl_multi = curl_multi_init();
    $curl_handles = array();
    
    // Add each request
    foreach ($requests as $key => $request) {
        $curl = curl_init();
        
        // Set up curl options
        curl_setopt_array($curl, array(
            CURLOPT_URL => $request['url'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $request['headers'],
            CURLOPT_POSTFIELDS => json_encode($request['data']),
            CURLOPT_TIMEOUT => 30
        ));
        
        curl_multi_add_handle($curl_multi, $curl);
        $curl_handles[$key] = $curl;
    }
    
    // Execute requests
    $running = null;
    do {
        curl_multi_exec($curl_multi, $running);
        curl_multi_select($curl_multi);
    } while ($running > 0);
    
    // Get results
    $results = array();
    foreach ($curl_handles as $key => $curl) {
        $results[$key] = array(
            'response' => curl_multi_getcontent($curl),
            'info' => curl_getinfo($curl),
            'error' => curl_error($curl)
        );
        curl_multi_remove_handle($curl_multi, $curl);
    }
    
    // Close multi handle
    curl_multi_close($curl_multi);
    
    return $results;
}
```

## Frontend Performance

### JavaScript Optimization

```javascript
// Use debouncing for user input
function debounceInput(callback, wait = 300) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => callback.apply(this, args), wait);
    };
}

const optimizedSearchHandler = debounceInput(function(searchTerm) {
    // Perform search only after user stops typing
    MPAI.performSearch(searchTerm);
}, 500);

// Implement progressive loading
function loadChatHistoryProgressively() {
    const chatContainer = document.getElementById('mpai-chat-container');
    let page = 1;
    const perPage = 10;
    
    function loadNextPage() {
        const loadingIndicator = document.createElement('div');
        loadingIndicator.className = 'mpai-loading';
        chatContainer.appendChild(loadingIndicator);
        
        fetch(`/wp-json/mpai/v1/chat-history?page=${page}&per_page=${perPage}`)
            .then(response => response.json())
            .then(data => {
                loadingIndicator.remove();
                
                if (data.length > 0) {
                    renderChatMessages(data);
                    page++;
                    
                    // Check if we need to add scroll listener
                    if (page === 2) {
                        chatContainer.addEventListener('scroll', handleScroll);
                    }
                } else {
                    // No more messages, remove scroll listener
                    chatContainer.removeEventListener('scroll', handleScroll);
                }
            });
    }
    
    function handleScroll() {
        // Load more when scroll near bottom
        if (chatContainer.scrollTop + chatContainer.clientHeight >= 
            chatContainer.scrollHeight - 100) {
            loadNextPage();
        }
    }
    
    // Initial load
    loadNextPage();
}
```

### CSS Optimization

```css
/* Efficient CSS selectors */
.mpai-chat-container .mpai-message {
    /* Direct descendant selector is faster */
}

/* Use hardware acceleration for animations */
.mpai-typing-indicator {
    transform: translateZ(0);
    will-change: opacity;
}

/* Reduce paint operations */
.mpai-message-appear {
    transition: opacity 0.3s ease-in-out;
}
```

## Context Management Optimization

### Smart Context Building

```php
// Implement context size limits
function mpai_limit_context_size($context, $max_tokens = 2000) {
    $total_tokens = 0;
    $optimized_context = array();
    
    // Sort context sections by priority
    $priority_order = array(
        'user_query',
        'conversation_history',
        'user_info',
        'current_page',
        'memberpress_data'
    );
    
    // Reorder based on priority
    $sorted_context = array();
    foreach ($priority_order as $key) {
        if (isset($context[$key])) {
            $sorted_context[$key] = $context[$key];
        }
    }
    
    // Add any remaining sections
    foreach ($context as $key => $value) {
        if (!in_array($key, $priority_order)) {
            $sorted_context[$key] = $value;
        }
    }
    
    // Add sections until we approach token limit
    foreach ($sorted_context as $key => $value) {
        $section_tokens = mpai_estimate_tokens(json_encode($value));
        
        if ($total_tokens + $section_tokens <= $max_tokens) {
            $optimized_context[$key] = $value;
            $total_tokens += $section_tokens;
        } elseif ($key == 'conversation_history') {
            // For conversation history, try to include at least some recent messages
            $optimized_history = mpai_truncate_conversation_history(
                $value, 
                $max_tokens - $total_tokens
            );
            $optimized_context[$key] = $optimized_history;
            $total_tokens += mpai_estimate_tokens(json_encode($optimized_history));
        }
    }
    
    return $optimized_context;
}

// Truncate conversation history intelligently
function mpai_truncate_conversation_history($history, $max_tokens) {
    // Always include the most recent messages
    $truncated_history = array();
    $tokens_used = 0;
    
    // Process in reverse (newest first)
    $reversed_history = array_reverse($history);
    
    foreach ($reversed_history as $message) {
        $message_tokens = mpai_estimate_tokens(json_encode($message));
        
        if ($tokens_used + $message_tokens <= $max_tokens) {
            array_unshift($truncated_history, $message); // Add to front
            $tokens_used += $message_tokens;
        } else {
            break;
        }
    }
    
    return $truncated_history;
}
```

### Context Persistence and Reuse

```php
// Persist context between requests for the same user
function mpai_persist_user_context($user_id, $context = null) {
    $context_key = 'user_context_' . $user_id;
    
    if ($context === null) {
        // Get stored context
        return get_transient($context_key);
    } else {
        // Store context for future use (15 minutes)
        set_transient($context_key, $context, 15 * MINUTE_IN_SECONDS);
    }
}

// Update specific context sections
function mpai_update_context_section($user_id, $section_key, $data) {
    $context = mpai_persist_user_context($user_id);
    
    if ($context) {
        $context[$section_key] = $data;
        mpai_persist_user_context($user_id, $context);
    }
    
    return $context;
}
```

## Batching and Chunking

### Request Batching

```php
// Batch multiple small requests into one
function mpai_batch_requests($requests) {
    // If only one request, process normally
    if (count($requests) <= 1) {
        return mpai_process_single_request($requests[0]);
    }
    
    // Combine requests into a batched format
    $combined_prompt = "I need multiple brief responses for different questions.\n\n";
    
    foreach ($requests as $index => $request) {
        $combined_prompt .= "Question " . ($index + 1) . ": " . $request['prompt'] . "\n";
    }
    
    $combined_prompt .= "\nPlease provide numbered responses that correspond to each question.";
    
    // Make a single API call
    $combined_response = mpai_make_api_request(array(
        'prompt' => $combined_prompt,
        'context' => array() // Minimal context for batch requests
    ));
    
    // Parse the combined response
    $individual_responses = mpai_parse_batch_response($combined_response, count($requests));
    
    return $individual_responses;
}

// Parse a batched response into individual answers
function mpai_parse_batch_response($combined_response, $request_count) {
    $responses = array();
    
    // Simple parsing by looking for question number patterns
    preg_match_all('/(?:Answer|Response)?\s*(\d+)\s*:?\s*(.*?)(?=(?:Answer|Response)?\s*\d+\s*:|\z)/is', $combined_response, $matches, PREG_SET_ORDER);
    
    foreach ($matches as $match) {
        $question_num = (int)$match[1];
        if ($question_num > 0 && $question_num <= $request_count) {
            $responses[$question_num - 1] = trim($match[2]);
        }
    }
    
    // Fill in any missing responses
    for ($i = 0; $i < $request_count; $i++) {
        if (!isset($responses[$i])) {
            $responses[$i] = "No response available for this question.";
        }
    }
    
    return $responses;
}
```

### Response Chunking

```php
// Process large responses in chunks
function mpai_process_large_response($response) {
    // Check if response is large
    if (strlen($response) > 100000) { // 100KB threshold
        return mpai_chunk_process_response($response);
    }
    
    // For smaller responses, process normally
    return mpai_process_response($response);
}

// Process a large response in smaller chunks
function mpai_chunk_process_response($response) {
    // Split into manageable chunks (e.g., paragraphs)
    $chunks = preg_split('/(\r\n|\n){2,}/', $response);
    
    $processed_chunks = array();
    foreach ($chunks as $chunk) {
        // Process each chunk individually
        $processed_chunks[] = mpai_process_response_chunk($chunk);
        
        // Optional: yield to other processes periodically
        if (function_exists('opcache_reset')) {
            opcache_reset(); // Help with memory management
        }
    }
    
    // Combine processed chunks
    return implode("\n\n", $processed_chunks);
}
```

## Performance Testing

### Load Testing Methods

```php
// Simulate multiple concurrent requests
function mpai_simulate_load($request_count, $concurrent = 5) {
    $results = array(
        'success' => 0,
        'failure' => 0,
        'response_times' => array(),
        'errors' => array()
    );
    
    $sample_request = array(
        'prompt' => 'What is MemberPress?',
        'context' => array('simple' => 'context')
    );
    
    // Process in batches of concurrent requests
    for ($i = 0; $i < $request_count; $i += $concurrent) {
        $batch_size = min($concurrent, $request_count - $i);
        $batch_requests = array();
        
        // Prepare batch
        for ($j = 0; $j < $batch_size; $j++) {
            $batch_requests[] = $sample_request;
        }
        
        // Process batch
        $start_time = microtime(true);
        $batch_results = mpai_parallel_requests($batch_requests);
        $end_time = microtime(true);
        
        // Record results
        foreach ($batch_results as $result) {
            if (!empty($result['error'])) {
                $results['failure']++;
                $results['errors'][] = $result['error'];
            } else {
                $results['success']++;
                $results['response_times'][] = $result['info']['total_time'];
            }
        }
        
        // Avoid overwhelming the system
        sleep(1);
    }
    
    // Calculate statistics
    if (!empty($results['response_times'])) {
        $results['avg_response_time'] = array_sum($results['response_times']) / count($results['response_times']);
        $results['min_response_time'] = min($results['response_times']);
        $results['max_response_time'] = max($results['response_times']);
    }
    
    return $results;
}
```

### Benchmarking

```php
// Benchmark key operations
function mpai_benchmark_operation($operation, $iterations = 10) {
    $times = array();
    $memory_usage = array();
    
    for ($i = 0; $i < $iterations; $i++) {
        // Clear previous state
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        
        // Measure execution time
        $start_time = microtime(true);
        $start_memory = memory_get_usage();
        
        // Execute the operation
        switch ($operation) {
            case 'context_building':
                mpai_generate_user_context(1);
                break;
                
            case 'response_processing':
                mpai_process_response("Sample response text for processing");
                break;
                
            case 'database_query':
                mpai_get_chat_history(1, 10);
                break;
                
            // Add other operations to benchmark
        }
        
        // Record metrics
        $times[] = microtime(true) - $start_time;
        $memory_usage[] = memory_get_usage() - $start_memory;
    }
    
    // Calculate statistics
    return array(
        'operation' => $operation,
        'iterations' => $iterations,
        'avg_time' => array_sum($times) / count($times),
        'min_time' => min($times),
        'max_time' => max($times),
        'avg_memory' => array_sum($memory_usage) / count($memory_usage),
        'min_memory' => min($memory_usage),
        'max_memory' => max($memory_usage)
    );
}
```

## Performance Monitoring

### System Health Checks

```php
// Implement system health monitoring
function mpai_monitor_system_health() {
    $health_data = array(
        'timestamp' => current_time('mysql'),
        'memory_limit' => ini_get('memory_limit'),
        'memory_usage' => memory_get_usage(true),
        'memory_peak' => memory_get_peak_usage(true),
        'api_status' => mpai_check_api_health(),
        'database_status' => mpai_check_database_health(),
        'cache_status' => mpai_check_cache_health(),
        'response_times' => get_option('mpai_last_response_times', array())
    );
    
    // Store health data
    $health_history = get_option('mpai_health_history', array());
    $health_history[] = $health_data;
    
    // Keep only the most recent records
    if (count($health_history) > 100) {
        $health_history = array_slice($health_history, -100);
    }
    
    update_option('mpai_health_history', $health_history);
    
    // Check for concerning metrics
    mpai_analyze_health_data($health_data);
    
    return $health_data;
}

// Set up regular health checks
function mpai_schedule_health_checks() {
    if (!wp_next_scheduled('mpai_health_check')) {
        wp_schedule_event(time(), 'hourly', 'mpai_health_check');
    }
    
    add_action('mpai_health_check', 'mpai_monitor_system_health');
}
```

### Performance Alerting

```php
// Analyze health data and alert if needed
function mpai_analyze_health_data($health_data) {
    $alerts = array();
    
    // Check memory usage
    $memory_usage_mb = $health_data['memory_usage'] / 1024 / 1024;
    $memory_limit_mb = intval($health_data['memory_limit']);
    
    if ($memory_usage_mb > $memory_limit_mb * 0.8) {
        $alerts[] = array(
            'type' => 'memory',
            'level' => 'warning',
            'message' => "Memory usage ($memory_usage_mb MB) approaching limit ($memory_limit_mb MB)"
        );
    }
    
    // Check API status
    if ($health_data['api_status'] !== 'healthy') {
        $alerts[] = array(
            'type' => 'api',
            'level' => 'error',
            'message' => "API health check failed: " . $health_data['api_status']
        );
    }
    
    // Check response times
    if (!empty($health_data['response_times']['total']) && 
        $health_data['response_times']['total'] > 5) { // More than 5 seconds
        $alerts[] = array(
            'type' => 'performance',
            'level' => 'warning',
            'message' => "Slow response time: " . round($health_data['response_times']['total'], 2) . " seconds"
        );
    }
    
    // Send alerts if needed
    if (!empty($alerts)) {
        mpai_send_performance_alerts($alerts);
    }
    
    return $alerts;
}

// Send performance alerts
function mpai_send_performance_alerts($alerts) {
    // Log alerts
    foreach ($alerts as $alert) {
        error_log("MPAI Performance Alert [{$alert['level']}]: {$alert['message']}");
    }
    
    // Only send email for serious issues
    $serious_alerts = array_filter($alerts, function($alert) {
        return $alert['level'] == 'error';
    });
    
    if (!empty($serious_alerts) && apply_filters('mpai_send_email_alerts', true)) {
        $admin_email = get_option('admin_email');
        $subject = 'MemberPress AI Assistant Performance Alert';
        
        $message = "The following performance issues were detected:\n\n";
        foreach ($serious_alerts as $alert) {
            $message .= "- [{$alert['type']}] {$alert['message']}\n";
        }
        
        $message .= "\nPlease check the system health page for more details.";
        
        wp_mail($admin_email, $subject, $message);
    }
    
    // Store alerts
    update_option('mpai_latest_alerts', $alerts);
}
```

## Document Revision History

| Date | Version | Changes |
|------|---------|---------|
| 2025-04-06 | 1.0.0 | Initial document creation |