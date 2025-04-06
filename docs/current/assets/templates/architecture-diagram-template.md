# Architecture Diagram Template

This template provides guidance for creating architecture diagrams for the MemberPress AI Assistant documentation.

## Basic Structure

```
+----------------------------------------------------------------------+
|                                                                      |
|                        SYSTEM BOUNDARY                               |
|                                                                      |
|  +------------------------+          +------------------------+      |
|  |                        |          |                        |      |
|  |     COMPONENT A        |<-------->|     COMPONENT B        |      |
|  |                        |          |                        |      |
|  +------------------------+          +------------------------+      |
|                |                                |                    |
|                v                                v                    |
|  +------------------------+          +------------------------+      |
|  |                        |          |                        |      |
|  |     COMPONENT C        |<-------->|     COMPONENT D        |      |
|  |                        |          |                        |      |
|  +------------------------+          +------------------------+      |
|                                              |                       |
|                                              v                       |
|  +----------------------------------------------------------+       |
|  |                                                          |       |
|  |                     EXTERNAL SYSTEM                      |       |
|  |                                                          |       |
|  +----------------------------------------------------------+       |
|                                                                      |
+----------------------------------------------------------------------+
```

## Style Guidelines

- **System Boundaries**: Rounded rectangle with light gray fill (#F8F9FA)
- **Components**: Sharp-cornered rectangles with white fill and MemberPress Dark Blue border (#0C4D87)
- **External Systems**: Rounded rectangles with MemberPress Light Blue fill (#3399FF)
- **Connections**: Solid lines for direct connections, dashed for indirect
- **Arrows**: Bidirectional arrows where appropriate, 2px width
- **Text**: Component names in bold, Dark Gray (#343A40) Roboto font
- **Layout**: Hierarchical with primary components at top
- **Icons**: Use consistent component icons where appropriate

## Example Implementation

```
Architecture Diagram: MemberPress AI Assistant System

+----------------------------------------------------------------------+
|                                                                      |
|                        WORDPRESS ENVIRONMENT                         |
|                                                                      |
|  +------------------------+          +------------------------+      |
|  |                        |          |                        |      |
|  |    MEMBERPRESS         |<-------->|    AI ASSISTANT        |      |
|  |    CORE                |          |    PLUGIN              |      |
|  +------------------------+          +------------------------+      |
|                |                                |                    |
|                v                                v                    |
|  +------------------------+          +------------------------+      |
|  |                        |          |                        |      |
|  |    MEMBER DATA         |<-------->|    AI PROCESSING       |      |
|  |    REPOSITORY          |          |    ENGINE              |      |
|  +------------------------+          +------------------------+      |
|                                              |                       |
|                                              v                       |
|  +----------------------------------------------------------+       |
|  |                                                          |       |
|  |                     AI SERVICE PROVIDER                  |       |
|  |                                                          |       |
|  +----------------------------------------------------------+       |
|                                                                      |
+----------------------------------------------------------------------+
```

When creating the final diagram, use the PowerPoint or Draw.io template with the proper colors, shapes, and styles from the Visual Documentation Style Guide.