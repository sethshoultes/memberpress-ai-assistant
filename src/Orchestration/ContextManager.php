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
        \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Adding message to history for conversation: ' . $conversationId);
        
        if (empty($conversationId)) {
            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Cannot add message to history: Empty conversation ID');
            return false;
        }

        // Initialize conversation history if it doesn't exist
        if (!isset($this->conversationContext[$conversationId]['history'])) {
            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Initializing new history for conversation: ' . $conversationId);
            
            $this->conversationContext[$conversationId]['history'] = [
                'value' => [],
                'timestamp' => time(),
                'priority' => self::PRIORITY_HIGH,
                'expiration' => time() + $this->contextExpirationTime
            ];
        } else {
            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('History already exists for conversation: ' . $conversationId .
                      ' with ' . count($this->conversationContext[$conversationId]['history']['value']) . ' items');
        }

        // Add message to history
        $history = &$this->conversationContext[$conversationId]['history']['value'];
        $messageArray = $message->toArray();
        $history[] = $messageArray;
        
        \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Added message to history: ' . substr(json_encode($messageArray), 0, 100) . '...');
        \MemberpressAiAssistant\Utilities\LoggingUtility::debug('History now has ' . count($history) . ' items');

        // Prune history if it exceeds the maximum size
        if (count($history) > $this->maxConversationHistory) {
            array_shift($history);
            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Pruned history to ' . count($history) . ' items');
        }

        // Update timestamp
        $this->conversationContext[$conversationId]['history']['timestamp'] = time();
        $this->conversationContext[$conversationId]['history']['expiration'] = time() + $this->contextExpirationTime;
        
        // Immediately persist the context after adding a message
        // Use just 'conversation_' as the prefix, since the conversationId already has 'conv_' prefix
        $this->persistContext('conversation_' . $conversationId);
        
        \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Message added to history successfully and context persisted');

        return true;
    }

    /**
     * Get conversation history
     *
     * @param string $conversationId Conversation ID
     * @return array|null Conversation history or null if not found
     */
    public function getConversationHistory(string $conversationId): ?array {
        \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Getting conversation history for ID: ' . $conversationId);
        \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Conversation context keys: ' . implode(', ', array_keys($this->conversationContext)));
        
        if (isset($this->conversationContext[$conversationId])) {
            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Conversation context exists for ID: ' . $conversationId);
            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Conversation context keys for this ID: ' .
                      implode(', ', array_keys($this->conversationContext[$conversationId])));
            
            if (isset($this->conversationContext[$conversationId]['history'])) {
                \MemberpressAiAssistant\Utilities\LoggingUtility::debug('History exists for this conversation');
                \MemberpressAiAssistant\Utilities\LoggingUtility::debug('History item count: ' .
                          count($this->conversationContext[$conversationId]['history']['value']));
            } else {
                \MemberpressAiAssistant\Utilities\LoggingUtility::debug('No history found for this conversation');
            }
        } else {
            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('No conversation context found for ID: ' . $conversationId);
        }
        
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
     * Persist context to storage
     *
     * @param string $storageKey Key to identify the stored context
     * @return bool Success status
     */
    public function persistContext(string $storageKey): bool {
        // Extract conversation ID from storage key
        $conversationId = null;
        if (strpos($storageKey, 'conversation_') === 0) {
            $conversationId = substr($storageKey, strlen('conversation_'));
        }
        
        // Log persistence attempt
        \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Persisting context with key: ' . $storageKey);
        
        // If we have a conversation ID, store just that conversation's data
        if ($conversationId && isset($this->conversationContext[$conversationId])) {
            // Store only the conversation data for this specific conversation ID
            $conversationData = $this->conversationContext[$conversationId];
            
            // Log what we're storing
            $historyCount = 0;
            if (isset($conversationData['history']['value'])) {
                $historyCount = count($conversationData['history']['value']);
                \MemberpressAiAssistant\Utilities\LoggingUtility::debug('History items for conversation ' . $conversationId . ': ' . $historyCount);
            }
            
            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Storing conversation data with ' . count($conversationData) . ' items and ' .
                      $historyCount . ' history items');
            
            // Log the size of the data being stored
            $serializedSize = strlen(serialize($conversationData));
            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Serialized conversation data size: ' . $serializedSize . ' bytes');
            
            // Try WordPress transients first (preferred method in WordPress environment)
            if (function_exists('set_transient')) {
                // Avoid duplicate 'conv_' prefix in the transient key
                $transientKey = 'mpai_' . $conversationId;
                $result = set_transient($transientKey, $conversationData, $this->contextExpirationTime);
                
                \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Transient set result: ' . ($result ? 'SUCCESS' : 'FAILURE'));
                \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Transient key used: ' . $transientKey);
                
                // Verify the transient was actually set
                $verifyData = get_transient($transientKey);
                \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Transient verification: ' . ($verifyData !== false ? 'EXISTS' : 'MISSING'));
                
                if ($result) {
                    \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Conversation data persisted using WordPress transients');
                }
                
                return $result;
            }
        } else {
            // Store the full context data (legacy method)
            $contextData = [
                'global' => $this->globalContext,
                'conversation' => $this->conversationContext,
                'request' => $this->requestContext,
                'entities' => $this->entityReferences,
                'timestamp' => time()
            ];
            
            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Storing full context data (legacy method)');
            
            // Try WordPress transients first (preferred method in WordPress environment)
            if (function_exists('set_transient')) {
                $result = set_transient('mpai_context_' . $storageKey, $contextData, $this->contextExpirationTime);
                
                if ($result) {
                    \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Full context persisted using WordPress transients');
                }
                
                return $result;
            }
        }
        
        // Fall back to file storage if transients not available
        try {
            // Create a storage directory if it doesn't exist
            $storageDir = WP_CONTENT_DIR . '/mpai-context';
            if (!file_exists($storageDir) && !mkdir($storageDir, 0755, true)) {
                \MemberpressAiAssistant\Utilities\LoggingUtility::error('Failed to create context storage directory');
                return false;
            }
            
            // If we have conversation-specific data, store it in a separate file
            if ($conversationId && isset($this->conversationContext[$conversationId])) {
                $conversationData = $this->conversationContext[$conversationId];
                $convJson = json_encode($conversationData);
                
                if ($convJson === false) {
                    \MemberpressAiAssistant\Utilities\LoggingUtility::error('Failed to encode conversation data to JSON');
                    return false;
                }
                
                // Avoid duplicate 'conv_' prefix in the file path
                $convFilePath = $storageDir . '/mpai_' . $conversationId . '.json';
                $result = file_put_contents($convFilePath, $convJson);
                
                if ($result !== false) {
                    \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Conversation data persisted to file: ' . $convFilePath);
                    \MemberpressAiAssistant\Utilities\LoggingUtility::debug('File size: ' . filesize($convFilePath) . ' bytes');
                }
                
                return $result !== false;
            } else {
                // Legacy method - store full context
                $contextJson = json_encode($contextData);
                if ($contextJson === false) {
                    \MemberpressAiAssistant\Utilities\LoggingUtility::error('Failed to encode context data to JSON');
                    return false;
                }
                
                $filePath = $storageDir . '/mpai_context_' . $storageKey . '.json';
                $result = file_put_contents($filePath, $contextJson);
                
                if ($result !== false) {
                    \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Context persisted to file: ' . $filePath);
                    \MemberpressAiAssistant\Utilities\LoggingUtility::debug('File size: ' . filesize($filePath) . ' bytes');
                }
                
                return $result !== false;
            }
        } catch (\Exception $e) {
            \MemberpressAiAssistant\Utilities\LoggingUtility::error('Error persisting context: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Load context from storage
     *
     * @param string $storageKey Key to identify the stored context
     * @return bool Success status
     */
    public function loadContext(string $storageKey): bool {
        // Extract conversation ID from storage key
        $conversationId = null;
        if (strpos($storageKey, 'conversation_') === 0) {
            $conversationId = substr($storageKey, strlen('conversation_'));
        }
        
        // Log loading attempt
        \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Loading context with key: ' . $storageKey);
        if ($conversationId) {
            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Extracted conversation ID: ' . $conversationId);
        }
        
        // If we have a conversation ID, try to load just that conversation's data first
        if ($conversationId && function_exists('get_transient')) {
            // Avoid duplicate 'conv_' prefix in the transient key
            $transientKey = 'mpai_' . $conversationId;
            
            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Trying to load conversation data with transient key: ' . $transientKey);
            
            $conversationData = get_transient($transientKey);
            
            if ($conversationData !== false) {
                // We found the conversation data, set it in the context
                $this->conversationContext[$conversationId] = $conversationData;
                
                \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Conversation data loaded from WordPress transients');
                
                // Log conversation context details
                \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Loaded conversation context for ID: ' . $conversationId);
                \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Conversation context keys: ' .
                          implode(', ', array_keys($this->conversationContext[$conversationId])));
                
                if (isset($this->conversationContext[$conversationId]['history'])) {
                    \MemberpressAiAssistant\Utilities\LoggingUtility::debug('History found in loaded context');
                    \MemberpressAiAssistant\Utilities\LoggingUtility::debug('History item count: ' .
                              count($this->conversationContext[$conversationId]['history']['value']));
                    
                    // Log the first message to verify content
                    if (!empty($this->conversationContext[$conversationId]['history']['value'])) {
                        $firstMsg = $this->conversationContext[$conversationId]['history']['value'][0];
                        \MemberpressAiAssistant\Utilities\LoggingUtility::debug('First history message type: ' .
                                 ($firstMsg['type'] ?? 'unknown') . ', sender: ' .
                                 ($firstMsg['sender'] ?? 'unknown'));
                    }
                } else {
                    \MemberpressAiAssistant\Utilities\LoggingUtility::debug('No history found in loaded context');
                }
                
                return true;
            } else {
                \MemberpressAiAssistant\Utilities\LoggingUtility::debug('No conversation data found with transient key: ' . $transientKey);
            }
        }
        
        // Fall back to the legacy method if conversation-specific loading failed
        \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Falling back to legacy context loading method');
        
        // Check if transient exists before trying to get it
        if (function_exists('get_option') && $conversationId) {
            $legacyTransientKey = 'mpai_context_' . $storageKey;
            $transientExists = get_transient($legacyTransientKey) !== false;
            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Legacy transient exists check: ' . ($transientExists ? 'YES' : 'NO'));
            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Legacy transient key: ' . $legacyTransientKey);
        }
        
        // Check if file exists
        $storageDir = WP_CONTENT_DIR . '/mpai-context';
        $filePath = $storageDir . '/mpai_context_' . $storageKey . '.json';
        \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Context file exists check: ' . (file_exists($filePath) ? 'YES' : 'NO'));
        if (file_exists($filePath)) {
            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Context file path: ' . $filePath);
        }
        
        $contextData = null;
        
        // Try WordPress transients with legacy key format
        if (function_exists('get_transient')) {
            $contextData = get_transient('mpai_context_' . $storageKey);
            if ($contextData !== false) {
                $this->globalContext = $contextData['global'] ?? [];
                $this->conversationContext = $contextData['conversation'] ?? [];
                $this->requestContext = $contextData['request'] ?? [];
                $this->entityReferences = $contextData['entities'] ?? [];
                
                \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Context loaded from WordPress transients (legacy format)');
                
                // Log conversation context details
                if ($conversationId && isset($this->conversationContext[$conversationId])) {
                    \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Loaded conversation context for ID: ' . $conversationId);
                    \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Conversation context keys: ' .
                              implode(', ', array_keys($this->conversationContext[$conversationId])));
                    
                    if (isset($this->conversationContext[$conversationId]['history'])) {
                        \MemberpressAiAssistant\Utilities\LoggingUtility::debug('History found in loaded context');
                        \MemberpressAiAssistant\Utilities\LoggingUtility::debug('History item count: ' .
                                  count($this->conversationContext[$conversationId]['history']['value']));
                    } else {
                        \MemberpressAiAssistant\Utilities\LoggingUtility::debug('No history found in loaded context');
                    }
                } else if ($conversationId) {
                    \MemberpressAiAssistant\Utilities\LoggingUtility::debug('No conversation context found for ID: ' . $conversationId . ' after loading');
                }
                
                return true;
            }
        }
        
        // Fall back to file storage if transients not available or empty
        try {
            // First try conversation-specific file if we have a conversation ID
            if ($conversationId) {
                $storageDir = WP_CONTENT_DIR . '/mpai-context';
                // Avoid duplicate 'conv_' prefix in the file path
                $convFilePath = $storageDir . '/mpai_' . $conversationId . '.json';
                
                \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Checking for conversation-specific file: ' . $convFilePath);
                \MemberpressAiAssistant\Utilities\LoggingUtility::debug('File exists: ' . (file_exists($convFilePath) ? 'YES' : 'NO'));
                
                if (file_exists($convFilePath)) {
                    $convJson = file_get_contents($convFilePath);
                    if ($convJson !== false) {
                        $convData = json_decode($convJson, true);
                        if (is_array($convData)) {
                            // Store just this conversation's data
                            $this->conversationContext[$conversationId] = $convData;
                            
                            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Conversation data loaded from file: ' . $convFilePath);
                            
                            // Log conversation context details
                            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Loaded conversation context for ID: ' . $conversationId . ' from file');
                            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Conversation context keys: ' .
                                      implode(', ', array_keys($this->conversationContext[$conversationId])));
                            
                            if (isset($this->conversationContext[$conversationId]['history'])) {
                                \MemberpressAiAssistant\Utilities\LoggingUtility::debug('History found in loaded context from file');
                                \MemberpressAiAssistant\Utilities\LoggingUtility::debug('History item count: ' .
                                          count($this->conversationContext[$conversationId]['history']['value']));
                                
                                // Log the first message to verify content
                                if (!empty($this->conversationContext[$conversationId]['history']['value'])) {
                                    $firstMsg = $this->conversationContext[$conversationId]['history']['value'][0];
                                    \MemberpressAiAssistant\Utilities\LoggingUtility::debug('First history message type: ' .
                                             ($firstMsg['type'] ?? 'unknown') . ', sender: ' .
                                             ($firstMsg['sender'] ?? 'unknown'));
                                }
                            } else {
                                \MemberpressAiAssistant\Utilities\LoggingUtility::debug('No history found in loaded context from file');
                            }
                            
                            return true;
                        }
                    }
                }
            }
            
            // Fall back to legacy file format
            $storageDir = WP_CONTENT_DIR . '/mpai-context';
            $filePath = $storageDir . '/mpai_context_' . $storageKey . '.json';
            
            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Falling back to legacy file format: ' . $filePath);
            
            if (file_exists($filePath)) {
                $contextJson = file_get_contents($filePath);
                if ($contextJson !== false) {
                    $contextData = json_decode($contextJson, true);
                    if (is_array($contextData)) {
                        $this->globalContext = $contextData['global'] ?? [];
                        $this->conversationContext = $contextData['conversation'] ?? [];
                        $this->requestContext = $contextData['request'] ?? [];
                        $this->entityReferences = $contextData['entities'] ?? [];
                        
                        \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Context loaded from legacy file: ' . $filePath);
                        
                        // Log conversation context details
                        if ($conversationId && isset($this->conversationContext[$conversationId])) {
                            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Loaded conversation context for ID: ' . $conversationId . ' from legacy file');
                            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Conversation context keys: ' .
                                      implode(', ', array_keys($this->conversationContext[$conversationId])));
                            
                            if (isset($this->conversationContext[$conversationId]['history'])) {
                                \MemberpressAiAssistant\Utilities\LoggingUtility::debug('History found in loaded context from legacy file');
                                \MemberpressAiAssistant\Utilities\LoggingUtility::debug('History item count: ' .
                                          count($this->conversationContext[$conversationId]['history']['value']));
                            } else {
                                \MemberpressAiAssistant\Utilities\LoggingUtility::debug('No history found in loaded context from legacy file');
                            }
                        } else if ($conversationId) {
                            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('No conversation context found for ID: ' . $conversationId . ' after loading from legacy file');
                        }
                        
                        return true;
                    }
                }
            }
        } catch (\Exception $e) {
            \MemberpressAiAssistant\Utilities\LoggingUtility::error('Error loading context: ' . $e->getMessage());
        }
        
        \MemberpressAiAssistant\Utilities\LoggingUtility::debug('No context found for key: ' . $storageKey);
        
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
        \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Clearing conversation context for ID: ' . $conversationId);
        
        // Delete the transient if it exists
        if (function_exists('delete_transient')) {
            $transientKey = 'mpai_' . $conversationId;
            delete_transient($transientKey);
            
            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Deleted transient with key: ' . $transientKey);
        }
        
        // Delete the file if it exists
        $storageDir = WP_CONTENT_DIR . '/mpai-context';
        $convFilePath = $storageDir . '/mpai_' . $conversationId . '.json';
        if (file_exists($convFilePath)) {
            unlink($convFilePath);
            
            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Deleted conversation file: ' . $convFilePath);
        }
        
        // Remove from memory
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
            
            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Removed conversation from memory');
            
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
