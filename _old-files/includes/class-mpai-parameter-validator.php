<?php
/**
 * Parameter Validator Class
 *
 * Handles strict validation of parameters for various tool calls
 * 
 * @package MemberPress AI Assistant
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

class MPAI_Parameter_Validator {
    /**
     * Constructor
     */
    public function __construct() {
        mpai_log_debug('Parameter Validator initialized', 'parameter-validator');
    }
    
    /**
     * Validate membership parameters
     *
     * @param array $parameters Parameters to validate
     * @return array Validation result with isValid, errors, and validated parameters
     */
    public function validate_membership_parameters($parameters) {
        mpai_log_debug('Validating membership parameters: ' . json_encode($parameters), 'parameter-validator');
        
        $result = [
            'isValid' => true,
            'errors' => [],
            'warnings' => [],
            'parameters' => []
        ];
        
        // Try to extract parameters from user message if needed
        $user_message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
        
        // Name validation - reject default or empty
        if (!isset($parameters['name']) || empty($parameters['name']) || $parameters['name'] === 'New Membership') {
            // Try to extract name from user message
            $extracted_name = '';
            // First try to match quoted strings (for multi-word names)
            if (!empty($user_message)) {
                $found_name = false;
                
                // Try to match quoted strings first
                if (preg_match('/(?:named|called|name)\s+[\'"]([^\'"]+)[\'"]/i', $user_message, $matches)) {
                    $extracted_name = trim($matches[1]);
                    $found_name = true;
                    mpai_log_debug('Extracted quoted name from user message: ' . $extracted_name, 'parameter-validator');
                }
                // If no quoted string found, try to capture everything until a natural boundary
                // Use a more conservative approach with a maximum of 5 words to avoid capturing too much text
                elseif (preg_match('/(?:named|called|name)\s+([^\s.,;:!?]{1,30}(?:\s+[^\s.,;:!?]{1,30}){0,4})(?:\s+(?:for|at|price|costs?|with|and|monthly|yearly|annually|lifetime|\$)|$|[.,;:!?])/i', $user_message, $matches)) {
                    $extracted_name = trim($matches[1]);
                    $found_name = true;
                    mpai_log_debug('Extracted multi-word name from user message: ' . $extracted_name, 'parameter-validator');
                }
                // If still no match, fall back to capturing just the next word
                elseif (preg_match('/(?:named|called|name)\s+([^\s.,;:!?]+)/i', $user_message, $matches)) {
                    $extracted_name = trim($matches[1]);
                    $found_name = true;
                    mpai_log_debug('Extracted single word name from user message: ' . $extracted_name, 'parameter-validator');
                }
                
                if ($found_name) {
                    // Remove any quotes and slashes from the name
                    $extracted_name = trim(preg_replace('/^[\'"`]|[\'"`]$|\\\\+/', '', $extracted_name));
                    
                    // Capitalize each word in the name
                    $extracted_name = implode(' ', array_map('ucfirst', explode(' ', strtolower($extracted_name))));
                    mpai_log_debug('Capitalized name from user message: ' . $extracted_name, 'parameter-validator');
                    $result['parameters']['name'] = sanitize_text_field($extracted_name);
                    $result['warnings'][] = "Name was extracted from user message: " . $extracted_name;
                }
            } else {
                // Generate a name based on period_type and price if available
                $period_display = isset($parameters['period_type']) ? ucfirst($parameters['period_type']) : 'Monthly';
                $price_display = isset($parameters['price']) ? '$' . number_format(floatval($parameters['price']), 2) : 'Premium';
                
                $generated_name = $price_display . ' ' . $period_display . ' Membership';
                $result['parameters']['name'] = sanitize_text_field($generated_name);
                $result['warnings'][] = "Generated a default name: " . $generated_name;
                mpai_log_warning('PARAMETER VALIDATOR - Generated default name: ' . $generated_name, 'parameter-validator');
            }
        } else {
            $result['parameters']['name'] = sanitize_text_field($parameters['name']);
        }
        
        // Price validation - must be positive number
        if (!isset($parameters['price'])) {
            // Try to extract price from user message
            if (!empty($user_message)) {
                if (preg_match('/\$\s*(\d+(?:\.\d+)?)/i', $user_message, $matches)) {
                    $extracted_price = floatval($matches[1]);
                    mpai_log_debug('Extracted price from user message: ' . $extracted_price, 'parameter-validator');
                    $result['parameters']['price'] = $extracted_price;
                    $result['warnings'][] = "Price was extracted from user message: $" . $extracted_price;
                } else if (preg_match('/(\d+(?:\.\d+)?)\s+dollars?/i', $user_message, $matches)) {
                    $extracted_price = floatval($matches[1]);
                    mpai_log_debug('Extracted price from user message (dollars): ' . $extracted_price, 'parameter-validator');
                    $result['parameters']['price'] = $extracted_price;
                    $result['warnings'][] = "Price was extracted from user message: $" . $extracted_price;
                } else {
                    $result['errors'][] = "Missing membership price. Price must be a positive number.";
                    $result['isValid'] = false;
                    mpai_log_error('PARAMETER VALIDATOR - Missing price parameter', 'parameter-validator');
                }
            } else {
                $result['errors'][] = "Missing membership price. Price must be a positive number.";
                $result['isValid'] = false;
                mpai_log_error('PARAMETER VALIDATOR - Missing price parameter', 'parameter-validator');
            }
        } elseif (is_string($parameters['price']) && !is_numeric($parameters['price'])) {
            // Try to extract numeric value from string
            if (preg_match('/\$?\s*(\d+(?:\.\d+)?)/', $parameters['price'], $matches)) {
                $extracted_price = floatval($matches[1]);
                mpai_log_warning('PARAMETER VALIDATOR - Converted price from string to number: ' . $extracted_price, 'parameter-validator');
                $result['parameters']['price'] = $extracted_price;
                $result['warnings'][] = "Price was provided as a string, converted to number: $" . $extracted_price;
            } else {
                $result['errors'][] = "Invalid price format. Price must be a positive number.";
                $result['isValid'] = false;
                mpai_log_error('PARAMETER VALIDATOR - Invalid price format: ' .
                    var_export($parameters['price'], true), 'parameter-validator');
            }
        } elseif (floatval($parameters['price']) <= 0) {
            $result['errors'][] = "Invalid price value. Price must be a positive number.";
            $result['isValid'] = false;
            mpai_log_error('PARAMETER VALIDATOR - Invalid price value: ' .
                var_export($parameters['price'], true), 'parameter-validator');
        } else {
            // Ensure price is stored as a float
            $result['parameters']['price'] = floatval($parameters['price']);
        }
        
        // Period type validation
        if (!isset($parameters['period_type']) || empty($parameters['period_type'])) {
            // Try to extract period_type from user message
            if (!empty($user_message) && preg_match('/\b(month(?:ly)?|annual(?:ly)?|year(?:ly)?|lifetime)\b/i', $user_message, $matches)) {
                $period = strtolower($matches[1]);
                if (strpos($period, 'month') === 0) {
                    $result['parameters']['period_type'] = 'month';
                } else if (strpos($period, 'year') === 0 || strpos($period, 'annual') === 0) {
                    $result['parameters']['period_type'] = 'year';
                } else if (strpos($period, 'lifetime') === 0) {
                    $result['parameters']['period_type'] = 'lifetime';
                }
                mpai_log_debug('Extracted period_type from user message: ' . $result['parameters']['period_type'], 'parameter-validator');
                $result['warnings'][] = "Period type was extracted from user message: " . $result['parameters']['period_type'];
            } else {
                // Default to month
                $result['parameters']['period_type'] = 'month';
                $result['warnings'][] = "Missing period_type, defaulting to 'month'";
                mpai_log_warning('PARAMETER VALIDATOR - Missing period_type, defaulting to month', 'parameter-validator');
            }
        } elseif (!in_array($parameters['period_type'], ['month', 'year', 'lifetime'])) {
            // Try to normalize the period_type
            $period_type = strtolower($parameters['period_type']);
            if (strpos($period_type, 'month') !== false) {
                $result['parameters']['period_type'] = 'month';
                $result['warnings'][] = "Normalized period_type from '{$parameters['period_type']}' to 'month'";
                mpai_log_warning('PARAMETER VALIDATOR - Normalized period_type to month', 'parameter-validator');
            } else if (strpos($period_type, 'year') !== false || strpos($period_type, 'annual') !== false) {
                $result['parameters']['period_type'] = 'year';
                $result['warnings'][] = "Normalized period_type from '{$parameters['period_type']}' to 'year'";
                mpai_log_warning('PARAMETER VALIDATOR - Normalized period_type to year', 'parameter-validator');
            } else if (strpos($period_type, 'life') !== false) {
                $result['parameters']['period_type'] = 'lifetime';
                $result['warnings'][] = "Normalized period_type from '{$parameters['period_type']}' to 'lifetime'";
                mpai_log_warning('PARAMETER VALIDATOR - Normalized period_type to lifetime', 'parameter-validator');
            } else {
                // Default to month
                $result['parameters']['period_type'] = 'month';
                $result['warnings'][] = "Invalid period_type '{$parameters['period_type']}', defaulting to 'month'";
                mpai_log_warning('PARAMETER VALIDATOR - Invalid period_type, defaulting to month', 'parameter-validator');
            }
        } else {
            $result['parameters']['period_type'] = sanitize_text_field($parameters['period_type']);
        }
        
        // Optional period validation (defaults to 1 if not provided)
        if (isset($parameters['period'])) {
            if (!is_numeric($parameters['period']) || intval($parameters['period']) < 1) {
                $result['errors'][] = "If provided, period must be a positive integer.";
                $result['isValid'] = false;
                mpai_log_error('PARAMETER VALIDATOR - Invalid period value: ' . 
                    var_export($parameters['period'], true), 'parameter-validator');
            } else {
                $result['parameters']['period'] = intval($parameters['period']);
            }
        } else {
            // Default period to 1
            $result['parameters']['period'] = 1;
        }
        
        // Optional description validation
        if (isset($parameters['description'])) {
            $result['parameters']['description'] = wp_kses_post($parameters['description']);
        }
        
        // Log validation result
        if ($result['isValid']) {
            mpai_log_debug('PARAMETER VALIDATOR - Parameters passed validation', 'parameter-validator');
        } else {
            mpai_log_error('PARAMETER VALIDATOR - Validation failed with errors: ' . 
                implode(', ', $result['errors']), 'parameter-validator');
        }
        
        return $result;
    }
    
    /**
     * Extract membership parameters from various formats sent by AI
     * 
     * @param array $parameters The input parameters
     * @return array Extracted parameters
     */
    public function extract_membership_parameters($parameters) {
        $extracted = [
            'type' => 'create' // Default type
        ];
        mpai_log_debug('Extracting membership parameters from: ' . json_encode($parameters), 'parameter-validator');
        
        // Track original parameters for debugging
        $this->trace_parameters('INITIAL_PARAMETERS', $parameters);
        
        // ENHANCED: First check for direct parameters in the input
        if (isset($parameters['name'])) $extracted['name'] = $parameters['name'];
        if (isset($parameters['price'])) {
            $extracted['price'] = is_string($parameters['price']) && is_numeric($parameters['price']) ?
                floatval($parameters['price']) : $parameters['price'];
        }
        if (isset($parameters['period_type'])) $extracted['period_type'] = $parameters['period_type'];
        
        // Check all parameters for embedded data
        foreach ($parameters as $key => $value) {
            // 1. Check for string parameters that might contain JSON
            if (is_string($value) && (strpos($value, '{') === 0 || strpos($value, '[') === 0)) {
                mpai_log_debug('Found potential JSON in parameter ' . $key, 'parameter-validator');
                try {
                    $json_data = json_decode($value, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($json_data)) {
                        mpai_log_debug('Successfully parsed JSON from ' . $key, 'parameter-validator');
                        
                        // Extract parameters based on various formats
                        if (isset($json_data['parameters']) && is_array($json_data['parameters'])) {
                            mpai_log_debug('Found nested parameters object', 'parameter-validator');
                            foreach ($json_data['parameters'] as $param_key => $param_value) {
                                $extracted[$param_key] = $param_value;
                                mpai_log_debug('Extracted from JSON: ' . $param_key . ' = ' . json_encode($param_value), 'parameter-validator');
                            }
                        } elseif (isset($json_data['name']) && !isset($json_data['tool']) && !isset($json_data['parameters'])) {
                            // This might be a direct parameters object
                            foreach ($json_data as $param_key => $param_value) {
                                $extracted[$param_key] = $param_value;
                                mpai_log_debug('Extracted from direct JSON: ' . $param_key . ' = ' . json_encode($param_value), 'parameter-validator');
                            }
                        } else {
                            // ENHANCED: Try to extract any useful parameters from the JSON
                            if (isset($json_data['name'])) $extracted['name'] = $json_data['name'];
                            if (isset($json_data['price'])) {
                                $extracted['price'] = is_string($json_data['price']) && is_numeric($json_data['price']) ?
                                    floatval($json_data['price']) : $json_data['price'];
                            }
                            if (isset($json_data['period_type'])) $extracted['period_type'] = $json_data['period_type'];
                        }
                    }
                } catch (Exception $e) {
                    mpai_log_error('Error parsing JSON parameter: ' . $e->getMessage(), 'parameter-validator');
                }
            }
            
            // 2. Check for parameters embedded in names (format, args, etc)
            if ($key === 'tool_request' && is_string($value)) {
                mpai_log_debug('Found tool_request parameter, trying to extract parameters', 'parameter-validator');
                try {
                    $tool_request = json_decode($value, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($tool_request)) {
                        if (isset($tool_request['parameters']) && is_array($tool_request['parameters'])) {
                            foreach ($tool_request['parameters'] as $param_key => $param_value) {
                                $extracted[$param_key] = $param_value;
                                mpai_log_debug('Extracted from tool_request: ' . $param_key . ' = ' . json_encode($param_value), 'parameter-validator');
                            }
                        }
                    }
                } catch (Exception $e) {
                    mpai_log_error('Error extracting from tool_request: ' . $e->getMessage(), 'parameter-validator');
                }
            }
            
            // 3. Check for parameters directly in parameters array
            if ($key === 'parameters' && is_array($value)) {
                mpai_log_debug('Found parameters array, extracting values', 'parameter-validator');
                foreach ($value as $param_key => $param_value) {
                    $extracted[$param_key] = $param_value;
                    mpai_log_debug('Extracted from parameters array: ' . $param_key . ' = ' . json_encode($param_value), 'parameter-validator');
                }
            }
        }
        
        // ENHANCED: Try to extract parameters from the user message if available
        if (empty($extracted['name']) || empty($extracted['price']) || empty($extracted['period_type'])) {
            mpai_log_debug('Attempting to extract missing parameters from user message', 'parameter-validator');
            
            // Get the user message from the request
            $user_message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
            
            if (!empty($user_message)) {
                // Extract name if missing
                if (empty($extracted['name'])) {
                    $found_name = false;
                    
                    // Try to match quoted strings first
                    if (preg_match('/(?:named|called|name)\s+[\'"]([^\'"]+)[\'"]/i', $user_message, $matches)) {
                        $extracted['name'] = trim($matches[1]);
                        $found_name = true;
                        mpai_log_debug('Extracted quoted name from user message: ' . $extracted['name'], 'parameter-validator');
                    }
                    // If no quoted string found, try to capture everything until a natural boundary
                    // Use a more conservative approach with a maximum of 5 words to avoid capturing too much text
                    elseif (preg_match('/(?:named|called|name)\s+([^\s.,;:!?]{1,30}(?:\s+[^\s.,;:!?]{1,30}){0,4})(?:\s+(?:for|at|price|costs?|with|and|monthly|yearly|annually|lifetime|\$)|$|[.,;:!?])/i', $user_message, $matches)) {
                        $extracted['name'] = trim($matches[1]);
                        $found_name = true;
                        mpai_log_debug('Extracted multi-word name from user message: ' . $extracted['name'], 'parameter-validator');
                    }
                    // If still no match, fall back to capturing just the next word
                    elseif (preg_match('/(?:named|called|name)\s+([^\s.,;:!?]+)/i', $user_message, $matches)) {
                        $extracted['name'] = trim($matches[1]);
                        $found_name = true;
                        mpai_log_debug('Extracted single word name from user message: ' . $extracted['name'], 'parameter-validator');
                    }
                    
                    if ($found_name) {
                        // Remove any quotes and slashes from the name
                        $extracted['name'] = trim(preg_replace('/^[\'"`]|[\'"`]$|\\\\+/', '', $extracted['name']));
                        
                        // Capitalize each word in the name
                        $extracted['name'] = implode(' ', array_map('ucfirst', explode(' ', strtolower($extracted['name']))));
                        mpai_log_debug('Capitalized name from user message: ' . $extracted['name'], 'parameter-validator');
                    }
                }
                
                // Extract price if missing
                if (empty($extracted['price'])) {
                    if (preg_match('/\$\s*(\d+(?:\.\d+)?)/i', $user_message, $matches)) {
                        $extracted['price'] = floatval($matches[1]);
                        mpai_log_debug('Extracted price from user message: ' . $extracted['price'], 'parameter-validator');
                    } else if (preg_match('/(\d+(?:\.\d+)?)\s+dollars?/i', $user_message, $matches)) {
                        $extracted['price'] = floatval($matches[1]);
                        mpai_log_debug('Extracted price from user message (dollars): ' . $extracted['price'], 'parameter-validator');
                    }
                }
                
                // Extract period_type if missing
                if (empty($extracted['period_type'])) {
                    if (preg_match('/\b(month(?:ly)?|annual(?:ly)?|year(?:ly)?|lifetime)\b/i', $user_message, $matches)) {
                        $period = strtolower($matches[1]);
                        if (strpos($period, 'month') === 0) {
                            $extracted['period_type'] = 'month';
                        } else if (strpos($period, 'year') === 0 || strpos($period, 'annual') === 0) {
                            $extracted['period_type'] = 'year';
                        } else if (strpos($period, 'lifetime') === 0) {
                            $extracted['period_type'] = 'lifetime';
                        }
                        mpai_log_debug('Extracted period_type from user message: ' . $extracted['period_type'], 'parameter-validator');
                    }
                }
            }
        }
        
        // Ensure price is numeric
        if (isset($extracted['price']) && is_string($extracted['price']) && is_numeric($extracted['price'])) {
            $extracted['price'] = floatval($extracted['price']);
            mpai_log_debug('Converted price from string to number: ' . $extracted['price'], 'parameter-validator');
        }
        
        // Verify period_type is valid
        if (isset($extracted['period_type']) && !in_array($extracted['period_type'], ['month', 'year', 'lifetime'])) {
            mpai_log_debug('Invalid period_type: ' . $extracted['period_type'] . ', defaulting to month', 'parameter-validator');
            $extracted['period_type'] = 'month';
        }
        
        // Set default period_type if not provided
        if (empty($extracted['period_type'])) {
            $extracted['period_type'] = 'month';
            mpai_log_debug('Setting default period_type to month', 'parameter-validator');
        }
        
        // Log the final extracted parameters
        $this->trace_parameters('EXTRACTED_PARAMETERS', $extracted);
        
        return $extracted;
    }
    
    /**
     * Trace parameters for debugging
     *
     * @param string $stage Current processing stage
     * @param array $parameters Parameters to log
     */
    public function trace_parameters($stage, $parameters) {
        mpai_log_debug("PARAMETER TRACE [{$stage}] - " . json_encode([
            'raw' => $parameters,
            'name' => isset($parameters['name']) ? $parameters['name'] : 'NOT SET',
            'name_type' => isset($parameters['name']) ? gettype($parameters['name']) : 'N/A',
            'price' => isset($parameters['price']) ? $parameters['price'] : 'NOT SET',
            'price_type' => isset($parameters['price']) ? gettype($parameters['price']) : 'N/A',
            'period_type' => isset($parameters['period_type']) ? $parameters['period_type'] : 'NOT SET',
        ]), 'parameter-validator');
    }
}

/**
 * Initialize the parameter validator
 * 
 * @return MPAI_Parameter_Validator Parameter validator instance
 */
function mpai_init_parameter_validator() {
    return new MPAI_Parameter_Validator();
}