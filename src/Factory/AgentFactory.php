<?php
/**
 * Agent Factory
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Factory;

use MemberpressAiAssistant\DI\Container;
use MemberpressAiAssistant\Interfaces\AgentInterface;
use MemberpressAiAssistant\Registry\AgentRegistry;

/**
 * Factory for creating agent instances
 */
class AgentFactory {
    /**
     * Dependency injection container
     *
     * @var Container
     */
    protected $container;

    /**
     * Agent registry instance
     *
     * @var AgentRegistry
     */
    protected $registry;

    /**
     * Constructor
     *
     * @param Container $container Dependency injection container
     * @param AgentRegistry|null $registry Agent registry instance
     */
    public function __construct(Container $container, AgentRegistry $registry = null) {
        $this->container = $container;
        $this->registry = $registry ?? AgentRegistry::getInstance();
    }

    /**
     * Create an agent instance by class name
     *
     * @param string $agentClass The agent class name
     * @param array $parameters Additional constructor parameters
     * @return AgentInterface The created agent instance
     * @throws \Exception If the agent class is invalid
     */
    public function createAgent(string $agentClass, array $parameters = []): AgentInterface {
        // Validate the agent class
        $this->validateAgentClass($agentClass);

        // Create the agent instance using the DI container
        $agent = $this->container->make($agentClass, $parameters);

        return $agent;
    }

    /**
     * Create an agent instance by agent type
     *
     * @param string $agentType The agent type
     * @param array $parameters Additional constructor parameters
     * @return AgentInterface The created agent instance
     * @throws \Exception If the agent type is not found
     */
    public function createAgentByType(string $agentType, array $parameters = []): AgentInterface {
        // Get available agent types
        $agentTypes = $this->getAvailableAgentTypes();

        // Check if the agent type exists
        if (!isset($agentTypes[$agentType])) {
            throw new \Exception("Agent type '{$agentType}' not found");
        }

        // Get the agent class for the type
        $agentClass = $agentTypes[$agentType];

        // Create the agent instance
        return $this->createAgent($agentClass, $parameters);
    }

    /**
     * Create an agent instance and register it with the registry
     *
     * @param string $agentClass The agent class name
     * @param array $parameters Additional constructor parameters
     * @return AgentInterface The created agent instance
     * @throws \Exception If the agent class is invalid or registration fails
     */
    public function createAndRegisterAgent(string $agentClass, array $parameters = []): AgentInterface {
        // Create the agent instance
        $agent = $this->createAgent($agentClass, $parameters);

        // Register the agent with the registry
        $success = $this->registry->registerAgent($agent);

        if (!$success) {
            throw new \Exception("Failed to register agent '{$agentClass}'");
        }

        return $agent;
    }

    /**
     * Get available agent types
     *
     * @return array<string, string> Array of agent types mapped to their class names
     */
    public function getAvailableAgentTypes(): array {
        // This would typically be populated from a configuration file or database
        // For now, we'll use a WordPress filter to allow plugins to register agent types
        $agentTypes = apply_filters('mpai_agent_types', []);

        return $agentTypes;
    }

    /**
     * Validate an agent class
     *
     * @param string $agentClass The agent class to validate
     * @return bool True if the agent class is valid
     * @throws \Exception If the agent class is invalid
     */
    public function validateAgentClass(string $agentClass): bool {
        // Check if the class exists
        if (!class_exists($agentClass)) {
            throw new \Exception("Agent class '{$agentClass}' does not exist");
        }

        // Check if the class implements AgentInterface
        $reflection = new \ReflectionClass($agentClass);
        if (!$reflection->implementsInterface(AgentInterface::class)) {
            throw new \Exception("Class '{$agentClass}' does not implement AgentInterface");
        }

        // Check if the class is instantiable (not abstract)
        if ($reflection->isAbstract()) {
            throw new \Exception("Agent class '{$agentClass}' is abstract and cannot be instantiated");
        }

        return true;
    }
}