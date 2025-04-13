<?php
/**
 * New Settings Page - Uses the MPAI_Settings_Registry system
 *
 * Displays the settings page for MemberPress AI Assistant using the modular registry system
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Direct menu fix for settings page
global $parent_file, $submenu_file;
$parent_file = class_exists('MeprAppCtrl') ? 'memberpress' : 'memberpress-ai-assistant';
$submenu_file = 'memberpress-ai-assistant-settings';

// Load dependencies
require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-settings-registry.php';

// Create Settings Registry instance
$mpai_settings_registry = new MPAI_Settings_Registry();

// Register default tabs and settings
$mpai_settings_registry = mpai_register_default_settings($mpai_settings_registry);

// Display any settings errors/notices
settings_errors('mpai_messages');

// Render the settings page
$mpai_settings_registry->render_settings_page();

/**
 * Register default settings with the registry
 * 
 * @param MPAI_Settings_Registry $registry Settings registry instance
 * @return MPAI_Settings_Registry Updated registry
 */
function mpai_register_default_settings($registry) {
    // General Tab with API Settings
    $registry->register_tab(
        'general', 
        __('General', 'memberpress-ai-assistant'),
        null,
        [
            'icon' => 'dashicons-admin-generic',
            'description' => __('Configure general settings for the AI Assistant including API providers and models.', 'memberpress-ai-assistant')
        ]
    );
    
    // General - OpenAI Settings Group
    $registry->register_setting_group('general', 'openai', __('OpenAI Settings', 'memberpress-ai-assistant'));
    
    // OpenAI API Key
    $registry->register_setting(
        'general', 
        'openai', 
        'api_key', 
        __('API Key', 'memberpress-ai-assistant'), 
        'text',
        [
            'description' => __('Enter your OpenAI API key.', 'memberpress-ai-assistant'),
            'placeholder' => __('sk-...', 'memberpress-ai-assistant'),
            'class' => 'regular-text code',
            'tooltip' => __('API key used to authenticate requests to OpenAI.', 'memberpress-ai-assistant'),
            'help' => __('
                <p><strong>Where to find your API key:</strong></p>
                <ol>
                    <li>Go to the <a href="https://platform.openai.com/account/api-keys" target="_blank">OpenAI Dashboard</a></li>
                    <li>Sign in to your account</li>
                    <li>Navigate to the API keys section</li>
                    <li>Click "Create new secret key" and copy the key</li>
                    <li>Paste it here</li>
                </ol>
                <p><strong>Note:</strong> Your API key is stored securely in your WordPress database. It is used to make API calls to OpenAI on your behalf.</p>
            ', 'memberpress-ai-assistant'),
            'register_args' => [
                'sanitize_callback' => 'sanitize_text_field',
                'show_in_rest' => false,
            ]
        ]
    );
    
    // OpenAI Model
    $registry->register_setting(
        'general', 
        'openai', 
        'model', 
        __('Model', 'memberpress-ai-assistant'),
        'select',
        [
            'description' => __('Select the OpenAI model to use.', 'memberpress-ai-assistant'),
            'options' => [
                'gpt-4o' => __('GPT-4o (Recommended)', 'memberpress-ai-assistant'),
                'gpt-4-turbo' => __('GPT-4 Turbo', 'memberpress-ai-assistant'),
                'gpt-4' => __('GPT-4', 'memberpress-ai-assistant'),
                'gpt-3.5-turbo' => __('GPT-3.5 Turbo', 'memberpress-ai-assistant'),
            ],
            'tooltip' => __('Different models have different capabilities and costs.', 'memberpress-ai-assistant'),
            'register_args' => [
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'gpt-4o',
            ]
        ]
    );
    
    // OpenAI Temperature
    $registry->register_setting(
        'general', 
        'openai', 
        'temperature', 
        __('Temperature', 'memberpress-ai-assistant'),
        'text',
        [
            'description' => __('Controls randomness: 0.0 is deterministic, 1.0 is very random.', 'memberpress-ai-assistant'),
            'class' => 'small-text',
            'tooltip' => __('Higher values produce more creative responses, lower values are more focused and deterministic.', 'memberpress-ai-assistant'),
            'register_args' => [
                'sanitize_callback' => function($value) {
                    $value = (float) $value;
                    return max(0, min(1, $value)); // Limit between 0.0 and 1.0
                },
                'default' => 0.7,
            ]
        ]
    );
    
    // General - Anthropic Settings Group
    $registry->register_setting_group('general', 'anthropic', __('Anthropic Settings', 'memberpress-ai-assistant'));
    
    // Anthropic API Key
    $registry->register_setting(
        'general', 
        'anthropic', 
        'anthropic_api_key', 
        __('API Key', 'memberpress-ai-assistant'),
        'text',
        [
            'description' => __('Enter your Anthropic API key.', 'memberpress-ai-assistant'),
            'placeholder' => __('sk-ant-...', 'memberpress-ai-assistant'),
            'class' => 'regular-text code',
            'tooltip' => __('API key used to authenticate requests to Anthropic Claude.', 'memberpress-ai-assistant'),
            'help' => __('
                <p><strong>Where to find your API key:</strong></p>
                <ol>
                    <li>Go to the <a href="https://console.anthropic.com/settings/keys" target="_blank">Anthropic Console</a></li>
                    <li>Sign in to your account</li>
                    <li>Navigate to the API keys section</li>
                    <li>Create a new API key and copy it</li>
                    <li>Paste it here</li>
                </ol>
                <p><strong>Note:</strong> Your API key is stored securely in your WordPress database. It is used to make API calls to Anthropic on your behalf.</p>
            ', 'memberpress-ai-assistant'),
            'register_args' => [
                'sanitize_callback' => 'sanitize_text_field',
                'show_in_rest' => false,
            ]
        ]
    );
    
    // Anthropic Model
    $registry->register_setting(
        'general', 
        'anthropic', 
        'anthropic_model', 
        __('Model', 'memberpress-ai-assistant'),
        'select',
        [
            'description' => __('Select the Anthropic model to use.', 'memberpress-ai-assistant'),
            'options' => [
                'claude-3-opus-20240229' => __('Claude 3 Opus (Recommended)', 'memberpress-ai-assistant'),
                'claude-3-sonnet-20240229' => __('Claude 3 Sonnet', 'memberpress-ai-assistant'),
                'claude-3-haiku-20240307' => __('Claude 3 Haiku', 'memberpress-ai-assistant'),
                'claude-2' => __('Claude 2', 'memberpress-ai-assistant'),
            ],
            'tooltip' => __('Different models have different capabilities and costs.', 'memberpress-ai-assistant'),
            'register_args' => [
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'claude-3-opus-20240229',
            ]
        ]
    );
    
    // Anthropic Temperature
    $registry->register_setting(
        'general', 
        'anthropic', 
        'anthropic_temperature', 
        __('Temperature', 'memberpress-ai-assistant'),
        'text',
        [
            'description' => __('Controls randomness: 0.0 is deterministic, 1.0 is very random.', 'memberpress-ai-assistant'),
            'class' => 'small-text',
            'tooltip' => __('Higher values produce more creative responses, lower values are more focused and deterministic.', 'memberpress-ai-assistant'),
            'register_args' => [
                'sanitize_callback' => function($value) {
                    $value = (float) $value;
                    return max(0, min(1, $value)); // Limit between 0.0 and 1.0
                },
                'default' => 0.7,
            ]
        ]
    );
    
    // API Provider Selection
    $registry->register_setting_group('general', 'provider', __('AI Provider', 'memberpress-ai-assistant'));
    
    // Primary API Provider
    $registry->register_setting(
        'general', 
        'provider', 
        'primary_api', 
        __('Primary AI Provider', 'memberpress-ai-assistant'),
        'radio',
        [
            'description' => __('Select which AI provider to use as the primary source.', 'memberpress-ai-assistant'),
            'options' => [
                'openai' => __('OpenAI (GPT-4, GPT-3.5)', 'memberpress-ai-assistant'),
                'anthropic' => __('Anthropic (Claude)', 'memberpress-ai-assistant'),
            ],
            'tooltip' => __('The other provider will be used as a fallback if the primary fails.', 'memberpress-ai-assistant'),
            'register_args' => [
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'openai',
            ]
        ]
    );
    
    // Chat Interface Tab
    $registry->register_tab(
        'chat', 
        __('Chat Interface', 'memberpress-ai-assistant'),
        null,
        [
            'icon' => 'dashicons-format-chat',
            'description' => __('Configure the appearance and behavior of the AI Assistant chat interface.', 'memberpress-ai-assistant')
        ]
    );
    
    // Chat - Settings Group
    $registry->register_setting_group('chat', 'interface', __('Chat Interface Settings', 'memberpress-ai-assistant'));
    
    // Enable Chat
    $registry->register_setting(
        'chat', 
        'interface', 
        'enable_chat', 
        __('Enable Chat Interface', 'memberpress-ai-assistant'),
        'checkbox',
        [
            'checkbox_label' => __('Show chat interface on admin pages', 'memberpress-ai-assistant'),
            'description' => __('Enable or disable the chat interface on all admin pages.', 'memberpress-ai-assistant'),
            'tooltip' => __('When disabled, the chat interface will not appear anywhere.', 'memberpress-ai-assistant'),
            'register_args' => [
                'sanitize_callback' => function($value) {
                    return (bool) $value;
                },
                'default' => true,
            ]
        ]
    );
    
    // Chat Position
    $registry->register_setting(
        'chat', 
        'interface', 
        'chat_position', 
        __('Chat Position', 'memberpress-ai-assistant'),
        'select',
        [
            'description' => __('Select where the chat interface should appear.', 'memberpress-ai-assistant'),
            'options' => [
                'bottom-right' => __('Bottom Right', 'memberpress-ai-assistant'),
                'bottom-left' => __('Bottom Left', 'memberpress-ai-assistant'),
                'top-right' => __('Top Right', 'memberpress-ai-assistant'),
                'top-left' => __('Top Left', 'memberpress-ai-assistant'),
            ],
            'tooltip' => __('The position of the chat interface bubble on the screen.', 'memberpress-ai-assistant'),
            'register_args' => [
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'bottom-right',
            ]
        ]
    );
    
    // Show on All Pages
    $registry->register_setting(
        'chat', 
        'interface', 
        'show_on_all_pages', 
        __('Show on All Admin Pages', 'memberpress-ai-assistant'),
        'checkbox',
        [
            'checkbox_label' => __('Show chat interface on all admin pages (not just MemberPress pages)', 'memberpress-ai-assistant'),
            'description' => __('When enabled, the chat interface will appear on all WordPress admin pages.', 'memberpress-ai-assistant'),
            'tooltip' => __('If disabled, the chat interface will only appear on MemberPress admin pages.', 'memberpress-ai-assistant'),
            'register_args' => [
                'sanitize_callback' => function($value) {
                    return (bool) $value;
                },
                'default' => true,
            ]
        ]
    );
    
    // Welcome Message
    $registry->register_setting(
        'chat', 
        'interface', 
        'welcome_message', 
        __('Welcome Message', 'memberpress-ai-assistant'),
        'textarea',
        [
            'description' => __('The message displayed when the chat is first opened.', 'memberpress-ai-assistant'),
            'placeholder' => __('Hi there! I\'m your MemberPress AI Assistant. How can I help you today?', 'memberpress-ai-assistant'),
            'rows' => 3,
            'tooltip' => __('This message is shown to the user when they first open the chat.', 'memberpress-ai-assistant'),
            'register_args' => [
                'sanitize_callback' => 'wp_kses_post',
                'default' => __('Hi there! I\'m your MemberPress AI Assistant. How can I help you today?', 'memberpress-ai-assistant'),
            ]
        ]
    );
    
    // Tools Tab
    $registry->register_tab(
        'tools', 
        __('Tools', 'memberpress-ai-assistant'),
        null,
        [
            'icon' => 'dashicons-admin-tools',
            'description' => __('Configure which tools are available to the AI Assistant and how they behave.', 'memberpress-ai-assistant')
        ]
    );
    
    // Tools - Command Settings Group
    $registry->register_setting_group('tools', 'commands', __('Command Settings', 'memberpress-ai-assistant'));
    
    // Enable MCP
    $registry->register_setting(
        'tools', 
        'commands', 
        'enable_mcp', 
        __('Enable MCP Commands', 'memberpress-ai-assistant'),
        'checkbox',
        [
            'checkbox_label' => __('Allow AI to execute MCP commands', 'memberpress-ai-assistant'),
            'description' => __('When enabled, the AI can execute MCP commands to perform actions on your behalf.', 'memberpress-ai-assistant'),
            'tooltip' => __('MCP (Model Context Protocol) allows the AI to interact with your WordPress site by executing commands.', 'memberpress-ai-assistant'),
            'help' => __('
                <p><strong>What is MCP?</strong></p>
                <p>The Model Context Protocol (MCP) is a system that allows the AI assistant to use tools to perform actions on your WordPress site. When enabled, the AI will be aware of available tools and can use them when needed.</p>
                
                <p><strong>Benefits of enabling MCP:</strong></p>
                <ul>
                    <li>The AI can get real-time information from your WordPress site</li>
                    <li>The AI can perform actions like creating content, managing members, etc.</li>
                    <li>You can ask the AI to execute complex tasks without manual intervention</li>
                </ul>
                
                <p><strong>Security considerations:</strong></p>
                <p>MCP commands are executed with the permissions of the current WordPress user. Only users with appropriate permissions can execute sensitive operations.</p>
            ', 'memberpress-ai-assistant'),
            'field_class' => 'highlight-setting',
            'register_args' => [
                'sanitize_callback' => function($value) {
                    return (bool) $value;
                },
                'default' => true,
            ]
        ]
    );
    
    // Enable CLI Commands
    $registry->register_setting(
        'tools', 
        'commands', 
        'enable_cli_commands', 
        __('Enable CLI Commands', 'memberpress-ai-assistant'),
        'checkbox',
        [
            'checkbox_label' => __('Allow AI to execute CLI commands', 'memberpress-ai-assistant'),
            'description' => __('When enabled, the AI can execute CLI commands to retrieve information.', 'memberpress-ai-assistant'),
            'tooltip' => __('CLI commands are WP-CLI commands that can be executed to get information about your WordPress site.', 'memberpress-ai-assistant'),
            'register_args' => [
                'sanitize_callback' => function($value) {
                    return (bool) $value;
                },
                'default' => true,
            ]
        ]
    );
    
    // Tools - Tool Settings Group
    $registry->register_setting_group('tools', 'tools', __('Tool Settings', 'memberpress-ai-assistant'));
    
    // Enable WP CLI Tool
    $registry->register_setting(
        'tools', 
        'tools', 
        'enable_wp_cli_tool', 
        __('Enable WP CLI Tool', 'memberpress-ai-assistant'),
        'checkbox',
        [
            'checkbox_label' => __('Allow AI to use WP CLI tool', 'memberpress-ai-assistant'),
            'description' => __('When enabled, the AI can use the WP CLI tool to execute WordPress CLI commands.', 'memberpress-ai-assistant'),
            'tooltip' => __('This tool allows the AI to get information about WordPress using WP-CLI.', 'memberpress-ai-assistant'),
            'register_args' => [
                'sanitize_callback' => function($value) {
                    return (bool) $value;
                },
                'default' => true,
            ]
        ]
    );
    
    // Enable MemberPress Info Tool
    $registry->register_setting(
        'tools', 
        'tools', 
        'enable_memberpress_info_tool', 
        __('Enable MemberPress Info Tool', 'memberpress-ai-assistant'),
        'checkbox',
        [
            'checkbox_label' => __('Allow AI to use MemberPress Info tool', 'memberpress-ai-assistant'),
            'description' => __('When enabled, the AI can use the MemberPress Info tool to retrieve information about memberships, transactions, etc.', 'memberpress-ai-assistant'),
            'tooltip' => __('This tool allows the AI to access information about your MemberPress site, such as members, memberships, and transactions.', 'memberpress-ai-assistant'),
            'register_args' => [
                'sanitize_callback' => function($value) {
                    return (bool) $value;
                },
                'default' => true,
            ]
        ]
    );
    
    // Enable Plugin Logs Tool
    $registry->register_setting(
        'tools', 
        'tools', 
        'enable_plugin_logs_tool', 
        __('Enable Plugin Logs Tool', 'memberpress-ai-assistant'),
        'checkbox',
        [
            'checkbox_label' => __('Allow AI to use Plugin Logs tool', 'memberpress-ai-assistant'),
            'description' => __('When enabled, the AI can use the Plugin Logs tool to retrieve and analyze plugin activity logs.', 'memberpress-ai-assistant'),
            'tooltip' => __('This tool allows the AI to access information about plugin installations, activations, deactivations, and updates.', 'memberpress-ai-assistant'),
            'register_args' => [
                'sanitize_callback' => function($value) {
                    return (bool) $value;
                },
                'default' => true,
            ]
        ]
    );
    
    // Advanced Tab
    $registry->register_tab(
        'advanced', 
        __('Advanced', 'memberpress-ai-assistant'),
        null,
        [
            'icon' => 'dashicons-admin-settings',
            'description' => __('Configure advanced settings such as logging and debugging options.', 'memberpress-ai-assistant')
        ]
    );
    
    // Advanced - Logging Settings Group
    $registry->register_setting_group('advanced', 'logging', __('Console Logging', 'memberpress-ai-assistant'));
    
    // Enable Console Logging
    $registry->register_setting(
        'advanced', 
        'logging', 
        'enable_console_logging', 
        __('Enable Console Logging', 'memberpress-ai-assistant'),
        'checkbox',
        [
            'checkbox_label' => __('Enable detailed logging to browser console', 'memberpress-ai-assistant'),
            'description' => __('When enabled, detailed logs will be output to the browser console for debugging.', 'memberpress-ai-assistant'),
            'tooltip' => __('Useful for debugging, but should be disabled in production.', 'memberpress-ai-assistant'),
            'register_args' => [
                'sanitize_callback' => function($value) {
                    return $value ? '1' : '0';
                },
                'default' => '0',
            ]
        ]
    );
    
    // Console Log Level
    $registry->register_setting(
        'advanced', 
        'logging', 
        'console_log_level', 
        __('Console Log Level', 'memberpress-ai-assistant'),
        'select',
        [
            'description' => __('Select the level of detail for console logs.', 'memberpress-ai-assistant'),
            'options' => [
                'error' => __('Error (Minimal)', 'memberpress-ai-assistant'),
                'warn' => __('Warning', 'memberpress-ai-assistant'),
                'info' => __('Info (Recommended)', 'memberpress-ai-assistant'),
                'debug' => __('Debug (Verbose)', 'memberpress-ai-assistant'),
            ],
            'tooltip' => __('Higher levels include all lower levels. Debug is the most verbose.', 'memberpress-ai-assistant'),
            'register_args' => [
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'info',
            ]
        ]
    );
    
    // Log Categories Group
    $registry->register_setting_group('advanced', 'log_categories', __('Log Categories', 'memberpress-ai-assistant'));
    
    // Log API Calls
    $registry->register_setting(
        'advanced', 
        'log_categories', 
        'log_api_calls', 
        __('Log API Calls', 'memberpress-ai-assistant'),
        'checkbox',
        [
            'checkbox_label' => __('Log API requests and responses', 'memberpress-ai-assistant'),
            'tooltip' => __('Log details about API calls to OpenAI and Anthropic.', 'memberpress-ai-assistant'),
            'register_args' => [
                'sanitize_callback' => function($value) {
                    return $value ? '1' : '0';
                },
                'default' => '0',
            ]
        ]
    );
    
    // Log Tool Usage
    $registry->register_setting(
        'advanced', 
        'log_categories', 
        'log_tool_usage', 
        __('Log Tool Usage', 'memberpress-ai-assistant'),
        'checkbox',
        [
            'checkbox_label' => __('Log tool execution and results', 'memberpress-ai-assistant'),
            'tooltip' => __('Log details about tool invocations and their results.', 'memberpress-ai-assistant'),
            'register_args' => [
                'sanitize_callback' => function($value) {
                    return $value ? '1' : '0';
                },
                'default' => '0',
            ]
        ]
    );
    
    // Log Agent Activity
    $registry->register_setting(
        'advanced', 
        'log_categories', 
        'log_agent_activity', 
        __('Log Agent Activity', 'memberpress-ai-assistant'),
        'checkbox',
        [
            'checkbox_label' => __('Log specialized agent activities', 'memberpress-ai-assistant'),
            'tooltip' => __('Log details about agent invocations and their activities.', 'memberpress-ai-assistant'),
            'register_args' => [
                'sanitize_callback' => function($value) {
                    return $value ? '1' : '0';
                },
                'default' => '0',
            ]
        ]
    );
    
    // Log Timing
    $registry->register_setting(
        'advanced', 
        'log_categories', 
        'log_timing', 
        __('Log Performance Timing', 'memberpress-ai-assistant'),
        'checkbox',
        [
            'checkbox_label' => __('Log performance metrics and timing information', 'memberpress-ai-assistant'),
            'tooltip' => __('Log detailed timing information about operations for performance analysis.', 'memberpress-ai-assistant'),
            'register_args' => [
                'sanitize_callback' => function($value) {
                    return $value ? '1' : '0';
                },
                'default' => '0',
            ]
        ]
    );
    
    // Advanced - Diagnostic Links Group
    $registry->register_setting_group('advanced', 'diagnostics', __('Diagnostics & Testing', 'memberpress-ai-assistant'));
    
    // Diagnostics Page Link
    $registry->register_setting(
        'advanced', 
        'diagnostics', 
        'diagnostics_page', 
        __('Diagnostics Page', 'memberpress-ai-assistant'),
        'custom',
        [
            'render_callback' => function($name, $value, $args) {
                ?>
                <p><?php _e('For comprehensive system tests and diagnostics, please use the dedicated Diagnostics page.', 'memberpress-ai-assistant'); ?></p>
                <p>
                    <a href="<?php echo admin_url('admin.php?page=memberpress-ai-assistant-diagnostics'); ?>" class="button button-primary">
                        <?php _e('Open Diagnostics Page', 'memberpress-ai-assistant'); ?>
                    </a>
                </p>
                <?php
            }
        ]
    );
    
    // Return the updated registry
    return $registry;
}