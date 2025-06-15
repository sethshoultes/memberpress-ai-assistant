/**
 * StateManager - Manages all application state for the chat interface
 *
 * This module is responsible for managing all application state including
 * conversation history, UI state, and user state. It provides a centralized
 * store for all data and state changes in the application.
 *
 * @module StateManager
 * @author MemberPress
 * @since 1.0.0
 */

// Import StorageManager
import StorageManager from '../utils/storage-manager.js';

/**
 * StateManager class - Manages application state
 * 
 * @class
 */
class StateManager {
  /**
   * Creates a new StateManager instance
   * 
   * @constructor
   * @param {Object} initialState - Initial state values
   * @param {EventBus} eventBus - Event bus instance for publishing state changes
   */
  constructor(initialState = {}, eventBus) {
    /**
     * Current application state
     * @type {Object}
     * @private
     */
    this._state = {
      conversation: {
        messages: [],
        isLoading: false,
        error: null
      },
      user: {
        id: null,
        name: '',
        isAuthenticated: false
      },
      ui: {
        isChatOpen: false,
        activeTab: 'chat',
        theme: 'light'
      },
      ...initialState
    };

    /**
     * Event bus for publishing state changes
     * @type {EventBus}
     * @private
     */
    this._eventBus = eventBus;
  }

  /**
   * Initializes the state manager
   * 
   * @public
   * @returns {Promise<void>} A promise that resolves when initialization is complete
   */
  async initialize() {
    try {
      // Load state from storage if available
      await this.loadState();
      
      // Set up event listeners if needed
      // For example, we could listen for window.beforeunload to persist state
      window.addEventListener('beforeunload', () => {
        this.persistState();
      });
      
      return true;
    } catch (error) {
      console.error('Failed to initialize StateManager:', error);
      return false;
    }
  }

  /**
   * Gets the current state or a specific part of the state
   * 
   * @public
   * @param {string} [path] - Optional dot-notation path to get a specific part of the state
   * @returns {*} The requested state
   */
  getState(path = null) {
    // If no path is provided, return the entire state
    if (!path) {
      return this._state;
    }
    
    // Handle dot notation path
    const parts = path.split('.');
    let current = this._state;
    
    // Navigate through the path
    for (const part of parts) {
      if (current === undefined || current === null) {
        return undefined;
      }
      current = current[part];
    }
    
    return current;
  }

  /**
   * Updates the state with new values
   * 
   * @public
   * @param {Object} updates - State updates to apply
   * @param {string} [source] - Source of the update for tracking
   * @returns {Object} The new state after updates
   */
  setState(updates, source = 'internal') {
    // Deep merge the updates with the current state
    this._state = this._deepMerge(this._state, updates);
    
    // Publish state change event if event bus exists
    if (this._eventBus) {
      this._eventBus.publish('state.changed', {
        state: this._state,
        updates,
        source
      });
      
      // Publish specific events for updated sections
      Object.keys(updates).forEach(section => {
        this._eventBus.publish(`state.${section}.changed`, {
          state: this._state[section],
          updates: updates[section],
          source
        });
      });
    } else {
      // Debug message removed - was appearing in admin interface
    }
    
    return this._state;
  }

  /**
   * Adds a new message to the conversation history
   * 
   * @public
   * @param {Object} message - Message to add to the conversation
   * @returns {Object} The updated conversation state
   */
  addMessage(message) {
    // Ensure the message has a timestamp and ID if not provided
    const enhancedMessage = {
      id: message.id || `msg_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`,
      timestamp: message.timestamp || new Date().toISOString(),
      ...message
    };
    
    // Update the state with the new message
    // Get current messages or default to empty array if not found
    const currentMessages = this.getState('conversation.messages') || [];
    
    // Ensure currentMessages is an array before spreading
    let messagesArray = [];
    if (Array.isArray(currentMessages)) {
      messagesArray = currentMessages;
    } else if (currentMessages && typeof currentMessages === 'object') {
      // Convert object to array if it's an object with numeric keys
      messagesArray = Object.values(currentMessages);
    }
    
    console.log('[MPAI Debug] Current messages:', messagesArray);
    
    const updates = {
      conversation: {
        messages: [...messagesArray, enhancedMessage]
      }
    };
    
    // Use setState to update the state and trigger events
    this.setState(updates, 'message.added');
    
    // Publish a specific event for the new message if event bus exists
    if (this._eventBus) {
      this._eventBus.publish('conversation.message.added', {
        message: enhancedMessage
      });
    }
    
    return this.getState('conversation');
  }

  /**
   * Clears the conversation history
   * 
   * @public
   * @returns {Object} The updated conversation state
   */
  clearConversation() {
    // DEBUG: Log current state before clearing
    const stateBefore = this.getState();
    console.log('[MPAI Debug] StateManager.clearConversation - State before clearing:', stateBefore);
    console.log('[MPAI Debug] StateManager.clearConversation - Conversation ID before:', stateBefore?.conversation?.id);
    console.log('[MPAI Debug] StateManager.clearConversation - Messages count before:',
      stateBefore?.conversation?.messages ?
      (Array.isArray(stateBefore.conversation.messages) ? stateBefore.conversation.messages.length : Object.keys(stateBefore.conversation.messages).length) :
      'No messages found');
    
    // Reset the conversation.messages array to empty AND generate a new conversation ID
    // This prevents old messages from reloading on page refresh
    const newConversationId = `conv_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    console.log('[MPAI Debug] StateManager.clearConversation - Generating new conversation ID:', newConversationId);
    
    const updates = {
      conversation: {
        messages: [],
        id: newConversationId  // NEW: Generate fresh conversation ID to prevent old message reload
      }
    };
    
    console.log('[MPAI Debug] StateManager.clearConversation - Updates to apply:', updates);
    
    // Use setState to update the state and trigger events
    this.setState(updates, 'conversation.cleared');
    
    // DEBUG: Log state after clearing
    const stateAfter = this.getState();
    console.log('[MPAI Debug] StateManager.clearConversation - State after clearing:', stateAfter);
    console.log('[MPAI Debug] StateManager.clearConversation - Conversation ID after:', stateAfter?.conversation?.id);
    
    // DEBUG: Check if conversation ID changed (key diagnostic)
    if (stateBefore?.conversation?.id === stateAfter?.conversation?.id) {
      // Debug message removed - was appearing in admin interface
      // Debug message removed - was appearing in admin interface
      // Debug message removed - was appearing in admin interface
    } else {
      console.log('[MPAI Debug] StateManager.clearConversation - Good: Conversation ID changed from',
        stateBefore?.conversation?.id, 'to', stateAfter?.conversation?.id);
    }
    
    // Publish a specific event for clearing the conversation if event bus exists
    if (this._eventBus) {
      this._eventBus.publish('conversation.cleared', {
        timestamp: new Date().toISOString(),
        oldConversationId: stateBefore?.conversation?.id,
        newConversationId: stateAfter?.conversation?.id
      });
    }
    
    // CRITICAL: Immediately persist the cleared state to prevent old messages from reloading
    // Debug message removed - was appearing in admin interface
    this.persistState().then(success => {
      console.log('[MPAI Debug] StateManager.clearConversation - State persistence result:', success);
    }).catch(error => {
      console.error('[MPAI Debug] StateManager.clearConversation - Failed to persist cleared state:', error);
    });
    
    return this.getState('conversation');
  }

  /**
   * Sets the loading state for the conversation
   * 
   * @public
   * @param {boolean} isLoading - Whether the conversation is loading
   * @returns {Object} The updated conversation state
   */
  setLoading(isLoading) {
    // Update the conversation.isLoading property
    const updates = {
      conversation: {
        isLoading
      }
    };
    
    // Use setState to update the state and trigger events
    this.setState(updates, 'loading.changed');
    
    // Publish a specific event for the loading state change if event bus exists
    if (this._eventBus) {
      this._eventBus.publish('conversation.loading.changed', {
        isLoading
      });
    }
    
    return this.getState('conversation');
  }

  /**
   * Sets an error in the conversation state
   * 
   * @public
   * @param {Error|null} error - Error object or null to clear
   * @returns {Object} The updated conversation state
   */
  setError(error) {
    // Update the conversation.error property
    const updates = {
      conversation: {
        error: error ? (error instanceof Error ? error.message : error) : null
      }
    };
    
    // Use setState to update the state and trigger events
    this.setState(updates, 'error.changed');
    
    // Publish a specific event for the error state change if event bus exists
    if (this._eventBus) {
      this._eventBus.publish('conversation.error.changed', {
        error: updates.conversation.error
      });
    }
    
    return this.getState('conversation');
  }

  /**
   * Updates user information
   * 
   * @public
   * @param {Object} userInfo - User information to update
   * @returns {Object} The updated user state
   */
  updateUser(userInfo) {
    // Update the user state with the provided information
    const updates = {
      user: {
        ...userInfo
      }
    };
    
    // Use setState to update the state and trigger events
    this.setState(updates, 'user.updated');
    
    // Publish a specific event for the user update if event bus exists
    if (this._eventBus) {
      this._eventBus.publish('user.updated', {
        user: this.getState('user')
      });
    }
    
    return this.getState('user');
  }

  /**
   * Updates UI state
   * 
   * @public
   * @param {Object} uiUpdates - UI state updates
   * @returns {Object} The updated UI state
   */
  updateUI(uiUpdates) {
    // Update the UI state with the provided updates
    const updates = {
      ui: {
        ...uiUpdates
      }
    };
    
    // Use setState to update the state and trigger events
    this.setState(updates, 'ui.updated');
    
    // Publish a specific event for the UI update if event bus exists
    if (this._eventBus) {
      this._eventBus.publish('ui.updated', {
        ui: this.getState('ui')
      });
    }
    
    return this.getState('ui');
  }

  /**
   * Persists the current state to storage
   * 
   * @public
   * @returns {Promise<boolean>} A promise that resolves with success status
   */
  async persistState() {
    try {
      // Debug message removed - was appearing in admin interface
      
      // Create a new instance of StorageManager
      const storageManager = new StorageManager({
        storagePrefix: 'mpai_',
        defaultExpiration: 30 * 24 * 60 * 60 * 1000 // 30 days
      });
      
      // Initialize the storage manager
      await storageManager.initialize();
      
      // Save the current state to storage
      const success = storageManager.save('chatState', this._state, {
        // Optional: Set expiration time
        expiration: Date.now() + (30 * 24 * 60 * 60 * 1000) // 30 days
      });
      
      console.log('[MPAI Debug] State persisted successfully:', success);
      return success;
    } catch (error) {
      console.error('[MPAI Debug] Failed to persist state:', error);
      return false;
    }
  }

  /**
   * Loads state from storage
   * 
   * @public
   * @returns {Promise<Object>} A promise that resolves with the loaded state
   */
  async loadState() {
    try {
      // Debug message removed - was appearing in admin interface
      
      // Create a new instance of StorageManager
      const storageManager = new StorageManager({
        storagePrefix: 'mpai_',
        defaultExpiration: 30 * 24 * 60 * 60 * 1000 // 30 days
      });
      
      // Initialize the storage manager
      await storageManager.initialize();
      
      // Load state from storage
      const savedState = storageManager.get('chatState');
      console.log('[MPAI Debug] Loaded state from storage:', savedState ? 'Found' : 'Not found');
      
      // If there's saved state, update the current state
      if (savedState) {
        this._state = this._deepMerge(this._state, savedState);
        
        // Publish an event to notify that state was loaded if event bus exists
        if (this._eventBus) {
          this._eventBus.publish('state.loaded', {
            state: this._state
          });
        }
        
        // Debug message removed - was appearing in admin interface
      }
      
      return this._state;
    } catch (error) {
      console.error('[MPAI Debug] Failed to load state:', error);
      return this._state;
    }
  }
  
  /**
   * Deep merges two objects
   *
   * @private
   * @param {Object} target - Target object to merge into
   * @param {Object} source - Source object to merge from
   * @returns {Object} The merged object
   */
  _deepMerge(target, source) {
    // Create a new object to avoid modifying the original
    const output = { ...target };
    
    // If source or target is not an object, return source
    if (!source || typeof source !== 'object' || !target || typeof target !== 'object') {
      return source;
    }
    
    // Iterate through source properties
    Object.keys(source).forEach(key => {
      // SURGICAL FIX: Only handle conversation.messages specifically
      // This prevents old messages from being merged back in after clearing
      if (key === 'conversation' && source[key] && source[key].messages !== undefined) {
        // Debug message removed - was appearing in admin interface
        output[key] = {
          ...target[key],
          ...source[key],
          messages: source[key].messages  // REPLACE, don't merge messages array
        };
      }
      // If the property is an object, recursively merge (normal deep merge behavior)
      else if (source[key] && typeof source[key] === 'object' &&
          target[key] && typeof target[key] === 'object') {
        output[key] = this._deepMerge(target[key], source[key]);
      } else {
        // Otherwise, just copy the property
        output[key] = source[key];
      }
    });
    
    return output;
  }
}

// Export the StateManager class
export default StateManager;