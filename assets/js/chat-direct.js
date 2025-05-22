/**
 * MemberPress AI Assistant - Direct Chat Implementation
 * 
 * This file provides a direct implementation of the chat functionality
 * without relying on ES6 modules, which were causing issues with
 * global scope access and jQuery availability.
 */

(function($) {
    'use strict';
    
    console.log('[MPAI Debug] chat-direct.js loading');
    console.log('[MPAI Debug] jQuery available:', typeof $ === 'function');
    
    // Simple EventBus implementation
    var EventBus = {
        events: {},
        
        subscribe: function(event, callback) {
            if (!this.events[event]) {
                this.events[event] = [];
            }
            this.events[event].push(callback);
            return this;
        },
        
        publish: function(event, data) {
            if (!this.events[event]) {
                return this;
            }
            this.events[event].forEach(function(callback) {
                callback(data);
            });
            return this;
        }
    };
    
    // Simple StateManager implementation
    var StateManager = {
        state: {
            ui: {
                isChatOpen: false,
                isExpanded: false
            }
        },
        
        getState: function(path) {
            if (!path) {
                return this.state;
            }
            
            var parts = path.split('.');
            var current = this.state;
            
            for (var i = 0; i < parts.length; i++) {
                if (current === undefined || current === null) {
                    return undefined;
                }
                current = current[parts[i]];
            }
            
            return current;
        },
        
        updateUI: function(updates) {
            this.state.ui = $.extend(this.state.ui, updates);
            EventBus.publish('ui.updated', { ui: this.state.ui });
            return this.state.ui;
        }
    };
    
    // Chat functionality
    var Chat = {
        initialized: false,
        
        init: function() {
            if (this.initialized) {
                return;
            }
            
            console.log('[MPAI Debug] Initializing chat');
            
            // Find the chat button
            this.chatButton = document.querySelector('.mpai-chat-toggle');
            if (!this.chatButton) {
                this.chatButton = document.querySelector('#mpai-chat-toggle');
            }
            if (!this.chatButton) {
                this.chatButton = document.querySelector('[aria-label="Toggle chat"]');
            }
            
            if (this.chatButton) {
                console.log('[MPAI Debug] Chat button found:', this.chatButton);
                
                // Add click handler
                $(this.chatButton).on('click', function() {
                    console.log('[MPAI Debug] Chat button clicked');
                    Chat.toggleChat();
                });
            } else {
                console.error('[MPAI Debug] Chat button not found');
            }
            
            // Find the chat container - try multiple selectors
            console.log('[MPAI Debug] Looking for chat container with different selectors');
            
            const possibleContainers = [
                '#mpai-chat-container',
                '.mpai-chat-container',
                '.mpai-chat',
                '#mpai-chat',
                '[data-mpai-chat]'
            ];
            
            for (var i = 0; i < possibleContainers.length; i++) {
                this.chatContainer = document.querySelector(possibleContainers[i]);
                if (this.chatContainer) {
                    console.log('[MPAI Debug] Chat container found with selector:', possibleContainers[i]);
                    break;
                }
            }
            
            if (this.chatContainer) {
                console.log('[MPAI Debug] Chat container details:', {
                    element: this.chatContainer,
                    id: this.chatContainer.id,
                    classes: this.chatContainer.className,
                    display: $(this.chatContainer).css('display'),
                    visibility: $(this.chatContainer).css('visibility'),
                    opacity: $(this.chatContainer).css('opacity'),
                    position: $(this.chatContainer).css('position')
                });
                
                // Log all elements with 'chat' in their class name or ID
                console.log('[MPAI Debug] All elements with "chat" in class or ID:');
                document.querySelectorAll('*[class*="chat"], *[id*="chat"]').forEach(function(el) {
                    console.log('- ' + el.tagName + ' #' + el.id + ' .' + el.className);
                });
            } else {
                console.error('[MPAI Debug] Chat container not found with any selector');
                
                // As a last resort, try to find any element that might be the chat container
                console.log('[MPAI Debug] Looking for any element that might be the chat container');
                
                // Look for elements with certain CSS properties that suggest they might be the chat container
                var allElements = document.querySelectorAll('div, section, aside');
                for (var i = 0; i < allElements.length; i++) {
                    var el = allElements[i];
                    var style = window.getComputedStyle(el);
                    
                    // Check if it has properties that suggest it might be a chat container
                    if (style.position === 'fixed' &&
                        (style.bottom === '0px' || parseInt(style.bottom) < 100) &&
                        (style.right === '0px' || parseInt(style.right) < 100) &&
                        el.offsetWidth > 200 && el.offsetHeight > 200) {
                        
                        console.log('[MPAI Debug] Potential chat container found:', {
                            element: el,
                            id: el.id,
                            classes: el.className,
                            display: style.display,
                            visibility: style.visibility,
                            opacity: style.opacity,
                            position: style.position,
                            width: el.offsetWidth,
                            height: el.offsetHeight
                        });
                        
                        // Use this as a fallback if no other container is found
                        if (!this.chatContainer) {
                            this.chatContainer = el;
                        }
                    }
                }
            }
            
            // Find the expand button
            this.expandButton = document.querySelector('.mpai-chat-expand');
            if (this.expandButton) {
                console.log('[MPAI Debug] Expand button found:', this.expandButton);
                
                // Add click handler
                $(this.expandButton).on('click', function() {
                    console.log('[MPAI Debug] Expand button clicked');
                    Chat.toggleExpand();
                });
            } else {
                console.log('[MPAI Debug] Expand button not found');
            }
            
            // Find the close button
            this.closeButton = document.querySelector('.mpai-chat-close');
            if (this.closeButton) {
                console.log('[MPAI Debug] Close button found:', this.closeButton);
                
                // Add click handler
                $(this.closeButton).on('click', function() {
                    console.log('[MPAI Debug] Close button clicked');
                    Chat.toggleChat();
                });
            } else {
                console.log('[MPAI Debug] Close button not found');
            }
            
            // Find the clear conversation link
            this.clearButton = document.querySelector('#mpai-clear-conversation');
            if (this.clearButton) {
                console.log('[MPAI Debug] Clear conversation button found:', this.clearButton);
                
                // Add click handler
                $(this.clearButton).on('click', function(e) {
                    e.preventDefault();
                    console.log('[MPAI Debug] Clear conversation button clicked');
                    // This would typically clear the conversation history
                    // For now, just log that it was clicked
                    console.log('[MPAI Debug] Conversation would be cleared here');
                });
            } else {
                console.log('[MPAI Debug] Clear conversation button not found');
            }
            
            // Find the download conversation button
            this.downloadButton = document.querySelector('#mpai-download-conversation');
            if (this.downloadButton) {
                console.log('[MPAI Debug] Download conversation button found:', this.downloadButton);
                
                // Add click handler
                $(this.downloadButton).on('click', function() {
                    console.log('[MPAI Debug] Download conversation button clicked');
                    // This would typically download the conversation
                    // For now, just log that it was clicked
                    console.log('[MPAI Debug] Conversation would be downloaded here');
                });
            } else {
                console.log('[MPAI Debug] Download conversation button not found');
            }
            
            // Find the run command button
            this.runCommandButton = document.querySelector('#mpai-run-command');
            if (this.runCommandButton) {
                console.log('[MPAI Debug] Run command button found:', this.runCommandButton);
                
                // Add click handler
                $(this.runCommandButton).on('click', function() {
                    console.log('[MPAI Debug] Run command button clicked');
                    Chat.toggleCommandRunner();
                });
            } else {
                console.log('[MPAI Debug] Run command button not found');
            }
            
            // Find the command close button
            this.commandCloseButton = document.querySelector('#mpai-command-close');
            if (this.commandCloseButton) {
                console.log('[MPAI Debug] Command close button found:', this.commandCloseButton);
                
                // Add click handler
                $(this.commandCloseButton).on('click', function() {
                    console.log('[MPAI Debug] Command close button clicked');
                    Chat.toggleCommandRunner(false);
                });
            } else {
                console.log('[MPAI Debug] Command close button not found');
            }
            
            // Find all command items
            this.commandItems = document.querySelectorAll('.mpai-command-item');
            if (this.commandItems.length > 0) {
                console.log('[MPAI Debug] Command items found:', this.commandItems.length);
                
                // Add click handler to each command item
                this.commandItems.forEach(function(item) {
                    $(item).on('click', function(e) {
                        e.preventDefault();
                        var command = $(this).data('command');
                        console.log('[MPAI Debug] Command item clicked:', command);
                        // This would typically insert the command into the input field
                        // For now, just log that it was clicked
                        console.log('[MPAI Debug] Command would be inserted here:', command);
                    });
                });
            } else {
                console.log('[MPAI Debug] No command items found');
            }
            
            this.initialized = true;
            console.log('[MPAI Debug] Chat initialized');
        },
        
        toggleChat: function() {
            console.log('[MPAI Debug] toggleChat called');
            
            if (!this.chatContainer) {
                console.error('[MPAI Debug] Chat container not found');
                return;
            }
            
            // Get current state
            var isOpen = StateManager.getState('ui.isChatOpen');
            console.log('[MPAI Debug] Current chat state:', isOpen);
            
            // Toggle state
            var newState = !isOpen;
            StateManager.updateUI({ isChatOpen: newState });
            console.log('[MPAI Debug] New chat state:', newState);
            
            // Update UI using the CSS classes defined in chat.css
            if (newState) {
                // Log the current state of the chat container
                console.log('[MPAI Debug] Chat container before showing:', {
                    element: this.chatContainer,
                    classes: this.chatContainer.className
                });
                
                // Add the 'active' class to show the chat (as defined in chat.css)
                $(this.chatContainer).addClass('active');
                
                // Also update the toggle button state
                if (this.chatButton) {
                    $(this.chatButton).addClass('active');
                }
                
                console.log('[MPAI Debug] Chat made visible with active class');
                
                // Log the state after our changes
                console.log('[MPAI Debug] Chat container after showing:', {
                    classes: this.chatContainer.className
                });
            } else {
                // Remove the 'active' class to hide the chat
                $(this.chatContainer).removeClass('active');
                
                // Also update the toggle button state
                if (this.chatButton) {
                    $(this.chatButton).removeClass('active');
                }
                
                console.log('[MPAI Debug] Chat hidden by removing active class');
            }
            
            return newState;
        },
        
        toggleExpand: function() {
            console.log('[MPAI Debug] toggleExpand called');
            
            if (!this.chatContainer) {
                console.error('[MPAI Debug] Chat container not found');
                return;
            }
            
            // Get current state
            var isExpanded = StateManager.getState('ui.isExpanded');
            console.log('[MPAI Debug] Current expand state:', isExpanded);
            
            // Toggle state
            var newState = !isExpanded;
            StateManager.updateUI({ isExpanded: newState });
            console.log('[MPAI Debug] New expand state:', newState);
            
            // Update UI using the CSS classes defined in chat.css
            if (newState) {
                // Add the 'mpai-chat-expanded' class to expand the chat (as defined in chat.css)
                $(this.chatContainer).addClass('mpai-chat-expanded');
                console.log('[MPAI Debug] Chat expanded with mpai-chat-expanded class');
            } else {
                // Remove the 'mpai-chat-expanded' class to collapse the chat
                $(this.chatContainer).removeClass('mpai-chat-expanded');
                console.log('[MPAI Debug] Chat collapsed by removing mpai-chat-expanded class');
            }
            
            return newState;
        },
        
        toggleCommandRunner: function(show) {
            console.log('[MPAI Debug] toggleCommandRunner called with:', show);
            
            // Find the command runner panel
            var commandRunner = document.querySelector('#mpai-command-runner');
            if (!commandRunner) {
                console.error('[MPAI Debug] Command runner panel not found');
                return;
            }
            
            // If show is not provided, toggle the current state
            if (typeof show === 'undefined') {
                show = commandRunner.style.display === 'none' || !commandRunner.style.display;
            }
            
            // Update UI
            if (show) {
                $(commandRunner).show();
                console.log('[MPAI Debug] Command runner panel shown');
            } else {
                $(commandRunner).hide();
                console.log('[MPAI Debug] Command runner panel hidden');
            }
            
            return show;
        }
    };
    
    // Initialize when the document is ready
    $(document).ready(function() {
        console.log('[MPAI Debug] Document ready, initializing chat');
        Chat.init();
    });
    
    // Also initialize when the window loads (as a backup)
    $(window).on('load', function() {
        console.log('[MPAI Debug] Window loaded, initializing chat if not already initialized');
        if (!Chat.initialized) {
            Chat.init();
        }
    });
    
    // Make chat available globally
    window.MPAI_Chat = Chat;
    window.MPAI_EventBus = EventBus;
    window.MPAI_StateManager = StateManager;
    
})(jQuery);