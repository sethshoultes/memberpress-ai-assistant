<?php
/**
 * Abstract Service
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Abstracts;

use MemberpressAiAssistant\Interfaces\ServiceInterface;

/**
 * Abstract base class for all services
 */
abstract class AbstractService implements ServiceInterface {
    /**
     * Service name
     *
     * @var string
     */
    protected $name;

    /**
     * Service dependencies
     *
     * @var array
     */
    protected $dependencies = [];

    /**
     * Logger instance
     *
     * @var mixed
     */
    protected $logger;

    /**
     * Constructor
     *
     * @param string $name Service name
     * @param mixed $logger Logger instance
     */
    public function __construct(string $name, $logger = null) {
        $this->name = $name;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function getServiceName(): string {
        // Return a default name if $this->name is null
        return $this->name ?? get_class($this);
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies(): array {
        return $this->dependencies;
    }

    /**
     * {@inheritdoc}
     */
    public function boot(): void {
        // Log service boot
        if ($this->logger) {
            $this->logger->info('Booting service ' . $this->getServiceName());
        }
        
        // Base implementation - should be overridden by specific services
    }

    /**
     * Add hooks and filters for this service
     *
     * @return void
     */
    protected function addHooks(): void {
        // Base implementation - should be overridden by specific services
    }

    /**
     * Log service activity
     *
     * @param string $message The log message
     * @param array $context The log context
     * @return void
     */
    protected function log(string $message, array $context = []): void {
        if ($this->logger) {
            $this->logger->info($message, array_merge(['service' => $this->getServiceName()], $context));
        }
    }
}