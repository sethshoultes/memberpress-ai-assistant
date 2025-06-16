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

// Debug messages removed - were appearing in admin interface

// Import jQuery as a module if it's not available in global scope
// This creates a module-scoped $ variable
let $;
if (typeof jQuery !== 'undefined') {
  $ = jQuery;
  // Debug messages removed - were appearing in admin interface
} else {
  // Debug messages removed - were appearing in admin interface
}

// Get the plugin URL dynamically
const scriptElement = document.querySelector('script[src*="memberpress-ai-assistant"]');
const pluginUrl = scriptElement ? scriptElement.src.split('/assets/')[0] : '/wp-content/plugins/memberpress-ai-assistant';

// Debug message removed - was appearing in admin interface

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

// Debug message removed - was appearing in admin interface

/**
 * Initialize the chat system when the DOM is ready
 */
document.addEventListener('DOMContentLoaded', async () => {
  // Debug messages removed - were appearing in admin interface

  // Enhanced chat container detection
  const chatContainer = document.getElementById('mpai-chat-container');
  
  // Additional container searches
  const containerByClass = document.querySelector('.mpai-chat-container');
  const allContainers = document.querySelectorAll('[id*="mpai"], [class*="mpai"]');
  
  // Debug messages removed - were appearing in admin interface
  
  // Check if we're on an admin page
  const isAdminPage = window.location.pathname.includes('/wp-admin/');
  const currentPage = new URLSearchParams(window.location.search).get('page');
  // Admin page detection - only log in debug mode
  if (window.mpai_chat_config?.debug) {
    console.log('[MPAI Debug] Admin page context:', { isAdminPage, currentPage });
  }
  
  if (!chatContainer) {
    console.warn('[MPAI Chat] Chat container not found, will try again later');
    
    // Try again after a delay in case the container is loaded dynamically
    setTimeout(() => {
      const delayedContainer = document.getElementById('mpai-chat-container');
      if (delayedContainer) {
        // Debug message removed - was appearing in admin interface
        initializeChat().catch(error => {
          console.error('[MPAI Chat] Delayed initialization error:', error);
        });
      } else {
        // Debug message removed - was appearing in admin interface
      }
    }, 2000);
    
    return;
  }

  // Debug message removed - was appearing in admin interface

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
  // Debug messages removed - were appearing in admin interface
  
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
  const chatOpenFromStorage = localStorage.getItem('mpai_chat_open') === 'true';
  const chatExpandedFromStorage = localStorage.getItem('mpai_chat_expanded') === 'true';
  
  // Debug messages removed - were appearing in admin interface
  
  const stateManager = new StateManager({
    // Initial state can be loaded from storage
    ui: {
      isChatOpen: chatOpenFromStorage,
      isExpanded: chatExpandedFromStorage
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
  // Pass the existing instances to avoid creating duplicates
  chatCore._stateManager = stateManager;
  chatCore._uiManager = uiManager;
  chatCore._apiClient = apiClient;
  chatCore._eventBus = eventBus;
  
  await chatCore.initialize();
  logger.debug('Chat core initialized');
  
  // Start the chat system
  await chatCore.start();
  logger.info('Chat system started');
  
  // Make chat interface available globally
  window.mpaiChat = chatCore;
  // Global exposure - only log in debug mode
  if (config.debug) {
    console.log('[MPAI Debug] Global chat interface exposed:', {
      mpaiChat: window.mpaiChat !== undefined,
      MPAIChat: window.MPAIChat !== undefined
    });
  }
  
  // Expose all module classes to the global scope
  window.EventBus = EventBus;
  window.StateManager = StateManager;
  window.UIManager = UIManager;
  window.APIClient = APIClient;
  window.Logger = Logger;
  window.LogLevel = LogLevel;
  window.StorageManager = StorageManager;
  
  // Debug messages removed - were appearing in admin interface
  
  // Also store module instances in the global scope
  window.eventBus = eventBus;
  window.stateManager = stateManager;
  window.uiManager = uiManager;
  window.apiClient = apiClient;
  
  // Debug messages removed - were appearing in admin interface
  
  logger.info('Chat system initialization complete');
  
  // Chat button click handling is now managed by UIManager
  // Just log that we found the button for debugging
  const chatButton = document.querySelector('.mpai-chat-toggle, #mpai-chat-toggle');
  if (chatButton) {
    // Debug message removed - was appearing in admin interface
  } else {
    // Debug message removed - was appearing in admin interface
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