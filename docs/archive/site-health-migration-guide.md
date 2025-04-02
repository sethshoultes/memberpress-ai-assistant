# WordPress Site Health Migration Guide

This document outlines the step-by-step process for migrating the existing diagnostic system to utilize the WordPress Site Health API.

## Overview

The migration will be performed in phases to ensure backward compatibility and minimize disruption:

1. Add Site Health integration without removing existing functionality
2. Update diagnostic tools to use Site Health data
3. Update memberpress_info tool to include system information
4. Update UI to display the enhanced information

## Phase 1: Create Site Health Integration

### Step 1: Create Base Class

Create the `class-mpai-site-health.php` file in the includes directory:

```bash
touch /Users/sethshoultes/Local\ Sites/memberpress-testing/app/public/wp-content/plugins/memberpress-ai-assistant/includes/class-mpai-site-health.php
```

Add the implementation from the example file.

### Step 2: Add Initial Tests

Add a new test type to the diagnostic page to verify that the Site Health integration works correctly:

1. Edit `/includes/diagnostic-page.php`
2. Add 'site_health' to the available test types
3. Add handling for the site_health test type
4. Add a new test card for Site Health in the UI

```php
// Add to the existing test type validation
if (!empty($test_type) && !in_array($test_type, ['openai_connection', 'anthropic_connection', 'memberpress_connection', 'wordpress_info', 'plugin_status', 'site_health', 'all'])) {
    // Error handling
}

// Add handling for site_health test
if ($test_type === 'site_health') {
    require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-site-health.php';
    $site_health = new MPAI_Site_Health();
    
    $result = array(
        'success' => true,
        'message' => 'Site Health information retrieved successfully',
        'data' => $site_health->get_complete_info()
    );
}
```

Add a new test card to the UI:

```php
<div class="test-card">
    <h3>WordPress Site Health</h3>
    <p>Get comprehensive system information using WordPress Site Health API.</p>
    <a href="?test=site_health" class="button">Run Test</a>
</div>
```

## Phase 2: Update Diagnostic Tool

### Step 1: Modify the Diagnostic Tool Class

Update `includes/tools/implementations/class-mpai-diagnostic-tool.php` to use the Site Health wrapper:

1. Add a dependency on the Site Health class
2. Initialize the Site Health instance in the constructor
3. Update the `get_wordpress_info()` method to use Site Health data
4. Add a new test_type for 'site_health' in the tool definition

```php
// In the constructor
public function __construct() {
    $this->name = 'diagnostic';
    $this->description = 'Run various diagnostic tests and status checks for the MemberPress AI Assistant';
    
    // Load Site Health class if needed
    if (!class_exists('MPAI_Site_Health') && file_exists(MPAI_PLUGIN_DIR . 'includes/class-mpai-site-health.php')) {
        require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-site-health.php';
        $this->site_health = new MPAI_Site_Health();
    }
}

// Update the get_tool_definition method
public function get_tool_definition() {
    return [
        'name' => 'run_diagnostic',
        'description' => 'Run diagnostic tests and status checks for the MemberPress AI Assistant',
        'parameters' => [
            'type' => 'object',
            'properties' => [
                'test_type' => [
                    'type' => 'string',
                    'enum' => ['openai_connection', 'anthropic_connection', 'memberpress_connection', 'wordpress_info', 'plugin_status', 'site_health', 'all'],
                    'description' => 'The type of diagnostic test to run'
                ],
                // ...other parameters...
            ],
            'required' => ['test_type']
        ],
    ];
}

// Update the execute method to handle the new test type
public function execute($parameters) {
    // ...existing code...
    
    switch ($test_type) {
        // ...existing cases...
        
        case 'site_health':
            if (!isset($this->site_health)) {
                require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-site-health.php';
                $this->site_health = new MPAI_Site_Health();
            }
            $result = [
                'success' => true,
                'message' => 'Site Health information retrieved successfully',
                'data' => $this->site_health->get_complete_info()
            ];
            break;
            
        // ...existing code...
    }
    
    // ...existing code...
}

// Update the get_wordpress_info method
public function get_wordpress_info() {
    // Use Site Health if available
    if (isset($this->site_health)) {
        return [
            'success' => true,
            'wordpress' => $this->convert_site_health_data($this->site_health->get_wordpress_info()),
            'php' => $this->convert_site_health_data($this->site_health->get_server_info(), 'php_'),
            'database' => $this->convert_site_health_data($this->site_health->get_database_info()),
            'server' => $this->convert_site_health_data($this->site_health->get_server_info(), '', 'php_')
        ];
    }
    
    // Fall back to existing implementation if Site Health is not available
    // ...existing code...
}

// Helper method to convert Site Health data format
private function convert_site_health_data($data, $prefix_to_remove = '', $prefix_to_exclude = '') {
    $result = [];
    
    foreach ($data as $key => $item) {
        // Skip items with the excluded prefix
        if (!empty($prefix_to_exclude) && strpos($key, $prefix_to_exclude) === 0) {
            continue;
        }
        
        // Remove prefix if needed
        $new_key = $key;
        if (!empty($prefix_to_remove) && strpos($key, $prefix_to_remove) === 0) {
            $new_key = substr($key, strlen($prefix_to_remove));
        }
        
        $result[$new_key] = $item['value'];
    }
    
    return $result;
}
```

## Phase 3: Update MemberPress Info Tool

### Step 1: Update the Context Manager

Modify the context manager to include system information in the memberpress_info tool:

Edit `includes/class-mpai-context-manager.php`:

```php
// Update the memberpress_info tool definition
private function get_memberpress_info_tool_definition() {
    return [
        'name' => 'memberpress_info',
        'description' => 'Get information about MemberPress data and system settings',
        'parameters' => [
            'type' => 'object',
            'properties' => [
                'type' => [
                    'type' => 'string',
                    'enum' => [
                        'members', 
                        'memberships', 
                        'transactions', 
                        'subscriptions', 
                        'new_members_this_month', 
                        'system_info', 
                        'all'
                    ],
                    'description' => 'The type of MemberPress information to retrieve'
                ],
                'include_system_info' => [
                    'type' => 'boolean',
                    'description' => 'Whether to include system information in the response',
                    'default' => false
                ],
            ],
            'required' => ['type']
        ]
    ];
}
```

### Step 2: Update the Tool Execution Method

Update the `execute_memberpress_info` method in `class-mpai-context-manager.php`:

```php
/**
 * Execute the MemberPress Info tool
 *
 * @param array $parameters Parameters for the tool
 * @return array Execution result
 */
public function execute_memberpress_info($parameters) {
    if (!isset($parameters['type'])) {
        return [
            'success' => false,
            'message' => 'Missing required parameter: type',
            'available_types' => ['members', 'memberships', 'transactions', 'subscriptions', 'new_members_this_month', 'system_info', 'all']
        ];
    }
    
    $type = sanitize_text_field($parameters['type']);
    $include_system_info = isset($parameters['include_system_info']) ? (bool)$parameters['include_system_info'] : false;
    
    try {
        // Initialize MemberPress API
        $memberpress_api = new MPAI_MemberPress_API();
        
        // Handle system_info type
        if ($type === 'system_info') {
            if (!class_exists('MPAI_Site_Health')) {
                require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-site-health.php';
            }
            $site_health = new MPAI_Site_Health();
            
            return [
                'success' => true,
                'message' => 'System information retrieved successfully',
                'data' => $site_health->get_complete_info()
            ];
        }
        
        // Handle all with system info
        if ($type === 'all' && $include_system_info) {
            $mp_data = $memberpress_api->get_data_summary();
            
            // Add system info if requested
            if ($include_system_info) {
                if (!class_exists('MPAI_Site_Health')) {
                    require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-site-health.php';
                }
                $site_health = new MPAI_Site_Health();
                $mp_data['system_info'] = $site_health->get_complete_info();
            }
            
            return [
                'success' => true,
                'message' => 'MemberPress data with system info retrieved successfully',
                'data' => $mp_data
            ];
        }
        
        // For other types, use existing implementation
        // ...existing code...
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error executing MemberPress info tool: ' . $e->getMessage()
        ];
    }
}
```

## Phase 4: Update User Interface

### Step 1: Add Site Health Tab to Diagnostic Page

Add a dedicated Site Health tab to the diagnostic page:

```php
<div id="site-health-tab" class="tab-content" style="display: none;">
    <h2>WordPress Site Health</h2>
    <p>This tab shows comprehensive information about your WordPress installation, server environment, and MemberPress configuration.</p>
    
    <div class="site-health-actions">
        <button id="refresh-site-health" class="button">Refresh Data</button>
        <button id="export-site-health" class="button">Export as JSON</button>
    </div>
    
    <div id="site-health-sections" class="site-health-sections">
        <!-- Sections will be populated via JavaScript -->
        <div class="loading">Loading site health information...</div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Load site health data via AJAX
    function loadSiteHealthData() {
        $('#site-health-sections').html('<div class="loading">Loading site health information...</div>');
        
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'mpai_get_site_health',
                nonce: '<?php echo wp_create_nonce('mpai_diagnostic'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    renderSiteHealthData(response.data);
                } else {
                    $('#site-health-sections').html('<div class="error">Error: ' + response.message + '</div>');
                }
            },
            error: function() {
                $('#site-health-sections').html('<div class="error">Error connecting to server</div>');
            }
        });
    }
    
    // Render site health data in sections
    function renderSiteHealthData(data) {
        var html = '';
        
        // Render each section
        for (var section in data) {
            if (data.hasOwnProperty(section)) {
                html += '<div class="site-health-section">';
                html += '<h3>' + formatSectionName(section) + '</h3>';
                html += '<table class="widefat striped">';
                html += '<thead><tr><th>Setting</th><th>Value</th></tr></thead>';
                html += '<tbody>';
                
                for (var item in data[section]) {
                    if (data[section].hasOwnProperty(item)) {
                        var label = data[section][item].label || formatItemName(item);
                        var value = data[section][item].value || 'Unknown';
                        
                        html += '<tr>';
                        html += '<td>' + label + '</td>';
                        html += '<td>' + value + '</td>';
                        html += '</tr>';
                    }
                }
                
                html += '</tbody></table>';
                html += '</div>';
            }
        }
        
        $('#site-health-sections').html(html);
    }
    
    // Format section name (e.g., "wp-core" -> "WordPress Core")
    function formatSectionName(section) {
        var formatted = section.replace(/-/g, ' ');
        return formatted.replace(/\b\w/g, function(l) { return l.toUpperCase(); });
    }
    
    // Format item name (e.g., "php_version" -> "PHP Version")
    function formatItemName(item) {
        var formatted = item.replace(/_/g, ' ');
        return formatted.replace(/\b\w/g, function(l) { return l.toUpperCase(); });
    }
    
    // Export data as JSON
    $('#export-site-health').on('click', function() {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'mpai_get_site_health',
                nonce: '<?php echo wp_create_nonce('mpai_diagnostic'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    var dataStr = JSON.stringify(response.data, null, 2);
                    var dataUri = 'data:application/json;charset=utf-8,' + encodeURIComponent(dataStr);
                    
                    var exportLink = document.createElement('a');
                    exportLink.setAttribute('href', dataUri);
                    exportLink.setAttribute('download', 'site-health-' + Date.now() + '.json');
                    document.body.appendChild(exportLink);
                    exportLink.click();
                    document.body.removeChild(exportLink);
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Error connecting to server');
            }
        });
    });
    
    // Refresh data
    $('#refresh-site-health').on('click', loadSiteHealthData);
    
    // Initial load
    loadSiteHealthData();
});
</script>
```

### Step 2: Add AJAX Handler

Add an AJAX handler to retrieve Site Health data:

Edit `includes/class-mpai-admin.php` and add:

```php
/**
 * Register AJAX handlers
 */
public function register_ajax_handlers() {
    // ...existing handlers...
    
    // Site Health
    add_action('wp_ajax_mpai_get_site_health', array($this, 'ajax_get_site_health'));
}

/**
 * AJAX handler for Site Health data
 */
public function ajax_get_site_health() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mpai_diagnostic')) {
        wp_send_json_error(array('message' => 'Invalid security token.'));
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'You do not have permission to access this data.'));
    }
    
    // Get site health data
    require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-site-health.php';
    $site_health = new MPAI_Site_Health();
    $data = $site_health->get_complete_info();
    
    wp_send_json_success($data);
}
```

## Phase 5: Update System Prompts

Update the system prompts to inform the AI about the new system information capabilities:

Edit the relevant system prompts files to include information about the new capabilities:

```
You can now access detailed system information using the memberpress_info tool with type="system_info".
This provides comprehensive data about the WordPress installation, server environment, database, and plugin settings.

Use this for troubleshooting or when the user asks about their system configuration.

Example:
```json
{
  "tool": "memberpress_info",
  "parameters": {
    "type": "system_info"
  }
}
```

You can also combine MemberPress data with system information:
```json
{
  "tool": "memberpress_info",
  "parameters": {
    "type": "all",
    "include_system_info": true
  }
}
```
```

## Testing Plan

1. Test Site Health integration on WordPress 5.2+ (with Site Health API)
2. Test fallback implementation on WordPress versions before 5.2
3. Verify all existing diagnostic functionality continues to work
4. Test the memberpress_info tool with system_info and include_system_info options
5. Test the UI updates in various browsers
6. Verify the AI can correctly use the new system information functionality

## Rollout Timeline

1. Development and testing: 1-2 days
2. Code review: 1 day
3. Staging deployment and integration testing: 1 day
4. Production deployment: 1 day

Total estimated time: 4-5 days

## Documentation Updates

1. Update user guide to include information about the new Site Health integration
2. Add new documentation for AI agents about using system information
3. Update diagnostic tool documentation
4. Add examples of using the memberpress_info tool with system_info parameter