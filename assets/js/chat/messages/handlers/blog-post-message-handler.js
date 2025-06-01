/**
 * Blog Post Message Handler Module
 * 
 * This module handles blog post messages in the chat interface.
 * It extends the BaseMessageHandler and creates preview cards for blog posts.
 * 
 * @module BlogPostMessageHandler
 */

import BaseMessageHandler from './base-message-handler.js';

/**
 * Class representing a blog post message handler.
 * @class
 * @extends BaseMessageHandler
 */
class BlogPostMessageHandler extends BaseMessageHandler {
    /**
     * Create a blog post message handler.
     * @param {Object} dependencies - The dependencies required by the handler.
     * @param {Object} dependencies.eventBus - The event bus for publishing events.
     * @param {Object} dependencies.stateManager - The state manager for accessing application state.
     * @param {Object} dependencies.contentPreview - The content preview service.
     */
    constructor({ eventBus, stateManager, contentPreview }) {
        super({ eventBus, stateManager });
        this._contentPreview = contentPreview;
    }

    /**
     * Check if this handler can process the given message.
     * @param {Object} message - The message to check.
     * @returns {boolean} True if this handler can process the message, false otherwise.
     */
    canHandle(message) {
        if (!message || !message.content) {
            return false;
        }

        const content = message.content;
        
        // Check if content contains XML blog post format
        return content.includes('<wp-post>') && content.includes('</wp-post>') ||
               content.includes('<post-title>') && content.includes('</post-title>') ||
               content.includes('<post-content>') && content.includes('</post-content>');
    }

    /**
     * Process a message and create a message component.
     * @param {Object} message - The message to process.
     * @returns {HTMLElement} The created message component.
     */
    createComponent(message) {
        const blogPostData = this._extractBlogPostData(message);
        
        if (!blogPostData) {
            // Fallback to basic message display
            const messageDiv = document.createElement('div');
            messageDiv.className = 'mpai-chat-message-content';
            messageDiv.textContent = message.content;
            return messageDiv;
        }

        return this._createPreviewCard(blogPostData, message.content);
    }

    /**
     * Update an existing message component with new data.
     * @param {HTMLElement} component - The component to update.
     * @param {Object} updatedData - The updated message data.
     * @returns {HTMLElement} The updated component.
     */
    updateComponent(component, updatedData) {
        // For blog posts, we typically don't update them in place
        // Instead, we recreate the component
        const newComponent = this.createComponent(updatedData);
        component.parentNode?.replaceChild(newComponent, component);
        return newComponent;
    }

    /**
     * Get the message type this handler processes.
     * @returns {string} The message type.
     */
    getMessageType() {
        return 'blog-post';
    }

    /**
     * Create a blog post preview card.
     * @private
     * @param {Object} blogPost - The blog post data.
     * @param {string} originalXml - The original XML content.
     * @returns {HTMLElement} The preview card element.
     */
    _createPreviewCard(blogPost, originalXml) {
        const card = document.createElement('div');
        card.className = 'mpai-post-preview-card';

        // Create header
        const header = document.createElement('div');
        header.className = 'mpai-post-preview-header';
        
        const typeDiv = document.createElement('div');
        typeDiv.className = 'mpai-post-preview-type';
        typeDiv.textContent = blogPost.type === 'page' ? 'Page' : 'Blog Post';
        
        const iconDiv = document.createElement('div');
        iconDiv.className = 'mpai-post-preview-icon';
        iconDiv.innerHTML = blogPost.type === 'page' ?
            '<span class="dashicons dashicons-page"></span>' :
            '<span class="dashicons dashicons-admin-post"></span>';
        
        header.appendChild(typeDiv);
        header.appendChild(iconDiv);

        // Create title
        const title = document.createElement('h3');
        title.className = 'mpai-post-preview-title';
        title.textContent = blogPost.title;

        // Create excerpt
        const excerpt = document.createElement('div');
        excerpt.className = 'mpai-post-preview-excerpt';
        excerpt.textContent = blogPost.excerpt;

        // Create action buttons
        const actions = document.createElement('div');
        actions.className = 'mpai-post-preview-actions';

        const createButton = document.createElement('button');
        createButton.className = 'mpai-create-post-button';
        createButton.setAttribute('data-content-type', blogPost.type);
        createButton.textContent = `Create ${blogPost.type === 'page' ? 'Page' : 'Post'}`;

        const previewButton = document.createElement('button');
        previewButton.className = 'mpai-preview-post-button';
        previewButton.textContent = 'Preview';

        const xmlButton = document.createElement('button');
        xmlButton.className = 'mpai-toggle-xml-button';
        xmlButton.textContent = 'View XML';

        actions.appendChild(createButton);
        actions.appendChild(previewButton);
        actions.appendChild(xmlButton);

        // Create XML content (hidden)
        const xmlContent = document.createElement('div');
        xmlContent.className = 'mpai-post-xml-content';
        xmlContent.style.display = 'none';
        
        const xmlPre = document.createElement('pre');
        xmlPre.textContent = originalXml;
        xmlContent.appendChild(xmlPre);

        // Create preview content (hidden)
        const previewContent = document.createElement('div');
        previewContent.className = 'mpai-post-preview-content';
        previewContent.style.display = 'none';
        
        const previewContainer = document.createElement('div');
        previewContainer.className = 'mpai-post-preview-container';
        previewContent.appendChild(previewContainer);

        // Assemble the card
        card.appendChild(header);
        card.appendChild(title);
        card.appendChild(excerpt);
        card.appendChild(actions);
        card.appendChild(xmlContent);
        card.appendChild(previewContent);

        // Add event listeners
        this._attachEventListeners(card, blogPost, originalXml);

        return card;
    }

    /**
     * Attach event listeners to the preview card.
     * @private
     * @param {HTMLElement} card - The preview card element.
     * @param {Object} blogPost - The blog post data.
     * @param {string} originalXml - The original XML content.
     */
    _attachEventListeners(card, blogPost, originalXml) {
        // Create post button
        const createButton = card.querySelector('.mpai-create-post-button');
        createButton?.addEventListener('click', (e) => {
            e.preventDefault();
            this._handleCreatePost(card, blogPost, originalXml);
        });

        // Preview button
        const previewButton = card.querySelector('.mpai-preview-post-button');
        previewButton?.addEventListener('click', (e) => {
            e.preventDefault();
            this._handlePreviewToggle(card, blogPost);
        });

        // XML toggle button
        const xmlButton = card.querySelector('.mpai-toggle-xml-button');
        xmlButton?.addEventListener('click', (e) => {
            e.preventDefault();
            this._handleXmlToggle(card);
        });
    }

    /**
     * Handle create post button click.
     * @private
     * @param {HTMLElement} card - The preview card element.
     * @param {Object} blogPost - The blog post data.
     * @param {string} originalXml - The original XML content.
     */
    _handleCreatePost(card, blogPost, originalXml) {
        const button = card.querySelector('.mpai-create-post-button');
        if (button.disabled) return;

        button.disabled = true;
        button.textContent = 'Creating...';

        // Use the content preview service if available
        if (this._contentPreview && typeof this._contentPreview.createPost === 'function') {
            this._contentPreview.createPost(blogPost, originalXml)
                .then((result) => {
                    button.textContent = 'Post Created!';
                    this._eventBus.emit('blog-post-created', { blogPost, result });
                })
                .catch((error) => {
                    button.disabled = false;
                    button.textContent = `Create ${blogPost.type === 'page' ? 'Page' : 'Post'}`;
                    console.error('Error creating post:', error);
                    this._eventBus.emit('blog-post-error', { error });
                });
        } else {
            // Fallback to direct AJAX
            this._createPostWithAjax(card, blogPost, originalXml);
        }
    }

    /**
     * Create post using AJAX as fallback.
     * @private
     * @param {HTMLElement} card - The preview card element.
     * @param {Object} blogPost - The blog post data.
     * @param {string} originalXml - The original XML content.
     */
    _createPostWithAjax(card, blogPost, originalXml) {
        const data = {
            action: 'mpai_create_post',
            nonce: window.mpai_nonce || '',
            title: blogPost.title,
            content: blogPost.content,
            excerpt: blogPost.excerpt,
            status: blogPost.status || 'draft',
            post_type: blogPost.type || 'post'
        };

        fetch(window.mpai_ajax?.url || '/wp-admin/admin-ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(data)
        })
        .then(response => response.json())
        .then(result => {
            const button = card.querySelector('.mpai-create-post-button');
            button.textContent = 'Post Created!';
            this._eventBus.emit('blog-post-created', { blogPost, result });
        })
        .catch(error => {
            const button = card.querySelector('.mpai-create-post-button');
            button.disabled = false;
            button.textContent = `Create ${blogPost.type === 'page' ? 'Page' : 'Post'}`;
            console.error('Error creating post:', error);
            this._eventBus.emit('blog-post-error', { error });
        });
    }

    /**
     * Handle preview toggle.
     * @private
     * @param {HTMLElement} card - The preview card element.
     * @param {Object} blogPost - The blog post data.
     */
    _handlePreviewToggle(card, blogPost) {
        const previewContent = card.querySelector('.mpai-post-preview-content');
        const previewContainer = card.querySelector('.mpai-post-preview-container');
        const button = card.querySelector('.mpai-preview-post-button');
        const xmlContent = card.querySelector('.mpai-post-xml-content');

        if (previewContent.style.display === 'none') {
            // Show preview
            previewContainer.innerHTML = this._convertXmlBlocksToHtml(blogPost.content);
            previewContent.style.display = 'block';
            button.textContent = 'Hide Preview';
            
            // Hide XML if visible
            xmlContent.style.display = 'none';
            const xmlButton = card.querySelector('.mpai-toggle-xml-button');
            xmlButton.textContent = 'View XML';
        } else {
            // Hide preview
            previewContent.style.display = 'none';
            button.textContent = 'Preview';
        }
    }

    /**
     * Handle XML toggle.
     * @private
     * @param {HTMLElement} card - The preview card element.
     */
    _handleXmlToggle(card) {
        const xmlContent = card.querySelector('.mpai-post-xml-content');
        const button = card.querySelector('.mpai-toggle-xml-button');
        const previewContent = card.querySelector('.mpai-post-preview-content');

        if (xmlContent.style.display === 'none') {
            // Show XML
            xmlContent.style.display = 'block';
            button.textContent = 'Hide XML';
            
            // Hide preview if visible
            previewContent.style.display = 'none';
            const previewButton = card.querySelector('.mpai-preview-post-button');
            previewButton.textContent = 'Preview';
        } else {
            // Hide XML
            xmlContent.style.display = 'none';
            button.textContent = 'View XML';
        }
    }

    /**
     * Extract blog post data from message.
     * @private
     * @param {Object} message - The message data.
     * @returns {Object} The extracted blog post data.
     */
    _extractBlogPostData(message) {
        if (!message || !message.content) {
            return null;
        }

        const content = message.content;
        let xmlContent = '';

        // Extract XML from code blocks first
        const codeBlockRegex = /```(?:xml)?\s*([\s\S]*?)```/g;
        let match = codeBlockRegex.exec(content);
        
        if (match && match[1] && match[1].includes('<wp-post')) {
            xmlContent = match[1];
        } else if (content.includes('<wp-post>') && content.includes('</wp-post>')) {
            // Extract direct XML
            const startPos = content.indexOf('<wp-post>');
            const endPos = content.lastIndexOf('</wp-post>') + 10;
            xmlContent = content.substring(startPos, endPos);
        }

        if (!xmlContent) {
            return null;
        }

        try {
            // Parse XML content
            const parser = new DOMParser();
            const xmlDoc = parser.parseFromString(xmlContent, 'text/xml');
            
            const parseError = xmlDoc.querySelector('parsererror');
            if (parseError) {
                console.error('XML parsing error:', parseError.textContent);
                return null;
            }

            const postElement = xmlDoc.querySelector('wp-post');
            if (!postElement) {
                return null;
            }

            const title = postElement.querySelector('post-title')?.textContent || 'New Blog Post';
            const excerpt = postElement.querySelector('post-excerpt')?.textContent || 'Blog post content created with AI Assistant';
            const status = postElement.querySelector('post-status')?.textContent || 'draft';
            const type = postElement.querySelector('post-type')?.textContent || 'post';

            // Extract and convert content blocks
            const contentElement = postElement.querySelector('post-content');
            let htmlContent = '';
            
            if (contentElement) {
                htmlContent = this._convertXmlBlocksToHtml(contentElement.innerHTML);
            }

            return {
                title,
                content: htmlContent,
                excerpt,
                status,
                type
            };
        } catch (error) {
            console.error('Error parsing blog post XML:', error);
            return null;
        }
    }

    /**
     * Convert XML blocks to HTML content.
     * @private
     * @param {string} xmlBlocks - The XML blocks content.
     * @returns {string} The converted HTML content.
     */
    _convertXmlBlocksToHtml(xmlBlocks) {
        if (!xmlBlocks) return '';

        let html = xmlBlocks;

        try {
            // Convert paragraph blocks
            html = html.replace(/<block\s+type=["']paragraph["'][^>]*>([\s\S]*?)<\/block>/g, '<p>$1</p>');
            
            // Convert heading blocks
            html = html.replace(/<block\s+type=["']heading["']\s+level=["'](\d)["'][^>]*>([\s\S]*?)<\/block>/g, '<h$1>$2</h$1>');
            
            // Convert list blocks
            html = html.replace(/<block\s+type=["']list["'][^>]*>([\s\S]*?)<\/block>/g, (match, content) => {
                const items = content.replace(/<item>([\s\S]*?)<\/item>/g, '<li>$1</li>');
                return `<ul>${items}</ul>`;
            });
            
            // Convert ordered list blocks
            html = html.replace(/<block\s+type=["']ordered-list["'][^>]*>([\s\S]*?)<\/block>/g, (match, content) => {
                const items = content.replace(/<item>([\s\S]*?)<\/item>/g, '<li>$1</li>');
                return `<ol>${items}</ol>`;
            });
            
            // Convert quote blocks
            html = html.replace(/<block\s+type=["']quote["'][^>]*>([\s\S]*?)<\/block>/g, '<blockquote>$1</blockquote>');
            
            // Convert code blocks
            html = html.replace(/<block\s+type=["']code["'][^>]*>([\s\S]*?)<\/block>/g, '<pre><code>$1</code></pre>');

            // If no conversions happened, wrap in paragraphs
            if (!html.includes('<p>') && !html.includes('<h') && !html.includes('<ul>') && !html.includes('<ol>')) {
                const paragraphs = html.split(/\n\s*\n/).filter(p => p.trim());
                html = paragraphs.map(p => `<p>${p.trim()}</p>`).join('');
            }

        } catch (error) {
            console.error('Error converting XML blocks to HTML:', error);
            html = `<p>${xmlBlocks}</p>`;
        }

        return html || '<p>Content created with MemberPress AI Assistant</p>';
    }
}

export default BlogPostMessageHandler;