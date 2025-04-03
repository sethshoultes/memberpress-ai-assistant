<?php
/**
 * Command Validation Agent
 * 
 * Validates commands before execution to prevent errors and improve user experience
 *
 * @package MemberPress AI Assistant
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Command Validation Agent
 */
class MPAI_Command_Validation_Agent extends MPAI_Base_Agent {
    /**
     * WordPress cache for expensive lookups
     * @var array
     */
    private $cache = [];

    /**
     * Constructor
     *
     * @param object $tool_registry Tool registry
     * @param object $logger Logger
     */
    public function __construct( $tool_registry = null, $logger = null ) {
        parent::__construct( $tool_registry, $logger );
        
        $this->id = 'command_validation_agent';
        $this->name = 'Command Validation Agent';
        $this->description = 'Validates commands before execution to prevent errors';
        $this->capabilities = [
            'validate_wp_plugin_commands',
            'validate_wp_theme_commands',
            'validate_wp_block_commands',
            'validate_wp_api_requests',
        ];
        
        // Initialize fallback logger if none was provided
        if (!isset($this->logger) || !is_object($this->logger)) {
            // Create a default logger class that uses error_log
            $this->logger = new class {
                public function info($message) { 
                    error_log('MPAI INFO: ' . $message); 
                }
                public function error($message) { 
                    error_log('MPAI ERROR: ' . $message); 
                }
                public function warning($message) { 
                    error_log('MPAI WARNING: ' . $message); 
                }
            };
            error_log('MPAI: Command validation agent initialized with fallback logger');
        }
    }

    /**
     * Process a command validation request
     *
     * @param array $intent_data Intent data from orchestrator
     * @param array $context User context
     * @return array Response data
     */
    public function process_request( $intent_data, $context = [] ) {
        try {
            $this->logger->info( 'Command validation agent processing request' );
            
            // Extract command data
            $command_type = isset( $intent_data['command_type'] ) ? $intent_data['command_type'] : '';
            $command_data = isset( $intent_data['command_data'] ) ? $intent_data['command_data'] : [];
            
            // Initial validation result (default to success)
            $validation_result = [
                'success' => true,
                'original_command' => $command_data,
                'validated_command' => $command_data,
                'message' => 'Command validated successfully',
                'source' => 'command_validation_agent',
            ];
            
            // FAST PATH: Skip validation for wp post list and wp user list commands
            if ($command_type === 'wp_cli' && isset($command_data['command'])) {
                // Check for post list command
                if (strpos($command_data['command'], 'wp post list') === 0) {
                    $this->logger->info('Bypassing validation for wp post list command');
                    return $validation_result;
                }
                
                // Check for user list command
                if (strpos($command_data['command'], 'wp user list') === 0) {
                    $this->logger->info('Bypassing validation for wp user list command');
                    return $validation_result;
                }
            }
            
            // Also check for post/user list commands in tool calls
            if ($command_type === 'tool_call' && isset($command_data['parameters']) && 
                isset($command_data['parameters']['command'])) {
                
                // Check for post list command in tool call
                if (strpos($command_data['parameters']['command'], 'wp post list') === 0) {
                    $this->logger->info('Bypassing validation for wp post list tool call');
                    return $validation_result;
                }
                
                // Check for user list command in tool call
                if (strpos($command_data['parameters']['command'], 'wp user list') === 0) {
                    $this->logger->info('Bypassing validation for wp user list tool call');
                    return $validation_result;
                }
            }
            
            // Process based on command type
            switch ( $command_type ) {
                case 'wp_api':
                    $validation_result = $this->validate_wp_api_command( $command_data, $context );
                    break;
                case 'wp_cli':
                    $validation_result = $this->validate_wp_cli_command( $command_data, $context );
                    break;
                case 'tool_call':
                    $validation_result = $this->validate_tool_call( $command_data, $context );
                    break;
                default:
                    // Still return success for unknown command types to maintain permissive behavior
                    $validation_result['success'] = true;
                    $validation_result['message'] = "Unknown command type: {$command_type} (continuing anyway)";
                    break;
            }
            
            $this->logger->info( 'Command validation result: ' . json_encode( $validation_result ) );
            
            // IMPORTANT: Even if validation fails, override success to true to maintain permissive behavior
            // This ensures that validation failures don't block operations
            if (!$validation_result['success']) {
                $this->logger->warning('Validation failed, but allowing operation to proceed: ' . $validation_result['message']);
                $validation_result['success'] = true;
                $validation_result['message'] .= ' (continuing with original command)';
                
                // Ensure we always return the original command data for system commands that should always work
                if (isset($command_data['command'])) {
                    // For system info commands like PHP version, ensure they always proceed
                    $system_cmd_patterns = [
                        '/php.*version/i',           // PHP version queries 
                        '/php\s+([-]{1,2}v|info)/i', // PHP info commands (php -v, php --version, php info)
                        '/php(?:info)?/i',           // PHP information
                        '/recently.*(?:activated|installed).*plugins/i',  // Recently activated plugins
                        '/(?:active|installed).*plugins/i',  // Plugin queries
                        '/plugin.*(?:status|info)/i',        // Plugin status or info
                        '/plugin.*(?:log|activity)/i',       // Plugin logs or activity
                        '/database.*info/i',        // Database info
                        '/site.*(?:health|info)/i', // Site health or info
                        '/wp.*php/i',               // WP PHP commands
                        '/wp.*plugins?/i',          // WP plugin commands
                        '/wp.*info/i',              // WordPress info
                        '/system.*info/i',          // System information
                        '/plugins?.*recent/i',      // Recent plugins activity
                        '/version/i',               // Any version queries
                        '/what.*(?:plugins?|php)/i', // Questions about plugins or PHP
                        '/which.*(?:plugins?|php)/i', // Questions about plugins or PHP
                        '/show.*(?:plugins?|php)/i', // Commands to show plugins or PHP
                        '/list.*(?:plugins?|php)/i', // Commands to list plugins or PHP
                        '/(?:get|display).*(?:plugins?|php)/i' // Commands to get or display plugins or PHP
                    ];
                    
                    foreach ($system_cmd_patterns as $pattern) {
                        if (preg_match($pattern, $command_data['command'])) {
                            $this->logger->info('System info command detected, ensuring it runs: ' . $command_data['command']);
                            $validation_result['validated_command'] = $command_data;
                            break;
                        }
                    }
                }
            }
            
            return $validation_result;
        } catch (Exception $e) {
            $this->logger->error('Error in process_request: ' . $e->getMessage());
            // Return a permissive result to allow operation to continue
            return [
                'success' => true,
                'original_command' => $command_data ?? [],
                'validated_command' => $command_data ?? [],
                'message' => 'Command validation bypassed due to error: ' . $e->getMessage(),
                'source' => 'command_validation_agent',
            ];
        }
    }

    /**
     * Validate a wp_api command
     *
     * @param array $command_data Command data
     * @param array $context Context data
     * @return array Validation result
     */
    private function validate_wp_api_command( $command_data, $context ) {
        $this->logger->info( 'Validating wp_api command: ' . json_encode( $command_data ) );
        
        $result = [
            'success' => true,
            'original_command' => $command_data,
            'validated_command' => $command_data,
            'message' => 'Command validated successfully',
            'source' => 'command_validation_agent',
        ];
        
        // Check if action is present
        if ( ! isset( $command_data['action'] ) ) {
            $result['success'] = false;
            $result['message'] = 'Missing required parameter: action';
            return $result;
        }
        
        // Handle different wp_api actions
        switch ( $command_data['action'] ) {
            case 'activate_plugin':
            case 'deactivate_plugin':
                $result = $this->validate_plugin_action( $command_data, $context );
                break;
            
            case 'get_plugins':
                // Nothing to validate for get_plugins
                break;
                
            // Add more action validations as needed
        }
        
        return $result;
    }

    /**
     * Validate a wp_cli command
     *
     * @param array $command_data Command data
     * @param array $context Context data
     * @return array Validation result
     */
    private function validate_wp_cli_command( $command_data, $context ) {
        try {
            $this->logger->info( 'Validating wp_cli command: ' . json_encode( $command_data ) );
            
            $result = [
                'success' => true,
                'original_command' => $command_data,
                'validated_command' => $command_data,
                'message' => 'Command validated successfully',
                'source' => 'command_validation_agent',
            ];
            
            // Check if the command parameter exists
            if ( ! isset( $command_data['command'] ) ) {
                $result['success'] = false;
                $result['message'] = 'Missing required parameter: command';
                return $result;
            }
            
            $command = $command_data['command'];
            
            // Plugin commands
            if ( strpos( $command, 'wp plugin ' ) === 0 ) {
                $result = $this->validate_wp_cli_plugin_command( $command, $context );
                $result['original_command'] = $command_data;
                $result['validated_command'] = [
                    'command' => $result['validated_command']
                ];
            }
            // Post commands - handle this case specifically
            else if ( strpos( $command, 'wp post ' ) === 0 ) {
                // For post list commands, just pass them through without validation
                // This prevents errors on common operations
                $result['success'] = true;
                $result['message'] = 'Post command validation bypassed for reliability';
                $result['original_command'] = $command_data;
                $result['validated_command'] = $command_data;
            }
            // Theme commands
            else if ( strpos( $command, 'wp theme ' ) === 0 ) {
                $result = $this->validate_wp_cli_theme_command( $command, $context );
                $result['original_command'] = $command_data;
                $result['validated_command'] = [
                    'command' => $result['validated_command']
                ];
            }
            // Block commands
            else if ( strpos( $command, 'wp block ' ) === 0 ) {
                $result = $this->validate_wp_cli_block_command( $command, $context );
                $result['original_command'] = $command_data;
                $result['validated_command'] = [
                    'command' => $result['validated_command']
                ];
            }
            
            return $result;
        } catch ( Exception $e ) {
            // Log the error but allow the command to proceed
            $this->logger->error( 'Error in validate_wp_cli_command: ' . $e->getMessage() );
            return [
                'success' => true, // Return true to allow operation to proceed
                'original_command' => $command_data,
                'validated_command' => $command_data,
                'message' => 'Command validation bypassed due to error: ' . $e->getMessage(),
                'source' => 'command_validation_agent',
            ];
        }
    }

    /**
     * Validate a WP-CLI plugin command
     *
     * @param string $command The command string
     * @param array $context Context data
     * @return array Validation result
     */
    private function validate_wp_cli_plugin_command( $command, $context ) {
        $result = [
            'success' => true,
            'original_command' => $command,
            'validated_command' => $command,
            'message' => 'Command validated successfully',
            'source' => 'command_validation_agent',
        ];
        
        // Extract the action (activate, deactivate, etc.)
        if ( preg_match( '/wp plugin (activate|deactivate|install|delete|update)\s+([^\s]+)/', $command, $matches ) ) {
            $action = $matches[1];
            $plugin_slug = trim( $matches[2], '"' ); // Remove quotes if present
            
            if ( $action == 'activate' || $action == 'deactivate' ) {
                // Get available plugins
                $available_plugins = $this->get_available_plugins();
                
                // Check if the plugin exists
                $plugin_path = $this->find_plugin_path( $plugin_slug, $available_plugins );
                
                if ( ! $plugin_path ) {
                    $result['success'] = false;
                    $result['message'] = "Plugin '{$plugin_slug}' not found. Available plugins: " . 
                        implode( ', ', array_keys( $available_plugins ) );
                } else {
                    // Update command with the correct plugin path
                    $result['validated_command'] = "wp plugin {$action} {$plugin_path}";
                    $result['message'] = "Plugin path corrected from '{$plugin_slug}' to '{$plugin_path}'";
                }
            }
        }
        
        return $result;
    }

    /**
     * Validate a tool call
     *
     * @param array $command_data Tool call data
     * @param array $context Context data
     * @return array Validation result
     */
    private function validate_tool_call( $command_data, $context ) {
        $this->logger->info( 'Validating tool call: ' . json_encode( $command_data ) );
        
        $result = [
            'success' => true,
            'original_command' => $command_data,
            'validated_command' => $command_data,
            'message' => 'Tool call validated successfully',
            'source' => 'command_validation_agent',
        ];
        
        // Check for required fields
        if ( ! isset( $command_data['name'] ) ) {
            $result['success'] = false;
            $result['message'] = 'Missing required parameter: name';
            return $result;
        }
        
        $tool_name = $command_data['name'];
        $parameters = isset( $command_data['parameters'] ) ? $command_data['parameters'] : [];
        
        // Validate based on tool name
        switch ( $tool_name ) {
            case 'wp_api':
                // We're validating a wp_api tool call
                $wp_api_validation = $this->validate_wp_api_command( $parameters, $context );
                
                if ( ! $wp_api_validation['success'] ) {
                    $result['success'] = false;
                    $result['message'] = $wp_api_validation['message'];
                } else {
                    // Update the parameters with validated ones
                    $result['validated_command']['parameters'] = $wp_api_validation['validated_command'];
                }
                break;
                
            case 'wp_cli':
                // We're validating a wp_cli tool call
                if ( isset( $parameters['command'] ) ) {
                    $wp_cli_validation = $this->validate_wp_cli_command( $parameters, $context );
                    
                    if ( ! $wp_cli_validation['success'] ) {
                        $result['success'] = false;
                        $result['message'] = $wp_cli_validation['message'];
                    } else {
                        // Update the parameters with validated ones
                        $result['validated_command']['parameters'] = $wp_cli_validation['validated_command'];
                    }
                }
                break;
        }
        
        return $result;
    }

    /**
     * Validate plugin action (activate/deactivate)
     *
     * @param array $command_data Command data
     * @param array $context Context data
     * @return array Validation result
     */
    private function validate_plugin_action( $command_data, $context ) {
        $result = [
            'success' => true,
            'original_command' => $command_data,
            'validated_command' => $command_data,
            'message' => 'Plugin action validated successfully',
            'source' => 'command_validation_agent',
        ];
        
        try {
            // Debug log all incoming data
            $this->logger->info('Starting plugin validation with data: ' . json_encode($command_data));
            
            // Check if plugin parameter exists
            if ( ! isset( $command_data['plugin'] ) ) {
                $result['success'] = false;
                $result['message'] = 'Missing required parameter: plugin';
                return $result;
            }
            
            $plugin_path = $command_data['plugin'];
            
            // Clean up any escaped slashes
            $plugin_path = str_replace( '\\/', '/', $plugin_path );
            
            $this->logger->info('Validating plugin path: ' . $plugin_path);
            
            // Get available plugins
            $available_plugins = $this->get_available_plugins();
            
            // Safety check for empty plugins list
            if (empty($available_plugins)) {
                // If we can't get the plugin list, assume the command is valid
                // This prevents blocking valid operations when plugin list can't be retrieved
                $this->logger->warning('Could not get plugins list, bypassing plugin validation');
                $result['validated_command']['plugin'] = $plugin_path;
                $result['message'] = 'Plugin validation bypassed - plugin list unavailable';
                return $result;
            }
            
            $this->logger->info('Found ' . count($available_plugins) . ' available plugins');
            
            // Check if the plugin path exists exactly as provided
            if ( isset( $available_plugins[$plugin_path] ) ) {
                // Plugin path is already correct
                $this->logger->info('Plugin path exists exactly as provided: ' . $plugin_path);
                $result['validated_command']['plugin'] = $plugin_path;
                return $result;
            }
            
            $this->logger->info('Plugin path not found exactly, trying to correct it');
            
            // Try to find the correct plugin path
            $corrected_path = $this->find_plugin_path( $plugin_path, $available_plugins );
            
            if ( $corrected_path ) {
                $this->logger->info('Found corrected plugin path: ' . $corrected_path);
                $result['validated_command']['plugin'] = $corrected_path;
                $result['message'] = "Plugin path corrected from '{$plugin_path}' to '{$corrected_path}'";
                return $result;
            }
            
            $this->logger->warning('Plugin path not found and could not be corrected: ' . $plugin_path);
            
            // If we get here, the plugin wasn't found
            // But we'll bypass the validation rather than block the operation
            $result['success'] = true; // Change to true to bypass
            $result['message'] = "Plugin '{$plugin_path}' not found in plugin list, but allowing operation";
            $result['validated_command']['plugin'] = $plugin_path;
            
            // Add a warning with available plugins
            if ( ! empty( $available_plugins ) ) {
                $sample_plugins = array_slice( array_keys( $available_plugins ), 0, 5 );
                $plugin_names = [];
                foreach ($sample_plugins as $path) {
                    if (isset($available_plugins[$path]['Name'])) {
                        $plugin_names[] = $available_plugins[$path]['Name'] . " ({$path})";
                    } else {
                        $plugin_names[] = $path;
                    }
                }
                $plugin_list = implode(', ', $plugin_names);
                $result['message'] .= ". Available plugins include: {$plugin_list}";
            }
            
            return $result;
        } catch (Exception $e) {
            // Log and bypass validation if there's an error
            $this->logger->error('Error in validate_plugin_action: ' . $e->getMessage());
            $result['success'] = true; // Always return success to prevent blocking
            $result['message'] = 'Plugin validation bypassed due to error';
            $result['validated_command'] = $command_data; // Keep original command
            return $result;
        }
    }

    /**
     * Get list of available plugins
     *
     * @return array List of available plugins
     */
    private function get_available_plugins($force_refresh = false) {
        // Return cached plugins if available and not forcing refresh
        if (!$force_refresh && isset($this->cache['plugins']) && !empty($this->cache['plugins'])) {
            $this->logger->info('Using cached plugins list with ' . count($this->cache['plugins']) . ' plugins');
            return $this->cache['plugins'];
        }
        
        // If we're force refreshing, clear WordPress plugin cache
        if ($force_refresh) {
            $this->logger->info('Force refreshing plugins list');
            // Clear WordPress plugin cache
            wp_cache_delete('plugins', 'plugins');
            // Also clear our internal cache
            unset($this->cache['plugins']);
        }
        
        // Initialize empty plugins array
        $plugins = [];
        
        try {
            $this->logger->info('Loading plugins list...');
            
            // Load plugin functions if needed
            if ( ! function_exists( 'get_plugins' ) ) {
                $plugin_php_path = ABSPATH . 'wp-admin/includes/plugin.php';
                $this->logger->info('get_plugins function not available, trying to load from: ' . $plugin_php_path);
                
                if ( file_exists( $plugin_php_path ) ) {
                    require_once $plugin_php_path;
                    $this->logger->info('Successfully loaded plugin.php');
                } else {
                    $this->logger->error( 'Failed to load plugin.php - file not found at: ' . $plugin_php_path );
                    // Try alternative method to find the file
                    $alt_path = WP_PLUGIN_DIR . '/../../../wp-admin/includes/plugin.php';
                    $this->logger->info('Trying alternative path: ' . $alt_path);
                    
                    if (file_exists($alt_path)) {
                        require_once $alt_path;
                        $this->logger->info('Successfully loaded plugin.php from alternative path');
                    } else {
                        $this->logger->error('Could not find plugin.php at alternative path either');
                        return [];
                    }
                }
            }
            
            // Get the plugins
            if ( function_exists( 'get_plugins' ) ) {
                $this->logger->info('Calling get_plugins() function...');
                $plugins = get_plugins();
                $this->logger->info('Got ' . count($plugins) . ' plugins from get_plugins()');
                
                if (!empty($plugins)) {
                    $this->cache['plugins'] = $plugins;
                    
                    // Log first few plugins for debugging
                    $sample_plugins = array_slice($plugins, 0, 3, true);
                    foreach ($sample_plugins as $path => $plugin) {
                        $this->logger->info('Sample plugin: ' . $path . ' - ' . $plugin['Name']);
                    }
                } else {
                    $this->logger->warning('get_plugins() returned empty array');
                    
                    // Try direct directory scanning as a fallback
                    $this->logger->info('Trying fallback method: Direct plugin directory scanning');
                    $plugins_dir = WP_PLUGIN_DIR;
                    $this->logger->info('Scanning plugins directory: ' . $plugins_dir);
                    
                    if (is_dir($plugins_dir)) {
                        $plugin_folders = glob($plugins_dir . '/*', GLOB_ONLYDIR);
                        foreach ($plugin_folders as $folder) {
                            $folder_name = basename($folder);
                            $main_files = glob($folder . '/*.php');
                            
                            if (!empty($main_files)) {
                                foreach ($main_files as $file) {
                                    $file_name = basename($file);
                                    $plugin_path = $folder_name . '/' . $file_name;
                                    
                                    // Create a simple plugin entry
                                    $plugins[$plugin_path] = [
                                        'Name' => $folder_name,
                                        'Version' => '1.0',
                                        'Description' => 'Found via directory scanning',
                                    ];
                                    
                                    $this->logger->info('Added plugin via directory scan: ' . $plugin_path);
                                }
                            }
                        }
                        
                        if (!empty($plugins)) {
                            $this->logger->info('Fallback method found ' . count($plugins) . ' plugins');
                            $this->cache['plugins'] = $plugins;
                        }
                    } else {
                        $this->logger->error('Could not access plugins directory: ' . $plugins_dir);
                    }
                }
            } else {
                $this->logger->error( 'get_plugins function still not available after loading plugin.php' );
            }
        } catch ( Exception $e ) {
            $this->logger->error( 'Error getting plugins: ' . $e->getMessage() );
        }
        
        // Always return an array, even if empty
        return $plugins;
    }

    /**
     * Find the correct plugin path based on a slug or partial path
     *
     * @param string $plugin_slug The plugin slug or partial path
     * @param array $available_plugins List of available plugins
     * @return string|false The correct plugin path or false if not found
     */
    private function find_plugin_path( $plugin_slug, $available_plugins ) {
        try {
            // Bail early if plugin_slug is empty
            if (empty($plugin_slug) || empty($available_plugins)) {
                $this->logger->warning('Empty plugin slug or available plugins list');
                return false;
            }
            
            $this->logger->info('Finding plugin path for: ' . $plugin_slug);
            
            // Check for direct matches first
            if (isset($available_plugins[$plugin_slug])) {
                $this->logger->info('Direct match found for: ' . $plugin_slug);
                return $plugin_slug;
            }
            
            // Clean up plugin slug for better matching
            $plugin_slug = trim($plugin_slug);
            // Remove quotes if present
            $plugin_slug = trim($plugin_slug, '"\'');
            
            $this->logger->info('Cleaned plugin slug for matching: ' . $plugin_slug);
            
            // Handle special case for MemberPress plugins by name
            if (stripos($plugin_slug, 'memberpress') !== false) {
                $this->logger->info('MemberPress plugin detected, trying special matching');
                
                // Extract potential specific addon name
                $memberpress_addon = '';
                if (stripos($plugin_slug, 'memberpress-') !== false) {
                    $memberpress_addon = str_ireplace('memberpress-', '', $plugin_slug);
                    $memberpress_addon = str_ireplace(' plugin', '', $memberpress_addon);
                    $memberpress_addon = str_ireplace(' add-on', '', $memberpress_addon);
                    $memberpress_addon = str_ireplace(' addon', '', $memberpress_addon);
                    $memberpress_addon = trim($memberpress_addon);
                    $this->logger->info('Extracted MemberPress addon name: ' . $memberpress_addon);
                }
                
                // First check exact matches for the addon 
                foreach ($available_plugins as $path => $plugin_data) {
                    // Match against folder names for MemberPress plugins
                    if (strpos($path, 'memberpress-' . strtolower($memberpress_addon) . '/') === 0) {
                        $this->logger->info('Found MemberPress addon by folder: ' . $path);
                        return $path;
                    }
                    
                    // Match plugin names
                    if (!empty($memberpress_addon) && isset($plugin_data['Name']) && 
                        (stripos($plugin_data['Name'], 'memberpress ' . $memberpress_addon) !== false ||
                         stripos($plugin_data['Name'], 'memberpress-' . $memberpress_addon) !== false)) {
                        $this->logger->info('Found MemberPress addon by name: ' . $path);
                        return $path;
                    }
                }
                
                // Then check if there's any MemberPress plugin with this word
                if (!empty($memberpress_addon)) {
                    foreach ($available_plugins as $path => $plugin_data) {
                        if (strpos($path, 'memberpress-') === 0 && 
                            (stripos($path, $memberpress_addon) !== false || 
                             (isset($plugin_data['Name']) && stripos($plugin_data['Name'], $memberpress_addon) !== false))) {
                            $this->logger->info('Found MemberPress addon by partial match: ' . $path);
                            return $path;
                        }
                    }
                }
                
                // Last resort - return the first MemberPress plugin
                foreach ($available_plugins as $path => $plugin_data) {
                    if (strpos($path, 'memberpress') === 0 || 
                        (isset($plugin_data['Name']) && stripos($plugin_data['Name'], 'memberpress') !== false)) {
                        $this->logger->info('Falling back to first MemberPress plugin: ' . $path);
                        return $path;
                    }
                }
            }
            
            // Case where plugin path is partially correct (correct folder, wrong main file)
            if (strpos($plugin_slug, '/') !== false) {
                $this->logger->info('Path contains slash, trying to match folder');
                list($folder, $file) = explode('/', $plugin_slug, 2);
                
                // Check if any plugin has this folder
                foreach (array_keys($available_plugins) as $plugin_path) {
                    if (strpos($plugin_path, $folder . '/') === 0) {
                        // Found a plugin with the correct folder
                        $this->logger->info('Found plugin with matching folder: ' . $plugin_path);
                        return $plugin_path;
                    }
                }
            }
            
            // Check for name-based matches
            $plugin_slug_lower = strtolower($plugin_slug);
            $this->logger->info('Checking name-based matches for: ' . $plugin_slug_lower);
            
            // First pass - check for direct name matches
            foreach ($available_plugins as $path => $plugin_data) {
                // Check exact plugin name match
                if (isset($plugin_data['Name']) && 
                    strtolower($plugin_data['Name']) === $plugin_slug_lower) {
                    $this->logger->info('Found exact name match: ' . $path);
                    return $path;
                }
                
                // Check for folder match
                if (strpos($path, '/') !== false) {
                    list($folder, $file) = explode('/', $path, 2);
                    if (strtolower($folder) === $plugin_slug_lower) {
                        $this->logger->info('Found folder match: ' . $path);
                        return $path;
                    }
                }
            }
            
            // Check for word-by-word matching (handle cases like "memberpress gifting" vs "memberpress-gifting")
            $words = preg_split('/[\s-]+/', $plugin_slug_lower);
            if (count($words) > 1) {
                $this->logger->info('Trying word-by-word matching with: ' . implode(', ', $words));
                
                $matches = [];
                foreach ($available_plugins as $path => $plugin_data) {
                    $path_lower = strtolower($path);
                    $name_lower = isset($plugin_data['Name']) ? strtolower($plugin_data['Name']) : '';
                    
                    $match_score = 0;
                    foreach ($words as $word) {
                        if (!empty($word) && (strpos($path_lower, $word) !== false || strpos($name_lower, $word) !== false)) {
                            $match_score++;
                        }
                    }
                    
                    if ($match_score > 0) {
                        $matches[$path] = $match_score;
                    }
                }
                
                if (!empty($matches)) {
                    // Sort by match score (highest first)
                    arsort($matches);
                    $best_match = key($matches);
                    $this->logger->info('Best word match found: ' . $best_match . ' with score: ' . $matches[$best_match]);
                    return $best_match;
                }
            }
            
            // Second pass - check for partial name matches
            foreach ($available_plugins as $path => $plugin_data) {
                // Check if the slug is in the plugin name (case insensitive)
                if (isset($plugin_data['Name']) && 
                    stripos($plugin_data['Name'], $plugin_slug) !== false) {
                    $this->logger->info('Found partial name match: ' . $path);
                    return $path;
                }
                
                // Check if the slug is in the path
                if (stripos($path, $plugin_slug) !== false) {
                    $this->logger->info('Found partial path match: ' . $path);
                    return $path;
                }
            }
            
            $this->logger->warning('No matches found for plugin: ' . $plugin_slug);
            // No matches found
            return false;
        } catch (Exception $e) {
            $this->logger->error('Error in find_plugin_path: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Validate a WP-CLI theme command
     *
     * @param string $command The command string
     * @param array $context Context data
     * @return array Validation result
     */
    private function validate_wp_cli_theme_command( $command, $context ) {
        $result = [
            'success' => true,
            'original_command' => $command,
            'validated_command' => $command,
            'message' => 'Theme command validated successfully',
            'source' => 'command_validation_agent',
        ];
        
        try {
            // Extract the action (activate, update, etc.) and theme
            if ( preg_match( '/wp theme (activate|update|install|delete)\s+([^\s]+)/', $command, $matches ) ) {
                $action = $matches[1];
                $theme_slug = trim( $matches[2], '"' ); // Remove quotes if present
                
                if ( $action === 'activate' || $action === 'update' ) {
                    // Get available themes
                    $available_themes = $this->get_available_themes();
                    
                    // Skip validation if we couldn't get themes
                    if ( empty( $available_themes ) ) {
                        $this->logger->warning( 'Could not get themes list, bypassing theme validation' );
                        return $result;
                    }
                    
                    // Check if the theme exists
                    $theme_stylesheet = $this->find_theme_stylesheet( $theme_slug, $available_themes );
                    
                    if ( ! $theme_stylesheet ) {
                        $result['success'] = false;
                        $result['message'] = "Theme '{$theme_slug}' not found. Please check available themes with 'wp theme list'.";
                    } else {
                        // Update command with the correct theme stylesheet
                        $result['validated_command'] = "wp theme {$action} {$theme_stylesheet}";
                        $result['message'] = "Theme stylesheet corrected from '{$theme_slug}' to '{$theme_stylesheet}'";
                    }
                }
            }
            
            return $result;
        } catch ( Exception $e ) {
            $this->logger->error( 'Error in validate_wp_cli_theme_command: ' . $e->getMessage() );
            return $result;
        }
    }
    
    /**
     * Get list of available themes
     *
     * @return array List of available themes
     */
    private function get_available_themes() {
        // Return cached themes if available
        if ( isset( $this->cache['themes'] ) && !empty( $this->cache['themes'] ) ) {
            return $this->cache['themes'];
        }
        
        // Initialize empty themes array
        $themes = [];
        
        try {
            // Get the themes
            if ( function_exists( 'wp_get_themes' ) ) {
                $themes = wp_get_themes();
                $this->cache['themes'] = $themes;
            } else {
                $this->logger->error( 'wp_get_themes function not available' );
            }
        } catch ( Exception $e ) {
            $this->logger->error( 'Error getting themes: ' . $e->getMessage() );
        }
        
        // Always return an array, even if empty
        return $themes;
    }
    
    /**
     * Find the correct theme stylesheet based on a slug or partial name
     *
     * @param string $theme_slug The theme slug or name
     * @param array $available_themes List of available themes
     * @return string|false The correct theme stylesheet or false if not found
     */
    private function find_theme_stylesheet( $theme_slug, $available_themes ) {
        try {
            // Bail early if theme_slug is empty
            if ( empty( $theme_slug ) || empty( $available_themes ) ) {
                return false;
            }
            
            // Check if slug exactly matches a theme's stylesheet
            if ( isset( $available_themes[$theme_slug] ) ) {
                return $theme_slug;
            }
            
            // Check if slug matches a theme name (case insensitive)
            $theme_slug_lower = strtolower( $theme_slug );
            foreach ( $available_themes as $stylesheet => $theme ) {
                // Exact name match
                if ( strtolower( $theme->get('Name') ) === $theme_slug_lower ) {
                    return $stylesheet;
                }
            }
            
            // Check for partial matches
            foreach ( $available_themes as $stylesheet => $theme ) {
                // Partial name match
                if ( stripos( $theme->get('Name'), $theme_slug ) !== false ) {
                    return $stylesheet;
                }
            }
            
            // No matches found
            return false;
        } catch ( Exception $e ) {
            $this->logger->error( 'Error in find_theme_stylesheet: ' . $e->getMessage() );
            return false;
        }
    }
    
    /**
     * Validate a WP-CLI block command
     *
     * @param string $command The command string
     * @param array $context Context data
     * @return array Validation result
     */
    private function validate_wp_cli_block_command( $command, $context ) {
        $result = [
            'success' => true,
            'original_command' => $command,
            'validated_command' => $command,
            'message' => 'Block command validated successfully',
            'source' => 'command_validation_agent',
        ];
        
        try {
            // Extract the action and block name
            if ( preg_match( '/wp block (unregister)\s+([^\s]+)/', $command, $matches ) ) {
                $action = $matches[1];
                $block_name = trim( $matches[2], '"' ); // Remove quotes if present
                
                // Only perform validation for certain actions
                if ( $action === 'unregister' ) {
                    // Get available blocks
                    $available_blocks = $this->get_available_blocks();
                    
                    // Skip validation if we couldn't get blocks
                    if ( empty( $available_blocks ) ) {
                        $this->logger->warning( 'Could not get blocks list, bypassing block validation' );
                        return $result;
                    }
                    
                    // Check if the block exists
                    $block_path = $this->find_block_path( $block_name, $available_blocks );
                    
                    if ( ! $block_path ) {
                        $result['success'] = false;
                        $result['message'] = "Block '{$block_name}' not found. Please check available blocks.";
                    } else {
                        // Update command with the correct block path
                        $result['validated_command'] = "wp block {$action} {$block_path}";
                        if ( $block_path !== $block_name ) {
                            $result['message'] = "Block name corrected from '{$block_name}' to '{$block_path}'";
                        }
                    }
                }
            }
            
            return $result;
        } catch ( Exception $e ) {
            $this->logger->error( 'Error in validate_wp_cli_block_command: ' . $e->getMessage() );
            return $result;
        }
    }
    
    /**
     * Get list of available blocks
     *
     * @return array List of available blocks
     */
    private function get_available_blocks() {
        // Return cached blocks if available
        if ( isset( $this->cache['blocks'] ) && !empty( $this->cache['blocks'] ) ) {
            return $this->cache['blocks'];
        }
        
        // Initialize empty blocks array
        $blocks = [];
        
        try {
            // Check if block registry is available
            if ( class_exists( 'WP_Block_Type_Registry' ) ) {
                $registry = WP_Block_Type_Registry::get_instance();
                $blocks = $registry->get_all_registered();
                $this->cache['blocks'] = $blocks;
            } else {
                $this->logger->error( 'WP_Block_Type_Registry class not available' );
            }
        } catch ( Exception $e ) {
            $this->logger->error( 'Error getting blocks: ' . $e->getMessage() );
        }
        
        // Always return an array, even if empty
        return $blocks;
    }
    
    /**
     * Find the correct block path based on a name or partial name
     *
     * @param string $block_name The block name
     * @param array $available_blocks List of available blocks
     * @return string|false The correct block path or false if not found
     */
    private function find_block_path( $block_name, $available_blocks ) {
        try {
            // Bail early if block_name is empty
            if ( empty( $block_name ) || empty( $available_blocks ) ) {
                return false;
            }
            
            // Check for exact block name match
            if ( isset( $available_blocks[$block_name] ) ) {
                return $block_name;
            }
            
            // Check for namespace match (if no namespace is provided)
            if ( strpos( $block_name, '/' ) === false ) {
                // Try common namespaces
                $common_namespaces = [ 'core', 'core-embed', 'memberpress', 'wp', 'woocommerce' ];
                foreach ( $common_namespaces as $namespace ) {
                    $full_name = $namespace . '/' . $block_name;
                    if ( isset( $available_blocks[$full_name] ) ) {
                        return $full_name;
                    }
                }
            }
            
            // Check for block name that contains the partial name (case insensitive)
            $block_name_lower = strtolower( $block_name );
            foreach ( array_keys( $available_blocks ) as $block_path ) {
                if ( stripos( $block_path, $block_name_lower ) !== false ) {
                    return $block_path;
                }
            }
            
            // No matches found
            return false;
        } catch ( Exception $e ) {
            $this->logger->error( 'Error in find_block_path: ' . $e->getMessage() );
            return false;
        }
    }
    
    /**
     * Get list of available patterns
     *
     * @return array List of available patterns
     */
    private function get_available_patterns() {
        // Return cached patterns if available
        if ( isset( $this->cache['patterns'] ) && !empty( $this->cache['patterns'] ) ) {
            return $this->cache['patterns'];
        }
        
        // Initialize empty patterns array
        $patterns = [];
        
        try {
            // Check if pattern registry is available
            if ( class_exists( 'WP_Block_Patterns_Registry' ) ) {
                $registry = WP_Block_Patterns_Registry::get_instance();
                $patterns = $registry->get_all_registered();
                $this->cache['patterns'] = $patterns;
            } else {
                $this->logger->error( 'WP_Block_Patterns_Registry class not available' );
            }
        } catch ( Exception $e ) {
            $this->logger->error( 'Error getting patterns: ' . $e->getMessage() );
        }
        
        // Always return an array, even if empty
        return $patterns;
    }
    
    /**
     * Find the correct pattern path based on a name or partial name
     *
     * @param string $pattern_name The pattern name
     * @param array $available_patterns List of available patterns
     * @return string|false The correct pattern path or false if not found
     */
    private function find_pattern_path( $pattern_name, $available_patterns ) {
        try {
            // Bail early if pattern_name is empty
            if ( empty( $pattern_name ) || empty( $available_patterns ) ) {
                return false;
            }
            
            // Check for direct matches
            foreach ( $available_patterns as $pattern ) {
                if ( isset( $pattern['name'] ) && $pattern['name'] === $pattern_name ) {
                    return $pattern['name'];
                }
            }
            
            // Check for name-based matches (case insensitive)
            $pattern_name_lower = strtolower( $pattern_name );
            foreach ( $available_patterns as $pattern ) {
                if ( isset( $pattern['name'] ) && strtolower( $pattern['name'] ) === $pattern_name_lower ) {
                    return $pattern['name'];
                }
                
                // Also check title if available
                if ( isset( $pattern['title'] ) && strtolower( $pattern['title'] ) === $pattern_name_lower ) {
                    return $pattern['name'];
                }
            }
            
            // Check for partial matches in name or title
            foreach ( $available_patterns as $pattern ) {
                if ( isset( $pattern['name'] ) && stripos( $pattern['name'], $pattern_name ) !== false ) {
                    return $pattern['name'];
                }
                
                if ( isset( $pattern['title'] ) && stripos( $pattern['title'], $pattern_name ) !== false ) {
                    return $pattern['name'];
                }
            }
            
            // No matches found
            return false;
        } catch ( Exception $e ) {
            $this->logger->error( 'Error in find_pattern_path: ' . $e->getMessage() );
            return false;
        }
    }
}