# API Reference for MemberPress AI Assistant

This section provides reference documentation for the APIs provided by the MemberPress AI Assistant plugin.

## Contents

1. [JavaScript API](./javascript-api.md)
2. [PHP API](./php-api.md)
3. [REST API](./rest-api.md)
4. [Hooks and Filters](./hooks-and-filters.md)
5. [Authentication](./authentication.md)
6. [Rate Limiting](./rate-limiting.md)
7. [Error Handling](./error-handling.md)
8. [Examples](./examples.md)

## JavaScript API

The JavaScript API allows you to interact with the MemberPress AI Assistant from your own JavaScript code. It provides methods for sending messages to the AI assistant, receiving responses, and handling tool calls.

### Key Components

- **MPAI_Chat**: The main interface for interacting with the AI assistant
- **MPAI_Tools**: Handles tool call detection, execution, and formatting
- **MPAI_Formatters**: Formats responses from the AI assistant
- **MPAI_UIUtils**: Provides utility functions for the chat interface

[Learn more about the JavaScript API](./javascript-api.md)

## PHP API

The PHP API allows you to interact with the MemberPress AI Assistant from your own PHP code. It provides classes and methods for sending messages to the AI assistant, receiving responses, and handling tool calls.

### Key Components

- **MPAI_Chat**: The main interface for interacting with the AI assistant
- **MPAI_Agent_Orchestrator**: Routes requests to specialized agents
- **MPAI_Tool_Registry**: Manages the registration and retrieval of tools
- **MPAI_Context_Manager**: Manages the context of the conversation

[Learn more about the PHP API](./php-api.md)

## REST API

The REST API allows you to interact with the MemberPress AI Assistant from external applications. It provides endpoints for sending messages to the AI assistant, receiving responses, and handling tool calls.

### Key Endpoints

- **/wp-json/mpai/v1/chat**: Send a message to the AI assistant
- **/wp-json/mpai/v1/tools**: Execute a tool
- **/wp-json/mpai/v1/context**: Manage the conversation context

[Learn more about the REST API](./rest-api.md)

## Hooks and Filters

The plugin provides a number of hooks and filters that allow you to customize its behavior.

### Key Hooks

- **mpai_before_message_processing**: Fired before a message is processed
- **mpai_after_message_processing**: Fired after a message is processed
- **mpai_before_tool_execution**: Fired before a tool is executed
- **mpai_after_tool_execution**: Fired after a tool is executed

### Key Filters

- **mpai_message_context**: Filter the context provided to the AI assistant
- **mpai_tool_response**: Filter the response from a tool
- **mpai_agent_response**: Filter the response from an agent
- **mpai_chat_response**: Filter the final response from the AI assistant

[Learn more about Hooks and Filters](./hooks-and-filters.md)

## Authentication

The API uses WordPress authentication mechanisms to ensure that only authorized users can access the API.

[Learn more about Authentication](./authentication.md)

## Rate Limiting

The API includes rate limiting to prevent abuse.

[Learn more about Rate Limiting](./rate-limiting.md)

## Error Handling

The API provides detailed error messages to help you diagnose and fix issues.

[Learn more about Error Handling](./error-handling.md)

## Examples

The API documentation includes examples of how to use the API in various scenarios.

[Learn more about Examples](./examples.md)