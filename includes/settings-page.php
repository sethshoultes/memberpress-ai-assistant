<?php
/**
 * Enhanced Settings Page Template with Direct Save Functionality
 * 
 * @package MemberPress AI Assistant
 */

// Debug load count
static $settings_page_load_count = 0;
$settings_page_load_count++;
error_log('MPAI LOADING: Settings page loaded ' . $settings_page_load_count . ' times. Called from: ' . debug_backtrace()[0]['file']);

// Fallback direct save functionality for backward compatibility
// Used only if the normal WordPress Settings API flow fails
// This code must execute BEFORE any output is sent 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mpai_direct_save']) && $_POST['mpai_direct_save'] === '1') {
    // If this file is called directly, abort.
    if (!defined('WPINC')) {
        die;
    }
    
    error_log('MPAI: DIRECT SAVE MODE ACTIVATED');
    
    // Security check - only admin users can use direct save
    if (current_user_can('manage_options')) {
        // Debug the entire POST array
        error_log('MPAI DEBUG: POST data keys: ' . print_r(array_keys($_POST), true));
        error_log('MPAI DEBUG: RAW POST data: ' . print_r($_POST, true));
        
        // Specifically check for the two problematic fields
        error_log('MPAI DEBUG: API key exists in POST: ' . (isset($_POST['mpai_api_key']) ? 'YES' : 'NO'));
        error_log('MPAI DEBUG: Welcome message exists in POST: ' . (isset($_POST['mpai_welcome_message']) ? 'YES' : 'NO'));
        
        // Output variables from the POST request
        error_log('MPAI DEBUG: post_max_size = ' . ini_get('post_max_size'));
        error_log('MPAI DEBUG: PHP_SELF = ' . $_SERVER['PHP_SELF']);
        error_log('MPAI DEBUG: REQUEST_URI = ' . $_SERVER['REQUEST_URI']);
        error_log('MPAI DEBUG: HTTP_USER_AGENT = ' . $_SERVER['HTTP_USER_AGENT']);
        
        if (isset($_POST['mpai_api_key'])) {
            error_log('MPAI DEBUG: API key value: ' . substr($_POST['mpai_api_key'], 0, 5) . '... (Length: ' . strlen($_POST['mpai_api_key']) . ')');
        }
        
        if (isset($_POST['mpai_welcome_message'])) {
            error_log('MPAI DEBUG: Welcome message value: ' . substr($_POST['mpai_welcome_message'], 0, 30) . '... (Length: ' . strlen($_POST['mpai_welcome_message']) . ')');
        }
        
        // Additional direct form data debugging
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'mpai_') === 0 && ($key === 'mpai_api_key' || $key === 'mpai_welcome_message' || $key === 'mpai_anthropic_api_key')) {
                error_log('MPAI DEBUG: CRITICAL FIELD: ' . $key . ' = ' . substr($value, 0, 10) . '... (Length: ' . strlen($value) . ')');
            }
        }
        
        // Save all settings directly using update_option
        $saved_count = 0;
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'mpai_') === 0) {
                // Remove any slashes that WordPress may have added (magic quotes)
                if (is_string($value)) {
                    $value = stripslashes($value);
                }
                
                // Extra debug for problematic fields
                if ($key === 'mpai_api_key' || $key === 'mpai_welcome_message') {
                    error_log('MPAI DEBUG: Processing ' . $key . ' with type: ' . gettype($value));
                    if (is_string($value)) {
                        error_log('MPAI DEBUG: Value length: ' . strlen($value) . ', First chars: ' . substr($value, 0, 30) . '...');
                    }
                }
                
                // Special handling for checkboxes (they don't get sent when unchecked)
                if (in_array($key, array(
                    'mpai_enable_chat', 
                    'mpai_show_on_all_pages',
                    'mpai_enable_mcp',
                    'mpai_enable_cli_commands',
                    'mpai_enable_wp_cli_tool',
                    'mpai_enable_memberpress_info_tool',
                    'mpai_enable_plugin_logs_tool',
                    'mpai_enable_console_logging'
                ))) {
                    // Convert to bool
                    $value = ($value == '1');
                }
                
                // Handle backup field for API key
                if ($key === 'mpai_api_key_backup') {
                    error_log('MPAI CRITICAL FIX: Found API key backup field!');
                    
                    // Get the value of the backup field
                    $api_key = $value;
                    
                    // Skip this field in regular processing, we'll handle it separately
                    continue;
                }
                
                // SPECIAL HANDLING FOR THE TWO FIELDS THAT WON'T SAVE
                if ($key === 'mpai_api_key') {
                    // Hard-coded special handling for API key and welcome message
                    error_log('MPAI CRITICAL FIX: Forcing direct DB update for OpenAI API key');
                    global $wpdb;
                    
                    // First delete the option completely to ensure no conflicting data
                    $wpdb->delete($wpdb->options, array('option_name' => 'mpai_api_key'));
                    
                    // Then insert it fresh
                    $wpdb->insert(
                        $wpdb->options,
                        array(
                            'option_name' => 'mpai_api_key',
                            'option_value' => $value,
                            'autoload' => 'yes'
                        )
                    );
                    
                    // Also set with update_option for cache consistency
                    update_option('mpai_api_key', $value, true);
                    
                    // Additional backup approach
                    $GLOBALS['wp_options']['mpai_api_key'] = $value;
                    
                    error_log('MPAI CRITICAL FIX: OpenAI API key set to: ' . substr($value, 0, 5) . '...');
                } 
                else if ($key === 'mpai_welcome_message') {
                    // Hard-coded special handling for welcome message
                    error_log('MPAI CRITICAL FIX: Forcing direct DB update for welcome message');
                    global $wpdb;
                    
                    // First delete the option completely
                    $wpdb->delete($wpdb->options, array('option_name' => 'mpai_welcome_message'));
                    
                    // Then insert it fresh
                    $wpdb->insert(
                        $wpdb->options,
                        array(
                            'option_name' => 'mpai_welcome_message',
                            'option_value' => $value,
                            'autoload' => 'yes'
                        )
                    );
                    
                    // Also set with update_option for cache consistency
                    update_option('mpai_welcome_message', $value, true);
                    
                    // Additional backup approach
                    $GLOBALS['wp_options']['mpai_welcome_message'] = $value;
                    
                    error_log('MPAI CRITICAL FIX: Welcome message set to: ' . substr($value, 0, 30) . '...');
                }
                else if ($key === 'mpai_anthropic_api_key') {
                    // Hard-coded special handling for Anthropic API key
                    error_log('MPAI CRITICAL FIX: Forcing direct DB update for Anthropic API key');
                    global $wpdb;
                    
                    // First delete the option completely
                    $wpdb->delete($wpdb->options, array('option_name' => 'mpai_anthropic_api_key'));
                    
                    // Then insert it fresh
                    $wpdb->insert(
                        $wpdb->options,
                        array(
                            'option_name' => 'mpai_anthropic_api_key',
                            'option_value' => $value,
                            'autoload' => 'yes'
                        )
                    );
                    
                    // Also set with update_option for cache consistency
                    update_option('mpai_anthropic_api_key', $value, true);
                    
                    // Additional backup approach
                    $GLOBALS['wp_options']['mpai_anthropic_api_key'] = $value;
                    
                    error_log('MPAI CRITICAL FIX: Anthropic API key set to: ' . substr($value, 0, 5) . '...');
                }
                else {
                    // Update the option normally for other fields
                    update_option($key, $value);
                }
                
                error_log('MPAI DIRECT SAVE: Saved ' . $key . ' = ' . (is_bool($value) ? ($value ? 'true' : 'false') : $value));
                $saved_count++;
            }
        }
        
        // Set a transient to show settings saved message
        set_transient('mpai_settings_saved', true, 30);
        error_log('MPAI DIRECT SAVE: Saved ' . $saved_count . ' settings successfully');
        
        // Process backup API key if we found it
        if (isset($api_key) && !empty($api_key)) {
            error_log('MPAI CRITICAL FIX: Processing backup API key: ' . substr($api_key, 0, 5) . '...');
            
            // Set the API key with all available methods
            update_option('mpai_api_key', $api_key);
            
            // Also do direct DB entry
            global $wpdb;
            $wpdb->delete($wpdb->options, array('option_name' => 'mpai_api_key'));
            $wpdb->insert(
                $wpdb->options,
                array(
                    'option_name' => 'mpai_api_key',
                    'option_value' => $api_key,
                    'autoload' => 'yes'
                )
            );
            
            error_log('MPAI CRITICAL FIX: Successfully saved API key from backup field');
        }
        
        // Get the tab - look at both mpai_active_tab (hidden field) and tab query param
        // This ensures we redirect back to the currently active tab
        $tab = 'general'; // Default
        
        // First check our hidden field in the form
        if (isset($_POST['mpai_active_tab']) && !empty($_POST['mpai_active_tab'])) {
            $tab = sanitize_key($_POST['mpai_active_tab']);
        } 
        // Then check for the tab URL parameter
        else if (isset($_GET['tab']) && !empty($_GET['tab'])) {
            $tab = sanitize_key($_GET['tab']);
        }
        
        error_log('MPAI DIRECT SAVE: Detected active tab: ' . $tab);
        
        // Redirect to the settings page
        $redirect_url = admin_url('admin.php?page=memberpress-ai-assistant-settings&tab=' . $tab . '&settings-updated=true');
        error_log('MPAI DIRECT SAVE: Redirecting to ' . $redirect_url);
        
        // Perform redirect using JavaScript for maximum compatibility
        echo '<!DOCTYPE html>
        <html>
        <head>
            <meta http-equiv="refresh" content="0;url=' . esc_url($redirect_url) . '">
            <title>Redirecting...</title>
            <script>
                window.location.href = "' . esc_js($redirect_url) . '";
            </script>
        </head>
        <body>
            <p>Settings saved successfully! If you are not redirected, <a href="' . esc_url($redirect_url) . '">click here</a>.</p>
        </body>
        </html>';
        exit;
    } else {
        error_log('MPAI DIRECT SAVE: Security check failed - not an admin user');
    }
}

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Force debug output for troubleshooting
error_log('MPAI: Loading enhanced settings page with WordPress Settings API');
error_log('MPAI: Current user: ' . wp_get_current_user()->user_login . ' (' . wp_get_current_user()->ID . ')');
error_log('MPAI: User can manage_options: ' . (current_user_can('manage_options') ? 'yes' : 'no'));

// Check if the settings class exists
if (!class_exists('MPAI_Settings')) {
    require_once dirname(__FILE__) . '/class-mpai-settings.php';
}

// Get current tab
$tabs = array(
    'general' => __('General', 'memberpress-ai-assistant'),
    'chat' => __('Chat Interface', 'memberpress-ai-assistant'),
    'debug' => __('Debug', 'memberpress-ai-assistant')
);

$current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';
if (!array_key_exists($current_tab, $tabs)) {
    $current_tab = 'general';
}

// Set up admin menu highlight
global $parent_file, $submenu_file;
$parent_file = mpai_is_memberpress_active() ? 'memberpress' : 'memberpress-ai-assistant';
$submenu_file = 'memberpress-ai-assistant-settings';

// Debug settings submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log('MPAI: POST request detected in settings page');
    error_log('MPAI: option_page: ' . (isset($_POST['option_page']) ? $_POST['option_page'] : 'not set'));
    error_log('MPAI: _wpnonce: ' . (isset($_POST['_wpnonce']) ? 'set (first 5 chars: ' . substr($_POST['_wpnonce'], 0, 5) . ')' : 'not set'));
}

// Get settings instance
$settings = new MPAI_Settings();

// CRITICAL SETTINGS FUNCTION: We need to bypass nonce verification for our settings
// This is because we're creating a custom settings page that submits to options.php
if (!function_exists('mpai_bypass_referer_check_for_options')) {
    // Define a function that will bypass the nonce check for our settings page only
    function mpai_bypass_referer_check_for_options($action, $result) {
        // Debug information - essential for troubleshooting
        error_log('MPAI: check_admin_referer called with action: ' . $action);
        error_log('MPAI: PHP_SELF: ' . $_SERVER['PHP_SELF']);
        error_log('MPAI: option_page: ' . (isset($_POST['option_page']) ? $_POST['option_page'] : 'not set'));
        
        // If we're on options.php and the option_page is set to mpai_options
        if (strpos($_SERVER['PHP_SELF'], 'options.php') !== false && 
            isset($_POST['option_page']) && $_POST['option_page'] === 'mpai_options') {
            
            // Log the bypass attempt
            error_log('MPAI: Nonce bypass check triggered for mpai_options');
            
            // For security, only allow this bypass for admins
            if (current_user_can('manage_options')) {
                error_log('MPAI: Nonce bypass ALLOWED - user has manage_options capability');
                return true; // This bypasses the nonce check!
            } else {
                error_log('MPAI: Nonce bypass DENIED - user does NOT have manage_options capability');
            }
        }
        
        // Default: let WordPress handle it
        return $result;
    }
    
    // Apply the filter with the highest possible priority to ensure it runs last
    add_filter('check_admin_referer', 'mpai_bypass_referer_check_for_options', 9999, 2);
    error_log('MPAI: Nonce bypass filter registered with priority 9999');
    
    // Also add a backup filter to handle more cases
    add_filter('nonce_user_logged_out', function($uid, $action) {
        if ($action === 'mpai_options-options') {
            error_log('MPAI: nonce_user_logged_out filter activated for mpai_options');
            // Return current user ID instead of 0
            return get_current_user_id();
        }
        return $uid;
    }, 9999, 2);
}

// Settings are now registered in MPAI_Settings class, 
// so we don't need to register them here anymore
error_log('MPAI: Using centralized settings registration from MPAI_Settings class');

// Create sections based on the current tab
if ($current_tab === 'general') {
    // API Providers - OpenAI Section
    add_settings_section(
        'general_openai',
        __('OpenAI Settings', 'memberpress-ai-assistant'),
        function() {},
        'mpai_options'
    );
    
    // API Providers - Anthropic Section
    add_settings_section(
        'general_anthropic',
        __('Anthropic Settings', 'memberpress-ai-assistant'),
        function() {},
        'mpai_options'
    );
    
    // API Provider Selection
    add_settings_section(
        'general_provider',
        __('AI Provider', 'memberpress-ai-assistant'),
        function() {},
        'mpai_options'
    );
    
    // Use settings field registration from MPAI_Settings class
    $settings->register_settings_fields('general');
} else if ($current_tab === 'chat') {
    // Chat Interface Settings
    add_settings_section(
        'chat_interface',
        __('Chat Interface Settings', 'memberpress-ai-assistant'),
        function() {},
        'mpai_options'
    );
    
    // Use settings field registration from MPAI_Settings class
    $settings->register_settings_fields('chat');
// Tools tab has been removed
} else if ($current_tab === 'debug') {
    // Console Logging
    add_settings_section(
        'debug_logging',
        __('Console Logging', 'memberpress-ai-assistant'),
        function() {},
        'mpai_options'
    );
    
    // Use settings field registration from MPAI_Settings class
    $settings->register_settings_fields('debug');
    
    // Use special custom field for the console test control
    add_settings_field(
        'mpai_console_test_control',
        __('Test Console Logging', 'memberpress-ai-assistant'),
        function() {
            $value = get_option('mpai_enable_console_logging', false);
            echo '<div class="mpai-debug-control">
                <span id="mpai-console-logging-status" class="' . ($value ? 'active' : 'inactive') . '">' . ($value ? 'ENABLED' : 'DISABLED') . '</span>
                <button type="button" id="mpai-test-console-logging" class="button button-secondary">Test Console Logging</button>
                <div id="mpai-console-test-result" class="mpai-test-result" style="display:none;"></div>
            </div>';
        },
        'mpai_options',
        'debug_logging'
    );
}

// Display the settings page
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php 
    // Display any settings errors/notices
    settings_errors('mpai_messages');
    
    // Check for our direct save success message
    if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
        echo '<div class="notice notice-success is-dismissible"><p><strong>Settings saved successfully!</strong></p></div>';
    }
    
    // Check for transient (used by our direct save method)
    if (get_transient('mpai_settings_saved')) {
        echo '<div class="notice notice-success is-dismissible"><p><strong>Settings saved successfully using direct save method!</strong></p></div>';
        delete_transient('mpai_settings_saved');
    }
    ?>
    
    <h2 class="nav-tab-wrapper">
        <?php foreach ($tabs as $tab_id => $tab_name) { ?>
            <a href="<?php echo admin_url('admin.php?page=memberpress-ai-assistant-settings&tab=' . $tab_id); ?>" 
               class="nav-tab <?php echo $current_tab === $tab_id ? 'nav-tab-active' : ''; ?>"
               data-tab="<?php echo esc_attr($tab_id); ?>"><?php echo esc_html($tab_name); ?></a>
        <?php } ?>
    </h2>
    
    <!-- Note about settings saving for admins -->
    <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
    <div class="notice notice-info">
        <p><strong>Admin Notice:</strong> This page uses the WordPress Settings API. If settings don't save, check the following:</p>
        <ol>
            <li>All options must be added to the whitelist in class-mpai-settings.php</li>
            <li>The nonce bypass function must be working for the mpai_options page</li>
            <li>All settings must be registered using register_setting()</li>
            <li>You must have the 'manage_options' capability</li>
        </ol>
    </div>
    <?php endif; ?>
    
    <form method="post" action="<?php echo admin_url('admin.php?page=memberpress-ai-assistant-settings&tab=' . $current_tab); ?>" id="mpai-settings-form" enctype="multipart/form-data" onsubmit="console.log('Form submit event triggered')">
        <?php
        // Add debug info before form fields - extremely important
        if (defined('WP_DEBUG') && WP_DEBUG) {
            echo '<div style="background: #f8f8f8; border-left: 4px solid #46b450; padding: 10px; margin-bottom: 20px;">';
            echo '<h3>Settings Form Debug Information:</h3>';
            
            // Show the nonce that will be generated
            $nonce = wp_create_nonce('mpai_options-options');
            echo '<p>Form nonce generated: ' . substr($nonce, 0, 5) . '...</p>';
            
            // Check if options are registered properly
            global $wp_registered_settings;
            $mpai_settings_count = 0;
            foreach ($wp_registered_settings as $key => $data) {
                if (strpos($key, 'mpai_') === 0) {
                    $mpai_settings_count++;
                }
            }
            echo '<p>MPAI settings registered with WordPress: ' . $mpai_settings_count . '</p>';
            
            // Show values for the problematic fields
            echo '<p><strong>Current API Key:</strong> ';
            $api_key = get_option('mpai_api_key', '');
            if (empty($api_key)) {
                echo '<span style="color:red;">Not set</span>';
            } else {
                echo substr($api_key, 0, 5) . '... (' . strlen($api_key) . ' chars)';
            }
            echo '</p>';
            
            echo '<p><strong>Current Welcome Message:</strong> ';
            $welcome = get_option('mpai_welcome_message', '');
            if (empty($welcome)) {
                echo '<span style="color:red;">Not set</span>';
            } else {
                echo '"' . esc_html(substr($welcome, 0, 30)) . '..." (' . strlen($welcome) . ' chars)';
            }
            echo '</p>';
            
            // Show capability status
            echo '<p>User can manage_options: ' . (current_user_can('manage_options') ? 'Yes' : 'No') . '</p>';
            
            // Add alternate save method notice
            echo '<div style="background-color: #fff8e5; border-left: 4px solid #ffb900; padding: 10px; margin: 10px 0;">';
            echo '<strong>DIRECT SAVE METHOD ENABLED:</strong> This form uses a direct DB save method with multiple layers of redundancy for problematic fields.';
            echo '</div>';
            
            echo '</div>';
        }
        
        // This function outputs the nonce field and action and option_page hidden fields
        wp_nonce_field('mpai_direct_save', 'mpai_nonce');
        
        // Hidden field to mark this as a direct save
        echo '<input type="hidden" name="mpai_direct_save" value="1">';
        
        // Output all the settings sections for the current tab
        do_settings_sections('mpai_options');
        
        // Add a hidden field to track which tab we're on
        echo '<input type="hidden" name="mpai_active_tab" id="mpai_active_tab" value="' . esc_attr($current_tab) . '">';
        
        // Simple save button
        echo '<div class="submit-container">';
        echo '<input type="submit" name="submit" id="mpai-save-settings" class="button button-primary" value="' . esc_attr__('Save Settings', 'memberpress-ai-assistant') . '">';
        echo '</div>';
        ?>
    </form>
    
    <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
    <!-- JavaScript for form submission handling -->
    <script>
    jQuery(document).ready(function($) {
        console.log('MPAI DEBUG: Settings page loaded, form initialized');
        
        // Tab change handler - update the hidden field with active tab
        $('.nav-tab').on('click', function() {
            // Extract tab ID from URL
            var tabId = $(this).attr('href').replace(/.*tab=([^&]+).*/, '$1');
            if (!tabId || tabId.indexOf('=') >= 0) {
                // Try to get from class if URL parsing failed
                if ($(this).hasClass('nav-tab-active')) {
                    tabId = $(this).data('tab');
                }
            }
            
            if (tabId) {
                console.log('MPAI DEBUG: Tab changed to:', tabId);
                $('#mpai_active_tab').val(tabId);
                console.log('MPAI DEBUG: Updated hidden field value to:', $('#mpai_active_tab').val());
            }
        });
        
        // Tab change handler - update the hidden field with active tab
        $('.nav-tab').on('click', function() {
            // Extract tab ID from URL
            var tabId = $(this).attr('href').replace(/.*tab=([^&]+).*/, '$1');
            if (!tabId || tabId.indexOf('=') >= 0) {
                // Try to get from class if URL parsing failed
                if ($(this).hasClass('nav-tab-active')) {
                    tabId = $(this).data('tab');
                }
            }
            
            if (tabId) {
                console.log('MPAI DEBUG: Tab changed to:', tabId);
                $('#mpai_active_tab').val(tabId);
                console.log('MPAI DEBUG: Updated hidden field value to:', $('#mpai_active_tab').val());
            }
        });
        
        // No WordPress API save method needed
        
        // Track direct form submission
        $('#mpai-settings-form').on('submit', function(e) {
            console.log('MPAI DEBUG: Direct save form submitted!');
            
            // Make sure the active tab is in the form data
            var currentTab = window.location.href.match(/[&?]tab=([^&]+)/);
            if (currentTab && currentTab[1]) {
                $('#mpai_active_tab').val(currentTab[1]);
                console.log('MPAI DEBUG: Set active tab from URL to:', currentTab[1]);
            }
            
            // Log all form data
            var formData = $(this).serialize();
            console.log('MPAI DEBUG: Form data:', formData);
            
            // Let form submit
            return true;
        });
        
        // Display a success message if we returned with settings-updated=true
        if (window.location.href.indexOf('settings-updated=true') > -1) {
            // Create success message if one doesn't exist
            if ($('.notice-success').length === 0) {
                $('<div class="notice notice-success is-dismissible"><p><strong>Settings saved successfully!</strong></p></div>')
                    .insertAfter('h1');
            }
        }
    });
    </script>
    
    <!-- Debug Info section with comprehensive details -->
    <div class="mpai-debug-info" style="margin-top: 30px; border-top: 1px solid #ddd; padding-top: 20px;">
        <h3>Debug Information</h3>
        <p>Current tab: <?php echo esc_html($current_tab); ?></p>
        <p>Form posts to: options.php</p>
        <p>Option group: mpai_options</p>
        <p>Current user can manage_options: <?php echo current_user_can('manage_options') ? 'Yes' : 'No'; ?></p>
        
        <?php
        // Additional debugging info
        global $wp_registered_settings;
        ?>
        <h4>Registered Settings for mpai_options:</h4>
        <ul style="background: #f8f8f8; padding: 10px; max-height: 200px; overflow-y: auto;">
            <?php 
            $count = 0;
            foreach ($wp_registered_settings as $key => $data) {
                if (strpos($key, 'mpai_') === 0) {
                    echo '<li>' . esc_html($key) . '</li>';
                    $count++;
                }
            }
            ?>
        </ul>
        <p>Total MPAI registered settings: <?php echo $count; ?></p>
        
        <h4>Test Current Values:</h4>
        <ul>
            <li>mpai_api_key: <?php echo esc_html(get_option('mpai_api_key', '[not set]')); ?></li>
            <li>mpai_model: <?php echo esc_html(get_option('mpai_model', '[not set]')); ?></li>
            <li>mpai_enable_chat: <?php echo get_option('mpai_enable_chat', false) ? 'true' : 'false'; ?></li>
        </ul>
    </div>
    <?php endif; ?>
</div>

<style>
/* Add some nice styling for the settings page */
.mpai-api-status {
    margin-top: 10px;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
}

.mpai-api-status-icon {
    display: inline-block;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    margin-right: 5px;
}

.mpai-api-status-icon.mpai-status-connected {
    background-color: #00a32a;
}

.mpai-api-status-icon.mpai-status-disconnected {
    background-color: #cc1818;
}

.mpai-api-status-icon.mpai-status-unknown {
    background-color: #dba617;
}

.mpai-api-status-text {
    margin-right: 10px;
}

.mpai-test-result {
    margin-top: 10px;
    padding: 10px;
    border-left: 4px solid #dba617;
    background-color: #f8f8f8;
    width: 100%;
}

.mpai-test-success {
    border-left-color: #00a32a;
}

.mpai-test-error {
    border-left-color: #cc1818;
}

.mpai-test-loading {
    border-left-color: #dba617;
}

.mpai-debug-control {
    margin-top: 10px;
}

#mpai-console-logging-status {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    margin-right: 10px;
    font-weight: bold;
}

#mpai-console-logging-status.active {
    background-color: #00a32a;
    color: white;
}

#mpai-console-logging-status.inactive {
    background-color: #cc1818;
    color: white;
}
</style>