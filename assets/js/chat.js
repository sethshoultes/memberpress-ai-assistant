/**
 * MemberPress AI Assistant Chat Interface
 * 
 * This file contains the main logic for the chat interface component.
 * It handles user interactions, message sending/receiving, and UI updates.
 */

(function() {
    'use strict';

    /**
     * Chat Interface Class
     * 
     * Manages the chat interface functionality and state.
     */
    class MPAIChat {
        /**
         * Constructor
         * 
         * @param {Object} options Configuration options
         */
        constructor(options = {}) {
            // Default configuration
            this.config = {
                apiEndpoint: options.apiEndpoint || '/wp-json/memberpress-ai/v1/chat',
                maxMessages: options.maxMessages || 50,
                typingDelay: options.typingDelay || 0, // Milliseconds delay between message chunks
                autoOpen: options.autoOpen || false,
                debug: options.debug || false
            };

            // DOM elements
            this.elements = {
                container: document.getElementById('mpai-chat-container'),
                messagesContainer: document.getElementById('mpai-chat-messages'),
                input: document.getElementById('mpai-chat-input'),
                submitButton: document.getElementById('mpai-chat-submit'),
                toggleButton: document.getElementById('mpai-chat-toggle'),
                closeButton: document.getElementById('mpai-chat-close'),
                headerContainer: document.getElementById('mpai-chat-header'),
                expandButton: document.getElementById('mpai-chat-expand')
            };
            
            // Add clear history button to header
            if (this.elements.headerContainer) {
                const clearButton = document.createElement('button');
                clearButton.id = 'mpai-clear-history';
                clearButton.className = 'mpai-clear-history-button';
                clearButton.innerHTML = '<span class="dashicons dashicons-trash"></span>';
                clearButton.title = 'Clear chat history';
                this.elements.headerContainer.appendChild(clearButton);
                this.elements.clearButton = clearButton;
            }

            // State
            this.state = {
                isOpen: false,
                isProcessing: false,
                conversationId: null,
                messages: [],
                lastUserMessage: null,
                isExpanded: false
            };

            // Initialize
            this.init();
        }

        /**
         * Initialize the chat interface
         */
        init() {
            this.log('Initializing chat interface');
            
            // Bind event listeners
            this.bindEvents();
            
            // Check if user is logged in (WordPress sets this global variable)
            this.isLoggedIn = typeof window.mpai_user_logged_in !== 'undefined' && window.mpai_user_logged_in === true;
            this.log('User logged in status:', this.isLoggedIn);
            
            // For logged-in users, we'll get the conversation ID from the server
            // For guests, we'll use localStorage
            if (this.isLoggedIn) {
                // We'll set the conversation ID when we load the history
                this.loadConversationHistory();
            } else {
                // Try to load conversation ID from localStorage for guests
                const savedConversationId = localStorage.getItem('mpai_conversation_id');
                if (savedConversationId) {
                    this.log('Loaded conversation ID from localStorage:', savedConversationId);
                    this.state.conversationId = savedConversationId;
                    // Load conversation history with this ID
                    this.loadConversationHistory();
                } else {
                    // Generate a new conversation ID if not found in localStorage
                    this.state.conversationId = this.generateConversationId();
                    // Save the new conversation ID to localStorage
                    localStorage.setItem('mpai_conversation_id', this.state.conversationId);
                    this.log('Generated and saved new conversation ID:', this.state.conversationId);
                    // No need to load history for a new conversation
                }
            }
            
            // Try to load chat state from localStorage
            const chatWasOpen = localStorage.getItem('mpai_chat_open') === 'true';
            if (chatWasOpen || this.config.autoOpen) {
                this.openChat();
            }
            
            // Check if chat was expanded in previous session
            const chatWasExpanded = localStorage.getItem('mpai_chat_expanded') === 'true';
            if (chatWasExpanded) {
                this.state.isExpanded = true;
                this.elements.container.classList.add('mpai-chat-expanded');
                if (this.elements.expandButton) {
                    this.elements.expandButton.innerHTML = '<span class="dashicons dashicons-editor-contract"></span>';
                    this.elements.expandButton.setAttribute('aria-label', 'Collapse chat');
                    this.elements.expandButton.setAttribute('title', 'Collapse chat');
                }
            }
            
            // Enable auto-resize for the input field
            this.setupInputAutoResize();
            
            this.log('Chat interface initialized');
        }
        
        /**
         * Load conversation history from the server
         */
        loadConversationHistory() {
            this.log('Loading conversation history');
            
            // Prepare request data
            const requestData = {
                message: '',
                load_history: true,
                user_logged_in: this.isLoggedIn
            };
            
            // Add conversation ID if we have one
            if (this.state.conversationId) {
                requestData.conversation_id = this.state.conversationId;
                this.log('Including conversation ID in history request:', this.state.conversationId);
            }
            
            // Send an empty message to get the conversation history
            fetch(this.config.apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': window.mpai_nonce || '' // WP REST API nonce
                },
                body: JSON.stringify(requestData),
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                // Add debug logging for history loading
                if (this.config.debug) {
                    console.log('[MPAI Chat] History load response:', data);
                    console.log('[MPAI Chat] History available in load response:',
                        data && data.history ? 'YES' : 'NO',
                        data && data.history ? `(${data.history.length} items)` : '');
                }
                
                // Update conversation ID if provided by the server
                if (data && data.conversation_id) {
                    this.log('Server provided conversation ID:', data.conversation_id);
                    this.state.conversationId = data.conversation_id;
                    
                    // Save to localStorage for guests
                    if (!this.isLoggedIn) {
                        localStorage.setItem('mpai_conversation_id', this.state.conversationId);
                        this.log('Saved conversation ID to localStorage');
                    }
                }
                
                // Process history if available
                if (data && data.history && Array.isArray(data.history) && data.history.length > 0) {
                    this.processConversationHistory(data.history);
                } else {
                    this.log('No history available or history is empty');
                }
            })
            .catch(error => {
                this.log('Error loading conversation history:', error);
            });
        }

        /**
         * Bind event listeners
         */
        bindEvents() {
            // Toggle chat visibility
            if (this.elements.toggleButton) {
                this.elements.toggleButton.addEventListener('click', () => this.toggleChat());
            }
            
            // Close chat
            if (this.elements.closeButton) {
                this.elements.closeButton.addEventListener('click', () => this.closeChat());
            }
            
            // Toggle expand state
            if (this.elements.expandButton) {
                this.elements.expandButton.addEventListener('click', () => this.toggleExpand());
            }
            
            // Submit message on button click
            if (this.elements.submitButton) {
                this.elements.submitButton.addEventListener('click', () => this.sendMessage());
            }
            
            // Submit message on Enter key (but allow Shift+Enter for new lines)
            if (this.elements.input) {
                this.elements.input.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        this.sendMessage();
                    }
                });
            }
            
            // Clear history button
            if (this.elements.clearButton) {
                this.elements.clearButton.addEventListener('click', () => this.clearHistory());
            }
            
            // Scroll to bottom when messages container changes
            if (this.elements.messagesContainer) {
                const observer = new MutationObserver(() => this.scrollToBottom());
                observer.observe(this.elements.messagesContainer, { childList: true, subtree: true });
            }
        }

        /**
         * Toggle chat visibility
         */
        toggleChat() {
            if (this.state.isOpen) {
                this.closeChat();
            } else {
                this.openChat();
            }
        }

        /**
         * Open the chat interface
         */
        openChat() {
            if (this.state.isOpen) return;
            
            this.elements.container.classList.add('active');
            this.elements.toggleButton.classList.add('active');
            this.state.isOpen = true;
            
            // Save chat state to localStorage
            localStorage.setItem('mpai_chat_open', 'true');
            
            // Focus the input field
            setTimeout(() => {
                this.elements.input.focus();
            }, 300);
            
            this.scrollToBottom();
            
            // Trigger custom event
            this.triggerEvent('mpai:chat:opened');
        }

        /**
         * Close the chat interface
         */
        closeChat() {
            if (!this.state.isOpen) return;
            
            this.elements.container.classList.remove('active');
            this.elements.toggleButton.classList.remove('active');
            this.state.isOpen = false;
            
            // Save chat state to localStorage
            localStorage.setItem('mpai_chat_open', 'false');
            
            // Trigger custom event
            this.triggerEvent('mpai:chat:closed');
        }

        /**
         * Send a message to the AI assistant
         */
        sendMessage() {
            // Get message text and trim whitespace
            const messageText = this.elements.input.value.trim();
            
            // Don't send empty messages
            if (!messageText || this.state.isProcessing) {
                return;
            }
            
            // Add user message to the chat
            this.addUserMessage(messageText);
            
            // Clear the input field
            this.elements.input.value = '';
            this.elements.input.style.height = 'auto';
            
            // Show loading indicator
            this.showLoadingIndicator();
            
            // Set processing state
            this.state.isProcessing = true;
            this.state.lastUserMessage = messageText;
            
            // Disable input and button during processing
            this.elements.input.disabled = true;
            this.elements.submitButton.disabled = true;
            
            // Send the message to the backend
            this.processMessage(messageText)
                .then(response => {
                    // Hide loading indicator
                    this.hideLoadingIndicator();
                    
                    // Add assistant message to the chat
                    if (response && response.message) {
                        this.addAssistantMessage(response.message);
                    } else {
                        this.addAssistantMessage('Sorry, I encountered an error processing your request.');
                    }
                    
                    // Update conversation ID if provided
                    if (response && response.conversation_id) {
                        this.state.conversationId = response.conversation_id;
                    }
                })
                .catch(error => {
                    this.log('Error processing message:', error);
                    
                    // Hide loading indicator
                    this.hideLoadingIndicator();
                    
                    // Add error message
                    this.addAssistantMessage('Sorry, I encountered an error processing your request. Please try again later.');
                })
                .finally(() => {
                    // Reset processing state
                    this.state.isProcessing = false;
                    
                    // Re-enable input and button
                    this.elements.input.disabled = false;
                    this.elements.submitButton.disabled = false;
                    
                    // Focus the input field
                    this.elements.input.focus();
                });
        }

        /**
         * Process a message by sending it to the backend
         * 
         * @param {string} message The message to process
         * @returns {Promise} Promise resolving to the response
         */
        processMessage(message) {
            return new Promise((resolve, reject) => {
                // Prepare request data
                const requestData = {
                    message: message,
                    conversation_id: this.state.conversationId,
                    user_logged_in: this.isLoggedIn
                };
                
                // Send request to the backend
                fetch(this.config.apiEndpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': window.mpai_nonce || '' // WP REST API nonce
                    },
                    body: JSON.stringify(requestData),
                    credentials: 'same-origin'
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    // Add debug logging for history data
                    if (this.config.debug) {
                        console.log('[MPAI Chat] Response data:', data);
                        console.log('[MPAI Chat] History available:',
                            data && data.history ? 'YES' : 'NO',
                            data && data.history ? `(${data.history.length} items)` : '');
                    }
                    
                    // Only process history when explicitly loading history, not after sending a message
                    // This prevents duplicate messages in the UI
                    if (data && data.history && Array.isArray(data.history) && data.history.length > 0 && message === '') {
                        this.log('Processing history from response (history load request)');
                        this.processConversationHistory(data.history);
                    } else if (data && data.history) {
                        this.log('Skipping history processing after sending message to avoid duplicates');
                    }
                    
                    resolve(data);
                })
                .catch(error => {
                    this.log('API request error:', error);
                    reject(error);
                });
            });
        }

        /**
         * Add a user message to the chat
         *
         * @param {string} message The message text
         * @param {boolean} addToState Whether to add the message to the state (default: true)
         */
        addUserMessage(message, addToState = true) {
            const messageElement = this.createMessageElement('user', message);
            this.elements.messagesContainer.appendChild(messageElement);
            
            // Add to messages array if requested
            if (addToState) {
                this.state.messages.push({
                    role: 'user',
                    content: message,
                    timestamp: new Date().toISOString()
                });
                
                // Prune messages if exceeding max
                this.pruneMessages();
            }
            
            // Scroll to bottom
            this.scrollToBottom();
        }

        /**
         * Add an assistant message to the chat
         *
         * @param {string} message The message text
         * @param {boolean} addToState Whether to add the message to the state (default: true)
         */
        addAssistantMessage(message, addToState = true) {
            const messageElement = this.createMessageElement('assistant', message);
            this.elements.messagesContainer.appendChild(messageElement);
            
            // Add to messages array if requested
            if (addToState) {
                this.state.messages.push({
                    role: 'assistant',
                    content: message,
                    timestamp: new Date().toISOString()
                });
                
                // Prune messages if exceeding max
                this.pruneMessages();
            }
            
            // Scroll to bottom
            this.scrollToBottom();
        }

        /**
         * Create a message element
         *
         * @param {string} role The message role ('user' or 'assistant')
         * @param {string} content The message content
         * @returns {HTMLElement} The message element
         */
        createMessageElement(role, content) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `mpai-chat-message mpai-chat-message-${role}`;
            
            const contentDiv = document.createElement('div');
            contentDiv.className = 'mpai-chat-message-content';
            
            // Process message content based on role
            if (role === 'user') {
                // For user messages, just format plain text with auto-linking
                if (window.MPAITextFormatter) {
                    contentDiv.innerHTML = window.MPAITextFormatter.formatPlainText(content);
                } else {
                    contentDiv.textContent = content;
                }
            } else {
                // For assistant messages, apply full formatting
                
                // Check if content contains an HTML table or div (from TableFormatter)
                if ((content.includes('<table') && content.includes('</table>')) ||
                    (content.includes('<div class="mpai-table-container"') && content.includes('</div>'))) {
                    // For HTML tables, add a small delay to ensure proper rendering
                    setTimeout(() => {
                        // Direct HTML content - use as is without processing
                        contentDiv.innerHTML = content;
                        
                        // Initialize any scripts that might be in the HTML content
                        const scripts = contentDiv.getElementsByTagName('script');
                        for (let i = 0; i < scripts.length; i++) {
                            const script = scripts[i];
                            const newScript = document.createElement('script');
                            newScript.textContent = script.textContent;
                            script.parentNode.replaceChild(newScript, script);
                        }
                    }, 100); // 100ms delay
                }
                // Check for XML content
                else if (window.MPAIXMLProcessor && window.MPAIXMLProcessor.containsXML(content)) {
                    // Process XML content
                    contentDiv.innerHTML = window.MPAIXMLProcessor.processMessage(content);
                } else if (window.MPAITextFormatter) {
                    // Process markdown formatting
                    contentDiv.innerHTML = window.MPAITextFormatter.formatText(content, {
                        allowHtml: true,
                        syntaxHighlighting: true
                    });
                } else {
                    // Fallback to plain text if modules not available
                    contentDiv.textContent = content;
                }
            }
            
            messageDiv.appendChild(contentDiv);
            
            return messageDiv;
        }

        /**
         * Show the loading indicator
         */
        showLoadingIndicator() {
            // Create loading indicator element
            const loadingDiv = document.createElement('div');
            loadingDiv.className = 'mpai-chat-loading';
            loadingDiv.id = 'mpai-chat-loading';
            
            // Add loading dots
            for (let i = 0; i < 3; i++) {
                const dot = document.createElement('div');
                dot.className = 'mpai-chat-loading-dot';
                loadingDiv.appendChild(dot);
            }
            
            // Add to messages container
            this.elements.messagesContainer.appendChild(loadingDiv);
            
            // Scroll to bottom
            this.scrollToBottom();
        }

        /**
         * Hide the loading indicator
         */
        hideLoadingIndicator() {
            const loadingElement = document.getElementById('mpai-chat-loading');
            if (loadingElement) {
                loadingElement.remove();
            }
        }

        /**
         * Scroll the messages container to the bottom
         */
        scrollToBottom() {
            if (this.elements.messagesContainer) {
                this.elements.messagesContainer.scrollTop = this.elements.messagesContainer.scrollHeight;
            }
        }

        /**
         * Prune messages if exceeding the maximum
         */
        pruneMessages() {
            if (this.state.messages.length > this.config.maxMessages) {
                // Remove oldest messages
                const excessCount = this.state.messages.length - this.config.maxMessages;
                this.state.messages.splice(0, excessCount);
                
                // Also remove from DOM
                const messageElements = this.elements.messagesContainer.querySelectorAll('.mpai-chat-message');
                for (let i = 0; i < excessCount && i < messageElements.length; i++) {
                    messageElements[i].remove();
                }
            }
        }

        /**
         * Set up auto-resize for the input field
         */
        setupInputAutoResize() {
            if (!this.elements.input) return;
            
            this.elements.input.addEventListener('input', () => {
                // Reset height to auto to get the correct scrollHeight
                this.elements.input.style.height = 'auto';
                
                // Set new height based on scrollHeight (with max height limit)
                const newHeight = Math.min(this.elements.input.scrollHeight, 120);
                this.elements.input.style.height = `${newHeight}px`;
            });
        }

        /**
         * Generate a unique conversation ID
         * 
         * @returns {string} A unique conversation ID
         */
        generateConversationId() {
            return 'conv_' + Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
        }

        /**
         * Trigger a custom event
         * 
         * @param {string} eventName The name of the event
         * @param {Object} detail Additional event details
         */
        triggerEvent(eventName, detail = {}) {
            const event = new CustomEvent(eventName, {
                bubbles: true,
                detail: {
                    ...detail,
                    chat: this
                }
            });
            
            document.dispatchEvent(event);
        }

        /**
         * Log a message to the console (if debug is enabled)
         * 
         * @param {...any} args Arguments to log
         */
        log(...args) {
            if (this.config.debug) {
                console.log('[MPAI Chat]', ...args);
            }
        }
        
        /**
         * Process conversation history from the server
         *
         * @param {Array} history Array of message objects
         */
        processConversationHistory(history) {
            // Skip if no history or empty history
            if (!history || !Array.isArray(history) || history.length === 0) {
                return;
            }
            
            this.log('Processing conversation history', history);
            
            // Clear existing messages in the UI
            this.elements.messagesContainer.innerHTML = '';
            
            // Clear existing messages in state
            this.state.messages = [];
            
            // Add each message to the chat
            history.forEach(message => {
                if (message.role === 'user') {
                    // Add user message
                    this.addUserMessage(message.content, false);
                } else if (message.role === 'assistant') {
                    // Add assistant message
                    this.addAssistantMessage(message.content, false);
                }
            });
            
            // Scroll to bottom
            this.scrollToBottom();
        }
        
        /**
         * Clear the chat history
         */
        clearHistory() {
            // Confirm with the user
            if (!confirm('Are you sure you want to clear your chat history?')) {
                return;
            }
            
            this.log('Clearing chat history');
            
            // Clear UI
            this.elements.messagesContainer.innerHTML = '';
            
            // Clear state
            this.state.messages = [];
            
            // For logged-in users, send a request to clear history on the server
            if (this.isLoggedIn) {
                fetch(this.config.apiEndpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': window.mpai_nonce || ''
                    },
                    body: JSON.stringify({
                        clear_history: true,
                        conversation_id: this.state.conversationId,
                        user_logged_in: true
                    }),
                    credentials: 'same-origin'
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    this.log('History cleared on server:', data);
                    
                    // Generate a new conversation ID
                    this.state.conversationId = this.generateConversationId();
                    this.log('Generated new conversation ID:', this.state.conversationId);
                })
                .catch(error => {
                    this.log('Error clearing history:', error);
                });
            } else {
                // For guests, just clear localStorage and generate a new conversation ID
                localStorage.removeItem('mpai_conversation_id');
                this.state.conversationId = this.generateConversationId();
                localStorage.setItem('mpai_conversation_id', this.state.conversationId);
                this.log('Generated new conversation ID for guest:', this.state.conversationId);
            }
        }
        
        /**
         * Toggle the expanded state of the chat interface
         */
        toggleExpand() {
            this.state.isExpanded = !this.state.isExpanded;
            
            if (this.state.isExpanded) {
                this.elements.container.classList.add('mpai-chat-expanded');
                this.elements.expandButton.innerHTML = '<span class="dashicons dashicons-editor-contract"></span>';
                this.elements.expandButton.setAttribute('aria-label', 'Collapse chat');
                this.elements.expandButton.setAttribute('title', 'Collapse chat');
                
                // Save state to localStorage
                localStorage.setItem('mpai_chat_expanded', 'true');
            } else {
                this.elements.container.classList.remove('mpai-chat-expanded');
                this.elements.expandButton.innerHTML = '<span class="dashicons dashicons-editor-expand"></span>';
                this.elements.expandButton.setAttribute('aria-label', 'Expand chat');
                this.elements.expandButton.setAttribute('title', 'Expand chat');
                
                // Save state to localStorage
                localStorage.setItem('mpai_chat_expanded', 'false');
            }
            
            // Scroll to bottom after expansion change
            this.scrollToBottom();
            
            // Log the action if debug is enabled
            this.log('Chat expansion toggled:', this.state.isExpanded ? 'expanded' : 'collapsed');
        }
    }

    /**
     * Initialize the chat interface when the DOM is ready
     */
    document.addEventListener('DOMContentLoaded', () => {
        // Check if chat elements exist
        if (!document.getElementById('mpai-chat-container')) {
            console.warn('[MPAI Chat] Chat container not found');
            return;
        }
        
        // Get configuration from global variable or use defaults
        const config = window.mpai_chat_config || {};
        
        // Initialize chat
        window.mpaiChat = new MPAIChat(config);
        
        // Make chat interface available globally
        window.MPAIChat = MPAIChat;
    });

})();