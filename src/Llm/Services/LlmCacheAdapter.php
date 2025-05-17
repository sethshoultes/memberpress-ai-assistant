<?php
/**
 * LLM Cache Adapter
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Llm\Services;

use MemberpressAiAssistant\Llm\ValueObjects\LlmRequest;
use MemberpressAiAssistant\Llm\ValueObjects\LlmResponse;
use MemberpressAiAssistant\Services\CacheService;
use MemberpressAiAssistant\Services\ConfigurationService;

/**
 * Adapter for caching LLM responses
 */
class LlmCacheAdapter {
    /**
     * Cache service
     *
     * @var CacheService
     */
    private $cacheService;

    /**
     * Configuration service
     *
     * @var ConfigurationService
     */
    private $configService;

    /**
     * Constructor
     *
     * @param CacheService        $cacheService  The cache service
     * @param ConfigurationService $configService The configuration service
     */
    public function __construct(CacheService $cacheService, ConfigurationService $configService) {
        $this->cacheService = $cacheService;
        $this->configService = $configService;
    }

    /**
     * Get a cached response
     *
     * @param LlmRequest $request  The request
     * @param string     $provider The provider name
     * @return LlmResponse|null The cached response or null if not found
     */
    public function getCachedResponse(LlmRequest $request, string $provider): ?LlmResponse {
        if (!$this->shouldCache($request, $provider)) {
            return null;
        }
        
        $cacheKey = $this->generateCacheKey($request, $provider);
        $cachedData = $this->cacheService->get($cacheKey);
        
        if ($cachedData === null) {
            return null;
        }
        
        // Deserialize the cached response
        return unserialize($cachedData);
    }

    /**
     * Cache a response
     *
     * @param LlmRequest  $request  The request
     * @param string      $provider The provider name
     * @param LlmResponse $response The response to cache
     * @return bool True if the response was cached
     */
    public function cacheResponse(LlmRequest $request, string $provider, LlmResponse $response): bool {
        if (!$this->shouldCache($request, $provider) || $response->isError()) {
            return false;
        }
        
        $cacheKey = $this->generateCacheKey($request, $provider);
        $ttl = $this->getTtl($request, $provider);
        
        // Serialize the response for caching
        $serializedResponse = serialize($response);
        
        return $this->cacheService->set($cacheKey, $serializedResponse, $ttl);
    }

    /**
     * Generate a cache key for a request
     *
     * @param LlmRequest $request  The request
     * @param string     $provider The provider name
     * @return string The cache key
     */
    private function generateCacheKey(LlmRequest $request, string $provider): string {
        // Create a hash of the request messages and tools
        $requestData = [
            'messages' => $request->getMessages(),
            'tools' => $request->getTools(),
            'options' => $request->getOptions(),
            'provider' => $provider,
        ];
        
        $requestHash = md5(serialize($requestData));
        
        return "mpai_llm_response_{$provider}_{$requestHash}";
    }

    /**
     * Check if a request should be cached
     *
     * @param LlmRequest $request  The request
     * @param string     $provider The provider name
     * @return bool True if the request should be cached
     */
    private function shouldCache(LlmRequest $request, string $provider): bool {
        // Check if caching is enabled
        $cachingEnabled = $this->getConfigOption('mpai_enable_response_caching', true);
        
        if (!$cachingEnabled) {
            return false;
        }
        
        // Check if the request has the no_cache option
        if ($request->getOption('no_cache', false)) {
            return false;
        }
        
        // Check if the request has tools
        // We don't cache requests with tools because they might have side effects
        if (!empty($request->getTools())) {
            return false;
        }
        
        return true;
    }

    /**
     * Get the TTL for a cached response
     *
     * @param LlmRequest $request  The request
     * @param string     $provider The provider name
     * @return int The TTL in seconds
     */
    private function getTtl(LlmRequest $request, string $provider): int {
        // Get the TTL from the request options
        $ttl = $request->getOption('cache_ttl', null);
        
        if ($ttl !== null) {
            return (int) $ttl;
        }
        
        // Get the default TTL from the configuration
        $defaultTtl = $this->getConfigOption('mpai_response_cache_ttl', 3600); // 1 hour default
        
        return (int) $defaultTtl;
    }

    /**
     * Clear the cache for a provider
     *
     * @param string|null $provider The provider name or null for all providers
     * @return bool True if the cache was cleared
     */
    public function clearCache(?string $provider = null): bool {
        if ($provider === null) {
            // Clear all LLM response caches
            return $this->deletePatternFromCache('mpai_llm_response_');
        }
        
        // Clear cache for a specific provider
        return $this->deletePatternFromCache("mpai_llm_response_{$provider}_");
    }

    /**
     * Delete cache entries matching a pattern
     *
     * @param string $pattern The pattern to match
     * @return bool True if the cache was cleared
     */
    private function deletePatternFromCache(string $pattern): bool {
        // Use the deletePattern method if available
        if (method_exists($this->cacheService, 'deletePattern')) {
            return $this->cacheService->deletePattern($pattern) > 0;
        }
        
        // Fallback to manual deletion
        global $wpdb;
        $count = 0;
        
        // Get all transients with our prefix
        $sql = $wpdb->prepare(
            "SELECT option_name FROM $wpdb->options WHERE option_name LIKE %s",
            '_transient_' . $pattern . '%'
        );
        
        $transients = $wpdb->get_col($sql);
        
        foreach ($transients as $transient) {
            $key = str_replace('_transient_', '', $transient);
            if ($this->cacheService->delete($key)) {
                $count++;
            }
        }
        
        return $count > 0;
    }

    /**
     * Get a configuration option
     *
     * @param string $key     The option key
     * @param mixed  $default The default value
     * @return mixed The option value
     */
    private function getConfigOption(string $key, $default = null) {
        // Use the getWarmingConfigValue method if available
        if (method_exists($this->configService, 'getWarmingConfigValue')) {
            return $this->configService->getWarmingConfigValue($key, $default);
        }
        
        // Fallback to WordPress options
        return get_option($key, $default);
    }
}