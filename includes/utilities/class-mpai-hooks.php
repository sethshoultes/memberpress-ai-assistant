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