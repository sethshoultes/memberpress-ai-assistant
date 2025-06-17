# Agent API Documentation

## Overview

The Agent API provides a powerful system for creating intelligent AI agents that can handle specific types of user requests. Agents are specialized components that understand context, make decisions, and coordinate with tools to accomplish tasks.

## Architecture

```
User Request → Agent Orchestrator → Agent Selection → Agent Execution → Response
                     ↓                    ↓              ↓
                Context Analysis    Scoring System   Tool Coordination
```

## Core Interfaces

### AgentInterface

All agents must implement the `AgentInterface`:

```php
<?php
namespace MemberpressAiAssistant\Interfaces;

interface AgentInterface {
    /**
     * Determine if this agent can handle the given context
     */
    public function canHandle(array $context): bool;
    
    /**
     * Execute the agent's logic for the given context
     */
    public function execute(array $context): array;
    
    /**
     * Get a score indicating how well this agent can handle the context
     */
    public function getScore(array $context): float;
    
    /**
     * Get the agent's identifier
     */
    public function getName(): string;
    
    /**
     * Get the agent's description
     */
    public function getDescription(): string;
}
```

### AbstractAgent

For convenience, extend the `AbstractAgent` base class:

```php
<?php
namespace MemberpressAiAssistant\Abstracts;

abstract class AbstractAgent implements AgentInterface {
    protected $name;
    protected $description;
    protected $tools = [];
    protected $logger;
    
    public function __construct(string $name, array $dependencies = []) {
        $this->name = $name;
        $this->logger = $dependencies['logger'] ?? null;
    }
    
    protected function logInfo(string $message, array $context = []): void {
        if ($this->logger) {
            $this->logger->info($message, $context);
        }
    }
    
    protected function executeTools(array $tool_calls): array {
        // Tool execution logic
    }
}
```

## Creating Custom Agents

### Basic Agent Implementation

```php
<?php
namespace YourPlugin\Agents;

use MemberpressAiAssistant\Abstracts\AbstractAgent;

class ReportingAgent extends AbstractAgent {
    
    public function __construct() {
        parent::__construct('reporting_agent');
        $this->description = 'Handles membership reporting and analytics requests';
    }
    
    public function canHandle(array $context): bool {
        $message = strtolower($context['message'] ?? '');
        
        $reporting_keywords = [
            'report', 'analytics', 'stats', 'revenue', 
            'members', 'growth', 'dashboard'
        ];
        
        foreach ($reporting_keywords as $keyword) {
            if (strpos($message, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    public function execute(array $context): array {
        $this->logInfo('Executing reporting agent', $context);
        
        try {
            // Analyze the request
            $report_type = $this->identifyReportType($context['message']);
            
            // Execute appropriate tools
            $tool_results = $this->executeReportingTools($report_type, $context);
            
            // Format response
            return [
                'success' => true,
                'response' => $this->formatReportResponse($tool_results),
                'data' => $tool_results,
                'agent' => $this->getName()
            ];
            
        } catch (\Exception $e) {
            $this->logInfo('Reporting agent error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Failed to generate report',
                'details' => $e->getMessage()
            ];
        }
    }
    
    public function getScore(array $context): float {
        if (!$this->canHandle($context)) {
            return 0.0;
        }
        
        $message = strtolower($context['message'] ?? '');
        
        // Higher score for specific reporting terms
        if (preg_match('/\b(revenue|analytics|dashboard)\b/', $message)) {
            return 0.9;
        }
        
        // Medium score for general reporting terms
        if (preg_match('/\b(report|stats|growth)\b/', $message)) {
            return 0.7;
        }
        
        return 0.5;
    }
    
    private function identifyReportType(string $message): string {
        if (strpos($message, 'revenue') !== false) {
            return 'revenue';
        }
        if (strpos($message, 'member') !== false) {
            return 'membership';
        }
        return 'general';
    }
    
    private function executeReportingTools(string $type, array $context): array {
        global $mpai_service_locator;
        $tool_registry = $mpai_service_locator->get('tool_registry');
        
        switch ($type) {
            case 'revenue':
                $tool = $tool_registry->get('memberpress_tool');
                return $tool->execute([
                    'action' => 'get_revenue_report',
                    'timeframe' => 'monthly'
                ]);
                
            case 'membership':
                $tool = $tool_registry->get('memberpress_tool');
                return $tool->execute([
                    'action' => 'get_membership_stats'
                ]);
                
            default:
                return ['message' => 'General reporting not implemented'];
        }
    }
    
    private function formatReportResponse(array $data): string {
        // Format the response for user consumption
        return "Here's your report: " . json_encode($data);
    }
}
```

### Advanced Agent with Memory

```php
<?php
namespace YourPlugin\Agents;

use MemberpressAiAssistant\Abstracts\AbstractAgent;

class ConversationAgent extends AbstractAgent {
    
    private $shortTermMemory = [];
    private $longTermMemory;
    
    public function __construct() {
        parent::__construct('conversation_agent');
        $this->description = 'Handles conversational context and follow-up questions';
        $this->initializeMemory();
    }
    
    public function execute(array $context): array {
        // Store context in short-term memory
        $this->shortTermMemory[] = $context;
        
        // Maintain memory size
        if (count($this->shortTermMemory) > 10) {
            array_shift($this->shortTermMemory);
        }
        
        // Check for follow-up context
        $previous_context = $this->getPreviousContext($context);
        
        if ($previous_context) {
            return $this->handleFollowUp($context, $previous_context);
        }
        
        return $this->handleNewConversation($context);
    }
    
    public function canHandle(array $context): bool {
        // This agent can handle any context but with lower priority
        return true;
    }
    
    public function getScore(array $context): float {
        // Lower score so other specialized agents are preferred
        return 0.3;
    }
    
    private function initializeMemory(): void {
        // Initialize long-term memory (could use database, cache, etc.)
        $this->longTermMemory = get_transient('mpai_conversation_memory') ?: [];
    }
    
    private function getPreviousContext(array $context): ?array {
        $user_id = $context['user_id'] ?? null;
        
        if (!$user_id) {
            return null;
        }
        
        // Look for recent context in short-term memory
        for ($i = count($this->shortTermMemory) - 1; $i >= 0; $i--) {
            $memory = $this->shortTermMemory[$i];
            if (($memory['user_id'] ?? null) === $user_id) {
                return $memory;
            }
        }
        
        return null;
    }
    
    private function handleFollowUp(array $context, array $previous): array {
        $this->logInfo('Handling follow-up conversation', [
            'current' => $context,
            'previous' => $previous
        ]);
        
        // Combine contexts for better understanding
        $combined_context = array_merge($previous, [
            'message' => $context['message'],
            'is_followup' => true,
            'previous_message' => $previous['message'] ?? ''
        ]);
        
        // Delegate to appropriate specialized agent
        global $mpai_service_locator;
        $orchestrator = $mpai_service_locator->get('orchestrator');
        
        return $orchestrator->processRequest($combined_context, ['exclude' => [$this->getName()]]);
    }
    
    private function handleNewConversation(array $context): array {
        // Store in long-term memory
        $user_id = $context['user_id'] ?? 'anonymous';
        $this->longTermMemory[$user_id] = [
            'last_interaction' => time(),
            'context' => $context
        ];
        
        set_transient('mpai_conversation_memory', $this->longTermMemory, HOUR_IN_SECONDS);
        
        return [
            'success' => true,
            'response' => "I'm here to help! What would you like to know?",
            'agent' => $this->getName()
        ];
    }
}
```

## Agent Orchestration

### Registering Agents

```php
// Register during plugin initialization
add_action('init', function() {
    global $mpai_service_locator;
    $agent_registry = $mpai_service_locator->get('agent_registry');
    
    // Register custom agents
    $agent_registry->register('reporting_agent', new ReportingAgent());
    $agent_registry->register('conversation_agent', new ConversationAgent());
});
```

### Manual Agent Orchestration

```php
global $mpai_service_locator;
$orchestrator = $mpai_service_locator->get('orchestrator');

// Process a request
$response = $orchestrator->processRequest([
    'message' => 'Show me revenue for this month',
    'user_id' => get_current_user_id(),
    'context_type' => 'admin_chat'
]);

// Process with specific agent
$agent_registry = $mpai_service_locator->get('agent_registry');
$reporting_agent = $agent_registry->get('reporting_agent');

$response = $reporting_agent->execute([
    'message' => 'Generate membership report',
    'user_id' => 123
]);
```

## Built-in Agents

### MemberPressAgent

Handles MemberPress-specific operations:

```php
// Example usage
$context = [
    'message' => 'Create a new membership plan called Premium for $29.99',
    'user_id' => get_current_user_id()
];

$memberpress_agent = $agent_registry->get('memberpress_agent');
$result = $memberpress_agent->execute($context);
```

**Capabilities:**
- Membership creation and management
- User-membership relationships
- Transaction processing
- Subscription management
- Access rule configuration

### ContentAgent

Handles content generation and management:

```php
// Example usage
$context = [
    'message' => 'Write a welcome email for new premium members',
    'user_id' => get_current_user_id(),
    'content_type' => 'email'
];

$content_agent = $agent_registry->get('content_agent');
$result = $content_agent->execute($context);
```

**Capabilities:**
- Email template generation
- Page and post content creation
- Marketing copy generation
- Documentation creation

### SystemAgent

Handles WordPress and plugin administration:

```php
// Example usage
$context = [
    'message' => 'Show me plugin performance metrics',
    'user_id' => get_current_user_id()
];

$system_agent = $agent_registry->get('system_agent');
$result = $system_agent->execute($context);
```

**Capabilities:**
- Plugin configuration
- Performance monitoring
- System diagnostics
- User management

### ValidationAgent

Handles data validation and verification:

```php
// Example usage
$context = [
    'message' => 'Validate this membership configuration',
    'data' => $membership_config,
    'user_id' => get_current_user_id()
];

$validation_agent = $agent_registry->get('validation_agent');
$result = $validation_agent->execute($context);
```

**Capabilities:**
- Data validation
- Configuration verification
- Error checking
- Compliance validation

## Scoring System

Agents use a scoring system (0.0 to 1.0) to indicate how well they can handle a request:

```php
public function getScore(array $context): float {
    $message = strtolower($context['message'] ?? '');
    
    // Perfect match
    if (preg_match('/\b(create|new) membership\b/', $message)) {
        return 1.0;
    }
    
    // Good match
    if (strpos($message, 'membership') !== false) {
        return 0.8;
    }
    
    // Partial match
    if (strpos($message, 'member') !== false) {
        return 0.6;
    }
    
    // No match
    return 0.0;
}
```

**Score Guidelines:**
- **1.0**: Perfect match, agent is ideal for this request
- **0.8-0.9**: Excellent match, agent handles this type well
- **0.6-0.7**: Good match, agent can handle with reasonable results
- **0.4-0.5**: Partial match, agent might help but not ideal
- **0.1-0.3**: Poor match, agent probably shouldn't handle this
- **0.0**: No match, agent cannot handle this request

## Agent Delegation

Agents can delegate to other agents:

```php
protected function delegateToSpecialist(array $context, string $specialist_type): array {
    global $mpai_service_locator;
    $orchestrator = $mpai_service_locator->get('orchestrator');
    
    // Add delegation context
    $context['delegated_from'] = $this->getName();
    $context['specialist_requested'] = $specialist_type;
    
    return $orchestrator->processRequest($context, [
        'exclude' => [$this->getName()], // Don't delegate back to ourselves
        'prefer' => [$specialist_type]   // Prefer specific agent type
    ]);
}
```

## Performance Optimization

### Caching Agent Responses

```php
protected function getCachedResponse(array $context): ?array {
    $cache_key = 'mpai_agent_' . $this->getName() . '_' . md5(serialize($context));
    return get_transient($cache_key);
}

protected function setCachedResponse(array $context, array $response): void {
    $cache_key = 'mpai_agent_' . $this->getName() . '_' . md5(serialize($context));
    set_transient($cache_key, $response, 300); // Cache for 5 minutes
}

public function execute(array $context): array {
    // Check cache first
    $cached = $this->getCachedResponse($context);
    if ($cached !== null) {
        return $cached;
    }
    
    // Execute logic
    $response = $this->performExecution($context);
    
    // Cache response
    $this->setCachedResponse($context, $response);
    
    return $response;
}
```

### Lazy Tool Loading

```php
protected function getToolLazy(string $tool_name): object {
    if (!isset($this->tools[$tool_name])) {
        global $mpai_service_locator;
        $tool_registry = $mpai_service_locator->get('tool_registry');
        $this->tools[$tool_name] = $tool_registry->get($tool_name);
    }
    
    return $this->tools[$tool_name];
}
```

## Testing Agents

### Unit Testing

```php
<?php
namespace YourPlugin\Tests;

use PHPUnit\Framework\TestCase;
use YourPlugin\Agents\ReportingAgent;

class ReportingAgentTest extends TestCase {
    
    private $agent;
    
    protected function setUp(): void {
        $this->agent = new ReportingAgent();
    }
    
    public function testCanHandleReportingRequests(): void {
        $context = ['message' => 'Show me revenue report'];
        
        $this->assertTrue($this->agent->canHandle($context));
        $this->assertGreaterThan(0.5, $this->agent->getScore($context));
    }
    
    public function testCannotHandleNonReportingRequests(): void {
        $context = ['message' => 'Create a new membership'];
        
        $this->assertFalse($this->agent->canHandle($context));
        $this->assertEquals(0.0, $this->agent->getScore($context));
    }
    
    public function testExecuteReturnsValidResponse(): void {
        $context = [
            'message' => 'Generate membership report',
            'user_id' => 123
        ];
        
        $response = $this->agent->execute($context);
        
        $this->assertArrayHasKey('success', $response);
        $this->assertArrayHasKey('response', $response);
        $this->assertEquals('reporting_agent', $response['agent']);
    }
}
```

### Integration Testing

```php
public function testAgentIntegrationWithOrchestrator(): void {
    global $mpai_service_locator;
    
    // Setup
    $orchestrator = $mpai_service_locator->get('orchestrator');
    $agent_registry = $mpai_service_locator->get('agent_registry');
    $agent_registry->register('test_reporting', new ReportingAgent());
    
    // Test
    $response = $orchestrator->processRequest([
        'message' => 'Show me this month revenue',
        'user_id' => 123
    ]);
    
    // Assert
    $this->assertTrue($response['success']);
    $this->assertEquals('test_reporting', $response['agent']);
}
```

## Best Practices

### Agent Design Principles

1. **Single Responsibility**: Each agent should handle one domain well
2. **Clear Boundaries**: Define clear criteria for what the agent handles
3. **Graceful Degradation**: Handle errors gracefully and provide helpful messages
4. **Context Awareness**: Use available context to improve responses
5. **Tool Coordination**: Leverage existing tools rather than duplicating functionality

### Error Handling

```php
public function execute(array $context): array {
    try {
        $result = $this->performMainLogic($context);
        
        return [
            'success' => true,
            'response' => $result['message'],
            'data' => $result['data'] ?? null,
            'agent' => $this->getName()
        ];
        
    } catch (ValidationException $e) {
        return [
            'success' => false,
            'error' => 'Validation failed',
            'details' => $e->getMessage(),
            'agent' => $this->getName()
        ];
        
    } catch (\Exception $e) {
        $this->logInfo('Agent execution error', [
            'error' => $e->getMessage(),
            'context' => $context
        ]);
        
        return [
            'success' => false,
            'error' => 'An unexpected error occurred',
            'agent' => $this->getName()
        ];
    }
}
```

### Security Considerations

```php
protected function validateUserPermissions(array $context): bool {
    $user_id = $context['user_id'] ?? null;
    
    if (!$user_id) {
        return false;
    }
    
    // Check WordPress capabilities
    $user = get_user_by('id', $user_id);
    if (!$user || !user_can($user, 'manage_options')) {
        return false;
    }
    
    return true;
}
```

## Hooks and Filters

### Available Hooks

```php
// Before agent execution
do_action('mpai_before_agent_execute', $agent_name, $context);

// After agent execution
do_action('mpai_after_agent_execute', $agent_name, $context, $response);

// Agent selection
apply_filters('mpai_agent_scores', $scores, $context);

// Context modification
apply_filters('mpai_agent_context', $context, $agent_name);
```

### Custom Hook Implementation

```php
// In your agent
public function execute(array $context): array {
    // Allow context modification
    $context = apply_filters('mpai_agent_context', $context, $this->getName());
    
    // Execute with hooks
    do_action('mpai_before_agent_execute', $this->getName(), $context);
    
    $response = $this->performExecution($context);
    
    do_action('mpai_after_agent_execute', $this->getName(), $context, $response);
    
    return $response;
}
```

---

**Next:** Check out the [Tool API documentation](tool-api.md) to learn about building operations that agents can use.