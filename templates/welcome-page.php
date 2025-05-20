<?php
/**
 * MemberPress AI Assistant Welcome Page Template
 *
 * This template displays the welcome and consent form for users when the plugin is activated.
 *
 * @package MemberpressAiAssistant
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current user ID
$user_id = get_current_user_id();

// Create nonce for form submission
$nonce = wp_create_nonce('mpai_consent_nonce');
?>

<div class="mpai-welcome-container wrap">
    <h1><?php _e('Welcome to MemberPress AI Assistant', 'memberpress-ai-assistant'); ?></h1>
    
    <?php
    // Display any admin notices
    settings_errors('mpai_messages');
    ?>
    
    <div class="mpai-welcome-content">
        <p class="mpai-intro">
            <?php _e('MemberPress AI Assistant leverages artificial intelligence to help you manage your membership site more effectively. Before you begin, please review and agree to the terms of use.', 'memberpress-ai-assistant'); ?>
        </p>
        
        <div class="mpai-terms-box">
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
                    <?php _e('Information processed by the AI is subject to the privacy policies of our AI providers (OpenAI and Anthropic).', 'memberpress-ai-assistant'); ?>
                </p>
            </div>
            
            <div class="mpai-terms-section">
                <h3><?php _e('Accuracy of Information', 'memberpress-ai-assistant'); ?></h3>
                <p>
                    <?php _e('The AI may occasionally provide incomplete or inaccurate information. Always verify important recommendations.', 'memberpress-ai-assistant'); ?>
                </p>
            </div>
            
            <div class="mpai-terms-section">
                <h3><?php _e('Limitation of Liability', 'memberpress-ai-assistant'); ?></h3>
                <p>
                    <?php _e('MemberPress is not liable for any actions taken based on AI recommendations.', 'memberpress-ai-assistant'); ?>
                </p>
            </div>
        </div>
        
        <div id="mpai-consent-form" class="mpai-consent-form">
            <div class="mpai-consent-checkbox">
                <label for="mpai-consent">
                    <input type="checkbox" name="mpai_consent" id="mpai-consent" value="1" />
                    <span><?php _e('I agree to the terms and conditions of using the MemberPress AI Assistant', 'memberpress-ai-assistant'); ?></span>
                </label>
            </div>
            
            <div class="mpai-welcome-buttons">
                <button type="button" id="mpai-submit-consent" class="button button-primary" disabled>
                    <?php _e('Get Started', 'memberpress-ai-assistant'); ?>
                </button>
                <a href="#" id="mpai-terms-link" class="button">
                    <?php _e('Review Full Terms', 'memberpress-ai-assistant'); ?>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modal for full terms -->
<div id="mpai-terms-modal" class="mpai-terms-modal" style="display: none;">
    <div class="mpai-terms-modal-content">
        <h2><?php _e('MemberPress AI Terms & Conditions', 'memberpress-ai-assistant'); ?></h2>
        <div class="mpai-terms-content">
            <p><?php _e('By using the MemberPress AI Assistant, you agree to the following terms:', 'memberpress-ai-assistant'); ?></p>
            <ol>
                <li><?php _e('The AI Assistant is provided "as is" without warranties of any kind.', 'memberpress-ai-assistant'); ?></li>
                <li><?php _e('The AI may occasionally provide incorrect or incomplete information.', 'memberpress-ai-assistant'); ?></li>
                <li><?php _e('You are responsible for verifying any information provided by the AI.', 'memberpress-ai-assistant'); ?></li>
                <li><?php _e('MemberPress is not liable for any actions taken based on AI recommendations.', 'memberpress-ai-assistant'); ?></li>
                <li><?php _e('Your interactions with the AI Assistant may be logged for training and improvement purposes.', 'memberpress-ai-assistant'); ?></li>
            </ol>
            <p><?php _e('For complete terms, please refer to the MemberPress Terms of Service.', 'memberpress-ai-assistant'); ?></p>
        </div>
        <button type="button" class="button button-primary mpai-close-modal">
            <?php _e('Close', 'memberpress-ai-assistant'); ?>
        </button>
    </div>
</div>

<style>
    .mpai-welcome-container {
        max-width: 800px;
        margin: 40px auto;
        background: #fff;
        padding: 30px;
        border-radius: 5px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .mpai-welcome-content {
        margin-top: 20px;
    }
    
    .mpai-intro {
        font-size: 16px;
        margin-bottom: 20px;
    }
    
    .mpai-terms-box {
        background: #f9f9f9;
        border: 1px solid #e5e5e5;
        border-radius: 4px;
        padding: 20px;
        margin: 20px 0;
        max-height: 300px;
        overflow-y: auto;
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
        padding: 15px;
        background: #f9f9f9;
        border: 1px solid #e5e5e5;
        border-radius: 4px;
    }
    
    .mpai-consent-checkbox label {
        display: flex;
        align-items: center;
        font-size: 15px;
        cursor: pointer;
    }
    
    .mpai-consent-checkbox input {
        margin-right: 10px;
    }
    
    .mpai-welcome-buttons {
        margin-top: 20px;
        display: flex;
        gap: 10px;
    }
    
    .mpai-welcome-buttons .button-primary:disabled {
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

<script type="text/javascript">
    (function() {
        // Get DOM elements
        const consentCheckbox = document.getElementById('mpai-consent');
        const submitButton = document.getElementById('mpai-submit-consent');
        const termsLink = document.getElementById('mpai-terms-link');
        const termsModal = document.getElementById('mpai-terms-modal');
        const closeModalButton = document.querySelector('.mpai-close-modal');
        
        console.log('Consent form script initialized');
        
        // Function to toggle submit button state
        function toggleSubmitButton() {
            if (consentCheckbox.checked) {
                submitButton.removeAttribute('disabled');
                console.log('Submit button enabled');
            } else {
                submitButton.setAttribute('disabled', 'disabled');
                console.log('Submit button disabled');
            }
        }
        
        // Add event listener to checkbox
        consentCheckbox.addEventListener('change', toggleSubmitButton);
        
        // Initialize button state
        toggleSubmitButton();
        
        // Submit button click handler - use AJAX instead of form submission
        submitButton.addEventListener('click', function() {
            console.log('Submit button clicked');
            
            if (!consentCheckbox.checked) {
                alert('<?php echo esc_js(__('Please agree to the terms to continue.', 'memberpress-ai-assistant')); ?>');
                console.log('Submission prevented - checkbox not checked');
                return;
            }
            
            console.log('Sending AJAX request to save consent');
            
            // Create AJAX request
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>');
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onload = function() {
                console.log('AJAX response received:', xhr.responseText);
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            console.log('Consent saved successfully, redirecting...');
                            window.location.href = '<?php echo admin_url('admin.php?page=mpai-settings'); ?>';
                        } else {
                            console.error('Error saving consent:', response.data.message);
                            alert('Error: ' + response.data.message);
                        }
                    } catch (e) {
                        console.error('Error parsing AJAX response:', e);
                        alert('An error occurred while processing your consent. Please try again.');
                    }
                } else {
                    console.error('AJAX request failed with status:', xhr.status);
                    alert('An error occurred while processing your consent. Please try again.');
                }
            };
            
            xhr.onerror = function() {
                console.error('AJAX request failed');
                alert('An error occurred while processing your consent. Please try again.');
            };
            
            // Prepare data
            const data = 'action=mpai_save_consent' +
                         '&mpai_consent=1' +
                         '&mpai_consent_nonce=<?php echo esc_js($nonce); ?>';
            
            // Send request
            xhr.send(data);
        });
        
        // Terms link handling
        termsLink.addEventListener('click', function(event) {
            event.preventDefault();
            termsModal.style.display = 'block';
        });
        
        // Close modal button
        closeModalButton.addEventListener('click', function() {
            termsModal.style.display = 'none';
        });
        
        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === termsModal) {
                termsModal.style.display = 'none';
            }
        });
    })();
</script>