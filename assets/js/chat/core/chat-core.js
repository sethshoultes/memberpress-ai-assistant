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
    console.log('[MPAI Debug] ChatCore.initialize called');
    
    // Store references to the modules
    this._stateManager = window.stateManager;
    this._uiManager = window.uiManager;
    this._apiClient = window.apiClient;
    this._eventBus = window.eventBus;
    
    // Check if all required modules are available
    if (!this._stateManager) {
      console.error('[MPAI Debug] StateManager not found');
    }
    if (!this._uiManager) {
      console.error('[MPAI Debug] UIManager not found');
    }
    if (!this._apiClient) {
      console.error('[MPAI Debug] APIClient not found');
    }
    if (!this._eventBus) {
      console.error('[MPAI Debug] EventBus not found');
    }
    
    // Set up event listeners
    if (this._eventBus) {
      this._eventBus.subscribe('ui.button.click', (data) => {
        console.log('[MPAI Debug] UI button click event received:', data);
        if (data.button === 'chat-toggle') {
          this.toggleChat();
        }
      });
    }
    
    console.log('[MPAI Debug] ChatCore initialized');
    return true;
  }

  /**
   * Starts the chat interface after initialization
   *
   * @public
   * @returns {Promise<void>} A promise that resolves when the chat interface is ready
   */
  async start() {
    console.log('[MPAI Debug] ChatCore.start called');
    
    // Add click handler for chat button if it exists
    const chatButton = document.querySelector('.mpai-chat-button');
    if (chatButton) {
      console.log('[MPAI Debug] Found chat button, adding click handler');
      chatButton.addEventListener('click', () => {
        console.log('[MPAI Debug] Chat button clicked');
        this.toggleChat();
      });
    } else {
      console.warn('[MPAI Debug] Chat button not found');
    }
    
    console.log('[MPAI Debug] ChatCore started');
    return true;
  }

  /**
   * Stops the chat interface and performs cleanup
   * 
   * @public
   * @returns {Promise<void>} A promise that resolves when the chat interface is stopped
   */
  async stop() {
    console.log('[MPAI Debug] ChatCore.stop called');
    
    // Cancel any ongoing API requests
    if (this._apiClient && typeof this._apiClient.cancelAllRequests === 'function') {
      this._apiClient.cancelAllRequests();
      console.log('[MPAI Debug] Cancelled all API requests');
    }
    
    // Persist state before stopping
    if (this._stateManager && typeof this._stateManager.persistState === 'function') {
      await this._stateManager.persistState();
      console.log('[MPAI Debug] Persisted state');
    }
    
    // Publish a stop event
    if (this._eventBus) {
      this._eventBus.publish('chat.stopped', {
        timestamp: new Date().toISOString()
      });
      console.log('[MPAI Debug] Published chat.stopped event');
    }
    
    console.log('[MPAI Debug] ChatCore stopped');
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
    console.log('[MPAI Debug] ChatCore.sendMessage called with:', message);
    
    if (!this._apiClient) {
      console.error('[MPAI Debug] APIClient not initialized');
      throw new Error('Chat system not properly initialized');
    }
    
    try {
      // Get the current state
      const state = this._stateManager.getState();
      
      // Send the message to the API
      const response = await this._apiClient.sendMessage(message, {
        conversationId: state?.conversation?.id,
        userLoggedIn: state?.user?.isAuthenticated || false
      });
      
      console.log('[MPAI Debug] Message sent successfully:', response);
      
      // Update the state with the response
      if (response.conversation_id) {
        this._stateManager.setState({
          conversation: {
            id: response.conversation_id
          }
        });
      }
      
      // Add the assistant message to the UI
      if (response.message) {
        this._stateManager.addMessage({
          role: 'assistant',
          content: response.message,
          timestamp: response.timestamp || new Date().toISOString()
        });
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
    console.log('[MPAI Debug] ChatCore.clearHistory called');
    
    if (!this._stateManager) {
      console.error('[MPAI Debug] StateManager not initialized');
      return false;
    }
    
    try {
      // Clear conversation history in state manager
      this._stateManager.clearConversation();
      console.log('[MPAI Debug] Cleared conversation history in state manager');
      
      // Clear conversation on the server if API client is available
      if (this._apiClient && typeof this._apiClient.clearConversation === 'function') {
        await this._apiClient.clearConversation();
        console.log('[MPAI Debug] Cleared conversation history on server');
      }
      
      // Publish a history cleared event
      if (this._eventBus) {
        this._eventBus.publish('chat.history.cleared', {
          timestamp: new Date().toISOString()
        });
        console.log('[MPAI Debug] Published chat.history.cleared event');
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
   * @returns {boolean} The new open state
   */
  toggleChat() {
    console.log('[MPAI Debug] toggleChat method called');
    
    if (!this._stateManager || !this._uiManager) {
      console.error('[MPAI Debug] StateManager or UIManager not initialized');
      return false;
    }
    
    // Get the current UI state
    const uiState = this._stateManager.getState('ui');
    const isChatOpen = uiState?.isChatOpen || false;
    
    // Toggle the chat open state
    const newState = !isChatOpen;
    console.log('[MPAI Debug] Toggling chat state from', isChatOpen, 'to', newState);
    
    // Update the state
    this._stateManager.updateUI({
      isChatOpen: newState
    });
    
    // Also update the UI directly
    if (this._uiManager && typeof this._uiManager.toggleChatVisibility === 'function') {
      this._uiManager.toggleChatVisibility(newState);
      console.log('[MPAI Debug] Called UIManager.toggleChatVisibility with', newState);
    } else {
      console.error('[MPAI Debug] UIManager or toggleChatVisibility not available');
    }
    
    return newState;
  }
}

// Export the ChatCore class
export default ChatCore;