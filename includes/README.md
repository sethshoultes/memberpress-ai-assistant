# MemberPress AI Assistant: Includes Directory

This directory contains the core classes and functionality for the MemberPress AI Assistant plugin. Below is an overview of the architecture and guidelines for development.

## Overview

The MemberPress AI Assistant is built on a modular architecture with several key systems:

1. **Core System**: Base classes for plugin functionality
2. **Agent System**: Specialized AI agents for different domains
3. **Tool System**: Tools for AI to interact with WordPress
4. **Command System**: WP-CLI command processing
5. **Error Recovery System**: Robust error handling and recovery
6. **Caching System**: Performance optimization through caching

## Development Guidelines

### Class Naming Conventions

- All plugin-specific classes use the `MPAI_` prefix with PascalCase
- MemberPress core classes use the `Mepr` prefix
- Files should match class names with `class-mpai-*.php` format
- Use camelCase for methods, snake_case for hooks
- Use `mpai_` prefix for actions and filters

### Architecture

The plugin uses a layered architecture:

1. **UI Layer**: Admin pages, chat interface, and settings
2. **Business Logic Layer**: Agents, context management, tools
3. **Data Layer**: MemberPress API, system cache, response cache

### Visual Architecture Overview

```
┌───────────────────────────────────────────────────────┐
│                      UI LAYER                         │
├───────────┬───────────────┬───────────┬──────────────┤
│ Admin UI  │ Chat Interface│ Settings  │ Dashboard    │
└─────┬─────┴───────┬───────┴─────┬─────┴──────┬───────┘
      │             │             │            │        
┌─────▼─────────────▼─────────────▼────────────▼───────┐
│                 BUSINESS LOGIC LAYER                  │
├───────────┬───────────────┬───────────┬──────────────┤
│           │               │           │              │
│  Agent    │  Context      │  Tool     │  Command     │
│  System   │  Management   │  System   │  System      │
│           │               │           │              │
└─────┬─────┴───────┬───────┴─────┬─────┴──────┬───────┘
      │             │             │            │        
┌─────▼─────────────▼─────────────▼────────────▼───────┐
│                     DATA LAYER                        │
├───────────┬───────────────┬───────────┬──────────────┤
│MemberPress│  System       │ Response  │ WordPress    │
│   API     │  Cache        │  Cache    │    API       │
└───────────┴───────────────┴───────────┴──────────────┘
```

### Component Relationships

```
                 ┌───────────────┐
                 │    MPAI_Chat  │
                 └───────┬───────┘
                         │
                         ▼
┌────────────────────────────────────────────────────────┐
│                 MPAI_Context_Manager                    │
└───┬────────────────┬────────────────┬──────────────────┘
    │                │                │
    ▼                ▼                ▼
┌─────────┐   ┌────────────┐   ┌─────────────┐
│MPAI_API_│   │MPAI_Agent_ │   │MPAI_Tool_   │
│Router   │   │Orchestrator│   │Registry     │
└────┬────┘   └─────┬──────┘   └──────┬──────┘
     │              │                 │
     ▼              ▼                 ▼
┌─────────┐   ┌────────────┐   ┌─────────────┐
│API      │   │Specialized │   │Tool         │
│Providers│   │Agents      │   │Implementations
└─────────┘   └────────────┘   └─────────────┘
```

### Data Flow Architecture

```
┌──────────┐    ┌─────────────┐    ┌────────────────┐
│  User    │───▶│ Chat        │───▶│ Context        │
│ Interface│    │ Interface   │    │ Manager        │
└──────────┘    └─────────────┘    └───────┬────────┘
                                           │
                       ┌───────────────────┼───────────────────┐
                       │                   │                   │
                       ▼                   ▼                   ▼
               ┌─────────────┐    ┌─────────────┐    ┌─────────────┐
               │ Agent       │    │ Tool        │    │ API         │
               │ System      │    │ System      │    │ Router      │
               └──────┬──────┘    └──────┬──────┘    └──────┬──────┘
                      │                  │                  │
                      ▼                  ▼                  ▼
               ┌─────────────┐    ┌─────────────┐    ┌─────────────┐
               │ Agent       │    │ WordPress   │    │ AI Provider │
               │ Response    │───▶│ Action      │───▶│ Response    │
               └─────────────┘    └─────────────┘    └─────────────┘
                                                            │
                                                            ▼
                                                     ┌─────────────┐
                                                     │ User        │
                                                     │ Response    │
                                                     └─────────────┘
```

### Error Recovery System

```
┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│ API Call or │    │ Exception   │    │ Error       │
│ Operation   │───▶│ or Error    │───▶│ Recovery    │
└─────────────┘    └─────────────┘    │ System      │
                                      └──────┬──────┘
                                             │
              ┌────────────────────┬────────┴────────┬────────────────────┐
              │                    │                 │                    │
              ▼                    ▼                 ▼                    ▼
      ┌─────────────┐      ┌─────────────┐   ┌─────────────┐      ┌─────────────┐
      │ Retry       │      │ Fallback    │   │ Graceful    │      │ Error       │
      │ Mechanism   │      │ Strategy    │   │ Degradation │      │ Reporting   │
      └─────────────┘      └─────────────┘   └─────────────┘      └─────────────┘
```

### Adding New Functionality

When adding new functionality, follow these guidelines:

1. **New Tools**:
   - Create a new class in `tools/implementations/` that extends `MPAI_Base_Tool`
   - Register in `class-mpai-tool-registry.php`
   - Update system prompt in `MPAI_Chat::get_system_message()`

2. **New Agents**:
   - Create a new class in `agents/specialized/` that extends `MPAI_Base_Agent`
   - Register in `class-mpai-agent-orchestrator.php`

3. **System Modifications**:
   - Update `class-mpai-context-manager.php` for new tool types
   - Update `class-mpai-api-router.php` for new API providers
   - Update `class-mpai-chat.php` for conversation flow changes

### Error Handling

- Use `WP_Error` for error objects
- Add comprehensive try/catch blocks for API calls
- Integrate with the Error Recovery System via `MPAI_Error_Recovery`
- Log errors with specific prefixes: `error_log('MPAI: ' . $message)`

### Testing

- Add test cases for new functionality in `tests/`
- Follow testing procedures in `tests/test-procedures.md`
- Use the `mpai_test_system_information_cache()` pattern for system tests

### Performance Considerations

- Use `MPAI_System_Cache` for frequently accessed system information
- Use `MPAI_Response_Cache` for API responses
- Consider filesystem persistence for long-lived cache data
- Add cache invalidation hooks for relevant WordPress actions

## Key Files

- `class-mpai-chat.php`: Core chat processing and AI interaction
- `class-mpai-context-manager.php`: Manages conversation context and tool execution
- `class-mpai-api-router.php`: Routes requests to appropriate AI provider
- `class-mpai-system-cache.php`: System information caching
- `class-mpai-error-recovery.php`: Error handling and recovery
- `class-mpai-memberpress-api.php`: MemberPress functionality interface

## Documentation

For more detailed information, refer to:

- [System Map](/docs/current/core/system-map.md): Complete system architecture
- [Documentation Map](/docs/current/core/documentation-map.md): Visual guide to documentation
- [Tool System](/docs/current/tool-system/tool-implementation-map.md): Tool implementation details
- [Agent System](/docs/current/agent-system/unified-agent-system.md): Agent architecture
- [Console Logging System](/docs/current/js-system/console-logging-system.md): JavaScript logging

## Need Help?

If you encounter issues or need assistance:

1. Check the Scooby Snacks documentation in `/docs/_snacks/` for solutions to common problems
2. Review the Error Catalog System in `/docs/current/MPAI_Error_Catalog_System.md`
3. Follow the Scooby Mode investigation protocol for systematic troubleshooting

Remember to add "🦴 Scooby Snack" to commit messages when solving documented issues!