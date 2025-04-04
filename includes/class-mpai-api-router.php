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
     * Error recovery system
     *
     * @var MPAI_Error_Recovery
     */
    private $error_recovery;

    /**
     * Constructor
     */
    public function __construct() {
        // Load dependencies if not already loaded
        error_log('MPAI API Router: Constructor started');
        if (!class_exists('MPAI_OpenAI')) {
            require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-openai.php';
        }
        
        if (!class_exists('MPAI_Anthropic')) {
            require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-anthropic.php';
        }
        
        if (!class_exists('MPAI_Error_Recovery')) {
            require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-error-recovery.php';
        }
        
        $this->openai = new MPAI_OpenAI();
        $this->anthropic = new MPAI_Anthropic();
        $this->error_recovery = mpai_init_error_recovery();
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
        $fallback = ($primary === 'openai') ? 'anthropic' : 'openai';
        
        // Skip fallback if API is forced
        $skip_fallback = ($force_api !== null);
        
        // Try primary API with error recovery system
        try {
            $result = null;
            
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
                    // Create standardized API error
                    $api_error = $this->error_recovery->create_api_error(
                        'openai',
                        $result->get_error_code(),
                        $result->get_error_message(),
                        $result->get_error_data()
                    );
                    
                    // Define retry callback for OpenAI
                    $retry_callback = function() use ($messages, $openai_params) {
                        error_log('MPAI: Retrying OpenAI API');
                        $retry_result = $this->openai->send_request($messages, $openai_params);
                        
                        if (is_wp_error($retry_result)) {
                            return $retry_result;
                        }
                        
                        return $this->format_openai_response($retry_result);
                    };
                    
                    // Define fallback callback for Anthropic
                    $fallback_callback = function() use ($messages, $tools) {
                        error_log('MPAI: Using Anthropic API as fallback');
                        $anthropic_tools = !empty($tools) ? $this->anthropic->convert_tools_to_anthropic_format($tools) : array();
                        $fallback_result = $this->anthropic->send_request($messages, $anthropic_tools);
                        
                        if (is_wp_error($fallback_result)) {
                            return $fallback_result;
                        }
                        
                        return $this->format_anthropic_response($fallback_result);
                    };
                    
                    // Use error recovery system
                    return $this->error_recovery->handle_error(
                        $api_error,
                        'openai',
                        $retry_callback,
                        [],
                        $skip_fallback ? null : $fallback_callback
                    );
                }
                
                return $this->format_openai_response($result);
            } else {
                error_log('MPAI: Trying Anthropic API first');
                
                // For Anthropic, we need to format tools correctly
                $anthropic_tools = !empty($tools) ? $this->anthropic->convert_tools_to_anthropic_format($tools) : array();
                
                $result = $this->anthropic->send_request($messages, $anthropic_tools);
                
                if (is_wp_error($result)) {
                    // Create standardized API error
                    $api_error = $this->error_recovery->create_api_error(
                        'anthropic',
                        $result->get_error_code(),
                        $result->get_error_message(),
                        $result->get_error_data()
                    );
                    
                    // Define retry callback for Anthropic
                    $retry_callback = function() use ($messages, $anthropic_tools) {
                        error_log('MPAI: Retrying Anthropic API');
                        $retry_result = $this->anthropic->send_request($messages, $anthropic_tools);
                        
                        if (is_wp_error($retry_result)) {
                            return $retry_result;
                        }
                        
                        return $this->format_anthropic_response($retry_result);
                    };
                    
                    // Define fallback callback for OpenAI
                    $fallback_callback = function() use ($messages, $tools) {
                        error_log('MPAI: Using OpenAI API as fallback');
                        $openai_params = array();
                        if (!empty($tools)) {
                            $openai_tools = $this->format_tools_for_openai($tools);
                            $openai_params['tools'] = $openai_tools;
                        }
                        
                        $fallback_result = $this->openai->send_request($messages, $openai_params);
                        
                        if (is_wp_error($fallback_result)) {
                            return $fallback_result;
                        }
                        
                        return $this->format_openai_response($fallback_result);
                    };
                    
                    // Use error recovery system
                    return $this->error_recovery->handle_error(
                        $api_error,
                        'anthropic',
                        $retry_callback,
                        [],
                        $skip_fallback ? null : $fallback_callback
                    );
                }
                
                return $this->format_anthropic_response($result);
            }
        } catch (Exception $e) {
            error_log("MPAI: Exception in process_request for $primary API: " . $e->getMessage());
            
            // Create standardized exception error
            $error = $this->error_recovery->create_api_error(
                $primary,
                'api_exception',
                $e->getMessage(),
                [
                    'exception_class' => get_class($e),
                    'exception_trace' => $e->getTraceAsString()
                ]
            );
            
            // If forced to use a specific API, don't use fallback
            if ($skip_fallback) {
                return $error;
            }
            
            // Define fallback callback for the other API
            $fallback_callback = function() use ($fallback, $messages, $tools) {
                try {
                    error_log("MPAI: Using $fallback API as fallback after exception");
                    
                    if ($fallback === 'openai') {
                        $openai_params = array();
                        if (!empty($tools)) {
                            $openai_tools = $this->format_tools_for_openai($tools);
                            $openai_params['tools'] = $openai_tools;
                        }
                        
                        $result = $this->openai->send_request($messages, $openai_params);
                        
                        if (is_wp_error($result)) {
                            return $result;
                        }
                        
                        return $this->format_openai_response($result);
                    } else {
                        $anthropic_tools = !empty($tools) ? $this->anthropic->convert_tools_to_anthropic_format($tools) : array();
                        
                        $result = $this->anthropic->send_request($messages, $anthropic_tools);
                        
                        if (is_wp_error($result)) {
                            return $result;
                        }
                        
                        return $this->format_anthropic_response($result);
                    }
                } catch (Exception $e2) {
                    error_log("MPAI: Fallback API ($fallback) also failed: " . $e2->getMessage());
                    
                    // Create combined error
                    return $this->error_recovery->create_api_error(
                        $fallback,
                        'fallback_exception',
                        $e2->getMessage(),
                        [
                            'primary_api' => $primary,
                            'primary_error' => $e->getMessage(),
                            'exception_class' => get_class($e2),
                            'exception_trace' => $e2->getTraceAsString()
                        ]
                    );
                }
            };
            
            // Use error recovery with fallback
            return $this->error_recovery->handle_error(
                $error,
                $primary,
                null,
                [],
                $fallback_callback
            );
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
     * Reset the internal state of the API Router
     * 
     * This clears any cached data and reinitializes API connections.
     */
    public function reset_state() {
        error_log('MPAI API Router: Resetting state');
        
        // Reset OpenAI instance if it exists
        if (isset($this->openai) && method_exists($this->openai, 'reset')) {
            $this->openai->reset();
            error_log('MPAI API Router: Reset OpenAI instance');
        } else {
            // If no reset method, recreate the instance
            $this->openai = new MPAI_OpenAI();
            error_log('MPAI API Router: Recreated OpenAI instance');
        }
        
        // Reset Anthropic instance if it exists
        if (isset($this->anthropic) && method_exists($this->anthropic, 'reset')) {
            $this->anthropic->reset();
            error_log('MPAI API Router: Reset Anthropic instance');
        } else {
            // If no reset method, recreate the instance  
            $this->anthropic = new MPAI_Anthropic();
            error_log('MPAI API Router: Recreated Anthropic instance');
        }
        
        // Reload primary API setting from database
        $this->primary_api = get_option('mpai_primary_api', 'openai');
        error_log('MPAI API Router: Reloaded primary API setting: ' . $this->primary_api);
        
        // Force refresh WordPress plugins cache
        wp_cache_delete('plugins', 'plugins');
        if (function_exists('get_plugins')) {
            get_plugins('', true); // Force refresh
            error_log('MPAI API Router: Refreshed WordPress plugins cache');
        }
        
        error_log('MPAI API Router: State reset complete');
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