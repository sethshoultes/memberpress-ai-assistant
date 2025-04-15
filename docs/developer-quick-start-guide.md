# MemberPress AI Assistant Developer Quick Start Guide

**Version:** 1.0.0  
**Last Updated:** 2025-04-14  
**Status:** ✅ Current

> This guide provides a comprehensive overview for developers working with the MemberPress AI Assistant plugin. For the latest updates, always check the project repository.

## Table of Contents

1. [Introduction](#introduction)
2. [System Architecture Overview](#system-architecture-overview)
3. [Core Components](#core-components)
   - [Entry Points](#entry-points)
   - [Key Classes](#key-classes)
   - [Directory Structure](#directory-structure)
   - [Initialization Sequence](#initialization-sequence)
   - [File Naming Patterns](#file-naming-patterns)
4. [Development Workflows](#development-workflows)
   - [Setting Up Your Environment](#setting-up-your-environment)
     - [System Requirements](#system-requirements)
     - [Installation](#installation)
   - [Adding New Features](#adding-new-features)
   - [Debugging and Testing](#debugging-and-testing)
5. [Agent System](#agent-system)
   - [Agent Orchestrator](#agent-orchestrator)
   - [Specialized Agents](#specialized-agents)
   - [Agent Scoring and Routing](#agent-scoring-and-routing)
6. [Tool System](#tool-system)
   - [Tool Registry](#tool-registry)
   - [Creating New Tools](#creating-new-tools)
   - [Tool Call Detection and Execution](#tool-call-detection-and-execution)
7. [Command System](#command-system)
   - [WP-CLI Integration](#wp-cli-integration)
   - [Command Handler](#command-handler)
   - [Command Validation and Security](#command-validation-and-security)
8. [API Integration](#api-integration)
   - [OpenAI Integration](#openai-integration)
   - [Anthropic Integration](#anthropic-integration)
   - [API Router](#api-router)
9. [MemberPress Integration](#memberpress-integration)
   - [MemberPress API](#memberpress-api)
   - [Independent Operation Mode](#independent-operation-mode)
10. [JavaScript Frontend](#javascript-frontend)
    - [Chat Interface](#chat-interface)
    - [Tool Execution](#tool-execution)
    - [UI Utilities](#ui-utilities)
11. [Error Handling](#error-handling)
    - [Error Recovery System](#error-recovery-system)
    - [State Validation](#state-validation)
12. [Current Development Status](#current-development-status)
    - [Completed Features](#completed-features)
    - [In-Progress Features](#in-progress-features)
    - [Planned Features](#planned-features)
13. [Next Steps for Developers](#next-steps-for-developers)
    - [Priority Tasks](#priority-tasks)
    - [Feature Implementation Guidelines](#feature-implementation-guidelines)
14. [Codebase Gotchas and Technical Debt](#codebase-gotchas-and-technical-debt)
    - [Unused Files and Code](#unused-files-and-code)
    - [Duplicated Code and Functionality](#duplicated-code-and-functionality)
    - [Inconsistent Patterns](#inconsistent-patterns)
    - [Backward Compatibility Challenges](#backward-compatibility-challenges)
    - [When Modifying Code](#when-modifying-code)
15. [Testing and Debugging Tools](#testing-and-debugging-tools)
    - [Troubleshooting Common Issues](#troubleshooting-common-issues)
    - [Diagnostics Page](#diagnostics-page)
    - [Debug Logging](#debug-logging)
    - [Test Fixtures](#test-fixtures)
    - [Performance Profiling](#performance-profiling)
16. [Contribution Guidelines](#contribution-guidelines)
    - [Contribution Checklist](#contribution-checklist)
    - [Coding Standards](#coding-standards)
    - [Pull Request Process](#pull-request-process)
    - [Version Control](#version-control)
    - [Documentation Guidelines](#documentation-guidelines)
17. [Security Guidelines](#security-guidelines)
    - [Input Validation](#input-validation)
    - [Output Escaping](#output-escaping)
    - [API Security](#api-security)
    - [Command Execution Security](#command-execution-security)
    - [Code Execution and Evaluation](#code-execution-and-evaluation)
18. [Internationalization](#internationalization)
    - [Text Domains](#text-domains)
    - [Translation Functions](#translation-functions)
    - [Translation Files](#translation-files)
19. [Additional Resources](#additional-resources)
    - [Documentation References](#documentation-references)
    - [Code References](#code-references)
    - [Official WordPress Resources](#official-wordpress-resources)

## Introduction

The MemberPress AI Assistant is a WordPress plugin that integrates AI capabilities into the MemberPress membership platform. This guide provides a comprehensive overview of the system architecture, development workflows, and guidelines for extending and enhancing the plugin.

The plugin follows a modular architecture with several key systems:

1. **AI Chat Interface**: Frontend and admin chat interface for interacting with AI assistants
2. **Tool System**: Extensible tools that give the AI capabilities to interact with WordPress and MemberPress
3. **Agent System**: Specialized AI agents for different domains and tasks
4. **API Integration**: Connections to AI providers (OpenAI and Anthropic)
5. **MemberPress Integration**: Specialized functions for membership management

This quick start guide will help you understand how these systems work together and how to contribute effectively to the project.

## System Architecture Overview

The MemberPress AI Assistant follows a layered architecture:

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

### Data Flow

1. User sends a message through the chat interface
2. The message is processed by MPAI_Chat
3. The API Router sends the request to the appropriate AI provider (OpenAI or Anthropic)
4. If the AI response includes tool calls, they are detected and executed
5. Tool execution is managed by the Context Manager
6. For specialized tasks, the Agent Orchestrator routes to the appropriate agent
7. Results are returned to the user

## Core Components

### Entry Points

- **Main Plugin File**: `/memberpress-ai-assistant.php` - Entry point and initialization
- **Plugin Class**: `MemberPress_AI_Assistant` - Main plugin class in the entry file
- **Chat Class**: `/includes/class-mpai-chat.php` - Core chat processing
- **Context Manager**: `/includes/class-mpai-context-manager.php` - Manages tool execution
- **Admin Pages**: `/includes/admin-page.php` and `/includes/settings-page.php` - WordPress admin UI

### Key Classes

- **MPAI_Chat**: Central class for chat message processing
- **MPAI_Context_Manager**: Manages execution context and tool calls
- **MPAI_Agent_Orchestrator**: Routes tasks to specialized agents
- **MPAI_Tool_Registry**: Registers and provides access to tools
- **MPAI_API_Router**: Routes requests between AI providers
- **MPAI_Error_Recovery**: Handles errors and provides recovery strategies

### Directory Structure

- `/includes`: Core PHP classes
  - `/agents`: Agent system classes
    - `/interfaces`: Agent interfaces
    - `/specialized`: Specialized agent implementations
  - `/commands`: Command handling classes
  - `/tools`: Tool system classes
    - `/implementations`: Specific tool implementations
  - `/class-mpai-*.php`: Core system classes
- `/assets`: JavaScript, CSS, and images
  - `/js`: JavaScript files
    - `/modules`: Modular JavaScript components
  - `/css`: Stylesheets
  - `/images`: Images and icons
- `/docs`: Documentation files
  - `/current`: Current features documentation
  - `/roadmap`: Future plans and specifications
  - `/archive`: Archived documentation
- `/test`: Testing utilities and scripts

### Initialization Sequence

Understanding the plugin's initialization sequence is crucial for working with the codebase:

1. **Plugin Bootstrap** (`memberpress-ai-assistant.php`):
   - Defines constants (`MPAI_VERSION`, `MPAI_PLUGIN_DIR`, etc.)
   - Creates and initializes the main `MemberPress_AI_Assistant` class

2. **Core System Initialization** (`MemberPress_AI_Assistant::__construct`):
   - Loads dependencies via `load_dependencies()`
   - Initializes the admin menu system
   - Sets up WordPress actions and filters
   - Handles MemberPress detection

3. **Component Loading** (`MemberPress_AI_Assistant::load_dependencies`):
   - Error Recovery System first (for exception handling)
   - State Validation System (for state consistency)
   - API Integration classes (OpenAI, Anthropic)
   - Core functionality classes (Chat, Context Manager, etc.)
   - Agent System
   - Diagnostic tools and tests (in admin)
   - CLI Commands

4. **Agent System Initialization** (`MemberPress_AI_Assistant::load_agent_system`):
   - Loads interfaces first
   - Loads base tool and tool registry classes
   - Loads individual tool implementations
   - Loads base agent class
   - Loads specialized agent implementations
   - Loads the Agent Orchestrator

5. **Plugin Hooks**:
   - `plugins_loaded` (priority 15): Checks MemberPress availability
   - `init`: Initializes plugin components with `init_plugin_components()`
   - Various admin hooks for assets, UI, and AJAX handlers

Understanding this sequence helps in diagnosing issues and extending functionality at the right points in the initialization chain.

### File Naming Patterns

The codebase follows specific naming conventions:

1. **Class Files**:
   - Prefix: `class-mpai-`
   - Format: `class-mpai-[system]-[component].php`
   - Examples:
     - `class-mpai-chat.php`
     - `class-mpai-wpcli-tool.php`
     - `class-mpai-agent-orchestrator.php`

2. **Interface Files**:
   - Prefix: `interface-mpai-` 
   - Format: `interface-mpai-[name].php`
   - Example: `interface-mpai-agent.php`

3. **Tool Implementation Files**:
   - Location: `/includes/tools/implementations/`
   - Format: `class-mpai-[tool-name]-tool.php`
   - Example: `class-mpai-plugin-logs-tool.php`

4. **Agent Implementation Files**:
   - Location: `/includes/agents/specialized/`
   - Format: `class-mpai-[agent-name]-agent.php`
   - Example: `class-mpai-memberpress-agent.php`

5. **JavaScript Module Files**:
   - Location: `/assets/js/modules/`
   - Format: `mpai-[module-name].js`
   - Examples: 
     - `mpai-chat-tools.js`
     - `mpai-chat-messages.js`

6. **Archive Files**:
   - Files that have been deprecated but retained for reference
   - Usually in `/includes/archive/` or `/assets/archive/`
   - May have `.old` extension or be in an archive directory

Following these naming conventions is important when creating new files to maintain consistency with the existing codebase.

## Development Workflows

### Setting Up Your Environment

#### System Requirements

- PHP 7.4 or higher
- WordPress 5.6 or higher
- MemberPress 1.9.0+ (optional, but recommended for full functionality)
- Modern browser with ES6 support

#### Installation

1. **Clone the Repository**
   ```bash
   git clone https://github.com/memberpress/memberpress-ai-assistant.git
   ```

2. **Install in WordPress**
   - Copy or symlink the repository into your WordPress plugins directory
   - Activate the plugin in WordPress admin

3. **Set Up API Keys**
   - Navigate to MemberPress → AI Assistant → Settings
   - Add your OpenAI API key or Anthropic API key
   - For development, consider using environment variables instead of storing keys in the database:
     ```php
     // Add to wp-config.php
     define('MPAI_OPENAI_API_KEY', 'your-api-key');
     define('MPAI_ANTHROPIC_API_KEY', 'your-api-key');
     ```

4. **Local Development Environment**
   - For local development, a setup using Local by Flywheel or Docker is recommended
   - Sample Docker configuration is available in `/docker/docker-compose.yml`

### Adding New Features

The plugin follows a modular architecture, making it easy to extend with new features. The general workflow for adding new features is:

1. **Identify the Feature Type**
   - Tool: For extending AI capabilities (e.g., a new WordPress API integration)
   - Agent: For specialized domain handling (e.g., a new MemberPress integration)
   - UI: For admin or frontend interface enhancements

2. **Implement the Feature**
   - Create new classes in the appropriate directories
   - Follow existing patterns for consistency
   - Add appropriate hooks and filters

3. **Register Your Feature**
   - For tools: Register with the Tool Registry
   - For agents: Register with the Agent Orchestrator
   - For UI: Add appropriate WordPress actions/filters

4. **Update Documentation**
   - Document your feature in the `/docs/current/` directory
   - Update README.md if significant

### Debugging and Testing

1. **PHP Debugging**
   - Enable `WP_DEBUG` in wp-config.php
   - Use `error_log('MPAI: message')` for logging
   - Access logs via your server error log

2. **JavaScript Debugging**
   - Enable console logging in plugin settings
   - Use `window.mpaiLogger.debug()` for console logging
   - Use browser dev tools to inspect network requests and responses

3. **Testing**
   - Manual testing procedures are outlined in `/test/manual-tests.md`
   - Unit tests are available in `/test/unit/`
   - Integration tests follow the pattern `test/integration/system-name-test.php`
   - Run tests using the WordPress test framework:
     ```bash
     cd /path/to/wordpress/wp-content/plugins/memberpress-ai-assistant
     composer install
     ./vendor/bin/phpunit
     ```

## Agent System

The Agent System provides a way to create specialized AI agents for different domains and tasks. This architecture allows the plugin to route specific types of requests to the most appropriate handler.

### Agent Orchestrator

The Agent Orchestrator (`MPAI_Agent_Orchestrator`) is responsible for:
- Registering and maintaining agents
- Routing requests to the appropriate agent based on specialization scoring
- Handling agent initialization and cleanup

File: `/includes/agents/class-mpai-agent-orchestrator.php`

### Specialized Agents

Specialized agents extend the `MPAI_Base_Agent` class and implement the `MPAI_Agent` interface. Each agent is responsible for a specific domain:

- **MPAI_MemberPress_Agent**: Handles MemberPress-specific operations
- **MPAI_Command_Validation_Agent**: Validates and secures command execution
- **MPAI_Content_Generator_Agent**: Specializes in content generation

To create a new agent:

1. Create a class in `/includes/agents/specialized/` extending `MPAI_Base_Agent`
2. Implement the required interface methods
3. Register your agent in the `MPAI_Agent_Orchestrator`

### Agent Scoring and Routing

Agents are selected based on a confidence scoring system that considers:
- Keywords in the user query
- Agent capabilities and specializations
- Context of the conversation

Implement the `calculate_confidence` method in your agent to provide custom scoring logic.

## Tool System

The Tool System provides a framework for adding capabilities to the AI assistant. Tools are registered with the Tool Registry and executed by the Context Manager.

### Tool Registry

The Tool Registry (`MPAI_Tool_Registry`) manages all available tools:
- Registering tools with unique identifiers
- Providing access to tools based on their IDs
- Lazy-loading tools for performance

File: `/includes/tools/class-mpai-tool-registry.php`

### Creating New Tools

To create a new tool:

1. Create a class in `/includes/tools/implementations/` extending `MPAI_Base_Tool`
2. Implement the required methods:
   - `get_parameters()`: Define tool parameters
   - `get_required_parameters()`: Specify required parameters
   - `execute_tool()`: Implement the tool's functionality
3. Register your tool with the Tool Registry

Example:
```php
class MPAI_Custom_Tool extends MPAI_Base_Tool {
    public function __construct() {
        $this->name = 'Custom Tool';
        $this->description = 'Performs custom operations';
    }
    
    public function get_parameters() {
        return [
            'param1' => [
                'type' => 'string',
                'description' => 'Description of parameter',
                'required' => true
            ]
        ];
    }
    
    public function get_required_parameters() {
        return ['param1'];
    }
    
    protected function execute_tool($parameters) {
        // Implement tool functionality
        return 'Result';
    }
}
```

### Tool Call Detection and Execution

Tool calls are detected in AI responses and executed by the Context Manager. The process involves:

1. Tool call detection in JavaScript (`mpai-chat-tools.js`)
2. AJAX execution request to the server
3. Tool validation and execution by the Context Manager
4. Result formatting and display in the chat interface

## Command System

The Command System provides a way to execute WordPress CLI commands and other system operations. It includes security validation, command detection, and execution components.

### WP-CLI Integration

The WP-CLI integration allows executing WordPress CLI commands through the AI assistant:

- **MPAI_WP_CLI_Executor**: Core command execution logic
- **MPAI_WP_CLI_Tool**: Tool wrapper for command execution

Files:
- `/includes/commands/class-mpai-wp-cli-executor.php`
- `/includes/tools/implementations/class-mpai-wpcli-tool.php`

### Command Handler

The Command Handler (`MPAI_Command_Handler`) centralizes command processing:

- Command sanitization and validation
- Command routing to appropriate executors
- Error handling and recovery

File: `/includes/commands/class-mpai-command-handler.php`

### Command Validation and Security

Command security is implemented through:

- **MPAI_Command_Security**: Validates commands against security rules
- **MPAI_Command_Sanitizer**: Sanitizes command inputs
- **MPAI_Command_Validation_Agent**: Specialized agent for command validation

Security follows a permissive blacklist approach that blocks dangerous patterns while allowing all other commands.

## API Integration

The plugin supports multiple AI providers through a flexible API integration system.

### OpenAI Integration

OpenAI integration provides access to GPT models:

- **MPAI_OpenAI**: API client for OpenAI
- Support for function calling
- Model and parameter configuration

File: `/includes/class-mpai-openai.php`

### Anthropic Integration

Anthropic integration provides access to Claude models:

- **MPAI_Anthropic**: API client for Anthropic
- Support for tool use
- Model and parameter configuration

File: `/includes/class-mpai-anthropic.php`

### API Router

The API Router (`MPAI_API_Router`) manages communication between the plugin and AI providers:

- Routing requests to the primary provider
- Fallback to secondary provider if primary fails
- Adapting between different API formats

File: `/includes/class-mpai-api-router.php`

## MemberPress Integration

The plugin integrates deeply with MemberPress to provide AI-powered membership management capabilities.

### MemberPress API

The MemberPress API class (`MPAI_MemberPress_API`) provides access to MemberPress data:

- Memberships and subscription data
- Member information and activity
- Transaction and financial data

File: `/includes/class-mpai-memberpress-api.php`

### Independent Operation Mode

The plugin can also operate without MemberPress being installed:

- Smart menu placement based on MemberPress availability
- Graceful degradation of MemberPress-specific features
- Clear indications of MemberPress-dependent functionality

## JavaScript Frontend

The plugin's frontend is built with modular JavaScript components.

### Chat Interface

The chat interface provides a user-friendly way to interact with the AI assistant:

- Message display and formatting
- Tool call execution
- Special formatting for tabular data

Files:
- `/assets/js/modules/mpai-chat-interface.js`
- `/assets/js/modules/mpai-chat-messages.js`

### Tool Execution

Tool execution in the frontend is handled by dedicated modules:

- **mpai-chat-tools.js**: Tool call detection and execution
- **mpai-chat-formatters.js**: Result formatting
- **mpai-blog-formatter.js**: Blog post formatting

File: `/assets/js/modules/mpai-chat-tools.js`

### UI Utilities

UI utilities provide common UI functionality:

- **mpai-chat-ui-utils.js**: Common UI operations
- **mpai-logger.js**: Console logging utilities

File: `/assets/js/modules/mpai-chat-ui-utils.js`

## Error Handling

The plugin includes robust error handling and recovery mechanisms.

### Error Recovery System

The Error Recovery System provides structured error handling:

- Standardized error types and severity levels
- Retry and fallback mechanisms
- Circuit breaker pattern for service protection

File: `/includes/class-mpai-error-recovery.php`

### State Validation

The State Validation System ensures system consistency:

- Component state monitoring
- Validation rules for system components
- Integration with Error Recovery System

File: `/includes/class-mpai-state-validator.php`

## Current Development Status

### Completed Features

✅ **Dual API Support**
- Integration with OpenAI and Anthropic
- API Router for provider switching
- Fallback mechanisms

✅ **Agent System**
- Agent Orchestrator
- Specialized agents
- Confidence scoring

✅ **Tool System**
- Tool Registry
- WP-CLI integration
- WordPress API integration

✅ **Error Handling**
- Error Recovery System
- State Validation System
- Input Sanitization

✅ **MemberPress Integration**
- MemberPress API
- Independent operation mode
- Membership data access

### In-Progress Features

🚧 **Admin UI Overhaul**
- Improved settings page
- Enhanced diagnostics page
- Better visual organization

🚧 **Documentation Improvement**
- System documentation
- Developer guides
- User documentation

🚧 **Performance Optimization**
- System Information Caching
- Response caching
- Lazy loading

### Planned Features

🔮 **Agentic Security Framework**
- Enhanced security validation
- Permission-based access control
- Audit logging

🔮 **Content Tools Enhancement**
- Advanced content generation
- Better formatting options
- Content templates

🔮 **WordPress Security Integration**
- Integration with WordPress security APIs
- Enhanced capability checks
- Site health integration

## Next Steps for Developers

### Priority Tasks

1. **Complete Admin UI Overhaul**
   - Implement tab-based settings page with WordPress Settings API
   - Enhance diagnostics page with more comprehensive tests
   - See `/docs/roadmap/admin-ui-overhaul.md`

2. **Enhance Documentation System**
   - Complete developer guides for all subsystems
   - Add more code examples and tutorials
   - Update visual diagrams

3. **Implement Hooks and Filters System**
   - Create standardized hooks for extending functionality
   - Add filters for modifying AI behavior
   - Document all hooks and filters
   - See `/docs/roadmap/hooks-filters.md`

### Feature Implementation Guidelines

When implementing new features, follow these guidelines:

1. **Review Existing Code**
   - Understand current implementation patterns
   - Follow established coding conventions
   - Look for similar features as examples

2. **Follow Documentation Guidelines**
   - Use templates from `/docs/templates/`
   - Update system documentation
   - Add implementation notes

3. **Implement Testing**
   - Add test cases for your feature
   - Document testing procedures
   - Verify with both AI providers

4. **Document Your Work**
   - Add inline code documentation
   - Update feature documentation
   - Consider adding to the changelog

## Codebase Gotchas and Technical Debt

This section provides important information about aspects of the codebase that might cause confusion or require attention during development.

### Unused Files and Code

The codebase contains several unused or deprecated files that are still present:

1. **Registered Tools with Missing Implementations**:
   - Tools referenced in the Tool Registry that don't exist:
     - `class-mpai-content-generator-tool.php`
     - `class-mpai-analytics-tool.php`

2. **Archive Directories**:
   - `/includes/archive/`: Contains older versions of core components
   - `/assets/archive/`: Contains deprecated JavaScript and CSS
   - `/docs/archive/`: Contains outdated documentation

3. **Specific Deprecated Files**:
   - `/includes/archive/class-mpai-diagnostics-page.php` - Older diagnostics implementation
   - `/includes/archive/class-mpai-settings-manager.php` - Older settings system
   - `/includes/archive/class-mpai-wpcli-tool.php.old` - Previous WP-CLI implementation

### Duplicated Code and Functionality

Several areas of the codebase contain duplicated functionality:

1. **Settings Systems**:
   - Multiple settings page implementations exist:
     - `/includes/archive/settings-page-new.php`
     - `/includes/archive/settings-page-simple.php` 
     - `/includes/archive/settings-page-v2.php`
     - Current implementation in `/includes/settings-page.php`

2. **Diagnostic Systems**:
   - Duplicated diagnostic implementations:
     - `/includes/archive/class-mpai-diagnostics-page.php`
     - Current implementation in `/includes/class-mpai-diagnostics-page.php`

3. **MemberPress Detection Logic**:
   - Duplicated in multiple locations:
     - Main plugin file (`memberpress-ai-assistant.php`)
     - Diagnostic classes
     - Agent system

4. **Logger Implementations**:
   - Multiple logging mechanisms:
     - JavaScript console logging
     - PHP error logging 
     - Custom logger classes

### Inconsistent Patterns

The codebase exhibits some inconsistent coding patterns:

1. **Mixed Programming Paradigms**:
   - Some components use OOP (classes)
   - Others use procedural code and global functions (e.g., `mpai_init_plugin_logger()`)

2. **Error Handling Approaches**:
   - Modern code uses the Error Recovery System
   - Older code uses traditional try/catch blocks
   - Some sections use WordPress error functions

3. **Initialization Sequences**:
   - Inconsistent initialization order in different parts of the system
   - Mix of singleton patterns and direct instantiation

### Backward Compatibility Challenges

The codebase includes several compatibility layers:

1. **Tool Naming Conventions**:
   - Dual registration of tools under multiple names (e.g., `wp_cli` and `wpcli`)
   - Special case handling for different name formats

2. **API Response Formats**:
   - Support for multiple response structures
   - Conversion between different JSON formats

### When Modifying Code

When working with these areas of technical debt:

1. **Prefer Current Implementations**:
   - Use the non-archived versions of files
   - When in doubt, check initialization sequences in the main plugin file

2. **Maintain Dual Registrations**:
   - When modifying tool systems, maintain backward compatibility
   - Keep tool registration under both naming conventions

3. **Consider Cleanup Opportunities**:
   - Look for opportunities to consolidate duplicated code
   - Document any technical debt you address

4. **Follow Established Patterns**:
   - For newer systems, follow the patterns in current implementations
   - For older systems, maintain consistency with existing code

## Testing and Debugging Tools

The plugin provides several tools to assist with testing and debugging:

### Troubleshooting Common Issues

Common development issues and their solutions:

1. **API Connection Failures**
   - Check API keys in settings
   - Verify WordPress can make outbound HTTP requests
   - Check for SSL certificate issues
   - Solution: Use the diagnostics page to test API connectivity

2. **Tool Execution Errors**
   - Ensure tool implementations follow the correct interface
   - Check tool parameter validation
   - Verify proper error handling
   - Solution: Test tools individually through the debug console

3. **JavaScript Module Loading Issues**
   - Check browser console for script errors
   - Verify all dependencies are properly enqueued
   - Check for conflicts with other plugins
   - Solution: Use the browser network inspector to check asset loading

### Diagnostics Page

The diagnostics page provides system tests and debug information:

- API connectivity testing
- WordPress environment information
- MemberPress detection and status
- Plugin dependency checks

Access: MemberPress → AI Assistant → Diagnostics

File: `/includes/class-mpai-diagnostics-page.php`

### Debug Logging

Logging is available in several forms:

1. **PHP Logging**:
   - Enable `WP_DEBUG` and `WP_DEBUG_LOG` in wp-config.php
   - Use `error_log('MPAI: your message')` for standard logging
   - The plugin includes two logging levels:
     - `mpai_log_debug('message')`: Detailed debugging
     - `mpai_log_error('message')`: Error logging

2. **JavaScript Logging**:
   - Enable JavaScript debug mode in settings
   - Use the global logging object:
     ```javascript
     window.mpaiLogger.debug('Debug message');
     window.mpaiLogger.error('Error message');
     window.mpaiLogger.warn('Warning message');
     ```

3. **API Response Logging**:
   - Enable API logging in settings
   - All API requests and responses are logged to the browser console
   - Full JSON structures are available for inspection

### Test Fixtures

Test fixtures are available for simulating different conditions:

- `/test/fixtures/api-responses/`: Sample API responses
- `/test/fixtures/tool-calls/`: Sample tool calls
- `/test/fixtures/conversations/`: Sample conversation flows

Use these fixtures to test new tools and agents without making API calls.

### Performance Profiling

Several performance monitoring tools are available:

1. **Backend Profiler**:
   - Enable profiling in the settings
   - Function timing data is logged to the error log
   - Long operations are automatically flagged

2. **Frontend Profiler**:
   - Use `window.mpaiPerformance.start('operation-name')` to start timing
   - Use `window.mpaiPerformance.end('operation-name')` to end timing
   - Timing data is available in console when debug mode is enabled

## Contribution Guidelines

When contributing to the MemberPress AI Assistant plugin, follow these best practices to ensure code quality and maintainability.

### Contribution Checklist

Before submitting your contributions, verify that you have completed these steps:

✅ Written or updated tests for your changes
✅ Documented any new functions, classes, or hooks
✅ Ensured compatibility with both OpenAI and Anthropic APIs
✅ Verified that your code works in WordPress 5.6+ environments
✅ Checked that your code follows the established naming conventions
✅ Run PHP linting and JavaScript linting tools
✅ Updated relevant documentation files
✅ Added your changes to the CHANGELOG.md file

### Coding Standards

The plugin follows WordPress coding standards with some additional guidelines:

1. **PHP Code**:
   - Follow [WordPress PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
   - Use PHP 7.4+ compatible code
   - Use type hints where appropriate for function parameters and return types
   - Use docblocks for all classes, methods, and functions

2. **JavaScript Code**:
   - Follow [WordPress JavaScript Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/javascript/)
   - Use modern ES6+ features but ensure compatibility with supported browsers
   - Maintain modular architecture in the `/assets/js/modules/` directory
   - Use JSDoc comments for all functions and classes

3. **CSS Code**:
   - Follow [WordPress CSS Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/css/)
   - Use prefixed class names (`mpai-*`) to avoid conflicts
   - Maintain responsive design principles
   - Organize styles by component

### Pull Request Process

1. **Branch Naming**:
   - Use feature branches named `feature/descriptive-name`
   - Use bugfix branches named `fix/issue-description`
   - Include ticket numbers when applicable

2. **Commit Messages**:
   - Use clear, descriptive commit messages
   - Start with a verb (Add, Fix, Update, Refactor, etc.)
   - Reference ticket numbers when applicable
   - Keep commits focused on single logical changes

3. **Before Submitting**:
   - Run PHP linting (`php -l your-file.php`)
   - Test on different WordPress versions
   - Verify all new features work with both OpenAI and Anthropic
   - Update documentation as needed

4. **Code Review**:
   - All code must be reviewed by at least one other developer
   - Address all review comments before merging
   - Include testing steps in your PR description

### Version Control

1. **Versioning**:
   - The plugin follows [Semantic Versioning](https://semver.org/)
   - Major.Minor.Patch format (e.g., 1.6.1)
   - Increment appropriately based on changes

2. **Changelog**:
   - Update the CHANGELOG.md file with all significant changes
   - Group changes under "Added", "Changed", "Fixed", and "Removed" headings
   - Include the version number and release date

### Documentation Guidelines

1. **Inline Documentation**:
   - Use PHPDoc for all classes, methods, and functions
   - Include `@since` tags for new additions
   - Document parameters, return values, and exceptions

2. **Feature Documentation**:
   - Add new features to the appropriate documentation file in `/docs/current/`
   - Use markdown for all documentation
   - Include examples and use cases

3. **User Documentation**:
   - Update user-facing documentation for new features
   - Consider adding screenshots for UI changes
   - Use clear, non-technical language for user docs

## Security Guidelines

When developing for the MemberPress AI Assistant, follow these security best practices:

1. **Input Validation**
   - All user inputs must be validated and sanitized
   - Use WordPress sanitization functions (`sanitize_text_field`, etc.)
   - Validate all parameters using type checking and range validation

2. **Output Escaping**
   - Always escape output with appropriate WordPress escape functions
   - Use `esc_html()`, `esc_attr()`, `esc_url()` as appropriate
   - For complex output in JavaScript, use `wp_json_encode()` with `FILTER_SANITIZE_SPECIAL_CHARS` filter

3. **API Security**
   - Never expose API keys in client-side code
   - Use WordPress nonce verification for all AJAX requests
   - Implement capability checks for all administrative actions
   - Follow least privilege principle when executing operations

4. **Command Execution Security**
   - All WP-CLI commands should be validated against allowlists
   - Always use parameterized commands to prevent injection
   - The Command Security class provides functions for validation

5. **Code Execution and Evaluation**
   - Avoid `eval()` and similar functions
   - Do not use `base64_decode()` with user-supplied data
   - Avoid dynamic inclusion of files with user input

## Internationalization

The plugin supports internationalization via WordPress i18n standards:

1. **Text Domains**
   - Use 'memberpress-ai-assistant' as the text domain
   - All user-facing strings should be translatable

2. **Translation Functions**
   - Use `__()` for simple strings
   - Use `_e()` for echoing strings
   - Use `_n()` for plurals
   - Use `_x()` for strings needing context

3. **Translation Files**
   - Translation template is in `/languages/memberpress-ai-assistant.pot`
   - Translations should be added as `.po` and `.mo` files in the languages directory

## Additional Resources

### Documentation References

- [System Map](https://github.com/memberpress/memberpress-ai-assistant/blob/main/docs/current/core/system-map.md) - Complete system architecture
- [Agent System Guide](https://github.com/memberpress/memberpress-ai-assistant/blob/main/docs/current/agent-system/comprehensive-agent-system-guide.md) - Detailed agent system documentation
- [Tool Implementation Map](https://github.com/memberpress/memberpress-ai-assistant/blob/main/docs/current/tool-system/tool-implementation-map.md) - Guide to implementing tools

### Code References

- [MPAI_Chat](https://github.com/memberpress/memberpress-ai-assistant/blob/main/includes/class-mpai-chat.php) - Main chat processing class
- [MPAI_Context_Manager](https://github.com/memberpress/memberpress-ai-assistant/blob/main/includes/class-mpai-context-manager.php) - Context and tool execution management
- [MPAI_Agent_Orchestrator](https://github.com/memberpress/memberpress-ai-assistant/blob/main/includes/agents/class-mpai-agent-orchestrator.php) - Agent system orchestration

### Official WordPress Resources

- [WordPress Plugin Development](https://developer.wordpress.org/plugins/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [WordPress Security White Paper](https://wordpress.org/about/security/)