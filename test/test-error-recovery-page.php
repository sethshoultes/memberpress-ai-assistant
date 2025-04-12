<?php
/**
 * Error Recovery System Test Page
 *
 * A standalone page to test the Error Recovery System functionality
 */

// Ensure we're running in WordPress
if (!defined('ABSPATH')) {
    $wp_load_path = dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/wp-load.php';
    if (file_exists($wp_load_path)) {
        require_once($wp_load_path);
    } else {
        die('WordPress not found. This script must be run within the WordPress context.');
    }
}

// Ensure user is logged in and has proper permissions
if (!current_user_can('manage_options')) {
    wp_die('You do not have sufficient permissions to access this page.');
}

// Define plugin directory
if (!defined('MPAI_PLUGIN_DIR')) {
    define('MPAI_PLUGIN_DIR', plugin_dir_path(dirname(__FILE__)));
}

// Load necessary files
require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-error-recovery.php';

// Load Plugin Logger
if (!class_exists('MPAI_Plugin_Logger')) {
    require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-plugin-logger.php';
}

// Ensure we have a plugin logger function 
if (!function_exists('mpai_init_plugin_logger')) {
    function mpai_init_plugin_logger() {
        return MPAI_Plugin_Logger::get_instance();
    }
}

// Ensure we have an error recovery function
if (!function_exists('mpai_init_error_recovery')) {
    function mpai_init_error_recovery() {
        return MPAI_Error_Recovery::get_instance();
    }
}

// Initialize error recovery system
$error_recovery = mpai_init_error_recovery();

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>MemberPress AI Assistant - Error Recovery System Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1 { color: #23282d; }
        hr { margin: 25px 0; }
        .container { max-width: 1200px; margin: 0 auto; }
        .test-panel { background: #f8f9fa; border: 1px solid #ddd; border-radius: 5px; padding: 20px; margin-bottom: 20px; }
        .test-title { font-size: 18px; font-weight: bold; margin-bottom: 15px; }
        .test-desc { margin-bottom: 15px; }
        .test-controls { margin-bottom: 15px; }
        .test-button { padding: 8px 15px; background: #0073aa; color: white; border: none; border-radius: 3px; cursor: pointer; }
        .test-button:hover { background: #006291; }
        .results { background: #fff; border: 1px solid #ddd; border-radius: 3px; padding: 15px; margin-top: 15px; display: none; max-height: 500px; overflow: auto; }
        .test-success { color: #46b450; }
        .test-error { color: #dc3232; }
        pre { background: #f0f0f0; padding: 10px; overflow: auto; max-height: 300px; }
        ul.test-list { padding-left: 20px; }
        ul.test-list li { margin-bottom: 5px; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        h3 { margin-top: 25px; margin-bottom: 10px; }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Error Recovery Test
            document.getElementById('run-error-recovery').addEventListener('click', function() {
                const resultsDiv = document.getElementById('error-recovery-results');
                resultsDiv.innerHTML = '<p>Running tests, please wait...</p>';
                resultsDiv.style.display = 'block';
                
                // Send AJAX request
                const xhr = new XMLHttpRequest();
                xhr.open('POST', '<?php echo MPAI_PLUGIN_DIR; ?>includes/direct-ajax-handler.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        if (xhr.status === 200) {
                            try {
                                const response = JSON.parse(xhr.responseText);
                                showErrorRecoveryResults(response);
                            } catch (e) {
                                resultsDiv.innerHTML = '<p class="test-error">Error parsing response: ' + e.message + '</p><pre>' + xhr.responseText + '</pre>';
                            }
                        } else {
                            resultsDiv.innerHTML = '<p class="test-error">Error: ' + xhr.status + ' - ' + xhr.statusText + '</p>';
                        }
                    }
                };
                xhr.send('action=test_error_recovery');
            });
            
            function showErrorRecoveryResults(data) {
                const resultsDiv = document.getElementById('error-recovery-results');
                let html = '';
                
                if (data.success) {
                    html += '<h3 class="test-success">All tests passed successfully!</h3>';
                } else {
                    html += '<h3 class="test-error">Some tests failed.</h3>';
                }
                
                html += '<p>' + data.message + '</p>';
                
                // Tests table
                html += '<h3>Test Results</h3>';
                html += '<table>';
                html += '<tr><th>Test</th><th>Status</th><th>Message</th></tr>';
                
                for (const [testName, testResult] of Object.entries(data.data.tests)) {
                    // Format the test name to be more readable
                    const prettyName = testName
                        .split('_')
                        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                        .join(' ');
                        
                    html += '<tr>';
                    html += '<td>' + prettyName + '</td>';
                    html += '<td>' + (testResult.success ? 
                        '<span class="test-success">✅ Pass</span>' : 
                        '<span class="test-error">❌ Fail</span>') + '</td>';
                    html += '<td>' + testResult.message + '</td>';
                    html += '</tr>';
                }
                
                html += '</table>';
                
                // Timing information
                html += '<h3>Timing (milliseconds)</h3>';
                html += '<table>';
                html += '<tr><th>Operation</th><th>Time (ms)</th></tr>';
                
                for (const [timerName, timerValue] of Object.entries(data.data.timing)) {
                    html += '<tr>';
                    html += '<td>' + timerName + '</td>';
                    html += '<td>' + (timerValue * 1000).toFixed(2) + ' ms</td>';
                    html += '</tr>';
                }
                
                html += '</table>';
                
                // Test details
                html += '<h3>Test Details</h3>';
                html += '<ul class="test-list">';
                
                for (const [testName, testResult] of Object.entries(data.data.tests)) {
                    // Format the test name to be more readable
                    const prettyName = testName
                        .split('_')
                        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                        .join(' ');
                    
                    html += '<li><strong>' + prettyName + ':</strong> ';
                    if (testResult.details) {
                        html += '<a href="#" onclick="toggleDetail(\'' + testName + '\'); return false;">Show/Hide Details</a>';
                        html += '<div id="detail-' + testName + '" style="display:none;">';
                        html += '<pre>' + JSON.stringify(testResult.details, null, 2) + '</pre>';
                        html += '</div>';
                    } else {
                        html += testResult.message;
                    }
                    html += '</li>';
                }
                
                html += '</ul>';
                
                resultsDiv.innerHTML = html;
            }
            
            window.toggleDetail = function(testName) {
                const detailDiv = document.getElementById('detail-' + testName);
                if (detailDiv.style.display === 'none') {
                    detailDiv.style.display = 'block';
                } else {
                    detailDiv.style.display = 'none';
                }
            };
        });
    </script>
</head>
<body>
    <div class="container">
        <h1>MemberPress AI Assistant - Error Recovery System Test</h1>
        <p>This page tests the functionality of the Error Recovery System, which provides standardized error handling and recovery mechanisms.</p>
        <hr>
        
        <!-- Error Recovery System Test -->
        <div class="test-panel">
            <div class="test-title">Error Recovery System Test</div>
            <div class="test-desc">
                Comprehensive testing of the Error Recovery System including:
                <ul>
                    <li>Basic error creation with context and severity</li>
                    <li>Recovery mechanisms with retry capability</li>
                    <li>Fallback strategy implementation</li>
                    <li>Circuit breaker pattern for automatic service protection</li>
                    <li>Error formatting for user-friendly display</li>
                </ul>
            </div>
            <div class="test-controls">
                <button id="run-error-recovery" class="test-button">Run Test</button>
                <a href="test-error-recovery-direct.php" class="test-button" target="_blank">Direct Test (Debugging)</a>
            </div>
            <div id="error-recovery-results" class="results"></div>
        </div>
        
        <hr>
        <p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=memberpress-ai-assistant')); ?>" class="test-button">Back to MemberPress AI Assistant</a>
        </p>
        <p>
            <small>MemberPress AI Assistant Version: <?php echo defined('MPAI_VERSION') ? MPAI_VERSION : 'unknown'; ?></small><br>
            <small>WordPress Version: <?php echo get_bloginfo('version'); ?></small><br>
            <small>PHP Version: <?php echo phpversion(); ?></small>
        </p>
    </div>
</body>
</html>