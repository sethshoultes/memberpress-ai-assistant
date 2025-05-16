<?php
/**
 * Debug Mode File
 *
 * This file enables debug mode for the MemberPress AI Assistant plugin.
 * Include this file in wp-config.php to enable debug mode.
 * 
 * Example usage in wp-config.php:
 * require_once(ABSPATH . 'wp-content/plugins/memberpress-ai-assistant/debug.php');
 */

// Enable debug mode
add_filter('mpai_debug_mode', '__return_true');

// Set WordPress debug constants if not already set
if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', true);
}

if (!defined('WP_DEBUG_LOG')) {
    define('WP_DEBUG_LOG', true);
}

if (!defined('WP_DEBUG_DISPLAY')) {
    define('WP_DEBUG_DISPLAY', false);
}

// Log that debug mode is enabled
add_action('plugins_loaded', function() {
    error_log('MPAI DEBUG MODE: Enabled via debug.php file');
    error_log('MPAI MEMORY: ' . memberpress_ai_assistant_memory_info());
}, 1);

/**
 * Get detailed memory usage information
 *
 * @return string Formatted memory usage information
 */
function memberpress_ai_assistant_memory_info() {
    $mem_current = memory_get_usage();
    $mem_peak = memory_get_peak_usage();
    $mem_limit = ini_get('memory_limit');
    
    return sprintf(
        "Current: %s MB | Peak: %s MB | Limit: %s | Available: %s%%",
        round($mem_current / 1024 / 1024, 2),
        round($mem_peak / 1024 / 1024, 2),
        $mem_limit,
        round((1 - ($mem_peak / (intval($mem_limit) * 1024 * 1024))) * 100, 2)
    );
}

// Add notice in admin
add_action('admin_notices', function() {
    // Only show on plugin pages
    $screen = get_current_screen();
    if (!$screen || strpos($screen->id, 'mpai') === false) {
        return;
    }
    
    echo '<div class="notice notice-warning">';
    echo '<p><strong>' . esc_html__('MemberPress AI Assistant Debug Mode', 'memberpress-ai-assistant') . '</strong></p>';
    echo '<p>' . esc_html__('Debug mode is enabled. Detailed logs are being written to the WordPress debug log.', 'memberpress-ai-assistant') . '</p>';
    echo '</div>';
    
    // Add memory usage information
    echo '<div class="notice notice-info">';
    echo '<p><strong>' . esc_html__('Memory Usage', 'memberpress-ai-assistant') . '</strong></p>';
    echo '<p>' . esc_html(memberpress_ai_assistant_memory_info()) . '</p>';
    echo '</div>';
});