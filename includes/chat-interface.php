<?php
/**
 * The chat interface template for the MemberPress AI Assistant.
 *
 * @package    MemberPress_AI_Assistant
 * @subpackage MemberPress_AI_Assistant/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Check if AI Assistant is enabled
if (get_option('mpai_enable_chat', 1) != 1) {
    return;
}

// Check if should only show on MemberPress pages
if (get_option('mpai_show_on_all_pages', 1) != 1) {
    $screen = get_current_screen();
    if (!$screen || (strpos($screen->id, 'memberpress') === false && strpos($screen->id, 'mepr') === false)) {
        return;
    }
}

// Get chat position
$position = get_option('mpai_chat_position', 'bottom-right');
$position_class = 'mpai-chat-' . $position;

// Get welcome message
$welcome_message = get_option('mpai_welcome_message', 'Hi there! I\'m your MemberPress AI Assistant. How can I help you today?');
?>

<div id="mpai-chat-container" class="mpai-chat-container <?php echo esc_attr($position_class); ?>">
    <div class="mpai-chat-header">
        <div class="mpai-chat-logo">
            <img src="<?php echo esc_url(MPAI_PLUGIN_URL . 'assets/images/memberpress-logo.svg'); ?>" alt="MemberPress">
        </div>
        <div class="mpai-chat-title">
            <?php esc_html_e('MemberPress AI Assistant', 'memberpress-ai-assistant'); ?>
        </div>
        <div class="mpai-chat-actions">
            <button id="mpai-chat-minimize" class="mpai-chat-action-button mpai-chat-minimize">
                <span class="dashicons dashicons-minus"></span>
            </button>
            <button id="mpai-chat-close" class="mpai-chat-action-button mpai-chat-close">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
    </div>
    
    <div class="mpai-chat-body">
        <div id="mpai-chat-messages" class="mpai-chat-messages">
            <div class="mpai-chat-message mpai-chat-message-assistant">
                <div class="mpai-chat-message-content">
                    <?php echo wp_kses_post($welcome_message); ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="mpai-chat-footer">
        <form id="mpai-chat-form" class="mpai-chat-form">
            <div class="mpai-chat-input-container">
                <textarea id="mpai-chat-input" class="mpai-chat-input" placeholder="<?php esc_attr_e('Type your message here...', 'memberpress-ai-assistant'); ?>" rows="1"></textarea>
                <button type="submit" id="mpai-chat-submit" class="mpai-chat-submit">
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </button>
            </div>
        </form>
        <div class="mpai-chat-footer-info">
            <span class="mpai-chat-branding">
                <?php esc_html_e('Powered by MemberPress AI', 'memberpress-ai-assistant'); ?>
            </span>
            <button id="mpai-chat-clear" class="mpai-chat-clear">
                <?php esc_html_e('Clear conversation', 'memberpress-ai-assistant'); ?>
            </button>
        </div>
    </div>
</div>

<div id="mpai-chat-toggle" class="mpai-chat-toggle <?php echo esc_attr($position_class); ?>">
    <span class="dashicons dashicons-format-chat"></span>
</div>