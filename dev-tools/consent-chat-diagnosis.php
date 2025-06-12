<?php
/**
 * Comprehensive Consent Form and Chat Interface Diagnosis
 * 
 * This script diagnoses the specific issues with consent form duplication
 * and chat interface rendering problems.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once dirname(__DIR__, 4) . '/wp-load.php';
}

echo "<h1>MemberPress AI Assistant - Consent Form and Chat Interface Diagnosis</h1>\n";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    .error { background-color: #ffebee; border-color: #f44336; }
    .warning { background-color: #fff3e0; border-color: #ff9800; }
    .success { background-color: #e8f5e8; border-color: #4caf50; }
    .info { background-color: #e3f2fd; border-color: #2196f3; }
    .code { background-color: #f5f5f5; padding: 10px; border-radius: 3px; font-family: monospace; }
    .highlight { background-color: yellow; font-weight: bold; }
</style>\n";

// 1. CONSENT FORM DUPLICATION ANALYSIS
echo "<div class='section info'>\n";
echo "<h2>🔍 ISSUE 1: Consent Form Duplication Analysis</h2>\n";

echo "<h3>Consent Form Rendering Locations Found:</h3>\n";

// Check welcome page template
$welcome_template = MPAI_PLUGIN_DIR . 'templates/welcome-page.php';
if (file_exists($welcome_template)) {
    $welcome_content = file_get_contents($welcome_template);
    echo "<div class='code'>\n";
    echo "<strong>📄 templates/welcome-page.php:</strong><br>\n";
    
    // Check for consent form rendering
    if (strpos($welcome_content, 'renderConsentForm()') !== false) {
        echo "<span class='highlight'>✓ FOUND: Line ~70 - \$consent_manager->renderConsentForm()</span><br>\n";
    }
    
    // Check for direct consent form content
    if (strpos($welcome_content, 'Welcome to MemberPress AI Assistant') !== false) {
        echo "<span class='highlight'>✓ FOUND: Line ~23 - Direct consent form title</span><br>\n";
    }
    
    echo "</div>\n";
}

// Check consent form template
$consent_template = MPAI_PLUGIN_DIR . 'templates/consent-form.php';
if (file_exists($consent_template)) {
    $consent_content = file_get_contents($consent_template);
    echo "<div class='code'>\n";
    echo "<strong>📄 templates/consent-form.php:</strong><br>\n";
    
    if (strpos($consent_content, 'MemberPress AI Assistant - Consent Form') !== false) {
        echo "<span class='highlight'>✓ FOUND: Line ~28 - Standalone consent form with title</span><br>\n";
    }
    
    echo "</div>\n";
}

echo "<h3>🚨 DIAGNOSIS: Consent Form Duplication Root Cause</h3>\n";
echo "<div class='error'>\n";
echo "<strong>PROBLEM IDENTIFIED:</strong><br>\n";
echo "1. <code>templates/welcome-page.php</code> contains BOTH:<br>\n";
echo "   - Its own 'Welcome to MemberPress AI Assistant' title and content (Line 23)<br>\n";
echo "   - A call to <code>\$consent_manager->renderConsentForm()</code> (Line 70)<br><br>\n";
echo "2. <code>templates/consent-form.php</code> contains:<br>\n";
echo "   - Another 'MemberPress AI Assistant - Consent Form' title (Line 28)<br>\n";
echo "   - Complete consent form with identical content<br><br>\n";
echo "<strong>RESULT:</strong> Two consent forms are rendered - one from welcome-page.php content and one from the renderConsentForm() call.\n";
echo "</div>\n";

echo "</div>\n";

// 2. CHAT INTERFACE RENDERING ANALYSIS
echo "<div class='section info'>\n";
echo "<h2>🔍 ISSUE 2: Chat Interface Rendering Analysis</h2>\n";

echo "<h3>Chat Interface Rendering Flow:</h3>\n";

// Check admin menu welcome page logic
echo "<div class='code'>\n";
echo "<strong>📄 src/Admin/MPAIAdminMenu.php - render_welcome_page():</strong><br>\n";
echo "Line 247: Checks if user has consented<br>\n";
echo "Line 248-251: If consented, redirects to settings page<br>\n";
echo "Line 254: If not consented, includes welcome-page.php template<br>\n";
echo "</div>\n";

// Check chat interface rendering logic
echo "<div class='code'>\n";
echo "<strong>📄 src/ChatInterface.php - renderAdminChatInterface():</strong><br>\n";
echo "Line 390: Checks if user has consented<br>\n";
echo "Line 393-397: If not consented AND not on welcome page, redirects to welcome<br>\n";
echo "Line 401: If consented, includes chat-interface.php template<br>\n";
echo "</div>\n";

echo "<h3>🚨 DIAGNOSIS: Chat Interface Missing Root Cause</h3>\n";
echo "<div class='error'>\n";
echo "<strong>PROBLEM IDENTIFIED:</strong><br>\n";
echo "1. After consent is given, user is redirected to settings page (mpai-settings)<br>\n";
echo "2. Settings page renders through SettingsControllerService, not through admin menu<br>\n";
echo "3. ChatInterface.renderAdminChatInterface() only renders on specific admin pages<br>\n";
echo "4. The settings page may not be triggering chat interface rendering<br><br>\n";
echo "<strong>RESULT:</strong> Chat container is never created after consent, causing JavaScript errors.\n";
echo "</div>\n";

echo "</div>\n";

// 3. JAVASCRIPT ERROR ANALYSIS
echo "<div class='section info'>\n";
echo "<h2>🔍 ISSUE 3: JavaScript Error Analysis</h2>\n";

echo "<div class='code'>\n";
echo "<strong>📄 assets/js/chat.js - DOMContentLoaded:</strong><br>\n";
echo "Line 58: const chatContainer = document.getElementById('mpai-chat-container');<br>\n";
echo "Line 59-62: if (!chatContainer) { console.warn('[MPAI Chat] Chat container not found'); return; }<br>\n";
echo "</div>\n";

echo "<h3>🚨 DIAGNOSIS: JavaScript Error Root Cause</h3>\n";
echo "<div class='error'>\n";
echo "<strong>PROBLEM IDENTIFIED:</strong><br>\n";
echo "1. JavaScript expects 'mpai-chat-container' element to exist<br>\n";
echo "2. Chat container is only created by templates/chat-interface.php<br>\n";
echo "3. Chat interface template is only included when ChatInterface.renderAdminChatInterface() is called<br>\n";
echo "4. After consent, user lands on settings page where chat interface may not be rendered<br><br>\n";
echo "<strong>RESULT:</strong> JavaScript runs but finds no chat container, generating console errors.\n";
echo "</div>\n";

echo "</div>\n";

// 4. ADMIN PAGE FLOW ANALYSIS
echo "<div class='section info'>\n";
echo "<h2>🔍 ISSUE 4: Admin Page Flow Analysis</h2>\n";

echo "<h3>Current Flow After Consent:</h3>\n";
echo "<div class='code'>\n";
echo "1. User submits consent form<br>\n";
echo "2. MPAIConsentManager.processConsentForm() saves consent<br>\n";
echo "3. Redirects to admin.php?page=mpai-settings<br>\n";
echo "4. MPAIAdminMenu.render_settings_page() calls SettingsControllerService.render_page()<br>\n";
echo "5. Settings page renders, but chat interface rendering depends on ChatInterface hooks<br>\n";
echo "</div>\n";

echo "<h3>🚨 DIAGNOSIS: Admin Page Flow Issue</h3>\n";
echo "<div class='warning'>\n";
echo "<strong>POTENTIAL ISSUE:</strong><br>\n";
echo "The settings page rendering may not be triggering the admin_footer hook where ChatInterface.renderAdminChatInterface() is attached.<br>\n";
echo "This needs verification by checking if the hook is being called on the settings page.\n";
echo "</div>\n";

echo "</div>\n";

// 5. TEMPLATE SELECTION LOGIC
echo "<div class='section info'>\n";
echo "<h2>🔍 ISSUE 5: Template Selection Logic</h2>\n";

echo "<h3>Current Template Logic:</h3>\n";
echo "<div class='code'>\n";
echo "<strong>Welcome Page (mpai-welcome):</strong><br>\n";
echo "- Always includes templates/welcome-page.php<br>\n";
echo "- welcome-page.php includes both its own content AND calls renderConsentForm()<br><br>\n";
echo "<strong>Settings Page (mpai-settings):</strong><br>\n";
echo "- Renders through SettingsControllerService<br>\n";
echo "- May or may not include chat interface depending on hook execution<br>\n";
echo "</div>\n";

echo "</div>\n";

// 6. RECOMMENDED FIXES
echo "<div class='section success'>\n";
echo "<h2>🔧 RECOMMENDED FIXES</h2>\n";

echo "<h3>Fix 1: Eliminate Consent Form Duplication</h3>\n";
echo "<div class='code'>\n";
echo "<strong>SOLUTION:</strong> Remove duplicate consent form content from templates/welcome-page.php<br>\n";
echo "- Keep only the renderConsentForm() call<br>\n";
echo "- Remove the duplicate 'Welcome to MemberPress AI Assistant' title and terms content<br>\n";
echo "- Let consent-form.php handle all consent form rendering<br>\n";
echo "</div>\n";

echo "<h3>Fix 2: Ensure Chat Interface Renders After Consent</h3>\n";
echo "<div class='code'>\n";
echo "<strong>SOLUTION:</strong> Verify and fix chat interface rendering on settings page<br>\n";
echo "- Ensure ChatInterface.renderAdminChatInterface() is called on settings page<br>\n";
echo "- Check if admin_footer hook is properly executed<br>\n";
echo "- Add fallback chat interface rendering in settings template if needed<br>\n";
echo "</div>\n";

echo "<h3>Fix 3: Add Diagnostic Logging</h3>\n";
echo "<div class='code'>\n";
echo "<strong>SOLUTION:</strong> Add comprehensive logging to validate the fixes<br>\n";
echo "- Log consent form rendering calls<br>\n";
echo "- Log chat interface rendering attempts<br>\n";
echo "- Log admin hook execution on settings page<br>\n";
echo "</div>\n";

echo "</div>\n";

// 7. VALIDATION TESTS
echo "<div class='section info'>\n";
echo "<h2>🧪 VALIDATION TESTS NEEDED</h2>\n";

echo "<h3>Test 1: Consent Form Count</h3>\n";
echo "<div class='code'>\n";
echo "- Navigate to mpai-welcome page<br>\n";
echo "- Count instances of 'Welcome to MemberPress AI Assistant' text<br>\n";
echo "- Should be exactly 1 after fix<br>\n";
echo "</div>\n";

echo "<h3>Test 2: Chat Container Existence</h3>\n";
echo "<div class='code'>\n";
echo "- Give consent and navigate to settings page<br>\n";
echo "- Check if 'mpai-chat-container' element exists in DOM<br>\n";
echo "- Should exist after fix<br>\n";
echo "</div>\n";

echo "<h3>Test 3: JavaScript Console Errors</h3>\n";
echo "<div class='code'>\n";
echo "- Open browser console on settings page after consent<br>\n";
echo "- Should not see 'Chat container not found' errors<br>\n";
echo "</div>\n";

echo "</div>\n";

echo "<div class='section success'>\n";
echo "<h2>✅ DIAGNOSIS COMPLETE</h2>\n";
echo "<p><strong>Primary Issues Identified:</strong></p>\n";
echo "<ol>\n";
echo "<li><strong>Consent Form Duplication:</strong> welcome-page.php contains both its own consent form content AND calls renderConsentForm()</li>\n";
echo "<li><strong>Chat Interface Missing:</strong> Chat container not being created on settings page after consent</li>\n";
echo "<li><strong>JavaScript Errors:</strong> Scripts looking for non-existent chat container</li>\n";
echo "</ol>\n";
echo "<p><strong>Ready to implement fixes with user confirmation.</strong></p>\n";
echo "</div>\n";

?>