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
                nonce: mpai_chat_data.mpai_nonce,
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
        
        // Check if the response contains XML content
        const hasXmlContent = response && (
            response.includes('<wp-post>') ||
            response.includes('</wp-post>') ||
            response.includes('<post-title>') ||
            response.includes('</post-title>') ||
            response.includes('<post-content>') ||
            response.includes('</post-content>') ||
            response.includes('<post-excerpt>') ||
            response.includes('</post-excerpt>') ||
            response.includes('<post-type>') ||
            response.includes('</post-type>')
        );
        
        if (hasXmlContent && window.mpaiLogger) {
            window.mpaiLogger.debug('Response contains XML content, removing it', 'ui', {
                responseLength: response.length,
                xmlDetected: true
            });
            
            // Store the original response for blog formatter
            const originalResponse = response;
            
            // Remove all XML content from the response
            
            // 1. Remove XML code blocks
            response = response.replace(/```xml\s*[\s\S]*?```/g, '');
            
            // 2. Remove XML in JSON code blocks
            response = response.replace(/```json\s*([\s\S]*?)```/g, function(match, jsonContent) {
                if (jsonContent.includes('<wp-post>') ||
                    jsonContent.includes('<post-title>') ||
                    jsonContent.includes('<post-content>') ||
                    jsonContent.includes('<post-excerpt>') ||
                    jsonContent.includes('<post-type>')) {
                    return '';
                }
                return match;
            });
            
            // 3. Remove raw XML tags
            response = response.replace(/<wp-post>[\s\S]*?<\/wp-post>/g, '');
            response = response.replace(/<post-title>[\s\S]*?<\/post-title>/g, '');
            response = response.replace(/<post-content>[\s\S]*?<\/post-content>/g, '');
            response = response.replace(/<post-excerpt>[\s\S]*?<\/post-excerpt>/g, '');
            response = response.replace(/<post-type>[\s\S]*?<\/post-type>/g, '');
            
            // 4. Remove XML in JSON strings
            response = response.replace(/"<wp-post>[\s\S]*?<\/wp-post>"/g, '""');
            response = response.replace(/"<post-title>[\s\S]*?<\/post-title>"/g, '""');
            response = response.replace(/"<post-content>[\s\S]*?<\/post-content>"/g, '""');
            response = response.replace(/"<post-excerpt>[\s\S]*?<\/post-excerpt>"/g, '""');
            response = response.replace(/"<post-type>[\s\S]*?<\/post-type>"/g, '""');
            
            // 5. Clean up any remaining XML-like content
            response = response.replace(/```json\s*[\s\S]*?<wp-post>[\s\S]*?<\/wp-post>[\s\S]*?```/g, '');
            response = response.replace(/```json\s*[\s\S]*?<post-title>[\s\S]*?<\/post-title>[\s\S]*?```/g, '');
            response = response.replace(/```json\s*[\s\S]*?<post-content>[\s\S]*?<\/post-content>[\s\S]*?```/g, '');
            
            // Pass the original response to the blog formatter
            window.originalXmlResponse = originalResponse;
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
        
        // Pre-process XML content if blog formatter is available
        let processedResponse = response;
        let previewCardHtml = '';
        let hasXml = false;
        
        if (window.MPAI_BlogFormatter && typeof window.MPAI_BlogFormatter.preProcessXmlContent === 'function') {
            const result = window.MPAI_BlogFormatter.preProcessXmlContent(response);
            if (result.hasXml) {
                processedResponse = result.content;
                previewCardHtml = result.previewCardHtml;
                hasXml = true;
                
                if (window.mpaiLogger) {
                    window.mpaiLogger.info('Pre-processed XML content before display', 'ui', {
                        originalLength: response.length,
                        processedLength: processedResponse.length,
                        hasPreviewCard: !!previewCardHtml
                    });
                }
            }
        }
        
        // Add the assistant message to the chat
        if (window.mpaiLogger) {
            const elapsed = window.mpaiLogger.endTimer('process_response');
            window.mpaiLogger.info('Adding assistant response to chat', 'ui', {
                processingTimeMs: elapsed,
                responseLength: processedResponse ? processedResponse.length : 0,
                hasHtml: processedResponse && (processedResponse.includes('<') && processedResponse.includes('>')),
                hasCodeBlocks: processedResponse && processedResponse.includes('```'),
                hasXml: hasXml
            });
        }
        
        // Add the message with the processed content
        const $message = addMessage('assistant', processedResponse);
        
        // If we have a preview card, append it to the message
        if (previewCardHtml) {
            $message.append(previewCardHtml);
            
            // Add event handlers for the buttons
            $message.find('.mpai-toggle-xml-button').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation(); // Prevent event bubbling
                const $xmlContent = $(this).closest('.mpai-post-preview-card').find('.mpai-post-xml-content');
                
                if ($xmlContent.is(':visible')) {
                    $xmlContent.slideUp(200);
                    $(this).text('View XML');
                } else {
                    $xmlContent.slideDown(200);
                    $(this).text('Hide XML');
                }
            });
            
            // Add preview post button handler
            $message.find('.mpai-preview-post-button').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation(); // Prevent event bubbling
                
                // Debug logging
                console.log("Preview button clicked");
                console.log("Preview card:", $(this).closest('.mpai-post-preview-card'));
                console.log("Data attributes:", $(this).closest('.mpai-post-preview-card').data());
                
                const $previewContent = $(this).closest('.mpai-post-preview-card').find('.mpai-post-preview-content');
                const $previewContainer = $(this).closest('.mpai-post-preview-card').find('.mpai-post-preview-container');
                
                // Get XML content directly from the hidden pre element instead of data attribute
                let xmlContent = '';
                const $xmlContentElement = $(this).closest('.mpai-post-preview-card').find('.mpai-post-xml-content pre');
                if ($xmlContentElement.length) {
                    // Get the HTML content and convert HTML entities back to characters
                    const htmlContent = $xmlContentElement.html();
                    xmlContent = $('<div/>').html(htmlContent).text();
                    console.log("XML content from pre element:", xmlContent.substring(0, 100) + "...");
                } else {
                    // Fallback to data attribute if pre element not found
                    try {
                        xmlContent = decodeURIComponent($(this).closest('.mpai-post-preview-card').data('xml-content') || '');
                        console.log("XML content from data attribute:", xmlContent.substring(0, 100) + "...");
                    } catch (e) {
                        console.error("Error decoding XML content:", e);
                        alert("Error accessing XML content. Please try again.");
                        return;
                    }
                }
                
                if (!xmlContent) {
                    console.error("No XML content found");
                    alert("No XML content found. Cannot generate preview.");
                    return;
                }
                
                if ($previewContent.is(':visible')) {
                    // Hide preview
                    $previewContent.slideUp(200);
                    $(this).text('Preview');
                } else {
                    // Show preview
                    // Generate HTML preview from XML content
                    try {
                        if (window.MPAI_BlogFormatter && typeof window.MPAI_BlogFormatter.convertXmlBlocksToHtml === 'function') {
                            // Extract content from XML
                            const contentMatch = xmlContent.match(/<post-content[^>]*>([\s\S]*?)<\/post-content>/i);
                            if (contentMatch && contentMatch[1]) {
                                const contentBlocks = contentMatch[1];
                                console.log("Content blocks found:", contentBlocks.substring(0, 100) + "...");
                                const previewHtml = window.MPAI_BlogFormatter.convertXmlBlocksToHtml(contentBlocks);
                                
                                // Add the formatted HTML to the preview container
                                $previewContainer.html(previewHtml);
                                
                                // Show the preview
                                $previewContent.slideDown(200);
                                $(this).text('Hide Preview');
                            } else {
                                console.error("No post content found in XML");
                                alert("Could not generate preview: No post content found.");
                            }
                        } else {
                            console.error("MPAI_BlogFormatter or convertXmlBlocksToHtml function not available");
                            alert("Preview functionality is not available.");
                        }
                    } catch (error) {
                        console.error("Error generating preview:", error);
                        alert(`Error generating preview: ${error.message}`);
                    }
                }
            });
            
            $message.find('.mpai-create-post-button').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation(); // Prevent event bubbling
                const clickedContentType = $(this).data('content-type');
                const $card = $(this).closest('.mpai-post-preview-card');
                const xmlContent = decodeURIComponent($card.data('xml-content'));
                
                console.log("Create post button clicked");
                console.log("Content type:", clickedContentType);
                console.log("XML content preview:", xmlContent.substring(0, 150) + "...");
                
                // Show a loading indicator
                $(this).prop('disabled', true).text('Creating...');
                
                // Use the createPostFromXML function with the raw XML content
                if (window.MPAI_BlogFormatter && typeof window.MPAI_BlogFormatter.createPostFromXML === 'function') {
                    window.MPAI_BlogFormatter.createPostFromXML(xmlContent, clickedContentType);
                }
            });
        }
        // Otherwise, process the message with blog formatter if available (fallback)
        else if (window.MPAI_BlogFormatter && typeof window.MPAI_BlogFormatter.processAssistantMessage === 'function') {
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
            
            // Check if the response contains XML content
            const hasXmlContent = finalResponse && (
                finalResponse.includes('<wp-post>') ||
                finalResponse.includes('</wp-post>') ||
                finalResponse.includes('<post-title>') ||
                finalResponse.includes('</post-title>') ||
                finalResponse.includes('<post-content>') ||
                finalResponse.includes('</post-content>') ||
                finalResponse.includes('<post-excerpt>') ||
                finalResponse.includes('</post-excerpt>') ||
                finalResponse.includes('<post-type>') ||
                finalResponse.includes('</post-type>')
            );
            
            // Store the original response for blog formatter
            const originalResponse = finalResponse;
            
            if (hasXmlContent && window.mpaiLogger) {
                window.mpaiLogger.debug('Final response contains XML content, removing it', 'ui', {
                    responseLength: finalResponse.length,
                    xmlDetected: true
                });
                
                // Remove all XML content from the response
                
                // 1. Remove XML code blocks
                finalResponse = finalResponse.replace(/```xml\s*[\s\S]*?```/g, '');
                
                // 2. Remove XML in JSON code blocks
                finalResponse = finalResponse.replace(/```json\s*([\s\S]*?)```/g, function(match, jsonContent) {
                    if (jsonContent.includes('<wp-post>') ||
                        jsonContent.includes('<post-title>') ||
                        jsonContent.includes('<post-content>') ||
                        jsonContent.includes('<post-excerpt>') ||
                        jsonContent.includes('<post-type>')) {
                        return '';
                    }
                    return match;
                });
                
                // 3. Remove raw XML tags
                finalResponse = finalResponse.replace(/<wp-post>[\s\S]*?<\/wp-post>/g, '');
                finalResponse = finalResponse.replace(/<post-title>[\s\S]*?<\/post-title>/g, '');
                finalResponse = finalResponse.replace(/<post-content>[\s\S]*?<\/post-content>/g, '');
                finalResponse = finalResponse.replace(/<post-excerpt>[\s\S]*?<\/post-excerpt>/g, '');
                finalResponse = finalResponse.replace(/<post-type>[\s\S]*?<\/post-type>/g, '');
                
                // 4. Remove XML in JSON strings
                finalResponse = finalResponse.replace(/"<wp-post>[\s\S]*?<\/wp-post>"/g, '""');
                finalResponse = finalResponse.replace(/"<post-title>[\s\S]*?<\/post-title>"/g, '""');
                finalResponse = finalResponse.replace(/"<post-content>[\s\S]*?<\/post-content>"/g, '""');
                finalResponse = finalResponse.replace(/"<post-excerpt>[\s\S]*?<\/post-excerpt>"/g, '""');
                finalResponse = finalResponse.replace(/"<post-type>[\s\S]*?<\/post-type>"/g, '""');
                
                // 5. Clean up any remaining XML-like content
                finalResponse = finalResponse.replace(/```json\s*[\s\S]*?<wp-post>[\s\S]*?<\/wp-post>[\s\S]*?```/g, '');
                finalResponse = finalResponse.replace(/```json\s*[\s\S]*?<post-title>[\s\S]*?<\/post-title>[\s\S]*?```/g, '');
                finalResponse = finalResponse.replace(/```json\s*[\s\S]*?<post-content>[\s\S]*?<\/post-content>[\s\S]*?```/g, '');
                
                // Pass the original response to the blog formatter
                window.originalXmlResponse = originalResponse;
            }
            
            // Pre-process XML content if blog formatter is available
            let processedResponse = finalResponse;
            let previewCardHtml = '';
            let xmlProcessed = false;
            
            if (window.MPAI_BlogFormatter && typeof window.MPAI_BlogFormatter.preProcessXmlContent === 'function') {
                // If we have XML content, use the original response for processing
                const contentToProcess = hasXmlContent ? originalResponse : finalResponse;
                const result = window.MPAI_BlogFormatter.preProcessXmlContent(contentToProcess);
                
                if (result.hasXml) {
                    processedResponse = result.content;
                    previewCardHtml = result.previewCardHtml;
                    xmlProcessed = true;
                    
                    if (window.mpaiLogger) {
                        window.mpaiLogger.info('Pre-processed XML content before display in tool call response', 'ui', {
                            originalLength: contentToProcess.length,
                            processedLength: processedResponse.length,
                            hasPreviewCard: !!previewCardHtml
                        });
                    }
                }
            }
            
            // Add the message with the processed content
            const $message = addMessage('assistant', processedResponse);
            
            // If we have a preview card, append it to the message
            if (previewCardHtml) {
                $message.append(previewCardHtml);
                
                // Add event handlers for the buttons
                $message.find('.mpai-toggle-xml-button').on('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation(); // Prevent event bubbling
                    const $xmlContent = $(this).closest('.mpai-post-preview-card').find('.mpai-post-xml-content');
                    
                    if ($xmlContent.is(':visible')) {
                        $xmlContent.slideUp(200);
                        $(this).text('View XML');
                    } else {
                        $xmlContent.slideDown(200);
                        $(this).text('Hide XML');
                    }
                });
                
                // Add preview post button handler
                $message.find('.mpai-preview-post-button').on('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation(); // Prevent event bubbling
                    
                    // Debug logging
                    console.log("Preview button clicked (tool calls)");
                    console.log("Preview card:", $(this).closest('.mpai-post-preview-card'));
                    console.log("Data attributes:", $(this).closest('.mpai-post-preview-card').data());
                    
                    const $previewContent = $(this).closest('.mpai-post-preview-card').find('.mpai-post-preview-content');
                    const $previewContainer = $(this).closest('.mpai-post-preview-card').find('.mpai-post-preview-container');
                    
                    // Get XML content directly from the hidden pre element instead of data attribute
                    let xmlContent = '';
                    const $xmlContentElement = $(this).closest('.mpai-post-preview-card').find('.mpai-post-xml-content pre');
                    if ($xmlContentElement.length) {
                        // Get the HTML content and convert HTML entities back to characters
                        const htmlContent = $xmlContentElement.html();
                        xmlContent = $('<div/>').html(htmlContent).text();
                        console.log("XML content from pre element:", xmlContent.substring(0, 100) + "...");
                    } else {
                        // Fallback to data attribute if pre element not found
                        try {
                            xmlContent = decodeURIComponent($(this).closest('.mpai-post-preview-card').data('xml-content') || '');
                            console.log("XML content from data attribute:", xmlContent.substring(0, 100) + "...");
                        } catch (e) {
                            console.error("Error decoding XML content:", e);
                            alert("Error accessing XML content. Please try again.");
                            return;
                        }
                    }
                    
                    if (!xmlContent) {
                        console.error("No XML content found");
                        alert("No XML content found. Cannot generate preview.");
                        return;
                    }
                    
                    if ($previewContent.is(':visible')) {
                        // Hide preview
                        $previewContent.slideUp(200);
                        $(this).text('Preview');
                    } else {
                        // Show preview
                        // Generate HTML preview from XML content
                        try {
                            if (window.MPAI_BlogFormatter && typeof window.MPAI_BlogFormatter.convertXmlBlocksToHtml === 'function') {
                                // Extract content from XML
                                const contentMatch = xmlContent.match(/<post-content[^>]*>([\s\S]*?)<\/post-content>/i);
                                if (contentMatch && contentMatch[1]) {
                                    const contentBlocks = contentMatch[1];
                                    console.log("Content blocks found:", contentBlocks.substring(0, 100) + "...");
                                    const previewHtml = window.MPAI_BlogFormatter.convertXmlBlocksToHtml(contentBlocks);
                                    
                                    // Add the formatted HTML to the preview container
                                    $previewContainer.html(previewHtml);
                                    
                                    // Show the preview
                                    $previewContent.slideDown(200);
                                    $(this).text('Hide Preview');
                                } else {
                                    console.error("No post content found in XML");
                                    alert("Could not generate preview: No post content found.");
                                }
                            } else {
                                console.error("MPAI_BlogFormatter or convertXmlBlocksToHtml function not available");
                                alert("Preview functionality is not available.");
                            }
                        } catch (error) {
                            console.error("Error generating preview:", error);
                            alert(`Error generating preview: ${error.message}`);
                        }
                    }
                });
                
                $message.find('.mpai-create-post-button').on('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation(); // Prevent event bubbling
                    const clickedContentType = $(this).data('content-type');
                    const $card = $(this).closest('.mpai-post-preview-card');
                    const xmlContent = decodeURIComponent($card.data('xml-content'));
                    
                    console.log("Create post button clicked");
                    console.log("Content type:", clickedContentType);
                    console.log("XML content preview:", xmlContent.substring(0, 150) + "...");
                    
                    // Show a loading indicator
                    $(this).prop('disabled', true).text('Creating...');
                    
                    // Use the createPostFromXML function with the raw XML content
                    if (window.MPAI_BlogFormatter && typeof window.MPAI_BlogFormatter.createPostFromXML === 'function') {
                        window.MPAI_BlogFormatter.createPostFromXML(xmlContent, clickedContentType);
                    }
                });
            }
            // Otherwise, process the message with blog formatter if available (fallback)
            else if (!xmlProcessed && window.MPAI_BlogFormatter && typeof window.MPAI_BlogFormatter.processAssistantMessage === 'function') {
                // If we have XML content, pass the original response to the blog formatter
                if (hasXmlContent) {
                    window.MPAI_BlogFormatter.processAssistantMessage($message, originalResponse);
                } else {
                    window.MPAI_BlogFormatter.processAssistantMessage($message, finalResponse);
                }
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