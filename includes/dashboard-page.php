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

// Check if terms have been accepted - check both options and user meta
$consent_given = false;

// First check the global option
$global_consent = get_option('mpai_consent_given', false);

// Then check user-specific consent
if (!$global_consent && is_user_logged_in()) {
    $user_id = get_current_user_id();
    $user_consent = get_user_meta($user_id, 'mpai_has_consented', true);
    $consent_given = !empty($user_consent);
    
    // For debugging
    error_log('MPAI DEBUG: User consent status - User ID: ' . $user_id . ', Has consented: ' . ($consent_given ? 'Yes' : 'No'));
} else {
    $consent_given = $global_consent;
}
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
                    
                    <?php if ($consent_given): ?>
                    <!-- Hidden field to always send consent value even if checkbox is readonly -->
                    <input type="hidden" name="mpai_consent" value="1" />
                    <?php endif; ?>
                    
                    <label id="mpai-consent-label" class="<?php echo $consent_given ? 'consent-given' : ''; ?>">
                        <input type="checkbox" name="mpai_consent" id="mpai-consent-checkbox" value="1" 
                            <?php echo $consent_given ? 'checked="checked"' : ''; ?> 
                            <?php echo $consent_given ? 'readonly="readonly" onclick="return false;"' : ''; ?> 
                        />
                        <?php _e('I agree to the terms and conditions of using the MemberPress AI Assistant', 'memberpress-ai-assistant'); ?>
                    </label>
                    <?php if ($consent_given): ?>
                    <p class="description" style="color: #46b450;">
                        <span class="dashicons dashicons-yes-alt"></span> 
                        <?php _e('You have already agreed to the terms. This agreement will persist until the plugin is deactivated.', 'memberpress-ai-assistant'); ?>
                    </p>
                    <?php endif; ?>
                    <p id="mpai-welcome-buttons" class="<?php echo $consent_given ? '' : 'consent-required'; ?>">
                        <input type="submit" name="mpai_save_consent" id="mpai-open-chat" class="button button-primary" value="<?php esc_attr_e('Get Started', 'memberpress-ai-assistant'); ?>" <?php echo $consent_given ? '' : 'disabled'; ?> />
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
                <!-- Diagnostic button has been removed -->
                <li>
                    <a href="admin.php?page=memberpress-ai-assistant-settings#tab-advanced" class="button">
                        <span class="dashicons dashicons-admin-tools"></span>
                        <?php _e('Advanced Settings', 'memberpress-ai-assistant'); ?>
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

#mpai-consent-label.consent-given {
    color: #46b450;
    font-weight: 600;
}

/* Style for readonly checkbox */
#mpai-consent-checkbox[readonly] {
    opacity: 0.7;
    cursor: not-allowed;
    pointer-events: none;
}

/* Make sure the checkbox appears checked when readonly */
#mpai-consent-checkbox[readonly]:before {
    content: '';
    display: block;
    width: 6px;
    height: 12px;
    border: solid #46b450;
    border-width: 0 2px 2px 0;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -75%) rotate(45deg);
}

/* Additional styles for Firefox */
@-moz-document url-prefix() {
    #mpai-consent-checkbox[readonly] {
        background-color: #f6f7f7 !important;
        box-shadow: 0 0 0 1px #8c8f94;
        outline: 2px solid transparent;
    }
}

/* Make consent checkbox disabled and checked visually obvious */
.consent-given #mpai-consent-checkbox {
    background-color: rgba(70, 180, 80, 0.1) !important;
    border-color: #46b450 !important;
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

.mpai-suggestion {
    color: #135e96;
    text-decoration: none;
    transition: color 0.2s;
    display: inline-block;
    cursor: pointer;
    padding: 2px 0;
    border-bottom: 1px dotted #ccc;
}

.mpai-suggestion:hover {
    color: #0073aa;
    border-bottom-color: #0073aa;
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
    // Function to check if consent was previously given
    function wasConsentGiven() {
        // Get the checkbox "checked" attribute
        var isChecked = $('#mpai-consent-checkbox').prop('checked');
        var hasCheckedAttr = $('#mpai-consent-checkbox').attr('checked') === 'checked';
        
        // Look for the "already agreed" message as another indicator
        var hasConsentMessage = $('.mpai-consent-form .description').length > 0;
        
        // Check for readonly attribute which indicates consent was given
        var isReadonly = $('#mpai-consent-checkbox').attr('readonly') === 'readonly';
        
        // Check if the label has the consent-given class
        var hasConsentClass = $('#mpai-consent-label').hasClass('consent-given');
        
        // Check for the hidden consent field
        var hasHiddenField = $('input[type="hidden"][name="mpai_consent"]').length > 0;
        
        console.log('MPAI DEBUG: Consent state - isChecked:', isChecked, 
                    'hasCheckedAttr:', hasCheckedAttr, 
                    'hasConsentMessage:', hasConsentMessage,
                    'isReadonly:', isReadonly,
                    'hasConsentClass:', hasConsentClass,
                    'hasHiddenField:', hasHiddenField);
        
        // Save to session storage to persist between page refreshes
        if (isChecked || hasCheckedAttr || hasConsentMessage || isReadonly || hasConsentClass || hasHiddenField) {
            try {
                sessionStorage.setItem('mpai_consent_given', 'true');
                console.log('MPAI DEBUG: Saved consent state to session storage');
            } catch (e) {
                console.error('MPAI DEBUG: Failed to save consent to session storage:', e);
            }
        }
        
        // Check session storage as a final fallback
        var sessionConsent = false;
        try {
            sessionConsent = sessionStorage.getItem('mpai_consent_given') === 'true';
        } catch (e) {
            console.error('MPAI DEBUG: Failed to read consent from session storage:', e);
        }
        
        return isChecked || hasCheckedAttr || hasConsentMessage || isReadonly || hasConsentClass || hasHiddenField || sessionConsent;
    }
    
    // When DOM is fully loaded, check all our consent indicators
    $(window).on('load', function() {
        // Check session storage first
        var sessionConsent = false;
        try {
            sessionConsent = sessionStorage.getItem('mpai_consent_given') === 'true';
            console.log('MPAI DEBUG: Session storage consent:', sessionConsent);
        } catch (e) {
            console.error('MPAI DEBUG: Error reading session storage:', e);
        }
        
        // Force the checkbox to be checked if any consent indicator is present
        var consentGiven = wasConsentGiven() || sessionConsent || 
                          $('#mpai-consent-checkbox').attr('readonly') || 
                          $('.consent-given').length > 0;
        
        if (consentGiven) {
            // Force the checkbox to remain checked
            $('#mpai-consent-checkbox').prop('checked', true);
            
            // Enable the button
            $('#mpai-open-chat').prop('disabled', false);
            $('#mpai-welcome-buttons').removeClass('consent-required');
            
            console.log('MPAI DEBUG: Force-enabled consent because indicators show consent was given');
            
            // Persist this in session storage
            try {
                sessionStorage.setItem('mpai_consent_given', 'true');
            } catch (e) {
                console.error('MPAI DEBUG: Error writing to session storage:', e);
            }
        }
    });
    
    // Immediate check as well
    if (wasConsentGiven() || $('#mpai-consent-checkbox').attr('readonly')) {
        $('#mpai-consent-checkbox').prop('checked', true);
        $('#mpai-open-chat').prop('disabled', false);
        $('#mpai-welcome-buttons').removeClass('consent-required');
        console.log('MPAI DEBUG: Enabling button because consent indicators are present');
    }
    
    // Handle consent checkbox - prevent unchecking if it should be read-only
    $('#mpai-consent-checkbox').on('click', function(e) {
        if ($(this).attr('readonly') || $(this).hasClass('readonly')) {
            e.preventDefault();
            return false;
        }
    });
    
    // Handle consent checkbox changes
    $('#mpai-consent-checkbox').on('change', function() {
        // If the checkbox has the readonly attribute, it should always remain checked
        if ($(this).attr('readonly') || $(this).hasClass('readonly')) {
            $(this).prop('checked', true);
            console.log('MPAI DEBUG: Preventing readonly checkbox from being unchecked');
            return;
        }
        
        if ($(this).is(':checked')) {
            $('#mpai-open-chat').prop('disabled', false);
            $('#mpai-welcome-buttons').removeClass('consent-required');
            
            // Ensure mpai_data object exists
            if (typeof mpai_data === 'undefined') {
                console.warn('MPAI DEBUG: mpai_data is undefined, creating fallback');
                window.mpai_data = {
                    ajax_url: '/wp-admin/admin-ajax.php',
                    nonce: $('#mpai_consent_nonce').val()
                };
            }
            
            // Save consent to server when checked
            var $checkbox = $(this); // Save checkbox reference for later use in callbacks
            
            $.ajax({
                url: mpai_data.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'mpai_save_consent',
                    nonce: mpai_data.nonce || $('#mpai_consent_nonce').val(), // Use either mpai_data nonce or the form nonce
                    consent: true
                },
                success: function(response) {
                    if (response.success) {
                        console.log('MPAI: Consent saved successfully');
                        
                        // Alternative: Submit the form for server-side processing
                        var $form = $checkbox.closest('form');
                        if ($form.length) {
                            $form.submit();
                            return;
                        }
                        
                        // Reload the page to reflect the saved consent state
                        window.location.reload();
                    } else {
                        console.error('MPAI: Error saving consent:', response.data);
                        // Still try to submit the form even if AJAX fails
                        $checkbox.closest('form').submit();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('MPAI: AJAX error when saving consent:', status, error);
                    // As a fallback, submit the form for server-side processing
                    $checkbox.closest('form').submit();
                }
            });
        } else {
            // If they try to uncheck, prevent it - consent can only be revoked by deactivating the plugin
            $(this).prop('checked', true);
            alert('Consent can only be revoked by deactivating the plugin.');
        }
    });
    
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
    
    // Handle clickable suggestion links
    $('.mpai-suggestion').on('click', function(e) {
        e.preventDefault();
        // Get the suggestion text
        var message = $(this).text();
        console.log('MPAI: Suggestion clicked: ' + message);
        
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