/**
 * Mock API responses for MemberPress AI Assistant tests
 */

/**
 * Mock chat API response
 * 
 * @param {Object} options Configuration options
 * @returns {Object} Mock response object
 */
export function mockChatResponse(options = {}) {
  const {
    message = 'This is a test response from the assistant.',
    conversation_id = 'mock_conversation_' + Math.random().toString(36).substring(2, 9),
    status = 'success',
    error = null
  } = options;
  
  return {
    status,
    message,
    conversation_id,
    error,
    timestamp: new Date().toISOString()
  };
}

/**
 * Mock chat API response with XML content
 * 
 * @param {Object} options Configuration options
 * @returns {Object} Mock response object
 */
export function mockXMLResponse(options = {}) {
  const {
    content = 'This is a test response.',
    type = 'info',
    conversation_id = 'mock_conversation_' + Math.random().toString(36).substring(2, 9)
  } = options;
  
  let xmlContent;
  
  switch (type) {
    case 'button':
      xmlContent = `<p>${content}</p><button type="primary">Click Me</button>`;
      break;
    case 'code':
      xmlContent = `<p>${content}</p><code language="javascript">function test() { return true; }</code>`;
      break;
    case 'table':
      xmlContent = `
        <p>${content}</p>
        <table>
          <row>
            <cell header="true">Header 1</cell>
            <cell header="true">Header 2</cell>
          </row>
          <row>
            <cell>Data 1</cell>
            <cell>Data 2</cell>
          </row>
        </table>
      `;
      break;
    case 'form':
      xmlContent = `
        <p>${content}</p>
        <form>
          <input name="test_field" label="Test Field" type="text" />
        </form>
      `;
      break;
    case 'error':
      xmlContent = `<error>${content}</error>`;
      break;
    case 'warning':
      xmlContent = `<warning>${content}</warning>`;
      break;
    case 'success':
      xmlContent = `<success>${content}</success>`;
      break;
    case 'info':
    default:
      xmlContent = `<info>${content}</info>`;
      break;
  }
  
  return {
    status: 'success',
    message: xmlContent,
    conversation_id,
    timestamp: new Date().toISOString()
  };
}

/**
 * Mock error response
 * 
 * @param {Object} options Configuration options
 * @returns {Object} Mock error response object
 */
export function mockErrorResponse(options = {}) {
  const {
    message = 'An error occurred while processing your request.',
    code = 'internal_error',
    status = 'error'
  } = options;
  
  return {
    status,
    error: {
      code,
      message
    },
    timestamp: new Date().toISOString()
  };
}

/**
 * Mock API response for a specific tool
 * 
 * @param {string} toolName The name of the tool
 * @param {Object} data The tool-specific data
 * @returns {Object} Mock tool response object
 */
export function mockToolResponse(toolName, data = {}) {
  return {
    status: 'success',
    tool: toolName,
    data,
    timestamp: new Date().toISOString()
  };
}

/**
 * Setup mock fetch responses
 * 
 * @param {Object} responses Map of URL patterns to response functions
 * @returns {Function} Function to restore original fetch
 */
export function setupMockFetch(responses = {}) {
  const originalFetch = global.fetch;
  
  global.fetch = jest.fn((url, options) => {
    // Find matching response handler
    const matchingPattern = Object.keys(responses).find(pattern => {
      return new RegExp(pattern).test(url);
    });
    
    if (matchingPattern) {
      const responseHandler = responses[matchingPattern];
      const response = typeof responseHandler === 'function' 
        ? responseHandler(url, options) 
        : responseHandler;
      
      // Parse request body if present
      let requestData = null;
      if (options && options.body) {
        try {
          requestData = JSON.parse(options.body);
        } catch (e) {
          // Ignore parsing errors
        }
      }
      
      // Create response object
      return Promise.resolve({
        ok: !response.error,
        status: response.error ? 400 : 200,
        json: () => Promise.resolve(response),
        text: () => Promise.resolve(JSON.stringify(response)),
        headers: new Headers({
          'Content-Type': 'application/json'
        }),
        requestData
      });
    }
    
    // Default response for unmatched URLs
    return Promise.resolve({
      ok: false,
      status: 404,
      json: () => Promise.resolve({ 
        status: 'error', 
        error: { 
          code: 'not_found',
          message: 'Endpoint not found'
        }
      })
    });
  });
  
  // Return function to restore original fetch
  return () => {
    global.fetch = originalFetch;
  };
}