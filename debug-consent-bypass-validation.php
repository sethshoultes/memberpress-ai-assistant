<?php
/**
 * Debug script to validate consent bypass diagnosis
 * 
 * This script will help confirm where the consent validation is failing
 */

// Add this to ChatInterface.php renderAdminChatInterface() method to validate our diagnosis
function debug_consent_bypass_validation() {
    error_log('[CONSENT DEBUG] === renderAdminChatInterface() Called ===');
    
    // Check current screen
    $current_screen = get_current_screen();
    $screen_id = $current_screen ? $current_screen->id : 'unknown';
    error_log('[CONSENT DEBUG] Screen ID: ' . $screen_id);
    
    // Check consent status
    $consent_manager = \MemberpressAiAssistant\Admin\MPAIConsentManager::getInstance();
    $has_consented = $consent_manager->hasUserConsented();
    $user_id = get_current_user_id();
    
    error_log('[CONSENT DEBUG] User ID: ' . $user_id);
    error_log('[CONSENT DEBUG] Has Consented: ' . ($has_consented ? 'YES' : 'NO'));
    
    // Check if we're bypassing consent validation
    error_log('[CONSENT DEBUG] CRITICAL: Chat interface rendering WITHOUT consent validation check');
    error_log('[CONSENT DEBUG] This is the root cause of the consent bypass issue');
    
    // Check user meta directly
    $consent_meta = get_user_meta($user_id, 'mpai_has_consented', true);
    error_log('[CONSENT DEBUG] Direct user meta check: ' . ($consent_meta ? 'TRUE' : 'FALSE'));
    
    // Check if this is after plugin reactivation
    $plugin_activated_recently = get_transient('mpai_plugin_activated');
    error_log('[CONSENT DEBUG] Plugin activated recently: ' . ($plugin_activated_recently ? 'YES' : 'NO'));
    
    return $has_consented;
}

// Recommended fix structure:
function fixed_renderAdminChatInterface() {
    // Add logging for monitoring
    $current_screen = get_current_screen();
    $screen_id = $current_screen ? $current_screen->id : 'unknown';
    
    error_log('[CONSENT DEBUG] renderAdminChatInterface() called for screen: ' . $screen_id);
    
    // Check for duplicate rendering flag
    if (defined('MPAI_CHAT_INTERFACE_RENDERED')) {
        error_log('[CONSENT DEBUG] Chat interface already rendered, preventing duplicate');
        return;
    }
    
    // Only render on appropriate admin pages
    if (!$this->shouldLoadAdminChatInterface($screen_id)) {
        error_log('[CONSENT DEBUG] Not loading chat interface for screen: ' . $screen_id);
        return;
    }
    
    // CRITICAL FIX: Check consent status BEFORE rendering
    $consent_manager = \MemberpressAiAssistant\Admin\MPAIConsentManager::getInstance();
    $has_consented = $consent_manager->hasUserConsented();
    
    error_log('[CONSENT DEBUG] Consent check result: ' . ($has_consented ? 'CONSENTED' : 'NOT_CONSENTED'));
    
    if (!$has_consented) {
        error_log('[CONSENT DEBUG] User has not consented - NOT rendering chat interface');
        return; // Don't render chat interface if user hasn't consented
    }
    
    error_log('[CONSENT DEBUG] User has consented - rendering chat interface');
    
    // Set flag to prevent duplicate rendering
    define('MPAI_CHAT_INTERFACE_RENDERED', true);
    
    // Render the chat interface
    $this->renderChatContainerHTML();
}
?>