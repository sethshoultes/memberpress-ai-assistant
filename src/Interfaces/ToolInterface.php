<?php
/**
 * Tool Interface
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Interfaces;

/**
 * Interface for all tools in the system
 */
interface ToolInterface {
    /**
     * Get the tool name
     *
     * @return string
     */
    public function getToolName(): string;

    /**
     * Get the tool description
     *
     * @return string
     */
    public function getToolDescription(): string;

    /**
     * Get the tool definition in a format compatible with AI models
     *
     * @return array
     */
    public function getToolDefinition(): array;

    /**
     * Execute the tool with the given parameters
     *
     * @param array $parameters The parameters for the tool execution
     * @return array The result of the tool execution
     */
    public function execute(array $parameters): array;
}