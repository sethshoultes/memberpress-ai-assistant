/**
 * MemberPress AI Assistant - Chat Module Loader
 * 
 * This file is responsible for loading the chat.js file as an ES6 module.
 * It creates a script element with type="module" and sets its src to chat.js.
 */

(function() {
    'use strict';
    
    console.log('[MPAI Debug] Chat module loader initializing');
    console.log('[MPAI Debug] jQuery available in loader:', typeof jQuery !== 'undefined');
    console.log('[MPAI Debug] $ available in loader:', typeof $ !== 'undefined');
    
    // Make jQuery available as $ in the global scope if it's not already
    if (typeof jQuery !== 'undefined' && typeof $ === 'undefined') {
        window.$ = jQuery;
        console.log('[MPAI Debug] Set $ to jQuery in global scope');
    }
    
    // Function to load the chat module
    function loadChatModule() {
        console.log('[MPAI Debug] Loading chat module');
        
        // Try to find the chat button with different selectors
        console.log('[MPAI Debug] Looking for chat button with different selectors');
        
        // Look for any button or element that might be the chat button
        const possibleButtons = [
            '.mpai-chat-button',
            '.mpai-chat-icon',
            '.mpai-chat-toggle',
            '.chat-button',
            '.chat-icon',
            '.chat-toggle'
        ];
        
        let chatButton = null;
        for (const selector of possibleButtons) {
            const button = document.querySelector(selector);
            if (button) {
                console.log(`[MPAI Debug] Found potential chat button with selector: ${selector}`);
                chatButton = button;
                break;
            }
        }
        
        // If no button found with specific selectors, look for any button in the bottom right corner
        if (!chatButton) {
            console.log('[MPAI Debug] Looking for any button in the bottom right corner');
            
            // Get all buttons and divs on the page
            const allButtons = document.querySelectorAll('button, div');
            
            // Log all elements with 'chat' in their class name
            console.log('[MPAI Debug] Elements with "chat" in class name:');
            document.querySelectorAll('*[class*="chat"]').forEach(el => {
                console.log(`- ${el.tagName} with classes: ${el.className}`);
            });
            
            // Log all elements fixed in the bottom right corner
            console.log('[MPAI Debug] Elements fixed in bottom right corner:');
            Array.from(allButtons).forEach(button => {
                const style = window.getComputedStyle(button);
                if (style.position === 'fixed' &&
                    (style.bottom === '0px' || parseInt(style.bottom) < 100) &&
                    (style.right === '0px' || parseInt(style.right) < 100)) {
                    console.log(`- ${button.tagName} with classes: ${button.className}`);
                    if (!chatButton) chatButton = button;
                }
            });
        }
        
        // If we found a button, add a click handler
        if (chatButton) {
            console.log('[MPAI Debug] Chat button found:', chatButton);
            
            // Add a direct click handler to the chat button for testing
            chatButton.addEventListener('click', function() {
                console.log('[MPAI Debug] Chat button clicked directly from module loader');
                alert('Chat button clicked!');
            });
        } else {
            console.error('[MPAI Debug] Chat button not found on the page with any selector');
        }
        
        // Create a script element with type="module"
        const script = document.createElement('script');
        script.type = 'module';
        
        // Get the base URL for the plugin
        const baseUrl = getPluginBaseUrl();
        
        // Set the src attribute to the chat.js file
        script.src = baseUrl + '/assets/js/chat.js';
        
        // Add an onload handler to log when the module is loaded
        script.onload = function() {
            console.log('[MPAI Debug] Chat module loaded successfully');
            
            // Check if the global objects are available
            console.log('[MPAI Debug] window.mpaiChat available:', typeof window.mpaiChat !== 'undefined');
            console.log('[MPAI Debug] window.MPAIChat available:', typeof window.MPAIChat !== 'undefined');
            
            // Try to initialize the chat system manually
            // Wait for modules to be available in the global scope
            const checkModulesInterval = setInterval(function() {
                console.log('[MPAI Debug] Checking if modules are available in global scope');
                
                if (window.EventBus && window.StateManager && window.UIManager && window.APIClient) {
                    clearInterval(checkModulesInterval);
                    console.log('[MPAI Debug] All required modules are available in global scope');
                    
                    // Initialize the chat system
                    try {
                        console.log('[MPAI Debug] Attempting to initialize chat system manually');
                        
                        // Create instances of required modules
                        const eventBus = new window.EventBus();
                        window.eventBus = eventBus;
                        console.log('[MPAI Debug] Created EventBus instance');
                        
                        const stateManager = new window.StateManager({}, eventBus);
                        window.stateManager = stateManager;
                        console.log('[MPAI Debug] Created StateManager instance');
                        
                        const uiManager = new window.UIManager({}, stateManager, eventBus);
                        window.uiManager = uiManager;
                        console.log('[MPAI Debug] Created UIManager instance');
                        
                        const apiClient = new window.APIClient({}, eventBus);
                        window.apiClient = apiClient;
                        console.log('[MPAI Debug] Created APIClient instance');
                        
                        // Create ChatCore
                        const chatCore = new window.MPAIChat({});
                        window.mpaiChat = chatCore;
                        console.log('[MPAI Debug] Created ChatCore instance');
                        
                        // Initialize the chat system
                        chatCore.initialize().then(() => {
                            console.log('[MPAI Debug] ChatCore initialized');
                            return chatCore.start();
                        }).then(() => {
                            console.log('[MPAI Debug] ChatCore started');
                            
                            // Add a direct click handler to the chat button
                            const chatButton = document.querySelector('.mpai-chat-toggle');
                            if (chatButton) {
                                console.log('[MPAI Debug] Adding click handler to chat button after initialization');
                                chatButton.addEventListener('click', function() {
                                    console.log('[MPAI Debug] Chat button clicked after initialization');
                                    if (window.mpaiChat && typeof window.mpaiChat.toggleChat === 'function') {
                                        window.mpaiChat.toggleChat();
                                    }
                                });
                            }
                        }).catch(error => {
                            console.error('[MPAI Debug] Error initializing chat system:', error);
                        });
                    } catch (error) {
                        console.error('[MPAI Debug] Error creating module instances:', error);
                    }
                } else {
                    console.log('[MPAI Debug] Modules not yet available:');
                    console.log('- EventBus available:', !!window.EventBus);
                    console.log('- StateManager available:', !!window.StateManager);
                    console.log('- UIManager available:', !!window.UIManager);
                    console.log('- APIClient available:', !!window.APIClient);
                }
            }, 500);
            
            // Set a timeout to clear the interval if modules never become available
            setTimeout(function() {
                clearInterval(checkModulesInterval);
                console.error('[MPAI Debug] Timed out waiting for modules to become available');
            }, 10000);
        };
        
        // Add an onerror handler to log if there's an error loading the module
        script.onerror = function(error) {
            console.error('[MPAI Debug] Error loading chat module:', error);
        };
        
        // Append the script element to the document head
        document.head.appendChild(script);
        console.log('[MPAI Debug] Chat module script element added to document head');
    }
    
    // Function to get the base URL for the plugin
    function getPluginBaseUrl() {
        // Try to find a script element that includes 'memberpress-ai-assistant' in its src
        const scriptElement = document.querySelector('script[src*="memberpress-ai-assistant"]');
        
        if (scriptElement) {
            // Extract the base URL from the script src
            const src = scriptElement.src;
            const baseUrl = src.substring(0, src.indexOf('/assets/'));
            console.log('[MPAI Debug] Detected plugin base URL:', baseUrl);
            return baseUrl;
        }
        
        // Fallback to a default URL if we can't detect it
        console.warn('[MPAI Debug] Could not detect plugin base URL, using default');
        return '/wp-content/plugins/memberpress-ai-assistant';
    }
    
    // Load the chat module when the DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadChatModule);
        console.log('[MPAI Debug] Added DOMContentLoaded event listener to load chat module');
    } else {
        // DOM is already ready, load the module immediately
        loadChatModule();
        console.log('[MPAI Debug] DOM already ready, loading chat module immediately');
    }
})();