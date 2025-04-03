# MemberPress AI Assistant Developer Guide

**Welcome to the MemberPress AI Assistant codebase!** 

This document serves as your comprehensive entry point and roadmap to quickly understand the system architecture and find the right resources for any development task.

## Table of Contents

1. [System Overview](#system-overview)
2. [Core Systems Map](#core-systems-map)
3. [Feature Development Pathways](#feature-development-pathways)
   - [AI Chat Features](#ai-chat-features)
   - [WordPress Admin Features](#wordpress-admin-features)
   - [MemberPress Integration](#memberpress-integration)
   - [Agent System Features](#agent-system-features)
   - [Performance Optimizations](#performance-optimizations)
   - [Security Enhancements](#security-enhancements)
4. [Common Development Tasks](#common-development-tasks)
5. [Development Tools and Workflows](#development-tools-and-workflows)
6. [Testing and Quality Assurance](#testing-and-quality-assurance)
7. [Documentation Standards](#documentation-standards)
8. [Getting Help](#getting-help)

## System Overview

MemberPress AI Assistant is a WordPress plugin that integrates AI capabilities into the MemberPress membership platform. The system combines:

1. **AI Chat Interface**: A frontend and admin chat interface for interacting with AI assistants
2. **Tool System**: Extensible tools that give the AI capabilities to interact with WordPress and MemberPress
3. **Agent System**: Specialized AI agents for different domains and tasks
4. **API Integration**: Connections to AI providers (OpenAI and Anthropic)
5. **MemberPress Integration**: Specialized functions for membership management

The plugin follows WordPress coding standards and leverages modern JavaScript for the frontend. It uses a modular architecture that allows easy extension of its capabilities.

## Core Systems Map

Understanding how the core systems interact is essential for development:

```
┌─────────────────────────────────────────────────────────────────┐
│                        WordPress Core                           │
└─────────────────────────────────────────────────────────────────┘
                                  ▲
                                  │
┌─────────────────────────────────────────────────────────────────┐
│                          MemberPress                            │
└─────────────────────────────────────────────────────────────────┘
                                  ▲
                                  │
┌─────────────────────────────────────────────────────────────────┐
│                   MemberPress AI Assistant                      │
│                                                                 │
│  ┌────────────────┐   ┌────────────────┐   ┌────────────────┐   │
│  │                │   │                │   │                │   │
│  │  Chat System   │◄─►│   Tool System  │◄─►│  Agent System  │   │
│  │                │   │                │   │                │   │
│  └────────────────┘   └────────────────┘   └────────────────┘   │
│          ▲                    ▲                    ▲            │
│          │                    │                    │            │
│  ┌────────────────┐   ┌────────────────┐   ┌────────────────┐   │
│  │                │   │                │   │                │   │
│  │  API Router    │   │Context Manager │   │ Admin Screens  │   │
│  │                │   │                │   │                │   │
│  └────────────────┘   └────────────────┘   └────────────────┘   │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### Key Components:

- **Main Plugin File**: `memberpress-ai-assistant.php` - Entry point and initialization
- **Chat System**: `/includes/class-mpai-chat.php` - Core chat processing
- **Tool System**: `/includes/tools/*` - Extensible tools for AI capabilities
- **Agent System**: `/includes/agents/*` - Specialized agents for different domains
- **API Router**: `/includes/class-mpai-api-router.php` - Manages AI provider communication
- **Context Manager**: `/includes/class-mpai-context-manager.php` - Manages tool execution
- **Admin Screens**: `/includes/admin-page.php`, `/includes/settings-page.php` - WordPress admin UI

Comprehensive documentation of all files is available in [/docs/current/system-map.md](/docs/current/system-map.md).

## Feature Development Pathways

Depending on the type of feature you're developing, you'll need to focus on different parts of the system.

### AI Chat Features

If you're working on AI chat capabilities (new tools, improved responses, etc.):

1. **Start here**: [/docs/current/tool-implementation-map.md](/docs/current/tool-implementation-map.md) and [_1_AGENTIC_SYSTEMS_.md](./_1_AGENTIC_SYSTEMS_.md#tool-system-integration)
2. **Key files**:
   - `/includes/tools/class-mpai-base-tool.php`
   - `/includes/tools/class-mpai-tool-registry.php`
   - `/includes/class-mpai-context-manager.php`
   - `/includes/class-mpai-chat.php`
3. **Frontend files**:
   - `/assets/js/modules/mpai-chat-tools.js`
   - `/assets/js/modules/mpai-chat-messages.js`
4. **Example implementation**: XML Blog Post Tool
   - `/includes/class-mpai-xml-content-parser.php`
   - `/assets/js/modules/mpai-blog-formatter.js`
5. **Testing**:
   - Create a specific test script in `/test/`
   - Follow procedures in `/test/test-procedures.md`

**Development workflow**:
1. Create tool class implementation
2. Register with Tool Registry
3. Update Context Manager
4. Add to system prompt (in `MPAI_Chat::get_system_prompt()`)
5. Add client-side integration if needed
6. Create tests

### WordPress Admin Features

If you're working on admin UI, settings, or WordPress integrations:

1. **Start here**: [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
2. **Key files**:
   - `/includes/class-mpai-admin.php`
   - `/includes/settings-page.php`
   - `/includes/admin-page.php`
   - `/assets/css/admin.css`
   - `/assets/js/admin.js`
3. **Settings system**:
   - `/includes/class-mpai-settings.php`
4. **Testing**:
   - Create test procedures for your admin features
   - Test across different WordPress roles and permissions

**Development workflow**:
1. Understand existing admin pages and structure
2. Add new settings to MPAI_Settings class if needed
3. Create or modify admin UI components
4. Add JavaScript for interactivity
5. Style with CSS
6. Add capability checks and nonce verification for security

### MemberPress Integration

If you're working on MemberPress-specific integrations:

1. **Start here**: [MemberPress Documentation](https://docs.memberpress.com/)
2. **Key files**:
   - `/includes/class-mpai-memberpress-api.php`
   - `/includes/agents/specialized/class-mpai-memberpress-agent.php`
3. **Testing**:
   - Use `/test/memberpress-test.php` as a reference
   - Test with different membership configurations

**Development workflow**:
1. Understand MemberPress data structures
2. Add methods to MPAI_MemberPress_API for data access
3. Update the MemberPress agent if needed
4. Create tools that leverage MemberPress functionality
5. Test with various membership scenarios

### Agent System Features

If you're working on the agent system or adding specialized agents:

1. **Start here**: [_1_AGENTIC_SYSTEMS_.md](./_1_AGENTIC_SYSTEMS_.md)
2. **Key files**:
   - `/includes/agents/interfaces/interface-mpai-agent.php`
   - `/includes/agents/class-mpai-base-agent.php`
   - `/includes/agents/class-mpai-agent-orchestrator.php`
3. **Example implementations**:
   - `/includes/agents/specialized/class-mpai-memberpress-agent.php`
   - `/includes/agents/specialized/class-mpai-command-validation-agent.php`
4. **Testing**:
   - Use `/test/test-agent-system.php`

**Development workflow**:
1. Understand the agent interface and base implementation
2. Create a specialized agent class
3. Implement required methods for your specific domain
4. Register the agent with the orchestrator
5. Update system prompts to utilize the agent capabilities
6. Create tests for your agent

### Performance Optimizations

If you're working on performance improvements:

1. **Start here**: [WordPress Performance](https://developer.wordpress.org/plugins/performance/)
2. **Areas to focus**:
   - JavaScript optimization in `/assets/js/`
   - Database query optimization in API classes
   - Caching strategies for expensive operations
   - API call optimization to reduce tokens and latency
3. **Logging**:
   - Use the logging system in `/assets/js/mpai-logger.js`
   - PHP logging with `error_log('MPAI: message')`

**Development workflow**:
1. Establish performance benchmarks before changes
2. Identify bottlenecks using profiling
3. Implement optimizations
4. Measure impact
5. Document performance improvements

### Security Enhancements

If you're working on security improvements:

1. **Start here**: [WordPress Security](https://developer.wordpress.org/plugins/security/)
2. **Key files**:
   - `/includes/commands/class-mpai-command-security.php`
   - `/includes/agents/specialized/class-mpai-command-validation-agent.php`
   - `/includes/direct-ajax-handler.php`
3. **Focus areas**:
   - Input validation and sanitization
   - Capability checks and permissions
   - Protection against potential misuse of AI capabilities
   - Secure AJAX handling

**Development workflow**:
1. Identify security risks using the roadmap docs
2. Implement enhanced validation and sanitization
3. Add capability checks where appropriate
4. Update security documentation
5. Test with penetration testing strategies

## Common Development Tasks

### Adding a New Tool

Follow the detailed documentation in [/docs/current/tool-implementation-map.md](/docs/current/tool-implementation-map.md) and [_1_AGENTIC_SYSTEMS_.md](./_1_AGENTIC_SYSTEMS_.md#tool-system-integration).

### Adding a New Agent

1. Create a new class in `/includes/agents/specialized/` extending `MPAI_Base_Agent`
2. Implement the required interface methods
3. Register your agent in the `MPAI_Agent_Orchestrator`
4. Update system prompts to utilize the new agent capabilities

### Modifying Admin Settings

1. Add setting definitions to `/includes/class-mpai-settings.php`
2. Update settings page UI in `/includes/settings-page.php`
3. Add validation for your settings
4. Use settings values in your code via `get_option('mpai_setting_name')`

### Adding JavaScript Features

1. If adding to existing modules, locate relevant files in `/assets/js/modules/`
2. If creating a new module:
   - Create new file in `/assets/js/modules/`
   - Register in `/assets/js/modules/chat-interface-loader.js`
3. Use the logging system from `mpaiLogger` for debugging
4. Follow the module pattern used in existing files

### Adding CSS Styles

1. Add styles to `/assets/css/admin.css` for admin pages
2. Add styles to `/assets/css/chat-interface.css` for the chat interface
3. Follow the existing naming conventions (`mpai-` prefix)
4. Ensure responsive design principles

## Development Tools and Workflows

### Required Development Tools

- Git for version control
- Composer for PHP dependencies (when needed)
- Node.js and npm for JavaScript build tools
- WordPress development environment

### Build Commands

- JavaScript build: Details in `CLAUDE.md`
- CSS compilation: Details in `CLAUDE.md`

### Debugging

1. **PHP debugging**:
   - Enable `WP_DEBUG` in wp-config.php
   - Use `error_log('MPAI: message')` for logging
   - Access logs via your server error log

2. **JavaScript debugging**:
   - Enable debug mode in plugin settings
   - Use `window.mpaiLogger.debug()` for console logging
   - Use browser dev tools to inspect network requests and responses

## Testing and Quality Assurance

1. **Manual testing**:
   - Follow procedures in `/test/test-procedures.md`
   - Test with both OpenAI and Anthropic providers
   - Test in different WordPress environments

2. **Test scripts**:
   - Create test scripts in `/test/` directory
   - Follow existing patterns like `test-agent-system.php`

3. **Quality standards**:
   - Follow WordPress coding standards
   - Include comprehensive error handling
   - Document all methods and complex logic
   - Write clean, maintainable code

## Documentation Standards

1. **Code documentation**:
   - Use PHPDoc comments for all classes and methods
   - Document complex logic with inline comments
   - Follow WordPress documentation standards

2. **Feature documentation**:
   - Create or update documentation in `/docs/` directory
   - Follow the appropriate template from `/docs/templates/`
   - Update the system map if your changes affect architecture
   - Update the main README.md to reference your new documentation

3. **Changelog**:
   - Update `CHANGELOG.md` with your changes
   - Follow the established format (Added/Changed/Fixed/Removed)

## Getting Help

1. **Internal documentation**:
   - Primary entry points: 
     - [_0_START_HERE_.md](./_0_START_HERE_.md) - This guide
     - [_1_AGENTIC_SYSTEMS_.md](./_1_AGENTIC_SYSTEMS_.md) - Agent system guide
   - Main documentation index: `/docs/README.md`
   - System architecture: `/docs/current/system-map.md`
   - Tool implementation: `/docs/current/tool-implementation-map.md`

2. **External resources**:
   - WordPress Plugin Handbook: https://developer.wordpress.org/plugins/
   - MemberPress Documentation: https://docs.memberpress.com/
   - OpenAI API Documentation: https://platform.openai.com/docs/
   - Anthropic API Documentation: https://docs.anthropic.com/

3. **Development guidelines**:
   - Review `CLAUDE.md` for code style and project-specific guidelines
   - Follow the "Scooby Snack" protocol for documenting solutions

---

This guide aims to help you navigate the MemberPress AI Assistant codebase quickly and efficiently. For more detailed information, refer to the specific documentation linked throughout this guide.

Remember to always follow the established patterns and practices in the codebase, and to thoroughly test any changes before submitting them for review.

Happy coding!