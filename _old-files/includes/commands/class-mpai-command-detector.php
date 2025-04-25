<?php
/**
 * Command Detector
 *
 * Detects commands from natural language queries
 *
 * @package MemberPress AI Assistant
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Command Detector Class
 */
class MPAI_Command_Detector {

    /**
     * Logger instance
     *
     * @var object
     */
    private $logger;

    /**
     * Constructor
     */
    public function __construct() {
        // Initialize logger
        $this->logger = $this->get_default_logger();
    }
    
    /**
     * Get default logger
     * 
     * @return object Default logger
     */
    private function get_default_logger() {
        // Create a simple logger class
        return new class() {
            public function info($message) {
                mpai_log_info($message, 'command-detector');
            }
            
            public function warning($message) {
                mpai_log_warning($message, 'command-detector');
            }
            
            public function error($message) {
                mpai_log_error($message, 'command-detector');
            }
            
            public function debug($message) {
                mpai_log_debug($message, 'command-detector');
            }
        };
    }


    /**
     * Detect command from natural language message
     *
     * @param string $message User message
     * @return array|false Detected command or false if none detected
     */
    public function detect_command($message) {
        $this->logger->info('Detecting command from: ' . $message);
        
        // If message already has explicit command syntax, return it directly
        if ($this->is_explicit_command($message)) {
            return [
                'type' => 'explicit',
                'command' => $message,
                'parameters' => []
            ];
        }
        
        // Check for PHP version queries
        if ($php_command = $this->detect_php_version_query($message)) {
            return $php_command;
        }
        
        // Check for plugin-related queries
        if ($plugin_command = $this->detect_plugin_query($message)) {
            return $plugin_command;
        }
        
        // Check for theme-related queries
        if ($theme_command = $this->detect_theme_query($message)) {
            return $theme_command;
        }
        
        // Check for WordPress system queries
        if ($system_command = $this->detect_system_query($message)) {
            return $system_command;
        }
        
        // Check for user-related queries
        if ($user_command = $this->detect_user_query($message)) {
            return $user_command;
        }
        
        // Check for database queries
        if ($db_command = $this->detect_db_query($message)) {
            return $db_command;
        }
        
        // No command detected
        $this->logger->warning('No command detected from message');
        return false;
    }

    /**
     * Check if message is an explicit command
     *
     * @param string $message Message to check
     * @return bool Whether message is an explicit command
     */
    private function is_explicit_command($message) {
        return (strpos($message, 'wp ') === 0 || strpos($message, 'php ') === 0);
    }

    /**
     * Detect PHP version query
     *
     * @param string $message User message
     * @return array|false Detected command or false
     */
    private function detect_php_version_query($message) {
        $php_version_patterns = [
            '/php.*version/i',
            '/version.*php/i',
            '/php.*info/i',
            '/what.*php.*version/i',
            '/which.*php.*version/i',
            '/php\s+([-]{1,2}v|info)/i',
            '/phpinfo/i',
            '/what.*version.*php/i',
            '/installed.*php.*version/i',
            '/php.*installed/i',
            '/check.*php.*version/i',
            '/show.*php.*version/i',
            '/tell.*php.*version/i',
            '/get.*php.*version/i',
            '/display.*php.*version/i'
        ];
        
        foreach ($php_version_patterns as $pattern) {
            if (preg_match($pattern, $message)) {
                $this->logger->info('Detected PHP version query');
                return [
                    'type' => 'php_version',
                    'command' => 'php -v',
                    'parameters' => []
                ];
            }
        }
        
        return false;
    }

    /**
     * Detect plugin-related query
     *
     * @param string $message User message
     * @return array|false Detected command or false
     */
    private function detect_plugin_query($message) {
        // Plugin list query
        $plugin_list_patterns = [
            '/list.*plugin/i',
            '/show.*plugin/i',
            '/get.*plugin/i',
            '/what.*plugin.*(installed|active)/i',
            '/plugin.*list/i'
        ];
        
        foreach ($plugin_list_patterns as $pattern) {
            if (preg_match($pattern, $message)) {
                $this->logger->info('Detected plugin list query');
                
                // Check for status filter
                $status = null;
                if (stripos($message, 'active') !== false) {
                    $status = 'active';
                } elseif (stripos($message, 'inactive') !== false) {
                    $status = 'inactive';
                }
                
                return [
                    'type' => 'plugin_list',
                    'command' => 'wp plugin list',
                    'parameters' => [
                        'status' => $status
                    ]
                ];
            }
        }
        
        // Plugin status or information query
        $plugin_status_patterns = [
            '/plugin.*status/i',
            '/plugin.*info/i',
            '/info.*plugin/i',
            '/status.*plugin/i'
        ];
        
        foreach ($plugin_status_patterns as $pattern) {
            if (preg_match($pattern, $message)) {
                $this->logger->info('Detected plugin status query');
                return [
                    'type' => 'plugin_status',
                    'command' => 'wp plugin status',
                    'parameters' => []
                ];
            }
        }
        
        // Plugin update check
        if (preg_match('/plugin.*update/i', $message) || preg_match('/update.*plugin/i', $message)) {
            $this->logger->info('Detected plugin update query');
            return [
                'type' => 'plugin_update',
                'command' => 'wp plugin update --dry-run',
                'parameters' => []
            ];
        }
        
        return false;
    }

    /**
     * Detect theme-related query
     *
     * @param string $message User message
     * @return array|false Detected command or false
     */
    private function detect_theme_query($message) {
        // Theme activation query
        $theme_activation_patterns = [
            '/activate.*theme/i',
            '/switch.*theme/i',
            '/change.*theme/i',
            '/use.*theme/i',
            '/set.*theme/i'
        ];
        
        foreach ($theme_activation_patterns as $pattern) {
            if (preg_match($pattern, $message)) {
                $this->logger->info('Detected theme activation query');
                
                // Try to extract theme name
                $theme_name = null;
                
                // Look for common theme names
                $theme_patterns = [
                    '/twenty\s*twenty\s*three/i' => 'twentytwentythree',
                    '/twenty\s*twenty\s*two/i' => 'twentytwentytwo',
                    '/twenty\s*twenty\s*one/i' => 'twentytwentyone',
                    '/twenty\s*twenty/i' => 'twentytwenty',
                    '/twenty\s*nineteen/i' => 'twentynineteen',
                    '/twenty\s*seventeen/i' => 'twentyseventeen',
                    '/twenty\s*sixteen/i' => 'twentysixteen',
                    '/twenty\s*fifteen/i' => 'twentyfifteen',
                    '/twenty\s*fourteen/i' => 'twentyfourteen',
                    '/twenty\s*thirteen/i' => 'twentythirteen',
                    '/twenty\s*twelve/i' => 'twentytwelve',
                    '/twenty\s*eleven/i' => 'twentyeleven',
                    '/twenty\s*ten/i' => 'twentyten'
                ];
                
                foreach ($theme_patterns as $search => $theme) {
                    if (preg_match($search, $message)) {
                        $theme_name = $theme;
                        break;
                    }
                }
                
                // If we found a theme name, return a wp_api tool call
                if ($theme_name) {
                    return [
                        'type' => 'wp_api',
                        'tool' => 'wp_api',
                        'parameters' => [
                            'action' => 'activate_theme',
                            'theme' => $theme_name
                        ]
                    ];
                }
                
                // If no specific theme name found, return a generic command
                return [
                    'type' => 'theme_activate',
                    'command' => 'wp theme activate',
                    'parameters' => []
                ];
            }
        }
        
        // Theme list query
        $theme_list_patterns = [
            '/list.*theme/i',
            '/show.*theme/i',
            '/get.*theme/i',
            '/what.*theme.*(installed|active)/i',
            '/theme.*list/i'
        ];
        
        foreach ($theme_list_patterns as $pattern) {
            if (preg_match($pattern, $message)) {
                $this->logger->info('Detected theme list query');
                
                // Check for status filter
                $status = null;
                if (stripos($message, 'active') !== false) {
                    $status = 'active';
                } elseif (stripos($message, 'inactive') !== false) {
                    $status = 'inactive';
                }
                
                // Return a wp_api tool call for theme list
                return [
                    'type' => 'wp_api',
                    'tool' => 'wp_api',
                    'parameters' => [
                        'action' => 'get_themes'
                    ]
                ];
            }
        }
        
        // Current theme query
        $current_theme_patterns = [
            '/current.*theme/i',
            '/active.*theme/i',
            '/theme.*active/i',
            '/theme.*current/i',
            '/what.*theme.*using/i'
        ];
        
        foreach ($current_theme_patterns as $pattern) {
            if (preg_match($pattern, $message)) {
                $this->logger->info('Detected current theme query');
                
                // Return a wp_api tool call for theme list
                return [
                    'type' => 'wp_api',
                    'tool' => 'wp_api',
                    'parameters' => [
                        'action' => 'get_themes'
                    ]
                ];
            }
        }
        
        return false;
    }

    /**
     * Detect system-related query
     *
     * @param string $message User message
     * @return array|false Detected command or false
     */
    private function detect_system_query($message) {
        // WordPress version query
        $wp_version_patterns = [
            '/wordpress.*version/i',
            '/version.*wordpress/i',
            '/what.*wordpress.*version/i',
            '/which.*wordpress.*version/i',
            '/wp.*version/i'
        ];
        
        foreach ($wp_version_patterns as $pattern) {
            if (preg_match($pattern, $message)) {
                $this->logger->info('Detected WordPress version query');
                return [
                    'type' => 'wp_version',
                    'command' => 'wp core version',
                    'parameters' => []
                ];
            }
        }
        
        // System information query
        $system_info_patterns = [
            '/system.*info/i',
            '/site.*health/i',
            '/health.*check/i',
            '/wordpress.*status/i',
            '/wp.*status/i',
            '/status.*wordpress/i',
            '/status.*wp/i'
        ];
        
        foreach ($system_info_patterns as $pattern) {
            if (preg_match($pattern, $message)) {
                $this->logger->info('Detected system info query');
                return [
                    'type' => 'system_info',
                    'command' => 'wp site health',
                    'parameters' => []
                ];
            }
        }
        
        // Database information query
        $db_info_patterns = [
            '/database.*info/i',
            '/db.*info/i',
            '/info.*database/i',
            '/info.*db/i',
            '/mysql.*info/i',
            '/info.*mysql/i'
        ];
        
        foreach ($db_info_patterns as $pattern) {
            if (preg_match($pattern, $message)) {
                $this->logger->info('Detected database info query');
                return [
                    'type' => 'db_info',
                    'command' => 'wp db info',
                    'parameters' => []
                ];
            }
        }
        
        return false;
    }

    /**
     * Detect user-related query
     *
     * @param string $message User message
     * @return array|false Detected command or false
     */
    private function detect_user_query($message) {
        // User list query
        $user_list_patterns = [
            '/list.*user/i',
            '/show.*user/i',
            '/get.*user/i',
            '/user.*list/i'
        ];
        
        foreach ($user_list_patterns as $pattern) {
            if (preg_match($pattern, $message)) {
                $this->logger->info('Detected user list query');
                return [
                    'type' => 'user_list',
                    'command' => 'wp user list',
                    'parameters' => []
                ];
            }
        }
        
        return false;
    }

    /**
     * Detect database-related query
     *
     * @param string $message User message
     * @return array|false Detected command or false
     */
    private function detect_db_query($message) {
        // Database status query
        $db_status_patterns = [
            '/database.*status/i',
            '/db.*status/i',
            '/status.*database/i',
            '/status.*db/i'
        ];
        
        foreach ($db_status_patterns as $pattern) {
            if (preg_match($pattern, $message)) {
                $this->logger->info('Detected database status query');
                return [
                    'type' => 'db_status',
                    'command' => 'wp db info',
                    'parameters' => []
                ];
            }
        }
        
        return false;
    }
}