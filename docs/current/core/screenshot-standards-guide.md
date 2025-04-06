# Screenshot Standards Guide

**Version:** 1.0.0  
**Last Updated:** 2025-04-06  
**Status:** ✅ Completed  
**Audience:** 👩‍💻 Developers, 👤 End Users, 🛠️ Administrators  
**Difficulty:** 🟢 Beginner  
**Reading Time:** ⏱️ 12 minutes

## Overview

This guide establishes standards for creating and using screenshots in MemberPress AI Assistant documentation. By following these standards, we ensure consistency, clarity, and professionalism across all documentation.

## Table of Contents

1. [Capturing Screenshots](#capturing-screenshots)
2. [Editing and Formatting](#editing-and-formatting)
3. [Annotation Guidelines](#annotation-guidelines)
4. [File Management](#file-management)
5. [Usage in Documentation](#usage-in-documentation)
6. [Mobile and Responsive View Screenshots](#mobile-and-responsive-view-screenshots)
7. [Accessibility Considerations](#accessibility-considerations)
8. [Tools and Resources](#tools-and-resources)

## Capturing Screenshots

### Recommended Tools

- **macOS**: Use built-in Screenshot utility (⌘+Shift+5) or Snagit
- **Windows**: Use Snipping Tool, Snip & Sketch, or Snagit
- **Browser Extensions**: Nimbus Screenshot or Awesome Screenshot are acceptable alternatives

### Resolution and Display Settings

- **Resolution**: Capture at 1920x1080 or higher resolution
- **Scaling**: Use 100% screen scaling (no zooming in or out)
- **Browser Zoom**: Set to 100% (default)
- **Browser Window**: Maximize the browser window before capturing
- **Color Profile**: sRGB or Display P3

### Environment Preparation

1. **Clear Visual Space**:
   - Close unnecessary tabs, windows, and applications
   - Hide browser extensions and unused toolbars
   - Use private/incognito mode to hide personalized content

2. **Clean Testing Environment**:
   - Use a clean test site when possible
   - Remove unnecessary notifications, popups, or banners
   - Use consistent demo data (see [Demo Content Guide](demo-content-guide.md))

3. **UI State**:
   - Capture UI in its default state (unless demonstrating a specific interaction)
   - For interactive elements, show both before and after states
   - For forms, show both empty and filled states when relevant

### Capture Boundaries

- **Full Page**: Include the entire interface when context is important
- **Feature Focus**: Crop to feature boundaries when focusing on a specific element
- **Consistent Margins**: Include 10-20px margin around the subject
- **Browser Chrome**: Include browser address bar only when relevant to the task

## Editing and Formatting

### Image Format and Size

- **Format**: Save as PNG for interface screenshots
- **File Size**: Optimize below 200KB when possible without quality loss
- **Dimensions**: Maximum width of 1600px, maximum height of 900px
- **Aspect Ratio**: Maintain original aspect ratio; do not stretch or distort

### Color and Appearance

- **Color Mode**: RGB color (sRGB color space)
- **Color Depth**: 24-bit
- **Background**: Use neutral backgrounds (white or light gray) when possible
- **Brightness/Contrast**: Adjust only if necessary for clarity
- **Reflections/Shadows**: Remove unless part of the intended visual design

### Cropping and Framing

- **Focus**: Center the primary subject/action area
- **Context**: Include enough surrounding UI to provide context
- **Whitespace**: Remove excessive whitespace but maintain margins
- **Alignment**: Use consistent alignment across related screenshots

### Visual Enhancements

- **Highlights**: Use subtle highlights for emphasis (MemberPress Blue #1A6EBD at 25% opacity)
- **Zoom Insets**: For small details, use zoom insets with 2px blue border
- **Focus Effects**: Subtle spotlight effect for highlighting is acceptable
- **Device Frames**: Use device frames for mobile/tablet screenshots

## Annotation Guidelines

### Annotation Elements

- **Arrows**: Use straight arrows with consistent styling
  - 2px width
  - MemberPress Red (#DC3545)
  - 30% opacity shadow
- **Circles/Ovals**: Use for highlighting areas
  - 2px stroke width
  - MemberPress Red (#DC3545)
  - No fill or 10% fill opacity
- **Numbered Indicators**: Use for sequential steps
  - 20px diameter circle
  - MemberPress Blue (#1A6EBD) with white number
  - 12pt bold Roboto font
- **Text Labels**: Use for descriptions
  - Roboto 12pt
  - Dark Gray (#343A40)
  - Match case with UI text (title case for headings, sentence case for descriptions)

### Annotation Placement

- **Position**: Place annotations outside the UI when possible
- **Alignment**: Align annotation text horizontally
- **Spacing**: Maintain consistent spacing between annotations
- **Readability**: Ensure annotations don't overlap or obscure important UI elements
- **Consistency**: Use consistent annotation style across related screenshots

### Step Sequences

- **Numbering**: Use sequential numbers starting from 1
- **Flow Direction**: Left-to-right, top-to-bottom sequence
- **Placement**: Place numbers at the starting point of each action
- **First Action**: Always place #1 in the top-left area when possible

## File Management

### File Naming Convention

Format: `[feature]-[action]-[state]-[version].png`

Examples:
- `ai-assistant-chat-interface-open.png`
- `member-search-results-filtered.png`
- `dashboard-revenue-chart-expanded.png`

Guidelines:
- Use lowercase letters
- Use hyphens to separate words
- Be descriptive but concise
- Include state/variant if multiple versions exist

### Directory Structure

Store screenshots in the following directory structure:
```
/assets/
  /images/
    /screenshots/
      /ui/
      /user-workflows/
      /admin-workflows/
      /developer-workflows/
      /mobile/
```

### Versioning

- **Version Control**: Include version number in filename when replacing existing screenshots
- **Archiving**: Move outdated screenshots to an archive folder rather than deleting
- **Tracking**: Document screenshot updates in the image metadata and in version control commit messages

## Usage in Documentation

### Placement Guidelines

- **Context First**: Always provide context before presenting a screenshot
- **Reference**: Clearly reference the screenshot in the text
- **Proximity**: Place screenshots near their relevant text
- **Whitespace**: Use consistent spacing before and after screenshots

### Caption Standards

- **Format**: Italicized text below the screenshot
- **Content**: Brief description of what the screenshot depicts
- **Style**: Sentence case, descriptive but concise
- **Period**: End with a period only if the caption is a complete sentence

### Screenshot Density

- **Appropriate Use**: Include screenshots only when they add value
- **Grouping**: Group related screenshots together
- **Pacing**: For tutorials, include screenshots at consistent intervals
- **Series**: For complex procedures, show key steps (not every click)

### Referencing Screenshots

When referencing screenshots in text:
- Use the phrase "as shown in the screenshot below" or "see the screenshot above"
- For numbered steps, reference the step number: "as shown in step 3"
- For multiple screenshots, use descriptive references: "in the member dashboard screenshot"

## Mobile and Responsive View Screenshots

### Mobile Device Standards

- **Device Types**: Use current iPhone and Android reference devices
- **Orientation**: Primarily use portrait orientation
- **Device Frame**: Include device frame for context
- **Platform Indicators**: When platform-specific, show both iOS and Android versions

### Responsive Design Views

- **Breakpoints**: Capture at standard breakpoints:
  - Desktop: 1920x1080
  - Tablet: 768x1024
  - Mobile: 375x667
- **Consistent States**: Show the same content/state across different screen sizes
- **Layout Changes**: Highlight significant layout differences between breakpoints

### Capturing Responsive Views

- **Browser Tools**: Use browser developer tools responsive mode
- **Device Simulation**: Use actual devices when possible, device emulators as second choice
- **Resolution**: Capture at 1x resolution (not scaled)
- **Frame Options**: Include browser chrome in responsive mode screenshots when demonstrating how to use responsive testing features

## Accessibility Considerations

### Alternative Text

- **Purpose**: Include detailed alt text for all screenshots
- **Description Format**: "Screenshot of [interface] showing [key elements]"
- **Context**: Describe the purpose, not just the visual elements
- **Action Focus**: For workflow steps, describe the action being performed
- **Length**: Keep alt text concise but comprehensive (125 characters max)

### Color Accessibility

- **Contrast**: Ensure annotation colors maintain 4.5:1 contrast ratio with background
- **Color Independence**: Don't rely solely on color for conveying information
- **Pattern Use**: Add patterns when using colors to differentiate areas

### Text Legibility

- **Font Size**: Ensure text in screenshots is legible (minimum 12px in final render)
- **Zoom Support**: For small UI details, provide zoomed-in views
- **Text Alternative**: Include transcription of key text in screenshot captions or body

## Tools and Resources

### Recommended Screenshot Tools

| Tool | Platform | Key Features | Best For |
|------|----------|--------------|----------|
| Snagit | Windows, macOS | Powerful editor, scrolling capture | Complex annotations |
| Nimbus Screenshot | Browser extension | Simple capture and editing | Quick documentation |
| Skitch | macOS | Simple annotation | Basic highlighting |
| ShareX | Windows | Workflow automation | Batch screenshot creation |
| Monosnap | macOS, Windows | Cloud uploads, basic editing | Quick sharing |

### Annotation Resources

- **Arrow Library**: Standard arrow set available in `/assets/images/annotation-elements/arrows/`
- **Number Icons**: Standard number icons available in `/assets/images/annotation-elements/numbers/`
- **Templates**: Photoshop and Sketch templates in `/assets/templates/screenshot-templates/`

### Training Resources

- [Internal Screenshot Training Video](https://memberpress.com/internal/screenshot-training)
- [Screenshot Creation Workshop Materials](https://memberpress.com/internal/workshop-materials)
- [Sample Screenshot Collection](https://memberpress.com/internal/screenshot-examples)

---

*This guide should be reviewed and updated annually or when major UI changes occur.*