#!/usr/bin/env php
<?php
/**
 * MemberPress AI Assistant - Asset Inventory Script
 * 
 * This script scans all asset files in the plugin's assets directory and provides
 * comprehensive information about each file including size, modification date,
 * type, and organization by directory structure.
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
define('MPAI_SCRIPT_NAME', 'Asset Inventory Scanner');

// Get the plugin directory (script is in scripts/ subdirectory)
$plugin_dir = dirname(__DIR__);
$assets_dir = $plugin_dir . '/assets';

/**
 * Display help information
 */
function display_help() {
    echo "\n" . MPAI_SCRIPT_NAME . " v" . MPAI_SCRIPT_VERSION . "\n";
    echo str_repeat("=", 50) . "\n";
    echo "Scans and analyzes all asset files in the MemberPress AI Assistant plugin.\n\n";
    echo "USAGE:\n";
    echo "  php scripts/asset-inventory.php [OPTIONS]\n\n";
    echo "OPTIONS:\n";
    echo "  --help, -h        Show this help message\n";
    echo "  --json            Output results in JSON format\n";
    echo "  --verbose, -v     Enable verbose output with detailed file information\n";
    echo "  --quiet, -q       Minimal output (only summary statistics)\n";
    echo "  --size-only       Show only file size analysis\n";
    echo "  --by-type         Group output by file type\n\n";
    echo "EXAMPLES:\n";
    echo "  php scripts/asset-inventory.php\n";
    echo "  php scripts/asset-inventory.php --json\n";
    echo "  php scripts/asset-inventory.php --verbose\n";
    echo "  php scripts/asset-inventory.php --quiet --by-type\n\n";
    echo "OUTPUT:\n";
    echo "  - Total file count and size statistics\n";
    echo "  - Files organized by directory structure\n";
    echo "  - File details: path, size, modification date, type\n";
    echo "  - Summary statistics by file type\n\n";
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
        'size_only' => false,
        'by_type' => false
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
            case '--size-only':
                $options['size_only'] = true;
                break;
            case '--by-type':
                $options['by_type'] = true;
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
 * Format file size in human readable format
 */
function format_file_size($bytes) {
    if ($bytes === 0) return '0 B';
    
    $units = ['B', 'KB', 'MB', 'GB'];
    $factor = floor(log($bytes, 1024));
    
    return sprintf('%.2f %s', $bytes / pow(1024, $factor), $units[$factor]);
}

/**
 * Get file type based on extension
 */
function get_file_type($filename) {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    $type_map = [
        'css' => 'CSS',
        'js' => 'JavaScript',
        'ts' => 'TypeScript',
        'scss' => 'SCSS',
        'sass' => 'SASS',
        'less' => 'LESS',
        'json' => 'JSON',
        'map' => 'Source Map',
        'min.js' => 'Minified JavaScript',
        'min.css' => 'Minified CSS'
    ];
    
    // Check for minified files first
    if (strpos($filename, '.min.') !== false) {
        if ($extension === 'js') return 'Minified JavaScript';
        if ($extension === 'css') return 'Minified CSS';
    }
    
    return $type_map[$extension] ?? 'Other';
}

/**
 * Check if file is an ES6 module
 */
function is_es6_module($filepath) {
    if (pathinfo($filepath, PATHINFO_EXTENSION) !== 'js') {
        return false;
    }
    
    // Read first few lines to check for ES6 module syntax
    $handle = fopen($filepath, 'r');
    if (!$handle) return false;
    
    $content = '';
    $lines_read = 0;
    while (($line = fgets($handle)) !== false && $lines_read < 20) {
        $content .= $line;
        $lines_read++;
    }
    fclose($handle);
    
    // Check for ES6 module patterns
    return preg_match('/\b(import|export)\s+/', $content) === 1;
}

/**
 * Scan directory recursively for asset files
 */
function scan_assets_directory($directory, $base_path = '') {
    $files = [];
    
    if (!is_dir($directory)) {
        return $files;
    }
    
    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $relative_path = str_replace($base_path . '/', '', $file->getPathname());
                $relative_path = str_replace($base_path, '', $relative_path);
                $relative_path = ltrim($relative_path, '/\\');
                
                $file_info = [
                    'path' => $relative_path,
                    'full_path' => $file->getPathname(),
                    'size_bytes' => $file->getSize(),
                    'size_human' => format_file_size($file->getSize()),
                    'modified' => date('Y-m-d H:i:s', $file->getMTime()),
                    'modified_timestamp' => $file->getMTime(),
                    'extension' => strtolower($file->getExtension()),
                    'type' => get_file_type($file->getFilename()),
                    'directory' => dirname($relative_path),
                    'filename' => $file->getFilename(),
                    'is_es6_module' => is_es6_module($file->getPathname())
                ];
                
                $files[] = $file_info;
            }
        }
    } catch (Exception $e) {
        echo "Error scanning directory: " . $e->getMessage() . "\n";
        return [];
    }
    
    return $files;
}

/**
 * Generate summary statistics
 */
function generate_statistics($files) {
    $stats = [
        'total_files' => count($files),
        'total_size_bytes' => 0,
        'total_size_human' => '',
        'by_type' => [],
        'by_directory' => [],
        'largest_file' => null,
        'smallest_file' => null,
        'newest_file' => null,
        'oldest_file' => null,
        'es6_modules' => 0
    ];
    
    if (empty($files)) {
        return $stats;
    }
    
    foreach ($files as $file) {
        // Total size
        $stats['total_size_bytes'] += $file['size_bytes'];
        
        // By type
        $type = $file['type'];
        if (!isset($stats['by_type'][$type])) {
            $stats['by_type'][$type] = ['count' => 0, 'size' => 0];
        }
        $stats['by_type'][$type]['count']++;
        $stats['by_type'][$type]['size'] += $file['size_bytes'];
        
        // By directory
        $dir = $file['directory'];
        if (!isset($stats['by_directory'][$dir])) {
            $stats['by_directory'][$dir] = ['count' => 0, 'size' => 0];
        }
        $stats['by_directory'][$dir]['count']++;
        $stats['by_directory'][$dir]['size'] += $file['size_bytes'];
        
        // Largest file
        if ($stats['largest_file'] === null || $file['size_bytes'] > $stats['largest_file']['size_bytes']) {
            $stats['largest_file'] = $file;
        }
        
        // Smallest file
        if ($stats['smallest_file'] === null || $file['size_bytes'] < $stats['smallest_file']['size_bytes']) {
            $stats['smallest_file'] = $file;
        }
        
        // Newest file
        if ($stats['newest_file'] === null || $file['modified_timestamp'] > $stats['newest_file']['modified_timestamp']) {
            $stats['newest_file'] = $file;
        }
        
        // Oldest file
        if ($stats['oldest_file'] === null || $file['modified_timestamp'] < $stats['oldest_file']['modified_timestamp']) {
            $stats['oldest_file'] = $file;
        }
        
        // ES6 modules
        if ($file['is_es6_module']) {
            $stats['es6_modules']++;
        }
    }
    
    $stats['total_size_human'] = format_file_size($stats['total_size_bytes']);
    
    // Format by_type sizes
    foreach ($stats['by_type'] as $type => &$data) {
        $data['size_human'] = format_file_size($data['size']);
    }
    
    // Format by_directory sizes
    foreach ($stats['by_directory'] as $dir => &$data) {
        $data['size_human'] = format_file_size($data['size']);
    }
    
    return $stats;
}

/**
 * Output results in JSON format
 */
function output_json($files, $stats) {
    $output = [
        'scan_info' => [
            'timestamp' => date('Y-m-d H:i:s'),
            'script_version' => MPAI_SCRIPT_VERSION,
            'plugin' => 'MemberPress AI Assistant'
        ],
        'statistics' => $stats,
        'files' => $files
    ];
    
    echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
}

/**
 * Output results in human readable format
 */
function output_human($files, $stats, $options) {
    if (!$options['quiet']) {
        echo "\n" . MPAI_SCRIPT_NAME . " v" . MPAI_SCRIPT_VERSION . "\n";
        echo str_repeat("=", 60) . "\n";
        echo "Scan completed: " . date('Y-m-d H:i:s') . "\n\n";
    }
    
    // Summary statistics
    if (!$options['quiet']) {
        echo "SUMMARY STATISTICS\n";
        echo str_repeat("-", 30) . "\n";
        echo "Total Files: {$stats['total_files']}\n";
        echo "Total Size: {$stats['total_size_human']} ({$stats['total_size_bytes']} bytes)\n";
        echo "ES6 Modules: {$stats['es6_modules']}\n\n";
    }
    
    // Size-only output
    if ($options['size_only']) {
        echo "FILE SIZE ANALYSIS\n";
        echo str_repeat("-", 30) . "\n";
        if ($stats['largest_file']) {
            echo "Largest: {$stats['largest_file']['path']} ({$stats['largest_file']['size_human']})\n";
        }
        if ($stats['smallest_file']) {
            echo "Smallest: {$stats['smallest_file']['path']} ({$stats['smallest_file']['size_human']})\n";
        }
        echo "\n";
        return;
    }
    
    // By type statistics
    if ($options['by_type'] || !$options['quiet']) {
        echo "BY FILE TYPE\n";
        echo str_repeat("-", 30) . "\n";
        foreach ($stats['by_type'] as $type => $data) {
            echo sprintf("%-20s %3d files  %10s\n", $type . ':', $data['count'], $data['size_human']);
        }
        echo "\n";
    }
    
    // By directory statistics
    if (!$options['quiet']) {
        echo "BY DIRECTORY\n";
        echo str_repeat("-", 30) . "\n";
        foreach ($stats['by_directory'] as $dir => $data) {
            $display_dir = $dir === '.' ? 'assets/' : 'assets/' . $dir . '/';
            echo sprintf("%-30s %3d files  %10s\n", $display_dir, $data['count'], $data['size_human']);
        }
        echo "\n";
    }
    
    // File listing
    if ($options['verbose'] && !$options['quiet']) {
        echo "DETAILED FILE LISTING\n";
        echo str_repeat("-", 60) . "\n";
        
        if ($options['by_type']) {
            // Group by type
            $by_type = [];
            foreach ($files as $file) {
                $by_type[$file['type']][] = $file;
            }
            
            foreach ($by_type as $type => $type_files) {
                echo "\n{$type} Files:\n";
                foreach ($type_files as $file) {
                    $module_indicator = $file['is_es6_module'] ? ' [ES6]' : '';
                    echo sprintf("  %-40s %10s  %s%s\n", 
                        $file['path'], 
                        $file['size_human'], 
                        $file['modified'],
                        $module_indicator
                    );
                }
            }
        } else {
            // Group by directory
            $by_dir = [];
            foreach ($files as $file) {
                $by_dir[$file['directory']][] = $file;
            }
            
            foreach ($by_dir as $dir => $dir_files) {
                $display_dir = $dir === '.' ? 'assets/' : 'assets/' . $dir . '/';
                echo "\n{$display_dir}:\n";
                foreach ($dir_files as $file) {
                    $module_indicator = $file['is_es6_module'] ? ' [ES6]' : '';
                    echo sprintf("  %-30s %10s  %s  %s%s\n", 
                        $file['filename'], 
                        $file['size_human'], 
                        $file['type'],
                        $file['modified'],
                        $module_indicator
                    );
                }
            }
        }
        echo "\n";
    }
    
    // Additional statistics for verbose mode
    if ($options['verbose'] && !$options['quiet']) {
        echo "ADDITIONAL DETAILS\n";
        echo str_repeat("-", 30) . "\n";
        if ($stats['largest_file']) {
            echo "Largest File: {$stats['largest_file']['path']} ({$stats['largest_file']['size_human']})\n";
        }
        if ($stats['smallest_file']) {
            echo "Smallest File: {$stats['smallest_file']['path']} ({$stats['smallest_file']['size_human']})\n";
        }
        if ($stats['newest_file']) {
            echo "Newest File: {$stats['newest_file']['path']} ({$stats['newest_file']['modified']})\n";
        }
        if ($stats['oldest_file']) {
            echo "Oldest File: {$stats['oldest_file']['path']} ({$stats['oldest_file']['modified']})\n";
        }
        echo "\n";
    }
}

/**
 * Main execution function
 */
function main($argv) {
    global $plugin_dir, $assets_dir;
    
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
    
    // Validate assets directory
    if (!is_dir($assets_dir)) {
        echo "Error: Assets directory not found: {$assets_dir}\n";
        echo "Expected location: {$assets_dir}\n";
        exit(1);
    }
    
    // Scan CSS directory
    $css_files = [];
    $css_dir = $assets_dir . '/css';
    if (is_dir($css_dir)) {
        $css_files = scan_assets_directory($css_dir, $plugin_dir);
        // Prefix paths with assets/css/
        foreach ($css_files as &$file) {
            $file['path'] = 'assets/css/' . $file['path'];
            $file['directory'] = 'css' . ($file['directory'] !== '.' ? '/' . $file['directory'] : '');
        }
    }
    
    // Scan JS directory
    $js_files = [];
    $js_dir = $assets_dir . '/js';
    if (is_dir($js_dir)) {
        $js_files = scan_assets_directory($js_dir, $plugin_dir);
        // Prefix paths with assets/js/
        foreach ($js_files as &$file) {
            $file['path'] = 'assets/js/' . $file['path'];
            $file['directory'] = 'js' . ($file['directory'] !== '.' ? '/' . $file['directory'] : '');
        }
    }
    
    // Combine all files
    $all_files = array_merge($css_files, $js_files);
    
    // Sort files by path
    usort($all_files, function($a, $b) {
        return strcmp($a['path'], $b['path']);
    });
    
    // Generate statistics
    $stats = generate_statistics($all_files);
    
    // Output results
    if ($options['json']) {
        output_json($all_files, $stats);
    } else {
        output_human($all_files, $stats, $options);
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