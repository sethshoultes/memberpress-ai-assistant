/**
 * MemberPress AI Assistant Chat Interface - Entry Point
 * 
 * This file serves as the main entry point for the modularized chat system.
 * It imports the necessary modules, initializes the chat system when the DOM is ready,
 * and exposes any necessary global interfaces.
 * 
 * @module chat
 * @author MemberPress
 * @since 1.0.0
 */

// Add diagnostic logging
console.log('[MPAI Debug] Starting chat.js module loading');
console.log('[MPAI Debug] jQuery available in global scope:', typeof jQuery !== 'undefined');

// Import jQuery as a module if it's not available in global scope
// This creates a module-scoped $ variable
let $;
if (typeof jQuery !== 'undefined') {
  $ = jQuery;
  console.log('[MPAI Debug] Using global jQuery in module');
  console.log('[MPAI Debug] $ available in global scope after initialization:', typeof $ !== 'undefined');
} else {
  console.error('[MPAI Debug] jQuery not found in global scope');
}

// Get the plugin URL dynamically
const scriptElement = document.querySelector('script[src*="memberpress-ai-assistant"]');
const pluginUrl = scriptElement ? scriptElement.src.split('/assets/')[0] : '/wp-content/plugins/memberpress-ai-assistant';

console.log('[MPAI Debug] Detected plugin URL:', pluginUrl);

// Import core modules - using relative paths
// We can't use dynamic imports with template literals in the import statement
// So we'll use relative paths instead
import ChatCore from './chat/core/chat-core.js';
import StateManager from './chat/core/state-manager.js';
import UIManager from './chat/core/ui-manager.js';
import APIClient from './chat/core/api-client.js';
import EventBus from './chat/core/event-bus.js';

// Import utility modules
import { Logger, LogLevel } from './chat/utils/logger.js';
import StorageManager from './chat/utils/storage-manager.js';

console.log('[MPAI Debug] Modules imported successfully');

/**
 * Initialize the chat system when the DOM is ready
 */
document.addEventListener('DOMContentLoaded', async () => {
  console.log('[MPAI Debug] DOMContentLoaded event fired in chat.js');
  console.log('[MPAI Debug] jQuery available in DOMContentLoaded:', typeof jQuery !== 'undefined');
  console.log('[MPAI Debug] $ available in DOMContentLoaded:', typeof $ !== 'undefined');
  
  // Check if chat container exists
  const chatContainer = document.getElementById('mpai-chat-container');
  if (!chatContainer) {
    console.warn('[MPAI Chat] Chat container not found');
    return;
  }

  try {
    // Initialize the chat system
    await initializeChat();
  } catch (error) {
    console.error('[MPAI Chat] Initialization error:', error);
  }
});

/**
 * Initialize the chat system and all required modules
 * 
 * This function creates and initializes all the core modules required
 * for the chat system to function properly.
 * 
 * @async
 * @returns {Promise<void>} A promise that resolves when initialization is complete
 */
async function initializeChat() {
  console.log('[MPAI Debug] initializeChat function called');
  console.log('[MPAI Debug] jQuery available in initializeChat:', typeof jQuery !== 'undefined');
  console.log('[MPAI Debug] $ available in initializeChat:', typeof $ !== 'undefined');
  
  // Get configuration from global variable or use defaults
  const config = window.mpai_chat_config || {};
  
  // Create logger
  const logger = new Logger({
    minLevel: config.debug ? LogLevel.DEBUG : LogLevel.INFO,
    enableTimestamps: true
  });
  logger.info('Initializing chat system');
  
  // Create event bus (central communication hub)
  const eventBus = new EventBus();
  logger.debug('Event bus created');
  
  // Create storage manager
  const storageManager = new StorageManager({
    storagePrefix: 'mpai_',
    defaultExpiration: 30 * 24 * 60 * 60 * 1000 // 30 days
  });
  logger.debug('Storage manager created');
  
  // Create state manager
  const stateManager = new StateManager({
    // Initial state can be loaded from storage
    ui: {
      isChatOpen: localStorage.getItem('mpai_chat_open') === 'true',
      isExpanded: localStorage.getItem('mpai_chat_expanded') === 'true'
    }
  }, eventBus);
  logger.debug('State manager created');
  
  // Create API client
  const apiClient = new APIClient({
    baseUrl: config.apiEndpoint || '/wp-json/memberpress-ai/v1/chat',
    timeout: config.timeout || 30000,
    retries: config.retries || 2
  }, eventBus);
  logger.debug('API client created');
  
  // Create UI manager
  const uiManager = new UIManager({
    typingDelay: config.typingDelay || 0,
    theme: config.theme || 'light'
  }, stateManager, eventBus);
  logger.debug('UI manager created');
  
  // Create chat core (main controller)
  const chatCore = new ChatCore({
    maxMessages: config.maxMessages || 50,
    autoOpen: config.autoOpen || false,
    debug: config.debug || false
  });
  logger.debug('Chat core created');
  
  // Initialize all modules
  // The initialization order is important:
  // 1. First initialize low-level services (storage, state)
  // 2. Then initialize the API client
  // 3. Then initialize the UI manager
  // 4. Finally initialize the chat core which coordinates everything
  
  logger.info('Initializing modules');
  
  // Initialize storage manager
  await storageManager.initialize();
  logger.debug('Storage manager initialized');
  
  // Initialize state manager
  await stateManager.initialize();
  logger.debug('State manager initialized');
  
  // Initialize API client
  await apiClient.initialize();
  logger.debug('API client initialized');
  
  // Initialize UI manager with the chat container
  await uiManager.initialize('#mpai-chat-container');
  logger.debug('UI manager initialized');
  
  // Initialize chat core with all dependencies
  // This will connect all the modules together
  await chatCore.initialize();
  logger.debug('Chat core initialized');
  
  // Start the chat system
  await chatCore.start();
  logger.info('Chat system started');
  
  // Make chat interface available globally
  window.mpaiChat = chatCore;
  console.log('[MPAI Debug] Set window.mpaiChat to chatCore instance:', window.mpaiChat !== undefined);
  
  // Also expose the ChatCore class for potential external use
  window.MPAIChat = ChatCore;
  console.log('[MPAI Debug] Set window.MPAIChat to ChatCore class:', window.MPAIChat !== undefined);
  
  // Expose all module classes to the global scope
  window.EventBus = EventBus;
  window.StateManager = StateManager;
  window.UIManager = UIManager;
  window.APIClient = APIClient;
  window.Logger = Logger;
  window.LogLevel = LogLevel;
  window.StorageManager = StorageManager;
  
  console.log('[MPAI Debug] Exposed all module classes to global scope:');
  console.log('- EventBus available:', typeof window.EventBus !== 'undefined');
  console.log('- StateManager available:', typeof window.StateManager !== 'undefined');
  console.log('- UIManager available:', typeof window.UIManager !== 'undefined');
  console.log('- APIClient available:', typeof window.APIClient !== 'undefined');
  
  // Also store module instances in the global scope
  window.eventBus = eventBus;
  window.stateManager = stateManager;
  window.uiManager = uiManager;
  window.apiClient = apiClient;
  
  console.log('[MPAI Debug] Stored module instances in global scope:');
  console.log('- eventBus available:', typeof window.eventBus !== 'undefined');
  console.log('- stateManager available:', typeof window.stateManager !== 'undefined');
  console.log('- uiManager available:', typeof window.uiManager !== 'undefined');
  console.log('- apiClient available:', typeof window.apiClient !== 'undefined');
  
  logger.info('Chat system initialization complete');
  
  // Add click handler for chat button if it exists
  const chatButton = document.querySelector('.mpai-chat-button');
  if (chatButton) {
    console.log('[MPAI Debug] Found chat button, adding click handler');
    chatButton.addEventListener('click', function() {
      console.log('[MPAI Debug] Chat button clicked');
      if (window.mpaiChat && typeof window.mpaiChat.toggleChat === 'function') {
        console.log('[MPAI Debug] Calling toggleChat function');
        window.mpaiChat.toggleChat();
      } else {
        console.error('[MPAI Debug] window.mpaiChat or toggleChat function not available');
      }
    });
  } else {
    console.warn('[MPAI Debug] Chat button not found');
  }
  
  return chatCore;
}

/**
 * Create a new chat instance with custom configuration
 * 
 * This function can be used by external code to create a new chat instance
 * with custom configuration options.
 * 
 * @param {Object} config - Configuration options for the chat
 * @returns {Promise<ChatCore>} A promise that resolves to the chat instance
 */
export async function createChat(config = {}) {
  // Implementation stub - would create a new chat instance
  // This is just a placeholder for potential future use
}

// Export the ChatCore class for module usage
export { ChatCore };

// Default export is the createChat function
export default createChat;