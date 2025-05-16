<?php
/**
 * Advanced Cache Strategy
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Cache;

use MemberpressAiAssistant\Abstracts\AbstractService;
use MemberpressAiAssistant\Services\CacheService;

/**
 * Advanced Cache Strategy Service
 * 
 * Implements sophisticated caching strategies for the MemberPress AI Assistant.
 * This class defines different caching strategies based on data volatility,
 * access patterns, and resource intensity, providing optimized TTL values
 * for different types of operations.
 */
class AdvancedCacheStrategy extends AbstractService {
    /**
     * Cache service instance
     *
     * @var CacheService
     */
    protected $cacheService;

    /**
     * Logger instance
     *
     * @var mixed
     */
    protected $logger;

    /**
     * Strategy definitions with base TTL values (in seconds)
     *
     * @var array
     */
    protected $strategies = [
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

    /**
     * Operation characteristics mapping
     * Maps operations to their characteristics for strategy selection
     *
     * @var array
     */
    protected $operationCharacteristics = [
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

    /**
     * Performance metrics for strategy evaluation
     *
     * @var array
     */
    protected $performanceMetrics = [
        'strategy_selections' => [],
        'ttl_adjustments' => [],
        'cache_hits_by_strategy' => [],
        'cache_misses_by_strategy' => [],
    ];

    /**
     * Constructor
     *
     * @param CacheService $cacheService Cache service instance
     * @param mixed $logger Logger instance
     */
    public function __construct(CacheService $cacheService, $logger = null) {
        $this->cacheService = $cacheService;
        $this->logger = $logger;
        
        // Initialize performance metrics
        foreach (array_keys($this->strategies) as $strategy) {
            $this->performanceMetrics['strategy_selections'][$strategy] = 0;
            $this->performanceMetrics['cache_hits_by_strategy'][$strategy] = 0;
            $this->performanceMetrics['cache_misses_by_strategy'][$strategy] = 0;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function register($container): void {
        // Register this service with the container
        $container->singleton('advanced_cache_strategy', function() {
            return $this;
        });

        // Log registration
        $this->log('Advanced cache strategy service registered');
    }

    /**
     * {@inheritdoc}
     */
    public function boot(): void {
        parent::boot();

        // Log boot
        $this->log('Advanced cache strategy service booted');
    }

    /**
     * Determine the appropriate caching strategy for a tool operation
     *
     * @param string $toolType The tool type (class name without namespace)
     * @param string $operation The operation name
     * @return string The selected strategy name
     */
    public function determineStrategy(string $toolType, string $operation): string {
        // Get operation characteristics
        $characteristics = $this->getOperationCharacteristics($toolType, $operation);
        
        // Select strategy based on characteristics
        $strategy = $this->selectStrategyFromCharacteristics($characteristics);
        
        // Track strategy selection for performance metrics
        $this->performanceMetrics['strategy_selections'][$strategy]++;
        
        // Log strategy selection
        $this->log('Strategy selected for operation', [
            'tool' => $toolType,
            'operation' => $operation,
            'strategy' => $strategy,
            'characteristics' => $characteristics
        ]);
        
        return $strategy;
    }

    /**
     * Get operation characteristics
     *
     * @param string $toolType The tool type
     * @param string $operation The operation name
     * @return array The operation characteristics
     */
    protected function getOperationCharacteristics(string $toolType, string $operation): array {
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
     * Select strategy based on operation characteristics
     *
     * @param array $characteristics The operation characteristics
     * @return string The selected strategy name
     */
    protected function selectStrategyFromCharacteristics(array $characteristics): string {
        // Extract characteristics with defaults
        $volatility = $characteristics['volatility'] ?? 'medium';
        $accessFrequency = $characteristics['access_frequency'] ?? 'medium';
        $resourceIntensity = $characteristics['resource_intensity'] ?? 'medium';
        
        // Strategy selection logic based on characteristics
        if ($volatility === 'very high') {
            return 'volatile';
        } elseif ($volatility === 'high') {
            return 'short_lived';
        } elseif ($volatility === 'very low') {
            return 'stable';
        } elseif ($volatility === 'low' && $resourceIntensity === 'high') {
            // For resource-intensive operations with low volatility, use longer cache
            return 'long_lived';
        } elseif ($accessFrequency === 'very high' || $accessFrequency === 'high') {
            // For frequently accessed operations, use at least medium-lived cache
            return $volatility === 'low' ? 'long_lived' : 'medium_lived';
        } else {
            // Default to medium-lived for most operations
            return 'medium_lived';
        }
    }

    /**
     * Calculate TTL for a tool operation based on the selected strategy
     *
     * @param string $toolType The tool type
     * @param string $operation The operation name
     * @return int TTL in seconds
     */
    public function calculateTtl(string $toolType, string $operation): int {
        // Determine the appropriate strategy
        $strategy = $this->determineStrategy($toolType, $operation);
        
        // Get base TTL for the strategy
        $baseTtl = $this->strategies[$strategy]['ttl'];
        
        // Apply adjustments based on operation characteristics
        $adjustedTtl = $this->applyTtlAdjustments($baseTtl, $toolType, $operation, $strategy);
        
        // Log TTL calculation
        $this->log('TTL calculated for operation', [
            'tool' => $toolType,
            'operation' => $operation,
            'strategy' => $strategy,
            'base_ttl' => $baseTtl,
            'adjusted_ttl' => $adjustedTtl
        ]);
        
        return $adjustedTtl;
    }

    /**
     * Apply adjustments to base TTL based on operation characteristics
     *
     * @param int $baseTtl The base TTL from the strategy
     * @param string $toolType The tool type
     * @param string $operation The operation name
     * @param string $strategy The selected strategy
     * @return int The adjusted TTL
     */
    protected function applyTtlAdjustments(int $baseTtl, string $toolType, string $operation, string $strategy): int {
        $characteristics = $this->getOperationCharacteristics($toolType, $operation);
        $adjustmentFactor = 1.0; // Default: no adjustment
        
        // Adjust based on resource intensity
        if (isset($characteristics['resource_intensity'])) {
            switch ($characteristics['resource_intensity']) {
                case 'very high':
                    $adjustmentFactor *= 1.5; // Increase TTL for very resource-intensive operations
                    break;
                case 'high':
                    $adjustmentFactor *= 1.2; // Increase TTL for resource-intensive operations
                    break;
                case 'low':
                    $adjustmentFactor *= 0.9; // Decrease TTL for less resource-intensive operations
                    break;
                case 'very low':
                    $adjustmentFactor *= 0.8; // Decrease TTL for very low resource-intensive operations
                    break;
            }
        }
        
        // Adjust based on access frequency
        if (isset($characteristics['access_frequency'])) {
            switch ($characteristics['access_frequency']) {
                case 'very high':
                    $adjustmentFactor *= 1.2; // Increase TTL for very frequently accessed operations
                    break;
                case 'high':
                    $adjustmentFactor *= 1.1; // Increase TTL for frequently accessed operations
                    break;
                case 'low':
                    $adjustmentFactor *= 0.9; // Decrease TTL for infrequently accessed operations
                    break;
                case 'very low':
                    $adjustmentFactor *= 0.7; // Decrease TTL for very infrequently accessed operations
                    break;
            }
        }
        
        // Calculate adjusted TTL
        $adjustedTtl = (int) round($baseTtl * $adjustmentFactor);
        
        // Ensure minimum TTL of 10 seconds
        $adjustedTtl = max(10, $adjustedTtl);
        
        // Track TTL adjustment for performance metrics
        $this->performanceMetrics['ttl_adjustments'][] = [
            'tool' => $toolType,
            'operation' => $operation,
            'strategy' => $strategy,
            'base_ttl' => $baseTtl,
            'adjusted_ttl' => $adjustedTtl,
            'adjustment_factor' => $adjustmentFactor
        ];
        
        return $adjustedTtl;
    }

    /**
     * Set operation characteristics for a specific tool operation
     *
     * @param string $toolType The tool type
     * @param string $operation The operation name
     * @param array $characteristics The operation characteristics
     * @return self
     */
    public function setOperationCharacteristics(string $toolType, string $operation, array $characteristics): self {
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
     * Set strategy TTL
     *
     * @param string $strategy The strategy name
     * @param int $ttl TTL in seconds
     * @return self
     */
    public function setStrategyTtl(string $strategy, int $ttl): self {
        if (!isset($this->strategies[$strategy])) {
            $this->log('Invalid strategy', [
                'strategy' => $strategy,
                'valid_strategies' => array_keys($this->strategies),
                'level' => 'warning'
            ]);
            return $this;
        }
        
        $this->strategies[$strategy]['ttl'] = max(0, $ttl);
        return $this;
    }

    /**
     * Get performance metrics
     *
     * @return array The performance metrics
     */
    public function getPerformanceMetrics(): array {
        // Calculate hit rates by strategy
        $hitRatesByStrategy = [];
        
        foreach ($this->strategies as $strategy => $config) {
            $hits = $this->performanceMetrics['cache_hits_by_strategy'][$strategy] ?? 0;
            $misses = $this->performanceMetrics['cache_misses_by_strategy'][$strategy] ?? 0;
            $total = $hits + $misses;
            
            $hitRatesByStrategy[$strategy] = $total > 0 ? round(($hits / $total) * 100, 2) . '%' : 'N/A';
        }
        
        // Return all metrics
        return [
            'strategy_selections' => $this->performanceMetrics['strategy_selections'],
            'ttl_adjustments_count' => count($this->performanceMetrics['ttl_adjustments']),
            'cache_hits_by_strategy' => $this->performanceMetrics['cache_hits_by_strategy'],
            'cache_misses_by_strategy' => $this->performanceMetrics['cache_misses_by_strategy'],
            'hit_rates_by_strategy' => $hitRatesByStrategy
        ];
    }

    /**
     * Track cache hit for a strategy
     *
     * @param string $strategy The strategy name
     * @return void
     */
    public function trackCacheHit(string $strategy): void {
        if (isset($this->performanceMetrics['cache_hits_by_strategy'][$strategy])) {
            $this->performanceMetrics['cache_hits_by_strategy'][$strategy]++;
        }
    }

    /**
     * Track cache miss for a strategy
     *
     * @param string $strategy The strategy name
     * @return void
     */
    public function trackCacheMiss(string $strategy): void {
        if (isset($this->performanceMetrics['cache_misses_by_strategy'][$strategy])) {
            $this->performanceMetrics['cache_misses_by_strategy'][$strategy]++;
        }
    }

    /**
     * Get all available strategies with their descriptions
     *
     * @return array The strategies with descriptions
     */
    public function getStrategies(): array {
        $result = [];
        
        foreach ($this->strategies as $name => $config) {
            $result[$name] = [
                'description' => $config['description'],
                'ttl' => $config['ttl'],
                'volatility' => $config['volatility'],
                'use_cases' => $config['use_cases']
            ];
        }
        
        return $result;
    }

    /**
     * Get fallback TTL for when strategy selection fails
     *
     * @return int Fallback TTL in seconds
     */
    public function getFallbackTtl(): int {
        return $this->strategies['medium_lived']['ttl'];
    }

    /**
     * Log a message
     *
     * @param string $message The message to log
     * @param array $context Additional context data
     * @return void
     */
    protected function log(string $message, array $context = []): void {
        if ($this->logger) {
            $level = $context['level'] ?? 'info';
            unset($context['level']);
            
            if (method_exists($this->logger, $level)) {
                $this->logger->$level($message, $context);
            } else {
                $this->logger->info($message, $context);
            }
        }
    }
}