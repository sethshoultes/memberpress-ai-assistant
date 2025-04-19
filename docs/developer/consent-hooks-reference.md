# Consent Mechanism Hooks and Filters Reference

This document outlines the hooks and filters available for extending and customizing the consent mechanism in the MemberPress AI Assistant plugin.

## Hooks

### MPAI_HOOK_ACTION_before_save_consent

Fires before saving user consent.

**Parameters:**
- `$user_id` (int): The ID of the user whose consent is being saved
- `$consent_value` (bool): The consent value being saved (true for consent given, false for consent revoked)

**Example Usage:**
```php
add_action('MPAI_HOOK_ACTION_before_save_consent', function($user_id, $consent_value) {
    // Log consent action to custom logging system
    my_custom_log('User ' . $user_id . ' consent status changing to: ' . ($consent_value ? 'granted' : 'revoked'));
    
    // Perform additional validation or checks
    if ($consent_value && !current_user_can('manage_options')) {
        // Maybe add additional requirements for non-admin users
    }
}, 10, 2);
```

### MPAI_HOOK_ACTION_after_save_consent

Fires after saving user consent.

**Parameters:**
- `$user_id` (int): The ID of the user whose consent was saved
- `$consent_value` (bool): The consent value that was saved

**Example Usage:**
```php
add_action('MPAI_HOOK_ACTION_after_save_consent', function($user_id, $consent_value) {
    // Notify external systems about consent change
    if ($consent_value) {
        // User has given consent
        my_external_api_client->notify_consent_given($user_id);
    } else {
        // User has revoked consent
        my_external_api_client->notify_consent_revoked($user_id);
    }
    
    // Update user capabilities based on consent
    $user = get_user_by('id', $user_id);
    if ($user && $consent_value) {
        $user->add_cap('use_ai_assistant');
    } else if ($user && !$consent_value) {
        $user->remove_cap('use_ai_assistant');
    }
}, 10, 2);
```

## Filters

### MPAI_HOOK_FILTER_consent_form_template

Filter the consent form template path.

**Parameters:**
- `$template_path` (string): The path to the consent form template file

**Example Usage:**
```php
add_filter('MPAI_HOOK_FILTER_consent_form_template', function($template_path) {
    // Use a custom template for specific user roles
    if (current_user_can('administrator')) {
        return plugin_dir_path(__FILE__) . 'templates/admin-consent-form.php';
    }
    
    // Use default template for other users
    return $template_path;
});
```

### MPAI_HOOK_FILTER_consent_redirect_url

Filter the URL to redirect to after consent is given.

**Parameters:**
- `$redirect_url` (string): The URL to redirect to
- `$user_id` (int): The ID of the user who gave consent

**Example Usage:**
```php
add_filter('MPAI_HOOK_FILTER_consent_redirect_url', function($redirect_url, $user_id) {
    // Redirect to a different page based on user role
    $user = get_user_by('id', $user_id);
    if ($user && in_array('editor', $user->roles)) {
        return admin_url('admin.php?page=memberpress-ai-assistant-editor-dashboard');
    }
    
    // Add additional parameters to the redirect URL
    return add_query_arg('welcome', 'true', $redirect_url);
}, 10, 2);
```

## Integration with Other Components

The consent mechanism integrates with other components of the MemberPress AI Assistant plugin:

1. **Chat Interface**: The chat interface checks if the user has consented before loading.
2. **Admin Dashboard**: The admin dashboard displays the consent form if the user hasn't consented.
3. **Plugin Deactivation**: All user consents are reset when the plugin is deactivated.

## Best Practices

When extending the consent mechanism, follow these best practices:

1. **Respect User Privacy**: Always respect the user's consent decision. Don't enable AI features for users who haven't consented.
2. **Maintain Consistency**: Ensure that your extensions maintain a consistent user experience with the core plugin.
3. **Performance Considerations**: Avoid heavy processing in consent hooks, especially in the admin interface.
4. **Security**: Validate and sanitize any data you process in your hook callbacks.

## Example: Custom Consent Validation

Here's an example of how to add custom validation to the consent process:

```php
// Add custom validation before saving consent
add_action('MPAI_HOOK_ACTION_before_save_consent', function($user_id, $consent_value) {
    // Only allow consent during business hours
    $current_hour = (int) current_time('G');
    if ($consent_value && ($current_hour < 9 || $current_hour > 17)) {
        // Prevent consent outside business hours
        wp_die('Consent can only be given during business hours (9 AM - 5 PM).');
    }
}, 10, 2);

// Customize the redirect URL after consent
add_filter('MPAI_HOOK_FILTER_consent_redirect_url', function($redirect_url, $user_id) {
    // Add a welcome parameter for first-time users
    $has_used_before = get_user_meta($user_id, 'mpai_has_used_before', true);
    if (!$has_used_before) {
        update_user_meta($user_id, 'mpai_has_used_before', true);
        return add_query_arg('first_time', 'true', $redirect_url);
    }
    return $redirect_url;
}, 10, 2);
```

## Troubleshooting

If you encounter issues with the consent mechanism, check the following:

1. **User Meta**: Ensure that the `mpai_has_consented` user meta is being properly set and retrieved.
2. **Hook Priority**: Check the priority of your hook callbacks if you have multiple extensions modifying the consent process.
3. **Redirect Issues**: If you're experiencing redirect loops, ensure that your `MPAI_HOOK_FILTER_consent_redirect_url` filter doesn't create circular redirects.
4. **JavaScript Errors**: Check the browser console for JavaScript errors that might affect the consent form functionality.

For more information on extending the MemberPress AI Assistant plugin, refer to the [Hooks and Filters Implementation Plan](./hooks-filters-implementation-plan.md).