<?php
/**
 * Content Agent Class
 *
 * Specialized agent for content creation and management.
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Content Agent for MemberPress AI Assistant
 */
class MPAI_Content_Agent extends MPAI_Base_Agent {
    /**
     * Constructor
     *
     * @param MPAI_Tool_Registry $tool_registry Tool registry
     */
    public function __construct($tool_registry) {
        parent::__construct($tool_registry);
        
        $this->name = 'Content Agent';
        $this->description = 'Creates and manages WordPress content';
        $this->capabilities = [
            'create_blog_post' => 'Create a new blog post',
            'create_page' => 'Create a new page',
            'edit_content' => 'Edit existing content',
            'optimize_content' => 'Optimize content for SEO',
            'suggest_topics' => 'Suggest content topics',
        ];
    }
    
    /**
     * Process a user request
     *
     * @param array $intent_data Intent data from orchestrator
     * @param array $context User context
     * @return array Response data
     */
    public function process_request($intent_data, $context = []) {
        error_log('MPAI: Content agent processing request: ' . $intent_data['message']);
        
        try {
            // For now, we'll use a direct approach until we implement all tools
            // In the future, we'll create an action plan using create_action_plan()
            
            // Create system prompt
            $system_prompt = "You are an AI assistant specialized in WordPress content creation and management. ";
            $system_prompt .= "You help with creating blog posts, pages, and optimizing content for MemberPress websites. ";
            $system_prompt .= "Provide helpful, detailed responses about content creation and management.";
            
            // Add context if available
            if (!empty($context)) {
                $system_prompt .= "\n\nPrevious conversation context:\n";
                
                if (isset($context['conversation_history']) && is_array($context['conversation_history'])) {
                    $history = array_slice($context['conversation_history'], -6); // Last 3 exchanges
                    
                    foreach ($history as $entry) {
                        $system_prompt .= "{$entry['role']}: {$entry['content']}\n";
                    }
                }
            }
            
            // Create messages for OpenAI
            $messages = [
                ['role' => 'system', 'content' => $system_prompt],
                ['role' => 'user', 'content' => $intent_data['message']]
            ];
            
            // Use OpenAI tool to generate a response
            $openai_tool = $this->tool_registry->get_tool('openai');
            
            if (!$openai_tool) {
                // Fallback to direct OpenAI call if tool not found
                $response = $this->openai->generate_chat_completion($messages);
            } else {
                // Use the tool
                $response = $openai_tool->execute(['messages' => $messages]);
            }
            
            // Return formatted response
            return [
                'success' => true,
                'message' => $response,
                'data' => [
                    'agent' => 'content',
                    'intent' => $intent_data['intent'],
                    'context' => [] // We'll add context data in future implementation
                ]
            ];
        } catch (Exception $e) {
            error_log('MPAI: Error in Content Agent: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error processing content request: ' . $e->getMessage(),
                'data' => [
                    'agent' => 'content',
                    'error' => $e->getMessage()
                ]
            ];
        }
    }
    
    /**
     * Generate a blog post
     *
     * @param string $title Post title
     * @param array $keywords Keywords to target
     * @param int $length Approximate length in words
     * @return array Post data
     */
    public function generate_blog_post($title, $keywords = [], $length = 1000) {
        // This is a placeholder implementation
        // In the real implementation, we would interact with WordPress API
        
        $openai_tool = $this->tool_registry->get_tool('openai');
        
        $system_prompt = "You are a blog post writer for WordPress with MemberPress integration. ";
        $system_prompt .= "Create a well-structured, engaging blog post with the given title and keywords. ";
        $system_prompt .= "Format the post with WordPress-compatible HTML including h2, h3, p, ul, ol tags as appropriate.";
        
        $user_prompt = "Title: {$title}\n";
        $user_prompt .= "Keywords: " . implode(', ', $keywords) . "\n";
        $user_prompt .= "Target length: {$length} words\n";
        
        $messages = [
            ['role' => 'system', 'content' => $system_prompt],
            ['role' => 'user', 'content' => $user_prompt]
        ];
        
        // Generate content
        $content = $openai_tool->execute(['messages' => $messages]);
        
        // In a real implementation, we would create the post in WordPress
        // For now, just return the generated content
        
        return [
            'title' => $title,
            'content' => $content,
            'keywords' => $keywords
        ];
    }
}