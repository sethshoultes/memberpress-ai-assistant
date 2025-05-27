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
    this._elements.inputForm = container.querySelector('.mpai-chat-input-container');
    this._elements.inputField = container.querySelector('.mpai-chat-input');
    this._elements.sendButton = container.querySelector('.mpai-chat-submit');
    this._elements.clearButton = container.querySelector('#mpai-clear-conversation');
    
    // Create loading indicator if it doesn't exist
    let loadingIndicator = container.querySelector('.mpai-chat-loading');
    if (!loadingIndicator) {
      loadingIndicator = document.createElement('div');
      loadingIndicator.className = 'mpai-chat-loading';
      loadingIndicator.innerHTML = '<div class="mpai-chat-loading-spinner"></div>';
      loadingIndicator.style.display = 'none';
      container.querySelector('.mpai-chat-messages').appendChild(loadingIndicator);
      console.log('[MPAI Debug] Created missing loading indicator');
    }
    this._elements.loadingIndicator = loadingIndicator;
    
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
      
      // Render messages from state
      this.renderMessages();
      console.log('[MPAI Debug] Rendered messages from state');
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
      // Create a submit event handler for the input container
      const handleSubmit = (event) => {
        if (event.type === 'keydown' && event.key !== 'Enter') {
          return;
        }
        if (event.type === 'keydown' && event.shiftKey) {
          return; // Allow shift+enter for new line
        }
        if (event.type === 'keydown') {
          event.preventDefault(); // Prevent default for Enter key
        }
        this._handleSubmit(event);
      };
      
      // Add click handler to send button
      if (this._elements.sendButton) {
        this._elements.sendButton.addEventListener('click', handleSubmit);
        console.log('[MPAI Debug] Added click event listener to send button');
      }
      
      // Add keydown handler to input field
      if (this._elements.inputField) {
        this._elements.inputField.addEventListener('keydown', (event) => {
          if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            handleSubmit(event);
          }
        });
        console.log('[MPAI Debug] Added keydown event listener to input field');
      }
      
      console.log('[MPAI Debug] Set up submit handlers for input');
    }
    
    // Set up clear button handler
    if (this._elements.clearButton) {
      this._elements.clearButton.addEventListener('click', (event) => {
        this._handleClear(event);
      });
      console.log('[MPAI Debug] Added click event listener to clear button');
    }
    
    // Set up chat button handler with multiple selectors
    const chatButtonSelectors = [
      '.mpai-chat-toggle',
      '#mpai-chat-toggle',
      '[aria-label="Toggle chat"]',
      '.mpai-chat-button'
    ];
    
    let chatButtonFound = false;
    
    for (const selector of chatButtonSelectors) {
      const chatButton = document.querySelector(selector);
      if (chatButton) {
        // Remove any existing event listeners to prevent duplicates
        chatButton.removeEventListener('click', this._chatButtonClickHandler);
        
        // Create a bound handler function
        this._chatButtonClickHandler = (event) => {
          event.preventDefault();
          event.stopPropagation();
          console.log(`[MPAI Debug] Chat button clicked (found with selector: ${selector})`);
          
          // Get current visibility state
          const container = this._elements.container;
          const isCurrentlyVisible = container && !container.classList.contains('mpai-chat-hidden') && container.style.display !== 'none';
          
          // Toggle to opposite state
          this.toggleChatVisibility(!isCurrentlyVisible);
        };
        
        chatButton.addEventListener('click', this._chatButtonClickHandler);
        console.log(`[MPAI Debug] Added click event listener to chat button with selector: ${selector}`);
        chatButtonFound = true;
        break;
      }
    }
    
    if (!chatButtonFound) {
      console.warn('[MPAI Debug] Chat button not found with any selector');
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
    console.log('[MPAI Debug] Rendering message:', message);
    
    if (!this._elements.messageList) {
      console.error('[MPAI Debug] Message list element not found');
      return null;
    }
    
    // Create message element
    const messageElement = document.createElement('div');
    messageElement.className = `mpai-chat-message mpai-chat-message-${message.role || 'assistant'}`;
    
    // Add message ID as data attribute if available
    if (message.id) {
      messageElement.dataset.messageId = message.id;
    }
    
    // Add timestamp as data attribute if available
    if (message.timestamp) {
      messageElement.dataset.timestamp = message.timestamp;
      
      // Add a human-readable timestamp
      const date = new Date(message.timestamp);
      const timeString = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
      
      const timestampElement = document.createElement('div');
      timestampElement.className = 'mpai-chat-message-timestamp';
      timestampElement.textContent = timeString;
      messageElement.appendChild(timestampElement);
    }
    
    // Create message content element
    const contentElement = document.createElement('div');
    contentElement.className = 'mpai-chat-message-content';
    contentElement.textContent = message.content || '';
    messageElement.appendChild(contentElement);
    
    // Add the message to the message list
    this._elements.messageList.appendChild(messageElement);
    
    // Scroll to the bottom
    this.scrollToBottom();
    
    console.log('[MPAI Debug] Message rendered');
    
    return messageElement;
  }

  /**
   * Renders all messages in the conversation history
   *
   * @public
   * @returns {void}
   */
  renderMessages() {
    console.log('[MPAI Debug] Rendering all messages');
    
    if (!this._elements.messageList) {
      console.error('[MPAI Debug] Message list element not found');
      return;
    }
    
    if (!this._stateManager) {
      console.error('[MPAI Debug] State manager not found');
      return;
    }
    
    // Clear the message list
    this._elements.messageList.innerHTML = '';
    
    // Get all messages from the state
    const messages = this._stateManager.getState('conversation.messages') || [];
    
    // Ensure messages is an array before trying to iterate
    let messagesArray = [];
    if (Array.isArray(messages)) {
      messagesArray = messages;
    } else if (messages && typeof messages === 'object') {
      // Convert object to array if it's an object with numeric keys
      messagesArray = Object.values(messages);
      console.warn('[MPAI Debug] Messages was an object, converted to array:', messagesArray);
    } else {
      console.warn('[MPAI Debug] Messages is not an array or object:', typeof messages, messages);
      return;
    }
    
    // Render each message
    messagesArray.forEach(message => {
      this.renderMessage(message);
    });
    
    console.log('[MPAI Debug] All messages rendered:', messagesArray.length);
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
    console.log('[MPAI Debug] Updating UI from state:', state);
    
    // Check if conversation messages have changed
    if (state && state.conversation && state.conversation.messages) {
      // Render all messages
      this.renderMessages();
    }
    
    // Check if UI state has changed
    if (state && state.ui) {
      // Update chat visibility if needed
      if (typeof state.ui.isChatOpen === 'boolean') {
        this.toggleChatVisibility(state.ui.isChatOpen);
      }
    }
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
    
    // Add the user message to the state and UI
    if (this._stateManager && typeof this._stateManager.addMessage === 'function') {
      this._stateManager.addMessage({
        role: 'user',
        content: message,
        timestamp: new Date().toISOString()
      });
      console.log('[MPAI Debug] Added user message to state');
    } else {
      console.warn('[MPAI Debug] StateManager or addMessage function not available');
      // Fallback to event bus
      if (this._eventBus) {
        this._eventBus.publish('message.user', { content: message });
        console.log('[MPAI Debug] Published message.user event');
      }
    }
    
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
      // Check for any visibility class or style
      const isCurrentlyHidden =
        container.classList.contains('mpai-chat-hidden') ||
        container.style.display === 'none' ||
        container.style.visibility === 'hidden' ||
        container.style.opacity === '0';
      
      newVisibility = isCurrentlyHidden;
      console.log('[MPAI Debug] Toggling visibility from hidden:', isCurrentlyHidden, 'to visible:', newVisibility);
    }
    
    // Update the container style and classes
    if (newVisibility) {
      // Make visible
      container.classList.remove('mpai-chat-hidden');
      container.classList.add('mpai-chat-visible');
      container.style.display = 'flex';
      container.style.visibility = 'visible';
      container.style.opacity = '1';
      console.log('[MPAI Debug] Chat made visible');
      
      // Also update the toggle button if it exists
      const toggleButton = document.querySelector('#mpai-chat-toggle, .mpai-chat-toggle');
      if (toggleButton) {
        toggleButton.classList.add('active');
        toggleButton.setAttribute('aria-expanded', 'true');
      }
    } else {
      // Hide
      container.classList.remove('mpai-chat-visible');
      container.classList.add('mpai-chat-hidden');
      container.style.display = 'none';
      console.log('[MPAI Debug] Chat hidden');
      
      // Also update the toggle button if it exists
      const toggleButton = document.querySelector('#mpai-chat-toggle, .mpai-chat-toggle');
      if (toggleButton) {
        toggleButton.classList.remove('active');
        toggleButton.setAttribute('aria-expanded', 'false');
      }
    }
    
    // Store visibility in localStorage for persistence
    try {
      localStorage.setItem('mpai_chat_open', newVisibility ? 'true' : 'false');
    } catch (e) {
      console.warn('[MPAI Debug] Could not save chat state to localStorage:', e);
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