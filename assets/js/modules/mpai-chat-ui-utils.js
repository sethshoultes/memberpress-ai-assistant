/**
 * MemberPress AI Assistant - Chat UI Utilities Module
 * 
 * Handles UI-related utilities for the chat interface
 */

var MPAI_UIUtils = (function($) {
    'use strict';
    
    // Private variables
    var elements = {};
    var isExpanded = false;
    
    /**
     * Initialize the module
     * 
     * @param {Object} domElements - DOM elements
     */
    function init(domElements) {
        elements = domElements;
        
        if (window.mpaiLogger) {
            window.mpaiLogger.info('UI Utils module initialized', 'ui');
        }
        
        // Set up event listeners
        setupEventListeners();
    }
    
    /**
     * Set up event listeners for UI elements
     */
    function setupEventListeners() {
        // Chat toggle (open)
        elements.chatToggle.on('click', function() {
            openChat();
        });
        
        // Chat close
        elements.chatClose.on('click', function() {
            closeChat();
        });
        
        // Chat minimize
        elements.chatMinimize.on('click', function() {
            minimizeChat();
        });
        
        // Chat expand/collapse
        elements.chatExpand.on('click', function() {
            toggleChatExpansion();
        });
        
        // Input auto-resize
        elements.chatInput.on('input', function() {
            adjustInputHeight();
        });
    }
    
    /**
     * Function to open the chat
     */
    function openChat() {
        elements.chatContainer.css('display', 'flex').hide().fadeIn(300);
        elements.chatToggle.hide();
        elements.chatInput.focus();
        
        // Save state to localStorage
        localStorage.setItem('mpai_chat_open', 'true');
        
        // Refresh chat history when opening only if it's empty
        if (elements.chatMessages.children().length === 0) {
            if (window.MPAI_History && typeof window.MPAI_History.loadChatHistory === 'function') {
                window.MPAI_History.loadChatHistory();
            }
        } else {
            // Just scroll to bottom if messages exist
            scrollToBottom();
        }
        
        if (window.mpaiLogger) {
            window.mpaiLogger.info('Chat opened', 'ui');
        }
    }
    
    /**
     * Function to close the chat
     */
    function closeChat() {
        elements.chatContainer.fadeOut(300);
        elements.chatToggle.fadeIn(300);
        
        // Save state to localStorage
        localStorage.setItem('mpai_chat_open', 'false');
        
        if (window.mpaiLogger) {
            window.mpaiLogger.info('Chat closed', 'ui');
        }
    }
    
    /**
     * Function to minimize the chat
     */
    function minimizeChat() {
        elements.chatContainer.fadeOut(300);
        elements.chatToggle.fadeIn(300);
        
        // Save state to localStorage
        localStorage.setItem('mpai_chat_open', 'false');
        
        if (window.mpaiLogger) {
            window.mpaiLogger.info('Chat minimized', 'ui');
        }
    }
    
    /**
     * Function to toggle chat expansion
     */
    function toggleChatExpansion() {
        isExpanded = !isExpanded;
        
        if (isExpanded) {
            elements.chatContainer.addClass('mpai-chat-expanded');
            elements.chatExpand.html('<span class="dashicons dashicons-editor-contract"></span>');
            elements.chatExpand.attr('title', 'Collapse chat');
            
            // Save expansion state to localStorage
            localStorage.setItem('mpai_chat_expanded', 'true');
        } else {
            elements.chatContainer.removeClass('mpai-chat-expanded');
            elements.chatExpand.html('<span class="dashicons dashicons-editor-expand"></span>');
            elements.chatExpand.attr('title', 'Expand chat');
            
            // Save expansion state to localStorage
            localStorage.setItem('mpai_chat_expanded', 'false');
        }
        
        // Scroll to bottom after expansion change
        scrollToBottom();
        
        if (window.mpaiLogger) {
            window.mpaiLogger.info('Chat expansion toggled: ' + (isExpanded ? 'expanded' : 'collapsed'), 'ui');
        }
    }
    
    /**
     * Function to scroll to the bottom of the chat
     */
    function scrollToBottom() {
        const messagesContainer = elements.chatMessages[0];
        if (!messagesContainer) {
            if (window.mpaiLogger) {
                window.mpaiLogger.error('Unable to scroll to bottom - messages container not found', 'ui');
            }
            return;
        }
        
        const previousScroll = messagesContainer.scrollTop;
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
        
        if (window.mpaiLogger) {
            window.mpaiLogger.debug('Scrolled to bottom of chat', 'ui', {
                previous: previousScroll,
                current: messagesContainer.scrollTop,
                scrollHeight: messagesContainer.scrollHeight,
                containerHeight: messagesContainer.clientHeight
            });
        }
    }
    
    /**
     * Function to adjust the height of the input textarea
     */
    function adjustInputHeight() {
        const textarea = elements.chatInput[0];
        if (!textarea) {
            if (window.mpaiLogger) {
                window.mpaiLogger.error('Unable to adjust input height - textarea not found', 'ui');
            }
            return;
        }
        
        const originalHeight = textarea.style.height;
        
        // Reset height to auto to get the correct scrollHeight
        textarea.style.height = 'auto';
        
        // Set the height based on scrollHeight, with a max of 150px
        const newHeight = Math.min(textarea.scrollHeight, 150);
        textarea.style.height = newHeight + 'px';
        
        if (window.mpaiLogger && originalHeight !== newHeight + 'px') {
            window.mpaiLogger.debug('Adjusted input height', 'ui', {
                original: originalHeight,
                new: newHeight + 'px',
                scrollHeight: textarea.scrollHeight,
                content: textarea.value.length + ' chars'
            });
        }
    }
    
    /**
     * Show a modal dialog
     * 
     * @param {Object} options - Modal options
     * @param {string} options.title - Modal title
     * @param {string} options.content - Modal content HTML
     * @param {function} options.onClose - Callback function when modal is closed
     * @return {jQuery} The modal element
     */
    function showModal(options) {
        if (window.mpaiLogger) {
            window.mpaiLogger.startTimer('show_modal');
            window.mpaiLogger.info('Showing modal dialog', 'ui', {
                title: options.title || 'Modal',
                hasContent: !!options.content,
                hasCloseCallback: typeof options.onClose === 'function'
            });
        }
        
        // Create modal container
        const $modal = $('<div>', {
            'class': 'mpai-modal',
            'id': 'mpai-modal-' + Date.now()
        });
        
        // Create modal content
        const $modalContent = $('<div>', {
            'class': 'mpai-modal-content'
        });
        
        // Create close button
        const $closeBtn = $('<span>', {
            'class': 'mpai-modal-close'
        }).html('&times;');
        
        // Create title
        const $title = $('<h2>').text(options.title || 'Modal');
        
        // Add content
        $modalContent.append($closeBtn, $title, options.content);
        $modal.append($modalContent);
        
        // Add to body
        $('body').append($modal);
        
        // Show modal
        $modal.fadeIn(300);
        
        if (window.mpaiLogger) {
            const elapsed = window.mpaiLogger.endTimer('show_modal');
            window.mpaiLogger.debug('Modal dialog displayed', 'ui', {
                timeMs: elapsed,
                modalId: $modal.attr('id'),
                contentSize: $modalContent.html().length
            });
        }
        
        // Set up close button
        $closeBtn.on('click', function() {
            closeModal($modal, options.onClose);
        });
        
        // Close on click outside content
        $modal.on('click', function(e) {
            if (e.target === $modal[0]) {
                closeModal($modal, options.onClose);
            }
        });
        
        // Close on escape key
        $(document).on('keydown.mpaiModal', function(e) {
            if (e.key === 'Escape') {
                closeModal($modal, options.onClose);
            }
        });
        
        return $modal;
    }
    
    /**
     * Close a modal dialog
     * 
     * @param {jQuery} $modal - The modal element
     * @param {function} callback - Optional callback function
     */
    function closeModal($modal, callback) {
        if (!$modal || $modal.length === 0) {
            if (window.mpaiLogger) {
                window.mpaiLogger.error('Attempt to close non-existent modal', 'ui');
            }
            return;
        }
        
        const modalId = $modal.attr('id') || 'unknown';
        
        if (window.mpaiLogger) {
            window.mpaiLogger.startTimer('close_modal_' + modalId);
            window.mpaiLogger.info('Closing modal dialog', 'ui', {
                modalId: modalId,
                hasCallback: typeof callback === 'function'
            });
        }
        
        $modal.fadeOut(300, function() {
            $modal.remove();
            
            // Remove global event handler
            $(document).off('keydown.mpaiModal');
            
            // Call callback if provided
            if (typeof callback === 'function') {
                try {
                    callback();
                    
                    if (window.mpaiLogger) {
                        window.mpaiLogger.debug('Modal close callback executed successfully', 'ui');
                    }
                } catch (error) {
                    if (window.mpaiLogger) {
                        window.mpaiLogger.error('Error in modal close callback', 'ui', {
                            error: error.message,
                            stack: error.stack
                        });
                    } else {
                        console.error('Error in modal close callback:', error);
                    }
                }
            }
            
            if (window.mpaiLogger) {
                const elapsed = window.mpaiLogger.endTimer('close_modal_' + modalId);
                window.mpaiLogger.debug('Modal dialog closed', 'ui', {
                    timeMs: elapsed,
                    modalId: modalId
                });
            }
        });
    }
    
    // Public API
    return {
        init: init,
        openChat: openChat,
        closeChat: closeChat,
        minimizeChat: minimizeChat,
        toggleChatExpansion: toggleChatExpansion,
        scrollToBottom: scrollToBottom,
        adjustInputHeight: adjustInputHeight,
        showModal: showModal,
        closeModal: closeModal
    };
})(jQuery);

// Expose the module globally
window.MPAI_UIUtils = MPAI_UIUtils;