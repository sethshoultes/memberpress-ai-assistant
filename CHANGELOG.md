# Changelog

## [Unreleased]

### Added
- Comprehensive error typing and catalog system with structured error codes
- Performance-optimized logging with batching and memory management
- Error log management interface integrated with System Diagnostics
- Configurable log retention settings with automated cleanup
- Detailed error catalog with resolution steps for troubleshooting
- Agent Specialization Scoring system for improved request routing 🦴
- System Information Caching for optimized performance 🦴
- Phase Two testing framework in System Diagnostics

### Fixed
- Fixed inconsistent test result display in System Diagnostics when using "Run All Phase One Tests" button
- All test results (Agent Discovery, Lazy Loading, Response Cache, and Agent Messaging) now display consistently without requiring additional clicks
- Fixed System Information Caching test button functionality in the diagnostics panel 🦴
- Implemented comprehensive real tests for the System Information Caching feature
- Added error handling and fallback mechanisms to ensure diagnostic tests work reliably

## [6.0.0] - 2024-04-01

### Added
- Blog post XML formatting implementation
- Blog post membership formatting implementation

### Changed
- Completely rewritten command system for better security and validation
- Updated logger implementation in all command system classes

### Fixed
- Console logging issue causing performance bottlenecks
- Copy icon functionality in chat interface

## [5.0.0] - 2024-03-15

### Added
- Agent system for specialized AI functionality
- Command validation for enhanced security
- Independent operation mode when MemberPress is not available
- Scooby Snack protocol for solution documentation
- Advanced console logging system with categories

### Changed
- Reorganized documentation by system components
- Improved chat interface with better tool call detection

### Fixed
- Support routing system enhancements
- Site health integration issues

## [4.0.0] - 2024-02-01

### Added
- Site health integration with AI agent prompts
- WordPress CLI executor for admin commands
- PHP executor for validated commands
- Context marker system for content tracking

### Changed
- Modularized JavaScript architecture
- Enhanced security framework for agent operations

### Fixed
- Direct AJAX handler issues with nonce validation
- Tool execution reliability improvements