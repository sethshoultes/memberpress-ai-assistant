/**
 * MemberPress AI Assistant Settings JavaScript
 *
 * Handles settings functionality.
 */
jQuery(document).ready(function($) {
    // Handle test connection button clicks
    $('.mpai-test-connection').on('click', function() {
        var $button = $(this);
        var provider = $button.data('provider');
        var $result = $('#mpai-' + provider + '-test-result');
        var apiKey = $('#mpai_' + provider + '_api_key').val();
        
        // Disable button and show loading
        $button.prop('disabled', true);
        $result.html('<span class="mpai-loading">Testing...</span>');
        
        // Send AJAX request
        $.ajax({
            url: mpai_settings.ajaxurl,
            type: 'POST',
            data: {
                action: 'mpai_test_api_connection',
                nonce: mpai_settings.nonce,
                provider: provider,
                api_key: apiKey
            },
            success: function(response) {
                if (response.success) {
                    $result.html('<span class="mpai-success">' + response.data.message + '</span>');
                } else {
                    $result.html('<span class="mpai-error">' + response.data.message + '</span>');
                }
            },
            error: function() {
                $result.html('<span class="mpai-error">Connection error. Please try again.</span>');
            },
            complete: function() {
                // Re-enable button
                $button.prop('disabled', false);
            }
        });
    });
});