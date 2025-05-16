<?php
/**
 * Service Provider
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\DI;

use MemberpressAiAssistant\Interfaces\ServiceInterface;
use ReflectionClass;
use ReflectionMethod; // Add this use statement

/**
 * Service provider for registering services with the container
 */
class ServiceProvider {
    /**
     * The registered services
     *
     * @var array
     */
    protected $services = [];

    /**
     * The booted services
     *
     * @var array
     */
    protected $booted = [];

    /**
     * Register the service provider with the container
     *
     * @param Container $container The DI container
     * @return void
     */
    public function register(Container $container): void {
        // Register core services
        $this->registerCoreServices($container);

        // Register custom services
        $this->registerCustomServices($container);

        // Boot services
        $this->bootServices($container);
    }

    /**
     * Register core services with the container
     *
     * @param Container $container The DI container
     * @return void
     */
    protected function registerCoreServices(Container $container): void {
        // Register the container as a singleton
        $container->singleton(Container::class, function() use ($container) {
            return $container;
        });

        // Register the service provider as a singleton
        $container->singleton(ServiceProvider::class, function() {
            return $this;
        });

        // Register logger service
        $container->singleton('logger', function() {
            // Simple logger implementation
            return new class {
                public function info($message, array $context = []) {
                    // Log to WordPress debug log if enabled
                    if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                        error_log(sprintf('[INFO] %s: %s %s', 
                            date('Y-m-d H:i:s'),
                            $message,
                            !empty($context) ? json_encode($context) : ''
                        ));
                    }
                }

                public function error($message, array $context = []) {
                    // Log to WordPress debug log if enabled
                    if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                        error_log(sprintf('[ERROR] %s: %s %s', 
                            date('Y-m-d H:i:s'),
                            $message,
                            !empty($context) ? json_encode($context) : ''
                        ));
                    }
                }
            };
        });
    }

    /**
     * Register custom services with the container
     *
     * @param Container $container The DI container
     * @return void
     */
    protected function registerCustomServices(Container $container): void {
        // Discover services (instantiates and sets dependencies)
        $this->discoverServices($container);

        // Register services (calls the register method on each service)
        // This ensures services can perform container-specific registration
        // after they have been fully instantiated and dependencies are set.
        foreach ($this->services as $service) {
            if ($service instanceof ServiceInterface) {
                $service->register($container);
            }
        }
    }

    /**
     * Boot the registered services
     *
     * @param Container $container The DI container
     * @return void
     */
    protected function bootServices(Container $container): void {
        foreach ($this->services as $service) {
            if ($service instanceof ServiceInterface && !in_array($service->getServiceName(), $this->booted)) {
                // Boot the service
                $service->boot();
                
                // Mark as booted
                $this->booted[] = $service->getServiceName();
            }
        }
    }

    /**
     * Discover services, instantiate them, and set dependencies.
     *
     * @param Container $container The DI container
     * @return void
     */
    protected function discoverServices(Container $container): void {
        $logger = $container->make('logger');

        // Phase 1: Instantiate all services first to ensure they exist in the container
        // before attempting to resolve dependencies for set_dependencies
        $this->instantiateServicesInDirectory($container, 'Services', $logger);
        $this->instantiateServicesInDirectory($container, 'Admin', $logger);

        // Phase 2: Set dependencies for services that require them via set_dependencies
        $this->resolveAndSetDependencies($container, $logger);
    }

    /**
     * Instantiate services in a specific directory.
     *
     * @param Container $container The DI container
     * @param string $directory The directory name (relative to src/)
     * @param object $logger The logger instance
     * @return void
     */
    protected function instantiateServicesInDirectory(Container $container, string $directory, $logger): void {
        $dir_path = MPAI_PLUGIN_DIR . 'src/' . $directory;
        if (!is_dir($dir_path)) {
            return;
        }
        $files = glob($dir_path . '/*.php');

        foreach ($files as $file) {
            $filename = basename($file, '.php');
            // Skip abstract classes or interfaces if named like typical files
            if (str_starts_with($filename, 'Abstract') || str_ends_with($filename, 'Interface')) {
                 $logger->info('Skipping potential abstract/interface file: ' . $filename);
                continue;
            }
            $class = 'MemberpressAiAssistant\\' . $directory . '\\' . $filename;

            if (class_exists($class)) {
                $reflection = new ReflectionClass($class);
                // Ensure it's not an abstract class or interface before trying to instantiate
                if (!$reflection->isAbstract() && !$reflection->isInterface() && $reflection->implementsInterface(ServiceInterface::class)) {
                    try {
                        // Use the container to resolve constructor dependencies
                        $service = $container->make($class);

                        // Store the service instance using a determined name
                        $serviceName = $this->determineServiceName($service, $filename);
                        $this->services[$serviceName] = $service;

                        // Register the service instance with the container by class name and service name
                        // This makes it available for dependency resolution later
                        if (!$container->bound($class)) { // Use bound() instead of has()
                            // Use singleton() to register the existing instance
                            $container->singleton($class, function() use ($service) { return $service; });
                        }
                        if ($serviceName !== $class && !$container->bound($serviceName)) { // Use bound() instead of has()
                             // Use singleton() to register the existing instance with its determined name
                            $container->singleton($serviceName, function() use ($service) { return $service; });
                        }
                         $logger->info('Instantiated and registered service: ' . $class . ' as ' . $serviceName);

                    } catch (\Exception $e) {
                        $logger->error('Failed to instantiate service: ' . $class, [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                } elseif (!$reflection->implementsInterface(ServiceInterface::class)) {
                     $logger->info('Skipping class (does not implement ServiceInterface): ' . $class);
                }
            }
        }
    }

    /**
     * Resolve and set dependencies for services using the set_dependencies method.
     *
     * @param Container $container The DI container
     * @param object $logger The logger instance
     * @return void
     */
    protected function resolveAndSetDependencies(Container $container, $logger): void {
        foreach ($this->services as $serviceName => $service) {
            if (method_exists($service, 'set_dependencies')) {
                try {
                    $method = new ReflectionMethod($service, 'set_dependencies');
                    $params = $method->getParameters();
                    $dependenciesToInject = [];

                    foreach ($params as $param) {
                        $type = $param->getType();
                        // Ensure type is ReflectionNamedType and not built-in
                        if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                            $dependencyClassOrName = $type->getName();
                            // Resolve the dependency from the container
                            // Use class name or registered service name
                            $dependenciesToInject[] = $container->make($dependencyClassOrName);
                             $logger->info('Resolving dependency ' . $dependencyClassOrName . ' for ' . get_class($service));
                        } else {
                            $paramName = $param->getName();
                            $className = get_class($service);
                             $logger->error("Cannot resolve dependency for parameter '{$paramName}' in {$className}::set_dependencies. Is it type-hinted correctly?");
                            throw new \Exception("Cannot resolve dependency '{$paramName}' for {$className}::set_dependencies.");
                        }
                    }

                    // Call set_dependencies with resolved dependencies
                    $service->set_dependencies(...$dependenciesToInject);
                     $logger->info('Successfully called set_dependencies for: ' . get_class($service));

                } catch (\Exception $e) {
                    $logger->error('Failed during set_dependencies for service: ' . get_class($service), [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                     // Re-throw or handle as appropriate for the application
                     // throw $e; 
                }
            }
        }
    }
    
    /**
     * Determine the service name. Prefers getServiceName() method, falls back to filename.
     *
     * @param object $service The service instance.
     * @param string $fallbackName The filename-based fallback name.
     * @return string The determined service name.
     */
    protected function determineServiceName(object $service, string $fallbackName): string {
        if (method_exists($service, 'getServiceName')) {
            return $service->getServiceName();
        }
        // Convert filename (e.g., MyCoolService) to snake_case (e.g., my_cool_service) as a convention
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $fallbackName));
    }

    /**
     * Get all registered services
     *
     * @return array
     */
    public function getServices(): array {
        return $this->services;
    }

    /**
     * Get a specific service by name
     *
     * @param string $name The service name
     * @return ServiceInterface|null
     */
    public function getService(string $name) {
        return $this->services[$name] ?? null;
    }

    /**
     * Check if a service is registered
     *
     * @param string $name The service name
     * @return bool
     */
    public function hasService(string $name): bool {
        return isset($this->services[$name]);
    }

    /**
     * Add a service
     *
     * @param string $name The service name
     * @param ServiceInterface $service The service instance
     * @return void
     */
    public function addService(string $name, ServiceInterface $service): void {
        $this->services[$name] = $service;
    }
}