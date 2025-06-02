/**
 * Mock DOM elements for MemberPress AI Assistant tests
 */

/**
 * Create a mock chat interface DOM structure
 * 
 * @returns {Object} Object containing the created DOM elements
 */
export function createMockChatInterface() {
  // Create container
  const container = document.createElement('div');
  container.id = 'mpai-chat-container';
  container.className = 'mpai-chat';
  
  // Create header
  const header = document.createElement('div');
  header.className = 'mpai-chat-header';
  
  const title = document.createElement('h3');
  title.className = 'mpai-chat-title';
  title.textContent = 'MemberPress AI Assistant';
  
  const closeButton = document.createElement('button');
  closeButton.id = 'mpai-chat-close';
  closeButton.className = 'mpai-chat-close';
  closeButton.innerHTML = '&times;';
  closeButton.setAttribute('aria-label', 'Close chat');
  
  header.appendChild(title);
  header.appendChild(closeButton);
  
  // Create messages container
  const messagesContainer = document.createElement('div');
  messagesContainer.id = 'mpai-chat-messages';
  messagesContainer.className = 'mpai-chat-messages';
  
  // Create input area
  const inputArea = document.createElement('div');
  inputArea.className = 'mpai-chat-input-area';
  
  const input = document.createElement('textarea');
  input.id = 'mpai-chat-input';
  input.className = 'mpai-chat-input';
  input.placeholder = 'Type your message...';
  input.rows = 1;
  
  const submitButton = document.createElement('button');
  submitButton.id = 'mpai-chat-submit';
  submitButton.className = 'mpai-chat-submit';
  submitButton.textContent = 'Send';
  
  inputArea.appendChild(input);
  inputArea.appendChild(submitButton);
  
  // Create toggle button
  const toggleButton = document.createElement('button');
  toggleButton.id = 'mpai-chat-toggle';
  toggleButton.className = 'mpai-chat-toggle';
  toggleButton.textContent = 'Chat';
  
  // Assemble chat interface
  container.appendChild(header);
  container.appendChild(messagesContainer);
  container.appendChild(inputArea);
  
  // Add elements to document
  document.body.appendChild(container);
  document.body.appendChild(toggleButton);
  
  // Return references to elements
  return {
    container,
    header,
    title,
    closeButton,
    messagesContainer,
    inputArea,
    input,
    submitButton,
    toggleButton
  };
}

/**
 * Create a mock message element
 * 
 * @param {string} role The message role ('user' or 'assistant')
 * @param {string} content The message content
 * @returns {HTMLElement} The created message element
 */
export function createMockMessage(role, content) {
  const messageDiv = document.createElement('div');
  messageDiv.className = `mpai-chat-message mpai-chat-message-${role}`;
  
  const contentDiv = document.createElement('div');
  contentDiv.className = 'mpai-chat-message-content';
  contentDiv.innerHTML = content;
  
  messageDiv.appendChild(contentDiv);
  
  return messageDiv;
}

/**
 * Create a mock loading indicator
 * 
 * @returns {HTMLElement} The created loading indicator element
 */
export function createMockLoadingIndicator() {
  const loadingDiv = document.createElement('div');
  loadingDiv.className = 'mpai-chat-loading';
  loadingDiv.id = 'mpai-chat-loading';
  
  // Add loading dots
  for (let i = 0; i < 3; i++) {
    const dot = document.createElement('div');
    dot.className = 'mpai-chat-loading-dot';
    loadingDiv.appendChild(dot);
  }
  
  return loadingDiv;
}

/**
 * Create a mock button element
 * 
 * @param {Object} options Button configuration options
 * @returns {HTMLButtonElement} The created button element
 */
export function createMockButton(options = {}) {
  const {
    text = 'Button',
    type = 'primary',
    size = 'medium',
    onClick = null
  } = options;
  
  const button = document.createElement('button');
  button.className = `mpai-btn mpai-btn-${type} mpai-btn-${size}`;
  button.textContent = text;
  
  if (onClick) {
    button.addEventListener('click', onClick);
  }
  
  return button;
}

/**
 * Create a mock code preview element
 * 
 * @param {string} code The code content
 * @param {string} language The code language
 * @returns {HTMLElement} The created code preview element
 */
export function createMockCodePreview(code, language = 'javascript') {
  const container = document.createElement('div');
  container.className = 'mpai-preview mpai-code-preview';
  
  // Create code header
  const header = document.createElement('div');
  header.className = 'mpai-code-header';
  
  const languageName = document.createElement('span');
  languageName.className = 'mpai-code-language';
  languageName.textContent = language;
  header.appendChild(languageName);
  
  const copyButton = document.createElement('button');
  copyButton.className = 'mpai-code-copy';
  copyButton.innerHTML = '<span class="dashicons dashicons-clipboard"></span>';
  header.appendChild(copyButton);
  
  container.appendChild(header);
  
  // Create code wrapper
  const codeWrapper = document.createElement('div');
  codeWrapper.className = 'mpai-code-wrapper';
  
  const codeElement = document.createElement('pre');
  codeElement.className = 'mpai-code';
  codeElement.textContent = code;
  
  codeWrapper.appendChild(codeElement);
  container.appendChild(codeWrapper);
  
  return container;
}

/**
 * Create a mock table preview element
 * 
 * @param {Array<Array<string>>} data Table data (array of rows, each row is an array of cells)
 * @param {Array<string>} headers Table headers
 * @returns {HTMLElement} The created table preview element
 */
export function createMockTablePreview(data, headers = []) {
  const container = document.createElement('div');
  container.className = 'mpai-preview mpai-table-preview';
  
  const table = document.createElement('table');
  table.className = 'mpai-table mpai-table-striped mpai-table-bordered';
  
  // Add headers if provided
  if (headers.length > 0) {
    const thead = document.createElement('thead');
    const headerRow = document.createElement('tr');
    
    headers.forEach(header => {
      const th = document.createElement('th');
      th.textContent = header;
      headerRow.appendChild(th);
    });
    
    thead.appendChild(headerRow);
    table.appendChild(thead);
  }
  
  // Add data rows
  const tbody = document.createElement('tbody');
  
  data.forEach(rowData => {
    const row = document.createElement('tr');
    
    rowData.forEach(cellData => {
      const cell = document.createElement('td');
      cell.textContent = cellData;
      row.appendChild(cell);
    });
    
    tbody.appendChild(row);
  });
  
  table.appendChild(tbody);
  container.appendChild(table);
  
  return container;
}

/**
 * Create a mock form element
 * 
 * @param {Array<Object>} fields Form fields configuration
 * @returns {HTMLFormElement} The created form element
 */
export function createMockForm(fields = []) {
  const form = document.createElement('form');
  form.className = 'mpai-form';
  
  // Add fields
  fields.forEach(field => {
    const fieldContainer = document.createElement('div');
    fieldContainer.className = `mpai-form-field mpai-form-field-${field.type || 'text'}`;
    
    // Add label if provided
    if (field.label) {
      const label = document.createElement('label');
      label.className = 'mpai-form-field-label';
      label.textContent = field.label;
      fieldContainer.appendChild(label);
    }
    
    // Create input element
    let input;
    
    switch (field.type) {
      case 'textarea':
        input = document.createElement('textarea');
        input.rows = field.rows || 4;
        break;
      case 'select':
        input = document.createElement('select');
        
        // Add options
        if (field.options) {
          field.options.forEach(option => {
            const optionElement = document.createElement('option');
            
            if (typeof option === 'object') {
              optionElement.value = option.value;
              optionElement.textContent = option.label;
            } else {
              optionElement.value = option;
              optionElement.textContent = option;
            }
            
            input.appendChild(optionElement);
          });
        }
        break;
      default:
        input = document.createElement('input');
        input.type = field.type || 'text';
        break;
    }
    
    // Set common attributes
    input.name = field.name;
    input.className = 'mpai-form-field-input';
    
    if (field.placeholder) input.placeholder = field.placeholder;
    if (field.value) input.value = field.value;
    if (field.required) input.required = true;
    
    fieldContainer.appendChild(input);
    form.appendChild(fieldContainer);
  });
  
  // Add buttons
  const buttonsContainer = document.createElement('div');
  buttonsContainer.className = 'mpai-form-buttons';
  
  const submitButton = document.createElement('button');
  submitButton.type = 'submit';
  submitButton.className = 'mpai-form-button mpai-form-button-submit';
  submitButton.textContent = 'Submit';
  
  buttonsContainer.appendChild(submitButton);
  form.appendChild(buttonsContainer);
  
  return form;
}

/**
 * Setup mock DOM environment
 * 
 * @returns {Function} Function to clean up the mock DOM environment
 */
export function setupMockDOM() {
  // Store original document methods
  const originalCreateElement = document.createElement;
  const originalGetElementById = document.getElementById;
  const originalQuerySelector = document.querySelector;
  const originalQuerySelectorAll = document.querySelectorAll;
  
  // Create a map to store mock elements
  const mockElements = new Map();
  
  // Override document.createElement
  document.createElement = jest.fn(tag => {
    const element = originalCreateElement.call(document, tag);
    
    // Add mock methods
    element.addEventListener = jest.fn((event, handler) => {
      if (!element._eventListeners) {
        element._eventListeners = {};
      }
      
      if (!element._eventListeners[event]) {
        element._eventListeners[event] = [];
      }
      
      element._eventListeners[event].push(handler);
    });
    
    element.removeEventListener = jest.fn((event, handler) => {
      if (!element._eventListeners || !element._eventListeners[event]) {
        return;
      }
      
      element._eventListeners[event] = element._eventListeners[event].filter(h => h !== handler);
    });
    
    element.dispatchEvent = jest.fn(event => {
      if (!element._eventListeners || !element._eventListeners[event.type]) {
        return true;
      }
      
      element._eventListeners[event.type].forEach(handler => {
        handler.call(element, event);
      });
      
      return !event.defaultPrevented;
    });
    
    return element;
  });
  
  // Override document.getElementById
  document.getElementById = jest.fn(id => {
    if (mockElements.has(id)) {
      return mockElements.get(id);
    }
    
    return originalGetElementById.call(document, id);
  });
  
  // Override document.querySelector
  document.querySelector = jest.fn(selector => {
    // Check if selector is an ID selector
    if (selector.startsWith('#')) {
      const id = selector.substring(1);
      if (mockElements.has(id)) {
        return mockElements.get(id);
      }
    }
    
    return originalQuerySelector.call(document, selector);
  });
  
  // Override document.querySelectorAll
  document.querySelectorAll = jest.fn(selector => {
    return originalQuerySelectorAll.call(document, selector);
  });
  
  // Return function to restore original methods
  return () => {
    document.createElement = originalCreateElement;
    document.getElementById = originalGetElementById;
    document.querySelector = originalQuerySelector;
    document.querySelectorAll = originalQuerySelectorAll;
    
    // Clear mock elements
    mockElements.clear();
  };
}