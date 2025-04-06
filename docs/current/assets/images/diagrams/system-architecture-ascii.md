# System Architecture ASCII Diagram

## Overview
This ASCII diagram represents the MemberPress AI Assistant system architecture. It shows the relationships between major components and data flow through the system.

## Diagram
```
+-------------------------------------------------------+
|                    WordPress Site                      |
+-------------------------------------------------------+
             |                    |
             v                    v
+------------------------+  +------------------------+
|    MemberPress Core    |  |  MemberPress AI Asst.  |
+------------------------+  +------------------------+
    |              ^             |            ^
    |              |             |            |
    v              |             v            |
+------------------------+  +------------------------+
| User Data / Membership |  |    AI Processing       |
| Subscriptions / Rules  |  |    Engine              |
+------------------------+  +------------------------+
                                |            ^
                                |            |
                                v            |
                           +------------------------+
                           | External AI Services   |
                           |                        |
                           | +------------------+   |
                           | |  Claude API      |   |
                           | +------------------+   |
                           |                        |
                           | +------------------+   |
                           | |  OpenAI API      |   |
                           | +------------------+   |
                           +------------------------+
                                |            ^
                                |            |
                                v            |
                           +------------------------+
                           |   Response Cache      |
                           |   Storage System      |
                           +------------------------+
```

## Component Description

1. **WordPress Site**: The base platform where MemberPress and the AI Assistant are installed.

2. **MemberPress Core**: The main membership plugin that handles:
   - User registration and management
   - Subscription processing
   - Content protection
   - Access rules

3. **MemberPress AI Assistant**: The AI plugin extension that:
   - Provides the chat interface
   - Processes natural language requests
   - Manages AI service connections
   - Formats responses

4. **User Data / Membership**: Database containing:
   - Member profiles
   - Subscription details
   - Transaction history
   - Access permissions

5. **AI Processing Engine**: Core component that:
   - Interprets user requests
   - Selects appropriate AI services
   - Formats prompts for external services
   - Processes and sanitizes responses

6. **External AI Services**:
   - Claude API for advanced reasoning
   - OpenAI API for specialized tasks
   - Each has independent connection and authentication

7. **Response Cache Storage System**:
   - Stores common queries and responses
   - Reduces API usage
   - Improves response time
   - Handles periodic cache invalidation

## Data Flow

1. User submits question via MemberPress AI Assistant interface
2. Plugin processes request and retrieves relevant MemberPress data
3. AI Processing Engine formats prompt with context
4. External AI service processes the request
5. Response is cached and formatted
6. User receives response through the interface

## Notes for Final Diagram

- Use official MemberPress color scheme
- Emphasize bidirectional data flow with arrows
- Include icons for each major component
- Consider showing database connections explicitly
- Add user interface representation