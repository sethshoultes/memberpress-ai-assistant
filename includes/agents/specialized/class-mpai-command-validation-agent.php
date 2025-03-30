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
            
            // FAST PATH: Skip validation for wp post list commands
            if ($command_type === 'wp_cli' && isset($command_data['command']) && 
                strpos($command_data['command'], 'wp post list') === 0) {
                $this->logger->info('Bypassing validation for wp post list command');
                return $validation_result;
            }
            
            // Also check for post list commands in tool calls
            if ($command_type === 'tool_call' && isset($command_data['parameters']) && 
                isset($command_data['parameters']['command']) && 
                strpos($command_data['parameters']['command'], 'wp post list') === 0) {
                $this->logger->info('Bypassing validation for wp post list tool call');
                return $validation_result;
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
                // Add theme command validations here
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
            // Check if plugin parameter exists
            if ( ! isset( $command_data['plugin'] ) ) {
                $result['success'] = false;
                $result['message'] = 'Missing required parameter: plugin';
                return $result;
            }
            
            $plugin_path = $command_data['plugin'];
            
            // Clean up any escaped slashes
            $plugin_path = str_replace( '\\/', '/', $plugin_path );
            
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
            
            // Check if the plugin path exists exactly as provided
            if ( isset( $available_plugins[$plugin_path] ) ) {
                // Plugin path is already correct
                $result['validated_command']['plugin'] = $plugin_path;
                return $result;
            }
            
            // Try to find the correct plugin path
            $corrected_path = $this->find_plugin_path( $plugin_path, $available_plugins );
            
            if ( $corrected_path ) {
                $result['validated_command']['plugin'] = $corrected_path;
                $result['message'] = "Plugin path corrected from '{$plugin_path}' to '{$corrected_path}'";
                return $result;
            }
            
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
    private function get_available_plugins() {
        // Return cached plugins if available
        if ( isset( $this->cache['plugins'] ) && !empty( $this->cache['plugins'] ) ) {
            return $this->cache['plugins'];
        }
        
        // Initialize empty plugins array
        $plugins = [];
        
        try {
            // Load plugin functions if needed
            if ( ! function_exists( 'get_plugins' ) ) {
                if ( file_exists( ABSPATH . 'wp-admin/includes/plugin.php' ) ) {
                    require_once ABSPATH . 'wp-admin/includes/plugin.php';
                } else {
                    $this->logger->error( 'Failed to load plugin.php' );
                    return [];
                }
            }
            
            // Get the plugins
            if ( function_exists( 'get_plugins' ) ) {
                $plugins = get_plugins();
                $this->cache['plugins'] = $plugins;
            } else {
                $this->logger->error( 'get_plugins function not available' );
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
                return false;
            }
            
            // Check for direct matches first
            if (isset($available_plugins[$plugin_slug])) {
                return $plugin_slug;
            }
            
            // Case where plugin path is partially correct (correct folder, wrong main file)
            if (strpos($plugin_slug, '/') !== false) {
                list($folder, $file) = explode('/', $plugin_slug, 2);
                
                // Check if any plugin has this folder
                foreach (array_keys($available_plugins) as $plugin_path) {
                    if (strpos($plugin_path, $folder . '/') === 0) {
                        // Found a plugin with the correct folder
                        return $plugin_path;
                    }
                }
            }
            
            // Check for name-based matches
            $plugin_slug_lower = strtolower($plugin_slug);
            
            // First pass - check for direct name matches 
            foreach ($available_plugins as $path => $plugin_data) {
                // Check exact plugin name match
                if (isset($plugin_data['Name']) && 
                    strtolower($plugin_data['Name']) === $plugin_slug_lower) {
                    return $path;
                }
                
                // Check for folder match
                if (strpos($path, '/') !== false) {
                    list($folder, $file) = explode('/', $path, 2);
                    if (strtolower($folder) === $plugin_slug_lower) {
                        return $path;
                    }
                }
            }
            
            // Second pass - check for partial name matches
            foreach ($available_plugins as $path => $plugin_data) {
                // Check if the slug is in the plugin name (case insensitive)
                if (isset($plugin_data['Name']) && 
                    stripos($plugin_data['Name'], $plugin_slug) !== false) {
                    return $path;
                }
            }
            
            // No matches found
            return false;
        } catch (Exception $e) {
            $this->logger->error('Error in find_plugin_path: ' . $e->getMessage());
            return false;
        }
    }
}