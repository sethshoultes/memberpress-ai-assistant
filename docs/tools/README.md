# Tools System Documentation

The Tools System is a core component of the MemberPress AI Assistant plugin that enables the AI to perform actions on your WordPress site. This documentation provides a comprehensive overview of the tools system, its architecture, and how to use and extend it.

## Contents

1. [Tools System Overview](./overview.md)
2. [Tool Registry](./tool-registry.md)
3. [Base Tool Class](./base-tool.md)
4. [Tool Call Detection](./tool-call-detection.md)
5. [Available Tools](./available-tools.md)
6. [Creating Custom Tools](./custom-tools.md)
7. [Tool Security](./tool-security.md)
8. [Troubleshooting](./troubleshooting.md)

## Tools System Overview

The Tools System allows the AI assistant to perform actions on your WordPress site, such as executing WP-CLI commands, accessing the WordPress API, and retrieving plugin logs. It consists of several components:

- **Tool Registry**: Manages the registration and retrieval of tools
- **Base Tool Class**: Provides a common interface for all tools
- **Tool Implementations**: Specific tools that perform actions
- **Tool Call Detection**: Detects and processes tool calls in AI responses

## Available Tools

The MemberPress AI Assistant comes with several built-in tools:

### WP-CLI Tool

The WP-CLI tool allows the AI assistant to execute WordPress CLI commands. It is implemented in the `MPAI_WP_CLI_Tool` class and is registered with the tool registry as 'wpcli'.

[Learn more about the WP-CLI Tool](./wpcli-tool.md)

### WordPress API Tool

The WordPress API tool provides access to WordPress API functions. It is implemented in the `MPAI_WP_API_Tool` class and is registered with the tool registry as 'wp_api'.

[Learn more about the WordPress API Tool](./wp-api-tool.md)

### Plugin Logs Tool

The Plugin Logs tool allows the AI assistant to retrieve and analyze logs of plugin installations, activations, deactivations, and deletions. It is implemented in the `MPAI_Plugin_Logs_Tool` class and is registered with the tool registry as 'plugin_logs'.

[Learn more about the Plugin Logs Tool](./plugin-logs-tool.md)

## Tool Call Detection

The Tool Call Detection system is responsible for detecting and processing tool calls in AI responses. It uses a combination of regex patterns and JSON parsing to identify tool calls and execute the corresponding tools.

[Learn more about Tool Call Detection](./tool-call-detection.md)

## Creating Custom Tools

You can create custom tools to extend the capabilities of the AI assistant. Custom tools must extend the `MPAI_Base_Tool` class and implement the required methods.

[Learn more about Creating Custom Tools](./custom-tools.md)

## Tool Security

The Tools System includes several security measures to prevent misuse:

- Command validation to prevent execution of dangerous commands
- Permission checks to ensure only authorized users can execute tools
- Rate limiting to prevent abuse
- Logging of all tool executions

[Learn more about Tool Security](./tool-security.md)

## Troubleshooting

If you encounter issues with the Tools System, check the [Troubleshooting](./troubleshooting.md) guide for common problems and their solutions.