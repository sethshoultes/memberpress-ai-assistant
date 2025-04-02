# MemberPress AI Assistant Independent Operation Implementation Plan

## Overview

This document outlines the implementation plan for making the MemberPress AI Assistant plugin work without requiring MemberPress to be installed. This will allow users to install and use the AI Assistant independently, with graceful degradation of MemberPress-specific features and strategic upsell opportunities.

## Goals

1. Make the plugin function properly when MemberPress is not installed
2. Maintain full functionality when MemberPress is installed
3. Add upsell touch points for users without MemberPress
4. Implement graceful degradation of MemberPress-dependent features
5. Ensure a seamless experience for all users

## Implementation Details

### 1. Modify Main Plugin File (`memberpress-ai-assistant.php`)

#### Current Implementation

Currently, the plugin displays a notice and doesn't provide functionality if MemberPress is not active:

```php
public function check_memberpress() {
    if (!class_exists('MeprAppCtrl')) {
        add_action('admin_notices', array($this, 'memberpress_missing_notice'));
    }
}

public function memberpress_missing_notice() {
    ?>
    <div class="error">
        <p><?php _e('MemberPress AI Assistant requires MemberPress to be installed and activated.', 'memberpress-ai-assistant'); ?></p>
    </div>
    <?php
}
```

The menu is also added as a submenu under MemberPress:

```php
public function add_admin_menu() {
    add_submenu_page(
        'memberpress',
        __('AI Assistant', 'memberpress-ai-assistant'),
        __('AI Assistant', 'memberpress-ai-assistant'),
        'manage_options',
        'memberpress-ai-assistant',
        array($this, 'display_admin_page')
    );
    
    // Register the settings page
    $settings_page = add_submenu_page(
        'memberpress-ai-assistant',
        __('Settings', 'memberpress-ai-assistant'),
        __('Settings', 'memberpress-ai-assistant'),
        'manage_options',
        'memberpress-ai-assistant-settings',
        array($this, 'display_settings_page')
    );
}
```

#### Modified Implementation

We'll modify the plugin to:
1. Check if MemberPress is active and store the status
2. Add menus differently based on MemberPress availability
3. Show an informative notice with upsell instead of an error

```php
private $has_memberpress = false;

public function check_memberpress() {
    $this->has_memberpress = class_exists('MeprAppCtrl');
    
    if (!$this->has_memberpress) {
        add_action('admin_notices', array($this, 'memberpress_upsell_notice'));
    }
}

public function memberpress_upsell_notice() {
    if (isset($_GET['page']) && (strpos($_GET['page'], 'memberpress-ai-assistant') === 0)) {
        ?>
        <div class="notice notice-info is-dismissible mpai-upsell-notice">
            <h3><?php _e('Enhance Your AI Assistant with MemberPress', 'memberpress-ai-assistant'); ?></h3>
            <p><?php _e('You\'re currently using the standalone version of MemberPress AI Assistant. Upgrade to the full MemberPress platform to unlock advanced membership management features including:', 'memberpress-ai-assistant'); ?></p>
            <ul class="mpai-upsell-features">
                <li><?php _e('Create and sell membership levels', 'memberpress-ai-assistant'); ?></li>
                <li><?php _e('Protect content with flexible rules', 'memberpress-ai-assistant'); ?></li>
                <li><?php _e('Process payments and subscriptions', 'memberpress-ai-assistant'); ?></li>
                <li><?php _e('Access detailed reporting', 'memberpress-ai-assistant'); ?></li>
                <li><?php _e('Unlock all AI Assistant features', 'memberpress-ai-assistant'); ?></li>
            </ul>
            <p><a href="https://memberpress.com/plans/?utm_source=ai_assistant&utm_medium=plugin&utm_campaign=upsell" class="button button-primary" target="_blank"><?php _e('Learn More About MemberPress', 'memberpress-ai-assistant'); ?></a></p>
        </div>
        <style>
            .mpai-upsell-notice {
                border-left-color: #2271b1;
                padding: 10px 15px;
            }
            .mpai-upsell-notice h3 {
                margin-top: 0.5em;
                margin-bottom: 0.5em;
            }
            .mpai-upsell-features {
                list-style-type: disc;
                padding-left: 20px;
                margin-bottom: 15px;
            }
        </style>
        <?php
    }
}

public function add_admin_menu() {
    if ($this->has_memberpress) {
        // If MemberPress is active, add as a submenu to MemberPress
        add_submenu_page(
            'memberpress',
            __('AI Assistant', 'memberpress-ai-assistant'),
            __('AI Assistant', 'memberpress-ai-assistant'),
            'manage_options',
            'memberpress-ai-assistant',
            array($this, 'display_admin_page')
        );
    } else {
        // If MemberPress is not active, add as a top-level menu
        add_menu_page(
            __('MemberPress AI', 'memberpress-ai-assistant'),
            __('MemberPress AI', 'memberpress-ai-assistant'),
            'manage_options',
            'memberpress-ai-assistant',
            array($this, 'display_admin_page'),
            MPAI_PLUGIN_URL . 'assets/images/memberpress-logo.svg',
            30
        );
    }
    
    // Register the settings page (always as a submenu of our main page)
    $settings_page = add_submenu_page(
        'memberpress-ai-assistant',
        __('Settings', 'memberpress-ai-assistant'),
        __('Settings', 'memberpress-ai-assistant'),
        'manage_options',
        'memberpress-ai-assistant-settings',
        array($this, 'display_settings_page')
    );
    
    // Make sure settings are properly registered for this page
    add_action('load-' . $settings_page, array($this, 'settings_page_load'));
}
```

### 2. Modify MemberPress API Integration (`class-mpai-memberpress-api.php`)

#### Current Implementation

The current MemberPress API class assumes MemberPress is available and tries to access MemberPress-specific tables and functions.

#### Modified Implementation

We'll modify the class to:
1. Check if MemberPress is available before trying to access MemberPress-specific data
2. Return informative placeholder data with upsell messaging when MemberPress is not available
3. Maintain full functionality when MemberPress is available

```php
class MPAI_MemberPress_API {
    /**
     * MemberPress API key
     *
     * @var string
     */
    private $api_key;

    /**
     * Base URL for API requests
     *
     * @var string
     */
    private $base_url;
    
    /**
     * Whether MemberPress is available
     *
     * @var bool
     */
    private $has_memberpress;

    /**
     * Constructor
     */
    public function __construct() {
        $this->api_key = get_option('mpai_memberpress_api_key', '');
        $this->base_url = site_url('/wp-json/mp/v1/');
        $this->has_memberpress = class_exists('MeprAppCtrl');
    }
    
    /**
     * Check if MemberPress is available
     *
     * @return bool
     */
    public function is_memberpress_available() {
        return $this->has_memberpress;
    }
    
    /**
     * Generate upsell response for when MemberPress is not available
     *
     * @param string $feature The MemberPress feature being requested
     * @return array Response with upsell message
     */
    private function get_upsell_response($feature) {
        return array(
            'status' => 'memberpress_not_available',
            'message' => sprintf(
                __('This feature requires MemberPress to be installed and activated. %sLearn more about MemberPress%s', 'memberpress-ai-assistant'),
                '<a href="https://memberpress.com/plans/?utm_source=ai_assistant&utm_medium=api&utm_campaign=upsell" target="_blank">',
                '</a>'
            ),
            'feature' => $feature,
            'memberpress_url' => 'https://memberpress.com/plans/?utm_source=ai_assistant&utm_medium=api&utm_campaign=upsell'
        );
    }

    /**
     * Make a request to the MemberPress data
     *
     * @param string $endpoint The API endpoint concept (members, memberships, etc.)
     * @param string $method HTTP method concept (GET, POST, etc.) - used to determine action
     * @param array $data Data for filtering or creating
     * @return array|WP_Error The data response or error
     */
    public function request($endpoint, $method = 'GET', $data = array()) {
        // Check if MemberPress is available
        if (!$this->has_memberpress) {
            return $this->get_upsell_response($endpoint);
        }
        
        // Original implementation continues here...
        switch ($endpoint) {
            case 'members':
            case 'users':
                return $this->get_members_from_db($data);
                
            case 'memberships':
            case 'products':
                return $this->get_memberships_from_db($data);
                
            case 'transactions':
                return $this->get_transactions_from_db($data);
                
            case 'subscriptions':
                return $this->get_subscriptions_from_db($data);
                
            default:
                return new WP_Error(
                    'invalid_endpoint',
                    'Invalid endpoint: ' . $endpoint,
                    array('endpoint' => $endpoint)
                );
        }
    }
    
    // Add similar checks to all methods...
    
    /**
     * Get members directly from WordPress database
     *
     * @param array $params Query parameters
     * @param bool $formatted Whether to return formatted tabular data
     * @return array|WP_Error|string The members or error
     */
    public function get_members($params = array(), $formatted = false) {
        if (!$this->has_memberpress) {
            if ($formatted) {
                return __("MemberPress is not installed. Install MemberPress to manage members and access detailed member information.", 'memberpress-ai-assistant');
            }
            return $this->get_upsell_response('members');
        }
        
        $result = $this->get_members_from_db($params);
        
        if ($formatted && !is_wp_error($result)) {
            return $this->format_members_as_table($result);
        }
        
        return $result;
    }
}
```

### 3. Update Admin Page (`admin-page.php`)

We need to modify the admin page to show relevant content based on whether MemberPress is installed.

```php
// In admin-page.php
$memberpress_api = new MPAI_MemberPress_API();
$has_memberpress = $memberpress_api->is_memberpress_available();

if (!$has_memberpress) {
    // Show a prominent upsell section
    ?>
    <div class="mpai-section mpai-upsell-section">
        <h2><?php _e('Supercharge Your Membership Business with MemberPress', 'memberpress-ai-assistant'); ?></h2>
        <div class="mpai-upsell-columns">
            <div class="mpai-upsell-column">
                <h3><?php _e('Why Choose MemberPress?', 'memberpress-ai-assistant'); ?></h3>
                <ul>
                    <li><?php _e('Create unlimited membership levels', 'memberpress-ai-assistant'); ?></li>
                    <li><?php _e('Protect your valuable content', 'memberpress-ai-assistant'); ?></li>
                    <li><?php _e('Accept payments with multiple gateways', 'memberpress-ai-assistant'); ?></li>
                    <li><?php _e('Generate detailed reports', 'memberpress-ai-assistant'); ?></li>
                    <li><?php _e('Automate emails and drip content', 'memberpress-ai-assistant'); ?></li>
                    <li><?php _e('Unlock advanced AI features', 'memberpress-ai-assistant'); ?></li>
                </ul>
                <p><a href="https://memberpress.com/plans/?utm_source=ai_assistant&utm_medium=admin&utm_campaign=upsell" class="button button-primary button-hero" target="_blank"><?php _e('Get MemberPress Now', 'memberpress-ai-assistant'); ?></a></p>
            </div>
            <div class="mpai-upsell-column">
                <img src="<?php echo MPAI_PLUGIN_URL; ?>assets/images/memberpress-screenshot.png" alt="MemberPress Screenshot" class="mpai-upsell-image">
            </div>
        </div>
    </div>
    <?php
}

// Continue with regular AI Assistant content...
?>
```

### 4. Add CSS Styles for Upsell Elements

Add the following CSS to `assets/css/admin.css`:

```css
/* Upsell Styles */
.mpai-upsell-section {
    background-color: #f8f9fa;
    border: 1px solid #dcdcde;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 30px;
}

.mpai-upsell-columns {
    display: flex;
    flex-wrap: wrap;
    gap: 30px;
}

.mpai-upsell-column {
    flex: 1;
    min-width: 300px;
}

.mpai-upsell-column h3 {
    margin-top: 0;
}

.mpai-upsell-column ul {
    list-style-type: disc;
    padding-left: 20px;
}

.mpai-upsell-image {
    max-width: 100%;
    height: auto;
    border-radius: 4px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.mpai-limited-feature {
    position: relative;
}

.mpai-limited-feature:after {
    content: "Requires MemberPress";
    position: absolute;
    top: 10px;
    right: 10px;
    background: #2271b1;
    color: white;
    padding: 5px 10px;
    border-radius: 3px;
    font-size: 12px;
}

.mpai-feature-badge {
    display: inline-block;
    background: #2271b1;
    color: white;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    margin-left: 10px;
    vertical-align: middle;
}
```

### 5. Modify Chat Interface for Graceful Degradation

Update the chat interface to handle MemberPress-specific questions appropriately when MemberPress isn't installed.

In `class-mpai-chat.php`, modify the system prompt:

```php
// In class-mpai-chat.php
$has_memberpress = class_exists('MeprAppCtrl');

// Modify the system prompt based on MemberPress availability
if ($has_memberpress) {
    $system_prompt .= "You have access to MemberPress data. You can use the memberpress_info tool to get information about memberships, members, and transactions.\n";
} else {
    $system_prompt .= "MemberPress is not installed on this site. When asked about MemberPress-specific features, explain that MemberPress needs to be installed and suggest upgrading. You can mention the benefits of MemberPress for membership sites.\n";
}
```

## Testing Plan

1. **Test without MemberPress installed:**
   - Verify the plugin activates without errors
   - Check that admin menus appear correctly
   - Verify the upsell notices display properly
   - Test the chat interface works for non-MemberPress queries
   - Ensure MemberPress-specific queries get appropriate upsell responses

2. **Test with MemberPress installed:**
   - Verify all functionality works as before
   - Check that no upsell notices are displayed
   - Confirm MemberPress-specific queries work correctly

## Required Assets

1. MemberPress logo SVG (for menu icon)
2. MemberPress screenshot image (for upsell section)
3. CSS for styling upsell components 

## Implementation Timeline

1. **Phase 1: Core Modifications**
   - Update main plugin file
   - Modify MemberPress API class
   - Add MemberPress availability checks to relevant files

2. **Phase 2: UI Enhancements**
   - Create upsell components
   - Add CSS styles
   - Update admin page layout

3. **Phase 3: Testing and Refinement**
   - Test without MemberPress
   - Test with MemberPress
   - Refine based on test results

## Conclusion

By implementing these changes, the MemberPress AI Assistant will function independently from the core MemberPress plugin. This allows a wider audience to benefit from the AI Assistant's capabilities while providing strategic upsell opportunities for users who might benefit from the full MemberPress platform.

The implementation maintains backward compatibility for users who already have MemberPress installed while ensuring a good experience for standalone users. The graceful degradation approach means the plugin will still be fully functional for core features, only displaying upsell messaging when users attempt to access MemberPress-specific functionality.