# Security Best Practices Guide

**Version:** 1.0.0  
**Last Updated:** 2025-04-06  
**Status:** ✅ Stable  
**Owner:** Developer Documentation Team

## Overview

This guide outlines security best practices for developing, extending, and implementing the MemberPress AI Assistant plugin. Given that the plugin interacts with AI services and handles potentially sensitive data, security is of paramount importance. Following these guidelines will help ensure the plugin remains secure and protects user data.

## Table of Contents

1. [General Security Principles](#general-security-principles)
2. [API Key Management](#api-key-management)
3. [Data Validation and Sanitization](#data-validation-and-sanitization)
4. [User Input Handling](#user-input-handling)
5. [Permissions and Capability Checks](#permissions-and-capability-checks)
6. [Secure API Communication](#secure-api-communication)
7. [Error Handling and Logging](#error-handling-and-logging)
8. [Request and Context Security](#request-and-context-security)
9. [Plugin Extensibility Security](#plugin-extensibility-security)
10. [Security Testing](#security-testing)
11. [Regular Security Updates](#regular-security-updates)
12. [Handling Sensitive Data](#handling-sensitive-data)

## General Security Principles

When working with the MemberPress AI Assistant, always adhere to these core security principles:

### Defense in Depth

Implement multiple layers of security controls. Don't rely on a single security measure to protect the entire system.

```php
// Example: Multiple security layers
function process_ai_request($request_data) {
    // Layer 1: Validate request structure
    if (!is_valid_request_structure($request_data)) {
        return new WP_Error('invalid_request', 'Invalid request structure');
    }
    
    // Layer 2: Check user permissions
    if (!current_user_can('use_ai_assistant')) {
        return new WP_Error('permission_denied', 'You do not have permission to use the AI Assistant');
    }
    
    // Layer 3: Sanitize inputs
    $sanitized_data = sanitize_request_data($request_data);
    
    // Layer 4: Verify request with nonce
    if (!verify_request_nonce($sanitized_data)) {
        return new WP_Error('invalid_nonce', 'Security check failed');
    }
    
    // Layer 5: Rate limiting
    if (is_rate_limited(get_current_user_id())) {
        return new WP_Error('rate_limited', 'Too many requests, please try again later');
    }
    
    // Process the request if all security checks pass
    return execute_ai_request($sanitized_data);
}
```

### Principle of Least Privilege

Always use the minimum level of permissions necessary. Restrict access to functionality based on user roles and capabilities.

```php
// Example: Creating custom capability for AI features
register_activation_hook(__FILE__, 'mpai_add_capabilities');

function mpai_add_capabilities() {
    // Add basic AI capability to administrators
    $role = get_role('administrator');
    $role->add_cap('use_ai_assistant');
    $role->add_cap('manage_ai_assistant');
    
    // Add usage capability only to editors
    $role = get_role('editor');
    $role->add_cap('use_ai_assistant');
    
    // Add usage capability only to authors
    $role = get_role('author');
    $role->add_cap('use_ai_assistant');
}

// Then check for specific capabilities
if (current_user_can('manage_ai_assistant')) {
    // Allow administrative functions
}

if (current_user_can('use_ai_assistant')) {
    // Allow usage of the AI assistant
}
```

### Security by Design

Incorporate security at every stage of development, not as an afterthought.

```php
// Example: Security-focused class design
class MPAI_Secure_Component {
    /**
     * Validate input before processing
     */
    private function validate_input($input) {
        // Validation logic
    }
    
    /**
     * Sanitize data before using
     */
    private function sanitize_data($data) {
        // Sanitization logic
    }
    
    /**
     * Check permissions before action
     */
    private function check_permissions($user_id, $action) {
        // Permission check logic
    }
    
    /**
     * Public method that enforces security by design
     */
    public function process($input, $user_id) {
        // First validate
        if (!$this->validate_input($input)) {
            return new WP_Error('invalid_input', 'Invalid input data');
        }
        
        // Then check permissions
        if (!$this->check_permissions($user_id, 'process')) {
            return new WP_Error('permission_denied', 'Permission denied');
        }
        
        // Then sanitize
        $sanitized = $this->sanitize_data($input);
        
        // Only then process
        return $this->do_process($sanitized);
    }
    
    /**
     * Actual processing logic
     */
    private function do_process($sanitized_data) {
        // Business logic
    }
}
```

## API Key Management

API keys for OpenAI, Anthropic, and other services are sensitive credentials that need special handling.

### Secure Storage

Store API keys securely, using WordPress options with proper encryption when possible.

```php
// Example: Storing and retrieving API keys securely
function mpai_save_api_key($key) {
    // If possible, encrypt the key before storage
    $encrypted_key = mpai_encrypt_sensitive_data($key);
    
    // Store in options
    update_option('mpai_api_key', $encrypted_key);
    
    // Clear any cached values
    wp_cache_delete('mpai_api_key', 'mpai');
}

function mpai_get_api_key() {
    // Try to get from cache first
    $encrypted_key = wp_cache_get('mpai_api_key', 'mpai');
    
    if (false === $encrypted_key) {
        // Get from options
        $encrypted_key = get_option('mpai_api_key', '');
        
        // Cache for this request
        wp_cache_set('mpai_api_key', $encrypted_key, 'mpai');
    }
    
    // Decrypt before returning
    return $encrypted_key ? mpai_decrypt_sensitive_data($encrypted_key) : '';
}

/**
 * Simple encryption for sensitive data.
 * Note: For true security, use a dedicated encryption library.
 */
function mpai_encrypt_sensitive_data($data) {
    // Get the encryption key
    $encryption_key = mpai_get_encryption_key();
    
    if (empty($encryption_key)) {
        // If no encryption is available, at least obfuscate
        return base64_encode($data);
    }
    
    // Use WordPress native encryption if available
    if (function_exists('wp_encrypt')) {
        return wp_encrypt($data, $encryption_key);
    }
    
    // For demo only - in production use a proper encryption library
    $ivlen = openssl_cipher_iv_length('AES-256-CBC');
    $iv = openssl_random_pseudo_bytes($ivlen);
    $encrypted = openssl_encrypt($data, 'AES-256-CBC', $encryption_key, 0, $iv);
    
    return base64_encode($iv . $encrypted);
}

/**
 * Get an encryption key, generating one if needed
 */
function mpai_get_encryption_key() {
    $key = get_option('mpai_encryption_key', '');
    
    if (empty($key)) {
        // Generate a new key
        if (function_exists('wp_generate_password')) {
            $key = wp_generate_password(64, true, true);
        } else {
            $key = bin2hex(random_bytes(32));
        }
        
        update_option('mpai_encryption_key', $key);
    }
    
    return $key;
}
```

### Key Access Restriction

Limit which users can view, change, or use API keys.

```php
// Example: Restricting API key access
function mpai_can_manage_api_keys() {
    // Only allow administrators to manage API keys
    return current_user_can('manage_options');
}

function mpai_can_use_api() {
    // Allow users with the custom capability to use the API
    return current_user_can('use_ai_assistant');
}

// Use in admin settings
add_action('admin_init', function() {
    // Register API key settings
    register_setting('mpai_settings', 'mpai_api_key', array(
        'sanitize_callback' => 'mpai_sanitize_api_key',
        'show_in_rest' => false // Never expose in REST API
    ));
    
    // Only show API key field to authorized users
    if (mpai_can_manage_api_keys()) {
        add_settings_field(
            'mpai_api_key',
            'API Key',
            'mpai_render_api_key_field',
            'mpai_settings',
            'mpai_api_settings'
        );
    }
});

// Use when processing requests
function mpai_process_api_request($request) {
    if (!mpai_can_use_api()) {
        return new WP_Error('permission_denied', 'You do not have permission to use the AI Assistant');
    }
    
    // Process the request
}
```

## Data Validation and Sanitization

Always validate and sanitize all data, regardless of source.

### Input Validation

Validate all inputs before processing them:

```php
// Example: Input validation class
class MPAI_Input_Validator {
    /**
     * Validate a general request structure
     */
    public static function validate_request($request) {
        // Check if request is an array
        if (!is_array($request)) {
            return false;
        }
        
        // Check for required fields
        $required_fields = array('prompt', 'context');
        foreach ($required_fields as $field) {
            if (!isset($request[$field])) {
                return false;
            }
        }
        
        // Validate prompt field
        if (!is_string($request['prompt']) || empty($request['prompt'])) {
            return false;
        }
        
        // Validate context field
        if (!is_string($request['context']) && !is_array($request['context'])) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate tool parameters against schema
     */
    public static function validate_tool_parameters($parameters, $schema) {
        if (!is_array($parameters) || !is_array($schema)) {
            return false;
        }
        
        // Check required parameters
        foreach ($schema as $param_name => $param_schema) {
            // Skip optional parameters
            if (isset($param_schema['required']) && $param_schema['required'] && !isset($parameters[$param_name])) {
                return false;
            }
            
            // Skip validation for parameters not provided
            if (!isset($parameters[$param_name])) {
                continue;
            }
            
            // Check type
            if (isset($param_schema['type'])) {
                $value = $parameters[$param_name];
                $type = $param_schema['type'];
                
                switch ($type) {
                    case 'string':
                        if (!is_string($value)) {
                            return false;
                        }
                        break;
                        
                    case 'integer':
                    case 'number':
                        if (!is_numeric($value)) {
                            return false;
                        }
                        break;
                        
                    case 'boolean':
                        if (!is_bool($value) && $value !== 0 && $value !== 1 && $value !== '0' && $value !== '1') {
                            return false;
                        }
                        break;
                        
                    case 'array':
                        if (!is_array($value)) {
                            return false;
                        }
                        break;
                        
                    case 'object':
                        if (!is_array($value) || array_values($value) === $value) {
                            return false;
                        }
                        break;
                }
            }
        }
        
        return true;
    }
    
    /**
     * Validate an API key format
     */
    public static function validate_api_key($api_key, $provider = 'openai') {
        if (!is_string($api_key) || empty($api_key)) {
            return false;
        }
        
        // Check format based on provider
        switch ($provider) {
            case 'openai':
                return preg_match('/^sk-[a-zA-Z0-9]{32,}$/', $api_key);
                
            case 'anthropic':
                return preg_match('/^sk-ant-[a-zA-Z0-9]{24,}$/', $api_key);
                
            default:
                // Generic check
                return strlen($api_key) >= 20;
        }
    }
}
```

### Data Sanitization

Sanitize all inputs to prevent injection attacks:

```php
// Example: Data sanitization class
class MPAI_Data_Sanitizer {
    /**
     * Sanitize general request data
     */
    public static function sanitize_request($request) {
        if (!is_array($request)) {
            return array();
        }
        
        $sanitized = array();
        
        // Sanitize prompt
        if (isset($request['prompt'])) {
            $sanitized['prompt'] = self::sanitize_prompt($request['prompt']);
        }
        
        // Sanitize context
        if (isset($request['context'])) {
            $sanitized['context'] = is_array($request['context']) 
                ? self::sanitize_context($request['context'])
                : sanitize_text_field($request['context']);
        }
        
        // Sanitize additional fields
        if (isset($request['user_id'])) {
            $sanitized['user_id'] = absint($request['user_id']);
        }
        
        if (isset($request['tool_calls'])) {
            $sanitized['tool_calls'] = self::sanitize_tool_calls($request['tool_calls']);
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize a prompt string
     */
    public static function sanitize_prompt($prompt) {
        if (!is_string($prompt)) {
            return '';
        }
        
        // Remove potentially harmful content while preserving newlines
        $sanitized = sanitize_textarea_field($prompt);
        
        // Additional prompt-specific sanitization if needed
        
        return $sanitized;
    }
    
    /**
     * Sanitize a context array
     */
    public static function sanitize_context($context) {
        if (!is_array($context)) {
            return array();
        }
        
        $sanitized = array();
        
        foreach ($context as $key => $value) {
            // Sanitize keys
            $sanitized_key = sanitize_text_field($key);
            
            // Recursively sanitize values
            if (is_array($value)) {
                $sanitized[$sanitized_key] = self::sanitize_context($value);
            } elseif (is_string($value)) {
                $sanitized[$sanitized_key] = sanitize_textarea_field($value);
            } elseif (is_int($value)) {
                $sanitized[$sanitized_key] = intval($value);
            } elseif (is_float($value)) {
                $sanitized[$sanitized_key] = floatval($value);
            } elseif (is_bool($value)) {
                $sanitized[$sanitized_key] = (bool) $value;
            } else {
                // Skip unsupported types
                continue;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize tool calls
     */
    public static function sanitize_tool_calls($tool_calls) {
        if (!is_array($tool_calls)) {
            return array();
        }
        
        $sanitized = array();
        
        foreach ($tool_calls as $tool_call) {
            if (!isset($tool_call['name']) || !isset($tool_call['parameters'])) {
                continue;
            }
            
            $sanitized_tool = array(
                'name' => sanitize_text_field($tool_call['name']),
                'parameters' => self::sanitize_context($tool_call['parameters'])
            );
            
            $sanitized[] = $sanitized_tool;
        }
        
        return $sanitized;
    }
}
```

### Data Escaping

Always escape data before output:

```php
// Example: Data escaping in output
function mpai_render_chat_history($chat_history) {
    if (!is_array($chat_history)) {
        return;
    }
    
    foreach ($chat_history as $message) {
        if (!isset($message['role']) || !isset($message['content'])) {
            continue;
        }
        
        $role = esc_html($message['role']);
        $content = wp_kses_post($message['content']);
        
        echo '<div class="mpai-message mpai-message-' . $role . '">';
        echo '<div class="mpai-message-role">' . $role . '</div>';
        echo '<div class="mpai-message-content">' . $content . '</div>';
        echo '</div>';
    }
}
```

## User Input Handling

User inputs, especially in AI prompts, require special attention to security.

### Prompt Safety Checks

Implement safeguards against harmful prompts:

```php
// Example: Prompt safety checker
class MPAI_Prompt_Safety {
    /**
     * Check if a prompt is safe to process
     */
    public static function is_safe_prompt($prompt) {
        // Convert to lowercase for easier matching
        $lower_prompt = strtolower($prompt);
        
        // Check for potentially harmful patterns
        $harmful_patterns = array(
            'sql injection',
            'xss attack',
            'hack the',
            'bypass security',
            '<script>',
            'document.cookie',
            'eval(',
            'exec(',
            'system(',
            'rm -rf',
            'format c:',
            'drop table',
            'delete from',
            'select * from',
        );
        
        foreach ($harmful_patterns as $pattern) {
            if (strpos($lower_prompt, $pattern) !== false) {
                error_log('MPAI: Potentially harmful prompt detected: ' . $pattern);
                return false;
            }
        }
        
        // Check prompt length to prevent DOS attacks
        if (strlen($prompt) > 2000) {
            error_log('MPAI: Prompt exceeds maximum length');
            return false;
        }
        
        return true;
    }
    
    /**
     * Filter out any unsafe content from prompt
     */
    public static function filter_prompt($prompt) {
        if (!is_string($prompt)) {
            return '';
        }
        
        // Remove any HTML tags
        $filtered = strip_tags($prompt);
        
        // Replace potentially harmful SQL characters
        $filtered = str_replace(array(';', '--', '/*', '*/'), ' ', $filtered);
        
        // Remove excessive whitespace
        $filtered = preg_replace('/\s+/', ' ', $filtered);
        
        return trim($filtered);
    }
    
    /**
     * Process a prompt with safety checks
     */
    public static function process_prompt($prompt, $strict = false) {
        // First filter the prompt
        $filtered_prompt = self::filter_prompt($prompt);
        
        // Then check if it's safe
        if ($strict && !self::is_safe_prompt($filtered_prompt)) {
            return new WP_Error('unsafe_prompt', 'The prompt contains potentially harmful content');
        }
        
        return $filtered_prompt;
    }
}
```

### Rate Limiting

Implement rate limiting to prevent abuse:

```php
// Example: Rate limiting for AI requests
class MPAI_Rate_Limiter {
    /**
     * Check if a user is rate limited
     */
    public static function is_rate_limited($user_id) {
        // Get user's request count
        $request_count = self::get_request_count($user_id);
        
        // Get rate limit settings
        $rate_limit = self::get_rate_limit($user_id);
        
        // Check if user has exceeded their limit
        return $request_count >= $rate_limit;
    }
    
    /**
     * Increment the request counter for a user
     */
    public static function increment_request_count($user_id) {
        $key = 'mpai_rate_limit_' . $user_id;
        $count = get_transient($key);
        
        if (false === $count) {
            // First request in this period
            set_transient($key, 1, HOUR_IN_SECONDS);
        } else {
            // Increment existing count
            set_transient($key, $count + 1, HOUR_IN_SECONDS);
        }
    }
    
    /**
     * Get the current request count for a user
     */
    public static function get_request_count($user_id) {
        $key = 'mpai_rate_limit_' . $user_id;
        $count = get_transient($key);
        
        return false === $count ? 0 : intval($count);
    }
    
    /**
     * Get the rate limit for a user
     */
    public static function get_rate_limit($user_id) {
        // Default rate limit
        $default_limit = 50; // 50 requests per hour
        
        // Check if user has a custom limit
        $user_meta_limit = get_user_meta($user_id, 'mpai_rate_limit', true);
        
        if (!empty($user_meta_limit)) {
            return intval($user_meta_limit);
        }
        
        // Check user role and apply different limits
        $user = get_userdata($user_id);
        
        if ($user && !empty($user->roles)) {
            // Administrators get higher limits
            if (in_array('administrator', $user->roles)) {
                return 100;
            }
            
            // Editors get standard limits
            if (in_array('editor', $user->roles)) {
                return 75;
            }
        }
        
        return $default_limit;
    }
    
    /**
     * Reset rate limit for a user
     */
    public static function reset_rate_limit($user_id) {
        $key = 'mpai_rate_limit_' . $user_id;
        delete_transient($key);
    }
}
```

## Permissions and Capability Checks

Always verify user permissions before performing actions.

### Custom Capabilities

Define and check custom capabilities for plugin functionality:

```php
// Example: Custom capability system
class MPAI_Capabilities {
    /**
     * Setup custom capabilities
     */
    public static function setup() {
        // Define the capabilities
        $capabilities = array(
            'use_ai_assistant' => array('administrator', 'editor', 'author'),
            'manage_ai_assistant' => array('administrator'),
            'view_ai_analytics' => array('administrator', 'editor'),
        );
        
        // Assign capabilities to roles
        foreach ($capabilities as $capability => $roles) {
            foreach ($roles as $role_name) {
                $role = get_role($role_name);
                if ($role) {
                    $role->add_cap($capability);
                }
            }
        }
    }
    
    /**
     * Remove custom capabilities
     */
    public static function cleanup() {
        // Define the capabilities
        $capabilities = array(
            'use_ai_assistant',
            'manage_ai_assistant',
            'view_ai_analytics',
        );
        
        // Remove capabilities from all roles
        global $wp_roles;
        
        foreach ($wp_roles->roles as $role_name => $role_info) {
            $role = get_role($role_name);
            if ($role) {
                foreach ($capabilities as $capability) {
                    $role->remove_cap($capability);
                }
            }
        }
    }
    
    /**
     * Check if a user can use the AI assistant
     */
    public static function can_use_ai($user_id = null) {
        if (null === $user_id) {
            $user_id = get_current_user_id();
        }
        
        // Super admins can always use it
        if (is_multisite() && is_super_admin($user_id)) {
            return true;
        }
        
        // Check the specific capability
        return user_can($user_id, 'use_ai_assistant');
    }
    
    /**
     * Check if a user can manage the AI assistant
     */
    public static function can_manage_ai($user_id = null) {
        if (null === $user_id) {
            $user_id = get_current_user_id();
        }
        
        // Super admins can always manage it
        if (is_multisite() && is_super_admin($user_id)) {
            return true;
        }
        
        // Check the specific capability
        return user_can($user_id, 'manage_ai_assistant');
    }
    
    /**
     * Check if a user can view AI analytics
     */
    public static function can_view_analytics($user_id = null) {
        if (null === $user_id) {
            $user_id = get_current_user_id();
        }
        
        // Super admins can always view analytics
        if (is_multisite() && is_super_admin($user_id)) {
            return true;
        }
        
        // Check the specific capability
        return user_can($user_id, 'view_ai_analytics');
    }
}

// Register activation/deactivation hooks
register_activation_hook(__FILE__, array('MPAI_Capabilities', 'setup'));
register_deactivation_hook(__FILE__, array('MPAI_Capabilities', 'cleanup'));

// Use in your code
if (!MPAI_Capabilities::can_use_ai()) {
    return new WP_Error('permission_denied', 'You do not have permission to use the AI Assistant');
}
```

### Nonce Verification

Always use nonces to protect against CSRF attacks:

```php
// Example: Nonce verification in AJAX handlers
add_action('wp_ajax_mpai_process_request', 'mpai_ajax_process_request');

function mpai_ajax_process_request() {
    // Verify nonce
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'mpai_process_request')) {
        wp_send_json_error(array(
            'message' => 'Security check failed'
        ), 403);
    }
    
    // Check permissions
    if (!MPAI_Capabilities::can_use_ai()) {
        wp_send_json_error(array(
            'message' => 'You do not have permission to use the AI Assistant'
        ), 403);
    }
    
    // Proceed with request processing
    // ...
}

// Creating a nonce for use in forms or AJAX
function mpai_get_nonce() {
    return wp_create_nonce('mpai_process_request');
}

// In JavaScript
function mpai_prepare_ajax_request() {
    return {
        url: ajaxurl,
        method: 'POST',
        data: {
            action: 'mpai_process_request',
            _wpnonce: mpai_ajax_vars.nonce,
            // other data...
        }
    };
}
```

## Secure API Communication

Ensure that communication with AI APIs is secure.

### HTTPS Enforcement

Always use HTTPS for API communications:

```php
// Example: Ensuring HTTPS for API requests
function mpai_prepare_api_request($url, $args) {
    // Force HTTPS
    $url = set_url_scheme($url, 'https');
    
    // Set a reasonable timeout
    if (!isset($args['timeout'])) {
        $args['timeout'] = 30;
    }
    
    // Ensure proper user agent
    if (!isset($args['user-agent'])) {
        $args['user-agent'] = 'MemberPress AI Assistant/' . MPAI_VERSION;
    }
    
    return array($url, $args);
}

// Add filter to WordPress HTTP API
add_filter('http_request_args', function($args, $url) {
    // Only modify our API requests
    if (strpos($url, 'api.openai.com') !== false || 
        strpos($url, 'api.anthropic.com') !== false) {
        
        list($url, $args) = mpai_prepare_api_request($url, $args);
    }
    
    return $args;
}, 10, 2);
```

### Data Minimization

Only send the data that's necessary to the API:

```php
// Example: Data minimization for API requests
function mpai_prepare_prompt_for_api($prompt, $context) {
    // Start with the essential prompt
    $prepared_data = array(
        'prompt' => $prompt
    );
    
    // Only add necessary context
    // Rather than sending the entire context, extract only what's needed
    if (is_array($context)) {
        $necessary_context = array();
        
        // Only include relevant sections
        if (isset($context['user_info'])) {
            // Extract only the fields we need
            $necessary_context['user'] = array(
                'role' => $context['user_info']['role'] ?? 'user',
                // Do NOT include sensitive fields like email, username, etc.
            );
        }
        
        if (isset($context['current_page'])) {
            $necessary_context['page'] = array(
                'title' => $context['current_page']['title'] ?? '',
                'type' => $context['current_page']['type'] ?? '',
                // Do NOT include full content or sensitive metadata
            );
        }
        
        $prepared_data['context'] = $necessary_context;
    }
    
    return $prepared_data;
}
```

### API Response Validation

Always validate API responses before processing:

```php
// Example: API response validation
function mpai_validate_api_response($response, $provider) {
    // Check for WP_Error
    if (is_wp_error($response)) {
        return $response;
    }
    
    // Check response structure based on provider
    switch ($provider) {
        case 'openai':
            // Check for OpenAI specific structure
            if (!isset($response['choices']) || !is_array($response['choices']) || empty($response['choices'])) {
                return new WP_Error('invalid_response', 'Invalid response structure from OpenAI');
            }
            
            // Check for error messages in response
            if (isset($response['error'])) {
                return new WP_Error(
                    'api_error',
                    $response['error']['message'] ?? 'Unknown API error',
                    $response['error']
                );
            }
            
            break;
            
        case 'anthropic':
            // Check for Anthropic specific structure
            if (!isset($response['completion'])) {
                return new WP_Error('invalid_response', 'Invalid response structure from Anthropic');
            }
            
            // Check for error messages in response
            if (isset($response['error'])) {
                return new WP_Error(
                    'api_error',
                    $response['error']['message'] ?? 'Unknown API error',
                    $response['error']
                );
            }
            
            break;
    }
    
    // General validation
    if (empty($response)) {
        return new WP_Error('empty_response', 'Empty response from API');
    }
    
    return $response;
}
```

## Error Handling and Logging

Proper error handling and logging are crucial for security.

### Secure Error Handling

Implement secure error handling that doesn't expose sensitive details:

```php
// Example: Secure error handling
class MPAI_Error_Handler {
    /**
     * Handle an error securely
     */
    public static function handle_error($error, $public = true) {
        // Log the full error details for administrators
        self::log_error($error);
        
        // Return an appropriate public error message
        if ($public) {
            return self::get_public_error($error);
        }
        
        // Return the original error for internal use
        return $error;
    }
    
    /**
     * Log an error with full details
     */
    private static function log_error($error) {
        if (is_wp_error($error)) {
            $message = $error->get_error_message();
            $code = $error->get_error_code();
            $data = $error->get_error_data();
            
            error_log(sprintf(
                'MPAI Error [%s]: %s | Data: %s',
                $code,
                $message,
                is_array($data) || is_object($data) ? json_encode($data) : $data
            ));
        } else {
            error_log('MPAI Error: ' . (is_string($error) ? $error : json_encode($error)));
        }
        
        // Store error in database for admin review
        self::store_error($error);
    }
    
    /**
     * Store error for admin review
     */
    private static function store_error($error) {
        $errors = get_option('mpai_error_log', array());
        
        // Format the error
        if (is_wp_error($error)) {
            $formatted_error = array(
                'code' => $error->get_error_code(),
                'message' => $error->get_error_message(),
                'data' => $error->get_error_data(),
                'time' => current_time('mysql'),
                'user_id' => get_current_user_id()
            );
        } else {
            $formatted_error = array(
                'message' => is_string($error) ? $error : json_encode($error),
                'time' => current_time('mysql'),
                'user_id' => get_current_user_id()
            );
        }
        
        // Add to log
        $errors[] = $formatted_error;
        
        // Keep log size reasonable
        if (count($errors) > 100) {
            $errors = array_slice($errors, -100);
        }
        
        update_option('mpai_error_log', $errors);
    }
    
    /**
     * Get a public-safe version of an error
     */
    private static function get_public_error($error) {
        if (!is_wp_error($error)) {
            return new WP_Error('unknown_error', 'An unexpected error occurred');
        }
        
        $code = $error->get_error_code();
        
        // Map internal error codes to user-friendly messages
        $public_errors = array(
            'api_connection_failed' => 'Could not connect to the AI service. Please try again later.',
            'invalid_api_key' => 'There is a configuration issue with the AI service. Please contact the administrator.',
            'rate_limited' => 'You have reached the maximum number of requests. Please try again later.',
            'invalid_prompt' => 'There was an issue with your request. Please try a different query.',
            'content_filter' => 'Your request was flagged by our content filter. Please modify your request.',
            'context_too_large' => 'Your request contains too much information. Please try a simpler query.',
            'permission_denied' => 'You do not have permission to perform this action.',
            'invalid_parameters' => 'Invalid parameters were provided for this request.',
        );
        
        if (isset($public_errors[$code])) {
            return new WP_Error($code, $public_errors[$code]);
        }
        
        // For unknown errors, return a generic message
        return new WP_Error('error', 'An error occurred while processing your request. Please try again.');
    }
}
```

## Request and Context Security

Ensure AI request and context data are handled securely.

### Context Data Security

Implement security for context data:

```php
// Example: Secure context data handling
class MPAI_Context_Security {
    /**
     * Filter context data for security
     */
    public static function filter_context($context) {
        if (!is_array($context)) {
            return array();
        }
        
        $filtered = array();
        
        // Process each section of the context
        foreach ($context as $key => $value) {
            switch ($key) {
                case 'user_info':
                    // Filter user information
                    $filtered[$key] = self::filter_user_info($value);
                    break;
                    
                case 'site_info':
                    // Filter site information
                    $filtered[$key] = self::filter_site_info($value);
                    break;
                    
                case 'memberpress_info':
                    // Filter MemberPress information
                    $filtered[$key] = self::filter_memberpress_info($value);
                    break;
                    
                default:
                    // General filtering for other sections
                    $filtered[$key] = self::filter_general_section($value);
                    break;
            }
        }
        
        return $filtered;
    }
    
    /**
     * Filter user information
     */
    private static function filter_user_info($user_info) {
        if (!is_array($user_info)) {
            return array();
        }
        
        $filtered = array();
        
        // Only include safe user information
        $safe_keys = array('ID', 'roles', 'display_name', 'locale');
        
        foreach ($safe_keys as $key) {
            if (isset($user_info[$key])) {
                $filtered[$key] = $user_info[$key];
            }
        }
        
        // Never include these, even if passed
        $unsafe_keys = array('user_pass', 'user_email', 'user_activation_key', 'session_tokens');
        
        foreach ($unsafe_keys as $key) {
            if (isset($filtered[$key])) {
                unset($filtered[$key]);
            }
        }
        
        return $filtered;
    }
    
    /**
     * Filter site information
     */
    private static function filter_site_info($site_info) {
        if (!is_array($site_info)) {
            return array();
        }
        
        $filtered = array();
        
        // Include basic site info
        $safe_keys = array('name', 'description', 'url', 'version', 'language');
        
        foreach ($safe_keys as $key) {
            if (isset($site_info[$key])) {
                $filtered[$key] = $site_info[$key];
            }
        }
        
        // Remove server info
        if (isset($filtered['server'])) {
            unset($filtered['server']);
        }
        
        return $filtered;
    }
    
    /**
     * Filter MemberPress information
     */
    private static function filter_memberpress_info($mp_info) {
        if (!is_array($mp_info)) {
            return array();
        }
        
        $filtered = array();
        
        // Include basic membership info
        if (isset($mp_info['memberships']) && is_array($mp_info['memberships'])) {
            $filtered['memberships'] = array();
            
            foreach ($mp_info['memberships'] as $membership) {
                $filtered_membership = array();
                
                // Only include safe membership data
                $safe_keys = array('ID', 'name', 'status', 'expiration');
                
                foreach ($safe_keys as $key) {
                    if (isset($membership[$key])) {
                        $filtered_membership[$key] = $membership[$key];
                    }
                }
                
                // Ensure no payment details are included
                if (isset($filtered_membership['payment_details'])) {
                    unset($filtered_membership['payment_details']);
                }
                
                $filtered['memberships'][] = $filtered_membership;
            }
        }
        
        // Only include transaction counts, not details
        if (isset($mp_info['transactions']) && is_array($mp_info['transactions'])) {
            $filtered['transaction_count'] = count($mp_info['transactions']);
        }
        
        return $filtered;
    }
    
    /**
     * Filter a general context section
     */
    private static function filter_general_section($section) {
        if (!is_array($section)) {
            return $section;
        }
        
        $filtered = array();
        
        // Process recursively
        foreach ($section as $key => $value) {
            // Skip any keys that look sensitive
            $lower_key = strtolower($key);
            $sensitive_patterns = array('password', 'secret', 'key', 'token', 'auth', 'credit', 'card', 'cvv', 'ssn', 'social', 'email', 'phone');
            
            $is_sensitive = false;
            foreach ($sensitive_patterns as $pattern) {
                if (strpos($lower_key, $pattern) !== false) {
                    $is_sensitive = true;
                    break;
                }
            }
            
            if ($is_sensitive) {
                continue;
            }
            
            // Process arrays recursively
            if (is_array($value)) {
                $filtered[$key] = self::filter_general_section($value);
            } else {
                $filtered[$key] = $value;
            }
        }
        
        return $filtered;
    }
}
```

## Plugin Extensibility Security

Ensure security when extending the plugin.

### Safe Extension Points

Create secure extension points:

```php
// Example: Secure extension point system
class MPAI_Extension_Manager {
    /**
     * Register extension point callbacks
     */
    public static function register($extension_point, $callback, $priority = 10, $accepted_args = 1) {
        // Validate extension point
        if (!self::is_valid_extension_point($extension_point)) {
            return false;
        }
        
        // Validate callback
        if (!is_callable($callback)) {
            return false;
        }
        
        // Register as WordPress filter
        return add_filter('mpai_extension_' . $extension_point, $callback, $priority, $accepted_args);
    }
    
    /**
     * Execute an extension point
     */
    public static function execute($extension_point, $data, ...$args) {
        // Validate extension point
        if (!self::is_valid_extension_point($extension_point)) {
            return $data;
        }
        
        // Apply filters with arguments
        return apply_filters('mpai_extension_' . $extension_point, $data, ...$args);
    }
    
    /**
     * Check if an extension point is valid
     */
    private static function is_valid_extension_point($extension_point) {
        // List of approved extension points
        $valid_points = array(
            'context',
            'prompt',
            'api_request',
            'api_response',
            'tool_registry',
            'tool_execution',
            'chat_response',
            'user_permissions',
            'content_filter',
        );
        
        return in_array($extension_point, $valid_points);
    }
    
    /**
     * Unregister an extension point callback
     */
    public static function unregister($extension_point, $callback, $priority = 10) {
        // Validate extension point
        if (!self::is_valid_extension_point($extension_point)) {
            return false;
        }
        
        return remove_filter('mpai_extension_' . $extension_point, $callback, $priority);
    }
}
```

## Security Testing

Implement security testing for the plugin.

### Penetration Testing

Regular penetration testing should focus on:

1. **Input Validation**: Test all input points with malicious data.
2. **Authorization Bypass**: Attempt to access functionality without proper permissions.
3. **API Security**: Test for API key leakage or improper API usage.
4. **Cross-Site Scripting (XSS)**: Test for script injection in AI responses.
5. **Cross-Site Request Forgery (CSRF)**: Test all forms and AJAX endpoints.

### Security Audit Checklist

Use this checklist for security audits:

- [ ] All user inputs are validated and sanitized
- [ ] API keys are stored securely and never exposed
- [ ] Proper capability checks are implemented for all actions
- [ ] CSRF protection via nonces is implemented for all forms/AJAX
- [ ] Error messages don't reveal sensitive information
- [ ] Data sent to API is minimized and contains no sensitive info
- [ ] Rate limiting is implemented to prevent abuse
- [ ] Logging system doesn't record sensitive data
- [ ] All AJAX endpoints validate nonces and check permissions
- [ ] Tool execution validates parameters and checks permissions
- [ ] Context data is filtered to remove sensitive information
- [ ] API responses are validated before processing
- [ ] Adequate error handling is implemented throughout
- [ ] No sensitive data is stored in client-side storage
- [ ] All integrated third-party code is reviewed for security issues

## Regular Security Updates

Maintain plugin security with regular updates.

### Security Patch Process

1. **Monitoring**: Regularly monitor for security issues in dependencies.
2. **Assessment**: Assess the impact and exploitability of vulnerabilities.
3. **Patching**: Develop and test security patches.
4. **Distribution**: Release patches through WordPress update system.
5. **Communication**: Notify users of security updates.

### Dependency Management

Keep dependencies updated:

```php
// Example: Dependency version checking
function mpai_check_dependencies() {
    $issues = array();
    
    // Check WordPress version
    global $wp_version;
    if (version_compare($wp_version, '5.8', '<')) {
        $issues[] = 'WordPress version is below 5.8. Please update WordPress.';
    }
    
    // Check PHP version
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        $issues[] = 'PHP version is below 7.4. Please update PHP.';
    }
    
    // Check MemberPress version if active
    if (function_exists('memberpress')) {
        $mp_version = MEPR_VERSION;
        if (version_compare($mp_version, '1.9.0', '<')) {
            $issues[] = 'MemberPress version is below 1.9.0. Please update MemberPress.';
        }
    }
    
    // Check third-party libraries
    $libraries = array(
        'vendor/openai-php/client/VERSION' => '1.5.0',
        'vendor/anthropic/anthropic/VERSION' => '1.0.0'
    );
    
    foreach ($libraries as $file => $min_version) {
        $full_path = MPAI_PLUGIN_DIR . '/' . $file;
        if (file_exists($full_path)) {
            $version = trim(file_get_contents($full_path));
            if (version_compare($version, $min_version, '<')) {
                $issues[] = basename(dirname($file)) . ' library is out of date. Please update the plugin.';
            }
        } else {
            $issues[] = 'Missing ' . basename(dirname($file)) . ' library. Please reinstall the plugin.';
        }
    }
    
    return $issues;
}

// Display admin notice for security issues
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $issues = mpai_check_dependencies();
    
    if (!empty($issues)) {
        echo '<div class="notice notice-error">';
        echo '<p><strong>MemberPress AI Assistant Security Notice:</strong></p>';
        echo '<ul>';
        
        foreach ($issues as $issue) {
            echo '<li>' . esc_html($issue) . '</li>';
        }
        
        echo '</ul>';
        echo '</div>';
    }
});
```

## Handling Sensitive Data

Special care must be taken with sensitive data.

### Data Masking

Implement data masking for sensitive information:

```php
// Example: Data masking class
class MPAI_Data_Masker {
    /**
     * Mask sensitive data in text
     */
    public static function mask_sensitive_data($text) {
        if (!is_string($text)) {
            return $text;
        }
        
        // Mask email addresses
        $text = preg_replace('/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6})/', '[EMAIL REDACTED]', $text);
        
        // Mask phone numbers (various formats)
        $text = preg_replace('/(\+\d{1,3}[-\.\s]?)?\(?\d{3}\)?[-\.\s]?\d{3}[-\.\s]?\d{4}/', '[PHONE REDACTED]', $text);
        
        // Mask credit card numbers
        $text = preg_replace('/\b(?:\d[ -]*?){13,16}\b/', '[CREDIT CARD REDACTED]', $text);
        
        // Mask social security numbers
        $text = preg_replace('/\b\d{3}[-\.\s]?\d{2}[-\.\s]?\d{4}\b/', '[SSN REDACTED]', $text);
        
        // Mask API keys (general pattern)
        $text = preg_replace('/(api[_\-]?key|apikey|access[_\-]?key|app[_\-]?key)(["\'\s:=]+)[a-zA-Z0-9_\-]{20,}/', '$1$2[API KEY REDACTED]', $text);
        
        return $text;
    }
    
    /**
     * Check if text contains sensitive data
     */
    public static function contains_sensitive_data($text) {
        if (!is_string($text)) {
            return false;
        }
        
        // Email pattern
        if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}/', $text)) {
            return true;
        }
        
        // Phone pattern
        if (preg_match('/(\+\d{1,3}[-\.\s]?)?\(?\d{3}\)?[-\.\s]?\d{3}[-\.\s]?\d{4}/', $text)) {
            return true;
        }
        
        // Credit card pattern
        if (preg_match('/\b(?:\d[ -]*?){13,16}\b/', $text)) {
            return true;
        }
        
        // SSN pattern
        if (preg_match('/\b\d{3}[-\.\s]?\d{2}[-\.\s]?\d{4}\b/', $text)) {
            return true;
        }
        
        // API key pattern
        if (preg_match('/(api[_\-]?key|apikey|access[_\-]?key|app[_\-]?key)(["\'\s:=]+)[a-zA-Z0-9_\-]{20,}/', $text)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Process AI response to ensure no sensitive data
     */
    public static function process_response($response) {
        if (!is_string($response)) {
            return $response;
        }
        
        // Check if response contains sensitive data
        if (self::contains_sensitive_data($response)) {
            // Log the incident (without the sensitive data)
            error_log('MPAI: Potential sensitive data detected in AI response');
            
            // Mask the data
            return self::mask_sensitive_data($response);
        }
        
        return $response;
    }
}
```

## Document Revision History

| Date | Version | Changes |
|------|---------|---------|
| 2025-04-06 | 1.0.0 | Initial document creation |