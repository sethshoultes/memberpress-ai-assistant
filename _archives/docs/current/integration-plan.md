# Integration Plan for Unified Settings Manager

This document outlines the steps to integrate the new unified settings manager into the main plugin while maintaining backward compatibility.

## Step 1: Load the New Files

Add the following to the main plugin's `load_dependencies()` method:

```php
// Load the unified settings manager first (priority is important)
require_once plugin_dir_path(__FILE__) . 'includes/class-mpai-unified-settings-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/unified-settings-page.php';

// Continue loading the rest of the dependencies...
```

## Step 2: Create Migration Functions

Add a migration function to copy settings from the old format to the new format:

```php
/**
 * Migrate settings from old format to new format
 */
function migrate_settings() {
    // List of settings to migrate with their old and new keys
    $settings_to_migrate = [
        'mpai_api_key' => 'mpai_openai_api_key',
        'mpai_model' => 'mpai_openai_model',
        'mpai_temperature' => 'mpai_openai_temperature',
        // Add other settings...
    ];
    
    // Loop through settings and migrate them
    foreach ($settings_to_migrate as $old_key => $new_key) {
        $value = get_option($old_key, null);
        
        if ($value !== null) {
            // Only save if not already set
            if (get_option($new_key, null) === null) {
                update_option($new_key, $value);
            }
            
            // Don't delete old options yet for backward compatibility
        }
    }
}
```

## Step 3: Modify Settings Retrieval Functions

Update any direct `get_option()` calls to use the unified settings manager:

```php
/**
 * Get a setting value
 * 
 * @param string $key     Setting key
 * @param mixed  $default Default value
 * @return mixed Setting value
 */
function mpai_get_setting($key, $default = null) {
    // If the unified settings manager is available, use it
    if (function_exists('mpai_unified_settings_manager')) {
        $value = mpai_unified_settings_manager()->get_setting($key);
        
        // If not found and default is provided, return default
        if ($value === null && $default !== null) {
            return $default;
        }
        
        return $value;
    }
    
    // Fallback to direct option retrieval
    return get_option($key, $default);
}
```

## Step 4: Test Individual Components

Before full integration, test individual components:

1. Test the unified settings manager in isolation
2. Test the unified settings page in isolation
3. Test backward compatibility for old settings keys

## Step 5: Phase Out Old Settings Pages

Mark the old settings files as deprecated:

```php
/**
 * @deprecated 1.7.0 Use unified-settings-page.php instead
 */
```

Keep them functional but redirect to the new settings page.

## Step 6: Gradual Transition Strategy

1. Release 1.7.0 with both systems running in parallel:
   - Keep legacy settings UI functional
   - Add the new settings UI for testing

2. Release 1.7.1 with new system as default:
   - Set the new settings UI as the default
   - Legacy settings only accessible via a URL parameter

3. Release 1.8.0 with complete transition:
   - Remove old settings files completely
   - Complete migration of all settings
   - Clean up legacy settings options in the database

## Notes on Backward Compatibility

- Maintain legacy option names for at least one major version
- Add appropriate deprecation notices in code
- Provide a migration tool or admin notice for users
- Document changes in the changelog

## Potential Challenges

1. **Plugin Customizations**: Users might have customized the existing settings UI
2. **Third-Party Integrations**: Third-party extensions might rely on the existing settings structure
3. **Caching Issues**: Caching might cause unexpected behavior during migration

## Testing Plan

1. **Manual Testing**:
   - Test on fresh WordPress installation
   - Test on existing installation with settings
   - Test with and without MemberPress

2. **Automated Testing**:
   - Unit tests for settings retrieval
   - Integration tests for settings migration
   - End-to-end tests for UI functionality

3. **Compatibility Testing**:
   - Test with various WordPress versions (5.6+)
   - Test with various PHP versions (7.4+)
   - Test with various MemberPress versions (if applicable)

4. **Edge Cases**:
   - Test with missing or corrupted settings
   - Test with extremely large settings values
   - Test with special characters in settings