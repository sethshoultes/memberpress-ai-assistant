<?php
/**
 * Integration Tests Diagnostic Section
 *
 * @package MemberPress AI Assistant
 */

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add integration tests section to diagnostics page
 */
function mpai_add_integration_tests_section() {
    ?>
    <!-- Tool Integration Tests Section -->
    <div class="mpai-debug-section">
        <h4><?php _e('Tool Execution Integration Tests', 'memberpress-ai-assistant'); ?></h4>
        <p><?php _e('Verify that MemberPress AI Assistant tools work correctly with WordPress and external systems.', 'memberpress-ai-assistant'); ?></p>
        
        <button type="button" id="mpai-run-tool-execution-tests" class="button button-primary"><?php _e('Run Tool Integration Tests', 'memberpress-ai-assistant'); ?></button>
        
        <div id="mpai-tool-integration-results" class="mpai-debug-results" style="display: none; margin-top: 20px;">
            <div id="mpai-tool-integration-output" class="mpai-test-container"></div>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Tool Execution Integration Tests
        $('#mpai-run-tool-execution-tests').on('click', function() {
            var $resultsContainer = $('#mpai-tool-integration-results');
            var $outputContainer = $('#mpai-tool-integration-output');
            
            $resultsContainer.show();
            $outputContainer.html('<p>Running tool integration tests, please wait...</p>');
            
            // Make an AJAX request to run the integration tests
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'mpai_run_tool_integration_tests',
                    nonce: mpai_data.nonce
                },
                success: function(response) {
                    try {
                        // Display the test results
                        if (response.success) {
                            $outputContainer.html(response.data);
                        } else {
                            $outputContainer.html('<div class="notice notice-error"><p>Error running integration tests: ' + 
                                (response.data || 'Unknown error') + '</p></div>');
                        }
                        
                        // Initialize any JavaScript functionality in the returned HTML
                        // For example, accordion functionality
                        var accordionHeaders = document.querySelectorAll('.mpai-accordion-header');
                        
                        accordionHeaders.forEach(function(header) {
                            header.addEventListener('click', function() {
                                this.classList.toggle('active');
                                var content = this.nextElementSibling;
                                if (content.style.maxHeight) {
                                    content.style.maxHeight = null;
                                } else {
                                    content.style.maxHeight = content.scrollHeight + 'px';
                                }
                            });
                        });
                    } catch (e) {
                        $outputContainer.html('<div class="notice notice-error"><p>Error processing test results: ' + e.message + '</p></div>');
                    }
                },
                error: function(xhr, status, error) {
                    $outputContainer.html('<div class="notice notice-error"><p>AJAX error: ' + error + '</p></div>');
                }
            });
        });
    });
    </script>
    <?php
}

// Hook into a WordPress action to display the diagnostic section
add_action('mpai_run_diagnostics', 'mpai_add_integration_tests_section');