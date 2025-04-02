/**
 * MemberPress AI Assistant - Chat Tools Module
 * 
 * Handles execution and formatting of tool calls within the chat interface
 */

(function($) {
    'use strict';
    
    // Define the MPAI Chat Tools namespace
    window.mpaiChatTools = window.mpaiChatTools || {};
    
    /**
     * Process and execute a tool call
     * 
     * @param {string} toolName - The name of the tool to execute
     * @param {object} parameters - The parameters for the tool
     * @param {string} toolId - The HTML element ID for updating the UI
     */
    mpaiChatTools.executeToolCall = function(toolName, parameters, toolId) {
        if (!toolName || !parameters || !toolId) {
            console.error('MPAI Chat Tools: Missing required parameters for tool execution');
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
                    window.mpaiLogger.debug('Tool execution response', 'tool_usage', response);
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
                    
                    if (typeof response.data.result === 'string') {
                        resultContent = `<pre class="mpai-command-result"><code>${response.data.result}</code></pre>`;
                    } else {
                        resultContent = `<pre class="mpai-command-result"><code>${JSON.stringify(response.data.result, null, 2)}</code></pre>`;
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
                setTimeout(function() {
                    if (typeof mpaiChatUI !== 'undefined' && mpaiChatUI.scrollToBottom) {
                        mpaiChatUI.scrollToBottom();
                    }
                }, 100);
            },
            error: function(xhr, status, error) {
                if (window.mpaiLogger) {
                    window.mpaiLogger.error('AJAX error executing tool ' + toolName, 'tool_usage', {
                        xhr: xhr,
                        status: status,
                        error: error,
                        tool: toolName,
                        parameters: parameters
                    });
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
                    if (typeof mpaiChatUI !== 'undefined' && mpaiChatUI.scrollToBottom) {
                        mpaiChatUI.scrollToBottom();
                    }
                }, 100);
            }
        });
    };
    
    /**
     * Format tabular result data
     *
     * @param {object} resultData - The tabular result data object
     * @return {string} Formatted HTML for the table
     */
    mpaiChatTools.formatTabularResult = function(resultData) {
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
    };
    
})(jQuery);
