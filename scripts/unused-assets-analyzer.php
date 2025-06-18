#!/usr/bin/env php
<?php
/**
 * MemberPress AI Assistant - Unused Assets Analyzer Script
 * 
 * This script cross-references asset inventory data with registration patterns to identify
 * potentially unused CSS and JavaScript files. It analyzes ES6 module dependencies,
 * provides risk assessment for each potentially unused asset, and generates comprehensive
 * reports with evidence and recommendations.
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
define('MPAI_SCRIPT_NAME', 'Unused Assets Analyzer');

// Get the plugin directory (script is in scripts/ subdirectory)
$plugin_dir = dirname(__DIR__);
$scripts_dir = $plugin_dir . '/scripts';

/**
 * Display help information
 */
function display_help() {
    echo "\n" . MPAI_SCRIPT_NAME . " v" . MPAI_SCRIPT_VERSION . "\n";
    echo str_repeat("=", 70) . "\n";
    echo "Cross-references asset inventory with registration patterns to identify unused assets.\n\n";
    echo "USAGE:\n";
    echo "  php scripts/unused-assets-analyzer.php [OPTIONS]\n\n";
    echo "OPTIONS:\n";
    echo "  --help, -h        Show this help message\n";
    echo "  --json            Output results in JSON format\n";
    echo "  --verbose, -v     Enable verbose output with detailed evidence\n";
    echo "  --quiet, -q       Minimal output (only summary statistics)\n";
    echo "  --risk-only       Show only risk assessment summary\n";
    echo "  --high-risk       Show only high-risk unused assets\n";
    echo "  --generate-data   Generate required JSON data files if missing\n\n";
    echo "EXAMPLES:\n";
    echo "  php scripts/unused-assets-analyzer.php\n";
    echo "  php scripts/unused-assets-analyzer.php --json\n";
    echo "  php scripts/unused-assets-analyzer.php --verbose\n";
    echo "  php scripts/unused-assets-analyzer.php --high-risk\n";
    echo "  php scripts/unused-assets-analyzer.php --generate-data\n\n";
    echo "OUTPUT:\n";
    echo "  - Summary of unused vs used assets with risk categorization\n";
    echo "  - Detailed analysis of potentially unused assets with evidence\n";
    echo "  - ES6 module dependency analysis and unused module detection\n";
    echo "  - Risk assessment (low/medium/high) with explanations\n";
    echo "  - Cleanup recommendations and action items\n\n";
    echo "DEPENDENCIES:\n";
    echo "  This script requires JSON output from:\n";
    echo "  - scripts/asset-inventory.php (asset inventory data)\n";
    echo "  - scripts/asset-registration-analyzer.php (registration patterns)\n";
    echo "  Use --generate-data to create these files automatically.\n\n";
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
        'risk_only' => false,
        'high_risk' => false,
        'generate_data' => false
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
            case '--risk-only':
                $options['risk_only'] = true;
                break;
            case '--high-risk':
                $options['high_risk'] = true;
                break;
            case '--generate-data':
                $options['generate_data'] = true;
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
 * Generate required JSON data files if missing
 */
function generate_required_data($scripts_dir, $quiet = false) {
    $inventory_file = $scripts_dir . '/asset-inventory.json';
    $registration_file = $scripts_dir . '/asset-registration.json';
    
    $generated = [];
    
    // Generate asset inventory if missing
    if (!file_exists($inventory_file)) {
        if (!$quiet) echo "Generating asset inventory data...\n";
        $cmd = "cd " . escapeshellarg(dirname($scripts_dir)) . " && php scripts/asset-inventory.php --json --quiet > " . escapeshellarg($inventory_file);
        exec($cmd, $output, $return_code);
        if ($return_code === 0) {
            $generated[] = 'asset-inventory.json';
        } else {
            throw new Exception("Failed to generate asset inventory data");
        }
    }
    
    // Generate registration analysis if missing
    if (!file_exists($registration_file)) {
        if (!$quiet) echo "Generating asset registration analysis...\n";
        $cmd = "cd " . escapeshellarg(dirname($scripts_dir)) . " && php scripts/asset-registration-analyzer.php --json --quiet > " . escapeshellarg($registration_file);
        exec($cmd, $output, $return_code);
        if ($return_code === 0) {
            $generated[] = 'asset-registration.json';
        } else {
            throw new Exception("Failed to generate asset registration analysis");
        }
    }
    
    return $generated;
}

/**
 * Load and validate JSON data from previous scripts
 */
function load_analysis_data($scripts_dir) {
    $inventory_file = $scripts_dir . '/asset-inventory.json';
    $registration_file = $scripts_dir . '/asset-registration.json';
    
    // Check if files exist
    if (!file_exists($inventory_file)) {
        throw new Exception("Asset inventory file not found: {$inventory_file}\nRun: php scripts/asset-inventory.php --json > {$inventory_file}");
    }
    
    if (!file_exists($registration_file)) {
        throw new Exception("Asset registration file not found: {$registration_file}\nRun: php scripts/asset-registration-analyzer.php --json > {$registration_file}");
    }
    
    // Load and decode JSON data
    $inventory_data = json_decode(file_get_contents($inventory_file), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON in asset inventory file: " . json_last_error_msg());
    }
    
    $registration_data = json_decode(file_get_contents($registration_file), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON in asset registration file: " . json_last_error_msg());
    }
    
    return [
        'inventory' => $inventory_data,
        'registration' => $registration_data
    ];
}

/**
 * Build ES6 module dependency graph
 */
function build_es6_dependency_graph($modules) {
    $graph = [];
    $entry_points = [];
    
    foreach ($modules as $module) {
        $file_path = $module['file'];
        $graph[$file_path] = [
            'imports' => [],
            'exports' => $module['exports'],
            'imported_by' => [],
            'is_entry_point' => false
        ];
        
        // Process imports and resolve relative paths
        foreach ($module['imports'] as $import) {
            $import_path = resolve_import_path($import['path'], $file_path);
            $graph[$file_path]['imports'][] = $import_path;
        }
    }
    
    // Build reverse dependencies (imported_by)
    foreach ($graph as $file => $data) {
        foreach ($data['imports'] as $import) {
            if (isset($graph[$import])) {
                $graph[$import]['imported_by'][] = $file;
            }
        }
    }
    
    // Identify entry points (files not imported by others)
    foreach ($graph as $file => $data) {
        if (empty($data['imported_by'])) {
            $graph[$file]['is_entry_point'] = true;
            $entry_points[] = $file;
        }
    }
    
    return [
        'graph' => $graph,
        'entry_points' => $entry_points
    ];
}

/**
 * Resolve relative import paths to actual file paths
 */
function resolve_import_path($import_path, $current_file) {
    // Handle relative imports
    if (strpos($import_path, './') === 0 || strpos($import_path, '../') === 0) {
        $current_dir = dirname($current_file);
        $resolved = $current_dir . '/' . $import_path;
        
        // Normalize path (remove ./ and ../)
        $parts = explode('/', $resolved);
        $normalized = [];
        foreach ($parts as $part) {
            if ($part === '..') {
                array_pop($normalized);
            } elseif ($part !== '.' && $part !== '') {
                $normalized[] = $part;
            }
        }
        
        $resolved = implode('/', $normalized);
        
        // Add .js extension if missing
        if (!pathinfo($resolved, PATHINFO_EXTENSION)) {
            $resolved .= '.js';
        }
        
        return $resolved;
    }
    
    return $import_path;
}

/**
 * Cross-reference assets with registration patterns
 */
function cross_reference_assets($inventory_data, $registration_data) {
    $assets = $inventory_data['files'];
    $mappings = $registration_data['asset_mappings'];
    $es6_modules = $registration_data['es6_modules'];
    
    // Build dependency graph for ES6 modules
    $dependency_analysis = build_es6_dependency_graph($es6_modules);
    
    $analysis = [
        'used_assets' => [],
        'unused_assets' => [],
        'es6_analysis' => $dependency_analysis
    ];
    
    // Create lookup maps
    $registered_paths = [];
    $direct_inclusions = [];
    
    // Map registered asset paths
    foreach ($mappings as $mapping) {
        if (!empty($mapping['path'])) {
            $clean_path = normalize_asset_path($mapping['path']);
            $registered_paths[$clean_path] = $mapping;
        }
    }
    
    // Map direct inclusions
    foreach ($registration_data['direct_inclusions'] as $inclusion) {
        $clean_path = normalize_asset_path($inclusion['asset_path']);
        $direct_inclusions[$clean_path] = $inclusion;
    }
    
    // Analyze each asset
    foreach ($assets as $asset) {
        $asset_path = $asset['path'];
        $normalized_path = normalize_asset_path($asset_path);
        
        $usage_evidence = [];
        $risk_factors = [];
        
        // Check WordPress registration
        if (isset($registered_paths[$normalized_path])) {
            $usage_evidence[] = [
                'type' => 'wordpress_registration',
                'details' => $registered_paths[$normalized_path]
            ];
        }
        
        // Check direct inclusions
        if (isset($direct_inclusions[$normalized_path])) {
            $usage_evidence[] = [
                'type' => 'direct_inclusion',
                'details' => $direct_inclusions[$normalized_path]
            ];
        }
        
        // Check ES6 module usage
        if ($asset['is_es6_module']) {
            $module_usage = analyze_es6_module_usage($asset_path, $dependency_analysis);
            if ($module_usage['is_used']) {
                $usage_evidence[] = [
                    'type' => 'es6_module_dependency',
                    'details' => $module_usage
                ];
            } else {
                $risk_factors[] = 'es6_module_unused';
            }
        }
        
        // Check for dynamic loading patterns
        $dynamic_usage = check_dynamic_loading($asset_path, $registration_data);
        if (!empty($dynamic_usage)) {
            $usage_evidence[] = [
                'type' => 'dynamic_loading',
                'details' => $dynamic_usage
            ];
        }
        
        // Assess risk factors
        $risk_assessment = assess_asset_risk($asset, $usage_evidence, $risk_factors);
        
        $asset_analysis = [
            'asset' => $asset,
            'usage_evidence' => $usage_evidence,
            'risk_factors' => $risk_factors,
            'risk_assessment' => $risk_assessment,
            'is_used' => !empty($usage_evidence)
        ];
        
        if (empty($usage_evidence)) {
            $analysis['unused_assets'][] = $asset_analysis;
        } else {
            $analysis['used_assets'][] = $asset_analysis;
        }
    }
    
    return $analysis;
}

/**
 * Normalize asset path for comparison
 */
function normalize_asset_path($path) {
    // Handle doubled assets directory (from old inventory data)
    $path = preg_replace('#assets/[^/]+/assets/#', 'assets/', $path);
    
    // Remove leading slashes and dots
    $path = ltrim($path, './');
    
    // Remove query parameters and fragments
    $path = strtok($path, '?');
    $path = strtok($path, '#');
    
    // Ensure consistent format: assets/css/file.css or assets/js/file.js
    if (!preg_match('#^assets/#', $path)) {
        // If path doesn't start with assets/, try to infer the correct prefix
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        if ($extension === 'css') {
            $path = 'assets/css/' . $path;
        } elseif ($extension === 'js') {
            $path = 'assets/js/' . $path;
        }
    }
    
    return $path;
}

/**
 * Analyze ES6 module usage in dependency graph
 */
function analyze_es6_module_usage($file_path, $dependency_analysis) {
    $graph = $dependency_analysis['graph'];
    $entry_points = $dependency_analysis['entry_points'];
    
    if (!isset($graph[$file_path])) {
        return ['is_used' => false, 'reason' => 'not_in_dependency_graph'];
    }
    
    $module = $graph[$file_path];
    
    // Entry points are considered used
    if ($module['is_entry_point']) {
        return [
            'is_used' => true,
            'reason' => 'entry_point',
            'imported_by' => $module['imported_by']
        ];
    }
    
    // Modules imported by others are used
    if (!empty($module['imported_by'])) {
        return [
            'is_used' => true,
            'reason' => 'imported_by_others',
            'imported_by' => $module['imported_by']
        ];
    }
    
    return [
        'is_used' => false,
        'reason' => 'no_imports_or_dependencies',
        'imported_by' => []
    ];
}

/**
 * Check for dynamic loading patterns
 */
function check_dynamic_loading($asset_path, $registration_data) {
    $dynamic_patterns = [];
    
    // Check for conditional loading in mappings
    foreach ($registration_data['asset_mappings'] as $mapping) {
        if (!empty($mapping['conditional']) && strpos($mapping['path'], basename($asset_path)) !== false) {
            $dynamic_patterns[] = [
                'type' => 'conditional_loading',
                'conditions' => $mapping['conditional'],
                'mapping' => $mapping
            ];
        }
    }
    
    return $dynamic_patterns;
}

/**
 * Assess risk level for potentially unused asset
 */
function assess_asset_risk($asset, $usage_evidence, $risk_factors) {
    $risk_score = 0;
    $risk_reasons = [];
    
    // Base risk for no usage evidence
    if (empty($usage_evidence)) {
        $risk_score += 50;
        $risk_reasons[] = 'no_registration_patterns_found';
    }
    
    // File age factor
    $days_since_modified = (time() - $asset['modified_timestamp']) / (24 * 60 * 60);
    if ($days_since_modified > 365) {
        $risk_score += 20;
        $risk_reasons[] = 'not_modified_in_over_year';
    } elseif ($days_since_modified < 30) {
        $risk_score -= 15;
        $risk_reasons[] = 'recently_modified';
    }
    
    // File size factor
    if ($asset['size_bytes'] > 50000) {
        $risk_score += 10;
        $risk_reasons[] = 'large_file_size';
    }
    
    // ES6 module factors
    if ($asset['is_es6_module']) {
        if (in_array('es6_module_unused', $risk_factors)) {
            $risk_score += 25;
            $risk_reasons[] = 'es6_module_with_no_dependencies';
        } else {
            $risk_score -= 10;
            $risk_reasons[] = 'es6_module_with_dependencies';
        }
    }
    
    // File type factors
    if ($asset['type'] === 'Minified JavaScript' || $asset['type'] === 'Minified CSS') {
        $risk_score += 15;
        $risk_reasons[] = 'minified_file_suggests_production_use';
    }
    
    // Directory-based risk assessment
    if (strpos($asset['path'], 'assets/js/chat/') === 0) {
        $risk_score -= 5;
        $risk_reasons[] = 'part_of_chat_system';
    }
    
    // Determine risk level
    if ($risk_score >= 70) {
        $risk_level = 'high';
    } elseif ($risk_score >= 40) {
        $risk_level = 'medium';
    } else {
        $risk_level = 'low';
    }
    
    return [
        'level' => $risk_level,
        'score' => $risk_score,
        'reasons' => $risk_reasons
    ];
}

/**
 * Generate comprehensive analysis statistics
 */
function generate_analysis_statistics($analysis) {
    $stats = [
        'total_assets' => count($analysis['used_assets']) + count($analysis['unused_assets']),
        'used_assets' => count($analysis['used_assets']),
        'unused_assets' => count($analysis['unused_assets']),
        'usage_percentage' => 0,
        'risk_breakdown' => [
            'low' => 0,
            'medium' => 0,
            'high' => 0
        ],
        'evidence_types' => [],
        'es6_modules' => [
            'total' => 0,
            'entry_points' => count($analysis['es6_analysis']['entry_points']),
            'unused' => 0
        ],
        'size_analysis' => [
            'used_size' => 0,
            'unused_size' => 0,
            'potential_savings' => 0
        ]
    ];
    
    // Calculate usage percentage
    if ($stats['total_assets'] > 0) {
        $stats['usage_percentage'] = round(($stats['used_assets'] / $stats['total_assets']) * 100, 2);
    }
    
    // Analyze risk breakdown and evidence types
    foreach ($analysis['unused_assets'] as $unused) {
        $risk_level = $unused['risk_assessment']['level'];
        $stats['risk_breakdown'][$risk_level]++;
        $stats['size_analysis']['unused_size'] += $unused['asset']['size_bytes'];
        
        if ($unused['asset']['is_es6_module']) {
            $stats['es6_modules']['unused']++;
        }
    }
    
    foreach ($analysis['used_assets'] as $used) {
        $stats['size_analysis']['used_size'] += $used['asset']['size_bytes'];
        
        if ($used['asset']['is_es6_module']) {
            $stats['es6_modules']['total']++;
        }
        
        foreach ($used['usage_evidence'] as $evidence) {
            $type = $evidence['type'];
            if (!isset($stats['evidence_types'][$type])) {
                $stats['evidence_types'][$type] = 0;
            }
            $stats['evidence_types'][$type]++;
        }
    }
    
    $stats['es6_modules']['total'] += $stats['es6_modules']['unused'];
    $stats['size_analysis']['potential_savings'] = $stats['size_analysis']['unused_size'];
    
    return $stats;
}

/**
 * Format file size in human readable format
 */
function format_file_size($bytes) {
    if ($bytes === 0) return '0 B';
    
    $units = ['B', 'KB', 'MB', 'GB'];
    $factor = floor(log($bytes, 1024));
    
    return sprintf('%.2f %s', $bytes / pow(1024, $factor), $units[$factor]);
}

/**
 * Generate cleanup recommendations
 */
function generate_recommendations($analysis, $stats) {
    $recommendations = [];
    
    // High-risk assets
    $high_risk_count = $stats['risk_breakdown']['high'];
    if ($high_risk_count > 0) {
        $recommendations[] = [
            'priority' => 'high',
            'action' => 'immediate_review',
            'description' => "Review {$high_risk_count} high-risk unused assets for immediate removal",
            'impact' => 'High potential for safe removal'
        ];
    }
    
    // ES6 modules
    if ($stats['es6_modules']['unused'] > 0) {
        $recommendations[] = [
            'priority' => 'medium',
            'action' => 'es6_module_cleanup',
            'description' => "Review {$stats['es6_modules']['unused']} unused ES6 modules in chat system",
            'impact' => 'Improve module loading performance'
        ];
    }
    
    // Size savings
    if ($stats['size_analysis']['potential_savings'] > 10000) {
        $savings = format_file_size($stats['size_analysis']['potential_savings']);
        $recommendations[] = [
            'priority' => 'medium',
            'action' => 'size_optimization',
            'description' => "Potential size savings of {$savings} from unused asset removal",
            'impact' => 'Reduce plugin size and improve load times'
        ];
    }
    
    // Medium-risk assets
    $medium_risk_count = $stats['risk_breakdown']['medium'];
    if ($medium_risk_count > 0) {
        $recommendations[] = [
            'priority' => 'low',
            'action' => 'conditional_review',
            'description' => "Review {$medium_risk_count} medium-risk assets for conditional usage patterns",
            'impact' => 'Verify dynamic loading before removal'
        ];
    }
    
    return $recommendations;
}

/**
 * Output results in JSON format
 */
function output_json($analysis, $stats, $recommendations) {
    $output = [
        'scan_info' => [
            'timestamp' => date('Y-m-d H:i:s'),
            'script_version' => MPAI_SCRIPT_VERSION,
            'plugin' => 'MemberPress AI Assistant'
        ],
        'statistics' => $stats,
        'analysis' => $analysis,
        'recommendations' => $recommendations
    ];
    
    echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
}

/**
 * Output results in human readable format
 */
function output_human($analysis, $stats, $recommendations, $options) {
    if (!$options['quiet']) {
        echo "\n" . MPAI_SCRIPT_NAME . " v" . MPAI_SCRIPT_VERSION . "\n";
        echo str_repeat("=", 80) . "\n";
        echo "Cross-reference analysis completed: " . date('Y-m-d H:i:s') . "\n\n";
    }
    
    // Summary statistics
    if (!$options['quiet']) {
        echo "SUMMARY STATISTICS\n";
        echo str_repeat("-", 50) . "\n";
        echo "Total Assets Analyzed: {$stats['total_assets']}\n";
        echo "Used Assets: {$stats['used_assets']} ({$stats['usage_percentage']}%)\n";
        echo "Potentially Unused: {$stats['unused_assets']}\n";
        echo "Used Size: " . format_file_size($stats['size_analysis']['used_size']) . "\n";
        echo "Unused Size: " . format_file_size($stats['size_analysis']['unused_size']) . "\n";
        echo "Potential Savings: " . format_file_size($stats['size_analysis']['potential_savings']) . "\n\n";
    }
    
    // Risk breakdown
    if ($options['risk_only'] || !$options['quiet']) {
        echo "RISK ASSESSMENT BREAKDOWN\n";
        echo str_repeat("-", 50) . "\n";
        echo "High Risk (safe to remove): {$stats['risk_breakdown']['high']} assets\n";
        echo "Medium Risk (review needed): {$stats['risk_breakdown']['medium']} assets\n";
        echo "Low Risk (likely in use): {$stats['risk_breakdown']['low']} assets\n\n";
    }
    
    // ES6 module analysis
    if (!$options['quiet']) {
        echo "ES6 MODULE ANALYSIS\n";
        echo str_repeat("-", 50) . "\n";
        echo "Total ES6 Modules: {$stats['es6_modules']['total']}\n";
        echo "Entry Points: {$stats['es6_modules']['entry_points']}\n";
        echo "Unused Modules: {$stats['es6_modules']['unused']}\n\n";
    }
    
    // Evidence types
    if (!$options['quiet'] && !empty($stats['evidence_types'])) {
        echo "USAGE EVIDENCE TYPES\n";
        echo str_repeat("-", 50) . "\n";
        foreach ($stats['evidence_types'] as $type => $count) {
            echo sprintf("%-30s %3d assets\n", ucwords(str_replace('_', ' ', $type)) . ':', $count);
        }
        echo "\n";
    }
    
    // Detailed unused assets analysis
    if ($options['verbose'] || $options['high_risk']) {
        $assets_to_show = $options['high_risk'] 
            ? array_filter($analysis['unused_assets'], function($asset) { 
                return $asset['risk_assessment']['level'] === 'high'; 
              })
            : $analysis['unused_assets'];
            
        if (!empty($assets_to_show)) {
            $title = $options['high_risk'] ? 'HIGH-RISK UNUSED ASSETS' : 'POTENTIALLY UNUSED ASSETS';
            echo "{$title}\n";
            echo str_repeat("-", 60) . "\n";
            
            foreach ($assets_to_show as $unused) {
                $asset = $unused['asset'];
                $risk = $unused['risk_assessment'];
                
                echo "File: {$asset['path']}\n";
                echo "Size: {$asset['size_human']} ({$asset['size_bytes']} bytes)\n";
                echo "Type: {$asset['type']}\n";
                echo "Modified: {$asset['modified']}\n";
                echo "Risk Level: " . strtoupper($risk['level']) . " (Score: {$risk['score']})\n";
                
                if ($options['verbose']) {
                    echo "Risk Factors:\n";
                    foreach ($risk['reasons'] as $reason) {
                        echo "  - " . ucwords(str_replace('_', ' ', $reason)) . "\n";
                    }
                    
                    if (!empty($unused['risk_factors'])) {
                        echo "Additional Factors:\n";
                        foreach ($unused['risk_factors'] as $factor) {
                            echo "  - " . ucwords(str_replace('_', ' ', $factor)) . "\n";
                        }
                    }
                }
                
                echo str_repeat("-", 40) . "\n";
            }
            echo "\n";
        }
    }
    
    // Recommendations
    if (!$options['quiet'] && !empty($recommendations)) {
        echo "CLEANUP RECOMMENDATIONS\n";
        echo str_repeat("-", 50) . "\n";
        foreach ($recommendations as $rec) {
            echo "Priority: " . strtoupper($rec['priority']) . "\n";
            echo "Action: {$rec['description']}\n";
            echo "Impact: {$rec['impact']}\n";
            echo str_repeat("-", 30) . "\n";
        }
        echo "\n";
    }
    
    // ES6 dependency graph summary
    if ($options['verbose'] && !empty($analysis['es6_analysis']['entry_points'])) {
        echo "ES6 MODULE ENTRY POINTS\n";
        echo str_repeat("-", 50) . "\n";
        foreach ($analysis['es6_analysis']['entry_points'] as $entry_point) {
            echo "- {$entry_point}\n";
        }
        echo "\n";
    }
}

/**
 * Main execution function
 */
function main($argv) {
    global $plugin_dir, $scripts_dir;
    
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
    
    try {
        // Generate required data files if requested
        if ($options['generate_data']) {
            $generated = generate_required_data($scripts_dir, $options['quiet']);
            if (!$options['quiet'] && !empty($generated)) {
                echo "Generated data files: " . implode(', ', $generated) . "\n\n";
            }
        }
        
        // Load analysis data
        if (!$options['quiet']) {
            echo "Loading analysis data...\n";
        }
        $data = load_analysis_data($scripts_dir);
        
        // Perform cross-reference analysis
        if (!$options['quiet']) {
            echo "Performing cross-reference analysis...\n";
        }
        $analysis = cross_reference_assets($data['inventory'], $data['registration']);
        
        // Generate statistics
        $stats = generate_analysis_statistics($analysis);
        
        // Generate recommendations
        $recommendations = generate_recommendations($analysis, $stats);
        
        // Output results
        if ($options['json']) {
            output_json($analysis, $stats, $recommendations);
        } else {
            output_human($analysis, $stats, $recommendations, $options);
        }
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
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