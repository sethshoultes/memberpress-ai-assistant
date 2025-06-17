# Installation and Configuration Guide

## Overview

This guide provides detailed instructions for installing, configuring, and getting started with the MemberPress AI Assistant plugin. The plugin integrates powerful AI language models with your MemberPress WordPress plugin, providing intelligent insights, content analysis, and assistance with membership management.

## Requirements

Before installing the MemberPress AI Assistant, ensure your system meets the following requirements:

- WordPress 5.8 or higher
- PHP 7.4 or higher (8.0+ recommended)
- MemberPress 1.9.0+ plugin installed and activated
- OpenAI API key (primary integration)
- Anthropic API key (optional)
- WP-CLI (optional, for command-line features)

## Installation

### Standard Installation

1. Download the MemberPress AI Assistant plugin from the official source
2. Log in to your WordPress admin dashboard
3. Navigate to Plugins > Add New
4. Click the "Upload Plugin" button at the top of the page
5. Click "Choose File" and select the downloaded plugin zip file
6. Click "Install Now"
7. After installation completes, click "Activate Plugin"

### Manual Installation

1. Download the MemberPress AI Assistant plugin from the official source
2. Extract the plugin zip file
3. Upload the `memberpress-ai-assistant` folder to the `/wp-content/plugins/` directory on your server
4. Log in to your WordPress admin dashboard
5. Navigate to Plugins
6. Find "MemberPress AI Assistant" in the list and click "Activate"

### WP-CLI Installation

If you have WP-CLI installed, you can install the plugin with the following command:

```bash
wp plugin install memberpress-ai-assistant.zip --activate
```

## Configuration

After activating the plugin, you can configure various settings to customize your experience.

### Feature Configuration

1. Navigate to AI Assistant > Settings in your WordPress admin dashboard
2. In the "Feature Settings" tab, enable or disable specific features:
   - Chat Interface: Enable the AI chat interface
   - Content Generation: Enable AI-assisted content creation
   - Membership Analysis: Enable membership data analysis
3. Click "Save Changes"

### Feature Configuration

1. In the "Feature Settings" tab, enable or disable specific features:
   - Chat Interface: Enable the AI chat interface
   - WP-CLI Integration: Enable command-line features
   - Content Generation: Enable AI-assisted content creation
   - Membership Analysis: Enable membership data analysis
2. Click "Save Changes"

### Security Configuration

1. In the "Security Settings" tab, configure security options:
   - Data Retention: Configure how long chat history is retained
   - Access Controls: Configure user role permissions
   - Command Whitelist: If using CLI features, specify allowed commands
2. Click "Save Changes"

### Privacy & Data Management

The MemberPress AI Assistant is designed with privacy-first principles:

- No consent requirements needed - the plugin operates transparently
- Minimal data processing with user control over data sharing
- Automatic data cleanup and retention policies
- Full GDPR compliance with data protection measures

## Verification

After installation and configuration, verify that the plugin is working correctly:

1. Navigate to AI Assistant in the WordPress admin menu
2. You should see the chat interface
3. Type a test message like "Hello" or "What can you help me with?"
4. You should receive a response from the AI assistant

If you encounter any issues:
- Check that your API keys are entered correctly
- Ensure your selected models are available on your plan
- Check the WordPress error log for any error messages

## Troubleshooting

### Common Issues

#### API Key Issues
- **Error**: "Invalid API key"
  - **Solution**: Double-check your API key for typos or extra spaces
  - **Solution**: Ensure your API key has not expired or been revoked

#### Connection Issues
- **Error**: "Unable to connect to API"
  - **Solution**: Check your internet connection
  - **Solution**: Verify that your server can make outbound HTTPS requests
  - **Solution**: Check if your server's IP is blocked by the API provider

#### Permission Issues
- **Error**: "Permission denied"
  - **Solution**: Ensure you're logged in as an administrator
  - **Solution**: Check file permissions on the plugin directory

### Diagnostic Tools

The plugin includes built-in diagnostic tools to help troubleshoot issues:

1. Navigate to AI Assistant > Settings > Diagnostics
2. Click "Run Diagnostics"
3. The system will check:
   - API connectivity
   - WordPress configuration
   - MemberPress integration
   - File permissions
4. Review the results and follow any recommended actions

Alternatively, you can run diagnostics via WP-CLI:

```bash
wp mpai diagnostics
```

## Updating

### Standard Update

1. When an update is available, you'll see a notification in your WordPress admin dashboard
2. Navigate to Plugins
3. Find "MemberPress AI Assistant" and click "Update Now"
4. The plugin will be updated automatically

### Manual Update

1. Download the latest version of the plugin
2. Deactivate and delete the current version (your settings will be preserved)
3. Follow the installation steps above to install the new version

### WP-CLI Update

```bash
wp plugin update memberpress-ai-assistant
```

## Uninstallation

If you need to uninstall the plugin:

1. Navigate to Plugins in your WordPress admin dashboard
2. Find "MemberPress AI Assistant" and click "Deactivate"
3. Click "Delete"
4. Confirm the deletion

Note that uninstalling the plugin will remove all plugin data, including settings and chat history.

## Next Steps

After installation, you're ready to start using the MemberPress AI Assistant. Here are some recommended next steps:

1. Explore the [Chat Interface](chat-interface.md) documentation to learn how to interact with the AI assistant
2. Check out the [Membership Operations](membership-operations.md) guide to learn how to manage memberships with the assistant
3. Review the [User Integration](user-integration.md) documentation to understand how to manage user-membership relationships
4. Explore the [Agent Architecture](agent-architecture.md) to understand how the AI system works
5. Learn about the [Available Tools](available-tools.md) that the assistant can use

## Support

If you encounter any issues or have questions about the MemberPress AI Assistant:

1. Check the [Troubleshooting](#troubleshooting) section above
2. Review the full documentation in the docs directory
3. Contact MemberPress support at support@memberpress.com
4. Visit the MemberPress support forum at https://memberpress.com/support/