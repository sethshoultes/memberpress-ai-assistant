# Unified XML Content System Documentation

**Status:** ✅ Implemented  
**Version:** 1.0.0  
**Last Updated:** April 2024

## Overview

This document provides a consolidated reference for the XML Content System implementation in the MemberPress AI Assistant plugin. It combines information from multiple sources into a single comprehensive guide, replacing previously fragmented documentation.

## Feature Consolidation

This document consolidates information from:

- [blog-xml-formatting-implementation.md](blog-xml-formatting-implementation.md)
- [blog-xml-membership-implementation-plan.md](blog-xml-membership-implementation-plan.md)
- [Comprehensive XML Content System Documentation](../xml-content-system/README.md)

## Implementation Status

The XML Content System has been fully implemented and includes:

- XML parser for structured content (`MPAI_XML_Content_Parser`)
- JavaScript formatter for client-side processing (`mpai-blog-formatter.js`) 
- WordPress API Tool enhancements for content creation
- Support for both blog posts and membership content

## Key Components

The XML Content System consists of the following key components:

1. **XML Content Parser** (`class-mpai-xml-content-parser.php`):
   - Parses XML-formatted blog posts into WordPress data
   - Converts XML blocks to Gutenberg-compatible format
   - Supports multiple block types (paragraph, heading, list, quote, code, image)
   - Implements robust error handling and fallback mechanisms

2. **JavaScript Blog Formatter** (`mpai-blog-formatter.js`):
   - Enhances user prompts with XML formatting instructions
   - Detects XML formatted blog posts in AI responses
   - Adds UI components for content creation
   - Handles content extraction and submission

3. **WordPress API Tool Enhancements** (`class-mpai-wp-api-tool.php`):
   - Detects XML formatted content in create_post/create_page operations
   - Integrates with the XML parser to process content
   - Creates properly formatted WordPress posts and pages

4. **System Prompt Instructions**:
   - Updated to instruct the AI to use XML format for content creation
   - Provides examples and templates for different content types

## XML Format Specification

The XML Content System uses a standardized format:

```xml
<wp-post>
  <post-title>Your Post Title Here</post-title>
  <post-content>
    <block type="paragraph">This is a paragraph block of content.</block>
    <block type="heading" level="2">This is a heading block</block>
    <block type="paragraph">Another paragraph block with content.</block>
    <!-- More content blocks -->
  </post-content>
  <post-excerpt>A brief summary of the post.</post-excerpt>
  <post-status>draft</post-status>
  <!-- Optional: only for pages -->
  <post-type>page</post-type>
</wp-post>
```

### Supported Block Types

| Block Type | Description | Attributes | Example |
|------------|-------------|------------|---------|
| paragraph | Standard text paragraph | none | `<block type="paragraph">Text content</block>` |
| heading | Section heading | level (1-6) | `<block type="heading" level="2">Heading</block>` |
| list | Unordered list | none | `<block type="list"><item>First</item><item>Second</item></block>` |
| ordered-list | Ordered list | none | `<block type="ordered-list"><item>First</item><item>Second</item></block>` |
| quote | Block quote | none | `<block type="quote">Quote text</block>` |
| code | Code snippet | none | `<block type="code">code_example();</block>` |
| image | Image reference | none | `<block type="image">https://example.com/image.jpg</block>` |

For complete examples, see the [XML Content System examples](../xml-content-system/examples/).

## Technical Implementation Details

### XML Parser

The XML parser (`MPAI_XML_Content_Parser`) handles the conversion of XML content into WordPress data:

```php
// Basic usage pattern
$xml_parser = new MPAI_XML_Content_Parser();
$parsed_data = $xml_parser->parse_xml_blog_post($xml_content);

if ($parsed_data) {
    // $parsed_data contains:
    // - title: The post title
    // - content: Gutenberg-formatted content
    // - excerpt: Post excerpt
    // - status: Post status (draft/publish)
}
```

The parser implements multiple extraction methods with fallbacks to handle variations in AI output:

- Pattern-based extraction using regular expressions
- Position-based extraction for when regex fails
- Structure detection for malformed XML
- Fallback content processing when structure cannot be determined

### WordPress API Tool Integration

The WordPress API Tool has been enhanced to detect and process XML content:

```php
// Inside create_post method of MPAI_WP_API_Tool
if (isset($parameters['content']) && strpos($parameters['content'], '<wp-post>') !== false) {
    $xml_parser = new MPAI_XML_Content_Parser();
    $parsed_data = $xml_parser->parse_xml_blog_post($parameters['content']);
    
    if ($parsed_data) {
        // Override parameters with parsed data
        foreach ($parsed_data as $key => $value) {
            $parameters[$key] = $value;
        }
    }
}
```

### JavaScript Integration

The front-end integration is handled by the `MPAI_BlogFormatter` JavaScript module, which:

1. Enhances user prompts with XML formatting instructions
2. Processes AI responses to detect XML content
3. Adds UI elements for content creation
4. Handles the content creation process

Key functions include:

- `enhanceUserPrompt(userPrompt, contentType)` - Adds XML instructions to prompts
- `processAssistantMessage($message, content)` - Processes AI responses
- `addCreatePostButton($message, content)` - Adds UI elements
- `createPostFromXML(content, contentType)` - Creates content via AJAX

## User Workflow

1. User asks the AI to create a blog post or page
2. JavaScript enhances the prompt with XML formatting instructions
3. AI generates content in XML format
4. JavaScript detects the XML format and adds a "Create Post" or "Create Page" button
5. User clicks the button to create the content
6. Content is processed by the XML parser and created in WordPress
7. User receives a confirmation with a link to edit the content

## Developer Extension Guide

### Adding New Block Types

To add support for new block types:

1. Update the XML parser in `convert_xml_blocks_to_gutenberg()`:

```php
case 'new-block-type':
    $gutenberg_blocks[] = '<!-- wp:custom-block --><div class="custom-block">' . 
                          esc_html($block_content) . 
                          '</div><!-- /wp:custom-block -->';
    break;
```

2. Update the JavaScript prompt enhancement to include the new block type
3. Update the system prompt to inform the AI about the new block type

### Security Considerations

When extending the system:

1. Always sanitize content with `esc_html()`, `esc_attr()`, etc.
2. Validate XML content before processing
3. Check user capabilities before creating content
4. Implement proper error handling for edge cases

## MemberPress Integration

The XML Content System has been integrated with MemberPress features:

1. Support for membership content creation
2. Handling of membership-specific attributes
3. Integration with MemberPress products and pricing

### Creating Membership Content

To create membership content, use the XML format with membership-specific elements:

```xml
<wp-post>
  <post-title>New Membership: Pro Plan</post-title>
  <post-content>
    <block type="paragraph">Description of the Pro membership plan.</block>
    <!-- More content blocks -->
  </post-content>
  <post-excerpt>Professional membership with premium features.</post-excerpt>
  <post-status>draft</post-status>
  <membership-data>
    <price>99.99</price>
    <period>1</period>
    <period-type>month</period-type>
    <billing-type>recurring</billing-type>
  </membership-data>
</wp-post>
```

## Future Enhancements

Potential future enhancements include:

1. **More Block Types**: Support for additional Gutenberg block types like columns, tables, and embed blocks
2. **Custom Block Templates**: Support for custom block templates specific to MemberPress content needs
3. **Category and Tag Support**: Ability to specify categories and tags in the XML format
4. **Featured Image Integration**: Support for specifying featured image requirements or URLs

## References

For more detailed information, see:

- [XML Content System Documentation](../xml-content-system/README.md)
- [XML Content Parser Source](../../includes/class-mpai-xml-content-parser.php)
- [JavaScript Formatter Source](../../assets/js/modules/mpai-blog-formatter.js)
- [WordPress API Tool Source](../../includes/tools/implementations/class-mpai-wp-api-tool.php)