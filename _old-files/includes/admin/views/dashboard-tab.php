<?php
/**
 * Dashboard Tab Content
 * 
 * @package MemberPress AI Assistant
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="mpai-dashboard-grid">
    <div class="mpai-dashboard-card mpai-card-primary">
        <h2><?php _e('Quick Actions', 'memberpress-ai-assistant'); ?></h2>
        <ul class="mpai-action-buttons">
            <li>
                <a href="#" id="mpai-open-chat-button" class="button button-primary">
                    <span class="dashicons dashicons-format-chat"></span>
                    <?php _e('Open AI Chat', 'memberpress-ai-assistant'); ?>
                </a>
            </li>
            <li>
                <a href="<?php echo admin_url('admin.php?page=memberpress-ai-assistant&tab=general'); ?>" class="button">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <?php _e('API Settings', 'memberpress-ai-assistant'); ?>
                </a>
            </li>
            <li>
                <a href="<?php echo admin_url('admin.php?page=memberpress-ai-assistant&tab=chat'); ?>" class="button">
                    <span class="dashicons dashicons-admin-customizer"></span>
                    <?php _e('Chat Settings', 'memberpress-ai-assistant'); ?>
                </a>
            </li>
            <li>
                <a href="<?php echo admin_url('admin.php?page=memberpress-ai-assistant&tab=debug'); ?>" class="button">
                    <span class="dashicons dashicons-admin-tools"></span>
                    <?php _e('Debug Tools', 'memberpress-ai-assistant'); ?>
                </a>
            </li>
        </ul>
    </div>
    
    <div class="mpai-dashboard-card">
        <h2><?php _e('Usage Tips', 'memberpress-ai-assistant'); ?></h2>
        <ul class="mpai-tips-list">
            <li><a href="#" class="mpai-suggestion"><?php _e('What are my top-selling memberships?', 'memberpress-ai-assistant'); ?></a></li>
            <li><a href="#" class="mpai-suggestion"><?php _e('Show me some useful WP-CLI commands for managing users', 'memberpress-ai-assistant'); ?></a></li>
            <li><a href="#" class="mpai-suggestion"><?php _e('Help me configure MemberPress payment settings', 'memberpress-ai-assistant'); ?></a></li>
            <li><a href="#" class="mpai-suggestion"><?php _e('How do I troubleshoot membership access issues?', 'memberpress-ai-assistant'); ?></a></li>
        </ul>
    </div>
    
    <div class="mpai-dashboard-card">
        <h2><?php _e('Status', 'memberpress-ai-assistant'); ?></h2>
        <div class="mpai-status-grid">
            <div class="mpai-status-item">
                <span class="mpai-status-label"><?php _e('API Connection:', 'memberpress-ai-assistant'); ?></span>
                <span class="mpai-status-value mpai-status-good" id="mpai-api-connection-status">
                    <?php 
                    $primary_api = get_option('mpai_primary_api', 'openai');
                    $api_key = ($primary_api == 'openai') ? 
                        get_option('mpai_api_key', '') : 
                        get_option('mpai_anthropic_api_key', '');
                    
                    if (!empty($api_key)) {
                        echo '<span class="dashicons dashicons-yes-alt"></span> ' . esc_html__('Connected', 'memberpress-ai-assistant');
                    } else {
                        echo '<span class="dashicons dashicons-warning"></span> ' . esc_html__('Not Configured', 'memberpress-ai-assistant');
                    }
                    ?>
                </span>
            </div>
            <div class="mpai-status-item">
                <span class="mpai-status-label"><?php _e('MemberPress:', 'memberpress-ai-assistant'); ?></span>
                <span class="mpai-status-value mpai-status-<?php echo mpai_is_memberpress_active() ? 'good' : 'bad'; ?>">
                    <?php 
                    if (mpai_is_memberpress_active()) {
                        echo '<span class="dashicons dashicons-yes-alt"></span> ' . esc_html__('Detected', 'memberpress-ai-assistant');
                    } else {
                        echo '<span class="dashicons dashicons-warning"></span> ' . esc_html__('Not Detected', 'memberpress-ai-assistant');
                    }
                    ?>
                </span>
            </div>
            <div class="mpai-status-item">
                <span class="mpai-status-label"><?php _e('Debug Mode:', 'memberpress-ai-assistant'); ?></span>
                <span class="mpai-status-value">
                    <?php 
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        echo '<span class="dashicons dashicons-info"></span> ' . esc_html__('Enabled', 'memberpress-ai-assistant');
                    } else {
                        echo '<span class="dashicons dashicons-yes-alt"></span> ' . esc_html__('Disabled', 'memberpress-ai-assistant');
                    }
                    ?>
                </span>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Handle "Open AI Chat" button click
    $('#mpai-open-chat-button').on('click', function(e) {
        e.preventDefault();
        // Trigger the floating chat interface toggle button if it exists
        if ($('#mpai-chat-toggle').length) {
            $('#mpai-chat-toggle').click();
        } else {
            alert('Chat interface is not available. Please check your settings.');
        }
    });
    
    // Handle clickable suggestion links
    $('.mpai-suggestion').on('click', function(e) {
        e.preventDefault();
        // Get the suggestion text
        var message = $(this).text();
        
        // Check if there's a chat interface
        if ($('#mpai-chat-toggle').length) {
            // Open the chat if it's not already open
            if (!$('#mpai-chat-container').is(':visible')) {
                $('#mpai-chat-toggle').click();
            }
            
            // Insert the suggestion into the chat input and submit
            setTimeout(function() {
                $('#mpai-chat-input').val(message);
                $('#mpai-chat-form').submit();
            }, 300); // Short delay to ensure the chat is open
        } else {
            alert('Chat interface is not available. Please check your settings.');
        }
    });
});
</script>