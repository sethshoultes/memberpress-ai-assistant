/**
 * APIClient - Handles all communication with the backend
 * 
 * This module is responsible for all communication with the backend API,
 * including sending messages, retrieving conversation history, and handling
 * authentication. It abstracts away the details of API communication from
 * the rest of the application.
 * 
 * @module APIClient
 * @author MemberPress
 * @since 1.0.0
 */

/**
 * APIClient class - Manages API communication
 * 
 * @class
 */
class APIClient {
  /**
   * Creates a new APIClient instance
   * 
   * @constructor
   * @param {Object} config - Configuration options for the API client
   * @param {EventBus} eventBus - Event bus instance
   */
  constructor(config = {}, eventBus) {
    /**
     * Configuration options
     * @type {Object}
     * @private
     */
    this._config = {
      baseUrl: '/wp-admin/admin-ajax.php',
      action: 'mpai_chat_request',
      timeout: 30000, // 30 seconds
      retries: 2,
      ...config
    };
    
    /**
     * Event bus instance
     * @type {EventBus}
     * @private
     */
    this._eventBus = eventBus;
    
    /**
     * Authentication token
     * @type {string|null}
     * @private
     */
    this._authToken = null;
    
    /**
     * Request abort controllers
     * @type {Map<string, AbortController>}
     * @private
     */
    this._abortControllers = new Map();
  }

  /**
   * Initializes the API client
   * 
   * @public
   * @returns {Promise<void>} A promise that resolves when initialization is complete
   */
  async initialize() {
    console.log('[MPAI Debug] APIClient.initialize called');
    
    // Check if the API endpoint is available
    try {
      // Make a simple request to check if the API is available
      const response = await fetch(this._config.baseUrl, {
        method: 'HEAD',
        headers: {
          'X-WP-Nonce': window.mpai_nonce || ''
        }
      });
      
      console.log('[MPAI Debug] API endpoint check result:', response.status);
      
      // Publish an event that the API client is ready
      if (this._eventBus) {
        this._eventBus.publish('api.ready', {
          baseUrl: this._config.baseUrl
        });
        console.log('[MPAI Debug] Published api.ready event');
      }
      
      return true;
    } catch (error) {
      console.error('[MPAI Debug] Error initializing API client:', error);
      
      // Publish an error event
      if (this._eventBus) {
        this._eventBus.publish('api.error', {
          message: 'Failed to initialize API client',
          error
        });
        console.log('[MPAI Debug] Published api.error event');
      }
      
      return false;
    }
  }

  /**
   * Sets the authentication token
   * 
   * @public
   * @param {string} token - Authentication token
   * @returns {void}
   */
  setAuthToken(token) {
    // Set auth token
  }

  /**
   * Sends a chat message to the API
   * 
   * @public
   * @param {string} message - Message to send
   * @param {Object} [options] - Additional options
   * @returns {Promise<Object>} A promise that resolves with the API response
   */
  async sendMessage(message, options = {}) {
    try {
      console.log('[MPAI Debug] APIClient.sendMessage called with:', message);
      
      // Generate a unique request ID
      const requestId = this._generateRequestId();
      
      // Create an abort controller for this request
      const abortController = new AbortController();
      this._abortControllers.set(requestId, abortController);
      
      // Prepare the request data
      const data = {
        message: message,
        conversation_id: options.conversationId || null,
        user_logged_in: options.userLoggedIn || false
      };
      
      // Make the API request
      const response = await this._makeRequest('chat', data, {
        signal: abortController.signal,
        timeout: options.timeout || this._config.timeout
      });
      
      // Remove the abort controller
      this._abortControllers.delete(requestId);
      
      // Publish an event with the response
      if (this._eventBus) {
        this._eventBus.publish('api.message.received', {
          requestId,
          response
        });
        console.log('[MPAI Debug] Published api.message.received event');
      }
      
      return response;
    } catch (error) {
      // Handle the error
      const processedError = this._handleError(error, {
        message,
        options
      });
      
      // Publish an event with the error
      if (this._eventBus) {
        this._eventBus.publish('api.message.error', {
          error: processedError
        });
        console.log('[MPAI Debug] Published api.message.error event');
      }
      
      // Re-throw the error
      throw processedError;
    }
  }

  /**
   * Fetches conversation history from the API
   * 
   * @public
   * @param {Object} [options] - Additional options
   * @returns {Promise<Array>} A promise that resolves with the conversation history
   */
  async getConversationHistory(options = {}) {
    // Get conversation history
  }

  /**
   * Clears the conversation history on the server
   * 
   * @public
   * @returns {Promise<boolean>} A promise that resolves with success status
   */
  async clearConversation() {
    // Clear conversation history
  }

  /**
   * Cancels an ongoing request
   * 
   * @public
   * @param {string} requestId - ID of the request to cancel
   * @returns {boolean} Whether the request was successfully cancelled
   */
  cancelRequest(requestId) {
    // Cancel request
  }

  /**
   * Cancels all ongoing requests
   * 
   * @public
   * @returns {void}
   */
  cancelAllRequests() {
    // Cancel all requests
  }

  /**
   * Makes a request to the API
   * 
   * @private
   * @param {string} endpoint - API endpoint
   * @param {Object} data - Request data
   * @param {Object} [options] - Additional options
   * @returns {Promise<Object>} A promise that resolves with the API response
   */
  async _makeRequest(endpoint, data, options = {}) {
    try {
      console.log('[MPAI Debug] APIClient._makeRequest called with endpoint:', endpoint);
      
      // Prepare the fetch options
      const fetchOptions = {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': window.mpai_nonce || ''
        },
        body: JSON.stringify(data),
        signal: options.signal
      };
      
      // Add authorization header if we have a token
      if (this._authToken) {
        fetchOptions.headers['Authorization'] = `Bearer ${this._authToken}`;
      }
      
      // Create a timeout promise
      const timeoutPromise = new Promise((_, reject) => {
        setTimeout(() => {
          reject(new Error('Request timed out'));
        }, options.timeout || this._config.timeout);
      });
      
      // Make the request with timeout
      const response = await Promise.race([
        fetch(`${this._config.baseUrl}`, fetchOptions),
        timeoutPromise
      ]);
      
      // Check if the response is ok
      if (!response.ok) {
        const errorText = await response.text();
        throw new Error(`API error (${response.status}): ${errorText}`);
      }
      
      // Parse the response
      const responseData = await response.json();
      console.log('[MPAI Debug] API response received:', responseData);
      
      return responseData;
    } catch (error) {
      console.error('[MPAI Debug] Error in _makeRequest:', error);
      // Re-throw the error
      throw error;
    }
  }

  /**
   * Handles API errors
   *
   * @private
   * @param {Error} error - Error object
   * @param {Object} requestInfo - Information about the failed request
   * @returns {Error} The processed error
   */
  _handleError(error, requestInfo) {
    // Log the error
    console.error('[MPAI Debug] API error:', error, requestInfo);
    
    // If this is an abort error, create a more user-friendly error
    if (error.name === 'AbortError') {
      return new Error('Request was cancelled');
    }
    
    // Return the original error
    return error;
  }

  /**
   * Generates a unique request ID
   *
   * @private
   * @returns {string} A unique request ID
   */
  _generateRequestId() {
    return `req_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
  }
}

// Export the APIClient class
export default APIClient;