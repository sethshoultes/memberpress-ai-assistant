<?php
/**
 * Service Provider Abstract Class
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\DI;

/**
 * Abstract ServiceProvider class that defines a standard interface for service providers
 * to register services with the ServiceLocator.
 */
abstract class ServiceProvider {
    /**
     * Register services with the service locator
     *
     * This method should be implemented by all service providers to register
     * their services with the service locator.
     *
     * @param ServiceLocator $locator The service locator
     * @return void
     */
    abstract public function register(ServiceLocator $locator): void;

    /**
     * Get the services provided by the provider
     *
     * This method should return an array of service names that the provider
     * registers with the service locator. This helps with dependency management
     * and avoiding circular dependencies.
     *
     * @return array Array of service names
     */
    abstract public function provides(): array;

    /**
     * Boot the service provider
     *
     * This method is called after all services have been registered.
     * It can be used for any initialization that depends on other services.
     *
     * This method is optional and can be overridden by service providers
     * that need to perform initialization after all services are registered.
     *
     * @param ServiceLocator $locator The service locator
     * @return void
     */
    public function boot(ServiceLocator $locator): void {
        // Default implementation does nothing
    }

    /**
     * Helper method to register a service as a singleton
     *
     * @param ServiceLocator $locator The service locator
     * @param string $name The service name
     * @param mixed $definition The service definition
     * @return void
     */
    protected function registerSingleton(ServiceLocator $locator, string $name, $definition): void {
        $locator->singleton($name, $definition);
    }

    /**
     * Helper method to register a service as a transient (non-singleton)
     *
     * @param ServiceLocator $locator The service locator
     * @param string $name The service name
     * @param mixed $definition The service definition
     * @return void
     */
    protected function registerTransient(ServiceLocator $locator, string $name, $definition): void {
        $locator->transient($name, $definition);
    }

    /**
     * Helper method to check if a service exists in the locator
     *
     * @param ServiceLocator $locator The service locator
     * @param string $name The service name
     * @return bool Whether the service exists
     */
    protected function hasService(ServiceLocator $locator, string $name): bool {
        return $locator->has($name);
    }

    /**
     * Helper method to get a service from the locator
     *
     * @param ServiceLocator $locator The service locator
     * @param string $name The service name
     * @return mixed The service instance
     */
    protected function getService(ServiceLocator $locator, string $name) {
        return $locator->get($name);
    }
}