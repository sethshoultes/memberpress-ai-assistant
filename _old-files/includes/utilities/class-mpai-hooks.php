<?php
/**
 * Hook and Filter Utilities
 *
 * Provides documentation and helper functions for the hook and filter system
 *
 * @package    MemberPress_AI_Assistant
 * @subpackage MemberPress_AI_Assistant/includes/utilities
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Hook and Filter Utilities
 *
 * Provides documentation and helper functions for the hook and filter system
 *
 * @package    MemberPress_AI_Assistant
 * @subpackage MemberPress_AI_Assistant/includes/utilities
 */
class MPAI_Hooks {
    /**
     * Store for hook documentation
     *
     * @var array
     */
    private static $hooks = [];

    /**
     * Store for filter documentation
     *
     * @var array
     */
    private static $filters = [];
    
    /**
     * Initialize hooks and filters
     */
    public static function init() {
        // Register all hooks and filters
        self::register_core_hooks();
        self::register_chat_hooks();
        self::register_history_hooks();
        self::register_content_hooks();
        self::register_tool_hooks();
        self::register_agent_hooks();
        self::register_api_hooks();
        self::register_error_hooks();
        self::register_logging_hooks();
    }
    
    /**
     * Register core plugin hooks
     */
    private static function register_core_hooks() {
        // Plugin Initialization Hooks
        self::register_hook(
            'MPAI_HOOK_ACTION_before_plugin_init',
            'Fires before the plugin initialization process begins.',
            [],
            '1.7.0',
            'core'
        );
        
        self::register_hook(
            'MPAI_HOOK_ACTION_loaded_dependencies',
            'Fires after all plugin dependencies have been loaded.',
            [],
            '1.7.0',
            'core'
        );
        
        self::register_hook(
            'MPAI_HOOK_ACTION_after_plugin_init',
            'Fires after the plugin has been fully initialized.',
            [],
            '1.7.0',
            'core'
        );
        
        self::register_filter(
            'MPAI_HOOK_FILTER_default_options',
            'Filters the default plugin options.',
            [],
            ['options' => ['type' => 'array', 'description' => 'The default options array.']],
            '1.7.0',
            'core'
        );
        
        self::register_filter(
            'MPAI_HOOK_FILTER_plugin_capabilities',
            'Filters the plugin capabilities.',
            [],
            ['capabilities' => ['type' => 'array', 'description' => 'The capabilities array.']],
            '1.7.0',
            'core'
        );
    }
    
    /**
     * Register chat processing hooks
     */
    private static function register_chat_hooks() {
        // Chat Processing Hooks
        self::register_hook(
            'MPAI_HOOK_ACTION_before_process_message',
            'Fires before a user message is processed.',
            ['message' => ['type' => 'string', 'description' => 'The user message being processed.']],
            '1.7.0',
            'chat'
        );
        
        self::register_hook(
            'MPAI_HOOK_ACTION_after_process_message',
            'Fires after a user message has been processed.',
            [
                'message' => ['type' => 'string', 'description' => 'The user message that was processed.'],
                'response' => ['type' => 'array', 'description' => 'The AI response array.']
            ],
            '1.7.0',
            'chat'
        );
        
        self::register_filter(
            'MPAI_HOOK_FILTER_system_prompt',
            'Filters the system prompt sent to the AI.',
            '',
            ['system_prompt' => ['type' => 'string', 'description' => 'The system prompt to filter.']],
            '1.7.0',
            'chat'
        );
        
        self::register_filter(
            'MPAI_HOOK_FILTER_chat_conversation_history',
            'Filters the conversation history array.',
            [],
            ['conversation' => ['type' => 'array', 'description' => 'The conversation history array.']],
            '1.7.0',
            'chat'
        );
        
        self::register_filter(
            'MPAI_HOOK_FILTER_message_content',
            'Filters message content before sending to AI.',
            '',
            ['message' => ['type' => 'string', 'description' => 'The message content to filter.']],
            '1.7.0',
            'chat'
        );
        
        self::register_filter(
            'MPAI_HOOK_FILTER_response_content',
            'Filters AI response before returning to user.',
            '',
            [
                'response' => ['type' => 'string', 'description' => 'The AI response content.'],
                'message' => ['type' => 'string', 'description' => 'The original user message.']
            ],
            '1.7.0',
            'chat'
        );
        
        self::register_filter(
            'MPAI_HOOK_FILTER_user_context',
            'Filters user context data sent with messages.',
            [],
            ['user_context' => ['type' => 'array', 'description' => 'The user context data.']],
            '1.7.0',
            'chat'
        );
        
        self::register_filter(
            'MPAI_HOOK_FILTER_allowed_commands',
            'Filters allowed commands in chat.',
            [],
            ['allowed_commands' => ['type' => 'array', 'description' => 'Array of allowed command names.']],
            '1.7.0',
            'chat'
        );
    }
    
    /**
     * Register history management hooks
     */
    private static function register_history_hooks() {
        // History Management Hooks
        self::register_hook(
            'MPAI_HOOK_ACTION_before_save_history',
            'Action before saving chat history.',
            [
                'message' => ['type' => 'string', 'description' => 'The user message.'],
                'response' => ['type' => 'string', 'description' => 'The AI response.']
            ],
            '1.7.0',
            'history'
        );
        
        self::register_hook(
            'MPAI_HOOK_ACTION_after_save_history',
            'Action after saving chat history.',
            [
                'message' => ['type' => 'string', 'description' => 'The user message.'],
                'response' => ['type' => 'string', 'description' => 'The AI response.']
            ],
            '1.7.0',
            'history'
        );
        
        self::register_hook(
            'MPAI_HOOK_ACTION_before_clear_history',
            'Action before clearing chat history.',
            [],
            '1.7.0',
            'history'
        );
        
        self::register_hook(
            'MPAI_HOOK_ACTION_after_clear_history',
            'Action after clearing chat history.',
            [],
            '1.7.0',
            'history'
        );
        
        self::register_filter(
            'MPAI_HOOK_FILTER_history_retention',
            'Filter history retention settings.',
            30,
            ['days' => ['type' => 'integer', 'description' => 'Number of days to retain history.']],
            '1.7.0',
            'history'
        );
    }
    
    /**
     * Register API integration hooks
     */
    private static function register_api_hooks() {
        // API Integration Hooks
        self::register_hook(
            'MPAI_HOOK_ACTION_before_api_request',
            'Action before sending request to AI provider',
            [
                'api_name' => ['type' => 'string', 'description' => 'The name of the API provider.'],
                'request_params' => ['type' => 'array', 'description' => 'The request parameters.'],
                'context' => ['type' => 'array', 'description' => 'Additional context for the request.']
            ],
            '1.7.0',
            'api'
        );
        
        self::register_hook(
            'MPAI_HOOK_ACTION_after_api_request',
            'Action after receiving response from AI provider',
            [
                'api_name' => ['type' => 'string', 'description' => 'The name of the API provider.'],
                'request_params' => ['type' => 'array', 'description' => 'The request parameters.'],
                'response' => ['type' => 'mixed', 'description' => 'The API response.'],
                'duration' => ['type' => 'float', 'description' => 'The request duration in seconds.']
            ],
            '1.7.0',
            'api'
        );
        
        self::register_filter(
            'MPAI_HOOK_FILTER_api_request_params',
            'Filter request parameters before sending',
            [],
            [
                'params' => ['type' => 'array', 'description' => 'The request parameters.'],
                'api_name' => ['type' => 'string', 'description' => 'The name of the API provider.']
            ],
            '1.7.0',
            'api'
        );
        
        self::register_filter(
            'MPAI_HOOK_FILTER_api_response',
            'Filter raw API response',
            [],
            [
                'response' => ['type' => 'mixed', 'description' => 'The API response.'],
                'api_name' => ['type' => 'string', 'description' => 'The name of the API provider.'],
                'request_params' => ['type' => 'array', 'description' => 'The request parameters.']
            ],
            '1.7.0',
            'api'
        );
        
        self::register_filter(
            'MPAI_HOOK_FILTER_api_provider',
            'Filter which API provider to use',
            'openai',
            [
                'provider' => ['type' => 'string', 'description' => 'The API provider to use.'],
                'context' => ['type' => 'array', 'description' => 'Additional context for the request.']
            ],
            '1.7.0',
            'api'
        );
        
        self::register_filter(
            'MPAI_HOOK_FILTER_api_rate_limit',
            'Filter rate limiting behavior',
            [],
            [
                'rate_limits' => ['type' => 'array', 'description' => 'The rate limiting configuration.'],
                'api_name' => ['type' => 'string', 'description' => 'The name of the API provider.']
            ],
            '1.7.0',
            'api'
        );
        
        self::register_filter(
            'MPAI_HOOK_FILTER_format_api_response',
            'Filter response formatting for display',
            '',
            [
                'formatted_response' => ['type' => 'string', 'description' => 'The formatted response.'],
                'raw_response' => ['type' => 'mixed', 'description' => 'The raw API response.'],
                'api_name' => ['type' => 'string', 'description' => 'The name of the API provider.']
            ],
            '1.7.0',
            'api'
        );
        
        self::register_filter(
            'MPAI_HOOK_FILTER_cache_ttl',
            'Filter cache time-to-live settings by request type',
            3600,
            [
                'ttl' => ['type' => 'int', 'description' => 'The cache TTL in seconds.'],
                'request_type' => ['type' => 'string', 'description' => 'The type of request.'],
                'api_name' => ['type' => 'string', 'description' => 'The name of the API provider.']
            ],
            '1.7.0',
            'api'
        );
    }
    
    /**
     * Register error handling hooks
     */
    private static function register_error_hooks() {
        // Error Handling Hooks
        self::register_filter(
            'MPAI_HOOK_FILTER_api_error_handling',
            'Filter error handling behavior',
            [],
            [
                'handling' => ['type' => 'array', 'description' => 'The error handling configuration.'],
                'error' => ['type' => 'WP_Error', 'description' => 'The error object.'],
                'api_name' => ['type' => 'string', 'description' => 'The name of the API provider.']
            ],
            '1.7.0',
            'error'
        );
        
        self::register_hook(
            'MPAI_HOOK_ACTION_before_error_recovery',
            'Action before error recovery attempted',
            [
                'error' => ['type' => 'WP_Error', 'description' => 'The error object.'],
                'component' => ['type' => 'string', 'description' => 'The component that failed.'],
                'recovery_strategy' => ['type' => 'array', 'description' => 'The recovery strategy.']
            ],
            '1.7.0',
            'error'
        );
        
        self::register_hook(
            'MPAI_HOOK_ACTION_after_error_recovery',
            'Action after error recovery completed',
            [
                'error' => ['type' => 'WP_Error', 'description' => 'The original error object.'],
                'component' => ['type' => 'string', 'description' => 'The component that failed.'],
                'recovery_result' => ['type' => 'mixed', 'description' => 'The result of recovery attempt.'],
                'success' => ['type' => 'bool', 'description' => 'Whether recovery was successful.']
            ],
            '1.7.0',
            'error'
        );
        
        self::register_filter(
            'MPAI_HOOK_FILTER_error_message',
            'Filter user-facing error messages',
            '',
            [
                'message' => ['type' => 'string', 'description' => 'The error message.'],
                'error' => ['type' => 'WP_Error', 'description' => 'The error object.'],
                'context' => ['type' => 'array', 'description' => 'Additional context.']
            ],
            '1.7.0',
            'error'
        );
        
        self::register_filter(
            'MPAI_HOOK_FILTER_error_should_retry',
            'Filter whether an error should trigger a retry',
            true,
            [
                'should_retry' => ['type' => 'bool', 'description' => 'Whether to retry.'],
                'error' => ['type' => 'WP_Error', 'description' => 'The error object.'],
                'retry_count' => ['type' => 'int', 'description' => 'The current retry count.'],
                'max_retries' => ['type' => 'int', 'description' => 'The maximum number of retries.']
            ],
            '1.7.0',
            'error'
        );
    }
    
    /**
     * Register logging system hooks
     */
    private static function register_logging_hooks() {
        // Logging Hooks
        self::register_filter(
            'MPAI_HOOK_FILTER_log_entry',
            'Filter log entry before writing',
            [],
            [
                'entry' => ['type' => 'array', 'description' => 'The log entry.'],
                'level' => ['type' => 'string', 'description' => 'The log level.'],
                'component' => ['type' => 'string', 'description' => 'The component generating the log.']
            ],
            '1.7.0',
            'logging'
        );
        
        self::register_filter(
            'MPAI_HOOK_FILTER_should_log',
            'Filter whether to log a specific event',
            true,
            [
                'should_log' => ['type' => 'bool', 'description' => 'Whether to log the event.'],
                'level' => ['type' => 'string', 'description' => 'The log level.'],
                'message' => ['type' => 'string', 'description' => 'The log message.'],
                'component' => ['type' => 'string', 'description' => 'The component generating the log.']
            ],
            '1.7.0',
            'logging'
        );
        
        self::register_filter(
            'MPAI_HOOK_FILTER_log_level',
            'Filter log level for a specific event',
            'info',
            [
                'level' => ['type' => 'string', 'description' => 'The log level.'],
                'message' => ['type' => 'string', 'description' => 'The log message.'],
                'component' => ['type' => 'string', 'description' => 'The component generating the log.']
            ],
            '1.7.0',
            'logging'
        );
        
        self::register_filter(
            'MPAI_HOOK_FILTER_log_retention',
            'Filter log retention period',
            30,
            ['days' => ['type' => 'int', 'description' => 'Number of days to retain logs.']],
            '1.7.0',
            'logging'
        );
        
        self::register_filter(
            'MPAI_HOOK_FILTER_sanitize_log_data',
            'Filter to sanitize sensitive data in logs',
            [],
            [
                'data' => ['type' => 'array', 'description' => 'The data to sanitize.'],
                'level' => ['type' => 'string', 'description' => 'The log level.'],
                'component' => ['type' => 'string', 'description' => 'The component generating the log.']
            ],
            '1.7.0',
            'logging'
        );
    }
    
    /**
     * Register content and UI hooks
     */
    private static function register_content_hooks() {
        // Content Generation Hooks
        self::register_filter(
            'MPAI_HOOK_FILTER_generated_content',
            'Filter any AI-generated content before use',
            '',
            [
                'content' => ['type' => 'string', 'description' => 'The generated content.'],
                'content_type' => ['type' => 'string', 'description' => 'The type of content being generated.']
            ],
            '1.7.0',
            'content'
        );
        
        self::register_filter(
            'MPAI_HOOK_FILTER_content_template',
            'Filter content templates before filling with data',
            '',
            ['template' => ['type' => 'string', 'description' => 'The content template.']],
            '1.7.0',
            'content'
        );
        
        self::register_filter(
            'MPAI_HOOK_FILTER_content_formatting',
            'Filter content formatting rules',
            [],
            ['rules' => ['type' => 'array', 'description' => 'The formatting rules.']],
            '1.7.0',
            'content'
        );
        
        self::register_filter(
            'MPAI_HOOK_FILTER_blog_post_content',
            'Filter blog post content before creation',
            [],
            [
                'post_data' => ['type' => 'array', 'description' => 'The post data.'],
                'xml_content' => ['type' => 'string', 'description' => 'The original XML content.']
            ],
            '1.7.0',
            'content'
        );
        
        self::register_hook(
            'MPAI_HOOK_ACTION_before_content_save',
            'Action before saving generated content',
            [
                'content' => ['type' => 'string', 'description' => 'The content to be saved.'],
                'content_type' => ['type' => 'string', 'description' => 'The type of content being saved.']
            ],
            '1.7.0',
            'content'
        );
        
        self::register_hook(
            'MPAI_HOOK_ACTION_after_content_save',
            'Action after saving generated content',
            [
                'content' => ['type' => 'string', 'description' => 'The saved content.'],
                'content_type' => ['type' => 'string', 'description' => 'The type of content that was saved.'],
                'content_id' => ['type' => 'int', 'description' => 'The ID of the saved content.']
            ],
            '1.7.0',
            'content'
        );
        
        self::register_filter(
            'MPAI_HOOK_FILTER_content_type',
            'Filter the detected content type from AI responses',
            'paragraph',
            [
                'detected_type' => ['type' => 'string', 'description' => 'The detected content type.'],
                'block_type' => ['type' => 'string', 'description' => 'The original block type.'],
                'content' => ['type' => 'string', 'description' => 'The content.']
            ],
            '1.7.0',
            'content'
        );
        
        self::register_filter(
            'MPAI_HOOK_FILTER_content_marker',
            'Filter content markers used in XML parsing',
            [],
            ['markers' => ['type' => 'array', 'description' => 'The content markers.']],
            '1.7.0',
            'content'
        );
        
        // Admin Interface Hooks
        self::register_filter(
            'MPAI_HOOK_FILTER_admin_menu_items',
            'Filter admin menu items before registration',
            [],
            [
                'menu_items' => ['type' => 'array', 'description' => 'The menu items.'],
                'has_memberpress' => ['type' => 'bool', 'description' => 'Whether MemberPress is active.']
            ],
            '1.7.0',
            'admin'
        );
        
        self::register_filter(
            'MPAI_HOOK_FILTER_admin_capabilities',
            'Filter capabilities required for admin functions',
            'manage_options',
            [
                'capability' => ['type' => 'string', 'description' => 'The capability.'],
                'menu_slug' => ['type' => 'string', 'description' => 'The menu slug.']
            ],
            '1.7.0',
            'admin'
        );
        
        self::register_filter(
            'MPAI_HOOK_FILTER_settings_fields',
            'Filter settings fields before display',
            [],
            ['fields' => ['type' => 'array', 'description' => 'The settings fields.']],
            '1.7.0',
            'admin'
        );
        
        self::register_filter(
            'MPAI_HOOK_FILTER_settings_tabs',
            'Filter settings tabs before display',
            [],
            ['tabs' => ['type' => 'array', 'description' => 'The settings tabs.']],
            '1.7.0',
            'admin'
        );
        
        self::register_hook(
            'MPAI_HOOK_ACTION_before_display_settings',
            'Action before displaying settings page',
            [],
            '1.7.0',
            'admin'
        );
        
        self::register_hook(
            'MPAI_HOOK_ACTION_after_display_settings',
            'Action after displaying settings page',
            [],
            '1.7.0',
            'admin'
        );
        
        self::register_filter(
            'MPAI_HOOK_FILTER_dashboard_sections',
            'Filter dashboard page sections',
            [],
            ['sections' => ['type' => 'array', 'description' => 'The dashboard sections.']],
            '1.7.0',
            'admin'
        );
        
        self::register_filter(
            'MPAI_HOOK_FILTER_chat_interface_render',
            'Filter chat interface rendering',
            '',
            [
                'content' => ['type' => 'string', 'description' => 'The chat interface HTML.'],
                'position' => ['type' => 'string', 'description' => 'The chat position.'],
                'welcome_message' => ['type' => 'string', 'description' => 'The welcome message.']
            ],
            '1.7.0',
            'admin'
        );
    }
    
    /**
     * Register tool and agent hooks
     */
    private static function register_tool_agent_hooks() {
        // Tool Execution Hooks
        self::register_hook(
            'MPAI_HOOK_ACTION_tool_registry_init',
            'Fires after tool registry initialization',
            ['registry' => ['type' => 'MPAI_Tool_Registry', 'description' => 'The tool registry instance.']],
            '1.7.0',
            'tools'
        );
        
        self::register_hook(
            'MPAI_HOOK_ACTION_register_tool',
            'Fires when a tool is registered to the system',
            [
                'tool_id' => ['type' => 'string', 'description' => 'The ID of the registered tool.'],
                'tool' => ['type' => 'object', 'description' => 'The tool instance.'],
                'registry' => ['type' => 'MPAI_Tool_Registry', 'description' => 'The tool registry instance.']
            ],
            '1.7.0',
            'tools'
        );
        
        self::register_hook(
            'MPAI_HOOK_ACTION_before_tool_execution',
            'Fires before any tool is executed with tool name and parameters',
            [
                'tool_name' => ['type' => 'string', 'description' => 'The name of the tool being executed.'],
                'parameters' => ['type' => 'array', 'description' => 'The parameters for the tool execution.'],
                'tool' => ['type' => 'MPAI_Base_Tool', 'description' => 'The tool instance.']
            ],
            '1.7.0',
            'tools'
        );
        
        self::register_hook(
            'MPAI_HOOK_ACTION_after_tool_execution',
            'Fires after tool execution with tool name, parameters, and result',
            [
                'tool_name' => ['type' => 'string', 'description' => 'The name of the tool that was executed.'],
                'parameters' => ['type' => 'array', 'description' => 'The parameters used for the tool execution.'],
                'result' => ['type' => 'mixed', 'description' => 'The result of the tool execution.'],
                'tool' => ['type' => 'MPAI_Base_Tool', 'description' => 'The tool instance.']
            ],
            '1.7.0',
            'tools'
        );
        
        self::register_filter(
            'MPAI_HOOK_FILTER_tool_parameters',
            'Filters tool parameters before execution',
            [],
            [
                'parameters' => ['type' => 'array', 'description' => 'The parameters to filter.'],
                'tool_name' => ['type' => 'string', 'description' => 'The name of the tool.'],
                'tool' => ['type' => 'MPAI_Base_Tool', 'description' => 'The tool instance.']
            ],
            '1.7.0',
            'tools'
        );
        
        self::register_filter(
            'MPAI_HOOK_FILTER_tool_execution_result',
            'Filters tool execution result',
            null,
            [
                'result' => ['type' => 'mixed', 'description' => 'The result to filter.'],
                'tool_name' => ['type' => 'string', 'description' => 'The name of the tool.'],
                'parameters' => ['type' => 'array', 'description' => 'The parameters used for execution.'],
                'tool' => ['type' => 'MPAI_Base_Tool', 'description' => 'The tool instance.']
            ],
            '1.7.0',
            'tools'
        );
        
        self::register_filter(
            'MPAI_HOOK_FILTER_available_tools',
            'Filters the list of available tools',
            [],
            [
                'tools' => ['type' => 'array', 'description' => 'The tools array.'],
                'registry' => ['type' => 'MPAI_Tool_Registry', 'description' => 'The tool registry instance.']
            ],
            '1.7.0',
            'tools'
        );
        
        self::register_filter(
            'MPAI_HOOK_FILTER_tool_capability_check',
            'Filters whether a user has capability to use a specific tool',
            true,
            [
                'can_use' => ['type' => 'bool', 'description' => 'Whether the user can use the tool.'],
                'tool_name' => ['type' => 'string', 'description' => 'The name of the tool.'],
                'parameters' => ['type' => 'array', 'description' => 'The parameters for the tool execution.'],
                'tool' => ['type' => 'MPAI_Base_Tool', 'description' => 'The tool instance.']
            ],
            '1.7.0',
            'tools'
        );
        
        // Agent System Hooks
        self::register_hook(
            'MPAI_HOOK_ACTION_register_agent',
            'Fires when an agent is registered to the system',
            [
                'agent_id' => ['type' => 'string', 'description' => 'The ID of the registered agent.'],
                'agent_instance' => ['type' => 'object', 'description' => 'The agent instance.'],
                'orchestrator' => ['type' => 'MPAI_Agent_Orchestrator', 'description' => 'The agent orchestrator instance.']
            ],
            '1.7.0',
            'agents'
        );
        
        self::register_hook(
            'MPAI_HOOK_ACTION_before_agent_process',
            'Fires before agent processes a request',
            [
                'agent_id' => ['type' => 'string', 'description' => 'The ID of the agent.'],
                'params' => ['type' => 'array', 'description' => 'The parameters for the agent.'],
                'user_id' => ['type' => 'int', 'description' => 'The user ID.'],
                'context' => ['type' => 'array', 'description' => 'The user context.'],
                'orchestrator' => ['type' => 'MPAI_Agent_Orchestrator', 'description' => 'The agent orchestrator instance.']
            ],
            '1.7.0',
            'agents'
        );
        
        self::register_hook(
            'MPAI_HOOK_ACTION_after_agent_process',
            'Fires after agent processes a request',
            [
                'agent_id' => ['type' => 'string', 'description' => 'The ID of the agent.'],
                'params' => ['type' => 'array', 'description' => 'The parameters for the agent.'],
                'user_id' => ['type' => 'int', 'description' => 'The user ID.'],
                'result' => ['type' => 'array', 'description' => 'The result of the agent processing.'],
                'orchestrator' => ['type' => 'MPAI_Agent_Orchestrator', 'description' => 'The agent orchestrator instance.']
            ],
            '1.7.0',
            'agents'
        );
        
        self::register_filter(
            'MPAI_HOOK_FILTER_agent_capabilities',
            'Filters agent capabilities',
            [],
            [
                'capabilities' => ['type' => 'array', 'description' => 'The agent capabilities.'],
                'agent_id' => ['type' => 'string', 'description' => 'The ID of the agent.'],
                'agent' => ['type' => 'object', 'description' => 'The agent instance.'],
                'orchestrator' => ['type' => 'MPAI_Agent_Orchestrator', 'description' => 'The agent orchestrator instance.']
            ],
            '1.7.0',
            'agents'
        );
        
        self::register_filter(
            'MPAI_HOOK_FILTER_agent_validation',
            'Filters agent validation results',
            true,
            [
                'is_valid' => ['type' => 'bool', 'description' => 'Whether the agent is valid.'],
                'agent_id' => ['type' => 'string', 'description' => 'The ID of the agent.'],
                'agent' => ['type' => 'object', 'description' => 'The agent instance.'],
                'orchestrator' => ['type' => 'MPAI_Agent_Orchestrator', 'description' => 'The agent orchestrator instance.']
            ],
            '1.7.0',
            'agents'
        );
        
        self::register_filter(
            'MPAI_HOOK_FILTER_agent_scoring',
            'Filters confidence scores for agent selection',
            [],
            [
                'scores' => ['type' => 'array', 'description' => 'The agent confidence scores.'],
                'message' => ['type' => 'string', 'description' => 'The user message.'],
                'context' => ['type' => 'array', 'description' => 'The user context.'],
                'orchestrator' => ['type' => 'MPAI_Agent_Orchestrator', 'description' => 'The agent orchestrator instance.']
            ],
            '1.7.0',
            'agents'
        );
        
        self::register_filter(
            'MPAI_HOOK_FILTER_agent_handoff',
            'Filters agent handoff behavior',
            '',
            [
                'selected_agent_id' => ['type' => 'string', 'description' => 'The ID of the selected agent.'],
                'agent_scores' => ['type' => 'array', 'description' => 'The agent confidence scores.'],
                'message' => ['type' => 'string', 'description' => 'The user message.'],
                'orchestrator' => ['type' => 'MPAI_Agent_Orchestrator', 'description' => 'The agent orchestrator instance.']
            ],
            '1.7.0',
            'agents'
        );
    }
    
    /**
     * Register tool execution hooks
     */
    private static function register_tool_hooks() {
        // Tool Registry Hooks
        self::register_hook(
            'MPAI_HOOK_ACTION_tool_registry_init',
            'Fires after tool registry initialization.',
            ['registry' => ['type' => 'MPAI_Tool_Registry', 'description' => 'The tool registry instance.']],
            '1.7.0',
            'tools'
        );
        
        self::register_hook(
            'MPAI_HOOK_ACTION_register_tool',
            'Fires when a tool is registered to the system.',
            [
                'tool_id' => ['type' => 'string', 'description' => 'The ID of the registered tool.'],
                'tool' => ['type' => 'object', 'description' => 'The tool instance.'],
                'registry' => ['type' => 'MPAI_Tool_Registry', 'description' => 'The tool registry instance.']
            ],
            '1.7.0',
            'tools'
        );
        
        // Tool Execution Hooks
        self::register_hook(
            'MPAI_HOOK_ACTION_before_tool_execution',
            'Fires before any tool is executed with tool name and parameters.',
            [
                'tool_name' => ['type' => 'string', 'description' => 'The name of the tool being executed.'],
                'parameters' => ['type' => 'array', 'description' => 'The parameters for the tool execution.'],
                'tool' => ['type' => 'MPAI_Base_Tool', 'description' => 'The tool instance.']
            ],
            '1.7.0',
            'tools'
        );
        
        self::register_hook(
            'MPAI_HOOK_ACTION_after_tool_execution',
            'Fires after tool execution with tool name, parameters, and result.',
            [
                'tool_name' => ['type' => 'string', 'description' => 'The name of the tool that was executed.'],
                'parameters' => ['type' => 'array', 'description' => 'The parameters used for the tool execution.'],
                'result' => ['type' => 'mixed', 'description' => 'The result of the tool execution.'],
                'tool' => ['type' => 'MPAI_Base_Tool', 'description' => 'The tool instance.']
            ],
            '1.7.0',
            'tools'
        );
        
        self::register_filter(
            'MPAI_HOOK_FILTER_tool_parameters',
            'Filters tool parameters before execution.',
            [],
            [
                'parameters' => ['type' => 'array', 'description' => 'The parameters to filter.'],
                'tool_name' => ['type' => 'string', 'description' => 'The name of the tool.'],
                'tool' => ['type' => 'MPAI_Base_Tool', 'description' => 'The tool instance.']
            ],
            '1.7.0',
            'tools'
        );
        
        self::register_filter(
            'MPAI_HOOK_FILTER_tool_execution_result',
            'Filters tool execution result.',
            null,
            [
                'result' => ['type' => 'mixed', 'description' => 'The result to filter.'],
                'tool_name' => ['type' => 'string', 'description' => 'The name of the tool.'],
                'parameters' => ['type' => 'array', 'description' => 'The parameters used for execution.'],
                'tool' => ['type' => 'MPAI_Base_Tool', 'description' => 'The tool instance.']
            ],
            '1.7.0',
            'tools'
        );
        
        self::register_filter(
            'MPAI_HOOK_FILTER_available_tools',
            'Filters the list of available tools.',
            [],
            [
                'tools' => ['type' => 'array', 'description' => 'The tools array.'],
                'registry' => ['type' => 'MPAI_Tool_Registry', 'description' => 'The tool registry instance.']
            ],
            '1.7.0',
            'tools'
        );
        
        self::register_filter(
            'MPAI_HOOK_FILTER_tool_capability_check',
            'Filters whether a user has capability to use a specific tool.',
            true,
            [
                'can_use' => ['type' => 'bool', 'description' => 'Whether the user can use the tool.'],
                'tool_name' => ['type' => 'string', 'description' => 'The name of the tool.'],
                'parameters' => ['type' => 'array', 'description' => 'The parameters for the tool execution.'],
                'tool' => ['type' => 'MPAI_Base_Tool', 'description' => 'The tool instance.']
            ],
            '1.7.0',
            'tools'
        );
    }
    
    /**
     * Register agent system hooks
     */
    private static function register_agent_hooks() {
        // Agent Registration Hooks
        self::register_hook(
            'MPAI_HOOK_ACTION_register_agent',
            'Fires when an agent is registered to the system.',
            [
                'agent_id' => ['type' => 'string', 'description' => 'The ID of the registered agent.'],
                'agent_instance' => ['type' => 'object', 'description' => 'The agent instance.'],
                'orchestrator' => ['type' => 'MPAI_Agent_Orchestrator', 'description' => 'The agent orchestrator instance.']
            ],
            '1.7.0',
            'agents'
        );
        
        // Agent Processing Hooks
        self::register_hook(
            'MPAI_HOOK_ACTION_before_agent_process',
            'Fires before agent processes a request.',
            [
                'agent_id' => ['type' => 'string', 'description' => 'The ID of the agent.'],
                'params' => ['type' => 'array', 'description' => 'The parameters for the agent.'],
                'user_id' => ['type' => 'int', 'description' => 'The user ID.'],
                'context' => ['type' => 'array', 'description' => 'The user context.'],
                'orchestrator' => ['type' => 'MPAI_Agent_Orchestrator', 'description' => 'The agent orchestrator instance.']
            ],
            '1.7.0',
            'agents'
        );
        
        self::register_hook(
            'MPAI_HOOK_ACTION_after_agent_process',
            'Fires after agent processes a request.',
            [
                'agent_id' => ['type' => 'string', 'description' => 'The ID of the agent.'],
                'params' => ['type' => 'array', 'description' => 'The parameters for the agent.'],
                'user_id' => ['type' => 'int', 'description' => 'The user ID.'],
                'result' => ['type' => 'array', 'description' => 'The result of the agent processing.'],
                'orchestrator' => ['type' => 'MPAI_Agent_Orchestrator', 'description' => 'The agent orchestrator instance.']
            ],
            '1.7.0',
            'agents'
        );
        
        // Agent Capability and Selection Hooks
        self::register_filter(
            'MPAI_HOOK_FILTER_agent_capabilities',
            'Filters agent capabilities.',
            [],
            [
                'capabilities' => ['type' => 'array', 'description' => 'The agent capabilities.'],
                'agent_id' => ['type' => 'string', 'description' => 'The ID of the agent.'],
                'agent' => ['type' => 'object', 'description' => 'The agent instance.'],
                'orchestrator' => ['type' => 'MPAI_Agent_Orchestrator', 'description' => 'The agent orchestrator instance.']
            ],
            '1.7.0',
            'agents'
        );
        
        self::register_filter(
            'MPAI_HOOK_FILTER_agent_validation',
            'Filters agent validation results.',
            true,
            [
                'is_valid' => ['type' => 'bool', 'description' => 'Whether the agent is valid.'],
                'agent_id' => ['type' => 'string', 'description' => 'The ID of the agent.'],
                'agent' => ['type' => 'object', 'description' => 'The agent instance.'],
                'orchestrator' => ['type' => 'MPAI_Agent_Orchestrator', 'description' => 'The agent orchestrator instance.']
            ],
            '1.7.0',
            'agents'
        );
        
        self::register_filter(
            'MPAI_HOOK_FILTER_agent_scoring',
            'Filters confidence scores for agent selection.',
            [],
            [
                'scores' => ['type' => 'array', 'description' => 'The agent confidence scores.'],
                'message' => ['type' => 'string', 'description' => 'The user message.'],
                'context' => ['type' => 'array', 'description' => 'The user context.'],
                'orchestrator' => ['type' => 'MPAI_Agent_Orchestrator', 'description' => 'The agent orchestrator instance.']
            ],
            '1.7.0',
            'agents'
        );
        
        self::register_filter(
            'MPAI_HOOK_FILTER_agent_handoff',
            'Filters agent handoff behavior.',
            '',
            [
                'selected_agent_id' => ['type' => 'string', 'description' => 'The ID of the selected agent.'],
                'agent_scores' => ['type' => 'array', 'description' => 'The agent confidence scores.'],
                'message' => ['type' => 'string', 'description' => 'The user message.'],
                'orchestrator' => ['type' => 'MPAI_Agent_Orchestrator', 'description' => 'The agent orchestrator instance.']
            ],
            '1.7.0',
            'agents'
        );
    }

    /**
     * Register a hook with documentation
     *
     * @param string $hook_name The name of the hook
     * @param string $description Description of the hook
     * @param array $parameters Parameters passed to the hook
     * @param string $since Version since hook was added
     * @param string $category Category of the hook (core, chat, history, etc.)
     */
    public static function register_hook($hook_name, $description, $parameters = [], $since = '1.7.0', $category = 'core') {
        // Store hook documentation for developer reference
        // This doesn't affect functionality but helps with auto-documentation
        self::$hooks[$hook_name] = [
            'description' => $description,
            'parameters' => $parameters,
            'since' => $since,
            'category' => $category,
            'type' => 'action'
        ];
        
        // Log hook registration if debugging is enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            mpai_log_debug("Registered hook: {$hook_name}", 'hooks');
        }
    }
    
    /**
     * Register a filter with documentation
     *
     * @param string $filter_name The name of the filter
     * @param string $description Description of the filter
     * @param mixed $default_value Default value if no callbacks are registered
     * @param array $parameters Parameters passed to the filter
     * @param string $since Version since filter was added
     * @param string $category Category of the filter (core, chat, history, etc.)
     */
    public static function register_filter($filter_name, $description, $default_value = null, $parameters = [], $since = '1.7.0', $category = 'core') {
        // Store filter documentation for developer reference
        self::$filters[$filter_name] = [
            'description' => $description,
            'default_value' => $default_value,
            'parameters' => $parameters,
            'since' => $since,
            'category' => $category,
            'type' => 'filter'
        ];
        
        // Log filter registration if debugging is enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            mpai_log_debug("Registered filter: {$filter_name}", 'hooks');
        }
    }
    
    /**
     * Get all registered hooks and filters
     *
     * @return array All registered hooks and filters with documentation
     */
    public static function get_all() {
        return [
            'hooks' => self::$hooks,
            'filters' => self::$filters
        ];
    }
    
    /**
     * Get hooks by category
     *
     * @param string $category The category to filter by
     * @return array Hooks in the specified category
     */
    public static function get_hooks_by_category($category) {
        return array_filter(self::$hooks, function($hook) use ($category) {
            return $hook['category'] === $category;
        });
    }
    
    /**
     * Get filters by category
     *
     * @param string $category The category to filter by
     * @return array Filters in the specified category
     */
    public static function get_filters_by_category($category) {
        return array_filter(self::$filters, function($filter) use ($category) {
            return $filter['category'] === $category;
        });
    }
    
    /**
     * Generate documentation for all hooks and filters
     *
     * @return string Markdown formatted documentation
     */
    public static function generate_documentation() {
        $doc = "# MemberPress AI Assistant: Hook and Filter Reference\n\n";
        $doc .= "This document provides a comprehensive reference for all hooks and filters available in the MemberPress AI Assistant plugin.\n\n";
        
        // Group hooks and filters by category
        $categories = [];
        
        // Process hooks
        foreach (self::$hooks as $name => $hook) {
            $category = $hook['category'];
            if (!isset($categories[$category])) {
                $categories[$category] = [
                    'hooks' => [],
                    'filters' => []
                ];
            }
            $categories[$category]['hooks'][$name] = $hook;
        }
        
        // Process filters
        foreach (self::$filters as $name => $filter) {
            $category = $filter['category'];
            if (!isset($categories[$category])) {
                $categories[$category] = [
                    'hooks' => [],
                    'filters' => []
                ];
            }
            $categories[$category]['filters'][$name] = $filter;
        }
        
        // Generate documentation for each category
        foreach ($categories as $category => $data) {
            $doc .= "## " . ucfirst($category) . " Hooks\n\n";
            
            if (!empty($data['hooks'])) {
                $doc .= "### Actions\n\n";
                
                foreach ($data['hooks'] as $name => $hook) {
                    $doc .= "- `{$name}`: {$hook['description']}\n";
                    $doc .= "  - Since: {$hook['since']}\n";
                    
                    if (!empty($hook['parameters'])) {
                        $doc .= "  - Parameters:\n";
                        foreach ($hook['parameters'] as $param_name => $param_desc) {
                            $doc .= "    - `\${$param_name}` ({$param_desc['type']}): {$param_desc['description']}\n";
                        }
                    } else {
                        $doc .= "  - Parameters: None\n";
                    }
                    
                    $doc .= "\n";
                }
            }
            
            if (!empty($data['filters'])) {
                $doc .= "### Filters\n\n";
                
                foreach ($data['filters'] as $name => $filter) {
                    $doc .= "- `{$name}`: {$filter['description']}\n";
                    $doc .= "  - Since: {$filter['since']}\n";
                    
                    if (!empty($filter['parameters'])) {
                        $doc .= "  - Parameters:\n";
                        foreach ($filter['parameters'] as $param_name => $param_desc) {
                            $doc .= "    - `\${$param_name}` ({$param_desc['type']}): {$param_desc['description']}\n";
                        }
                    } else {
                        $doc .= "  - Parameters: None\n";
                    }
                    
                    if (isset($filter['default_value'])) {
                        if (is_array($filter['default_value'])) {
                            $doc .= "  - Default: Array of default values\n";
                        } else {
                            $doc .= "  - Default: `" . var_export($filter['default_value'], true) . "`\n";
                        }
                    }
                    
                    $doc .= "\n";
                }
            }
        }
        
        // Add example usage section
        $doc .= "## Example Usage\n\n";
        
        $doc .= "### Adding a Custom System Prompt Prefix\n\n";
        $doc .= "```php\n";
        $doc .= "function my_custom_system_prompt(\$system_prompt) {\n";
        $doc .= "    return \"CUSTOM PREFIX: \" . \$system_prompt;\n";
        $doc .= "}\n";
        $doc .= "add_filter('mpai_system_prompt', 'my_custom_system_prompt');\n";
        $doc .= "```\n\n";
        
        $doc .= "### Logging All User Messages\n\n";
        $doc .= "```php\n";
        $doc .= "function log_user_messages(\$message) {\n";
        $doc .= "    error_log('MemberPress AI: User asked: ' . \$message);\n";
        $doc .= "    return \$message;\n";
        $doc .= "}\n";
        $doc .= "add_filter('mpai_message_content', 'log_user_messages');\n";
        $doc .= "```\n\n";
        
        $doc .= "### Extending History Retention\n\n";
        $doc .= "```php\n";
        $doc .= "function extend_history_retention(\$days) {\n";
        $doc .= "    return 60; // Keep history for 60 days instead of default 30\n";
        $doc .= "}\n";
        $doc .= "add_filter('mpai_history_retention', 'extend_history_retention');\n";
        $doc .= "```\n";
        
        return $doc;
    }
}