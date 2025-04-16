# WordPress Site Health Integration Example

This document provides example code for implementing the Site Health integration into the MemberPress AI Assistant.

## File: includes/class-mpai-site-health.php

```php
<?php
/**
 * MemberPress AI Assistant Site Health Integration
 *
 * @package MemberPress AI Assistant
 */

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MPAI_Site_Health
 * 
 * Provides wrapper functions for WordPress Site Health API
 */
class MPAI_Site_Health {
    /**
     * Get all debug data from WordPress Site Health
     * 
     * @return array All site health debug data
     */
    public function get_all_debug_data() {
        // Check if WordPress Site Health function exists (WP 5.2+)
        if (function_exists('wp_get_debug_data')) {
            return wp_get_debug_data();
        }
        
        // Fallback for older WordPress versions
        return $this->get_fallback_debug_data();
    }
    
    /**
     * Get a specific section of debug data
     * 
     * @param string $section The section key to retrieve
     * @return array The section data
     */
    public function get_section($section) {
        $all_data = $this->get_all_debug_data();
        
        if (isset($all_data[$section])) {
            return $all_data[$section];
        }
        
        return array();
    }
    
    /**
     * Get WordPress core information
     * 
     * @return array WordPress core information
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
     * Get active plugins information
     * 
     * @return array Plugins information
     */
    public function get_plugins_info() {
        return $this->get_section('plugins-active');
    }
    
    /**
     * Get installed theme information
     * 
     * @return array Theme information
     */
    public function get_theme_info() {
        return $this->get_section('theme');
    }
    
    /**
     * Get MemberPress-specific debug information
     * 
     * @return array MemberPress debug information
     */
    public function get_memberpress_info() {
        $info = array();
        
        // Check if MemberPress is active
        if (class_exists('MeprAppCtrl')) {
            $info['version'] = array(
                'label' => 'MemberPress Version',
                'value' => defined('MEPR_VERSION') ? MEPR_VERSION : 'Unknown'
            );
            
            $info['license_active'] = array(
                'label' => 'License Active',
                'value' => class_exists('MeprUpdateCtrl') && method_exists('MeprUpdateCtrl', 'is_activated') ? 
                        (MeprUpdateCtrl::is_activated() ? 'Yes' : 'No') : 'Unknown'
            );
            
            // Add MemberPress tables
            global $wpdb;
            $tables = array(
                $wpdb->prefix . 'mepr_transactions',
                $wpdb->prefix . 'mepr_subscriptions',
                $wpdb->prefix . 'mepr_members'
            );
            
            $existing_tables = array();
            foreach ($tables as $table) {
                if ($wpdb->get_var("SHOW TABLES LIKE '$table'") === $table) {
                    $existing_tables[] = $table;
                }
            }
            
            $info['database_tables'] = array(
                'label' => 'MemberPress DB Tables',
                'value' => implode(', ', $existing_tables)
            );
            
            // Add membership counts
            $membership_count = wp_count_posts('memberpressproduct')->publish;
            $info['membership_count'] = array(
                'label' => 'MemberPress Memberships',
                'value' => $membership_count
            );
            
            // Add transaction counts if table exists
            if (in_array($wpdb->prefix . 'mepr_transactions', $existing_tables)) {
                $transaction_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mepr_transactions");
                $info['transaction_count'] = array(
                    'label' => 'MemberPress Transactions',
                    'value' => $transaction_count
                );
            }
            
            // Add subscription counts if table exists
            if (in_array($wpdb->prefix . 'mepr_subscriptions', $existing_tables)) {
                $subscription_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mepr_subscriptions");
                $info['subscription_count'] = array(
                    'label' => 'MemberPress Subscriptions',
                    'value' => $subscription_count
                );
            }
        } else {
            $info['status'] = array(
                'label' => 'MemberPress Status',
                'value' => 'Not Active'
            );
        }
        
        return $info;
    }
    
    /**
     * Get MemberPress AI Assistant debug information
     * 
     * @return array MemberPress AI Assistant debug information
     */
    public function get_mpai_info() {
        $info = array();
        
        $info['version'] = array(
            'label' => 'MPAI Version',
            'value' => defined('MPAI_VERSION') ? MPAI_VERSION : 'Unknown'
        );
        
        // Get API configurations
        $info['openai_api_configured'] = array(
            'label' => 'OpenAI API Configured',
            'value' => !empty(get_option('mpai_api_key', '')) ? 'Yes' : 'No'
        );
        
        $info['anthropic_api_configured'] = array(
            'label' => 'Anthropic API Configured',
            'value' => !empty(get_option('mpai_anthropic_api_key', '')) ? 'Yes' : 'No'
        );
        
        $info['primary_api'] = array(
            'label' => 'Primary API',
            'value' => get_option('mpai_primary_api', 'openai')
        );
        
        // Get model configurations
        $info['openai_model'] = array(
            'label' => 'OpenAI Model',
            'value' => get_option('mpai_model', 'gpt-4o')
        );
        
        $info['anthropic_model'] = array(
            'label' => 'Anthropic Model',
            'value' => get_option('mpai_anthropic_model', 'claude-3-opus-20240229')
        );
        
        // Check database tables
        global $wpdb;
        $tables = array(
            $wpdb->prefix . 'mpai_conversations',
            $wpdb->prefix . 'mpai_messages'
        );
        
        $existing_tables = array();
        foreach ($tables as $table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") === $table) {
                $existing_tables[] = $table;
            }
        }
        
        $info['database_tables'] = array(
            'label' => 'MPAI DB Tables',
            'value' => implode(', ', $existing_tables)
        );
        
        // Count conversations if table exists
        if (in_array($wpdb->prefix . 'mpai_conversations', $existing_tables)) {
            $conversation_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mpai_conversations");
            $info['conversation_count'] = array(
                'label' => 'Saved Conversations',
                'value' => $conversation_count
            );
        }
        
        // Count messages if table exists
        if (in_array($wpdb->prefix . 'mpai_messages', $existing_tables)) {
            $message_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mpai_messages");
            $info['message_count'] = array(
                'label' => 'Saved Messages',
                'value' => $message_count
            );
        }
        
        return $info;
    }
    
    /**
     * Get all site health information including custom sections
     * 
     * @return array Complete site health information
     */
    public function get_complete_info() {
        $debug_data = $this->get_all_debug_data();
        
        // Add our custom sections
        $debug_data['memberpress'] = $this->get_memberpress_info();
        $debug_data['mpai'] = $this->get_mpai_info();
        
        return $debug_data;
    }
    
    /**
     * Fallback method to manually gather debug data
     * 
     * @return array Basic system information
     */
    private function get_fallback_debug_data() {
        global $wpdb;
        
        // Create a fallback for the debug data (simplified version)
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
                'permalink_structure' => array(
                    'label' => 'Permalink Structure',
                    'value' => get_option('permalink_structure') ? get_option('permalink_structure') : 'Plain',
                ),
                'https_status' => array(
                    'label' => 'HTTPS Status',
                    'value' => is_ssl() ? 'HTTPS is enabled' : 'HTTPS is not enabled',
                ),
            ),
            'server' => array(
                'php_version' => array(
                    'label' => 'PHP Version',
                    'value' => phpversion(),
                ),
                'php_memory_limit' => array(
                    'label' => 'PHP Memory Limit',
                    'value' => ini_get('memory_limit'),
                ),
                'php_max_execution_time' => array(
                    'label' => 'PHP Max Execution Time',
                    'value' => ini_get('max_execution_time') . ' seconds',
                ),
                'php_post_max_size' => array(
                    'label' => 'PHP Post Max Size',
                    'value' => ini_get('post_max_size'),
                ),
                'php_max_input_vars' => array(
                    'label' => 'PHP Max Input Vars',
                    'value' => ini_get('max_input_vars'),
                ),
                'mysql_version' => array(
                    'label' => 'MySQL Version',
                    'value' => $wpdb->db_version(),
                ),
                'server_software' => array(
                    'label' => 'Server Software',
                    'value' => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'Unknown',
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
                'database_tables' => array(
                    'label' => 'Database Tables',
                    'value' => $this->get_table_count(),
                ),
            ),
            'plugins-active' => $this->get_active_plugins_info(),
            'theme' => array(
                'theme' => array(
                    'label' => 'Theme',
                    'value' => wp_get_theme()->get('Name') . ' ' . wp_get_theme()->get('Version'),
                ),
                'theme_author' => array(
                    'label' => 'Theme Author',
                    'value' => wp_get_theme()->get('Author'),
                ),
            ),
        );
        
        return $debug_data;
    }
    
    /**
     * Get the database size
     * 
     * @return string Formatted database size
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
    
    /**
     * Get the number of database tables
     * 
     * @return string Number of tables
     */
    private function get_table_count() {
        global $wpdb;
        
        $tables = $wpdb->get_results('SHOW TABLES', ARRAY_N);
        
        return count($tables);
    }
    
    /**
     * Get information about active plugins
     * 
     * @return array Active plugins information
     */
    private function get_active_plugins_info() {
        $active_plugins = array();
        $plugins = get_plugins();
        $active_plugin_slugs = get_option('active_plugins', array());
        
        foreach ($active_plugin_slugs as $plugin_slug) {
            if (isset($plugins[$plugin_slug])) {
                $plugin = $plugins[$plugin_slug];
                $active_plugins[$plugin_slug] = array(
                    'label' => $plugin['Name'],
                    'value' => 'Version ' . $plugin['Version'] . ' by ' . $plugin['Author'],
                );
            }
        }
        
        return $active_plugins;
    }
}
```

## Example Usage in Diagnostic Tool

```php
/**
 * WordPress information test
 */
public function get_wordpress_info() {
    // Create Site Health instance
    require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-site-health.php';
    $site_health = new MPAI_Site_Health();
    
    // Get complete information
    $complete_info = $site_health->get_complete_info();
    
    // Format for our existing API
    $result = array(
        'success' => true,
        'wordpress' => array(),
        'php' => array(),
        'database' => array(),
        'server' => array(),
        'memberpress' => array(),
        'mpai' => array(),
    );
    
    // Map WordPress core info
    foreach ($complete_info['wp-core'] as $key => $item) {
        $result['wordpress'][$key] = $item['value'];
    }
    
    // Map PHP/server info
    foreach ($complete_info['server'] as $key => $item) {
        if (strpos($key, 'php_') === 0) {
            $result['php'][str_replace('php_', '', $key)] = $item['value'];
        } else {
            $result['server'][$key] = $item['value'];
        }
    }
    
    // Map database info
    foreach ($complete_info['db'] as $key => $item) {
        $result['database'][$key] = $item['value'];
    }
    
    // Map MemberPress info
    foreach ($complete_info['memberpress'] as $key => $item) {
        $result['memberpress'][$key] = $item['value'];
    }
    
    // Map MPAI info
    foreach ($complete_info['mpai'] as $key => $item) {
        $result['mpai'][$key] = $item['value'];
    }
    
    return $result;
}
```

## Example Usage in MemberPress Info Tool

```php
/**
 * Execute the MemberPress Info tool with system info
 */
public function execute_memberpress_info($type, $include_system_info = false) {
    // If requesting system info
    if ($type === 'system_info') {
        require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-site-health.php';
        $site_health = new MPAI_Site_Health();
        
        return array(
            'success' => true,
            'message' => 'System information retrieved successfully',
            'data' => $site_health->get_complete_info()
        );
    }
    
    // If requesting all with system info
    if ($type === 'all' && $include_system_info) {
        // Get MemberPress data
        $memberpress_api = new MPAI_MemberPress_API();
        $mp_data = $memberpress_api->get_data_summary();
        
        // Add system info
        require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-site-health.php';
        $site_health = new MPAI_Site_Health();
        $mp_data['system_info'] = $site_health->get_complete_info();
        
        return array(
            'success' => true,
            'message' => 'MemberPress data with system info retrieved successfully',
            'data' => $mp_data
        );
    }
    
    // For other types, use existing logic...
}
```

## Example Output in JSON Format

```json
{
  "wp-core": {
    "version": {
      "label": "WordPress Version",
      "value": "6.2.3"
    },
    "site_url": {
      "label": "Site URL",
      "value": "https://example.com"
    },
    "home_url": {
      "label": "Home URL",
      "value": "https://example.com"
    }
  },
  "server": {
    "php_version": {
      "label": "PHP Version",
      "value": "8.0.27"
    },
    "php_memory_limit": {
      "label": "PHP Memory Limit",
      "value": "256M"
    },
    "mysql_version": {
      "label": "MySQL Version",
      "value": "8.0.31"
    }
  },
  "memberpress": {
    "version": {
      "label": "MemberPress Version",
      "value": "1.11.28"
    },
    "membership_count": {
      "label": "MemberPress Memberships",
      "value": "5"
    },
    "transaction_count": {
      "label": "MemberPress Transactions",
      "value": "247"
    }
  },
  "mpai": {
    "version": {
      "label": "MPAI Version",
      "value": "1.5.4"
    },
    "primary_api": {
      "label": "Primary API",
      "value": "anthropic"
    },
    "conversation_count": {
      "label": "Saved Conversations",
      "value": "32"
    }
  }
}
```