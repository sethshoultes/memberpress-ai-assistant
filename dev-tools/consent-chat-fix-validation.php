<?php
/**
 * Consent Form and Chat Interface Fix Validation Script
 * 
 * This script validates that the fixes for consent form duplication
 * and chat interface rendering issues are working correctly.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once dirname(__DIR__, 4) . '/wp-load.php';
}

echo "<h1>MemberPress AI Assistant - Fix Validation Results</h1>\n";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    .error { background-color: #ffebee; border-color: #f44336; }
    .warning { background-color: #fff3e0; border-color: #ff9800; }
    .success { background-color: #e8f5e8; border-color: #4caf50; }
    .info { background-color: #e3f2fd; border-color: #2196f3; }
    .code { background-color: #f5f5f5; padding: 10px; border-radius: 3px; font-family: monospace; }
    .highlight { background-color: yellow; font-weight: bold; }
    .pass { color: #4caf50; font-weight: bold; }
    .fail { color: #f44336; font-weight: bold; }
</style>\n";

// Test 1: Consent Form Duplication Fix
echo "<div class='section info'>\n";
echo "<h2>✅ TEST 1: Consent Form Duplication Fix</h2>\n";

$welcome_template = MPAI_PLUGIN_DIR . 'templates/welcome-page.php';
if (file_exists($welcome_template)) {
    $welcome_content = file_get_contents($welcome_template);
    
    echo "<h3>Checking welcome-page.php content:</h3>\n";
    
    // Check if duplicate content was removed
    $has_duplicate_title = strpos($welcome_content, '<h1><?php _e(\'Welcome to MemberPress AI Assistant\'') !== false;
    $has_duplicate_terms = strpos($welcome_content, '<div class="mpai-terms-box">') !== false;
    $has_consent_manager_call = strpos($welcome_content, 'renderConsentForm()') !== false;
    
    echo "<div class='code'>\n";
    echo "Duplicate title removed: " . ($has_duplicate_title ? "<span class='fail'>FAIL - Still present</span>" : "<span class='pass'>PASS - Removed</span>") . "<br>\n";
    echo "Duplicate terms content removed: " . ($has_duplicate_terms ? "<span class='fail'>FAIL - Still present</span>" : "<span class='pass'>PASS - Removed</span>") . "<br>\n";
    echo "Consent manager call preserved: " . ($has_consent_manager_call ? "<span class='pass'>PASS - Present</span>" : "<span class='fail'>FAIL - Missing</span>") . "<br>\n";
    echo "</div>\n";
    
    if (!$has_duplicate_title && !$has_duplicate_terms && $has_consent_manager_call) {
        echo "<div class='success'><strong>✅ CONSENT FORM DUPLICATION FIX: SUCCESSFUL</strong></div>\n";
    } else {
        echo "<div class='error'><strong>❌ CONSENT FORM DUPLICATION FIX: FAILED</strong></div>\n";
    }
} else {
    echo "<div class='error'>Welcome template not found!</div>\n";
}

echo "</div>\n";

// Test 2: Logging Implementation
echo "<div class='section info'>\n";
echo "<h2>✅ TEST 2: Comprehensive Logging Implementation</h2>\n";

echo "<h3>Checking logging additions:</h3>\n";
echo "<div class='code'>\n";

// Check welcome-page.php logging
$welcome_has_logging = strpos($welcome_content, '[MPAI Debug] Welcome page:') !== false;
echo "Welcome page logging: " . ($welcome_has_logging ? "<span class='pass'>PASS - Added</span>" : "<span class='fail'>FAIL - Missing</span>") . "<br>\n";

// Check consent manager logging
$consent_manager_file = MPAI_PLUGIN_DIR . 'src/Admin/MPAIConsentManager.php';
if (file_exists($consent_manager_file)) {
    $consent_content = file_get_contents($consent_manager_file);
    $consent_has_logging = strpos($consent_content, '[MPAI Debug] ConsentManager:') !== false;
    echo "Consent manager logging: " . ($consent_has_logging ? "<span class='pass'>PASS - Added</span>" : "<span class='fail'>FAIL - Missing</span>") . "<br>\n";
}

// Check chat interface logging
$chat_interface_file = MPAI_PLUGIN_DIR . 'src/ChatInterface.php';
if (file_exists($chat_interface_file)) {
    $chat_content = file_get_contents($chat_interface_file);
    $chat_has_logging = strpos($chat_content, '[MPAI Debug] ChatInterface:') !== false;
    echo "Chat interface logging: " . ($chat_has_logging ? "<span class='pass'>PASS - Added</span>" : "<span class='fail'>FAIL - Missing</span>") . "<br>\n";
}

// Check settings controller logging
$settings_controller_file = MPAI_PLUGIN_DIR . 'src/Services/Settings/SettingsControllerService.php';
if (file_exists($settings_controller_file)) {
    $settings_content = file_get_contents($settings_controller_file);
    $settings_has_logging = strpos($settings_content, '[MPAI Debug] SettingsController:') !== false;
    echo "Settings controller logging: " . ($settings_has_logging ? "<span class='pass'>PASS - Added</span>" : "<span class='fail'>FAIL - Missing</span>") . "<br>\n";
}

// Check JavaScript logging
$chat_js_file = MPAI_PLUGIN_DIR . 'assets/js/chat.js';
if (file_exists($chat_js_file)) {
    $js_content = file_get_contents($chat_js_file);
    $js_has_enhanced_logging = strpos($js_content, 'Enhanced chat container detection') !== false;
    echo "JavaScript enhanced logging: " . ($js_has_enhanced_logging ? "<span class='pass'>PASS - Added</span>" : "<span class='fail'>FAIL - Missing</span>") . "<br>\n";
}

echo "</div>\n";
echo "</div>\n";

// Test 3: Chat Interface Rendering Logic
echo "<div class='section info'>\n";
echo "<h2>✅ TEST 3: Chat Interface Rendering Logic</h2>\n";

echo "<h3>Checking shouldLoadAdminChatInterface improvements:</h3>\n";
echo "<div class='code'>\n";

if (isset($chat_content)) {
    $has_improved_logic = strpos($chat_content, 'memberpress_page_mpai-settings') !== false;
    $has_page_param_check = strpos($chat_content, 'allowed_page_params') !== false;
    
    echo "Improved page detection logic: " . ($has_improved_logic ? "<span class='pass'>PASS - Added</span>" : "<span class='fail'>FAIL - Missing</span>") . "<br>\n";
    echo "Page parameter checking: " . ($has_page_param_check ? "<span class='pass'>PASS - Added</span>" : "<span class='fail'>FAIL - Missing</span>") . "<br>\n";
}

echo "</div>\n";
echo "</div>\n";

// Test 4: Template Integrity
echo "<div class='section info'>\n";
echo "<h2>✅ TEST 4: Template Integrity Check</h2>\n";

echo "<h3>Verifying template files exist and are valid:</h3>\n";
echo "<div class='code'>\n";

$templates = [
    'welcome-page.php' => MPAI_PLUGIN_DIR . 'templates/welcome-page.php',
    'consent-form.php' => MPAI_PLUGIN_DIR . 'templates/consent-form.php',
    'chat-interface.php' => MPAI_PLUGIN_DIR . 'templates/chat-interface.php'
];

foreach ($templates as $name => $path) {
    $exists = file_exists($path);
    $readable = $exists && is_readable($path);
    $has_content = $readable && filesize($path) > 0;
    
    echo "$name: ";
    if ($exists && $readable && $has_content) {
        echo "<span class='pass'>PASS - Valid</span>";
    } else {
        echo "<span class='fail'>FAIL - ";
        if (!$exists) echo "Missing";
        elseif (!$readable) echo "Not readable";
        elseif (!$has_content) echo "Empty";
        echo "</span>";
    }
    echo "<br>\n";
}

echo "</div>\n";
echo "</div>\n";

// Test 5: Current User State
echo "<div class='section info'>\n";
echo "<h2>✅ TEST 5: Current User State</h2>\n";

echo "<h3>Current user and consent status:</h3>\n";
echo "<div class='code'>\n";

$current_user_id = get_current_user_id();
echo "Current user ID: " . ($current_user_id ? $current_user_id : 'Not logged in') . "<br>\n";

if ($current_user_id) {
    $consent_manager = \MemberpressAiAssistant\Admin\MPAIConsentManager::getInstance();
    $has_consented = $consent_manager->hasUserConsented();
    echo "User consent status: " . ($has_consented ? "<span class='pass'>Consented</span>" : "<span class='warning'>Not consented</span>") . "<br>\n";
    
    $can_manage_options = current_user_can('manage_options');
    echo "Can manage options: " . ($can_manage_options ? "<span class='pass'>Yes</span>" : "<span class='fail'>No</span>") . "<br>\n";
}

echo "</div>\n";
echo "</div>\n";

// Test 6: Next Steps and Recommendations
echo "<div class='section success'>\n";
echo "<h2>🔧 NEXT STEPS FOR TESTING</h2>\n";

echo "<h3>Manual Testing Steps:</h3>\n";
echo "<ol>\n";
echo "<li><strong>Test Consent Form:</strong><br>\n";
echo "   - Navigate to <code>/wp-admin/admin.php?page=mpai-welcome</code><br>\n";
echo "   - Verify only ONE consent form appears<br>\n";
echo "   - Check browser console for '[MPAI Debug] Welcome page:' logs</li>\n";

echo "<li><strong>Test Chat Interface After Consent:</strong><br>\n";
echo "   - Give consent and get redirected to settings page<br>\n";
echo "   - Check if chat container exists in DOM<br>\n";
echo "   - Look for '[MPAI Debug] ChatInterface:' logs in browser console</li>\n";

echo "<li><strong>Check Error Logs:</strong><br>\n";
echo "   - Monitor WordPress error logs for '[MPAI Debug]' entries<br>\n";
echo "   - Verify the flow from consent to chat interface rendering</li>\n";

echo "<li><strong>JavaScript Console Testing:</strong><br>\n";
echo "   - Open browser dev tools on settings page<br>\n";
echo "   - Should see enhanced chat container detection logs<br>\n";
echo "   - Should NOT see 'Chat container not found' errors</li>\n";
echo "</ol>\n";

echo "<h3>Expected Results After Fixes:</h3>\n";
echo "<ul>\n";
echo "<li>✅ Only one consent form on welcome page</li>\n";
echo "<li>✅ Chat interface appears on settings page after consent</li>\n";
echo "<li>✅ No JavaScript 'container not found' errors</li>\n";
echo "<li>✅ Comprehensive debug logs in browser console and error logs</li>\n";
echo "</ul>\n";

echo "</div>\n";

// Summary
echo "<div class='section info'>\n";
echo "<h2>📋 SUMMARY</h2>\n";
echo "<p><strong>Fixes Implemented:</strong></p>\n";
echo "<ol>\n";
echo "<li><strong>Consent Form Duplication:</strong> Removed duplicate content from welcome-page.php</li>\n";
echo "<li><strong>Chat Interface Rendering:</strong> Enhanced shouldLoadAdminChatInterface logic</li>\n";
echo "<li><strong>Comprehensive Logging:</strong> Added debug logs throughout the system</li>\n";
echo "<li><strong>JavaScript Enhancement:</strong> Improved chat container detection with retry logic</li>\n";
echo "</ol>\n";

echo "<p><strong>Ready for manual testing to validate the fixes work correctly.</strong></p>\n";
echo "</div>\n";

?>