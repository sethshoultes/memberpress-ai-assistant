# MemberPress AI Assistant: Agent Selection Algorithm Optimizations

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [Architectural Overview](#2-architectural-overview)
   - [2.1 High-Level Architecture](#21-high-level-architecture)
   - [2.2 Key Components](#22-key-components)
   - [2.3 Selection Process Flow](#23-selection-process-flow)
3. [Optimization Mechanisms](#3-optimization-mechanisms)
   - [3.1 Fast-Path Selection](#31-fast-path-selection)
   - [3.2 Progressive Scoring with Early Termination](#32-progressive-scoring-with-early-termination)
   - [3.3 Scoring Calculation Efficiency](#33-scoring-calculation-efficiency)
   - [3.4 Performance Metrics Tracking](#34-performance-metrics-tracking)
4. [Technical Implementation Details](#4-technical-implementation-details)
   - [4.1 Code Structure and Organization](#41-code-structure-and-organization)
   - [4.2 Key Method Details](#42-key-method-details)
   - [4.3 Configuration Options](#43-configuration-options)
5. [Performance Analysis](#5-performance-analysis)
   - [5.1 Interpreting Performance Metrics](#51-interpreting-performance-metrics)
   - [5.2 Analyzing Logs](#52-analyzing-logs)
   - [5.3 Potential Future Optimizations](#53-potential-future-optimizations)
6. [Developer Guide](#6-developer-guide)
   - [6.1 Extending the Agent Selection Algorithm](#61-extending-the-agent-selection-algorithm)
   - [6.2 Integration Patterns](#62-integration-patterns)
   - [6.3 Advanced Use Cases](#63-advanced-use-cases)
   - [6.4 Code Examples](#64-code-examples)
   - [6.5 Troubleshooting and Best Practices](#65-troubleshooting-and-best-practices)

## 1. Executive Summary

The agent selection algorithm in the MemberPress AI Assistant has been optimized to improve speed, efficiency, and accuracy in routing user requests to the most appropriate agent. This document provides comprehensive documentation of these optimizations, which include:

- **Fast-path selection** for common request patterns
- **Progressive scoring with early termination**
- **Optimized calculation efficiency**
- **Performance metrics tracking**

These optimizations collectively improve response times, reduce computational overhead, and maintain or enhance selection accuracy. The optimized algorithm is particularly effective for applications with recurring request patterns and multiple specialized agents.
## 2. Architectural Overview

### 2.1 High-Level Architecture

The agent selection system is a core component of the MemberPress AI Assistant, responsible for routing user requests to the most appropriate agent for processing. The system follows a pipeline architecture:

```mermaid
flowchart TB
  subgraph "Agent Selection System"
    A[User Request] --> B[Request Enrichment]
    B --> C[Agent Selection]
    C --> D[Request Processing]
    D --> E[Response Generation]
    
    subgraph "Optimized Selection Algorithm"
      C --> F[Fast-Path Selection]
      F -- "Cache Hit" --> J[Return Cached Selection]
      F -- "Cache Miss" --> G[Progressive Scoring]
      G --> H[Context Multipliers]
      H --> I[Early Termination Check]
      I -- "Clear Winner" --> K[Return Top Agent]
      I -- "No Clear Winner" --> L[Apply History Weights]
      L --> M[Final Agent Selection]
    end
  end
```

### 2.2 Key Components

The agent selection system consists of several key components that work together:

1. **AgentOrchestrator**: Central coordinator that manages the agent selection process and implements the optimized selection algorithm.

2. **AgentRegistry**: Repository of available agents with their capabilities and specializations. Provides methods to find agents based on their specialization scores.

3. **ContextManager**: Manages conversation context and history, providing contextual information for agent selection.

4. **Pattern Cache**: Stores previous agent selections for common request patterns, enabling fast-path selection.

5. **Performance Metrics Tracker**: Collects and reports on selection performance, including cache hit rates, early termination rates, and selection times.

### 2.3 Selection Process Flow

The optimized agent selection process follows these steps:

1. **Request Enrichment**: The user request is enriched with context, history, and entity information to provide a more complete picture for agent selection.

2. **Pattern Extraction**: A pattern signature is extracted from the request, capturing key elements like intent, entity types, and message fingerprint.

3. **Fast-Path Selection**: The system checks if a similar pattern has been processed before and, if so, returns the cached agent selection.

4. **Progressive Scoring** (if fast-path fails):
   - Apply context multipliers to base agent scores
   - Check for a clear winner (early termination)
   - Apply history weights only if needed

5. **Agent Selection**: The highest-scoring agent(s) are selected to process the request.

6. **Pattern Caching**: The selection is stored in the pattern cache for future similar requests.
## 3. Optimization Mechanisms

### 3.1 Fast-Path Selection

The fast-path selection mechanism provides a shortcut for handling common request patterns, bypassing the full scoring process when a similar request has been processed before.

```mermaid
flowchart TD
  A[Incoming Request] --> B[Extract Request Pattern]
  B --> C{Pattern in Cache?}
  C -- Yes --> D[Verify Cached Agents Exist]
  C -- No --> E[Proceed to Full Scoring]
  D -- Valid --> F[Return Cached Selection]
  D -- Invalid --> E
  F --> G[Log Cache Hit]
  E --> H[Log Cache Miss]
```

#### Key Features:

- **Pattern Extraction**: The `extractRequestPattern` method converts requests into consistent pattern signatures by extracting intent, entity types, and creating a message fingerprint.

- **Pattern Verification**: Before using a cached selection, the system verifies that all cached agents still exist in the registry.

- **Cache Management**: The pattern cache size is limited to prevent memory issues, with older entries being removed when the cache exceeds its size limit.

- **Performance Tracking**: The system tracks cache hits and misses to measure the effectiveness of the pattern cache.

### 3.2 Progressive Scoring with Early Termination

The progressive scoring mechanism applies scoring factors in stages, with checks for early termination to avoid unnecessary calculations.

```mermaid
flowchart TD
  A[Agent Base Scores] --> B[Apply Context Multipliers]
  B --> C{Clear Winner?}
  C -- Yes --> D[Early Termination]
  C -- No --> E[Apply History Weights]
  E --> F[Final Scoring]
  D --> G[Return Top Agent]
  F --> G
```

#### Key Features:

- **Staged Scoring**: Scoring factors are applied in order of computational cost, with less expensive operations performed first.

- **Early Termination**: The scoring process can terminate early if a clear winner emerges after applying context multipliers, avoiding the need for additional calculations.

- **Confidence Threshold**: A configurable threshold determines when an agent's score is significantly higher than others, allowing for early termination.

- **Performance Tracking**: The system tracks early terminations to measure the effectiveness of the progressive scoring mechanism.

### 3.3 Scoring Calculation Efficiency

The scoring calculation has been optimized for efficiency through several techniques:

1. **Combined Calculation and Application**: Previously separate methods for calculating and applying multipliers and weights have been merged to reduce function call overhead and improve efficiency.

2. **Optimized Entity Type Checking**: Associative arrays are used for faster lookups when checking if an agent has capabilities related to entities in the context.

3. **Pre-calculation of Frequencies**: Agent frequencies for history weights are computed once instead of repeatedly for each agent.

4. **Reduced Function Call Overhead**: Previously separate functions have been inlined to reduce the overhead of function calls.

Before optimization:
```php
// Calculate multipliers
$multipliers = $this->getContextMultipliers($request);

// Apply multipliers to scores
foreach ($agentsWithScores as $agentId => &$data) {
    $data['score'] *= $multipliers[$agentId] ?? 1.0;
}
```

After optimization:
```php
// Combined calculation and application
foreach ($agentsWithScores as $agentId => &$data) {
    $multiplier = 1.0;
    $agent = $data['agent'];
    
    // Calculate and apply multiplier directly
    // ... (calculation logic)
    
    $data['score'] *= $multiplier;
}
```

### 3.4 Performance Metrics Tracking

The algorithm tracks various performance metrics to enable monitoring and optimization:

```mermaid
flowchart TD
  A[Agent Selection Process] --> B[Track Selection Time]
  A --> C{Selection Path}
  C -- "Fast Path" --> D[Increment Cache Hits]
  C -- "Full Calculation" --> E[Increment Cache Misses]
  C -- "Early Termination" --> F[Increment Early Terminations]
## 4. Technical Implementation Details

### 4.1 Code Structure and Organization

The agent selection algorithm is primarily implemented in the `AgentOrchestrator` class, with the following key methods:

- **selectAgentsForRequest**: Main entry point for agent selection, orchestrates the entire selection process.

- **tryFastPathSelection**: Implements the fast-path selection mechanism, checking for cached selections.

- **extractRequestPattern**: Extracts pattern signatures from requests for pattern matching.

- **applyProgressiveScoring**: Implements the progressive scoring mechanism with early termination.

- **checkForClearWinner**: Determines if early termination is possible based on the current scores.

- **applyContextMultipliers**: Applies context-based score adjustments to agent scores.

- **applyHistoryWeights**: Applies history-based score adjustments to agent scores.

- **getPerformanceMetrics**: Calculates and returns performance metrics for analysis.

### 4.2 Key Method Details

#### 4.2.1 selectAgentsForRequest

This is the main entry point for agent selection, orchestrating the entire selection process:

```php
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
```

#### 4.2.2 tryFastPathSelection

This method implements the fast-path selection mechanism, checking for cached selections:

```php
protected function tryFastPathSelection(array $request): ?array {
    // Extract pattern from request
    $pattern = $this->extractRequestPattern($request);
    
    // Check if we have a cached selection for this pattern
    if (isset($this->patternCache[$pattern])) {
        // Clone the cached result to avoid reference issues
        $cachedResult = $this->patternCache[$pattern];
## 5. Performance Analysis

### 5.1 Interpreting Performance Metrics

The `getPerformanceMetrics()` method returns a comprehensive set of metrics that can be used to analyze the performance of the agent selection algorithm:

```php
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
```

#### Key Metrics to Monitor:

1. **Pattern Cache Hit Rate**: Higher is better, indicates effective pattern extraction
   - Target: > 50%
   - Action if low: Review pattern extraction logic, consider more generalized patterns

2. **Early Termination Rate**: Higher is better, indicates effective progressive scoring
   - Target: > 30%
   - Action if low: Adjust confidence threshold, review agent specialization scores

3. **Average Selection Time**: Lower is better, indicates overall efficiency
   - Target: < 5ms
   - Action if high: Review scoring calculations, optimize pattern extraction

### 5.2 Analyzing Logs

The agent selection algorithm logs key events that can be used for performance analysis:

1. **Fast-Path Selection**: Logged when a request is served from the pattern cache
   ```
   [info] Fast-path agent selection {pattern: "1a2b3c4d", agent: "ContentAgent", conversation_id: "conv_123"}
   ```

2. **Agent Selection Results**: Logged after agent selection is complete
   ```
   [info] Agent selection results {agents: [{name: "ContentAgent", score: 85}, ...], conversation_id: "conv_123", metrics: {...}}
   ```

3. **Cache Operations**: Logged when responses are cached or retrieved
   ```
   [info] Cached agent response {cache_key: "agent_response_abc123", agent: "ContentAgent", conversation_id: "conv_123"}
   [info] Cache hit for agent response {cache_key: "agent_response_abc123", conversation_id: "conv_123"}
   ```

#### Log Analysis Tips:

- **Monitor Cache Hit Rate Trends**: A declining cache hit rate may indicate changing request patterns or issues with pattern extraction.

- **Analyze Selection Times**: Sudden increases in selection times may indicate performance issues or changes in the request mix.

- **Review Early Termination Rate**: A low early termination rate may indicate that agent scores are too close together, making it difficult to identify clear winners.

### 5.3 Potential Future Optimizations

Based on the current implementation, several potential optimizations could be considered:

1. **Distributed Pattern Cache**: Implement a distributed cache for pattern-based selection in multi-server environments, enabling cache sharing across instances.
## 6. Developer Guide

### 6.1 Extending the Agent Selection Algorithm

#### 6.1.1 Custom Pattern Extraction

The pattern extraction mechanism can be extended to better handle domain-specific requests:

1. **Creating specialized pattern extractors for domain-specific requests**
   - Implement custom logic for identifying patterns in specific domains
   - Extract domain-specific intents and entities
   - Create more precise pattern signatures

   ```php
   // Example: Extending the pattern extraction for membership-related requests
   protected function extractRequestPattern(array $request): string {
       $message = $request['message'] ?? '';
       
       // Extract membership-specific intents
       $membershipIntent = $this->extractMembershipIntent($message);
       
       // Extract membership-related entities
       $membershipEntities = $this->extractMembershipEntities($message);
       
       // Create a pattern signature specific to memberships
       $patternParts = [
           'membership_intent' => $membershipIntent,
           'membership_entities' => implode(',', $membershipEntities),
           'message_fp' => substr(md5($message), 0, 8),
       ];
       
       return md5(json_encode($patternParts));
   }
   ```

2. **Implementing advanced NLP techniques**
   - Integrate with NLP services for better intent recognition
   - Use entity extraction libraries for improved entity recognition
   - Implement semantic analysis for better pattern matching

3. **Maintaining pattern consistency and uniqueness**
   - Ensure patterns are deterministic for similar requests
   - Balance specificity with generalization
   - Implement pattern normalization techniques

#### 6.1.2 Custom Scoring Mechanisms

The scoring system can be extended with additional factors:

1. **Creating new scoring factors**
   - Implement domain-specific scoring factors
   - Create time-based or location-based scoring
   - Add user preference-based scoring

   ```php
   // Example: Adding a user preference scoring factor
   protected function applyUserPreferenceScoring(array $agentsWithScores, array $request): array {
       // Get user preferences from the request context
       $userPreferences = $request['context']['user_preferences'] ?? [];
       
       foreach ($agentsWithScores as $agentId => &$data) {
           $agent = $data['agent'];
           $agentType = $agent->getAgentType();
           
           // Check if user has a preference for this agent type
           if (isset($userPreferences['preferred_agents']) && 
               in_array($agentType, $userPreferences['preferred_agents'])) {
               // Boost score for preferred agent types
               $data['score'] *= 1.2;
               $data['preference_factor'] = 1.2;
           } else {
               $data['preference_factor'] = 1.0;
           }
       }
       
       return $agentsWithScores;
   }
   ```

2. **Implementing domain-specific multipliers**
   - Create multipliers based on business rules
   - Implement industry-specific scoring adjustments
   - Develop user segment-specific multipliers

3. **Integrating external data sources**
   - Connect to CRM systems for customer-specific scoring
   - Use analytics data to inform agent selection
   - Integrate with business intelligence systems

#### 6.1.3 Confidence Threshold Customization

The confidence threshold mechanism can be customized:

1. **Strategies for tuning the confidence threshold**
   - Analyze historical data to determine optimal thresholds
   - Balance accuracy with performance
   - Consider the cost of incorrect agent selection

   ```php
   // Example: Setting different thresholds for different request types
#### 6.1.4 Pattern Cache Customization

The pattern cache can be customized for specific use cases:

1. **Implementing specialized cache strategies**
   - Create domain-specific cache segmentation
   - Implement priority-based caching
   - Develop user-specific cache partitions

2. **Custom eviction policies**
   - Implement frequency-based eviction
   - Create time-decay eviction policies
   - Develop importance-based retention

   ```php
   // Example: Implementing a frequency-based eviction policy
   protected function evictFromPatternCache(): void {
       // Track access frequency for cache entries
       $accessCounts = [];
       foreach ($this->patternCache as $pattern => $value) {
           $accessCounts[$pattern] = $this->cacheAccessCounts[$pattern] ?? 0;
       }
       
       // Sort by access count (ascending)
       asort($accessCounts);
       
       // Remove least frequently accessed entries until we're under the limit
       $entriesToRemove = count($this->patternCache) - $this->maxCacheSize;
       if ($entriesToRemove > 0) {
           foreach (array_keys($accessCounts) as $pattern) {
               unset($this->patternCache[$pattern]);
               unset($this->cacheAccessCounts[$pattern]);
               $entriesToRemove--;
               if ($entriesToRemove <= 0) break;
           }
       }
   }
   ```

3. **Persistent caching**
   - Implement database-backed pattern caches
   - Create distributed caching mechanisms
   - Develop cache warming strategies

#### 6.1.5 Custom Performance Metrics

The performance metrics system can be extended:

1. **Adding domain-specific performance metrics**
   - Track business-specific performance indicators
   - Implement user satisfaction metrics
   - Create domain-specific efficiency metrics

2. **Creating specialized analytics**
   - Develop visualization tools for selection quality
   - Implement trend analysis for pattern recognition
   - Create comparative analytics for agent performance

3. **Implementing feedback loops**
   - Develop user feedback integration
   - Create automatic performance optimization
   - Implement A/B testing frameworks

### 6.2 Integration Patterns

#### 6.2.1 Adding New Agent Types

Guidelines for integrating new agent types:

1. **Agent specialization definition**
   - Define clear areas of expertise
   - Create specific intent mappings
   - Implement entity type affinities

2. **Implementing capability declarations**
   ```php
   public function getCapabilities(): array {
       return [
           'product_catalog' => 0.9,  // High affinity for product catalog queries
           'pricing' => 0.8,          // Strong capability for pricing questions
           'inventory' => 0.7,        // Good capability for inventory questions
           'shipping' => 0.5,         // Moderate capability for shipping questions
       ];
   }
   ```

3. **Ensuring compatibility with the selection algorithm**
   - Implement all required interfaces
   - Follow scoring factor conventions
   - Maintain consistent capability scoring

#### 6.2.2 Custom Delegation Strategies

Implementing specialized delegation logic:

1. **Implementing specialized delegation logic**
   ```php
   public function processRequest(array $request, array $context): array {
       // Check if this request requires specialized knowledge
       if ($this->requiresSpecialistKnowledge($request)) {
           return [
               'status' => 'delegating',
               'message' => 'Delegating to specialist',
               'agent' => $this->getAgentName(),
               'delegate_to' => 'SpecialistAgent',
               'delegation_reason' => 'Request requires specialized knowledge',
               'delegate_data' => $this->prepareDataForSpecialist($request),
           ];
       }
       
       // Process normally if no delegation needed
       return $this->processNormally($request, $context);
   }
   ```

2. **Creating multi-stage delegation pipelines**
   - Implement delegation chains
   - Create delegation with feedback loops
   - Develop collaborative delegation models

### 6.3 Advanced Use Cases

#### 6.3.1 Multi-Tenant Implementations

Guidelines for implementing multi-tenant agent selection:

1. **Isolating selection patterns between tenants**
   - Implement tenant-specific pattern caches
   - Create tenant-specific pattern extractors
   - Develop tenant isolation mechanisms

   ```php
   // Example: Tenant-specific pattern cache
   protected function tryFastPathSelection(array $request): ?array {
       // Extract tenant ID from request
       $tenantId = $request['tenant_id'] ?? 'default';
       
       // Extract pattern from request
       $pattern = $this->extractRequestPattern($request);
       
       // Create tenant-specific cache key
       $cacheKey = $tenantId . ':' . $pattern;
       
       // Check if we have a cached selection for this pattern
       if (isset($this->patternCache[$cacheKey])) {
           // Use the tenant-specific cached result
           return $this->patternCache[$cacheKey];
       }
       
       return null;
   }
   ```

2. **Implementing tenant-specific optimizations**
   - Create tenant-specific confidence thresholds
   - Implement tenant-specific scoring factors
   - Develop tenant-specific performance metrics

3. **Managing shared vs. tenant-specific caches**
   - Implement cache partitioning strategies
   - Create cache isolation mechanisms
   - Develop shared pattern recognition

#### 6.3.2 High-Volume Scenarios

Optimizing for high-volume environments:

1. **Scaling strategies for the selection algorithm**
   - Implement distributed pattern caching
   - Create load-balanced agent selection
   - Develop asynchronous scoring mechanisms

2. **Optimizing for high request rates**
   - Implement request batching
   - Create prioritization mechanisms
   - Develop performance-focused pattern extraction

3. **Balancing memory usage with performance**
   - Implement efficient cache eviction policies
   - Create memory-conscious data structures
   - Develop streaming processing techniques

#### 6.3.3 Learning Systems Integration

Integrating with machine learning systems:

1. **Implementing feedback loops**
   - Collect selection quality metrics
   - Create reinforcement learning integration
   - Develop continuous improvement mechanisms

2. **Integrating with machine learning systems**
   ```php
   class MLEnhancedOrchestrator extends AgentOrchestrator {
       protected $mlClient;
       
       public function __construct(
           AgentRegistry $agentRegistry,
           AgentFactory $agentFactory,
           ContextManager $contextManager,
           $logger = null,
           CacheService $cacheService = null,
           MLClient $mlClient = null
       ) {
           parent::__construct($agentRegistry, $agentFactory, $contextManager, $logger, $cacheService);
           $this->mlClient = $mlClient;
       }
       
       protected function selectAgentsForRequest(array $request): array {
           // First try the standard selection process
           $selectedAgents = parent::selectAgentsForRequest($request);
           
           // If we have an ML client, enhance the selection
           if ($this->mlClient !== null && !empty($selectedAgents)) {
               $selectedAgents = $this->enhanceSelectionWithML($selectedAgents, $request);
           }
           
           return $selectedAgents;
       }
       
       protected function enhanceSelectionWithML(array $selectedAgents, array $request): array {
           // Get ML-based adjustments
           $mlAdjustments = $this->mlClient->getPredictedAgentScores($request);
           
           // Apply ML adjustments to the selected agents
           foreach ($selectedAgents as $agentId => &$data) {
               if (isset($mlAdjustments[$agentId])) {
                   $data['score'] *= $mlAdjustments[$agentId];
                   $data['ml_factor'] = $mlAdjustments[$agentId];
               }
           }
           
           // Re-sort by the adjusted scores
           uasort($selectedAgents, function($a, $b) {
               return $b['score'] <=> $a['score'];
           });
           
           return $selectedAgents;
       }
   }
   ```

3. **Creating self-tuning selection mechanisms**
   - Implement automatic threshold adjustment
   - Create pattern optimization mechanisms
   - Develop adaptive scoring systems

### 6.4 Code Examples

#### 6.4.1 Custom Pattern Extractor Implementation

```php
/**
 * Example of a custom pattern extractor for e-commerce requests
 */
class EcommercePatternExtractor extends BasePatternExtractor {
    public function extractPattern(array $request): string {
        // Custom implementation for e-commerce specific patterns
        $message = $request['message'] ?? '';
        
        // Extract product-related intents
        $productIntent = $this->extractProductIntent($message);
        
        // Extract category information
        $categoryInfo = $this->extractCategoryInfo($message);
        
        // Create a pattern signature specific to e-commerce
        $patternParts = [
            'product_intent' => $productIntent,
            'category_info' => $categoryInfo,
            'message_fp' => substr(md5($message), 0, 8),
        ];
        
        return md5(json_encode($patternParts));
    }
    
    protected function extractProductIntent(string $message): string {
        // Implementation details for extracting product intents
        if (stripos($message, 'buy') !== false || stripos($message, 'purchase') !== false) {
            return 'purchase_intent';
        } elseif (stripos($message, 'price') !== false || stripos($message, 'cost') !== false) {
            return 'price_inquiry';
        } elseif (stripos($message, 'compare') !== false) {
            return 'product_comparison';
        } else {
            return 'general_product_inquiry';
        }
    }
    
    protected function extractCategoryInfo(string $message): string {
        // Implementation details for extracting category information
        $categories = ['electronics', 'clothing', 'books', 'home', 'beauty'];
        $foundCategories = [];
        
        foreach ($categories as $category) {
            if (stripos($message, $category) !== false) {
                $foundCategories[] = $category;
            }
        }
        
        return !empty($foundCategories) ? implode(',', $foundCategories) : 'unknown';
    }
}
```

#### 6.4.2 Custom Scoring Factor Implementation

```php
/**
 * Example of a custom scoring factor for time-sensitive requests
 */
class TimeAwareScoring {
    /**
     * Apply time-based scoring adjustments
     *
     * @param array $agentsWithScores Agents with their current scores
     * @param array $request The request data
     * @return array Updated agents with adjusted scores
     */
    public function applyTimeFactors(array $agentsWithScores, array $request): array {
        // Get current time factors
        $hourOfDay = (int)date('G');
        $dayOfWeek = (int)date('w');
        
        // Apply time-based adjustments to each agent
        foreach ($agentsWithScores as $agentId => &$data) {
            $agent = $data['agent'];
            $timePreferences = $agent->getTimePreferences();
            
            // Check if agent has time-based specialization
            if (isset($timePreferences['peak_hours']) && 
                in_array($hourOfDay, $timePreferences['peak_hours'])) {
                // Boost score during agent's peak hours
                $data['score'] *= 1.15;
                $data['time_factor'] = 1.15;
            } else {
                $data['time_factor'] = 1.0;
            }
        }
        
        return $agentsWithScores;
    }
}
```

### 6.5 Troubleshooting and Best Practices

#### 6.5.1 Common Extension Issues

1. **Handling pattern collisions**
   - Ensure pattern extraction is sufficiently unique
   - Implement collision detection mechanisms
   - Create fallback strategies for collision resolution

2. **Debugging scoring anomalies**
   - Implement detailed score logging
   - Create score breakdown analysis
   - Develop scoring visualization tools

3. **Resolving cache inconsistencies**
   - Implement cache validation mechanisms
   - Create cache monitoring tools
   - Develop cache consistency checks

#### 6.5.2 Performance Optimization Guidelines

1. **Balancing memory usage with cache size**
   - Monitor memory consumption
   - Implement adaptive cache sizing
   - Create efficient data structures

2. **Optimizing pattern extraction for speed**
   - Minimize regex usage
   - Implement efficient string operations
   - Create optimized pattern signatures

3. **Efficient scoring calculation techniques**
   - Pre-compute common values
   - Implement early termination checks
   - Optimize data structures for scoring

#### 6.5.3 Testing Strategies

1. **Creating test fixtures for selection scenarios**
   - Develop representative request samples
   - Create agent capability profiles
   - Implement context simulation

2. **Benchmarking selection performance**
   - Measure selection times
   - Track cache hit rates
   - Monitor early termination rates

3. **Validating selection quality**
   - Implement selection accuracy metrics
   - Create A/B testing frameworks
   - Develop user satisfaction tracking
3. **Handling complex delegation scenarios**
   - Implement conditional delegation logic
   - Create context-aware delegation
   - Develop fallback mechanisms

#### 6.2.3 Context-Aware Selection Extensions

Extending the context-awareness of the selection algorithm:

1. **Implementing context providers**
   ```php
   class DomainContextProvider implements ContextProviderInterface {
       public function enrichContext(array $request, string $conversationId): array {
           // Add domain-specific context
           $domainContext = $this->getDomainContext($request);
           
           // Return the enriched request
           return array_merge($request, [
               'context' => array_merge(
                   $request['context'] ?? [],
                   ['domain_context' => $domainContext]
               ),
           ]);
       }
       
       protected function getDomainContext(array $request): array {
           // Implementation details...
       }
   }
   ```

2. **Creating context-aware multipliers**
   - Implement domain-specific context multipliers
   - Create user-specific context adjustments
   - Develop session-based context factors

3. **Developing entity recognition for domain-specific concepts**
   - Implement custom entity extractors
   - Create domain-specific entity types
   - Develop entity relationship mapping
   protected function getConfidenceThresholdForRequest(array $request): float {
       $intent = $this->extractIntentAndEntities($request)['intent'] ?? 'unknown';
       
       // Use different thresholds for different intents
       switch ($intent) {
           case 'help':
               return 1.3; // Lower threshold for help requests
           case 'create':
               return 1.7; // Higher threshold for creation requests
           default:
               return $this->confidenceThreshold; // Default threshold
       }
   }
   ```

2. **Implementing adaptive thresholds**
   - Adjust thresholds based on request complexity
   - Implement different thresholds for different request types
   - Create time-based threshold adjustments

3. **Balancing early termination with selection accuracy**
   - Monitor selection quality metrics
   - Implement feedback loops for threshold adjustment
   - Create A/B testing for threshold optimization

2. **Machine Learning Integration**: Use machine learning to improve pattern recognition and agent selection, potentially learning from user feedback and selection outcomes.

3. **Adaptive Confidence Threshold**: Dynamically adjust the confidence threshold based on historical performance, optimizing the balance between accuracy and performance.

4. **Request Categorization**: Pre-categorize requests to further optimize the selection process, potentially using a hierarchical selection approach.

5. **Parallel Scoring**: Implement parallel scoring for high-volume environments, distributing the scoring workload across multiple threads or processes.
        
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
```

#### 4.2.3 applyProgressiveScoring

This method implements the progressive scoring mechanism with early termination:

```php
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
```

### 4.3 Configuration Options

The agent selection algorithm provides several configuration options:

1. **Confidence Threshold**: Controls the threshold for early termination. A higher threshold requires a more dominant winner for early termination.

   ```php
   $orchestrator->setConfidenceThreshold(1.5); // Default is 1.5
   ```

2. **Cache TTL**: Controls the time-to-live for cached responses, determining how long cached selections remain valid.

   ```php
   $orchestrator->setDefaultCacheTtl(600); // Default is 600 seconds (10 minutes)
   ```

3. **Maximum Delegation Depth**: Controls the maximum depth of agent delegations to prevent infinite delegation loops.

   ```php
   // Set in the constructor or via a setter method
   protected $maxDelegationDepth = 5; // Default is 5
   ```
  C -- "Complete Calculation" --> G[Increment Full Calculations]
  D & E & F & G --> H[Calculate Performance Metrics]
  H --> I[Log Metrics]
```

#### Tracked Metrics:

- **Pattern Cache Hits/Misses**: Counts how often the pattern cache successfully provides a cached selection versus how often a full calculation is needed.

- **Early Terminations**: Counts how often the scoring process can terminate early due to a clear winner.

- **Selection Times**: Tracks the time taken for each agent selection operation, including minimum, maximum, and average times.

- **Cache Hit Rate**: Calculated as the percentage of requests served from the pattern cache.

- **Early Termination Rate**: Calculated as the percentage of scoring operations that terminate early.