<?php
/**
 * PHP Executor
 *
 * Executes PHP commands
 *
 * @package MemberPress AI Assistant
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * PHP Executor Class
 */
class MPAI_PHP_Executor {
    /**
     * Logger instance
     *
     * @var object
     */
    private $logger;

    /**
     * Execution timeout in seconds
     *
     * @var int
     */
    private $timeout = 10;

    /**
     * Constructor
     */
    public function __construct() {
        // Initialize logger
        $this->logger = $this->get_default_logger();
    }

    /**
     * Get default logger
     *
     * @return object Default logger
     */
    private function get_default_logger() {
        return (object) [
            'info'    => function( $message ) { error_log( 'MPAI PHP INFO: ' . $message ); },
            'warning' => function( $message ) { error_log( 'MPAI PHP WARNING: ' . $message ); },
            'error'   => function( $message ) { error_log( 'MPAI PHP ERROR: ' . $message ); },
        ];
    }

    /**
     * Execute a PHP command
     *
     * @param string $command Command to execute
     * @param array $parameters Additional parameters
     * @return array Execution result
     */
    public function execute($command, $parameters = []) {
        try {
            $this->logger->info('Executing PHP command: ' . $command);
            
            // Set custom timeout if provided
            if (isset($parameters['timeout'])) {
                $this->timeout = min((int)$parameters['timeout'], 30); // Max 30 seconds
            }
            
            // For PHP version queries, use specialized method
            if ($this->is_php_version_query($command)) {
                return $this->get_php_version_info();
            }
            
            // Build the command
            $php_command = $this->build_command($command, $parameters);
            
            // Execute the command
            $output = [];
            $return_var = 0;
            exec($php_command, $output, $return_var);
            
            // Handle the result
            if ($return_var !== 0) {
                $this->logger->error('Command failed with code ' . $return_var . ': ' . implode("\n", $output));
                return [
                    'success' => false,
                    'output' => implode("\n", $output),
                    'return_code' => $return_var,
                    'command' => $command
                ];
            }
            
            return [
                'success' => true,
                'output' => implode("\n", $output),
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
            '/phpinfo/i'
        ];
        
        foreach ($php_version_patterns as $pattern) {
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
     * Build a PHP command with proper parameters and escaping
     *
     * @param string $command Base command
     * @param array $parameters Additional parameters
     * @return string Full command line
     */
    private function build_command($command, $parameters = []) {
        // Ensure command starts with php
        if (strpos($command, 'php ') !== 0) {
            $command = 'php ' . $command;
        }
        
        // Escape the command
        $escaped_command = escapeshellcmd($command);
        
        // Add timeout
        $timeout = isset($parameters['timeout']) ? min((int)$parameters['timeout'], 30) : $this->timeout;
        $full_command = "timeout {$timeout}s {$escaped_command}";
        
        return $full_command;
    }
}