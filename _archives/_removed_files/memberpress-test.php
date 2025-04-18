<?php
/**
 * MemberPress API Test Script
 * 
 * Simple test script to directly test MemberPress API connection
 */

// Load WordPress core
require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/wp-load.php');

// Check if user is logged in and is admin
if (!current_user_can('manage_options')) {
    wp_die('You do not have sufficient permissions to access this page.');
}

// Get API key from form submission or use the saved one
$api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : get_option('mpai_memberpress_api_key', '');
$test_result = '';

// Process form submission
if (isset($_POST['test_api']) && !empty($api_key)) {
    // Check if MemberPress is active
    if (!class_exists('MeprAppCtrl')) {
        $test_result = '<div class="notice notice-error"><p>MemberPress plugin is not active. Please activate it to use the API.</p></div>';
    } else {
        // Check if Developer Tools is active
        $dev_tools_active = class_exists('MeprRestRoutes');
        if (!$dev_tools_active) {
            $test_result = '<div class="notice notice-error"><p>MemberPress Developer Tools plugin is not active. Please activate it to use the API.</p></div>';
        } else {
            // Test MemberPress API
            $base_url = site_url('/wp-json/mp/v1/');
            $endpoint = $base_url . 'memberships';
            
            $args = array(
                'method' => 'GET',
                'headers' => array(
                    'Authorization' => $api_key,
                    'Content-Type' => 'application/json',
                ),
                'timeout' => 30,
            );
            
            $response = wp_remote_request($endpoint, $args);
            
            if (is_wp_error($response)) {
                $test_result = '<div class="notice notice-error"><p>Error: ' . $response->get_error_message() . '</p></div>';
            } else {
                $status_code = wp_remote_retrieve_response_code($response);
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);
                
                if ($status_code !== 200) {
                    $error_message = 'Invalid API key or API error';
                    if (is_array($data) && isset($data['message'])) {
                        $error_message = $data['message'];
                    }
                    $test_result = '<div class="notice notice-error"><p>API Error (' . $status_code . '): ' . $error_message . '</p></div>';
                } else {
                    // Get membership details
                    $memberships_list = '';
                    $count = 0;
                    
                    if (is_array($data)) {
                        foreach ($data as $membership) {
                            if ($count < 5 && isset($membership['title'])) {
                                $price = isset($membership['price']) ? ' - ' . $membership['price'] : '';
                                $memberships_list .= '<li>' . esc_html($membership['title']) . $price . '</li>';
                                $count++;
                            }
                        }
                        
                        $test_result = '<div class="notice notice-success"><p>Connection successful! Found ' . count($data) . ' membership(s).</p>';
                        if (!empty($memberships_list)) {
                            $test_result .= '<p>First ' . $count . ' memberships:</p><ul>' . $memberships_list . '</ul>';
                        }
                        $test_result .= '</div>';
                    } else {
                        $test_result = '<div class="notice notice-error"><p>API returned unexpected data structure.</p></div>';
                    }
                }
            }
        }
    }
}

// Check if MemberPress and Developer Tools are active
$memberpress_active = class_exists('MeprAppCtrl');
$dev_tools_active = class_exists('MeprRestRoutes');
?>
<!DOCTYPE html>
<html>
<head>
    <title>MemberPress API Test</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            margin: 20px;
            background: #f0f0f1;
        }
        .container {
            max-width: 800px;
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
        .form-field {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        input[type="text"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #8c8f94;
            border-radius: 4px;
        }
        input[type="submit"] {
            background: #2271b1;
            border-color: #2271b1;
            color: #fff;
            text-decoration: none;
            text-shadow: none;
            padding: 0 10px;
            height: 30px;
            line-height: 28px;
            border-radius: 3px;
            cursor: pointer;
            border-width: 1px;
            border-style: solid;
        }
        .notice {
            background: #fff;
            border-left: 4px solid #72aee6;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
            margin: 15px 0;
            padding: 12px;
        }
        .notice-success {
            border-left-color: #00a32a;
        }
        .notice-error {
            border-left-color: #d63638;
        }
        .back-link {
            margin-top: 20px;
            display: block;
        }
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 5px;
        }
        .status-active {
            background-color: #00a32a;
        }
        .status-inactive {
            background-color: #d63638;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>MemberPress API Direct Test</h1>
        
        <?php echo $test_result; ?>
        
        <form method="post">
            <div class="form-field">
                <label for="api_key">MemberPress API Key</label>
                <input type="text" id="api_key" name="api_key" value="<?php echo esc_attr($api_key); ?>" placeholder="Enter your MemberPress API key" />
                <p class="description">Your MemberPress API key will not be saved unless you save it in the settings page.</p>
            </div>
            
            <div class="form-field">
                <input type="submit" name="test_api" value="Test API Connection" />
            </div>
        </form>
        
        <div class="status-section">
            <h3>Plugin Status</h3>
            <p>
                <span class="status-indicator <?php echo $memberpress_active ? 'status-active' : 'status-inactive'; ?>"></span>
                MemberPress: <?php echo $memberpress_active ? 'Active' : 'Not Active'; ?>
            </p>
            <p>
                <span class="status-indicator <?php echo $dev_tools_active ? 'status-active' : 'status-inactive'; ?>"></span>
                MemberPress Developer Tools: <?php echo $dev_tools_active ? 'Active' : 'Not Active'; ?>
            </p>
            
            <?php if (!$memberpress_active): ?>
                <div class="notice notice-error">
                    <p>MemberPress is not active. Please activate the MemberPress plugin to use the API.</p>
                </div>
            <?php endif; ?>
            
            <?php if ($memberpress_active && !$dev_tools_active): ?>
                <div class="notice notice-error">
                    <p>MemberPress Developer Tools is not active. This plugin is required for the REST API.</p>
                    <p>Please activate the MemberPress Developer Tools plugin to use the API.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <a href="<?php echo admin_url('admin.php?page=memberpress-ai-assistant-settings'); ?>" class="back-link">← Back to Settings</a>
        
        <div class="debug-info">
            <h3>Debug Information</h3>
            <p>PHP Version: <?php echo phpversion(); ?></p>
            <p>WordPress Version: <?php echo get_bloginfo('version'); ?></p>
            <p>Site URL: <?php echo site_url(); ?></p>
            <p>REST API Base: <?php echo site_url('/wp-json/mp/v1/'); ?></p>
        </div>
    </div>
</body>
</html>