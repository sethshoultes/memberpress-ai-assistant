<?php
/**
 * Consent Form and Chat Interface Diagnosis Script
 *
 * This script diagnoses the consent form duplication and chat interface loading issues.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once dirname(__DIR__, 4) . '/wp-load.php';
}

echo "<h1>MemberPress AI Assistant - Consent Form and Chat Interface Diagnosis</h1>\n";

// Check 1: Consent form template locations and usage
echo "<h2>1. Consent Form Template Analysis</h2>\n";

$consent_template = dirname(__DIR__) . '/templates/consent-form.php';
$welcome_template = dirname(__DIR__) . '/templates/welcome-page.php';

echo "<h3>Template Files:</h3>\n";
echo "<ul>\n";
echo "<li>Consent Form Template: " . ($consent_template && file_exists($consent_template) ? "✅ EXISTS" : "❌ MISSING") . "</li>\n";
echo "<li>Welcome Page Template: " . ($welcome_template && file_exists($welcome_template) ? "✅ EXISTS" : "❌ MISSING") . "</li>\n";
echo "</ul>\n";

// Check 2: Consent form rendering locations
echo "<h3>Consent Form Rendering Analysis:</h3>\n";
echo "<p><strong>DIAGNOSIS:</strong> The consent form appears in TWO separate templates:</p>\n";
echo "<ul>\n";
echo "<li><code>templates/consent-form.php</code> - Standalone consent form</li>\n";
echo "<li><code>templates/welcome-page.php</code> - Welcome page with embedded consent form</li>\n";
echo "</ul>\n";

echo "<p><strong>ISSUE:</strong> Both templates contain consent forms with similar functionality but different implementations:</p>\n";
echo "<ul>\n";
echo "<li>Consent form template: Uses traditional form submission</li>\n";
echo "<li>Welcome page template: Uses AJAX submission</li>\n";
echo "</ul>\n";

// Check 3: Chat interface consent validation
echo "<h2>2. Chat Interface Consent Validation Analysis</h2>\n";

$chat_interface_file = dirname(__DIR__) . '/src/ChatInterface.php';
if (file_exists($chat_interface_file)) {
    $chat_content = file_get_contents($chat_interface_file);
    
    echo "<h3>Chat Interface Consent Checks:</h3>\n";
    
    // Check for bypassed consent validation
    $frontend_bypass = strpos($chat_content, 'TEMPORARILY BYPASS CONSENT CHECK FOR TESTING') !== false;
    $admin_bypass = strpos($chat_content, 'TEMPORARILY BYPASS CONSENT CHECK FOR TESTING') !== false;
    $api_bypass = strpos($chat_content, 'TEMPORARILY BYPASS CONSENT CHECK FOR TESTING') !== false;
    
    echo "<ul>\n";
    echo "<li>Frontend consent check: " . ($frontend_bypass ? "❌ BYPASSED (lines 369-378)" : "✅ ACTIVE") . "</li>\n";
    echo "<li>Admin consent check: " . ($admin_bypass ? "❌ BYPASSED (lines 393-406)" : "✅ ACTIVE") . "</li>\n";
    echo "<li>API consent check: " . ($api_bypass ? "❌ BYPASSED (lines 884-896)" : "✅ ACTIVE") . "</li>\n";
    echo "</ul>\n";
    
    echo "<p><strong>CRITICAL ISSUE:</strong> All consent validation is currently bypassed with TODO comments!</p>\n";
} else {
    echo "<p>❌ ChatInterface.php not found</p>\n";
}

// Check 4: Consent manager functionality
echo "<h2>3. Consent Manager Analysis</h2>\n";

try {
    if (class_exists('\\MemberpressAiAssistant\\Admin\\MPAIConsentManager')) {
        $consent_manager = \MemberpressAiAssistant\Admin\MPAIConsentManager::getInstance();
        
        echo "<h3>Consent Manager Status:</h3>\n";
        echo "<ul>\n";
        echo "<li>Consent Manager Class: ✅ AVAILABLE</li>\n";
        
        // Check current user consent status
        $user_id = get_current_user_id();
        if ($user_id > 0) {
            $has_consented = $consent_manager->hasUserConsented($user_id);
            echo "<li>Current User Consent Status: " . ($has_consented ? "✅ CONSENTED" : "❌ NOT CONSENTED") . "</li>\n";
            
            // Check consent meta key
            $consent_meta = get_user_meta($user_id, 'mpai_has_consented', true);
            echo "<li>User Meta Value: " . ($consent_meta ? "✅ TRUE" : "❌ FALSE/EMPTY") . "</li>\n";
        } else {
            echo "<li>Current User: ❌ NOT LOGGED IN</li>\n";
        }
        echo "</ul>\n";
    } else {
        echo "<p>❌ MPAIConsentManager class not available</p>\n";
    }
} catch (Exception $e) {
    echo "<p>❌ Error checking consent manager: " . esc_html($e->getMessage()) . "</p>\n";
}

// Check 5: Admin menu and page routing
echo "<h2>4. Admin Menu and Page Routing Analysis</h2>\n";

echo "<h3>Page Routing Logic:</h3>\n";
echo "<p>The admin menu (<code>MPAIAdminMenu.php</code>) has the following routing:</p>\n";
echo "<ul>\n";
echo "<li><code>mpai-settings</code> → Settings page</li>\n";
echo "<li><code>mpai-welcome</code> → Welcome page with consent form</li>\n";
echo "</ul>\n";

echo "<p><strong>ROUTING ISSUE:</strong> The welcome page checks consent and redirects to settings if already consented, but there may be multiple paths leading to consent form display.</p>\n";

// Check 6: Plugin activation/deactivation hooks
echo "<h2>5. Plugin Lifecycle Analysis</h2>\n";

echo "<h3>Consent Reset on Deactivation:</h3>\n";
echo "<p>The <code>MPAIConsentManager</code> has a static method <code>resetAllConsents()</code> that clears all user consent data during plugin deactivation.</p>\n";
echo "<p><strong>ISSUE:</strong> After plugin reactivation, users need to consent again, but the routing may show multiple consent forms.</p>\n";

// Summary and recommendations
echo "<h2>6. DIAGNOSIS SUMMARY</h2>\n";

echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 20px 0;'>\n";
echo "<h3>🔍 ROOT CAUSE ANALYSIS:</h3>\n";
echo "<ol>\n";
echo "<li><strong>Consent Form Duplication:</strong> Two separate templates contain consent forms:\n";
echo "   <ul>\n";
echo "   <li><code>templates/consent-form.php</code> - Standalone form</li>\n";
echo "   <li><code>templates/welcome-page.php</code> - Embedded form</li>\n";
echo "   </ul>\n";
echo "</li>\n";
echo "<li><strong>Chat Interface Loading Despite Consent Issues:</strong> All consent validation is bypassed with 'TEMPORARILY BYPASS CONSENT CHECK FOR TESTING' comments in <code>ChatInterface.php</code></li>\n";
echo "</ol>\n";
echo "</div>\n";

echo "<div style='background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; margin: 20px 0;'>\n";
echo "<h3>🎯 MOST LIKELY SOURCES:</h3>\n";
echo "<ol>\n";
echo "<li><strong>Template Confusion:</strong> The system may be rendering both consent templates in certain scenarios</li>\n";
echo "<li><strong>Bypassed Consent Validation:</strong> Chat interface ignores consent status due to temporary bypass code</li>\n";
echo "</ol>\n";
echo "</div>\n";

echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 20px 0;'>\n";
echo "<h3>✅ RECOMMENDED FIXES:</h3>\n";
echo "<ol>\n";
echo "<li><strong>Consolidate Consent Forms:</strong> Use only one consent form template and ensure proper routing</li>\n";
echo "<li><strong>Re-enable Consent Validation:</strong> Remove the bypass code and restore proper consent checks in ChatInterface.php</li>\n";
echo "<li><strong>Fix Routing Logic:</strong> Ensure clear separation between welcome page and standalone consent form</li>\n";
echo "</ol>\n";
echo "</div>\n";

echo "<p><strong>Next Steps:</strong> Please confirm this diagnosis before proceeding with the fixes.</p>\n";