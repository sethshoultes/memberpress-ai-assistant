// Additional script to bind event handlers for Phase Two tests
jQuery(document).ready(function($) {
    console.log('MPAI: Test script loaded');
    
    // Bind System Cache test button
    $('#run-system-cache-test').on('click', function() {
        console.log('MPAI: Clicked System Cache test button');
        
        // Create a general function to run phase tests if not already defined
        if (typeof runPhaseTest !== 'function') {
            console.log('MPAI: Creating runPhaseTest function');
            window.runPhaseTest = function(testType, resultContainer, statusIndicator, phaseLabel = 'Phase One') {
                console.log(`MPAI: Running ${phaseLabel} test:`, testType);
                
                // Show loading state
                $(resultContainer).html('<p>Running test...</p>');
                $(resultContainer).show();
                
                // Update status indicator
                if (statusIndicator) {
                    $(statusIndicator + ' .mpai-status-dot').removeClass('mpai-status-unknown mpai-status-success mpai-status-error')
                        .addClass('mpai-status-unknown');
                    $(statusIndicator + ' .mpai-status-text').text('Running...');
                }
                
                // Make API request to run diagnostic
                var formData = new FormData();
                formData.append('action', testType);
                
                // Use direct AJAX handler
                var directHandlerUrl = mpai_data.plugin_url + 'includes/direct-ajax-handler.php';
                
                console.log(`MPAI: Running ${phaseLabel} test via direct handler:`, testType, 'URL:', directHandlerUrl);
                fetch(directHandlerUrl, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                })
                .then(function(response) {
                    return response.json();
                })
                .then(function(data) {
                    console.log(`MPAI: ${phaseLabel} test response:`, data);
                    
                    if (data.success) {
                        // Update status indicator
                        if (statusIndicator) {
                            $(statusIndicator + ' .mpai-status-dot').removeClass('mpai-status-unknown mpai-status-error')
                                .addClass('mpai-status-success');
                            $(statusIndicator + ' .mpai-status-text').text('Success');
                        }
                        
                        // Format and display the result
                        let formattedResult = '';
                        
                        if (testType === 'test_system_cache') {
                            formattedResult = formatSystemCacheResult(data.data);
                        } else {
                            formattedResult = '<div class="mpai-system-test-result mpai-test-success">';
                            formattedResult += '<h4>Test Successful!</h4>';
                            formattedResult += '<p>' + JSON.stringify(data.data) + '</p>';
                            formattedResult += '</div>';
                        }
                        
                        $(resultContainer).html(formattedResult);
                    } else {
                        // Update status indicator
                        if (statusIndicator) {
                            $(statusIndicator + ' .mpai-status-dot').removeClass('mpai-status-unknown mpai-status-success')
                                .addClass('mpai-status-error');
                            $(statusIndicator + ' .mpai-status-text').text('Failed');
                        }
                        
                        // Display error message
                        let errorMessage = data.message || 'Unknown error occurred';
                        let formattedError = '<div class="mpai-system-test-result mpai-test-error">';
                        formattedError += '<h4>Test Failed</h4>';
                        formattedError += '<p>' + errorMessage + '</p>';
                        formattedError += '</div>';
                        
                        $(resultContainer).html(formattedError);
                    }
                })
                .catch(function(error) {
                    console.error(`MPAI: Error in ${phaseLabel} test:`, error);
                    
                    // Update status indicator
                    if (statusIndicator) {
                        $(statusIndicator + ' .mpai-status-dot').removeClass('mpai-status-unknown mpai-status-success')
                            .addClass('mpai-status-error');
                        $(statusIndicator + ' .mpai-status-text').text('Error');
                    }
                    
                    // Display error message
                    let formattedError = '<div class="mpai-system-test-result mpai-test-error">';
                    formattedError += '<h4>Test Error</h4>';
                    formattedError += '<p>Error executing test: ' + error.message + '</p>';
                    formattedError += '</div>';
                    
                    $(resultContainer).html(formattedError);
                });
            };
        }
        
        // Function to format system cache test results
        window.formatSystemCacheResult = function(data) {
            let output = '<div class="mpai-system-test-result mpai-test-success">';
            output += '<h4>System Information Cache Test Results</h4>';
            
            if (data.success) {
                output += '<p class="mpai-test-success-message">' + data.message + '</p>';
                
                // Add test details
                output += '<h5>Test Details:</h5>';
                output += '<table class="mpai-test-results-table">';
                output += '<tr><th>Test</th><th>Result</th><th>Timing</th></tr>';
                
                data.data.tests.forEach(function(test) {
                    let resultClass = test.success ? 'mpai-test-success' : 'mpai-test-error';
                    let resultText = test.success ? 'PASSED' : 'FAILED';
                    
                    output += '<tr>';
                    output += '<td>' + test.name + '</td>';
                    output += '<td class="' + resultClass + '">' + resultText + '</td>';
                    
                    // Format timing information
                    let timing = '';
                    if (typeof test.timing === 'object') {
                        timing = 'First Request: ' + test.timing.first_request + '<br>';
                        timing += 'Second Request: ' + test.timing.second_request + '<br>';
                        timing += 'Improvement: ' + test.timing.improvement;
                    } else {
                        timing = test.timing;
                    }
                    
                    output += '<td>' + timing + '</td>';
                    output += '</tr>';
                });
                
                output += '</table>';
                
                // Add cache hits
                output += '<p>Cache Hits: ' + data.data.cache_hits + '</p>';
            } else {
                output += '<p class="mpai-test-error-message">' + data.message + '</p>';
            }
            
            output += '</div>';
            return output;
        };
        
        // Run the system cache test
        runPhaseTest('test_system_cache', '#system-cache-result', '#system-cache-status-indicator', 'Phase Two');
    });
    
    // Bind to any existing agent scoring test button as well to ensure it works
    $('#run-agent-scoring-test').on('click', function() {
        console.log('MPAI: Clicked Agent Scoring test button');
        if (typeof runPhaseTest === 'function') {
            runPhaseTest('test_agent_scoring', '#agent-scoring-result', '#agent-scoring-status-indicator', 'Phase Two');
        } else {
            console.error('MPAI: runPhaseTest function not found');
            alert('Test system error: runPhaseTest function not found');
        }
    });
});