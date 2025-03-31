# Command Validation Agent

## Overview

The Command Validation Agent is a specialized AI assistant component that pre-processes commands before execution to ensure they are properly formatted and valid. This validation system improves the reliability and user experience by catching and correcting common errors before commands are executed.

## Key Features

- **Plugin Validation**: Validates plugin paths and names for activation/deactivation commands
- **Theme Validation**: Validates theme stylesheets for theme-related operations
- **Block Validation**: Validates block names and namespaces for block-related commands
- **Pattern Validation**: Validates block pattern names and references
- **Fuzzy Matching**: Intelligently matches partial or informal names to correct system paths
- **Permissive Validation**: Logs issues but allows operations to continue for maximum reliability
- **Caching**: Stores lookup results for improved performance
- **Error Resilience**: Comprehensive try/catch blocks to prevent fatal errors

## Implementation

The Command Validation Agent is implemented as a specialized agent class that extends the base agent functionality:

```php
class MPAI_Command_Validation_Agent extends MPAI_Base_Agent {
    // Implementation details...
}
```

### Validation Process

1. Command requests are intercepted by the context manager
2. The validation agent analyzes the command for validity and correctness
3. If the command can be improved, a corrected version is returned
4. The context manager uses the validated command for execution
5. Even if validation fails, the operation is allowed to proceed (permissive validation)
6. MemberPress-specific tool calls (memberpress_info) bypass validation completely

### Supported Command Types

- **WP-CLI Commands**: `wp plugin activate`, `wp theme activate`, `wp block unregister`, etc.
- **WordPress API Actions**: `activate_plugin`, `deactivate_plugin`, etc.
- **Tool Calls**: Commands issued through the tool calling interface

## Technical Implementation

### Plugin Validation

The agent maintains a registry of available plugins using `get_plugins()` and implements sophisticated path matching:

```php
private function find_plugin_path($plugin_slug, $available_plugins) {
    // Direct match check
    if (isset($available_plugins[$plugin_slug])) {
        return $plugin_slug;
    }
    
    // Partial path match (correct folder, wrong file)
    // Name-based matching
    // Fuzzy matching for partial names
    // ...
}
```

### Theme Validation

Similarly, theme validation uses `wp_get_themes()` to obtain available themes and match them against user queries:

```php
private function find_theme_stylesheet($theme_slug, $available_themes) {
    // Direct stylesheet match
    if (isset($available_themes[$theme_slug])) {
        return $theme_slug;
    }
    
    // Name-based matching
    // Partial name matching
    // ...
}
```

### Block and Pattern Validation

For blocks and patterns, the agent uses the WordPress registries:

```php
private function get_available_blocks() {
    if (class_exists('WP_Block_Type_Registry')) {
        $registry = WP_Block_Type_Registry::get_instance();
        return $registry->get_all_registered();
    }
    return [];
}

private function get_available_patterns() {
    if (class_exists('WP_Block_Patterns_Registry')) {
        $registry = WP_Block_Patterns_Registry::get_instance();
        return $registry->get_all_registered();
    }
    return [];
}
```

## Integration

The Command Validation Agent is integrated into the context manager's command processing pipeline:

```php
private function validate_command($request) {
    // Validate the command using the Command Validation Agent
    // ...
    
    // Use the validated command for further processing
    if ($validation_result['success']) {
        $request = $validation_result['validated_command'];
    }
}
```

## Testing

A dedicated test script is available to verify the validation functionality:

```
/test-validate-theme-block.php
```

This script tests various scenarios for theme and block validation to ensure the agent works correctly.

## Error Handling

The validation agent implements comprehensive error handling to ensure that validation failures never block valid operations:

1. **Try/Catch Blocks**: All validation methods are wrapped in try/catch
2. **Permissive Validation**: Even failed validations return success to allow operations to continue
3. **Detailed Logging**: All validation steps are logged for debugging
4. **Graceful Degradation**: Fallbacks for when services are unavailable
5. **Validation Bypassing**: Certain tools and operations bypass validation completely for maximum reliability:
   - memberpress_info tool calls
   - wp_api post actions (create_post, update_post, etc.)
   - wp post list commands
   - wp theme list and wp block list commands
6. **Command Type Detection**: Commands that perform listing operations typically bypass validation

## Future Enhancements

- Add validation for custom post types
- Implement validation for taxonomies
- Add validation for REST API endpoints
- Improve fuzzy matching with Levenshtein distance
- Add context-aware suggestions for invalid commands