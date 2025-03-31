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
            // Include the diagnostics tab
            require_once MPAI_PLUGIN_DIR . 'includes/settings-diagnostic.php';
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