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

// Get stats and check if MemberPress is available
$api = new MPAI_MemberPress_API();
$has_memberpress = $api->is_memberpress_available();
$stats = $api->get_data_summary();
?>

<div class="wrap mpai-admin-page">
    <h1><?php _e('MemberPress AI Assistant', 'memberpress-ai-assistant'); ?></h1>
    
    <!-- Direct console logging test script -->
    <script>
    // These console messages should appear in the browser console regardless of any plugin JS
    console.log('🔴 DIRECT TEST: This message should appear in the console');
    console.error('🔴 DIRECT TEST: This error message should appear in red');
    console.warn('🔴 DIRECT TEST: This warning message should appear in yellow');
    
    // Removed interval test that was logging every 5 seconds
    
    // Add a test button directly in the admin page
    document.addEventListener('DOMContentLoaded', function() {
        var testButton = document.createElement('button');
        testButton.className = 'button';
        testButton.innerText = 'Test Console (Direct)';
        testButton.style.marginBottom = '10px';
        testButton.addEventListener('click', function() {
            console.group('🔵 Direct Console Test from Button');
            console.log('Button clicked at ' + new Date().toISOString());
            console.log('Test Object:', { test: 'value', number: 123 });
            console.error('Test Error Message');
            console.warn('Test Warning Message');
            console.groupEnd();
            alert('Test logs sent to console - check developer tools (F12)');
        });
        
        document.querySelector('.mpai-admin-page h1').after(testButton);
    });
    </script>
    
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
            <p><?php _e('You can use it to ask questions about your site, get insights, and run commands.', 'memberpress-ai-assistant'); ?></p>
            
            <div class="mpai-consent-container">
                <?php
                // Check if user has already consented
                $user_id = get_current_user_id();
                $has_consented = get_user_meta($user_id, 'mpai_has_consented', true);
                ?>
                <label>
                    <input type="checkbox" id="mpai-consent-checkbox" class="mpai-consent-checkbox" <?php checked($has_consented, true); ?>>
                    <?php _e('I agree to the <a href="#" id="mpai-terms-link">MemberPress AI Terms & Conditions</a>', 'memberpress-ai-assistant'); ?>
                </label>
                
                <div class="mpai-consent-notice">
                    <p><strong><?php _e('Important Notice:', 'memberpress-ai-assistant'); ?></strong> <?php _e('The MemberPress AI Assistant is an AI-powered tool. While it strives for accuracy, it may occasionally provide incorrect or incomplete information. Always verify important information before taking action.', 'memberpress-ai-assistant'); ?></p>
                </div>
            </div>
            
            <div class="mpai-welcome-buttons <?php echo !$has_consented ? 'consent-required' : ''; ?>" id="mpai-welcome-buttons">
                <button id="mpai-open-chat" class="button button-primary" <?php disabled(!$has_consented); ?>><?php _e('Open Chat', 'memberpress-ai-assistant'); ?></button>
                <a href="<?php echo admin_url('admin.php?page=memberpress-ai-assistant-settings'); ?>" class="button"><?php _e('Settings', 'memberpress-ai-assistant'); ?></a>
            </div>
        </div>
        
        <?php if (!$has_memberpress): ?>
        <div class="mpai-upsell-section">
            <h2><?php _e('Supercharge Your Membership Business with MemberPress', 'memberpress-ai-assistant'); ?></h2>
            <div class="mpai-upsell-columns">
                <div class="mpai-upsell-column">
                    <h3><?php _e('Why Choose MemberPress?', 'memberpress-ai-assistant'); ?></h3>
                    <ul>
                        <li><?php _e('Create unlimited membership levels', 'memberpress-ai-assistant'); ?></li>
                        <li><?php _e('Protect your valuable content', 'memberpress-ai-assistant'); ?></li>
                        <li><?php _e('Accept payments with multiple gateways', 'memberpress-ai-assistant'); ?></li>
                        <li><?php _e('Generate detailed reports', 'memberpress-ai-assistant'); ?></li>
                        <li><?php _e('Automate emails and drip content', 'memberpress-ai-assistant'); ?></li>
                        <li><?php _e('Unlock advanced AI features', 'memberpress-ai-assistant'); ?></li>
                    </ul>
                    <p><a href="https://memberpress.com/plans/?utm_source=ai_assistant&utm_medium=admin&utm_campaign=upsell" class="button button-primary button-hero" target="_blank"><?php _e('Get MemberPress Now', 'memberpress-ai-assistant'); ?></a></p>
                </div>
                <div class="mpai-upsell-column">
                    <div class="mpai-upsell-logo">
                        <img src="<?php echo MPAI_PLUGIN_URL; ?>assets/images/memberpress-logo.svg" alt="MemberPress Logo" class="mpai-upsell-logo-img">
                    </div>
                    <div class="mpai-limited-features">
                        <h4><?php _e('With MemberPress, you\'ll be able to:', 'memberpress-ai-assistant'); ?></h4>
                        <div class="mpai-limited-feature-grid">
                            <div class="mpai-limited-feature-item">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <span><?php _e('View Member Analytics', 'memberpress-ai-assistant'); ?></span>
                            </div>
                            <div class="mpai-limited-feature-item">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <span><?php _e('Track Membership Sales', 'memberpress-ai-assistant'); ?></span>
                            </div>
                            <div class="mpai-limited-feature-item">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <span><?php _e('Manage Subscriptions', 'memberpress-ai-assistant'); ?></span>
                            </div>
                            <div class="mpai-limited-feature-item">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <span><?php _e('Get Revenue Reports', 'memberpress-ai-assistant'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
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
        <?php endif; ?>
        
        <div class="mpai-help-card">
            <h2><?php _e('Example Questions', 'memberpress-ai-assistant'); ?></h2>
            <p><?php _e('Here are some questions you can ask the AI assistant:', 'memberpress-ai-assistant'); ?></p>
            <ul class="mpai-example-questions">
                <?php if ($has_memberpress): ?>
                <li><a href="#" class="mpai-example-question"><?php _e('How many new members joined this month?', 'memberpress-ai-assistant'); ?></a></li>
                <li><a href="#" class="mpai-example-question"><?php _e('What is the best selling membership?', 'memberpress-ai-assistant'); ?></a></li>
                <li><a href="#" class="mpai-example-question"><?php _e('Show me active subscriptions', 'memberpress-ai-assistant'); ?></a></li>
                <li><a href="#" class="mpai-example-question"><?php _e('What WP-CLI commands can I use for MemberPress?', 'memberpress-ai-assistant'); ?></a></li>
                <?php else: ?>
                <li><a href="#" class="mpai-example-question"><?php _e('What can I do with MemberPress?', 'memberpress-ai-assistant'); ?></a></li>
                <li><a href="#" class="mpai-example-question"><?php _e('Show me WordPress users', 'memberpress-ai-assistant'); ?></a></li>
                <li><a href="#" class="mpai-example-question"><?php _e('How can I create a membership site?', 'memberpress-ai-assistant'); ?></a></li>
                <li><a href="#" class="mpai-example-question"><?php _e('List installed plugins', 'memberpress-ai-assistant'); ?></a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Handle opening the chat
    $('#mpai-open-chat').on('click', function() {
        // Check if consent has been given
        var hasConsented = $('#mpai-consent-checkbox').prop('checked');
        
        if (!hasConsented) {
            // If not consented, show an alert
            alert('Please agree to the terms and conditions before using the AI Assistant.');
            return;
        }
        
        // If the chat elements don't exist yet (because page hasn't been refreshed after consent)
        if (!$('#mpai-chat-toggle').length) {
            // Reload the page to ensure the chat interface is properly loaded
            window.location.reload();
            return;
        }
        
        // Trigger the chat to open by simulating a click on the chat toggle
        $('#mpai-chat-toggle').click();
    });
    
    // Handle example question clicks
    $('.mpai-example-question').on('click', function(e) {
        e.preventDefault();
        
        // Check if consent has been given
        var hasConsented = $('#mpai-consent-checkbox').prop('checked');
        
        if (!hasConsented) {
            // If not consented, show an alert
            alert('Please agree to the terms and conditions before using the AI Assistant.');
            return;
        }
        
        // If the chat elements don't exist yet (because page hasn't been refreshed after consent)
        if (!$('#mpai-chat-toggle').length || !$('#mpai-chat-container').length) {
            // Reload the page to ensure the chat interface is properly loaded
            window.location.reload();
            return;
        }
        
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

/* Upsell Styles */
.mpai-upsell-section {
    background-color: #f8f9fa;
    border: 1px solid #dcdcde;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 30px;
    grid-column: 1 / 3;
}

.mpai-upsell-columns {
    display: flex;
    flex-wrap: wrap;
    gap: 30px;
}

.mpai-upsell-column {
    flex: 1;
    min-width: 300px;
}

.mpai-upsell-column h3 {
    margin-top: 0;
}

.mpai-upsell-column ul {
    list-style-type: disc;
    padding-left: 20px;
}

.mpai-upsell-logo {
    text-align: center;
    margin-bottom: 20px;
}

.mpai-upsell-logo-img {
    max-width: 200px;
    height: auto;
}

.mpai-limited-features h4 {
    margin-top: 0;
    margin-bottom: 15px;
}

.mpai-limited-feature-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.mpai-limited-feature-item {
    display: flex;
    align-items: center;
}

.mpai-limited-feature-item .dashicons {
    color: #2271b1;
    margin-right: 8px;
}

@media (max-width: 782px) {
    .mpai-welcome-container {
        grid-template-columns: 1fr;
    }
    
    .mpai-welcome-card, .mpai-upsell-section {
        grid-column: 1;
    }
    
    .mpai-limited-feature-grid {
        grid-template-columns: 1fr;
    }
}
</style>