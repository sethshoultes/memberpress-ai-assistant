<?php
/**
 * Configuration Service
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Services;

use MemberpressAiAssistant\Abstracts\AbstractService;

/**
 * Centralized configuration management service for MemberPress AI Assistant
 * 
 * This service centralizes configuration management for various components:
 * - Cache TTL configuration (from CachedToolWrapper)
 * - Non-cacheable operations (from CachedToolWrapper)
 * - Cache strategies (from AdvancedCacheStrategy)
 * - Operation characteristics (from AdvancedCacheStrategy)
 * - Cache warming configuration (from CacheWarmer)
 * - Warming operations (from CacheWarmer)
 */
class ConfigurationService extends AbstractService {
    /**
     * Default TTL in seconds (5 minutes)
     *
     * @var int
     */
    protected $defaultTtl = 300;

    /**
     * TTL configuration for different tool types
     *
     * @var array
     */
    protected $ttlConfig = [];

    /**
     * List of operations that should not be cached
     *
     * @var array
     */
    protected $nonCacheableOperations = [];

    /**
     * Strategy definitions with base TTL values (in seconds)
     *
     * @var array
     */
    protected $strategies = [];

    /**
     * Operation characteristics mapping
     * Maps operations to their characteristics for strategy selection
     *
     * @var array
     */
    protected $operationCharacteristics = [];

    /**
     * Warming configuration
     *
     * @var array
     */
    protected $warmingConfig = [];

    /**
     * Warming operations with priorities
     * Higher number means higher priority
     *
     * @var array
     */
    protected $warmingOperations = [];

    /**
     * Constructor
     *
     * @param string $name Service name
     * @param mixed $logger Logger instance
     */
    public function __construct(string $name = 'configuration', $logger = null) {
        parent::__construct($name, $logger);
        
        // Load default configurations
        $this->loadDefaultConfigurations();
    }

    /**
     * {@inheritdoc}
     */
    public function register($container): void {
        // Register this service with the container
        $container->singleton('configuration', function() {
            return $this;
        });

        // Log registration
        $this->log('Configuration service registered');
    }

    /**
     * {@inheritdoc}
     */
    public function boot(): void {
        parent::boot();
        
        // Add hooks
        $this->addHooks();
        
        // Log boot
        $this->log('Configuration service booted');
    }

    /**
     * Add hooks and filters for this service
     *
     * @return void
     */
    protected function addHooks(): void {
        // Hook to load configurations from WordPress options
        add_action('init', [$this, 'loadConfigurationsFromOptions']);
        
        // Hook to save configurations when they are updated
        add_action('memberpress_ai_assistant_update_configuration', [$this, 'saveConfigurationsToOptions']);
    }

    /**
     * Load default configurations
     *
     * @return void
     */
    protected function loadDefaultConfigurations(): void {
        $this->loadDefaultTtlConfig();
        $this->loadDefaultNonCacheableOperations();
        $this->loadDefaultStrategies();
        $this->loadDefaultOperationCharacteristics();
        $this->loadDefaultWarmingConfig();
        $this->loadDefaultWarmingOperations();
    }

    /**
     * Load configurations from WordPress options
     *
     * @return void
     */
    public function loadConfigurationsFromOptions(): void {
        // Load TTL config from options
        $ttlConfig = get_option('mpai_ttl_config', []);
        if (!empty($ttlConfig)) {
            $this->ttlConfig = $ttlConfig;
        }
        
        // Load non-cacheable operations from options
        $nonCacheableOperations = get_option('mpai_non_cacheable_operations', []);
        if (!empty($nonCacheableOperations)) {
            $this->nonCacheableOperations = $nonCacheableOperations;
        }
        
        // Load strategies from options
        $strategies = get_option('mpai_strategies', []);
        if (!empty($strategies)) {
            $this->strategies = $strategies;
        }
        
        // Load operation characteristics from options
        $operationCharacteristics = get_option('mpai_operation_characteristics', []);
        if (!empty($operationCharacteristics)) {
            $this->operationCharacteristics = $operationCharacteristics;
        }
        
        // Load warming config from options
        $warmingConfig = get_option('mpai_warming_config', []);
        if (!empty($warmingConfig)) {
            $this->warmingConfig = $warmingConfig;
        }
        
        // Load warming operations from options
        $warmingOperations = get_option('mpai_warming_operations', []);
        if (!empty($warmingOperations)) {
            $this->warmingOperations = $warmingOperations;
        }
        
        // Log configuration loading
        $this->log('Configurations loaded from WordPress options');
    }

    /**
     * Save configurations to WordPress options
     *
     * @return void
     */
    public function saveConfigurationsToOptions(): void {
        // Save TTL config to options
        update_option('mpai_ttl_config', $this->ttlConfig);
        
        // Save non-cacheable operations to options
        update_option('mpai_non_cacheable_operations', $this->nonCacheableOperations);
        
        // Save strategies to options
        update_option('mpai_strategies', $this->strategies);
        
        // Save operation characteristics to options
        update_option('mpai_operation_characteristics', $this->operationCharacteristics);
        
        // Save warming config to options
        update_option('mpai_warming_config', $this->warmingConfig);
        
        // Save warming operations to options
        update_option('mpai_warming_operations', $this->warmingOperations);
        
        // Log configuration saving
        $this->log('Configurations saved to WordPress options');
    }

    /**
     * Load default TTL configuration
     *
     * @return void
     */
    protected function loadDefaultTtlConfig(): void {
        $this->ttlConfig = [
            // Content tools have shorter TTL as content might change frequently
            'ContentTool' => [
                'default' => 300, // 5 minutes
                'format_content' => 600, // 10 minutes
                'optimize_seo' => 1800, // 30 minutes
            ],
            // MemberPress tools have longer TTL for read operations, shorter for write operations
            'MemberPressTool' => [
                'default' => 300, // 5 minutes
                'get_membership' => 600, // 10 minutes
                'list_memberships' => 600, // 10 minutes
                'get_member' => 300, // 5 minutes
                'list_members' => 300, // 5 minutes
                'get_transaction' => 600, // 10 minutes
                'list_transactions' => 300, // 5 minutes
            ],
            // WordPress tools have medium TTL
            'WordPressTool' => [
                'default' => 300, // 5 minutes
                'get_post' => 600, // 10 minutes
                'list_posts' => 300, // 5 minutes
                'get_option' => 1800, // 30 minutes
            ],
            // Default TTL for any other tool
            'default' => 300, // 5 minutes
        ];
    }

    /**
     * Load default non-cacheable operations
     *
     * @return void
     */
    protected function loadDefaultNonCacheableOperations(): void {
        $this->nonCacheableOperations = [
            // Content tool write operations
            'ContentTool.manage_revisions',
            // MemberPress tool write operations
            'MemberPressTool.create_membership',
            'MemberPressTool.update_membership',
            'MemberPressTool.delete_membership',
            'MemberPressTool.create_member',
            'MemberPressTool.update_member',
            'MemberPressTool.delete_member',
            'MemberPressTool.create_transaction',
            'MemberPressTool.update_transaction',
            'MemberPressTool.delete_transaction',
            // WordPress tool write operations
            'WordPressTool.create_post',
            'WordPressTool.update_post',
            'WordPressTool.delete_post',
            'WordPressTool.update_option',
            'WordPressTool.delete_option',
        ];
    }

    /**
     * Load default strategies
     *
     * @return void
     */
    protected function loadDefaultStrategies(): void {
        $this->strategies = [
            // For highly volatile data that changes very frequently
            'short_lived' => [
                'ttl' => 60, // 1 minute
                'description' => 'Short-lived cache for highly volatile data',
                'volatility' => 'high',
                'use_cases' => ['real-time data', 'frequently updated content', 'user-specific temporary data']
            ],
            
            // For moderately volatile data
            'medium_lived' => [
                'ttl' => 300, // 5 minutes
                'description' => 'Medium-lived cache for moderately volatile data',
                'volatility' => 'medium',
                'use_cases' => ['semi-dynamic content', 'user preferences', 'session-related data']
            ],
            
            // For relatively stable data
            'long_lived' => [
                'ttl' => 3600, // 1 hour
                'description' => 'Long-lived cache for stable data',
                'volatility' => 'low',
                'use_cases' => ['reference data', 'configuration settings', 'infrequently updated content']
            ],
            
            // For data that changes frequently but still benefits from short caching
            'volatile' => [
                'ttl' => 30, // 30 seconds
                'description' => 'Volatile cache for frequently changing data that still benefits from caching',
                'volatility' => 'very high',
                'use_cases' => ['rapidly changing metrics', 'status indicators', 'availability data']
            ],
            
            // For data that rarely changes
            'stable' => [
                'ttl' => 86400, // 24 hours
                'description' => 'Stable cache for rarely changing data',
                'volatility' => 'very low',
                'use_cases' => ['static content', 'historical data', 'archived information']
            ]
        ];
    }

    /**
     * Load default operation characteristics
     *
     * @return void
     */
    protected function loadDefaultOperationCharacteristics(): void {
        $this->operationCharacteristics = [
            // Content tool operations
            'ContentTool' => [
                'default' => ['volatility' => 'medium', 'access_frequency' => 'medium', 'resource_intensity' => 'medium'],
                'format_content' => ['volatility' => 'low', 'access_frequency' => 'high', 'resource_intensity' => 'medium'],
                'optimize_seo' => ['volatility' => 'low', 'access_frequency' => 'medium', 'resource_intensity' => 'high'],
                'analyze_content' => ['volatility' => 'low', 'access_frequency' => 'medium', 'resource_intensity' => 'high'],
                'generate_summary' => ['volatility' => 'low', 'access_frequency' => 'medium', 'resource_intensity' => 'high'],
                'manage_revisions' => ['volatility' => 'high', 'access_frequency' => 'low', 'resource_intensity' => 'medium'],
            ],
            
            // MemberPress tool operations
            'MemberPressTool' => [
                'default' => ['volatility' => 'medium', 'access_frequency' => 'medium', 'resource_intensity' => 'medium'],
                'get_membership' => ['volatility' => 'low', 'access_frequency' => 'high', 'resource_intensity' => 'low'],
                'list_memberships' => ['volatility' => 'low', 'access_frequency' => 'high', 'resource_intensity' => 'medium'],
                'get_member' => ['volatility' => 'medium', 'access_frequency' => 'high', 'resource_intensity' => 'low'],
                'list_members' => ['volatility' => 'medium', 'access_frequency' => 'medium', 'resource_intensity' => 'medium'],
                'get_transaction' => ['volatility' => 'low', 'access_frequency' => 'medium', 'resource_intensity' => 'low'],
                'list_transactions' => ['volatility' => 'medium', 'access_frequency' => 'medium', 'resource_intensity' => 'high'],
                'get_subscription' => ['volatility' => 'medium', 'access_frequency' => 'high', 'resource_intensity' => 'low'],
                'list_subscriptions' => ['volatility' => 'medium', 'access_frequency' => 'medium', 'resource_intensity' => 'high'],
            ],
            
            // WordPress tool operations
            'WordPressTool' => [
                'default' => ['volatility' => 'medium', 'access_frequency' => 'medium', 'resource_intensity' => 'medium'],
                'get_post' => ['volatility' => 'low', 'access_frequency' => 'high', 'resource_intensity' => 'low'],
                'list_posts' => ['volatility' => 'medium', 'access_frequency' => 'high', 'resource_intensity' => 'medium'],
                'get_option' => ['volatility' => 'very low', 'access_frequency' => 'very high', 'resource_intensity' => 'very low'],
                'get_user' => ['volatility' => 'low', 'access_frequency' => 'high', 'resource_intensity' => 'low'],
                'list_users' => ['volatility' => 'medium', 'access_frequency' => 'medium', 'resource_intensity' => 'medium'],
            ]
        ];
    }

    /**
     * Load default warming configuration
     *
     * @return void
     */
    protected function loadDefaultWarmingConfig(): void {
        $this->warmingConfig = [
            'batch_size' => 10,           // Number of items to warm in a single batch
            'interval' => 'hourly',       // WordPress cron schedule interval
            'max_execution_time' => 30,   // Maximum execution time in seconds
            'enabled' => true,            // Whether warming is enabled
        ];
    }

    /**
     * Load default warming operations
     *
     * @return void
     */
    protected function loadDefaultWarmingOperations(): void {
        $this->warmingOperations = [
            // MemberPress operations
            'MemberPressTool.get_membership' => [
                'priority' => 100,
                'params' => [],
                'frequency' => 'hourly',
            ],
            'MemberPressTool.list_memberships' => [
                'priority' => 90,
                'params' => [],
                'frequency' => 'hourly',
            ],
            'MemberPressTool.get_member' => [
                'priority' => 80,
                'params' => [],
                'frequency' => 'daily',
            ],
            'MemberPressTool.list_members' => [
                'priority' => 70,
                'params' => [],
                'frequency' => 'daily',
            ],
            'MemberPressTool.get_transaction' => [
                'priority' => 60,
                'params' => [],
                'frequency' => 'daily',
            ],
            'MemberPressTool.list_transactions' => [
                'priority' => 50,
                'params' => [],
                'frequency' => 'daily',
            ],
            
            // WordPress operations
            'WordPressTool.get_post' => [
                'priority' => 40,
                'params' => [],
                'frequency' => 'daily',
            ],
            'WordPressTool.list_posts' => [
                'priority' => 30,
                'params' => [],
                'frequency' => 'daily',
            ],
            'WordPressTool.get_option' => [
                'priority' => 20,
                'params' => [],
                'frequency' => 'daily',
            ],
        ];
    }

    /**
     * Get the default TTL
     *
     * @return int Default TTL in seconds
     */
    public function getDefaultTtl(): int {
        return $this->defaultTtl;
    }

    /**
     * Set the default TTL
     *
     * @param int $ttl TTL in seconds
     * @return self
     */
    public function setDefaultTtl(int $ttl): self {
        $this->defaultTtl = max(0, $ttl);
        return $this;
    }

    /**
     * Get the TTL configuration
     *
     * @return array TTL configuration
     */
    public function getTtlConfig(): array {
        return $this->ttlConfig;
    }

    /**
     * Get TTL configuration for a specific tool type
     *
     * @param string $toolType The tool type
     * @return array TTL configuration for the tool type
     */
    public function getToolTtlConfig(string $toolType): array {
        return $this->ttlConfig[$toolType] ?? ['default' => $this->defaultTtl];
    }

    /**
     * Set the TTL configuration for a specific tool type
     *
     * @param string $toolType The tool type
     * @param array $config TTL configuration for the tool type
     * @return self
     */
    public function setToolTtlConfig(string $toolType, array $config): self {
        $this->ttlConfig[$toolType] = $config;
        return $this;
    }

    /**
     * Set the TTL configuration
     *
     * @param array $config TTL configuration
     * @return self
     */
    public function setTtlConfig(array $config): self {
        $this->ttlConfig = $config;
        return $this;
    }

    /**
     * Get the TTL for a specific tool operation
     *
     * @param string $toolType The tool type
     * @param string $operation The operation
     * @return int TTL in seconds
     */
    public function getOperationTtl(string $toolType, string $operation): int {
        // Check if we have a specific TTL for this tool type and operation
        if (isset($this->ttlConfig[$toolType][$operation])) {
            return $this->ttlConfig[$toolType][$operation];
        }
        
        // Check if we have a default TTL for this tool type
        if (isset($this->ttlConfig[$toolType]['default'])) {
            return $this->ttlConfig[$toolType]['default'];
        }
        
        // Check if we have a global default TTL for all tools
        if (isset($this->ttlConfig['default'])) {
            return $this->ttlConfig['default'];
        }
        
        // Use the instance default TTL
        return $this->defaultTtl;
    }

    /**
     * Set the TTL for a specific tool operation
     *
     * @param string $toolType The tool type
     * @param string $operation The operation
     * @param int $ttl TTL in seconds
     * @return self
     */
    public function setOperationTtl(string $toolType, string $operation, int $ttl): self {
        if (!isset($this->ttlConfig[$toolType])) {
            $this->ttlConfig[$toolType] = ['default' => $this->defaultTtl];
        }
        
        $this->ttlConfig[$toolType][$operation] = max(0, $ttl);
        return $this;
    }

    /**
     * Get the non-cacheable operations
     *
     * @return array Non-cacheable operations
     */
    public function getNonCacheableOperations(): array {
        return $this->nonCacheableOperations;
    }

    /**
     * Set the non-cacheable operations
     *
     * @param array $operations Non-cacheable operations
     * @return self
     */
    public function setNonCacheableOperations(array $operations): self {
        $this->nonCacheableOperations = $operations;
        return $this;
    }

    /**
     * Add a non-cacheable operation
     *
     * @param string $operation The operation in format "ToolType.operation_name"
     * @return self
     */
    public function addNonCacheableOperation(string $operation): self {
        if (!in_array($operation, $this->nonCacheableOperations)) {
            $this->nonCacheableOperations[] = $operation;
        }
        return $this;
    }

    /**
     * Remove a non-cacheable operation
     *
     * @param string $operation The operation in format "ToolType.operation_name"
     * @return self
     */
    public function removeNonCacheableOperation(string $operation): self {
        $key = array_search($operation, $this->nonCacheableOperations);
        if ($key !== false) {
            unset($this->nonCacheableOperations[$key]);
            $this->nonCacheableOperations = array_values($this->nonCacheableOperations);
        }
        return $this;
    }

    /**
     * Check if an operation is cacheable
     *
     * @param string $toolType The tool type
     * @param string $operation The operation
     * @return bool Whether the operation is cacheable
     */
    public function isOperationCacheable(string $toolType, string $operation): bool {
        $operationKey = $toolType . '.' . $operation;
        return !in_array($operationKey, $this->nonCacheableOperations);
    }

    /**
     * Get the cache strategies
     *
     * @return array Cache strategies
     */
    public function getStrategies(): array {
        return $this->strategies;
    }

    /**
     * Set the cache strategies
     *
     * @param array $strategies Cache strategies
     * @return self
     */
    public function setStrategies(array $strategies): self {
        $this->strategies = $strategies;
        return $this;
    }

    /**
     * Get a specific cache strategy
     *
     * @param string $strategy The strategy name
     * @return array|null The strategy configuration or null if not found
     */
    public function getStrategy(string $strategy): ?array {
        return $this->strategies[$strategy] ?? null;
    }

    /**
     * Set a specific cache strategy
     *
     * @param string $strategy The strategy name
     * @param array $config The strategy configuration
     * @return self
     */
    public function setStrategy(string $strategy, array $config): self {
        $this->strategies[$strategy] = $config;
        return $this;
    }

    /**
     * Get the TTL for a specific cache strategy
     *
     * @param string $strategy The strategy name
     * @return int TTL in seconds or 0 if strategy not found
     */
    public function getStrategyTtl(string $strategy): int {
        return $this->strategies[$strategy]['ttl'] ?? 0;
    }

    /**
     * Set the TTL for a specific cache strategy
     *
     * @param string $strategy The strategy name
     * @param int $ttl TTL in seconds
     * @return self
     */
    public function setStrategyTtl(string $strategy, int $ttl): self {
        if (isset($this->strategies[$strategy])) {
            $this->strategies[$strategy]['ttl'] = max(0, $ttl);
        }
        return $this;
    }

    /**
     * Get the operation characteristics
     *
     * @return array Operation characteristics
     */
    public function getOperationCharacteristics(): array {
        return $this->operationCharacteristics;
    }

    /**
     * Set the operation characteristics
     *
     * @param array $characteristics Operation characteristics
     * @return self
     */
    public function setOperationCharacteristics(array $characteristics): self {
        $this->operationCharacteristics = $characteristics;
        return $this;
    }

    /**
     * Get characteristics for a specific tool operation
     *
     * @param string $toolType The tool type
     * @param string $operation The operation
     * @return array Operation characteristics
     */
    public function getToolOperationCharacteristics(string $toolType, string $operation): array {
        // Check if we have specific characteristics for this operation
        if (isset($this->operationCharacteristics[$toolType][$operation])) {
            return $this->operationCharacteristics[$toolType][$operation];
        }
        
        // Check if we have default characteristics for this tool type
        if (isset($this->operationCharacteristics[$toolType]['default'])) {
            return $this->operationCharacteristics[$toolType]['default'];
        }
        
        // Return default characteristics
        return [
            'volatility' => 'medium',
            'access_frequency' => 'medium',
            'resource_intensity' => 'medium'
        ];
    }

    /**
     * Set characteristics for a specific tool operation
     *
     * @param string $toolType The tool type
     * @param string $operation The operation
     * @param array $characteristics The operation characteristics
     * @return self
     */
    public function setToolOperationCharacteristics(string $toolType, string $operation, array $characteristics): self {
        // Validate characteristics
        $validCharacteristics = ['volatility', 'access_frequency', 'resource_intensity'];
        $validValues = ['very low', 'low', 'medium', 'high', 'very high'];
        
        foreach ($characteristics as $key => $value) {
            if (!in_array($key, $validCharacteristics)) {
                $this->log('Invalid characteristic key', [
                    'key' => $key,
                    'valid_keys' => $validCharacteristics,
                    'level' => 'warning'
                ]);
                continue;
            }
            
            if (!in_array($value, $validValues)) {
                $this->log('Invalid characteristic value', [
                    'key' => $key,
                    'value' => $value,
                    'valid_values' => $validValues,
                    'level' => 'warning'
                ]);
                continue;
            }
            
            // Set the characteristic
            if (!isset($this->operationCharacteristics[$toolType])) {
                $this->operationCharacteristics[$toolType] = [];
            }
            
            if (!isset($this->operationCharacteristics[$toolType][$operation])) {
                $this->operationCharacteristics[$toolType][$operation] = [];
            }
            
            $this->operationCharacteristics[$toolType][$operation][$key] = $value;
        }
        
        return $this;
    }

    /**
     * Get the warming configuration
     *
     * @return array Warming configuration
     */
    public function getWarmingConfig(): array {
        return $this->warmingConfig;
    }

    /**
     * Set the warming configuration
     *
     * @param array $config Warming configuration
     * @return self
     */
    public function setWarmingConfig(array $config): self {
        foreach ($config as $key => $value) {
            if (array_key_exists($key, $this->warmingConfig)) {
                $this->warmingConfig[$key] = $value;
            }
        }
        
        return $this;
    }

    /**
     * Get a specific warming configuration value
     *
     * @param string $key Configuration key
     * @param mixed $default Default value if key not found
     * @return mixed Configuration value
     */
    public function getWarmingConfigValue(string $key, $default = null) {
        return $this->warmingConfig[$key] ?? $default;
    }

    /**
     * Set a specific warming configuration value
     *
     * @param string $key Configuration key
     * @param mixed $value Configuration value
     * @return self
     */
    public function setWarmingConfigValue(string $key, $value): self {
        if (array_key_exists($key, $this->warmingConfig)) {
            $this->warmingConfig[$key] = $value;
        }
        
        return $this;
    }

    /**
     * Check if cache warming is enabled
     *
     * @return bool Whether cache warming is enabled
     */
    public function isWarmingEnabled(): bool {
        return $this->warmingConfig['enabled'] ?? false;
    }

    /**
     * Enable or disable cache warming
     *
     * @param bool $enabled Whether to enable cache warming
     * @return self
     */
    public function setWarmingEnabled(bool $enabled): self {
        $this->warmingConfig['enabled'] = $enabled;
        return $this;
    }

    /**
     * Get the warming operations
     *
     * @return array Warming operations
     */
    public function getWarmingOperations(): array {
        return $this->warmingOperations;
    }

    /**
     * Set the warming operations
     *
     * @param array $operations Warming operations
     * @return self
     */
    public function setWarmingOperations(array $operations): self {
        $this->warmingOperations = $operations;
        return $this;
    }

    /**
     * Get a specific warming operation
     *
     * @param string $operation Operation name in format "ToolType.operation_name"
     * @return array|null Operation configuration or null if not found
     */
    public function getWarmingOperation(string $operation): ?array {
        return $this->warmingOperations[$operation] ?? null;
    }

    /**
     * Add a warming operation
     *
     * @param string $operation Operation name in format "ToolType.operation_name"
     * @param array $config Operation configuration
     * @return self
     */
    public function addWarmingOperation(string $operation, array $config): self {
        $this->warmingOperations[$operation] = $config;
        return $this;
    }

    /**
     * Remove a warming operation
     *
     * @param string $operation Operation name in format "ToolType.operation_name"
     * @return self
     */
    public function removeWarmingOperation(string $operation): self {
        if (isset($this->warmingOperations[$operation])) {
            unset($this->warmingOperations[$operation]);
        }
        return $this;
    }
}