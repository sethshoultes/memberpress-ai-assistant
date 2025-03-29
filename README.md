# MemberPress AI Assistant

## Description

MemberPress AI Assistant integrates OpenAI's powerful language models with your MemberPress WordPress plugin, providing intelligent insights, content analysis, and WP-CLI command assistance. This plugin helps site administrators better understand their membership data and streamline site management tasks.

## Features

- **AI-Powered Chat Interface**: Ask questions about your MemberPress data and receive intelligent answers
- **Agent System**: Specialized AI agents that perform specific tasks through natural language commands
- **MemberPress Data Analysis**: Get insights about memberships, transactions, subscriptions, and more
- **WP-CLI Integration**: Run WordPress CLI commands with AI assistance
- **Command Recommendations**: Get AI-suggested commands based on your goals
- **Secure Command Execution**: Only pre-approved commands from a whitelist can be executed
- **Conversation History**: Save and retrieve previous conversations

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher (8.0+ recommended)
- MemberPress 1.9.0+ plugin installed and activated
- OpenAI API key
- WP-CLI (optional, for command-line features)

## Installation

1. Upload the `memberpress-ai-assistant` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the WordPress admin interface
3. Navigate to AI Assistant > Settings to configure your OpenAI API key and other settings
4. Start using the AI assistant from the AI Assistant menu in WordPress admin

## Configuration

### API Settings

1. Obtain an API key from OpenAI (https://platform.openai.com/api-keys)
2. Enter your API key in the plugin settings
3. Select your preferred model (e.g., gpt-4o)
4. Configure temperature and token settings as needed

### CLI Command Settings

If you want to use the WP-CLI integration:

1. Enable CLI commands in the settings
2. Add allowed commands to the whitelist
3. Only commands in the whitelist can be executed

## Usage

### Admin Chat Interface

1. Navigate to AI Assistant in the WordPress admin menu
2. Type your question in the chat interface
3. Receive AI-generated insights about your MemberPress site

### WP-CLI Commands

This plugin adds several WP-CLI commands:

```bash
# Generate insights from MemberPress data
wp mpai insights [--prompt=<prompt>] [--format=<format>]

# Get command recommendations
wp mpai recommend <prompt>

# Chat with the AI assistant
wp mpai chat <message>

# Run a command with AI analysis
wp mpai run <command> [--context=<context>]
```

## Documentation

For detailed information, please check these documentation files:

- [Project Specification](docs/project-specification.md) - Complete project overview and technical specifications
- [User Guide](docs/user-guide.md) - Complete guide for users
- [Developer Guide](docs/developer-guide.md) - Information for developers who want to extend the plugin
- [Agent System Specification](docs/agent-system-spec.md) - Detailed specification of the agent system
- [Agent System Implementation](docs/agent-system-implementation.md) - Implementation details for the agent system
- [Agent System Quick Start](docs/agent-system-quickstart.md) - Get started with the agent system quickly
- [Testing Procedures](tests/test-procedures.md) - Procedures for testing the plugin

## Agent System

The MemberPress AI Assistant includes an advanced agent system with specialized AI assistants:

1. **Content Agent**: Create and manage website content
2. **System Agent**: Manage WordPress updates, plugins, and settings
3. **Security Agent**: Monitor and enhance site security 
4. **Analytics Agent**: Generate insights about your membership site
5. **MemberPress Agent**: Handle MemberPress-specific tasks and configurations

For more information, see the [Agent System Specification](docs/agent-system-spec.md).

## Security

This plugin takes security seriously:

- Only administrators can access the plugin features
- OpenAI API keys are stored securely
- WP-CLI commands can only be executed if they're on a pre-approved whitelist
- All user inputs are properly sanitized and validated

## Development Resources

Refer to the [CLAUDE.md](../CLAUDE.md) file for development guidelines and coding standards.

## Support

For support, please use the GitHub issue tracker or contact us at [support@example.com](mailto:support@example.com).

## License

This plugin is licensed under the GPL v2 or later.

## Credits

- Developed by [Your Name]
- OpenAI integration for language model capabilities
- Built to enhance the MemberPress plugin experience

---

MemberPress AI Assistant is not officially affiliated with MemberPress or OpenAI.