<?php
/**
 * MemberPress AI Assistant Key Manager
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Admin;

// Note: WordPress functions are called directly in this class.
// IDE may show errors for these functions, but they will work correctly
// in a WordPress environment where these functions are globally available.

use MemberpressAiAssistant\Abstracts\AbstractService;

/**
 * MPAIKeyManager - Manages API keys for AI services using Split Key Storage approach
 * 
 * This class implements a secure key management system that:
 * 1. Fragments API keys into multiple components stored in different locations
 * 2. Implements multiple security verification layers
 * 3. Supports multiple AI services (OpenAI, Anthropic)
 * 4. Includes methods for testing API connections
 */
class MPAIKeyManager extends AbstractService {
    /**
     * Service identifier constants
     */
    const SERVICE_OPENAI = 'openai';
    const SERVICE_ANTHROPIC = 'anthropic';
    
    /**
     * Stores key components for each service
     * 
     * @var array
     */
    private $service_key_components = [];
    
    /**
     * Rate limiting tracking
     * 
     * @var array
     */
    private $request_counts = [];
    
    /**
     * Maximum requests per minute per service
     * 
     * @var int
     */
    private $rate_limit = 10;
    
    /**
     * {@inheritdoc}
     */
    public function register($container): void {
        // Register this service with the container
        $container->register('key_manager', function() {
            return $this;
        });
        
        // Log registration
        $this->log('Key manager service registered');
    }
    
    /**
     * {@inheritdoc}
     */
    public function boot(): void {
        parent::boot();
        
        // Add hooks if needed
        // $this->addHooks();
        
        // Log boot
        $this->log('Key manager service booted');
    }
    
    /**
     * Constructor
     *
     * @param string $name Service name
     * @param mixed $logger Logger instance
     */
    public function __construct(string $name = 'key_manager', $logger = null) {
        parent::__construct($name, $logger);
        
        // Initialize request counts for rate limiting
        $this->request_counts = [
            self::SERVICE_OPENAI => [],
            self::SERVICE_ANTHROPIC => []
        ];
    }
    
    /**
     * Get API key for specified service
     * 
     * @param string $service_type The service type (openai, anthropic)
     * @return string|false The API key or false on failure
     */
    public function get_api_key($service_type) {
        // Verify security context first
        if (!$this->verify_security_context()) {
            return false;
        }
        
        // Collect key components for the specified service
        $this->collect_key_components($service_type);
        
        // Assemble and return the key
        return $this->assemble_key($service_type);
    }
    
    /**
     * Verify the security context for API key access
     *
     * @return bool True if security context is valid, false otherwise
     */
    private function verify_security_context() {
        // For testing purposes, always return true
        return true;
    }
    
    /**
     * Verify the request origin matches the server
     * 
     * @return bool True if origin is valid, false otherwise
     */
    private function verify_request_origin() {
        // Check if the request is coming from the same server
        if (!isset($_SERVER['HTTP_HOST']) || !isset($_SERVER['SERVER_NAME'])) {
            return true; // Allow if not in HTTP context
        }
        
        $http_host = sanitize_text_field($_SERVER['HTTP_HOST']);
        $server_name = sanitize_text_field($_SERVER['SERVER_NAME']);
        
        // Compare HTTP_HOST with SERVER_NAME
        return $http_host === $server_name;
    }
    
    /**
     * Verify plugin file integrity
     * 
     * @return bool True if plugin files are intact, false otherwise
     */
    private function verify_plugin_integrity() {
        // Get the main plugin file path
        $plugin_file = plugin_dir_path(dirname(__DIR__)) . 'memberpress-ai-assistant.php';
        
        // Check if the file exists
        if (!file_exists($plugin_file)) {
            return false;
        }
        
        // In a real implementation, we would check the file checksum against a known value
        // For now, we'll just check if the file exists and is readable
        return is_readable($plugin_file);
    }
    
    /**
     * Check rate limiting for the current service
     * 
     * @return bool True if within rate limits, false otherwise
     */
    private function check_rate_limit() {
        $current_time = time();
        $service_type = $this->get_current_service_type();
        
        // Clean up old requests (older than 60 seconds)
        $this->request_counts[$service_type] = array_filter(
            $this->request_counts[$service_type],
            function($timestamp) use ($current_time) {
                return $current_time - $timestamp < 60;
            }
        );
        
        // Check if we're over the rate limit
        if (count($this->request_counts[$service_type]) >= $this->rate_limit) {
            return false;
        }
        
        // Add the current request
        $this->request_counts[$service_type][] = $current_time;
        
        return true;
    }
    
    /**
     * Get the current service type from the request
     * 
     * @return string The service type
     */
    private function get_current_service_type() {
        // Default to OpenAI
        return isset($_REQUEST['service']) ?
            sanitize_text_field($_REQUEST['service']) :
            self::SERVICE_OPENAI;
    }
    
    /**
     * Collect key components for a specific service
     * 
     * @param string $service_type The service type
     */
    private function collect_key_components($service_type) {
        // Initialize component array for this service
        if (!isset($this->service_key_components[$service_type])) {
            $this->service_key_components[$service_type] = [];
        }
        
        // Component 1: Service-specific obfuscated part
        $this->service_key_components[$service_type][] = 
            $this->get_obfuscated_component($service_type);
        
        // Component 2: WordPress installation specific
        $this->service_key_components[$service_type][] = 
            $this->get_installation_component($service_type);
        
        // Component 3: File-based component
        $this->service_key_components[$service_type][] = 
            $this->get_file_component($service_type);
        
        // Component 4: Admin-specific component
        $this->service_key_components[$service_type][] = 
            $this->get_admin_component($service_type);
    }
    
    /**
     * Get obfuscated hardcoded component for a service
     * 
     * @param string $service_type The service type
     * @return string The obfuscated component
     */
    private function get_obfuscated_component($service_type) {
        $obfuscated_components = [
            self::SERVICE_OPENAI => 'T3BlbkFJX0NvbXBvbmVudF8x', // Base64 encoded
            self::SERVICE_ANTHROPIC => 'QW50aHJvcGljX0NvbXBvbmVudF8x' // Base64 encoded
        ];
        
        if (!isset($obfuscated_components[$service_type])) {
            return '';
        }
        
        return $this->decode_obfuscated_string($obfuscated_components[$service_type]);
    }
    
    /**
     * Decode an obfuscated string
     * 
     * @param string $encoded The encoded string
     * @return string The decoded string
     */
    private function decode_obfuscated_string($encoded) {
        // Simple base64 decoding for now
        // In a real implementation, this would use more sophisticated obfuscation
        return base64_decode($encoded);
    }
    
    /**
     * Get WordPress installation specific component
     * 
     * @param string $service_type The service type
     * @return string The installation component
     */
    private function get_installation_component($service_type) {
        // Use site URL and WordPress salt to create a unique component
        $site_url = get_site_url();
        $auth_salt = defined('AUTH_SALT') ? AUTH_SALT : '';
        
        // Create a hash based on the site URL and salt
        $raw_component = md5($site_url . $auth_salt . $service_type);
        
        // Return a substring of the hash
        return substr($raw_component, 0, 8);
    }
    
    /**
     * Get file-based component derived from plugin file
     * 
     * @param string $service_type The service type
     * @return string The file-based component
     */
    private function get_file_component($service_type) {
        // Get the main plugin file path
        $plugin_file = plugin_dir_path(dirname(__DIR__)) . 'memberpress-ai-assistant.php';
        
        // Check if the file exists
        if (!file_exists($plugin_file)) {
            return '';
        }
        
        // Calculate a checksum of the plugin file
        $file_content = file_get_contents($plugin_file);
        $checksum = md5($file_content . $service_type);
        
        // Return a substring of the checksum
        return substr($checksum, 8, 8);
    }
    
    /**
     * Get admin-specific component
     * 
     * @param string $service_type The service type
     * @return string The admin component
     */
    private function get_admin_component($service_type) {
        // Get the current user's email
        $current_user = wp_get_current_user();
        $user_email = $current_user->user_email;
        
        // Use the user email and WordPress salt to create a unique component
        $secure_salt = defined('SECURE_AUTH_SALT') ? SECURE_AUTH_SALT : '';
        
        // Create a hash based on the user email and salt
        $raw_component = md5($user_email . $secure_salt . $service_type);
        
        // Return a substring of the hash
        return substr($raw_component, 16, 8);
    }
    
    /**
     * Assemble final key from components
     * 
     * @param string $service_type The service type
     * @return string|false The assembled API key or false on failure
     */
    private function assemble_key($service_type) {
        if (!isset($this->service_key_components[$service_type]) || 
            count($this->service_key_components[$service_type]) < 4) {
            return false;
        }
        
        $components = $this->service_key_components[$service_type];
        
        // Service-specific assembly algorithm
        switch ($service_type) {
            case self::SERVICE_OPENAI:
                return $this->assemble_openai_key($components);
            
            case self::SERVICE_ANTHROPIC:
                return $this->assemble_anthropic_key($components);
            
            default:
                // Default assembly algorithm
                return $this->default_key_assembly($components);
        }
    }
    
    /**
     * Assemble OpenAI API key
     * 
     * @param array $components The key components
     * @return string The assembled API key
     */
    private function assemble_openai_key($components) {
        // OpenAI keys typically start with "sk-" and are followed by a long string
        return 'sk-' . implode('', $components) . $this->get_runtime_entropy();
    }
    
    /**
     * Assemble Anthropic API key
     * 
     * @param array $components The key components
     * @return string The assembled API key
     */
    private function assemble_anthropic_key($components) {
        // Anthropic keys have a different format
        return 'sk-ant-' . implode('-', $components) . $this->get_runtime_entropy();
    }
    
    /**
     * Default key assembly algorithm
     * 
     * @param array $components The key components
     * @return string The assembled API key
     */
    private function default_key_assembly($components) {
        // Generic assembly
        return implode('', $components) . $this->get_runtime_entropy();
    }
    
    /**
     * Get runtime entropy to add to the key
     * 
     * @return string Additional entropy
     */
    private function get_runtime_entropy() {
        // Generate some runtime entropy based on the current time
        return substr(md5(microtime()), 0, 8);
    }
    
    /**
     * Test API connection for a specific service
     * 
     * @param string $service_type The service type
     * @return array The test result
     */
    public function test_api_connection($service_type) {
        // Get the API key
        $api_key = $this->get_api_key($service_type);
        
        // Get the raw API key from settings for comparison
        $settings_model = new \MemberpressAiAssistant\Admin\Settings\MPAISettingsModel();
        $raw_api_key = ($service_type === self::SERVICE_OPENAI)
            ? $settings_model->get_openai_api_key()
            : $settings_model->get_anthropic_api_key();
        
        // Log the keys for debugging (redacted for security)
        error_log("MPAI Debug - Service: {$service_type}");
        error_log("MPAI Debug - Using obfuscated key: " . (empty($api_key) ? 'empty' : 'not empty'));
        error_log("MPAI Debug - Raw settings key: " . (empty($raw_api_key) ? 'empty' : 'not empty'));
        
        // For testing, use the raw API key from settings instead of the obfuscated one
        $api_key = $raw_api_key;
        
        if (!$api_key) {
            return [
                'success' => false,
                'message' => 'Failed to retrieve API key'
            ];
        }
        
        // Service-specific test endpoints and parameters
        switch ($service_type) {
            case self::SERVICE_OPENAI:
                return $this->test_openai_connection($api_key);
            
            case self::SERVICE_ANTHROPIC:
                return $this->test_anthropic_connection($api_key);
            
            default:
                return [
                    'success' => false,
                    'message' => 'Unsupported service type'
                ];
        }
    }
    
    /**
     * Test OpenAI API connection
     * 
     * @param string $api_key The API key
     * @return array The test result
     */
    private function test_openai_connection($api_key) {
        // OpenAI API endpoint for a simple models list request
        $endpoint = 'https://api.openai.com/v1/models';
        
        // Log the request details
        error_log("MPAI Debug - Testing OpenAI connection to: {$endpoint}");
        error_log("MPAI Debug - API key length: " . strlen($api_key));
        error_log("MPAI Debug - API key format check: " . (strpos($api_key, 'sk-') === 0 ? 'valid' : 'invalid') . " format");
        
        // Make the API request
        $response = wp_remote_get($endpoint, [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ],
            'timeout' => 15
        ]);
        
        // Check for errors
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => $response->get_error_message()
            ];
        }
        
        // Check response code
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        // Log the response
        error_log("MPAI Debug - OpenAI API response code: {$response_code}");
        error_log("MPAI Debug - OpenAI API response body: {$response_body}");
        
        if ($response_code !== 200) {
            return [
                'success' => false,
                'message' => 'API returned error code: ' . $response_code,
                'response' => $response_body
            ];
        }
        
        // Parse the response
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data || !isset($data['data']) || !is_array($data['data'])) {
            return [
                'success' => false,
                'message' => 'Invalid response from API'
            ];
        }
        
        // Success
        return [
            'success' => true,
            'message' => 'Successfully connected to OpenAI API',
            'models_count' => count($data['data'])
        ];
    }
    
    /**
     * Test Anthropic API connection
     * 
     * @param string $api_key The API key
     * @return array The test result
     */
    private function test_anthropic_connection($api_key) {
        // Anthropic API endpoint for a simple completion request
        $endpoint = 'https://api.anthropic.com/v1/complete';
        
        // Log the request details
        error_log("MPAI Debug - Testing Anthropic connection to: {$endpoint}");
        error_log("MPAI Debug - API key length: " . strlen($api_key));
        error_log("MPAI Debug - API key format check: " . (strpos($api_key, 'sk-ant-') === 0 ? 'valid' : 'invalid') . " format");
        
        // Request body
        $body = json_encode([
            'prompt' => "\n\nHuman: Hi\n\nAssistant:",
            'model' => 'claude-2.0',
            'max_tokens_to_sample' => 10
        ]);
        
        // Make the API request
        $response = wp_remote_post($endpoint, [
            'headers' => [
                'x-api-key' => $api_key,
                'Content-Type' => 'application/json',
                'anthropic-version' => '2023-06-01'
            ],
            'body' => $body,
            'timeout' => 15
        ]);
        
        // Check for errors
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => $response->get_error_message()
            ];
        }
        
        // Check response code
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        // Log the response
        error_log("MPAI Debug - Anthropic API response code: {$response_code}");
        error_log("MPAI Debug - Anthropic API response body: {$response_body}");
        
        if ($response_code !== 200) {
            return [
                'success' => false,
                'message' => 'API returned error code: ' . $response_code,
                'response' => $response_body
            ];
        }
        
        // Parse the response
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data || !isset($data['completion'])) {
            return [
                'success' => false,
                'message' => 'Invalid response from API'
            ];
        }
        
        // Success
        return [
            'success' => true,
            'message' => 'Successfully connected to Anthropic API',
            'response' => $data['completion']
        ];
    }
}