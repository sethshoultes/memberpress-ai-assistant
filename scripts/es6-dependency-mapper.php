<?php
/**
 * ES6 Module Dependency Mapper
 * 
 * This script maps actual import/export relationships in ES6 modules
 * to provide accurate dependency analysis for optimization decisions.
 * 
 * @package MemberpressAiAssistant
 * @version 1.0.0
 */

class ES6DependencyMapper {
    private $assets_dir;
    private $dependency_graph = [];
    private $entry_points = [];
    private $module_exports = [];
    private $module_imports = [];
    private $unused_modules = [];
    private $bundle_recommendations = [];

    public function __construct($assets_dir = null) {
        $this->assets_dir = $assets_dir ?: __DIR__ . '/../assets/js';
    }

    /**
     * Main execution method
     */
    public function mapDependencies($output_file = null) {
        $this->log("Starting ES6 dependency mapping...");
        
        $this->scanModules();
        $this->identifyEntryPoints();  
        $this->buildDependencyGraph();
        $this->findUnusedModules();
        $this->generateBundleRecommendations();
        
        $results = $this->generateReport();
        
        if ($output_file) {
            file_put_contents($output_file, json_encode($results, JSON_PRETTY_PRINT));
            $this->log("Results saved to: {$output_file}");
        }
        
        return $results;
    }

    /**
     * Scan all JavaScript modules for imports and exports
     */
    private function scanModules() {
        $this->log("Scanning JavaScript modules...");
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->assets_dir)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'js') {
                $this->analyzeModule($file->getPathname());
            }
        }
        
        $this->log("Found " . count($this->module_imports) . " modules with dependencies");
    }

    /**
     * Analyze a single module file
     */
    private function analyzeModule($file_path) {
        $content = file_get_contents($file_path);
        $relative_path = $this->getRelativePath($file_path);
        
        // Extract imports
        $imports = $this->extractImports($content, $file_path);
        if (!empty($imports)) {
            $this->module_imports[$relative_path] = $imports;
        }
        
        // Extract exports
        $exports = $this->extractExports($content);
        if (!empty($exports)) {
            $this->module_exports[$relative_path] = $exports;
        }
        
        // Check if module is ES6 module
        $is_es6_module = $this->isES6Module($content);
        if ($is_es6_module) {
            $this->log("ES6 Module: {$relative_path}");
        }
    }

    /**
     * Extract import statements from module content
     */
    private function extractImports($content, $file_path) {
        $imports = [];
        
        // Match various import patterns
        $import_patterns = [
            // import module from 'path'
            '/import\s+(\w+)\s+from\s+[\'"]([^\'"\s]+)[\'"]/',
            // import { named } from 'path'
            '/import\s+\{([^}]+)\}\s+from\s+[\'"]([^\'"\s]+)[\'"]/',
            // import * as name from 'path'
            '/import\s+\*\s+as\s+(\w+)\s+from\s+[\'"]([^\'"\s]+)[\'"]/',
            // import 'path' (side effects)
            '/import\s+[\'"]([^\'"\s]+)[\'"]/',
            // Dynamic imports
            '/import\s*\(\s*[\'"]([^\'"\s]+)[\'"]\s*\)/',
            // Require statements (CommonJS compatibility)
            '/require\s*\(\s*[\'"]([^\'"\s]+)[\'"]\s*\)/'
        ];

        foreach ($import_patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                $path_index = count($matches) > 2 ? 2 : 1;
                foreach ($matches[$path_index] as $i => $import_path) {
                    $resolved_path = $this->resolveImportPath($import_path, $file_path);
                    if ($resolved_path) {
                        $import_info = [
                            'path' => $resolved_path,
                            'original_path' => $import_path,
                            'type' => $this->getImportType($pattern)
                        ];
                        
                        // Add imported names if available
                        if (isset($matches[1][$i])) {
                            $import_info['imported'] = trim($matches[1][$i]);
                        }
                        
                        $imports[] = $import_info;
                    }
                }
            }
        }

        return $imports;
    }

    /**
     * Extract export statements from module content
     */
    private function extractExports($content) {
        $exports = [];
        
        $export_patterns = [
            // export default
            '/export\s+default\s+(\w+|class\s+\w+|function\s+\w+)/',
            // export { named }
            '/export\s+\{([^}]+)\}/',
            // export const/let/var
            '/export\s+(const|let|var)\s+(\w+)/',
            // export function
            '/export\s+function\s+(\w+)/',
            // export class
            '/export\s+class\s+(\w+)/'
        ];

        foreach ($export_patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                foreach ($matches[1] as $export_name) {
                    $exports[] = [
                        'name' => trim($export_name),
                        'type' => $this->getExportType($pattern)
                    ];
                }
            }
        }

        return $exports;
    }

    /**
     * Resolve import path to actual file path
     */
    private function resolveImportPath($import_path, $current_file) {
        // Handle relative imports
        if (str_starts_with($import_path, './') || str_starts_with($import_path, '../')) {
            $current_dir = dirname($current_file);
            $resolved = realpath($current_dir . '/' . $import_path);
            
            // Try with .js extension if file doesn't exist
            if (!$resolved && !str_ends_with($import_path, '.js')) {
                $resolved = realpath($current_dir . '/' . $import_path . '.js');
            }
            
            return $resolved ? $this->getRelativePath($resolved) : null;
        }
        
        // Handle absolute imports (relative to assets/js)
        if (str_starts_with($import_path, '/')) {
            $full_path = $this->assets_dir . $import_path;
            return file_exists($full_path) ? $this->getRelativePath($full_path) : null;
        }
        
        // Handle bare imports (node modules, etc.)
        return null; // We don't track external dependencies
    }

    /**
     * Get relative path from assets/js directory
     */
    private function getRelativePath($full_path) {
        $assets_dir = realpath($this->assets_dir);
        $full_path = realpath($full_path);
        
        if (str_starts_with($full_path, $assets_dir)) {
            return 'js/' . substr($full_path, strlen($assets_dir) + 1);
        }
        
        return $full_path;
    }

    /**
     * Check if file contains ES6 module syntax
     */
    private function isES6Module($content) {
        return preg_match('/\b(import|export)\b/', $content);
    }

    /**
     * Identify entry points (modules that are WordPress registered)
     */
    private function identifyEntryPoints() {
        $this->log("Identifying entry points...");
        
        // Load WordPress registration patterns
        $registration_file = __DIR__ . '/asset-registration.json';
        if (file_exists($registration_file)) {
            $registration_data = json_decode(file_get_contents($registration_file), true);
            
            foreach ($registration_data['asset_handles'] as $handle => $info) {
                if (isset($info['src']) && str_contains($info['src'], '.js')) {
                    $path = $this->extractPathFromSrc($info['src']);
                    if ($path) {
                        $this->entry_points[] = $path;
                    }
                }
            }
        }
        
        // Also check chat.js as it's the main ES6 entry point
        if (file_exists($this->assets_dir . '/chat.js')) {
            $this->entry_points[] = 'js/chat.js';
        }
        
        $this->log("Found " . count($this->entry_points) . " entry points");
    }

    /**
     * Build complete dependency graph
     */
    private function buildDependencyGraph() {
        $this->log("Building dependency graph...");
        
        // Start from entry points and traverse dependencies
        foreach ($this->entry_points as $entry_point) {
            $this->traverseDependencies($entry_point, []);
        }
        
        $this->log("Dependency graph built with " . count($this->dependency_graph) . " nodes");
    }

    /**
     * Recursively traverse dependencies
     */
    private function traverseDependencies($module_path, $visited = []) {
        if (in_array($module_path, $visited)) {
            // Circular dependency detected
            return;
        }

        $visited[] = $module_path;
        $dependencies = [];

        if (isset($this->module_imports[$module_path])) {
            foreach ($this->module_imports[$module_path] as $import) {
                $dep_path = $import['path'];
                if ($dep_path && !in_array($dep_path, $dependencies)) {
                    $dependencies[] = $dep_path;
                    $this->traverseDependencies($dep_path, $visited);
                }
            }
        }

        $this->dependency_graph[$module_path] = $dependencies;
    }

    /**
     * Find modules that are not referenced by any entry point
     */
    private function findUnusedModules() {
        $this->log("Finding unused modules...");
        
        $all_modules = array_keys($this->module_imports + $this->module_exports);
        $used_modules = $this->getAllReferencedModules();
        
        $this->unused_modules = array_diff($all_modules, $used_modules);
        
        $this->log("Found " . count($this->unused_modules) . " potentially unused modules");
    }

    /**
     * Get all modules referenced in dependency graph
     */
    private function getAllReferencedModules() {
        $referenced = [];
        
        foreach ($this->dependency_graph as $module => $dependencies) {
            $referenced[] = $module;
            $referenced = array_merge($referenced, $dependencies);
        }
        
        return array_unique($referenced);
    }

    /**
     * Generate bundle recommendations based on dependency patterns
     */
    private function generateBundleRecommendations() {
        $this->log("Generating bundle recommendations...");
        
        // Analyze import patterns to suggest logical bundles
        $bundles = [
            'core' => [],
            'ui' => [],
            'messaging' => [],
            'utils' => []
        ];

        foreach ($this->dependency_graph as $module => $dependencies) {
            if (str_contains($module, '/core/')) {
                $bundles['core'][] = $module;
            } elseif (str_contains($module, '/ui/')) {
                $bundles['ui'][] = $module;
            } elseif (str_contains($module, '/messages/')) {
                $bundles['messaging'][] = $module;
            } elseif (str_contains($module, '/utils/')) {
                $bundles['utils'][] = $module;
            }
        }

        $this->bundle_recommendations = array_filter($bundles);
    }

    /**
     * Generate comprehensive report
     */
    private function generateReport() {
        $total_modules = count($this->module_imports + $this->module_exports);
        $used_modules = count($this->getAllReferencedModules());
        $unused_count = count($this->unused_modules);

        return [
            'summary' => [
                'total_modules' => $total_modules,
                'used_modules' => $used_modules,
                'unused_modules' => $unused_count,
                'usage_percentage' => $total_modules > 0 ? round(($used_modules / $total_modules) * 100, 2) : 0,
                'entry_points' => count($this->entry_points)
            ],
            'entry_points' => $this->entry_points,
            'dependency_graph' => $this->dependency_graph,
            'module_imports' => $this->module_imports,
            'module_exports' => $this->module_exports,
            'unused_modules' => $this->unused_modules,
            'bundle_recommendations' => $this->bundle_recommendations,
            'analysis_metadata' => [
                'timestamp' => date('Y-m-d H:i:s'),
                'assets_directory' => $this->assets_dir,
                'total_files_scanned' => $this->getTotalFilesScanned()
            ]
        ];
    }

    /**
     * Helper methods
     */
    private function getImportType($pattern) {
        if (str_contains($pattern, 'import\\s*\\(')) return 'dynamic';
        if (str_contains($pattern, '\\*\\s+as')) return 'namespace';
        if (str_contains($pattern, '\\{')) return 'named';
        if (str_contains($pattern, 'require')) return 'commonjs';
        return 'default';
    }

    private function getExportType($pattern) {
        if (str_contains($pattern, 'export\\s+default')) return 'default';
        if (str_contains($pattern, 'export\\s+\\{')) return 'named';
        if (str_contains($pattern, 'export\\s+(const|let|var)')) return 'variable';
        if (str_contains($pattern, 'export\\s+function')) return 'function';
        if (str_contains($pattern, 'export\\s+class')) return 'class';
        return 'unknown';
    }

    private function extractPathFromSrc($src) {
        // Extract relative path from WordPress asset URL
        if (preg_match('/assets\/js\/(.+)$/', $src, $matches)) {
            return 'js/' . $matches[1];
        }
        return null;
    }

    private function getTotalFilesScanned() {
        $count = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->assets_dir)
        );
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'js') {
                $count++;
            }
        }
        return $count;
    }

    private function log($message) {
        echo "[ES6 Dependency Mapper] {$message}\n";
    }
}

// CLI execution
if (isset($argv[0]) && basename($argv[0]) === basename(__FILE__)) {
    $options = getopt('o:v', ['output:', 'verbose']);
    $output_file = $options['o'] ?? $options['output'] ?? __DIR__ . '/es6-dependency-analysis.json';
    $verbose = isset($options['v']) || isset($options['verbose']);

    $mapper = new ES6DependencyMapper();
    
    if ($verbose) {
        echo "ES6 Dependency Mapper v1.0.0\n";
        echo "========================================\n";
    }
    
    $results = $mapper->mapDependencies($output_file);
    
    if ($verbose) {
        echo "\nAnalysis Complete!\n";
        echo "==================\n";
        echo "Total modules: {$results['summary']['total_modules']}\n";
        echo "Used modules: {$results['summary']['used_modules']}\n";
        echo "Unused modules: {$results['summary']['unused_modules']}\n";
        echo "Usage percentage: {$results['summary']['usage_percentage']}%\n";
        echo "\nResults saved to: {$output_file}\n";
    }
}