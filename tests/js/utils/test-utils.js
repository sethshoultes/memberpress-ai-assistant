/**
 * Test utilities for MemberPress AI Assistant frontend tests
 */

// Helper function to create a mock DOM element
export function createMockElement(tagName, attributes = {}, children = []) {
  const element = document.createElement(tagName);
  
  // Set attributes
  Object.entries(attributes).forEach(([key, value]) => {
    if (key === 'className') {
      element.className = value;
    } else if (key === 'textContent') {
      element.textContent = value;
    } else if (key === 'innerHTML') {
      element.innerHTML = value;
    } else if (key === 'style') {
      Object.entries(value).forEach(([prop, val]) => {
        element.style[prop] = val;
      });
    } else {
      element.setAttribute(key, value);
    }
  });
  
  // Add children
  children.forEach(child => {
    element.appendChild(child);
  });
  
  return element;
}

// Helper function to simulate events
export function simulateEvent(element, eventName, eventData = {}) {
  const event = new Event(eventName, {
    bubbles: true,
    cancelable: true,
    ...eventData
  });
  
  element.dispatchEvent(event);
  return event;
}

// Mock API response generator
export function createMockApiResponse(data, status = 200, statusText = 'OK') {
  return {
    ok: status >= 200 && status < 300,
    status,
    statusText,
    json: jest.fn().mockResolvedValue(data),
    text: jest.fn().mockResolvedValue(JSON.stringify(data))
  };
}

// Setup global mocks for components
export function setupGlobalMocks() {
  // Mock MPAITextFormatter
  window.MPAITextFormatter = {
    formatText: jest.fn(text => `<p>${text}</p>`),
    formatPlainText: jest.fn(text => text),
    autoLink: jest.fn(text => text),
    truncateText: jest.fn((text, length) => text.length > length ? text.substring(0, length) + '...' : text),
    escapeHtml: jest.fn(text => text)
  };
  
  // Mock MPAIXMLProcessor
  window.MPAIXMLProcessor = {
    processMessage: jest.fn(content => content),
    parseXML: jest.fn(),
    extractTags: jest.fn(() => []),
    containsXML: jest.fn(() => false)
  };
  
  // Mock MPAIContentPreview
  window.MPAIContentPreview = {
    createTextPreview: jest.fn(),
    createImagePreview: jest.fn(),
    createTablePreview: jest.fn(),
    createCodePreview: jest.fn(),
    createPreview: jest.fn(),
    detectContentType: jest.fn(),
    parseMarkdownTable: jest.fn()
  };
  
  // Mock MPAIButtonRenderer
  window.MPAIButtonRenderer = {
    createButton: jest.fn(),
    createButtonGroup: jest.fn(),
    renderButton: jest.fn(),
    renderButtonGroup: jest.fn(),
    updateButton: jest.fn()
  };
  
  // Mock MPAIFormGenerator
  window.MPAIFormGenerator = {
    createForm: jest.fn(),
    createFormField: jest.fn(),
    createToolParametersForm: jest.fn(),
    validateField: jest.fn(),
    getFormData: jest.fn()
  };
  
  // Mock MPAIDataHandler
  window.MPAIDataHandler = {
    processData: jest.fn()
  };
  
  // Mock WordPress nonce
  window.mpai_nonce = 'test_nonce';
}

// Clean up global mocks
export function cleanupGlobalMocks() {
  delete window.MPAITextFormatter;
  delete window.MPAIXMLProcessor;
  delete window.MPAIContentPreview;
  delete window.MPAIButtonRenderer;
  delete window.MPAIFormGenerator;
  delete window.MPAIDataHandler;
  delete window.mpai_nonce;
}