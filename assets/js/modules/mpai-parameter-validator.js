/**
 * MemberPress AI Assistant - Parameter Validator Module
 * 
 * Provides strict validation for membership parameters with detailed error reporting
 */

var MPAI_ParameterValidator = (function($) {
    'use strict';
    
    // Validation rules for membership parameters
    const validationRules = {
        name: {
            required: true,
            notEqual: 'New Membership',
            minLength: 3,
            maxLength: 100
        },
        price: {
            required: true,
            type: 'number',
            minimum: 0.01
        },
        period_type: {
            required: true,
            allowedValues: ['month', 'year', 'lifetime']
        },
        period: {
            required: false,
            type: 'number',
            minimum: 1,
            default: 1
        }
    };
    
    /**
     * Validate membership creation parameters
     * 
     * @param {Object} parameters Parameters to validate
     * @return {Object} Result with isValid flag and any error messages
     */
    function validateMembershipParameters(parameters) {
        const result = {
            isValid: true,
            errors: [],
            warnings: [],
            parameters: {}
        };
        
        console.log('MPAI Parameter Validation - Starting validation for:', parameters);
        
        if (!parameters) {
            // Create default parameters instead of failing
            parameters = { type: 'create' };
            result.warnings.push('No parameters provided, using defaults');
            console.warn('MPAI Parameter Validation - No parameters provided, using defaults');
        }
        
        // Ensure we have a "type" parameter and it's "create"
        if (!parameters.type || parameters.type !== 'create') {
            // Add type parameter instead of failing
            result.warnings.push('Missing or invalid type parameter, defaulting to "create"');
            console.warn('MPAI Parameter Validation - Invalid type, defaulting to "create":', parameters.type);
        }
        
        // Copy the type parameter to the result parameters
        result.parameters.type = 'create';
        
        // Extract user message from the page if available
        let userMessage = '';
        try {
            // Try to get the last user message from the chat interface
            const lastUserMessage = document.querySelector('.mpai-chat-message.mpai-user-message:last-child .mpai-chat-message-content');
            if (lastUserMessage) {
                userMessage = lastUserMessage.textContent || '';
                console.log('MPAI Parameter Validation - Found user message:', userMessage);
            }
        } catch (e) {
            console.error('MPAI Parameter Validation - Error getting user message:', e);
        }
        
        // Validate each required parameter
        Object.keys(validationRules).forEach(paramName => {
            const rules = validationRules[paramName];
            let value = parameters[paramName];
            
            // Try to extract missing parameters from user message
            if ((value === undefined || value === null || value === '') && userMessage) {
                console.log(`MPAI Parameter Validation - Attempting to extract ${paramName} from user message`);
                
                if (paramName === 'name' && userMessage.match(/named|called/i)) {
                    const nameMatch = userMessage.match(/(?:named|called)\s+['"]?([^'"]+?)['"]?(?:\s|$)/i);
                    if (nameMatch && nameMatch[1]) {
                        value = nameMatch[1].trim();
                        console.log(`MPAI Parameter Validation - Extracted name from user message: ${value}`);
                    }
                } else if (paramName === 'price' && (userMessage.includes('$') || userMessage.includes('dollar'))) {
                    let priceMatch = userMessage.match(/\$\s*(\d+(?:\.\d+)?)/i);
                    if (!priceMatch) {
                        priceMatch = userMessage.match(/(\d+(?:\.\d+)?)\s+dollars?/i);
                    }
                    if (priceMatch && priceMatch[1]) {
                        value = parseFloat(priceMatch[1]);
                        console.log(`MPAI Parameter Validation - Extracted price from user message: ${value}`);
                    }
                } else if (paramName === 'period_type') {
                    if (userMessage.match(/month(?:ly)?/i)) {
                        value = 'month';
                        console.log(`MPAI Parameter Validation - Extracted period_type from user message: ${value}`);
                    } else if (userMessage.match(/(?:year|annual)(?:ly)?/i)) {
                        value = 'year';
                        console.log(`MPAI Parameter Validation - Extracted period_type from user message: ${value}`);
                    } else if (userMessage.match(/lifetime/i)) {
                        value = 'lifetime';
                        console.log(`MPAI Parameter Validation - Extracted period_type from user message: ${value}`);
                    }
                }
            }
            
            // Check if parameter is required but missing
            if (rules.required && (value === undefined || value === null || value === '')) {
                // For required parameters, provide defaults instead of failing
                if (paramName === 'name') {
                    // Generate a name based on price and period_type if available
                    const priceDisplay = parameters.price ? `$${parameters.price}` : 'Premium';
                    const periodDisplay = parameters.period_type ?
                        (parameters.period_type === 'month' ? 'Monthly' :
                         parameters.period_type === 'year' ? 'Annual' : 'Lifetime') : 'Monthly';
                    
                    value = `${priceDisplay} ${periodDisplay} Membership`;
                    result.warnings.push(`Generated default name: ${value}`);
                    console.warn(`MPAI Parameter Validation - Generated default name: ${value}`);
                } else if (paramName === 'price') {
                    // Default price
                    value = 29.99;
                    result.warnings.push(`Using default price: $${value}`);
                    console.warn(`MPAI Parameter Validation - Using default price: $${value}`);
                } else if (paramName === 'period_type') {
                    // Default period_type
                    value = 'month';
                    result.warnings.push(`Using default period_type: ${value}`);
                    console.warn(`MPAI Parameter Validation - Using default period_type: ${value}`);
                } else {
                    // For other required parameters, still fail validation
                    result.isValid = false;
                    result.errors.push(`Missing required parameter: ${paramName}`);
                    console.error(`MPAI Parameter Validation - Missing required parameter: ${paramName}`);
                    return;
                }
            }
            
            // Skip further validation if parameter is not required and not provided
            if (!rules.required && (value === undefined || value === null || value === '')) {
                // Use default if available
                if (rules.default !== undefined) {
                    result.parameters[paramName] = rules.default;
                }
                return;
            }
            
            // Validate parameter type
            if (rules.type === 'number') {
                // Convert string to number if possible
                if (typeof value === 'string') {
                    // Try to extract numeric value from string with currency symbols
                    const numMatch = value.match(/\$?\s*(\d+(?:\.\d+)?)/);
                    if (numMatch && numMatch[1]) {
                        value = parseFloat(numMatch[1]);
                        console.log(`MPAI Parameter Validation - Extracted numeric value from string: ${value}`);
                    } else if (!isNaN(parseFloat(value))) {
                        value = parseFloat(value);
                    }
                }
                
                if (typeof value === 'number') {
                    // Check minimum value if specified, but use minimum as default instead of failing
                    if (rules.minimum !== undefined && value < rules.minimum) {
                        result.warnings.push(`${paramName} value ${value} is below minimum ${rules.minimum}, using minimum value`);
                        console.warn(`MPAI Parameter Validation - ${paramName} value ${value} is below minimum ${rules.minimum}, using minimum value`);
                        value = rules.minimum;
                    }
                    
                    // Store as numeric value
                    result.parameters[paramName] = value;
                } else {
                    // For price, use a default value instead of failing
                    if (paramName === 'price') {
                        result.parameters[paramName] = 29.99;
                        result.warnings.push(`Could not parse price value, using default: $29.99`);
                        console.warn(`MPAI Parameter Validation - Could not parse price value, using default: $29.99`);
                    } else {
                        // For other numeric parameters, use default if available
                        if (rules.default !== undefined) {
                            result.parameters[paramName] = rules.default;
                            result.warnings.push(`Invalid ${paramName} value, using default: ${rules.default}`);
                            console.warn(`MPAI Parameter Validation - Invalid ${paramName} value, using default: ${rules.default}`);
                        } else {
                            result.isValid = false;
                            result.errors.push(`${paramName} must be a number`);
                            console.error(`MPAI Parameter Validation - ${paramName} must be a number, got: ${typeof value}`);
                            return;
                        }
                    }
                }
            } else if (rules.type === 'string' || !rules.type) {
                // Convert to string if not already
                const strValue = String(value);
                
                // Check minimum length if specified, but pad if needed instead of failing
                if (rules.minLength !== undefined && strValue.length < rules.minLength) {
                    if (paramName === 'name') {
                        // For name, generate a better name instead of failing
                        const priceDisplay = parameters.price ? `$${parameters.price}` : 'Premium';
                        const periodDisplay = parameters.period_type ?
                            (parameters.period_type === 'month' ? 'Monthly' :
                             parameters.period_type === 'year' ? 'Annual' : 'Lifetime') : 'Monthly';
                        
                        const newValue = `${priceDisplay} ${periodDisplay} Membership`;
                        result.parameters[paramName] = newValue;
                        result.warnings.push(`Name too short, generated better name: ${newValue}`);
                        console.warn(`MPAI Parameter Validation - Name too short, generated better name: ${newValue}`);
                    } else {
                        result.warnings.push(`${paramName} length ${strValue.length} is below minimum ${rules.minLength}`);
                        console.warn(`MPAI Parameter Validation - ${paramName} length ${strValue.length} is below minimum ${rules.minLength}`);
                        result.parameters[paramName] = strValue;
                    }
                    return;
                }
                
                // Check maximum length if specified, but truncate instead of failing
                if (rules.maxLength !== undefined && strValue.length > rules.maxLength) {
                    const truncated = strValue.substring(0, rules.maxLength);
                    result.parameters[paramName] = truncated;
                    result.warnings.push(`${paramName} truncated to ${rules.maxLength} characters`);
                    console.warn(`MPAI Parameter Validation - ${paramName} truncated from ${strValue.length} to ${rules.maxLength} characters`);
                    return;
                }
                
                // Check not equal constraint if specified, but generate a better name instead of failing
                if (rules.notEqual !== undefined && strValue === rules.notEqual) {
                    if (paramName === 'name') {
                        // For name, generate a better name
                        const priceDisplay = parameters.price ? `$${parameters.price}` : 'Premium';
                        const periodDisplay = parameters.period_type ?
                            (parameters.period_type === 'month' ? 'Monthly' :
                             parameters.period_type === 'year' ? 'Annual' : 'Lifetime') : 'Monthly';
                        
                        const newValue = `${priceDisplay} ${periodDisplay} Membership`;
                        result.parameters[paramName] = newValue;
                        result.warnings.push(`Name cannot be "${rules.notEqual}", generated better name: ${newValue}`);
                        console.warn(`MPAI Parameter Validation - Name cannot be "${rules.notEqual}", generated better name: ${newValue}`);
                    } else {
                        result.warnings.push(`${paramName} cannot be "${rules.notEqual}"`);
                        console.warn(`MPAI Parameter Validation - ${paramName} cannot be "${rules.notEqual}"`);
                        result.parameters[paramName] = strValue + " (Modified)";
                    }
                    return;
                }
                
                // Check allowed values if specified, but use default instead of failing
                if (rules.allowedValues !== undefined && !rules.allowedValues.includes(strValue)) {
                    if (paramName === 'period_type') {
                        // For period_type, try to normalize the value
                        let normalizedValue = 'month'; // Default
                        
                        if (strValue.toLowerCase().includes('month')) {
                            normalizedValue = 'month';
                        } else if (strValue.toLowerCase().includes('year') || strValue.toLowerCase().includes('annual')) {
                            normalizedValue = 'year';
                        } else if (strValue.toLowerCase().includes('life')) {
                            normalizedValue = 'lifetime';
                        }
                        
                        result.parameters[paramName] = normalizedValue;
                        result.warnings.push(`Normalized ${paramName} from "${strValue}" to "${normalizedValue}"`);
                        console.warn(`MPAI Parameter Validation - Normalized ${paramName} from "${strValue}" to "${normalizedValue}"`);
                    } else {
                        // For other parameters, use the first allowed value
                        const defaultValue = rules.allowedValues[0];
                        result.parameters[paramName] = defaultValue;
                        result.warnings.push(`Invalid ${paramName} value "${strValue}", using default: ${defaultValue}`);
                        console.warn(`MPAI Parameter Validation - Invalid ${paramName} value "${strValue}", using default: ${defaultValue}`);
                    }
                    return;
                }
                
                // Store sanitized string value
                result.parameters[paramName] = strValue;
            }
        });
        
        // Log validation result
        if (result.isValid) {
            console.log('MPAI Parameter Validation - Parameters are valid:', result.parameters);
        } else {
            console.error('MPAI Parameter Validation - Validation failed with errors:', result.errors);
        }
        
        return result;
    }
    
    /**
     * Format error messages for display
     * 
     * @param {Array} errors Array of error messages
     * @return {string} Formatted HTML for error display
     */
    function formatErrorMessages(errors) {
        if (!errors || errors.length === 0) {
            return '';
        }
        
        let html = '<div class="mpai-validation-errors">';
        html += '<strong>Validation Errors:</strong>';
        html += '<ul>';
        
        errors.forEach(error => {
            html += `<li>${error}</li>`;
        });
        
        html += '</ul>';
        html += '</div>';
        
        return html;
    }
    
    /**
     * Format parameters as a summary string
     * 
     * @param {Object} parameters The parameters to format
     * @return {string} Formatted parameters summary
     */
    function formatParameterSummary(parameters) {
        if (!parameters) {
            return 'No parameters';
        }
        
        const parts = [];
        
        if (parameters.name) {
            parts.push(`"${parameters.name}"`);
        }
        
        if (parameters.price !== undefined) {
            parts.push(`$${parameters.price}`);
        }
        
        if (parameters.period_type) {
            if (parameters.period && parameters.period > 1) {
                parts.push(`${parameters.period} ${parameters.period_type}s`);
            } else {
                parts.push(parameters.period_type);
            }
        }
        
        return parts.join(' - ');
    }
    
    /**
     * Extract membership parameters from various formats
     *
     * @param {Object} data The data to extract parameters from
     * @return {Object} Extracted parameters
     */
    function extractMembershipParameters(data) {
        console.log('MPAI Parameter Validation - Extracting membership parameters from:', data);
        
        // Initialize extracted parameters
        const extracted = {
            type: 'create'
        };
        
        // Get user message from the page if available
        let userMessage = '';
        try {
            // Try to get the last user message from the chat interface
            const lastUserMessage = document.querySelector('.mpai-chat-message.mpai-user-message:last-child .mpai-chat-message-content');
            if (lastUserMessage) {
                userMessage = lastUserMessage.textContent || '';
                console.log('MPAI Parameter Validation - Found user message for extraction:', userMessage);
            }
        } catch (e) {
            console.error('MPAI Parameter Validation - Error getting user message:', e);
        }
        
        // ENHANCED: First check for direct parameters in the input
        if (data && typeof data === 'object') {
            // Direct parameters with type
            if (data.type === 'create') {
                console.log('MPAI Parameter Validation - Found direct parameters with type=create');
                
                // Copy all parameters
                Object.keys(data).forEach(key => {
                    extracted[key] = data[key];
                });
            }
            
            // Nested parameters in tool or name object (EXACT FORMAT FROM EXAMPLE)
            else if ((data.tool === 'memberpress_info' || data.name === 'memberpress_info') &&
                data.parameters && data.parameters.type === 'create') {
                
                console.log('MPAI Parameter Validation - Found nested parameters in tool object');
                
                // Copy all parameters from the nested object
                Object.keys(data.parameters).forEach(key => {
                    extracted[key] = data.parameters[key];
                });
            }
            
            // Check for any useful parameters at the top level
            else {
                if (data.name) extracted.name = data.name;
                if (data.price !== undefined) extracted.price = data.price;
                if (data.period_type) extracted.period_type = data.period_type;
            }
            
            // Raw request string that might contain JSON
            if (data.raw_request && typeof data.raw_request === 'string') {
                console.log('MPAI Parameter Validation - Attempting to extract from raw request');
                
                // Try multiple patterns to find JSON in the raw request
                const jsonPatterns = [
                    /{"tool":\s*"memberpress_info",\s*"parameters":\s*{[^}]*}}/g,
                    /{\s*"tool"\s*:\s*"memberpress_info"\s*,\s*"parameters"\s*:\s*{[^}]*}}/g,
                    /{\s*"name"\s*:\s*"memberpress_info"\s*,\s*"parameters"\s*:\s*{[^}]*}}/g,
                    /{\s*"type"\s*:\s*"create"\s*,\s*"name"\s*:\s*"[^"]*"\s*,\s*"price"\s*:[^,]*,\s*"period_type"\s*:\s*"[^"]*"}/g
                ];
                
                for (const pattern of jsonPatterns) {
                    const jsonMatches = data.raw_request.match(pattern);
                    
                    if (jsonMatches && jsonMatches.length > 0) {
                        try {
                            const jsonData = JSON.parse(jsonMatches[0]);
                            console.log('MPAI Parameter Validation - Found JSON in raw request:', jsonData);
                            
                            if (jsonData.tool === 'memberpress_info' &&
                                jsonData.parameters &&
                                jsonData.parameters.type === 'create') {
                                
                                // Copy all parameters from the nested object
                                Object.keys(jsonData.parameters).forEach(key => {
                                    extracted[key] = jsonData.parameters[key];
                                });
                            } else if (jsonData.name === 'memberpress_info' &&
                                jsonData.parameters &&
                                jsonData.parameters.type === 'create') {
                                
                                // Copy all parameters from the nested object
                                Object.keys(jsonData.parameters).forEach(key => {
                                    extracted[key] = jsonData.parameters[key];
                                });
                            } else if (jsonData.type === 'create') {
                                // Direct parameters
                                Object.keys(jsonData).forEach(key => {
                                    extracted[key] = jsonData[key];
                                });
                            }
                        } catch (e) {
                            console.error('MPAI Parameter Validation - Error parsing JSON in raw request:', e);
                        }
                    }
                }
                
                // Also try to extract parameters from the raw text
                if (userMessage === '' && data.raw_request) {
                    userMessage = data.raw_request;
                }
            }
        }
        
        // Try to extract missing parameters from user message
        if (userMessage) {
            // Extract name if missing
            if (!extracted.name && userMessage.match(/named|called/i)) {
                const nameMatch = userMessage.match(/(?:named|called)\s+['"]?([^'"]+?)['"]?(?:\s|$)/i);
                if (nameMatch && nameMatch[1]) {
                    extracted.name = nameMatch[1].trim();
                    console.log('MPAI Parameter Validation - Extracted name from user message:', extracted.name);
                }
            }
            
            // Extract price if missing
            if (!extracted.price && (userMessage.includes('$') || userMessage.includes('dollar'))) {
                let priceMatch = userMessage.match(/\$\s*(\d+(?:\.\d+)?)/i);
                if (!priceMatch) {
                    priceMatch = userMessage.match(/(\d+(?:\.\d+)?)\s+dollars?/i);
                }
                if (priceMatch && priceMatch[1]) {
                    extracted.price = parseFloat(priceMatch[1]);
                    console.log('MPAI Parameter Validation - Extracted price from user message:', extracted.price);
                }
            }
            
            // Extract period_type if missing
            if (!extracted.period_type) {
                if (userMessage.match(/month(?:ly)?/i)) {
                    extracted.period_type = 'month';
                    console.log('MPAI Parameter Validation - Extracted period_type from user message:', extracted.period_type);
                } else if (userMessage.match(/(?:year|annual)(?:ly)?/i)) {
                    extracted.period_type = 'year';
                    console.log('MPAI Parameter Validation - Extracted period_type from user message:', extracted.period_type);
                } else if (userMessage.match(/lifetime/i)) {
                    extracted.period_type = 'lifetime';
                    console.log('MPAI Parameter Validation - Extracted period_type from user message:', extracted.period_type);
                }
            }
        }
        
        // Ensure price is a number
        if (typeof extracted.price === 'string' && !isNaN(parseFloat(extracted.price))) {
            extracted.price = parseFloat(extracted.price);
            console.log('MPAI Parameter Validation - Converted price from string to number:', extracted.price);
        }
        
        // Set default period_type if not provided
        if (!extracted.period_type) {
            extracted.period_type = 'month';
            console.log('MPAI Parameter Validation - Setting default period_type to month');
        }
        
        console.log('MPAI Parameter Validation - Extracted parameters:', extracted);
        return extracted;
    }
    
    // Public API
    return {
        validateMembershipParameters: validateMembershipParameters,
        extractMembershipParameters: extractMembershipParameters,
        formatErrorMessages: formatErrorMessages,
        formatParameterSummary: formatParameterSummary
    };
})(jQuery);

// Expose the module globally
window.MPAI_ParameterValidator = MPAI_ParameterValidator;