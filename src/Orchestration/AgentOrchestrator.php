<?php
/**
 * Agent Orchestrator
 *
 * Coordinates agent activities in the MemberPress AI Assistant system.
 * This class is responsible for agent selection, context management,
 * request routing, and inter-agent communication.
 *
 * @package MemberpressAiAssistant\Orchestration
 */

namespace MemberpressAiAssistant\Orchestration;

use MemberpressAiAssistant\Factory\AgentFactory;
use MemberpressAiAssistant\Interfaces\AgentInterface;
use MemberpressAiAssistant\Registry\AgentRegistry;
use MemberpressAiAssistant\Services\CacheService;

/**
 * Class AgentOrchestrator
 *
 * Core component that coordinates all agent activities in the system.
 */
class AgentOrchestrator {
    /**
     * Agent registry instance
     *
     * @var AgentRegistry
     */
    protected $agentRegistry;

    /**
     * Agent factory instance
     *
     * @var AgentFactory
     */
    protected $agentFactory;

    /**
     * Context manager instance
     *
     * @var ContextManager
     */
    protected $contextManager;

    /**
     * Current conversation ID
     *
     * @var string|null
     */
    protected $conversationId;

    /**
     * Logger instance
     *
     * @var mixed
     */
    protected $logger;

    /**
     * Cache service instance
     *
     * @var CacheService
     */
    protected $cacheService;

    /**
     * Default TTL for cached agent responses in seconds (10 minutes)
     *
     * @var int
     */
    protected $defaultCacheTtl = 600;

    /**
     * History of agent selections for the current conversation
     *
     * @var array
     */
    protected $agentSelectionHistory = [];
    
    /**
     * Cache for pattern-based agent selection
     * Maps request patterns to agent selections
     *
     * @var array
     */
    protected $patternCache = [];
    
    /**
     * Performance metrics for agent selection
     *
     * @var array
     */
    protected $performanceMetrics = [
        'pattern_cache_hits' => 0,
        'pattern_cache_misses' => 0,
        'early_terminations' => 0,
        'full_calculations' => 0,
        'selection_times' => [],
    ];
    
    /**
     * Confidence threshold for early termination
     * If an agent's score exceeds the next highest by this factor,
     * we can terminate early
     *
     * @var float
     */
    protected $confidenceThreshold = 1.5;

    /**
     * Delegation stack to track request delegations
     *
     * @var array
     */
    protected $delegationStack = [];

    /**
     * Maximum delegation depth to prevent infinite loops
     *
     * @var int
     */
    protected $maxDelegationDepth = 5;

    /**
     * Constructor
     *
     * @param AgentRegistry $agentRegistry Agent registry instance
     * @param AgentFactory $agentFactory Agent factory instance
     * @param ContextManager $contextManager Context manager instance
     * @param mixed $logger Logger instance
     */
    public function __construct(
        AgentRegistry $agentRegistry,
        AgentFactory $agentFactory,
        ContextManager $contextManager,
        $logger = null,
        CacheService $cacheService = null
    ) {
        $this->agentRegistry = $agentRegistry;
        $this->agentFactory = $agentFactory;
        $this->contextManager = $contextManager;
        $this->logger = $logger;
        $this->cacheService = $cacheService;
    }

    /**
     * Set the cache service
     *
     * @param CacheService $cacheService The cache service instance
     * @return self
     */
    public function setCacheService(CacheService $cacheService): self
    {
        $this->cacheService = $cacheService;
        return $this;
    }

    /**
     * Set the default TTL for cached agent responses
     *
     * @param int $ttl TTL in seconds
     * @return self
     */
    public function setDefaultCacheTtl(int $ttl): self
    {
        $this->defaultCacheTtl = max(0, $ttl);
        return $this;
    }

    /**
     * Process a user request
     *
     * This is the main entry point for processing user requests. It handles the entire
     * pipeline from request validation to agent selection, request routing, and response processing.
     *
     * @param array $request The user request data
     * @param string|null $conversationId Optional conversation ID for context
     * @return array The processed response
     */
    public function processUserRequest(array $request, ?string $conversationId = null): array {
        // Set or generate conversation ID
        $this->conversationId = $conversationId ?? $this->generateConversationId();

        // Log the incoming request
        if ($this->logger) {
            $this->logger->info('Processing user request', [
                'request' => $request,
                'conversation_id' => $this->conversationId,
            ]);
        }
        
        // Add direct error logging for debugging
        \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Orchestrator processing request: " . json_encode($request));

        try {
            // Validate the request
            $this->validateRequest($request);

            // Enrich request with context
            $enrichedRequest = $this->enrichRequestWithContext($request);

            // Check if we have a cached response for this request
            $cacheKey = $this->generateCacheKey($enrichedRequest);
            $cachedResponse = $this->getCachedResponse($cacheKey);
            
            if ($cachedResponse !== null) {
                // Log cache hit
                if ($this->logger) {
                    $this->logger->info('Cache hit for agent response', [
                        'cache_key' => $cacheKey,
                        'conversation_id' => $this->conversationId,
                    ]);
                }
                
                return $cachedResponse;
            }

            // Select appropriate agent(s)
            $selectedAgents = $this->selectAgentsForRequest($enrichedRequest);

            // If no suitable agent found
            if (empty($selectedAgents)) {
                // Get the intent data for better error message
                $intentData = $this->extractIntentAndEntities($enrichedRequest);
                $intent = $intentData['intent'] ?? 'unknown';
                
                if ($intent === 'unknown' || empty($intent)) {
                    return [
                        'status' => 'error',
                        'message' => 'I\'m not sure what you\'re asking for. Could you please provide more details?',
                        'conversation_id' => $this->conversationId,
                    ];
                } else {
                    return [
                        'status' => 'error',
                        'message' => 'No suitable agent found to handle your request about "' . $intent . '". Please try a different question.',
                        'conversation_id' => $this->conversationId,
                    ];
                }
            }

            // Process request with selected agent(s)
            $response = $this->routeRequestToAgents($enrichedRequest, $selectedAgents, $cacheKey);

            // Update context with new information from response
            $this->updateContextFromResponse($response);

            // Return the final response
            return $response;
        } catch (\Exception $e) {
            // Log the error
            if ($this->logger) {
                $this->logger->error('Error processing user request: ' . $e->getMessage(), [
                    'exception' => $e,
                    'request' => $request,
                    'conversation_id' => $this->conversationId,
                ]);
            }

            // Return error response
            return [
                'status' => 'error',
                'message' => 'Error processing request: ' . $e->getMessage(),
                'conversation_id' => $this->conversationId,
            ];
        }
    }

    /**
     * Validate the user request
     *
     * @param array $request The request to validate
     * @return bool True if valid
     * @throws \Exception If validation fails
     */
    protected function validateRequest(array $request): bool {
        // Check for required fields
        if (!isset($request['message'])) {
            throw new \Exception('Request must include a message');
        }

        return true;
    }

    /**
     * Enrich the request with context information
     *
     * @param array $request The original request
     * @return array The enriched request
     */
    protected function enrichRequestWithContext(array $request): array {
        // Get conversation context
        $conversationContext = $this->contextManager->getContext(
            'conversation_data',
            ContextManager::SCOPE_CONVERSATION,
            $this->conversationId
        ) ?? [];

        // Get entity references from context
        $entities = $this->contextManager->getEntitiesByConversation($this->conversationId);

        // Get conversation history
        $history = $this->contextManager->getConversationHistory($this->conversationId) ?? [];

        // Enrich the request with context
        $enrichedRequest = array_merge($request, [
            'context' => [
                'conversation' => $conversationContext,
                'entities' => $entities,
                'history' => $history,
                'previous_agents' => $this->agentSelectionHistory,
            ],
            'conversation_id' => $this->conversationId,
        ]);

        return $enrichedRequest;
    }

    /**
     * Select the most appropriate agent(s) for the request
     *
     * Implements the agent selection algorithm based on specialization scores,
     * context multipliers, and history weights. Includes optimizations for:
     * - Fast-path selection for common request patterns
     * - Progressive scoring with early termination
     * - Optimized calculation efficiency
     * - Performance metrics tracking
     *
     * @param array $request The enriched request
     * @return array Array of selected agents with scores
     */
    protected function selectAgentsForRequest(array $request): array {
        // Start timing the selection process
        $startTime = microtime(true);
        
        // Try fast-path selection first
        $fastPathResult = $this->tryFastPathSelection($request);
        if ($fastPathResult !== null) {
            // Record performance metrics
            $this->performanceMetrics['pattern_cache_hits']++;
            $this->performanceMetrics['selection_times'][] = microtime(true) - $startTime;
            
            // Log fast-path selection
            if ($this->logger) {
                $this->logger->info('Fast-path agent selection', [
                    'pattern' => $this->extractRequestPattern($request),
                    'agent' => reset($fastPathResult)['agent']->getAgentName(),
                    'conversation_id' => $this->conversationId,
                ]);
            }
            
            return $fastPathResult;
        }
        
        // Record cache miss
        $this->performanceMetrics['pattern_cache_misses']++;
        
        // Get all agents with their specialization scores
        $agentsWithScores = $this->agentRegistry->findAgentsBySpecialization($request, 0);
        
        // Apply progressive scoring with early termination
        $agentsWithScores = $this->applyProgressiveScoring($agentsWithScores, $request);
        
        // Sort by final score (descending)
        uasort($agentsWithScores, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        // Log agent selection
        if ($this->logger) {
            $this->logger->info('Agent selection results', [
                'agents' => array_map(function($item) {
                    return [
                        'name' => $item['agent']->getAgentName(),
                        'score' => $item['score'],
                    ];
                }, $agentsWithScores),
                'conversation_id' => $this->conversationId,
                'metrics' => $this->getPerformanceMetrics(),
            ]);
        }
        
        // Update agent selection history
        if (!empty($agentsWithScores)) {
            $topAgent = reset($agentsWithScores);
            $this->agentSelectionHistory[] = [
                'agent' => $topAgent['agent']->getAgentName(),
                'score' => $topAgent['score'],
                'timestamp' => time(),
            ];
            
            // Cache this pattern for future fast-path selection
            $this->cachePatternSelection($request, $agentsWithScores);
        }
        
        // Record full calculation
        $this->performanceMetrics['full_calculations']++;
        $this->performanceMetrics['selection_times'][] = microtime(true) - $startTime;
        
        return $agentsWithScores;
    }
    
    /**
     * Try to select agents using fast-path pattern recognition
     *
     * @param array $request The enriched request
     * @return array|null Array of selected agents or null if no pattern match
     */
    protected function tryFastPathSelection(array $request): ?array {
        // Extract pattern from request
        $pattern = $this->extractRequestPattern($request);
        
        // Check if we have a cached selection for this pattern
        if (isset($this->patternCache[$pattern])) {
            // Clone the cached result to avoid reference issues
            $cachedResult = $this->patternCache[$pattern];
            
            // Verify the cached agents still exist
            foreach ($cachedResult as $agentId => $data) {
                if (!$this->agentRegistry->hasAgent($agentId)) {
                    // If any agent is missing, invalidate the cache entry
                    unset($this->patternCache[$pattern]);
                    return null;
                }
            }
            
            return $cachedResult;
        }
        
        return null;
    }
    
    /**
     * Extract a pattern signature from a request for pattern matching
     *
     * @param array $request The request to extract a pattern from
     * @return string The pattern signature
     */
    protected function extractRequestPattern(array $request): string {
        // Extract key elements that define a pattern
        $message = $request['message'] ?? '';
        
        // Use NLP-like approach to extract key terms and intent
        $intentData = $this->extractIntentAndEntities($request);
        $intent = $intentData['intent'] ?? 'unknown';
        
        // Extract entity types (not specific IDs)
        $entityTypes = [];
        foreach (($intentData['entities'] ?? []) as $entity) {
            if (isset($entity['type'])) {
                $entityTypes[] = $entity['type'];
            }
        }
        sort($entityTypes); // Sort for consistency
        
        // Create a pattern signature
        $patternParts = [
            'intent' => $intent,
            'entity_types' => implode(',', $entityTypes),
            // Add message fingerprint (e.g., first few words or hash of key terms)
            'message_fp' => substr(md5($message), 0, 8),
        ];
        
        return md5(json_encode($patternParts));
    }
    
    /**
     * Cache a pattern-based selection for future fast-path lookups
     *
     * @param array $request The request that generated the selection
     * @param array $selection The selected agents with scores
     * @return void
     */
    protected function cachePatternSelection(array $request, array $selection): void {
        // Only cache if we have a clear winner (high confidence)
        if (count($selection) < 2) {
            return;
        }
        
        $values = array_values($selection);
        if (count($values) >= 2) {
            $topScore = $values[0]['score'];
            $runnerUpScore = $values[1]['score'];
            
            // Only cache patterns with clear winners
            if ($topScore > $runnerUpScore * $this->confidenceThreshold) {
                $pattern = $this->extractRequestPattern($request);
                $this->patternCache[$pattern] = $selection;
                
                // Limit cache size to prevent memory issues
                if (count($this->patternCache) > 100) {
                    // Remove oldest entry
                    array_shift($this->patternCache);
                }
            }
        }
    }
    
    /**
     * Apply progressive scoring with early termination
     *
     * @param array $agentsWithScores Agents with their base scores
     * @param array $request The request data
     * @return array Updated agents with adjusted scores
     */
    protected function applyProgressiveScoring(array $agentsWithScores, array $request): array {
        // Skip optimization if we have too few agents
        if (count($agentsWithScores) <= 2) {
            // Apply regular scoring
            $agentsWithScores = $this->applyContextMultipliers($agentsWithScores, $request);
            $agentsWithScores = $this->applyHistoryWeights($agentsWithScores, $request);
            return $agentsWithScores;
        }
        
        // Step 1: Apply context multipliers with progressive evaluation
        $agentsWithScores = $this->applyContextMultipliers($agentsWithScores, $request);
        
        // Check if we have a clear winner after context multipliers
        $earlyResult = $this->checkForClearWinner($agentsWithScores);
        if ($earlyResult !== null) {
            $this->performanceMetrics['early_terminations']++;
            return $earlyResult;
        }
        
        // Step 2: Apply history weights only if needed
        $agentsWithScores = $this->applyHistoryWeights($agentsWithScores, $request);
        
        return $agentsWithScores;
    }
    
    /**
     * Check if there's a clear winner in the current scoring
     *
     * @param array $agentsWithScores Agents with their current scores
     * @return array|null The winning agent(s) or null if no clear winner
     */
    protected function checkForClearWinner(array $agentsWithScores): ?array {
        // Need at least 2 agents to compare
        if (count($agentsWithScores) < 2) {
            return $agentsWithScores;
        }
        
        // Sort by score (descending)
        uasort($agentsWithScores, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        // Get top two scores
        $values = array_values($agentsWithScores);
        $topScore = $values[0]['score'];
        $runnerUpScore = $values[1]['score'];
        
        // If top score is significantly higher than runner-up
        if ($topScore > $runnerUpScore * $this->confidenceThreshold) {
            // Return only the clear winner(s) - include any tied for first
            $winners = [];
            foreach ($agentsWithScores as $agentId => $data) {
                if ($data['score'] >= $topScore * 0.99) { // Allow for minor floating point differences
                    $winners[$agentId] = $data;
                }
            }
            
            return $winners;
        }
        
        return null;
    }

    /**
     * Apply context multipliers to agent scores
     * Optimized version that combines multiplier calculation with application
     *
     * @param array $agentsWithScores Agents with their base scores
     * @param array $request The request data
     * @return array Updated agents with adjusted scores
     */
    protected function applyContextMultipliers(array $agentsWithScores, array $request): array {
        // Get entities from request context (once)
        $entities = $request['context']['entities'] ?? [];
        $entityTypes = [];
        
        // Extract entity types once (optimization)
        foreach ($entities as $entity) {
            if (isset($entity['type'])) {
                $entityTypes[$entity['type']] = true;
            }
        }
        
        // Get last agent for continuity check (once)
        $lastAgent = !empty($this->agentSelectionHistory)
            ? end($this->agentSelectionHistory)['agent']
            : null;
        
        // Apply multipliers directly without separate function call
        foreach ($agentsWithScores as $agentId => &$data) {
            $multiplier = 1.0;
            $agent = $data['agent'];
            
            // Check if agent has capabilities related to entities in context
            $capabilities = $agent->getCapabilities();
            
            // Optimize entity type checking
            foreach ($capabilities as $capabilityType => $capabilityValue) {
                if (isset($entityTypes[$capabilityType])) {
                    $multiplier *= 1.2; // 20% boost per relevant entity
                }
            }
            
            // Check for conversation continuity
            if ($lastAgent === $agentId) {
                // Boost score for the previously used agent for continuity
                $multiplier *= 1.1;
            }
            
            // Apply multiplier directly
            $data['score'] *= $multiplier;
            $data['context_multiplier'] = $multiplier;
        }
        
        return $agentsWithScores;
    }

    /**
     * Get context-based multipliers for agents
     *
     * This method is kept for backward compatibility but is no longer used
     * in the optimized implementation. The logic has been moved directly into
     * applyContextMultipliers for efficiency.
     *
     * @param array $request The request data
     * @return array Agent multipliers keyed by agent ID
     * @deprecated Use applyContextMultipliers directly
     */
    protected function getContextMultipliers(array $request): array {
        // This implementation is kept for backward compatibility
        $multipliers = [];
        
        // Get entities from request context
        $entities = $request['context']['entities'] ?? [];
        
        // Get all agents
        $agents = $this->agentRegistry->getAllAgents();
        
        foreach ($agents as $agentId => $agent) {
            $multiplier = 1.0;
            
            // Check if agent has capabilities related to entities in context
            $capabilities = $agent->getCapabilities();
            
            foreach ($entities as $entity) {
                $entityType = $entity['type'] ?? '';
                
                // If agent has capability related to this entity type, increase multiplier
                if (!empty($entityType) && isset($capabilities[$entityType])) {
                    $multiplier *= 1.2; // 20% boost per relevant entity
                }
            }
            
            // Check for conversation continuity
            if (!empty($this->agentSelectionHistory)) {
                $lastAgent = end($this->agentSelectionHistory)['agent'];
                if ($lastAgent === $agentId) {
                    // Boost score for the previously used agent for continuity
                    $multiplier *= 1.1;
                }
            }
            
            $multipliers[$agentId] = $multiplier;
        }
        
        return $multipliers;
    }

    /**
     * Apply history weights to agent scores
     * Optimized version that combines weight calculation with application
     *
     * @param array $agentsWithScores Agents with their scores
     * @param array $request The request data
     * @return array Updated agents with adjusted scores
     */
    protected function applyHistoryWeights(array $agentsWithScores, array $request): array {
        // Get previous agent selections (once)
        $previousAgents = $request['context']['previous_agents'] ?? [];
        
        // Pre-calculate agent frequency (optimization)
        $agentFrequency = [];
        foreach ($previousAgents as $selection) {
            $agentId = $selection['agent'];
            $agentFrequency[$agentId] = ($agentFrequency[$agentId] ?? 0) + 1;
        }
        
        // Apply weights directly
        foreach ($agentsWithScores as $agentId => &$data) {
            $weight = 0;
            
            // Add weight based on frequency (familiarity)
            $frequency = $agentFrequency[$agentId] ?? 0;
            $weight += min(10, $frequency * 2); // Up to 10 points for frequency
            
            // Add recency weight - only if we have previous selections
            if (!empty($previousAgents)) {
                foreach (array_reverse($previousAgents) as $index => $selection) {
                    if ($selection['agent'] === $agentId) {
                        // More recent = higher weight, but decaying
                        $recencyWeight = 5 / (1 + $index);
                        $weight += $recencyWeight;
                        break; // Only need the most recent occurrence
                    }
                }
            }
            
            // Apply weight directly
            $data['score'] += $weight;
            $data['history_weight'] = $weight;
        }
        
        return $agentsWithScores;
    }

    /**
     * Get history-based weights for agents
     *
     * This method is kept for backward compatibility but is no longer used
     * in the optimized implementation. The logic has been moved directly into
     * applyHistoryWeights for efficiency.
     *
     * @param array $request The request data
     * @return array Agent weights keyed by agent ID
     * @deprecated Use applyHistoryWeights directly
     */
    protected function getHistoryWeights(array $request): array {
        // This implementation is kept for backward compatibility
        $weights = [];
        
        // Get conversation history
        $history = $request['context']['history'] ?? [];
        
        // Get previous agent selections
        $previousAgents = $request['context']['previous_agents'] ?? [];
        
        // Count agent usage frequency
        $agentFrequency = [];
        foreach ($previousAgents as $selection) {
            $agentId = $selection['agent'];
            $agentFrequency[$agentId] = ($agentFrequency[$agentId] ?? 0) + 1;
        }
        
        // Get all agents
        $agents = $this->agentRegistry->getAllAgents();
        
        foreach ($agents as $agentId => $agent) {
            $weight = 0;
            
            // Add weight based on frequency (familiarity)
            $frequency = $agentFrequency[$agentId] ?? 0;
            $weight += min(10, $frequency * 2); // Up to 10 points for frequency
            
            // Add recency weight
            foreach (array_reverse($previousAgents) as $index => $selection) {
                if ($selection['agent'] === $agentId) {
                    // More recent = higher weight, but decaying
                    $recencyWeight = 5 / (1 + $index);
                    $weight += $recencyWeight;
                    break;
                }
            }
            
            $weights[$agentId] = $weight;
        }
        
        return $weights;
    }

    /**
     * Route the request to selected agents
     *
     * @param array $request The enriched request
     * @param array $selectedAgents The selected agents with scores
     * @return array The processed response
     */
    protected function routeRequestToAgents(array $request, array $selectedAgents, ?string $cacheKey = null): array {
        // Get the top-scoring agent
        $topAgent = reset($selectedAgents);
        $agent = $topAgent['agent'];
        
        // Prepare context for the agent
        $context = [
            'conversation_id' => $this->conversationId,
            'request_id' => $this->generateRequestId(),
            'timestamp' => time(),
        ];
        
        // Extract intent and add it to the request
        $intentData = $this->extractIntentAndEntitiesV2($request);
        $request['intent'] = $intentData['intent'];
        
        // Log the intent being passed to the agent
        \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Passing intent to agent: " . $request['intent']);
        \MemberpressAiAssistant\Utilities\debug_log("[MEMBERSHIP DEBUG] Agent selected: " . $agent->getAgentName() . " for intent: " . $request['intent']);
        
        // Process the request with the agent
        $response = $agent->processRequest($request, $context);
        
        // Check if the agent wants to delegate to another agent
        if (isset($response['delegate_to']) && !empty($response['delegate_to'])) {
            $delegatedResponse = $this->handleDelegation($request, $response, $selectedAgents);
            
            // Don't cache delegated responses as they're more complex
            return $delegatedResponse;
        }
        
        // Check if we need to aggregate results from multiple agents
        if (isset($request['aggregate_results']) && $request['aggregate_results'] === true) {
            $aggregatedResponse = $this->aggregateResults($request, $selectedAgents);
            
            // Don't cache aggregated responses as they're more complex
            return $aggregatedResponse;
        }
        
        // Cache the response if caching is enabled and we have a cache key
        if ($this->cacheService !== null && $cacheKey !== null) {
            $this->cacheResponse($cacheKey, $response);
            
            // Log cache store
            if ($this->logger) {
                $this->logger->info('Cached agent response', [
                    'cache_key' => $cacheKey,
                    'agent' => $agent->getAgentName(),
                    'conversation_id' => $this->conversationId,
                ]);
            }
        }
        
        return $response;
    }

    /**
     * Handle delegation between agents
     *
     * @param array $request The original request
     * @param array $response The response with delegation info
     * @param array $selectedAgents All selected agents
     * @return array The final response after delegation
     */
    protected function handleDelegation(array $request, array $response, array $selectedAgents): array {
        // Check delegation depth to prevent infinite loops
        if (count($this->delegationStack) >= $this->maxDelegationDepth) {
            if ($this->logger) {
                $this->logger->warning('Maximum delegation depth reached', [
                    'delegation_stack' => $this->delegationStack,
                    'conversation_id' => $this->conversationId,
                ]);
            }
            
            return [
                'status' => 'error',
                'message' => 'Maximum delegation depth reached',
                'original_response' => $response,
            ];
        }
        
        // Get the agent to delegate to
        $delegateToAgentId = $response['delegate_to'];
        $delegateAgent = $this->agentRegistry->getAgent($delegateToAgentId);
        
        if (!$delegateAgent) {
            return [
                'status' => 'error',
                'message' => "Agent '{$delegateToAgentId}' not found for delegation",
                'original_response' => $response,
            ];
        }
        
        // Add to delegation stack
        $this->delegationStack[] = [
            'from' => $response['agent'] ?? 'unknown',
            'to' => $delegateToAgentId,
            'timestamp' => time(),
        ];
        
        // Create delegation message
        $delegationMessage = MessageProtocol::createDelegation(
            $response['agent'] ?? 'system',
            $delegateToAgentId,
            $response['delegate_data'] ?? $request,
            [
                'original_request' => $request,
                'delegation_reason' => $response['delegation_reason'] ?? 'Not specified',
                'delegation_stack' => $this->delegationStack,
            ]
        );
        
        // Log delegation
        if ($this->logger) {
            $this->logger->info('Delegating request', [
                'from' => $response['agent'] ?? 'unknown',
                'to' => $delegateToAgentId,
                'reason' => $response['delegation_reason'] ?? 'Not specified',
                'conversation_id' => $this->conversationId,
            ]);
        }
        
        // Process with delegate agent
        $delegateContext = [
            'conversation_id' => $this->conversationId,
            'request_id' => $this->generateRequestId(),
            'timestamp' => time(),
            'is_delegation' => true,
            'delegation_stack' => $this->delegationStack,
        ];
        
        $delegateRequest = array_merge(
            $request,
            ['delegation_message' => $delegationMessage->toArray()]
        );
        
        $delegateResponse = $delegateAgent->processRequest($delegateRequest, $delegateContext);
        
        // Remove from delegation stack
        array_pop($this->delegationStack);
        
        // Add delegation metadata to response
        $delegateResponse['delegated_from'] = $response['agent'] ?? 'unknown';
        $delegateResponse['delegation_reason'] = $response['delegation_reason'] ?? 'Not specified';
        
        return $delegateResponse;
    }

    /**
     * Aggregate results from multiple agents
     *
     * @param array $request The original request
     * @param array $selectedAgents The selected agents with scores
     * @return array The aggregated response
     */
    protected function aggregateResults(array $request, array $selectedAgents): array {
        $responses = [];
        $aggregatedData = [];
        
        // Get top N agents (limit to 3 for performance)
        $topAgents = array_slice($selectedAgents, 0, 3, true);
        
        foreach ($topAgents as $agentId => $data) {
            $agent = $data['agent'];
            
            // Prepare context for the agent
            $context = [
                'conversation_id' => $this->conversationId,
                'request_id' => $this->generateRequestId(),
                'timestamp' => time(),
                'is_aggregation' => true,
            ];
            
            // Process the request with the agent
            $response = $agent->processRequest($request, $context);
            
            // Store the response
            $responses[$agentId] = $response;
            
            // Extract data for aggregation
            if (isset($response['data'])) {
                $aggregatedData[$agentId] = $response['data'];
            }
        }
        
        // Create aggregated response
        $aggregatedResponse = [
            'status' => 'success',
            'message' => 'Aggregated response from multiple agents',
            'agent' => 'orchestrator',
            'timestamp' => time(),
            'conversation_id' => $this->conversationId,
            'aggregated_data' => $aggregatedData,
            'individual_responses' => $responses,
        ];
        
        return $aggregatedResponse;
    }

    /**
     * Update context with information from the response
     *
     * @param array $response The response data
     * @return void
     */
    protected function updateContextFromResponse(array $response): void {
        // Extract entities from response
        if (isset($response['entities']) && is_array($response['entities'])) {
            foreach ($response['entities'] as $entity) {
                if (isset($entity['type'], $entity['id'])) {
                    $this->contextManager->trackEntity(
                        $entity['type'],
                        $entity['id'],
                        $entity['metadata'] ?? [],
                        $this->conversationId
                    );
                }
            }
        }
        
        // Update conversation context
        if (isset($response['context_updates']) && is_array($response['context_updates'])) {
            foreach ($response['context_updates'] as $key => $value) {
                $this->contextManager->addContext(
                    $key,
                    $value,
                    ContextManager::SCOPE_CONVERSATION,
                    $this->conversationId
                );
            }
            
            // Invalidate cache for this conversation when context changes
            $this->invalidateConversationCache();
        }
        
        // Create message from response and add to history
        $message = MessageProtocol::createResponse(
            $response['agent'] ?? 'system',
            'user',
            $response['message'] ?? $response,
            $response['request_id'] ?? 'unknown'
        );
        
        $result = $this->contextManager->addMessageToHistory($message, $this->conversationId);
        
        // Add logging for message history
        if (function_exists('error_log')) {
            \MemberpressAiAssistant\Utilities\debug_log('MPAI Debug - Added message to history: ' . ($result ? 'SUCCESS' : 'FAILED'));
            \MemberpressAiAssistant\Utilities\debug_log('MPAI Debug - Message content: ' . substr($message->getContent(), 0, 100) . '...');
            
            // Get the updated history to verify
            $history = $this->contextManager->getConversationHistory($this->conversationId);
            \MemberpressAiAssistant\Utilities\debug_log('MPAI Debug - Updated history count: ' . ($history ? count($history) : 'NULL'));
        }
        
        // Invalidate cache for this conversation when new messages are added
        $this->invalidateConversationCache();
    }

    /**
     * Generate a unique conversation ID
     *
     * @return string Unique conversation ID
     */
    protected function generateConversationId(): string {
        return 'conv_' . uniqid('', true);
    }

    /**
     * Generate a unique request ID
     *
     * @return string Unique request ID
     */
    protected function generateRequestId(): string {
        return 'req_' . uniqid('', true);
    }

    /**
     * Extract intent and entities from user request
     *
     * @param array $request The user request
     * @return array Extracted intent and entities
     */
    protected function extractIntentAndEntities(array $request): array {
        // This would typically use NLP/NLU services or libraries
        // For now, we'll use a simple keyword-based approach
        
        $message = $request['message'] ?? '';
        $intent = '';
        $entities = [];
        
        // Add direct error logging for debugging
        \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Extracting intent from message: " . $message);
        
        // Add detailed logging for intent detection
        \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Intent detection for message: " . $message);
        
        // Simple intent detection based on keywords
        // Check for specific intents first
        if (stripos($message, 'what plugins') !== false ||
            stripos($message, 'list plugins') !== false ||
            stripos($message, 'show plugins') !== false ||
            stripos($message, 'installed plugins') !== false) {
            $intent = 'list_plugins';
            \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Intent detected (specific pattern 0): list_plugins");
        } elseif (stripos($message, 'create membership') !== false ||
            stripos($message, 'add membership') !== false ||
            stripos($message, 'new membership') !== false) {
            $intent = 'create_membership';
            \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Intent detected (specific pattern 1): create_membership");
        } elseif (stripos($message, 'update membership') !== false ||
                 stripos($message, 'edit membership') !== false ||
                 stripos($message, 'modify membership') !== false) {
            $intent = 'update_membership';
            \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Intent detected (specific pattern 2): update_membership");
        } elseif (stripos($message, 'delete membership') !== false ||
                 stripos($message, 'remove membership') !== false) {
            $intent = 'delete_membership';
            \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Intent detected (specific pattern 3): delete_membership");
        } elseif (stripos($message, 'list memberships') !== false ||
                 stripos($message, 'show memberships') !== false ||
                 stripos($message, 'get memberships') !== false) {
            $intent = 'list_memberships';
            \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Intent detected (specific pattern 4): list_memberships");
        } elseif (stripos($message, 'create post') !== false ||
                 stripos($message, 'add post') !== false ||
                 stripos($message, 'new post') !== false) {
            $intent = 'create_post';
            \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Intent detected (specific pattern 5): create_post");
        } elseif (stripos($message, 'validate') !== false) {
            $intent = 'validate_input';
            \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Intent detected (specific pattern 6): validate_input");
        }
        // Then check for general categories
        elseif (stripos($message, 'hello') !== false || stripos($message, 'hi') !== false ||
               stripos($message, 'hey') !== false || stripos($message, 'greetings') !== false) {
            $intent = 'greeting';
            \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Intent detected (greeting): greeting");
        } elseif (stripos($message, 'help') !== false) {
            $intent = 'help';
            \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Intent detected (help): help");
        } elseif (stripos($message, 'create') !== false || stripos($message, 'add') !== false) {
            // If it contains "membership", use create_membership
            if (stripos($message, 'membership') !== false) {
                $intent = 'create_membership';
                \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Intent detected (create + membership): create_membership");
            } else {
                $intent = 'create';
                \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Intent detected (create): create");
            }
        } elseif (stripos($message, 'update') !== false || stripos($message, 'edit') !== false) {
            $intent = 'update';
            \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Intent detected (update): update");
        } elseif (stripos($message, 'delete') !== false || stripos($message, 'remove') !== false) {
            $intent = 'delete';
            \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Intent detected (delete): delete");
        } elseif (stripos($message, 'list') !== false || stripos($message, 'show') !== false) {
            $intent = 'list';
            \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Intent detected (list): list");
        } elseif (stripos($message, 'search') !== false || stripos($message, 'find') !== false) {
            $intent = 'search';
            \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Intent detected (search): search");
        } else {
            // Default to general intent if no specific intent is detected
            $intent = 'general';
            \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Intent detected (default): general");
        }
        
        // Log the detected intent for debugging
        if (isset($this->logger)) {
            $this->logger->info("Detected intent: {$intent} for message: {$message}");
        }
        
        // Simple entity extraction
        // Look for membership-related terms
        if (stripos($message, 'membership') !== false) {
            $entities[] = [
                'type' => 'membership',
                'value' => 'membership',
            ];
        }
        
        // Look for member-related terms
        if (stripos($message, 'member') !== false) {
            $entities[] = [
                'type' => 'member',
                'value' => 'member',
            ];
        }
        
        // Look for transaction-related terms
        if (stripos($message, 'transaction') !== false || stripos($message, 'payment') !== false) {
            $entities[] = [
                'type' => 'transaction',
                'value' => 'transaction',
            ];
        }
        
        // Add direct error logging for debugging
        \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - Extracted intent: " . $intent);
        
        return [
            'intent' => $intent,
            'entities' => $entities,
        ];
    }

    /**
     * Get the current conversation ID
     *
     * @return string|null Current conversation ID
     */
    public function getConversationId(): ?string {
        return $this->conversationId;
    }
    
    /**
     * Get the context manager instance
     *
     * @return ContextManager The context manager
     */
    public function getContextManager(): ContextManager {
        return $this->contextManager;
    }

    /**
     * Set the current conversation ID
     *
     * @param string $conversationId Conversation ID to set
     * @return void
     */
    public function setConversationId(string $conversationId): void {
        $this->conversationId = $conversationId;
    }

    /**
     * Get the agent selection history
     *
     * @return array Agent selection history
     */
    public function getAgentSelectionHistory(): array {
        return $this->agentSelectionHistory;
    }

    /**
     * Clear the current conversation context
     *
     * @return bool Success status
     */
    public function clearConversation(): bool {
        if ($this->conversationId) {
            $this->agentSelectionHistory = [];
            $this->delegationStack = [];
            return $this->contextManager->clearConversationContext($this->conversationId);
        }
        
        return false;
    }

    /**
     * Create a new conversation
     *
     * @return string New conversation ID
     */
    public function createNewConversation(): string {
        $this->conversationId = $this->generateConversationId();
        $this->agentSelectionHistory = [];
        $this->delegationStack = [];
        
        return $this->conversationId;
    }

    /**
     * Get statistics about the orchestrator
     *
     * @return array Statistics data
     */
    public function getStatistics(): array {
        return [
            'conversation_id' => $this->conversationId,
            'agent_selection_history' => $this->agentSelectionHistory,
            'delegation_stack' => $this->delegationStack,
            'context_stats' => $this->contextManager->getContextStats(),
            'performance_metrics' => $this->getPerformanceMetrics(),
        ];
    }
    
    /**
     * Get performance metrics for agent selection
     *
     * @return array Performance metrics
     */
    public function getPerformanceMetrics(): array {
        $metrics = $this->performanceMetrics;
        
        // Calculate average selection time
        if (!empty($metrics['selection_times'])) {
            $metrics['avg_selection_time'] = array_sum($metrics['selection_times']) / count($metrics['selection_times']);
            $metrics['max_selection_time'] = max($metrics['selection_times']);
            $metrics['min_selection_time'] = min($metrics['selection_times']);
        }
        
        // Calculate cache hit rate
        $totalCacheAttempts = $metrics['pattern_cache_hits'] + $metrics['pattern_cache_misses'];
        $metrics['pattern_cache_hit_rate'] = $totalCacheAttempts > 0
            ? $metrics['pattern_cache_hits'] / $totalCacheAttempts
            : 0;
            
        // Calculate early termination rate
        $totalCalculations = $metrics['early_terminations'] + $metrics['full_calculations'];
        $metrics['early_termination_rate'] = $totalCalculations > 0
            ? $metrics['early_terminations'] / $totalCalculations
            : 0;
        
        return $metrics;
    }
    
    /**
     * Reset performance metrics
     *
     * @return self
     */
    public function resetPerformanceMetrics(): self {
        $this->performanceMetrics = [
            'pattern_cache_hits' => 0,
            'pattern_cache_misses' => 0,
            'early_terminations' => 0,
            'full_calculations' => 0,
            'selection_times' => [],
        ];
        
        return $this;
    }
    
    /**
     * Set the confidence threshold for early termination
     *
     * @param float $threshold The threshold value (default: 1.5)
     * @return self
     */
    public function setConfidenceThreshold(float $threshold): self {
        $this->confidenceThreshold = max(1.0, $threshold);
        return $this;
    }
    
    /**
     * Generate a cache key for a request
     *
     * @param array $request The request to generate a key for
     * @return string The cache key
     */
    protected function generateCacheKey(array $request): string {
        // Extract the essential parts of the request for the cache key
        $keyParts = [
            'message' => $request['message'] ?? '',
            'conversation_id' => $this->conversationId,
        ];
        
        // Add entity references to the key if they exist
        if (isset($request['context']['entities'])) {
            $entityRefs = [];
            foreach ($request['context']['entities'] as $entity) {
                if (isset($entity['type'], $entity['id'])) {
                    $entityRefs[] = $entity['type'] . '_' . $entity['id'];
                }
            }
            if (!empty($entityRefs)) {
                sort($entityRefs); // Sort for consistency
                $keyParts['entities'] = implode(',', $entityRefs);
            }
        }
        
        // Add the last message from history if it exists
        if (isset($request['context']['history']) && !empty($request['context']['history'])) {
            $lastMessage = end($request['context']['history']);
            if (isset($lastMessage['content'])) {
                // If content is an array, convert to JSON for the key
                $content = is_array($lastMessage['content'])
                    ? json_encode($lastMessage['content'])
                    : $lastMessage['content'];
                $keyParts['last_message'] = $content;
            }
        }
        
        // Create a hash of the key parts
        return 'agent_response_' . md5(json_encode($keyParts));
    }
    
    /**
     * Get a cached response
     *
     * @param string $cacheKey The cache key
     * @return array|null The cached response or null if not found
     */
    protected function getCachedResponse(string $cacheKey): ?array {
        if ($this->cacheService === null) {
            return null;
        }
        
        $response = $this->cacheService->get($cacheKey);
        
        if ($response === null) {
            // Log cache miss
            if ($this->logger) {
                $this->logger->info('Cache miss for agent response', [
                    'cache_key' => $cacheKey,
                    'conversation_id' => $this->conversationId,
                ]);
            }
        }
        
        return $response;
    }
    
    /**
     * Cache a response
     *
     * @param string $cacheKey The cache key
     * @param array $response The response to cache
     * @return bool Whether the response was cached successfully
     */
    protected function cacheResponse(string $cacheKey, array $response): bool {
        if ($this->cacheService === null) {
            return false;
        }
        
        return $this->cacheService->set($cacheKey, $response, $this->defaultCacheTtl);
    }
    
    /**
     * Invalidate all cached responses for the current conversation
     *
     * @return int Number of cache entries invalidated
     */
    protected function invalidateConversationCache(): int {
        if ($this->cacheService === null || empty($this->conversationId)) {
            return 0;
        }
        
        $pattern = 'agent_response_' . $this->conversationId;
        $count = $this->cacheService->deletePattern($pattern);
        
        if ($count > 0 && $this->logger) {
            $this->logger->info('Invalidated conversation cache', [
                'conversation_id' => $this->conversationId,
                'count' => $count,
            ]);
        }
        
        return $count;
    }
    
    /**
     * Extract intent and entities from user request (Version 2)
     * This is a new implementation to avoid any caching issues
     *
     * @param array $request The user request
     * @return array Extracted intent and entities
     */
    protected function extractIntentAndEntitiesV2(array $request): array {
        $message = $request['message'] ?? '';
        $intent = '';
        $entities = [];
        
        \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - V2 Intent detection for message: " . $message);
        
        // Check for plugin-related intents first
        if (stripos($message, 'what plugins') !== false ||
            stripos($message, 'list plugins') !== false ||
            stripos($message, 'show plugins') !== false ||
            stripos($message, 'installed plugins') !== false) {
            $intent = 'list_plugins';
            \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - V2 Intent detected: list_plugins");
        }
        // Check for membership-related intents
        elseif (stripos($message, 'membership') !== false) {
            if (stripos($message, 'create') !== false || stripos($message, 'add') !== false || stripos($message, 'new') !== false) {
                $intent = 'create_membership';
                \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - V2 Intent detected: create_membership");
            } elseif (stripos($message, 'update') !== false || stripos($message, 'edit') !== false || stripos($message, 'modify') !== false) {
                $intent = 'update_membership';
                \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - V2 Intent detected: update_membership");
            } elseif (stripos($message, 'delete') !== false || stripos($message, 'remove') !== false) {
                $intent = 'delete_membership';
                \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - V2 Intent detected: delete_membership");
            } elseif (stripos($message, 'list') !== false || stripos($message, 'show') !== false || stripos($message, 'get') !== false) {
                $intent = 'list_memberships';
                \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - V2 Intent detected: list_memberships");
            } else {
                // Default to create_membership if it contains "membership" but no specific action
                $intent = 'create_membership';
                \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - V2 Intent detected (default membership): create_membership");
            }
        }
        // Check for post-related intents
        elseif (stripos($message, 'post') !== false) {
            if (stripos($message, 'create') !== false || stripos($message, 'add') !== false || stripos($message, 'new') !== false) {
                $intent = 'create_post';
                \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - V2 Intent detected: create_post");
            } elseif (stripos($message, 'update') !== false || stripos($message, 'edit') !== false || stripos($message, 'modify') !== false) {
                $intent = 'update_post';
                \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - V2 Intent detected: update_post");
            } elseif (stripos($message, 'delete') !== false || stripos($message, 'remove') !== false) {
                $intent = 'delete_post';
                \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - V2 Intent detected: delete_post");
            } elseif (stripos($message, 'list') !== false || stripos($message, 'show') !== false || stripos($message, 'get') !== false) {
                $intent = 'list_posts';
                \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - V2 Intent detected: list_posts");
            } else {
                $intent = 'create_post';
                \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - V2 Intent detected (default post): create_post");
            }
        }
        // Check for validation-related intents
        elseif (stripos($message, 'validate') !== false || stripos($message, 'validation') !== false) {
            $intent = 'validate_input';
            \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - V2 Intent detected: validate_input");
        }
        // Check for greeting intents
        elseif (stripos($message, 'hello') !== false || stripos($message, 'hi') !== false ||
               stripos($message, 'hey') !== false || stripos($message, 'greetings') !== false) {
            $intent = 'greeting';
            \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - V2 Intent detected: greeting");
        }
        // Check for help intents
        elseif (stripos($message, 'help') !== false) {
            $intent = 'help';
            \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - V2 Intent detected: help");
        }
        // Check for general action intents
        elseif (stripos($message, 'create') !== false || stripos($message, 'add') !== false || stripos($message, 'new') !== false) {
            $intent = 'create';
            \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - V2 Intent detected: create");
        } elseif (stripos($message, 'update') !== false || stripos($message, 'edit') !== false || stripos($message, 'modify') !== false) {
            $intent = 'update';
            \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - V2 Intent detected: update");
        } elseif (stripos($message, 'delete') !== false || stripos($message, 'remove') !== false) {
            $intent = 'delete';
            \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - V2 Intent detected: delete");
        } elseif (stripos($message, 'list') !== false || stripos($message, 'show') !== false || stripos($message, 'get') !== false) {
            $intent = 'list';
            \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - V2 Intent detected: list");
        } elseif (stripos($message, 'search') !== false || stripos($message, 'find') !== false) {
            $intent = 'search';
            \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - V2 Intent detected: search");
        } else {
            // Default to general intent if no specific intent is detected
            $intent = 'general';
            \MemberpressAiAssistant\Utilities\debug_log("MPAI Debug - V2 Intent detected (default): general");
        }
        
        // Simple entity extraction
        // Look for membership-related terms
        if (stripos($message, 'membership') !== false) {
            $entities[] = [
                'type' => 'membership',
                'value' => 'membership',
            ];
        }
        
        // Look for member-related terms
        if (stripos($message, 'member') !== false) {
            $entities[] = [
                'type' => 'member',
                'value' => 'member',
            ];
        }
        
        // Look for transaction-related terms
        if (stripos($message, 'transaction') !== false || stripos($message, 'payment') !== false) {
            $entities[] = [
                'type' => 'transaction',
                'value' => 'transaction',
            ];
        }
        
        return [
            'intent' => $intent,
            'entities' => $entities,
        ];
    }
}