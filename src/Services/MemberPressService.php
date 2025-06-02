<?php
/**
 * MemberPress Service
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Services;

use MemberpressAiAssistant\Abstracts\AbstractService;
use MemberpressAiAssistant\DI\ServiceLocator;

/**
 * Service for interacting with MemberPress core functionality
 *
 * Provides methods for accessing and managing MemberPress data with caching support
 * for improved performance.
 */
class MemberPressService extends AbstractService {
    /**
     * MemberPress adapters
     *
     * @var array
     */
    protected $adapters = [];

    /**
     * MemberPress transformers
     *
     * @var array
     */
    protected $transformers = [];
    
    /**
     * Service locator instance
     *
     * @var ServiceLocator|null
     */
    protected $serviceLocator = null;
    
    /**
     * Cache service instance
     *
     * @var CacheService|null
     */
    protected $cacheService = null;
    
    /**
     * Default TTL values for different data types (in seconds)
     *
     * @var array
     */
    protected $cacheTtl = [
        'membership' => 1800,     // 30 minutes
        'memberships' => 1800,    // 30 minutes
        'member' => 600,          // 10 minutes
        'members' => 900,         // 15 minutes
        'subscription' => 600,    // 10 minutes
        'subscriptions' => 900,   // 15 minutes
        'transaction' => 1800,    // 30 minutes
        'transactions' => 1200,   // 20 minutes
        'access_rule' => 3600,    // 1 hour
        'access_rules' => 3600,   // 1 hour
        'user_memberships' => 600,// 10 minutes
        'access_check' => 300,    // 5 minutes
        'default' => 900          // 15 minutes
    ];
    
    /**
     * Flag to indicate if cache warming is in progress
     *
     * @var bool
     */
    protected $isWarming = false;

    /**
     * {@inheritdoc}
     */
    public function register($serviceLocator): void {
        // Store the service locator for later use
        $this->serviceLocator = $serviceLocator;
        
        // Register this service with the service locator
        $serviceLocator->register('memberpress', function() {
            return $this;
        });

        // Register adapters
        $this->registerAdapters($serviceLocator);

        // Register transformers
        $this->registerTransformers($serviceLocator);
        
        // Get cache service if available
        if ($serviceLocator->has('cache')) {
            $this->cacheService = $serviceLocator->get('cache');
        }

        // Log registration
        $this->log('MemberPress service registered');
    }

    /**
     * {@inheritdoc}
     */
    public function boot(): void {
        parent::boot();

        // Check if MemberPress is active
        if (!$this->isMemberPressActive()) {
            $this->log('MemberPress is not active', ['level' => 'warning']);
            return;
        }

        // Add hooks
        $this->addHooks();
        
        // Warm cache if enabled and available
        if ($this->cacheService !== null && !$this->isWarming) {
            $this->warmCache();
        }
    }
    
    /**
     * Set the cache service
     *
     * @param CacheService $cacheService The cache service instance
     * @return self
     */
    public function setCacheService(CacheService $cacheService): self
    {
        $this->cacheService = $cacheService;
        return $this;
    }
    
    /**
     * Set TTL for a specific data type
     *
     * @param string $type The data type
     * @param int $ttl TTL in seconds
     * @return self
     */
    public function setCacheTtl(string $type, int $ttl): self
    {
        $this->cacheTtl[$type] = max(0, $ttl);
        return $this;
    }
    
    /**
     * Get TTL for a specific data type
     *
     * @param string $type The data type
     * @return int TTL in seconds
     */
    protected function getCacheTtl(string $type): int
    {
        return $this->cacheTtl[$type] ?? $this->cacheTtl['default'];
    }
    
    /**
     * Generate a cache key for MemberPress data
     *
     * @param string $type The data type
     * @param mixed $id Optional identifier
     * @param array $args Optional arguments that affect the data
     * @return string The cache key
     */
    protected function generateCacheKey(string $type, $id = null, array $args = []): string
    {
        $key = 'mpai_mp_' . $type;
        
        if ($id !== null) {
            $key .= '_' . $id;
        }
        
        if (!empty($args)) {
            $key .= '_' . md5(json_encode($args));
        }
        
        return $key;
    }
    
    /**
     * Warm the cache with frequently accessed data
     *
     * @return void
     */
    protected function warmCache(): void
    {
        if ($this->cacheService === null || $this->isWarming) {
            return;
        }
        
        $this->isWarming = true;
        
        try {
            $this->log('Starting cache warming');
            
            // Warm memberships cache
            $this->getMemberships(['limit' => 20]);
            
            // Warm members cache
            $this->getMembers(['limit' => 20]);
            
            // Warm subscriptions cache
            $this->getSubscriptions(['limit' => 20]);
            
            // Warm transactions cache
            $this->getTransactions(['limit' => 20]);
            
            // Warm access rules cache
            $this->getAccessRules(['limit' => 20]);
            
            $this->log('Cache warming completed');
        } catch (\Exception $e) {
            $this->log('Error during cache warming: ' . $e->getMessage(), [
                'exception' => $e,
                'level' => 'error'
            ]);
        } finally {
            $this->isWarming = false;
        }

        // Log boot
        $this->log('MemberPress service booted');
    }

    /**
     * Register adapters with the container
     *
     * @param ServiceLocator $serviceLocator The service locator
     * @return void
     */
    protected function registerAdapters($serviceLocator): void {
        // Register product adapter
        $serviceLocator->register('memberpress.adapters.product', function() {
            $adapter = new Adapters\ProductAdapter($this->logger);
            $this->adapters['product'] = $adapter;
            return $adapter;
        });

        // Register user adapter
        $serviceLocator->register('memberpress.adapters.user', function() {
            $adapter = new Adapters\UserAdapter($this->logger);
            $this->adapters['user'] = $adapter;
            return $adapter;
        });

        // Register subscription adapter
        $serviceLocator->register('memberpress.adapters.subscription', function() {
            $adapter = new Adapters\SubscriptionAdapter($this->logger);
            $this->adapters['subscription'] = $adapter;
            return $adapter;
        });

        // Register transaction adapter
        $serviceLocator->register('memberpress.adapters.transaction', function() {
            $adapter = new Adapters\TransactionAdapter($this->logger);
            $this->adapters['transaction'] = $adapter;
            return $adapter;
        });

        // Register rule adapter
        $serviceLocator->register('memberpress.adapters.rule', function() {
            $adapter = new Adapters\RuleAdapter($this->logger);
            $this->adapters['rule'] = $adapter;
            return $adapter;
        });
    }

    /**
     * Register transformers with the container
     *
     * @param ServiceLocator $serviceLocator The service locator
     * @return void
     */
    protected function registerTransformers($serviceLocator): void {
        // Register product transformer
        $serviceLocator->register('memberpress.transformers.product', function() {
            $transformer = new Transformers\ProductTransformer();
            $this->transformers['product'] = $transformer;
            return $transformer;
        });

        // Register user transformer
        $serviceLocator->register('memberpress.transformers.user', function() {
            $transformer = new Transformers\UserTransformer();
            $this->transformers['user'] = $transformer;
            return $transformer;
        });

        // Register subscription transformer
        $serviceLocator->register('memberpress.transformers.subscription', function() {
            $transformer = new Transformers\SubscriptionTransformer();
            $this->transformers['subscription'] = $transformer;
            return $transformer;
        });

        // Register transaction transformer
        $serviceLocator->register('memberpress.transformers.transaction', function() {
            $transformer = new Transformers\TransactionTransformer();
            $this->transformers['transaction'] = $transformer;
            return $transformer;
        });

        // Register rule transformer
        $serviceLocator->register('memberpress.transformers.rule', function() {
            $transformer = new Transformers\RuleTransformer();
            $this->transformers['rule'] = $transformer;
            return $transformer;
        });
    }

    /**
     * Add hooks and filters for this service
     *
     * @return void
     */
    protected function addHooks(): void {
        // Add hooks for MemberPress integration
        add_action('mepr_subscription_post_create', [$this, 'onSubscriptionCreated'], 10, 1);
        add_action('mepr_subscription_status_changed', [$this, 'onSubscriptionStatusChanged'], 10, 3);
        add_action('mepr_transaction_completed', [$this, 'onTransactionCompleted'], 10, 1);
        
        // Add cache invalidation hooks
        add_action('mepr_subscription_post_create', [$this, 'invalidateSubscriptionCache'], 10, 1);
        add_action('mepr_subscription_post_update', [$this, 'invalidateSubscriptionCache'], 10, 1);
        add_action('mepr_subscription_post_delete', [$this, 'invalidateSubscriptionCache'], 10, 1);
        add_action('mepr_subscription_status_changed', [$this, 'invalidateSubscriptionCache'], 10, 1);
        
        add_action('mepr_transaction_post_create', [$this, 'invalidateTransactionCache'], 10, 1);
        add_action('mepr_transaction_post_update', [$this, 'invalidateTransactionCache'], 10, 1);
        add_action('mepr_transaction_post_delete', [$this, 'invalidateTransactionCache'], 10, 1);
        add_action('mepr_transaction_completed', [$this, 'invalidateTransactionCache'], 10, 1);
        
        add_action('save_post', [$this, 'invalidateMembershipCache'], 10, 3);
        add_action('delete_post', [$this, 'invalidateMembershipCache'], 10, 1);
        
        add_action('user_register', [$this, 'invalidateMemberCache'], 10, 1);
        add_action('profile_update', [$this, 'invalidateMemberCache'], 10, 1);
        add_action('delete_user', [$this, 'invalidateMemberCache'], 10, 1);
        
        add_action('mepr_rule_post_create', [$this, 'invalidateRuleCache'], 10, 1);
        add_action('mepr_rule_post_update', [$this, 'invalidateRuleCache'], 10, 1);
        add_action('mepr_rule_post_delete', [$this, 'invalidateRuleCache'], 10, 1);
    }

    /**
     * Check if MemberPress is active
     *
     * @return bool
     */
    public function isMemberPressActive(): bool {
        return class_exists('MeprProduct') && class_exists('MeprUser');
    }

    /**
     * Get a MemberPress adapter
     *
     * @param string $type The adapter type
     * @return mixed|null The adapter instance or null if not found
     */
    public function getAdapter(string $type) {
        // First check the local adapters array (current behavior)
        if (isset($this->adapters[$type])) {
            return $this->adapters[$type];
        }
        
        // If not found locally, attempt to retrieve from ServiceLocator
        if ($this->serviceLocator !== null) {
            $serviceKey = 'memberpress.adapters.' . $type;
            
            try {
                if ($this->serviceLocator->has($serviceKey)) {
                    $adapter = $this->serviceLocator->get($serviceKey);
                    
                    // Store it in the local array for future use
                    $this->adapters[$type] = $adapter;
                    
                    return $adapter;
                }
            } catch (\Exception $e) {
                $this->log('Error retrieving adapter from ServiceLocator: ' . $e->getMessage(), [
                    'adapter_type' => $type,
                    'service_key' => $serviceKey,
                    'exception' => $e,
                    'level' => 'error'
                ]);
            }
        }
        
        // Return null if not found anywhere
        return null;
    }

    /**
     * Get a MemberPress transformer
     *
     * @param string $type The transformer type
     * @return mixed|null The transformer instance or null if not found
     */
    public function getTransformer(string $type) {
        // First check the local transformers array (current behavior)
        if (isset($this->transformers[$type])) {
            return $this->transformers[$type];
        }
        
        // If not found locally, attempt to retrieve from ServiceLocator
        if ($this->serviceLocator !== null) {
            $serviceKey = 'memberpress.transformers.' . $type;
            
            try {
                if ($this->serviceLocator->has($serviceKey)) {
                    $transformer = $this->serviceLocator->get($serviceKey);
                    
                    // Store it in the local array for future use
                    $this->transformers[$type] = $transformer;
                    
                    return $transformer;
                }
            } catch (\Exception $e) {
                $this->log('Error retrieving transformer from ServiceLocator: ' . $e->getMessage(), [
                    'transformer_type' => $type,
                    'service_key' => $serviceKey,
                    'exception' => $e,
                    'level' => 'error'
                ]);
            }
        }
        
        // Return null if not found anywhere
        return null;
    }

    /**
     * Get all memberships
     *
     * @param array $args Optional arguments
     * @return array Array of memberships
     */
    public function getMemberships(array $args = []): array {
        try {
            if (!$this->isMemberPressActive()) {
                throw new \Exception('MemberPress is not active');
            }
            
            // Generate cache key
            $cacheKey = $this->generateCacheKey('memberships', null, $args);
            
            // Try to get from cache
            if ($this->cacheService) {
                $cachedResult = $this->cacheService->get($cacheKey);
                if ($cachedResult !== null) {
                    $this->log('Cache hit for memberships', [
                        'args' => $args,
                        'cache_key' => $cacheKey
                    ]);
                    return $cachedResult;
                }
            }

            $productAdapter = $this->getAdapter('product');
            if (!$productAdapter) {
                throw new \Exception('Product adapter not found');
            }

            $products = $productAdapter->getAll($args);
            
            // Transform products if transformer exists
            $transformer = $this->getTransformer('product');
            if ($transformer) {
                $products = array_map(function($product) use ($transformer) {
                    return $transformer->transform($product);
                }, $products);
            }

            $result = [
                'status' => 'success',
                'data' => $products
            ];
            
            // Cache the result
            if ($this->cacheService) {
                $ttl = $this->getCacheTtl('memberships');
                $this->cacheService->set($cacheKey, $result, $ttl);
                $this->log('Cached memberships', [
                    'args' => $args,
                    'cache_key' => $cacheKey,
                    'ttl' => $ttl
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            $this->log('Error getting memberships: ' . $e->getMessage(), [
                'exception' => $e,
                'level' => 'error'
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get a single membership by ID
     *
     * @param int $id The membership ID
     * @return array The membership data
     */
    public function getMembership(int $id): array {
        try {
            if (!$this->isMemberPressActive()) {
                throw new \Exception('MemberPress is not active');
            }
            
            // Generate cache key
            $cacheKey = $this->generateCacheKey('membership', $id);
            
            // Try to get from cache
            if ($this->cacheService) {
                $cachedResult = $this->cacheService->get($cacheKey);
                if ($cachedResult !== null) {
                    $this->log('Cache hit for membership', [
                        'id' => $id,
                        'cache_key' => $cacheKey
                    ]);
                    return $cachedResult;
                }
            }

            $productAdapter = $this->getAdapter('product');
            if (!$productAdapter) {
                throw new \Exception('Product adapter not found');
            }

            $product = $productAdapter->get($id);
            if (!$product) {
                throw new \Exception('Membership not found');
            }
            
            // Transform product if transformer exists
            $transformer = $this->getTransformer('product');
            if ($transformer) {
                $product = $transformer->transform($product);
            }

            $result = [
                'status' => 'success',
                'data' => $product
            ];
            
            // Cache the result
            if ($this->cacheService) {
                $ttl = $this->getCacheTtl('membership');
                $this->cacheService->set($cacheKey, $result, $ttl);
                $this->log('Cached membership', [
                    'id' => $id,
                    'cache_key' => $cacheKey,
                    'ttl' => $ttl
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            $this->log('Error getting membership: ' . $e->getMessage(), [
                'membership_id' => $id,
                'exception' => $e,
                'level' => 'error'
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get all members
     *
     * @param array $args Optional arguments
     * @return array Array of members
     */
    public function getMembers(array $args = []): array {
        try {
            if (!$this->isMemberPressActive()) {
                throw new \Exception('MemberPress is not active');
            }
            
            // Generate cache key
            $cacheKey = $this->generateCacheKey('members', null, $args);
            
            // Try to get from cache
            if ($this->cacheService) {
                $cachedResult = $this->cacheService->get($cacheKey);
                if ($cachedResult !== null) {
                    $this->log('Cache hit for members', [
                        'args' => $args,
                        'cache_key' => $cacheKey
                    ]);
                    return $cachedResult;
                }
            }

            $userAdapter = $this->getAdapter('user');
            if (!$userAdapter) {
                throw new \Exception('User adapter not found');
            }

            $users = $userAdapter->getAll($args);
            
            // Transform users if transformer exists
            $transformer = $this->getTransformer('user');
            if ($transformer) {
                $users = array_map(function($user) use ($transformer) {
                    return $transformer->transform($user);
                }, $users);
            }

            $result = [
                'status' => 'success',
                'data' => $users
            ];
            
            // Cache the result
            if ($this->cacheService) {
                $ttl = $this->getCacheTtl('members');
                $this->cacheService->set($cacheKey, $result, $ttl);
                $this->log('Cached members', [
                    'args' => $args,
                    'cache_key' => $cacheKey,
                    'ttl' => $ttl
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            $this->log('Error getting members: ' . $e->getMessage(), [
                'exception' => $e,
                'level' => 'error'
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get a single member by ID
     *
     * @param int $id The member ID
     * @return array The member data
     */
    public function getMember(int $id): array {
        try {
            if (!$this->isMemberPressActive()) {
                throw new \Exception('MemberPress is not active');
            }
            
            // Generate cache key
            $cacheKey = $this->generateCacheKey('member', $id);
            
            // Try to get from cache
            if ($this->cacheService) {
                $cachedResult = $this->cacheService->get($cacheKey);
                if ($cachedResult !== null) {
                    $this->log('Cache hit for member', [
                        'id' => $id,
                        'cache_key' => $cacheKey
                    ]);
                    return $cachedResult;
                }
            }

            $userAdapter = $this->getAdapter('user');
            if (!$userAdapter) {
                throw new \Exception('User adapter not found');
            }

            $user = $userAdapter->get($id);
            if (!$user) {
                throw new \Exception('Member not found');
            }
            
            // Transform user if transformer exists
            $transformer = $this->getTransformer('user');
            if ($transformer) {
                $user = $transformer->transform($user);
            }

            $result = [
                'status' => 'success',
                'data' => $user
            ];
            
            // Cache the result
            if ($this->cacheService) {
                $ttl = $this->getCacheTtl('member');
                $this->cacheService->set($cacheKey, $result, $ttl);
                $this->log('Cached member', [
                    'id' => $id,
                    'cache_key' => $cacheKey,
                    'ttl' => $ttl
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            $this->log('Error getting member: ' . $e->getMessage(), [
                'member_id' => $id,
                'exception' => $e,
                'level' => 'error'
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Check if a member has access to a membership
     *
     * @param int $user_id The user ID
     * @param int $product_id The product ID
     * @return array The access status
     */
    public function checkAccess(int $user_id, int $product_id): array {
        try {
            if (!$this->isMemberPressActive()) {
                throw new \Exception('MemberPress is not active');
            }
            
            // Generate cache key
            $cacheKey = $this->generateCacheKey('access_check', $user_id . '_' . $product_id);
            
            // Try to get from cache
            if ($this->cacheService) {
                $cachedResult = $this->cacheService->get($cacheKey);
                if ($cachedResult !== null) {
                    $this->log('Cache hit for access check', [
                        'user_id' => $user_id,
                        'product_id' => $product_id,
                        'cache_key' => $cacheKey
                    ]);
                    return $cachedResult;
                }
            }

            $userAdapter = $this->getAdapter('user');
            if (!$userAdapter) {
                throw new \Exception('User adapter not found');
            }

            $hasAccess = $userAdapter->hasAccess($user_id, $product_id);

            $result = [
                'status' => 'success',
                'data' => [
                    'has_access' => $hasAccess,
                    'user_id' => $user_id,
                    'product_id' => $product_id
                ]
            ];
            
            // Cache the result
            if ($this->cacheService) {
                $ttl = $this->getCacheTtl('access_check');
                $this->cacheService->set($cacheKey, $result, $ttl);
                $this->log('Cached access check', [
                    'user_id' => $user_id,
                    'product_id' => $product_id,
                    'cache_key' => $cacheKey,
                    'ttl' => $ttl
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            $this->log('Error checking access: ' . $e->getMessage(), [
                'user_id' => $user_id,
                'product_id' => $product_id,
                'exception' => $e,
                'level' => 'error'
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get all subscriptions
     *
     * @param array $args Optional arguments
     * @return array Array of subscriptions
     */
    public function getSubscriptions(array $args = []): array {
        try {
            if (!$this->isMemberPressActive()) {
                throw new \Exception('MemberPress is not active');
            }
            
            // Generate cache key
            $cacheKey = $this->generateCacheKey('subscriptions', null, $args);
            
            // Try to get from cache
            if ($this->cacheService) {
                $cachedResult = $this->cacheService->get($cacheKey);
                if ($cachedResult !== null) {
                    $this->log('Cache hit for subscriptions', [
                        'args' => $args,
                        'cache_key' => $cacheKey
                    ]);
                    return $cachedResult;
                }
            }

            $subscriptionAdapter = $this->getAdapter('subscription');
            if (!$subscriptionAdapter) {
                throw new \Exception('Subscription adapter not found');
            }

            $subscriptions = $subscriptionAdapter->getAll($args);
            
            // Transform subscriptions if transformer exists
            $transformer = $this->getTransformer('subscription');
            if ($transformer) {
                $subscriptions = array_map(function($subscription) use ($transformer) {
                    return $transformer->transform($subscription);
                }, $subscriptions);
            }

            $result = [
                'status' => 'success',
                'data' => $subscriptions
            ];
            
            // Cache the result
            if ($this->cacheService) {
                $ttl = $this->getCacheTtl('subscriptions');
                $this->cacheService->set($cacheKey, $result, $ttl);
                $this->log('Cached subscriptions', [
                    'args' => $args,
                    'cache_key' => $cacheKey,
                    'ttl' => $ttl
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            $this->log('Error getting subscriptions: ' . $e->getMessage(), [
                'exception' => $e,
                'level' => 'error'
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get all transactions
     *
     * @param array $args Optional arguments
     * @return array Array of transactions
     */
    public function getTransactions(array $args = []): array {
        try {
            if (!$this->isMemberPressActive()) {
                throw new \Exception('MemberPress is not active');
            }
            
            // Generate cache key
            $cacheKey = $this->generateCacheKey('transactions', null, $args);
            
            // Try to get from cache
            if ($this->cacheService) {
                $cachedResult = $this->cacheService->get($cacheKey);
                if ($cachedResult !== null) {
                    $this->log('Cache hit for transactions', [
                        'args' => $args,
                        'cache_key' => $cacheKey
                    ]);
                    return $cachedResult;
                }
            }

            $transactionAdapter = $this->getAdapter('transaction');
            if (!$transactionAdapter) {
                throw new \Exception('Transaction adapter not found');
            }

            $transactions = $transactionAdapter->getAll($args);
            
            // Transform transactions if transformer exists
            $transformer = $this->getTransformer('transaction');
            if ($transformer) {
                $transactions = array_map(function($transaction) use ($transformer) {
                    return $transformer->transform($transaction);
                }, $transactions);
            }

            $result = [
                'status' => 'success',
                'data' => $transactions
            ];
            
            // Cache the result
            if ($this->cacheService) {
                $ttl = $this->getCacheTtl('transactions');
                $this->cacheService->set($cacheKey, $result, $ttl);
                $this->log('Cached transactions', [
                    'args' => $args,
                    'cache_key' => $cacheKey,
                    'ttl' => $ttl
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            $this->log('Error getting transactions: ' . $e->getMessage(), [
                'exception' => $e,
                'level' => 'error'
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get all access rules
     *
     * @param array $args Optional arguments
     * @return array Array of access rules
     */
    public function getAccessRules(array $args = []): array {
        try {
            if (!$this->isMemberPressActive()) {
                throw new \Exception('MemberPress is not active');
            }
            
            // Generate cache key
            $cacheKey = $this->generateCacheKey('access_rules', null, $args);
            
            // Try to get from cache
            if ($this->cacheService) {
                $cachedResult = $this->cacheService->get($cacheKey);
                if ($cachedResult !== null) {
                    $this->log('Cache hit for access rules', [
                        'args' => $args,
                        'cache_key' => $cacheKey
                    ]);
                    return $cachedResult;
                }
            }

            $ruleAdapter = $this->getAdapter('rule');
            if (!$ruleAdapter) {
                throw new \Exception('Rule adapter not found');
            }

            $rules = $ruleAdapter->getAll($args);
            
            // Transform rules if transformer exists
            $transformer = $this->getTransformer('rule');
            if ($transformer) {
                $rules = array_map(function($rule) use ($transformer) {
                    return $transformer->transform($rule);
                }, $rules);
            }

            $result = [
                'status' => 'success',
                'data' => $rules
            ];
            
            // Cache the result
            if ($this->cacheService) {
                $ttl = $this->getCacheTtl('access_rules');
                $this->cacheService->set($cacheKey, $result, $ttl);
                $this->log('Cached access rules', [
                    'args' => $args,
                    'cache_key' => $cacheKey,
                    'ttl' => $ttl
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            $this->log('Error getting access rules: ' . $e->getMessage(), [
                'exception' => $e,
                'level' => 'error'
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Handle subscription created event
     *
     * @param \MeprSubscription $subscription The subscription object
     * @return void
     */
    public function onSubscriptionCreated($subscription): void {
        try {
            $this->log('Subscription created', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'product_id' => $subscription->product_id
            ]);
        } catch (\Exception $e) {
            $this->log('Error handling subscription created: ' . $e->getMessage(), [
                'exception' => $e,
                'level' => 'error'
            ]);
        }
    }

    /**
     * Handle subscription status changed event
     *
     * @param \MeprSubscription $subscription The subscription object
     * @param string $old_status The old status
     * @param string $new_status The new status
     * @return void
     */
    public function onSubscriptionStatusChanged($subscription, $old_status, $new_status): void {
        try {
            $this->log('Subscription status changed', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'product_id' => $subscription->product_id,
                'old_status' => $old_status,
                'new_status' => $new_status
            ]);
        } catch (\Exception $e) {
            $this->log('Error handling subscription status changed: ' . $e->getMessage(), [
                'exception' => $e,
                'level' => 'error'
            ]);
        }
    }

    /**
     * Handle transaction completed event
     *
     * @param \MeprTransaction $transaction The transaction object
     * @return void
     */
    public function onTransactionCompleted($transaction): void {
        try {
            $this->log('Transaction completed', [
                'transaction_id' => $transaction->id,
                'user_id' => $transaction->user_id,
                'product_id' => $transaction->product_id,
                'amount' => $transaction->total
            ]);
        } catch (\Exception $e) {
            $this->log('Error handling transaction completed: ' . $e->getMessage(), [
                'exception' => $e,
                'level' => 'error'
            ]);
        }
    }

    /**
     * Create a new membership
     *
     * @param array $data The membership data
     * @return array The result of the operation
     */
    public function createMembership(array $data): array {
        try {
            // Add comprehensive debug logging
            $this->log('[MEMBERSHIP DEBUG] MemberPressService::createMembership - Data received from tool', [
                'received_data' => $data,
                'data_keys' => array_keys($data),
                'title_value' => $data['title'] ?? 'NOT_SET',
                'price_value' => $data['price'] ?? 'NOT_SET',
                'period_type_value' => $data['period_type'] ?? 'NOT_SET'
            ]);
            
            if (!$this->isMemberPressActive()) {
                throw new \Exception('MemberPress is not active');
            }

            $productAdapter = $this->getAdapter('product');
            if (!$productAdapter) {
                throw new \Exception('Product adapter not found');
            }

            $this->log('[MEMBERSHIP DEBUG] MemberPressService::createMembership - Calling ProductAdapter::create', [
                'adapter_found' => true,
                'data_to_adapter' => $data
            ]);

            $product = $productAdapter->create($data);
            if (!$product) {
                throw new \Exception('Failed to create membership');
            }
            
            $this->log('[MEMBERSHIP DEBUG] MemberPressService::createMembership - ProductAdapter returned product', [
                'product_created' => true,
                'product_id' => $product->ID ?? 'NO_ID',
                'product_title' => $product->post_title ?? 'NO_TITLE',
                'product_price' => $product->price ?? 'NO_PRICE',
                'product_period_type' => $product->period_type ?? 'NO_PERIOD_TYPE'
            ]);
            
            // Transform product if transformer exists
            $transformer = $this->getTransformer('product');
            if ($transformer) {
                $product = $transformer->transform($product);
            }

            return [
                'status' => 'success',
                'message' => 'Membership created successfully',
                'data' => $product
            ];
        } catch (\Exception $e) {
            $this->log('Error creating membership: ' . $e->getMessage(), [
                'data' => $data,
                'exception' => $e,
                'level' => 'error'
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Update an existing membership
     *
     * @param int $id The membership ID
     * @param array $data The membership data
     * @return array The result of the operation
     */
    public function updateMembership(int $id, array $data): array {
        try {
            if (!$this->isMemberPressActive()) {
                throw new \Exception('MemberPress is not active');
            }

            $productAdapter = $this->getAdapter('product');
            if (!$productAdapter) {
                throw new \Exception('Product adapter not found');
            }

            // Check if the membership exists
            $existingProduct = $productAdapter->get($id);
            if (!$existingProduct) {
                throw new \Exception('Membership not found');
            }

            $product = $productAdapter->update($id, $data);
            if (!$product) {
                throw new \Exception('Failed to update membership');
            }
            
            // Transform product if transformer exists
            $transformer = $this->getTransformer('product');
            if ($transformer) {
                $product = $transformer->transform($product);
            }

            return [
                'status' => 'success',
                'message' => 'Membership updated successfully',
                'data' => $product
            ];
        } catch (\Exception $e) {
            $this->log('Error updating membership: ' . $e->getMessage(), [
                'membership_id' => $id,
                'data' => $data,
                'exception' => $e,
                'level' => 'error'
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete a membership
     *
     * @param int $id The membership ID
     * @return array The result of the operation
     */
    public function deleteMembership(int $id): array {
        try {
            if (!$this->isMemberPressActive()) {
                throw new \Exception('MemberPress is not active');
            }

            $productAdapter = $this->getAdapter('product');
            if (!$productAdapter) {
                throw new \Exception('Product adapter not found');
            }

            // Check if the membership exists
            $existingProduct = $productAdapter->get($id);
            if (!$existingProduct) {
                throw new \Exception('Membership not found');
            }

            $result = $productAdapter->delete($id);
            if (!$result) {
                throw new \Exception('Failed to delete membership');
            }

            return [
                'status' => 'success',
                'message' => 'Membership deleted successfully',
                'data' => [
                    'id' => $id
                ]
            ];
        } catch (\Exception $e) {
            $this->log('Error deleting membership: ' . $e->getMessage(), [
                'membership_id' => $id,
                'exception' => $e,
                'level' => 'error'
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Create a new access rule
     *
     * @param array $data The access rule data
     * @return array The result of the operation
     */
    public function createAccessRule(array $data): array {
        try {
            if (!$this->isMemberPressActive()) {
                throw new \Exception('MemberPress is not active');
            }

            $ruleAdapter = $this->getAdapter('rule');
            if (!$ruleAdapter) {
                throw new \Exception('Rule adapter not found');
            }

            // Validate required fields
            if (empty($data['product_id'])) {
                throw new \Exception('Product ID is required');
            }

            if (empty($data['content_type'])) {
                throw new \Exception('Content type is required');
            }

            $rule = $ruleAdapter->create($data);
            if (!$rule) {
                throw new \Exception('Failed to create access rule');
            }
            
            // Transform rule if transformer exists
            $transformer = $this->getTransformer('rule');
            if ($transformer) {
                $rule = $transformer->transform($rule);
            }

            return [
                'status' => 'success',
                'message' => 'Access rule created successfully',
                'data' => $rule
            ];
        } catch (\Exception $e) {
            $this->log('Error creating access rule: ' . $e->getMessage(), [
                'data' => $data,
                'exception' => $e,
                'level' => 'error'
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Update an existing access rule
     *
     * @param int $id The access rule ID
     * @param array $data The access rule data
     * @return array The result of the operation
     */
    public function updateAccessRule(int $id, array $data): array {
        try {
            if (!$this->isMemberPressActive()) {
                throw new \Exception('MemberPress is not active');
            }

            $ruleAdapter = $this->getAdapter('rule');
            if (!$ruleAdapter) {
                throw new \Exception('Rule adapter not found');
            }

            // Check if the rule exists
            $existingRule = $ruleAdapter->get($id);
            if (!$existingRule) {
                throw new \Exception('Access rule not found');
            }

            $rule = $ruleAdapter->update($id, $data);
            if (!$rule) {
                throw new \Exception('Failed to update access rule');
            }
            
            // Transform rule if transformer exists
            $transformer = $this->getTransformer('rule');
            if ($transformer) {
                $rule = $transformer->transform($rule);
            }

            return [
                'status' => 'success',
                'message' => 'Access rule updated successfully',
                'data' => $rule
            ];
        } catch (\Exception $e) {
            $this->log('Error updating access rule: ' . $e->getMessage(), [
                'rule_id' => $id,
                'data' => $data,
                'exception' => $e,
                'level' => 'error'
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete an access rule
     *
     * @param int $id The access rule ID
     * @return array The result of the operation
     */
    public function deleteAccessRule(int $id): array {
        try {
            if (!$this->isMemberPressActive()) {
                throw new \Exception('MemberPress is not active');
            }

            $ruleAdapter = $this->getAdapter('rule');
            if (!$ruleAdapter) {
                throw new \Exception('Rule adapter not found');
            }

            // Check if the rule exists
            $existingRule = $ruleAdapter->get($id);
            if (!$existingRule) {
                throw new \Exception('Access rule not found');
            }

            $result = $ruleAdapter->delete($id);
            if (!$result) {
                throw new \Exception('Failed to delete access rule');
            }

            return [
                'status' => 'success',
                'message' => 'Access rule deleted successfully',
                'data' => [
                    'id' => $id
                ]
            ];
        } catch (\Exception $e) {
            $this->log('Error deleting access rule: ' . $e->getMessage(), [
                'rule_id' => $id,
                'exception' => $e,
                'level' => 'error'
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Manage pricing for a membership
     *
     * @param int $id The membership ID
     * @param array $data The pricing data
     * @return array The result of the operation
     */
    public function managePricing(int $id, array $data): array {
        try {
            if (!$this->isMemberPressActive()) {
                throw new \Exception('MemberPress is not active');
            }

            $productAdapter = $this->getAdapter('product');
            if (!$productAdapter) {
                throw new \Exception('Product adapter not found');
            }

            // Check if the membership exists
            $existingProduct = $productAdapter->get($id);
            if (!$existingProduct) {
                throw new \Exception('Membership not found');
            }

            // Prepare pricing data
            $pricingData = [];
            
            // Basic pricing fields
            if (isset($data['price'])) {
                $pricingData['price'] = floatval($data['price']);
            }
            
            if (isset($data['period'])) {
                $pricingData['period'] = intval($data['period']);
            }
            
            if (isset($data['period_type'])) {
                $pricingData['period_type'] = sanitize_text_field($data['period_type']);
            }
            
            // Trial related fields
            if (isset($data['trial'])) {
                $pricingData['trial'] = (bool)$data['trial'];
            }
            
            if (isset($data['trial_days'])) {
                $pricingData['trial_days'] = intval($data['trial_days']);
            }
            
            if (isset($data['trial_amount'])) {
                $pricingData['trial_amount'] = floatval($data['trial_amount']);
            }
            
            // Billing cycle limits
            if (isset($data['limit_cycles'])) {
                $pricingData['limit_cycles'] = (bool)$data['limit_cycles'];
            }
            
            if (isset($data['limit_cycles_num'])) {
                $pricingData['limit_cycles_num'] = intval($data['limit_cycles_num']);
            }
            
            if (isset($data['limit_cycles_action'])) {
                $pricingData['limit_cycles_action'] = sanitize_text_field($data['limit_cycles_action']);
            }
            
            // Expiration settings
            if (isset($data['expire_type'])) {
                $pricingData['expire_type'] = sanitize_text_field($data['expire_type']);
            }
            
            if (isset($data['expire_after'])) {
                $pricingData['expire_after'] = intval($data['expire_after']);
            }
            
            if (isset($data['expire_unit'])) {
                $pricingData['expire_unit'] = sanitize_text_field($data['expire_unit']);
            }
            
            if (isset($data['expire_fixed'])) {
                $pricingData['expire_fixed'] = sanitize_text_field($data['expire_fixed']);
            }
            
            // Tax settings
            if (isset($data['tax_exempt'])) {
                $pricingData['tax_exempt'] = (bool)$data['tax_exempt'];
            }
            
            if (isset($data['tax_class'])) {
                $pricingData['tax_class'] = sanitize_text_field($data['tax_class']);
            }
            
            // Display settings
            if (isset($data['pricing_display'])) {
                $pricingData['pricing_display'] = sanitize_text_field($data['pricing_display']);
            }
            
            if (isset($data['pricing_display_text'])) {
                $pricingData['pricing_display_text'] = sanitize_text_field($data['pricing_display_text']);
            }
            
            if (isset($data['register_price_action'])) {
                $pricingData['register_price_action'] = sanitize_text_field($data['register_price_action']);
            }
            
            if (isset($data['register_price'])) {
                $pricingData['register_price'] = sanitize_text_field($data['register_price']);
            }

            // Update the product with pricing data
            $product = $productAdapter->update($id, $pricingData);
            if (!$product) {
                throw new \Exception('Failed to update membership pricing');
            }
            
            // Transform product if transformer exists
            $transformer = $this->getTransformer('product');
            if ($transformer) {
                $product = $transformer->transform($product);
            }

            return [
                'status' => 'success',
                'message' => 'Membership pricing updated successfully',
                'data' => $product
            ];
        } catch (\Exception $e) {
            $this->log('Error managing membership pricing: ' . $e->getMessage(), [
                'membership_id' => $id,
                'data' => $data,
                'exception' => $e,
                'level' => 'error'
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Associate a user with a membership
     *
     * @param int $user_id The user ID
     * @param int $membership_id The membership ID
     * @param array $args Optional arguments
     * @return array The result of the operation
     */
    public function associateUserWithMembership(int $user_id, int $membership_id, array $args = []): array {
        try {
            if (!$this->isMemberPressActive()) {
                throw new \Exception('MemberPress is not active');
            }

            $userAdapter = $this->getAdapter('user');
            if (!$userAdapter) {
                throw new \Exception('User adapter not found');
            }

            // Check if the user exists
            $user = $userAdapter->get($user_id);
            if (!$user) {
                throw new \Exception('User not found');
            }

            // Check if the membership exists
            $productAdapter = $this->getAdapter('product');
            if (!$productAdapter) {
                throw new \Exception('Product adapter not found');
            }

            $product = $productAdapter->get($membership_id);
            if (!$product) {
                throw new \Exception('Membership not found');
            }

            // Associate the user with the membership
            $result = $userAdapter->addToMembership($user_id, $membership_id, $args);
            if (!$result) {
                throw new \Exception('Failed to associate user with membership');
            }

            return [
                'status' => 'success',
                'message' => 'User associated with membership successfully',
                'data' => [
                    'user_id' => $user_id,
                    'membership_id' => $membership_id,
                    'has_access' => $userAdapter->hasAccess($user_id, $membership_id)
                ]
            ];
        } catch (\Exception $e) {
            $this->log('Error associating user with membership: ' . $e->getMessage(), [
                'user_id' => $user_id,
                'membership_id' => $membership_id,
                'args' => $args,
                'exception' => $e,
                'level' => 'error'
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Disassociate a user from a membership
     *
     * @param int $user_id The user ID
     * @param int $membership_id The membership ID
     * @return array The result of the operation
     */
    public function disassociateUserFromMembership(int $user_id, int $membership_id): array {
        try {
            if (!$this->isMemberPressActive()) {
                throw new \Exception('MemberPress is not active');
            }

            $userAdapter = $this->getAdapter('user');
            if (!$userAdapter) {
                throw new \Exception('User adapter not found');
            }

            // Check if the user exists
            $user = $userAdapter->get($user_id);
            if (!$user) {
                throw new \Exception('User not found');
            }

            // Check if the membership exists
            $productAdapter = $this->getAdapter('product');
            if (!$productAdapter) {
                throw new \Exception('Product adapter not found');
            }

            $product = $productAdapter->get($membership_id);
            if (!$product) {
                throw new \Exception('Membership not found');
            }

            // Disassociate the user from the membership
            $result = $userAdapter->removeFromMembership($user_id, $membership_id);
            if (!$result) {
                throw new \Exception('Failed to disassociate user from membership');
            }

            return [
                'status' => 'success',
                'message' => 'User disassociated from membership successfully',
                'data' => [
                    'user_id' => $user_id,
                    'membership_id' => $membership_id,
                    'has_access' => $userAdapter->hasAccess($user_id, $membership_id)
                ]
            ];
        } catch (\Exception $e) {
            $this->log('Error disassociating user from membership: ' . $e->getMessage(), [
                'user_id' => $user_id,
                'membership_id' => $membership_id,
                'exception' => $e,
                'level' => 'error'
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Invalidate membership cache
     *
     * @param int $post_id Post ID
     * @param \WP_Post|null $post Post object
     * @param bool $update Whether this is an update
     * @return void
     */
    public function invalidateMembershipCache($post_id, $post = null, $update = false): void
    {
            if ($this->cacheService === null) {
                return;
            }
            
            // Skip revisions and auto-saves
            if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
                return;
            }
            
            // Check if this is a MemberPress product
            if ($post && $post->post_type === 'memberpressproduct') {
                // Invalidate specific membership cache
                $this->cacheService->delete($this->generateCacheKey('membership', $post_id));
                
                // Invalidate all memberships cache
                $this->cacheService->deletePattern('mpai_mp_memberships');
                
                $this->log('Invalidated membership cache', [
                    'post_id' => $post_id
                ]);
            }
        }
        
        /**
         * Invalidate member cache
         *
         * @param int $user_id User ID
         * @return void
         */
    public function invalidateMemberCache($user_id): void
    {
            if ($this->cacheService === null) {
                return;
            }
            
            // Invalidate specific member cache
            $this->cacheService->delete($this->generateCacheKey('member', $user_id));
            
            // Invalidate all members cache
            $this->cacheService->deletePattern('mpai_mp_members');
            
            // Invalidate user memberships cache
            $this->cacheService->delete($this->generateCacheKey('user_memberships', $user_id));
            
            // Invalidate access check caches for this user
            $this->cacheService->deletePattern('mpai_mp_access_check_' . $user_id);
            
            $this->log('Invalidated member cache', [
                'user_id' => $user_id
            ]);
        }
        
        /**
         * Invalidate subscription cache
         *
         * @param \MeprSubscription $subscription Subscription object
         * @return void
         */
    public function invalidateSubscriptionCache($subscription): void
    {
            if ($this->cacheService === null) {
                return;
            }
            
            // Invalidate specific subscription cache
            $this->cacheService->delete($this->generateCacheKey('subscription', $subscription->id));
            
            // Invalidate all subscriptions cache
            $this->cacheService->deletePattern('mpai_mp_subscriptions');
            
            // Invalidate user memberships cache
            $this->cacheService->delete($this->generateCacheKey('user_memberships', $subscription->user_id));
            
            // Invalidate access check caches for this user
            $this->cacheService->deletePattern('mpai_mp_access_check_' . $subscription->user_id);
            
            $this->log('Invalidated subscription cache', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id
            ]);
        }
        
        /**
         * Invalidate transaction cache
         *
         * @param \MeprTransaction $transaction Transaction object
         * @return void
         */
    public function invalidateTransactionCache($transaction): void
    {
            if ($this->cacheService === null) {
                return;
            }
            
            // Invalidate specific transaction cache
            $this->cacheService->delete($this->generateCacheKey('transaction', $transaction->id));
            
            // Invalidate all transactions cache
            $this->cacheService->deletePattern('mpai_mp_transactions');
            
            // Invalidate user memberships cache if user ID is available
            if (isset($transaction->user_id)) {
                $this->cacheService->delete($this->generateCacheKey('user_memberships', $transaction->user_id));
                
                // Invalidate access check caches for this user
                $this->cacheService->deletePattern('mpai_mp_access_check_' . $transaction->user_id);
            }
            
            $this->log('Invalidated transaction cache', [
                'transaction_id' => $transaction->id,
                'user_id' => $transaction->user_id ?? 'unknown'
            ]);
        }
        
        /**
         * Invalidate rule cache
         *
         * @param \MeprRule $rule Rule object
         * @return void
         */
    public function invalidateRuleCache($rule): void
    {
            if ($this->cacheService === null) {
                return;
            }
            
            // Invalidate specific rule cache
            $this->cacheService->delete($this->generateCacheKey('access_rule', $rule->id));
            
            // Invalidate all rules cache
            $this->cacheService->deletePattern('mpai_mp_access_rules');
            
            // Invalidate all access check caches as rules affect access
            $this->cacheService->deletePattern('mpai_mp_access_check');
            
            $this->log('Invalidated rule cache', [
                'rule_id' => $rule->id
            ]);
        }
    /**
     * Get a user's memberships
     *
     * @param int $user_id The user ID
     * @return array The result of the operation
     */
    public function getUserMemberships(int $user_id): array {
        try {
            if (!$this->isMemberPressActive()) {
                throw new \Exception('MemberPress is not active');
            }
            
            // Generate cache key
            $cacheKey = $this->generateCacheKey('user_memberships', $user_id);
            
            // Try to get from cache
            if ($this->cacheService) {
                $cachedResult = $this->cacheService->get($cacheKey);
                if ($cachedResult !== null) {
                    $this->log('Cache hit for user memberships', [
                        'user_id' => $user_id,
                        'cache_key' => $cacheKey
                    ]);
                    return $cachedResult;
                }
            }

            $userAdapter = $this->getAdapter('user');
            if (!$userAdapter) {
                throw new \Exception('User adapter not found');
            }

            // Check if the user exists
            $user = $userAdapter->get($user_id);
            if (!$user) {
                throw new \Exception('User not found');
            }

            // Get the user's memberships
            $memberships = $userAdapter->getMemberships($user);

            // Get detailed membership information
            $productAdapter = $this->getAdapter('product');
            $transformer = $this->getTransformer('product');
            $detailed_memberships = [];

            foreach ($memberships as $membership_id) {
                $product = $productAdapter->get($membership_id);
                if ($product) {
                    if ($transformer) {
                        $detailed_memberships[] = $transformer->transform($product);
                    } else {
                        $detailed_memberships[] = [
                            'id' => $product->ID,
                            'name' => $product->post_title
                        ];
                    }
                }
            }

            $result = [
                'status' => 'success',
                'message' => 'User memberships retrieved successfully',
                'data' => [
                    'user_id' => $user_id,
                    'memberships' => $detailed_memberships,
                    'active_count' => count($memberships)
                ]
            ];
            
            // Cache the result
            if ($this->cacheService) {
                $ttl = $this->getCacheTtl('user_memberships');
                $this->cacheService->set($cacheKey, $result, $ttl);
                $this->log('Cached user memberships', [
                    'user_id' => $user_id,
                    'cache_key' => $cacheKey,
                    'ttl' => $ttl
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            $this->log('Error getting user memberships: ' . $e->getMessage(), [
                'user_id' => $user_id,
                'exception' => $e,
                'level' => 'error'
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Update a user's WordPress role
     *
     * @param int $user_id The user ID
     * @param string $role The role to set
     * @param string $action The action to perform (set, add, remove)
     * @return array The result of the operation
     */
    public function updateUserRole(int $user_id, string $role, string $action = 'set'): array {
        try {
            if (!$this->isMemberPressActive()) {
                throw new \Exception('MemberPress is not active');
            }

            $userAdapter = $this->getAdapter('user');
            if (!$userAdapter) {
                throw new \Exception('User adapter not found');
            }

            // Check if the user exists
            $user = $userAdapter->get($user_id);
            if (!$user) {
                throw new \Exception('User not found');
            }

            // Perform the requested action
            $result = false;
            switch ($action) {
                case 'set':
                    $result = $userAdapter->setRole($user_id, $role);
                    $message = 'User role set successfully';
                    break;
                case 'add':
                    $result = $userAdapter->addRole($user_id, $role);
                    $message = 'User role added successfully';
                    break;
                case 'remove':
                    $result = $userAdapter->removeRole($user_id, $role);
                    $message = 'User role removed successfully';
                    break;
                default:
                    throw new \Exception('Invalid action: ' . $action);
            }

            if (!$result) {
                throw new \Exception('Failed to update user role');
            }

            // Get the user's current roles
            $roles = $userAdapter->getRoles($user_id);

            return [
                'status' => 'success',
                'message' => $message,
                'data' => [
                    'user_id' => $user_id,
                    'roles' => $roles
                ]
            ];
        } catch (\Exception $e) {
            $this->log('Error updating user role: ' . $e->getMessage(), [
                'user_id' => $user_id,
                'role' => $role,
                'action' => $action,
                'exception' => $e,
                'level' => 'error'
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get a user's permissions (roles and capabilities)
     *
     * @param int $user_id The user ID
     * @return array The result of the operation
     */
    public function getUserPermissions(int $user_id): array {
        try {
            if (!$this->isMemberPressActive()) {
                throw new \Exception('MemberPress is not active');
            }

            $userAdapter = $this->getAdapter('user');
            if (!$userAdapter) {
                throw new \Exception('User adapter not found');
            }

            // Check if the user exists
            $user = $userAdapter->get($user_id);
            if (!$user) {
                throw new \Exception('User not found');
            }

            // Get the user's roles and capabilities
            $roles = $userAdapter->getRoles($user_id);
            $capabilities = $userAdapter->getCapabilities($user_id);

            return [
                'status' => 'success',
                'message' => 'User permissions retrieved successfully',
                'data' => [
                    'user_id' => $user_id,
                    'roles' => $roles,
                    'capabilities' => $capabilities
                ]
            ];
        } catch (\Exception $e) {
            $this->log('Error getting user permissions: ' . $e->getMessage(), [
                'user_id' => $user_id,
                'exception' => $e,
                'level' => 'error'
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}