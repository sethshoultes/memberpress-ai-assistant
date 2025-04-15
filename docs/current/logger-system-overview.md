# MemberPress AI Assistant Logger System

## Overview

The Logger System consolidates and standardizes all logging functionality into a single, comprehensive system. It provides consistent interfaces, multiple output methods, and configurable log levels for better diagnostic and debugging capabilities.

## Components

### Interface and Core Classes

- **MPAI_Logger Interface** - Defines the contract that all logger implementations must follow
- **MPAI_Abstract_Logger** - Base implementation with common functionality
- **MPAI_Logger_Manager** - Singleton manager that controls all logger instances

### Logger Implementations

- **MPAI_Error_Log_Logger** - Uses PHP's `error_log()` function (default)
- **MPAI_File_Logger** - Logs to a specified file with rotation support 
- **MPAI_DB_Logger** - Stores logs in the database for persistence and querying
- **MPAI_Null_Logger** - Discards all logs (useful for testing or when logging is disabled)
- **MPAI_Multi_Logger** - Dispatches logs to multiple other loggers

### Utility Functions

- **mpai_logger_manager()** - Returns the logger manager instance
- **mpai_get_logger()** - Gets a specific logger by key
- **mpai_log_<level>()** - Convenience functions for different log levels
- **mpai_log()** - Generic logging function
- **mpai_replace_error_logs_in_file/directory()** - Utility to refactor old `error_log` calls

## Log Levels

The logger system supports the following log levels in decreasing order of severity:

1. **emergency** - System is unusable
2. **alert** - Action must be taken immediately
3. **critical** - Critical conditions
4. **error** - Error conditions
5. **warning** - Warning conditions
6. **notice** - Normal but significant conditions
7. **info** - Informational messages
8. **debug** - Debug-level messages

## Usage Examples

### Basic Logging

```php
// Log an informational message
mpai_log_info('User successfully logged in', ['user_id' => 123]);

// Log an error with exception context
try {
    // Code that might throw an exception
} catch (Exception $e) {
    mpai_log_error('Failed to process payment', ['exception' => $e, 'order_id' => $order_id]);
}
```

### Advanced Usage with Context

```php
// Log a warning with contextual data
mpai_log_warning('Database connection is slow', [
    'query_time' => $query_time,
    'sql' => $sql_query,
    'server' => DB_HOST
]);

// Log with specific logger
mpai_log_debug('Detailed debugging information', $context, 'file');
```

### Registering Custom Logger

```php
// Get the logger manager
$logger_manager = mpai_logger_manager();

// Create a custom logger
$custom_logger = new My_Custom_Logger();

// Register it with the manager
$logger_manager->register_logger('custom', $custom_logger);

// Set as default if desired
$logger_manager->set_default_logger('custom');
```

## Configuration

The logger system can be configured in the WordPress admin under MemberPress AI > Settings > Logging. Options include:

- **Minimum Log Level** - Only messages at or above this level will be logged
- **Database Logging** - Enable storing logs in the database (useful for history)
- **File Logging** - Enable writing logs to a file
- **PHP Error Capture** - Automatically log PHP errors from plugin files
- **Log Retention** - Number of days to keep logs before automatic cleanup

## Migration from error_log

The system includes utilities to help migrate existing `error_log()` calls to the new system:

### WP-CLI Commands

```bash
# Replace error_log calls in plugin files
wp mpai replace-error-logs [directory] --recursive=true

# Restore from backups if needed
wp mpai restore-error-log-backups [directory]
```

### Manual Replacement

Replace calls like:
```php
error_log('MPAI: Something happened');
```

With:
```php
mpai_log_info('Something happened');
```

## Best Practices

1. **Use Appropriate Levels** - Choose the correct log level based on severity
2. **Provide Context** - Always include relevant contextual data in the second parameter
3. **Be Concise** - Keep log messages clear and to the point
4. **Use Components** - For database logging, use the component parameter to categorize logs
5. **Handle Exceptions** - Always log exceptions with full context
6. **Consider Performance** - Use `mpai_logger_manager()->should_log($level)` for expensive operations

## Implementation Details

### Database Schema

When database logging is enabled, logs are stored in the `{prefix}mpai_logs` table with the following structure:

- **id** - Primary key
- **timestamp** - When the log was created
- **level** - Log level (emergency, alert, critical, error, etc.)
- **component** - Component or module that generated the log
- **message** - Log message
- **context** - JSON-encoded contextual data
- **user_id** - WordPress user ID (if available)

### Performance Considerations

- **Log Level Filtering** - Only messages at or above the minimum level are processed
- **Batched Database Operations** - When many logs are created at once
- **File Rotation** - Prevents log files from growing too large
- **Context Serialization** - Complex objects are properly handled

## Integration with Other Systems

The logger system integrates with:

1. **WordPress Settings API** - For configuration
2. **WordPress REST API** - For accessing logs programmatically
3. **WordPress WP-CLI** - For maintenance commands
4. **Error Recovery System** - For capturing fatal errors
5. **MemberPress AI Diagnostic System** - For system health checks