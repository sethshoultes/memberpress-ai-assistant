<?php
/**
 * Chat Interface Template for AJAX Loading
 *
 * This template is optimized for AJAX loading contexts where jQuery and other
 * assets need to be handled differently than in normal page loads.
 *
 * @package MemberPressCopilot
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Check if this is being loaded in AJAX context
$is_ajax_context = isset($is_ajax_context) ? $is_ajax_context : false;
?>

<div class="mpai-chat-container" id="mpai-chat-container">
    <div class="mpai-chat-header">
        <h3><?php esc_html_e('MemberPress Copilot', 'memberpress-copilot'); ?></h3>
        <button class="mpai-chat-expand" id="mpai-chat-expand" aria-label="<?php esc_attr_e('Expand chat', 'memberpress-copilot'); ?>" title="<?php esc_attr_e('Expand chat', 'memberpress-copilot'); ?>">
            <span class="dashicons dashicons-editor-expand"></span>
        </button>
        <button class="mpai-chat-close" id="mpai-chat-close" aria-label="<?php esc_attr_e('Close chat', 'memberpress-copilot'); ?>">
            <span class="dashicons dashicons-no-alt"></span>
        </button>
    </div>
    
    <div class="mpai-chat-messages" id="mpai-chat-messages">
        <div class="mpai-chat-welcome">
            <div class="mpai-chat-message mpai-chat-message-assistant">
                <div class="mpai-chat-message-content">
                    <?php esc_html_e('Hello! I\'m your MemberPress Copilot. How can I help you today?', 'memberpress-copilot'); ?>
                </div>
            </div>
        </div>
        <!-- Chat messages will be dynamically inserted here -->
    </div>
    
    <div class="mpai-chat-input-container">
        <div class="mpai-chat-input-wrapper">
            <textarea 
                id="mpai-chat-input" 
                class="mpai-chat-input" 
                placeholder="<?php esc_attr_e('Type your message here...', 'memberpress-copilot'); ?>"
                rows="1"
                aria-label="<?php esc_attr_e('Message input', 'memberpress-copilot'); ?>"
            ></textarea>
            <button 
                id="mpai-chat-submit" 
                class="mpai-chat-submit" 
                aria-label="<?php esc_attr_e('Send message', 'memberpress-copilot'); ?>"
            >
                <span class="dashicons dashicons-arrow-right-alt2"></span>
            </button>
        </div>
        <div class="mpai-chat-footer">
            <span class="mpai-chat-powered-by">
                <?php esc_html_e('Powered by MemberPress AI', 'memberpress-copilot'); ?>
            </span>
            <div class="mpai-chat-footer-actions">
                <a href="#" id="mpai-clear-conversation" class="mpai-clear-conversation">
                    <?php esc_html_e('Clear Conversation', 'memberpress-copilot'); ?>
                </a>
                <button id="mpai-download-conversation" class="mpai-download-conversation" aria-label="<?php esc_attr_e('Download conversation', 'memberpress-copilot'); ?>" title="<?php esc_attr_e('Download conversation', 'memberpress-copilot'); ?>">
                    <span class="dashicons dashicons-download"></span>
                </button>
                <button id="mpai-run-command" class="mpai-run-command" aria-label="<?php esc_attr_e('Show common commands', 'memberpress-copilot'); ?>" title="<?php esc_attr_e('Show common commands', 'memberpress-copilot'); ?>">
                    <span class="dashicons dashicons-admin-tools"></span>
                </button>
            </div>
        </div>
        
        <!-- Export format menu -->
        <div id="mpai-export-format-menu" class="mpai-export-format-menu" style="display: none;">
            <div class="mpai-export-format-title"><?php esc_html_e('Export Format', 'memberpress-copilot'); ?></div>
            <div class="mpai-export-format-options">
                <button class="mpai-export-format-btn" data-format="html"><?php esc_html_e('HTML', 'memberpress-copilot'); ?></button>
                <button class="mpai-export-format-btn" data-format="markdown"><?php esc_html_e('Markdown', 'memberpress-copilot'); ?></button>
            </div>
        </div>
    </div>
    
    <!-- Command runner panel (initially hidden) -->
    <div id="mpai-command-runner" class="mpai-command-runner" style="display: none;">
        <div class="mpai-command-header">
            <h4><?php esc_html_e('Common Commands', 'memberpress-copilot'); ?></h4>
            <button id="mpai-command-close" class="mpai-command-close" aria-label="<?php esc_attr_e('Close command panel', 'memberpress-copilot'); ?>">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <div class="mpai-command-body">
            <div class="mpai-command-list">
                <h5><?php esc_html_e('MemberPress', 'memberpress-copilot'); ?></h5>
                <ul>
                    <li><a href="#" class="mpai-command-item" data-command="List all active memberships">List all active memberships</a></li>
                    <li><a href="#" class="mpai-command-item" data-command="Show recent transactions">Show recent transactions</a></li>
                    <li><a href="#" class="mpai-command-item" data-command="Summarize membership data">Summarize membership data</a></li>
                </ul>
            </div>
            <div class="mpai-command-list">
                <h5><?php esc_html_e('WordPress', 'memberpress-copilot'); ?></h5>
                <ul>
                    <li><a href="#" class="mpai-command-item" data-command="wp plugin list">wp plugin list</a></li>
                    <li><a href="#" class="mpai-command-item" data-command="wp user list">wp user list</a></li>
                    <li><a href="#" class="mpai-command-item" data-command="wp post list">wp post list</a></li>
                </ul>
            </div>
            <div class="mpai-command-list">
                <h5><?php esc_html_e('Content Creation', 'memberpress-copilot'); ?></h5>
                <ul>
                    <li><a href="#" class="mpai-command-item" data-command="Create a blog post about">Create a blog post</a></li>
                    <li><a href="#" class="mpai-command-item" data-command="Create a page about">Create a page</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Chat toggle button (fixed position) -->
<button id="mpai-chat-toggle" class="mpai-chat-toggle" aria-label="<?php esc_attr_e('Toggle chat', 'memberpress-copilot'); ?>">
    <span class="dashicons dashicons-format-chat"></span>
</button>

<?php if ($is_ajax_context): ?>
<!-- AJAX-specific initialization script that doesn't rely on jQuery being pre-loaded -->
<script type="text/javascript">
(function() {
    'use strict';
    
    // Wait for jQuery to be available (it will be loaded by the AJAX response handler)
    function waitForJQuery(callback) {
        if (typeof jQuery !== 'undefined') {
            callback(jQuery);
        } else {
            setTimeout(function() {
                waitForJQuery(callback);
            }, 100);
        }
    }
    
    // Initialize chat interface when jQuery is ready
    waitForJQuery(function($) {
        console.log('MPAI: Initializing chat interface in AJAX context');
        
        // Function to process existing messages
        function processExistingMessages() {
            $('.mpai-chat-message-assistant').each(function() {
                var $message = $(this);
                var content = $message.find('.mpai-chat-message-content').text();
                
                if (content && (
                    content.includes('<wp-post>') ||
                    content.includes('</wp-post>') ||
                    content.includes('<post-title>') ||
                    content.includes('</post-title>') ||
                    content.includes('<post-content>') ||
                    content.includes('</post-content>')
                )) {
                    if (window.MPAI_BlogFormatter) {
                        window.MPAI_BlogFormatter.processAssistantMessage($message, content);
                    }
                }
            });
        }
        
        // Initialize blog formatter if available
        if (window.MPAI_BlogFormatter) {
            window.MPAI_BlogFormatter.init();
            setTimeout(processExistingMessages, 1000);
        } else {
            // Load blog formatter script
            var script = document.createElement('script');
            script.src = '<?php echo esc_url(MPAI_PLUGIN_URL . 'assets/js/blog-formatter.js'); ?>';
            script.onload = function() {
                if (window.MPAI_BlogFormatter) {
                    window.MPAI_BlogFormatter.init();
                    setTimeout(processExistingMessages, 1000);
                }
            };
            document.head.appendChild(script);
        }
        
        // Set up mutation observer for new messages
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1) {
                            var $node = $(node);
                            if ($node.hasClass('mpai-chat-message-assistant') || $node.find('.mpai-chat-message-assistant').length > 0) {
                                setTimeout(processExistingMessages, 500);
                            }
                        }
                    });
                }
            });
        });
        
        // Start observing the chat container
        var chatContainer = document.querySelector('.mpai-chat-messages');
        if (chatContainer) {
            observer.observe(chatContainer, { childList: true, subtree: true });
        }
        
        // Initialize chat functionality if MPAI_Chat is available
        if (window.MPAI_Chat && typeof window.MPAI_Chat.init === 'function') {
            window.MPAI_Chat.init();
            console.log('MPAI: Chat interface initialized successfully');
        } else {
            console.log('MPAI: Chat core not yet available, will initialize when loaded');
        }
    });
})();
</script>
<?php else: ?>
<!-- Standard initialization script for non-AJAX context -->
<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
    // Function to process existing messages (reduced logging)
    function processExistingMessages() {
        if (typeof jQuery !== 'undefined') {
            jQuery('.mpai-chat-message-assistant').each(function() {
                const $message = jQuery(this);
                const content = $message.find('.mpai-chat-message-content').text();
                
                if (content && (
                    content.includes('<wp-post>') ||
                    content.includes('</wp-post>') ||
                    content.includes('<post-title>') ||
                    content.includes('</post-title>') ||
                    content.includes('<post-content>') ||
                    content.includes('</post-content>')
                )) {
                    if (window.MPAI_BlogFormatter) {
                        window.MPAI_BlogFormatter.processAssistantMessage($message, content);
                    }
                }
            });
        }
    }
    
    // Check if blog formatter is available
    if (window.MPAI_BlogFormatter) {
        window.MPAI_BlogFormatter.init();
        setTimeout(processExistingMessages, 1000);
    } else {
        // Create script element
        var script = document.createElement('script');
        script.src = '<?php echo esc_url(MPAI_PLUGIN_URL . 'assets/js/blog-formatter.js'); ?>';
        script.onload = function() {
            if (window.MPAI_BlogFormatter) {
                window.MPAI_BlogFormatter.init();
                setTimeout(processExistingMessages, 1000);
            }
        };
        document.head.appendChild(script);
    }
    
    // Set up a mutation observer to watch for new messages
    if (typeof jQuery !== 'undefined') {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1) {
                            const $node = jQuery(node);
                            if ($node.hasClass('mpai-chat-message-assistant') || $node.find('.mpai-chat-message-assistant').length > 0) {
                                setTimeout(processExistingMessages, 500);
                            }
                        }
                    });
                }
            });
        });
        
        // Start observing the chat container
        const chatContainer = document.querySelector('.mpai-chat-messages');
        if (chatContainer) {
            observer.observe(chatContainer, { childList: true, subtree: true });
        }
    }
});
</script>
<?php endif; ?>