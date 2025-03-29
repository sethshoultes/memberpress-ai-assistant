<?php
/**
 * Agent Orchestrator Class
 *
 * Central component that manages agent selection and orchestration.
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Main orchestrator for the MemberPress AI agent system
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
        
        // Initialize tool registry
        $this->tool_registry = new MPAI_Tool_Registry();
        
        // Initialize memory manager if class exists
        if (class_exists('MPAI_Memory_Manager')) {
            $this->memory_manager = new MPAI_Memory_Manager();
        }
        
        // Register core agents
        $this->register_core_agents();
    }
    
    /**
     * Register core agents
     */
    private function register_core_agents() {
        // Register Content Agent if class exists
        if (class_exists('MPAI_Content_Agent')) {
            $content_agent = new MPAI_Content_Agent($this->tool_registry);
            $this->register_agent('content', $content_agent);
        }
        
        // Register System Agent if class exists
        if (class_exists('MPAI_System_Agent')) {
            $system_agent = new MPAI_System_Agent($this->tool_registry);
            $this->register_agent('system', $system_agent);
        }
        
        // Register MemberPress Agent if class exists
        if (class_exists('MPAI_MemberPress_Agent')) {
            $memberpress_agent = new MPAI_MemberPress_Agent($this->tool_registry);
            $this->register_agent('memberpress', $memberpress_agent);
        }
        
        // Allow plugins to register additional agents
        do_action('mpai_register_agents', $this);
    }
    
    /**
     * Register an agent
     *
     * @param string $agent_id Agent identifier
     * @param MPAI_Agent $agent Agent instance
     * @return bool Success status
     */
    public function register_agent($agent_id, $agent) {
        if (isset($this->agents[$agent_id])) {
            error_log("MPAI: Agent with ID {$agent_id} already registered");
            return false;
        }
        
        $this->agents[$agent_id] = $agent;
        return true;
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
        if (is_null($user_id)) {
            $user_id = get_current_user_id();
        }
        
        try {
            error_log("MPAI: Processing request: {$message}");
            
            // Get user context from memory if available
            $context = [];
            if (isset($this->memory_manager)) {
                $context = $this->memory_manager->get_context($user_id);
            }
            
            // Determine intent and appropriate agent
            $intent_data = $this->determine_intent($message, $context);
            
            // Get the primary agent
            $agent_id = $intent_data['primary_agent'];
            
            if (!isset($this->agents[$agent_id])) {
                throw new Exception("Agent not found: {$agent_id}");
            }
            
            error_log("MPAI: Selected agent: {$agent_id}");
            
            // Process the request with the selected agent
            $result = $this->agents[$agent_id]->process_request($intent_data, $context);
            
            // Update context with result if memory manager exists
            if (isset($this->memory_manager)) {
                $this->memory_manager->update_context($user_id, $message, $result);
            }
            
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
                'message' => 'Error processing request: ' . $e->getMessage(),
                'error' => $e->getMessage()
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
        
        if (is_wp_error($response)) {
            error_log('MPAI: Error in intent classification: ' . $response->get_error_message());
            // Default to content agent if classification fails
            return [
                'intent' => 'general_question',
                'primary_agent' => 'content',
                'message' => $message,
                'timestamp' => time()
            ];
        }
        
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
        
        // Check if the selected agent is available, fall back to content if not
        if (!isset($this->agents[$primary_agent])) {
            error_log("MPAI: Selected agent '{$primary_agent}' not available, falling back to content");
            $primary_agent = 'content';
            
            // If content agent is also not available, use the first available agent
            if (!isset($this->agents[$primary_agent])) {
                $agent_ids = array_keys($this->agents);
                $primary_agent = !empty($agent_ids) ? $agent_ids[0] : '';
                
                if (empty($primary_agent)) {
                    throw new Exception("No agents available");
                }
            }
        }
        
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