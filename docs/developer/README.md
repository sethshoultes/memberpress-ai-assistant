# Developer Guide for MemberPress AI Assistant

This section provides technical documentation for developers who want to extend or customize the MemberPress AI Assistant plugin.

## Contents

1. [Architecture Overview](./architecture-overview.md)
2. [Tool System](./tool-system.md)
3. [Agent System](./agent-system.md)
4. [Hooks and Filters](./hooks-and-filters.md)
5. [WP-CLI Tool Implementation](./wpcli-tool-implementation.md)
6. [Consent Hooks Reference](./consent-hooks-reference.md)
7. [Custom Tool Development](./custom-tool-development.md)
8. [Custom Agent Development](./custom-agent-development.md)
9. [JavaScript API](./javascript-api.md)
10. [PHP API](./php-api.md)

## Getting Started with Development

### Development Environment Setup

1. Clone the repository
2. Install dependencies
3. Configure your development environment

```bash
# Clone the repository
git clone https://github.com/yourusername/memberpress-ai-assistant.git

# Install dependencies
cd memberpress-ai-assistant
composer install
npm install

# Build assets
npm run build
```

### Key Concepts

#### Tool System

The Tool System is the mechanism that allows the AI assistant to perform actions on the WordPress site. Tools are registered with the Tool Registry and can be executed by the AI assistant through the Agent System.

[Learn more about the Tool System](./tool-system.md)

#### Agent System

The Agent System provides specialized AI capabilities for different types of tasks. Agents are registered with the Agent Orchestrator, which routes requests to the appropriate agent based on the user's intent.

[Learn more about the Agent System](./agent-system.md)

#### Hooks and Filters

The plugin provides a number of hooks and filters that allow developers to customize its behavior.

[Learn more about Hooks and Filters](./hooks-and-filters.md)

### Extending the Plugin

#### Creating Custom Tools

You can create custom tools to extend the capabilities of the AI assistant. Custom tools must extend the `MPAI_Base_Tool` class and implement the required methods.

[Learn more about Custom Tool Development](./custom-tool-development.md)

#### Creating Custom Agents

You can create custom agents to provide specialized AI capabilities for specific tasks. Custom agents must extend the `MPAI_Base_Agent` class and implement the required methods.

[Learn more about Custom Agent Development](./custom-agent-development.md)

## API Reference

### JavaScript API

The plugin provides a JavaScript API that allows you to interact with the AI assistant from your own JavaScript code.

[Learn more about the JavaScript API](./javascript-api.md)

### PHP API

The plugin provides a PHP API that allows you to interact with the AI assistant from your own PHP code.

[Learn more about the PHP API](./php-api.md)

## Best Practices

- Follow the WordPress coding standards
- Use the provided hooks and filters instead of modifying the plugin's code directly
- Test your changes thoroughly before deploying to production
- Keep your custom code in a separate plugin to avoid conflicts with updates

## Contributing

If you'd like to contribute to the plugin, please see the [Contributing Guide](../CONTRIBUTING.md).