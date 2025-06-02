/**
 * Base Message Handler Module
 * 
 * This module serves as an abstract base class for all message handlers.
 * It provides common functionality and defines the interface that all message handlers must implement.
 * 
 * @module BaseMessageHandler
 */

/**
 * Class representing a base message handler.
 * This is an abstract class that should be extended by specific message handlers.
 * @class
 * @abstract
 */
class BaseMessageHandler {
    /**
     * Create a base message handler.
     * @param {Object} dependencies - The dependencies required by the handler.
     * @param {Object} dependencies.eventBus - The event bus for publishing events.
     * @param {Object} dependencies.stateManager - The state manager for accessing application state.
     */
    constructor({ eventBus, stateManager }) {
        // Ensure this class is not instantiated directly
        if (this.constructor === BaseMessageHandler) {
            throw new Error('BaseMessageHandler is an abstract class and cannot be instantiated directly.');
        }
        
        this._eventBus = eventBus;
        this._stateManager = stateManager;
    }

    /**
     * Check if this handler can process the given message.
     * @param {Object} message - The message to check.
     * @returns {boolean} True if this handler can process the message, false otherwise.
     */
    canHandle(message) {
        throw new Error('Method canHandle() must be implemented by subclass.');
    }

    /**
     * Process a message and create a message component.
     * @param {Object} message - The message to process.
     * @returns {HTMLElement} The created message component.
     */
    createComponent(message) {
        throw new Error('Method createComponent() must be implemented by subclass.');
    }

    /**
     * Update an existing message component with new data.
     * @param {HTMLElement} component - The component to update.
     * @param {Object} updatedData - The updated message data.
     * @returns {HTMLElement} The updated component.
     */
    updateComponent(component, updatedData) {
        throw new Error('Method updateComponent() must be implemented by subclass.');
    }

    /**
     * Get the message type this handler processes.
     * @returns {string} The message type.
     */
    getMessageType() {
        throw new Error('Method getMessageType() must be implemented by subclass.');
    }

    /**
     * Create a DOM element with the given properties.
     * @protected
     * @param {string} tag - The HTML tag name.
     * @param {Object} attributes - The attributes to set on the element.
     * @param {string|HTMLElement} content - The content to append to the element.
     * @returns {HTMLElement} The created element.
     */
    _createElement(tag, attributes = {}, content = '') {
        // Helper method to create DOM elements
    }

    /**
     * Publish an event to the event bus.
     * @protected
     * @param {string} eventName - The name of the event.
     * @param {Object} data - The event data.
     */
    _publishEvent(eventName, data) {
        // Publish event to event bus
    }
}

export default BaseMessageHandler;