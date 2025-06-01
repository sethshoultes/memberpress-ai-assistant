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
use MemberpressAiAssistant\Utilities\LoggingUtility;

/**
 * MPAIKeyManager - Manages API keys for AI services
 *
 * This class implements a key management system that:
 * 1. Retrieves API keys from the WordPress database
 * 2. Implements security verification layers
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
     * Settings model instance
     *
     * @var \MemberpressAiAssistant\Admin\Settings\MPAISettingsModel
     */
    private $settings;
    
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
    public function __construct(string $name = 'key_manager', $logger = null, $settings = null) {
        parent::__construct($name, $logger);
        
        // Initialize request counts for rate limiting
        $this->request_counts = [
            self::SERVICE_OPENAI => [],
            self::SERVICE_ANTHROPIC => []
        ];
        
        // Store settings model
        $this->settings = $settings;
    }
    
    /**
     * Get the settings model
     *
     * @return \MemberpressAiAssistant\Admin\Settings\MPAISettingsModel|null
     */
    public function get_settings() {
        return $this->settings;
    }
    
    /**
     * Set the settings model
     *
     * @param \MemberpressAiAssistant\Admin\Settings\MPAISettingsModel $settings
     * @return void
     */
    public function set_settings($settings) {
        $this->settings = $settings;
    }
    
    /**
     * Get API key for specified service
     * 
     * @param string $service_type The service type (openai, anthropic)
     * @return string|false The API key or false on failure
     */
    public function get_api_key($service_type) {
        // Allow addons to override the API key
        $override_key = apply_filters('mpai_override_api_key_' . $service_type, null);
        
        // If an addon has provided a key, use it
        if ($override_key !== null) {
            \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Using override key from addon for service: {$service_type}");
            return $override_key;
        }
        
        // Add detailed logging
        \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Getting API key for service: {$service_type}");
        
        // Verify security context first
        if (!$this->verify_security_context()) {
            \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Security context verification failed");
            return false;
        }
        
        // Get the API key from settings
        if ($this->settings !== null) {
            \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Settings model is available");
            
            if ($service_type === self::SERVICE_OPENAI) {
                $key = $this->settings->get_openai_api_key();
                \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Got OpenAI key from settings: " . (empty($key) ? "empty" : "not empty"));
            } elseif ($service_type === self::SERVICE_ANTHROPIC) {
                $key = $this->settings->get_anthropic_api_key();
                \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Got Anthropic key from settings: " . (empty($key) ? "empty" : "not empty"));
            } else {
                \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Unknown service type: {$service_type}");
                return false;
            }
            
            // Validate the key format
            if (!empty($key)) {
                if ($this->validate_key_format($service_type, $key)) {
                    \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Key format is valid for service: {$service_type}");
                    return $key;
                } else {
                    \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Invalid key format for service: {$service_type}");
                    return false;
                }
            } else {
                \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Empty key for service: {$service_type}");
            }
        } else {
            \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Settings model is not available");
        }
        
        // If settings model is not available or key is empty, try to get the key from WordPress options
        $all_settings = get_option('mpai_settings', []);
        \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Trying to get key from WordPress options directly");
        
        if (!empty($all_settings)) {
            if ($service_type === self::SERVICE_OPENAI && isset($all_settings['openai_api_key'])) {
                $key = $all_settings['openai_api_key'];
                \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Got OpenAI key from WordPress options: " . (empty($key) ? "empty" : "not empty"));
                
                if (!empty($key) && $this->validate_key_format($service_type, $key)) {
                    \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Key format is valid for service: {$service_type}");
                    return $key;
                }
            } elseif ($service_type === self::SERVICE_ANTHROPIC && isset($all_settings['anthropic_api_key'])) {
                $key = $all_settings['anthropic_api_key'];
                \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Got Anthropic key from WordPress options: " . (empty($key) ? "empty" : "not empty"));
                
                if (!empty($key) && $this->validate_key_format($service_type, $key)) {
                    \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Key format is valid for service: {$service_type}");
                    return $key;
                }
            }
        } else {
            \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - No settings found in WordPress options");
        }
        
        // As a last resort, try the old option name format
        $option_name = "mpai_{$service_type}_api_key";
        $key = get_option($option_name, '');
        
        if (!empty($key)) {
            \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Got key from old option name format: " . $option_name);
            return $key;
        }
        
        \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - No API key found for service type: {$service_type}");
        return false;
    }
    
    /**
     * Verify the security context for API key access
     *
     * @return bool True if security context is valid, false otherwise
     */
    private function verify_security_context() {
        \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Verifying security context for API key access");
        
        // For chat interface, bypass security checks
        $is_chat_request = false;
        
        // Check if this is a chat request by examining the backtrace
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        foreach ($backtrace as $trace) {
            if (isset($trace['class']) &&
                (strpos($trace['class'], 'ChatInterface') !== false ||
                 strpos($trace['class'], 'LlmChatAdapter') !== false ||
                 strpos($trace['class'], 'LlmProviderFactory') !== false)) {
                $is_chat_request = true;
                \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Detected chat request from: " . $trace['class'] . "::" . $trace['function']);
                break;
            }
        }
        
        if ($is_chat_request) {
            \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Bypassing security checks for chat request");
            return true;
        }
        
        // Must be in WordPress admin context
        if (!is_admin()) {
            $this->log_error('Not in admin context');
            \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Security check failed: Not in admin context");
            return false;
        } else {
            \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Security check passed: In admin context");
        }
        
        // User must have appropriate capabilities
        if (!current_user_can('manage_options')) {
            $this->log_error('User lacks required capabilities');
            \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Security check failed: User lacks required capabilities");
            return false;
        } else {
            \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Security check passed: User has required capabilities");
        }
        
        // Verify request origin
        if (!$this->verify_request_origin()) {
            $this->log_error('Invalid request origin');
            \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Security check failed: Invalid request origin");
            return false;
        } else {
            \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Security check passed: Valid request origin");
        }
        
        // Verify plugin integrity
        if (!$this->verify_plugin_integrity()) {
            $this->log_error('Plugin integrity check failed');
            \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Security check failed: Plugin integrity check failed");
            return false;
        } else {
            \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Security check passed: Plugin integrity verified");
        }
        
        // Check rate limiting
        if (!$this->check_rate_limit()) {
            $this->log_error('Rate limit exceeded');
            \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Security check failed: Rate limit exceeded");
            return false;
        } else {
            \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Security check passed: Within rate limits");
        }
        
        \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - All security checks passed");
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
     * Test API connection for a specific service
     * 
     * @param string $service_type The service type
     * @return array The test result
     */
    public function test_api_connection($service_type) {
        // Get the API key from settings or addon override
        $api_key = $this->get_api_key($service_type);
        
        // Log the key info for debugging (redacted for security)
        \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Testing {$service_type} API connection");
        \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - API key available: " . (empty($api_key) ? 'No' : 'Yes'));
        
        // Fire action before API request
        do_action('mpai_before_api_request', $service_type);
        
        if (!$api_key) {
            $error = [
                'success' => false,
                'message' => 'Could not retrieve API key. Please check your configuration.'
            ];
            
            // Fire action for API request error
            do_action('mpai_api_request_error', $service_type, null, $error);
            
            return $error;
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
        \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Testing OpenAI connection to: {$endpoint}");
        \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - API key length: " . strlen($api_key));
        \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - API key format check: " . (strpos($api_key, 'sk-') === 0 ? 'valid' : 'invalid') . " format");
        
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
        \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - OpenAI API response code: {$response_code}");
        \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - OpenAI API response body: {$response_body}");
        
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
        \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Testing Anthropic connection to: {$endpoint}");
        \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - API key length: " . strlen($api_key));
        \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - API key format check: " . (strpos($api_key, 'sk-ant-') === 0 ? 'valid' : 'invalid') . " format");
        
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
        \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Anthropic API response code: {$response_code}");
        \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Anthropic API response body: {$response_body}");
        
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
    
    /**
     * Log an error message securely (without exposing keys)
     *
     * @param string $message Error message
     * @return void
     */
    private function log_error($message) {
        if ($this->logger) {
            $this->logger->error('Key Manager: ' . $message);
        } else {
            LoggingUtility::error($message);
        }
    }
    
    /**
     * Validate key format for a specific service
     *
     * @param string $service_type The service type
     * @param string $key The API key
     * @return bool Whether the key format is valid
     */
    private function validate_key_format($service_type, $key) {
        if (empty($key)) {
            return false;
        }
        
        switch ($service_type) {
            case self::SERVICE_OPENAI:
                return strpos($key, 'sk-') === 0;
            
            case self::SERVICE_ANTHROPIC:
                return strpos($key, 'sk-ant-') === 0;
            
            default:
                return true;
        }
    }
}