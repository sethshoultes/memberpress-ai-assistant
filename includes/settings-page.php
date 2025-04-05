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

// Direct menu fix for settings page
global $parent_file, $submenu_file;
$parent_file = class_exists('MeprAppCtrl') ? 'memberpress' : 'memberpress-ai-assistant';
$submenu_file = 'memberpress-ai-assistant-settings';

// Try loading MPAI_Settings class if needed
if (!class_exists('MPAI_Settings')) {
    $settings_path = dirname(__FILE__) . '/class-mpai-settings.php';
    if (file_exists($settings_path)) {
        require_once $settings_path;
    }
}

// Generate the nonce for AJAX requests
$mpai_settings_nonce = wp_create_nonce('mpai_nonce');

// Process form submission - direct approach without using Settings API
if (isset($_POST['mpai_save_settings']) && check_admin_referer('mpai_settings_nonce', 'mpai_nonce')) {
    // OpenAI API Settings
    if (isset($_POST['mpai_api_key'])) {
        update_option('mpai_api_key', sanitize_text_field($_POST['mpai_api_key']));
    }
    
    if (isset($_POST['mpai_model'])) {
        update_option('mpai_model', sanitize_text_field($_POST['mpai_model']));
    }
    
    // Anthropic API Settings
    if (isset($_POST['mpai_anthropic_api_key'])) {
        update_option('mpai_anthropic_api_key', sanitize_text_field($_POST['mpai_anthropic_api_key']));
    }
    
    if (isset($_POST['mpai_anthropic_model'])) {
        update_option('mpai_anthropic_model', sanitize_text_field($_POST['mpai_anthropic_model']));
    }
    
    // Primary API Selection
    if (isset($_POST['mpai_primary_api'])) {
        update_option('mpai_primary_api', sanitize_text_field($_POST['mpai_primary_api']));
    }
    
    // MemberPress API Key - removed as not needed
    
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
    
    // AI Tools
    update_option('mpai_enable_mcp', isset($_POST['mpai_enable_mcp']) ? '1' : '0');
    update_option('mpai_enable_wp_cli_tool', isset($_POST['mpai_enable_wp_cli_tool']) ? '1' : '0');
    update_option('mpai_enable_memberpress_info_tool', isset($_POST['mpai_enable_memberpress_info_tool']) ? '1' : '0');
    
    // Advanced Settings - OpenAI
    if (isset($_POST['mpai_temperature'])) {
        update_option('mpai_temperature', floatval($_POST['mpai_temperature']));
    }
    
    if (isset($_POST['mpai_max_tokens'])) {
        update_option('mpai_max_tokens', absint($_POST['mpai_max_tokens']));
    }
    
    // Advanced Settings - Anthropic
    if (isset($_POST['mpai_anthropic_temperature'])) {
        update_option('mpai_anthropic_temperature', floatval($_POST['mpai_anthropic_temperature']));
    }
    
    if (isset($_POST['mpai_anthropic_max_tokens'])) {
        update_option('mpai_anthropic_max_tokens', absint($_POST['mpai_anthropic_max_tokens']));
    }
    
    // Console Logging Settings
    update_option('mpai_enable_console_logging', isset($_POST['mpai_enable_console_logging']) ? '1' : '0');
    
    if (isset($_POST['mpai_console_log_level'])) {
        update_option('mpai_console_log_level', sanitize_text_field($_POST['mpai_console_log_level']));
    }
    
    update_option('mpai_log_api_calls', isset($_POST['mpai_log_api_calls']) ? '1' : '0');
    update_option('mpai_log_tool_usage', isset($_POST['mpai_log_tool_usage']) ? '1' : '0');
    update_option('mpai_log_agent_activity', isset($_POST['mpai_log_agent_activity']) ? '1' : '0');
    update_option('mpai_log_timing', isset($_POST['mpai_log_timing']) ? '1' : '0');
    
    // Show success message
    add_settings_error('mpai_messages', 'mpai_success', __('Settings saved successfully.', 'memberpress-ai-assistant'), 'updated');
}

// Get current settings - OpenAI
$api_key = get_option('mpai_api_key', '');
$model = get_option('mpai_model', 'gpt-4o');
$temperature = get_option('mpai_temperature', 0.7);
$max_tokens = get_option('mpai_max_tokens', 2048);

// Get current settings - Anthropic
$anthropic_api_key = get_option('mpai_anthropic_api_key', '');
$anthropic_model = get_option('mpai_anthropic_model', 'claude-3-opus-20240229');
$anthropic_temperature = get_option('mpai_anthropic_temperature', 0.7);
$anthropic_max_tokens = get_option('mpai_anthropic_max_tokens', 2048);

// Get primary API setting
$primary_api = get_option('mpai_primary_api', 'openai');

// MemberPress API Key retrieval removed - not needed

// Get CLI command settings
$enable_cli_commands = get_option('mpai_enable_cli_commands', false);
$allowed_commands = get_option('mpai_allowed_cli_commands', array());

// Default allowed commands if empty
if (empty($allowed_commands)) {
    $allowed_commands = array(
        'wp user list',
        'wp post list',
        'wp plugin list',
    );
}

// Get available models and providers
$settings = new MPAI_Settings();
$openai_models = $settings->get_available_models();
$anthropic_models = $settings->get_available_anthropic_models();
$api_providers = $settings->get_available_api_providers();

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
    
    <!-- Direct console test script -->
    <script>
    // These console messages should appear in the browser console regardless of any plugin JS
    console.log('🟢 SETTINGS PAGE TEST: This message should appear in the console');
    console.error('🟢 SETTINGS PAGE TEST: This error message should appear in red');
    console.warn('🟢 SETTINGS PAGE TEST: This warning message should appear in yellow');
    
    // Add a test button directly in the settings page
    document.addEventListener('DOMContentLoaded', function() {
        var testButton = document.createElement('button');
        testButton.className = 'button';
        testButton.innerText = 'Test Console Directly';
        testButton.style.marginBottom = '10px';
        testButton.addEventListener('click', function() {
            console.group('🟢 Direct Console Test from Settings Button');
            console.log('Button clicked at ' + new Date().toISOString());
            console.log('Test Object:', { test: 'value', number: 123 });
            console.error('Test Error Message');
            console.warn('Test Warning Message');
            
            // Add a browser alert so the user knows something happened
            alert('Test logs sent to console - check developer tools (F12)');
            
            console.groupEnd();
        });
        
        document.querySelector('.mpai-settings-page h1').after(testButton);
    });
    </script>

    <form method="post" action="">
        <?php wp_nonce_field('mpai_settings_nonce', 'mpai_nonce'); ?>
        
        <div class="mpai-settings-container">
            <h2 class="nav-tab-wrapper">
                <a href="#tab-api" class="nav-tab nav-tab-active"><?php _e('API Settings', 'memberpress-ai-assistant'); ?></a>
                <a href="#tab-chat" class="nav-tab"><?php _e('Chat Interface', 'memberpress-ai-assistant'); ?></a>
                <a href="#tab-cli" class="nav-tab"><?php _e('CLI Commands', 'memberpress-ai-assistant'); ?></a>
                <a href="#tab-tools" class="nav-tab"><?php _e('AI Tools', 'memberpress-ai-assistant'); ?></a>
                <a href="#tab-advanced" class="nav-tab"><?php _e('Advanced', 'memberpress-ai-assistant'); ?></a>
                <a href="#tab-diagnostic" class="nav-tab"><?php _e('Diagnostics', 'memberpress-ai-assistant'); ?></a>
            </h2>
            
            <div id="tab-api" class="mpai-settings-tab">
                <h3><?php _e('API Selection', 'memberpress-ai-assistant'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="mpai_primary_api"><?php _e('Primary API Provider', 'memberpress-ai-assistant'); ?></label>
                        </th>
                        <td>
                            <select name="mpai_primary_api" id="mpai_primary_api">
                                <?php
                                foreach ($api_providers as $provider_key => $provider_name) {
                                    printf(
                                        '<option value="%s" %s>%s</option>',
                                        esc_attr($provider_key),
                                        selected($primary_api, $provider_key, false),
                                        esc_html($provider_name)
                                    );
                                }
                                ?>
                            </select>
                            <p class="description"><?php _e('Select which AI provider to use as the primary service. The other provider will be used as a fallback if the primary one fails.', 'memberpress-ai-assistant'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <h3><?php _e('OpenAI Settings', 'memberpress-ai-assistant'); ?></h3>
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
                            <label for="mpai_model"><?php _e('OpenAI Model', 'memberpress-ai-assistant'); ?></label>
                        </th>
                        <td>
                            <select name="mpai_model" id="mpai_model">
                                <?php
                                foreach ($openai_models as $model_key => $model_name) {
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
                </table>
                
                <h3><?php _e('Anthropic (Claude) Settings', 'memberpress-ai-assistant'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="mpai_anthropic_api_key">
                                <?php _e('Anthropic API Key', 'memberpress-ai-assistant'); ?>
                                <div class="mpai-api-status" id="anthropic-api-status">
                                    <span class="mpai-api-status-icon mpai-status-unknown"></span>
                                    <span class="mpai-api-status-text"><?php _e('Unknown', 'memberpress-ai-assistant'); ?></span>
                                </div>
                            </label>
                        </th>
                        <td>
                            <div class="mpai-key-field">
                                <input type="password" name="mpai_anthropic_api_key" id="mpai_anthropic_api_key" value="<?php echo esc_attr($anthropic_api_key); ?>" class="regular-text" />
                                <button type="button" id="mpai-test-anthropic-api" class="button"><?php _e('Test Connection', 'memberpress-ai-assistant'); ?></button>
                                <span id="mpai-anthropic-test-result" class="mpai-test-result" style="display: none;"></span>
                            </div>
                            <p class="description"><?php _e('Enter your Anthropic API key. You can get one from <a href="https://console.anthropic.com/settings/keys" target="_blank">Anthropic Console</a>.', 'memberpress-ai-assistant'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="mpai_anthropic_model"><?php _e('Claude Model', 'memberpress-ai-assistant'); ?></label>
                        </th>
                        <td>
                            <select name="mpai_anthropic_model" id="mpai_anthropic_model">
                                <?php
                                foreach ($anthropic_models as $model_key => $model_name) {
                                    printf(
                                        '<option value="%s" %s>%s</option>',
                                        esc_attr($model_key),
                                        selected($anthropic_model, $model_key, false),
                                        esc_html($model_name)
                                    );
                                }
                                ?>
                            </select>
                            <p class="description"><?php _e('Select the Anthropic Claude model to use.', 'memberpress-ai-assistant'); ?></p>
                        </td>
                    </tr>
                </table>
                <!-- MemberPress API Integration section removed as it is not needed -->
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

            <div id="tab-tools" class="mpai-settings-tab" style="display: none;">
                <h3><?php _e('AI Tool Configuration', 'memberpress-ai-assistant'); ?></h3>
                <p><?php _e('Configure tools available to the AI assistant. These tools allow the AI to perform actions when requested.', 'memberpress-ai-assistant'); ?></p>
                
                <div class="mpai-tools-section">
                    <h4><?php _e('Available Tools', 'memberpress-ai-assistant'); ?></h4>
                    
                    <div class="mpai-tool-card">
                        <div class="mpai-tool-header">
                            <h4><?php _e('WP CLI Tool', 'memberpress-ai-assistant'); ?></h4>
                            <label class="mpai-toggle">
                                <input type="checkbox" name="mpai_enable_wp_cli_tool" value="1" <?php checked(get_option('mpai_enable_wp_cli_tool', true)); ?> />
                                <span class="mpai-toggle-slider"></span>
                            </label>
                        </div>
                        <p><?php _e('Allows the AI to execute WP-CLI commands.', 'memberpress-ai-assistant'); ?></p>
                        <div class="mpai-tool-details">
                            <p><strong><?php _e('Usage:', 'memberpress-ai-assistant'); ?></strong> <?php _e('When enabled, the AI can execute commands like "wp user list" or "wp plugin list" directly.', 'memberpress-ai-assistant'); ?></p>
                            <p><strong><?php _e('Format:', 'memberpress-ai-assistant'); ?></strong> <code>{"tool": "wp_cli", "parameters": {"command": "wp user list"}}</code></p>
                            <p><strong><?php _e('Note:', 'memberpress-ai-assistant'); ?></strong> <?php _e('Only commands configured in the CLI Commands tab will be allowed.', 'memberpress-ai-assistant'); ?></p>
                        </div>
                    </div>
                    
                    <div class="mpai-tool-card">
                        <div class="mpai-tool-header">
                            <h4><?php _e('MemberPress Info Tool', 'memberpress-ai-assistant'); ?></h4>
                            <label class="mpai-toggle">
                                <input type="checkbox" name="mpai_enable_memberpress_info_tool" value="1" <?php checked(get_option('mpai_enable_memberpress_info_tool', true)); ?> />
                                <span class="mpai-toggle-slider"></span>
                            </label>
                        </div>
                        <p><?php _e('Allows the AI to fetch MemberPress data.', 'memberpress-ai-assistant'); ?></p>
                        <div class="mpai-tool-details">
                            <p><strong><?php _e('Usage:', 'memberpress-ai-assistant'); ?></strong> <?php _e('When enabled, the AI can fetch information about memberships, members, transactions, and subscriptions.', 'memberpress-ai-assistant'); ?></p>
                            <p><strong><?php _e('Format:', 'memberpress-ai-assistant'); ?></strong> <code>{"tool": "memberpress_info", "parameters": {"type": "memberships"}}</code></p>
                            <p><strong><?php _e('Available Types:', 'memberpress-ai-assistant'); ?></strong> <code>memberships, members, transactions, subscriptions, summary</code></p>
                        </div>
                    </div>
                </div>
                
                <h3><?php _e('Model Context Protocol (MCP)', 'memberpress-ai-assistant'); ?></h3>
                <p><?php _e('The Model Context Protocol allows the AI assistant to use tools to perform actions. When enabled, the AI will be aware of available tools and can use them when needed.', 'memberpress-ai-assistant'); ?></p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="mpai_enable_mcp"><?php _e('Enable MCP', 'memberpress-ai-assistant'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="mpai_enable_mcp" id="mpai_enable_mcp" value="1" <?php checked(get_option('mpai_enable_mcp', true)); ?> />
                                <?php _e('Allow the AI assistant to use tools via MCP', 'memberpress-ai-assistant'); ?>
                            </label>
                            <p class="description"><?php _e('When enabled, the AI assistant can use tools to perform actions when you ask it to.', 'memberpress-ai-assistant'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div id="tab-advanced" class="mpai-settings-tab" style="display: none;">
                <h3><?php _e('OpenAI Advanced Settings', 'memberpress-ai-assistant'); ?></h3>
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
                
                <h3><?php _e('Anthropic Advanced Settings', 'memberpress-ai-assistant'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="mpai_anthropic_temperature"><?php _e('Temperature', 'memberpress-ai-assistant'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="mpai_anthropic_temperature" id="mpai_anthropic_temperature" value="<?php echo esc_attr($anthropic_temperature); ?>" class="regular-text" min="0" max="1" step="0.01" />
                            <p class="description"><?php _e('Controls randomness: lower values make responses more focused and deterministic (0-1).', 'memberpress-ai-assistant'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="mpai_anthropic_max_tokens"><?php _e('Max Tokens', 'memberpress-ai-assistant'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="mpai_anthropic_max_tokens" id="mpai_anthropic_max_tokens" value="<?php echo esc_attr($anthropic_max_tokens); ?>" class="regular-text" min="1" max="4096" step="1" />
                            <p class="description"><?php _e('Maximum number of tokens to generate in the response.', 'memberpress-ai-assistant'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            
            <?php 
            // Load the new diagnostic system with error handling
            error_log('MPAI DEBUG: Loading improved diagnostic system...');
            
            // Check for the diagnostic class
            if (!class_exists('MPAI_Diagnostics')) {
                $diagnostics_class_file = MPAI_PLUGIN_DIR . 'includes/class-mpai-diagnostics.php';
                if (file_exists($diagnostics_class_file)) {
                    error_log('MPAI DEBUG: Loading MPAI_Diagnostics class file...');
                    try {
                        include_once $diagnostics_class_file;
                        error_log('MPAI DEBUG: MPAI_Diagnostics class loaded successfully');
                    } catch (Exception $e) {
                        error_log('MPAI ERROR: Failed to load diagnostics class: ' . $e->getMessage());
                    }
                } else {
                    error_log('MPAI ERROR: Diagnostics class file not found at: ' . $diagnostics_class_file);
                }
            }
            
            // Render the diagnostic interface if the class exists
            if (class_exists('MPAI_Diagnostics')) {
                error_log('MPAI DEBUG: Rendering improved diagnostics interface...');
                try {
                    MPAI_Diagnostics::render_interface();
                    error_log('MPAI DEBUG: Diagnostics interface rendered successfully');
                } catch (Exception $e) {
                    error_log('MPAI ERROR: Exception rendering diagnostics interface: ' . $e->getMessage());
                    // Fallback UI on error
                    echo '<div id="tab-diagnostic" class="mpai-settings-tab" style="display: none;">';
                    echo '<h3>Diagnostics</h3>';
                    echo '<div class="mpai-notice mpai-notice-error">';
                    echo '<p>Error rendering diagnostics interface: ' . esc_html($e->getMessage()) . '</p>';
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                // Create the simplified diagnostic tab directly
                error_log('MPAI DEBUG: Creating simplified diagnostics tab');
                ?>
                <div id="tab-diagnostic" class="mpai-settings-tab" style="display: none;">
                    <h3><?php _e('System Diagnostics', 'memberpress-ai-assistant'); ?></h3>
                    <p><?php _e('Run various diagnostic tests to check the health of your MemberPress AI Assistant installation.', 'memberpress-ai-assistant'); ?></p>
                    
                    <div class="mpai-diagnostic-section">
                        <h4><?php _e('System Information', 'memberpress-ai-assistant'); ?></h4>
                        <p><?php _e('Information about your WordPress and server environment.', 'memberpress-ai-assistant'); ?></p>
                        
                        <div class="mpai-diagnostic-card">
                            <div class="mpai-diagnostic-header">
                                <h4><?php _e('System Cache Test', 'memberpress-ai-assistant'); ?></h4>
                                <div class="mpai-status-indicator" id="system-cache-status-indicator">
                                    <span class="mpai-status-dot mpai-status-unknown"></span>
                                    <span class="mpai-status-text"><?php _e('Not Tested', 'memberpress-ai-assistant'); ?></span>
                                </div>
                            </div>
                            <p><?php _e('Tests the system information cache functionality.', 'memberpress-ai-assistant'); ?></p>
                            <div class="mpai-diagnostic-actions">
                                <button type="button" id="run-system-cache-test" class="button"><?php _e('Run Test', 'memberpress-ai-assistant'); ?></button>
                            </div>
                            <div class="mpai-diagnostic-result" id="system-cache-result" style="display: none;"></div>
                        </div>
                    </div>

                    <div class="mpai-diagnostic-section">
                        <h4><?php _e('Console Logging', 'memberpress-ai-assistant'); ?></h4>
                        <p><?php _e('Configure the browser console logging system to help with debugging and monitoring.', 'memberpress-ai-assistant'); ?></p>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="mpai_enable_console_logging"><?php _e('Enable Console Logging', 'memberpress-ai-assistant'); ?></label>
                                </th>
                                <td>
                                    <div class="console-logging-control">
                                        <label>
                                            <input type="checkbox" name="mpai_enable_console_logging" id="mpai_enable_console_logging" value="1" <?php checked(get_option('mpai_enable_console_logging', '0'), '1'); ?> />
                                            <?php _e('Log detailed information to the browser console', 'memberpress-ai-assistant'); ?>
                                        </label>
                                        <span id="mpai-console-logging-status" class="logging-status-indicator <?php echo get_option('mpai_enable_console_logging', '0') === '1' ? 'active' : 'inactive'; ?>">
                                            <?php echo get_option('mpai_enable_console_logging', '0') === '1' ? 'ENABLED' : 'DISABLED'; ?>
                                        </span>
                                    </div>
                                    <p class="description"><?php _e('Enable this option to log detailed information about AI Assistant operations to your browser console.', 'memberpress-ai-assistant'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="mpai_console_log_level"><?php _e('Log Level', 'memberpress-ai-assistant'); ?></label>
                                </th>
                                <td>
                                    <select name="mpai_console_log_level" id="mpai_console_log_level">
                                        <option value="error" <?php selected(get_option('mpai_console_log_level', 'info'), 'error'); ?>><?php _e('Error Only', 'memberpress-ai-assistant'); ?></option>
                                        <option value="warning" <?php selected(get_option('mpai_console_log_level', 'info'), 'warning'); ?>><?php _e('Warning & Error', 'memberpress-ai-assistant'); ?></option>
                                        <option value="info" <?php selected(get_option('mpai_console_log_level', 'info'), 'info'); ?>><?php _e('Info, Warning & Error', 'memberpress-ai-assistant'); ?></option>
                                        <option value="debug" <?php selected(get_option('mpai_console_log_level', 'info'), 'debug'); ?>><?php _e('All (Debug)', 'memberpress-ai-assistant'); ?></option>
                                    </select>
                                    <p class="description"><?php _e('Select the level of detail to log in the browser console.', 'memberpress-ai-assistant'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label><?php _e('Log Categories', 'memberpress-ai-assistant'); ?></label>
                                </th>
                                <td>
                                    <fieldset>
                                        <legend class="screen-reader-text"><?php _e('Log Categories', 'memberpress-ai-assistant'); ?></legend>
                                        <label>
                                            <input type="checkbox" name="mpai_log_api_calls" id="mpai_log_api_calls" value="1" <?php checked(get_option('mpai_log_api_calls', true)); ?> />
                                            <?php _e('API Calls (Anthropic & OpenAI)', 'memberpress-ai-assistant'); ?>
                                        </label><br>
                                        <label>
                                            <input type="checkbox" name="mpai_log_tool_usage" id="mpai_log_tool_usage" value="1" <?php checked(get_option('mpai_log_tool_usage', true)); ?> />
                                            <?php _e('Tool Usage', 'memberpress-ai-assistant'); ?>
                                        </label><br>
                                        <label>
                                            <input type="checkbox" name="mpai_log_agent_activity" id="mpai_log_agent_activity" value="1" <?php checked(get_option('mpai_log_agent_activity', true)); ?> />
                                            <?php _e('Agent Activity', 'memberpress-ai-assistant'); ?>
                                        </label><br>
                                        <label>
                                            <input type="checkbox" name="mpai_log_timing" id="mpai_log_timing" value="1" <?php checked(get_option('mpai_log_timing', true)); ?> />
                                            <?php _e('Performance Timing', 'memberpress-ai-assistant'); ?>
                                        </label>
                                        <p class="description"><?php _e('Select which categories of events to log to the console.', 'memberpress-ai-assistant'); ?></p>
                                    </fieldset>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label><?php _e('Console Tester', 'memberpress-ai-assistant'); ?></label>
                                </th>
                                <td>
                                    <button type="button" id="mpai-test-console-logging" class="button"><?php _e('Test Console Logging', 'memberpress-ai-assistant'); ?></button>
                                    <span id="mpai-console-test-result" class="mpai-test-result" style="display: none;"></span>
                                    <p class="description"><?php _e('Click to test console logging with your current settings. Check your browser\'s developer console (F12) to see the logs.', 'memberpress-ai-assistant'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="mpai-diagnostic-section">
                        <h4><?php _e('Plugin Logs', 'memberpress-ai-assistant'); ?></h4>
                        <p><?php _e('Track and review plugin installation, activation, deactivation, and deletion events.', 'memberpress-ai-assistant'); ?></p>
                        
                        <div class="mpai-plugin-logs-controls">
                            <div class="mpai-plugin-logs-filters">
                                <select id="mpai-plugin-logs-action-filter">
                                    <option value=""><?php _e('All Actions', 'memberpress-ai-assistant'); ?></option>
                                    <option value="installed"><?php _e('Installed', 'memberpress-ai-assistant'); ?></option>
                                    <option value="updated"><?php _e('Updated', 'memberpress-ai-assistant'); ?></option>
                                    <option value="activated"><?php _e('Activated', 'memberpress-ai-assistant'); ?></option>
                                    <option value="deactivated"><?php _e('Deactivated', 'memberpress-ai-assistant'); ?></option>
                                    <option value="deleted"><?php _e('Deleted', 'memberpress-ai-assistant'); ?></option>
                                </select>
                                
                                <input type="text" id="mpai-plugin-logs-plugin-filter" placeholder="<?php _e('Filter by plugin name...', 'memberpress-ai-assistant'); ?>">
                                
                                <select id="mpai-plugin-logs-date-filter">
                                    <option value="7"><?php _e('Last 7 days', 'memberpress-ai-assistant'); ?></option>
                                    <option value="30" selected><?php _e('Last 30 days', 'memberpress-ai-assistant'); ?></option>
                                    <option value="90"><?php _e('Last 90 days', 'memberpress-ai-assistant'); ?></option>
                                    <option value="365"><?php _e('Last year', 'memberpress-ai-assistant'); ?></option>
                                    <option value="0"><?php _e('All time', 'memberpress-ai-assistant'); ?></option>
                                </select>
                                
                                <button type="button" id="mpai-plugin-logs-refresh" class="button"><?php _e('Refresh', 'memberpress-ai-assistant'); ?></button>
                            </div>
                            
                            <div class="mpai-plugin-logs-actions">
                                <button type="button" id="mpai-plugin-logs-export" class="button"><?php _e('Export CSV', 'memberpress-ai-assistant'); ?></button>
                                <label class="mpai-switch">
                                    <input type="checkbox" id="mpai-enable-plugin-logging" name="mpai_enable_plugin_logging" value="1" <?php checked(get_option('mpai_enable_plugin_logging', true)); ?>>
                                    <span class="mpai-slider"></span>
                                    <?php _e('Enable Logging', 'memberpress-ai-assistant'); ?>
                                </label>
                            </div>
                        </div>
                        
                        <div id="mpai-plugin-logs-container">
                            <div class="mpai-plugin-logs-summary">
                                <div class="mpai-summary-card">
                                    <h5><?php _e('Recent Activity', 'memberpress-ai-assistant'); ?></h5>
                                    <div class="mpai-summary-count" id="mpai-recent-activity-count">-</div>
                                    <div class="mpai-summary-label"><?php _e('events in selected period', 'memberpress-ai-assistant'); ?></div>
                                </div>
                                
                                <div class="mpai-summary-card">
                                    <h5><?php _e('Installations', 'memberpress-ai-assistant'); ?></h5>
                                    <div class="mpai-summary-count" id="mpai-installations-count">-</div>
                                </div>
                                
                                <div class="mpai-summary-card">
                                    <h5><?php _e('Updates', 'memberpress-ai-assistant'); ?></h5>
                                    <div class="mpai-summary-count" id="mpai-updates-count">-</div>
                                </div>
                                
                                <div class="mpai-summary-card">
                                    <h5><?php _e('Activations', 'memberpress-ai-assistant'); ?></h5>
                                    <div class="mpai-summary-count" id="mpai-activations-count">-</div>
                                </div>
                                
                                <div class="mpai-summary-card">
                                    <h5><?php _e('Deactivations', 'memberpress-ai-assistant'); ?></h5>
                                    <div class="mpai-summary-count" id="mpai-deactivations-count">-</div>
                                </div>
                            </div>
                            
                            <div class="mpai-plugin-logs-table-container">
                                <table class="widefat mpai-plugin-logs-table">
                                    <thead>
                                        <tr>
                                            <th class="mpai-col-date"><?php _e('Date & Time', 'memberpress-ai-assistant'); ?></th>
                                            <th class="mpai-col-action"><?php _e('Action', 'memberpress-ai-assistant'); ?></th>
                                            <th class="mpai-col-plugin"><?php _e('Plugin', 'memberpress-ai-assistant'); ?></th>
                                            <th class="mpai-col-version"><?php _e('Version', 'memberpress-ai-assistant'); ?></th>
                                            <th class="mpai-col-user"><?php _e('User', 'memberpress-ai-assistant'); ?></th>
                                            <th class="mpai-col-details"><?php _e('Details', 'memberpress-ai-assistant'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody id="mpai-plugin-logs-table-body">
                                        <tr>
                                            <td colspan="6" class="mpai-plugin-logs-loading"><?php _e('Loading plugin logs...', 'memberpress-ai-assistant'); ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                                
                                <div class="mpai-plugin-logs-pagination">
                                    <button type="button" id="mpai-plugin-logs-prev-page" class="button" disabled><?php _e('Previous', 'memberpress-ai-assistant'); ?></button>
                                    <span id="mpai-plugin-logs-page-info"><?php _e('Page 1', 'memberpress-ai-assistant'); ?></span>
                                    <button type="button" id="mpai-plugin-logs-next-page" class="button" disabled><?php _e('Next', 'memberpress-ai-assistant'); ?></button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php
                    // Check if any test files exist in the new location
                    $test_files_exist = false;
                    $test_files = array(
                        'debug-info.php' => __('Debug Info', 'memberpress-ai-assistant'),
                        'ajax-test.php' => __('AJAX Test', 'memberpress-ai-assistant'),
                        'openai-test.php' => __('OpenAI API Test', 'memberpress-ai-assistant'),
                        'memberpress-test.php' => __('MemberPress API Test', 'memberpress-ai-assistant'),
                        'anthropic-test.php' => __('Anthropic API Test', 'memberpress-ai-assistant'),
                        'test-validate-command.php' => __('Validate Command', 'memberpress-ai-assistant'),
                        'test-best-selling.php' => __('Best Selling Test', 'memberpress-ai-assistant'),
                        'test-plugin-logs.php' => __('Plugin Logs Test', 'memberpress-ai-assistant')
                    );
                    
                    $test_dir = plugin_dir_path(dirname(__FILE__)) . 'test/';
                    
                    // Check if any test files exist
                    foreach ($test_files as $file => $label) {
                        if (file_exists($test_dir . $file)) {
                            $test_files_exist = true;
                            break;
                        }
                    }
                    
                    // Only show the section if test files exist
                    if ($test_files_exist) :
                    ?>
                    <div class="mpai-diagnostic-section">
                        <h4><?php _e('Legacy Test Scripts', 'memberpress-ai-assistant'); ?></h4>
                        <p><?php _e('These test scripts provide additional diagnostic capabilities.', 'memberpress-ai-assistant'); ?></p>
                        <p>
                            <?php foreach ($test_files as $file => $label) : ?>
                                <?php if (file_exists($test_dir . $file)) : ?>
                                    <a href="<?php echo esc_url(plugin_dir_url(dirname(__FILE__)) . 'test/' . $file); ?>" class="button" target="_blank"><?php echo $label; ?></a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
                <script type="text/javascript">
                jQuery(document).ready(function($) {
                    // System Cache Test
                    $('#run-system-cache-test').on('click', function() {
                        var $resultContainer = $('#system-cache-result');
                        var $statusIndicator = $('#system-cache-status-indicator');
                        
                        // Show loading state
                        $resultContainer.html('<p>Running test...</p>');
                        $resultContainer.show();
                        
                        // Update status indicator
                        $statusIndicator.find('.mpai-status-dot')
                            .removeClass('mpai-status-unknown mpai-status-success mpai-status-error')
                            .addClass('mpai-status-unknown');
                        $statusIndicator.find('.mpai-status-text').text('Running...');
                        
                        // Create form data for request
                        var formData = new FormData();
                        formData.append('action', 'test_system_cache');
                        
                        // Use direct AJAX handler
                        var directHandlerUrl = '<?php echo plugin_dir_url(dirname(__FILE__)) . "includes/direct-ajax-handler.php"; ?>';
                        
                        // Make the request
                        fetch(directHandlerUrl, {
                            method: 'POST',
                            body: formData,
                            credentials: 'same-origin'
                        })
                        .then(function(response) {
                            return response.json();
                        })
                        .then(function(data) {
                            if (data.success) {
                                // Update status indicator for success
                                $statusIndicator.find('.mpai-status-dot')
                                    .removeClass('mpai-status-unknown mpai-status-error')
                                    .addClass('mpai-status-success');
                                $statusIndicator.find('.mpai-status-text').text('Success');
                                
                                // Format and display the result
                                var formattedResult = formatSystemCacheResult(data.data);
                                $resultContainer.html(formattedResult);
                            } else {
                                // Update status indicator for failure
                                $statusIndicator.find('.mpai-status-dot')
                                    .removeClass('mpai-status-unknown mpai-status-success')
                                    .addClass('mpai-status-error');
                                $statusIndicator.find('.mpai-status-text').text('Failed');
                                
                                // Display error message
                                var errorMessage = data.message || 'Unknown error occurred';
                                var formattedError = '<div class="mpai-system-test-result mpai-test-error">';
                                formattedError += '<h4>Test Failed</h4>';
                                formattedError += '<p>' + errorMessage + '</p>';
                                formattedError += '</div>';
                                
                                $resultContainer.html(formattedError);
                            }
                        })
                        .catch(function(error) {
                            // Update status indicator for error
                            $statusIndicator.find('.mpai-status-dot')
                                .removeClass('mpai-status-unknown mpai-status-success')
                                .addClass('mpai-status-error');
                            $statusIndicator.find('.mpai-status-text').text('Error');
                            
                            // Display error message
                            var formattedError = '<div class="mpai-system-test-result mpai-test-error">';
                            formattedError += '<h4>Test Error</h4>';
                            formattedError += '<p>Error executing test: ' + error.message + '</p>';
                            formattedError += '</div>';
                            
                            $resultContainer.html(formattedError);
                        });
                    });
                    
                    // Console Logging Test
                    $('#mpai-test-console-logging').on('click', function() {
                        var $resultSpan = $('#mpai-console-test-result');
                        $resultSpan.text('Testing console logging...');
                        $resultSpan.show();
                        
                        // Check if mpaiLogger exists
                        if (window.mpaiLogger) {
                            try {
                                // Test each logging level
                                window.mpaiLogger.info('🚀 Console Logging Test: Info message', 'ui');
                                window.mpaiLogger.warn('🚀 Console Logging Test: Warning message', 'ui');
                                window.mpaiLogger.error('🚀 Console Logging Test: Error message', 'ui');
                                window.mpaiLogger.debug('🚀 Console Logging Test: Debug message', 'ui');
                                
                                $resultSpan.html('<span style="color: green;">✓ Test messages sent to console</span>');
                                
                                setTimeout(function() {
                                    $resultSpan.fadeOut();
                                }, 3000);
                            } catch (e) {
                                $resultSpan.html('<span style="color: red;">✗ Error: ' + e.message + '</span>');
                            }
                        } else {
                            $resultSpan.html('<span style="color: red;">✗ mpaiLogger not found. Logger may not be initialized.</span>');
                        }
                    });
                    
                    // Format system cache test results
                    function formatSystemCacheResult(data) {
                        var output = '<div class="mpai-system-test-result mpai-test-success">';
                        output += '<h4>System Information Cache Test Results</h4>';
                        
                        if (data.success) {
                            output += '<p class="mpai-test-success-message">' + data.message + '</p>';
                            
                            // Add test details
                            if (data.tests) {
                                output += '<h5>Test Details:</h5>';
                                output += '<table class="mpai-test-results-table">';
                                output += '<tr><th>Test</th><th>Result</th><th>Timing</th></tr>';
                                
                                data.tests.forEach(function(test) {
                                    var resultClass = test.success ? 'mpai-test-success' : 'mpai-test-error';
                                    var resultText = test.success ? 'PASSED' : 'FAILED';
                                    
                                    output += '<tr>';
                                    output += '<td>' + test.name + '</td>';
                                    output += '<td class="' + resultClass + '">' + resultText + '</td>';
                                    
                                    // Format timing information
                                    var timing = '';
                                    if (typeof test.timing === 'object') {
                                        timing = 'First Request: ' + test.timing.first_request + '<br>';
                                        timing += 'Second Request: ' + test.timing.second_request + '<br>';
                                        timing += 'Improvement: ' + test.timing.improvement;
                                    } else {
                                        timing = test.timing;
                                    }
                                    
                                    output += '<td>' + timing + '</td>';
                                    output += '</tr>';
                                });
                                
                                output += '</table>';
                            }
                            
                            // Add cache hits
                            if (data.cache_hits) {
                                output += '<p>Cache Hits: ' + data.cache_hits + '</p>';
                            }
                        } else {
                            output += '<p class="mpai-test-error-message">' + data.message + '</p>';
                        }
                        
                        output += '</div>';
                        return output;
                    }

                    // Plugin Logs Functionality
                    let pluginLogsPage = 1;
                    let pluginLogsTotalPages = 1;
                    let pluginLogsPerPage = 10;
                    
                    // Function to load plugin logs
                    function loadPluginLogs() {
                        $('#mpai-plugin-logs-table-body').html('<tr><td colspan="6" class="mpai-plugin-logs-loading"><?php _e('Loading plugin logs...', 'memberpress-ai-assistant'); ?></td></tr>');
                        
                        const action = $('#mpai-plugin-logs-action-filter').val();
                        const pluginName = $('#mpai-plugin-logs-plugin-filter').val();
                        const days = $('#mpai-plugin-logs-date-filter').val();
                        
                        // Prepare data for AJAX request
                        const data = {
                            action: 'mpai_get_plugin_logs',
                            nonce: mpai_data.nonce,
                            log_action: action,
                            plugin_name: pluginName,
                            days: days,
                            page: pluginLogsPage,
                            per_page: pluginLogsPerPage
                        };
                        
                        // Make the AJAX request
                        $.ajax({
                            url: mpai_data.ajax_url,
                            type: 'POST',
                            data: data,
                            success: function(response) {
                                if (response.success) {
                                    updateSummaryCounts(response.data.summary);
                                    updatePagination(response.data.total);
                                    
                                    if (response.data.logs.length > 0) {
                                        let html = '';
                                        $.each(response.data.logs, function(index, log) {
                                            html += buildLogRow(log);
                                        });
                                        $('#mpai-plugin-logs-table-body').html(html);
                                        initDetailsButtons();
                                    } else {
                                        $('#mpai-plugin-logs-table-body').html('<tr><td colspan="6" class="mpai-plugin-logs-empty"><?php _e('No logs found matching your criteria.', 'memberpress-ai-assistant'); ?></td></tr>');
                                    }
                                } else {
                                    $('#mpai-plugin-logs-table-body').html('<tr><td colspan="6" class="mpai-plugin-logs-empty"><?php _e('Error loading logs:', 'memberpress-ai-assistant'); ?> ' + response.data + '</td></tr>');
                                }
                            },
                            error: function() {
                                $('#mpai-plugin-logs-table-body').html('<tr><td colspan="6" class="mpai-plugin-logs-empty"><?php _e('Error loading logs. Please try again.', 'memberpress-ai-assistant'); ?></td></tr>');
                            }
                        });
                    }
                    
                    // Function to update summary counts
                    function updateSummaryCounts(summary) {
                        $('#mpai-recent-activity-count').text(summary.total || 0);
                        $('#mpai-installations-count').text(summary.installed || 0);
                        $('#mpai-updates-count').text(summary.updated || 0);
                        $('#mpai-activations-count').text(summary.activated || 0);
                        $('#mpai-deactivations-count').text(summary.deactivated || 0);
                    }
                    
                    // Function to update pagination
                    function updatePagination(total) {
                        pluginLogsTotalPages = Math.ceil(total / pluginLogsPerPage);
                        
                        // Update page info text
                        $('#mpai-plugin-logs-page-info').text('<?php _e('Page', 'memberpress-ai-assistant'); ?> ' + pluginLogsPage + ' <?php _e('of', 'memberpress-ai-assistant'); ?> ' + pluginLogsTotalPages);
                        
                        // Enable/disable pagination buttons
                        $('#mpai-plugin-logs-prev-page').prop('disabled', pluginLogsPage <= 1);
                        $('#mpai-plugin-logs-next-page').prop('disabled', pluginLogsPage >= pluginLogsTotalPages);
                    }
                    
                    // Function to build log row HTML
                    function buildLogRow(log) {
                        const date = new Date(log.date_time);
                        const formattedDate = date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
                        
                        let actionClass = 'mpai-action-' + log.action;
                        let actionText = log.action.charAt(0).toUpperCase() + log.action.slice(1);
                        
                        let row = '<tr data-log-id="' + log.id + '">';
                        row += '<td>' + formattedDate + '</td>';
                        row += '<td><span class="mpai-action-badge ' + actionClass + '">' + actionText + '</span></td>';
                        row += '<td>' + log.plugin_name + '</td>';
                        row += '<td>' + log.plugin_version + '</td>';
                        row += '<td>' + (log.user_info ? log.user_info.display_name : log.user_login) + '</td>';
                        row += '<td><button type="button" class="mpai-details-button" data-log-id="' + log.id + '"><?php _e('View Details', 'memberpress-ai-assistant'); ?></button></td>';
                        row += '</tr>';
                        
                        return row;
                    }
                    
                    // Function to initialize details buttons
                    function initDetailsButtons() {
                        $('.mpai-details-button').on('click', function() {
                            const logId = $(this).data('log-id');
                            
                            // Prepare data for AJAX request
                            const data = {
                                action: 'mpai_get_plugin_log_details',
                                nonce: mpai_data.nonce,
                                log_id: logId
                            };
                            
                            // Make the AJAX request
                            $.ajax({
                                url: mpai_data.ajax_url,
                                type: 'POST',
                                data: data,
                                success: function(response) {
                                    if (response.success) {
                                        showDetailsPopup(response.data);
                                    } else {
                                        alert('Error: ' + response.data);
                                    }
                                },
                                error: function() {
                                    alert('Error: Failed to fetch log details. Please try again.');
                                }
                            });
                        });
                    }
                    
                    // Function to show details popup
                    function showDetailsPopup(log) {
                        const date = new Date(log.date_time);
                        const formattedDate = date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
                        
                        let actionClass = 'mpai-action-' + log.action;
                        let actionText = log.action.charAt(0).toUpperCase() + log.action.slice(1);
                        
                        // Create popup content
                        let popupContent = '<div class="mpai-details-popup">';
                        popupContent += '<div class="mpai-details-popup-content">';
                        popupContent += '<div class="mpai-details-popup-header">';
                        popupContent += '<h3>' + log.plugin_name + ' <span class="mpai-action-badge ' + actionClass + '">' + actionText + '</span></h3>';
                        popupContent += '<button type="button" class="mpai-details-popup-close">&times;</button>';
                        popupContent += '</div>';
                        popupContent += '<div class="mpai-details-popup-body">';
                        
                        // Basic info table
                        popupContent += '<table class="mpai-details-table">';
                        popupContent += '<tr><th><?php _e('Date & Time', 'memberpress-ai-assistant'); ?></th><td>' + formattedDate + '</td></tr>';
                        popupContent += '<tr><th><?php _e('Plugin', 'memberpress-ai-assistant'); ?></th><td>' + log.plugin_name + '</td></tr>';
                        popupContent += '<tr><th><?php _e('Slug', 'memberpress-ai-assistant'); ?></th><td>' + log.plugin_slug + '</td></tr>';
                        popupContent += '<tr><th><?php _e('Version', 'memberpress-ai-assistant'); ?></th><td>' + log.plugin_version + '</td></tr>';
                        
                        if (log.plugin_prev_version && log.action === 'updated') {
                            popupContent += '<tr><th><?php _e('Previous Version', 'memberpress-ai-assistant'); ?></th><td>' + log.plugin_prev_version + '</td></tr>';
                        }
                        
                        popupContent += '<tr><th><?php _e('User', 'memberpress-ai-assistant'); ?></th><td>' + (log.user_info ? log.user_info.display_name + ' (' + log.user_info.user_login + ')' : log.user_login) + '</td></tr>';
                        popupContent += '</table>';
                        
                        // Additional data if available
                        if (log.additional_data) {
                            popupContent += '<h4><?php _e('Additional Information', 'memberpress-ai-assistant'); ?></h4>';
                            popupContent += '<table class="mpai-details-table">';
                            
                            for (const [key, value] of Object.entries(log.additional_data)) {
                                if (value) {
                                    const label = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                                    popupContent += '<tr><th>' + label + '</th><td>' + value + '</td></tr>';
                                }
                            }
                            
                            popupContent += '</table>';
                        }
                        
                        popupContent += '</div>'; // End popup body
                        popupContent += '</div>'; // End popup content
                        popupContent += '</div>'; // End popup
                        
                        // Add popup to the page
                        $('body').append(popupContent);
                        
                        // Handle close button
                        $('.mpai-details-popup-close').on('click', function() {
                            $('.mpai-details-popup').remove();
                        });
                        
                        // Close when clicking outside
                        $('.mpai-details-popup').on('click', function(e) {
                            if ($(e.target).hasClass('mpai-details-popup')) {
                                $('.mpai-details-popup').remove();
                            }
                        });
                    }
                    
                    // Function to export logs to CSV
                    function exportLogsToCSV() {
                        const action = $('#mpai-plugin-logs-action-filter').val();
                        const pluginName = $('#mpai-plugin-logs-plugin-filter').val();
                        const days = $('#mpai-plugin-logs-date-filter').val();
                        
                        // Prepare data for AJAX request
                        const data = {
                            action: 'mpai_export_plugin_logs',
                            nonce: mpai_data.nonce,
                            log_action: action,
                            plugin_name: pluginName,
                            days: days
                        };
                        
                        // Make the AJAX request
                        $.ajax({
                            url: mpai_data.ajax_url,
                            type: 'POST',
                            data: data,
                            success: function(response) {
                                if (response.success) {
                                    // Create file download
                                    const blob = new Blob([response.data], { type: 'text/csv' });
                                    const url = window.URL.createObjectURL(blob);
                                    const a = document.createElement('a');
                                    a.style.display = 'none';
                                    a.href = url;
                                    a.download = 'plugin-logs-export.csv';
                                    document.body.appendChild(a);
                                    a.click();
                                    window.URL.revokeObjectURL(url);
                                } else {
                                    alert('Error: ' + response.data);
                                }
                            },
                            error: function() {
                                alert('Error: Failed to export plugin logs. Please try again.');
                            }
                        });
                    }
                    
                    // Event Listeners for Plugin Logs
                    $('#mpai-plugin-logs-refresh').on('click', function() {
                        pluginLogsPage = 1;
                        loadPluginLogs();
                    });
                    
                    $('#mpai-plugin-logs-prev-page').on('click', function() {
                        if (pluginLogsPage > 1) {
                            pluginLogsPage--;
                            loadPluginLogs();
                        }
                    });
                    
                    $('#mpai-plugin-logs-next-page').on('click', function() {
                        if (pluginLogsPage < pluginLogsTotalPages) {
                            pluginLogsPage++;
                            loadPluginLogs();
                        }
                    });
                    
                    $('#mpai-plugin-logs-export').on('click', function() {
                        exportLogsToCSV();
                    });
                    
                    $('#mpai-enable-plugin-logging').on('change', function() {
                        const enabled = $(this).is(':checked');
                        
                        // Save the setting
                        const data = {
                            action: 'mpai_update_plugin_logging_setting',
                            nonce: mpai_data.nonce,
                            enabled: enabled ? 1 : 0
                        };
                        
                        $.ajax({
                            url: mpai_data.ajax_url,
                            type: 'POST',
                            data: data,
                            success: function(response) {
                                if (!response.success) {
                                    alert('Error: ' + response.data);
                                    // Revert the checkbox state if failed
                                    $('#mpai-enable-plugin-logging').prop('checked', !enabled);
                                }
                            },
                            error: function() {
                                alert('Error: Failed to update setting. Please try again.');
                                // Revert the checkbox state
                                $('#mpai-enable-plugin-logging').prop('checked', !enabled);
                            }
                        });
                    });
                    
                    // Load Plugin Logs when the page is loaded
                    loadPluginLogs();
                });
                </script>
                <?php
            }
            ?>
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
            if (typeof mpai_data !== 'undefined') {
                console.log('MPAI: Testing MemberPress API with nonce:', mpai_data.nonce ? mpai_data.nonce.substring(0, 5) + '...' : 'undefined');
            } else {
                console.error('MPAI: mpai_data is not available for MemberPress API test');
            }
            console.log('MPAI: AJAX URL:', typeof ajaxurl !== 'undefined' ? ajaxurl : 'ajaxurl not defined');
            
            // Try the direct AJAX handler instead of admin-ajax.php
            var directHandlerUrl = '<?php echo plugin_dir_url(dirname(__FILE__)) . 'includes/direct-ajax-handler.php'; ?>';
            
            // Create the form data object directly to ensure proper formatting
            var formData = new FormData();
            formData.append('action', 'test_memberpress');
            formData.append('nonce', typeof mpai_data !== 'undefined' ? mpai_data.nonce : '');
            formData.append('api_key', apiKey);
            
            // Log what we're sending for debugging
            console.log('MPAI: FormData prepared with direct AJAX handler');
            if (typeof mpai_data !== 'undefined' && mpai_data.nonce) {
                console.log('MPAI: Nonce length:', mpai_data.nonce.length);
            }
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
        
        // Test Anthropic API Connection
        $('#mpai-test-anthropic-api').on('click', function() {
            var apiKey = $('#mpai_anthropic_api_key').val();
            var $resultContainer = $('#mpai-anthropic-test-result');
            
            // Use the globally localized nonce
            console.log('Test Anthropic API clicked with localized nonce');
            
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
            console.log('MPAI: Testing Anthropic API with nonce:', mpai_data.nonce ? mpai_data.nonce.substring(0, 5) + '...' : 'undefined');
            
            // Try the direct AJAX handler instead of admin-ajax.php
            var directHandlerUrl = '<?php echo plugin_dir_url(dirname(__FILE__)) . 'includes/direct-ajax-handler.php'; ?>';
            
            // Create the form data object directly to ensure proper formatting
            var formData = new FormData();
            formData.append('action', 'test_anthropic');
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
                console.log('MPAI: Anthropic API test response:', data);
                if (data.success) {
                    $resultContainer.html(data.data);
                    $resultContainer.addClass('mpai-test-success').removeClass('mpai-test-loading mpai-test-error');
                } else {
                    $resultContainer.html(data.data);
                    $resultContainer.addClass('mpai-test-error').removeClass('mpai-test-loading mpai-test-success');
                }
                $('#mpai-test-anthropic-api').prop('disabled', false);
            })
            .catch(function(error) {
                console.error('MPAI: Fetch error:', error);
                $resultContainer.html('Error: ' + error.message);
                $resultContainer.addClass('mpai-test-error').removeClass('mpai-test-loading mpai-test-success');
                $('#mpai-test-anthropic-api').prop('disabled', false);
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

/* AI Tools Tab Styles */
.mpai-tools-section {
    margin-bottom: 25px;
}

.mpai-tool-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 15px;
    margin-bottom: 15px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}

.mpai-tool-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.mpai-tool-header h4 {
    margin: 0;
    font-size: 16px;
}

.mpai-tool-details {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #eee;
}

.mpai-tool-details code {
    background: #f5f5f5;
    padding: 3px 5px;
    border-radius: 3px;
    font-size: 12px;
}

/* Toggle Switch Styles */
.mpai-toggle {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 24px;
}

.mpai-toggle input {
    opacity: 0;
    width: 0;
    height: 0;
}

.mpai-toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 24px;
}

.mpai-toggle-slider:before {
    position: absolute;
    content: "";
    height: 16px;
    width: 16px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

input:checked + .mpai-toggle-slider {
    background-color: #2196F3;
}

input:focus + .mpai-toggle-slider {
    box-shadow: 0 0 1px #2196F3;
}

input:checked + .mpai-toggle-slider:before {
    transform: translateX(26px);
}

@media (max-width: 782px) {
    .mpai-tool-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .mpai-toggle {
        margin-top: 10px;
    }
}
</style>