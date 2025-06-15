<?php
/**
 * Consent Flow Diagnosis Tool
 * 
 * This script diagnoses the consent flow issues after plugin reactivation
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once dirname(__DIR__, 4) . '/wp-load.php';
}

echo "<h2>MemberPress AI Assistant - Consent Flow Diagnosis</h2>\n";
echo "<pre>\n";

// Test 1: Check current consent status
echo "=== TEST 1: Current Consent Status ===\n";
$user_id = get_current_user_id();
echo "Current User ID: " . $user_id . "\n";

if ($user_id > 0) {
    $consent_manager = \MemberpressAiAssistant\Admin\MPAIConsentManager::getInstance();
    $has_consented = $consent_manager->hasUserConsented();
    echo "Has User Consented: " . ($has_consented ? 'YES' : 'NO') . "\n";
    
    // Check raw user meta
    $raw_consent = get_user_meta($user_id, 'mpai_has_consented', true);
    echo "Raw User Meta Value: " . var_export($raw_consent, true) . "\n";
    echo "Raw User Meta Type: " . gettype($raw_consent) . "\n";
} else {
    echo "ERROR: No user logged in\n";
}

// Test 2: Check plugin activation/deactivation hooks
echo "\n=== TEST 2: Plugin Lifecycle Hooks ===\n";
$activation_hooks = wp_get_active_and_valid_plugins();
$mpai_active = false;
foreach ($activation_hooks as $plugin) {
    if (strpos($plugin, 'memberpress-ai-assistant') !== false) {
        $mpai_active = true;
        echo "Plugin Active: YES\n";
        echo "Plugin Path: " . $plugin . "\n";
        break;
    }
}
if (!$mpai_active) {
    echo "Plugin Active: NO\n";
}

// Test 3: Check consent database entries
echo "\n=== TEST 3: Database Consent Entries ===\n";
global $wpdb;
$consent_entries = $wpdb->get_results(
    "SELECT user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key = 'mpai_has_consented'"
);
echo "Total Consent Entries in Database: " . count($consent_entries) . "\n";
foreach ($consent_entries as $entry) {
    echo "User ID {$entry->user_id}: " . var_export($entry->meta_value, true) . "\n";
}

// Test 4: Test consent clearing functionality
echo "\n=== TEST 4: Consent Clearing Test ===\n";
if ($user_id > 0) {
    // Save current state
    $original_consent = get_user_meta($user_id, 'mpai_has_consented', true);
    echo "Original Consent State: " . var_export($original_consent, true) . "\n";
    
    // Test setting consent to true
    $result = update_user_meta($user_id, 'mpai_has_consented', true);
    echo "Set Consent to TRUE - Result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
    
    $check_consent = get_user_meta($user_id, 'mpai_has_consented', true);
    echo "Consent After Setting TRUE: " . var_export($check_consent, true) . "\n";
    
    // Test clearing consent
    $clear_result = delete_user_meta($user_id, 'mpai_has_consented');
    echo "Clear Consent - Result: " . ($clear_result ? 'SUCCESS' : 'FAILED') . "\n";
    
    $check_cleared = get_user_meta($user_id, 'mpai_has_consented', true);
    echo "Consent After Clearing: " . var_export($check_cleared, true) . "\n";
    
    // Restore original state
    if ($original_consent) {
        update_user_meta($user_id, 'mpai_has_consented', $original_consent);
        echo "Restored Original Consent State\n";
    }
}

// Test 5: Test resetAllConsents functionality
echo "\n=== TEST 5: Reset All Consents Test ===\n";
// Create a test user meta entry
$test_user_id = $user_id > 0 ? $user_id : 1;
update_user_meta($test_user_id, 'mpai_has_consented_test', true);
echo "Created test consent entry for user {$test_user_id}\n";

// Test the resetAllConsents method simulation
$test_delete_result = $wpdb->delete(
    $wpdb->usermeta,
    array('meta_key' => 'mpai_has_consented_test')
);
echo "Test Delete Result: " . var_export($test_delete_result, true) . "\n";

// Verify deletion
$remaining_test = $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = 'mpai_has_consented_test'"
);
echo "Remaining Test Entries: " . $remaining_test . "\n";

// Test 6: Check template rendering logic
echo "\n=== TEST 6: Template Rendering Logic ===\n";
$current_screen = get_current_screen();
echo "Current Screen ID: " . ($current_screen ? $current_screen->id : 'unknown') . "\n";
echo "Current Page Parameter: " . (isset($_GET['page']) ? $_GET['page'] : 'none') . "\n";

// Check if we're on settings page
$is_settings_page = isset($_GET['page']) && $_GET['page'] === 'mpai-settings';
echo "Is Settings Page: " . ($is_settings_page ? 'YES' : 'NO') . "\n";

// Check if chat interface should be rendered
if (class_exists('\MemberpressAiAssistant\ChatInterface')) {
    $chat_interface = \MemberpressAiAssistant\ChatInterface::getInstance();
    // We can't directly call private methods, so we'll check the conditions manually
    $hook_suffix = $current_screen ? $current_screen->id : '';
    $allowed_pages = [
        'memberpress_page_mpai-settings',
        'toplevel_page_mpai-settings',
        'admin_page_mpai-welcome',
        'memberpress_page_mpai-welcome',
    ];
    $current_page = isset($_GET['page']) ? $_GET['page'] : '';
    $allowed_page_params = ['mpai-settings', 'mpai-welcome'];
    
    $should_load = in_array($hook_suffix, $allowed_pages) || in_array($current_page, $allowed_page_params);
    echo "Should Load Admin Chat Interface: " . ($should_load ? 'YES' : 'NO') . "\n";
}

// Test 7: Check for duplicate rendering prevention
echo "\n=== TEST 7: Duplicate Rendering Prevention ===\n";
echo "MPAI_CHAT_INTERFACE_RENDERED defined: " . (defined('MPAI_CHAT_INTERFACE_RENDERED') ? 'YES' : 'NO') . "\n";

// Test 8: Check consent form templates
echo "\n=== TEST 8: Template File Existence ===\n";
$consent_template = MPAI_PLUGIN_DIR . 'templates/consent-form.php';
$inline_consent_template = MPAI_PLUGIN_DIR . 'templates/consent-form-inline.php';
$chat_template = MPAI_PLUGIN_DIR . 'templates/chat-interface.php';

echo "Consent Form Template: " . (file_exists($consent_template) ? 'EXISTS' : 'MISSING') . "\n";
echo "Inline Consent Template: " . (file_exists($inline_consent_template) ? 'EXISTS' : 'MISSING') . "\n";
echo "Chat Interface Template: " . (file_exists($chat_template) ? 'EXISTS' : 'MISSING') . "\n";

if (file_exists($consent_template)) {
    echo "Consent Template Path: " . $consent_template . "\n";
}
if (file_exists($inline_consent_template)) {
    echo "Inline Consent Template Path: " . $inline_consent_template . "\n";
}

echo "\n=== DIAGNOSIS COMPLETE ===\n";
echo "Please review the results above to identify the consent flow issues.\n";
echo "</pre>\n";

// Add some JavaScript to make this easier to read
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
}
h2 {
    color: #333;
    border-bottom: 2px solid #0073aa;
    padding-bottom: 10px;
}
</style>