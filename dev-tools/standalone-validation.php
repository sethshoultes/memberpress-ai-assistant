<?php
/**
 * MemberPress AI Assistant - Standalone Validation Tool
 * 
 * This validation tool tests the chat interface system without requiring
 * a database connection, focusing on file structure, class definitions,
 * and template integrity.
 * 
 * @package MemberpressAiAssistant
 * @version 1.0.0
 */

// Prevent direct access and set up basic environment
if (!defined('ABSPATH')) {
    // Define basic constants for standalone operation
    define('ABSPATH', dirname(__DIR__, 4) . '/');
    define('MPAI_PLUGIN_DIR', __DIR__ . '/../');
    define('MPAI_PLUGIN_URL', 'http://localhost/wp-content/plugins/memberpress-ai-assistant/');
    define('MPAI_VERSION', '1.0.0');
}

// Start output buffering
ob_start();

// Set up error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Standalone Validation Tracker
 */
class MPAIStandaloneTracker {
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

// Initialize tracker
$tracker = new MPAIStandaloneTracker();

// Clean output buffer
ob_clean();

?>
<!DOCTYPE html>
<html>
<head>
    <title>MemberPress AI Assistant - Standalone Validation</title>
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
            <h1>🔍 MemberPress AI Assistant - Standalone Validation</h1>
            <div class="timestamp"><?php echo date('Y-m-d H:i:s T'); ?></div>
            <div class="info">This validation runs without database connection to test file structure and code integrity.</div>
        </div>

<?php

// =============================================================================
// FILE STRUCTURE VALIDATION
// =============================================================================

echo '<div class="section">';
echo '<h2>📁 File Structure Validation</h2>';

// Core plugin files
$core_files = [
    'memberpress-ai-assistant.php' => MPAI_PLUGIN_DIR . 'memberpress-ai-assistant.php',
    'composer.json' => MPAI_PLUGIN_DIR . 'composer.json',
    'README.md' => MPAI_PLUGIN_DIR . 'README.md'
];

$missing_core = [];
foreach ($core_files as $name => $path) {
    if (!file_exists($path)) {
        $missing_core[] = $name;
    }
}

$core_check = empty($missing_core);
$tracker->addTest('Core Plugin Files', $core_check,
    $core_check ? 'All core files exist' : 'Missing files: ' . implode(', ', $missing_core));

// Template files
$template_files = [
    'settings-page.php' => MPAI_PLUGIN_DIR . 'templates/settings-page.php',
    'consent-form-inline.php' => MPAI_PLUGIN_DIR . 'templates/consent-form-inline.php',
    'chat-interface.php' => MPAI_PLUGIN_DIR . 'templates/chat-interface.php',
    'consent-form.php' => MPAI_PLUGIN_DIR . 'templates/consent-form.php'
];

$missing_templates = [];
foreach ($template_files as $name => $path) {
    if (!file_exists($path)) {
        $missing_templates[] = $name;
    }
}

$templates_check = empty($missing_templates);
$tracker->addTest('Template Files', $templates_check,
    $templates_check ? 'All template files exist' : 'Missing templates: ' . implode(', ', $missing_templates));

// Source files
$source_files = [
    'ChatInterface.php' => MPAI_PLUGIN_DIR . 'src/ChatInterface.php',
    'MPAIConsentManager.php' => MPAI_PLUGIN_DIR . 'src/Admin/MPAIConsentManager.php',
    'MPAIAjaxHandler.php' => MPAI_PLUGIN_DIR . 'src/Admin/MPAIAjaxHandler.php',
    'MPAIAdminMenu.php' => MPAI_PLUGIN_DIR . 'src/Admin/MPAIAdminMenu.php'
];

$missing_sources = [];
foreach ($source_files as $name => $path) {
    if (!file_exists($path)) {
        $missing_sources[] = $name;
    }
}

$sources_check = empty($missing_sources);
$tracker->addTest('Source Files', $sources_check,
    $sources_check ? 'All source files exist' : 'Missing sources: ' . implode(', ', $missing_sources));

echo '</div>';

// =============================================================================
// ASSET VALIDATION
// =============================================================================

echo '<div class="section">';
echo '<h2>📦 Asset Validation</h2>';

// CSS Assets
$css_assets = [
    'chat.css' => MPAI_PLUGIN_DIR . 'assets/css/chat.css',
    'blog-post.css' => MPAI_PLUGIN_DIR . 'assets/css/blog-post.css',
    'settings.css' => MPAI_PLUGIN_DIR . 'assets/css/settings.css',
    'mpai-table-styles.css' => MPAI_PLUGIN_DIR . 'assets/css/mpai-table-styles.css'
];

$missing_css = [];
$css_sizes = [];
foreach ($css_assets as $name => $path) {
    if (!file_exists($path)) {
        $missing_css[] = $name;
    } else {
        $css_sizes[$name] = filesize($path);
    }
}

$css_check = empty($missing_css);
$tracker->addTest('CSS Assets', $css_check,
    $css_check ? 'All CSS files exist' : 'Missing CSS: ' . implode(', ', $missing_css),
    $css_sizes);

// JavaScript Assets
$js_assets = [
    'chat.js' => MPAI_PLUGIN_DIR . 'assets/js/chat.js',
    'blog-formatter.js' => MPAI_PLUGIN_DIR . 'assets/js/blog-formatter.js',
    'text-formatter.js' => MPAI_PLUGIN_DIR . 'assets/js/text-formatter.js',
    'xml-processor.js' => MPAI_PLUGIN_DIR . 'assets/js/xml-processor.js',
    'data-handler-minimal.js' => MPAI_PLUGIN_DIR . 'assets/js/data-handler-minimal.js'
];

$missing_js = [];
$js_sizes = [];
foreach ($js_assets as $name => $path) {
    if (!file_exists($path)) {
        $missing_js[] = $name;
    } else {
        $js_sizes[$name] = filesize($path);
    }
}

$js_check = empty($missing_js);
$tracker->addTest('JavaScript Assets', $js_check,
    $js_check ? 'All JavaScript files exist' : 'Missing JS: ' . implode(', ', $missing_js),
    $js_sizes);

// Modular JavaScript
$modular_js_path = MPAI_PLUGIN_DIR . 'assets/js/chat/';
$modular_js_exists = is_dir($modular_js_path);
$tracker->addTest('Modular JavaScript Structure', $modular_js_exists,
    $modular_js_exists ? 'Modular JS directory exists' : 'Modular JS directory missing');

if ($modular_js_exists) {
    $modular_files = [
        'core/api-client.js',
        'core/ui-manager.js',
        'messages/message-factory.js',
        'ui/input-handler.js'
    ];
    
    $missing_modular = [];
    foreach ($modular_files as $file) {
        if (!file_exists($modular_js_path . $file)) {
            $missing_modular[] = $file;
        }
    }
    
    $modular_check = empty($missing_modular);
    $tracker->addTest('Modular JavaScript Files', $modular_check,
        $modular_check ? 'All modular JS files exist' : 'Missing modular JS: ' . implode(', ', $missing_modular));
}

echo '</div>';

// =============================================================================
// TEMPLATE CONTENT VALIDATION
// =============================================================================

echo '<div class="section">';
echo '<h2>📄 Template Content Validation</h2>';

// Settings Page Template Analysis
if (file_exists($template_files['settings-page.php'])) {
    $settings_content = file_get_contents($template_files['settings-page.php']);
    
    $settings_checks = [
        'Consent Manager Usage' => strpos($settings_content, 'MPAIConsentManager::getInstance()') !== false,
        'Consent Status Check' => strpos($settings_content, 'hasUserConsented()') !== false,
        'Inline Consent Include' => strpos($settings_content, 'consent-form-inline.php') !== false,
        'Chat Interface Include' => strpos($settings_content, 'chat-interface.php') !== false,
        'Duplicate Prevention Flag' => strpos($settings_content, 'MPAI_CHAT_INTERFACE_RENDERED') !== false,
        'AJAX Nonce' => strpos($settings_content, 'wp_nonce_field') !== false || strpos($settings_content, 'wp_create_nonce') !== false
    ];
    
    $settings_passed = array_filter($settings_checks);
    $settings_success = count($settings_passed) >= 4; // At least 4 out of 6 checks should pass
    
    $tracker->addTest('Settings Page Integration', $settings_success,
        'Passed checks: ' . count($settings_passed) . '/6 - ' . implode(', ', array_keys($settings_passed)));
}

// Consent Form Template Analysis
if (file_exists($template_files['consent-form-inline.php'])) {
    $consent_content = file_get_contents($template_files['consent-form-inline.php']);
    
    $consent_checks = [
        'AJAX Form Structure' => strpos($consent_content, 'mpai-inline-consent-form') !== false,
        'Nonce Field' => strpos($consent_content, 'mpai_consent_nonce') !== false,
        'AJAX Action' => strpos($consent_content, 'mpai_save_consent') !== false,
        'JavaScript Handler' => strpos($consent_content, 'jQuery') !== false || strpos($consent_content, '$') !== false,
        'Form Validation' => strpos($consent_content, 'required') !== false || strpos($consent_content, 'validate') !== false
    ];
    
    $consent_passed = array_filter($consent_checks);
    $consent_success = count($consent_passed) >= 3; // At least 3 out of 5 checks should pass
    
    $tracker->addTest('Consent Form Structure', $consent_success,
        'Passed checks: ' . count($consent_passed) . '/5 - ' . implode(', ', array_keys($consent_passed)));
}

// Chat Interface Template Analysis
if (file_exists($template_files['chat-interface.php'])) {
    $chat_content = file_get_contents($template_files['chat-interface.php']);
    
    $chat_checks = [
        'Chat Container' => strpos($chat_content, 'mpai-chat-container') !== false,
        'Chat Toggle' => strpos($chat_content, 'mpai-chat-toggle') !== false,
        'Messages Area' => strpos($chat_content, 'mpai-chat-messages') !== false,
        'Input Area' => strpos($chat_content, 'mpai-chat-input') !== false,
        'Send Button' => strpos($chat_content, 'send') !== false || strpos($chat_content, 'submit') !== false
    ];
    
    $chat_passed = array_filter($chat_checks);
    $chat_success = count($chat_passed) >= 4; // At least 4 out of 5 checks should pass
    
    $tracker->addTest('Chat Interface Structure', $chat_success,
        'Passed checks: ' . count($chat_passed) . '/5 - ' . implode(', ', array_keys($chat_passed)));
}

echo '</div>';

// =============================================================================
// PHP CLASS STRUCTURE VALIDATION
// =============================================================================

echo '<div class="section">';
echo '<h2>🔧 PHP Class Structure Validation</h2>';

// Try to include and analyze PHP classes without instantiating them
$class_files = [
    'ChatInterface' => MPAI_PLUGIN_DIR . 'src/ChatInterface.php',
    'MPAIConsentManager' => MPAI_PLUGIN_DIR . 'src/Admin/MPAIConsentManager.php',
    'MPAIAjaxHandler' => MPAI_PLUGIN_DIR . 'src/Admin/MPAIAjaxHandler.php',
    'MPAIAdminMenu' => MPAI_PLUGIN_DIR . 'src/Admin/MPAIAdminMenu.php'
];

foreach ($class_files as $class_name => $file_path) {
    if (file_exists($file_path)) {
        $class_content = file_get_contents($file_path);
        
        // Basic syntax validation
        $syntax_valid = strpos($class_content, '<?php') !== false && 
                       strpos($class_content, 'class ' . $class_name) !== false;
        
        // Check for required methods based on class type
        $required_methods = [];
        switch ($class_name) {
            case 'ChatInterface':
                $required_methods = ['getInstance', 'renderAdminChatInterface', 'registerAdminAssets'];
                break;
            case 'MPAIConsentManager':
                $required_methods = ['getInstance', 'hasUserConsented', 'saveUserConsent'];
                break;
            case 'MPAIAjaxHandler':
                $required_methods = ['handle_save_consent', 'handle_get_chat_interface'];
                break;
            case 'MPAIAdminMenu':
                $required_methods = ['__construct', 'ensure_chat_assets_enqueued'];
                break;
        }
        
        $methods_found = 0;
        foreach ($required_methods as $method) {
            if (strpos($class_content, 'function ' . $method) !== false) {
                $methods_found++;
            }
        }
        
        $methods_check = count($required_methods) > 0 ? ($methods_found / count($required_methods)) >= 0.7 : true;
        
        $tracker->addTest($class_name . ' Class Structure', $syntax_valid && $methods_check,
            'Syntax: ' . ($syntax_valid ? '✓' : '✗') . 
            ', Methods: ' . $methods_found . '/' . count($required_methods));
    } else {
        $tracker->addTest($class_name . ' Class Structure', false, 'File not found: ' . $file_path);
    }
}

echo '</div>';

// =============================================================================
// CONFIGURATION VALIDATION
// =============================================================================

echo '<div class="section">';
echo '<h2>⚙️ Configuration Validation</h2>';

// Check main plugin file
$main_plugin_file = MPAI_PLUGIN_DIR . 'memberpress-ai-assistant.php';
if (file_exists($main_plugin_file)) {
    $plugin_content = file_get_contents($main_plugin_file);
    
    $plugin_checks = [
        'Plugin Header' => strpos($plugin_content, 'Plugin Name:') !== false,
        'Version Info' => strpos($plugin_content, 'Version:') !== false,
        'Namespace Usage' => strpos($plugin_content, 'namespace') !== false || strpos($plugin_content, 'MemberpressAiAssistant') !== false,
        'Autoloader' => strpos($plugin_content, 'autoload') !== false || strpos($plugin_content, 'require') !== false,
        'Hook Registration' => strpos($plugin_content, 'add_action') !== false || strpos($plugin_content, 'add_filter') !== false
    ];
    
    $plugin_passed = array_filter($plugin_checks);
    $plugin_success = count($plugin_passed) >= 3;
    
    $tracker->addTest('Main Plugin File', $plugin_success,
        'Passed checks: ' . count($plugin_passed) . '/5 - ' . implode(', ', array_keys($plugin_passed)));
}

// Check composer configuration
$composer_file = MPAI_PLUGIN_DIR . 'composer.json';
if (file_exists($composer_file)) {
    $composer_content = file_get_contents($composer_file);
    $composer_data = json_decode($composer_content, true);
    
    $composer_valid = is_array($composer_data) && 
                     isset($composer_data['autoload']) &&
                     isset($composer_data['name']);
    
    $tracker->addTest('Composer Configuration', $composer_valid,
        $composer_valid ? 'Valid composer.json with autoload configuration' : 'Invalid or incomplete composer.json');
}

echo '</div>';

// =============================================================================
// SECURITY VALIDATION
// =============================================================================

echo '<div class="section">';
echo '<h2>🛡️ Security Validation</h2>';

// Check for security patterns in templates
$security_patterns = [
    'ABSPATH Check' => 'ABSPATH',
    'Nonce Usage' => 'wp_nonce',
    'Capability Check' => 'current_user_can',
    'Data Sanitization' => 'sanitize_',
    'Data Escaping' => 'esc_'
];

$template_security_scores = [];
foreach ($template_files as $template_name => $template_path) {
    if (file_exists($template_path)) {
        $content = file_get_contents($template_path);
        $security_score = 0;
        $found_patterns = [];
        
        foreach ($security_patterns as $pattern_name => $pattern) {
            if (strpos($content, $pattern) !== false) {
                $security_score++;
                $found_patterns[] = $pattern_name;
            }
        }
        
        $template_security_scores[$template_name] = [
            'score' => $security_score,
            'total' => count($security_patterns),
            'patterns' => $found_patterns
        ];
    }
}

$overall_security = 0;
$total_templates = count($template_security_scores);
foreach ($template_security_scores as $score_data) {
    $overall_security += ($score_data['score'] / $score_data['total']);
}

$security_percentage = $total_templates > 0 ? ($overall_security / $total_templates) * 100 : 0;
$security_check = $security_percentage >= 60; // At least 60% security coverage

$tracker->addTest('Template Security Patterns', $security_check,
    'Overall security coverage: ' . round($security_percentage, 1) . '%');

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
    echo '<div style="font-size: 24px; color: #721c24;">⚠️ SOME ISSUES FOUND</div>';
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

foreach ($results['tests'] as $test) {
    $class = $test['passed'] ? 'test-pass' : 'test-fail';
    $icon = $test['passed'] ? '✅' : '❌';
    
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
echo '<h2>💡 Next Steps</h2>';

if ($overall_success) {
    echo '<div class="info">';
    echo '<h4>🎉 File Structure Validation Complete!</h4>';
    echo '<p>All file structure and code integrity tests passed. To complete validation:</p>';
    echo '<ul>';
    echo '<li>✅ Start your Local Sites environment</li>';
    echo '<li>✅ Ensure the database is running</li>';
    echo '<li>✅ Run the full validation tool with WordPress loaded</li>';
    echo '<li>✅ Test the chat interface manually in the admin</li>';
    echo '</ul>';
    echo '</div>';
} else {
    echo '<div class="warning">';
    echo '<h4>⚠️ Issues Found - Action Required</h4>';
    echo '<p>Please address the failed tests above before proceeding:</p>';
    echo '<ul>';
    
    foreach ($results['tests'] as $test) {
        if (!$test['passed']) {
            echo '<li><strong>' . htmlspecialchars($test['name']) . ':</strong> ' . htmlspecialchars($test['message']) . '</li>';
        }
    }
    
    echo '</ul>';
    echo '</div>';
}

echo '<div class="info">';
echo '<h4>🔧 To Test with Full WordPress Environment:</h4>';
echo '<ol>';
echo '<li>Start your Local Sites environment</li>';
echo '<li>Ensure the database service is running</li>';
echo '<li>Run: <code>php dev-tools/chat-interface-complete-validation.php</code></li>';
echo '<li>Or access via browser: <code>http://your-site/wp-content/plugins/memberpress-ai-assistant/dev-tools/chat-interface-complete-validation.php</code></li>';
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
echo 'Plugin Directory: ' . MPAI_PLUGIN_DIR . "\n";
echo 'Plugin URL: ' . MPAI_PLUGIN_URL . "\n";
echo 'Plugin Version: ' . MPAI_VERSION . "\n";
echo 'Current Time: ' . date('Y-m-d H:i:s T') . "\n";
echo 'Validation Mode: Standalone (No Database)' . "\n";
echo '</div>';
echo '</div>';

echo '<div>';
echo '<h4>File Permissions</h4>';
echo '<div class="code">';
$key_files = [
    'Plugin Directory' => MPAI_PLUGIN_DIR,
    'Templates Directory' => MPAI_PLUGIN_DIR . 'templates/',
    'Assets Directory' => MPAI_PLUGIN_DIR . 'assets/',
    'Source Directory' => MPAI_PLUGIN_DIR . 'src/'
];

foreach ($key_files as $name => $path) {
    if (file_exists($path)) {
        $perms = substr(sprintf('%o', fileperms($path)), -4);
        echo $name . ': ' . $perms . "\n";
    } else {
        echo $name . ': NOT FOUND' . "\n";
    }
}
echo '</div>';
echo '</div>';

echo '</div>';

echo '</div>';

echo '<div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; color: #666;">';
echo '<p>MemberPress AI Assistant - Standalone Validation Tool v1.0.0</p>';
echo '<p>Generated on ' . date('Y-m-d H:i:s T') . '</p>';
echo '</div>';

?>
    </div>
</body>
</html>