/**
 * MemberPress AI Assistant - Chat Interface Script
 */

(function($) {
    'use strict';

    // Initialize the chat interface once the document is ready
    $(document).ready(function() {
        // Check if the logger is available and log initialization
        if (window.mpaiLogger) {
            window.mpaiLogger.info('Chat interface initializing', 'ui');
        }
        
        // DOM elements
        const $chatToggle = $('#mpai-chat-toggle');
        const $chatContainer = $('#mpai-chat-container');
        const $chatMessages = $('#mpai-chat-messages');
        const $chatInput = $('#mpai-chat-input');
        const $chatForm = $('#mpai-chat-form');
        const $chatExpand = $('#mpai-chat-expand');
        const $chatMinimize = $('#mpai-chat-minimize');
        const $chatClose = $('#mpai-chat-close');
        const $chatClear = $('#mpai-chat-clear');
        const $chatSubmit = $('#mpai-chat-submit');
        const $exportChat = $('#mpai-export-chat');

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

            // Log the message being sent
            if (window.mpaiLogger) {
                window.mpaiLogger.info('Sending user message: ' + message.substring(0, 50) + (message.length > 50 ? '...' : ''), 'api_calls');
                window.mpaiLogger.startTimer('message_processing');
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
                    // Log the response received
                    if (window.mpaiLogger) {
                        const elapsed = window.mpaiLogger.endTimer('message_processing');
                        window.mpaiLogger.info('Received response in ' + (elapsed ? elapsed.toFixed(2) + 'ms' : 'unknown time'), 'api_calls');
                    }
                    
                    // Hide typing indicator
                    hideTypingIndicator();

                    if (response.success && response.data && response.data.response) {
                        // Process response for tool calls
                        let processedResponse = processToolCalls(response.data.response);
                        
                        // Add the response to the chat
                        addMessageToChat('assistant', processedResponse);
                        
                        if (window.mpaiLogger) {
                            window.mpaiLogger.debug('Response successfully processed and added to chat', 'api_calls');
                        }
                    } else {
                        // Show error message
                        addMessageToChat('assistant', mpai_chat_data.strings.error_message);
                        
                        if (window.mpaiLogger) {
                            window.mpaiLogger.error('Invalid response format received', 'api_calls', response);
                        } else {
                            console.error('MPAI: Invalid response format:', response);
                        }
                    }

                    // Scroll to the bottom with a slight delay to ensure content is rendered
                    setTimeout(scrollToBottom, 100);
                },
                error: function(xhr, status, error) {
                    // Log the error
                    if (window.mpaiLogger) {
                        window.mpaiLogger.error('AJAX error when sending message', 'api_calls', {
                            xhr: xhr,
                            status: status,
                            error: error
                        });
                        window.mpaiLogger.endTimer('message_processing');
                    } else {
                        console.error('MPAI: AJAX error:', error);
                    }
                    
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
            // Log the first 200 characters of the response to see what format it's in
            console.log('MPAI: Response preview:', response.substring(0, 200) + (response.length > 200 ? '...' : ''));
            // Look for markers that might indicate tool calls
            console.log('MPAI: Response contains tool markers:', {
                'jsonBlock': response.includes('```json'),
                'jsonObjectBlock': response.includes('```json-object'),
                'toolProperty': response.includes('"tool"'),
                'parametersProperty': response.includes('"parameters"')
            });
            
            // Match JSON blocks that look like tool calls
            // Support multiple formats:
            // 1. ```json\n{...}\n``` - Standard code block with JSON
            // 2. ```json-object\n{...}\n``` - Special marker for pre-parsed JSON that shouldn't be double-encoded
            // 3. {tool: ..., parameters: ...} - Direct JSON in text
            // Enhanced regex patterns to catch different code block and tool call formats
            const jsonBlockRegex = /```(?:json)?\s*\n({[\s\S]*?})\s*\n```/g;  // Standard JSON code blocks with optional 'json' tag
            const jsonObjectBlockRegex = /```(?:json-object)?\s*\n({[\s\S]*?})\s*\n```/g;  // JSON-object blocks
            const directJsonRegex = /\{[\s\S]*?["']tool["'][\s\S]*?["']parameters["'][\s\S]*?\}/g;  // Direct JSON in text with tool/parameters
            // Additional patterns to catch more variations
            const anyCodeBlockRegex = /```[\w-]*\s*\n(\{[\s\S]*?["']tool["'][\s\S]*?["']parameters["'][\s\S]*?\})\s*\n```/g;  // Any code block with tool calls
            const altFormatRegex = /```\s*(\{[\s\S]*?["']tool["'][\s\S]*?["']parameters["'][\s\S]*?\})\s*```/g;  // No newlines after ticks
            
            // Enhanced patterns for additional edge cases
            const indentedJsonBlockRegex = /```(?:json)?\s*\n\s*({[\s\S]*?})\s*\n```/g;  // JSON with indentation
            const multilineToolRegex = /\{\s*[\r\n\s]*"tool"[\s\S]*?"parameters"[\s\S]*?\}/g;  // Multi-line JSON without code blocks
            const singleQuoteJsonRegex = /```(?:json)?\s*\n({'[\s\S]*?'})\s*\n```/g;  // JSON with single quotes
            const backtickFenceVariantRegex = /``\s*\n({[\s\S]*?})\s*\n``/g;  // Two backticks instead of three
            
            // Log the regex patterns we're using to find tool calls
            console.log('MPAI: Using regex patterns:', {
                jsonBlockRegex: jsonBlockRegex.toString(),
                jsonObjectBlockRegex: jsonObjectBlockRegex.toString(),
                directJsonRegex: directJsonRegex.toString(),
                anyCodeBlockRegex: anyCodeBlockRegex.toString(),
                altFormatRegex: altFormatRegex.toString()
            });
            
            // Test each pattern on the response text and log the results
            // Note: We need to reset the regex patterns after each test since they're global
            console.log('MPAI: Testing jsonBlockRegex:', jsonBlockRegex.test(response));
            jsonBlockRegex.lastIndex = 0;
            
            console.log('MPAI: Testing jsonObjectBlockRegex:', jsonObjectBlockRegex.test(response));
            jsonObjectBlockRegex.lastIndex = 0;
            
            console.log('MPAI: Testing directJsonRegex:', directJsonRegex.test(response));
            directJsonRegex.lastIndex = 0;
            
            console.log('MPAI: Testing anyCodeBlockRegex:', anyCodeBlockRegex.test(response));
            anyCodeBlockRegex.lastIndex = 0;
            
            console.log('MPAI: Testing altFormatRegex:', altFormatRegex.test(response));
            altFormatRegex.lastIndex = 0;
            
            // Log exact regex match content for better debugging
            if (response.length < 500) {
                console.log('MPAI: Full response for regex analysis:', response);
            } else {
                // Log sections of the response where we might find tool calls
                const codeBlockMatch = response.match(/```[\s\S]*?```/g);
                if (codeBlockMatch) {
                    console.log('MPAI: Found code blocks that might contain tool calls:', codeBlockMatch);
                }
            }
            
            let match;
            let processedResponse = response;
            let matches = [];
            
            // Process all regex patterns in a unified way to find tool calls
            function processMatchesFromPattern(pattern, patternName) {
                let patternMatch;
                while ((patternMatch = pattern.exec(response)) !== null) {
                    try {
                        console.log(`MPAI: Found potential match with ${patternName}`, patternMatch[1]);
                        
                        // Skip empty matches
                        if (!patternMatch[1] || patternMatch[1].trim() === '') {
                            console.log(`MPAI: Skipping empty match from ${patternName}`);
                            continue;
                        }
                        
                        // Try to parse the JSON
                        const jsonData = JSON.parse(patternMatch[1]);
                        
                        // Check if this is a formatted tabular result that we can display directly
                        if (jsonData.success && jsonData.tool && jsonData.result && 
                            typeof jsonData.result === 'object' && 
                            jsonData.result.command_type && jsonData.result.result) {
                            
                            console.log(`MPAI: Found formatted tabular result with ${patternName}`, jsonData);
                            
                            // Format the result directly without executing a tool call
                            processedResponse = processedResponse.replace(patternMatch[0], formatTabularResult(jsonData.result));
                            continue;
                        }
                        
                        // Only process if it looks like a tool call (has tool and parameters properties)
                        // and isn't already a tool result (no success or error properties)
                        if (jsonData.tool && jsonData.parameters && 
                            !jsonData.hasOwnProperty('success') && !jsonData.hasOwnProperty('error')) {
                            console.log(`MPAI: Valid tool call found with ${patternName}`, jsonData);
                            
                            // Check if we already have this match to avoid duplicates
                            const isDuplicate = matches.some(existing => 
                                existing.jsonData.tool === jsonData.tool && 
                                JSON.stringify(existing.jsonData.parameters) === JSON.stringify(jsonData.parameters)
                            );
                            
                            if (!isDuplicate) {
                                matches.push({
                                    fullMatch: patternMatch[0],
                                    jsonStr: patternMatch[1],
                                    jsonData: jsonData,
                                    patternUsed: patternName
                                });
                            } else {
                                console.log(`MPAI: Skipping duplicate tool call from ${patternName}`);
                            }
                        }
                    } catch (e) {
                        console.error(`MPAI: Error parsing potential tool call with ${patternName}:`, e);
                    }
                }
            }
            
            // Process all patterns
            // Process all pattern variations
            processMatchesFromPattern(jsonBlockRegex, 'jsonBlockRegex');
            processMatchesFromPattern(jsonObjectBlockRegex, 'jsonObjectBlockRegex');
            processMatchesFromPattern(anyCodeBlockRegex, 'anyCodeBlockRegex');
            processMatchesFromPattern(altFormatRegex, 'altFormatRegex');
            
            // Process enhanced patterns
            processMatchesFromPattern(indentedJsonBlockRegex, 'indentedJsonBlockRegex');
            processMatchesFromPattern(multilineToolRegex, 'multilineToolRegex');
            processMatchesFromPattern(singleQuoteJsonRegex, 'singleQuoteJsonRegex');
            processMatchesFromPattern(backtickFenceVariantRegex, 'backtickFenceVariantRegex');
            
            // Process direct JSON format separately because it's different (match[0] instead of match[1])
            let directMatch;
            while ((directMatch = directJsonRegex.exec(response)) !== null) {
                try {
                    const jsonStr = directMatch[0];
                    
                    // Skip if this JSON string is inside a code block we already processed
                    const isInCodeBlock = processedResponse.includes('```') && 
                                         processedResponse.includes(jsonStr) &&
                                         processedResponse.indexOf('```') < processedResponse.indexOf(jsonStr);
                    
                    if (isInCodeBlock) {
                        console.log('MPAI: Skipping direct JSON match that is part of a code block');
                        continue;
                    }
                    
                    console.log('MPAI: Found direct JSON outside code blocks', jsonStr);
                    
                    try {
                        const jsonData = JSON.parse(jsonStr);
                        
                        // Check if this is a formatted tabular result
                        if (jsonData.success && jsonData.tool && jsonData.result && 
                            typeof jsonData.result === 'object' && 
                            jsonData.result.command_type && jsonData.result.result) {
                            
                            console.log('MPAI: Found formatted tabular result in direct JSON', jsonData);
                            processedResponse = processedResponse.replace(jsonStr, formatTabularResult(jsonData.result));
                            continue;
                        }
                        
                        // Only process if it looks like a tool call
                        if (jsonData.tool && jsonData.parameters && 
                            !jsonData.hasOwnProperty('success') && !jsonData.hasOwnProperty('error')) {
                            
                            // Check for duplicates
                            const isDuplicate = matches.some(existing => 
                                existing.jsonData.tool === jsonData.tool && 
                                JSON.stringify(existing.jsonData.parameters) === JSON.stringify(jsonData.parameters)
                            );
                            
                            if (!isDuplicate) {
                                console.log('MPAI: Valid tool call in direct JSON', jsonData);
                                matches.push({
                                    fullMatch: jsonStr,
                                    jsonStr: jsonStr,
                                    jsonData: jsonData,
                                    patternUsed: 'directJsonRegex'
                                });
                            } else {
                                console.log('MPAI: Skipping duplicate direct JSON tool call');
                            }
                        }
                    } catch (e) {
                        console.error('MPAI: Error parsing potential direct JSON tool call:', e);
                    }
                } catch (e) {
                    console.error('MPAI: Error processing direct JSON match:', e);
                }
            }
            
            // Add more diagnostics about matches found
            console.log('MPAI: Found', matches.length, 'tool calls to process');
            
            if (matches.length > 0) {
                // Log pattern usage statistics
                const patternStats = {};
                matches.forEach(match => {
                    if (!patternStats[match.patternUsed]) {
                        patternStats[match.patternUsed] = 0;
                    }
                    patternStats[match.patternUsed]++;
                });
                console.log('MPAI: Tool call detection pattern statistics:', patternStats);
                
                // Log tools being used
                const toolsUsed = matches.map(match => match.jsonData.tool);
                console.log('MPAI: Tools being used:', toolsUsed);
            } else {
                // Additional diagnostics when no tool calls found to help understand why
                console.log('MPAI: No tool calls found. Response contains:');
                console.log('- JSON code blocks:', response.includes('```json'));
                console.log('- Any code blocks:', response.includes('```'));
                console.log('- Tool keyword:', response.includes('"tool"') || response.includes("'tool'"));
                console.log('- Parameters keyword:', response.includes('"parameters"') || response.includes("'parameters'"));
                
                // Test each regex pattern individually to see which ones might have matched
                console.log('MPAI: Testing individual patterns:');
                console.log('- jsonBlockRegex:', response.match(jsonBlockRegex) !== null);
                console.log('- jsonObjectBlockRegex:', response.match(jsonObjectBlockRegex) !== null);
                console.log('- anyCodeBlockRegex:', response.match(anyCodeBlockRegex) !== null);
                console.log('- altFormatRegex:', response.match(altFormatRegex) !== null);
                console.log('- indentedJsonBlockRegex:', response.match(indentedJsonBlockRegex) !== null);
                console.log('- multilineToolRegex:', response.match(multilineToolRegex) !== null);
                console.log('- singleQuoteJsonRegex:', response.match(singleQuoteJsonRegex) !== null);
                console.log('- backtickFenceVariantRegex:', response.match(backtickFenceVariantRegex) !== null);
                console.log('- directJsonRegex:', response.match(directJsonRegex) !== null);
                
                // Check for alternative formats that might not be matching
                if (response.includes('tool') && response.includes('parameters')) {
                    console.log('MPAI: Response contains "tool" and "parameters" keywords, but might be in an unexpected format:');
                    const potentialMatches = response.match(/tool[\s\S]*?parameters/g);
                    if (potentialMatches) {
                        console.log('MPAI: Potential undetected tool patterns:', potentialMatches);
                        
                        // Extract code blocks for inspection
                        const codeBlocks = response.match(/```[\s\S]*?```/g);
                        if (codeBlocks && codeBlocks.length > 0) {
                            console.log('MPAI: Code blocks in response for inspection:', codeBlocks);
                        }
                        console.log('MPAI: Potential undetected tool patterns:', potentialMatches);
                    }
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
                
                // Execute the tool call with pattern information
                executeToolCall(match.jsonStr, match.jsonData, toolId, match.patternUsed);
            });
            
            return processedResponse;
        }
        
        /**
         * Execute a tool call
         * 
         * @param {string} jsonStr - The tool call JSON string
         * @param {object} jsonData - The parsed tool call JSON
         * @param {string} toolId - The tool call element ID
         * @param {string} patternUsed - Which regex pattern found this tool call
         */
        function executeToolCall(jsonStr, jsonData, toolId, patternUsed) {
            // Enhanced logging for the tool call
            if (window.mpaiLogger) {
                window.mpaiLogger.logToolUsage(jsonData.tool, jsonData.parameters);
                window.mpaiLogger.startTimer('tool_' + toolId);
                window.mpaiLogger.debug(`Tool call detected with ${patternUsed || 'unknown pattern'}`, 'tool_usage', {
                    tool: jsonData.tool,
                    parameters: jsonData.parameters,
                    toolId: toolId,
                    jsonStr: jsonStr.substring(0, 100) + (jsonStr.length > 100 ? '...' : '')
                });
            } else {
                console.log('MPAI: Executing tool call', {
                    tool: jsonData.tool,
                    parameters: jsonData.parameters,
                    toolId: toolId,
                    patternUsed: patternUsed || 'unknown pattern'
                });
            }
            
            // Construct the tool request in the format expected by the backend
            const toolRequest = {
                name: jsonData.tool,
                parameters: jsonData.parameters
            };
            
            // Log the prepared request
            if (window.mpaiLogger) {
                window.mpaiLogger.debug('Tool request prepared', 'tool_usage', toolRequest);
            } else {
                console.log('MPAI: Tool request being sent:', toolRequest);
                console.log('MPAI: Raw tool_request parameter:', JSON.stringify(toolRequest));
            }
            
            // Log request timing start
            const requestStartTime = performance.now();
            
            $.ajax({
                url: mpai_chat_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'mpai_execute_tool',
                    tool_request: JSON.stringify(toolRequest),
                    nonce: mpai_chat_data.nonce // Try using the regular nonce instead
                },
                success: function(response) {
                    // Calculate the elapsed time since request start
                    const requestElapsed = performance.now() - requestStartTime;
                    
                    if (window.mpaiLogger) {
                        const loggerElapsed = window.mpaiLogger.endTimer('tool_' + toolId);
                        window.mpaiLogger.info(
                            'Tool "' + jsonData.tool + '" executed in ' + (loggerElapsed ? loggerElapsed.toFixed(2) + 'ms' : requestElapsed.toFixed(2) + 'ms'), 
                            'tool_usage'
                        );
                        window.mpaiLogger.debug('Tool execution response', 'tool_usage', {
                            response: response,
                            requestTime: requestElapsed.toFixed(2) + 'ms',
                            toolId: toolId,
                            tool: jsonData.tool
                        });
                    } else {
                        console.log('MPAI: Tool execution response', {
                            response: response,
                            requestTime: requestElapsed.toFixed(2) + 'ms',
                            toolId: toolId,
                            tool: jsonData.tool
                        });
                    }
                    
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
                                // Check first if this is already a formatted table output
                                if (typeof response.data.result === 'string' && 
                                    response.data.result.includes('\t') && 
                                    response.data.result.includes('\n')) {
                                    
                                    console.log('MPAI: Detected tabular data in memberpress_info result');
                                    
                                    // Format as table
                                    const rows = response.data.result.trim().split('\n');
                                    let tableHtml = '<div class="mpai-result-table">';
                                    
                                    // Add title based on parameter type
                                    const type = jsonData.parameters && jsonData.parameters.type ? 
                                        jsonData.parameters.type : 'summary';
                                    
                                    tableHtml += `<h3>MemberPress ${type.charAt(0).toUpperCase() + type.slice(1)}</h3>`;
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
                                            cells.forEach(cell => {
                                                tableHtml += `<td>${cell}</td>`;
                                            });
                                            tableHtml += '</tr>';
                                        }
                                    });
                                    
                                    tableHtml += '</tbody></table></div>';
                                    resultContent = tableHtml;
                                    
                                } else {
                                    // Try the traditional JSON parsing approach
                                    let data;
                                    if (typeof response.data.result === 'string') {
                                        data = JSON.parse(response.data.result);
                                    } else {
                                        data = response.data.result;
                                    }
                                    
                                    // Check if this is a pre-formatted tabular result
                                    if (data && data.command_type && data.result && 
                                        typeof data.result === 'string' && 
                                        data.result.includes('\t') && 
                                        data.result.includes('\n')) {
                                        
                                        console.log('MPAI: Found pre-formatted tabular result in JSON');
                                        
                                        // Format as table
                                        const rows = data.result.trim().split('\n');
                                        let tableHtml = '<div class="mpai-result-table">';
                                        
                                        // Add title based on command type
                                        let tableTitle = '';
                                        switch(data.command_type) {
                                            case 'member_list':
                                                tableTitle = '<h3>MemberPress Members</h3>';
                                                break;
                                            case 'membership_list':
                                                tableTitle = '<h3>MemberPress Memberships</h3>';
                                                break;
                                            case 'transaction_list':
                                                tableTitle = '<h3>MemberPress Transactions</h3>';
                                                break;
                                            case 'subscription_list':
                                                tableTitle = '<h3>MemberPress Subscriptions</h3>';
                                                break;
                                            case 'summary':
                                                tableTitle = '<h3>MemberPress Summary</h3>';
                                                break;
                                            default:
                                                tableTitle = '<h3>MemberPress Data</h3>';
                                                break;
                                        }
                                        
                                        tableHtml += tableTitle;
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
                                                cells.forEach(cell => {
                                                    tableHtml += `<td>${cell}</td>`;
                                                });
                                                tableHtml += '</tr>';
                                            }
                                        });
                                        
                                        tableHtml += '</tbody></table></div>';
                                        resultContent = tableHtml;
                                    } else {
                                        // Fall back to the old card-based approach
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
                                    }
                                }
                            } catch (e) {
                                console.error('MPAI: Error processing memberpress_info result:', e);
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
                    // Calculate the elapsed time since request start
                    const requestElapsed = performance.now() - requestStartTime;
                    
                    if (window.mpaiLogger) {
                        window.mpaiLogger.error('AJAX error executing tool ' + jsonData.tool, 'tool_usage', {
                            xhr: xhr,
                            status: status,
                            error: error,
                            tool: jsonData.tool,
                            parameters: jsonData.parameters,
                            requestTime: requestElapsed.toFixed(2) + 'ms',
                            toolId: toolId
                        });
                        window.mpaiLogger.endTimer('tool_' + toolId);
                    } else {
                        console.error('MPAI: AJAX error executing tool', {
                            xhr, 
                            status, 
                            error, 
                            toolId,
                            tool: jsonData.tool,
                            requestTime: requestElapsed.toFixed(2) + 'ms'
                        });
                    }
                    
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
            const messageId = 'msg-' + Date.now() + '-' + Math.floor(Math.random() * 1000);
            
            const $message = $(`
                <div id="${messageId}" class="mpai-chat-message ${messageClass}">
                    <div class="mpai-message-actions">
                        <button class="mpai-message-action-btn mpai-export-message" title="Export this message" data-message-id="${messageId}">
                            <span class="dashicons dashicons-download"></span>
                        </button>
                    </div>
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

        /**
         * Function to toggle chat expansion
         */
        function toggleChatExpansion() {
            $chatContainer.toggleClass('mpai-chat-expanded');
            
            // Toggle icon from expand to collapse and vice versa
            const $icon = $chatExpand.find('.dashicons');
            if ($chatContainer.hasClass('mpai-chat-expanded')) {
                $icon.removeClass('dashicons-editor-expand').addClass('dashicons-editor-contract');
                $chatExpand.attr('title', 'Collapse');
            } else {
                $icon.removeClass('dashicons-editor-contract').addClass('dashicons-editor-expand');
                $chatExpand.attr('title', 'Expand');
            }
            
            // Save the expanded state in localStorage
            localStorage.setItem('mpaiChatExpanded', $chatContainer.hasClass('mpai-chat-expanded'));
            
            console.log('MPAI: Chat expansion toggled');
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
        
        // Toggle expansion when the expand button is clicked
        $chatExpand.on('click', function() {
            toggleChatExpansion();
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
        
        /**
         * Export a single message
         * 
         * @param {string} messageId - The ID of the message to export
         * @param {string} format - The export format (markdown or html)
         */
        function exportMessage(messageId, format = 'markdown') {
            const $message = $('#' + messageId);
            if (!$message.length) return;
            
            // Determine if user or assistant message
            const isUserMessage = $message.hasClass('mpai-chat-message-user');
            const role = isUserMessage ? 'User' : 'Assistant';
            
            // Get message content
            const content = $message.find('.mpai-chat-message-content').clone();
            
            // Remove any interactive elements from the clone
            content.find('.mpai-command-toolbar, .mpai-tool-call, .mpai-loading-dots').remove();
            
            let fileContent = '';
            let fileExt = '';
            
            if (format === 'html') {
                // For HTML export, preserve the HTML structure
                // Get the HTML content including formatting
                let htmlContent = content.html();
                
                // Create a styled HTML document
                fileContent = createStyledHTML(`<h3>${role}</h3><div class="message-content">${htmlContent}</div>`);
                fileExt = 'html';
            } else {
                // For Markdown export
                
                // Clone the content to work with
                const markdownContent = content.clone();
                
                // Process the content for markdown
                
                // Replace tables with markdown tables
                markdownContent.find('table').each(function() {
                    const $table = $(this);
                    let mdTable = '';
                    
                    // Process header row
                    const $headerRow = $table.find('thead tr');
                    if ($headerRow.length) {
                        const headers = [];
                        $headerRow.find('th').each(function() {
                            headers.push($(this).text().trim());
                        });
                        
                        mdTable += '| ' + headers.join(' | ') + ' |\n';
                        mdTable += '| ' + headers.map(() => '---').join(' | ') + ' |\n';
                    }
                    
                    // Process data rows
                    $table.find('tbody tr').each(function() {
                        const cells = [];
                        $(this).find('td').each(function() {
                            cells.push($(this).text().trim());
                        });
                        
                        mdTable += '| ' + cells.join(' | ') + ' |\n';
                    });
                    
                    // Replace the table with markdown
                    $table.replaceWith('<div class="md-table">' + mdTable + '</div>');
                });
                
                // Replace code blocks
                markdownContent.find('pre').each(function() {
                    const code = $(this).text().trim();
                    $(this).replaceWith('\n```\n' + code + '\n```\n');
                });
                
                // Replace inline code elements
                markdownContent.find('code').each(function() {
                    if ($(this).parent().is('pre')) return; // Skip if in a pre block
                    const code = $(this).text().trim();
                    $(this).replaceWith('`' + code + '`');
                });
                
                // Handle numbered lists
                markdownContent.find('ol').each(function() {
                    const $list = $(this);
                    let mdList = '\n';
                    
                    $list.find('li').each(function(index) {
                        mdList += (index + 1) + '. ' + $(this).text().trim() + '\n';
                    });
                    
                    $list.replaceWith(mdList);
                });
                
                // Handle bullet lists
                markdownContent.find('ul').each(function() {
                    const $list = $(this);
                    let mdList = '\n';
                    
                    $list.find('li').each(function() {
                        mdList += '- ' + $(this).text().trim() + '\n';
                    });
                    
                    $list.replaceWith(mdList);
                });
                
                // Process HTML directly to handle nested lists and details
                
                // First identify patterns that look like nested lists and add special markers
                markdownContent.find('.mpai-result-table h3').after('<div class="markdown-h3-marker"></div>');
                
                // Manually look for patterns like "1. Item - Detail" and format them
                let html = markdownContent.html();
                // This will be used to detect and format numbered lists with nested details
                html = html.replace(/(\d+\.\s+[^<\n-]+)(\s*-\s+[^<\n]+)/g, '$1<br>    - $2');
                markdownContent.html(html);
                
                // Now do the final text processing
                let textContent = markdownContent.text().trim();
                
                // Check for list patterns and format them properly
                const listLines = textContent.split(/\n/);
                const formattedLines = [];
                
                listLines.forEach(line => {
                    // Is this a numbered list item?
                    if (/^\d+\.\s/.test(line)) {
                        // Add a blank line before a new list item (unless it's the first line)
                        if (formattedLines.length > 0 && !/^\d+\.\s/.test(formattedLines[formattedLines.length - 1])) {
                            formattedLines.push('');
                        }
                        formattedLines.push(line);
                    } 
                    // Is this a bullet list item?
                    else if (/^-\s/.test(line)) {
                        formattedLines.push(line);
                    } 
                    // Is this a special table header?
                    else if (/^[A-Z][\w\s]+:$/.test(line)) {
                        if (formattedLines.length > 0) formattedLines.push('');
                        formattedLines.push('**' + line + '**');
                    }
                    // Regular text
                    else {
                        formattedLines.push(line);
                    }
                });
                
                // Join the lines back together
                textContent = formattedLines.join('\n');
                
                // Final cleanup - do NOT replace newlines with spaces
                textContent = textContent
                    .replace(/([^\n])\s{2,}([^\n])/g, '$1 $2')  // Replace multiple spaces with a single space (but not newlines)
                    .replace(/(\d+\.\s+[^-\n]+)(\s+-\s+)/g, '$1\n    $2') // Format list with bullet points as details
                    .replace(/\n{3,}/g, '\n\n'); // Replace multiple newlines with double newlines
                
                // Handle any JSON content that might be present
                if (textContent.includes('json{')) {
                    textContent = textContent.replace(/json\{/g, '```json\n{');
                    textContent = textContent.replace(/\}(?=\s|$)/, '}\n```');
                }
                
                // Format as markdown with proper spacing
                fileContent = `## ${role}\n\n${textContent}\n`;
                fileExt = 'md';
            }
            
            // Generate filename based on date and time
            const date = new Date();
            const formattedDate = `${date.getFullYear()}-${(date.getMonth()+1).toString().padStart(2, '0')}-${date.getDate().toString().padStart(2, '0')}`;
            const formattedTime = `${date.getHours().toString().padStart(2, '0')}-${date.getMinutes().toString().padStart(2, '0')}`;
            const filename = `memberpress-ai-message-${formattedDate}-${formattedTime}.${fileExt}`;
            
            // Create and trigger the download
            if (format === 'html') {
                downloadTextFile(fileContent, filename, 'text/html');
            } else {
                downloadTextFile(fileContent, filename, 'text/markdown');
            }
        }
        
        /**
         * Export the entire conversation
         * 
         * @param {string} format - The export format (markdown or html)
         */
        function exportConversation(format = 'markdown') {
            // Collect all messages
            const messages = [];
            const htmlMessages = [];
            
            $('.mpai-chat-message').each(function() {
                const isUserMessage = $(this).hasClass('mpai-chat-message-user');
                const role = isUserMessage ? 'User' : 'Assistant';
                
                // Get message content
                const content = $(this).find('.mpai-chat-message-content').clone();
                
                // Remove any interactive elements from the clone
                content.find('.mpai-command-toolbar, .mpai-tool-call, .mpai-loading-dots').remove();
                
                if (format === 'html') {
                    // For HTML export, preserve the HTML structure
                    let htmlContent = content.html();
                    htmlMessages.push(`<div class="message ${isUserMessage ? 'user-message' : 'assistant-message'}">
                        <h3>${role}</h3>
                        <div class="message-content">${htmlContent}</div>
                    </div>`);
                } else {
                    // For Markdown export - use the same processing as single message export
                    
                    // Clone the content to work with
                    const markdownContent = content.clone();
                    
                    // Process the content for markdown
                    
                    // Replace tables with markdown tables
                    markdownContent.find('table').each(function() {
                        const $table = $(this);
                        let mdTable = '';
                        
                        // Process header row
                        const $headerRow = $table.find('thead tr');
                        if ($headerRow.length) {
                            const headers = [];
                            $headerRow.find('th').each(function() {
                                headers.push($(this).text().trim());
                            });
                            
                            mdTable += '| ' + headers.join(' | ') + ' |\n';
                            mdTable += '| ' + headers.map(() => '---').join(' | ') + ' |\n';
                        }
                        
                        // Process data rows
                        $table.find('tbody tr').each(function() {
                            const cells = [];
                            $(this).find('td').each(function() {
                                cells.push($(this).text().trim());
                            });
                            
                            mdTable += '| ' + cells.join(' | ') + ' |\n';
                        });
                        
                        // Replace the table with markdown
                        $table.replaceWith('<div class="md-table">' + mdTable + '</div>');
                    });
                    
                    // Replace code blocks
                    markdownContent.find('pre').each(function() {
                        const code = $(this).text().trim();
                        $(this).replaceWith('\n```\n' + code + '\n```\n');
                    });
                    
                    // Replace inline code elements
                    markdownContent.find('code').each(function() {
                        if ($(this).parent().is('pre')) return; // Skip if in a pre block
                        const code = $(this).text().trim();
                        $(this).replaceWith('`' + code + '`');
                    });
                    
                    // Handle numbered lists
                    markdownContent.find('ol').each(function() {
                        const $list = $(this);
                        let mdList = '\n';
                        
                        $list.find('li').each(function(index) {
                            mdList += (index + 1) + '. ' + $(this).text().trim() + '\n';
                        });
                        
                        $list.replaceWith(mdList);
                    });
                    
                    // Handle bullet lists
                    markdownContent.find('ul').each(function() {
                        const $list = $(this);
                        let mdList = '\n';
                        
                        $list.find('li').each(function() {
                            mdList += '- ' + $(this).text().trim() + '\n';
                        });
                        
                        $list.replaceWith(mdList);
                    });
                    
                    // Process HTML directly to handle nested lists and details
                    
                    // First identify patterns that look like nested lists and add special markers
                    markdownContent.find('.mpai-result-table h3').after('<div class="markdown-h3-marker"></div>');
                    
                    // Manually look for patterns like "1. Item - Detail" and format them
                    let html = markdownContent.html();
                    // This will be used to detect and format numbered lists with nested details
                    html = html.replace(/(\d+\.\s+[^<\n-]+)(\s*-\s+[^<\n]+)/g, '$1<br>    - $2');
                    markdownContent.html(html);
                    
                    // Now do the final text processing
                    let textContent = markdownContent.text().trim();
                    
                    // Check for list patterns and format them properly
                    const listLines = textContent.split(/\n/);
                    const formattedLines = [];
                    
                    listLines.forEach(line => {
                        // Is this a numbered list item?
                        if (/^\d+\.\s/.test(line)) {
                            // Add a blank line before a new list item (unless it's the first line)
                            if (formattedLines.length > 0 && !/^\d+\.\s/.test(formattedLines[formattedLines.length - 1])) {
                                formattedLines.push('');
                            }
                            formattedLines.push(line);
                        } 
                        // Is this a bullet list item?
                        else if (/^-\s/.test(line)) {
                            formattedLines.push(line);
                        } 
                        // Is this a special table header?
                        else if (/^[A-Z][\w\s]+:$/.test(line)) {
                            if (formattedLines.length > 0) formattedLines.push('');
                            formattedLines.push('**' + line + '**');
                        }
                        // Regular text
                        else {
                            formattedLines.push(line);
                        }
                    });
                    
                    // Join the lines back together
                    textContent = formattedLines.join('\n');
                    
                    // Final cleanup - do NOT replace newlines with spaces
                    textContent = textContent
                        .replace(/([^\n])\s{2,}([^\n])/g, '$1 $2')  // Replace multiple spaces with a single space (but not newlines)
                        .replace(/(\d+\.\s+[^-\n]+)(\s+-\s+)/g, '$1\n    $2') // Format list with bullet points as details
                        .replace(/\n{3,}/g, '\n\n'); // Replace multiple newlines with double newlines
                    
                    // Handle any JSON content that might be present
                    if (textContent.includes('json{')) {
                        textContent = textContent.replace(/json\{/g, '```json\n{');
                        textContent = textContent.replace(/\}(?=\s|$)/, '}\n```');
                    }
                    
                    messages.push(`## ${role}\n\n${textContent}\n`);
                }
            });
            
            let fileContent = '';
            let fileExt = '';
            
            if (format === 'html') {
                // Create a styled HTML document with all messages
                fileContent = createStyledHTML(`<div class="chat-container">${htmlMessages.join('\n<hr>\n')}</div>`);
                fileExt = 'html';
            } else {
                // Combine all messages with markdown formatting
                fileContent = messages.join('\n---\n\n');
                fileExt = 'md';
            }
            
            // Generate filename based on date
            const date = new Date();
            const formattedDate = `${date.getFullYear()}-${(date.getMonth()+1).toString().padStart(2, '0')}-${date.getDate().toString().padStart(2, '0')}`;
            const formattedTime = `${date.getHours().toString().padStart(2, '0')}-${date.getMinutes().toString().padStart(2, '0')}`;
            const filename = `memberpress-ai-conversation-${formattedDate}-${formattedTime}.${fileExt}`;
            
            // Create and trigger the download
            if (format === 'html') {
                downloadTextFile(fileContent, filename, 'text/html');
            } else {
                downloadTextFile(fileContent, filename, 'text/markdown');
            }
        }
        
        /**
         * Creates a styled HTML document with the provided content
         * 
         * @param {string} content - The HTML content to include in the document
         * @returns {string} - The complete HTML document as a string
         */
        function createStyledHTML(content) {
            return `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MemberPress AI Chat</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            line-height: 1.5;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .chat-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .message {
            padding: 15px;
            border-radius: 8px;
            position: relative;
        }
        .user-message {
            background-color: #e7f3ff;
            align-self: flex-end;
        }
        .assistant-message {
            background-color: #f7f7f7;
            align-self: flex-start;
        }
        h3 {
            margin-top: 0;
            color: #135e96;
            font-size: 16px;
            font-weight: 600;
            border-bottom: 1px solid #ddd;
            padding-bottom: 8px;
        }
        .message-content {
            font-size: 14px;
        }
        hr {
            border: none;
            border-top: 1px solid #eee;
            margin: 20px 0;
        }
        code {
            background-color: rgba(0, 0, 0, 0.05);
            padding: 2px 4px;
            border-radius: 3px;
            font-family: monospace;
            font-size: 90%;
        }
        pre {
            background-color: #f6f8fa;
            border-radius: 3px;
            padding: 10px;
            overflow-x: auto;
            margin: 10px 0;
            font-family: monospace;
            font-size: 12px;
            border: 1px solid #eee;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin: 10px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #135e96;
            margin-bottom: 5px;
        }
        .header p {
            color: #666;
            margin-top: 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>MemberPress AI Assistant</h1>
        <p>Chat transcript exported on ${new Date().toLocaleString()}</p>
    </div>
    ${content}
</body>
</html>`;
        }

        /**
         * Helper function to download text as a file
         * 
         * @param {string} content - The text content to download
         * @param {string} filename - The name of the file to download
         * @param {string} mimeType - The MIME type of the file
         */
        function downloadTextFile(content, filename, mimeType = 'text/plain') {
            // Create a blob with the content and appropriate MIME type
            const blob = new Blob([content], { type: mimeType });
            
            // Create a URL for the blob
            const url = URL.createObjectURL(blob);
            
            // Create a temporary anchor element
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            
            // Append to the document and trigger the download
            document.body.appendChild(a);
            a.click();
            
            // Clean up
            setTimeout(function() {
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            }, 100);
        }
        
        // Export menu for a single message
        $(document).on('click', '.mpai-export-message', function(e) {
            e.preventDefault();
            const messageId = $(this).data('message-id');
            
            // Show a simple dialog with format options
            const $message = $('#' + messageId);
            const $existingMenu = $('.mpai-export-format-menu');
            
            // Remove any existing menus first
            if ($existingMenu.length) {
                $existingMenu.remove();
            }
            
            // Create format selection menu
            const $menu = $(`
                <div class="mpai-export-format-menu">
                    <div class="mpai-export-format-title">Export Format</div>
                    <div class="mpai-export-format-options">
                        <button class="mpai-export-format-btn" data-format="markdown" data-message-id="${messageId}">Markdown</button>
                        <button class="mpai-export-format-btn" data-format="html" data-message-id="${messageId}">HTML</button>
                    </div>
                </div>
            `);
            
            // Get position relative to the viewport
            const buttonRect = this.getBoundingClientRect();
            
            // Position below the button
            $menu.css({
                top: buttonRect.bottom + 5,
                left: buttonRect.left
            });
            
            // Add to document and show
            $('body').append($menu);
            
            // Handle clicks outside menu to close it
            $(document).one('click', function(e) {
                if (!$(e.target).closest('.mpai-export-format-menu, .mpai-export-message').length) {
                    $menu.remove();
                }
            });
        });
        
        // Handle format selection for single message export
        $(document).on('click', '.mpai-export-format-btn', function() {
            const format = $(this).data('format');
            const messageId = $(this).data('message-id');
            
            // Remove the menu
            $('.mpai-export-format-menu').remove();
            
            // Export with selected format
            exportMessage(messageId, format);
        });
        
        // Export menu for the entire conversation
        $exportChat.on('click', function(e) {
            e.preventDefault();
            
            // Remove any existing menus first
            const $existingMenu = $('.mpai-export-format-menu');
            if ($existingMenu.length) {
                $existingMenu.remove();
            }
            
            // Create format selection menu
            const $menu = $(`
                <div class="mpai-export-format-menu">
                    <div class="mpai-export-format-title">Export Format</div>
                    <div class="mpai-export-format-options">
                        <button class="mpai-export-all-format-btn" data-format="markdown">Markdown</button>
                        <button class="mpai-export-all-format-btn" data-format="html">HTML</button>
                    </div>
                </div>
            `);
            
            // Get position relative to the viewport
            const buttonRect = this.getBoundingClientRect();
            
            // Position directly above the button
            $menu.css({
                bottom: (window.innerHeight - buttonRect.top) + 10,
                left: buttonRect.left
            });
            
            // Add to document
            $('body').append($menu);
            
            // Handle clicks outside menu to close it
            $(document).one('click', function(e) {
                if (!$(e.target).closest('.mpai-export-format-menu, #mpai-export-chat').length) {
                    $menu.remove();
                }
            });
        });
        
        // Handle format selection for entire conversation export
        $(document).on('click', '.mpai-export-all-format-btn', function() {
            const format = $(this).data('format');
            
            // Remove the menu
            $('.mpai-export-format-menu').remove();
            
            // Export with selected format
            exportConversation(format);
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
            
            // Check if it was expanded previously
            if (localStorage.getItem('mpaiChatExpanded') === 'true') {
                $chatContainer.addClass('mpai-chat-expanded');
                $chatExpand.find('.dashicons')
                    .removeClass('dashicons-editor-expand')
                    .addClass('dashicons-editor-contract');
                $chatExpand.attr('title', 'Collapse');
            }
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

    /**
     * Test utility function for tool call detection
     * This function can be called from the browser console to test tool call detection with various formats
     * 
     * @param {string} testResponse - The test response to process
     * @return {object} Test results
     */
    window.testToolCallDetection = function(testResponse) {
        // Create a clean test environment
        console.group('MPAI Tool Call Detection Test');
        
        console.log('Running tool call detection test on provided response');
        console.log('Response length:', testResponse.length);
        
        if (window.mpaiLogger) {
            window.mpaiLogger.debug('Starting tool call detection test', 'tool_usage');
        }
        
        // Apply all regex patterns to the test response
        const jsonBlockRegex = /```(?:json)?\s*\n({[\s\S]*?})\s*\n```/g;
        const jsonObjectBlockRegex = /```(?:json-object)?\s*\n({[\s\S]*?})\s*\n```/g;
        const directJsonRegex = /\{[\s\S]*?["']tool["'][\s\S]*?["']parameters["'][\s\S]*?\}/g;
        const anyCodeBlockRegex = /```[\w-]*\s*\n(\{[\s\S]*?["']tool["'][\s\S]*?["']parameters["'][\s\S]*?\})\s*\n```/g;
        const altFormatRegex = /```\s*(\{[\s\S]*?["']tool["'][\s\S]*?["']parameters["'][\s\S]*?\})\s*```/g;
        const indentedJsonBlockRegex = /```(?:json)?\s*\n\s*({[\s\S]*?})\s*\n```/g;
        const multilineToolRegex = /\{\s*[\r\n\s]*"tool"[\s\S]*?"parameters"[\s\S]*?\}/g;
        const singleQuoteJsonRegex = /```(?:json)?\s*\n({'[\s\S]*?'})\s*\n```/g;
        const backtickFenceVariantRegex = /``\s*\n({[\s\S]*?})\s*\n``/g;
        
        // Object to store all matches by pattern
        const allMatches = {
            jsonBlockRegex: [],
            jsonObjectBlockRegex: [],
            directJsonRegex: [],
            anyCodeBlockRegex: [],
            altFormatRegex: [],
            indentedJsonBlockRegex: [],
            multilineToolRegex: [],
            singleQuoteJsonRegex: [],
            backtickFenceVariantRegex: []
        };
        
        // Function to collect matches from each pattern
        function collectMatches(pattern, patternName) {
            let match;
            let patternCopy = new RegExp(pattern.source, pattern.flags); // Create a copy of the regex to avoid lastIndex issues
            while ((match = patternCopy.exec(testResponse)) !== null) {
                try {
                    const matchText = patternName === 'directJsonRegex' ? match[0] : match[1];
                    if (!matchText || matchText.trim() === '') continue;
                    
                    try {
                        const jsonData = JSON.parse(matchText);
                        if (jsonData.tool && jsonData.parameters) {
                            allMatches[patternName].push({
                                fullMatch: match[0],
                                jsonText: matchText,
                                jsonData: jsonData
                            });
                        }
                    } catch (parseError) {
                        console.log(`MPAI Test: Parse error for ${patternName} match:`, parseError);
                        allMatches[patternName].push({
                            fullMatch: match[0],
                            jsonText: matchText,
                            parseError: parseError.message
                        });
                    }
                } catch (e) {
                    console.error(`MPAI Test: Error processing ${patternName} match:`, e);
                }
            }
        }
        
        // Collect matches for each pattern
        collectMatches(jsonBlockRegex, 'jsonBlockRegex');
        collectMatches(jsonObjectBlockRegex, 'jsonObjectBlockRegex');
        collectMatches(anyCodeBlockRegex, 'anyCodeBlockRegex');
        collectMatches(altFormatRegex, 'altFormatRegex');
        collectMatches(indentedJsonBlockRegex, 'indentedJsonBlockRegex');
        collectMatches(multilineToolRegex, 'multilineToolRegex');
        collectMatches(singleQuoteJsonRegex, 'singleQuoteJsonRegex');
        collectMatches(backtickFenceVariantRegex, 'backtickFenceVariantRegex');
        
        // Handle directJsonRegex specially (match[0] instead of match[1])
        collectMatches(directJsonRegex, 'directJsonRegex');
        
        // Consolidate all valid tool calls (deduplicated)
        const validToolCalls = [];
        const toolCallMap = {}; // For deduplication
        
        Object.keys(allMatches).forEach(patternName => {
            allMatches[patternName].forEach(match => {
                if (match.jsonData && match.jsonData.tool && match.jsonData.parameters) {
                    const toolKey = match.jsonData.tool + JSON.stringify(match.jsonData.parameters);
                    if (!toolCallMap[toolKey]) {
                        toolCallMap[toolKey] = true;
                        validToolCalls.push({
                            tool: match.jsonData.tool,
                            parameters: match.jsonData.parameters,
                            patternUsed: patternName
                        });
                    }
                }
            });
        });
        
        // Generate pattern usage statistics
        const patternStats = {};
        validToolCalls.forEach(toolCall => {
            if (!patternStats[toolCall.patternUsed]) {
                patternStats[toolCall.patternUsed] = 0;
            }
            patternStats[toolCall.patternUsed]++;
        });
        
        // Output the test results
        console.log('MPAI Test: Pattern matching results:', allMatches);
        console.log('MPAI Test: Valid tool calls found:', validToolCalls);
        console.log('MPAI Test: Pattern usage statistics:', patternStats);
        console.log('MPAI Test: Total valid tool calls:', validToolCalls.length);
        
        if (validToolCalls.length === 0) {
            // Additional diagnostics for debugging
            console.log('MPAI Test: No valid tool calls found. Response analysis:');
            console.log('- Contains `tool` keyword:', testResponse.includes('tool'));
            console.log('- Contains `parameters` keyword:', testResponse.includes('parameters'));
            console.log('- Contains code blocks:', testResponse.includes('```'));
            
            // Extract code blocks for inspection
            const codeBlocks = testResponse.match(/```[\s\S]*?```/g);
            if (codeBlocks && codeBlocks.length > 0) {
                console.log('MPAI Test: Code blocks in response:', codeBlocks);
            }
        }
        
        console.groupEnd();
        
        // Return the test results
        return {
            patternMatches: allMatches,
            validToolCalls: validToolCalls,
            patternStats: patternStats,
            responseLength: testResponse.length
        };
    };

})(jQuery);