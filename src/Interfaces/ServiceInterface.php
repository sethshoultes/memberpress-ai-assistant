<?php
/**
 * Service Interface
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Interfaces;

/**
 * Interface for all services in the system
 */
interface ServiceInterface {
    /**
     * Register the service with the service locator
     *
     * @param \MemberpressAiAssistant\DI\ServiceLocator $serviceLocator The service locator
     * @return void
     */
    public function register($serviceLocator): void;

    /**
     * Boot the service
     *
     * @return void
     */
    public function boot(): void;

    /**
     * Get the service name
     *
     * @return string
     */
    public function getServiceName(): string;

    /**
     * Get the service dependencies
     *
     * @return array
     */
    public function getDependencies(): array;
}