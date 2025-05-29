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
      expandButton: null,
      closeButton: null,
      downloadButton: null,
      commandButton: null,
      commandPanel: null
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
    this._elements.expandButton = container.querySelector('.mpai-chat-expand');
    this._elements.closeButton = container.querySelector('.mpai-chat-close');
    this._elements.downloadButton = container.querySelector('#mpai-download-conversation');
    this._elements.commandButton = container.querySelector('#mpai-run-command');
    this._elements.commandPanel = container.querySelector('#mpai-command-runner');
    
    // Debug: Log the actual elements found
    console.log('[MPAI Debug] Element search results:');
    console.log('[MPAI Debug] clearButton element:', this._elements.clearButton);
    console.log('[MPAI Debug] downloadButton element:', this._elements.downloadButton);
    console.log('[MPAI Debug] commandButton element:', this._elements.commandButton);
    console.log('[MPAI Debug] commandPanel element:', this._elements.commandPanel);
    
    // Continue with the rest of initialization
    this._continueInitialization();
  }

  /**
   * Continues with element initialization
   *
   * @private
   * @returns {void}
   */
  _continueInitialization() {
    // Log which elements were found
    console.log('[MPAI Debug] Message list found:', !!this._elements.messageList);
    console.log('[MPAI Debug] Input form found:', !!this._elements.inputForm);
    console.log('[MPAI Debug] Input field found:', !!this._elements.inputField);
    console.log('[MPAI Debug] Send button found:', !!this._elements.sendButton);
    console.log('[MPAI Debug] Clear button found:', !!this._elements.clearButton);
    console.log('[MPAI Debug] Expand button found:', !!this._elements.expandButton);
    console.log('[MPAI Debug] Close button found:', !!this._elements.closeButton);
    console.log('[MPAI Debug] Download button found:', !!this._elements.downloadButton);
    console.log('[MPAI Debug] Command button found:', !!this._elements.commandButton);
    console.log('[MPAI Debug] Command panel found:', !!this._elements.commandPanel);
    
    // Set up event listeners
    this._setupEventListeners();
    
    // Apply initial state
    const uiState = this._stateManager.getState('ui');
    
    // Also check localStorage directly as a fallback
    let chatOpenFromStorage = false;
    let chatExpandedFromStorage = false;
    
    try {
      const storedChatOpen = localStorage.getItem('mpai_chat_open');
      const storedChatExpanded = localStorage.getItem('mpai_chat_expanded');
      
      chatOpenFromStorage = storedChatOpen === 'true';
      chatExpandedFromStorage = storedChatExpanded === 'true';
      
      console.log('[MPAI Debug] Loaded from localStorage - chatOpen:', chatOpenFromStorage, 'chatExpanded:', chatExpandedFromStorage);
    } catch (e) {
      console.warn('[MPAI Debug] Could not read from localStorage:', e);
    }
    
    if (uiState) {
      // Apply chat open state (prefer state manager, fallback to localStorage)
      const shouldBeOpen = typeof uiState.isChatOpen === 'boolean' ? uiState.isChatOpen : chatOpenFromStorage;
      this.toggleChatVisibility(shouldBeOpen);
      console.log('[MPAI Debug] Applied initial chat visibility:', shouldBeOpen);
      
      // Apply chat expanded state (prefer state manager, fallback to localStorage)
      const shouldBeExpanded = typeof uiState.isExpanded === 'boolean' ? uiState.isExpanded : chatExpandedFromStorage;
      this.toggleChatExpanded(shouldBeExpanded);
      console.log('[MPAI Debug] Applied initial chat expanded state:', shouldBeExpanded);
      
      // Render messages from state
      this.renderMessages();
      console.log('[MPAI Debug] Rendered messages from state');
    } else {
      // No state manager state, use localStorage values
      this.toggleChatVisibility(chatOpenFromStorage);
      this.toggleChatExpanded(chatExpandedFromStorage);
      console.log('[MPAI Debug] Applied localStorage fallback - chatOpen:', chatOpenFromStorage, 'chatExpanded:', chatExpandedFromStorage);
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
    
    // Set up expand button handler
    if (this._elements.expandButton) {
      this._elements.expandButton.addEventListener('click', (event) => {
        this._handleExpand(event);
      });
      console.log('[MPAI Debug] Added click event listener to expand button');
    }
    
    // Set up close button handler
    if (this._elements.closeButton) {
      this._elements.closeButton.addEventListener('click', (event) => {
        this._handleClose(event);
      });
      console.log('[MPAI Debug] Added click event listener to close button');
    }
    
    // Set up download button handler
    if (this._elements.downloadButton) {
      this._elements.downloadButton.addEventListener('click', (event) => {
        this._handleDownload(event);
      });
      console.log('[MPAI Debug] Added click event listener to download button');
    }
    
    // Set up command button handler
    if (this._elements.commandButton) {
      this._elements.commandButton.addEventListener('click', (event) => {
        this._handleCommand(event);
      });
      console.log('[MPAI Debug] Added click event listener to command button');
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
    
    // Add timestamp as data attribute if available (but don't display it)
    if (message.timestamp) {
      messageElement.dataset.timestamp = message.timestamp;
    }
    
    // Create message content element
    const contentElement = document.createElement('div');
    contentElement.className = 'mpai-chat-message-content';
    contentElement.textContent = message.content || '';
    messageElement.appendChild(contentElement);
    
    // Add the message to the message list
    this._elements.messageList.appendChild(messageElement);
    
    // Scroll to the bottom
    this.scrollToBottom(false); // Use instant scroll to reduce visual noise
    
    return messageElement;
  }

  /**
   * Renders all messages in the conversation history
   *
   * @public
   * @returns {void}
   */
  renderMessages() {
    if (!this._elements.messageList) {
      console.error('[MPAI Debug] Message list element not found');
      return;
    }
    
    if (!this._stateManager) {
      console.error('[MPAI Debug] State manager not found');
      return;
    }
    
    // Preserve loading indicator if it exists
    const existingLoading = this._elements.messageList.querySelector('.mpai-chat-loading');
    console.log('[MPAI Debug] renderMessages - Found existing loading indicator:', !!existingLoading);
    
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
    } else {
      return;
    }
    
    // Render each message
    messagesArray.forEach(message => {
      this.renderMessage(message);
    });
    
    // Restore loading indicator if it existed
    if (existingLoading) {
      console.log('[MPAI Debug] renderMessages - Restoring loading indicator');
      this._elements.messageList.appendChild(existingLoading);
    }
  }

  /**
   * Shows the loading indicator
   *
   * @public
   * @returns {void}
   */
  showLoading() {
    console.log('[MPAI Debug] showLoading() called');
    console.log('[MPAI Debug] messageList element:', this._elements.messageList);
    
    if (!this._elements.messageList) {
      console.error('[MPAI Debug] messageList element not found - cannot show loading indicator');
      return;
    }
    
    // Remove any existing loading indicator
    const existing = this._elements.messageList.querySelector('.mpai-chat-loading');
    if (existing) {
      console.log('[MPAI Debug] Removing existing loading indicator');
      existing.remove();
    }
    
    // Create new loading indicator with animated dots
    const loading = document.createElement('div');
    loading.className = 'mpai-chat-loading';
    loading.innerHTML = '<span class="mpai-chat-loading-dot"></span><span class="mpai-chat-loading-dot"></span><span class="mpai-chat-loading-dot"></span>';
    
    console.log('[MPAI Debug] Created STATIC loading element:', loading);
    console.log('[MPAI Debug] Loading element HTML:', loading.outerHTML);
    console.log('[MPAI Debug] Loading element computed styles:', window.getComputedStyle(loading));
    
    // Add to DOM
    this._elements.messageList.appendChild(loading);
    
    // Store the timestamp when loading started for minimum display time
    this._loadingStartTime = Date.now();
    
    // Verify it was added and is visible
    const addedElement = this._elements.messageList.querySelector('.mpai-chat-loading');
    console.log('[MPAI Debug] Loading indicator added to DOM:', !!addedElement);
    console.log('[MPAI Debug] Added element:', addedElement);
    console.log('[MPAI Debug] Element offsetHeight:', addedElement?.offsetHeight);
    console.log('[MPAI Debug] Element offsetWidth:', addedElement?.offsetWidth);
    console.log('[MPAI Debug] Element getBoundingClientRect:', addedElement?.getBoundingClientRect());
    
    // Force a reflow to ensure visibility
    if (addedElement) {
      addedElement.offsetHeight;
    }
    
    // Scroll to bottom to show the loading indicator
    setTimeout(() => {
      this.scrollToBottom(true);
      console.log('[MPAI Debug] Scrolled to show loading indicator');
    }, 50);
  }

  /**
   * Hides the loading indicator
   *
   * @public
   * @returns {void}
   */
  hideLoading() {
    console.log('[MPAI Debug] hideLoading() called');
    console.log('[MPAI Debug] messageList element:', this._elements.messageList);
    
    if (!this._elements.messageList) {
      console.error('[MPAI Debug] messageList element not found - cannot hide loading indicator');
      return;
    }
    
    const loading = this._elements.messageList.querySelector('.mpai-chat-loading');
    console.log('[MPAI Debug] Found loading indicator to remove:', !!loading);
    
    if (loading) {
      // Ensure minimum display time of 800ms for better UX
      const minDisplayTime = 800;
      const elapsedTime = this._loadingStartTime ? Date.now() - this._loadingStartTime : minDisplayTime;
      const remainingTime = Math.max(0, minDisplayTime - elapsedTime);
      
      console.log('[MPAI Debug] Loading elapsed time:', elapsedTime, 'ms, remaining time:', remainingTime, 'ms');
      
      if (remainingTime > 0) {
        console.log('[MPAI Debug] Delaying loading indicator removal by', remainingTime, 'ms');
        setTimeout(() => {
          const stillExists = this._elements.messageList.querySelector('.mpai-chat-loading');
          if (stillExists) {
            console.log('[MPAI Debug] Removing loading indicator after delay:', stillExists);
            stillExists.remove();
            console.log('[MPAI Debug] Loading indicator removed successfully after delay');
          }
        }, remainingTime);
      } else {
        console.log('[MPAI Debug] Removing loading indicator immediately:', loading);
        loading.remove();
        console.log('[MPAI Debug] Loading indicator removed successfully');
      }
    } else {
      console.log('[MPAI Debug] No loading indicator found to remove');
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
    console.log('[MPAI Debug] clearMessages called');
    if (this._elements.messageList) {
      // Clear all messages from the UI
      this._elements.messageList.innerHTML = '';
      console.log('[MPAI Debug] Message list cleared from UI');
      
      
      // Add back the welcome message
      const welcomeMessage = document.createElement('div');
      welcomeMessage.className = 'mpai-chat-welcome';
      welcomeMessage.innerHTML = `
        <div class="mpai-chat-message mpai-chat-message-assistant">
          <div class="mpai-chat-message-content">
            Hello! I'm your MemberPress AI Assistant. How can I help you today?
          </div>
        </div>
      `;
      this._elements.messageList.appendChild(welcomeMessage);
      console.log('[MPAI Debug] Welcome message restored');
    } else {
      console.warn('[MPAI Debug] Message list element not found');
    }
  }

  /**
   * Scrolls to the bottom of the message list
   * 
   * @public
   * @param {boolean} [smooth=true] - Whether to use smooth scrolling
   * @returns {void}
   */
  scrollToBottom(smooth = true) {
    if (this._elements.messageList) {
      this._elements.messageList.scrollTo({
        top: this._elements.messageList.scrollHeight,
        behavior: smooth ? 'smooth' : 'auto'
      });
    }
  }
  
  /**
   * Disables the input field and send button
   *
   * @public
   * @returns {void}
   */
  disableInput() {
    if (this._elements.inputField) {
      this._elements.inputField.disabled = true;
    }
    
    if (this._elements.sendButton) {
      this._elements.sendButton.disabled = true;
    }
  }
  
  /**
   * Enables the input field and send button
   *
   * @public
   * @returns {void}
   */
  enableInput() {
    if (this._elements.inputField) {
      this._elements.inputField.disabled = false;
    }
    
    if (this._elements.sendButton) {
      this._elements.sendButton.disabled = false;
    }
  }

  /**
   * Focuses the input field
   * 
   * @public
   * @returns {void}
   */
  focusInput() {
    if (this._elements.inputField) {
      try {
        this._elements.inputField.focus();
      } catch (error) {
        console.error('[MPAI Debug] Error focusing input field:', error);
      }
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
      
      // Update chat expanded state if needed
      if (typeof state.ui.isExpanded === 'boolean') {
        this.toggleChatExpanded(state.ui.isExpanded);
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
    console.log('[MPAI Debug] About to call showLoading()');
    this.showLoading();
    console.log('[MPAI Debug] showLoading() call completed');
    
    // Disable the input field while processing
    this.disableInput();
    
    // Don't add the user message here - let ChatCore handle it to avoid duplicates
    
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
    event.preventDefault();
    console.log('[MPAI Debug] Clear button clicked');
    
    // DEBUG: Log current state before clearing
    const currentState = this._stateManager.getState();
    console.log('[MPAI Debug] Clear - Current state before clearing:', currentState);
    console.log('[MPAI Debug] Clear - Current messages count:',
      currentState?.conversation?.messages ?
      (Array.isArray(currentState.conversation.messages) ? currentState.conversation.messages.length : Object.keys(currentState.conversation.messages).length) :
      'No messages found');
    
    // Confirm with user before clearing
    if (confirm('Are you sure you want to clear the conversation? This action cannot be undone.')) {
      console.log('[MPAI Debug] Clear - User confirmed, proceeding with clear');
      
      // Clear conversation through ChatCore
      if (window.mpaiChat && typeof window.mpaiChat.clearHistory === 'function') {
        console.log('[MPAI Debug] Clear - Calling window.mpaiChat.clearHistory()');
        window.mpaiChat.clearHistory()
          .then(() => {
            console.log('[MPAI Debug] Clear - Conversation cleared successfully');
            
            // DEBUG: Log state after clearing
            const stateAfterClear = this._stateManager.getState();
            console.log('[MPAI Debug] Clear - State after clearing:', stateAfterClear);
            
            // Clear the UI messages immediately
            this.clearMessages();
            
            // DEBUG: Check if conversation ID changed (this is key for the diagnosis)
            console.log('[MPAI Debug] Clear - Conversation ID before:', currentState?.conversation?.id);
            console.log('[MPAI Debug] Clear - Conversation ID after:', stateAfterClear?.conversation?.id);
            
            if (currentState?.conversation?.id === stateAfterClear?.conversation?.id) {
              console.warn('[MPAI Debug] Clear - WARNING: Conversation ID did not change! This may cause messages to reload.');
            } else {
              console.log('[MPAI Debug] Clear - Good: Conversation ID changed, old messages should not reload.');
            }
          })
          .catch(error => {
            console.error('[MPAI Debug] Error clearing conversation:', error);
            this.showError('Error clearing conversation: ' + (error.message || 'Unknown error'));
          });
      } else {
        console.error('[MPAI Debug] mpaiChat or clearHistory function not available');
        this.showError('Chat system not properly initialized');
      }
    } else {
      console.log('[MPAI Debug] Clear - User cancelled clear operation');
    }
  }
  
  /**
   * Handles expand button click
   *
   * @private
   * @param {Event} event - Click event
   * @returns {void}
   */
  _handleExpand(event) {
    event.preventDefault();
    console.log('[MPAI Debug] Expand button clicked');
    
    // Get the container element
    const container = this._elements.container;
    if (!container) {
      console.error('[MPAI Debug] Chat container not found');
      return;
    }
    
    // Toggle the expanded state
    const isCurrentlyExpanded = container.classList.contains('mpai-chat-expanded');
    const newExpandedState = !isCurrentlyExpanded;
    
    console.log('[MPAI Debug] Toggling expand state from', isCurrentlyExpanded, 'to', newExpandedState);
    
    if (newExpandedState) {
      // Expand the chat
      container.classList.add('mpai-chat-expanded');
      console.log('[MPAI Debug] Chat expanded');
    } else {
      // Collapse the chat
      container.classList.remove('mpai-chat-expanded');
      console.log('[MPAI Debug] Chat collapsed');
    }
    
    // Store expanded state in localStorage for persistence
    try {
      localStorage.setItem('mpai_chat_expanded', newExpandedState ? 'true' : 'false');
    } catch (e) {
      console.warn('[MPAI Debug] Could not save chat expanded state to localStorage:', e);
    }
    
    // Update the state
    if (this._stateManager) {
      this._stateManager.updateUI({
        isExpanded: newExpandedState
      });
      console.log('[MPAI Debug] Updated state with isExpanded:', newExpandedState);
    }
  }
  
  /**
   * Handles close button click
   *
   * @private
   * @param {Event} event - Click event
   * @returns {void}
   */
  _handleClose(event) {
    event.preventDefault();
    console.log('[MPAI Debug] Close button clicked');
    
    // Close the chat interface
    this.toggleChatVisibility(false);
  }
  
  /**
   * Handles download button click
   *
   * @private
   * @param {Event} event - Click event
   * @returns {void}
   */
  _handleDownload(event) {
    event.preventDefault();
    console.log('[MPAI Debug] Download button clicked');
    
    // Get all messages from the state
    const messages = this._stateManager.getState('conversation.messages') || [];
    
    console.log('[MPAI Debug] Download - messages from state:', messages);
    console.log('[MPAI Debug] Download - messages type:', typeof messages);
    console.log('[MPAI Debug] Download - messages length:', Array.isArray(messages) ? messages.length : 'not array');
    
    // DEBUG: Log the entire state for comparison
    const fullState = this._stateManager.getState();
    console.log('[MPAI Debug] Download - Full state manager state:', fullState);
    
    // DEBUG: Also check if we can get messages from DOM as fallback
    const domMessages = document.querySelectorAll('.mpai-chat-message');
    console.log('[MPAI Debug] Download - DOM messages found:', domMessages.length);
    
    // Handle both array and object formats with improved robustness
    let messagesArray = [];
    if (Array.isArray(messages)) {
      messagesArray = messages;
      console.log('[MPAI Debug] Download - Messages are in array format');
    } else if (messages && typeof messages === 'object') {
      // Handle object format (could be indexed object)
      messagesArray = Object.values(messages);
      console.log('[MPAI Debug] Download - Messages converted from object to array');
    } else {
      console.log('[MPAI Debug] Download - Messages are not in expected format:', typeof messages);
    }
    
    // Filter out any invalid messages
    messagesArray = messagesArray.filter(msg =>
      msg &&
      typeof msg === 'object' &&
      msg.content &&
      typeof msg.content === 'string' &&
      msg.content.trim().length > 0
    );
    
    console.log('[MPAI Debug] Download - Final messagesArray:', messagesArray);
    console.log('[MPAI Debug] Download - Final messagesArray length after filtering:', messagesArray.length);
    
    if (messagesArray.length === 0) {
      console.log('[MPAI Debug] Download - No valid messages found, checking DOM fallback...');
      
      // Enhanced DOM fallback check - exclude welcome messages
      const validDomMessages = Array.from(domMessages).filter(el =>
        !el.classList.contains('mpai-chat-welcome') &&
        el.querySelector('.mpai-chat-message-content') &&
        el.querySelector('.mpai-chat-message-content').textContent.trim().length > 0
      );
      
      console.log('[MPAI Debug] Download - Valid DOM messages found:', validDomMessages.length);
      
      if (validDomMessages.length > 0) {
        console.log('[MPAI Debug] Download - Using DOM fallback method');
        this._downloadFromDOM();
        return;
      }
      this.showError('No conversation to download');
      return;
    }
    
    // Create conversation text
    let conversationText = 'MemberPress AI Assistant Conversation\n';
    conversationText += '=====================================\n\n';
    
    messagesArray.forEach((message, index) => {
      const role = message.role === 'user' ? 'You' : 'AI Assistant';
      const timestamp = message.timestamp ? new Date(message.timestamp).toLocaleString() : '';
      conversationText += `${role}${timestamp ? ` (${timestamp})` : ''}:\n`;
      conversationText += `${message.content}\n\n`;
    });
    
    // Create and download file
    const blob = new Blob([conversationText], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `mpai-conversation-${new Date().toISOString().split('T')[0]}.txt`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    
    console.log('[MPAI Debug] Conversation downloaded');
  }
  
  /**
   * Download conversation from DOM as fallback (like old system)
   *
   * @private
   * @returns {void}
   */
  _downloadFromDOM() {
    console.log('[MPAI Debug] Using DOM fallback for download');
    
    const domMessages = document.querySelectorAll('.mpai-chat-message');
    let conversationText = 'MemberPress AI Assistant Conversation\n';
    conversationText += '=====================================\n\n';
    
    let validMessageCount = 0;
    
    domMessages.forEach((messageEl, index) => {
      // Skip welcome messages and empty messages
      if (messageEl.classList.contains('mpai-chat-welcome')) {
        console.log('[MPAI Debug] DOM Download - Skipping welcome message');
        return;
      }
      
      const isUser = messageEl.classList.contains('mpai-chat-message-user');
      const role = isUser ? 'You' : 'AI Assistant';
      const contentEl = messageEl.querySelector('.mpai-chat-message-content');
      const content = contentEl ? contentEl.textContent.trim() : '';
      
      // Skip empty messages
      if (!content || content.length === 0) {
        console.log('[MPAI Debug] DOM Download - Skipping empty message');
        return;
      }
      
      // Get timestamp if available
      const timestamp = messageEl.dataset.timestamp ?
        new Date(messageEl.dataset.timestamp).toLocaleString() : '';
      
      conversationText += `${role}${timestamp ? ` (${timestamp})` : ''}:\n`;
      conversationText += `${content}\n\n`;
      validMessageCount++;
    });
    
    console.log('[MPAI Debug] DOM Download - Valid messages processed:', validMessageCount);
    
    if (validMessageCount === 0) {
      console.log('[MPAI Debug] DOM Download - No valid messages found in DOM');
      this.showError('No valid conversation messages found to download');
      return;
    }
    
    // Create and download file
    const blob = new Blob([conversationText], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `mpai-conversation-${new Date().toISOString().split('T')[0]}.txt`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    
    console.log('[MPAI Debug] Conversation downloaded from DOM with', validMessageCount, 'messages');
  }
  
  /**
   * Handles command button click
   *
   * @private
   * @param {Event} event - Click event
   * @returns {void}
   */
  _handleCommand(event) {
    event.preventDefault();
    console.log('[MPAI Debug] Command button clicked');
    
    // Toggle command panel visibility
    if (this._elements.commandPanel) {
      const isVisible = this._elements.commandPanel.style.display !== 'none';
      this._elements.commandPanel.style.display = isVisible ? 'none' : 'block';
      console.log('[MPAI Debug] Command panel toggled to:', !isVisible ? 'visible' : 'hidden');
      
      // Set up command item handlers if panel is now visible
      if (!isVisible) {
        this._setupCommandHandlers();
      }
    } else {
      console.warn('[MPAI Debug] Command panel not found');
    }
  }
  
  /**
   * Sets up event handlers for command items
   *
   * @private
   * @returns {void}
   */
  _setupCommandHandlers() {
    const commandItems = this._elements.commandPanel.querySelectorAll('.mpai-command-item');
    const commandClose = this._elements.commandPanel.querySelector('.mpai-command-close');
    
    // Set up command item click handlers
    commandItems.forEach(item => {
      item.addEventListener('click', (event) => {
        event.preventDefault();
        const command = item.dataset.command;
        if (command) {
          console.log('[MPAI Debug] Command selected:', command);
          
          // Insert command into input field
          if (this._elements.inputField) {
            this._elements.inputField.value = command;
            this._elements.inputField.focus();
          }
          
          // Hide command panel
          this._elements.commandPanel.style.display = 'none';
        }
      });
    });
    
    // Set up close button handler
    if (commandClose) {
      commandClose.addEventListener('click', (event) => {
        event.preventDefault();
        this._elements.commandPanel.style.display = 'none';
        console.log('[MPAI Debug] Command panel closed');
      });
    }
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
  
  /**
   * Toggles the expanded state of the chat interface
   *
   * @public
   * @param {boolean} [isExpanded] - Force expanded state to this value if provided
   * @returns {boolean} The new expanded state
   */
  toggleChatExpanded(isExpanded) {
    console.log('[MPAI Debug] toggleChatExpanded called with:', isExpanded);
    
    // Get the container element
    const container = this._elements.container;
    if (!container) {
      console.error('[MPAI Debug] Chat container not found');
      return false;
    }
    
    // If isExpanded is not provided, toggle the current state
    let newExpandedState = isExpanded;
    if (typeof newExpandedState !== 'boolean') {
      const isCurrentlyExpanded = container.classList.contains('mpai-chat-expanded');
      newExpandedState = !isCurrentlyExpanded;
      console.log('[MPAI Debug] Toggling expanded state from', isCurrentlyExpanded, 'to', newExpandedState);
    }
    
    // Update the container classes
    if (newExpandedState) {
      // Expand the chat
      container.classList.add('mpai-chat-expanded');
      console.log('[MPAI Debug] Chat expanded');
    } else {
      // Collapse the chat
      container.classList.remove('mpai-chat-expanded');
      console.log('[MPAI Debug] Chat collapsed');
    }
    
    // Store expanded state in localStorage for persistence
    try {
      localStorage.setItem('mpai_chat_expanded', newExpandedState ? 'true' : 'false');
    } catch (e) {
      console.warn('[MPAI Debug] Could not save chat expanded state to localStorage:', e);
    }
    
    // Update the state
    if (this._stateManager) {
      this._stateManager.updateUI({
        isExpanded: newExpandedState
      });
      console.log('[MPAI Debug] Updated state with isExpanded:', newExpandedState);
    }
    
    return newExpandedState;
  }
}

// Export the UIManager class
export default UIManager;