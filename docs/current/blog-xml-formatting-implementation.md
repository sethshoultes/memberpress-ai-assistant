# Blog XML Formatting and WordPress Content Tools Implementation

## Overview

This document outlines the implementation of the XML-based blog post formatting feature and content tools enhancement for the MemberPress AI Assistant. These features enable more structured content creation, proper Gutenberg block formatting, and enhanced WordPress API tool capabilities.

## Features Implemented

### 1. XML Blog Post Format

The XML blog post format provides a standardized structure for blog posts, ensuring proper separation of title, content blocks, and metadata. The format looks like:

```xml
<wp-post>
  <post-title>Your Post Title Here</post-title>
  <post-content>
    <block type="paragraph">This is a paragraph block of content.</block>
    <block type="heading" level="2">This is a heading block</block>
    <block type="paragraph">Another paragraph block with content.</block>
  </post-content>
  <post-excerpt>A brief summary of the post.</post-excerpt>
  <post-status>draft</post-status>
</wp-post>
```

### 2. XML Content Parser

A dedicated XML content parser class (`MPAI_XML_Content_Parser`) was created to:

- Parse the XML formatted blog post into WordPress data
- Extract title, content blocks, excerpt, and status
- Convert XML blocks to Gutenberg-compatible format
- Support various block types (paragraph, heading, list, quote, code, image)

### 3. Enhanced Content Detection

The MPAI_Chat class was updated to detect XML formatted blog posts through the existing content detection system. The system now:

- Prioritizes detection of the XML format over traditional formats
- Adds appropriate content markers to identify blog posts
- Maintains backward compatibility with existing blog post detection patterns
- Supports seamless handling of both XML and traditional formats

### 4. WordPress API Tool Enhancements

The WordPress API Tool (`MPAI_WP_API_Tool`) was enhanced to:

- Detect XML formatted content in create_post/create_page operations
- Parse XML content into appropriate WordPress data using the XML parser
- Handle title, content, excerpt and status extracted from XML
- Support multiple block types including lists, quotes, and code blocks

### 5. System Prompt Instructions

The system prompt was updated to instruct the AI to use the XML format when creating blog posts. The instructions include:

- XML format template with example structure
- Available block types and their usage
- How to structure different content elements
- Parameters for headings and other special blocks

## Technical Implementation

### XML Parser Class

The parser class handles the conversion of XML content into WordPress data:

```php
class MPAI_XML_Content_Parser {
    public function parse_xml_blog_post($xml_content) {
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

    public function convert_xml_blocks_to_gutenberg($blocks_content) {
        // Implementation of XML to Gutenberg conversion
        // Supporting paragraph, heading, list, ordered-list, quote, code, and image blocks
    }
}
```

### Enhanced Content Detection

The content detection logic in MPAI_Chat was enhanced to recognize the XML format:

```php
// Check for XML formatted blog post first
if (preg_match('/<wp-post>.*?<\/wp-post>/s', $message_content)) {
    // This is an XML formatted blog post
    if (method_exists($this, 'add_content_marker')) {
        $modified_content = $this->add_content_marker($message_content, 'blog-post');
        error_log('MPAI Chat: Added blog-post marker to XML-formatted response');
    }
}
// Check for traditional blog post content patterns as fallback
else if (preg_match('/(?:#+\s*Title:?|Title:)\s*([^\n]+)/i', $message_content) ||
    (preg_match('/^#+\s*([^\n]+)/i', $message_content) && 
     preg_match('/introduction|summary|overview|content|body|conclusion/i', $message_content))) {
    // Handle traditional format
}
```

### WordPress API Tool

The WordPress API Tool was enhanced to process XML content:

```php
// Check if content is in XML format
if (isset($parameters['content']) && strpos($parameters['content'], '<wp-post>') !== false) {
    error_log('MPAI: Detected XML formatted blog post');
    
    // Include the XML parser class
    if (!class_exists('MPAI_XML_Content_Parser')) {
        require_once dirname(dirname(dirname(__FILE__))) . '/class-mpai-xml-content-parser.php';
    }
    
    $xml_parser = new MPAI_XML_Content_Parser();
    $parsed_data = $xml_parser->parse_xml_blog_post($parameters['content']);
    
    if ($parsed_data) {
        error_log('MPAI: Successfully parsed XML blog post format');
        // Override parameters with parsed data
        foreach ($parsed_data as $key => $value) {
            $parameters[$key] = $value;
        }
    }
}
```

### System Prompt Instructions

The AI system prompt was updated to include XML formatting instructions:

```php
$system_prompt .= "IMPORTANT: When asked to create a blog post, always write the post content in the following XML format:\n";
$system_prompt .= "<wp-post>\n";
$system_prompt .= "  <post-title>Your Post Title Here</post-title>\n";
$system_prompt .= "  <post-content>\n";
$system_prompt .= "    <block type=\"paragraph\">This is a paragraph block of content.</block>\n";
$system_prompt .= "    <block type=\"heading\" level=\"2\">This is a heading block</block>\n";
$system_prompt .= "    <block type=\"paragraph\">Another paragraph block with content.</block>\n";
$system_prompt .= "  </post-content>\n";
$system_prompt .= "  <post-excerpt>A brief summary of the post.</post-excerpt>\n";
$system_prompt .= "  <post-status>draft</post-status>\n";
$system_prompt .= "</wp-post>\n\n";
```

## Usage and Examples

### Creating a Blog Post

When a user asks the AI to create a blog post, the response will now use the XML format:

```
<wp-post>
  <post-title>10 Essential MemberPress Features for Course Creators</post-title>
  <post-content>
    <block type="paragraph">Are you a course creator looking to maximize your MemberPress setup? In this post, we'll explore the essential features that every course creator should know about.</block>
    
    <block type="heading" level="2">1. Drip Content Scheduling</block>
    <block type="paragraph">MemberPress allows you to release course materials on a schedule. This feature helps prevent overwhelm and keeps students engaged throughout the course.</block>
    
    <!-- More content blocks -->
  </post-content>
  <post-excerpt>Discover the top 10 MemberPress features specifically designed to help course creators deliver better content and improve student engagement.</post-excerpt>
  <post-status>draft</post-status>
</wp-post>
```

The WordPress API tool will then:
1. Detect the XML format
2. Parse the content into WordPress-friendly data
3. Convert the blocks to Gutenberg format
4. Create the post with proper formatting

## Future Enhancements

Potential future enhancements include:

1. **More Block Types**: Support for additional Gutenberg block types like columns, tables, and embed blocks
2. **Custom Block Templates**: Support for custom block templates specific to MemberPress content needs
3. **Category and Tag Support**: Ability to specify categories and tags in the XML format
4. **Featured Image Integration**: Support for specifying featured image requirements or URLs

## Testing

To test the XML blog post formatting:

1. Ask the AI to create a blog post on any topic
2. Verify the response uses the XML format
3. Create the post using the WordPress API tool
4. Inspect the created post in the WordPress editor to ensure proper Gutenberg block formatting