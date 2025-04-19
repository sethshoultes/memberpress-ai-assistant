<?php
/**
 * Anthropic Claude Integration Class
 *
 * Handles integration with Anthropic Claude API
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

class MPAI_Anthropic {
    /**
     * Anthropic API key
     *
     * @var string
     */
    private $api_key;

    /**
     * Model to use
     *
     * @var string
     */
    private $model;

    /**
     * Temperature for response generation
     *
     * @var float
     */
    private $temperature;

    /**
     * Maximum number of tokens to generate
     *
     * @var int
     */
    private $max_tokens;

    /**
     * Response cache instance
     *
     * @var MPAI_Response_Cache
     */
    private $cache;
    
    /**
     * Constructor
     * 
     * @param string $api_key Optional API key to override the one from options
     */
    public function __construct($api_key = '') {
        $this->api_key = !empty($api_key) ? $api_key : get_option('mpai_anthropic_api_key', '');
        $this->model = get_option('mpai_anthropic_model', 'claude-3-opus-20240229');
        $this->temperature = (float) get_option('mpai_anthropic_temperature', 0.7);
        $this->max_tokens = (int) get_option('mpai_anthropic_max_tokens', 2048);
        
        // Initialize cache
        $cache_config = [
            'filesystem_cache' => true,
            'db_cache' => false,
            'cache_ttl' => 3600 // 1 hour
        ];
        
        if (class_exists('MPAI_Response_Cache')) {
            $this->cache = new MPAI_Response_Cache($cache_config);
        }
    }
    
    /**
     * Test connection to Anthropic API
     * 
     * @param string $model Optional model to test
     * @return array Success status and any error message
     */
    public function test_connection($model = '') {
        if (empty($this->api_key)) {
            return array(
                'success' => false,
                'error' => 'API key is not set'
            );
        }
        
        $test_model = !empty($model) ? $model : $this->model;
        
        // Simple test message
        $messages = array(
            array('role' => 'user', 'content' => 'Test connection. Respond with only "Connection successful".')
        );
        
        try {
            $response = $this->send_request($messages);
            
            if (is_wp_error($response)) {
                return array(
                    'success' => false,
                    'error' => $response->get_error_message()
                );
            }
            
            if (empty($response['content'][0]['text'])) {
                return array(
                    'success' => false,
                    'error' => 'Empty response from API'
                );
            }
            
            return array(
                'success' => true,
                'model' => $test_model,
                'response' => $response['content'][0]['text']
            );
        } catch (Exception $e) {
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * Send a request to the Anthropic Claude API
     *
     * @param array $messages The messages to send
     * @param array $tools Available tools for function calling
     * @param array $additional_params Additional parameters to send to the API
     * @return array|WP_Error The API response or error
     */
    public function send_request($messages, $tools = array(), $additional_params = array()) {
        if (empty($this->api_key)) {
            return new WP_Error('missing_api_key', 'Anthropic API key is not configured.');
        }

        $endpoint = 'https://api.anthropic.com/v1/messages';

        $headers = array(
            'x-api-key' => $this->api_key,
            'anthropic-version' => '2023-06-01',
            'Content-Type' => 'application/json',
        );

        // Format messages from OpenAI format to Anthropic format if needed
        $formatted_messages = $this->format_messages_for_anthropic($messages);

        $body = array(
            'model' => $this->model,
            'messages' => $formatted_messages,
            'temperature' => $this->temperature,
            'max_tokens' => $this->max_tokens,
        );
        
        // Add system parameter if we have a system prompt
        if (isset($GLOBALS['anthropic_system_prompt'])) {
            $body['system'] = $GLOBALS['anthropic_system_prompt'];
            unset($GLOBALS['anthropic_system_prompt']);
        }
        
        // Add any additional parameters
        $body = array_merge($body, $additional_params);

        // Add tools if provided
        if (!empty($tools)) {
            $body['tools'] = $tools;
        }
        
        // Create request params for hooks
        $request_params = [
            'endpoint' => $endpoint,
            'headers' => $headers,
            'body' => $body,
            'model' => $this->model,
            'temperature' => $this->temperature,
            'max_tokens' => $this->max_tokens,
            'tools' => $tools,
        ];
        
        // Context for hooks
        $context = [
            'request_type' => 'chat_completion',
            'message_count' => count($messages),
            'has_tools' => !empty($tools),
        ];
        
        // Filter request parameters
        $request_params = apply_filters('MPAI_HOOK_FILTER_api_request_params', $request_params, 'anthropic');
        
        // Extract filtered values
        $endpoint = $request_params['endpoint'];
        $headers = $request_params['headers'];
        $body = $request_params['body'];
        
        // Action before API request
        do_action('MPAI_HOOK_ACTION_before_api_request', 'anthropic', $request_params, $context);
        
        // Track request time
        $start_time = microtime(true);

        $response = wp_remote_post(
            $endpoint,
            array(
                'headers' => $headers,
                'body' => json_encode($body),
                'timeout' => 60,
            )
        );
        
        // Calculate request duration
        $duration = microtime(true) - $start_time;

        if (is_wp_error($response)) {
            // Action after API request (with error)
            do_action('MPAI_HOOK_ACTION_after_api_request', 'anthropic', $request_params, $response, $duration);
            return $response;
        }

        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $error = new WP_Error('json_error', 'Failed to parse Anthropic API response.');
            // Action after API request (with error)
            do_action('MPAI_HOOK_ACTION_after_api_request', 'anthropic', $request_params, $error, $duration);
            return $error;
        }

        if (isset($data['error'])) {
            $error = new WP_Error(
                'anthropic_error',
                $data['error']['message'],
                array('status' => $data['error']['type'])
            );
            // Action after API request (with error)
            do_action('MPAI_HOOK_ACTION_after_api_request', 'anthropic', $request_params, $error, $duration);
            return $error;
        }
        
        // Filter API response
        $data = apply_filters('MPAI_HOOK_FILTER_api_response', $data, 'anthropic', $request_params);
        
        // Action after API request (success)
        do_action('MPAI_HOOK_ACTION_after_api_request', 'anthropic', $request_params, $data, $duration);

        return $data;
    }

    /**
     * Format messages from OpenAI format to Anthropic format if needed
     *
     * @param array $messages Messages in OpenAI format
     * @return array Messages in Anthropic format
     */
    private function format_messages_for_anthropic($messages) {
        $formatted_messages = array();
        $system_message = null;
        
        // First extract the system message if available
        foreach ($messages as $message) {
            if ($message['role'] === 'system') {
                $system_message = $message['content'];
                break;
            }
        }
        
        // Now process all non-system messages
        foreach ($messages as $message) {
            if ($message['role'] !== 'system') {
                $formatted_message = array(
                    'role' => $message['role'],
                    'content' => $message['content']
                );
                
                $formatted_messages[] = $formatted_message;
            }
        }
        
        // If we have a system message and formatted messages
        if ($system_message && !empty($formatted_messages)) {
            // Instead of adding system to each user message, we'll add it to the API request
            // as a separate parameter rather than inside a message
            $GLOBALS['anthropic_system_prompt'] = $system_message;
        }
        
        return $formatted_messages;
    }

    /**
     * Generate a chat completion using Anthropic Claude
     *
     * @param array $messages The conversation history
     * @param array $tools Available tools for function calling
     * @param array $options Additional options
     * @return string|WP_Error The generated text or error
     */
    public function generate_completion($messages, $tools = array(), $options = []) {
        // If no API key is set, return a dummy response for testing
        if (empty($this->api_key)) {
            mpai_log_warning('No Anthropic API key configured, returning dummy response', 'anthropic');
            return "I'm sorry, but the Anthropic API key is not configured. Please add your API key in the settings page to use the Claude AI assistant.";
        }
        
        // Determine if caching should be skipped for this request
        $skip_cache = false;
        if (isset($options['type']) && $options['type'] === 'content_creation') {
            $skip_cache = true;
            mpai_log_debug('Skipping cache for content creation request', 'anthropic');
        }
        
        if (!$skip_cache && isset($this->cache)) {
            // Generate cache key
            $cache_key = 'anthropic_' . md5(json_encode($messages) . json_encode($tools) . json_encode($options));
            
            // Check cache
            $cached_response = $this->cache->get($cache_key);
            if ($cached_response !== null) {
                mpai_log_debug('Using cached response for request', 'anthropic');
                return $cached_response;
            }
        }
        
        // Only log a summary of the request, not the entire content
        mpai_log_debug('Sending request to Anthropic API - ' . count($messages) . ' messages', 'anthropic');
        
        try {
            $response = $this->send_request($messages, $tools);
    
            if (is_wp_error($response)) {
                mpai_log_error('Anthropic API returned WP_Error: ' . $response->get_error_message(), 'anthropic');
                return $response;
            }
    
            if (empty($response['content'][0]['text'])) {
                mpai_log_warning('Anthropic returned empty response', 'anthropic');
                return new WP_Error('empty_response', 'Anthropic returned an empty response.');
            }
            
            // Extract tool calls if present
            if (!empty($response['tool_outputs'])) {
                // Process tool calls - don't cache tool call responses
                mpai_log_debug('Tool outputs found in Anthropic response', 'anthropic');
                $result = array(
                    'message' => $response['content'][0]['text'],
                    'tool_outputs' => $response['tool_outputs']
                );
                return $result;
            }
            
            $result = $response['content'][0]['text'];
            
            // Cache the successful response if not skipped
            if (!$skip_cache && isset($this->cache)) {
                $this->cache->set($cache_key, $result);
                mpai_log_debug('Cached response for future use', 'anthropic');
            }
    
            return $result;
        } catch (Exception $e) {
            mpai_log_error('Error in generate_completion: ' . $e->getMessage(), 'anthropic', array(
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ));
            return new WP_Error('anthropic_error', 'Error generating completion: ' . $e->getMessage());
        }
    }

    /**
     * Generate a completion with context about MemberPress data
     *
     * @param string $prompt The prompt to send
     * @param array $memberpress_data MemberPress data to include in the context
     * @return string|WP_Error The generated text or error
     */
    public function generate_memberpress_completion($prompt, $memberpress_data) {
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
        
        return $this->generate_completion($messages);
    }

    /**
     * Generate CLI command recommendations
     *
     * @param string $prompt The user's request
     * @return string|WP_Error The recommended commands or error
     */
    public function generate_cli_recommendations($prompt) {
        $system_message = "You are an AI assistant that recommends WordPress CLI commands. 
        Your task is to suggest appropriate WP-CLI commands based on the user's request. 
        Only suggest commands that are safe to run and relevant to MemberPress. 
        Format your response as a list of commands with a brief explanation for each.";
        
        $messages = array(
            array('role' => 'system', 'content' => $system_message),
            array('role' => 'user', 'content' => $prompt)
        );
        
        return $this->generate_completion($messages);
    }

    /**
     * Convert tools from OpenAI format to Anthropic format
     *
     * @param array $openai_tools Tools in OpenAI format
     * @return array Tools in Anthropic format
     */
    public function convert_tools_to_anthropic_format($openai_tools) {
        $anthropic_tools = array();
        
        foreach ($openai_tools as $tool) {
            if (isset($tool['function'])) {
                $function = $tool['function'];
                
                // Convert OpenAI function schema to Anthropic schema
                $parameters = array();
                if (isset($function['parameters']['properties'])) {
                    foreach ($function['parameters']['properties'] as $name => $property) {
                        $param = array(
                            'name' => $name,
                            'type' => $property['type'],
                        );
                        
                        if (isset($property['description'])) {
                            $param['description'] = $property['description'];
                        }
                        
                        if (isset($property['enum'])) {
                            $param['enum'] = $property['enum'];
                        }
                        
                        $parameters[] = $param;
                    }
                }
                
                $anthropic_tools[] = array(
                    'name' => $function['name'],
                    'description' => isset($function['description']) ? $function['description'] : '',
                    'input_schema' => array(
                        'type' => 'object',
                        'properties' => $parameters,
                        'required' => isset($function['parameters']['required']) ? $function['parameters']['required'] : array(),
                    ),
                );
            }
        }
        
        return $anthropic_tools;
    }
}