/**
 * UIManager - Manages all UI-related functionality for the chat interface
 * 
 * This module is responsible for all UI-related functionality, including
 * message rendering, UI controls, and input handling. It provides a clean
 * separation between the UI and the underlying application logic.
 * 
 * @module UIManager
 * @author MemberPress
 * @since 1.0.0
 */

/**
 * UIManager class - Manages UI interactions and rendering
 * 
 * @class
 */
class UIManager {
  /**
   * Creates a new UIManager instance
   * 
   * @constructor
   * @param {Object} config - Configuration options for the UI
   * @param {StateManager} stateManager - State manager instance
   * @param {EventBus} eventBus - Event bus instance
   */
  constructor(config = {}, stateManager, eventBus) {
    /**
     * Configuration options
     * @type {Object}
     * @private
     */
    this._config = config;
    
    /**
     * State manager instance
     * @type {StateManager}
     * @private
     */
    this._stateManager = stateManager;
    
    /**
     * Event bus instance
     * @type {EventBus}
     * @private
     */
    this._eventBus = eventBus;
    
    /**
     * DOM element references
     * @type {Object}
     * @private
     */
    this._elements = {
      container: null,
      messageList: null,
      inputForm: null,
      inputField: null,
      sendButton: null,
      clearButton: null,
      loadingIndicator: null
    };
  }

  /**
   * Initializes the UI manager and sets up DOM elements
   * 
   * @public
   * @param {string|HTMLElement} containerSelector - Container element or selector
   * @returns {Promise<void>} A promise that resolves when initialization is complete
   */
  async initialize(containerSelector) {
    console.log('[MPAI Debug] UIManager.initialize called with:', containerSelector);
    
    // Get the container element
    let container;
    if (typeof containerSelector === 'string') {
      container = document.querySelector(containerSelector);
    } else if (containerSelector instanceof HTMLElement) {
      container = containerSelector;
    }
    
    if (!container) {
      console.error('[MPAI Debug] Chat container not found:', containerSelector);
      return false;
    }
    
    console.log('[MPAI Debug] Chat container found');
    
    // Store references to DOM elements
    this._elements.container = container;
    this._elements.messageList = container.querySelector('.mpai-chat-messages');
    this._elements.inputForm = container.querySelector('.mpai-chat-input-form');
    this._elements.inputField = container.querySelector('.mpai-chat-input');
    this._elements.sendButton = container.querySelector('.mpai-chat-send-button');
    this._elements.clearButton = container.querySelector('.mpai-chat-clear-button');
    this._elements.loadingIndicator = container.querySelector('.mpai-chat-loading');
    
    // Log which elements were found
    console.log('[MPAI Debug] Message list found:', !!this._elements.messageList);
    console.log('[MPAI Debug] Input form found:', !!this._elements.inputForm);
    console.log('[MPAI Debug] Input field found:', !!this._elements.inputField);
    console.log('[MPAI Debug] Send button found:', !!this._elements.sendButton);
    console.log('[MPAI Debug] Clear button found:', !!this._elements.clearButton);
    console.log('[MPAI Debug] Loading indicator found:', !!this._elements.loadingIndicator);
    
    // Set up event listeners
    this._setupEventListeners();
    
    // Apply initial state
    const uiState = this._stateManager.getState('ui');
    if (uiState) {
      // Apply chat open state
      this.toggleChatVisibility(uiState.isChatOpen);
      console.log('[MPAI Debug] Applied initial chat visibility:', uiState.isChatOpen);
    }
    
    console.log('[MPAI Debug] UIManager initialized');
    return true;
  }

  /**
   * Sets up event listeners for UI interactions
   * 
   * @private
   * @returns {void}
   */
  _setupEventListeners() {
    console.log('[MPAI Debug] UIManager._setupEventListeners called');
    
    // Set up form submission handler
    if (this._elements.inputForm) {
      this._elements.inputForm.addEventListener('submit', (event) => {
        this._handleSubmit(event);
      });
      console.log('[MPAI Debug] Added submit event listener to input form');
    }
    
    // Set up clear button handler
    if (this._elements.clearButton) {
      this._elements.clearButton.addEventListener('click', (event) => {
        this._handleClear(event);
      });
      console.log('[MPAI Debug] Added click event listener to clear button');
    }
    
    // Set up chat button handler with the correct selector
    const chatButton = document.querySelector('.mpai-chat-toggle');
    if (chatButton) {
      chatButton.addEventListener('click', () => {
        console.log('[MPAI Debug] Chat button clicked in UIManager');
        // Publish an event that the chat button was clicked
        if (this._eventBus) {
          this._eventBus.publish('ui.button.click', { button: 'chat-toggle' });
          console.log('[MPAI Debug] Published ui.button.click event');
        }
        // Also toggle the chat visibility directly
        this.toggleChatVisibility();
      });
      console.log('[MPAI Debug] Added click event listener to chat button with class .mpai-chat-toggle');
    } else {
      console.warn('[MPAI Debug] Chat button with class .mpai-chat-toggle not found');
      
      // Try alternative selectors
      const alternativeSelectors = ['#mpai-chat-toggle', '[aria-label="Toggle chat"]'];
      for (const selector of alternativeSelectors) {
        const altButton = document.querySelector(selector);
        if (altButton) {
          altButton.addEventListener('click', () => {
            console.log(`[MPAI Debug] Chat button clicked (found with selector: ${selector})`);
            this.toggleChatVisibility();
          });
          console.log(`[MPAI Debug] Added click event listener to chat button with selector: ${selector}`);
          break;
        }
      }
    }
    
    // Subscribe to state changes
    if (this._eventBus) {
      this._eventBus.subscribe('state.ui.changed', (data) => {
        console.log('[MPAI Debug] State UI changed event received:', data);
        this.updateFromState(data.state, data.previousState);
      });
      console.log('[MPAI Debug] Subscribed to state.ui.changed event');
    }
    
    console.log('[MPAI Debug] Event listeners set up');
  }

  /**
   * Renders the chat interface
   * 
   * @public
   * @returns {void}
   */
  render() {
    // Render the chat interface
  }

  /**
   * Renders a single message in the chat interface
   * 
   * @public
   * @param {Object} message - Message object to render
   * @returns {HTMLElement} The rendered message element
   */
  renderMessage(message) {
    // Render a message
  }

  /**
   * Renders all messages in the conversation history
   * 
   * @public
   * @returns {void}
   */
  renderMessages() {
    // Render all messages
  }

  /**
   * Shows the loading indicator
   * 
   * @public
   * @returns {void}
   */
  showLoading() {
    console.log('[MPAI Debug] showLoading called');
    if (this._elements.loadingIndicator) {
      this._elements.loadingIndicator.style.display = 'block';
      console.log('[MPAI Debug] Loading indicator shown');
    } else {
      console.warn('[MPAI Debug] Loading indicator element not found');
    }
  }

  /**
   * Hides the loading indicator
   *
   * @public
   * @returns {void}
   */
  hideLoading() {
    console.log('[MPAI Debug] hideLoading called');
    if (this._elements.loadingIndicator) {
      this._elements.loadingIndicator.style.display = 'none';
      console.log('[MPAI Debug] Loading indicator hidden');
    } else {
      console.warn('[MPAI Debug] Loading indicator element not found');
    }
  }

  /**
   * Shows an error message
   *
   * @public
   * @param {string} message - Error message to display
   * @returns {void}
   */
  showError(message) {
    console.log('[MPAI Debug] showError called with:', message);
    
    // Create an error message element
    const errorElement = document.createElement('div');
    errorElement.className = 'mpai-chat-error';
    errorElement.textContent = message;
    
    // Add it to the message list
    if (this._elements.messageList) {
      this._elements.messageList.appendChild(errorElement);
      console.log('[MPAI Debug] Error message added to message list');
      
      // Scroll to the bottom
      this.scrollToBottom();
      
      // Remove the error message after 5 seconds
      setTimeout(() => {
        if (errorElement.parentNode) {
          errorElement.parentNode.removeChild(errorElement);
          console.log('[MPAI Debug] Error message removed after timeout');
        }
      }, 5000);
    } else {
      console.warn('[MPAI Debug] Message list element not found');
      // Fallback to console error
      console.error('[MPAI Chat Error]', message);
    }
  }

  /**
   * Clears the input field
   * 
   * @public
   * @returns {void}
   */
  clearInput() {
    // Clear input field
  }

  /**
   * Clears the message list
   * 
   * @public
   * @returns {void}
   */
  clearMessages() {
    // Clear message list
  }

  /**
   * Scrolls to the bottom of the message list
   * 
   * @public
   * @param {boolean} [smooth=true] - Whether to use smooth scrolling
   * @returns {void}
   */
  scrollToBottom(smooth = true) {
    console.log('[MPAI Debug] scrollToBottom called with smooth:', smooth);
    
    if (this._elements.messageList) {
      this._elements.messageList.scrollTo({
        top: this._elements.messageList.scrollHeight,
        behavior: smooth ? 'smooth' : 'auto'
      });
      console.log('[MPAI Debug] Scrolled to bottom of message list');
    } else {
      console.warn('[MPAI Debug] Message list element not found');
    }
  }
  
  /**
   * Disables the input field and send button
   *
   * @public
   * @returns {void}
   */
  disableInput() {
    console.log('[MPAI Debug] disableInput called');
    
    if (this._elements.inputField) {
      this._elements.inputField.disabled = true;
      console.log('[MPAI Debug] Input field disabled');
    } else {
      console.warn('[MPAI Debug] Input field element not found');
    }
    
    if (this._elements.sendButton) {
      this._elements.sendButton.disabled = true;
      console.log('[MPAI Debug] Send button disabled');
    } else {
      console.warn('[MPAI Debug] Send button element not found');
    }
  }
  
  /**
   * Enables the input field and send button
   *
   * @public
   * @returns {void}
   */
  enableInput() {
    console.log('[MPAI Debug] enableInput called');
    
    if (this._elements.inputField) {
      this._elements.inputField.disabled = false;
      console.log('[MPAI Debug] Input field enabled');
    } else {
      console.warn('[MPAI Debug] Input field element not found');
    }
    
    if (this._elements.sendButton) {
      this._elements.sendButton.disabled = false;
      console.log('[MPAI Debug] Send button enabled');
    } else {
      console.warn('[MPAI Debug] Send button element not found');
    }
  }

  /**
   * Focuses the input field
   * 
   * @public
   * @returns {void}
   */
  focusInput() {
    console.log('[MPAI Debug] focusInput called');
    
    if (this._elements.inputField) {
      try {
        this._elements.inputField.focus();
        console.log('[MPAI Debug] Input field focused');
      } catch (error) {
        console.error('[MPAI Debug] Error focusing input field:', error);
      }
    } else {
      console.warn('[MPAI Debug] Input field not found');
    }
  }

  /**
   * Updates the UI based on state changes
   * 
   * @public
   * @param {Object} state - New state
   * @param {Object} previousState - Previous state
   * @returns {void}
   */
  updateFromState(state, previousState) {
    // Update UI from state
  }

  /**
   * Handles form submission
   * 
   * @private
   * @param {Event} event - Form submission event
   * @returns {void}
   */
  _handleSubmit(event) {
    // Prevent default form submission
    event.preventDefault();
    
    console.log('[MPAI Debug] Form submitted');
    
    // Get the input field
    const inputField = this._elements.inputField;
    if (!inputField) {
      console.error('[MPAI Debug] Input field not found');
      return;
    }
    
    // Get the message text
    const message = inputField.value.trim();
    if (!message) {
      console.log('[MPAI Debug] Empty message, not submitting');
      return;
    }
    
    console.log('[MPAI Debug] Submitting message:', message);
    
    // Clear the input field
    inputField.value = '';
    
    // Resize the input field if auto-resize is implemented
    if (typeof this._autoResize === 'function') {
      this._autoResize();
    }
    
    // Show loading indicator
    this.showLoading();
    
    // Disable the input field while processing
    this.disableInput();
    
    // Add the user message to the UI
    this._eventBus.publish('message.user', { content: message });
    
    // Send the message to the API
    if (window.mpaiChat && typeof window.mpaiChat.sendMessage === 'function') {
      window.mpaiChat.sendMessage(message)
        .then(response => {
          console.log('[MPAI Debug] Message sent successfully:', response);
          
          // Hide loading indicator
          this.hideLoading();
          
          // Enable the input field
          this.enableInput();
          
          // Focus the input field
          this.focusInput();
        })
        .catch(error => {
          console.error('[MPAI Debug] Error sending message:', error);
          
          // Hide loading indicator
          this.hideLoading();
          
          // Enable the input field
          this.enableInput();
          
          // Focus the input field
          this.focusInput();
          
          // Show error message
          this.showError('Error sending message: ' + (error.message || 'Unknown error'));
        });
    } else {
      console.error('[MPAI Debug] mpaiChat or sendMessage function not available');
      
      // Hide loading indicator
      this.hideLoading();
      
      // Enable the input field
      this.enableInput();
      
      // Focus the input field
      this.focusInput();
      
      // Show error message
      this.showError('Chat system not properly initialized');
      
      // Publish an error event
      this._eventBus.publish('error', {
        message: 'Chat system not properly initialized',
        source: 'UIManager._handleSubmit'
      });
    }
  }

  /**
   * Handles clear button click
   * 
   * @private
   * @param {Event} event - Click event
   * @returns {void}
   */
  _handleClear(event) {
    // Handle clear button click
  }
  
  /**
   * Toggles the visibility of the chat interface
   *
   * @public
   * @param {boolean} [isVisible] - Force visibility to this value if provided
   * @returns {boolean} The new visibility state
   */
  toggleChatVisibility(isVisible) {
    console.log('[MPAI Debug] toggleChatVisibility called with:', isVisible);
    
    // Get the container element
    const container = this._elements.container;
    if (!container) {
      console.error('[MPAI Debug] Chat container not found');
      return false;
    }
    
    // If isVisible is not provided, toggle the current state
    let newVisibility = isVisible;
    if (typeof newVisibility !== 'boolean') {
      const currentVisibility = !container.classList.contains('mpai-chat-hidden');
      newVisibility = !currentVisibility;
      console.log('[MPAI Debug] Toggling visibility from', currentVisibility, 'to', newVisibility);
    }
    
    // Update the container class
    if (newVisibility) {
      container.classList.remove('mpai-chat-hidden');
      container.classList.add('mpai-chat-visible');
      console.log('[MPAI Debug] Chat made visible');
    } else {
      container.classList.remove('mpai-chat-visible');
      container.classList.add('mpai-chat-hidden');
      console.log('[MPAI Debug] Chat hidden');
    }
    
    // Update the state
    if (this._stateManager) {
      this._stateManager.updateUI({
        isChatOpen: newVisibility
      });
      console.log('[MPAI Debug] Updated state with isChatOpen:', newVisibility);
    }
    
    // Focus the input field if the chat is now visible
    if (newVisibility) {
      this.focusInput();
    }
    
    return newVisibility;
  }
}

// Export the UIManager class
export default UIManager;