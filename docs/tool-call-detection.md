# Tool Call Detection System

The MemberPress AI Assistant includes an enhanced tool call detection system that improves the reliability of executing commands and tools in response to AI-generated suggestions.

## Overview

Tool calls are JSON-formatted instructions that the AI assistant generates to perform operations like fetching WordPress data, executing commands, or accessing MemberPress information. The detection system is designed to recognize these tool calls in various formats and execute them reliably.

## Features

### Multiple Pattern Recognition

The tool call detection system uses several regex patterns to identify tool calls in different formats:

1. **Standard JSON Code Blocks** - Tool calls in conventional code blocks:
   ```json
   {
     "tool": "wp_api",
     "parameters": { "action": "get_posts" }
   }
   ```

2. **JSON-Object Blocks** - Pre-parsed JSON that shouldn't be double-encoded:
   ```json-object
   {
     "tool": "memberpress_info",
     "parameters": { "type": "memberships" }
   }
   ```

3. **Direct JSON in Text** - Tool calls without code fences:
   ```
   Here's what we'll do: {"tool": "wp_cli", "parameters": {"command": "wp user list"}}
   ```

4. **Multiple Code Block Styles** - Variations in formatting:
   ```
   {"tool": "wp_api", "parameters": {"action": "get_plugins"}}
   ```

5. **Alternative Formats** - Tool calls with irregular spacing or newlines

### Duplicate Detection

The system intelligently prevents duplicate tool execution by:

- Comparing tool names and parameters to identify duplicates
- Tracking which tool calls have already been processed
- Skipping redundant executions while maintaining output formatting

### Enhanced Debugging

When tool calls aren't being detected, the system provides detailed diagnostics:

- Shows which regex patterns matched or failed
- Displays pattern usage statistics
- Lists tools being used in the response
- Examines the response for potential undetected tool calls
- Checks for JSON code blocks, tool keywords, and parameter references

### Performance Monitoring

Each tool execution includes detailed performance tracking:

- Execution time measured with high-precision timing
- Request/response cycle duration logging
- Tool-specific performance metrics
- Pattern efficiency analytics

## Implementation

The tool call detection system is primarily implemented in `assets/js/chat-interface.js` and integrates with the console logging system for detailed visibility into its operation.

### Key Components

- **Regex Pattern Set** - Multiple patterns targeting different tool call formats
- **Pattern Processor** - Unified code for handling matches from different patterns
- **Match Consolidator** - Prevents duplicate tool executions
- **Diagnostic Logger** - Provides detailed debug information
- **Execution Tracker** - Monitors tool execution performance

### Pattern Definitions

```javascript
// Standard JSON code blocks with optional 'json' tag
const jsonBlockRegex = /```(?:json)?\s*\n({[\s\S]*?})\s*\n```/g;  

// JSON-object blocks for pre-parsed data
const jsonObjectBlockRegex = /```(?:json-object)?\s*\n({[\s\S]*?})\s*\n```/g;  

// Direct JSON in text with tool/parameters
const directJsonRegex = /\{[\s\S]*?["']tool["'][\s\S]*?["']parameters["'][\s\S]*?\}/g;  

// Any code block containing tool calls
const anyCodeBlockRegex = /```[\w-]*\s*\n(\{[\s\S]*?["']tool["'][\s\S]*?["']parameters["'][\s\S]*?\})\s*\n```/g;  

// No newlines after code fence format
const altFormatRegex = /```\s*(\{[\s\S]*?["']tool["'][\s\S]*?["']parameters["'][\s\S]*?\})\s*```/g;
```

## Diagnostics

The system includes comprehensive diagnostic capabilities that help identify why tool calls might not be detected or executed properly:

### Console Output

When tool calls are successfully detected:
```
MPAI: Found 2 tool calls to process
MPAI: Tool call detection pattern statistics: {jsonBlockRegex: 1, directJsonRegex: 1}
MPAI: Tools being used: ["wp_api", "memberpress_info"]
```

When no tool calls are found:
```
MPAI: No tool calls found. Response contains:
- JSON code blocks: true
- Any code blocks: true
- Tool keyword: true
- Parameters keyword: true
MPAI: Response contains "tool" and "parameters" keywords, but might be in an unexpected format:
MPAI: Potential undetected tool patterns: ["tool":"wp_api","parameters"]
```

### Pattern Testing

The system tests each regex pattern against the response and logs the results:
```
MPAI: Testing jsonBlockRegex: true
MPAI: Testing jsonObjectBlockRegex: false
MPAI: Testing directJsonRegex: true
MPAI: Testing anyCodeBlockRegex: true
MPAI: Testing altFormatRegex: false
```

## Troubleshooting

If tool calls aren't being detected properly:

1. **Enable Debug Logging** - Set console log level to "Debug" in the Diagnostics tab
2. **Check Browser Console** - Look for "MPAI: Found 0 tool calls to process" messages
3. **Examine Response Format** - The detailed diagnostics will show if code blocks contain the expected structure
4. **Check Pattern Test Results** - See which patterns are matching or failing
5. **Look for Alternative Formats** - The system will identify potential undetected patterns
6. **Review the AI's Response** - Sometimes the AI doesn't include proper tool call formatting

## Future Improvements

The tool call detection system will continue to evolve with:

1. **Machine Learning Pattern Recognition** - For identifying highly irregular tool call formats
2. **Response Correction** - Automatically fixing malformed tool calls
3. **AI Prompt Refinement** - Improving instructions to the AI for consistent tool call formatting
4. **Pattern Performance Analytics** - Measuring which patterns are most effective over time