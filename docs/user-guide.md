# MemberPress AI Assistant - User Guide

## Introduction

The MemberPress AI Assistant is a powerful plugin that integrates MemberPress with OpenAI to provide intelligent assistance for your membership site. This guide will help you get started with configuring and using the plugin.

## Features

- AI-powered chat interface for MemberPress data analysis
- WP-CLI integration for running commands with AI assistance
- OpenAI integration using the latest models
- Secure conversation storage
- Command recommendation system
- Enhanced command output formatting with automatic table detection
- One-click command execution from chat messages
- Command runner interface for direct command entry
- Improved error handling with detailed feedback
- Support for a wide range of WordPress CLI commands

## Installation

1. Download the `memberpress-ai-assistant` plugin
2. Upload it to your WordPress site via Plugins > Add New > Upload Plugin
3. Activate the plugin through the WordPress Plugins menu
4. Navigate to AI Assistant > Settings to configure the plugin

## Requirements

- WordPress 5.6 or higher
- PHP 7.4 or higher
- MemberPress plugin installed and activated
- OpenAI API key (https://platform.openai.com/api-keys)
- WP-CLI installed (optional, for command-line features)

## Configuration

### API Settings

1. Navigate to AI Assistant > Settings in your WordPress admin
2. Enter your OpenAI API key in the API Key field
3. Select your preferred model (e.g., gpt-4o)
4. Adjust temperature and token settings as needed
5. Save your settings

![API Settings Screenshot](images/api-settings.png)

### CLI Command Settings

If you plan to use the CLI commands feature:

1. Navigate to the "CLI Commands" tab in Settings
2. Enable CLI Commands
3. Add allowed commands to the whitelist
4. Commands must be on the whitelist to be executed

![CLI Settings Screenshot](images/cli-settings.png)

## Using the Chat Interface

The chat interface allows you to interact with the AI assistant directly from your WordPress admin.

1. Navigate to AI Assistant in your WordPress admin menu
2. Type your question or request in the chat input field
3. The AI assistant will respond with relevant information about your MemberPress site
4. Your conversation history is saved automatically

![Chat Interface Screenshot](images/chat-interface.png)

### Using Command Execution Features

The chat interface provides several ways to execute WordPress CLI commands:

1. **Command Suggestions**: When you ask about site information, the AI may suggest commands it can run for you
2. **Clickable Commands**: Any command displayed in the chat with a ▶ icon can be clicked to execute directly
3. **Run Command Button**: Use the tools icon (🔧) at the bottom of the chat to open the command runner
4. **Command Runner Interface**: Enter any allowed WP-CLI command and execute it directly

Commands will execute in real-time and display their results in the chat. Tabular data (like lists of plugins, users, or posts) will automatically be formatted as HTML tables for better readability.

### Example Queries

Try asking questions like:
- "How many active memberships do we have?"
- "What was our revenue last month?"
- "Show me the most popular membership levels"
- "Analyze our recent subscription activity"
- "Can you list all the WordPress plugins?"
- "How many users are registered on the site?"
- "Show me the active memberships"

## Using WP-CLI Commands

If you have WP-CLI installed, you can interact with the AI assistant from the command line.

### Available Commands

#### Get Insights

```bash
wp mpai insights [--prompt=<prompt>] [--format=<format>]
```

This command generates insights from your MemberPress data.

Options:
- `--prompt`: Custom prompt for the AI (default: "Analyze MemberPress data and provide key insights")
- `--format`: Output format (json or text, default: text)

Example:
```bash
wp mpai insights --prompt="What are the top selling memberships?"
```

#### Get Command Recommendations

```bash
wp mpai recommend <prompt>
```

This command suggests WP-CLI commands based on your task description.

Example:
```bash
wp mpai recommend "How do I list all transactions from last month?"
```

#### Chat with the Assistant

```bash
wp mpai chat <message>
```

This command starts a chat conversation with the AI assistant.

Example:
```bash
wp mpai chat "How many active memberships do we have?"
```

#### Run Command with AI Analysis

```bash
wp mpai run <command> [--context=<context>]
```

This command runs a WP-CLI command and provides AI analysis of the output.

Options:
- `--context`: Additional context to help the AI understand your request

Example:
```bash
wp mpai run "wp user list --role=subscriber" --context="I want to understand our member demographics"
```

## Security Considerations

### API Key Security

Your OpenAI API key is stored in the WordPress database. For additional security:

1. Consider using constant definitions in your wp-config.php file
2. Implement API key encryption using a security plugin
3. Use an API key with appropriate permissions and spending limits

### Command Execution Security

The CLI command feature is restricted to:

1. Only administrators can use this feature
2. Only pre-approved commands in the whitelist can be executed
3. Command execution is logged for security auditing

## Troubleshooting

### API Connection Issues

If you experience problems connecting to the OpenAI API:

1. Verify your API key is correct
2. Check that your server can make outbound HTTPS connections
3. Verify your OpenAI account has sufficient credits
4. Check API limits and usage in your OpenAI dashboard

### Command Execution Issues

If commands fail to execute:

1. Verify the command is on the allowed command whitelist
2. Check that WP-CLI is properly installed
3. Ensure your user has appropriate permissions
4. Check the command syntax is correct
5. Look for error messages in the browser console or WordPress debug.log
6. Try using the direct command runner interface instead of clicking commands
7. Ensure JavaScript errors are not preventing proper AJAX requests

### Table Formatting Issues

If command output isn't displaying as formatted tables:

1. Verify the command returns tabular data with tab-separated values
2. Check for JavaScript errors that might interrupt formatting
3. Ensure the response is properly recognized as a table format

### Saving Settings Issues

If settings won't save:

1. Check for any JavaScript errors in your browser console
2. Verify database permissions for the WordPress user
3. Check for plugin conflicts
4. Try deactivating other plugins temporarily

## Data Storage

The plugin stores:

1. Your OpenAI API configuration
2. Conversation history in the `mpai_conversations` table
3. Message history in the `mpai_messages` table

No data is sent to external services except:

1. Chat messages sent to OpenAI for processing
2. MemberPress summary data included in API requests for context

## FAQ

### How much does it cost to use?

The plugin itself is free, but you need an OpenAI API key which has associated costs. OpenAI charges based on token usage. Monitor your usage in the OpenAI dashboard.

### Can I use a different AI provider?

Currently, the plugin only supports OpenAI. Future versions may include support for other providers.

### Is my MemberPress data secure?

Yes. The plugin only sends summarized data to OpenAI for context. No customer personal data is shared unless specifically included in your prompts.

### How do I clear conversation history?

Currently, conversation history is maintained in the database. You can manually truncate the `mpai_conversations` and `mpai_messages` tables to clear history.

### Can I use this plugin without MemberPress?

No, this plugin is designed specifically to work with MemberPress and requires it to be installed and activated.

## Support

If you need assistance with the MemberPress AI Assistant plugin:

1. Check the documentation in the plugin's `docs` directory
2. Visit our support forum at [example.com/support](https://example.com/support)
3. Contact us at support@example.com

## Updates and Maintenance

- Keep the plugin updated for the latest features and security fixes
- Updates can be installed through the WordPress dashboard
- Major version updates may require reconfiguration

---

Thank you for using the MemberPress AI Assistant plugin! We hope it helps you better understand and manage your membership site.