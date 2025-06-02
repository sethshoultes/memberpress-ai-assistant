# Dependency Injection System Rewrite Plan

## Issue Summary

The MemberPress AI Assistant plugin is currently experiencing critical memory issues in its dependency injection (DI) system, leading to PHP memory exhaustion errors. The current system appears to have circular dependencies and inefficient memory management.

## Root Causes

After investigation, we've identified several potential root causes:

1. **Circular Dependencies**: The current DI system creates circular references between services, particularly in the settings components.

2. **Eager Loading**: All dependencies are loaded at once, rather than lazily loading them when needed.

3. **Complex Dependency Resolution**: The current implementation attempts to automatically resolve all dependencies, which creates complexity and potential infinite loops.

4. **Inefficient Memory Usage**: The current implementation may be storing multiple instances of the same service or creating copies unnecessarily.

5. **Deep Dependency Chains**: Services have deep chains of dependencies, where A depends on B depends on C, etc., creating a cascade of object instantiations.

## Proposed Solution

### 1. Simplified Service Locator Pattern

Replace the current dependency injection container with a simpler service locator pattern that:

- Registers services by name
- Instantiates services only when requested (lazy loading)
- Supports singleton services to avoid duplicate instances
- Has clear dependency paths to avoid circular references

### 2. Dependency Management Approach

- Use constructor injection only for required dependencies
- Use setter injection for optional dependencies
- Ensure no bi-directional dependencies between services
- Document the dependency graph for each service

### 3. Implementation Plan

#### Phase 1: Emergency Mode

- Create a simplified emergency mode for the plugin
- Bypass the current dependency injection system
- Provide basic functionality while rewrite is in progress

#### Phase 2: Service Locator Implementation

- Create a new `ServiceLocator` class with simplified registration and resolution
- Implement lazy loading of services
- Add support for service factories

#### Phase 3: Service Migration

- Rewrite services with clear dependency paths
- Refactor settings system to avoid circular dependencies
- Update service registration to use the new service locator

#### Phase 4: Testing and Validation

- Test memory usage with large datasets
- Verify no memory leaks or circular references
- Measure performance improvements

## Implementation Details

### Service Locator Class

```php
class ServiceLocator {
    protected $definitions = [];
    protected $instances = [];
    
    public function register($name, $definition, $singleton = true) {
        $this->definitions[$name] = [
            'definition' => $definition,
            'singleton' => $singleton
        ];
    }
    
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
    
    public function has($name) {
        return isset($this->definitions[$name]) || isset($this->instances[$name]);
    }
}
```

### Settings System Rewrite

The settings system should be refactored to use a clearer dependency structure:

1. **Settings Model**: Handles data storage/retrieval - has no dependencies
2. **Settings View**: Handles rendering - depends only on the model
3. **Settings Controller**: Handles logic - depends on model and view
4. **Settings Page**: Entry point - depends on controller

This creates a clear, one-way dependency path without circular references.

## Timeline and Resources

- **Phase 1 (Emergency Mode)**: 1 day - Complete
- **Phase 2 (Service Locator)**: 2 days
- **Phase 3 (Service Migration)**: 3-5 days
- **Phase 4 (Testing)**: 2 days

### Required Resources

- 1 Senior PHP Developer
- 1 QA Engineer for testing

## Success Criteria

- Plugin runs without memory exhaustion errors
- Admin settings page loads successfully
- All functionality works as expected
- Memory usage is reduced by at least 50%
- No circular dependencies in the system

## Risks and Mitigation

**Risk**: Some existing code may depend on the current DI implementation.
**Mitigation**: Create adapter classes to maintain backward compatibility where needed.

**Risk**: New system may introduce different bugs.
**Mitigation**: Comprehensive testing plan with specific focus on edge cases.

**Risk**: Migration may take longer than anticipated.
**Mitigation**: Continue to use emergency mode until migration is complete.

## Conclusion

The proposed rewrite of the dependency injection system will address the root cause of the memory issues by simplifying the architecture, removing circular dependencies, and implementing lazy loading. This will result in a more stable, efficient, and maintainable plugin.