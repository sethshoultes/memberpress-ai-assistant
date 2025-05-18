# AI API Key System Documentation (CONFIDENTIAL)

> **IMPORTANT**: This documentation contains sensitive information about the AI API key obfuscation system and should be excluded from public documentation at build time.

## Overview

The MemberPress AI Assistant uses a split key storage approach to allow users to interact with AI services (OpenAI and Anthropic) without requiring them to provide their own API keys. This system securely manages API keys by fragmenting them into multiple components stored in different locations, making the plugin more accessible to users while maintaining security.

## Key Obfuscation System

### Architecture

The API key obfuscation system is implemented in the `MPAIKeyManager` class (`src/Admin/MPAIKeyManager.php`) and consists of several components:

1. **Split Key Storage**: API keys are fragmented into multiple components
2. **Component Collection**: Components are collected from different sources
3. **Key Assembly**: Components are assembled into a complete API key
4. **Security Verification**: Basic security checks are performed
5. **API Connection Testing**: Methods to test API connections

### Key Components

The system collects four key components for each service:

1. **Service-Specific Obfuscated Component**: A hardcoded component specific to each service (OpenAI, Anthropic)
2. **Installation-Specific Component**: Derived from the site URL and WordPress salt
3. **File-Based Component**: Derived from the plugin file checksum
4. **Admin-Specific Component**: Derived from the current user's email and WordPress salt

### Key Collection Process

When the plugin needs to make an API call to an AI service:

1. The system verifies the security context
2. It collects the four key components for the specified service
3. The components are assembled into a complete API key
4. The key is used for the API call and then discarded

### Security Measures

The system implements several basic security measures:

1. **Component Fragmentation**: Keys are split into multiple components
2. **WordPress Integration**: Components are tied to the WordPress installation
3. **User Verification**: Components are tied to the current user
4. **File Integrity**: Components are tied to the plugin file integrity

## Implementation Details

### Key Manager

The `MPAIKeyManager` class handles the API key management:

```php
// Get an API key for a service
$api_key = $key_manager->get_api_key('openai');

// Test API connection
$result = $key_manager->test_api_connection('openai');
```

### Component Collection

The system collects key components from different sources:

1. **Obfuscated Component**: Retrieved from hardcoded, base64-encoded strings
2. **Installation Component**: Generated from site URL and WordPress salt
3. **File Component**: Generated from plugin file checksum
4. **Admin Component**: Generated from user email and WordPress salt

### Key Assembly

The system assembles the key components into a complete API key:

1. For OpenAI: Components are combined with a prefix of "sk-"
2. For Anthropic: Components are combined with a prefix of "sk-ant-"
3. Additional runtime entropy is added for security

### API Connection Testing

The system includes methods to test API connections:

1. For OpenAI: Tests connection to the models endpoint
2. For Anthropic: Tests connection to the completion endpoint
3. Returns success/failure status and relevant messages

## User Experience

From the user's perspective, the obfuscated key system is completely transparent:

1. Users install and activate the plugin
2. They can immediately start using the AI features
3. No API key configuration is required

If a user prefers to use their own API keys:

1. Navigate to AI Assistant > Settings > API
2. Enter their OpenAI or Anthropic API keys
3. The system will use their keys instead of the obfuscated keys

## Development Guidelines

When working with the API key system:

1. **Never log API keys**: Even in debug mode, keys should never be logged
2. **Use the Key Manager**: Always use `MPAIKeyManager` for key operations
3. **Maintain component separation**: Keep key components separate
4. **Secure all endpoints**: Any endpoint that uses keys must be properly secured

## Conclusion

The split key storage approach allows the MemberPress AI Assistant to provide AI capabilities to users without requiring them to obtain and manage their own API keys. This significantly improves the user experience while maintaining a basic level of security.

By implementing component fragmentation and basic security checks, the system protects API keys while making advanced AI features accessible to all MemberPress users.