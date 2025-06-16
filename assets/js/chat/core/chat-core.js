/**
 * ChatCore - Main entry point and orchestrator for the chat system
 * 
 * This module serves as the primary controller and orchestrator for the entire
 * chat interface. It initializes and coordinates all other modules, handles
 * high-level event coordination, and exposes the public API for the chat interface.
 * 
 * @module ChatCore
 * @author MemberPress
 * @since 1.0.0
 */

/**
 * ChatCore class - Main controller for the chat interface
 * 
 * @class
 */
class ChatCore {
  /**
   * Creates a new ChatCore instance
   * 
   * @constructor
   * @param {Object} config - Configuration options for the chat interface
   */
  constructor(config = {}) {
    /**
     * Configuration options
     * @type {Object}
     * @private
     */
    this._config = config;
    
    /**
     * StateManager instance
     * @type {StateManager}
     * @private
     */
    this._stateManager = null;
    
    /**
     * UIManager instance
     * @type {UIManager}
     * @private
     */
    this._uiManager = null;
    
    /**
     * APIClient instance
     * @type {APIClient}
     * @private
     */
    this._apiClient = null;
    
    /**
     * EventBus instance
     * @type {EventBus}
     * @private
     */
    this._eventBus = null;
  }

  /**
   * Initializes the chat interface and all required modules
   * 
   * @public
   * @returns {Promise<void>} A promise that resolves when initialization is complete
   */
  async initialize() {
    // Debug message removed - was appearing in admin interface
    
    try {
      // Check if instances are already provided (from chat.js)
      if (this._stateManager && this._uiManager && this._apiClient && this._eventBus) {
        // Debug message removed - was appearing in admin interface
        // Module availability logging removed - creates excessive console noise
      } else {
        // Debug message removed - was appearing in admin interface
        
        // Create new instances of the required modules if they're not already provided
        // Create EventBus first since other modules depend on it
        if (!this._eventBus) {
          // Debug message removed - was appearing in admin interface
          // Import EventBus dynamically if needed
          const EventBus = (await import('../core/event-bus.js')).default;
          this._eventBus = new EventBus();
        } else {
          // Debug message removed - was appearing in admin interface
        }
        
        // Now create StateManager with the EventBus
        if (!this._stateManager) {
          // Debug message removed - was appearing in admin interface
          // Import StateManager dynamically if needed
          const StateManager = (await import('../core/state-manager.js')).default;
          this._stateManager = new StateManager({}, this._eventBus);
          
          // Initialize the StateManager to load state from localStorage
          await this._stateManager.initialize();
          // Debug message removed - was appearing in admin interface
        } else {
          // Debug message removed - was appearing in admin interface
        }
        
        if (!this._apiClient) {
          // Debug message removed - was appearing in admin interface
          // Import APIClient dynamically if needed
          const APIClient = (await import('../core/api-client.js')).default;
          this._apiClient = new APIClient({}, this._eventBus);
        } else {
          // Debug message removed - was appearing in admin interface
        }
        
        if (!this._uiManager) {
          // Debug message removed - was appearing in admin interface
          // Import UIManager dynamically if needed
          const UIManager = (await import('../core/ui-manager.js')).default;
          this._uiManager = new UIManager({}, this._stateManager, this._eventBus);
          
          // Initialize the UI manager with the chat container
          await this._uiManager.initialize('#mpai-chat-container');
        } else {
          // Debug message removed - was appearing in admin interface
        }
      }
      
      // Initialize the modules in the correct order
      if (this._eventBus) {
        // Debug message removed - was appearing in admin interface
        
        // Chat button handling is now managed by UIManager to avoid conflicts
        // Removed ui.button.click subscription to prevent duplicate event handling
        
        // Subscribe to message events
        this._eventBus.subscribe('message.user', (data) => {
          // User message event logging removed - creates excessive console noise
          if (this._stateManager && data.content) {
            this._stateManager.addMessage({
              role: 'user',
              content: data.content,
              timestamp: data.timestamp || new Date().toISOString()
            });
          }
        });
        
        // Subscribe to conversation state changes
        this._eventBus.subscribe('state.conversation.changed', (data) => {
          // Conversation state change logging removed - creates excessive console noise
          if (this._uiManager) {
            this._uiManager.renderMessages();
          }
        });
      }
      
      // Store the module instances in the global scope for debugging
      window.stateManager = this._stateManager;
      window.uiManager = this._uiManager;
      window.apiClient = this._apiClient;
      window.eventBus = this._eventBus;
      
      // Debug message removed - was appearing in admin interface
      return true;
    } catch (error) {
      console.error('[MPAI Debug] Error initializing ChatCore:', error);
      return false;
    }
  }

  /**
   * Starts the chat interface after initialization
   *
   * @public
   * @returns {Promise<void>} A promise that resolves when the chat interface is ready
   */
  async start() {
    // Debug message removed - was appearing in admin interface
    
    // Chat button click handling is now managed by UIManager to avoid conflicts
    // Just check if the button exists for debugging
    const chatButton = document.querySelector('.mpai-chat-toggle, #mpai-chat-toggle');
    if (chatButton) {
      // Debug message removed - was appearing in admin interface
    } else {
      // Debug message removed - was appearing in admin interface
    }
    
    // Set initial visibility based on state
    if (this._stateManager) {
      const uiState = this._stateManager.getState('ui');
      if (uiState && uiState.isChatOpen) {
        // Debug message removed - was appearing in admin interface
        this.toggleChat(true);
      }
    }
    
    // Debug message removed - was appearing in admin interface
    return true;
  }

  /**
   * Stops the chat interface and performs cleanup
   * 
   * @public
   * @returns {Promise<void>} A promise that resolves when the chat interface is stopped
   */
  async stop() {
    // Debug message removed - was appearing in admin interface
    
    // Cancel any ongoing API requests
    if (this._apiClient && typeof this._apiClient.cancelAllRequests === 'function') {
      this._apiClient.cancelAllRequests();
      // Debug message removed - was appearing in admin interface
    }
    
    // Persist state before stopping
    if (this._stateManager && typeof this._stateManager.persistState === 'function') {
      await this._stateManager.persistState();
      // Debug message removed - was appearing in admin interface
    }
    
    // Publish a stop event
    if (this._eventBus) {
      this._eventBus.publish('chat.stopped', {
        timestamp: new Date().toISOString()
      });
      // Debug message removed - was appearing in admin interface
    }
    
    // Debug message removed - was appearing in admin interface
    return true;
  }

  /**
   * Sends a message through the chat interface
   * 
   * @public
   * @param {string} message - The message to send
   * @returns {Promise<Object>} A promise that resolves with the response
   */
  async sendMessage(message) {
    // Message sending logging removed - creates excessive console noise
    
    if (!this._apiClient) {
      // Debug message removed - was appearing in admin interface
      throw new Error('Chat system not properly initialized');
    }
    
    try {
      // Get the current state
      const state = this._stateManager.getState();
      
      // Add the user message to the state
      if (this._stateManager) {
        this._stateManager.addMessage({
          role: 'user',
          content: message,
          timestamp: new Date().toISOString()
        });
        // Debug message removed - was appearing in admin interface
      }
      
      // Send the message to the API
      const response = await this._apiClient.sendMessage(message, {
        conversationId: state?.conversation?.id,
        userLoggedIn: state?.user?.isAuthenticated || false
      });
      
      // Message success logging removed - creates excessive console noise
      
      // Update the state with the response
      // Handle both direct response.conversation_id and response.data.conversation_id formats
      let conversationId = response.conversation_id || response.data?.conversation_id;
      if (conversationId) {
        this._stateManager.setState({
          conversation: {
            id: conversationId
          }
        });
        // Conversation ID update logging removed - creates excessive console noise
      }
      
      // Add the assistant message to the UI
      // Handle both direct response.message and response.data.message formats
      // Response processing logging removed - creates excessive console noise
      
      let messageContent = null;
      if (response.message) {
        messageContent = response.message;
        // Message content detection logging removed - creates excessive console noise
      } else if (response.data && response.data.message) {
        messageContent = response.data.message;
        // Message content detection logging removed - creates excessive console noise
      }
      
      if (messageContent) {
        // Debug message removed - was appearing in admin interface
        const messageObj = {
          role: 'assistant',
          content: messageContent,
          timestamp: response.timestamp || response.data?.timestamp || new Date().toISOString()
        };
        const result = this._stateManager.addMessage(messageObj);
        // Message addition logging removed - creates excessive console noise
      } else {
        console.warn('[MPAI Debug] No message content found in response:', response);
        console.warn('[MPAI Debug] Response structure:', JSON.stringify(response, null, 2));
      }
      
      return response;
    } catch (error) {
      console.error('[MPAI Debug] Error sending message:', error);
      
      // Update the state with the error
      if (this._stateManager) {
        this._stateManager.setError(error);
      }
      
      // Re-throw the error
      throw error;
    }
  }

  /**
   * Clears the chat history
   * 
   * @public
   * @returns {Promise<void>} A promise that resolves when the history is cleared
   */
  async clearHistory() {
    // Debug message removed - was appearing in admin interface
    
    if (!this._stateManager) {
      // Debug message removed - was appearing in admin interface
      return false;
    }
    
    try {
      // DEBUG: Log current state before clearing
      const stateBefore = this._stateManager.getState();
      // Clear history state logging - keep minimal info for troubleshooting
      if (window.mpai_chat_config?.debug) {
        console.log('[MPAI Debug] ChatCore.clearHistory - Messages count before:',
          stateBefore?.conversation?.messages ?
          (Array.isArray(stateBefore.conversation.messages) ? stateBefore.conversation.messages.length : Object.keys(stateBefore.conversation.messages).length) :
          'No messages found');
      }
      
      // Clear conversation history in state manager
      // Debug message removed - was appearing in admin interface
      this._stateManager.clearConversation();
      // Debug message removed - was appearing in admin interface
      
      // DEBUG: Log state after state manager clear
      // State after clear logging removed - creates excessive console noise
      
      // Clear conversation on the server if API client is available
      if (this._apiClient && typeof this._apiClient.clearConversation === 'function') {
        // Debug message removed - was appearing in admin interface
        await this._apiClient.clearConversation();
        // Debug message removed - was appearing in admin interface
      } else {
        // Debug message removed - was appearing in admin interface
      }
      
      // DEBUG: Log final state after server clear
      const stateFinal = this._stateManager.getState();
      // Conversation ID change verification - keep for troubleshooting
      if (window.mpai_chat_config?.debug && stateBefore?.conversation?.id !== stateFinal?.conversation?.id) {
        console.log('[MPAI Debug] ChatCore.clearHistory - Conversation ID changed successfully');
      }
      
      // Publish a history cleared event
      if (this._eventBus) {
        this._eventBus.publish('chat.history.cleared', {
          timestamp: new Date().toISOString(),
          oldConversationId: stateBefore?.conversation?.id,
          newConversationId: stateFinal?.conversation?.id
        });
        // Debug message removed - was appearing in admin interface
      }
      
      return true;
    } catch (error) {
      console.error('[MPAI Debug] Error clearing history:', error);
      
      // Update the state with the error
      if (this._stateManager) {
        this._stateManager.setError(error);
      }
      
      return false;
    }
  }

  /**
   * Gets the current state of the chat interface
   * 
   * @public
   * @returns {Object} The current state
   */
  getState() {
    // Return current state
    if (this._stateManager) {
      return this._stateManager.getState();
    }
    return null;
  }
  
  /**
   * Toggles the chat interface open/closed state
   *
   * @public
   * @param {boolean} [forceState] - Force the chat to this state instead of toggling
   * @returns {boolean} The new open state
   */
  toggleChat(forceState) {
    // Toggle chat logging removed - creates excessive console noise
    
    if (!this._stateManager || !this._uiManager) {
      // Debug message removed - was appearing in admin interface
      return false;
    }
    
    // Get the current UI state
    const uiState = this._stateManager.getState('ui');
    const isChatOpen = uiState?.isChatOpen || false;
    
    // Set the chat state - use forceState if provided, otherwise toggle
    const newState = typeof forceState === 'boolean' ? forceState : !isChatOpen;
    // Chat state change logging removed - creates excessive console noise
    
    // Update the state
    this._stateManager.updateUI({
      isChatOpen: newState
    });
    
    // Also update the UI directly
    if (this._uiManager && typeof this._uiManager.toggleChatVisibility === 'function') {
      this._uiManager.toggleChatVisibility(newState);
      // UI manager call logging removed - creates excessive console noise
    } else {
      // Debug message removed - was appearing in admin interface
    }
    
    return newState;
  }
}

// Export the ChatCore class
export default ChatCore;