/**
 * MemberPress AI Assistant Form Generator
 * 
 * This module provides functions to dynamically generate forms
 * for tool parameters in the chat interface.
 */

(function() {
    'use strict';

    /**
     * Input type constants
     */
    const INPUT_TYPES = {
        TEXT: 'text',
        NUMBER: 'number',
        SELECT: 'select',
        CHECKBOX: 'checkbox',
        RADIO: 'radio',
        TEXTAREA: 'textarea',
        EMAIL: 'email',
        URL: 'url',
        DATE: 'date'
    };

    /**
     * Validation types
     */
    const VALIDATION_TYPES = {
        REQUIRED: 'required',
        MIN_LENGTH: 'minLength',
        MAX_LENGTH: 'maxLength',
        MIN: 'min',
        MAX: 'max',
        PATTERN: 'pattern',
        EMAIL: 'email',
        URL: 'url',
        MATCH: 'match',
        CUSTOM: 'custom'
    };

    /**
     * Create a form element with specified fields
     * 
     * @param {Object[]} fields Array of field configuration objects
     * @param {Object} options Form configuration options
     * @returns {HTMLFormElement} The created form element
     */
    function createForm(fields, options = {}) {
        const {
            id = 'mpai-form-' + Math.random().toString(36).substring(2, 9),
            className = 'mpai-form',
            submitLabel = 'Submit',
            cancelLabel = 'Cancel',
            onSubmit = null,
            onCancel = null,
            showButtons = true,
            validateOnChange = true,
            validateOnSubmit = true
        } = options;
        
        // Create form element
        const form = document.createElement('form');
        form.id = id;
        form.className = className;
        form.noValidate = true; // Disable browser validation to use custom validation
        
        // Add fields to form
        fields.forEach(field => {
            const fieldElement = createFormField(field);
            form.appendChild(fieldElement);
        });
        
        // Add form buttons if enabled
        if (showButtons) {
            const buttonsContainer = document.createElement('div');
            buttonsContainer.className = 'mpai-form-buttons';
            
            // Cancel button
            if (onCancel) {
                const cancelButton = document.createElement('button');
                cancelButton.type = 'button';
                cancelButton.className = 'mpai-form-button mpai-form-button-cancel';
                cancelButton.textContent = cancelLabel;
                cancelButton.addEventListener('click', (e) => {
                    if (typeof onCancel === 'function') {
                        onCancel(e);
                    }
                });
                buttonsContainer.appendChild(cancelButton);
            }
            
            // Submit button
            const submitButton = document.createElement('button');
            submitButton.type = 'submit';
            submitButton.className = 'mpai-form-button mpai-form-button-submit';
            submitButton.textContent = submitLabel;
            buttonsContainer.appendChild(submitButton);
            
            form.appendChild(buttonsContainer);
        }
        
        // Add form validation and submission handling
        if (validateOnChange) {
            form.addEventListener('change', (e) => {
                if (e.target.tagName !== 'BUTTON') {
                    validateField(e.target);
                }
            });
            
            form.addEventListener('input', (e) => {
                if (e.target.tagName !== 'BUTTON') {
                    // Clear error when user starts typing
                    const errorElement = form.querySelector(`#${e.target.id}-error`);
                    if (errorElement) {
                        errorElement.textContent = '';
                        errorElement.style.display = 'none';
                    }
                }
            });
        }
        
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            
            let isValid = true;
            
            if (validateOnSubmit) {
                // Validate all fields
                const formFields = Array.from(form.querySelectorAll('.mpai-form-field-input'));
                formFields.forEach(field => {
                    if (!validateField(field)) {
                        isValid = false;
                    }
                });
            }
            
            if (isValid && typeof onSubmit === 'function') {
                // Get form data
                const formData = getFormData(form);
                onSubmit(formData, e);
            }
        });
        
        return form;
    }

    /**
     * Create a form field element based on field configuration
     * 
     * @param {Object} field Field configuration object
     * @returns {HTMLElement} The created field element
     */
    function createFormField(field) {
        const {
            type = INPUT_TYPES.TEXT,
            name,
            label,
            placeholder = '',
            value = '',
            options = [],
            required = false,
            disabled = false,
            readonly = false,
            validation = {},
            className = '',
            attributes = {},
            description = ''
        } = field;
        
        // Create field container
        const fieldContainer = document.createElement('div');
        fieldContainer.className = `mpai-form-field mpai-form-field-${type} ${className}`;
        
        // Create field ID if not provided
        const fieldId = field.id || `mpai-field-${name}-${Math.random().toString(36).substring(2, 9)}`;
        
        // Add label if provided
        if (label) {
            const labelElement = document.createElement('label');
            labelElement.htmlFor = fieldId;
            labelElement.className = 'mpai-form-field-label';
            labelElement.textContent = label;
            
            // Add required indicator
            if (required) {
                const requiredIndicator = document.createElement('span');
                requiredIndicator.className = 'mpai-form-required';
                requiredIndicator.textContent = '*';
                requiredIndicator.setAttribute('aria-hidden', 'true');
                labelElement.appendChild(requiredIndicator);
                
                // Add screen reader text
                const srText = document.createElement('span');
                srText.className = 'mpai-sr-only';
                srText.textContent = '(required)';
                labelElement.appendChild(srText);
            }
            
            fieldContainer.appendChild(labelElement);
        }
        
        // Create input element based on type
        let inputElement;
        
        switch (type) {
            case INPUT_TYPES.SELECT:
                inputElement = document.createElement('select');
                
                // Add options
                options.forEach(option => {
                    const optionElement = document.createElement('option');
                    
                    if (typeof option === 'object') {
                        optionElement.value = option.value;
                        optionElement.textContent = option.label;
                        if (option.disabled) optionElement.disabled = true;
                    } else {
                        optionElement.value = option;
                        optionElement.textContent = option;
                    }
                    
                    // Set selected if value matches
                    if (optionElement.value === value) {
                        optionElement.selected = true;
                    }
                    
                    inputElement.appendChild(optionElement);
                });
                break;
                
            case INPUT_TYPES.CHECKBOX:
                // Create wrapper for checkbox and label
                const checkboxWrapper = document.createElement('div');
                checkboxWrapper.className = 'mpai-form-checkbox-wrapper';
                
                inputElement = document.createElement('input');
                inputElement.type = 'checkbox';
                
                // Set checked state
                if (value === true || value === 'true' || value === '1' || value === 1) {
                    inputElement.checked = true;
                }
                
                // Move label after checkbox
                if (label) {
                    const labelElement = fieldContainer.querySelector('label');
                    if (labelElement) {
                        fieldContainer.removeChild(labelElement);
                        checkboxWrapper.appendChild(inputElement);
                        checkboxWrapper.appendChild(labelElement);
                        fieldContainer.appendChild(checkboxWrapper);
                    }
                } else {
                    checkboxWrapper.appendChild(inputElement);
                    fieldContainer.appendChild(checkboxWrapper);
                }
                break;
                
            case INPUT_TYPES.RADIO:
                // Create radio group
                const radioGroup = document.createElement('div');
                radioGroup.className = 'mpai-form-radio-group';
                
                // Add radio options
                options.forEach((option, index) => {
                    const radioWrapper = document.createElement('div');
                    radioWrapper.className = 'mpai-form-radio-wrapper';
                    
                    const radioInput = document.createElement('input');
                    radioInput.type = 'radio';
                    radioInput.name = name;
                    
                    const optionId = `${fieldId}-option-${index}`;
                    radioInput.id = optionId;
                    
                    if (typeof option === 'object') {
                        radioInput.value = option.value;
                        if (option.disabled) radioInput.disabled = true;
                        
                        // Set checked if value matches
                        if (option.value === value) {
                            radioInput.checked = true;
                        }
                        
                        const radioLabel = document.createElement('label');
                        radioLabel.htmlFor = optionId;
                        radioLabel.textContent = option.label;
                        
                        radioWrapper.appendChild(radioInput);
                        radioWrapper.appendChild(radioLabel);
                    } else {
                        radioInput.value = option;
                        
                        // Set checked if value matches
                        if (option === value) {
                            radioInput.checked = true;
                        }
                        
                        const radioLabel = document.createElement('label');
                        radioLabel.htmlFor = optionId;
                        radioLabel.textContent = option;
                        
                        radioWrapper.appendChild(radioInput);
                        radioWrapper.appendChild(radioLabel);
                    }
                    
                    radioGroup.appendChild(radioWrapper);
                });
                
                fieldContainer.appendChild(radioGroup);
                
                // Use the first radio input as the reference for validation
                inputElement = radioGroup.querySelector('input[type="radio"]');
                break;
                
            case INPUT_TYPES.TEXTAREA:
                inputElement = document.createElement('textarea');
                inputElement.value = value;
                inputElement.placeholder = placeholder;
                inputElement.rows = field.rows || 4;
                break;
                
            default:
                // Default to text input
                inputElement = document.createElement('input');
                inputElement.type = type;
                inputElement.value = value;
                inputElement.placeholder = placeholder;
                
                // Add specific attributes for number inputs
                if (type === INPUT_TYPES.NUMBER) {
                    if (validation.min !== undefined) inputElement.min = validation.min;
                    if (validation.max !== undefined) inputElement.max = validation.max;
                    if (field.step !== undefined) inputElement.step = field.step;
                }
                break;
        }
        
        // Set common attributes
        inputElement.id = fieldId;
        inputElement.name = name;
        inputElement.className = 'mpai-form-field-input';
        
        if (required) inputElement.setAttribute('aria-required', 'true');
        if (disabled) inputElement.disabled = true;
        if (readonly) inputElement.readOnly = true;
        
        // Add any additional attributes
        for (const [key, value] of Object.entries(attributes)) {
            inputElement.setAttribute(key, value);
        }
        
        // Add validation attributes
        if (validation) {
            if (validation.required) inputElement.dataset.validationRequired = 'true';
            if (validation.minLength) inputElement.dataset.validationMinLength = validation.minLength;
            if (validation.maxLength) inputElement.dataset.validationMaxLength = validation.maxLength;
            if (validation.min) inputElement.dataset.validationMin = validation.min;
            if (validation.max) inputElement.dataset.validationMax = validation.max;
            if (validation.pattern) inputElement.dataset.validationPattern = validation.pattern;
            if (validation.email) inputElement.dataset.validationEmail = 'true';
            if (validation.url) inputElement.dataset.validationUrl = 'true';
            if (validation.match) inputElement.dataset.validationMatch = validation.match;
            if (validation.message) inputElement.dataset.validationMessage = validation.message;
        }
        
        // Add input to container if not already added (for checkbox and radio)
        if (type !== INPUT_TYPES.CHECKBOX && type !== INPUT_TYPES.RADIO) {
            fieldContainer.appendChild(inputElement);
        }
        
        // Add description if provided
        if (description) {
            const descriptionElement = document.createElement('div');
            descriptionElement.className = 'mpai-form-field-description';
            descriptionElement.textContent = description;
            fieldContainer.appendChild(descriptionElement);
        }
        
        // Add error message container
        const errorElement = document.createElement('div');
        errorElement.id = `${fieldId}-error`;
        errorElement.className = 'mpai-form-field-error';
        errorElement.setAttribute('aria-live', 'polite');
        errorElement.style.display = 'none';
        fieldContainer.appendChild(errorElement);
        
        return fieldContainer;
    }

    /**
     * Validate a form field
     * 
     * @param {HTMLElement} field The field element to validate
     * @returns {boolean} Whether the field is valid
     */
    function validateField(field) {
        // Get field properties
        const fieldId = field.id;
        const fieldType = field.type;
        const fieldValue = fieldType === 'checkbox' ? field.checked : field.value;
        
        // Get error element
        const errorElement = document.querySelector(`#${fieldId}-error`);
        if (!errorElement) return true; // No error element, can't show errors
        
        // Get validation rules from data attributes
        const validationRules = {
            required: field.dataset.validationRequired === 'true',
            minLength: parseInt(field.dataset.validationMinLength, 10) || null,
            maxLength: parseInt(field.dataset.validationMaxLength, 10) || null,
            min: parseFloat(field.dataset.validationMin) || null,
            max: parseFloat(field.dataset.validationMax) || null,
            pattern: field.dataset.validationPattern || null,
            email: field.dataset.validationEmail === 'true',
            url: field.dataset.validationUrl === 'true',
            match: field.dataset.validationMatch || null,
            message: field.dataset.validationMessage || null
        };
        
        // Check if field is required and empty
        if (validationRules.required && 
            (fieldValue === '' || fieldValue === null || fieldValue === undefined)) {
            showError(errorElement, 'This field is required');
            return false;
        }
        
        // Skip other validations if field is empty and not required
        if (fieldValue === '' || fieldValue === null || fieldValue === undefined) {
            hideError(errorElement);
            return true;
        }
        
        // Validate minLength
        if (validationRules.minLength !== null && 
            typeof fieldValue === 'string' && 
            fieldValue.length < validationRules.minLength) {
            showError(errorElement, `Must be at least ${validationRules.minLength} characters`);
            return false;
        }
        
        // Validate maxLength
        if (validationRules.maxLength !== null && 
            typeof fieldValue === 'string' && 
            fieldValue.length > validationRules.maxLength) {
            showError(errorElement, `Must be no more than ${validationRules.maxLength} characters`);
            return false;
        }
        
        // Validate min value
        if (validationRules.min !== null && 
            (fieldType === 'number' || !isNaN(parseFloat(fieldValue))) && 
            parseFloat(fieldValue) < validationRules.min) {
            showError(errorElement, `Must be at least ${validationRules.min}`);
            return false;
        }
        
        // Validate max value
        if (validationRules.max !== null && 
            (fieldType === 'number' || !isNaN(parseFloat(fieldValue))) && 
            parseFloat(fieldValue) > validationRules.max) {
            showError(errorElement, `Must be no more than ${validationRules.max}`);
            return false;
        }
        
        // Validate pattern
        if (validationRules.pattern !== null && 
            !new RegExp(validationRules.pattern).test(fieldValue)) {
            showError(errorElement, validationRules.message || 'Invalid format');
            return false;
        }
        
        // Validate email
        if (validationRules.email && !isValidEmail(fieldValue)) {
            showError(errorElement, 'Please enter a valid email address');
            return false;
        }
        
        // Validate URL
        if (validationRules.url && !isValidUrl(fieldValue)) {
            showError(errorElement, 'Please enter a valid URL');
            return false;
        }
        
        // Validate matching field
        if (validationRules.match) {
            const matchField = document.getElementById(validationRules.match);
            if (matchField && fieldValue !== matchField.value) {
                showError(errorElement, 'Fields do not match');
                return false;
            }
        }
        
        // Field is valid
        hideError(errorElement);
        return true;
    }

    /**
     * Show an error message for a field
     * 
     * @param {HTMLElement} errorElement The error element
     * @param {string} message The error message
     */
    function showError(errorElement, message) {
        errorElement.textContent = message;
        errorElement.style.display = 'block';
        
        // Add error class to parent field
        const fieldContainer = errorElement.closest('.mpai-form-field');
        if (fieldContainer) {
            fieldContainer.classList.add('mpai-form-field-has-error');
        }
    }

    /**
     * Hide the error message for a field
     * 
     * @param {HTMLElement} errorElement The error element
     */
    function hideError(errorElement) {
        errorElement.textContent = '';
        errorElement.style.display = 'none';
        
        // Remove error class from parent field
        const fieldContainer = errorElement.closest('.mpai-form-field');
        if (fieldContainer) {
            fieldContainer.classList.remove('mpai-form-field-has-error');
        }
    }

    /**
     * Check if a string is a valid email address
     * 
     * @param {string} email The email address to validate
     * @returns {boolean} Whether the email is valid
     */
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    /**
     * Check if a string is a valid URL
     * 
     * @param {string} url The URL to validate
     * @returns {boolean} Whether the URL is valid
     */
    function isValidUrl(url) {
        try {
            new URL(url);
            return true;
        } catch (e) {
            return false;
        }
    }

    /**
     * Get form data as an object
     * 
     * @param {HTMLFormElement} form The form element
     * @returns {Object} Form data as key-value pairs
     */
    function getFormData(form) {
        const formData = {};
        const formElements = form.elements;
        
        for (let i = 0; i < formElements.length; i++) {
            const element = formElements[i];
            
            // Skip buttons and elements without a name
            if (element.tagName === 'BUTTON' || !element.name) {
                continue;
            }
            
            // Handle different input types
            switch (element.type) {
                case 'checkbox':
                    formData[element.name] = element.checked;
                    break;
                    
                case 'radio':
                    if (element.checked) {
                        formData[element.name] = element.value;
                    }
                    break;
                    
                case 'select-multiple':
                    const selectedOptions = Array.from(element.options)
                        .filter(option => option.selected)
                        .map(option => option.value);
                    formData[element.name] = selectedOptions;
                    break;
                    
                case 'number':
                    formData[element.name] = element.value === '' ? '' : parseFloat(element.value);
                    break;
                    
                default:
                    formData[element.name] = element.value;
                    break;
            }
        }
        
        return formData;
    }

    /**
     * Create a form for tool parameters
     * 
     * @param {Object} tool Tool configuration
     * @param {Object} options Form configuration options
     * @returns {HTMLFormElement} The created form element
     */
    function createToolParametersForm(tool, options = {}) {
        const {
            name,
            parameters = []
        } = tool;
        
        // Convert parameters to form fields
        const fields = parameters.map(param => {
            const field = {
                name: param.name,
                label: param.label || param.name,
                type: mapParameterTypeToInputType(param.type),
                placeholder: param.placeholder || '',
                value: param.defaultValue || '',
                required: param.required === true,
                options: param.options || [],
                description: param.description || '',
                validation: {}
            };
            
            // Add validation rules
            if (param.required) {
                field.validation.required = true;
            }
            
            if (param.validation) {
                field.validation = { ...field.validation, ...param.validation };
            }
            
            return field;
        });
        
        // Create form with default options
        const defaultOptions = {
            id: `mpai-tool-form-${name}`,
            className: 'mpai-tool-form',
            submitLabel: 'Execute',
            validateOnSubmit: true,
            ...options
        };
        
        return createForm(fields, defaultOptions);
    }

    /**
     * Map parameter type to input type
     * 
     * @param {string} paramType Parameter type
     * @returns {string} Input type
     */
    function mapParameterTypeToInputType(paramType) {
        switch (paramType) {
            case 'string':
                return INPUT_TYPES.TEXT;
            case 'number':
                return INPUT_TYPES.NUMBER;
            case 'boolean':
                return INPUT_TYPES.CHECKBOX;
            case 'select':
                return INPUT_TYPES.SELECT;
            case 'radio':
                return INPUT_TYPES.RADIO;
            case 'textarea':
                return INPUT_TYPES.TEXTAREA;
            case 'email':
                return INPUT_TYPES.EMAIL;
            case 'url':
                return INPUT_TYPES.URL;
            case 'date':
                return INPUT_TYPES.DATE;
            default:
                return INPUT_TYPES.TEXT;
        }
    }

    /**
     * Add CSS styles for forms
     */
    function addFormStyles() {
        const styleElement = document.createElement('style');
        styleElement.textContent = `
            /* Form Base Styles */
            .mpai-form {
                margin: 10px 0;
                font-size: 14px;
            }
            
            /* Form Field */
            .mpai-form-field {
                margin-bottom: 15px;
            }
            
            .mpai-form-field-label {
                display: block;
                margin-bottom: 5px;
                font-weight: 600;
            }
            
            .mpai-form-required {
                color: #dc3232;
                margin-left: 3px;
            }
            
            .mpai-sr-only {
                position: absolute;
                width: 1px;
                height: 1px;
                padding: 0;
                margin: -1px;
                overflow: hidden;
                clip: rect(0, 0, 0, 0);
                white-space: nowrap;
                border: 0;
            }
            
            .mpai-form-field-input {
                width: 100%;
                padding: 8px 12px;
                border: 1px solid #ddd;
                border-radius: 4px;
                font-size: 14px;
                line-height: 1.4;
                transition: border-color 0.2s ease;
            }
            
            .mpai-form-field-input:focus {
                border-color: #0073aa;
                outline: none;
                box-shadow: 0 0 0 1px #0073aa;
            }
            
            .mpai-form-field-description {
                margin-top: 5px;
                font-size: 12px;
                color: #666;
            }
            
            .mpai-form-field-error {
                margin-top: 5px;
                font-size: 12px;
                color: #dc3232;
            }
            
            .mpai-form-field-has-error .mpai-form-field-input {
                border-color: #dc3232;
            }
            
            /* Checkbox and Radio Styles */
            .mpai-form-checkbox-wrapper,
            .mpai-form-radio-wrapper {
                display: flex;
                align-items: center;
                margin-bottom: 5px;
            }
            
            .mpai-form-checkbox-wrapper label,
            .mpai-form-radio-wrapper label {
                margin-bottom: 0;
                margin-left: 8px;
                font-weight: normal;
            }
            
            .mpai-form-checkbox-wrapper input[type="checkbox"],
            .mpai-form-radio-wrapper input[type="radio"] {
                width: auto;
                margin-right: 5px;
            }
            
            .mpai-form-radio-group {
                margin-top: 5px;
            }
            
            /* Form Buttons */
            .mpai-form-buttons {
                display: flex;
                justify-content: flex-end;
                gap: 10px;
                margin-top: 20px;
            }
            
            .mpai-form-button {
                padding: 8px 16px;
                border: none;
                border-radius: 4px;
                font-size: 14px;
                cursor: pointer;
                transition: background-color 0.2s ease;
            }
            
            .mpai-form-button-submit {
                background-color: #0073aa;
                color: #fff;
            }
            
            .mpai-form-button-submit:hover {
                background-color: #005d8c;
            }
            
            .mpai-form-button-cancel {
                background-color: #f1f1f1;
                color: #333;
            }
            
            .mpai-form-button-cancel:hover {
                background-color: #e2e2e2;
            }
            
            /* Dark Mode Support */
            @media (prefers-color-scheme: dark) {
                .mpai-form-field-input {
                    background-color: #3c434a;
                    border-color: #4f5a65;
                    color: #f0f0f1;
                }
                
                .mpai-form-field-input:focus {
                    border-color: #3db2ff;
                    box-shadow: 0 0 0 1px #3db2ff;
                }
                
                .mpai-form-field-description {
                    color: #bbb;
                }
                
                .mpai-form-button-cancel {
                    background-color: #3c434a;
                    color: #f0f0f1;
                }
                
                .mpai-form-button-cancel:hover {
                    background-color: #4f5a65;
                }
            }
        `;
        document.head.appendChild(styleElement);
    }

    // Initialize the module
    function init() {
        // Add form styles to the document
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', addFormStyles);
        } else {
            addFormStyles();
        }
    }

    // Initialize on load
    init();

    // Export public API
    window.MPAIFormGenerator = {
        createForm,
        createFormField,
        createToolParametersForm,
        validateField,
        getFormData,
        INPUT_TYPES,
        VALIDATION_TYPES
    };

})();