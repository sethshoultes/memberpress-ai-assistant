/**
 * MemberPress AI Assistant - Chat Formatters Module
 * 
 * Handles formatting of messages and special content types in the chat interface
 */

(function($) {
    'use strict';
    
    // Define the MPAI Chat Formatters namespace
    window.mpaiChatFormatters = window.mpaiChatFormatters || {};
    
    /**
     * Format a message with markdown-like syntax
     * 
     * @param {*} content - The message content (any type)
     * @return {string} Formatted content
     */
    mpaiChatFormatters.formatMessage = function(content) {
        // Guard for null/undefined first
        if (content === null || content === undefined) {
            console.error('formatMessage received null or undefined content');
            return 'No response received';
        }
        
        // Convert any non-string content to string
        if (typeof content !== 'string') {
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
        
        try {
            // Store any wp commands so we can make them clickable
            const wpCommands = [];
            
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
                
            // Convert **text** to <strong>text</strong>
            content = content.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                
            // Convert *text* to <em>text</em>
            content = content.replace(/\*(.*?)\*/g, '<em>$1</em>');
                
            // Special handling for inline WP CLI commands - make them runnable
            content = content.replace(/`(wp\s+[^`]+)`/g, function(match, p1) {
                const command = p1.trim();
                wpCommands.push(command);
                return '<code class="mpai-runnable-command" data-command="' + 
                    command.replace(/"/g, '&quot;') + 
                    '">' + p1.replace(/</g, '&lt;').replace(/>/g, '&gt;') + 
                    ' <span class="mpai-run-indicator">▶</span></code>';
            });
                
            // Convert `code` to <code>code</code> (excluding what's already processed for WP commands)
            content = content.replace(/`([^`]+)`/g, function(match, p1) {
                if (p1.trim().startsWith('wp ')) {
                    // Already processed as a WP command
                    return match;
                }
                return '<code>' + p1.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</code>';
            });
                
            // Convert line breaks to <br>
            content = content.replace(/\n/g, '<br>');
                
            // If we found any WP commands, add a toolbar to the message
            if (wpCommands.length > 0) {
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
    };
    
    /**
     * Format plugin logs result
     * 
     * @param {object} data - The plugin logs data from the direct AJAX handler
     * @return {string} Formatted HTML for the plugin logs
     */
    mpaiChatFormatters.formatPluginLogsResult = function(data) {
        // Handle the case where data is undefined or null
        if (!data) {
            return '<div class="mpai-plugin-logs-error">No plugin logs data available</div>';
        }
        
        // Format summary
        let html = '<div class="mpai-plugin-logs">';
        
        // Add summary section
        html += '<div class="mpai-plugin-logs-summary">';
        html += `<h3>Plugin Activity Summary (Last 30 Days)</h3>`;
        
        // Create summary table
        html += '<table class="mpai-summary-table">';
        html += '<tr>';
        
        // Check if summary exists, if not try to find it in a different location
        const summary = data.summary || (data.data && data.data.summary) || {};
        
        // Add each count to the table if it exists
        if (summary.activated > 0) {
            html += `<td><strong>Activated:</strong> ${summary.activated}</td>`;
        }
        if (summary.installed > 0) {
            html += `<td><strong>Installed:</strong> ${summary.installed}</td>`;
        }
        if (summary.updated > 0) {
            html += `<td><strong>Updated:</strong> ${summary.updated}</td>`;
        }
        if (summary.deactivated > 0) {
            html += `<td><strong>Deactivated:</strong> ${summary.deactivated}</td>`;
        }
        if (summary.deleted > 0) {
            html += `<td><strong>Deleted:</strong> ${summary.deleted}</td>`;
        }
        
        // Add total if we have a count
        if (summary.total > 0) {
            html += `<td><strong>Total:</strong> ${summary.total}</td>`;
        }
        
        html += '</tr></table>';
        html += '</div>';
        
        // Get logs from the appropriate location
        const logs = data.logs || (data.data && data.data.logs) || [];
        
        // Add logs section if we have logs
        if (logs.length > 0) {
            html += '<div class="mpai-plugin-logs-list">';
            html += '<h4>Recent Plugin Activity</h4>';
            html += '<table class="mpai-logs-table">';
            html += '<thead><tr>';
            html += '<th>Plugin</th>';
            html += '<th>Action</th>';
            html += '<th>Version</th>';
            html += '<th>When</th>';
            html += '<th>User</th>';
            html += '</tr></thead><tbody>';
            
            // Add each log entry
            logs.forEach(log => {
                const date = new Date(log.date_time);
                const timeAgo = log.time_ago || `${date.toLocaleDateString()} ${date.toLocaleTimeString()}`;
                
                html += '<tr>';
                html += `<td>${log.plugin_name}</td>`;
                html += `<td>${log.action.charAt(0).toUpperCase() + log.action.slice(1)}</td>`;
                html += `<td>${log.plugin_version || '-'}</td>`;
                html += `<td>${timeAgo}</td>`;
                html += `<td>${log.user_login || '-'}</td>`;
                html += '</tr>';
            });
            
            html += '</tbody></table>';
            html += '</div>';
        } else {
            html += '<div class="mpai-no-logs">No plugin activity found in the specified time period.</div>';
        }
        
        html += '</div>';
        return html;
    };
    
})(jQuery);
