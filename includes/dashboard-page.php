<?php
/**
 * Dashboard/Welcome Page
 *
 * Displays the main dashboard for MemberPress AI Assistant
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Log page load for debugging
error_log('MPAI DEBUG: Dashboard page is being loaded');

// Check if terms have been accepted
$consent_given = get_option('mpai_consent_given', false);
?>

<div class="wrap mpai-dashboard-page">
    <h1><?php _e('MemberPress AI Assistant', 'memberpress-ai-assistant'); ?></h1>
    
    <?php
    // Show a success message if they've just given consent (from redirect)
    if (isset($_GET['consent']) && $_GET['consent'] == 'given'): ?>
    <div class="notice notice-success is-dismissible">
        <p><strong><?php _e('Success!', 'memberpress-ai-assistant'); ?></strong> <?php _e('Thank you for agreeing to the terms. You can now use the MemberPress AI Assistant.', 'memberpress-ai-assistant'); ?></p>
    </div>
    <?php endif; ?>
    
    <?php if (!$consent_given): ?>
    <!-- Opt-in/Consent Section - Only shown if consent hasn't been given -->
    <div class="mpai-welcome-section mpai-consent-section">
        <h2><?php _e('Welcome to MemberPress AI Assistant', 'memberpress-ai-assistant'); ?></h2>
        
        <div class="mpai-welcome-content">
            <p><?php _e('MemberPress AI Assistant leverages artificial intelligence to help you manage your membership site more effectively. Before you begin, please review and agree to the terms of use.', 'memberpress-ai-assistant'); ?></p>
            
            <div class="mpai-terms-box">
                <h3><?php _e('Terms of Use', 'memberpress-ai-assistant'); ?></h3>
                <ul>
                    <li><?php _e('This AI assistant will access and analyze your MemberPress data to provide insights and recommendations.', 'memberpress-ai-assistant'); ?></li>
                    <li><?php _e('Information processed by the AI is subject to the privacy policies of our AI providers (OpenAI and Anthropic).', 'memberpress-ai-assistant'); ?></li>
                    <li><?php _e('The AI may occasionally provide incomplete or inaccurate information. Always verify important recommendations.', 'memberpress-ai-assistant'); ?></li>
                    <li><?php _e('MemberPress is not liable for any actions taken based on AI recommendations.', 'memberpress-ai-assistant'); ?></li>
                </ul>
            </div>
            
            <div class="mpai-consent-form">
                <form method="post" action="">
                    <?php wp_nonce_field('mpai_consent_nonce', 'mpai_consent_nonce'); ?>
                    <label id="mpai-consent-label">
                        <input type="checkbox" name="mpai_consent" id="mpai-consent-checkbox" value="1" <?php checked(get_option('mpai_consent_given', false)); ?> <?php echo get_option('mpai_consent_given', false) ? 'readonly disabled' : ''; ?> />
                        <?php _e('I agree to the terms and conditions of using the MemberPress AI Assistant', 'memberpress-ai-assistant'); ?>
                    </label>
                    <?php if (get_option('mpai_consent_given', false)): ?>
                    <p class="description" style="color: #46b450;">
                        <span class="dashicons dashicons-yes-alt"></span> 
                        <?php _e('You have already agreed to the terms. This agreement will persist until the plugin is deactivated.', 'memberpress-ai-assistant'); ?>
                    </p>
                    <?php endif; ?>
                    <p id="mpai-welcome-buttons" class="<?php echo get_option('mpai_consent_given', false) ? '' : 'consent-required'; ?>">
                        <input type="submit" name="mpai_save_consent" id="mpai-open-chat" class="button button-primary" value="<?php esc_attr_e('Get Started', 'memberpress-ai-assistant'); ?>" <?php echo get_option('mpai_consent_given', false) ? '' : 'disabled'; ?> />
                        <a href="#" id="mpai-terms-link" class="button"><?php _e('Review Full Terms', 'memberpress-ai-assistant'); ?></a>
                    </p>
                </form>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- Dashboard for users who have already given consent -->
    <div class="mpai-dashboard-grid">
        <div class="mpai-dashboard-card mpai-card-primary">
            <h2><?php _e('Quick Actions', 'memberpress-ai-assistant'); ?></h2>
            <ul class="mpai-action-buttons">
                <li>
                    <a href="admin.php?page=memberpress-ai-assistant-settings" class="button button-primary">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <?php _e('Configure Settings', 'memberpress-ai-assistant'); ?>
                    </a>
                </li>
                <li>
                    <a href="#" id="mpai-open-chat-button" class="button button-primary">
                        <span class="dashicons dashicons-format-chat"></span>
                        <?php _e('Open AI Chat', 'memberpress-ai-assistant'); ?>
                    </a>
                </li>
                <li>
                    <a href="admin.php?page=memberpress-ai-assistant-settings#tab-diagnostic" class="button">
                        <span class="dashicons dashicons-chart-bar"></span>
                        <?php _e('Run Diagnostics', 'memberpress-ai-assistant'); ?>
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="mpai-dashboard-card">
            <h2><?php _e('Usage Tips', 'memberpress-ai-assistant'); ?></h2>
            <ul class="mpai-tips-list">
                <li><?php _e('Ask the AI about your membership data, like "What are my top-selling memberships?"', 'memberpress-ai-assistant'); ?></li>
                <li><?php _e('Get recommendations for WP-CLI commands to manage your site', 'memberpress-ai-assistant'); ?></li>
                <li><?php _e('Ask for help with configuring MemberPress settings', 'memberpress-ai-assistant'); ?></li>
                <li><?php _e('Troubleshoot issues with your membership site', 'memberpress-ai-assistant'); ?></li>
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
                    <span class="mpai-status-value mpai-status-<?php echo class_exists('MeprAppCtrl') ? 'good' : 'bad'; ?>">
                        <?php 
                        if (class_exists('MeprAppCtrl')) {
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
    <?php endif; ?>
</div>

<style>
/* Dashboard Styles */
.mpai-dashboard-page {
    max-width: 1200px;
    margin: 20px auto;
}

.mpai-welcome-section {
    background: #fff;
    border-radius: 5px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    padding: 25px;
    margin-bottom: 25px;
}

.mpai-terms-box {
    background: #f9f9f9;
    border: 1px solid #e5e5e5;
    border-radius: 4px;
    padding: 15px 20px;
    margin: 20px 0;
    max-height: 200px;
    overflow-y: auto;
}

.mpai-consent-form {
    margin-top: 25px;
}

#mpai-consent-label {
    font-size: 15px;
    font-weight: 500;
}

.consent-required .button-primary {
    opacity: 0.7;
    cursor: not-allowed;
}

.mpai-dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.mpai-dashboard-card {
    background: #fff;
    border-radius: 5px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    padding: 20px;
}

.mpai-card-primary {
    grid-column: 1 / -1;
}

.mpai-action-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin: 0;
    padding: 0;
    list-style: none;
}

.mpai-action-buttons li .button {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 8px 15px;
    height: auto;
}

.mpai-tips-list {
    margin: 0;
    padding-left: 20px;
}

.mpai-tips-list li {
    margin-bottom: 10px;
}

.mpai-status-grid {
    display: grid;
    gap: 12px;
}

.mpai-status-item {
    display: flex;
    justify-content: space-between;
    border-bottom: 1px solid #f0f0f0;
    padding-bottom: 8px;
}

.mpai-status-value {
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 5px;
}

.mpai-status-good {
    color: #46b450;
}

.mpai-status-bad {
    color: #dc3232;
}

@media (max-width: 782px) {
    .mpai-dashboard-grid {
        grid-template-columns: 1fr;
    }
    
    .mpai-action-buttons {
        flex-direction: column;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Check if consent checkbox is already checked on page load
    if ($('#mpai-consent-checkbox').is(':checked')) {
        $('#mpai-open-chat').prop('disabled', false);
        $('#mpai-welcome-buttons').removeClass('consent-required');
    }
    
    // Handle consent checkbox
    $('#mpai-consent-checkbox').on('change', function() {
        if ($(this).is(':checked')) {
            $('#mpai-open-chat').prop('disabled', false);
            $('#mpai-welcome-buttons').removeClass('consent-required');
        } else {
            $('#mpai-open-chat').prop('disabled', true);
            $('#mpai-welcome-buttons').addClass('consent-required');
        }
    });
    
    // If the checkbox is disabled (which means consent was previously given),
    // make sure the button is enabled
    if ($('#mpai-consent-checkbox').prop('disabled')) {
        $('#mpai-open-chat').prop('disabled', false);
        $('#mpai-welcome-buttons').removeClass('consent-required');
    }
    
    // Handle terms link click
    $('#mpai-terms-link').on('click', function(e) {
        e.preventDefault();
        
        // Create modal if it doesn't exist
        if (!$('#mpai-terms-modal').length) {
            var $modal = $('<div>', {
                id: 'mpai-terms-modal',
                class: 'mpai-terms-modal'
            }).appendTo('body');
            
            var $modalContent = $('<div>', {
                class: 'mpai-terms-modal-content'
            }).appendTo($modal);
            
            $('<h2>').text('MemberPress AI Terms & Conditions').appendTo($modalContent);
            
            $('<div>', {
                class: 'mpai-terms-content'
            }).html(`
                <p>By using the MemberPress AI Assistant, you agree to the following terms:</p>
                <ol>
                    <li>The AI Assistant is provided "as is" without warranties of any kind.</li>
                    <li>The AI may occasionally provide incorrect or incomplete information.</li>
                    <li>You are responsible for verifying any information provided by the AI.</li>
                    <li>MemberPress is not liable for any actions taken based on AI recommendations.</li>
                    <li>Your interactions with the AI Assistant may be logged for training and improvement purposes.</li>
                </ol>
                <p>For complete terms, please refer to the MemberPress Terms of Service.</p>
            `).appendTo($modalContent);
            
            $('<button>', {
                class: 'button button-primary',
                text: 'Close'
            }).on('click', function() {
                $modal.hide();
            }).appendTo($modalContent);
        }
        
        $('#mpai-terms-modal').show();
    });
    
    // Handle "Open AI Chat" button click on dashboard page
    $('#mpai-open-chat-button').on('click', function(e) {
        e.preventDefault();
        // Trigger the floating chat interface toggle button if it exists
        if ($('#mpai-chat-toggle').length) {
            $('#mpai-chat-toggle').click();
        } else {
            alert('Chat interface is not available. Please check your settings.');
        }
    });
});
</script>

<style>
/* Modal Styles */
.mpai-terms-modal {
    display: none;
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.mpai-terms-modal-content {
    background-color: #fff;
    margin: 10% auto;
    padding: 30px;
    border-radius: 5px;
    max-width: 600px;
    max-height: 80vh;
    overflow-y: auto;
    position: relative;
}

.mpai-terms-content {
    margin-bottom: 20px;
}
</style>