# MemberPress AI Assistant Documentation Style Guide

**Version:** 1.0.0  
**Last Updated:** 2025-04-05  
**Status:** 🚧 In Progress  
**Owner:** Documentation Team

## Overview

This style guide establishes the standards for all MemberPress AI Assistant documentation. It ensures consistency in documentation format, naming, structure, and content across the project.

## Document Structure

### Metadata Header

All documentation files must include a metadata header at the top:

```markdown
# Document Title

**Version:** 1.0.0  
**Last Updated:** YYYY-MM-DD  
**Status:** [Status Indicator]  
**Owner:** [Team or Individual]
```

### Status Indicators

Use the following status indicators consistently:

- ✅ **Maintained**: Documentation is current and actively maintained
- 🚧 **In Progress**: Documentation is being actively developed
- 🔮 **Planned**: Documentation is planned but not yet created
- 🗄️ **Archived**: Documentation has been superseded or deprecated

### Standard Sections

Documentation should include these standard sections when applicable:

1. **Overview/Executive Summary**: Brief description of the document's purpose
2. **Background/Context**: Relevant background information
3. **Main Content**: The primary information, organized in logical sections
4. **Implementation Details**: Technical specifications and code examples
5. **References**: Links to related documentation
6. **Next Steps/Conclusion**: Summary and future directions

## File Organization

### Directory Structure

- `/docs/current/`: Implemented features organized by system
- `/docs/_snacks/`: Investigation results and solutions ("Scooby Snacks")
- `/docs/roadmap/`: Planned features
- `/docs/archive/`: Deprecated or superseded documentation
- `/docs/templates/`: Document templates

### File Naming Conventions

- Use lowercase, hyphenated names for all documentation files: `file-name-example.md`
- System-specific prefixes for specialized documents: `agent-system-implementation.md`
- Avoid spaces, underscores, and uppercase characters in filenames
- Add numeric prefixes for ordered documents: `1-getting-started.md`, `2-configuration.md`

### Directory Naming Conventions

- Use lowercase, hyphenated names for directories: `tool-system/`
- System-based directories should be singular: `agent-system/`, not `agent-systems/`
- Use prefixes for organizational directories: `_snacks/`, `_templates/`

## Content Guidelines

### Writing Style

- Write in clear, concise language
- Use active voice and present tense
- Address the reader directly using "you"
- Keep paragraphs and sentences short (max 3-4 sentences per paragraph)
- Use technical terminology consistently

### Headings

- Use sentence case for headings: "Documentation style guide" not "Documentation Style Guide"
- Nest headings properly (H1 → H2 → H3, never skip levels)
- Keep headings concise and descriptive
- Use a single H1 (#) at the top of each document

### Lists

- Use numbered lists for sequential steps
- Use bullet points for unordered lists
- Maintain parallel structure in list items
- Begin each list item with a capital letter
- End each list item consistently (with periods if they're complete sentences)

### Code Examples

- Use syntax highlighting with language identifiers:

  ```php
  function example() {
      return 'Hello World';
  }
  ```

- Include comments in code examples to explain key points
- Provide context for code examples
- Use consistent indentation (4 spaces recommended)
- For inline code, use backticks: `function_name()`

### Links

- Use descriptive link text: [documentation map](documentation-map.md), not [click here](documentation-map.md)
- Prefer relative links for internal documentation
- Check links regularly to ensure they're not broken
- Include tooltip text for complex links

### Images

- Use Alt text for all images: `![Alt text description](image.png)`
- Keep images in the `/docs/images/` directory
- Use descriptive filenames for images: `agent-system-architecture.png`
- Include captions for complex images
- Optimize images for web (compress when possible)

### Tables

- Include headers for all tables
- Align content properly in tables
- Keep tables simple (max 5-6 columns)
- Use consistent formatting for similar tables
- Include caption or description before the table

## Document Types

### Reference Documentation

- Focus on technical accuracy and completeness
- Organize alphabetically or by logical category
- Include parameters, return values, and examples
- Link to related documentation
- Use tables for parameter lists

### Tutorials

- Include clear, step-by-step instructions
- Define prerequisites at the beginning
- Explain the purpose of each step
- Include screenshots or diagrams where helpful
- End with a complete working example

### Conceptual Guides

- Begin with a clear explanation of the concept
- Include diagrams to illustrate relationships
- Connect to real-world use cases
- Link to relevant reference documentation
- Include examples to reinforce concepts

### Troubleshooting Guides

- Organize by symptom or error message
- Include diagnostic steps
- Provide clear solutions
- Explain the cause of common problems
- Link to relevant reference documentation

### API Documentation

- Document all parameters and return values
- Include request and response examples
- Note any rate limits or restrictions
- Provide authentication information
- Include error responses and status codes

## Cross-References and Navigation

### Cross-References

- Use relative links for cross-references: `[system map](system-map.md)`
- Include descriptive link text that indicates destination
- Group related links in "See Also" sections
- Update cross-references when moving or renaming documents

### Navigation Aids

- Include breadcrumbs at the top of complex documents
- Create an index for document collections
- Use consistent navigation patterns across documentation
- Add a table of contents for longer documents
- Include "Next Steps" at the end of documents

## Markdown Formatting

### Basic Formatting

- **Bold** for emphasis: `**bold text**`
- *Italic* for terms or slight emphasis: `*italic text*`
- ~~Strikethrough~~ for deprecated features: `~~strikethrough~~`
- `Code` for code snippets, commands, or file paths: \`code\`

### Advanced Formatting

- Blockquotes for quoted content:
  ```
  > This is a blockquote
  ```

- Horizontal rules for section breaks:
  ```
  ---
  ```

- Footnotes for additional information:
  ```
  Here is text with a footnote[^1].
  [^1]: This is the footnote content.
  ```

### Special Formatting

- Use callout boxes for important information:
  ```
  > **Note:** Important information to consider.
  
  > **Warning:** Critical warning about this functionality.
  
  > **Tip:** Helpful advice for better usage.
  ```

- Use collapsible sections for detailed information:
  ```
  <details>
  <summary>Click to expand</summary>
  
  This is the expanded content.
  
  </details>
  ```

## Version Control

### Document Version

- Use semantic versioning for documents (MAJOR.MINOR.PATCH)
- Increment MAJOR for significant content changes
- Increment MINOR for additions of new information
- Increment PATCH for small corrections or typo fixes

### Last Updated Date

- Use ISO format (YYYY-MM-DD) for all dates
- Update the date whenever the document is modified
- Include the update date in the metadata header

### Change Notes

- For significant updates, include an "Updates" section:
  ```
  ## Updates
  
  - 2025-04-05: Added new section on cross-references
  - 2025-03-28: Updated code examples
  - 2025-03-15: Initial version
  ```

## File Extensions

- Use `.md` for Markdown files
- Use `.xml` for XML examples
- Use `.php`, `.js`, etc. for code examples

## Document Reviews

All documentation should go through the following review process:

1. Self-review by the author
2. Technical review by a subject matter expert
3. Editorial review for style and clarity
4. Final approval by documentation owner

## Accessibility

- Use descriptive alt text for all images
- Maintain proper heading hierarchy
- Use sufficient color contrast
- Avoid using color alone to convey meaning
- Make link text descriptive and unique
- Use tables with proper headers
- Provide text alternatives for diagrams

## Implementation Examples

### Example: Reference Documentation

```markdown
# Tool Registry API

**Version:** 1.0.0  
**Last Updated:** 2025-04-05  
**Status:** ✅ Maintained  
**Owner:** Tool System Team

## Overview

The Tool Registry API allows you to register, retrieve, and manage tools in the MemberPress AI Assistant.

## Methods

### register_tool()

Registers a new tool with the system.

#### Parameters

| Parameter | Type | Description | Required |
|-----------|------|-------------|----------|
| `name` | string | The tool name | Yes |
| `description` | string | Tool description | Yes |
| `callback` | callable | Function to execute | Yes |
| `parameters` | array | Tool parameters | No |

#### Returns

`boolean` - True on success, false on failure.

#### Example

```php
$registry->register_tool(
    'memberpress_info',
    'Get MemberPress information',
    [$this, 'execute_tool'],
    ['type' => 'string', 'required' => true]
);
```

## See Also

- [Tool Implementation Map](../tool-system/tool-implementation-map.md)
- [Creating Custom Tools](../tool-system/creating-custom-tools.md)
```

### Example: Tutorial Documentation

```markdown
# Creating Your First Custom Tool

**Version:** 1.0.0  
**Last Updated:** 2025-04-05  
**Status:** ✅ Maintained  
**Owner:** Documentation Team

## Overview

This tutorial guides you through creating a custom tool for the MemberPress AI Assistant.

## Prerequisites

- MemberPress AI Assistant installed and activated
- Basic PHP knowledge
- Familiarity with WordPress plugin development

## Steps

### 1. Create tool class file

Create a new PHP file in the `includes/tools/implementations/` directory:

```php
<?php
/**
 * My Custom Tool
 *
 * @package MemberPress AI Assistant
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class MPAI_My_Custom_Tool extends MPAI_Base_Tool {
    // Tool implementation goes here
}
```

### 2. Implement required methods

Add the required methods to your tool class:

[Additional steps...]

## Troubleshooting

If you encounter issues, check these common problems:

- **Tool not appearing**: Ensure it's properly registered
- **Execution errors**: Check PHP error logs

## Next Steps

- [Advanced Tool Features](advanced-tool-features.md)
- [Tool Testing Guide](tool-testing-guide.md)
```

## Conclusion

This style guide establishes the foundation for consistent, high-quality documentation across the MemberPress AI Assistant project. Following these guidelines ensures that all documentation is easily navigable, technically accurate, and accessible to all users.

All contributors should familiarize themselves with this guide before creating or updating documentation. For questions or clarifications, please contact the Documentation Team.