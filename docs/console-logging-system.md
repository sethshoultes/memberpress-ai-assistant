# Console Logging System

The MemberPress AI Assistant includes a comprehensive console logging system that helps developers and administrators debug and monitor the application's behavior through the browser's JavaScript console.

## Overview

The console logging system is designed to provide structured, categorized, and level-based logging that can be configured through the WordPress settings interface. This allows for detailed insights into the plugin's operations, including API calls, tool usage, agent activities, and performance timing.

## Features

### Log Levels

The logging system supports four log levels, with increasing verbosity:

1. **Error** - Critical errors that prevent functionality from working
2. **Warning** - Important issues that don't cause complete failure
3. **Info** - General information about operations (default level)
4. **Debug** - Detailed information for troubleshooting

Each level includes the messages from all less-verbose levels (e.g., selecting "Warning" will show both Warning and Error messages).

### Log Categories

Logs are organized into categories to make them easier to filter and understand:

1. **API Calls** - Interactions with OpenAI, Anthropic, and other external APIs
2. **Tool Usage** - Execution of tools like WP-CLI, WordPress API, and MemberPress tools
3. **Agent Activity** - Operations performed by the agent system
4. **Timing** - Performance measurements for operations

Each category can be individually enabled or disabled.

### Performance Timing

The logger includes built-in performance tracking to measure the duration of operations:

```javascript
// Start timing an operation
window.mpaiLogger.startTimer('operation_name');

// Some code to measure...
doSomething();

// End timing and get the duration
const duration = window.mpaiLogger.endTimer('operation_name');
// The duration is also automatically logged
```

## Configuration

The console logging system can be configured through the "Diagnostics" tab in the MemberPress AI Assistant settings page:

1. **Enable Console Logging** - Master switch to turn logging on/off
2. **Log Level** - Select the verbosity level
3. **Log Categories** - Choose which categories to enable
4. **Test Console Logging** - Button to run a test of the current configuration

## Implementation

The logging system is implemented in `assets/js/mpai-logger.js` and is loaded before other scripts to ensure it's available throughout the application's lifecycle.

### Key Methods

- `error(message, category, data)` - Log an error
- `warn(message, category, data)` - Log a warning
- `info(message, category, data)` - Log information (default level)
- `debug(message, category, data)` - Log detailed debug information
- `logApiCall(service, endpoint, params, level)` - Log an API call
- `logToolUsage(toolName, params, level)` - Log tool execution
- `logAgentActivity(agentName, action, data, level)` - Log agent actions
- `startTimer(label)` - Start timing an operation
- `endTimer(label)` - End timing and return duration

### Example Usage

```javascript
// Basic logging
window.mpaiLogger.info('Operation completed successfully');

// Categorized logging
window.mpaiLogger.info('Processing user message', 'api_calls');

// Logging with additional data
window.mpaiLogger.debug('Tool execution details', 'tool_usage', {
    tool: 'wp_api',
    parameters: { action: 'get_posts', limit: 10 }
});

// Performance timing
window.mpaiLogger.startTimer('process_message');
// ... code to measure ...
const elapsed = window.mpaiLogger.endTimer('process_message');
console.log(`Operation took ${elapsed.toFixed(2)}ms`);
```

## Enhanced Tool Call Detection

The console logging system includes specialized debugging for tool call detection in the chat interface, which helps diagnose issues with tool execution. The system logs:

- Which regex patterns were used to detect tool calls
- Raw tool call JSON for inspection
- Pattern statistics to show which detection methods were most effective
- Details about tools being used
- Comprehensive diagnostics when no tool calls are detected

## Persistence

Logger settings are stored in both:

1. WordPress options for server-side configuration
2. `localStorage` for client-side persistence between page loads

This ensures that logging preferences are maintained even when WordPress data isn't available.

## Testing

A built-in test function can be triggered from the Diagnostics tab to verify logging functionality:

```javascript
// Run a comprehensive test
window.mpaiLogger.testLog();
```

This will log test messages at all levels and categories based on current settings.

## Troubleshooting

If you're not seeing log messages in your browser console:

1. Ensure console logging is enabled in the settings
2. Open your browser's developer tools (F12 in most browsers)
3. Navigate to the "Console" tab
4. Check that the console is not filtering out the messages
5. Try different log levels and categories to see if specific types are working