<?php
/**
 * Test script for activating a plugin
 */

// Load WordPress
// Calculate the path to wp-load.php
$wp_load_path = dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . '/wp-load.php';

// Verify path exists
if (!file_exists($wp_load_path)) {
    echo "Error: wp-load.php not found at {$wp_load_path}<br>";
    // Try alternative relative path
    $wp_load_path = '../../../../wp-load.php';
    echo "Trying alternative path: {$wp_load_path}<br>";
}

require_once($wp_load_path);

// Load the WP API Tool class
require_once dirname(__DIR__) . '/includes/tools/implementations/class-mpai-wp-api-tool.php';

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