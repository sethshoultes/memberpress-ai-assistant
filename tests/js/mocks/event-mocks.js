/**
 * Mock event handling for MemberPress Copilot tests
 */

/**
 * Create a mock event
 * 
 * @param {string} type Event type
 * @param {Object} options Event options
 * @returns {Event} The created event
 */
export function createMockEvent(type, options = {}) {
  const {
    bubbles = true,
    cancelable = true,
    composed = true,
    ...eventData
  } = options;
  
  const event = new Event(type, {
    bubbles,
    cancelable,
    composed
  });
  
  // Add custom properties
  Object.entries(eventData).forEach(([key, value]) => {
    Object.defineProperty(event, key, {
      value,
      enumerable: true
    });
  });
  
  return event;
}

/**
 * Create a mock keyboard event
 * 
 * @param {string} type Event type (keydown, keyup, keypress)
 * @param {Object} options Event options
 * @returns {KeyboardEvent} The created keyboard event
 */
export function createMockKeyboardEvent(type, options = {}) {
  const {
    key = '',
    code = '',
    keyCode = 0,
    which = keyCode,
    altKey = false,
    ctrlKey = false,
    shiftKey = false,
    metaKey = false,
    repeat = false,
    bubbles = true,
    cancelable = true,
    composed = true
  } = options;
  
  const event = new KeyboardEvent(type, {
    key,
    code,
    keyCode,
    which,
    altKey,
    ctrlKey,
    shiftKey,
    metaKey,
    repeat,
    bubbles,
    cancelable,
    composed
  });
  
  return event;
}

/**
 * Create a mock mouse event
 * 
 * @param {string} type Event type (click, mousedown, mouseup, etc.)
 * @param {Object} options Event options
 * @returns {MouseEvent} The created mouse event
 */
export function createMockMouseEvent(type, options = {}) {
  const {
    clientX = 0,
    clientY = 0,
    screenX = 0,
    screenY = 0,
    button = 0,
    buttons = 0,
    altKey = false,
    ctrlKey = false,
    shiftKey = false,
    metaKey = false,
    bubbles = true,
    cancelable = true,
    composed = true
  } = options;
  
  const event = new MouseEvent(type, {
    clientX,
    clientY,
    screenX,
    screenY,
    button,
    buttons,
    altKey,
    ctrlKey,
    shiftKey,
    metaKey,
    bubbles,
    cancelable,
    composed
  });
  
  return event;
}

/**
 * Create a mock focus event
 * 
 * @param {string} type Event type (focus, blur, focusin, focusout)
 * @param {Object} options Event options
 * @returns {FocusEvent} The created focus event
 */
export function createMockFocusEvent(type, options = {}) {
  const {
    relatedTarget = null,
    bubbles = true,
    cancelable = false,
    composed = true
  } = options;
  
  const event = new FocusEvent(type, {
    relatedTarget,
    bubbles,
    cancelable,
    composed
  });
  
  return event;
}

/**
 * Create a mock input event
 * 
 * @param {string} type Event type (input, change)
 * @param {Object} options Event options
 * @returns {InputEvent} The created input event
 */
export function createMockInputEvent(type, options = {}) {
  const {
    inputType = 'insertText',
    data = '',
    dataTransfer = null,
    isComposing = false,
    bubbles = true,
    cancelable = false,
    composed = true
  } = options;
  
  const event = new InputEvent(type, {
    inputType,
    data,
    dataTransfer,
    isComposing,
    bubbles,
    cancelable,
    composed
  });
  
  return event;
}

/**
 * Create a mock custom event
 * 
 * @param {string} type Event type
 * @param {Object} detail Event detail data
 * @param {Object} options Event options
 * @returns {CustomEvent} The created custom event
 */
export function createMockCustomEvent(type, detail = {}, options = {}) {
  const {
    bubbles = true,
    cancelable = true,
    composed = true
  } = options;
  
  const event = new CustomEvent(type, {
    detail,
    bubbles,
    cancelable,
    composed
  });
  
  return event;
}

/**
 * Simulate an event on an element
 * 
 * @param {HTMLElement} element The target element
 * @param {Event} event The event to dispatch
 * @returns {boolean} Whether the event was canceled
 */
export function simulateEvent(element, event) {
  return element.dispatchEvent(event);
}

/**
 * Simulate a click event on an element
 * 
 * @param {HTMLElement} element The target element
 * @param {Object} options Click event options
 * @returns {boolean} Whether the event was canceled
 */
export function simulateClick(element, options = {}) {
  const event = createMockMouseEvent('click', options);
  return simulateEvent(element, event);
}

/**
 * Simulate a keyboard event on an element
 * 
 * @param {HTMLElement} element The target element
 * @param {string} key The key to simulate
 * @param {string} type The event type (keydown, keyup, keypress)
 * @param {Object} options Keyboard event options
 * @returns {boolean} Whether the event was canceled
 */
export function simulateKeyEvent(element, key, type = 'keydown', options = {}) {
  const event = createMockKeyboardEvent(type, { key, ...options });
  return simulateEvent(element, event);
}

/**
 * Simulate an input event on an element
 * 
 * @param {HTMLElement} element The target element
 * @param {string} value The new value
 * @returns {boolean} Whether the event was canceled
 */
export function simulateInput(element, value) {
  // Set the value
  element.value = value;
  
  // Create and dispatch input event
  const inputEvent = createMockInputEvent('input', { data: value });
  const inputResult = simulateEvent(element, inputEvent);
  
  // Create and dispatch change event
  const changeEvent = createMockEvent('change');
  const changeResult = simulateEvent(element, changeEvent);
  
  return inputResult && changeResult;
}

/**
 * Simulate form submission
 * 
 * @param {HTMLFormElement} form The form element
 * @param {Object} values Form field values
 * @returns {boolean} Whether the event was canceled
 */
export function simulateFormSubmit(form, values = {}) {
  // Set form field values
  Object.entries(values).forEach(([name, value]) => {
    const field = form.elements[name];
    if (field) {
      field.value = value;
    }
  });
  
  // Create and dispatch submit event
  const event = createMockEvent('submit');
  return simulateEvent(form, event);
}

/**
 * Mock event listener
 * 
 * @param {string} eventType Event type to listen for
 * @returns {Object} Mock event listener object
 */
export function createMockEventListener(eventType) {
  const listeners = [];
  let eventCount = 0;
  let lastEvent = null;
  
  return {
    /**
     * Add event listener
     * 
     * @param {Function} callback Event callback function
     */
    addEventListener(callback) {
      listeners.push(callback);
    },
    
    /**
     * Remove event listener
     * 
     * @param {Function} callback Event callback function to remove
     */
    removeEventListener(callback) {
      const index = listeners.indexOf(callback);
      if (index !== -1) {
        listeners.splice(index, 1);
      }
    },
    
    /**
     * Trigger the event
     * 
     * @param {Event|Object} event Event object or data
     */
    trigger(event) {
      // Create event if not provided
      if (!(event instanceof Event)) {
        event = createMockCustomEvent(eventType, event);
      }
      
      // Store event data
      lastEvent = event;
      eventCount++;
      
      // Call listeners
      listeners.forEach(callback => {
        callback(event);
      });
    },
    
    /**
     * Get event count
     * 
     * @returns {number} Number of times the event was triggered
     */
    getEventCount() {
      return eventCount;
    },
    
    /**
     * Get last event
     * 
     * @returns {Event} Last triggered event
     */
    getLastEvent() {
      return lastEvent;
    },
    
    /**
     * Reset event listener
     */
    reset() {
      eventCount = 0;
      lastEvent = null;
    }
  };
}

/**
 * Mock mutation observer
 * 
 * @returns {Object} Mock mutation observer object
 */
export function createMockMutationObserver() {
  let callback = null;
  let observing = false;
  let target = null;
  let options = null;
  let mutations = [];
  
  return {
    /**
     * Create a new mutation observer
     * 
     * @param {Function} cb Callback function
     */
    MutationObserver: function(cb) {
      callback = cb;
      return this;
    },
    
    /**
     * Start observing mutations
     * 
     * @param {Node} targetNode Target node to observe
     * @param {Object} observerOptions Observer options
     */
    observe(targetNode, observerOptions) {
      observing = true;
      target = targetNode;
      options = observerOptions;
    },
    
    /**
     * Stop observing mutations
     */
    disconnect() {
      observing = false;
      target = null;
      options = null;
    },
    
    /**
     * Simulate a mutation
     * 
     * @param {Object} mutation Mutation object
     */
    simulateMutation(mutation) {
      if (!observing) {
        return;
      }
      
      mutations.push(mutation);
      
      if (callback) {
        callback(mutations, this);
      }
    },
    
    /**
     * Check if observing
     * 
     * @returns {boolean} Whether the observer is active
     */
    isObserving() {
      return observing;
    },
    
    /**
     * Get target node
     * 
     * @returns {Node} Target node
     */
    getTarget() {
      return target;
    },
    
    /**
     * Get observer options
     * 
     * @returns {Object} Observer options
     */
    getOptions() {
      return options;
    },
    
    /**
     * Get mutations
     * 
     * @returns {Array} Mutations array
     */
    getMutations() {
      return mutations;
    },
    
    /**
     * Reset observer
     */
    reset() {
      mutations = [];
    }
  };
}