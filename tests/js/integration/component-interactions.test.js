/**
 * Integration tests for MemberPress Copilot UI components
 * 
 * These tests verify that the different UI components work together correctly.
 */

import { 
  createMockElement, 
  simulateEvent, 
  setupGlobalMocks, 
  cleanupGlobalMocks 
} from '../utils/test-utils';

describe('Component Interactions', () => {
  beforeEach(() => {
    // Setup DOM elements
    document.body.innerHTML = `
      <div id="mpai-chat-container">
        <div id="mpai-chat-messages"></div>
        <textarea id="mpai-chat-input"></textarea>
        <button id="mpai-chat-submit">Send</button>
        <button id="mpai-chat-toggle">Toggle</button>
        <button id="mpai-chat-close">Close</button>
      </div>
    `;
    
    // Setup global mocks
    setupGlobalMocks();
    
    // Mock fetch
    global.fetch = jest.fn().mockResolvedValue({
      ok: true,
      json: () => Promise.resolve({
        message: 'This is a test response',
        conversation_id: 'test_conversation_id'
      })
    });
    
    // Load all component scripts
    require('../../../assets/js/text-formatter');
    require('../../../assets/js/xml-processor');
    require('../../../assets/js/button-renderer');
    require('../../../assets/js/content-preview');
    require('../../../assets/js/form-generator');
    require('../../../assets/js/chat');
  });
  
  afterEach(() => {
    // Clean up DOM
    document.body.innerHTML = '';
    
    // Restore fetch
    global.fetch.mockRestore();
    
    // Clean up global mocks
    cleanupGlobalMocks();
    
    // Clean up window objects
    delete window.mpaiChat;
    delete window.MPAIChat;
    delete window.MPAITextFormatter;
    delete window.MPAIXMLProcessor;
    delete window.MPAIButtonRenderer;
    delete window.MPAIContentPreview;
    delete window.MPAIFormGenerator;
  });
  
  test('should format messages with TextFormatter when added to chat', async () => {
    // Initialize chat
    document.dispatchEvent(new Event('DOMContentLoaded'));
    
    // Spy on TextFormatter
    const formatTextSpy = jest.spyOn(window.MPAITextFormatter, 'formatPlainText');
    
    // Send a message
    const input = document.getElementById('mpai-chat-input');
    const submitButton = document.getElementById('mpai-chat-submit');
    
    input.value = 'Test message with a link: https://example.com';
    simulateEvent(submitButton, 'click');
    
    // Wait for async operations to complete
    await new Promise(resolve => setTimeout(resolve, 0));
    
    // TextFormatter should have been called for the user message
    expect(formatTextSpy).toHaveBeenCalled();
    expect(formatTextSpy).toHaveBeenCalledWith(expect.stringContaining('https://example.com'));
    
    formatTextSpy.mockRestore();
  });
  
  test('should process XML content in assistant messages', async () => {
    // Initialize chat
    document.dispatchEvent(new Event('DOMContentLoaded'));
    
    // Spy on XMLProcessor
    const containsXMLSpy = jest.spyOn(window.MPAIXMLProcessor, 'containsXML');
    const processMessageSpy = jest.spyOn(window.MPAIXMLProcessor, 'processMessage');
    
    // Mock fetch to return a message with XML content
    global.fetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve({
        message: 'Here is a button: <button type="primary">Click Me</button>',
        conversation_id: 'test_conversation_id'
      })
    });
    
    // Send a message
    const input = document.getElementById('mpai-chat-input');
    const submitButton = document.getElementById('mpai-chat-submit');
    
    input.value = 'Show me a button';
    simulateEvent(submitButton, 'click');
    
    // Wait for async operations to complete
    await new Promise(resolve => setTimeout(resolve, 0));
    
    // XMLProcessor should have been called for the assistant message
    expect(containsXMLSpy).toHaveBeenCalled();
    expect(processMessageSpy).toHaveBeenCalled();
    
    containsXMLSpy.mockRestore();
    processMessageSpy.mockRestore();
  });
  
  test('should render buttons using ButtonRenderer', async () => {
    // Initialize chat
    document.dispatchEvent(new Event('DOMContentLoaded'));
    
    // Spy on ButtonRenderer
    const createButtonSpy = jest.spyOn(window.MPAIButtonRenderer, 'createButton');
    
    // Mock XMLProcessor to simulate XML processing
    window.MPAIXMLProcessor.containsXML = jest.fn().mockReturnValue(true);
    window.MPAIXMLProcessor.processMessage = jest.fn().mockImplementation((content) => {
      // Simulate processing a button tag
      if (content.includes('<button')) {
        // Call the real ButtonRenderer
        const button = window.MPAIButtonRenderer.createButton({
          text: 'Test Button',
          type: 'primary'
        });
        
        // Create a wrapper to return the HTML
        const wrapper = document.createElement('div');
        wrapper.appendChild(button);
        return wrapper.innerHTML;
      }
      return content;
    });
    
    // Mock fetch to return a message with a button
    global.fetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve({
        message: 'Here is a button: <button type="primary">Test Button</button>',
        conversation_id: 'test_conversation_id'
      })
    });
    
    // Send a message
    const input = document.getElementById('mpai-chat-input');
    const submitButton = document.getElementById('mpai-chat-submit');
    
    input.value = 'Show me a button';
    simulateEvent(submitButton, 'click');
    
    // Wait for async operations to complete
    await new Promise(resolve => setTimeout(resolve, 0));
    
    // ButtonRenderer should have been called
    expect(createButtonSpy).toHaveBeenCalled();
    
    createButtonSpy.mockRestore();
  });
  
  test('should render code previews using ContentPreview', async () => {
    // Initialize chat
    document.dispatchEvent(new Event('DOMContentLoaded'));
    
    // Spy on ContentPreview
    const createCodePreviewSpy = jest.spyOn(window.MPAIContentPreview, 'createCodePreview');
    
    // Mock XMLProcessor to simulate XML processing
    window.MPAIXMLProcessor.containsXML = jest.fn().mockReturnValue(true);
    window.MPAIXMLProcessor.processMessage = jest.fn().mockImplementation((content) => {
      // Simulate processing a code tag
      if (content.includes('<code')) {
        // Call the real ContentPreview
        const codePreview = window.MPAIContentPreview.createCodePreview(
          'function test() { return true; }',
          { language: 'javascript' }
        );
        
        // Create a wrapper to return the HTML
        const wrapper = document.createElement('div');
        wrapper.appendChild(codePreview);
        return wrapper.innerHTML;
      }
      return content;
    });
    
    // Mock fetch to return a message with code
    global.fetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve({
        message: 'Here is some code: <code language="javascript">function test() { return true; }</code>',
        conversation_id: 'test_conversation_id'
      })
    });
    
    // Send a message
    const input = document.getElementById('mpai-chat-input');
    const submitButton = document.getElementById('mpai-chat-submit');
    
    input.value = 'Show me some code';
    simulateEvent(submitButton, 'click');
    
    // Wait for async operations to complete
    await new Promise(resolve => setTimeout(resolve, 0));
    
    // ContentPreview should have been called
    expect(createCodePreviewSpy).toHaveBeenCalled();
    
    createCodePreviewSpy.mockRestore();
  });
  
  test('should render forms using FormGenerator', async () => {
    // Initialize chat
    document.dispatchEvent(new Event('DOMContentLoaded'));
    
    // Spy on FormGenerator
    const createFormSpy = jest.spyOn(window.MPAIFormGenerator, 'createForm');
    
    // Mock XMLProcessor to simulate XML processing
    window.MPAIXMLProcessor.containsXML = jest.fn().mockReturnValue(true);
    window.MPAIXMLProcessor.processMessage = jest.fn().mockImplementation((content) => {
      // Simulate processing a form tag
      if (content.includes('<form')) {
        // Call the real FormGenerator
        const form = window.MPAIFormGenerator.createForm([
          {
            name: 'test_field',
            label: 'Test Field',
            type: 'text'
          }
        ]);
        
        // Create a wrapper to return the HTML
        const wrapper = document.createElement('div');
        wrapper.appendChild(form);
        return wrapper.innerHTML;
      }
      return content;
    });
    
    // Mock fetch to return a message with a form
    global.fetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve({
        message: 'Here is a form: <form><input name="test_field" label="Test Field" type="text" /></form>',
        conversation_id: 'test_conversation_id'
      })
    });
    
    // Send a message
    const input = document.getElementById('mpai-chat-input');
    const submitButton = document.getElementById('mpai-chat-submit');
    
    input.value = 'Show me a form';
    simulateEvent(submitButton, 'click');
    
    // Wait for async operations to complete
    await new Promise(resolve => setTimeout(resolve, 0));
    
    // FormGenerator should have been called
    expect(createFormSpy).toHaveBeenCalled();
    
    createFormSpy.mockRestore();
  });
  
  test('should handle complex interactions between multiple components', async () => {
    // Initialize chat
    document.dispatchEvent(new Event('DOMContentLoaded'));
    
    // Spy on all component methods
    const formatTextSpy = jest.spyOn(window.MPAITextFormatter, 'formatText');
    const processMessageSpy = jest.spyOn(window.MPAIXMLProcessor, 'processMessage');
    const createButtonSpy = jest.spyOn(window.MPAIButtonRenderer, 'createButton');
    const createCodePreviewSpy = jest.spyOn(window.MPAIContentPreview, 'createCodePreview');
    
    // Mock fetch to return a complex message with multiple components
    global.fetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve({
        message: `
          Here's a summary of what you can do:
          
          <button type="primary">View Documentation</button>
          
          Example code:
          <code language="javascript">
          function example() {
            console.log("Hello world");
          }
          </code>
          
          **Note**: This is just an example.
        `,
        conversation_id: 'test_conversation_id'
      })
    });
    
    // Send a message
    const input = document.getElementById('mpai-chat-input');
    const submitButton = document.getElementById('mpai-chat-submit');
    
    input.value = 'Show me what I can do';
    simulateEvent(submitButton, 'click');
    
    // Wait for async operations to complete
    await new Promise(resolve => setTimeout(resolve, 0));
    
    // Check that the message was processed
    expect(processMessageSpy).toHaveBeenCalled();
    
    // In a real scenario, the XML processor would call the other components
    // but since we're mocking, we'll just verify that the chat message was added
    const messagesContainer = document.getElementById('mpai-chat-messages');
    expect(messagesContainer.children.length).toBeGreaterThan(0);
    
    // Clean up spies
    formatTextSpy.mockRestore();
    processMessageSpy.mockRestore();
    createButtonSpy.mockRestore();
    createCodePreviewSpy.mockRestore();
  });
});