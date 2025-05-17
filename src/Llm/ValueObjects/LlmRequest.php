<?php
/**
 * LLM Request Value Object
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Llm\ValueObjects;

/**
 * Immutable value object representing a request to an LLM provider
 */
class LlmRequest {
    /**
     * The messages to send to the LLM
     *
     * @var array
     */
    private $messages;

    /**
     * The tools to make available to the LLM
     *
     * @var array
     */
    private $tools;

    /**
     * Additional options for the request
     *
     * @var array
     */
    private $options;

    /**
     * Constructor
     *
     * @param array $messages The messages to send to the LLM
     * @param array $tools    The tools to make available to the LLM
     * @param array $options  Additional options for the request
     */
    public function __construct(array $messages, array $tools = [], array $options = []) {
        $this->messages = $messages;
        $this->tools = $tools;
        $this->options = $options;
    }

    /**
     * Get the messages
     *
     * @return array The messages
     */
    public function getMessages(): array {
        return $this->messages;
    }

    /**
     * Get the tools
     *
     * @return array The tools
     */
    public function getTools(): array {
        return $this->tools;
    }

    /**
     * Get the options
     *
     * @return array The options
     */
    public function getOptions(): array {
        return $this->options;
    }

    /**
     * Get a specific option
     *
     * @param string $key     The option key
     * @param mixed  $default The default value if the option doesn't exist
     * @return mixed The option value or default
     */
    public function getOption(string $key, $default = null) {
        return $this->options[$key] ?? $default;
    }

    /**
     * Create a new instance with an additional option
     *
     * @param string $key   The option key
     * @param mixed  $value The option value
     * @return self A new instance with the option added
     */
    public function withOption(string $key, $value): self {
        $options = $this->options;
        $options[$key] = $value;
        return new self($this->messages, $this->tools, $options);
    }

    /**
     * Create a new instance with additional options
     *
     * @param array $options The options to add
     * @return self A new instance with the options added
     */
    public function withOptions(array $options): self {
        return new self($this->messages, $this->tools, array_merge($this->options, $options));
    }

    /**
     * Create a new instance with different tools
     *
     * @param array $tools The tools to use
     * @return self A new instance with the tools
     */
    public function withTools(array $tools): self {
        return new self($this->messages, $tools, $this->options);
    }

    /**
     * Create a new instance with an additional message
     *
     * @param array $message The message to add
     * @return self A new instance with the message added
     */
    public function withMessage(array $message): self {
        $messages = $this->messages;
        $messages[] = $message;
        return new self($messages, $this->tools, $this->options);
    }
}