# XML Content System Index

**Status:** ✅ Maintained  
**Version:** 1.0.0  
**Last Updated:** April 3, 2025

This directory contains comprehensive documentation and examples for the XML Content System used in the MemberPress AI Assistant plugin.

## Documentation

| Document | Description |
|----------|-------------|
| [README.md](README.md) | Main documentation for the XML Content System |
| [Unified XML Content System](../current/content-system/unified-xml-content-system.md) | Consolidated reference document |
| [Blog XML Formatting Implementation](../current/content-system/blog-xml-formatting-implementation.md) | Detailed implementation documentation |
| [Blog XML Membership Implementation Plan](../current/content-system/blog-xml-membership-implementation-plan.md) | Membership content integration |

## XML Format Examples

These examples demonstrate the standardized XML format for different content types:

| Example | Description | Preview |
|---------|-------------|---------|
| [Blog Post Example](examples/blog-post-example.xml) | Standard blog post format | `<wp-post><post-title>10 Essential MemberPress Features...</post-title>...` |
| [Page Example](examples/page-example.xml) | WordPress page format | `<wp-post><post-title>About Our Membership Program</post-title>...` |
| [Membership Post Example](examples/membership-post-example.xml) | Membership product content | `<wp-post><post-title>Pro Membership: Monthly Coaching Access</post-title>...` |
| [Complex Post Example](examples/complex-post-example.xml) | Advanced content with all block types | `<wp-post><post-title>Advanced Content Creation with XML Formatting</post-title>...` |

## XML Format Specification

The XML Content System uses a standardized format for WordPress content:

```xml
<wp-post>
  <post-title>Post Title</post-title>
  <post-content>
    <block type="paragraph">Text content</block>
    <block type="heading" level="2">Heading</block>
    <!-- More blocks -->
  </post-content>
  <post-excerpt>Post excerpt</post-excerpt>
  <post-status>draft</post-status>
  <!-- Optional elements -->
  <post-type>page</post-type>
</wp-post>
```

### Supported Block Types

| Block Type | Description | Example |
|------------|-------------|---------|
| paragraph | Standard text | `<block type="paragraph">Text</block>` |
| heading | Section heading | `<block type="heading" level="2">Heading</block>` |
| list | Unordered list | `<block type="list"><item>Item</item></block>` |
| ordered-list | Ordered list | `<block type="ordered-list"><item>Item</item></block>` |
| quote | Block quote | `<block type="quote">Quote text</block>` |
| code | Code snippet | `<block type="code">code_example();</block>` |
| image | Image reference | `<block type="image">https://example.com/image.jpg</block>` |

## Integration Points

The XML Content System integrates with several other components:

1. **Chat Interface** - Detects XML content and adds UI elements
2. **WordPress API Tool** - Processes XML to create formatted posts/pages
3. **Agent System** - Uses XML format in prompts and responses

## For Developers

When extending the XML Content System:

1. **Adding new block types**:
   - Update parser in `MPAI_XML_Content_Parser::convert_xml_blocks_to_gutenberg()`
   - Update client-side detection in `mpai-blog-formatter.js`
   - Update system prompts for AI guidance

2. **Security considerations**:
   - Always sanitize content with WordPress escaping functions
   - Validate XML content before processing
   - Implement appropriate capability checks

## Implementation Files

- [MPAI_XML_Content_Parser](../../includes/class-mpai-xml-content-parser.php)
- [mpai-blog-formatter.js](../../assets/js/modules/mpai-blog-formatter.js)
- [MPAI_WP_API_Tool](../../includes/tools/implementations/class-mpai-wp-api-tool.php)