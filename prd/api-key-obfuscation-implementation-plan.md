# PRD: API Key Obfuscation System Implementation

## 1. Executive Summary

This document outlines the requirements for implementing the obfuscated key system as the default method for API access in the MemberPress AI Assistant plugin. The change will improve security by eliminating the need to store API keys in the WordPress database while maintaining compatibility with a future addon plugin that will allow users to override the obfuscated keys with their own API keys.

## 2. Problem Statement

Currently, the MemberPress AI Assistant plugin:
- Has a sophisticated obfuscated key management system implemented in `MPAIKeyManager`
- However, it's not using this system by default and instead relies on API keys stored in WordPress settings
- Storing API keys in the database poses security risks
- Users who want to use their own API keys have no clear separation between the default system and their custom keys

## 3. Goals and Objectives

### Primary Goals
1. Remove API key fields from the settings interface
2. Make the obfuscated key system the default and only built-in method for API access
3. Implement hooks to allow a separate addon plugin to override the obfuscated keys

## 4. Security Implications and Mitigations

### 4.1 Security Benefits

#### Elimination of Stored API Keys
- API keys will no longer be stored in the WordPress database
- Reduces risk of keys being exposed through database breaches
- Eliminates risk of keys being exposed in database backups

#### Split Key Storage Protection
- API keys are fragmented into multiple components stored in different locations
- No single component contains the full key
- Components are derived from various sources (hardcoded, site-specific, file-specific, user-specific)
- Makes extraction of complete keys extremely difficult

#### Runtime-Only Key Assembly
- Complete keys only exist in memory during API calls
- Keys are never persisted to storage in assembled form
- Reduces exposure window for complete keys

### 4.2 Security Challenges and Mitigations

#### Obfuscation vs. True Security
**Challenge**: Obfuscation alone is not true security and can be reverse-engineered
**Mitigation**: 
- Multiple layers of obfuscation make reverse engineering more difficult
- Server-side verification ensures keys can only be used in legitimate contexts
- Regular key rotation can be implemented in future updates

#### Plugin File Integrity
**Challenge**: If plugin files are modified, the obfuscation could be compromised
**Mitigation**:
- File integrity checks verify plugin files haven't been tampered with
- Component derivation includes file checksums to detect modifications
- WordPress core file verification can be leveraged

#### Unauthorized Access
**Challenge**: Unauthorized users might attempt to extract keys
**Mitigation**:
- User capability checks ensure only authorized users can trigger API calls
- WordPress nonce verification prevents CSRF attacks
- Request origin validation ensures requests come from the same server

#### Key Exposure in Transit
**Challenge**: Keys could be exposed during API calls
**Mitigation**:
- All API calls use HTTPS encryption
- Keys are never logged or exposed in client-side code
- Error messages don't include key information

### 4.3 Security Implementation Details

#### Secure Component Storage
- Hardcoded components use base64 encoding and custom obfuscation
- Installation-specific components use WordPress salts and site URL
- File-based components use secure hashing of plugin files
- Admin-specific components use secure hashing of user data

#### Runtime Security Checks
- Verify WordPress admin context
- Verify user capabilities
- Verify request origin
- Verify plugin file integrity
- Implement rate limiting

#### Secure Key Assembly
- Keys are assembled only when needed for API calls
- Assembly occurs in memory only
- Different assembly algorithms for different services
- Additional runtime entropy added during assembly

## 5. Technical Requirements

### 5.1 Core Changes

#### Remove API Key Settings
- Remove OpenAI API key field from settings
- Remove Anthropic API key field from settings
- Update settings UI to reflect these changes

#### Activate Obfuscated Key System
- Modify `MPAIKeyManager::test_api_connection()` to use the obfuscated key system instead of settings
- Update any other methods that currently use settings keys to use the obfuscated system

#### Add Extension Points
- Create a filter hook for each service to allow addons to override the API key
- Implement proper priority handling to ensure addon keys take precedence

### 5.2 Security Enhancements
- Implement additional obfuscation techniques for hardcoded components
- Add more comprehensive security context verification
- Implement detailed error logging (without exposing keys)
- Add monitoring for unusual API usage patterns

## 6. Technical Implementation

### 6.1 Key Manager Modifications

```php
/**
 * Get API key for specified service
 * 
 * @param string $service_type The service type (openai, anthropic)
 * @return string|false The API key or false on failure
 */
public function get_api_key($service_type) {
    // Allow addons to override the API key
    $override_key = apply_filters('mpai_override_api_key_' . $service_type, null);
    
    // If an addon has provided a key, use it
    if ($override_key !== null) {
        return $override_key;
    }
    
    // Otherwise, use the obfuscated key system
    // Verify security context first
    if (!$this->verify_security_context()) {
        $this->log_error('Security context verification failed');
        return false;
    }
    
    // Collect key components for the specified service
    $this->collect_key_components($service_type);
    
    // Assemble and return the key
    $key = $this->assemble_key($service_type);
    
    // Validate key format
    if (!$this->validate_key_format($service_type, $key)) {
        $this->log_error('Invalid key format for ' . $service_type);
        return false;
    }
    
    return $key;
}

/**
 * Validate key format for a specific service
 *
 * @param string $service_type The service type
 * @param string $key The API key
 * @return bool Whether the key format is valid
 */
private function validate_key_format($service_type, $key) {
    if (empty($key)) {
        return false;
    }
    
    switch ($service_type) {
        case self::SERVICE_OPENAI:
            return strpos($key, 'sk-') === 0;
        
        case self::SERVICE_ANTHROPIC:
            return strpos($key, 'sk-ant-') === 0;
        
        default:
            return true;
    }
}

/**
 * Verify the security context for API key access
 *
 * @return bool True if security context is valid, false otherwise
 */
private function verify_security_context() {
    // Must be in WordPress admin context
    if (!is_admin()) {
        $this->log_error('Not in admin context');
        return false;
    }
    
    // User must have appropriate capabilities
    if (!current_user_can('manage_options')) {
        $this->log_error('User lacks required capabilities');
        return false;
    }
    
    // Verify request origin
    if (!$this->verify_request_origin()) {
        $this->log_error('Invalid request origin');
        return false;
    }
    
    // Verify plugin integrity
    if (!$this->verify_plugin_integrity()) {
        $this->log_error('Plugin integrity check failed');
        return false;
    }
    
    // Check rate limiting
    if (!$this->check_rate_limit()) {
        $this->log_error('Rate limit exceeded');
        return false;
    }
    
    return true;
}

/**
 * Test API connection for a specific service
 *
 * @param string $service_type The service type
 * @return array The test result
 */
public function test_api_connection($service_type) {
    // Get the API key using the obfuscated system or addon override
    $api_key = $this->get_api_key($service_type);
    
    if (!$api_key) {
        return [
            'success' => false,
            'message' => 'Could not retrieve API key. Please check your configuration.'
        ];
    }
    
    // Service-specific test endpoints and parameters
    switch ($service_type) {
        case self::SERVICE_OPENAI:
            return $this->test_openai_connection($api_key);
        
        case self::SERVICE_ANTHROPIC:
            return $this->test_anthropic_connection($api_key);
        
        default:
            return [
                'success' => false,
                'message' => 'Unsupported service type'
            ];
    }
}

/**
 * Log an error message securely (without exposing keys)
 *
 * @param string $message Error message
 * @return void
 */
private function log_error($message) {
    if ($this->logger) {
        $this->logger->error('Key Manager: ' . $message);
    }
}
```

### 6.2 Settings Model Modifications

```php
/**
 * Default settings values
 *
 * @var array
 */
private $defaults = [
    // General settings
    'chat_enabled' => true,
    
    // Chat settings
    'chat_location' => 'admin_only',
    'chat_position' => 'bottom_right',
    
    // Access settings
    'user_roles' => ['administrator'],
    
    // API settings
    'primary_api' => 'openai',
    
    // Consent settings
    'consent_required' => true,
];
```

### 6.3 Settings Controller Modifications

```php
/**
 * Register API section and fields
 *
 * @return void
 */
protected function register_api_section() {
    // Register API Settings section
    add_settings_section(
        'mpai_api_section',
        __('API Settings', 'memberpress-ai-assistant'),
        [$this->view, 'render_api_section'],
        $this->page_slug
    );
    
    // Add OpenAI Model field
    add_settings_field(
        'mpai_openai_model',
        __('OpenAI Model', 'memberpress-ai-assistant'),
        [$this, 'render_openai_model_field'],
        $this->page_slug,
        'mpai_api_section'
    );
    
    // Add OpenAI Temperature field
    add_settings_field(
        'mpai_openai_temperature',
        __('OpenAI Temperature', 'memberpress-ai-assistant'),
        [$this, 'render_openai_temperature_field'],
        $this->page_slug,
        'mpai_api_section'
    );
    
    // Add OpenAI Max Tokens field
    add_settings_field(
        'mpai_openai_max_tokens',
        __('OpenAI Max Tokens', 'memberpress-ai-assistant'),
        [$this, 'render_openai_max_tokens_field'],
        $this->page_slug,
        'mpai_api_section'
    );
    
    // Add Anthropic Model field
    add_settings_field(
        'mpai_anthropic_model',
        __('Anthropic Model', 'memberpress-ai-assistant'),
        [$this, 'render_anthropic_model_field'],
        $this->page_slug,
        'mpai_api_section'
    );
    
    // Add Anthropic Temperature field
    add_settings_field(
        'mpai_anthropic_temperature',
        __('Anthropic Temperature', 'memberpress-ai-assistant'),
        [$this, 'render_anthropic_temperature_field'],
        $this->page_slug,
        'mpai_api_section'
    );
    
    // Add Anthropic Max Tokens field
    add_settings_field(
        'mpai_anthropic_max_tokens',
        __('Anthropic Max Tokens', 'memberpress-ai-assistant'),
        [$this, 'render_anthropic_max_tokens_field'],
        $this->page_slug,
        'mpai_api_section'
    );
    
    // Add Primary AI Provider field
    add_settings_field(
        'mpai_primary_api',
        __('Primary AI Provider', 'memberpress-ai-assistant'),
        [$this, 'render_primary_api_field'],
        $this->page_slug,
        'mpai_api_section'
    );
    
    // Add API Key Information field
    add_settings_field(
        'mpai_api_key_info',
        __('API Keys', 'memberpress-ai-assistant'),
        [$this, 'render_api_key_info_field'],
        $this->page_slug,
        'mpai_api_section'
    );
}

/**
 * Render the API key information field
 *
 * @return void
 */
public function render_api_key_info_field() {
    ?>
    <div class="mpai-api-key-info">
        <p><?php _e('This plugin includes built-in API keys that allow you to use AI features without providing your own keys.', 'memberpress-ai-assistant'); ?></p>
        <p><?php _e('If you prefer to use your own API keys, you can install the "MemberPress AI Assistant - Custom Keys" addon plugin.', 'memberpress-ai-assistant'); ?></p>
    </div>
    <?php
}
```

## 7. Extension Points

The following hooks will be available for the addon plugin:

1. `mpai_override_api_key_openai` - Filter to override the OpenAI API key
   ```php
   add_filter('mpai_override_api_key_openai', function($key) {
       return 'your-openai-api-key';
   });
   ```

2. `mpai_override_api_key_anthropic` - Filter to override the Anthropic API key
   ```php
   add_filter('mpai_override_api_key_anthropic', function($key) {
       return 'your-anthropic-api-key';
   });
   ```

3. `mpai_before_api_request` - Action before making an API request
   ```php
   add_action('mpai_before_api_request', function($service_type, $endpoint) {
       // Log or monitor API usage
   }, 10, 2);
   ```

4. `mpai_after_api_request` - Action after making an API request
   ```php
   add_action('mpai_after_api_request', function($service_type, $endpoint, $response) {
       // Process or log response
   }, 10, 3);
   ```

5. `mpai_api_request_error` - Action when an API request fails
   ```php
   add_action('mpai_api_request_error', function($service_type, $endpoint, $error) {
       // Handle or log error
   }, 10, 3);
   ```

## 8. Implementation Timeline

| Phase | Description | Tasks | Estimated Time |
|-------|-------------|-------|----------------|
| 1 | Remove API Key Settings | - Remove API key fields from settings UI<br>- Update settings model<br>- Add informational text about built-in keys | 1 day |
| 2 | Activate Obfuscated Key System | - Update `test_api_connection()` method<br>- Enhance security verification<br>- Implement key validation | 2 days |
| 3 | Add Extension Points | - Add filter hooks for key overrides<br>- Add action hooks for request lifecycle<br>- Document hooks for addon developers | 1 day |
| 4 | Security Enhancements | - Improve obfuscation techniques<br>- Enhance security context verification<br>- Implement secure error logging | 2 days |
| 5 | Testing | - Test with obfuscated keys<br>- Test with mock addon<br>- Security testing | 2 days |
| 6 | Documentation | - Update internal documentation<br>- Create addon development guide<br>- Document security measures | 1 day |

## 9. Success Criteria

1. All API calls use the obfuscated key system by default
2. No API keys stored in the WordPress database
3. Clear extension points for the addon plugin
4. No security vulnerabilities introduced
5. No decrease in functionality or performance