<?php
/**
 * Command Security
 *
 * Security filter for commands to block dangerous operations
 *
 * @package MemberPress AI Assistant
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Command Security Class
 */
class MPAI_Command_Security {
    /**
     * Logger instance
     *
     * @var object
     */
    private $logger;

    /**
     * List of dangerous command patterns
     *
     * @var array
     */
    private $dangerous_patterns = [
        // File system operations
        '/rm\s+(-rf|--recursive)/i',      // Recursive remove
        '/rmdir\s+.*--recursive/i',       // Recursive directory remove
        '/chmod\s+777/i',                 // Chmod 777 (too permissive)
        '/chown\s+/i',                    // Ownership changes
        
        // Database operations
        '/DROP\s+TABLE/i',                // SQL DROP TABLE
        '/DROP\s+DATABASE/i',             // SQL DROP DATABASE
        '/TRUNCATE\s+TABLE/i',            // SQL TRUNCATE
        '/DELETE\s+FROM\s+\w+/i',         // SQL DELETE without WHERE
        
        // PHP code execution
        '/system\s*\(/i',                 // PHP system function
        '/exec\s*\(/i',                   // PHP exec function
        '/shell_exec\s*\(/i',             // PHP shell_exec function
        '/passthru\s*\(/i',               // PHP passthru function
        '/`.*`/i',                        // Backtick shell execution
        
        // Network operations
        '/(?:curl|wget|axel|aria2c)/i',   // Download commands
        '/nc\s+-/i',                      // Netcat with options
        
        // File creation in sensitive locations
        '/\s+>\s+\/etc\//i',              // Output to /etc/
        '/\s+>\s+\/var\/www\//i',         // Output to webroot
        
        // Command chaining
        '/\s*;\s*(?!$)/i',                // Command separator
        '/\s*\|\s*/i',                    // Pipe operator
        '/\s*&&\s*/i',                    // AND operator
        '/\s*\|\|\s*/i',                  // OR operator
        
        // Other dangerous operations
        '/:(){ :|:& };:/i',               // Fork bomb
        '/mkfs/i',                        // Filesystem creation
        '/dd\s+if/i',                     // Raw disk operations
    ];

    /**
     * Constructor
     */
    public function __construct() {
        // Initialize logger
        $this->logger = $this->get_default_logger();
        
        // Add WordPress-specific dangerous patterns
        $this->add_wordpress_patterns();
    }

    /**
     * Get default logger
     *
     * @return object Default logger
     */
    private function get_default_logger() {
        return (object) [
            'info'    => function( $message ) { error_log( 'MPAI SECURITY INFO: ' . $message ); },
            'warning' => function( $message ) { error_log( 'MPAI SECURITY WARNING: ' . $message ); },
            'error'   => function( $message ) { error_log( 'MPAI SECURITY ERROR: ' . $message ); },
        ];
    }

    /**
     * Add WordPress-specific dangerous patterns
     */
    private function add_wordpress_patterns() {
        $wp_dangerous_patterns = [
            // WordPress core modifications
            '/wp\s+core\s+update\s+--force/i',    // Force core update
            
            // Plugin/theme installation from untrusted sources
            '/wp\s+plugin\s+install\s+http/i',    // Install plugin from URL
            '/wp\s+theme\s+install\s+http/i',     // Install theme from URL
            
            // Database operations
            '/wp\s+db\s+query\s+[\'"]?(DELETE|DROP|TRUNCATE)/i',  // Dangerous SQL through WP-CLI
            
            // User operations
            '/wp\s+user\s+delete\s+1/i',          // Delete admin user
            
            // Option modifications
            '/wp\s+option\s+(update|delete)\s+siteurl/i',    // Change site URL
            '/wp\s+option\s+(update|delete)\s+home/i',       // Change home URL
            '/wp\s+option\s+(update|delete)\s+active_plugins/i', // Modify active plugins
        ];
        
        // Merge WordPress patterns with general dangerous patterns
        $this->dangerous_patterns = array_merge($this->dangerous_patterns, $wp_dangerous_patterns);
    }

    /**
     * Check if a command is dangerous
     *
     * @param string $command Command to check
     * @return bool Whether command is dangerous
     */
    public function is_dangerous($command) {
        // Skip check for empty commands
        if (empty($command)) {
            return false;
        }
        
        // Check against dangerous patterns
        foreach ($this->dangerous_patterns as $pattern) {
            if (preg_match($pattern, $command)) {
                $this->logger->warning('Dangerous command detected: ' . $command . ' (matched pattern: ' . $pattern . ')');
                return true;
            }
        }
        
        // Check for sensitive file access
        if ($this->involves_sensitive_files($command)) {
            $this->logger->warning('Command involves sensitive files: ' . $command);
            return true;
        }
        
        // Check for unsafe commands with high impact
        if ($this->is_high_impact_command($command)) {
            $this->logger->warning('High impact command detected: ' . $command);
            return true;
        }
        
        return false;
    }

    /**
     * Check if command involves sensitive files
     *
     * @param string $command Command to check
     * @return bool Whether command involves sensitive files
     */
    private function involves_sensitive_files($command) {
        $sensitive_files = [
            'wp-config.php',
            '.htaccess',
            '.env',
            'wp-config-sample.php',
            '/etc/passwd',
            '/etc/shadow',
            '/etc/hosts',
        ];
        
        foreach ($sensitive_files as $file) {
            if (stripos($command, $file) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if command is a high impact command
     *
     * @param string $command Command to check
     * @return bool Whether command is high impact
     */
    private function is_high_impact_command($command) {
        $high_impact_commands = [
            'wp plugin deactivate --all',
            'wp theme delete',
            'wp db reset',
            'wp site empty',
            'wp site delete',
            'wp config set',
        ];
        
        foreach ($high_impact_commands as $risky_command) {
            if (stripos($command, $risky_command) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get a safe subset of a command for logging
     *
     * @param string $command Command to process
     * @return string Safe version for logging
     */
    public function get_safe_command_for_logging($command) {
        // Remove any password parameters
        $command = preg_replace('/--pass(word)?=\S+/', '--password=[REDACTED]', $command);
        $command = preg_replace('/-p\s+\S+/', '-p [REDACTED]', $command);
        
        // Remove any API key parameters
        $command = preg_replace('/--key=\S+/', '--key=[REDACTED]', $command);
        $command = preg_replace('/--api-key=\S+/', '--api-key=[REDACTED]', $command);
        
        return $command;
    }
}