# MemberPress AI Assistant System Map

**Version:** 1.5.8  
**Last Updated:** 2025-04-03  
**Status:** ✅ Maintained

This document provides a comprehensive system map of the MemberPress AI Assistant plugin, detailing the purpose and interactions of each file in the system.

## Plugin Structure Overview

MemberPress AI Assistant is structured with the following main components:

- **Main Plugin File**: Initializes the plugin and core functionality
- **Core Classes**: Handles API integration, chat functionality, and settings
- **Agent System**: Provides a modular system of AI agents and tools
- **Admin Interface**: Manages admin pages, settings, and the chat interface
- **Asset Files**: CSS and JavaScript for styling and client-side functionality
- **Test Files**: Testing scripts and procedures for various features

## Core Files

### Main Plugin File

`/memberpress-ai-assistant.php`: 
- The main plugin file that initializes everything
- Defines plugin constants (MPAI_VERSION, MPAI_PLUGIN_DIR, etc.)
- Contains the MemberPress_AI_Assistant class which:
  - Handles plugin initialization and dependencies loading
  - Registers hooks for admin menus, AJAX handlers
  - Creates database tables on activation
  - Manages chat interface rendering
  - Contains direct plugin logs functionality

### Core API Integration

`/includes/class-mpai-openai.php`:
- Handles integration with OpenAI's API
- Manages API authentication, requests, and response parsing
- Implements function calling capabilities for tools

`/includes/class-mpai-anthropic.php`:
- Handles integration with Anthropic's Claude API
- Similar to OpenAI integration but with Claude-specific formats
- Supports Claude's function calling format

`/includes/class-mpai-api-router.php`:
- Routes requests between OpenAI and Anthropic
- Handles API fallback between providers
- Formats requests and responses to a common format

`/includes/class-mpai-memberpress-api.php`:
- Interfaces with MemberPress data
- Retrieves members, memberships, transactions, subscriptions
- Contains best-selling membership functionality
- Formats data for AI consumption

### Chat and Context Management

`/includes/class-mpai-chat.php`:
- Main chat processing class
- Manages conversations with AI
- Formats prompts and handles responses
- Processes tool calls from AI responses

`/includes/class-mpai-context-manager.php`:
- Manages execution context for AI tools
- Defines and registers available tools
- Handles command execution and validation
- Processes memberpress_info tool requests
- Routes tool calls to appropriate handlers

`/includes/class-mpai-plugin-logger.php`:
- Logs plugin activity (installation, updates, etc.)
- Provides functionality for retrieving and querying logs
- Supports API access for tool integration

### Admin and Settings

`/includes/class-mpai-admin.php`:
- Handles admin UI components
- Manages admin notices and integration

`/includes/class-mpai-settings.php`:
- Manages plugin settings
- Handles settings validation and storage
- Provides default settings values

`/includes/class-mpai-chat-interface.php`:
- Renders the chat interface bubble
- Handles chat interface styling and behavior

`/includes/admin-page.php`:
- Renders the main plugin admin page
- Displays the main chat interface

`/includes/settings-page.php`:
- Renders the settings configuration page
- Provides settings for API keys, models, and features

`/includes/chat-interface.php`:
- Contains the HTML for the floating chat interface
- Referenced by the main plugin class for rendering

`/includes/direct-ajax-handler.php`:
- Handles AJAX requests that bypass WordPress nonce checks
- Used by AI tools that need direct access

`/includes/settings-diagnostic.php`:
- Diagnostic page for troubleshooting
- Shows system information and test functionality

### Agent System

`/includes/agents/interfaces/interface-mpai-agent.php`:
- Defines the interface for all agent implementations
- Specifies required methods and structure

`/includes/agents/class-mpai-base-agent.php`:
- Base implementation for all agents
- Contains common agent functionality

`/includes/agents/class-mpai-agent-orchestrator.php`:
- Manages and coordinates multiple agents
- Handles agent discovery and registration
- Routes requests to appropriate agents

`/includes/agents/specialized/class-mpai-command-validation-agent.php`:
- Validates CLI commands before execution
- Ensures commands are safe and on the allowed list

`/includes/agents/specialized/class-mpai-memberpress-agent.php`:
- Specialized agent for MemberPress operations
- Handles MemberPress-specific requests and data

`/includes/agents/sdk/class-mpai-py-bridge.php`:
- Bridge between PHP and potential Python components
- Currently not actively used but provides future expansion

`/includes/agents/sdk/class-mpai-sdk-agent-adapter.php`:
- Adapter for external SDK-based agents
- Creates compatibility between SDK and internal agent system

`/includes/agents/sdk/class-mpai-sdk-integration.php`:
- Integration point for external SDK functionality
- Manages SDK initialization and configuration

### Tools System

`/includes/tools/class-mpai-base-tool.php`:
- Base implementation for all tools
- Contains common tool functionality and interface

`/includes/tools/class-mpai-tool-registry.php`:
- Manages tool registration and discovery
- Provides access to available tools

`/includes/tools/implementations/class-mpai-diagnostic-tool.php`:
- Tool for system diagnostics
- Provides system information to the AI

`/includes/tools/implementations/class-mpai-plugin-logs-tool.php`:
- Tool for accessing plugin logs
- Allows AI to query and analyze plugin activity

`/includes/tools/implementations/class-mpai-wp-api-tool.php`:
- Tool for WordPress API operations
- Provides WordPress functionality in browser environments
- Alternative to WP-CLI for commands without CLI access

`/includes/tools/implementations/class-mpai-wpcli-tool.php`:
- Tool for WP-CLI command execution
- Manages command validation and execution
- Integrates with System Information Caching for improved performance
- Caches expensive system queries like plugin lists and PHP information

### CLI Integration

`/includes/cli/class-mpai-cli-commands.php`:
- Registers custom WP-CLI commands
- Provides CLI access to plugin functionality
- Commands include: insights, recommend, chat, run

### Site Health Integration

`/includes/class-mpai-site-health.php`:
- Integrates with WordPress Site Health API
- Provides enhanced diagnostics for MemberPress

### Feature-Specific Files

`/includes/class-mpai-system-cache.php`:
- Implements system information caching
- Provides multi-tiered caching with in-memory and filesystem storage
- Manages automatic invalidation and TTL-based expiration
- Significantly improves performance for repeated system queries

`/includes/best-selling-membership.php`:
- Implementation guidance for best-selling membership feature
- Documentation for integrating the feature into MemberPress API

## Asset Files

### CSS Files

`/assets/css/admin.css`:
- Styling for admin pages and interfaces
- Used on the plugin's admin pages

`/assets/css/chat-interface.css`:
- Styling for the floating chat interface
- Handles bubble appearance, chat window, and messages

### JavaScript Files

`/assets/js/admin.js`:
- JavaScript for admin page functionality
- Handles settings forms, buttons, and UI interactions

`/assets/js/chat-interface.js`:
- JavaScript for the chat interface
- Manages user interactions, message sending
- Processes AI responses and tool calls
- Renders formatted responses

`/assets/js/mpai-logger.js`:
- Client-side logging system
- Provides structured console logging
- Supports different log levels and categories

### Images

`/assets/images/memberpress-logo.svg`:
- MemberPress logo for UI elements
- Used in the chat interface

## Test Files

`/test/ajax-test.php`:
- Tests AJAX functionality
- Verifies request/response handling

`/test/anthropic-test.php`:
- Tests Anthropic API integration
- Verifies Claude API functionality

`/test/debug-info.php`:
- Displays debug information
- Useful for troubleshooting

`/test/diagnostic-page.php`:
- Diagnostic page for system testing
- Shows comprehensive system information

`/test/direct-ajax-handler-fix.php`:
- Testing for direct AJAX handler fixes
- Verifies solution for AJAX handling issues

`/test/memberpress-test.php`:
- Tests MemberPress integration
- Verifies data retrieval and formatting

`/test/openai-test.php`:
- Tests OpenAI API integration
- Verifies API key and model functionality

`/test/test-activate-plugin.php`:
- Tests plugin activation functionality
- Verifies activation/deactivation processes

`/test/test-agent-system.php`:
- Tests the agent system
- Verifies agent registration and execution

`/test/test-best-selling.php`:
- Tests best-selling membership feature
- Verifies data retrieval and ranking

`/test/test-plugin-logs.php`:
- Tests plugin logging system
- Verifies log recording and retrieval

`/test/test-procedures.md`:
- Documentation of testing procedures
- Step-by-step guides for testing features

`/test/test-validate-command.php`:
- Tests command validation functionality
- Verifies CLI command validation

`/test/test-system-cache.php`:
- Tests System Information Caching functionality
- Verifies cache operations, TTL settings, and performance improvements

`/test/test-validate-theme-block.php`:
- Tests theme and block validation
- Verifies validation for themes and blocks

## File Relationships and Interactions

### Initialization Flow

1. Plugin is loaded from `memberpress-ai-assistant.php`
2. `MemberPress_AI_Assistant::get_instance()` is called on plugins_loaded
3. Constructor loads dependencies and registers hooks
4. Admin assets are enqueued on admin_enqueue_scripts
5. Chat interface is rendered on admin_footer
6. AJAX handlers are registered for chat functionality

### Chat Processing Flow

1. User sends message via chat interface (`/assets/js/chat-interface.js`)
2. AJAX request to `process_chat_ajax` in main plugin file
3. `MPAI_Chat::process_message()` processes the message
4. Message is sent to API via `MPAI_API_Router`
5. Response is processed for tool calls
6. Tool calls are executed via `MPAI_Context_Manager`
7. Response is formatted and returned to the user

### Tool Execution Flow

1. AI identifies a tool to use (e.g., memberpress_info)
2. Tool call is detected in `MPAI_Chat::process_message()`
3. Tool execution is delegated to `MPAI_Context_Manager`
4. Context Manager routes to appropriate handler method
5. Tool executes and returns formatted data
6. Response is incorporated into AI's message

## Root Documentation Files

The project root contains two key documentation files that serve as primary entry points for developers:

1. **`_0_START_HERE_.md`**: Primary entry point for new developers with pathways to different types of development tasks

2. **`_1_AGENTIC_SYSTEMS_.md`**: Comprehensive guide to the agent system with detailed implementation information

These files should be the starting point for new developers working with the codebase.

## Files That Could Be Removed/Consolidated

1. `/includes/agents/sdk/`: The SDK integration files appear to be placeholders and not actively used in the current implementation. The three files in this directory could potentially be removed or consolidated if the SDK functionality is not currently being used.

2. `/test/direct-ajax-handler-fix.php`: This appears to be a temporary test file that has likely served its purpose now that the fixes are implemented.

3. Several test files could be consolidated or moved to a more organized structure, particularly those that test similar functionality.

## Documentation Structure

The documentation is organized into several key areas:

1. **Root Documentation**: Primary entry points for developers
   - `_0_START_HERE_.md`
   - `_1_AGENTIC_SYSTEMS_.md`

2. **Feature Documentation**: Details on specific features
   - `/docs/current/`: Documentation for implemented features
   - `/docs/roadmap/`: Documentation for planned features
   - `/docs/archive/`: Historical documentation that has been superseded

3. **Reference Documentation**:
   - `/docs/current/system-map.md`: This file
   - `/docs/current/tool-implementation-map.md`: Guide for implementing tools
   - `/docs/current/implementation-status.md`: Status of all features

For a visual guide to documentation, see [documentation-map.md](documentation-map.md).

## Recommendations for Improvement

1. **Organize Test Files**: Create subdirectories within `/test/` for different categories of tests (API, AJAX, features, etc.)

2. **Consolidate API Handling**: The OpenAI and Anthropic integration classes contain some duplicate functionality that could be abstracted to a common base class.

3. **Document Tool Dependencies**: The tool implementation files don't clearly indicate their dependencies, which could make troubleshooting difficult.

4. **Improve Error Handling**: While there is error logging throughout the codebase, a more centralized error handling system could improve debugging and user feedback.

5. **Standardize Naming Conventions**: Some files use different naming conventions (class-mpai-*.php vs. other formats), making navigation less intuitive.

## Conclusion

The MemberPress AI Assistant is a well-structured plugin with clear separation of concerns. The main components (API integration, chat processing, agent system, and tools) are organized logically. Some improvements could be made in standardizing naming conventions and consolidating duplicate functionality, but overall the structure supports the plugin's functionality effectively.

Most files serve a clear purpose in the system and are actively used. The few files that could be removed or consolidated are primarily related to the SDK integration which appears to be a future expansion point rather than currently active functionality.

For developers working with this codebase, please refer to the root documentation files (`_0_START_HERE_.md` and `_1_AGENTIC_SYSTEMS_.md`) for the most up-to-date guidance on development approaches and best practices.