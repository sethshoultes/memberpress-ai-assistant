<?php
/**
 * Abstract Agent
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Abstracts;

use MemberpressAiAssistant\Interfaces\AgentInterface;

/**
 * Abstract base class for all agents
 */
abstract class AbstractAgent implements AgentInterface {
    /**
     * Agent context
     *
     * @var array
     */
    protected $context = [];

    /**
     * Logger instance
     *
     * @var mixed
     */
    protected $logger;

    /**
     * Agent capabilities
     *
     * @var array
     */
    protected $capabilities = [];

    /**
     * Short-term memory (conversation history)
     *
     * @var array
     */
    protected $shortTermMemory = [];

    /**
     * Long-term memory (user preferences and persistent data)
     *
     * @var array
     */
    protected $longTermMemory = [];

    /**
     * Maximum size of short-term memory
     *
     * @var int
     */
    protected $shortTermMemoryLimit = 10;

    /**
     * Constructor
     *
     * @param mixed $logger Logger instance
     */
    public function __construct($logger = null) {
        $this->logger = $logger;
        $this->registerCapabilities();
        $this->loadLongTermMemory();
    }

    /**
     * {@inheritdoc}
     */
    public function getSpecializationScore(array $request): float {
        $score = 0.0;
        
        // Intent matching (0-30 points)
        $intentScore = $this->calculateIntentMatchScore($request);
        
        // Entity relevance (0-30 points)
        $entityScore = $this->calculateEntityRelevanceScore($request);
        
        // Capability matching (0-20 points)
        $capabilityScore = $this->calculateCapabilityMatchScore($request);
        
        // Context continuity (0-20 points)
        $contextScore = $this->calculateContextContinuityScore($request);
        
        // Calculate total score
        $score = $intentScore + $entityScore + $capabilityScore + $contextScore;
        
        // Apply any multipliers based on agent-specific criteria
        $score = $this->applyScoreMultipliers($score, $request);
        
        // Ensure score is between 0-100
        return max(0, min(100, $score));
    }

    /**
     * Calculate intent match score
     *
     * @param array $request The request data
     * @return float Score between 0-30
     */
    protected function calculateIntentMatchScore(array $request): float {
        // Base implementation - should be overridden by specific agents
        return 0.0;
    }

    /**
     * Calculate entity relevance score
     *
     * @param array $request The request data
     * @return float Score between 0-30
     */
    protected function calculateEntityRelevanceScore(array $request): float {
        // Base implementation - should be overridden by specific agents
        return 0.0;
    }

    /**
     * Calculate capability match score
     *
     * @param array $request The request data
     * @return float Score between 0-20
     */
    protected function calculateCapabilityMatchScore(array $request): float {
        // Base implementation - should be overridden by specific agents
        return 0.0;
    }

    /**
     * Calculate context continuity score
     *
     * @param array $request The request data
     * @return float Score between 0-20
     */
    protected function calculateContextContinuityScore(array $request): float {
        // Base implementation - should be overridden by specific agents
        return 0.0;
    }

    /**
     * Apply score multipliers based on agent-specific criteria
     *
     * @param float $score The current score
     * @param array $request The request data
     * @return float The adjusted score
     */
    protected function applyScoreMultipliers(float $score, array $request): float {
        // Base implementation - should be overridden by specific agents
        return $score;
    }

    /**
     * {@inheritdoc}
     */
    public function processRequest(array $request, array $context): array {
        $this->setContext($context);
        
        // Add request to short-term memory
        $this->remember('request', $request);
        
        // Log the request
        if ($this->logger) {
            $this->logger->info('Processing request with ' . $this->getAgentName(), [
                'request' => $request,
                'agent' => $this->getAgentName(),
            ]);
        }
        
        // Base implementation - should be overridden by specific agents
        return [
            'status' => 'error',
            'message' => 'Not implemented',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getCapabilities(): array {
        return $this->capabilities;
    }

    /**
     * Register the agent's capabilities
     *
     * @return void
     */
    protected function registerCapabilities(): void {
        // Base implementation - should be overridden by specific agents
        $this->capabilities = [];
    }

    /**
     * Add a capability to the agent
     *
     * @param string $capability The capability to add
     * @param array $metadata Optional metadata for the capability
     * @return void
     */
    protected function addCapability(string $capability, array $metadata = []): void {
        $this->capabilities[$capability] = [
            'name' => $capability,
            'metadata' => $metadata,
        ];
    }

    /**
     * Remove a capability from the agent
     *
     * @param string $capability The capability to remove
     * @return void
     */
    protected function removeCapability(string $capability): void {
        if (isset($this->capabilities[$capability])) {
            unset($this->capabilities[$capability]);
        }
    }

    /**
     * Check if the agent has a specific capability
     *
     * @param string $capability The capability to check
     * @return bool True if the agent has the capability, false otherwise
     */
    protected function hasCapability(string $capability): bool {
        return isset($this->capabilities[$capability]);
    }

    /**
     * Get capability metadata
     *
     * @param string $capability The capability to get metadata for
     * @return array|null The capability metadata or null if not found
     */
    protected function getCapabilityMetadata(string $capability): ?array {
        return $this->hasCapability($capability) ? $this->capabilities[$capability]['metadata'] : null;
    }

    /**
     * Set the agent context
     *
     * @param array $context The context data
     * @return void
     */
    public function setContext(array $context): void {
        $this->context = $context;
    }

    /**
     * Get the agent context
     *
     * @return array The context data
     */
    public function getContext(): array {
        return $this->context;
    }

    /**
     * Update the agent context
     *
     * @param array $contextUpdates The context updates
     * @return void
     */
    public function updateContext(array $contextUpdates): void {
        $this->context = array_merge($this->context, $contextUpdates);
    }

    /**
     * Store information in short-term memory
     *
     * @param string $key The memory key
     * @param mixed $value The memory value
     * @return void
     */
    protected function remember(string $key, $value): void {
        // Add timestamp for tracking recency
        $this->shortTermMemory[$key] = [
            'value' => $value,
            'timestamp' => time(),
        ];
        
        // Trim short-term memory if it exceeds the limit
        if (count($this->shortTermMemory) > $this->shortTermMemoryLimit) {
            // Sort by timestamp (oldest first)
            uasort($this->shortTermMemory, function($a, $b) {
                return $a['timestamp'] <=> $b['timestamp'];
            });
            
            // Remove oldest entry
            array_shift($this->shortTermMemory);
        }
    }

    /**
     * Retrieve information from memory (short-term first, then long-term)
     *
     * @param string $key The memory key
     * @param mixed $default Default value if key not found
     * @return mixed The memory value or default if not found
     */
    protected function recall(string $key, $default = null) {
        // Check short-term memory first
        if (isset($this->shortTermMemory[$key])) {
            return $this->shortTermMemory[$key]['value'];
        }
        
        // Then check long-term memory
        if (isset($this->longTermMemory[$key])) {
            return $this->longTermMemory[$key];
        }
        
        return $default;
    }

    /**
     * Remove information from memory
     *
     * @param string $key The memory key
     * @param bool $fromLongTerm Whether to remove from long-term memory
     * @return void
     */
    protected function forget(string $key, bool $fromLongTerm = false): void {
        // Remove from short-term memory
        if (isset($this->shortTermMemory[$key])) {
            unset($this->shortTermMemory[$key]);
        }
        
        // Remove from long-term memory if specified
        if ($fromLongTerm && isset($this->longTermMemory[$key])) {
            unset($this->longTermMemory[$key]);
            $this->saveLongTermMemory();
        }
    }

    /**
     * Store information in long-term memory
     *
     * @param string $key The memory key
     * @param mixed $value The memory value
     * @return void
     */
    protected function rememberLongTerm(string $key, $value): void {
        $this->longTermMemory[$key] = $value;
        $this->saveLongTermMemory();
    }

    /**
     * Load long-term memory from storage
     *
     * @return void
     */
    protected function loadLongTermMemory(): void {
        // Base implementation - should be overridden by specific agents
        // This would typically load from a database or file
        $this->longTermMemory = [];
    }

    /**
     * Save long-term memory to storage
     *
     * @return void
     */
    protected function saveLongTermMemory(): void {
        // Base implementation - should be overridden by specific agents
        // This would typically save to a database or file
    }

    /**
     * Clear short-term memory
     *
     * @return void
     */
    protected function clearShortTermMemory(): void {
        $this->shortTermMemory = [];
    }

    /**
     * Get all short-term memory
     *
     * @return array The short-term memory
     */
    protected function getShortTermMemory(): array {
        return $this->shortTermMemory;
    }

    /**
     * Get all long-term memory
     *
     * @return array The long-term memory
     */
    protected function getLongTermMemory(): array {
        return $this->longTermMemory;
    }
}