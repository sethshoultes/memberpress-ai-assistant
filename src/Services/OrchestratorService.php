<?php
/**
 * Orchestrator Service
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Services;

use MemberpressAiAssistant\Abstracts\AbstractService;
use MemberpressAiAssistant\DI\Container;
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
    public function register($container): void {
        // Register the AgentOrchestrator with the container
        $container->singleton('agent_orchestrator', function() use ($container) {
            // Get required dependencies
            $agentRegistry = AgentRegistry::getInstance($container->make('logger'));
            $agentFactory = $container->make('agent_factory') ?? new AgentFactory($container, $agentRegistry);
            $contextManager = $container->make('context_manager') ?? new ContextManager();
            $logger = $container->make('logger');
            
            // Get the cache service if available
            $cacheService = null;
            if ($container->bound('cache')) {
                $cacheService = $container->make('cache');
                $this->log('Cache service found and will be used for agent response caching');
            } else {
                $this->log('Cache service not found, agent response caching will be disabled', ['level' => 'warning']);
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
                $orchestrator->setDefaultCacheTtl($this->default_cache_ttl);
            }
            
            return $orchestrator;
        });
        
        // Register the agent factory if not already registered
        if (!$container->bound('agent_factory')) {
            $container->singleton('agent_factory', function() use ($container) {
                $agentRegistry = AgentRegistry::getInstance($container->make('logger'));
                return new AgentFactory($container, $agentRegistry);
            });
        }
        
        // Register the context manager if not already registered
        if (!$container->bound('context_manager')) {
            $container->singleton('context_manager', function() {
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
            global $mpai_container;
            if ($mpai_container && $mpai_container->bound('cache')) {
                $cacheService = $mpai_container->make('cache');
                
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
        global $mpai_container;
        if ($mpai_container && $mpai_container->bound('agent_orchestrator')) {
            $orchestrator = $mpai_container->make('agent_orchestrator');
            if (method_exists($orchestrator, 'setDefaultCacheTtl')) {
                $orchestrator->setDefaultCacheTtl($this->default_cache_ttl);
            }
        }
        
        return $this;
    }
}