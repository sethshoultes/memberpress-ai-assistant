# Changelog

All notable changes to the MemberPress AI Assistant plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2025-03-28

### Added
- Floating chat bubble interface adapted from AccessAlly AI Assistant
- Debug tab in settings page with dedicated testing tools
- Database tables creation during plugin activation
- Comprehensive agent system specification and documentation
- Agent orchestrator architecture design
- Five specialized agents specification: Content, System, Security, Analytics, and MemberPress
- Tool integration layer for WordPress functionality
- Memory and context management system

### Fixed
- Chat history persistence between page visits
- JavaScript errors in chat interface:
  - "Cannot read properties of undefined (reading 'trim')"
  - "content.replace is not a function"
- Improved error handling and logging in chat history functions
- Enhanced user experience with better UI feedback for test functions

### Changed
- Moved debug buttons from settings header to dedicated Debug tab
- Updated JavaScript handlers to display test results inline
- Enhanced settings UI with consistent styling

## [1.0.0] - 2025-03-28

### Added
- Initial release of MemberPress AI Assistant
- AI-powered chat interface for MemberPress data analysis
- Integration with OpenAI API
- MemberPress data retrieval and formatting
- Conversation history storage in database
- Admin settings page for configuration
- WP-CLI integration with command execution
- Command recommendation system
- Model Context Protocol (MCP) implementation for commands
- Documentation: User Guide, Developer Guide, and Testing Procedures

### Security
- Secure storage of API keys
- Command whitelist for CLI commands
- Permission checks for admin functions
- Input sanitization and validation
- Authentication for REST API endpoints