# Chat.js Modularization Plan

## Overview

This document outlines a comprehensive plan to modularize the current chat.js file, which has grown large and fragile. The goal is to create a more maintainable, extensible architecture that follows KISS and DRY principles while enabling easier addition of new interactive message elements.

## Current Issues

1. **Large monolithic file**: At 1550+ lines, the file is difficult to maintain and understand
2. **Tight coupling between message handling and UI rendering**: Makes it difficult to add new message types
3. **Future scalability concerns**: Current structure may not scale well as features are added
4. **Need for extensibility**: System needs to support new interactive elements in messages

## Proposed Architecture

The new architecture will follow these principles:
- Separation of concerns
- Module independence
- Extensibility
- Consistent interfaces

### Architecture Diagram

```mermaid
graph TD
    A[Chat Core] --> B[State Manager]
    A --> C[UI Manager]
    A --> D[API Client]
    
    B --> B1[Conversation State]
    B --> B2[UI State]
    B --> B3[User State]
    
    C --> C1[Message Renderer]
    C --> C2[Input Handler]
    C --> C3[UI Controls]
    
    C1 --> M1[Message Factory]
    
    M1 --> MT1[Text Message]
    M1 --> MT2[Blog Post Message]
    M1 --> MT3[Interactive Message]
    M1 --> MT4[System Message]
    
    D --> D1[API Requests]
    D --> D2[Response Handler]
    D --> D3[Error Handler]
    
    E[Event Bus] --- A
    E --- B
    E --- C
    E --- D
## Module Breakdown

### 1. Core Modules

#### 1.1. Chat Core (`chat-core.js`)
- Main entry point and orchestrator
- Initializes other modules
- Handles high-level event coordination
- Exposes public API for the chat interface

```javascript
// Example structure
class ChatCore {
    constructor(options = {}) {
        this.config = this._mergeConfig(options);
        this.elements = this._findElements();
        
        // Initialize core modules
        this.eventBus = new EventBus();
        this.stateManager = new StateManager(this.eventBus);
        this.uiManager = new UIManager(this.elements, this.eventBus);
        this.apiClient = new APIClient(this.config.apiEndpoint, this.eventBus);
        
        // Initialize the chat interface
        this.init();
    }
    
    init() {
        // Subscribe to events
        this.eventBus.subscribe('message:send', this._handleSendMessage.bind(this));
        this.eventBus.subscribe('history:clear', this._handleClearHistory.bind(this));
        
        // Load conversation history
        this._loadHistory();
    }
    
    // Public methods that will be exposed globally
    sendMessage(text) {
        this.eventBus.publish('message:send', { text });
    }
    
    clearHistory() {
        this.eventBus.publish('history:clear');
    }
    
    toggleChat() {
        this.eventBus.publish('ui:toggle');
    }
    
    // Private methods
    _mergeConfig(options) { /* ... */ }
    _findElements() { /* ... */ }
    _loadHistory() { /* ... */ }
    _handleSendMessage(data) { /* ... */ }
    _handleClearHistory() { /* ... */ }
}

// Export for global access
window.MPAIChat = ChatCore;
```

#### 1.2. State Manager (`state-manager.js`)
- Manages all application state
- Handles conversation history
- Manages UI state (open/closed, expanded/collapsed)
- Provides methods for state updates and retrieval

```javascript
class StateManager {
    constructor(eventBus) {
        this.eventBus = eventBus;
        
        // Initialize state containers
        this.conversationState = new ConversationState();
        this.uiState = new UIState();
        this.userState = new UserState();
        
        // Subscribe to events
        this.eventBus.subscribe('message:received', this._handleMessageReceived.bind(this));
        this.eventBus.subscribe('message:sent', this._handleMessageSent.bind(this));
        this.eventBus.subscribe('history:loaded', this._handleHistoryLoaded.bind(this));
        this.eventBus.subscribe('history:cleared', this._handleHistoryCleared.bind(this));
        this.eventBus.subscribe('ui:opened', this._handleUiOpened.bind(this));
        this.eventBus.subscribe('ui:closed', this._handleUiClosed.bind(this));
    }
    
    // Public methods
    getConversationId() {
        return this.conversationState.getId();
    }
    
    getMessages() {
        return this.conversationState.getMessages();
    }
    
    isOpen() {
        return this.uiState.isOpen();
    }
    
    isExpanded() {
        return this.uiState.isExpanded();
    }
    
    // Private event handlers
    _handleMessageReceived(message) { /* ... */ }
    _handleMessageSent(message) { /* ... */ }
    _handleHistoryLoaded(history) { /* ... */ }
    _handleHistoryCleared() { /* ... */ }
    _handleUiOpened() { /* ... */ }
    _handleUiClosed() { /* ... */ }
}

// Sub-state classes
class ConversationState {
    constructor() {
        this.id = null;
        this.messages = [];
    }
    
    getId() { /* ... */ }
    getMessages() { /* ... */ }
    addMessage(message) { /* ... */ }
    setMessages(messages) { /* ... */ }
    clear() { /* ... */ }
}

class UIState {
    constructor() {
        this.isOpenState = false;
        this.isExpandedState = false;
    }
    
    isOpen() { /* ... */ }
    setOpen(isOpen) { /* ... */ }
    isExpanded() { /* ... */ }
    setExpanded(isExpanded) { /* ... */ }
}

class UserState {
    constructor() {
        this.isLoggedIn = false;
    }
    
    getIsLoggedIn() { /* ... */ }
    setIsLoggedIn(isLoggedIn) { /* ... */ }
}
```

#### 1.3. UI Manager (`ui-manager.js`)
- Manages all UI-related functionality
- Coordinates message rendering
- Handles UI controls (buttons, toggles)
- Manages input handling

```javascript
class UIManager {
    constructor(elements, eventBus) {
        this.elements = elements;
        this.eventBus = eventBus;
        
        // Initialize UI components
        this.messageRenderer = new MessageRenderer(elements.messagesContainer, eventBus);
        this.inputHandler = new InputHandler(elements.input, elements.submitButton, eventBus);
        this.uiControls = new UIControls(elements, eventBus);
        
        // Subscribe to events
        this.eventBus.subscribe('message:received', this._handleMessageReceived.bind(this));
        this.eventBus.subscribe('message:sent', this._handleMessageSent.bind(this));
        this.eventBus.subscribe('message:sending', this._handleMessageSending.bind(this));
        this.eventBus.subscribe('history:loaded', this._handleHistoryLoaded.bind(this));
        this.eventBus.subscribe('history:cleared', this._handleHistoryCleared.bind(this));
    }
    
    // Public methods
    showLoadingIndicator() {
        // Create and show loading indicator
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
    
    hideLoadingIndicator() {
        const loadingElement = document.getElementById('mpai-chat-loading');
        if (loadingElement) {
            loadingElement.remove();
        }
    }
    
    scrollToBottom() {
        if (this.elements.messagesContainer) {
            this.elements.messagesContainer.scrollTop = this.elements.messagesContainer.scrollHeight;
        }
    }
    
    // Private event handlers
    _handleMessageReceived(message) {
        this.hideLoadingIndicator();
        this.messageRenderer.renderMessage(message);
        this.scrollToBottom();
    }
    
    _handleMessageSent(message) {
        this.messageRenderer.renderMessage(message);
        this.scrollToBottom();
    }
    
    _handleMessageSending() {
        this.showLoadingIndicator();
    }
    
    _handleHistoryLoaded(history) {
        this.messageRenderer.renderHistory(history);
        this.scrollToBottom();
    }
    
    _handleHistoryCleared() {
        this.elements.messagesContainer.innerHTML = '';
    }
}
```

#### 1.4. API Client (`api-client.js`)
- Handles all communication with the backend
- Manages API requests and responses
- Handles error conditions
- Provides consistent interface for data operations

```javascript
class APIClient {
    constructor(endpoint, eventBus) {
        this.endpoint = endpoint;
        this.eventBus = eventBus;
        
        // Subscribe to events
        this.eventBus.subscribe('message:send', this._handleSendMessage.bind(this));
        this.eventBus.subscribe('history:load', this._handleLoadHistory.bind(this));
        this.eventBus.subscribe('history:clear', this._handleClearHistory.bind(this));
    }
    
    // Private event handlers
    _handleSendMessage(data) {
        this.sendMessage(data.text, data.conversationId)
            .then(response => {
                this.eventBus.publish('message:received', {
                    role: 'assistant',
                    content: response.message,
                    timestamp: new Date().toISOString()
                });
                
                if (response.conversation_id) {
                    this.eventBus.publish('conversation:updated', {
                        id: response.conversation_id
                    });
                }
            })
            .catch(error => {
                this.eventBus.publish('api:error', {
                    type: 'send_message',
                    error
                });
                
                this.eventBus.publish('message:received', {
                    role: 'assistant',
                    content: 'Sorry, I encountered an error processing your request. Please try again later.',
                    timestamp: new Date().toISOString(),
                    isError: true
                });
            });
    }
    
    _handleLoadHistory(data) {
        this.loadHistory(data.conversationId)
            .then(response => {
                if (response && response.history) {
                    this.eventBus.publish('history:loaded', response.history);
                }
                
                if (response && response.conversation_id) {
                    this.eventBus.publish('conversation:updated', {
                        id: response.conversation_id
                    });
                }
            })
            .catch(error => {
                this.eventBus.publish('api:error', {
                    type: 'load_history',
                    error
                });
            });
    }
    
    _handleClearHistory(data) {
        this.clearHistory(data.conversationId)
            .then(response => {
                this.eventBus.publish('history:cleared');
                
                if (response && response.conversation_id) {
                    this.eventBus.publish('conversation:updated', {
                        id: response.conversation_id
                    });
                }
            })
            .catch(error => {
                this.eventBus.publish('api:error', {
                    type: 'clear_history',
### 2. Message Handling System (Extensible Architecture)

The message handling system is designed to be highly extensible, allowing for easy addition of new message types and interactive elements.

#### 2.1. Message Renderer (`message-renderer.js`)
- Renders messages in the chat interface
- Uses MessageFactory to create appropriate message components
- Handles message animations and transitions

```javascript
class MessageRenderer {
    constructor(container, eventBus) {
        this.container = container;
        this.eventBus = eventBus;
        this.messageFactory = new MessageFactory(eventBus);
    }
    
    renderMessage(message) {
        const messageElement = this.messageFactory.createMessage(message);
        this.container.appendChild(messageElement);
    }
    
    renderHistory(messages) {
        // Clear existing messages
        this.container.innerHTML = '';
        
        // Render each message
        messages.forEach(message => {
            this.renderMessage(message);
        });
    }
}
```

#### 2.2. Message Factory (`message-factory.js`)
- Creates message components based on message type
- Provides extensible system for adding new message types
- Delegates to specific message type handlers

```javascript
class MessageFactory {
    constructor(eventBus) {
        this.eventBus = eventBus;
        
        // Initialize message type handlers
        this.messageTypes = {
            'text': new TextMessageHandler(eventBus),
            'blog-post': new BlogPostMessageHandler(eventBus),
            'interactive': new InteractiveMessageHandler(eventBus),
            'system': new SystemMessageHandler(eventBus)
        };
    }
    
    createMessage(message) {
        const type = this.detectMessageType(message);
        return this.messageTypes[type].create(message);
    }
    
    detectMessageType(message) {
        // Detect message type based on content and metadata
        if (message.isError) {
            return 'system';
        }
        
        const content = message.content || '';
        
        // Check for blog post XML
        if (content.includes('<wp-post>') || 
            content.includes('</wp-post>') || 
            content.includes('<post-title>') || 
            content.includes('</post-title>') || 
            content.includes('<post-content>') || 
            content.includes('</post-content>')) {
            return 'blog-post';
        }
        
        // Check for interactive elements
        if (content.includes('<button') || 
            content.includes('<form') || 
            content.includes('<input') || 
            content.includes('<select')) {
            return 'interactive';
        }
        
        // Default to text
        return 'text';
    }
    
    // Method to register new message types
    registerMessageType(type, handler) {
        this.messageTypes[type] = handler;
    }
}
```

#### 2.3. Message Type Handlers

Each message type has its own handler class that encapsulates the logic for creating and rendering that type of message. This makes it easy to add new message types without modifying existing code.

##### 2.3.1. Base Message Handler (`base-message-handler.js`)
- Abstract base class for all message handlers
- Provides common functionality and interface

```javascript
class BaseMessageHandler {
    constructor(eventBus) {
        this.eventBus = eventBus;
    }
    
    create(message) {
        // Create base message element
        const messageDiv = document.createElement('div');
        messageDiv.className = `mpai-chat-message mpai-chat-message-${message.role}`;
        
        // Create content container
        const contentDiv = document.createElement('div');
        contentDiv.className = 'mpai-chat-message-content';
        
        // Add timestamp if available
        if (message.timestamp) {
            const timestampDiv = document.createElement('div');
            timestampDiv.className = 'mpai-chat-message-timestamp';
            timestampDiv.textContent = new Date(message.timestamp).toLocaleTimeString();
            messageDiv.appendChild(timestampDiv);
        }
        
        // Process content (to be implemented by subclasses)
        this.processContent(contentDiv, message);
        
        // Add content to message
        messageDiv.appendChild(contentDiv);
        
        return messageDiv;
    }
    
    // Abstract method to be implemented by subclasses
    processContent(contentDiv, message) {
        throw new Error('Method not implemented');
    }
}
```

##### 2.3.2. Text Message Handler (`text-message-handler.js`)
- Handles plain text messages
- Uses TextFormatter for formatting

```javascript
class TextMessageHandler extends BaseMessageHandler {
    processContent(contentDiv, message) {
        const content = message.content || '';
        
        if (message.role === 'user') {
            // For user messages, just format plain text with auto-linking
            if (window.MPAITextFormatter) {
                contentDiv.innerHTML = window.MPAITextFormatter.formatPlainText(content);
            } else {
                contentDiv.textContent = content;
            }
        } else {
            // For assistant messages, apply full formatting
            if (window.MPAITextFormatter) {
                contentDiv.innerHTML = window.MPAITextFormatter.formatText(content, {
                    allowHtml: true,
                    syntaxHighlighting: true
                });
            } else {
                contentDiv.textContent = content;
            }
        }
    }
}
```

##### 2.3.3. Blog Post Message Handler (`blog-post-message-handler.js`)
- Handles blog post messages
- Creates preview cards for blog posts

```javascript
class BlogPostMessageHandler extends BaseMessageHandler {
    processContent(contentDiv, message) {
        const content = message.content || '';
        
        // Extract the XML content
        const xmlContent = this.extractXmlContent(content);
        
        if (!xmlContent) {
            // Fallback to text if XML extraction fails
            contentDiv.textContent = content;
            return;
        }
        
        // Parse the XML content
        const postData = this.parsePostXml(xmlContent);
        
        if (!postData) {
            // Fallback to text if XML parsing fails
            contentDiv.textContent = content;
            return;
        }
        
        // Create the post preview card
        this.createPostPreviewCard(contentDiv, postData, xmlContent);
    }
    
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
    
    parsePostXml(xmlContent) {
        // Implementation similar to existing code
    }
    
    createPostPreviewCard(contentDiv, postData, xmlContent) {
        // Implementation similar to existing code
    }
}
```

##### 2.3.4. Interactive Message Handler (`interactive-message-handler.js`)
- Handles messages with interactive elements
- Processes XML tags for buttons, forms, etc.

```javascript
class InteractiveMessageHandler extends BaseMessageHandler {
    processContent(contentDiv, message) {
        const content = message.content || '';
        
        // Use XML processor to handle interactive elements
        if (window.MPAIXMLProcessor && window.MPAIXMLProcessor.containsXML(content)) {
            contentDiv.innerHTML = window.MPAIXMLProcessor.processMessage(content);
            
            // Add event listeners for interactive elements
            this.setupInteractiveElements(contentDiv);
        } else {
            // Fallback to text formatting
            if (window.MPAITextFormatter) {
                contentDiv.innerHTML = window.MPAITextFormatter.formatText(content, {
                    allowHtml: true,
                    syntaxHighlighting: true
                });
            } else {
                contentDiv.textContent = content;
            }
        }
    }
    
    setupInteractiveElements(container) {
        // Add event listeners for buttons
        const buttons = container.querySelectorAll('.mpai-button');
        buttons.forEach(button => {
            button.addEventListener('click', (e) => {
                const action = button.getAttribute('data-action');
                if (action) {
                    this.eventBus.publish('interactive:action', {
                        type: 'button',
                        action,
                        element: button
                    });
                }
            });
        });
        
        // Add event listeners for forms
        const forms = container.querySelectorAll('.mpai-form');
        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                
                const formData = new FormData(form);
                const data = {};
                
                for (const [key, value] of formData.entries()) {
                    data[key] = value;
                }
                
                this.eventBus.publish('interactive:action', {
                    type: 'form',
                    action: form.getAttribute('data-action'),
                    data,
                    element: form
                });
            });
        });
    }
}
```

##### 2.3.5. System Message Handler (`system-message-handler.js`)
- Handles system messages (errors, notifications)
- Applies appropriate styling for different message types

```javascript
class SystemMessageHandler extends BaseMessageHandler {
    processContent(contentDiv, message) {
        const content = message.content || '';
        
        // Add appropriate class based on message type
        if (message.isError) {
            contentDiv.classList.add('mpai-error-message');
        } else if (message.isWarning) {
            contentDiv.classList.add('mpai-warning-message');
        } else if (message.isSuccess) {
            contentDiv.classList.add('mpai-success-message');
        } else {
            contentDiv.classList.add('mpai-info-message');
        }
        
        // Format content
        if (window.MPAITextFormatter) {
            contentDiv.innerHTML = window.MPAITextFormatter.formatText(content, {
                allowHtml: true
            });
        } else {
            contentDiv.textContent = content;
        }
    }
}
```

#### 2.4. Adding New Interactive Elements

The architecture makes it easy to add new interactive elements by following these steps:

1. **Define XML Schema**: Create a schema for the new interactive element in the XML processor
   ```javascript
   // Example: Adding a slider element to xml-processor.js
   const XML_TAG_TYPES = {
       // ... existing tags
       SLIDER: 'slider'
   };
   ```

2. **Add Processing Logic**: Add logic to process the new element in the XML processor
   ```javascript
   // Example: Processing a slider element
   case XML_TAG_TYPES.SLIDER:
       const min = attributes.min || '0';
       const max = attributes.max || '100';
       const value = attributes.value || '50';
       const step = attributes.step || '1';
       return `<input type="range" class="mpai-slider" min="${min}" max="${max}" value="${value}" step="${step}" data-action="${attributes.action || ''}">`;
   ```

3. **Add Event Handling**: Update the InteractiveMessageHandler to handle events for the new element
### 3. UI Component Modules

#### 3.1. Input Handler (`input-handler.js`)
- Manages the chat input field
- Handles input events and validation
- Provides auto-resize functionality

```javascript
class InputHandler {
    constructor(inputElement, submitButton, eventBus) {
        this.input = inputElement;
        this.submitButton = submitButton;
        this.eventBus = eventBus;
        
        this.setupAutoResize();
        this.bindEvents();
    }
    
    setupAutoResize() {
        if (!this.input) return;
        
        this.input.addEventListener('input', () => {
            // Reset height to auto to get the correct scrollHeight
            this.input.style.height = 'auto';
            
            // Set new height based on scrollHeight (with max height limit)
            const newHeight = Math.min(this.input.scrollHeight, 120);
            this.input.style.height = `${newHeight}px`;
        });
    }
    
    bindEvents() {
        // Submit message on button click
        if (this.submitButton) {
            this.submitButton.addEventListener('click', () => this.handleSubmit());
        }
        
        // Submit message on Enter key (but allow Shift+Enter for new lines)
        if (this.input) {
            this.input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.handleSubmit();
                }
            });
        }
    }
    
    handleSubmit() {
        // Get message text and trim whitespace
        const messageText = this.input.value.trim();
        
        // Don't send empty messages
        if (!messageText) {
            return;
        }
        
        // Publish message:send event
        this.eventBus.publish('message:send', { text: messageText });
        
        // Clear the input field
        this.input.value = '';
        this.input.style.height = 'auto';
        
        // Publish message:sent event
        this.eventBus.publish('message:sent', {
            role: 'user',
            content: messageText,
            timestamp: new Date().toISOString()
        });
    }
    
    disable() {
        this.input.disabled = true;
        this.submitButton.disabled = true;
    }
    
    enable() {
        this.input.disabled = false;
        this.submitButton.disabled = false;
    }
}
```

#### 3.2. UI Controls (`ui-controls.js`)
- Manages UI control elements (buttons, toggles)
- Handles UI state changes (open/close, expand/collapse)
- Coordinates UI animations

```javascript
class UIControls {
    constructor(elements, eventBus) {
        this.elements = elements;
        this.eventBus = eventBus;
        
        this.bindEvents();
        
        // Subscribe to events
        this.eventBus.subscribe('ui:open', this.openChat.bind(this));
        this.eventBus.subscribe('ui:close', this.closeChat.bind(this));
        this.eventBus.subscribe('ui:toggle', this.toggleChat.bind(this));
        this.eventBus.subscribe('ui:expand', this.expandChat.bind(this));
        this.eventBus.subscribe('ui:collapse', this.collapseChat.bind(this));
        this.eventBus.subscribe('ui:toggle-expand', this.toggleExpand.bind(this));
    }
    
    bindEvents() {
        // Toggle chat visibility
        if (this.elements.toggleButton) {
            this.elements.toggleButton.addEventListener('click', () => {
                this.eventBus.publish('ui:toggle');
            });
        }
        
        // Close chat
        if (this.elements.closeButton) {
            this.elements.closeButton.addEventListener('click', () => {
                this.eventBus.publish('ui:close');
            });
        }
        
        // Toggle expand state
        if (this.elements.expandButton) {
            this.elements.expandButton.addEventListener('click', () => {
                this.eventBus.publish('ui:toggle-expand');
            });
        }
        
        // Other UI control event bindings...
    }
    
    openChat() {
        if (this.elements.container.classList.contains('active')) return;
        
        this.elements.container.classList.add('active');
        this.elements.toggleButton.classList.add('active');
        
        // Save chat state to localStorage
        localStorage.setItem('mpai_chat_open', 'true');
        
        // Focus the input field
        setTimeout(() => {
            this.elements.input.focus();
        }, 300);
        
        // Publish event
        this.eventBus.publish('ui:opened');
    }
    
    closeChat() {
        if (!this.elements.container.classList.contains('active')) return;
        
        this.elements.container.classList.remove('active');
        this.elements.toggleButton.classList.remove('active');
        
        // Save chat state to localStorage
        localStorage.setItem('mpai_chat_open', 'false');
        
        // Publish event
        this.eventBus.publish('ui:closed');
    }
    
    toggleChat() {
        if (this.elements.container.classList.contains('active')) {
            this.closeChat();
        } else {
            this.openChat();
        }
    }
    
    expandChat() {
        this.elements.container.classList.add('mpai-chat-expanded');
        this.elements.expandButton.innerHTML = '<span class="dashicons dashicons-editor-contract"></span>';
        this.elements.expandButton.setAttribute('aria-label', 'Collapse chat');
        this.elements.expandButton.setAttribute('title', 'Collapse chat');
        
        // Save state to localStorage
        localStorage.setItem('mpai_chat_expanded', 'true');
        
        // Publish event
        this.eventBus.publish('ui:expanded');
    }
    
    collapseChat() {
        this.elements.container.classList.remove('mpai-chat-expanded');
        this.elements.expandButton.innerHTML = '<span class="dashicons dashicons-editor-expand"></span>';
        this.elements.expandButton.setAttribute('aria-label', 'Expand chat');
        this.elements.expandButton.setAttribute('title', 'Expand chat');
        
        // Save state to localStorage
        localStorage.setItem('mpai_chat_expanded', 'false');
        
        // Publish event
        this.eventBus.publish('ui:collapsed');
    }
    
    toggleExpand() {
        if (this.elements.container.classList.contains('mpai-chat-expanded')) {
            this.collapseChat();
        } else {
            this.expandChat();
        }
    }
}
```

### 4. Utility Modules

#### 4.1. Event Bus (`event-bus.js`)
- Provides pub/sub functionality for loose coupling
- Allows modules to communicate without direct dependencies
- Simplifies event handling across the application

```javascript
class EventBus {
    constructor() {
        this.events = {};
    }
    
    subscribe(event, callback) {
        if (!this.events[event]) {
            this.events[event] = [];
        }
        
        this.events[event].push(callback);
        
        return () => {
            this.events[event] = this.events[event].filter(cb => cb !== callback);
        };
    }
    
    publish(event, data = {}) {
        if (!this.events[event]) {
            return;
        }
        
        this.events[event].forEach(callback => {
            callback(data);
        });
    }
    
    unsubscribe(event, callback) {
        if (!this.events[event]) {
            return;
        }
        
        this.events[event] = this.events[event].filter(cb => cb !== callback);
    }
}
```

#### 4.2. Storage Manager (`storage-manager.js`)
- Handles local storage operations
- Manages conversation persistence
- Provides consistent interface for storage operations

```javascript
class StorageManager {
    constructor(prefix = 'mpai_') {
        this.prefix = prefix;
    }
    
    save(key, value) {
        try {
            const serializedValue = JSON.stringify(value);
            localStorage.setItem(this.prefix + key, serializedValue);
            return true;
        } catch (error) {
            console.error('Error saving to localStorage:', error);
## Implementation Strategy

Since this is unreleased code, we can take a more direct approach to the transition. Here's a phased implementation strategy:

### Phase 1: Setup Project Structure and Core Modules (1-2 days)

1. **Create Module Files**: Set up the directory structure and create empty files for all modules.
   ```
   assets/js/
   ├── chat/
   │   ├── core/
   │   │   ├── chat-core.js
   │   │   ├── state-manager.js
   │   │   ├── ui-manager.js
   │   │   ├── api-client.js
   │   │   └── event-bus.js
   │   ├── messages/
   │   │   ├── message-renderer.js
   │   │   ├── message-factory.js
   │   │   ├── handlers/
   │   │   │   ├── base-message-handler.js
   │   │   │   ├── text-message-handler.js
   │   │   │   ├── blog-post-message-handler.js
   │   │   │   ├── interactive-message-handler.js
   │   │   │   └── system-message-handler.js
   │   ├── ui/
   │   │   ├── input-handler.js
   │   │   └── ui-controls.js
   │   └── utils/
   │       ├── storage-manager.js
   │       └── logger.js
   └── chat.js  (new entry point)
   ```

2. **Implement Event Bus**: This is the foundation of our loosely coupled architecture.

3. **Create Module Interfaces**: Define the public APIs for each module.

4. **Update Build Process**: Ensure the build system can handle the new file structure.

### Phase 2: Extract Core Functionality (2-3 days)

1. **Implement State Manager**: Extract state management from the current chat.js.
   - Move conversation state logic
   - Move UI state logic
   - Move user state logic

2. **Implement API Client**: Extract API communication logic.
   - Move message sending functionality
   - Move history loading functionality
   - Move error handling

3. **Implement UI Manager**: Extract UI management logic.
   - Move loading indicator functionality
   - Move scrolling functionality
   - Set up coordination with other UI components

4. **Implement Chat Core**: Create the main orchestrator.
   - Set up initialization logic
   - Connect all core modules
   - Expose public API

### Phase 3: Implement Message Handling System (2-3 days)

1. **Implement Base Message Handler**: Create the foundation for all message handlers.

2. **Implement Message Factory**: Create the factory for generating message components.
   - Set up message type detection
   - Create registration mechanism for new message types

3. **Implement Message Type Handlers**: Create handlers for each message type.
   - Text message handler
   - Blog post message handler
   - Interactive message handler
   - System message handler

4. **Implement Message Renderer**: Create the renderer for displaying messages.
   - Connect with message factory
   - Set up rendering logic

### Phase 4: Implement UI Components (1-2 days)

1. **Implement Input Handler**: Extract input handling logic.
   - Move auto-resize functionality
   - Move submission logic

2. **Implement UI Controls**: Extract UI control logic.
   - Move open/close functionality
   - Move expand/collapse functionality
   - Move other UI control functionality

### Phase 5: Implement Utility Modules (1 day)

1. **Implement Storage Manager**: Extract storage logic.
   - Move localStorage operations
   - Create consistent interface

2. **Implement Logger**: Extract logging functionality.
   - Create consistent logging interface
   - Add debug levels

### Phase 6: Integration and Testing (2-3 days)

1. **Create New Entry Point**: Update chat.js to use the new modular architecture.
   - Initialize core modules
   - Set up event subscriptions
   - Expose public API

2. **Test Each Module**: Write unit tests for each module.

3. **Test Integration**: Ensure all modules work together correctly.

4. **Fix Issues**: Address any issues that arise during testing.

## Code Migration Strategy

Since backward compatibility isn't a concern, we can use a "rewrite in place" approach:

1. **Extract and Rewrite**: For each piece of functionality:
   - Extract it from the original file
   - Rewrite it in the new module
   - Remove it from the original file

2. **Incremental Testing**: After each extraction, test the new module in isolation.

3. **Final Integration**: Once all modules are implemented, integrate them through the new entry point.

## Example: Extracting Message Handling

Here's how we would extract the message handling functionality:

1. **Identify Current Implementation**: In the current chat.js, message handling is spread across several methods:
   - `addUserMessage`
   - `addAssistantMessage`
   - `createMessageElement`
   - `processBlogPostContent`

2. **Create New Module Structure**:
   - Create base-message-handler.js with common functionality
   - Create specific handler files for each message type

3. **Extract and Rewrite**:
   - Move the logic from `createMessageElement` to the appropriate handlers
   - Move blog post handling from `processBlogPostContent` to blog-post-message-handler.js
   - Create a message factory to determine which handler to use

4. **Update References**:
   - Replace direct calls to the old methods with calls to the new message factory

## Benefits of This Approach

1. **Clean Break**: Since backward compatibility isn't a concern, we can make a clean break from the old architecture.

2. **Incremental Progress**: The phased approach allows for incremental progress and testing.

3. **Clear Structure**: The new architecture has a clear structure with well-defined responsibilities.

4. **Extensibility**: The message handling system is designed for easy extension with new message types.

## Potential Challenges

1. **Complexity Management**: The new architecture is more complex, so we need to ensure good documentation.

2. **Learning Curve**: Developers will need to learn the new architecture.

3. **Testing**: Comprehensive testing is needed to ensure all functionality works correctly.

## Conclusion

This modularization plan provides a clear path to transform the current monolithic chat.js into a modular, extensible architecture. By separating concerns and implementing a factory pattern for message handling, we'll make it much easier to add new interactive elements in the future while improving maintainability and following KISS and DRY principles.

The event-driven architecture with the EventBus at its core ensures loose coupling between components, making the system more robust and easier to test. Each module has a clear responsibility, and the interfaces between modules are well-defined.

With this architecture in place, adding new features or modifying existing ones will be much simpler and less prone to introducing bugs.
            return false;
        }
    }
    
    load(key, defaultValue = null) {
        try {
            const serializedValue = localStorage.getItem(this.prefix + key);
            if (serializedValue === null) {
                return defaultValue;
            }
            return JSON.parse(serializedValue);
        } catch (error) {
            console.error('Error loading from localStorage:', error);
            return defaultValue;
        }
    }
    
    remove(key) {
        try {
            localStorage.removeItem(this.prefix + key);
            return true;
        } catch (error) {
            console.error('Error removing from localStorage:', error);
            return false;
        }
    }
    
    clear() {
        try {
            // Only clear items with our prefix
            const keysToRemove = [];
            for (let i = 0; i < localStorage.length; i++) {
                const key = localStorage.key(i);
                if (key.startsWith(this.prefix)) {
                    keysToRemove.push(key);
                }
            }
            
            keysToRemove.forEach(key => localStorage.removeItem(key));
            return true;
        } catch (error) {
            console.error('Error clearing localStorage:', error);
            return false;
        }
    }
}
```

#### 4.3. Logger (`logger.js`)
- Centralizes logging functionality
- Provides different log levels
- Can be configured for development/production

```javascript
class Logger {
    constructor(options = {}) {
        this.debug = options.debug || false;
        this.prefix = options.prefix || '[MPAI Chat]';
        this.logLevel = options.logLevel || 'info'; // 'debug', 'info', 'warn', 'error'
        
        this.logLevels = {
            debug: 0,
            info: 1,
            warn: 2,
            error: 3
        };
    }
    
    shouldLog(level) {
        return this.debug && this.logLevels[level] >= this.logLevels[this.logLevel];
    }
    
    debug(...args) {
        if (this.shouldLog('debug')) {
            console.debug(this.prefix, ...args);
        }
    }
    
    log(...args) {
        if (this.shouldLog('info')) {
            console.log(this.prefix, ...args);
        }
    }
    
    warn(...args) {
        if (this.shouldLog('warn')) {
            console.warn(this.prefix, ...args);
        }
    }
    
    error(...args) {
        if (this.shouldLog('error')) {
            console.error(this.prefix, ...args);
        }
    }
}
```
   ```javascript
   // Example: Adding slider event handling
   setupInteractiveElements(container) {
       // ... existing event handlers
       
       // Add event listeners for sliders
       const sliders = container.querySelectorAll('.mpai-slider');
       sliders.forEach(slider => {
           slider.addEventListener('change', (e) => {
               const action = slider.getAttribute('data-action');
               if (action) {
                   this.eventBus.publish('interactive:action', {
                       type: 'slider',
                       action,
                       value: slider.value,
                       element: slider
                   });
               }
           });
       });
   }
   ```

4. **Add Response Handling**: Update the chat core to handle responses to the new interactive element
   ```javascript
   // Example: Handling slider actions in chat-core.js
   this.eventBus.subscribe('interactive:action', (data) => {
       if (data.type === 'slider') {
           // Handle slider action
           this.sendMessage(`Selected value: ${data.value} for action: ${data.action}`);
       }
   });
   ```

This approach allows for adding new interactive elements without modifying existing code, following the Open/Closed Principle.
                    error
                });
            });
    }
    
    // API methods
    sendMessage(message, conversationId) {
        return new Promise((resolve, reject) => {
            // Prepare request data
            const requestData = {
                message: message,
                conversation_id: conversationId,
                user_logged_in: window.mpai_user_logged_in === true
            };
            
            // Send request to the backend
            fetch(this.endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': window.mpai_nonce || ''
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
                reject(error);
            });
        });
    }
    
    loadHistory(conversationId) {
        // Similar implementation to sendMessage
    }
    
    clearHistory(conversationId) {
        // Similar implementation to sendMessage
    }
}
```