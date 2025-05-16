<?php
/**
 * ContextManager Class
 *
 * Responsible for maintaining and managing context across agent interactions in the
 * MemberPress AI Assistant system. This class handles conversation history,
 * entity references, and state management for multi-turn interactions.
 *
 * @package MemberpressAiAssistant\Orchestration
 */

namespace MemberpressAiAssistant\Orchestration;

/**
 * Class ContextManager
 *
 * Manages context across agent interactions with support for different scopes,
 * context persistence, and optimization.
 */
class ContextManager {
    /**
     * Context scope constants
     */
    const SCOPE_GLOBAL = 'global';
    const SCOPE_CONVERSATION = 'conversation';
    const SCOPE_REQUEST = 'request';

    /**
     * Priority level constants
     */
    const PRIORITY_HIGH = 'high';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_LOW = 'low';

    /**
     * @var array Global context data (available to all agents)
     */
    private $globalContext = [];

    /**
     * @var array Conversation context data (specific to conversations)
     */
    private $conversationContext = [];

    /**
     * @var array Request context data (specific to individual requests)
     */
    private $requestContext = [];

    /**
     * @var array Entity tracking data (references to entities mentioned in conversations)
     */
    private $entityReferences = [];

    /**
     * @var int Maximum age for context entries in seconds (default: 1 hour)
     */
    private $contextExpirationTime = 3600;

    /**
     * @var int Maximum number of conversation turns to retain (default: 10)
     */
    private $maxConversationHistory = 10;

    /**
     * Constructor
     *
     * @param int $expirationTime Optional custom expiration time in seconds
     * @param int $maxHistory Optional custom max conversation history
     */
    public function __construct(int $expirationTime = 3600, int $maxHistory = 10) {
        $this->contextExpirationTime = $expirationTime;
        $this->maxConversationHistory = $maxHistory;
    }

    /**
     * Add context data to the specified scope
     *
     * @param string $key Context key
     * @param mixed $value Context value
     * @param string $scope Context scope (global, conversation, request)
     * @param string|null $conversationId Conversation ID (required for conversation scope)
     * @param string|null $requestId Request ID (required for request scope)
     * @param string $priority Priority level (high, medium, low)
     * @return bool Success status
     */
    public function addContext(
        string $key,
        $value,
        string $scope = self::SCOPE_CONVERSATION,
        ?string $conversationId = null,
        ?string $requestId = null,
        string $priority = self::PRIORITY_MEDIUM
    ): bool {
        $timestamp = time();
        $contextEntry = [
            'value' => $value,
            'timestamp' => $timestamp,
            'priority' => $priority,
            'expiration' => $timestamp + $this->contextExpirationTime
        ];

        switch ($scope) {
            case self::SCOPE_GLOBAL:
                $this->globalContext[$key] = $contextEntry;
                break;

            case self::SCOPE_CONVERSATION:
                if (empty($conversationId)) {
                    return false;
                }
                if (!isset($this->conversationContext[$conversationId])) {
                    $this->conversationContext[$conversationId] = [];
                }
                $this->conversationContext[$conversationId][$key] = $contextEntry;
                break;

            case self::SCOPE_REQUEST:
                if (empty($requestId)) {
                    return false;
                }
                if (!isset($this->requestContext[$requestId])) {
                    $this->requestContext[$requestId] = [];
                }
                $this->requestContext[$requestId][$key] = $contextEntry;
                break;

            default:
                return false;
        }

        return true;
    }

    /**
     * Get context data from the specified scope
     *
     * @param string $key Context key
     * @param string $scope Context scope (global, conversation, request)
     * @param string|null $conversationId Conversation ID (required for conversation scope)
     * @param string|null $requestId Request ID (required for request scope)
     * @param mixed $default Default value if context not found
     * @return mixed Context value or default
     */
    public function getContext(
        string $key,
        string $scope = self::SCOPE_CONVERSATION,
        ?string $conversationId = null,
        ?string $requestId = null,
        $default = null
    ) {
        // First check if the context exists in the specified scope
        $contextEntry = null;

        switch ($scope) {
            case self::SCOPE_GLOBAL:
                $contextEntry = $this->globalContext[$key] ?? null;
                break;

            case self::SCOPE_CONVERSATION:
                if (empty($conversationId) || !isset($this->conversationContext[$conversationId])) {
                    $contextEntry = null;
                } else {
                    $contextEntry = $this->conversationContext[$conversationId][$key] ?? null;
                }
                break;

            case self::SCOPE_REQUEST:
                if (empty($requestId) || !isset($this->requestContext[$requestId])) {
                    $contextEntry = null;
                } else {
                    $contextEntry = $this->requestContext[$requestId][$key] ?? null;
                }
                break;

            default:
                return $default;
        }

        // Check if context exists and is not expired
        if ($contextEntry !== null && time() <= $contextEntry['expiration']) {
            return $contextEntry['value'];
        }

        // If not found or expired, return default
        return $default;
    }

    /**
     * Add a message to the conversation history
     *
     * @param MessageProtocol $message Message to add
     * @param string $conversationId Conversation ID
     * @return bool Success status
     */
    public function addMessageToHistory(MessageProtocol $message, string $conversationId): bool {
        if (empty($conversationId)) {
            return false;
        }

        // Initialize conversation history if it doesn't exist
        if (!isset($this->conversationContext[$conversationId]['history'])) {
            $this->conversationContext[$conversationId]['history'] = [
                'value' => [],
                'timestamp' => time(),
                'priority' => self::PRIORITY_HIGH,
                'expiration' => time() + $this->contextExpirationTime
            ];
        }

        // Add message to history
        $history = &$this->conversationContext[$conversationId]['history']['value'];
        $history[] = $message->toArray();

        // Prune history if it exceeds the maximum size
        if (count($history) > $this->maxConversationHistory) {
            array_shift($history);
        }

        // Update timestamp
        $this->conversationContext[$conversationId]['history']['timestamp'] = time();
        $this->conversationContext[$conversationId]['history']['expiration'] = time() + $this->contextExpirationTime;

        return true;
    }

    /**
     * Get conversation history
     *
     * @param string $conversationId Conversation ID
     * @return array|null Conversation history or null if not found
     */
    public function getConversationHistory(string $conversationId): ?array {
        if (empty($conversationId) || 
            !isset($this->conversationContext[$conversationId]['history']) ||
            time() > $this->conversationContext[$conversationId]['history']['expiration']) {
            return null;
        }

        return $this->conversationContext[$conversationId]['history']['value'];
    }

    /**
     * Track an entity reference
     *
     * @param string $entityType Type of entity (e.g., 'user', 'product', 'membership')
     * @param string $entityId Entity identifier
     * @param array $metadata Additional entity metadata
     * @param string|null $conversationId Optional conversation ID to associate with
     * @return bool Success status
     */
    public function trackEntity(
        string $entityType,
        string $entityId,
        array $metadata = [],
        ?string $conversationId = null
    ): bool {
        $timestamp = time();
        
        // Create entity reference entry
        $entityEntry = [
            'type' => $entityType,
            'id' => $entityId,
            'metadata' => $metadata,
            'timestamp' => $timestamp,
            'expiration' => $timestamp + $this->contextExpirationTime,
            'conversations' => []
        ];

        // Add conversation ID if provided
        if (!empty($conversationId)) {
            $entityEntry['conversations'][] = $conversationId;
        }

        // Check if entity already exists
        $entityKey = $entityType . '_' . $entityId;
        if (isset($this->entityReferences[$entityKey])) {
            // Update existing entity
            $this->entityReferences[$entityKey]['metadata'] = array_merge(
                $this->entityReferences[$entityKey]['metadata'],
                $metadata
            );
            $this->entityReferences[$entityKey]['timestamp'] = $timestamp;
            $this->entityReferences[$entityKey]['expiration'] = $timestamp + $this->contextExpirationTime;
            
            // Add conversation ID if not already present
            if (!empty($conversationId) && 
                !in_array($conversationId, $this->entityReferences[$entityKey]['conversations'])) {
                $this->entityReferences[$entityKey]['conversations'][] = $conversationId;
            }
        } else {
            // Add new entity
            $this->entityReferences[$entityKey] = $entityEntry;
        }

        return true;
    }

    /**
     * Get entity reference
     *
     * @param string $entityType Type of entity
     * @param string $entityId Entity identifier
     * @return array|null Entity data or null if not found
     */
    public function getEntity(string $entityType, string $entityId): ?array {
        $entityKey = $entityType . '_' . $entityId;
        
        if (!isset($this->entityReferences[$entityKey]) || 
            time() > $this->entityReferences[$entityKey]['expiration']) {
            return null;
        }

        return $this->entityReferences[$entityKey];
    }

    /**
     * Get all entities of a specific type
     *
     * @param string $entityType Type of entity
     * @return array Array of entities of the specified type
     */
    public function getEntitiesByType(string $entityType): array {
        $entities = [];
        $currentTime = time();

        foreach ($this->entityReferences as $key => $entity) {
            if ($entity['type'] === $entityType && $currentTime <= $entity['expiration']) {
                $entities[] = $entity;
            }
        }

        return $entities;
    }

    /**
     * Get entities referenced in a conversation
     *
     * @param string $conversationId Conversation ID
     * @return array Array of entities referenced in the conversation
     */
    public function getEntitiesByConversation(string $conversationId): array {
        $entities = [];
        $currentTime = time();

        foreach ($this->entityReferences as $entity) {
            if (in_array($conversationId, $entity['conversations']) && 
                $currentTime <= $entity['expiration']) {
                $entities[] = $entity;
            }
        }

        return $entities;
    }

    /**
     * Extract context from a message and store it
     *
     * @param MessageProtocol $message Message to extract context from
     * @param string|null $conversationId Optional conversation ID
     * @return bool Success status
     */
    public function extractContextFromMessage(MessageProtocol $message, ?string $conversationId = null): bool {
        // Add message to conversation history if conversation ID is provided
        if (!empty($conversationId)) {
            $this->addMessageToHistory($message, $conversationId);
        }

        // Extract context from message metadata
        $metadata = $message->getMetadata();
        if (isset($metadata['context']) && is_array($metadata['context'])) {
            foreach ($metadata['context'] as $contextItem) {
                if (isset($contextItem['key'], $contextItem['value'], $contextItem['scope'])) {
                    $this->addContext(
                        $contextItem['key'],
                        $contextItem['value'],
                        $contextItem['scope'],
                        $conversationId,
                        $message->getId(),
                        $contextItem['priority'] ?? self::PRIORITY_MEDIUM
                    );
                }
            }
        }

        // Extract entity references from metadata
        if (isset($metadata['entities']) && is_array($metadata['entities'])) {
            foreach ($metadata['entities'] as $entity) {
                if (isset($entity['type'], $entity['id'])) {
                    $this->trackEntity(
                        $entity['type'],
                        $entity['id'],
                        $entity['metadata'] ?? [],
                        $conversationId
                    );
                }
            }
        }

        return true;
    }

    /**
     * Prune expired context entries
     *
     * @return int Number of pruned entries
     */
    public function pruneExpiredContext(): int {
        $prunedCount = 0;
        $currentTime = time();

        // Prune global context
        foreach ($this->globalContext as $key => $entry) {
            if ($currentTime > $entry['expiration']) {
                unset($this->globalContext[$key]);
                $prunedCount++;
            }
        }

        // Prune conversation context
        foreach ($this->conversationContext as $conversationId => $contextItems) {
            foreach ($contextItems as $key => $entry) {
                if ($currentTime > $entry['expiration']) {
                    unset($this->conversationContext[$conversationId][$key]);
                    $prunedCount++;
                }
            }
            
            // Remove empty conversation entries
            if (empty($this->conversationContext[$conversationId])) {
                unset($this->conversationContext[$conversationId]);
            }
        }

        // Prune request context
        foreach ($this->requestContext as $requestId => $contextItems) {
            foreach ($contextItems as $key => $entry) {
                if ($currentTime > $entry['expiration']) {
                    unset($this->requestContext[$requestId][$key]);
                    $prunedCount++;
                }
            }
            
            // Remove empty request entries
            if (empty($this->requestContext[$requestId])) {
                unset($this->requestContext[$requestId]);
            }
        }

        // Prune entity references
        foreach ($this->entityReferences as $key => $entity) {
            if ($currentTime > $entity['expiration']) {
                unset($this->entityReferences[$key]);
                $prunedCount++;
            }
        }

        return $prunedCount;
    }

    /**
     * Optimize context by removing low-priority items when context size exceeds limits
     *
     * @param int $maxGlobalItems Maximum number of global context items
     * @param int $maxConversationItems Maximum number of items per conversation
     * @param int $maxRequestItems Maximum number of items per request
     * @return int Number of removed items
     */
    public function optimizeContext(
        int $maxGlobalItems = 100,
        int $maxConversationItems = 50,
        int $maxRequestItems = 20
    ): int {
        $removedCount = 0;

        // Optimize global context
        if (count($this->globalContext) > $maxGlobalItems) {
            // Sort by priority and timestamp
            uasort($this->globalContext, function ($a, $b) {
                // First compare by priority
                $priorityOrder = [
                    self::PRIORITY_LOW => 0,
                    self::PRIORITY_MEDIUM => 1,
                    self::PRIORITY_HIGH => 2
                ];
                
                $priorityA = $priorityOrder[$a['priority']] ?? 1;
                $priorityB = $priorityOrder[$b['priority']] ?? 1;
                
                if ($priorityA !== $priorityB) {
                    return $priorityA <=> $priorityB;
                }
                
                // If same priority, compare by timestamp (older first)
                return $a['timestamp'] <=> $b['timestamp'];
            });
            
            // Remove excess items (lowest priority and oldest first)
            $itemsToRemove = count($this->globalContext) - $maxGlobalItems;
            $removed = array_splice($this->globalContext, 0, $itemsToRemove);
            $removedCount += count($removed);
        }

        // Optimize conversation context
        foreach ($this->conversationContext as $conversationId => $contextItems) {
            if (count($contextItems) > $maxConversationItems) {
                // Sort by priority and timestamp
                uasort($this->conversationContext[$conversationId], function ($a, $b) {
                    // First compare by priority
                    $priorityOrder = [
                        self::PRIORITY_LOW => 0,
                        self::PRIORITY_MEDIUM => 1,
                        self::PRIORITY_HIGH => 2
                    ];
                    
                    $priorityA = $priorityOrder[$a['priority']] ?? 1;
                    $priorityB = $priorityOrder[$b['priority']] ?? 1;
                    
                    if ($priorityA !== $priorityB) {
                        return $priorityA <=> $priorityB;
                    }
                    
                    // If same priority, compare by timestamp (older first)
                    return $a['timestamp'] <=> $b['timestamp'];
                });
                
                // Remove excess items (lowest priority and oldest first)
                $itemsToRemove = count($this->conversationContext[$conversationId]) - $maxConversationItems;
                $keys = array_keys($this->conversationContext[$conversationId]);
                for ($i = 0; $i < $itemsToRemove; $i++) {
                    // Preserve history if possible
                    if ($keys[$i] !== 'history') {
                        unset($this->conversationContext[$conversationId][$keys[$i]]);
                        $removedCount++;
                    }
                }
            }
        }

        // Optimize request context
        foreach ($this->requestContext as $requestId => $contextItems) {
            if (count($contextItems) > $maxRequestItems) {
                // Sort by priority and timestamp
                uasort($this->requestContext[$requestId], function ($a, $b) {
                    // First compare by priority
                    $priorityOrder = [
                        self::PRIORITY_LOW => 0,
                        self::PRIORITY_MEDIUM => 1,
                        self::PRIORITY_HIGH => 2
                    ];
                    
                    $priorityA = $priorityOrder[$a['priority']] ?? 1;
                    $priorityB = $priorityOrder[$b['priority']] ?? 1;
                    
                    if ($priorityA !== $priorityB) {
                        return $priorityA <=> $priorityB;
                    }
                    
                    // If same priority, compare by timestamp (older first)
                    return $a['timestamp'] <=> $b['timestamp'];
                });
                
                // Remove excess items (lowest priority and oldest first)
                $itemsToRemove = count($this->requestContext[$requestId]) - $maxRequestItems;
                $keys = array_keys($this->requestContext[$requestId]);
                for ($i = 0; $i < $itemsToRemove; $i++) {
                    unset($this->requestContext[$requestId][$keys[$i]]);
                    $removedCount++;
                }
            }
        }

        return $removedCount;
    }

    /**
     * Persist context to storage (can be implemented with various storage backends)
     *
     * @param string $storageKey Key to identify the stored context
     * @return bool Success status
     */
    public function persistContext(string $storageKey): bool {
        // This is a placeholder implementation
        // In a real implementation, this would save to a database, file, or cache
        
        $contextData = [
            'global' => $this->globalContext,
            'conversation' => $this->conversationContext,
            'request' => $this->requestContext,
            'entities' => $this->entityReferences,
            'timestamp' => time()
        ];
        
        // Example persistence using WordPress transients
        if (function_exists('set_transient')) {
            return set_transient('mpai_context_' . $storageKey, $contextData, $this->contextExpirationTime);
        }
        
        // Example persistence using file storage
        $contextJson = json_encode($contextData);
        $filePath = sys_get_temp_dir() . '/mpai_context_' . $storageKey . '.json';
        
        return file_put_contents($filePath, $contextJson) !== false;
    }

    /**
     * Load context from storage
     *
     * @param string $storageKey Key to identify the stored context
     * @return bool Success status
     */
    public function loadContext(string $storageKey): bool {
        // This is a placeholder implementation
        // In a real implementation, this would load from a database, file, or cache
        
        $contextData = null;
        
        // Example loading using WordPress transients
        if (function_exists('get_transient')) {
            $contextData = get_transient('mpai_context_' . $storageKey);
            if ($contextData !== false) {
                $this->globalContext = $contextData['global'] ?? [];
                $this->conversationContext = $contextData['conversation'] ?? [];
                $this->requestContext = $contextData['request'] ?? [];
                $this->entityReferences = $contextData['entities'] ?? [];
                return true;
            }
        }
        
        // Example loading using file storage
        $filePath = sys_get_temp_dir() . '/mpai_context_' . $storageKey . '.json';
        if (file_exists($filePath)) {
            $contextJson = file_get_contents($filePath);
            if ($contextJson !== false) {
                $contextData = json_decode($contextJson, true);
                if (is_array($contextData)) {
                    $this->globalContext = $contextData['global'] ?? [];
                    $this->conversationContext = $contextData['conversation'] ?? [];
                    $this->requestContext = $contextData['request'] ?? [];
                    $this->entityReferences = $contextData['entities'] ?? [];
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Clear all context data
     *
     * @return void
     */
    public function clearAllContext(): void {
        $this->globalContext = [];
        $this->conversationContext = [];
        $this->requestContext = [];
        $this->entityReferences = [];
    }

    /**
     * Clear context for a specific conversation
     *
     * @param string $conversationId Conversation ID
     * @return bool Success status
     */
    public function clearConversationContext(string $conversationId): bool {
        if (isset($this->conversationContext[$conversationId])) {
            unset($this->conversationContext[$conversationId]);
            
            // Also remove this conversation ID from entity references
            foreach ($this->entityReferences as &$entity) {
                $key = array_search($conversationId, $entity['conversations']);
                if ($key !== false) {
                    unset($entity['conversations'][$key]);
                    $entity['conversations'] = array_values($entity['conversations']); // Reindex array
                }
            }
            
            return true;
        }
        
        return false;
    }

    /**
     * Clear context for a specific request
     *
     * @param string $requestId Request ID
     * @return bool Success status
     */
    public function clearRequestContext(string $requestId): bool {
        if (isset($this->requestContext[$requestId])) {
            unset($this->requestContext[$requestId]);
            return true;
        }
        
        return false;
    }

    /**
     * Get context statistics
     *
     * @return array Statistics about the current context state
     */
    public function getContextStats(): array {
        $globalCount = count($this->globalContext);
        $conversationCount = 0;
        $requestCount = 0;
        $entityCount = count($this->entityReferences);
        $conversationIds = [];
        $requestIds = [];
        
        foreach ($this->conversationContext as $conversationId => $items) {
            $conversationCount += count($items);
            $conversationIds[] = $conversationId;
        }
        
        foreach ($this->requestContext as $requestId => $items) {
            $requestCount += count($items);
            $requestIds[] = $requestId;
        }
        
        return [
            'global_items' => $globalCount,
            'conversation_items' => $conversationCount,
            'request_items' => $requestCount,
            'entity_items' => $entityCount,
            'conversation_count' => count($this->conversationContext),
            'request_count' => count($this->requestContext),
            'conversation_ids' => $conversationIds,
            'request_ids' => $requestIds,
        ];
    }
}