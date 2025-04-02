<?php
/**
 * Plugin Logs Tool Test
 */

// Load WordPress
define('WP_USE_THEMES', false);
require_once('../../../../../../wp-load.php');

// Add error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if plugin logger exists and is enabled
if (function_exists('mpai_init_plugin_logger')) {
    echo "<h2>Plugin Logger Function Exists</h2>";
    $plugin_logger = mpai_init_plugin_logger();
    
    if ($plugin_logger) {
        echo "<p>Plugin Logger Initialized Successfully</p>";
        
        // Check if the table exists
        global $wpdb;
        $table_name = $wpdb->prefix . 'mpai_plugin_logs';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
        
        echo "<p>Plugin Logs Table Exists: " . ($table_exists ? 'Yes' : 'No') . "</p>";
        
        if ($table_exists) {
            // Get log count
            $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
            echo "<p>Total log entries: {$count}</p>";
            
            // Get recent logs
            echo "<h3>Recent Plugin Logs (30 days)</h3>";
            $logs = $plugin_logger->get_logs([
                'days' => 30,
                'limit' => 10
            ]);
            
            if (!empty($logs)) {
                echo "<table border='1'>";
                echo "<tr><th>ID</th><th>Date</th><th>Action</th><th>Plugin</th><th>Version</th><th>User</th></tr>";
                
                foreach ($logs as $log) {
                    echo "<tr>";
                    echo "<td>{$log['id']}</td>";
                    echo "<td>{$log['date_time']}</td>";
                    echo "<td>{$log['action']}</td>";
                    echo "<td>{$log['plugin_name']}</td>";
                    echo "<td>{$log['plugin_version']}</td>";
                    echo "<td>{$log['user_login']}</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
            } else {
                echo "<p>No logs found in the last 30 days</p>";
            }
            
            // Get activated plugin logs
            echo "<h3>Recently Activated Plugins (30 days)</h3>";
            $activated_logs = $plugin_logger->get_logs([
                'action' => 'activated',
                'days' => 30,
                'limit' => 10
            ]);
            
            if (!empty($activated_logs)) {
                echo "<table border='1'>";
                echo "<tr><th>ID</th><th>Date</th><th>Plugin</th><th>Version</th><th>User</th></tr>";
                
                foreach ($activated_logs as $log) {
                    echo "<tr>";
                    echo "<td>{$log['id']}</td>";
                    echo "<td>{$log['date_time']}</td>";
                    echo "<td>{$log['plugin_name']}</td>";
                    echo "<td>{$log['plugin_version']}</td>";
                    echo "<td>{$log['user_login']}</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
            } else {
                echo "<p>No activation logs found in the last 30 days</p>";
            }
        }
    } else {
        echo "<p>Failed to initialize Plugin Logger</p>";
    }
} else {
    echo "<h2>Plugin Logger Function Not Found</h2>";
    echo "<p>The mpai_init_plugin_logger function is not available.</p>";
}

// Test the Plugin Logs Tool
echo "<h2>Plugin Logs Tool Test</h2>";

// Try to load the tool
$tool_file = __DIR__ . '/includes/tools/implementations/class-mpai-plugin-logs-tool.php';
if (file_exists($tool_file)) {
    echo "<p>Plugin Logs Tool file exists at: {$tool_file}</p>";
    require_once($tool_file);
    
    if (class_exists('MPAI_Plugin_Logs_Tool')) {
        echo "<p>MPAI_Plugin_Logs_Tool class loaded successfully</p>";
        
        // Initialize the tool
        $tool = new MPAI_Plugin_Logs_Tool();
        
        // Test execution with default parameters
        echo "<h3>Tool Execution Test</h3>";
        $result = $tool->execute([
            'days' => 30,
            'limit' => 10
        ]);
        
        echo "<pre>";
        print_r($result);
        echo "</pre>";
    } else {
        echo "<p>Failed to load MPAI_Plugin_Logs_Tool class</p>";
    }
} else {
    echo "<p>Plugin Logs Tool file not found at: {$tool_file}</p>";
}

// Check Tool Registry
echo "<h2>Tool Registry Test</h2>";
$registry_file = __DIR__ . '/includes/tools/class-mpai-tool-registry.php';
if (file_exists($registry_file)) {
    echo "<p>Tool Registry file exists at: {$registry_file}</p>";
    
    if (!class_exists('MPAI_Tool_Registry')) {
        require_once($registry_file);
    }
    
    if (class_exists('MPAI_Tool_Registry')) {
        echo "<p>MPAI_Tool_Registry class loaded successfully</p>";
        
        // Initialize registry
        $registry = new MPAI_Tool_Registry();
        
        // Check if plugin_logs tool is registered
        $available_tools = $registry->get_available_tools();
        
        echo "<p>Available tools: " . implode(", ", array_keys($available_tools)) . "</p>";
        
        if (isset($available_tools['plugin_logs'])) {
            echo "<p>plugin_logs tool is registered in the registry</p>";
        } else {
            echo "<p>plugin_logs tool is NOT registered in the registry</p>";
        }
    } else {
        echo "<p>Failed to load MPAI_Tool_Registry class</p>";
    }
} else {
    echo "<p>Tool Registry file not found at: {$registry_file}</p>";
}

// Check Context Manager
echo "<h2>Context Manager Test</h2>";
$context_manager_file = __DIR__ . '/includes/class-mpai-context-manager.php';
if (file_exists($context_manager_file)) {
    echo "<p>Context Manager file exists at: {$context_manager_file}</p>";
    
    if (!class_exists('MPAI_Context_Manager')) {
        require_once($context_manager_file);
    }
    
    if (class_exists('MPAI_Context_Manager')) {
        echo "<p>MPAI_Context_Manager class loaded successfully</p>";
        
        // Initialize context manager
        $context_manager = new MPAI_Context_Manager();
        
        // Get available tools
        $available_tools = $context_manager->get_available_tools();
        
        echo "<p>Available tools in Context Manager: " . implode(", ", array_keys($available_tools)) . "</p>";
        
        if (isset($available_tools['plugin_logs'])) {
            echo "<p>plugin_logs tool is available in the context manager</p>";
            
            // Try to execute the tool
            echo "<h3>Context Manager Tool Execution Test</h3>";
            $result = $context_manager->process_tool_request([
                'name' => 'plugin_logs',
                'parameters' => [
                    'days' => 30,
                    'limit' => 10
                ]
            ]);
            
            echo "<pre>";
            print_r($result);
            echo "</pre>";
        } else {
            echo "<p>plugin_logs tool is NOT available in the context manager</p>";
        }
    } else {
        echo "<p>Failed to load MPAI_Context_Manager class</p>";
    }
} else {
    echo "<p>Context Manager file not found at: {$context_manager_file}</p>";
}