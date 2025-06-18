#!/usr/bin/env php
<?php
/**
 * MemberPress AI Assistant - Template Analyzer Script
 * 
 * This script analyzes template files and their usage patterns throughout
 * the codebase, completing Phase 3 of the unused files analysis system.
 * It identifies template files, their inclusion patterns, and cross-references
 * them with PHP files to detect unused templates.
 * 
 * Part of Phase 3 analysis system for identifying unused template files.
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
define('MPAI_SCRIPT_NAME', 'Template Analyzer');

// Get the plugin directory (script is in scripts/ subdirectory)
$plugin_dir = dirname(__DIR__);

/**
 * Display help information
 */
function display_help() {
    echo "\n" . MPAI_SCRIPT_NAME . " v" . MPAI_SCRIPT_VERSION . "\n";
    echo str_repeat("=", 70) . "\n";
    echo "Analyzes template files and their usage patterns in the MemberPress AI Assistant plugin.\n\n";
    echo "USAGE:\n";
    echo "  php scripts/template-analyzer.php [OPTIONS]\n\n";
    echo "OPTIONS:\n";
    echo "  --help, -h        Show this help message\n";
    echo "  --json            Output results in JSON format\n";
    echo "  --verbose, -v     Enable verbose output with detailed information\n";
    echo "  --quiet, -q       Minimal output (only summary statistics)\n";
    echo "  --templates-only  Show only template file information\n";
    echo "  --usage-only      Show only template usage analysis\n";
    echo "  --unused-only     Show only unused template analysis\n\n";
    echo "EXAMPLES:\n";
    echo "  php scripts/template-analyzer.php\n";
    echo "  php scripts/template-analyzer.php --json\n";
    echo "  php scripts/template-analyzer.php --verbose\n";
    echo "  php scripts/template-analyzer.php --unused-only\n\n";
}

/**
 * Template Analyzer Class
 * 
 * Comprehensive analysis system for template files and their usage patterns.
 */
class TemplateAnalyzer {
    
    private $plugin_dir;
    private $templates = [];
    private $template_usage = [];
    private $php_files = [];
    private $options = [];
    
    // Template inclusion patterns to search for
    private $inclusion_patterns = [
        'include\s*\(\s*[\'"]([^\'"]*/templates/[^\'"]*)[\'"]',
        'include_once\s*\(\s*[\'"]([^\'"]*/templates/[^\'"]*)[\'"]',
        'require\s*\(\s*[\'"]([^\'"]*/templates/[^\'"]*)[\'"]',
        'require_once\s*\(\s*[\'"]([^\'"]*/templates/[^\'"]*)[\'"]',
        'get_template_part\s*\(\s*[\'"]([^\'"]*)[\'"]',
        'load_template\s*\(\s*[\'"]([^\'"]*/templates/[^\'"]*)[\'"]',
        'plugin_dir_path.*templates\/([^\'"\s)]+)',
        '__DIR__.*[\'"]/?templates\/([^\'"\s)]+)[\'"]',
        '\$this->.*template.*[\'"]([^\'"]*/templates/[^\'"]*)[\'"]',
        'file_get_contents\s*\(\s*[\'"]([^\'"]*/templates/[^\'"]*)[\'"]'
    ];
    
    // Dynamic template loading patterns
    private $dynamic_patterns = [
        '\$template\s*=\s*[\'"]([^\'"]+)[\'"]',
        'template.*\.\s*[\'"]php[\'"]',
        'sprintf.*template',
        'str_replace.*template',
        'templates\/.*\$'
    ];
    
    public function __construct($plugin_dir, $options = []) {
        $this->plugin_dir = $plugin_dir;
        $this->options = $options;
    }
    
    /**
     * Run the complete template analysis
     */
    public function analyze() {
        if (!$this->options['quiet']) {
            echo "\n" . MPAI_SCRIPT_NAME . " v" . MPAI_SCRIPT_VERSION . "\n";
            echo str_repeat("=", 70) . "\n";
            echo "Analyzing template files and usage patterns...\n\n";
        }
        
        // Phase 1: Inventory all template files
        $this->inventoryTemplates();
        
        // Phase 2: Scan PHP files for template usage
        $this->scanPhpFiles();
        
        // Phase 3: Analyze template usage patterns
        $this->analyzeTemplateUsage();
        
        // Phase 4: Generate analysis results
        return $this->generateResults();
    }
    
    /**
     * Inventory all template files in the templates directory
     */
    private function inventoryTemplates() {
        $templates_dir = $this->plugin_dir . '/templates';
        
        if (!is_dir($templates_dir)) {
            if (!$this->options['quiet']) {
                echo "Warning: Templates directory not found at: $templates_dir\n";
            }
            return;
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($templates_dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $relative_path = str_replace($this->plugin_dir . '/', '', $file->getPathname());
                $template_name = basename($file->getFilename(), '.php');
                
                $this->templates[$relative_path] = [
                    'path' => $file->getPathname(),
                    'relative_path' => $relative_path,
                    'name' => $template_name,
                    'size' => $file->getSize(),
                    'modified' => $file->getMTime(),
                    'extension' => $file->getExtension(),
                    'used' => false,
                    'usage_patterns' => [],
                    'included_by' => [],
                    'risk_factors' => []
                ];
            }
        }
        
        if (!$this->options['quiet']) {
            echo "Found " . count($this->templates) . " template files\n";
        }
    }
    
    /**
     * Scan all PHP files for template usage patterns
     */
    private function scanPhpFiles() {
        $src_dirs = [
            $this->plugin_dir . '/src',
            $this->plugin_dir . '/includes',
            $this->plugin_dir,
        ];
        
        foreach ($src_dirs as $dir) {
            if (is_dir($dir)) {
                $this->scanDirectory($dir);
            }
        }
        
        if (!$this->options['quiet']) {
            echo "Scanned " . count($this->php_files) . " PHP files for template usage\n";
        }
    }
    
    /**
     * Scan a directory for PHP files and analyze template usage
     */
    private function scanDirectory($dir) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $this->analyzePhpFile($file->getPathname());
            }
        }
    }
    
    /**
     * Analyze a single PHP file for template usage patterns
     */
    private function analyzePhpFile($file_path) {
        $content = file_get_contents($file_path);
        if ($content === false) {
            return;
        }
        
        $relative_path = str_replace($this->plugin_dir . '/', '', $file_path);
        
        // Skip template files themselves
        if (strpos($relative_path, 'templates/') === 0) {
            return;
        }
        
        $file_data = [
            'path' => $file_path,
            'relative_path' => $relative_path,
            'template_references' => [],
            'dynamic_patterns' => [],
            'template_includes' => []
        ];
        
        // Search for direct template inclusion patterns
        foreach ($this->inclusion_patterns as $pattern) {
            if (preg_match_all('/' . $pattern . '/i', $content, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[1] as $match) {
                    $template_ref = $match[0];
                    $offset = $match[1];
                    
                    // Normalize template path
                    $normalized_path = $this->normalizeTemplatePath($template_ref);
                    
                    $file_data['template_includes'][] = [
                        'pattern' => $pattern,
                        'template' => $template_ref,
                        'normalized' => $normalized_path,
                        'offset' => $offset,
                        'line' => substr_count(substr($content, 0, $offset), "\n") + 1
                    ];
                    
                    // Mark template as used
                    if (isset($this->templates[$normalized_path])) {
                        $this->templates[$normalized_path]['used'] = true;
                        $this->templates[$normalized_path]['included_by'][] = $relative_path;
                        $this->templates[$normalized_path]['usage_patterns'][] = [
                            'type' => 'direct_include',
                            'file' => $relative_path,
                            'pattern' => $pattern,
                            'line' => substr_count(substr($content, 0, $offset), "\n") + 1
                        ];
                    }
                }
            }
        }
        
        // Search for dynamic template loading patterns
        foreach ($this->dynamic_patterns as $pattern) {
            if (preg_match_all('/' . $pattern . '/i', $content, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $offset = $match[1];
                    $file_data['dynamic_patterns'][] = [
                        'pattern' => $pattern,
                        'match' => $match[0],
                        'offset' => $offset,
                        'line' => substr_count(substr($content, 0, $offset), "\n") + 1
                    ];
                }
            }
        }
        
        // Check for any mention of template files by name
        foreach ($this->templates as $template_path => $template_data) {
            $template_name = $template_data['name'];
            if (stripos($content, $template_name) !== false) {
                if (!isset($this->templates[$template_path]['usage_patterns'])) {
                    $this->templates[$template_path]['usage_patterns'] = [];
                }
                
                $this->templates[$template_path]['usage_patterns'][] = [
                    'type' => 'name_reference',
                    'file' => $relative_path,
                    'template_name' => $template_name
                ];
            }
        }
        
        if (!empty($file_data['template_includes']) || !empty($file_data['dynamic_patterns'])) {
            $this->php_files[$relative_path] = $file_data;
        }
    }
    
    /**
     * Normalize template path for consistent comparison
     */
    private function normalizeTemplatePath($path) {
        // Remove leading slashes and normalize
        $path = ltrim($path, '/');
        
        // If path doesn't start with templates/, add it
        if (strpos($path, 'templates/') !== 0) {
            $path = 'templates/' . $path;
        }
        
        // Add .php extension if missing
        if (!str_ends_with($path, '.php')) {
            $path .= '.php';
        }
        
        return $path;
    }
    
    /**
     * Analyze template usage patterns and calculate risk factors
     */
    private function analyzeTemplateUsage() {
        foreach ($this->templates as $path => &$template) {
            $this->calculateTemplateRisk($template);
        }
        
        if (!$this->options['quiet']) {
            echo "Completed template usage analysis\n";
        }
    }
    
    /**
     * Calculate risk factors for a template file
     */
    private function calculateTemplateRisk(&$template) {
        $risk_score = 0;
        $risk_factors = [];
        
        // Factor 1: Not used in any PHP files (highest risk)
        if (!$template['used']) {
            $risk_score += 50;
            $risk_factors[] = 'No usage patterns found';
        }
        
        // Factor 2: No direct includes found
        $direct_includes = array_filter($template['usage_patterns'], function($p) {
            return $p['type'] === 'direct_include';
        });
        if (empty($direct_includes)) {
            $risk_score += 30;
            $risk_factors[] = 'No direct include statements found';
        }
        
        // Factor 3: Only name references (potentially unused)
        $name_references = array_filter($template['usage_patterns'], function($p) {
            return $p['type'] === 'name_reference';
        });
        if (!empty($name_references) && empty($direct_includes)) {
            $risk_score += 20;
            $risk_factors[] = 'Only found as name references';
        }
        
        // Factor 4: File age (older files might be unused)
        $age_days = (time() - $template['modified']) / (24 * 60 * 60);
        if ($age_days > 90) {
            $risk_score += 15;
            $risk_factors[] = 'File older than 90 days';
        } elseif ($age_days > 30) {
            $risk_score += 10;
            $risk_factors[] = 'File older than 30 days';
        }
        
        // Factor 5: Small file size (might be stub/unused)
        if ($template['size'] < 500) {
            $risk_score += 10;
            $risk_factors[] = 'Very small file size (<500 bytes)';
        } elseif ($template['size'] < 1000) {
            $risk_score += 5;
            $risk_factors[] = 'Small file size (<1KB)';
        }
        
        // Factor 6: Naming patterns suggesting test/example files
        $name = strtolower($template['name']);
        if (strpos($name, 'test') !== false || 
            strpos($name, 'example') !== false || 
            strpos($name, 'sample') !== false ||
            strpos($name, 'demo') !== false) {
            $risk_score += 25;
            $risk_factors[] = 'Naming suggests test/example file';
        }
        
        // Determine risk level
        if ($risk_score >= 70) {
            $risk_level = 'high';
        } elseif ($risk_score >= 40) {
            $risk_level = 'medium';
        } else {
            $risk_level = 'low';
        }
        
        $template['risk_score'] = $risk_score;
        $template['risk_level'] = $risk_level;
        $template['risk_factors'] = $risk_factors;
    }
    
    /**
     * Generate analysis results
     */
    private function generateResults() {
        $stats = $this->calculateStats();
        
        $results = [
            'analysis_info' => [
                'script' => MPAI_SCRIPT_NAME,
                'version' => MPAI_SCRIPT_VERSION,
                'timestamp' => date('Y-m-d H:i:s'),
                'plugin_dir' => $this->plugin_dir
            ],
            'summary' => $stats,
            'templates' => $this->templates,
            'template_usage' => $this->php_files,
            'unused_templates' => $this->getUnusedTemplates(),
            'risk_assessment' => $this->getRiskAssessment()
        ];
        
        if ($this->options['json']) {
            echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } else {
            $this->displayResults($results);
        }
        
        return $results;
    }
    
    /**
     * Calculate summary statistics
     */
    private function calculateStats() {
        $total_templates = count($this->templates);
        $used_templates = count(array_filter($this->templates, function($t) { return $t['used']; }));
        $unused_templates = $total_templates - $used_templates;
        
        $total_size = array_sum(array_column($this->templates, 'size'));
        $unused_size = array_sum(array_column(
            array_filter($this->templates, function($t) { return !$t['used']; }), 
            'size'
        ));
        
        $risk_levels = array_count_values(array_column($this->templates, 'risk_level'));
        
        return [
            'total_templates' => $total_templates,
            'used_templates' => $used_templates,
            'unused_templates' => $unused_templates,
            'usage_percentage' => $total_templates > 0 ? round(($used_templates / $total_templates) * 100, 2) : 0,
            'total_size_bytes' => $total_size,
            'unused_size_bytes' => $unused_size,
            'potential_savings' => $this->formatBytes($unused_size),
            'php_files_scanned' => count($this->php_files),
            'risk_levels' => $risk_levels
        ];
    }
    
    /**
     * Get templates marked as unused
     */
    private function getUnusedTemplates() {
        return array_filter($this->templates, function($template) {
            return !$template['used'];
        });
    }
    
    /**
     * Get risk assessment breakdown
     */
    private function getRiskAssessment() {
        $risk_assessment = [
            'high_risk' => [],
            'medium_risk' => [],
            'low_risk' => []
        ];
        
        foreach ($this->templates as $path => $template) {
            $risk_assessment[$template['risk_level'] . '_risk'][$path] = [
                'name' => $template['name'],
                'score' => $template['risk_score'],
                'factors' => $template['risk_factors'],
                'size' => $template['size'],
                'used' => $template['used']
            ];
        }
        
        return $risk_assessment;
    }
    
    /**
     * Display formatted results
     */
    private function displayResults($results) {
        $stats = $results['summary'];
        
        if (!$this->options['templates-only'] && !$this->options['usage-only'] && !$this->options['unused-only']) {
            echo "\n" . str_repeat("=", 70) . "\n";
            echo "TEMPLATE ANALYSIS SUMMARY\n";
            echo str_repeat("=", 70) . "\n";
            echo sprintf("Total Templates Found: %d\n", $stats['total_templates']);
            echo sprintf("Used Templates: %d (%.1f%%)\n", $stats['used_templates'], $stats['usage_percentage']);
            echo sprintf("Unused Templates: %d\n", $stats['unused_templates']);
            echo sprintf("Total Size: %s\n", $this->formatBytes($stats['total_size_bytes']));
            echo sprintf("Potential Savings: %s\n", $stats['potential_savings']);
            echo sprintf("PHP Files Scanned: %d\n", $stats['php_files_scanned']);
            echo "\nRisk Assessment:\n";
            echo sprintf("  High Risk: %d templates\n", $stats['risk_levels']['high'] ?? 0);
            echo sprintf("  Medium Risk: %d templates\n", $stats['risk_levels']['medium'] ?? 0);
            echo sprintf("  Low Risk: %d templates\n", $stats['risk_levels']['low'] ?? 0);
            echo "\n";
        }
        
        if ($this->options['templates-only'] || (!$this->options['usage-only'] && !$this->options['unused-only'])) {
            $this->displayTemplateInventory($results['templates']);
        }
        
        if ($this->options['usage-only'] || (!$this->options['templates-only'] && !$this->options['unused-only'])) {
            $this->displayTemplateUsage($results['template_usage']);
        }
        
        if ($this->options['unused-only'] || (!$this->options['templates-only'] && !$this->options['usage-only'])) {
            $this->displayUnusedTemplates($results['unused_templates'], $results['risk_assessment']);
        }
    }
    
    /**
     * Display template inventory
     */
    private function displayTemplateInventory($templates) {
        echo str_repeat("-", 70) . "\n";
        echo "TEMPLATE INVENTORY\n";
        echo str_repeat("-", 70) . "\n";
        
        foreach ($templates as $path => $template) {
            echo sprintf("📄 %s\n", $template['name']);
            echo sprintf("   Path: %s\n", $path);
            echo sprintf("   Size: %s | Modified: %s\n", 
                $this->formatBytes($template['size']), 
                date('Y-m-d H:i:s', $template['modified'])
            );
            echo sprintf("   Status: %s | Risk: %s (%d)\n", 
                $template['used'] ? '✅ Used' : '❌ Unused',
                strtoupper($template['risk_level']),
                $template['risk_score']
            );
            
            if (!empty($template['included_by'])) {
                echo "   Included by: " . implode(', ', $template['included_by']) . "\n";
            }
            
            if (!empty($template['risk_factors']) && $this->options['verbose']) {
                echo "   Risk factors: " . implode(', ', $template['risk_factors']) . "\n";
            }
            echo "\n";
        }
    }
    
    /**
     * Display template usage patterns
     */
    private function displayTemplateUsage($usage_data) {
        echo str_repeat("-", 70) . "\n";
        echo "TEMPLATE USAGE PATTERNS\n";
        echo str_repeat("-", 70) . "\n";
        
        foreach ($usage_data as $file => $data) {
            echo sprintf("📝 %s\n", $file);
            
            if (!empty($data['template_includes'])) {
                echo "   Template Includes:\n";
                foreach ($data['template_includes'] as $include) {
                    echo sprintf("     • %s (line %d)\n", $include['template'], $include['line']);
                }
            }
            
            if (!empty($data['dynamic_patterns']) && $this->options['verbose']) {
                echo "   Dynamic Patterns:\n";
                foreach ($data['dynamic_patterns'] as $pattern) {
                    echo sprintf("     • %s (line %d)\n", $pattern['match'], $pattern['line']);
                }
            }
            echo "\n";
        }
    }
    
    /**
     * Display unused templates analysis
     */
    private function displayUnusedTemplates($unused_templates, $risk_assessment) {
        echo str_repeat("-", 70) . "\n";
        echo "UNUSED TEMPLATES ANALYSIS\n";
        echo str_repeat("-", 70) . "\n";
        
        if (empty($unused_templates)) {
            echo "✅ No unused templates found!\n\n";
            return;
        }
        
        foreach (['high_risk', 'medium_risk', 'low_risk'] as $risk_level) {
            $templates = $risk_assessment[$risk_level];
            if (empty($templates)) continue;
            
            $risk_name = str_replace('_', ' ', strtoupper($risk_level));
            echo "\n{$risk_name} TEMPLATES (" . count($templates) . "):\n";
            echo str_repeat("-", 30) . "\n";
            
            foreach ($templates as $path => $template) {
                $icon = $risk_level === 'high_risk' ? '🔴' : ($risk_level === 'medium_risk' ? '🟡' : '🟢');
                echo sprintf("%s %s (Score: %d)\n", $icon, $template['name'], $template['score']);
                echo sprintf("   Size: %s | Used: %s\n", 
                    $this->formatBytes($template['size']),
                    $template['used'] ? 'Yes' : 'No'
                );
                
                if (!empty($template['factors'])) {
                    echo "   Risk Factors: " . implode(', ', $template['factors']) . "\n";
                }
                echo "\n";
            }
        }
        
        // Cleanup recommendations
        echo str_repeat("-", 70) . "\n";
        echo "CLEANUP RECOMMENDATIONS\n";
        echo str_repeat("-", 70) . "\n";
        
        $high_risk = $risk_assessment['high_risk'];
        if (!empty($high_risk)) {
            echo "🔴 HIGH PRIORITY - Safe to remove:\n";
            foreach ($high_risk as $path => $template) {
                echo sprintf("   • %s (%s)\n", $template['name'], $this->formatBytes($template['size']));
            }
            echo "\n";
        }
        
        $medium_risk = $risk_assessment['medium_risk'];
        if (!empty($medium_risk)) {
            echo "🟡 REVIEW REQUIRED - Verify before removal:\n";
            foreach ($medium_risk as $path => $template) {
                echo sprintf("   • %s (%s)\n", $template['name'], $this->formatBytes($template['size']));
            }
            echo "\n";
        }
    }
    
    /**
     * Format bytes into human readable format
     */
    private function formatBytes($bytes) {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' B';
        }
    }
}

// Command line argument parsing
$options = [
    'help' => false,
    'json' => false,
    'verbose' => false,
    'quiet' => false,
    'templates-only' => false,
    'usage-only' => false,
    'unused-only' => false
];

foreach ($argv as $arg) {
    switch ($arg) {
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
        case '--templates-only':
            $options['templates-only'] = true;
            break;
        case '--usage-only':
            $options['usage-only'] = true;
            break;
        case '--unused-only':
            $options['unused-only'] = true;
            break;
    }
}

// Show help if requested
if ($options['help']) {
    display_help();
    exit(0);
}

// Run the analysis
try {
    $analyzer = new TemplateAnalyzer($plugin_dir, $options);
    $results = $analyzer->analyze();
    
    // Save JSON results for cross-reference analysis
    if (!$options['json']) {
        $json_file = $plugin_dir . '/scripts/template-analysis.json';
        file_put_contents($json_file, json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        
        if (!$options['quiet']) {
            echo "\n" . str_repeat("=", 70) . "\n";
            echo "✅ Analysis complete! Results saved to: scripts/template-analysis.json\n";
            echo "Use --json flag to output JSON directly to stdout\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}