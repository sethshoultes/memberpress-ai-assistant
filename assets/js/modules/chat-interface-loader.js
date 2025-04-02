/**
 * MemberPress AI Assistant - Chat Interface Loader
 * 
 * This is the main entry point for the chat interface.
 * It initializes all modules and sets up the basic event handlers.
 */

(function($) {
    'use strict';
    
    // Store global references to avoid repetitive lookups
    var modules = {};
    
    // Initialize the chat interface once the document is ready
    $(document).ready(function() {
        // Check if the logger is available and log initialization
        if (window.mpaiLogger) {
            window.mpaiLogger.info('Chat interface initializing', 'ui');
            window.mpaiLogger.startTimer('chat_initialization');
        }
        
        // Get DOM elements
        const elements = {
            chatToggle: $('#mpai-chat-toggle'),
            chatContainer: $('#mpai-chat-container'),
            chatMessages: $('#mpai-chat-messages'),
            chatInput: $('#mpai-chat-input'),
            chatForm: $('#mpai-chat-form'),
            chatExpand: $('#mpai-chat-expand'),
            chatMinimize: $('#mpai-chat-minimize'),
            chatClose: $('#mpai-chat-close'),
            chatClear: $('#mpai-chat-clear'),
            chatSubmit: $('#mpai-chat-submit'),
            exportChat: $('#mpai-export-chat')
        };
        
        // Check if modules are available
        const modulesAvailable = checkModulesAvailable();
        
        if (!modulesAvailable) {
            console.error('MPAI: Required modules not available. Chat functionality may be limited.');
            // Continue anyway, as we'll handle missing modules gracefully
        }
        
        // Initialize modules in correct order
        initializeModules(elements);
        
        // Setup core event listeners that aren't handled by individual modules
        setupEventListeners(elements);
        
        // Log completion of initialization
        if (window.mpaiLogger) {
            window.mpaiLogger.endTimer('chat_initialization');
            window.mpaiLogger.info('Chat interface initialized', 'ui');
        }
    });
    
    /**
     * Check if all required modules are available
     * 
     * @return {boolean} Whether all modules are available
     */
    function checkModulesAvailable() {
        const requiredModules = [
            'MPAI_Messages',
            'MPAI_UIUtils',
            'MPAI_Tools',
            'MPAI_Formatters',
            'MPAI_History'
        ];
        
        let allAvailable = true;
        
        requiredModules.forEach(function(moduleName) {
            if (!window[moduleName]) {
                console.error('MPAI: Required module not available: ' + moduleName);
                allAvailable = false;
            } else {
                // Store reference
                modules[moduleName] = window[moduleName];
            }
        });
        
        return allAvailable;
    }
    
    /**
     * Initialize all modules
     * 
     * @param {Object} elements - DOM elements
     */
    function initializeModules(elements) {
        // First initialize UI utilities
        if (modules.MPAI_UIUtils) {
            modules.MPAI_UIUtils.init(elements);
        }
        
        // Initialize formatters (no dependencies)
        if (modules.MPAI_Formatters) {
            modules.MPAI_Formatters.init();
        }
        
        // Initialize messages (depends on UI utils)
        if (modules.MPAI_Messages) {
            modules.MPAI_Messages.init(elements);
        }
        
        // Initialize tools (depends on messages and formatters)
        if (modules.MPAI_Tools) {
            modules.MPAI_Tools.init(elements, modules.MPAI_Messages, modules.MPAI_Formatters);
        }
        
        // Initialize history (depends on messages)
        if (modules.MPAI_History) {
            modules.MPAI_History.init(elements, modules.MPAI_Messages);
        }
    }
    
    /**
     * Setup core event listeners
     * 
     * @param {Object} elements - DOM elements
     */
    function setupEventListeners(elements) {
        // Form submission
        elements.chatForm.on('submit', function(e) {
            e.preventDefault();
            
            const message = elements.chatInput.val();
            
            if (modules.MPAI_Messages) {
                modules.MPAI_Messages.sendMessage(message);
            }
        });
        
        // Clear history button
        elements.chatClear.on('click', function() {
            if (confirm('Are you sure you want to clear your chat history?')) {
                if (modules.MPAI_History) {
                    modules.MPAI_History.clearChatHistory();
                }
            }
        });
        
        // Export chat button
        elements.exportChat.on('click', function() {
            if (modules.MPAI_History) {
                modules.MPAI_History.exportChatHistory();
            }
        });
        
        // Welcome message - display on load if no history
        setTimeout(function() {
            if (elements.chatMessages.children().length === 0) {
                if (modules.MPAI_Messages) {
                    // Display welcome message
                    modules.MPAI_Messages.addMessage('assistant', mpai_chat_data.strings.welcome_message);
                }
            }
        }, 500); // Small delay to ensure history has been loaded
    }
})(jQuery);