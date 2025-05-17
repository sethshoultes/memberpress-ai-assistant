/**
 * MemberPress AI Assistant Settings JavaScript
 * 
 * Handles AJAX requests for testing API connections and other settings functionality.
 */
jQuery(document).ready(function($) {
    // Test OpenAI API connection
    $('#mpai-test-openai-api').on('click', function() {
        var button = $(this);
        var statusIcon = $('#openai-api-status .mpai-api-status-icon');
        var statusText = $('#openai-api-status .mpai-api-status-text');
        var resultContainer = $('#mpai-openai-test-result');
        
        // Get the API key
        var apiKey = $('#mpai_openai_api_key').val();
        
        if (!apiKey) {
            resultContainer.html('<div class="notice notice-error inline"><p>Please enter an API key first.</p></div>');
            resultContainer.show();
            return;
        }
        
        // Disable button and show loading state
        button.prop('disabled', true);
        button.text('Testing...');
        statusIcon.addClass('loading');
        statusText.text('Testing connection...');
        resultContainer.hide();
        
        // Make AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mpai_test_api_connection',
                service: 'openai',
                _wpnonce: mpai_settings.nonce
            },
            success: function(response) {
                // Reset button
                button.prop('disabled', false);
                button.text('Test Connection');
                statusIcon.removeClass('loading');
                
                if (response.success) {
                    // Success
                    statusIcon.addClass('success').removeClass('error');
                    statusText.text('Connected');
                    resultContainer.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
                    resultContainer.show();
                } else {
                    // Error
                    statusIcon.addClass('error').removeClass('success');
                    statusText.text('Connection Failed');
                    resultContainer.html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
                    resultContainer.show();
                }
            },
            error: function(xhr, status, error) {
                // Reset button
                button.prop('disabled', false);
                button.text('Test Connection');
                statusIcon.removeClass('loading').addClass('error').removeClass('success');
                statusText.text('Connection Failed');
                
                // Show error message
                resultContainer.html('<div class="notice notice-error inline"><p>Error: ' + error + '</p></div>');
                resultContainer.show();
            }
        });
    });
    
    // Test Anthropic API connection
    $('#mpai-test-anthropic-api').on('click', function() {
        var button = $(this);
        var statusIcon = $('#anthropic-api-status .mpai-api-status-icon');
        var statusText = $('#anthropic-api-status .mpai-api-status-text');
        var resultContainer = $('#mpai-anthropic-test-result');
        
        // Get the API key
        var apiKey = $('#mpai_anthropic_api_key').val();
        
        if (!apiKey) {
            resultContainer.html('<div class="notice notice-error inline"><p>Please enter an API key first.</p></div>');
            resultContainer.show();
            return;
        }
        
        // Disable button and show loading state
        button.prop('disabled', true);
        button.text('Testing...');
        statusIcon.addClass('loading');
        statusText.text('Testing connection...');
        resultContainer.hide();
        
        // Make AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mpai_test_api_connection',
                service: 'anthropic',
                _wpnonce: mpai_settings.nonce
            },
            success: function(response) {
                // Reset button
                button.prop('disabled', false);
                button.text('Test Connection');
                statusIcon.removeClass('loading');
                
                if (response.success) {
                    // Success
                    statusIcon.addClass('success').removeClass('error');
                    statusText.text('Connected');
                    resultContainer.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
                    resultContainer.show();
                } else {
                    // Error
                    statusIcon.addClass('error').removeClass('success');
                    statusText.text('Connection Failed');
                    resultContainer.html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
                    resultContainer.show();
                }
            },
            error: function(xhr, status, error) {
                // Reset button
                button.prop('disabled', false);
                button.text('Test Connection');
                statusIcon.removeClass('loading').addClass('error').removeClass('success');
                statusText.text('Connection Failed');
                
                // Show error message
                resultContainer.html('<div class="notice notice-error inline"><p>Error: ' + error + '</p></div>');
                resultContainer.show();
            }
        });
    });
});