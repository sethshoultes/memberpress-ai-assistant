<?php
/**
 * Debug Information
 * 
 * Simple script to show debug information about the WordPress environment
 */

// Load WordPress core
require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/wp-load.php');

// Check if user is logged in and is admin
if (!current_user_can('manage_options')) {
    wp_die('You do not have sufficient permissions to access this page.');
}

// Generate a test nonce
$test_nonce = wp_create_nonce('mpai_nonce');
$verify_nonce = wp_verify_nonce($test_nonce, 'mpai_nonce');

// Test REST API
$rest_url = rest_url();
$wp_version = get_bloginfo('version');
$home_url = home_url();
$site_url = site_url();
$admin_url = admin_url();
$ajax_url = admin_url('admin-ajax.php');
$cookie_domain = defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : '';
$siteurl = get_option('siteurl');
$home = get_option('home');

// Create a test AJAX request
$test_ajax = admin_url('admin-ajax.php');
$test_nonce = wp_create_nonce('wp_rest');
$memberpress_active = class_exists('MeprAppCtrl');
$mpai_active = class_exists('MemberPress_AI_Assistant');
$dev_tools_active = class_exists('MeprRestRoutes');

// Check if curl is available
$curl_available = function_exists('curl_version');
$curl_info = $curl_available ? curl_version() : null;

// Check if OpenSSL is available
$openssl_available = extension_loaded('openssl');
$openssl_version = $openssl_available ? OPENSSL_VERSION_TEXT : null;

// Check database
global $wpdb;
$db_prefix = $wpdb->prefix;
$db_name = defined('DB_NAME') ? DB_NAME : 'Unknown';
$db_host = defined('DB_HOST') ? DB_HOST : 'Unknown';
$db_tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}mpai_%'", ARRAY_N);
?>
<!DOCTYPE html>
<html>
<head>
    <title>MemberPress AI Assistant Debug Info</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            margin: 20px;
            background: #f0f0f1;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border: 1px solid #c3c4c7;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        h1 {
            color: #1d2327;
            font-size: 23px;
            font-weight: 400;
            margin-top: 0;
        }
        h2 {
            font-size: 18px;
            margin-top: 30px;
        }
        pre {
            background: #f6f7f7;
            padding: 15px;
            overflow-x: auto;
            border: 1px solid #dcdcde;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 15px;
        }
        th, td {
            text-align: left;
            padding: 8px;
            border: 1px solid #dcdcde;
        }
        th {
            background-color: #f6f7f7;
        }
        tr:nth-child(even) {
            background-color: #f6f7f7;
        }
        .success {
            color: #00a32a;
        }
        .error {
            color: #d63638;
        }
        .warning {
            color: #dba617;
        }
        .button {
            background: #2271b1;
            border-color: #2271b1;
            color: #fff;
            text-decoration: none;
            text-shadow: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            border-width: 1px;
            border-style: solid;
            display: inline-block;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>MemberPress AI Assistant Debug Info</h1>
        
        <h2>WordPress Environment</h2>
        <table>
            <tr>
                <th>Setting</th>
                <th>Value</th>
            </tr>
            <tr>
                <td>WordPress Version</td>
                <td><?php echo $wp_version; ?></td>
            </tr>
            <tr>
                <td>Site URL</td>
                <td><?php echo $site_url; ?></td>
            </tr>
            <tr>
                <td>Home URL</td>
                <td><?php echo $home_url; ?></td>
            </tr>
            <tr>
                <td>REST API URL</td>
                <td><?php echo $rest_url; ?></td>
            </tr>
            <tr>
                <td>Admin URL</td>
                <td><?php echo $admin_url; ?></td>
            </tr>
            <tr>
                <td>AJAX URL</td>
                <td><?php echo $ajax_url; ?></td>
            </tr>
            <tr>
                <td>Cookie Domain</td>
                <td><?php echo $cookie_domain; ?></td>
            </tr>
            <tr>
                <td>Site URL (option)</td>
                <td><?php echo $siteurl; ?></td>
            </tr>
            <tr>
                <td>Home (option)</td>
                <td><?php echo $home; ?></td>
            </tr>
        </table>
        
        <h2>Plugin Status</h2>
        <table>
            <tr>
                <th>Plugin</th>
                <th>Status</th>
            </tr>
            <tr>
                <td>MemberPress</td>
                <td class="<?php echo $memberpress_active ? 'success' : 'error'; ?>">
                    <?php echo $memberpress_active ? 'Active' : 'Not Active'; ?>
                </td>
            </tr>
            <tr>
                <td>MemberPress Developer Tools</td>
                <td class="<?php echo $dev_tools_active ? 'success' : 'error'; ?>">
                    <?php echo $dev_tools_active ? 'Active' : 'Not Active'; ?>
                </td>
            </tr>
            <tr>
                <td>MemberPress AI Assistant</td>
                <td class="<?php echo $mpai_active ? 'success' : 'error'; ?>">
                    <?php echo $mpai_active ? 'Active' : 'Not Active'; ?>
                </td>
            </tr>
        </table>
        
        <h2>Security Tests</h2>
        <table>
            <tr>
                <th>Test</th>
                <th>Result</th>
            </tr>
            <tr>
                <td>Generate Nonce</td>
                <td><?php echo $test_nonce; ?></td>
            </tr>
            <tr>
                <td>Verify Nonce</td>
                <td class="<?php echo $verify_nonce ? 'success' : 'error'; ?>">
                    <?php echo $verify_nonce ? 'Success' : 'Failed'; ?>
                </td>
            </tr>
        </table>
        
        <h2>Database</h2>
        <table>
            <tr>
                <th>Setting</th>
                <th>Value</th>
            </tr>
            <tr>
                <td>Database Name</td>
                <td><?php echo $db_name; ?></td>
            </tr>
            <tr>
                <td>Database Host</td>
                <td><?php echo $db_host; ?></td>
            </tr>
            <tr>
                <td>Table Prefix</td>
                <td><?php echo $db_prefix; ?></td>
            </tr>
            <tr>
                <td>Plugin Tables</td>
                <td>
                    <?php
                    if (empty($db_tables)) {
                        echo '<span class="warning">No MPAI tables found</span>';
                    } else {
                        echo '<ul>';
                        foreach ($db_tables as $table) {
                            echo '<li>' . $table[0] . '</li>';
                        }
                        echo '</ul>';
                    }
                    ?>
                </td>
            </tr>
        </table>
        
        <h2>PHP Environment</h2>
        <table>
            <tr>
                <th>Setting</th>
                <th>Value</th>
            </tr>
            <tr>
                <td>PHP Version</td>
                <td><?php echo phpversion(); ?></td>
            </tr>
            <tr>
                <td>cURL Available</td>
                <td class="<?php echo $curl_available ? 'success' : 'error'; ?>">
                    <?php echo $curl_available ? 'Yes' : 'No'; ?>
                </td>
            </tr>
            <?php if ($curl_available): ?>
            <tr>
                <td>cURL Version</td>
                <td><?php echo $curl_info['version']; ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <td>OpenSSL Available</td>
                <td class="<?php echo $openssl_available ? 'success' : 'error'; ?>">
                    <?php echo $openssl_available ? 'Yes' : 'No'; ?>
                </td>
            </tr>
            <?php if ($openssl_available): ?>
            <tr>
                <td>OpenSSL Version</td>
                <td><?php echo $openssl_version; ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <td>JSON Support</td>
                <td class="<?php echo function_exists('json_encode') ? 'success' : 'error'; ?>">
                    <?php echo function_exists('json_encode') ? 'Available' : 'Not Available'; ?>
                </td>
            </tr>
        </table>
        
        <h2>Manual AJAX Test</h2>
        <p>Test AJAX requests manually to troubleshoot connection issues:</p>
        
        <div id="ajax-test-area">
            <button id="test-nonce-ajax" class="button">Test Nonce AJAX</button>
            <button id="test-openai-ajax" class="button">Test OpenAI AJAX</button>
            <div id="ajax-result" style="margin-top: 10px; padding: 10px; background: #f6f7f7; border: 1px solid #dcdcde; display: none;"></div>
        </div>
        
        <p><a href="<?php echo admin_url('admin.php?page=memberpress-ai-assistant-settings'); ?>" class="button">← Back to Settings</a></p>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Test Nonce AJAX
        document.getElementById('test-nonce-ajax').addEventListener('click', function() {
            var resultArea = document.getElementById('ajax-result');
            resultArea.style.display = 'block';
            resultArea.innerHTML = 'Sending request...';
            
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>');
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    resultArea.innerHTML = '<strong>Success!</strong><br><pre>' + xhr.responseText + '</pre>';
                } else {
                    resultArea.innerHTML = '<strong>Error:</strong> ' + xhr.status + '<br><pre>' + xhr.responseText + '</pre>';
                }
            };
            xhr.onerror = function() {
                resultArea.innerHTML = '<strong>Network Error!</strong> Could not connect to server.';
            };
            var data = 'action=mpai_debug_nonce&mpai_nonce=<?php echo $test_nonce; ?>';
            xhr.send(data);
        });
        
        // Test OpenAI AJAX
        document.getElementById('test-openai-ajax').addEventListener('click', function() {
            var resultArea = document.getElementById('ajax-result');
            resultArea.style.display = 'block';
            resultArea.innerHTML = 'Sending request to test OpenAI API...';
            
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>');
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    resultArea.innerHTML = '<strong>Success!</strong><br><pre>' + xhr.responseText + '</pre>';
                } else {
                    resultArea.innerHTML = '<strong>Error:</strong> ' + xhr.status + '<br><pre>' + xhr.responseText + '</pre>';
                }
            };
            xhr.onerror = function() {
                resultArea.innerHTML = '<strong>Network Error!</strong> Could not connect to server.';
            };
            var data = 'action=mpai_test_openai_api&mpai_nonce=<?php echo $test_nonce; ?>&api_key=<?php echo esc_js(get_option('mpai_api_key', 'sk-test')); ?>';
            xhr.send(data);
        });
    });
    </script>
</body>
</html>