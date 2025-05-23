# Changelog

All notable changes to the MemberPress AI Assistant plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- New AI Plugin Documentation feature:
  - Added AI-powered documentation generation for plugin developers
  - Implemented pattern detection for best documentation practices
  - Created automated code documentation suggestions
  - Added support for both inline and standalone documentation
  
### Changed
- Enhanced error handling system with improved logging:
  - Added detailed context tracking for better debugging
  - Implemented hierarchical error categories

### Fixed
- Fixed issue with membership creation parameters not being correctly processed:
  - Added robust type handling for price parameters
  - Enhanced detection of membership creation tool calls
  - Improved logging for parameter validation
  - Fixed parameter type conversion to ensure proper data format
- Fixed inconsistent behavior in API fallback mechanism
- Fixed style conflicts with WordPress 6.4 admin interface
- Fixed MemberPress integration with direct core class approach:
  - Created new MPAI_MemberPress_Service class for direct integration with MemberPress core
  - Replaced API-based approach with direct class interaction
  - Enhanced WP-CLI executor to better handle MemberPress commands
  - Improved parameter parsing for various command formats
  - Added support for multiple command styles and parameter formats
  - Improved logging and error handling for MemberPress operations
  - Created comprehensive documentation of the integration fix
- Fixed membership creation parameter handling ensuring name and price are properly passed:
  - Enhanced tool call detection with improved parameter extraction 
  - Added comprehensive logging throughout parameter flow
  - Implemented parameter validation at each step to ensure proper data transmission
  - Improved error handling for malformed tool calls
  - Enhanced JSON processing to handle all parameter formats correctly
  - Fixed Context Manager to properly handle membership parameters
  - Updated MemberPress Service with robust parameter processing
  - Implemented comprehensive parameter handling system with strict validation:
    - Added client-side validation to prevent creating memberships with default values
    - Implemented server-side validation with detailed error messages
    - Enhanced parameter tracing throughout the system for better debugging
    - Eliminated all default fallback values to force explicit parameter passing
    - Added support for various tool call formats (JSON, XML, function calls)
    - Improved user feedback with validation error display in chat interface
    - Created detailed documentation in docs/feature-plans/membership-creation-parameter-handling.md

## [1.6.1] - 2025-04-14

### Fixed
- Fixed WP-CLI command handling and type safety issues:
  - Added robust error handling in MPAI_Command_Handler with fallbacks for component initialization
  - Fixed type safety in MPAI_WP_CLI_Executor output formatting
  - Enhanced WP-CLI Tool wrapper with better error logging
  - Added direct plugin list fallback method in MPAI_Chat
  - Improved special case handling for wp plugin list command with multiple execution paths
  - Fixed uninitialized logger in MPAI_Command_Detector
  - Changed security approach from restrictive whitelist to permissive blacklist with dangerous pattern detection
  - Added tool name mapping for backward compatibility (wp_cli → wpcli)
  - Updated tool registry with dual registration for both name formats
  - Enhanced JavaScript frontend to handle both tool name formats
  - Improved command adapter to support different return formats
  - Fixed parsing of tool responses with enhanced type checking

## [1.6.0] - 2025-04-15

### Added
- Enhanced Diagnostics Page with Plugin Management tab:
  - Added a new Plugin Management category in the diagnostics page
  - Implemented Active Plugins test to display all active/inactive plugins
  - Added Plugin History test to show plugin installation, activation, and update history
  - Created visual formatting with HTML tables and CSS styling
  - Added statistics summary for plugin activity

### Changed
- Implemented Phase 2 of Admin UI Overhaul - Settings Page Improvements:
  - Completely rewrote settings page implementation with standard WordPress Settings API
  - Replaced complex custom Settings Registry with simplified approach
  - Implemented tab-based navigation with clean URL parameters
  - Added proper settings sections and fields organization
  - Improved form submission handling with standard options.php approach
  - Enhanced settings fields with proper descriptions and sanitization
  - Fixed settings page JavaScript issues for smoother tab transitions

### Fixed
- Fixed "not in the allowed options list" error in settings page
- Fixed "link expired" error when saving settings
- Fixed Anthropic API test method name
- Fixed Error Recovery System logger method access issue

## [1.5.5] - 2025-04-11

### Added
- New standalone Diagnostics page with improved UI and test organization
- Comprehensive system information display in diagnostics
- Category-based test navigation for better organization
- Test results summary with pass/warning/fail counts
- Improved test detail display with warnings and critical issues

### Changed
- Diagnostics system completely redesigned as a standalone page
- Tests organized by category: Core System, API Connections, AI Tools, Integration Tests
- Improved UI styling for better user experience

### Fixed
- Enhanced Error Recovery System tests with comprehensive test suite:
  - Added test cases for error creation with context and severity
  - Added test cases for retry mechanism functionality
  - Added test cases for fallback strategies implementation
  - Added test cases for circuit breaker pattern for service protection
  - Added test cases for error formatting for user-friendly display
- Fixed diagnostics test failures in API connections and tools:
  - Fixed OpenAI API test by using the correct generate_chat_completion() method instead of non-existent complete() method
  - Fixed Anthropic API test with similar correction to use the proper method
  - Fixed WP-CLI tool test by using get_available_tools() instead of non-existent get_all_tools() method
  - Fixed Plugin Logs tool test with the same correction for proper tool registry access
  - Improved tool registration checking for all tool tests
- Fixed nonce test in Debug tab by updating the AJAX handler to use direct-ajax-handler.php with correct parameters
- Improved Diagnostics page with additional tests for AJAX Communication, Nonce Verification, and System Cache
- Simplified Debug tab by removing redundant diagnostic tools in favor of the dedicated Diagnostics page

### Added
- Implemented Phase 1 of Admin UI Overhaul plan:
  - Created new MPAI_Admin_Menu class for centralized menu registration
  - Implemented MPAI_Settings_Manager with proper tab navigation
  - Added MPAI_Diagnostics_Page for improved diagnostics experience
  - Created consistent admin UI styling with admin-ui.css
  - Added diagnostics.js for enhanced admin interactions
  - Implemented comprehensive menu highlighting solution that doesn't rely on JavaScript
  - Created server-side tab navigation for improved state persistence
  - Added dedicated settings group system for better organization
  - Enhanced diagnostics page with system information display
  - Created diagnostic test framework with AJAX-based test execution
  - Added tool registration system for better extensibility
- Completed Phase 3, Week 9 of documentation improvement plan:
  - Created comprehensive performance optimization guide with detailed strategies
  - Archived all old documentation to the archive directory
  - Updated documentation structure and organization
  - Updated all references to point to archived content

### Fixed
- Fixed duplicate sections in diagnostic tab by properly enforcing new diagnostic system 🦴
  - Identified duplication caused by fallback code in settings-page.php
  - Removed 800+ lines of legacy diagnostic functionality
  - Ensured only the modern diagnostic system (MPAI_Diagnostics) is used
  - Eliminated all duplicate UI sections while preserving functionality
  - Reduced code complexity and improved maintainability
  - Created comprehensive documentation in archive/DIAGNOSTIC_TAB_DUPLICATE_FIX.md

### Changed
- Updated roadmap documentation with accurate implementation status:
  - Added Phase 3.5 to prioritize essential features that need completion
  - Updated _1_agent-system-enhancement-plan.md with more accurate task scheduling
  - Updated _2_performance-optimization-plan.md with implementation status indicators
  - Created comprehensive roadmap index with feature categorization and priority ordering
  - Moved implemented blog-post-formatting-plan.md to archive directory
  - Added implementation status indicators (✅, 🚧, 🔮) across all roadmap documents
- Enhanced documentation structure and organization:
  - Created master documentation index at /docs/index.md
  - Moved DOCUMENTATION_PLAN.md to archive now that all phases are complete
  - Updated documentation with Final Documentation Structure reflecting system-based organization
  - Added more detailed Entry Points for specific development tasks
  - Enhanced links between documentation files for better navigation
  - Added Recent Major Documentation Updates section to index.md
  - Moved and renamed _1_AGENTIC_SYSTEMS_.md to /docs/current/agent-system/comprehensive-agent-system-guide.md
  - Updated all references to point to the new location
  - Created redirect notice in the old file location
- Reorganized documentation with comprehensive archiving:
  - Moved _snacks directory to archive/_snacks with all investigation results
  - Moved xml-content-system directory to archive/xml-content-system
  - Created detailed archive README with explanation of archived content
  - Updated all links in documentation to point to archived locations
  - Updated documentation metadata with current dates and version numbers

## [1.6.1] - 2025-04-05

### Fixed
- WordPress API Tool parameter validation to handle missing or empty plugin parameters 🦴
  - Added enhanced error handling with debug backtracing for troubleshooting
  - Improved integration test for plugin activation/deactivation with better fallbacks
  - Implemented validation for empty parameters, not just undefined ones
  - Created comprehensive documentation in docs/SCOOBY_SNACK_WP_API_TOOL_FIX.md
- Fixed clickable WordPress command links in chat interface 🦴
  - Enhanced the runnable command CSS styling for better visibility
  - Improved click handler with visual feedback and delay
  - Added distinct styling to make commands appear as clickable buttons
  - Made the 'run' indicator more visible with blue color
- Fixed missing clickable suggestion links in dashboard 🦴
  - Changed plain text suggestions to clickable links with mpai-suggestion class
  - Added styling to make the suggestions appear as clickable links
  - Implemented JavaScript to handle clicks on suggestions and send to chat
  - Ensured the chat opens when clicking on a suggestion if it's not already open

### Added
- Completed Phase Three: Stability & Testing Enhancement with comprehensive improvements:
  - Implemented comprehensive Error Recovery System for improved robustness:
    - Standardized error types and severity levels for consistent handling
    - Added rich error context with enhanced debug information 
    - Implemented recovery strategies with retry and fallback capabilities
    - Added circuit breaker pattern to prevent repeated failures
    - Created user-friendly error message formatting for end users
    - Component-specific error creation for APIs, tools, and agents
    - Integrated with API Router for robust API fallback between providers
    - Enhanced Context Manager with error recovery for tool execution
    - Added Agent Orchestrator integration for agent-related errors
    - Created test suite for Error Recovery System validation
    - Added detailed documentation in error-recovery-system.md
  - Implemented State Validation System for system state consistency:
    - Created system invariant verification for core components
    - Added pre/post condition framework for operation validation
    - Implemented component state monitoring with consistency checks
    - Added validation rules for API clients, routers, and tool registry
    - Created assertion framework for state verification
    - Added integration with Error Recovery System for validation failures
    - Created comprehensive test suite with 15 validation tests
    - Added detailed documentation in state-validation-system.md
  - Implemented Tool Execution Integration Tests for Phase Three: ✅
    - Created end-to-end test suite for WP-CLI Tool, WordPress API Tool, and Plugin Logs Tool
    - Added parameterized test framework with 30+ tests across all tools
    - Implemented comprehensive error handling and recovery testing
    - Added integration with System Diagnostics page for admin visibility
    - Created dedicated integration tests admin page
    - Added detailed documentation in tool-execution-integration-tests.md
    - Added extension hook (mpai_run_diagnostics) to System Diagnostics page
    - Created complete test-system documentation structure
  - Implemented Error Catalog System for standardized error management:
    - Created comprehensive error categorization system
    - Added detailed error codes with contextual information
    - Implemented error catalog lookup for consistent messaging
    - Added debug mode with enhanced error details
    - Created fallback error handling for unknown error types
    - Added integration with logging system for better error tracking
    - Created detailed documentation in error-catalog-system.md
  - Implemented Input Sanitization Improvements for enhanced security and reliability:
    - Created centralized MPAI_Input_Validator class for consistent validation and sanitization
    - Integrated validator with base tool class for automatic parameter validation
    - Implemented schema-based validation compatible with OpenAI/Anthropic function calling
    - Added comprehensive validation rules for all data types (string, number, boolean, array, object)
    - Enhanced error reporting with detailed validation failure messages
    - Created sanitization methods for all data types with security-focused cleaning
    - Added default value support for optional parameters
    - Integrated with Tool Registry for consistent parameter validation across all tools
    - Implemented comprehensive test suite for validation and sanitization
    - Created detailed documentation in input-sanitization-improvements.md
  - Implemented Edge Case Test Suite for boundary condition testing:
    - Created comprehensive test suite for extreme input values
    - Added tests for resource limit conditions
    - Implemented validation failure testing
    - Created defensive programming validation tests
    - Added detailed documentation with test cases and expected results
    - Integrated with System Diagnostics page for admin visibility
    - Created detailed documentation in edge-case-test-suite.md

## [Unreleased]

### Fixed
- Fixed Error Recovery System test 500 error by correcting dependency loading and initialization 🦴
  - Added explicit dependency management for Plugin Logger and Error Recovery System
  - Provided fallback function definitions for `mpai_init_plugin_logger()` and `mpai_init_error_recovery()`
  - Enhanced error reporting and exception handling in test scripts
  - Created alternative testing paths including direct-access test script
  - Added comprehensive debugging information to test results
  - Created detailed documentation in error-recovery-system-fix.md
- Fixed System Information Caching test failure by correcting method name discrepancies between test and implementation 🦴
  - Changed `persist_to_filesystem()` call to use the existing `set()` method which handles filesystem persistence internally
  - Replaced `load_from_filesystem()` with the correct `maybe_load_filesystem_cache()` method
  - Documented remaining issues with cache expiration and invalidation tests
  - Created comprehensive investigation document in Scooby Snacks system

### Added
- Scooby Mode investigation protocol for systematic troubleshooting: 🦴
  - Added triggers to activate dedicated investigation mode
  - Created comprehensive documentation with templates
  - Implemented dedicated investigations directory in Scooby Snacks
  - Enhanced documentation structure for investigation results
  - Added Investigation section to Scooby Snacks index
- Phase Two Agent System and Performance Enhancements: 🦴
  - Agent Specialization Scoring system with weighted confidence scoring for improved request routing
  - System Information Caching for PHP, WordPress, and plugin information with 70-80% performance improvement
  - Enhanced diagnostic panel with Phase Two test framework
  - Comprehensive documentation for all Phase Two features
- XML Content System with structured documentation:
  - Created comprehensive index for XML Content System documentation
  - Added code examples for XML content creation
  - Organized example XML files with clear categorization
- Testing System Organization:
  - Comprehensive documentation structure for all test files
  - Categorized index of test files by feature and purpose
  - Detailed guides for running and creating tests
- Documentation System Enhancements:
  - Comprehensive developer onboarding system with _0_START_HERE_.md and tool implementation map
  - Detailed documentation of tool development workflow and integration points
  - Documentation consolidation and navigation improvements:
    - Added implementation status tracking with consistent indicators
    - Created documentation map for better navigation
    - Added agent system reference pointing to comprehensive documentation
    - Archived superseded documentation with clear notices
    - Organized Scooby Snacks by category with improved indexing

### Changed
- Enhanced documentation structure with new tool implementation guide
- Improved developer experience with centralized entry point documentation
- Reorganized agent system documentation with root-level comprehensive guide
- Optimized WP-CLI Tool with caching integration
- Enhanced diagnostic panel with more comprehensive testing capabilities

## [1.5.8] - 2025-04-02

### Added
- Independent operation mode allowing the plugin to work without MemberPress:
  - Smart menu placement: top-level when MemberPress is absent, submenu when present
  - Graceful degradation of MemberPress-specific API features with informative messages
  - MemberPress upsell integration with strategically placed promotional elements
  - Enhanced API class with comprehensive availability checks 
  - Added fallback responses for all MemberPress-specific features
  - Detailed implementation documentation in MEMBERPRESS_INDEPENDENT_OPERATION.md

### Fixed
- Fixed "wp plugin list" command functionality with proper error handling:
  - Created missing JavaScript modules for better separation of concerns:
    - mpai-chat-tools.js - Handles tool execution and formatting
    - mpai-chat-formatters.js - Manages message formatting 
    - mpai-chat-history.js - Handles chat history operations
  - Fixed response format in MPAI_Chat to use 'message' instead of 'response' key
  - Improved error handling in Context Manager and WP CLI Tool
  - Enhanced JavaScript response format parsing to handle multiple formats
  - Fixed code block detection and tabular data extraction in chat interface
  - Added extensive logging for better troubleshooting
- Copy icon functionality in chat interface now correctly copies message content to clipboard
- Enhanced clipboard operations with modern Clipboard API and fallback for older browsers
- Fixed JavaScript modularization issues with chat interface functionality
- Fixed console logging in browser console with comprehensive solution: 🦴 Scooby Snack
  - Fixed bug where "Enable Console Logging" checkbox wasn't stopping console logs
  - Removed interval timer that was continuously logging regardless of settings
  - Fixed console logger initialization to properly handle settings
  - Properly initialized logger with user settings instead of forced debug settings
  - Implemented proper type conversion between string and boolean values for settings
  - Added direct checkbox handler to update logger state immediately when changed
  - Created comprehensive documentation of the solution in docs/console-logging-issues-fix.md

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