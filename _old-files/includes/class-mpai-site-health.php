<?php
/**
 * MemberPress AI Assistant Site Health Integration
 *
 * This class serves as a wrapper for the WordPress Site Health API,
 * providing standardized access to system diagnostics and health data.
 *
 * @package MemberPress AI Assistant
 * @subpackage Site Health
 */

class MPAI_Site_Health {

    /**
     * Constructor
     */
    public function __construct() {
        // No initialization needed
    }

    /**
     * Get all debug data from WordPress Site Health API
     * 
     * @return array All debug data
     */
    public function get_all_debug_data() {
        // Check if WordPress Site Health API is available (WP 5.2+)
        if (function_exists('wp_get_debug_data')) {
            return wp_get_debug_data();
        } else {
            // Fall back to our custom implementation for older WordPress versions
            return $this->get_fallback_debug_data();
        }
    }

    /**
     * Get WordPress information from Site Health
     * 
     * @return array WordPress information
     */
    public function get_wordpress_info() {
        $debug_data = $this->get_all_debug_data();
        
        if (isset($debug_data['wp-core'])) {
            return $debug_data['wp-core'];
        }
        
        return array();
    }

    /**
     * Get server information from Site Health
     * 
     * @return array Server information
     */
    public function get_server_info() {
        $debug_data = $this->get_all_debug_data();
        
        $server_info = array();
        if (isset($debug_data['wp-server'])) {
            $server_info = array_merge($server_info, $debug_data['wp-server']);
        }
        
        if (isset($debug_data['wp-paths'])) {
            $server_info = array_merge($server_info, $debug_data['wp-paths']);
        }
        
        return $server_info;
    }

    /**
     * Get database information from Site Health
     * 
     * @return array Database information
     */
    public function get_database_info() {
        $debug_data = $this->get_all_debug_data();
        
        if (isset($debug_data['wp-database'])) {
            return $debug_data['wp-database'];
        }
        
        return array();
    }

    /**
     * Get plugin information from Site Health
     * 
     * @return array Plugin information
     */
    public function get_plugins_info() {
        $debug_data = $this->get_all_debug_data();
        
        $plugin_info = array();
        if (isset($debug_data['wp-plugins-active'])) {
            $plugin_info['active'] = $debug_data['wp-plugins-active'];
        }
        
        if (isset($debug_data['wp-plugins-inactive'])) {
            $plugin_info['inactive'] = $debug_data['wp-plugins-inactive'];
        }
        
        if (isset($debug_data['wp-mu-plugins'])) {
            $plugin_info['mu-plugins'] = $debug_data['wp-mu-plugins'];
        }
        
        return $plugin_info;
    }

    /**
     * Get theme information from Site Health
     * 
     * @return array Theme information
     */
    public function get_themes_info() {
        $debug_data = $this->get_all_debug_data();
        
        $theme_info = array();
        if (isset($debug_data['wp-active-theme'])) {
            $theme_info['active'] = $debug_data['wp-active-theme'];
        }
        
        if (isset($debug_data['wp-parent-theme'])) {
            $theme_info['parent'] = $debug_data['wp-parent-theme'];
        }
        
        if (isset($debug_data['wp-themes-inactive'])) {
            $theme_info['inactive'] = $debug_data['wp-themes-inactive'];
        }
        
        return $theme_info;
    }

    /**
     * Get security information from Site Health
     * 
     * @return array Security information
     */
    public function get_security_info() {
        $debug_data = $this->get_all_debug_data();
        
        $security_info = array();
        
        if (isset($debug_data['wp-constants'])) {
            $security_info = $debug_data['wp-constants'];
        }
        
        return $security_info;
    }

    /**
     * Get MemberPress specific information 
     * 
     * @return array MemberPress information
     */
    public function get_memberpress_info() {
        $mp_info = array();
        
        // Check if MemberPress is installed and active
        if (defined('MEPR_VERSION')) {
            $mp_info['version'] = MEPR_VERSION;
            $mp_info['is_active'] = true;
            
            // Get MemberPress options
            if (class_exists('MeprOptions')) {
                $mepr_options = MeprOptions::fetch();
                
                $mp_info['payment_methods'] = array();
                if (!empty($mepr_options->integrations)) {
                    foreach ($mepr_options->integrations as $integration) {
                        if (isset($integration['gateway']) && !empty($integration['gateway'])) {
                            $mp_info['payment_methods'][] = array(
                                'name' => $integration['name'],
                                'gateway' => $integration['gateway'],
                                'is_active' => isset($integration['enabled']) ? (bool)$integration['enabled'] : false
                            );
                        }
                    }
                }
                
                // Get active add-ons
                if (function_exists('get_plugins')) {
                    $all_plugins = get_plugins();
                    $mp_addons = array();
                    
                    foreach ($all_plugins as $plugin_path => $plugin_data) {
                        if (stripos($plugin_path, 'memberpress-') !== false || stripos($plugin_data['Name'], 'MemberPress') !== false) {
                            if ($plugin_path !== 'memberpress/memberpress.php' && $plugin_path !== 'memberpress-ai-assistant/memberpress-ai-assistant.php') {
                                $mp_addons[] = array(
                                    'name' => $plugin_data['Name'],
                                    'version' => $plugin_data['Version'],
                                    'is_active' => is_plugin_active($plugin_path)
                                );
                            }
                        }
                    }
                    
                    $mp_info['addons'] = $mp_addons;
                }
                
                // Get membership count
                if (class_exists('MeprProduct')) {
                    $membership_count = count(MeprProduct::get_all());
                    $mp_info['membership_count'] = $membership_count;
                }
                
                // Get user count with at least one active subscription
                if (class_exists('MeprUser')) {
                    global $wpdb;
                    $mepr_db = new MeprDb();
                    $table = $mepr_db->transactions;
                    
                    $query = "SELECT COUNT(DISTINCT user_id) 
                              FROM {$table} 
                              WHERE status IN ('complete', 'confirmed')";
                    
                    $user_count = $wpdb->get_var($query);
                    $mp_info['active_customer_count'] = (int)$user_count;
                }
            }
        } else {
            $mp_info['is_active'] = false;
        }
        
        return $mp_info;
    }

    /**
     * Get MemberPress AI Assistant specific information
     * 
     * @return array MemberPress AI Assistant information
     */
    public function get_mpai_info() {
        $mpai_info = array();
        
        if (defined('MPAI_VERSION')) {
            $mpai_info['version'] = MPAI_VERSION;
            
            // Get settings
            $settings = get_option('mpai_settings', array());
            
            // Safely remove any API keys from the settings before returning
            $safe_settings = array();
            foreach ($settings as $key => $value) {
                if (strpos($key, 'api_key') !== false || strpos($key, 'password') !== false || strpos($key, 'secret') !== false) {
                    $safe_settings[$key] = 'REDACTED';
                } else {
                    $safe_settings[$key] = $value;
                }
            }
            
            $mpai_info['settings'] = $safe_settings;
            
            // Get diagnostics about the AI provider integration
            $mpai_info['ai_provider'] = array(
                'anthropic_available' => class_exists('MPAI_Anthropic'),
                'openai_available' => class_exists('MPAI_OpenAI'),
                'active_provider' => isset($settings['ai_provider']) ? $settings['ai_provider'] : 'none'
            );
        }
        
        return $mpai_info;
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
        
        // Format data for better display in API response
        $formatted_data = array();
        
        foreach ($debug_data as $section_key => $section) {
            $formatted_section = array();
            
            // Make sure each item is properly formatted with at least a value property
            foreach ($section as $item_key => $item) {
                if (is_array($item) && isset($item['value'])) {
                    // Already properly formatted with value property
                    $formatted_section[$item_key] = $item;
                } else {
                    // Convert simple values to standard format
                    $formatted_section[$item_key] = array(
                        'label' => $this->format_label($item_key),
                        'value' => is_array($item) ? json_encode($item) : $item,
                    );
                }
            }
            
            $formatted_data[$section_key] = $formatted_section;
        }
        
        return $formatted_data;
    }
    
    /**
     * Format a key into a readable label
     * 
     * @param string $key The key to format
     * @return string Formatted label
     */
    private function format_label($key) {
        // Replace underscores and dashes with spaces
        $label = str_replace(array('_', '-'), ' ', $key);
        
        // Capitalize each word
        $label = ucwords($label);
        
        return $label;
    }

    /**
     * Fallback method to get debug data for WordPress versions without Site Health API
     * 
     * @return array Debug data in a format similar to wp_get_debug_data()
     */
    public function get_fallback_debug_data() {
        global $wpdb;
        $debug_data = array();
        
        // WordPress Core
        $debug_data['wp-core'] = array(
            'version' => array(
                'label' => 'WordPress version',
                'value' => get_bloginfo('version'),
                'debug' => get_bloginfo('version')
            ),
            'site_language' => array(
                'label' => 'Site Language',
                'value' => get_locale(),
                'debug' => get_locale()
            ),
            'home_url' => array(
                'label' => 'Home URL',
                'value' => home_url(),
                'debug' => home_url()
            ),
            'site_url' => array(
                'label' => 'Site URL',
                'value' => site_url(),
                'debug' => site_url()
            ),
            'permalink' => array(
                'label' => 'Permalink structure',
                'value' => get_option('permalink_structure') ? get_option('permalink_structure') : 'Default',
                'debug' => get_option('permalink_structure') ? get_option('permalink_structure') : 'Default'
            )
        );
        
        // Server Information
        $debug_data['wp-server'] = array(
            'php_version' => array(
                'label' => 'PHP Version',
                'value' => PHP_VERSION,
                'debug' => PHP_VERSION
            ),
            'mysql_version' => array(
                'label' => 'MySQL Version',
                'value' => $wpdb->db_version(),
                'debug' => $wpdb->db_version()
            ),
            'server_software' => array(
                'label' => 'Server Software',
                'value' => $_SERVER['SERVER_SOFTWARE'],
                'debug' => $_SERVER['SERVER_SOFTWARE']
            )
        );
        
        // Database Information
        $debug_data['wp-database'] = array(
            'extension' => array(
                'label' => 'Extension',
                'value' => 'mysql',
                'debug' => 'mysql'
            ),
            'server_version' => array(
                'label' => 'Server Version',
                'value' => $wpdb->db_version(),
                'debug' => $wpdb->db_version()
            ),
            'database_size' => array(
                'label' => 'Database Size',
                'value' => 'N/A in fallback mode',
                'debug' => 'N/A'
            )
        );
        
        // Active Plugins
        $active_plugins = get_option('active_plugins');
        $plugins_data = array();
        
        if (function_exists('get_plugins')) {
            $all_plugins = get_plugins();
            
            foreach ($active_plugins as $plugin) {
                if (isset($all_plugins[$plugin])) {
                    $plugins_data[$plugin] = array(
                        'label' => $all_plugins[$plugin]['Name'],
                        'value' => $all_plugins[$plugin]['Version'],
                        'debug' => $plugin
                    );
                }
            }
        }
        
        $debug_data['wp-plugins-active'] = $plugins_data;
        
        // Active Theme
        $theme = wp_get_theme();
        $debug_data['wp-active-theme'] = array(
            'name' => array(
                'label' => 'Name',
                'value' => $theme->get('Name'),
                'debug' => $theme->get('Name')
            ),
            'version' => array(
                'label' => 'Version',
                'value' => $theme->get('Version'),
                'debug' => $theme->get('Version')
            ),
            'author' => array(
                'label' => 'Author',
                'value' => $theme->get('Author'),
                'debug' => $theme->get('Author')
            )
        );
        
        // Constants
        $debug_data['wp-constants'] = array(
            'WP_DEBUG' => array(
                'label' => 'WP_DEBUG',
                'value' => defined('WP_DEBUG') && WP_DEBUG ? 'Enabled' : 'Disabled',
                'debug' => defined('WP_DEBUG') && WP_DEBUG
            ),
            'WP_DEBUG_LOG' => array(
                'label' => 'WP_DEBUG_LOG',
                'value' => defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? 'Enabled' : 'Disabled',
                'debug' => defined('WP_DEBUG_LOG') && WP_DEBUG_LOG
            ),
            'WP_DEBUG_DISPLAY' => array(
                'label' => 'WP_DEBUG_DISPLAY',
                'value' => defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY ? 'Enabled' : 'Disabled',
                'debug' => defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY
            )
        );
        
        return $debug_data;
    }
}