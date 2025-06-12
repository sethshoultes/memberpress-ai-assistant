<?php
/**
 * Chat Container Diagnosis Tool
 * 
 * This tool diagnoses why the chat container is not appearing in the DOM
 * despite all previous fixes.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once dirname(__DIR__, 4) . '/wp-load.php';
}

// Ensure we're in admin context
if (!is_admin()) {
    wp_redirect(admin_url('admin.php?page=mpai-settings'));
    exit;
}

echo "<h1>Chat Container Diagnosis</h1>\n";
echo "<div style='font-family: monospace; background: #f0f0f0; padding: 20px; margin: 20px 0;'>\n";

// Test 1: Check if we're on the right page
echo "<h2>1. Page Detection Test</h2>\n";
$current_page = isset($_GET['page']) ? $_GET['page'] : 'none';
$current_screen = get_current_screen();
$screen_id = $current_screen ? $current_screen->id : 'unknown';

echo "Current page parameter: <strong>$current_page</strong><br>\n";
echo "Current screen ID: <strong>$screen_id</strong><br>\n";
echo "Is mpai-settings page: <strong>" . ($current_page === 'mpai-settings' ? 'YES' : 'NO') . "</strong><br>\n";

// Test 2: Check consent status
echo "<h2>2. Consent Status Test</h2>\n";
$consent_manager = \MemberpressAiAssistant\Admin\MPAIConsentManager::getInstance();
$has_consented = $consent_manager->hasUserConsented();
$user_id = get_current_user_id();

echo "Current user ID: <strong>$user_id</strong><br>\n";
echo "User has consented: <strong>" . ($has_consented ? 'YES' : 'NO') . "</strong><br>\n";

if ($user_id > 0) {
    $consent_meta = get_user_meta($user_id, 'mpai_has_consented', true);
    echo "Raw consent meta value: <strong>" . var_export($consent_meta, true) . "</strong><br>\n";
}

// Test 3: Check template file existence
echo "<h2>3. Template File Existence Test</h2>\n";
$settings_template = MPAI_PLUGIN_DIR . 'templates/settings-page.php';
$consent_template = MPAI_PLUGIN_DIR . 'templates/consent-form-inline.php';
$chat_template = MPAI_PLUGIN_DIR . 'templates/chat-interface.php';

echo "Settings template exists: <strong>" . (file_exists($settings_template) ? 'YES' : 'NO') . "</strong><br>\n";
echo "Consent template exists: <strong>" . (file_exists($consent_template) ? 'YES' : 'NO') . "</strong><br>\n";
echo "Chat template exists: <strong>" . (file_exists($chat_template) ? 'YES' : 'NO') . "</strong><br>\n";

if (file_exists($settings_template)) {
    echo "Settings template path: <strong>$settings_template</strong><br>\n";
}

// Test 4: Check if settings page is being rendered through the correct path
echo "<h2>4. Settings Rendering Path Test</h2>\n";

// Get service locator
global $mpai_service_locator;
echo "Service locator available: <strong>" . (isset($mpai_service_locator) ? 'YES' : 'NO') . "</strong><br>\n";

if (isset($mpai_service_locator)) {
    $has_settings_controller = $mpai_service_locator->has('settings_controller');
    echo "Settings controller available: <strong>" . ($has_settings_controller ? 'YES' : 'NO') . "</strong><br>\n";
    
    if ($has_settings_controller) {
        $settings_controller = $mpai_service_locator->get('settings_controller');
        echo "Settings controller class: <strong>" . get_class($settings_controller) . "</strong><br>\n";
    }
}

// Test 5: Simulate template rendering to see what happens
echo "<h2>5. Template Rendering Simulation</h2>\n";

try {
    // Start output buffering to capture what would be rendered
    ob_start();
    
    // Simulate the template variables that should be available
    $current_tab = 'general';
    $tabs = ['general' => 'General Settings'];
    $page_slug = 'mpai-settings';
    
    // Include the settings template
    if (file_exists($settings_template)) {
        include $settings_template;
        $rendered_content = ob_get_clean();
        
        echo "Template rendered successfully: <strong>YES</strong><br>\n";
        echo "Rendered content length: <strong>" . strlen($rendered_content) . " characters</strong><br>\n";
        
        // Check if key elements are in the rendered content
        $has_assistant_container = strpos($rendered_content, 'mpai-assistant-container') !== false;
        $has_consent_form = strpos($rendered_content, 'mpai-inline-consent-container') !== false;
        $has_chat_container = strpos($rendered_content, 'mpai-chat-container') !== false;
        
        echo "Contains #mpai-assistant-container: <strong>" . ($has_assistant_container ? 'YES' : 'NO') . "</strong><br>\n";
        echo "Contains consent form: <strong>" . ($has_consent_form ? 'YES' : 'NO') . "</strong><br>\n";
        echo "Contains chat container: <strong>" . ($has_chat_container ? 'YES' : 'NO') . "</strong><br>\n";
        
        // Show a snippet of the rendered content around the assistant container
        if ($has_assistant_container) {
            $pos = strpos($rendered_content, 'mpai-assistant-container');
            $snippet_start = max(0, $pos - 100);
            $snippet_length = 300;
            $snippet = substr($rendered_content, $snippet_start, $snippet_length);
            echo "<h3>Content around #mpai-assistant-container:</h3>\n";
            echo "<pre style='background: #fff; padding: 10px; border: 1px solid #ccc; overflow-x: auto;'>" . htmlspecialchars($snippet) . "</pre>\n";
        }
        
    } else {
        ob_end_clean();
        echo "Template rendering failed: <strong>Template file not found</strong><br>\n";
    }
    
} catch (Exception $e) {
    if (ob_get_level()) {
        ob_end_clean();
    }
    echo "Template rendering failed: <strong>" . $e->getMessage() . "</strong><br>\n";
}

// Test 6: Check if ChatInterface is being called
echo "<h2>6. ChatInterface Rendering Test</h2>\n";

$chat_interface = \MemberpressAiAssistant\ChatInterface::getInstance();
echo "ChatInterface instance available: <strong>YES</strong><br>\n";

// Check if the renderAdminChatInterface method would be called
$reflection = new ReflectionClass($chat_interface);
$shouldLoadMethod = $reflection->getMethod('shouldLoadAdminChatInterface');
$shouldLoadMethod->setAccessible(true);
$should_load = $shouldLoadMethod->invoke($chat_interface, $screen_id);

echo "Should load admin chat interface: <strong>" . ($should_load ? 'YES' : 'NO') . "</strong><br>\n";

// Check if the duplicate rendering flag is set
$duplicate_flag_set = defined('MPAI_CHAT_INTERFACE_RENDERED');
echo "Duplicate rendering flag set: <strong>" . ($duplicate_flag_set ? 'YES' : 'NO') . "</strong><br>\n";

// Test 7: Check WordPress hooks and actions
echo "<h2>7. WordPress Hooks Test</h2>\n";

global $wp_filter;

// Check if admin_footer hook has our chat interface renderer
$admin_footer_hooks = isset($wp_filter['admin_footer']) ? $wp_filter['admin_footer'] : null;
$has_chat_renderer = false;

if ($admin_footer_hooks) {
    foreach ($admin_footer_hooks->callbacks as $priority => $callbacks) {
        foreach ($callbacks as $callback) {
            if (is_array($callback['function']) && 
                $callback['function'][0] instanceof \MemberpressAiAssistant\ChatInterface &&
                $callback['function'][1] === 'renderAdminChatInterface') {
                $has_chat_renderer = true;
                break 2;
            }
        }
    }
}

echo "ChatInterface::renderAdminChatInterface hooked to admin_footer: <strong>" . ($has_chat_renderer ? 'YES' : 'NO') . "</strong><br>\n";

// Test 8: Check for PHP errors that might prevent rendering
echo "<h2>8. Error Detection Test</h2>\n";

// Check if error reporting is on
$error_reporting = error_reporting();
$display_errors = ini_get('display_errors');

echo "Error reporting level: <strong>$error_reporting</strong><br>\n";
echo "Display errors: <strong>$display_errors</strong><br>\n";

// Check for recent PHP errors in the log
$error_log_path = ini_get('error_log');
if ($error_log_path && file_exists($error_log_path)) {
    echo "Error log path: <strong>$error_log_path</strong><br>\n";
    
    // Get last few lines of error log
    $error_lines = array_slice(file($error_log_path), -10);
    $recent_mpai_errors = array_filter($error_lines, function($line) {
        return strpos($line, 'MPAI') !== false || strpos($line, 'memberpress-ai') !== false;
    });
    
    if (!empty($recent_mpai_errors)) {
        echo "<h3>Recent MPAI-related errors:</h3>\n";
        echo "<pre style='background: #fff; padding: 10px; border: 1px solid #ccc; color: red;'>" . htmlspecialchars(implode('', $recent_mpai_errors)) . "</pre>\n";
    } else {
        echo "No recent MPAI-related errors found<br>\n";
    }
} else {
    echo "Error log not accessible or not configured<br>\n";
}

echo "</div>\n";

// Summary and Recommendations
echo "<h2>Diagnosis Summary</h2>\n";
echo "<div style='background: #e7f3ff; padding: 15px; border-left: 4px solid #2196F3; margin: 20px 0;'>\n";

if (!$has_consented) {
    echo "<strong>PRIMARY ISSUE IDENTIFIED:</strong> User has not consented to use the AI Assistant.<br>\n";
    echo "<strong>EXPECTED BEHAVIOR:</strong> The consent form should be displayed in the #mpai-assistant-container div.<br>\n";
    echo "<strong>NEXT STEPS:</strong> Check if the consent form is actually being rendered in the DOM.<br>\n";
} else {
    echo "<strong>PRIMARY ISSUE IDENTIFIED:</strong> User has consented but chat interface is not rendering.<br>\n";
    echo "<strong>EXPECTED BEHAVIOR:</strong> The chat interface should be displayed in the #mpai-assistant-container div.<br>\n";
    echo "<strong>NEXT STEPS:</strong> Check if the chat interface template is being included properly.<br>\n";
}

echo "</div>\n";

// Action buttons
echo "<div style='margin: 20px 0;'>\n";
echo "<a href='" . admin_url('admin.php?page=mpai-settings') . "' class='button button-primary'>Return to Settings Page</a>\n";
echo "<a href='" . admin_url('admin.php?page=mpai-settings&tab=general') . "' class='button'>Settings Page (General Tab)</a>\n";
echo "</div>\n";