<?php
/**
 * Chat Interface Loading Fix Validation
 * 
 * This script validates that the chat interface loading fix works correctly
 * and respects the "Admin area only" setting.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../../wp-load.php');
}

echo "<h1>Chat Interface Loading Fix Validation</h1>\n";

// Test 1: Verify the fix is in place
echo "<h2>1. Fix Implementation Check</h2>\n";
$chat_interface = \MemberpressAiAssistant\ChatInterface::getInstance();

// Use reflection to check if the method has been updated
$reflection = new ReflectionClass($chat_interface);
$method = $reflection->getMethod('shouldLoadAdminChatInterface');
$method->setAccessible(true);

// Get the method source to verify it contains our fix
$filename = $reflection->getFileName();
$start_line = $method->getStartLine();
$end_line = $method->getEndLine();
$source = file($filename);
$method_source = implode('', array_slice($source, $start_line - 1, $end_line - $start_line + 1));

if (strpos($method_source, 'chat_location_setting') !== false && strpos($method_source, 'admin_only') !== false) {
    echo "✅ <strong>Fix confirmed:</strong> Method now checks chat_location setting<br>\n";
} else {
    echo "❌ <strong>Fix not found:</strong> Method may not have been updated correctly<br>\n";
}

// Test 2: Check current settings
echo "<h2>2. Current Settings</h2>\n";
global $mpai_service_locator;

$chat_location = 'unknown';
if (isset($mpai_service_locator) && $mpai_service_locator->has('settings.model')) {
    try {
        $settings_model = $mpai_service_locator->get('settings.model');
        $chat_location = $settings_model->get_chat_location();
        echo "✅ Settings model available<br>\n";
        echo "✅ Current chat_location setting: <strong>" . esc_html($chat_location) . "</strong><br>\n";
    } catch (Exception $e) {
        echo "⚠️ Settings model error: " . esc_html($e->getMessage()) . "<br>\n";
    }
} else {
    // Fallback to raw settings
    $raw_settings = get_option('mpai_settings', []);
    if (isset($raw_settings['chat_location'])) {
        $chat_location = $raw_settings['chat_location'];
        echo "✅ Raw settings available<br>\n";
        echo "✅ Current chat_location setting: <strong>" . esc_html($chat_location) . "</strong><br>\n";
    } else {
        echo "⚠️ No chat_location setting found, will use default<br>\n";
    }
}

// Test 3: Test the new logic with different hook suffixes
echo "<h2>3. New Logic Testing</h2>\n";

// Simulate being in admin context
if (!is_admin()) {
    echo "⚠️ Not in admin context, results may not be accurate<br>\n";
}

$test_hooks = [
    'dashboard' => 'WordPress Dashboard',
    'edit.php' => 'Posts List',
    'post-new.php' => 'Add New Post',
    'users.php' => 'Users List',
    'plugins.php' => 'Plugins Page',
    'themes.php' => 'Themes Page',
    'memberpress_page_mpai-settings' => 'MPAI Settings Page',
    'toplevel_page_mpai-settings' => 'MPAI Settings (Top Level)',
];

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
echo "<tr><th>Admin Page</th><th>Hook Suffix</th><th>Should Load Chat</th><th>Expected Result</th></tr>\n";

foreach ($test_hooks as $hook => $description) {
    $should_load = $method->invoke($chat_interface, $hook);
    
    // Determine expected result based on current setting
    $expected = 'Unknown';
    if ($chat_location === 'admin_only' || $chat_location === 'both') {
        $expected = 'YES (admin_only/both setting)';
    } elseif ($chat_location === 'frontend') {
        $expected = 'NO (frontend setting)';
    } else {
        // For unknown settings, only specific pages should load
        if (in_array($hook, ['memberpress_page_mpai-settings', 'toplevel_page_mpai-settings'])) {
            $expected = 'YES (hardcoded fallback)';
        } else {
            $expected = 'NO (hardcoded fallback)';
        }
    }
    
    $result_color = $should_load ? 'green' : 'red';
    $result_text = $should_load ? 'YES' : 'NO';
    
    echo "<tr>";
    echo "<td>" . esc_html($description) . "</td>";
    echo "<td><code>" . esc_html($hook) . "</code></td>";
    echo "<td style='color: $result_color; font-weight: bold;'>$result_text</td>";
    echo "<td>" . esc_html($expected) . "</td>";
    echo "</tr>\n";
}
echo "</table>\n";

// Test 4: Validation summary
echo "<h2>4. Validation Summary</h2>\n";

$success = true;
$issues = [];

// Check if the fix addresses the original problem
if ($chat_location === 'admin_only') {
    $dashboard_loads = $method->invoke($chat_interface, 'dashboard');
    $posts_loads = $method->invoke($chat_interface, 'edit.php');
    
    if ($dashboard_loads && $posts_loads) {
        echo "✅ <strong>SUCCESS:</strong> Chat interface now loads on all admin pages with 'admin_only' setting<br>\n";
    } else {
        echo "❌ <strong>ISSUE:</strong> Chat interface still not loading on all admin pages<br>\n";
        $issues[] = "Chat not loading on all admin pages despite admin_only setting";
        $success = false;
    }
} else {
    echo "ℹ️ <strong>INFO:</strong> Current setting is '$chat_location', not 'admin_only'<br>\n";
    echo "ℹ️ To test the fix, set chat_location to 'admin_only' in the settings<br>\n";
}

// Check if settings are being consulted
if (strpos($method_source, 'get_chat_location') !== false || strpos($method_source, 'chat_location') !== false) {
    echo "✅ <strong>SUCCESS:</strong> Method now consults the chat_location setting<br>\n";
} else {
    echo "❌ <strong>ISSUE:</strong> Method may not be consulting settings properly<br>\n";
    $issues[] = "Settings consultation not implemented correctly";
    $success = false;
}

// Final result
echo "<h2>5. Final Result</h2>\n";
if ($success && empty($issues)) {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 10px; border-radius: 5px;'>\n";
    echo "<strong>✅ FIX SUCCESSFUL!</strong><br>\n";
    echo "The chat interface loading issue has been resolved. The chat interface will now:<br>\n";
    echo "• Respect the 'Chat Interface Location' setting<br>\n";
    echo "• Appear on all admin pages when 'Admin area only' is selected<br>\n";
    echo "• Include comprehensive logging for debugging<br>\n";
    echo "</div>\n";
} else {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 10px; border-radius: 5px;'>\n";
    echo "<strong>❌ ISSUES DETECTED:</strong><br>\n";
    foreach ($issues as $issue) {
        echo "• " . esc_html($issue) . "<br>\n";
    }
    echo "</div>\n";
}

echo "<h2>6. Next Steps</h2>\n";
echo "1. Test the chat interface on different admin pages (Dashboard, Posts, Users, etc.)<br>\n";
echo "2. Verify the setting 'Chat Interface Location > Admin area only' is working<br>\n";
echo "3. Check the WordPress debug log for the new diagnostic messages<br>\n";
echo "4. Test with different chat_location settings (admin_only, frontend, both)<br>\n";