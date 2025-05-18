<?php
/**
 * LLM Provider Factory
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Llm\Services;

use MemberpressAiAssistant\Llm\Interfaces\LlmClientInterface;
use MemberpressAiAssistant\Llm\Providers\AnthropicClient;
use MemberpressAiAssistant\Llm\Providers\OpenAiClient;
use MemberpressAiAssistant\Llm\ValueObjects\LlmProviderConfig;
use MemberpressAiAssistant\Admin\MPAIKeyManager;

/**
 * Factory for creating LLM provider clients
 */
class LlmProviderFactory {
    /**
     * Registered provider classes
     *
     * @var array
     */
    private $providers = [];

    /**
     * Provider configurations
     *
     * @var array
     */
    private $configs = [];
    
    /**
     * Key manager instance
     *
     * @var MPAIKeyManager|null
     */
    private $keyManager = null;

    /**
     * Constructor
     *
     * @param MPAIKeyManager|null $keyManager Key manager instance
     */
    public function __construct($keyManager = null) {
        // Register default providers
        $this->registerProvider('openai', OpenAiClient::class);
        $this->registerProvider('anthropic', AnthropicClient::class);
        
        // Store key manager
        $this->keyManager = $keyManager;
    }

    /**
     * Register a provider
     *
     * @param string                $name         The provider name
     * @param string                $providerClass The provider class
     * @param LlmProviderConfig|null $config       The provider configuration
     * @return self
     */
    public function registerProvider(string $name, string $providerClass, ?LlmProviderConfig $config = null): self {
        $this->providers[$name] = $providerClass;
        
        if ($config !== null) {
            $this->configs[$name] = $config;
        }
        
        return $this;
    }

    /**
     * Create a provider client
     *
     * @param string $name The provider name
     * @return LlmClientInterface The provider client
     * @throws \Exception If the provider is not registered or the API key is not available
     */
    public function createProvider(string $name): LlmClientInterface {
        if (!isset($this->providers[$name])) {
            throw new \Exception("Provider '$name' is not registered");
        }
        
        $providerClass = $this->providers[$name];
        $config = $this->configs[$name] ?? null;
        $apiKey = $this->getApiKey($name);
        
        return new $providerClass($apiKey, $config);
    }

    /**
     * Get a provider configuration
     *
     * @param string $name The provider name
     * @return LlmProviderConfig|null The provider configuration
     */
    public function getProviderConfig(string $name): ?LlmProviderConfig {
        return $this->configs[$name] ?? null;
    }

    /**
     * Set a provider configuration
     *
     * @param string           $name   The provider name
     * @param LlmProviderConfig $config The provider configuration
     * @return self
     */
    public function setProviderConfig(string $name, LlmProviderConfig $config): self {
        $this->configs[$name] = $config;
        return $this;
    }

    /**
     * Get the registered providers
     *
     * @return array The registered providers
     */
    public function getProviders(): array {
        return array_keys($this->providers);
    }

    /**
     * Check if a provider is registered
     *
     * @param string $name The provider name
     * @return bool True if the provider is registered
     */
    public function hasProvider(string $name): bool {
        return isset($this->providers[$name]);
    }

    /**
     * Get the API key for a provider
     *
     * @param string $provider The provider name
     * @return string The API key
     * @throws \Exception If the API key is not available
     */
    private function getApiKey(string $provider): string {
        if (function_exists('error_log')) {
            error_log("MPAI Debug - Getting API key for provider: $provider");
        }
        
        // Try to get the API key from the key manager first
        if ($this->keyManager !== null) {
            if (function_exists('error_log')) {
                error_log("MPAI Debug - Using provided key manager instance");
            }
            
            $apiKey = $this->keyManager->get_api_key($provider);
            
            if (!empty($apiKey)) {
                if (function_exists('error_log')) {
                    error_log("MPAI Debug - Got API key from key manager: " . substr($apiKey, 0, 10) . "... (Length: " . strlen($apiKey) . ")");
                }
                return $apiKey;
            } else {
                if (function_exists('error_log')) {
                    error_log("MPAI Debug - Key manager returned empty API key");
                }
            }
        } else {
            if (function_exists('error_log')) {
                error_log("MPAI Debug - No key manager instance provided");
            }
        }
        
        // Fall back to global service locator if key manager wasn't provided
        global $mpai_service_locator;
        if (isset($mpai_service_locator) && $mpai_service_locator->has('key_manager')) {
            if (function_exists('error_log')) {
                error_log("MPAI Debug - Using key manager from service locator");
            }
            
            $keyManager = $mpai_service_locator->get('key_manager');
            $apiKey = $keyManager->get_api_key($provider);
            
            if (!empty($apiKey)) {
                if (function_exists('error_log')) {
                    error_log("MPAI Debug - Got API key from service locator key manager: " . substr($apiKey, 0, 10) . "... (Length: " . strlen($apiKey) . ")");
                }
                return $apiKey;
            } else {
                if (function_exists('error_log')) {
                    error_log("MPAI Debug - Service locator key manager returned empty API key");
                }
            }
        } else {
            if (function_exists('error_log')) {
                error_log("MPAI Debug - No key manager available in service locator");
            }
        }
        
        // As a last resort, try to get the API key from the options
        // This is for backward compatibility
        $optionName = "mpai_{$provider}_api_key";
        $apiKey = get_option($optionName, '');
        
        if (!empty($apiKey)) {
            if (function_exists('error_log')) {
                error_log("MPAI Debug - Got API key from options: " . substr($apiKey, 0, 10) . "... (Length: " . strlen($apiKey) . ")");
            }
            return $apiKey;
        } else {
            if (function_exists('error_log')) {
                error_log("MPAI Debug - No API key found in options");
            }
        }
        
        if (function_exists('error_log')) {
            error_log("MPAI Debug - No API key available for provider: $provider");
        }
        
        throw new \Exception("API key for provider '$provider' is not available");
    }
}