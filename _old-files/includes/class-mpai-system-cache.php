<?php
/**
 * System Information Cache for MemberPress AI Assistant
 *
 * @package MemberPress AI Assistant
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * System Information Cache Class
 * 
 * Provides a caching layer for system information queries that don't change frequently.
 * Improves performance for repeated system information requests from AI tools.
 */
class MPAI_System_Cache {
	/**
	 * In-memory cache
	 * 
	 * @var array
	 */
	private $cache = [];
	
	/**
	 * Cache TTL in seconds
	 * 
	 * @var array
	 */
	private $ttl_settings = [
		'default' => 3600, // 1 hour default
		'php_info' => 86400, // 24 hours for PHP info
		'wp_info' => 3600, // 1 hour for WordPress info
		'plugin_list' => 1800, // 30 minutes for plugin list
		'plugin_status' => 1800, // 30 minutes for plugin status
		'theme_list' => 3600, // 1 hour for theme list
		'system_info' => 3600, // 1 hour for system info
		'db_info' => 3600, // 1 hour for database info
		'site_health' => 3600, // 1 hour for site health info
	];
	
	/**
	 * Filesystem cache expiration timestamps
	 * 
	 * @var array
	 */
	private $fs_cache_expiry = [];
	
	/**
	 * Whether filesystem caching is enabled
	 * 
	 * @var bool
	 */
	private $filesystem_cache_enabled = true;
	
	/**
	 * Whether response caching is preloaded
	 * 
	 * @var bool
	 */
	private $is_preloaded = false;
	
	/**
	 * Instance of this class
	 *
	 * @var MPAI_System_Cache
	 */
	private static $instance = null;
	
	/**
	 * Get instance of the class
	 *
	 * @return MPAI_System_Cache
	 */
	public static function get_instance() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
	/**
	 * Constructor
	 * 
	 * @param array $config Configuration options
	 */
	public function __construct($config = []) {
		// Set configuration
		if (isset($config['filesystem_cache'])) {
			$this->filesystem_cache_enabled = (bool)$config['filesystem_cache'];
		}
		
		if (isset($config['ttl_settings']) && is_array($config['ttl_settings'])) {
			$this->ttl_settings = array_merge($this->ttl_settings, $config['ttl_settings']);
		}
		
		// Maybe load cached values from filesystem
		$this->maybe_load_filesystem_cache();
		
		// Register hooks for automatic cache invalidation
		$this->register_invalidation_hooks();
	}
	
	/**
	 * Get cached system information
	 * 
	 * @param string $key Cache key
	 * @param string $type Information type (for TTL determination)
	 * @return mixed|null Cached data or null if not found
	 */
	public function get($key, $type = 'default') {
		// Check memory cache first
		if (isset($this->cache[$key])) {
			$cached = $this->cache[$key];
			
			// Check if expired
			if (time() <= $cached['expires']) {
				return $cached['data'];
			}
			
			// Expired, remove from memory
			unset($this->cache[$key]);
		}
		
		// Check filesystem cache if enabled
		if ($this->filesystem_cache_enabled) {
			$data = $this->get_from_filesystem($key);
			if ($data !== null) {
				// Store in memory for future use
				$ttl = isset($this->ttl_settings[$type]) ? $this->ttl_settings[$type] : $this->ttl_settings['default'];
				$this->cache[$key] = [
					'data' => $data,
					'expires' => time() + $ttl
				];
				
				return $data;
			}
		}
		
		return null;
	}
	
	/**
	 * Set cached system information
	 * 
	 * @param string $key Cache key
	 * @param mixed $data Data to cache
	 * @param string $type Information type (for TTL determination)
	 * @return bool Success
	 */
	public function set($key, $data, $type = 'default') {
		$ttl = isset($this->ttl_settings[$type]) ? $this->ttl_settings[$type] : $this->ttl_settings['default'];
		$expires = time() + $ttl;
		
		// Store in memory
		$this->cache[$key] = [
			'data' => $data,
			'expires' => $expires
		];
		
		// Store in filesystem if enabled
		if ($this->filesystem_cache_enabled) {
			$this->set_in_filesystem($key, $data, $expires);
		}
		
		return true;
	}
	
	/**
	 * Remove cached system information
	 * 
	 * @param string $key Cache key
	 * @return bool Success
	 */
	public function delete($key) {
		// Remove from memory
		if (isset($this->cache[$key])) {
			unset($this->cache[$key]);
		}
		
		// Remove from filesystem if enabled
		if ($this->filesystem_cache_enabled) {
			$this->delete_from_filesystem($key);
		}
		
		return true;
	}
	
	/**
	 * Clear all cached system information
	 * 
	 * @return bool Success
	 */
	public function clear() {
		// Clear memory cache
		$this->cache = [];
		
		// Clear filesystem cache if enabled
		if ($this->filesystem_cache_enabled) {
			$this->clear_filesystem_cache();
		}
		
		return true;
	}
	
	/**
	 * Get system information from filesystem cache
	 * 
	 * @param string $key Cache key
	 * @return mixed|null Cached data or null if not found
	 */
	private function get_from_filesystem($key) {
		$cache_dir = $this->get_cache_dir();
		if (!$cache_dir) {
			return null;
		}
		
		$cache_file = $cache_dir . '/sysinfo_' . md5($key) . '.cache';
		if (!file_exists($cache_file)) {
			return null;
		}
		
		$content = @file_get_contents($cache_file);
		if ($content === false) {
			return null;
		}
		
		$data = @unserialize($content);
		if ($data === false) {
			return null;
		}
		
		// Check if expired
		if (time() > $data['expires']) {
			@unlink($cache_file);
			return null;
		}
		
		return $data['data'];
	}
	
	/**
	 * Store system information in filesystem cache
	 * 
	 * @param string $key Cache key
	 * @param mixed $data Data to cache
	 * @param int $expires Expiry timestamp
	 * @return bool Success
	 */
	private function set_in_filesystem($key, $data, $expires) {
		$cache_dir = $this->get_cache_dir();
		if (!$cache_dir) {
			return false;
		}
		
		$cache_file = $cache_dir . '/sysinfo_' . md5($key) . '.cache';
		$content = serialize([
			'key' => $key,
			'data' => $data,
			'created' => time(),
			'expires' => $expires
		]);
		
		// Store expiry for future reference
		$this->fs_cache_expiry[$key] = $expires;
		
		// Save expiry metadata
		$this->save_expiry_metadata();
		
		return @file_put_contents($cache_file, $content) !== false;
	}
	
	/**
	 * Remove system information from filesystem cache
	 * 
	 * @param string $key Cache key
	 * @return bool Success
	 */
	private function delete_from_filesystem($key) {
		$cache_dir = $this->get_cache_dir();
		if (!$cache_dir) {
			return false;
		}
		
		$cache_file = $cache_dir . '/sysinfo_' . md5($key) . '.cache';
		if (file_exists($cache_file)) {
			// Remove from expiry metadata
			if (isset($this->fs_cache_expiry[$key])) {
				unset($this->fs_cache_expiry[$key]);
				$this->save_expiry_metadata();
			}
			
			return @unlink($cache_file);
		}
		
		return true;
	}
	
	/**
	 * Clear all filesystem cached system information
	 * 
	 * @return bool Success
	 */
	private function clear_filesystem_cache() {
		$cache_dir = $this->get_cache_dir();
		if (!$cache_dir) {
			return false;
		}
		
		$files = glob($cache_dir . '/sysinfo_*.cache');
		if (!$files) {
			return true;
		}
		
		$success = true;
		foreach ($files as $file) {
			if (@unlink($file) === false) {
				$success = false;
			}
		}
		
		// Clear expiry metadata
		$this->fs_cache_expiry = [];
		$this->save_expiry_metadata();
		
		return $success;
	}
	
	/**
	 * Get filesystem cache directory
	 * 
	 * @return string|bool Cache directory path or false on failure
	 */
	private function get_cache_dir() {
		$upload_dir = wp_upload_dir();
		$cache_dir = $upload_dir['basedir'] . '/mpai-cache';
		
		// Try to create directory if it doesn't exist
		if (!file_exists($cache_dir)) {
			if (!wp_mkdir_p($cache_dir)) {
				return false;
			}
			
			// Create .htaccess file to protect cache
			$htaccess = $cache_dir . '/.htaccess';
			@file_put_contents($htaccess, 'Deny from all');
		}
		
		// Check if directory is writable
		if (!is_writable($cache_dir)) {
			return false;
		}
		
		return $cache_dir;
	}
	
	/**
	 * Save expiry metadata to filesystem
	 * 
	 * @return bool Success
	 */
	private function save_expiry_metadata() {
		$cache_dir = $this->get_cache_dir();
		if (!$cache_dir) {
			return false;
		}
		
		$meta_file = $cache_dir . '/sysinfo_meta.json';
		$content = wp_json_encode($this->fs_cache_expiry);
		
		return @file_put_contents($meta_file, $content) !== false;
	}
	
	/**
	 * Load expiry metadata from filesystem
	 * 
	 * @return bool Success
	 */
	private function load_expiry_metadata() {
		$cache_dir = $this->get_cache_dir();
		if (!$cache_dir) {
			return false;
		}
		
		$meta_file = $cache_dir . '/sysinfo_meta.json';
		if (!file_exists($meta_file)) {
			return false;
		}
		
		$content = @file_get_contents($meta_file);
		if ($content === false) {
			return false;
		}
		
		$data = json_decode($content, true);
		if ($data === null) {
			return false;
		}
		
		$this->fs_cache_expiry = $data;
		return true;
	}
	
	/**
	 * Maybe load cached values from filesystem
	 * 
	 * @return bool Success
	 */
	private function maybe_load_filesystem_cache() {
		if (!$this->filesystem_cache_enabled) {
			return false;
		}
		
		// Load expiry metadata
		if (!$this->load_expiry_metadata()) {
			return false;
		}
		
		// Load unexpired cache items
		$now = time();
		foreach ($this->fs_cache_expiry as $key => $expires) {
			if ($expires > $now) {
				$data = $this->get_from_filesystem($key);
				if ($data !== null) {
					$this->cache[$key] = [
						'data' => $data,
						'expires' => $expires
					];
				}
			}
		}
		
		return true;
	}
	
	/**
	 * Register hooks for automatic cache invalidation
	 */
	private function register_invalidation_hooks() {
		// Plugin activation/deactivation should invalidate plugin-related caches
		add_action('activated_plugin', array($this, 'invalidate_plugin_cache'));
		add_action('deactivated_plugin', array($this, 'invalidate_plugin_cache'));
		add_action('upgrader_process_complete', array($this, 'invalidate_update_cache'), 10, 2);
		
		// Theme changes should invalidate theme-related caches
		add_action('switch_theme', array($this, 'invalidate_theme_cache'));
		
		// WordPress updates should invalidate core-related caches
		add_action('upgrader_process_complete', array($this, 'invalidate_core_cache'), 10, 2);
		
		// MemberPress specific hooks if available
		if (class_exists('MeprHooks')) {
			add_action('mepr-txn-store', array($this, 'invalidate_memberpress_cache'));
			add_action('mepr-subscription-created', array($this, 'invalidate_memberpress_cache'));
			add_action('mepr-event-store', array($this, 'invalidate_memberpress_cache'));
		}
	}
	
	/**
	 * Invalidate plugin-related caches
	 */
	public function invalidate_plugin_cache() {
		$this->delete('plugin_list');
		$this->delete('plugin_status');
		$this->delete('plugin_recent');
		$this->delete('site_health');
		$this->delete('active_plugins');
	}
	
	/**
	 * Invalidate theme-related caches
	 */
	public function invalidate_theme_cache() {
		$this->delete('theme_list');
		$this->delete('site_health');
	}
	
	/**
	 * Invalidate update-related caches
	 * 
	 * @param WP_Upgrader $upgrader WP_Upgrader instance
	 * @param array $options Options
	 */
	public function invalidate_update_cache($upgrader, $options) {
		if (isset($options['type'])) {
			switch ($options['type']) {
				case 'plugin':
					$this->invalidate_plugin_cache();
					break;
				case 'theme':
					$this->invalidate_theme_cache();
					break;
				case 'core':
					$this->invalidate_core_cache();
					break;
			}
		}
	}
	
	/**
	 * Invalidate core-related caches
	 */
	public function invalidate_core_cache() {
		$this->delete('core_version');
		$this->delete('site_health');
		$this->delete('system_info');
		$this->delete('wp_info');
	}
	
	/**
	 * Invalidate MemberPress-related caches
	 */
	public function invalidate_memberpress_cache() {
		$this->delete('memberpress_status');
		$this->delete('memberpress_transactions');
		$this->delete('memberpress_subscriptions');
		$this->delete('memberpress_members');
	}
	
	/**
	 * Preload common system information
	 * 
	 * @return bool Success
	 */
	public function preload_common_info() {
		// Don't preload if already done
		if ($this->is_preloaded) {
			return true;
		}
		
		// Preload PHP information if not already cached
		if (!isset($this->cache['php_info'])) {
			$php_version = phpversion();
			$php_info = [
				'version' => $php_version,
				'sapi' => php_sapi_name(),
				'memory_limit' => ini_get('memory_limit'),
				'max_execution_time' => ini_get('max_execution_time'),
				'upload_max_filesize' => ini_get('upload_max_filesize'),
				'post_max_size' => ini_get('post_max_size'),
				'extensions' => get_loaded_extensions(),
			];
			
			$this->set('php_info', $php_info, 'php_info');
		}
		
		// Preload WordPress information if not already cached
		if (!isset($this->cache['wp_info'])) {
			global $wp_version;
			
			$wp_info = [
				'version' => $wp_version,
				'site_url' => get_site_url(),
				'home_url' => get_home_url(),
				'is_multisite' => is_multisite(),
				'theme' => wp_get_theme()->get('Name'),
				'theme_version' => wp_get_theme()->get('Version'),
			];
			
			$this->set('wp_info', $wp_info, 'wp_info');
		}
		
		// Preload plugin count information if not already cached
		if (!isset($this->cache['plugin_count'])) {
			if (!function_exists('get_plugins')) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			
			$all_plugins = get_plugins();
			$active_plugins = get_option('active_plugins');
			
			$plugin_count = [
				'total' => count($all_plugins),
				'active' => count($active_plugins),
				'inactive' => count($all_plugins) - count($active_plugins),
			];
			
			$this->set('plugin_count', $plugin_count, 'plugin_status');
		}
		
		$this->is_preloaded = true;
		return true;
	}
	
	/**
	 * Clean up expired cache entries
	 * 
	 * @return bool Success
	 */
	public function cleanup() {
		// Clean up memory cache
		foreach ($this->cache as $key => $data) {
			if (time() > $data['expires']) {
				unset($this->cache[$key]);
			}
		}
		
		// Clean up filesystem cache
		if ($this->filesystem_cache_enabled) {
			$this->cleanup_filesystem_cache();
		}
		
		return true;
	}
	
	/**
	 * Clean up expired filesystem cache entries
	 * 
	 * @return bool Success
	 */
	private function cleanup_filesystem_cache() {
		$cache_dir = $this->get_cache_dir();
		if (!$cache_dir) {
			return false;
		}
		
		$files = glob($cache_dir . '/sysinfo_*.cache');
		if (!$files) {
			return true;
		}
		
		$now = time();
		$success = true;
		$valid_cache_files = [];
		
		foreach ($files as $file) {
			// Skip the metadata file
			if (strpos($file, 'sysinfo_meta.json') !== false) {
				continue;
			}
			
			$content = @file_get_contents($file);
			if ($content === false) {
				continue;
			}
			
			$data = @unserialize($content);
			if ($data === false) {
				// Invalid cache file, try to remove it
				@unlink($file);
				continue;
			}
			
			// Check if expired
			if ($now > $data['expires']) {
				if (@unlink($file) === false) {
					$success = false;
				}
				
				// Remove from expiry metadata
				if (isset($data['key']) && isset($this->fs_cache_expiry[$data['key']])) {
					unset($this->fs_cache_expiry[$data['key']]);
				}
			} else {
				// Still valid, keep track of its key
				if (isset($data['key'])) {
					$valid_cache_files[$data['key']] = $data['expires'];
				}
			}
		}
		
		// Update expiry metadata to match actual files
		$this->fs_cache_expiry = $valid_cache_files;
		$this->save_expiry_metadata();
		
		return $success;
	}
}