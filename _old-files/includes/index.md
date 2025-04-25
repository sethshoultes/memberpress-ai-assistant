# MemberPress AI Assistant: Includes Directory

**Version:** 2.5.0  
**Last Updated:** 2025-04-06  
**Status:** ✅ Active Development  
**Owner:** MemberPress AI Team

This directory contains the core functionality of the MemberPress AI Assistant plugin. It's organized into several key components and subsystems that work together to provide AI-powered features for MemberPress.

## Directory Structure

| Directory | Purpose | Status |
|-----------|---------|--------|
| **Root Files** | Core classes for the plugin's main functionality | ✅ Stable |
| **[agents/](#agent-system)** | Agent system architecture for specialized AI behaviors | ✅ Stable |
| **[cli/](#cli-system)** | WordPress CLI integration | ✅ Stable |
| **[commands/](#command-system)** | Command processing and execution system | ✅ Stable |
| **[tools/](#tool-system)** | Tool implementations for AI to interact with WordPress | 🚧 Active Development |
| **[templates/](#templates)** | UI templates for the plugin | ✅ Stable |
| **[tests/](#tests)** | Internal test implementations | 🔄 Regular Updates |

## Key Components

### Core Files

| File | Purpose | Status | Last Updated |
|------|---------|--------|-------------|
| `class-mpai-chat.php` | Core chat processing and AI interaction | ✅ Stable | 2025-03-15 |
| `class-mpai-context-manager.php` | Manages conversation context and tool execution | ✅ Stable | 2025-03-20 |
| `class-mpai-api-router.php` | Routes requests to appropriate AI provider (OpenAI/Anthropic) | ✅ Stable | 2025-02-28 |
| `class-mpai-openai.php` | OpenAI API integration | ✅ Stable | 2025-03-10 |
| `class-mpai-anthropic.php` | Anthropic Claude API integration | 🚧 Active Development | 2025-04-05 |
| `class-mpai-memberpress-api.php` | Interface to MemberPress functionality | ✅ Stable | 2025-03-01 |
| `class-mpai-system-cache.php` | Performance optimization through system information caching | ✅ Stable | 2025-02-15 |
| `class-mpai-error-recovery.php` | Error handling and recovery system | ✅ Stable | 2025-03-05 |
| `class-mpai-plugin-logger.php` | Logging system for plugin activities | ✅ Stable | 2025-02-20 |
| `class-mpai-state-validator.php` | Validation system for maintaining system state consistency | 🚧 Active Development | 2025-04-02 |
| `class-mpai-input-validator.php` | Input validation and sanitization | ✅ Stable | 2025-03-08 |
| `class-mpai-site-health.php` | WordPress Site Health integration | 🚧 Active Development | 2025-04-04 |
| `class-mpai-xml-content-parser.php` | XML content system for structured content | ✅ Stable | 2025-03-12 |

### Admin & UI

| File | Purpose | Status | Last Updated |
|------|---------|--------|-------------|
| `class-mpai-admin.php` | Admin functionality and hooks | ✅ Stable | 2025-03-10 |
| `class-mpai-settings.php` | Plugin settings management | ✅ Stable | 2025-03-15 |
| `class-mpai-chat-interface.php` | Chat UI implementation | 🚧 Active Development | 2025-04-03 |
| `settings-page.php` | Settings page UI | ✅ Stable | 2025-02-28 |
| `settings-diagnostic.php` | Diagnostic panel implementation | ✅ Stable | 2025-03-05 |
| `admin-page.php` | Admin page implementation | ✅ Stable | 2025-02-20 |
| `dashboard-page.php` | Dashboard UI implementation | 🚧 Active Development | 2025-04-01 |
| `chat-interface.php` | Chat interface implementation | 🚧 Active Development | 2025-04-04 |

### Agent System

The agent system (`agents/` directory) provides specialized AI capabilities through a modular architecture:

| Component | Purpose |
|-----------|---------|
| `class-mpai-agent-orchestrator.php` | Coordinates and routes requests between specialized agents |
| `class-mpai-base-agent.php` | Base functionality for all agents |
| `interface-mpai-agent.php` | Interface that all agents must implement |
| `specialized/` | Implementation of domain-specific agents |
| `sdk/` | Integration with external AI systems and frameworks |

### CLI System

The CLI system (`cli/` directory) provides WordPress command-line integration:

| File | Purpose |
|------|---------|
| `class-mpai-cli-commands.php` | WP-CLI commands implementation |

### Command System

The command system (`commands/` directory) handles WordPress command processing:

| File | Purpose |
|------|---------|
| `class-mpai-command-adapter.php` | Adapts commands for execution |
| `class-mpai-command-detector.php` | Detects commands in user input |
| `class-mpai-command-handler.php` | Handles command execution |
| `class-mpai-command-sanitizer.php` | Sanitizes command input |
| `class-mpai-command-security.php` | Security checks for commands |
| `class-mpai-php-executor.php` | PHP code execution |
| `class-mpai-wp-cli-executor.php` | WP-CLI command execution |

### Tool System

The tool system (`tools/` directory) provides functionality for AI to interact with WordPress:

| File | Purpose |
|------|---------|
| `class-mpai-base-tool.php` | Base functionality for all tools |
| `class-mpai-tool-registry.php` | Registers and manages available tools |
| `implementations/` | Specific tool implementations |

## Integration Points

For developers extending the plugin, here are the key integration points:

1. **New Tools**: Add new tools in `tools/implementations/` by extending `MPAI_Base_Tool`
2. **New Agents**: Create specialized agents in `agents/specialized/` by extending `MPAI_Base_Agent`
3. **Custom Commands**: Extend the command system in the `commands/` directory
4. **UI Customizations**: Modify templates in the `templates/` directory

## Additional Resources

For more detailed information, see:

- [System Map](/docs/current/core/system-map.md)
- [Documentation Map](/docs/current/core/documentation-map.md)
- [Tool Implementation Map](/docs/current/tool-system/tool-implementation-map.md)
- [Unified Agent System](/docs/current/agent-system/unified-agent-system.md)