# MemberPress AI Assistant - Specialized Tests

**Status:** ✅ Maintained  
**Version:** 1.0.0  
**Last Updated:** April 3, 2025

## Overview

This document provides detailed testing procedures for specialized components of the MemberPress AI Assistant plugin. These specialized tests focus on specific subsystems or features that require detailed verification beyond the general test procedures.

## Test Categories

- [Console Logging System](#console-logging-system)
- [Tool Call Detection](#tool-call-detection)
- [Agent System](#agent-system)
- [Command Validation](#command-validation)
- [MemberPress Integration](#memberpress-integration)
- [API Integrations](#api-integrations)
- [Performance Optimization](#performance-optimization)

## Console Logging System

Detailed in [logging-and-tool-detection-tests.md](logging-and-tool-detection-tests.md#console-logging-tests)

### Key Test Areas:

1. **Logger Initialization**
   - Verify initialization with default settings
   - Check for proper console output

2. **Settings Persistence**
   - Test settings storage in localStorage
   - Verify persistence across page reloads

3. **Log Levels**
   - Test error, warning, info, and debug levels
   - Verify appropriate filtering by level

4. **Timing Functions**
   - Test startTimer and endTimer methods
   - Verify API call and tool execution timing

## Tool Call Detection

Detailed in [logging-and-tool-detection-tests.md](logging-and-tool-detection-tests.md#tool-call-detection-tests)

### Key Test Areas:

1. **Pattern Detection**
   - Test standard JSON code blocks
   - Test JSON-object code blocks
   - Test direct JSON in text
   - Test alternative code block styles

2. **Error Handling**
   - Test malformed JSON handling
   - Test missing properties validation
   - Test tool execution error reporting

3. **Duplicate Detection**
   - Verify duplicate tool calls are identified
   - Check that each tool is executed only once

## Agent System

Detailed in [test-agent-system.php](test-agent-system.php)

### Key Test Areas:

1. **Component Loading**
   - Verify required files exist
   - Check all classes can be loaded
   - Test class instantiation

2. **Agent Capabilities**
   - Check agent capabilities are defined
   - Verify orchestrator can access capabilities

3. **Request Processing**
   - Test sample queries
   - Verify appropriate agent routing

4. **Agent Scoring**
   - Test confidence scoring system
   - Verify appropriate agent selection based on scores

## Command Validation

Detailed in [test-validate-command.php](test-validate-command.php)

### Key Test Areas:

1. **Security Validation**
   - Test allowed commands
   - Test disallowed commands
   - Test command injection attempts

2. **Parameter Handling**
   - Test required parameters
   - Test optional parameters
   - Test parameter validation

3. **Execution Flow**
   - Verify validation occurs before execution
   - Check permission validation
   - Test error handling for invalid commands

## MemberPress Integration

Detailed in [memberpress-test.php](memberpress-test.php) and [test-best-selling.php](test-best-selling.php)

### Key Test Areas:

1. **Data Access**
   - Test membership data retrieval
   - Test transaction data access
   - Test user subscription information

2. **Best-Selling Feature**
   - Verify correct identification of top-selling products
   - Test date range filtering
   - Check performance with large datasets

3. **Independent Operation**
   - Test fallback when MemberPress is not available
   - Verify graceful degradation of features

## API Integrations

Detailed in [anthropic-test.php](anthropic-test.php) and [openai-test.php](openai-test.php)

### Key Test Areas:

1. **Authentication**
   - Test API key validation
   - Verify secure key storage

2. **Request Handling**
   - Test message formatting
   - Check parameter handling
   - Verify context management

3. **Response Processing**
   - Test response parsing
   - Verify tool call extraction
   - Check error handling for API failures

## Performance Optimization

Detailed in [test-system-cache.php](test-system-cache.php)

### Key Test Areas:

1. **Response Caching**
   - Test cache hit/miss logic
   - Verify cache expiration
   - Check memory usage

2. **Tool Execution**
   - Test tool execution timing
   - Verify performance optimizations
   - Check concurrent tool execution

3. **Large Dataset Handling**
   - Test with large conversation histories
   - Verify pagination handling
   - Check memory usage with large datasets

## Test Response Templates

To facilitate testing, sample data templates are provided for:

- [Tool Call Formats](logging-and-tool-detection-tests.md#test-response-templates)
- Sample queries in [test-agent-system.php](test-agent-system.php)
- Test commands in [test-validate-command.php](test-validate-command.php)

## Running Specialized Tests

Most specialized tests can be run using WP-CLI:

```bash
# Test agent system
wp eval-file test/test-agent-system.php

# Test command validation
wp eval-file test/test-validate-command.php

# Test best-selling feature
wp eval-file test/test-best-selling.php
```

For browser-based tests (console logging, tool detection), follow the procedures in [logging-and-tool-detection-tests.md](logging-and-tool-detection-tests.md) and use the browser's developer tools.

## Reporting Test Results

When reporting specialized test results:

1. Reference the specific test procedure followed
2. Provide full console output when available
3. Include environment details (WordPress version, PHP version, browser)
4. Describe any deviations from expected behavior
5. Include screenshots for visual verification when applicable

For detailed reporting guidelines, see [test-procedures.md](test-procedures.md#test-reporting).