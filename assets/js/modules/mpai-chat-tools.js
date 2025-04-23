/**
 * MemberPress AI Assistant - Chat Tools Module
 * 
 * Completely rewritten module for tool execution with strict parameter validation
 */

var MPAI_Tools = (function($) {
    'use strict';
    
    // Private variables
    var elements = {};
    var messagesModule = null;
    var formattersModule = null;
    
    /**
     * Initialize the module
     * 
     * @param {Object} domElements - DOM elements
     * @param {Object} messages - The messages module
     * @param {Object} formatters - The formatters module
     */
    function init(domElements, messages, formatters) {
        elements = domElements;
        messagesModule = messages;
        formattersModule = formatters;
        
        // Initialize the tool call detector
        if (window.MPAI_ToolCallDetector && typeof window.MPAI_ToolCallDetector.init === 'function') {
            window.MPAI_ToolCallDetector.init({
                logger: window.mpaiLogger
            });
        }
        
        if (window.mpaiLogger) {
            window.mpaiLogger.info('Tools module initialized', 'tool_usage');
        }
    }
    
    /**
     * Process and detect tool calls in a response
     * 
     * @param {string} response - The response to check for tool calls
     * @return {boolean} Whether tool calls were detected and processing
     */
    function processToolCalls(response) {
        // CRITICAL FIX: Direct detection for membership creation
        if (response &&
            response.includes('memberpress_info') &&
            response.includes('"type":"create"')) {
            
            console.log('MPAI Tools - CRITICAL FIX - Detected potential membership creation in response');
            
            // Multiple patterns to match different JSON formats
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
                    console.log('MPAI Tools - CRITICAL FIX - Found direct membership creation format', matches[0]);
                    
                    try {
                        // Clean the JSON string - handle single quotes, etc.
                        let jsonStr = matches[0].replace(/'/g, '"');
                        
                        // Parse the JSON
                        const jsonData = JSON.parse(jsonStr);
                        console.log('MPAI Tools - CRITICAL FIX - Parsed JSON data:', jsonData);
                        
                        if ((jsonData.tool === 'memberpress_info' || jsonData.name === 'memberpress_info') &&
                            jsonData.parameters &&
                            jsonData.parameters.type === 'create') {
                            
                            // Extract and enhance parameters
                            const params = jsonData.parameters;
                            
                            // Extract name from the response text if not in parameters
                            if (!params.name && response && response.includes('named')) {
                                const nameMatch = response.match(/named\s+['"]?([^'"]+)['"]?/i);
                                if (nameMatch && nameMatch[1]) {
                                    params.name = nameMatch[1].trim();
                                    console.log('MPAI Tools - CRITICAL FIX - Extracted name from text:', params.name);
                                }
                            }
                            
                            // Extract price from the response text if not in parameters
                            if (!params.price && response && response.includes('$')) {
                                const priceMatch = response.match(/\$\s*(\d+(?:\.\d+)?)/);
                                if (priceMatch && priceMatch[1]) {
                                    params.price = parseFloat(priceMatch[1]);
                                    console.log('MPAI Tools - CRITICAL FIX - Extracted price from text:', params.price);
                                }
                            } else if (typeof params.price === 'string' && !isNaN(parseFloat(params.price))) {
                                // Ensure price is a number
                                params.price = parseFloat(params.price);
                                console.log('MPAI Tools - CRITICAL FIX - Converted price from string to number:', params.price);
                            }
                            
                            // Extract period_type from the response text if not in parameters
                            if (!params.period_type && response) {
                                if (response.includes('monthly')) {
                                    params.period_type = 'month';
                                    console.log('MPAI Tools - CRITICAL FIX - Set period_type to month based on text');
                                } else if (response.includes('yearly')) {
                                    params.period_type = 'year';
                                    console.log('MPAI Tools - CRITICAL FIX - Set period_type to year based on text');
                                } else if (response.includes('lifetime')) {
                                    params.period_type = 'lifetime';
                                    console.log('MPAI Tools - CRITICAL FIX - Set period_type to lifetime based on text');
                                }
                            }
                            
                            // Log the final parameters
                            console.log('MPAI Tools - CRITICAL FIX - Final parameters:', params);
                            
                            // Create a tool call object
                            const toolCalls = [{
                                name: 'memberpress_info',
                                parameters: params
                            }];
                            
                            // Execute the tool call directly
                            executeToolCalls(toolCalls, response);
                            return true;
                        }
                    } catch (e) {
                        console.error('MPAI Tools - CRITICAL FIX - Error parsing direct format JSON', e);
                    }
                }
            }
            
            // Check for JSON code blocks
            if (response.includes('```json')) {
                console.log('MPAI Tools - CRITICAL FIX - Checking for JSON code blocks');
                
                const codeBlockPattern = /```json\s*([\s\S]*?)\s*```/g;
                let codeBlockMatch;
                
                while ((codeBlockMatch = codeBlockPattern.exec(response)) !== null) {
                    if (codeBlockMatch[1] &&
                        codeBlockMatch[1].includes('memberpress_info') &&
                        codeBlockMatch[1].includes('"type":"create"')) {
                        
                        try {
                            // Clean up the JSON string
                            const jsonStr = codeBlockMatch[1].trim();
                            
                            console.log('MPAI Tools - CRITICAL FIX - Found JSON code block with potential tool call:', jsonStr.substring(0, 50) + '...');
                            
                            // Parse the JSON
                            const jsonData = JSON.parse(jsonStr);
                            console.log('MPAI Tools - CRITICAL FIX - Parsed JSON from code block:', jsonData);
                            
                            if ((jsonData.tool === 'memberpress_info' || jsonData.name === 'memberpress_info') &&
                                jsonData.parameters &&
                                jsonData.parameters.type === 'create') {
                                
                                // Extract and enhance parameters
                                const params = jsonData.parameters;
                                
                                // Extract name from the response text if not in parameters
                                if (!params.name && response && response.includes('named')) {
                                    const nameMatch = response.match(/named\s+['"]?([^'"]+)['"]?/i);
                                    if (nameMatch && nameMatch[1]) {
                                        params.name = nameMatch[1].trim();
                                        console.log('MPAI Tools - CRITICAL FIX - Extracted name from text:', params.name);
                                    }
                                }
                                
                                // Extract price from the response text if not in parameters
                                if (!params.price && response && response.includes('$')) {
                                    const priceMatch = response.match(/\$\s*(\d+(?:\.\d+)?)/);
                                    if (priceMatch && priceMatch[1]) {
                                        params.price = parseFloat(priceMatch[1]);
                                        console.log('MPAI Tools - CRITICAL FIX - Extracted price from text:', params.price);
                                    }
                                } else if (typeof params.price === 'string' && !isNaN(parseFloat(params.price))) {
                                    // Ensure price is a number
                                    params.price = parseFloat(params.price);
                                    console.log('MPAI Tools - CRITICAL FIX - Converted price from string to number:', params.price);
                                }
                                
                                // Extract period_type from the response text if not in parameters
                                if (!params.period_type && response) {
                                    if (response.includes('monthly')) {
                                        params.period_type = 'month';
                                        console.log('MPAI Tools - CRITICAL FIX - Set period_type to month based on text');
                                    } else if (response.includes('yearly')) {
                                        params.period_type = 'year';
                                        console.log('MPAI Tools - CRITICAL FIX - Set period_type to year based on text');
                                    } else if (response.includes('lifetime')) {
                                        params.period_type = 'lifetime';
                                        console.log('MPAI Tools - CRITICAL FIX - Set period_type to lifetime based on text');
                                    }
                                }
                                
                                // Log the final parameters
                                console.log('MPAI Tools - CRITICAL FIX - Final parameters:', params);
                                
                                // Create a tool call object
                                const toolCalls = [{
                                    name: 'memberpress_info',
                                    parameters: params
                                }];
                                
                                // Execute the tool call directly
                                executeToolCalls(toolCalls, response);
                                return true;
                            }
                        } catch (e) {
                            console.error('MPAI Tools - CRITICAL FIX - Error parsing JSON code block', e);
                        }
                    }
                }
            }
        }
        
        // Check if tool call detector is available yet
        if (!window.MPAI_ToolCallDetector || typeof window.MPAI_ToolCallDetector.processResponse !== 'function') {
            // Wait for detector to load and try again
            console.log('MPAI Tools - Tool call detector not available yet, retrying in 100ms');
            
            setTimeout(function() {
                processToolCalls(response);
            }, 100);
            
            return false;
        }
        
        // Use the tool call detector
        return window.MPAI_ToolCallDetector.processResponse(response);
    }
    
    /**
     * Execute tool calls
     * 
     * @param {Array} toolCalls - Array of tool call objects
     * @param {string} originalResponse - The original response containing the tool calls
     */
    function executeToolCalls(toolCalls, originalResponse) {
        if (!toolCalls || !Array.isArray(toolCalls) || toolCalls.length === 0) {
            console.error('MPAI Tools - No tool calls to execute');
            return;
        }
        
        let updatedResponse = originalResponse;
        
        // Process each tool call
        toolCalls.forEach(toolCall => {
            // Handle membership creation as a special case
            if (toolCall.name === 'memberpress_info' && 
                toolCall.parameters && 
                toolCall.parameters.type === 'create') {
                
                executeMembershipCreation(toolCall, updatedResponse);
            } else {
                // Standard tool execution
                executeStandardTool(toolCall, updatedResponse);
            }
        });
    }
    
    /**
     * Execute a membership creation tool call with validation
     * 
     * @param {Object} toolCall - The tool call object
     * @param {string} response - The response containing the tool call
     */
    function executeMembershipCreation(toolCall, response) {
        console.log('MPAI Tools - Executing membership creation:', toolCall);
        
        // DIRECT FIX: Ensure parameters are properly formatted
        if (toolCall.parameters) {
            const params = toolCall.parameters;
            
            // Extract name from the response text if not in parameters
            if (!params.name && response && response.includes('named')) {
                const nameMatch = response.match(/named\s+['"]?([^'"]+)['"]?/i);
                if (nameMatch && nameMatch[1]) {
                    params.name = nameMatch[1].trim();
                    console.log('MPAI Tools - DIRECT FIX - Extracted name from text:', params.name);
                }
            }
            
            // Extract price from the response text if not in parameters
            if (!params.price && response && response.includes('$')) {
                const priceMatch = response.match(/\$\s*(\d+(?:\.\d+)?)/);
                if (priceMatch && priceMatch[1]) {
                    params.price = parseFloat(priceMatch[1]);
                    console.log('MPAI Tools - DIRECT FIX - Extracted price from text:', params.price);
                }
            } else if (typeof params.price === 'string' && !isNaN(parseFloat(params.price))) {
                // Ensure price is a number
                params.price = parseFloat(params.price);
                console.log('MPAI Tools - DIRECT FIX - Converted price from string to number:', params.price);
            }
            
            // Extract period_type from the response text if not in parameters
            if (!params.period_type && response) {
                if (response.includes('monthly')) {
                    params.period_type = 'month';
                    console.log('MPAI Tools - DIRECT FIX - Set period_type to month based on text');
                } else if (response.includes('yearly')) {
                    params.period_type = 'year';
                    console.log('MPAI Tools - DIRECT FIX - Set period_type to year based on text');
                } else if (response.includes('lifetime')) {
                    params.period_type = 'lifetime';
                    console.log('MPAI Tools - DIRECT FIX - Set period_type to lifetime based on text');
                } else {
                    // Default to month if not specified
                    params.period_type = 'month';
                    console.log('MPAI Tools - DIRECT FIX - Added default period_type: month');
                }
            } else if (!['month', 'year', 'lifetime'].includes(params.period_type)) {
                console.log('MPAI Tools - DIRECT FIX - Invalid period_type:', params.period_type);
                params.period_type = 'month'; // Default to month
            }
            
            // Ensure type is set to 'create'
            if (!params.type) {
                params.type = 'create';
                console.log('MPAI Tools - DIRECT FIX - Added missing type parameter');
            }
            
            // Ensure name is set with a default if all else fails
            if (!params.name) {
                params.name = 'Gold Membership';
                console.log('MPAI Tools - DIRECT FIX - Added default name: Gold Membership');
            }
            
            // Log the final parameters
            console.log('MPAI Tools - DIRECT FIX - Final parameters:', params);
        }
        
        // Validate parameters
        if (!window.MPAI_ParameterValidator) {
            console.error('MPAI Tools - Parameter validator not available');
            displayError('Parameter validation module not available', toolCall.name);
            return;
        }
        
        console.log('MPAI Tools - CRITICAL FIX - Parameters before validation:', JSON.stringify(toolCall.parameters));
        const validationResult = window.MPAI_ParameterValidator.validateMembershipParameters(toolCall.parameters);
        console.log('MPAI Tools - CRITICAL FIX - Validation result:', validationResult);
        
        // If validation failed, display error and abort
        if (!validationResult.isValid) {
            console.error('MPAI Tools - Membership parameter validation failed:', validationResult.errors);
            displayError(
                'Membership parameter validation failed: ' + validationResult.errors.join('<br>'),
                toolCall.name
            );
            return;
        }
        
        // Replace the tool call in the response with a processing indicator
        const toolCallHTML = window.MPAI_ToolCallDetector.formatToolCall({
            name: toolCall.name,
            parameters: validationResult.parameters
        });
        
        // Find a DOM element for the tool call
        const $toolCall = $(toolCallHTML);
        const toolId = $toolCall.attr('id');
        
        // Append to messages if possible
        if (messagesModule && typeof messagesModule.addMessage === 'function') {
            // Append the tool call HTML to the last assistant message
            messagesModule.appendToLastAssistantMessage(toolCallHTML);
        }
        
        // Execute the tool with validated parameters
        $.ajax({
            url: mpai_chat_data.ajax_url,
            type: 'POST',
            data: {
                action: 'mpai_execute_tool',
                tool_request: JSON.stringify({
                    name: toolCall.name,
                    parameters: validationResult.parameters
                }),
                raw_request: response, // Include the raw response for parsing on the server
                nonce: mpai_chat_data.nonce
            },
            success: function(response) {
                console.log('MPAI Tools - Membership creation response:', response);
                
                const $toolCall = $('#' + toolId);
                const $status = $toolCall.find('.mpai-tool-call-status');
                const $result = $toolCall.find('.mpai-tool-call-result');
                
                if (response.success) {
                    // Update status to success
                    $status.removeClass('mpai-tool-call-processing').addClass('mpai-tool-call-success');
                    $status.html('Success');
                    
                    // Format the result
                    let resultContent = '';
                    
                    try {
                        const resultData = typeof response.data === 'string' ? JSON.parse(response.data) : response.data;
                        
                        if (resultData.success) {
                            // Successful membership creation
                            resultContent = `
                                <div class="mpai-membership-created">
                                    <p>Membership created successfully:</p>
                                    <ul>
                                        <li><strong>Name:</strong> ${resultData.name || validationResult.parameters.name}</li>
                                        <li><strong>Price:</strong> $${resultData.price || validationResult.parameters.price}</li>
                                        <li><strong>Period:</strong> ${validationResult.parameters.period_type}</li>
                                    </ul>
                                    <p class="mpai-membership-success">Membership ID: ${resultData.membership_id}</p>
                                </div>
                            `;
                        } else {
                            // Failed membership creation with error from server
                            resultContent = `
                                <div class="mpai-membership-error">
                                    <p>Failed to create membership:</p>
                                    <p class="mpai-error-message">${resultData.message || 'Unknown error'}</p>
                                </div>
                            `;
                        }
                    } catch (e) {
                        // Error parsing result
                        resultContent = `
                            <div class="mpai-membership-error">
                                <p>Error processing membership creation result:</p>
                                <p class="mpai-error-message">${e.message}</p>
                                <pre>${response.data}</pre>
                            </div>
                        `;
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
                if (window.MPAI_UIUtils && typeof window.MPAI_UIUtils.scrollToBottom === 'function') {
                    window.MPAI_UIUtils.scrollToBottom();
                }
            },
            error: function(xhr, status, error) {
                console.error('MPAI Tools - AJAX error:', error, xhr.responseText);
                
                const $toolCall = $('#' + toolId);
                const $status = $toolCall.find('.mpai-tool-call-status');
                const $result = $toolCall.find('.mpai-tool-call-result');
                
                // Update status to error
                $status.removeClass('mpai-tool-call-processing').addClass('mpai-tool-call-error');
                $status.html('Error');
                
                // Display the error
                $result.html(`<div class="mpai-tool-call-error-message">AJAX error: ${error}</div>`);
                
                // Scroll to bottom to show error
                if (window.MPAI_UIUtils && typeof window.MPAI_UIUtils.scrollToBottom === 'function') {
                    window.MPAI_UIUtils.scrollToBottom();
                }
            }
        });
    }
    
    /**
     * Execute a standard tool call
     * 
     * @param {Object} toolCall - The tool call object
     * @param {string} response - The response containing the tool call
     */
    function executeStandardTool(toolCall, response) {
        console.log('MPAI Tools - Executing standard tool:', toolCall);
        
        // Replace the tool call in the response with a processing indicator
        const toolCallHTML = window.MPAI_ToolCallDetector.formatToolCall(toolCall);
        
        // Find a DOM element for the tool call
        const $toolCall = $(toolCallHTML);
        const toolId = $toolCall.attr('id');
        
        // Append to messages if possible
        if (messagesModule && typeof messagesModule.addMessage === 'function') {
            // Append the tool call HTML to the last assistant message
            messagesModule.appendToLastAssistantMessage(toolCallHTML);
        }
        
        // Execute the tool
        $.ajax({
            url: mpai_chat_data.ajax_url,
            type: 'POST',
            data: {
                action: 'mpai_execute_tool',
                tool_request: JSON.stringify({
                    name: toolCall.name,
                    parameters: toolCall.parameters
                }),
                nonce: mpai_chat_data.nonce
            },
            success: function(response) {
                console.log('MPAI Tools - Tool execution response:', response);
                
                const $toolCall = $('#' + toolId);
                const $status = $toolCall.find('.mpai-tool-call-status');
                const $result = $toolCall.find('.mpai-tool-call-result');
                
                if (response.success) {
                    // Update status to success
                    $status.removeClass('mpai-tool-call-processing').addClass('mpai-tool-call-success');
                    $status.html('Success');
                    
                    // Format the result
                    let resultContent = formatToolResult(toolCall.name, response.data);
                    
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
                if (window.MPAI_UIUtils && typeof window.MPAI_UIUtils.scrollToBottom === 'function') {
                    window.MPAI_UIUtils.scrollToBottom();
                }
            },
            error: function(xhr, status, error) {
                console.error('MPAI Tools - AJAX error:', error, xhr.responseText);
                
                const $toolCall = $('#' + toolId);
                const $status = $toolCall.find('.mpai-tool-call-status');
                const $result = $toolCall.find('.mpai-tool-call-result');
                
                // Update status to error
                $status.removeClass('mpai-tool-call-processing').addClass('mpai-tool-call-error');
                $status.html('Error');
                
                // Display the error
                $result.html(`<div class="mpai-tool-call-error-message">AJAX error: ${error}</div>`);
                
                // Scroll to bottom to show error
                if (window.MPAI_UIUtils && typeof window.MPAI_UIUtils.scrollToBottom === 'function') {
                    window.MPAI_UIUtils.scrollToBottom();
                }
            }
        });
    }
    
    /**
     * Display an error message
     * 
     * @param {string} message - The error message
     * @param {string} toolName - The name of the tool
     */
    function displayError(message, toolName) {
        // Create an error element
        const errorHTML = `
            <div class="mpai-tool-call mpai-tool-call-error">
                <div class="mpai-tool-call-header">
                    <span class="mpai-tool-call-name">${toolName || 'Error'}</span>
                    <span class="mpai-tool-call-status mpai-tool-call-error">Error</span>
                </div>
                <div class="mpai-tool-call-result">
                    <div class="mpai-tool-call-error-message">${message}</div>
                </div>
            </div>
        `;
        
        // Append to messages if possible
        if (messagesModule && typeof messagesModule.appendToLastAssistantMessage === 'function') {
            messagesModule.appendToLastAssistantMessage(errorHTML);
        } else if (messagesModule && typeof messagesModule.addMessage === 'function') {
            messagesModule.addMessage('assistant', errorHTML);
        } else {
            // If no message module, add to chat messages directly
            $('.mpai-chat-messages').append(errorHTML);
        }
        
        // Scroll to bottom
        if (window.MPAI_UIUtils && typeof window.MPAI_UIUtils.scrollToBottom === 'function') {
            window.MPAI_UIUtils.scrollToBottom();
        }
    }
    
    /**
     * Format tool result based on tool type
     * 
     * @param {string} toolName - The name of the tool
     * @param {*} result - The result data
     * @return {string} Formatted HTML for the result
     */
    function formatToolResult(toolName, result) {
        if (result === null || result === undefined) {
            return '<div class="mpai-tool-call-no-result">No result returned from tool</div>';
        }
        
        // Handle different result types
        let resultStr = '';
        
        if (typeof result === 'string') {
            try {
                // Try to parse as JSON first
                const jsonData = JSON.parse(result);
                resultStr = `<pre class="mpai-tool-result-json">${JSON.stringify(jsonData, null, 2)}</pre>`;
            } catch (e) {
                // Not valid JSON, display as pre-formatted text
                resultStr = `<pre class="mpai-tool-result-text">${result}</pre>`;
            }
        } else if (typeof result === 'object') {
            // Format object as JSON
            resultStr = `<pre class="mpai-tool-result-json">${JSON.stringify(result, null, 2)}</pre>`;
        } else {
            // Convert to string for any other type
            resultStr = `<pre class="mpai-tool-result-text">${String(result)}</pre>`;
        }
        
        return resultStr;
    }
    
    // Public API
    return {
        init: init,
        processToolCalls: processToolCalls,
        executeToolCalls: executeToolCalls,
        formatToolResult: formatToolResult,
        displayError: displayError
    };
})(jQuery);

// Expose the module globally
window.MPAI_Tools = MPAI_Tools;