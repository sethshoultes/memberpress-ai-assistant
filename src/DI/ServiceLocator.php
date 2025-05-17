<?php
/**
 * Service Locator
 *
 * A simplified service locator implementation that replaces the more complex
 * dependency injection container. This implementation focuses on lazy loading
 * and explicit dependency management to avoid memory issues.
 *
 * @package MemberpressAiAssistant\DI
 */

namespace MemberpressAiAssistant\DI;

/**
 * Service Locator class for managing service instances and definitions.
 *
 * This class implements a simplified service locator pattern that:
 * - Registers services by name
 * - Instantiates services only when requested (lazy loading)
 * - Supports singleton services to avoid duplicate instances
 * - Has clear dependency paths to avoid circular references
 */
class ServiceLocator {
    /**
     * The service definitions
     *
     * @var array
     */
    protected $definitions = [];

    /**
     * The resolved service instances
     *
     * @var array
     */
    protected $instances = [];

    /**
     * Register a service with the service locator
     *
     * @param string $name      The service identifier
     * @param mixed  $definition The service definition (closure, class name string, or object instance)
     * @param bool   $singleton Whether the service should be a singleton (default: true)
     * @return void
     */
    public function register($name, $definition, $singleton = true): void {
        $this->definitions[$name] = [
            'definition' => $definition,
            'singleton' => $singleton
        ];
        
        // Remove existing instance if re-registering
        if (isset($this->instances[$name])) {
            unset($this->instances[$name]);
        }
    }
    
    /**
     * Retrieve a service from the service locator
     *
     * @param string $name The service identifier
     * @return mixed The resolved service instance
     * @throws \Exception If the service is not registered
     */
    public function get($name) {
        // Return existing instance if it's a singleton and already resolved
        if (isset($this->instances[$name])) {
            return $this->instances[$name];
        }
        
        // Check if the service is registered
        if (!isset($this->definitions[$name])) {
            throw new \Exception("Service '$name' is not registered");
        }
        
        $def = $this->definitions[$name];
        
        // Resolve the service
        if ($def['definition'] instanceof \Closure) {
            $instance = $def['definition']($this);
        } elseif (is_string($def['definition']) && class_exists($def['definition'])) {
            $instance = new $def['definition']();
        } else {
            $instance = $def['definition'];
        }
        
        // Store the instance if it's a singleton
        if ($def['singleton']) {
            $this->instances[$name] = $instance;
        }
        
        return $instance;
    }
    
    /**
     * Check if a service is registered with the service locator
     *
     * @param string $name The service identifier
     * @return bool Whether the service is registered
     */
    public function has($name): bool {
        return isset($this->definitions[$name]) || isset($this->instances[$name]);
    }

    /**
     * Get all registered service definitions
     *
     * This method is primarily for debugging purposes to inspect
     * what services are registered and how they are configured.
     *
     * @return array All registered service definitions
     */
    public function getDefinitions(): array {
        return $this->definitions;
    }
}