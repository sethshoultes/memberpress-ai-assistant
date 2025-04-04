<?php
/**
 * Response Cache for AI API responses
 *
 * @package MemberPress AI Assistant
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Response Cache Class
 */
class MPAI_Response_Cache {
	/**
	 * In-memory cache
	 * 
	 * @var array
	 */
	private $cache = [];
	
	/**
	 * Whether filesystem caching is enabled
	 * 
	 * @var bool
	 */
	private $filesystem_cache_enabled = false;
	
	/**
	 * Whether database caching is enabled
	 * 
	 * @var bool
	 */
	private $db_cache_enabled = false;
	
	/**
	 * Cache TTL in seconds
	 * 
	 * @var int
	 */
	private $cache_ttl = 3600; // 1 hour default
	
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
		
		if (isset($config['db_cache'])) {
			$this->db_cache_enabled = (bool)$config['db_cache'];
		}
		
		if (isset($config['cache_ttl'])) {
			$this->cache_ttl = (int)$config['cache_ttl'];
		}
	}
	
	/**
	 * Get cached response
	 * 
	 * @param string $key Cache key
	 * @return mixed|null Cached data or null if not found
	 */
	public function get($key) {
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
		
		// Check filesystem cache next
		if ($this->filesystem_cache_enabled) {
			$data = $this->get_from_filesystem($key);
			if ($data !== null) {
				// Store in memory for future use
				$this->cache[$key] = [
					'data' => $data,
					'expires' => time() + $this->cache_ttl
				];
				
				return $data;
			}
		}
		
		// Check database cache last
		if ($this->db_cache_enabled) {
			$data = $this->get_from_database($key);
			if ($data !== null) {
				// Store in memory for future use
				$this->cache[$key] = [
					'data' => $data,
					'expires' => time() + $this->cache_ttl
				];
				
				return $data;
			}
		}
		
		return null;
	}
	
	/**
	 * Set cached response
	 * 
	 * @param string $key Cache key
	 * @param mixed $data Data to cache
	 * @param int|null $ttl Optional TTL override
	 * @return bool Success
	 */
	public function set($key, $data, $ttl = null) {
		$expires = time() + ($ttl !== null ? $ttl : $this->cache_ttl);
		
		// Store in memory
		$this->cache[$key] = [
			'data' => $data,
			'expires' => $expires
		];
		
		// Store in filesystem if enabled
		if ($this->filesystem_cache_enabled) {
			$this->set_in_filesystem($key, $data, $expires);
		}
		
		// Store in database if enabled
		if ($this->db_cache_enabled) {
			$this->set_in_database($key, $data, $expires);
		}
		
		return true;
	}
	
	/**
	 * Remove cached response
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
		
		// Remove from database if enabled
		if ($this->db_cache_enabled) {
			$this->delete_from_database($key);
		}
		
		return true;
	}
	
	/**
	 * Clear all cached responses
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
		
		// Clear database cache if enabled
		if ($this->db_cache_enabled) {
			$this->clear_database_cache();
		}
		
		return true;
	}
	
	/**
	 * Get response from filesystem cache
	 * 
	 * @param string $key Cache key
	 * @return mixed|null Cached data or null if not found
	 */
	private function get_from_filesystem($key) {
		$cache_dir = $this->get_cache_dir();
		if (!$cache_dir) {
			return null;
		}
		
		$cache_file = $cache_dir . '/' . md5($key) . '.cache';
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
	 * Store response in filesystem cache
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
		
		$cache_file = $cache_dir . '/' . md5($key) . '.cache';
		$content = serialize([
			'key' => $key,
			'data' => $data,
			'created' => time(),
			'expires' => $expires
		]);
		
		return @file_put_contents($cache_file, $content) !== false;
	}
	
	/**
	 * Remove response from filesystem cache
	 * 
	 * @param string $key Cache key
	 * @return bool Success
	 */
	private function delete_from_filesystem($key) {
		$cache_dir = $this->get_cache_dir();
		if (!$cache_dir) {
			return false;
		}
		
		$cache_file = $cache_dir . '/' . md5($key) . '.cache';
		if (file_exists($cache_file)) {
			return @unlink($cache_file);
		}
		
		return true;
	}
	
	/**
	 * Clear all filesystem cached responses
	 * 
	 * @return bool Success
	 */
	private function clear_filesystem_cache() {
		$cache_dir = $this->get_cache_dir();
		if (!$cache_dir) {
			return false;
		}
		
		$files = glob($cache_dir . '/*.cache');
		if (!$files) {
			return true;
		}
		
		$success = true;
		foreach ($files as $file) {
			if (@unlink($file) === false) {
				$success = false;
			}
		}
		
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
	 * Get response from database cache
	 * The table will be created lazily when needed
	 * 
	 * @param string $key Cache key
	 * @return mixed|null Cached data or null if not found
	 */
	private function get_from_database($key) {
		global $wpdb;
		
		// Create table if it doesn't exist
		$this->maybe_create_cache_table();
		
		$table_name = $wpdb->prefix . 'mpai_response_cache';
		$data = $wpdb->get_row(
			$wpdb->prepare("SELECT * FROM {$table_name} WHERE cache_key = %s", $key),
			ARRAY_A
		);
		
		if (!$data) {
			return null;
		}
		
		// Check if expired
		if (time() > $data['expires']) {
			$wpdb->delete($table_name, ['id' => $data['id']], ['%d']);
			return null;
		}
		
		return maybe_unserialize($data['cache_value']);
	}
	
	/**
	 * Store response in database cache
	 * 
	 * @param string $key Cache key
	 * @param mixed $data Data to cache
	 * @param int $expires Expiry timestamp
	 * @return bool Success
	 */
	private function set_in_database($key, $data, $expires) {
		global $wpdb;
		
		// Create table if it doesn't exist
		$this->maybe_create_cache_table();
		
		$table_name = $wpdb->prefix . 'mpai_response_cache';
		
		// Check if key already exists
		$existing = $wpdb->get_var(
			$wpdb->prepare("SELECT id FROM {$table_name} WHERE cache_key = %s", $key)
		);
		
		if ($existing) {
			// Update existing record
			$result = $wpdb->update(
				$table_name,
				[
					'cache_value' => maybe_serialize($data),
					'created' => time(),
					'expires' => $expires
				],
				['cache_key' => $key],
				['%s', '%d', '%d'],
				['%s']
			);
			
			return $result !== false;
		}
		
		// Insert new record
		$result = $wpdb->insert(
			$table_name,
			[
				'cache_key' => $key,
				'cache_value' => maybe_serialize($data),
				'created' => time(),
				'expires' => $expires
			],
			['%s', '%s', '%d', '%d']
		);
		
		return $result !== false;
	}
	
	/**
	 * Remove response from database cache
	 * 
	 * @param string $key Cache key
	 * @return bool Success
	 */
	private function delete_from_database($key) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'mpai_response_cache';
		
		// Check if table exists
		if (!$this->check_cache_table_exists()) {
			return true;
		}
		
		$result = $wpdb->delete(
			$table_name,
			['cache_key' => $key],
			['%s']
		);
		
		return $result !== false;
	}
	
	/**
	 * Clear all database cached responses
	 * 
	 * @return bool Success
	 */
	private function clear_database_cache() {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'mpai_response_cache';
		
		// Check if table exists
		if (!$this->check_cache_table_exists()) {
			return true;
		}
		
		$result = $wpdb->query("TRUNCATE TABLE {$table_name}");
		
		return $result !== false;
	}
	
	/**
	 * Create database cache table if needed
	 * 
	 * @return bool Success
	 */
	private function maybe_create_cache_table() {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'mpai_response_cache';
		
		// Check if table exists
		if ($this->check_cache_table_exists()) {
			return true;
		}
		
		// Create table
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			cache_key varchar(255) NOT NULL,
			cache_value longtext NOT NULL,
			created int(11) NOT NULL,
			expires int(11) NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY cache_key (cache_key),
			KEY expires (expires)
		) {$charset_collate};";
		
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		
		// Verify table was created
		return $this->check_cache_table_exists();
	}
	
	/**
	 * Check if database cache table exists
	 * 
	 * @return bool Whether table exists
	 */
	private function check_cache_table_exists() {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'mpai_response_cache';
		return $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
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
		
		// Clean up database cache
		if ($this->db_cache_enabled) {
			$this->cleanup_database_cache();
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
		
		$files = glob($cache_dir . '/*.cache');
		if (!$files) {
			return true;
		}
		
		$now = time();
		$success = true;
		
		foreach ($files as $file) {
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
			}
		}
		
		return $success;
	}
	
	/**
	 * Clean up expired database cache entries
	 * 
	 * @return bool Success
	 */
	private function cleanup_database_cache() {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'mpai_response_cache';
		
		// Check if table exists
		if (!$this->check_cache_table_exists()) {
			return true;
		}
		
		$result = $wpdb->query(
			$wpdb->prepare("DELETE FROM {$table_name} WHERE expires < %d", time())
		);
		
		return $result !== false;
	}
}