# REST API Endpoints Documentation

## Overview

The MemberPress AI Assistant provides both traditional WordPress AJAX endpoints and modern REST API endpoints for integrating with the chat interface and accessing system functionality.

## Authentication & Security

### Authentication Methods

1. **WordPress Nonces**: For AJAX requests
2. **WordPress User Sessions**: For logged-in users
3. **Capability Checks**: For admin functionality

### Security Headers

All API responses include appropriate security headers:
- `Content-Type: application/json`
- WordPress nonce verification
- User authentication checks
- Permission validation

## REST API Endpoints

### Base URL

All REST API endpoints are prefixed with:
```
/wp-json/memberpress-ai/v1/
```

### Chat Endpoint

#### POST `/wp-json/memberpress-ai/v1/chat`

Processes chat messages through the AI system.

**Request Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `message` | string | Yes | The user's chat message |
| `conversation_id` | string | No | Unique conversation identifier |
| `load_history` | boolean | No | Whether to load conversation history |
| `clear_history` | boolean | No | Whether to clear conversation history |
| `user_logged_in` | boolean | No | User authentication status |

**Request Example:**

```javascript
// Using fetch API
const response = await fetch('/wp-json/memberpress-ai/v1/chat', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': wpApiSettings.nonce
    },
    body: JSON.stringify({
        message: 'Create a new membership product',
        conversation_id: 'conv_12345',
        load_history: false
    })
});

const data = await response.json();
```

**Response Format:**

```json
{
    "success": true,
    "data": {
        "response": "I'll help you create a new membership product...",
        "conversation_id": "conv_12345",
        "agent_used": "memberpress_agent",
        "response_type": "text",
        "formatted_response": "<p>I'll help you create...</p>",
        "timestamp": 1640995200
    }
}
```

**Error Response:**

```json
{
    "success": false,
    "error": "invalid_message",
    "message": "Message cannot be empty",
    "code": 400
}
```

**Permission Callback:**

The endpoint checks:
- User must be logged in
- User must have appropriate capabilities for requested operations
- Rate limiting (if configured)

## AJAX Endpoints

All AJAX endpoints follow the WordPress AJAX pattern and are prefixed with `wp_ajax_mpai_`.

### Base AJAX URL

```
/wp-admin/admin-ajax.php
```

### Chat Processing

#### `mpai_process_chat`

Legacy chat processing endpoint.

**Request Method:** POST

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `action` | string | Yes | Must be 'mpai_process_chat' |
| `message` | string | Yes | The chat message |
| `nonce` | string | Yes | WordPress nonce for security |

**Example Request:**

```javascript
jQuery.ajax({
    url: ajaxurl,
    type: 'POST',
    data: {
        action: 'mpai_process_chat',
        message: 'Help me create a membership',
        nonce: mpai_ajax.nonce
    },
    success: function(response) {
        if (response.success) {
            console.log(response.data);
        }
    }
});
```

**Response Format:**

```json
{
    "success": true,
    "data": {
        "response": "I'll help you create a membership...",
        "formatted_response": "<p>I'll help you create...</p>",
        "response_type": "text",
        "agent_used": "memberpress_agent"
    }
}
```

#### `mpai_chat_request`

Modern modular chat processing endpoint.

**Request Method:** POST

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `action` | string | Yes | Must be 'mpai_chat_request' |
| `endpoint` | string | No | Specific endpoint within chat system |
| `data` | string | Yes | JSON-encoded request data |
| `nonce` | string | Yes | WordPress nonce for security |

**Example Request:**

```javascript
const requestData = {
    message: 'List all memberships',
    context: {
        user_id: currentUserId,
        page: 'dashboard'
    },
    load_history: false,
    conversation_id: 'conv_' + Date.now()
};

jQuery.ajax({
    url: ajaxurl,
    type: 'POST',
    data: {
        action: 'mpai_chat_request',
        endpoint: 'chat',
        data: JSON.stringify(requestData),
        nonce: mpai_ajax.nonce
    },
    success: function(response) {
        if (response.success) {
            displayChatResponse(response.data);
        }
    },
    error: function(xhr, status, error) {
        console.error('Chat request failed:', error);
    }
});
```

**Advanced Request with Context:**

```javascript
// Request with specific agent targeting
const agentSpecificRequest = {
    message: 'Create a premium membership for $29.99/month',
    context: {
        user_id: currentUserId,
        preferred_agent: 'memberpress_agent',
        admin_context: true
    },
    conversation_id: sessionStorage.getItem('mpai_conversation_id')
};

jQuery.ajax({
    url: ajaxurl,
    type: 'POST',
    data: {
        action: 'mpai_chat_request',
        data: JSON.stringify(agentSpecificRequest),
        nonce: mpai_ajax.nonce
    },
    success: function(response) {
        if (response.success) {
            // Handle different response types
            switch (response.data.response_type) {
                case 'interactive':
                    renderInteractiveResponse(response.data);
                    break;
                case 'blog_post':
                    renderBlogPostResponse(response.data);
                    break;
                default:
                    renderTextResponse(response.data);
            }
        }
    }
});
```

### Chat Interface

#### `mpai_get_chat_interface`

Retrieves the chat interface HTML for dynamic loading.

**Request Method:** POST

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `action` | string | Yes | Must be 'mpai_get_chat_interface' |
| `nonce` | string | Yes | WordPress nonce for security |

**Response:**

```json
{
    "success": true,
    "data": {
        "html": "<div class='mpai-chat-interface'>...</div>",
        "assets": {
            "css": {
                "mpai-chat": "path/to/chat.css",
                "mpai-blog-post": "path/to/blog-post.css"
            },
            "js": {
                "mpai-chat": "path/to/chat.js",
                "mpai-xml-processor": "path/to/xml-processor.js"
            }
        },
        "config": {
            "ajax_url": "/wp-admin/admin-ajax.php",
            "nonce": "abc123",
            "user_id": 1
        }
    }
}
```

### API Testing

#### `mpai_test_api_connection`

Tests connectivity to AI provider APIs.

**Request Method:** POST

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `action` | string | Yes | Must be 'mpai_test_api_connection' |
| `provider` | string | Yes | AI provider ('openai' or 'anthropic') |
| `api_key` | string | Yes | API key to test |
| `nonce` | string | Yes | WordPress nonce for security |

**Permission Required:** `manage_options`

**Example Request:**

```javascript
jQuery.ajax({
    url: ajaxurl,
    type: 'POST',
    data: {
        action: 'mpai_test_api_connection',
        provider: 'openai',
        api_key: 'sk-...',
        nonce: mpai_settings.nonce
    },
    success: function(response) {
        if (response.success) {
            alert('API connection successful!');
        } else {
            alert('API connection failed: ' + response.message);
        }
    }
});
```

## Frontend JavaScript API

### Modern Chat Interface SDK

The frontend provides a comprehensive JavaScript SDK for interacting with the chat system:

```javascript
// Initialize chat interface with advanced configuration
const chatInterface = new MPAIChatInterface({
    container: '#mpai-chat-container',
    config: {
        ajax_url: mpai_chat_config.ajax_url,
        nonce: mpai_chat_config.nonce,
        user_id: mpai_chat_config.user_id,
        auto_scroll: true,
        typing_indicator: true,
        conversation_persistence: true
    }
});

// Send a message with context
chatInterface.sendMessage('Hello, assistant!', {
    context: {
        page: 'dashboard',
        user_role: 'administrator'
    },
    conversation_id: 'conv_12345'
})
.then(response => {
    console.log('Response:', response);
    
    // Handle specific response types
    if (response.response_type === 'interactive') {
        chatInterface.renderInteractiveElements(response.interactions);
    }
})
.catch(error => {
    console.error('Error:', error);
    chatInterface.showError('Failed to send message');
});

// Advanced conversation management
chatInterface.loadHistory('conv_12345', {
    limit: 50,
    include_context: true
});

// Clear conversation with confirmation
chatInterface.clearConversation({
    confirm: true,
    preserve_context: false
});

// Download conversation in various formats
chatInterface.downloadConversation('html', {
    include_timestamps: true,
    include_agent_info: true,
    format_code: true
});

// Real-time features
chatInterface.enableTypingIndicator();
chatInterface.enableAutoScroll();
chatInterface.enableNotifications();
```

### Core Chat Components

The system includes modular JavaScript components:

#### Message Handler

```javascript
// Custom message handling
import { MessageFactory } from 'mpai-chat-core';

const messageFactory = new MessageFactory();

// Register custom message handler
messageFactory.registerHandler('custom_type', (message, context) => {
    return `<div class="custom-message">${message.content}</div>`;
});

// Process message
const processedMessage = messageFactory.createMessage({
    type: 'custom_type',
    content: 'Custom message content',
    metadata: { source: 'api' }
});
```

#### State Management

```javascript
// Chat state management
import { StateManager } from 'mpai-chat-core';

const stateManager = new StateManager();

// Subscribe to state changes
stateManager.subscribe('conversation', (newState) => {
    console.log('Conversation state updated:', newState);
});

// Update state
stateManager.setState('conversation', {
    id: 'conv_12345',
    messages: [],
    context: {}
});
```

#### API Client

```javascript
// Low-level API client
import { ApiClient } from 'mpai-chat-core';

const apiClient = new ApiClient({
    baseUrl: mpai_chat_config.ajax_url,
    nonce: mpai_chat_config.nonce
});

// Make authenticated requests
const response = await apiClient.post('mpai_chat_request', {
    message: 'Hello',
    context: { user_id: 123 }
});
```

### Event Handling

```javascript
// Listen for chat events
document.addEventListener('mpai:message:sent', function(event) {
    console.log('Message sent:', event.detail.message);
});

document.addEventListener('mpai:response:received', function(event) {
    console.log('Response received:', event.detail.response);
});

document.addEventListener('mpai:error', function(event) {
    console.error('Chat error:', event.detail.error);
});
```

## Response Formats

### Standard Success Response

```json
{
    "success": true,
    "data": {
        // Response data specific to the endpoint
    },
    "message": "Operation completed successfully",
    "timestamp": 1640995200
}
```

### Standard Error Response

```json
{
    "success": false,
    "error": "error_code",
    "message": "Human-readable error message",
    "details": {
        // Additional error details
    },
    "code": 400
}
```

### Chat Response Types

#### Text Response

```json
{
    "success": true,
    "data": {
        "response": "Plain text response",
        "response_type": "text",
        "formatted_response": "<p>HTML formatted response</p>",
        "agent_used": "system_agent"
    }
}
```

#### Interactive Response

```json
{
    "success": true,
    "data": {
        "response": "Interactive response",
        "response_type": "interactive",
        "interactions": [
            {
                "type": "button",
                "label": "Create Membership",
                "action": "create_membership",
                "data": {"type": "premium"}
            }
        ],
        "agent_used": "memberpress_agent"
    }
}
```

#### Blog Post Response

```json
{
    "success": true,
    "data": {
        "response": "Blog post content with XML tags",
        "response_type": "blog_post",
        "formatted_response": "<article>...</article>",
        "post_data": {
            "title": "Post Title",
            "content": "Post content",
            "excerpt": "Post excerpt"
        },
        "agent_used": "content_agent"
    }
}
```

## Error Codes

### Common Error Codes

| Code | Description |
|------|-------------|
| `invalid_request` | Request format is invalid |
| `missing_parameters` | Required parameters are missing |
| `invalid_nonce` | Security nonce verification failed |
| `unauthorized` | User not authorized for this operation |
| `rate_limited` | Too many requests from this user |
| `service_unavailable` | AI service is temporarily unavailable |
| `processing_error` | Error occurred while processing request |

### Chat-Specific Error Codes

| Code | Description |
|------|-------------|
| `empty_message` | Message cannot be empty |
| `invalid_conversation` | Conversation ID is invalid |
| `agent_selection_failed` | Could not select appropriate agent |
| `tool_execution_failed` | Tool execution encountered an error |
| `context_error` | Error managing conversation context |

## Rate Limiting

### Default Limits

- **Authenticated Users**: 60 requests per minute
- **Admin Users**: 120 requests per minute
- **Chat Requests**: 30 per minute per conversation

### Rate Limit Headers

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1640995260
```

### Rate Limit Exceeded Response

```json
{
    "success": false,
    "error": "rate_limit_exceeded",
    "message": "Too many requests. Please try again later.",
    "retry_after": 60
}
```

## Webhook Support

### Chat Events

The system can send webhooks for chat events:

```json
POST /your-webhook-endpoint
{
    "event": "chat.message.processed",
    "data": {
        "conversation_id": "conv_12345",
        "user_id": 1,
        "message": "User message",
        "response": "AI response",
        "agent_used": "memberpress_agent",
        "processing_time": 1.5
    },
    "timestamp": 1640995200
}
```

### Configuration

Webhooks can be configured through the admin interface or programmatically:

```php
// Register webhook endpoint
add_filter('mpai_webhook_endpoints', function($endpoints) {
    $endpoints['chat_events'] = 'https://your-site.com/webhook';
    return $endpoints;
});

// Custom webhook events
do_action('mpai_webhook', 'custom.event', [
    'data' => $custom_data
]);
```

## Client Libraries

### PHP Client

```php
use MemberpressAiAssistant\Client\RestClient;

$client = new RestClient([
    'base_url' => 'https://your-site.com',
    'api_key' => 'your-api-key'
]);

$response = $client->chat([
    'message' => 'Hello, assistant!',
    'conversation_id' => 'conv_12345'
]);
```

### JavaScript Client

```javascript
import MPAIClient from 'memberpress-ai-client';

const client = new MPAIClient({
    baseUrl: 'https://your-site.com',
    apiKey: 'your-api-key'
});

const response = await client.chat({
    message: 'Hello, assistant!',
    conversationId: 'conv_12345'
});
```

## Testing

### Unit Testing Endpoints

```php
class RestApiTest extends WP_UnitTestCase {
    public function test_chat_endpoint() {
        $request = new WP_REST_Request('POST', '/memberpress-ai/v1/chat');
        $request->set_param('message', 'Test message');
        
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        $this->assertTrue($response->get_data()['success']);
    }
}
```

### Integration Testing

```javascript
// Jest test example
describe('Chat API', () => {
    test('should process chat message', async () => {
        const response = await fetch('/wp-json/memberpress-ai/v1/chat', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': window.wpApiSettings.nonce
            },
            body: JSON.stringify({
                message: 'Test message'
            })
        });
        
        const data = await response.json();
        expect(data.success).toBe(true);
        expect(data.data.response).toBeDefined();
    });
});
```

## Best Practices

### API Design
1. **Consistent Response Format**: Always use the standard success/error format
2. **Proper HTTP Status Codes**: Use appropriate status codes for different scenarios
3. **Comprehensive Error Messages**: Provide clear, actionable error messages
4. **Security First**: Always validate input and check permissions

### Performance
1. **Caching**: Implement appropriate caching for expensive operations
2. **Pagination**: Use pagination for large data sets
3. **Rate Limiting**: Implement rate limiting to prevent abuse
4. **Async Processing**: Use asynchronous processing for long-running operations

### Integration
1. **Backwards Compatibility**: Maintain backwards compatibility when updating APIs
2. **Versioning**: Use API versioning for breaking changes
3. **Documentation**: Keep API documentation up to date
4. **Testing**: Implement comprehensive API testing

This REST API provides a robust foundation for integrating with the MemberPress AI Assistant system from various platforms and applications.