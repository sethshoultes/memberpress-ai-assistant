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
        
        // Log each element to verify it exists
        console.log('Chat interface elements:', {
            chatToggle: elements.chatToggle.length > 0,
            chatContainer: elements.chatContainer.length > 0,
            chatMessages: elements.chatMessages.length > 0,
            chatInput: elements.chatInput.length > 0,
            chatForm: elements.chatForm.length > 0,
            chatExpand: elements.chatExpand.length > 0,
            chatMinimize: elements.chatMinimize.length > 0,
            chatClose: elements.chatClose.length > 0,
            chatClear: elements.chatClear.length > 0,
            chatSubmit: elements.chatSubmit.length > 0,
            exportChat: elements.exportChat.length > 0
        });
        
        // Debug logging for element availability
        if (window.mpaiLogger) {
            window.mpaiLogger.debug('Chat toggle element found: ' + (elements.chatToggle.length > 0), 'ui');
            window.mpaiLogger.debug('Chat container element found: ' + (elements.chatContainer.length > 0), 'ui');
            window.mpaiLogger.debug('Chat messages element found: ' + (elements.chatMessages.length > 0), 'ui');
            window.mpaiLogger.debug('Chat input element found: ' + (elements.chatInput.length > 0), 'ui');
            window.mpaiLogger.debug('Chat form element found: ' + (elements.chatForm.length > 0), 'ui');
            
            // Log the logger settings
            window.mpaiLogger.info('Logger settings:', 'ui', {
                enabled: window.mpaiLogger.enabled,
                logLevel: window.mpaiLogger.logLevel,
                categories: window.mpaiLogger.categories
            });
        }
        
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
        
        // Always load chat history on page load
        if (modules.MPAI_History) {
            modules.MPAI_History.loadChatHistory();
        }
        
        // Check if the chat should be open based on localStorage
        if (localStorage.getItem('mpai_chat_open') === 'true') {
            elements.chatContainer.css('display', 'flex').show();
            elements.chatToggle.hide();
            
            // If expanded previously, expand again
            if (localStorage.getItem('mpai_chat_expanded') === 'true' && modules.MPAI_UIUtils) {
                modules.MPAI_UIUtils.toggleChatExpansion();
            }
        }
        
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
            'MPAI_History',
            'MPAI_BlogFormatter'
        ];
        
        // Optional modules that will be loaded by the module loader
        const optionalModules = [
            'MPAI_MessageProcessor',
            'MPAI_ToolCallDetector',
            'MPAI_ParameterValidator'
        ];
        
        let allAvailable = true;
        
        // Check required modules
        requiredModules.forEach(function(moduleName) {
            if (!window[moduleName]) {
                console.error('MPAI: Required module not available: ' + moduleName);
                allAvailable = false;
            } else {
                // Store reference
                modules[moduleName] = window[moduleName];
            }
        });
        
        // Check optional modules but don't fail if they're not available
        optionalModules.forEach(function(moduleName) {
            if (window[moduleName]) {
                // Store reference if available
                modules[moduleName] = window[moduleName];
                console.log('MPAI: Optional module available: ' + moduleName);
            } else {
                console.log('MPAI: Optional module not available yet: ' + moduleName);
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
            
            // Log formatter availability for debugging
            if (window.mpaiLogger) {
                window.mpaiLogger.debug('MPAI_Formatters module initialized and available globally as window.MPAI_Formatters', 'ui');
            }
        }
        
        // Initialize messages (depends on UI utils)
        if (modules.MPAI_Messages) {
            modules.MPAI_Messages.init(elements);
            
            // Log messages module availability for debugging
            if (window.mpaiLogger) {
                window.mpaiLogger.debug('MPAI_Messages module initialized', 'ui');
            }
        }
        
        // Initialize tools (depends on messages and formatters)
        if (modules.MPAI_Tools) {
            // Pass the formatter module explicitly to ensure proper access
            modules.MPAI_Tools.init(elements, modules.MPAI_Messages, modules.MPAI_Formatters);
            
            // Log tools module initialization
            if (window.mpaiLogger) {
                window.mpaiLogger.debug('MPAI_Tools module initialized with formatters module', 'ui');
            }
        }
        
        // Initialize history (depends on messages)
        if (modules.MPAI_History) {
            modules.MPAI_History.init(elements, modules.MPAI_Messages);
        }
        
        // Initialize blog formatter (depends on messages and tools)
        if (modules.MPAI_BlogFormatter) {
            modules.MPAI_BlogFormatter.init();
            
            if (window.mpaiLogger) {
                window.mpaiLogger.debug('MPAI_BlogFormatter module initialized', 'ui');
            }
        }
        
        // These modules are now initialized by the module loader
        // Skip initialization here to avoid conflicts
        if (window.mpaiLogger) {
            window.mpaiLogger.debug('Skipping initialization of modules handled by module loader', 'ui');
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
            
            if (!elements.chatInput || elements.chatInput.length === 0) {
                console.error('MPAI: Message input not found, cannot process form submission');
                return;
            }
            
            const message = elements.chatInput.val();
            
            if (!message || message.trim() === '') {
                console.warn('MPAI: Empty message, not sending');
                return;
            }
            
            if (modules.MPAI_Messages) {
                modules.MPAI_Messages.sendMessage(message);
            } else {
                console.error('MPAI: Messages module not available, cannot send message');
            }
        });
        
        // Log the available nonces for debugging
        if (window.mpaiLogger) {
            window.mpaiLogger.debug('Available nonces:', 'ui', {
                mpai_nonce: !!mpai_chat_data.mpai_nonce,
                chat_nonce: !!mpai_chat_data.nonce
            });
            
            // Log more detailed information to help diagnose issues
            console.log('Chat interface data:', {
                ajax_url: mpai_chat_data.ajax_url,
                mpai_nonce: mpai_chat_data.mpai_nonce ? 'exists' : 'missing',
                chat_nonce: mpai_chat_data.nonce ? 'exists' : 'missing'
            });
        } else {
            console.log('Chat interface data:', {
                ajax_url: mpai_chat_data.ajax_url,
                mpai_nonce: mpai_chat_data.mpai_nonce ? 'exists' : 'missing',
                chat_nonce: mpai_chat_data.nonce ? 'exists' : 'missing'
            });
        }
        
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
        
        // Command runner button (wrench icon)
        $('#mpai-run-command').on('click', function() {
            $('#mpai-command-runner').slideToggle(200);
        });
        
        // Command runner close button
        $('#mpai-command-close').on('click', function() {
            $('#mpai-command-runner').slideUp(200);
        });
        
        // Command items in the command panel
        $(document).on('click', '.mpai-command-item', function(e) {
            e.preventDefault();
            const command = $(this).data('command');
            
            // Set the command to the input field
            elements.chatInput.val(command);
            
            // Hide the command runner
            $('#mpai-command-runner').slideUp(200);
            
            // Focus the input
            elements.chatInput.focus();
        });
        
        // Welcome message - display on load if no history
        // Increase delay to ensure history has fully loaded
        setTimeout(function() {
            if (elements.chatMessages.children().length === 0) {
                if (modules.MPAI_Messages) {
                    // Display welcome message
                    modules.MPAI_Messages.addMessage('assistant', mpai_chat_data.strings.welcome_message);
                    
                    if (window.mpaiLogger) {
                        window.mpaiLogger.info('Displayed welcome message (no history found)', 'ui');
                    }
                }
            }
        }, 1000); // Longer delay to ensure history has been fully loaded
        
        // Add click handlers for command toolbar buttons and selects
        $(document).on('click', '.mpai-run-suggested-command', function() {
            const command = $(this).data('command');
            if (command && elements.chatInput) {
                elements.chatInput.val(command);
                elements.chatForm.trigger('submit');
            }
        });
        
        $(document).on('change', '.mpai-command-select', function() {
            const selectedIndex = $(this).val();
            const $runBtn = $(this).siblings('.mpai-run-selected-command');
            
            if (selectedIndex !== '') {
                const commands = [];
                $(this).find('option').each(function() {
                    if ($(this).val() !== '') {
                        commands.push($(this).text());
                    }
                });
                
                const selectedCommand = commands[selectedIndex];
                $runBtn.data('command', selectedCommand);
                $runBtn.prop('disabled', false);
            } else {
                $runBtn.prop('disabled', true);
            }
        });
        
        $(document).on('click', '.mpai-run-selected-command', function() {
            if (!$(this).prop('disabled')) {
                const command = $(this).data('command');
                if (command && elements.chatInput) {
                    elements.chatInput.val(command);
                    elements.chatForm.trigger('submit');
                }
            }
        });
        
        // Add click handler for runnable commands
        $(document).on('click', '.mpai-runnable-command', function() {
            const command = $(this).data('command');
            if (command && elements.chatInput) {
                // Log the click event
                console.log('Clicked runnable command:', command);
                
                // Set the command in the input field
                elements.chatInput.val(command);
                
                // Add visual feedback
                const $this = $(this);
                $this.css('background-color', '#d4edff');
                
                // Submit the form after a brief delay to show the visual feedback
                setTimeout(function() {
                    elements.chatForm.trigger('submit');
                    $this.css('background-color', '');
                }, 200);
            } else {
                console.warn('Runnable command clicked but no command data found or no input field available');
            }
        });
        
        // Add click handler for copy message button
        $(document).on('click', '.mpai-copy-message', function() {
            const messageId = $(this).data('message-id');
            if (messageId && modules.MPAI_Messages) {
                // The actual copy functionality is in the messages module
                // We need to call it via window.MPAI_Messages to ensure it's available
                if (window.mpaiLogger) {
                    window.mpaiLogger.debug('Copy message clicked for: ' + messageId, 'ui');
                }
                
                if (typeof window.MPAI_Messages.copyMessageToClipboard === 'function') {
                    window.MPAI_Messages.copyMessageToClipboard(messageId);
                }
            }
        });
    }
})(jQuery);