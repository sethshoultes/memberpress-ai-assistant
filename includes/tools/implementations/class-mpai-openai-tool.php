<?php
/**
 * OpenAI Tool Class
 *
 * Provides access to OpenAI API functionality.
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * OpenAI Tool for MemberPress AI Assistant
 */
class MPAI_OpenAI_Tool extends MPAI_Base_Tool {
    /**
     * OpenAI client
     *
     * @var MPAI_OpenAI
     */
    private $openai;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->name = 'OpenAI Tool';
        $this->description = 'Provides access to OpenAI capabilities';
        $this->openai = new MPAI_OpenAI();
    }
    
    /**
     * Execute the tool
     *
     * @param array $parameters Tool parameters
     * @return mixed Execution result
     */
    public function execute($parameters) {
        // Check for required parameters
        if (!isset($parameters['messages']) && !isset($parameters['prompt'])) {
            throw new Exception('Either messages or prompt parameter is required');
        }
        
        // If messages are provided, use chat completion
        if (isset($parameters['messages'])) {
            return $this->generate_chat_completion($parameters);
        }
        
        // Otherwise use prompt for simple completion
        return $this->generate_completion($parameters);
    }
    
    /**
     * Generate chat completion
     *
     * @param array $parameters Parameters
     * @return string Generated text
     */
    private function generate_chat_completion($parameters) {
        $messages = $parameters['messages'];
        
        // Add optional parameters
        $additional_params = [];
        
        if (isset($parameters['temperature'])) {
            $additional_params['temperature'] = (float) $parameters['temperature'];
        }
        
        if (isset($parameters['max_tokens'])) {
            $additional_params['max_tokens'] = (int) $parameters['max_tokens'];
        }
        
        if (isset($parameters['model'])) {
            $additional_params['model'] = $parameters['model'];
        }
        
        // Generate completion
        $result = $this->openai->send_request($messages, $additional_params);
        
        if (is_wp_error($result)) {
            throw new Exception('OpenAI API error: ' . $result->get_error_message());
        }
        
        return $result['choices'][0]['message']['content'];
    }
    
    /**
     * Generate completion from a prompt
     *
     * @param array $parameters Parameters
     * @return string Generated text
     */
    private function generate_completion($parameters) {
        $prompt = $parameters['prompt'];
        
        // Convert prompt to messages format
        $messages = [
            ['role' => 'user', 'content' => $prompt]
        ];
        
        // Add system message if provided
        if (isset($parameters['system'])) {
            array_unshift($messages, ['role' => 'system', 'content' => $parameters['system']]);
        }
        
        // Add rest of parameters
        $parameters['messages'] = $messages;
        
        // Use chat completion method
        return $this->generate_chat_completion($parameters);
    }
    
    /**
     * Get parameters schema
     *
     * @return array Parameters schema
     */
    public function get_parameters_schema() {
        return [
            'type' => 'object',
            'properties' => [
                'messages' => [
                    'type' => 'array',
                    'description' => 'Array of message objects for chat completion',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'role' => [
                                'type' => 'string',
                                'description' => 'Role of the message sender (system, user, or assistant)',
                                'enum' => ['system', 'user', 'assistant']
                            ],
                            'content' => [
                                'type' => 'string',
                                'description' => 'Content of the message'
                            ]
                        ],
                        'required' => ['role', 'content']
                    ]
                ],
                'prompt' => [
                    'type' => 'string',
                    'description' => 'Text prompt for simple completion (alternative to messages)'
                ],
                'system' => [
                    'type' => 'string',
                    'description' => 'System message to add when using prompt'
                ],
                'temperature' => [
                    'type' => 'number',
                    'description' => 'Sampling temperature between 0 and 2',
                    'minimum' => 0,
                    'maximum' => 2
                ],
                'max_tokens' => [
                    'type' => 'integer',
                    'description' => 'Maximum number of tokens to generate',
                    'minimum' => 1
                ],
                'model' => [
                    'type' => 'string',
                    'description' => 'OpenAI model to use'
                ]
            ],
            'anyOf' => [
                ['required' => ['messages']],
                ['required' => ['prompt']]
            ]
        ];
    }
}