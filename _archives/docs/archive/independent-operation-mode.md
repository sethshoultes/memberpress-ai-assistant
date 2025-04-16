# MemberPress AI Assistant Independent Operation Mode

**STATUS: Implemented in version 1.5.8 (2025-04-02)**

## Overview

The Independent Operation Mode allows the MemberPress AI Assistant plugin to function correctly even when the core MemberPress plugin is not installed. This implementation provides graceful degradation of MemberPress-specific features, strategic upsell opportunities, and ensures a seamless experience for all users regardless of whether they have the core MemberPress plugin installed.

## Key Features

1. **Smart Menu Placement**:
   - Top-level menu when MemberPress is not present
   - Submenu under MemberPress when available
   - Consistent settings access in both configurations

2. **Graceful Degradation**:
   - Informative responses for MemberPress-specific commands when MemberPress is absent
   - Clear indication of features that require MemberPress
   - Full functionality maintained for non-MemberPress features

3. **MemberPress API Enhancements**:
   - Comprehensive availability checks before accessing MemberPress data
   - Fallback data and responses for when MemberPress is not available
   - Centralized method for determining MemberPress availability

4. **Strategic Upsell Integration**:
   - Contextual promotion of MemberPress benefits
   - Non-intrusive upsell messaging within AI responses
   - Visual indicators of enhanced capabilities with MemberPress

## Implementation Details

### Menu Placement Logic

The plugin uses intelligent menu placement based on MemberPress availability:

```php
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
    
    // Settings page is always a submenu of our main page
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

### Enhanced API Class

The MemberPress API class has been updated to handle both scenarios:

```php
class MPAI_MemberPress_API {
    private $has_memberpress;
    
    public function __construct() {
        $this->has_memberpress = class_exists('MeprAppCtrl');
    }
    
    public function is_memberpress_available() {
        return $this->has_memberpress;
    }
    
    private function get_fallback_response($feature) {
        return array(
            'status' => 'memberpress_not_available',
            'message' => sprintf(
                __('This feature requires MemberPress. %sLearn more%s', 'memberpress-ai-assistant'),
                '<a href="https://memberpress.com/plans/?utm_source=ai_assistant" target="_blank">',
                '</a>'
            ),
            'feature' => $feature
        );
    }
    
    public function get_members($params = array(), $formatted = false) {
        if (!$this->has_memberpress) {
            if ($formatted) {
                return $this->format_fallback_message('members');
            }
            return $this->get_fallback_response('members');
        }
        
        // Regular implementation continues...
    }
    
    // Additional methods follow the same pattern
}
```

### System Prompt Adaptation

The AI system prompt adjusts based on MemberPress availability:

```php
// Modify the system prompt
if ($has_memberpress) {
    $system_prompt .= "You have access to MemberPress data including memberships, " .
                     "members, transactions, and subscriptions.\n";
} else {
    $system_prompt .= "MemberPress is not installed. When asked about MemberPress " .
                     "features, explain that MemberPress needs to be installed and " .
                     "suggest its benefits for membership sites.\n";
}
```

### Contextual UI Components

The admin interface shows different content based on MemberPress availability:

```php
$has_memberpress = $api->is_memberpress_available();

// Display MemberPress stats section only if available
if ($has_memberpress) {
    ?>
    <div class="mpai-stats-card">
        <h2><?php _e('MemberPress Stats', 'memberpress-ai-assistant'); ?></h2>
        <div class="mpai-stats-grid">
            <!-- MemberPress stats display -->
        </div>
    </div>
    <?php
} else {
    ?>
    <div class="mpai-upsell-card">
        <h2><?php _e('Enhance with MemberPress', 'memberpress-ai-assistant'); ?></h2>
        <p><?php _e('Install MemberPress to unlock the full potential of your AI Assistant with membership data analysis, transaction insights, and more.', 'memberpress-ai-assistant'); ?></p>
        <a href="https://memberpress.com/plans/?utm_source=ai_assistant&utm_medium=admin" 
           class="button button-primary" target="_blank">
           <?php _e('Learn More About MemberPress', 'memberpress-ai-assistant'); ?>
        </a>
    </div>
    <?php
}
```

## AI Response Handling

When MemberPress isn't installed, the AI Assistant responds to MemberPress-specific queries with helpful context:

### Without MemberPress:
```
User: How many active members do I have?

AI: I don't see MemberPress installed on your site. To get detailed member information, 
you would need to install MemberPress, which provides comprehensive membership 
management features. MemberPress would allow you to track active members, 
view subscription data, and monitor membership performance.

Would you like me to help you with something else I can do without MemberPress?
```

### With MemberPress:
```
User: How many active members do I have?

AI: You currently have 47 active members across all membership levels.

Here's a breakdown by membership level:
- Premium Plan: 23 active members
- Basic Plan: 19 active members
- Trial Plan: 5 active members
```

## Benefits

1. **Wider Accessibility**:
   - Users can benefit from AI Assistant without needing MemberPress
   - Gradual introduction to MemberPress ecosystem

2. **Improved User Experience**:
   - No error messages or blocked functionality
   - Clear indication of enhanced capabilities with MemberPress
   - Contextual promotion of relevant features

3. **Seamless Transition**:
   - When users install MemberPress, all features automatically unlock
   - No reconfiguration needed when upgrading

## Testing Procedures

The implementation has been thoroughly tested in both environments:

1. **Without MemberPress**:
   - Plugin activates without errors
   - Top-level menu appears correctly
   - AI responds appropriately to MemberPress-specific queries
   - Upsell messages are contextual and helpful

2. **With MemberPress**:
   - Plugin integrates correctly as a MemberPress submenu
   - All MemberPress-specific functionality works as expected
   - No upsell messages are displayed
   - Data retrieval and analysis functions properly