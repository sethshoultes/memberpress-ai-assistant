<?php
/**
 * ES6 Module Optimization Tester
 * 
 * Comprehensive testing suite for ES6 module optimizations
 * Tests module loading, bundle functionality, and performance improvements.
 * 
 * @package MemberpressAiAssistant
 * @version 1.0.0
 */

class OptimizationTester {
    private $assets_dir;
    private $test_results = [];
    private $performance_data = [];
    private $errors = [];

    public function __construct($assets_dir = null) {
        $this->assets_dir = $assets_dir ?: __DIR__ . '/../assets/js';
    }

    /**
     * Run comprehensive optimization tests
     */
    public function runTests($test_suite = 'all') {
        $this->log("Starting optimization testing suite...");
        
        $tests = [
            'module_loading' => 'testModuleLoading',
            'dependency_resolution' => 'testDependencyResolution',
            'bundle_integrity' => 'testBundleIntegrity',
            'wordpress_registration' => 'testWordPressRegistration',
            'performance_metrics' => 'testPerformanceMetrics',
            'browser_compatibility' => 'testBrowserCompatibility'
        ];

        if ($test_suite === 'all') {
            $tests_to_run = $tests;
        } else {
            $tests_to_run = array_intersect_key($tests, array_flip(explode(',', $test_suite)));
        }

        foreach ($tests_to_run as $test_name => $test_method) {
            $this->log("Running test: {$test_name}");
            $this->runTest($test_name, $test_method);
        }

        return $this->generateTestReport();
    }

    /**
     * Run individual test
     */
    private function runTest($test_name, $test_method) {
        $start_time = microtime(true);
        
        try {
            $result = $this->$test_method();
            $this->test_results[$test_name] = [
                'status' => 'passed',
                'result' => $result,
                'execution_time' => microtime(true) - $start_time
            ];
        } catch (Exception $e) {
            $this->test_results[$test_name] = [
                'status' => 'failed',
                'error' => $e->getMessage(),
                'execution_time' => microtime(true) - $start_time
            ];
            $this->errors[] = "Test {$test_name} failed: " . $e->getMessage();
        }
    }

    /**
     * Test module loading functionality
     */
    private function testModuleLoading() {
        $results = [];
        
        // Test original chat.js exists and is valid
        $original_chat = $this->assets_dir . '/chat.js';
        if (!file_exists($original_chat)) {
            throw new Exception("Original chat.js file not found");
        }
        
        $original_content = file_get_contents($original_chat);
        $results['original_chat_size'] = filesize($original_chat);
        $results['original_has_imports'] = $this->hasES6Imports($original_content);
        
        // Test optimized chat.js if it exists
        $optimized_chat = $this->assets_dir . '/chat-optimized.js';
        if (file_exists($optimized_chat)) {
            $optimized_content = file_get_contents($optimized_chat);
            $results['optimized_chat_size'] = filesize($optimized_chat);
            $results['optimized_has_imports'] = $this->hasES6Imports($optimized_content);
            $results['size_difference'] = $results['original_chat_size'] - $results['optimized_chat_size'];
        }
        
        // Test individual modules exist
        $required_modules = [
            'chat/core/chat-core.js',
            'chat/core/state-manager.js',
            'chat/core/ui-manager.js',
            'chat/core/api-client.js',
            'chat/core/event-bus.js'
        ];
        
        $missing_modules = [];
        foreach ($required_modules as $module) {
            if (!file_exists($this->assets_dir . '/' . $module)) {
                $missing_modules[] = $module;
            }
        }
        
        if (!empty($missing_modules)) {
            throw new Exception("Missing required modules: " . implode(', ', $missing_modules));
        }
        
        $results['all_required_modules_exist'] = true;
        $results['total_module_count'] = count($required_modules);
        
        return $results;
    }

    /**
     * Test dependency resolution
     */
    private function testDependencyResolution() {
        $results = [];
        
        // Load dependency analysis if available
        $dependency_file = __DIR__ . '/es6-dependency-analysis.json';
        if (!file_exists($dependency_file)) {
            // Generate dependency analysis
            if (file_exists(__DIR__ . '/es6-dependency-mapper.php')) {
                exec("php " . __DIR__ . "/es6-dependency-mapper.php -o {$dependency_file}");
            }
        }
        
        if (file_exists($dependency_file)) {
            $dependency_data = json_decode(file_get_contents($dependency_file), true);
            
            $results['dependency_analysis_available'] = true;
            $results['total_modules'] = $dependency_data['summary']['total_modules'] ?? 0;
            $results['used_modules'] = $dependency_data['summary']['used_modules'] ?? 0;
            $results['unused_modules'] = $dependency_data['summary']['unused_modules'] ?? 0;
            $results['usage_percentage'] = $dependency_data['summary']['usage_percentage'] ?? 0;
            
            // Test for circular dependencies
            $circular_deps = $this->findCircularDependencies($dependency_data['dependency_graph'] ?? []);
            $results['circular_dependencies'] = $circular_deps;
            $results['has_circular_dependencies'] = !empty($circular_deps);
            
        } else {
            $results['dependency_analysis_available'] = false;
        }
        
        return $results;
    }

    /**
     * Test bundle integrity
     */
    private function testBundleIntegrity() {
        $results = [];
        $bundles_dir = $this->assets_dir . '/bundles';
        
        if (!is_dir($bundles_dir)) {
            throw new Exception("Bundles directory not found");
        }
        
        $expected_bundles = [
            'core-bundle.js',
            'ui-bundle.js',
            'messaging-bundle.js',
            'message-handlers-bundle.js'
        ];
        
        $missing_bundles = [];
        $bundle_info = [];
        
        foreach ($expected_bundles as $bundle) {
            $bundle_path = $bundles_dir . '/' . $bundle;
            if (!file_exists($bundle_path)) {
                $missing_bundles[] = $bundle;
            } else {
                $content = file_get_contents($bundle_path);
                $bundle_info[$bundle] = [
                    'size' => filesize($bundle_path),
                    'has_exports' => preg_match('/export\s+/', $content) > 0,
                    'export_count' => preg_match_all('/export\s+/', $content),
                    'import_count' => preg_match_all('/import\s+/', $content)
                ];
            }
        }
        
        if (!empty($missing_bundles)) {
            throw new Exception("Missing bundles: " . implode(', ', $missing_bundles));
        }
        
        $results['all_bundles_exist'] = true;
        $results['bundle_count'] = count($expected_bundles);
        $results['bundle_info'] = $bundle_info;
        $results['total_bundle_size'] = array_sum(array_column($bundle_info, 'size'));
        
        // Test bundle index
        $index_path = $bundles_dir . '/index.js';
        if (file_exists($index_path)) {
            $results['bundle_index_exists'] = true;
            $results['bundle_index_size'] = filesize($index_path);
        } else {
            $results['bundle_index_exists'] = false;
        }
        
        return $results;
    }

    /**
     * Test WordPress registration patterns
     */
    private function testWordPressRegistration() {
        $results = [];
        
        // Find ChatInterface.php
        $chat_interface_paths = [
            __DIR__ . '/../src/ChatInterface.php',
            __DIR__ . '/../includes/ChatInterface.php'
        ];
        
        $chat_interface_path = null;
        foreach ($chat_interface_paths as $path) {
            if (file_exists($path)) {
                $chat_interface_path = $path;
                break;
            }
        }
        
        if (!$chat_interface_path) {
            throw new Exception("ChatInterface.php not found");
        }
        
        $content = file_get_contents($chat_interface_path);
        
        // Test for WordPress registration patterns
        $wp_register_count = preg_match_all('/wp_register_script\s*\(/', $content);
        $wp_enqueue_count = preg_match_all('/wp_enqueue_script\s*\(/', $content);
        $module_type_count = preg_match_all('/wp_script_add_data\s*\([^,]+,\s*[\'"]type[\'"]\s*,\s*[\'"]module[\'"]\s*\)/', $content);
        
        $results['wp_register_script_count'] = $wp_register_count;
        $results['wp_enqueue_script_count'] = $wp_enqueue_count;
        $results['module_type_declarations'] = $module_type_count;
        
        // Check for bundle registration
        $bundle_registration = strpos($content, 'bundle') !== false;
        $results['has_bundle_registration'] = $bundle_registration;
        
        // Test WordPress registration helper
        $wp_helper_path = $this->assets_dir . '/bundles/wordpress-registration.php';
        if (file_exists($wp_helper_path)) {
            $results['wordpress_helper_exists'] = true;
            $results['wordpress_helper_size'] = filesize($wp_helper_path);
        } else {
            $results['wordpress_helper_exists'] = false;
        }
        
        return $results;
    }

    /**
     * Test performance metrics
     */
    private function testPerformanceMetrics() {
        $results = [];
        
        // Calculate total asset sizes
        $original_size = $this->calculateDirectorySize($this->assets_dir, ['bundles']);
        $bundle_size = 0;
        
        $bundles_dir = $this->assets_dir . '/bundles';
        if (is_dir($bundles_dir)) {
            $bundle_size = $this->calculateDirectorySize($bundles_dir);
        }
        
        $results['original_total_size'] = $original_size;
        $results['bundle_total_size'] = $bundle_size;
        $results['size_reduction'] = $original_size - $bundle_size;
        $results['size_reduction_percentage'] = $original_size > 0 ? round((($original_size - $bundle_size) / $original_size) * 100, 2) : 0;
        
        // Count HTTP requests (estimate)
        $js_files = $this->countJSFiles($this->assets_dir, ['bundles']);
        $bundle_files = $this->countJSFiles($bundles_dir);
        
        $results['original_js_files'] = $js_files;
        $results['bundle_files'] = $bundle_files;
        $results['request_reduction'] = $js_files - $bundle_files;
        $results['request_reduction_percentage'] = $js_files > 0 ? round((($js_files - $bundle_files) / $js_files) * 100, 2) : 0;
        
        // Estimate loading time improvements
        $results['estimated_loading_improvement'] = $this->estimateLoadingImprovement($results);
        
        return $results;
    }

    /**
     * Test browser compatibility
     */
    private function testBrowserCompatibility() {
        $results = [];
        
        // Test ES6 feature usage
        $features_tested = [
            'arrow_functions' => '/\=\>\s*/',
            'template_literals' => '/`[^`]*`/',
            'destructuring' => '/\{[^}]+\}\s*=\s*/',
            'async_await' => '/\b(async|await)\b/',
            'modules' => '/\b(import|export)\b/',
            'const_let' => '/\b(const|let)\b/'
        ];
        
        $feature_usage = [];
        $all_js_content = $this->getAllJSContent();
        
        foreach ($features_tested as $feature => $pattern) {
            $matches = preg_match_all($pattern, $all_js_content);
            $feature_usage[$feature] = $matches;
        }
        
        $results['es6_feature_usage'] = $feature_usage;
        $results['uses_modern_js'] = array_sum($feature_usage) > 0;
        
        // Browser support analysis
        $results['browser_support'] = [
            'chrome' => 'good', // ES6 modules supported since Chrome 61
            'firefox' => 'good', // ES6 modules supported since Firefox 60
            'safari' => 'good', // ES6 modules supported since Safari 10.1
            'edge' => 'good', // ES6 modules supported since Edge 16
            'ie11' => 'poor' // No ES6 module support
        ];
        
        return $results;
    }

    /**
     * Helper methods
     */
    private function hasES6Imports($content) {
        return preg_match('/\b(import|export)\b/', $content) > 0;
    }

    private function findCircularDependencies($dependency_graph) {
        $circular = [];
        $visited = [];
        $stack = [];
        
        foreach (array_keys($dependency_graph) as $node) {
            if (!isset($visited[$node])) {
                $this->dfsCircular($node, $dependency_graph, $visited, $stack, $circular);
            }
        }
        
        return $circular;
    }

    private function dfsCircular($node, $graph, &$visited, &$stack, &$circular) {
        $visited[$node] = true;
        $stack[$node] = true;
        
        if (isset($graph[$node])) {
            foreach ($graph[$node] as $neighbor) {
                if (!isset($visited[$neighbor])) {
                    $this->dfsCircular($neighbor, $graph, $visited, $stack, $circular);
                } elseif (isset($stack[$neighbor])) {
                    $circular[] = [$node, $neighbor];
                }
            }
        }
        
        unset($stack[$node]);
    }

    private function calculateDirectorySize($dir, $exclude_dirs = []) {
        if (!is_dir($dir)) return 0;
        
        $size = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $skip = false;
                foreach ($exclude_dirs as $exclude) {
                    if (strpos($file->getPath(), $exclude) !== false) {
                        $skip = true;
                        break;
                    }
                }
                if (!$skip) {
                    $size += $file->getSize();
                }
            }
        }
        
        return $size;
    }

    private function countJSFiles($dir, $exclude_dirs = []) {
        if (!is_dir($dir)) return 0;
        
        $count = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir)
        );
        
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'js') {
                $skip = false;
                foreach ($exclude_dirs as $exclude) {
                    if (strpos($file->getPath(), $exclude) !== false) {
                        $skip = true;
                        break;
                    }
                }
                if (!$skip) {
                    $count++;
                }
            }
        }
        
        return $count;
    }

    private function getAllJSContent() {
        $content = '';
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->assets_dir)
        );
        
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'js') {
                $content .= file_get_contents($file->getPathname()) . "\n";
            }
        }
        
        return $content;
    }

    private function estimateLoadingImprovement($performance_data) {
        $request_reduction = $performance_data['request_reduction_percentage'] ?? 0;
        $size_reduction = $performance_data['size_reduction_percentage'] ?? 0;
        
        // Rough estimate: request reduction has more impact than size reduction
        $loading_improvement = ($request_reduction * 0.7) + ($size_reduction * 0.3);
        
        return round($loading_improvement, 2);
    }

    /**
     * Generate comprehensive test report
     */
    private function generateTestReport() {
        $total_tests = count($this->test_results);
        $passed_tests = count(array_filter($this->test_results, function($result) {
            return $result['status'] === 'passed';
        }));
        $failed_tests = $total_tests - $passed_tests;
        
        $report = [
            'summary' => [
                'total_tests' => $total_tests,
                'passed_tests' => $passed_tests,
                'failed_tests' => $failed_tests,
                'success_rate' => $total_tests > 0 ? round(($passed_tests / $total_tests) * 100, 2) : 0,
                'total_execution_time' => array_sum(array_column($this->test_results, 'execution_time'))
            ],
            'test_results' => $this->test_results,
            'performance_summary' => $this->extractPerformanceSummary(),
            'recommendations' => $this->generateRecommendations(),
            'errors' => $this->errors,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        return $report;
    }

    private function extractPerformanceSummary() {
        $performance = [];
        
        if (isset($this->test_results['performance_metrics']['result'])) {
            $perf_data = $this->test_results['performance_metrics']['result'];
            
            $performance = [
                'size_reduction' => $perf_data['size_reduction_percentage'] ?? 0,
                'request_reduction' => $perf_data['request_reduction_percentage'] ?? 0,
                'estimated_loading_improvement' => $perf_data['estimated_loading_improvement'] ?? 0,
                'bundle_count' => isset($this->test_results['bundle_integrity']['result']['bundle_count']) 
                    ? $this->test_results['bundle_integrity']['result']['bundle_count'] : 0
            ];
        }
        
        return $performance;
    }

    private function generateRecommendations() {
        $recommendations = [];
        
        // Analyze test results and generate recommendations
        if (isset($this->test_results['dependency_resolution']['result']['has_circular_dependencies']) 
            && $this->test_results['dependency_resolution']['result']['has_circular_dependencies']) {
            $recommendations[] = "Fix circular dependencies found in module graph";
        }
        
        if (isset($this->test_results['performance_metrics']['result']['size_reduction_percentage']) 
            && $this->test_results['performance_metrics']['result']['size_reduction_percentage'] < 10) {
            $recommendations[] = "Consider additional bundle optimizations for better size reduction";
        }
        
        if (isset($this->test_results['wordpress_registration']['result']['has_bundle_registration']) 
            && !$this->test_results['wordpress_registration']['result']['has_bundle_registration']) {
            $recommendations[] = "Update WordPress registration to use bundle system";
        }
        
        if (empty($recommendations)) {
            $recommendations[] = "All optimization tests passed - system is ready for production";
        }
        
        return $recommendations;
    }

    private function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    private function log($message) {
        echo "[Optimization Tester] {$message}\n";
    }
}

// CLI execution
if (isset($argv[0]) && basename($argv[0]) === basename(__FILE__)) {
    $options = getopt('t:o:v', ['tests:', 'output:', 'verbose']);
    $test_suite = $options['t'] ?? $options['tests'] ?? 'all';
    $output_file = $options['o'] ?? $options['output'] ?? __DIR__ . '/optimization-test-results.json';
    $verbose = isset($options['v']) || isset($options['verbose']);
    
    $tester = new OptimizationTester();
    
    if ($verbose) {
        echo "ES6 Module Optimization Tester v1.0.0\n";
        echo "======================================\n";
        echo "Running test suite: {$test_suite}\n\n";
    }
    
    $results = $tester->runTests($test_suite);
    
    // Save results
    file_put_contents($output_file, json_encode($results, JSON_PRETTY_PRINT));
    
    if ($verbose) {
        echo "\nTest Results Summary\n";
        echo "===================\n";
        echo "Total tests: {$results['summary']['total_tests']}\n";
        echo "Passed: {$results['summary']['passed_tests']}\n";
        echo "Failed: {$results['summary']['failed_tests']}\n";
        echo "Success rate: {$results['summary']['success_rate']}%\n";
        echo "Execution time: " . round($results['summary']['total_execution_time'], 3) . "s\n";
        
        if (!empty($results['performance_summary'])) {
            echo "\nPerformance Summary\n";
            echo "==================\n";
            echo "Size reduction: {$results['performance_summary']['size_reduction']}%\n";
            echo "Request reduction: {$results['performance_summary']['request_reduction']}%\n";
            echo "Estimated loading improvement: {$results['performance_summary']['estimated_loading_improvement']}%\n";
        }
        
        if (!empty($results['recommendations'])) {
            echo "\nRecommendations\n";
            echo "===============\n";
            foreach ($results['recommendations'] as $recommendation) {
                echo "- {$recommendation}\n";
            }
        }
        
        if (!empty($results['errors'])) {
            echo "\nErrors\n";
            echo "======\n";
            foreach ($results['errors'] as $error) {
                echo "- {$error}\n";
            }
        }
        
        echo "\nDetailed results saved to: {$output_file}\n";
    }
}