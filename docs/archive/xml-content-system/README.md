# XML Content System Documentation

**Status:** ✅ Implemented  
**Version:** 1.0.0  
**Last Updated:** April 2024

## Overview

The XML Content System provides a structured approach to content creation in the MemberPress AI Assistant plugin. This system enables the AI to generate properly formatted blog posts and pages that can be directly imported into WordPress with correct Gutenberg block formatting.

## Key Components

1. **XML Parser (`MPAI_XML_Content_Parser`)**: Processes XML-formatted content into WordPress data
2. **JavaScript Formatter (`mpai-blog-formatter.js`)**: Handles client-side XML processing and user interactions
3. **WordPress API Integration**: Extends the WordPress API tool to handle XML content

## System Architecture

The XML Content System uses a multi-layered approach to process XML-formatted content:

1. **Generation Layer**: AI generates content in XML format
2. **Parsing Layer**: XML parser converts content to WordPress data
3. **Integration Layer**: WordPress API tool creates posts/pages with proper formatting

### Component Diagram

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│                 │    │                  │    │                 │
│  AI Generation  │───►│  XML Processing  │───►│  WordPress API  │
│                 │    │                  │    │                 │
└─────────────────┘    └──────────────────┘    └─────────────────┘
         │                      │                       │
         ▼                      ▼                       ▼
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   JavaScript    │    │      MPAI        │    │    Gutenberg    │
│    Frontend     │◄───┤   XML Parser     │◄───┤     Blocks      │
└─────────────────┘    └──────────────────┘    └─────────────────┘
```

## XML Format Specification

The XML Content System uses a standardized XML format for both blog posts and pages:

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

## Content Flow

1. User requests blog post creation
2. JavaScript enhances the prompt with XML instructions
3. AI generates response in XML format
4. JavaScript detects XML format and adds "Create Post" button
5. User clicks button to create content
6. AJAX request sends XML to server
7. XML parser extracts structured data
8. WordPress API creates properly formatted post
9. User receives confirmation with edit link

## Integration with Other Systems

The XML Content System integrates with several other components:

### Integration with Chat Interface

- XML content is detected and marked with a 'blog-post' content marker
- UI elements are added to relevant messages for content creation
- The chat interface JavaScript detects XML content in the AI response
- A "Create Post" button is dynamically added for easy content creation

### Integration with WordPress API Tool

- The WP API Tool detects XML formatted content (`create_post` and `create_page` actions)
- It invokes the XML parser to extract structured data
- It uses the extracted data to create properly formatted posts with Gutenberg blocks
- Supports both blog posts and pages with appropriate content structure

### Integration with Agent System

- System prompts include instructions for XML formatting
- Agent orchestration routes content creation requests appropriately
- Tool execution handles content creation tool calls

## Examples and Reference

The following examples demonstrate the XML format in action:

- [Blog Post Example](examples/blog-post-example.xml) - Standard blog post
- [Page Example](examples/page-example.xml) - WordPress page
- [Complex Content Example](examples/complex-post-example.xml) - Advanced content with all block types

## Technical Implementation

The XML Content System is implemented through several key files:

- [MPAI_XML_Content_Parser](../../includes/class-mpai-xml-content-parser.php) - Backend XML parsing
- [mpai-blog-formatter.js](../../assets/js/modules/mpai-blog-formatter.js) - Frontend processing
- [MPAI_WP_API_Tool](../../includes/tools/implementations/class-mpai-wp-api-tool.php) - WordPress integration

For detailed implementation information, see [blog-xml-formatting-implementation.md](../current/blog-xml-formatting-implementation.md).

## For Developers

When extending the XML Content System:

1. **Adding new block types**:
   - Add parser support in `convert_xml_blocks_to_gutenberg()`
   - Update system prompts to inform AI about new block types
   - Update client-side XML detection in `mpai-blog-formatter.js`

2. **Modifying XML structure**:
   - Update parser to handle new XML elements
   - Update JavaScript prompt enhancement
   - Update system prompts and detection patterns

3. **Security considerations**:
   - Always sanitize content with `esc_html()`, `esc_attr()`, etc.
   - Validate XML content before processing
   - Apply appropriate capability checks
   - Implement error handling for malformed XML

## Related Documentation

- [Blog XML Formatting Implementation](../current/blog-xml-formatting-implementation.md)
- [Blog XML Membership Implementation Plan](../current/blog-xml-membership-implementation-plan.md)
- [Content Marker System](../current/CONTENT_MARKER_SYSTEM.md)
- [Tool Implementation Map](../current/tool-implementation-map.md)
- [System Map](../current/system-map.md)
- [Unified XML Content System](../current/unified-xml-content-system.md)