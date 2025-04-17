# Tool Execution Integration Tests Procedures

This document outlines the procedures for testing the Tool Execution Integration Tests implemented as part of Phase Three of the MemberPress AI Assistant development.

## Overview

Tool Execution Integration Tests verify that MemberPress AI Assistant tools work correctly with WordPress and external systems. These tests examine end-to-end functionality including initialization, parameter handling, proper execution, and error handling.

## Running the Tests

### Method 1: System Diagnostics Page

1. Navigate to MemberPress AI → Settings
2. Click on the "Diagnostics" tab
3. Locate the "Tool Execution Integration Tests" section
4. Click "Run Tool Integration Tests" button
5. Review the results in the expandable sections

### Method 2: Integration Tests Page

1. Navigate to MemberPress AI → Integration Tests
2. The tests will automatically run and display results
3. Expand sections to view details for each tool
4. Check pass/fail indicators for each test

## Test Breakdown

### WP-CLI Tool Tests (12 tests)
- Basic tool creation and property verification
- Parameter validation tests
- Command execution with various parameters
- Error handling for invalid commands
- Caching mechanism verification
- Edge case handling (whitespace, timeout)

### WordPress API Tool Tests (12 tests)
- Tool initialization and properties
- WordPress API integration
- Post/page creation and retrieval
- User management functions
- Plugin activation/deactivation (when permissions allow)
- MemberPress integration (when MemberPress is active)

### Plugin Logs Tool Tests (12 tests)
- Tool definition and parameter structure
- Basic and advanced query execution
- Parameter combination handling
- Filtering capabilities
- Time formatting and processing
- Edge case handling (empty results, invalid parameters)

## Expected Results

- Each test should report as "Passed" with a descriptive message
- The overall test summary should show total tests run, passed, and failed
- Failure messages should indicate the specific issue that was encountered
- Any skipped tests will be clearly marked with a reason

## Troubleshooting Common Issues

### "Test failed to execute" Error
- Verify WordPress permissions (admin role required)
- Check that nonce verification is passing
- Examine PHP error logs for fatal errors

### "Tool initialization failed" Error
- Verify tool classes exist in the correct locations
- Check for PHP version compatibility issues
- Ensure WordPress environment is correctly initialized

### "Command execution failed" Error
- Check WordPress capabilities and permissions
- Verify that the command is in the allowlist
- Examine server configuration for command execution restrictions

## Adding New Tests

To add new tests to the integration test suite:

1. Create a new test file in `test/integration/tools/`
2. Define test functions following the established pattern
3. Add the test to `test-tool-execution.php`
4. Register the test in `register-integration-tests.php`

## Logging Test Results

For important test findings:

1. Document in the appropriate Scooby Snack format
2. Update CHANGELOG.md with significant test-related fixes
3. Add test case to regression test suite if needed