<?php
// This is a temporary script to update MCP and CLI settings

// Bootstrap WordPress
require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/wp-load.php';

// Update options
update_option('mpai_enable_mcp', '1');
update_option('mpai_enable_cli_commands', '1');
update_option('mpai_enable_wp_cli_tool', '1');
update_option('mpai_enable_memberpress_info_tool', '1');
update_option('mpai_allowed_cli_commands', array('wp user list', 'wp post list', 'wp plugin list'));

echo "Options updated successfully!\n";
