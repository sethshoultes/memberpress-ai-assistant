/**
 * MemberPress AI Assistant Admin Scripts
 */

(function($) {
    'use strict';
    
    /**
     * Test API connection
     * 
     * @param {string} provider - The provider to test (openai or anthropic)
     */
    function testApiConnection(provider) {
        console.log('Testing API connection for', provider);
        
        const $testButton = provider === 'openai' ? $('#mpai-test-openai-api') : $('#mpai-test-anthropic-api');
        const $resultContainer = provider === 'openai' ? $('#mpai-openai-test-result') : $('#mpai-anthropic-test-result');
        const $statusIcon = provider === 'openai' ? $('#openai-api-status .mpai-api-status-icon') : $('#anthropic-api-status .mpai-api-status-icon');
        const $statusText = provider === 'openai' ? $('#openai-api-status .mpai-api-status-text') : $('#anthropic-api-status .mpai-api-status-text');
        const apiKeyField = provider === 'openai' ? '#mpai_api_key' : '#mpai_anthropic_api_key';
        const apiKey = $(apiKeyField).val();
        
        console.log('Button element exists:', $testButton.length > 0);
        console.log('Result container exists:', $resultContainer.length > 0);
        console.log('API key exists:', apiKey && apiKey.length > 0);
        
        if (!apiKey) {
            $resultContainer.html('Please enter an API key first');
            $resultContainer.addClass('mpai-test-error').removeClass('mpai-test-success');
            $resultContainer.show();
            return;
        }
        
        // Show loading state
        $testButton.prop('disabled', true);
        $resultContainer.html('Testing connection...');
        $resultContainer.removeClass('mpai-test-success mpai-test-error').addClass('mpai-test-loading');
        $resultContainer.show();
        
        $statusIcon.removeClass('mpai-status-connected mpai-status-disconnected').addClass('mpai-status-unknown');
        $statusText.text('Testing...');
        
        console.log('Making AJAX request to', mpai_data.ajax_url);
        console.log('With nonce length:', mpai_data.nonce ? mpai_data.nonce.length : 0);
        
        // Make AJAX request
        $.ajax({
            url: mpai_data.ajax_url,
            type: 'POST',
            data: {
                action: 'mpai_test_api_connection',
                nonce: mpai_data.nonce,
                provider: provider
            },
            success: function(response) {
                console.log('AJAX response:', response);
                
                if (response.success) {
                    // Display the success message
                    var displayText = 'Connection successful!';
                    if (response.data) {
                        displayText = 'Response: ' + response.data;
                    }
                    $resultContainer.html(displayText);
                    $resultContainer.show();
                    $resultContainer.addClass('mpai-test-success').removeClass('mpai-test-loading mpai-test-error');
                    
                    // Update the status indicator
                    $statusIcon.removeClass('mpai-status-unknown mpai-status-disconnected').addClass('mpai-status-connected');
                    $statusText.text('Connected');
                } else {
                    // Display the error message
                    const errorMsg = response.data ? response.data : 'Unknown error occurred';
                    $resultContainer.html('Connection failed: ' + errorMsg);
                    $resultContainer.addClass('mpai-test-error').removeClass('mpai-test-loading mpai-test-success');
                    
                    // Update the status indicator
                    $statusIcon.removeClass('mpai-status-unknown mpai-status-connected').addClass('mpai-status-disconnected');
                    $statusText.text('Error');
                }
                $testButton.prop('disabled', false);
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
                
                $resultContainer.html('Connection failed: ' + error);
                $resultContainer.addClass('mpai-test-error').removeClass('mpai-test-loading mpai-test-success');
                
                $statusIcon.addClass('mpai-status-disconnected').removeClass('mpai-status-unknown mpai-status-connected');
                $statusText.text('Error');
                $testButton.prop('disabled', false);
            }
        });
    }

    /**
     * Initialize the chat functionality
     */
    function initChat() {
        // Check if the old chat interface elements exist
        const $chatForm = $('#mpai-chat-form');
        
        // Return early if the chat form doesn't exist (we're using the floating chat interface)
        if ($chatForm.length === 0) {
            console.log('MPAI: Old chat interface not found, skipping initChat()');
            return;
        }
        
        const $messageInput = $('#mpai-message');
        const $chatMessages = $('#mpai-chat-messages');
        const $resetButton = $('#mpai-reset-conversation');

        /**
         * Send a message to the AI
         * 
         * @param {string} message - The message to send
         */
        function sendMessage(message) {
            // Add user message to chat
            addMessageToChat('user', message);
            
            // Clear input
            $messageInput.val('');
            
            // Add loading indicator
            addLoadingMessage();

            // Send message to server with nonce that matches the server-side check
            $.ajax({
                url: mpai_data.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'mpai_process_chat',
                    nonce: mpai_data.nonce, // Just 'nonce', not 'mpai_nonce'
                    message: message
                },
                success: function(response) {
                    // Remove loading indicator
                    removeLoadingMessage();
                    
                    if (response.success) {
                        // Add AI response to chat
                        addMessageToChat('assistant', response.data.message);
                    } else {
                        // Add error message
                        addMessageToChat('assistant', 'Error: ' + response.data);
                    }
                    
                    // Scroll to bottom
                    scrollChatToBottom();
                },
                error: function() {
                    // Remove loading indicator
                    removeLoadingMessage();
                    
                    // Add error message
                    addMessageToChat('assistant', 'Error: Failed to communicate with the server.');
                    
                    // Scroll to bottom
                    scrollChatToBottom();
                }
            });
        }

        /**
         * Add a message to the chat
         * 
         * @param {string} role - The message role (user or assistant)
         * @param {string} content - The message content
         */
        function addMessageToChat(role, content) {
            const formattedContent = formatMessageContent(content);
            
            const $message = $('<div class="mpai-message mpai-message-' + role + '">' +
                '<div class="mpai-message-content">' + formattedContent + '</div>' +
                '</div>');
            
            $chatMessages.append($message);
            
            // Initialize code highlighting if available
            if (typeof Prism !== 'undefined') {
                Prism.highlightAllUnder($message[0]);
            }
            
            // Scroll to bottom
            scrollChatToBottom();
        }

        /**
         * Format message content with markdown-like syntax
         * 
         * @param {string} content - The message content
         * @return {string} Formatted content
         */
        function formatMessageContent(content) {
            // Convert code blocks
            content = content.replace(/```(\w*)\n([\s\S]*?)\n```/g, function(match, language, code) {
                return '<pre><code class="language-' + (language || 'text') + '">' + 
                    escapeHtml(code.trim()) + '</code></pre>';
            });
            
            // Convert inline code
            content = content.replace(/`([^`]+)`/g, '<code>$1</code>');
            
            // Convert paragraphs
            content = '<p>' + content.replace(/\n\n/g, '</p><p>') + '</p>';
            
            // Convert line breaks
            content = content.replace(/\n/g, '<br>');
            
            return content;
        }

        /**
         * Escape HTML special characters
         * 
         * @param {string} text - The text to escape
         * @return {string} Escaped text
         */
        function escapeHtml(text) {
            return text
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        /**
         * Add loading message
         */
        function addLoadingMessage() {
            $chatMessages.append(
                '<div class="mpai-message mpai-message-assistant mpai-loading-message">' +
                '<div class="mpai-message-content mpai-loading">Thinking</div>' +
                '</div>'
            );
            
            scrollChatToBottom();
        }

        /**
         * Remove loading message
         */
        function removeLoadingMessage() {
            $('.mpai-loading-message').remove();
        }

        /**
         * Scroll chat to bottom
         */
        function scrollChatToBottom() {
            $chatMessages.scrollTop($chatMessages[0].scrollHeight);
        }

        /**
         * Reset conversation
         */
        function resetConversation() {
            // First clear the UI immediately
            $chatMessages.empty();
            $messageInput.val('');
            
            // Show loading indicator immediately
            addLoadingMessage();
            
            // Add a notice that we're resetting
            $chatMessages.append(
                '<div class="mpai-message mpai-message-system">' +
                '<div class="mpai-message-content">Resetting conversation and clearing cached data...</div>' +
                '</div>'
            );
            
            // Now make the API call to reset on the server
            $.ajax({
                url: mpai_data.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'mpai_reset_conversation',
                    mpai_nonce: mpai_data.nonce
                },
                success: function(response) {
                    // Remove loading indicator
                    removeLoadingMessage();
                    
                    // Remove all messages including the system message
                    $chatMessages.empty();
                    
                    if (response.success) {
                        // Log the successful reset
                        console.log('MPAI: Conversation reset successfully');
                        
                        // Add welcome message
                        addMessageToChat('assistant', 'Hello! I\'m your MemberPress AI Assistant. I can help you with your MemberPress site data and suggest WP-CLI commands. How can I assist you today?');
                        
                        // Optionally, refresh the page to ensure all state is cleared
                        // Uncomment the next line if you want a full page refresh
                        // window.location.reload();
                    } else {
                        console.error('MPAI: Failed to reset conversation:', response.data);
                        alert('Error: ' + response.data);
                    }
                },
                error: function(xhr, status, error) {
                    // Remove loading indicator
                    removeLoadingMessage();
                    
                    console.error('MPAI: AJAX error when resetting conversation:', status, error);
                    alert('Failed to reset conversation. Please try refreshing the page.');
                }
            });
        }

        // Handle form submission - we defer to the chat-interface-loader.js handler
        // This handler has been removed to prevent duplication of handlers
        /* $chatForm.on('submit', function(e) {
            e.preventDefault();
            
            // Early return if form exists but message input doesn't
            if (!$messageInput || !$messageInput.length) {
                console.log('MPAI: Message input not found, cannot process form submission');
                return;
            }
            
            // Get message safely
            const message = $messageInput.val().trim();
            
            if (message) { 
                sendMessage(message);
            }
        }); */

        // Handle suggested questions
        $('.mpai-suggestion').on('click', function(e) {
            e.preventDefault();
            
            const message = $(this).text();
            
            $messageInput.val(message);
            $chatForm.submit();
        });

        // Handle reset button
        $resetButton.on('click', function(e) {
            e.preventDefault();
            
            if (confirm('Are you sure you want to reset the conversation?')) {
                resetConversation();
            }
        });
    }

    /**
     * Initialize the command functionality
     */
    function initCommands() {
        // Check if the command interface elements exist
        const $commandForm = $('#mpai-command-form');
        
        // Return early if the command form doesn't exist 
        if ($commandForm.length === 0) {
            console.log('MPAI: Command interface not found, skipping initCommands()');
            return;
        }
        
        const $commandInput = $('#mpai-command');
        const $contextInput = $('#mpai-command-context');
        const $commandResult = $('#mpai-command-result');
        const $commandInsights = $('#mpai-command-insights');

        /**
         * Run a command
         * 
         * @param {string} command - The command to run
         * @param {string} context - Optional context for the command
         */
        function runCommand(command, context) {
            // Clear previous results
            $commandResult.text('Running command...');
            $commandInsights.empty();

            // Run command
            $.ajax({
                url: mpai_data.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'mpai_run_command',
                    nonce: mpai_data.nonce, // Just 'nonce', not 'mpai_nonce'
                    command: command,
                    context: context
                },
                success: function(response) {
                    if (response.success) {
                        // Display command output
                        $commandResult.text(response.data.output || 'Command executed successfully, but no output was returned.');
                        
                        // Display insights
                        if (response.data.insights) {
                            $commandInsights.html(formatMessageContent(response.data.insights));
                        } else {
                            $commandInsights.html('<p>No insights available.</p>');
                        }
                    } else {
                        $commandResult.text('Error: ' + response.data);
                        $commandInsights.html('<p>No insights available due to error.</p>');
                    }
                },
                error: function() {
                    $commandResult.text('Failed to run command. Please try again.');
                    $commandInsights.html('<p>No insights available due to error.</p>');
                }
            });
        }

        /**
         * Format message content with markdown-like syntax
         * 
         * @param {string} content - The message content
         * @return {string} Formatted content
         */
        function formatMessageContent(content) {
            // Convert code blocks
            content = content.replace(/```(\w*)\n([\s\S]*?)\n```/g, function(match, language, code) {
                return '<pre><code class="language-' + (language || 'text') + '">' + 
                    escapeHtml(code.trim()) + '</code></pre>';
            });
            
            // Convert inline code
            content = content.replace(/`([^`]+)`/g, '<code>$1</code>');
            
            // Convert paragraphs
            content = '<p>' + content.replace(/\n\n/g, '</p><p>') + '</p>';
            
            // Convert line breaks
            content = content.replace(/\n/g, '<br>');
            
            return content;
        }

        /**
         * Escape HTML special characters
         * 
         * @param {string} text - The text to escape
         * @return {string} Escaped text
         */
        function escapeHtml(text) {
            return text
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // Handle form submission
        $commandForm.on('submit', function(e) {
            e.preventDefault();
            
            const command = $commandInput.val().trim();
            const context = $contextInput.val().trim();
            
            if (command) {
                runCommand(command, context);
            }
        });
    }

    // Check OpenAI API status on page load
    function checkOpenAIStatus() {
        var apiKey = $('#mpai_api_key').val();
        var $statusIcon = $('#openai-api-status .mpai-api-status-icon');
        var $statusText = $('#openai-api-status .mpai-api-status-text');
        
        if (!apiKey) {
            $statusIcon.removeClass('mpai-status-connected mpai-status-unknown').addClass('mpai-status-disconnected');
            $statusText.text('Not Configured');
            return;
        }
        
        $statusIcon.removeClass('mpai-status-connected mpai-status-disconnected').addClass('mpai-status-unknown');
        $statusText.text('Checking...');
        
        // Create the form data object directly to ensure proper formatting
        var formData = new FormData();
        formData.append('action', 'test_openai');
        formData.append('nonce', mpai_data.nonce);
        formData.append('api_key', apiKey);
        
        // Use the direct AJAX handler
        var directHandlerUrl = mpai_data.plugin_url + 'includes/direct-ajax-handler.php';
        
        fetch(directHandlerUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(function(response) {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            return response.json();
        })
        .then(function(data) {
            if (data.success) {
                $statusIcon.removeClass('mpai-status-disconnected mpai-status-unknown').addClass('mpai-status-connected');
                $statusText.text('Connected');
            } else {
                $statusIcon.removeClass('mpai-status-connected mpai-status-unknown').addClass('mpai-status-disconnected');
                $statusText.text('Error');
            }
        })
        .catch(function(error) {
            console.error('MPAI: OpenAI status check error:', error);
            $statusIcon.removeClass('mpai-status-connected mpai-status-unknown').addClass('mpai-status-disconnected');
            $statusText.text('Connection Error');
        });
    }
    
    // Check Anthropic API status on page load
    function checkAnthropicStatus() {
        var apiKey = $('#mpai_anthropic_api_key').val();
        var $statusIcon = $('#anthropic-api-status .mpai-api-status-icon');
        var $statusText = $('#anthropic-api-status .mpai-api-status-text');
        
        if (!apiKey) {
            $statusIcon.removeClass('mpai-status-connected mpai-status-unknown').addClass('mpai-status-disconnected');
            $statusText.text('Not Configured');
            return;
        }
        
        $statusIcon.removeClass('mpai-status-connected mpai-status-disconnected').addClass('mpai-status-unknown');
        $statusText.text('Checking...');
        
        // Create the form data object directly to ensure proper formatting
        var formData = new FormData();
        formData.append('action', 'test_anthropic');
        formData.append('nonce', mpai_data.nonce);
        formData.append('api_key', apiKey);
        
        // Use the direct AJAX handler
        var directHandlerUrl = mpai_data.plugin_url + 'includes/direct-ajax-handler.php';
        
        fetch(directHandlerUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(function(response) {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            return response.json();
        })
        .then(function(data) {
            if (data.success) {
                $statusIcon.removeClass('mpai-status-disconnected mpai-status-unknown').addClass('mpai-status-connected');
                $statusText.text('Connected');
            } else {
                $statusIcon.removeClass('mpai-status-connected mpai-status-unknown').addClass('mpai-status-disconnected');
                $statusText.text('Error');
            }
        })
        .catch(function(error) {
            console.error('MPAI: Anthropic status check error:', error);
            $statusIcon.removeClass('mpai-status-connected mpai-status-unknown').addClass('mpai-status-disconnected');
            $statusText.text('Connection Error');
        });
    }
    
    // MemberPress API status check removed - not needed

    // Test OpenAI API Connection
    function initApiTests() {
        $('#mpai-test-openai-api').on('click', function() {
            var apiKey = $('#mpai_api_key').val();
            var $resultContainer = $('#mpai-openai-test-result');
            var $statusIcon = $('#openai-api-status .mpai-api-status-icon');
            var $statusText = $('#openai-api-status .mpai-api-status-text');
            
            if (window.mpaiLogger) {
                window.mpaiLogger.info('Testing OpenAI API connection', 'api_calls');
            } else {
                console.log('Test OpenAI clicked with localized nonce');
            }
            
            if (!apiKey) {
                $resultContainer.html('Please enter an API key first');
                $resultContainer.addClass('mpai-test-error').removeClass('mpai-test-success mpai-test-loading');
                $resultContainer.show();
                
                $statusIcon.removeClass('mpai-status-connected mpai-status-unknown').addClass('mpai-status-disconnected');
                $statusText.text('Not Configured');
                
                if (window.mpaiLogger) {
                    window.mpaiLogger.warn('OpenAI API test canceled - no API key provided', 'api_calls');
                }
                return;
            }
            
            // Show loading state
            $(this).prop('disabled', true);
            $resultContainer.html('Testing...');
            $resultContainer.addClass('mpai-test-loading').removeClass('mpai-test-success mpai-test-error');
            $resultContainer.show();
            
            $statusIcon.removeClass('mpai-status-connected mpai-status-disconnected').addClass('mpai-status-unknown');
            $statusText.text('Checking...');
            
            if (window.mpaiLogger) {
                window.mpaiLogger.info('Testing OpenAI API with key: ***' + apiKey.substring(apiKey.length - 4), 'api_calls');
                window.mpaiLogger.startTimer('openai_test');
            } else {
                console.log('MPAI: Testing OpenAI API with nonce:', mpai_data.nonce ? mpai_data.nonce.substring(0, 5) + '...' : 'undefined');
            }
            
            // Create the form data object directly to ensure proper formatting
            var formData = new FormData();
            formData.append('action', 'test_openai');
            formData.append('nonce', mpai_data.nonce);
            formData.append('api_key', apiKey);
            
            // Use direct AJAX handler
            var directHandlerUrl = mpai_data.plugin_url + 'includes/direct-ajax-handler.php';
            
            if (window.mpaiLogger) {
                window.mpaiLogger.debug('Using direct AJAX handler for OpenAI test', 'api_calls', {
                    url: directHandlerUrl,
                    action: 'test_openai',
                    nonceLength: mpai_data.nonce ? mpai_data.nonce.length : 0
                });
            } else {
                console.log('MPAI: FormData prepared with direct AJAX handler and nonce length:', 
                            mpai_data.nonce ? mpai_data.nonce.length : 0);
                console.log('MPAI: Direct handler URL:', directHandlerUrl);
            }
            
            // Use fetch API with direct handler
            fetch(directHandlerUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(function(response) {
                if (window.mpaiLogger) {
                    window.mpaiLogger.debug('OpenAI API test response status: ' + response.status, 'api_calls');
                } else {
                    console.log('MPAI: Fetch response status:', response.status);
                }
                
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.json();
            })
            .then(function(data) {
                if (window.mpaiLogger) {
                    const elapsed = window.mpaiLogger.endTimer('openai_test');
                    window.mpaiLogger.info('OpenAI API test completed in ' + (elapsed ? elapsed.toFixed(2) + 'ms' : 'unknown time'), 'api_calls');
                    window.mpaiLogger.debug('OpenAI API test response data', 'api_calls', data);
                } else {
                    console.log('MPAI: API test response:', data);
                }
                
                if (data.success) {
                    $resultContainer.html(data.data);
                    $resultContainer.addClass('mpai-test-success').removeClass('mpai-test-loading mpai-test-error');
                    
                    $statusIcon.removeClass('mpai-status-disconnected mpai-status-unknown').addClass('mpai-status-connected');
                    $statusText.text('Connected');
                    
                    if (window.mpaiLogger) {
                        window.mpaiLogger.info('OpenAI API connection successful', 'api_calls');
                    }
                } else {
                    $resultContainer.html(data.data);
                    $resultContainer.addClass('mpai-test-error').removeClass('mpai-test-loading mpai-test-success');
                    
                    $statusIcon.removeClass('mpai-status-connected mpai-status-unknown').addClass('mpai-status-disconnected');
                    $statusText.text('Error');
                    
                    if (window.mpaiLogger) {
                        window.mpaiLogger.error('OpenAI API connection failed', 'api_calls', data);
                    }
                }
                $('#mpai-test-openai-api').prop('disabled', false);
            })
            .catch(function(error) {
                if (window.mpaiLogger) {
                    window.mpaiLogger.error('OpenAI API test fetch error', 'api_calls', error);
                    window.mpaiLogger.endTimer('openai_test');
                } else {
                    console.error('MPAI: Fetch error:', error);
                }
                
                $resultContainer.html('Error: ' + error.message);
                $resultContainer.addClass('mpai-test-error').removeClass('mpai-test-loading mpai-test-success');
                $('#mpai-test-openai-api').prop('disabled', false);
                
                $statusIcon.removeClass('mpai-status-connected mpai-status-unknown').addClass('mpai-status-disconnected');
                $statusText.text('Connection Error');
            });
        });
        
        // MemberPress API test handler removed - not needed
    }

    /**
     * Initialize the console logging settings functionality
     */
    function initConsoleLoggingSettings() {
        // Check if console logging UI elements exist
        const $testConsoleLoggingButton = $('#mpai-test-console-logging');
        const $enableLoggingCheckbox = $('#mpai_enable_console_logging');
        
        if (!$testConsoleLoggingButton.length) {
            console.log('MPAI: Console logging UI not found on page');
            return;
        }
        
        console.log('MPAI: Initializing console logging settings');
        
        // Add direct handler for the checkbox to immediately update logger
        if ($enableLoggingCheckbox.length) {
            $enableLoggingCheckbox.on('change', function() {
                const isChecked = $(this).is(':checked');
                
                // Extra logging to debug the issue
                console.log('MPAI DEBUG: Checkbox changed - isChecked =', isChecked);
                console.log('MPAI DEBUG: Checkbox value =', $(this).val());
                console.log('MPAI DEBUG: Current logger enabled state =', 
                    window.mpaiLogger ? window.mpaiLogger.enabled : 'mpaiLogger not available');
                
                // Update visual indicator
                const $statusIndicator = $('#mpai-console-logging-status');
                if ($statusIndicator.length) {
                    if (isChecked) {
                        $statusIndicator.removeClass('inactive').addClass('active').text('ENABLED');
                    } else {
                        $statusIndicator.removeClass('active').addClass('inactive').text('DISABLED');
                    }
                }
                
                // Update mpaiLogger immediately when checkbox changes with a strict value
                if (window.mpaiLogger) {
                    // Set to exactly true or false for strict type checking
                    window.mpaiLogger.enabled = isChecked === true;
                    console.log('MPAI: Console logging ' + (isChecked ? 'ENABLED' : 'DISABLED') + ' via checkbox');
                    console.log('MPAI: New logger.enabled value (type: ' + typeof window.mpaiLogger.enabled + '): ' + window.mpaiLogger.enabled);
                    
                    // Run a test log to verify
                    if (isChecked) {
                        console.log('MPAI: Testing enabled logger...');
                        window.mpaiLogger.info('Console logging enabled via checkbox', 'ui');
                    } else {
                        console.log('MPAI: Testing disabled logger...');
                        console.log('MPAI: This direct console.log should appear, but no mpaiLogger messages should appear');
                        window.mpaiLogger.info('This message should NOT appear in console', 'ui');
                    }
                    
                    // Save to localStorage to persist setting
                    try {
                        localStorage.setItem('mpai_logger_settings', JSON.stringify({
                            enabled: isChecked === true, // Ensure boolean
                            logLevel: window.mpaiLogger.logLevel,
                            categories: window.mpaiLogger.categories
                        }));
                        console.log('MPAI: Saved enabled state to localStorage:', isChecked === true);
                        
                        // Also send to server for persistence across sessions
                        saveSyncConsoleSettings();
                    } catch(e) {
                        console.error('MPAI: Error saving to localStorage:', e);
                    }
                }
                
                // Function to synchronize settings with server
                function saveSyncConsoleSettings() {
                    // Only run if we have mpai_data with the direct handler URL
                    if (typeof mpai_data === 'undefined' || !mpai_data.plugin_url) {
                        console.log('MPAI: Cannot sync console settings - missing mpai_data');
                        return;
                    }
                    
                    // Prepare form data for server sync
                    var formData = new FormData();
                    formData.append('action', 'test_console_logging');
                    formData.append('enable_logging', isChecked ? '1' : '0');
                    formData.append('save_settings', '1');
                    
                    // Use the direct AJAX handler to avoid admin-ajax issues
                    var directHandlerUrl = mpai_data.plugin_url + 'includes/direct-ajax-handler.php';
                    
                    // Send the sync request silently
                    fetch(directHandlerUrl, {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin'
                    })
                    .then(function(response) {
                        if (!response.ok) {
                            console.error('MPAI: Error syncing console settings: Network error', response.status);
                            return;
                        }
                        return response.json();
                    })
                    .then(function(data) {
                        if (data && data.success) {
                            console.log('MPAI: Console settings synced successfully with server');
                        } else {
                            console.error('MPAI: Error syncing console settings:', data);
                        }
                    })
                    .catch(function(error) {
                        console.error('MPAI: Error syncing console settings:', error);
                    });
                }
            });
        }
        
        // Handle the Test Console Logging button
        $testConsoleLoggingButton.on('click', function() {
            var $resultContainer = $('#mpai-console-test-result');
            
            // Show loading state
            $resultContainer.html('Testing...');
            $resultContainer.show();
            
            // Log messages directly to console for testing
            console.log('MPAI: Running console logging test');
            
            // Get current settings from form
            var settings = {
                enabled: $('#mpai_enable_console_logging').is(':checked'),
                logLevel: $('#mpai_console_log_level').val(),
                categories: {
                    api_calls: true, // Always enabled
                    tool_usage: true, // Always enabled
                    agent_activity: true, // Always enabled
                    timing: true, // Always enabled
                    ui: true // Always enable UI logging for tests
                }
            };
            
            // Debug info to ensure correct values are being used
            console.log('MPAI DEBUG: Settings from form:', {
                enabled: settings.enabled,
                logLevel: settings.logLevel,
                categories: settings.categories
            });
            
            // Create form data for AJAX request
            var formData = new FormData();
            formData.append('action', 'test_console_logging');
            
            // Ensure we're sending a string '0' when disabled, not boolean false
            var enabledValue = settings.enabled ? '1' : '0';
            console.log('MPAI DEBUG: enable_logging value being sent:', enabledValue);
            formData.append('enable_logging', enabledValue);
            
            formData.append('log_level', settings.logLevel);
            formData.append('log_api_calls', '1'); // Always enabled
            formData.append('log_tool_usage', '1'); // Always enabled
            formData.append('log_agent_activity', '1'); // Always enabled
            formData.append('log_timing', '1'); // Always enabled
            formData.append('save_settings', '1');
            
            // Use the direct AJAX handler
            var directHandlerUrl = mpai_data.plugin_url + 'includes/direct-ajax-handler.php';
            
            // Send the request
            fetch(directHandlerUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(function(response) {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.json();
            })
            .then(function(data) {
                console.log('MPAI: Console test response:', data);
                
                if (data.success) {
                    // Run direct console test
                    runConsoleTest(settings);
                    
                    // Show test results with clear indication of enabled/disabled state
                    var resultHtml = '<span style="color: green; font-weight: bold;">✓ Console Log Test Completed</span><br>';
                    resultHtml += '<br><strong>Settings:</strong><br>';
                    
                    // Show enabled/disabled status very clearly 
                    resultHtml += 'Console Logging: <span style="font-weight:bold; color:' + 
                        (settings.enabled ? 'green' : 'red') + '">' + 
                        (settings.enabled ? 'ENABLED' : 'DISABLED') + '</span><br>';
                        
                    resultHtml += 'Log Level: ' + settings.logLevel + '<br>';
                    
                    // Show enabled categories
                    resultHtml += 'Categories: ';
                    var enabledCategories = [];
                    for (var cat in settings.categories) {
                        if (settings.categories[cat]) {
                            enabledCategories.push(cat);
                        }
                    }
                    
                    if (enabledCategories.length > 0) {
                        resultHtml += enabledCategories.join(', ');
                    } else {
                        resultHtml += 'None enabled';
                    }
                    
                    resultHtml += '<br><br>';
                    
                    // Special note when logging is disabled
                    if (!settings.enabled) {
                        resultHtml += '<div style="padding: 10px; background-color: #fff8e5; border-left: 4px solid #ffb900; margin-bottom: 10px;">' +
                            '<strong>Note:</strong> Console logging is currently <strong>DISABLED</strong>. ' +
                            'Only direct console messages will appear in your browser console. ' +
                            'The logger system will not log any messages.' +
                            '</div>';
                    }
                    
                    resultHtml += 'Check your browser\'s console (F12) for test log messages.';
                    
                    $resultContainer.html(resultHtml);
                    
                    // Update any active logger with new settings
                    if (window.mpaiLogger) {
                        console.log('MPAI: Applying new settings to active logger');
                        
                        // Strict set to boolean true or false
                        window.mpaiLogger.enabled = settings.enabled === true;
                        window.mpaiLogger.logLevel = settings.logLevel;
                        window.mpaiLogger.categories = settings.categories;
                        
                        // Log the new state to verify it changed
                        console.log('MPAI: Updated logger enabled state to', window.mpaiLogger.enabled);
                    }
                    
                    // Save settings to localStorage
                    try {
                        localStorage.setItem('mpai_logger_settings', JSON.stringify({
                            enabled: settings.enabled,
                            logLevel: settings.logLevel,
                            categories: settings.categories
                        }));
                        console.log('MPAI: Saved console settings to localStorage');
                    } catch(e) {
                        console.error('MPAI: Error saving to localStorage:', e);
                    }
                } else {
                    $resultContainer.html('<span style="color: red;">Error: ' + data.message + '</span>');
                }
            })
            .catch(function(error) {
                console.error('MPAI: Error in console test:', error);
                $resultContainer.html('<span style="color: red;">Error: ' + error.message + '</span>');
            });
        });
        
        // Function to run console test
        function runConsoleTest(settings) {
            console.group('MPAI Console Test');
            
            console.log('Logger Raw Settings:', settings);
            console.log('Enabled Value Type:', typeof settings.enabled);
            console.log('Logger Status: ' + (settings.enabled ? 'Enabled' : 'Disabled'));
            console.log('Log Level: ' + settings.logLevel);
            console.log('Categories:', settings.categories);
            
            // Test direct console logging (without mpaiLogger)
            console.log('=== Direct Console Logging Test (No Logger) ===');
            
            // These messages will always appear, as they don't use the logger
            console.error('🔴 DIRECT TEST: This is an ERROR test message');
            console.warn('🟡 DIRECT TEST: This is a WARNING test message');
            console.info('🔵 DIRECT TEST: This is an INFO test message');
            console.log('⚪ DIRECT TEST: This is a regular LOG test message');
            
            // Test logger-based console logging (these should respect settings)
            console.log('=== Logger-Based Test (Should respect settings) ===');
            
            // Test API calls category
            console.log('MPAI TEST: API Call Test', {
                'endpoint': '/api/test',
                'method': 'POST',
                'status': 200
            });
            
            // Test tool usage category
            console.log('MPAI TEST: Tool Usage Test', {
                'tool': 'test_tool',
                'parameters': {'param1': 'value1'},
                'result': 'success'
            });
            
            // Test agent activity category
            console.log('MPAI TEST: Agent Activity Test', {
                'agent': 'test_agent',
                'action': 'process',
                'status': 'completed'
            });
            
            // Test timing category
            console.time('MPAI TEST: Timer Test');
            setTimeout(function() {
                console.timeEnd('MPAI TEST: Timer Test');
            }, 50);
            
            console.groupEnd();
        }
    }

    /**
     * Initialize the consent mechanism
     */
    function initConsent() {
        const $consentCheckbox = $('#mpai-consent-checkbox');
        const $openChatButton = $('#mpai-open-chat');
        const $welcomeButtons = $('#mpai-welcome-buttons');
        const $termsLink = $('#mpai-terms-link');
        
        // Skip if no consent UI on page
        if (!$consentCheckbox.length) {
            console.log('MPAI: Consent UI not found on page');
            return;
        }
        
        // If already checked, make it read-only
        if ($consentCheckbox.prop('checked')) {
            $consentCheckbox.prop('disabled', true);
            $welcomeButtons.removeClass('consent-required');
            $openChatButton.prop('disabled', false);
            
            // Add info text about the permanent nature of the consent
            const $consentInfo = $('<p>', {
                class: 'mpai-consent-info',
                html: 'You have agreed to the terms. This agreement can only be revoked by deactivating and reactivating the plugin.'
            });
            
            $consentCheckbox.closest('label').after($consentInfo);
        }
        
        // Handle checkbox change - only allow checking, not unchecking
        $consentCheckbox.on('change', function() {
            const isChecked = $(this).prop('checked');
            
            // Only proceed if they are checking the box
            if (isChecked) {
                // Update UI
                $welcomeButtons.removeClass('consent-required');
                $openChatButton.prop('disabled', false);
                
                // Make the checkbox read-only once checked
                $consentCheckbox.prop('disabled', true);
                
                // Add info text
                const $consentInfo = $('<p>', {
                    class: 'mpai-consent-info',
                    html: 'You have agreed to the terms. This agreement can only be revoked by deactivating and reactivating the plugin.'
                });
                
                $consentCheckbox.closest('label').after($consentInfo);
                
                // Save consent to server and initialize chat interface
                $.ajax({
                    url: mpai_data.ajax_url,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'mpai_save_consent',
                        nonce: mpai_chat_data.nonce, // Use the chat nonce
                        consent: true // Always save as true
                    },
                    success: function(response) {
                        if (window.mpaiLogger) {
                            window.mpaiLogger.info('Consent saved successfully', 'ui');
                        } else {
                            console.log('MPAI: Consent saved successfully');
                        }
                        
                        // Dynamically load the chat interface if it's not already there
                        if (!$('#mpai-chat-container').length) {
                            // Reload the page to ensure the chat interface is properly loaded
                            window.location.reload();
                        } else {
                            // If chat interface exists but is not visible, make it visible
                            $('#mpai-chat-container').show();
                            $('#mpai-chat-toggle').show();
                        }
                    },
                    error: function(xhr, status, error) {
                        if (window.mpaiLogger) {
                            window.mpaiLogger.error('Error saving consent: ' + error, 'ui');
                        } else {
                            console.error('MPAI: Error saving consent:', error);
                        }
                    }
                });
            } else {
                // If they somehow try to uncheck, prevent it
                $consentCheckbox.prop('checked', true);
            }
        });
        
        // Handle terms link click (show terms modal)
        $termsLink.on('click', function(e) {
            e.preventDefault();
            
            // Create and show modal if it doesn't exist
            if (!$('#mpai-terms-modal').length) {
                const $modal = $('<div>', {
                    id: 'mpai-terms-modal',
                    class: 'mpai-terms-modal'
                }).appendTo('body');
                
                const $modalContent = $('<div>', {
                    class: 'mpai-terms-modal-content'
                }).appendTo($modal);
                
                $('<h2>').text('MemberPress AI Terms & Conditions').appendTo($modalContent);
                
                $('<div>', {
                    class: 'mpai-terms-content'
                }).html(`
                    <p>By using the MemberPress AI Assistant, you agree to the following terms:</p>
                    <ol>
                        <li>The AI Assistant is provided "as is" without warranties of any kind.</li>
                        <li>The AI may occasionally provide incorrect or incomplete information.</li>
                        <li>You are responsible for verifying any information provided by the AI.</li>
                        <li>MemberPress is not liable for any actions taken based on AI recommendations.</li>
                        <li>Your interactions with the AI Assistant may be logged for training and improvement purposes.</li>
                    </ol>
                    <p>For complete terms, please refer to the MemberPress Terms of Service.</p>
                `).appendTo($modalContent);
                
                $('<button>', {
                    class: 'button button-primary',
                    text: 'Close'
                }).on('click', function() {
                    $modal.hide();
                }).appendTo($modalContent);
            }
            
            $('#mpai-terms-modal').show();
        });
    }

    // Initialize the API test buttons
    function initApiTestButtons() {
        console.log('Initializing API test buttons');
        console.log('OpenAI test button exists:', $('#mpai-test-openai-api').length > 0);
        console.log('Anthropic test button exists:', $('#mpai-test-anthropic-api').length > 0);
        
        // Using event delegation to ensure the buttons work even if they're loaded later
        $(document).on('click', '#mpai-test-openai-api', function(e) {
            e.preventDefault();
            console.log('OpenAI test button clicked');
            testApiConnection('openai');
        });
        
        $(document).on('click', '#mpai-test-anthropic-api', function(e) {
            e.preventDefault();
            console.log('Anthropic test button clicked');
            testApiConnection('anthropic');
        });
    }
    
    $(document).ready(function() {
        // Initialize API test buttons
        initApiTestButtons();
        
        // Clear existing chat history button
        $('#mpai-clear-chat-history').on('click', function(e) {
            e.preventDefault();
            
            if (confirm('Are you sure you want to clear your chat history? This cannot be undone.')) {
                $(this).addClass('updating-message').prop('disabled', true);
                
                $.ajax({
                    url: mpai_data.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'mpai_clear_chat_history',
                        nonce: mpai_data.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Chat history cleared successfully!');
                            // Refresh the page to ensure UI is updated
                            window.location.reload();
                        } else {
                            alert('Error clearing chat history: ' + (response.data || 'Unknown error'));
                        }
                    },
                    error: function() {
                        alert('Network error occurred while clearing chat history.');
                    },
                    complete: function() {
                        $('#mpai-clear-chat-history').removeClass('updating-message').prop('disabled', false);
                    }
                });
            }
        });
        
        // Verify direct-ajax-handler.php URL (for debugging)
        if (typeof mpai_data !== 'undefined' && mpai_data.plugin_url) {
            var directHandlerUrl = mpai_data.plugin_url + 'includes/direct-ajax-handler.php';
            console.log('MPAI: Direct AJAX handler URL:', directHandlerUrl);
        } else {
            console.error('MPAI: mpai_data is not defined or missing plugin_url');
        }
        
        // Initialize consent mechanism
        initConsent();
        
        // Initialize console logging settings
        initConsoleLoggingSettings();

        // Check if mpai_data includes plugin_url, add it if missing
        if (typeof mpai_data !== 'undefined' && !mpai_data.plugin_url) {
            // Try to get plugin URL from the page
            var scriptPath = $('script[src*="memberpress-ai-assistant"]').attr('src');
            if (scriptPath) {
                var pluginUrl = scriptPath.split('/assets/')[0] + '/';
                console.log('MPAI: Setting plugin_url from script tag:', pluginUrl);
                mpai_data.plugin_url = pluginUrl;
            } else {
                // Fallback to current site URL + plugin path
                mpai_data.plugin_url = window.location.origin + '/wp-content/plugins/memberpress-ai-assistant/';
                console.log('MPAI: Setting fallback plugin_url:', mpai_data.plugin_url);
            }
        }
        
        // Initialize chat (only if the old chat interface exists)
        initChat();
        
        // Initialize commands (only if the command interface exists)
        initCommands();
        
        // Initialize API tests and status checks
        initApiTests();
        
        // Initialize API status indicators on page load if we're on the settings page
        if ($('#openai-api-status').length > 0) {
            // Check status after a short delay to ensure everything is loaded
            setTimeout(function() {
                // Only check OpenAI and Anthropic APIs by default
                checkOpenAIStatus();
                checkAnthropicStatus();
                
                // MemberPress API check removed - not needed
            }, 500);
        }
        
        // Check if the diagnostic tab is already active and trigger load if it is
        if (window.location.hash === '#tab-diagnostic' || $('.nav-tab-wrapper a.nav-tab-active[href="#tab-diagnostic"]').length > 0) {
            console.log('MPAI: Diagnostic tab is active on page load, triggering plugin logs load');
            setTimeout(function() {
                $(document).trigger('mpai-load-plugin-logs');
            }, 100);
        }
        
        console.log('MPAI: Admin script initialization complete');
    });

})(jQuery);