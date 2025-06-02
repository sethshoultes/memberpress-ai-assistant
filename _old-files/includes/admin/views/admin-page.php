<?php
/**
 * Admin Page
 * 
 * Simple admin page without tabs
 *
 * @package MemberPress AI Assistant
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap mpai-admin-page">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php
    // Show success message if consent was just given
    if (isset($_GET['consent']) && $_GET['consent'] == 'given'): ?>
    <div class="notice notice-success is-dismissible">
        <p><strong><?php _e('Success!', 'memberpress-ai-assistant'); ?></strong> <?php _e('Thank you for agreeing to the terms. You can now use the MemberPress AI Assistant.', 'memberpress-ai-assistant'); ?></p>
    </div>
    <?php endif; ?>
    
    <?php
    // Show settings saved message
    if (isset($_GET['settings-updated']) && $_GET['settings-updated'] == true): ?>
    <div class="notice notice-success is-dismissible">
        <p><strong><?php _e('Settings saved successfully!', 'memberpress-ai-assistant'); ?></strong></p>
    </div>
    <?php endif; ?>
    
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
                    <a href="#" id="mpai-clear-chat-history" class="button">
                        <span class="dashicons dashicons-trash"></span>
                        <?php _e('Clear Chat History', 'memberpress-ai-assistant'); ?>
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="mpai-dashboard-card">
            <h2><?php _e('Status', 'memberpress-ai-assistant'); ?></h2>
            <div class="mpai-status-grid">
                <div class="mpai-status-item">
                    <span class="mpai-status-label"><?php _e('API Connection:', 'memberpress-ai-assistant'); ?></span>
                    <span class="mpai-status-value" id="mpai-api-connection-status">
                        <?php 
                        $primary_api = get_option('mpai_primary_api', 'openai');
                        $api_key = ($primary_api == 'openai') ? 
                            get_option('mpai_api_key', '') : 
                            get_option('mpai_anthropic_api_key', '');
                        
                        if (!empty($api_key)) {
                            echo '<span class="dashicons dashicons-yes-alt"></span> ' . esc_html__('Connected', 'memberpress-ai-assistant');
                            echo ' (' . esc_html(ucfirst($primary_api)) . ')';
                        } else {
                            echo '<span class="dashicons dashicons-warning"></span> ' . esc_html__('Not Configured', 'memberpress-ai-assistant');
                        }
                        ?>
                    </span>
                </div>
                <div class="mpai-status-item">
                    <span class="mpai-status-label"><?php _e('MemberPress:', 'memberpress-ai-assistant'); ?></span>
                    <span class="mpai-status-value mpai-status-<?php echo mpai_is_memberpress_active() ? 'good' : 'warning'; ?>">
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
                    <span class="mpai-status-label"><?php _e('Current Model:', 'memberpress-ai-assistant'); ?></span>
                    <span class="mpai-status-value">
                        <?php 
                        $primary_api = get_option('mpai_primary_api', 'openai');
                        $model = ($primary_api == 'openai') ? 
                            get_option('mpai_model', 'gpt-4o') : 
                            get_option('mpai_anthropic_model', 'claude-3-opus-20240229');
                            
                        echo '<span class="dashicons dashicons-admin-generic"></span> ' . esc_html($model);
                        ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Settings Form -->
    <div class="mpai-settings-section">
        <h2><?php _e('Settings', 'memberpress-ai-assistant'); ?></h2>
        <form method="post" action="options.php">
            <?php
            settings_fields('mpai_settings');
            do_settings_sections('mpai_settings');
            submit_button();
            ?>
        </form>
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
    
    // Note: Clear Chat History button is now handled in assets/js/admin.js
    // Removing duplicate handler here to prevent double alerts
});
</script>

<style>
/* Admin Page Styles */
.mpai-dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.mpai-dashboard-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.12);
    padding: 20px;
}

.mpai-card-primary {
    border-top: 4px solid #2271b1;
}

.mpai-action-buttons {
    list-style: none;
    padding: 0;
    margin: 15px 0 5px;
}

.mpai-action-buttons li {
    margin-bottom: 12px;
}

.mpai-action-buttons .button {
    display: inline-flex;
    align-items: center;
    min-width: 180px;
}

.mpai-action-buttons .dashicons {
    margin-right: 8px;
}

.mpai-status-grid {
    display: grid;
    gap: 15px;
}

.mpai-status-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 10px;
    border-bottom: 1px solid #f0f0f0;
}

.mpai-status-item:last-child {
    border-bottom: none;
}

.mpai-status-label {
    font-weight: 500;
}

.mpai-status-value {
    display: flex;
    align-items: center;
}

.mpai-status-value .dashicons {
    margin-right: 5px;
}

.mpai-status-value .dashicons-yes-alt {
    color: #46b450;
}

.mpai-status-value .dashicons-warning {
    color: #f56e28;
}

.mpai-settings-section {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.12);
    padding: 20px 25px;
}

.mpai-settings-section h2 {
    margin-top: 0;
    padding-bottom: 15px;
    border-bottom: 1px solid #f0f0f0;
}

.form-table th {
    padding: 20px 10px 20px 0;
}
</style>