# MemberPress AI Assistant - Development Tools

This directory contains development and diagnostic tools for the MemberPress AI Assistant plugin. These tools are designed to help developers debug issues, monitor performance, and troubleshoot problems during development and support.

## 📁 Contents

### `debug.php` - Development Debug Enabler
A lightweight debug mode enabler that integrates with the plugin's debug system.

**Purpose:**
- Enables debug mode via WordPress filter system
- Provides memory usage tracking
- Shows admin notices when debug mode is active
- Sets appropriate WordPress debug constants

**Usage:**
1. Include in your `wp-config.php` file:
   ```php
   require_once(ABSPATH . 'wp-content/plugins/memberpress-ai-assistant/dev-tools/debug.php');
   ```

2. Or manually enable debug mode in your theme/plugin:
   ```php
   add_filter('mpai_debug_mode', '__return_true');
   ```

**Features:**
- ✅ Memory usage monitoring
- ✅ Admin dashboard notices
- ✅ WordPress debug constant management
- ✅ Integration with plugin logging system

### `diagnostics.php` - System Diagnostics Tool
A comprehensive diagnostic tool that provides detailed system information and analysis.

**Purpose:**
- System health monitoring
- Memory usage analysis
- Plugin class inspection
- Dependency analysis
- Performance troubleshooting

**Usage:**
1. The diagnostics page is automatically available in the WordPress admin under:
   `MemberPress > AI Diagnostics`

2. Access requires `manage_options` capability (Administrator role)

**Features:**
- ✅ Memory and PHP version information
- ✅ WordPress and theme details
- ✅ Active plugins listing
- ✅ Plugin class analysis
- ✅ Dependency analysis recommendations
- ✅ AJAX endpoints for real-time monitoring

## 🚀 Quick Start

### For Development
1. Copy `debug.php` path to your `wp-config.php`
2. Visit your WordPress admin to see debug notices
3. Check debug logs for detailed information

### For Troubleshooting
1. Navigate to `MemberPress > AI Diagnostics` in WordPress admin
2. Review system information and recommendations
3. Use the diagnostic data to identify issues

## 🔧 Technical Details

### Debug Mode Integration
The debug system uses WordPress filters for clean integration:
- Filter: `mpai_debug_mode`
- Main integration point: `memberpress-ai-assistant.php:213`
- Logging utility: `\MemberpressAiAssistant\Utilities\LoggingUtility`

### Memory Tracking
Both tools provide memory usage information:
- Current memory usage
- Peak memory usage
- Memory limit information
- Available memory percentage

### Admin Integration
- Debug notices appear only on plugin-related admin pages
- Diagnostics page integrates with MemberPress admin menu
- AJAX endpoint: `wp_ajax_mpai_get_memory_usage`

## 📋 Troubleshooting

### Common Issues

**Debug mode not working:**
1. Verify the file path in `wp-config.php` is correct
2. Check file permissions
3. Ensure WordPress debug constants are properly set

**Diagnostics page not accessible:**
1. Verify user has `manage_options` capability
2. Check if MemberPress plugin is active
3. Clear any caching plugins

**Memory issues:**
1. Review memory limit settings
2. Check for circular dependencies
3. Use diagnostics recommendations

## 🔒 Security Notes

- These tools should only be used in development/staging environments
- Remove or disable before production deployment
- Admin access is required for diagnostics features
- Memory information may contain sensitive system details

## 📝 Development Notes

### File History
- Originally located in plugin root directory
- Moved to `dev-tools/` directory for better organization
- Part of Phase 3 cleanup (June 2025)

### Integration Points
- Main plugin file: `memberpress-ai-assistant.php`
- Logging system: `src/Utilities/LoggingUtility.php`
- Admin menu integration via WordPress hooks

---

**Last Updated:** June 10, 2025  
**Compatibility:** WordPress 5.0+, PHP 7.4+  
**Plugin Version:** Compatible with all versions