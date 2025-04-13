# MemberPress AI Assistant Diagnostics System

**Version:** 1.6.0  
**Last Updated:** 2025-04-13  
**Status:** ✅ Maintained

## Overview

The Diagnostics System is a comprehensive testing and troubleshooting framework for the MemberPress AI Assistant plugin. It provides a dedicated admin interface for running various diagnostic tests, viewing system information, and troubleshooting issues.

## Key Components

The diagnostics system consists of the following key components:

1. **Diagnostics Page** (`class-mpai-diagnostics-page.php`): Provides the admin UI for running and displaying diagnostic tests
2. **Test Categories**: Organizes tests into logical groups for easier navigation
3. **Test Registry**: Manages test registration and execution
4. **Test Callbacks**: Individual test implementations that check different aspects of the system

## Test Categories

The diagnostics system organizes tests into the following categories:

1. **Core System**: Tests for core plugin functionality and PHP environment
2. **API Connections**: Tests for AI API connections (OpenAI and Anthropic)
3. **AI Tools**: Tests for tool implementations and functionality
4. **Integration Tests**: Tests for integration with WordPress and MemberPress
5. **Plugin Management**: Tests for plugin tracking and management (NEW)

## Recent Updates

### Plugin Management Tab (April 2025)

The latest enhancement to the diagnostics system is the addition of a dedicated Plugin Management category that provides comprehensive information about installed plugins and plugin history.

#### Features Added:

1. **Active Plugins Test**:
   - Displays a comprehensive list of all active and inactive plugins
   - Shows plugin details including name, version, author, and status
   - Provides a count of total, active, and inactive plugins
   - Uses formatted HTML tables with alternating row colors for better readability

2. **Plugin History Test**:
   - Shows recent plugin installation, activation, deactivation, and update history
   - Displays activity summary with counts by action type
   - Lists most active plugins based on recent activity
   - Shows detailed logs of recent plugin events with timestamps and users
   - Uses color-coded action types for better visual identification:
     - Green for installations
     - Blue for activations
     - Orange for deactivations
     - Purple for updates

#### Implementation Details:

1. Added new 'plugins' category to the `get_test_categories()` method:
   ```php
   'plugins' => [
       'name' => __('Plugin Management', 'memberpress-ai-assistant'),
       'description' => __('Information about installed plugins and plugin history', 'memberpress-ai-assistant')
   ],
   ```

2. Added two new tests to `get_available_tests()`:
   ```php
   'active_plugins' => [
       'name' => __('Active Plugins', 'memberpress-ai-assistant'),
       'description' => __('Displays a list of active plugins on the site.', 'memberpress-ai-assistant'),
       'category' => 'plugins',
       'callback' => [$this, 'test_active_plugins'],
   ],
   'plugin_history' => [
       'name' => __('Plugin History', 'memberpress-ai-assistant'),
       'description' => __('Shows recent plugin installation, activation, deactivation, and update history.', 'memberpress-ai-assistant'),
       'category' => 'plugins',
       'callback' => [$this, 'test_plugin_history'],
   ]
   ```

3. Implemented `test_active_plugins()` method to display all plugins with their status.

4. Implemented `test_plugin_history()` method to display plugin logs and activity.

5. Added helper method `generate_plugin_history_html()` for creating formatted HTML output.

## Using the Diagnostics System

### Accessing the Diagnostics Page

1. Navigate to the MemberPress AI Assistant menu in the WordPress admin
2. Click on "Diagnostics" in the submenu

### Running Tests

1. Select a test category from the left sidebar
2. Click on a test to run it
3. View the results displayed in the main content area
4. Use the "Run All Tests" button to execute all tests in a category

### Interpreting Results

Test results are displayed with the following information:

1. **Status**: Pass (green), Warning (yellow), or Error (red)
2. **Message**: A summary of the test result
3. **Details**: Additional information about the test results
4. **Visual Elements**: Tables, formatted data, and statistics where applicable

## Implementation Files

The diagnostics system is primarily implemented in:

- `/includes/class-mpai-diagnostics-page.php`: Main diagnostics page implementation
- `/assets/js/diagnostics.js`: JavaScript for the diagnostics interface
- `/assets/css/admin.css`: Styling for the diagnostics interface

## Admin UI Overhaul Integration

The diagnostics system is a key component of the larger Admin UI Overhaul plan. Phase 3 of the overhaul specifically focused on rebuilding the diagnostics system as a standalone page with improved organization and user experience.

### Completed Items from Admin UI Overhaul Plan:

1. **Standalone Diagnostics Page**:
   - Created as a separate submenu page
   - Implemented proper AJAX handling
   - Added comprehensive test categories

2. **Test Result Presentation**:
   - Added HTML tables for data display
   - Implemented color coding for statuses
   - Created expandable details sections

3. **Plugin Management Tab**:
   - Added new category for plugin-related tests
   - Implemented plugin status visualization
   - Added plugin history tracking

### Pending Items:

1. **Integration with WordPress Site Health**:
   - Contributing to WordPress health checks
   - Using the WordPress health check API

2. **Test Result Storage**:
   - Implementing database storage for test results
   - Adding comparison functionality between test runs

## Future Enhancements

Planned enhancements to the diagnostics system include:

1. **Test Result Export**: Adding the ability to export test results for support
2. **Automatic Testing**: Scheduled tests that run automatically
3. **Email Notifications**: Alerts for critical test failures
4. **Enhanced Visualizations**: Charts and graphs for test results
5. **More Test Categories**: Additional test categories for new features

## Related Documentation

- [Admin UI Overhaul Plan](/docs/roadmap/admin-ui-overhaul-plan.md): The broader plan for admin UI improvements
- [System Map](/docs/current/core/system-map.md): Overview of the entire system architecture
- [Error Recovery System](/docs/current/error-system/error-recovery-system.md): Documentation on the error handling system