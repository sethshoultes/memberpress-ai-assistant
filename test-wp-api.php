<?php
/**
 * Test script for WP API Tool
 * 
 * This script demonstrates direct usage of the WordPress API Tool
 */

// Load WordPress
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';

// Load the WP API Tool class
require_once __DIR__ . '/includes/tools/implementations/class-mpai-wp-api-tool.php';

// Create the tool instance
$wp_api_tool = new MPAI_WP_API_Tool();

// Create a test post
try {
    $result = $wp_api_tool->execute(array(
        'action' => 'create_post',
        'title' => 'Test Post',
        'content' => 'Test content',
        'status' => 'draft'
    ));
    
    echo "Post created successfully!\n";
    echo "Post ID: " . $result['post_id'] . "\n";
    echo "Title: " . $result['post']['post_title'] . "\n";
    echo "Content: " . $result['post']['post_content'] . "\n";
    echo "Status: " . $result['post']['post_status'] . "\n";
    echo "URL: " . $result['post_url'] . "\n";
    echo "Edit URL: " . $result['edit_url'] . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}