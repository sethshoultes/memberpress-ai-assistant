/**
 * MemberPress AI Assistant Admin Scripts
 */

(function($) {
    'use strict';
    
    // Check if mpai_data is available
    if (typeof mpai_data === 'undefined') {
        console.error('MPAI: mpai_data is not available in admin.js');
    } else {
        // Use the logger if available, otherwise fall back to console
        if (window.mpaiLogger) {
            window.mpaiLogger.info('Admin script loaded', 'ui');
            window.mpaiLogger.debug('Admin script loaded with mpai_data nonce: ' + 
                (mpai_data.nonce ? mpai_data.nonce.substring(0, 5) + '...' : 'undefined'), 'ui');
        } else {
            console.log('MPAI: Admin script loaded with mpai_data nonce:', 
                mpai_data.nonce ? mpai_data.nonce.substring(0, 5) + '...' : 'undefined');
        }
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
                    mpai_nonce: mpai_data.nonce,
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
            $.ajax({
                url: mpai_data.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'mpai_reset_conversation',
                    mpai_nonce: mpai_data.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Clear chat
                        $chatMessages.empty();
                        
                        // Add welcome message
                        addMessageToChat('assistant', 'Hello! I\'m your MemberPress AI Assistant. I can help you with your MemberPress site data and suggest WP-CLI commands. How can I assist you today?');
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function() {
                    alert('Failed to reset conversation.');
                }
            });
        }

        // Handle form submission
        $chatForm.on('submit', function(e) {
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
        });

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
                    mpai_nonce: mpai_data.nonce,
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
    
    // Check MemberPress API status on page load
    function checkMemberPressStatus() {
        var apiKey = $('#mpai_memberpress_api_key').val();
        var $statusIcon = $('#memberpress-api-status .mpai-api-status-icon');
        var $statusText = $('#memberpress-api-status .mpai-api-status-text');
        
        if (!apiKey) {
            $statusIcon.removeClass('mpai-status-connected mpai-status-unknown').addClass('mpai-status-disconnected');
            $statusText.text('Not Configured');
            return;
        }
        
        $statusIcon.removeClass('mpai-status-connected mpai-status-disconnected').addClass('mpai-status-unknown');
        $statusText.text('Checking...');
        
        // Create the form data object directly to ensure proper formatting
        var formData = new FormData();
        formData.append('action', 'test_memberpress');
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
            console.error('MPAI: MemberPress status check error:', error);
            $statusIcon.removeClass('mpai-status-connected mpai-status-unknown').addClass('mpai-status-disconnected');
            $statusText.text('Connection Error');
        });
    }

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
        
        // Test MemberPress API Connection
        $('#mpai-test-memberpress-api').on('click', function() {
            var apiKey = $('#mpai_memberpress_api_key').val();
            var $resultContainer = $('#mpai-memberpress-test-result');
            var $statusIcon = $('#memberpress-api-status .mpai-api-status-icon');
            var $statusText = $('#memberpress-api-status .mpai-api-status-text');
            
            console.log('Test MemberPress API clicked with localized nonce');
            
            if (!apiKey) {
                $resultContainer.html('Please enter an API key first');
                $resultContainer.addClass('mpai-test-error').removeClass('mpai-test-success mpai-test-loading');
                $resultContainer.show();
                
                $statusIcon.removeClass('mpai-status-connected mpai-status-unknown').addClass('mpai-status-disconnected');
                $statusText.text('Not Configured');
                return;
            }
            
            // Show loading state
            $(this).prop('disabled', true);
            $resultContainer.html('Testing...');
            $resultContainer.addClass('mpai-test-loading').removeClass('mpai-test-success mpai-test-error');
            $resultContainer.show();
            
            $statusIcon.removeClass('mpai-status-connected mpai-status-disconnected').addClass('mpai-status-unknown');
            $statusText.text('Checking...');
            
            console.log('MPAI: Testing MemberPress API with nonce:', mpai_data.nonce ? mpai_data.nonce.substring(0, 5) + '...' : 'undefined');
            
            // Create the form data object directly to ensure proper formatting
            var formData = new FormData();
            formData.append('action', 'test_memberpress');
            formData.append('nonce', mpai_data.nonce);
            formData.append('api_key', apiKey);
            
            // Use direct AJAX handler
            var directHandlerUrl = mpai_data.plugin_url + 'includes/direct-ajax-handler.php';
            
            console.log('MPAI: FormData prepared with direct AJAX handler and nonce length:', 
                        mpai_data.nonce ? mpai_data.nonce.length : 0);
            console.log('MPAI: Direct handler URL:', directHandlerUrl);
            
            // Use fetch API with direct handler
            fetch(directHandlerUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(function(response) {
                console.log('MPAI: Fetch response status:', response.status);
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.json();
            })
            .then(function(data) {
                console.log('MPAI: MemberPress API test response:', data);
                if (data.success) {
                    $resultContainer.html(data.data);
                    $resultContainer.addClass('mpai-test-success').removeClass('mpai-test-loading mpai-test-error');
                    
                    $statusIcon.removeClass('mpai-status-disconnected mpai-status-unknown').addClass('mpai-status-connected');
                    $statusText.text('Connected');
                } else {
                    $resultContainer.html(data.data);
                    $resultContainer.addClass('mpai-test-error').removeClass('mpai-test-loading mpai-test-success');
                    
                    $statusIcon.removeClass('mpai-status-connected mpai-status-unknown').addClass('mpai-status-disconnected');
                    $statusText.text('Error');
                }
                $('#mpai-test-memberpress-api').prop('disabled', false);
            })
            .catch(function(error) {
                console.error('MPAI: Fetch error:', error);
                $resultContainer.html('Error: ' + error.message);
                $resultContainer.addClass('mpai-test-error').removeClass('mpai-test-loading mpai-test-success');
                $('#mpai-test-memberpress-api').prop('disabled', false);
                
                $statusIcon.removeClass('mpai-status-connected mpai-status-unknown').addClass('mpai-status-disconnected');
                $statusText.text('Connection Error');
            });
        });
    }

    $(document).ready(function() {
        console.log('MPAI: Admin script ready');

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
        if ($('#openai-api-status').length > 0 && $('#memberpress-api-status').length > 0) {
            // Check status after a short delay to ensure everything is loaded
            setTimeout(function() {
                checkOpenAIStatus();
                checkAnthropicStatus();
                checkMemberPressStatus();
            }, 500);
        }
        
        console.log('MPAI: Admin script initialization complete');
    });

})(jQuery);