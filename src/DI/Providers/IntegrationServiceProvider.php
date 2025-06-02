<?php
/**
 * Integration Service Provider
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\DI\Providers;

use MemberpressAiAssistant\DI\ServiceProvider;
use MemberpressAiAssistant\DI\ServiceLocator;
use MemberpressAiAssistant\Services\MemberPressService;

/**
 * Service provider for integration services
 */
class IntegrationServiceProvider extends ServiceProvider {
    /**
     * Register services with the service locator
     *
     * @param ServiceLocator $locator The service locator
     * @return void
     */
    public function register(ServiceLocator $locator): void {
        // Register MemberPress service
        $this->registerSingleton($locator, 'memberpress', function() use ($locator) {
            $logger = $locator->has('logger') ? $locator->get('logger') : null;
            $memberPressService = new MemberPressService('memberpress', $logger);
            
            // Register adapters and transformers
            $memberPressService->register($locator);
            
            // Set cache service if available
            if ($locator->has('cache')) {
                $memberPressService->setCacheService($locator->get('cache'));
            }
            
            return $memberPressService;
        });

        // Register adapters and transformers
        $this->registerAdapters($locator);
        $this->registerTransformers($locator);
    }

    /**
     * Register adapters with the service locator
     *
     * @param ServiceLocator $locator The service locator
     * @return void
     */
    protected function registerAdapters(ServiceLocator $locator): void {
        // These are already registered by the MemberPressService
        // but we're listing them here for clarity
    }

    /**
     * Register transformers with the service locator
     *
     * @param ServiceLocator $locator The service locator
     * @return void
     */
    protected function registerTransformers(ServiceLocator $locator): void {
        // These are already registered by the MemberPressService
        // but we're listing them here for clarity
    }

    /**
     * Get the services provided by the provider
     *
     * @return array Array of service names
     */
    public function provides(): array {
        // List of adapter services
        $adapterTypes = [
            'product',
            'user',
            'subscription',
            'transaction',
            'rule'
        ];
        
        $adapterServices = [];
        foreach ($adapterTypes as $type) {
            $adapterServices[] = "memberpress.adapters.{$type}";
        }
        
        // List of transformer services
        $transformerTypes = [
            'product',
            'user',
            'subscription',
            'transaction',
            'rule'
        ];
        
        $transformerServices = [];
        foreach ($transformerTypes as $type) {
            $transformerServices[] = "memberpress.transformers.{$type}";
        }
        
        // Combine all services
        return array_merge(
            ['memberpress'],
            $adapterServices,
            $transformerServices
        );
    }
}