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
    // Stop the chat interface
  }

  /**
   * Sends a message through the chat interface
   * 
   * @public
   * @param {string} message - The message to send
   * @returns {Promise<Object>} A promise that resolves with the response
   */
  async sendMessage(message) {
    // Send a message
  }

  /**
   * Clears the chat history
   * 
   * @public
   * @returns {Promise<void>} A promise that resolves when the history is cleared
   */
  async clearHistory() {
    // Clear chat history
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