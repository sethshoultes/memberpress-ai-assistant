/**
 * StorageManager.js
 * 
 * This utility module handles all local storage operations for the chat application.
 * It provides a centralized interface for saving, retrieving, and managing conversation
 * persistence in the browser's localStorage. This abstraction helps maintain consistent
 * storage patterns across the application and simplifies data persistence operations.
 * 
 * Part of the chat.js modularization architecture.
 */

/**
 * Class responsible for managing local storage operations.
 * Provides methods for saving, retrieving, and deleting data from localStorage.
 */
class StorageManager {
  /**
   * Initialize the StorageManager.
   * @param {Object} options - Configuration options for the storage manager.
   * @param {string} options.storagePrefix - Prefix to use for all storage keys.
   * @param {number} options.defaultExpiration - Default expiration time in milliseconds.
   */
  constructor(options = {}) {
    // Set default storage prefix or use provided one
    this.storagePrefix = options.storagePrefix || 'mpai_chat_';
    
    // Set default expiration time (7 days in milliseconds) or use provided one
    this.defaultExpiration = options.defaultExpiration || 7 * 24 * 60 * 60 * 1000;
    
    // Store any additional options
    this.options = options;
    
    // Key for storing conversation IDs
    this.conversationListKey = 'conversation_ids';
  }

  /**
   * Initializes the storage manager.
   * This method is required for compatibility with the chat.js initialization flow.
   * 
   * @public
   * @async
   * @returns {Promise<boolean>} A promise that resolves with success status
   */
  async initialize() {
    try {
      // Check if localStorage is available
      if (typeof localStorage === 'undefined') {
        console.error('StorageManager: localStorage is not available');
        return false;
      }

      // Test localStorage by setting and getting a value
      const testKey = `${this.storagePrefix}test`;
      try {
        localStorage.setItem(testKey, 'test');
        localStorage.removeItem(testKey);
      } catch (error) {
        console.error('StorageManager: localStorage test failed', error);
        return false;
      }

      // Clean up expired items on initialization
      this.clear(true);
      
      return true;
    } catch (error) {
      console.error('StorageManager: Initialization failed', error);
      return false;
    }
  }

  /**
   * Save data to localStorage with the given key.
   * @param {string} key - The key to store the data under.
   * @param {*} data - The data to store (will be JSON stringified).
   * @param {Object} options - Additional options for this storage operation.
   * @param {number} options.expiration - Custom expiration time in milliseconds.
   * @returns {boolean} - True if the operation was successful.
   */
  save(key, data, options = {}) {
    try {
      // Prefix the key
      const prefixedKey = this.storagePrefix + key;
      
      // Calculate expiration timestamp
      const expiration = Date.now() + (options.expiration || this.defaultExpiration);
      
      // Create storage object with data and metadata
      const storageObject = {
        data: data,
        meta: {
          timestamp: Date.now(),
          expiration: expiration
        }
      };
      
      // Stringify the data
      const serializedData = JSON.stringify(storageObject);
      
      // Save to localStorage
      localStorage.setItem(prefixedKey, serializedData);
      
      return true;
    } catch (error) {
      console.error('StorageManager: Error saving data', error);
      return false;
    }
  }

  /**
   * Retrieve data from localStorage by key.
   * @param {string} key - The key to retrieve data for.
   * @param {*} defaultValue - Value to return if the key doesn't exist.
   * @returns {*} The retrieved data or defaultValue if not found.
   */
  get(key, defaultValue = null) {
    try {
      // Prefix the key
      const prefixedKey = this.storagePrefix + key;
      
      // Get data from localStorage
      const serializedData = localStorage.getItem(prefixedKey);
      
      // If no data found, return default value
      if (!serializedData) {
        return defaultValue;
      }
      
      // Parse the data
      const storageObject = JSON.parse(serializedData);
      
      // Check if data has expired
      if (storageObject.meta && storageObject.meta.expiration < Date.now()) {
        // Data has expired, remove it and return default value
        localStorage.removeItem(prefixedKey);
        return defaultValue;
      }
      
      // Return the data
      return storageObject.data;
    } catch (error) {
      console.error('StorageManager: Error retrieving data', error);
      return defaultValue;
    }
  }

  /**
   * Delete data from localStorage by key.
   * @param {string} key - The key to delete.
   * @returns {boolean} - True if the operation was successful.
   */
  delete(key) {
    try {
      // Prefix the key
      const prefixedKey = this.storagePrefix + key;
      
      // Remove the item
      localStorage.removeItem(prefixedKey);
      
      return true;
    } catch (error) {
      console.error('StorageManager: Error deleting data', error);
      return false;
    }
  }

  /**
   * Check if a key exists in localStorage.
   * @param {string} key - The key to check.
   * @returns {boolean} - True if the key exists.
   */
  exists(key) {
    try {
      // Prefix the key
      const prefixedKey = this.storagePrefix + key;
      
      // Get data from localStorage
      const serializedData = localStorage.getItem(prefixedKey);
      
      // If no data found, return false
      if (!serializedData) {
        return false;
      }
      
      // Parse the data
      const storageObject = JSON.parse(serializedData);
      
      // Check if data has expired
      if (storageObject.meta && storageObject.meta.expiration < Date.now()) {
        // Data has expired, remove it and return false
        localStorage.removeItem(prefixedKey);
        return false;
      }
      
      // Data exists and has not expired
      return true;
    } catch (error) {
      console.error('StorageManager: Error checking if key exists', error);
      return false;
    }
  }

  /**
   * Clear all data stored by this application.
   * @param {boolean} onlyExpired - If true, only clear expired items.
   * @returns {boolean} - True if the operation was successful.
   */
  clear(onlyExpired = false) {
    try {
      if (onlyExpired) {
        // Only clear expired items
        const keysToRemove = [];
        
        // Iterate through all localStorage items
        for (let i = 0; i < localStorage.length; i++) {
          const key = localStorage.key(i);
          
          // Check if the key belongs to our application
          if (key.startsWith(this.storagePrefix)) {
            try {
              // Get and parse the data
              const serializedData = localStorage.getItem(key);
              const storageObject = JSON.parse(serializedData);
              
              // Check if data has expired
              if (storageObject.meta && storageObject.meta.expiration < Date.now()) {
                keysToRemove.push(key);
              }
            } catch (parseError) {
              // If we can't parse the data, consider it corrupted and remove it
              keysToRemove.push(key);
            }
          }
        }
        
        // Remove all expired items
        keysToRemove.forEach(key => localStorage.removeItem(key));
      } else {
        // Clear all items with our prefix
        const keysToRemove = [];
        
        // Iterate through all localStorage items
        for (let i = 0; i < localStorage.length; i++) {
          const key = localStorage.key(i);
          
          // Check if the key belongs to our application
          if (key.startsWith(this.storagePrefix)) {
            keysToRemove.push(key);
          }
        }
        
        // Remove all items
        keysToRemove.forEach(key => localStorage.removeItem(key));
      }
      
      return true;
    } catch (error) {
      console.error('StorageManager: Error clearing data', error);
      return false;
    }
  }

  /**
   * Save a conversation to localStorage.
   * @param {string} conversationId - Unique identifier for the conversation.
   * @param {Array} messages - Array of message objects to save.
   * @returns {boolean} - True if the operation was successful.
   */
  saveConversation(conversationId, messages) {
    try {
      // Create conversation key
      const conversationKey = `conversation_${conversationId}`;
      
      // Save the conversation
      const saveResult = this.save(conversationKey, messages);
      
      if (saveResult) {
        // Update the list of conversation IDs
        const conversationIds = this.getAllConversationIds();
        
        // Add the new ID if it doesn't exist
        if (!conversationIds.includes(conversationId)) {
          conversationIds.push(conversationId);
          this.save(this.conversationListKey, conversationIds);
        }
        
        return true;
      }
      
      return false;
    } catch (error) {
      console.error('StorageManager: Error saving conversation', error);
      return false;
    }
  }

  /**
   * Retrieve a conversation from localStorage.
   * @param {string} conversationId - Unique identifier for the conversation.
   * @returns {Array|null} - Array of message objects or null if not found.
   */
  getConversation(conversationId) {
    try {
      // Create conversation key
      const conversationKey = `conversation_${conversationId}`;
      
      // Get the conversation
      return this.get(conversationKey, null);
    } catch (error) {
      console.error('StorageManager: Error retrieving conversation', error);
      return null;
    }
  }

  /**
   * Delete a conversation from localStorage.
   * @param {string} conversationId - Unique identifier for the conversation to delete.
   * @returns {boolean} - True if the operation was successful.
   */
  deleteConversation(conversationId) {
    try {
      // Create conversation key
      const conversationKey = `conversation_${conversationId}`;
      
      // Delete the conversation
      const deleteResult = this.delete(conversationKey);
      
      if (deleteResult) {
        // Update the list of conversation IDs
        let conversationIds = this.getAllConversationIds();
        
        // Remove the ID from the list
        conversationIds = conversationIds.filter(id => id !== conversationId);
        
        // Save the updated list
        this.save(this.conversationListKey, conversationIds);
        
        return true;
      }
      
      return false;
    } catch (error) {
      console.error('StorageManager: Error deleting conversation', error);
      return false;
    }
  }

  /**
   * Get a list of all saved conversation IDs.
   * @returns {Array} - Array of conversation IDs.
   */
  getAllConversationIds() {
    try {
      // Get the list of conversation IDs
      const conversationIds = this.get(this.conversationListKey, []);
      
      // Filter out any IDs that no longer exist or have expired
      const validIds = conversationIds.filter(id => {
        return this.exists(`conversation_${id}`);
      });
      
      // If the filtered list is different from the original, update storage
      if (validIds.length !== conversationIds.length) {
        this.save(this.conversationListKey, validIds);
      }
      
      return validIds;
    } catch (error) {
      console.error('StorageManager: Error getting conversation IDs', error);
      return [];
    }
  }
}

// Export the StorageManager class
export default StorageManager;