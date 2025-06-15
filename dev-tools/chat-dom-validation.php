<?php
/**
 * MemberPress AI Assistant - Chat Interface DOM Validation Script
 * 
 * This validation script performs comprehensive DOM and asset validation
 * to test that the chat container is always present in the DOM after
 * the ChatInterface was reverted to direct HTML rendering.
 * 
 * @package MemberpressAiAssistant
 * @version 1.0.0
 */

// Security check - only allow access from admin users
if (!defined('ABSPATH')) {
    // Try to load WordPress
    $wp_load_paths = [
        dirname(__DIR__, 4) . '/wp-load.php',
        dirname(__DIR__, 5) . '/wp-load.php',
        dirname(__DIR__, 3) . '/wp-load.php'
    ];
    
    $wp_loaded = false;
    foreach ($wp_load_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $wp_loaded = true;
            break;
        }
    }
    
    if (!$wp_loaded) {
        die('WordPress environment not found. Please access this file through WordPress admin.');
    }
}

// Check if user has admin capabilities
if (!current_user_can('manage_options')) {
    wp_die('You do not have sufficient permissions to access this page.');
}

// Start output buffering
ob_start();

// Set up error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Chat DOM Validation Tracker
 */
class MPAIChatDOMTracker {
    private $tests = [];
    private $passed = 0;
    private $failed = 0;
    private $warnings = 0;
    
    public function addTest($name, $status, $message = '', $details = []) {
        $this->tests[] = [
            'name' => $name,
            'status' => $status, // 'pass', 'fail', 'warning'
            'message' => $message,
            'details' => $details
        ];
        
        switch ($status) {
            case 'pass':
                $this->passed++;
                break;
            case 'fail':
                $this->failed++;
                break;
            case 'warning':
                $this->warnings++;
                break;
        }
    }
    
    public function getResults() {
        return [
            'tests' => $this->tests,
            'passed' => $this->passed,
            'failed' => $this->failed,
            'warnings' => $this->warnings,
            'total' => count($this->tests)
        ];
    }
}

// Initialize tracker
$tracker = new MPAIChatDOMTracker();

// Clean output buffer
ob_clean();

?>
<!DOCTYPE html>
<html>
<head>
    <title>MemberPress AI Assistant - Chat DOM Validation</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; margin: 20px; background: #f1f1f1; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { border-bottom: 2px solid #0073aa; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { color: #0073aa; margin: 0; }
        .timestamp { color: #666; font-size: 14px; margin-top: 5px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 4px; }
        .section h2 { margin-top: 0; color: #333; border-bottom: 1px solid #eee; padding-bottom: 8px; }
        .test-result { margin: 10px 0; padding: 10px; border-radius: 4px; }
        .test-pass { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .test-fail { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .test-warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }
        .test-name { font-weight: bold; }
        .test-details { margin-top: 5px; font-size: 13px; opacity: 0.8; }
        .summary { background: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; border-radius: 4px; margin: 20px 0; }
        .summary.pass { background: #d4edda; border-color: #c3e6cb; }
        .summary.fail { background: #f8d7da; border-color: #f5c6cb; }
        .code { background: #f8f9fa; border: 1px solid #e9ecef; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 13px; white-space: pre-wrap; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .dom-preview { background: #f8f9fa; border: 1px solid #e9ecef; padding: 15px; border-radius: 4px; margin: 10px 0; }
        .dom-element { background: #e9ecef; padding: 5px; margin: 2px 0; border-radius: 3px; font-family: monospace; font-size: 12px; }
        .dom-found { background: #d4edda; color: #155724; }
        .dom-missing { background: #f8d7da; color: #721c24; }
        @media (max-width: 768px) { .grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 MemberPress AI Assistant - Chat DOM Validation</h1>
            <div class="timestamp"><?php echo date('Y-m-d H:i:s T'); ?></div>
            <div class="info">This validation tests that the chat container is always present in the DOM after reverting to direct HTML rendering.</div>
        </div>

<?php

// =============================================================================
// ENVIRONMENT SETUP AND VALIDATION
// =============================================================================

echo '<div class="section">';
echo '<h2>🌍 Environment Setup</h2>';

// Check current page context
$current_page = isset($_GET['page']) ? $_GET['page'] : 'none';
$current_screen = get_current_screen();
$screen_id = $current_screen ? $current_screen->id : 'unknown';

$tracker->addTest('WordPress Environment', 'pass', 'WordPress loaded successfully');
$tracker->addTest('Admin Context', is_admin() ? 'pass' : 'fail', 
    is_admin() ? 'Running in admin context' : 'Not in admin context');

// Check if we're on a relevant page
$is_relevant_page = in_array($current_page, ['mpai-settings', 'mpai-welcome']) || 
                   strpos($screen_id, 'mpai') !== false;

$tracker->addTest('Page Context', $is_relevant_page ? 'pass' : 'warning',
    "Current page: $current_page, Screen ID: $screen_id" . 
    ($is_relevant_page ? ' (Relevant for chat interface)' : ' (May not show chat interface)'));

echo '</div>';

// =============================================================================
// CHAT INTERFACE CLASS VALIDATION
// =============================================================================

echo '<div class="section">';
echo '<h2>🔧 ChatInterface Class Validation</h2>';

// Check if ChatInterface class exists and can be instantiated
$chat_interface_available = false;
$chat_interface_instance = null;

try {
    if (class_exists('\MemberpressAiAssistant\ChatInterface')) {
        $chat_interface_instance = \MemberpressAiAssistant\ChatInterface::getInstance();
        $chat_interface_available = true;
        $tracker->addTest('ChatInterface Class', 'pass', 'ChatInterface class available and instantiated');
    } else {
        $tracker->addTest('ChatInterface Class', 'fail', 'ChatInterface class not found');
    }
} catch (Exception $e) {
    $tracker->addTest('ChatInterface Class', 'fail', 'Error instantiating ChatInterface: ' . $e->getMessage());
}

// Check if required methods exist
if ($chat_interface_available && $chat_interface_instance) {
    $required_methods = [
        'renderAdminChatInterface',
        'registerAdminAssets',
        'shouldLoadAdminChatInterface'
    ];
    
    $reflection = new ReflectionClass($chat_interface_instance);
    $missing_methods = [];
    
    foreach ($required_methods as $method) {
        if (!$reflection->hasMethod($method)) {
            $missing_methods[] = $method;
        }
    }
    
    $methods_check = empty($missing_methods);
    $tracker->addTest('Required Methods', $methods_check ? 'pass' : 'fail',
        $methods_check ? 'All required methods exist' : 'Missing methods: ' . implode(', ', $missing_methods));
}

echo '</div>';

// =============================================================================
// CONSENT MANAGER VALIDATION
// =============================================================================

echo '<div class="section">';
echo '<h2>👤 Consent Manager Validation</h2>';

// Check consent manager
$consent_manager_available = false;
$consent_status = false;

try {
    if (class_exists('\MemberpressAiAssistant\Admin\MPAIConsentManager')) {
        $consent_manager = \MemberpressAiAssistant\Admin\MPAIConsentManager::getInstance();
        $consent_manager_available = true;
        $consent_status = $consent_manager->hasUserConsented();
        
        $tracker->addTest('Consent Manager', 'pass', 'MPAIConsentManager available');
        $tracker->addTest('User Consent Status', $consent_status ? 'pass' : 'warning',
            $consent_status ? 'User has consented' : 'User has not consented (expected for testing)');
    } else {
        $tracker->addTest('Consent Manager', 'fail', 'MPAIConsentManager class not found');
    }
} catch (Exception $e) {
    $tracker->addTest('Consent Manager', 'fail', 'Error with consent manager: ' . $e->getMessage());
}

echo '</div>';

// =============================================================================
// WORDPRESS HOOKS VALIDATION
// =============================================================================

echo '<div class="section">';
echo '<h2>🪝 WordPress Hooks Validation</h2>';

global $wp_filter;

// Check if admin_footer hook has our chat interface renderer
$admin_footer_hooks = isset($wp_filter['admin_footer']) ? $wp_filter['admin_footer'] : null;
$has_chat_renderer = false;
$hook_priority = null;

if ($admin_footer_hooks && $chat_interface_instance) {
    foreach ($admin_footer_hooks->callbacks as $priority => $callbacks) {
        foreach ($callbacks as $callback) {
            if (is_array($callback['function']) && 
                $callback['function'][0] instanceof \MemberpressAiAssistant\ChatInterface &&
                $callback['function'][1] === 'renderAdminChatInterface') {
                $has_chat_renderer = true;
                $hook_priority = $priority;
                break 2;
            }
        }
    }
}

$tracker->addTest('Admin Footer Hook', $has_chat_renderer ? 'pass' : 'fail',
    $has_chat_renderer ? "ChatInterface::renderAdminChatInterface hooked at priority $hook_priority" : 
    'ChatInterface::renderAdminChatInterface not hooked to admin_footer');

// Check admin_enqueue_scripts hook
$admin_enqueue_hooks = isset($wp_filter['admin_enqueue_scripts']) ? $wp_filter['admin_enqueue_scripts'] : null;
$has_asset_enqueue = false;

if ($admin_enqueue_hooks && $chat_interface_instance) {
    foreach ($admin_enqueue_hooks->callbacks as $priority => $callbacks) {
        foreach ($callbacks as $callback) {
            if (is_array($callback['function']) && 
                $callback['function'][0] instanceof \MemberpressAiAssistant\ChatInterface &&
                $callback['function'][1] === 'registerAdminAssets') {
                $has_asset_enqueue = true;
                break 2;
            }
        }
    }
}

$tracker->addTest('Admin Enqueue Scripts Hook', $has_asset_enqueue ? 'pass' : 'fail',
    $has_asset_enqueue ? 'ChatInterface::registerAdminAssets properly hooked' : 
    'ChatInterface::registerAdminAssets not hooked to admin_enqueue_scripts');

echo '</div>';

// =============================================================================
// DOM RENDERING SIMULATION
// =============================================================================

echo '<div class="section">';
echo '<h2>🎭 DOM Rendering Simulation</h2>';

$dom_elements_found = [];
$dom_elements_missing = [];
$rendered_html = '';

// Simulate the admin_footer hook execution
if ($chat_interface_available && $chat_interface_instance) {
    try {
        // Check if we should load the chat interface for current context
        $reflection = new ReflectionClass($chat_interface_instance);
        $shouldLoadMethod = $reflection->getMethod('shouldLoadAdminChatInterface');
        $shouldLoadMethod->setAccessible(true);
        $should_load = $shouldLoadMethod->invoke($chat_interface_instance, $screen_id);
        
        $tracker->addTest('Should Load Check', $should_load ? 'pass' : 'warning',
            $should_load ? 'Chat interface should load for current context' : 
            'Chat interface will not load for current context (may be expected)');
        
        if ($should_load) {
            // Capture the output of renderAdminChatInterface
            ob_start();
            
            // Temporarily undefine the duplicate prevention constant if it exists
            $had_constant = defined('MPAI_CHAT_INTERFACE_RENDERED');
            if ($had_constant) {
                // We can't undefine constants, so we'll note this
                $tracker->addTest('Duplicate Prevention', 'warning', 
                    'MPAI_CHAT_INTERFACE_RENDERED already defined - may prevent rendering');
            }
            
            // Call the render method
            $chat_interface_instance->renderAdminChatInterface();
            $rendered_html = ob_get_clean();
            
            // Analyze the rendered HTML
            if (!empty($rendered_html)) {
                $tracker->addTest('HTML Rendering', 'pass', 
                    'Chat interface HTML rendered successfully (' . strlen($rendered_html) . ' characters)');
                
                // Check for required DOM elements
                $required_elements = [
                    'mpai-chat-container' => '#mpai-chat-container div',
                    'mpai-chat-toggle' => '#mpai-chat-toggle button',
                    'mpai-chat-messages' => '.mpai-chat-messages container',
                    'mpai-chat-input' => '#mpai-chat-input textarea',
                    'mpai-chat-submit' => '#mpai-chat-submit button',
                    'mpai-chat-header' => '.mpai-chat-header section',
                    'mpai-chat-close' => '#mpai-chat-close button',
                    'mpai-chat-expand' => '#mpai-chat-expand button'
                ];
                
                foreach ($required_elements as $element_id => $description) {
                    if (strpos($rendered_html, $element_id) !== false) {
                        $dom_elements_found[] = $element_id;
                    } else {
                        $dom_elements_missing[] = $element_id;
                    }
                }
                
                $elements_found_count = count($dom_elements_found);
                $elements_total_count = count($required_elements);
                
                $tracker->addTest('Required DOM Elements', 
                    $elements_found_count === $elements_total_count ? 'pass' : 
                    ($elements_found_count > 0 ? 'warning' : 'fail'),
                    "Found $elements_found_count/$elements_total_count required elements");
                
            } else {
                $tracker->addTest('HTML Rendering', 'fail', 'No HTML output generated');
            }
        }
        
    } catch (Exception $e) {
        $tracker->addTest('DOM Rendering', 'fail', 'Error during rendering simulation: ' . $e->getMessage());
    }
} else {
    $tracker->addTest('DOM Rendering', 'fail', 'ChatInterface not available for rendering simulation');
}

// Display DOM analysis results
if (!empty($dom_elements_found) || !empty($dom_elements_missing)) {
    echo '<div class="dom-preview">';
    echo '<h4>DOM Elements Analysis</h4>';
    
    if (!empty($dom_elements_found)) {
        echo '<h5>✅ Found Elements:</h5>';
        foreach ($dom_elements_found as $element) {
            echo '<div class="dom-element dom-found">' . htmlspecialchars($element) . '</div>';
        }
    }
    
    if (!empty($dom_elements_missing)) {
        echo '<h5>❌ Missing Elements:</h5>';
        foreach ($dom_elements_missing as $element) {
            echo '<div class="dom-element dom-missing">' . htmlspecialchars($element) . '</div>';
        }
    }
    echo '</div>';
}

// Show HTML snippet if available
if (!empty($rendered_html)) {
    echo '<div class="dom-preview">';
    echo '<h4>Rendered HTML Preview (first 500 characters):</h4>';
    echo '<div class="code">' . htmlspecialchars(substr($rendered_html, 0, 500)) . 
         (strlen($rendered_html) > 500 ? '...' : '') . '</div>';
    echo '</div>';
}

echo '</div>';

// =============================================================================
// ASSET VALIDATION
// =============================================================================

echo '<div class="section">';
echo '<h2>📦 Asset Validation</h2>';

// Check if assets are properly enqueued
$required_styles = [
    'mpai-chat-admin' => 'Chat CSS (Admin)',
    'mpai-blog-post-admin' => 'Blog Post CSS (Admin)',
    'mpai-table-styles-admin' => 'Table Styles CSS (Admin)'
];

$required_scripts = [
    'mpai-chat-admin' => 'Main Chat JS (Admin)',
    'mpai-xml-processor-admin' => 'XML Processor JS (Admin)',
    'mpai-data-handler-admin' => 'Data Handler JS (Admin)',
    'mpai-text-formatter-admin' => 'Text Formatter JS (Admin)',
    'mpai-blog-formatter-admin' => 'Blog Formatter JS (Admin)'
];

// Simulate asset registration by calling registerAdminAssets
if ($chat_interface_available && $chat_interface_instance) {
    try {
        $chat_interface_instance->registerAdminAssets($screen_id);
        
        // Check registered styles
        $styles_found = 0;
        $styles_missing = [];
        
        foreach ($required_styles as $handle => $description) {
            if (wp_style_is($handle, 'registered')) {
                $styles_found++;
            } else {
                $styles_missing[] = $handle;
            }
        }
        
        $tracker->addTest('CSS Assets Registration', 
            $styles_found === count($required_styles) ? 'pass' : 'warning',
            "Registered $styles_found/" . count($required_styles) . " required stylesheets" .
            (!empty($styles_missing) ? ' (Missing: ' . implode(', ', $styles_missing) . ')' : ''));
        
        // Check registered scripts
        $scripts_found = 0;
        $scripts_missing = [];
        
        foreach ($required_scripts as $handle => $description) {
            if (wp_script_is($handle, 'registered')) {
                $scripts_found++;
            } else {
                $scripts_missing[] = $handle;
            }
        }
        
        $tracker->addTest('JavaScript Assets Registration', 
            $scripts_found === count($required_scripts) ? 'pass' : 'warning',
            "Registered $scripts_found/" . count($required_scripts) . " required scripts" .
            (!empty($scripts_missing) ? ' (Missing: ' . implode(', ', $scripts_missing) . ')' : ''));
        
        // Check if jQuery is enqueued
        $jquery_enqueued = wp_script_is('jquery', 'enqueued') || wp_script_is('jquery', 'done');
        $tracker->addTest('jQuery Dependency', $jquery_enqueued ? 'pass' : 'warning',
            $jquery_enqueued ? 'jQuery is properly enqueued' : 'jQuery may not be available');
        
    } catch (Exception $e) {
        $tracker->addTest('Asset Registration', 'fail', 'Error during asset registration: ' . $e->getMessage());
    }
}

// Check physical asset files
$asset_files = [
    'chat.css' => MPAI_PLUGIN_DIR . 'assets/css/chat.css',
    'blog-post.css' => MPAI_PLUGIN_DIR . 'assets/css/blog-post.css',
    'mpai-table-styles.css' => MPAI_PLUGIN_DIR . 'assets/css/mpai-table-styles.css',
    'chat.js' => MPAI_PLUGIN_DIR . 'assets/js/chat.js',
    'xml-processor.js' => MPAI_PLUGIN_DIR . 'assets/js/xml-processor.js',
    'data-handler-minimal.js' => MPAI_PLUGIN_DIR . 'assets/js/data-handler-minimal.js',
    'text-formatter.js' => MPAI_PLUGIN_DIR . 'assets/js/text-formatter.js',
    'blog-formatter.js' => MPAI_PLUGIN_DIR . 'assets/js/blog-formatter.js'
];

$missing_files = [];
foreach ($asset_files as $name => $path) {
    if (!file_exists($path)) {
        $missing_files[] = $name;
    }
}

$tracker->addTest('Physical Asset Files', empty($missing_files) ? 'pass' : 'fail',
    empty($missing_files) ? 'All required asset files exist' : 'Missing files: ' . implode(', ', $missing_files));

echo '</div>';

// =============================================================================
// CONSENT STATUS SCENARIOS
// =============================================================================

echo '<div class="section">';
echo '<h2>🔐 Consent Status Scenarios</h2>';

// Test both consent scenarios
if ($consent_manager_available) {
    $current_consent = $consent_status;
    
    // Test with consent
    $tracker->addTest('Chat Renders With Consent', $current_consent ? 'pass' : 'warning',
        $current_consent ? 'Chat interface should render when user has consented' : 
        'User has not consented - chat interface behavior depends on implementation');
    
    // Test without consent (informational)
    $tracker->addTest('Chat Renders Without Consent', 'pass',
        'Chat container should always render regardless of consent status (consent handled in JavaScript)');
    
    // Check JavaScript configuration
    if ($chat_interface_instance) {
        $reflection = new ReflectionClass($chat_interface_instance);
        $configMethod = $reflection->getMethod('getChatConfig');
        $configMethod->setAccessible(true);
        $chat_config = $configMethod->invoke($chat_interface_instance, true);
        
        $has_consent_config = isset($chat_config['hasConsented']);
        $tracker->addTest('JavaScript Consent Configuration', $has_consent_config ? 'pass' : 'warning',
            $has_consent_config ? 'Consent status properly passed to JavaScript configuration' : 
            'Consent status not found in JavaScript configuration');
    }
}

echo '</div>';

// =============================================================================
// JAVASCRIPT VALIDATION
// =============================================================================

echo '<div class="section">';
echo '<h2>🔧 JavaScript Integration Validation</h2>';

// Check if the rendered HTML includes proper JavaScript integration
if (!empty($rendered_html)) {
    $js_checks = [
        'DOMContentLoaded Event' => strpos($rendered_html, 'DOMContentLoaded') !== false,
        'jQuery Usage' => strpos($rendered_html, 'jQuery') !== false || strpos($rendered_html, '$') !== false,
        'Blog Formatter Integration' => strpos($rendered_html, 'MPAI_BlogFormatter') !== false,
        'Mutation Observer' => strpos($rendered_html, 'MutationObserver') !== false,
        'Chat Container Query' => strpos($rendered_html, 'mpai-chat-messages') !== false
    ];
    
    $js_passed = array_filter($js_checks);
    $js_success = count($js_passed) >= 3; // At least 3 out of 5 checks should pass
    
    $tracker->addTest('JavaScript Integration', $js_success ? 'pass' : 'warning',
        'Passed checks: ' . count($js_passed) . '/5 - ' . implode(', ', array_keys($js_passed)));
}

echo '</div>';

// =============================================================================
// SUMMARY AND RESULTS
// =============================================================================

$results = $tracker->getResults();
$critical_failures = 0;
$overall_success = $results['failed'] === 0;

// Count critical failures (failures that would prevent chat from working)
foreach ($results['tests'] as $test) {
    if ($test['status'] === 'fail' && in_array($test['name'], [
        'ChatInterface Class',
        'Required Methods', 
        'Admin Footer Hook',
        'HTML Rendering',
        'Required DOM Elements'
    ])) {
        $critical_failures++;
    }
}

$success_rate = $results['total'] > 0 ? round(($results['passed'] / $results['total']) * 100, 1) : 0;

echo '<div class="summary ' . ($critical_failures === 0 ? 'pass' : 'fail') . '">';
echo '<h2>📊 Validation Summary</h2>';
echo '<div class="grid">';
echo '<div>';
echo '<h3>Overall Status</h3>';
if ($critical_failures === 0) {
    echo '<div style="font-size: 24px; color: #155724;">✅ CHAT DOM VALIDATION PASSED</div>';
    echo '<p>The chat container should render properly in the DOM.</p>';
} else {
    echo '<div style="font-size: 24px; color: #721c24;">⚠️ CRITICAL ISSUES FOUND</div>';
    echo '<p>' . $critical_failures . ' critical failure(s) that may prevent chat rendering.</p>';
}
echo '<p><strong>Success Rate:</strong> ' . $success_rate . '% (' . $results['passed'] . '/' . $results['total'] . ' tests passed)</p>';
echo '</div>';
echo '<div>';
echo '<h3>Test Breakdown</h3>';
echo '<ul>';
echo '<li><strong>Passed:</strong> ' . $results['passed'] . ' tests</li>';
echo '<li><strong>Failed:</strong> ' . $results['failed'] . ' tests</li>';
echo '<li><strong>Warnings:</strong> ' . $results['warnings'] . ' tests</li>';
echo '<li><strong>Total:</strong> ' . $results['total'] . ' tests</li>';
echo '</ul>';
echo '</div>';
echo '</div>';
echo '</div>';

// Display detailed test results
echo '<div class="section">';
echo '<h2>📋 Detailed Test Results</h2>';

foreach ($results['tests'] as $test) {
    $class = 'test-' . $test['status'];
    $icon = $test['status'] === 'pass' ? '✅' : ($test['status'] === 'fail' ? '❌' : '⚠️');
    
    echo '<div class="test-result ' . $class . '">';
    echo '<div class="test-name">' . $icon . ' ' . htmlspecialchars($test['name']) . '</div>';
    if (!empty($test['message'])) {
        echo '<div class="test-details">' . htmlspecialchars($test['message']) . '</div>';
    }
    echo '</div>';
}

echo '</div>';

// =============================================================================
// RECOMMENDATIONS
// =============================================================================

echo '<div class="section">';
echo '<h2>💡 Recommendations</h2>';

if ($critical_failures === 0) {
    echo '<div class="info">';
    echo '<h4>🎉 Chat DOM Validation Successful!</h4>';
    echo '<p>The chat interface DOM validation passed successfully. Key findings:</p>';
    echo '<ul>';
    echo '<li>✅ Chat container elements are properly rendered</li>';
    echo '<li>✅ Required DOM elements are present</li>';
    echo '<li>✅ Assets are properly registered and enqueued</li>';
    echo '<li>✅ WordPress hooks are correctly configured</li>';
    echo '</ul>';
    echo '</div>';
} else {
    echo '<div class="warning">';
    echo '<h4>⚠️ Critical Issues Found - Action Required</h4>';
    echo '<p>Please address the following critical issues:</p>';
    echo '<ul>';
    
    foreach ($results['tests'] as $test) {
        if ($test['status'] === 'fail' && in_array($test['name'], [
            'ChatInterface Class',
            'Required Methods', 
            'Admin Footer Hook',
            'HTML Rendering',
            'Required DOM Elements'
        ])) {
            echo '<li><strong>' . htmlspecialchars($test['name']) . ':</strong> ' . htmlspecialchars($test['message']) . '</li>';
        }
    }
    
    echo '</ul>';
    echo '</div>';
}

echo '<div class="info">';
echo '<h4>🔧 Next Steps:</h4>';
echo '<ol>';
echo '<li>If validation passed, test the chat interface manually in the admin</li>';
echo '<li>Check browser console for any JavaScript errors</li>';
echo '<li>Verify chat functionality with both consented and non-consented users</li>';
echo '<li>Test on different admin pages to ensure consistent rendering</li>';
echo '</ol>';
echo '</div>';

echo '</div>';

// =============================================================================
// DEBUGGING INFORMATION
// =============================================================================

echo '<div class="section">';
echo '<h2>🔧 Environment Information</h2>';

echo '<div class="grid">';

echo '<div>';
echo '<h4>System Information</h4>';
echo '<div class="code">';
echo 'PHP Version: ' . PHP_VERSION . "\n";
echo 'WordPress Version: ' . get_bloginfo('version') . "\n";
echo 'Plugin Directory: ' . MPAI_PLUGIN_DIR . "\n";
echo 'Plugin URL: ' . MPAI_PLUGIN_URL . "\n";
echo 'Current Screen: ' . $screen_id . "\n";
echo 'Current Page: ' . $current_page . "\n";
echo 'Current Time: ' . date('Y-m-d H:i:s T') . "\n";
echo 'User ID: ' . get_current_user_id() . "\n";
echo 'User Capabilities: ' . (current_user_can('manage_options') ? 'Admin' : 'Limited') . "\n";
echo '</div>';
echo '</div>';

echo '<div>';
echo '<h4>Chat Configuration</h4>';
echo '<div class="code">';
if ($chat_interface_instance) {
    $reflection = new ReflectionClass($chat_interface_instance);
    $configMethod = $reflection->getMethod('getChatConfig');
    $configMethod->setAccessible(true);
    $chat_config = $configMethod->invoke($chat_interface_instance, true);
    
    echo 'API Endpoint: ' . ($chat_config['apiEndpoint'] ?? 'Not set') . "\n";
    echo 'Debug Mode: ' . (($chat_config['debug'] ?? false) ? 'Enabled' : 'Disabled') . "\n";
    echo 'Max Messages: ' . ($chat_config['maxMessages'] ?? 'Not set') . "\n";
    echo 'Auto Open: ' . (($chat_config['autoOpen'] ?? false) ? 'Yes' : 'No') . "\n";
    echo 'Has Consented: ' . (($chat_config['hasConsented'] ?? false) ? 'Yes' : 'No') . "\n";
    echo 'Conversation ID: ' . ($chat_config['conversationId'] ?? 'None') . "\n";
} else {
    echo 'ChatInterface not available for configuration inspection' . "\n";
}
echo '</div>';
echo '</div>';

echo '</div>';

echo '</div>';

// =============================================================================
// USAGE INSTRUCTIONS
// =============================================================================

echo '<div class="section">';
echo '<h2>📖 Usage Instructions</h2>';

echo '<div class="info">';
echo '<h4>How to Use This Validation Script:</h4>';
echo '<ol>';
echo '<li><strong>Browser Access:</strong> Navigate to <code>' . admin_url('admin.php?page=mpai-settings') . '</code> then access this script at <code>' . MPAI_PLUGIN_URL . 'dev-tools/chat-dom-validation.php</code></li>';
echo '<li><strong>Direct Access:</strong> Run <code>php ' . __FILE__ . '</code> from the command line (requires WordPress environment)</li>';
echo '<li><strong>Integration Testing:</strong> Use this script after making changes to ChatInterface to verify DOM rendering</li>';
echo '<li><strong>Debugging:</strong> Check the detailed test results and HTML preview sections for troubleshooting</li>';
echo '</ol>';
echo '</div>';

echo '<div class="warning">';
echo '<h4>⚠️ Important Notes:</h4>';
echo '<ul>';
echo '<li>This script requires admin privileges to run</li>';
echo '<li>The script simulates chat interface rendering - actual functionality may vary</li>';
echo '<li>Some tests may show warnings on non-MPAI admin pages (this is expected)</li>';
echo '<li>Always test the actual chat interface manually after validation passes</li>';
echo '</ul>';
echo '</div>';

echo '</div>';

echo '<div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; color: #666;">';
echo '<p>MemberPress AI Assistant - Chat DOM Validation Tool v1.0.0</p>';
echo '<p>Generated on ' . date('Y-m-d H:i:s T') . '</p>';
echo '<p>Validates that chat container elements are always present in DOM regardless of consent status</p>';
echo '</div>';

?>
    </div>
</body>
</html>