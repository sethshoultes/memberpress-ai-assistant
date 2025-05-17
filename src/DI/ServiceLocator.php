<?php
/**
 * Service Locator for Dependency Injection
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\DI;

/**
 * Service Locator class that implements a simplified dependency injection system
 * with lazy loading and singleton support to avoid memory issues.
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
     * @param string $name       The service identifier
     * @param mixed  $definition The service definition (closure, class name, or object)
     * @param bool   $singleton  Whether the service should be a singleton
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
     * Get a service from the service locator
     *
     * @param string $name The service identifier
     * @return mixed The resolved service
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
     * Check if a service is registered
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
     * This method is primarily for debugging purposes
     *
     * @return array The service definitions
     */
    public function getDefinitions(): array {
        return $this->definitions;
    }

    /**
     * Get all resolved service instances
     * 
     * This method is primarily for debugging purposes
     *
     * @return array The service instances
     */
    public function getInstances(): array {
        return $this->instances;
    }

    /**
     * Register a service as a singleton
     * 
     * This is a convenience method that calls register() with singleton set to true
     *
     * @param string $name       The service identifier
     * @param mixed  $definition The service definition (closure, class name, or object)
     * @return void
     */
    public function singleton($name, $definition): void {
        $this->register($name, $definition, true);
    }

    /**
     * Register a service as a transient (non-singleton)
     * 
     * This is a convenience method that calls register() with singleton set to false
     *
     * @param string $name       The service identifier
     * @param mixed  $definition The service definition (closure, class name, or object)
     * @return void
     */
    public function transient($name, $definition): void {
        $this->register($name, $definition, false);
    }
}