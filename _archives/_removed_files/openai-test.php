<?php
/**
 * OpenAI API Test Script
 * 
 * Simple test script to directly test OpenAI API connection
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

// Check if user is logged in and is admin
if (!current_user_can('manage_options')) {
    wp_die('You do not have sufficient permissions to access this page.');
}

// Get API key from form submission or use the saved one
$api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : get_option('mpai_api_key', '');
$test_result = '';

// Process form submission
if (isset($_POST['test_api']) && !empty($api_key)) {
    // Test OpenAI API
    $endpoint = 'https://api.openai.com/v1/models';
    
    $response = wp_remote_get(
        $endpoint,
        array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'timeout' => 30,
            'sslverify' => true,
        )
    );
    
    if (is_wp_error($response)) {
        $test_result = '<div class="notice notice-error"><p>Error: ' . $response->get_error_message() . '</p></div>';
    } else {
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($status_code !== 200) {
            $error_message = isset($data['error']['message']) ? $data['error']['message'] : 'Invalid API key or API error';
            $test_result = '<div class="notice notice-error"><p>API Error (' . $status_code . '): ' . $error_message . '</p></div>';
        } else if (empty($data['data'])) {
            $test_result = '<div class="notice notice-error"><p>API returned unexpected data structure.</p></div>';
        } else {
            // Get available models
            $models_list = '';
            $count = 0;
            foreach ($data['data'] as $model) {
                if ($count < 5) {
                    $models_list .= '<li>' . esc_html($model['id']) . '</li>';
                    $count++;
                }
            }
            
            $test_result = '<div class="notice notice-success"><p>Connection successful! Found ' . count($data['data']) . ' models.</p>';
            if (!empty($models_list)) {
                $test_result .= '<p>First 5 models:</p><ul>' . $models_list . '</ul>';
            }
            $test_result .= '</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>OpenAI API Test</title>
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
    </style>
</head>
<body>
    <div class="container">
        <h1>OpenAI API Direct Test</h1>
        
        <?php echo $test_result; ?>
        
        <form method="post">
            <div class="form-field">
                <label for="api_key">OpenAI API Key</label>
                <input type="text" id="api_key" name="api_key" value="<?php echo esc_attr($api_key); ?>" placeholder="Enter your OpenAI API key" />
                <p class="description">Your OpenAI API key will not be saved.</p>
            </div>
            
            <div class="form-field">
                <input type="submit" name="test_api" value="Test API Connection" />
            </div>
        </form>
        
        <a href="<?php echo admin_url('admin.php?page=memberpress-ai-assistant-settings'); ?>" class="back-link">← Back to Settings</a>
        
        <div class="debug-info">
            <h3>Debug Information</h3>
            <p>PHP Version: <?php echo phpversion(); ?></p>
            <p>WordPress Version: <?php echo get_bloginfo('version'); ?></p>
            <p>SSL Verification: <?php echo extension_loaded('openssl') ? 'Available' : 'Not Available'; ?></p>
            <p>cURL Version: <?php echo function_exists('curl_version') ? curl_version()['version'] : 'Not Available'; ?></p>
        </div>
    </div>
</body>
</html>