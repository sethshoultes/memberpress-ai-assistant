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
     * Constructor
     */
    public function __construct() {
        // Register default providers
        $this->registerProvider('openai', OpenAiClient::class);
        $this->registerProvider('anthropic', AnthropicClient::class);
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
        // Get the API key from the options
        $optionName = "mpai_{$provider}_api_key";
        $apiKey = get_option($optionName, '');
        
        if (empty($apiKey)) {
            throw new \Exception("API key for provider '$provider' is not available");
        }
        
        return $apiKey;
    }
}