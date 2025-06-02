<?php
/**
 * Validation Result
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Validation;

/**
 * Class ValidationResult
 * 
 * Standardizes validation responses across the application.
 * This class encapsulates the result of a validation operation,
 * providing a consistent interface for checking validation status
 * and accessing detailed error information.
 */
class ValidationResult {
    /**
     * Whether the validation passed
     *
     * @var bool
     */
    private $isValid;

    /**
     * Array of error messages if validation failed
     *
     * @var array
     */
    private $errors;

    /**
     * Constructor
     *
     * @param bool  $isValid Whether the validation passed.
     * @param array $errors  Array of error messages if validation failed.
     */
    public function __construct(bool $isValid = true, array $errors = []) {
        $this->isValid = $isValid;
        $this->errors = $errors;
    }

    /**
     * Create a successful validation result
     *
     * @return self
     */
    public static function success(): self {
        return new self(true, []);
    }

    /**
     * Create a failed validation result with error messages
     *
     * @param array $errors Array of error messages.
     * @return self
     */
    public static function failure(array $errors): self {
        return new self(false, $errors);
    }

    /**
     * Create a validation result from the legacy validation format
     * 
     * This method helps with transitioning from the old validation system
     * which returned either true or an array of error messages.
     *
     * @param bool|array $legacyResult The result from a legacy validation method.
     * @return self
     */
    public static function fromLegacyResult($legacyResult): self {
        if ($legacyResult === true) {
            return self::success();
        }
        
        return self::failure(is_array($legacyResult) ? $legacyResult : ['Unknown validation error']);
    }

    /**
     * Check if validation passed
     *
     * @return bool
     */
    public function isValid(): bool {
        return $this->isValid;
    }

    /**
     * Check if validation failed
     *
     * @return bool
     */
    public function hasErrors(): bool {
        return !$this->isValid;
    }

    /**
     * Get all error messages
     *
     * @return array
     */
    public function getErrors(): array {
        return $this->errors;
    }

    /**
     * Get the first error message
     *
     * @return string|null Null if there are no errors
     */
    public function getFirstError(): ?string {
        return !empty($this->errors) ? reset($this->errors) : null;
    }

    /**
     * Get error messages as a single string
     *
     * @param string $separator Separator to use between error messages.
     * @return string
     */
    public function getErrorsAsString(string $separator = ', '): string {
        return implode($separator, $this->errors);
    }

    /**
     * Convert to array format for API responses
     *
     * @return array
     */
    public function toArray(): array {
        return [
            'valid' => $this->isValid,
            'errors' => $this->errors,
        ];
    }

    /**
     * Convert to the legacy validation format
     * 
     * This method helps with backward compatibility by converting
     * the ValidationResult object back to the legacy format
     * (true for success, array of errors for failure).
     *
     * @return bool|array
     */
    public function toLegacyFormat() {
        return $this->isValid ? true : $this->errors;
    }
}