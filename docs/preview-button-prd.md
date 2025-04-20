# Product Requirements Document: Blog Post Preview Button

## Overview
This document outlines the requirements for adding a new "Preview" button to the MemberPress AI Assistant blog post interface. This feature will allow users to preview how a blog post will look before creating it as an actual WordPress post.

## Problem Statement
Currently, users can view the raw XML content of an AI-generated blog post and create the post directly, but they cannot preview how the formatted post will look without actually creating it. This leads to uncertainty about the final appearance and may result in unnecessary post creation and editing.

## User Stories
- As a content creator, I want to preview how my AI-generated blog post will look before creating it, so I can ensure it meets my expectations.
- As a site administrator, I want to review the formatted content of AI-generated posts before they are added to the WordPress database.

## Requirements

### Functional Requirements
1. Add a "Preview" button to the blog post preview card, positioned next to the "View XML" button.
2. When clicked, the Preview button should display a formatted preview of the blog post content directly within the chat interface.
3. The preview should accurately represent how the content would appear in a WordPress post, including proper formatting of:
   - Headings
   - Paragraphs
   - Lists
   - Blockquotes
   - Code blocks
4. The preview should be toggleable - clicking the button again should hide the preview.
5. The preview should not create an actual WordPress post.
6. The preview functionality should work for both blog posts and pages.

### Non-Functional Requirements
1. Performance: The preview should render quickly, with minimal delay after clicking the button.
2. Usability: The preview should be clearly distinguishable from other elements in the chat interface.
3. Compatibility: The feature should work across all major browsers and devices.
4. Maintainability: The code should be well-documented and follow existing coding patterns.

### UI/UX Requirements
1. The Preview button should have a consistent style with the existing "Create Post" and "View XML" buttons.
2. The preview container should have a clean, readable design that mimics WordPress post styling.
3. The preview should include visual indicators for different content elements (headings, lists, etc.).
4. The button should change text to "Hide Preview" when the preview is visible.

## Technical Approach
1. Modify the `mpai-blog-formatter.js` file to add the new button and preview functionality.
2. Leverage the existing `convertXmlBlocksToHtml` function to transform XML content to formatted HTML.
3. Add CSS styles in `blog-post-preview.css` for the preview button and container.
4. Implement toggle functionality to show/hide the preview.

## Success Metrics
1. Users can successfully preview blog posts before creating them.
2. The preview accurately represents how the post will appear when created.
3. The feature works reliably across different browsers and devices.

## Timeline
- Development: 1-2 days
- Testing: 1 day
- Deployment: 1 day

## Future Enhancements (Out of Scope)
- Add ability to edit the post content directly in the preview.
- Implement a full-screen preview mode.
- Add print functionality for the preview.