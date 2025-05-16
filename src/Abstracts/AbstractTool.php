<?php
/**
 * Abstract Tool
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Abstracts;

use MemberpressAiAssistant\Interfaces\ToolInterface;
use MemberpressAiAssistant\Validation\ValidationResult;
use MemberpressAiAssistant\Validation\ParameterValidator;
use MemberpressAiAssistant\Services\CacheService;

/**
 * Abstract base class for all tools
 */
abstract class AbstractTool implements ToolInterface {
    /**
     * Tool name
     *
     * @var string
     */
    protected $name;

    /**
     * Tool description
     *
     * @var string
     */
    protected $description;

    /**
     * Logger instance
     *
     * @var mixed
     */
    protected $logger;

    /**
     * Parameter validator instance
     *
     * @var ParameterValidator|null
     */
    protected $validator;

    /**
     * Cache service instance
     *
     * @var CacheService|null
     */
    protected $cacheService;

    /**
     * Validation result cache
     *
     * @var array
     */
    protected $validationCache = [];

    /**
     * Whether to use validation caching
     *
     * @var bool
     */
    protected $useValidationCache = true;

    /**
     * Constructor
     *
     * @param string $name Tool name
     * @param string $description Tool description
     * @param mixed $logger Logger instance
     * @param ParameterValidator|null $validator Parameter validator instance
     * @param CacheService|null $cacheService Cache service instance
     */
    public function __construct(
        string $name,
        string $description,
        $logger = null,
        ?ParameterValidator $validator = null,
        ?CacheService $cacheService = null
    ) {
        $this->name = $name;
        $this->description = $description;
        $this->logger = $logger;
        $this->validator = $validator;
        $this->cacheService = $cacheService;
    }

    /**
     * {@inheritdoc}
     */
    public function getToolName(): string {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getToolDescription(): string {
        return $this->description;
    }

    /**
     * {@inheritdoc}
     */
    public function getToolDefinition(): array {
        return [
            'name' => $this->getToolName(),
            'description' => $this->getToolDescription(),
            'parameters' => $this->getParameters(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute(array $parameters): array {
        // Validate parameters early to fail fast
        $validationResult = $this->validateParametersWithSchema($parameters);
        
        if (!$validationResult->isValid()) {
            return [
                'success' => false,
                'error' => 'Parameter validation failed: ' . $validationResult->getErrorsAsString(),
                'validation_errors' => $validationResult->getErrors(),
            ];
        }

        // Execute the tool implementation
        try {
            $result = $this->executeInternal($parameters);
            $this->logExecution($parameters, $result);
            return $result;
        } catch (\Exception $e) {
            $error = [
                'success' => false,
                'error' => 'Tool execution failed: ' . $e->getMessage(),
                'exception' => get_class($e),
            ];
            
            $this->logExecution($parameters, $error);
            return $error;
        }
    }

    /**
     * Execute the tool implementation
     *
     * @param array $parameters The validated parameters
     * @return array The result of the tool execution
     */
    abstract protected function executeInternal(array $parameters): array;

    /**
     * Get the parameters for this tool
     *
     * @return array
     */
    abstract protected function getParameters(): array;

    /**
     * Get the parameter schema for validation
     *
     * This method should be overridden by specific tools to define their parameter schema.
     * The schema follows JSON Schema format with additional features provided by ParameterValidator.
     *
     * @return array The parameter schema
     */
    protected function getParameterSchema(): array {
        // Base implementation - should be overridden by specific tools
        return [
            'type' => 'object',
            'properties' => [],
            'required' => [],
        ];
    }

    /**
     * Validate parameters against the schema
     *
     * @param array $parameters The parameters to validate
     * @return ValidationResult The validation result
     */
    protected function validateParametersWithSchema(array $parameters): ValidationResult {
        // If no validator is available, fall back to legacy validation
        if (!$this->validator) {
            return ValidationResult::fromLegacyResult($this->validateParameters($parameters));
        }

        // Check if we can use cached validation result
        if ($this->useValidationCache) {
            $cacheKey = $this->generateValidationCacheKey($parameters);
            
            if (isset($this->validationCache[$cacheKey])) {
                return $this->validationCache[$cacheKey];
            }
            
            // If we have a cache service, try to get from there
            if ($this->cacheService) {
                $cacheKey = 'tool_validation_' . $this->getToolName() . '_' . md5(json_encode($parameters));
                $cachedResult = $this->cacheService->get($cacheKey);
                
                if ($cachedResult instanceof ValidationResult) {
                    return $cachedResult;
                }
            }
        }

        // Get the parameter schema
        $schema = $this->getParameterSchema();
        
        // Validate parameters against schema
        $result = $this->validator->validate($parameters, $schema);
        
        // Cache the result
        if ($this->useValidationCache) {
            $cacheKey = $this->generateValidationCacheKey($parameters);
            $this->validationCache[$cacheKey] = $result;
            
            // If we have a cache service, store there too
            if ($this->cacheService) {
                $cacheKey = 'tool_validation_' . $this->getToolName() . '_' . md5(json_encode($parameters));
                $this->cacheService->set($cacheKey, $result);
            }
        }
        
        return $result;
    }

    /**
     * Legacy parameter validation method
     *
     * This method is kept for backward compatibility and will be called
     * if no validator is available.
     *
     * @param array $parameters The parameters to validate
     * @return bool|array True if valid, array of errors if invalid
     */
    protected function validateParameters(array $parameters) {
        // Base implementation - should be overridden by specific tools
        return true;
    }

    /**
     * Generate a cache key for validation results
     *
     * @param array $parameters The parameters to validate
     * @return string The cache key
     */
    protected function generateValidationCacheKey(array $parameters): string {
        return md5($this->getToolName() . '_' . json_encode($parameters));
    }

    /**
     * Enable or disable validation caching
     *
     * @param bool $useCache Whether to use validation caching
     * @return self
     */
    public function setUseValidationCache(bool $useCache): self {
        $this->useValidationCache = $useCache;
        return $this;
    }

    /**
     * Clear the validation cache
     *
     * @return self
     */
    public function clearValidationCache(): self {
        $this->validationCache = [];
        
        // If we have a cache service, clear from there too
        if ($this->cacheService) {
            $this->cacheService->deletePattern('tool_validation_' . $this->getToolName());
        }
        
        return $this;
    }

    /**
     * Normalize parameters based on schema
     *
     * This method uses the parameter validator to normalize parameters
     * according to their schema definitions, providing type coercion.
     *
     * @param array $parameters The parameters to normalize
     * @return array The normalized parameters
     */
    protected function normalizeParameters(array $parameters): array {
        if (!$this->validator) {
            return $parameters;
        }
        
        return $this->validator->normalizeParameters($parameters, $this->getParameterSchema());
    }

    /**
     * Log tool execution
     *
     * @param array $parameters The parameters used
     * @param array $result The result of the execution
     * @return void
     */
    protected function logExecution(array $parameters, array $result): void {
        if ($this->logger) {
            $this->logger->info('Executed tool ' . $this->getToolName(), [
                'parameters' => $parameters,
                'result' => $result,
            ]);
        }
    }
}