# MemberPress AI Assistant Testing System

**Status:** ✅ Maintained  
**Version:** 1.0.0  
**Last Updated:** April 3, 2025

## Overview

This directory contains all testing resources for the MemberPress AI Assistant plugin, including test procedures, test scripts, unit tests, and integration tests. The testing system is designed to ensure the quality, functionality, and security of the plugin across all its components.

## Test Directory Structure

- [**Testing Procedures**](test-procedures.md) - Comprehensive test checklists and procedures
- [**Unit Tests**](unit/) - Automated unit tests for core components
- [**Specialized Tests**](specialized-tests.md) - Categorized special-purpose test scripts
- [**Feature Tests**](#feature-tests) - Tests for specific plugin features
- [**Integration Tests**](#integration-tests) - Tests for external system integration
- [**Performance Tests**](#performance-tests) - Tests for performance and optimization

## Quick References

- [Test Procedures](test-procedures.md) - Complete testing checklist for plugin release
- [Logging and Tool Detection Tests](logging-and-tool-detection-tests.md) - Dedicated tests for console logging and tool detection
- [Phase One Tests](test-phase-one.php) - Basic functionality test suite
- [Agent System Tests](test-agent-system.php) - Tests for the agent orchestration system

## Test Categories

### Unit Tests

Located in the [unit/](unit/) directory, these tests use PHPUnit for automated testing of core components:

| Test File | Component | Description |
|-----------|-----------|-------------|
| [AgentOrchestratorTest.php](unit/AgentOrchestratorTest.php) | Agent System | Tests agent discovery, security validation, messaging, and scoring |
| [ToolRegistryTest.php](unit/ToolRegistryTest.php) | Tool System | Tests tool registration, validation, and execution |

### Feature Tests

These test files verify specific plugin features:

| Test File | Feature | Description |
|-----------|---------|-------------|
| [test-agent-system.php](test-agent-system.php) | Agent System | Tests agent system components and orchestration |
| [test-agent-scoring.php](test-agent-scoring.php) | Agent Scoring | Tests agent specialization scoring system |
| [test-plugin-logs.php](test-plugin-logs.php) | Logging System | Tests the plugin logging functionality |
| [test-validate-command.php](test-validate-command.php) | Command Validation | Tests command security validation |
| [test-best-selling.php](test-best-selling.php) | MemberPress Integration | Tests best-selling membership feature |

### Integration Tests

These test files verify integration with external systems:

| Test File | Integration | Description |
|-----------|-------------|-------------|
| [ajax-test.php](ajax-test.php) | AJAX | Tests AJAX handler functionality |
| [anthropic-test.php](anthropic-test.php) | Anthropic API | Tests integration with Anthropic Claude |
| [openai-test.php](openai-test.php) | OpenAI API | Tests integration with OpenAI GPT models |
| [memberpress-test.php](memberpress-test.php) | MemberPress | Tests integration with MemberPress core |
| [test-plugin-list.php](test-plugin-list.php) | WordPress | Tests integration with WordPress plugins |

### Performance Tests

These test files verify performance and optimization:

| Test File | Performance Area | Description |
|-----------|------------------|-------------|
| [test-system-cache.php](test-system-cache.php) | Response Cache | Tests caching system performance |

## Test Utilities

Supporting files and utilities for testing:

| File | Purpose | Description |
|------|---------|-------------|
| [bootstrap.php](bootstrap.php) | Test Bootstrap | Sets up the environment for tests |
| [debug-info.php](debug-info.php) | Debugging | Provides debugging information |
| [diagnostic-page.php](diagnostic-page.php) | System Diagnostics | Tests the diagnostic page functionality |
| [direct-ajax-handler-fix.php](direct-ajax-handler-fix.php) | AJAX Testing | Tests fixes for direct AJAX handlers |

## Running Tests

### Manual Testing

Follow the procedures in [test-procedures.md](test-procedures.md) for comprehensive manual testing. Key test areas include:

1. Installation and activation
2. Settings configuration
3. Chat interface functionality
4. API integrations (OpenAI, Anthropic)
5. MemberPress data access
6. WP-CLI commands
7. Security and validation
8. Browser compatibility

### Automated Testing

For unit tests:

```bash
# Run all unit tests
wp scaffold plugin-tests memberpress-ai-assistant
cd /tmp/wordpress/wp-content/plugins/memberpress-ai-assistant
composer install
bash bin/install-wp-tests.sh wordpress_test root '' localhost latest
vendor/bin/phpunit

# Run a specific test class
vendor/bin/phpunit --filter=AgentOrchestratorTest
```

For integration tests:

```bash
# Run a specific integration test
wp eval-file test/memberpress-test.php

# Run with debugging
WP_DEBUG=true wp eval-file test/test-agent-system.php
```

## Test Development Guidelines

When creating new tests:

1. **Unit Tests**: Place in the `unit/` directory following PHPUnit conventions
2. **Feature Tests**: Create dedicated test files for each major feature
3. **Test Procedures**: Update test-procedures.md when adding new testable features
4. **Documentation**: Add new tests to this README.md in the appropriate section

### Test File Naming Conventions

- Unit tests: `ClassNameTest.php`
- Feature tests: `test-feature-name.php`
- Integration tests: `system-name-test.php`

## Automated Test Integration

Future plans include:

- GitHub Actions integration for CI/CD
- Automated test runs on pull requests
- Performance benchmark tests
- Visual regression testing for UI components
- End-to-end testing for user workflows

## Contributing to the Test Suite

When contributing new tests:

1. Follow existing patterns and naming conventions
2. Include detailed comments explaining the purpose of each test
3. Update relevant documentation, including this README.md
4. Ensure all tests pass before submitting pull requests

## Related Documentation

- [Developer Guide](../docs/current/core/developer-guide.md)
- [System Map](../docs/current/core/system-map.md)
- [Plugin Specification](../docs/current/core/project-specification.md)
- [Implementation Status](../docs/current/core/implementation-status.md)