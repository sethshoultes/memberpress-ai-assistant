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
                expandButton: document.getElementById('mpai-chat-expand'),
                runCommandButton: document.getElementById('mpai-run-command'),
                commandCloseButton: document.getElementById('mpai-command-close'),
                commandRunner: document.getElementById('mpai-command-runner'),
                clearConversationLink: document.getElementById('mpai-clear-conversation'),
                downloadButton: document.getElementById('mpai-download-conversation'),
                exportFormatMenu: document.getElementById('mpai-export-format-menu')
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
            // Add a direct console log to verify logging is working
            console.log('[MPAI Debug] Direct console log test - init method called');
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
            
            // Run command button (wrench icon)
            if (this.elements.runCommandButton) {
                this.elements.runCommandButton.addEventListener('click', () => this.toggleCommandRunner());
            }
            
            // Command runner close button
            if (this.elements.commandCloseButton) {
                this.elements.commandCloseButton.addEventListener('click', () => this.hideCommandRunner());
            }
            
            // Clear conversation link
            if (this.elements.clearConversationLink) {
                this.elements.clearConversationLink.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.clearHistory();
                });
            }
            
            // Download conversation button
            if (this.elements.downloadButton) {
                console.log('[MPAI Debug] Adding click event listener to download button');
                this.elements.downloadButton.addEventListener('click', () => {
                    console.log('[MPAI Debug] Download button clicked');
                    console.log('[MPAI Debug] Current state:', this.state);
                    console.log('[MPAI Debug] Messages length:', this.state.messages ? this.state.messages.length : 'undefined');
                    
                    // Check if we have messages to download
                    if (!this.state.messages || this.state.messages.length === 0) {
                        console.log('[MPAI Debug] No messages to download');
                        alert('No conversation to download.');
                        return;
                    }
                    
                    // Download as HTML by default (simpler approach)
                    this.downloadConversation('html');
                });
            } else {
                console.log('[MPAI Debug] Download button element not found');
            }
            
            // Export button reference removed (not used in the HTML template)
            
            // Add event delegation for format selection buttons
            document.addEventListener('click', (e) => {
                console.log('[MPAI Debug] Document click event detected on:', e.target);
                console.log('[MPAI Debug] Target classList:', e.target.classList);
                
                // Find the closest format button, even if a child element was clicked
                const formatButton = e.target.closest('.mpai-export-format-btn');
                if (formatButton) {
                    console.log('[MPAI Debug] Format button clicked via event delegation');
                    const format = formatButton.getAttribute('data-format');
                    console.log('[MPAI Debug] Format selected via event delegation:', format);
                    this.downloadConversation(format);
                }
                
                // Close the format menu when clicking outside
                if (this.elements.exportFormatMenu &&
                    this.elements.exportFormatMenu.style.display !== 'none' &&
                    !this.elements.exportFormatMenu.contains(e.target) &&
                    e.target !== this.elements.downloadButton) {
                    this.elements.exportFormatMenu.style.display = 'none';
                }
            });
            
            // Command items
            document.addEventListener('click', (e) => {
                if (e.target.classList.contains('mpai-command-item') || e.target.closest('.mpai-command-item')) {
                    e.preventDefault();
                    const commandItem = e.target.classList.contains('mpai-command-item') ?
                        e.target : e.target.closest('.mpai-command-item');
                    const command = commandItem.getAttribute('data-command');
                    this.insertCommand(command);
                }
            });
            
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
            
            // Check if this is a blog post response
            if (role === 'assistant' && content && (
                content.includes('<wp-post>') ||
                content.includes('</wp-post>') ||
                content.includes('<post-title>') ||
                content.includes('</post-title>') ||
                content.includes('<post-content>') ||
                content.includes('</post-content>')
            )) {
                console.log('[MPAI Debug] Blog post content detected in assistant message');
                
                // Process the blog post content directly here
                this.processBlogPostContent(contentDiv, content);
                
                // Add the content div to the message div
                messageDiv.appendChild(contentDiv);
                
                return messageDiv;
            }
            
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
         * Process blog post content and create a preview card
         *
         * @param {HTMLElement} contentDiv - The content div to add the preview card to
         * @param {string} content - The content containing blog post XML
         */
        processBlogPostContent(contentDiv, content) {
            console.log('[MPAI Debug] Processing blog post content');
            
            // Extract the XML content
            const xmlContent = this.extractXmlContent(content);
            
            if (!xmlContent) {
                console.error('[MPAI Debug] Failed to extract XML content');
                contentDiv.textContent = content;
                return;
            }
            
            console.log('[MPAI Debug] Extracted XML content:', xmlContent.substring(0, 100) + '...');
            
            // Parse the XML content
            const postData = this.parsePostXml(xmlContent);
            
            if (!postData) {
                console.error('[MPAI Debug] Failed to parse XML content');
                contentDiv.textContent = content;
                return;
            }
            
            console.log('[MPAI Debug] Parsed post data:', postData);
            
            // Create the post preview card
            this.createPostPreviewCard(contentDiv, postData, xmlContent);
        }
        
        /**
         * Extract XML content from a message
         *
         * @param {string} content - The message content
         * @return {string|null} The XML content or null if not found
         */
        extractXmlContent(content) {
            // First, try to extract from code blocks
            const codeBlockRegex = /```(?:xml)?\s*(<wp-post>[\s\S]*?<\/wp-post>)\s*```/;
            const codeBlockMatch = content.match(codeBlockRegex);
            
            if (codeBlockMatch && codeBlockMatch[1]) {
                return codeBlockMatch[1];
            }
            
            // If not found in code blocks, try to extract directly
            const directRegex = /(<wp-post>[\s\S]*?<\/wp-post>)/;
            const directMatch = content.match(directRegex);
            
            if (directMatch && directMatch[1]) {
                return directMatch[1];
            }
            
            return null;
        }
        
        /**
         * Parse post XML content
         *
         * @param {string} xmlContent - The XML content
         * @return {object|null} The parsed post data or null if parsing failed
         */
        parsePostXml(xmlContent) {
            try {
                // Create a temporary DOM element to parse the XML
                const parser = new DOMParser();
                const xmlDoc = parser.parseFromString(xmlContent, 'text/xml');
                
                // Check for parsing errors
                const parseError = xmlDoc.querySelector('parsererror');
                if (parseError) {
                    console.error('[MPAI Debug] XML parsing error:', parseError.textContent);
                    return null;
                }
                
                // Extract post data
                const postElement = xmlDoc.querySelector('wp-post');
                if (!postElement) {
                    console.error('[MPAI Debug] No wp-post element found in XML');
                    return null;
                }
                
                const title = postElement.querySelector('post-title')?.textContent || '';
                const excerpt = postElement.querySelector('post-excerpt')?.textContent || '';
                const status = postElement.querySelector('post-status')?.textContent || 'draft';
                const type = postElement.querySelector('post-type')?.textContent || 'post';
                
                // Extract content blocks
                const contentElement = postElement.querySelector('post-content');
                let content = '';
                
                if (contentElement) {
                    // Convert content blocks to HTML
                    const blocks = contentElement.querySelectorAll('block');
                    
                    for (const block of blocks) {
                        const blockType = block.getAttribute('type');
                        
                        if (blockType === 'paragraph') {
                            content += `<p>${block.textContent}</p>`;
                        } else if (blockType === 'heading') {
                            const level = block.getAttribute('level') || '2';
                            content += `<h${level}>${block.textContent}</h${level}>`;
                        } else if (blockType === 'list') {
                            content += '<ul>';
                            const items = block.querySelectorAll('item');
                            for (const item of items) {
                                content += `<li>${item.textContent}</li>`;
                            }
                            content += '</ul>';
                        }
                    }
                }
                
                return {
                    title,
                    content,
                    excerpt,
                    status,
                    type
                };
            } catch (error) {
                console.error('[MPAI Debug] Error parsing XML:', error);
                return null;
            }
        }
        
        /**
         * Create a post preview card
         *
         * @param {HTMLElement} contentDiv - The content div to add the preview card to
         * @param {object} postData - The post data
         * @param {string} xmlContent - The original XML content
         */
        createPostPreviewCard(contentDiv, postData, xmlContent) {
            console.log('[MPAI Debug] Creating post preview card');
            
            // Create the card element
            const card = document.createElement('div');
            card.className = 'mpai-post-preview-card';
            
            // Add the header
            const header = document.createElement('div');
            header.className = 'mpai-post-preview-header';
            
            const typeDiv = document.createElement('div');
            typeDiv.className = 'mpai-post-preview-type';
            typeDiv.textContent = 'BLOG POST';
            header.appendChild(typeDiv);
            
            const iconDiv = document.createElement('div');
            iconDiv.className = 'mpai-post-preview-icon';
            iconDiv.innerHTML = '<span class="dashicons dashicons-edit"></span>';
            header.appendChild(iconDiv);
            
            card.appendChild(header);
            
            // Add the title
            const title = document.createElement('h3');
            title.className = 'mpai-post-preview-title';
            title.textContent = postData.title;
            card.appendChild(title);
            
            // Add the excerpt
            const excerpt = document.createElement('div');
            excerpt.className = 'mpai-post-preview-excerpt';
            excerpt.textContent = postData.excerpt;
            card.appendChild(excerpt);
            
            // Add the action buttons
            const actions = document.createElement('div');
            actions.className = 'mpai-post-preview-actions';
            
            const createButton = document.createElement('button');
            createButton.className = 'mpai-create-post-button';
            createButton.textContent = 'Create Post';
            createButton.addEventListener('click', () => this.createPost(card, postData, xmlContent));
            actions.appendChild(createButton);
            
            const previewButton = document.createElement('button');
            previewButton.className = 'mpai-preview-post-button';
            previewButton.textContent = 'Preview';
            previewButton.addEventListener('click', () => this.togglePreview(card));
            actions.appendChild(previewButton);
            
            const xmlButton = document.createElement('button');
            xmlButton.className = 'mpai-toggle-xml-button';
            xmlButton.textContent = 'View XML';
            xmlButton.addEventListener('click', () => this.toggleXml(card));
            actions.appendChild(xmlButton);
            
            card.appendChild(actions);
            
            // Add the XML content (hidden by default)
            const xmlDiv = document.createElement('div');
            xmlDiv.className = 'mpai-post-xml-content';
            xmlDiv.style.display = 'none';
            
            const xmlPre = document.createElement('pre');
            xmlPre.textContent = xmlContent;
            xmlDiv.appendChild(xmlPre);
            
            card.appendChild(xmlDiv);
            
            // Add the preview content (hidden by default)
            const previewDiv = document.createElement('div');
            previewDiv.className = 'mpai-post-preview-content';
            previewDiv.style.display = 'none';
            
            const previewContainer = document.createElement('div');
            previewContainer.className = 'mpai-post-preview-container';
            previewContainer.innerHTML = postData.content;
            previewDiv.appendChild(previewContainer);
            
            card.appendChild(previewDiv);
            
            // Add the card to the content div
            contentDiv.appendChild(card);
            
            console.log('[MPAI Debug] Post preview card created');
        }
        
        /**
         * Toggle the XML content visibility
         *
         * @param {HTMLElement} card - The card element
         */
        toggleXml(card) {
            const xmlContent = card.querySelector('.mpai-post-xml-content');
            const button = card.querySelector('.mpai-toggle-xml-button');
            
            if (xmlContent.style.display === 'none') {
                xmlContent.style.display = 'block';
                button.textContent = 'Hide XML';
                
                // Hide the preview if it's visible
                const previewContent = card.querySelector('.mpai-post-preview-content');
                previewContent.style.display = 'none';
                card.querySelector('.mpai-preview-post-button').textContent = 'Preview';
            } else {
                xmlContent.style.display = 'none';
                button.textContent = 'View XML';
            }
        }
        
        /**
         * Toggle the preview content visibility
         *
         * @param {HTMLElement} card - The card element
         */
        togglePreview(card) {
            const previewContent = card.querySelector('.mpai-post-preview-content');
            const button = card.querySelector('.mpai-preview-post-button');
            
            if (previewContent.style.display === 'none') {
                previewContent.style.display = 'block';
                button.textContent = 'Hide Preview';
                
                // Hide the XML if it's visible
                const xmlContent = card.querySelector('.mpai-post-xml-content');
                xmlContent.style.display = 'none';
                card.querySelector('.mpai-toggle-xml-button').textContent = 'View XML';
            } else {
                previewContent.style.display = 'none';
                button.textContent = 'Preview';
            }
        }
        
        /**
         * Create a post from the preview
         *
         * @param {HTMLElement} card - The card element
         * @param {object} postData - The post data
         * @param {string} xmlContent - The original XML content
         */
        createPost(card, postData, xmlContent) {
            console.log('[MPAI Debug] Creating post:', postData);
            
            // Disable the button to prevent multiple submissions
            const button = card.querySelector('.mpai-create-post-button');
            button.disabled = true;
            button.textContent = 'Creating...';
            
            // Prepare the data for the AJAX request
            const data = {
                action: 'mpai_create_post',
                nonce: window.mpai_nonce || '',
                title: postData.title,
                content: postData.content,
                excerpt: postData.excerpt,
                status: postData.status,
                post_type: postData.type || 'post'
            };
            
            // Send the AJAX request
            fetch(ajaxurl || '/wp-admin/admin-ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(data)
            })
            .then(response => response.json())
            .then(response => {
                console.log('[MPAI Debug] Post created successfully:', response);
                
                // Update the button
                button.disabled = false;
                button.textContent = 'Post Created!';
                
                // Add the edit link if available
                if (response.data && response.data.edit_url) {
                    const editLink = document.createElement('a');
                    editLink.className = 'mpai-edit-post-link';
                    editLink.href = response.data.edit_url;
                    editLink.target = '_blank';
                    editLink.textContent = 'Edit Post';
                    card.querySelector('.mpai-post-preview-actions').appendChild(editLink);
                }
                
                // Show a success message
                const successMessage = document.createElement('div');
                successMessage.className = 'mpai-post-success-message';
                successMessage.textContent = response.data && response.data.message ? response.data.message : 'Post created successfully!';
                card.appendChild(successMessage);
            })
            .catch(error => {
                console.error('[MPAI Debug] Error creating post:', error);
                
                // Update the button
                button.disabled = false;
                button.textContent = 'Create Post';
                
                // Show an error message
                const errorMessage = document.createElement('div');
                errorMessage.className = 'mpai-post-error-message';
                errorMessage.textContent = 'Error creating post: ' + error.message;
                card.appendChild(errorMessage);
            });
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

        /**
         * Toggle the command runner panel
         */
        toggleCommandRunner() {
            if (this.elements.commandRunner) {
                if (this.elements.commandRunner.style.display === 'none' || !this.elements.commandRunner.style.display) {
                    this.elements.commandRunner.style.display = 'flex';
                    this.log('Command runner opened');
                } else {
                    this.elements.commandRunner.style.display = 'none';
                    this.log('Command runner closed');
                }
            }
        }

        /**
         * Hide the command runner panel
         */
        hideCommandRunner() {
            if (this.elements.commandRunner) {
                this.elements.commandRunner.style.display = 'none';
                this.log('Command runner closed');
            }
        }

        /**
         * Insert a command into the chat input
         *
         * @param {string} command The command to insert
         */
        insertCommand(command) {
            if (!command || !this.elements.input) {
                return;
            }
            
            // Set the command in the input field
            this.elements.input.value = command;
            
            // Hide the command runner
            this.hideCommandRunner();
            
            // Focus the input
            this.elements.input.focus();
            
            this.log('Command inserted:', command);
        }
        
        /**
         * Show export format selection menu
         */
        showExportFormatMenu() {
            console.log('[MPAI Debug] showExportFormatMenu called');
            console.log('[MPAI Debug] Download button element:', this.elements.downloadButton);
            console.log('[MPAI Debug] Export format menu element:', this.elements.exportFormatMenu);
            console.log('[MPAI Debug] State:', this.state);
            console.log('[MPAI Debug] Messages:', this.state.messages);
            
            // Check if we have messages to download
            if (!this.state.messages || this.state.messages.length === 0) {
                console.log('[MPAI Debug] No messages to download');
                alert('No conversation to download.');
                return;
            }
            
            // Use the static menu from the HTML template
            if (this.elements.exportFormatMenu) {
                console.log('[MPAI Debug] Using static export format menu from HTML template');
                
                // Position the menu near the download button
                const buttonRect = this.elements.downloadButton.getBoundingClientRect();
                console.log('[MPAI Debug] Download button rect:', {
                    top: buttonRect.top,
                    right: buttonRect.right,
                    bottom: buttonRect.bottom,
                    left: buttonRect.left
                });
                
                console.log('[MPAI Debug] Window dimensions:', {
                    innerWidth: window.innerWidth,
                    innerHeight: window.innerHeight,
                    scrollX: window.scrollX,
                    scrollY: window.scrollY
                });
                
                // Fix menu positioning - position it relative to the button without using scrollY
                // This ensures it appears near the button regardless of scroll position
                this.elements.exportFormatMenu.style.position = 'fixed';
                this.elements.exportFormatMenu.style.top = `${buttonRect.bottom + 5}px`;
                this.elements.exportFormatMenu.style.left = `${buttonRect.left}px`;
                this.elements.exportFormatMenu.style.right = 'auto'; // Clear the right property
                
                console.log('[MPAI Debug] Menu position calculated:', {
                    top: this.elements.exportFormatMenu.style.top,
                    right: this.elements.exportFormatMenu.style.right
                });
                
                // Show the menu
                this.elements.exportFormatMenu.style.display = 'block';
                
                // Log menu style after display is set
                console.log('[MPAI Debug] Menu style after display set:', {
                    display: this.elements.exportFormatMenu.style.display,
                    top: this.elements.exportFormatMenu.style.top,
                    right: this.elements.exportFormatMenu.style.right,
                    width: this.elements.exportFormatMenu.offsetWidth,
                    height: this.elements.exportFormatMenu.offsetHeight,
                    visibility: window.getComputedStyle(this.elements.exportFormatMenu).visibility,
                    zIndex: window.getComputedStyle(this.elements.exportFormatMenu).zIndex
                });
                
                // Close menu when clicking outside
                const closeMenu = (e) => {
                    if (!this.elements.exportFormatMenu.contains(e.target) && e.target !== this.elements.downloadButton) {
                        this.elements.exportFormatMenu.style.display = 'none';
                        document.removeEventListener('click', closeMenu);
                    }
                };
                
                // Add a small delay before adding the click listener to prevent immediate closing
                setTimeout(() => {
                    document.addEventListener('click', closeMenu);
                }, 100);
                
                this.log('Export format menu displayed');
            } else {
                console.log('[MPAI Debug] Export format menu element not found in HTML template');
                alert('Error: Export format menu not found.');
            }
        }
        
        // First implementation of downloadConversation removed to avoid duplication
        
        // Removed duplicate HTML/Markdown formatting methods
        
        // toggleExportFormatMenu method removed (not used anymore)

        /**
         * Download the conversation in the specified format
         *
         * @param {string} format The format to download (html or markdown)
         */
        downloadConversation(format) {
            console.log('[MPAI Debug] downloadConversation called with format:', format);
            console.log('[MPAI Debug] Messages state:', this.state.messages);
            this.log('Downloading conversation in format:', format);
            
            // Check if we have messages to download
            if (!format || !this.state.messages || this.state.messages.length === 0) {
                alert('No conversation to download.');
                return;
            }
            
            let content = '';
            let mimeType = '';
            
            // Generate filename based on date and time
            const date = new Date();
            const formattedDate = `${date.getFullYear()}-${(date.getMonth()+1).toString().padStart(2, '0')}-${date.getDate().toString().padStart(2, '0')}`;
            const formattedTime = `${date.getHours().toString().padStart(2, '0')}-${date.getMinutes().toString().padStart(2, '0')}`;
            let filename = `memberpress-ai-conversation-${formattedDate}-${formattedTime}`;
            
            if (format === 'html') {
                content = this.formatConversationAsHTML();
                filename += '.html';
                mimeType = 'text/html';
            } else if (format === 'markdown') {
                content = this.formatConversationAsMarkdown();
                filename += '.md';
                mimeType = 'text/markdown';
            } else {
                return;
            }
            
            // Hide the format menu
            if (this.elements.exportFormatMenu) {
                this.elements.exportFormatMenu.style.display = 'none';
            }
            
            // Download the file
            this.downloadTextFile(content, filename, mimeType);
            
            this.log('Conversation downloaded as:', filename);
        }

        /**
         * Format the conversation as HTML
         *
         * @returns {string} The conversation formatted as HTML
         */
        formatConversationAsHTML() {
            console.log('[MPAI Debug] formatConversationAsHTML called (second implementation)');
            let html = `<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>MemberPress AI Chat - ${new Date().toLocaleString()}</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .message { margin-bottom: 20px; padding: 10px 15px; border-radius: 10px; }
        .user { background-color: #f0f7ff; border-left: 3px solid #0073aa; }
        .assistant { background-color: #f9f9f9; border-left: 3px solid #ddd; }
        .timestamp { font-size: 12px; color: #666; margin-top: 5px; }
        h1 { color: #0073aa; }
        pre { background-color: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
        code { background-color: #f5f5f5; padding: 2px 4px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>MemberPress AI Chat</h1>
    <p>Exported on ${new Date().toLocaleString()}</p>
    <div class="conversation">
`;

            this.state.messages.forEach(message => {
                const role = message.role;
                const content = message.content;
                const timestamp = message.timestamp ? new Date(message.timestamp).toLocaleString() : '';
                
                html += `<div class="message ${role}">
            <div class="content">${content}</div>
            ${timestamp ? `<div class="timestamp">${timestamp}</div>` : ''}
        </div>
`;
            });

            html += `    </div>
</body>
</html>`;

            return html;
        }

        /**
         * Format the conversation as Markdown
         *
         * @returns {string} The conversation formatted as Markdown
         */
        formatConversationAsMarkdown() {
            console.log('[MPAI Debug] formatConversationAsMarkdown called (second implementation)');
            let markdown = `# MemberPress AI Chat\n\nExported on ${new Date().toLocaleString()}\n\n`;

            this.state.messages.forEach(message => {
                const role = message.role;
                const content = message.content.replace(/<[^>]*>/g, ''); // Strip HTML tags
                const timestamp = message.timestamp ? new Date(message.timestamp).toLocaleString() : '';
                
                markdown += `## ${role === 'user' ? 'You' : 'MemberPress AI'}\n\n${content}\n\n`;
                if (timestamp) {
                    markdown += `*${timestamp}*\n\n`;
                }
                markdown += `---\n\n`;
            });

            return markdown;
        }
        
        /**
         * Helper function to download text as a file
         *
         * @param {string} content - The text content to download
         * @param {string} filename - The name of the file to download
         * @param {string} mimeType - The MIME type of the file
         */
        downloadTextFile(content, filename, mimeType) {
            console.log('[MPAI Debug] downloadTextFile called with filename:', filename, 'and mimeType:', mimeType);
            
            try {
                // Create a blob with the content and appropriate MIME type
                const blob = new Blob([content], { type: mimeType || 'text/plain' });
                
                // Create a URL for the blob
                const url = URL.createObjectURL(blob);
                
                // Create a temporary anchor element
                const a = document.createElement('a');
                a.href = url;
                a.download = filename;
                
                // Append to the document and trigger the download
                document.body.appendChild(a);
                a.click();
                
                // Clean up
                setTimeout(function() {
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                }, 100);
                
                console.log('[MPAI Debug] Download initiated successfully');
                return true;
            } catch (error) {
                console.error('[MPAI Debug] Error downloading file:', error);
                alert('There was an error downloading the conversation. Please try again.');
                return false;
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