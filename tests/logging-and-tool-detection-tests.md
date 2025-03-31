# MemberPress AI Assistant - Logging and Tool Detection Tests

This document outlines specific tests for the console logging system and tool call detection functionality.

## Console Logging Tests

### Basic Functionality

- [ ] **Logger Initialization**: Verify the logger initializes correctly with default settings
  - Load the admin page with developer tools open
  - Check for "MPAI Logger: Initialized" message in console
  - Verify log level and enabled categories are displayed

- [ ] **Settings Persistence**: Test that logger settings persist across page loads
  - Configure logger settings in the Diagnostics tab
  - Refresh the page and verify settings are maintained
  - Check localStorage for mpai_logger_settings entry

- [ ] **Log Levels**: Test different log levels and filtering
  - Set log level to "Error" and verify only errors are logged
  - Set log level to "Warning" and verify errors and warnings are logged
  - Set log level to "Info" and verify errors, warnings, and info messages are logged
  - Set log level to "Debug" and verify all message types are logged

- [ ] **Category Filtering**: Test category-specific logging
  - Enable only "API Calls" category and verify only API related logs appear
  - Enable only "Tool Usage" category and verify only tool related logs appear
  - Test combinations of categories

### Performance Timing

- [ ] **Timer Functions**: Test timer functionality
  - Start and end timers manually: `window.mpaiLogger.startTimer('test'); window.mpaiLogger.endTimer('test');`
  - Verify elapsed time is displayed in console
  - Test with multiple concurrent timers

- [ ] **API Call Timing**: Verify API call performance tracking
  - Make test API calls and check timing information in logs
  - Verify request/response cycle durations are logged

- [ ] **Tool Execution Timing**: Check tool execution performance metrics
  - Execute tools through chat interface and verify timing logs
  - Check that both total execution time and AJAX request time are tracked

### Browser Compatibility

- [ ] **Chrome**: Test all logging features in Chrome
  - Verify formatting, expandable objects, and timing information
  - Test localStorage persistence

- [ ] **Firefox**: Test all logging features in Firefox
  - Verify formatting and console output style
  - Check for any Firefox-specific console API differences

- [ ] **Safari**: Test all logging features in Safari
  - Verify performance.now() compatibility
  - Test expandable object formatting

- [ ] **Edge**: Test all logging features in Edge
  - Verify full functionality matches Chrome

## Tool Call Detection Tests

### Pattern Testing

- [ ] **Standard JSON Code Blocks**: Test detection of standard JSON code blocks
  ```json
  {
    "tool": "wp_api",
    "parameters": { "action": "get_posts" }
  }
  ```

- [ ] **JSON-Object Blocks**: Test detection of JSON-object blocks
  ```json-object
  {
    "tool": "memberpress_info",
    "parameters": { "type": "memberships" }
  }
  ```

- [ ] **Direct JSON**: Test detection of direct JSON in text
  ```
  I recommend using {"tool": "wp_cli", "parameters": {"command": "wp user list"}} to get this information.
  ```

- [ ] **Alternative Formats**: Test different code block styles
  ```
  {"tool": "wp_api", "parameters": {"action": "get_plugins"}}
  ```
  
  ````
  ```
  {"tool": "wp_api", "parameters": {"action": "get_posts"}}
  ```
  ````

### Diagnostic Features

- [ ] **Pattern Statistics**: Verify pattern usage statistics are logged
  - Execute multiple tool calls using different formats
  - Check console for "Tool call detection pattern statistics" message
  - Verify counts for each pattern match the expected values

- [ ] **No Tools Diagnostics**: Test diagnostic output when no tools are detected
  - Provide a response with tool-like content but in incorrect format
  - Verify "No tool calls found" diagnostic details appear in console
  - Check that all detection checks are listed with results

- [ ] **Duplicate Detection**: Verify duplicate tool calls are identified
  - Create a response with the same tool call in multiple formats
  - Verify each tool is only executed once
  - Check for "isDuplicate" log messages

### Error Handling

- [ ] **Malformed JSON**: Test handling of malformed JSON in tool calls
  - Create a response with invalid JSON in a code block
  - Verify error is logged but doesn't break processing
  - Check other valid tool calls are still executed

- [ ] **Missing Properties**: Test with incomplete tool call format
  - Create a response with JSON missing required properties
  - Verify validation errors are logged
  - Check that partial tool calls don't cause execution errors

- [ ] **Tool Execution Errors**: Verify error handling during tool execution
  - Execute a tool call that will trigger a server-side error
  - Verify error is caught and displayed appropriately
  - Check for detailed error information in console logs

## Test Response Templates

To facilitate testing, here are sample AI responses with different tool call formats:

### Basic Tool Call
```
To get a list of all MemberPress memberships, I'll use the memberpress_info tool:

```json
{
  "tool": "memberpress_info",
  "parameters": {
    "type": "memberships"
  }
}
```

This will return all your membership options.
```

### Multiple Tool Calls
```
I'll help you get both user and membership information:

First, let's list all users:
```json
{
  "tool": "wp_cli",
  "parameters": {
    "command": "wp user list --format=json"
  }
}
```

Now, let's check the memberships:
```json
{
  "tool": "memberpress_info",
  "parameters": {
    "type": "memberships"
  }
}
```
```

### Direct JSON Format
```
To get this data, I'll use {"tool": "memberpress_info", "parameters": {"type": "transactions", "limit": 5}} to show your recent transactions.
```

### Mixed Formats
```
Here are two ways to get the information:

```json
{
  "tool": "wp_api",
  "parameters": {
    "action": "get_users",
    "args": {"role": "subscriber"}
  }
}
```

Alternatively, you could use {"tool": "wp_cli", "parameters": {"command": "wp user list --role=subscriber"}} for the same information.
```

## Reporting Tool Call Detection Issues

When reporting tool call detection problems:

1. **Capture Logs**: Include full console output with DEBUG level enabled
2. **Provide Response**: Include the exact AI response that failed detection
3. **Browser Information**: Specify browser and version
4. **Expected Outcome**: Describe what tools should have been detected
5. **Pattern Analysis**: Note which patterns should have matched