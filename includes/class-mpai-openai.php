<?php
/**
 * OpenAI Integration Class
 *
 * Handles integration with OpenAI API
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

class MPAI_OpenAI {
    /**
     * OpenAI API key
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
     * Constructor
     */
    public function __construct() {
        $this->api_key = get_option('mpai_api_key', '');
        $this->model = get_option('mpai_model', 'gpt-4o');
        $this->temperature = (float) get_option('mpai_temperature', 0.7);
        $this->max_tokens = (int) get_option('mpai_max_tokens', 2048);
    }

    /**
     * Send a request to the OpenAI API
     *
     * @param array $messages The messages to send
     * @param array $additional_params Additional parameters to send to the API
     * @return array|WP_Error The API response or error
     */
    public function send_request($messages, $additional_params = array()) {
        if (empty($this->api_key)) {
            return new WP_Error('missing_api_key', 'OpenAI API key is not configured.');
        }

        $endpoint = 'https://api.openai.com/v1/chat/completions';

        $headers = array(
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json',
        );

        $body = array_merge(
            array(
                'model' => $this->model,
                'messages' => $messages,
                'temperature' => $this->temperature,
                'max_tokens' => $this->max_tokens,
            ),
            $additional_params
        );

        $response = wp_remote_post(
            $endpoint,
            array(
                'headers' => $headers,
                'body' => json_encode($body),
                'timeout' => 60,
            )
        );

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', 'Failed to parse OpenAI API response.');
        }

        if (isset($data['error'])) {
            return new WP_Error(
                'openai_error',
                $data['error']['message'],
                array('status' => $data['error']['type'])
            );
        }

        return $data;
    }

    /**
     * Generate a chat completion
     *
     * @param array $messages The conversation history
     * @return string|WP_Error The generated text or error
     */
    public function generate_chat_completion($messages) {
        // If no API key is set, return a dummy response for testing
        if (empty($this->api_key)) {
            error_log('MPAI: No OpenAI API key configured, returning dummy response');
            return "I'm sorry, but the OpenAI API key is not configured. Please add your API key in the settings page to use the AI assistant.";
        }
        
        // Let's log the messages for debugging
        error_log('MPAI: Sending messages to OpenAI: ' . json_encode($messages));
        
        try {
            $response = $this->send_request($messages);
    
            if (is_wp_error($response)) {
                error_log('MPAI: OpenAI API returned WP_Error: ' . $response->get_error_message());
                return $response;
            }
    
            if (empty($response['choices'][0]['message']['content'])) {
                error_log('MPAI: OpenAI returned empty response');
                return new WP_Error('empty_response', 'OpenAI returned an empty response.');
            }
    
            return $response['choices'][0]['message']['content'];
        } catch (Exception $e) {
            error_log('MPAI: Error in generate_chat_completion: ' . $e->getMessage());
            return new WP_Error('openai_error', 'Error generating completion: ' . $e->getMessage());
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
        
        return $this->generate_chat_completion($messages);
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
        
        return $this->generate_chat_completion($messages);
    }
}