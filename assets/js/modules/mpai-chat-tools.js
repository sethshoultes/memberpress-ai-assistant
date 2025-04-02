/**
 * MemberPress AI Assistant - Chat Tools Module
 * 
 * Handles tool call detection, execution, and formatting
 */

var MPAI_Tools = (function($) {
    'use strict';
    
    // Private variables
    var elements = {};
    var messagesModule = null;
    var formattersModule = null;
    var processedToolCalls = new Set();
    
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
        // Check for tool call markup
        const toolCallRegex = /<tool:([^>]+)>([\s\S]*?)<\/tool>/g;
        let match;
        let hasToolCalls = false;
        
        // Create a temporary div to parse HTML
        const $temp = $('<div>').html(response);
        
        // Look for structured tool calls
        const $toolCalls = $temp.find('.mpai-tool-call');
        
        if ($toolCalls.length > 0) {
            // Process structured tool calls in the DOM
            hasToolCalls = true;
            
            $toolCalls.each(function() {
                const $toolCall = $(this);
                const toolId = $toolCall.attr('id');
                const toolName = $toolCall.data('tool-name');
                const parametersStr = $toolCall.data('tool-parameters');
                
                // Skip if this tool call has already been processed
                if (processedToolCalls.has(toolId)) {
                    return;
                }
                
                // Mark as processed
                processedToolCalls.add(toolId);
                
                // Parse parameters
                let parameters = {};
                try {
                    parameters = JSON.parse(parametersStr);
                } catch (e) {
                    console.error('Failed to parse tool parameters:', e);
                }
                
                // Execute the tool
                executeToolCall(toolName, parameters, toolId);
            });
        } else {
            // Check for string-based tool calls
            while ((match = toolCallRegex.exec(response)) !== null) {
                hasToolCalls = true;
                
                const toolName = match[1];
                const parametersStr = match[2];
                
                // Generate a unique ID for this tool call
                const toolId = 'mpai-tool-' + Date.now() + '-' + Math.floor(Math.random() * 1000);
                
                // Skip if this exact command has already been processed (using the full match as key)
                if (processedToolCalls.has(match[0])) {
                    continue;
                }
                
                // Mark as processed
                processedToolCalls.add(match[0]);
                
                // Parse parameters
                let parameters = {};
                try {
                    parameters = JSON.parse(parametersStr);
                } catch (e) {
                    console.error('Failed to parse tool parameters:', e);
                }
                
                // Create HTML for the tool call
                const toolCallHtml = createToolCallHTML(toolName, parameters, toolId);
                
                // Replace the tool call markup with the HTML
                response = response.replace(match[0], toolCallHtml);
                
                // Execute the tool
                executeToolCall(toolName, parameters, toolId);
            }
        }
        
        // If tool calls were found and processed, update the response
        if (hasToolCalls && !$toolCalls.length) {
            // Add the processed message
            if (messagesModule) {
                messagesModule.addMessage('assistant', response);
            }
        }
        
        return hasToolCalls;
    }
    
    /**
     * Create HTML for a tool call
     * 
     * @param {string} toolName - The name of the tool
     * @param {object} parameters - The parameters for the tool
     * @param {string} toolId - The HTML element ID
     * @return {string} HTML markup for the tool call
     */
    function createToolCallHTML(toolName, parameters, toolId) {
        return `
        <div id="${toolId}" class="mpai-tool-call" data-tool-name="${toolName}" data-tool-parameters='${JSON.stringify(parameters)}'>
            <div class="mpai-tool-call-header">
                <span class="mpai-tool-call-name">${toolName}</span>
                <span class="mpai-tool-call-status mpai-tool-call-processing">Processing</span>
            </div>
            <div class="mpai-tool-call-parameters">
                <pre>${JSON.stringify(parameters, null, 2)}</pre>
            </div>
            <div class="mpai-tool-call-result"></div>
        </div>
        `;
    }
    
    /**
     * Execute a tool call
     * 
     * @param {string} toolName - The name of the tool to execute
     * @param {object} parameters - The parameters for the tool
     * @param {string} toolId - The HTML element ID for updating the UI
     */
    function executeToolCall(toolName, parameters, toolId) {
        if (!toolName || !parameters || !toolId) {
            console.error('MPAI Tools: Missing required parameters for tool execution');
            return;
        }
        
        // Log the tool call if logger is available
        if (window.mpaiLogger) {
            window.mpaiLogger.logToolUsage(toolName, parameters);
            window.mpaiLogger.startTimer('tool_' + toolId);
        }
        
        // Construct the tool request in the format expected by the backend
        const toolRequest = {
            name: toolName,
            parameters: parameters
        };
        
        $.ajax({
            url: mpai_chat_data.ajax_url,
            type: 'POST',
            data: {
                action: 'mpai_execute_tool',
                tool_request: JSON.stringify(toolRequest),
                nonce: mpai_chat_data.nonce
            },
            success: function(response) {
                if (window.mpaiLogger) {
                    const elapsed = window.mpaiLogger.endTimer('tool_' + toolId);
                    window.mpaiLogger.info(
                        'Tool "' + toolName + '" executed in ' + (elapsed ? elapsed.toFixed(2) + 'ms' : 'unknown time'), 
                        'tool_usage'
                    );
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
                    let resultContent = formatToolResult(toolName, response.data.result, parameters);
                    
                    // Display the result
                    $result.html(resultContent);
                    
                    // Handle post-execution as needed (e.g., for chain of thought responses)
                    if (response.data.final_response) {
                        // Use the messages module to complete tool calls
                        if (messagesModule) {
                            messagesModule.completeToolCalls(response.data.final_response);
                        }
                    }
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
                setTimeout(function() {
                    if (window.MPAI_UIUtils && typeof window.MPAI_UIUtils.scrollToBottom === 'function') {
                        window.MPAI_UIUtils.scrollToBottom();
                    }
                }, 100);
            },
            error: function(xhr, status, error) {
                if (window.mpaiLogger) {
                    window.mpaiLogger.error('AJAX error executing tool ' + toolName, 'tool_usage');
                    window.mpaiLogger.endTimer('tool_' + toolId);
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
                setTimeout(function() {
                    if (window.MPAI_UIUtils && typeof window.MPAI_UIUtils.scrollToBottom === 'function') {
                        window.MPAI_UIUtils.scrollToBottom();
                    }
                }, 100);
            }
        });
    }
    
    /**
     * Format tool result based on tool type
     * 
     * @param {string} toolName - The name of the tool
     * @param {*} result - The result data
     * @param {Object} parameters - The tool parameters
     * @return {string} Formatted HTML for the result
     */
    function formatToolResult(toolName, result, parameters) {
        // Handle null/undefined results
        if (result === null || result === undefined) {
            return '<div class="mpai-tool-call-no-result">No result returned from tool</div>';
        }
        
        // Format based on tool type
        switch (toolName) {
            case 'plugin_logs':
                // Use the dedicated formatter for plugin logs
                if (window.MPAI_Formatters && typeof window.MPAI_Formatters.formatPluginLogsResult === 'function') {
                    return window.MPAI_Formatters.formatPluginLogsResult(result);
                } else if (formattersModule && typeof formattersModule.formatPluginLogsResult === 'function') {
                    return formattersModule.formatPluginLogsResult(result);
                }
                break;
                
            case 'wp_cli':
            case 'runCommand':
                // Format tabular data
                if (result && typeof result === 'object' && result.result && result.command_type) {
                    if (window.MPAI_Formatters && typeof window.MPAI_Formatters.formatTabularResult === 'function') {
                        return window.MPAI_Formatters.formatTabularResult(result);
                    } else if (formattersModule && typeof formattersModule.formatTabularResult === 'function') {
                        return formattersModule.formatTabularResult(result);
                    }
                }
                break;
        }
        
        // Default formatting based on content type
        if (typeof result === 'string') {
            return `<pre class="mpai-command-result"><code>${result}</code></pre>`;
        } else {
            // For objects, arrays, etc.
            try {
                return `<pre class="mpai-command-result"><code>${JSON.stringify(result, null, 2)}</code></pre>`;
            } catch (e) {
                return `<div class="mpai-tool-call-error-message">Error formatting result: ${e.message}</div>`;
            }
        }
    }
    
    /**
     * Format tabular result data
     *
     * @param {object} resultData - The tabular result data object
     * @return {string} Formatted HTML for the table
     */
    function formatTabularResult(resultData) {
        if (!resultData || !resultData.result) {
            return '<div class="mpai-tool-call-error-message">No result data to format</div>';
        }
        
        const commandType = resultData.command_type || 'generic';
        let result = resultData.result || '';
        
        // Process the result to handle escaped tabs and newlines
        if (typeof result === 'string') {
            if (result.includes('\\t')) {
                result = result.replace(/\\t/g, '\t');
            }
            
            if (result.includes('\\n')) {
                result = result.replace(/\\n/g, '\n');
            }
        } else {
            // If result is not a string, return a simple display
            return `<pre class="mpai-command-result"><code>${JSON.stringify(result, null, 2)}</code></pre>`;
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
        
        let tableHtml = '<div class="mpai-result-table">';
        tableHtml += tableTitle;
        tableHtml += '<table>';
        
        // Skip empty rows
        const nonEmptyRows = rows.filter(row => row.trim() !== '');
        
        nonEmptyRows.forEach((row, index) => {
            // Try different delimiters to find the best match
            let cells = [];
            
            // First try tab delimiter
            if (row.includes('\t')) {
                cells = row.split('\t');
            } 
            // Then try space delimiter with some intelligence
            else if (commandType === 'plugin_list' && !row.includes('\t')) {
                // For plugin list, we'll try to intelligently split by multi-spaces
                // This matches the format: Name   Status   Version   Last Activity
                const matches = row.match(/([^\s]+(?:\s+[^\s]+)*)\s{2,}/g);
                
                if (matches && matches.length > 0) {
                    // Add the remainder of the string after the last match
                    const matchedText = matches.join('');
                    const remainder = row.substring(matchedText.length).trim();
                    
                    cells = matches.map(m => m.trim());
                    
                    if (remainder) {
                        cells.push(remainder);
                    }
                } else {
                    // Fallback to standard tab or 4+ space split
                    cells = row.split(/\t|\s{4,}/);
                }
            } 
            // Generic fallback
            else {
                // Split by multiple spaces (3 or more) for other types
                cells = row.split(/\s{3,}/);
                
                // Fallback to basic split method if we didn't get at least 2 cells
                if (cells.length < 2) {
                    cells = row.split(/\s{2,}/);
                }
            }
            
            if (index === 0) {
                // Header row
                tableHtml += '<thead><tr>';
                cells.forEach(cell => {
                    tableHtml += `<th>${cell.trim()}</th>`;
                });
                tableHtml += '</tr></thead><tbody>';
            } else {
                // Handle status formatting for plugin list with special coloring
                if (commandType === 'plugin_list') {
                    tableHtml += '<tr>';
                    cells.forEach((cell, cellIndex) => {
                        cell = cell.trim();
                        
                        // Apply special formatting for Status column (typically index 1)
                        if (cellIndex === 1 && (cell.toLowerCase() === 'active' || cell.toLowerCase() === 'inactive')) {
                            const statusClass = cell.toLowerCase() === 'active' ? 'mpai-status-active' : 'mpai-status-inactive';
                            tableHtml += `<td class="${statusClass}">${cell}</td>`;
                        } else {
                            tableHtml += `<td>${cell}</td>`;
                        }
                    });
                    tableHtml += '</tr>';
                } else {
                    // Standard data row
                    tableHtml += '<tr>';
                    cells.forEach(cell => {
                        tableHtml += `<td>${cell.trim()}</td>`;
                    });
                    tableHtml += '</tr>';
                }
            }
        });
        
        tableHtml += '</tbody></table></div>';
        
        // Add CSS for status indicators
        tableHtml += `
        <style>
            .mpai-result-table table {
                width: 100%;
                border-collapse: collapse;
                margin: 10px 0;
            }
            .mpai-result-table th, .mpai-result-table td {
                padding: 8px;
                text-align: left;
                border: 1px solid #ddd;
            }
            .mpai-result-table th {
                background-color: #f2f2f2;
                font-weight: bold;
            }
            .mpai-result-table .mpai-status-active {
                color: green;
                font-weight: bold;
            }
            .mpai-result-table .mpai-status-inactive {
                color: #999;
            }
        </style>`;
        
        return tableHtml;
    }
    
    /**
     * Reset processed tool calls
     */
    function resetProcessedToolCalls() {
        processedToolCalls.clear();
    }
    
    // Public API
    return {
        init: init,
        processToolCalls: processToolCalls,
        executeToolCall: executeToolCall,
        formatTabularResult: formatTabularResult,
        resetProcessedToolCalls: resetProcessedToolCalls
    };
})(jQuery);

// Expose the module globally
window.MPAI_Tools = MPAI_Tools;