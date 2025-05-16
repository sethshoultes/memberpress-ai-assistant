<?php
/**
 * Cache Service
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Services;

use MemberpressAiAssistant\Abstracts\AbstractService;
use MemberpressAiAssistant\DI\Container;

/**
 * Service for caching data throughout the system
 * 
 * Provides methods for storing and retrieving cached data with TTL support,
 * cache invalidation, and support for different cache storage backends.
 */
class CacheService extends AbstractService {
    /**
     * Default TTL in seconds (1 hour)
     *
     * @var int
     */
    protected $default_ttl = 3600;

    /**
     * Cache prefix to avoid collisions
     *
     * @var string
     */
    protected $prefix = 'mpai_cache_';

    /**
     * Cache storage backend
     * 
     * @var string 'transient' or 'object'
     */
    protected $storage = 'transient';

    /**
     * Debug mode flag
     * 
     * @var bool
     */
    protected $debug = false;

    /**
     * Cache metrics
     * 
     * @var array
     */
    protected $metrics = [
        'hits' => 0,
        'misses' => 0,
        'stores' => 0,
        'deletes' => 0,
    ];

    /**
     * {@inheritdoc}
     */
    public function register($container): void {
        // Register this service with the container
        $container->singleton('cache', function() {
            return $this;
        });

        // Log registration
        $this->log('Cache service registered');
    }

    /**
     * {@inheritdoc}
     */
    public function boot(): void {
        parent::boot();

        // Add hooks
        $this->addHooks();

        // Log boot
        $this->log('Cache service booted');
    }

    /**
     * Add hooks and filters for this service
     *
     * @return void
     */
    protected function addHooks(): void {
        // Add hook to clear cache on plugin update
        add_action('upgrader_process_complete', [$this, 'clearAllCaches'], 10, 2);
        
        // Add hook to clear cache on post update
        add_action('save_post', [$this, 'clearPostRelatedCaches'], 10, 3);
    }

    /**
     * Set the cache storage backend
     *
     * @param string $storage The storage backend ('transient' or 'object')
     * @return self
     */
    public function setStorage(string $storage): self {
        if ($storage !== 'transient' && $storage !== 'object') {
            $this->log('Invalid storage backend: ' . $storage . '. Using default.', ['level' => 'warning']);
            return $this;
        }

        // Check if object cache is available when 'object' is specified
        if ($storage === 'object' && !wp_using_ext_object_cache()) {
            $this->log('Object cache not available. Using transients instead.', ['level' => 'warning']);
            $this->storage = 'transient';
            return $this;
        }

        $this->storage = $storage;
        return $this;
    }

    /**
     * Set the default TTL for cache items
     *
     * @param int $ttl TTL in seconds
     * @return self
     */
    public function setDefaultTTL(int $ttl): self {
        $this->default_ttl = max(0, $ttl);
        return $this;
    }

    /**
     * Set the cache prefix
     *
     * @param string $prefix The prefix to use
     * @return self
     */
    public function setPrefix(string $prefix): self {
        $this->prefix = sanitize_key($prefix);
        return $this;
    }

    /**
     * Enable or disable debug mode
     * 
     * When debug mode is enabled, cache is bypassed
     *
     * @param bool $debug Whether to enable debug mode
     * @return self
     */
    public function setDebug(bool $debug): self {
        $this->debug = $debug;
        return $this;
    }

    /**
     * Generate a cache key with prefix
     *
     * @param string $key The base key
     * @return string The prefixed key
     */
    public function generateKey(string $key): string {
        return $this->prefix . md5($key);
    }

    /**
     * Store data in the cache
     *
     * @param string $key The cache key
     * @param mixed $data The data to store
     * @param int|null $ttl TTL in seconds, null to use default
     * @return bool Whether the data was stored successfully
     */
    public function set(string $key, $data, ?int $ttl = null): bool {
        // Skip if in debug mode
        if ($this->debug) {
            $this->log('Cache bypassed in debug mode', [
                'key' => $key,
                'operation' => 'set'
            ]);
            return false;
        }

        $ttl = $ttl ?? $this->default_ttl;
        $prefixed_key = $this->generateKey($key);
        
        // Prepare data for storage with metadata
        $cache_data = [
            'data' => $data,
            'created' => time(),
            'expires' => time() + $ttl
        ];

        $result = false;
        
        if ($this->storage === 'object') {
            $result = wp_cache_set($prefixed_key, $cache_data, 'memberpress_ai_assistant', $ttl);
        } else {
            $result = set_transient($prefixed_key, $cache_data, $ttl);
        }

        if ($result) {
            $this->metrics['stores']++;
            $this->log('Cache set', [
                'key' => $key,
                'ttl' => $ttl,
                'storage' => $this->storage
            ]);
        }

        return $result;
    }

    /**
     * Retrieve data from the cache
     *
     * @param string $key The cache key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed The cached data or default value
     */
    public function get(string $key, $default = null) {
        // Skip if in debug mode
        if ($this->debug) {
            $this->log('Cache bypassed in debug mode', [
                'key' => $key,
                'operation' => 'get'
            ]);
            $this->metrics['misses']++;
            return $default;
        }

        $prefixed_key = $this->generateKey($key);
        $cache_data = null;
        
        if ($this->storage === 'object') {
            $cache_data = wp_cache_get($prefixed_key, 'memberpress_ai_assistant');
        } else {
            $cache_data = get_transient($prefixed_key);
        }

        // Check if data exists and is not expired
        if ($cache_data !== false && is_array($cache_data) && isset($cache_data['data'])) {
            // Check if manually expired
            if (isset($cache_data['expires']) && $cache_data['expires'] < time()) {
                $this->delete($key);
                $this->metrics['misses']++;
                $this->log('Cache expired', ['key' => $key]);
                return $default;
            }

            $this->metrics['hits']++;
            $this->log('Cache hit', ['key' => $key]);
            return $cache_data['data'];
        }

        $this->metrics['misses']++;
        $this->log('Cache miss', ['key' => $key]);
        return $default;
    }

    /**
     * Check if a key exists in the cache
     *
     * @param string $key The cache key
     * @return bool Whether the key exists
     */
    public function has(string $key): bool {
        // Skip if in debug mode
        if ($this->debug) {
            return false;
        }

        $prefixed_key = $this->generateKey($key);
        
        if ($this->storage === 'object') {
            return wp_cache_get($prefixed_key, 'memberpress_ai_assistant') !== false;
        } else {
            return get_transient($prefixed_key) !== false;
        }
    }

    /**
     * Delete a key from the cache
     *
     * @param string $key The cache key
     * @return bool Whether the key was deleted
     */
    public function delete(string $key): bool {
        $prefixed_key = $this->generateKey($key);
        $result = false;
        
        if ($this->storage === 'object') {
            $result = wp_cache_delete($prefixed_key, 'memberpress_ai_assistant');
        } else {
            $result = delete_transient($prefixed_key);
        }

        if ($result) {
            $this->metrics['deletes']++;
            $this->log('Cache deleted', ['key' => $key]);
        }

        return $result;
    }

    /**
     * Delete multiple keys from the cache using a pattern
     *
     * @param string $pattern The pattern to match keys against
     * @return int Number of keys deleted
     */
    public function deletePattern(string $pattern): int {
        global $wpdb;
        $count = 0;

        if ($this->storage === 'transient') {
            // For transients, we need to query the database
            $sql = $wpdb->prepare(
                "SELECT option_name FROM $wpdb->options WHERE option_name LIKE %s",
                '_transient_' . $this->prefix . '%'
            );
            
            $transients = $wpdb->get_col($sql);
            
            foreach ($transients as $transient) {
                // Extract the key without the _transient_ prefix
                $key = str_replace('_transient_', '', $transient);
                
                // Check if the key matches the pattern
                if (strpos($key, $pattern) !== false) {
                    if (delete_transient(str_replace('_transient_', '', $transient))) {
                        $count++;
                    }
                }
            }
        } else {
            // For object cache, we can't easily delete by pattern
            // This is a limitation of the object cache API
            $this->log('Pattern deletion not supported for object cache', [
                'pattern' => $pattern,
                'level' => 'warning'
            ]);
        }

        if ($count > 0) {
            $this->metrics['deletes'] += $count;
            $this->log('Cache pattern deleted', [
                'pattern' => $pattern,
                'count' => $count
            ]);
        }

        return $count;
    }

    /**
     * Clear all caches managed by this service
     *
     * @param \WP_Upgrader $upgrader Upgrader instance
     * @param array $options Upgrader options
     * @return void
     */
    public function clearAllCaches($upgrader = null, $options = []): void {
        global $wpdb;
        $count = 0;

        // Check if this is our plugin being updated
        if (isset($options['action']) && $options['action'] === 'update' && 
            isset($options['type']) && $options['type'] === 'plugin' &&
            isset($options['plugins']) && in_array('memberpress-ai-assistant/memberpress-ai-assistant.php', $options['plugins'])) {
            
            // Clear all transients with our prefix
            if ($this->storage === 'transient') {
                $sql = $wpdb->prepare(
                    "SELECT option_name FROM $wpdb->options WHERE option_name LIKE %s",
                    '_transient_' . $this->prefix . '%'
                );
                
                $transients = $wpdb->get_col($sql);
                
                foreach ($transients as $transient) {
                    if (delete_transient(str_replace('_transient_', '', $transient))) {
                        $count++;
                    }
                }
            } else {
                // For object cache, we can flush the group
                wp_cache_flush_group('memberpress_ai_assistant');
                $count = 'all';
            }

            $this->log('All caches cleared on plugin update', [
                'count' => $count
            ]);
        }
    }

    /**
     * Clear caches related to a specific post
     *
     * @param int $post_id Post ID
     * @param \WP_Post $post Post object
     * @param bool $update Whether this is an update
     * @return void
     */
    public function clearPostRelatedCaches($post_id, $post, $update): void {
        // Skip revisions and auto-saves
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }

        // Clear caches related to this post
        $this->deletePattern('post_' . $post_id);
        
        // If this is a MemberPress product, clear membership-related caches
        if ($post->post_type === 'memberpressproduct') {
            $this->deletePattern('membership_');
            $this->log('Membership caches cleared', [
                'post_id' => $post_id
            ]);
        }
    }

    /**
     * Get cache metrics
     *
     * @return array The cache metrics
     */
    public function getMetrics(): array {
        $total = $this->metrics['hits'] + $this->metrics['misses'];
        $hit_rate = $total > 0 ? ($this->metrics['hits'] / $total) * 100 : 0;
        
        return [
            'hits' => $this->metrics['hits'],
            'misses' => $this->metrics['misses'],
            'stores' => $this->metrics['stores'],
            'deletes' => $this->metrics['deletes'],
            'hit_rate' => round($hit_rate, 2) . '%',
            'storage' => $this->storage,
            'debug' => $this->debug
        ];
    }

    /**
     * Reset cache metrics
     *
     * @return void
     */
    public function resetMetrics(): void {
        $this->metrics = [
            'hits' => 0,
            'misses' => 0,
            'stores' => 0,
            'deletes' => 0,
        ];
        
        $this->log('Cache metrics reset');
    }

    /**
     * Store data in the cache with a remember callback
     * 
     * If the key exists, returns the cached value
     * If not, executes the callback and stores the result
     *
     * @param string $key The cache key
     * @param callable $callback Function to generate the value
     * @param int|null $ttl TTL in seconds, null to use default
     * @return mixed The cached or generated data
     */
    public function remember(string $key, callable $callback, ?int $ttl = null) {
        // Check if the value is cached
        if (!$this->debug && $this->has($key)) {
            return $this->get($key);
        }

        // Generate the value
        $value = $callback();

        // Store in cache if not in debug mode
        if (!$this->debug) {
            $this->set($key, $value, $ttl);
        }

        return $value;
    }
}