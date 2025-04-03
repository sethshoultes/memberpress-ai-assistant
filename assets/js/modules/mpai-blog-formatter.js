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
                const $xmlContent = $previewCard.find('.mpai-post-xml-content');
                
                if ($xmlContent.is(':visible')) {
                    $xmlContent.slideUp(200);
                    $(this).text('View XML');
                } else {
                    $xmlContent.slideDown(200);
                    $(this).text('Hide XML');
                }
            });
            
            // Add create post button handler
            $previewCard.find('.mpai-create-post-button').on('click', function(e) {
                e.preventDefault();
                const clickedContentType = $(this).data('content-type');
                const actualXmlContent = $previewCard.data('xml-content');
                
                console.log("Create post button clicked");
                console.log("Content type:", clickedContentType);
                console.log("XML content preview:", actualXmlContent.substring(0, 150) + "...");
                
                // Use the createPostFromXML function with the raw XML content
                createPostFromXML(actualXmlContent, clickedContentType);
            });
            
            // Add the preview card to the message
            $message.append($previewCard);
        }
    }
    
    /**
     * Create a post from XML content using the wp_api tool
     * 
     * @param {string} content - The XML formatted content
     * @param {string} contentType - The type of content ('post' or 'page')
     */
    function createPostFromXML(content, contentType) {
        // Super simple approach - construct a hardcoded XML to see if it works
        const testXml = `<wp-post>
  <post-title>Test Post Title</post-title>
  <post-content>
    <block type="paragraph">This is a test paragraph.</block>
  </post-content>
  <post-excerpt>Test excerpt</post-excerpt>
  <post-status>draft</post-status>
</wp-post>`;

        // Execute the wp_api tool to create the post
        if (window.MPAI_Tools && typeof window.MPAI_Tools.executeToolCall === 'function') {
            const toolId = 'mpai-tool-' + Date.now() + '-' + Math.floor(Math.random() * 1000);
            const parameters = {
                action: contentType === 'page' ? 'create_page' : 'create_post',
                content: testXml,
                status: 'draft',
                title: 'Test Post Title'
            };
            
            try {
                // Execute the tool
                window.MPAI_Tools.executeToolCall('wp_api', parameters, toolId);
                
                // Log success
                if (window.mpaiLogger) {
                    window.mpaiLogger.info(`Successfully initiated ${contentType} creation`, 'tool_usage');
                }
            } catch (error) {
                // Log and display error
                if (window.mpaiLogger) {
                    window.mpaiLogger.error(`Error executing tool: ${error.message}`, 'tool_usage');
                }
                alert(`An error occurred while creating the ${contentType}: ${error.message}`);
            }
        } else {
            if (window.mpaiLogger) {
                window.mpaiLogger.error('Cannot create post: MPAI_Tools module not available', 'tool_usage');
            }
            alert('Cannot create post: The tools module is not available. Please try again later.');
        }
        
        // Log the XML content for debugging
        if (window.mpaiLogger) {
            window.mpaiLogger.debug('Using hardcoded XML content', 'tool_usage', {
                testXml: testXml
            });
        }
        
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