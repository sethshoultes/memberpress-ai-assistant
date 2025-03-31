<?php
/**
 * Test script for activating a plugin
 */

// Load WordPress
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';

// Load the WP API Tool class
require_once __DIR__ . '/includes/tools/implementations/class-mpai-wp-api-tool.php';

// Create the tool instance
$wp_api_tool = new MPAI_WP_API_Tool();

// Get a list of plugins first
try {
    echo "Getting plugin list...\n";
    $plugins = $wp_api_tool->execute(array(
        'action' => 'get_plugins'
    ));
    
    echo "Found plugins:\n";
    foreach ($plugins['plugins'] as $plugin) {
        echo "- {$plugin['name']} ({$plugin['plugin_path']}): {$plugin['status']}\n";
    }
    
    echo "\n------------------------------------\n\n";
} catch (Exception $e) {
    echo "Error getting plugins: " . $e->getMessage() . "\n";
}

// Activate the plugin
try {
    echo "Attempting to activate MemberPress CoachKit...\n";
    $result = $wp_api_tool->execute(array(
        'action' => 'activate_plugin',
        'plugin' => 'memberpress-coachkit/main.php'
    ));
    
    echo "Plugin activation attempt complete.\n";
    echo "Result: " . print_r($result, true) . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}