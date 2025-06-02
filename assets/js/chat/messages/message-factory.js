/**
 * Message Factory Module
 * 
 * This module is responsible for creating message components based on message type.
 * It provides an extensible system for adding new message types.
 * 
 * @module MessageFactory
 */

/**
 * Class representing a message factory.
 * @class
 */
class MessageFactory {
    /**
     * Create a message factory.
     * @param {Object} dependencies - The dependencies required by the factory.
     * @param {Object} dependencies.handlerRegistry - Registry of message handlers.
     * @param {Object} dependencies.eventBus - The event bus for publishing events.
     */
    constructor({ handlerRegistry, eventBus }) {
        this._handlerRegistry = handlerRegistry || {};
        this._eventBus = eventBus;
    }

    /**
     * Initialize the message factory.
     */
    initialize() {
        this._registerDefaultHandlers();
    }

    /**
     * Register default message handlers.
     * @private
     */
    _registerDefaultHandlers() {
        // Import and register default handlers
        this._loadDefaultHandlers();
    }

    /**
     * Load default handlers dynamically.
     * @private
     */
    async _loadDefaultHandlers() {
        try {
            // Import handlers
            const { default: BlogPostMessageHandler } = await import('./handlers/blog-post-message-handler.js');
            const { default: TextMessageHandler } = await import('./handlers/text-message-handler.js');
            const { default: SystemMessageHandler } = await import('./handlers/system-message-handler.js');
            const { default: InteractiveMessageHandler } = await import('./handlers/interactive-message-handler.js');

            // Create handler instances with dependencies
            const dependencies = {
                eventBus: this._eventBus,
                stateManager: this._stateManager || null,
                contentPreview: this._contentPreview || null
            };

            // Register handlers
            const blogPostHandler = new BlogPostMessageHandler(dependencies);
            const textHandler = new TextMessageHandler(dependencies);
            const systemHandler = new SystemMessageHandler(dependencies);
            const interactiveHandler = new InteractiveMessageHandler(dependencies);

            this.registerHandler('blog-post', blogPostHandler);
            this.registerHandler('text', textHandler);
            this.registerHandler('system', systemHandler);
            this.registerHandler('interactive', interactiveHandler);

            console.log('[MessageFactory] Default handlers registered');
        } catch (error) {
            console.error('[MessageFactory] Error loading default handlers:', error);
        }
    }

    /**
     * Register a message handler for a specific message type.
     * @param {string} messageType - The type of message this handler processes.
     * @param {Object} handler - The handler instance.
     */
    registerHandler(messageType, handler) {
        if (!messageType || !handler) {
            console.warn('[MessageFactory] Invalid handler registration:', { messageType, handler });
            return;
        }

        this._handlerRegistry[messageType] = handler;
        console.log(`[MessageFactory] Registered handler for type: ${messageType}`);
    }

    /**
     * Get a handler for a specific message type.
     * @param {string} messageType - The type of message.
     * @returns {Object} The handler for the specified message type.
     */
    getHandler(messageType) {
        return this._handlerRegistry[messageType] || null;
    }

    /**
     * Create a message component based on message type.
     * @param {Object} message - The message data.
     * @returns {HTMLElement} The created message component.
     */
    createMessageComponent(message) {
        if (!message) {
            console.warn('[MessageFactory] No message provided');
            return this._createFallbackComponent('No message content');
        }

        // Determine message type
        const messageType = this._determineMessageType(message);
        
        // Get appropriate handler
        const handler = this.getHandler(messageType);
        
        if (!handler) {
            console.warn(`[MessageFactory] No handler found for message type: ${messageType}`);
            return this._createFallbackComponent(message.content || 'Unknown message type');
        }

        try {
            // Create component using handler
            const component = handler.createComponent(message);
            
            if (!component) {
                console.warn(`[MessageFactory] Handler returned null component for type: ${messageType}`);
                return this._createFallbackComponent(message.content || 'Handler error');
            }

            return component;
        } catch (error) {
            console.error(`[MessageFactory] Error creating component for type ${messageType}:`, error);
            return this._createFallbackComponent(message.content || 'Component creation error');
        }
    }

    /**
     * Determine the message type based on message content.
     * @private
     * @param {Object} message - The message data.
     * @returns {string} The determined message type.
     */
    _determineMessageType(message) {
        if (!message || !message.content) {
            return 'text';
        }

        const content = message.content;

        // Check for blog post XML
        if (content.includes('<wp-post>') || content.includes('<post-title>') || content.includes('<post-content>')) {
            return 'blog-post';
        }

        // Check for system messages
        if (message.role === 'system' || content.startsWith('[SYSTEM]')) {
            return 'system';
        }

        // Check for interactive elements (buttons, forms, etc.)
        if (content.includes('<button') || content.includes('<form') || content.includes('data-action=')) {
            return 'interactive';
        }

        // Default to text
        return 'text';
    }

    /**
     * Create a fallback component for when handlers fail.
     * @private
     * @param {string} content - The content to display.
     * @returns {HTMLElement} The fallback component.
     */
    _createFallbackComponent(content) {
        const div = document.createElement('div');
        div.className = 'mpai-chat-message-content mpai-fallback-message';
        div.textContent = content || 'Message could not be displayed';
        return div;
    }

    /**
     * Check if a handler exists for a specific message type.
     * @param {string} messageType - The type of message.
     * @returns {boolean} True if a handler exists, false otherwise.
     */
    hasHandlerFor(messageType) {
        return this._handlerRegistry.hasOwnProperty(messageType) && this._handlerRegistry[messageType] !== null;
    }

    /**
     * Get all registered message types.
     * @returns {string[]} Array of registered message types.
     */
    getRegisteredTypes() {
        return Object.keys(this._handlerRegistry);
    }

    /**
     * Set dependencies for handlers.
     * @param {Object} dependencies - Dependencies to pass to handlers.
     */
    setDependencies(dependencies) {
        this._stateManager = dependencies.stateManager;
        this._contentPreview = dependencies.contentPreview;
        
        // Re-register handlers with new dependencies if they exist
        if (Object.keys(this._handlerRegistry).length > 0) {
            this._registerDefaultHandlers();
        }
    }
}

export default MessageFactory;