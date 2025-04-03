# MemberPress AI Assistant Development Guidelines

## Quick Reference
- **System Map**: `/docs/current/core/system-map.md` - Complete system architecture overview
- **Documentation Map**: `/docs/current/core/documentation-map.md` - Visual guide to documentation
- **Key Files**:
  - `memberpress-ai-assistant.php` - Main plugin file
  - `class-mpai-chat.php` - Core chat processing
  - `class-mpai-context-manager.php` - Tool execution
  - `class-mpai-api-router.php` - AI provider management
- **Documentation Structure**:
  - `/docs/current/` - Implemented features organized by system:
    - `/docs/current/core/` - Core system documentation
    - `/docs/current/agent-system/` - Agent system documentation
    - `/docs/current/tool-system/` - Tool system documentation
    - `/docs/current/content-system/` - Content system documentation
    - `/docs/current/js-system/` - JavaScript system documentation
    - `/docs/current/feature-plans/` - Feature planning documentation
  - `/docs/_snacks/` - Investigation results and solutions ("Scooby Snacks")
  - `/docs/roadmap/` - Planned features
  - `/docs/archive/` - Outdated documentation

## Build & Development Commands
- Build JS/CSS: `npm run build` (in individual plugin folders)
- Development: `npm run start` or `npm run dev` (hot reload in some plugins)
- SASS compilation: `npm run sass` and `npm run sass-watch`

## Testing
- Manual testing required following the procedures in `/tests/test-procedures.md`
- Run tests for specific features according to test checklist

## Scooby Snack Protocol
- When given a "Scooby Snack" for a successful solution or implementation:
  1. Create a detailed document following the Scooby Snack template in `/docs/_snacks/`
  2. Place it in the appropriate category folder (e.g., `tool-system`, `content-system`, etc.)
  3. Update the Scooby Snack index at `/docs/_snacks/index.md` with the new entry
  4. Update any existing documentation that relates to the solution
  5. Add an entry to the CHANGELOG.md file if it's a significant fix or feature
  6. Create a git commit with the documentation and implementation changes
  7. Include "🦴 Scooby Snack" in the commit message to track successful solutions
  8. The commit should summarize what worked, why it worked, and any lessons learned

For complete details on the Scooby Snack documentation system, see `/docs/_snacks/README.md`

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
  - `docs/current/` - Current feature documentation organized by system:
    - `core/` - Core system documentation
    - `agent-system/` - Agent system documentation
    - `tool-system/` - Tool system documentation
    - `content-system/` - Content system documentation
    - `js-system/` - JavaScript system documentation
    - `feature-plans/` - Feature planning documentation
  - `docs/_snacks/` - Investigation results and solutions ("Scooby Snacks")
  - `docs/roadmap/` - Planned feature documentation
  - `docs/archive/` - Archived documentation
- WP-CLI commands in `includes/cli/` directory
- Follow MVC-like pattern where appropriate
- **Important**: For complete system architecture, refer to `/docs/current/core/system-map.md`

## Error Handling
- Use `wp_send_json_error()` for AJAX errors
- Use `WP_Error` for REST API errors
- Use `MeprExceptions` when extending core MemberPress
- Proper validation with sanitization for all user input
- Thorough error logging with `error_log()`
- User-friendly error messages for front-end display

## AJAX Handlers
- Always use existing AJAX handlers when available rather than creating new onesagent
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
  - `/docs/current/` - For implemented features, place in appropriate system directory:
    - `core/` - Core system documentation
    - `agent-system/` - Agent system documentation
    - `tool-system/` - Tool system documentation
    - `content-system/` - Content system documentation
    - `js-system/` - JavaScript system documentation
    - `feature-plans/` - Feature planning documentation
  - `/docs/_snacks/` - For investigation results and solutions, place in appropriate category
  - `/docs/roadmap/` - For planned features
  - `/docs/archive/` - For outdated documentation
- When working on existing features, first check the system map at `/docs/current/core/system-map.md`
- Check the documentation map at `/docs/current/core/documentation-map.md` for navigation guidance
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
- When first approaching the codebase, begin by reviewing:
  - [`_0_START_HERE_.md`](/_0_START_HERE_.md) - Primary entry point with development pathways
  - [`/docs/current/core/system-map.md`](/docs/current/core/system-map.md) - Complete system architecture
  - [`/docs/current/core/documentation-map.md`](/docs/current/core/documentation-map.md) - Documentation structure
- For specific systems, refer to the unified documentation:
  - [`/docs/current/agent-system/unified-agent-system.md`](/docs/current/agent-system/unified-agent-system.md) - Agent system
  - [`/docs/current/content-system/unified-xml-content-system.md`](/docs/current/content-system/unified-xml-content-system.md) - Content system
  - [`/docs/current/tool-system/tool-implementation-map.md`](/docs/current/tool-system/tool-implementation-map.md) - Tool system
- Key relationships to understand:
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
  - Create appropriate documentation in the correct system directory

## Development Workflows

### Understanding Data Flow
- **User Message Processing Flow**:
  1. User submits message via chat interface
  2. `process_chat_ajax()` in main plugin file receives the AJAX request
  3. `MPAI_Chat::process_message()` formats and sends request to API
  4. `MPAI_API_Router` directs request to appropriate provider
  5. Response is parsed and tool calls are executed via `MPAI_Context_Manager`
  6. Final response is returned to user

- **Tool Execution Flow**:
  1. AI identifies a tool to use in its response
  2. Chat class extracts tool calls using pattern matching
  3. `Context_Manager` receives tool call and routes to handler method
  4. Tool executes and formats response
  5. Result is incorporated into conversation history

### Common Patterns and Pitfalls
- **Always use existing validation logic** rather than creating new validation methods
- **Check for existing hooks** before adding similar functionality
- **Understand context persistence** between API calls and browser sessions
- **Tool call format variations**: Both AI providers have slightly different formats
- **Avoid tight coupling** with MemberPress core to maintain compatibility across versions
- **Use the logging system consistently** to ensure debugging information is available

### Complex Component Documentation
- **API Router**: Handles both OpenAI and Anthropic formats; see implementation details in `class-mpai-api-router.php`
- **Tool Registry**: Centralizes tool registration; see examples in `class-mpai-tool-registry.php`
- **Agent System**: Uses a modular approach; see architecture in `class-mpai-agent-orchestrator.php`
- **Context Manager**: Central to tool execution; see implementation in `class-mpai-context-manager.php`

## Project-Specific Guidelines

### Security Considerations
- **Always verify permissions** before executing commands or accessing data
- **Never execute arbitrary user code** from AI suggestions without validation
- **Sanitize all inputs** from both users and AI responses
- **Use nonce verification** for all admin actions except designated direct-access endpoints
- **Follow permission-checking patterns** found in existing code
- **Log security-related events** to aid in troubleshooting

### Cross-Plugin Integration
- **MemberPress Core**: Access via `MPAI_MemberPress_API` to maintain abstraction
- **WP-CLI Integration**: Use `MPAI_WPCLI_Tool` for command execution
- **Database Access**: Use direct database queries sparingly and only when necessary

### Testing and Debugging
- When investigating bugs, first enable:
  - System debug mode via `define('WP_DEBUG', true);` in wp-config.php
  - Console logging via Settings > Debug tab
- **Always test features** using the test procedures in `/test/test-procedures.md`
- **Check recent error logs** for relevant issues before implementing fixes
- **Verify both AI providers** (OpenAI and Anthropic) when making chat-related changes
- **Test in different WordPress environments** (admin area, frontend, CLI)

### Feature Implementation Strategy
- First check the system map and documentation map to understand related components
- Update the system map with your planned changes
- Create tests before implementing the feature
- Add tool definitions to the context manager
- Update relevant agent implementations
- Validate with both AI providers
- Document your implementation in the appropriate system directory:
  - `core/` - For core system features
  - `agent-system/` - For agent system features
  - `tool-system/` - For tool system features
  - `content-system/` - For content-related features
  - `js-system/` - For JavaScript features
- If solving a complex issue, create a Scooby Snack in the `_snacks/` directory
- Update main documentation and changelog

## AI Integration Guidelines

### System Prompts and AI Behavior
- System prompts are defined in `MPAI_Chat::get_system_message()`
- **Be specific with instructions** to guide AI behavior
- **Add examples** for complex tools or formatting expectations
- **Update prompts** when adding new tools or capabilities
- **Test prompt changes** with both AI providers

### Tool Definition Best Practices
- **Keep tool names consistent** with their functionality
- **Use clear parameter names** that match internal usage
- **Provide comprehensive descriptions** for the AI to understand usage
- **Include enum values** when parameters have fixed options
- **Mark required parameters** to prevent errors
- **Document expected response format** in the tool description

### Error Handling for AI
- **Provide helpful error messages** that explain what went wrong
- **Include usage examples** when a tool is used incorrectly
- **Implement graceful fallbacks** when primary methods fail
- **Add logging for AI-specific errors** to track issues
- **Format errors consistently** to help the AI understand issues

### Response Processing
- **Handle both JSON and text responses** from AI models
- **Implement robust pattern matching** for tool call extraction
- **Validate tool calls** before execution
- **Format responses consistently** for better AI understanding
- **Use standardized formatters** for tabular data