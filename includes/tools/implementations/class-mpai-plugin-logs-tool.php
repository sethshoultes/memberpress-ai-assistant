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
     * Execute the plugin logs tool
     *
     * @param array $parameters Tool parameters
     * @return array Result of the plugin logs query
     */
    public function execute($parameters) {
        // Initialize the plugin logger
        if (!function_exists('mpai_init_plugin_logger')) {
            if (file_exists(MPAI_PLUGIN_DIR . 'includes/class-mpai-plugin-logger.php')) {
                require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-plugin-logger.php';
            } else {
                return [
                    'success' => false,
                    'message' => 'Plugin logger class not found'
                ];
            }
        }
        
        $plugin_logger = mpai_init_plugin_logger();
        
        if (!$plugin_logger) {
            return [
                'success' => false,
                'message' => 'Failed to initialize plugin logger'
            ];
        }
        
        // Parse parameters
        $action = isset($parameters['action']) ? sanitize_text_field($parameters['action']) : '';
        $plugin_name = isset($parameters['plugin_name']) ? sanitize_text_field($parameters['plugin_name']) : '';
        $days = isset($parameters['days']) ? intval($parameters['days']) : 30;
        $limit = isset($parameters['limit']) ? intval($parameters['limit']) : 25;
        $summary_only = isset($parameters['summary_only']) ? (bool)$parameters['summary_only'] : false;
        
        // Calculate date range
        $date_from = '';
        if ($days > 0) {
            $date_from = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        }
        
        // Get summary data
        $summary_days = $days > 0 ? $days : 365; // If all time, limit to 1 year for summary
        $summary = $plugin_logger->get_activity_summary($summary_days);
        
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
        
        // Get logs
        $logs = $plugin_logger->get_logs($args);
        
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
        
        return [
            'success' => true,
            'summary' => $action_counts,
            'time_period' => $days > 0 ? "past {$days} days" : "all time",
            'total_records' => $total,
            'returned_records' => count($logs),
            'has_more' => $total > count($logs),
            'plugins' => array_values($processed_logs),
            'logs' => $logs,  // Include flat logs array for compatibility
            'query' => [
                'action' => $action,
                'plugin_name' => $plugin_name,
                'days' => $days,
                'limit' => $limit
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