# XML Content System

**Version:** 1.0.0  
**Last Updated:** 2025-04-03  
**Status:** ✅ Maintained

## Overview

The XML Content System is a comprehensive solution for structured content generation in the MemberPress AI Assistant plugin. It provides a standardized way for AI-generated content to be formatted, parsed, and integrated with WordPress's Gutenberg editor, ensuring consistent and high-quality content creation.

## Key Components

### 1. XML Content Parser (`class-mpai-xml-content-parser.php`)

The core component that handles parsing XML-formatted blog posts into WordPress-compatible data.

**Functionality:**
- Extracts content from `<wp-post>` tags
- Parses post components (title, content, excerpt, status)
- Converts XML block formats to Gutenberg blocks
- Supports multiple block types (paragraph, heading, list, quote, code, image)
- Provides robust fallback mechanisms for malformed or incomplete XML

**Integration Points:**
- Used by WordPress API Tool for post creation
- Used by direct AJAX handlers for content processing
- Provides format validation for XML-structured content

### 2. Blog Formatter JS (`mpai-blog-formatter.js`)

Frontend JavaScript module for XML blog post handling in the chat interface.

**Functionality:**
- Enhances user prompts to request XML-formatted content
- Adds "Create Post" buttons to messages containing XML content
- Processes XML content for WordPress post creation
- Provides user feedback during post creation process
- Handles XML extraction with multiple fallback strategies

**Integration Points:**
- Integrates with chat interface for formatting requests
- Works with Message system for communication with AI
- Calls direct AJAX handler for post creation
- Registers UI event handlers for XML content

### 3. Chat Formatters JS (`mpai-chat-formatters.js`)

Frontend formatting module that visually enhances XML content in the chat interface.

**Functionality:**
- Detects and protects XML content during message formatting
- Creates user-friendly preview cards for XML blog posts
- Formats XML syntax with proper highlighting
- Adds toggle buttons for viewing raw XML
- Provides "Create Post" buttons for easy content publishing

**Integration Points:**
- Hooks into chat message rendering system
- Works with other formatters (code blocks, tables, etc.)
- Enhances user experience for XML content

### 4. WordPress API Tool (`class-mpai-wp-api-tool.php`)

Backend tool that processes XML content for WordPress post creation.

**Functionality:**
- Detects XML-formatted content in post creation requests
- Parses XML content into WordPress post data
- Creates properly formatted Gutenberg blocks
- Supports post metadata from XML (status, excerpt, etc.)
- Provides error handling and logging

**Integration Points:**
- Called by AI through function calling
- Used by direct AJAX handler for browser-based post creation
- Integrates with XML parser for content processing

## System Workflow

1. **Content Request**:
   - User asks AI to create a blog post
   - `mpai-blog-formatter.js` enhances prompt to request XML format
   - AI generates content in XML format with proper structure

2. **Content Formatting**:
   - `mpai-chat-formatters.js` detects XML content
   - Creates formatted preview card with title and excerpt
   - Adds "Create Post" button and view controls

3. **Content Processing**:
   - User clicks "Create Post" button
   - `mpai-blog-formatter.js` extracts and processes XML
   - Sends to direct AJAX handler to bypass WP nonce requirements

4. **Content Publishing**:
   - AJAX handler calls `MPAI_WP_API_Tool`
   - Tool detects XML format and invokes `MPAI_XML_Content_Parser`
   - Parser converts XML to WordPress-compatible format
   - WordPress creates post with proper Gutenberg blocks

## XML Format Specification

```xml
<wp-post>
  <post-title>Your Post Title Here</post-title>
  <post-content>
    <block type="paragraph">This is a paragraph block of content.</block>
    <block type="heading" level="2">This is a heading block</block>
    <block type="paragraph">Another paragraph block with content.</block>
    <block type="list">
      <item>First list item</item>
      <item>Second list item</item>
      <item>Third list item</item>
    </block>
    <block type="quote">This is a quote block</block>
    <block type="code">This is a code block</block>
  </post-content>
  <post-excerpt>A brief summary of the post.</post-excerpt>
  <post-status>draft</post-status>
</wp-post>
```

### Supported Block Types

| Block Type | XML Format | Gutenberg Equivalent |
|------------|------------|----------------------|
| Paragraph | `<block type="paragraph">Text</block>` | Paragraph block |
| Heading | `<block type="heading" level="2">Heading</block>` | Heading block (H1-H6) |
| List | `<block type="list"><item>Item</item></block>` | Unordered list |
| Ordered List | `<block type="ordered-list"><item>Item</item></block>` | Ordered list |
| Quote | `<block type="quote">Quote text</block>` | Quote block |
| Code | `<block type="code">Code content</block>` | Code block |
| Image | `<block type="image">URL</block>` | Image block |

## Integration with Agent System

The XML Content System works seamlessly with the agent system documented in [_1_AGENTIC_SYSTEMS_.md](../../_1_AGENTIC_SYSTEMS_.md):

- **Content Agent**: Can generate XML-formatted blog posts based on user requests
- **MemberPress Agent**: Can create membership-related content in XML format
- **Agent Orchestrator**: Routes content creation requests to the appropriate specialized agent

When an agent needs to create content, it uses the XML format to ensure proper structure and formatting, which is then processed by the XML Content System for publishing.

## Integration with Tool System

The system leverages the tool implementation framework documented in [tool-implementation-map.md](../current/tool-implementation-map.md):

- WordPress API Tool (`wp_api`): Primary tool for content creation, enhanced with XML support
- Direct AJAX Handler: Provides secure endpoint for browser-based content creation
- Tool Registry: Registers and manages tool availability

## Extensions and Customization

To extend the XML Content System:

1. **Add New Block Types**:
   - Update the XML parser's `convert_xml_blocks_to_gutenberg` method
   - Add new case in the switch statement for the new block type
   - Update the blog formatter's prompt template to include examples

2. **Add Metadata Support**:
   - Add new XML tag in the format: `<post-{metadata}>`
   - Update the parser to extract the new metadata
   - Add handling in the WordPress API Tool

3. **Customize Preview Appearance**:
   - Modify the preview card generation in `mpai-chat-formatters.js`
   - Update CSS styles for card components

## Best Practices

1. **XML Structure**: Always use the complete XML structure with all required tags
2. **Error Handling**: Implement fallback mechanisms for incomplete or malformed XML
3. **User Feedback**: Provide clear feedback during processing and after post creation
4. **Testing**: Test with various content types and edge cases (very long content, special characters)

## Related Documentation

- [Blog XML Formatting Implementation](../current/blog-xml-formatting-implementation.md)
- [Blog XML Membership Implementation Plan](../current/blog-xml-membership-implementation-plan.md)
- [System Map](../current/system-map.md)

## Troubleshooting

### Common Issues

1. **XML Not Detected**:
   - Ensure content includes both opening `<wp-post>` and closing `</wp-post>` tags
   - Check for malformed XML with unclosed tags
   - Verify the AI prompt properly requests XML format

2. **Formatting Issues**:
   - Check block type is one of the supported types
   - Ensure proper nesting of tags (e.g., `<item>` tags inside list blocks)
   - Verify all tags are properly closed

3. **Post Creation Fails**:
   - Check PHP error logs for parsing errors
   - Verify user has permission to create posts
   - Check for issues with special characters in content

### Debugging

- Enable console logging in MemberPress AI Assistant settings
- Check browser console for JavaScript errors during post creation
- Check PHP error logs for backend processing issues
- Use test scripts in `/test/` directory to validate XML parsing