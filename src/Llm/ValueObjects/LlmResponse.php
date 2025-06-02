<?php
/**
 * LLM Response Value Object
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Llm\ValueObjects;

/**
 * Immutable value object representing a response from an LLM provider
 */
class LlmResponse {
    /**
     * The content of the response
     *
     * @var string|null
     */
    private $content;

    /**
     * The tool calls in the response
     *
     * @var array
     */
    private $toolCalls;

    /**
     * The provider that generated the response
     *
     * @var string
     */
    private $provider;

    /**
     * The model that generated the response
     *
     * @var string
     */
    private $model;

    /**
     * Usage information for the response
     *
     * @var array
     */
    private $usage;

    /**
     * Raw response data
     *
     * @var mixed
     */
    private $rawResponse;

    /**
     * Constructor
     *
     * @param string|null $content    The content of the response
     * @param array       $toolCalls  The tool calls in the response
     * @param string      $provider   The provider that generated the response
     * @param string      $model      The model that generated the response
     * @param array       $usage      Usage information for the response
     * @param mixed       $rawResponse Raw response data
     */
    public function __construct(
        ?string $content,
        array $toolCalls = [],
        string $provider = '',
        string $model = '',
        array $usage = [],
        $rawResponse = null
    ) {
        $this->content = $content;
        $this->toolCalls = $toolCalls;
        $this->provider = $provider;
        $this->model = $model;
        $this->usage = $usage;
        $this->rawResponse = $rawResponse;
    }

    /**
     * Get the content
     *
     * @return string|null The content
     */
    public function getContent(): ?string {
        return $this->content;
    }

    /**
     * Check if the response has tool calls
     *
     * @return bool True if the response has tool calls
     */
    public function hasToolCalls(): bool {
        return !empty($this->toolCalls);
    }

    /**
     * Get the tool calls
     *
     * @return array The tool calls
     */
    public function getToolCalls(): array {
        return $this->toolCalls;
    }

    /**
     * Get the provider
     *
     * @return string The provider
     */
    public function getProvider(): string {
        return $this->provider;
    }

    /**
     * Get the model
     *
     * @return string The model
     */
    public function getModel(): string {
        return $this->model;
    }

    /**
     * Get the usage information
     *
     * @return array The usage information
     */
    public function getUsage(): array {
        return $this->usage;
    }

    /**
     * Get the raw response
     *
     * @return mixed The raw response
     */
    public function getRawResponse() {
        return $this->rawResponse;
    }

    /**
     * Create a response from an error
     *
     * @param \Exception $error    The error
     * @param string     $provider The provider
     * @param string     $model    The model
     * @return self A new response instance
     */
    public static function fromError(\Exception $error, string $provider = '', string $model = ''): self {
        return new self(
            "Error: {$error->getMessage()}",
            [],
            $provider,
            $model,
            [],
            ['error' => $error->getMessage(), 'code' => $error->getCode()]
        );
    }

    /**
     * Check if the response is an error
     *
     * @return bool True if the response is an error
     */
    public function isError(): bool {
        return is_array($this->rawResponse) && isset($this->rawResponse['error']);
    }

    /**
     * Get the error message if this is an error response
     *
     * @return string|null The error message or null if not an error
     */
    public function getErrorMessage(): ?string {
        if ($this->isError()) {
            return $this->rawResponse['error'];
        }
        return null;
    }
}