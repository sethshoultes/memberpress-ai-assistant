<?php
/**
 * API Router Class
 *
 * Handles routing between different AI APIs (OpenAI and Anthropic)
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

class MPAI_API_Router {
    /**
     * OpenAI instance
     *
     * @var MPAI_OpenAI
     */
    private $openai;

    /**
     * Anthropic instance
     *
     * @var MPAI_Anthropic
     */
    private $anthropic;

    /**
     * Primary API to use
     *
     * @var string
     */
    private $primary_api;

    /**
     * Constructor
     */
    public function __construct() {
        // Load dependencies if not already loaded
        if (!class_exists('MPAI_OpenAI')) {
            require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-openai.php';
        }
        
        if (!class_exists('MPAI_Anthropic')) {
            require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-anthropic.php';
        }
        
        $this->openai = new MPAI_OpenAI();
        $this->anthropic = new MPAI_Anthropic();
        $this->primary_api = get_option('mpai_primary_api', 'openai');
    }

    /**
     * Process a request using the appropriate API
     *
     * @param array $messages The messages to process
     * @param array $tools The tools to make available to the APIs
     * @param array $context Additional context for processing
     * @return mixed The API response
     */
    public function process_request($messages, $tools = array(), $context = array()) {
        error_log('MPAI: Processing request using API Router with primary API: ' . $this->primary_api);
        
        // Determine if we should force a specific API for this request
        $force_api = null;
        if (isset($context['force_api']) && in_array($context['force_api'], array('openai', 'anthropic'))) {
            $force_api = $context['force_api'];
            error_log('MPAI: Forcing usage of ' . $force_api . ' API for this request');
        }
        
        // Default to primary API unless forced
        $primary = $force_api ?: $this->primary_api;
        $result = null;
        
        try {
            if ($primary === 'openai') {
                error_log('MPAI: Trying OpenAI API first');
                
                // For OpenAI, we need to format tools correctly
                $openai_params = array();
                if (!empty($tools)) {
                    $openai_tools = $this->format_tools_for_openai($tools);
                    $openai_params['tools'] = $openai_tools;
                }
                
                $result = $this->openai->send_request($messages, $openai_params);
                
                if (is_wp_error($result)) {
                    throw new Exception($result->get_error_message());
                }
                
                return $this->format_openai_response($result);
            } else {
                error_log('MPAI: Trying Anthropic API first');
                
                // For Anthropic, we need to format tools correctly
                $anthropic_tools = !empty($tools) ? $this->anthropic->convert_tools_to_anthropic_format($tools) : array();
                
                $result = $this->anthropic->send_request($messages, $anthropic_tools);
                
                if (is_wp_error($result)) {
                    throw new Exception($result->get_error_message());
                }
                
                return $this->format_anthropic_response($result);
            }
        } catch (Exception $e) {
            // Log the failure
            error_log("MPAI: Primary API ($primary) failed: " . $e->getMessage());
            
            // If forced to use a specific API, don't fall back
            if ($force_api !== null) {
                return new WP_Error('api_error', "The selected API ($force_api) failed: " . $e->getMessage());
            }
            
            // Try fallback API
            try {
                if ($primary === 'openai') {
                    error_log('MPAI: Trying Anthropic API as fallback');
                    
                    // For Anthropic, we need to format tools correctly
                    $anthropic_tools = !empty($tools) ? $this->anthropic->convert_tools_to_anthropic_format($tools) : array();
                    
                    $result = $this->anthropic->send_request($messages, $anthropic_tools);
                    
                    if (is_wp_error($result)) {
                        throw new Exception($result->get_error_message());
                    }
                    
                    return $this->format_anthropic_response($result);
                } else {
                    error_log('MPAI: Trying OpenAI API as fallback');
                    
                    // For OpenAI, we need to format tools correctly
                    $openai_params = array();
                    if (!empty($tools)) {
                        $openai_tools = $this->format_tools_for_openai($tools);
                        $openai_params['tools'] = $openai_tools;
                    }
                    
                    $result = $this->openai->send_request($messages, $openai_params);
                    
                    if (is_wp_error($result)) {
                        throw new Exception($result->get_error_message());
                    }
                    
                    return $this->format_openai_response($result);
                }
            } catch (Exception $e2) {
                error_log("MPAI: Fallback API also failed: " . $e2->getMessage());
                return new WP_Error('api_error', 'Both APIs failed. Primary error: ' . $e->getMessage() . ', Fallback error: ' . $e2->getMessage());
            }
        }
    }

    /**
     * Format tools for OpenAI
     *
     * @param array $tools The tools to format
     * @return array Formatted tools for OpenAI
     */
    private function format_tools_for_openai($tools) {
        $openai_tools = array();
        
        foreach ($tools as $tool) {
            $openai_tools[] = array(
                'type' => 'function',
                'function' => $tool
            );
        }
        
        return $openai_tools;
    }

    /**
     * Format OpenAI response to a standard format
     *
     * @param array $response The OpenAI response
     * @return array|string Standardized response
     */
    private function format_openai_response($response) {
        // Extract text message
        $message = isset($response['choices'][0]['message']['content']) 
            ? $response['choices'][0]['message']['content'] 
            : '';
        
        // Check for tool calls
        if (isset($response['choices'][0]['message']['tool_calls']) && !empty($response['choices'][0]['message']['tool_calls'])) {
            $tool_calls = $response['choices'][0]['message']['tool_calls'];
            
            return array(
                'message' => $message,
                'tool_calls' => $tool_calls,
                'api' => 'openai'
            );
        }
        
        // Return just the message content if no tool calls
        return $message;
    }

    /**
     * Format Anthropic response to a standard format
     *
     * @param array $response The Anthropic response
     * @return array|string Standardized response
     */
    private function format_anthropic_response($response) {
        // Extract text message
        $message = isset($response['content'][0]['text']) 
            ? $response['content'][0]['text'] 
            : '';
        
        // Check for tool calls
        if (isset($response['tool_outputs']) && !empty($response['tool_outputs'])) {
            $tool_outputs = $response['tool_outputs'];
            
            // Convert Anthropic tool outputs format to match OpenAI's tool_calls format
            $tool_calls = array();
            foreach ($tool_outputs as $output) {
                $tool_calls[] = array(
                    'id' => isset($output['id']) ? $output['id'] : uniqid('tool_call_'),
                    'type' => 'function',
                    'function' => array(
                        'name' => $output['name'],
                        'arguments' => json_encode($output['input']),
                    )
                );
            }
            
            return array(
                'message' => $message,
                'tool_calls' => $tool_calls,
                'api' => 'anthropic'
            );
        }
        
        // Return just the message content if no tool calls
        return $message;
    }

    /**
     * Generate a completion using appropriate API
     *
     * @param array $messages Conversation messages
     * @param array $tools Available tools
     * @param array $context Additional context
     * @return string|array|WP_Error Response from API
     */
    public function generate_completion($messages, $tools = array(), $context = array()) {
        return $this->process_request($messages, $tools, $context);
    }

    /**
     * Generate a completion with MemberPress context
     *
     * @param string $prompt User prompt
     * @param array $memberpress_data MemberPress data for context
     * @param array $context Additional context
     * @return string|WP_Error Response from API
     */
    public function generate_memberpress_completion($prompt, $memberpress_data, $context = array()) {
        // Create a system message with MemberPress context
        $system_message = "You are an AI assistant for MemberPress. You have access to the following MemberPress data:\n\n";
        
        foreach ($memberpress_data as $key => $value) {
            if (is_array($value)) {
                $system_message .= "- {$key}:\n";
                foreach ($value as $item) {
                    if (is_array($item)) {
                        $system_message .= json_encode($item, JSON_PRETTY_PRINT) . "\n";
                    } else {
                        $system_message .= "  - {$item}\n";
                    }
                }
            } else {
                $system_message .= "- {$key}: {$value}\n";
            }
        }
        
        $system_message .= "\nYour task is to provide helpful responses about MemberPress based on this data.";
        
        $messages = array(
            array('role' => 'system', 'content' => $system_message),
            array('role' => 'user', 'content' => $prompt)
        );
        
        return $this->process_request($messages, array(), $context);
    }

    /**
     * Generate CLI command recommendations
     *
     * @param string $prompt User request
     * @param array $context Additional context
     * @return string|WP_Error Response from API
     */
    public function generate_cli_recommendations($prompt, $context = array()) {
        $system_message = "You are an AI assistant that recommends WordPress CLI commands. 
        Your task is to suggest appropriate WP-CLI commands based on the user's request. 
        Only suggest commands that are safe to run and relevant to MemberPress. 
        Format your response as a list of commands with a brief explanation for each.";
        
        $messages = array(
            array('role' => 'system', 'content' => $system_message),
            array('role' => 'user', 'content' => $prompt)
        );
        
        return $this->process_request($messages, array(), $context);
    }
}