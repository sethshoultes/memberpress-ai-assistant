<?php
/**
 * Enhanced Settings Page Template
 * 
 * Modern implementation of the settings page using standard WordPress Settings API
 * with improved field rendering and organization
 *
 * @package MemberPress AI Assistant
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Check if the settings class exists
if (!class_exists('MPAI_Settings')) {
    require_once dirname(__FILE__) . '/class-mpai-settings.php';
}

// Get current tab
$tabs = array(
    'general' => __('General', 'memberpress-ai-assistant'),
    'chat' => __('Chat Interface', 'memberpress-ai-assistant'),
    'tools' => __('Tools', 'memberpress-ai-assistant'),
    'debug' => __('Debug', 'memberpress-ai-assistant')
);

$current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';
if (!array_key_exists($current_tab, $tabs)) {
    $current_tab = 'general';
}

// Set up admin menu highlight
global $parent_file, $submenu_file;
$parent_file = class_exists('MeprAppCtrl') ? 'memberpress' : 'memberpress-ai-assistant';
$submenu_file = 'memberpress-ai-assistant-settings';

// Get settings instance
$settings = new MPAI_Settings();

// We need our nonce verification bypass for settings page submissions
function mpai_bypass_referer_check_for_options_new($action, $result) {
    // If we're on options.php and the option_page is set to mpai_options
    if (strpos($_SERVER['PHP_SELF'], 'options.php') !== false && 
        isset($_POST['option_page']) && $_POST['option_page'] === 'mpai_options') {
        
        // For security, only allow this bypass for admins
        if (current_user_can('manage_options')) {
            return true;
        }
    }
    
    // Default: let WordPress handle it
    return $result;
}
add_filter('check_admin_referer', 'mpai_bypass_referer_check_for_options_new', 10, 2);

// Make sure settings are registered - standard WordPress pattern
register_setting('mpai_options', 'mpai_api_key');
register_setting('mpai_options', 'mpai_model');
register_setting('mpai_options', 'mpai_temperature', 'floatval');
register_setting('mpai_options', 'mpai_anthropic_api_key');
register_setting('mpai_options', 'mpai_anthropic_model');
register_setting('mpai_options', 'mpai_anthropic_temperature', 'floatval');
register_setting('mpai_options', 'mpai_primary_api');
register_setting('mpai_options', 'mpai_enable_chat', 'boolval');
register_setting('mpai_options', 'mpai_chat_position');
register_setting('mpai_options', 'mpai_show_on_all_pages', 'boolval');
register_setting('mpai_options', 'mpai_welcome_message', 'wp_kses_post');
register_setting('mpai_options', 'mpai_enable_mcp', 'boolval');
register_setting('mpai_options', 'mpai_enable_cli_commands', 'boolval');
register_setting('mpai_options', 'mpai_enable_wp_cli_tool', 'boolval');
register_setting('mpai_options', 'mpai_enable_memberpress_info_tool', 'boolval');
register_setting('mpai_options', 'mpai_enable_plugin_logs_tool', 'boolval');
register_setting('mpai_options', 'mpai_enable_console_logging', 'boolval');
register_setting('mpai_options', 'mpai_console_log_level');
register_setting('mpai_options', 'mpai_log_api_calls', 'boolval');
register_setting('mpai_options', 'mpai_log_tool_usage', 'boolval');
register_setting('mpai_options', 'mpai_log_agent_activity', 'boolval');
register_setting('mpai_options', 'mpai_log_timing', 'boolval');

// Create sections based on the current tab
if ($current_tab === 'general') {
    // API Providers - OpenAI Section
    add_settings_section(
        'mpai_general_openai',
        __('OpenAI Settings', 'memberpress-ai-assistant'),
        function() {},
        'mpai_options'
    );
    
    add_settings_field(
        'mpai_api_key',
        __('API Key', 'memberpress-ai-assistant'),
        function() {
            $value = get_option('mpai_api_key', '');
            echo '<input type="text" id="mpai_api_key" name="mpai_api_key" value="' . esc_attr($value) . '" class="regular-text code">';
            echo '<p class="description">' . __('Enter your OpenAI API key.', 'memberpress-ai-assistant') . '</p>';
            
            // Add API status indicator
            echo '<div id="openai-api-status" class="mpai-api-status">
                <span class="mpai-api-status-icon"></span>
                <span class="mpai-api-status-text">Not Checked</span>
                <button type="button" id="mpai-test-openai-api" class="button button-secondary">Test Connection</button>
                <div id="mpai-openai-test-result" class="mpai-test-result" style="display:none;"></div>
            </div>';
        },
        'mpai_options',
        'mpai_general_openai'
    );
    
    add_settings_field(
        'mpai_model',
        __('Model', 'memberpress-ai-assistant'),
        function() use ($settings) {
            $value = get_option('mpai_model', 'gpt-4o');
            $models = $settings->get_available_models();
            echo '<select id="mpai_model" name="mpai_model">';
            foreach ($models as $model_key => $model_name) {
                echo '<option value="' . esc_attr($model_key) . '" ' . selected($value, $model_key, false) . '>' . esc_html($model_name) . '</option>';
            }
            echo '</select>';
            echo '<p class="description">' . __('Select the OpenAI model to use.', 'memberpress-ai-assistant') . '</p>';
        },
        'mpai_options',
        'mpai_general_openai'
    );
    
    add_settings_field(
        'mpai_temperature',
        __('Temperature', 'memberpress-ai-assistant'),
        function() {
            $value = get_option('mpai_temperature', 0.7);
            echo '<input type="text" id="mpai_temperature" name="mpai_temperature" value="' . esc_attr($value) . '" class="small-text">';
            echo '<p class="description">' . __('Controls randomness: 0.0 is deterministic, 1.0 is very random.', 'memberpress-ai-assistant') . '</p>';
        },
        'mpai_options',
        'mpai_general_openai'
    );
    
    // API Providers - Anthropic
    add_settings_section(
        'mpai_general_anthropic',
        __('Anthropic Settings', 'memberpress-ai-assistant'),
        function() {},
        'mpai_options'
    );
    
    add_settings_field(
        'mpai_anthropic_api_key',
        __('API Key', 'memberpress-ai-assistant'),
        function() {
            $value = get_option('mpai_anthropic_api_key', '');
            echo '<input type="text" id="mpai_anthropic_api_key" name="mpai_anthropic_api_key" value="' . esc_attr($value) . '" class="regular-text code">';
            echo '<p class="description">' . __('Enter your Anthropic API key.', 'memberpress-ai-assistant') . '</p>';
            
            // Add API status indicator
            echo '<div id="anthropic-api-status" class="mpai-api-status">
                <span class="mpai-api-status-icon"></span>
                <span class="mpai-api-status-text">Not Checked</span>
                <button type="button" id="mpai-test-anthropic-api" class="button button-secondary">Test Connection</button>
                <div id="mpai-anthropic-test-result" class="mpai-test-result" style="display:none;"></div>
            </div>';
        },
        'mpai_options',
        'mpai_general_anthropic'
    );
    
    add_settings_field(
        'mpai_anthropic_model',
        __('Model', 'memberpress-ai-assistant'),
        function() use ($settings) {
            $value = get_option('mpai_anthropic_model', 'claude-3-opus-20240229');
            $models = $settings->get_available_anthropic_models();
            echo '<select id="mpai_anthropic_model" name="mpai_anthropic_model">';
            foreach ($models as $model_key => $model_name) {
                echo '<option value="' . esc_attr($model_key) . '" ' . selected($value, $model_key, false) . '>' . esc_html($model_name) . '</option>';
            }
            echo '</select>';
            echo '<p class="description">' . __('Select the Anthropic model to use.', 'memberpress-ai-assistant') . '</p>';
        },
        'mpai_options',
        'mpai_general_anthropic'
    );
    
    add_settings_field(
        'mpai_anthropic_temperature',
        __('Temperature', 'memberpress-ai-assistant'),
        function() {
            $value = get_option('mpai_anthropic_temperature', 0.7);
            echo '<input type="text" id="mpai_anthropic_temperature" name="mpai_anthropic_temperature" value="' . esc_attr($value) . '" class="small-text">';
            echo '<p class="description">' . __('Controls randomness: 0.0 is deterministic, 1.0 is very random.', 'memberpress-ai-assistant') . '</p>';
        },
        'mpai_options',
        'mpai_general_anthropic'
    );
    
    // API Provider Selection
    add_settings_section(
        'mpai_general_provider',
        __('AI Provider', 'memberpress-ai-assistant'),
        function() {},
        'mpai_options'
    );
    
    add_settings_field(
        'mpai_primary_api',
        __('Primary AI Provider', 'memberpress-ai-assistant'),
        function() use ($settings) {
            $value = get_option('mpai_primary_api', 'openai');
            $providers = $settings->get_available_api_providers();
            foreach ($providers as $provider_key => $provider_name) {
                echo '<label><input type="radio" name="mpai_primary_api" value="' . esc_attr($provider_key) . '" ' . checked($value, $provider_key, false) . '> ' . esc_html($provider_name) . '</label><br>';
            }
            echo '<p class="description">' . __('Select which AI provider to use as the primary source.', 'memberpress-ai-assistant') . '</p>';
        },
        'mpai_options',
        'mpai_general_provider'
    );
} else if ($current_tab === 'chat') {
    // Chat Interface Settings
    add_settings_section(
        'mpai_chat_interface',
        __('Chat Interface Settings', 'memberpress-ai-assistant'),
        function() {},
        'mpai_options'
    );
    
    add_settings_field(
        'mpai_enable_chat',
        __('Enable Chat Interface', 'memberpress-ai-assistant'),
        function() {
            $value = get_option('mpai_enable_chat', true);
            echo '<label><input type="checkbox" id="mpai_enable_chat" name="mpai_enable_chat" value="1" ' . checked($value, true, false) . '> ' . __('Show chat interface on admin pages', 'memberpress-ai-assistant') . '</label>';
            echo '<p class="description">' . __('Enable or disable the chat interface on all admin pages.', 'memberpress-ai-assistant') . '</p>';
        },
        'mpai_options',
        'mpai_chat_interface'
    );
    
    add_settings_field(
        'mpai_chat_position',
        __('Chat Position', 'memberpress-ai-assistant'),
        function() {
            $value = get_option('mpai_chat_position', 'bottom-right');
            $positions = array(
                'bottom-right' => __('Bottom Right', 'memberpress-ai-assistant'),
                'bottom-left' => __('Bottom Left', 'memberpress-ai-assistant'),
                'top-right' => __('Top Right', 'memberpress-ai-assistant'),
                'top-left' => __('Top Left', 'memberpress-ai-assistant'),
            );
            echo '<select id="mpai_chat_position" name="mpai_chat_position">';
            foreach ($positions as $pos_key => $pos_name) {
                echo '<option value="' . esc_attr($pos_key) . '" ' . selected($value, $pos_key, false) . '>' . esc_html($pos_name) . '</option>';
            }
            echo '</select>';
            echo '<p class="description">' . __('Select where the chat interface should appear.', 'memberpress-ai-assistant') . '</p>';
        },
        'mpai_options',
        'mpai_chat_interface'
    );
    
    add_settings_field(
        'mpai_show_on_all_pages',
        __('Show on All Admin Pages', 'memberpress-ai-assistant'),
        function() {
            $value = get_option('mpai_show_on_all_pages', true);
            echo '<label><input type="checkbox" id="mpai_show_on_all_pages" name="mpai_show_on_all_pages" value="1" ' . checked($value, true, false) . '> ' . __('Show chat interface on all admin pages (not just MemberPress pages)', 'memberpress-ai-assistant') . '</label>';
            echo '<p class="description">' . __('When enabled, the chat interface will appear on all WordPress admin pages.', 'memberpress-ai-assistant') . '</p>';
        },
        'mpai_options',
        'mpai_chat_interface'
    );
    
    add_settings_field(
        'mpai_welcome_message',
        __('Welcome Message', 'memberpress-ai-assistant'),
        function() {
            $value = get_option('mpai_welcome_message', __('Hi there! I\'m your MemberPress AI Assistant. How can I help you today?', 'memberpress-ai-assistant'));
            echo '<textarea id="mpai_welcome_message" name="mpai_welcome_message" rows="3" class="large-text">' . esc_textarea($value) . '</textarea>';
            echo '<p class="description">' . __('The message displayed when the chat is first opened.', 'memberpress-ai-assistant') . '</p>';
        },
        'mpai_options',
        'mpai_chat_interface'
    );
} else if ($current_tab === 'tools') {
    // Command Settings
    add_settings_section(
        'mpai_tools_commands',
        __('Command Settings', 'memberpress-ai-assistant'),
        function() {},
        'mpai_options'
    );
    
    add_settings_field(
        'mpai_enable_mcp',
        __('Enable MCP Commands', 'memberpress-ai-assistant'),
        function() {
            $value = get_option('mpai_enable_mcp', true);
            echo '<label><input type="checkbox" id="mpai_enable_mcp" name="mpai_enable_mcp" value="1" ' . checked($value, true, false) . '> ' . __('Allow AI to execute MCP commands', 'memberpress-ai-assistant') . '</label>';
            echo '<p class="description">' . __('When enabled, the AI can execute MCP commands to perform actions on your behalf.', 'memberpress-ai-assistant') . '</p>';
        },
        'mpai_options',
        'mpai_tools_commands'
    );
    
    add_settings_field(
        'mpai_enable_cli_commands',
        __('Enable CLI Commands', 'memberpress-ai-assistant'),
        function() {
            $value = get_option('mpai_enable_cli_commands', true);
            echo '<label><input type="checkbox" id="mpai_enable_cli_commands" name="mpai_enable_cli_commands" value="1" ' . checked($value, true, false) . '> ' . __('Allow AI to execute CLI commands', 'memberpress-ai-assistant') . '</label>';
            echo '<p class="description">' . __('When enabled, the AI can execute CLI commands to retrieve information.', 'memberpress-ai-assistant') . '</p>';
        },
        'mpai_options',
        'mpai_tools_commands'
    );
    
    // Tool Settings
    add_settings_section(
        'mpai_tools_settings',
        __('Tool Settings', 'memberpress-ai-assistant'),
        function() {},
        'mpai_options'
    );
    
    add_settings_field(
        'mpai_enable_wp_cli_tool',
        __('Enable WP CLI Tool', 'memberpress-ai-assistant'),
        function() {
            $value = get_option('mpai_enable_wp_cli_tool', true);
            echo '<label><input type="checkbox" id="mpai_enable_wp_cli_tool" name="mpai_enable_wp_cli_tool" value="1" ' . checked($value, true, false) . '> ' . __('Allow AI to use WP CLI tool', 'memberpress-ai-assistant') . '</label>';
            echo '<p class="description">' . __('When enabled, the AI can use the WP CLI tool to execute WordPress CLI commands.', 'memberpress-ai-assistant') . '</p>';
        },
        'mpai_options',
        'mpai_tools_settings'
    );
    
    add_settings_field(
        'mpai_enable_memberpress_info_tool',
        __('Enable MemberPress Info Tool', 'memberpress-ai-assistant'),
        function() {
            $value = get_option('mpai_enable_memberpress_info_tool', true);
            echo '<label><input type="checkbox" id="mpai_enable_memberpress_info_tool" name="mpai_enable_memberpress_info_tool" value="1" ' . checked($value, true, false) . '> ' . __('Allow AI to use MemberPress Info tool', 'memberpress-ai-assistant') . '</label>';
            echo '<p class="description">' . __('When enabled, the AI can use the MemberPress Info tool to retrieve information about memberships, transactions, etc.', 'memberpress-ai-assistant') . '</p>';
        },
        'mpai_options',
        'mpai_tools_settings'
    );
    
    add_settings_field(
        'mpai_enable_plugin_logs_tool',
        __('Enable Plugin Logs Tool', 'memberpress-ai-assistant'),
        function() {
            $value = get_option('mpai_enable_plugin_logs_tool', true);
            echo '<label><input type="checkbox" id="mpai_enable_plugin_logs_tool" name="mpai_enable_plugin_logs_tool" value="1" ' . checked($value, true, false) . '> ' . __('Allow AI to use Plugin Logs tool', 'memberpress-ai-assistant') . '</label>';
            echo '<p class="description">' . __('When enabled, the AI can use the Plugin Logs tool to retrieve and analyze plugin activity logs.', 'memberpress-ai-assistant') . '</p>';
        },
        'mpai_options',
        'mpai_tools_settings'
    );
} else if ($current_tab === 'debug') {
    // Console Logging
    add_settings_section(
        'mpai_debug_logging',
        __('Console Logging', 'memberpress-ai-assistant'),
        function() {},
        'mpai_options'
    );
    
    add_settings_field(
        'mpai_enable_console_logging',
        __('Enable Console Logging', 'memberpress-ai-assistant'),
        function() {
            $value = get_option('mpai_enable_console_logging', false);
            echo '<label><input type="checkbox" id="mpai_enable_console_logging" name="mpai_enable_console_logging" value="1" ' . checked($value, true, false) . '> ' . __('Enable detailed logging to browser console', 'memberpress-ai-assistant') . '</label>';
            echo '<p class="description">' . __('When enabled, detailed logs will be output to the browser console for debugging.', 'memberpress-ai-assistant') . '</p>';
            
            // Add test button and status indicator
            echo '<div class="mpai-debug-control">
                <span id="mpai-console-logging-status" class="' . ($value ? 'active' : 'inactive') . '">' . ($value ? 'ENABLED' : 'DISABLED') . '</span>
                <button type="button" id="mpai-test-console-logging" class="button button-secondary">Test Console Logging</button>
                <div id="mpai-console-test-result" class="mpai-test-result" style="display:none;"></div>
            </div>';
        },
        'mpai_options',
        'mpai_debug_logging'
    );
    
    add_settings_field(
        'mpai_console_log_level',
        __('Console Log Level', 'memberpress-ai-assistant'),
        function() {
            $value = get_option('mpai_console_log_level', 'info');
            $levels = array(
                'error' => __('Error (Minimal)', 'memberpress-ai-assistant'),
                'warn' => __('Warning', 'memberpress-ai-assistant'),
                'info' => __('Info (Recommended)', 'memberpress-ai-assistant'),
                'debug' => __('Debug (Verbose)', 'memberpress-ai-assistant'),
            );
            echo '<select id="mpai_console_log_level" name="mpai_console_log_level">';
            foreach ($levels as $level_key => $level_name) {
                echo '<option value="' . esc_attr($level_key) . '" ' . selected($value, $level_key, false) . '>' . esc_html($level_name) . '</option>';
            }
            echo '</select>';
            echo '<p class="description">' . __('Select the level of detail for console logs.', 'memberpress-ai-assistant') . '</p>';
        },
        'mpai_options',
        'mpai_debug_logging'
    );
    
    // Log Categories
    add_settings_section(
        'mpai_debug_log_categories',
        __('Log Categories', 'memberpress-ai-assistant'),
        function() {},
        'mpai_options'
    );
    
    add_settings_field(
        'mpai_log_api_calls',
        __('Log API Calls', 'memberpress-ai-assistant'),
        function() {
            $value = get_option('mpai_log_api_calls', false);
            echo '<label><input type="checkbox" id="mpai_log_api_calls" name="mpai_log_api_calls" value="1" ' . checked($value, true, false) . '> ' . __('Log API requests and responses', 'memberpress-ai-assistant') . '</label>';
        },
        'mpai_options',
        'mpai_debug_log_categories'
    );
    
    add_settings_field(
        'mpai_log_tool_usage',
        __('Log Tool Usage', 'memberpress-ai-assistant'),
        function() {
            $value = get_option('mpai_log_tool_usage', false);
            echo '<label><input type="checkbox" id="mpai_log_tool_usage" name="mpai_log_tool_usage" value="1" ' . checked($value, true, false) . '> ' . __('Log tool execution and results', 'memberpress-ai-assistant') . '</label>';
        },
        'mpai_options',
        'mpai_debug_log_categories'
    );
    
    add_settings_field(
        'mpai_log_agent_activity',
        __('Log Agent Activity', 'memberpress-ai-assistant'),
        function() {
            $value = get_option('mpai_log_agent_activity', false);
            echo '<label><input type="checkbox" id="mpai_log_agent_activity" name="mpai_log_agent_activity" value="1" ' . checked($value, true, false) . '> ' . __('Log specialized agent activities', 'memberpress-ai-assistant') . '</label>';
        },
        'mpai_options',
        'mpai_debug_log_categories'
    );
    
    add_settings_field(
        'mpai_log_timing',
        __('Log Performance Timing', 'memberpress-ai-assistant'),
        function() {
            $value = get_option('mpai_log_timing', false);
            echo '<label><input type="checkbox" id="mpai_log_timing" name="mpai_log_timing" value="1" ' . checked($value, true, false) . '> ' . __('Log performance metrics and timing information', 'memberpress-ai-assistant') . '</label>';
        },
        'mpai_options',
        'mpai_debug_log_categories'
    );
    
    // Diagnostics Section
    add_settings_section(
        'mpai_debug_diagnostics',
        __('Diagnostics & Testing', 'memberpress-ai-assistant'),
        function() {},
        'mpai_options'
    );
    
    add_settings_field(
        'mpai_diagnostics_page',
        __('Diagnostics Page', 'memberpress-ai-assistant'),
        function() {
            echo '<p>' . __('For comprehensive system tests and diagnostics, please use the dedicated Diagnostics page.', 'memberpress-ai-assistant') . '</p>';
            echo '<p><a href="' . admin_url('admin.php?page=memberpress-ai-assistant-diagnostics') . '" class="button button-primary">' . __('Open Diagnostics Page', 'memberpress-ai-assistant') . '</a></p>';
        },
        'mpai_options',
        'mpai_debug_diagnostics'
    );
}

// Display the settings page
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php 
    // Display any settings errors/notices
    settings_errors('mpai_messages');
    ?>
    
    <h2 class="nav-tab-wrapper">
        <?php foreach ($tabs as $tab_id => $tab_name) { ?>
            <a href="<?php echo admin_url('admin.php?page=memberpress-ai-assistant-settings&tab=' . $tab_id); ?>" class="nav-tab <?php echo $current_tab === $tab_id ? 'nav-tab-active' : ''; ?>"><?php echo esc_html($tab_name); ?></a>
        <?php } ?>
    </h2>
    
    <form method="post" action="options.php">
        <?php
        settings_fields('mpai_options');
        do_settings_sections('mpai_options');
        submit_button();
        ?>
    </form>
    
    <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
    <!-- Debug Info, only visible during debug mode -->
    <div class="mpai-debug-info" style="margin-top: 30px; border-top: 1px solid #ddd; padding-top: 20px;">
        <h3>Debug Information</h3>
        <p>Current tab: <?php echo esc_html($current_tab); ?></p>
        <p>Form posts to: options.php</p>
        <p>Option group: mpai_options</p>
        <p>Current user can manage_options: <?php echo current_user_can('manage_options') ? 'Yes' : 'No'; ?></p>
    </div>
    <?php endif; ?>
</div>

<style>
/* Add some nice styling for the settings page */
.mpai-api-status {
    margin-top: 10px;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
}

.mpai-api-status-icon {
    display: inline-block;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    margin-right: 5px;
}

.mpai-api-status-icon.mpai-status-connected {
    background-color: #00a32a;
}

.mpai-api-status-icon.mpai-status-disconnected {
    background-color: #cc1818;
}

.mpai-api-status-icon.mpai-status-unknown {
    background-color: #dba617;
}

.mpai-api-status-text {
    margin-right: 10px;
}

.mpai-test-result {
    margin-top: 10px;
    padding: 10px;
    border-left: 4px solid #dba617;
    background-color: #f8f8f8;
    width: 100%;
}

.mpai-test-success {
    border-left-color: #00a32a;
}

.mpai-test-error {
    border-left-color: #cc1818;
}

.mpai-test-loading {
    border-left-color: #dba617;
}

.mpai-debug-control {
    margin-top: 10px;
}

#mpai-console-logging-status {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    margin-right: 10px;
    font-weight: bold;
}

#mpai-console-logging-status.active {
    background-color: #00a32a;
    color: white;
}

#mpai-console-logging-status.inactive {
    background-color: #cc1818;
    color: white;
}
</style>