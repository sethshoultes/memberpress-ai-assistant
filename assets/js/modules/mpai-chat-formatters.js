/**
 * MemberPress AI Assistant - Chat Formatters Module
 * 
 * Handles formatting of messages and special content types in the chat interface
 */

var MPAI_Formatters = (function($) {
    'use strict';
    
    /**
     * Initialize the module
     */
    function init() {
        if (window.mpaiLogger) {
            window.mpaiLogger.info('Formatters module initialized', 'ui');
        }
    }
    
    /**
     * Format a message with markdown-like syntax
     * 
     * @param {*} content - The message content (any type)
     * @return {string} Formatted content
     */
    function formatMessage(content) {
        // Log the start of formatting
        if (window.mpaiLogger) {
            window.mpaiLogger.startTimer('format_message');
            window.mpaiLogger.debug('Starting message formatting', 'ui', { contentType: typeof content });
        }
        
        // Guard for null/undefined first
        if (content === null || content === undefined) {
            if (window.mpaiLogger) {
                window.mpaiLogger.error('formatMessage received null or undefined content', 'ui');
            } else {
                console.error('formatMessage received null or undefined content');
            }
            return 'No response received';
        }
        
        // Convert any non-string content to string
        if (typeof content !== 'string') {
            try {
                if (typeof content === 'object') {
                    // Try to convert object to JSON string
                    content = JSON.stringify(content);
                    if (window.mpaiLogger) {
                        window.mpaiLogger.debug('Converted object to JSON string', 'ui');
                    }
                } else {
                    // Convert any other type to string
                    content = String(content);
                    if (window.mpaiLogger) {
                        window.mpaiLogger.debug('Converted ' + typeof content + ' to string', 'ui');
                    }
                }
            } catch (e) {
                if (window.mpaiLogger) {
                    window.mpaiLogger.error('Error converting content to string:', 'ui', e);
                } else {
                    console.error('Error converting content to string:', e);
                }
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
            
            // Process XML blog post content
            // Look for wp-post XML content outside of code blocks - using a completely revised approach
            // This completely rewrites the XML handling to be more robust in all cases
            let xmlFound = false;
            
            // Add event handler for toggle XML button if not already added
            if (!window.mpaiXmlToggleHandlerAdded) {
                $(document).on('click', '.mpai-toggle-xml-button', function(e) {
                    e.preventDefault();
                    e.stopPropagation(); // Prevent event bubbling
                    
                    const $card = $(this).closest('.mpai-post-preview-card');
                    const $xmlContent = $card.find('.mpai-post-xml-content');
                    
                    // Toggle a class instead of just using slideUp/slideDown
                    if ($xmlContent.hasClass('xml-visible')) {
                        $xmlContent.removeClass('xml-visible');
                        $xmlContent.slideUp(200);
                        $(this).text('View XML');
                    } else {
                        $xmlContent.addClass('xml-visible');
                        $xmlContent.slideDown(200);
                        $(this).text('Hide XML');
                    }
                    
                    // Return false to prevent any other handlers from executing
                    return false;
                });
                
                // Add click handler for .mpai-create-post-button
                $(document).on('click', '.mpai-create-post-button', function(e) {
                    e.preventDefault();
                    e.stopPropagation(); // Prevent event bubbling
                    
                    const $button = $(this);
                    const contentType = $button.data('content-type') || 'post';
                    const xmlContent = $button.data('xml') || $button.closest('.mpai-post-preview-card').data('xml-content');
                    
                    console.log("Create post button clicked in chat-formatters.js");
                    console.log("Content type:", contentType);
                    console.log("XML content available:", !!xmlContent);
                    
                    if (window.mpaiLogger) {
                        window.mpaiLogger.info('Create post button clicked in chat-formatters.js', 'ui', {
                            contentType: contentType,
                            hasXmlContent: !!xmlContent
                        });
                    }
                    
                    // Check if MPAI_BlogFormatter is available
                    if (window.MPAI_BlogFormatter && typeof window.MPAI_BlogFormatter.createPostFromXML === 'function') {
                        // Use the blog formatter to create the post
                        window.MPAI_BlogFormatter.createPostFromXML(xmlContent, contentType);
                    } else {
                        console.error("MPAI_BlogFormatter not available or createPostFromXML method not found");
                        alert("Error: Blog formatter module not available. Please refresh the page and try again.");
                    }
                    
                    return false;
                });
                
                console.log("XML post creation button handler ENABLED in chat-formatters.js");
                if (window.mpaiLogger) {
                    window.mpaiLogger.info('XML post creation button handler ENABLED in chat-formatters.js', 'ui');
                }
                
                window.mpaiXmlToggleHandlerAdded = true;
                
                if (window.mpaiLogger) {
                    window.mpaiLogger.info('XML toggle handler added', 'ui');
                }
            }
            
            // ========================
            // XML DETECTION IN CHAT FORMATTERS
            // We need to properly handle XML content in the chat
            // ========================
            
            // Process XML code blocks and extract content
            let xmlCodeBlockContent = null;
            content = content.replace(/```xml\s*([\s\S]*?)```/g, function(match, xmlCode) {
                // Store the XML content for processing
                if (xmlCode.includes('<wp-post>') && xmlCode.includes('</wp-post>')) {
                    xmlCodeBlockContent = xmlCode;
                    // Return an empty string to remove the XML code block from the content
                    return '';
                }
                // If it's not a wp-post XML block, return the original match
                return match;
            });
            
            // Re-enable XML handling in chat formatters
            if (xmlCodeBlockContent || content.includes('<wp-post>')) {
                if (window.mpaiLogger) {
                    window.mpaiLogger.info('Processing XML content in chat formatters', 'ui');
                }
                
                // Pre-process the content to protect XML from other formatters
                // This uses a simple but effective search method
                const processXmlBlock = function(fullContent) {
                    // Find all occurrences of wp-post tags
                    let startIndex = 0;
                    const blocks = [];
                    const placeholders = [];
                    let blockCount = 0;
                    
                    while ((startIndex = fullContent.indexOf('<wp-post', startIndex)) !== -1) {
                        const openTagEnd = fullContent.indexOf('>', startIndex);
                        if (openTagEnd === -1) break;
                        
                        const closeTagStart = fullContent.indexOf('</wp-post>', openTagEnd);
                        if (closeTagStart === -1) break;
                        
                        const closeTagEnd = closeTagStart + 10; // Length of '</wp-post>'
                        
                        // Extract the XML block
                        const xmlBlock = fullContent.substring(startIndex, closeTagEnd);
                        blocks.push(xmlBlock);
                        
                        // Create a unique placeholder
                        const placeholder = `__XML_BLOCK_${blockCount++}__`;
                        placeholders.push(placeholder);
                        
                        // Replace the XML block with the placeholder in the original content
                        fullContent = fullContent.substring(0, startIndex) + 
                                   placeholder + 
                                   fullContent.substring(closeTagEnd);
                        
                        // Update startIndex to continue search after the placeholder
                        startIndex = startIndex + placeholder.length;
                    }
                    
                    return { content: fullContent, blocks, placeholders };
                };
                
                // Use either the XML from a code block or look for it in the regular content
                let contentToProcess = content;
                if (xmlCodeBlockContent) {
                    // If we found XML in a code block, use that directly
                    const tempResult = {
                        content: content,  // Keep original content for display
                        blocks: [xmlCodeBlockContent],
                        placeholders: ['__XML_BLOCK_FROM_CODE_BLOCK__']
                    };
                    
                    if (window.mpaiLogger) {
                        window.mpaiLogger.info('Using XML content from code block', 'ui', { 
                            length: xmlCodeBlockContent.length 
                        });
                    }
                    
                    // Override result with our synthetic one
                    const result = tempResult;
                    content = result.content;
                    
                    // Process each XML block and reinsert it later
                    const processedBlocks = result.blocks.map(function(xmlContent, index) {
                    if (window.mpaiLogger) {
                        window.mpaiLogger.info(`Processing XML blog post content block ${index}`, 'ui', { 
                            length: xmlContent.length 
                        });
                    }
                    
                    // Extract title and content for preview card
                    let title = "New Blog Post";
                    let excerpt = "Blog post content created with AI Assistant";
                    let postType = "post";
                    
                    // Try to extract title
                    const titleMatch = xmlContent.match(/<post-title[^>]*>([\s\S]*?)<\/post-title>/i);
                    if (titleMatch && titleMatch[1]) {
                        title = titleMatch[1].trim();
                    }
                    
                    // Try to extract excerpt
                    const excerptMatch = xmlContent.match(/<post-excerpt[^>]*>([\s\S]*?)<\/post-excerpt>/i);
                    if (excerptMatch && excerptMatch[1]) {
                        excerpt = excerptMatch[1].trim();
                    } else {
                        // If no excerpt, try to get first paragraph from content
                        const contentMatch = xmlContent.match(/<post-content[^>]*>([\s\S]*?)<\/post-content>/i);
                        if (contentMatch && contentMatch[1]) {
                            // Find first paragraph or block
                            const firstBlockMatch = contentMatch[1].match(/<block[^>]*>([\s\S]*?)<\/block>/i);
                            if (firstBlockMatch && firstBlockMatch[1]) {
                                excerpt = firstBlockMatch[1].trim();
                                // Limit length
                                if (excerpt.length > 150) {
                                    excerpt = excerpt.substring(0, 147) + '...';
                                }
                            }
                        }
                    }
                    
                    // Check for post type
                    const typeMatch = xmlContent.match(/<post-type[^>]*>([\s\S]*?)<\/post-type>/i);
                    if (typeMatch && typeMatch[1] && typeMatch[1].trim().toLowerCase() === 'page') {
                        postType = "page";
                    }
                    
                    // Escape the XML content for data attribute
                    const escapedXml = encodeURIComponent(xmlContent);
                    
                    // Create a user-friendly preview card
                    return `<div class="mpai-post-preview-card">
                        <div class="mpai-post-preview-header">
                            <div class="mpai-post-preview-type">${postType === "page" ? "Page" : "Blog Post"}</div>
                            <div class="mpai-post-preview-icon">${postType === "page" ? '<span class="dashicons dashicons-page"></span>' : '<span class="dashicons dashicons-admin-post"></span>'}</div>
                        </div>
                        <h3 class="mpai-post-preview-title">${title}</h3>
                        <div class="mpai-post-preview-excerpt">${excerpt}</div>
                        <div class="mpai-post-preview-actions">
                            <button class="mpai-create-post-button" data-content-type="${postType}" data-xml="${escapedXml}">
                                Create ${postType === "page" ? "Page" : "Post"}
                            </button>
                            <button class="mpai-toggle-xml-button">View XML</button>
                        </div>
                        <div class="mpai-post-xml-content">
                            <pre>${xmlContent.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</pre>
                        </div>
                    </div>`;
                });
                
                    // Reinsert the processed XML blocks
                    for (let i = 0; i < result.placeholders.length; i++) {
                        content = content.replace(result.placeholders[i], processedBlocks[i]);
                        xmlFound = true;
                    }
                } else {
                    // Process and protect all XML blocks using regular approach
                    const result = processXmlBlock(content);
                    content = result.content;
                    
                    // Process each XML block and reinsert it later
                    const processedBlocks = result.blocks.map(function(xmlContent, index) {
                        if (window.mpaiLogger) {
                            window.mpaiLogger.info(`Processing XML blog post content block ${index}`, 'ui', { 
                                length: xmlContent.length 
                            });
                        }
                        
                        // Extract title and content for preview card
                        let title = "New Blog Post";
                        let excerpt = "Blog post content created with AI Assistant";
                        let postType = "post";
                        
                        // Try to extract title
                        const titleMatch = xmlContent.match(/<post-title[^>]*>([\s\S]*?)<\/post-title>/i);
                        if (titleMatch && titleMatch[1]) {
                            title = titleMatch[1].trim();
                        }
                        
                        // Try to extract excerpt
                        const excerptMatch = xmlContent.match(/<post-excerpt[^>]*>([\s\S]*?)<\/post-excerpt>/i);
                        if (excerptMatch && excerptMatch[1]) {
                            excerpt = excerptMatch[1].trim();
                        } else {
                            // If no excerpt, try to get first paragraph from content
                            const contentMatch = xmlContent.match(/<post-content[^>]*>([\s\S]*?)<\/post-content>/i);
                            if (contentMatch && contentMatch[1]) {
                                // Find first paragraph or block
                                const firstBlockMatch = contentMatch[1].match(/<block[^>]*>([\s\S]*?)<\/block>/i);
                                if (firstBlockMatch && firstBlockMatch[1]) {
                                    excerpt = firstBlockMatch[1].trim();
                                    // Limit length
                                    if (excerpt.length > 150) {
                                        excerpt = excerpt.substring(0, 147) + '...';
                                    }
                                }
                            }
                        }
                        
                        // Check for post type
                        const typeMatch = xmlContent.match(/<post-type[^>]*>([\s\S]*?)<\/post-type>/i);
                        if (typeMatch && typeMatch[1] && typeMatch[1].trim().toLowerCase() === 'page') {
                            postType = "page";
                        }
                        
                        // Escape the XML content for data attribute
                        const escapedXml = encodeURIComponent(xmlContent);
                        
                        // Create a user-friendly preview card
                        return `<div class="mpai-post-preview-card">
                            <div class="mpai-post-preview-header">
                                <div class="mpai-post-preview-type">${postType === "page" ? "Page" : "Blog Post"}</div>
                                <div class="mpai-post-preview-icon">${postType === "page" ? '<span class="dashicons dashicons-page"></span>' : '<span class="dashicons dashicons-admin-post"></span>'}</div>
                            </div>
                            <h3 class="mpai-post-preview-title">${title}</h3>
                            <div class="mpai-post-preview-excerpt">${excerpt}</div>
                            <div class="mpai-post-preview-actions">
                                <button class="mpai-create-post-button" data-content-type="${postType}" data-xml="${escapedXml}">
                                    Create ${postType === "page" ? "Page" : "Post"}
                                </button>
                                <button class="mpai-toggle-xml-button">View XML</button>
                            </div>
                            <div class="mpai-post-xml-content">
                                <pre>${xmlContent.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</pre>
                            </div>
                        </div>`;
                    });
                    
                    // Reinsert the processed XML blocks
                    for (let i = 0; i < result.placeholders.length; i++) {
                        content = content.replace(result.placeholders[i], processedBlocks[i]);
                        xmlFound = true;
                    }
                }
                
                if (xmlFound && window.mpaiLogger) {
                    window.mpaiLogger.info(`Successfully processed ${result.blocks.length} XML blocks`, 'ui');
                } else if (window.mpaiLogger) {
                    window.mpaiLogger.warn('XML tags found but processing failed', 'ui');
                }
            }
            
            // Convert markdown lists to HTML lists - this is the key improvement
            
            // Unordered lists with dashes or asterisks
            content = content.replace(/^([\s]*)-[\s]+(.*?)$/gm, '<li>$2</li>');
            content = content.replace(/^([\s]*)\*[\s]+(.*?)$/gm, '<li>$2</li>');
            
            // Wrap consecutive <li> elements with <ul> tags
            content = content.replace(/(<li>.*?<\/li>(\s*\n\s*)?)+/g, function(match) {
                return '<ul class="mpai-list">' + match + '</ul>';
            });
            
            // Ordered lists with numbers
            content = content.replace(/^([\s]*)\d+\.[\s]+(.*?)$/gm, '<li>$2</li>');
            
            // Wrap consecutive <li> elements with <ol> tags but only those not already in <ul>
            content = content.replace(/(?<!<ul class="mpai-list">)(<li>.*?<\/li>(\s*\n\s*)?)+(?!<\/ul>)/g, function(match) {
                return '<ol class="mpai-list">' + match + '</ol>';
            });
            
            // Process tables
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
                    '" style="cursor:pointer;">' + p1.replace(/</g, '&lt;').replace(/>/g, '&gt;') + 
                    ' <span class="mpai-run-indicator" style="color:#0073aa;">▶</span></code>';
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
            
            // End formatting timer
            if (window.mpaiLogger) {
                const elapsed = window.mpaiLogger.endTimer('format_message');
                window.mpaiLogger.debug('Message formatting completed', 'ui', { 
                    timeMs: elapsed,
                    responseLength: content.length,
                    hasCodeBlocks: content.includes('```'),
                    hasInlineCode: content.includes('`'),
                    hasLinks: content.includes('http://') || content.includes('https://'),
                    hasTables: content.includes('|') && content.includes('---')  
                });
            }
            
            return content;
        } catch (error) {
            if (window.mpaiLogger) {
                window.mpaiLogger.error('Error in formatMessage:', 'ui', { 
                    error: error.message, 
                    contentType: typeof content,
                    contentLength: content ? content.length : 0
                });
            } else {
                console.error('Error in formatMessage:', error, 'with content type:', typeof content);
            }
            
            try {
                // Attempt to return the raw content if all formatting fails
                return 'Error formatting message. Raw content: ' + String(content).substring(0, 100) + 
                       (String(content).length > 100 ? '...' : '');
            } catch (e) {
                if (window.mpaiLogger) {
                    window.mpaiLogger.error('Failed to display raw content:', 'ui', e);
                }
                return 'Error formatting message and unable to display raw content.';
            }
        }
    }
    
    /**
     * Format plugin logs result
     * 
     * @param {object} data - The plugin logs data from the direct AJAX handler
     * @return {string} Formatted HTML for the plugin logs
     */
    function formatPluginLogsResult(data) {
        // Log the start of formatting plugin logs
        if (window.mpaiLogger) {
            window.mpaiLogger.startTimer('format_plugin_logs');
            window.mpaiLogger.info('Formatting plugin logs data', 'tool_usage', {
                hasData: !!data,
                dataType: typeof data
            });
        }
        
        // Handle the case where data is undefined or null
        if (!data) {
            if (window.mpaiLogger) {
                window.mpaiLogger.warn('No plugin logs data available for formatting', 'tool_usage');
            }
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
        
        // End timer and log completion
        if (window.mpaiLogger) {
            const elapsed = window.mpaiLogger.endTimer('format_plugin_logs');
            window.mpaiLogger.info('Plugin logs formatting completed', 'tool_usage', {
                timeMs: elapsed,
                logCount: logs.length,
                htmlLength: html.length
            });
        }
        
        return html;
    }
    
    /**
     * Format tabular result data
     *
     * @param {object} resultData - The tabular result data object
     * @return {string} Formatted HTML for the table
     */
    function formatTabularResult(resultData) {
        // Log the start of formatting tabular results
        if (window.mpaiLogger) {
            window.mpaiLogger.startTimer('format_tabular_result');
            window.mpaiLogger.info('Formatting tabular result data', 'tool_usage', {
                hasData: !!resultData,
                commandType: resultData ? (resultData.command_type || 'unknown') : 'none'
            });
        }
        
        if (!resultData || !resultData.result) {
            if (window.mpaiLogger) {
                window.mpaiLogger.warn('No result data available for tabular formatting', 'tool_usage');
            }
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
        
        // End timer and log completion
    if (window.mpaiLogger) {
        const elapsed = window.mpaiLogger.endTimer('format_tabular_result');
        window.mpaiLogger.info('Tabular result formatting completed', 'tool_usage', {
            timeMs: elapsed,
            rowCount: nonEmptyRows.length,
            htmlLength: tableHtml.length,
            commandType: commandType
        });
    }
    
    return tableHtml;
    }
    
    // Public API
    return {
        init: init,
        formatMessage: formatMessage,
        formatPluginLogsResult: formatPluginLogsResult,
        formatTabularResult: formatTabularResult
    };
})(jQuery);

// Expose the module globally
window.MPAI_Formatters = MPAI_Formatters;