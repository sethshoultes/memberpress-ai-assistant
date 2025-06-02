<?php
/**
 * LLM Client Interface
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Llm\Interfaces;

use MemberpressAiAssistant\Llm\ValueObjects\LlmRequest;
use MemberpressAiAssistant\Llm\ValueObjects\LlmResponse;

/**
 * Interface for LLM API clients
 */
interface LlmClientInterface {
    /**
     * Send a message to the LLM provider
     *
     * @param LlmRequest $request The request to send
     * @return LlmResponse The response from the provider
     * @throws \Exception If the request fails
     */
    public function sendMessage(LlmRequest $request): LlmResponse;

    /**
     * Test the connection to the LLM provider
     *
     * @param string|null $model Optional model to test
     * @return bool True if the connection is successful
     */
    public function testConnection(?string $model = null): bool;

    /**
     * Get the provider name
     *
     * @return string The provider name
     */
    public function getProviderName(): string;

    /**
     * Get the available models for this provider
     *
     * @return array List of available models
     */
    public function getAvailableModels(): array;
}