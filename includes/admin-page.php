<?php
/**
 * Admin Page
 *
 * Displays the main admin page for MemberPress AI Assistant
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get stats
$api = new MPAI_MemberPress_API();
$stats = $api->get_data_summary();
?>

<div class="wrap mpai-admin-page">
    <h1><?php _e('MemberPress AI Assistant', 'memberpress-ai-assistant'); ?></h1>
    
    <?php if (empty(get_option('mpai_api_key'))) : ?>
        <div class="notice notice-warning">
            <p><?php _e('Please configure your API key in the settings page before using the AI assistant.', 'memberpress-ai-assistant'); ?></p>
            <p><a href="<?php echo admin_url('admin.php?page=memberpress-ai-assistant-settings'); ?>" class="button button-primary"><?php _e('Go to Settings', 'memberpress-ai-assistant'); ?></a></p>
        </div>
    <?php endif; ?>
    
    <div class="mpai-welcome-container">
        <div class="mpai-welcome-card">
            <h2><?php _e('Welcome to MemberPress AI Assistant', 'memberpress-ai-assistant'); ?></h2>
            <p><?php _e('The AI assistant is available via the chat bubble in the bottom-right corner of your screen (or wherever you positioned it in settings).', 'memberpress-ai-assistant'); ?></p>
            <p><?php _e('You can use it to ask questions about your MemberPress site, get insights, and run commands.', 'memberpress-ai-assistant'); ?></p>
            
            <div class="mpai-welcome-buttons">
                <button id="mpai-open-chat" class="button button-primary"><?php _e('Open Chat', 'memberpress-ai-assistant'); ?></button>
                <a href="<?php echo admin_url('admin.php?page=memberpress-ai-assistant-settings'); ?>" class="button"><?php _e('Settings', 'memberpress-ai-assistant'); ?></a>
            </div>
        </div>
        
        <div class="mpai-stats-card">
            <h2><?php _e('MemberPress Stats', 'memberpress-ai-assistant'); ?></h2>
            <div class="mpai-stats-grid">
                <div class="mpai-stat-item">
                    <span class="mpai-stat-value"><?php echo isset($stats['total_members']) ? esc_html($stats['total_members']) : '0'; ?></span>
                    <span class="mpai-stat-label"><?php _e('Members', 'memberpress-ai-assistant'); ?></span>
                </div>
                <div class="mpai-stat-item">
                    <span class="mpai-stat-value"><?php echo isset($stats['total_memberships']) ? esc_html($stats['total_memberships']) : '0'; ?></span>
                    <span class="mpai-stat-label"><?php _e('Memberships', 'memberpress-ai-assistant'); ?></span>
                </div>
                <div class="mpai-stat-item">
                    <span class="mpai-stat-value"><?php echo isset($stats['transaction_count']) ? esc_html($stats['transaction_count']) : '0'; ?></span>
                    <span class="mpai-stat-label"><?php _e('Transactions', 'memberpress-ai-assistant'); ?></span>
                </div>
                <div class="mpai-stat-item">
                    <span class="mpai-stat-value"><?php echo isset($stats['subscription_count']) ? esc_html($stats['subscription_count']) : '0'; ?></span>
                    <span class="mpai-stat-label"><?php _e('Subscriptions', 'memberpress-ai-assistant'); ?></span>
                </div>
            </div>
        </div>
        
        <div class="mpai-help-card">
            <h2><?php _e('Example Questions', 'memberpress-ai-assistant'); ?></h2>
            <p><?php _e('Here are some questions you can ask the AI assistant:', 'memberpress-ai-assistant'); ?></p>
            <ul class="mpai-example-questions">
                <li><a href="#" class="mpai-example-question"><?php _e('How many new members joined this month?', 'memberpress-ai-assistant'); ?></a></li>
                <li><a href="#" class="mpai-example-question"><?php _e('What is the best selling membership?', 'memberpress-ai-assistant'); ?></a></li>
                <li><a href="#" class="mpai-example-question"><?php _e('Show me active subscriptions', 'memberpress-ai-assistant'); ?></a></li>
                <li><a href="#" class="mpai-example-question"><?php _e('What WP-CLI commands can I use for MemberPress?', 'memberpress-ai-assistant'); ?></a></li>
            </ul>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Handle opening the chat
    $('#mpai-open-chat').on('click', function() {
        // Trigger the chat to open by simulating a click on the chat toggle
        $('#mpai-chat-toggle').click();
    });
    
    // Handle example question clicks
    $('.mpai-example-question').on('click', function(e) {
        e.preventDefault();
        
        // Get the question
        var question = $(this).text();
        
        // Open the chat if it's not already open
        if (!$('#mpai-chat-container').is(':visible')) {
            $('#mpai-chat-toggle').click();
        }
        
        // Set the question in the input
        setTimeout(function() {
            $('#mpai-chat-input').val(question);
            
            // Trigger the form submission
            $('#mpai-chat-form').submit();
        }, 500);
    });
});
</script>

<style>
.mpai-welcome-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    grid-gap: 20px;
    margin-top: 20px;
}

.mpai-welcome-card,
.mpai-stats-card,
.mpai-help-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.mpai-welcome-card {
    grid-column: 1 / 3;
}

.mpai-welcome-buttons {
    margin-top: 20px;
}

.mpai-stats-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    grid-gap: 15px;
    margin-top: 15px;
}

.mpai-stat-item {
    text-align: center;
    padding: 15px;
    background: #f7f7f7;
    border-radius: 5px;
}

.mpai-stat-value {
    display: block;
    font-size: 24px;
    font-weight: 600;
    color: #135e96;
}

.mpai-stat-label {
    font-size: 14px;
    color: #555;
}

.mpai-example-questions {
    margin-top: 15px;
}

.mpai-example-questions li {
    margin-bottom: 10px;
}

.mpai-example-question {
    text-decoration: none;
}

@media (max-width: 782px) {
    .mpai-welcome-container {
        grid-template-columns: 1fr;
    }
    
    .mpai-welcome-card {
        grid-column: 1;
    }
}
</style>