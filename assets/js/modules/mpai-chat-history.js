/**
 * MemberPress AI Assistant - Chat History Module
 * 
 * Handles loading, saving, and managing chat history
 */

var MPAI_History = (function($) {
    'use strict';
    
    // Private variables
    var elements = {};
    var messagesModule = null;
    
    /**
     * Initialize the module
     * 
     * @param {Object} domElements - DOM elements
     * @param {Object} messages - The messages module
     */
    function init(domElements, messages) {
        elements = domElements;
        messagesModule = messages;
        
        if (window.mpaiLogger) {
            window.mpaiLogger.info('History module initialized', 'ui');
        }
    }
    
    /**
     * Load chat history from the server
     */
    function loadChatHistory() {
        // Clear existing messages to avoid duplicates
        elements.chatMessages.empty();
        
        $.ajax({
            url: mpai_chat_data.ajax_url,
            type: 'POST',
            data: {
                action: 'mpai_get_chat_history',
                nonce: mpai_chat_data.nonce,
                cache_buster: new Date().getTime() // Add timestamp to prevent caching
            },
            success: function(response) {
                if (response.success && response.data.history && response.data.history.length > 0) {
                    displayChatHistory(response.data.history);
                    
                    if (window.mpaiLogger) {
                        window.mpaiLogger.info('Chat history loaded: ' + response.data.history.length + ' messages', 'ui');
                    }
                } else {
                    // No history to load or empty history
                    if (window.mpaiLogger) {
                        window.mpaiLogger.info('No chat history to load', 'ui');
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('MPAI: Failed to load chat history', error);
                
                if (window.mpaiLogger) {
                    window.mpaiLogger.error('Failed to load chat history: ' + error, 'ui');
                }
            }
        });
    }
    
    /**
     * Display chat history
     * 
     * @param {Array} history - The chat history
     */
    function displayChatHistory(history) {
        // Loop through the history and add each message
        history.forEach(function(item) {
            if (messagesModule) {
                messagesModule.addMessage(
                    item.role, 
                    item.content, 
                    { 
                        isHistory: true,
                        timestamp: item.timestamp 
                    }
                );
            }
        });
        
        // Log the number of messages loaded
        if (window.mpaiLogger) {
            window.mpaiLogger.info('Loaded ' + history.length + ' chat messages from history', 'ui');
        }
    }
    
    /**
     * Clear chat history
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
                    // Clear the chat interface
                    elements.chatMessages.empty();
                    
                    // Display a message that history has been cleared
                    if (messagesModule) {
                        messagesModule.addMessage(
                            'assistant',
                            'Chat history has been cleared.'
                        );
                    }
                    
                    if (window.mpaiLogger) {
                        window.mpaiLogger.info('Chat history cleared', 'ui');
                    }
                } else {
                    console.error('MPAI: Failed to clear chat history');
                    
                    if (window.mpaiLogger) {
                        window.mpaiLogger.error('Failed to clear chat history: ' + (response.data || 'Unknown error'), 'ui');
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('MPAI: AJAX error clearing chat history', error);
                
                if (window.mpaiLogger) {
                    window.mpaiLogger.error('AJAX error clearing chat history: ' + error, 'ui');
                }
            }
        });
    }
    
    /**
     * Export a single message
     * 
     * @param {string} messageId - The ID of the message to export
     * @param {string} format - The export format (markdown or html)
     */
    function exportMessage(messageId, format = 'html') {
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
            let htmlContent = content.html();
            
            // Create a styled HTML document
            fileContent = createStyledHTML(`<h3>${role}</h3><div class="message-content">${htmlContent}</div>`);
            fileExt = 'html';
        } else {
            // For Markdown export
            let textContent = formatHtmlAsMarkdown(content);
            
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
        downloadTextFile(fileContent, filename, format === 'html' ? 'text/html' : 'text/markdown');
    }
    
    /**
     * Export the entire conversation
     * 
     * @param {string} format - The export format (markdown or html)
     */
    function exportChatHistory(format = 'html') {
        // Collect all messages
        const messages = [];
        const htmlMessages = [];
        
        $('.mpai-chat-message').each(function() {
            const isUserMessage = $(this).attr('data-role') === 'user';
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
                // For Markdown export
                let textContent = formatHtmlAsMarkdown(content);
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
        downloadTextFile(fileContent, filename, format === 'html' ? 'text/html' : 'text/markdown');
    }
    
    /**
     * Format HTML content as Markdown text
     * 
     * @param {jQuery} $content - The HTML content to convert
     * @return {string} Markdown formatted text
     */
    function formatHtmlAsMarkdown($content) {
        // Clone the content to work with
        const $clone = $content.clone();
        
        // Process HTML elements to markdown format
        $clone.find('table').each(function() {
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
        $clone.find('pre').each(function() {
            const code = $(this).text().trim();
            $(this).replaceWith('\n```\n' + code + '\n```\n');
        });
        
        // Replace inline code elements
        $clone.find('code').each(function() {
            if ($(this).parent().is('pre')) return; // Skip if in a pre block
            const code = $(this).text().trim();
            $(this).replaceWith('`' + code + '`');
        });
        
        // Get the final text format
        let textContent = $clone.text().trim();
        
        // Clean up any artifacts from HTML to markdown conversion
        textContent = textContent
            .replace(/([^\n])\s{2,}([^\n])/g, '$1 $2')  // Replace multiple spaces with a single space
            .replace(/\n{3,}/g, '\n\n'); // Replace multiple newlines with double newlines
        
        return textContent;
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
    function downloadTextFile(content, filename, mimeType) {
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
    }
    
    // Public API
    return {
        init: init,
        loadChatHistory: loadChatHistory,
        clearChatHistory: clearChatHistory,
        exportMessage: exportMessage,
        exportChatHistory: exportChatHistory
    };
})(jQuery);

// Expose the module globally
window.MPAI_History = MPAI_History;