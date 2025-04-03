<?php
/**
 * MPAI Agent Message Class
 *
 * @package MemberPress AI Assistant
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Agent Message for inter-agent communication
 */
class MPAI_Agent_Message {
	/**
	 * Sender agent ID
	 * 
	 * @var string
	 */
	private $sender;
	
	/**
	 * Receiver agent ID
	 * 
	 * @var string
	 */
	private $receiver;
	
	/**
	 * Message type
	 * 
	 * @var string
	 */
	private $message_type;
	
	/**
	 * Message content
	 * 
	 * @var string
	 */
	private $content;
	
	/**
	 * Additional metadata
	 * 
	 * @var array
	 */
	private $metadata;
	
	/**
	 * Timestamp
	 * 
	 * @var string
	 */
	private $timestamp;
	
	/**
	 * Constructor
	 *
	 * @param string $sender Sender agent ID
	 * @param string $receiver Receiver agent ID
	 * @param string $message_type Message type (e.g. 'handoff', 'request', 'response')
	 * @param string $content Message content
	 * @param array $metadata Additional metadata
	 */
	public function __construct($sender, $receiver, $message_type, $content, $metadata = []) {
		$this->sender = $sender;
		$this->receiver = $receiver;
		$this->message_type = $message_type;
		$this->content = $content;
		$this->metadata = $metadata;
		$this->timestamp = current_time('mysql');
	}
	
	/**
	 * Get sender
	 *
	 * @return string Sender agent ID
	 */
	public function get_sender() {
		return $this->sender;
	}
	
	/**
	 * Get receiver
	 *
	 * @return string Receiver agent ID
	 */
	public function get_receiver() {
		return $this->receiver;
	}
	
	/**
	 * Get message type
	 *
	 * @return string Message type
	 */
	public function get_message_type() {
		return $this->message_type;
	}
	
	/**
	 * Get content
	 *
	 * @return string Message content
	 */
	public function get_content() {
		return $this->content;
	}
	
	/**
	 * Get metadata
	 *
	 * @return array Message metadata
	 */
	public function get_metadata() {
		return $this->metadata;
	}
	
	/**
	 * Get timestamp
	 *
	 * @return string Message timestamp
	 */
	public function get_timestamp() {
		return $this->timestamp;
	}
	
	/**
	 * Set sender
	 *
	 * @param string $sender Sender agent ID
	 */
	public function set_sender($sender) {
		$this->sender = $sender;
	}
	
	/**
	 * Set receiver
	 *
	 * @param string $receiver Receiver agent ID
	 */
	public function set_receiver($receiver) {
		$this->receiver = $receiver;
	}
	
	/**
	 * Set message type
	 *
	 * @param string $message_type Message type
	 */
	public function set_message_type($message_type) {
		$this->message_type = $message_type;
	}
	
	/**
	 * Set content
	 *
	 * @param string $content Message content
	 */
	public function set_content($content) {
		$this->content = $content;
	}
	
	/**
	 * Set metadata
	 *
	 * @param array $metadata Message metadata
	 */
	public function set_metadata($metadata) {
		$this->metadata = $metadata;
	}
	
	/**
	 * Set timestamp
	 *
	 * @param string $timestamp Message timestamp
	 */
	public function set_timestamp($timestamp) {
		$this->timestamp = $timestamp;
	}
	
	/**
	 * Add metadata item
	 *
	 * @param string $key Metadata key
	 * @param mixed $value Metadata value
	 */
	public function add_metadata($key, $value) {
		$this->metadata[$key] = $value;
	}
	
	/**
	 * Convert to array
	 *
	 * @return array Message as array
	 */
	public function to_array() {
		return [
			'sender' => $this->sender,
			'receiver' => $this->receiver,
			'message_type' => $this->message_type,
			'content' => $this->content,
			'metadata' => $this->metadata,
			'timestamp' => $this->timestamp
		];
	}
	
	/**
	 * Create from array
	 *
	 * @param array $data Message data
	 * @return MPAI_Agent_Message New message instance
	 */
	public static function from_array($data) {
		$message = new self(
			$data['sender'],
			$data['receiver'],
			$data['message_type'],
			$data['content'],
			isset($data['metadata']) ? $data['metadata'] : []
		);
		
		if (isset($data['timestamp'])) {
			$message->set_timestamp($data['timestamp']);
		}
		
		return $message;
	}
}