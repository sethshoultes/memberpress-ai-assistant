<?php
/**
 * Orchestrator Service
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Services;

use MemberpressAiAssistant\Abstracts\AbstractService;
use MemberpressAiAssistant\DI\ServiceLocator;
use MemberpressAiAssistant\Orchestration\AgentOrchestrator;
use MemberpressAiAssistant\Orchestration\ContextManager;
use MemberpressAiAssistant\Factory\AgentFactory;
use MemberpressAiAssistant\Registry\AgentRegistry;

/**
 * Service for managing the AgentOrchestrator with caching support
 */
class OrchestratorService extends AbstractService {
    /**
     * Default TTL for cached agent responses in seconds (10 minutes)
     *
     * @var int
     */
    protected $default_cache_ttl = 600;

    /**
     * {@inheritdoc}
     */
    public function register($serviceLocator): void {
        // Store the service locator for use in closures
        $self = $this;
        
        // Register the AgentOrchestrator with the service locator
        $serviceLocator->register('agent_orchestrator', function() use ($serviceLocator, $self) {
            // Get required dependencies
            $agentRegistry = AgentRegistry::getInstance($serviceLocator->get('logger'));
            $agentFactory = $serviceLocator->has('agent_factory') ? $serviceLocator->get('agent_factory') : new AgentFactory($serviceLocator, $agentRegistry);
            $contextManager = $serviceLocator->has('context_manager') ? $serviceLocator->get('context_manager') : new ContextManager();
            $logger = $serviceLocator->get('logger');
            
            // Get the cache service if available
            $cacheService = null;
            if ($serviceLocator->has('cache')) {
                $cacheService = $serviceLocator->get('cache');
                $self->log('Cache service found and will be used for agent response caching');
            } else {
                $self->log('Cache service not found, agent response caching will be disabled', ['level' => 'warning']);
            }
            
            // Create the orchestrator with cache service
            $orchestrator = new AgentOrchestrator(
                $agentRegistry,
                $agentFactory,
                $contextManager,
                $logger,
                $cacheService
            );
            
            // Configure cache TTL if cache service is available
            if ($cacheService !== null) {
                $orchestrator->setDefaultCacheTtl($self->default_cache_ttl);
            }
            
            return $orchestrator;
        });
        
        // Register the agent factory if not already registered
        if (!$serviceLocator->has('agent_factory')) {
            $serviceLocator->register('agent_factory', function() use ($serviceLocator) {
                $agentRegistry = AgentRegistry::getInstance($serviceLocator->get('logger'));
                return new AgentFactory($serviceLocator, $agentRegistry);
            });
        }
        
        // Register the context manager if not already registered
        if (!$serviceLocator->has('context_manager')) {
            $serviceLocator->register('context_manager', function() {
                return new ContextManager();
            });
        }
        
        // Log registration
        $this->log('Orchestrator service registered');
    }

    /**
     * {@inheritdoc}
     */
    public function boot(): void {
        parent::boot();
        
        // Add hooks
        $this->addHooks();
        
        // Log boot
        $this->log('Orchestrator service booted');
    }

    /**
     * Add hooks and filters for this service
     *
     * @return void
     */
    protected function addHooks(): void {
        // Add hook to clear cache on plugin update
        add_action('upgrader_process_complete', [$this, 'clearCacheOnUpdate'], 10, 2);
    }

    /**
     * Clear cache when plugin is updated
     *
     * @param \WP_Upgrader $upgrader Upgrader instance
     * @param array $options Upgrader options
     * @return void
     */
    public function clearCacheOnUpdate($upgrader, $options): void {
        // Check if this is our plugin being updated
        if (isset($options['action']) && $options['action'] === 'update' && 
            isset($options['type']) && $options['type'] === 'plugin' &&
            isset($options['plugins']) && in_array('memberpress-ai-assistant/memberpress-ai-assistant.php', $options['plugins'])) {
            
            // Get the cache service
            global $mpai_service_locator;
            if ($mpai_service_locator && $mpai_service_locator->has('cache')) {
                $cacheService = $mpai_service_locator->get('cache');
                
                // Clear all agent response caches
                $cacheService->deletePattern('agent_response_');
                
                $this->log('Cleared agent response cache on plugin update');
            }
        }
    }

    /**
     * Set the default cache TTL
     *
     * @param int $ttl TTL in seconds
     * @return self
     */
    public function setDefaultCacheTTL(int $ttl): self {
        $this->default_cache_ttl = max(0, $ttl);
        
        // Update the orchestrator if already instantiated
        global $mpai_service_locator;
        if ($mpai_service_locator && $mpai_service_locator->has('agent_orchestrator')) {
            $orchestrator = $mpai_service_locator->get('agent_orchestrator');
            if (method_exists($orchestrator, 'setDefaultCacheTtl')) {
                $orchestrator->setDefaultCacheTtl($this->default_cache_ttl);
            }
        }
        
        return $this;
    }
}