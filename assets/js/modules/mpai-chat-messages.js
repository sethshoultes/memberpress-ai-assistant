/**
 * MemberPress AI Assistant - Chat Messages Module
 * 
 * Handles message processing, formatting, and display
 */

var MPAI_Messages = (function($) {
    'use strict';
    
    // Private variables
    var elements = {};
    var pendingToolCalls = false;
    
    /**
     * Initialize the module
     * 
     * @param {Object} domElements - DOM elements
     */
    function init(domElements) {
        elements = domElements;
        
        if (window.mpaiLogger) {
            window.mpaiLogger.info('Messages module initialized', 'ui');
        }
    }
    
    /**
     * Add a message to the chat
     * 
     * @param {string} role - The role of the message sender (user/assistant)
     * @param {string} content - The message content
     * @param {Object} options - Additional options
     * @param {boolean} options.isHistory - Whether this is from history
     * @param {string} options.timestamp - Optional timestamp
     * @return {jQuery} The message element
     */
    function addMessage(role, content, options = {}) {
        const isHistory = options.isHistory || false;
        const timestamp = options.timestamp || new Date().toISOString();
        
        // Create message container
        const messageId = 'mpai-message-' + Date.now();
        const $message = $('<div>', {
            'class': 'mpai-chat-message mpai-chat-message-' + role,
            'id': messageId,
            'data-role': role,
            'data-timestamp': timestamp
        });
        
        // Create message content container
        const $content = $('<div>', {
            'class': 'mpai-chat-message-content'
        });
        
        // Format the message content
        $content.html(formatMessage(content));
        
        // Add content to message
        $message.append($content);
        
        // Add message actions for assistant messages
        if (role === 'assistant') {
            const $actions = $('<div>', {
                'class': 'mpai-message-actions'
            });
            
            // Export button
            const $exportBtn = $('<button>', {
                'class': 'mpai-message-action mpai-export-message',
                'title': 'Export message',
                'data-message-id': messageId
            }).html('<span class="dashicons dashicons-download"></span>');
            
            // Copy button
            const $copyBtn = $('<button>', {
                'class': 'mpai-message-action mpai-copy-message',
                'title': 'Copy message',
                'data-message-id': messageId
            }).html('<span class="dashicons dashicons-clipboard"></span>');
            
            $actions.append($exportBtn, $copyBtn);
            $message.append($actions);
            
            // Set up click handlers
            $exportBtn.on('click', function() {
                if (window.MPAI_History && typeof window.MPAI_History.exportMessage === 'function') {
                    window.MPAI_History.exportMessage(messageId);
                }
            });
            
            $copyBtn.on('click', function() {
                copyMessageToClipboard(messageId);
            });
        }
        
        // Add to chat messages container
        elements.chatMessages.append($message);
        
        // Scroll to the bottom only if not loading history
        if (!isHistory) {
            if (window.MPAI_UIUtils && typeof window.MPAI_UIUtils.scrollToBottom === 'function') {
                window.MPAI_UIUtils.scrollToBottom();
            }
        }
        
        return $message;
    }
    
    /**
     * Format message content
     * 
     * @param {string} content - The raw message content
     * @return {string} Formatted HTML content
     */
    function formatMessage(content) {
        if (!content) {
            return '';
        }
        
        // Check if content is already HTML
        if (content.indexOf('<') !== -1 && content.indexOf('>') !== -1) {
            return content;
        }
        
        // Use the comprehensive formatter from MPAI_Formatters module if available
        if (window.MPAI_Formatters && typeof window.MPAI_Formatters.formatMessage === 'function') {
            if (window.mpaiLogger) {
                window.mpaiLogger.debug('Using comprehensive formatter from MPAI_Formatters', 'ui');
            }
            return window.MPAI_Formatters.formatMessage(content);
        }
        
        // Fallback to basic formatting if the formatter module isn't available
        
        // Convert markdown-style code blocks to HTML
        content = content.replace(/```(\w+)?\n([\s\S]*?)\n```/g, function(match, language, code) {
            language = language || '';
            return '<pre><code class="language-' + language + '">' + 
                   escapeHtml(code) + 
                   '</code></pre>';
        });
        
        // Convert inline code
        content = content.replace(/`([^`]+)`/g, '<code>$1</code>');
        
        // Convert markdown-style links to HTML
        content = content.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank">$1</a>');
        
        // Convert line breaks to HTML
        content = content.replace(/\n/g, '<br>');
        
        return content;
    }
    
    /**
     * Show typing indicator
     */
    function showTypingIndicator() {
        const $typingIndicator = $('.mpai-typing-indicator');
        
        if ($typingIndicator.length === 0) {
            const $indicator = $('<div>', {
                'class': 'mpai-chat-message mpai-chat-message-assistant mpai-typing-indicator'
            }).html('<div class="mpai-chat-message-content">' + 
                   mpai_chat_data.strings.typing + 
                   '<span class="mpai-typing-dots"><span>.</span><span>.</span><span>.</span></span>' + 
                   '</div>');
            
            elements.chatMessages.append($indicator);
        } else {
            $typingIndicator.show();
        }
        
        if (window.MPAI_UIUtils && typeof window.MPAI_UIUtils.scrollToBottom === 'function') {
            window.MPAI_UIUtils.scrollToBottom();
        }
    }
    
    /**
     * Hide typing indicator
     */
    function hideTypingIndicator() {
        $('.mpai-typing-indicator').remove();
    }
    
    /**
     * Send a message to the server
     * 
     * @param {string} message - The message to send
     */
    function sendMessage(message) {
        if (!message.trim()) {
            if (window.mpaiLogger) {
                window.mpaiLogger.warn('Attempted to send empty message', 'api_calls');
            }
            return;
        }
        
        // Check if the message is requesting to write a blog post or page
        // and enhance it with XML formatting if MPAI_BlogFormatter is available
        let enhancedMessage = message;
        if (window.MPAI_BlogFormatter && typeof window.MPAI_BlogFormatter.enhanceUserPrompt === 'function') {
            // Check for blog post creation requests
            if (/write(\s+a)?\s+blog(\s+post)?|create(\s+a)?\s+blog(\s+post)?/i.test(message) && 
                !message.includes('<wp-post>')) {
                
                if (window.mpaiLogger) {
                    window.mpaiLogger.info('Detected blog post creation request, enhancing with XML format', 'ui');
                }
                
                // Use the blog formatter to enhance the prompt, but don't send it directly
                window.MPAI_BlogFormatter.enhanceUserPrompt(message, 'blog-post');
                return; // The enhanceUserPrompt function will send the message
            }
            
            // Check for page creation requests
            if (/write(\s+a)?\s+page|create(\s+a)?\s+page/i.test(message) && 
                !message.includes('<wp-post>')) {
                
                if (window.mpaiLogger) {
                    window.mpaiLogger.info('Detected page creation request, enhancing with XML format', 'ui');
                }
                
                // Use the blog formatter to enhance the prompt, but don't send it directly
                window.MPAI_BlogFormatter.enhanceUserPrompt(message, 'page');
                return; // The enhanceUserPrompt function will send the message
            }
        }
        
        // Log the message being sent with comprehensive details
        if (window.mpaiLogger) {
            window.mpaiLogger.info('Sending user message: ' + message.substring(0, 50) + (message.length > 50 ? '...' : ''), 'api_calls');
            window.mpaiLogger.startTimer('message_processing');
            window.mpaiLogger.logApiCall('OpenAI/Anthropic', 'chat completions', {
                message: message.substring(0, 100) + (message.length > 100 ? '...' : ''),
                messageLength: message.length,
                timestamp: new Date().toISOString(),
                messageId: 'msg_' + Date.now()
            });
            window.mpaiLogger.debug('Message details', 'api_calls', {
                wordCount: message.split(/\s+/).length,
                containsCode: message.includes('```'),
                containsQuestion: message.includes('?'),
                containsURL: /https?:\/\/[^\s]+/.test(message)
            });
        }
        
        // Add the user message to the chat
        addMessage('user', message);
        
        // Clear the input
        elements.chatInput.val('');
        
        // Adjust the height of the input
        if (window.MPAI_UIUtils && typeof window.MPAI_UIUtils.adjustInputHeight === 'function') {
            window.MPAI_UIUtils.adjustInputHeight();
        }
        
        // Show typing indicator
        showTypingIndicator();
        
        // Send the message to the server using AJAX
        $.ajax({
            url: mpai_chat_data.ajax_url,
            type: 'POST',
            data: {
                action: 'mpai_process_chat',
                message: message,
                nonce: mpai_chat_data.nonce,
            },
            success: function(response) {
                // End timing for message processing
                if (window.mpaiLogger) {
                    window.mpaiLogger.endTimer('message_processing');
                }
                
                if (response.success) {
                    // Hide typing indicator
                    hideTypingIndicator();
                    
                    // Process the response
                    processResponse(response.data.response);
                } else {
                    // Show error message
                    hideTypingIndicator();
                    addMessage('assistant', mpai_chat_data.strings.error_message);
                    
                    // Log the error
                    if (window.mpaiLogger) {
                        window.mpaiLogger.error('Error processing message: ' + (response.data || 'Unknown error'), 'api_calls');
                    }
                }
            },
            error: function(xhr, status, error) {
                // End timing for message processing
                if (window.mpaiLogger) {
                    window.mpaiLogger.endTimer('message_processing');
                }
                
                // Hide typing indicator
                hideTypingIndicator();
                
                // Show error message
                addMessage('assistant', mpai_chat_data.strings.error_message);
                
                // Log the error
                if (window.mpaiLogger) {
                    window.mpaiLogger.error('AJAX error: ' + error, 'api_calls');
                }
            }
        });
    }
    
    /**
     * Process AI response
     * 
     * @param {string} response - The response from the AI
     */
    function processResponse(response) {
        // Start processing timing
        if (window.mpaiLogger) {
            window.mpaiLogger.startTimer('process_response');
            window.mpaiLogger.debug('Processing AI response', 'api_calls', {
                responseLength: response ? response.length : 0,
                responseType: typeof response
            });
        }
        
        // Check if the response contains tool calls
        if (window.MPAI_Tools && typeof window.MPAI_Tools.processToolCalls === 'function') {
            if (window.mpaiLogger) {
                window.mpaiLogger.debug('Checking for tool calls in response', 'tool_usage');
            }
            
            const hasToolCalls = window.MPAI_Tools.processToolCalls(response);
            pendingToolCalls = hasToolCalls;
            
            // If there are tool calls, don't add the response message yet
            if (hasToolCalls) {
                if (window.mpaiLogger) {
                    const elapsed = window.mpaiLogger.endTimer('process_response');
                    window.mpaiLogger.info('Response contains tool calls - deferring message display', 'tool_usage', {
                        processingTimeMs: elapsed
                    });
                }
                return;
            }
        }
        
        // Add the assistant message to the chat
        if (window.mpaiLogger) {
            const elapsed = window.mpaiLogger.endTimer('process_response');
            window.mpaiLogger.info('Adding assistant response to chat', 'ui', {
                processingTimeMs: elapsed,
                responseLength: response ? response.length : 0,
                hasHtml: response && (response.includes('<') && response.includes('>')),
                hasCodeBlocks: response && response.includes('```')
            });
        }
        
        const $message = addMessage('assistant', response);
        
        // Process the message with blog formatter if available
        if (window.MPAI_BlogFormatter && typeof window.MPAI_BlogFormatter.processAssistantMessage === 'function') {
            window.MPAI_BlogFormatter.processAssistantMessage($message, response);
        }
    }
    
    /**
     * Escape HTML special characters
     * 
     * @param {string} html - The HTML to escape
     * @return {string} Escaped HTML
     */
    function escapeHtml(html) {
        const div = document.createElement('div');
        div.textContent = html;
        return div.innerHTML;
    }
    
    /**
     * Copy message content to clipboard
     * 
     * @param {string} messageId - The ID of the message to copy
     */
    function copyMessageToClipboard(messageId) {
        const $message = $('#' + messageId);
        const content = $message.find('.mpai-chat-message-content').text();
        
        if (window.mpaiLogger) {
            window.mpaiLogger.info('Copying message content to clipboard: ' + messageId, 'ui');
        }
        
        // Use the modern clipboard API if available
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(content)
                .then(() => {
                    // Show success confirmation
                    const $copyBtn = $message.find('.mpai-copy-message');
                    const $originalIcon = $copyBtn.html();
                    
                    $copyBtn.html('<span class="dashicons dashicons-yes"></span>');
                    
                    setTimeout(function() {
                        $copyBtn.html($originalIcon);
                    }, 2000);
                })
                .catch(err => {
                    console.error('Failed to copy text: ', err);
                    fallbackCopyToClipboard(content);
                });
        } else {
            // Fallback to older execCommand method
            fallbackCopyToClipboard(content, $message);
        }
    }
    
    /**
     * Fallback copy method using execCommand
     * 
     * @param {string} text - Text to copy
     * @param {jQuery} $message - Message element (optional)
     */
    function fallbackCopyToClipboard(text, $message) {
        // Create a temporary textarea to copy from
        const $temp = $('<textarea>');
        $('body').append($temp);
        $temp.val(text).select();
        
        // Copy the text
        document.execCommand('copy');
        
        // Remove the temporary element
        $temp.remove();
        
        // Show a confirmation if we have the message element
        if ($message) {
            const $copyBtn = $message.find('.mpai-copy-message');
            const $originalIcon = $copyBtn.html();
            
            $copyBtn.html('<span class="dashicons dashicons-yes"></span>');
            
            setTimeout(function() {
                $copyBtn.html($originalIcon);
            }, 2000);
        }
    }
    
    /**
     * Complete tool calls and display final response
     * 
     * @param {string} finalResponse - The final response with tools executed
     */
    function completeToolCalls(finalResponse) {
        if (pendingToolCalls) {
            pendingToolCalls = false;
            const $message = addMessage('assistant', finalResponse);
            
            // Process the message with blog formatter if available
            if (window.MPAI_BlogFormatter && typeof window.MPAI_BlogFormatter.processAssistantMessage === 'function') {
                window.MPAI_BlogFormatter.processAssistantMessage($message, finalResponse);
            }
        }
    }
    
    // Public API
    return {
        init: init,
        addMessage: addMessage,
        formatMessage: formatMessage,
        showTypingIndicator: showTypingIndicator,
        hideTypingIndicator: hideTypingIndicator,
        sendMessage: sendMessage,
        completeToolCalls: completeToolCalls,
        copyMessageToClipboard: copyMessageToClipboard
    };
})(jQuery);

// Expose the module globally
window.MPAI_Messages = MPAI_Messages;