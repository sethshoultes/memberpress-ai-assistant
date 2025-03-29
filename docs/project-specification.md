# MemberPress AI Assistant Project Specification

## Project Overview

MemberPress AI Assistant is a WordPress plugin that integrates OpenAI's powerful language models with the MemberPress membership plugin. It provides intelligent assistance for site administrators through a chat interface, command-line tools, and an advanced agent system.

## Core Components

1. **Chat Interface**: AI-powered chat for answering questions about MemberPress data
2. **Agent System**: Specialized AI agents that perform specific tasks through natural language commands
3. **OpenAI Integration**: Connection to GPT-4 and newer models for natural language processing
4. **MemberPress API**: Deep integration with MemberPress data and functionality
5. **WP-CLI Integration**: Command execution and analysis with AI assistance

## Agent System Architecture

The Agent System consists of the following components:

1. **Agent Orchestrator**: Central component that processes requests and routes to appropriate agents
2. **Specialized Agents**:
   - Content Agent: Creates and manages content
   - System Agent: Handles WordPress system maintenance
   - Security Agent: Performs security audits and fixes
   - Analytics Agent: Analyzes membership site performance
   - MemberPress Agent: Manages membership-specific functionality

3. **Tool Integration Layer**: Provides agents with access to WordPress functionality
4. **Memory System**: Maintains context across conversations

## Database Structure

The plugin uses several database tables:
- `mpai_conversations`: Stores conversation sessions
- `mpai_messages`: Stores individual messages
- `mpai_agent_tasks`: Tracks agent task execution
- `mpai_user_preferences`: Stores user-specific settings

## Technical Requirements

- PHP 7.4+ (8.0+ recommended)
- WordPress 5.8+
- MemberPress 1.9.0+
- OpenAI API access (GPT-4 or better)
- WP-CLI installed for command execution

## Implementation Plan

### Phase 1: Foundation (Weeks 1-4)
- Core OpenAI integration
- Basic chat interface
- MemberPress data retrieval
- Initial settings page

### Phase 2: Agent System (Weeks 5-8)
- Agent orchestrator framework
- Content and System agents
- Tool integration layer
- Memory management

### Phase 3: Advanced Features (Weeks 9-12)
- Security, Analytics, and MemberPress agents
- Background task processing
- Agent dashboard UI
- Cross-agent workflows

### Phase 4: Refinement (Weeks 13-16)
- Performance optimization
- Security enhancements
- User experience improvements
- Documentation completion

## Key Files and Organization

- `memberpress-ai-assistant.php`: Main plugin file
- `includes/`: Core plugin classes
  - `agents/`: Agent system files
  - `tools/`: Tool implementations
  - `memory/`: Context management
  - `api/`: REST API endpoints
- `admin/`: Admin interface components
- `assets/`: JavaScript and CSS files
- `docs/`: Documentation files

## REST API Endpoints

The plugin provides several REST API endpoints:

- `/wp-json/mpai/v1/agent/process`: Process a request through the agent system
- `/wp-json/mpai/v1/agent/status`: Check status of long-running agent tasks
- `/wp-json/mpai/v1/agent/capabilities`: List available agent capabilities
- `/wp-json/mpai/v1/agent/history`: Retrieve history of agent actions

## Security Considerations

1. **Command Validation**: All WP-CLI commands validated against an allowlist
2. **Permission Controls**: User capability checks before agent actions
3. **Content Protection**: Sanitization of all generated content
4. **Rate Limiting**: Prevent abuse with request limits

## Development Guidelines

- Follow WordPress PHP Coding Standards
- Class naming: 
  - `MPAI_PascalCase` for AI Assistant classes
  - `MeprPascalCase` for MemberPress core classes
- Method naming: `camelCase`
- Hook naming: snake_case with plugin prefix (`mpai_*`)
- Thorough error logging and user-friendly messages
- All output properly escaped

## Resources and Documentation

For detailed information, refer to the following documentation:
- `docs/user-guide.md`: End-user instructions
- `docs/developer-guide.md`: Developer extension guide
- `docs/agent-system-spec.md`: Technical specification of the agent system
- `docs/agent-system-implementation.md`: Implementation details
- `tests/test-procedures.md`: Testing procedures and checklist

## Future Expansion

- E-commerce integration for advanced product management
- Multi-site management for WordPress networks
- Advanced SEO optimization tools
- Membership campaign creation wizards
- Integration with additional AI providers