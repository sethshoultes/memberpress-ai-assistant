# MemberPress AI Assistant Glossary

**Version:** 1.0.0  
**Last Updated:** 2025-04-05  
**Status:** 🚧 In Progress  
**Owner:** Documentation Team

## Overview

This glossary defines key terms, concepts, and acronyms used throughout the MemberPress AI Assistant documentation. It provides a consistent reference for terminology to ensure clear communication and understanding across the project.

## How to Use This Glossary

- Terms are listed in alphabetical order
- Each term includes a definition and, where applicable, examples or related terms
- Links to relevant documentation are provided when available
- Use this glossary as a reference when reading or creating documentation

## Terms

### Agent

A specialized AI component designed to handle specific types of tasks or domains. Agents use natural language processing capabilities to understand and respond to user requests within their area of expertise.

**Example:** The MemberPress Agent specializes in handling MemberPress-specific questions and commands.

**Related terms:** Agent Orchestrator, Base Agent, Specialized Agent

**See also:** [Comprehensive Agent System Guide](../agent-system/comprehensive-agent-system-guide.md)

### Agent Orchestrator

The central component that coordinates multiple specialized agents, routing requests to the most appropriate agent based on the content and context of the request.

**Related terms:** Agent, Agent Routing

**See also:** [Agent Orchestrator](../agent-system/comprehensive-agent-system-guide.md#agent-orchestrator)

### Agent Scoring

The process of evaluating which specialized agent is best suited to handle a specific user request based on confidence scores calculated for each agent.

**Example:** When processing a user request about MemberPress subscriptions, the Agent Scoring system would assign a high confidence score to the MemberPress Agent.

**See also:** [Agent Specialization Scoring](../../_snacks/agents/agent-specialization-scoring.md)

### API Router

A component that manages communication between the plugin and multiple AI providers (such as OpenAI and Anthropic), routing requests to the appropriate provider and handling fallbacks if necessary.

**Related terms:** OpenAI, Anthropic, Claude

**See also:** [API Router](../core/system-map.md#api-router)

### Chat Interface

The user interface that allows users to interact with the AI assistant through natural language conversations.

**Related terms:** Chat UI, Chat History

**See also:** [Chat Interface](../core/system-map.md#chat-interface)

### Command System

A subsystem that processes and executes WordPress and MemberPress commands issued through the AI assistant.

**Related terms:** Command Validation, Command Detection, Command Execution

**See also:** [Command System](../core/system-map.md#command-system)

### Content Marker System

A system that uses HTML comments to mark and identify different types of content generated by the AI assistant, facilitating accurate content retrieval and processing.

**Example:** `<!-- MPAI_CONTENT_MARKER:blog_post:2025-04-01T12:00:00Z -->`

**See also:** [Content Marker System](../content-system/CONTENT_MARKER_SYSTEM.md)

### Context Manager

A component that maintains the conversation context between the user and the AI assistant, including handling tool execution and managing conversation state.

**Related terms:** Conversation History, State Management

**See also:** [Context Manager](../core/system-map.md#context-manager)

### Error Recovery System

A system designed to handle errors gracefully, implementing retry mechanisms, fallbacks, and user-friendly error messages to maintain system stability.

**Related terms:** Error Handling, Fallback Mechanism

**See also:** [Error Recovery System](../error-system/error-recovery-system.md)

### Function Calling

A capability of modern AI models that allows the AI to request the execution of specific functions with structured parameters to perform tasks beyond text generation.

**Example:** When a user asks to create a new blog post, the AI uses function calling to invoke the appropriate WordPress API function with title, content, and other parameters.

**Related terms:** Tool Call, Tool Execution

### Independent Operation Mode

A feature that allows the MemberPress AI Assistant to function with limited capabilities even when the MemberPress plugin is not active or installed.

**See also:** [Independent Operation Implementation](../../archive/MEMBERPRESS_INDEPENDENT_OPERATION.md)

### MemberPress API

An internal API that provides a standardized interface for interacting with MemberPress functionality, abstracting the underlying implementation details.

**Related terms:** MemberPress Integration

**See also:** [MemberPress API](../core/system-map.md#memberpress-api)

### MPAI

Acronym for MemberPress AI Assistant, used as a prefix for class names, functions, and hooks throughout the codebase.

**Example:** `MPAI_Chat`, `mpai_process_message`, `mpai_tool_registry`

### Scooby Mode

An investigation mode triggered by specific phrases ("Scooby Mode", "Scooby Doo", "Scooby", "Jinkies") that shifts the assistant's behavior to a diagnostic approach focused on problem-solving.

**Related terms:** Scooby Snack, Investigation Protocol

**See also:** [Scooby Snack Protocol](../../_snacks/README.md)

### Scooby Snack

A documented solution to a problem or investigation, following a standardized format that includes the problem statement, investigation steps, solution, and lessons learned.

**Example:** "🦴 Scooby Snack: Fixed duplicate error logs at session start with proper singleton pattern"

**See also:** [Scooby Snacks Index](../../_snacks/index.md)

### State Validation System

A system that ensures the consistency and validity of the plugin's internal state by enforcing invariants and validating pre/post conditions for operations.

**Related terms:** Invariant, Validation, State Management

**See also:** [State Validation System](../error-system/state-validation-system.md)

### System Cache

A caching mechanism that stores frequently accessed system information to improve performance and reduce redundant API calls.

**Related terms:** Caching, Performance Optimization

**See also:** [System Information Caching](system-information-caching.md)

### Tool

A functionality module that can be invoked by the AI assistant to perform specific tasks, such as retrieving data or executing commands.

**Example:** `memberpress_info` tool, `wp_api` tool, `diagnostic_tool`

**Related terms:** Tool Registry, Tool Execution

**See also:** [Tool Implementation Map](../tool-system/tool-implementation-map.md)

### Tool Call

A structured request from the AI assistant to execute a specific tool with defined parameters.

**Example:** 
```json
{
  "name": "memberpress_info",
  "parameters": {
    "type": "subscriptions"
  }
}
```

**Related terms:** Function Calling, Tool Execution

**See also:** [Tool Call Detection](../tool-system/tool-call-detection.md)

### Tool Registry

A central registry that manages all available tools, their parameters, and execution handlers.

**Related terms:** Tool, Tool Call

**See also:** [Tool Registry](../tool-system/tool-implementation-map.md#tool-registry)

### XML Content System

A system that uses structured XML format for creating and managing different types of content such as blog posts, pages, and membership products.

**Example:** 
```xml
<blogpost>
  <title>Getting Started with MemberPress</title>
  <content>
    <p>This guide will help you...</p>
  </content>
</blogpost>
```

**Related terms:** Content Parser, XML Format

**See also:** [Unified XML Content System](../content-system/unified-xml-content-system.md)

## Contributing to the Glossary

To add or modify terms in this glossary:

1. Follow the established format (Term, Definition, Example, Related terms, See also)
2. Maintain alphabetical ordering of terms
3. Link to relevant documentation when available
4. Keep definitions clear and concise
5. Include examples where helpful
6. Submit your changes for review by the Documentation Team

## Revision History

- **2025-04-05**: Initial version created
- **2025-04-05**: Added terms related to Agent System and Tool System