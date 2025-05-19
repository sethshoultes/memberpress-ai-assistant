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
        </div>
    </div>
</div>

<!-- Chat toggle button (fixed position) -->
<button id="mpai-chat-toggle" class="mpai-chat-toggle" aria-label="<?php esc_attr_e('Toggle chat', 'memberpress-ai-assistant'); ?>">
    <span class="dashicons dashicons-format-chat"></span>
</button>