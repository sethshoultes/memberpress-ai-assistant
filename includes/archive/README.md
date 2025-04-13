# Archived MemberPress AI Assistant Components

This directory contains archived components that have been replaced by more streamlined implementations in the main codebase.

## Settings System Evolution

The settings system for MemberPress AI Assistant has evolved from complex custom implementations to a more standard WordPress Settings API approach:

### Current (Active) Implementation

- `includes/settings-page.php` - Current implementation using standard WordPress Settings API
- `includes/class-mpai-settings.php` - Utility class with model lists and helper methods

### Archived (Deprecated) Implementations

These files are kept for reference but are no longer used:

- `class-mpai-settings-registry.php` - A complex settings registry implementation (Phase 1)
- `class-mpai-settings-page.php` - A settings page class that uses the registry (Phase 1)
- `class-mpai-settings-manager.php` - An alternative settings manager implementation (Phase 1)
- `settings-page-v2.php` - An earlier version of the settings page (Phase 1)
- `settings-page-simple.php` - A simplified version of the settings page (Phase 1)
- `settings-page-new.php` - Another iteration of the settings page (Phase 1)

## Migration Notes

In Phase 2 of the Admin UI Overhaul, we transitioned to a standard WordPress Settings API approach for several reasons:

1. **Simplicity and Maintainability**: The standard WordPress Settings API is well-documented and follows established patterns.
2. **Reliability**: The WordPress Settings API has been thoroughly tested across countless plugins.
3. **Compatibility**: Using standard WordPress functions ensures better compatibility with other plugins and themes.

If you need to modify settings functionality, please update the active implementation files rather than reviving these archived approaches.