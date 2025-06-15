<?php
/**
 * Abstract LLM Client
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Llm\Providers;

use MemberpressAiAssistant\Llm\Interfaces\LlmClientInterface;
use MemberpressAiAssistant\Llm\ValueObjects\LlmProviderConfig;
use MemberpressAiAssistant\Llm\ValueObjects\LlmRequest;
use MemberpressAiAssistant\Llm\ValueObjects\LlmResponse;

/**
 * Abstract base class for LLM clients
 */
abstract class AbstractLlmClient implements LlmClientInterface {
    /**
     * The API key
     *
     * @var string
     */
    protected $apiKey;

    /**
     * The provider configuration
     *
     * @var LlmProviderConfig
     */
    protected $config;

    /**
     * Constructor
     *
     * @param string           $apiKey The API key
     * @param LlmProviderConfig $config The provider configuration
     */
    public function __construct(string $apiKey, LlmProviderConfig $config) {
        $this->apiKey = $apiKey;
        $this->config = $config;
    }

    /**
     * Get the provider name
     *
     * @return string The provider name
     */
    public function getProviderName(): string {
        return $this->config->getName();
    }

    /**
     * Get the available models for this provider
     *
     * @return array List of available models
     */
    public function getAvailableModels(): array {
        return $this->config->getAvailableModels();
    }

    /**
     * Send a message to the LLM provider
     *
     * @param LlmRequest $request The request to send
     * @return LlmResponse The response from the provider
     * @throws \Exception If the request fails
     */
    abstract public function sendMessage(LlmRequest $request): LlmResponse;

    /**
     * Test the connection to the LLM provider
     *
     * @param string|null $model Optional model to test
     * @return bool True if the connection is successful
     */
    public function testConnection(?string $model = null): bool {
        try {
            // Create a simple request to test the connection
            $testModel = $model ?? $this->config->getDefaultModel();
            $request = new LlmRequest(
                [['role' => 'user', 'content' => 'Hello']],
                [],
                ['model' => $testModel, 'max_tokens' => 5]
            );
            
            $response = $this->sendMessage($request);
            return !$response->isError();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Build the HTTP headers for the request
     *
     * @return array The HTTP headers
     */
    protected function buildHeaders(): array {
        return [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->apiKey,
        ];
    }

    /**
     * Make an HTTP request
     *
     * @param string $url     The URL to request
     * @param string $method  The HTTP method
     * @param array  $headers The HTTP headers
     * @param array  $data    The request data
     * @return array The response data
     * @throws \Exception If the request fails
     */
    protected function makeHttpRequest(string $url, string $method = 'POST', array $headers = [], array $data = []): array {
        // Log the request details
        if (function_exists('error_log')) {
            \MemberpressAiAssistant\Utilities\debug_log('MPAI Debug - HTTP Request: ' . $method . ' ' . $url);
            \MemberpressAiAssistant\Utilities\debug_log('MPAI Debug - HTTP Headers: ' . json_encode($headers));
            
            // Don't log the full body as it might contain sensitive information
            \MemberpressAiAssistant\Utilities\debug_log('MPAI Debug - HTTP Body Size: ' . (empty($data) ? 0 : strlen(json_encode($data))) . ' bytes');
        }
        
        $args = [
            'method'  => $method,
            'headers' => $headers,
            'timeout' => 30,
        ];

        if (!empty($data) && $method === 'POST') {
            $args['body'] = json_encode($data);
        }

        // Make the request
        $start_time = microtime(true);
        $response = wp_remote_request($url, $args);
        $request_time = microtime(true) - $start_time;
        
        if (function_exists('error_log')) {
            \MemberpressAiAssistant\Utilities\debug_log('MPAI Debug - HTTP Request completed in ' . round($request_time, 2) . ' seconds');
        }

        // Handle WP_Error
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            $error_code = $response->get_error_code();
            
            if (function_exists('error_log')) {
                \MemberpressAiAssistant\Utilities\debug_log('MPAI Debug - HTTP Request WP_Error: ' . $error_message . ' (Code: ' . $error_code . ')');
            }
            
            throw new \Exception('HTTP Request Error: ' . $error_message . ' (Code: ' . $error_code . ')', 0);
        }

        // Get response details
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if (function_exists('error_log')) {
            \MemberpressAiAssistant\Utilities\debug_log('MPAI Debug - HTTP Response Code: ' . $response_code);
            \MemberpressAiAssistant\Utilities\debug_log('MPAI Debug - HTTP Response Size: ' . strlen($response_body) . ' bytes');
        }
        
        // Parse JSON response
        $response_data = json_decode($response_body, true);
        
        // Check for JSON parsing errors
        if ($response_data === null && json_last_error() !== JSON_ERROR_NONE) {
            $json_error = json_last_error_msg();
            
            if (function_exists('error_log')) {
                \MemberpressAiAssistant\Utilities\debug_log('MPAI Debug - JSON Parse Error: ' . $json_error);
                \MemberpressAiAssistant\Utilities\debug_log('MPAI Debug - Response Body: ' . substr($response_body, 0, 1000) . '...');
            }
            
            throw new \Exception('Invalid JSON response: ' . $json_error);
        }

        // Handle error responses
        if ($response_code >= 400) {
            $error_message = isset($response_data['error']['message'])
                ? $response_data['error']['message']
                : (isset($response_data['error']) ? (is_string($response_data['error']) ? $response_data['error'] : json_encode($response_data['error'])) : 'Unknown error');
            
            if (function_exists('error_log')) {
                \MemberpressAiAssistant\Utilities\debug_log('MPAI Debug - HTTP Error Response: ' . $error_message);
                \MemberpressAiAssistant\Utilities\debug_log('MPAI Debug - Full Error Response: ' . json_encode($response_data));
            }
                
            throw new \Exception($error_message, $response_code);
        }

        return $response_data;
    }

    /**
     * Get the model to use for a request
     *
     * @param LlmRequest $request The request
     * @return string The model to use
     */
    protected function getModelForRequest(LlmRequest $request): string {
        $model = $request->getOption('model', $this->config->getDefaultModel());
        
        // If the model is not available, use the default model
        if (!$this->config->isModelAvailable($model)) {
            $model = $this->config->getDefaultModel();
        }
        
        return $model;
    }

    /**
     * Get the temperature to use for a request
     *
     * @param LlmRequest $request The request
     * @return float The temperature to use
     */
    protected function getTemperatureForRequest(LlmRequest $request): float {
        return $request->getOption('temperature', $this->config->getDefaultTemperature());
    }

    /**
     * Get the max tokens to use for a request
     *
     * @param LlmRequest $request The request
     * @return int|null The max tokens to use
     */
    protected function getMaxTokensForRequest(LlmRequest $request): ?int {
        return $request->getOption('max_tokens', $this->config->getDefaultMaxTokens());
    }
}