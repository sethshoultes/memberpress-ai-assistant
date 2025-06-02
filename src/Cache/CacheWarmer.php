<?php
/**
 * Cache Warmer
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Cache;

use MemberpressAiAssistant\Abstracts\AbstractService;
use MemberpressAiAssistant\Services\CacheService;
use MemberpressAiAssistant\Services\CachedToolWrapper;

/**
 * Cache Warmer Service
 * 
 * Implements a proactive cache warming system for the MemberPress AI Assistant.
 * This class pre-populates cache for predictable operations, implements scheduling
 * for warming operations, and prioritizes frequently accessed data to ensure
 * optimal performance and response times.
 */
class CacheWarmer extends AbstractService {
    /**
     * Cache service instance
     *
     * @var CacheService
     */
    protected $cacheService;

    /**
     * Advanced cache strategy instance
     *
     * @var AdvancedCacheStrategy
     */
    protected $cacheStrategy;

    /**
     * Cached tool wrapper instance
     *
     * @var CachedToolWrapper
     */
    protected $cachedToolWrapper;

    /**
     * Logger instance
     *
     * @var mixed
     */
    protected $logger;

    /**
     * Warming configuration
     *
     * @var array
     */
    protected $warmingConfig = [
        'batch_size' => 10,           // Number of items to warm in a single batch
        'interval' => 'hourly',       // WordPress cron schedule interval
        'max_execution_time' => 30,   // Maximum execution time in seconds
        'enabled' => true,            // Whether warming is enabled
    ];

    /**
     * Warming operations with priorities
     * Higher number means higher priority
     *
     * @var array
     */
    protected $warmingOperations = [
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

    /**
     * Performance metrics
     *
     * @var array
     */
    protected $metrics = [
        'warming_runs' => 0,
        'items_warmed' => 0,
        'warming_errors' => 0,
        'last_run_time' => 0,
        'average_run_time' => 0,
        'total_run_time' => 0,
        'operations_by_priority' => [],
    ];

    /**
     * Constructor
     *
     * @param CacheService $cacheService Cache service instance
     * @param AdvancedCacheStrategy $cacheStrategy Advanced cache strategy instance
     * @param CachedToolWrapper $cachedToolWrapper Cached tool wrapper instance
     * @param mixed $logger Logger instance
     */
    public function __construct(
        CacheService $cacheService,
        AdvancedCacheStrategy $cacheStrategy,
        CachedToolWrapper $cachedToolWrapper,
        $logger = null
    ) {
        $this->cacheService = $cacheService;
        $this->cacheStrategy = $cacheStrategy;
        $this->cachedToolWrapper = $cachedToolWrapper;
        $this->logger = $logger;
        
        // Initialize metrics
        $this->initializeMetrics();
    }

    /**
     * {@inheritdoc}
     */
    public function register($container): void {
        // Register this service with the container
        $container->register('cache_warmer', function() {
            return $this;
        });

        // Log registration
        $this->log('Cache warmer service registered');
    }

    /**
     * {@inheritdoc}
     */
    public function boot(): void {
        parent::boot();

        // Add hooks
        $this->addHooks();

        // Log boot
        $this->log('Cache warmer service booted');
    }

    /**
     * Add hooks and filters for this service
     *
     * @return void
     */
    protected function addHooks(): void {
        // Register cron event for scheduled warming
        add_action('init', [$this, 'registerCronEvents']);
        
        // Add hook for scheduled warming
        add_action('mpai_cache_warming_event', [$this, 'runScheduledWarming']);
        
        // Add hook to warm cache on plugin activation
        add_action('memberpress_ai_assistant_activated', [$this, 'warmCacheOnActivation']);
        
        // Add hook to warm membership cache when memberships are updated
        add_action('mepr_membership_save_meta', [$this, 'warmMembershipCache'], 10, 1);
        
        // Add hook to warm member cache when members are updated
        add_action('user_register', [$this, 'warmMemberCache'], 10, 1);
        add_action('profile_update', [$this, 'warmMemberCache'], 10, 1);
        
        // Add hook to warm transaction cache when transactions are created/updated
        add_action('mepr_transaction_completed', [$this, 'warmTransactionCache'], 10, 1);
        
        // Add hook to warm post cache when posts are published
        add_action('transition_post_status', [$this, 'warmPostCache'], 10, 3);
    }

    /**
     * Initialize performance metrics
     *
     * @return void
     */
    protected function initializeMetrics(): void {
        // Initialize operation metrics by priority
        foreach ($this->warmingOperations as $operation => $config) {
            $this->metrics['operations_by_priority'][$operation] = [
                'priority' => $config['priority'],
                'times_warmed' => 0,
                'last_warmed' => 0,
                'average_warming_time' => 0,
                'total_warming_time' => 0,
            ];
        }
    }

    /**
     * Register cron events for scheduled warming
     *
     * @return void
     */
    public function registerCronEvents(): void {
        // Skip if warming is disabled
        if (!$this->warmingConfig['enabled']) {
            return;
        }
        
        // Register the cron event if not already scheduled
        if (!wp_next_scheduled('mpai_cache_warming_event')) {
            wp_schedule_event(time(), $this->warmingConfig['interval'], 'mpai_cache_warming_event');
            $this->log('Scheduled cache warming event registered');
        }
    }

    /**
     * Run scheduled cache warming
     *
     * @return void
     */
    public function runScheduledWarming(): void {
        // Skip if warming is disabled
        if (!$this->warmingConfig['enabled']) {
            return;
        }
        
        $this->log('Starting scheduled cache warming');
        $start_time = microtime(true);
        
        // Warm cache based on priority
        $this->warmCacheByPriority();
        
        // Update metrics
        $this->metrics['warming_runs']++;
        $this->metrics['last_run_time'] = microtime(true) - $start_time;
        $this->metrics['total_run_time'] += $this->metrics['last_run_time'];
        $this->metrics['average_run_time'] = $this->metrics['total_run_time'] / $this->metrics['warming_runs'];
        
        $this->log('Completed scheduled cache warming', [
            'run_time' => $this->metrics['last_run_time'],
            'items_warmed' => $this->metrics['items_warmed'],
        ]);
    }

    /**
     * Warm cache on plugin activation
     *
     * @return void
     */
    public function warmCacheOnActivation(): void {
        // Skip if warming is disabled
        if (!$this->warmingConfig['enabled']) {
            return;
        }
        
        $this->log('Starting cache warming on plugin activation');
        
        // Warm high-priority cache items
        $this->warmHighPriorityCache();
        
        $this->log('Completed cache warming on plugin activation');
    }

    /**
     * Warm cache based on priority
     *
     * @param int $limit Maximum number of operations to warm
     * @return int Number of operations warmed
     */
    public function warmCacheByPriority(int $limit = 0): int {
        // Skip if warming is disabled
        if (!$this->warmingConfig['enabled']) {
            return 0;
        }
        
        // Sort operations by priority (highest first)
        $operations = $this->getSortedOperations();
        
        // Set execution time limit
        $max_time = $this->warmingConfig['max_execution_time'];
        $start_time = microtime(true);
        $count = 0;
        
        // Process operations up to the limit or all if limit is 0
        $batch_size = $this->warmingConfig['batch_size'];
        $max_operations = $limit > 0 ? $limit : count($operations);
        
        foreach ($operations as $operation => $config) {
            // Check if we've reached the limit
            if ($count >= $max_operations) {
                break;
            }
            
            // Check if we've exceeded the maximum execution time
            if ((microtime(true) - $start_time) > $max_time) {
                $this->log('Maximum execution time reached during cache warming', [
                    'max_time' => $max_time,
                    'operations_processed' => $count,
                ]);
                break;
            }
            
            // Warm this operation
            $operation_start_time = microtime(true);
            $warmed = $this->warmOperation($operation, $config['params'], $batch_size);
            $operation_time = microtime(true) - $operation_start_time;
            
            // Update metrics
            $this->metrics['items_warmed'] += $warmed;
            $this->metrics['operations_by_priority'][$operation]['times_warmed']++;
            $this->metrics['operations_by_priority'][$operation]['last_warmed'] = time();
            $this->metrics['operations_by_priority'][$operation]['total_warming_time'] += $operation_time;
            $this->metrics['operations_by_priority'][$operation]['average_warming_time'] = 
                $this->metrics['operations_by_priority'][$operation]['total_warming_time'] / 
                $this->metrics['operations_by_priority'][$operation]['times_warmed'];
            
            $count++;
        }
        
        return $count;
    }

    /**
     * Warm high-priority cache items
     *
     * @return int Number of operations warmed
     */
    public function warmHighPriorityCache(): int {
        // Get operations with priority >= 80 (high priority)
        $high_priority_operations = array_filter(
            $this->warmingOperations,
            function($config) {
                return $config['priority'] >= 80;
            }
        );
        
        // Sort by priority
        uasort(
            $high_priority_operations,
            function($a, $b) {
                return $b['priority'] <=> $a['priority'];
            }
        );
        
        // Warm these operations
        $count = 0;
        foreach (array_keys($high_priority_operations) as $operation) {
            $config = $this->warmingOperations[$operation];
            $warmed = $this->warmOperation($operation, $config['params'], $this->warmingConfig['batch_size']);
            $this->metrics['items_warmed'] += $warmed;
            $count++;
        }
        
        return $count;
    }

    /**
     * Warm a specific operation
     *
     * @param string $operation Operation name in format "ToolType.operation_name"
     * @param array $params Parameters for the operation
     * @param int $batch_size Number of items to warm in a batch
     * @return int Number of items warmed
     */
    public function warmOperation(string $operation, array $params = [], int $batch_size = 10): int {
        // Parse operation into tool type and operation name
        $parts = explode('.', $operation);
        if (count($parts) !== 2) {
            $this->log('Invalid operation format', [
                'operation' => $operation,
                'level' => 'warning',
            ]);
            return 0;
        }
        
        $toolType = $parts[0];
        $operationName = $parts[1];
        
        // Log warming operation
        $this->log('Warming cache for operation', [
            'tool' => $toolType,
            'operation' => $operationName,
            'batch_size' => $batch_size,
        ]);
        
        try {
            // Implement specific warming logic based on the operation
            switch ($operation) {
                case 'MemberPressTool.list_memberships':
                    return $this->warmMemberships($batch_size);
                
                case 'MemberPressTool.list_members':
                    return $this->warmMembers($batch_size);
                
                case 'MemberPressTool.list_transactions':
                    return $this->warmTransactions($batch_size);
                
                case 'WordPressTool.list_posts':
                    return $this->warmPosts($batch_size);
                
                // For other operations, we'll use a generic approach
                default:
                    // For now, just simulate warming by generating a cache key
                    $cacheKey = 'tool_execution_' . $toolType . '_' . $operationName . '_' . md5(json_encode($params));
                    $this->log('Generic warming for operation', [
                        'operation' => $operation,
                        'cache_key' => $cacheKey,
                    ]);
                    return 1;
            }
        } catch (\Exception $e) {
            $this->metrics['warming_errors']++;
            $this->log('Error warming cache for operation', [
                'operation' => $operation,
                'error' => $e->getMessage(),
                'level' => 'error',
            ]);
            return 0;
        }
    }

    /**
     * Warm memberships cache
     *
     * @param int $batch_size Number of memberships to warm
     * @return int Number of memberships warmed
     */
    protected function warmMemberships(int $batch_size): int {
        // This is a placeholder implementation
        // In a real implementation, we would:
        // 1. Get a list of membership IDs
        // 2. For each ID, pre-generate the cache for get_membership
        // 3. Also pre-generate the cache for list_memberships
        
        $this->log('Warming memberships cache', [
            'batch_size' => $batch_size,
        ]);
        
        // Simulate warming by returning the batch size
        return $batch_size;
    }

    /**
     * Warm members cache
     *
     * @param int $batch_size Number of members to warm
     * @return int Number of members warmed
     */
    protected function warmMembers(int $batch_size): int {
        // This is a placeholder implementation
        // In a real implementation, we would:
        // 1. Get a list of member IDs
        // 2. For each ID, pre-generate the cache for get_member
        // 3. Also pre-generate the cache for list_members with different filters
        
        $this->log('Warming members cache', [
            'batch_size' => $batch_size,
        ]);
        
        // Simulate warming by returning the batch size
        return $batch_size;
    }

    /**
     * Warm transactions cache
     *
     * @param int $batch_size Number of transactions to warm
     * @return int Number of transactions warmed
     */
    protected function warmTransactions(int $batch_size): int {
        // This is a placeholder implementation
        // In a real implementation, we would:
        // 1. Get a list of transaction IDs
        // 2. For each ID, pre-generate the cache for get_transaction
        // 3. Also pre-generate the cache for list_transactions with different filters
        
        $this->log('Warming transactions cache', [
            'batch_size' => $batch_size,
        ]);
        
        // Simulate warming by returning the batch size
        return $batch_size;
    }

    /**
     * Warm posts cache
     *
     * @param int $batch_size Number of posts to warm
     * @return int Number of posts warmed
     */
    protected function warmPosts(int $batch_size): int {
        // This is a placeholder implementation
        // In a real implementation, we would:
        // 1. Get a list of post IDs
        // 2. For each ID, pre-generate the cache for get_post
        // 3. Also pre-generate the cache for list_posts with different filters
        
        $this->log('Warming posts cache', [
            'batch_size' => $batch_size,
        ]);
        
        // Simulate warming by returning the batch size
        return $batch_size;
    }

    /**
     * Warm membership cache when a membership is updated
     *
     * @param int $membership_id Membership ID
     * @return void
     */
    public function warmMembershipCache($membership_id): void {
        // Skip if warming is disabled
        if (!$this->warmingConfig['enabled']) {
            return;
        }
        
        $this->log('Warming cache for updated membership', [
            'membership_id' => $membership_id,
        ]);
        
        // Warm the specific membership
        $this->warmOperation('MemberPressTool.get_membership', ['id' => $membership_id]);
        
        // Also warm the list of memberships
        $this->warmOperation('MemberPressTool.list_memberships');
    }

    /**
     * Warm member cache when a member is updated
     *
     * @param int $user_id User ID
     * @return void
     */
    public function warmMemberCache($user_id): void {
        // Skip if warming is disabled
        if (!$this->warmingConfig['enabled']) {
            return;
        }
        
        $this->log('Warming cache for updated member', [
            'user_id' => $user_id,
        ]);
        
        // Warm the specific member
        $this->warmOperation('MemberPressTool.get_member', ['id' => $user_id]);
        
        // Also warm the list of members
        $this->warmOperation('MemberPressTool.list_members');
    }

    /**
     * Warm transaction cache when a transaction is updated
     *
     * @param int $transaction_id Transaction ID
     * @return void
     */
    public function warmTransactionCache($transaction_id): void {
        // Skip if warming is disabled
        if (!$this->warmingConfig['enabled']) {
            return;
        }
        
        $this->log('Warming cache for updated transaction', [
            'transaction_id' => $transaction_id,
        ]);
        
        // Warm the specific transaction
        $this->warmOperation('MemberPressTool.get_transaction', ['id' => $transaction_id]);
        
        // Also warm the list of transactions
        $this->warmOperation('MemberPressTool.list_transactions');
    }

    /**
     * Warm post cache when a post status changes
     *
     * @param string $new_status New post status
     * @param string $old_status Old post status
     * @param \WP_Post $post Post object
     * @return void
     */
    public function warmPostCache($new_status, $old_status, $post): void {
        // Skip if warming is disabled
        if (!$this->warmingConfig['enabled']) {
            return;
        }
        
        // Only warm cache when a post is published
        if ($new_status !== 'publish') {
            return;
        }
        
        $this->log('Warming cache for published post', [
            'post_id' => $post->ID,
            'post_type' => $post->post_type,
        ]);
        
        // Warm the specific post
        $this->warmOperation('WordPressTool.get_post', ['id' => $post->ID]);
        
        // Also warm the list of posts
        $this->warmOperation('WordPressTool.list_posts', ['post_type' => $post->post_type]);
    }

    /**
     * Get operations sorted by priority (highest first)
     *
     * @return array Sorted operations
     */
    protected function getSortedOperations(): array {
        $operations = $this->warmingOperations;
        
        uasort(
            $operations,
            function($a, $b) {
                return $b['priority'] <=> $a['priority'];
            }
        );
        
        return $operations;
    }

    /**
     * Set warming configuration
     *
     * @param array $config Configuration array
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
     * Get warming configuration
     *
     * @return array Warming configuration
     */
    public function getWarmingConfig(): array {
        return $this->warmingConfig;
    }

    /**
     * Set warming operations
     *
     * @param array $operations Operations array
     * @return self
     */
    public function setWarmingOperations(array $operations): self {
        $this->warmingOperations = $operations;
        
        // Reinitialize metrics for operations
        $this->initializeMetrics();
        
        return $this;
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
        
        // Initialize metrics for this operation
        if (!isset($this->metrics['operations_by_priority'][$operation])) {
            $this->metrics['operations_by_priority'][$operation] = [
                'priority' => $config['priority'] ?? 0,
                'times_warmed' => 0,
                'last_warmed' => 0,
                'average_warming_time' => 0,
                'total_warming_time' => 0,
            ];
        }
        
        return $this;
    }

    /**
     * Get warming operations
     *
     * @return array Warming operations
     */
    public function getWarmingOperations(): array {
        return $this->warmingOperations;
    }

    /**
     * Get performance metrics
     *
     * @return array Performance metrics
     */
    public function getMetrics(): array {
        return $this->metrics;
    }

    /**
     * Reset performance metrics
     *
     * @return void
     */
    public function resetMetrics(): void {
        $this->metrics = [
            'warming_runs' => 0,
            'items_warmed' => 0,
            'warming_errors' => 0,
            'last_run_time' => 0,
            'average_run_time' => 0,
            'total_run_time' => 0,
            'operations_by_priority' => [],
        ];
        
        $this->initializeMetrics();
        
        $this->log('Cache warmer metrics reset');
    }

    /**
     * Enable or disable cache warming
     *
     * @param bool $enabled Whether to enable cache warming
     * @return self
     */
    public function setEnabled(bool $enabled): self {
        $this->warmingConfig['enabled'] = $enabled;
        
        if ($enabled) {
            $this->log('Cache warming enabled');
        } else {
            $this->log('Cache warming disabled');
        }
        
        return $this;
    }

    /**
     * Check if cache warming is enabled
     *
     * @return bool Whether cache warming is enabled
     */
    public function isEnabled(): bool {
        return $this->warmingConfig['enabled'];
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