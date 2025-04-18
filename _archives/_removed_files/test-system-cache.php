<?php
/**
 * Test script for System Information Caching
 *
 * @package MemberPress AI Assistant
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Test the System Information Cache functionality
 *
 * @return array Test results
 */
function mpai_test_system_information_cache() {
	$results = [
		'success' => false,
		'message' => '',
		'data' => [
			'tests' => [],
			'cache_hits' => 0,
			'timing' => []
		]
	];
	
	try {
		// Load required classes
		if (!class_exists('MPAI_System_Cache')) {
			require_once dirname(dirname(__FILE__)) . '/includes/class-mpai-system-cache.php';
		}
		
		if (!class_exists('MPAI_WP_CLI_Tool')) {
			require_once dirname(dirname(__FILE__)) . '/includes/tools/implementations/class-mpai-wpcli-tool.php';
		}
		
		// Initialize system cache
		$system_cache = MPAI_System_Cache::get_instance();
		$wp_cli_tool = new MPAI_WP_CLI_Tool();
		
		// Clear existing cache
		$system_cache->clear();
		
		// Test 1: Basic Cache Operations
		$start_time = microtime(true);
		$test_data = ['test_key' => 'test_value', 'timestamp' => time()];
		$set_result = $system_cache->set('test_key', $test_data, 'default');
		$get_result = $system_cache->get('test_key', 'default');
		$end_time = microtime(true);
		
		$results['data']['tests'][] = [
			'name' => 'Basic Cache Operations',
			'success' => ($set_result && $get_result !== null && $get_result['test_key'] === 'test_value'),
			'message' => 'Cache should be able to store and retrieve data',
			'timing' => number_format(($end_time - $start_time) * 1000, 2) . ' ms'
		];
		
		// Test 2: PHP Info Caching
		$start_time_first = microtime(true);
		$first_result = $wp_cli_tool->execute(['command' => 'wp php info']);
		$end_time_first = microtime(true);
		
		$start_time_second = microtime(true);
		$second_result = $wp_cli_tool->execute(['command' => 'wp php info']);
		$end_time_second = microtime(true);
		
		$first_timing = number_format(($end_time_first - $start_time_first) * 1000, 2);
		$second_timing = number_format(($end_time_second - $start_time_second) * 1000, 2);
		$is_cached = (strpos($second_result, '[CACHED]') !== false);
		
		$results['data']['tests'][] = [
			'name' => 'PHP Info Caching',
			'success' => $is_cached,
			'message' => 'Second request should be served from cache',
			'timing' => [
				'first_request' => $first_timing . ' ms',
				'second_request' => $second_timing . ' ms',
				'improvement' => number_format(($first_timing - $second_timing) / $first_timing * 100, 2) . '%'
			]
		];
		
		// Test 3: Site Health Caching
		$start_time_first = microtime(true);
		$first_result = $wp_cli_tool->execute(['command' => 'wp site health', 'skip_plugins' => true]);
		$end_time_first = microtime(true);
		
		$start_time_second = microtime(true);
		$second_result = $wp_cli_tool->execute(['command' => 'wp site health', 'skip_plugins' => true]);
		$end_time_second = microtime(true);
		
		$first_timing = number_format(($end_time_first - $start_time_first) * 1000, 2);
		$second_timing = number_format(($end_time_second - $start_time_second) * 1000, 2);
		$is_cached = (strpos($second_result, '[CACHED]') !== false);
		
		$results['data']['tests'][] = [
			'name' => 'Site Health Caching',
			'success' => $is_cached,
			'message' => 'Second request should be served from cache',
			'timing' => [
				'first_request' => $first_timing . ' ms',
				'second_request' => $second_timing . ' ms',
				'improvement' => number_format(($first_timing - $second_timing) / $first_timing * 100, 2) . '%'
			]
		];
		
		// Test 4: Cache Expiration
		$system_cache->set('expiring_test', 'test_data', 'default');
		
		// Manually override TTL settings with a very short TTL for testing
		$reflection = new ReflectionClass($system_cache);
		$ttl_property = $reflection->getProperty('ttl_settings');
		$ttl_property->setAccessible(true);
		$ttl_settings = $ttl_property->getValue($system_cache);
		$ttl_settings['default'] = 1; // 1 second TTL
		$ttl_property->setValue($system_cache, $ttl_settings);
		
		// Sleep for 2 seconds
		sleep(2);
		
		// Get should return null after expiration
		$expired_result = $system_cache->get('expiring_test', 'default');
		
		$results['data']['tests'][] = [
			'name' => 'Cache Expiration',
			'success' => ($expired_result === null),
			'message' => 'Expired cache entries should be removed automatically',
			'timing' => '2000 ms (sleep duration)'
		];
		
		// Test 5: Cache Invalidation
		$system_cache->set('test_invalidation', 'test_data', 'plugin_list');
		$system_cache->invalidate_plugin_cache();
		$invalidated_result = $system_cache->get('test_invalidation', 'plugin_list');
		
		$results['data']['tests'][] = [
			'name' => 'Cache Invalidation',
			'success' => ($invalidated_result === null),
			'message' => 'Cache entries should be invalidated by specific events',
			'timing' => 'N/A'
		];
		
		// Test 6: Preloading
		$start_time = microtime(true);
		$system_cache->preload_common_info();
		$end_time = microtime(true);
		
		// Check if PHP info was preloaded
		$php_info = $system_cache->get('php_info', 'php_info');
		
		$results['data']['tests'][] = [
			'name' => 'Cache Preloading',
			'success' => ($php_info !== null),
			'message' => 'Common system information should be preloaded',
			'timing' => number_format(($end_time - $start_time) * 1000, 2) . ' ms'
		];
		
		// Calculate success percentage
		$success_count = 0;
		foreach ($results['data']['tests'] as $test) {
			if ($test['success']) {
				$success_count++;
			}
		}
		
		$success_percentage = number_format(($success_count / count($results['data']['tests'])) * 100, 2);
		
		$results['success'] = ($success_percentage >= 80);
		$results['message'] = "System Information Cache Test: {$success_count} of " . count($results['data']['tests']) . " tests passed ({$success_percentage}%)";
		
		// Check for cached indicators in all responses for cache hit count
		$cache_hits = 0;
		$responses = [$second_result, $second_result];
		foreach ($responses as $response) {
			if (is_string($response) && strpos($response, '[CACHED]') !== false) {
				$cache_hits++;
			}
		}
		$results['data']['cache_hits'] = $cache_hits;
		
	} catch (Exception $e) {
		$results['success'] = false;
		$results['message'] = 'Error: ' . $e->getMessage();
	}
	
	return $results;
}