# MemberPress AI Assistant Detection System

## Overview

The Detection System consolidates and standardizes how the plugin detects whether MemberPress is active. It provides a centralized, cacheable, and consistent approach to MemberPress detection across all plugin components.

## Components

### Core Classes

- **MPAI_MemberPress_Detector** - Singleton class that manages all detection logic
- **Global Helper Functions** - For easy access to detection capabilities:
  - `mpai_memberpress_detector()` - Get detector instance
  - `mpai_is_memberpress_active()` - Check if MemberPress is active
  - `mpai_get_memberpress_version()` - Get MemberPress version

## Detection Methods

The system uses multiple detection methods in the following order:

1. **Class Existence** - Checks if core MemberPress classes exist:
   - MeprAppCtrl, MeprOptions, MeprUser, MeprProduct, MeprTransaction, MeprSubscription

2. **Constants** - Checks if core MemberPress constants are defined:
   - MEPR_VERSION, MEPR_PLUGIN_NAME, MEPR_PATH, MEPR_URL

3. **Plugin Active** - Uses `is_plugin_active('memberpress/memberpress.php')`

4. **API Classes** - Checks for MemberPress API classes:
   - MeprApi, MeprRestApi

5. **Admin Menu** - Checks if the 'memberpress' admin menu exists

## Performance Features

### Caching

Detection results are cached using the WordPress transients API:

- **Cache Duration**: 1 hour by default
- **Automatic Invalidation**: On plugin activation/deactivation
- **Manual Invalidation**: Via `clear_detection_cache()` method

### Forced Detection

In certain contexts, MemberPress detection can be forced to return `true`:

- **Settings Pages**: For proper menu highlighting
- **Custom Contexts**: Via the `mpai_force_memberpress_detection` filter

## Usage Examples

### Basic Usage

```php
// Check if MemberPress is active
if (mpai_is_memberpress_active()) {
    // MemberPress-specific code
} else {
    // Fallback code
}

// Get MemberPress version
$version = mpai_get_memberpress_version();
if ($version && version_compare($version, '1.9.0', '>=')) {
    // Code for MemberPress 1.9.0 and above
}
```

### Advanced Usage

```php
// Force re-detection (bypass cache)
$is_active = mpai_is_memberpress_active(true);

// Get full detection info
$detector = mpai_memberpress_detector();
$info = $detector->get_detection_info();

// Clear the detection cache
$detector->clear_detection_cache();
```

### Adding Custom Force Contexts

```php
/**
 * Force MemberPress detection on a specific admin page
 */
add_filter('mpai_force_memberpress_detection', function($forces, $has_memberpress) {
    global $pagenow;
    
    // Force detection on the widgets admin page
    if ($pagenow === 'widgets.php') {
        $forces[] = 'widgets_page';
    }
    
    return $forces;
}, 10, 2);
```

## Integration Points

### Main Plugin

The main plugin class uses the detector to check MemberPress status:

```php
public function check_memberpress() {
    // Use the centralized MemberPress detection system
    $has_memberpress = mpai_is_memberpress_active();
    
    // Store the result
    $this->has_memberpress = $has_memberpress;
    
    // Display upsell notice if needed
    if (!$this->has_memberpress) {
        add_action('admin_notices', array($this, 'memberpress_upsell_notice'));
    }
}
```

### Admin Menu

The admin menu class uses the detector to determine menu placement:

```php
private function detect_memberpress() {
    // Use the centralized MemberPress detection system
    return mpai_is_memberpress_active();
}
```

### MemberPress API

The API integration class uses the detector to check feature availability:

```php
public function __construct() {
    $this->api_key = get_option('mpai_memberpress_api_key', '');
    $this->base_url = site_url('/wp-json/mp/v1/');
    $this->has_memberpress = mpai_is_memberpress_active();
}
```

## Logging Integration

The detection system integrates with the unified logging system:

```php
// Detection results are logged for diagnostics
mpai_log_debug('MemberPress detection: ' . $message, [
    'method' => $this->detection_method,
    'has_memberpress' => $this->has_memberpress,
    'version' => $this->mepr_version
]);
```

## Error Handling

The system includes robust error handling to prevent failures:

- **Fallback Logic**: If one detection method fails, falls back to others
- **Exception Handling**: All exceptions are caught and logged
- **Default Values**: Safe defaults if all detection methods fail

## Compatibility

The detection system is compatible with various MemberPress installation methods:

- Standard WordPress plugin installation
- Manual installation
- Network/multisite installations