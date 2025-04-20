# MemberPress AI Assistant: Command Flow and Data Pipeline

This document provides a comprehensive overview of the MemberPress AI Assistant's command processing flow, data pipeline, and examples of commands and queries that can be used with the system.

## System Architecture Overview

The MemberPress AI Assistant is built with a layered architecture that processes user queries through several stages:

1. **Query Detection and Classification**
2. **Direct Handler Execution** (for common queries)
3. **AI-Based Processing** (for complex queries)
4. **Tool Execution** (for performing actions)
5. **Response Formatting and Delivery**

## Command and Data Flow

### 1. Initial Query Processing

When a user sends a message to the MemberPress AI Assistant, the following flow occurs:

```
User Message → MPAI_Chat::process_message() → Query Detection → Handler Selection → Response Generation
```

The system first checks if the query matches any patterns in the query registry. If a match is found, it bypasses the AI and uses a direct handler. Otherwise, it sends the query to the AI for processing.

### 2. Query Registry and Direct Handlers

The system maintains a registry of common query types and their corresponding handlers in the `get_query_registry()` method. This allows for efficient processing of frequent queries without involving the AI.

Current direct handlers include:
- Plugin history queries
- Best-selling membership queries
- Active subscription queries
- User listing queries
- Post listing queries
- Plugin list queries

### 3. AI Processing Flow

For queries that don't match direct handlers, the system follows this flow:

```
User Message → API Router → AI Model → Tool Call Detection → Tool Execution → Response Formatting
```

The AI model (typically OpenAI's API) processes the query and may return:
- A direct text response
- A tool call request (in JSON format)

### 4. Tool Execution Pipeline

When a tool call is detected, the system processes it through:

```
Tool Call → Context Manager → Tool Registry → Tool Implementation → Result → Response Formatting
```

Available tools include:
- `wp_api`: For WordPress API operations
- `wpcli`: For WordPress CLI commands
- `memberpress_info`: For MemberPress-specific data
- `plugin_logs`: For plugin activity logs

### 5. Response Processing

After executing a tool or getting a direct response, the system:
1. Formats the response
2. Saves the conversation to the database
3. Returns the formatted response to the user

## Command Examples and Capabilities

### Direct Query Examples

These queries bypass the AI and use direct handlers:

#### Plugin History Queries
```
"Show me the plugin history"
"What plugins were recently activated?"
"Show plugin activation logs"
```

#### Membership Queries
```
"What are the best-selling memberships?"
"Show me the top selling membership products"
"Which membership is most popular?"
```

#### Subscription Queries
```
"Show active subscriptions"
"How many active members do we have?"
"List current subscriptions"
```

#### User Listing Queries
```
"List all WordPress users"
"Show me the site users"
"Get user list"
```

#### Post Listing Queries
```
"Show all posts"
"List recent blog posts"
"Get post list"
```

#### Plugin Listing Queries
```
"List all plugins"
"Show installed plugins"
"What plugins are active?"
```

### Tool-Based Command Examples

These commands are processed through the AI and executed using tools:

#### WordPress API Tool (`wp_api`)

**Creating Content:**
```
"Create a new post titled 'Welcome to MemberPress' with content 'This is a welcome post for new members.'"
```

This would trigger:
```json
{"tool": "wp_api", "parameters": {"action": "create_post", "title": "Welcome to MemberPress", "content": "This is a welcome post for new members.", "status": "draft"}}
```

**Managing Plugins:**
```
"Activate the MemberPress CoachKit plugin"
```

This would trigger:
```json
{"tool": "wp_api", "parameters": {"action": "activate_plugin", "plugin": "memberpress-coachkit/main.php"}}
```

**Getting User Information:**
```
"Show me the first 10 users"
```

This would trigger:
```json
{"tool": "wp_api", "parameters": {"action": "get_users", "limit": 10}}
```

#### WordPress CLI Tool (`wpcli`)

```
"Run wp core version"
"Check PHP version"
"List all themes"
```

These would trigger the corresponding WP-CLI commands through the `wpcli` tool.

#### MemberPress Info Tool (`memberpress_info`)

```
"Get MemberPress system information"
"Show me membership statistics"
"How many new members joined this month?"
```

These would trigger:
```json
{"tool": "memberpress_info", "parameters": {"type": "system_info"}}
{"tool": "memberpress_info", "parameters": {"type": "summary"}}
{"tool": "memberpress_info", "parameters": {"type": "new_members_this_month"}}
```

#### Plugin Logs Tool (`plugin_logs`)

```
"Show me plugin activity for the last 30 days"
"What plugins were activated recently?"
"Show plugin installation history"
```

These would trigger:
```json
{"tool": "plugin_logs", "parameters": {"days": 30}}
{"tool": "plugin_logs", "parameters": {"action": "activated", "days": 30}}
{"tool": "plugin_logs", "parameters": {"action": "installed", "days": 30}}
```

## Data Flow Diagram

```
User Query
    │
    ▼
┌─────────────────┐
│ MPAI_Chat       │
│ process_message │
└────────┬────────┘
         │
         ▼
┌─────────────────┐     Yes    ┌─────────────────┐
│ Match in Query  ├───────────►│ Direct Handler  │
│ Registry?       │            │ Execution       │
└────────┬────────┘            └────────┬────────┘
         │ No                           │
         ▼                              │
┌─────────────────┐                     │
│ API Router      │                     │
│ (AI Processing) │                     │
└────────┬────────┘                     │
         │                              │
         ▼                              │
┌─────────────────┐     Yes    ┌────────▼────────┐
│ Contains Tool   ├───────────►│ Context Manager │
│ Call?           │            │ Tool Processing │
└────────┬────────┘            └────────┬────────┘
         │ No                           │
         │                              │
         │                              │
         ▼                              ▼
┌─────────────────┐            ┌─────────────────┐
│ Format Direct   │            │ Execute Tool    │
│ Response        │            │ Implementation  │
└────────┬────────┘            └────────┬────────┘
         │                              │
         └──────────────────────────────┘
                        │
                        ▼
                ┌─────────────────┐
                │ Format Response │
                │ Save to DB      │
                └────────┬────────┘
                         │
                         ▼
                   User Response
```

## Implementation Details

### Query Detection System

The query detection system uses keyword matching to identify common query types:

```php
private function get_query_registry() {
    return [
        'plugin_history' => [
            'keywords' => ['plugin history', 'plugin activation history', ...],
            'handler' => 'get_direct_plugin_history',
            'description' => 'Shows plugin activation and deactivation history'
        ],
        'best_selling_memberships' => [
            'keywords' => ['best-selling membership', 'top selling membership', ...],
            'handler' => 'get_direct_best_selling_memberships',
            'description' => 'Shows best-selling memberships'
        ],
        // Additional query types...
    ];
}
```

### Direct Handler Example

Direct handlers bypass the AI completely and execute code directly:

```php
private function get_direct_user_list() {
    global $wpdb;
    $results = $wpdb->get_results("SELECT ID, user_login, user_email, user_registered FROM {$wpdb->users} ORDER BY ID ASC LIMIT 10");
    
    // Format results as HTML table
    $output = "<h2>WordPress Users</h2>";
    $output .= "<table border='1'>...</table>";
    
    // Save this as a message/response pair
    $this->save_message("Show users", $output);
    
    return [
        'success' => true,
        'message' => $output
    ];
}
```

### Tool Execution Example

When the AI returns a tool call, it's processed by the Context Manager:

```php
public function process_tool_request($request) {
    // Validate the tool exists
    if (!isset($request['name']) || !isset($this->available_tools[$request['name']])) {
        return [
            'success' => false,
            'error' => 'Tool not found or invalid'
        ];
    }

    $tool = $this->available_tools[$request['name']];
    $parameters = isset($request['parameters']) ? $request['parameters'] : [];
    
    // Execute the tool
    $result = call_user_func($tool['callback'], $parameters);
    
    return [
        'success' => true,
        'tool' => $request['name'],
        'result' => $result
    ];
}
```

## Best Practices for Using the System

1. **Direct Queries for Common Tasks**: For frequent operations like listing users, posts, or plugins, use direct queries that match the query registry patterns.

2. **Specific Commands for Complex Tasks**: For complex operations, be specific in your requests to help the AI generate the correct tool calls.

3. **Combining Multiple Operations**: You can perform multiple operations in sequence by sending multiple messages.

4. **Using System Information**: Ask for system information or MemberPress statistics to get insights into your WordPress installation.

## Example Conversation Flow

```
User: "Show me the active subscriptions"

System: [Detects direct handler match, bypasses AI]
[Executes get_direct_active_subscriptions()]
[Returns formatted HTML table with subscription data]

User: "Create a new post about MemberPress features"

System: [No direct handler match, sends to AI]
[AI generates tool call for wp_api with create_post action]
[Context Manager executes the tool]
[Returns success message with post details]

User: "What plugins were activated recently?"

System: [Detects direct handler match for plugin history]
[Executes get_direct_plugin_history()]
[Returns formatted HTML with plugin activity logs]
```

## Conclusion

The MemberPress AI Assistant provides a flexible and efficient system for handling user queries through a combination of direct handlers and AI-powered tool execution. By understanding the command flow and data pipeline, you can effectively use the system to manage your MemberPress installation and WordPress site.

For developers looking to extend the system, the modular architecture allows for adding new direct handlers, tools, and query types to enhance the capabilities of the assistant.