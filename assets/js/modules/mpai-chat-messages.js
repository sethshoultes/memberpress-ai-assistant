/**
 * MemberPress AI Assistant - Chat Messages Module
 * 
 * Completely rewritten message handling with enhanced parameter validation
 */

var MPAI_Messages = (function($) {
    'use strict';
    
    // Private variables
    var elements = {};
    var pendingToolCalls = false;
    var lastAssistantMessageId = null;
    
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
        
        // Check if this is an assistant message with XML content
        if (role === 'assistant' && window.MPAI_BlogFormatter &&
            typeof window.MPAI_BlogFormatter.preProcessXmlContent === 'function' &&
            (content.includes('<wp-post>') ||
             content.includes('</wp-post>') ||
             content.includes('<post-title>') ||
             content.includes('<post-content>'))) {
            
            // Pre-process XML content
            const processResult = window.MPAI_BlogFormatter.preProcessXmlContent(content);
            
            // Format the cleaned content
            $content.html(formatMessage(processResult.content));
            
            // Store the original XML content for later use
            if (processResult.hasXml) {
                window.originalXmlResponse = content;
            }
        } else {
            // Format the message content normally
            $content.html(formatMessage(content));
        }
        
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
            
            // Store the ID of the last assistant message
            lastAssistantMessageId = messageId;
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
     * Update the content of the last assistant message
     * 
     * @param {string} content - The new content
     */
    function updateLastAssistantMessage(content) {
        if (!lastAssistantMessageId) {
            return;
        }
        
        const $message = $('#' + lastAssistantMessageId);
        if ($message.length === 0) {
            return;
        }
        
        const $content = $message.find('.mpai-chat-message-content');
        $content.html(formatMessage(content));
        
        // Scroll to the bottom
        if (window.MPAI_UIUtils && typeof window.MPAI_UIUtils.scrollToBottom === 'function') {
            window.MPAI_UIUtils.scrollToBottom();
        }
    }
    
    /**
     * Append content to the last assistant message
     * 
     * @param {string} content - The content to append
     */
    function appendToLastAssistantMessage(content) {
        if (!lastAssistantMessageId) {
            return;
        }
        
        const $message = $('#' + lastAssistantMessageId);
        if ($message.length === 0) {
            return;
        }
        
        const $content = $message.find('.mpai-chat-message-content');
        $content.append(content);
        
        // Scroll to the bottom
        if (window.MPAI_UIUtils && typeof window.MPAI_UIUtils.scrollToBottom === 'function') {
            window.MPAI_UIUtils.scrollToBottom();
        }
    }
    
    /**
     * Format message content with enhanced markdown support
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
        
        // Store the last user message globally for parameter extraction
        window.lastUserMessage = message;
        console.log('Stored last user message for parameter extraction:', message);
        
        // Reset tool call processors
        if (window.MPAI_ToolCallDetector && typeof window.MPAI_ToolCallDetector.resetProcessed === 'function') {
            window.MPAI_ToolCallDetector.resetProcessed();
        }
        
        // Log the message being sent
        if (window.mpaiLogger) {
            window.mpaiLogger.info('Sending user message: ' + message.substring(0, 50) + (message.length > 50 ? '...' : ''), 'api_calls');
            window.mpaiLogger.startTimer('message_processing');
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
        
        // CRITICAL FIX: Direct interception of membership creation JSON
        // This is a simple, direct solution that will catch the exact format shown in the example
        if (response) {
            // Look for the exact pattern: {"tool": "memberpress_info", "parameters": {"type": "create", ...}}
            // FIXED PATTERN: Use a more robust pattern that captures the entire JSON object
            const membershipPattern = /\{[\s\n]*"tool"[\s\n]*:[\s\n]*"memberpress_info"[\s\n]*,[\s\n]*"parameters"[\s\n]*:[\s\n]*\{[\s\S]*?"type"[\s\n]*:[\s\n]*"create"[\s\S]*?\}\}/g;
            const matches = response.match(membershipPattern);
            
            // Log the entire response for debugging
            console.log('DIRECT FIX: Processing response:', response);
            
            if (matches && matches.length > 0) {
                console.log('DIRECT FIX: Intercepted membership creation JSON', matches[0]);
                
                try {
                    // Parse the JSON
                    const jsonData = JSON.parse(matches[0]);
                    console.log('DIRECT FIX: Parsed JSON data:', jsonData);
                    
                    // Verify it's a valid membership creation tool call
                    if (jsonData.tool === 'memberpress_info' &&
                        jsonData.parameters &&
                        jsonData.parameters.type === 'create') {
                        
                        // Ensure all required parameters are present
                        const params = jsonData.parameters;
                        
                        // IMPROVED EXTRACTION: Extract name from the response text if not in JSON
                        if (!params.name) {
                            // Try to extract from "named X" pattern
                            if (response.includes('named')) {
                                const nameMatch = response.match(/named\s+['"]?([^'"]+)['"]?/i);
                                if (nameMatch && nameMatch[1]) {
                                    params.name = nameMatch[1].trim();
                                    console.log('DIRECT FIX: Extracted name from "named X" pattern:', params.name);
                                }
                            }
                            // Try to extract from "level X" pattern
                            else if (response.includes('level')) {
                                const nameMatch = response.match(/level\s+['"]?([^'"]+)['"]?/i);
                                if (nameMatch && nameMatch[1]) {
                                    params.name = nameMatch[1].trim();
                                    console.log('DIRECT FIX: Extracted name from "level X" pattern:', params.name);
                                }
                            }
                            // Try to extract from user message
                            else if (window.lastUserMessage && window.lastUserMessage.includes('named')) {
                                const nameMatch = window.lastUserMessage.match(/named\s+['"]?([^'"]+)['"]?/i);
                                if (nameMatch && nameMatch[1]) {
                                    params.name = nameMatch[1].trim();
                                    console.log('DIRECT FIX: Extracted name from user message:', params.name);
                                }
                            }
                            
                            // Default to "Gold" if still not found
                            if (!params.name) {
                                params.name = "Gold";
                                console.log('DIRECT FIX: Using default name "Gold"');
                            }
                        }
                        
                        // IMPROVED EXTRACTION: Extract price from the response text if not in JSON
                        if (!params.price) {
                            // Try to extract from $ pattern
                            if (response.includes('$')) {
                                const priceMatch = response.match(/\$\s*(\d+(?:\.\d+)?)/);
                                if (priceMatch && priceMatch[1]) {
                                    params.price = parseFloat(priceMatch[1]);
                                    console.log('DIRECT FIX: Extracted price from $ pattern:', params.price);
                                }
                            }
                            // Try to extract from "X dollars" pattern
                            else if (response.includes('dollar')) {
                                const priceMatch = response.match(/(\d+(?:\.\d+)?)\s+dollars?/i);
                                if (priceMatch && priceMatch[1]) {
                                    params.price = parseFloat(priceMatch[1]);
                                    console.log('DIRECT FIX: Extracted price from "X dollars" pattern:', params.price);
                                }
                            }
                            // Try to extract from user message
                            else if (window.lastUserMessage) {
                                if (window.lastUserMessage.includes('$')) {
                                    const priceMatch = window.lastUserMessage.match(/\$\s*(\d+(?:\.\d+)?)/);
                                    if (priceMatch && priceMatch[1]) {
                                        params.price = parseFloat(priceMatch[1]);
                                        console.log('DIRECT FIX: Extracted price from user message $ pattern:', params.price);
                                    }
                                } else if (window.lastUserMessage.includes('dollar')) {
                                    const priceMatch = window.lastUserMessage.match(/(\d+(?:\.\d+)?)\s+dollars?/i);
                                    if (priceMatch && priceMatch[1]) {
                                        params.price = parseFloat(priceMatch[1]);
                                        console.log('DIRECT FIX: Extracted price from user message "X dollars" pattern:', params.price);
                                    }
                                } else {
                                    // Try to extract any number from user message as a last resort
                                    const priceMatch = window.lastUserMessage.match(/(\d+(?:\.\d+)?)/);
                                    if (priceMatch && priceMatch[1]) {
                                        params.price = parseFloat(priceMatch[1]);
                                        console.log('DIRECT FIX: Extracted price from user message number:', params.price);
                                    }
                                }
                            }
                            
                            // Default to 30 if still not found
                            if (!params.price) {
                                params.price = 30;
                                console.log('DIRECT FIX: Using default price 30');
                            }
                        } else if (typeof params.price === 'string') {
                            // Ensure price is a number
                            params.price = parseFloat(params.price);
                            console.log('DIRECT FIX: Converted price from string to number:', params.price);
                        }
                        
                        // IMPROVED EXTRACTION: Extract period_type from the response text if not in JSON
                        if (!params.period_type) {
                            if (response.includes('monthly') || response.includes('per month') || response.includes('a month')) {
                                params.period_type = 'month';
                                console.log('DIRECT FIX: Set period_type to month based on response text');
                            } else if (response.includes('yearly') || response.includes('per year') || response.includes('a year')) {
                                params.period_type = 'year';
                                console.log('DIRECT FIX: Set period_type to year based on response text');
                            } else if (response.includes('lifetime')) {
                                params.period_type = 'lifetime';
                                console.log('DIRECT FIX: Set period_type to lifetime based on response text');
                            } else if (window.lastUserMessage) {
                                // Try to extract from user message
                                if (window.lastUserMessage.includes('monthly') || window.lastUserMessage.includes('per month') || window.lastUserMessage.includes('a month')) {
                                    params.period_type = 'month';
                                    console.log('DIRECT FIX: Set period_type to month based on user message');
                                } else if (window.lastUserMessage.includes('yearly') || window.lastUserMessage.includes('per year') || window.lastUserMessage.includes('a year')) {
                                    params.period_type = 'year';
                                    console.log('DIRECT FIX: Set period_type to year based on user message');
                                } else if (window.lastUserMessage.includes('lifetime')) {
                                    params.period_type = 'lifetime';
                                    console.log('DIRECT FIX: Set period_type to lifetime based on user message');
                                } else {
                                    // Default to month if not found
                                    params.period_type = 'month';
                                    console.log('DIRECT FIX: Using default period_type month');
                                }
                            } else {
                                // Default to month if not found
                                params.period_type = 'month';
                                console.log('DIRECT FIX: Using default period_type month');
                            }
                        }
                        
                        // Log the final parameters
                        console.log('DIRECT FIX: Final parameters:', params);
                        
                        // Create a tool call object
                        const toolCall = {
                            name: 'memberpress_info',
                            parameters: params
                        };
                        
                        // Replace the JSON in the response with a processing message
                        const modifiedResponse = response.replace(
                            matches[0],
                            '<div class="mpai-tool-call-processing">Creating membership...</div>'
                        );
                        
                        // Add the modified response to the chat
                        addMessage('assistant', modifiedResponse);
                        
                        // Execute the tool call
                        if (window.MPAI_Tools && typeof window.MPAI_Tools.executeToolCalls === 'function') {
                            window.MPAI_Tools.executeToolCalls([toolCall], response);
                            
                            // Set pending tool calls flag
                            pendingToolCalls = true;
                            
                            if (window.mpaiLogger) {
                                const elapsed = window.mpaiLogger.endTimer('process_response');
                                window.mpaiLogger.info('DIRECT FIX: Executed membership creation tool call', 'tool_usage', {
                                    processingTimeMs: elapsed
                                });
                            }
                            
                            return;
                        }
                    }
                } catch (e) {
                    console.error('DIRECT FIX: Error parsing JSON', e);
                }
            }
        }
        
        // CRITICAL FIX: Check for code blocks with JSON tool calls
        if (response && response.includes('```json')) {
            if (window.mpaiLogger) {
                window.mpaiLogger.info('CRITICAL FIX - Detected JSON code block in response', 'tool_usage');
            }
            
            // Extract JSON from code blocks
            const codeBlockPattern = /```json\s*([\s\S]*?)\s*```/g;
            let codeBlockMatch;
            let foundToolCall = false;
            
            while ((codeBlockMatch = codeBlockPattern.exec(response)) !== null) {
                if (codeBlockMatch[1] &&
                    codeBlockMatch[1].includes('memberpress_info') &&
                    codeBlockMatch[1].includes('"type":"create"')) {
                    
                    try {
                        // Clean up the JSON string
                        const jsonStr = codeBlockMatch[1].trim();
                        
                        if (window.mpaiLogger) {
                            window.mpaiLogger.info('CRITICAL FIX - Found JSON code block with potential tool call', 'tool_usage', {
                                jsonStr: jsonStr.substring(0, 100)
                            });
                        }
                        
                        // Parse the JSON
                        const jsonData = JSON.parse(jsonStr);
                        console.log('CRITICAL FIX - Parsed JSON from code block:', jsonData);
                        
                        if (jsonData.tool === 'memberpress_info' &&
                            jsonData.parameters &&
                            jsonData.parameters.type === 'create') {
                            
                            // Ensure all required parameters are present
                            const params = jsonData.parameters;
                            
                            // Extract name from the response text if not in JSON
                            if (!params.name && response.includes('named')) {
                                const nameMatch = response.match(/named\s+['"]?([^'"]+)['"]?/i);
                                if (nameMatch && nameMatch[1]) {
                                    params.name = nameMatch[1].trim();
                                    console.log('CRITICAL FIX - Extracted name from text:', params.name);
                                }
                            }
                            
                            // Extract price from the response text if not in JSON
                            if (!params.price && response.includes('$')) {
                                const priceMatch = response.match(/\$\s*(\d+(?:\.\d+)?)/);
                                if (priceMatch && priceMatch[1]) {
                                    params.price = parseFloat(priceMatch[1]);
                                    console.log('CRITICAL FIX - Extracted price from text:', params.price);
                                }
                            } else if (typeof params.price === 'string') {
                                // Ensure price is a number
                                params.price = parseFloat(params.price);
                                console.log('CRITICAL FIX - Converted price from string to number:', params.price);
                            }
                            
                            // Extract period_type from the response text if not in JSON
                            if (!params.period_type && response.includes('monthly')) {
                                params.period_type = 'month';
                                console.log('CRITICAL FIX - Set period_type to month based on text');
                            } else if (!params.period_type && response.includes('yearly')) {
                                params.period_type = 'year';
                                console.log('CRITICAL FIX - Set period_type to year based on text');
                            } else if (!params.period_type && response.includes('lifetime')) {
                                params.period_type = 'lifetime';
                                console.log('CRITICAL FIX - Set period_type to lifetime based on text');
                            }
                            
                            // Log the final parameters
                            console.log('CRITICAL FIX - Final parameters:', params);
                            
                            // Create a tool call object
                            const toolCalls = [{
                                name: 'memberpress_info',
                                parameters: params
                            }];
                            
                            // Execute the tool call directly
                            if (window.MPAI_Tools && typeof window.MPAI_Tools.executeToolCalls === 'function') {
                                window.MPAI_Tools.executeToolCalls(toolCalls, response);
                                
                                // Set pending tool calls flag
                                pendingToolCalls = true;
                                foundToolCall = true;
                                
                                if (window.mpaiLogger) {
                                    window.mpaiLogger.info('CRITICAL FIX - Executed membership creation tool call from JSON code block', 'tool_usage');
                                }
                                
                                // Replace the code block with a processing message
                                response = response.replace(
                                    codeBlockMatch[0],
                                    '<div class="mpai-tool-call-processing">Processing membership creation...</div>'
                                );
                            }
                        }
                    } catch (e) {
                        if (window.mpaiLogger) {
                            window.mpaiLogger.error('CRITICAL FIX - Error parsing JSON code block', 'tool_usage', {
                                error: e.toString()
                            });
                        }
                    }
                }
            }
            
            // If we found and executed a tool call, return the modified response
            if (foundToolCall) {
                if (window.mpaiLogger) {
                    const elapsed = window.mpaiLogger.endTimer('process_response');
                    window.mpaiLogger.info('CRITICAL FIX - Executed tool call from JSON code block', 'tool_usage', {
                        processingTimeMs: elapsed
                    });
                }
                
                // Add the modified response
                addMessage('assistant', response);
                return;
            }
        }
        
        // CRITICAL FIX: Direct detection for membership creation in raw text
        // This is a fallback in case the tool call detector fails
        if (response &&
            response.includes('memberpress_info') &&
            response.includes('"type":"create"') &&
            response.includes('"parameters"')) {
            
            if (window.mpaiLogger) {
                window.mpaiLogger.info('CRITICAL FIX - Detected potential membership creation in raw text', 'tool_usage');
            }
            
            // Try multiple patterns to extract the JSON
            const jsonPatterns = [
                // FIXED PATTERN: Use a more robust pattern that captures the entire JSON object
                /\{[\s\n]*"tool"[\s\n]*:[\s\n]*"memberpress_info"[\s\n]*,[\s\n]*"parameters"[\s\n]*:[\s\n]*\{[\s\S]*?"type"[\s\n]*:[\s\n]*"create"[\s\S]*?\}\}/g,
                
                // Variation with name instead of tool
                /\{[\s\n]*"name"[\s\n]*:[\s\n]*"memberpress_info"[\s\n]*,[\s\n]*"parameters"[\s\n]*:[\s\n]*\{[\s\S]*?"type"[\s\n]*:[\s\n]*"create"[\s\S]*?\}\}/g,
                
                // Variation with single quotes
                /\{[\s\n]*['"]tool['"][\s\n]*:[\s\n]*['"]memberpress_info['"][\s\n]*,[\s\n]*['"]parameters['"][\s\n]*:[\s\n]*\{[\s\S]*?['"]type['"][\s\n]*:[\s\n]*['"]create['"][\s\S]*?\}\}/g
            ];
            
            // Try each pattern
            for (const pattern of jsonPatterns) {
                const matches = response.match(pattern);
                
                if (matches && matches.length > 0) {
                    if (window.mpaiLogger) {
                        window.mpaiLogger.info('CRITICAL FIX - Found direct membership creation format', 'tool_usage', {
                            match: matches[0]
                        });
                    }
                    
                    try {
                        // Clean the JSON string - handle single quotes, etc.
                        let jsonStr = matches[0].replace(/'/g, '"');
                        
                        // Parse the JSON
                        const jsonData = JSON.parse(jsonStr);
                        console.log('CRITICAL FIX - Parsed JSON from raw text:', jsonData);
                        
                        if ((jsonData.tool === 'memberpress_info' || jsonData.name === 'memberpress_info') &&
                            jsonData.parameters &&
                            jsonData.parameters.type === 'create') {
                            
                            // Ensure all required parameters are present
                            const params = jsonData.parameters;
                            
                            // Extract name from the response text if not in JSON
                            if (!params.name && response.includes('named')) {
                                const nameMatch = response.match(/named\s+['"]?([^'"]+)['"]?/i);
                                if (nameMatch && nameMatch[1]) {
                                    params.name = nameMatch[1].trim();
                                    console.log('CRITICAL FIX - Extracted name from text:', params.name);
                                    
                                    if (window.mpaiLogger) {
                                        window.mpaiLogger.info('CRITICAL FIX - Extracted name from text', 'tool_usage', {
                                            name: params.name
                                        });
                                    }
                                }
                            }
                            
                            // Extract price from the response text if not in JSON
                            if (!params.price && response.includes('$')) {
                                const priceMatch = response.match(/\$\s*(\d+(?:\.\d+)?)/);
                                if (priceMatch && priceMatch[1]) {
                                    params.price = parseFloat(priceMatch[1]);
                                    console.log('CRITICAL FIX - Extracted price from text:', params.price);
                                    
                                    if (window.mpaiLogger) {
                                        window.mpaiLogger.info('CRITICAL FIX - Extracted price from text', 'tool_usage', {
                                            price: params.price
                                        });
                                    }
                                }
                            } else if (typeof params.price === 'string') {
                                // Ensure price is a number
                                params.price = parseFloat(params.price);
                                console.log('CRITICAL FIX - Converted price from string to number:', params.price);
                                
                                if (window.mpaiLogger) {
                                    window.mpaiLogger.info('CRITICAL FIX - Converted price from string to number', 'tool_usage', {
                                        price: params.price
                                    });
                                }
                            }
                            
                            // Extract period_type from the response text if not in JSON
                            if (!params.period_type && response.includes('monthly')) {
                                params.period_type = 'month';
                                console.log('CRITICAL FIX - Set period_type to month based on text');
                                
                                if (window.mpaiLogger) {
                                    window.mpaiLogger.info('CRITICAL FIX - Set period_type to month based on text', 'tool_usage');
                                }
                            } else if (!params.period_type && response.includes('yearly')) {
                                params.period_type = 'year';
                                console.log('CRITICAL FIX - Set period_type to year based on text');
                                
                                if (window.mpaiLogger) {
                                    window.mpaiLogger.info('CRITICAL FIX - Set period_type to year based on text', 'tool_usage');
                                }
                            } else if (!params.period_type && response.includes('lifetime')) {
                                params.period_type = 'lifetime';
                                console.log('CRITICAL FIX - Set period_type to lifetime based on text');
                                
                                if (window.mpaiLogger) {
                                    window.mpaiLogger.info('CRITICAL FIX - Set period_type to lifetime based on text', 'tool_usage');
                                }
                            }
                            
                            // Log the final parameters
                            console.log('CRITICAL FIX - Final parameters:', params);
                            
                            if (window.mpaiLogger) {
                                window.mpaiLogger.info('CRITICAL FIX - Final parameters for tool call', 'tool_usage', params);
                            }
                            
                            // Create a tool call object
                            const toolCalls = [{
                                name: 'memberpress_info',
                                parameters: params
                            }];
                            
                            // Execute the tool call directly
                            if (window.MPAI_Tools && typeof window.MPAI_Tools.executeToolCalls === 'function') {
                                window.MPAI_Tools.executeToolCalls(toolCalls, response);
                                
                                // Set pending tool calls flag
                                pendingToolCalls = true;
                                
                                if (window.mpaiLogger) {
                                    const elapsed = window.mpaiLogger.endTimer('process_response');
                                    window.mpaiLogger.info('CRITICAL FIX - Executed membership creation tool call', 'tool_usage', {
                                        processingTimeMs: elapsed
                                    });
                                }
                                
                                // Replace the raw JSON with a processing message
                                response = response.replace(
                                    matches[0],
                                    '<div class="mpai-tool-call-processing">Processing membership creation...</div>'
                                );
                                
                                // Add the modified response
                                addMessage('assistant', response);
                                return;
                            }
                        }
                    } catch (e) {
                        if (window.mpaiLogger) {
                            window.mpaiLogger.error('CRITICAL FIX - Error parsing direct format JSON', 'tool_usage', {
                                error: e.toString()
                            });
                        }
                    }
                }
            }
        }
        
        // Standard tool call detection
        let hasToolCalls = false;
        if (window.MPAI_Tools && typeof window.MPAI_Tools.processToolCalls === 'function') {
            hasToolCalls = window.MPAI_Tools.processToolCalls(response);
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
        
        // No tool calls detected, check for XML content before adding the message
        if (window.MPAI_BlogFormatter &&
            typeof window.MPAI_BlogFormatter.preProcessXmlContent === 'function' &&
            (response.includes('<wp-post>') ||
             response.includes('</wp-post>') ||
             response.includes('<post-title>') ||
             response.includes('<post-content>'))) {
            
            // Store the original XML response for later processing
            window.originalXmlResponse = response;
            
            // Pre-process XML content
            const processResult = window.MPAI_BlogFormatter.preProcessXmlContent(response);
            
            // Add the message with the cleaned content
            const $message = addMessage('assistant', processResult.content);
            
            // If XML was found and processed, append the preview card
            if (processResult.hasXml && processResult.previewCardHtml) {
                $message.append(processResult.previewCardHtml);
            }
        } else {
            // Add the message normally
            addMessage('assistant', response);
        }
        
        if (window.mpaiLogger) {
            const elapsed = window.mpaiLogger.endTimer('process_response');
            window.mpaiLogger.info('Added assistant response to chat', 'ui', {
                processingTimeMs: elapsed,
                responseLength: response ? response.length : 0
            });
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
            
            // Add the final response
            addMessage('assistant', finalResponse);
            
            if (window.mpaiLogger) {
                window.mpaiLogger.info('Tool calls completed, added final response', 'tool_usage');
            }
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
    
    // Public API
    return {
        init: init,
        addMessage: addMessage,
        updateLastAssistantMessage: updateLastAssistantMessage,
        appendToLastAssistantMessage: appendToLastAssistantMessage,
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