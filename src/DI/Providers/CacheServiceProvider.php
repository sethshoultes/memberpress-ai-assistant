<?php
/**
 * Cache Service Provider
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\DI\Providers;

use MemberpressAiAssistant\DI\ServiceProvider;
use MemberpressAiAssistant\DI\ServiceLocator;
use MemberpressAiAssistant\Services\CacheService;
use MemberpressAiAssistant\Services\CachedToolWrapper;
use MemberpressAiAssistant\Cache\AdvancedCacheStrategy;
use MemberpressAiAssistant\Cache\CacheWarmer;

/**
 * Service provider for cache-related services
 */
class CacheServiceProvider extends ServiceProvider {
    /**
     * Register services with the service locator
     *
     * @param ServiceLocator $locator The service locator
     * @return void
     */
    public function register(ServiceLocator $locator): void {
        // Register cache service
        $this->registerSingleton($locator, 'cache', function() use ($locator) {
            $logger = $locator->has('logger') ? $locator->get('logger') : null;
            $cacheService = new CacheService('cache', $logger);
            return $cacheService;
        });

        // Register advanced cache strategy
        $this->registerSingleton($locator, 'advanced_cache_strategy', function() use ($locator) {
            $cacheService = $locator->get('cache');
            $logger = $locator->has('logger') ? $locator->get('logger') : null;
            $cacheStrategy = new AdvancedCacheStrategy($cacheService, $logger);
            return $cacheStrategy;
        });

        // First register a dummy cache warmer that will be replaced later
        $this->registerSingleton($locator, 'cache_warmer', function() {
            // Return a placeholder that will be replaced
            return new \stdClass();
        });

        // Register cached tool wrapper
        $this->registerSingleton($locator, 'cached_tool_wrapper', function() use ($locator) {
            $cacheService = $locator->get('cache');
            $cacheStrategy = $locator->get('advanced_cache_strategy');
            $dummyCacheWarmer = $locator->get('cache_warmer');
            $configService = $locator->get('configuration');
            $logger = $locator->has('logger') ? $locator->get('logger') : null;
            
            $cachedToolWrapper = new CachedToolWrapper(
                $cacheService,
                $cacheStrategy,
                $dummyCacheWarmer, // This will be a dummy object for now
                $configService,
                $logger
            );
            
            return $cachedToolWrapper;
        });

        // Now replace the dummy cache warmer with the real one
        $locator->register('cache_warmer', function() use ($locator) {
            $cacheService = $locator->get('cache');
            $cacheStrategy = $locator->get('advanced_cache_strategy');
            $cachedToolWrapper = $locator->get('cached_tool_wrapper');
            $logger = $locator->has('logger') ? $locator->get('logger') : null;
            
            $cacheWarmer = new CacheWarmer(
                $cacheService,
                $cacheStrategy,
                $cachedToolWrapper,
                $logger
            );
            
            return $cacheWarmer;
        });
    }

    /**
     * Get the services provided by the provider
     *
     * @return array Array of service names
     */
    public function provides(): array {
        return [
            'cache',
            'advanced_cache_strategy',
            'cache_warmer',
            'cached_tool_wrapper'
        ];
    }
}