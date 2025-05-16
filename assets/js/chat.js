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
                closeButton: document.getElementById('mpai-chat-close')
            };

            // State
            this.state = {
                isOpen: false,
                isProcessing: false,
                conversationId: null,
                messages: [],
                lastUserMessage: null
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
            
            // Generate a conversation ID if not provided
            if (!this.state.conversationId) {
                this.state.conversationId = this.generateConversationId();
            }
            
            // Auto-open chat if configured
            if (this.config.autoOpen) {
                this.openChat();
            }
            
            // Enable auto-resize for the input field
            this.setupInputAutoResize();
            
            this.log('Chat interface initialized');
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
                    conversation_id: this.state.conversationId
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
         */
        addUserMessage(message) {
            const messageElement = this.createMessageElement('user', message);
            this.elements.messagesContainer.appendChild(messageElement);
            
            // Add to messages array
            this.state.messages.push({
                role: 'user',
                content: message,
                timestamp: new Date().toISOString()
            });
            
            // Prune messages if exceeding max
            this.pruneMessages();
            
            // Scroll to bottom
            this.scrollToBottom();
        }

        /**
         * Add an assistant message to the chat
         * 
         * @param {string} message The message text
         */
        addAssistantMessage(message) {
            const messageElement = this.createMessageElement('assistant', message);
            this.elements.messagesContainer.appendChild(messageElement);
            
            // Add to messages array
            this.state.messages.push({
                role: 'assistant',
                content: message,
                timestamp: new Date().toISOString()
            });
            
            // Prune messages if exceeding max
            this.pruneMessages();
            
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
                
                // First check for XML content
                if (window.MPAIXMLProcessor && window.MPAIXMLProcessor.containsXML(content)) {
                    // Process XML content
                    contentDiv.innerHTML = window.MPAIXMLProcessor.processMessage(content);
                } else if (window.MPAITextFormatter) {
                    // Process markdown formatting
                    contentDiv.innerHTML = window.MPAITextFormatter.formatText(content, {
                        allowHtml: false,
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