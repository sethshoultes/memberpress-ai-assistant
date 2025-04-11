<?php
/**
 * Settings Page Template
 * 
 * New modular settings page using the Settings Registry system
 *
 * @package MemberPress AI Assistant
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get the settings registry instance
global $mpai_settings_registry;

if (!isset($mpai_settings_registry) || !($mpai_settings_registry instanceof MPAI_Settings_Registry)) {
    // Create a new settings registry if it doesn't exist
    $mpai_settings_registry = new MPAI_Settings_Registry();
    
    // Set up default tabs and settings
    $mpai_settings_registry = mpai_register_default_settings($mpai_settings_registry);
}

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
    // General Tab
    $registry->register_tab('general', __('General', 'memberpress-ai-assistant'));
    
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
                'gpt-4o' => __('GPT-4o', 'memberpress-ai-assistant'),
                'gpt-4-turbo' => __('GPT-4 Turbo', 'memberpress-ai-assistant'),
                'gpt-4' => __('GPT-4', 'memberpress-ai-assistant'),
                'gpt-3.5-turbo' => __('GPT-3.5 Turbo', 'memberpress-ai-assistant'),
            ],
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
                'claude-3-opus-20240229' => __('Claude 3 Opus', 'memberpress-ai-assistant'),
                'claude-3-sonnet-20240229' => __('Claude 3 Sonnet', 'memberpress-ai-assistant'),
                'claude-3-haiku-20240307' => __('Claude 3 Haiku', 'memberpress-ai-assistant'),
                'claude-2' => __('Claude 2', 'memberpress-ai-assistant'),
            ],
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
            'register_args' => [
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'openai',
            ]
        ]
    );
    
    // Chat Tab
    $registry->register_tab('chat', __('Chat Interface', 'memberpress-ai-assistant'));
    
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
            'register_args' => [
                'sanitize_callback' => 'wp_kses_post',
                'default' => __('Hi there! I\'m your MemberPress AI Assistant. How can I help you today?', 'memberpress-ai-assistant'),
            ]
        ]
    );
    
    // Tools Tab
    $registry->register_tab('tools', __('Tools', 'memberpress-ai-assistant'));
    
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
            'description' => __('When enabled, the AI can execute MCP commands to perform actions.', 'memberpress-ai-assistant'),
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
            'register_args' => [
                'sanitize_callback' => function($value) {
                    return (bool) $value;
                },
                'default' => true,
            ]
        ]
    );
    
    // Advanced Tab
    $registry->register_tab('advanced', __('Advanced', 'memberpress-ai-assistant'));
    
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
            'register_args' => [
                'sanitize_callback' => function($value) {
                    return $value ? '1' : '0';
                },
                'default' => '0',
            ]
        ]
    );
    
    // Return the updated registry
    return $registry;
}