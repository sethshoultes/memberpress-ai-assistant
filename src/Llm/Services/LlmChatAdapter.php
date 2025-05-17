<?php
/**
 * LLM Chat Adapter
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Llm\Services;

use MemberpressAiAssistant\Llm\ValueObjects\LlmRequest;
use MemberpressAiAssistant\Llm\ValueObjects\LlmResponse;

/**
 * Adapter for integrating LLM services with the chat interface
 */
class LlmChatAdapter {
    /**
     * LLM Orchestrator
     *
     * @var LlmOrchestrator
     */
    private $orchestrator;

    /**
     * Constructor
     *
     * @param LlmOrchestrator $orchestrator The LLM orchestrator
     */
    public function __construct(LlmOrchestrator $orchestrator) {
        $this->orchestrator = $orchestrator;
    }

    /**
     * Process a chat request
     *
     * @param string $message The user message
     * @param string|null $conversationId The conversation ID
     * @return array The response data
     */
    public function processRequest(string $message, ?string $conversationId = null): array {
        // Create a new LlmRequest
        $request = new LlmRequest(
            [
                ['role' => 'user', 'content' => $message]
            ],
            [], // No tools for now
            [
                'conversation_id' => $conversationId
            ]
        );

        try {
            // Process the request with the orchestrator
            $response = $this->orchestrator->processRequest($request);

            // Format the response for the chat interface
            return [
                'status' => 'success',
                'message' => $response->getContent(),
                'conversation_id' => $conversationId ?? $this->generateConversationId(),
                'timestamp' => time(),
            ];
        } catch (\Exception $e) {
            // Log the error
            if (function_exists('error_log')) {
                error_log('MPAI LLM Chat Error: ' . $e->getMessage());
            }

            // Return error response
            return [
                'status' => 'error',
                'message' => 'Error processing request: ' . $e->getMessage(),
                'conversation_id' => $conversationId ?? $this->generateConversationId(),
                'timestamp' => time(),
            ];
        }
    }

    /**
     * Generate a unique conversation ID
     *
     * @return string Unique conversation ID
     */
    private function generateConversationId(): string {
        return 'conv_' . uniqid('', true);
    }
}