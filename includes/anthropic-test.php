<?php
/**
 * Anthropic API Test Page
 *
 * This file provides a simple standalone test of the Anthropic API connection.
 */

// Do not allow direct access to this file
if (!defined('ABSPATH')) {
    // Define ABSPATH if not already defined to allow standalone operation
    if (!defined('WP_CONTENT_DIR')) {
        define('WP_CONTENT_DIR', realpath(dirname(__FILE__) . '/../../../'));
    }
    
    // WordPress constants for require_once compatibility
    define('ABSPATH', realpath(WP_CONTENT_DIR . '/../') . '/');
    require_once ABSPATH . 'wp-load.php';
}

// Security check
if (!current_user_can('manage_options')) {
    wp_die('Access denied');
}

// Set headers for a clean display
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Anthropic API Test</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            margin: 20px;
            max-width: 800px;
            line-height: 1.5;
        }
        h1 {
            color: #2271b1;
        }
        .result {
            margin-top: 20px;
            padding: 10px;
            border: 1px solid #ccc;
            background-color: #f8f8f8;
            border-radius: 5px;
            overflow-wrap: break-word;
        }
        .success {
            color: #00a32a;
            border-left: 5px solid #00a32a;
        }
        .error {
            color: #d63638;
            border-left: 5px solid #d63638;
        }
        pre {
            white-space: pre-wrap;
            margin: 0;
            font-family: monospace;
            font-size: 13px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        input[type="text"], select {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            background-color: #2271b1;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        button:hover {
            background-color: #135e96;
        }
        .note {
            background-color: #f0f6fc;
            padding: 10px;
            border-left: 5px solid #2271b1;
            margin-bottom: 20px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <h1>Anthropic Claude API Test</h1>
    
    <div class="note">
        <p>This page allows you to test your connection to the Anthropic Claude API directly. Enter your API key and run a test to ensure everything is working properly.</p>
    </div>
    
    <form id="test-form">
        <div>
            <label for="api-key">Anthropic API Key:</label>
            <input type="text" id="api-key" name="api-key" value="<?php echo esc_attr(get_option('mpai_anthropic_api_key', '')); ?>" placeholder="Enter your Anthropic API key">
        </div>
        
        <div>
            <label for="model">Model:</label>
            <select id="model" name="model">
                <option value="claude-3-opus-20240229" <?php selected(get_option('mpai_anthropic_model', 'claude-3-opus-20240229'), 'claude-3-opus-20240229'); ?>>Claude 3 Opus</option>
                <option value="claude-3-sonnet-20240229" <?php selected(get_option('mpai_anthropic_model', 'claude-3-opus-20240229'), 'claude-3-sonnet-20240229'); ?>>Claude 3 Sonnet</option>
                <option value="claude-3-haiku-20240307" <?php selected(get_option('mpai_anthropic_model', 'claude-3-opus-20240229'), 'claude-3-haiku-20240307'); ?>>Claude 3 Haiku</option>
                <option value="claude-2.1" <?php selected(get_option('mpai_anthropic_model', 'claude-3-opus-20240229'), 'claude-2.1'); ?>>Claude 2.1</option>
                <option value="claude-2.0" <?php selected(get_option('mpai_anthropic_model', 'claude-3-opus-20240229'), 'claude-2.0'); ?>>Claude 2.0</option>
            </select>
        </div>
        
        <button type="button" id="test-button">Test Connection</button>
    </form>
    
    <div id="result" class="result" style="display: none;">
        <pre id="result-content"></pre>
    </div>
    
    <script>
        document.getElementById('test-button').addEventListener('click', function() {
            const apiKey = document.getElementById('api-key').value;
            const model = document.getElementById('model').value;
            const resultDiv = document.getElementById('result');
            const resultContent = document.getElementById('result-content');
            
            if (!apiKey) {
                resultDiv.className = 'result error';
                resultContent.textContent = 'Please enter an API key';
                resultDiv.style.display = 'block';
                return;
            }
            
            // Show loading state
            resultDiv.className = 'result';
            resultContent.textContent = 'Testing connection to Anthropic API...';
            resultDiv.style.display = 'block';
            
            // Simple test request to Anthropic API
            fetch('https://api.anthropic.com/v1/messages', {
                method: 'POST',
                headers: {
                    'x-api-key': apiKey,
                    'anthropic-version': '2023-06-01',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    model: model,
                    messages: [
                        {
                            role: 'user',
                            content: 'Hello, I am testing the MemberPress AI Assistant connection to Anthropic. Please respond with a very brief welcome message.'
                        }
                    ],
                    max_tokens: 150
                })
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        throw new Error(`API Error (${response.status}): ${JSON.stringify(err)}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log(data);
                resultDiv.className = 'result success';
                resultContent.textContent = 'Connection successful!\n\nModel: ' + model + '\nResponse: ' + data.content[0].text;
                
                // Save to settings if requested
                if (confirm('Connection test successful! Would you like to save this API key and model to your MemberPress AI Assistant settings?')) {
                    // Send request to save settings
                    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            action: 'mpai_save_anthropic_settings',
                            api_key: apiKey,
                            model: model,
                            nonce: '<?php echo wp_create_nonce('mpai_save_settings'); ?>'
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Settings saved successfully!');
                        } else {
                            alert('Error saving settings: ' + data.data);
                        }
                    })
                    .catch(error => {
                        alert('Error saving settings: ' + error.message);
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                resultDiv.className = 'result error';
                resultContent.textContent = 'Connection failed: ' + error.message;
            });
        });
    </script>
</body>
</html>