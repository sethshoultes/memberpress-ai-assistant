/**
 * MemberPress AI Assistant - Chat History Module
 * 
 * Handles loading, saving, and managing chat history
 */

(function($) {
    'use strict';
    
    // Define the MPAI Chat History namespace
    window.mpaiChatHistory = window.mpaiChatHistory || {};
    
    /**
     * Load chat history from the server
     * 
     * @param {function} callback - Function to call with the history data
     */
    mpaiChatHistory.loadChatHistory = function(callback) {
        // Default callback if none provided
        callback = callback || function() {};
        
        $.ajax({
            url: mpai_chat_data.ajax_url,
            type: 'POST',
            data: {
                action: 'mpai_get_chat_history',
                nonce: mpai_chat_data.nonce,
                cache_buster: new Date().getTime() // Add timestamp to prevent caching
            },
            success: function(response) {
                if (response.success && response.data.history) {
                    callback(response.data.history);
                } else {
                    callback([]);
                }
            },
            error: function() {
                console.error('MPAI: Failed to load chat history');
                callback([]);
            }
        });
    };
    
    /**
     * Clear chat history
     * 
     * @param {function} callback - Function to call when complete
     */
    mpaiChatHistory.clearChatHistory = function(callback) {
        // Default callback if none provided
        callback = callback || function() {};
        
        $.ajax({
            url: mpai_chat_data.ajax_url,
            type: 'POST',
            data: {
                action: 'mpai_clear_chat_history',
                nonce: mpai_chat_data.nonce
            },
            success: function(response) {
                if (response.success) {
                    callback(true);
                } else {
                    console.error('MPAI: Failed to clear chat history');
                    callback(false);
                }
            },
            error: function() {
                console.error('MPAI: AJAX error clearing chat history');
                callback(false);
            }
        });
    };
    
    /**
     * Save a message to history
     * 
     * @param {string} messageId - The ID of the message element
     * @param {string} content - The HTML content of the message
     * @param {function} callback - Function to call when complete
     */
    mpaiChatHistory.saveMessageToHistory = function(messageId, content, callback) {
        // Default callback if none provided
        callback = callback || function() {};
        
        $.ajax({
            type: 'POST',
            url: mpai_chat_data.ajax_url,
            data: {
                action: 'mpai_update_message',
                message_id: messageId,
                content: content,
                nonce: mpai_chat_data.mpai_nonce // The server expects the mpai_nonce parameter
            },
            success: function(response) {
                callback(response.success);
            },
            error: function() {
                console.error('MPAI: Error saving message to history');
                callback(false);
            }
        });
    };
    
    /**
     * Export a single message
     * 
     * @param {string} messageId - The ID of the message to export
     * @param {string} format - The export format (markdown or html)
     */
    mpaiChatHistory.exportMessage = function(messageId, format) {
        const $message = $('#' + messageId);
        if (!$message.length) return;
        
        // Determine if user or assistant message
        const isUserMessage = $message.hasClass('mpai-chat-message-user');
        const role = isUserMessage ? 'User' : 'Assistant';
        
        // Get message content - check if we have saved formatted tool results
        let content;
        if (window.mpai_saved_tool_results && window.mpai_saved_tool_results[messageId]) {
            // Use the saved formatted content that includes properly rendered plugin logs
            content = $('<div>').html(window.mpai_saved_tool_results[messageId]);
        } else {
            // Use the original content
            content = $message.find('.mpai-chat-message-content').clone();
        }
        
        // Remove any interactive elements from the clone
        content.find('.mpai-command-toolbar, .mpai-tool-call, .mpai-loading-dots').remove();
        
        let fileContent = '';
        let fileExt = '';
        
        if (format === 'html') {
            // For HTML export, preserve the HTML structure
            // Get the HTML content including formatting
            let htmlContent = content.html();
            
            // Create a styled HTML document
            fileContent = mpaiChatHistory.createStyledHTML(`<h3>${role}</h3><div class="message-content">${htmlContent}</div>`);
            fileExt = 'html';
        } else {
            // For Markdown export - convert HTML to markdown
            // Clone the content to work with
            const markdownContent = content.clone();
            
            // Process HTML elements to markdown format
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
            
            // Get the final text format
            let textContent = markdownContent.text().trim();
            
            // Clean up any artifacts from HTML to markdown conversion
            textContent = textContent
                .replace(/([^\n])\s{2,}([^\n])/g, '$1 $2')  // Replace multiple spaces with a single space
                .replace(/\n{3,}/g, '\n\n'); // Replace multiple newlines with double newlines
            
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
        mpaiChatHistory.downloadTextFile(fileContent, filename, format === 'html' ? 'text/html' : 'text/markdown');
    };
    
    /**
     * Export the entire conversation
     * 
     * @param {string} format - The export format (markdown or html)
     */
    mpaiChatHistory.exportConversation = function(format) {
        // Collect all messages
        const messages = [];
        const htmlMessages = [];
        
        $('.mpai-chat-message').each(function() {
            const isUserMessage = $(this).hasClass('mpai-chat-message-user');
            const role = isUserMessage ? 'User' : 'Assistant';
            
            // Get the message ID to check if we have saved tool results
            const messageId = $(this).attr('id');
            
            // Get message content - check if we have saved formatted tool results
            let content;
            if (window.mpai_saved_tool_results && window.mpai_saved_tool_results[messageId]) {
                // Use the saved formatted content that includes properly rendered plugin logs
                content = $('<div>').html(window.mpai_saved_tool_results[messageId]);
            } else {
                // Use the original content
                content = $(this).find('.mpai-chat-message-content').clone();
            }
            
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
                
                // Process HTML elements to markdown format
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
                
                // Get the final text format
                let textContent = markdownContent.text().trim();
                
                // Clean up any artifacts from HTML to markdown conversion
                textContent = textContent
                    .replace(/([^\n])\s{2,}([^\n])/g, '$1 $2')  // Replace multiple spaces with a single space
                    .replace(/\n{3,}/g, '\n\n'); // Replace multiple newlines with double newlines
                
                messages.push(`## ${role}\n\n${textContent}\n`);
            }
        });
        
        let fileContent = '';
        let fileExt = '';
        
        if (format === 'html') {
            // Create a styled HTML document with all messages
            fileContent = mpaiChatHistory.createStyledHTML(`<div class="chat-container">${htmlMessages.join('\n<hr>\n')}</div>`);
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
        mpaiChatHistory.downloadTextFile(fileContent, filename, format === 'html' ? 'text/html' : 'text/markdown');
    };
    
    /**
     * Creates a styled HTML document with the provided content
     * 
     * @param {string} content - The HTML content to include in the document
     * @returns {string} - The complete HTML document as a string
     */
    mpaiChatHistory.createStyledHTML = function(content) {
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
    };
    
    /**
     * Helper function to download text as a file
     * 
     * @param {string} content - The text content to download
     * @param {string} filename - The name of the file to download
     * @param {string} mimeType - The MIME type of the file
     */
    mpaiChatHistory.downloadTextFile = function(content, filename, mimeType) {
        // Create a blob with the content and appropriate MIME type
        const blob = new Blob([content], { type: mimeType || 'text/plain' });
        
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
    };
    
})(jQuery);
