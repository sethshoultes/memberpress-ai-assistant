# Sequence Diagram Template

This template provides guidance for creating sequence diagrams for the MemberPress AI Assistant documentation.

## Basic Structure

```
+-------------+       +-------------+        +-------------+
|             |       |             |        |             |
| Component A |       | Component B |        | Component C |
|             |       |             |        |             |
+-------------+       +-------------+        +-------------+
      |                     |                      |
      | 1. Action           |                      |
      |-------------------->|                      |
      |                     |                      |
      |                     | 2. Action            |
      |                     |--------------------->|
      |                     |                      |
      |                     | 3. Response          |
      |                     |<---------------------|  
      |                     |                      |
      | 4. Response         |                      |
      |<--------------------|                      |
      |                     |                      |
```

## Style Guidelines

- **Components**: Rectangles at top with component names
- **Lifelines**: Vertical dashed lines extending from components
- **Messages**: Solid arrows with sequenced numbering
- **Responses**: Dashed return arrows
- **Labels**: Concise text describing the action/message
- **Time**: Flows top to bottom
- **Optional Activations**: Rectangles on lifelines showing active processing
- **Colors**: MemberPress Blue for messages, MemberPress Gray for responses

## Example Implementation

```
Sequence Diagram: AI Query Processing

+-------------+       +-------------+        +-------------+
|             |       |             |        |             |
|    User     |       | AI Assistant |        | AI Service  |
|             |       |             |        |             |
+-------------+       +-------------+        +-------------+
      |                     |                      |
      | 1. Ask Question     |                      |
      |-------------------->|                      |
      |                     |                      |
      |                     | 2. Format Query      |
      |                     |--------------------->|
      |                     |                      |
      |                     | 3. Generate Response |
      |                     |<---------------------|
      |                     |                      |
      | 4. Display Answer   |                      |
      |<--------------------|                      |
      |                     |                      |
```

When creating the final diagram, use the PowerPoint or Draw.io template with the proper colors, shapes, and styles from the Visual Documentation Style Guide.