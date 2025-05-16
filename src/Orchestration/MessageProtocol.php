<?php
/**
 * MessageProtocol Class
 *
 * Defines the standardized message format for communication between agents in the
 * MemberPress AI Assistant system. This class provides methods for creating,
 * validating, parsing, and serializing/deserializing messages.
 *
 * @package MemberpressAiAssistant\Orchestration
 */

namespace MemberpressAiAssistant\Orchestration;

/**
 * Class MessageProtocol
 *
 * Defines the standardized message format for inter-agent communication.
 */
class MessageProtocol {
    /**
     * Message types constants
     */
    const TYPE_REQUEST = 'request';
    const TYPE_RESPONSE = 'response';
    const TYPE_DELEGATION = 'delegation';
    const TYPE_NOTIFICATION = 'notification';
    const TYPE_ERROR = 'error';
    const TYPE_BROADCAST = 'broadcast';

    /**
     * Recipient special values
     */
    const RECIPIENT_BROADCAST = 'broadcast';

    /**
     * @var string Unique identifier for the message
     */
    private $id;

    /**
     * @var string Type of message (request, response, delegation, etc.)
     */
    private $type;

    /**
     * @var string ID of the agent sending the message
     */
    private $sender;

    /**
     * @var string ID of the agent receiving the message (or 'broadcast')
     */
    private $recipient;

    /**
     * @var int Unix timestamp when the message was created
     */
    private $timestamp;

    /**
     * @var mixed The actual message payload/content
     */
    private $content;

    /**
     * @var array Additional metadata for tracking context, state, etc.
     */
    private $metadata;

    /**
     * @var array References to link related messages (e.g., a response to a request)
     */
    private $references;

    /**
     * Constructor
     *
     * @param string $type      Message type
     * @param string $sender    Sender agent ID
     * @param string $recipient Recipient agent ID or 'broadcast'
     * @param mixed  $content   Message payload
     * @param array  $metadata  Additional metadata (optional)
     * @param array  $references References to other messages (optional)
     */
    public function __construct(
        string $type,
        string $sender,
        string $recipient,
        $content,
        array $metadata = [],
        array $references = []
    ) {
        $this->id = $this->generateId();
        $this->type = $type;
        $this->sender = $sender;
        $this->recipient = $recipient;
        $this->timestamp = time();
        $this->content = $content;
        $this->metadata = $metadata;
        $this->references = $references;
    }

    /**
     * Generate a unique message ID
     *
     * @return string Unique message ID
     */
    private function generateId(): string {
        return uniqid('msg_', true);
    }

    /**
     * Create a request message
     *
     * @param string $sender    Sender agent ID
     * @param string $recipient Recipient agent ID
     * @param mixed  $content   Request content
     * @param array  $metadata  Additional metadata
     * @return MessageProtocol New message instance
     */
    public static function createRequest(
        string $sender,
        string $recipient,
        $content,
        array $metadata = []
    ): MessageProtocol {
        return new self(
            self::TYPE_REQUEST,
            $sender,
            $recipient,
            $content,
            $metadata
        );
    }

    /**
     * Create a response message
     *
     * @param string $sender     Sender agent ID
     * @param string $recipient  Recipient agent ID
     * @param mixed  $content    Response content
     * @param string $requestId  ID of the request being responded to
     * @param array  $metadata   Additional metadata
     * @return MessageProtocol New message instance
     */
    public static function createResponse(
        string $sender,
        string $recipient,
        $content,
        string $requestId,
        array $metadata = []
    ): MessageProtocol {
        return new self(
            self::TYPE_RESPONSE,
            $sender,
            $recipient,
            $content,
            $metadata,
            ['request_id' => $requestId]
        );
    }

    /**
     * Create a delegation message
     *
     * @param string $sender    Sender agent ID
     * @param string $recipient Recipient agent ID
     * @param mixed  $content   Delegation content/instructions
     * @param array  $metadata  Additional metadata
     * @return MessageProtocol New message instance
     */
    public static function createDelegation(
        string $sender,
        string $recipient,
        $content,
        array $metadata = []
    ): MessageProtocol {
        return new self(
            self::TYPE_DELEGATION,
            $sender,
            $recipient,
            $content,
            $metadata
        );
    }

    /**
     * Create a broadcast message to all agents
     *
     * @param string $sender   Sender agent ID
     * @param mixed  $content  Broadcast content
     * @param array  $metadata Additional metadata
     * @return MessageProtocol New message instance
     */
    public static function createBroadcast(
        string $sender,
        $content,
        array $metadata = []
    ): MessageProtocol {
        return new self(
            self::TYPE_BROADCAST,
            $sender,
            self::RECIPIENT_BROADCAST,
            $content,
            $metadata
        );
    }

    /**
     * Create an error message
     *
     * @param string $sender    Sender agent ID
     * @param string $recipient Recipient agent ID
     * @param string $error     Error message
     * @param array  $metadata  Additional metadata
     * @param array  $references References to related messages
     * @return MessageProtocol New message instance
     */
    public static function createError(
        string $sender,
        string $recipient,
        string $error,
        array $metadata = [],
        array $references = []
    ): MessageProtocol {
        return new self(
            self::TYPE_ERROR,
            $sender,
            $recipient,
            $error,
            $metadata,
            $references
        );
    }

    /**
     * Validate if the message follows the protocol
     *
     * @return bool True if valid, false otherwise
     */
    public function validate(): bool {
        // Check required fields
        if (empty($this->id) || 
            empty($this->type) || 
            empty($this->sender) || 
            empty($this->recipient) || 
            empty($this->timestamp)) {
            return false;
        }

        // Validate message type
        $validTypes = [
            self::TYPE_REQUEST,
            self::TYPE_RESPONSE,
            self::TYPE_DELEGATION,
            self::TYPE_NOTIFICATION,
            self::TYPE_ERROR,
            self::TYPE_BROADCAST
        ];

        if (!in_array($this->type, $validTypes)) {
            return false;
        }

        // Validate references for response messages
        if ($this->type === self::TYPE_RESPONSE && 
            (!isset($this->references['request_id']) || empty($this->references['request_id']))) {
            return false;
        }

        return true;
    }

    /**
     * Convert the message to an array
     *
     * @return array Message as an associative array
     */
    public function toArray(): array {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'sender' => $this->sender,
            'recipient' => $this->recipient,
            'timestamp' => $this->timestamp,
            'content' => $this->content,
            'metadata' => $this->metadata,
            'references' => $this->references,
        ];
    }

    /**
     * Serialize the message to JSON
     *
     * @return string JSON representation of the message
     */
    public function toJson(): string {
        return json_encode($this->toArray());
    }

    /**
     * Create a message from an array
     *
     * @param array $data Message data as an associative array
     * @return MessageProtocol|null New message instance or null if invalid
     */
    public static function fromArray(array $data): ?MessageProtocol {
        // Check required fields
        $requiredFields = ['type', 'sender', 'recipient', 'content'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                return null;
            }
        }

        $message = new self(
            $data['type'],
            $data['sender'],
            $data['recipient'],
            $data['content'],
            $data['metadata'] ?? [],
            $data['references'] ?? []
        );

        // Override generated ID and timestamp if provided
        if (isset($data['id'])) {
            $message->id = $data['id'];
        }
        
        if (isset($data['timestamp'])) {
            $message->timestamp = $data['timestamp'];
        }

        return $message->validate() ? $message : null;
    }

    /**
     * Create a message from JSON
     *
     * @param string $json JSON representation of the message
     * @return MessageProtocol|null New message instance or null if invalid
     */
    public static function fromJson(string $json): ?MessageProtocol {
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            return null;
        }

        return self::fromArray($data);
    }

    /**
     * Get message ID
     *
     * @return string Message ID
     */
    public function getId(): string {
        return $this->id;
    }

    /**
     * Get message type
     *
     * @return string Message type
     */
    public function getType(): string {
        return $this->type;
    }

    /**
     * Get sender agent ID
     *
     * @return string Sender agent ID
     */
    public function getSender(): string {
        return $this->sender;
    }

    /**
     * Get recipient agent ID
     *
     * @return string Recipient agent ID
     */
    public function getRecipient(): string {
        return $this->recipient;
    }

    /**
     * Get message timestamp
     *
     * @return int Message timestamp
     */
    public function getTimestamp(): int {
        return $this->timestamp;
    }

    /**
     * Get message content
     *
     * @return mixed Message content
     */
    public function getContent() {
        return $this->content;
    }

    /**
     * Get message metadata
     *
     * @return array Message metadata
     */
    public function getMetadata(): array {
        return $this->metadata;
    }

    /**
     * Get specific metadata value
     *
     * @param string $key Metadata key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed Metadata value or default
     */
    public function getMetadataValue(string $key, $default = null) {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Get message references
     *
     * @return array Message references
     */
    public function getReferences(): array {
        return $this->references;
    }

    /**
     * Check if this message is a response to another message
     *
     * @param string $requestId Request message ID
     * @return bool True if this is a response to the specified request
     */
    public function isResponseTo(string $requestId): bool {
        return $this->type === self::TYPE_RESPONSE && 
               isset($this->references['request_id']) && 
               $this->references['request_id'] === $requestId;
    }

    /**
     * Check if this message is a broadcast
     *
     * @return bool True if this is a broadcast message
     */
    public function isBroadcast(): bool {
        return $this->recipient === self::RECIPIENT_BROADCAST;
    }

    /**
     * Add or update metadata
     *
     * @param string $key Metadata key
     * @param mixed $value Metadata value
     * @return MessageProtocol This instance for method chaining
     */
    public function setMetadata(string $key, $value): MessageProtocol {
        $this->metadata[$key] = $value;
        return $this;
    }

    /**
     * Add a reference to another message
     *
     * @param string $key Reference key
     * @param string $messageId Referenced message ID
     * @return MessageProtocol This instance for method chaining
     */
    public function addReference(string $key, string $messageId): MessageProtocol {
        $this->references[$key] = $messageId;
        return $this;
    }
}