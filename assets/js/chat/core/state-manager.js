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
    
    // Publish state change event
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
    const updates = {
      conversation: {
        messages: [...this.getState('conversation.messages'), enhancedMessage]
      }
    };
    
    // Use setState to update the state and trigger events
    this.setState(updates, 'message.added');
    
    // Publish a specific event for the new message
    this._eventBus.publish('conversation.message.added', {
      message: enhancedMessage
    });
    
    return this.getState('conversation');
  }

  /**
   * Clears the conversation history
   * 
   * @public
   * @returns {Object} The updated conversation state
   */
  clearConversation() {
    // Reset the conversation.messages array to empty
    const updates = {
      conversation: {
        messages: []
      }
    };
    
    // Use setState to update the state and trigger events
    this.setState(updates, 'conversation.cleared');
    
    // Publish a specific event for clearing the conversation
    this._eventBus.publish('conversation.cleared', {
      timestamp: new Date().toISOString()
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
    
    // Publish a specific event for the loading state change
    this._eventBus.publish('conversation.loading.changed', {
      isLoading
    });
    
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
    
    // Publish a specific event for the error state change
    this._eventBus.publish('conversation.error.changed', {
      error: updates.conversation.error
    });
    
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
    
    // Publish a specific event for the user update
    this._eventBus.publish('user.updated', {
      user: this.getState('user')
    });
    
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
    
    // Publish a specific event for the UI update
    this._eventBus.publish('ui.updated', {
      ui: this.getState('ui')
    });
    
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
      // Get an instance of the StorageManager
      // This assumes StorageManager is available as a global or imported
      const storageManager = new StorageManager();
      
      // Save the current state to storage
      const success = storageManager.save('chatState', this._state, {
        // Optional: Set expiration time
        expiration: Date.now() + (30 * 24 * 60 * 60 * 1000) // 30 days
      });
      
      return success;
    } catch (error) {
      console.error('Failed to persist state:', error);
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
      // Get an instance of the StorageManager
      // This assumes StorageManager is available as a global or imported
      const storageManager = new StorageManager();
      
      // Load state from storage
      const savedState = storageManager.get('chatState');
      
      // If there's saved state, update the current state
      if (savedState) {
        this._state = this._deepMerge(this._state, savedState);
        
        // Publish an event to notify that state was loaded
        this._eventBus.publish('state.loaded', {
          state: this._state
        });
      }
      
      return this._state;
    } catch (error) {
      console.error('Failed to load state:', error);
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
      // If the property is an object, recursively merge
      if (source[key] && typeof source[key] === 'object' &&
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