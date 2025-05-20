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
        console.log('[MPAI Debug] Blog Formatter module initialized');
        
        // Register event handlers for the "Create blog post" and "Create page" buttons in the chat interface
        // Use namespaced events to prevent duplicate handlers
        $(document).off('click.mpai', '.mpai-command-item[data-command*="Create blog post"], .mpai-command-item[data-command*="Create a blog post"]')
            .on('click.mpai', '.mpai-command-item[data-command*="Create blog post"], .mpai-command-item[data-command*="Create a blog post"]', function(e) {
                e.preventDefault();
                console.log('[MPAI Debug] Blog post command clicked');
                enhanceUserPrompt($(this).data('command'), 'blog-post');
            });
        
        $(document).off('click.mpai', '.mpai-command-item[data-command*="Create page"], .mpai-command-item[data-command*="Create a page"]')
            .on('click.mpai', '.mpai-command-item[data-command*="Create page"], .mpai-command-item[data-command*="Create a page"]', function(e) {
                e.preventDefault();
                enhanceUserPrompt($(this).data('command'), 'page');
            });
        
        // Add event handlers for all buttons using event delegation with namespace
        $(document).off('click.mpai', '.mpai-toggle-xml-button').on('click.mpai', '.mpai-toggle-xml-button', function(e) {
            e.preventDefault();
            e.stopPropagation(); // Prevent event bubbling
            const $xmlContent = $(this).closest('.mpai-post-preview-card').find('.mpai-post-xml-content');
            
            if ($xmlContent.is(':visible')) {
                $xmlContent.slideUp(200);
                $(this).text('View XML');
            } else {
                $xmlContent.slideDown(200);
                $(this).text('Hide XML');
            }
        });
// Use a namespace for the event to prevent duplicate handlers
        $(document).off('click.mpai', '.mpai-create-post-button').on('click.mpai', '.mpai-create-post-button', function(e) {
            e.preventDefault();
            e.stopPropagation(); // Prevent event bubbling
            
            // Check if this button is already disabled (to prevent double-clicks)
            if ($(this).prop('disabled')) {
                console.log("[MPAI Debug] Button already disabled, ignoring click");
                return;
            }
            
            const clickedContentType = $(this).data('content-type');
            const $card = $(this).closest('.mpai-post-preview-card');
            const xmlContent = $card.find('.mpai-post-xml-content pre').text();
            
            console.log("[MPAI Debug] Create post button clicked");
            console.log("[MPAI Debug] Content type:", clickedContentType);
            console.log("[MPAI Debug] XML content preview:", xmlContent.substring(0, 150) + "...");
            
            // Show a loading indicator
            $(this).prop('disabled', true).text('Creating...');
            
            // Use the createPostFromXML function with the raw XML content
            createPostFromXML(xmlContent, clickedContentType);
        });
        
        // Add event handler for preview button using event delegation with namespace
        $(document).off('click.mpai', '.mpai-preview-post-button').on('click.mpai', '.mpai-preview-post-button', function(e) {
            e.preventDefault();
            e.stopPropagation(); // Prevent event bubbling
            
            console.log("[MPAI Debug] Preview button clicked");
            
            const $card = $(this).closest('.mpai-post-preview-card');
            const $previewContent = $card.find('.mpai-post-preview-content');
            const $previewContainer = $card.find('.mpai-post-preview-container');
            
            // Get XML content from the pre element
            const xmlContent = $card.find('.mpai-post-xml-content pre').text();
            
            if (!xmlContent) {
                console.error("[MPAI Debug] No XML content found");
                alert("No XML content found. Cannot generate preview.");
                return;
            }
            
            if ($previewContent.hasClass('show-preview')) {
                // Hide preview
                $previewContent.removeClass('show-preview');
                $previewContent.slideUp(200);
                $(this).text('Preview');
            } else {
                // Show preview
                // Generate HTML preview from XML content
                try {
                    // Extract content from XML
                    const contentMatch = xmlContent.match(/<post-content[^>]*>([\s\S]*?)<\/post-content>/i);
                    if (contentMatch && contentMatch[1]) {
                        const contentBlocks = contentMatch[1];
                        console.log("[MPAI Debug] Content blocks found:", contentBlocks.substring(0, 100) + "...");
                        const previewHtml = convertXmlBlocksToHtml(contentBlocks);
                        
                        // Add the formatted HTML to the preview container
                        $previewContainer.html(previewHtml);
                        
                        // Show the preview
                        $previewContent.addClass('show-preview');
                        $previewContent.slideDown(200);
                        $(this).text('Hide Preview');
                    } else {
                        console.error("[MPAI Debug] No post content found in XML");
                        alert("Could not generate preview: No post content found.");
                    }
                } catch (error) {
                    console.error("[MPAI Debug] Error generating preview:", error);
                    alert(`Error generating preview: ${error.message}`);
                }
            }
// Process any existing XML content on page load
        setTimeout(function() {
            console.log("[MPAI Debug] Checking for XML content on page load");
            $('.mpai-chat-message-assistant').each(function() {
                const $message = $(this);
                
                // Skip messages that already have a post preview card
                if ($message.find('.mpai-post-preview-card').length > 0) {
                    return;
                }
                
                const content = $message.find('.mpai-chat-message-content').html();
                
                if (content && (
                    content.includes('<wp-post>') ||
                    content.includes('</wp-post>') ||
                    content.includes('<post-title>') ||
                    content.includes('</post-title>') ||
                    content.includes('<post-content>') ||
                    content.includes('</post-content>')
                )) {
                    console.log("[MPAI Debug] Found XML content in message, processing");
                    processAssistantMessage($message, content);
                }
            });
            
            // Set up a mutation observer to watch for new messages
            setupMutationObserver();
        }, 500); // Short delay to ensure DOM is ready
    }
    
    /**
     * Enhance user prompt by adding XML formatting instructions
     * 
     * @param {string} userPrompt - The user's original prompt text
     * @param {string} contentType - The type of content to create ('blog-post' or 'page')
     */
    function enhanceUserPrompt(userPrompt, contentType) {
        // Only enhance if the chat interface is available
        if (!window.mpaiChat) {
            console.error('[MPAI Debug] Cannot enhance prompt: mpaiChat not available');
            
            // Try to find the chat input and submit button directly
            const chatInput = document.getElementById('mpai-chat-input');
            const submitButton = document.getElementById('mpai-chat-submit');
            
            if (chatInput && submitButton) {
                console.log('[MPAI Debug] Found chat input and submit button directly');
                enhanceAndSubmitDirectly(userPrompt, contentType, chatInput, submitButton);
                return;
            }
            
/**
     * Enhance and submit directly when mpaiChat is not available
     * 
     * @param {string} userPrompt - The user's original prompt text
     * @param {string} contentType - The type of content to create ('blog-post' or 'page')
     * @param {HTMLElement} chatInput - The chat input element
     * @param {HTMLElement} submitButton - The submit button element
     */
    function enhanceAndSubmitDirectly(userPrompt, contentType, chatInput, submitButton) {
        console.log(`[MPAI Debug] Enhancing and submitting directly for ${contentType} creation`);
        
        let enhancedPrompt = '';
        
        // For blog posts, add XML formatting instructions
        if (contentType === 'blog-post') {
            enhancedPrompt = `${userPrompt}

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
        } 
        // For pages, add similar formatting but adjust the instruction
        else if (contentType === 'page') {
            enhancedPrompt = `${userPrompt}

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
        } 
        // For any other content type, just use the original prompt
        else {
            enhancedPrompt = userPrompt;
        }
        
        // Set the enhanced prompt in the input field
        chatInput.value = enhancedPrompt;
        
        // Trigger a click on the submit button
        submitButton.click();
        
        console.log('[MPAI Debug] Enhanced prompt submitted directly');
    }
            return;
        }
        
        console.log(`[MPAI Debug] Enhancing user prompt for ${contentType} creation`);
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

            // Send the enhanced prompt using the chat interface
            window.mpaiChat.elements.input.value = enhancedPrompt;
            window.mpaiChat.sendMessage();
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

            // Send the enhanced prompt using the chat interface
            window.mpaiChat.elements.input.value = enhancedPrompt;
            window.mpaiChat.sendMessage();
        } 
        // For any other content type, just send the original prompt
        else {
            window.mpaiChat.elements.input.value = userPrompt;
            window.mpaiChat.sendMessage();
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
                console.log("[MPAI Debug] Found XML in code block", xmlContent.substring(0, 100) + "...");
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
            console.log("[MPAI Debug] Found direct XML", xmlContent.substring(0, 100) + "...");
        }
// Try one more time with a more lenient approach if still not found
        if (!hasXml && content.includes('post-title') && content.includes('post-content')) {
            console.log("[MPAI Debug] Trying lenient XML detection approach");
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
                console.log("[MPAI Debug] Reconstructed XML", xmlContent.substring(0, 100) + "...");
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
            
            // Create the preview card with a more reliable approach
            const previewCardHtml = '<div class="mpai-post-preview-card">' +
                '<div class="mpai-post-preview-header">' +
                    '<div class="mpai-post-preview-type">' + (postType === "page" ? "Page" : "Blog Post") + '</div>' +
                    '<div class="mpai-post-preview-icon">' + (postType === "page" ? '<span class="dashicons dashicons-page"></span>' : '<span class="dashicons dashicons-admin-post"></span>') + '</div>' +
                '</div>' +
                '<h3 class="mpai-post-preview-title">' + title + '</h3>' +
                '<div class="mpai-post-preview-excerpt">' + excerpt + '</div>' +
                '<div class="mpai-post-preview-actions">' +
                    '<button class="mpai-create-post-button" data-content-type="' + postType + '">' +
                        'Create ' + (postType === "page" ? "Page" : "Post") +
                    '</button>' +
                    '<button class="mpai-preview-post-button">Preview</button>' +
                    '<button class="mpai-toggle-xml-button">View XML</button>' +
                '</div>' +
                '<div class="mpai-post-xml-content" style="display:none;">' +
                    '<pre>' + xmlContent.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</pre>' +
                '</div>' +
                '<div class="mpai-post-preview-content" style="display:none;">' +
                    '<div class="mpai-post-preview-container"></div>' +
                '</div>' +
            '</div>';
            
            // Create jQuery object from HTML
            const $previewCard = $(previewCardHtml);
            
            // Log for debugging
            console.log("[MPAI Debug] Created preview card with XML content length:", xmlContent.length);
            
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
    // Track post creation to prevent duplicates
    let isCreatingPost = false;
function createPostFromXML(xmlContent, contentType) {
        // Prevent duplicate post creation
        if (isCreatingPost) {
            console.log("[MPAI Debug] Post creation already in progress, ignoring duplicate request");
            return;
        }
        
        // Set flag to prevent duplicate creation
        isCreatingPost = true;
        
        console.log("[MPAI Debug] Creating " + contentType + " with XML content", xmlContent.substring(0, 100) + "...");
        
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
                console.log("[MPAI Debug] No post-content tags found, using entire XML as content");
                content_html = `<!-- wp:paragraph --><p>${xmlContent.replace(/<[^>]*>/g, ' ').trim()}</p><!-- /wp:paragraph -->`;
            }
            
            // Extract excerpt
            const excerptMatch = xmlContent.match(/<post-excerpt[^>]*>([\s\S]*?)<\/post-excerpt>/i);
            if (excerptMatch && excerptMatch[1]) {
                excerpt = excerptMatch[1].trim();
            }
            
            console.log("[MPAI Debug] Extracted content:", {
                title: title,
                content_preview: content_html.substring(0, 100) + "...",
                excerpt: excerpt
            });
            
            // Create post with WordPress REST API
            createPostWithRestAPI(title, content_html, excerpt, contentType);
        } catch (error) {
            console.error("[MPAI Debug] Error processing XML content:", error);
            alert(`Error processing XML content: ${error.message}`);
            
            // Reset the flag to allow future post creation
            isCreatingPost = false;
        }
    }
/**
     * Create a post using the WordPress REST API
     * 
     * @param {string} title - The post title
     * @param {string} content - The post content HTML
     * @param {string} excerpt - The post excerpt
     * @param {string} contentType - The content type (post or page)
     */
    function createPostWithRestAPI(title, content, excerpt, contentType) {
        // Get the REST API URL
        const apiUrl = window.wpApiSettings ? window.wpApiSettings.root : '/wp-json';
        const restUrl = `${apiUrl}wp/v2/${contentType === 'page' ? 'pages' : 'posts'}`;
        
        // Get the nonce
        const nonce = window.wpApiSettings ? window.wpApiSettings.nonce : '';
        
        console.log("[MPAI Debug] Creating post with REST API:", {
            url: restUrl,
            title: title,
            contentPreview: content.substring(0, 100) + "..."
        });
        
        // Create the post data
        const postData = {
            title: title,
            content: content,
            excerpt: excerpt,
            status: 'draft'
        };
        
        // Send the request
        jQuery.ajax({
            url: restUrl,
            method: 'POST',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', nonce);
            },
            data: postData,
            success: function(response) {
                console.log("[MPAI Debug] Post created successfully:", response);
                
                // Reset the flag to allow future post creation
                isCreatingPost = false;
                
                // Show success message with edit link
                const editUrl = response.link ? response.link.replace(/\/$/, '') + '/edit' : '';
                const successMessage = editUrl ? 
                    `${contentType === 'page' ? 'Page' : 'Post'} created successfully! <a href="${editUrl}" target="_blank">Edit ${contentType}</a>` :
                    `${contentType === 'page' ? 'Page' : 'Post'} created successfully!`;
                
                alert(successMessage);
                
                // Update button text
                $('.mpai-create-post-button').prop('disabled', false).text(`Create ${contentType === 'page' ? 'Page' : 'Post'}`);
            },
            error: function(xhr, status, error) {
                console.error("[MPAI Debug] Error creating post with REST API:", xhr.responseText);
                
                // Reset the flag to allow future post creation
                isCreatingPost = false;
                
                // Try using admin-ajax.php as a fallback
                createPostWithAdminAjax(title, content, excerpt, contentType);
            }
        });
    }
/**
     * Fallback method to create post using admin-ajax.php
     * 
     * @param {string} title - The post title
     * @param {string} content - The post content HTML
     * @param {string} excerpt - The post excerpt
     * @param {string} contentType - The content type (post or page)
     */
    function createPostWithAdminAjax(title, content, excerpt, contentType) {
        console.log("[MPAI Debug] Using admin-ajax.php to create " + contentType);
        
        // Create post with admin-ajax.php
        jQuery.ajax({
            url: ajaxurl || '/wp-admin/admin-ajax.php',
            method: 'POST',
            data: {
                action: 'mpai_create_post',
                post_type: contentType === 'page' ? 'page' : 'post',
                title: title,
                content: content,
                excerpt: excerpt,
                status: 'draft',
                nonce: window.mpai_nonce || ''
            },
            success: function(response) {
                console.log("[MPAI Debug] Post created successfully with admin-ajax:", response);
                
                // Reset the flag to allow future post creation
                isCreatingPost = false;
                
                if (response.success) {
                    // Show success message with edit link
                    const editUrl = response.data && response.data.edit_url ? response.data.edit_url : '';
                    const successMessage = editUrl ? 
                        `${contentType === 'page' ? 'Page' : 'Post'} created successfully! <a href="${editUrl}" target="_blank">Edit ${contentType}</a>` :
                        `${contentType === 'page' ? 'Page' : 'Post'} created successfully!`;
                    
                    alert(successMessage);
                } else {
                    alert(`Error creating ${contentType}: ${response.data && response.data.message ? response.data.message : 'Unknown error'}`);
                }
                
                // Update button text
                $('.mpai-create-post-button').prop('disabled', false).text(`Create ${contentType === 'page' ? 'Page' : 'Post'}`);
            },
            error: function(xhr, status, error) {
                console.error("[MPAI Debug] Error creating post with admin-ajax:", xhr.responseText);
                
                // Reset the flag to allow future post creation
                isCreatingPost = false;
                
                // Show error message
                alert(`Error creating ${contentType}: ${error}`);
                
                // Update button text
                $('.mpai-create-post-button').prop('disabled', false).text(`Create ${contentType === 'page' ? 'Page' : 'Post'}`);
            }
        });
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
                console.log("[MPAI Debug] No XML blocks were transformed, wrapping as paragraphs");
                
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
            console.error("[MPAI Debug] Error converting XML blocks to HTML:", error);
            
            // Fallback to simple content
            html = `<!-- wp:paragraph --><p>Content created with MemberPress AI Assistant</p><!-- /wp:paragraph -->`;
        }
        
        return html;
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
        
        // Add create post button
        addCreatePostButton($message, content);
    }
    
    /**
     * Set up a mutation observer to watch for new messages with XML content
     */
    function setupMutationObserver() {
        console.log("[MPAI Debug] Setting up mutation observer for blog formatter");
        
        const config = { childList: true, subtree: true, characterData: true };
        
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length) {
                    // Check if any added nodes are messages or contain messages
                    Array.from(mutation.addedNodes).forEach(function(node) {
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            // Check if this is a message element
                            if ($(node).hasClass('mpai-chat-message-assistant')) {
                                const $message = $(node);
                                
                                // Skip if already processed
                                if ($message.find('.mpai-post-preview-card').length > 0) {
                                    return;
                                }
                                
                                const content = $message.find('.mpai-chat-message-content').html();
                                
                                if (content && (
                                    content.includes('<wp-post>') ||
                                    content.includes('</wp-post>') ||
                                    content.includes('<post-title>') ||
                                    content.includes('</post-title>') ||
                                    content.includes('<post-content>') ||
                                    content.includes('</post-content>')
                                )) {
                                    console.log("[MPAI Debug] Found XML content in new message, processing");
                                    processAssistantMessage($message, content);
                                }
                            }
                            // Check if this contains message elements
                            else {
                                const $messages = $(node).find('.mpai-chat-message-assistant');
                                
                                if ($messages.length) {
                                    $messages.each(function() {
                                        const $message = $(this);
                                        
                                        // Skip if already processed
                                        if ($message.find('.mpai-post-preview-card').length > 0) {
                                            return;
                                        }
                                        
                                        const content = $message.find('.mpai-chat-message-content').html();
                                        
                                        if (content && (
                                            content.includes('<wp-post>') ||
                                            content.includes('</wp-post>') ||
                                            content.includes('<post-title>') ||
                                            content.includes('</post-title>') ||
                                            content.includes('<post-content>') ||
                                            content.includes('</post-content>')
                                        )) {
                                            console.log("[MPAI Debug] Found XML content in new message, processing");
                                            processAssistantMessage($message, content);
                                        }
                                    });
                                }
                            }
                        }
                    });
                }
            });
        });
        
        // Start observing the chat messages container
        const chatMessages = $('.mpai-messages');
        if (chatMessages.length) {
            observer.observe(chatMessages[0], config);
            console.log("[MPAI Debug] Mutation observer started for blog formatter");
        } else {
            console.log("[MPAI Debug] Chat messages container not found, mutation observer not started");
        }
    }
    
    // Public API
    return {
        init: init,
        enhanceUserPrompt: enhanceUserPrompt,
        processAssistantMessage: processAssistantMessage,
        createPostFromXML: createPostFromXML,
        convertXmlBlocksToHtml: convertXmlBlocksToHtml,
        setupMutationObserver: setupMutationObserver
    };
})(jQuery);

// Expose the module globally
window.MPAI_BlogFormatter = MPAI_BlogFormatter;

// Initialize the module when the DOM is ready
jQuery(document).ready(function() {
    if (window.MPAI_BlogFormatter) {
        window.MPAI_BlogFormatter.init();
        console.log("[MPAI Debug] Blog formatter initialized on document ready");
    }
});
        });