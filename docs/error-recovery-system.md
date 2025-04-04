# MemberPress AI Assistant - Error Recovery System

The Error Recovery System provides standardized error handling and recovery mechanisms for the MemberPress AI Assistant plugin. It enables graceful degradation when errors occur and improves the overall robustness of the plugin.

## Features

- **Standardized Error Types and Severity Levels**: Clearly defined error classifications for consistent handling.
- **Context-Rich Error Objects**: Enhanced error objects with detailed context information.
- **Recovery Strategies**: Configurable retry and fallback mechanisms for different error types.
- **Circuit Breaker Pattern**: Prevents repeated failures by temporarily disabling failing components.
- **User-Friendly Error Messages**: Formats error messages appropriately for end users.
- **Component-Specific Error Creation**: Specialized error creation for APIs, tools, and agents.

## Core Components

### 1. Error Recovery Class (`MPAI_Error_Recovery`)

The main class that provides the error recovery functionality:

- `create_error()`: Creates standardized error objects with enhanced context.
- `handle_error()`: Handles errors with appropriate recovery strategies.
- `is_circuit_breaker_tripped()`: Checks if a circuit breaker is tripped for a component.
- `format_error_for_display()`: Formats errors for user display.
- `create_api_error()`, `create_tool_error()`, `create_agent_error()`: Component-specific error creation.

### 2. Error Types

The system defines standardized error types:

- `ERROR_TYPE_API`: API-related errors (OpenAI, Anthropic).
- `ERROR_TYPE_TOOL`: Tool execution errors.
- `ERROR_TYPE_AGENT`: Agent-related errors.
- `ERROR_TYPE_DATABASE`: Database errors.
- `ERROR_TYPE_PERMISSION`: Permission-related errors.
- `ERROR_TYPE_VALIDATION`: Validation errors.
- `ERROR_TYPE_TIMEOUT`: Timeout errors.
- `ERROR_TYPE_RESOURCE`: Resource limitation errors.
- `ERROR_TYPE_SYSTEM`: System errors.
- `ERROR_TYPE_NETWORK`: Network connectivity errors.

### 3. Severity Levels

Four severity levels are defined:

- `SEVERITY_CRITICAL`: System cannot function, no recovery possible.
- `SEVERITY_ERROR`: Component failed, recovery may be possible.
- `SEVERITY_WARNING`: Issue detected but operation can continue.
- `SEVERITY_INFO`: Informational message about recovery.

### 4. Recovery Strategies

Each error type has configurable recovery strategies:

- **Retry Mechanism**: Attempts the same operation multiple times before giving up.
- **Fallback Options**: Uses alternative methods or components when primary ones fail.
- **Circuit Breaker Settings**: Threshold and reset time for preventing cascading failures.

### 5. Integration Points

The Error Recovery System is integrated with key plugin components:

- **API Router**: For robust handling of API failures and fallback between providers.
- **Context Manager**: For tool execution error handling and recovery.
- **Agent Orchestrator**: For agent-related error handling and task fallback.

## Implementation Details

### Default Recovery Strategies

The system provides default recovery strategies for common error types:

- **API Errors**: 3 retries with 1-second delay, fallback to alternative API provider.
- **Tool Errors**: 2 retries, possibility to use alternative tools.
- **Agent Errors**: 1 retry, fallback to alternative agent, degraded mode operation.
- **Database Errors**: 3 retries with 2-second delay, in-memory fallback.

### Error Context

Errors include rich context information:

- Unique error ID for tracking
- Type and severity
- Timestamp
- Component-specific context (e.g., API endpoint, tool arguments)
- User context (when applicable)

### Circuit Breaker Implementation

The circuit breaker pattern is implemented with:

- Error counters per component
- Configurable thresholds for tripping
- Automatic reset after a specified time period
- State tracking to prevent further calls to failing components

## Usage Examples

### Handling API Errors

```php
// Create an API error
$error = $error_recovery->create_api_error(
    'openai', 
    'rate_limit_exceeded', 
    'OpenAI API rate limit exceeded', 
    ['endpoint' => '/v1/chat/completions']
);

// Define retry and fallback functions
$retry_function = function() {
    // Retry API call with backoff
};

$fallback_function = function() {
    // Use alternative API provider
};

// Handle the error with recovery
$result = $error_recovery->handle_error(
    $error,
    'chat_completion',
    $retry_function,
    [],
    $fallback_function
);
```

### Tool Execution Error Recovery

```php
// Define execution and fallback functions
$execute_command = function() use ($wp_cli_tool) {
    return $wp_cli_tool->execute(['command' => 'wp plugin list']);
};

$fallback_execution = function() {
    return $this->execute_wp_cli_command('wp plugin list --format=table');
};

// Handle execution with recovery
$result = $error_recovery->handle_error(
    $error_recovery->create_tool_error('wp_cli', 'command_execution', 'WP-CLI command execution'),
    'wp_cli',
    $execute_command,
    [],
    $fallback_execution
);
```

## Testing

A comprehensive test suite is available for validating the Error Recovery System:

1. Error creation with context
2. Recovery with retry functionality
3. Recovery with fallback functionality
4. Circuit breaker pattern testing
5. Error formatting for display
6. Integration with API Router and Context Manager

Run tests using the provided diagnostic page at `/test/test-error-recovery-page.php` or via the direct AJAX handler with action `test_error_recovery`.

## Benefits

- **Improved Robustness**: Graceful degradation when errors occur.
- **Better User Experience**: Less likelihood of complete system failure.
- **Enhanced Debugging**: Rich context for error diagnosis.
- **Consistent Error Handling**: Standardized approach across components.
- **Fault Isolation**: Circuit breaker prevents cascading failures.
- **Flexible Recovery**: Configurable strategies for different error types.

## Future Enhancements

- Persistent error logging to database for trend analysis
- User notification system for critical errors
- Adaptive retry strategies based on error patterns
- Dashboard for error statistics and monitoring
- Event-driven recovery mechanisms