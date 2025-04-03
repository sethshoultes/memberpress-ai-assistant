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
        
        // Check if the content contains XML blog post format
        if (content.includes('<wp-post>') && content.includes('</wp-post>')) {
            // Don't add if buttons are already present
            if ($message.find('.mpai-create-post-button').length > 0) {
                return;
            }
            
            // Create a button container
            const $buttonContainer = $('<div>', {
                'class': 'mpai-content-actions'
            });
            
            // Determine if this is a blog post or a page
            const isPage = content.toLowerCase().includes('<post-type>page</post-type>') || 
                          (content.toLowerCase().includes('page') && 
                           !content.toLowerCase().includes('post'));
            
            // Create button
            const $createButton = $('<button>', {
                'class': 'mpai-create-post-button',
                'data-content-type': isPage ? 'page' : 'post'
            }).text(isPage ? 'Create Page' : 'Create Post');
            
            // Add click handler to execute wp_api tool
            $createButton.on('click', function() {
                createPostFromXML(content, $(this).data('content-type'));
            });
            
            // Add button to container
            $buttonContainer.append($createButton);
            
            // Add container to message
            $message.append($buttonContainer);
        }
    }
    
    /**
     * Create a post from XML content using the wp_api tool
     * 
     * @param {string} content - The XML formatted content
     * @param {string} contentType - The type of content ('post' or 'page')
     */
    function createPostFromXML(content, contentType) {
        // CRITICAL ERROR DEBUG - Log the raw content
        console.log("RAW CONTENT FOR POST CREATION:", content);
        console.log("CONTENT TYPE:", contentType);
        console.log("CONTENT LENGTH:", content.length);
        
        // Log the action
        if (window.mpaiLogger) {
            window.mpaiLogger.info(`Creating ${contentType} from XML content`, 'tool_usage', {
                contentType: contentType,
                xmlContentLength: content.length
            });
        }
        
        // Check for empty content
        if (!content || content.trim() === '') {
            alert('No content provided. Please try again.');
            if (window.mpaiLogger) {
                window.mpaiLogger.error('Empty content provided to createPostFromXML', 'tool_usage');
            }
            return;
        }
        
        // Check for code block format and extract
        const codeBlockRegex = /```(?:xml)?\s*([\s\S]*?)```/g;
        let codeBlockMatch;
        while ((codeBlockMatch = codeBlockRegex.exec(content)) !== null) {
            if (codeBlockMatch[1] && codeBlockMatch[1].includes('<wp-post') && codeBlockMatch[1].includes('</wp-post>')) {
                content = codeBlockMatch[1]; // Use the content from inside the code block
                if (window.mpaiLogger) {
                    window.mpaiLogger.info('Extracted XML from code block', 'tool_usage', { length: content.length });
                }
                console.log("EXTRACTED FROM CODE BLOCK:", content);
                break;
            }
        }
        
        // Handle HTML-escaped content
        if (content.includes('&lt;wp-post') && content.includes('&lt;/wp-post&gt;')) {
            if (window.mpaiLogger) {
                window.mpaiLogger.info('Detected HTML-escaped XML content, unescaping', 'tool_usage');
            }
            
            // Create a temporary div to unescape the HTML
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = content;
            content = tempDiv.textContent || tempDiv.innerText || '';
            
            console.log("AFTER HTML UNESCAPING:", content);
            
            if (window.mpaiLogger) {
                window.mpaiLogger.debug('Unescaped content length: ' + content.length, 'tool_usage');
            }
        }
        
        // Extreme fallback - if content doesn't have wp-post tags, wrap it
        if (!content.includes('<wp-post') || !content.includes('</wp-post>')) {
            console.log("NO WP-POST TAGS FOUND, WRAPPING CONTENT");
            
            // Check if it looks like an XML fragment with post-title and post-content
            if (content.includes('<post-title>') && content.includes('<post-content>')) {
                content = '<wp-post>\n' + content + '\n</wp-post>';
                console.log("WRAPPED XML FRAGMENT:", content);
                if (window.mpaiLogger) {
                    window.mpaiLogger.info('Found XML fragment, wrapped in wp-post tags', 'tool_usage');
                }
            }
        }
        
        // Extract the XML section
        let xmlContent = '';
        
        // SIMPLER APPROACH: Just find from first <wp-post to last </wp-post>
        const startPos = content.indexOf('<wp-post');
        const endPos = content.lastIndexOf('</wp-post>');
        
        if (startPos >= 0 && endPos > startPos) {
            xmlContent = content.substring(startPos, endPos + 10); // 10 = length of </wp-post>
            console.log("EXTRACTED WITH SIMPLE APPROACH:", xmlContent);
            if (window.mpaiLogger) {
                window.mpaiLogger.info('Extracted XML with simple position-based approach', 'tool_usage', {
                    startPos: startPos,
                    endPos: endPos,
                    length: xmlContent.length
                });
            }
        } else {
            // Last desperate attempt - just use the whole content and hope it works
            console.log("LAST RESORT: USING ENTIRE CONTENT");
            xmlContent = content;
            if (window.mpaiLogger) {
                window.mpaiLogger.warn('Using entire content as XML', 'tool_usage');
            }
        }
        
        // If we still don't have valid XML content, show error
        if (!xmlContent.includes('<wp-post') || !xmlContent.includes('</wp-post>')) {
            console.log("FINAL CONTENT DOESN'T HAVE WP-POST TAGS:", xmlContent);
            if (window.mpaiLogger) {
                window.mpaiLogger.error('Failed to extract valid XML with wp-post tags', 'tool_usage');
            }
            
            // Show a more detailed error message
            const errorMessage = 'Could not find properly formatted XML content. XML content must be wrapped in <wp-post> tags and follow the correct format. Please try again or ask the AI to format the response as XML.';
            alert(errorMessage);
            return;
        }
        
        // Log the XML content for debugging
        if (window.mpaiLogger) {
            window.mpaiLogger.debug('Extracted XML content', 'tool_usage', {
                xmlContentPreview: xmlContent.substring(0, 150) + (xmlContent.length > 150 ? '...' : '')
            });
        }
        
        // Execute the wp_api tool to create the post using the ajax-direct handler for reliability
        // This bypasses potential permission issues with admin-ajax.php
        const ajaxUrl = '/wp-content/plugins/memberpress-ai-assistant/includes/direct-ajax-handler.php';
        
        $.ajax({
            type: 'POST',
            url: ajaxUrl,
            data: {
                action: 'test_simple',
                is_update_message: 'false',
                wp_api_action: 'create_post',
                content: xmlContent,
                content_type: contentType
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Show success message
                    alert(`The ${contentType} was created successfully! You can edit it in the WordPress admin.`);
                    
                    // Log success
                    if (window.mpaiLogger) {
                        window.mpaiLogger.info(`Successfully created ${contentType}`, 'tool_usage', {
                            postId: response.post_id || 'unknown'
                        });
                    }
                    
                    // If there's an edit URL, offer to open it
                    if (response.edit_url) {
                        if (confirm(`Would you like to edit the ${contentType} now?`)) {
                            window.open(response.edit_url, '_blank');
                        }
                    }
                } else {
                    // Log and display error
                    if (window.mpaiLogger) {
                        window.mpaiLogger.error('Error creating post', 'tool_usage', {
                            error: response.message || 'Unknown error'
                        });
                    }
                    alert(`An error occurred while creating the ${contentType}: ${response.message || 'Unknown error'}`);
                }
            },
            error: function(xhr, status, error) {
                // Fallback to standard WP API tool if direct handler fails
                if (window.mpaiLogger) {
                    window.mpaiLogger.warn('Direct AJAX handler failed, falling back to WP API tool', 'tool_usage', {
                        status: status,
                        error: error
                    });
                }
                
                // Execute the wp_api tool to create the post
                if (window.MPAI_Tools && typeof window.MPAI_Tools.executeToolCall === 'function') {
                    const toolId = 'mpai-tool-' + Date.now() + '-' + Math.floor(Math.random() * 1000);
                    const parameters = {
                        action: contentType === 'page' ? 'create_page' : 'create_post',
                        content: xmlContent,
                        status: 'draft'
                    };
                    
                    try {
                        // Execute the tool
                        window.MPAI_Tools.executeToolCall('wp_api', parameters, toolId);
                        
                        // Log success
                        if (window.mpaiLogger) {
                            window.mpaiLogger.info(`Fallback: initiated ${contentType} creation with WP API tool`, 'tool_usage');
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