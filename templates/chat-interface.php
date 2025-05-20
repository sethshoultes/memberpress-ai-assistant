<?php
/**
 * Chat Interface Template
 *
 * Renders the MemberPress AI Assistant chat interface.
 *
 * @package MemberpressAiAssistant
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="mpai-chat-container" id="mpai-chat-container">
    <div class="mpai-chat-header">
            <h3><?php esc_html_e('MemberPress AI Assistant', 'memberpress-ai-assistant'); ?></h3>
            <button class="mpai-chat-expand" id="mpai-chat-expand" aria-label="<?php esc_attr_e('Expand chat', 'memberpress-ai-assistant'); ?>" title="<?php esc_attr_e('Expand chat', 'memberpress-ai-assistant'); ?>">
                <span class="dashicons dashicons-editor-expand"></span>
            </button>
            <button class="mpai-chat-close" id="mpai-chat-close" aria-label="<?php esc_attr_e('Close chat', 'memberpress-ai-assistant'); ?>">
            <span class="dashicons dashicons-no-alt"></span>
        </button>
    </div>
    
    <div class="mpai-chat-messages" id="mpai-chat-messages">
        <div class="mpai-chat-welcome">
            <div class="mpai-chat-message mpai-chat-message-assistant">
                <div class="mpai-chat-message-content">
                    <?php esc_html_e('Hello! I\'m your MemberPress AI Assistant. How can I help you today?', 'memberpress-ai-assistant'); ?>
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
                placeholder="<?php esc_attr_e('Type your message here...', 'memberpress-ai-assistant'); ?>"
                rows="1"
                aria-label="<?php esc_attr_e('Message input', 'memberpress-ai-assistant'); ?>"
            ></textarea>
            <button 
                id="mpai-chat-submit" 
                class="mpai-chat-submit" 
                aria-label="<?php esc_attr_e('Send message', 'memberpress-ai-assistant'); ?>"
            >
                <span class="dashicons dashicons-arrow-right-alt2"></span>
            </button>
        </div>
        <div class="mpai-chat-footer">
            <span class="mpai-chat-powered-by">
                <?php esc_html_e('Powered by MemberPress AI', 'memberpress-ai-assistant'); ?>
            </span>
            <div class="mpai-chat-footer-actions">
                <a href="#" id="mpai-clear-conversation" class="mpai-clear-conversation">
                    <?php esc_html_e('Clear Conversation', 'memberpress-ai-assistant'); ?>
                </a>
                <button id="mpai-download-conversation" class="mpai-download-conversation" aria-label="<?php esc_attr_e('Download conversation', 'memberpress-ai-assistant'); ?>" title="<?php esc_attr_e('Download conversation', 'memberpress-ai-assistant'); ?>">
                    <span class="dashicons dashicons-download"></span>
                </button>
                <button id="mpai-run-command" class="mpai-run-command" aria-label="<?php esc_attr_e('Show common commands', 'memberpress-ai-assistant'); ?>" title="<?php esc_attr_e('Show common commands', 'memberpress-ai-assistant'); ?>">
                    <span class="dashicons dashicons-admin-tools"></span>
                </button>
            </div>
        </div>
        
        <!-- Add this after the chat-footer div -->
        <div id="mpai-export-format-menu" class="mpai-export-format-menu" style="display: none;">
            <div class="mpai-export-format-title"><?php esc_html_e('Export Format', 'memberpress-ai-assistant'); ?></div>
            <div class="mpai-export-format-options">
                <button class="mpai-export-format-btn" data-format="html"><?php esc_html_e('HTML', 'memberpress-ai-assistant'); ?></button>
                <button class="mpai-export-format-btn" data-format="markdown"><?php esc_html_e('Markdown', 'memberpress-ai-assistant'); ?></button>
            </div>
        </div>
    </div>
    
    <!-- Command runner panel (initially hidden) -->
    <div id="mpai-command-runner" class="mpai-command-runner" style="display: none;">
        <div class="mpai-command-header">
            <h4><?php esc_html_e('Common Commands', 'memberpress-ai-assistant'); ?></h4>
            <button id="mpai-command-close" class="mpai-command-close" aria-label="<?php esc_attr_e('Close command panel', 'memberpress-ai-assistant'); ?>">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <div class="mpai-command-body">
            <div class="mpai-command-list">
                <h5><?php esc_html_e('MemberPress', 'memberpress-ai-assistant'); ?></h5>
                <ul>
                    <li><a href="#" class="mpai-command-item" data-command="List all active memberships">List all active memberships</a></li>
                    <li><a href="#" class="mpai-command-item" data-command="Show recent transactions">Show recent transactions</a></li>
                    <li><a href="#" class="mpai-command-item" data-command="Summarize membership data">Summarize membership data</a></li>
                </ul>
            </div>
            <div class="mpai-command-list">
                <h5><?php esc_html_e('WordPress', 'memberpress-ai-assistant'); ?></h5>
                <ul>
                    <li><a href="#" class="mpai-command-item" data-command="wp plugin list">wp plugin list</a></li>
                    <li><a href="#" class="mpai-command-item" data-command="wp user list">wp user list</a></li>
                    <li><a href="#" class="mpai-command-item" data-command="wp post list">wp post list</a></li>
                </ul>
                
                <h5><?php esc_html_e('Content Creation', 'memberpress-ai-assistant'); ?></h5>
                <ul>
                    <li><a href="#" class="mpai-command-item" data-command="Create a blog post about">Create a blog post</a></li>
                    <li><a href="#" class="mpai-command-item" data-command="Create a page about">Create a page</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Chat toggle button (fixed position) -->
<button id="mpai-chat-toggle" class="mpai-chat-toggle" aria-label="<?php esc_attr_e('Toggle chat', 'memberpress-ai-assistant'); ?>">
    <span class="dashicons dashicons-format-chat"></span>
</button>

<!-- Add direct script loading for blog formatter -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('[MPAI Debug] DOM content loaded, checking for blog formatter');
        if (window.MPAI_BlogFormatter) {
            console.log('[MPAI Debug] Blog formatter found, initializing');
            window.MPAI_BlogFormatter.init();
        } else {
            console.log('[MPAI Debug] Blog formatter not found, loading directly');
            // Create script element
            var script = document.createElement('script');
            script.src = '<?php echo esc_url(MPAI_PLUGIN_URL . 'assets/js/blog-formatter.js'); ?>';
            script.onload = function() {
                console.log('[MPAI Debug] Blog formatter loaded directly, initializing');
                if (window.MPAI_BlogFormatter) {
                    window.MPAI_BlogFormatter.init();
                }
            };
            document.head.appendChild(script);
        }
    });
</script>