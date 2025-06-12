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
    <?php
    // Display any admin notices
    settings_errors('mpai_messages');
    ?>
    
    <div class="mpai-welcome-content">
        <?php
        // Add comprehensive logging for consent form rendering
        error_log('[MPAI Debug] Welcome page: About to render consent form via consent manager');
        
        // Use the consent manager to render the consent form
        $consent_manager = \MemberpressAiAssistant\Admin\MPAIConsentManager::getInstance();
        
        // Log consent manager state
        error_log('[MPAI Debug] Welcome page: Consent manager instance created');
        error_log('[MPAI Debug] Welcome page: User consent status: ' . ($consent_manager->hasUserConsented() ? 'true' : 'false'));
        
        $consent_manager->renderConsentForm();
        
        error_log('[MPAI Debug] Welcome page: Consent form rendering completed');
        ?>
        
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
</style>

<script type="text/javascript">
    console.log('[MPAI Debug] Welcome page loaded - consent form will be rendered by consent manager');
</script>