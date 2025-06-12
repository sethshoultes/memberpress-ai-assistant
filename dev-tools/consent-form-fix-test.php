<?php
/**
 * Consent Form Fix Test Script
 * 
 * This script tests the fixed consent form functionality
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once dirname(__DIR__, 4) . '/wp-load.php';
}

echo "<h1>Consent Form Fix Test Results</h1>\n";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    .error { background-color: #ffebee; border-color: #f44336; }
    .warning { background-color: #fff3e0; border-color: #ff9800; }
    .success { background-color: #e8f5e8; border-color: #4caf50; }
    .info { background-color: #e3f2fd; border-color: #2196f3; }
    .code { background-color: #f5f5f5; padding: 10px; border-radius: 3px; font-family: monospace; }
    .pass { color: #4caf50; font-weight: bold; }
    .fail { color: #f44336; font-weight: bold; }
</style>\n";

// Test 1: Check consent form structure
echo "<div class='section info'>\n";
echo "<h2>✅ TEST 1: Consent Form Structure</h2>\n";

$consent_template = MPAI_PLUGIN_DIR . 'templates/consent-form.php';
if (file_exists($consent_template)) {
    $consent_content = file_get_contents($consent_template);
    
    echo "<h3>Checking form fixes:</h3>\n";
    echo "<div class='code'>\n";
    
    // Check for form action
    $has_form_action = strpos($consent_content, 'action="<?php echo esc_url(admin_url(\'admin.php?page=mpai-settings\')); ?>"') !== false;
    echo "Form action attribute: " . ($has_form_action ? "<span class='pass'>PASS - Present</span>" : "<span class='fail'>FAIL - Missing</span>") . "<br>\n";
    
    // Check for proper nonce
    $has_wp_nonce = strpos($consent_content, 'wp_nonce_field(\'mpai_consent_nonce\', \'mpai_consent_nonce\')') !== false;
    echo "WordPress nonce field: " . ($has_wp_nonce ? "<span class='pass'>PASS - Present</span>" : "<span class='fail'>FAIL - Missing</span>") . "<br>\n";
    
    // Check for jQuery usage
    $has_jquery = strpos($consent_content, 'jQuery(document).ready(function($)') !== false;
    echo "jQuery implementation: " . ($has_jquery ? "<span class='pass'>PASS - Present</span>" : "<span class='fail'>FAIL - Missing</span>") . "<br>\n";
    
    // Check for proper label structure
    $has_proper_label = strpos($consent_content, 'id="mpai-consent-label"') !== false;
    echo "Proper label structure: " . ($has_proper_label ? "<span class='pass'>PASS - Present</span>" : "<span class='fail'>FAIL - Missing</span>") . "<br>\n";
    
    // Check for consent-required CSS
    $has_consent_css = strpos($consent_content, '.consent-required .button-primary') !== false;
    echo "Consent-required CSS: " . ($has_consent_css ? "<span class='pass'>PASS - Present</span>" : "<span class='fail'>FAIL - Missing</span>") . "<br>\n";
    
    echo "</div>\n";
    
    $all_checks_pass = $has_form_action && $has_wp_nonce && $has_jquery && $has_proper_label && $has_consent_css;
    
    if ($all_checks_pass) {
        echo "<div class='success'><strong>✅ CONSENT FORM STRUCTURE: FIXED</strong></div>\n";
    } else {
        echo "<div class='error'><strong>❌ CONSENT FORM STRUCTURE: STILL BROKEN</strong></div>\n";
    }
} else {
    echo "<div class='error'>Consent form template not found!</div>\n";
}

echo "</div>\n";

// Test 2: Check welcome page duplication fix
echo "<div class='section info'>\n";
echo "<h2>✅ TEST 2: Welcome Page Duplication Fix</h2>\n";

$welcome_template = MPAI_PLUGIN_DIR . 'templates/welcome-page.php';
if (file_exists($welcome_template)) {
    $welcome_content = file_get_contents($welcome_template);
    
    echo "<h3>Checking duplication removal:</h3>\n";
    echo "<div class='code'>\n";
    
    // Check if duplicate content was removed
    $has_duplicate_title = strpos($welcome_content, '<h1><?php _e(\'Welcome to MemberPress AI Assistant\'') !== false;
    $has_duplicate_terms = strpos($welcome_content, '<div class="mpai-terms-box">') !== false;
    $has_consent_manager_call = strpos($welcome_content, 'renderConsentForm()') !== false;
    
    echo "Duplicate title removed: " . ($has_duplicate_title ? "<span class='fail'>FAIL - Still present</span>" : "<span class='pass'>PASS - Removed</span>") . "<br>\n";
    echo "Duplicate terms content removed: " . ($has_duplicate_terms ? "<span class='fail'>FAIL - Still present</span>" : "<span class='pass'>PASS - Removed</span>") . "<br>\n";
    echo "Consent manager call preserved: " . ($has_consent_manager_call ? "<span class='pass'>PASS - Present</span>" : "<span class='fail'>FAIL - Missing</span>") . "<br>\n";
    
    echo "</div>\n";
    
    if (!$has_duplicate_title && !$has_duplicate_terms && $has_consent_manager_call) {
        echo "<div class='success'><strong>✅ WELCOME PAGE DUPLICATION: FIXED</strong></div>\n";
    } else {
        echo "<div class='error'><strong>❌ WELCOME PAGE DUPLICATION: STILL BROKEN</strong></div>\n";
    }
} else {
    echo "<div class='error'>Welcome page template not found!</div>\n";
}

echo "</div>\n";

// Test 3: Check consent manager redirect
echo "<div class='section info'>\n";
echo "<h2>✅ TEST 3: Consent Manager Redirect Fix</h2>\n";

$consent_manager_file = MPAI_PLUGIN_DIR . 'src/Admin/MPAIConsentManager.php';
if (file_exists($consent_manager_file)) {
    $consent_manager_content = file_get_contents($consent_manager_file);
    
    echo "<h3>Checking redirect URL:</h3>\n";
    echo "<div class='code'>\n";
    
    // Check for correct redirect URL
    $has_correct_redirect = strpos($consent_manager_content, 'admin.php?page=mpai-settings&consent=given') !== false;
    echo "Correct redirect URL: " . ($has_correct_redirect ? "<span class='pass'>PASS - Present</span>" : "<span class='fail'>FAIL - Missing</span>") . "<br>\n";
    
    echo "</div>\n";
    
    if ($has_correct_redirect) {
        echo "<div class='success'><strong>✅ CONSENT MANAGER REDIRECT: FIXED</strong></div>\n";
    } else {
        echo "<div class='error'><strong>❌ CONSENT MANAGER REDIRECT: STILL BROKEN</strong></div>\n";
    }
} else {
    echo "<div class='error'>Consent manager file not found!</div>\n";
}

echo "</div>\n";

// Test 4: Current user state
echo "<div class='section info'>\n";
echo "<h2>✅ TEST 4: Current User State</h2>\n";

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
    
    if (!$has_consented) {
        echo "<br><strong>To test the fix:</strong><br>\n";
        echo "1. Navigate to <a href='" . admin_url('admin.php?page=mpai-welcome') . "'>Welcome Page</a><br>\n";
        echo "2. You should see only ONE consent form<br>\n";
        echo "3. Check the checkbox and click 'Agree and Continue'<br>\n";
        echo "4. You should be redirected to the settings page<br>\n";
    }
}

echo "</div>\n";
echo "</div>\n";

// Test 5: Manual testing instructions
echo "<div class='section success'>\n";
echo "<h2>🧪 MANUAL TESTING INSTRUCTIONS</h2>\n";

echo "<h3>Step-by-Step Testing:</h3>\n";
echo "<ol>\n";
echo "<li><strong>Reset Consent (if needed):</strong><br>\n";
if ($current_user_id) {
    echo "   <a href='#' onclick='resetConsent()' class='button'>Reset My Consent</a><br>\n";
}
echo "   This will clear your consent so you can test the form again</li>\n";

echo "<li><strong>Test Consent Form:</strong><br>\n";
echo "   - Navigate to <a href='" . admin_url('admin.php?page=mpai-welcome') . "' target='_blank'>Welcome Page</a><br>\n";
echo "   - Verify only ONE consent form appears<br>\n";
echo "   - Check that the checkbox enables/disables the button<br>\n";
echo "   - Submit the form and verify redirect works</li>\n";

echo "<li><strong>Test Settings Page:</strong><br>\n";
echo "   - After consent, you should land on <a href='" . admin_url('admin.php?page=mpai-settings') . "' target='_blank'>Settings Page</a><br>\n";
echo "   - Check if chat interface appears<br>\n";
echo "   - Look for any JavaScript errors in browser console</li>\n";
echo "</ol>\n";

echo "<h3>Expected Results:</h3>\n";
echo "<ul>\n";
echo "<li>✅ Only one consent form on welcome page</li>\n";
echo "<li>✅ Form submission works and redirects properly</li>\n";
echo "<li>✅ Settings page loads after consent</li>\n";
echo "<li>✅ No duplicate consent forms</li>\n";
echo "<li>✅ No JavaScript errors</li>\n";
echo "</ul>\n";

echo "</div>\n";

// JavaScript for reset consent functionality
if ($current_user_id) {
    echo "<script>\n";
    echo "function resetConsent() {\n";
    echo "    if (confirm('Are you sure you want to reset your consent? This will allow you to test the consent form again.')) {\n";
    echo "        fetch('" . admin_url('admin-ajax.php') . "', {\n";
    echo "            method: 'POST',\n";
    echo "            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },\n";
    echo "            body: 'action=mpai_reset_consent&nonce=" . wp_create_nonce('mpai_reset_consent') . "'\n";
    echo "        }).then(() => {\n";
    echo "            alert('Consent reset successfully. You can now test the consent form.');\n";
    echo "            window.location.reload();\n";
    echo "        });\n";
    echo "    }\n";
    echo "}\n";
    echo "</script>\n";
}

?>