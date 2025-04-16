/**
 * Diagnostics page JavaScript
 */
(function($) {
    'use strict';
    
    // Initialize diagnostics page
    function initDiagnostics() {
        if (typeof mpai_diagnostics === 'undefined') {
            console.error('MPAI: Diagnostics data not available');
            return;
        }
        
        // Handle test button clicks
        $('.mpai-run-test').on('click', function() {
            runDiagnosticTest($(this).data('test'));
        });
    }
    
    // Run a diagnostic test
    function runDiagnosticTest(testId) {
        // Show loading state
        $('#mpai-test-results-container').html(
            '<div class="mpai-test-loading">' +
            '<p>' + gettext('Running test...') + '</p>' +
            '</div>'
        );
        
        // Scroll to results
        $('html, body').animate({
            scrollTop: $('#mpai-test-results-container').offset().top - 50
        }, 200);
        
        // Run the test via AJAX
        $.ajax({
            url: mpai_diagnostics.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'mpai_run_diagnostics',
                nonce: mpai_diagnostics.nonce,
                test_id: testId
            },
            success: function(response) {
                if (response.success) {
                    displayTestResults(response.data);
                } else {
                    displayTestError(response.data);
                }
            },
            error: function(xhr, status, error) {
                displayTestError('AJAX error: ' + error);
            }
        });
    }
    
    // Display test results
    function displayTestResults(data) {
        const result = data.result;
        const status = result.status || 'error';
        const message = result.message || gettext('No message provided');
        const details = result.details || {};
        
        let html = '<div class="mpai-test-result-header">';
        html += '<h3>' + data.name + '</h3>';
        html += '<span class="mpai-test-result-status mpai-test-result-' + status + '">' + capitalizeFirstLetter(status) + '</span>';
        html += '</div>';
        
        html += '<div class="mpai-test-result-message">';
        html += '<p>' + message + '</p>';
        html += '</div>';
        
        if (Object.keys(details).length > 0) {
            html += '<div class="mpai-test-details-container">';
            html += '<button type="button" class="button mpai-toggle-details">' + gettext('Show Details') + '</button>';
            html += '<div class="mpai-test-details" style="display: none;">';
            html += '<pre>' + JSON.stringify(details, null, 2) + '</pre>';
            html += '</div>';
            html += '</div>';
        }
        
        $('#mpai-test-results-container').html(html);
        
        // Handle details toggle
        $('.mpai-toggle-details').on('click', function() {
            const $details = $('.mpai-test-details');
            const isVisible = $details.is(':visible');
            
            $details.slideToggle(200);
            $(this).text(isVisible ? gettext('Show Details') : gettext('Hide Details'));
        });
    }
    
    // Display test error
    function displayTestError(error) {
        let html = '<div class="mpai-test-result-header">';
        html += '<h3>' + gettext('Test Error') + '</h3>';
        html += '<span class="mpai-test-result-status mpai-test-result-error">Error</span>';
        html += '</div>';
        
        html += '<div class="mpai-test-result-message">';
        html += '<p>' + error + '</p>';
        html += '</div>';
        
        $('#mpai-test-results-container').html(html);
    }
    
    // Helper functions
    function gettext(text) {
        return text; // Placeholder for translation function
    }
    
    function capitalizeFirstLetter(string) {
        return string.charAt(0).toUpperCase() + string.slice(1);
    }
    
    // Initialize when document is ready
    $(document).ready(function() {
        initDiagnostics();
    });
    
})(jQuery);