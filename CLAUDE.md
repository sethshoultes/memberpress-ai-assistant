# MemberPress AI Assistant Development Guidelines

## Build & Development Commands
- Build JS/CSS: `npm run build` (in individual plugin folders)
- Development: `npm run start` or `npm run dev` (hot reload in some plugins)
- SASS compilation: `npm run sass` and `npm run sass-watch`

## Testing
- Manual testing required following the procedures in `/tests/test-procedures.md`
- Run tests for specific features according to test checklist

## Scooby Snack Protocol
- When given a "Scooby Snack" for a successful solution or implementation:
  1. Create a detailed document of the findings/solution in an appropriate location (usually in `/docs/`)
  2. Update any existing documentation that relates to the solution
  3. Add an entry to the CHANGELOG.md file if it's a significant fix or feature
  4. Create a git commit with the documentation and implementation changes
  5. Include "🦴 Scooby Snack" in the commit message to track successful solutions
  6. The commit should summarize what worked, why it worked, and any lessons learned

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
- Documentation in `docs/` directory:
  - `docs/current/` - Current feature documentation
  - `docs/roadmap/` - Planned feature documentation
  - `docs/archive/` - Archived documentation
- WP-CLI commands in `includes/cli/` directory
- Follow MVC-like pattern where appropriate
- **Important**: For complete system architecture, refer to `/docs/current/system-map.md`

## Error Handling
- Use `wp_send_json_error()` for AJAX errors
- Use `WP_Error` for REST API errors
- Use `MeprExceptions` when extending core MemberPress
- Proper validation with sanitization for all user input
- Thorough error logging with `error_log()`
- User-friendly error messages for front-end display

## AJAX Handlers
- Always use existing AJAX handlers when available rather than creating new ones
- Leverage the plugin's established AJAX endpoints for communication
- Reference how existing functionality works rather than creating parallel implementations
- Maintain consistent parameter naming between frontend and backend
- For AI tool calls that need to bypass nonce checks, use the direct-ajax-handler.php endpoint
- Permission issues in admin-ajax handlers can be solved by using direct-ajax-handler.php for AI tools

## Documentation and Change Management
- Always update `CHANGELOG.md` after implementing new features or fixing bugs
- Follow the established changelog format:
  - Categorize changes as "Added", "Changed", "Fixed", or "Removed"
  - Use bullet points for each significant change
  - Group related changes with sub-bullet points
- Create or update documentation in the proper directory:
  - `/docs/current/` - For implemented features
  - `/docs/roadmap/` - For planned features
  - `/docs/archive/` - For outdated documentation
- When working on existing features, first check the system map at `/docs/current/system-map.md`
- Reference documentation in code comments for complex functionality
- Update main `docs/README.md` when adding new documentation files
- When adding new PHP files, update the system map to include them

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

## System Architecture Exploration
- When first approaching the codebase, begin by reviewing `/docs/current/system-map.md`
- For AI-specific components, understand these key relationships:
  - `memberpress-ai-assistant.php` initializes the plugin
  - `class-mpai-chat.php` processes user messages and AI responses
  - `class-mpai-api-router.php` routes requests between different AI providers
  - `class-mpai-context-manager.php` manages tool execution and context
  - Agent classes provide specialized functionality for different domains
- Debug issues by examining error logs with the correct prefixes:
  - PHP errors use the `MPAI:` prefix in error logs
  - JavaScript issues log to console via `mpaiLogger`
- When adding features, follow the established architecture:
  - Add new agent classes to `/includes/agents/specialized/`
  - Add new tools to `/includes/tools/implementations/`
  - Register tools in the context manager
  - Update system prompt to include new capabilities