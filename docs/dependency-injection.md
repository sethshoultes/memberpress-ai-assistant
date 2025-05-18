# Dependency Injection System in MemberPress AI Assistant

This document provides a comprehensive overview of the dependency injection (DI) system implemented in the MemberPress AI Assistant plugin, including its components, usage patterns, and benefits.

## Overview

The MemberPress AI Assistant implements a robust dependency injection system that facilitates loose coupling, testability, and maintainability of the codebase. The DI system consists of two main components:

1. A full-featured Container with automatic dependency resolution
2. A simpler ServiceLocator with lazy loading and singleton support

These components work together to provide a flexible and powerful dependency management solution that is used throughout the plugin.

## Container

The [`Container`](../src/DI/Container.php) class implements a full-featured dependency injection container with automatic dependency resolution:

### Key Features

- **Binding Registration**: Register concrete implementations for abstract types
- **Singleton Support**: Option to register services as singletons (shared instances)
- **Automatic Resolution**: Uses reflection to automatically resolve dependencies
- **Parameter Overrides**: Allows passing explicit parameters to override automatic resolution

### Core Methods

- `bind($abstract, $concrete, $shared)`: Register a binding
- `singleton($abstract, $concrete)`: Register a singleton binding
- `make($abstract, $parameters)`: Resolve a type from the container
- `bound($abstract)`: Check if a type is bound
- `getBinding($abstract)`: Get a registered binding
- `getBindings()`: Get all registered bindings
- `getInstances()`: Get all resolved instances

### Dependency Resolution Process

The Container uses PHP's Reflection API to automatically resolve dependencies:

1. When `make()` is called, the Container checks if the requested type is already resolved (for singletons)
2. If not, it checks if there's a binding for the requested type
3. If there's a binding and it's a Closure, the Closure is executed to get the instance
4. Otherwise, the Container uses reflection to analyze the constructor parameters
5. For each parameter, it:
   - Uses an explicitly provided parameter if available
   - Recursively resolves class type-hinted dependencies
   - Uses default values for parameters with defaults
   - Throws an exception if a dependency cannot be resolved
6. Finally, it creates a new instance with the resolved dependencies
7. If the binding is a singleton, the instance is stored for future requests

### Example Usage

```php
// Create a container
$container = new Container();

// Register a binding
$container->bind('logger', 'MyLogger');

// Register a singleton
$container->singleton('database', function($container) {
    return new Database('localhost', 'username', 'password');
});

// Register a binding with a concrete implementation
$container->bind('Interfaces\UserRepository', 'Repositories\DatabaseUserRepository');

// Resolve a type
$userRepository = $container->make('Interfaces\UserRepository');

// Resolve with explicit parameters
$logger = $container->make('logger', ['level' => 'debug']);
```

## ServiceLocator

The [`ServiceLocator`](../src/DI/ServiceLocator.php) class provides a simpler service location pattern implementation:

### Key Features

- **Lazy Loading**: Services are only instantiated when requested
- **Singleton Management**: Services can be registered as singletons or transients
- **Flexible Definitions**: Supports closures, class names, or object instances

### Core Methods

- `register($name, $definition, $singleton)`: Register a service
- `get($name)`: Retrieve a service
- `has($name)`: Check if a service is registered
- `singleton($name, $definition)`: Register a singleton service
- `transient($name, $definition)`: Register a transient service
- `getDefinitions()`: Get all registered service definitions
- `getInstances()`: Get all resolved service instances

### Service Resolution Process

The ServiceLocator uses a simpler resolution process:

1. When `get()` is called, it checks if the service is already resolved (for singletons)
2. If not, it checks if the service is registered
3. If the definition is a Closure, it executes the Closure to get the instance
4. If the definition is a class name, it creates a new instance
5. Otherwise, it returns the definition as-is
6. If the service is registered as a singleton, the instance is stored for future requests

### Example Usage

```php
// Create a service locator
$serviceLocator = new ServiceLocator();

// Register a service with a class name
$serviceLocator->register('logger', 'MyLogger');

// Register a singleton with a closure
$serviceLocator->singleton('database', function($serviceLocator) {
    return new Database('localhost', 'username', 'password');
});

// Register a service with an object instance
$serviceLocator->register('config', new Configuration());

// Get a service
$logger = $serviceLocator->get('logger');
$database = $serviceLocator->get('database');
```

## Usage in the System

The dependency injection system is used extensively throughout the MemberPress AI Assistant:

### Service Registration

Services are typically registered during the plugin initialization:

```php
// Register core services
$container->singleton('cache_service', function($container) {
    return new CacheService();
});

$container->singleton('configuration_service', function($container) {
    return new ConfigurationService();
});

$container->singleton('context_manager', function($container) {
    return new ContextManager($container->make('cache_service'));
});
```

### Agent System Integration

The DI system is used extensively in the agent architecture:

1. **Agent Factory**:
   - Uses the ServiceLocator to create agent instances
   - Injects required dependencies into agents

2. **Agent Orchestrator**:
   - Receives its dependencies (ContextManager, AgentRegistry, etc.) through constructor injection
   - Uses the Container to resolve additional dependencies as needed

3. **Service Registration**:
   - Services like CacheService, ConfigurationService, etc. are registered with the container
   - These services are then injected into agents and tools as needed

### Tool System Integration

The DI system is also used in the tool architecture:

1. **Tool Registry**:
   - Uses the Container to create tool instances
   - Injects dependencies into tools

2. **Tool Wrapper**:
   - Receives its dependencies through constructor injection
   - Uses the Container to resolve additional dependencies as needed

### Example: Agent Factory with DI

```php
class AgentFactory {
    protected $container;
    protected $registry;

    public function __construct(ServiceLocator $serviceLocator, ?AgentRegistry $registry = null) {
        $this->container = $serviceLocator;
        $this->registry = $registry ?? AgentRegistry::getInstance();
    }

    public function createAgent(string $agentClass, array $parameters = []): AgentInterface {
        // Validate the agent class
        $this->validateAgentClass($agentClass);

        // Create the agent instance using the service locator
        $agent = $this->container->get($agentClass);

        return $agent;
    }
}
```

## Benefits of the DI System

The dependency injection system provides several benefits to the MemberPress AI Assistant:

1. **Loose Coupling**:
   - Components depend on abstractions rather than concrete implementations
   - Changes to one component don't require changes to dependent components

2. **Testability**:
   - Dependencies can be easily mocked for unit testing
   - Components can be tested in isolation

3. **Maintainability**:
   - Dependencies are explicit and centrally managed
   - Service configuration is centralized

4. **Flexibility**:
   - Implementations can be swapped without modifying dependent code
   - New features can be added with minimal changes to existing code

5. **Lifecycle Management**:
   - Singleton vs. transient instances are managed consistently
   - Resource cleanup is centralized

## Best Practices

When working with the dependency injection system in MemberPress AI Assistant, follow these best practices:

1. **Depend on Abstractions**:
   - Use interfaces or abstract classes as type hints
   - Avoid depending on concrete implementations

2. **Register Services Early**:
   - Register all services during plugin initialization
   - Use a consistent naming convention for service identifiers

3. **Prefer Constructor Injection**:
   - Inject dependencies through constructors
   - Use method injection only for optional dependencies

4. **Be Mindful of Circular Dependencies**:
   - Avoid circular dependencies between services
   - Use factories or providers to break dependency cycles

5. **Document Service Requirements**:
   - Document the dependencies of each service
   - Use PHPDoc to document constructor parameters

## Conclusion

The dependency injection system in MemberPress AI Assistant provides a robust foundation for managing dependencies and promoting good software design principles. By using this system consistently throughout the codebase, the plugin achieves a high degree of modularity, testability, and maintainability.

The combination of a full-featured Container with automatic dependency resolution and a simpler ServiceLocator with lazy loading provides flexibility in how dependencies are managed, allowing developers to choose the approach that best fits their needs.