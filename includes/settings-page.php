<?php
/**
 * Settings Page
 *
 * Displays the settings page for MemberPress AI Assistant
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Generate the nonce for AJAX requests
$mpai_settings_nonce = wp_create_nonce('mpai_nonce');
error_log('MPAI: Settings page nonce generated: ' . substr($mpai_settings_nonce, 0, 5) . '...');

// Process form submission - direct approach without using Settings API
if (isset($_POST['mpai_save_settings']) && check_admin_referer('mpai_settings_nonce', 'mpai_nonce')) {
    // API Settings
    if (isset($_POST['mpai_api_key'])) {
        update_option('mpai_api_key', sanitize_text_field($_POST['mpai_api_key']));
    }
    
    if (isset($_POST['mpai_model'])) {
        update_option('mpai_model', sanitize_text_field($_POST['mpai_model']));
    }
    
    if (isset($_POST['mpai_memberpress_api_key'])) {
        update_option('mpai_memberpress_api_key', sanitize_text_field($_POST['mpai_memberpress_api_key']));
    }
    
    // CLI Commands
    update_option('mpai_enable_cli_commands', isset($_POST['mpai_enable_cli_commands']) ? '1' : '0');
    
    if (isset($_POST['mpai_allowed_cli_commands']) && is_array($_POST['mpai_allowed_cli_commands'])) {
        $commands = array();
        foreach ($_POST['mpai_allowed_cli_commands'] as $command) {
            if (!empty($command)) {
                $commands[] = sanitize_text_field($command);
            }
        }
        update_option('mpai_allowed_cli_commands', $commands);
    }
    
    // Advanced Settings
    if (isset($_POST['mpai_temperature'])) {
        update_option('mpai_temperature', floatval($_POST['mpai_temperature']));
    }
    
    if (isset($_POST['mpai_max_tokens'])) {
        update_option('mpai_max_tokens', absint($_POST['mpai_max_tokens']));
    }
    
    // Show success message
    add_settings_error('mpai_messages', 'mpai_success', __('Settings saved successfully.', 'memberpress-ai-assistant'), 'updated');
}

// Get current settings
$api_key = get_option('mpai_api_key', '');
$model = get_option('mpai_model', 'gpt-4o');
$memberpress_api_key = get_option('mpai_memberpress_api_key', '');
$enable_cli_commands = get_option('mpai_enable_cli_commands', false);
$allowed_commands = get_option('mpai_allowed_cli_commands', array());
$temperature = get_option('mpai_temperature', 0.7);
$max_tokens = get_option('mpai_max_tokens', 2048);

// Default allowed commands if empty
if (empty($allowed_commands)) {
    $allowed_commands = array(
        'wp user list',
        'wp post list',
        'wp plugin list',
    );
}

// Get available models
$settings = new MPAI_Settings();
$models = $settings->get_available_models();

// Display settings errors
settings_errors('mpai_messages');
?>

<script type="text/javascript">
    /* <![CDATA[ */
    // Only define ajaxurl if it's not already defined in admin section
    if (typeof ajaxurl === 'undefined') {
        var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
        console.log('MPAI: Setting ajaxurl to', ajaxurl);
    }
    
    // Output to console which nonce is being used
    if (typeof mpai_data !== 'undefined' && mpai_data.nonce) {
        console.log('MPAI: Localized nonce available:', mpai_data.nonce.substring(0, 5) + '...');
    } else {
        console.log('MPAI: Localized nonce NOT available, settings page might not work properly');
    }
    /* ]]> */
</script>

<div class="wrap mpai-settings-page">
    <h1><?php _e('MemberPress AI Assistant Settings', 'memberpress-ai-assistant'); ?></h1>

    <form method="post" action="">
        <?php wp_nonce_field('mpai_settings_nonce', 'mpai_nonce'); ?>
        
        <div class="mpai-settings-container">
            <h2 class="nav-tab-wrapper">
                <a href="#tab-api" class="nav-tab nav-tab-active"><?php _e('API Settings', 'memberpress-ai-assistant'); ?></a>
                <a href="#tab-chat" class="nav-tab"><?php _e('Chat Interface', 'memberpress-ai-assistant'); ?></a>
                <a href="#tab-cli" class="nav-tab"><?php _e('CLI Commands', 'memberpress-ai-assistant'); ?></a>
                <a href="#tab-advanced" class="nav-tab"><?php _e('Advanced', 'memberpress-ai-assistant'); ?></a>
                <a href="#tab-debug" class="nav-tab"><?php _e('Debug', 'memberpress-ai-assistant'); ?></a>
            </h2>
            
            <div id="tab-api" class="mpai-settings-tab">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="mpai_api_key">
                                <?php _e('OpenAI API Key', 'memberpress-ai-assistant'); ?>
                                <div class="mpai-api-status" id="openai-api-status">
                                    <span class="mpai-api-status-icon mpai-status-unknown"></span>
                                    <span class="mpai-api-status-text"><?php _e('Unknown', 'memberpress-ai-assistant'); ?></span>
                                </div>
                            </label>
                        </th>
                        <td>
                            <div class="mpai-key-field">
                                <input type="password" name="mpai_api_key" id="mpai_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" />
                                <button type="button" id="mpai-test-openai-api" class="button"><?php _e('Test Connection', 'memberpress-ai-assistant'); ?></button>
                                <a href="<?php echo esc_url(plugin_dir_url(dirname(__FILE__)) . 'includes/openai-test.php'); ?>" class="button" target="_blank"><?php _e('Direct Test', 'memberpress-ai-assistant'); ?></a>
                                <span id="mpai-openai-test-result" class="mpai-test-result" style="display: none;"></span>
                            </div>
                            <p class="description"><?php _e('Enter your OpenAI API key. You can get one from <a href="https://platform.openai.com/account/api-keys" target="_blank">OpenAI Dashboard</a>.', 'memberpress-ai-assistant'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="mpai_model"><?php _e('AI Model', 'memberpress-ai-assistant'); ?></label>
                        </th>
                        <td>
                            <select name="mpai_model" id="mpai_model">
                                <?php
                                foreach ($models as $model_key => $model_name) {
                                    printf(
                                        '<option value="%s" %s>%s</option>',
                                        esc_attr($model_key),
                                        selected($model, $model_key, false),
                                        esc_html($model_name)
                                    );
                                }
                                ?>
                            </select>
                            <p class="description"><?php _e('Select the OpenAI model to use.', 'memberpress-ai-assistant'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="mpai_memberpress_api_key">
                                <?php _e('MemberPress API Key', 'memberpress-ai-assistant'); ?>
                                <div class="mpai-api-status" id="memberpress-api-status">
                                    <span class="mpai-api-status-icon mpai-status-unknown"></span>
                                    <span class="mpai-api-status-text"><?php _e('Unknown', 'memberpress-ai-assistant'); ?></span>
                                </div>
                            </label>
                        </th>
                        <td>
                            <div class="mpai-key-field">
                                <input type="password" name="mpai_memberpress_api_key" id="mpai_memberpress_api_key" value="<?php echo esc_attr($memberpress_api_key); ?>" class="regular-text" />
                                <button type="button" id="mpai-test-memberpress-api" class="button"><?php _e('Test Connection', 'memberpress-ai-assistant'); ?></button>
                                <a href="<?php echo esc_url(plugin_dir_url(dirname(__FILE__)) . 'includes/memberpress-test.php'); ?>" class="button" target="_blank"><?php _e('Direct Test', 'memberpress-ai-assistant'); ?></a>
                                <span id="mpai-memberpress-test-result" class="mpai-test-result" style="display: none;"></span>
                            </div>
                            <p class="description"><?php _e('Enter your MemberPress API key. You can generate one in the MemberPress Developer Tools settings.', 'memberpress-ai-assistant'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div id="tab-cli" class="mpai-settings-tab" style="display: none;">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="mpai_enable_cli_commands"><?php _e('Enable CLI Commands', 'memberpress-ai-assistant'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="mpai_enable_cli_commands" id="mpai_enable_cli_commands" value="1" <?php checked($enable_cli_commands); ?> />
                                <?php _e('Allow running WP-CLI commands through the AI Assistant', 'memberpress-ai-assistant'); ?>
                            </label>
                            <p class="description"><?php _e('This allows the AI to execute WP-CLI commands on your behalf. Only enable if you trust your admin users.', 'memberpress-ai-assistant'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="mpai_allowed_cli_commands"><?php _e('Allowed Commands', 'memberpress-ai-assistant'); ?></label>
                        </th>
                        <td>
                            <div id="mpai-allowed-commands">
                                <?php
                                foreach ($allowed_commands as $index => $command) {
                                    printf(
                                        '<div class="mpai-command-row"><input type="text" name="mpai_allowed_cli_commands[]" value="%s" class="regular-text" /> <button type="button" class="button mpai-remove-command">%s</button></div>',
                                        esc_attr($command),
                                        __('Remove', 'memberpress-ai-assistant')
                                    );
                                }
                                ?>
                            </div>
                            <button type="button" class="button mpai-add-command"><?php _e('Add Command', 'memberpress-ai-assistant'); ?></button>
                            <p class="description"><?php _e('Specify the allowed WP-CLI commands. The AI will only be able to execute these commands. Use prefixes like "wp user" to allow all user-related commands.', 'memberpress-ai-assistant'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div id="tab-chat" class="mpai-settings-tab" style="display: none;">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="mpai_enable_chat"><?php _e('Enable Chat Interface', 'memberpress-ai-assistant'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="mpai_enable_chat" id="mpai_enable_chat" value="1" <?php checked(get_option('mpai_enable_chat', true)); ?> />
                                <?php _e('Show floating chat bubble in admin', 'memberpress-ai-assistant'); ?>
                            </label>
                            <p class="description"><?php _e('Enable or disable the floating chat interface in the WordPress admin.', 'memberpress-ai-assistant'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="mpai_chat_position"><?php _e('Chat Position', 'memberpress-ai-assistant'); ?></label>
                        </th>
                        <td>
                            <select name="mpai_chat_position" id="mpai_chat_position">
                                <option value="bottom-right" <?php selected(get_option('mpai_chat_position', 'bottom-right'), 'bottom-right'); ?>><?php _e('Bottom Right', 'memberpress-ai-assistant'); ?></option>
                                <option value="bottom-left" <?php selected(get_option('mpai_chat_position', 'bottom-right'), 'bottom-left'); ?>><?php _e('Bottom Left', 'memberpress-ai-assistant'); ?></option>
                                <option value="top-right" <?php selected(get_option('mpai_chat_position', 'bottom-right'), 'top-right'); ?>><?php _e('Top Right', 'memberpress-ai-assistant'); ?></option>
                                <option value="top-left" <?php selected(get_option('mpai_chat_position', 'bottom-right'), 'top-left'); ?>><?php _e('Top Left', 'memberpress-ai-assistant'); ?></option>
                            </select>
                            <p class="description"><?php _e('Choose where the chat bubble should appear.', 'memberpress-ai-assistant'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="mpai_show_on_all_pages"><?php _e('Display Scope', 'memberpress-ai-assistant'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="mpai_show_on_all_pages" id="mpai_show_on_all_pages" value="1" <?php checked(get_option('mpai_show_on_all_pages', true)); ?> />
                                <?php _e('Show on all admin pages', 'memberpress-ai-assistant'); ?>
                            </label>
                            <p class="description"><?php _e('If unchecked, the chat will only appear on MemberPress admin pages.', 'memberpress-ai-assistant'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="mpai_welcome_message"><?php _e('Welcome Message', 'memberpress-ai-assistant'); ?></label>
                        </th>
                        <td>
                            <textarea name="mpai_welcome_message" id="mpai_welcome_message" class="large-text" rows="3"><?php echo esc_textarea(get_option('mpai_welcome_message', 'Hi there! I\'m your MemberPress AI Assistant. How can I help you today?')); ?></textarea>
                            <p class="description"><?php _e('The welcome message shown when the chat is opened.', 'memberpress-ai-assistant'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <div id="tab-advanced" class="mpai-settings-tab" style="display: none;">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="mpai_temperature"><?php _e('Temperature', 'memberpress-ai-assistant'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="mpai_temperature" id="mpai_temperature" value="<?php echo esc_attr($temperature); ?>" class="regular-text" min="0" max="2" step="0.1" />
                            <p class="description"><?php _e('Controls randomness: lower values make responses more focused and deterministic (0-2).', 'memberpress-ai-assistant'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="mpai_max_tokens"><?php _e('Max Tokens', 'memberpress-ai-assistant'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="mpai_max_tokens" id="mpai_max_tokens" value="<?php echo esc_attr($max_tokens); ?>" class="regular-text" min="1" max="16000" step="1" />
                            <p class="description"><?php _e('Maximum number of tokens to generate in the response.', 'memberpress-ai-assistant'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div id="tab-debug" class="mpai-settings-tab" style="display: none;">
                <h3><?php _e('Debugging Tools', 'memberpress-ai-assistant'); ?></h3>
                <p><?php _e('These tools are intended for debugging purposes only. Use them to diagnose issues with the API connections and AJAX functionality.', 'memberpress-ai-assistant'); ?></p>
                
                <div class="mpai-debug-section">
                    <h4><?php _e('Diagnostic Links', 'memberpress-ai-assistant'); ?></h4>
                    <p>
                        <a href="<?php echo esc_url(plugin_dir_url(dirname(__FILE__)) . 'includes/debug-info.php'); ?>" class="button" target="_blank"><?php _e('Debug Info', 'memberpress-ai-assistant'); ?></a>
                        <a href="<?php echo esc_url(plugin_dir_url(dirname(__FILE__)) . 'includes/ajax-test.php'); ?>" class="button" target="_blank"><?php _e('AJAX Diagnostics', 'memberpress-ai-assistant'); ?></a>
                        <a href="<?php echo esc_url(plugin_dir_url(dirname(__FILE__)) . 'includes/ajax-test.php?run_ajax_test=1'); ?>" class="button" target="_blank"><?php _e('Direct AJAX Test', 'memberpress-ai-assistant'); ?></a>
                    </p>
                </div>
                
                <div class="mpai-debug-section">
                    <h4><?php _e('AJAX Tests', 'memberpress-ai-assistant'); ?></h4>
                    <p>
                        <button type="button" id="mpai-simple-test" class="button"><?php _e('Simple AJAX Test', 'memberpress-ai-assistant'); ?></button>
                        <button type="button" id="mpai-nonce-test" class="button"><?php _e('Test Nonce', 'memberpress-ai-assistant'); ?></button>
                    </p>
                    <div id="mpai-debug-results" class="mpai-debug-results" style="display: none;">
                        <h4><?php _e('Test Results', 'memberpress-ai-assistant'); ?></h4>
                        <pre id="mpai-debug-output"></pre>
                    </div>
                </div>
                
                <div class="mpai-debug-section">
                    <h4><?php _e('Error Log', 'memberpress-ai-assistant'); ?></h4>
                    <p><?php _e('Check your WordPress error log for more detailed information about any issues.', 'memberpress-ai-assistant'); ?></p>
                </div>
            </div>
        </div>
        
        <p class="submit">
            <input type="submit" name="mpai_save_settings" class="button button-primary" value="<?php esc_attr_e('Save Settings', 'memberpress-ai-assistant'); ?>" />
        </p>
    </form>
</div>

<script>
// Define a safer document ready handler that checks for mpai_data
function initMpaiSettings() {
    if (typeof jQuery === 'undefined') {
        console.error('MPAI: jQuery is not loaded!');
        setTimeout(initMpaiSettings, 100);
        return;
    }
    
    if (typeof mpai_data === 'undefined') {
        console.error('MPAI: mpai_data is not available!');
        setTimeout(initMpaiSettings, 100);
        return;
    }
    
    console.log('MPAI: Initializing settings page with mpai_data:', {
        nonce: mpai_data.nonce ? mpai_data.nonce.substring(0, 5) + '...' : 'undefined',
        ajax_url: mpai_data.ajax_url
    });
    
    jQuery(document).ready(function($) {
        // Tab navigation
        $('.nav-tab').on('click', function(e) {
            e.preventDefault();
            
            // Hide all tabs
            $('.mpai-settings-tab').hide();
            
            // Remove active class
            $('.nav-tab').removeClass('nav-tab-active');
            
            // Show the selected tab
            $($(this).attr('href')).show();
            
            // Add active class
            $(this).addClass('nav-tab-active');
        });
        
        // Add command
        $('.mpai-add-command').on('click', function() {
            var newRow = '<div class="mpai-command-row"><input type="text" name="mpai_allowed_cli_commands[]" value="" class="regular-text" /> <button type="button" class="button mpai-remove-command"><?php echo esc_js(__('Remove', 'memberpress-ai-assistant')); ?></button></div>';
            $('#mpai-allowed-commands').append(newRow);
        });
        
        // Remove command
        $(document).on('click', '.mpai-remove-command', function() {
            $(this).closest('.mpai-command-row').remove();
        });
        
        // Test OpenAI API Connection
        $('#mpai-test-openai-api').on('click', function() {
            var apiKey = $('#mpai_api_key').val();
            var $resultContainer = $('#mpai-openai-test-result');
            
            // Use the globally localized nonce instead of PHP echoed one
            console.log('Test OpenAI clicked with localized nonce');
            
            if (!apiKey) {
                $resultContainer.html('<?php echo esc_js(__('Please enter an API key first', 'memberpress-ai-assistant')); ?>');
                $resultContainer.addClass('mpai-test-error').removeClass('mpai-test-success mpai-test-loading');
                $resultContainer.show();
                return;
            }
            
            // Show loading state
            $(this).prop('disabled', true);
            $resultContainer.html('<?php echo esc_js(__('Testing...', 'memberpress-ai-assistant')); ?>');
            $resultContainer.addClass('mpai-test-loading').removeClass('mpai-test-success mpai-test-error');
            $resultContainer.show();
            
            // Make AJAX request to test the API
            console.log('MPAI: Testing OpenAI API with nonce:', mpai_data.nonce ? mpai_data.nonce.substring(0, 5) + '...' : 'undefined');
            console.log('MPAI: AJAX URL:', ajaxurl);
            
            // Try the direct AJAX handler instead of admin-ajax.php
            var directHandlerUrl = '<?php echo plugin_dir_url(dirname(__FILE__)) . 'includes/direct-ajax-handler.php'; ?>';
            
            // Create the form data object directly to ensure proper formatting
            var formData = new FormData();
            formData.append('action', 'test_openai');
            formData.append('nonce', mpai_data.nonce);
            formData.append('api_key', apiKey);
            
            // Log what we're sending for debugging
            console.log('MPAI: FormData prepared with direct AJAX handler and nonce length:', 
                        mpai_data.nonce ? mpai_data.nonce.length : 0);
            console.log('MPAI: Direct handler URL:', directHandlerUrl);
            
            // Use fetch API with direct handler
            fetch(directHandlerUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(function(response) {
                console.log('MPAI: Fetch response status:', response.status);
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.json();
            })
            .then(function(data) {
                console.log('MPAI: API test response:', data);
                if (data.success) {
                    $resultContainer.html(data.data);
                    $resultContainer.addClass('mpai-test-success').removeClass('mpai-test-loading mpai-test-error');
                } else {
                    $resultContainer.html(data.data);
                    $resultContainer.addClass('mpai-test-error').removeClass('mpai-test-loading mpai-test-success');
                }
                $('#mpai-test-openai-api').prop('disabled', false);
            })
            .catch(function(error) {
                console.error('MPAI: Fetch error:', error);
                $resultContainer.html('Error: ' + error.message);
                $resultContainer.addClass('mpai-test-error').removeClass('mpai-test-loading mpai-test-success');
                $('#mpai-test-openai-api').prop('disabled', false);
            });
        });
        
        // Test MemberPress API Connection
        $('#mpai-test-memberpress-api').on('click', function() {
            var apiKey = $('#mpai_memberpress_api_key').val();
            var $resultContainer = $('#mpai-memberpress-test-result');
            
            // Use the globally localized nonce
            console.log('Test MemberPress API clicked with localized nonce');
            
            if (!apiKey) {
                $resultContainer.html('<?php echo esc_js(__('Please enter an API key first', 'memberpress-ai-assistant')); ?>');
                $resultContainer.addClass('mpai-test-error').removeClass('mpai-test-success mpai-test-loading');
                $resultContainer.show();
                return;
            }
            
            // Show loading state
            $(this).prop('disabled', true);
            $resultContainer.html('<?php echo esc_js(__('Testing...', 'memberpress-ai-assistant')); ?>');
            $resultContainer.addClass('mpai-test-loading').removeClass('mpai-test-success mpai-test-error');
            $resultContainer.show();
            
            // Make AJAX request to test the API
            console.log('MPAI: Testing MemberPress API with nonce:', mpai_data.nonce ? mpai_data.nonce.substring(0, 5) + '...' : 'undefined');
            console.log('MPAI: AJAX URL:', ajaxurl);
            
            // Try the direct AJAX handler instead of admin-ajax.php
            var directHandlerUrl = '<?php echo plugin_dir_url(dirname(__FILE__)) . 'includes/direct-ajax-handler.php'; ?>';
            
            // Create the form data object directly to ensure proper formatting
            var formData = new FormData();
            formData.append('action', 'test_memberpress');
            formData.append('nonce', mpai_data.nonce);
            formData.append('api_key', apiKey);
            
            // Log what we're sending for debugging
            console.log('MPAI: FormData prepared with direct AJAX handler and nonce length:', 
                        mpai_data.nonce ? mpai_data.nonce.length : 0);
            console.log('MPAI: Direct handler URL:', directHandlerUrl);
            
            // Use fetch API with direct handler
            fetch(directHandlerUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(function(response) {
                console.log('MPAI: Fetch response status:', response.status);
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.json();
            })
            .then(function(data) {
                console.log('MPAI: MemberPress API test response:', data);
                if (data.success) {
                    $resultContainer.html(data.data);
                    $resultContainer.addClass('mpai-test-success').removeClass('mpai-test-loading mpai-test-error');
                } else {
                    $resultContainer.html(data.data);
                    $resultContainer.addClass('mpai-test-error').removeClass('mpai-test-loading mpai-test-success');
                }
                $('#mpai-test-memberpress-api').prop('disabled', false);
            })
            .catch(function(error) {
                console.error('MPAI: Fetch error:', error);
                $resultContainer.html('Error: ' + error.message);
                $resultContainer.addClass('mpai-test-error').removeClass('mpai-test-loading mpai-test-success');
                $('#mpai-test-memberpress-api').prop('disabled', false);
            });
        });
        
        // Simple AJAX test
        $('#mpai-simple-test').on('click', function() {
            console.log('MPAI: Simple AJAX test clicked');
            
            // Show the results container and update with status
            $('#mpai-debug-results').show();
            $('#mpai-debug-output').html('Running simple AJAX test...');
            
            // Try the direct AJAX handler instead of admin-ajax.php
            var directHandlerUrl = '<?php echo plugin_dir_url(dirname(__FILE__)) . 'includes/direct-ajax-handler.php'; ?>';
            
            // Create FormData for fetch API
            var formData = new FormData();
            formData.append('action', 'test_simple');
            formData.append('test_data', 'This is a test');
            
            console.log('MPAI: Sending simple AJAX test with direct handler');
            console.log('MPAI: Direct handler URL:', directHandlerUrl);
            
            // Use fetch API with direct handler
            fetch(directHandlerUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(function(response) {
                console.log('MPAI: Simple test fetch response status:', response.status);
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.json();
            })
            .then(function(data) {
                console.log('MPAI: Simple AJAX test response:', data);
                
                // Update the results with formatted JSON
                var resultHtml = '<span style="color: green; font-weight: bold;">✓ Success!</span><br><br>';
                resultHtml += '<strong>Response:</strong><br>';
                resultHtml += JSON.stringify(data, null, 2);
                
                $('#mpai-debug-output').html(resultHtml);
            })
            .catch(function(error) {
                console.error('MPAI: Simple AJAX test error:', error);
                
                // Update the results with error
                var resultHtml = '<span style="color: red; font-weight: bold;">✗ Error!</span><br><br>';
                resultHtml += '<strong>Error details:</strong><br>';
                resultHtml += error.message;
                
                $('#mpai-debug-output').html(resultHtml);
            });
        });
        
        // Nonce test
        $('#mpai-nonce-test').on('click', function() {
            console.log('MPAI: Nonce test clicked');
            
            // Show the results container and update with status
            $('#mpai-debug-results').show();
            $('#mpai-debug-output').html('Testing nonce verification...');
            
            // Create FormData for fetch API
            var formData = new FormData();
            formData.append('action', 'mpai_debug_nonce');
            formData.append('mpai_nonce', mpai_data.nonce);
            
            console.log('MPAI: Sending nonce test request with nonce:', 
                       mpai_data.nonce ? mpai_data.nonce.substring(0, 5) + '...' : 'undefined');
            
            // Use fetch API for better error handling
            fetch(ajaxurl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(function(response) {
                console.log('MPAI: Nonce test fetch response status:', response.status);
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.json();
            })
            .then(function(data) {
                console.log('MPAI: Nonce test response:', data);
                
                // Format the results in HTML
                var resultHtml = '<span style="color: ' + (data.success ? 'green' : 'red') + '; font-weight: bold;">';
                resultHtml += data.success ? '✓ Success!' : '✗ Failed!';
                resultHtml += '</span><br><br>';
                
                resultHtml += '<strong>Message:</strong> ' + data.message + '<br><br>';
                
                if (data.data) {
                    resultHtml += '<strong>Details:</strong><br>';
                    resultHtml += 'Nonce provided: ' + data.data.nonce_provided + '<br>';
                    resultHtml += 'Verification result: ' + data.data.verified + '<br>';
                    if (data.data.verified_alt) {
                        resultHtml += 'Alt verification result: ' + data.data.verified_alt + '<br>';
                    }
                    resultHtml += 'New test nonce: ' + data.data.new_test_nonce + '<br>';
                }
                
                $('#mpai-debug-output').html(resultHtml);
            })
            .catch(function(error) {
                console.error('MPAI: Nonce test error:', error);
                
                // Update results with error
                var resultHtml = '<span style="color: red; font-weight: bold;">✗ Error!</span><br><br>';
                resultHtml += '<strong>Error details:</strong><br>';
                resultHtml += error.message;
                
                $('#mpai-debug-output').html(resultHtml);
            });
        });
    });
}

// Initialize the settings page
initMpaiSettings();
</script>

<style>
/* Style for Debug tab */
.mpai-debug-section {
    margin-bottom: 20px;
    padding: 15px;
    background: #f9f9f9;
    border: 1px solid #e5e5e5;
    border-radius: 5px;
}

.mpai-debug-section h4 {
    margin-top: 0;
    margin-bottom: 10px;
    font-size: 16px;
}

.mpai-debug-results {
    margin-top: 15px;
    padding: 15px;
    background: #fff;
    border: 1px solid #e5e5e5;
    border-radius: 5px;
}

.mpai-debug-results pre {
    margin: 0;
    padding: 10px;
    background: #f5f5f5;
    border: 1px solid #ddd;
    border-radius: 3px;
    overflow: auto;
    white-space: pre-wrap;
    font-family: monospace;
    font-size: 12px;
    line-height: 1.5;
}

.mpai-debug-section .button {
    margin-right: 5px;
    margin-bottom: 5px;
}
</style>