/**
 * MemberPress AI Assistant - Tool Call Detector
 * 
 * This module provides a unified approach to tool call detection that works consistently
 * across both JavaScript and PHP. It detects tool calls in AI responses and extracts
 * the tool name and parameters.
 */

(function($) {
    'use strict';

    // Store processed tool calls to avoid duplicates
    const processedToolCalls = new Set();

    /**
     * Detect tool calls in an AI response
     * 
     * @param {string} response The AI response to check for tool calls
     * @return {Array} Array of detected tool calls
     */
    function detectToolCalls(response) {
        console.log('Detecting tool calls in response');
        
        const toolCalls = [];
        
        // XML-style format: <tool:tool_name>{"param1": "value1", "param2": "value2"}</tool>
        detectXmlStyleToolCalls(response, toolCalls);
        
        // JSON format: {"name": "tool_name", "parameters": {"param1": "value1", "param2": "value2"}}
        detectJsonToolCalls(response, toolCalls);
        
        // HTML format (DOM-based)
        detectHtmlToolCalls(response, toolCalls);
        
        console.log(`Detected ${toolCalls.length} tool calls`);
        
        return toolCalls;
    }

    /**
     * Detect XML-style tool calls
     * 
     * @param {string} response The AI response
     * @param {Array} toolCalls Array to store detected tool calls
     */
    function detectXmlStyleToolCalls(response, toolCalls) {
        // Pattern for XML-style tool calls: <tool:tool_name>{"param1": "value1", "param2": "value2"}</tool>
        const pattern = /<tool:([^>]+)>([\s\S]*?)<\/tool>/g;
        
        let match;
        while ((match = pattern.exec(response)) !== null) {
            const toolName = match[1];
            const parametersStr = match[2];
            
            // Skip if this exact command has already been processed
            if (processedToolCalls.has(match[0])) {
                continue;
            }
            
            try {
                // Parse parameters
                const parameters = JSON.parse(parametersStr);
                
                // Add to tool calls
                toolCalls.push({
                    name: toolName,
                    parameters: parameters,
                    original: match[0],
                    format: 'xml'
                });
                
                // Mark as processed
                processedToolCalls.add(match[0]);
            } catch (e) {
                console.error('Failed to parse tool parameters:', e);
            }
        }
    }

    /**
     * Detect JSON format tool calls
     * 
     * @param {string} response The AI response
     * @param {Array} toolCalls Array to store detected tool calls
     */
    function detectJsonToolCalls(response, toolCalls) {
        // Pattern for JSON tool calls: {"name": "tool_name", "parameters": {"param1": "value1", "param2": "value2"}}
        const pattern = /```(?:json)?\s*(\{[\s\S]*?\})\s*```/g;
        
        let match;
        while ((match = pattern.exec(response)) !== null) {
            const jsonStr = match[1];
            
            // Skip if this exact command has already been processed
            if (processedToolCalls.has(match[0])) {
                continue;
            }
            
            try {
                // Parse JSON
                const jsonData = JSON.parse(jsonStr);
                
                // Check if this is a tool call
                if (jsonData.name && jsonData.parameters) {
                    // Add to tool calls
                    toolCalls.push({
                        name: jsonData.name,
                        parameters: jsonData.parameters,
                        original: match[0],
                        format: 'json'
                    });
                    
                    // Mark as processed
                    processedToolCalls.add(match[0]);
                } else if (jsonData.tool && jsonData.parameters) {
                    // Legacy format with 'tool' instead of 'name'
                    toolCalls.push({
                        name: jsonData.tool,
                        parameters: jsonData.parameters,
                        original: match[0],
                        format: 'json_legacy'
                    });
                    
                    // Mark as processed
                    processedToolCalls.add(match[0]);
                }
            } catch (e) {
                console.error('Failed to parse JSON tool call:', e);
            }
        }
    }

    /**
     * Detect HTML format tool calls
     * 
     * @param {string} response The AI response
     * @param {Array} toolCalls Array to store detected tool calls
     */
    function detectHtmlToolCalls(response, toolCalls) {
        // Create a temporary div to parse HTML
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = response;
        
        // Find all tool call elements
        const toolCallElements = tempDiv.querySelectorAll('.mpai-tool-call');
        
        for (const element of toolCallElements) {
            const toolId = element.getAttribute('id');
            const toolName = element.getAttribute('data-tool-name');
            const parametersStr = element.getAttribute('data-tool-parameters');
            
            // Skip if this tool call has already been processed
            if (toolId && processedToolCalls.has(toolId)) {
                continue;
            }
            
            if (toolName && parametersStr) {
                try {
                    // Parse parameters
                    const parameters = JSON.parse(parametersStr);
                    
                    // Add to tool calls
                    toolCalls.push({
                        name: toolName,
                        parameters: parameters,
                        original: element.outerHTML,
                        format: 'html',
                        element: element
                    });
                    
                    // Mark as processed
                    if (toolId) {
                        processedToolCalls.add(toolId);
                    }
                } catch (e) {
                    console.error('Failed to parse HTML tool parameters:', e);
                }
            }
        }
    }

    /**
     * Execute detected tool calls
     * 
     * @param {Array} toolCalls Array of detected tool calls
     * @param {string} response Original response
     * @return {string} Updated response with tool call results
     */
    function executeToolCalls(toolCalls, response) {
        console.log(`Executing ${toolCalls.length} tool calls`);
        
        // Create a copy of the response
        let updatedResponse = response;
        
        for (const toolCall of toolCalls) {
            const toolName = toolCall.name;
            const parameters = toolCall.parameters;
            const original = toolCall.original;
            
            // Legacy tool IDs have been removed - only 'wpcli' is supported
            if (toolName === 'wpcli_new' || toolName === 'wp_cli') {
                console.error(`Legacy tool ID "${toolName}" is no longer supported. Use "wpcli" instead.`);
                const errorMessage = `Legacy tool ID "${toolName}" is no longer supported. Use "wpcli" instead.`;
                updatedResponse = updatedResponse.replace(original, formatErrorResult(toolName, errorMessage));
                continue;
            }
            
            // Execute the tool via AJAX
            $.ajax({
                url: mpai_chat_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'mpai_execute_tool',
                    tool_request: JSON.stringify({
                        name: toolName,
                        parameters: parameters
                    }),
                    nonce: mpai_chat_data.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Format the result
                        const resultHtml = formatSuccessResult(toolName, response.data);
                        
                        // Update the response
                        updatedResponse = updatedResponse.replace(original, resultHtml);
                        
                        // If we have a message container, update it
                        if (window.mpai_chat_interface && window.mpai_chat_interface.updateLastAssistantMessage) {
                            window.mpai_chat_interface.updateLastAssistantMessage(updatedResponse);
                        }
                    } else {
                        // Format error
                        const errorHtml = formatErrorResult(toolName, response.data);
                        
                        // Update the response
                        updatedResponse = updatedResponse.replace(original, errorHtml);
                        
                        // If we have a message container, update it
                        if (window.mpai_chat_interface && window.mpai_chat_interface.updateLastAssistantMessage) {
                            window.mpai_chat_interface.updateLastAssistantMessage(updatedResponse);
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error executing tool:', error);
                    
                    // Format error
                    const errorHtml = formatErrorResult(toolName, 'Error executing tool: ' + error);
                    
                    // Update the response
                    updatedResponse = updatedResponse.replace(original, errorHtml);
                    
                    // If we have a message container, update it
                    if (window.mpai_chat_interface && window.mpai_chat_interface.updateLastAssistantMessage) {
                        window.mpai_chat_interface.updateLastAssistantMessage(updatedResponse);
                    }
                }
            });
        }
        
        return updatedResponse;
    }

    /**
     * Format a successful tool execution result
     * 
     * @param {string} toolName The name of the tool
     * @param {*} result The result of the tool execution
     * @return {string} Formatted result
     */
    function formatSuccessResult(toolName, result) {
        // Convert result to string if it's not already
        if (typeof result === 'object') {
            result = JSON.stringify(result, null, 2);
        }
        
        // Create a formatted result
        const formattedResult = `<div class="mpai-tool-result mpai-tool-success">
  <div class="mpai-tool-header">
    <span class="mpai-tool-name">${toolName}</span>
    <span class="mpai-tool-status mpai-tool-success">Success</span>
  </div>
  <div class="mpai-tool-content">
    <pre>${result}</pre>
  </div>
</div>`;
        
        return formattedResult;
    }

    /**
     * Format an error result
     * 
     * @param {string} toolName The name of the tool
     * @param {string} errorMessage The error message
     * @return {string} Formatted error
     */
    function formatErrorResult(toolName, errorMessage) {
        // Create a formatted error
        const formattedError = `<div class="mpai-tool-result mpai-tool-error">
  <div class="mpai-tool-header">
    <span class="mpai-tool-name">${toolName}</span>
    <span class="mpai-tool-status mpai-tool-error">Error</span>
  </div>
  <div class="mpai-tool-content">
    <pre>${errorMessage}</pre>
  </div>
</div>`;
        
        return formattedError;
    }

    /**
     * Process an AI response for tool calls
     * 
     * @param {string} response The AI response to process
     * @return {boolean} Whether tool calls were detected and processed
     */
    function processResponse(response) {
        // Detect tool calls
        const toolCalls = detectToolCalls(response);
        
        // If no tool calls were detected, return false
        if (toolCalls.length === 0) {
            return false;
        }
        
        // Execute tool calls
        executeToolCalls(toolCalls, response);
        
        // Return true to indicate that tool calls were detected and processed
        return true;
    }

    /**
     * Clear the processed tool calls cache
     */
    function clearProcessedToolCalls() {
        processedToolCalls.clear();
    }

    // Export the module
    window.MPAI_ToolCallDetector = {
        detectToolCalls: detectToolCalls,
        executeToolCalls: executeToolCalls,
        processResponse: processResponse,
        clearProcessedToolCalls: clearProcessedToolCalls
    };

})(jQuery);