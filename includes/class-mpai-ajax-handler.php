<?php
/**
 * AJAX Handler Class
 *
 * Handles all AJAX requests for the MemberPress AI Assistant plugin
 * 
 * @package MemberPress AI Assistant
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

class MPAI_AJAX_Handler {
    /**
     * Constructor
     */
    public function __construct() {
        // Register AJAX handlers
        add_action('wp_ajax_mpai_execute_tool', array($this, 'execute_tool'));
    }

    /**
     * Execute a tool via AJAX
     */
    public function execute_tool() {
        // Log the tool execution request for debugging
        mpai_log_debug('execute_tool called. POST data: ' . json_encode($_POST), 'ajax-handler');
        
        // Check nonce
        check_ajax_referer('mpai_chat_nonce', 'nonce');
        
        // Only allow logged-in users with appropriate capabilities
        if (!current_user_can('edit_posts')) {
            mpai_log_warning('Unauthorized access attempt', 'ajax-handler');
            wp_send_json_error('Unauthorized access');
            return;
        }
        
        // Check tool request
        if (empty($_POST['tool_request'])) {
            mpai_log_warning('Tool request is empty', 'ajax-handler');
            wp_send_json_error('Tool request is required');
            return;
        }

        // Store raw tool request for debugging
        $raw_tool_request = $_POST['tool_request'];
        mpai_log_debug('RAW tool request: ' . $raw_tool_request, 'ajax-handler');
        
        // Parse the tool request
        $tool_request = json_decode(stripslashes($_POST['tool_request']), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Try without stripslashes
            $tool_request = json_decode($_POST['tool_request'], true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                // Store the error details for debugging
                $json_error = json_last_error_msg();
                mpai_log_warning('Invalid JSON in tool request: ' . $json_error, 'ajax-handler');
                
                // Try to salvage the request by looking for XML or function call format
                // This handles cases where the AI uses a different format for tool calls
                if (strpos($raw_tool_request, '<') === 0 || 
                    strpos($raw_tool_request, 'function(') !== false ||
                    strpos($raw_tool_request, 'create_membership') !== false) {
                    
                    mpai_log_debug('Attempting to salvage non-JSON tool request', 'ajax-handler');
                    
                    // Initialize parameter validator if available
                    if (file_exists(MPAI_PLUGIN_DIR . 'includes/class-mpai-parameter-validator.php')) {
                        require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-parameter-validator.php';
                        $parameter_validator = mpai_init_parameter_validator();
                        
                        // Create a minimal tool request with the raw request as parameter
                        $tool_request = [
                            'name' => 'memberpress_info',
                            'raw_request' => $raw_tool_request,
                            'parameters' => [
                                'type' => 'create'
                            ]
                        ];
                        
                        mpai_log_debug('Created fallback tool request for parameter extraction', 'ajax-handler');
                    } else {
                        // Cannot salvage without parameter validator
                        wp_send_json_error('Invalid JSON in tool request and parameter validator not available: ' . $json_error);
                        return;
                    }
                } else {
                    // Not a salvageable format
                    wp_send_json_error('Invalid JSON in tool request: ' . $json_error);
                    return;
                }
            }
        }
        
        mpai_log_debug('Processing tool request: ' . json_encode($tool_request), 'ajax-handler');
        
        // Special handling for plugin_logs tool
        if ((isset($tool_request['name']) && $tool_request['name'] === 'plugin_logs') ||
            (isset($tool_request['tool']) && $tool_request['tool'] === 'plugin_logs')) {
            // Use the direct plugin logs handler instead of going through WP-CLI
            $this->handle_plugin_logs_tool_direct($tool_request);
            return;
        }
        
        // Standardize tool request format
        if (!isset($tool_request['name']) && isset($tool_request['tool'])) {
            // Convert from tool to name format for consistency
            $tool_request['name'] = $tool_request['tool'];
            unset($tool_request['tool']);
            mpai_log_debug('Converted tool format to name format', 'ajax-handler');
        }
        
        // Ensure name parameter exists
        if (!isset($tool_request['name'])) {
            mpai_log_warning('Tool request missing name parameter', 'ajax-handler');
            wp_send_json_error('Tool request must include a name parameter');
            return;
        }
        
        try {
            // Initialize context manager
            $context_manager = new MPAI_Context_Manager();
            
            // Process the tool request
            $result = $context_manager->process_tool_request($tool_request);
            
            mpai_log_debug('Tool execution result: ' . json_encode($result), 'ajax-handler');
            
            wp_send_json_success($result);
        } catch (Exception $e) {
            mpai_log_error('Error executing tool: ' . $e->getMessage(), 'ajax-handler', array(
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ));
            wp_send_json_error('Error executing tool: ' . $e->getMessage());
        }
    }
    
    /**
     * Handle plugin_logs tool using the direct approach
     *
     * @param array $tool_request Tool request data
     */
    private function handle_plugin_logs_tool_direct($tool_request) {
        try {
            // Extract parameters
            $parameters = isset($tool_request['parameters']) ? $tool_request['parameters'] : [];
            
            // Parameters should always be in the standard format now
            $action = isset($parameters['action']) ? sanitize_text_field($parameters['action']) : '';
            $plugin_name = isset($parameters['plugin_name']) ? sanitize_text_field($parameters['plugin_name']) : '';
            $days = isset($parameters['days']) ? intval($parameters['days']) : 30;
            $limit = isset($parameters['limit']) ? intval($parameters['limit']) : 25;
            $summary_only = isset($parameters['summary_only']) ? (bool)$parameters['summary_only'] : false;
            
            // Initialize the plugin logger
            if (!function_exists('mpai_init_plugin_logger')) {
                if (file_exists(MPAI_PLUGIN_DIR . 'includes/class-mpai-plugin-logger.php')) {
                    require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-plugin-logger.php';
                    mpai_log_debug('Loaded plugin logger class', 'ajax-handler');
                } else {
                    mpai_log_error('Plugin logger class not found', 'ajax-handler');
                    wp_send_json_error('Plugin logger class not found');
                    return;
                }
            }
            
            $plugin_logger = mpai_init_plugin_logger();
            
            if (!$plugin_logger) {
                mpai_log_error('Failed to initialize plugin logger', 'ajax-handler');
                wp_send_json_error('Failed to initialize plugin logger');
                return;
            }
            
            mpai_log_debug('Plugin logger initialized successfully', 'ajax-handler');
            
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
                mpai_log_debug('Returning summary data for plugin logs', 'ajax-handler');
                wp_send_json_success(array(
                    'success' => true,
                    'summary' => $action_counts,
                    'time_period' => $days > 0 ? "past {$days} days" : "all time",
                    'most_active_plugins' => $summary['most_active_plugins'] ?? [],
                    'logs_exist' => $action_counts['total'] > 0,
                    'message' => $action_counts['total'] > 0
                        ? "Found {$action_counts['total']} plugin log entries"
                        : "No plugin logs found for the specified criteria"
                ));
                return;
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
            mpai_log_debug('Retrieved ' . count($logs) . ' plugin logs', 'ajax-handler');
            
            // Get total count for the query
            $count_args = [
                'plugin_name' => $plugin_name,
                'action'      => $action,
                'date_from'   => $date_from
            ];
            $total = $plugin_logger->count_logs($count_args);
            
            // Enhance the logs with readable timestamps
            foreach ($logs as &$log) {
                // Convert the MySQL timestamp to a readable format
                $timestamp = strtotime($log['date_time']);
                $log['readable_date'] = date('F j, Y, g:i a', $timestamp);
                $log['time_ago'] = human_time_diff($timestamp, current_time('timestamp')) . ' ago';
            }
            
            // Generate a natural language summary
            $nl_summary = $this->generate_natural_language_summary($action, $plugin_name, $logs, $action_counts);
            
            // Create a more user-friendly output format
            $formatted_output = "## Plugin Activity Log\n\n";
            $formatted_output .= "Showing plugin activity for the " . ($days > 0 ? "past {$days} days" : "all time") . "\n\n";
            
            // Add summary section
            $formatted_output .= "### Summary\n";
            $formatted_output .= "- Total activities: " . $action_counts['total'] . "\n";
            $formatted_output .= "- Installations: " . $action_counts['installed'] . "\n";
            $formatted_output .= "- Updates: " . $action_counts['updated'] . "\n";
            $formatted_output .= "- Activations: " . $action_counts['activated'] . "\n";
            $formatted_output .= "- Deactivations: " . $action_counts['deactivated'] . "\n";
            $formatted_output .= "- Deletions: " . $action_counts['deleted'] . "\n\n";
            
            // Add detailed logs section
            $formatted_output .= "### Recent Activity\n";
            if (count($logs) > 0) {
                foreach ($logs as $log) {
                    $action_verb = ucfirst($log['action']);
                    $plugin_name = $log['plugin_name'];
                    $version = $log['plugin_version'];
                    $time_ago = $log['time_ago'];
                    $user = isset($log['user_login']) && !empty($log['user_login']) ? " by user {$log['user_login']}" : '';
                    
                    $formatted_output .= "- {$action_verb}: {$plugin_name} v{$version} ({$time_ago}){$user}\n";
                }
            } else {
                $formatted_output .= "No plugin activity found for the specified criteria.\n";
            }
            
            // Include the natural language summary
            $formatted_output .= "\n### Analysis\n" . $nl_summary;
            
            // Format the result for readability
            $result = array(
                'success' => true,
                'summary' => $action_counts,
                'time_period' => $days > 0 ? "past {$days} days" : "all time",
                'total_records' => $total,
                'returned_records' => count($logs),
                'has_more' => $total > count($logs),
                'logs' => $logs,
                'nl_summary' => $nl_summary,
                'formatted_output' => $formatted_output,
                'query' => [
                    'action' => $action,
                    'plugin_name' => $plugin_name,
                    'days' => $days,
                    'limit' => $limit
                ]
            );
            
            mpai_log_info('Successfully retrieved plugin logs, entry count: ' . count($logs), 'ajax-handler');
            wp_send_json_success($result);
        } catch (Exception $e) {
            mpai_log_error('Error in plugin_logs handler: ' . $e->getMessage(), 'ajax-handler', array(
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ));
            wp_send_json_error('Error getting plugin logs: ' . $e->getMessage());
        }
    }
    
    // Legacy method removed as part of refactoring
    
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
                        $plugin_list[] = $log['plugin_name'] . " (" . $log['time_ago'] . ")";
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
                $summary .= " The most recent activity was " . $logs[0]['action'] . " of " . $logs[0]['plugin_name'] . " " . $logs[0]['time_ago'] . ".";
            }
        }
        
        return $summary;
    }
}

// Initialize the AJAX handler
new MPAI_AJAX_Handler();