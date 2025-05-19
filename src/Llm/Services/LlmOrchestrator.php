<?php
/**
 * LLM Orchestrator
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Llm\Services;

use MemberpressAiAssistant\Llm\ValueObjects\LlmRequest;
use MemberpressAiAssistant\Llm\ValueObjects\LlmResponse;
use MemberpressAiAssistant\Llm\Interfaces\LlmClientInterface;

/**
 * Orchestrator for LLM requests
 */
class LlmOrchestrator {
    /**
     * Provider factory
     *
     * @var LlmProviderFactory
     */
    private $providerFactory;

    /**
     * Cache adapter
     *
     * @var LlmCacheAdapter
     */
    private $cacheAdapter;

    /**
     * Primary provider name
     *
     * @var string
     */
    private $primaryProvider = 'openai';

    /**
     * Fallback provider name
     *
     * @var string|null
     */
    private $fallbackProvider = 'anthropic';

    /**
     * Tool operations that should always use OpenAI
     *
     * @var array
     */
    private $openaiToolOperations = [
        'list_plugins',
        'list_posts',
        'list_users',
        'list_terms',
        // Add other structured data operations here
    ];

    /**
     * Constructor
     *
     * @param LlmProviderFactory $providerFactory The provider factory
     * @param LlmCacheAdapter    $cacheAdapter    The cache adapter
     */
    public function __construct(LlmProviderFactory $providerFactory, LlmCacheAdapter $cacheAdapter) {
        $this->providerFactory = $providerFactory;
        $this->cacheAdapter = $cacheAdapter;
        
        // Load primary provider from settings
        $settings = get_option('mpai_settings', []);
        if (!empty($settings) && isset($settings['primary_api'])) {
            $this->primaryProvider = $settings['primary_api'];
            if (function_exists('error_log')) {
                \MemberpressAiAssistant\Utilities\debug_log('MPAI Debug - Loaded primary provider from settings: ' . $this->primaryProvider);
            }
        } else {
            // Fallback to old option name for backward compatibility
            $this->primaryProvider = get_option('mpai_primary_provider', 'openai');
            if (function_exists('error_log')) {
                \MemberpressAiAssistant\Utilities\debug_log('MPAI Debug - Loaded primary provider from old option: ' . $this->primaryProvider);
            }
        }
    }

    /**
     * Process a request
     *
     * @param LlmRequest $request The request to process
     * @return LlmResponse The response
     */
    public function processRequest(LlmRequest $request): LlmResponse {
        // Add detailed logging
        if (function_exists('error_log')) {
            \MemberpressAiAssistant\Utilities\debug_log('MPAI Debug - LlmOrchestrator processing request: ' . json_encode([
                'messages' => $request->getMessages(),
                'tools' => $request->getTools(),
                'options' => $request->getOptions()
            ]));
        }
        
        // Check if this is a tool call that should use OpenAI
        $providerName = $request->getOption('provider', $this->primaryProvider);
        
        // If request contains tools, check if any are in the OpenAI-only list
        if ($request->getTools() && $this->shouldUseOpenAiForTools($request->getTools())) {
            $providerName = 'openai';
            
            // Log that we're overriding the provider
            if (function_exists('error_log')) {
                \MemberpressAiAssistant\Utilities\debug_log('MPAI Debug - Overriding provider to OpenAI for tool operations');
            }
        }
        
        if (function_exists('error_log')) {
            \MemberpressAiAssistant\Utilities\debug_log('MPAI Debug - Using provider: ' . $providerName);
        }
        
        // Check if we have a cached response
        $cachedResponse = $this->cacheAdapter->getCachedResponse($request, $providerName);
        if ($cachedResponse !== null) {
            if (function_exists('error_log')) {
                \MemberpressAiAssistant\Utilities\debug_log('MPAI Debug - Using cached response');
            }
            return $cachedResponse;
        }
        
        try {
            // Execute the request
            if (function_exists('error_log')) {
                \MemberpressAiAssistant\Utilities\debug_log('MPAI Debug - Executing request with provider: ' . $providerName);
            }
            
            $response = $this->executeRequest($request, $providerName);
            
            if (function_exists('error_log')) {
                \MemberpressAiAssistant\Utilities\debug_log('MPAI Debug - Got response: ' . ($response->isError() ? 'ERROR' : 'SUCCESS'));
                \MemberpressAiAssistant\Utilities\debug_log('MPAI Debug - Response content: ' . $response->getContent());
            }
            
            // Cache the response if it's not an error
            if (!$response->isError()) {
                $this->cacheAdapter->cacheResponse($request, $providerName, $response);
            } else if ($this->fallbackProvider !== null && $this->fallbackProvider !== $providerName) {
                // Try fallback provider if available
                if (function_exists('error_log')) {
                    \MemberpressAiAssistant\Utilities\debug_log('MPAI Debug - Trying fallback provider: ' . $this->fallbackProvider);
                }
                return $this->handleFallback($request, $response);
            }
            
            return $response;
        } catch (\Exception $e) {
            // Log the error
            if (function_exists('error_log')) {
                \MemberpressAiAssistant\Utilities\debug_log('MPAI Debug - LlmOrchestrator error: ' . $e->getMessage());
                \MemberpressAiAssistant\Utilities\debug_log('MPAI Debug - Error trace: ' . $e->getTraceAsString());
            }
            
            // Handle error
            $errorResponse = LlmResponse::fromError($e, $providerName);
            
            // Try fallback provider if available
            if ($this->fallbackProvider !== null && $this->fallbackProvider !== $providerName) {
                if (function_exists('error_log')) {
                    \MemberpressAiAssistant\Utilities\debug_log('MPAI Debug - Trying fallback provider after error: ' . $this->fallbackProvider);
                }
                return $this->handleFallback($request, $errorResponse);
            }
            
            return $errorResponse;
        }
    }

    /**
     * Execute a request with a specific provider
     *
     * @param LlmRequest $request      The request to execute
     * @param string     $providerName The provider name
     * @return LlmResponse The response
     * @throws \Exception If the provider is not available
     */
    private function executeRequest(LlmRequest $request, string $providerName): LlmResponse {
        // Check if the provider is available
        if (!$this->providerFactory->hasProvider($providerName)) {
            throw new \Exception("Provider '$providerName' is not available");
        }
        
        // Create the provider
        $provider = $this->providerFactory->createProvider($providerName);
        
        // Send the request
        return $provider->sendMessage($request);
    }

    /**
     * Handle fallback to another provider
     *
     * @param LlmRequest  $request        The original request
     * @param LlmResponse $originalResponse The original response
     * @return LlmResponse The fallback response
     */
    private function handleFallback(LlmRequest $request, LlmResponse $originalResponse): LlmResponse {
        try {
            // Log the fallback
            $this->logFallback($request, $originalResponse);
            
            // Check if we have a cached response for the fallback provider
            $cachedResponse = $this->cacheAdapter->getCachedResponse($request, $this->fallbackProvider);
            if ($cachedResponse !== null) {
                return $cachedResponse;
            }
            
            // Execute the request with the fallback provider
            $fallbackResponse = $this->executeRequest($request, $this->fallbackProvider);
            
            // Cache the response if it's not an error
            if (!$fallbackResponse->isError()) {
                $this->cacheAdapter->cacheResponse($request, $this->fallbackProvider, $fallbackResponse);
            }
            
            return $fallbackResponse;
        } catch (\Exception $e) {
            // If fallback also fails, return the original error
            return $originalResponse;
        }
    }

    /**
     * Log a fallback
     *
     * @param LlmRequest  $request  The request
     * @param LlmResponse $response The original response
     * @return void
     */
    private function logFallback(LlmRequest $request, LlmResponse $response): void {
        // Log the fallback if a logger is available
        if (function_exists('mpai_log_info')) {
            mpai_log_info(
                "Falling back to {$this->fallbackProvider} due to error with {$response->getProvider()}",
                'llm-orchestrator',
                [
                    'error' => $response->getErrorMessage(),
                    'provider' => $response->getProvider(),
                    'fallback_provider' => $this->fallbackProvider,
                ]
            );
        }
    }

    /**
     * Set the primary provider
     *
     * @param string $providerName The provider name
     * @return self
     */
    public function withProvider(string $providerName): self {
        $this->primaryProvider = $providerName;
        return $this;
    }

    /**
     * Set the fallback provider
     *
     * @param string|null $providerName The provider name or null to disable fallback
     * @return self
     */
    public function withFallback(?string $providerName): self {
        $this->fallbackProvider = $providerName;
        return $this;
    }

    /**
     * Get the primary provider name
     *
     * @return string The primary provider name
     */
    public function getPrimaryProvider(): string {
        return $this->primaryProvider;
    }

    /**
     * Get the fallback provider name
     *
     * @return string|null The fallback provider name
     */
    public function getFallbackProvider(): ?string {
        return $this->fallbackProvider;
    }

    /**
     * Check if the request should use OpenAI for tools
     *
     * @param array $tools The tools in the request
     * @return bool Whether to use OpenAI
     */
    private function shouldUseOpenAiForTools(array $tools): bool {
        foreach ($tools as $tool) {
            $toolName = $tool['name'] ?? '';
            // Check if the tool name contains any of the OpenAI-only operations
            foreach ($this->openaiToolOperations as $operation) {
                if (strpos($toolName, $operation) !== false) {
                    return true;
                }
            }
        }
        return false;
    }
}