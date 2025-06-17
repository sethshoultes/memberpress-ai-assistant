# Agent Architecture in the MemberPress Copilot

This document provides a comprehensive overview of the agent-based architecture in the MemberPress Copilot plugin, including the agent system design, orchestration mechanisms, and integration with the tool system.

## Agent Architecture Overview

The MemberPress Copilot implements a sophisticated agent-based architecture where specialized agents handle different types of requests:

### Core Components

1. **Agent Interface and Base Class**:
   - [`AgentInterface`](../src/Interfaces/AgentInterface.php) defines the contract for all agents with methods for:
     - Specialization scoring (`getSpecializationScore`)
     - Request processing (`processRequest`)
     - System prompt management (`getSystemPrompt`)
     - Capability declaration (`getCapabilities`)
   - [`AbstractAgent`](../src/Abstracts/AbstractAgent.php) provides a robust base implementation with:
     - Memory management (short-term and long-term)
     - Context handling
     - Scoring algorithms
     - Capability registration

2. **Agent Orchestration**:
   - [`AgentOrchestrator`](../src/Orchestration/AgentOrchestrator.php) coordinates agent activities:
     - Selects the most appropriate agent for each request
     - Implements sophisticated agent selection algorithms with pattern recognition
     - Uses progressive scoring with early termination for efficiency
     - Manages delegation between agents
     - Handles context sharing and updates

3. **Agent Factory**:
   - [`AgentFactory`](../src/Factory/AgentFactory.php) creates agent instances:
     - Uses dependency injection for service management
     - Validates agent classes
     - Manages agent registration with the registry

4. **Agent Registry**:
   - Maintains a registry of available agents
   - Provides methods to find agents by specialization or capability
   - Manages agent lifecycle

## Agent Selection Process

The agent selection process is a sophisticated algorithm that determines which agent is best suited to handle a particular request:

1. **Fast-Path Selection**:
   - Uses pattern recognition to quickly match requests to previously successful agents
   - Maintains a cache of request patterns and corresponding agent selections

2. **Progressive Scoring**:
   - Calculates specialization scores for each agent based on the request
   - Applies context multipliers based on entity relevance
   - Applies history weights based on previous agent selections
   - Implements early termination when a clear winner emerges

3. **Scoring Components**:
   - Intent matching (0-30 points)
   - Entity relevance (0-30 points)
   - Capability matching (0-20 points)
   - Context continuity (0-20 points)
   - Additional multipliers based on agent-specific criteria

## Agent Memory Management

Agents in the system maintain both short-term and long-term memory:

1. **Short-Term Memory**:
   - Stores recent interactions and context
   - Limited size with automatic pruning of oldest entries
   - Used for maintaining conversation context

2. **Long-Term Memory**:
   - Persists across sessions
   - Stores user preferences and important information
   - Can be saved to external storage

## Agent Delegation

The system supports delegation between agents:

1. **Delegation Process**:
   - An agent can delegate a request to another agent with specialized capabilities
   - The delegation includes context and reason for delegation
   - The delegated agent processes the request and returns a response

2. **Delegation Stack**:
   - Tracks the chain of delegations to prevent infinite loops
   - Enforces a maximum delegation depth

## Integration with Tool System

The agent architecture integrates with the tool system:

1. **Agents Use Tools**:
   - Agents leverage tools to perform specific operations
   - When an agent needs to execute a particular function, it uses the appropriate tool from the ToolRegistry

2. **Orchestration Layer**:
   - The AgentOrchestrator selects the most appropriate agent for a request
   - The selected agent then uses tools to fulfill the request

3. **Context Sharing**:
   - Both tools and agents share context through the ContextManager
   - This allows for consistent state management across the system

4. **Memory vs. Caching**:
   - Agents maintain memory for stateful operations
   - Tools use caching for performance optimization of stateless operations

## Agent Capabilities

Agents declare their capabilities, which are used in the selection process:

1. **Capability Registration**:
   - Agents register their capabilities during initialization
   - Capabilities can include metadata for more detailed matching

2. **Capability-Based Selection**:
   - The orchestrator uses capabilities to match agents to requests
   - Agents with capabilities matching the entities in a request receive higher scores

## Performance Optimization

The agent system includes several performance optimizations:

1. **Pattern Caching**:
   - Caches request patterns for fast-path selection
   - Reduces the need for full agent scoring

2. **Progressive Scoring**:
   - Implements early termination when a clear winner emerges
   - Avoids unnecessary scoring calculations

3. **Response Caching**:
   - Caches agent responses for similar requests
   - Configurable TTL based on request type

## Extending the Agent System

The agent system is designed to be extensible:

1. **Creating a New Agent**:
   - Create a class that extends `AbstractAgent`
   - Implement the required methods:
     - `calculateIntentMatchScore`: Define how the agent scores intent matches
     - `calculateEntityRelevanceScore`: Define entity relevance scoring
     - `calculateCapabilityMatchScore`: Define capability matching
     - `registerCapabilities`: Register the agent's capabilities
     - `processRequest`: Implement the agent's request processing logic

2. **Registering an Agent**:
   - Use the AgentFactory to create and register the agent
   - Or register directly with the AgentRegistry

## Example Agent Implementation

```php
class MembershipAgent extends AbstractAgent {
    public function __construct($logger = null) {
        parent::__construct($logger);
        $this->registerCapabilities();
    }
    
    protected function registerCapabilities(): void {
        $this->addCapability('membership', [
            'operations' => ['create', 'update', 'delete', 'list'],
        ]);
        $this->addCapability('subscription', [
            'operations' => ['manage', 'cancel'],
        ]);
    }
    
    protected function calculateIntentMatchScore(array $request): float {
        $message = $request['message'] ?? '';
        $score = 0.0;
        
        // Score based on membership-related keywords
        if (stripos($message, 'membership') !== false) {
            $score += 20.0;
        }
        if (stripos($message, 'subscription') !== false) {
            $score += 15.0;
        }
        
        return min(30.0, $score);
    }
    
    protected function calculateEntityRelevanceScore(array $request): float {
        // Implementation for entity relevance scoring
        // ...
    }
    
    protected function calculateCapabilityMatchScore(array $request): float {
        // Implementation for capability matching
        // ...
    }
    
    public function processRequest(array $request, array $context): array {
        // Implementation for processing membership-related requests
        // ...
    }
    
    public function getSystemPrompt(): string {
        return "You are a membership management assistant...";
    }
}
```

## Conclusion

The agent-based architecture of the MemberPress Copilot provides a sophisticated system for handling user requests. By using specialized agents with different capabilities, the system can route requests to the most appropriate handler, improving response quality and efficiency.

The integration with the tool system creates a separation of concerns where agents handle high-level decision making, request routing, and specialization, while tools provide the specific operations and functionality needed to fulfill requests. This creates a flexible and maintainable system that can be easily extended with new capabilities.