/**
 * Unit tests for chat.js
 */

import { 
  createMockElement, 
  simulateEvent, 
  createMockApiResponse, 
  setupGlobalMocks, 
  cleanupGlobalMocks 
} from '../utils/test-utils';

describe('MPAIChat', () => {
  // Mock DOM elements
  let container;
  let messagesContainer;
  let input;
  let submitButton;
  let toggleButton;
  let closeButton;
  
  // Store original fetch
  let originalFetch;
  
  beforeEach(() => {
    // Setup DOM elements
    container = createMockElement('div', { id: 'mpai-chat-container' });
    messagesContainer = createMockElement('div', { id: 'mpai-chat-messages' });
    input = createMockElement('textarea', { id: 'mpai-chat-input' });
    submitButton = createMockElement('button', { id: 'mpai-chat-submit' });
    toggleButton = createMockElement('button', { id: 'mpai-chat-toggle' });
    closeButton = createMockElement('button', { id: 'mpai-chat-close' });
    
    // Add elements to document
    document.body.appendChild(container);
    document.body.appendChild(messagesContainer);
    document.body.appendChild(input);
    document.body.appendChild(submitButton);
    document.body.appendChild(toggleButton);
    document.body.appendChild(closeButton);
    
    // Setup global mocks
    setupGlobalMocks();
    
    // Mock fetch
    originalFetch = global.fetch;
    global.fetch = jest.fn();
    
    // Load the chat.js script
    require('../../../assets/js/chat');
  });
  
  afterEach(() => {
    // Clean up DOM
    document.body.innerHTML = '';
    
    // Restore fetch
    global.fetch = originalFetch;
    
    // Clean up global mocks
    cleanupGlobalMocks();
    
    // Clean up window.mpaiChat
    delete window.mpaiChat;
    delete window.MPAIChat;
  });
  
  test('should initialize chat interface', () => {
    // Trigger DOMContentLoaded to initialize chat
    document.dispatchEvent(new Event('DOMContentLoaded'));
    
    // Check if chat was initialized
    expect(window.mpaiChat).toBeDefined();
    expect(window.MPAIChat).toBeDefined();
  });
  
  test('should toggle chat visibility', () => {
    // Trigger DOMContentLoaded to initialize chat
    document.dispatchEvent(new Event('DOMContentLoaded'));
    
    // Initial state should be closed
    expect(container.classList.contains('active')).toBe(false);
    
    // Click toggle button to open
    simulateEvent(toggleButton, 'click');
    
    // Chat should be open
    expect(container.classList.contains('active')).toBe(true);
    expect(toggleButton.classList.contains('active')).toBe(true);
    
    // Click toggle button again to close
    simulateEvent(toggleButton, 'click');
    
    // Chat should be closed
    expect(container.classList.contains('active')).toBe(false);
    expect(toggleButton.classList.contains('active')).toBe(false);
  });
  
  test('should close chat when close button is clicked', () => {
    // Trigger DOMContentLoaded to initialize chat
    document.dispatchEvent(new Event('DOMContentLoaded'));
    
    // Open chat first
    simulateEvent(toggleButton, 'click');
    expect(container.classList.contains('active')).toBe(true);
    
    // Click close button
    simulateEvent(closeButton, 'click');
    
    // Chat should be closed
    expect(container.classList.contains('active')).toBe(false);
  });
  
  test('should send message when submit button is clicked', async () => {
    // Setup mock response
    const mockResponse = {
      message: 'This is a test response',
      conversation_id: 'test_conversation_id'
    };
    
    global.fetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve(mockResponse)
    });
    
    // Trigger DOMContentLoaded to initialize chat
    document.dispatchEvent(new Event('DOMContentLoaded'));
    
    // Set input value
    input.value = 'Test message';
    
    // Click submit button
    simulateEvent(submitButton, 'click');
    
    // Check if fetch was called with correct parameters
    expect(global.fetch).toHaveBeenCalledWith(
      expect.any(String),
      expect.objectContaining({
        method: 'POST',
        headers: expect.objectContaining({
          'Content-Type': 'application/json',
          'X-WP-Nonce': 'test_nonce'
        }),
        body: expect.stringContaining('Test message')
      })
    );
    
    // Wait for async operations to complete
    await new Promise(resolve => setTimeout(resolve, 0));
    
    // Check if messages were added to the chat
    expect(messagesContainer.children.length).toBeGreaterThan(0);
  });
  
  test('should send message when Enter key is pressed', async () => {
    // Setup mock response
    const mockResponse = {
      message: 'This is a test response',
      conversation_id: 'test_conversation_id'
    };
    
    global.fetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve(mockResponse)
    });
    
    // Trigger DOMContentLoaded to initialize chat
    document.dispatchEvent(new Event('DOMContentLoaded'));
    
    // Set input value
    input.value = 'Test message';
    
    // Press Enter key
    const enterEvent = new KeyboardEvent('keydown', {
      key: 'Enter',
      bubbles: true,
      cancelable: true
    });
    input.dispatchEvent(enterEvent);
    
    // Check if fetch was called
    expect(global.fetch).toHaveBeenCalled();
    
    // Wait for async operations to complete
    await new Promise(resolve => setTimeout(resolve, 0));
    
    // Check if messages were added to the chat
    expect(messagesContainer.children.length).toBeGreaterThan(0);
  });
  
  test('should not send empty messages', () => {
    // Trigger DOMContentLoaded to initialize chat
    document.dispatchEvent(new Event('DOMContentLoaded'));
    
    // Set empty input value
    input.value = '';
    
    // Click submit button
    simulateEvent(submitButton, 'click');
    
    // Fetch should not be called
    expect(global.fetch).not.toHaveBeenCalled();
  });
  
  test('should handle API errors gracefully', async () => {
    // Setup mock error response
    global.fetch.mockRejectedValueOnce(new Error('API error'));
    
    // Trigger DOMContentLoaded to initialize chat
    document.dispatchEvent(new Event('DOMContentLoaded'));
    
    // Set input value
    input.value = 'Test message';
    
    // Click submit button
    simulateEvent(submitButton, 'click');
    
    // Wait for async operations to complete
    await new Promise(resolve => setTimeout(resolve, 0));
    
    // Check if error message was added to the chat
    const lastMessage = messagesContainer.lastChild;
    expect(lastMessage).toBeDefined();
    expect(lastMessage.textContent).toContain('error');
  });
  
  test('should generate a unique conversation ID', () => {
    // Trigger DOMContentLoaded to initialize chat
    document.dispatchEvent(new Event('DOMContentLoaded'));
    
    // Get the conversation ID
    const conversationId = window.mpaiChat.state.conversationId;
    
    // Should be a string starting with 'conv_'
    expect(typeof conversationId).toBe('string');
    expect(conversationId.startsWith('conv_')).toBe(true);
  });
  
  test('should auto-open chat if configured', () => {
    // Clean up previous chat instance
    delete window.mpaiChat;
    delete window.MPAIChat;
    
    // Set auto-open config
    window.mpai_chat_config = {
      autoOpen: true
    };
    
    // Trigger DOMContentLoaded to initialize chat
    document.dispatchEvent(new Event('DOMContentLoaded'));
    
    // Chat should be open
    expect(container.classList.contains('active')).toBe(true);
    
    // Clean up
    delete window.mpai_chat_config;
  });
});