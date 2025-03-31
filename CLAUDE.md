# MemberPress AI Assistant Development Guidelines

## Build & Development Commands
- Build JS/CSS: `npm run build` (in individual plugin folders)
- Development: `npm run start` or `npm run dev` (hot reload in some plugins)
- SASS compilation: `npm run sass` and `npm run sass-watch`

## Testing
- Manual testing required following the procedures in `/tests/test-procedures.md`
- Run tests for specific features according to test checklist

## Code Style
- Follow WordPress PHP Coding Standards
- Class naming: 
  - `MPAI_PascalCase` for AI Assistant classes
  - `MeprPascalCase` for MemberPress core classes
- File naming: matches class names with `class-mpai-*.php` format
- Method naming: `camelCase`
- Hooks: snake_case with plugin prefix (`mpai_*` or `mepr_*`)
- Proper escaping required for all output (esc_html, esc_attr, etc.)
- Nonce verification for all form submissions

## Structure
- Main plugin file: `memberpress-ai-assistant.php`
- Class files in `includes/` directory
- Assets (JS/CSS) in `assets/` directory
- Documentation in `docs/` directory
- WP-CLI commands in `includes/cli/` directory
- Follow MVC-like pattern where appropriate

## Error Handling
- Use `wp_send_json_error()` for AJAX errors
- Use `WP_Error` for REST API errors
- Use `MeprExceptions` when extending core MemberPress
- Proper validation with sanitization for all user input
- Thorough error logging with `error_log()`
- User-friendly error messages for front-end display

## Documentation and Change Management
- Always update `CHANGELOG.md` after implementing new features or fixing bugs
- Follow the established changelog format:
  - Categorize changes as "Added", "Changed", "Fixed", or "Removed"
  - Use bullet points for each significant change
  - Group related changes with sub-bullet points
- Create or update documentation in the `docs/` directory whenever:
  - Implementing new features
  - Making architectural changes
  - Adding new configuration options
  - Changing existing behavior
- Reference documentation in code comments for complex functionality
- Update main `docs/README.md` when adding new documentation files

## Console Logging System
- Use the `mpaiLogger` object for all browser console logging
- Available methods:
  - `window.mpaiLogger.error()` - for critical errors
  - `window.mpaiLogger.warn()` - for warnings
  - `window.mpaiLogger.info()` - for general information
  - `window.mpaiLogger.debug()` - for detailed debugging
- Include category parameter:
  - `'api_calls'` - for API interactions
  - `'tool_usage'` - for tool execution
  - `'agent_activity'` - for agent operations
  - `'timing'` - for performance metrics
- Use timing functions for performance tracking:
  - `window.mpaiLogger.startTimer('operation_name')`
  - `window.mpaiLogger.endTimer('operation_name')`
- Check `window.mpaiLogger` exists before using it:
  ```javascript
  if (window.mpaiLogger) {
      window.mpaiLogger.info('Operation completed', 'category');
  }
  ```