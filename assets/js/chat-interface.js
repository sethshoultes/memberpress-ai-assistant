/**
 * MemberPress AI Assistant - Chat Interface Script
 */

(function($) {
    'use strict';

    // Initialize the chat interface once the document is ready
    $(document).ready(function() {
        // DOM elements
        const $chatToggle = $('#mpai-chat-toggle');
        const $chatContainer = $('#mpai-chat-container');
        const $chatMessages = $('#mpai-chat-messages');
        const $chatInput = $('#mpai-chat-input');
        const $chatForm = $('#mpai-chat-form');
        const $chatMinimize = $('#mpai-chat-minimize');
        const $chatClose = $('#mpai-chat-close');
        const $chatClear = $('#mpai-chat-clear');
        const $chatSubmit = $('#mpai-chat-submit');

        /**
         * Function to open the chat
         */
        function openChat() {
            $chatContainer.css('display', 'flex').hide().fadeIn(300);
            $chatToggle.hide();
            $chatInput.focus();
            
            // Refresh chat history when opening
            // This will ensure the most current history is displayed
            loadChatHistory();
            
            console.log('MPAI: Chat opened');
        }

        /**
         * Function to close the chat
         */
        function closeChat() {
            $chatContainer.fadeOut(300);
            $chatToggle.fadeIn(300);
        }

        /**
         * Function to minimize the chat
         */
        function minimizeChat() {
            $chatContainer.fadeOut(300);
            $chatToggle.fadeIn(300);
        }

        /**
         * Function to send a message
         * 
         * @param {string} message - The message to send
         */
        function sendMessage(message) {
            if (!message.trim()) {
                return;
            }

            // Add the user message to the chat
            addMessageToChat('user', message);

            // Clear the input
            $chatInput.val('');
            
            // Adjust the height of the input
            adjustInputHeight();

            // Show typing indicator
            showTypingIndicator();

            // Scroll to the bottom with a slight delay to ensure content is rendered
            setTimeout(scrollToBottom, 100);

            // Send the message to the server using AJAX
            $.ajax({
                url: mpai_chat_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'mpai_process_chat',
                    message: message,
                    nonce: mpai_chat_data.nonce
                },
                success: function(response) {
                    // Hide typing indicator
                    hideTypingIndicator();

                    if (response.success && response.data && response.data.response) {
                        // Process response for tool calls
                        let processedResponse = processToolCalls(response.data.response);
                        
                        // Add the response to the chat
                        addMessageToChat('assistant', processedResponse);
                    } else {
                        // Show error message
                        addMessageToChat('assistant', mpai_chat_data.strings.error_message);
                        console.error('MPAI: Invalid response format:', response);
                    }

                    // Scroll to the bottom with a slight delay to ensure content is rendered
                    setTimeout(scrollToBottom, 100);
                },
                error: function() {
                    // Hide typing indicator
                    hideTypingIndicator();

                    // Show error message
                    addMessageToChat('assistant', mpai_chat_data.strings.error_message);

                    // Scroll to the bottom with a slight delay to ensure content is rendered
                    setTimeout(scrollToBottom, 100);
                }
            });
        }

        /**
         * Process tool calls in the response
         * 
         * @param {string} response - The assistant's response
         * @return {string} The processed response
         */
        function processToolCalls(response) {
            if (!response || typeof response !== 'string') {
                return response;
            }
            
            // Match JSON blocks that look like tool calls
            const toolCallRegex = /```json\n({[\s\S]*?})\n```/g;
            let match;
            let processedResponse = response;
            let matches = [];
            
            // Find all tool call matches first
            while ((match = toolCallRegex.exec(response)) !== null) {
                try {
                    const jsonData = JSON.parse(match[1]);
                    
                    // Only process if it looks like a tool call (has tool and parameters properties)
                    // and isn't already a tool result (no success or error properties)
                    if (jsonData.tool && jsonData.parameters && 
                        !jsonData.hasOwnProperty('success') && !jsonData.hasOwnProperty('error')) {
                        matches.push({
                            fullMatch: match[0],
                            jsonStr: match[1],
                            jsonData: jsonData
                        });
                    }
                } catch (e) {
                    console.error('MPAI: Error parsing potential tool call:', e);
                }
            }
            
            // Process each match
            matches.forEach(match => {
                const toolId = 'tool-' + Date.now() + '-' + Math.floor(Math.random() * 1000);
                
                // Replace the tool call with a processing indicator
                const processingHtml = `
                    <div class="mpai-tool-call" id="${toolId}">
                        <div class="mpai-tool-call-header">
                            <span class="mpai-tool-call-name">${match.jsonData.tool}</span>
                            <span class="mpai-tool-call-status mpai-tool-call-processing">
                                <span class="mpai-loading-dots"><span></span><span></span><span></span></span>
                                Processing
                            </span>
                        </div>
                        <div class="mpai-tool-call-content">
                            <pre><code>${JSON.stringify(match.jsonData, null, 2)}</code></pre>
                        </div>
                        <div class="mpai-tool-call-result"></div>
                    </div>
                `;
                
                processedResponse = processedResponse.replace(match.fullMatch, processingHtml);
                
                // Execute the tool call
                executeToolCall(match.jsonStr, match.jsonData, toolId);
            });
            
            return processedResponse;
        }
        
        /**
         * Execute a tool call
         * 
         * @param {string} jsonStr - The tool call JSON string
         * @param {object} jsonData - The parsed tool call JSON
         * @param {string} toolId - The tool call element ID
         */
        function executeToolCall(jsonStr, jsonData, toolId) {
            $.ajax({
                url: mpai_chat_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'mpai_execute_tool',
                    tool_request: jsonStr,
                    nonce: mpai_chat_data.nonce
                },
                success: function(response) {
                    const $toolCall = $('#' + toolId);
                    if (!$toolCall.length) return;
                    
                    const $status = $toolCall.find('.mpai-tool-call-status');
                    const $result = $toolCall.find('.mpai-tool-call-result');
                    
                    if (response.success) {
                        // Update status to success
                        $status.removeClass('mpai-tool-call-processing').addClass('mpai-tool-call-success');
                        $status.html('Success');
                        
                        // Display the result
                        const resultHtml = `<pre><code>${JSON.stringify(response.data, null, 2)}</code></pre>`;
                        $result.html(resultHtml);
                    } else {
                        // Update status to error
                        $status.removeClass('mpai-tool-call-processing').addClass('mpai-tool-call-error');
                        $status.html('Error');
                        
                        // Display the error
                        const errorMessage = response.data || 'Unknown error executing tool';
                        $result.html(`<div class="mpai-tool-call-error-message">${errorMessage}</div>`);
                    }
                    
                    // Scroll to bottom to show results
                    setTimeout(scrollToBottom, 100);
                },
                error: function(xhr, status, error) {
                    const $toolCall = $('#' + toolId);
                    if (!$toolCall.length) return;
                    
                    const $status = $toolCall.find('.mpai-tool-call-status');
                    const $result = $toolCall.find('.mpai-tool-call-result');
                    
                    // Update status to error
                    $status.removeClass('mpai-tool-call-processing').addClass('mpai-tool-call-error');
                    $status.html('Error');
                    
                    // Display the error
                    $result.html(`<div class="mpai-tool-call-error-message">AJAX error: ${error}</div>`);
                    
                    // Scroll to bottom to show error
                    setTimeout(scrollToBottom, 100);
                }
            });
        }

        /**
         * Function to add a message to the chat
         * 
         * @param {string} role - The message role (user or assistant)
         * @param {string} content - The message content
         */
        function addMessageToChat(role, content) {
            const messageClass = 'mpai-chat-message-' + role;
            const formattedContent = formatMessage(content);
            
            const $message = $(`
                <div class="mpai-chat-message ${messageClass}">
                    <div class="mpai-chat-message-content">
                        ${formattedContent}
                    </div>
                </div>
            `);

            $chatMessages.append($message);
        }

        /**
         * Function to format the message with markdown
         * 
         * @param {*} content - The message content (any type)
         * @return {string} Formatted content
         */
        function formatMessage(content) {
            // Guard for null/undefined first
            if (content === null || content === undefined) {
                console.error('formatMessage received null or undefined content');
                return 'No response received';
            }
            
            // Convert any non-string content to string
            if (typeof content !== 'string') {
                console.warn('formatMessage received non-string content of type:', typeof content);
                
                try {
                    if (typeof content === 'object') {
                        // Try to convert object to JSON string
                        content = JSON.stringify(content);
                    } else {
                        // Convert any other type to string
                        content = String(content);
                    }
                } catch (e) {
                    console.error('Error converting content to string:', e);
                    return 'Invalid response format (type: ' + typeof content + ')';
                }
            }
            
            // Additional safety check after conversion
            if (typeof content !== 'string') {
                return 'Unable to format non-string content';
            }
            
            try {
                // Wrap all replacement operations in try/catch to prevent cascading failures
                
                try {
                    // Process code blocks first to avoid interference with other replacements
                    content = content.replace(/```([\s\S]*?)```/g, function(match, p1) {
                        // Clean up the code content
                        p1 = p1.trim();
                        return '<div class="code-container"><pre><code>' + p1.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</code></pre></div>';
                    });
                } catch (e) {
                    console.error('Error processing code blocks:', e);
                }
                
                try {
                    // Process tables for markdown tables: | Header1 | Header2 |\n| --- | --- |\n| Data1 | Data2 |
                    const tableRegex = /\|(.+)\|\n\|([\s-:]+\|)+\n((\|.+\|\n)+)/g;
                    content = content.replace(tableRegex, function(match) {
                        try {
                            // Split the table into rows
                            const rows = match.split('\n').filter(row => row.trim() !== '');
                            if (rows.length < 3) return match; // Not enough rows for a table
                            
                            // Process header
                            const headerRow = rows[0];
                            const headerCells = headerRow.split('|').filter(cell => cell.trim() !== '');
                            
                            // Skip the separator row (row[1])
                            
                            // Process data rows
                            const dataRows = rows.slice(2);
                            
                            // Build HTML table
                            let table = '<div class="table-wrapper"><table><thead><tr>';
                            
                            // Add header cells
                            headerCells.forEach(cell => {
                                table += `<th>${cell.trim()}</th>`;
                            });
                            
                            table += '</tr></thead><tbody>';
                            
                            // Add data rows
                            dataRows.forEach(row => {
                                table += '<tr>';
                                const cells = row.split('|').filter(cell => cell.trim() !== '');
                                cells.forEach(cell => {
                                    table += `<td>${cell.trim()}</td>`;
                                });
                                table += '</tr>';
                            });
                            
                            table += '</tbody></table></div>';
                            return table;
                        } catch (e) {
                            console.error('Error processing table:', e);
                            return match; // Return original if there's an error
                        }
                    });
                } catch (e) {
                    console.error('Error processing tables:', e);
                }
                
                try {
                    // Convert URLs to links with truncated display
                    content = content.replace(
                        /(\b(https?|ftp):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/gim,
                        function(match) {
                            // Display shortened URL for long links
                            let displayUrl = match;
                            if (displayUrl.length > 40) {
                                displayUrl = displayUrl.substring(0, 37) + '...';
                            }
                            return '<a href="' + match + '" target="_blank" rel="noopener noreferrer">' + displayUrl + '</a>';
                        }
                    );
                } catch (e) {
                    console.error('Error processing URLs:', e);
                }
                
                try {
                    // Convert **text** to <strong>text</strong>
                    content = content.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                } catch (e) {
                    console.error('Error processing bold text:', e);
                }
                
                try {
                    // Convert *text* to <em>text</em>
                    content = content.replace(/\*(.*?)\*/g, '<em>$1</em>');
                } catch (e) {
                    console.error('Error processing italic text:', e);
                }
                
                try {
                    // Convert `code` to <code>code</code> (excluding what's already in code blocks)
                    content = content.replace(/`([^`]+)`/g, function(match, p1) {
                        return '<code>' + p1.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</code>';
                    });
                } catch (e) {
                    console.error('Error processing inline code:', e);
                }
                
                try {
                    // Convert line breaks to <br>
                    content = content.replace(/\n/g, '<br>');
                } catch (e) {
                    console.error('Error processing line breaks:', e);
                }
                
                return content;
            } catch (error) {
                console.error('Error in formatMessage:', error, 'with content type:', typeof content);
                try {
                    // Attempt to return the raw content if all formatting fails
                    return 'Error formatting message. Raw content: ' + String(content).substring(0, 100) + 
                           (String(content).length > 100 ? '...' : '');
                } catch (e) {
                    return 'Error formatting message and unable to display raw content.';
                }
            }
        }

        /**
         * Function to show the typing indicator
         */
        function showTypingIndicator() {
            // Remove existing typing indicator if any
            $('.mpai-chat-typing').remove();
            
            // Add typing indicator
            const $typingIndicator = $(`
                <div class="mpai-chat-typing">
                    <span></span><span></span><span></span>
                </div>
            `);
            
            $chatMessages.append($typingIndicator);
        }

        /**
         * Function to hide the typing indicator
         */
        function hideTypingIndicator() {
            $('.mpai-chat-typing').remove();
        }

        /**
         * Function to scroll to the bottom of the chat
         */
        function scrollToBottom() {
            if ($chatMessages[0]) {
                $chatMessages[0].scrollTop = $chatMessages[0].scrollHeight;
            }
        }

        /**
         * Function to adjust the height of the input based on content
         */
        function adjustInputHeight() {
            $chatInput.css('height', 'auto');
            let newHeight = Math.min($chatInput[0].scrollHeight, 80); // Max height 80px
            $chatInput.css('height', newHeight + 'px');
        }

        /**
         * Function to load chat history from the server
         */
        function loadChatHistory() {
            $.ajax({
                url: mpai_chat_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'mpai_get_chat_history',
                    nonce: mpai_chat_data.nonce
                },
                success: function(response) {
                    if (response.success && response.data.history) {
                        // Clear existing messages
                        $chatMessages.empty();
                        
                        // Add history messages
                        const history = response.data.history;
                        if (history.length === 0) {
                            // Add welcome message if no history
                            addMessageToChat('assistant', mpai_chat_data.strings.welcome_message);
                        } else {
                            // Add messages from history
                            for (let i = 0; i < history.length; i++) {
                                addMessageToChat(history[i].role, history[i].content);
                            }
                        }
                        
                        // Scroll to the bottom with a slight delay to ensure content is rendered
                        setTimeout(scrollToBottom, 100);
                    } else {
                        // Add welcome message
                        addMessageToChat('assistant', mpai_chat_data.strings.welcome_message);
                    }
                },
                error: function() {
                    // Add welcome message
                    addMessageToChat('assistant', mpai_chat_data.strings.welcome_message);
                }
            });
        }

        /**
         * Function to clear chat history
         */
        function clearChatHistory() {
            $.ajax({
                url: mpai_chat_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'mpai_clear_chat_history',
                    nonce: mpai_chat_data.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Clear existing messages
                        $chatMessages.empty();
                        
                        // Add welcome message
                        addMessageToChat('assistant', mpai_chat_data.strings.welcome_message);
                        
                        // Scroll to the bottom with a slight delay to ensure content is rendered
                        setTimeout(scrollToBottom, 100);
                    }
                }
            });
        }

        // Event Listeners
        
        // Open chat when the toggle button is clicked
        $chatToggle.on('click', function() {
            openChat();
        });

        // Close chat when the close button is clicked
        $chatClose.on('click', function() {
            closeChat();
        });

        // Minimize chat when the minimize button is clicked
        $chatMinimize.on('click', function() {
            minimizeChat();
        });

        // Send message when the form is submitted
        $chatForm.on('submit', function(e) {
            e.preventDefault();
            const message = $chatInput.val();
            sendMessage(message);
        });

        // Handle input height adjustment as user types
        $chatInput.on('input', function() {
            adjustInputHeight();
        });

        // Clear chat history when the clear button is clicked
        $chatClear.on('click', function() {
            clearChatHistory();
        });

        // Auto-resize the input when the window is resized
        $(window).on('resize', function() {
            adjustInputHeight();
        });

        // Initialize
        adjustInputHeight();
        
        // Always load chat history at startup to ensure it's available
        loadChatHistory();
        
        // If chat was open in previous session, reopen it
        if (localStorage.getItem('mpaiChatOpen') === 'true') {
            // We'll just show the chat since the history is already loaded
            $chatContainer.css('display', 'flex').hide().fadeIn(300);
            $chatToggle.hide();
            $chatInput.focus();
        }
        
        // Log that initialization is complete
        console.log('MPAI: Chat interface initialized');
        
        // Save chat state when opening/closing
        $chatToggle.on('click', function() {
            localStorage.setItem('mpaiChatOpen', 'true');
        });
        
        $chatClose.on('click', function() {
            localStorage.setItem('mpaiChatOpen', 'false');
        });
        
        $chatMinimize.on('click', function() {
            localStorage.setItem('mpaiChatOpen', 'false');
        });
    });

})(jQuery);