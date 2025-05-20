/**
 * MemberPress AI Assistant - Blog Formatter Module
 * 
 * Handles formatting and processing of blog post XML content
 */
(function($) {
    'use strict';

    // Create a global reference to this module
    window.MPAI_BlogFormatter = {};
    
    // Initialize the module
    function init() {
        console.log('[MPAI Debug] Blog Formatter module initializing');
        
        // Process any existing messages that might contain blog post XML
        processExistingMessages();
        
        // Set up a mutation observer to watch for new messages
        setupMutationObserver();
        
        console.log('[MPAI Debug] Blog Formatter module initialized');
    }
    
    /**
     * Set up a mutation observer to watch for new messages
     */
    function setupMutationObserver() {
        // Create a mutation observer to watch for new messages
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    // Check each added node
                    mutation.addedNodes.forEach(function(node) {
                        // If it's an element node
                        if (node.nodeType === 1) {
                            // Check if it's a message or contains messages
                            const $messages = $(node).find('.mpai-chat-message-assistant');
                            if ($messages.length > 0 || $(node).hasClass('mpai-chat-message-assistant')) {
                                // Process the messages
                                processExistingMessages();
                            }
                        }
                    });
                }
            });
        });
        
        // Start observing the chat container
        const chatContainer = document.querySelector('.mpai-chat-messages');
        if (chatContainer) {
            observer.observe(chatContainer, { childList: true, subtree: true });
            console.log('[MPAI Debug] Mutation observer set up for chat container');
        } else {
            console.log('[MPAI Debug] Chat container not found, will try again later');
            // Try again later
            setTimeout(setupMutationObserver, 1000);
        }
    }
    
    /**
     * Process any existing messages that might contain blog post XML
     */
    function processExistingMessages() {
        console.log('[MPAI Debug] Processing existing messages for blog post XML');
        
        $('.mpai-chat-message-assistant').each(function() {
            const $message = $(this);
            const $content = $message.find('.mpai-chat-message-content');
            
            // Skip if already processed
            if ($message.hasClass('mpai-blog-post-processed')) {
                return;
            }
            
            // Mark as processed to avoid duplicate processing
            $message.addClass('mpai-blog-post-processed');
            
            // Get the content
            const content = $content.text();
            
            // Check if it contains blog post XML
            if (content && (
                content.includes('<wp-post>') ||
                content.includes('</wp-post>') ||
                content.includes('<post-title>') ||
                content.includes('</post-title>') ||
                content.includes('<post-content>') ||
                content.includes('</post-content>')
            )) {
                console.log('[MPAI Debug] Found blog post XML in message');
                processAssistantMessage($message, content);
            }
        });
    }
    
    /**
     * Process an assistant message that contains blog post XML
     * 
     * @param {jQuery} $message - The message element
     * @param {string} content - The message content
     */
    function processAssistantMessage($message, content) {
        console.log('[MPAI Debug] Processing assistant message with blog post XML');
        
        // Extract the XML content
        const xmlContent = extractXmlContent(content);
        
        if (!xmlContent) {
            console.error('[MPAI Debug] Failed to extract XML content from message');
            return;
        }
        
        console.log('[MPAI Debug] Extracted XML content:', xmlContent.substring(0, 100) + '...');
        
        // Parse the XML content
        const postData = parsePostXml(xmlContent);
        
        if (!postData) {
            console.error('[MPAI Debug] Failed to parse XML content');
            return;
        }
        
        console.log('[MPAI Debug] Parsed post data:', postData);
        
        // Create the post preview card
        createPostPreviewCard($message, postData, xmlContent);
    }
    
    /**
     * Extract XML content from a message
     * 
     * @param {string} content - The message content
     * @return {string|null} The XML content or null if not found
     */
    function extractXmlContent(content) {
        // First, try to extract from code blocks
        const codeBlockRegex = /```(?:xml)?\s*(<wp-post>[\s\S]*?<\/wp-post>)\s*```/;
        const codeBlockMatch = content.match(codeBlockRegex);
        
        if (codeBlockMatch && codeBlockMatch[1]) {
            return codeBlockMatch[1];
        }
        
        // If not found in code blocks, try to extract directly
        const directRegex = /(<wp-post>[\s\S]*?<\/wp-post>)/;
        const directMatch = content.match(directRegex);
        
        if (directMatch && directMatch[1]) {
            return directMatch[1];
        }
        
        return null;
    }
    
    /**
     * Parse post XML content
     * 
     * @param {string} xmlContent - The XML content
     * @return {object|null} The parsed post data or null if parsing failed
     */
    function parsePostXml(xmlContent) {
        try {
            // Create a temporary DOM element to parse the XML
            const parser = new DOMParser();
            const xmlDoc = parser.parseFromString(xmlContent, 'text/xml');
            
            // Check for parsing errors
            const parseError = xmlDoc.querySelector('parsererror');
            if (parseError) {
                console.error('[MPAI Debug] XML parsing error:', parseError.textContent);
                return null;
            }
            
            // Extract post data
            const postElement = xmlDoc.querySelector('wp-post');
            if (!postElement) {
                console.error('[MPAI Debug] No wp-post element found in XML');
                return null;
            }
            
            const title = postElement.querySelector('post-title')?.textContent || '';
            const excerpt = postElement.querySelector('post-excerpt')?.textContent || '';
            const status = postElement.querySelector('post-status')?.textContent || 'draft';
            const type = postElement.querySelector('post-type')?.textContent || 'post';
            
            // Extract content blocks
            const contentElement = postElement.querySelector('post-content');
            let content = '';
            
            if (contentElement) {
                // Convert content blocks to HTML
                const blocks = contentElement.querySelectorAll('block');
                
                for (const block of blocks) {
                    const blockType = block.getAttribute('type');
                    
                    if (blockType === 'paragraph') {
                        content += `<p>${block.textContent}</p>`;
                    } else if (blockType === 'heading') {
                        const level = block.getAttribute('level') || '2';
                        content += `<h${level}>${block.textContent}</h${level}>`;
                    } else if (blockType === 'list') {
                        content += '<ul>';
                        const items = block.querySelectorAll('item');
                        for (const item of items) {
                            content += `<li>${item.textContent}</li>`;
                        }
                        content += '</ul>';
                    }
                }
            }
            
            return {
                title,
                content,
                excerpt,
                status,
                type
            };
        } catch (error) {
            console.error('[MPAI Debug] Error parsing XML:', error);
            return null;
        }
    }
    
    /**
     * Create a post preview card
     * 
     * @param {jQuery} $message - The message element
     * @param {object} postData - The post data
     * @param {string} xmlContent - The original XML content
     */
    function createPostPreviewCard($message, postData, xmlContent) {
        console.log('[MPAI Debug] Creating post preview card');
        
        // Create the card element
        const $card = $('<div class="mpai-post-preview-card"></div>');
        
        // Add the header
        const $header = $('<div class="mpai-post-preview-header"></div>');
        $header.append(`<div class="mpai-post-preview-type">BLOG POST</div>`);
        $header.append('<div class="mpai-post-preview-icon"><span class="dashicons dashicons-edit"></span></div>');
        $card.append($header);
        
        // Add the title
        $card.append(`<h3 class="mpai-post-preview-title">${postData.title}</h3>`);
        
        // Add the excerpt
        $card.append(`<div class="mpai-post-preview-excerpt">${postData.excerpt}</div>`);
        
        // Add the action buttons
        const $actions = $('<div class="mpai-post-preview-actions"></div>');
        $actions.append('<button class="mpai-create-post-button">Create Post</button>');
        $actions.append('<button class="mpai-preview-post-button">Preview</button>');
        $actions.append('<button class="mpai-toggle-xml-button">View XML</button>');
        $card.append($actions);
        
        // Add the XML content (hidden by default)
        const $xmlContent = $('<div class="mpai-post-xml-content" style="display: none;"></div>');
        $xmlContent.append(`<pre>${xmlContent}</pre>`);
        $card.append($xmlContent);
        
        // Add the preview content (hidden by default)
        const $previewContent = $('<div class="mpai-post-preview-content" style="display: none;"></div>');
        $previewContent.append(`<div class="mpai-post-preview-container">${postData.content}</div>`);
        $card.append($previewContent);
        
        // Replace the message content with the card
        const $content = $message.find('.mpai-chat-message-content');
        $content.empty().append($card);
        
        // Add event handlers for the buttons
        $message.find('.mpai-create-post-button').on('click', function() {
            createPost($message, postData, xmlContent);
        });
        
        $message.find('.mpai-preview-post-button').on('click', function() {
            togglePreview($message);
        });
        
        $message.find('.mpai-toggle-xml-button').on('click', function() {
            toggleXml($message);
        });
        
        console.log('[MPAI Debug] Post preview card created');
    }
    
    /**
     * Toggle the XML content visibility
     * 
     * @param {jQuery} $message - The message element
     */
    function toggleXml($message) {
        const $xmlContent = $message.find('.mpai-post-xml-content');
        const $button = $message.find('.mpai-toggle-xml-button');
        
        if ($xmlContent.is(':visible')) {
            $xmlContent.hide();
            $button.text('View XML');
        } else {
            $xmlContent.show();
            $button.text('Hide XML');
            
            // Hide the preview if it's visible
            $message.find('.mpai-post-preview-content').hide();
            $message.find('.mpai-preview-post-button').text('Preview');
        }
    }
    
    /**
     * Toggle the preview content visibility
     * 
     * @param {jQuery} $message - The message element
     */
    function togglePreview($message) {
        const $previewContent = $message.find('.mpai-post-preview-content');
        const $button = $message.find('.mpai-preview-post-button');
        
        if ($previewContent.is(':visible')) {
            $previewContent.hide();
            $button.text('Preview');
        } else {
            $previewContent.show();
            $button.text('Hide Preview');
            
            // Hide the XML if it's visible
            $message.find('.mpai-post-xml-content').hide();
            $message.find('.mpai-toggle-xml-button').text('View XML');
        }
    }
    
    /**
     * Create a post from the preview
     * 
     * @param {jQuery} $message - The message element
     * @param {object} postData - The post data
     * @param {string} xmlContent - The original XML content
     */
    function createPost($message, postData, xmlContent) {
        console.log('[MPAI Debug] Creating post:', postData);
        
        // Disable the button to prevent multiple submissions
        const $button = $message.find('.mpai-create-post-button');
        $button.prop('disabled', true).text('Creating...');
        
        // Prepare the data for the AJAX request
        const data = {
            action: 'mpai_create_post',
            nonce: mpai_nonce,
            title: postData.title,
            content: postData.content,
            excerpt: postData.excerpt,
            status: postData.status,
            post_type: postData.type || 'post'
        };
        
        // Send the AJAX request
        $.ajax({
            url: ajaxurl || (typeof mpai_chat_config !== 'undefined' ? mpai_chat_config.apiEndpoint.replace('/chat', '/create-post') : '/wp-admin/admin-ajax.php'),
            type: 'POST',
            data: data,
            success: function(response) {
                console.log('[MPAI Debug] Post created successfully:', response);
                
                // Update the button
                $button.prop('disabled', false).text('Post Created!');
                
                // Add the edit link if available
                if (response.data && response.data.edit_url) {
                    const $editLink = $('<a class="mpai-edit-post-link" target="_blank">Edit Post</a>');
                    $editLink.attr('href', response.data.edit_url);
                    $message.find('.mpai-post-preview-actions').append($editLink);
                }
                
                // Show a success message
                const $successMessage = $('<div class="mpai-post-success-message"></div>');
                $successMessage.text(response.data && response.data.message ? response.data.message : 'Post created successfully!');
                $message.find('.mpai-post-preview-card').append($successMessage);
            },
            error: function(xhr, status, error) {
                console.error('[MPAI Debug] Error creating post:', error);
                
                // Update the button
                $button.prop('disabled', false).text('Create Post');
                
                // Show an error message
                const $errorMessage = $('<div class="mpai-post-error-message"></div>');
                $errorMessage.text('Error creating post: ' + (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message ? xhr.responseJSON.data.message : error));
                $message.find('.mpai-post-preview-card').append($errorMessage);
            }
        });
    }
    
    // Initialize the module when the document is ready
    $(document).ready(function() {
        // Wait a short time to ensure all other scripts have loaded
        setTimeout(init, 500);
    });
    
    // Also initialize when the window loads (as a backup)
    $(window).on('load', function() {
        // Only initialize if not already initialized
        if (!window.MPAI_BlogFormatter.initialized) {
            init();
        }
    });
    
    // Export public methods
    window.MPAI_BlogFormatter = {
        init: init,
        processAssistantMessage: processAssistantMessage,
        initialized: false
    };
    
})(jQuery);

// Add a direct script tag to ensure the blog formatter is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('[MPAI Debug] DOM content loaded, checking for blog formatter');
    
    // Set a flag to track initialization
    if (window.MPAI_BlogFormatter) {
        console.log('[MPAI Debug] Blog formatter found, initializing');
        window.MPAI_BlogFormatter.initialized = true;
        window.MPAI_BlogFormatter.init();
        
        // Process any existing messages
        setTimeout(function() {
            console.log('[MPAI Debug] Processing existing messages after delay');
            $('.mpai-chat-message-assistant').each(function() {
                const $message = $(this);
                const content = $message.find('.mpai-chat-message-content').text();
                
                if (content && (
                    content.includes('<wp-post>') ||
                    content.includes('</wp-post>') ||
                    content.includes('<post-title>') ||
                    content.includes('</post-title>') ||
                    content.includes('<post-content>') ||
                    content.includes('</post-content>')
                )) {
                    console.log('[MPAI Debug] Found existing blog post XML in message');
                    window.MPAI_BlogFormatter.processAssistantMessage($message, content);
                }
            });
        }, 1000);
    }
});