<?php
/**
 * MemberPress AI Assistant - Browser-Accessible Validation Tool
 * 
 * This validation tool can be accessed via browser to test the chat interface
 * system with a running WordPress environment.
 * 
 * @package MemberpressAiAssistant
 * @version 1.0.0
 */

// Security check - only allow access from admin users
if (!defined('ABSPATH')) {
    // Try to load WordPress
    $wp_load_paths = [
        dirname(__DIR__, 4) . '/wp-load.php',
        dirname(__DIR__, 5) . '/wp-load.php',
        dirname(__DIR__, 3) . '/wp-load.php'
    ];
    
    $wp_loaded = false;
    foreach ($wp_load_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $wp_loaded = true;
            break;
        }
    }
    
    if (!$wp_loaded) {
        die('WordPress environment not found. Please access this file through WordPress admin.');
    }
}

// Check if user has admin capabilities
if (!current_user_can('manage_options')) {
    wp_die('You do not have sufficient permissions to access this page.');
}

// Include the complete validation tool
$validation_tool_path = __DIR__ . '/chat-interface-complete-validation.php';
if (file_exists($validation_tool_path)) {
    // Set a flag to indicate browser access
    define('MPAI_BROWSER_VALIDATION', true);
    include $validation_tool_path;
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Validation Tool Not Found</title>
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; margin: 40px; }
            .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 20px; border-radius: 4px; }
        </style>
    </head>
    <body>
        <div class="error">
            <h2>❌ Validation Tool Not Found</h2>
            <p>The complete validation tool could not be found at: <code><?php echo esc_html($validation_tool_path); ?></code></p>
            <p>Please ensure all validation files are properly uploaded.</p>
        </div>
    </body>
    </html>
    <?php
}
?>