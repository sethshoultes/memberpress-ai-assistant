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
     * Constructor
     *
     * @param LlmProviderFactory $providerFactory The provider factory
     * @param LlmCacheAdapter    $cacheAdapter    The cache adapter
     */
    public function __construct(LlmProviderFactory $providerFactory, LlmCacheAdapter $cacheAdapter) {
        $this->providerFactory = $providerFactory;
        $this->cacheAdapter = $cacheAdapter;
        
        // Load primary provider from options
        $this->primaryProvider = get_option('mpai_primary_provider', 'openai');
    }

    /**
     * Process a request
     *
     * @param LlmRequest $request The request to process
     * @return LlmResponse The response
     */
    public function processRequest(LlmRequest $request): LlmResponse {
        // Get the provider to use
        $providerName = $request->getOption('provider', $this->primaryProvider);
        
        // Check if we have a cached response
        $cachedResponse = $this->cacheAdapter->getCachedResponse($request, $providerName);
        if ($cachedResponse !== null) {
            return $cachedResponse;
        }
        
        try {
            // Execute the request
            $response = $this->executeRequest($request, $providerName);
            
            // Cache the response if it's not an error
            if (!$response->isError()) {
                $this->cacheAdapter->cacheResponse($request, $providerName, $response);
            } else if ($this->fallbackProvider !== null && $this->fallbackProvider !== $providerName) {
                // Try fallback provider if available
                return $this->handleFallback($request, $response);
            }
            
            return $response;
        } catch (\Exception $e) {
            // Handle error
            $errorResponse = LlmResponse::fromError($e, $providerName);
            
            // Try fallback provider if available
            if ($this->fallbackProvider !== null && $this->fallbackProvider !== $providerName) {
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
}