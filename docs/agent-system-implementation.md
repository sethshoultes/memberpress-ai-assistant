# MemberPress AI Assistant: Agent System Implementation Plan

## Overview

This document outlines the implementation plan for integrating the OpenAI Agents SDK into the MemberPress AI Assistant plugin. The implementation will follow a phased approach to build a robust, extensible agent system while ensuring minimal disruption to existing functionality.

## Phase 1: Foundation (Weeks 1-2)

### 1.1 Project Setup and SDK Integration

- Install and configure OpenAI Agents SDK
  - `composer require openai/openai-php`
  - Set up appropriate namespace handling
- Create base agent architecture
  - Directory structure creation
  - Core interfaces definition

### 1.2 Core Agent Classes

- Implement `MPAI_Agent_Orchestrator` class
- Create `MPAI_Base_Agent` abstract class
- Implement `MPAI_Agent_Interface`
- Set up tool integration layer

### 1.3 Database Schema Updates

- Create agent-specific database tables:
  - `mpai_agent_tasks` - For tracking agent tasks
  - `mpai_agent_memory` - For storing agent memory/context

### 1.4 Basic Tool Implementation

- Implement WP-CLI Tool
- Implement WordPress Core API Tool
- Implement MemberPress API Tool

## Phase 2: Initial Agents (Weeks 3-4)

### 2.1 Content Agent

- Implement specialized content agent
- Build content generation capabilities
- Integrate with WordPress post creation API

### 2.2 System Agent

- Implement system management agent
- Create system diagnostic capabilities
- Build secure command execution system

### 2.3 MemberPress Agent

- Implement MemberPress-specific agent
- Create membership management capabilities
- Build subscription and transaction handling

### 2.4 Agent Orchestration Logic

- Implement intent classification
- Build agent selection system
- Create multi-agent workflow handling

## Phase 3: User Interface & API (Weeks 5-6)

### 3.1 REST API Development

- Create agent system endpoints
- Implement task management API
- Build agent configuration endpoints

### 3.2 Agent Dashboard

- Create admin interface for agents
- Implement agent configuration UI
- Build task monitoring and history view

### 3.3 Chat Interface Integration

- Update existing chat interface to support agents
- Implement agent selection in chat
- Add agent capabilities listing

### 3.4 Agent Results Display

- Create result templates for different agent types
- Implement formatted output for various tasks
- Build visual feedback for agent actions

## Phase 4: Advanced Features (Weeks 7-8)

### 4.1 Memory and Context System

- Implement short-term memory for agents
- Create long-term memory storage
- Build context-aware agent responses

### 4.2 Advanced Workflows

- Implement chained agent actions
- Build conditional task execution
- Create task verification and correction

### 4.3 Background Processing

- Implement async processing for long-running tasks
- Create task queue management
- Build notification system for completed tasks

### 4.4 Security Enhancements

- Implement granular permission system
- Create action validation and sanitization
- Build audit logging for agent actions

## Phase 5: Testing & Refinement (Weeks 9-10)

### 5.1 Comprehensive Testing

- Unit tests for core components
- Integration tests for agent system
- End-to-end testing of workflows

### 5.2 Performance Optimization

- Optimize API usage
- Implement caching strategies
- Reduce resource consumption

### 5.3 Documentation

- Create developer documentation
- Write user guides
- Document API endpoints

### 5.4 Final Refinements

- Polish user interface
- Enhance error handling
- Implement user feedback system

## Technical Implementation Details

### Directory Structure

```
memberpress-ai-assistant/
├── includes/
│   ├── agents/
│   │   ├── class-mpai-agent-orchestrator.php
│   │   ├── class-mpai-base-agent.php
│   │   ├── interface-mpai-agent.php
│   │   └── specialized/
│   │       ├── class-mpai-content-agent.php
│   │       ├── class-mpai-system-agent.php
│   │       ├── class-mpai-security-agent.php
│   │       ├── class-mpai-analytics-agent.php
│   │       └── class-mpai-memberpress-agent.php
│   ├── tools/
│   │   ├── class-mpai-tool-registry.php
│   │   ├── class-mpai-base-tool.php
│   │   └── implementations/
│   │       ├── class-mpai-wpcli-tool.php
│   │       ├── class-mpai-wordpress-tool.php
│   │       ├── class-mpai-memberpress-tool.php
│   │       └── class-mpai-file-system-tool.php
│   ├── memory/
│   │   ├── class-mpai-memory-manager.php
│   │   └── class-mpai-context-store.php
│   ├── api/
│   │   └── class-mpai-agent-endpoints.php
│   └── utils/
│       ├── class-mpai-security.php
│       └── class-mpai-logger.php
├── admin/
│   ├── class-mpai-agent-settings.php
│   └── views/
│       ├── agent-dashboard.php
│       └── agent-settings.php
└── templates/
    └── agent-results/
        ├── content-result.php
        ├── system-result.php
        └── memberpress-result.php
```

### Key Classes Implementation

#### Agent Orchestrator

```php
/**
 * Main orchestrator for the agent system
 */
class MPAI_Agent_Orchestrator {
    /**
     * Available agents
     *
     * @var array
     */
    private $agents = [];
    
    /**
     * Memory manager
     *
     * @var MPAI_Memory_Manager
     */
    private $memory_manager;
    
    /**
     * Tool registry
     *
     * @var MPAI_Tool_Registry
     */
    private $tool_registry;
    
    /**
     * OpenAI client for intent classification
     *
     * @var MPAI_OpenAI
     */
    private $openai;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->openai = new MPAI_OpenAI();
        $this->memory_manager = new MPAI_Memory_Manager();
        $this->tool_registry = new MPAI_Tool_Registry();
        
        // Register the core agents
        $this->register_core_agents();
    }
    
    /**
     * Register core agents
     */
    private function register_core_agents() {
        // Content Agent
        $this->register_agent('content', new MPAI_Content_Agent($this->tool_registry));
        
        // System Agent
        $this->register_agent('system', new MPAI_System_Agent($this->tool_registry));
        
        // MemberPress Agent
        $this->register_agent('memberpress', new MPAI_MemberPress_Agent($this->tool_registry));
    }
    
    /**
     * Register an agent
     *
     * @param string $agent_id Agent identifier
     * @param MPAI_Agent $agent Agent instance
     */
    public function register_agent($agent_id, $agent) {
        $this->agents[$agent_id] = $agent;
    }
    
    /**
     * Process a user request
     *
     * @param string $message User message
     * @param int $user_id User ID
     * @return array Response data
     */
    public function process_request($message, $user_id = null) {
        // If no user ID provided, use current user
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        try {
            // Get user context from memory
            $context = $this->memory_manager->get_context($user_id);
            
            // Determine intent and appropriate agent
            $intent_data = $this->determine_intent($message, $context);
            
            // Get the primary agent
            $agent_id = $intent_data['primary_agent'];
            
            if (!isset($this->agents[$agent_id])) {
                throw new Exception("Agent not found: {$agent_id}");
            }
            
            // Process the request with the agent
            $result = $this->agents[$agent_id]->process_request($intent_data, $context);
            
            // Update context with result
            $this->memory_manager->update_context($user_id, $message, $result);
            
            return [
                'success' => true,
                'message' => $result['message'],
                'data' => $result['data'],
                'agent' => $agent_id
            ];
        } catch (Exception $e) {
            error_log('MPAI: Error in process_request: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error processing request: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Determine intent and appropriate agent
     *
     * @param string $message User message
     * @param array $context User context
     * @return array Intent data
     */
    private function determine_intent($message, $context = []) {
        // Create system prompt for intent classification
        $system_prompt = "You are an intent classifier for a WordPress plugin with MemberPress integration. ";
        $system_prompt .= "Analyze the user message and classify it into one of these categories: ";
        $system_prompt .= "content_creation, system_management, memberpress_management, general_question";
        
        $messages = [
            ['role' => 'system', 'content' => $system_prompt],
            ['role' => 'user', 'content' => $message]
        ];
        
        // Get classification from OpenAI
        $response = $this->openai->generate_chat_completion($messages);
        
        // Extract intent category
        $intent_category = $this->extract_intent_category($response);
        
        // Map category to agent
        $agent_map = [
            'content_creation' => 'content',
            'system_management' => 'system',
            'memberpress_management' => 'memberpress',
            'general_question' => 'content' // Default to content agent
        ];
        
        $primary_agent = isset($agent_map[$intent_category]) ? $agent_map[$intent_category] : 'content';
        
        return [
            'intent' => $intent_category,
            'primary_agent' => $primary_agent,
            'message' => $message,
            'timestamp' => time()
        ];
    }
    
    /**
     * Extract intent category from response
     *
     * @param string $response OpenAI response
     * @return string Intent category
     */
    private function extract_intent_category($response) {
        $categories = [
            'content_creation',
            'system_management',
            'memberpress_management',
            'general_question'
        ];
        
        foreach ($categories as $category) {
            if (stripos($response, $category) !== false) {
                return $category;
            }
        }
        
        return 'general_question'; // Default fallback
    }
    
    /**
     * Get available agents
     *
     * @return array Available agents with capabilities
     */
    public function get_available_agents() {
        $available_agents = [];
        
        foreach ($this->agents as $agent_id => $agent) {
            $available_agents[$agent_id] = [
                'name' => $agent->get_name(),
                'description' => $agent->get_description(),
                'capabilities' => $agent->get_capabilities()
            ];
        }
        
        return $available_agents;
    }
}
```

#### Agent Interface

```php
/**
 * Interface for all agents
 */
interface MPAI_Agent {
    /**
     * Process a request
     *
     * @param array $intent_data Intent data from orchestrator
     * @param array $context User context
     * @return array Response data
     */
    public function process_request($intent_data, $context = []);
    
    /**
     * Get agent name
     *
     * @return string Agent name
     */
    public function get_name();
    
    /**
     * Get agent description
     *
     * @return string Agent description
     */
    public function get_description();
    
    /**
     * Get agent capabilities
     *
     * @return array Agent capabilities
     */
    public function get_capabilities();
}
```

#### Base Agent

```php
/**
 * Base agent class that all agents should extend
 */
abstract class MPAI_Base_Agent implements MPAI_Agent {
    /**
     * Agent name
     *
     * @var string
     */
    protected $name;
    
    /**
     * Agent description
     *
     * @var string
     */
    protected $description;
    
    /**
     * Agent capabilities
     *
     * @var array
     */
    protected $capabilities = [];
    
    /**
     * Tool registry
     *
     * @var MPAI_Tool_Registry
     */
    protected $tool_registry;
    
    /**
     * OpenAI client
     *
     * @var MPAI_OpenAI
     */
    protected $openai;
    
    /**
     * Constructor
     *
     * @param MPAI_Tool_Registry $tool_registry Tool registry
     */
    public function __construct($tool_registry) {
        $this->tool_registry = $tool_registry;
        $this->openai = new MPAI_OpenAI();
    }
    
    /**
     * Get agent name
     *
     * @return string Agent name
     */
    public function get_name() {
        return $this->name;
    }
    
    /**
     * Get agent description
     *
     * @return string Agent description
     */
    public function get_description() {
        return $this->description;
    }
    
    /**
     * Get agent capabilities
     *
     * @return array Agent capabilities
     */
    public function get_capabilities() {
        return $this->capabilities;
    }
    
    /**
     * Execute a tool
     *
     * @param string $tool_id Tool identifier
     * @param array $parameters Tool parameters
     * @return mixed Tool execution result
     */
    protected function execute_tool($tool_id, $parameters) {
        $tool = $this->tool_registry->get_tool($tool_id);
        
        if (!$tool) {
            throw new Exception("Tool not found: {$tool_id}");
        }
        
        return $tool->execute($parameters);
    }
    
    /**
     * Create action plan
     *
     * @param array $intent_data Intent data
     * @return array Action plan
     */
    protected function create_action_plan($intent_data) {
        // Get available tools
        $available_tools = $this->tool_registry->get_available_tools();
        $tool_descriptions = [];
        
        foreach ($available_tools as $tool_id => $tool) {
            $tool_descriptions[] = [
                'id' => $tool_id,
                'name' => $tool->get_name(),
                'description' => $tool->get_description()
            ];
        }
        
        // Create system prompt
        $system_prompt = "You are an AI assistant creating an action plan for the {$this->name}. ";
        $system_prompt .= "Based on the user's request, create a JSON plan with a sequence of actions using available tools.\n\n";
        $system_prompt .= "Available tools:\n";
        
        foreach ($tool_descriptions as $tool) {
            $system_prompt .= "- {$tool['id']}: {$tool['description']}\n";
        }
        
        $system_prompt .= "\nRespond with a JSON object containing an 'actions' array, where each action has 'tool_id', 'parameters', and 'description' fields.";
        
        $messages = [
            ['role' => 'system', 'content' => $system_prompt],
            ['role' => 'user', 'content' => $intent_data['message']]
        ];
        
        // Get plan from OpenAI
        $response = $this->openai->generate_chat_completion($messages);
        
        // Extract JSON plan
        return $this->extract_json_plan($response);
    }
    
    /**
     * Extract JSON plan from response
     *
     * @param string $response OpenAI response
     * @return array Action plan
     */
    private function extract_json_plan($response) {
        // Try to extract code block
        preg_match('/```(?:json)?\s*([\s\S]*?)```/', $response, $matches);
        
        if (!empty($matches[1])) {
            $json = $matches[1];
        } else {
            // Try to extract just the JSON object
            preg_match('/(\{[\s\S]*\})/', $response, $matches);
            $json = !empty($matches[1]) ? $matches[1] : $response;
        }
        
        // Decode JSON
        $plan = json_decode($json, true);
        
        // Fallback if JSON parsing fails
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'actions' => [
                    [
                        'tool_id' => 'openai',
                        'parameters' => [
                            'prompt' => $response
                        ],
                        'description' => 'Process request directly'
                    ]
                ]
            ];
        }
        
        return $plan;
    }
    
    /**
     * Generate a summary of results
     *
     * @param array $results Action results
     * @param array $intent_data Original intent data
     * @return string Summary
     */
    protected function generate_summary($results, $intent_data) {
        $system_prompt = "You are an AI assistant summarizing the results of actions taken by the {$this->name}. ";
        $system_prompt .= "Create a concise, user-friendly summary of what was accomplished.";
        
        $user_prompt = "Original request: {$intent_data['message']}\n\n";
        $user_prompt .= "Actions and results:\n" . json_encode($results, JSON_PRETTY_PRINT);
        
        $messages = [
            ['role' => 'system', 'content' => $system_prompt],
            ['role' => 'user', 'content' => $user_prompt]
        ];
        
        return $this->openai->generate_chat_completion($messages);
    }
}
```

### Database Schema

```sql
-- Agent Tasks Table
CREATE TABLE {$wpdb->prefix}mpai_agent_tasks (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    task_id varchar(36) NOT NULL,
    user_id bigint(20) unsigned NOT NULL,
    agent_id varchar(50) NOT NULL,
    status varchar(20) NOT NULL DEFAULT 'pending',
    request text NOT NULL,
    result longtext DEFAULT NULL,
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at datetime DEFAULT NULL,
    PRIMARY KEY (id),
    KEY task_id (task_id),
    KEY user_id (user_id),
    KEY agent_id (agent_id),
    KEY status (status)
);

-- Agent Memory Table
CREATE TABLE {$wpdb->prefix}mpai_agent_memory (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    user_id bigint(20) unsigned NOT NULL,
    memory_key varchar(100) NOT NULL,
    memory_value longtext NOT NULL,
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY user_memory (user_id, memory_key)
);
```

## Integration with OpenAI Agents SDK

The implementation will use the OpenAI Agents Python SDK (available at https://openai.github.io/openai-agents-python/) with PHP wrappers for WordPress integration:

### Key Integration Points

1. **Agent Configuration** - Using the SDK's configuration system to define agent behaviors
2. **Handoff Protocol** - Implementing the handoff protocol to allow agents to transfer control
3. **Running Agents** - Creating a WordPress-compatible system for running agents within the plugin
4. **Custom Extensions** - Building WordPress-specific extensions for the SDK

### OpenAI API Integration

```php
/**
 * OpenAI Agents integration
 */
class MPAI_OpenAI_Agents {
    /**
     * API key
     *
     * @var string
     */
    private $api_key;
    
    /**
     * Agent configurations
     *
     * @var array
     */
    private $agent_configs = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->api_key = get_option('mpai_api_key', '');
        $this->load_agent_configs();
    }
    
    /**
     * Load agent configurations
     */
    private function load_agent_configs() {
        // Define base configurations for agents
        $this->agent_configs = [
            'content' => [
                'name' => 'Content Agent',
                'description' => 'Creates and manages WordPress content',
                'model' => get_option('mpai_model', 'gpt-4o'),
                'tools' => ['wordpress', 'filesystem']
            ],
            'system' => [
                'name' => 'System Agent',
                'description' => 'Manages WordPress system tasks',
                'model' => get_option('mpai_model', 'gpt-4o'),
                'tools' => ['wpcli', 'filesystem']
            ],
            'memberpress' => [
                'name' => 'MemberPress Agent',
                'description' => 'Manages MemberPress functionality',
                'model' => get_option('mpai_model', 'gpt-4o'),
                'tools' => ['memberpress', 'wordpress']
            ]
        ];
    }
    
    /**
     * Create an agent
     *
     * @param string $agent_type Agent type
     * @return array Agent definition
     */
    public function create_agent($agent_type) {
        if (!isset($this->agent_configs[$agent_type])) {
            throw new Exception("Unknown agent type: {$agent_type}");
        }
        
        $config = $this->agent_configs[$agent_type];
        
        // Create agent definition for the API
        $agent_definition = [
            'name' => $config['name'],
            'description' => $config['description'],
            'model' => $config['model'],
            'tools' => $this->get_tool_definitions($config['tools']),
            'metadata' => [
                'type' => $agent_type,
                'version' => MPAI_VERSION
            ]
        ];
        
        return $agent_definition;
    }
    
    /**
     * Get tool definitions
     *
     * @param array $tool_ids Tool IDs
     * @return array Tool definitions
     */
    private function get_tool_definitions($tool_ids) {
        $tool_registry = new MPAI_Tool_Registry();
        $tool_definitions = [];
        
        foreach ($tool_ids as $tool_id) {
            $tool = $tool_registry->get_tool($tool_id);
            
            if ($tool) {
                $tool_definitions[] = [
                    'type' => 'function',
                    'function' => [
                        'name' => $tool_id,
                        'description' => $tool->get_description(),
                        'parameters' => $tool->get_parameters_schema()
                    ]
                ];
            }
        }
        
        return $tool_definitions;
    }
    
    /**
     * Run an agent
     *
     * @param string $agent_type Agent type
     * @param string $message User message
     * @param array $context Context
     * @return array Response
     */
    public function run_agent($agent_type, $message, $context = []) {
        $agent_definition = $this->create_agent($agent_type);
        
        // Prepare request data
        $request_data = [
            'agent' => $agent_definition,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $message
                ]
            ]
        ];
        
        // Add context if provided
        if (!empty($context)) {
            $context_message = [
                'role' => 'system',
                'content' => 'Prior context: ' . json_encode($context)
            ];
            
            array_unshift($request_data['messages'], $context_message);
        }
        
        // Make the API request
        $response = $this->send_request('agents/runs', $request_data);
        
        return $this->process_agent_response($response);
    }
    
    /**
     * Process agent response
     *
     * @param array $response API response
     * @return array Processed response
     */
    private function process_agent_response($response) {
        // Extract relevant information from the response
        $processed_response = [
            'success' => isset($response['status']) && $response['status'] === 'completed',
            'message' => $this->extract_message($response),
            'data' => [
                'run_id' => $response['id'] ?? null,
                'tool_calls' => $response['tool_calls'] ?? [],
                'steps' => $response['steps'] ?? []
            ]
        ];
        
        return $processed_response;
    }
    
    /**
     * Extract message from response
     *
     * @param array $response API response
     * @return string Message
     */
    private function extract_message($response) {
        // Get the last message from the response
        $messages = $response['messages'] ?? [];
        
        if (!empty($messages)) {
            $last_message = end($messages);
            return $last_message['content'] ?? 'No message content';
        }
        
        return 'No response message';
    }
    
    /**
     * Send request to OpenAI API
     *
     * @param string $endpoint API endpoint
     * @param array $data Request data
     * @return array Response data
     */
    private function send_request($endpoint, $data) {
        $url = 'https://api.openai.com/v1/' . $endpoint;
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        ];
        
        $response = wp_remote_post($url, [
            'headers' => $headers,
            'body' => json_encode($data),
            'timeout' => 60
        ]);
        
        if (is_wp_error($response)) {
            throw new Exception('API request failed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Failed to parse API response');
        }
        
        if (isset($result['error'])) {
            throw new Exception('API error: ' . $result['error']['message']);
        }
        
        return $result;
    }
}
```

## Risk Assessment and Mitigation

| Risk | Impact | Likelihood | Mitigation |
|------|--------|------------|------------|
| OpenAI API Changes | High | Medium | Create abstraction layer for API interaction; monitor for updates |
| Security Vulnerabilities | High | Medium | Implement strict validation; limit permissions; sandbox execution |
| Performance Issues | Medium | Medium | Optimize API usage; implement caching; use background processing |
| Integration Conflicts | Medium | Low | Thorough testing; maintain compatibility with existing code |
| Cost Overruns | Medium | Medium | Implement usage limits; optimize API requests; track consumption |

## Success Criteria

1. **Functionality**: All agents perform their intended tasks correctly
2. **Performance**: Response times under 5 seconds for simple tasks
3. **Reliability**: 99% success rate for agent tasks
4. **Security**: No security vulnerabilities introduced
5. **Usability**: Positive user feedback on interface and functionality

## Conclusion

This implementation plan provides a comprehensive roadmap for integrating the OpenAI Agents SDK into the MemberPress AI Assistant plugin. By following a phased approach, we can build a robust, extensible agent system that enhances the plugin's capabilities while ensuring security, performance, and reliability.