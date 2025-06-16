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

// Import message system components
import MessageFactory from '../messages/message-factory.js';
import MessageRenderer from '../messages/message-renderer.js';

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
    
    // Message system components
    this._messageFactory = null;
    this._messageRenderer = null;
  }

  /**
   * Initializes the UI manager and sets up DOM elements
   * 
   * @public
   * @param {string|HTMLElement} containerSelector - Container element or selector
   * @returns {Promise<void>} A promise that resolves when initialization is complete
   */
  async initialize(containerSelector) {
    // Initialization logging only for critical errors
    
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
    
    // Debug message removed - was appearing in admin interface
    
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
    // Debug message removed - was appearing in admin interface
    // Element detection logging removed - creates excessive console noise
    
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
    // Element detection logging removed - creates excessive console noise
    
    // Initialize message system
    this._initializeMessageSystem();
    
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
      
      // Storage state logging removed - creates excessive console noise
    } catch (e) {
      console.warn('[MPAI Debug] Could not read from localStorage:', e);
    }
    
    if (uiState) {
      // Apply chat open state (prefer state manager, fallback to localStorage)
      const shouldBeOpen = typeof uiState.isChatOpen === 'boolean' ? uiState.isChatOpen : chatOpenFromStorage;
      this.toggleChatVisibility(shouldBeOpen);
      // Initial state logging removed - creates excessive console noise
      
      // Apply chat expanded state (prefer state manager, fallback to localStorage)
      const shouldBeExpanded = typeof uiState.isExpanded === 'boolean' ? uiState.isExpanded : chatExpandedFromStorage;
      this.toggleChatExpanded(shouldBeExpanded);
      // Initial state logging removed - creates excessive console noise
      
      // Render messages from state
      this.renderMessages();
      // Debug message removed - was appearing in admin interface
    } else {
      // No state manager state, use localStorage values
      this.toggleChatVisibility(chatOpenFromStorage);
      this.toggleChatExpanded(chatExpandedFromStorage);
      // Fallback state logging removed - creates excessive console noise
    }
    
    // Debug message removed - was appearing in admin interface
    return true;
  }

  /**
   * Sets up event listeners for UI interactions
   * 
   * @private
   * @returns {void}
   */
  _setupEventListeners() {
    // Debug message removed - was appearing in admin interface
    
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
        // Debug message removed - was appearing in admin interface
      }
      
      // Add keydown handler to input field
      if (this._elements.inputField) {
        this._elements.inputField.addEventListener('keydown', (event) => {
          if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            handleSubmit(event);
          }
        });
        // Debug message removed - was appearing in admin interface
      }
      
      // Debug message removed - was appearing in admin interface
    }
    
    // Set up clear button handler
    if (this._elements.clearButton) {
      this._elements.clearButton.addEventListener('click', (event) => {
        this._handleClear(event);
      });
      // Debug message removed - was appearing in admin interface
    }
    
    // Set up expand button handler
    if (this._elements.expandButton) {
      this._elements.expandButton.addEventListener('click', (event) => {
        this._handleExpand(event);
      });
      // Debug message removed - was appearing in admin interface
    }
    
    // Set up close button handler
    if (this._elements.closeButton) {
      this._elements.closeButton.addEventListener('click', (event) => {
        this._handleClose(event);
      });
      // Debug message removed - was appearing in admin interface
    }
    
    // Set up download button handler
    if (this._elements.downloadButton) {
      this._elements.downloadButton.addEventListener('click', (event) => {
        this._handleDownload(event);
      });
      // Debug message removed - was appearing in admin interface
    }
    
    // Set up command button handler
    if (this._elements.commandButton) {
      this._elements.commandButton.addEventListener('click', (event) => {
        this._handleCommand(event);
      });
      // Debug message removed - was appearing in admin interface
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
          // Chat button click logging removed - creates excessive console noise
          
          // Get current visibility state
          const container = this._elements.container;
          const isCurrentlyVisible = container && !container.classList.contains('mpai-chat-hidden') && container.style.display !== 'none';
          
          // Toggle to opposite state
          this.toggleChatVisibility(!isCurrentlyVisible);
        };
        
        chatButton.addEventListener('click', this._chatButtonClickHandler);
        // Event listener setup logging removed - creates excessive console noise
        chatButtonFound = true;
        break;
      }
    }
    
    if (!chatButtonFound) {
      // Debug message removed - was appearing in admin interface
    }
    
    // Subscribe to state changes
    if (this._eventBus) {
      this._eventBus.subscribe('state.ui.changed', (data) => {
        // State change event logging removed - creates excessive console noise
        this.updateFromState(data.state, data.previousState);
      });
      // Debug message removed - was appearing in admin interface
    }
    
    // Debug message removed - was appearing in admin interface
  }

  /**
   * Initialize the message system.
   * @private
   */
  _initializeMessageSystem() {
    try {
      // Debug message removed - was appearing in admin interface
      
      // Create message factory
      this._messageFactory = new MessageFactory({
        handlerRegistry: {},
        eventBus: this._eventBus
      });

      // Create message renderer
      this._messageRenderer = new MessageRenderer({
        messageFactory: this._messageFactory,
        eventBus: this._eventBus,
        stateManager: this._stateManager
      });

      // Initialize message factory
      this._messageFactory.initialize();

      // Initialize message renderer with messages container
      if (this._elements.messageList) {
        this._messageRenderer.initialize(this._elements.messageList);
      }

      // Debug message removed - was appearing in admin interface
    } catch (error) {
      console.error('[MPAI Debug] Error initializing message system:', error);
      // Continue without message system - fallback to basic rendering
    }
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
      // Debug message removed - was appearing in admin interface
      return null;
    }
    
    // Check if this is a blog post message and we have the message system available
    const content = message.content || '';
    const isBlogPost = content.includes('<wp-post>') || content.includes('<post-title>') || content.includes('<post-content>');
    
    if (isBlogPost && this._messageRenderer) {
      // Debug message removed - was appearing in admin interface
      try {
        // Use the message renderer for blog posts
        const renderedElement = this._messageRenderer.renderMessage(message);
        if (renderedElement) {
          // Scroll to the bottom
          this.scrollToBottom(false);
          return renderedElement;
        }
      } catch (error) {
        console.error('[MPAI Debug] Error rendering blog post with message system:', error);
        // Fall back to basic rendering
      }
    }
    
    // Basic rendering for non-blog posts or when message system is not available
    // Debug message removed - was appearing in admin interface
    
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
    
    // Check if content contains HTML and handle appropriately
    const containsHtml = /<[^>]+>/.test(content);
    
    if (containsHtml && message.role === 'assistant') {
      // For assistant messages with HTML, sanitize and render as HTML
      // Debug message removed - was appearing in admin interface
      const sanitizedHtml = this._sanitizeHtml(content);
      contentElement.innerHTML = sanitizedHtml;
    } else {
      // For user messages or plain text, use textContent for security
      // Debug message removed - was appearing in admin interface
      contentElement.textContent = content;
    }
    
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
      // Debug message removed - was appearing in admin interface
      return;
    }
    
    if (!this._stateManager) {
      // Debug message removed - was appearing in admin interface
      return;
    }
    
    // Preserve loading indicator if it exists
    const existingLoading = this._elements.messageList.querySelector('.mpai-chat-loading');
    // Loading indicator detection logging removed - creates excessive console noise
    
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
      // Debug message removed - was appearing in admin interface
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
    // Debug message removed - was appearing in admin interface
    // Element availability logging removed - creates excessive console noise
    
    if (!this._elements.messageList) {
      // Debug message removed - was appearing in admin interface
      return;
    }
    
    // Remove any existing loading indicator
    const existing = this._elements.messageList.querySelector('.mpai-chat-loading');
    if (existing) {
      // Debug message removed - was appearing in admin interface
      existing.remove();
    }
    
    // Create new loading indicator with animated dots
    const loading = document.createElement('div');
    loading.className = 'mpai-chat-loading';
    loading.innerHTML = '<span class="mpai-chat-loading-dot"></span><span class="mpai-chat-loading-dot"></span><span class="mpai-chat-loading-dot"></span>';
    
    // Loading element creation logging removed - creates excessive console noise
    
    // Add to DOM
    this._elements.messageList.appendChild(loading);
    
    // Store the timestamp when loading started for minimum display time
    this._loadingStartTime = Date.now();
    
    // Verify it was added and is visible
    const addedElement = this._elements.messageList.querySelector('.mpai-chat-loading');
    // Loading indicator DOM verification logging removed - creates excessive console noise
    
    // Force a reflow to ensure visibility
    if (addedElement) {
      addedElement.offsetHeight;
    }
    
    // Scroll to bottom to show the loading indicator
    setTimeout(() => {
      this.scrollToBottom(true);
      // Debug message removed - was appearing in admin interface
    }, 50);
  }

  /**
   * Hides the loading indicator
   *
   * @public
   * @returns {void}
   */
  hideLoading() {
    // Debug message removed - was appearing in admin interface
    // Element availability logging removed - creates excessive console noise
    
    if (!this._elements.messageList) {
      // Debug message removed - was appearing in admin interface
      return;
    }
    
    const loading = this._elements.messageList.querySelector('.mpai-chat-loading');
    // Loading indicator removal logging removed - creates excessive console noise
    
    if (loading) {
      // Ensure minimum display time of 800ms for better UX
      const minDisplayTime = 800;
      const elapsedTime = this._loadingStartTime ? Date.now() - this._loadingStartTime : minDisplayTime;
      const remainingTime = Math.max(0, minDisplayTime - elapsedTime);
      
      // Loading timing logging removed - creates excessive console noise
      
      if (remainingTime > 0) {
        // Loading delay logging removed - creates excessive console noise
        setTimeout(() => {
          const stillExists = this._elements.messageList.querySelector('.mpai-chat-loading');
          if (stillExists) {
            // Loading removal logging removed - creates excessive console noise
            stillExists.remove();
            // Debug message removed - was appearing in admin interface
          }
        }, remainingTime);
      } else {
        // Loading removal logging removed - creates excessive console noise
        loading.remove();
        // Debug message removed - was appearing in admin interface
      }
    } else {
      // Debug message removed - was appearing in admin interface
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
    // Error display logging removed - creates excessive console noise
    
    // Create an error message element
    const errorElement = document.createElement('div');
    errorElement.className = 'mpai-chat-error';
    errorElement.textContent = message;
    
    // Add it to the message list
    if (this._elements.messageList) {
      this._elements.messageList.appendChild(errorElement);
      // Debug message removed - was appearing in admin interface
      
      // Scroll to the bottom
      this.scrollToBottom();
      
      // Remove the error message after 5 seconds
      setTimeout(() => {
        if (errorElement.parentNode) {
          errorElement.parentNode.removeChild(errorElement);
          // Debug message removed - was appearing in admin interface
        }
      }, 5000);
    } else {
      // Debug message removed - was appearing in admin interface
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
    // Debug message removed - was appearing in admin interface
    if (this._elements.messageList) {
      // Clear all messages from the UI
      this._elements.messageList.innerHTML = '';
      // Debug message removed - was appearing in admin interface
      
      
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
      // Debug message removed - was appearing in admin interface
    } else {
      // Debug message removed - was appearing in admin interface
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
    
    // Debug message removed - was appearing in admin interface
    
    // Get the input field
    const inputField = this._elements.inputField;
    if (!inputField) {
      // Debug message removed - was appearing in admin interface
      return;
    }
    
    // Get the message text
    const message = inputField.value.trim();
    if (!message) {
      // Debug message removed - was appearing in admin interface
      return;
    }
    
    // Message submission logging removed - creates excessive console noise
    
    // Clear the input field
    inputField.value = '';
    
    // Resize the input field if auto-resize is implemented
    if (typeof this._autoResize === 'function') {
      this._autoResize();
    }
    
    // Show loading indicator
    // Debug message removed - was appearing in admin interface
    this.showLoading();
    // Debug message removed - was appearing in admin interface
    
    // Disable the input field while processing
    this.disableInput();
    
    // Don't add the user message here - let ChatCore handle it to avoid duplicates
    
    // Send the message to the API
    if (window.mpaiChat && typeof window.mpaiChat.sendMessage === 'function') {
      window.mpaiChat.sendMessage(message)
        .then(response => {
          // Message success logging removed - creates excessive console noise
          
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
      // Debug message removed - was appearing in admin interface
      
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
    // Debug message removed - was appearing in admin interface
    
    // DEBUG: Log current state before clearing
    const currentState = this._stateManager.getState();
    // State debugging for clear operation - keep for troubleshooting
    if (window.mpai_chat_config?.debug) {
      console.log('[MPAI Debug] Clear - Current messages count:',
        currentState?.conversation?.messages ?
        (Array.isArray(currentState.conversation.messages) ? currentState.conversation.messages.length : Object.keys(currentState.conversation.messages).length) :
        'No messages found');
    }
    
    // Confirm with user before clearing
    if (confirm('Are you sure you want to clear the conversation? This action cannot be undone.')) {
      // Debug message removed - was appearing in admin interface
      
      // Clear conversation through ChatCore
      if (window.mpaiChat && typeof window.mpaiChat.clearHistory === 'function') {
        // Debug message removed - was appearing in admin interface
        window.mpaiChat.clearHistory()
          .then(() => {
            // Debug message removed - was appearing in admin interface
            
            // DEBUG: Log state after clearing
            const stateAfterClear = this._stateManager.getState();
            // State after clear logging removed - creates excessive console noise
            
            // Clear the UI messages immediately
            this.clearMessages();
            
            // DEBUG: Check if conversation ID changed (this is key for the diagnosis)
            // Conversation ID change logging - keep for troubleshooting
            if (window.mpai_chat_config?.debug && currentState?.conversation?.id !== stateAfterClear?.conversation?.id) {
              console.log('[MPAI Debug] Clear - Conversation ID changed successfully');
            }
            
            if (currentState?.conversation?.id === stateAfterClear?.conversation?.id) {
              // Debug message removed - was appearing in admin interface
            } else {
              // Debug message removed - was appearing in admin interface
            }
          })
          .catch(error => {
            console.error('[MPAI Debug] Error clearing conversation:', error);
            this.showError('Error clearing conversation: ' + (error.message || 'Unknown error'));
          });
      } else {
        // Debug message removed - was appearing in admin interface
        this.showError('Chat system not properly initialized');
      }
    } else {
      // Debug message removed - was appearing in admin interface
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
    // Debug message removed - was appearing in admin interface
    
    // Get the container element
    const container = this._elements.container;
    if (!container) {
      // Debug message removed - was appearing in admin interface
      return;
    }
    
    // Toggle the expanded state
    const isCurrentlyExpanded = container.classList.contains('mpai-chat-expanded');
    const newExpandedState = !isCurrentlyExpanded;
    
    // Expand state toggle logging removed - creates excessive console noise
    
    if (newExpandedState) {
      // Expand the chat
      container.classList.add('mpai-chat-expanded');
      // Debug message removed - was appearing in admin interface
    } else {
      // Collapse the chat
      container.classList.remove('mpai-chat-expanded');
      // Debug message removed - was appearing in admin interface
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
      // State update logging removed - creates excessive console noise
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
    // Debug message removed - was appearing in admin interface
    
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
    // Debug message removed - was appearing in admin interface
    
    // Get all messages from the state
    const messages = this._stateManager.getState('conversation.messages') || [];
    
    // Download operation logging - only log in debug mode
    const domMessages = document.querySelectorAll('.mpai-chat-message');
    if (window.mpai_chat_config?.debug) {
      console.log('[MPAI Debug] Download - Messages available:', Array.isArray(messages) ? messages.length : 'not array', 'DOM messages:', domMessages.length);
    }
    
    // Handle both array and object formats with improved robustness
    let messagesArray = [];
    if (Array.isArray(messages)) {
      messagesArray = messages;
      // Debug message removed - was appearing in admin interface
    } else if (messages && typeof messages === 'object') {
      // Handle object format (could be indexed object)
      messagesArray = Object.values(messages);
      // Debug message removed - was appearing in admin interface
    } else if (window.mpai_chat_config?.debug) {
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
    
    // Final message count logging removed - creates excessive console noise
    
    if (messagesArray.length === 0) {
      // Debug message removed - was appearing in admin interface
      
      // Enhanced DOM fallback check - exclude welcome messages
      const validDomMessages = Array.from(domMessages).filter(el =>
        !el.classList.contains('mpai-chat-welcome') &&
        el.querySelector('.mpai-chat-message-content') &&
        el.querySelector('.mpai-chat-message-content').textContent.trim().length > 0
      );
      
      // DOM message validation logging removed - creates excessive console noise
      
      if (validDomMessages.length > 0) {
        // Debug message removed - was appearing in admin interface
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
    
    // Debug message removed - was appearing in admin interface
  }
  
  /**
   * Download conversation from DOM as fallback (like old system)
   *
   * @private
   * @returns {void}
   */
  _downloadFromDOM() {
    // Debug message removed - was appearing in admin interface
    
    const domMessages = document.querySelectorAll('.mpai-chat-message');
    let conversationText = 'MemberPress AI Assistant Conversation\n';
    conversationText += '=====================================\n\n';
    
    let validMessageCount = 0;
    
    domMessages.forEach((messageEl, index) => {
      // Skip welcome messages and empty messages
      if (messageEl.classList.contains('mpai-chat-welcome')) {
        // Debug message removed - was appearing in admin interface
        return;
      }
      
      const isUser = messageEl.classList.contains('mpai-chat-message-user');
      const role = isUser ? 'You' : 'AI Assistant';
      const contentEl = messageEl.querySelector('.mpai-chat-message-content');
      const content = contentEl ? contentEl.textContent.trim() : '';
      
      // Skip empty messages
      if (!content || content.length === 0) {
        // Debug message removed - was appearing in admin interface
        return;
      }
      
      // Get timestamp if available
      const timestamp = messageEl.dataset.timestamp ?
        new Date(messageEl.dataset.timestamp).toLocaleString() : '';
      
      conversationText += `${role}${timestamp ? ` (${timestamp})` : ''}:\n`;
      conversationText += `${content}\n\n`;
      validMessageCount++;
    });
    
    // DOM download processing logging removed - creates excessive console noise
    
    if (validMessageCount === 0) {
      // Debug message removed - was appearing in admin interface
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
    
    // DOM download completion logging removed - creates excessive console noise
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
    // Debug message removed - was appearing in admin interface
    
    // Toggle command panel visibility
    if (this._elements.commandPanel) {
      const isVisible = this._elements.commandPanel.style.display !== 'none';
      this._elements.commandPanel.style.display = isVisible ? 'none' : 'block';
      // Command panel toggle logging removed - creates excessive console noise
      
      // Set up command item handlers if panel is now visible
      if (!isVisible) {
        this._setupCommandHandlers();
      }
    } else {
      // Debug message removed - was appearing in admin interface
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
          // Command selection logging removed - creates excessive console noise
          
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
        // Debug message removed - was appearing in admin interface
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
    // Chat visibility toggle logging removed - creates excessive console noise
    
    // Get the container element
    const container = this._elements.container;
    if (!container) {
      // Debug message removed - was appearing in admin interface
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
      // Visibility state change logging removed - creates excessive console noise
    }
    
    // Update the container style and classes
    if (newVisibility) {
      // Make visible
      container.classList.remove('mpai-chat-hidden');
      container.classList.add('mpai-chat-visible');
      container.style.display = 'flex';
      container.style.visibility = 'visible';
      container.style.opacity = '1';
      // Debug message removed - was appearing in admin interface
      
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
      // Debug message removed - was appearing in admin interface
      
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
      // State update logging removed - creates excessive console noise
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
    // Chat expanded toggle logging removed - creates excessive console noise
    
    // Get the container element
    const container = this._elements.container;
    if (!container) {
      // Debug message removed - was appearing in admin interface
      return false;
    }
    
    // If isExpanded is not provided, toggle the current state
    let newExpandedState = isExpanded;
    if (typeof newExpandedState !== 'boolean') {
      const isCurrentlyExpanded = container.classList.contains('mpai-chat-expanded');
      newExpandedState = !isCurrentlyExpanded;
      // Expanded state change logging removed - creates excessive console noise
    }
    
    // Update the container classes
    if (newExpandedState) {
      // Expand the chat
      container.classList.add('mpai-chat-expanded');
      // Debug message removed - was appearing in admin interface
    } else {
      // Collapse the chat
      container.classList.remove('mpai-chat-expanded');
      // Debug message removed - was appearing in admin interface
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
      // State update logging removed - creates excessive console noise
    }
    
    return newExpandedState;
  }

  /**
   * Sanitizes HTML content to prevent XSS while allowing safe formatting elements
   *
   * @private
   * @param {string} html - The HTML content to sanitize
   * @returns {string} The sanitized HTML
   */
  _sanitizeHtml(html) {
    // Create a temporary DOM element to parse and sanitize the HTML
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = html;
    
    // Define allowed tags and attributes
    const allowedTags = [
      'div', 'span', 'p', 'br', 'strong', 'b', 'em', 'i', 'u',
      'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
      'ul', 'ol', 'li',
      'table', 'thead', 'tbody', 'tr', 'th', 'td',
      'a', 'code', 'pre', 'blockquote',
      // Blog post XML tags
      'wp-post', 'post-title', 'post-content', 'post-excerpt',
      'post-status', 'post-type', 'block'
    ];
    
    const allowedAttributes = {
      'a': ['href', 'target', 'rel'],
      'table': ['class'],
      'div': ['class'],
      'span': ['class'],
      'th': ['class'],
      'td': ['class'],
      'tr': ['class'],
      'thead': ['class'],
      'tbody': ['class'],
      'h1': ['class'],
      'h2': ['class'],
      'h3': ['class'],
      'h4': ['class'],
      'h5': ['class'],
      'h6': ['class'],
      'p': ['class'],
      'ul': ['class'],
      'ol': ['class'],
      'li': ['class'],
      'code': ['class'],
      'pre': ['class'],
      'blockquote': ['class'],
      // Blog post XML tag attributes
      'wp-post': ['class'],
      'post-title': ['class'],
      'post-content': ['class'],
      'post-excerpt': ['class'],
      'post-status': ['class'],
      'post-type': ['class'],
      'block': ['type', 'level', 'class']
    };
    
    // Recursively sanitize elements
    this._sanitizeElement(tempDiv, allowedTags, allowedAttributes);
    
    return tempDiv.innerHTML;
  }
  
  /**
   * Recursively sanitizes a DOM element and its children
   *
   * @private
   * @param {Element} element - The element to sanitize
   * @param {Array} allowedTags - Array of allowed tag names
   * @param {Object} allowedAttributes - Object mapping tag names to allowed attributes
   */
  _sanitizeElement(element, allowedTags, allowedAttributes) {
    // Get all child elements
    const children = Array.from(element.children);
    
    children.forEach(child => {
      const tagName = child.tagName.toLowerCase();
      
      // Remove disallowed tags
      if (!allowedTags.includes(tagName)) {
        // HTML sanitization logging removed - creates excessive console noise
        child.remove();
        return;
      }
      
      // Remove disallowed attributes
      const allowedAttrs = allowedAttributes[tagName] || [];
      const attributes = Array.from(child.attributes);
      
      attributes.forEach(attr => {
        if (!allowedAttrs.includes(attr.name)) {
          // Attribute sanitization logging removed - creates excessive console noise
          child.removeAttribute(attr.name);
        }
      });
      
      // Sanitize href attributes for links
      if (tagName === 'a' && child.hasAttribute('href')) {
        const href = child.getAttribute('href');
        // Only allow http, https, and mailto links
        if (!href.match(/^(https?:\/\/|mailto:)/i)) {
          // Unsafe href logging removed - creates excessive console noise
          child.removeAttribute('href');
        }
      }
      
      // Recursively sanitize children
      this._sanitizeElement(child, allowedTags, allowedAttributes);
    });
  }
}

// Export the UIManager class
export default UIManager;