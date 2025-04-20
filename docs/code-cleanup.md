# Code Cleanup Recommendations

This document identifies legacy code, duplicated code, and unused code that should be removed from the MemberPress AI Assistant plugin. It also provides recommendations for improving code quality and maintainability.

## Legacy Tool IDs

The plugin currently has multiple tool IDs for the WP-CLI functionality, which causes confusion and potential bugs:

- `wpcli` (current standard)
- `wpcli_new` (legacy)
- `wp_cli` (legacy)

### Recommendation

Remove support for the legacy tool IDs `wpcli_new` and `wp_cli`. Update the following files:

1. `includes/agents/specialized/class-mpai-command-validation-agent.php` - Remove code that handles legacy tool IDs
2. `includes/class-mpai-ajax-handler.php` - Update to reject requests using legacy tool IDs instead of converting them
3. `docs/developer/wpcli-tool-implementation.md` - Update documentation to clarify that only `wpcli` is supported

## Duplicated Code

### Tool Call Detection

There are multiple implementations of tool call detection in the codebase:

1. JavaScript implementation in `assets/js/modules/mpai-chat-tools.js`
2. PHP implementation in `includes/detection/class-mpai-tool-call-detector.php`

While some duplication is necessary due to the different environments (client-side vs. server-side), there's unnecessary duplication in the logic.

### Recommendation

Refactor the tool call detection code to share common logic and patterns. Create a unified approach to tool call detection that works consistently across JavaScript and PHP.

### Agent Specialization Scoring

There are multiple implementations of agent specialization scoring:

1. `includes/agents/class-mpai-agent-orchestrator.php`
2. `includes/agents/specialized/class-mpai-memberpress-agent.php`
3. `includes/agents/specialized/class-mpai-command-validation-agent.php`

### Recommendation

Create a unified approach to agent specialization scoring that can be reused across different agents. Move the scoring logic to a separate class that can be used by all agents.

## Unused Code

### Test Files in Production

There are test files in the production codebase that should be moved to the `tests` directory:

1. `includes/cli/test-wpcli-command.php`
2. `includes/tools/test-tool-execution.php`

### Recommendation

Move test files to the `tests` directory or remove them if they're no longer needed.

### Legacy JavaScript Files

There are JavaScript files that may no longer be used:

1. Check for unused JavaScript files in `assets/js`
2. Check for unused CSS files in `assets/css`

### Recommendation

Audit the JavaScript and CSS files to identify unused files and remove them.

## Inconsistent Naming Conventions

The codebase uses inconsistent naming conventions for classes, methods, and variables:

1. Some files use camelCase for method names, while others use snake_case
2. Some classes use `MPAI_` prefix, while others don't
3. Some files use different naming conventions for similar concepts

### Recommendation

Standardize naming conventions across the codebase:

1. Use `MPAI_` prefix for all classes
2. Use camelCase for method names (WordPress standard)
3. Use snake_case for variable names (WordPress standard)
4. Use consistent naming for similar concepts

## Incomplete Error Handling

Some parts of the codebase have incomplete error handling:

1. Some methods don't check for error conditions
2. Some methods don't return appropriate error messages
3. Some methods don't log errors

### Recommendation

Improve error handling across the codebase:

1. Add appropriate error checks
2. Return meaningful error messages
3. Log errors for debugging

## Lack of Documentation

Some parts of the codebase lack proper documentation:

1. Some classes and methods don't have PHPDoc comments
2. Some complex logic isn't explained
3. Some configuration options aren't documented

### Recommendation

Improve documentation across the codebase:

1. Add PHPDoc comments to all classes and methods
2. Explain complex logic with inline comments
3. Document all configuration options

## Implementation Plan

1. Create a new branch for code cleanup
2. Remove legacy tool IDs
3. Refactor duplicated code
4. Remove unused code
5. Standardize naming conventions
6. Improve error handling
7. Improve documentation
8. Write tests for the refactored code
9. Submit a pull request for review

## Testing Plan

1. Write unit tests for the refactored code
2. Test the plugin with different WordPress versions
3. Test the plugin with different PHP versions
4. Test the plugin with different browsers
5. Test the plugin with different MemberPress versions
6. Test the plugin with different API providers (OpenAI and Anthropic)
7. Test the plugin with different user roles and permissions