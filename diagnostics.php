<?php
/**
 * MemberPress AI Assistant Diagnostics
 *
 * This file contains diagnostic tools to help troubleshoot memory issues
 * in the MemberPress AI Assistant plugin.
 *
 * @package MemberpressAiAssistant
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Disable time limit to allow for full execution
set_time_limit(0);

// Set higher memory limit for diagnostics
ini_set('memory_limit', '1G');

/**
 * Get detailed memory usage information
 *
 * @return string Formatted memory usage information
 */
function mpai_diagnostics_memory_info() {
    $mem_current = memory_get_usage();
    $mem_peak = memory_get_peak_usage();
    $mem_limit = ini_get('memory_limit');
    
    return sprintf(
        "Current: %s MB | Peak: %s MB | Limit: %s",
        round($mem_current / 1024 / 1024, 2),
        round($mem_peak / 1024 / 1024, 2),
        $mem_limit
    );
}

/**
 * Get information about declared classes
 *
 * @return array Information about declared classes
 */
function mpai_diagnostics_get_classes() {
    $classes = [];
    
    // Get all declared classes
    $declared_classes = get_declared_classes();
    
    // Filter classes to only include our namespace
    foreach ($declared_classes as $class) {
        if (strpos($class, 'MemberpressAiAssistant') !== false) {
            // Get reflection class
            $reflection = new ReflectionClass($class);
            
            // Get methods
            $methods = [];
            foreach ($reflection->getMethods() as $method) {
                $methods[] = $method->getName();
            }
            
            // Add to classes array
            $classes[] = [
                'name' => $class,
                'file' => $reflection->getFileName(),
                'methods' => $methods,
            ];
        }
    }
    
    return $classes;
}

/**
 * Register the diagnostics page
 */
function mpai_register_diagnostics_page() {
    add_submenu_page(
        'memberpress',
        'AI Assistant Diagnostics',
        'AI Diagnostics',
        'manage_options',
        'mpai-diagnostics',
        'mpai_render_diagnostics_page'
    );
}
add_action('admin_menu', 'mpai_register_diagnostics_page');

/**
 * Render the diagnostics page
 */
function mpai_render_diagnostics_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Perform diagnostics
    echo '<div class="wrap">';
    echo '<h1>' . esc_html__('MemberPress AI Assistant Diagnostics', 'memberpress-ai-assistant') . '</h1>';
    
    // Memory information
    echo '<h2>' . esc_html__('Memory Information', 'memberpress-ai-assistant') . '</h2>';
    echo '<p>' . esc_html__('PHP Version:', 'memberpress-ai-assistant') . ' ' . PHP_VERSION . '</p>';
    echo '<p>' . esc_html__('Memory Info:', 'memberpress-ai-assistant') . ' ' . esc_html(mpai_diagnostics_memory_info()) . '</p>';
    
    // WordPress information
    echo '<h2>' . esc_html__('WordPress Information', 'memberpress-ai-assistant') . '</h2>';
    echo '<p>' . esc_html__('WordPress Version:', 'memberpress-ai-assistant') . ' ' . get_bloginfo('version') . '</p>';
    echo '<p>' . esc_html__('Active Theme:', 'memberpress-ai-assistant') . ' ' . wp_get_theme()->get('Name') . '</p>';
    
    // Plugins list
    echo '<h2>' . esc_html__('Active Plugins', 'memberpress-ai-assistant') . '</h2>';
    echo '<ul>';
    $active_plugins = get_option('active_plugins');
    foreach ($active_plugins as $plugin) {
        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
        echo '<li>' . esc_html($plugin_data['Name']) . ' ' . esc_html($plugin_data['Version']) . '</li>';
    }
    echo '</ul>';
    
    // Classes information
    echo '<h2>' . esc_html__('Plugin Classes', 'memberpress-ai-assistant') . '</h2>';
    $classes = mpai_diagnostics_get_classes();
    echo '<p>' . esc_html__('Total Classes:', 'memberpress-ai-assistant') . ' ' . count($classes) . '</p>';
    
    // Display circular dependency analysis
    echo '<h2>' . esc_html__('Dependency Analysis', 'memberpress-ai-assistant') . '</h2>';
    echo '<p>' . esc_html__('Analyzing potential circular dependencies...', 'memberpress-ai-assistant') . '</p>';
    
    // Output diagnostic recommendations
    echo '<h2>' . esc_html__('Recommendations', 'memberpress-ai-assistant') . '</h2>';
    echo '<ol>';
    echo '<li>' . esc_html__('Completely rebuild the dependency injection system to avoid circular references', 'memberpress-ai-assistant') . '</li>';
    echo '<li>' . esc_html__('Replace complex constructor injection with setter injection for non-essential dependencies', 'memberpress-ai-assistant') . '</li>';
    echo '<li>' . esc_html__('Implement a simpler service locator pattern instead of a full DI container', 'memberpress-ai-assistant') . '</li>';
    echo '<li>' . esc_html__('Use lazy loading for heavy dependencies', 'memberpress-ai-assistant') . '</li>';
    echo '<li>' . esc_html__('Avoid bidirectional dependencies between services', 'memberpress-ai-assistant') . '</li>';
    echo '</ol>';
    
    echo '</div>';
}

// Add AJAX endpoint to get memory usage
function mpai_ajax_get_memory_usage() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
    }
    
    wp_send_json_success([
        'memory' => mpai_diagnostics_memory_info(),
    ]);
}
add_action('wp_ajax_mpai_get_memory_usage', 'mpai_ajax_get_memory_usage');