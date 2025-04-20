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
        check_ajax_referer('mpai_nonce', 'nonce');
        
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

        // Parse the tool request
        $tool_request = json_decode(stripslashes($_POST['tool_request']), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Try without stripslashes
            $tool_request = json_decode($_POST['tool_request'], true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                mpai_log_warning('Invalid JSON in tool request: ' . json_last_error_msg(), 'ajax-handler');
                wp_send_json_error('Invalid JSON in tool request: ' . json_last_error_msg());
                return;
            }
        }
        
        mpai_log_debug('Processing tool request: ' . json_encode($tool_request), 'ajax-handler');
        
        // Special handling for plugin_logs tool
        if ((isset($tool_request['name']) && $tool_request['name'] === 'plugin_logs') || 
            (isset($tool_request['tool']) && $tool_request['tool'] === 'plugin_logs')) {
            $this->handle_plugin_logs_tool($tool_request);
            return;
        }
        
        // Check for legacy tool IDs and reject them
        $tool_id = '';
        if (isset($tool_request['name'])) {
            $tool_id = $tool_request['name'];
        } elseif (isset($tool_request['tool'])) {
            $tool_id = $tool_request['tool'];
        }
        
        // Reject legacy tool IDs with a clear error message
        if ($tool_id === 'wpcli_new' || $tool_id === 'wp_cli') {
            mpai_log_warning('Rejected request using legacy tool ID: ' . $tool_id, 'ajax-handler');
            wp_send_json_error('Legacy tool ID "' . $tool_id . '" is no longer supported. Please use "wpcli" instead.');
            return;
        }
        
        // Only the standardized 'wpcli' tool ID is supported
        
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
     * Handle plugin_logs tool
     * 
     * @param array $tool_request Tool request data
     */
    private function handle_plugin_logs_tool($tool_request) {
        try {
            // Make sure we have class-mpai-plugin-logger.php
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
            
            // Initialize plugin logger
            $plugin_logger = mpai_init_plugin_logger();
            if (!$plugin_logger) {
                mpai_log_error('Failed to initialize plugin logger', 'ajax-handler');
                wp_send_json_error('Failed to initialize plugin logger');
                return;
            }
            
            // Extract parameters
            $parameters = isset($tool_request['parameters']) ? $tool_request['parameters'] : [];
            
            // In case we have 'tool' instead of 'name' format
            if (empty($parameters) && isset($tool_request['tool']) && isset($tool_request['parameters'])) {
                $parameters = $tool_request['parameters'];
            }
            
            $action = isset($parameters['action']) ? sanitize_text_field($parameters['action']) : '';
            $days = isset($parameters['days']) ? intval($parameters['days']) : 30;
            $limit = isset($parameters['limit']) ? intval($parameters['limit']) : 25;
            
            // Get logs
            $args = [
                'action'    => $action,
                'date_from' => date('Y-m-d H:i:s', strtotime("-{$days} days")),
                'orderby'   => 'date_time',
                'order'     => 'DESC',
                'limit'     => $limit
            ];
            
            $logs = $plugin_logger->get_logs($args);
            
            // Create summary of logs
            $summary = [
                'total' => count($logs),
                'installed' => 0,
                'updated' => 0,
                'activated' => 0,
                'deactivated' => 0,
                'deleted' => 0
            ];
            
            foreach ($logs as $log) {
                if (isset($log['action']) && isset($summary[$log['action']])) {
                    $summary[$log['action']]++;
                }
            }
            
            // Format logs with time_ago
            foreach ($logs as &$log) {
                $timestamp = strtotime($log['date_time']);
                $log['time_ago'] = human_time_diff($timestamp, current_time('timestamp')) . ' ago';
            }
            
            // Create response
            $result = [
                'success' => true,
                'tool' => 'plugin_logs',
                'summary' => $summary,
                'time_period' => "past {$days} days",
                'logs' => $logs,
                'total' => count($logs),
                'result' => "Plugin logs for the past {$days} days: " . count($logs) . " entries found"
            ];
            
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
}

// Initialize the AJAX handler
new MPAI_AJAX_Handler();