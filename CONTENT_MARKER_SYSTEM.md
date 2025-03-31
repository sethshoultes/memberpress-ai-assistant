# Content Marker System

## Overview

The Content Marker System provides a robust solution for identifying and extracting specific content types in chat conversations. This system addresses the challenge of accurately retrieving blog posts, pages, and other structured content when a user wants to publish or modify them.

## Problem Solved

Previously, the system attempted to extract content from the latest assistant message when publishing a post, but this was problematic because:

1. The latest message often contains only a simple acknowledgment like "Let me publish that for you"
2. The actual content was typically in a previous message
3. Relying on position-based retrieval (latest vs. previous) was brittle

## Implementation

The Content Marker System solves these issues by:

1. **Automatically tagging content** with unique markers when it's generated
2. **Adding HTML comment markers** with timestamps to identify content types
3. **Implementing smart content detection** to recognize different content types
4. **Creating a prioritized retrieval system** with multiple fallbacks

### Key Components

#### 1. Content Type Detection

```php
// Check for blog post content patterns
if (preg_match('/(?:#+\s*Title:?|Title:)\s*([^\n]+)/i', $message_content) ||
    (preg_match('/^#+\s*([^\n]+)/i', $message_content) && 
     preg_match('/introduction|summary|overview|content|body|conclusion/i', $message_content))) {
    
    // This looks like a blog post or article
    $modified_content = $this->add_content_marker($message_content, 'blog-post');
    error_log('MPAI: Added blog-post marker to response');
}
```

#### 2. Content Marker Addition

```php
private function add_content_marker($response, $type) {
    $timestamp = time();
    $marker = "<!-- #create-{$type}-{$timestamp} -->";
    
    // We'll add the marker at the very end of the content
    return $response . "\n\n" . $marker;
}
```

#### 3. Marker-Based Content Retrieval

```php
public function find_message_with_content_marker($type) {
    // Implementation...
    $marker_pattern = '/<!--\s*#create-' . preg_quote($type, '/') . '-\d+\s*-->/i';
    
    foreach ($messages_copy as $message) {
        // Check if this message has the marker we're looking for
        if (preg_match($marker_pattern, $message['content'])) {
            // Create a copy of the message
            $cleaned_message = $message;
            
            // Remove the marker from the content before returning
            $cleaned_message['content'] = preg_replace($marker_pattern, '', $cleaned_message['content']);
            
            return $cleaned_message;
        }
    }
}
```

#### 4. Prioritized Content Finding

```php
// First approach: look for content marker
if (method_exists($this->chat_instance, 'find_message_with_content_marker')) {
    $message_to_use = $this->chat_instance->find_message_with_content_marker($content_type);
    if ($message_to_use) {
        error_log('MPAI: Using message with ' . $content_type . ' marker for content extraction');
    }
}

// Second approach: try previous message
if (!$message_to_use && method_exists($this->chat_instance, 'get_previous_assistant_message')) {
    $message_to_use = $this->chat_instance->get_previous_assistant_message();
}

// Last resort: latest message
if (!$message_to_use) {
    $message_to_use = $this->chat_instance->get_latest_assistant_message();
}
```

## Benefits

1. **Reliability**: Content can be accurately identified regardless of message order or timing
2. **Type-Specific**: Different content types (blog posts, pages, memberships) can be distinguished
3. **Fallback Mechanisms**: Multiple retrieval strategies ensure content is found even if markers are missing
4. **Transparency**: Extensive logging helps track which method was used to retrieve content
5. **Clean Content**: Markers are removed before content is used, preventing them from appearing in published content

## Testing

To verify the system is working correctly:

1. Ask the AI to write a blog post
2. Check the PHP error logs for messages like "Added blog-post marker to response"
3. Ask the AI to publish the post
4. Verify in the logs that "Using message with blog-post marker for content extraction" appears
5. Confirm the post is published with the correct title and content

## Future Enhancements

The marker system could be extended to support additional content types or to store metadata about the content (such as intended publication status, category suggestions, etc.).