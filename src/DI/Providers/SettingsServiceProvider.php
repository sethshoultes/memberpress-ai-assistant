<?php
/**
 * Settings Service Provider
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\DI\Providers;

use MemberpressAiAssistant\DI\ServiceProvider;
use MemberpressAiAssistant\DI\ServiceLocator;
use MemberpressAiAssistant\Services\Settings\SettingsModelService;
use MemberpressAiAssistant\Services\Settings\SettingsViewService;
use MemberpressAiAssistant\Services\Settings\SettingsControllerService;

/**
 * Service provider for settings services
 */
class SettingsServiceProvider extends ServiceProvider {
    /**
     * Register services with the service locator
     *
     * @param ServiceLocator $locator The service locator
     * @return void
     */
    public function register(ServiceLocator $locator): void {
        // Register settings model service
        $this->registerSingleton($locator, 'settings.model', function() use ($locator) {
            $logger = $locator->get('logger');
            $service = new SettingsModelService('settings.model', $logger);
            $service->setServiceLocator($locator);
            return $service;
        });

        // Register settings view service
        $this->registerSingleton($locator, 'settings.view', function() use ($locator) {
            $logger = $locator->get('logger');
            $service = new SettingsViewService('settings.view', $logger);
            $service->setServiceLocator($locator);
            return $service;
        });

        // Register settings controller service
        $this->registerSingleton($locator, 'settings.controller', function() use ($locator) {
            $logger = $locator->get('logger');
            $service = new SettingsControllerService('settings.controller', $logger);
            $service->setServiceLocator($locator);
            return $service;
        });
    }

    /**
     * Get the services provided by the provider
     *
     * @return array Array of service names
     */
    public function provides(): array {
        return [
            'settings.model',
            'settings.view',
            'settings.controller'
        ];
    }

    /**
     * Boot the service provider
     * 
     * This method initializes services in the correct order
     *
     * @param ServiceLocator $locator The service locator
     * @return void
     */
    public function boot(ServiceLocator $locator): void {
        // Boot services in the correct order
        $locator->get('settings.model')->boot();
        $locator->get('settings.view')->boot();
        $locator->get('settings.controller')->boot();
    }
}