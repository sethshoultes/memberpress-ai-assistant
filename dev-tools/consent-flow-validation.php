<?php
/**
 * Consent Flow Validation Tool
 * 
 * This script validates the complete consent flow after plugin reactivation
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once dirname(__DIR__, 4) . '/wp-load.php';
}

echo "<h2>MemberPress AI Assistant - Consent Flow Validation</h2>\n";
echo "<pre>\n";

$user_id = get_current_user_id();
if ($user_id <= 0) {
    echo "ERROR: No user logged in. Please log in to test consent flow.\n";
    echo "</pre>\n";
    return;
}

echo "=== CONSENT FLOW VALIDATION ===\n";
echo "Testing User ID: {$user_id}\n\n";

// Get consent manager instance
$consent_manager = \MemberpressAiAssistant\Admin\MPAIConsentManager::getInstance();

// SCENARIO 1: Simulate plugin deactivation (clear consent)
echo "SCENARIO 1: Plugin Deactivation (Clear Consent)\n";
echo "-------------------------------------------\n";

// Check initial state
$initial_consent = $consent_manager->hasUserConsented();
echo "Initial Consent Status: " . ($initial_consent ? 'CONSENTED' : 'NOT CONSENTED') . "\n";

// Simulate plugin deactivation by clearing consent
echo "Simulating plugin deactivation...\n";
$clear_result = $consent_manager->resetUserConsent($user_id);
echo "Clear Consent Result: " . ($clear_result ? 'SUCCESS' : 'FAILED') . "\n";

// Verify consent is cleared
$after_clear = $consent_manager->hasUserConsented();
echo "Consent After Clear: " . ($after_clear ? 'STILL CONSENTED (ERROR!)' : 'CLEARED (CORRECT)') . "\n";

// Check raw database value
$raw_after_clear = get_user_meta($user_id, 'mpai_has_consented', true);
echo "Raw DB Value After Clear: " . var_export($raw_after_clear, true) . "\n";

echo "\n";

// SCENARIO 2: Simulate plugin reactivation (should show consent form)
echo "SCENARIO 2: Plugin Reactivation (Should Show Consent Form)\n";
echo "--------------------------------------------------------\n";

// Check if consent form should be shown
$should_show_form = !$consent_manager->hasUserConsented();
echo "Should Show Consent Form: " . ($should_show_form ? 'YES (CORRECT)' : 'NO (ERROR!)') . "\n";

// Check template rendering conditions
$current_screen = get_current_screen();
$screen_id = $current_screen ? $current_screen->id : 'unknown';
$current_page = isset($_GET['page']) ? $_GET['page'] : 'none';

echo "Current Screen ID: {$screen_id}\n";
echo "Current Page Parameter: {$current_page}\n";

// Check if we're on a page that should show consent form
$is_settings_page = in_array($current_page, ['mpai-settings', 'mpai-welcome']);
echo "Is Settings/Welcome Page: " . ($is_settings_page ? 'YES' : 'NO') . "\n";

echo "\n";

// SCENARIO 3: Simulate consent opt-in
echo "SCENARIO 3: User Consent Opt-in\n";
echo "-------------------------------\n";

// Save consent
echo "Simulating user consent opt-in...\n";
$save_result = $consent_manager->saveUserConsent($user_id, true);
echo "Save Consent Result: " . ($save_result ? 'SUCCESS' : 'FAILED') . "\n";

// Verify consent is saved
$after_save = $consent_manager->hasUserConsented();
echo "Consent After Save: " . ($after_save ? 'CONSENTED (CORRECT)' : 'NOT CONSENTED (ERROR!)') . "\n";

// Check raw database value
$raw_after_save = get_user_meta($user_id, 'mpai_has_consented', true);
echo "Raw DB Value After Save: " . var_export($raw_after_save, true) . "\n";

echo "\n";

// SCENARIO 4: Check if chat interface should appear
echo "SCENARIO 4: Chat Interface Rendering Check\n";
echo "-----------------------------------------\n";

// Check consent status for chat interface
$has_consented_for_chat = $consent_manager->hasUserConsented();
echo "Has Consented for Chat: " . ($has_consented_for_chat ? 'YES' : 'NO') . "\n";

// Check if chat interface should be rendered
if (class_exists('\MemberpressAiAssistant\ChatInterface')) {
    // Simulate the conditions from renderAdminChatInterface
    $hook_suffix = $screen_id;
    $allowed_pages = [
        'memberpress_page_mpai-settings',
        'toplevel_page_mpai-settings',
        'admin_page_mpai-welcome',
        'memberpress_page_mpai-welcome',
    ];
    $allowed_page_params = ['mpai-settings', 'mpai-welcome'];
    
    $should_load_chat = in_array($hook_suffix, $allowed_pages) || in_array($current_page, $allowed_page_params);
    echo "Should Load Chat Interface: " . ($should_load_chat ? 'YES' : 'NO') . "\n";
    
    // Check if duplicate rendering prevention would block it
    $already_rendered = defined('MPAI_CHAT_INTERFACE_RENDERED');
    echo "Already Rendered Flag Set: " . ($already_rendered ? 'YES (WOULD BLOCK)' : 'NO (WOULD ALLOW)') . "\n";
    
    // Final determination
    $would_render_chat = $has_consented_for_chat && $should_load_chat && !$already_rendered;
    echo "Would Render Chat Interface: " . ($would_render_chat ? 'YES (CORRECT)' : 'NO (PROBLEM!)') . "\n";
} else {
    echo "ChatInterface class not found!\n";
}

echo "\n";

// SCENARIO 5: Test complete flow simulation
echo "SCENARIO 5: Complete Flow Simulation\n";
echo "-----------------------------------\n";

// Step 1: Clear consent (simulate deactivation)
echo "Step 1: Clearing consent (deactivation)...\n";
$consent_manager->resetUserConsent($user_id);
$step1_result = !$consent_manager->hasUserConsented();
echo "Step 1 Result: " . ($step1_result ? 'SUCCESS - Consent cleared' : 'FAILED - Consent not cleared') . "\n";

// Step 2: Check consent form would show (simulate reactivation)
echo "Step 2: Checking consent form display (reactivation)...\n";
$step2_result = !$consent_manager->hasUserConsented();
echo "Step 2 Result: " . ($step2_result ? 'SUCCESS - Would show consent form' : 'FAILED - Would not show consent form') . "\n";

// Step 3: Give consent (simulate user opt-in)
echo "Step 3: Giving consent (user opt-in)...\n";
$consent_manager->saveUserConsent($user_id, true);
$step3_result = $consent_manager->hasUserConsented();
echo "Step 3 Result: " . ($step3_result ? 'SUCCESS - Consent saved' : 'FAILED - Consent not saved') . "\n";

// Step 4: Check chat interface would show
echo "Step 4: Checking chat interface display...\n";
$step4_result = $consent_manager->hasUserConsented();
echo "Step 4 Result: " . ($step4_result ? 'SUCCESS - Would show chat interface' : 'FAILED - Would not show chat interface') . "\n";

// Overall flow result
$flow_success = $step1_result && $step2_result && $step3_result && $step4_result;
echo "\nOVERALL FLOW RESULT: " . ($flow_success ? 'SUCCESS - Flow works correctly' : 'FAILED - Flow has issues') . "\n";

echo "\n";

// SCENARIO 6: Test resetAllConsents functionality
echo "SCENARIO 6: Reset All Consents Test\n";
echo "----------------------------------\n";

// Create multiple test consent entries
$test_users = [$user_id];
if ($user_id > 1) {
    $test_users[] = 1; // Admin user
}

echo "Creating test consent entries...\n";
foreach ($test_users as $test_user) {
    update_user_meta($test_user, 'mpai_has_consented', true);
    echo "Set consent for user {$test_user}\n";
}

// Count consent entries before reset
global $wpdb;
$before_count = $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = 'mpai_has_consented'"
);
echo "Consent entries before reset: {$before_count}\n";

// Test resetAllConsents
echo "Testing resetAllConsents...\n";
\MemberpressAiAssistant\Admin\MPAIConsentManager::resetAllConsents();

// Count consent entries after reset
$after_count = $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = 'mpai_has_consented'"
);
echo "Consent entries after reset: {$after_count}\n";

$reset_success = ($after_count == 0);
echo "Reset All Consents Result: " . ($reset_success ? 'SUCCESS - All consents cleared' : 'FAILED - Some consents remain') . "\n";

// Verify individual user consent is cleared
$individual_cleared = !$consent_manager->hasUserConsented();
echo "Individual User Consent Cleared: " . ($individual_cleared ? 'YES' : 'NO') . "\n";

echo "\n=== VALIDATION COMPLETE ===\n";

// Summary of findings
echo "SUMMARY OF FINDINGS:\n";
echo "- Plugin deactivation consent clearing: " . ($step1_result ? 'WORKING' : 'BROKEN') . "\n";
echo "- Plugin reactivation consent form display: " . ($step2_result ? 'WORKING' : 'BROKEN') . "\n";
echo "- User consent opt-in saving: " . ($step3_result ? 'WORKING' : 'BROKEN') . "\n";
echo "- Chat interface display after consent: " . ($step4_result ? 'WORKING' : 'BROKEN') . "\n";
echo "- Reset all consents functionality: " . ($reset_success ? 'WORKING' : 'BROKEN') . "\n";
echo "- Overall consent flow: " . ($flow_success && $reset_success ? 'WORKING' : 'NEEDS FIXING') . "\n";

echo "</pre>\n";
?>

<style>
pre {
    background: #f1f1f1;
    padding: 15px;
    border-radius: 5px;
    font-family: 'Courier New', monospace;
    font-size: 12px;
    line-height: 1.4;
    overflow-x: auto;
    max-height: 600px;
    overflow-y: auto;
}
h2 {
    color: #333;
    border-bottom: 2px solid #0073aa;
    padding-bottom: 10px;
}
</style>