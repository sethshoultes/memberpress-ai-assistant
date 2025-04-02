# MemberPress AI Assistant - Testing Procedures

This document outlines the testing procedures for the MemberPress AI Assistant plugin. These tests should be performed before each release to ensure functionality and quality.

## Prerequisites

1. WordPress installation with MemberPress plugin installed and activated
2. OpenAI API key for testing
3. WP-CLI installed and configured (for CLI command testing)
4. User accounts with administrator permissions

## Testing Environment Setup

1. Install WordPress in a test environment
2. Install and activate MemberPress
3. Install and activate the MemberPress AI Assistant plugin
4. Configure the plugin with a valid OpenAI API key

## Testing Process

### 1. Installation and Activation

- [ ] Plugin installs without errors
- [ ] Plugin activates without errors
- [ ] Database tables are created correctly (`mpai_conversations` and `mpai_messages`)
- [ ] Default options are set correctly

### 2. Settings Configuration

- [ ] Navigate to AI Assistant > Settings
- [ ] Verify all settings fields are displayed correctly
- [ ] Enter a valid API key and save
- [ ] Verify settings are saved correctly
- [ ] Test with invalid API key and verify error handling

### 3. Chat Interface Testing

- [ ] Navigate to AI Assistant main page
- [ ] Verify chat interface is displayed correctly
- [ ] Send a test message and verify response
- [ ] Test with complex queries about MemberPress
- [ ] Verify conversation history is maintained
- [ ] Test error handling with API failures

### 4. OpenAI Integration

- [ ] Verify API connection with valid credentials
- [ ] Check response generation with various prompts
- [ ] Test handling of API rate limits
- [ ] Verify model settings (temperature, tokens) affect responses
- [ ] Test error handling for API timeouts and failures

### 5. MemberPress API Integration

- [ ] Verify MemberPress data is retrieved correctly
- [ ] Test with different MemberPress configurations
- [ ] Verify data summary includes memberships, transactions, etc.
- [ ] Test with large datasets to ensure performance

### 6. WP-CLI Commands

- [ ] Test `wp mpai insights` command
  - [ ] Basic command execution
  - [ ] With custom prompt parameter
  - [ ] With JSON output format
- [ ] Test `wp mpai recommend` command
  - [ ] Verify command recommendations are relevant
  - [ ] Test with various task descriptions
- [ ] Test `wp mpai chat` command
  - [ ] Verify response is generated correctly
  - [ ] Test error handling
- [ ] Test `wp mpai run` command
  - [ ] With allowed commands
  - [ ] With disallowed commands (should fail)
  - [ ] With context parameter

### 7. Command Security

- [ ] Verify command whitelist functionality
- [ ] Test with allowed commands (should execute)
- [ ] Test with disallowed commands (should fail)
- [ ] Test with command injection attempts
- [ ] Verify permissions checks are enforced

### 8. REST API Testing

- [ ] Test `/mpai/v1/chat` endpoint
  - [ ] With valid message
  - [ ] With authentication
  - [ ] Without authentication (should fail)
- [ ] Test `/mpai/v1/run-command` endpoint
  - [ ] With valid command
  - [ ] With invalid command
  - [ ] With authentication
  - [ ] Without authentication (should fail)

### 9. Database Testing

- [ ] Verify conversations are stored correctly
- [ ] Verify messages are stored correctly
- [ ] Test with large conversation history
- [ ] Check performance with many concurrent conversations

### 10. Browser Compatibility

Test in the following browsers:
- [ ] Chrome
- [ ] Firefox
- [ ] Safari
- [ ] Edge

### 11. Error Handling

- [ ] Test with invalid API key
- [ ] Test with API service down
- [ ] Test with malformed responses
- [ ] Test with timeouts
- [ ] Verify error messages are clear and helpful

### 12. Performance Testing

- [ ] Measure response time for chat completions
- [ ] Test with long conversations
- [ ] Test with multiple concurrent users
- [ ] Monitor memory usage during operation

## Automated Testing

Future implementation:

- [ ] Unit tests for API integrations
- [ ] Integration tests for database operations
- [ ] End-to-end tests for chat functionality
- [ ] WP-CLI command tests

## Regression Testing Checklist

After making changes:

- [ ] Verify all existing functionality still works
- [ ] Check for any performance degradation
- [ ] Ensure backward compatibility
- [ ] Validate settings are preserved

## Security Testing

- [ ] Verify API key is stored securely
- [ ] Check permission validation for admin functions
- [ ] Test for SQL injection vulnerabilities
- [ ] Test for XSS vulnerabilities in chat display
- [ ] Verify command execution security

## Test Reporting

When reporting test results, include:

1. Test environment details (WordPress version, PHP version, browser)
2. Steps to reproduce any issues
3. Screenshots or logs of failures
4. Expected vs. actual results

## Release Criteria

All tests must pass before a release is considered ready for production. Critical issues must be addressed and retested before release.

## Special Test Cases

### AI Response Quality

- [ ] Test various MemberPress-related questions
- [ ] Verify responses are accurate and helpful
- [ ] Check for hallucination or incorrect information
- [ ] Test complex queries that require MemberPress context

### CLI Command Integration

- [ ] Test integration with different WP-CLI commands
- [ ] Verify command output analysis is helpful
- [ ] Test with complex command outputs