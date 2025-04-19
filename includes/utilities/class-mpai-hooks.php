<?php
/**
 * Hooks and Filters Registration Utility
 *
 * @package MemberPress AI Assistant
 */

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Hooks and Filters Registration Utility
 * 
 * This class provides a centralized way to register and document hooks and filters
 * used throughout the plugin.
 */
class MPAI_Hooks {
    /**
     * Registered hooks
     *
     * @var array
     */
    private static $registered_hooks = array();

    /**
     * Registered filters
     *
     * @var array
     */
    private static $registered_filters = array();

    /**
     * Register a hook (action)
     *
     * @param string $hook_name Hook name
     * @param string $description Hook description
     * @param array $parameters Parameters description
     * @param string $since Version since hook was added
     * @param string $category Hook category
     * @return bool Success status
     */
    public static function register_hook($hook_name, $description, $parameters = array(), $since = '1.0.0', $category = 'general') {
        // Validate hook name
        if (empty($hook_name)) {
            return false;
        }

        // Store hook information
        self::$registered_hooks[$hook_name] = array(
            'description' => $description,
            'parameters' => $parameters,
            'since' => $since,
            'category' => $category
        );

        return true;
    }

    /**
     * Register a filter
     *
     * @param string $filter_name Filter name
     * @param string $description Filter description
     * @param mixed $value Default value
     * @param array $parameters Parameters description
     * @param string $since Version since filter was added
     * @param string $category Filter category
     * @return bool Success status
     */
    public static function register_filter($filter_name, $description, $value = null, $parameters = array(), $since = '1.0.0', $category = 'general') {
        // Validate filter name
        if (empty($filter_name)) {
            return false;
        }

        // Store filter information
        self::$registered_filters[$filter_name] = array(
            'description' => $description,
            'default_value' => $value,
            'parameters' => $parameters,
            'since' => $since,
            'category' => $category
        );

        return true;
    }

    /**
     * Get all registered hooks
     *
     * @return array Registered hooks
     */
    public static function get_registered_hooks() {
        return self::$registered_hooks;
    }

    /**
     * Get all registered filters
     *
     * @return array Registered filters
     */
    public static function get_registered_filters() {
        return self::$registered_filters;
    }

    /**
     * Get hook information
     *
     * @param string $hook_name Hook name
     * @return array|null Hook information or null if not found
     */
    public static function get_hook_info($hook_name) {
        return isset(self::$registered_hooks[$hook_name]) ? self::$registered_hooks[$hook_name] : null;
    }

    /**
     * Get filter information
     *
     * @param string $filter_name Filter name
     * @return array|null Filter information or null if not found
     */
    public static function get_filter_info($filter_name) {
        return isset(self::$registered_filters[$filter_name]) ? self::$registered_filters[$filter_name] : null;
    }

    /**
     * Get hooks by category
     *
     * @param string $category Category name
     * @return array Hooks in the specified category
     */
    public static function get_hooks_by_category($category) {
        $hooks = array();

        foreach (self::$registered_hooks as $hook_name => $hook_info) {
            if ($hook_info['category'] === $category) {
                $hooks[$hook_name] = $hook_info;
            }
        }

        return $hooks;
    }

    /**
     * Get filters by category
     *
     * @param string $category Category name
     * @return array Filters in the specified category
     */
    public static function get_filters_by_category($category) {
        $filters = array();

        foreach (self::$registered_filters as $filter_name => $filter_info) {
            if ($filter_info['category'] === $category) {
                $filters[$filter_name] = $filter_info;
            }
        }

        return $filters;
    }

    /**
     * Generate documentation for all hooks and filters
     *
     * @return string Documentation in Markdown format
     */
    public static function generate_documentation() {
        $doc = "# MemberPress AI Assistant Hooks and Filters\n\n";
        
        // Add hooks documentation
        $doc .= "## Actions\n\n";
        
        $categories = array();
        foreach (self::$registered_hooks as $hook_name => $hook_info) {
            $category = $hook_info['category'];
            if (!isset($categories[$category])) {
                $categories[$category] = array();
            }
            $categories[$category][$hook_name] = $hook_info;
        }
        
        ksort($categories);
        
        foreach ($categories as $category => $hooks) {
            $doc .= "### {$category}\n\n";
            
            ksort($hooks);
            
            foreach ($hooks as $hook_name => $hook_info) {
                $doc .= "#### `{$hook_name}`\n\n";
                $doc .= "**Description:** {$hook_info['description']}\n\n";
                $doc .= "**Since:** {$hook_info['since']}\n\n";
                
                if (!empty($hook_info['parameters'])) {
                    $doc .= "**Parameters:**\n\n";
                    
                    foreach ($hook_info['parameters'] as $param_name => $param_info) {
                        $type = isset($param_info['type']) ? $param_info['type'] : 'mixed';
                        $desc = isset($param_info['description']) ? $param_info['description'] : '';
                        
                        $doc .= "- `\${$param_name}` ({$type}) {$desc}\n";
                    }
                    
                    $doc .= "\n";
                }
                
                $doc .= "**Example:**\n\n";
                $doc .= "```php\n";
                $doc .= "add_action('{$hook_name}', function(";
                
                $params = array();
                foreach (array_keys($hook_info['parameters']) as $param_name) {
                    $params[] = "\${$param_name}";
                }
                
                $doc .= implode(', ', $params);
                $doc .= ") {\n";
                $doc .= "    // Your code here\n";
                $doc .= "});\n";
                $doc .= "```\n\n";
            }
        }
        
        // Add filters documentation
        $doc .= "## Filters\n\n";
        
        $categories = array();
        foreach (self::$registered_filters as $filter_name => $filter_info) {
            $category = $filter_info['category'];
            if (!isset($categories[$category])) {
                $categories[$category] = array();
            }
            $categories[$category][$filter_name] = $filter_info;
        }
        
        ksort($categories);
        
        foreach ($categories as $category => $filters) {
            $doc .= "### {$category}\n\n";
            
            ksort($filters);
            
            foreach ($filters as $filter_name => $filter_info) {
                $doc .= "#### `{$filter_name}`\n\n";
                $doc .= "**Description:** {$filter_info['description']}\n\n";
                $doc .= "**Since:** {$filter_info['since']}\n\n";
                
                $default_value = $filter_info['default_value'];
                if (is_array($default_value)) {
                    $default_value = 'array()';
                } elseif (is_object($default_value)) {
                    $default_value = get_class($default_value) . ' object';
                } elseif (is_bool($default_value)) {
                    $default_value = $default_value ? 'true' : 'false';
                } elseif (is_null($default_value)) {
                    $default_value = 'null';
                } elseif (is_string($default_value)) {
                    $default_value = "'{$default_value}'";
                }
                
                $doc .= "**Default Value:** {$default_value}\n\n";
                
                if (!empty($filter_info['parameters'])) {
                    $doc .= "**Parameters:**\n\n";
                    
                    foreach ($filter_info['parameters'] as $param_name => $param_info) {
                        $type = isset($param_info['type']) ? $param_info['type'] : 'mixed';
                        $desc = isset($param_info['description']) ? $param_info['description'] : '';
                        
                        $doc .= "- `\${$param_name}` ({$type}) {$desc}\n";
                    }
                    
                    $doc .= "\n";
                }
                
                $doc .= "**Example:**\n\n";
                $doc .= "```php\n";
                $doc .= "add_filter('{$filter_name}', function(";
                
                $params = array();
                foreach (array_keys($filter_info['parameters']) as $param_name) {
                    $params[] = "\${$param_name}";
                }
                
                $doc .= implode(', ', $params);
                $doc .= ") {\n";
                $doc .= "    // Modify the value\n";
                $doc .= "    return \${$params[0]};\n";
                $doc .= "});\n";
                $doc .= "```\n\n";
            }
        }
        
        return $doc;
    }
}