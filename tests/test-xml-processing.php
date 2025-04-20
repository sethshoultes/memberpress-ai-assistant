<?php
/**
 * Test XML Processing
 * 
 * This script tests the XML content processing in the chat interface.
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    define('WPINC', 'wp-includes');
}

// Include the plugin file to load all classes
require_once dirname(__FILE__) . '/memberpress-ai-assistant.php';

// Create a test message with XML content
$test_message = array(
    'role' => 'assistant',
    'content' => 'Here is a blog post for you:

```xml
<wp-post>
  <post-title>Test Blog Post</post-title>
  <post-content>
    <block type="paragraph">This is a test blog post.</block>
    <block type="heading" level="2">First Section</block>
    <block type="paragraph">This is the first section of the blog post.</block>
    <block type="list">
      <item>Item 1</item>
      <item>Item 2</item>
      <item>Item 3</item>
    </block>
  </post-content>
  <post-excerpt>This is a test blog post.</post-excerpt>
  <post-status>draft</post-status>
</wp-post>
```

Let me know if you need any changes to the blog post.',
    'timestamp' => time()
);

// Create an instance of the XML display handler
$xml_display_handler = new MPAI_XML_Display_Handler();

// Process the message
$processed_content = $xml_display_handler->process_xml_content($test_message['content'], $test_message);

// Output the processed content
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test XML Processing</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        .test-container {
            border: 1px solid #ccc;
            padding: 20px;
            margin-bottom: 20px;
        }
        .original-content {
            background-color: #f5f5f5;
            padding: 10px;
            margin-bottom: 20px;
            white-space: pre-wrap;
            font-family: monospace;
        }
        .processed-content {
            border: 1px solid #ddd;
            padding: 20px;
        }
        h1, h2 {
            color: #333;
        }
        .debug-info {
            background-color: #f8f8f8;
            border: 1px solid #ddd;
            padding: 10px;
            margin-top: 20px;
            font-family: monospace;
            white-space: pre-wrap;
        }
    </style>
    <?php
    // Enqueue the necessary styles
    wp_enqueue_style('dashicons');
    wp_enqueue_style('mpai-blog-post-preview', plugin_dir_url(__FILE__) . 'assets/css/blog-post-preview.css');
    
    // Enqueue the necessary scripts
    wp_enqueue_script('jquery');
    wp_enqueue_script('mpai-blog-formatter', plugin_dir_url(__FILE__) . 'assets/js/modules/mpai-blog-formatter.js', array('jquery'));
    
    // Print the styles and scripts
    wp_print_styles();
    wp_print_scripts();
    ?>
</head>
<body>
    <h1>Test XML Processing</h1>
    
    <div class="test-container">
        <h2>Original Content</h2>
        <div class="original-content"><?php echo htmlspecialchars($test_message['content']); ?></div>
        
        <h2>Processed Content</h2>
        <div class="processed-content mpai-chat-message mpai-chat-message-assistant">
            <div class="mpai-chat-message-content"><?php echo $processed_content; ?></div>
        </div>
    </div>
    
    <div class="debug-info">
        <h3>Debug Information</h3>
        <p>XML Content Detected: <?php echo $xml_display_handler->contains_xml_blog_post($test_message['content']) ? 'Yes' : 'No'; ?></p>
        <p>Original Content Length: <?php echo strlen($test_message['content']); ?> characters</p>
        <p>Processed Content Length: <?php echo strlen($processed_content); ?> characters</p>
        <p>XML Tags Remaining in Processed Content: <?php echo preg_match('/<wp-post>|<post-title>|<post-content>/', $processed_content) ? 'Yes' : 'No'; ?></p>
    </div>
    
    <script>
        // Initialize the blog formatter
        jQuery(document).ready(function($) {
            console.log('Document ready');
            
            // Define a mock createPostFromXML function for testing
            window.MPAI_BlogFormatter = window.MPAI_BlogFormatter || {};
            window.MPAI_BlogFormatter.createPostFromXML = function(xmlContent, contentType) {
                console.log('createPostFromXML called with content type:', contentType);
                console.log('XML content:', xmlContent);
                alert('Create Post function called with content type: ' + contentType);
            };
            
            // Log the state of the preview card
            var card = $('.mpai-post-preview-card');
            if (card.length) {
                console.log('Preview card found:', card);
                console.log('Preview card display:', card.css('display'));
                console.log('Preview card visibility:', card.css('visibility'));
                console.log('Preview card opacity:', card.css('opacity'));
            } else {
                console.log('No preview card found');
            }
        });
    </script>
</body>
</html>