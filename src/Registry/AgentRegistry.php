<?php
/**
 * Agent Registry
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Registry;

use MemberpressAiAssistant\Interfaces\AgentInterface;

/**
 * Registry for managing and discovering agents
 */
class AgentRegistry {
    /**
     * Singleton instance
     *
     * @var AgentRegistry
     */
    private static $instance = null;

    /**
     * Registered agents
     *
     * @var array<string, AgentInterface>
     */
    private $agents = [];

    /**
     * Logger instance
     *
     * @var mixed
     */
    private $logger;

    /**
     * Private constructor for singleton pattern
     *
     * @param mixed $logger Logger instance
     */
    private function __construct($logger = null) {
        $this->logger = $logger;
    }

    /**
     * Get singleton instance
     *
     * @param mixed $logger Logger instance
     * @return AgentRegistry
     */
    public static function getInstance($logger = null): AgentRegistry {
        if (self::$instance === null) {
            self::$instance = new self($logger);
        }
        return self::$instance;
    }

    /**
     * Prevent cloning of the instance
     *
     * @return void
     */
    private function __clone() {
        // Prevent cloning of the singleton instance
    }

    /**
     * Prevent unserializing of the instance
     *
     * @return void
     */
    public function __wakeup() {
        throw new \Exception('Cannot unserialize singleton');
    }

    /**
     * Register an agent
     *
     * @param AgentInterface $agent The agent to register
     * @return bool True if registration was successful, false otherwise
     */
    public function registerAgent(AgentInterface $agent): bool {
        $agentId = $agent->getAgentName();
        
        if ($this->hasAgent($agentId)) {
            if ($this->logger) {
                $this->logger->warning("Agent with ID {$agentId} is already registered");
            }
            return false;
        }
        
        $this->agents[$agentId] = $agent;
        
        if ($this->logger) {
            $this->logger->info("Agent {$agentId} registered successfully");
        }
        
        return true;
    }

    /**
     * Unregister an agent
     *
     * @param string $agentId The ID of the agent to unregister
     * @return bool True if unregistration was successful, false otherwise
     */
    public function unregisterAgent(string $agentId): bool {
        if (!$this->hasAgent($agentId)) {
            if ($this->logger) {
                $this->logger->warning("Agent with ID {$agentId} is not registered");
            }
            return false;
        }
        
        unset($this->agents[$agentId]);
        
        if ($this->logger) {
            $this->logger->info("Agent {$agentId} unregistered successfully");
        }
        
        return true;
    }

    /**
     * Check if an agent is registered
     *
     * @param string $agentId The ID of the agent to check
     * @return bool True if the agent is registered, false otherwise
     */
    public function hasAgent(string $agentId): bool {
        return isset($this->agents[$agentId]);
    }

    /**
     * Get a registered agent
     *
     * @param string $agentId The ID of the agent to get
     * @return AgentInterface|null The agent instance or null if not found
     */
    public function getAgent(string $agentId): ?AgentInterface {
        return $this->hasAgent($agentId) ? $this->agents[$agentId] : null;
    }

    /**
     * Get all registered agents
     *
     * @return array<string, AgentInterface> Array of registered agents
     */
    public function getAllAgents(): array {
        return $this->agents;
    }

    /**
     * Discover and register available agents
     *
     * @param string $directory Optional directory to scan for agents
     * @return int Number of agents discovered and registered
     */
    public function discoverAgents(string $directory = ''): int {
        $count = 0;
        
        // Register core agents (static registration)
        $count += $this->registerCoreAgents();
        
        // Discover plugin-based agents (dynamic discovery)
        $count += $this->discoverPluginAgents();
        
        // Scan directory for agent classes if provided
        if (!empty($directory) && is_dir($directory)) {
            $count += $this->scanDirectoryForAgents($directory);
        }
        
        if ($this->logger) {
            $this->logger->info("Discovered and registered {$count} agents");
        }
        
        return $count;
    }

    /**
     * Register core agents
     *
     * @return int Number of core agents registered
     */
    private function registerCoreAgents(): int {
        $count = 0;
        
        // Get core agent classes
        $coreAgentClasses = $this->getCoreAgentClasses();
        
        foreach ($coreAgentClasses as $agentClass) {
            if (class_exists($agentClass)) {
                try {
                    $agent = new $agentClass($this->logger);
                    if ($this->registerAgent($agent)) {
                        $count++;
                    }
                } catch (\Exception $e) {
                    if ($this->logger) {
                        $this->logger->error("Failed to instantiate core agent {$agentClass}: " . $e->getMessage());
                    }
                }
            }
        }
        
        return $count;
    }

    /**
     * Get core agent classes
     *
     * @return array List of core agent class names
     */
    private function getCoreAgentClasses(): array {
        // Register the core agent classes that we found in the src/Agents directory
        $coreAgents = [
            'MemberpressAiAssistant\\Agents\\ContentAgent',
            'MemberpressAiAssistant\\Agents\\MemberPressAgent',
            'MemberpressAiAssistant\\Agents\\SystemAgent',
            'MemberpressAiAssistant\\Agents\\ValidationAgent'
        ];
        
        // Log the core agents being registered
        if ($this->logger) {
            $this->logger->info("Registering core agents: " . implode(', ', $coreAgents));
        }
        
        // Allow plugins to add or remove core agents
        return apply_filters('mpai_core_agent_classes', $coreAgents);
    }

    /**
     * Discover plugin-based agents
     *
     * @return int Number of plugin agents registered
     */
    private function discoverPluginAgents(): int {
        $count = 0;
        
        // Get plugin agent classes through WordPress filter
        $pluginAgentClasses = apply_filters('mpai_plugin_agent_classes', []);
        
        foreach ($pluginAgentClasses as $agentClass) {
            if (class_exists($agentClass)) {
                try {
                    $agent = new $agentClass($this->logger);
                    if ($this->registerAgent($agent)) {
                        $count++;
                    }
                } catch (\Exception $e) {
                    if ($this->logger) {
                        $this->logger->error("Failed to instantiate plugin agent {$agentClass}: " . $e->getMessage());
                    }
                }
            }
        }
        
        return $count;
    }

    /**
     * Scan directory for agent classes
     *
     * @param string $directory Directory to scan
     * @return int Number of agents registered from directory scan
     */
    private function scanDirectoryForAgents(string $directory): int {
        $count = 0;
        
        // Get all PHP files in the directory
        $files = glob(rtrim($directory, '/') . '/*.php');
        
        if (!$files) {
            return 0;
        }
        
        foreach ($files as $file) {
            // Get the class name from the file
            $className = $this->getClassNameFromFile($file);
            
            if ($className && class_exists($className)) {
                try {
                    $reflection = new \ReflectionClass($className);
                    
                    // Check if the class implements AgentInterface
                    if ($reflection->implementsInterface(AgentInterface::class) && !$reflection->isAbstract()) {
                        $agent = new $className($this->logger);
                        if ($this->registerAgent($agent)) {
                            $count++;
                        }
                    }
                } catch (\Exception $e) {
                    if ($this->logger) {
                        $this->logger->error("Failed to load agent from file {$file}: " . $e->getMessage());
                    }
                }
            }
        }
        
        return $count;
    }

    /**
     * Get class name from file
     *
     * @param string $file File path
     * @return string|null Class name or null if not found
     */
    private function getClassNameFromFile(string $file): ?string {
        // Simple implementation - in a real-world scenario, this would be more robust
        $content = file_get_contents($file);
        if (!$content) {
            return null;
        }
        
        // Extract namespace
        $namespace = '';
        if (preg_match('/namespace\s+([^;]+);/i', $content, $matches)) {
            $namespace = $matches[1];
        }
        
        // Extract class name
        $className = null;
        if (preg_match('/class\s+(\w+)\s+/i', $content, $matches)) {
            $className = $matches[1];
        }
        
        if ($className) {
            return $namespace ? $namespace . '\\' . $className : $className;
        }
        
        return null;
    }

    /**
     * Find the best agent for a request
     *
     * @param array $request The request data
     * @return AgentInterface|null The best agent for the request or null if none found
     */
    public function findBestAgentForRequest(array $request): ?AgentInterface {
        $bestAgent = null;
        $highestScore = 0;
        
        foreach ($this->agents as $agent) {
            $score = $agent->getSpecializationScore($request);
            
            if ($score > $highestScore) {
                $highestScore = $score;
                $bestAgent = $agent;
            }
        }
        
        if ($this->logger && $bestAgent) {
            $this->logger->info("Found best agent for request: " . $bestAgent->getAgentName() . " with score {$highestScore}");
        }
        
        return $bestAgent;
    }

    /**
     * Find agents by capability
     *
     * @param string $capability The capability to search for
     * @return array<string, AgentInterface> Array of agents with the specified capability
     */
    public function findAgentsByCapability(string $capability): array {
        $matchingAgents = [];
        
        foreach ($this->agents as $agentId => $agent) {
            $capabilities = $agent->getCapabilities();
            
            if (isset($capabilities[$capability])) {
                $matchingAgents[$agentId] = $agent;
            }
        }
        
        return $matchingAgents;
    }

    /**
     * Find agents by specialization score
     *
     * @param array $request The request data
     * @param float $minimumScore Minimum specialization score (0-100)
     * @return array<string, array> Array of agents with their scores, sorted by score descending
     */
    public function findAgentsBySpecialization(array $request, float $minimumScore = 50.0): array {
        $matchingAgents = [];
        
        foreach ($this->agents as $agentId => $agent) {
            $score = $agent->getSpecializationScore($request);
            
            if ($score >= $minimumScore) {
                $matchingAgents[$agentId] = [
                    'agent' => $agent,
                    'score' => $score,
                ];
            }
        }
        
        // Sort by score descending
        uasort($matchingAgents, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        return $matchingAgents;
    }
}