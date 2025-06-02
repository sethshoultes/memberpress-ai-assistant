<?php
/**
 * Parameter Validator
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Validation;

use MemberpressAiAssistant\Services\CacheService;

/**
 * Class ParameterValidator
 * 
 * Implements a parameter schema validation system with support for
 * parameter normalization and validation caching.
 * 
 * Supports JSON Schema validation features including:
 * - Type validation (string, number, integer, boolean, array, object, null)
 * - Required properties
 * - Minimum/maximum for numbers
 * - Min/max length for strings
 * - Pattern matching for strings
 * - Min/max items for arrays
 * - Enum values
 * - oneOf, anyOf, allOf schema combinations
 */
class ParameterValidator {
    /**
     * Cache service instance
     *
     * @var CacheService|null
     */
    protected $cacheService;

    /**
     * Cache TTL in seconds (5 minutes)
     *
     * @var int
     */
    protected $cacheTtl = 300;

    /**
     * Whether to use caching
     *
     * @var bool
     */
    protected $useCache = true;

    /**
     * Current validation path for error messages
     * 
     * @var array
     */
    protected $currentPath = [];

    /**
     * Constructor
     *
     * @param CacheService|null $cacheService Cache service instance
     */
    public function __construct(?CacheService $cacheService = null) {
        $this->cacheService = $cacheService;
    }

    /**
     * Set the cache TTL
     *
     * @param int $ttl TTL in seconds
     * @return self
     */
    public function setCacheTtl(int $ttl): self {
        $this->cacheTtl = max(0, $ttl);
        return $this;
    }

    /**
     * Enable or disable caching
     *
     * @param bool $useCache Whether to use caching
     * @return self
     */
    public function setUseCache(bool $useCache): self {
        $this->useCache = $useCache;
        return $this;
    }

    /**
     * Validate parameters against a schema
     *
     * @param array $parameters The parameters to validate
     * @param array $schema The schema to validate against
     * @return ValidationResult The validation result
     */
    public function validate(array $parameters, array $schema): ValidationResult {
        // Check if we can use cached validation result
        if ($this->useCache && $this->cacheService) {
            $cacheKey = $this->generateCacheKey($parameters, $schema);
            
            // Use the remember pattern for better cache integration
            return $this->cacheService->remember($cacheKey, function() use ($parameters, $schema) {
                // Normalize parameters based on schema
                $normalizedParameters = $this->normalizeParameters($parameters, $schema);
                
                // Validate parameters against schema
                return $this->validateAgainstSchema($normalizedParameters, $schema);
            }, $this->cacheTtl);
        }

        // If caching is disabled, proceed with validation directly
        $normalizedParameters = $this->normalizeParameters($parameters, $schema);
        return $this->validateAgainstSchema($normalizedParameters, $schema);
    }

    /**
     * Normalize parameters based on schema
     *
     * @param array $parameters The parameters to normalize
     * @param array $schema The schema to normalize against
     * @return array The normalized parameters
     */
    public function normalizeParameters(array $parameters, array $schema): array {
        $normalized = [];
        
        // If schema has properties, normalize each property
        if (isset($schema['properties']) && is_array($schema['properties'])) {
            foreach ($schema['properties'] as $name => $propertySchema) {
                if (array_key_exists($name, $parameters)) {
                    // Handle null values based on schema nullable property
                    if ($parameters[$name] === null && (!isset($propertySchema['nullable']) || $propertySchema['nullable'] !== true)) {
                        // Use default if available, otherwise keep as null
                        $normalized[$name] = isset($propertySchema['default']) ? $propertySchema['default'] : null;
                    } else {
                        $normalized[$name] = $this->normalizeValue($parameters[$name], $propertySchema);
                    }
                } elseif (isset($propertySchema['default'])) {
                    // Use default value if provided in schema
                    $normalized[$name] = $propertySchema['default'];
                }
            }
        }
        
        // Copy any parameters not in the schema as-is
        foreach ($parameters as $name => $value) {
            if (!isset($normalized[$name])) {
                $normalized[$name] = $value;
            }
        }
        
        return $normalized;
    }

    /**
     * Normalize a single value based on its schema
     *
     * @param mixed $value The value to normalize
     * @param array $schema The schema for this value
     * @return mixed The normalized value
     */
    protected function normalizeValue($value, array $schema) {
        $type = $schema['type'] ?? null;
        
        // Handle null values with improved null handling
        if ($value === null) {
            // If nullable is explicitly set to true, return null
            if (isset($schema['nullable']) && $schema['nullable'] === true) {
                return null;
            }
            
            // If there's a default, use it
            if (isset($schema['default'])) {
                return $schema['default'];
            }
            
            // Otherwise return null
            return null;
        }
        
        // Normalize based on type
        switch ($type) {
            case 'boolean':
                return $this->normalizeBoolean($value);
                
            case 'integer':
                return $this->normalizeInteger($value);
                
            case 'number':
                return $this->normalizeNumber($value);
                
            case 'string':
                return $this->normalizeString($value);
                
            case 'array':
                return $this->normalizeArray($value, $schema);
                
            case 'object':
                return $this->normalizeObject($value, $schema);
                
            default:
                // If no type specified or unknown type, return as-is
                return $value;
        }
    }

    /**
     * Normalize a value to boolean
     *
     * @param mixed $value The value to normalize
     * @return bool The normalized boolean value
     */
    protected function normalizeBoolean($value): bool {
        if (is_bool($value)) {
            return $value;
        }
        
        if (is_string($value)) {
            $lowercase = strtolower($value);
            if (in_array($lowercase, ['true', 'yes', 'y', '1', 'on'])) {
                return true;
            }
            if (in_array($lowercase, ['false', 'no', 'n', '0', 'off'])) {
                return false;
            }
        }
        
        return (bool) $value;
    }

    /**
     * Normalize a value to integer
     *
     * @param mixed $value The value to normalize
     * @return int The normalized integer value
     */
    protected function normalizeInteger($value): int {
        if (is_int($value)) {
            return $value;
        }
        
        if (is_string($value) && preg_match('/^-?\d+$/', $value)) {
            return (int) $value;
        }
        
        return (int) $value;
    }

    /**
     * Normalize a value to number (float)
     *
     * @param mixed $value The value to normalize
     * @return float The normalized number value
     */
    protected function normalizeNumber($value): float {
        if (is_float($value) || is_int($value)) {
            return (float) $value;
        }
        
        if (is_string($value) && is_numeric($value)) {
            return (float) $value;
        }
        
        return (float) $value;
    }

    /**
     * Normalize a value to string
     *
     * @param mixed $value The value to normalize
     * @return string The normalized string value
     */
    protected function normalizeString($value): string {
        if (is_string($value)) {
            return $value;
        }
        
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        
        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }
        
        return (string) $value;
    }

    /**
     * Normalize a value to array
     *
     * @param mixed $value The value to normalize
     * @param array $schema The schema for this array
     * @return array The normalized array value
     */
    protected function normalizeArray($value, array $schema): array {
        if (!is_array($value)) {
            // Convert non-array to single-item array
            $value = [$value];
        }
        
        // If schema has items definition, normalize each item
        if (isset($schema['items']) && is_array($schema['items'])) {
            $itemSchema = $schema['items'];
            foreach ($value as $key => $item) {
                // Improved null handling in arrays
                if ($item === null && (!isset($itemSchema['nullable']) || $itemSchema['nullable'] !== true)) {
                    // If item schema has a default, use it
                    if (isset($itemSchema['default'])) {
                        $value[$key] = $itemSchema['default'];
                    }
                    // Otherwise keep as null and let validation handle it
                } else {
                    $value[$key] = $this->normalizeValue($item, $itemSchema);
                }
            }
        }
        
        return $value;
    }

    /**
     * Normalize a value to object (associative array)
     *
     * @param mixed $value The value to normalize
     * @param array $schema The schema for this object
     * @return array The normalized object value as associative array
     */
    protected function normalizeObject($value, array $schema): array {
        if (is_string($value) && $decoded = json_decode($value, true)) {
            $value = $decoded;
        }
        
        if (!is_array($value)) {
            $value = [];
        }
        
        // If schema has properties, normalize each property
        if (isset($schema['properties']) && is_array($schema['properties'])) {
            return $this->normalizeParameters($value, $schema);
        }
        
        return $value;
    }

    /**
     * Validate parameters against a schema
     *
     * @param array $parameters The parameters to validate
     * @param array $schema The schema to validate against
     * @param string $path Current path for error messages
     * @return ValidationResult The validation result
     */
    protected function validateAgainstSchema(array $parameters, array $schema, string $path = ''): ValidationResult {
        $errors = [];
        $this->currentPath = empty($path) ? [] : explode('.', $path);
        
        // Check for oneOf, anyOf, allOf schema combinations
        if (isset($schema['oneOf']) && is_array($schema['oneOf'])) {
            $oneOfResult = $this->validateOneOf($parameters, $schema['oneOf']);
            if (!$oneOfResult->isValid()) {
                return $oneOfResult;
            }
        }
        
        if (isset($schema['anyOf']) && is_array($schema['anyOf'])) {
            $anyOfResult = $this->validateAnyOf($parameters, $schema['anyOf']);
            if (!$anyOfResult->isValid()) {
                return $anyOfResult;
            }
        }
        
        if (isset($schema['allOf']) && is_array($schema['allOf'])) {
            $allOfResult = $this->validateAllOf($parameters, $schema['allOf']);
            if (!$allOfResult->isValid()) {
                return $allOfResult;
            }
        }
        
        // Check required properties
        if (isset($schema['required']) && is_array($schema['required'])) {
            foreach ($schema['required'] as $requiredProperty) {
                if (!array_key_exists($requiredProperty, $parameters)) {
                    $propertyPath = $this->formatPath($requiredProperty);
                    $errors[] = "Required parameter '{$propertyPath}' is missing";
                    
                    // Early return for performance optimization if there's a missing required property
                    if (count($errors) >= 1) {
                        return ValidationResult::failure($errors);
                    }
                }
            }
        }
        
        // Validate each property against its schema
        if (isset($schema['properties']) && is_array($schema['properties'])) {
            foreach ($schema['properties'] as $name => $propertySchema) {
                if (array_key_exists($name, $parameters)) {
                    $this->pushPath($name);
                    $propertyErrors = $this->validateProperty($name, $parameters[$name], $propertySchema);
                    $this->popPath();
                    
                    $errors = array_merge($errors, $propertyErrors);
                    
                    // Early return for performance optimization if we have errors
                    if (count($errors) >= 5) {
                        return ValidationResult::failure($errors);
                    }
                }
            }
        }
        
        // Check for additional properties if not allowed
        if (isset($schema['additionalProperties']) && $schema['additionalProperties'] === false) {
            $allowedProperties = array_keys($schema['properties'] ?? []);
            $actualProperties = array_keys($parameters);
            $extraProperties = array_diff($actualProperties, $allowedProperties);
            
            if (!empty($extraProperties)) {
                $extraPropertiesList = implode(', ', $extraProperties);
                $errors[] = "Additional properties not allowed: {$extraPropertiesList}";
            }
        }
        
        return empty($errors) ? ValidationResult::success() : ValidationResult::failure($errors);
    }

    /**
     * Validate a single property against its schema
     *
     * @param string $name The property name
     * @param mixed $value The property value
     * @param array $schema The schema for this property
     * @return array Array of validation errors
     */
    protected function validateProperty(string $name, $value, array $schema): array {
        $errors = [];
        $type = $schema['type'] ?? null;
        $propertyPath = $this->formatPath();
        
        // Skip validation if value is null and nullable is true
        if ($value === null && isset($schema['nullable']) && $schema['nullable'] === true) {
            return $errors;
        }
        
        // Validate type
        if ($type !== null && !$this->validateType($value, $type)) {
            $errors[] = "Parameter '{$propertyPath}' must be of type {$type}";
            // Early return for type validation failure
            return $errors;
        }
        
        // Validate enum
        if (isset($schema['enum']) && is_array($schema['enum']) && !in_array($value, $schema['enum'], true)) {
            $enumValues = implode(', ', array_map(function($v) { return var_export($v, true); }, $schema['enum']));
            $errors[] = "Parameter '{$propertyPath}' must be one of: {$enumValues}";
        }
        
        // Validate minimum/maximum for numbers
        if (($type === 'number' || $type === 'integer') && is_numeric($value)) {
            if (isset($schema['minimum']) && $value < $schema['minimum']) {
                $errors[] = "Parameter '{$propertyPath}' must be greater than or equal to {$schema['minimum']}";
            }
            
            if (isset($schema['maximum']) && $value > $schema['maximum']) {
                $errors[] = "Parameter '{$propertyPath}' must be less than or equal to {$schema['maximum']}";
            }
        }
        
        // Validate minLength/maxLength for strings
        if ($type === 'string' && is_string($value)) {
            if (isset($schema['minLength']) && strlen($value) < $schema['minLength']) {
                $errors[] = "Parameter '{$propertyPath}' must be at least {$schema['minLength']} characters long";
            }
            
            if (isset($schema['maxLength']) && strlen($value) > $schema['maxLength']) {
                $errors[] = "Parameter '{$propertyPath}' must be at most {$schema['maxLength']} characters long";
            }
            
            // Validate pattern
            if (isset($schema['pattern']) && !preg_match('/' . $schema['pattern'] . '/', $value)) {
                $errors[] = "Parameter '{$propertyPath}' must match pattern: {$schema['pattern']}";
            }
        }
        
        // Validate minItems/maxItems for arrays
        if ($type === 'array' && is_array($value)) {
            if (isset($schema['minItems']) && count($value) < $schema['minItems']) {
                $errors[] = "Parameter '{$propertyPath}' must have at least {$schema['minItems']} items";
            }
            
            if (isset($schema['maxItems']) && count($value) > $schema['maxItems']) {
                $errors[] = "Parameter '{$propertyPath}' must have at most {$schema['maxItems']} items";
            }
            
            // Validate items
            if (isset($schema['items']) && is_array($schema['items'])) {
                foreach ($value as $index => $item) {
                    $this->pushPath($index);
                    $itemErrors = $this->validateProperty("{$name}[{$index}]", $item, $schema['items']);
                    $this->popPath();
                    $errors = array_merge($errors, $itemErrors);
                    
                    // Early return for performance optimization
                    if (count($errors) >= 5) {
                        break;
                    }
                }
            }
        }
        
        // Validate properties for objects
        if ($type === 'object' && is_array($value)) {
            if (isset($schema['properties'])) {
                foreach ($schema['properties'] as $propName => $propSchema) {
                    if (array_key_exists($propName, $value)) {
                        $this->pushPath($propName);
                        $propErrors = $this->validateProperty($propName, $value[$propName], $propSchema);
                        $this->popPath();
                        $errors = array_merge($errors, $propErrors);
                        
                        // Early return for performance optimization
                        if (count($errors) >= 5) {
                            break;
                        }
                    }
                }
            }
        }
        
        return $errors;
    }

    /**
     * Validate that a value matches the specified type
     *
     * @param mixed $value The value to validate
     * @param string $type The expected type
     * @return bool Whether the value matches the type
     */
    protected function validateType($value, string $type): bool {
        switch ($type) {
            case 'boolean':
                return is_bool($value);
                
            case 'integer':
                return is_int($value);
                
            case 'number':
                return is_numeric($value);
                
            case 'string':
                return is_string($value);
                
            case 'array':
                // Fixed empty array check
                return is_array($value) && (empty($value) || array_keys($value) === range(0, count($value) - 1));
                
            case 'object':
                // Fixed empty object check
                return is_array($value) && (empty($value) || array_keys($value) !== range(0, count($value) - 1));
                
            case 'null':
                return $value === null;
                
            default:
                return true; // Unknown type, assume valid
        }
    }

    /**
     * Validate that parameters match exactly one of the provided schemas
     *
     * @param array $parameters The parameters to validate
     * @param array $schemas Array of schemas to validate against
     * @return ValidationResult The validation result
     */
    protected function validateOneOf(array $parameters, array $schemas): ValidationResult {
        $validCount = 0;
        $allErrors = [];
        
        foreach ($schemas as $index => $schema) {
            $result = $this->validateAgainstSchema($parameters, $schema);
            if ($result->isValid()) {
                $validCount++;
            } else {
                $allErrors[] = "Schema option " . ($index + 1) . ": " . $result->getErrorsAsString();
            }
            
            // Early return if we've already matched more than one schema
            if ($validCount > 1) {
                break;
            }
        }
        
        if ($validCount === 1) {
            return ValidationResult::success();
        }
        
        if ($validCount === 0) {
            return ValidationResult::failure(["Parameters do not match any of the required schemas: " . implode("; ", $allErrors)]);
        }
        
        return ValidationResult::failure(["Parameters match multiple schemas when exactly one is required"]);
    }

    /**
     * Validate that parameters match at least one of the provided schemas
     *
     * @param array $parameters The parameters to validate
     * @param array $schemas Array of schemas to validate against
     * @return ValidationResult The validation result
     */
    protected function validateAnyOf(array $parameters, array $schemas): ValidationResult {
        $allErrors = [];
        
        foreach ($schemas as $index => $schema) {
            $result = $this->validateAgainstSchema($parameters, $schema);
            if ($result->isValid()) {
                return ValidationResult::success();
            }
            
            $allErrors[] = "Schema option " . ($index + 1) . ": " . $result->getErrorsAsString();
        }
        
        return ValidationResult::failure(["Parameters do not match any of the required schemas: " . implode("; ", $allErrors)]);
    }

    /**
     * Validate that parameters match all of the provided schemas
     *
     * @param array $parameters The parameters to validate
     * @param array $schemas Array of schemas to validate against
     * @return ValidationResult The validation result
     */
    protected function validateAllOf(array $parameters, array $schemas): ValidationResult {
        $errors = [];
        
        foreach ($schemas as $index => $schema) {
            $result = $this->validateAgainstSchema($parameters, $schema);
            if (!$result->isValid()) {
                $errors[] = "Failed to match schema " . ($index + 1) . ": " . $result->getErrorsAsString();
            }
        }
        
        return empty($errors) ? ValidationResult::success() : ValidationResult::failure($errors);
    }

    /**
     * Generate a cache key for validation
     *
     * @param array $parameters The parameters to validate
     * @param array $schema The schema to validate against
     * @return string The cache key
     */
    protected function generateCacheKey(array $parameters, array $schema): string {
        // Extract only essential schema properties for the cache key
        $essentialSchema = [
            'type' => $schema['type'] ?? null,
            'required' => $schema['required'] ?? [],
        ];
        
        // Include property types if available
        if (isset($schema['properties'])) {
            $essentialSchema['propertyTypes'] = [];
            foreach ($schema['properties'] as $name => $propSchema) {
                $essentialSchema['propertyTypes'][$name] = $propSchema['type'] ?? 'any';
            }
        }
        
        // Include schema features that affect validation
        foreach (['oneOf', 'anyOf', 'allOf'] as $feature) {
            if (isset($schema[$feature])) {
                $essentialSchema[$feature] = true;
            }
        }
        
        // Generate hashes
        $paramsHash = md5(json_encode(array_keys($parameters)));
        $schemaHash = md5(json_encode($essentialSchema));
        
        return "param_validation_{$paramsHash}_{$schemaHash}";
    }

    /**
     * Invalidate cached validation results
     *
     * @return bool Whether the invalidation was successful
     */
    public function invalidateCache(): bool {
        if (!$this->cacheService) {
            return false;
        }
        
        // Improved cache invalidation with better object cache support
        if (function_exists('wp_cache_flush_group')) {
            // Try to flush the cache group if the function exists (WP 6.1+)
            wp_cache_flush_group('memberpress_ai_assistant');
        }
        
        // Always use pattern deletion as a reliable fallback
        return $this->cacheService->deletePattern('param_validation_') > 0;
    }
    
    /**
     * Push a path segment onto the current path stack
     *
     * @param string|int $segment The path segment to push
     * @return void
     */
    protected function pushPath($segment): void {
        $this->currentPath[] = $segment;
    }
    
    /**
     * Pop a path segment from the current path stack
     *
     * @return void
     */
    protected function popPath(): void {
        array_pop($this->currentPath);
    }
    
    /**
     * Format the current path for error messages
     *
     * @param string|null $additionalSegment Optional additional segment to append
     * @return string The formatted path
     */
    protected function formatPath(?string $additionalSegment = null): string {
        $path = $this->currentPath;
        
        if ($additionalSegment !== null) {
            $path[] = $additionalSegment;
        }
        
        if (empty($path)) {
            return 'root';
        }
        
        $formattedPath = '';
        foreach ($path as $i => $segment) {
            if (is_numeric($segment)) {
                $formattedPath .= "[{$segment}]";
            } else {
                if ($i > 0) {
                    $formattedPath .= ".{$segment}";
                } else {
                    $formattedPath = $segment;
                }
            }
        }
        
        return $formattedPath;
    }
}