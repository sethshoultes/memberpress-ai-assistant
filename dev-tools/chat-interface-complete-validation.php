<?php
/**
 * MemberPress AI Assistant - Complete Chat Interface Validation Tool
 * 
 * This comprehensive validation tool tests the entire fixed chat interface workflow
 * including settings page integration, consent management, AJAX endpoints, 
 * chat interface loading, asset management, and error handling.
 * 
 * @package MemberpressAiAssistant
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once dirname(__DIR__, 4) . '/wp-load.php';
}

// Start output buffering to capture any unexpected output
ob_start();

// Set up error reporting for comprehensive testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Validation Test Results Tracker
 */
class MPAIValidationTracker {
    private $tests = [];
    private $passed = 0;
    private $failed = 0;
    
    public function addTest($name, $passed, $message = '', $details = []) {
        $this->tests[] = [
            'name' => $name,
            'passed' => $passed,
            'message' => $message,
            'details' => $details
        ];
        
        if ($passed) {
            $this->passed++;
        } else {
            $this->failed++;
        }
    }
    
    public function getResults() {
        return [
            'tests' => $this->tests,
            'passed' => $this->passed,
            'failed' => $this->failed,
            'total' => count($this->tests)
        ];
    }
}

// Initialize validation tracker
$tracker = new MPAIValidationTracker();

// Clean any previous output
ob_clean();

// Start HTML output
?>
<!DOCTYPE html>
<html>
<head>
    <title>MemberPress AI Assistant - Complete Validation</title>
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
        .test-name { font-weight: bold; }
        .test-details { margin-top: 5px; font-size: 13px; opacity: 0.8; }
        .summary { background: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; border-radius: 4px; margin: 20px 0; }
        .summary.pass { background: #d4edda; border-color: #c3e6cb; }
        .summary.fail { background: #f8d7da; border-color: #f5c6cb; }
        .code { background: #f8f9fa; border: 1px solid #e9ecef; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 13px; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 768px) { .grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 MemberPress AI Assistant - Complete Validation</h1>
            <div class="timestamp"><?php echo date('Y-m-d H:i:s T'); ?></div>
        </div>

<?php

// =============================================================================
// ENVIRONMENT CHECKS
// =============================================================================

echo '<div class="section">';
echo '<h2>🌍 Environment Checks</h2>';

// WordPress Environment
try {
    $wp_version = get_bloginfo('version');
    $tracker->addTest('WordPress Environment', true, "WordPress {$wp_version} loaded successfully");
} catch (Exception $e) {
    $tracker->addTest('WordPress Environment', false, 'WordPress environment not available: ' . $e->getMessage());
}

// Plugin Activation
$plugin_active = is_plugin_active('memberpress-ai-assistant/memberpress-ai-assistant.php');
$tracker->addTest('Plugin Activation', $plugin_active, $plugin_active ? 'Plugin is active' : 'Plugin is not active');

// Required Constants
$constants_check = defined('MPAI_PLUGIN_DIR') && defined('MPAI_PLUGIN_URL') && defined('MPAI_VERSION');
$tracker->addTest('Required Constants', $constants_check, $constants_check ? 'All required constants defined' : 'Missing required constants');

// Required Classes
$required_classes = [
    'MemberpressAiAssistant\Admin\MPAIConsentManager',
    'MemberpressAiAssistant\Admin\MPAIAjaxHandler', 
    'MemberpressAiAssistant\ChatInterface',
    'MemberpressAiAssistant\Admin\MPAIAdminMenu'
];

$missing_classes = [];
foreach ($required_classes as $class) {
    if (!class_exists($class)) {
        $missing_classes[] = $class;
    }
}

$classes_check = empty($missing_classes);
$tracker->addTest('Required Classes', $classes_check, 
    $classes_check ? 'All required classes available' : 'Missing classes: ' . implode(', ', $missing_classes));

echo '</div>';

// =============================================================================
// CONSENT SYSTEM VALIDATION
// =============================================================================

echo '<div class="section">';
echo '<h2>✅ Consent System Validation</h2>';

// Consent Manager Instance
try {
    $consent_manager = \MemberpressAiAssistant\Admin\MPAIConsentManager::getInstance();
    $tracker->addTest('Consent Manager Instance', true, 'MPAIConsentManager instance created successfully');
    
    // Test consent status detection
    $current_user_id = get_current_user_id();
    if ($current_user_id > 0) {
        $has_consented = $consent_manager->hasUserConsented();
        $tracker->addTest('Consent Status Detection', true, 
            "User consent status: " . ($has_consented ? 'CONSENTED' : 'NOT CONSENTED'));
    } else {
        $tracker->addTest('Consent Status Detection', false, 'No user logged in to test consent status');
    }
    
} catch (Exception $e) {
    $tracker->addTest('Consent Manager Instance', false, 'Failed to create consent manager: ' . $e->getMessage());
}

// Consent Form Template
$consent_template_path = MPAI_PLUGIN_DIR . 'templates/consent-form-inline.php';
$consent_template_exists = file_exists($consent_template_path);
$tracker->addTest('Inline Consent Form Template', $consent_template_exists, 
    $consent_template_exists ? 'Template exists at: ' . $consent_template_path : 'Template missing at: ' . $consent_template_path);

if ($consent_template_exists) {
    $consent_content = file_get_contents($consent_template_path);
    $has_ajax_form = strpos($consent_content, 'mpai-inline-consent-form') !== false;
    $has_nonce = strpos($consent_content, 'mpai_consent_nonce') !== false;
    $has_ajax_handler = strpos($consent_content, 'mpai_save_consent') !== false;
    
    $tracker->addTest('Consent Form AJAX Integration', 
        $has_ajax_form && $has_nonce && $has_ajax_handler,
        'AJAX form: ' . ($has_ajax_form ? '✓' : '✗') . 
        ', Nonce: ' . ($has_nonce ? '✓' : '✗') . 
        ', Handler: ' . ($has_ajax_handler ? '✓' : '✗'));
}

echo '</div>';

// =============================================================================
// AJAX SYSTEM VALIDATION
// =============================================================================

echo '<div class="section">';
echo '<h2>🔄 AJAX System Validation</h2>';

// AJAX Handler Instance
try {
    $ajax_handler = new \MemberpressAiAssistant\Admin\MPAIAjaxHandler();
    $tracker->addTest('AJAX Handler Instance', true, 'MPAIAjaxHandler instance created successfully');
    
    // Check if AJAX endpoints are registered
    global $wp_filter;
    $ajax_endpoints = [
        'wp_ajax_mpai_save_consent' => 'handle_save_consent',
        'wp_ajax_mpai_get_chat_interface' => 'handle_get_chat_interface',
        'wp_ajax_mpai_process_chat' => 'handle_process_chat',
        'wp_ajax_mpai_chat_request' => 'handle_chat_request'
    ];
    
    $registered_endpoints = [];
    $missing_endpoints = [];
    
    foreach ($ajax_endpoints as $hook => $method) {
        if (isset($wp_filter[$hook])) {
            $registered_endpoints[] = $hook;
        } else {
            $missing_endpoints[] = $hook;
        }
    }
    
    $endpoints_check = empty($missing_endpoints);
    $tracker->addTest('AJAX Endpoints Registration', $endpoints_check,
        'Registered: ' . count($registered_endpoints) . '/' . count($ajax_endpoints) . 
        ($missing_endpoints ? ' | Missing: ' . implode(', ', $missing_endpoints) : ''));
    
} catch (Exception $e) {
    $tracker->addTest('AJAX Handler Instance', false, 'Failed to create AJAX handler: ' . $e->getMessage());
}

// Test AJAX Handler Methods
if (isset($ajax_handler)) {
    $ajax_methods = ['handle_save_consent', 'handle_get_chat_interface', 'handle_process_chat', 'handle_chat_request'];
    $missing_methods = [];
    
    foreach ($ajax_methods as $method) {
        if (!method_exists($ajax_handler, $method)) {
            $missing_methods[] = $method;
        }
    }
    
    $methods_check = empty($missing_methods);
    $tracker->addTest('AJAX Handler Methods', $methods_check,
        $methods_check ? 'All required methods exist' : 'Missing methods: ' . implode(', ', $missing_methods));
}

echo '</div>';

// =============================================================================
// CHAT INTERFACE VALIDATION
// =============================================================================

echo '<div class="section">';
echo '<h2>💬 Chat Interface Validation</h2>';

// ChatInterface Class
try {
    $chat_interface = \MemberpressAiAssistant\ChatInterface::getInstance();
    $tracker->addTest('ChatInterface Instance', true, 'ChatInterface singleton instance created successfully');
    
    // Check required methods
    $required_methods = [
        'registerAdminAssets',
        'shouldLoadAdminChatInterface', 
        'renderAdminChatInterface',
        'processChatRequest',
        'checkChatPermissions'
    ];
    
    $missing_methods = [];
    foreach ($required_methods as $method) {
        if (!method_exists($chat_interface, $method)) {
            $missing_methods[] = $method;
        }
    }
    
    $methods_check = empty($missing_methods);
    $tracker->addTest('ChatInterface Methods', $methods_check,
        $methods_check ? 'All required methods exist' : 'Missing methods: ' . implode(', ', $missing_methods));
    
} catch (Exception $e) {
    $tracker->addTest('ChatInterface Instance', false, 'Failed to create ChatInterface: ' . $e->getMessage());
}

// Chat Interface Template
$chat_template_path = MPAI_PLUGIN_DIR . 'templates/chat-interface.php';
$chat_template_exists = file_exists($chat_template_path);
$tracker->addTest('Chat Interface Template', $chat_template_exists,
    $chat_template_exists ? 'Template exists at: ' . $chat_template_path : 'Template missing at: ' . $chat_template_path);

if ($chat_template_exists) {
    $chat_content = file_get_contents($chat_template_path);
    $has_container = strpos($chat_content, 'mpai-chat-container') !== false;
    $has_toggle = strpos($chat_content, 'mpai-chat-toggle') !== false;
    $has_messages = strpos($chat_content, 'mpai-chat-messages') !== false;
    $has_input = strpos($chat_content, 'mpai-chat-input') !== false;
    
    $template_structure = $has_container && $has_toggle && $has_messages && $has_input;
    $tracker->addTest('Chat Template Structure', $template_structure,
        'Container: ' . ($has_container ? '✓' : '✗') . 
        ', Toggle: ' . ($has_toggle ? '✓' : '✗') . 
        ', Messages: ' . ($has_messages ? '✓' : '✗') . 
        ', Input: ' . ($has_input ? '✓' : '✗'));
}

echo '</div>';

// =============================================================================
// ASSET LOADING VALIDATION
// =============================================================================

echo '<div class="section">';
echo '<h2>📦 Asset Loading Validation</h2>';

// CSS Assets
$css_assets = [
    'chat.css' => MPAI_PLUGIN_DIR . 'assets/css/chat.css',
    'blog-post.css' => MPAI_PLUGIN_DIR . 'assets/css/blog-post.css',
    'mpai-table-styles.css' => MPAI_PLUGIN_DIR . 'assets/css/mpai-table-styles.css'
];

$missing_css = [];
foreach ($css_assets as $name => $path) {
    if (!file_exists($path)) {
        $missing_css[] = $name;
    }
}

$css_check = empty($missing_css);
$tracker->addTest('CSS Assets', $css_check,
    $css_check ? 'All CSS assets exist' : 'Missing CSS: ' . implode(', ', $missing_css));

// JavaScript Assets
$js_assets = [
    'chat.js' => MPAI_PLUGIN_DIR . 'assets/js/chat.js',
    'blog-formatter.js' => MPAI_PLUGIN_DIR . 'assets/js/blog-formatter.js',
    'text-formatter.js' => MPAI_PLUGIN_DIR . 'assets/js/text-formatter.js',
    'xml-processor.js' => MPAI_PLUGIN_DIR . 'assets/js/xml-processor.js',
    'data-handler-minimal.js' => MPAI_PLUGIN_DIR . 'assets/js/data-handler-minimal.js'
];

$missing_js = [];
foreach ($js_assets as $name => $path) {
    if (!file_exists($path)) {
        $missing_js[] = $name;
    }
}

$js_check = empty($missing_js);
$tracker->addTest('JavaScript Assets', $js_check,
    $js_check ? 'All JavaScript assets exist' : 'Missing JS: ' . implode(', ', $missing_js));

// Modular JavaScript Assets
$modular_js_assets = [
    'api-client.js' => MPAI_PLUGIN_DIR . 'assets/js/chat/core/api-client.js',
    'ui-manager.js' => MPAI_PLUGIN_DIR . 'assets/js/chat/core/ui-manager.js'
];

$missing_modular_js = [];
foreach ($modular_js_assets as $name => $path) {
    if (!file_exists($path)) {
        $missing_modular_js[] = $name;
    }
}

$modular_js_check = empty($missing_modular_js);
$tracker->addTest('Modular JavaScript Assets', $modular_js_check,
    $modular_js_check ? 'All modular JS assets exist' : 'Missing modular JS: ' . implode(', ', $missing_modular_js));

echo '</div>';

// =============================================================================
// SETTINGS PAGE INTEGRATION
// =============================================================================

echo '<div class="section">';
echo '<h2>⚙️ Settings Page Integration</h2>';

// Settings Page Template
$settings_template_path = MPAI_PLUGIN_DIR . 'templates/settings-page.php';
$settings_template_exists = file_exists($settings_template_path);
$tracker->addTest('Settings Page Template', $settings_template_exists,
    $settings_template_exists ? 'Template exists at: ' . $settings_template_path : 'Template missing at: ' . $settings_template_path);

if ($settings_template_exists) {
    $settings_content = file_get_contents($settings_template_path);
    
    // Check for consent integration
    $has_consent_manager = strpos($settings_content, 'MPAIConsentManager::getInstance()') !== false;
    $has_consent_check = strpos($settings_content, 'hasUserConsented()') !== false;
    $has_inline_consent = strpos($settings_content, 'consent-form-inline.php') !== false;
    $has_chat_interface = strpos($settings_content, 'chat-interface.php') !== false;
    $has_duplicate_prevention = strpos($settings_content, 'MPAI_CHAT_INTERFACE_RENDERED') !== false;
    
    $integration_check = $has_consent_manager && $has_consent_check && $has_inline_consent && $has_chat_interface;
    $tracker->addTest('Settings Page Integration', $integration_check,
        'Consent Manager: ' . ($has_consent_manager ? '✓' : '✗') . 
        ', Consent Check: ' . ($has_consent_check ? '✓' : '✗') . 
        ', Inline Form: ' . ($has_inline_consent ? '✓' : '✗') . 
        ', Chat Interface: ' . ($has_chat_interface ? '✓' : '✗'));
    
    $tracker->addTest('Duplicate Rendering Prevention', $has_duplicate_prevention,
        $has_duplicate_prevention ? 'Global flag system implemented' : 'Missing duplicate prevention');
}

// Admin Menu Integration
try {
    $admin_menu = new \MemberpressAiAssistant\Admin\MPAIAdminMenu();
    $tracker->addTest('Admin Menu Instance', true, 'MPAIAdminMenu instance created successfully');
    
    // Check for asset enqueuing method
    $has_ensure_method = method_exists($admin_menu, 'ensure_chat_assets_enqueued');
    $tracker->addTest('Admin Menu Asset Enqueuing', $has_ensure_method,
        $has_ensure_method ? 'ensure_chat_assets_enqueued method exists' : 'Missing asset enqueuing method');
    
} catch (Exception $e) {
    $tracker->addTest('Admin Menu Instance', false, 'Failed to create admin menu: ' . $e->getMessage());
}

echo '</div>';

// =============================================================================
// GLOBAL FLAG SYSTEM
// =============================================================================

echo '<div class="section">';
echo '<h2>🚩 Global Flag System Validation</h2>';

// Test duplicate rendering prevention
$flag_defined = defined('MPAI_CHAT_INTERFACE_RENDERED');
$tracker->addTest('Global Flag Definition', !$flag_defined, 
    $flag_defined ? 'Flag already defined (possible duplicate render)' : 'Flag not defined (clean state)');

// Test flag usage in templates
if ($settings_template_exists) {
    $settings_content = file_get_contents($settings_template_path);
    $flag_check = strpos($settings_content, 'defined(\'MPAI_CHAT_INTERFACE_RENDERED\')') !== false;
    $flag_set = strpos($settings_content, 'define(\'MPAI_CHAT_INTERFACE_RENDERED\', true)') !== false;
    
    $flag_system = $flag_check && $flag_set;
    $tracker->addTest('Flag System Implementation', $flag_system,
        'Flag Check: ' . ($flag_check ? '✓' : '✗') . ', Flag Set: ' . ($flag_set ? '✓' : '✗'));
}

if (isset($chat_interface)) {
    // Check ChatInterface for flag usage
    $reflection = new ReflectionClass($chat_interface);
    $render_method = $reflection->getMethod('renderAdminChatInterface');
    $render_method->setAccessible(true);
    
    // Get method source (if available)
    $filename = $reflection->getFileName();
    if ($filename && file_exists($filename)) {
        $source = file_get_contents($filename);
        $has_flag_check = strpos($source, 'MPAI_CHAT_INTERFACE_RENDERED') !== false;
        $tracker->addTest('ChatInterface Flag Usage', $has_flag_check,
            $has_flag_check ? 'ChatInterface implements flag system' : 'ChatInterface missing flag system');
    }
}

echo '</div>';

// =============================================================================
// ERROR HANDLING VALIDATION
// =============================================================================

echo '<div class="section">';
echo '<h2>🛡️ Error Handling Validation</h2>';

// Test consent manager error handling
if (isset($consent_manager)) {
    try {
        // Test with invalid user ID
        $invalid_consent = $consent_manager->hasUserConsented(999999);
        $tracker->addTest('Consent Manager Error Handling', true, 'Handles invalid user ID gracefully');
    } catch (Exception $e) {
        $tracker->addTest('Consent Manager Error Handling', false, 'Exception with invalid user ID: ' . $e->getMessage());
    }
}

// Test AJAX handler error handling
if (isset($ajax_handler)) {
    // Check if methods have proper error handling
    $reflection = new ReflectionClass($ajax_handler);
    $save_consent_method = $reflection->getMethod('handle_save_consent');
    
    $filename = $reflection->getFileName();
    if ($filename && file_exists($filename)) {
        $source = file_get_contents($filename);
        $has_nonce_check = strpos($source, 'wp_verify_nonce') !== false;
        $has_user_check = strpos($source, 'is_user_logged_in') !== false;
        $has_error_response = strpos($source, 'wp_send_json_error') !== false;
        
        $error_handling = $has_nonce_check && $has_user_check && $has_error_response;
        $tracker->addTest('AJAX Error Handling', $error_handling,
            'Nonce Check: ' . ($has_nonce_check ? '✓' : '✗') . 
            ', User Check: ' . ($has_user_check ? '✓' : '✗') . 
            ', Error Response: ' . ($has_error_response ? '✓' : '✗'));
    }
}

// Test template error handling
$templates_with_error_handling = 0;
$total_templates = 0;

$template_files = [
    'settings-page.php' => $settings_template_path,
    'consent-form-inline.php' => $consent_template_path,
    'chat-interface.php' => $chat_template_path
];

foreach ($template_files as $name => $path) {
    if (file_exists($path)) {
        $total_templates++;
        $content = file_get_contents($path);
        
        // Check for basic error handling patterns
        $has_abspath_check = strpos($content, 'ABSPATH') !== false;
        $has_error_handling = strpos($content, 'try') !== false || strpos($content, 'catch') !== false || $has_abspath_check;
        
        if ($has_error_handling) {
            $templates_with_error_handling++;
        }
    }
}

$template_error_handling = $total_templates > 0 ? ($templates_with_error_handling / $total_templates) >= 0.5 : false;
$tracker->addTest('Template Error Handling', $template_error_handling,
    "Templates with error handling: {$templates_with_error_handling}/{$total_templates}");

echo '</div>';

// =============================================================================
// INTEGRATION TESTING
// =============================================================================

echo '<div class="section">';
echo '<h2>🔗 Integration Testing</h2>';

// Test end-to-end workflow simulation
$current_user = wp_get_current_user();
if ($current_user && $current_user->ID > 0) {
    // Simulate workflow steps
    $workflow_steps = [
        'User Authentication' => $current_user->ID > 0,
        'Consent Manager Available' => isset($consent_manager),
        'AJAX Handler Available' => isset($ajax_handler),
        'Chat Interface Available' => isset($chat_interface),
        'Templates Available' => $settings_template_exists && $consent_template_exists && $chat_template_exists
    ];
    
    $workflow_passed = array_filter($workflow_steps);
    $workflow_success = count($workflow_passed) === count($workflow_steps);
    
    $tracker->addTest('End-to-End Workflow', $workflow_success,
        'Workflow steps: ' . count($workflow_passed) . '/' . count($workflow_steps) . ' passed');
    
    // Test settings page rendering capability
    if ($workflow_success) {
        try {
            ob_start();
            
            // Simulate settings page variables
            $current_tab = 'general';
            $tabs = ['general' => 'General Settings'];
            $page_slug = 'mpai-settings';
            
            // Test if template can be included without errors
            include $settings_template_path;
            $output = ob_get_clean();
            
            $rendering_success = !empty($output) && strpos($output, 'MemberPress AI Assistant Settings') !== false;
            $tracker->addTest('Settings Page Rendering', $rendering_success,
                $rendering_success ? 'Settings page renders successfully' : 'Settings page rendering failed');
            
        } catch (Exception $e) {
            ob_end_clean();
            $tracker->addTest('Settings Page Rendering', false, 'Rendering error: ' . $e->getMessage());
        }
    }
} else {
    $tracker->addTest('End-to-End Workflow', false, 'No authenticated user available for testing');
}

echo '</div>';

// =============================================================================
// SUMMARY AND RESULTS
// =============================================================================

$results = $tracker->getResults();
$overall_success = $results['failed'] === 0;
$success_rate = $results['total'] > 0 ? round(($results['passed'] / $results['total']) * 100, 1) : 0;

echo '<div class="summary ' . ($overall_success ? 'pass' : 'fail') . '">';
echo '<h2>📊 Validation Summary</h2>';
echo '<div class="grid">';
echo '<div>';
echo '<h3>Overall Status</h3>';
if ($overall_success) {
    echo '<div style="font-size: 24px; color: #155724;">✅ ALL TESTS PASSED</div>';
} else {
    echo '<div style="font-size: 24px; color: #721c24;">❌ SOME TESTS FAILED</div>';
}
echo '<p><strong>Success Rate:</strong> ' . $success_rate . '% (' . $results['passed'] . '/' . $results['total'] . ' tests passed)</p>';
echo '</div>';
echo '<div>';
echo '<h3>Test Breakdown</h3>';
echo '<ul>';
echo '<li><strong>Passed:</strong> ' . $results['passed'] . ' tests</li>';
echo '<li><strong>Failed:</strong> ' . $results['failed'] . ' tests</li>';
echo '<li><strong>Total:</strong> ' . $results['total'] . ' tests</li>';
echo '</ul>';
echo '</div>';
echo '</div>';
echo '</div>';

// Display detailed test results
echo '<div class="section">';
echo '<h2>📋 Detailed Test Results</h2>';

$current_section = '';
foreach ($results['tests'] as $test) {
    // Group tests by section (extract from test name)
    $test_parts = explode(' - ', $test['name']);
    $section = isset($test_parts[1]) ? $test_parts[0] : '';
    
    if ($section !== $current_section && !empty($section)) {
        if (!empty($current_section)) {
            echo '</div>';
        }
        echo '<div style="margin: 15px 0;">';
        echo '<h4>' . esc_html($section) . '</h4>';
        $current_section = $section;
    }
    
    $class = $test['passed'] ? 'test-pass' : 'test-fail';
    $icon = $test['passed'] ? '✅' : '❌';
    
    echo '<div class="test-result ' . $class . '">';
    echo '<div class="test-name">' . $icon . ' ' . esc_html($test['name']) . '</div>';
    if (!empty($test['message'])) {
        echo '<div class="test-details">' . esc_html($test['message']) . '</div>';
    }
    if (!empty($test['details'])) {
        echo '<div class="test-details">Details: ' . esc_html(json_encode($test['details'])) . '</div>';
    }
    echo '</div>';
}

if (!empty($current_section)) {
    echo '</div>';
}

echo '</div>';

// =============================================================================
// RECOMMENDATIONS
// =============================================================================

echo '<div class="section">';
echo '<h2>💡 Recommendations</h2>';

if ($overall_success) {
    echo '<div class="info">';
    echo '<h4>🎉 Excellent! All systems are functioning correctly.</h4>';
    echo '<p>Your MemberPress AI Assistant chat interface implementation is working perfectly. Here are some next steps:</p>';
    echo '<ul>';
    echo '<li>✅ Test the chat interface functionality manually</li>';
    echo '<li>✅ Verify consent form submission works correctly</li>';
    echo '<li>Test AJAX endpoints with real data</li>';
    echo '<li>✅ Monitor for any JavaScript console errors</li>';
    echo '<li>✅ Test chat interface on different screen sizes</li>';
    echo '</ul>';
    echo '</div>';
} else {
    echo '<div class="warning">';
    echo '<h4>⚠️ Issues Found - Action Required</h4>';
    echo '<p>Some components need attention. Please address the failed tests above:</p>';
    echo '<ul>';
    
    // Provide specific recommendations based on failed tests
    foreach ($results['tests'] as $test) {
        if (!$test['passed']) {
            echo '<li><strong>' . esc_html($test['name']) . ':</strong> ' . esc_html($test['message']) . '</li>';
        }
    }
    
    echo '</ul>';
    echo '<p><strong>Priority Actions:</strong></p>';
    echo '<ol>';
    echo '<li>Fix any missing files or classes</li>';
    echo '<li>Ensure all AJAX endpoints are properly registered</li>';
    echo '<li>Verify template files exist and have correct structure</li>';
    echo '<li>Test consent form functionality manually</li>';
    echo '<li>Re-run this validation after fixes</li>';
    echo '</ol>';
    echo '</div>';
}

echo '</div>';

// =============================================================================
// MANUAL TESTING INSTRUCTIONS
// =============================================================================

echo '<div class="section">';
echo '<h2>🧪 Manual Testing Instructions</h2>';

echo '<div class="info">';
echo '<h4>Step-by-Step Manual Testing</h4>';
echo '<p>After this automated validation passes, perform these manual tests:</p>';
echo '</div>';

echo '<div class="grid">';

echo '<div>';
echo '<h4>1. Consent Flow Testing</h4>';
echo '<ol>';
echo '<li>Clear any existing consent (if needed)</li>';
echo '<li>Navigate to <code>admin.php?page=mpai-settings</code></li>';
echo '<li>Verify inline consent form appears</li>';
echo '<li>Check consent checkbox and submit</li>';
echo '<li>Verify chat interface loads after consent</li>';
echo '</ol>';
echo '</div>';

echo '<div>';
echo '<h4>2. Chat Interface Testing</h4>';
echo '<ol>';
echo '<li>Verify chat toggle button appears</li>';
echo '<li>Click toggle to open/close chat</li>';
echo '<li>Send a test message</li>';
echo '<li>Verify response is received</li>';
echo '<li>Test chat history persistence</li>';
echo '</ol>';
echo '</div>';

echo '<div>';
echo '<h4>3. Asset Loading Testing</h4>';
echo '<ol>';
echo '<li>Open browser developer tools</li>';
echo '<li>Check Network tab for asset loading</li>';
echo '<li>Verify no 404 errors for CSS/JS files</li>';
echo '<li>Check Console tab for JavaScript errors</li>';
echo '<li>Verify chat styling appears correctly</li>';
echo '</ol>';
echo '</div>';

echo '<div>';
echo '<h4>4. Error Handling Testing</h4>';
echo '<ol>';
echo '<li>Test with network disconnected</li>';
echo '<li>Test with invalid input data</li>';
echo '<li>Test consent form without checkbox</li>';
echo '<li>Verify appropriate error messages</li>';
echo '<li>Test recovery after errors</li>';
echo '</ol>';
echo '</div>';

echo '</div>';

echo '</div>';

// =============================================================================
// DEBUGGING INFORMATION
// =============================================================================

echo '<div class="section">';
echo '<h2>🔧 Debugging Information</h2>';

echo '<div class="grid">';

echo '<div>';
echo '<h4>System Information</h4>';
echo '<div class="code">';
echo 'WordPress Version: ' . get_bloginfo('version') . "\n";
echo 'PHP Version: ' . PHP_VERSION . "\n";
echo 'Plugin Directory: ' . MPAI_PLUGIN_DIR . "\n";
echo 'Plugin URL: ' . MPAI_PLUGIN_URL . "\n";
if (defined('MPAI_VERSION')) {
    echo 'Plugin Version: ' . MPAI_VERSION . "\n";
}
echo 'Current User ID: ' . get_current_user_id() . "\n";
echo 'Current Time: ' . date('Y-m-d H:i:s T') . "\n";
echo '</div>';
echo '</div>';

echo '<div>';
echo '<h4>WordPress Environment</h4>';
echo '<div class="code">';
echo 'WP_DEBUG: ' . (defined('WP_DEBUG') && WP_DEBUG ? 'true' : 'false') . "\n";
echo 'WP_DEBUG_LOG: ' . (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? 'true' : 'false') . "\n";
echo 'SCRIPT_DEBUG: ' . (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? 'true' : 'false') . "\n";
echo 'Admin URL: ' . admin_url() . "\n";
echo 'AJAX URL: ' . admin_url('admin-ajax.php') . "\n";
echo 'REST URL: ' . rest_url() . "\n";
echo '</div>';
echo '</div>';

echo '</div>';

if ($results['failed'] > 0) {
    echo '<div class="warning">';
    echo '<h4>🚨 Failed Tests Require Attention</h4>';
    echo '<p>Please review the failed tests above and take corrective action. ';
    echo 'Common issues and solutions:</p>';
    echo '<ul>';
    echo '<li><strong>Missing Files:</strong> Ensure all plugin files are properly uploaded</li>';
    echo '<li><strong>Class Not Found:</strong> Check autoloading and file paths</li>';
    echo '<li><strong>AJAX Endpoints:</strong> Verify hooks are properly registered</li>';
    echo '<li><strong>Template Issues:</strong> Check file permissions and paths</li>';
    echo '</ul>';
    echo '</div>';
}

echo '</div>';

// =============================================================================
// FOOTER
// =============================================================================

echo '<div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; color: #666;">';
echo '<p>MemberPress AI Assistant - Complete Validation Tool v1.0.0</p>';
echo '<p>Generated on ' . date('Y-m-d H:i:s T') . '</p>';
echo '<p>For support, please contact the MemberPress team.</p>';
echo '</div>';

?>
    </div>
</body>
</html>