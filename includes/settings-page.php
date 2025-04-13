<?php
/**
 * Settings Page
 *
 * Displays the settings page for MemberPress AI Assistant using the improved Settings Registry
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Direct menu fix for settings page
global $parent_file, $submenu_file;
$parent_file = class_exists('MeprAppCtrl') ? 'memberpress' : 'memberpress-ai-assistant';
$submenu_file = 'memberpress-ai-assistant-settings';

// Load the settings registry class if needed
if (!class_exists('MPAI_Settings_Registry')) {
    $settings_registry_path = dirname(__FILE__) . '/class-mpai-settings-registry.php';
    if (file_exists($settings_registry_path)) {
        require_once $settings_registry_path;
    }
}

// Check if the MPAI_Settings class exists
if (!class_exists('MPAI_Settings')) {
    $settings_path = dirname(__FILE__) . '/class-mpai-settings.php';
    if (file_exists($settings_path)) {
        require_once $settings_path;
    }
}

// Create Settings Registry instance
$mpai_settings_registry = new MPAI_Settings_Registry();

// Register default settings
if (function_exists('mpai_register_default_settings')) {
    $mpai_settings_registry = mpai_register_default_settings($mpai_settings_registry);
} else {
    // Fallback implementation
    $settings = new MPAI_Settings();
    
    // General Tab
    $mpai_settings_registry->register_tab(
        'general', 
        __('General', 'memberpress-ai-assistant'),
        null,
        [
            'icon' => 'dashicons-admin-generic',
            'description' => __('Configure general settings for the AI Assistant including API providers and models.', 'memberpress-ai-assistant')
        ]
    );
    
    // API Providers - OpenAI
    $mpai_settings_registry->register_setting_group('general', 'openai', __('OpenAI Settings', 'memberpress-ai-assistant'));
    
    // OpenAI API Key
    $mpai_settings_registry->register_setting(
        'general', 
        'openai', 
        'api_key', 
        __('API Key', 'memberpress-ai-assistant'), 
        'text',
        [
            'description' => __('Enter your OpenAI API key. You can get one from the OpenAI Dashboard.', 'memberpress-ai-assistant'),
            'placeholder' => __('sk-...', 'memberpress-ai-assistant'),
            'class' => 'regular-text code',
            'tooltip' => __('API key used to authenticate requests to OpenAI.', 'memberpress-ai-assistant'),
            'register_args' => [
                'sanitize_callback' => 'sanitize_text_field',
                'show_in_rest' => false,
            ]
        ]
    );
    
    // OpenAI Model
    $mpai_settings_registry->register_setting(
        'general', 
        'openai', 
        'model', 
        __('Model', 'memberpress-ai-assistant'),
        'select',
        [
            'description' => __('Select the OpenAI model to use.', 'memberpress-ai-assistant'),
            'options' => $settings->get_available_models(),
            'tooltip' => __('Different models have different capabilities and costs.', 'memberpress-ai-assistant'),
            'register_args' => [
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'gpt-4o',
            ]
        ]
    );
    
    // API Providers - Anthropic
    $mpai_settings_registry->register_setting_group('general', 'anthropic', __('Anthropic Settings', 'memberpress-ai-assistant'));
    
    // Anthropic API Key
    $mpai_settings_registry->register_setting(
        'general', 
        'anthropic', 
        'anthropic_api_key', 
        __('API Key', 'memberpress-ai-assistant'),
        'text',
        [
            'description' => __('Enter your Anthropic API key. You can get one from the Anthropic Console.', 'memberpress-ai-assistant'),
            'placeholder' => __('sk-ant-...', 'memberpress-ai-assistant'),
            'class' => 'regular-text code',
            'tooltip' => __('API key used to authenticate requests to Anthropic Claude.', 'memberpress-ai-assistant'),
            'register_args' => [
                'sanitize_callback' => 'sanitize_text_field',
                'show_in_rest' => false,
            ]
        ]
    );
    
    // Anthropic Model
    $mpai_settings_registry->register_setting(
        'general', 
        'anthropic', 
        'anthropic_model', 
        __('Model', 'memberpress-ai-assistant'),
        'select',
        [
            'description' => __('Select the Anthropic model to use.', 'memberpress-ai-assistant'),
            'options' => $settings->get_available_anthropic_models(),
            'tooltip' => __('Different models have different capabilities and costs.', 'memberpress-ai-assistant'),
            'register_args' => [
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'claude-3-opus-20240229',
            ]
        ]
    );
    
    // API Provider Selection
    $mpai_settings_registry->register_setting_group('general', 'provider', __('AI Provider', 'memberpress-ai-assistant'));
    
    // Primary API Provider
    $mpai_settings_registry->register_setting(
        'general', 
        'provider', 
        'primary_api', 
        __('Primary AI Provider', 'memberpress-ai-assistant'),
        'radio',
        [
            'description' => __('Select which AI provider to use as the primary source.', 'memberpress-ai-assistant'),
            'options' => $settings->get_available_api_providers(),
            'tooltip' => __('The other provider will be used as a fallback if the primary fails.', 'memberpress-ai-assistant'),
            'register_args' => [
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'openai',
            ]
        ]
    );
    
    // Chat Interface Tab
    $mpai_settings_registry->register_tab(
        'chat', 
        __('Chat Interface', 'memberpress-ai-assistant'),
        null,
        [
            'icon' => 'dashicons-format-chat',
            'description' => __('Configure the appearance and behavior of the AI Assistant chat interface.', 'memberpress-ai-assistant')
        ]
    );
    
    // Chat Interface Settings
    $mpai_settings_registry->register_setting_group('chat', 'interface', __('Chat Interface Settings', 'memberpress-ai-assistant'));
    
    // Enable Chat
    $mpai_settings_registry->register_setting(
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
    $mpai_settings_registry->register_setting(
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
    $mpai_settings_registry->register_setting(
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
    $mpai_settings_registry->register_setting(
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
    $mpai_settings_registry->register_tab(
        'tools', 
        __('Tools', 'memberpress-ai-assistant'),
        null,
        [
            'icon' => 'dashicons-admin-tools',
            'description' => __('Configure which tools are available to the AI Assistant and how they behave.', 'memberpress-ai-assistant')
        ]
    );
    
    // Tools - Command Settings
    $mpai_settings_registry->register_setting_group('tools', 'commands', __('Command Settings', 'memberpress-ai-assistant'));
    
    // Enable MCP
    $mpai_settings_registry->register_setting(
        'tools', 
        'commands', 
        'enable_mcp', 
        __('Enable MCP Commands', 'memberpress-ai-assistant'),
        'checkbox',
        [
            'checkbox_label' => __('Allow AI to execute MCP commands', 'memberpress-ai-assistant'),
            'description' => __('When enabled, the AI can execute MCP commands to perform actions on your behalf.', 'memberpress-ai-assistant'),
            'tooltip' => __('MCP (Model Context Protocol) allows the AI to interact with your WordPress site by executing commands.', 'memberpress-ai-assistant'),
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
    $mpai_settings_registry->register_setting(
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
    
    // Debug Tab
    $mpai_settings_registry->register_tab(
        'debug', 
        __('Debug', 'memberpress-ai-assistant'),
        null,
        [
            'icon' => 'dashicons-bug',
            'description' => __('Debug settings and tools for troubleshooting issues.', 'memberpress-ai-assistant')
        ]
    );
    
    // Console Logging Settings Group
    $mpai_settings_registry->register_setting_group('debug', 'logging', __('Console Logging', 'memberpress-ai-assistant'));
    
    // Enable Console Logging
    $mpai_settings_registry->register_setting(
        'debug', 
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
    $mpai_settings_registry->register_setting(
        'debug', 
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
    
    // Log Categories
    $mpai_settings_registry->register_setting_group('debug', 'log_categories', __('Log Categories', 'memberpress-ai-assistant'));
    
    // Log API Calls
    $mpai_settings_registry->register_setting(
        'debug', 
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
    $mpai_settings_registry->register_setting(
        'debug', 
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
    $mpai_settings_registry->register_setting(
        'debug', 
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
    $mpai_settings_registry->register_setting(
        'debug', 
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
    
    // Diagnostics Group
    $mpai_settings_registry->register_setting_group('debug', 'diagnostics', __('Diagnostics & Testing', 'memberpress-ai-assistant'));
    
    // Diagnostics Page Link
    $mpai_settings_registry->register_setting(
        'debug', 
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
}

// Display any settings errors/notices
settings_errors('mpai_messages');

// Render the settings page
$mpai_settings_registry->render_settings_page();

// Register a function to run after settings are registered
function mpai_register_default_settings($registry) {
    // Use the registry that was already set up
    return $registry;
}