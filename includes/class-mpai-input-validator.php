<?php
/**
 * Input Validator
 *
 * Provides comprehensive input validation and sanitization for all plugin components
 *
 * @package MemberPress AI Assistant
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Input Validator Class
 * 
 * Centralizes input validation and sanitization across the plugin
 */
class MPAI_Input_Validator {

    /**
     * Validation rules
     * @var array
     */
    private $rules = [];

    /**
     * Error messages for validation
     * @var array
     */
    private $error_messages = [];

    /**
     * Default error templates
     * @var array
     */
    private $default_error_templates = [
        'required' => '%s is required',
        'type' => '%s must be of type %s',
        'enum' => '%s must be one of: %s',
        'min' => '%s must be at least %s',
        'max' => '%s must be at most %s',
        'min_length' => '%s must be at least %s characters',
        'max_length' => '%s must be at most %s characters',
        'pattern' => '%s does not match the required pattern',
        'email' => '%s must be a valid email address',
        'url' => '%s must be a valid URL',
        'numeric' => '%s must be numeric',
        'boolean' => '%s must be a boolean',
        'array' => '%s must be an array',
        'object' => '%s must be an object',
    ];

    /**
     * Constructor
     */
    public function __construct() {
        // No initialization needed
    }

    /**
     * Add a validation rule
     *
     * @param string $field Field name
     * @param array $rules Rules for the field
     * @param string $error_message Custom error message
     * @return MPAI_Input_Validator This instance for chaining
     */
    public function add_rule($field, $rules, $error_message = '') {
        $this->rules[$field] = $rules;
        
        if (!empty($error_message)) {
            $this->error_messages[$field] = $error_message;
        }

        return $this;
    }

    /**
     * Load rules from OpenAI/Anthropic function parameter schema
     *
     * @param array $parameter_schema Parameter schema from function definition
     * @return MPAI_Input_Validator This instance for chaining
     */
    public function load_from_schema($parameter_schema) {
        if (isset($parameter_schema['properties']) && is_array($parameter_schema['properties'])) {
            foreach ($parameter_schema['properties'] as $field => $schema) {
                $rules = [];

                // Extract validation rules from schema
                if (isset($schema['type'])) {
                    $rules['type'] = $schema['type'];
                }
                
                if (isset($schema['required']) && $schema['required']) {
                    $rules['required'] = true;
                } elseif (isset($parameter_schema['required']) && is_array($parameter_schema['required']) && in_array($field, $parameter_schema['required'])) {
                    $rules['required'] = true;
                }
                
                if (isset($schema['enum'])) {
                    $rules['enum'] = $schema['enum'];
                }
                
                if (isset($schema['minimum'])) {
                    $rules['min'] = $schema['minimum'];
                }
                
                if (isset($schema['maximum'])) {
                    $rules['max'] = $schema['maximum'];
                }
                
                if (isset($schema['minLength'])) {
                    $rules['min_length'] = $schema['minLength'];
                }
                
                if (isset($schema['maxLength'])) {
                    $rules['max_length'] = $schema['maxLength'];
                }
                
                if (isset($schema['pattern'])) {
                    $rules['pattern'] = $schema['pattern'];
                }
                
                if (isset($schema['format'])) {
                    $rules['format'] = $schema['format'];
                }
                
                // Add custom error message if available
                $error_message = isset($schema['description']) ? $schema['description'] : '';
                
                // Add the rule
                $this->add_rule($field, $rules, $error_message);
            }
        }
        
        return $this;
    }

    /**
     * Validate input data against rules
     *
     * @param array $data Input data to validate
     * @return array Array with 'valid' boolean and 'errors' array
     */
    public function validate($data) {
        $errors = [];
        $validated_data = [];

        foreach ($this->rules as $field => $rules) {
            $field_errors = [];
            $value = isset($data[$field]) ? $data[$field] : null;
            
            // Apply validation rules
            foreach ($rules as $rule => $rule_value) {
                switch ($rule) {
                    case 'required':
                        if ($rule_value && ($value === null || $value === '')) {
                            $field_errors[] = sprintf(
                                $this->error_messages[$field] ?? $this->default_error_templates['required'],
                                $field
                            );
                        }
                        break;
                        
                    case 'type':
                        if ($value !== null && !$this->validate_type($value, $rule_value)) {
                            $field_errors[] = sprintf(
                                $this->error_messages[$field] ?? $this->default_error_templates['type'],
                                $field,
                                $rule_value
                            );
                        }
                        break;
                        
                    case 'enum':
                        if ($value !== null && !in_array($value, $rule_value, true)) {
                            $field_errors[] = sprintf(
                                $this->error_messages[$field] ?? $this->default_error_templates['enum'],
                                $field,
                                implode(', ', $rule_value)
                            );
                        }
                        break;
                        
                    case 'min':
                        if ($value !== null && is_numeric($value) && $value < $rule_value) {
                            $field_errors[] = sprintf(
                                $this->error_messages[$field] ?? $this->default_error_templates['min'],
                                $field,
                                $rule_value
                            );
                        }
                        break;
                        
                    case 'max':
                        if ($value !== null && is_numeric($value) && $value > $rule_value) {
                            $field_errors[] = sprintf(
                                $this->error_messages[$field] ?? $this->default_error_templates['max'],
                                $field,
                                $rule_value
                            );
                        }
                        break;
                        
                    case 'min_length':
                        if ($value !== null && is_string($value) && mb_strlen($value) < $rule_value) {
                            $field_errors[] = sprintf(
                                $this->error_messages[$field] ?? $this->default_error_templates['min_length'],
                                $field,
                                $rule_value
                            );
                        }
                        break;
                        
                    case 'max_length':
                        if ($value !== null && is_string($value) && mb_strlen($value) > $rule_value) {
                            $field_errors[] = sprintf(
                                $this->error_messages[$field] ?? $this->default_error_templates['max_length'],
                                $field,
                                $rule_value
                            );
                        }
                        break;
                        
                    case 'pattern':
                        if ($value !== null && is_string($value) && !preg_match('/' . $rule_value . '/', $value)) {
                            $field_errors[] = sprintf(
                                $this->error_messages[$field] ?? $this->default_error_templates['pattern'],
                                $field
                            );
                        }
                        break;
                        
                    case 'format':
                        if ($value !== null) {
                            if ($rule_value === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                                $field_errors[] = sprintf(
                                    $this->error_messages[$field] ?? $this->default_error_templates['email'],
                                    $field
                                );
                            } elseif ($rule_value === 'uri' && !filter_var($value, FILTER_VALIDATE_URL)) {
                                $field_errors[] = sprintf(
                                    $this->error_messages[$field] ?? $this->default_error_templates['url'],
                                    $field
                                );
                            }
                        }
                        break;
                }
            }
            
            // If value exists and there are no errors, sanitize and add to validated data
            if ($value !== null && empty($field_errors)) {
                $validated_data[$field] = $this->sanitize($value, $rules['type'] ?? null);
            }
            
            // If there are errors for this field, add them to the main errors array
            if (!empty($field_errors)) {
                $errors[$field] = $field_errors;
            }
        }
        
        // Log validation errors
        if (!empty($errors) && function_exists('mpai_log_warning')) {
            mpai_log_warning('Validation failed: ' . json_encode($errors), 'input-validator');
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'data' => $validated_data
        ];
    }

    /**
     * Validate if a value is of a specific type
     *
     * @param mixed $value Value to check
     * @param string $type Expected type
     * @return bool Whether the value is of the expected type
     */
    private function validate_type($value, $type) {
        switch ($type) {
            case 'string':
                return is_string($value);
                
            case 'number':
            case 'integer':
                return is_numeric($value);
                
            case 'boolean':
                return is_bool($value) || $value === 'true' || $value === 'false' || $value === '0' || $value === '1' || 
                       $value === 0 || $value === 1;
                
            case 'array':
                return is_array($value);
                
            case 'object':
                return is_object($value) || (is_array($value) && array_keys($value) !== range(0, count($value) - 1));
                
            case 'null':
                return $value === null;
                
            default:
                return true; // Unknown type, assume valid
        }
    }

    /**
     * Sanitize a value based on its expected type
     *
     * @param mixed $value Value to sanitize
     * @param string $type Expected type
     * @return mixed Sanitized value
     */
    public function sanitize($value, $type = null) {
        // Check type of value for specific sanitization
        if ($type === 'string' || is_string($value)) {
            return $this->sanitize_string($value);
        } elseif ($type === 'integer' || is_int($value)) {
            return $this->sanitize_int($value);
        } elseif (($type === 'number' || is_float($value)) && !is_int($value)) {
            return $this->sanitize_float($value);
        } elseif ($type === 'boolean' || is_bool($value)) {
            return $this->sanitize_bool($value);
        } elseif ($type === 'array' || is_array($value)) {
            return $this->sanitize_array($value);
        } elseif ($type === 'object' || is_object($value)) {
            return $this->sanitize_object($value);
        }
        
        // Default sanitization (best guess based on the value)
        if (is_string($value)) {
            return $this->sanitize_string($value);
        } elseif (is_int($value)) {
            return $this->sanitize_int($value);
        } elseif (is_float($value)) {
            return $this->sanitize_float($value);
        } elseif (is_bool($value)) {
            return $this->sanitize_bool($value);
        } elseif (is_array($value)) {
            return $this->sanitize_array($value);
        } elseif (is_object($value)) {
            return $this->sanitize_object($value);
        }
        
        // Unknown type, return as is
        return $value;
    }

    /**
     * Sanitize a string value
     *
     * @param string $value String to sanitize
     * @return string Sanitized string
     */
    public function sanitize_string($value) {
        // Use WordPress sanitization when available
        if (function_exists('sanitize_text_field')) {
            return sanitize_text_field($value);
        }
        
        // Fallback sanitization
        return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Sanitize an integer value
     *
     * @param mixed $value Value to sanitize as integer
     * @return int Sanitized integer
     */
    public function sanitize_int($value) {
        return intval($value);
    }

    /**
     * Sanitize a float value
     *
     * @param mixed $value Value to sanitize as float
     * @return float Sanitized float
     */
    public function sanitize_float($value) {
        return floatval($value);
    }

    /**
     * Sanitize a boolean value
     *
     * @param mixed $value Value to sanitize as boolean
     * @return bool Sanitized boolean
     */
    public function sanitize_bool($value) {
        if (is_string($value)) {
            return $value === 'true' || $value === '1';
        }
        return (bool) $value;
    }

    /**
     * Sanitize an array value
     *
     * @param array $value Array to sanitize
     * @return array Sanitized array
     */
    public function sanitize_array($value) {
        $sanitized = [];
        
        foreach ($value as $key => $item) {
            // Sanitize the key
            $sanitized_key = is_string($key) ? $this->sanitize_string($key) : $key;
            
            // Sanitize the value recursively
            $sanitized[$sanitized_key] = $this->sanitize($item);
        }
        
        return $sanitized;
    }

    /**
     * Sanitize an object value
     *
     * @param object $value Object to sanitize
     * @return object Sanitized object
     */
    public function sanitize_object($value) {
        // Convert to array, sanitize, then convert back
        $array = (array) $value;
        $sanitized_array = $this->sanitize_array($array);
        return (object) $sanitized_array;
    }

    /**
     * Get default value for a field
     *
     * @param string $field Field name
     * @return mixed Default value or null if not specified
     */
    public function get_default($field) {
        return isset($this->rules[$field]['default']) ? $this->rules[$field]['default'] : null;
    }

    /**
     * Apply defaults to data array
     *
     * @param array $data Input data
     * @return array Data with defaults applied
     */
    public function apply_defaults($data) {
        foreach ($this->rules as $field => $rules) {
            if (!isset($data[$field]) && isset($rules['default'])) {
                $data[$field] = $rules['default'];
            }
        }
        
        return $data;
    }
}