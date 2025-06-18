#!/usr/bin/env php
<?php
/**
 * MemberPress AI Assistant - PHP Class Analyzer Script
 * 
 * This script analyzes PHP classes, interfaces, and their usage patterns throughout
 * the codebase, building on the asset analysis system. It identifies class definitions,
 * inheritance relationships, DI container registrations, and usage patterns to detect
 * potentially unused classes and dependencies.
 * 
 * Part of Phase 2 analysis system for identifying unused PHP classes and dependencies.
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
define('MPAI_SCRIPT_NAME', 'PHP Class Analyzer');

// Get the plugin directory (script is in scripts/ subdirectory)
$plugin_dir = dirname(__DIR__);

/**
 * Display help information
 */
function display_help() {
    echo "\n" . MPAI_SCRIPT_NAME . " v" . MPAI_SCRIPT_VERSION . "\n";
    echo str_repeat("=", 70) . "\n";
    echo "Analyzes PHP classes, interfaces, and dependencies in the MemberPress AI Assistant plugin.\n\n";
    echo "USAGE:\n";
    echo "  php scripts/php-class-analyzer.php [OPTIONS]\n\n";
    echo "OPTIONS:\n";
    echo "  --help, -h        Show this help message\n";
    echo "  --json            Output results in JSON format\n";
    echo "  --verbose, -v     Enable verbose output with detailed information\n";
    echo "  --quiet, -q       Minimal output (only summary statistics)\n";
    echo "  --classes-only    Show only class information\n";
    echo "  --di-only         Show only DI container analysis\n";
    echo "  --unused-only     Show only unused class analysis\n\n";
    echo "EXAMPLES:\n";
    echo "  php scripts/php-class-analyzer.php\n";
    echo "  php scripts/php-class-analyzer.php --json\n";
    echo "  php scripts/php-class-analyzer.php --verbose\n";
    echo "  php scripts/php-class-analyzer.php --unused-only\n\n";
    echo "OUTPUT:\n";
    echo "  - PHP class definitions and inheritance relationships\n";
    echo "  - Interface implementations and abstract class analysis\n";
    echo "  - DI container registrations and service dependencies\n";
    echo "  - Class usage patterns and instantiation analysis\n";
    echo "  - Risk assessment for potentially unused classes\n";
    echo "  - Summary statistics and cleanup recommendations\n\n";
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
        'classes_only' => false,
        'di_only' => false,
        'unused_only' => false
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
            case '--classes-only':
                $options['classes_only'] = true;
                break;
            case '--di-only':
                $options['di_only'] = true;
                break;
            case '--unused-only':
                $options['unused_only'] = true;
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
 * Scan PHP files and extract class information
 */
function scan_php_classes($directory) {
    $classes = [];
    
    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $relative_path = str_replace($directory . '/', '', $file->getPathname());
                $class_info = analyze_php_file($file->getPathname(), $relative_path);
                
                if (!empty($class_info)) {
                    $classes = array_merge($classes, $class_info);
                }
            }
        }
    } catch (Exception $e) {
        echo "Error scanning PHP classes: " . $e->getMessage() . "\n";
    }
    
    return $classes;
}

/**
 * Analyze a single PHP file for class definitions
 */
function analyze_php_file($file_path, $relative_path) {
    $classes = [];
    $content = file_get_contents($file_path);
    
    if ($content === false) {
        return $classes;
    }
    
    // Extract namespace
    $namespace = extract_namespace($content);
    
    // Find class definitions
    $class_matches = find_class_definitions($content);
    foreach ($class_matches as $match) {
        $class_info = [
            'name' => $match['name'],
            'full_name' => $namespace ? $namespace . '\\' . $match['name'] : $match['name'],
            'namespace' => $namespace,
            'type' => $match['type'],
            'file' => $relative_path,
            'line' => $match['line'],
            'extends' => $match['extends'],
            'implements' => $match['implements'],
            'methods' => extract_methods_from_content($content, $match['start_pos']),
            'dependencies' => extract_dependencies_from_content($content),
            'is_abstract' => $match['is_abstract'],
            'is_final' => $match['is_final']
        ];
        
        $classes[] = $class_info;
    }
    
    return $classes;
}

/**
 * Extract namespace from PHP content
 */
function extract_namespace($content) {
    if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
        return trim($matches[1]);
    }
    return null;
}

/**
 * Find class definitions in PHP content
 */
function find_class_definitions($content) {
    $definitions = [];
    
    $pattern = '/^(abstract\s+)?(final\s+)?(class|interface|trait)\s+(\w+)(?:\s+extends\s+(\w+))?(?:\s+implements\s+([^{]+))?/m';
    
    if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
        foreach ($matches[0] as $index => $match) {
            $line_number = substr_count(substr($content, 0, $match[1]), "\n") + 1;
            
            $definitions[] = [
                'name' => $matches[4][$index][0],
                'type' => $matches[3][$index][0],
                'line' => $line_number,
                'start_pos' => $match[1],
                'extends' => !empty($matches[5][$index][0]) ? trim($matches[5][$index][0]) : null,
                'implements' => !empty($matches[6][$index][0]) ? 
                    array_map('trim', explode(',', $matches[6][$index][0])) : [],
                'is_abstract' => !empty($matches[1][$index][0]),
                'is_final' => !empty($matches[2][$index][0])
            ];
        }
    }
    
    return $definitions;
}

/**
 * Extract methods from content
 */
function extract_methods_from_content($content, $class_start_pos) {
    $methods = [];
    
    $pattern = '/(public|protected|private)\s+(static\s+)?function\s+(\w+)\s*\(/';
    
    if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
        foreach ($matches[0] as $index => $match) {
            if ($match[1] > $class_start_pos) {
                $methods[] = [
                    'name' => $matches[3][$index][0],
                    'visibility' => $matches[1][$index][0],
                    'is_static' => !empty($matches[2][$index][0])
                ];
            }
        }
    }
    
    return $methods;
}

/**
 * Extract dependencies from content
 */
function extract_dependencies_from_content($content) {
    $dependencies = [];
    
    if (preg_match_all('/use\s+([^;]+);/', $content, $matches)) {
        foreach ($matches[1] as $use_statement) {
            $parts = explode('\\', trim($use_statement));
            $class_name = end($parts);
            $dependencies[] = [
                'name' => strtolower($class_name),
                'type' => $use_statement
            ];
        }
    }
    
    return $dependencies;
}

/**
 * Analyze DI container registrations
 */
function analyze_di_registrations($plugin_dir) {
    $registrations = [];
    $providers_dir = $plugin_dir . '/src/DI/Providers';
    
    if (!is_dir($providers_dir)) {
        return $registrations;
    }
    
    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($providers_dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $relative_path = str_replace($plugin_dir . '/', '', $file->getPathname());
                $provider_registrations = analyze_service_provider($file->getPathname(), $relative_path);
                $registrations = array_merge($registrations, $provider_registrations);
            }
        }
    } catch (Exception $e) {
        echo "Error analyzing DI registrations: " . $e->getMessage() . "\n";
    }
    
    return $registrations;
}

/**
 * Analyze a service provider file
 */
function analyze_service_provider($file_path, $relative_path) {
    $registrations = [];
    $content = file_get_contents($file_path);
    
    if ($content === false) {
        return $registrations;
    }
    
    $provider_class = extract_provider_class_name($content);
    
    $patterns = [
        'singleton' => '/\$locator->singleton\s*\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*([^)]+)\)/',
        'registerSingleton' => '/\$this->registerSingleton\s*\(\s*\$locator\s*,\s*[\'"]([^\'"]+)[\'"]\s*,\s*([^)]+)\)/'
    ];
    
    foreach ($patterns as $type => $pattern) {
        if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $index => $match) {
                $line_number = substr_count(substr($content, 0, $match[1]), "\n") + 1;
                
                $registrations[] = [
                    'service_name' => $matches[1][$index][0],
                    'registration_type' => $type,
                    'provider_class' => $provider_class,
                    'provider_file' => $relative_path,
                    'line' => $line_number,
                    'definition' => trim($matches[2][$index][0]),
                    'resolved_class' => extract_class_from_definition($matches[2][$index][0])
                ];
            }
        }
    }
    
    return $registrations;
}

/**
 * Extract provider class name from content
 */
function extract_provider_class_name($content) {
    if (preg_match('/class\s+(\w+)\s+extends\s+ServiceProvider/', $content, $matches)) {
        return $matches[1];
    }
    return 'Unknown';
}

/**
 * Extract class name from registration definition
 */
function extract_class_from_definition($definition) {
    if (preg_match('/new\s+([A-Za-z_][A-Za-z0-9_\\\\]*)\s*\(/', $definition, $matches)) {
        return $matches[1];
    }
    
    if (preg_match('/([A-Za-z_][A-Za-z0-9_\\\\]*)::\s*class/', $definition, $matches)) {
        return $matches[1];
    }
    
    return null;
}

/**
 * Analyze class usage patterns
 */
function analyze_class_usage($plugin_dir, $classes) {
    $usage_patterns = [];
    
    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($plugin_dir . '/src', RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $relative_path = str_replace($plugin_dir . '/', '', $file->getPathname());
                $file_usage = analyze_file_usage($file->getPathname(), $relative_path, $classes);
                $usage_patterns = array_merge($usage_patterns, $file_usage);
            }
        }
    } catch (Exception $e) {
        echo "Error analyzing class usage: " . $e->getMessage() . "\n";
    }
    
    return $usage_patterns;
}

/**
 * Analyze usage patterns in a single file
 */
function analyze_file_usage($file_path, $relative_path, $classes) {
    $usage = [];
    $content = file_get_contents($file_path);
    
    if ($content === false) {
        return $usage;
    }
    
    foreach ($classes as $class) {
        $class_name = $class['name'];
        $full_name = $class['full_name'];
        
        // Check for direct instantiation
        $instantiation_pattern = '/new\s+' . preg_quote($class_name) . '\s*\(/';
        if (preg_match_all($instantiation_pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $match) {
                $line_number = substr_count(substr($content, 0, $match[1]), "\n") + 1;
                $usage[] = [
                    'class' => $full_name,
                    'usage_type' => 'direct_instantiation',
                    'file' => $relative_path,
                    'line' => $line_number
                ];
            }
        }
        
        // Check for static method calls
        $static_pattern = '/' . preg_quote($class_name) . '::\w+/';
        if (preg_match_all($static_pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $match) {
                $line_number = substr_count(substr($content, 0, $match[1]), "\n") + 1;
                $usage[] = [
                    'class' => $full_name,
                    'usage_type' => 'static_method_call',
                    'file' => $relative_path,
                    'line' => $line_number
                ];
            }
        }
        
        // Check for type hints
        $typehint_pattern = '/function\s+\w+\s*\([^)]*' . preg_quote($class_name) . '\s+\$\w+/';
        if (preg_match_all($typehint_pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $match) {
                $line_number = substr_count(substr($content, 0, $match[1]), "\n") + 1;
                $usage[] = [
                    'class' => $full_name,
                    'usage_type' => 'type_hint',
                    'file' => $relative_path,
                    'line' => $line_number
                ];
            }
        }
    }
    
    return $usage;
}

/**
 * Cross-reference classes with usage and DI registrations
 */
function cross_reference_analysis($classes, $di_registrations, $usage_patterns) {
    $analysis = [
        'used_classes' => [],
        'unused_classes' => [],
        'registered_classes' => [],
        'unregistered_classes' => []
    ];
    
    // Create lookup maps
    $registered_map = [];
    foreach ($di_registrations as $registration) {
        if ($registration['resolved_class']) {
            $registered_map[$registration['resolved_class']] = $registration;
        }
    }
    
    $usage_map = [];
    foreach ($usage_patterns as $usage) {
        if (!isset($usage_map[$usage['class']])) {
            $usage_map[$usage['class']] = [];
        }
        $usage_map[$usage['class']][] = $usage;
    }
    
    // Analyze each class
    foreach ($classes as $class) {
        $full_name = $class['full_name'];
        $class_analysis = [
            'class' => $class,
            'is_registered' => isset($registered_map[$full_name]),
            'registration_info' => $registered_map[$full_name] ?? null,
            'usage_patterns' => $usage_map[$full_name] ?? [],
            'is_used' => !empty($usage_map[$full_name]),
            'risk_assessment' => assess_class_risk($class, $usage_map[$full_name] ?? [], isset($registered_map[$full_name]))
        ];
        
        if ($class_analysis['is_used'] || $class_analysis['is_registered']) {
            $analysis['used_classes'][] = $class_analysis;
        } else {
            $analysis['unused_classes'][] = $class_analysis;
        }
        
        if ($class_analysis['is_registered']) {
            $analysis['registered_classes'][] = $class_analysis;
        } else {
            $analysis['unregistered_classes'][] = $class_analysis;
        }
    }
    
    return $analysis;
}

/**
 * Assess risk level for potentially unused class
 */
function assess_class_risk($class, $usage_patterns, $is_registered) {
    $risk_score = 0;
    $risk_reasons = [];
    
    // Base risk for no usage
    if (empty($usage_patterns)) {
        $risk_score += 40;
        $risk_reasons[] = 'no_direct_usage_found';
    }
    
    // Registration factor
    if (!$is_registered) {
        $risk_score += 20;
        $risk_reasons[] = 'not_registered_in_di_container';
    } else {
        $risk_score -= 15;
        $risk_reasons[] = 'registered_in_di_container';
    }
    
    // Class type factors
    if ($class['type'] === 'interface') {
        $risk_score -= 25;
        $risk_reasons[] = 'interface_likely_used_for_contracts';
    } elseif ($class['is_abstract']) {
        $risk_score -= 15;
        $risk_reasons[] = 'abstract_class_likely_extended';
    } elseif ($class['type'] === 'trait') {
        $risk_score -= 10;
        $risk_reasons[] = 'trait_likely_used_in_classes';
    }
    
    // WordPress patterns
    if (strpos($class['file'], 'Admin/') !== false) {
        $risk_score -= 5;
        $risk_reasons[] = 'admin_class_likely_used';
    }
    
    if (preg_match('/Tool|Agent|Service/', $class['name'])) {
        $risk_score -= 10;
        $risk_reasons[] = 'follows_plugin_naming_convention';
    }
    
    // Test classes
    if (strpos($class['file'], 'Test') !== false) {
        $risk_score += 15;
        $risk_reasons[] = 'test_class_may_be_unused';
    }
    
    // Determine risk level
    if ($risk_score >= 50) {
        $risk_level = 'high';
    } elseif ($risk_score >= 25) {
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
function generate_analysis_statistics($classes, $di_registrations, $usage_patterns, $cross_reference) {
    $stats = [
        'total_classes' => count($classes),
        'registered_classes' => count($cross_reference['registered_classes']),
        'unused_classes' => count($cross_reference['unused_classes']),
        'abstract_classes' => 0,
        'interfaces' => 0,
        'concrete_implementations' => 0,
        'di_registrations' => count($di_registrations),
        'usage_patterns' => count($usage_patterns),
        'risk_breakdown' => [
            'low' => 0,
            'medium' => 0,
            'high' => 0
        ],
        'by_type' => [],
        'by_namespace' => []
    ];
    
    // Analyze class types
    foreach ($classes as $class) {
        if ($class['type'] === 'interface') {
            $stats['interfaces']++;
        } elseif ($class['is_abstract']) {
            $stats['abstract_classes']++;
        } else {
            $stats['concrete_implementations']++;
        }
        
        // By type
        $type = $class['type'];
        if (!isset($stats['by_type'][$type])) {
            $stats['by_type'][$type] = 0;
        }
        $stats['by_type'][$type]++;
        
        // By namespace
        $namespace = $class['namespace'] ?? 'global';
        if (!isset($stats['by_namespace'][$namespace])) {
            $stats['by_namespace'][$namespace] = 0;
        }
        $stats['by_namespace'][$namespace]++;
    }
    
    // Risk breakdown
    foreach ($cross_reference['unused_classes'] as $unused) {
        $risk_level = $unused['risk_assessment']['level'];
        $stats['risk_breakdown'][$risk_level]++;
    }
    
    return $stats;
}

/**
 * Output results in JSON format
 */
function output_json($classes, $di_registrations, $usage_patterns, $cross_reference, $stats) {
    $output = [
        'scan_info' => [
            'timestamp' => date('Y-m-d H:i:s'),
            'script_version' => MPAI_SCRIPT_VERSION,
            'plugin' => 'MemberPress AI Assistant'
        ],
        'statistics' => $stats,
        'classes' => $classes,
        'dependencies' => $usage_patterns,
        'di_registrations' => $di_registrations,
        'unused_analysis' => $cross_reference['unused_classes']
    ];
    
    echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
}

/**
 * Output results in human readable format
 */
function output_human($classes, $di_registrations, $usage_patterns, $cross_reference, $stats, $options) {
    if (!$options['quiet']) {
        echo "\n" . MPAI_SCRIPT_NAME . " v" . MPAI_SCRIPT_VERSION . "\n";
        echo str_repeat("=", 80) . "\n";
        echo "PHP class analysis completed: " . date('Y-m-d H:i:s') . "\n\n";
    }
    
    // Summary statistics
    if (!$options['quiet']) {
        echo "SUMMARY STATISTICS\n";
        echo str_repeat("-", 50) . "\n";
        echo "Total Classes Analyzed: {$stats['total_classes']}\n";
        echo "Registered in DI Container: {$stats['registered_classes']}\n";
        echo "Potentially Unused: {$stats['unused_classes']}\n";
        echo "Abstract Classes: {$stats['abstract_classes']}\n";
        echo "Interfaces: {$stats['interfaces']}\n";
        echo "Concrete Implementations: {$stats['concrete_implementations']}\n";
        echo "DI Registrations: {$stats['di_registrations']}\n";
        echo "Usage Patterns Found: {$stats['usage_patterns']}\n\n";
    }
    
    // Risk breakdown
    if (!$options['quiet']) {
        echo "RISK ASSESSMENT BREAKDOWN\n";
        echo str_repeat("-", 50) . "\n";
        echo "High Risk (likely unused): {$stats['risk_breakdown']['high']} classes\n";
        echo "Medium Risk (review needed): {$stats['risk_breakdown']['medium']} classes\n";
        echo "Low Risk (likely in use): {$stats['risk_breakdown']['low']} classes\n\n";
    }
    
    // Unused classes analysis
    if ($options['unused_only'] || $options['verbose']) {
        if (!empty($cross_reference['unused_classes'])) {
            echo "POTENTIALLY UNUSED CLASSES\n";
            echo str_repeat("-", 60) . "\n";
            foreach ($cross_reference['unused_classes'] as $unused) {
                $class = $unused['class'];
                $risk = $unused['risk_assessment'];
                
                echo "Class: {$class['full_name']}\n";
                echo "File: {$class['file']}:{$class['line']}\n";
                echo "Type: " . ucfirst($class['type']);
                if ($class['is_abstract']) echo " (Abstract)";
                echo "\n";
                echo "Risk Level: " . strtoupper($risk['level']) . " (Score: {$risk['score']})\n";
                echo "Registered in DI: " . ($unused['is_registered'] ? 'Yes' : 'No') . "\n";
                echo "Usage Patterns: " . count($unused['usage_patterns']) . "\n";
                
                if ($options['verbose']) {
                    echo "Risk Factors:\n";
                    foreach ($risk['reasons'] as $reason) {
                        echo "  - " . ucwords(str_replace('_', ' ', $reason)) . "\n";
                    }
                }
                
                echo str_repeat("-", 40) . "\n";
            }
            echo "\n";
        }
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
    
    // Validate src directory
    $src_dir = $plugin_dir . '/src';
    if (!is_dir($src_dir)) {
        echo "Error: Source directory not found: {$src_dir}\n";
        exit(1);
    }
    
    if (!$options['quiet']) {
        echo "Scanning PHP classes and analyzing dependencies...\n";
    }
    
    try {
        // Scan PHP classes
        if (!$options['quiet']) {
            echo "Scanning PHP class definitions...\n";
        }
        $classes = scan_php_classes($src_dir);
        
        // Analyze DI registrations
        if (!$options['quiet']) {
            echo "Analyzing DI container registrations...\n";
        }
        $di_registrations = analyze_di_registrations($plugin_dir);
        
        // Analyze usage patterns
        if (!$options['quiet']) {
            echo "Analyzing class usage patterns...\n";
        }
        $usage_patterns = analyze_class_usage($plugin_dir, $classes);
        
        // Cross-reference analysis
        if (!$options['quiet']) {
            echo "Performing cross-reference analysis...\n";
        }
        $cross_reference = cross_reference_analysis($classes, $di_registrations, $usage_patterns);
        
        // Generate statistics
        $stats = generate_analysis_statistics($classes, $di_registrations, $usage_patterns, $cross_reference);
        
        // Output results
        if ($options['json']) {
            output_json($classes, $di_registrations, $usage_patterns, $cross_reference, $stats);
        } else {
            output_human($classes, $di_registrations, $usage_patterns, $cross_reference, $stats, $options);
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