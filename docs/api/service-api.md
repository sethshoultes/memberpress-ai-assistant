# Service API Documentation

## Overview

The Service API provides the business logic layer of the MemberPress Copilot. Services handle complex operations, integrate with external systems, manage data persistence, and coordinate between different components of the system.

## Core Concepts

### Service Interface

All services must implement the `ServiceInterface` which defines the core contract:

```php
namespace MemberpressAiAssistant\Interfaces;

interface ServiceInterface {
    public function register($serviceLocator): void;
    public function boot(): void;
    public function getServiceName(): string;
    public function getDependencies(): array;
}
```

### Abstract Service Base Class

The `AbstractService` class provides a foundation with:

- **Service Registration**: Integration with the service locator
- **Lifecycle Management**: Boot process and initialization
- **Dependency Management**: Declare and resolve dependencies
- **Logging Support**: Built-in logging capabilities
- **Hook System**: WordPress hook integration

## Creating Custom Services

### Step 1: Extend AbstractService

```php
<?php
namespace MyPlugin\Services;

use MemberpressAiAssistant\Abstracts\AbstractService;

class CustomService extends AbstractService {
    public function __construct($logger = null) {
        parent::__construct('custom_service', $logger);
        
        // Define dependencies
        $this->dependencies = [
            'cache_service',
            'configuration_service'
        ];
    }
}
```

### Step 2: Implement Service Registration

```php
public function register($serviceLocator): void {
    // Store service locator reference
    $this->serviceLocator = $serviceLocator;
    
    // Register this service
    $serviceLocator->register('custom_service', function() {
        return $this;
    });
    
    // Register any sub-services or components
    $this->registerComponents($serviceLocator);
    
    // Log registration
    $this->log('Custom service registered');
}

private function registerComponents($serviceLocator): void {
    // Register related components
    $serviceLocator->register('custom_component', function() {
        return new CustomComponent();
    });
}
```

### Step 3: Implement Service Boot Process

```php
public function boot(): void {
    parent::boot();
    
    // Initialize service components
    $this->initializeComponents();
    
    // Add WordPress hooks
    $this->addHooks();
    
    // Perform any startup tasks
    $this->performStartupTasks();
    
    $this->log('Custom service booted');
}

private function initializeComponents(): void {
    // Initialize any required components
    $this->cache = $this->serviceLocator->get('cache_service');
    $this->config = $this->serviceLocator->get('configuration_service');
}

protected function addHooks(): void {
    // Add WordPress actions and filters
    add_action('init', [$this, 'onWordPressInit']);
    add_filter('custom_filter', [$this, 'handleCustomFilter']);
    
    // Add AJAX handlers if needed
    add_action('wp_ajax_custom_action', [$this, 'handleAjaxRequest']);
}
```

### Step 4: Implement Business Logic

```php
public function processCustomOperation(array $data): array {
    try {
        // Validate input data
        $validationResult = $this->validateOperationData($data);
        if (!$validationResult['valid']) {
            return [
                'success' => false,
                'error' => 'validation_failed',
                'message' => 'Invalid data provided',
                'details' => $validationResult['errors']
            ];
        }
        
        // Check cache first
        $cacheKey = $this->generateCacheKey('custom_operation', $data);
        $cachedResult = $this->getCachedResult($cacheKey);
        
        if ($cachedResult !== null) {
            $this->log('Returning cached result for custom operation');
            return $cachedResult;
        }
        
        // Process the operation
        $result = $this->executeCustomOperation($data);
        
        // Cache the result
        $this->cacheResult($cacheKey, $result, 300); // 5 minutes
        
        $this->log('Custom operation completed successfully');
        return $result;
        
    } catch (\Exception $e) {
        $this->log('Custom operation failed: ' . $e->getMessage(), ['error' => true]);
        return [
            'success' => false,
            'error' => 'operation_failed',
            'message' => 'Operation could not be completed',
            'details' => ['exception' => $e->getMessage()]
        ];
    }
}

private function executeCustomOperation(array $data): array {
    // Implement your business logic here
    $result = $this->performDatabaseOperation($data);
    
    return [
        'success' => true,
        'data' => $result,
        'message' => 'Operation completed successfully'
    ];
}
```

## Service Registration Patterns

### Using Service Providers

```php
<?php
namespace MyPlugin\DI\Providers;

use MemberpressAiAssistant\DI\ServiceProvider;

class CustomServiceProvider extends ServiceProvider {
    public function register($container): void {
        // Register the main service
        $container->register('custom_service', function($container) {
            $logger = $container->has('logger') ? $container->get('logger') : null;
            $service = new CustomService($logger);
            
            // Inject dependencies
            if ($container->has('cache_service')) {
                $service->setCacheService($container->get('cache_service'));
            }
            
            return $service;
        });
        
        // Register related services
        $container->register('custom_helper', function() {
            return new CustomHelper();
        });
    }
    
    public function boot($container): void {
        // Boot services that need early initialization
        $container->get('custom_service')->boot();
    }
}
```

### Manual Registration

```php
// In your plugin initialization
global $mpai_service_locator;

$custom_service = new CustomService();
$custom_service->register($mpai_service_locator);
$custom_service->boot();
```

## Built-in Services

### MemberPressService

Provides integration with MemberPress functionality with advanced caching and adapter patterns:

```php
global $mpai_service_locator;
$memberpress_service = $mpai_service_locator->get('memberpress');

// Get membership data with caching
$membership = $memberpress_service->getMembership(123);

// Create new membership
$result = $memberpress_service->createMembership([
    'title' => 'Premium Plan',
    'price' => 29.99,
    'period' => 1,
    'period_type' => 'months'
]);

// Get user memberships with caching
$user_memberships = $memberpress_service->getUserMemberships(456);

// Manage pricing for memberships
$pricing_result = $memberpress_service->managePricing(123, [
    'price' => 39.99,
    'period' => 1,
    'period_type' => 'months'
]);
```

**Key Methods:**
- `getMembership($id)`, `createMembership($data)`, `updateMembership($id, $data)`
- `getSubscription($id)`, `cancelSubscription($id)`
- `getUserMemberships($user_id)`, `associateUserWithMembership($user_id, $membership_id)`
- `getAccessRules()`, `createAccessRule($data)`, `updateAccessRule($id, $data)`
- `managePricing($membership_id, $pricing_data)` - Advanced pricing management
- `updateUserRole($user_id, $role, $action)` - User role management
- `getUserPermissions($user_id)` - User permission retrieval

**Adapter System Integration:**

```php
// Access raw adapters for advanced operations
$user_adapter = $memberpress_service->getUserAdapter();
$subscription_adapter = $memberpress_service->getSubscriptionAdapter();

// Use transformers for data formatting
$transformer = $memberpress_service->getProductTransformer();
$formatted_data = $transformer->transform($raw_membership_data);
```

### ChatInterfaceService

Manages the chat interface and conversation flow:

```php
$chat_service = $mpai_service_locator->get('chat_interface');

// Process a chat request
$response = $chat_service->processChatRequest($request);

// Get conversation history
$history = $chat_service->getConversationHistory($conversation_id);

// Clear conversation
$chat_service->clearConversation($conversation_id);
```

**Key Methods:**
- `processChatRequest($request)`, `processMessage($message, $context)`
- `getConversationHistory($id)`, `saveConversationState($id, $state)`
- `formatResponse($response)`, `generateChatConfig($admin_context)`

### ConfigurationService

Handles system configuration and settings:

```php
$config_service = $mpai_service_locator->get('configuration');

// Get configuration values
$api_key = $config_service->get('openai_api_key');
$default_model = $config_service->get('default_ai_model', 'gpt-3.5-turbo');

// Set configuration values
$config_service->set('custom_setting', 'value');

// Get multiple settings
$settings = $config_service->getMultiple(['setting1', 'setting2']);
```

**Key Methods:**
- `get($key, $default)`, `set($key, $value)`, `delete($key)`
- `getMultiple($keys)`, `setMultiple($values)`
- `has($key)`, `getAll()`

### CacheService

Provides caching functionality across the system:

```php
$cache_service = $mpai_service_locator->get('cache');

// Cache data
$cache_service->set('cache_key', $data, 300); // 5 minutes TTL

// Retrieve cached data
$cached_data = $cache_service->get('cache_key');

// Delete cached data
$cache_service->delete('cache_key');

// Clear cache by pattern
$cache_service->deletePattern('user_data_*');
```

**Key Methods:**
- `get($key)`, `set($key, $value, $ttl)`, `delete($key)`
- `has($key)`, `clear()`, `deletePattern($pattern)`
- `increment($key)`, `decrement($key)`

### OrchestratorService

Coordinates between agents, tools, and other services:

```php
$orchestrator = $mpai_service_locator->get('orchestrator');

// Process a request through the orchestration system
$response = $orchestrator->processRequest($request, $context);

// Get available agents
$agents = $orchestrator->getAvailableAgents();

// Get agent for specific request
$agent = $orchestrator->selectAgent($request);
```

### Settings Services

The system includes specialized settings services following the MVC pattern:

#### SettingsControllerService

Handles settings page coordination and user interactions:

```php
$settings_controller = $mpai_service_locator->get('settings_controller');

// Handle settings update
$result = $settings_controller->handleSettingsUpdate($post_data);

// Get current tab
$current_tab = $settings_controller->getCurrentTab();

// Register custom settings tab
$settings_controller->registerTab('custom_tab', [
    'label' => 'Custom Settings',
    'callback' => 'render_custom_tab'
]);
```

#### SettingsModelService

Manages settings data persistence and validation:

```php
$settings_model = $mpai_service_locator->get('settings_model');

// Get setting value
$api_key = $settings_model->get('openai_api_key');

// Save setting with validation
$result = $settings_model->save('custom_setting', $value);

// Get all settings for a tab
$tab_settings = $settings_model->getTabSettings('general');
```

#### SettingsViewService

Handles settings page rendering and UI components:

```php
$settings_view = $mpai_service_locator->get('settings_view');

// Render settings page
$settings_view->render();

// Render specific tab
$settings_view->renderTab('api_settings');

// Render field
$settings_view->renderField('text', 'api_key', [
    'label' => 'API Key',
    'description' => 'Enter your API key'
]);
```

## Service Dependencies

### Dependency Declaration

```php
class CustomService extends AbstractService {
    protected $dependencies = [
        'cache_service',
        'configuration_service',
        'memberpress_service'
    ];
    
    public function boot(): void {
        parent::boot();
        
        // Resolve dependencies
        $this->cache = $this->serviceLocator->get('cache_service');
        $this->config = $this->serviceLocator->get('configuration_service');
        $this->memberpress = $this->serviceLocator->get('memberpress_service');
    }
}
```

### Dependency Injection

```php
// Constructor injection
public function __construct(
    CacheService $cache,
    ConfigurationService $config,
    $logger = null
) {
    parent::__construct('custom_service', $logger);
    $this->cache = $cache;
    $this->config = $config;
}

// Setter injection
public function setCacheService(CacheService $cache): void {
    $this->cache = $cache;
}

public function setConfigurationService(ConfigurationService $config): void {
    $this->config = $config;
}
```

### Lazy Dependency Resolution

```php
private function getCacheService(): CacheService {
    if (!$this->cache) {
        $this->cache = $this->serviceLocator->get('cache_service');
    }
    return $this->cache;
}

private function getConfigurationService(): ConfigurationService {
    if (!$this->config) {
        $this->config = $this->serviceLocator->get('configuration_service');
    }
    return $this->config;
}
```

## Caching in Services

### Basic Caching

```php
public function getExpensiveData($id): array {
    $cacheKey = "expensive_data_{$id}";
    
    // Try cache first
    $cached = $this->getCachedResult($cacheKey);
    if ($cached !== null) {
        return $cached;
    }
    
    // Generate data
    $data = $this->computeExpensiveOperation($id);
    
    // Cache for 1 hour
    $this->cacheResult($cacheKey, $data, 3600);
    
    return $data;
}

protected function getCachedResult(string $key) {
    return $this->cacheService ? $this->cacheService->get($key) : null;
}

protected function cacheResult(string $key, $data, int $ttl): void {
    if ($this->cacheService) {
        $this->cacheService->set($key, $data, $ttl);
    }
}
```

### Cache Invalidation

```php
public function updateUserData($userId, array $data): array {
    // Update the data
    $result = $this->performUpdate($userId, $data);
    
    if ($result['success']) {
        // Invalidate related cache entries
        $this->invalidateUserCache($userId);
    }
    
    return $result;
}

private function invalidateUserCache($userId): void {
    if ($this->cacheService) {
        $this->cacheService->deletePattern("user_data_{$userId}_*");
        $this->cacheService->delete("user_memberships_{$userId}");
        $this->cacheService->delete("user_permissions_{$userId}");
    }
}
```

## Error Handling in Services

### Custom Service Exceptions

```php
namespace MyPlugin\Exceptions;

class ServiceException extends \Exception {
    private $context;
    
    public function __construct(string $message, array $context = [], int $code = 0, \Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }
    
    public function getContext(): array {
        return $this->context;
    }
}
```

### Error Handling Patterns

```php
public function processUserRequest(array $request): array {
    try {
        // Validate request
        if (!$this->validateRequest($request)) {
            throw new ServiceException('Invalid request format', [
                'request' => $request
            ]);
        }
        
        // Process request
        $result = $this->executeRequest($request);
        
        return [
            'success' => true,
            'data' => $result
        ];
        
    } catch (ServiceException $e) {
        $this->log('Service error: ' . $e->getMessage(), [
            'error' => true,
            'context' => $e->getContext()
        ]);
        
        return [
            'success' => false,
            'error' => 'service_error',
            'message' => $e->getMessage()
        ];
        
    } catch (\Exception $e) {
        $this->log('Unexpected error: ' . $e->getMessage(), ['error' => true]);
        
        return [
            'success' => false,
            'error' => 'unexpected_error',
            'message' => 'An unexpected error occurred'
        ];
    }
}
```

## Service Events and Hooks

### WordPress Hook Integration

```php
protected function addHooks(): void {
    // WordPress actions
    add_action('wp_loaded', [$this, 'onWordPressLoaded']);
    add_action('user_register', [$this, 'onUserRegister']);
    
    // WordPress filters
    add_filter('memberpress_ai_process_request', [$this, 'processRequest'], 10, 2);
    
    // Custom actions
    add_action('mpai_service_ready', [$this, 'onServiceReady']);
    
    // AJAX handlers
    add_action('wp_ajax_custom_service_action', [$this, 'handleAjaxAction']);
    add_action('wp_ajax_nopriv_custom_service_action', [$this, 'handlePublicAjaxAction']);
}

public function onWordPressLoaded(): void {
    // Initialize after WordPress is fully loaded
    $this->initializeAfterWordPress();
}

public function onUserRegister($user_id): void {
    // Handle new user registration
    $this->setupDefaultUserSettings($user_id);
}
```

### Custom Service Events

```php
// Trigger custom events
public function processImportantOperation($data): array {
    // Before processing
    do_action('mpai_before_important_operation', $data, $this);
    
    $result = $this->executeOperation($data);
    
    // After processing
    do_action('mpai_after_important_operation', $result, $data, $this);
    
    return $result;
}

// Allow filtering of results
public function getProcessedData($input): array {
    $data = $this->processData($input);
    
    // Allow other plugins to modify the result
    return apply_filters('mpai_processed_data', $data, $input, $this);
}
```

## Testing Services

### Unit Testing

```php
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class CustomServiceTest extends TestCase {
    private CustomService $service;
    private MockObject $cacheService;
    private MockObject $serviceLocator;
    
    public function setUp(): void {
        $this->cacheService = $this->createMock(CacheService::class);
        $this->serviceLocator = $this->createMock(ServiceLocator::class);
        
        $this->service = new CustomService();
        $this->service->setCacheService($this->cacheService);
        $this->service->register($this->serviceLocator);
    }
    
    public function testProcessCustomOperation(): void {
        $data = ['key' => 'value'];
        
        $this->cacheService
            ->expects($this->once())
            ->method('get')
            ->willReturn(null);
            
        $this->cacheService
            ->expects($this->once())
            ->method('set');
            
        $result = $this->service->processCustomOperation($data);
        
        $this->assertTrue($result['success']);
    }
}
```

### Integration Testing

```php
class ServiceIntegrationTest extends TestCase {
    public function testServiceRegistration(): void {
        global $mpai_service_locator;
        
        $service = new CustomService();
        $service->register($mpai_service_locator);
        
        $this->assertTrue($mpai_service_locator->has('custom_service'));
        $this->assertInstanceOf(CustomService::class, $mpai_service_locator->get('custom_service'));
    }
    
    public function testServiceInteraction(): void {
        global $mpai_service_locator;
        
        $custom_service = $mpai_service_locator->get('custom_service');
        $memberpress_service = $mpai_service_locator->get('memberpress');
        
        // Test interaction between services
        $result = $custom_service->processWithMemberPress(['user_id' => 1]);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }
}
```

## Best Practices

### Service Design
1. **Single Responsibility**: Each service should handle a specific domain
2. **Loose Coupling**: Use dependency injection to avoid tight coupling
3. **Clear Contracts**: Define clear interfaces and method signatures
4. **Error Handling**: Implement comprehensive error handling and logging

### Performance
1. **Cache Strategically**: Cache expensive operations appropriately
2. **Lazy Loading**: Load dependencies only when needed
3. **Batch Operations**: Support batch processing where applicable
4. **Resource Management**: Properly manage database connections and external resources

### Integration
1. **Use Service Locator**: Access dependencies through the service locator
2. **Follow Conventions**: Use consistent naming and response formats
3. **Hook Integration**: Integrate with WordPress hooks appropriately
4. **Event Driven**: Use events for loose coupling between components

### Security
1. **Validate Input**: Always validate and sanitize input data
2. **Check Permissions**: Verify user permissions before operations
3. **Secure Configuration**: Store sensitive configuration securely
4. **Log Security Events**: Log authentication and authorization events

This Service API provides a robust foundation for building scalable business logic within the MemberPress Copilot system.