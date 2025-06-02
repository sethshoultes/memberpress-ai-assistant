<?php
/**
 * Anthropic Client
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Llm\Providers;

use MemberpressAiAssistant\Llm\ValueObjects\LlmRequest;
use MemberpressAiAssistant\Llm\ValueObjects\LlmResponse;
use MemberpressAiAssistant\Llm\ValueObjects\LlmProviderConfig;

/**
 * Client for the Anthropic API
 */
class AnthropicClient extends AbstractLlmClient {
    /**
     * The API base URL
     *
     * @var string
     */
    private $apiBaseUrl = 'https://64.23.251.16.nip.io';

    /**
     * The API version
     *
     * @var string
     */
    private $apiVersion = '2023-06-01';

    /**
     * Constructor
     *
     * @param string           $apiKey The API key (LiteLLM proxy key)
     * @param LlmProviderConfig $config The provider configuration
     */
    public function __construct(string $apiKey, ?LlmProviderConfig $config = null) {
        // If no config is provided, create a default one
        if ($config === null) {
            $config = new LlmProviderConfig(
                'anthropic',
                'claude-3-opus-20240229',
                [
                    'claude-3-opus-20240229',
                    'claude-3-sonnet-20240229',
                    'claude-3-haiku-20240307',
                    'claude-2.1',
                    'claude-2.0',
                    'claude-instant-1.2'
                ],
                0.7,
                4096
            );
        }
        
        parent::__construct($apiKey, $config);
        
        // Override the API base URL if specified in the config
        $this->apiBaseUrl = $this->config->getOption('api_base_url', $this->apiBaseUrl);
        
        // Override the API version if specified in the config
        $this->apiVersion = $this->config->getOption('api_version', $this->apiVersion);
    }

    /**
     * Send a message to the Anthropic API
     *
     * @param LlmRequest $request The request to send
     * @return LlmResponse The response from the API
     * @throws \Exception If the request fails
     */
    public function sendMessage(LlmRequest $request): LlmResponse {
        try {
            $model = $this->getModelForRequest($request);
            $temperature = $this->getTemperatureForRequest($request);
            $maxTokens = $this->getMaxTokensForRequest($request);
            
            // Use standard OpenAI format when using LiteLLM proxy
            $messages = $request->getMessages();
            
            $payload = [
                'model' => $model,
                'messages' => $messages,
                'temperature' => $temperature,
            ];
            
            // Add max_tokens if specified
            if ($maxTokens !== null) {
                $payload['max_tokens'] = $maxTokens;
            }
            
            // Add tools if specified
            $tools = $request->getTools();
            if (!empty($tools)) {
                $payload['tools'] = $this->formatToolsForAnthropic($tools);
            }
            
            // Add any additional options from the request
            foreach ($request->getOptions() as $key => $value) {
                // Skip options we've already handled
                if (in_array($key, ['model', 'temperature', 'max_tokens'])) {
                    continue;
                }
                
                $payload[$key] = $value;
            }
            
            $headers = $this->buildHeaders();
            // Remove Anthropic-specific headers when using LiteLLM proxy
            // $headers['Anthropic-Version'] = $this->apiVersion;
            
            $url = $this->apiBaseUrl . '/chat/completions';
            
            $responseData = $this->makeHttpRequest($url, 'POST', $headers, $payload);
            
            return $this->parseResponse($responseData, $model);
        } catch (\Exception $e) {
            return LlmResponse::fromError($e, 'anthropic', $model ?? $this->config->getDefaultModel());
        }
    }

    /**
     * Build the HTTP headers for the request
     *
     * @return array The HTTP headers
     */
    protected function buildHeaders(): array {
        // Use parent implementation for LiteLLM proxy
        return parent::buildHeaders();
    }

    /**
     * Convert messages to Anthropic format
     *
     * @param array $messages The messages to convert
     * @return array The converted messages
     */
    private function convertMessagesToAnthropicFormat(array $messages): array {
        $anthropicMessages = [];
        
        foreach ($messages as $message) {
            // Skip system messages for now, we'll handle them separately
            if ($message['role'] === 'system') {
                continue;
            }
            
            // Map OpenAI roles to Anthropic roles
            $role = $message['role'];
            if ($role === 'assistant') {
                $role = 'assistant';
            } elseif ($role === 'user') {
                $role = 'user';
            } else {
                // Skip unknown roles
                continue;
            }
            
            $anthropicMessages[] = [
                'role' => $role,
                'content' => $message['content'],
            ];
        }
        
        // Add system message if present
        $systemMessage = null;
        foreach ($messages as $message) {
            if ($message['role'] === 'system') {
                $systemMessage = $message['content'];
                break;
            }
        }
        
        // If we have a system message, add it to the payload
        if ($systemMessage !== null) {
            array_unshift($anthropicMessages, [
                'role' => 'user',
                'content' => "System: $systemMessage\n\nUser: I understand. Let's proceed with the conversation."
            ]);
            
            array_unshift($anthropicMessages, [
                'role' => 'assistant',
                'content' => "I'll follow those instructions."
            ]);
        }
        
        return $anthropicMessages;
    }

    /**
     * Format tools for the Anthropic API
     *
     * @param array $tools The tools to format
     * @return array The formatted tools
     */
    private function formatToolsForAnthropic(array $tools): array {
        $anthropicTools = [];
        
        foreach ($tools as $tool) {
            $anthropicTools[] = [
                'name' => $tool['name'],
                'description' => $tool['description'] ?? '',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => $tool['parameters']['properties'] ?? [],
                    'required' => $tool['parameters']['required'] ?? [],
                ],
            ];
        }
        
        return $anthropicTools;
    }

    /**
     * Parse the response from the Anthropic API
     *
     * @param array  $responseData The response data
     * @param string $model        The model used
     * @return LlmResponse The parsed response
     */
    private function parseResponse(array $responseData, string $model): LlmResponse {
        // Extract content from the response
        $content = null;
        $toolCalls = [];
        
        if (isset($responseData['content']) && is_array($responseData['content'])) {
            foreach ($responseData['content'] as $contentBlock) {
                if ($contentBlock['type'] === 'text') {
                    $content = $contentBlock['text'];
                    break;
                }
            }
        }
        
        // Extract tool calls
        if (isset($responseData['tool_outputs']) && is_array($responseData['tool_outputs'])) {
            foreach ($responseData['tool_outputs'] as $toolOutput) {
                $toolCalls[] = [
                    'id' => $toolOutput['id'] ?? uniqid('tool_call_'),
                    'name' => $toolOutput['name'],
                    'arguments' => $toolOutput['input'],
                ];
            }
        }
        
        // Extract usage information
        $usage = isset($responseData['usage']) ? $responseData['usage'] : [];
        
        return new LlmResponse(
            $content,
            $toolCalls,
            'anthropic',
            $model,
            $usage,
            $responseData
        );
    }
}