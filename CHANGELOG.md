# Changelog

All notable changes to the MemberPress AI Assistant plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.5.0] - 2025-03-30

### Added
- Added direct database access for MemberPress data to reduce dependencies
- Created comprehensive documentation index in docs folder
- Implemented project organization improvements
- Created archive system for test and deprecated files

### Changed
- Updated README.md with current features and organization
- Reorganized test files into archive directory
- Updated MemberPress API class to prioritize direct database access
- Removed dependency on MemberPress Developer Tools
- Improved diagnostic tool to work without Developer Tools

### Fixed
- Fixed fatal error in diagnostic tool accessing undeclared static property
- Fixed Anthropic API auto-testing in settings page
- Enhanced fallback methods for member data retrieval when API fails
- Improved direct AJAX handler for MemberPress tests

## [1.4.1] - 2025-03-30

### Added
- Added new `new_members_this_month` type to memberpress_info tool for direct access to new member information
- Added formatted output for member reports
- Enhanced MemberPress API with more user-friendly data formatting
- Improved system prompt to guide the AI to use specific member data tools

### Fixed
- Fixed AI response to user questions about new member counts
- Improved tabular data formatting for member information
- Enhanced fallback methods for member data retrieval when API fails

## [1.4.0] - 2025-03-30

### Added
- Implemented dual API support with both OpenAI and Anthropic Claude
- Created API Router to manage requests between multiple AI providers
- Added Anthropic integration class for Claude API
- Enhanced Settings page with Anthropic API configuration options
- Added API fallback mechanism for improved reliability
- Created direct test tool for Anthropic Claude API
- Implemented API Provider selection for primary and fallback preferences
- Added compatibility with Claude's function calling format
- Added support for Claude 3 Opus, Sonnet, and Haiku models
- Improved tool management with dynamic tool registration and routing
- Created structured tool calls handling for both API formats
- Added WordPress API Tool for browser-based environments
- Implemented WP-CLI fallback mechanism when CLI isn't available
- Added pattern matching for common WP-CLI commands with WordPress API equivalents
- Added helpful error messages with alternative tool usage examples
- Added smart detection and handling of WP-CLI fallback scenarios
- Implemented enhanced system prompting to prefer wp_api tool in browser contexts
- Created user-friendly formatting for wp_api results (posts, pages)
- Improved command detection for clearer tool usage suggestions

### Changed
- Refactored Chat class to use the new API Router
- Updated system messages handling for compatibility with both APIs
- Enhanced error handling with API-specific debugging information
- Improved settings UI with dedicated sections for each API provider
- Restructured default settings to support multiple API configurations
- Added API attribution in the chat interface
- Enhanced Context Manager to support multiple execution paths
- Improved system prompt to prioritize wp_api tool over wp_cli in browser contexts
- Added context-aware messaging to guide AI in tool selection
- Added automatic WP-CLI fallback detection and system guidance
- Enhanced wp_api result formatting for more readable output
- Added specific examples in system prompts to ensure correct parameter passing
- Improved JSON result parsing and display for better readability
- Added clear instructions to preserve user-specified titles and content

### Fixed
- Fixed issues with tool calling formats between different APIs
- Enhanced error handling for API-specific error messages
- Improved response processing for different API response structures
- Fixed message formatting with proper context passing between APIs
- Fixed post creation in browser environments without WP-CLI
- Resolved "WP-CLI not available" errors with proper fallback mechanisms
- Fixed tool execution in browser contexts using WordPress native API functions
- Improved AI response to WP-CLI unavailability messages
- Fixed AI not adapting to use wp_api after seeing WP-CLI unavailability error
- Added adaptive conversation management to handle tool fallback scenarios
- Fixed post creation with missing title and content values
- Fixed post creation using default values instead of user-specified values
- Fixed wp_api tool results displaying as raw JSON instead of user-friendly format
- Improved detection of wp_cli commands that should use wp_api instead

## [1.3.0] - 2025-03-29

### Added
- Enhanced command output formatting for better readability
- Automatic table detection and HTML table formatting for tabular command results
- Improved error handling for command execution
- Added support for additional WP-CLI commands (including 'wp option get')
- Direct command execution from chat interface with improved UI
- Added JSON response formatting system for structured command outputs
- Implemented multi-agent approach for data processing and display:
  - First agent (PHP backend): Formats and structures tabular data
  - Second agent (JavaScript frontend): Processes and displays formatted data
- Specialized formatters for MemberPress data (memberships and transactions)
- Command-specific titles and styling for "wp user list", "wp post list" and other tabular outputs
- Distinct table formatting for plugin lists, user lists, and membership data
- Intelligent command type detection for optimized display formatting

### Fixed
- Resolved AJAX errors in command execution
- Fixed issues with nonce validation in tool execution
- Improved JSON parsing robustness in request handling
- Enhanced error reporting with detailed failure information
- Added fallback execution paths for commands
- Fixed inconsistent JSON outputs in command results
- Improved handling of MemberPress-specific command output
- Resolved JSON double-encoding issue causing raw JSON to display in chat
- Fixed parsing of pre-formatted JSON responses for seamless display
- Implemented proper object handling to prevent string conversion of JSON data
- Fixed tabular data formatting for simulated WP-CLI commands (wp user list, wp post list, wp plugin list)
- Fixed double-encoding of JSON responses in tool execution and chat processing
- Enhanced JSON detection and unwrapping logic to prevent raw JSON display
- Fixed handling of escaped tab and newline characters in tabular data formatting
- Added intelligent splitting for tabular data with mixed separator formats
- Fixed direct rendering of pre-formatted JSON command outputs
- Added special handling for WP CLI command result tables

### Changed
- Refactored command execution to use a more direct approach
- Improved debugging information for easier troubleshooting
- Enhanced UI feedback during command execution
- Added striped row styling for improved table readability
- Updated command response handling to detect and format JSON data
- Enhanced table UI with better headers, spacing, and responsive design
- Improved detection of tabular data in command outputs
- Enhanced JSON processing with support for multiple formats
- Optimized data flow between backend and frontend for better performance
- Improved logging to facilitate easier troubleshooting of data formatting issues

## [1.2.0] - 2025-03-29

### Added
- Implemented Agent System with modular architecture
- Created MemberPress Agent for specialized MemberPress operations
- Developed Tool Registry and WP-CLI Tool for command execution
- Added test utilities for agent system verification
- Enhanced CLI commands to use the agent system:
  - `wp mpai process` for natural language request processing
  - `wp mpai mepr` for direct MemberPress command execution
- Updated existing CLI commands to leverage agent system when available
- Created comprehensive project specification

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