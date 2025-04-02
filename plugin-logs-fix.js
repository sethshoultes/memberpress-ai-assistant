/**
 * This is a fix for the plugin_logs tool issues
 * 
 * To restore functionality, incorporate these changes into chat-interface.js:
 * 
 * 1. Locate the executeToolCall function, find the section where it handles plugin_logs tool
 * 2. Replace the code with this direct handler approach
 * 3. Make sure the formatExistingPluginLogsResult function is properly updated to handle
 *    the response format from the direct handler
 */

// Inside the executeToolCall function, replace the plugin_logs section with:

// Special handling for plugin_logs tool - use direct AJAX handler
if (jsonData.tool === 'plugin_logs') {
    console.log('MPAI: Using direct plugin_logs endpoint (no nonce required)');
    
    // Set up data for direct handler
    let pluginLogsData = {
        action: 'plugin_logs',  // Direct handler action
        action_type: jsonData.parameters.action || '',  // Action parameter for filtering
        days: jsonData.parameters.days || 30
    };
    
    // Get the plugin URL from data passed from PHP
    const pluginUrl = mpai_chat_data.plugin_url || '';
    console.log('MPAI: Plugin URL for direct handler:', pluginUrl);
    
    // Use direct AJAX handler that bypasses admin-ajax.php
    $.ajax({
        url: pluginUrl + 'includes/direct-ajax-handler.php',
        type: 'POST',
        data: pluginLogsData,
        dataType: 'json',  // Ensure jQuery parses the response as JSON
        success: function(response) {
            console.log('MPAI: Direct plugin_logs endpoint response', response);
            
            // Log the actual response type and content for debugging
            console.log('MPAI: Response type:', typeof response);
            console.log('MPAI: Response has summary property:', response && response.summary ? 'Yes' : 'No');
            
            const $toolCall = $('#' + toolId);
            if (!$toolCall.length) return;
            
            const $status = $toolCall.find('.mpai-tool-call-status');
            const $result = $toolCall.find('.mpai-tool-call-result');
            
            if (response.success) {
                // Update status to success
                $status.removeClass('mpai-tool-call-processing').addClass('mpai-tool-call-success');
                $status.html('Success');
                
                // Format plugin logs result - direct handler returns direct response, not in response.data
                let resultContent = formatExistingPluginLogsResult(response);
                $result.html(resultContent);
            } else {
                // Update status to error
                $status.removeClass('mpai-tool-call-processing').addClass('mpai-tool-call-error');
                $status.html('Error');
                
                // Display the error
                const errorMessage = response.data || 'Error getting plugin logs';
                $result.html(`<div class="mpai-tool-call-error-message">${errorMessage}</div>`);
            }
            
            // Scroll to bottom to show results
            setTimeout(scrollToBottom, 100);
        },
        error: function(xhr, status, error) {
            console.error('MPAI: Error with direct plugin_logs endpoint', {
                xhr, status, error, toolId
            });
            
            const $toolCall = $('#' + toolId);
            if (!$toolCall.length) return;
            
            const $status = $toolCall.find('.mpai-tool-call-status');
            const $result = $toolCall.find('.mpai-tool-call-result');
            
            // Update status to error
            $status.removeClass('mpai-tool-call-processing').addClass('mpai-tool-call-error');
            $status.html('Error');
            
            // Display the error
            $result.html(`<div class="mpai-tool-call-error-message">Error getting plugin logs: ${error}</div>`);
            
            // Scroll to bottom to show results
            setTimeout(scrollToBottom, 100);
        }
    });
    
    // Return early - we're handling this tool separately
    return;
}

/**
 * Replace the formatExistingPluginLogsResult function with this version:
 */
function formatExistingPluginLogsResult(data) {
    console.log('MPAI: Formatting existing plugin logs result', data);
    
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
}