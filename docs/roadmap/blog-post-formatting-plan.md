# Blog Post XML Formatting for WordPress Gutenberg

## Problem Statement

When using the MemberPress AI Assistant to create and publish blog posts, the system is currently processing the entire AI response text as the blog post, without properly separating the post title from content. Additionally, the content isn't formatted properly for the WordPress Gutenberg editor, which uses a block-based approach.

## Proposed Solution

Implement a standardized XML format for blog post generation that clearly delineates post components and supports direct conversion to Gutenberg blocks.

## Goals

1. Provide a clear structure that separates post title, content, and metadata
2. Support proper Gutenberg block formatting
3. Ensure backward compatibility with existing blog post generation
4. Improve the reliability of the blog post publishing process
5. Enhance the quality of published content

## Technical Design

### 1. XML Response Format

We will implement a standardized XML format that the AI will use when generating blog posts:

```xml
<wp-post>
  <post-title>Your Post Title Here</post-title>
  <post-content>
    <block type="paragraph">
      This is a paragraph block of content.
    </block>
    <block type="heading" level="2">
      This is a heading block
    </block>
    <block type="paragraph">
      Another paragraph block with content.
    </block>
    <!-- More blocks as needed -->
  </post-content>
  <post-excerpt>
    A brief summary of the post.
  </post-excerpt>
  <post-status>draft</post-status> <!-- draft or publish -->
</wp-post>
```

### 2. Enhanced Content Detection

We'll extend the existing Content Marker System to recognize and properly handle the new XML format:

```php
/**
 * Enhanced content type detection
 */
private function detect_content_type($message_content) {
    // Check for XML post format first
    if (preg_match('/<wp-post>.*?<\/wp-post>/s', $message_content)) {
        $modified_content = $this->add_content_marker($message_content, 'blog-post');
        error_log('MPAI: Added blog-post marker to XML-formatted response');
        return $modified_content;
    }
    
    // Check for traditional blog post patterns (fallback)
    if (preg_match('/(?:#+\s*Title:?|Title:)\s*([^\n]+)/i', $message_content) ||
        (preg_match('/^#+\s*([^\n]+)/i', $message_content) && 
         preg_match('/introduction|summary|overview|content|body|conclusion/i', $message_content))) {
        
        $modified_content = $this->add_content_marker($message_content, 'blog-post');
        error_log('MPAI: Added blog-post marker to traditional response');
        return $modified_content;
    }
    
    return $message_content;
}
```

### 3. XML Parser for Gutenberg Blocks

Create a dedicated parser to convert the XML format into Gutenberg blocks:

```php
/**
 * Parse XML formatted blog post into WordPress data
 */
private function parse_xml_blog_post($xml_content) {
    // Extract content from wp-post tags
    if (!preg_match('/<wp-post>(.*?)<\/wp-post>/s', $xml_content, $matches)) {
        error_log('MPAI: Failed to find wp-post tags');
        return false;
    }
    
    $post_data = [];
    $xml = $matches[1];
    
    // Extract post components
    if (preg_match('/<post-title>(.*?)<\/post-title>/s', $xml, $title_match)) {
        $post_data['title'] = trim($title_match[1]);
    }
    
    if (preg_match('/<post-content>(.*?)<\/post-content>/s', $xml, $content_match)) {
        $blocks_content = $content_match[1];
        $post_data['content'] = $this->convert_xml_blocks_to_gutenberg($blocks_content);
    }
    
    if (preg_match('/<post-excerpt>(.*?)<\/post-excerpt>/s', $xml, $excerpt_match)) {
        $post_data['excerpt'] = trim($excerpt_match[1]);
    }
    
    if (preg_match('/<post-status>(.*?)<\/post-status>/s', $xml, $status_match)) {
        $post_data['status'] = trim($status_match[1]);
    }
    
    return $post_data;
}

/**
 * Convert XML block format to Gutenberg blocks
 */
private function convert_xml_blocks_to_gutenberg($blocks_content) {
    $gutenberg_blocks = [];
    
    // Extract individual blocks
    preg_match_all('/<block type="([^"]+)"(?:\s+level="([^"]+)")?\s*>(.*?)<\/block>/s', $blocks_content, $blocks, PREG_SET_ORDER);
    
    foreach ($blocks as $block) {
        $block_type = $block[1];
        $block_content = trim($block[3]);
        
        switch ($block_type) {
            case 'paragraph':
                $gutenberg_blocks[] = '<!-- wp:paragraph --><p>' . $block_content . '</p><!-- /wp:paragraph -->';
                break;
                
            case 'heading':
                $level = isset($block[2]) ? $block[2] : '2';
                $gutenberg_blocks[] = '<!-- wp:heading {"level":' . $level . '} --><h' . $level . '>' . $block_content . '</h' . $level . '><!-- /wp:heading -->';
                break;
                
            case 'list':
                $gutenberg_blocks[] = '<!-- wp:list --><ul>' . $block_content . '</ul><!-- /wp:list -->';
                break;
                
            // Support for additional block types
            case 'quote':
                $gutenberg_blocks[] = '<!-- wp:quote --><blockquote class="wp-block-quote"><p>' . $block_content . '</p></blockquote><!-- /wp:quote -->';
                break;
                
            case 'code':
                $gutenberg_blocks[] = '<!-- wp:code --><pre class="wp-block-code"><code>' . htmlspecialchars($block_content) . '</code></pre><!-- /wp:code -->';
                break;
                
            default:
                // Default to paragraph for unknown types
                $gutenberg_blocks[] = '<!-- wp:paragraph --><p>' . $block_content . '</p><!-- /wp:paragraph -->';
        }
    }
    
    return implode("\n\n", $gutenberg_blocks);
}
```

### 4. Updated Create Post Method

Modify the post creation method to handle the new format:

```php
/**
 * Enhanced create_post method that handles XML formatted content
 */
private function create_post($parameters) {
    error_log('MPAI: Create post parameters: ' . json_encode($parameters));
    
    // Check if content is in XML format
    if (isset($parameters['content']) && strpos($parameters['content'], '<wp-post>') !== false) {
        error_log('MPAI: Detected XML formatted blog post');
        $parsed_data = $this->parse_xml_blog_post($parameters['content']);
        
        if ($parsed_data) {
            // Override parameters with parsed data
            foreach ($parsed_data as $key => $value) {
                $parameters[$key] = $value;
            }
            error_log('MPAI: Successfully parsed XML blog post format');
        }
    }
    
    // Proceed with normal post creation using the enhanced parameters
    $post_data = array(
        'post_title'   => isset($parameters['title']) ? $parameters['title'] : 'New Post',
        'post_content' => isset($parameters['content']) ? $parameters['content'] : '',
        'post_status'  => isset($parameters['status']) ? $parameters['status'] : 'draft',
        'post_type'    => isset($parameters['post_type']) ? $parameters['post_type'] : 'post',
        'post_author'  => isset($parameters['author_id']) ? $parameters['author_id'] : get_current_user_id(),
    );
    
    // Continue with existing implementation...
}
```

### 5. Agent Prompt Instructions

Update the AI's system prompt to include instructions for the new XML format:

```php
/**
 * Content creation instructions for the agent
 */
private function get_content_creation_instructions() {
    return <<<EOT
When asked to create a blog post, always return it in the following XML format:

<wp-post>
  <post-title>Your Post Title Here</post-title>
  <post-content>
    <block type="paragraph">
      This is a paragraph block of content.
    </block>
    <block type="heading" level="2">
      This is a heading block
    </block>
    <block type="paragraph">
      Another paragraph block with content.
    </block>
    <!-- Add more blocks as needed -->
  </post-content>
  <post-excerpt>
    A brief summary of the post.
  </post-excerpt>
  <post-status>draft</post-status> <!-- draft or publish -->
</wp-post>

This format ensures the post will be correctly processed by WordPress Gutenberg.
EOT;
}
```

## Implementation Timeline

1. **Phase 1: Core XML Format (1-2 weeks)**
   - Define XML schema for blog posts
   - Implement parsing functionality
   - Update AI system prompt with XML format instructions

2. **Phase 2: Gutenberg Block Conversion (1-2 weeks)**
   - Implement block conversion from XML to Gutenberg format
   - Add support for various block types
   - Add error handling and validation

3. **Phase 3: Integration and Testing (1 week)**
   - Integrate with existing content marker system
   - Test with various post types and content formats
   - Ensure backward compatibility

4. **Phase 4: Documentation and Release (1 week)**
   - Update user documentation
   - Create developer documentation
   - Release feature

## Benefits

1. **Improved Publishing Experience**
   - Clear separation of post title and content
   - Proper block formatting for Gutenberg editor
   - Support for post excerpts and status

2. **Enhanced Content Quality**
   - Structured content with proper headings
   - Consistent formatting across posts
   - Better SEO potential with proper HTML structure

3. **Technical Reliability**
   - Reduced errors in post creation
   - More predictable parsing results
   - Better error handling and fallbacks

## Compatibility Considerations

- The system will maintain backward compatibility with the current text-based format
- Legacy content will still be processed using the existing extraction methods
- The XML format will be preferred when available, with fallback to traditional extraction

## Future Enhancements

1. **Category and Tag Support**
   - Add XML tags for categories and tags
   - Implement automatic tag suggestion based on content

2. **Featured Image Integration**
   - Support for specifying featured image requirements
   - Automatic image search and attachment

3. **Advanced Block Types**
   - Support for more complex Gutenberg blocks
   - Custom block templates for common content patterns