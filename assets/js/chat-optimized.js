/**
 * MemberPress AI Assistant Chat Interface - Optimized Entry Point
 * 
 * This file has been optimized to use ES6 module bundles for better
 * loading performance and reduced HTTP requests.
 * 
 * @module chat
 * @author MemberPress
 * @version 1.0.0
 * @optimized 2025-06-19 18:31:10
 */

// Import jQuery as a module if it's not available in global scope
let $;
if (typeof jQuery !== 'undefined') {
  $ = jQuery;
} else {
  console.warn('[MPAI] jQuery not found, chat functionality may be limited');
}

// Import core modules from bundles
import {
  StateManager,
  EventBus,
  ApiClient,
  Logger,
  LogLevel,
  StorageManager
} from './bundles/core-bundle.js';

import {
  MessageFactory,
  MessageRenderer
} from './bundles/messaging-bundle.js';

// Chat core is imported individually as it coordinates everything
import ChatCore from './chat/core/chat-core.js';

// UI components are lazy loaded
let UIManager, InputHandler, UIControls;

/**
 * Initialize the chat system when the DOM is ready
 */
document.addEventListener('DOMContentLoaded', async () => {
  const chatContainer = document.getElementById('mpai-chat-container');
  
  if (!chatContainer) {
    console.warn('[MPAI Chat] Chat container not found, will try again later');
    setTimeout(() => {
      const delayedContainer = document.getElementById('mpai-chat-container');
      if (delayedContainer) {
        initializeChat();
      }
    }, 2000);
    return;
  }

  try {
    await initializeChat();
  } catch (error) {
    console.error('[MPAI Chat] Initialization error:', error);
  }
});

/**
 * Initialize the chat system with optimized module loading
 */
async function initializeChat() {
  const config = window.mpai_chat_config || {};
  
  // Create logger first
  const logger = new Logger({
    minLevel: config.debug ? LogLevel.DEBUG : LogLevel.INFO,
    enableTimestamps: true
  });
  
  logger.info('Initializing optimized chat system');
  
  // Initialize core services
  const eventBus = new EventBus();
  const storageManager = new StorageManager({
    storagePrefix: 'mpai_',
    defaultExpiration: 30 * 24 * 60 * 60 * 1000
  });
  
  const stateManager = new StateManager({
    ui: {
      isChatOpen: localStorage.getItem('mpai_chat_open') === 'true',
      isExpanded: localStorage.getItem('mpai_chat_expanded') === 'true'
    }
  }, eventBus);
  
  const apiClient = new ApiClient({
    baseUrl: config.apiEndpoint || '/wp-json/memberpress-ai/v1/chat',
    timeout: config.timeout || 30000,
    retries: config.retries || 2
  }, eventBus);
  
  // Lazy load UI components
  logger.debug('Loading UI components...');
  const uiBundle = await import('./bundles/ui-bundle.js');
  UIManager = uiBundle.UiManager;
  
  const uiManager = new UIManager({
    typingDelay: config.typingDelay || 0,
    theme: config.theme || 'light'
  }, stateManager, eventBus);
  
  // Initialize chat core
  const chatCore = new ChatCore({
    maxMessages: config.maxMessages || 50,
    autoOpen: config.autoOpen || false,
    debug: config.debug || false
  });
  
  // Initialize all components
  await storageManager.initialize();
  await stateManager.initialize();
  await apiClient.initialize();
  await uiManager.initialize('#mpai-chat-container');
  
  // Connect dependencies
  chatCore._stateManager = stateManager;
  chatCore._uiManager = uiManager;
  chatCore._apiClient = apiClient;
  chatCore._eventBus = eventBus;
  
  await chatCore.initialize();
  await chatCore.start();
  
  // Global exposure
  window.mpaiChat = chatCore;
  
  logger.info('Optimized chat system ready');
  return chatCore;
}

// Export main chat functionality
export { ChatCore };
export default initializeChat;
