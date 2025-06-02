<?php
/**
 * Orchestration Service Provider
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\DI\Providers;

use MemberpressAiAssistant\DI\ServiceProvider;
use MemberpressAiAssistant\DI\ServiceLocator;
use MemberpressAiAssistant\Services\OrchestratorService;
use MemberpressAiAssistant\Services\ChatInterfaceService;
use MemberpressAiAssistant\Orchestration\AgentOrchestrator;
use MemberpressAiAssistant\Orchestration\ContextManager;
use MemberpressAiAssistant\Factory\AgentFactory;
use MemberpressAiAssistant\Registry\AgentRegistry;
use MemberpressAiAssistant\Registry\ToolRegistry;

/**
 * Service provider for orchestration services
 */
class OrchestrationServiceProvider extends ServiceProvider {
    /**
     * Register services with the service locator
     *
     * @param ServiceLocator $locator The service locator
     * @return void
     */
    public function register(ServiceLocator $locator): void {
        // Register agent registry
        $this->registerSingleton($locator, 'agent_registry', function() use ($locator) {
            $logger = $locator->has('logger') ? $locator->get('logger') : null;
            $registry = AgentRegistry::getInstance($logger);
            
            // Discover and register available agents
            $registry->discoverAgents();
            
            return $registry;
        });

        // Register tool registry
        $this->registerSingleton($locator, 'tool_registry', function() use ($locator) {
            $logger = $locator->has('logger') ? $locator->get('logger') : null;
            $registry = ToolRegistry::getInstance($logger);
            
            // Discover and register available tools
            $registry->discoverTools();
            
            return $registry;
        });

        // Register agent factory
        $this->registerSingleton($locator, 'agent_factory', function() use ($locator) {
            $agentRegistry = $locator->get('agent_registry');
            return new AgentFactory($locator, $agentRegistry);
        });

        // Register context manager
        $this->registerSingleton($locator, 'context_manager', function() {
            return new ContextManager();
        });

        // Register orchestrator service
        $this->registerSingleton($locator, 'orchestrator_service', function() use ($locator) {
            $logger = $locator->has('logger') ? $locator->get('logger') : null;
            $orchestratorService = new OrchestratorService('orchestrator_service', $logger);
            $orchestratorService->register($locator);
            return $orchestratorService;
        });

        // Register agent orchestrator
        $this->registerSingleton($locator, 'agent_orchestrator', function() use ($locator) {
            $agentRegistry = $locator->get('agent_registry');
            $agentFactory = $locator->get('agent_factory');
            $contextManager = $locator->get('context_manager');
            $logger = $locator->has('logger') ? $locator->get('logger') : null;
            
            // Get the cache service if available
            $cacheService = null;
            if ($locator->has('cache')) {
                $cacheService = $locator->get('cache');
            }
            
            $orchestrator = new AgentOrchestrator(
                $agentRegistry,
                $agentFactory,
                $contextManager,
                $logger,
                $cacheService
            );
            
            return $orchestrator;
        });

        // Register chat interface service
        $this->registerSingleton($locator, 'chat_interface_service', function() use ($locator) {
            $logger = $locator->has('logger') ? $locator->get('logger') : null;
            $chatInterfaceService = new ChatInterfaceService('chat_interface', $logger);
            $chatInterfaceService->register($locator);
            return $chatInterfaceService;
        });
    }

    /**
     * Get the services provided by the provider
     *
     * @return array Array of service names
     */
    public function provides(): array {
        return [
            'agent_registry',
            'tool_registry',
            'agent_factory',
            'context_manager',
            'orchestrator_service',
            'agent_orchestrator',
            'chat_interface_service',
            'chat_interface' // Registered by ChatInterfaceService
        ];
    }
}