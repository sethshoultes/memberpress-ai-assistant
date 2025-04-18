<?php
/**
 * Consent Form Template
 * 
 * @package MemberPress AI Assistant
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

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
                    <input type="checkbox" name="mpai_consent" id="mpai-consent-checkbox" value="1">
                    <?php _e('I agree to the terms and conditions of using the MemberPress AI Assistant', 'memberpress-ai-assistant'); ?>
                </label>
                
                <p id="mpai-welcome-buttons" class="consent-required">
                    <input type="submit" name="mpai_save_consent" id="mpai-open-chat" class="button button-primary" value="<?php esc_attr_e('Get Started', 'memberpress-ai-assistant'); ?>" disabled>
                    <a href="#" id="mpai-terms-link" class="button"><?php _e('Review Full Terms', 'memberpress-ai-assistant'); ?></a>
                </p>
            </form>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Handle consent checkbox changes
    $('#mpai-consent-checkbox').on('change', function() {
        if ($(this).is(':checked')) {
            $('#mpai-open-chat').prop('disabled', false);
            $('#mpai-welcome-buttons').removeClass('consent-required');
        } else {
            $('#mpai-open-chat').prop('disabled', true);
            $('#mpai-welcome-buttons').addClass('consent-required');
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
});
</script>

<style>
/* Consent Form Styles */
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