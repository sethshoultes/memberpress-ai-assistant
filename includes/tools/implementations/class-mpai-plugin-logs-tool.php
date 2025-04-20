<?php
/**
 * Plugin Logs Tool for MemberPress AI Assistant
 *
 * @package MemberPress AI Assistant
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin Logs Tool for MemberPress AI Assistant
 * 
 * Provides access to plugin installation, activation, deactivation, and deletion logs
 */
class MPAI_Plugin_Logs_Tool extends MPAI_Base_Tool {
    /**
     * Constructor
     */
    public function __construct() {
        $this->name = 'plugin_logs';
        $this->description = 'Retrieve and analyze logs of plugin installations, activations, deactivations, and deletions';
    }
    
    /**
     * Get tool definition for AI function calling
     *
     * @return array Tool definition
     */
    public function get_tool_definition() {
        return [
            'type' => 'function',
            'function' => [
                'name' => 'get_plugin_logs',
                'description' => 'Retrieve and analyze logs of WordPress plugin installations, activations, deactivations, and deletions',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'action' => [
                            'type' => 'string',
                            'enum' => ['installed', 'updated', 'activated', 'deactivated', 'deleted', ''],
                            'description' => 'Filter logs by action type (installed, updated, activated, deactivated, deleted) or empty for all actions'
                        ],
                        'plugin_name' => [
                            'type' => 'string',
                            'description' => 'Filter logs by plugin name (partial match)'
                        ],
                        'days' => [
                            'type' => 'integer',
                            'description' => 'Number of days to look back in the logs (0 for all time)',
                            'default' => 30
                        ],
                        'limit' => [
                            'type' => 'integer',
                            'description' => 'Maximum number of logs to return',
                            'default' => 25
                        ],
                        'summary_only' => [
                            'type' => 'boolean',
                            'description' => 'Return only summary information instead of detailed logs',
                            'default' => false
                        ]
                    ],
                    'required' => []
                ]
            ]
        ];
    }
    
    /**
     * Get tool parameters for OpenAI function calling
     *
     * @return array Tool parameters
     */
    public function get_parameters() {
        // Return the parameters part of the tool definition
        return [
            'action' => [
                'type' => 'string',
                'enum' => ['installed', 'updated', 'activated', 'deactivated', 'deleted', ''],
                'description' => 'Filter logs by action type (installed, updated, activated, deactivated, deleted) or empty for all actions'
            ],
            'plugin_name' => [
                'type' => 'string',
                'description' => 'Filter logs by plugin name (partial match)'
            ],
            'days' => [
                'type' => 'integer',
                'description' => 'Number of days to look back in the logs (0 for all time)',
                'default' => 30
            ],
            'limit' => [
                'type' => 'integer',
                'description' => 'Maximum number of logs to return',
                'default' => 25
            ],
            'summary_only' => [
                'type' => 'boolean',
                'description' => 'Return only summary information instead of detailed logs',
                'default' => false
            ]
        ];
    }
    
    /**
     * Get required parameters
     *
     * @return array List of required parameter names
     */
    public function get_required_parameters() {
        return []; // No required parameters for this tool
    }
    
    /**
     * Execute the tool implementation with validated parameters
     *
     * @param array $parameters Validated tool parameters
     * @return array Result of the plugin logs query
     */
    protected function execute_tool($parameters) {
        mpai_log_debug('Executing plugin_logs tool with parameters: ' . json_encode($parameters), 'plugin-logs-tool');
        
        // Initialize the plugin logger with better error handling
        if (!function_exists('mpai_init_plugin_logger')) {
            if (defined('MPAI_PLUGIN_DIR')) {
                $logger_file = MPAI_PLUGIN_DIR . 'includes/class-mpai-plugin-logger.php';
                if (file_exists($logger_file)) {
                    mpai_log_debug('Loading plugin logger class from: ' . $logger_file, 'plugin-logs-tool');
                    require_once $logger_file;
                } else {
                    mpai_log_error('Plugin logger class not found at: ' . $logger_file, 'plugin-logs-tool');
                    return $this->get_fallback_response('Plugin logger class not found. Using WordPress API instead.');
                }
            } else {
                mpai_log_error('MPAI_PLUGIN_DIR not defined', 'plugin-logs-tool');
                return $this->get_fallback_response('MPAI_PLUGIN_DIR not defined. Using WordPress API instead.');
            }
        }
        
        // Try to initialize the plugin logger
        try {
            $plugin_logger = mpai_init_plugin_logger();
            
            if (!$plugin_logger) {
                mpai_log_error('Failed to initialize plugin logger', 'plugin-logs-tool');
                return $this->get_fallback_response('Failed to initialize plugin logger. Using WordPress API instead.');
            }
        } catch (Exception $e) {
            mpai_log_error('Exception initializing plugin logger: ' . $e->getMessage(), 'plugin-logs-tool');
            return $this->get_fallback_response('Error initializing plugin logger: ' . $e->getMessage());
        }
        
        // Parameters are already validated and sanitized by the base class
        $action = isset($parameters['action']) ? $parameters['action'] : '';
        $plugin_name = isset($parameters['plugin_name']) ? $parameters['plugin_name'] : '';
        $days = isset($parameters['days']) ? intval($parameters['days']) : 30;
        $limit = isset($parameters['limit']) ? intval($parameters['limit']) : 25;
        $summary_only = isset($parameters['summary_only']) ? (bool)$parameters['summary_only'] : false;
        
        // Calculate date range
        $date_from = '';
        if ($days > 0) {
            $date_from = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        }
        
        // Get summary data with error handling
        try {
            $summary_days = $days > 0 ? $days : 365; // If all time, limit to 1 year for summary
            $summary = $plugin_logger->get_activity_summary($summary_days);
            
            // Check if we got a fallback summary (indicates database issues)
            $using_fallback = isset($summary['is_fallback']) && $summary['is_fallback'];
            
            if ($using_fallback) {
                mpai_log_warning('Using fallback summary data from plugin logger', 'plugin-logs-tool');
            }
            
            // Create a simplified summary
            $action_counts = [
                'total' => 0,
                'installed' => 0,
                'updated' => 0,
                'activated' => 0,
                'deactivated' => 0,
                'deleted' => 0
            ];
            
            if (isset($summary['action_counts']) && is_array($summary['action_counts'])) {
                foreach ($summary['action_counts'] as $count_data) {
                    if (isset($count_data['action']) && isset($count_data['count'])) {
                        $action_counts[$count_data['action']] = intval($count_data['count']);
                        $action_counts['total'] += intval($count_data['count']);
                    }
                }
            }
            
            // If summary_only is true, return just the summary data
            if ($summary_only) {
                return [
                    'success' => true,
                    'summary' => $action_counts,
                    'time_period' => $days > 0 ? "past {$days} days" : "all time",
                    'most_active_plugins' => $summary['most_active_plugins'] ?? [],
                    'logs_exist' => $action_counts['total'] > 0,
                    'using_fallback' => $using_fallback,
                    'message' => $action_counts['total'] > 0
                        ? "Found {$action_counts['total']} plugin log entries"
                        : "No plugin logs found for the specified criteria"
                ];
            }
            
            // Prepare query arguments for detailed logs
            $args = [
                'plugin_name' => $plugin_name,
                'action'      => $action,
                'date_from'   => $date_from,
                'orderby'     => 'date_time',
                'order'       => 'DESC',
                'limit'       => $limit
            ];
            
            // Get logs with error handling
            try {
                $logs = $plugin_logger->get_logs($args);
                
                // If we got no logs but we're looking for installed plugins specifically,
                // try to get information directly from WordPress
                if (empty($logs) && $action === 'installed') {
                    mpai_log_debug('No installation logs found, trying WordPress API', 'plugin-logs-tool');
                    $wp_plugins_info = $this->get_wordpress_plugin_info();
                    
                    if (!empty($wp_plugins_info['recently_activated'])) {
                        mpai_log_debug('Found recently activated plugins via WordPress API', 'plugin-logs-tool');
                        $logs = $this->convert_wp_plugins_to_logs($wp_plugins_info['recently_activated']);
                    }
                }
                
                // Get total count for the query
                $count_args = [
                    'plugin_name' => $plugin_name,
                    'action'      => $action,
                    'date_from'   => $date_from
                ];
                
                $total = $plugin_logger->count_logs($count_args);
                
                // Enhance the logs with readable timestamps and organize by plugin
                $processed_logs = [];
                foreach ($logs as $log) {
                    // Convert the MySQL timestamp to a readable format
                    $timestamp = strtotime($log['date_time']);
                    $log['readable_date'] = date('F j, Y, g:i a', $timestamp);
                    $log['time_ago'] = $this->time_elapsed_string($timestamp);
                    
                    // Extract plugin slug as key for grouping
                    $plugin_key = strtolower($log['plugin_slug']);
                    if (!isset($processed_logs[$plugin_key])) {
                        $processed_logs[$plugin_key] = [
                            'plugin_name' => $log['plugin_name'],
                            'plugin_slug' => $log['plugin_slug'],
                            'current_version' => $log['plugin_version'],
                            'logs' => []
                        ];
                    }
                    
                    // Add this log to the plugin's logs array
                    $processed_logs[$plugin_key]['logs'][] = $log;
                }
                
                // Add natural language summary for better AI understanding
                $nl_summary = $this->generate_natural_language_summary($action, $plugin_name, $logs, $action_counts);
                
                return [
                    'success' => true,
                    'summary' => $action_counts,
                    'time_period' => $days > 0 ? "past {$days} days" : "all time",
                    'total_records' => $total,
                    'returned_records' => count($logs),
                    'has_more' => $total > count($logs),
                    'plugins' => array_values($processed_logs),
                    'logs' => $logs,  // Include flat logs array for compatibility
                    'using_fallback' => $using_fallback,
                    'nl_summary' => $nl_summary,
                    'query' => [
                        'action' => $action,
                        'plugin_name' => $plugin_name,
                        'days' => $days,
                        'limit' => $limit
                    ]
                ];
            } catch (Exception $e) {
                mpai_log_error('Error getting logs: ' . $e->getMessage(), 'plugin-logs-tool');
                return $this->get_fallback_response('Error retrieving plugin logs: ' . $e->getMessage());
            }
        } catch (Exception $e) {
            mpai_log_error('Error getting activity summary: ' . $e->getMessage(), 'plugin-logs-tool');
            return $this->get_fallback_response('Error retrieving plugin activity summary: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate a natural language summary of the plugin logs
     *
     * @param string $action Action filter
     * @param string $plugin_name Plugin name filter
     * @param array $logs Log entries
     * @param array $action_counts Action counts
     * @return string Natural language summary
     */
    private function generate_natural_language_summary($action, $plugin_name, $logs, $action_counts) {
        $summary = '';
        
        // If filtering by action
        if (!empty($action)) {
            if (count($logs) > 0) {
                $summary .= "Found " . count($logs) . " plugins that were " . $action . " recently. ";
                
                // List the most recent ones
                $summary .= "The most recent ones include: ";
                $plugin_list = [];
                $count = 0;
                
                foreach ($logs as $log) {
                    if ($count < 3) {
                        $plugin_list[] = $log['plugin_name'] . " (" . $this->time_elapsed_string(strtotime($log['date_time'])) . ")";
                        $count++;
                    } else {
                        break;
                    }
                }
                
                $summary .= implode(", ", $plugin_list) . ".";
            } else {
                $summary .= "No plugins were found that were " . $action . " recently.";
            }
        }
        // If filtering by plugin name
        elseif (!empty($plugin_name)) {
            if (count($logs) > 0) {
                $summary .= "Found " . count($logs) . " recent activities for plugins matching '" . $plugin_name . "'. ";
                
                // Group activities by plugin
                $plugin_activities = [];
                foreach ($logs as $log) {
                    if (!isset($plugin_activities[$log['plugin_name']])) {
                        $plugin_activities[$log['plugin_name']] = [];
                    }
                    $plugin_activities[$log['plugin_name']][] = $log;
                }
                
                // Summarize activities for each plugin
                foreach ($plugin_activities as $name => $activities) {
                    $summary .= $name . " was ";
                    $actions = array_column($activities, 'action');
                    $summary .= implode(", ", array_unique($actions)) . ". ";
                }
            } else {
                $summary .= "No recent activities found for plugins matching '" . $plugin_name . "'.";
            }
        }
        // General summary
        else {
            $summary .= "In the recent period, there have been " . $action_counts['total'] . " plugin activities: ";
            $summary .= $action_counts['installed'] . " installations, ";
            $summary .= $action_counts['updated'] . " updates, ";
            $summary .= $action_counts['activated'] . " activations, ";
            $summary .= $action_counts['deactivated'] . " deactivations, and ";
            $summary .= $action_counts['deleted'] . " deletions.";
            
            if (count($logs) > 0) {
                $summary .= " The most recent activity was " . $logs[0]['action'] . " of " . $logs[0]['plugin_name'] . " " . $this->time_elapsed_string(strtotime($logs[0]['date_time'])) . ".";
            }
        }
        
        return $summary;
    }
    
    /**
     * Get plugin information directly from WordPress
     *
     * @return array Plugin information
     */
    private function get_wordpress_plugin_info() {
        mpai_log_debug('Getting plugin information directly from WordPress', 'plugin-logs-tool');
        
        // Ensure plugin functions are available
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        if (!function_exists('is_plugin_active')) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        // Get all plugins
        $all_plugins = get_plugins();
        $active_plugins = get_option('active_plugins');
        
        // Get recently activated plugins
        $recently_activated = get_option('recently_activated');
        
        // Process recently activated plugins
        $recent_plugins = [];
        if (is_array($recently_activated)) {
            foreach ($recently_activated as $plugin => $time) {
                if (isset($all_plugins[$plugin])) {
                    $plugin_data = $all_plugins[$plugin];
                    $recent_plugins[] = [
                        'plugin_file' => $plugin,
                        'plugin_name' => $plugin_data['Name'],
                        'plugin_slug' => dirname($plugin),
                        'plugin_version' => $plugin_data['Version'],
                        'deactivated_time' => $time,
                        'author' => $plugin_data['Author'],
                        'description' => $plugin_data['Description'],
                        'url' => $plugin_data['PluginURI']
                    ];
                }
            }
        }
        
        return [
            'all_plugins' => $all_plugins,
            'active_plugins' => $active_plugins,
            'recently_activated' => $recent_plugins
        ];
    }
    
    /**
     * Convert WordPress plugin data to log format
     *
     * @param array $plugins Plugin data from WordPress
     * @return array Logs in the same format as the plugin logger
     */
    private function convert_wp_plugins_to_logs($plugins) {
        $logs = [];
        $user_id = get_current_user_id();
        $user = get_userdata($user_id);
        $user_login = $user ? $user->user_login : 'admin';
        
        foreach ($plugins as $plugin) {
            // Create a log entry for each plugin
            $logs[] = [
                'id' => 0,
                'plugin_slug' => $plugin['plugin_slug'],
                'plugin_name' => $plugin['plugin_name'],
                'plugin_version' => $plugin['plugin_version'],
                'plugin_prev_version' => null,
                'action' => 'deactivated', // These are from recently_activated
                'user_id' => $user_id,
                'user_login' => $user_login,
                'date_time' => date('Y-m-d H:i:s', $plugin['deactivated_time']),
                'additional_data' => [
                    'plugin_file' => $plugin['plugin_file'],
                    'author' => $plugin['author'],
                    'url' => $plugin['url'],
                    'from_wp_api' => true
                ]
            ];
            
            // Also add an installation entry (approximate time)
            $logs[] = [
                'id' => 0,
                'plugin_slug' => $plugin['plugin_slug'],
                'plugin_name' => $plugin['plugin_name'],
                'plugin_version' => $plugin['plugin_version'],
                'plugin_prev_version' => null,
                'action' => 'installed',
                'user_id' => $user_id,
                'user_login' => $user_login,
                'date_time' => date('Y-m-d H:i:s', $plugin['deactivated_time'] - 86400), // Assume installed 1 day before deactivation
                'additional_data' => [
                    'plugin_file' => $plugin['plugin_file'],
                    'author' => $plugin['author'],
                    'url' => $plugin['url'],
                    'from_wp_api' => true,
                    'estimated' => true
                ]
            ];
        }
        
        return $logs;
    }
    
    /**
     * Get a fallback response when the plugin logger fails
     *
     * @param string $error_message Error message
     * @return array Fallback response
     */
    private function get_fallback_response($error_message) {
        mpai_log_warning('Using fallback response: ' . $error_message, 'plugin-logs-tool');
        
        // Get plugin information directly from WordPress
        $wp_plugins_info = $this->get_wordpress_plugin_info();
        
        // Create a summary of plugin information
        $all_plugins = $wp_plugins_info['all_plugins'];
        $active_plugins = $wp_plugins_info['active_plugins'];
        $recently_activated = $wp_plugins_info['recently_activated'];
        
        $action_counts = [
            'total' => count($all_plugins),
            'installed' => count($all_plugins),
            'updated' => 0,
            'activated' => count($active_plugins),
            'deactivated' => count($recently_activated),
            'deleted' => 0
        ];
        
        // Create a natural language summary
        $nl_summary = "Using WordPress API directly due to database issues. ";
        $nl_summary .= "There are currently " . count($all_plugins) . " plugins installed, ";
        $nl_summary .= "with " . count($active_plugins) . " active plugins and ";
        $nl_summary .= count($recently_activated) . " recently deactivated plugins.";
        
        // Process recently activated plugins for detailed logs
        $logs = $this->convert_wp_plugins_to_logs($recently_activated);
        
        // Organize logs by plugin
        $processed_logs = [];
        foreach ($logs as $log) {
            // Extract plugin slug as key for grouping
            $plugin_key = strtolower($log['plugin_slug']);
            if (!isset($processed_logs[$plugin_key])) {
                $processed_logs[$plugin_key] = [
                    'plugin_name' => $log['plugin_name'],
                    'plugin_slug' => $log['plugin_slug'],
                    'current_version' => $log['plugin_version'],
                    'logs' => []
                ];
            }
            
            // Add this log to the plugin's logs array
            $processed_logs[$plugin_key]['logs'][] = $log;
        }
        
        return [
            'success' => true,
            'summary' => $action_counts,
            'time_period' => "current",
            'total_records' => count($logs),
            'returned_records' => count($logs),
            'has_more' => false,
            'plugins' => array_values($processed_logs),
            'logs' => $logs,
            'using_fallback' => true,
            'fallback_reason' => $error_message,
            'nl_summary' => $nl_summary,
            'query' => [
                'action' => '',
                'plugin_name' => '',
                'days' => 30,
                'limit' => 25
            ]
        ];
    }
    
    /**
     * Convert a timestamp to a human-readable "time ago" string
     *
     * @param int $timestamp Unix timestamp
     * @return string Human-readable time difference
     */
    private function time_elapsed_string($timestamp) {
        $now = current_time('timestamp');
        $diff = $now - $timestamp;
        
        if ($diff < 60) {
            return 'just now';
        }
        
        $intervals = [
            31536000 => 'year',
            2592000 => 'month',
            604800 => 'week',
            86400 => 'day',
            3600 => 'hour',
            60 => 'minute'
        ];
        
        foreach ($intervals as $seconds => $label) {
            $count = floor($diff / $seconds);
            if ($count > 0) {
                return $count == 1 ? "1 {$label} ago" : "{$count} {$label}s ago";
            }
        }
        
        return 'just now';
    }
}