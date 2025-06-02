<?php
/**
 * Dependency Injection Container
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\DI;

/**
 * Simple dependency injection container
 */
class Container {
    /**
     * The container bindings
     *
     * @var array
     */
    protected $bindings = [];

    /**
     * The resolved instances
     *
     * @var array
     */
    protected $instances = [];

    /**
     * Bind a type into the container
     *
     * @param string $abstract The abstract type
     * @param mixed $concrete The concrete implementation
     * @param bool $shared Whether the binding should be a singleton
     * @return void
     */
    public function bind(string $abstract, $concrete = null, bool $shared = false): void {
        // If no concrete type is given, use the abstract type
        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'shared' => $shared,
        ];

        // If we've already resolved this instance, remove it so it will be resolved again
        if (isset($this->instances[$abstract])) {
            unset($this->instances[$abstract]);
        }
    }

    /**
     * Register a shared binding in the container
     *
     * @param string $abstract The abstract type
     * @param mixed $concrete The concrete implementation
     * @return void
     */
    public function singleton(string $abstract, $concrete = null): void {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Resolve a type from the container
     *
     * @param string $abstract The abstract type
     * @param array $parameters Parameters to pass to the constructor
     * @return mixed
     */
    public function make(string $abstract, array $parameters = []) {
        // If we've already resolved this instance and it's shared, return it
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // If the type is not bound, just create a new instance
        $concrete = $abstract;

        // If the type is bound, get the concrete implementation
        if (isset($this->bindings[$abstract])) {
            $concrete = $this->bindings[$abstract]['concrete'];
        }

        // If the concrete type is a closure, execute it
        if ($concrete instanceof \Closure) {
            $instance = $concrete($this, $parameters);
        } else {
            // Otherwise, create a new instance
            $instance = $this->build($concrete, $parameters);
        }

        // If the type is bound as shared, store the instance
        if (isset($this->bindings[$abstract]) && $this->bindings[$abstract]['shared']) {
            $this->instances[$abstract] = $instance;
        }

        return $instance;
    }

    /**
     * Build a concrete type
     *
     * @param string $concrete The concrete type
     * @param array $parameters Parameters to pass to the constructor
     * @return mixed
     */
    protected function build(string $concrete, array $parameters = []) {
        // Create a reflection class for the concrete type
        $reflector = new \ReflectionClass($concrete);

        // If the type is not instantiable, throw an exception
        if (!$reflector->isInstantiable()) {
            throw new \Exception("Type {$concrete} is not instantiable");
        }

        // Get the constructor
        $constructor = $reflector->getConstructor();

        // If there is no constructor, just create a new instance
        if (is_null($constructor)) {
            return new $concrete;
        }

        // Get the constructor parameters
        $dependencies = $constructor->getParameters();

        // If there are no dependencies, just create a new instance
        if (empty($dependencies)) {
            return new $concrete;
        }

        // Build the dependencies
        $instances = $this->resolveDependencies($dependencies, $parameters);

        // Create a new instance with the dependencies
        return $reflector->newInstanceArgs($instances);
    }

    /**
     * Resolve all of the dependencies from the ReflectionParameters
     *
     * @param array $dependencies The dependencies
     * @param array $parameters Parameters to pass to the constructor
     * @return array
     */
    protected function resolveDependencies(array $dependencies, array $parameters): array {
        $results = [];

        foreach ($dependencies as $dependency) {
            // If the dependency is in the parameters, use it
            if (array_key_exists($dependency->name, $parameters)) {
                $results[] = $parameters[$dependency->name];
                continue;
            }

            // If the dependency has a type hint, resolve it
            $type = $dependency->getType();
            if (!is_null($type) && !$type->isBuiltin()) {
                $results[] = $this->make($type->getName());
                continue;
            }

            // If the dependency has a default value, use it
            if ($dependency->isDefaultValueAvailable()) {
                $results[] = $dependency->getDefaultValue();
                continue;
            }

            // Otherwise, throw an exception
            throw new \Exception("Unable to resolve dependency: {$dependency->name}");
        }

        return $results;
    }

    /**
     * Determine if a given type is bound
     *
     * @param string $abstract The abstract type
     * @return bool
     */
    public function bound(string $abstract): bool {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }

    /**
     * Get a registered binding
     *
     * @param string $abstract The abstract type
     * @return mixed
     */
    public function getBinding(string $abstract) {
        if (!$this->bound($abstract)) {
            return null;
        }

        return $this->bindings[$abstract] ?? null;
    }

    /**
     * Get all registered bindings
     *
     * @return array
     */
    public function getBindings(): array {
        return $this->bindings;
    }

    /**
     * Get all resolved instances
     *
     * @return array
     */
    public function getInstances(): array {
        return $this->instances;
    }
}