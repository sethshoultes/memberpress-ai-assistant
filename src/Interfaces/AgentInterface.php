<?php
/**
 * Agent Interface
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Interfaces;

/**
 * Interface for all agents in the system
 */
interface AgentInterface {
    /**
     * Get the agent name
     *
     * @return string
     */
    public function getAgentName(): string;

    /**
     * Get the agent description
     *
     * @return string
     */
    public function getAgentDescription(): string;

    /**
     * Calculate a specialization score for this agent based on the request
     *
     * @param array $request The request data
     * @return float Score between 0-100 indicating how specialized this agent is for the request
     */
    public function getSpecializationScore(array $request): float;

    /**
     * Process a request with this agent
     *
     * @param array $request The request data
     * @param array $context The context data
     * @return array The response data
     */
    public function processRequest(array $request, array $context): array;

    /**
     * Get the system prompt for this agent
     *
     * @return string
     */
    public function getSystemPrompt(): string;

    /**
     * Get the capabilities of this agent
     *
     * @return array
     */
    public function getCapabilities(): array;
}