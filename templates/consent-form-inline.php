<?php
/**
 * Inline Consent Form Template for Settings Page Integration
 *
 * This template displays a compact consent form directly on the settings page
 * when users haven't consented yet. It uses AJAX to submit consent and 
 * dynamically replaces itself with the chat interface.
 *
 * @package MemberpressAiAssistant
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current user ID
$user_id = get_current_user_id();

// Create nonce for AJAX submission
$nonce = wp_create_nonce('mpai_consent_nonce');
?>

<div class="mpai-inline-consent-container" id="mpai-inline-consent-container">
    <div class="mpai-inline-consent-header">
        <h3><?php _e('MemberPress AI Assistant', 'memberpress-ai-assistant'); ?></h3>
        <p class="mpai-inline-consent-intro">
            <?php _e('To use the AI Assistant, please review and agree to our terms of use.', 'memberpress-ai-assistant'); ?>
        </p>
    </div>
    
    <div class="mpai-inline-consent-content">
        <div class="mpai-inline-terms-summary">
            <h4><?php _e('Terms Summary', 'memberpress-ai-assistant'); ?></h4>
            <ul class="mpai-terms-list">
                <li><?php _e('The AI will access and analyze your MemberPress data to provide assistance', 'memberpress-ai-assistant'); ?></li>
                <li><?php _e('Data is processed by third-party AI services with appropriate privacy measures', 'memberpress-ai-assistant'); ?></li>
                <li><?php _e('AI recommendations should be verified and used with your judgment', 'memberpress-ai-assistant'); ?></li>
                <li><?php _e('You are responsible for decisions made based on AI information', 'memberpress-ai-assistant'); ?></li>
            </ul>
            <p class="mpai-terms-link">
                <a href="<?php echo esc_url(admin_url('admin.php?page=mpai-welcome')); ?>" target="_blank">
                    <?php _e('View full terms and conditions', 'memberpress-ai-assistant'); ?>
                </a>
            </p>
        </div>
        
        <form id="mpai-inline-consent-form" class="mpai-inline-consent-form">
            <div class="mpai-inline-consent-checkbox">
                <label for="mpai-inline-consent" class="mpai-inline-consent-label">
                    <input type="checkbox" name="mpai_consent" id="mpai-inline-consent" value="1" />
                    <span class="mpai-checkbox-text">
                        <?php _e('I agree to the terms of use for the MemberPress AI Assistant', 'memberpress-ai-assistant'); ?>
                    </span>
                </label>
            </div>
            
            <div class="mpai-inline-consent-actions">
                <button type="submit" id="mpai-inline-submit-consent" class="button button-primary" disabled>
                    <span class="mpai-button-text"><?php _e('Enable AI Assistant', 'memberpress-ai-assistant'); ?></span>
                    <span class="mpai-button-spinner" style="display: none;">
                        <span class="spinner is-active"></span>
                    </span>
                </button>
            </div>
            
            <div class="mpai-inline-consent-messages" id="mpai-inline-consent-messages" style="display: none;">
                <!-- Success/error messages will be displayed here -->
            </div>
        </form>
    </div>
</div>

<style>
    .mpai-inline-consent-container {
        background: #fff;
        border: 1px solid #c3c4c7;
        border-radius: 4px;
        padding: 20px;
        margin: 20px 0;
        box-shadow: 0 1px 1px rgba(0,0,0,.04);
    }
    
    .mpai-inline-consent-header h3 {
        margin: 0 0 10px 0;
        font-size: 18px;
        color: #1d2327;
    }
    
    .mpai-inline-consent-intro {
        margin: 0 0 15px 0;
        color: #646970;
        font-size: 14px;
    }
    
    .mpai-inline-terms-summary {
        background: #f6f7f7;
        border: 1px solid #dcdcde;
        border-radius: 4px;
        padding: 15px;
        margin-bottom: 15px;
    }
    
    .mpai-inline-terms-summary h4 {
        margin: 0 0 10px 0;
        font-size: 14px;
        font-weight: 600;
        color: #1d2327;
    }
    
    .mpai-terms-list {
        margin: 0 0 10px 0;
        padding-left: 20px;
    }
    
    .mpai-terms-list li {
        margin-bottom: 5px;
        font-size: 13px;
        color: #50575e;
        line-height: 1.4;
    }
    
    .mpai-terms-link {
        margin: 10px 0 0 0;
        font-size: 12px;
    }
    
    .mpai-terms-link a {
        color: #2271b1;
        text-decoration: none;
    }
    
    .mpai-terms-link a:hover {
        color: #135e96;
        text-decoration: underline;
    }
    
    .mpai-inline-consent-form {
        margin: 0;
    }
    
    .mpai-inline-consent-checkbox {
        margin: 15px 0;
    }
    
    .mpai-inline-consent-label {
        display: flex;
        align-items: flex-start;
        cursor: pointer;
        font-size: 14px;
        line-height: 1.4;
    }
    
    .mpai-inline-consent-label input[type="checkbox"] {
        margin: 2px 8px 0 0;
        transform: scale(1.1);
    }
    
    .mpai-checkbox-text {
        flex: 1;
        color: #1d2327;
    }
    
    .mpai-inline-consent-actions {
        margin: 15px 0 0 0;
    }
    
    .mpai-inline-consent-actions .button {
        position: relative;
        min-width: 140px;
    }
    
    .mpai-inline-consent-actions .button:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    
    .mpai-button-spinner {
        position: absolute;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%);
    }
    
    .mpai-button-spinner .spinner {
        float: none;
        margin: 0;
        width: 16px;
        height: 16px;
    }
    
    .mpai-inline-consent-messages {
        margin: 15px 0 0 0;
        padding: 10px;
        border-radius: 4px;
    }
    
    .mpai-inline-consent-messages.success {
        background: #d1e7dd;
        border: 1px solid #badbcc;
        color: #0f5132;
    }
    
    .mpai-inline-consent-messages.error {
        background: #f8d7da;
        border: 1px solid #f5c2c7;
        color: #842029;
    }
    
    /* Loading state */
    .mpai-inline-consent-container.loading .mpai-button-text {
        opacity: 0;
    }
    
    .mpai-inline-consent-container.loading .mpai-button-spinner {
        display: block;
    }
    
    /* Fade out animation for successful consent */
    .mpai-inline-consent-container.fade-out {
        opacity: 0;
        transition: opacity 0.3s ease-out;
    }
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    console.log('Inline consent form script loaded');
    
    var $container = $('#mpai-inline-consent-container');
    var $form = $('#mpai-inline-consent-form');
    var $checkbox = $('#mpai-inline-consent');
    var $submitButton = $('#mpai-inline-submit-consent');
    var $messages = $('#mpai-inline-consent-messages');
    
    // Function to update button state
    function updateButtonState(isChecked) {
        if (isChecked) {
            $submitButton.prop('disabled', false);
        } else {
            $submitButton.prop('disabled', true);
        }
    }
    
    // Handle consent checkbox changes
    $checkbox.on('change', function() {
        updateButtonState($(this).is(':checked'));
    });
    
    // Handle label clicks
    $('.mpai-inline-consent-label').on('click', function(e) {
        if (e.target.type !== 'checkbox') {
            e.preventDefault();
            $checkbox.prop('checked', !$checkbox.is(':checked')).trigger('change');
        }
    });
    
    // Function to show message
    function showMessage(message, type) {
        $messages.removeClass('success error').addClass(type).text(message).show();
    }
    
    // Function to hide message
    function hideMessage() {
        $messages.hide();
    }
    
    // Function to set loading state
    function setLoadingState(loading) {
        if (loading) {
            $container.addClass('loading');
            $submitButton.prop('disabled', true);
        } else {
            $container.removeClass('loading');
            updateButtonState($checkbox.is(':checked'));
        }
    }
    
    // Function to load required assets
    function loadAssets(assets, callback) {
        var loadedCount = 0;
        var totalAssets = 0;
        
        // Count total assets
        if (assets.css) {
            totalAssets += Object.keys(assets.css).length;
        }
        if (assets.js) {
            totalAssets += Object.keys(assets.js).length;
        }
        
        function assetLoaded() {
            loadedCount++;
            if (loadedCount >= totalAssets) {
                callback();
            }
        }
        
        // Load CSS files
        if (assets.css) {
            Object.keys(assets.css).forEach(function(key) {
                var link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = assets.css[key];
                link.onload = assetLoaded;
                link.onerror = assetLoaded; // Continue even if CSS fails to load
                document.head.appendChild(link);
            });
        }
        
        // Load JS files
        if (assets.js) {
            Object.keys(assets.js).forEach(function(key) {
                var script = document.createElement('script');
                script.src = assets.js[key];
                script.onload = assetLoaded;
                script.onerror = assetLoaded; // Continue even if JS fails to load
                document.head.appendChild(script);
            });
        }
        
        // If no assets to load, call callback immediately
        if (totalAssets === 0) {
            callback();
        }
    }
    
    // Function to load chat interface
    // DIAGNOSTIC: Log consent validation process

    LoggingUtility::debug('[CHAT RENDER DIAGNOSIS] Starting consent validation', [

        'user_id' => $user_id,

        'consent_manager_available' => class_exists('\MemberpressAiAssistant\Admin\MPAIConsentManager')

    ]);

    

    // CRITICAL FIX: Check consent status BEFORE rendering
        
        // Make AJAX request to get chat interface HTML
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mpai_get_chat_interface',
                nonce: '<?php echo esc_js($nonce); ?>'
            },
            success: function(response) {

                // DIAGNOSTIC: Log AJAX response for chat interface

                console.log('[CHAT RENDER DIAGNOSIS] AJAX response received for chat interface', {

                    response: response,

                    hasHtml: !!(response.data && response.data.html),

                    htmlLength: response.data && response.data.html ? response.data.html.length : 0,

                    hasAssets: !!(response.data && response.data.assets),

                    timestamp: new Date().toISOString()

                });

                

                if (response.success && response.data.html) {
                    console.log('Chat interface loaded successfully');
                    
                    // Load required assets first
                    if (response.data.assets) {
                        console.log('Loading chat interface assets...');
                        loadAssets(response.data.assets, function() {
                            console.log('Assets loaded, initializing chat interface...');
                            initializeChatInterface(response.data);
                        });
                    } else {
                        // No assets to load, initialize directly
                        initializeChatInterface(response.data);
                    }
                } else {
                    console.error('Failed to load chat interface:', response);
                    showMessage('<?php echo esc_js(__('Failed to load chat interface. Please refresh the page.', 'memberpress-ai-assistant')); ?>', 'error');
                    setLoadingState(false);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error loading chat interface:', error);
                showMessage('<?php echo esc_js(__('Network error. Please try again.', 'memberpress-ai-assistant')); ?>', 'error');
                setLoadingState(false);
            }
        });
    }
    
    // Function to initialize chat interface after assets are loaded
    function initializeChatInterface(data) {
        // CRITICAL FIX: Instead of replacing, just show the existing chat container
        console.log('[CHAT RENDER DIAGNOSIS] Showing existing chat container after consent');
        
        // Find the existing chat container that was rendered server-side
        var $chatContainer = $('#mpai-chat-container');
        
        if ($chatContainer.length > 0) {
            console.log('[CHAT RENDER DIAGNOSIS] Found existing chat container, making it visible');
            
            // Fade out consent form
            $container.addClass('fade-out');
            
            setTimeout(function() {
                // Hide consent form and show chat container
                $container.hide();
                $chatContainer.show();
                
                console.log('[CHAT RENDER DIAGNOSIS] Chat container is now visible');
                
                // Set up chat configuration if provided
                if (data && data.config) {
                    window.mpai_chat_config = data.config;
                }
                
                // Wait a bit for DOM to settle, then initialize chat
                setTimeout(function() {
                    // Try to initialize chat interface
                    if (typeof window.MPAI_Chat !== 'undefined' && window.MPAI_Chat.init) {
                        console.log('Initializing MPAI_Chat...');
                        window.MPAI_Chat.init();
                    } else {
                        console.log('MPAI_Chat not available, will be initialized by template script');
                    }
                    
                    console.log('Chat interface initialization complete');
                }, 500);
            }, 300);
        } else {
            console.error('[CHAT RENDER DIAGNOSIS] Chat container not found in DOM - falling back to AJAX replacement');
            
            // Fallback to the original replacement method if container not found
            $container.addClass('fade-out');
            
            setTimeout(function() {
                if (data && data.html) {
                    $container.replaceWith(data.html);
                    
                    if (data.config) {
                        window.mpai_chat_config = data.config;
                    }
                    
                    setTimeout(function() {
                        if (typeof window.MPAI_Chat !== 'undefined' && window.MPAI_Chat.init) {
                            console.log('Initializing MPAI_Chat...');
                            window.MPAI_Chat.init();
                        }
                        console.log('Chat interface initialization complete');
                    }, 500);
                }
            }, 300);
        }
    }
    
    // Handle form submission
    $form.on('submit', function(e) {
        e.preventDefault();
        
        if (!$checkbox.is(':checked')) {
            showMessage('<?php echo esc_js(__('Please agree to the terms before proceeding.', 'memberpress-ai-assistant')); ?>', 'error');
            return;
        }
        
        hideMessage();
        setLoadingState(true);
        
        console.log('Submitting consent via AJAX...');
        
        // Submit consent via AJAX
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mpai_save_consent',
                consent: true,
                mpai_consent_nonce: '<?php echo esc_js($nonce); ?>',
                nonce: '<?php echo esc_js(wp_create_nonce('mpai_nonce')); ?>'
            },
            success: function(response) {
                console.log('Consent submission response:', response);
                
                if (response.success) {

                
                    // DIAGNOSTIC: Log successful consent submission

                
                    console.log('[CHAT RENDER DIAGNOSIS] Consent saved successfully, preparing to load chat interface', {

                
                        response: response,

                
                        timestamp: new Date().toISOString()

                
                    });

                
                    

                
                    showMessage('<?php echo esc_js(__('Thank you! Loading AI Assistant...', 'memberpress-ai-assistant')); ?>', 'success');
                    
                    // Load chat interface after short delay
                    setTimeout(function() {
                        loadChatInterface();
                    }, 1000);
                } else {
                    console.error('Consent submission failed:', response);
                    showMessage(response.data || '<?php echo esc_js(__('Failed to save consent. Please try again.', 'memberpress-ai-assistant')); ?>', 'error');
                    setLoadingState(false);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error submitting consent:', error);
                showMessage('<?php echo esc_js(__('Network error. Please try again.', 'memberpress-ai-assistant')); ?>', 'error');
                setLoadingState(false);
            }
        });
    });
    
    // Initialize button state
    updateButtonState($checkbox.is(':checked'));
});
</script>