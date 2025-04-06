# MemberPress AI Assistant: Diagram Template Guide

**Version:** 1.0.0  
**Last Updated:** 2025-04-06  
**Status:** ✅ Completed  
**Audience:** 👩‍💻 Developers, 👤 End Users, 🛠️ Administrators  
**Difficulty:** 🟡 Intermediate

## Overview

This guide provides standardized templates for creating diagrams for the MemberPress AI Assistant documentation. Following these templates ensures consistency in visual presentation across all documentation while maintaining the established branding and style guidelines.

## Table of Contents

1. [Diagram Types and Uses](#diagram-types-and-uses)
2. [Flow Diagram Template](#flow-diagram-template)
3. [Architecture Diagram Template](#architecture-diagram-template)
4. [Component Relationship Template](#component-relationship-template)
5. [Sequence Diagram Template](#sequence-diagram-template)
6. [Data Flow Diagram Template](#data-flow-diagram-template)
7. [Decision Tree Template](#decision-tree-template)
8. [Creating Diagrams](#creating-diagrams)

## Diagram Types and Uses

Choose the appropriate diagram type based on your documentation needs:

| Diagram Type | Best For | Example Use Cases |
|--------------|----------|-------------------|
| Flow Diagram | Processes and workflows | User interaction flows, administrative workflows |
| Architecture Diagram | System structure | Overall system architecture, deployment architecture |
| Component Relationship | Showing connections | Plugin components, integration points |
| Sequence Diagram | Time-based interactions | API communication, request handling |
| Data Flow Diagram | Data movement | Information processing, data transformation |
| Decision Tree | Decision points | Troubleshooting guides, configuration decisions |

## Flow Diagram Template

Flow diagrams illustrate processes, workflows, and sequential steps.

### Design Elements

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

### Style Guidelines

- **Start/End Nodes**: Rounded rectangles with MemberPress Blue fill (#1A6EBD)
- **Process Boxes**: Sharp-cornered rectangles with white fill and MemberPress Blue border
- **Decision Nodes**: Diamond shapes with light blue fill (#3399FF)
- **Arrows**: 2px MemberPress Blue directional arrows
- **Text**: Dark Gray (#343A40) Roboto font, centered in shapes
- **Layout**: Left-to-right or top-to-bottom flow
- **Numbering**: Optional step numbers (1, 2, 3) for complex flows

### Example Use

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

## Architecture Diagram Template

Architecture diagrams show the structural organization of systems and components.

### Design Elements

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

### Style Guidelines

- **System Boundaries**: Rounded rectangle with light gray fill (#F8F9FA)
- **Components**: Sharp-cornered rectangles with white fill and MemberPress Dark Blue border (#0C4D87)
- **External Systems**: Rounded rectangles with MemberPress Light Blue fill (#3399FF)
- **Connections**: Solid lines for direct connections, dashed for indirect
- **Arrows**: Bidirectional arrows where appropriate, 2px width
- **Text**: Component names in bold, Dark Gray (#343A40) Roboto font
- **Layout**: Hierarchical with primary components at top
- **Icons**: Use consistent component icons where appropriate

### Example Use

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

## Component Relationship Template

Component relationship diagrams show how different parts of a system interact.

### Design Elements

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

### Style Guidelines

- **Components**: Consistent sized rectangles with white fill and MemberPress Blue border
- **Relationships**: Labeled connecting lines with arrowheads indicating direction
- **Relationship Text**: Small italicized text on the connection lines
- **Component Text**: Bold component names in center of boxes
- **Optional Attributes**: Listed below component name in regular text
- **Layout**: Balanced spacing with minimal line crossings
- **Grouping**: Optional colored backgrounds to group related components

### Example Use

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

## Sequence Diagram Template

Sequence diagrams show time-ordered interactions between components.

### Design Elements

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

### Style Guidelines

- **Components**: Rectangles at top with component names
- **Lifelines**: Vertical dashed lines extending from components
- **Messages**: Solid arrows with sequenced numbering
- **Responses**: Dashed return arrows
- **Labels**: Concise text describing the action/message
- **Time**: Flows top to bottom
- **Optional Activations**: Rectangles on lifelines showing active processing
- **Colors**: MemberPress Blue for messages, MemberPress Gray for responses

### Example Use

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

## Data Flow Diagram Template

Data flow diagrams illustrate how data moves through a system.

### Design Elements

```
                     +------------------+
                     |                  |
                     |   DATA SOURCE    |
                     |                  |
                     +------------------+
                              |
                              | Data Type
                              v
+------------------+    +------------------+    +------------------+
|                  |    |                  |    |                  |
|  PROCESS A       |    |  PROCESS B       |    |  PROCESS C       |
|                  |    |                  |    |                  |
+------------------+    +------------------+    +------------------+
        |    ^               |    ^                   |
        |    |               |    |                   |
        +----+               +----+                   |
      Data Type            Data Type                  |
                                                      | Data Type
                                                      v
                                             +------------------+
                                             |                  |
                                             |  DATA STORE      |
                                             |                  |
                                             +------------------+
```

### Style Guidelines

- **Data Sources/Sinks**: Rectangles with MemberPress Light Blue fill (#3399FF)
- **Processes**: Rounded rectangles with MemberPress Blue border (#1A6EBD)
- **Data Stores**: Open-ended rectangles or cylinder shapes
- **Data Flows**: Arrows labeled with data types
- **Flow Direction**: Indicated by arrowheads
- **Labels**: All elements and flows should be clearly labeled
- **Levels**: Can be hierarchical with increasing detail

### Example Use

```
Data Flow Diagram: AI Assistant Knowledge Processing

                     +------------------+
                     |                  |
                     |  MEMBER DATABASE |
                     |                  |
                     +------------------+
                              |
                              | Member Records
                              v
+------------------+    +------------------+    +------------------+
|                  |    |                  |    |                  |
|  DATA            |    |  CONTEXT         |    |  QUERY           |
|  EXTRACTION      |    |  ENRICHMENT      |    |  PROCESSING      |
|                  |    |                  |    |                  |
+------------------+    +------------------+    +------------------+
        |    ^               |    ^                   |
        |    |               |    |                   |
        +----+               +----+                   |
       Raw Data         Enriched Data                 |
                                                      | Formatted Query
                                                      v
                                             +------------------+
                                             |                  |
                                             |  AI SERVICE      |
                                             |                  |
                                             +------------------+
```

## Decision Tree Template

Decision trees illustrate choices and possible outcomes in a hierarchical structure.

### Design Elements

```
                      +-------------------+
                      |                   |
                      |   START POINT     |
                      |                   |
                      +-------------------+
                               |
                               |
                  +-------------------------+
                  |                         |
                  |   DECISION QUESTION?    |
                  |                         |
                  +-------------------------+
                 /                           \
                /                             \
               /                               \
        +----------+                      +----------+
        |          |                      |          |
        |   YES    |                      |    NO    |
        |          |                      |          |
        +----------+                      +----------+
             |                                  |
             |                                  |
      +------------+                     +------------+
      |            |                     |            |
      | OUTCOME A  |                     | OUTCOME B  |
      |            |                     |            |
      +------------+                     +------------+
```

### Style Guidelines

- **Start Point**: Rounded rectangle with MemberPress Blue fill (#1A6EBD)
- **Decision Nodes**: Diamond shapes with light blue fill (#3399FF)
- **Answers**: Small rectangles with MemberPress Green (#28A745) for Yes/Positive and MemberPress Red (#DC3545) for No/Negative
- **Outcomes**: Rounded rectangles with white fill and MemberPress Dark Blue border (#0C4D87)
- **Connecting Lines**: Straight lines with directional arrows
- **Text**: Clear, concise questions and answers
- **Layout**: Balanced tree with consistent spacing

### Example Use

```
Decision Tree: AI Service Provider Selection

                      +-------------------+
                      |                   |
                      |   SELECT AI       |
                      |   PROVIDER        |
                      +-------------------+
                               |
                               |
                  +-------------------------+
                  |                         |
                  |   NEED ADVANCED         |
                  |   CAPABILITIES?         |
                  +-------------------------+
                 /                           \
                /                             \
               /                               \
        +----------+                      +----------+
        |          |                      |          |
        |   YES    |                      |    NO    |
        |          |                      |          |
        +----------+                      +----------+
             |                                  |
             |                                  |
      +------------+                     +------------+
      |            |                     |            |
      | USE CLAUDE |                     | USE GPT-3.5|
      | OR GPT-4   |                     |            |
      +------------+                     +------------+
             |
             |
      +-------------------------+
      |                         |
      |   BUDGET CONCERNS?      |
      |                         |
      +-------------------------+
     /                           \
    /                             \
   /                               \
+----------+                  +----------+
|          |                  |          |
|   YES    |                  |    NO    |
|          |                  |          |
+----------+                  +----------+
     |                              |
     |                              |
+------------+                +------------+
|            |                |            |
| USE CLAUDE |                | USE GPT-4  |
| 3 SONNET   |                |            |
+------------+                +------------+
```

## Creating Diagrams

### Recommended Tools

The following tools are recommended for creating diagrams using these templates:

1. **Draw.io / diagrams.net**: Free, web-based or desktop diagramming tool
   - Use our custom template library: [MemberPress Templates](../templates/memberpress-drawio-templates.xml)

2. **Lucidchart**: Professional diagramming tool with team collaboration
   - MemberPress template set available at: [MemberPress Lucidchart Templates](../templates/memberpress-lucidchart-templates.zip)

3. **Microsoft Visio**: Enterprise diagramming solution
   - Visio template stencils: [MemberPress Visio Templates](../templates/memberpress-visio-templates.vssx)

4. **Mermaid.js**: For code-based diagram generation
   - Example syntax files: [MemberPress Mermaid Templates](../templates/memberpress-mermaid-examples.md)

### File Format Standards

When saving diagrams, use the following standards:

1. **Working Files**: 
   - Save source files in the native format of your diagramming tool
   - Store in the `/assets/templates/diagrams/source/` directory
   - Include version number in filename

2. **Export Formats**:
   - Primary: PNG format at 144dpi with transparent background
   - Secondary: SVG for web display where appropriate
   - Store in the `/assets/images/diagrams/` directory

3. **Naming Convention**:
   - Format: `diagram-[type]-[subject]-v[version].png`
   - Example: `diagram-flow-authentication-v1.png`

### Template Files

The following template files are available for immediate use:

1. **[Flow Diagram Template](../templates/flow-diagram-template.pptx)**
2. **[Architecture Diagram Template](../templates/architecture-diagram-template.pptx)**
3. **[Component Relationship Template](../templates/component-relationship-template.pptx)**
4. **[Sequence Diagram Template](../templates/sequence-diagram-template.pptx)**
5. **[Data Flow Diagram Template](../templates/data-flow-diagram-template.pptx)**
6. **[Decision Tree Template](../templates/decision-tree-template.pptx)**

---

*This guide is regularly updated as new diagram types and templates are developed. Last updated: April 6, 2025.*