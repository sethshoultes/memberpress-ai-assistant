<?php
/**
 * This is the fix for the direct-ajax-handler.php file
 * 
 * This adds a special plugin_logs action handler that bypasses the admin check
 * and allows any logged-in user to access plugin logs.
 */

// Update the permission check at the top of direct-ajax-handler.php:
// Replace:
/*
// Check if user is logged in and is admin
if (!current_user_can('manage_options')) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(array(
        'success' => false,
        'message' => 'Permission denied'
    ));
    exit;
}
*/

// With:
// Check if user is logged in (only for certain actions)
if (isset($_POST['action']) && $_POST['action'] === 'plugin_logs') {
    // For plugin_logs, only require being logged in
    if (!is_user_logged_in()) {
        header('HTTP/1.1 403 Forbidden');
        echo json_encode(array(
            'success' => false,
            'message' => 'Permission denied - must be logged in'
        ));
        exit;
    }
} else {
    // For all other actions, require admin privileges
    if (!current_user_can('manage_options')) {
        header('HTTP/1.1 403 Forbidden');
        echo json_encode(array(
            'success' => false,
            'message' => 'Permission denied - admin privileges required'
        ));
        exit;
    }
}

// Add this case in the switch statement:
/*
case 'plugin_logs':
    // Direct plugin logs handler for AI tool calls
    if (!is_user_logged_in()) {
        header('HTTP/1.1 403 Forbidden');
        echo json_encode(array(
            'success' => false,
            'message' => 'User must be logged in'
        ));
        exit;
    }

    // Include the plugin logger
    require_once(dirname(__FILE__) . '/class-mpai-plugin-logger.php');
    $plugin_logger = mpai_init_plugin_logger();

    if (!$plugin_logger) {
        echo json_encode(array(
            'success' => false,
            'message' => 'Failed to initialize plugin logger'
        ));
        exit;
    }

    // Get parameters
    $action_type = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : '';
    $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
    
    // Get logs
    $args = array(
        'action'    => $action_type,
        'date_from' => date('Y-m-d H:i:s', strtotime("-{$days} days")),
        'orderby'   => 'date_time',
        'order'     => 'DESC',
        'limit'     => 25
    );
    
    $logs = $plugin_logger->get_logs($args);
    
    // Count logs by action
    $summary = array(
        'total' => count($logs),
        'installed' => 0,
        'updated' => 0,
        'activated' => 0,
        'deactivated' => 0,
        'deleted' => 0
    );
    
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
    
    echo json_encode(array(
        'success' => true,
        'tool' => 'plugin_logs',
        'summary' => $summary,
        'time_period' => "past {$days} days",
        'logs' => $logs,
        'total' => count($logs),
        'result' => "Plugin logs for the past {$days} days: " . count($logs) . " entries found"
    ));
    break;
*/