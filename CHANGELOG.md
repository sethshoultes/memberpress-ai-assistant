# Changelog

All notable changes to the MemberPress AI Assistant plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.5.7] - 2025-04-02

### Added
- Implemented best-selling membership feature:
  - Added get_best_selling_membership method to MPAI_MemberPress_API class
  - Enhanced Context Manager with best_selling type in memberpress_info tool
  - Added detailed sales data with product information, ranks, and prices
  - Implemented fallback data generation when transaction data is unavailable
  - Created comprehensive test script for best-selling membership feature
- Updated support routing system with Docsbot integration:
  - Added Docsbot connector for documentation-based answers
  - Implemented multi-tier support system (AI → Docsbot → Human Support)
  - Created support detection system to identify when to escalate
  - Added tool definitions for documentation queries and support routing
  - Designed intelligent escalation criteria with confidence thresholds
- Reorganized documentation structure:
  - Created organized directory system (current, roadmap, archive)
  - Added documentation status tracking with metadata
  - Created feature documentation template for consistency
  - Archived outdated documentation
  - Added comprehensive documentation indexes

## [1.5.5] - 2025-03-31

### Added
- Implemented WordPress Site Health API integration for comprehensive system diagnostics:
  - Created MPAI_Site_Health class as a wrapper for WordPress Site Health API
  - Added detailed MemberPress-specific diagnostics to Site Health data
  - Enhanced Diagnostic Tool with Site Health integration
  - Added site_health test type to diagnostic page
  - Updated memberpress_info tool to include system_info option
  - Added include_system_info parameter to combine MemberPress and system data
  - Created comprehensive system information retrieval capabilities
  - Added fallback methods for environments without Site Health API (WP < 5.2)
  - Enhanced system prompt with instructions for accessing system information
  - Updated tool usage messages with system information examples

### Changed
- Improved diagnostic capabilities with more comprehensive system information
- Enhanced troubleshooting capabilities with detailed WordPress environment data
- Updated context manager to support system information in memberpress_info tool
- Added system_info type to memberpress_info tool parameter options
- Improved system prompt with guidance on accessing system information

## [1.5.6] - 2025-04-01

### Fixed
- Fixed duplicate membership creation issue:
  - Implemented tool call fingerprinting to identify and track previously executed tools
  - Added a global Set to store processed tool calls and prevent redundant execution
  - Enhanced executeToolCall function with duplicate detection and visual feedback
  - Improved UI to show "Skipped (duplicate)" status for duplicate tool executions
  - Added detailed logging of duplicate tool call prevention
  - Fixed issue where the same tool call was being detected and executed multiple times
  - Created comprehensive documentation of the deduplication system

## [1.5.4] - 2025-03-31

### Fixed
- Fixed plugin activation and deactivation issues:
  - Enhanced plugin path matching with improved logging
  - Added robust error handling for plugin.php loading
  - Implemented multi-tier plugin name matching for MemberPress plugins
  - Added word-by-word matching for better plugin name recognition
  - Enhanced logging throughout plugin activation process
  - Improved fallback methods for plugin identification
  - Fixed special case handling for MemberPress addon plugins
  - Added enhanced debugging for plugin path resolution
  - Implemented directory scanning fallback when get_plugins() fails
  - Fixed plugin name to path conversion with fuzzy matching
  - Resilient error handling with descriptive error messages
  - Fixed 500 errors during plugin activation with comprehensive catch blocks
- Enhanced conversation reset functionality to clear cached state:
  - Completely rewrote reset_conversation method with comprehensive state clearing
  - Added reset_context method to context manager to clear cached tools and settings
  - Added reset_state method to API Router to refresh API connections
  - Updated JavaScript UI to immediately clear visible chat on reset
  - Improved error handling and logging throughout the reset process
  - Fixed issue where context/conversation history was persisting between resets
- Fixed issues with AJAX tool execution and stale data:
  - Enhanced nonce validation in run_command and execute_tool methods
  - Added flexible nonce verification to handle different client formats
  - Modified MemberPress API to support force-refreshing data
  - Added plugin cache clearing in multiple locations to ensure fresh data
  - Updated get_available_plugins method to support forced refresh
  - Improved system prompt generation with fresh data every time
  - Fixed 400 Bad Request errors during tool execution
  - Added fast path for wp_user_list to avoid dependency issues
  - Removed problematic direct handling in admin AJAX controller
  - Fixed error with logger dependency when processing tool calls
  
## [1.5.3] - 2025-03-31

### Added
- Implemented successful content marker system for blog post publishing:
  - Added HTML comment-based markers with timestamps
  - Created comprehensive documentation in CONTENT_MARKER_SYSTEM.md
  - Added automatic content type detection and tagging
  - Implemented three-tiered content retrieval strategy with fallbacks
  - Added logging for marker detection and content extraction

### Fixed
- Solved blog post publishing issues with marker-based content identification
- Fixed content loss when publishing posts with marker-based retrieval
- Enhanced post-type detection for better content extraction
- Added defensive programming to support older versions without markers
- Fixed pattern matching for more reliable content extraction

## [1.5.2] - 2025-03-31

### Added
- Enhanced tool call detection system with additional patterns:
  - Added support for indented JSON code blocks
  - Added detection for multi-line JSON without code blocks
  - Added support for JSON with single quotes
  - Added compatibility with two-backtick code blocks
- Implemented comprehensive tool call testing utility:
  - Added browser console test function `testToolCallDetection()`
  - Enhanced diagnostic reporting for pattern matching
  - Added detailed test reporting for different tool call formats
- Created detailed testing documentation:
  - Added new test file for logging and tool call detection testing
  - Documented test cases for all supported formats
  - Created sample responses for testing and debugging
  - Added reporting guidelines for tool call detection issues
- Added content marker system for reliable content identification:
  - Implemented automatic tagging of blog posts with unique markers
  - Created message type identification for blog posts, pages, and memberships
  - Added find_message_with_content_marker method to locate specific content types
  - Implemented prioritized content retrieval logic with multiple fallbacks
  - Added HTML comment-based markers with timestamps for content identification

### Fixed
- Significantly improved tool call detection reliability:
  - Enhanced pattern testing diagnostics
  - Added individual pattern test output
  - Improved error reporting for malformed tool calls
- Fixed blog post content extraction issues:
  - Added more reliable blog post content identification
  - Implemented smart content type detection for blog posts, pages, and memberships
  - Fixed incorrect post content by using marker-based message identification
  - Improved title and content extraction reliability
  - Fixed pattern matching for edge cases
  - Added code block extraction for better debugging

## [1.5.1] - 2025-03-31

### Fixed
- Fixed PHP fatal error in command validation agent by replacing stdClass logger with anonymous class
- Fixed missing blog post content when using AI to create and publish posts
- Enhanced content extraction from AI assistant messages with better pattern matching
- Added additional fallback mechanisms for blog post creation when content is missing
- Improved title extraction from multiple message formats
- Enhanced error handling with try/catch blocks to prevent 500 errors
- Added comprehensive error logging for blog post publishing process
- Extended validation bypass to include more post-related actions
- Implemented additional checks for chat instance method existence
- Fixed nested parameters structure handling for more reliable post creation
- Added defensive programming to prevent PHP fatal errors during content extraction
- Added get_previous_assistant_message method to correctly extract blog post content
- Fixed incorrect post content by using previous AI message instead of latest message
- Improved reliability of extracting content by prioritizing the most relevant message
- Added better message selection logic for post content extraction

## [1.5.0] - 2025-03-30

### Added
- Added direct database access for MemberPress data to reduce dependencies
- Created comprehensive documentation index in docs folder
- Implemented project organization improvements
- Created archive system for test and deprecated files
- Added plugin management capabilities to WP API tool:
  - List installed plugins with get_plugins action
  - Activate plugins with activate_plugin action
  - Deactivate plugins with deactivate_plugin action
- Enhanced WP-CLI fallback mechanisms for browser environments
- Extended command validation agent with support for themes, blocks, and patterns:
  - Added theme validation for theme activation/update commands
  - Implemented block validation for block-related commands
  - Added pattern support for pattern-related operations
  - Created test script for validation functionality
- Implemented comprehensive console logging system for improved debugging:
  - Log levels: error, warning, info, debug
  - Categories: API calls, tool usage, agent activity, timing
  - Performance timing functionality
  - Configuration via WordPress settings
  - Diagnostic testing interface

### Changed
- Updated README.md with current features and organization
- Reorganized test files into archive directory
- Updated MemberPress API class to prioritize direct database access
- Removed dependency on MemberPress Developer Tools
- Improved diagnostic tool to work without Developer Tools
- Enhanced WP API tool with detailed tool definition for AI function calling

### Fixed
- Fixed fatal error in diagnostic tool accessing undeclared static property
- Fixed Anthropic API auto-testing in settings page
- Enhanced fallback methods for member data retrieval when API fails
- Improved direct AJAX handler for MemberPress tests
- Fixed issue with plugin activation in browser environments without WP-CLI
- Fixed fatal error in command validation agent causing 500 error with memberpress_info tool
- Enhanced validation system to bypass validation for MemberPress tools
- Fixed 500 error when publishing blog posts or pages through AI assistant
- Added validation bypass for wp_api post creation and editing operations
- Fixed issue with blog post content not being passed correctly when publishing posts
- Added intelligent content extraction from assistant messages for post publishing
- Enhanced context manager with chat instance reference for better content extraction
- Improved debugging for missing blog post content issues
- Fixed critical bug in execute_wp_api method where parameters were being lost
- Enhanced tool call detection in chat interface for more reliable execution:
  - Improved regex patterns to handle multiple tool call formats
  - Added comprehensive pattern matching for different code block styles
  - Improved detection of JSON-formatted tool calls in text
  - Enhanced logging and debugging for tool call processing
  - Added pattern usage statistics for tool call execution
  - Fixed issue with tool calls showing "0 tool calls" in logging
  - Added detailed diagnostics for response content when no tool calls detected

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