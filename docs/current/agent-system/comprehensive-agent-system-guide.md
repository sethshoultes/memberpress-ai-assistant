# MemberPress AI Assistant Agentic Systems

**Version:** 1.0.1  
**Last Updated:** 2025-04-05  
**Status:** ✅ Maintained

## Overview

The MemberPress AI Assistant leverages an advanced agentic framework that enables specialized AI agents to perform domain-specific tasks within the WordPress and MemberPress ecosystem. This document provides a comprehensive guide to understanding and integrating with the agent system.

This guide is part of the developer documentation suite for the MemberPress AI Assistant plugin. For an overview of the entire system and guidance on where to start for different development tasks, please see [_0_START_HERE_.md](../../../_0_START_HERE_.md).

## Table of Contents

1. [Architecture](#architecture)
2. [Agent System Components](#agent-system-components)
3. [Agent Interfaces and Base Class](#agent-interfaces-and-base-class)
4. [Tool System Integration](#tool-system-integration)
5. [Agent Orchestration](#agent-orchestration)
6. [Command Validation](#command-validation)
7. [Implementation Guidelines](#implementation-guidelines)
8. [Security Considerations](#security-considerations)
9. [Performance Optimization](#performance-optimization)
10. [Troubleshooting and FAQ](#troubleshooting-and-faq)

## Architecture

The Agent System is designed around a hierarchical architecture with the following layers:

1. **Agent Orchestrator**: Central controller that manages agent routing, specialization assessment, and delegation
2. **Agent Interface**: Common contract for all agent implementations
3. **Base Agent Class**: Abstract implementation with shared functionality
4. **Specialized Agents**: Domain-specific implementations for different tasks
5. **Tool Integration Layer**: Connection between agents and executable tools
6. **SDK Integration**: Connectivity to AI provider APIs (OpenAI, Anthropic)

The system uses a plugin-based architecture where specialized agents can be dynamically discovered and registered at runtime.

## Agent System Components

### 1. Agent Orchestrator

The `MPAI_Agent_Orchestrator` class serves as the central control point for the agent system. It handles:

- Agent discovery and registration
- Agent selection based on specialization scoring
- Request routing to appropriate agents
- Agent message passing and context management

Key methods:
- `register_agent($agent)`: Registers a new agent instance
- `get_best_agent_for_request($request)`: Determines the most suitable agent
- `handle_request($request)`: Routes a request to the appropriate agent
- `execute_tool_call($tool_call, $context)`: Executes a tool on behalf of an agent

### 2. Agent Interface

The `MPAI_Agent` interface defines the contract for all agent implementations:

```php
interface MPAI_Agent {
    public function get_agent_name();
    public function get_agent_description();
    public function get_specialization_score($request);
    public function process_request($request, $context);
    public function get_system_prompt();
    public function get_capabilities();
    public function can_handle_capability($capability);
}
```

These methods ensure consistent behavior across all agent implementations.

### 3. Base Agent Class

The `MPAI_Base_Agent` abstract class provides a foundation for all agents:

- Implements common agent functionality
- Handles context management and persistence
- Provides standard tool integration methods
- Manages agent-specific memory and state

Extending this class is the recommended approach for creating new agents.

### 4. Specialized Agents

Several specialized agents are included:

- `MPAI_MemberPress_Agent`: Specialized for MemberPress operations
- `MPAI_Command_Validation_Agent`: Validates commands for security
- `MPAI_Content_Agent`: Handles content creation and modification
- `MPAI_System_Agent`: Performs system administration tasks

## Agent Interfaces and Base Class

### Agent Interface (MPAI_Agent)

The interface ensures all agents provide:

- Identity information (name, description)
- Capability assessment (specialization scoring)
- Request processing
- System prompt generation
- Capability declarations and verification

### Base Agent (MPAI_Base_Agent)

The abstract base class provides:

```php
abstract class MPAI_Base_Agent implements MPAI_Agent {
    protected $context;
    protected $logger;
    protected $memory;
    protected $capabilities;
    
    public function __construct($context, $logger) {
        $this->context = $context;
        $this->logger = $logger;
        $this->memory = new MPAI_Agent_Memory();
        $this->capabilities = $this->register_capabilities();
    }
    
    // Implementation of common methods
    public function get_specialization_score($request) {
        // Base implementation using keyword matching and capability assessment
    }
    
    public function process_request($request, $context) {
        // Template method for request processing
    }
    
    // Abstract methods that must be implemented
    abstract protected function register_capabilities();
    abstract protected function generate_system_prompt();
}
```

## Tool System Integration

Agents interact with the Tool System to execute operations:

1. The agent receives a request through `process_request()`
2. The agent evaluates available tools for the operation
3. The agent requests tool execution through the context manager
4. The agent incorporates tool results into its response

Example of tool integration:

```php
// Within an agent implementation
public function process_request($request, $context) {
    // Determine required tools
    $tools = $this->get_required_tools($request);
    
    // Include tool definitions in the context
    $context->set_available_tools($tools);
    
    // Generate response using AI provider
    $response = $this->generate_response($request, $context);
    
    // Extract and execute tool calls
    $tool_calls = $this->extract_tool_calls($response);
    foreach ($tool_calls as $tool_call) {
        $result = $context->execute_tool($tool_call);
        $response = $this->incorporate_tool_result($response, $result);
    }
    
    return $response;
}
```

## Agent Orchestration

The orchestration process follows these steps:

1. User request is received by the chat interface
2. Request is passed to `MPAI_Agent_Orchestrator::handle_request()`
3. Orchestrator calculates specialization scores for all agents
4. Agent with highest score processes the request
5. Results are returned to the chat interface

### Specialization Scoring

Agents assess their suitability for a request through the `get_specialization_score()` method:

```php
public function get_specialization_score($request) {
    $score = 0;
    
    // 1. Check for exact capability matches
    foreach ($this->capabilities as $capability) {
        if (strpos($request, $capability) !== false) {
            $score += 25;
        }
    }
    
    // 2. Check for domain keywords
    foreach ($this->domain_keywords as $keyword) {
        if (strpos($request, $keyword) !== false) {
            $score += 15;
        }
    }
    
    // 3. Check for contextual relevance
    if ($this->is_continuation_of_previous_conversation($request)) {
        $score += 20;
    }
    
    // 4. Apply scaling factors
    $score = $this->apply_scaling_factors($score, $request);
    
    return min(100, $score);
}
```

Scores range from 0-100, with the highest-scoring agent selected for request handling.

## Command Validation

The `MPAI_Command_Validation_Agent` provides security validation for potentially sensitive operations:

1. Intercepts requests containing command execution patterns
2. Validates commands against security policies
3. Prevents execution of unsafe or unauthorized commands
4. Provides explanations for denied operations

Example validation rules:

- Disallow direct database queries
- Restrict filesystem write operations to safe directories
- Prevent execution of arbitrary PHP code
- Require validation for user management operations

## Implementation Guidelines

### Creating a New Agent

To create a new specialized agent:

1. Create a class that extends `MPAI_Base_Agent` 
2. Implement all required abstract methods
3. Register capabilities and domain keywords
4. Create agent-specific system prompt
5. Implement specialized request processing
6. Register the agent with the orchestrator

Example skeleton:

```php
class MPAI_My_Specialized_Agent extends MPAI_Base_Agent {
    protected function register_capabilities() {
        return [
            'capability_one',
            'capability_two',
            'capability_three'
        ];
    }
    
    protected function generate_system_prompt() {
        return "You are a specialized agent for handling...[specific instructions]";
    }
    
    public function get_specialization_score($request) {
        $base_score = parent::get_specialization_score($request);
        
        // Add custom scoring logic here
        
        return $base_score;
    }
    
    public function process_request($request, $context) {
        // Specialized processing logic
    }
}
```

### Agent Registration

Agents are registered with the orchestrator during initialization:

```php
// In the plugin initialization
$orchestrator = new MPAI_Agent_Orchestrator($context, $logger);

// Register core agents
$orchestrator->register_agent(new MPAI_MemberPress_Agent($context, $logger));
$orchestrator->register_agent(new MPAI_Command_Validation_Agent($context, $logger));

// Register your custom agent
$orchestrator->register_agent(new MPAI_My_Specialized_Agent($context, $logger));
```

## Security Considerations

The agent system implements several security measures:

1. **Command Validation**: All commands are validated before execution
2. **Capability Restrictions**: Agents are limited to declared capabilities
3. **Authorization Checks**: User permissions are verified for sensitive operations
4. **Sanitization**: All inputs and outputs are sanitized
5. **Audit Logging**: Agent operations are logged for security auditing

Best practices for agent security:

- Limit agent capabilities to the minimum required
- Implement detailed validation for all operations
- Use context manager for tool execution rather than direct execution
- Validate all AI-generated content before processing
- Follow WordPress security best practices for data handling

## Performance Optimization

The agent system includes several performance optimizations:

1. **Lazy Agent Loading**: Agents are loaded only when needed
2. **Response Caching**: Common agent responses are cached
3. **Tool Result Caching**: Results of expensive tool operations are cached
4. **Specialization Score Optimization**: Fast-path scoring for common requests
5. **Memory Management**: Agent memory is garbage-collected when not needed

## Troubleshooting and FAQ

### Common Issues

1. **Agent Selection Problems**
   - Check specialization scoring implementation
   - Verify agent registration
   - Review request formatting

2. **Tool Execution Failures**
   - Ensure tools are properly registered
   - Check tool definitions and parameters
   - Verify context manager initialization

3. **Performance Issues**
   - Enable response caching
   - Review memory usage patterns
   - Consider specialized agent count

### Frequently Asked Questions

**Q: How do I debug agent selection issues?**
A: Enable debug logging with `$logger->set_level('debug')` and check agent scores with `$orchestrator->get_agent_scores($request)`.

**Q: Can multiple agents handle a request?**
A: Currently, only one agent processes each request, but agents can delegate subtasks to other agents.

**Q: How do I add new tools for agents?**
A: Register new tools with the Tool Registry and ensure they're available in the context provided to agents.

**Q: What's the recommended agent count?**
A: For optimal performance, limit to 5-7 specialized agents focusing on distinct domains.

## Related Documentation

- [Tool Implementation Map](../tool-system/tool-implementation-map.md)
- [Error Recovery System](../error-system/error-recovery-system.md)
- [Command Validation Agent](./command-validation-agent.md)
- [System Map](../core/system-map.md)