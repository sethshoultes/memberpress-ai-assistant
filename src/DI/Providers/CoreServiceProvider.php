<?php
/**
 * Core Service Provider
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\DI\Providers;

use MemberpressAiAssistant\DI\ServiceProvider;
use MemberpressAiAssistant\DI\ServiceLocator;
use MemberpressAiAssistant\Services\ConfigurationService;

/**
 * Service provider for core services like logger and configuration
 */
class CoreServiceProvider extends ServiceProvider {
    /**
     * Register services with the service locator
     *
     * @param ServiceLocator $locator The service locator
     * @return void
     */
    public function register(ServiceLocator $locator): void {
        // Register logger service
        $this->registerSingleton($locator, 'logger', function() {
            // Simple logger implementation
            return new \stdClass();
        });

        // Register configuration service
        $this->registerSingleton($locator, 'configuration', function() use ($locator) {
            $logger = $locator->get('logger');
            $configService = new ConfigurationService('configuration', $logger);
            return $configService;
        });
    }

    /**
     * Get the services provided by the provider
     *
     * @return array Array of service names
     */
    public function provides(): array {
        return [
            'logger',
            'configuration'
        ];
    }
}