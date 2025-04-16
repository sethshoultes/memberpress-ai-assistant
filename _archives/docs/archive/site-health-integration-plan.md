# [ARCHIVED] WordPress Site Health Integration Plan

> **Archive Notice**: This document is archived as the integration plan has been fully implemented in version 1.5.5 (2025-03-31). Please refer to the current implementation documentation in [site-health-implementation-example.md](../site-health-implementation-example.md) for up-to-date information.

## Overview

This document outlines a plan to integrate WordPress Site Health API into the MemberPress AI Assistant diagnostic system. The Site Health API provides comprehensive system information in a standardized way, which will allow us to:

1. Reduce custom code by leveraging WordPress's built-in diagnostic tools
2. Provide more complete system information to the AI
3. Improve compatibility across WordPress versions
4. Ensure diagnostic data is properly formatted and categorized

## Current Diagnostic System

Currently, our diagnostic system consists of:

1. `/includes/tools/implementations/class-mpai-diagnostic-tool.php` - Main diagnostic tool implementation
2. `/includes/diagnostic-page.php` - Standalone diagnostic interface
3. `/includes/debug-info.php` - Debug information page
4. Custom database queries in `MPAI_MemberPress_API` class

## Integration Plan

### 1. Create a Site Health Wrapper Class

Create a new class that will handle interactions with the WordPress Site Health API:

```php
class MPAI_Site_Health {
    /**
     * Get all site health information
     *
     * @return array Complete site health data
     */
    public function get_all_debug_data() {
        if (!function_exists('wp_get_debug_data')) {
            // Fallback if the function doesn't exist
            return $this->get_fallback_debug_data();
        }
        
        return wp_get_debug_data();
    }
    
    /**
     * Get specific site health information by section
     *
     * @param string $section Section name to retrieve
     * @return array Section data
     */
    public function get_section($section) {
        $all_data = $this->get_all_debug_data();
        
        if (isset($all_data[$section])) {
            return $all_data[$section];
        }
        
        return array();
    }
    
    /**
     * Get WordPress information
     *
     * @return array WordPress information
     */
    public function get_wordpress_info() {
        return $this->get_section('wp-core');
    }
    
    /**
     * Get server information
     *
     * @return array Server information
     */
    public function get_server_info() {
        return $this->get_section('server');
    }
    
    /**
     * Get database information
     *
     * @return array Database information
     */
    public function get_database_info() {
        return $this->get_section('db');
    }
    
    /**
     * Get plugin information
     *
     * @return array Plugin information
     */
    public function get_plugin_info() {
        return $this->get_section('plugins');
    }
    
    /**
     * Get theme information
     *
     * @return array Theme information
     */
    public function get_theme_info() {
        return $this->get_section('themes');
    }
    
    /**
     * Fallback method if Site Health API is not available
     *
     * @return array Basic system information
     */
    private function get_fallback_debug_data() {
        global $wpdb;
        
        // Recreate the most important parts of the debug data
        $debug_data = array(
            'wp-core' => array(
                'version' => array(
                    'label' => 'WordPress Version',
                    'value' => get_bloginfo('version'),
                ),
                'site_url' => array(
                    'label' => 'Site URL',
                    'value' => site_url(),
                ),
                'home_url' => array(
                    'label' => 'Home URL',
                    'value' => home_url(),
                ),
                'multisite' => array(
                    'label' => 'Multisite',
                    'value' => is_multisite() ? 'Yes' : 'No',
                ),
            ),
            'server' => array(
                'php_version' => array(
                    'label' => 'PHP Version',
                    'value' => phpversion(),
                ),
                'mysql_version' => array(
                    'label' => 'MySQL Version',
                    'value' => $wpdb->db_version(),
                ),
                'server_software' => array(
                    'label' => 'Server Software',
                    'value' => $_SERVER['SERVER_SOFTWARE'],
                ),
            ),
            'db' => array(
                'table_prefix' => array(
                    'label' => 'Table Prefix',
                    'value' => $wpdb->prefix,
                ),
                'database_size' => array(
                    'label' => 'Database Size',
                    'value' => $this->get_database_size(),
                ),
            ),
        );
        
        return $debug_data;
    }
    
    /**
     * Get the database size
     *
     * @return string Database size
     */
    private function get_database_size() {
        global $wpdb;
        
        $size = 0;
        $tables = $wpdb->get_results('SHOW TABLE STATUS', ARRAY_A);
        
        if ($tables) {
            foreach ($tables as $table) {
                $size += $table['Data_length'] + $table['Index_length'];
            }
        }
        
        // Format the size
        if ($size < 1024) {
            return $size . ' B';
        } elseif ($size < 1024 * 1024) {
            return round($size / 1024, 2) . ' KB';
        } elseif ($size < 1024 * 1024 * 1024) {
            return round($size / (1024 * 1024), 2) . ' MB';
        } else {
            return round($size / (1024 * 1024 * 1024), 2) . ' GB';
        }
    }
}
```

### 2. Update the Diagnostic Tool Class

Update the `MPAI_Diagnostic_Tool` class to use the Site Health wrapper:

```php
class MPAI_Diagnostic_Tool extends MPAI_Base_Tool {
    /**
     * Site Health instance
     */
    private $site_health;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->name = 'diagnostic';
        $this->description = 'Run various diagnostic tests and status checks for the MemberPress AI Assistant';
        
        // Initialize Site Health wrapper
        $this->site_health = new MPAI_Site_Health();
    }
    
    /**
     * Get WordPress information
     *
     * @return array WordPress information
     */
    public function get_wordpress_info() {
        // Get the data from Site Health API
        $wp_info = $this->site_health->get_wordpress_info();
        $server_info = $this->site_health->get_server_info();
        $db_info = $this->site_health->get_database_info();
        
        // Format it for our existing output structure
        $result = array(
            'success' => true,
            'wordpress' => array(),
            'php' => array(),
            'database' => array(),
            'server' => array(),
        );
        
        // Map WP info
        foreach ($wp_info as $key => $item) {
            $result['wordpress'][$key] = $item['value'];
        }
        
        // Map PHP/server info
        foreach ($server_info as $key => $item) {
            if (strpos($key, 'php_') === 0) {
                $result['php'][str_replace('php_', '', $key)] = $item['value'];
            } else {
                $result['server'][$key] = $item['value'];
            }
        }
        
        // Map database info
        foreach ($db_info as $key => $item) {
            $result['database'][$key] = $item['value'];
        }
        
        return $result;
    }
    
    // ... other methods remain the same
}
```

### 3. Update the Diagnostic Page

Update `/includes/diagnostic-page.php` to use the new Site Health data:

```php
// Add a new test for comprehensive Site Health data
if ($test_type === 'site_health') {
    // Create site health instance
    require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-site-health.php';
    $site_health = new MPAI_Site_Health();
    
    // Get the data
    $result = array(
        'success' => true,
        'data' => $site_health->get_all_debug_data()
    );
}
```

### 4. Create a MemberPress Integration with Site Health

Enhance the `MPAI_MemberPress_API` class to incorporate Site Health data:

```php
/**
 * Get MemberPress data with system information
 *
 * @param bool $include_system_info Whether to include system info
 * @return array MemberPress data with system info
 */
public function get_data_with_system_info($include_system_info = true) {
    // Get the MemberPress data
    $mp_data = $this->get_data_summary();
    
    // If we don't want system info, return just the MP data
    if (!$include_system_info) {
        return $mp_data;
    }
    
    // Get system info
    require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-site-health.php';
    $site_health = new MPAI_Site_Health();
    $system_info = array(
        'wordpress' => $site_health->get_wordpress_info(),
        'server' => $site_health->get_server_info(),
        'database' => $site_health->get_database_info(),
        'plugins' => $site_health->get_plugin_info(),
    );
    
    // Add system info to the response
    $mp_data['system_info'] = $system_info;
    
    return $mp_data;
}
```

### 5. Update AI Tool Definitions

Update the memberpress_info tool definition in the Context Manager:

```php
// Add a new parameter for including system info
$memberpress_info_tool = array(
    'name' => 'memberpress_info',
    'description' => 'Get information about MemberPress data and system',
    'parameters' => array(
        'type' => 'object',
        'properties' => array(
            'type' => array(
                'type' => 'string',
                'enum' => array('members', 'memberships', 'transactions', 'subscriptions', 'new_members_this_month', 'system_info', 'all'),
                'description' => 'The type of MemberPress information to retrieve'
            ),
            'include_system_info' => array(
                'type' => 'boolean',
                'description' => 'Whether to include system information in the response',
                'default' => false
            ),
        ),
        'required' => array('type')
    )
);
```

### 6. Update the Execute Method for MemberPress Info Tool

```php
/**
 * Execute the MemberPress Info tool
 *
 * @param string $type The type of information to retrieve
 * @param bool $include_system_info Whether to include system information
 * @return array Tool execution result
 */
public function execute_memberpress_info($type, $include_system_info = false) {
    // Initialize MemberPress API
    $memberpress_api = new MPAI_MemberPress_API();
    
    // Handle system info request
    if ($type === 'system_info') {
        require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-site-health.php';
        $site_health = new MPAI_Site_Health();
        
        return array(
            'success' => true,
            'result' => $site_health->get_all_debug_data()
        );
    }
    
    // Handle the 'all' type with system info
    if ($type === 'all' && $include_system_info) {
        return array(
            'success' => true,
            'result' => $memberpress_api->get_data_with_system_info(true)
        );
    }
    
    // For other types, use existing logic...
}
```

## Implementation Steps

1. Create `/includes/class-mpai-site-health.php` file with the Site Health wrapper class
2. Update `/includes/tools/implementations/class-mpai-diagnostic-tool.php` to use the new wrapper
3. Update `/includes/diagnostic-page.php` to add a site health test option
4. Update the MemberPress API integration class
5. Update the context manager to support system info in tool definitions
6. Add documentation for the new functionality

## AI Agent Prompt Update

Update the system prompt for the MemberPress Agent to include information about accessing system data:

```
You can now access system information using the memberpress_info tool with type=system_info. This provides comprehensive details about the WordPress installation, server environment, database, and plugins. Use this for troubleshooting and when the user asks about their WordPress installation.

For example:
- When troubleshooting, use `{ "tool": "memberpress_info", "parameters": { "type": "system_info" }}` to get system details
- When providing a complete overview, use `{ "tool": "memberpress_info", "parameters": { "type": "all", "include_system_info": true }}` to get both MemberPress data and system information
```

## Benefits

1. **Reduced Code Duplication**: Leverages WordPress's built-in diagnostic capabilities
2. **Standardized Data Format**: Uses WordPress's standardized data format
3. **Future-Proof**: Updates automatically as WordPress's Site Health features evolve
4. **Comprehensive Data**: Provides more detailed information than our custom implementation
5. **Improved AI Context**: Gives the AI more context for troubleshooting and recommendations

## Testing Plan

1. Test in environments with WordPress 5.2+ (which includes Site Health)
2. Test fallback functionality in environments with older WordPress versions
3. Verify the AI can properly access and utilize the system information
4. Test integration with the diagnostic page and tools