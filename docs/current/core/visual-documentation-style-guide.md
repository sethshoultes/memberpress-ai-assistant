# Visual Documentation Style Guide

**Version:** 1.0.0  
**Last Updated:** 2025-04-06  
**Status:** 🚧 In Progress  
**Audience:** 👩‍💻 Developers, 👤 End Users, 🛠️ Administrators  
**Difficulty:** 🟢 Beginner  
**Reading Time:** ⏱️ 10 minutes

## Overview

This style guide establishes standards for visual elements in MemberPress AI Assistant documentation. Consistent visual presentation improves user experience, reinforces branding, and makes documentation more effective and engaging.

## Table of Contents

1. [Design Principles](#design-principles)
2. [Color Palette](#color-palette)
3. [Typography](#typography)
4. [Screenshots](#screenshots)
5. [Diagrams](#diagrams)
6. [Icons and Visual Indicators](#icons-and-visual-indicators)
7. [Layout and Composition](#layout-and-composition)
8. [Accessibility Considerations](#accessibility-considerations)
9. [File Management](#file-management)

## Design Principles

All visual elements in documentation should adhere to these core principles:

- **Clarity**: Visual elements should clarify concepts, not complicate them
- **Consistency**: Maintain visual coherence across all documentation
- **Purpose**: Each visual element should serve a specific communication purpose
- **Simplicity**: Focus on essential information and avoid visual clutter
- **Branding**: Align with MemberPress brand identity and design language

## Color Palette

Use these official colors for visual elements in documentation:

### Primary Colors

| Color | Hex Code | Usage |
|-------|----------|-------|
| MemberPress Blue | #1A6EBD | Primary brand color, main headings, important UI elements |
| MemberPress Dark Blue | #0C4D87 | Secondary accent, buttons, links |
| MemberPress Light Blue | #3399FF | Highlights, secondary elements, progress indicators |

### Secondary Colors

| Color | Hex Code | Usage |
|-------|----------|-------|
| MemberPress Green | #28A745 | Success states, positive indicators |
| MemberPress Red | #DC3545 | Error states, warnings, critical information |
| MemberPress Yellow | #FFC107 | Caution indicators, notes |
| MemberPress Gray | #6C757D | Neutral elements, background shading |

### Background and Text Colors

| Color | Hex Code | Usage |
|-------|----------|-------|
| White | #FFFFFF | Document backgrounds, primary container backgrounds |
| Light Gray | #F8F9FA | Secondary backgrounds, code blocks |
| Dark Gray | #343A40 | Primary text color |
| Medium Gray | #495057 | Secondary text, captions |

### Accessibility Note

Always maintain a minimum contrast ratio of 4.5:1 for normal text and 3:1 for large text between text and background colors to ensure readability.

## Typography

Documentation should use consistent typography across all visual elements:

### Fonts

- **Primary Font**: Open Sans (Sans-serif)
- **Secondary Font**: Roboto (Sans-serif)
- **Monospace Font**: Source Code Pro (for code elements)

### Text Styling

- **Headers**: Use MemberPress Blue (#1A6EBD)
- **Body Text**: Use Dark Gray (#343A40)
- **Links**: Use MemberPress Dark Blue (#0C4D87)
- **Captions**: Use Medium Gray (#495057)

### Font Sizes for Visual Elements

| Element | Size | Weight |
|---------|------|--------|
| Diagram Titles | 18px | Bold |
| Diagram Labels | 14px | Regular |
| Screenshot Annotations | 12px | Medium |
| Captions | 12px | Regular |
| Legend Text | 12px | Regular |

## Screenshots

Screenshots should provide clear visual representation of the interface:

### Capture Standards

- **Resolution**: Capture at 1920x1080 or higher resolution
- **Scaling**: Scale screenshots consistently, generally 100% unless focusing on details
- **Format**: Save as PNG for user interface screenshots
- **Crop**: Crop to relevant area only, eliminating unnecessary elements
- **Device Frame**: Use device frames for mobile screenshots

### Annotation Standards

- Use consistent annotation elements (arrows, highlights, numbers)
- Use MemberPress Red (#DC3545) for highlights and callouts
- Number sequential steps consistently
- Keep annotations outside the interface where possible
- Use 2px stroke width for annotation lines and shapes

### Example:

![Screenshot Example](../assets/images/screenshot-example.png)
*Caption: Example of properly annotated screenshot with numbered steps*

## Diagrams

Diagrams should follow consistent design patterns:

### Diagram Types and Templates

| Type | Usage | Template |
|------|-------|----------|
| Flow Diagrams | Process workflows, decision trees | [Flow Template](../assets/templates/flow-diagram-template.pptx) |
| Architecture Diagrams | System components and interactions | [Architecture Template](../assets/templates/architecture-diagram-template.pptx) |
| Concept Diagrams | Illustrating abstract concepts | [Concept Template](../assets/templates/concept-diagram-template.pptx) |
| Entity Relationship | Data relationships | [ER Template](../assets/templates/er-diagram-template.pptx) |

### Diagram Style

- Use consistent shapes for similar elements across diagrams
- Maintain adequate spacing between elements (minimum 20px)
- Use arrows with consistent styling for flow indication
- Include a legend for complex diagrams
- Label all major components
- Use MemberPress color palette for all elements

### Example:

![Diagram Example](../assets/images/diagram-example.png)
*Caption: Example architecture diagram using the standard template*

## Icons and Visual Indicators

Use consistent iconography throughout documentation:

### Standard Icons

| Icon | Usage | Code |
|------|-------|------|
| ✅ | Completed task, success | `:white_check_mark:` |
| 🚧 | Work in progress | `:construction:` |
| ⚠️ | Warning or caution | `:warning:` |
| ℹ️ | Information or note | `:information_source:` |
| 🔒 | Security feature or restriction | `:lock:` |
| 🔍 | Search or find feature | `:magnifying_glass:` |
| 💡 | Tip or suggestion | `:bulb:` |

### Target Audience Indicators

| Icon | Audience | Code |
|------|----------|------|
| 👩‍💻 | Developers | `:woman_technologist:` |
| 👤 | End Users | `:bust_in_silhouette:` |
| 🛠️ | Administrators | `:hammer_and_wrench:` |

### Difficulty Level Indicators

| Icon | Level | Code |
|------|-------|------|
| 🟢 | Beginner | `:green_circle:` |
| 🟡 | Intermediate | `:yellow_circle:` |
| 🔴 | Advanced | `:red_circle:` |

## Layout and Composition

Arrange visual elements consistently:

### Visual Hierarchy

- Place most important information at top-left (for left-to-right reading)
- Use size and color to indicate importance
- Group related elements with consistent spacing
- Use white space effectively to separate content sections

### Page Layout

- Align visual elements to a grid system
- Maintain consistent margins (minimum 20px)
- Limit diagram width to 800px for readability
- Position captions directly below visual elements
- Include alt text for all images

## Accessibility Considerations

Ensure visual elements are accessible to all users:

- Include descriptive alt text for all images
- Do not rely solely on color to convey information
- Maintain high contrast ratios for text elements
- Provide text descriptions for complex diagrams
- Use patterns in addition to colors for differentiation
- Ensure diagrams are understandable when printed in black and white

## File Management

Maintain organized file structure for visual assets:

### File Naming Convention

Format: `[doc-type]-[subject]-[version].[extension]`

Examples:
- `screenshot-dashboard-login-v1.png`
- `diagram-ai-workflow-v2.png`
- `icon-settings-v1.svg`

### File Organization

Store visual assets in the following directory structure:
```
/assets/
  /images/
    /screenshots/
    /diagrams/
    /icons/
  /templates/
```

### Version Control

- Increment version number for significant updates to visual elements
- Keep source files (e.g., Sketch, Figma, PowerPoint) in version control
- Document significant changes in the file metadata

## Implementation Checklist

- [ ] Create templates for each diagram type
- [ ] Develop screenshot annotation guidelines for documentation team
- [ ] Build icon library for common documentation elements
- [ ] Review existing documentation and identify visual enhancement opportunities
- [ ] Create directory structure for visual assets
- [ ] Develop training materials for documentation contributors

## Conclusion

This visual style guide provides a comprehensive framework for creating consistent, high-quality visual elements across all MemberPress AI Assistant documentation. By adhering to these standards, we ensure that our documentation is not only visually appealing but also more effective at communicating complex information to our users.

## Related Documents

- [Documentation Style Guide](documentation-style-guide.md)
- [Screenshot Standards Guide](screenshot-standards-guide.md)
- [Diagram Templates Library](diagram-templates-library.md)