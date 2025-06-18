#!/usr/bin/env php
<?php
/**
 * MemberPress AI Assistant - Asset Registration Analyzer Script
 * 
 * This script analyzes how assets are registered and enqueued throughout the codebase,
 * building on the asset inventory created in the previous step. It identifies WordPress
 * registration patterns, direct file inclusions, ES6 module loading, and conditional
 * loading patterns.
 * 
 * Part of Phase 1 asset analysis system for identifying unused CSS and JavaScript files.
 * 
 * @package MemberpressAiAssistant
 * @version 1.0.0
 * @author MemberPress
 */

// Exit if accessed directly from web
if (isset($_SERVER['HTTP_HOST'])) {
    exit('This script can only be run from the command line.');
}

// Define script constants
define('MPAI_SCRIPT_VERSION', '1.0.0');
define('MPAI_SCRIPT_NAME', 'Asset Registration Analyzer');

// Get the plugin directory (script is in scripts/ subdirectory)
$plugin_dir = dirname(__DIR__);

/**
 * Display help information
 */
function display_help() {
    echo "\n" . MPAI_SCRIPT_NAME . " v" . MPAI_SCRIPT_VERSION . "\n";
    echo str_repeat("=", 60) . "\n";
    echo "Analyzes asset registration patterns in the MemberPress AI Assistant plugin.\n\n";
    echo "USAGE:\n";
    echo "  php scripts/asset-registration-analyzer.php [OPTIONS]\n\n";
    echo "OPTIONS:\n";
    echo "  --help, -h        Show this help message\n";
    echo "  --json            Output results in JSON format\n";
    echo "  --verbose, -v     Enable verbose output with detailed information\n";
    echo "  --quiet, -q       Minimal output (only summary statistics)\n";
    echo "  --patterns-only   Show only registration patterns found\n";
    echo "  --files-only      Show only file mappings\n\n";
    echo "EXAMPLES:\n";
    echo "  php scripts/asset-registration-analyzer.php\n";
    echo "  php scripts/asset-registration-analyzer.php --json\n";
    echo "  php scripts/asset-registration-analyzer.php --verbose\n";
    echo "  php scripts/asset-registration-analyzer.php --patterns-only\n\n";
    echo "OUTPUT:\n";
    echo "  - WordPress registration patterns (wp_enqueue_*, wp_register_*)\n";
    echo "  - Direct file inclusions in templates\n";
    echo "  - ES6 module loading patterns and dependencies\n";
    echo "  - Conditional loading analysis\n";
    echo "  - Asset handle to file path mappings\n";
    echo "  - Summary statistics of registration patterns\n\n";
}

/**
 * Parse command line arguments
 */
function parse_arguments($argv) {
    $options = [
        'help' => false,
        'json' => false,
        'verbose' => false,
        'quiet' => false,
        'patterns_only' => false,
        'files_only' => false
    ];
    
    for ($i = 1; $i < count($argv); $i++) {
        switch ($argv[$i]) {
            case '--help':
            case '-h':
                $options['help'] = true;
                break;
            case '--json':
                $options['json'] = true;
                break;
            case '--verbose':
            case '-v':
                $options['verbose'] = true;
                break;
            case '--quiet':
            case '-q':
                $options['quiet'] = true;
                break;
            case '--patterns-only':
                $options['patterns_only'] = true;
                break;
            case '--files-only':
                $options['files_only'] = true;
                break;
            default:
                echo "Unknown option: {$argv[$i]}\n";
                echo "Use --help for usage information.\n";
                exit(1);
        }
    }
    
    return $options;
}

/**
 * Scan PHP files for WordPress asset registration patterns
 */
function scan_wordpress_registration_patterns($directory) {
    $patterns = [];
    $registration_functions = [
        'wp_enqueue_style',
        'wp_enqueue_script', 
        'wp_register_style',
        'wp_register_script',
        'wp_script_add_data'
    ];
    
    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $content = file_get_contents($file->getPathname());
                $relative_path = str_replace($directory . '/', '', $file->getPathname());
                
                // Search for each registration function
                foreach ($registration_functions as $function) {
                    $pattern = '/(' . preg_quote($function) . '\s*\([^;]+;)/';
                    if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                        foreach ($matches[1] as $match) {
                            $line_number = substr_count(substr($content, 0, $match[1]), "\n") + 1;
                            
                            $patterns[] = [
                                'type' => 'wordpress_registration',
                                'function' => $function,
                                'file' => $relative_path,
                                'line' => $line_number,
                                'code' => trim($match[0]),
                                'context' => extract_context($content, $match[1], 2)
                            ];
                        }
                    }
                }
            }
        }
    } catch (Exception $e) {
        echo "Error scanning WordPress registration patterns: " . $e->getMessage() . "\n";
    }
    
    return $patterns;
}

/**
 * Scan template files for direct asset inclusions
 */
function scan_direct_inclusions($directory) {
    $inclusions = [];
    $inclusion_patterns = [
        'script_src' => '/<script[^>]+src=["\']([^"\']+)["\'][^>]*>/i',
        'link_href' => '/<link[^>]+href=["\']([^"\']+)["\'][^>]*>/i',
        'style_import' => '/@import\s+["\']([^"\']+)["\'];?/i'
    ];
    
    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && in_array($file->getExtension(), ['php', 'html', 'htm'])) {
                $content = file_get_contents($file->getPathname());
                $relative_path = str_replace($directory . '/', '', $file->getPathname());
                
                foreach ($inclusion_patterns as $type => $pattern) {
                    if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                        foreach ($matches[0] as $index => $match) {
                            $line_number = substr_count(substr($content, 0, $match[1]), "\n") + 1;
                            $asset_path = $matches[1][$index][0];
                            
                            $inclusions[] = [
                                'type' => 'direct_inclusion',
                                'inclusion_type' => $type,
                                'file' => $relative_path,
                                'line' => $line_number,
                                'asset_path' => $asset_path,
                                'code' => trim($match[0]),
                                'context' => extract_context($content, $match[1], 1)
                            ];
                        }
                    }
                }
            }
        }
    } catch (Exception $e) {
        echo "Error scanning direct inclusions: " . $e->getMessage() . "\n";
    }
    
    return $inclusions;
}

/**
 * Analyze ES6 module patterns and dependencies
 */
function analyze_es6_modules($directory) {
    $modules = [];
    
    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'js') {
                $content = file_get_contents($file->getPathname());
                $relative_path = str_replace($directory . '/', '', $file->getPathname());
                
                // Check for ES6 module syntax
                $import_pattern = '/import\s+(?:{[^}]+}|\*\s+as\s+\w+|\w+)\s+from\s+["\']([^"\']+)["\'];?/';
                $export_pattern = '/export\s+(?:default\s+)?(?:class|function|const|let|var)\s+(\w+)/';
                
                $imports = [];
                $exports = [];
                
                // Find imports
                if (preg_match_all($import_pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                    foreach ($matches[0] as $index => $match) {
                        $line_number = substr_count(substr($content, 0, $match[1]), "\n") + 1;
                        $import_path = $matches[1][$index][0];
                        
                        $imports[] = [
                            'line' => $line_number,
                            'path' => $import_path,
                            'code' => trim($match[0])
                        ];
                    }
                }
                
                // Find exports
                if (preg_match_all($export_pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                    foreach ($matches[0] as $index => $match) {
                        $line_number = substr_count(substr($content, 0, $match[1]), "\n") + 1;
                        $export_name = $matches[1][$index][0];
                        
                        $exports[] = [
                            'line' => $line_number,
                            'name' => $export_name,
                            'code' => trim($match[0])
                        ];
                    }
                }
                
                if (!empty($imports) || !empty($exports)) {
                    $modules[] = [
                        'type' => 'es6_module',
                        'file' => $relative_path,
                        'imports' => $imports,
                        'exports' => $exports,
                        'is_module' => !empty($imports) || !empty($exports)
                    ];
                }
            }
        }
    } catch (Exception $e) {
        echo "Error analyzing ES6 modules: " . $e->getMessage() . "\n";
    }
    
    return $modules;
}

/**
 * Extract asset handles and file paths from registration patterns
 */
function extract_asset_mappings($patterns) {
    $mappings = [];
    
    foreach ($patterns as $pattern) {
        if ($pattern['type'] === 'wordpress_registration') {
            $code = $pattern['code'];
            $handle = '';
            $path = '';
            
            // Try multiple extraction patterns for different registration styles
            
            // Pattern 1: Extract handle from any wp function call
            if (preg_match('/wp_(?:enqueue|register)_(?:style|script)\s*\(\s*["\']([^"\']+)["\']/', $code, $matches)) {
                $handle = $matches[1];
                
                // Pattern 2: Look for path in multiline calls with MPAI_PLUGIN_URL concatenation
                if (preg_match('/MPAI_PLUGIN_URL\s*\.\s*["\']([^"\']+)["\']/', $code, $path_matches)) {
                    $path = $path_matches[1];
                }
                // Pattern 3: Look for simple string path (single line calls)
                elseif (preg_match('/wp_(?:enqueue|register)_(?:style|script)\s*\(\s*["\'][^"\']+["\']\s*,\s*["\']([^"\']+)["\']/', $code, $path_matches)) {
                    $path = $path_matches[1];
                }
                // Pattern 4: For enqueue calls, path will be empty (filled by cross-referencing)
                else {
                    $path = '';
                }
            }
            
            // Clean up path - remove any remaining constants and normalize
            if (!empty($path)) {
                $path = str_replace(['MPAI_PLUGIN_URL . \'', 'MPAI_PLUGIN_URL . "', 'MPAI_PLUGIN_URL.\'', 'MPAI_PLUGIN_URL."'], '', $path);
                $path = str_replace(['\' . MPAI_VERSION', '" . MPAI_VERSION', '\'.MPAI_VERSION', '".MPAI_VERSION'], '', $path);
                $path = trim($path, '\'" ');
                
                // Ensure path starts with assets/ for consistency
                if (!empty($path) && strpos($path, 'assets/') !== 0) {
                    $path = 'assets/' . ltrim($path, '/');
                }
            }
            
            if (!empty($handle)) {
                $mappings[] = [
                    'handle' => $handle,
                    'path' => $path,
                    'function' => $pattern['function'],
                    'file' => $pattern['file'],
                    'line' => $pattern['line'],
                    'conditional' => detect_conditional_loading($pattern['context'])
                ];
            }
        }
    }
    
    // Post-process to match enqueue calls with their registration paths
    $mappings = cross_reference_enqueue_calls($mappings);
    
    return $mappings;
}

/**
 * Cross-reference enqueue calls with their registration paths
 */
function cross_reference_enqueue_calls($mappings) {
    $handle_paths = [];
    $updated_mappings = [];
    
    // First pass: collect all paths from registration calls
    foreach ($mappings as $mapping) {
        if (in_array($mapping['function'], ['wp_register_style', 'wp_register_script']) && !empty($mapping['path'])) {
            $handle_paths[$mapping['handle']] = $mapping['path'];
        }
    }
    
    // Second pass: update enqueue calls with paths from registrations
    foreach ($mappings as $mapping) {
        if (in_array($mapping['function'], ['wp_enqueue_style', 'wp_enqueue_script']) && empty($mapping['path'])) {
            if (isset($handle_paths[$mapping['handle']])) {
                $mapping['path'] = $handle_paths[$mapping['handle']];
            }
        }
        $updated_mappings[] = $mapping;
    }
    
    return $updated_mappings;
}

/**
 * Detect conditional loading patterns in context
 */
function detect_conditional_loading($context) {
    $conditions = [];
    
    // Check for common conditional patterns
    $conditional_patterns = [
        'is_admin' => '/is_admin\s*\(\s*\)/',
        'wp_doing_ajax' => '/wp_doing_ajax\s*\(\s*\)/',
        'current_user_can' => '/current_user_can\s*\([^)]+\)/',
        'hook_suffix' => '/\$hook_suffix/',
        'page_check' => '/page.*===/',
        'screen_check' => '/screen.*===/',
        'frontend_only' => '/!is_admin/',
        'rest_request' => '/REST_REQUEST/',
        'doing_cron' => '/DOING_CRON/'
    ];
    
    foreach ($conditional_patterns as $name => $pattern) {
        if (preg_match($pattern, $context)) {
            $conditions[] = $name;
        }
    }
    
    return $conditions;
}

/**
 * Extract context around a match
 */
function extract_context($content, $offset, $lines_before_after = 2) {
    $lines = explode("\n", $content);
    $line_number = substr_count(substr($content, 0, $offset), "\n");
    
    $start = max(0, $line_number - $lines_before_after);
    $end = min(count($lines) - 1, $line_number + $lines_before_after);
    
    $context_lines = [];
    for ($i = $start; $i <= $end; $i++) {
        $context_lines[] = ($i + 1) . ': ' . trim($lines[$i]);
    }
    
    return implode("\n", $context_lines);
}

/**
 * Generate comprehensive analysis statistics
 */
function generate_analysis_statistics($patterns, $inclusions, $modules, $mappings) {
    $stats = [
        'total_patterns' => count($patterns),
        'total_inclusions' => count($inclusions),
        'total_modules' => count($modules),
        'total_mappings' => count($mappings),
        'by_function' => [],
        'by_file_type' => [],
        'conditional_loading' => [],
        'es6_dependencies' => 0,
        'unique_handles' => [],
        'unique_files' => []
    ];
    
    // Analyze patterns by function
    foreach ($patterns as $pattern) {
        $function = $pattern['function'];
        if (!isset($stats['by_function'][$function])) {
            $stats['by_function'][$function] = 0;
        }
        $stats['by_function'][$function]++;
    }
    
    // Analyze inclusions by type
    foreach ($inclusions as $inclusion) {
        $type = $inclusion['inclusion_type'];
        if (!isset($stats['by_file_type'][$type])) {
            $stats['by_file_type'][$type] = 0;
        }
        $stats['by_file_type'][$type]++;
    }
    
    // Analyze conditional loading
    foreach ($mappings as $mapping) {
        foreach ($mapping['conditional'] as $condition) {
            if (!isset($stats['conditional_loading'][$condition])) {
                $stats['conditional_loading'][$condition] = 0;
            }
            $stats['conditional_loading'][$condition]++;
        }
        
        if (!empty($mapping['handle'])) {
            $stats['unique_handles'][] = $mapping['handle'];
        }
        if (!empty($mapping['path'])) {
            $stats['unique_files'][] = $mapping['path'];
        }
    }
    
    // Count ES6 dependencies
    foreach ($modules as $module) {
        $stats['es6_dependencies'] += count($module['imports']);
    }
    
    // Remove duplicates
    $stats['unique_handles'] = array_unique($stats['unique_handles']);
    $stats['unique_files'] = array_unique($stats['unique_files']);
    $stats['unique_handles_count'] = count($stats['unique_handles']);
    $stats['unique_files_count'] = count($stats['unique_files']);
    
    return $stats;
}

/**
 * Output results in JSON format
 */
function output_json($patterns, $inclusions, $modules, $mappings, $stats) {
    $output = [
        'scan_info' => [
            'timestamp' => date('Y-m-d H:i:s'),
            'script_version' => MPAI_SCRIPT_VERSION,
            'plugin' => 'MemberPress AI Assistant'
        ],
        'statistics' => $stats,
        'wordpress_patterns' => $patterns,
        'direct_inclusions' => $inclusions,
        'es6_modules' => $modules,
        'asset_mappings' => $mappings
    ];
    
    echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
}

/**
 * Output results in human readable format
 */
function output_human($patterns, $inclusions, $modules, $mappings, $stats, $options) {
    if (!$options['quiet']) {
        echo "\n" . MPAI_SCRIPT_NAME . " v" . MPAI_SCRIPT_VERSION . "\n";
        echo str_repeat("=", 70) . "\n";
        echo "Scan completed: " . date('Y-m-d H:i:s') . "\n\n";
    }
    
    // Summary statistics
    if (!$options['quiet']) {
        echo "SUMMARY STATISTICS\n";
        echo str_repeat("-", 40) . "\n";
        echo "WordPress Registration Patterns: {$stats['total_patterns']}\n";
        echo "Direct File Inclusions: {$stats['total_inclusions']}\n";
        echo "ES6 Modules Found: {$stats['total_modules']}\n";
        echo "Asset Handle Mappings: {$stats['total_mappings']}\n";
        echo "Unique Asset Handles: {$stats['unique_handles_count']}\n";
        echo "Unique Asset Files: {$stats['unique_files_count']}\n";
        echo "ES6 Import Dependencies: {$stats['es6_dependencies']}\n\n";
    }
    
    // Registration patterns by function
    if (!$options['files_only'] && !empty($stats['by_function'])) {
        echo "REGISTRATION FUNCTIONS USAGE\n";
        echo str_repeat("-", 40) . "\n";
        foreach ($stats['by_function'] as $function => $count) {
            echo sprintf("%-25s %3d calls\n", $function . ':', $count);
        }
        echo "\n";
    }
    
    // Conditional loading analysis
    if (!$options['files_only'] && !empty($stats['conditional_loading'])) {
        echo "CONDITIONAL LOADING PATTERNS\n";
        echo str_repeat("-", 40) . "\n";
        foreach ($stats['conditional_loading'] as $condition => $count) {
            echo sprintf("%-25s %3d assets\n", $condition . ':', $count);
        }
        echo "\n";
    }
    
    // WordPress registration patterns
    if ($options['patterns_only'] || $options['verbose']) {
        echo "WORDPRESS REGISTRATION PATTERNS\n";
        echo str_repeat("-", 50) . "\n";
        foreach ($patterns as $pattern) {
            echo "Function: {$pattern['function']}\n";
            echo "File: {$pattern['file']}:{$pattern['line']}\n";
            echo "Code: {$pattern['code']}\n";
            if ($options['verbose']) {
                echo "Context:\n{$pattern['context']}\n";
            }
            echo str_repeat("-", 30) . "\n";
        }
        echo "\n";
    }
    
    // Asset mappings
    if ($options['files_only'] || $options['verbose']) {
        echo "ASSET HANDLE TO FILE MAPPINGS\n";
        echo str_repeat("-", 50) . "\n";
        foreach ($mappings as $mapping) {
            echo "Handle: {$mapping['handle']}\n";
            echo "Path: {$mapping['path']}\n";
            echo "Function: {$mapping['function']}\n";
            echo "Location: {$mapping['file']}:{$mapping['line']}\n";
            if (!empty($mapping['conditional'])) {
                echo "Conditions: " . implode(', ', $mapping['conditional']) . "\n";
            }
            echo str_repeat("-", 30) . "\n";
        }
        echo "\n";
    }
    
    // Direct inclusions
    if ($options['patterns_only'] || $options['verbose']) {
        echo "DIRECT FILE INCLUSIONS\n";
        echo str_repeat("-", 50) . "\n";
        foreach ($inclusions as $inclusion) {
            echo "Type: {$inclusion['inclusion_type']}\n";
            echo "File: {$inclusion['file']}:{$inclusion['line']}\n";
            echo "Asset: {$inclusion['asset_path']}\n";
            echo "Code: {$inclusion['code']}\n";
            if ($options['verbose']) {
                echo "Context:\n{$inclusion['context']}\n";
            }
            echo str_repeat("-", 30) . "\n";
        }
        echo "\n";
    }
    
    // ES6 modules
    if ($options['patterns_only'] || $options['verbose']) {
        echo "ES6 MODULES ANALYSIS\n";
        echo str_repeat("-", 50) . "\n";
        foreach ($modules as $module) {
            echo "File: {$module['file']}\n";
            echo "Is Module: " . ($module['is_module'] ? 'Yes' : 'No') . "\n";
            
            if (!empty($module['imports'])) {
                echo "Imports:\n";
                foreach ($module['imports'] as $import) {
                    echo "  Line {$import['line']}: {$import['path']}\n";
                    if ($options['verbose']) {
                        echo "    Code: {$import['code']}\n";
                    }
                }
            }
            
            if (!empty($module['exports'])) {
                echo "Exports:\n";
                foreach ($module['exports'] as $export) {
                    echo "  Line {$export['line']}: {$export['name']}\n";
                    if ($options['verbose']) {
                        echo "    Code: {$export['code']}\n";
                    }
                }
            }
            echo str_repeat("-", 30) . "\n";
        }
        echo "\n";
    }
}

/**
 * Main execution function
 */
function main($argv) {
    global $plugin_dir;
    
    // Parse command line arguments
    $options = parse_arguments($argv);
    
    // Show help if requested
    if ($options['help']) {
        display_help();
        exit(0);
    }
    
    // Validate plugin directory
    if (!is_dir($plugin_dir)) {
        echo "Error: Plugin directory not found: {$plugin_dir}\n";
        exit(1);
    }
    
    // Define scan directories
    $src_dir = $plugin_dir . '/src';
    $templates_dir = $plugin_dir . '/templates';
    $assets_dir = $plugin_dir . '/assets';
    
    // Validate directories exist
    $required_dirs = [$src_dir, $templates_dir, $assets_dir];
    foreach ($required_dirs as $dir) {
        if (!is_dir($dir)) {
            echo "Warning: Directory not found: {$dir}\n";
        }
    }
    
    if (!$options['quiet']) {
        echo "Scanning asset registration patterns...\n";
    }
    
    // Scan for WordPress registration patterns in PHP files
    $patterns = [];
    if (is_dir($src_dir)) {
        $patterns = array_merge($patterns, scan_wordpress_registration_patterns($src_dir));
    }
    if (is_dir($templates_dir)) {
        $patterns = array_merge($patterns, scan_wordpress_registration_patterns($templates_dir));
    }
    
    // Scan for direct inclusions in templates
    $inclusions = [];
    if (is_dir($templates_dir)) {
        $inclusions = scan_direct_inclusions($templates_dir);
    }
    
    // Analyze ES6 modules in JavaScript files
    $modules = [];
    if (is_dir($assets_dir)) {
        $modules = analyze_es6_modules($assets_dir);
    }
    
    // Extract asset mappings
    $mappings = extract_asset_mappings($patterns);
    
    // Generate statistics
    $stats = generate_analysis_statistics($patterns, $inclusions, $modules, $mappings);
    
    // Output results
    if ($options['json']) {
        output_json($patterns, $inclusions, $modules, $mappings, $stats);
    } else {
        output_human($patterns, $inclusions, $modules, $mappings, $stats, $options);
    }
    
    // Exit with appropriate code
    exit(0);
}

// Run the script
if (php_sapi_name() === 'cli') {
    main($argv);
} else {
    echo "This script must be run from the command line.\n";
    exit(1);
}