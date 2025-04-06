# Decision Tree Diagram Template

This template provides guidance for creating decision tree diagrams for the MemberPress AI Assistant documentation.

## Basic Structure

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

## Style Guidelines

- **Start Point**: Rounded rectangle with MemberPress Blue fill (#1A6EBD)
- **Decision Nodes**: Diamond shapes with light blue fill (#3399FF)
- **Answers**: Small rectangles with MemberPress Green (#28A745) for Yes/Positive and MemberPress Red (#DC3545) for No/Negative
- **Outcomes**: Rounded rectangles with white fill and MemberPress Dark Blue border (#0C4D87)
- **Connecting Lines**: Straight lines with directional arrows
- **Text**: Clear, concise questions and answers
- **Layout**: Balanced tree with consistent spacing

## Example Implementation

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

When creating the final diagram, use the PowerPoint or Draw.io template with the proper colors, shapes, and styles from the Visual Documentation Style Guide.