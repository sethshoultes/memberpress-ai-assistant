# System Diagnostics Optimization Plan

## Overview

This document outlines a comprehensive plan to refactor and optimize the System Diagnostics section of the MemberPress AI Assistant settings page. The current implementation has grown organically but shows signs of technical debt, including code duplication, inconsistent test patterns, and a high barrier to adding new tests.

## Goals

1. **Simplify Test Addition**: Make it trivial to add new diagnostic tests without extensive HTML/CSS/JS coding
2. **Improve Organization**: Group tests into logical categories with consistent visual design
3. **Standardize Test API**: Create a uniform API for test execution, result handling, and display
4. **Enhance Maintainability**: Reduce code duplication and improve separation of concerns
5. **Improve User Experience**: Create a more intuitive, responsive diagnostic interface

## Current Issues

- **Monolithic Structure**: The current settings-diagnostic.php file is large and unwieldy
- **Inconsistent Patterns**: Different tests use varying HTML structures and JavaScript patterns
- **High Duplication**: Similar logic and styling is repeated for multiple test types
- **Poor Extensibility**: Adding new tests requires extensive modifications to the main file
- **Complex Test Logic**: Test execution and result handling are tightly coupled to display logic
- **Limited Organization**: Tests are not clearly grouped by purpose or related functionality

## Proposed Architecture

### 1. Test Registry System

Create a central registry system to manage test definitions:

```php
class MPAI_Diagnostics {
    // Registry of all diagnostic tests
    private static $tests = [];
    
    /**
     * Register a diagnostic test
     * 
     * @param array $test_data Test configuration
     * @return bool Success
     */
    public static function register_test($test_data) {
        // Validate required fields
        if (!isset($test_data['id']) || !isset($test_data['name']) || !isset($test_data['category'])) {
            return false;
        }
        
        // Add to registry
        self::$tests[$test_data['id']] = $test_data;
        return true;
    }
    
    /**
     * Get all registered tests
     * 
     * @return array Tests
     */
    public static function get_tests() {
        return self::$tests;
    }
    
    /**
     * Get tests by category
     * 
     * @param string $category Category ID
     * @return array Tests in category
     */
    public static function get_tests_by_category($category) {
        return array_filter(self::$tests, function($test) use ($category) {
            return $test['category'] === $category;
        });
    }
    
    /**
     * Get a specific test
     * 
     * @param string $test_id Test ID
     * @return array|null Test data or null if not found
     */
    public static function get_test($test_id) {
        return isset(self::$tests[$test_id]) ? self::$tests[$test_id] : null;
    }
}
```

### 2. Standardized Test Interface

Define a standardized test interface with consistent parameters and return values:

```php
/**
 * Standard test interface
 *
 * @param array $params Test parameters from AJAX request
 * @return array {
 *     @type bool        $success   Whether the test was successful
 *     @type string      $message   Main test result message 
 *     @type array       $data      Optional detailed test data
 *     @type array       $timing    Optional timing information
 *     @type array       $tests     Optional sub-test results
 * }
 */
function mpai_standard_test_interface($params) {
    return [
        'success' => true,
        'message' => 'Test completed successfully',
        'data' => [
            // Test-specific data
        ],
        'timing' => [
            'start' => microtime(true),
            'end' => microtime(true),
            'total' => 0.123, // seconds
        ],
        'tests' => [
            'subtest_1' => [
                'success' => true,
                'message' => 'Sub-test 1 passed',
            ],
            // Additional sub-tests
        ],
    ];
}
```

### 3. Test Registration System

Create a hook-based system for registering tests:

```php
/**
 * Action hook to register diagnostic tests
 */
function mpai_register_diagnostic_tests() {
    do_action('mpai_register_diagnostic_tests');
}

/**
 * Register core diagnostic tests
 */
function mpai_register_core_diagnostic_tests() {
    // Register core system tests
    MPAI_Diagnostics::register_test([
        'id' => 'system-info',
        'category' => 'core',
        'name' => 'System Information',
        'description' => 'Get detailed information about your WordPress and PHP environment',
        'test_callback' => 'mpai_run_system_info_test',
        'doc_url' => 'system-information.md',
    ]);
    
    // Register API tests
    MPAI_Diagnostics::register_test([
        'id' => 'openai-connection',
        'category' => 'api',
        'name' => 'OpenAI API Connection',
        'description' => 'Test connection to the OpenAI API',
        'test_callback' => 'mpai_test_openai_connection',
        'doc_url' => 'api-connections.md#openai',
    ]);
    
    // Register Phase Three tests
    MPAI_Diagnostics::register_test([
        'id' => 'error-recovery',
        'category' => 'phase-three',
        'name' => 'Error Recovery System',
        'description' => 'Test the Error Recovery System functionality',
        'test_callback' => 'mpai_test_error_recovery',
        'direct_url' => 'test/test-error-recovery-page.php',
        'doc_url' => 'error-recovery-system.md',
    ]);
    
    MPAI_Diagnostics::register_test([
        'id' => 'state-validation',
        'category' => 'phase-three',
        'name' => 'State Validation System',
        'description' => 'Test the State Validation System functionality',
        'test_callback' => 'mpai_test_state_validation',
        'direct_url' => 'test/test-state-validation.php',
        'doc_url' => 'state-validation-system.md',
    ]);
    
    // Additional tests...
}
add_action('mpai_register_diagnostic_tests', 'mpai_register_core_diagnostic_tests');
```

### 4. Template-Based Rendering

Create templates for consistent rendering:

```php
// Template: diagnostic-page.php
<div class="mpai-diagnostics-container">
    <h2><?php _e('System Diagnostics', 'memberpress-ai-assistant'); ?></h2>
    <p><?php _e('Run diagnostic tests to verify system functionality and troubleshoot issues.', 'memberpress-ai-assistant'); ?></p>
    
    <div class="mpai-test-categories">
        <ul class="mpai-category-tabs">
            <?php foreach (MPAI_Diagnostics::get_categories() as $category_id => $category): ?>
                <li>
                    <a href="#category-<?php echo esc_attr($category_id); ?>" class="<?php echo $category_id === 'core' ? 'active' : ''; ?>">
                        <?php echo esc_html($category['name']); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
        
        <?php foreach (MPAI_Diagnostics::get_categories() as $category_id => $category): ?>
            <div id="category-<?php echo esc_attr($category_id); ?>" class="mpai-category-content">
                <h3><?php echo esc_html($category['name']); ?></h3>
                <p><?php echo esc_html($category['description']); ?></p>
                
                <div class="mpai-test-grid">
                    <?php foreach (MPAI_Diagnostics::get_tests_by_category($category_id) as $test): ?>
                        <?php mpai_render_test_card($test); ?>
                    <?php endforeach; ?>
                </div>
                
                <?php if (count(MPAI_Diagnostics::get_tests_by_category($category_id)) > 1): ?>
                    <div class="mpai-category-actions">
                        <button type="button" class="button button-secondary mpai-run-category-tests" 
                                data-category="<?php echo esc_attr($category_id); ?>">
                            <?php _e('Run All Tests in Category', 'memberpress-ai-assistant'); ?>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="mpai-global-actions">
        <button type="button" id="mpai-run-all-tests" class="button button-primary">
            <?php _e('Run All Diagnostics', 'memberpress-ai-assistant'); ?>
        </button>
    </div>
    
    <div id="mpai-test-results-summary" class="mpai-test-results-summary" style="display: none;">
        <h3><?php _e('Test Results Summary', 'memberpress-ai-assistant'); ?></h3>
        <div id="mpai-summary-content"></div>
    </div>
</div>
```

### 5. Unified AJAX Handler

Create a unified AJAX handler for all test requests:

```php
/**
 * AJAX handler for diagnostic tests
 */
function mpai_ajax_run_diagnostic_test() {
    // Check nonce for security
    check_ajax_referer('mpai_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized access']);
        return;
    }
    
    $test_id = isset($_POST['test_id']) ? sanitize_key($_POST['test_id']) : '';
    if (empty($test_id)) {
        wp_send_json_error(['message' => 'Missing test ID']);
        return;
    }
    
    $test = MPAI_Diagnostics::get_test($test_id);
    if (empty($test) || !isset($test['test_callback']) || !is_callable($test['test_callback'])) {
        wp_send_json_error(['message' => 'Invalid test ID or callback']);
        return;
    }
    
    try {
        // Start timing
        $start_time = microtime(true);
        
        // Call the test callback
        $result = call_user_func($test['test_callback'], $_POST);
        
        // End timing
        $end_time = microtime(true);
        $total_time = $end_time - $start_time;
        
        // Add timing if not already included
        if (!isset($result['timing'])) {
            $result['timing'] = [
                'start' => $start_time,
                'end' => $end_time,
                'total' => $total_time,
            ];
        }
        
        // Add test metadata
        $result['test_id'] = $test_id;
        $result['test_name'] = $test['name'];
        
        // Save test result
        mpai_save_test_result($test_id, $result);
        
        wp_send_json_success($result);
    } catch (Exception $e) {
        wp_send_json_error([
            'message' => 'Error running test: ' . $e->getMessage(),
            'error' => $e->getMessage(),
            'test_id' => $test_id,
            'test_name' => $test['name'],
        ]);
    }
}
add_action('wp_ajax_mpai_run_diagnostic_test', 'mpai_ajax_run_diagnostic_test');
```

### 6. JavaScript Framework

Create a modular JavaScript framework for test execution:

```javascript
// Main diagnostics module
const MPAIDiagnostics = (function($) {
    // Private state
    const state = {
        runningTests: {},
        testResults: {},
    };
    
    // Public interface
    return {
        init: function() {
            // Initialize all test cards
            $('.mpai-test-card').each(function() {
                MPAIDiagnostics.initTestCard($(this));
            });
            
            // Initialize category test buttons
            $('.mpai-run-category-tests').on('click', function() {
                const category = $(this).data('category');
                MPAIDiagnostics.runCategoryTests(category);
            });
            
            // Initialize "Run All Tests" button
            $('#mpai-run-all-tests').on('click', function() {
                MPAIDiagnostics.runAllTests();
            });
            
            // Initialize category tabs
            $('.mpai-category-tabs a').on('click', function(e) {
                e.preventDefault();
                MPAIDiagnostics.switchCategory($(this).attr('href'));
            });
        },
        
        initTestCard: function($card) {
            const testId = $card.data('test-id');
            
            $card.find('.mpai-run-test').on('click', function() {
                MPAIDiagnostics.runTest(testId, $card);
            });
        },
        
        runTest: function(testId, $card) {
            // Show loading state
            $card.find('.mpai-status-dot')
                 .removeClass('mpai-status-unknown mpai-status-success mpai-status-error')
                 .addClass('mpai-status-loading');
            $card.find('.mpai-status-text').text('Running...');
            
            const $resultContainer = $card.find('.mpai-test-result');
            $resultContainer.html('<p>Running test...</p>').show();
            
            // Track running test
            state.runningTests[testId] = true;
            
            // Make AJAX request
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'mpai_run_diagnostic_test',
                    test_id: testId,
                    nonce: mpai_data.nonce
                },
                success: function(response) {
                    delete state.runningTests[testId];
                    state.testResults[testId] = response.data;
                    MPAIDiagnostics.handleTestResponse(response, $card);
                },
                error: function(xhr, status, error) {
                    delete state.runningTests[testId];
                    MPAIDiagnostics.handleTestError(error, $card);
                }
            });
        },
        
        handleTestResponse: function(response, $card) {
            const data = response.data;
            const success = response.success && data.success;
            
            // Update status indicator
            $card.find('.mpai-status-dot')
                 .removeClass('mpai-status-unknown mpai-status-loading')
                 .addClass(success ? 'mpai-status-success' : 'mpai-status-error');
            $card.find('.mpai-status-text').text(success ? 'Success' : 'Failed');
            
            // Generate result HTML
            let resultHtml = '<div class="mpai-test-result-content">';
            
            // Add header with success/failure indicator
            resultHtml += '<div class="mpai-result-header ' + (success ? 'success' : 'error') + '">';
            resultHtml += '<span class="dashicons ' + (success ? 'dashicons-yes-alt' : 'dashicons-warning') + '"></span>';
            resultHtml += '<h4>' + (success ? 'Test Passed' : 'Test Failed') + '</h4>';
            resultHtml += '</div>';
            
            // Add main message
            if (data.message) {
                resultHtml += '<p class="mpai-result-message">' + data.message + '</p>';
            }
            
            // Add timing info if available
            if (data.timing && data.timing.total) {
                resultHtml += '<p class="mpai-timing-info">Execution time: ' + 
                              (data.timing.total * 1000).toFixed(2) + ' ms</p>';
            }
            
            // Add sub-tests if available
            if (data.tests && Object.keys(data.tests).length > 0) {
                resultHtml += '<div class="mpai-subtests">';
                resultHtml += '<h5>Detailed Results</h5>';
                resultHtml += '<table class="widefat">';
                resultHtml += '<thead><tr><th>Test</th><th>Status</th><th>Message</th></tr></thead>';
                resultHtml += '<tbody>';
                
                Object.entries(data.tests).forEach(([testName, testResult]) => {
                    resultHtml += '<tr>';
                    resultHtml += '<td>' + testName + '</td>';
                    resultHtml += '<td>' + 
                                  (testResult.success ? 
                                   '<span class="success">Pass</span>' : 
                                   '<span class="error">Fail</span>') + 
                                  '</td>';
                    resultHtml += '<td>' + (testResult.message || '') + '</td>';
                    resultHtml += '</tr>';
                });
                
                resultHtml += '</tbody></table>';
                resultHtml += '</div>';
            }
            
            // Add documentation link if available
            const testId = $card.data('test-id');
            const test = MPAIDiagnostics.getTestData(testId);
            if (test && test.doc_url) {
                resultHtml += '<div class="mpai-doc-link">';
                resultHtml += '<a href="' + test.doc_url + '" target="_blank">';
                resultHtml += 'View Documentation';
                resultHtml += '</a>';
                resultHtml += '</div>';
            }
            
            resultHtml += '</div>'; // Close mpai-test-result-content
            
            // Update result container
            $card.find('.mpai-test-result').html(resultHtml);
        },
        
        handleTestError: function(error, $card) {
            // Update status indicator
            $card.find('.mpai-status-dot')
                 .removeClass('mpai-status-unknown mpai-status-loading')
                 .addClass('mpai-status-error');
            $card.find('.mpai-status-text').text('Error');
            
            // Generate error HTML
            let errorHtml = '<div class="mpai-test-result-content error">';
            errorHtml += '<div class="mpai-result-header error">';
            errorHtml += '<span class="dashicons dashicons-warning"></span>';
            errorHtml += '<h4>Test Error</h4>';
            errorHtml += '</div>';
            errorHtml += '<p class="mpai-result-message">' + error + '</p>';
            errorHtml += '</div>';
            
            // Update result container
            $card.find('.mpai-test-result').html(errorHtml);
        },
        
        // Additional methods for category switching, running multiple tests, etc.
    };
})(jQuery);

// Initialize on document ready
jQuery(document).ready(function($) {
    MPAIDiagnostics.init();
});
```

## Implementation Plan

### Phase 1: Core Framework (Week 1)

1. Create `class-mpai-diagnostics.php` with test registry functionality
2. Implement template system for test cards and diagnostic page
3. Create unified AJAX handler for tests
4. Develop JavaScript framework for test execution

### Phase 2: Test Migration (Week 2)

1. Migrate existing core system tests to the new framework
2. Migrate Phase One, Two, and Three tests to the new framework
3. Update API integration tests to use standardized interface
4. Create test for new State Validation System

### Phase 3: Enhanced Features (Week 3)

1. Add test result storage and history
2. Implement test dependency system
3. Create test scheduling for background diagnostics
4. Add comprehensive reporting system

### Phase 4: Refinement & Documentation (Week 4)

1. Optimize UI/UX for all test interactions
2. Create comprehensive developer documentation
3. Implement automated test coverage for diagnostic system
4. Add export/import functionality for test results

## Benefits

1. **Modular Design**: Each test becomes a self-contained component
2. **Simplified Maintenance**: Reduced code duplication and improved separation of concerns
3. **Easier Extensions**: Adding new tests is straightforward and requires minimal code
4. **Improved UI/UX**: Consistent interface and better organization of tests
5. **Better Performance**: Optimized test execution and result handling
6. **Enhanced Reporting**: Comprehensive test history and result tracking

## Development Recommendations

1. Start with a fresh file structure rather than trying to refactor the existing one
2. Use a gradual migration approach to prevent disruption to existing functionality
3. Add comprehensive inline documentation for all new components
4. Create detailed developer guides for creating new diagnostic tests
5. Implement extensive automated testing for the diagnostic framework itself

## Compatibility Considerations

This refactoring should maintain backward compatibility by:

1. Preserving existing test IDs and names
2. Maintaining the same URL structure for direct test pages
3. Ensuring test results have the same format as before
4. Preserving existing test functions while adapting them to the new system

## Conclusion

This refactoring will significantly improve the organization, maintainability, and extensibility of the System Diagnostics section while providing a much better user experience. The modular design will make it easy to add new tests as the plugin evolves, and the standardized interface will ensure consistent behavior across all test types.