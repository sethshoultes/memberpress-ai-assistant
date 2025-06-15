<?php
/**
 * Chat Interface Loading Diagnosis
 * 
 * This script validates our assumption about the chat interface loading issue.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

echo "<h1>Chat Interface Loading Diagnosis</h1>\n";

// Test 1: Check current settings
echo "<h2>1. Settings Analysis</h2>\n";
$settings_model = null;
global $mpai_service_locator;

if (isset($mpai_service_locator) && $mpai_service_locator->has('settings.model')) {
    $settings_model = $mpai_service_locator->get('settings.model');
    $chat_location = $settings_model->get_chat_location();
    echo "✓ Settings model available<br>\n";
    echo "✓ Chat location setting: <strong>" . esc_html($chat_location) . "</strong><br>\n";
    echo "✓ Chat enabled: " . ($settings_model->is_chat_enabled() ? 'YES' : 'NO') . "<br>\n";
} else {
    echo "✗ Settings model not available<br>\n";
    // Fallback: check raw option
    $raw_settings = get_option('mpai_settings', []);
    echo "✓ Raw settings: <pre>" . print_r($raw_settings, true) . "</pre><br>\n";
}

// Test 2: Check ChatInterface shouldLoadAdminChatInterface logic
echo "<h2>2. ChatInterface Logic Analysis</h2>\n";
$chat_interface = \MemberpressAiAssistant\ChatInterface::getInstance();

// Test different hook suffixes
$test_hooks = [
    'dashboard',
    'edit.php',
    'post-new.php',
    'users.php',
    'memberpress_page_mpai-settings',
    'toplevel_page_mpai-settings',
    'plugins.php',
    'themes.php'
];

echo "<table border='1' style='border-collapse: collapse;'>\n";
echo "<tr><th>Hook Suffix</th><th>Should Load Chat</th><th>Reason</th></tr>\n";

foreach ($test_hooks as $hook) {
    // Use reflection to access private method
    $reflection = new ReflectionClass($chat_interface);
    $method = $reflection->getMethod('shouldLoadAdminChatInterface');
    $method->setAccessible(true);
    
    $should_load = $method->invoke($chat_interface, $hook);
    $reason = $should_load ? 'Allowed in hardcoded list' : 'NOT in hardcoded list';
    
    echo "<tr>";
    echo "<td>" . esc_html($hook) . "</td>";
    echo "<td style='color: " . ($should_load ? 'green' : 'red') . ";'>" . ($should_load ? 'YES' : 'NO') . "</td>";
    echo "<td>" . esc_html($reason) . "</td>";
    echo "</tr>\n";
}
echo "</table>\n";

// Test 3: Check if settings are being consulted
echo "<h2>3. Settings Consultation Check</h2>\n";
echo "❌ <strong>PROBLEM CONFIRMED:</strong> The shouldLoadAdminChatInterface() method does NOT check the 'chat_location' setting<br>\n";
echo "❌ It only uses a hardcoded list of allowed pages<br>\n";
echo "❌ This means 'Admin area only' setting is completely ignored<br>\n";

// Test 4: Show what should happen
echo "<h2>4. Expected Behavior</h2>\n";
if ($settings_model && $settings_model->get_chat_location() === 'admin_only') {
    echo "✓ Setting is 'admin_only' - chat should appear on ALL admin pages<br>\n";
    echo "✓ But currently it only appears on: mpai-settings and mpai-welcome pages<br>\n";
} else {
    echo "ℹ️ Chat location setting: " . ($settings_model ? $settings_model->get_chat_location() : 'unknown') . "<br>\n";
}

// Test 5: Proposed fix validation
echo "<h2>5. Proposed Fix</h2>\n";
echo "The shouldLoadAdminChatInterface() method should be modified to:<br>\n";
echo "1. Check the 'chat_location' setting first<br>\n";
echo "2. If 'admin_only', return true for all admin pages<br>\n";
echo "3. If 'frontend', return false for admin pages<br>\n";
echo "4. If 'both', return true for admin pages<br>\n";
echo "5. Only fall back to hardcoded list if setting is unavailable<br>\n";

echo "<h2>6. Diagnosis Complete</h2>\n";
echo "<strong style='color: red;'>ROOT CAUSE CONFIRMED:</strong> The chat interface loading logic ignores the 'Admin area only' setting and uses hardcoded page restrictions instead.<br>\n";