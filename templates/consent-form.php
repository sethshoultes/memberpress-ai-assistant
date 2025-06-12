<?php
/**
 * MemberPress AI Assistant Consent Form Template
 *
 * This template displays the consent form for users to agree to the terms
 * of using the MemberPress AI Assistant.
 *
 * @package MemberpressAiAssistant
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Note: WordPress functions are called directly in this template.
// IDE may show errors for these functions, but they will work correctly
// in a WordPress environment where these functions are globally available.

// Get current user ID
$user_id = get_current_user_id();

// Create nonce for form submission
$nonce = wp_create_nonce('mpai_consent_nonce');
?>

<div class="mpai-consent-form-container wrap">
    <h1><?php _e('MemberPress AI Assistant - Consent Form', 'memberpress-ai-assistant'); ?></h1>
    
    <?php
    // Display any admin notices
    settings_errors('mpai_messages');
    ?>
    
    <div class="mpai-consent-form-content">
        <p class="mpai-intro">
            <?php _e('Before using the MemberPress AI Assistant, please review and agree to the following terms:', 'memberpress-ai-assistant'); ?>
        </p>
        
        <div class="mpai-consent-terms card">
            <h2><?php _e('Terms of Use', 'memberpress-ai-assistant'); ?></h2>
            
            <div class="mpai-terms-section">
                <h3><?php _e('Data Access and Analysis', 'memberpress-ai-assistant'); ?></h3>
                <p>
                    <?php _e('By using the MemberPress AI Assistant, you acknowledge and agree that the AI will access and analyze your MemberPress data, including but not limited to membership information, transaction records, subscription details, and user data.', 'memberpress-ai-assistant'); ?>
                </p>
            </div>
            
            <div class="mpai-terms-section">
                <h3><?php _e('Privacy and Data Handling', 'memberpress-ai-assistant'); ?></h3>
                <p>
                    <?php _e('All data processed by the MemberPress AI Assistant is subject to the privacy policies of the AI service providers. While we take reasonable measures to protect your data, please be aware that information processed by third-party AI services may be stored temporarily on their servers.', 'memberpress-ai-assistant'); ?>
                </p>
            </div>
            
            <div class="mpai-terms-section">
                <h3><?php _e('Accuracy of Information', 'memberpress-ai-assistant'); ?></h3>
                <p>
                    <?php _e('The MemberPress AI Assistant uses advanced AI technology to provide assistance and recommendations. However, the AI may occasionally provide incomplete or inaccurate information. Always verify important information and use your judgment when implementing AI recommendations.', 'memberpress-ai-assistant'); ?>
                </p>
            </div>
            
            <div class="mpai-terms-section">
                <h3><?php _e('Limitation of Liability', 'memberpress-ai-assistant'); ?></h3>
                <p>
                    <?php _e('MemberPress is not liable for any actions taken based on AI recommendations or for any direct, indirect, incidental, or consequential damages arising from the use of the AI Assistant. You are solely responsible for decisions made based on the information provided by the AI.', 'memberpress-ai-assistant'); ?>
                </p>
            </div>
        </div>
        
        <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=mpai-settings')); ?>" id="mpai-consent-form" class="mpai-consent-form">
            <?php wp_nonce_field('mpai_consent_nonce', 'mpai_consent_nonce'); ?>
            
            <div class="mpai-consent-checkbox">
                <label for="mpai-consent" id="mpai-consent-label">
                    <input type="checkbox" name="mpai_consent" id="mpai-consent" value="1" />
                    <span class="mpai-checkbox-text"><?php _e('I have read and agree to the terms of use for the MemberPress AI Assistant', 'memberpress-ai-assistant'); ?></span>
                </label>
            </div>
            
            <div class="mpai-consent-actions">
                <input type="hidden" name="mpai_save_consent" value="1" />
                <button type="submit" id="mpai-submit-consent" class="button button-primary" disabled>
                    <?php _e('Agree and Continue', 'memberpress-ai-assistant'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    .mpai-consent-form-container {
        max-width: 800px;
        margin: 20px auto;
    }
    
    .mpai-consent-form-content {
        margin-top: 20px;
    }
    
    .mpai-intro {
        font-size: 16px;
        margin-bottom: 20px;
    }
    
    .mpai-consent-terms {
        background-color: #fff;
        padding: 20px;
        margin-bottom: 20px;
        border-radius: 5px;
        border: 1px solid #ddd;
    }
    
    .mpai-terms-section {
        margin-bottom: 20px;
    }
    
    .mpai-terms-section h3 {
        margin-top: 0;
        margin-bottom: 10px;
        font-size: 16px;
    }
    
    .mpai-consent-checkbox {
        margin: 20px 0;
    }
    
    .mpai-consent-checkbox label {
        display: flex;
        align-items: flex-start;
    }
    
    .mpai-consent-checkbox input {
        margin-top: 3px;
        margin-right: 10px;
    }
    
    .mpai-consent-actions {
        margin-top: 20px;
    }
    
    .consent-required .button-primary {
        opacity: 0.7;
        cursor: not-allowed;
    }
    
    .mpai-checkbox-text {
        flex: 1;
    }
    
    #mpai-consent-label {
        display: flex;
        align-items: center;
        font-size: 15px;
        font-weight: 500;
        cursor: pointer;
    }
    
    #mpai-consent {
        margin-right: 10px;
        transform: scale(1.2);
    }
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    console.log('Consent form script loaded');
    
    // Function to update button state
    function updateButtonState(isChecked) {
        console.log('Updating button state. Checked:', isChecked);
        if (isChecked) {
            $('#mpai-submit-consent').prop('disabled', false).removeClass('disabled');
            $('.mpai-consent-actions').removeClass('consent-required');
        } else {
            $('#mpai-submit-consent').prop('disabled', true).addClass('disabled');
            $('.mpai-consent-actions').addClass('consent-required');
        }
    }
    
    // Handle consent checkbox changes
    $('#mpai-consent').on('change', function() {
        updateButtonState($(this).is(':checked'));
    });
    
    // Also handle clicks on the label
    $('#mpai-consent-label').on('click', function(e) {
        // Don't handle if the click was directly on the checkbox (it will trigger the change event)
        if (e.target.id !== 'mpai-consent') {
            e.preventDefault();
            var checkbox = $('#mpai-consent');
            checkbox.prop('checked', !checkbox.is(':checked')).trigger('change');
        }
    });
    
    // Handle form submission
    $('form').on('submit', function(e) {
        if (!$('#mpai-consent').is(':checked')) {
            e.preventDefault();
            alert('<?php echo esc_js(__('Please agree to the terms and conditions before proceeding.', 'memberpress-ai-assistant')); ?>');
            return false;
        }
        return true;
    });
});
</script>