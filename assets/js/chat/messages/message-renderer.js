/**
 * Message Renderer Module
 * 
 * This module is responsible for rendering messages in the chat interface.
 * It uses the MessageFactory to create appropriate message components based on message type.
 * 
 * @module MessageRenderer
 */

/**
 * Class representing a message renderer.
 * @class
 */
class MessageRenderer {
    /**
     * Create a message renderer.
     * @param {Object} dependencies - The dependencies required by the renderer.
     * @param {Object} dependencies.messageFactory - The message factory instance.
     * @param {Object} dependencies.eventBus - The event bus for publishing events.
     * @param {Object} dependencies.stateManager - The state manager for accessing application state.
     */
    constructor({ messageFactory, eventBus, stateManager }) {
        this._messageFactory = messageFactory;
        this._eventBus = eventBus;
        this._stateManager = stateManager;
        this._messageContainer = null;
    }

    /**
     * Initialize the message renderer.
     * @param {HTMLElement} container - The container element for messages.
     */
    initialize(container) {
        this._messageContainer = container;
        this._messageElements = new Map(); // Track rendered messages by ID
        this._bindEvents();
        
        // Initialization logging removed - creates excessive console noise
    }

    /**
     * Bind events to the event bus.
     * @private
     */
    _bindEvents() {
        if (!this._eventBus) {
            // Debug message removed - was appearing in admin interface
            return;
        }

        // Listen for message events
        this._eventBus.on('message-rendered', (data) => {
            // Message rendered logging removed - creates excessive console noise
        });

        this._eventBus.on('message-updated', (data) => {
            // Message updated logging removed - creates excessive console noise
        });

        this._eventBus.on('message-removed', (data) => {
            // Message removed logging removed - creates excessive console noise
        });

        // Debug message removed - was appearing in admin interface
    }

    /**
     * Render a message in the chat interface.
     * @param {Object} message - The message to render.
     * @returns {HTMLElement} The rendered message element.
     */
    renderMessage(message) {
        if (!message) {
            // Debug message removed - was appearing in admin interface
            return null;
        }

        if (!this._messageContainer) {
            // Debug message removed - was appearing in admin interface
            return null;
        }

        try {
            // Create the message wrapper
            const messageWrapper = this._createMessageWrapper(message);
            
            // Create message component using factory
            const messageComponent = this._messageFactory.createMessageComponent(message);
            
            if (!messageComponent) {
                // Debug message removed - was appearing in admin interface
                return null;
            }

            // Add the component to the wrapper
            const contentArea = messageWrapper.querySelector('.mpai-chat-message-content');
            if (contentArea) {
                contentArea.appendChild(messageComponent);
            } else {
                messageWrapper.appendChild(messageComponent);
            }

            // Append to container
            this._messageContainer.appendChild(messageWrapper);

            // Track the message element
            if (message.id) {
                this._messageElements.set(message.id, messageWrapper);
            }

            // Emit event
            this._eventBus?.emit('message-rendered', { message, element: messageWrapper });

            // Scroll to bottom
            this._scrollToBottom();

            // Message render success logging removed - creates excessive console noise
            return messageWrapper;

        } catch (error) {
            console.error('[MessageRenderer] Error rendering message:', error);
            return this._createErrorMessage(message, error);
        }
    }

    /**
     * Create a message wrapper element.
     * @private
     * @param {Object} message - The message data.
     * @returns {HTMLElement} The message wrapper element.
     */
    _createMessageWrapper(message) {
        const wrapper = document.createElement('div');
        wrapper.className = `mpai-chat-message mpai-chat-message-${message.role || 'assistant'}`;
        
        if (message.id) {
            wrapper.setAttribute('data-message-id', message.id);
        }

        // Add timestamp
        if (message.timestamp) {
            wrapper.setAttribute('data-timestamp', message.timestamp);
        }

        // Create message structure
        const avatar = document.createElement('div');
        avatar.className = 'mpai-chat-message-avatar';
        avatar.innerHTML = message.role === 'user' ?
            '<span class="dashicons dashicons-admin-users"></span>' :
            '<span class="dashicons dashicons-admin-tools"></span>';

        const content = document.createElement('div');
        content.className = 'mpai-chat-message-content';

        wrapper.appendChild(avatar);
        wrapper.appendChild(content);

        return wrapper;
    }

    /**
     * Create an error message element.
     * @private
     * @param {Object} message - The original message data.
     * @param {Error} error - The error that occurred.
     * @returns {HTMLElement} The error message element.
     */
    _createErrorMessage(message, error) {
        const wrapper = this._createMessageWrapper({
            ...message,
            role: 'system'
        });

        const errorDiv = document.createElement('div');
        errorDiv.className = 'mpai-chat-message-error';
        errorDiv.innerHTML = `
            <p><strong>Error rendering message:</strong></p>
            <p>${error.message}</p>
            <details>
                <summary>Original message content</summary>
                <pre>${JSON.stringify(message, null, 2)}</pre>
            </details>
        `;

        const contentArea = wrapper.querySelector('.mpai-chat-message-content');
        contentArea.appendChild(errorDiv);

        this._messageContainer?.appendChild(wrapper);
        return wrapper;
    }

    /**
     * Update an existing message in the chat interface.
     * @param {string} messageId - The ID of the message to update.
     * @param {Object} updatedData - The updated message data.
     * @returns {HTMLElement} The updated message element.
     */
    updateMessage(messageId, updatedData) {
        if (!messageId) {
            // Debug message removed - was appearing in admin interface
            return null;
        }

        const existingElement = this._messageElements.get(messageId);
        if (!existingElement) {
            // Message not found logging removed - creates excessive console noise
            return null;
        }

        try {
            // Create new message component
            const newComponent = this._messageFactory.createMessageComponent(updatedData);
            
            if (!newComponent) {
                // Debug message removed - was appearing in admin interface
                return existingElement;
            }

            // Replace the content
            const contentArea = existingElement.querySelector('.mpai-chat-message-content');
            if (contentArea) {
                contentArea.innerHTML = '';
                contentArea.appendChild(newComponent);
            }

            // Update attributes
            if (updatedData.timestamp) {
                existingElement.setAttribute('data-timestamp', updatedData.timestamp);
            }

            // Emit event
            this._eventBus?.emit('message-updated', { messageId, updatedData, element: existingElement });

            // Message update success logging removed - creates excessive console noise
            return existingElement;

        } catch (error) {
            console.error(`[MessageRenderer] Error updating message ${messageId}:`, error);
            return existingElement;
        }
    }

    /**
     * Remove a message from the chat interface.
     * @param {string} messageId - The ID of the message to remove.
     */
    removeMessage(messageId) {
        if (!messageId) {
            // Debug message removed - was appearing in admin interface
            return;
        }

        const element = this._messageElements.get(messageId);
        if (!element) {
            // Message removal not found logging removed - creates excessive console noise
            return;
        }

        try {
            // Remove from DOM
            element.remove();

            // Remove from tracking
            this._messageElements.delete(messageId);

            // Emit event
            this._eventBus?.emit('message-removed', { messageId });

            // Message removal success logging removed - creates excessive console noise

        } catch (error) {
            console.error(`[MessageRenderer] Error removing message ${messageId}:`, error);
        }
    }

    /**
     * Clear all messages from the chat interface.
     */
    clearMessages() {
        if (!this._messageContainer) {
            // Debug message removed - was appearing in admin interface
            return;
        }

        try {
            // Remove all child elements
            this._messageContainer.innerHTML = '';

            // Clear tracking
            this._messageElements.clear();

            // Emit event
            this._eventBus?.emit('messages-cleared');

            // Debug message removed - was appearing in admin interface

        } catch (error) {
            console.error('[MessageRenderer] Error clearing messages:', error);
        }
    }

    /**
     * Scroll to the bottom of the message container.
     * @private
     */
    _scrollToBottom() {
        if (this._messageContainer) {
            this._messageContainer.scrollTop = this._messageContainer.scrollHeight;
        }
    }

    /**
     * Get all rendered message elements.
     * @returns {HTMLElement[]} Array of message elements.
     */
    getAllMessages() {
        return Array.from(this._messageElements.values());
    }

    /**
     * Get a specific message element by ID.
     * @param {string} messageId - The message ID.
     * @returns {HTMLElement|null} The message element or null if not found.
     */
    getMessage(messageId) {
        return this._messageElements.get(messageId) || null;
    }

    /**
     * Get the count of rendered messages.
     * @returns {number} The number of rendered messages.
     */
    getMessageCount() {
        return this._messageElements.size;
    }
}

export default MessageRenderer;