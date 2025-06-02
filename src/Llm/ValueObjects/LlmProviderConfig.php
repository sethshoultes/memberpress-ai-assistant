<?php
/**
 * LLM Provider Configuration Value Object
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Llm\ValueObjects;

/**
 * Configuration for an LLM provider
 */
class LlmProviderConfig {
    /**
     * The provider name
     *
     * @var string
     */
    private $name;

    /**
     * The default model to use
     *
     * @var string
     */
    private $defaultModel;

    /**
     * Available models for this provider
     *
     * @var array
     */
    private $availableModels;

    /**
     * Default temperature setting
     *
     * @var float
     */
    private $defaultTemperature;

    /**
     * Default max tokens setting
     *
     * @var int|null
     */
    private $defaultMaxTokens;

    /**
     * Additional configuration options
     *
     * @var array
     */
    private $options;

    /**
     * Constructor
     *
     * @param string   $name               The provider name
     * @param string   $defaultModel       The default model to use
     * @param array    $availableModels    Available models for this provider
     * @param float    $defaultTemperature Default temperature setting
     * @param int|null $defaultMaxTokens   Default max tokens setting
     * @param array    $options            Additional configuration options
     */
    public function __construct(
        string $name,
        string $defaultModel,
        array $availableModels = [],
        float $defaultTemperature = 0.7,
        ?int $defaultMaxTokens = null,
        array $options = []
    ) {
        $this->name = $name;
        $this->defaultModel = $defaultModel;
        $this->availableModels = $availableModels;
        $this->defaultTemperature = $defaultTemperature;
        $this->defaultMaxTokens = $defaultMaxTokens;
        $this->options = $options;
    }

    /**
     * Get the provider name
     *
     * @return string The provider name
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * Get the default model
     *
     * @return string The default model
     */
    public function getDefaultModel(): string {
        return $this->defaultModel;
    }

    /**
     * Create a new instance with a different default model
     *
     * @param string $model The new default model
     * @return self A new instance with the updated default model
     */
    public function withDefaultModel(string $model): self {
        return new self(
            $this->name,
            $model,
            $this->availableModels,
            $this->defaultTemperature,
            $this->defaultMaxTokens,
            $this->options
        );
    }

    /**
     * Get the available models
     *
     * @return array The available models
     */
    public function getAvailableModels(): array {
        return $this->availableModels;
    }

    /**
     * Check if a model is available
     *
     * @param string $model The model to check
     * @return bool True if the model is available
     */
    public function isModelAvailable(string $model): bool {
        return in_array($model, $this->availableModels);
    }

    /**
     * Get the default temperature
     *
     * @return float The default temperature
     */
    public function getDefaultTemperature(): float {
        return $this->defaultTemperature;
    }

    /**
     * Get the default max tokens
     *
     * @return int|null The default max tokens
     */
    public function getDefaultMaxTokens(): ?int {
        return $this->defaultMaxTokens;
    }

    /**
     * Get an option value
     *
     * @param string $key     The option key
     * @param mixed  $default The default value if the option doesn't exist
     * @return mixed The option value or default
     */
    public function getOption(string $key, $default = null) {
        return $this->options[$key] ?? $default;
    }

    /**
     * Get all options
     *
     * @return array All options
     */
    public function getOptions(): array {
        return $this->options;
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
        
        return new self(
            $this->name,
            $this->defaultModel,
            $this->availableModels,
            $this->defaultTemperature,
            $this->defaultMaxTokens,
            $options
        );
    }

    /**
     * Create a new instance with additional options
     *
     * @param array $options The options to add
     * @return self A new instance with the options added
     */
    public function withOptions(array $options): self {
        return new self(
            $this->name,
            $this->defaultModel,
            $this->availableModels,
            $this->defaultTemperature,
            $this->defaultMaxTokens,
            array_merge($this->options, $options)
        );
    }
}