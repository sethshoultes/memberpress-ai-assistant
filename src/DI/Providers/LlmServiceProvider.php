<?php
/**
 * LLM Service Provider
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\DI\Providers;

use MemberpressAiAssistant\DI\ServiceLocator;
use MemberpressAiAssistant\DI\ServiceProvider;
use MemberpressAiAssistant\Llm\Providers\AnthropicClient;
use MemberpressAiAssistant\Llm\Providers\OpenAiClient;
use MemberpressAiAssistant\Llm\Services\LlmCacheAdapter;
use MemberpressAiAssistant\Llm\Services\LlmChatAdapter;
use MemberpressAiAssistant\Llm\Services\LlmOrchestrator;
use MemberpressAiAssistant\Llm\Services\LlmProviderFactory;
use MemberpressAiAssistant\Llm\ValueObjects\LlmProviderConfig;

/**
 * Service provider for LLM services
 */
class LlmServiceProvider extends ServiceProvider {
    /**
     * Register LLM services with the service locator
     *
     * @param ServiceLocator $locator The service locator
     * @return void
     */
    public function register(ServiceLocator $locator): void {
        // Register the provider factory
        $locator->register('llm.provider_factory', function($locator) {
            // Get the key manager if available
            $keyManager = null;
            if ($locator->has('key_manager')) {
                $keyManager = $locator->get('key_manager');
            }
            
            return new LlmProviderFactory($keyManager);
        });

        // Register the cache adapter
        $locator->register('llm.cache_adapter', function($locator) {
            $cacheService = $locator->get('cache');
            $configService = $locator->get('configuration');
            return new LlmCacheAdapter($cacheService, $configService);
        });

        // Register the orchestrator
        $locator->register('llm.orchestrator', function($locator) {
            $providerFactory = $locator->get('llm.provider_factory');
            $cacheAdapter = $locator->get('llm.cache_adapter');
            return new LlmOrchestrator($providerFactory, $cacheAdapter);
        });

        // Register the chat adapter
        $locator->register('llm.chat_adapter', function($locator) {
            $orchestrator = $locator->get('llm.orchestrator');
            
            // Get the tool registry if available
            $toolRegistry = null;
            if ($locator->has('tool_registry')) {
                $toolRegistry = $locator->get('tool_registry');
            }
            
            // Get the context manager if available
            $contextManager = null;
            if ($locator->has('context_manager')) {
                $contextManager = $locator->get('context_manager');
            } else if ($locator->has('agent_orchestrator')) {
                // Try to get the context manager from the agent orchestrator
                $agentOrchestrator = $locator->get('agent_orchestrator');
                if (property_exists($agentOrchestrator, 'contextManager') &&
                    method_exists($agentOrchestrator, 'getContextManager')) {
                    $contextManager = $agentOrchestrator->getContextManager();
                }
            }
            
            // Log what we found
            if (function_exists('error_log')) {
                error_log('MPAI Debug - LlmChatAdapter dependencies: ' .
                    'ToolRegistry: ' . ($toolRegistry ? 'Yes' : 'No') . ', ' .
                    'ContextManager: ' . ($contextManager ? 'Yes' : 'No'));
            }
            
            return new LlmChatAdapter($orchestrator, $toolRegistry, $contextManager);
        });

        // Register provider configurations
        $this->registerProviderConfigurations($locator);
    }

    /**
     * Register provider configurations
     *
     * @param ServiceLocator $locator The service locator
     * @return void
     */
    private function registerProviderConfigurations(ServiceLocator $locator): void {
        // Get the provider factory
        $providerFactory = $locator->get('llm.provider_factory');

        // Register OpenAI configuration
        $openaiConfig = new LlmProviderConfig(
            'openai',
            'gpt-4o',
            ['gpt-4o', 'gpt-4-turbo', 'gpt-4', 'gpt-3.5-turbo'],
            0.7,
            4096
        );
        $providerFactory->setProviderConfig('openai', $openaiConfig);

        // Register Anthropic configuration
        $anthropicConfig = new LlmProviderConfig(
            'anthropic',
            'claude-3-opus-20240229',
            [
                'claude-3-opus-20240229',
                'claude-3-sonnet-20240229',
                'claude-3-haiku-20240307',
                'claude-2.1',
                'claude-2.0',
                'claude-instant-1.2'
            ],
            0.7,
            4096
        );
        $providerFactory->setProviderConfig('anthropic', $anthropicConfig);
    }

    /**
     * Get the services provided by this provider
     *
     * @return array
     */
    public function provides(): array {
        return [
            'llm.provider_factory',
            'llm.cache_adapter',
            'llm.orchestrator',
            'llm.chat_adapter',
        ];
    }

    /**
     * Boot the services
     *
     * @param ServiceLocator $locator The service locator
     * @return void
     */
    public function boot(ServiceLocator $locator): void {
        // Add hooks
        add_action('admin_init', [$this, 'registerSettings']);
    }

    /**
     * Register settings
     *
     * @return void
     */
    public function registerSettings(): void {
        // API key settings are now handled by the obfuscated key system
        // These settings are kept for backward compatibility but are deprecated

        register_setting('mpai_settings', 'mpai_primary_provider', [
            'type' => 'string',
            'description' => 'Primary LLM Provider',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'openai',
        ]);

        register_setting('mpai_settings', 'mpai_enable_response_caching', [
            'type' => 'boolean',
            'description' => 'Enable LLM Response Caching',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => true,
        ]);

        register_setting('mpai_settings', 'mpai_response_cache_ttl', [
            'type' => 'integer',
            'description' => 'LLM Response Cache TTL (seconds)',
            'sanitize_callback' => 'absint',
            'default' => 3600,
        ]);
    }
}