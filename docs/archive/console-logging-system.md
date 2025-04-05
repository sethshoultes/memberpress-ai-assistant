# MemberPress AI Assistant Console Logging System

This document explains how the Console Logging System works in MemberPress AI Assistant.

## Overview

The console logging system provides a configurable way to log information to the browser's console for debugging and monitoring purposes. The system supports different log levels, categories, and can be configured through the plugin's admin interface.

## Components

The console logging system consists of the following components:

1. **mpai-logger.js** - Core JavaScript module that implements the logger functionality
2. **Admin class** - PHP class that passes logger settings to JavaScript and handles saving settings
3. **Chat Interface class** - PHP class that passes logger settings to the chat interface
4. **Direct AJAX Handler** - PHP script that provides a direct endpoint for testing and saving console settings

## Configuration

Logger settings can be configured in the System Diagnostics section of the plugin's admin interface. These settings include:

- **Enable Console Logging** - Master toggle for the entire system
- **Log Level** - Controls the verbosity (error, warning, info, debug)
- **Log Categories** - Specific types of events to log (API calls, tool usage, agent activity, etc.)

## How It Works

1. Logger settings are stored in WordPress options:
   - `mpai_enable_console_logging` - Whether logging is enabled (1/0)
   - `mpai_console_log_level` - Log level (error/warning/info/debug)
   - `mpai_log_api_calls` - Whether to log API calls (1/0)
   - `mpai_log_tool_usage` - Whether to log tool usage (1/0)
   - `mpai_log_agent_activity` - Whether to log agent activity (1/0)
   - `mpai_log_timing` - Whether to log timing information (1/0)

2. Settings are passed to JavaScript via:
   - `mpai_data.logger` object (admin pages)
   - `mpai_chat_data.logger` object (chat interface)

3. Settings are also stored in browser's localStorage for persistence between page loads

4. The logger initializes from various sources with this priority:
   - localStorage (for persistence between page reloads)
   - `mpai_data.logger` (admin context)
   - `mpai_chat_data.logger` (chat interface context)
   - Default values as fallback

## Testing

There are multiple ways to test the console logging system:

1. **Ultra Direct Console Test** - Uses inline JavaScript to test console logging without any dependencies

2. **Direct AJAX Handler Console Test** - Uses the direct-ajax-handler.php endpoint to bypass WordPress's admin-ajax.php. This method is more reliable in cases where there are issues with WordPress AJAX.

3. **Test Console Logging** - Standard test button that uses the admin-ajax.php endpoint with the `mpai_save_logger_settings` action.

## Direct AJAX Handler

The direct-ajax-handler.php script provides a special endpoint for console logging tests. It's used when other AJAX methods might be problematic due to:

- Permission issues
- Nonce verification failures
- WordPress admin-ajax.php overhead

This handler:
1. Receives settings via POST
2. Saves them to WordPress options
3. Returns current settings and test results
4. Bypasses many of the usual WordPress AJAX constraints

## Debugging Tips

If console logging isn't working:

1. Check browser console for any JavaScript errors
2. Try the "Ultra Direct Console Test" to verify basic console functionality
3. Try the "Direct AJAX Handler Console Test" to bypass WordPress AJAX issues
4. Check browser localStorage for saved settings
5. Verify WordPress options are being properly saved
6. Ensure mpai-logger.js is loading properly

## Usage in Code

Using the logger in JavaScript:

```javascript
// Basic logging
if (window.mpaiLogger) {
    window.mpaiLogger.debug('Debug message');
    window.mpaiLogger.info('Info message');
    window.mpaiLogger.warn('Warning message');
    window.mpaiLogger.error('Error message');
}

// Category-specific logging
if (window.mpaiLogger) {
    window.mpaiLogger.logApiCall('Anthropic', '/v1/messages', params);
    window.mpaiLogger.logToolUsage('wp_cli', params);
    window.mpaiLogger.logAgentActivity('MemberPressAgent', 'action', data);
}

// Performance timing
if (window.mpaiLogger) {
    window.mpaiLogger.startTimer('operation_name');
    // ... operation ...
    window.mpaiLogger.endTimer('operation_name');
}
```

## Troubleshooting

1. **No logs appear in console**: 
   - Check if the logger is enabled in settings
   - Try the Ultra Direct Console Test
   - Look for JavaScript errors

2. **Settings not saving**:
   - Try using the Direct AJAX Handler test
   - Check network tab for AJAX errors
   - Verify permissions and nonce validation

3. **Logger not available**:
   - Ensure mpai-logger.js is loading properly
   - Check for script loading errors
   - Verify the window.mpaiLogger global object exists