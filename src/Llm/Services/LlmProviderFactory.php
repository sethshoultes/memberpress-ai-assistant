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
// Removed MPAIKeyManager import - no longer needed with LiteLLM proxy

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
    
    // Removed key manager - no longer needed with LiteLLM proxy

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
     * @throws \Exception If the provider is not registered
     */
    public function createProvider(string $name): LlmClientInterface {
        if (!isset($this->providers[$name])) {
            throw new \Exception("Provider '$name' is not registered");
        }
        
        $providerClass = $this->providers[$name];
        $config = $this->configs[$name] ?? null;
        
        // Use LiteLLM proxy key for all providers
        return new $providerClass('3d82afe47512fcb1faba41cc1c9c796d3dbe8624b0a5c62fa68e6d38f0bf6d72', $config);
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

    // Removed getApiKey method - no longer needed with LiteLLM proxy
}