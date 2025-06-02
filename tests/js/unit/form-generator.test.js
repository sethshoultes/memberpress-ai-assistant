/**
 * Unit tests for form-generator.js
 */

import { createMockElement, simulateEvent } from '../utils/test-utils';

describe('MPAIFormGenerator', () => {
  beforeEach(() => {
    // Create a test container
    document.body.innerHTML = '<div id="test-container"></div>';
    
    // Load the form-generator.js script
    require('../../../assets/js/form-generator');
  });
  
  afterEach(() => {
    // Clean up DOM
    document.body.innerHTML = '';
    
    // Clean up window.MPAIFormGenerator
    delete window.MPAIFormGenerator;
  });
  
  test('should export public API', () => {
    expect(window.MPAIFormGenerator).toBeDefined();
    expect(window.MPAIFormGenerator.createForm).toBeInstanceOf(Function);
    expect(window.MPAIFormGenerator.createFormField).toBeInstanceOf(Function);
    expect(window.MPAIFormGenerator.createToolParametersForm).toBeInstanceOf(Function);
    expect(window.MPAIFormGenerator.validateField).toBeInstanceOf(Function);
    expect(window.MPAIFormGenerator.getFormData).toBeInstanceOf(Function);
    expect(window.MPAIFormGenerator.INPUT_TYPES).toBeDefined();
    expect(window.MPAIFormGenerator.VALIDATION_TYPES).toBeDefined();
  });
  
  test('should create a form with text fields', () => {
    const fields = [
      {
        name: 'username',
        label: 'Username',
        type: 'text',
        placeholder: 'Enter your username',
        required: true
      },
      {
        name: 'email',
        label: 'Email',
        type: 'email',
        placeholder: 'Enter your email',
        required: true
      }
    ];
    
    const form = window.MPAIFormGenerator.createForm(fields);
    
    expect(form).toBeInstanceOf(HTMLFormElement);
    expect(form.classList.contains('mpai-form')).toBe(true);
    
    // Check if fields were created
    const usernameField = form.querySelector('[name="username"]');
    const emailField = form.querySelector('[name="email"]');
    
    expect(usernameField).toBeTruthy();
    expect(usernameField.type).toBe('text');
    expect(usernameField.placeholder).toBe('Enter your username');
    expect(usernameField.getAttribute('aria-required')).toBe('true');
    
    expect(emailField).toBeTruthy();
    expect(emailField.type).toBe('email');
    expect(emailField.placeholder).toBe('Enter your email');
    expect(emailField.getAttribute('aria-required')).toBe('true');
    
    // Check if submit button was created
    const submitButton = form.querySelector('button[type="submit"]');
    expect(submitButton).toBeTruthy();
    expect(submitButton.textContent).toBe('Submit');
  });
  
  test('should create a form with custom options', () => {
    const fields = [
      {
        name: 'name',
        label: 'Name',
        type: 'text'
      }
    ];
    
    const onSubmit = jest.fn();
    const onCancel = jest.fn();
    
    const form = window.MPAIFormGenerator.createForm(fields, {
      id: 'custom-form',
      className: 'custom-form-class',
      submitLabel: 'Save',
      cancelLabel: 'Cancel',
      onSubmit,
      onCancel
    });
    
    expect(form.id).toBe('custom-form');
    expect(form.classList.contains('custom-form-class')).toBe(true);
    
    // Check submit button
    const submitButton = form.querySelector('button[type="submit"]');
    expect(submitButton.textContent).toBe('Save');
    
    // Check cancel button
    const cancelButton = form.querySelector('button[type="button"]');
    expect(cancelButton.textContent).toBe('Cancel');
    
    // Test cancel button click
    simulateEvent(cancelButton, 'click');
    expect(onCancel).toHaveBeenCalled();
    
    // Test form submission
    const nameField = form.querySelector('[name="name"]');
    nameField.value = 'Test Name';
    
    form.dispatchEvent(new Event('submit'));
    expect(onSubmit).toHaveBeenCalled();
    expect(onSubmit).toHaveBeenCalledWith(
      expect.objectContaining({
        name: 'Test Name'
      }),
      expect.any(Event)
    );
  });
  
  test('should create different types of form fields', () => {
    // Test text field
    const textField = window.MPAIFormGenerator.createFormField({
      name: 'text_field',
      label: 'Text Field',
      type: 'text',
      value: 'Default text'
    });
    
    expect(textField.querySelector('label').textContent).toContain('Text Field');
    expect(textField.querySelector('input').type).toBe('text');
    expect(textField.querySelector('input').value).toBe('Default text');
    
    // Test number field
    const numberField = window.MPAIFormGenerator.createFormField({
      name: 'number_field',
      label: 'Number Field',
      type: 'number',
      value: '42',
      validation: {
        min: 0,
        max: 100
      }
    });
    
    expect(numberField.querySelector('input').type).toBe('number');
    expect(numberField.querySelector('input').value).toBe('42');
    expect(numberField.querySelector('input').min).toBe('0');
    expect(numberField.querySelector('input').max).toBe('100');
    
    // Test select field
    const selectField = window.MPAIFormGenerator.createFormField({
      name: 'select_field',
      label: 'Select Field',
      type: 'select',
      options: [
        { value: 'option1', label: 'Option 1' },
        { value: 'option2', label: 'Option 2' }
      ],
      value: 'option2'
    });
    
    expect(selectField.querySelector('select')).toBeTruthy();
    expect(selectField.querySelectorAll('option').length).toBe(2);
    expect(selectField.querySelector('option[value="option2"]').selected).toBe(true);
    
    // Test checkbox field
    const checkboxField = window.MPAIFormGenerator.createFormField({
      name: 'checkbox_field',
      label: 'Checkbox Field',
      type: 'checkbox',
      value: true
    });
    
    expect(checkboxField.querySelector('input[type="checkbox"]')).toBeTruthy();
    expect(checkboxField.querySelector('input[type="checkbox"]').checked).toBe(true);
    
    // Test radio field
    const radioField = window.MPAIFormGenerator.createFormField({
      name: 'radio_field',
      label: 'Radio Field',
      type: 'radio',
      options: ['Option 1', 'Option 2', 'Option 3'],
      value: 'Option 2'
    });
    
    expect(radioField.querySelectorAll('input[type="radio"]').length).toBe(3);
    expect(radioField.querySelector('input[value="Option 2"]').checked).toBe(true);
    
    // Test textarea field
    const textareaField = window.MPAIFormGenerator.createFormField({
      name: 'textarea_field',
      label: 'Textarea Field',
      type: 'textarea',
      value: 'Multiline\ntext',
      rows: 5
    });
    
    expect(textareaField.querySelector('textarea')).toBeTruthy();
    expect(textareaField.querySelector('textarea').value).toBe('Multiline\ntext');
    expect(textareaField.querySelector('textarea').rows).toBe(5);
  });
  
  test('should validate required fields', () => {
    const field = window.MPAIFormGenerator.createFormField({
      name: 'required_field',
      label: 'Required Field',
      type: 'text',
      required: true
    });
    
    document.body.appendChild(field);
    
    const inputElement = field.querySelector('input');
    const errorElement = field.querySelector('.mpai-form-field-error');
    
    // Field should be invalid when empty
    inputElement.value = '';
    const isValid = window.MPAIFormGenerator.validateField(inputElement);
    
    expect(isValid).toBe(false);
    expect(errorElement.textContent).toContain('required');
    expect(errorElement.style.display).not.toBe('none');
    expect(field.classList.contains('mpai-form-field-has-error')).toBe(true);
    
    // Field should be valid when filled
    inputElement.value = 'Test value';
    const isValidAfterFill = window.MPAIFormGenerator.validateField(inputElement);
    
    expect(isValidAfterFill).toBe(true);
    expect(errorElement.style.display).toBe('none');
    expect(field.classList.contains('mpai-form-field-has-error')).toBe(false);
  });
  
  test('should validate email fields', () => {
    const field = window.MPAIFormGenerator.createFormField({
      name: 'email_field',
      label: 'Email Field',
      type: 'email'
    });
    
    document.body.appendChild(field);
    
    const inputElement = field.querySelector('input');
    inputElement.dataset.validationEmail = 'true';
    
    // Invalid email
    inputElement.value = 'not-an-email';
    const isValid = window.MPAIFormGenerator.validateField(inputElement);
    expect(isValid).toBe(false);
    
    // Valid email
    inputElement.value = 'test@example.com';
    const isValidEmail = window.MPAIFormGenerator.validateField(inputElement);
    expect(isValidEmail).toBe(true);
  });
  
  test('should validate URL fields', () => {
    const field = window.MPAIFormGenerator.createFormField({
      name: 'url_field',
      label: 'URL Field',
      type: 'url'
    });
    
    document.body.appendChild(field);
    
    const inputElement = field.querySelector('input');
    inputElement.dataset.validationUrl = 'true';
    
    // Invalid URL
    inputElement.value = 'not-a-url';
    const isValid = window.MPAIFormGenerator.validateField(inputElement);
    expect(isValid).toBe(false);
    
    // Valid URL
    inputElement.value = 'https://example.com';
    const isValidUrl = window.MPAIFormGenerator.validateField(inputElement);
    expect(isValidUrl).toBe(true);
  });
  
  test('should validate min/max length', () => {
    const field = window.MPAIFormGenerator.createFormField({
      name: 'length_field',
      label: 'Length Field',
      type: 'text'
    });
    
    document.body.appendChild(field);
    
    const inputElement = field.querySelector('input');
    inputElement.dataset.validationMinLength = '5';
    inputElement.dataset.validationMaxLength = '10';
    
    // Too short
    inputElement.value = 'abcd';
    const isTooShort = window.MPAIFormGenerator.validateField(inputElement);
    expect(isTooShort).toBe(false);
    
    // Valid length
    inputElement.value = 'abcdef';
    const isValidLength = window.MPAIFormGenerator.validateField(inputElement);
    expect(isValidLength).toBe(true);
    
    // Too long
    inputElement.value = 'abcdefghijk';
    const isTooLong = window.MPAIFormGenerator.validateField(inputElement);
    expect(isTooLong).toBe(false);
  });
  
  test('should create a tool parameters form', () => {
    const tool = {
      name: 'test_tool',
      parameters: [
        {
          name: 'param1',
          label: 'Parameter 1',
          type: 'string',
          required: true,
          description: 'This is the first parameter'
        },
        {
          name: 'param2',
          label: 'Parameter 2',
          type: 'number',
          defaultValue: '42',
          validation: {
            min: 0,
            max: 100
          }
        },
        {
          name: 'param3',
          label: 'Parameter 3',
          type: 'select',
          options: ['Option 1', 'Option 2', 'Option 3']
        }
      ]
    };
    
    const form = window.MPAIFormGenerator.createToolParametersForm(tool);
    
    expect(form.id).toBe('mpai-tool-form-test_tool');
    expect(form.classList.contains('mpai-tool-form')).toBe(true);
    
    // Check if parameters were converted to form fields
    const param1Field = form.querySelector('[name="param1"]');
    const param2Field = form.querySelector('[name="param2"]');
    const param3Field = form.querySelector('[name="param3"]');
    
    expect(param1Field).toBeTruthy();
    expect(param1Field.type).toBe('text');
    expect(param1Field.getAttribute('aria-required')).toBe('true');
    
    expect(param2Field).toBeTruthy();
    expect(param2Field.type).toBe('number');
    expect(param2Field.value).toBe('42');
    expect(param2Field.min).toBe('0');
    expect(param2Field.max).toBe('100');
    
    expect(param3Field).toBeTruthy();
    expect(param3Field.tagName.toLowerCase()).toBe('select');
    expect(param3Field.options.length).toBe(3);
    
    // Check submit button
    const submitButton = form.querySelector('button[type="submit"]');
    expect(submitButton.textContent).toBe('Execute');
  });
  
  test('should get form data as an object', () => {
    // Create a form with different field types
    const form = document.createElement('form');
    
    // Text input
    const textInput = document.createElement('input');
    textInput.type = 'text';
    textInput.name = 'text_field';
    textInput.value = 'Text value';
    form.appendChild(textInput);
    
    // Number input
    const numberInput = document.createElement('input');
    numberInput.type = 'number';
    numberInput.name = 'number_field';
    numberInput.value = '42';
    form.appendChild(numberInput);
    
    // Checkbox input
    const checkboxInput = document.createElement('input');
    checkboxInput.type = 'checkbox';
    checkboxInput.name = 'checkbox_field';
    checkboxInput.checked = true;
    form.appendChild(checkboxInput);
    
    // Radio inputs
    const radio1 = document.createElement('input');
    radio1.type = 'radio';
    radio1.name = 'radio_field';
    radio1.value = 'option1';
    form.appendChild(radio1);
    
    const radio2 = document.createElement('input');
    radio2.type = 'radio';
    radio2.name = 'radio_field';
    radio2.value = 'option2';
    radio2.checked = true;
    form.appendChild(radio2);
    
    // Select input
    const selectInput = document.createElement('select');
    selectInput.name = 'select_field';
    
    const option1 = document.createElement('option');
    option1.value = 'value1';
    option1.textContent = 'Option 1';
    
    const option2 = document.createElement('option');
    option2.value = 'value2';
    option2.textContent = 'Option 2';
    option2.selected = true;
    
    selectInput.appendChild(option1);
    selectInput.appendChild(option2);
    form.appendChild(selectInput);
    
    // Get form data
    const formData = window.MPAIFormGenerator.getFormData(form);
    
    expect(formData).toEqual({
      text_field: 'Text value',
      number_field: 42,
      checkbox_field: true,
      radio_field: 'option2',
      select_field: 'value2'
    });
  });
  
  test('should add form styles to document', () => {
    // The styles should be added to the document head
    const styleElements = document.head.querySelectorAll('style');
    let formStyleFound = false;
    
    for (let i = 0; i < styleElements.length; i++) {
      if (styleElements[i].textContent.includes('.mpai-form')) {
        formStyleFound = true;
        break;
      }
    }
    
    expect(formStyleFound).toBe(true);
  });
});