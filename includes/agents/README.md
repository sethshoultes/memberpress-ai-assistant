# MemberPress AI Assistant Agent System

This directory contains the agent system components for the MemberPress AI Assistant. The agent system provides a modular, extensible architecture for AI-powered functionality in MemberPress.

## Architecture Overview

The agent system follows a hierarchical architecture:

```
Agent Orchestrator
    ├── Base Agent (abstract)
    │   ├── MemberPress Agent
    │   ├── Command Validation Agent
    │   └── [Other Specialized Agents]
    │
    ├── Tool Registry
    │   ├── WP-CLI Tool
    │   ├── WordPress API Tool
    │   ├── MemberPress Tool
    │   └── [Other Tools]
    │
    └── SDK Integration
        └── OpenAI Assistant API
```

## Core Components

### 1. Agent Orchestrator (`class-mpai-agent-orchestrator.php`)

The Agent Orchestrator serves as the central coordination point for all AI agents. It:

- Routes user requests to appropriate specialized agents
- Manages agent registration and discovery
- Coordinates tool execution across agents
- Maintains conversation context and history
- Handles transitions between different agents
- Provides SDK integration for advanced capabilities

Key methods:
- `process_request()` - Main entry point for user messages
- `register_agent()` - Register a new specialized agent
- `handle_handoff()` - Manage transitions between agents
- `run_agent()` - Execute a specific agent with parameters

### 2. Base Agent (`class-mpai-base-agent.php`) 

An abstract class that implements the `MPAI_Agent` interface and provides common functionality for all agents:

- Tool execution and error handling
- Response generation and formatting
- OpenAI integration for AI-powered summaries
- Consistent logging across all agent types

### 3. Agent Interface (`interfaces/interface-mpai-agent.php`)

Defines the contract that all agents must implement:

- `process_request()` - Process a user request and return a response
- `get_name()` - Return the agent's display name
- `get_description()` - Return the agent's description
- `get_capabilities()` - Return the agent's capabilities

## Specialized Agents

### 1. MemberPress Agent (`specialized/class-mpai-memberpress-agent.php`)

Handles MemberPress-specific operations:

- Membership management (create, list, update)
- Transaction processing and reporting
- Subscription management
- User membership verification and control

### 2. Command Validation Agent (`specialized/class-mpai-command-validation-agent.php`)

Validates commands before execution to prevent errors:

- Validates plugin commands (activate, deactivate)
- Validates theme commands (activate, update)
- Validates block commands
- Corrects command parameters when possible
- Identifies and suggests alternatives for invalid commands

## SDK Integration

The SDK integration layer (`sdk/`) connects the agent system to external AI providers:

### 1. SDK Integration (`sdk/class-mpai-sdk-integration.php`)

- Provides integration with OpenAI Assistants API
- Creates and manages OpenAI assistants for agents
- Handles conversation threading and history
- Executes tool calls from the assistant
- Supports fallback mechanisms for browser environments

### 2. Tool Execution

Tools are executed through:
1. Direct PHP execution via the tool registry
2. SDK-based execution via the OpenAI function calling API
3. Fallback mechanisms for browser environments

## Usage Flow

1. User sends a message to the MemberPress AI Assistant
2. The Agent Orchestrator determines the most appropriate agent
3. The agent processes the request, possibly using tools
4. Tools are executed in the WordPress environment
5. Results are formatted and returned to the user

## Extending the Agent System

### Adding a New Agent

1. Create a new class that extends `MPAI_Base_Agent`
2. Implement the required methods
3. Register the agent with the orchestrator

Example:
```php
class My_Custom_Agent extends MPAI_Base_Agent {
    public function __construct($tool_registry = null, $logger = null) {
        parent::__construct($tool_registry, $logger);
        
        $this->id = 'my_custom_agent';
        $this->name = 'My Custom Agent';
        $this->description = 'Handles specialized functionality';
        $this->capabilities = [
            'custom_operation' => 'Performs a custom operation',
        ];
    }
    
    public function process_request($intent_data, $context = []) {
        // Implementation
    }
}

// Register with orchestrator
$orchestrator = new MPAI_Agent_Orchestrator();
$custom_agent = new My_Custom_Agent($tool_registry);
$orchestrator->register_agent('my_custom_agent', $custom_agent);
```

### Adding a New Tool

1. Create a new tool class that implements the necessary methods
2. Register the tool with the tool registry

### Security Considerations

- The agent system includes validation of all commands
- Permissions are checked before executing sensitive operations
- All tool executions are logged for auditing
- Specialized agents can implement additional security measures

## Future Enhancements

1. **Enhanced Agent Memory** - Improved context retention across conversations
2. **Dynamic Agent Routing** - Smarter selection of agents based on conversation history
3. **Multi-Agent Collaboration** - Coordinated problem-solving across multiple specialized agents
4. **Custom Tool Development API** - Easier extension of available tools
5. **Adaptive Learning** - Improved agent responses based on user feedback