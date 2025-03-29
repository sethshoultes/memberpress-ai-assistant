# MemberPress AI Assistant: Agent System Technical Implementation Guide

## Architecture Overview

The Agent System will be built using a modular, extensible architecture following object-oriented principles. This document outlines the technical implementation details for developers.

```
+---------------------+       +-------------------+
|                     |       |                   |
|  User Interface     |<----->|  API Layer        |
|  (Chat Interface)   |       |                   |
|                     |       +--------+----------+
+---------------------+                |
                                       v
+---------------------+       +--------+----------+       +-------------------+
|                     |       |                   |       |                   |
|  Memory System      |<----->|  Agent            |<----->|  Tool Registry    |
|  (Context Storage)  |       |  Orchestrator     |       |                   |
|                     |       |                   |       +---------+---------+
+---------------------+       +-------------------+                 |
                                       |                           |
                                       v                           v
                              +--------+----------+       +--------+---------+
                              |                   |       |                  |
                              |  Specialized      |       |  Tool            |
                              |  Agents           |       |  Implementations |
                              |                   |       |                  |
                              +-------------------+       +------------------+
```

## Directory Structure

```
memberpress-ai-assistant/
├── includes/
│   ├── agents/
│   │   ├── class-mpai-agent-orchestrator.php
│   │   ├── class-mpai-base-agent.php
│   │   ├── specialized/
│   │   │   ├── class-mpai-content-agent.php
│   │   │   ├── class-mpai-system-agent.php
│   │   │   ├── class-mpai-security-agent.php
│   │   │   ├── class-mpai-analytics-agent.php
│   │   │   └── class-mpai-memberpress-agent.php
│   │   └── interfaces/
│   │       └── interface-mpai-agent.php
│   ├── tools/
│   │   ├── class-mpai-tool-registry.php
│   │   ├── class-mpai-base-tool.php
│   │   └── implementations/
│   │       ├── class-mpai-wpcli-tool.php
│   │       ├── class-mpai-content-tool.php
│   │       ├── class-mpai-file-system-tool.php
│   │       ├── class-mpai-database-tool.php
│   │       └── class-mpai-api-tool.php
│   ├── memory/
│   │   ├── class-mpai-memory-manager.php
│   │   ├── class-mpai-context-store.php
│   │   └── class-mpai-user-preferences.php
│   ├── api/
│   │   ├── class-mpai-rest-controller.php
│   │   └── class-mpai-agent-endpoints.php
│   └── utils/
│       ├── class-mpai-security.php
│       ├── class-mpai-logger.php
│       └── class-mpai-task-scheduler.php
├── admin/
│   ├── class-mpai-agent-settings.php
│   └── views/
│       ├── agent-dashboard.php
│       └── agent-settings.php
├── assets/
│   ├── js/
│   │   ├── agent-interface.js
│   │   └── agent-settings.js
│   └── css/
│       ├── agent-interface.css
│       └── agent-dashboard.css
└── templates/
    └── agent-result-templates/
        ├── content-result.php
        ├── system-result.php
        └── security-result.php
```

## Core Classes

### Agent Orchestrator

```php
/**
 * Main orchestrator for the agent system
 */
class MPAI_Agent_Orchestrator {
    /**
     * Registry of available agents
     * @var array
     */
    private $agents = [];
    
    /**
     * Memory manager instance
     * @var MPAI_Memory_Manager
     */
    private $memory_manager;
    
    /**
     * Tool registry instance
     * @var MPAI_Tool_Registry
     */
    private $tool_registry;
    
    /**
     * Logger instance
     * @var MPAI_Logger
     */
    private $logger;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->memory_manager = new MPAI_Memory_Manager();
        $this->tool_registry = new MPAI_Tool_Registry();
        $this->logger = new MPAI_Logger();
        
        // Register core agents
        $this->register_core_agents();
    }
    
    /**
     * Register a new agent
     *
     * @param string $agent_id Unique identifier for the agent
     * @param MPAI_Base_Agent $agent_instance Instance of the agent
     * @return bool Success status
     */
    public function register_agent($agent_id, $agent_instance) {
        if (isset($this->agents[$agent_id])) {
            $this->logger->warning("Agent with ID {$agent_id} already registered");
            return false;
        }
        
        $this->agents[$agent_id] = $agent_instance;
        return true;
    }
    
    /**
     * Process a user request
     *
     * @param string $user_message The natural language request
     * @param int $user_id User ID
     * @return array Response data
     */
    public function process_request($user_message, $user_id = null) {
        try {
            // Get user context
            $user_context = $this->memory_manager->get_context($user_id);
            
            // Analyze intent
            $intent_data = $this->determine_intent($user_message, $user_context);
            
            // Find appropriate agent(s)
            $primary_agent_id = $intent_data['primary_agent'];
            
            // Dispatch to primary agent
            if (!isset($this->agents[$primary_agent_id])) {
                throw new Exception("Agent {$primary_agent_id} not found");
            }
            
            $result = $this->agents[$primary_agent_id]->process_request($intent_data, $user_context);
            
            // Update memory with results
            $this->memory_manager->update_context($user_id, $intent_data, $result);
            
            // Log the successful completion
            $this->logger->info("Successfully processed request for agent {$primary_agent_id}", [
                'user_id' => $user_id,
                'intent' => $intent_data['intent'],
            ]);
            
            return [
                'success' => true,
                'message' => $result['message'],
                'data' => $result['data'],
                'agent' => $primary_agent_id,
            ];
        } catch (Exception $e) {
            $this->logger->error("Error processing request: " . $e->getMessage(), [
                'user_message' => $user_message,
                'user_id' => $user_id,
                'exception' => $e,
            ]);
            
            return [
                'success' => false,
                'message' => "Sorry, I couldn't process that request: " . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Determine the user's intent and which agent should handle it
     *
     * @param string $message User message
     * @param array $context User context
     * @return array Intent data including primary agent
     */
    private function determine_intent($message, $context = []) {
        // Use OpenAI to analyze the intent
        $openai = new MPAI_OpenAI();
        
        $system_prompt = "You are an intent classifier for an AI assistant for WordPress with MemberPress. ";
        $system_prompt .= "Categorize the user's request into one of these categories: ";
        $system_prompt .= "content_creation, system_management, security_audit, analytics, memberpress_management, general_question.";
        
        $user_prompt = $message;
        
        if (!empty($context)) {
            $user_prompt .= "\n\nContext from previous interactions: " . json_encode($context);
        }
        
        $messages = [
            ['role' => 'system', 'content' => $system_prompt],
            ['role' => 'user', 'content' => $user_prompt]
        ];
        
        $response = $openai->generate_chat_completion($messages);
        
        // Parse the response to get the intent category
        $intent_category = $this->parse_intent_category($response);
        
        // Map intent category to primary agent
        $agent_map = [
            'content_creation' => 'content',
            'system_management' => 'system',
            'security_audit' => 'security',
            'analytics' => 'analytics',
            'memberpress_management' => 'memberpress',
            'general_question' => 'content', // Default to content agent for general questions
        ];
        
        $primary_agent = isset($agent_map[$intent_category]) ? $agent_map[$intent_category] : 'content';
        
        return [
            'intent' => $intent_category,
            'primary_agent' => $primary_agent,
            'original_message' => $message,
            'context' => $context,
            'timestamp' => time(),
        ];
    }
    
    /**
     * Parse the intent category from the OpenAI response
     *
     * @param string $response
     * @return string Intent category
     */
    private function parse_intent_category($response) {
        $categories = [
            'content_creation',
            'system_management',
            'security_audit',
            'analytics',
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
     * Register all core agents
     */
    private function register_core_agents() {
        // Content agent
        $content_agent = new MPAI_Content_Agent($this->tool_registry, $this->logger);
        $this->register_agent('content', $content_agent);
        
        // System agent
        $system_agent = new MPAI_System_Agent($this->tool_registry, $this->logger);
        $this->register_agent('system', $system_agent);
        
        // Security agent
        $security_agent = new MPAI_Security_Agent($this->tool_registry, $this->logger);
        $this->register_agent('security', $security_agent);
        
        // Analytics agent
        $analytics_agent = new MPAI_Analytics_Agent($this->tool_registry, $this->logger);
        $this->register_agent('analytics', $analytics_agent);
        
        // MemberPress agent
        $memberpress_agent = new MPAI_MemberPress_Agent($this->tool_registry, $this->logger);
        $this->register_agent('memberpress', $memberpress_agent);
    }
    
    /**
     * Get list of available agents and their capabilities
     *
     * @return array Agent information
     */
    public function get_available_agents() {
        $result = [];
        
        foreach ($this->agents as $agent_id => $agent) {
            $result[$agent_id] = [
                'name' => $agent->get_name(),
                'description' => $agent->get_description(),
                'capabilities' => $agent->get_capabilities(),
            ];
        }
        
        return $result;
    }
}
```

### Base Agent Interface

```php
/**
 * Interface that all agents must implement
 */
interface MPAI_Agent {
    /**
     * Process a user request
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
     * @return array List of capabilities
     */
    public function get_capabilities();
}
```

### Base Agent Abstract Class

```php
/**
 * Base abstract class for all agents
 */
abstract class MPAI_Base_Agent implements MPAI_Agent {
    /**
     * Unique identifier
     * @var string
     */
    protected $id;
    
    /**
     * Display name
     * @var string
     */
    protected $name;
    
    /**
     * Description
     * @var string
     */
    protected $description;
    
    /**
     * List of capabilities
     * @var array
     */
    protected $capabilities = [];
    
    /**
     * Tool registry instance
     * @var MPAI_Tool_Registry
     */
    protected $tool_registry;
    
    /**
     * Logger instance
     * @var MPAI_Logger
     */
    protected $logger;
    
    /**
     * Constructor
     *
     * @param MPAI_Tool_Registry $tool_registry Tool registry
     * @param MPAI_Logger $logger Logger
     */
    public function __construct($tool_registry, $logger) {
        $this->tool_registry = $tool_registry;
        $this->logger = $logger;
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
     * @return array List of capabilities
     */
    public function get_capabilities() {
        return $this->capabilities;
    }
    
    /**
     * Execute a tool with parameters
     *
     * @param string $tool_id Tool identifier
     * @param array $parameters Tool parameters
     * @return mixed Tool result
     * @throws Exception If tool not found or execution fails
     */
    protected function execute_tool($tool_id, $parameters) {
        $tool = $this->tool_registry->get_tool($tool_id);
        
        if (!$tool) {
            throw new Exception("Tool {$tool_id} not found");
        }
        
        $this->logger->info("Executing tool {$tool_id}", [
            'agent' => $this->id,
            'parameters' => $parameters,
        ]);
        
        try {
            return $tool->execute($parameters);
        } catch (Exception $e) {
            $this->logger->error("Tool execution failed: " . $e->getMessage(), [
                'tool' => $tool_id,
                'parameters' => $parameters,
                'exception' => $e,
            ]);
            throw $e;
        }
    }
    
    /**
     * Plan sequence of actions based on intent
     *
     * @param array $intent_data Intent data
     * @return array Action plan
     */
    protected function plan_actions($intent_data) {
        // This method would use the OpenAI API to create a plan
        // based on the intent data and available tools
        
        $openai = new MPAI_OpenAI();
        
        // Build a system prompt that describes the available tools
        $system_prompt = "You are an AI assistant planning actions for: {$this->name}.\n\n";
        $system_prompt .= "Available tools:\n";
        
        $tools = $this->tool_registry->get_available_tools();
        foreach ($tools as $tool_id => $tool) {
            $system_prompt .= "- {$tool_id}: {$tool->get_description()}\n";
        }
        
        $system_prompt .= "\nCreate a JSON plan with a sequence of actions to accomplish the user's request.";
        $system_prompt .= "\nEach action should have: tool_id, parameters, description";
        
        $user_prompt = "User request: {$intent_data['original_message']}";
        
        $messages = [
            ['role' => 'system', 'content' => $system_prompt],
            ['role' => 'user', 'content' => $user_prompt]
        ];
        
        $response = $openai->generate_chat_completion($messages);
        
        // Extract and parse the JSON plan
        $plan = $this->extract_json_plan($response);
        
        return $plan;
    }
    
    /**
     * Extract JSON plan from text response
     *
     * @param string $response Text response containing JSON
     * @return array Parsed plan
     */
    private function extract_json_plan($response) {
        // Find JSON in the response
        preg_match('/```(?:json)?\s*([\s\S]*?)```/', $response, $matches);
        
        if (!empty($matches[1])) {
            return json_decode($matches[1], true);
        }
        
        // Try extracting without code blocks
        preg_match('/(\{[\s\S]*\})/', $response, $matches);
        
        if (!empty($matches[1])) {
            return json_decode($matches[1], true);
        }
        
        // Fallback to simple plan
        return [
            'actions' => [
                [
                    'description' => 'Process the request directly',
                    'tool_id' => 'openai',
                    'parameters' => [
                        'prompt' => $response
                    ]
                ]
            ]
        ];
    }
}
```

### Example Specialized Agent (Content Agent)

```php
/**
 * Content Agent for handling content creation and management
 */
class MPAI_Content_Agent extends MPAI_Base_Agent {
    /**
     * Constructor
     *
     * @param MPAI_Tool_Registry $tool_registry Tool registry
     * @param MPAI_Logger $logger Logger
     */
    public function __construct($tool_registry, $logger) {
        parent::__construct($tool_registry, $logger);
        
        $this->id = 'content';
        $this->name = 'Content Agent';
        $this->description = 'Creates and manages blog posts, pages, and other content';
        $this->capabilities = [
            'create_blog_post' => 'Create a new blog post',
            'create_page' => 'Create a new page',
            'edit_content' => 'Edit existing content',
            'optimize_content' => 'Optimize content for SEO',
            'suggest_topics' => 'Suggest content topics',
        ];
    }
    
    /**
     * Process a content request
     *
     * @param array $intent_data Intent data from orchestrator
     * @param array $context User context
     * @return array Response data
     */
    public function process_request($intent_data, $context = []) {
        $this->logger->info("Content agent processing request", [
            'intent' => $intent_data['intent'],
            'message' => $intent_data['original_message'],
        ]);
        
        // Plan the actions
        $plan = $this->plan_actions($intent_data);
        
        // Execute each action in the plan
        $results = [];
        $overall_status = true;
        
        foreach ($plan['actions'] as $action) {
            try {
                $tool_id = $action['tool_id'];
                $parameters = $action['parameters'];
                
                $result = $this->execute_tool($tool_id, $parameters);
                
                $results[] = [
                    'description' => $action['description'],
                    'status' => 'success',
                    'result' => $result,
                ];
            } catch (Exception $e) {
                $overall_status = false;
                $results[] = [
                    'description' => $action['description'],
                    'status' => 'error',
                    'error' => $e->getMessage(),
                ];
                
                $this->logger->error("Action failed: " . $e->getMessage(), [
                    'action' => $action,
                    'exception' => $e,
                ]);
                
                // Break on critical errors
                if (isset($action['critical']) && $action['critical']) {
                    break;
                }
            }
        }
        
        // Generate a human-readable summary of the results
        $summary = $this->generate_summary($results, $intent_data);
        
        return [
            'success' => $overall_status,
            'message' => $summary,
            'data' => [
                'plan' => $plan,
                'results' => $results,
            ],
        ];
    }
    
    /**
     * Generate a blog post
     *
     * @param string $title Post title
     * @param array $keywords Keywords to target
     * @param int $length Approximate length in words
     * @return array Post data
     */
    public function generate_blog_post($title, $keywords, $length = 1000) {
        $this->logger->info("Generating blog post", [
            'title' => $title,
            'keywords' => $keywords,
            'length' => $length,
        ]);
        
        // Use OpenAI to generate the post content
        $openai_tool = $this->tool_registry->get_tool('openai');
        
        $system_prompt = "You are a blog post writer for WordPress with MemberPress. ";
        $system_prompt .= "Create a well-structured, engaging blog post that incorporates the given keywords naturally. ";
        $system_prompt .= "Format the post with WordPress-compatible HTML including h2, h3, p, ul, ol tags as appropriate.";
        
        $user_prompt = "Title: {$title}\n";
        $user_prompt .= "Keywords: " . implode(', ', $keywords) . "\n";
        $user_prompt .= "Target length: {$length} words\n";
        
        $messages = [
            ['role' => 'system', 'content' => $system_prompt],
            ['role' => 'user', 'content' => $user_prompt]
        ];
        
        $content = $openai_tool->execute([
            'messages' => $messages,
            'temperature' => 0.7,
        ]);
        
        // Use WordPress API to create the post
        $wp_tool = $this->tool_registry->get_tool('wordpress');
        
        $post_data = [
            'post_title' => $title,
            'post_content' => $content,
            'post_status' => 'draft',
            'post_type' => 'post',
        ];
        
        $post_id = $wp_tool->execute([
            'action' => 'create_post',
            'data' => $post_data,
        ]);
        
        // Set keywords as tags
        if (!empty($keywords)) {
            $wp_tool->execute([
                'action' => 'set_tags',
                'post_id' => $post_id,
                'tags' => $keywords,
            ]);
        }
        
        return [
            'post_id' => $post_id,
            'title' => $title,
            'content' => $content,
            'status' => 'draft',
            'edit_url' => admin_url("post.php?post={$post_id}&action=edit"),
        ];
    }
    
    /**
     * Generate a summary of the actions taken
     *
     * @param array $results Results from actions
     * @param array $intent_data Original intent data
     * @return string Human-readable summary
     */
    private function generate_summary($results, $intent_data) {
        // Use OpenAI to generate a natural language summary
        $openai_tool = $this->tool_registry->get_tool('openai');
        
        $system_prompt = "You are a helpful assistant summarizing actions taken by an AI content agent. ";
        $system_prompt .= "Create a concise, human-readable summary of the actions taken and their results. ";
        $system_prompt .= "Be specific about what was accomplished.";
        
        $user_prompt = "Original request: {$intent_data['original_message']}\n\n";
        $user_prompt .= "Actions and results:\n" . json_encode($results, JSON_PRETTY_PRINT);
        
        $messages = [
            ['role' => 'system', 'content' => $system_prompt],
            ['role' => 'user', 'content' => $user_prompt]
        ];
        
        $summary = $openai_tool->execute([
            'messages' => $messages,
            'temperature' => 0.7,
        ]);
        
        return $summary;
    }
}
```

### Tool Registry

```php
/**
 * Registry for all available tools
 */
class MPAI_Tool_Registry {
    /**
     * Registered tools
     * @var array
     */
    private $tools = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->register_core_tools();
    }
    
    /**
     * Register a new tool
     *
     * @param string $tool_id Unique tool identifier
     * @param MPAI_Base_Tool $tool Tool instance
     * @return bool Success status
     */
    public function register_tool($tool_id, $tool) {
        if (isset($this->tools[$tool_id])) {
            return false;
        }
        
        $this->tools[$tool_id] = $tool;
        return true;
    }
    
    /**
     * Get a tool by ID
     *
     * @param string $tool_id Tool identifier
     * @return MPAI_Base_Tool|null Tool instance or null if not found
     */
    public function get_tool($tool_id) {
        return isset($this->tools[$tool_id]) ? $this->tools[$tool_id] : null;
    }
    
    /**
     * Get all available tools
     *
     * @return array All registered tools
     */
    public function get_available_tools() {
        return $this->tools;
    }
    
    /**
     * Register all core tools
     */
    private function register_core_tools() {
        // OpenAI API tool
        $openai_tool = new MPAI_OpenAI_Tool();
        $this->register_tool('openai', $openai_tool);
        
        // WordPress API tool
        $wp_tool = new MPAI_WordPress_Tool();
        $this->register_tool('wordpress', $wp_tool);
        
        // WP-CLI tool
        $wpcli_tool = new MPAI_WP_CLI_Tool();
        $this->register_tool('wpcli', $wpcli_tool);
        
        // File system tool
        $fs_tool = new MPAI_FileSystem_Tool();
        $this->register_tool('filesystem', $fs_tool);
        
        // Database tool
        $db_tool = new MPAI_Database_Tool();
        $this->register_tool('database', $db_tool);
        
        // MemberPress API tool
        $memberpress_tool = new MPAI_MemberPress_Tool();
        $this->register_tool('memberpress', $memberpress_tool);
    }
}
```

### WP-CLI Tool Example

```php
/**
 * Tool for executing WP-CLI commands
 */
class MPAI_WP_CLI_Tool extends MPAI_Base_Tool {
    /**
     * Allowlist of permitted command prefixes
     * @var array
     */
    private $allowed_command_prefixes = [
        'wp plugin list',
        'wp plugin update',
        'wp theme list',
        'wp theme update',
        'wp post list',
        'wp post get',
        'wp user list',
        'wp user get',
        'wp option get',
        'wp core version',
        'wp core verify-checksums',
        'wp mepr',  // MemberPress commands
    ];
    
    /**
     * Execution timeout in seconds
     * @var int
     */
    private $execution_timeout = 30;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->name = 'WP-CLI Tool';
        $this->description = 'Executes WordPress CLI commands securely';
    }
    
    /**
     * Execute a WP-CLI command
     *
     * @param array $parameters Parameters for the tool
     * @return mixed Command result
     * @throws Exception If command validation fails or execution error
     */
    public function execute($parameters) {
        if (!isset($parameters['command'])) {
            throw new Exception('Command parameter is required');
        }
        
        $command = $parameters['command'];
        
        // Validate the command
        if (!$this->validate_command($command)) {
            throw new Exception('Command validation failed: not in allowlist');
        }
        
        // Set execution timeout
        $timeout = isset($parameters['timeout']) ? 
            min((int)$parameters['timeout'], 60) : 
            $this->execution_timeout;
        
        // Build the command
        $wp_cli_command = $this->build_command($command);
        
        // Execute the command
        $output = [];
        $return_var = 0;
        $last_line = exec($wp_cli_command, $output, $return_var);
        
        if ($return_var !== 0) {
            throw new Exception('Command execution failed with code ' . $return_var . ': ' . implode("\n", $output));
        }
        
        // Parse the output based on requested format
        $format = isset($parameters['format']) ? $parameters['format'] : 'text';
        
        return $this->parse_output($output, $format);
    }
    
    /**
     * Validate that a command is allowed
     *
     * @param string $command WP-CLI command
     * @return bool Whether command is valid
     */
    private function validate_command($command) {
        // Sanitize the command
        $sanitized_command = $this->sanitize_command($command);
        
        // Check against allowlist
        foreach ($this->allowed_command_prefixes as $prefix) {
            if (strpos($sanitized_command, $prefix) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Sanitize a command to prevent injection
     *
     * @param string $command Command to sanitize
     * @return string Sanitized command
     */
    private function sanitize_command($command) {
        // Remove potentially dangerous characters
        $command = preg_replace('/[;&|><]/', '', $command);
        
        // Ensure command starts with 'wp '
        if (strpos($command, 'wp ') !== 0) {
            $command = 'wp ' . $command;
        }
        
        return trim($command);
    }
    
    /**
     * Build the full command with proper escaping
     *
     * @param string $command WP-CLI command
     * @return string Full bash command
     */
    private function build_command($command) {
        // Add --skip-plugins and --skip-themes for safety
        if (strpos($command, '--skip-plugins') === false) {
            $command .= ' --skip-plugins=all';
        }
        
        // Format as JSON for easier parsing if possible
        if (strpos($command, '--format=') === false) {
            $command .= ' --format=json';
        }
        
        // Escape the command
        $escaped_command = escapeshellcmd($command);
        
        // Add timeout
        $full_command = "timeout {$this->execution_timeout}s {$escaped_command}";
        
        return $full_command;
    }
    
    /**
     * Parse command output into usable format
     *
     * @param array $output Command output lines
     * @param string $format Desired output format
     * @return mixed Parsed output
     */
    private function parse_output($output, $format) {
        $raw_output = implode("\n", $output);
        
        switch ($format) {
            case 'json':
                return json_decode($raw_output, true);
                
            case 'array':
                return $output;
                
            case 'text':
            default:
                return $raw_output;
        }
    }
    
    /**
     * Get suggestions for WP-CLI commands based on task
     *
     * @param string $task Description of the task
     * @return array Suggested commands
     */
    public function get_command_suggestions($task) {
        // Generate command suggestions using OpenAI
        // This would be implemented with a call to the OpenAI API
        
        $openai_tool = new MPAI_OpenAI_Tool();
        
        $system_prompt = "You are a WordPress CLI expert. ";
        $system_prompt .= "Suggest appropriate WP-CLI commands to accomplish the described task. ";
        $system_prompt .= "Only suggest commands from this allowed list:\n";
        $system_prompt .= implode("\n", $this->allowed_command_prefixes);
        
        $user_prompt = "Task: {$task}\n\n";
        $user_prompt .= "Respond with a JSON array of suggested commands with descriptions.";
        
        $messages = [
            ['role' => 'system', 'content' => $system_prompt],
            ['role' => 'user', 'content' => $user_prompt]
        ];
        
        $response = $openai_tool->execute([
            'messages' => $messages,
            'temperature' => 0.3,
        ]);
        
        // Extract the JSON array from the response
        preg_match('/```(?:json)?\s*([\s\S]*?)```/', $response, $matches);
        
        if (!empty($matches[1])) {
            return json_decode($matches[1], true);
        }
        
        // Try extracting without code blocks
        preg_match('/(\[[\s\S]*\])/', $response, $matches);
        
        if (!empty($matches[1])) {
            return json_decode($matches[1], true);
        }
        
        // Fallback to empty array
        return [];
    }
}
```

## Database Schema

The agent system will require several new database tables:

### Agent Memory Tables

```sql
-- Conversation history table
CREATE TABLE {$wpdb->prefix}mpai_conversations (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    user_id bigint(20) NOT NULL,
    conversation_id varchar(36) NOT NULL,
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY user_id (user_id),
    KEY conversation_id (conversation_id)
);

-- Message history table
CREATE TABLE {$wpdb->prefix}mpai_messages (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    conversation_id varchar(36) NOT NULL,
    message text NOT NULL,
    response text NOT NULL,
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY conversation_id (conversation_id)
);

-- Agent task history table
CREATE TABLE {$wpdb->prefix}mpai_agent_tasks (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    user_id bigint(20) NOT NULL,
    agent_id varchar(50) NOT NULL,
    task_id varchar(36) NOT NULL,
    status varchar(20) NOT NULL,
    request text NOT NULL,
    result longtext DEFAULT NULL,
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at datetime DEFAULT NULL,
    PRIMARY KEY (id),
    KEY user_id (user_id),
    KEY agent_id (agent_id),
    KEY task_id (task_id)
);

-- User preferences table
CREATE TABLE {$wpdb->prefix}mpai_user_preferences (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    user_id bigint(20) NOT NULL,
    preference_key varchar(50) NOT NULL,
    preference_value longtext NOT NULL,
    updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY user_pref (user_id, preference_key)
);
```

## REST API Endpoints

### Agent Endpoints

```php
/**
 * REST API controller for agent endpoints
 */
class MPAI_Agent_Endpoints extends WP_REST_Controller {
    /**
     * Agent orchestrator instance
     * @var MPAI_Agent_Orchestrator
     */
    private $orchestrator;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->namespace = 'mpai/v1';
        $this->rest_base = 'agent';
        $this->orchestrator = new MPAI_Agent_Orchestrator();
    }
    
    /**
     * Register routes
     */
    public function register_routes() {
        // Process a request through an agent
        register_rest_route($this->namespace, '/' . $this->rest_base . '/process', [
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'process_request'],
                'permission_callback' => [$this, 'check_permissions'],
                'args' => [
                    'message' => [
                        'required' => true,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'agent_id' => [
                        'required' => false,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
            ],
        ]);
        
        // Get task status
        register_rest_route($this->namespace, '/' . $this->rest_base . '/task/(?P<task_id>[a-zA-Z0-9-]+)', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_task_status'],
                'permission_callback' => [$this, 'check_permissions'],
                'args' => [
                    'task_id' => [
                        'required' => true,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
            ],
        ]);
        
        // Get available agents
        register_rest_route($this->namespace, '/' . $this->rest_base . '/available', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_available_agents'],
                'permission_callback' => [$this, 'check_permissions'],
            ],
        ]);
        
        // Get agent history
        register_rest_route($this->namespace, '/' . $this->rest_base . '/history', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_agent_history'],
                'permission_callback' => [$this, 'check_permissions'],
                'args' => [
                    'agent_id' => [
                        'required' => false,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'limit' => [
                        'required' => false,
                        'type' => 'integer',
                        'default' => 10,
                        'sanitize_callback' => 'absint',
                    ],
                ],
            ],
        ]);
    }
    
    /**
     * Check permissions for API access
     *
     * @param WP_REST_Request $request Request object
     * @return bool Whether user has permission
     */
    public function check_permissions($request) {
        return current_user_can('edit_posts');
    }
    
    /**
     * Process a request through an agent
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function process_request($request) {
        $message = $request->get_param('message');
        $agent_id = $request->get_param('agent_id');
        $user_id = get_current_user_id();
        
        // Generate a unique task ID
        $task_id = wp_generate_uuid4();
        
        // If this is a long-running task, process it asynchronously
        if ($this->is_long_running_task($message)) {
            // Schedule the task
            $this->schedule_agent_task($task_id, $user_id, $agent_id, $message);
            
            return rest_ensure_response([
                'success' => true,
                'message' => 'Task scheduled for processing',
                'task_id' => $task_id,
                'status' => 'pending',
            ]);
        }
        
        // For simple tasks, process immediately
        $result = $this->orchestrator->process_request($message, $user_id);
        
        // Save the completed task
        $this->save_completed_task($task_id, $user_id, $result['agent'], $message, $result);
        
        return rest_ensure_response($result);
    }
    
    /**
     * Check if a task is likely to be long-running
     *
     * @param string $message User message
     * @return bool Whether task is long-running
     */
    private function is_long_running_task($message) {
        // Keywords that suggest a long-running task
        $long_task_keywords = [
            'update all plugins',
            'security audit',
            'analyze',
            'generate report',
            'create blog posts',
            'backup',
        ];
        
        foreach ($long_task_keywords as $keyword) {
            if (stripos($message, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Schedule an agent task for background processing
     *
     * @param string $task_id Task ID
     * @param int $user_id User ID
     * @param string $agent_id Agent ID
     * @param string $message User message
     */
    private function schedule_agent_task($task_id, $user_id, $agent_id, $message) {
        global $wpdb;
        
        // Save task to database
        $wpdb->insert(
            $wpdb->prefix . 'mpai_agent_tasks',
            [
                'user_id' => $user_id,
                'agent_id' => $agent_id ?: 'auto',
                'task_id' => $task_id,
                'status' => 'pending',
                'request' => $message,
                'created_at' => current_time('mysql'),
            ]
        );
        
        // Schedule the task
        wp_schedule_single_event(
            time(),
            'mpai_process_agent_task',
            [$task_id, $user_id, $agent_id, $message]
        );
    }
    
    /**
     * Save a completed task
     *
     * @param string $task_id Task ID
     * @param int $user_id User ID
     * @param string $agent_id Agent ID
     * @param string $message User message
     * @param array $result Task result
     */
    private function save_completed_task($task_id, $user_id, $agent_id, $message, $result) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'mpai_agent_tasks',
            [
                'user_id' => $user_id,
                'agent_id' => $agent_id,
                'task_id' => $task_id,
                'status' => 'completed',
                'request' => $message,
                'result' => json_encode($result),
                'created_at' => current_time('mysql'),
                'completed_at' => current_time('mysql'),
            ]
        );
    }
    
    /**
     * Get task status
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function get_task_status($request) {
        global $wpdb;
        
        $task_id = $request->get_param('task_id');
        $user_id = get_current_user_id();
        
        $task = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}mpai_agent_tasks WHERE task_id = %s AND user_id = %d",
                $task_id,
                $user_id
            ),
            ARRAY_A
        );
        
        if (!$task) {
            return new WP_Error(
                'task_not_found',
                'Task not found',
                ['status' => 404]
            );
        }
        
        $response = [
            'task_id' => $task['task_id'],
            'agent_id' => $task['agent_id'],
            'status' => $task['status'],
            'created_at' => $task['created_at'],
            'completed_at' => $task['completed_at'],
        ];
        
        if ($task['status'] === 'completed') {
            $response['result'] = json_decode($task['result'], true);
        }
        
        return rest_ensure_response($response);
    }
    
    /**
     * Get available agents
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function get_available_agents($request) {
        $agents = $this->orchestrator->get_available_agents();
        
        return rest_ensure_response($agents);
    }
    
    /**
     * Get agent history
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function get_agent_history($request) {
        global $wpdb;
        
        $agent_id = $request->get_param('agent_id');
        $limit = $request->get_param('limit');
        $user_id = get_current_user_id();
        
        $query = "SELECT * FROM {$wpdb->prefix}mpai_agent_tasks WHERE user_id = %d";
        $params = [$user_id];
        
        if ($agent_id) {
            $query .= " AND agent_id = %s";
            $params[] = $agent_id;
        }
        
        $query .= " ORDER BY created_at DESC LIMIT %d";
        $params[] = $limit;
        
        $tasks = $wpdb->get_results(
            $wpdb->prepare($query, $params),
            ARRAY_A
        );
        
        $response = [];
        
        foreach ($tasks as $task) {
            $task_data = [
                'task_id' => $task['task_id'],
                'agent_id' => $task['agent_id'],
                'status' => $task['status'],
                'request' => $task['request'],
                'created_at' => $task['created_at'],
                'completed_at' => $task['completed_at'],
            ];
            
            if ($task['status'] === 'completed') {
                $task_data['result'] = json_decode($task['result'], true);
            }
            
            $response[] = $task_data;
        }
        
        return rest_ensure_response($response);
    }
}
```

## Implementation Timeline

### Week 1-2: Foundation
- Set up basic architecture
- Create core interfaces and abstract classes
- Implement tool registry
- Build initial WP-CLI tool

### Week 3-4: Orchestrator and First Agent
- Implement agent orchestrator
- Create content agent
- Build basic chat interface integration
- Set up REST API endpoints

### Week 5-6: Additional Agents
- Implement system agent
- Build security agent
- Add memory system
- Create database tables

### Week 7-8: User Interface
- Build agent dashboard
- Create settings interface
- Add task monitoring UI
- Implement result display templates

### Week 9-10: Advanced Features
- Add background task processing
- Implement MemberPress-specific agent
- Create analytics agent
- Add multi-agent workflows

### Week 11-12: Testing and Refinement
- Comprehensive testing
- Performance optimization
- Security review
- Documentation