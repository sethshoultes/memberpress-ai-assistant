<?php
/**
 * Standalone Chat Container Rendering Diagnosis Tool
 * 
 * This tool adds comprehensive logging to diagnose why chat container
 * DOM elements are not being rendered after consent opt-in.
 */

echo "Chat Container Rendering Diagnosis Tool\n";
echo "=====================================\n\n";

// Define plugin directory
$plugin_dir = dirname(__DIR__) . '/';

echo "Plugin directory: $plugin_dir\n\n";

// 1. Add logging to ChatInterface::renderAdminChatInterface()
$chat_interface_file = $plugin_dir . 'src/ChatInterface.php';
echo "Processing ChatInterface.php...\n";

if (!file_exists($chat_interface_file)) {
    echo "ERROR: ChatInterface.php not found at $chat_interface_file\n";
    exit(1);
}

$chat_interface_content = file_get_contents($chat_interface_file);

// Add detailed logging at the start of renderAdminChatInterface
$search_pattern = '/(\s+)\/\/ Add logging for monitoring\s+\$current_screen = get_current_screen\(\);/';
$replacement = '$1// DIAGNOSTIC: Add comprehensive logging for chat container rendering
$1$current_screen = get_current_screen();
$1$screen_id = $current_screen ? $current_screen->id : \'unknown\';
$1$current_page = isset($_GET[\'page\']) ? $_GET[\'page\'] : \'none\';
$1$request_uri = $_SERVER[\'REQUEST_URI\'] ?? \'unknown\';
$1$is_ajax = wp_doing_ajax();
$1$user_id = get_current_user_id();
$1
$1LoggingUtility::debug(\'[CHAT RENDER DIAGNOSIS] renderAdminChatInterface() called\', [
$1    \'screen_id\' => $screen_id,
$1    \'page\' => $current_page,
$1    \'request_uri\' => $request_uri,
$1    \'is_ajax\' => $is_ajax,
$1    \'user_id\' => $user_id,
$1    \'already_rendered_flag\' => defined(\'MPAI_CHAT_INTERFACE_RENDERED\'),
$1    \'call_stack\' => wp_debug_backtrace_summary()
$1]);
$1
$1// Add logging for monitoring';

if (preg_match($search_pattern, $chat_interface_content)) {
    $chat_interface_content = preg_replace($search_pattern, $replacement, $chat_interface_content);
    echo "✓ Added comprehensive logging to renderAdminChatInterface()\n";
} else {
    echo "⚠ Could not find renderAdminChatInterface() logging pattern\n";
}

// Add logging before consent validation
$consent_check_pattern = '/(\s+)\/\/ CRITICAL FIX: Check consent status BEFORE rendering/';
$consent_replacement = '$1// DIAGNOSTIC: Log consent validation process
$1LoggingUtility::debug(\'[CHAT RENDER DIAGNOSIS] Starting consent validation\', [
$1    \'user_id\' => $user_id,
$1    \'consent_manager_available\' => class_exists(\'\\\\MemberpressAiAssistant\\\\Admin\\\\MPAIConsentManager\')
$1]);
$1
$1// CRITICAL FIX: Check consent status BEFORE rendering';

if (preg_match($consent_check_pattern, $chat_interface_content)) {
    $chat_interface_content = preg_replace($consent_check_pattern, $consent_replacement, $chat_interface_content);
    echo "✓ Added consent validation logging\n";
}

// Add logging after consent check
$consent_result_pattern = '/(\s+)LoggingUtility::debug\(\'ChatInterface: Consent validation - User ID: \' \. \$user_id \. \', Has consented: \' \. \(\$has_consented \? \'YES\' : \'NO\'\)\);/';
$consent_result_replacement = '$1LoggingUtility::debug(\'ChatInterface: Consent validation - User ID: \' . $user_id . \', Has consented: \' . ($has_consented ? \'YES\' : \'NO\'));
$1
$1// DIAGNOSTIC: Additional consent validation details
$1LoggingUtility::debug(\'[CHAT RENDER DIAGNOSIS] Consent validation details\', [
$1    \'user_id\' => $user_id,
$1    \'has_consented\' => $has_consented,
$1    \'consent_meta_raw\' => get_user_meta($user_id, \'mpai_has_consented\', true),
$1    \'consent_meta_all\' => get_user_meta($user_id, \'mpai_has_consented\', false),
$1    \'user_meta_cache\' => wp_cache_get($user_id, \'user_meta\')
$1]);';

if (preg_match($consent_result_pattern, $chat_interface_content)) {
    $chat_interface_content = preg_replace($consent_result_pattern, $consent_result_replacement, $chat_interface_content);
    echo "✓ Added detailed consent validation logging\n";
}

// Add logging before renderChatContainerHTML call
$render_call_pattern = '/(\s+)\/\/ Render container only after consent validation passes\s+\$this->renderChatContainerHTML\(\);/';
$render_call_replacement = '$1// DIAGNOSTIC: Log before rendering chat container HTML
$1LoggingUtility::debug(\'[CHAT RENDER DIAGNOSIS] About to render chat container HTML\', [
$1    \'user_id\' => $user_id,
$1    \'screen_id\' => $screen_id,
$1    \'page\' => $current_page,
$1    \'output_buffer_level\' => ob_get_level(),
$1    \'headers_sent\' => headers_sent()
$1]);
$1
$1// Render container only after consent validation passes
$1$this->renderChatContainerHTML();
$1
$1// DIAGNOSTIC: Log after rendering chat container HTML
$1LoggingUtility::debug(\'[CHAT RENDER DIAGNOSIS] Chat container HTML rendering completed\');';

if (preg_match($render_call_pattern, $chat_interface_content)) {
    $chat_interface_content = preg_replace($render_call_pattern, $render_call_replacement, $chat_interface_content);
    echo "✓ Added chat container HTML rendering logging\n";
}

// Write the updated file
file_put_contents($chat_interface_file, $chat_interface_content);

// 2. Add logging to AJAX handler for get_chat_interface
$ajax_handler_file = $plugin_dir . 'src/Admin/MPAIAjaxHandler.php';
echo "\nProcessing MPAIAjaxHandler.php...\n";

if (!file_exists($ajax_handler_file)) {
    echo "ERROR: MPAIAjaxHandler.php not found at $ajax_handler_file\n";
    exit(1);
}

$ajax_handler_content = file_get_contents($ajax_handler_file);

// Add logging to handle_get_chat_interface method
$ajax_method_pattern = '/(\s+)public function handle_get_chat_interface\(\): void \{\s+\$this->log\(\'Processing request for chat interface HTML\'\);/';
$ajax_method_replacement = '$1public function handle_get_chat_interface(): void {
$1    // DIAGNOSTIC: Add comprehensive logging for AJAX chat interface request
$1    $user_id = get_current_user_id();
$1    $nonce_provided = isset($_POST[\'nonce\']);
$1    $nonce_valid = $nonce_provided ? wp_verify_nonce($_POST[\'nonce\'], \'mpai_consent_nonce\') : false;
$1    
$1    $this->log(\'[CHAT RENDER DIAGNOSIS] AJAX get_chat_interface called\', [
$1        \'user_id\' => $user_id,
$1        \'is_logged_in\' => is_user_logged_in(),
$1        \'nonce_provided\' => $nonce_provided,
$1        \'nonce_valid\' => $nonce_valid,
$1        \'post_data_keys\' => array_keys($_POST),
$1        \'request_uri\' => $_SERVER[\'REQUEST_URI\'] ?? \'unknown\'
$1    ]);
$1    
$1    $this->log(\'Processing request for chat interface HTML\');';

if (preg_match($ajax_method_pattern, $ajax_handler_content)) {
    $ajax_handler_content = preg_replace($ajax_method_pattern, $ajax_method_replacement, $ajax_handler_content);
    echo "✓ Added AJAX handler logging\n";
}

// Add logging before consent check in AJAX handler
$ajax_consent_pattern = '/(\s+)\/\/ Check if user has consented\s+\$consent_manager = MPAIConsentManager::getInstance\(\);/';
$ajax_consent_replacement = '$1// DIAGNOSTIC: Log consent check in AJAX handler
$1$consent_manager = MPAIConsentManager::getInstance();
$1$has_consented = $consent_manager->hasUserConsented();
$1
$1$this->log(\'[CHAT RENDER DIAGNOSIS] AJAX consent validation\', [
$1    \'user_id\' => $user_id,
$1    \'has_consented\' => $has_consented,
$1    \'consent_meta_raw\' => get_user_meta($user_id, \'mpai_has_consented\', true),
$1    \'consent_manager_class\' => get_class($consent_manager)
$1]);
$1
$1// Check if user has consented';

if (preg_match($ajax_consent_pattern, $ajax_handler_content)) {
    $ajax_handler_content = preg_replace($ajax_consent_pattern, $ajax_consent_replacement, $ajax_handler_content);
    echo "✓ Added AJAX consent validation logging\n";
}

// Add logging before template inclusion
$template_pattern = '/(\s+)if \(file_exists\(\$chat_template_path\)\) \{\s+\/\/ Set up template variables for AJAX context/';
$template_replacement = '$1if (file_exists($chat_template_path)) {
$1    // DIAGNOSTIC: Log template inclusion process
$1    $this->log(\'[CHAT RENDER DIAGNOSIS] Including chat interface template\', [
$1        \'template_path\' => $chat_template_path,
$1        \'file_exists\' => true,
$1        \'is_readable\' => is_readable($chat_template_path),
$1        \'file_size\' => filesize($chat_template_path),
$1        \'output_buffer_level_before\' => ob_get_level()
$1    ]);
$1    
$1    // Set up template variables for AJAX context';

if (preg_match($template_pattern, $ajax_handler_content)) {
    $ajax_handler_content = preg_replace($template_pattern, $template_replacement, $ajax_handler_content);
    echo "✓ Added template inclusion logging\n";
}

// Write the updated AJAX handler file
file_put_contents($ajax_handler_file, $ajax_handler_content);

// 3. Add logging to consent form inline template
$consent_template_file = $plugin_dir . 'templates/consent-form-inline.php';
echo "\nProcessing consent-form-inline.php...\n";

if (!file_exists($consent_template_file)) {
    echo "ERROR: consent-form-inline.php not found at $consent_template_file\n";
    exit(1);
}

$consent_template_content = file_get_contents($consent_template_file);

// Add logging to consent submission success handler
$consent_success_pattern = '/(\s+)if \(response\.success\) \{\s+showMessage\(/';
$consent_success_replacement = '$1if (response.success) {
$1    // DIAGNOSTIC: Log successful consent submission
$1    console.log(\'[CHAT RENDER DIAGNOSIS] Consent saved successfully, preparing to load chat interface\', {
$1        response: response,
$1        timestamp: new Date().toISOString()
$1    });
$1    
$1    showMessage(';

if (preg_match($consent_success_pattern, $consent_template_content)) {
    $consent_template_content = preg_replace($consent_success_pattern, $consent_success_replacement, $consent_template_content);
    echo "✓ Added consent submission success logging\n";
}

// Add logging to loadChatInterface function
$load_chat_pattern = '/(\s+)function loadChatInterface\(\) \{\s+console\.log\(\'Loading chat interface\.\.\.\'\);/';
$load_chat_replacement = '$1function loadChatInterface() {
$1    // DIAGNOSTIC: Log chat interface loading process
$1    console.log(\'[CHAT RENDER DIAGNOSIS] loadChatInterface() called\', {
$1        ajaxurl: ajaxurl,
$1        timestamp: new Date().toISOString()
$1    });
$1    
$1    console.log(\'Loading chat interface...\');';

if (preg_match($load_chat_pattern, $consent_template_content)) {
    $consent_template_content = preg_replace($load_chat_pattern, $consent_replacement, $consent_template_content);
    echo "✓ Added chat interface loading logging\n";
}

// Add logging to AJAX success handler
$ajax_success_pattern = '/(\s+)success: function\(response\) \{\s+if \(response\.success && response\.data\.html\) \{/';
$ajax_success_replacement = '$1success: function(response) {
$1    // DIAGNOSTIC: Log AJAX response for chat interface
$1    console.log(\'[CHAT RENDER DIAGNOSIS] AJAX response received for chat interface\', {
$1        response: response,
$1        hasHtml: !!(response.data && response.data.html),
$1        htmlLength: response.data && response.data.html ? response.data.html.length : 0,
$1        hasAssets: !!(response.data && response.data.assets),
$1        timestamp: new Date().toISOString()
$1    });
$1    
$1    if (response.success && response.data.html) {';

if (preg_match($ajax_success_pattern, $consent_template_content)) {
    $consent_template_content = preg_replace($ajax_success_pattern, $ajax_success_replacement, $consent_template_content);
    echo "✓ Added AJAX response logging\n";
}

// Write the updated consent template file
file_put_contents($consent_template_file, $consent_template_content);

// 4. Add logging to settings page template
$settings_template_file = $plugin_dir . 'templates/settings-page.php';
echo "\nProcessing settings-page.php...\n";

if (!file_exists($settings_template_file)) {
    echo "ERROR: settings-page.php not found at $settings_template_file\n";
    exit(1);
}

$settings_template_content = file_get_contents($settings_template_file);

// Add logging to consent check
$settings_consent_pattern = '/(\s+)\$has_consented = \$consent_manager->hasUserConsented\(\);\s+\$already_rendered = defined\(\'MPAI_CHAT_INTERFACE_RENDERED\'\);/';
$settings_consent_replacement = '$1$has_consented = $consent_manager->hasUserConsented();
$1$already_rendered = defined(\'MPAI_CHAT_INTERFACE_RENDERED\');
$1
$1// DIAGNOSTIC: Log settings page consent check
$1\\MemberpressAiAssistant\\Utilities\\LoggingUtility::debug(\'[CHAT RENDER DIAGNOSIS] Settings page consent check\', [
$1    \'user_id\' => get_current_user_id(),
$1    \'has_consented\' => $has_consented,
$1    \'consent_meta_raw\' => get_user_meta(get_current_user_id(), \'mpai_has_consented\', true),
$1    \'already_rendered\' => $already_rendered,
$1    \'request_uri\' => $_SERVER[\'REQUEST_URI\'] ?? \'unknown\'
$1]);';

if (preg_match($settings_consent_pattern, $settings_template_content)) {
    $settings_template_content = preg_replace($settings_consent_pattern, $settings_consent_replacement, $settings_template_content);
    echo "✓ Added settings page consent check logging\n";
}

// Write the updated settings template file
file_put_contents($settings_template_file, $settings_template_content);

echo "\n=== Diagnostic Logging Added Successfully ===\n";
echo "The following diagnostic logging has been added:\n";
echo "✓ Comprehensive logging in ChatInterface::renderAdminChatInterface()\n";
echo "✓ Detailed consent validation logging\n";
echo "✓ AJAX handler logging for get_chat_interface\n";
echo "✓ Template inclusion process logging\n";
echo "✓ JavaScript consent submission logging\n";
echo "✓ Settings page consent check logging\n";

echo "\n=== Next Steps ===\n";
echo "1. Go to the settings page and try the consent flow\n";
echo "2. Check the debug logs for '[CHAT RENDER DIAGNOSIS]' entries\n";
echo "3. Look for any gaps in the rendering pipeline\n";
echo "4. Check browser console for JavaScript diagnostic logs\n";

echo "\n=== Key Questions to Answer ===\n";
echo "• Is renderAdminChatInterface() being called after consent?\n";
echo "• Is the consent validation passing correctly?\n";
echo "• Is the AJAX get_chat_interface handler being called?\n";
echo "• Is the chat interface HTML being generated?\n";
echo "• Are there any errors in the template inclusion?\n";

echo "\nDiagnostic logging installation complete!\n";