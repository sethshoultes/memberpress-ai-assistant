# Unused Files in MemberPress AI Assistant

This document lists files that are no longer used in the MemberPress AI Assistant plugin and can be safely removed as part of the code cleanup process.

## JavaScript Files

1. **assets/js/diagnostics.js**
   - This file appears to be related to a diagnostics system that is no longer in use.
   - References to this file are only found in legacy documentation and the CHANGELOG.md.
   - The corresponding PHP file `class-mpai-diagnostics-page.php` is not present in the includes directory.
   - The file is not loaded by any active part of the plugin.

## CSS Files

1. **assets/css/diagnostics.css**
   - This file is referenced in legacy documentation but doesn't exist in the assets/css directory.
   - It was likely part of the diagnostics system that is no longer in use.
   - References to this file are only found in legacy documentation.

## PHP Files

1. **class-mpai-diagnostics-page.php**
   - This file is referenced in legacy documentation but doesn't exist in the includes directory.
   - It was likely part of the diagnostics system that is no longer in use.
   - No references to this file are found in any active PHP files.

## WP-CLI Tool Standardization

The codebase currently has references to multiple WP-CLI tool IDs:
1. `wpcli` - This is the standardized and currently supported tool ID
2. `wpcli_new` - This is a legacy tool ID that is no longer supported
3. `wp_cli` - This is another legacy tool ID that is no longer supported

The system already has code in place to handle the legacy tool IDs and redirect users to use the standardized 'wpcli' tool ID. For example:

```php
// Legacy tool IDs have been removed - only 'wpcli' is supported
if (isset($tool_request['name']) && ($tool_request['name'] === 'wpcli_new' || $tool_request['name'] === 'wp_cli')) {
    mpai_log_error('Legacy tool ID "' . $tool_request['name'] . '" is no longer supported. Use "wpcli" instead.', 'ajax-handler');
    wp_send_json_error('Legacy tool ID "' . $tool_request['name'] . '" is no longer supported. Use "wpcli" instead.');
    return;
}
```

This approach ensures that only the standardized 'wpcli' tool ID is supported, while providing clear error messages for any code that might still be using the legacy tool IDs.

## Next Steps

1. Remove the unused files listed above.
2. Update any documentation that references these files to avoid confusion.
3. Continue the audit to identify other unused files.
4. Ensure that all code examples and documentation consistently use the standardized 'wpcli' tool ID.