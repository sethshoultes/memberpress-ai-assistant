<?php
/**
 * Check Server-Side Diagnostic Logs
 * 
 * This tool checks the WordPress debug logs for our diagnostic entries
 * to see where the chat container rendering pipeline is failing.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once(dirname(dirname(dirname(dirname(__DIR__)))) . '/wp-load.php');
}

echo "<h2>Server-Side Diagnostic Log Analysis</h2>\n";
echo "<p>Checking WordPress debug logs for '[CHAT RENDER DIAGNOSIS]' entries...</p>\n";

// Check common WordPress debug log locations
$possible_log_files = [
    ABSPATH . 'wp-content/debug.log',
    ABSPATH . 'debug.log',
    WP_CONTENT_DIR . '/debug.log',
    ini_get('error_log'),
    '/tmp/wordpress-debug.log'
];

$log_entries = [];
$log_file_found = false;

foreach ($possible_log_files as $log_file) {
    if ($log_file && file_exists($log_file) && is_readable($log_file)) {
        echo "<p>✓ Found debug log: " . esc_html($log_file) . "</p>\n";
        $log_file_found = true;
        
        // Read the last 1000 lines of the log file
        $lines = file($log_file);
        $recent_lines = array_slice($lines, -1000);
        
        foreach ($recent_lines as $line) {
            if (strpos($line, '[CHAT RENDER DIAGNOSIS]') !== false) {
                $log_entries[] = trim($line);
            }
        }
        break;
    }
}

if (!$log_file_found) {
    echo "<p>⚠ No debug log files found. Debug logging may not be enabled.</p>\n";
    echo "<p>Common locations checked:</p>\n";
    echo "<ul>\n";
    foreach ($possible_log_files as $log_file) {
        if ($log_file) {
            echo "<li>" . esc_html($log_file) . " - " . (file_exists($log_file) ? "exists but not readable" : "not found") . "</li>\n";
        }
    }
    echo "</ul>\n";
    
    echo "<h3>Debug Logging Configuration</h3>\n";
    echo "<p>To enable debug logging, add these lines to wp-config.php:</p>\n";
    echo "<pre>\n";
    echo "define('WP_DEBUG', true);\n";
    echo "define('WP_DEBUG_LOG', true);\n";
    echo "define('WP_DEBUG_DISPLAY', false);\n";
    echo "</pre>\n";
} else {
    if (empty($log_entries)) {
        echo "<p>⚠ No '[CHAT RENDER DIAGNOSIS]' entries found in recent log entries.</p>\n";
        echo "<p>This suggests that either:</p>\n";
        echo "<ul>\n";
        echo "<li>The renderAdminChatInterface() method is not being called at all</li>\n";
        echo "<li>The diagnostic logging was not properly added</li>\n";
        echo "<li>The consent flow hasn't been tested yet</li>\n";
        echo "</ul>\n";
    } else {
        echo "<h3>Diagnostic Log Entries Found (" . count($log_entries) . ")</h3>\n";
        echo "<div style='background: #f0f0f0; padding: 10px; border: 1px solid #ccc; font-family: monospace; white-space: pre-wrap;'>\n";
        foreach ($log_entries as $entry) {
            echo esc_html($entry) . "\n";
        }
        echo "</div>\n";
    }
}

// Also check if we can manually trigger the diagnostic logging
echo "<h3>Manual Diagnostic Check</h3>\n";

// Check if user has consented
if (function_exists('get_current_user_id') && get_current_user_id()) {
    $user_id = get_current_user_id();
    $consent_meta = get_user_meta($user_id, 'mpai_has_consented', true);
    
    echo "<p>Current user ID: " . $user_id . "</p>\n";
    echo "<p>Consent meta value: " . var_export($consent_meta, true) . "</p>\n";
    echo "<p>Has consented (boolean): " . ($consent_meta ? 'YES' : 'NO') . "</p>\n";
    
    // Check if consent manager is available
    if (class_exists('\\MemberpressAiAssistant\\Admin\\MPAIConsentManager')) {
        $consent_manager = \MemberpressAiAssistant\Admin\MPAIConsentManager::getInstance();
        $has_consented = $consent_manager->hasUserConsented();
        echo "<p>Consent manager says: " . ($has_consented ? 'YES' : 'NO') . "</p>\n";
    } else {
        echo "<p>⚠ MPAIConsentManager class not available</p>\n";
    }
} else {
    echo "<p>⚠ No current user or user not logged in</p>\n";
}

// Check if ChatInterface class is available
if (class_exists('\\MemberpressAiAssistant\\ChatInterface')) {
    echo "<p>✓ ChatInterface class is available</p>\n";
    
    // Check if we're on the right page
    $current_page = isset($_GET['page']) ? $_GET['page'] : 'none';
    echo "<p>Current page parameter: " . esc_html($current_page) . "</p>\n";
    
    // Check if this would be considered a valid admin page for chat interface
    $allowed_pages = ['mpai-settings', 'mpai-welcome'];
    $should_load = in_array($current_page, $allowed_pages);
    echo "<p>Should load chat interface: " . ($should_load ? 'YES' : 'NO') . "</p>\n";
} else {
    echo "<p>⚠ ChatInterface class not available</p>\n";
}

echo "<h3>Next Steps</h3>\n";
echo "<p>Based on the console logs showing 'Chat container not found', the issue is confirmed to be server-side rendering.</p>\n";
echo "<p>If no diagnostic log entries are found, we need to:</p>\n";
echo "<ol>\n";
echo "<li>Ensure debug logging is enabled in WordPress</li>\n";
echo "<li>Test the consent flow on the settings page</li>\n";
echo "<li>Check if renderAdminChatInterface() is being called at all</li>\n";
echo "<li>Verify the consent validation is working correctly</li>\n";
echo "</ol>\n";