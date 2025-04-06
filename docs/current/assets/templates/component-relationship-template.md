# Component Relationship Diagram Template

This template provides guidance for creating component relationship diagrams for the MemberPress AI Assistant documentation.

## Basic Structure

```
      +-------------------+                  +-------------------+
      |                   |                  |                   |
      |   COMPONENT A     |<---------------->|   COMPONENT B     |
      |                   |   Relationship   |                   |
      +-------------------+   Type/Label     +-------------------+
               ^                                      ^
               |                                      |
               | Relationship                         | Relationship
               | Type/Label                           | Type/Label
               |                                      |
      +-------------------+                  +-------------------+
      |                   |                  |                   |
      |   COMPONENT C     |<---------------->|   COMPONENT D     |
      |                   |   Relationship   |                   |
      +-------------------+   Type/Label     +-------------------+
               |
               | Relationship
               | Type/Label
               v
      +-------------------+
      |                   |
      |   COMPONENT E     |
      |                   |
      +-------------------+
```

## Style Guidelines

- **Components**: Consistent sized rectangles with white fill and MemberPress Blue border
- **Relationships**: Labeled connecting lines with arrowheads indicating direction
- **Relationship Text**: Small italicized text on the connection lines
- **Component Text**: Bold component names in center of boxes
- **Optional Attributes**: Listed below component name in regular text
- **Layout**: Balanced spacing with minimal line crossings
- **Grouping**: Optional colored backgrounds to group related components

## Example Implementation

```
Component Relationship Diagram: AI Assistant Integration Points

      +-------------------+                  +-------------------+
      |                   |                  |                   |
      |   User Interface  |<---------------->|   Query Handler   |
      |   Component       |   Sends Query    |                   |
      +-------------------+   Displays Result +-------------------+
               ^                                      ^
               |                                      |
               | Shows To                             | Processes
               | User                                 | Query
               |                                      |
      +-------------------+                  +-------------------+
      |                   |                  |                   |
      |   WordPress User  |<---------------->|   AI Service      |
      |                   |   Authenticates  |   Provider        |
      +-------------------+                  +-------------------+
               |
               | Has Access To
               |
               v
      +-------------------+
      |                   |
      |   Member Data     |
      |                   |
      +-------------------+
```

When creating the final diagram, use the PowerPoint or Draw.io template with the proper colors, shapes, and styles from the Visual Documentation Style Guide.