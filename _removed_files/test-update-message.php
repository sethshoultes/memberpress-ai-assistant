<?php
/**
 * Test Update Message AJAX
 * 
 * This file provides a direct way to test the update_message AJAX call
 */

// Load WordPress core
require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/wp-load.php');

// Check if user is logged in and is admin
if (!current_user_can('manage_options')) {
    wp_die('You do not have sufficient permissions to access this page.');
}

// Get admin ajax URL and nonces
$ajax_url = admin_url('admin-ajax.php');
$mpai_nonce = wp_create_nonce('mpai_nonce');
$chat_nonce = wp_create_nonce('mpai_chat_nonce');

// Get existing message ID if available
global $wpdb;
$table_messages = $wpdb->prefix . 'mpai_messages';
$message_id = $wpdb->get_var("SELECT id FROM $table_messages ORDER BY id DESC LIMIT 1");
if (!$message_id) {
    $message_id = 'test-123';
}

// HTML output
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Update Message AJAX</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .card { background: #fff; padding: 20px; border: 1px solid #ccc; margin-bottom: 20px; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; }
        button { padding: 8px 16px; background: #0073aa; color: white; border: none; cursor: pointer; margin-right: 10px; margin-bottom: 10px; }
        button:hover { background: #005177; }
        #results { display: none; margin-top: 20px; padding: 10px; background: #f0f0f0; }
        .info { background: #e7f7ff; padding: 10px; border-left: 4px solid #00a0d2; margin-bottom: 20px; }
    </style>
    <?php
    // Include WordPress scripts
    wp_enqueue_script('jquery');
    wp_print_scripts('jquery');
    ?>
</head>
<body>
    <h1>Test Update Message AJAX</h1>
    
    <div class="info">
        <p>This page tests the update_message AJAX endpoint with different nonce configurations to diagnose the 400 Bad Request error.</p>
        <p>We'll test different parameter names and values to see which one works.</p>
    </div>
    
    <div class="card">
        <h2>Configuration</h2>
        <p><strong>AJAX URL:</strong> <?php echo $ajax_url; ?></p>
        <p><strong>Message ID:</strong> <?php echo $message_id; ?></p>
        <p><strong>mpai_nonce value:</strong> <?php echo substr($mpai_nonce, 0, 10); ?>...</p>
        <p><strong>chat_nonce value:</strong> <?php echo substr($chat_nonce, 0, 10); ?>...</p>
    </div>
    
    <div class="card">
        <h2>Test Update Message</h2>
        
        <div>
            <button id="test1">Test 1: nonce='mpai_nonce'</button>
            <button id="test2">Test 2: mpai_nonce='mpai_nonce'</button>
            <button id="test3">Test 3: nonce='chat_nonce'</button>
            <button id="test4">Test 4: _wpnonce='mpai_nonce'</button>
            <button id="test5">Test 5: Without nonce verification</button>
        </div>
        
        <div id="results">
            <h3>Results:</h3>
            <pre id="result-content"></pre>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        function displayResult(data) {
            $('#results').show();
            $('#result-content').text(JSON.stringify(data, null, 2));
        }
        
        // Test 1: Using 'nonce' parameter with mpai_nonce value
        $('#test1').click(function() {
            $.ajax({
                url: '<?php echo $ajax_url; ?>',
                type: 'POST',
                data: {
                    action: 'mpai_update_message',
                    message_id: '<?php echo $message_id; ?>',
                    content: 'Test content updated at ' + new Date().toISOString(),
                    nonce: '<?php echo $mpai_nonce; ?>'
                },
                success: function(response) {
                    console.log('Test 1 success:', response);
                    displayResult({
                        test: 'Test 1: nonce=mpai_nonce',
                        status: 'success',
                        response: response
                    });
                },
                error: function(xhr, status, error) {
                    console.error('Test 1 error:', error);
                    displayResult({
                        test: 'Test 1: nonce=mpai_nonce',
                        status: 'error',
                        error: error,
                        statusCode: xhr.status,
                        responseText: xhr.responseText
                    });
                }
            });
        });
        
        // Test 2: Using 'mpai_nonce' parameter with mpai_nonce value
        $('#test2').click(function() {
            $.ajax({
                url: '<?php echo $ajax_url; ?>',
                type: 'POST',
                data: {
                    action: 'mpai_update_message',
                    message_id: '<?php echo $message_id; ?>',
                    content: 'Test content updated at ' + new Date().toISOString(),
                    mpai_nonce: '<?php echo $mpai_nonce; ?>'
                },
                success: function(response) {
                    console.log('Test 2 success:', response);
                    displayResult({
                        test: 'Test 2: mpai_nonce=mpai_nonce',
                        status: 'success',
                        response: response
                    });
                },
                error: function(xhr, status, error) {
                    console.error('Test 2 error:', error);
                    displayResult({
                        test: 'Test 2: mpai_nonce=mpai_nonce',
                        status: 'error',
                        error: error,
                        statusCode: xhr.status,
                        responseText: xhr.responseText
                    });
                }
            });
        });
        
        // Test 3: Using 'nonce' parameter with chat_nonce value
        $('#test3').click(function() {
            $.ajax({
                url: '<?php echo $ajax_url; ?>',
                type: 'POST',
                data: {
                    action: 'mpai_update_message',
                    message_id: '<?php echo $message_id; ?>',
                    content: 'Test content updated at ' + new Date().toISOString(),
                    nonce: '<?php echo $chat_nonce; ?>'
                },
                success: function(response) {
                    console.log('Test 3 success:', response);
                    displayResult({
                        test: 'Test 3: nonce=chat_nonce',
                        status: 'success',
                        response: response
                    });
                },
                error: function(xhr, status, error) {
                    console.error('Test 3 error:', error);
                    displayResult({
                        test: 'Test 3: nonce=chat_nonce',
                        status: 'error',
                        error: error,
                        statusCode: xhr.status,
                        responseText: xhr.responseText
                    });
                }
            });
        });
        
        // Test 4: Using _wpnonce parameter with mpai_nonce value
        $('#test4').click(function() {
            $.ajax({
                url: '<?php echo $ajax_url; ?>',
                type: 'POST',
                data: {
                    action: 'mpai_update_message',
                    message_id: '<?php echo $message_id; ?>',
                    content: 'Test content updated at ' + new Date().toISOString(),
                    _wpnonce: '<?php echo $mpai_nonce; ?>'
                },
                success: function(response) {
                    console.log('Test 4 success:', response);
                    displayResult({
                        test: 'Test 4: _wpnonce=mpai_nonce',
                        status: 'success',
                        response: response
                    });
                },
                error: function(xhr, status, error) {
                    console.error('Test 4 error:', error);
                    displayResult({
                        test: 'Test 4: _wpnonce=mpai_nonce',
                        status: 'error',
                        error: error,
                        statusCode: xhr.status,
                        responseText: xhr.responseText
                    });
                }
            });
        });
        
        // Test 5: Override the nonce check on the backend to test other issues
        $('#test5').click(function() {
            $.ajax({
                url: '<?php echo $ajax_url; ?>',
                type: 'POST',
                data: {
                    action: 'mpai_update_message',
                    message_id: '<?php echo $message_id; ?>',
                    content: 'Test content updated at ' + new Date().toISOString(),
                    bypass_nonce: 'true'
                },
                success: function(response) {
                    console.log('Test 5 success:', response);
                    displayResult({
                        test: 'Test 5: Without nonce verification',
                        status: 'success',
                        response: response
                    });
                },
                error: function(xhr, status, error) {
                    console.error('Test 5 error:', error);
                    displayResult({
                        test: 'Test 5: Without nonce verification',
                        status: 'error',
                        error: error,
                        statusCode: xhr.status,
                        responseText: xhr.responseText
                    });
                }
            });
        });
    });
    </script>
</body>
</html>