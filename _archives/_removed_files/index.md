# MemberPress AI Assistant Test Index

**Status:** ✅ Maintained  
**Version:** 1.0.0  
**Last Updated:** April 3, 2025

This document provides a categorized index of all tests available for the MemberPress AI Assistant plugin to facilitate testing activities.

## Testing Resources

| Resource | Type | Description |
|----------|------|-------------|
| [README.md](README.md) | Documentation | Overview of the testing system |
| [test-procedures.md](test-procedures.md) | Procedure | Comprehensive test checklist |
| [logging-and-tool-detection-tests.md](logging-and-tool-detection-tests.md) | Procedure | Console logging and tool detection test procedures |

## Tests by Plugin Component

### Core System

| Test File | Type | Description |
|-----------|------|-------------|
| [test-activate-plugin.php](test-activate-plugin.php) | Integration | Tests plugin activation and initialization |
| [bootstrap.php](bootstrap.php) | Utility | Test bootstrap file for environment setup |

### Agent System

| Test File | Type | Description |
|-----------|------|-------------|
| [test-agent-system.php](test-agent-system.php) | Integration | Tests agent system components |
| [test-agent-scoring.php](test-agent-scoring.php) | Feature | Tests agent specialization scoring |
| [unit/AgentOrchestratorTest.php](unit/AgentOrchestratorTest.php) | Unit | Unit tests for Agent Orchestrator |

### Tool System

| Test File | Type | Description |
|-----------|------|-------------|
| [unit/ToolRegistryTest.php](unit/ToolRegistryTest.php) | Unit | Unit tests for Tool Registry |
| [test-validate-command.php](test-validate-command.php) | Feature | Tests command validation security |

### API Integration

| Test File | Type | Description |
|-----------|------|-------------|
| [anthropic-test.php](anthropic-test.php) | Integration | Tests Anthropic API integration |
| [openai-test.php](openai-test.php) | Integration | Tests OpenAI API integration |
| [ajax-test.php](ajax-test.php) | Integration | Tests AJAX handler functionality |
| [direct-ajax-handler-fix.php](direct-ajax-handler-fix.php) | Utility | Tests for AJAX handler fixes |

### MemberPress Integration

| Test File | Type | Description |
|-----------|------|-------------|
| [memberpress-test.php](memberpress-test.php) | Integration | Tests MemberPress core integration |
| [test-best-selling.php](test-best-selling.php) | Feature | Tests best-selling membership feature |
| [best-selling-membership.php](best-selling-membership.php) | Utility | Utility for best-selling tests |

### User Interface

| Test File | Type | Description |
|-----------|------|-------------|
| [diagnostic-page.php](diagnostic-page.php) | Feature | Tests diagnostic page UI |
| [test-script.js](test-script.js) | Utility | JavaScript testing utilities |

### WordPress Integration

| Test File | Type | Description |
|-----------|------|-------------|
| [test-plugin-list.php](test-plugin-list.php) | Integration | Tests WordPress plugin integration |
| [test-validate-theme-block.php](test-validate-theme-block.php) | Feature | Tests theme block validation |

### Logging & Diagnostics

| Test File | Type | Description |
|-----------|------|-------------|
| [test-plugin-logs.php](test-plugin-logs.php) | Feature | Tests logging functionality |
| [debug-info.php](debug-info.php) | Utility | Debug information display |

### Performance

| Test File | Type | Description |
|-----------|------|-------------|
| [test-system-cache.php](test-system-cache.php) | Performance | Tests response caching system |
| [test-update-message.php](test-update-message.php) | Feature | Tests update notification system |

### Phase-Specific Tests

| Test File | Type | Description |
|-----------|------|-------------|
| [test-phase-one.php](test-phase-one.php) | Integration | Tests Phase One functionality |
| [test-input-validator.php](test-input-validator.php) | Feature | Tests Input Validation system |
| [integration/tools/test-wpcli-tool.php](integration/tools/test-wpcli-tool.php) | Integration | Tests WP-CLI Tool execution |
| [integration/tools/test-wp-api-tool.php](integration/tools/test-wp-api-tool.php) | Integration | Tests WordPress API Tool execution |
| [integration/tools/test-plugin-logs-tool.php](integration/tools/test-plugin-logs-tool.php) | Integration | Tests Plugin Logs Tool execution |
| [edge-cases/test-input-validation.php](edge-cases/test-input-validation.php) | Edge Case | Tests input validation edge cases |
| [edge-cases/test-resource-limits.php](edge-cases/test-resource-limits.php) | Edge Case | Tests resource constraint handling |
| [edge-cases/test-error-conditions.php](edge-cases/test-error-conditions.php) | Edge Case | Tests error condition handling |

## Tests by Type

### Unit Tests

- [AgentOrchestratorTest.php](unit/AgentOrchestratorTest.php) - Agent Orchestrator component
- [ToolRegistryTest.php](unit/ToolRegistryTest.php) - Tool Registry component

### Integration Tests

- [ajax-test.php](ajax-test.php) - AJAX functionality
- [anthropic-test.php](anthropic-test.php) - Anthropic API
- [memberpress-test.php](memberpress-test.php) - MemberPress integration
- [openai-test.php](openai-test.php) - OpenAI API
- [test-activate-plugin.php](test-activate-plugin.php) - Plugin activation
- [test-agent-system.php](test-agent-system.php) - Agent system
- [test-phase-one.php](test-phase-one.php) - Phase One features
- [test-plugin-list.php](test-plugin-list.php) - WordPress plugin integration

### Feature Tests

- [diagnostic-page.php](diagnostic-page.php) - Diagnostic UI
- [test-agent-scoring.php](test-agent-scoring.php) - Agent scoring
- [test-best-selling.php](test-best-selling.php) - Best selling memberships
- [test-input-validator.php](test-input-validator.php) - Input validation system
- [test-plugin-logs.php](test-plugin-logs.php) - Logging system
- [test-system-cache.php](test-system-cache.php) - Response cache
- [test-update-message.php](test-update-message.php) - Update notifications
- [test-validate-command.php](test-validate-command.php) - Command validation
- [test-validate-theme-block.php](test-validate-theme-block.php) - Theme block validation

### Utilities

- [best-selling-membership.php](best-selling-membership.php) - Best selling test utilities
- [bootstrap.php](bootstrap.php) - Test environment setup
- [debug-info.php](debug-info.php) - Debugging utilities
- [direct-ajax-handler-fix.php](direct-ajax-handler-fix.php) - AJAX handler utilities
- [test-script.js](test-script.js) - JavaScript test utilities

## Running Tests

For complete information on executing tests, see the [README.md](README.md) file.

Quick reference for common testing operations:

```bash
# Run unit tests
cd /tmp/wordpress/wp-content/plugins/memberpress-ai-assistant
vendor/bin/phpunit

# Run integration tests
wp eval-file test/memberpress-test.php

# Run with debug mode
WP_DEBUG=true wp eval-file test/test-agent-system.php
```

## Test Development

When adding new tests, follow these guidelines:

1. Place unit tests in the `unit/` directory
2. Name integration tests with the pattern `system-name-test.php`
3. Name feature tests with the pattern `test-feature-name.php`
4. Update this index when adding new test files
5. Include detailed comments in test files explaining their purpose

For detailed test development standards, see the [README.md](README.md) file.