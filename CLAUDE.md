# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Essential Development Commands

### PHP Testing & Code Quality
```bash
# Run PHPUnit tests
composer test
# Or directly with vendor binary
./vendor/bin/phpunit

# Run PHP CodeSniffer (WordPress coding standards)
composer phpcs

# Auto-fix PHP code style issues
composer phpcbf

# Run specific test class
./vendor/bin/phpunit tests/Unit/Agents/MemberPressAgentTest.php

# Run with coverage
./vendor/bin/phpunit --coverage-html tests/coverage
```

### JavaScript Testing
```bash
# Run Jest tests
npm test

# Run tests in watch mode for development
npm run test:watch

# Generate test coverage report
npm run test:coverage
```

### WordPress Integration
This is a WordPress plugin that requires MemberPress to be installed and activated. The plugin integrates deeply with WordPress admin interface and MemberPress functionality.

## System Architecture Overview

### Core Architectural Patterns
- **Dependency Injection Container**: Uses ServiceLocator pattern for component management
- **Agent-Based Architecture**: Specialized AI agents handle different request types (MemberPressAgent, ContentAgent, SystemAgent, ValidationAgent)  
- **Tool-Based Operations**: Modular tools for specific operations (MemberPressTool, ContentTool, WordPressTool)
- **Service-Oriented Architecture**: Business logic encapsulated in discrete services
- **Orchestration Layer**: AgentOrchestrator coordinates between agents and manages context

### Key System Components

**Agent System (`src/Agents/`)**:
- Specialized AI assistants for different domains
- AgentOrchestrator selects appropriate agent based on request context
- AgentFactory creates instances with proper dependencies
- AgentRegistry maintains available agents

**Tool System (`src/Tools/`)**:
- Reusable operations with standardized interfaces
- CachedToolWrapper provides performance optimization
- ToolRegistry maintains available tools

**Dependency Injection (`src/DI/`)**:
- ServiceLocator manages component creation and dependencies
- Service Providers organize related service registrations
- Factory classes create specialized instances

**Services Layer (`src/Services/`)**:
- MemberPressService: Core MemberPress integration
- ChatInterfaceService: Manages conversational UI
- CacheService: Provides caching functionality
- ConfigurationService: Handles system configuration

### Data Flow Architecture
1. User input → Controller → Service → AgentOrchestrator
2. AgentOrchestrator → Agent selection → Tool execution
3. Results → Service → Controller → UI response

## WordPress Plugin Structure

**Main Plugin File**: `memberpress-copilot.php`
- Plugin initialization and dependency setup
- Service registration and bootstrapping
- WordPress hook integration

**Admin Interface** (`src/Admin/`):
- MPAIAdminMenu: WordPress admin menu integration
- MPAIAjaxHandler: AJAX request processing
- Settings management with MVC pattern

**Templates** (`templates/`):
- chat-interface.php: Main chat UI
- settings-page.php: Plugin configuration  
- dashboard-tab.php: WordPress dashboard integration

## Important Development Notes

### Service Container Usage
The system uses a ServiceLocator pattern rather than a full DI container. Services are lazily loaded and typically registered as singletons.

```php
// Access services through global service locator
global $mpai_service_locator;
$memberpress_service = $mpai_service_locator->get('memberpress');
```

### Agent Development
When creating new agents:
- Extend AbstractAgent or implement AgentInterface
- Register with AgentRegistry in appropriate ServiceProvider
- Follow the standardized tool execution pattern

### Tool Development  
When creating new tools:
- Extend AbstractTool or implement ToolInterface
- Consider caching with CachedToolWrapper for performance
- Register with ToolRegistry

### Testing Architecture
- **PHP Tests**: Located in `tests/Unit/` and use PHPUnit with WordPress testing framework
- **JavaScript Tests**: Located in `tests/js/` and use Jest with JSDOM
- **Mock Objects**: Use TestCase base class and MockFactory for consistent test data

### Logging System
Custom logging utility at `src/Utilities/LoggingUtility.php` with configurable log levels. Avoid using raw `error_log()` - use the logging utility instead.

### Performance Considerations
- CachedToolWrapper provides automatic caching for expensive operations
- ServiceLocator uses lazy loading to avoid memory issues
- Database queries are optimized through adapters and transformers

### Consent System Removed
Phase 6A completed - all MPAIConsentManager references have been removed from the codebase. The plugin now operates without consent requirements.