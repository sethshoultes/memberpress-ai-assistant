# Flow Diagram Template

This template provides guidance for creating flow diagrams for the MemberPress AI Assistant documentation.

## Basic Structure

```
+----------------+    +----------------+    +----------------+
|                |    |                |    |                |
|  START/INPUT   | -> |  PROCESS BOX   | -> |  END/OUTPUT   |
|                |    |                |    |                |
+----------------+    +----------------+    +----------------+
                            |
                            v
                      +----------------+
                      |                |
                      | DECISION NODE  |
                      |                |
                      +----------------+
                       /             \
                      /               \
                     v                 v
             +----------------+ +----------------+
             |                | |                |
             |   PATH ONE     | |   PATH TWO     |
             |                | |                |
             +----------------+ +----------------+
```

## Style Guidelines

- **Start/End Nodes**: Rounded rectangles with MemberPress Blue fill (#1A6EBD)
- **Process Boxes**: Sharp-cornered rectangles with white fill and MemberPress Blue border
- **Decision Nodes**: Diamond shapes with light blue fill (#3399FF)
- **Arrows**: 2px MemberPress Blue directional arrows
- **Text**: Dark Gray (#343A40) Roboto font, centered in shapes
- **Layout**: Left-to-right or top-to-bottom flow
- **Numbering**: Optional step numbers (1, 2, 3) for complex flows

## Example Implementation

```
Flow Diagram: User Authentication Process

+----------------+    +----------------+    +----------------+
|                |    |                |    |                |
|  User Login    | -> |  Authenticate  | -> | Access Granted |
|  Request       |    |  Credentials   |    |                |
+----------------+    +----------------+    +----------------+
                            |
                            v
                      +----------------+
                      |                |
                      | Valid          |
                      | Credentials?   |
                      +----------------+
                       /             \
                      /               \
                     v                 v
             +----------------+ +----------------+
             |                | |                |
             |   YES          | |   NO           |
             |                | |                |
             +----------------+ +----------------+
                     |                 |
                     v                 v
             +----------------+ +----------------+
             |                | |                |
             | Load User      | | Show Error     |
             | Dashboard      | | Message        |
             +----------------+ +----------------+
```

When creating the final diagram, use the PowerPoint or Draw.io template with the proper colors, shapes, and styles from the Visual Documentation Style Guide.