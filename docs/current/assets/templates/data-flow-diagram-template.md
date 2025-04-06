# Data Flow Diagram Template

This template provides guidance for creating data flow diagrams for the MemberPress AI Assistant documentation.

## Basic Structure

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

## Style Guidelines

- **Data Sources/Sinks**: Rectangles with MemberPress Light Blue fill (#3399FF)
- **Processes**: Rounded rectangles with MemberPress Blue border (#1A6EBD)
- **Data Stores**: Open-ended rectangles or cylinder shapes
- **Data Flows**: Arrows labeled with data types
- **Flow Direction**: Indicated by arrowheads
- **Labels**: All elements and flows should be clearly labeled
- **Levels**: Can be hierarchical with increasing detail

## Example Implementation

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

When creating the final diagram, use the PowerPoint or Draw.io template with the proper colors, shapes, and styles from the Visual Documentation Style Guide.