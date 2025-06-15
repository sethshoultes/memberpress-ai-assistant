<?php
/**
 * Chat Container Rendering Diagnosis Tool
 * 
 * This tool adds comprehensive logging to diagnose why chat container
 * DOM elements are not being rendered after consent opt-in.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

echo "<h2>Chat Container Rendering Diagnosis</h2>\n";
echo "<p>Adding comprehensive logging to diagnose chat container rendering issues...</p>\n";

// 1. Add logging to ChatInterface::renderAdminChatInterface()
$chat_interface_file = MPAI_PLUGIN_DIR . 'src/ChatInterface.php';
$chat_interface_content = file_get_contents($chat_interface_file);

// Add detailed logging at the start of renderAdminChatInterface
$search_pattern = '/public function renderAdminChatInterface\(\) \{/';
$replacement = 'public function renderAdminChatInterface() {
        // DIAGNOSTIC: Add comprehensive logging for chat container rendering
        $current_screen = get_current_screen();
        $screen_id = $current_screen ? $current_screen->id : \'unknown\';
        $current_page = isset($_GET[\'page\']) ? $_GET[\'page\'] : \'none\';
        $request_uri = $_SERVER[\'REQUEST_URI\'] ?? \'unknown\';
        $is_ajax = wp_doing_ajax();
        $user_id = get_current_user_id();
        
        LoggingUtility::debug(\'[CHAT RENDER DIAGNOSIS] renderAdminChatInterface() called\', [
            \'screen_id\' => $screen_id,
            \'page\' => $current_page,
            \'request_uri\' => $request_uri,
            \'is_ajax\' => $is_ajax,
            \'user_id\' => $user_id,
            \'already_rendered_flag\' => defined(\'MPAI_CHAT_INTERFACE_RENDERED\'),
            \'call_stack\' => wp_debug_backtrace_summary()
        ]);';

if (preg_match($search_pattern, $chat_interface_content)) {
    $chat_interface_content = preg_replace($search_pattern, $replacement, $chat_interface_content);
    echo "<p>✓ Added comprehensive logging to renderAdminChatInterface()</p>\n";
} else {
    echo "<p>⚠ Could not find renderAdminChatInterface() method pattern</p>\n";
}

// Add logging before consent validation
$consent_check_pattern = '/\/\/ CRITICAL FIX: Check consent status BEFORE rendering/';
$consent_replacement = '// DIAGNOSTIC: Log consent validation process
        LoggingUtility::debug(\'[CHAT RENDER DIAGNOSIS] Starting consent validation\', [
            \'user_id\' => $user_id,
            \'consent_manager_available\' => class_exists(\'\\\\MemberpressAiAssistant\\\\Admin\\\\MPAIConsentManager\')
        ]);
        
        // CRITICAL FIX: Check consent status BEFORE rendering';

if (strpos($chat_interface_content, '// CRITICAL FIX: Check consent status BEFORE rendering') !== false) {
    $chat_interface_content = str_replace('// CRITICAL FIX: Check consent status BEFORE rendering', $consent_replacement, $chat_interface_content);
    echo "<p>✓ Added consent validation logging</p>\n";
}

// Add logging after consent check
$consent_result_pattern = '/LoggingUtility::debug\(\'ChatInterface: Consent validation - User ID: \' \. \$user_id \. \', Has consented: \' \. \(\$has_consented \? \'YES\' : \'NO\'\)\);/';
$consent_result_replacement = 'LoggingUtility::debug(\'ChatInterface: Consent validation - User ID: \' . $user_id . \', Has consented: \' . ($has_consented ? \'YES\' : \'NO\'));
        
        // DIAGNOSTIC: Additional consent validation details
        LoggingUtility::debug(\'[CHAT RENDER DIAGNOSIS] Consent validation details\', [
            \'user_id\' => $user_id,
            \'has_consented\' => $has_consented,
            \'consent_meta_raw\' => get_user_meta($user_id, \'mpai_has_consented\', true),
            \'consent_meta_all\' => get_user_meta($user_id, \'mpai_has_consented\', false),
            \'user_meta_cache\' => wp_cache_get($user_id, \'user_meta\')
        ]);';

if (preg_match($consent_result_pattern, $chat_interface_content)) {
    $chat_interface_content = preg_replace($consent_result_pattern, $consent_result_replacement, $chat_interface_content);
    echo "<p>✓ Added detailed consent validation logging</p>\n";
}

// Add logging before renderChatContainerHTML call
$render_call_pattern = '/\$this->renderChatContainerHTML\(\);/';
$render_call_replacement = '// DIAGNOSTIC: Log before rendering chat container HTML
        LoggingUtility::debug(\'[CHAT RENDER DIAGNOSIS] About to render chat container HTML\', [
            \'user_id\' => $user_id,
            \'screen_id\' => $screen_id,
            \'page\' => $current_page,
            \'output_buffer_level\' => ob_get_level(),
            \'headers_sent\' => headers_sent()
        ]);
        
        $this->renderChatContainerHTML();
        
        // DIAGNOSTIC: Log after rendering chat container HTML
        LoggingUtility::debug(\'[CHAT RENDER DIAGNOSIS] Chat container HTML rendering completed\');';

if (preg_match($render_call_pattern, $chat_interface_content)) {
    $chat_interface_content = preg_replace($render_call_pattern, $render_call_replacement, $chat_interface_content);
    echo "<p>✓ Added chat container HTML rendering logging</p>\n";
}

// Write the updated file
file_put_contents($chat_interface_file, $chat_interface_content);

// 2. Add logging to AJAX handler for get_chat_interface
$ajax_handler_file = MPAI_PLUGIN_DIR . 'src/Admin/MPAIAjaxHandler.php';
$ajax_handler_content = file_get_contents($ajax_handler_file);

// Add logging to handle_get_chat_interface method
$ajax_method_pattern = '/public function handle_get_chat_interface\(\): void \{/';
$ajax_method_replacement = 'public function handle_get_chat_interface(): void {
        // DIAGNOSTIC: Add comprehensive logging for AJAX chat interface request
        $user_id = get_current_user_id();
        $nonce_provided = isset($_POST[\'nonce\']);
        $nonce_valid = $nonce_provided ? wp_verify_nonce($_POST[\'nonce\'], \'mpai_consent_nonce\') : false;
        
        $this->log(\'[CHAT RENDER DIAGNOSIS] AJAX get_chat_interface called\', [
            \'user_id\' => $user_id,
            \'is_logged_in\' => is_user_logged_in(),
            \'nonce_provided\' => $nonce_provided,
            \'nonce_valid\' => $nonce_valid,
            \'post_data_keys\' => array_keys($_POST),
            \'request_uri\' => $_SERVER[\'REQUEST_URI\'] ?? \'unknown\'
        ]);';

if (preg_match($ajax_method_pattern, $ajax_handler_content)) {
    $ajax_handler_content = preg_replace($ajax_method_pattern, $ajax_method_replacement, $ajax_handler_content);
    echo "<p>✓ Added AJAX handler logging</p>\n";
}

// Add logging before consent check in AJAX handler
$ajax_consent_pattern = '/\/\/ Check if user has consented/';
$ajax_consent_replacement = '// DIAGNOSTIC: Log consent check in AJAX handler
        $consent_manager = MPAIConsentManager::getInstance();
        $has_consented = $consent_manager->hasUserConsented();
        
        $this->log(\'[CHAT RENDER DIAGNOSIS] AJAX consent validation\', [
            \'user_id\' => $user_id,
            \'has_consented\' => $has_consented,
            \'consent_meta_raw\' => get_user_meta($user_id, \'mpai_has_consented\', true),
            \'consent_manager_class\' => get_class($consent_manager)
        ]);
        
        // Check if user has consented';

if (strpos($ajax_handler_content, '// Check if user has consented') !== false) {
    $ajax_handler_content = str_replace('// Check if user has consented', $ajax_consent_replacement, $ajax_handler_content);
    echo "<p>✓ Added AJAX consent validation logging</p>\n";
}

// Add logging before template inclusion
$template_pattern = '/if \(file_exists\(\$chat_template_path\)\) \{/';
$template_replacement = 'if (file_exists($chat_template_path)) {
                // DIAGNOSTIC: Log template inclusion process
                $this->log(\'[CHAT RENDER DIAGNOSIS] Including chat interface template\', [
                    \'template_path\' => $chat_template_path,
                    \'file_exists\' => true,
                    \'is_readable\' => is_readable($chat_template_path),
                    \'file_size\' => filesize($chat_template_path),
                    \'output_buffer_level_before\' => ob_get_level()
                ]);';

if (preg_match($template_pattern, $ajax_handler_content)) {
    $ajax_handler_content = preg_replace($template_pattern, $template_replacement, $ajax_handler_content);
    echo "<p>✓ Added template inclusion logging</p>\n";
}

// Write the updated AJAX handler file
file_put_contents($ajax_handler_file, $ajax_handler_content);

// 3. Add logging to consent form inline template
$consent_template_file = MPAI_PLUGIN_DIR . 'templates/consent-form-inline.php';
$consent_template_content = file_get_contents($consent_template_file);

// Add logging to consent submission success handler
$consent_success_pattern = '/if \(response\.success\) \{/';
$consent_success_replacement = 'if (response.success) {
                    // DIAGNOSTIC: Log successful consent submission
                    console.log(\'[CHAT RENDER DIAGNOSIS] Consent saved successfully, preparing to load chat interface\', {
                        response: response,
                        timestamp: new Date().toISOString()
                    });';

if (preg_match($consent_success_pattern, $consent_template_content)) {
    $consent_template_content = preg_replace($consent_success_pattern, $consent_success_replacement, $consent_template_content);
    echo "<p>✓ Added consent submission success logging</p>\n";
}

// Add logging to loadChatInterface function
$load_chat_pattern = '/function loadChatInterface\(\) \{/';
$load_chat_replacement = 'function loadChatInterface() {
        // DIAGNOSTIC: Log chat interface loading process
        console.log(\'[CHAT RENDER DIAGNOSIS] loadChatInterface() called\', {
            ajaxurl: ajaxurl,
            timestamp: new Date().toISOString()
        });';

if (preg_match($load_chat_pattern, $consent_template_content)) {
    $consent_template_content = preg_replace($load_chat_pattern, $load_chat_replacement, $consent_template_content);
    echo "<p>✓ Added chat interface loading logging</p>\n";
}

// Add logging to AJAX success handler
$ajax_success_pattern = '/success: function\(response\) \{/';
$ajax_success_replacement = 'success: function(response) {
                // DIAGNOSTIC: Log AJAX response for chat interface
                console.log(\'[CHAT RENDER DIAGNOSIS] AJAX response received for chat interface\', {
                    response: response,
                    hasHtml: !!(response.data && response.data.html),
                    htmlLength: response.data && response.data.html ? response.data.html.length : 0,
                    hasAssets: !!(response.data && response.data.assets),
                    timestamp: new Date().toISOString()
                });';

if (preg_match($ajax_success_pattern, $consent_template_content)) {
    $consent_template_content = preg_replace($ajax_success_pattern, $ajax_success_replacement, $consent_template_content);
    echo "<p>✓ Added AJAX response logging</p>\n";
}

// Write the updated consent template file
file_put_contents($consent_template_file, $consent_template_content);

// 4. Add logging to settings page template
$settings_template_file = MPAI_PLUGIN_DIR . 'templates/settings-page.php';
$settings_template_content = file_get_contents($settings_template_file);

// Add logging to consent check
$settings_consent_pattern = '/\$has_consented = \$consent_manager->hasUserConsented\(\);/';
$settings_consent_replacement = '$has_consented = $consent_manager->hasUserConsented();
    
    // DIAGNOSTIC: Log settings page consent check
    \\MemberpressAiAssistant\\Utilities\\LoggingUtility::debug(\'[CHAT RENDER DIAGNOSIS] Settings page consent check\', [
        \'user_id\' => get_current_user_id(),
        \'has_consented\' => $has_consented,
        \'consent_meta_raw\' => get_user_meta(get_current_user_id(), \'mpai_has_consented\', true),
        \'already_rendered\' => defined(\'MPAI_CHAT_INTERFACE_RENDERED\'),
        \'request_uri\' => $_SERVER[\'REQUEST_URI\'] ?? \'unknown\'
    ]);';

if (preg_match($settings_consent_pattern, $settings_template_content)) {
    $settings_template_content = preg_replace($settings_consent_pattern, $settings_consent_replacement, $settings_template_content);
    echo "<p>✓ Added settings page consent check logging</p>\n";
}

// Write the updated settings template file
file_put_contents($settings_template_file, $settings_template_content);

echo "<h3>Diagnostic Logging Added Successfully</h3>\n";
echo "<p>The following diagnostic logging has been added:</p>\n";
echo "<ul>\n";
echo "<li>✓ Comprehensive logging in ChatInterface::renderAdminChatInterface()</li>\n";
echo "<li>✓ Detailed consent validation logging</li>\n";
echo "<li>✓ AJAX handler logging for get_chat_interface</li>\n";
echo "<li>✓ Template inclusion process logging</li>\n";
echo "<li>✓ JavaScript consent submission logging</li>\n";
echo "<li>✓ Settings page consent check logging</li>\n";
echo "</ul>\n";

echo "<h3>Next Steps</h3>\n";
echo "<p>1. Go to the settings page and try the consent flow</p>\n";
echo "<p>2. Check the debug logs for '[CHAT RENDER DIAGNOSIS]' entries</p>\n";
echo "<p>3. Look for any gaps in the rendering pipeline</p>\n";
echo "<p>4. Check browser console for JavaScript diagnostic logs</p>\n";

echo "<h3>Key Questions to Answer</h3>\n";
echo "<ul>\n";
echo "<li>Is renderAdminChatInterface() being called after consent?</li>\n";
echo "<li>Is the consent validation passing correctly?</li>\n";
echo "<li>Is the AJAX get_chat_interface handler being called?</li>\n";
echo "<li>Is the chat interface HTML being generated?</li>\n";
echo "<li>Are there any errors in the template inclusion?</li>\n";
echo "</ul>\n";