<?php
/**
 * WP-CLI Executor
 *
 * Executes WP-CLI commands
 * 
 * SECURITY NOTE: This implementation uses a permissive blacklist approach rather than
 * a restrictive whitelist. This follows the Command System Rewrite Plan's KISS principle
 * and is more user-friendly while still maintaining security by blocking dangerous patterns.
 *
 * @package MemberPress AI Assistant
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * WP-CLI Executor Class
 */
class MPAI_WP_CLI_Executor {

    /**
     * Execution timeout in seconds
     *
     * @var int
     */
    private $timeout = 30;

    /**
     * Logger instance
     *
     * @var object
     */
    private $logger;

    /**
     * List of dangerous command patterns to block
     * 
     * Following the Command System Rewrite Plan that adopts a permissive-by-default approach,
     * we block commands that are explicitly dangerous rather than starting with an allowlist.
     * 
     * @var array
     */
    private $dangerous_patterns = [
        '/rm\s+-rf/i',                   // Recursive file deletion
        '/DROP\s+TABLE/i',               // SQL table drops
        '/system\s*\(/i',                // PHP system calls
        '/(?:curl|wget)\s+.*-o/i',       // File downloads
        '/eval\s*\(/i',                  // PHP code evaluation
        '/<\?php/i',                     // PHP code inclusion
        '/>(\\/dev\\/null|\\/dev\\/zero)/i', // Redirects to system devices
        '/:(){ :|:& };:/i',              // Fork bomb
        '/sudo /i',                      // Sudo commands
        '/shutdown/i',                   // System shutdown
        '/reboot/i',                     // System reboot
        '/mkfs/i',                       // Filesystem formatting
        '/dd\s+if/i',                    // Disk operations
        '/shred/i',                      // Secure deletion
        '/chmod\s+777/i',                // Insecure permissions
        '/chmod\s+-R/i',                 // Recursive permission changes
        '/chown\s+-R/i',                 // Recursive ownership changes
        '/alias\s+/i',                   // Shell alias commands
        '/exec\s*\(/i',                  // PHP exec function
        '/passthru\s*\(/i',              // PHP passthru function
        '/shell_exec\s*\(/i',            // PHP shell_exec function
        '/popen\s*\(/i',                 // PHP popen function
        '/proc_open\s*\(/i',             // PHP proc_open function
        '/pcntl_exec\s*\(/i',            // PHP pcntl_exec function
        '/\|\s*bash/i',                  // Piping to bash
        '/`.*`/i',                       // Backtick command execution
        '/>\s*\/etc\/passwd/i',          // Writing to /etc/passwd
        '/>\s*\/etc\/shadow/i',          // Writing to /etc/shadow
        '/>\s*\/etc\/hosts/i',           // Writing to /etc/hosts
    ];

    /**
     * Constructor
     */
    public function __construct() {
        // Initialize logger
        $this->init_logger();
        
        // Allow plugins to extend the dangerous patterns list
        $this->dangerous_patterns = apply_filters('mpai_blocked_cli_patterns', $this->dangerous_patterns);
    }
    
    /**
     * Initialize logger
     */
    private function init_logger() {
        // Create a simple logger class
        $this->logger = new class() {
            public function info($message) {
                error_log('MPAI WP-CLI: ' . $message);
            }
            
            public function error($message) {
                error_log('MPAI WP-CLI ERROR: ' . $message);
            }
            
            public function debug($message) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('MPAI WP-CLI DEBUG: ' . $message);
                }
            }
        };
    }


    /**
     * Execute a WP-CLI command
     *
     * @param string $command Command to execute
     * @param array $parameters Additional parameters
     * @return array Execution result
     */
    public function execute($command, $parameters = []) {
        $this->logger->info('Executing WP-CLI command: ' . $command);
        
        // Validate the command for security
        if (!$this->validate_command($command)) {
            $this->logger->error('Command security validation failed: ' . $command);
            return [
                'success' => false,
                'output' => 'Command could not be executed for security reasons. Please try a different command.',
                'error' => 'Command blocked by security validation',
                'command' => $command
            ];
        }
        try {
            error_log('MPAI WP-CLI: Executing command: ' . $command);
            
            // Set custom timeout if provided
            if (isset($parameters['timeout'])) {
                $this->timeout = min((int)$parameters['timeout'], 60); // Max 60 seconds
            }
            
            // Parse command type and route to appropriate handler
            $command = trim($command);
            $this->logger->info('Analyzing command type: ' . $command);
            
            if ($this->is_php_version_query($command)) {
                $this->logger->info('Handling as PHP version query');
                return $this->get_php_version_info();
            } elseif ($this->is_plugin_query($command)) {
                $this->logger->info('Handling as plugin command');
                return $this->handle_plugin_command($command, $parameters);
            } elseif ($this->is_system_query($command)) {
                $this->logger->info('Handling as system command');
                return $this->handle_system_command($command, $parameters);
            }
            
            $this->logger->info('Handling as general WP-CLI command');
            
            // Check for special cases that should bypass WP-CLI
            if (trim($command) === 'wp plugin list' || 
                trim($command) === 'wp plugins' || 
                trim($command) === 'plugins') {
                // Special handling for plugin list command
                $this->logger->info('Using direct WordPress API for plugin list');
                return $this->get_plugin_list($parameters);
            }
            
            // Build the command
            $wp_cli_command = $this->build_command($command, $parameters);
            
            // Execute the command
            $output = [];
            $return_var = 0;
            $this->logger->info('Executing: ' . $wp_cli_command);
            $last_line = exec($wp_cli_command, $output, $return_var);
            
            // Handle the result
            if ($return_var !== 0) {
                error_log('MPAI WP-CLI ERROR: Command failed with code ' . $return_var . ': ' . implode("\n", $output));
                return [
                    'success' => false,
                    'output' => implode("\n", $output),
                    'return_code' => $return_var,
                    'command' => $command
                ];
            }
            
            // Format the output based on the requested format
            $format = isset($parameters['format']) ? $parameters['format'] : 'text';
            $formatted_output = $this->format_output($output, $format);
            
            return [
                'success' => true,
                'output' => $formatted_output,
                'return_code' => $return_var,
                'command' => $command
            ];
        } catch (Exception $e) {
            $this->logger->error('Command execution error: ' . $e->getMessage());
            return [
                'success' => false,
                'output' => 'Error executing command: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'command' => $command
            ];
        }
    }

    /**
     * Check if command is a PHP version query
     *
     * @param string $command Command to check
     * @return bool Whether command is a PHP version query
     */
    private function is_php_version_query($command) {
        $php_version_patterns = [
            '/php.*version/i',
            '/php\s+[-]{1,2}v/i',
            '/php\s+info/i',
            '/phpinfo/i',
            '/wp\s+eval\s+[\'"]?echo\s+PHP_VERSION/i',
            '/wp\s+php\s+info/i',
            '/wp\s+php\s+version/i'
        ];
        
        foreach ($php_version_patterns as $pattern) {
            if (preg_match($pattern, $command)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if command is a plugin query
     *
     * @param string $command Command to check
     * @return bool Whether command is a plugin query
     */
    private function is_plugin_query($command) {
        $command = strtolower(trim($command));
        return (strpos($command, 'wp plugin') === 0 || 
                strpos($command, 'plugin') === 0 || 
                strpos($command, 'plugins') === 0 || 
                strpos($command, 'wp plugins') === 0);
    }

    /**
     * Check if command is a system query
     *
     * @param string $command Command to check
     * @return bool Whether command is a system query
     */
    private function is_system_query($command) {
        $system_patterns = [
            '/wp\s+system-info/i',
            '/wp\s+site\s+health/i',
            '/wp\s+core\s+version/i',
            '/wp\s+core\s+/i',         // Any core command
            '/wp\s+db\s+info/i',
            '/wp\s+db\s+/i',           // Any db command
            '/wp\s+option\s+/i',       // Any option command
            '/wp\s+post\s+/i',         // Any post command
            '/wp\s+user\s+/i',         // Any user command
            '/wp\s+theme\s+/i',        // Any theme command
            '/wp\s+config\s+/i',       // Any config command
            '/wp\s+maintenance-mode/i',
            '/wp\s+cron\s+/i'          // Any cron command
        ];
        
        foreach ($system_patterns as $pattern) {
            if (preg_match($pattern, $command)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get PHP version and configuration information
     *
     * @return array PHP version information
     */
    private function get_php_version_info() {
        $this->logger->info('Getting PHP version information');
        
        // Get PHP version and other information
        $php_version = phpversion();
        $php_uname = php_uname();
        $php_sapi = php_sapi_name();
        
        // Get PHP configuration
        $memory_limit = ini_get('memory_limit');
        $max_execution_time = ini_get('max_execution_time');
        $upload_max_filesize = ini_get('upload_max_filesize');
        $post_max_size = ini_get('post_max_size');
        $max_input_vars = ini_get('max_input_vars');
        
        // Get loaded extensions
        $loaded_extensions = get_loaded_extensions();
        sort($loaded_extensions);
        $extensions_str = implode(', ', array_slice($loaded_extensions, 0, 15)) . '...';
        
        // Format the output
        $output = "PHP Information:\n\n";
        $output .= "PHP Version: $php_version\n";
        $output .= "System: $php_uname\n";
        $output .= "SAPI: $php_sapi\n";
        $output .= "\nImportant Settings:\n";
        $output .= "memory_limit: $memory_limit\n";
        $output .= "max_execution_time: $max_execution_time seconds\n";
        $output .= "upload_max_filesize: $upload_max_filesize\n";
        $output .= "post_max_size: $post_max_size\n";
        $output .= "max_input_vars: $max_input_vars\n";
        $output .= "\nExtensions: $extensions_str\n";
        
        return [
            'success' => true,
            'output' => $output,
            'php_version' => $php_version,
            'command' => 'php -v'
        ];
    }

    /**
     * Handle plugin-related commands
     *
     * @param string $command Command to execute
     * @param array $parameters Additional parameters
     * @return array Execution result
     */
    private function handle_plugin_command($command, $parameters = []) {
        $this->logger->info('Handling plugin command: ' . $command);
        
        // Normalize command for better pattern matching
        $command_lower = strtolower(trim($command));
        
        // Handle plugin list commands
        if (preg_match('/wp\s+plugins?\s+list/i', $command) || 
            $command_lower === 'plugins' || 
            $command_lower === 'wp plugins' || 
            $command_lower === 'plugin list' || 
            $command_lower === 'wp plugin list') {
            $this->logger->info('Getting plugin list');
            return $this->get_plugin_list($parameters);
        }
        
        // Handle plugin status or info commands
        if (preg_match('/wp\s+plugins?\s+(status|info)/i', $command) || 
            preg_match('/plugins?\s+(status|info)/i', $command)) {
            $this->logger->info('Getting plugin status');
            return $this->get_plugin_status($parameters);
        }
        
        // For other plugin commands, execute directly
        $this->logger->info('Executing plugin command directly: ' . $command);
        $wp_cli_command = $this->build_command($command, $parameters);
        $output = [];
        $return_var = 0;
        exec($wp_cli_command, $output, $return_var);
        
        return [
            'success' => ($return_var === 0),
            'output' => implode("\n", $output),
            'return_code' => $return_var,
            'command' => $command
        ];
    }

    /**
     * Get list of plugins
     *
     * @param array $parameters Additional parameters
     * @return array Plugin list
     */
    private function get_plugin_list($parameters) {
        $this->logger->info('Getting plugin list from WordPress API');
        
        // Ensure plugin functions are available
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        // Get plugins
        $all_plugins = get_plugins();
        $active_plugins = get_option('active_plugins');
        
        // Filter by status if requested
        $status = isset($parameters['status']) ? $parameters['status'] : null;
        $this->logger->info('Status filter: ' . ($status ?: 'none'));
        
        // Format output - headers in the same format as WP-CLI would output
        if (isset($parameters['format']) && $parameters['format'] === 'json') {
            // Format as JSON array
            $plugins_json = [];
            foreach ($all_plugins as $plugin_path => $plugin_data) {
                $plugin_status = in_array($plugin_path, $active_plugins) ? 'active' : 'inactive';
                
                // Skip if status filter doesn't match
                if ($status && $plugin_status !== $status) {
                    continue;
                }
                
                $plugins_json[] = [
                    'name' => $plugin_data['Name'],
                    'status' => $plugin_status,
                    'version' => $plugin_data['Version'],
                    'description' => $plugin_data['Description'],
                    'path' => $plugin_path
                ];
            }
            
            return [
                'success' => true,
                'output' => json_encode($plugins_json),
                'command' => 'wp plugin list',
                'plugins' => $all_plugins
            ];
        } else {
            // Format as text table 
            $output = "NAME\tSTATUS\tVERSION\tDESCRIPTION\n";
            
            foreach ($all_plugins as $plugin_path => $plugin_data) {
                $plugin_status = in_array($plugin_path, $active_plugins) ? 'active' : 'inactive';
                
                // Skip if status filter doesn't match
                if ($status && $plugin_status !== $status) {
                    continue;
                }
                
                $name = $plugin_data['Name'];
                $version = $plugin_data['Version'];
                // Make sure description is a string before using strlen
                $description = isset($plugin_data['Description']) && is_string($plugin_data['Description']) ? 
                               (strlen($plugin_data['Description']) > 40 ? 
                               substr($plugin_data['Description'], 0, 40) . '...' : 
                               $plugin_data['Description']) : '';
                
                $output .= "$name\t$plugin_status\t$version\t$description\n";
            }
            
            return [
                'success' => true,
                'output' => $output,
                'command' => 'wp plugin list',
                'plugins' => $all_plugins
            ];
        }
    }

    /**
     * Get plugin status and information
     *
     * @param array $parameters Additional parameters
     * @return array Plugin status information
     */
    private function get_plugin_status($parameters) {
        $this->logger->info('Getting plugin status');
        
        // Ensure plugin functions are available
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        // Get system information
        $php_version = phpversion();
        $wp_version = get_bloginfo('version');
        
        $output = "WordPress System Status:\n\n";
        $output .= "PHP Version: $php_version\n";
        $output .= "WordPress Version: $wp_version\n";
        
        // Get plugin information
        $all_plugins = get_plugins();
        $active_plugins = get_option('active_plugins');
        
        $output .= "\nPlugin Statistics:\n";
        $output .= "Total Plugins: " . count($all_plugins) . "\n";
        $output .= "Active Plugins: " . count($active_plugins) . "\n";
        $output .= "Inactive Plugins: " . (count($all_plugins) - count($active_plugins)) . "\n\n";
        
        // List active plugins
        $output .= "Active Plugins:\n";
        foreach ($active_plugins as $plugin) {
            if (isset($all_plugins[$plugin])) {
                $plugin_data = $all_plugins[$plugin];
                $output .= "- {$plugin_data['Name']} v{$plugin_data['Version']}\n";
            }
        }
        
        return [
            'success' => true,
            'output' => $output,
            'command' => 'wp plugin status',
            'php_version' => $php_version,
            'wp_version' => $wp_version,
            'plugins' => $all_plugins
        ];
    }

    /**
     * Handle system-related commands
     *
     * @param string $command Command to execute
     * @param array $parameters Additional parameters
     * @return array Execution result
     */
    private function handle_system_command($command, $parameters = []) {
        $this->logger->info('Handling system command: ' . $command);
        
        if (preg_match('/wp\s+core\s+version/i', $command)) {
            // Get WordPress version
            $wp_version = get_bloginfo('version');
            return [
                'success' => true,
                'output' => "WordPress version: $wp_version",
                'wp_version' => $wp_version,
                'command' => $command
            ];
        }
        
        if (preg_match('/wp\s+db\s+info/i', $command)) {
            // Get database information
            return $this->get_database_info();
        }
        
        if (preg_match('/wp\s+site\s+health/i', $command) || preg_match('/wp\s+system-info/i', $command)) {
            // Get site health information
            return $this->get_site_health_info();
        }
        
        // For any other system command, execute directly
        $wp_cli_command = $this->build_command($command, $parameters);
        $output = [];
        $return_var = 0;
        exec($wp_cli_command, $output, $return_var);
        
        return [
            'success' => ($return_var === 0),
            'output' => implode("\n", $output),
            'return_code' => $return_var,
            'command' => $command
        ];
    }

    /**
     * Get database information
     *
     * @return array Database information
     */
    private function get_database_info() {
        global $wpdb;
        
        $db_version = $wpdb->db_version();
        $db_name = defined('DB_NAME') ? DB_NAME : 'unknown';
        $db_host = defined('DB_HOST') ? DB_HOST : 'unknown';
        $db_user = defined('DB_USER') ? DB_USER : 'unknown';
        $db_charset = defined('DB_CHARSET') ? DB_CHARSET : $wpdb->charset;
        $db_collate = $wpdb->collate;
        
        $output = "Database Information:\n\n";
        $output .= "MySQL Version: $db_version\n";
        $output .= "Database Name: $db_name\n";
        $output .= "Database Host: $db_host\n";
        $output .= "Database User: $db_user\n";
        $output .= "Database Charset: $db_charset\n";
        $output .= "Database Collation: " . ($db_collate ?: 'Not Set') . "\n";
        $output .= "Table Prefix: " . $wpdb->prefix . "\n";
        
        // Get table statistics
        $tables = $wpdb->get_results('SHOW TABLES', ARRAY_N);
        $table_count = count($tables);
        
        $output .= "\nTable Statistics:\n";
        $output .= "Total Tables: $table_count\n";
        
        // Get some basic WordPress table counts
        $post_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts}");
        $user_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}");
        $comment_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->comments}");
        $option_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->options}");
        
        $output .= "Posts Table Count: $post_count\n";
        $output .= "Users Table Count: $user_count\n";
        $output .= "Comments Table Count: $comment_count\n";
        $output .= "Options Table Count: $option_count\n";
        
        return [
            'success' => true,
            'output' => $output,
            'command' => 'wp db info',
            'db_version' => $db_version,
            'table_count' => $table_count
        ];
    }

    /**
     * Get site health information
     *
     * @return array Site health information
     */
    private function get_site_health_info() {
        global $wp_version;
        global $wpdb;
        
        $output = "WordPress Site Health Information:\n\n";
        
        // WordPress core information
        $output .= "WordPress Version: $wp_version\n";
        $output .= "Site URL: " . get_site_url() . "\n";
        $output .= "Home URL: " . get_home_url() . "\n";
        $output .= "Is Multisite: " . (is_multisite() ? 'Yes' : 'No') . "\n";
        
        // PHP information
        $output .= "\nPHP Information:\n";
        $output .= "PHP Version: " . phpversion() . "\n";
        $output .= "Memory Limit: " . ini_get('memory_limit') . "\n";
        $output .= "Max Execution Time: " . ini_get('max_execution_time') . " seconds\n";
        $output .= "Upload Max Filesize: " . ini_get('upload_max_filesize') . "\n";
        
        // Database information
        $output .= "\nDatabase Information:\n";
        $db_version = $wpdb->db_version();
        $output .= "MySQL Version: $db_version\n";
        $output .= "Database Prefix: " . $wpdb->prefix . "\n";
        
        // Theme information
        $theme = wp_get_theme();
        $output .= "\nActive Theme:\n";
        $output .= "Name: " . $theme->get('Name') . "\n";
        $output .= "Version: " . $theme->get('Version') . "\n";
        
        // Plugin information
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $all_plugins = get_plugins();
        $active_plugins = get_option('active_plugins');
        
        $output .= "\nPlugin Status:\n";
        $output .= "Active Plugins: " . count($active_plugins) . "\n";
        $output .= "Total Plugins: " . count($all_plugins) . "\n";
        
        return [
            'success' => true,
            'output' => $output,
            'command' => 'wp site health',
            'wp_version' => $wp_version,
            'php_version' => phpversion()
        ];
    }

    /**
     * Build a WP-CLI command with proper parameters and escaping
     *
     * @param string $command Base command
     * @param array $parameters Additional parameters
     * @return string Full command line
     */
    private function build_command($command, $parameters = []) {
        // Ensure command starts with wp
        if (strpos($command, 'wp ') !== 0 && strpos($command, 'php ') !== 0) {
            $command = 'wp ' . $command;
        }
        
        // Add format parameter if not specifically set in command
        if (strpos($command, '--format=') === false && strpos($command, 'help') === false) {
            // Default to JSON format for easier parsing
            $command .= ' --format=json';
        }
        
        // Escape the command
        $escaped_command = escapeshellcmd($command);
        
        // Add timeout
        $timeout = isset($parameters['timeout']) ? min((int)$parameters['timeout'], 60) : $this->timeout;
        $full_command = "timeout {$timeout}s {$escaped_command}";
        
        return $full_command;
    }

    /**
     * Check if a command contains dangerous patterns
     *
     * @param string $command WP-CLI command
     * @return bool Whether command is safe (true = safe, false = dangerous)
     */
    private function validate_command($command) {
        // Sanitize the command
        $sanitized_command = $this->sanitize_command($command);
        
        // Check against blacklist of dangerous patterns
        foreach ($this->dangerous_patterns as $pattern) {
            if (preg_match($pattern, $sanitized_command)) {
                $this->logger->error('Dangerous command pattern detected: ' . $pattern);
                return false;
            }
        }
        
        // Additional basic safety check
        if (strpos($sanitized_command, 'wp ') !== 0 && strpos($sanitized_command, 'php ') !== 0) {
            $this->logger->error('Command must start with wp or php: ' . $sanitized_command);
            return false;
        }
        
        // Command passed all security checks
        return true;
    }
    
    /**
     * Sanitize a command to prevent injection
     *
     * @param string $command Command to sanitize
     * @return string Sanitized command
     */
    private function sanitize_command($command) {
        // Remove potentially dangerous characters
        $command = preg_replace('/[;&|><]/', '', $command);
        
        // Ensure command starts with 'wp ' or 'php '
        if (strpos($command, 'wp ') !== 0 && strpos($command, 'php ') !== 0) {
            $command = 'wp ' . $command;
        }
        
        return trim($command);
    }
    
    /**
     * Format command output based on requested format
     *
     * @param array|string $output Command output lines
     * @param string $format Desired output format
     * @return mixed Formatted output
     */
    private function format_output($output, $format) {
        // Improved type safety handling
        $this->logger->info('Formatting output of type: ' . gettype($output));
        
        // Handle different input types
        if (is_string($output)) {
            // If it's already a string and format is text, return directly
            if ($format === 'text') {
                return $output;
            }
            
            // For other formats, convert to array
            $output = [$output];
        } elseif (is_object($output)) {
            // Convert objects to JSON strings
            $this->logger->info('Converting object to JSON');
            return json_encode($output);
        } elseif (!is_array($output)) {
            // For any other type, convert to string then array
            $this->logger->warning('Converting unexpected type to string: ' . gettype($output));
            $output = [(string)$output];
        }
        
        // Now $output should be an array
        if (empty($output)) {
            return '';
        }
        
        $raw_output = implode("\n", $output);
        
        switch ($format) {
            case 'json':
                // Try to parse the output as JSON
                $decoded = json_decode($raw_output, true);
                if ($decoded !== null && json_last_error() === JSON_ERROR_NONE) {
                    return $decoded;
                }
                // Fall through to array if not valid JSON
                
            case 'array':
                return $output;
                
            case 'text':
            default:
                return $raw_output;
        }
    }
}