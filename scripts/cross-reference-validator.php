#!/usr/bin/env php
<?php
/**
 * MemberPress AI Assistant - Cross-Reference Validator Script
 * 
 * This script completes Phase 4 of the unused files analysis by performing
 * comprehensive cross-reference validation between assets, classes, templates,
 * and dynamic loading patterns. It addresses the gaps in the previous analysis
 * to provide accurate unused file detection.
 * 
 * Part of Phase 4 analysis system for validating file usage patterns.
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
define('MPAI_SCRIPT_NAME', 'Cross-Reference Validator');

// Get the plugin directory (script is in scripts/ subdirectory)
$plugin_dir = dirname(__DIR__);

/**
 * Display help information
 */
function display_help() {
    echo "\n" . MPAI_SCRIPT_NAME . " v" . MPAI_SCRIPT_VERSION . "\n";
    echo str_repeat("=", 70) . "\n";
    echo "Performs comprehensive cross-reference validation for the MemberPress AI Assistant plugin.\n\n";
    echo "USAGE:\n";
    echo "  php scripts/cross-reference-validator.php [OPTIONS]\n\n";
    echo "OPTIONS:\n";
    echo "  --help, -h        Show this help message\n";
    echo "  --json            Output results in JSON format\n";
    echo "  --verbose, -v     Enable verbose output with detailed information\n";
    echo "  --quiet, -q       Minimal output (only summary statistics)\n";
    echo "  --fix-patterns    Attempt to fix registration pattern detection\n";
    echo "  --hooks-only      Focus only on WordPress hook analysis\n";
    echo "  --dynamic-only    Focus only on dynamic loading analysis\n\n";
    echo "EXAMPLES:\n";
    echo "  php scripts/cross-reference-validator.php\n";
    echo "  php scripts/cross-reference-validator.php --json\n";
    echo "  php scripts/cross-reference-validator.php --verbose --fix-patterns\n\n";
}

/**
 * Cross-Reference Validator Class
 * 
 * Comprehensive validation system for all file usage patterns.
 */
class CrossReferenceValidator {
    
    private $plugin_dir;
    private $options = [];
    
    // Analysis data
    private $assets = [];
    private $classes = [];
    private $templates = [];
    private $wordpress_hooks = [];
    private $dynamic_patterns = [];
    private $string_references = [];
    private $conditional_loading = [];
    
    // Validation results
    private $validation_results = [];
    private $corrected_usage = [];
    private $integration_map = [];
    
    public function __construct($plugin_dir, $options = []) {
        $this->plugin_dir = $plugin_dir;
        $this->options = $options;
    }
    
    /**
     * Run the complete cross-reference validation
     */
    public function validate() {
        if (!$this->options['quiet']) {
            echo "\n" . MPAI_SCRIPT_NAME . " v" . MPAI_SCRIPT_VERSION . "\n";
            echo str_repeat("=", 70) . "\n";
            echo "Performing comprehensive cross-reference validation...\n\n";
        }
        
        // Phase 1: Load existing analysis data
        $this->loadAnalysisData();
        
        // Phase 2: WordPress hooks analysis
        if (!$this->options['dynamic-only']) {
            $this->analyzeWordPressHooks();
        }
        
        // Phase 3: Dynamic loading patterns analysis
        if (!$this->options['hooks-only']) {
            $this->analyzeDynamicLoading();
        }
        
        // Phase 4: String-based references analysis
        $this->analyzeStringReferences();
        
        // Phase 5: Conditional loading analysis
        $this->analyzeConditionalLoading();
        
        // Phase 6: Cross-reference integration
        $this->performCrossReferenceIntegration();
        
        // Phase 7: Fix registration pattern detection
        if ($this->options['fix-patterns']) {
            $this->fixRegistrationPatterns();
        }
        
        // Phase 8: Generate validation results
        return $this->generateValidationResults();
    }
    
    /**
     * Load existing analysis data from previous phases
     */
    private function loadAnalysisData() {
        if (!$this->options['quiet']) {
            echo "Loading analysis data from previous phases...\n";
        }
        
        // Load asset analysis data
        $asset_file = $this->plugin_dir . '/scripts/asset-inventory.json';
        if (file_exists($asset_file)) {
            $asset_data = json_decode(file_get_contents($asset_file), true);
            if ($asset_data && isset($asset_data['assets'])) {
                $this->assets = $asset_data['assets'];
            }
        }
        
        // Load asset registration data
        $registration_file = $this->plugin_dir . '/scripts/asset-registration.json';
        if (file_exists($registration_file)) {
            $registration_data = json_decode(file_get_contents($registration_file), true);
            if ($registration_data) {
                $this->wordpress_hooks = $registration_data['registration_patterns'] ?? [];
            }
        }
        
        // Load PHP class analysis data
        $class_file = $this->plugin_dir . '/scripts/php-class-analysis.json';
        if (file_exists($class_file)) {
            $class_data = json_decode(file_get_contents($class_file), true);
            if ($class_data && isset($class_data['classes'])) {
                $this->classes = $class_data['classes'];
            }
        }
        
        // Load template analysis data
        $template_file = $this->plugin_dir . '/scripts/template-analysis.json';
        if (file_exists($template_file)) {
            $template_data = json_decode(file_get_contents($template_file), true);
            if ($template_data && isset($template_data['templates'])) {
                $this->templates = $template_data['templates'];
            }
        }
        
        if (!$this->options['quiet']) {
            echo sprintf("Loaded %d assets, %d classes, %d templates\n", 
                count($this->assets), count($this->classes), count($this->templates));
        }
    }
    
    /**
     * Analyze WordPress hooks for asset and template loading
     */
    private function analyzeWordPressHooks() {
        if (!$this->options['quiet']) {
            echo "Analyzing WordPress hooks and action patterns...\n";
        }
        
        $hook_patterns = [
            // WordPress asset hooks
            'wp_enqueue_scripts',
            'admin_enqueue_scripts',
            'wp_enqueue_style',
            'wp_enqueue_script',
            'wp_register_style',
            'wp_register_script',
            
            // Template loading hooks
            'template_redirect',
            'template_include',
            'get_template_part',
            'load_template',
            
            // WordPress admin hooks
            'admin_init',
            'admin_menu',
            'admin_head',
            'admin_footer',
            
            // AJAX hooks
            'wp_ajax_',
            'wp_ajax_nopriv_',
            
            // Plugin hooks
            'init',
            'plugins_loaded'
        ];
        
        $this->scanForHookPatterns($hook_patterns);
    }
    
    /**
     * Scan for WordPress hook patterns in PHP files
     */
    private function scanForHookPatterns($patterns) {
        $dirs_to_scan = [
            $this->plugin_dir . '/src',
            $this->plugin_dir . '/includes',
            $this->plugin_dir
        ];
        
        foreach ($dirs_to_scan as $dir) {
            if (is_dir($dir)) {
                $this->scanDirectoryForHooks($dir, $patterns);
            }
        }
    }
    
    /**
     * Scan directory for hook patterns
     */
    private function scanDirectoryForHooks($dir, $patterns) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $this->analyzeFileForHooks($file->getPathname(), $patterns);
            }
        }
    }
    
    /**
     * Analyze a single file for hook patterns
     */
    private function analyzeFileForHooks($file_path, $patterns) {
        $content = file_get_contents($file_path);
        if ($content === false) {
            return;
        }
        
        $relative_path = str_replace($this->plugin_dir . '/', '', $file_path);
        
        foreach ($patterns as $pattern) {
            // Look for add_action and add_filter calls
            $hook_regex = '/(?:add_action|add_filter)\s*\(\s*[\'"]' . preg_quote($pattern, '/') . '[\'"]?\s*,\s*[\'"]?([^\'",\s)]+)/i';
            if (preg_match_all($hook_regex, $content, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[1] as $match) {
                    $callback = $match[0];
                    $offset = $match[1];
                    $line = substr_count(substr($content, 0, $offset), "\n") + 1;
                    
                    if (!isset($this->wordpress_hooks[$relative_path])) {
                        $this->wordpress_hooks[$relative_path] = [];
                    }
                    
                    $this->wordpress_hooks[$relative_path][] = [
                        'hook' => $pattern,
                        'callback' => $callback,
                        'line' => $line,
                        'type' => 'hook_registration'
                    ];
                }
            }
            
            // Look for direct hook calls
            $direct_regex = '/' . preg_quote($pattern, '/') . '\s*\(/i';
            if (preg_match_all($direct_regex, $content, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $offset = $match[1];
                    $line = substr_count(substr($content, 0, $offset), "\n") + 1;
                    
                    if (!isset($this->wordpress_hooks[$relative_path])) {
                        $this->wordpress_hooks[$relative_path] = [];
                    }
                    
                    $this->wordpress_hooks[$relative_path][] = [
                        'hook' => $pattern,
                        'usage' => 'direct_call',
                        'line' => $line,
                        'type' => 'hook_usage'
                    ];
                }
            }
        }
    }
    
    /**
     * Analyze dynamic loading patterns
     */
    private function analyzeDynamicLoading() {
        if (!$this->options['quiet']) {
            echo "Analyzing dynamic loading patterns...\n";
        }
        
        $dynamic_patterns = [
            // File path building
            'plugin_dir_path\s*\(\s*__FILE__\s*\)\s*\.\s*[\'"]([^\'"]+)[\'"]',
            '__DIR__\s*\.\s*[\'"]([^\'"]+)[\'"]',
            'ABSPATH\s*\.\s*[\'"]([^\'"]+)[\'"]',
            
            // Variable-based loading
            '\$[a-zA-Z_]\w*\s*\.\s*[\'"]\.php[\'"]',
            'sprintf\s*\(\s*[\'"][^\'"]*%s[^\'"]*[\'"]',
            
            // Dynamic class loading
            'class_exists\s*\(\s*[\'"]([^\'"]+)[\'"]',
            'new\s+\$[a-zA-Z_]\w*',
            'call_user_func',
            
            // WordPress loading functions
            'wp_enqueue_script\s*\([^)]+\)',
            'wp_enqueue_style\s*\([^)]+\)',
            'include\s*\(\s*[^)]+\)',
            'require\s*\(\s*[^)]+\)',
            
            // AJAX and template loading
            'wp_ajax_[a-zA-Z_]+',
            'template_redirect',
            'get_template_part'
        ];
        
        $this->scanForDynamicPatterns($dynamic_patterns);
    }
    
    /**
     * Scan for dynamic loading patterns
     */
    private function scanForDynamicPatterns($patterns) {
        $dirs_to_scan = [
            $this->plugin_dir . '/src',
            $this->plugin_dir . '/includes',
            $this->plugin_dir . '/templates',
            $this->plugin_dir
        ];
        
        foreach ($dirs_to_scan as $dir) {
            if (is_dir($dir)) {
                $this->scanDirectoryForDynamicPatterns($dir, $patterns);
            }
        }
    }
    
    /**
     * Scan directory for dynamic patterns
     */
    private function scanDirectoryForDynamicPatterns($dir, $patterns) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $this->analyzeFileForDynamicPatterns($file->getPathname(), $patterns);
            }
        }
    }
    
    /**
     * Analyze file for dynamic patterns
     */
    private function analyzeFileForDynamicPatterns($file_path, $patterns) {
        $content = file_get_contents($file_path);
        if ($content === false) {
            return;
        }
        
        $relative_path = str_replace($this->plugin_dir . '/', '', $file_path);
        
        foreach ($patterns as $pattern) {
            if (preg_match_all('/' . $pattern . '/i', $content, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $matched_text = $match[0];
                    $offset = $match[1];
                    $line = substr_count(substr($content, 0, $offset), "\n") + 1;
                    
                    if (!isset($this->dynamic_patterns[$relative_path])) {
                        $this->dynamic_patterns[$relative_path] = [];
                    }
                    
                    $this->dynamic_patterns[$relative_path][] = [
                        'pattern' => $pattern,
                        'match' => $matched_text,
                        'line' => $line,
                        'context' => $this->extractContext($content, $offset)
                    ];
                }
            }
        }
    }
    
    /**
     * Extract context around a match
     */
    private function extractContext($content, $offset, $context_length = 100) {
        $start = max(0, $offset - $context_length);
        $end = min(strlen($content), $offset + $context_length);
        return substr($content, $start, $end - $start);
    }
    
    /**
     * Analyze string-based references
     */
    private function analyzeStringReferences() {
        if (!$this->options['quiet']) {
            echo "Analyzing string-based file references...\n";
        }
        
        // Build list of all asset, class, and template names
        $search_terms = [];
        
        // Asset names
        foreach ($this->assets as $asset_path => $asset_data) {
            $filename = basename($asset_path, '.css');
            $filename = basename($filename, '.js');
            $search_terms[] = $filename;
            $search_terms[] = $asset_path;
        }
        
        // Class names
        foreach ($this->classes as $class_path => $class_data) {
            if (isset($class_data['class_name'])) {
                $search_terms[] = $class_data['class_name'];
            }
        }
        
        // Template names
        foreach ($this->templates as $template_path => $template_data) {
            $template_name = basename($template_path, '.php');
            $search_terms[] = $template_name;
            $search_terms[] = $template_path;
        }
        
        // Search for these terms in all PHP files
        $this->searchForStringReferences($search_terms);
    }
    
    /**
     * Search for string references in files
     */
    private function searchForStringReferences($search_terms) {
        $dirs_to_scan = [
            $this->plugin_dir . '/src',
            $this->plugin_dir . '/includes',
            $this->plugin_dir
        ];
        
        foreach ($dirs_to_scan as $dir) {
            if (is_dir($dir)) {
                $this->scanDirectoryForStringReferences($dir, $search_terms);
            }
        }
    }
    
    /**
     * Scan directory for string references
     */
    private function scanDirectoryForStringReferences($dir, $search_terms) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $this->analyzeFileForStringReferences($file->getPathname(), $search_terms);
            }
        }
    }
    
    /**
     * Analyze file for string references
     */
    private function analyzeFileForStringReferences($file_path, $search_terms) {
        $content = file_get_contents($file_path);
        if ($content === false) {
            return;
        }
        
        $relative_path = str_replace($this->plugin_dir . '/', '', $file_path);
        
        foreach ($search_terms as $term) {
            // Skip very short terms to avoid false positives
            if (strlen($term) < 4) {
                continue;
            }
            
            // Search for the term in strings
            $pattern = '/[\'"]([^\'"]*' . preg_quote($term, '/') . '[^\'"]*)[\'"]|' .
                      '\/\*[^*]*' . preg_quote($term, '/') . '[^*]*\*\/|' .
                      '\/\/.*' . preg_quote($term, '/') . '/i';
            
            if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $matched_text = $match[0];
                    $offset = $match[1];
                    $line = substr_count(substr($content, 0, $offset), "\n") + 1;
                    
                    if (!isset($this->string_references[$relative_path])) {
                        $this->string_references[$relative_path] = [];
                    }
                    
                    $this->string_references[$relative_path][] = [
                        'term' => $term,
                        'match' => $matched_text,
                        'line' => $line,
                        'type' => $this->determineReferenceType($matched_text)
                    ];
                }
            }
        }
    }
    
    /**
     * Determine the type of string reference
     */
    private function determineReferenceType($matched_text) {
        if (strpos($matched_text, '//') === 0) {
            return 'comment';
        } elseif (strpos($matched_text, '/*') === 0) {
            return 'block_comment';
        } elseif (strpos($matched_text, '"') === 0 || strpos($matched_text, "'") === 0) {
            return 'string_literal';
        } else {
            return 'unknown';
        }
    }
    
    /**
     * Analyze conditional loading patterns
     */
    private function analyzeConditionalLoading() {
        if (!$this->options['quiet']) {
            echo "Analyzing conditional loading patterns...\n";
        }
        
        $conditional_patterns = [
            // WordPress conditionals
            'is_admin\s*\(\s*\)',
            'is_ajax\s*\(\s*\)',
            'wp_doing_ajax\s*\(\s*\)',
            'is_user_logged_in\s*\(\s*\)',
            
            // Environment conditionals
            'defined\s*\(\s*[\'"]WP_DEBUG[\'"]',
            'WP_ENV',
            'SCRIPT_DEBUG',
            
            // Plugin conditionals
            'class_exists\s*\(',
            'function_exists\s*\(',
            'is_plugin_active\s*\(',
            
            // Settings-based conditionals
            'get_option\s*\(',
            'get_user_meta\s*\(',
            'get_site_option\s*\('
        ];
        
        $this->scanForConditionalPatterns($conditional_patterns);
    }
    
    /**
     * Scan for conditional loading patterns
     */
    private function scanForConditionalPatterns($patterns) {
        $dirs_to_scan = [
            $this->plugin_dir . '/src',
            $this->plugin_dir . '/includes',
            $this->plugin_dir
        ];
        
        foreach ($dirs_to_scan as $dir) {
            if (is_dir($dir)) {
                $this->scanDirectoryForConditionalPatterns($dir, $patterns);
            }
        }
    }
    
    /**
     * Scan directory for conditional patterns
     */
    private function scanDirectoryForConditionalPatterns($dir, $patterns) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $this->analyzeFileForConditionalPatterns($file->getPathname(), $patterns);
            }
        }
    }
    
    /**
     * Analyze file for conditional patterns
     */
    private function analyzeFileForConditionalPatterns($file_path, $patterns) {
        $content = file_get_contents($file_path);
        if ($content === false) {
            return;
        }
        
        $relative_path = str_replace($this->plugin_dir . '/', '', $file_path);
        
        foreach ($patterns as $pattern) {
            if (preg_match_all('/' . $pattern . '/i', $content, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $matched_text = $match[0];
                    $offset = $match[1];
                    $line = substr_count(substr($content, 0, $offset), "\n") + 1;
                    
                    if (!isset($this->conditional_loading[$relative_path])) {
                        $this->conditional_loading[$relative_path] = [];
                    }
                    
                    $this->conditional_loading[$relative_path][] = [
                        'pattern' => $pattern,
                        'match' => $matched_text,
                        'line' => $line,
                        'context' => $this->extractContext($content, $offset, 50)
                    ];
                }
            }
        }
    }
    
    /**
     * Perform cross-reference integration
     */
    private function performCrossReferenceIntegration() {
        if (!$this->options['quiet']) {
            echo "Performing cross-reference integration...\n";
        }
        
        // Integrate asset references
        $this->integrateAssetReferences();
        
        // Integrate class references
        $this->integrateClassReferences();
        
        // Integrate template references
        $this->integrateTemplateReferences();
        
        // Build comprehensive usage map
        $this->buildUsageMap();
    }
    
    /**
     * Integrate asset references from all sources
     */
    private function integrateAssetReferences() {
        foreach ($this->assets as $asset_path => &$asset_data) {
            $asset_name = basename($asset_path);
            $asset_basename = basename($asset_path, '.css');
            $asset_basename = basename($asset_basename, '.js');
            
            $usage_sources = [];
            
            // Check WordPress hooks
            foreach ($this->wordpress_hooks as $file_path => $hooks) {
                foreach ($hooks as $hook) {
                    if (stripos($hook['callback'] ?? $hook['usage'] ?? '', $asset_basename) !== false) {
                        $usage_sources[] = [
                            'type' => 'wordpress_hook',
                            'file' => $file_path,
                            'hook' => $hook['hook'],
                            'line' => $hook['line']
                        ];
                    }
                }
            }
            
            // Check dynamic patterns
            foreach ($this->dynamic_patterns as $file_path => $patterns) {
                foreach ($patterns as $pattern) {
                    if (stripos($pattern['match'], $asset_name) !== false || 
                        stripos($pattern['match'], $asset_basename) !== false) {
                        $usage_sources[] = [
                            'type' => 'dynamic_pattern',
                            'file' => $file_path,
                            'pattern' => $pattern['pattern'],
                            'line' => $pattern['line']
                        ];
                    }
                }
            }
            
            // Check string references
            foreach ($this->string_references as $file_path => $references) {
                foreach ($references as $reference) {
                    if (stripos($reference['term'], $asset_basename) !== false || 
                        stripos($reference['match'], $asset_name) !== false) {
                        $usage_sources[] = [
                            'type' => 'string_reference',
                            'file' => $file_path,
                            'reference' => $reference['match'],
                            'line' => $reference['line']
                        ];
                    }
                }
            }
            
            $asset_data['usage_sources'] = $usage_sources;
            $asset_data['is_used'] = !empty($usage_sources);
        }
    }
    
    /**
     * Integrate class references from all sources
     */
    private function integrateClassReferences() {
        foreach ($this->classes as $class_path => &$class_data) {
            $class_name = $class_data['class_name'] ?? '';
            
            if (empty($class_name)) {
                continue;
            }
            
            $usage_sources = [];
            
            // Check string references
            foreach ($this->string_references as $file_path => $references) {
                foreach ($references as $reference) {
                    if (stripos($reference['term'], $class_name) !== false || 
                        stripos($reference['match'], $class_name) !== false) {
                        $usage_sources[] = [
                            'type' => 'string_reference',
                            'file' => $file_path,
                            'reference' => $reference['match'],
                            'line' => $reference['line']
                        ];
                    }
                }
            }
            
            // Check dynamic patterns
            foreach ($this->dynamic_patterns as $file_path => $patterns) {
                foreach ($patterns as $pattern) {
                    if (stripos($pattern['match'], $class_name) !== false) {
                        $usage_sources[] = [
                            'type' => 'dynamic_pattern',
                            'file' => $file_path,
                            'pattern' => $pattern['pattern'],
                            'line' => $pattern['line']
                        ];
                    }
                }
            }
            
            $class_data['usage_sources'] = $usage_sources;
            $class_data['is_used'] = !empty($usage_sources) || ($class_data['used'] ?? false);
        }
    }
    
    /**
     * Integrate template references from all sources
     */
    private function integrateTemplateReferences() {
        foreach ($this->templates as $template_path => &$template_data) {
            $template_name = $template_data['name'] ?? '';
            
            $usage_sources = [];
            
            // Check dynamic patterns
            foreach ($this->dynamic_patterns as $file_path => $patterns) {
                foreach ($patterns as $pattern) {
                    if (stripos($pattern['match'], $template_name) !== false || 
                        stripos($pattern['match'], $template_path) !== false) {
                        $usage_sources[] = [
                            'type' => 'dynamic_pattern',
                            'file' => $file_path,
                            'pattern' => $pattern['pattern'],
                            'line' => $pattern['line']
                        ];
                    }
                }
            }
            
            // Check string references
            foreach ($this->string_references as $file_path => $references) {
                foreach ($references as $reference) {
                    if (stripos($reference['term'], $template_name) !== false || 
                        stripos($reference['match'], $template_name) !== false) {
                        $usage_sources[] = [
                            'type' => 'string_reference',
                            'file' => $file_path,
                            'reference' => $reference['match'],
                            'line' => $reference['line']
                        ];
                    }
                }
            }
            
            $template_data['usage_sources'] = $usage_sources;
            $template_data['is_used'] = !empty($usage_sources) || ($template_data['used'] ?? false);
        }
    }
    
    /**
     * Build comprehensive usage map
     */
    private function buildUsageMap() {
        $this->integration_map = [
            'assets' => [
                'total' => count($this->assets),
                'used' => 0,
                'unused' => 0,
                'usage_sources' => []
            ],
            'classes' => [
                'total' => count($this->classes),
                'used' => 0,
                'unused' => 0,
                'usage_sources' => []
            ],
            'templates' => [
                'total' => count($this->templates),
                'used' => 0,
                'unused' => 0,
                'usage_sources' => []
            ]
        ];
        
        // Count asset usage
        foreach ($this->assets as $asset_data) {
            if ($asset_data['is_used'] ?? false) {
                $this->integration_map['assets']['used']++;
                foreach ($asset_data['usage_sources'] ?? [] as $source) {
                    $this->integration_map['assets']['usage_sources'][] = $source;
                }
            } else {
                $this->integration_map['assets']['unused']++;
            }
        }
        
        // Count class usage
        foreach ($this->classes as $class_data) {
            if ($class_data['is_used'] ?? false) {
                $this->integration_map['classes']['used']++;
                foreach ($class_data['usage_sources'] ?? [] as $source) {
                    $this->integration_map['classes']['usage_sources'][] = $source;
                }
            } else {
                $this->integration_map['classes']['unused']++;
            }
        }
        
        // Count template usage
        foreach ($this->templates as $template_data) {
            if ($template_data['is_used'] ?? false) {
                $this->integration_map['templates']['used']++;
                foreach ($template_data['usage_sources'] ?? [] as $source) {
                    $this->integration_map['templates']['usage_sources'][] = $source;
                }
            } else {
                $this->integration_map['templates']['unused']++;
            }
        }
    }
    
    /**
     * Fix registration pattern detection
     */
    private function fixRegistrationPatterns() {
        if (!$this->options['quiet']) {
            echo "Fixing registration pattern detection issues...\n";
        }
        
        // Re-analyze asset registration with improved patterns
        $improved_patterns = [
            // WordPress enqueue patterns
            'wp_enqueue_style\s*\(\s*[\'"]([^\'"]+)[\'"]',
            'wp_enqueue_script\s*\(\s*[\'"]([^\'"]+)[\'"]',
            'wp_register_style\s*\(\s*[\'"]([^\'"]+)[\'"]',
            'wp_register_script\s*\(\s*[\'"]([^\'"]+)[\'"]',
            
            // Direct asset references
            'plugin_dir_url\s*\(\s*__FILE__\s*\)\s*\.\s*[\'"]([^\'"]+\.(?:css|js))[\'"]',
            'plugins_url\s*\(\s*[\'"]([^\'"]+\.(?:css|js))[\'"]',
            
            // Template loading patterns
            'include\s*\(\s*[\'"]([^\'"]*templates/[^\'"]*\.php)[\'"]',
            'require\s*\(\s*[\'"]([^\'"]*templates/[^\'"]*\.php)[\'"]',
            'get_template_part\s*\(\s*[\'"]([^\'"]+)[\'"]',
            'load_template\s*\(\s*[\'"]([^\'"]*templates/[^\'"]*\.php)[\'"]'
        ];
        
        $this->scanForImprovedPatterns($improved_patterns);
    }
    
    /**
     * Scan for improved registration patterns
     */
    private function scanForImprovedPatterns($patterns) {
        $dirs_to_scan = [
            $this->plugin_dir . '/src',
            $this->plugin_dir . '/includes',
            $this->plugin_dir
        ];
        
        $improved_registrations = [];
        
        foreach ($dirs_to_scan as $dir) {
            if (is_dir($dir)) {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
                );
                
                foreach ($iterator as $file) {
                    if ($file->isFile() && $file->getExtension() === 'php') {
                        $content = file_get_contents($file->getPathname());
                        $relative_path = str_replace($this->plugin_dir . '/', '', $file->getPathname());
                        
                        foreach ($patterns as $pattern) {
                            if (preg_match_all('/' . $pattern . '/i', $content, $matches, PREG_OFFSET_CAPTURE)) {
                                foreach ($matches[1] as $match) {
                                    $asset_ref = $match[0];
                                    $offset = $match[1];
                                    $line = substr_count(substr($content, 0, $offset), "\n") + 1;
                                    
                                    $improved_registrations[] = [
                                        'file' => $relative_path,
                                        'asset' => $asset_ref,
                                        'pattern' => $pattern,
                                        'line' => $line
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }
        
        $this->corrected_usage['improved_registrations'] = $improved_registrations;
    }
    
    /**
     * Generate validation results
     */
    private function generateValidationResults() {
        $results = [
            'analysis_info' => [
                'script' => MPAI_SCRIPT_NAME,
                'version' => MPAI_SCRIPT_VERSION,
                'timestamp' => date('Y-m-d H:i:s'),
                'plugin_dir' => $this->plugin_dir
            ],
            'validation_summary' => $this->integration_map,
            'assets' => $this->assets,
            'classes' => $this->classes,
            'templates' => $this->templates,
            'wordpress_hooks' => $this->wordpress_hooks,
            'dynamic_patterns' => $this->dynamic_patterns,
            'string_references' => $this->string_references,
            'conditional_loading' => $this->conditional_loading,
            'corrected_usage' => $this->corrected_usage,
            'recommendations' => $this->generateRecommendations()
        ];
        
        if ($this->options['json']) {
            echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } else {
            $this->displayResults($results);
        }
        
        return $results;
    }
    
    /**
     * Generate cleanup recommendations
     */
    private function generateRecommendations() {
        $recommendations = [];
        
        // Asset recommendations
        $unused_assets = array_filter($this->assets, function($asset) {
            return !($asset['is_used'] ?? false);
        });
        
        if (!empty($unused_assets)) {
            $recommendations[] = [
                'type' => 'asset_cleanup',
                'priority' => 'medium',
                'count' => count($unused_assets),
                'action' => 'Review ' . count($unused_assets) . ' potentially unused assets',
                'files' => array_keys($unused_assets)
            ];
        }
        
        // Template recommendations
        $unused_templates = array_filter($this->templates, function($template) {
            return !($template['is_used'] ?? false);
        });
        
        if (!empty($unused_templates)) {
            $recommendations[] = [
                'type' => 'template_cleanup',
                'priority' => 'high',
                'count' => count($unused_templates),
                'action' => 'Verify ' . count($unused_templates) . ' potentially unused templates',
                'files' => array_keys($unused_templates)
            ];
        }
        
        // Class recommendations
        $unused_classes = array_filter($this->classes, function($class) {
            return !($class['is_used'] ?? false);
        });
        
        if (!empty($unused_classes)) {
            $recommendations[] = [
                'type' => 'class_cleanup',
                'priority' => 'low',
                'count' => count($unused_classes),
                'action' => 'Review ' . count($unused_classes) . ' potentially unused classes',
                'files' => array_keys($unused_classes)
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * Display formatted results
     */
    private function displayResults($results) {
        $summary = $results['validation_summary'];
        
        echo "\n" . str_repeat("=", 70) . "\n";
        echo "CROSS-REFERENCE VALIDATION RESULTS\n";
        echo str_repeat("=", 70) . "\n";
        
        echo sprintf("Assets: %d total, %d used (%.1f%%), %d unused\n",
            $summary['assets']['total'],
            $summary['assets']['used'],
            $summary['assets']['total'] > 0 ? ($summary['assets']['used'] / $summary['assets']['total']) * 100 : 0,
            $summary['assets']['unused']
        );
        
        echo sprintf("Classes: %d total, %d used (%.1f%%), %d unused\n",
            $summary['classes']['total'],
            $summary['classes']['used'],
            $summary['classes']['total'] > 0 ? ($summary['classes']['used'] / $summary['classes']['total']) * 100 : 0,
            $summary['classes']['unused']
        );
        
        echo sprintf("Templates: %d total, %d used (%.1f%%), %d unused\n",
            $summary['templates']['total'],
            $summary['templates']['used'],
            $summary['templates']['total'] > 0 ? ($summary['templates']['used'] / $summary['templates']['total']) * 100 : 0,
            $summary['templates']['unused']
        );
        
        echo "\nValidation Sources Found:\n";
        echo sprintf("WordPress Hooks: %d files analyzed\n", count($results['wordpress_hooks']));
        echo sprintf("Dynamic Patterns: %d files with dynamic loading\n", count($results['dynamic_patterns']));
        echo sprintf("String References: %d files with string references\n", count($results['string_references']));
        echo sprintf("Conditional Loading: %d files with conditionals\n", count($results['conditional_loading']));
        
        if (isset($results['corrected_usage']['improved_registrations'])) {
            echo sprintf("Improved Registrations: %d patterns found\n", count($results['corrected_usage']['improved_registrations']));
        }
        
        echo "\n" . str_repeat("-", 70) . "\n";
        echo "CLEANUP RECOMMENDATIONS\n";
        echo str_repeat("-", 70) . "\n";
        
        foreach ($results['recommendations'] as $recommendation) {
            echo sprintf("🔹 %s (%s priority): %s\n",
                strtoupper($recommendation['type']),
                $recommendation['priority'],
                $recommendation['action']
            );
        }
        
        echo "\n" . str_repeat("=", 70) . "\n";
        echo "✅ Cross-reference validation complete!\n";
        echo "Results saved to: scripts/cross-reference-validation.json\n";
    }
}

// Command line argument parsing
$options = [
    'help' => false,
    'json' => false,
    'verbose' => false,
    'quiet' => false,
    'fix-patterns' => false,
    'hooks-only' => false,
    'dynamic-only' => false
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
        case '--fix-patterns':
            $options['fix-patterns'] = true;
            break;
        case '--hooks-only':
            $options['hooks-only'] = true;
            break;
        case '--dynamic-only':
            $options['dynamic-only'] = true;
            break;
    }
}

// Show help if requested
if ($options['help']) {
    display_help();
    exit(0);
}

// Run the validation
try {
    $validator = new CrossReferenceValidator($plugin_dir, $options);
    $results = $validator->validate();
    
    // Save JSON results
    if (!$options['json']) {
        $json_file = $plugin_dir . '/scripts/cross-reference-validation.json';
        file_put_contents($json_file, json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}