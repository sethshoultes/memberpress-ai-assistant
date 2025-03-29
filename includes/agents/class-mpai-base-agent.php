<?php
/**
 * Base Agent Class
 *
 * Abstract class that all specialized agents should extend.
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Abstract base class for all MemberPress AI agents
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
     * @param MPAI_Tool_Registry $tool_registry Tool registry instance
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
     * @throws Exception If tool not found or execution fails
     */
    protected function execute_tool($tool_id, $parameters) {
        $tool = $this->tool_registry->get_tool($tool_id);
        
        if (!$tool) {
            throw new Exception("Tool not found: {$tool_id}");
        }
        
        return $tool->execute($parameters);
    }
    
    /**
     * Create action plan based on intent data
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
     * Extract JSON plan from OpenAI response
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
            error_log('MPAI: Failed to parse JSON plan: ' . json_last_error_msg());
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