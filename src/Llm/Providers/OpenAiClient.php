<?php
/**
 * OpenAI Client
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Llm\Providers;

use MemberpressAiAssistant\Llm\ValueObjects\LlmRequest;
use MemberpressAiAssistant\Llm\ValueObjects\LlmResponse;
use MemberpressAiAssistant\Llm\ValueObjects\LlmProviderConfig;

/**
 * Client for the OpenAI API
 */
class OpenAiClient extends AbstractLlmClient {
    /**
     * The API base URL
     *
     * @var string
     */
    private $apiBaseUrl = 'https://64.23.251.16.nip.io';

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
                'openai',
                'gpt-4o',
                ['gpt-4o', 'gpt-4-turbo', 'gpt-4', 'gpt-3.5-turbo'],
                0.7,
                4096
            );
        }
        
        parent::__construct($apiKey, $config);
        
        // Override the API base URL if specified in the config
        $this->apiBaseUrl = $this->config->getOption('api_base_url', $this->apiBaseUrl);
    }

    /**
     * Send a message to the OpenAI API
     *
     * @param LlmRequest $request The request to send
     * @return LlmResponse The response from the API
     * @throws \Exception If the request fails
     */
    public function sendMessage(LlmRequest $request): LlmResponse {
        try {
            // Log the request
            if (function_exists('error_log')) {
                \MemberpressAiAssistant\Utilities\debug_log('MPAI Debug - OpenAI request: ' . json_encode([
                    'messages' => $request->getMessages(),
                    'options' => $request->getOptions()
                ]));
            }
            
            $model = $this->getModelForRequest($request);
            $temperature = $this->getTemperatureForRequest($request);
            $maxTokens = $this->getMaxTokensForRequest($request);
            
            if (function_exists('error_log')) {
                \MemberpressAiAssistant\Utilities\debug_log('MPAI Debug - OpenAI using model: ' . $model);
            }
            
            $payload = [
                'model' => $model,
                'messages' => $request->getMessages(),
                'temperature' => $temperature,
            ];
            
            // Add max_tokens if specified
            if ($maxTokens !== null) {
                $payload['max_tokens'] = $maxTokens;
            }
            
            // Add tools if specified
            $tools = $request->getTools();
            if (!empty($tools)) {
                $payload['tools'] = $this->formatToolsForOpenAi($tools);
            }
            
            // Add any additional options from the request
            foreach ($request->getOptions() as $key => $value) {
                // Skip options we've already handled and any internal options
                if (in_array($key, ['model', 'temperature', 'max_tokens', 'conversation_id'])) {
                    continue;
                }
                
                $payload[$key] = $value;
            }
            
            if (function_exists('error_log')) {
                \MemberpressAiAssistant\Utilities\debug_log('MPAI Debug - OpenAI payload: ' . json_encode($payload));
            }
            
            $headers = $this->buildHeaders();
            $url = $this->apiBaseUrl . '/chat/completions';
            
            if (function_exists('error_log')) {
                \MemberpressAiAssistant\Utilities\debug_log('MPAI Debug - OpenAI sending request to: ' . $url);
            }
            
            $responseData = $this->makeHttpRequest($url, 'POST', $headers, $payload);
            
            if (function_exists('error_log')) {
                \MemberpressAiAssistant\Utilities\debug_log('MPAI Debug - OpenAI response received');
            }
            
            return $this->parseResponse($responseData, $model);
        } catch (\Exception $e) {
            // Log the error
            if (function_exists('error_log')) {
                \MemberpressAiAssistant\Utilities\debug_log('MPAI Debug - OpenAI error: ' . $e->getMessage());
                \MemberpressAiAssistant\Utilities\debug_log('MPAI Debug - OpenAI error trace: ' . $e->getTraceAsString());
            }
            
            return LlmResponse::fromError($e, 'openai', $model ?? $this->config->getDefaultModel());
        }
    }

    /**
     * Format tools for the OpenAI API
     *
     * @param array $tools The tools to format
     * @return array The formatted tools
     */
    private function formatToolsForOpenAi(array $tools): array {
        $openaiTools = [];
        
        foreach ($tools as $tool) {
            $openaiTools[] = [
                'type' => 'function',
                'function' => $tool,
            ];
        }
        
        return $openaiTools;
    }

    /**
     * Parse the response from the OpenAI API
     *
     * @param array  $responseData The response data
     * @param string $model        The model used
     * @return LlmResponse The parsed response
     */
    private function parseResponse(array $responseData, string $model): LlmResponse {
        // Extract content from the response
        $content = null;
        $toolCalls = [];
        
        if (isset($responseData['choices'][0]['message'])) {
            $message = $responseData['choices'][0]['message'];
            
            // Extract content
            if (isset($message['content'])) {
                $content = $message['content'];
            }
            
            // Extract tool calls
            if (isset($message['tool_calls']) && is_array($message['tool_calls'])) {
                foreach ($message['tool_calls'] as $toolCall) {
                    if ($toolCall['type'] === 'function') {
                        $toolCalls[] = [
                            'id' => $toolCall['id'],
                            'name' => $toolCall['function']['name'],
                            'arguments' => json_decode($toolCall['function']['arguments'], true),
                        ];
                    }
                }
            }
        }
        
        // Extract usage information
        $usage = isset($responseData['usage']) ? $responseData['usage'] : [];
        
        return new LlmResponse(
            $content,
            $toolCalls,
            'openai',
            $model,
            $usage,
            $responseData
        );
    }
}