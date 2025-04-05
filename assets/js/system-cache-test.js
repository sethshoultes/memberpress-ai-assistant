/**
 * System Information Cache Test Handler
 * 
 * Handles the Phase Two test for System Information Caching
 */
(function($) {
    'use strict';
    
    // Debug flag - set to true for detailed logging
    var debug = true;
    
    // Debug logging function
    function logDebug(message, data) {
        if (debug) {
            if (data !== undefined) {
                console.log('MPAI DEBUG: ' + message, data);
            } else {
                console.log('MPAI DEBUG: ' + message);
            }
        }
    }
    
    // Initialize when document is ready (with extra safeguards)
    $(function() {
        logDebug('Document ready event fired');
        
        // Check if jQuery is available
        if (typeof $ !== 'function') {
            console.error('MPAI ERROR: jQuery is not available');
            return;
        }
        
        // Check if the button exists
        var $button = $('#run-system-cache-test');
        if ($button.length === 0) {
            logDebug('System Cache test button not found in DOM');
            
            // Try again after a short delay
            setTimeout(function() {
                $button = $('#run-system-cache-test');
                if ($button.length > 0) {
                    logDebug('System Cache test button found after delay');
                    bindButtonEvents($button);
                } else {
                    console.error('MPAI ERROR: System Cache test button not found even after delay');
                }
            }, 1000);
        } else {
            logDebug('System Cache test button found immediately');
            bindButtonEvents($button);
        }
    });
    
    // Function to bind events to the button
    function bindButtonEvents($button) {
        logDebug('Binding click event to button', $button);
        
        // Use direct event binding with selector
        $button.on('click', function(e) {
            e.preventDefault();
            logDebug('System Cache test button clicked!');
            runSystemCacheTest();
        });
        
        // Add inline click handler as backup
        $button.attr('onclick', 'jQuery(this).trigger("click"); return false;');
        
        // Check mpai_data availability
        if (typeof window.mpai_data === 'undefined') {
            console.error('MPAI ERROR: mpai_data is not defined');
            return;
        }
        
        logDebug('Button events bound successfully');
    }
    
    // Function to run the system cache test
    function runSystemCacheTest() {
        logDebug('Starting System Cache test');
        
        // Get the UI elements
        var $resultContainer = $('#system-cache-result');
        var $statusIndicator = $('#system-cache-status-indicator');
        
        // Check if UI elements exist
        if ($resultContainer.length === 0) {
            console.error('MPAI ERROR: Result container not found');
            return;
        }
        
        if ($statusIndicator.length === 0) {
            console.error('MPAI ERROR: Status indicator not found');
            return;
        }
        
        // Update UI to show loading state
        $resultContainer.html('<p>Running test...</p>');
        $resultContainer.show();
        
        // Update status indicator
        $statusIndicator.find('.mpai-status-dot')
            .removeClass('mpai-status-unknown mpai-status-success mpai-status-error')
            .addClass('mpai-status-unknown');
        $statusIndicator.find('.mpai-status-text').text('Running...');
        
        // Create form data for request
        var formData = new FormData();
        formData.append('action', 'test_system_cache');
        
        // Get direct handler URL
        var directHandlerUrl = window.mpai_data.plugin_url + 'includes/direct-ajax-handler.php';
        logDebug('Direct handler URL', directHandlerUrl);
        
        // Make the request using fetch API
        logDebug('Sending fetch request');
        fetch(directHandlerUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(function(response) {
            logDebug('Received response', response);
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            return response.json();
        })
        .then(function(data) {
            logDebug('Parsed JSON data', data);
            
            if (data.success) {
                // Update status indicator for success
                $statusIndicator.find('.mpai-status-dot')
                    .removeClass('mpai-status-unknown mpai-status-error')
                    .addClass('mpai-status-success');
                $statusIndicator.find('.mpai-status-text').text('Success');
                
                // Format and display the result
                var formattedResult = formatSystemCacheResult(data.data);
                $resultContainer.html(formattedResult);
                logDebug('Test succeeded, results displayed');
            } else {
                // Update status indicator for failure
                $statusIndicator.find('.mpai-status-dot')
                    .removeClass('mpai-status-unknown mpai-status-success')
                    .addClass('mpai-status-error');
                $statusIndicator.find('.mpai-status-text').text('Failed');
                
                // Display error message
                var errorMessage = data.message || 'Unknown error occurred';
                var formattedError = '<div class="mpai-system-test-result mpai-test-error">';
                formattedError += '<h4>Test Failed</h4>';
                formattedError += '<p>' + errorMessage + '</p>';
                formattedError += '</div>';
                
                $resultContainer.html(formattedError);
                logDebug('Test failed', errorMessage);
            }
        })
        .catch(function(error) {
            console.error('MPAI ERROR: System Cache test error:', error);
            
            // Update status indicator for error
            $statusIndicator.find('.mpai-status-dot')
                .removeClass('mpai-status-unknown mpai-status-success')
                .addClass('mpai-status-error');
            $statusIndicator.find('.mpai-status-text').text('Error');
            
            // Display error message
            var formattedError = '<div class="mpai-system-test-result mpai-test-error">';
            formattedError += '<h4>Test Error</h4>';
            formattedError += '<p>Error executing test: ' + error.message + '</p>';
            formattedError += '</div>';
            
            $resultContainer.html(formattedError);
        });
    }
    
    /**
     * Format the system cache test results
     * 
     * @param {Object} data The test result data
     * @return {string} Formatted HTML
     */
    function formatSystemCacheResult(data) {
        logDebug('Formatting test results', data);
        
        var output = '<div class="mpai-system-test-result mpai-test-success">';
        output += '<h4>System Information Cache Test Results</h4>';
        
        if (data.success) {
            output += '<p class="mpai-test-success-message">' + data.message + '</p>';
            
            // Add test details
            output += '<h5>Test Details:</h5>';
            output += '<table class="mpai-test-results-table">';
            output += '<tr><th>Test</th><th>Result</th><th>Timing</th></tr>';
            
            data.data.tests.forEach(function(test) {
                var resultClass = test.success ? 'mpai-test-success' : 'mpai-test-error';
                var resultText = test.success ? 'PASSED' : 'FAILED';
                
                output += '<tr>';
                output += '<td>' + test.name + '</td>';
                output += '<td class="' + resultClass + '">' + resultText + '</td>';
                
                // Format timing information
                var timing = '';
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
    }
    
    // Log that the script was loaded
    logDebug('System Cache Test script loaded and initialized');
    
})(jQuery);