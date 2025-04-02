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
        
        // Refresh chat history when opening
        if (window.MPAI_History && typeof window.MPAI_History.loadChatHistory === 'function') {
            window.MPAI_History.loadChatHistory();
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
        } else {
            elements.chatContainer.removeClass('mpai-chat-expanded');
            elements.chatExpand.html('<span class="dashicons dashicons-editor-expand"></span>');
            elements.chatExpand.attr('title', 'Expand chat');
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
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    
    /**
     * Function to adjust the height of the input textarea
     */
    function adjustInputHeight() {
        const textarea = elements.chatInput[0];
        
        // Reset height to auto to get the correct scrollHeight
        textarea.style.height = 'auto';
        
        // Set the height based on scrollHeight, with a max of 150px
        const newHeight = Math.min(textarea.scrollHeight, 150);
        textarea.style.height = newHeight + 'px';
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
        // Create modal container
        const $modal = $('<div>', {
            'class': 'mpai-modal'
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
        $modal.fadeOut(300, function() {
            $modal.remove();
            
            // Remove global event handler
            $(document).off('keydown.mpaiModal');
            
            // Call callback if provided
            if (typeof callback === 'function') {
                callback();
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