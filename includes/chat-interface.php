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

// Check if user has consented to terms and conditions
$user_id = get_current_user_id();
$has_consented = get_user_meta($user_id, 'mpai_has_consented', true);
if (!$has_consented) {
    // Don't render the chat interface, but output a message about consent if debug is enabled
    if (defined('WP_DEBUG') && WP_DEBUG) {
        ?>
        <script>
        console.log('MPAI: Chat interface not rendered - user has not consented to terms and conditions');
        </script>
        <?php
    }
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
            <button id="mpai-chat-expand" class="mpai-chat-action-button mpai-chat-expand" title="Expand">
                <span class="dashicons dashicons-editor-expand"></span>
            </button>
            <button id="mpai-chat-minimize" class="mpai-chat-action-button mpai-chat-minimize" title="Minimize">
                <span class="dashicons dashicons-minus"></span>
            </button>
            <button id="mpai-chat-close" class="mpai-chat-action-button mpai-chat-close" title="Close">
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
        <div id="mpai-command-runner" class="mpai-command-runner" style="display: none;">
            <div class="mpai-command-header">
                <h4><?php esc_html_e('Common Commands', 'memberpress-ai-assistant'); ?></h4>
                <button type="button" id="mpai-command-close" class="mpai-command-close">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
            <div class="mpai-command-body">
                <div class="mpai-command-list">
                    <h5><?php esc_html_e('WordPress', 'memberpress-ai-assistant'); ?></h5>
                    <ul>
                        <li><a href="#" class="mpai-command-item" data-command="wp plugin list">wp plugin list</a></li>
                        <li><a href="#" class="mpai-command-item" data-command="wp user list">wp user list</a></li>
                        <li><a href="#" class="mpai-command-item" data-command="wp post list">wp post list</a></li>
                    </ul>
                    
                    <h5><?php esc_html_e('MemberPress', 'memberpress-ai-assistant'); ?></h5>
                    <ul>
                        <li><a href="#" class="mpai-command-item" data-command="List all active memberships">List all active memberships</a></li>
                        <li><a href="#" class="mpai-command-item" data-command="Show recent transactions">Show recent transactions</a></li>
                        <li><a href="#" class="mpai-command-item" data-command="Summarize membership data">Summarize membership data</a></li>
                    </ul>
                </div>
            </div>
        </div>
        
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
            <div class="mpai-chat-actions-group">
                <button id="mpai-run-command" class="mpai-run-command" title="<?php esc_attr_e('Run Command', 'memberpress-ai-assistant'); ?>">
                    <span class="dashicons dashicons-admin-tools"></span>
                </button>
                <button id="mpai-export-chat" class="mpai-export-chat" title="<?php esc_attr_e('Export Conversation', 'memberpress-ai-assistant'); ?>">
                    <span class="dashicons dashicons-download"></span>
                </button>
                <button id="mpai-chat-clear" class="mpai-chat-clear">
                    <?php esc_html_e('Clear conversation', 'memberpress-ai-assistant'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<div id="mpai-chat-toggle" class="mpai-chat-toggle <?php echo esc_attr($position_class); ?>">
    <span class="dashicons dashicons-format-chat"></span>
</div>