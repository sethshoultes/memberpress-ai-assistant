<?php
/**
 * Test Plugin List Implementation
 * 
 * Direct script to test our updated plugin list implementation
 */

// Load WordPress
define('WP_USE_THEMES', false);
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php');

// Ensure this can only be accessed by admins
if (!current_user_can('manage_options')) {
    die('You do not have permission to access this page.');
}

// Set header for plain text output
header('Content-Type: text/plain');

// Debugging information
echo "Testing plugin list implementation\n";
echo "----------------------------------------\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo "PHP Version: " . phpversion() . "\n";
echo "WordPress Version: " . get_bloginfo('version') . "\n";
echo "----------------------------------------\n\n";

// Make sure database is connected
global $wpdb;
echo "Testing Database Connection\n";
echo "----------------------------------------\n";
try {
    $db_test = $wpdb->get_var("SELECT 1");
    if ($db_test === '1') {
        echo "Database connection successful.\n";
    } else {
        echo "WARNING: Database test returned unexpected result: " . var_export($db_test, true) . "\n";
    }
} catch (Exception $e) {
    echo "ERROR: Database connection test failed: " . $e->getMessage() . "\n";
}

echo "WordPress Database Prefix: " . $wpdb->prefix . "\n";
echo "----------------------------------------\n\n";

// Check for required files
echo "Checking for required files:\n";
$plugin_dir = dirname(__FILE__);
$files_to_check = [
    '/includes/class-mpai-context-manager.php',
    '/includes/tools/implementations/class-mpai-wp-api-tool.php',
    '/includes/tools/implementations/class-mpai-wpcli-tool.php',
    '/includes/class-mpai-plugin-logger.php',
];

foreach ($files_to_check as $file) {
    $full_path = $plugin_dir . $file;
    if (file_exists($full_path)) {
        echo "✓ " . $file . " exists\n";
    } else {
        echo "✗ " . $file . " MISSING\n";
    }
}
echo "----------------------------------------\n\n";

// Initialize all relevant classes
echo "Loading required classes...\n";
require_once($plugin_dir . '/includes/class-mpai-plugin-logger.php');
require_once($plugin_dir . '/includes/class-mpai-context-manager.php');
require_once($plugin_dir . '/includes/tools/implementations/class-mpai-wp-api-tool.php');
require_once($plugin_dir . '/includes/tools/implementations/class-mpai-wpcli-tool.php');
echo "All classes loaded successfully.\n\n";

// Output version information
echo "Context Manager Version: " . MPAI_Context_Manager::VERSION . "\n\n";

// Test plugin logger table creation directly
echo "Testing Plugin Logger Table Creation:\n";
echo "----------------------------------------\n";
try {
    $plugin_logger = mpai_init_plugin_logger();
    
    if (!$plugin_logger) {
        echo "ERROR: Failed to initialize plugin logger.\n\n";
    } else {
        // Call the public table creation method with force=true
        $table_created = $plugin_logger->maybe_create_table(true);
        
        if ($table_created) {
            echo "SUCCESS: Plugin logger table created or verified successfully.\n";
            
            // Check if table has data
            global $wpdb;
            $table_name = $wpdb->prefix . 'mpai_plugin_logs';
            $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
            echo "Table has " . intval($count) . " records.\n";
            
            // Show sample records
            if ($count > 0) {
                $records = $wpdb->get_results("SELECT * FROM $table_name ORDER BY date_time DESC LIMIT 3", ARRAY_A);
                echo "Sample records:\n";
                foreach ($records as $record) {
                    echo "- " . $record['plugin_name'] . " - " . $record['action'] . " on " . $record['date_time'] . "\n";
                }
            }
        } else {
            echo "ERROR: Failed to create plugin logger table.\n";
        }
    }
} catch (Exception $e) {
    echo "EXCEPTION in plugin logger test: " . $e->getMessage() . "\n";
}
echo "----------------------------------------\n\n";

// Test the WP API Tool directly
echo "Testing WP API Tool get_plugins method:\n";
echo "----------------------------------------\n";
try {
    $wp_api_tool = new MPAI_WP_API_Tool();
    $result = $wp_api_tool->execute(array(
        'action' => 'get_plugins',
        'format' => 'table'
    ));
    
    if (is_array($result) && isset($result['table_data'])) {
        echo "SUCCESS: WP API Tool returned formatted table data:\n\n";
        echo $result['table_data'] . "\n\n";
        
        // Also output if plugin logger is available
        echo "Plugin logger available: " . ($result['plugin_logger_available'] ? 'Yes' : 'No') . "\n\n";
    } else {
        echo "ERROR: WP API Tool returned unexpected result format:\n";
        var_dump($result);
    }
} catch (Exception $e) {
    echo "EXCEPTION in WP API Tool: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n\n";
}

// Test the WP CLI Tool
echo "Testing WP CLI Tool wp plugin list command:\n";
echo "----------------------------------------\n";
try {
    $wp_cli_tool = new MPAI_WP_CLI_Tool();
    $result = $wp_cli_tool->execute(array(
        'command' => 'wp plugin list'
    ));
    
    echo "SUCCESS: WP CLI Tool returned:\n\n";
    echo $result . "\n\n";
} catch (Exception $e) {
    echo "EXCEPTION in WP CLI Tool: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n\n";
}

// Test the Context Manager directly
echo "Testing Context Manager run_command:\n";
echo "----------------------------------------\n";
try {
    $context_manager = new MPAI_Context_Manager();
    $result = $context_manager->run_command('wp plugin list');
    
    echo "SUCCESS: Context Manager returned:\n\n";
    echo $result . "\n\n";
} catch (Exception $e) {
    echo "EXCEPTION in Context Manager: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n\n";
}

// Display WordPress plugin data
echo "Actual WordPress Plugins:\n";
echo "----------------------------------------\n";
if (!function_exists('get_plugins')) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}
if (!function_exists('is_plugin_active')) {
    include_once ABSPATH . 'wp-admin/includes/plugin.php';
}
$plugins = get_plugins();
echo "Total plugins: " . count($plugins) . "\n\n";
echo "Name\tStatus\tVersion\n";
foreach ($plugins as $plugin_path => $plugin_data) {
    $status = is_plugin_active($plugin_path) ? 'active' : 'inactive';
    echo $plugin_data['Name'] . "\t" . $status . "\t" . $plugin_data['Version'] . "\n";
}

// Output PHP include path and loaded files
echo "\nPHP Include Path: " . get_include_path() . "\n\n";

// Test if the plugin has an OpenAI API integration at all
echo "Testing for OpenAI Integration:\n";
echo "----------------------------------------\n";
if (class_exists('MPAI_API_Router')) {
    echo "Class MPAI_API_Router exists.\n";
} else {
    echo "Class MPAI_API_Router does not exist.\n";
}

// End of test
echo "\n----------------------------------------\n";
echo "Test completed at " . date('Y-m-d H:i:s') . "\n";
?>