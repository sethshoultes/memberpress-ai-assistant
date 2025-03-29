<?php
/**
 * WP-CLI Tool Class
 *
 * Provides access to WP-CLI functionality.
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * WP-CLI Tool for MemberPress AI Assistant
 */
class MPAI_WP_CLI_Tool extends MPAI_Base_Tool {
    /**
     * Allowed commands
     *
     * @var array
     */
    private $allowed_commands = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->name = 'WP-CLI Tool';
        $this->description = 'Provides access to WordPress CLI commands';
        
        // Load allowed commands from settings
        $this->allowed_commands = get_option('mpai_allowed_cli_commands', [
            'wp user list',
            'wp post list',
            'wp plugin list',
            'wp theme list',
            'wp option list',
            'wp help',
        ]);
    }
    
    /**
     * Execute the tool
     *
     * @param array $parameters Tool parameters
     * @return mixed Execution result
     */
    public function execute($parameters) {
        // Check for required parameters
        if (!isset($parameters['command'])) {
            throw new Exception('The command parameter is required');
        }
        
        // Check if CLI commands are enabled
        if (get_option('mpai_enable_cli_commands', false) !== true) {
            throw new Exception('WP-CLI commands are currently disabled in settings');
        }
        
        // Only administrators can execute CLI commands
        if (!current_user_can('manage_options')) {
            throw new Exception('Insufficient permissions to execute CLI commands');
        }
        
        $command = trim($parameters['command']);
        
        // Validate the command against allowed list
        if (!$this->validate_command($command)) {
            throw new Exception('This command is not allowed. Please contact your administrator if you need this functionality.');
        }
        
        // Execute the command
        $result = $this->execute_command($command);
        
        // Parse the output if requested
        $format = isset($parameters['format']) ? $parameters['format'] : 'plain';
        
        return $this->parse_command_output($result, $format);
    }
    
    /**
     * Validate a command against the allowed list
     *
     * @param string $command Command to validate
     * @return bool Whether the command is allowed
     */
    private function validate_command($command) {
        // Strip out any potentially harmful characters
        $sanitized_command = preg_replace('/[;&|`$><]/', '', $command);
        
        if ($sanitized_command !== $command) {
            return false; // Command contains disallowed characters
        }
        
        // Check against the allowed list
        foreach ($this->allowed_commands as $allowed_command) {
            if (strpos($command, $allowed_command) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Execute a WP-CLI command
     *
     * @param string $command Command to execute
     * @return string Command output
     */
    private function execute_command($command) {
        // Prepare the command for execution
        // Adding --skip-plugins and --skip-themes option to avoid conflicts
        if (strpos($command, '--skip-plugins') === false && strpos($command, '--skip-themes') === false) {
            $command .= ' --skip-plugins=memberpress-ai-assistant';
        }
        
        // Set a timeout for the command execution
        $timeout = isset($parameters['timeout']) ? intval($parameters['timeout']) : 30;
        
        // Prepare the command with proper escaping and timeout
        $full_command = 'timeout ' . $timeout . ' ' . $command . ' 2>&1';
        
        // Check if WP-CLI is available by checking if the command exists
        exec('command -v wp', $output, $return_var);
        if ($return_var !== 0) {
            throw new Exception('WP-CLI is not available on this system');
        }
        
        // Execute the command and capture output
        $output = shell_exec($full_command);
        
        if ($output === null) {
            throw new Exception('Command execution failed or timed out');
        }
        
        return $output;
    }
    
    /**
     * Parse command output into various formats
     *
     * @param string $output Command output
     * @param string $format Output format (plain, json, table)
     * @return mixed Parsed output
     */
    private function parse_command_output($output, $format = 'plain') {
        // For plain format, just return the output as is
        if ($format === 'plain') {
            return $output;
        }
        
        // For JSON format, attempt to parse as JSON
        if ($format === 'json') {
            // Try to detect if the output is already in JSON format
            if (substr(trim($output), 0, 1) === '{' && substr(trim($output), -1) === '}') {
                $json = json_decode($output, true);
                
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $json;
                }
            }
            
            // If not JSON, wrap it in a simple structure
            return array(
                'output' => $output,
                'format' => 'text'
            );
        }
        
        // For table format, attempt to parse as a table
        if ($format === 'table') {
            $lines = explode("\n", $output);
            $table = array();
            
            // Try to detect table headers
            if (count($lines) > 2) {
                $headers = preg_split('/\s+/', trim($lines[0]));
                
                // Process each line (skip the header and the separator line)
                for ($i = 2; $i < count($lines); $i++) {
                    if (empty(trim($lines[$i]))) {
                        continue; // Skip empty lines
                    }
                    
                    // This is a simple approach and may not work for all tables
                    $row = preg_split('/\s+/', trim($lines[$i]), count($headers));
                    
                    // Create associative array with headers as keys
                    $data = array();
                    for ($j = 0; $j < count($headers) && $j < count($row); $j++) {
                        $data[$headers[$j]] = $row[$j];
                    }
                    
                    $table[] = $data;
                }
                
                return $table;
            }
        }
        
        // Default to plain text for unsupported formats
        return $output;
    }
    
    /**
     * Get parameters schema
     *
     * @return array Parameters schema
     */
    public function get_parameters_schema() {
        return [
            'type' => 'object',
            'properties' => [
                'command' => [
                    'type' => 'string',
                    'description' => 'The WP-CLI command to execute'
                ],
                'timeout' => [
                    'type' => 'integer',
                    'description' => 'Command execution timeout in seconds',
                    'minimum' => 1,
                    'maximum' => 60
                ],
                'format' => [
                    'type' => 'string',
                    'description' => 'Output format (plain, json, table)',
                    'enum' => ['plain', 'json', 'table']
                ]
            ],
            'required' => ['command']
        ];
    }
}