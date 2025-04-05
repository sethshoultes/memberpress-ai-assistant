<?php
/**
 * Diagnostics Interface Template
 * 
 * Provides a well-structured UI for the diagnostic system
 * 
 * @package MemberPress AI Assistant
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Check if an element with id="tab-diagnostic" already exists in the DOM to prevent duplicates
$diagnostics_exists = false;
?>
<script>
// Check if the diagnostic tab already exists before rendering
var diagnosticsExists = document.getElementById('tab-diagnostic') !== null;
if (diagnosticsExists) {
    console.log('MPAI WARNING: Diagnostic tab already exists, preventing duplicate rendering');
    document.write('<div style="display:none;" class="mpai-diagnostic-duplicate-prevention"></div>');
}
</script>
<?php 
// Get the current tab from URL parameter if it exists
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : '';

// If we are explicitly loading the diagnostic tab, don't continue
if ($current_tab === 'diagnostic') {
    error_log('MPAI: Skipping diagnostic interface render due to explicit diagnostic tab URL parameter');
    return;
}
?>

<div id="tab-diagnostic" class="mpai-settings-tab" style="display: none;">
    <h3><?php _e('System Diagnostics', 'memberpress-ai-assistant'); ?></h3>
    <p><?php _e('Run various diagnostic tests to check the health of your MemberPress AI Assistant installation.', 'memberpress-ai-assistant'); ?></p>
    
    <div class="mpai-diagnostics-container">
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
                <div id="category-<?php echo esc_attr($category_id); ?>" class="mpai-category-content" style="<?php echo $category_id === 'core' ? '' : 'display:none;'; ?>">
                    <h3><?php echo esc_html($category['name']); ?></h3>
                    <p><?php echo esc_html($category['description']); ?></p>
                    
                    <div class="mpai-test-grid">
                        <?php 
                        $tests = MPAI_Diagnostics::get_tests_by_category($category_id);
                        if (!empty($tests)): 
                            foreach ($tests as $test_id => $test): 
                        ?>
                            <div class="mpai-test-card" data-test-id="<?php echo esc_attr($test_id); ?>">
                                <div class="mpai-test-header">
                                    <h4><?php echo esc_html($test['name']); ?></h4>
                                    <div class="mpai-test-status">
                                        <span class="mpai-status-dot mpai-status-unknown"></span>
                                        <span class="mpai-status-text"><?php _e('Not Run', 'memberpress-ai-assistant'); ?></span>
                                    </div>
                                </div>
                                <p><?php echo esc_html($test['description']); ?></p>
                                <div class="mpai-test-actions">
                                    <button type="button" class="button mpai-run-test">
                                        <?php _e('Run Test', 'memberpress-ai-assistant'); ?>
                                    </button>
                                    <?php if (!empty($test['direct_url'])): ?>
                                        <a href="<?php echo esc_url(plugin_dir_url(dirname(dirname(__FILE__))) . $test['direct_url']); ?>" class="button" target="_blank">
                                            <?php _e('Direct Test', 'memberpress-ai-assistant'); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <div class="mpai-test-result" style="display: none;"></div>
                            </div>
                        <?php 
                            endforeach;
                        else:
                        ?>
                            <div class="mpai-empty-tests">
                                <p><?php _e('No tests available in this category.', 'memberpress-ai-assistant'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (count($tests) > 1): ?>
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
    
    <?php 
    // Legacy support - still run the mpai_run_diagnostics action for backward compatibility
    do_action('mpai_run_diagnostics'); 
    ?>
</div>

<style>
/* Diagnostic System Styles */
.mpai-diagnostics-container {
    margin-top: 20px;
}

.mpai-category-tabs {
    display: flex;
    flex-wrap: wrap;
    padding: 0;
    margin: 0 0 20px 0;
    list-style: none;
    border-bottom: 1px solid #ccc;
}

.mpai-category-tabs li {
    margin-bottom: -1px;
}

.mpai-category-tabs a {
    display: block;
    padding: 8px 12px;
    text-decoration: none;
    margin-right: 5px;
    border: 1px solid transparent;
    border-bottom: none;
    border-radius: 4px 4px 0 0;
    background: #f1f1f1;
    color: #555;
}

.mpai-category-tabs a:hover {
    background: #e5e5e5;
}

.mpai-category-tabs a.active {
    background: #fff;
    color: #000;
    border-color: #ccc;
    border-bottom-color: #fff;
}

.mpai-category-content {
    margin-bottom: 30px;
    background: #fff;
    border: 1px solid #ccc;
    border-top: none;
    padding: 20px;
    border-radius: 0 0 4px 4px;
}

.mpai-test-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 20px;
    margin-top: 20px;
    margin-bottom: 20px;
}

.mpai-test-card {
    border: 1px solid #e0e0e0;
    border-radius: 5px;
    padding: 15px;
    background: #f9f9f9;
    transition: box-shadow 0.3s;
}

.mpai-test-card:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.mpai-test-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.mpai-test-header h4 {
    margin: 0;
    font-size: 16px;
}

.mpai-test-status {
    display: flex;
    align-items: center;
    gap: 5px;
}

.mpai-status-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.mpai-status-unknown {
    background-color: #bbb;
}

.mpai-status-loading {
    background-color: #2271b1;
    animation: pulse 1.5s infinite;
}

.mpai-status-success {
    background-color: #46b450;
}

.mpai-status-error {
    background-color: #dc3232;
}

.mpai-test-actions {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

.mpai-test-result {
    margin-top: 15px;
    border-top: 1px solid #e0e0e0;
    padding-top: 15px;
}

.mpai-test-result-content {
    margin-bottom: 15px;
}

.mpai-result-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
    padding: 8px 12px;
    border-radius: 4px;
}

.mpai-result-header.success {
    background-color: rgba(70, 180, 80, 0.1);
    color: #2e7d32;
}

.mpai-result-header.error {
    background-color: rgba(220, 50, 50, 0.1);
    color: #c62828;
}

.mpai-result-header h4 {
    margin: 0;
    font-size: 15px;
}

.mpai-result-message {
    margin-bottom: 15px;
}

.mpai-timing-info {
    font-size: 13px;
    color: #777;
    margin-bottom: 15px;
}

.mpai-subtests {
    margin-top: 20px;
}

.mpai-doc-link {
    margin-top: 15px;
    font-size: 13px;
}

.mpai-category-actions {
    margin-top: 20px;
}

.mpai-global-actions {
    margin-top: 30px;
    margin-bottom: 20px;
}

.mpai-test-results-summary {
    margin-top: 30px;
    padding: 20px;
    background: #fff;
    border: 1px solid #ccc;
    border-radius: 4px;
}

.mpai-empty-tests {
    grid-column: 1 / -1;
    padding: 20px;
    text-align: center;
    background: rgba(0,0,0,0.03);
    border-radius: 4px;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.4; }
    100% { opacity: 1; }
}

/* Responsive adjustments */
@media (max-width: 782px) {
    .mpai-test-grid {
        grid-template-columns: 1fr;
    }
    
    .mpai-category-tabs {
        flex-direction: column;
        border-bottom: none;
    }
    
    .mpai-category-tabs li {
        margin-bottom: 5px;
    }
    
    .mpai-category-tabs a {
        border: 1px solid #ccc;
        border-radius: 4px;
    }
    
    .mpai-category-tabs a.active {
        border-bottom-color: #ccc;
    }
    
    .mpai-category-content {
        border: 1px solid #ccc;
        border-radius: 4px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Diagnostic system
    const MPAIDiagnostics = (function() {
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
            
            switchCategory: function(tabId) {
                // Remove 'active' class from all tabs
                $('.mpai-category-tabs a').removeClass('active');
                
                // Add 'active' class to clicked tab
                $('.mpai-category-tabs a[href="' + tabId + '"]').addClass('active');
                
                // Hide all category content
                $('.mpai-category-content').hide();
                
                // Show selected category content
                $(tabId).show();
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
                        
                        if (response.success) {
                            state.testResults[testId] = response.data;
                            MPAIDiagnostics.handleTestResponse(response, $card);
                        } else {
                            MPAIDiagnostics.handleTestError(response.data?.message || 'Unknown error', $card);
                        }
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
                    
                    // Add each test result
                    Object.entries(data.tests).forEach(([testName, testResult]) => {
                        resultHtml += '<tr>';
                        resultHtml += '<td>' + testName.replace(/_/g, ' ') + '</td>';
                        resultHtml += '<td>' + 
                                      (testResult.success ? 
                                       '<span style="color: green;">✓ Pass</span>' : 
                                       '<span style="color: red;">✗ Fail</span>') + 
                                      '</td>';
                        resultHtml += '<td>' + (testResult.message || '') + '</td>';
                        resultHtml += '</tr>';
                    });
                    
                    resultHtml += '</tbody></table>';
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
            
            runCategoryTests: function(categoryId) {
                // Get all test cards in this category
                const $cards = $('#category-' + categoryId + ' .mpai-test-card');
                
                // Run each test
                $cards.each(function() {
                    const testId = $(this).data('test-id');
                    MPAIDiagnostics.runTest(testId, $(this));
                });
            },
            
            runAllTests: function() {
                // Show summary container
                $('#mpai-test-results-summary').show();
                $('#mpai-summary-content').html('<p>Running all tests, please wait...</p>');
                
                // Run all tests in each category
                $('.mpai-category-content').each(function() {
                    const categoryId = $(this).attr('id').replace('category-', '');
                    MPAIDiagnostics.runCategoryTests(categoryId);
                });
                
                // Update summary after all tests complete (simplified)
                setTimeout(function() {
                    let summaryHtml = '<p>All tests have been initiated. Check individual test cards for results.</p>';
                    $('#mpai-summary-content').html(summaryHtml);
                }, 500);
            }
        };
    })();
    
    // Initialize diagnostics system
    MPAIDiagnostics.init();
});
</script>