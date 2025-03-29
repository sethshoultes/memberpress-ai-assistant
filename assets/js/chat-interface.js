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
         * Format tabular result directly
         *
         * @param {object} resultData - The tabular result data object
         * @return {string} Formatted HTML for the table
         */
        function formatTabularResult(resultData) {
            console.log('MPAI: Formatting tabular result directly:', resultData);
            
            const commandType = resultData.command_type || 'generic';
            let result = resultData.result || '';
            
            // Process the result to handle escaped tabs and newlines
            if (result.includes('\\t')) {
                console.log('MPAI: Found escaped tabs, replacing with real tabs');
                result = result.replace(/\\t/g, '\t');
            }
            
            if (result.includes('\\n')) {
                console.log('MPAI: Found escaped newlines, replacing with real newlines');
                result = result.replace(/\\n/g, '\n');
            }
            
            // Generate title based on command type
            let tableTitle = '';
            switch(commandType) {
                case 'user_list':
                    tableTitle = '<h3>WordPress Users</h3>';
                    break;
                case 'post_list':
                    tableTitle = '<h3>WordPress Posts</h3>';
                    break;
                case 'plugin_list':
                    tableTitle = '<h3>WordPress Plugins</h3>';
                    break;
                case 'membership_list':
                    tableTitle = '<h3>MemberPress Memberships</h3>';
                    break;
                case 'transaction_list':
                    tableTitle = '<h3>MemberPress Transactions</h3>';
                    break;
                default:
                    tableTitle = '<h3>Command Results</h3>';
                    break;
            }
            
            // Format as table
            const rows = result.trim().split('\n');
            console.log(`MPAI: Found ${rows.length} rows to format`);
            
            let tableHtml = '<div class="mpai-result-table">';
            tableHtml += tableTitle;
            tableHtml += '<table>';
            
            rows.forEach((row, index) => {
                console.log(`MPAI: Processing direct format row ${index}:`, row.substring(0, 50));
                
                // Split by tab character or by string representation of tab if needed
                const cells = row.includes('\t') ? 
                    row.split('\t') : 
                    row.split('t'); // Fallback if somehow we still have text "t" separators
                
                console.log(`MPAI: Direct format row ${index} has ${cells.length} cells`);
                
                if (index === 0) {
                    // Header row
                    tableHtml += '<thead><tr>';
                    cells.forEach(cell => {
                        tableHtml += `<th>${cell}</th>`;
                    });
                    tableHtml += '</tr></thead><tbody>';
                } else {
                    // Data row
                    tableHtml += '<tr>';
                    cells.forEach(cell => {
                        tableHtml += `<td>${cell}</td>`;
                    });
                    tableHtml += '</tr>';
                }
            });
            
            tableHtml += '</tbody></table></div>';
            return tableHtml;
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
            
            console.log('MPAI: Processing tool calls in response');
            
            // Match JSON blocks that look like tool calls
            // Support multiple formats:
            // 1. ```json\n{...}\n``` - Standard code block with JSON
            // 2. ```json-object\n{...}\n``` - Special marker for pre-parsed JSON that shouldn't be double-encoded
            // 3. {tool: ..., parameters: ...} - Direct JSON in text
            const jsonBlockRegex = /```json\n({[\s\S]*?})\n```/g;
            const jsonObjectBlockRegex = /```json-object\n({[\s\S]*?})\n```/g;
            const directJsonRegex = /\{[\s\S]*?"tool"[\s\S]*?"parameters"[\s\S]*?\}/g;
            
            let match;
            let processedResponse = response;
            let matches = [];
            
            // Find all tool call matches from standard JSON blocks
            while ((match = jsonBlockRegex.exec(response)) !== null) {
                try {
                    console.log('MPAI: Found JSON block', match[1]);
                    const jsonData = JSON.parse(match[1]);
                    
                    // Check if this is a formatted tabular result that we can display directly
                    if (jsonData.success && jsonData.tool && jsonData.result && 
                        typeof jsonData.result === 'object' && 
                        jsonData.result.command_type && jsonData.result.result) {
                        
                        console.log('MPAI: Found formatted tabular result', jsonData);
                        
                        // Format the result directly without executing a tool call
                        processedResponse = processedResponse.replace(match[0], formatTabularResult(jsonData.result));
                        continue;
                    }
                    
                    // Only process if it looks like a tool call (has tool and parameters properties)
                    // and isn't already a tool result (no success or error properties)
                    if (jsonData.tool && jsonData.parameters && 
                        !jsonData.hasOwnProperty('success') && !jsonData.hasOwnProperty('error')) {
                        console.log('MPAI: Valid tool call in JSON block', jsonData);
                        matches.push({
                            fullMatch: match[0],
                            jsonStr: match[1],
                            jsonData: jsonData
                        });
                    }
                } catch (e) {
                    console.error('MPAI: Error parsing potential tool call in JSON block:', e);
                }
            }
            
            // Find all tool call matches from JSON object blocks (specially marked pre-parsed JSON)
            while ((match = jsonObjectBlockRegex.exec(response)) !== null) {
                try {
                    console.log('MPAI: Found JSON-object block (pre-parsed)', match[1]);
                    const jsonData = JSON.parse(match[1]);
                    
                    // Check if this is a formatted tabular result that we can display directly
                    if (jsonData.success && jsonData.tool && jsonData.result && 
                        typeof jsonData.result === 'object' && 
                        jsonData.result.command_type && jsonData.result.result) {
                        
                        console.log('MPAI: Found formatted tabular result in JSON-object block', jsonData);
                        
                        // Format the result directly without executing a tool call
                        processedResponse = processedResponse.replace(match[0], formatTabularResult(jsonData.result));
                        continue;
                    }
                    
                    // Only process if it looks like a tool call (has tool and parameters properties)
                    // and isn't already a tool result (no success or error properties)
                    if (jsonData.tool && jsonData.parameters && 
                        !jsonData.hasOwnProperty('success') && !jsonData.hasOwnProperty('error')) {
                        console.log('MPAI: Valid tool call in JSON-object block', jsonData);
                        matches.push({
                            fullMatch: match[0],
                            jsonStr: match[1],
                            jsonData: jsonData
                        });
                    }
                } catch (e) {
                    console.error('MPAI: Error parsing potential tool call in JSON-object block:', e);
                }
            }
            
            // Also try to find direct JSON format
            while ((match = directJsonRegex.exec(response)) !== null) {
                try {
                    const jsonStr = match[0];
                    // Skip if this match is part of a JSON code block we already processed
                    if (processedResponse.includes('```json\\n' + jsonStr + '\\n```')) {
                        continue;
                    }
                    
                    console.log('MPAI: Found direct JSON', jsonStr);
                    const jsonData = JSON.parse(jsonStr);
                    
                    // Check if this is a formatted tabular result that we can display directly
                    if (jsonData.success && jsonData.tool && jsonData.result && 
                        typeof jsonData.result === 'object' && 
                        jsonData.result.command_type && jsonData.result.result) {
                        
                        console.log('MPAI: Found formatted tabular result in direct JSON', jsonData);
                        
                        // Format the result directly without executing a tool call
                        processedResponse = processedResponse.replace(match[0], formatTabularResult(jsonData.result));
                        continue;
                    }
                    
                    // Only process if it looks like a tool call (has tool and parameters properties)
                    // and isn't already a tool result (no success or error properties)
                    if (jsonData.tool && jsonData.parameters && 
                        !jsonData.hasOwnProperty('success') && !jsonData.hasOwnProperty('error')) {
                        console.log('MPAI: Valid tool call in direct JSON', jsonData);
                        matches.push({
                            fullMatch: jsonStr,
                            jsonStr: jsonStr,
                            jsonData: jsonData
                        });
                    }
                } catch (e) {
                    console.error('MPAI: Error parsing potential direct JSON tool call:', e);
                }
            }
            
            console.log('MPAI: Found', matches.length, 'tool calls to process');
            
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
            console.log('MPAI: Executing tool call', {
                tool: jsonData.tool,
                parameters: jsonData.parameters,
                toolId: toolId
            });
            
            // Construct the tool request in the format expected by the backend
            const toolRequest = {
                name: jsonData.tool,
                parameters: jsonData.parameters
            };
            
            console.log('MPAI: Tool request being sent:', toolRequest);
            
            // Add raw format for debugging
            console.log('MPAI: Raw tool_request parameter:', JSON.stringify(toolRequest));
            
            $.ajax({
                url: mpai_chat_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'mpai_execute_tool',
                    tool_request: JSON.stringify(toolRequest),
                    nonce: mpai_chat_data.nonce // Try using the regular nonce instead
                },
                success: function(response) {
                    console.log('MPAI: Tool execution response', response);
                    const $toolCall = $('#' + toolId);
                    if (!$toolCall.length) return;
                    
                    const $status = $toolCall.find('.mpai-tool-call-status');
                    const $result = $toolCall.find('.mpai-tool-call-result');
                    
                    if (response.success) {
                        // Update status to success
                        $status.removeClass('mpai-tool-call-processing').addClass('mpai-tool-call-success');
                        $status.html('Success');
                        
                        // Format the result based on type and tool
                        let resultContent = '';
                        
                        // Get tool name to decide how to format
                        const toolName = response.data.tool || jsonData.tool;
                        
                        // Check if it's a wp_cli tool result
                        if (toolName === 'wp_cli') {
                            // Check if the result is a JSON string first
                            let jsonResult = null;
                            try {
                                // First check if it's an object already - this means the parsing happened on the backend
                                if (typeof response.data.result === 'object' && response.data.result !== null) {
                                    console.log('MPAI: Result is already an object, using directly', response.data.result);
                                    jsonResult = response.data.result;
                                }
                                // Then check if it's a string that needs parsing
                                else if (typeof response.data.result === 'string' && 
                                    response.data.result.trim().startsWith('{') && 
                                    response.data.result.trim().endsWith('}')) {
                                    jsonResult = JSON.parse(response.data.result);
                                    console.log('MPAI: Found JSON string in wp_cli result, parsed to:', jsonResult);
                                }
                            } catch (e) {
                                console.log('MPAI: Error processing JSON:', e);
                                console.log('MPAI: Not valid JSON, continuing with normal processing');
                            }
                            
                            // Process JSON results if found
                            if (jsonResult !== null && jsonResult.result) {
                                console.log('MPAI: Processing JSON result with embedded result property');
                                
                                // Get command type for specific formatting
                                const commandType = jsonResult.command_type || 'generic';
                                console.log('MPAI: Command type:', commandType);
                                
                                // Special handling for all tabular formats
                                if (typeof jsonResult.result === 'string') {
                                    console.log('MPAI: Examining string result for table formatting:', jsonResult.result.substring(0, 100));
                                    
                                    // The tab characters might be literal tabs \t or escaped tabs "\\t"
                                    // The newline characters might be literal newlines \n or escaped newlines "\\n"
                                    // We need to handle both cases
                                    
                                    // First, check if we have escaped characters
                                    let processedResult = jsonResult.result;
                                    
                                    // Replace escaped tabs with real tabs if present
                                    if (processedResult.includes('\\t')) {
                                        console.log('MPAI: Found escaped tabs, replacing with real tabs');
                                        processedResult = processedResult.replace(/\\t/g, '\t');
                                    }
                                    
                                    // Replace escaped newlines with real newlines if present
                                    if (processedResult.includes('\\n')) {
                                        console.log('MPAI: Found escaped newlines, replacing with real newlines');
                                        processedResult = processedResult.replace(/\\n/g, '\n');
                                    }
                                    
                                    // Check if we have tabs and newlines now
                                    const hasTabsAndNewlines = (
                                        processedResult.includes('\t') && 
                                        processedResult.includes('\n')
                                    );
                                    
                                    if (!hasTabsAndNewlines) {
                                        console.log('MPAI: Result does not have tabs and newlines after processing, showing as plain text');
                                        resultContent = `<pre class="mpai-command-result"><code>${jsonResult.result || 'No output'}</code></pre>`;
                                        return;
                                    }
                                    
                                    console.log('MPAI: Confirmed result has tabs and newlines, formatting as table');
                                    
                                    // Format as table - this handles formatted lists from the original JSON
                                    const rows = processedResult.trim().split('\n');
                                    
                                    // Generate title based on command type
                                    let tableTitle = '';
                                    switch(commandType) {
                                        case 'user_list':
                                            tableTitle = '<h3>WordPress Users</h3>';
                                            break;
                                        case 'post_list':
                                            tableTitle = '<h3>WordPress Posts</h3>';
                                            break;
                                        case 'plugin_list':
                                            tableTitle = '<h3>WordPress Plugins</h3>';
                                            break;
                                        case 'membership_list':
                                            tableTitle = '<h3>MemberPress Memberships</h3>';
                                            break;
                                        case 'transaction_list':
                                            tableTitle = '<h3>MemberPress Transactions</h3>';
                                            break;
                                        default:
                                            // No title for generic tables
                                            break;
                                    }
                                    
                                    let tableHtml = '<div class="mpai-result-table">';
                                    if (tableTitle) {
                                        tableHtml += tableTitle;
                                    }
                                    tableHtml += '<table>';
                                    
                                    rows.forEach((row, index) => {
                                        console.log(`MPAI: Processing row ${index}:`, row.substring(0, 50));
                                        
                                        // Split by tab character or by string representation of tab if needed
                                        const cells = row.includes('\t') ? 
                                            row.split('\t') : 
                                            row.split('t'); // Fallback if somehow we still have text "t" separators
                                        
                                        console.log(`MPAI: Row ${index} has ${cells.length} cells`);
                                        
                                        if (index === 0) {
                                            // Header row
                                            tableHtml += '<thead><tr>';
                                            cells.forEach(cell => {
                                                tableHtml += `<th>${cell}</th>`;
                                            });
                                            tableHtml += '</tr></thead><tbody>';
                                        } else {
                                            // Data row
                                            tableHtml += '<tr>';
                                            cells.forEach(cell => {
                                                tableHtml += `<td>${cell}</td>`;
                                            });
                                            tableHtml += '</tr>';
                                        }
                                    });
                                    
                                    tableHtml += '</tbody></table></div>';
                                    resultContent = tableHtml;
                                } else {
                                    // Generic JSON result formatting
                                    resultContent = `<pre class="mpai-command-result"><code>${jsonResult.result || 'No output'}</code></pre>`;
                                }
                            }
                            // Standard tab-separated processing (for non-JSON results)
                            else if (typeof response.data.result === 'string') {
                                console.log('MPAI: Checking standard string result for table format:', response.data.result.substring(0, 100));
                                
                                // The tab characters might be literal tabs \t or escaped tabs "\\t"
                                // The newline characters might be literal newlines \n or escaped newlines "\\n"
                                // We need to handle both cases
                                
                                // First, check if we have escaped characters
                                let processedResult = response.data.result;
                                
                                // Replace escaped tabs with real tabs if present
                                if (processedResult.includes('\\t')) {
                                    console.log('MPAI: Found escaped tabs, replacing with real tabs');
                                    processedResult = processedResult.replace(/\\t/g, '\t');
                                }
                                
                                // Replace escaped newlines with real newlines if present
                                if (processedResult.includes('\\n')) {
                                    console.log('MPAI: Found escaped newlines, replacing with real newlines');
                                    processedResult = processedResult.replace(/\\n/g, '\n');
                                }
                                
                                // Check if we have tabs and newlines now
                                const hasTabsAndNewlines = (
                                    processedResult.includes('\t') && 
                                    processedResult.includes('\n')
                                );
                                
                                if (!hasTabsAndNewlines) {
                                    console.log('MPAI: Standard result does not contain tabs and newlines after processing');
                                    resultContent = `<pre class="mpai-command-result"><code>${response.data.result || 'No output'}</code></pre>`;
                                    $result.html(resultContent);
                                    return;
                                }
                                
                                console.log('MPAI: Standard result has tabs and newlines, formatting as table');
                                
                                // Format as table
                                const rows = processedResult.trim().split('\n');
                                let tableHtml = '<div class="mpai-result-table"><table>';
                                
                                rows.forEach((row, index) => {
                                    console.log(`MPAI: Processing standard row ${index}:`, row.substring(0, 50));
                                    
                                    // Split by tab character or by string representation of tab if needed
                                    const cells = row.includes('\t') ? 
                                        row.split('\t') : 
                                        row.split('t'); // Fallback if somehow we still have text "t" separators
                                    
                                    console.log(`MPAI: Standard row ${index} has ${cells.length} cells`);
                                    
                                    if (index === 0) {
                                        // Header row
                                        tableHtml += '<thead><tr>';
                                        cells.forEach(cell => {
                                            tableHtml += `<th>${cell}</th>`;
                                        });
                                        tableHtml += '</tr></thead><tbody>';
                                    } else {
                                        // Data row
                                        tableHtml += '<tr>';
                                        cells.forEach(cell => {
                                            tableHtml += `<td>${cell}</td>`;
                                        });
                                        tableHtml += '</tr>';
                                    }
                                });
                                
                                tableHtml += '</tbody></table></div>';
                                resultContent = tableHtml;
                            } else {
                                // For other WP CLI results, format with a monospace font
                                resultContent = `<pre class="mpai-command-result"><code>${response.data.result || 'No output'}</code></pre>`;
                            }
                        } 
                        // Check if it's memberpress_info
                        else if (toolName === 'memberpress_info') {
                            try {
                                // Try to parse as JSON and format as info card
                                let data;
                                if (typeof response.data.result === 'string') {
                                    data = JSON.parse(response.data.result);
                                } else {
                                    data = response.data.result;
                                }
                                
                                // Create a nice summary display
                                let infoHtml = '<div class="mpai-info-card">';
                                
                                // Add a title based on the type
                                const type = jsonData.parameters && jsonData.parameters.type ? 
                                    jsonData.parameters.type : 'summary';
                                
                                infoHtml += `<h4 class="mpai-info-title">MemberPress ${type.charAt(0).toUpperCase() + type.slice(1)}</h4>`;
                                infoHtml += '<div class="mpai-info-content">';
                                
                                // Format the data based on what we have
                                if (Array.isArray(data)) {
                                    // For arrays (like memberships, members, etc.)
                                    infoHtml += '<ul>';
                                    data.forEach(item => {
                                        if (item.title) {
                                            infoHtml += `<li><strong>${item.title}</strong>`;
                                            if (item.id) infoHtml += ` (ID: ${item.id})`;
                                            infoHtml += '</li>';
                                        } else if (item.name || item.display_name) {
                                            infoHtml += `<li><strong>${item.name || item.display_name}</strong>`;
                                            if (item.email) infoHtml += ` (${item.email})`;
                                            infoHtml += '</li>';
                                        } else {
                                            infoHtml += `<li>${JSON.stringify(item)}</li>`;
                                        }
                                    });
                                    infoHtml += '</ul>';
                                } else if (typeof data === 'object') {
                                    // For objects with key-value pairs
                                    infoHtml += '<table class="mpai-info-table">';
                                    Object.entries(data).forEach(([key, value]) => {
                                        infoHtml += '<tr>';
                                        infoHtml += `<th>${key.replace(/_/g, ' ')}</th>`;
                                        infoHtml += `<td>${typeof value === 'object' ? JSON.stringify(value) : value}</td>`;
                                        infoHtml += '</tr>';
                                    });
                                    infoHtml += '</table>';
                                } else {
                                    // Fallback for simple values
                                    infoHtml += `<p>${data}</p>`;
                                }
                                
                                infoHtml += '</div></div>';
                                resultContent = infoHtml;
                            } catch (e) {
                                // If parsing fails, fall back to simpler formatting
                                resultContent = `<pre class="mpai-command-result"><code>${response.data.result || 'No data available'}</code></pre>`;
                            }
                        } else {
                            // For other tools, use a simpler format
                            if (typeof response.data.result === 'string') {
                                resultContent = `<pre class="mpai-command-result"><code>${response.data.result}</code></pre>`;
                            } else {
                                resultContent = `<pre class="mpai-command-result"><code>${JSON.stringify(response.data.result, null, 2)}</code></pre>`;
                            }
                        }
                        
                        // Display the result
                        $result.html(resultContent);
                    } else {
                        // Update status to error
                        $status.removeClass('mpai-tool-call-processing').addClass('mpai-tool-call-error');
                        $status.html('Error');
                        
                        // Display the error
                        const errorMessage = response.data && response.data.error ? response.data.error : 
                                           (response.data || 'Unknown error executing tool');
                        $result.html(`<div class="mpai-tool-call-error-message">${errorMessage}</div>`);
                    }
                    
                    // Scroll to bottom to show results
                    setTimeout(scrollToBottom, 100);
                },
                error: function(xhr, status, error) {
                    console.error('MPAI: AJAX error executing tool', {xhr, status, error});
                    
                    const $toolCall = $('#' + toolId);
                    if (!$toolCall.length) return;
                    
                    const $status = $toolCall.find('.mpai-tool-call-status');
                    const $result = $toolCall.find('.mpai-tool-call-result');
                    
                    // Update status to error
                    $status.removeClass('mpai-tool-call-processing').addClass('mpai-tool-call-error');
                    $status.html('Error');
                    
                    // Display the error
                    let errorMessage = `AJAX error: ${error}`;
                    if (xhr.responseText) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.data) {
                                errorMessage += ` - ${response.data}`;
                            }
                        } catch (e) {
                            // Can't parse response, use the raw text
                            if (xhr.responseText.length < 100) {
                                errorMessage += ` - ${xhr.responseText}`;
                            }
                        }
                    }
                    $result.html(`<div class="mpai-tool-call-error-message">${errorMessage}</div>`);
                    
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
                // Store any wp commands so we can make them clickable
                const wpCommands = [];
                
                // Wrap all replacement operations in try/catch to prevent cascading failures
                
                try {
                    // Process code blocks first to avoid interference with other replacements
                    content = content.replace(/```([\s\S]*?)```/g, function(match, p1) {
                        // Clean up the code content
                        p1 = p1.trim();
                        
                        // Extract WP-CLI commands from code blocks
                        if (p1.match(/^(sh|bash|shell|command|cmd|wp|wordpress)\b/i)) {
                            // This is likely a command block
                            const lines = p1.split('\n');
                            for (let line of lines) {
                                // Look for WP CLI commands
                                const wpCliMatch = line.match(/^(wp\s+.*?)(\s*#.*)?$/);
                                if (wpCliMatch) {
                                    const command = wpCliMatch[1].trim();
                                    wpCommands.push(command);
                                }
                            }
                        }
                        
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
                    // Special handling for inline WP CLI commands - make them runnable
                    content = content.replace(/`(wp\s+[^`]+)`/g, function(match, p1) {
                        const command = p1.trim();
                        wpCommands.push(command);
                        return '<code class="mpai-runnable-command" data-command="' + 
                            command.replace(/"/g, '&quot;') + 
                            '">' + p1.replace(/</g, '&lt;').replace(/>/g, '&gt;') + 
                            ' <span class="mpai-run-indicator">▶</span></code>';
                    });
                } catch (e) {
                    console.error('Error processing wp commands:', e);
                }
                
                try {
                    // Convert `code` to <code>code</code> (excluding what's already processed for WP commands)
                    content = content.replace(/`([^`]+)`/g, function(match, p1) {
                        if (p1.trim().startsWith('wp ')) {
                            // Already processed as a WP command
                            return match;
                        }
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
                
                // If we found any WP commands, add a toolbar to the message
                if (wpCommands.length > 0) {
                    console.log('MPAI: Found WP commands in message:', wpCommands);
                    
                    // Add a toolbar at the top
                    let toolbarHtml = '<div class="mpai-command-toolbar">';
                    
                    // If we have more than one command, add a dropdown
                    if (wpCommands.length === 1) {
                        toolbarHtml += '<button class="mpai-run-suggested-command" data-command="' + 
                            wpCommands[0].replace(/"/g, '&quot;') + 
                            '">Run Command: ' + wpCommands[0] + '</button>';
                    } else if (wpCommands.length > 1) {
                        toolbarHtml += '<select class="mpai-command-select">';
                        toolbarHtml += '<option value="">Select a command...</option>';
                        
                        wpCommands.forEach((cmd, index) => {
                            toolbarHtml += '<option value="' + index + '">' + cmd + '</option>';
                        });
                        
                        toolbarHtml += '</select>';
                        toolbarHtml += '<button class="mpai-run-selected-command" disabled>Run Selected Command</button>';
                    }
                    
                    toolbarHtml += '</div>';
                    
                    content = toolbarHtml + content;
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

        // Command Runner
        const $commandRunner = $('#mpai-command-runner');
        const $runCommandBtn = $('#mpai-run-command');
        const $commandInput = $('#mpai-command-input');
        const $commandExecute = $('#mpai-command-execute');
        const $commandCancel = $('#mpai-command-cancel');
        const $commandClose = $('#mpai-command-close');
        
        // Show command runner when the Run Command button is clicked
        $runCommandBtn.on('click', function() {
            $commandRunner.slideDown(300);
            $commandInput.focus();
        });
        
        // Hide command runner
        function hideCommandRunner() {
            $commandRunner.slideUp(300);
            $commandInput.val('');
        }
        
        // Set up command close/cancel buttons
        $commandClose.on('click', hideCommandRunner);
        $commandCancel.on('click', hideCommandRunner);
        
        // Handle clicks on runnable commands in the chat messages
        $(document).on('click', '.mpai-runnable-command', function() {
            const command = $(this).data('command');
            if (command) {
                $commandInput.val(command);
                $commandRunner.slideDown(300);
            }
        });
        
        // Handle clicks on the suggested command button in the toolbar
        $(document).on('click', '.mpai-run-suggested-command', function() {
            const command = $(this).data('command');
            if (command) {
                // Execute the command directly
                executeWpCommand(command);
            }
        });
        
        // Handle command selection in dropdown
        $(document).on('change', '.mpai-command-select', function() {
            const index = $(this).val();
            const $runBtn = $(this).siblings('.mpai-run-selected-command');
            
            if (index !== '') {
                // Enable run button
                $runBtn.prop('disabled', false);
            } else {
                // Disable run button
                $runBtn.prop('disabled', true);
            }
        });
        
        // Handle clicks on the run selected command button
        $(document).on('click', '.mpai-run-selected-command', function() {
            if ($(this).prop('disabled')) {
                return;
            }
            
            const $select = $(this).siblings('.mpai-command-select');
            const index = $select.val();
            
            if (index !== '') {
                const command = $select.find('option:selected').text();
                executeWpCommand(command);
            }
        });
        
        // Function to execute a WP command
        function executeWpCommand(command) {
            // Add a message showing what command we're running
            addMessageToChat('user', 'Run command: `' + command + '`');
            
            // Show typing indicator 
            showTypingIndicator();
            
            // Log what we're executing
            console.log('MPAI: Executing WP command:', command);
            
            // Try a direct AJAX approach instead of using the tool call mechanism
            // This is a fallback approach that might work differently
            
            // Create a simpler tool request
            const simpleToolRequest = {
                "name": "wp_cli",
                "parameters": {
                    "command": command
                }
            };
            
            console.log('MPAI: Sending direct AJAX request with simplified format');
            
            // Add a temporary processing message
            const processingMessageId = 'processing-' + Date.now();
            addMessageToChat('assistant', `<div id="${processingMessageId}">Running command \`${command}\`...<br><div class="mpai-loading-dots"><span></span><span></span><span></span></div></div>`);
            
            // Make the AJAX request
            $.ajax({
                url: mpai_chat_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'mpai_run_command', // Use the simpler endpoint
                    command: command,
                    context: '',
                    nonce: mpai_chat_data.nonce
                },
                success: function(response) {
                    console.log('MPAI: Command execution response:', response);
                    
                    // Replace the processing message
                    const $processingMessage = $('#' + processingMessageId);
                    
                    if (response.success) {
                        let resultContent = '';
                        let outputData = response.data && response.data.output ? response.data.output : 'No output available';
                        
                        // First try to parse the output as JSON (for our new format)
                        let commandType = 'generic';
                        try {
                            console.log('MPAI: Examining output data type:', typeof outputData);
                            console.log('MPAI: Output data preview:', typeof outputData === 'string' 
                                ? outputData.substring(0, 100) + '...' 
                                : outputData);
                            
                            // Try to parse the output as JSON first (if it's a string)
                            if (typeof outputData === 'string' && 
                                (outputData.trim().startsWith('{') && outputData.trim().endsWith('}'))) {
                                console.log('MPAI: Found JSON string in command execution response');
                                const jsonOutput = JSON.parse(outputData);
                                
                                // Check if it has the new tool response format
                                if (jsonOutput.success && jsonOutput.tool === 'wp_cli' && jsonOutput.result) {
                                    console.log('MPAI: Processing structured tool response from command');
                                    // Extract the command type if available
                                    if (jsonOutput.command_type) {
                                        commandType = jsonOutput.command_type;
                                        console.log('MPAI: Command type from JSON:', commandType);
                                    }
                                    outputData = jsonOutput.result;
                                }
                            } 
                            // Check if the output is already a parsed JSON object (direct pass-through case)
                            else if (typeof outputData === 'object' && outputData !== null) {
                                console.log('MPAI: Output is already a parsed JSON object');
                                // Use it as is, but extract relevant data if it follows our format
                                if (outputData.success && outputData.tool === 'wp_cli' && outputData.result) {
                                    if (outputData.command_type) {
                                        commandType = outputData.command_type;
                                        console.log('MPAI: Command type from direct object:', commandType);
                                    }
                                    outputData = outputData.result;
                                }
                            } else {
                                console.log('MPAI: Output does not appear to be JSON or an object, using as-is');
                            }
                        } catch (e) {
                            console.log('MPAI: Error while processing output data:', e);
                            // Continue with the original output
                        }
                        
                        // Add detailed logging for debugging
                        console.log('MPAI: Final output data type:', typeof outputData);
                        console.log('MPAI: Final command type:', commandType);
                        console.log('MPAI: Final output data preview:', typeof outputData === 'string' 
                            ? outputData.substring(0, 100) + '...' 
                            : outputData);
                        
                        // Format the output if it looks like a table
                        if (outputData && typeof outputData === 'string' && 
                            outputData.includes('\t') && outputData.includes('\n')) {
                            
                            // Generate title based on command type
                            let tableTitle = '';
                            switch(commandType) {
                                case 'user_list':
                                    tableTitle = '<h3>WordPress Users</h3>';
                                    break;
                                case 'post_list':
                                    tableTitle = '<h3>WordPress Posts</h3>';
                                    break;
                                case 'plugin_list':
                                    tableTitle = '<h3>WordPress Plugins</h3>';
                                    break;
                                case 'membership_list':
                                    tableTitle = '<h3>MemberPress Memberships</h3>';
                                    break;
                                case 'transaction_list':
                                    tableTitle = '<h3>MemberPress Transactions</h3>';
                                    break;
                                default:
                                    // Generic title for regular commands
                                    tableTitle = '<h3>Command Results</h3>';
                                    break;
                            }
                            
                            // Format as table
                            const rows = outputData.trim().split('\n');
                            let tableHtml = '<div class="mpai-result-table">';
                            
                            // Add title
                            if (tableTitle) {
                                tableHtml += tableTitle;
                            }
                            
                            tableHtml += '<table>';
                            
                            rows.forEach((row, index) => {
                                const cells = row.split('\t');
                                
                                if (index === 0) {
                                    // Header row
                                    tableHtml += '<thead><tr>';
                                    cells.forEach(cell => {
                                        tableHtml += `<th>${cell}</th>`;
                                    });
                                    tableHtml += '</tr></thead><tbody>';
                                } else {
                                    // Data row
                                    tableHtml += '<tr>';
                                    cells.forEach((cell, cellIndex) => {
                                        tableHtml += `<td>${cell}</td>`;
                                    });
                                    tableHtml += '</tr>';
                                }
                            });
                            
                            tableHtml += '</tbody></table></div>';
                            resultContent = `<div class="command-success">${tableHtml}</div>`;
                        } else {
                            // Just show the raw output
                            resultContent = `<strong>Command executed successfully:</strong><br><pre><code>${outputData || 'No output'}</code></pre>`;
                        }
                        
                        $processingMessage.html(resultContent);
                    } else {
                        // Error executing command
                        $processingMessage.html(`<strong>Error executing command:</strong><br><pre><code>${response.message || 'Unknown error'}</code></pre>`);
                    }
                    
                    // Scroll to bottom to show results
                    setTimeout(scrollToBottom, 100);
                },
                error: function(xhr, status, error) {
                    console.error('MPAI: AJAX error executing command:', error);
                    console.error('MPAI: AJAX status:', status);
                    console.error('MPAI: AJAX response status code:', xhr.status);
                    console.error('MPAI: AJAX response text:', xhr.responseText);
                    
                    // Replace the processing message with error
                    const $processingMessage = $('#' + processingMessageId);
                    $processingMessage.html(`<strong>Error executing command:</strong><br><pre><code>AJAX error: ${error} (Status: ${xhr.status})</code></pre>`);
                    
                    // Scroll to bottom to show error
                    setTimeout(scrollToBottom, 100);
                }
            });
            
            // Hide typing indicator after setting up the request
            hideTypingIndicator();
        }
        
        // Handle clicking on a command item in the command list
        $(document).on('click', '.mpai-command-item', function(e) {
            e.preventDefault();
            
            const command = $(this).data('command');
            
            if (!command) {
                return;
            }
            
            // Hide command runner
            hideCommandRunner();
            
            // Add the command to the chat input
            $chatInput.val(command);
            
            // Focus the input and adjust its height
            $chatInput.focus();
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