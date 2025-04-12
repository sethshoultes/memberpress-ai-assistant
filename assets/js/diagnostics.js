/**
 * MemberPress AI Assistant Diagnostics JavaScript
 * 
 * Handles the diagnostics page UI functionality
 */
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
                
                // Check URL hash for category tab
                if (window.location.hash && $(window.location.hash).length) {
                    MPAIDiagnostics.switchCategory(window.location.hash);
                }
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
                
                // Update URL hash without scrolling
                if (history.pushState) {
                    history.pushState(null, null, tabId);
                } else {
                    location.hash = tabId;
                }
            },
            
            runTest: function(testId, $card) {
                // Prevent running a test that's already running
                if (state.runningTests[testId]) {
                    return;
                }
                
                // Show loading state
                $card.find('.mpai-status-dot')
                     .removeClass('mpai-status-unknown mpai-status-success mpai-status-error mpai-status-warning')
                     .addClass('mpai-status-loading');
                $card.find('.mpai-status-text').text('Running...');
                
                const $resultContainer = $card.find('.mpai-test-result');
                $resultContainer.html('<p>Running test...</p>').show();
                
                // Track running test
                state.runningTests[testId] = true;
                
                // Make AJAX request
                $.ajax({
                    url: mpai_diagnostics.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'mpai_run_diagnostic_test',
                        test_id: testId,
                        nonce: mpai_diagnostics.nonce
                    },
                    success: function(response) {
                        delete state.runningTests[testId];
                        
                        if (response.success) {
                            state.testResults[testId] = response.data;
                            MPAIDiagnostics.displayTestResult(response.data, $card);
                        } else {
                            MPAIDiagnostics.displayTestError(response.data?.message || 'Unknown error', $card);
                        }
                    },
                    error: function(xhr, status, error) {
                        delete state.runningTests[testId];
                        MPAIDiagnostics.displayTestError(error, $card);
                    }
                });
            },
            
            displayTestResult: function(result, $card) {
                // Set status based on result.status (success, warning, error)
                const status = result.status || (result.success ? 'success' : 'error');
                
                // Update status indicator
                $card.find('.mpai-status-dot')
                     .removeClass('mpai-status-unknown mpai-status-loading')
                     .addClass('mpai-status-' + status);
                     
                $card.find('.mpai-status-text').text(
                    status === 'success' ? 'Success' : 
                    status === 'warning' ? 'Warning' : 'Failed'
                );
                
                // Generate result HTML
                let resultHtml = '<div class="mpai-test-result-content">';
                
                // Add header with status indicator
                resultHtml += '<div class="mpai-result-header ' + status + '">';
                
                // Choose dashicon based on status
                let dashicon = 'dashicons-yes-alt';
                if (status === 'warning') {
                    dashicon = 'dashicons-warning';
                } else if (status === 'error') {
                    dashicon = 'dashicons-no';
                }
                
                resultHtml += '<span class="dashicons ' + dashicon + '"></span>';
                
                // Header text
                let headerText = 'Test Passed';
                if (status === 'warning') {
                    headerText = 'Test Passed with Warnings';
                } else if (status === 'error') {
                    headerText = 'Test Failed';
                }
                
                resultHtml += '<h4>' + headerText + '</h4>';
                resultHtml += '</div>';
                
                // Add main message
                if (result.message) {
                    resultHtml += '<p class="mpai-result-message">' + result.message + '</p>';
                }
                
                // Add critical issues if present
                if (result.critical_issues && result.critical_issues.length > 0) {
                    resultHtml += '<div class="mpai-critical-issues">';
                    resultHtml += '<h5>Critical Issues:</h5>';
                    resultHtml += '<ul>';
                    result.critical_issues.forEach(function(issue) {
                        resultHtml += '<li>' + issue + '</li>';
                    });
                    resultHtml += '</ul>';
                    resultHtml += '</div>';
                }
                
                // Add warnings if present
                if (result.warnings && result.warnings.length > 0) {
                    resultHtml += '<div class="mpai-warnings">';
                    resultHtml += '<h5>Warnings:</h5>';
                    resultHtml += '<ul>';
                    result.warnings.forEach(function(warning) {
                        resultHtml += '<li>' + warning + '</li>';
                    });
                    resultHtml += '</ul>';
                    resultHtml += '</div>';
                }
                
                // Add timing info if available
                if (result.timing && result.timing.total) {
                    resultHtml += '<p class="mpai-timing-info">Execution time: ' + 
                                  (result.timing.total * 1000).toFixed(2) + ' ms</p>';
                }
                
                // Add details if available
                if (result.details) {
                    resultHtml += '<div class="mpai-details">';
                    resultHtml += '<h5>Details:</h5>';
                    
                    if (typeof result.details === 'object') {
                        resultHtml += '<pre>' + JSON.stringify(result.details, null, 2) + '</pre>';
                    } else {
                        resultHtml += '<p>' + result.details + '</p>';
                    }
                    
                    resultHtml += '</div>';
                }
                
                resultHtml += '</div>'; // Close mpai-test-result-content
                
                // Update result container
                $card.find('.mpai-test-result').html(resultHtml);
            },
            
            displayTestError: function(error, $card) {
                // Update status indicator
                $card.find('.mpai-status-dot')
                     .removeClass('mpai-status-unknown mpai-status-loading')
                     .addClass('mpai-status-error');
                $card.find('.mpai-status-text').text('Error');
                
                // Generate error HTML
                let errorHtml = '<div class="mpai-test-result-content">';
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
                const $categoryContent = $('#category-' + categoryId);
                const $tests = $categoryContent.find('.mpai-test-card');
                const $categoryButton = $('.mpai-run-category-tests[data-category="' + categoryId + '"]');
                
                // Disable button while tests are running
                $categoryButton.prop('disabled', true).text('Running Tests...');
                
                // Show loading state for all tests
                $tests.each(function() {
                    const $card = $(this);
                    $card.find('.mpai-status-dot')
                         .removeClass('mpai-status-unknown mpai-status-success mpai-status-error mpai-status-warning')
                         .addClass('mpai-status-loading');
                    $card.find('.mpai-status-text').text('Queued...');
                });
                
                // Make AJAX request to run all tests in this category
                $.ajax({
                    url: mpai_diagnostics.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'mpai_run_category_tests',
                        category_id: categoryId,
                        nonce: mpai_diagnostics.nonce
                    },
                    success: function(response) {
                        // Re-enable button
                        $categoryButton.prop('disabled', false).text('Run All Tests in Category');
                        
                        if (response.success && response.data && response.data.results) {
                            // Process results for each test
                            const results = response.data.results;
                            
                            for (const testId in results) {
                                if (results.hasOwnProperty(testId)) {
                                    const result = results[testId];
                                    const $testCard = $categoryContent.find('.mpai-test-card[data-test-id="' + testId + '"]');
                                    
                                    if ($testCard.length) {
                                        state.testResults[testId] = result;
                                        MPAIDiagnostics.displayTestResult(result, $testCard);
                                    }
                                }
                            }
                        } else {
                            alert('Error running category tests: ' + 
                                  (response.data && response.data.message ? response.data.message : 'Unknown error'));
                        }
                    },
                    error: function(xhr, status, error) {
                        // Re-enable button
                        $categoryButton.prop('disabled', false).text('Run All Tests in Category');
                        alert('Error running category tests: ' + error);
                    }
                });
            },
            
            runAllTests: function() {
                // Show summary container
                $('#mpai-test-results-summary').show();
                $('#mpai-summary-content').html('<p>Running all tests, please wait...</p>');
                
                // Disable the button while tests are running
                const $allTestsButton = $('#mpai-run-all-tests');
                $allTestsButton.prop('disabled', true).text('Running All Tests...');
                
                // Show loading state for all tests
                $('.mpai-test-card').each(function() {
                    const $card = $(this);
                    $card.find('.mpai-status-dot')
                         .removeClass('mpai-status-unknown mpai-status-success mpai-status-error mpai-status-warning')
                         .addClass('mpai-status-loading');
                    $card.find('.mpai-status-text').text('Queued...');
                });
                
                // Make AJAX request to run all tests
                $.ajax({
                    url: mpai_diagnostics.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'mpai_run_all_tests',
                        nonce: mpai_diagnostics.nonce
                    },
                    success: function(response) {
                        // Re-enable the button
                        $allTestsButton.prop('disabled', false).text('Run All Diagnostics');
                        
                        if (response.success && response.data) {
                            const allResults = response.data.all_results;
                            const groupedResults = response.data.grouped_results;
                            
                            // Process results for each test
                            for (const testId in allResults) {
                                if (allResults.hasOwnProperty(testId)) {
                                    const result = allResults[testId];
                                    const $testCard = $('.mpai-test-card[data-test-id="' + testId + '"]');
                                    
                                    if ($testCard.length) {
                                        state.testResults[testId] = result;
                                        MPAIDiagnostics.displayTestResult(result, $testCard);
                                    }
                                }
                            }
                            
                            // Generate summary HTML
                            let summaryHtml = '<div class="mpai-summary-content">';
                            
                            // Add overall summary
                            let totalTests = 0;
                            let successCount = 0;
                            let warningCount = 0;
                            let failCount = 0;
                            
                            for (const categoryId in groupedResults) {
                                if (groupedResults.hasOwnProperty(categoryId)) {
                                    const category = groupedResults[categoryId];
                                    const categoryTestCount = Object.keys(category.results).length;
                                    
                                    totalTests += categoryTestCount;
                                    successCount += category.success_count;
                                    warningCount += category.warning_count;
                                    failCount += category.fail_count;
                                }
                            }
                            
                            summaryHtml += '<div class="mpai-overall-summary">';
                            summaryHtml += '<h4>Overall Results</h4>';
                            summaryHtml += '<p>' + totalTests + ' tests run: ';
                            summaryHtml += '<span class="mpai-status-success">' + successCount + ' passed</span>, ';
                            
                            if (warningCount > 0) {
                                summaryHtml += '<span class="mpai-status-warning">' + warningCount + ' warnings</span>, ';
                            }
                            
                            summaryHtml += '<span class="mpai-status-error">' + failCount + ' failed</span>';
                            summaryHtml += '</p>';
                            summaryHtml += '</div>';
                            
                            // Add category summaries
                            summaryHtml += '<div class="mpai-category-summaries">';
                            summaryHtml += '<h4>Results by Category</h4>';
                            
                            for (const categoryId in groupedResults) {
                                if (groupedResults.hasOwnProperty(categoryId)) {
                                    const category = groupedResults[categoryId];
                                    const categoryTestCount = Object.keys(category.results).length;
                                    
                                    summaryHtml += '<div class="mpai-category-summary">';
                                    summaryHtml += '<h5>' + category.name + '</h5>';
                                    summaryHtml += '<p>' + categoryTestCount + ' tests: ';
                                    summaryHtml += '<span class="mpai-status-success">' + category.success_count + ' passed</span>, ';
                                    
                                    if (category.warning_count > 0) {
                                        summaryHtml += '<span class="mpai-status-warning">' + category.warning_count + ' warnings</span>, ';
                                    }
                                    
                                    summaryHtml += '<span class="mpai-status-error">' + category.fail_count + ' failed</span>';
                                    summaryHtml += '</p>';
                                    
                                    // Add link to category
                                    summaryHtml += '<p><a href="#category-' + categoryId + '" class="button button-small">View Category Tests</a></p>';
                                    
                                    summaryHtml += '</div>';
                                }
                            }
                            
                            summaryHtml += '</div>'; // Close category summaries
                            
                            if (failCount > 0) {
                                // Add failed tests summary
                                summaryHtml += '<div class="mpai-failed-tests-summary">';
                                summaryHtml += '<h4>Failed Tests</h4>';
                                
                                for (const categoryId in groupedResults) {
                                    if (groupedResults.hasOwnProperty(categoryId)) {
                                        const category = groupedResults[categoryId];
                                        const categoryResults = category.results;
                                        let categoryFailedTests = [];
                                        
                                        for (const testId in categoryResults) {
                                            if (categoryResults.hasOwnProperty(testId)) {
                                                const result = categoryResults[testId];
                                                if (!result.success || result.status === 'error') {
                                                    categoryFailedTests.push({
                                                        id: testId,
                                                        name: result.test_name,
                                                        message: result.message
                                                    });
                                                }
                                            }
                                        }
                                        
                                        if (categoryFailedTests.length > 0) {
                                            summaryHtml += '<div class="mpai-category-failed-tests">';
                                            summaryHtml += '<h5>' + category.name + '</h5>';
                                            summaryHtml += '<ul>';
                                            
                                            categoryFailedTests.forEach(function(test) {
                                                summaryHtml += '<li>';
                                                summaryHtml += '<strong>' + test.name + ':</strong> ';
                                                summaryHtml += test.message;
                                                summaryHtml += ' <a href="#category-' + categoryId + '" ' +
                                                              'data-test-id="' + test.id + '" ' +
                                                              'class="mpai-view-test-details">View Details</a>';
                                                summaryHtml += '</li>';
                                            });
                                            
                                            summaryHtml += '</ul>';
                                            summaryHtml += '</div>';
                                        }
                                    }
                                }
                                
                                summaryHtml += '</div>'; // Close failed tests summary
                            }
                            
                            summaryHtml += '</div>'; // Close summary content
                            
                            // Update summary container
                            $('#mpai-summary-content').html(summaryHtml);
                            
                            // Add click handlers for "View Details" links
                            $('.mpai-view-test-details').on('click', function(e) {
                                e.preventDefault();
                                
                                const testId = $(this).data('test-id');
                                const categoryId = $(this).attr('href').replace('#category-', '');
                                
                                // Switch to the category tab
                                MPAIDiagnostics.switchCategory('#category-' + categoryId);
                                
                                // Scroll to the test card
                                const $testCard = $('#category-' + categoryId + ' .mpai-test-card[data-test-id="' + testId + '"]');
                                
                                if ($testCard.length) {
                                    // Highlight the test card briefly
                                    $testCard.addClass('mpai-highlight-test');
                                    
                                    // Scroll to the test card
                                    $('html, body').animate({
                                        scrollTop: $testCard.offset().top - 50
                                    }, 500);
                                    
                                    // Remove the highlight after a delay
                                    setTimeout(function() {
                                        $testCard.removeClass('mpai-highlight-test');
                                    }, 2000);
                                }
                            });
                        } else {
                            // Update summary with error
                            $('#mpai-summary-content').html(
                                '<div class="mpai-summary-error">' +
                                '<h4>Error Running Tests</h4>' +
                                '<p>' + (response.data && response.data.message ? response.data.message : 'Unknown error') + '</p>' +
                                '</div>'
                            );
                        }
                    },
                    error: function(xhr, status, error) {
                        // Re-enable the button
                        $allTestsButton.prop('disabled', false).text('Run All Diagnostics');
                        
                        // Update summary with error
                        $('#mpai-summary-content').html(
                            '<div class="mpai-summary-error">' +
                            '<h4>Error Running Tests</h4>' +
                            '<p>AJAX error: ' + error + '</p>' +
                            '</div>'
                        );
                    }
                });
            }
        };
    })();
    
    // Initialize diagnostics system
    MPAIDiagnostics.init();
});