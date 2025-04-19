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
        self::register_tool_hooks();
        self::register_agent_hooks();
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