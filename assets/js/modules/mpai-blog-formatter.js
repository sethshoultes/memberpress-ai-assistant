/**
 * MemberPress AI Assistant - Blog Formatter Module
 * 
 * Handles XML blog post formatting detection and integration with the chat interface
 */

var MPAI_BlogFormatter = (function($) {
    'use strict';
    
    /**
     * Initialize the module
     */
    function init() {
        if (window.mpaiLogger) {
            window.mpaiLogger.info('Blog Formatter module initialized', 'ui');
        }
        
        // Register event handlers for the "Create blog post" and "Create page" buttons in the chat interface
        $(document).on('click', '.mpai-command-item[data-command*="Create blog post"], .mpai-command-item[data-command*="Create a blog post"]', function(e) {
            e.preventDefault();
            enhanceUserPrompt($(this).data('command'), 'blog-post');
        });
        
        $(document).on('click', '.mpai-command-item[data-command*="Create page"], .mpai-command-item[data-command*="Create a page"]', function(e) {
            e.preventDefault();
            enhanceUserPrompt($(this).data('command'), 'page');
        });
    }
    
    /**
     * Enhance user prompt by adding XML formatting instructions
     * 
     * @param {string} userPrompt - The user's original prompt text
     * @param {string} contentType - The type of content to create ('blog-post' or 'page')
     */
    function enhanceUserPrompt(userPrompt, contentType) {
        // Only enhance if the MPAI_Messages module is available
        if (!window.MPAI_Messages || typeof window.MPAI_Messages.sendMessage !== 'function') {
            if (window.mpaiLogger) {
                window.mpaiLogger.error('Cannot enhance prompt: MPAI_Messages module not available', 'ui');
            }
            return;
        }
        
        // Log the enhancement action
        if (window.mpaiLogger) {
            window.mpaiLogger.info(`Enhancing user prompt for ${contentType} creation`, 'ui', {
                originalPrompt: userPrompt
            });
        }
        
        // For blog posts, add XML formatting instructions
        if (contentType === 'blog-post') {
            const enhancedPrompt = `${userPrompt}

I need you to write a blog post in XML format. This is VERY IMPORTANT - the output MUST be wrapped in XML tags EXACTLY as shown in this example. The format must be exactly like this, with no deviations:

\`\`\`xml
<wp-post>
  <post-title>Title of the blog post</post-title>
  <post-content>
    <block type="paragraph">Introduction paragraph here.</block>
    <block type="heading" level="2">First Section Heading</block>
    <block type="paragraph">Content of the first section.</block>
    <block type="paragraph">Another paragraph with content.</block>
    <block type="heading" level="2">Second Section Heading</block>
    <block type="paragraph">Content for this section.</block>
    <block type="list">
      <item>First list item</item>
      <item>Second list item</item>
      <item>Third list item</item>
    </block>
  </post-content>
  <post-excerpt>A brief summary of the post.</post-excerpt>
  <post-status>draft</post-status>
</wp-post>
\`\`\`

The XML structure is required for proper WordPress integration. IMPORTANT: The opening and closing tags must be exactly <wp-post> and </wp-post>. Please ensure the XML is not inside any additional code blocks or formatting - just keep the exact format shown above, with the same indentation patterns. The content must be complete and well-formed.`;

            // Send the enhanced prompt
            window.MPAI_Messages.sendMessage(enhancedPrompt);
        } 
        // For pages, add similar formatting but adjust the instruction
        else if (contentType === 'page') {
            const enhancedPrompt = `${userPrompt}

I need you to write a page in XML format. This is VERY IMPORTANT - the output MUST be wrapped in XML tags EXACTLY as shown in this example. The format must be exactly like this, with no deviations:

\`\`\`xml
<wp-post>
  <post-title>Title of the page</post-title>
  <post-content>
    <block type="paragraph">Introduction paragraph here.</block>
    <block type="heading" level="2">First Section Heading</block>
    <block type="paragraph">Content of the first section.</block>
    <block type="paragraph">Another paragraph with content.</block>
    <block type="heading" level="2">Second Section Heading</block>
    <block type="paragraph">Content for this section.</block>
    <block type="list">
      <item>First list item</item>
      <item>Second list item</item>
      <item>Third list item</item>
    </block>
  </post-content>
  <post-excerpt>A brief summary of the page.</post-excerpt>
  <post-status>draft</post-status>
  <post-type>page</post-type>
</wp-post>
\`\`\`

The XML structure is required for proper WordPress integration. IMPORTANT: The opening and closing tags must be exactly <wp-post> and </wp-post>. Please ensure the XML is not inside any additional code blocks or formatting - just keep the exact format shown above, with the same indentation patterns. The content must be complete and well-formed.`;

            // Send the enhanced prompt
            window.MPAI_Messages.sendMessage(enhancedPrompt);
        } 
        // For any other content type, just send the original prompt
        else {
            window.MPAI_Messages.sendMessage(userPrompt);
        }
    }
    
    /**
     * Add "Create Post" button to relevant messages
     * 
     * @param {jQuery} $message - The message element
     * @param {string} content - The message content
     */
    function addCreatePostButton($message, content) {
        // Only proceed if the message is from the assistant
        if (!$message.hasClass('mpai-chat-message-assistant')) {
            return;
        }
        
        // Check if the message already has a post preview card
        if ($message.find('.mpai-post-preview-card').length > 0) {
            return;
        }
        
        // Check if content contains XML blog post format (directly or in code blocks)
        let hasXml = false;
        let xmlContent = '';
        
        // Check for XML in code blocks first
        const codeBlockRegex = /```(?:xml)?\s*([\s\S]*?)```/g;
        let match;
        while ((match = codeBlockRegex.exec(content)) !== null) {
            if (match[1] && match[1].includes('<wp-post') && match[1].includes('</wp-post>')) {
                xmlContent = match[1];
                hasXml = true;
                console.log("AddCreatePostButton: Found XML in code block", xmlContent.substring(0, 100) + "...");
                break;
            }
        }
        
        // If not found in code blocks, check for direct XML
        if (!hasXml && content.includes('<wp-post>') && content.includes('</wp-post>')) {
            // Extract the XML content
            const startPos = content.indexOf('<wp-post>');
            const endPos = content.lastIndexOf('</wp-post>') + 10; // 10 = length of "</wp-post>"
            xmlContent = content.substring(startPos, endPos);
            hasXml = true;
            console.log("AddCreatePostButton: Found direct XML", xmlContent.substring(0, 100) + "...");
        }
        
        // Try one more time with a more lenient approach if still not found
        if (!hasXml && content.includes('post-title') && content.includes('post-content')) {
            console.log("Trying lenient XML detection approach");
            // Try to reconstruct the XML structure
            const titleMatch = content.match(/<post-title[^>]*>([\s\S]*?)<\/post-title>/i);
            const contentMatch = content.match(/<post-content[^>]*>([\s\S]*?)<\/post-content>/i);
            
            if (titleMatch && contentMatch) {
                // Reconstruct a minimal valid XML structure
                xmlContent = '<wp-post>\n' +
                             '  ' + content.substring(titleMatch.index, titleMatch.index + titleMatch[0].length) + '\n' +
                             '  ' + content.substring(contentMatch.index, contentMatch.index + contentMatch[0].length) + '\n' +
                             '</wp-post>';
                hasXml = true;
                console.log("AddCreatePostButton: Reconstructed XML", xmlContent.substring(0, 100) + "...");
            }
        }
        
        // If we found XML content
        if (hasXml) {
            // Don't add if buttons are already present
            if ($message.find('.mpai-create-post-button').length > 0) {
                return;
            }
            
            // Extract title and excerpt for a nicer display
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
            
            // Create the preview card
            const $previewCard = $(`
                <div class="mpai-post-preview-card">
                    <div class="mpai-post-preview-header">
                        <div class="mpai-post-preview-type">${postType === "page" ? "Page" : "Blog Post"}</div>
                        <div class="mpai-post-preview-icon">${postType === "page" ? '<span class="dashicons dashicons-page"></span>' : '<span class="dashicons dashicons-admin-post"></span>'}</div>
                    </div>
                    <h3 class="mpai-post-preview-title">${title}</h3>
                    <div class="mpai-post-preview-excerpt">${excerpt}</div>
                    <div class="mpai-post-preview-actions">
                        <button class="mpai-create-post-button" data-content-type="${postType}">
                            Create ${postType === "page" ? "Page" : "Post"}
                        </button>
                        <button class="mpai-toggle-xml-button">View XML</button>
                    </div>
                    <div class="mpai-post-xml-content" style="display:none;">
                        <pre>${xmlContent.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</pre>
                    </div>
                </div>
            `);
            
            // Store the raw XML content for use by the button handler
            $previewCard.data('xml-content', xmlContent);
            
            // Add toggle XML button handler
            $previewCard.find('.mpai-toggle-xml-button').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation(); // Prevent event bubbling
                const $xmlContent = $previewCard.find('.mpai-post-xml-content');
                
                if ($xmlContent.hasClass('show-xml')) {
                    $xmlContent.removeClass('show-xml');
                    $xmlContent.slideUp(200);
                    $(this).text('View XML');
                } else {
                    $xmlContent.addClass('show-xml');
                    $xmlContent.slideDown(200);
                    $(this).text('Hide XML');
                }
            });
            
            // Add create post button handler
            $previewCard.find('.mpai-create-post-button').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation(); // Prevent event bubbling
                const clickedContentType = $(this).data('content-type');
                const actualXmlContent = $previewCard.data('xml-content');
                
                console.log("Create post button clicked");
                console.log("Content type:", clickedContentType);
                console.log("XML content preview:", actualXmlContent.substring(0, 150) + "...");
                
                // Show a loading indicator
                $(this).prop('disabled', true).text('Creating...');
                
                // Use the createPostFromXML function with the raw XML content
                createPostFromXML(actualXmlContent, clickedContentType);
            });
            
            // Add the preview card to the message
            $message.append($previewCard);
        }
    }
    
    /**
     * Create a post from XML content using the WordPress REST API
     * 
     * @param {string} xmlContent - The XML formatted content
     * @param {string} contentType - The type of content ('post' or 'page')
     */
    function createPostFromXML(xmlContent, contentType) {
        // Log the XML content for debugging
        if (window.mpaiLogger) {
            window.mpaiLogger.debug('XML content received', 'tool_usage', {
                contentPreview: xmlContent.substring(0, 150) + "...",
                contentLength: xmlContent.length,
                contentType: contentType
            });
        }
        
        console.log("Creating " + contentType + " with XML content", xmlContent.substring(0, 100) + "...");
        
        // Extract content from XML
        let title = "New " + (contentType === 'page' ? 'Page' : 'Post');
        let content_html = '';
        let excerpt = '';
        
        try {
            // Extract title
            const titleMatch = xmlContent.match(/<post-title[^>]*>([\s\S]*?)<\/post-title>/i);
            if (titleMatch && titleMatch[1]) {
                title = titleMatch[1].trim();
            }
            
            // Extract content and convert blocks to HTML
            const contentMatch = xmlContent.match(/<post-content[^>]*>([\s\S]*?)<\/post-content>/i);
            if (contentMatch && contentMatch[1]) {
                const contentBlocks = contentMatch[1];
                content_html = convertXmlBlocksToHtml(contentBlocks);
            } else {
                // Fallback for when post-content tags aren't found - use the entire xml content
                console.log("No post-content tags found, using entire XML as content");
                if (window.mpaiLogger) {
                    window.mpaiLogger.warn('No post-content tags found, using entire XML as content', 'tool_usage');
                }
                content_html = `<!-- wp:paragraph --><p>${xmlContent.replace(/<[^>]*>/g, ' ').trim()}</p><!-- /wp:paragraph -->`;
            }
            
            // Extract excerpt
            const excerptMatch = xmlContent.match(/<post-excerpt[^>]*>([\s\S]*?)<\/post-excerpt>/i);
            if (excerptMatch && excerptMatch[1]) {
                excerpt = excerptMatch[1].trim();
            }
            
            console.log("Extracted content:", {
                title: title,
                content_preview: content_html.substring(0, 100) + "...",
                excerpt: excerpt
            });
            
            if (window.mpaiLogger) {
                window.mpaiLogger.info('Content extracted from XML', 'tool_usage', {
                    title: title,
                    excerpt: excerpt,
                    contentLength: content_html.length
                });
            }
            
            // Skip REST API check - just use our direct method since we know REST API is failing
            console.log("Skipping REST API and going straight to direct AJAX handler");
            if (window.mpaiLogger) {
                window.mpaiLogger.info('Skipping REST API and going straight to direct AJAX handler', 'api_calls');
            }
            
            // Go directly to our direct AJAX handler
            createPostWithAdminAjax(title, content_html, excerpt, contentType);
        } catch (error) {
            console.error("Error processing XML content:", error);
            
            if (window.mpaiLogger) {
                window.mpaiLogger.error('Error processing XML content', 'tool_usage', {
                    error: error.message,
                    stack: error.stack
                });
            }
            
            alert(`Error processing XML content: ${error.message}`);
        }
    }
    
    /**
     * Convert XML blocks to HTML content
     * 
     * @param {string} xmlBlocks - The XML blocks
     * @return {string} HTML content
     */
    function convertXmlBlocksToHtml(xmlBlocks) {
        let html = '';
        
        try {
            // Process paragraph blocks
            const paragraphRegex = /<block\s+type=["']paragraph["'][^>]*>([\s\S]*?)<\/block>/g;
            let match;
            
            // Replace paragraph blocks with WordPress paragraph blocks
            xmlBlocks = xmlBlocks.replace(paragraphRegex, function(match, content) {
                return `<!-- wp:paragraph --><p>${content.trim()}</p><!-- /wp:paragraph -->`;
            });
            
            // Process heading blocks
            const headingRegex = /<block\s+type=["']heading["']\s+level=["'](\d)["'][^>]*>([\s\S]*?)<\/block>/g;
            xmlBlocks = xmlBlocks.replace(headingRegex, function(match, level, content) {
                return `<!-- wp:heading {"level":${level}} --><h${level}>${content.trim()}</h${level}><!-- /wp:heading -->`;
            });
            
            // Process list blocks
            const listRegex = /<block\s+type=["']list["'][^>]*>([\s\S]*?)<\/block>/g;
            xmlBlocks = xmlBlocks.replace(listRegex, function(match, content) {
                // Extract list items
                const items = [];
                const itemRegex = /<item>([\s\S]*?)<\/item>/g;
                let itemMatch;
                
                while ((itemMatch = itemRegex.exec(content)) !== null) {
                    items.push(`<li>${itemMatch[1].trim()}</li>`);
                }
                
                const listItems = items.join('');
                return `<!-- wp:list --><ul>${listItems}</ul><!-- /wp:list -->`;
            });
            
            // Process ordered list blocks
            const orderedListRegex = /<block\s+type=["']ordered-list["'][^>]*>([\s\S]*?)<\/block>/g;
            xmlBlocks = xmlBlocks.replace(orderedListRegex, function(match, content) {
                // Extract list items
                const items = [];
                const itemRegex = /<item>([\s\S]*?)<\/item>/g;
                let itemMatch;
                
                while ((itemMatch = itemRegex.exec(content)) !== null) {
                    items.push(`<li>${itemMatch[1].trim()}</li>`);
                }
                
                const listItems = items.join('');
                return `<!-- wp:list {"ordered":true} --><ol>${listItems}</ol><!-- /wp:list -->`;
            });
            
            // Process quote blocks
            const quoteRegex = /<block\s+type=["']quote["'][^>]*>([\s\S]*?)<\/block>/g;
            xmlBlocks = xmlBlocks.replace(quoteRegex, function(match, content) {
                return `<!-- wp:quote --><blockquote class="wp-block-quote"><p>${content.trim()}</p></blockquote><!-- /wp:quote -->`;
            });
            
            // Process code blocks
            const codeRegex = /<block\s+type=["']code["'][^>]*>([\s\S]*?)<\/block>/g;
            xmlBlocks = xmlBlocks.replace(codeRegex, function(match, content) {
                return `<!-- wp:code --><pre class="wp-block-code"><code>${content.trim()}</code></pre><!-- /wp:code -->`;
            });
            
            // Check if we've done any transformations by looking for wp:paragraph and other block types
            if (!xmlBlocks.includes('<!-- wp:paragraph -->') &&
                !xmlBlocks.includes('<!-- wp:heading -->') &&
                !xmlBlocks.includes('<!-- wp:list -->') &&
                !xmlBlocks.includes('<!-- wp:quote -->') &&
                !xmlBlocks.includes('<!-- wp:code -->')) {
                
                // No transformations happened - wrap the entire content in a paragraph block
                console.log("No XML blocks were transformed, wrapping as paragraphs");
                
                // Split by new lines and create paragraphs
                const paragraphs = xmlBlocks.split(/\n\s*\n/);
                html = paragraphs.map(p => {
                    if (p.trim()) {
                        // Remove XML tags but preserve content
                        const cleanedText = p.replace(/<[^>]*>/g, ' ').trim();
                        if (cleanedText) {
                            return `<!-- wp:paragraph --><p>${cleanedText}</p><!-- /wp:paragraph -->`;
                        }
                    }
                    return '';
                }).filter(Boolean).join('\n\n');
            } else {
                // Some transformations happened, but we need to preserve the Gutenberg blocks
                // and only remove remaining XML tags
                html = xmlBlocks;
                
                // If there are still XML tags that weren't converted to blocks, remove them
                if (html.includes('<') && html.includes('>')) {
                    // Only remove XML tags that aren't part of HTML or Gutenberg comments
                    const safeHtml = html.replace(/<(?!\!--)(\/?)([^>\s]+)[^>]*>/g, function(match) {
                        // Don't replace HTML tags that are part of Gutenberg blocks
                        if (match.startsWith('<p>') || match.startsWith('</p>') ||
                            match.startsWith('<h') || match.startsWith('</h') ||
                            match.startsWith('<ul>') || match.startsWith('</ul>') ||
                            match.startsWith('<ol>') || match.startsWith('</ol>') ||
                            match.startsWith('<li>') || match.startsWith('</li>') ||
                            match.startsWith('<blockquote') || match.startsWith('</blockquote>') ||
                            match.startsWith('<code>') || match.startsWith('</code>') ||
                            match.startsWith('<pre>') || match.startsWith('</pre>')) {
                            return match;
                        }
                        // Remove other XML tags
                        return ' ';
                    });
                    html = safeHtml;
                }
            }
            
            // If the content is still empty after processing, use a simple paragraph
            if (!html.trim()) {
                html = `<!-- wp:paragraph --><p>Content created with MemberPress AI Assistant</p><!-- /wp:paragraph -->`;
            }
        } catch (error) {
            console.error("Error converting XML blocks to HTML:", error);
            if (window.mpaiLogger) {
                window.mpaiLogger.error('Error converting XML blocks to HTML', 'tool_usage', {
                    error: error.message
                });
            }
            
            // Fallback to simple content
            html = `<!-- wp:paragraph --><p>Content created with MemberPress AI Assistant</p><!-- /wp:paragraph -->`;
        }
        
        return html;
    }
    
    /**
     * Fallback method to create post using direct ajax handler
     * 
     * @param {string} title - The post title
     * @param {string} content - The post content HTML
     * @param {string} excerpt - The post excerpt
     * @param {string} contentType - The content type (post or page)
     */
    function createPostWithAdminAjax(title, content, excerpt, contentType) {
        console.log("Using direct AJAX handler to create " + contentType);
        
        if (window.mpaiLogger) {
            window.mpaiLogger.info(`Using direct AJAX handler to create ${contentType}`, 'api_calls');
        }
        
        // Get the plugin directory URL - falling back to a common WordPress structure if needed
        let pluginUrl = '';
        if (typeof window.mpai_plugin_url !== 'undefined') {
            pluginUrl = window.mpai_plugin_url;
        } else {
            // Try to extract from script tags
            const scriptTags = document.querySelectorAll('script[src*="memberpress-ai-assistant"]');
            if (scriptTags.length > 0) {
                const src = scriptTags[0].getAttribute('src');
                pluginUrl = src.split('/assets/')[0];
            } else {
                // Fallback to a common WordPress structure
                pluginUrl = '/wp-content/plugins/memberpress-ai-assistant';
            }
        }
        
        // Build the direct AJAX handler URL
        const directAjaxUrl = pluginUrl + '/includes/direct-ajax-handler.php';
        console.log("Using direct AJAX URL:", directAjaxUrl);
        
        // Create post with direct AJAX handler
        jQuery.ajax({
            url: directAjaxUrl,
            method: 'POST',
            data: {
                action: 'mpai_create_post',
                post_type: contentType === 'page' ? 'page' : 'post',
                title: title,
                content: content,
                excerpt: excerpt,
                status: 'draft'
            },
            success: function(response) {
                console.log("Post created successfully with direct AJAX:", response);
                
                if (window.mpaiLogger) {
                    window.mpaiLogger.info(`${contentType} created successfully with direct AJAX`, 'api_calls', {
                        response: response
                    });
                }
                
                if (response.success) {
                    alert(`${contentType === 'page' ? 'Page' : 'Post'} created successfully!`);
                } else {
                    alert(`Error creating ${contentType}: ${response.message || 'Unknown error'}`);
                }
            },
            error: function(xhr, status, error) {
                console.error("Error creating post with direct AJAX:", xhr.responseText);
                
                if (window.mpaiLogger) {
                    window.mpaiLogger.error(`Error creating ${contentType} with direct AJAX`, 'api_calls', {
                        status: xhr.status,
                        statusText: xhr.statusText,
                        responseText: xhr.responseText
                    });
                }
                
                // Try using the test_simple action instead as a last resort
                tryUsingSimpleHandler(title, content, excerpt, contentType);
            }
        });
    }
    
    /**
     * Last attempt to create post using the test_simple action in direct-ajax-handler.php
     */
    function tryUsingSimpleHandler(title, content, excerpt, contentType) {
        console.log("Trying test_simple action as last resort");
        
        if (window.mpaiLogger) {
            window.mpaiLogger.info(`Trying test_simple action as last resort`, 'api_calls');
        }
        
        // Get the plugin directory URL - same as above
        let pluginUrl = '';
        if (typeof window.mpai_plugin_url !== 'undefined') {
            pluginUrl = window.mpai_plugin_url;
        } else {
            const scriptTags = document.querySelectorAll('script[src*="memberpress-ai-assistant"]');
            if (scriptTags.length > 0) {
                const src = scriptTags[0].getAttribute('src');
                pluginUrl = src.split('/assets/')[0];
            } else {
                pluginUrl = '/wp-content/plugins/memberpress-ai-assistant';
            }
        }
        
        // Build the direct AJAX handler URL
        const directAjaxUrl = pluginUrl + '/includes/direct-ajax-handler.php';
        
        // Create post with test_simple action
        jQuery.ajax({
            url: directAjaxUrl,
            method: 'POST',
            data: {
                action: 'test_simple',
                wp_api_action: 'create_post',
                content_type: contentType,
                title: title,
                content: content,
                excerpt: excerpt,
                status: 'draft'
            },
            success: function(response) {
                console.log("Post created successfully with test_simple action:", response);
                
                if (window.mpaiLogger) {
                    window.mpaiLogger.info(`${contentType} created with test_simple action`, 'api_calls', {
                        response: response
                    });
                }
                
                if (response.success) {
                    alert(`${contentType === 'page' ? 'Page' : 'Post'} created successfully!`);
                } else {
                    alert(`Error creating ${contentType}: ${response.message || 'Unknown error'}`);
                }
            },
            error: function(xhr, status, error) {
                console.error("Final attempt failed:", xhr.responseText);
                
                if (window.mpaiLogger) {
                    window.mpaiLogger.error(`Final attempt failed`, 'api_calls', {
                        status: xhr.status,
                        statusText: xhr.statusText,
                        responseText: xhr.responseText
                    });
                }
                
                // Now try the manual redirect as absolute last resort
                if (contentType === 'page') {
                    console.log("Trying last resort - redirecting to page creation screen");
                    
                    // Redirect to page creation with title prefilled
                    const redirectUrl = '/wp-admin/post-new.php?post_type=page&post_title=' + encodeURIComponent(title);
                    
                    // Ask the user if they want to be redirected
                    if (confirm(`All API approaches failed. Would you like to be redirected to the WordPress ${contentType} editor to create your ${contentType} manually?`)) {
                        window.location.href = redirectUrl;
                        return;
                    }
                } else {
                    console.log("Trying last resort - redirecting to post creation screen");
                    
                    // Redirect to post creation with title prefilled
                    const redirectUrl = '/wp-admin/post-new.php?post_title=' + encodeURIComponent(title);
                    
                    // Ask the user if they want to be redirected
                    if (confirm(`All API approaches failed. Would you like to be redirected to the WordPress ${contentType} editor to create your ${contentType} manually?`)) {
                        window.location.href = redirectUrl;
                        return;
                    }
                }
                
                alert(`Error creating ${contentType}: ${xhr.statusText || 'Unknown error'}. Please try again later.`);
            }
        });
    }
    
    /**
     * Process assistant message to add create post buttons
     * 
     * @param {jQuery} $message - The message element
     * @param {string} content - The message content
     */
    function processAssistantMessage($message, content) {
        if (!$message || !content) {
            return;
        }
        
        // Add create post buttons
        addCreatePostButton($message, content);
    }
    
    // Public API
    return {
        init: init,
        enhanceUserPrompt: enhanceUserPrompt,
        processAssistantMessage: processAssistantMessage,
        createPostFromXML: createPostFromXML
    };
})(jQuery);

// Expose the module globally
window.MPAI_BlogFormatter = MPAI_BlogFormatter;