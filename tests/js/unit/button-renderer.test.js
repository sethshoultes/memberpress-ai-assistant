/**
 * Unit tests for button-renderer.js
 */

import { createMockElement } from '../utils/test-utils';

describe('MPAIButtonRenderer', () => {
  beforeEach(() => {
    // Load the button-renderer.js script
    require('../../../assets/js/button-renderer');
    
    // Create a test container
    document.body.innerHTML = '<div id="test-container"></div>';
  });
  
  afterEach(() => {
    // Clean up DOM
    document.body.innerHTML = '';
    
    // Clean up window.MPAIButtonRenderer
    delete window.MPAIButtonRenderer;
  });
  
  test('should export public API', () => {
    expect(window.MPAIButtonRenderer).toBeDefined();
    expect(window.MPAIButtonRenderer.createButton).toBeInstanceOf(Function);
    expect(window.MPAIButtonRenderer.createButtonGroup).toBeInstanceOf(Function);
    expect(window.MPAIButtonRenderer.renderButton).toBeInstanceOf(Function);
    expect(window.MPAIButtonRenderer.renderButtonGroup).toBeInstanceOf(Function);
    expect(window.MPAIButtonRenderer.updateButton).toBeInstanceOf(Function);
    expect(window.MPAIButtonRenderer.BUTTON_TYPES).toBeDefined();
    expect(window.MPAIButtonRenderer.BUTTON_SIZES).toBeDefined();
  });
  
  test('should create a button with default options', () => {
    const button = window.MPAIButtonRenderer.createButton({
      text: 'Test Button'
    });
    
    expect(button).toBeInstanceOf(HTMLButtonElement);
    expect(button.textContent).toBe('Test Button');
    expect(button.classList.contains('mpai-btn')).toBe(true);
    expect(button.classList.contains('mpai-btn-primary')).toBe(true);
    expect(button.classList.contains('mpai-btn-md')).toBe(true);
  });
  
  test('should create a button with custom options', () => {
    const clickHandler = jest.fn();
    
    const button = window.MPAIButtonRenderer.createButton({
      text: 'Custom Button',
      type: 'secondary',
      size: 'large',
      icon: 'dashicons-admin-users',
      onClick: clickHandler,
      ariaLabel: 'Custom Button Label',
      disabled: true,
      attributes: {
        'data-test': 'test-value'
      }
    });
    
    expect(button.textContent).toContain('Custom Button');
    expect(button.classList.contains('mpai-btn-secondary')).toBe(true);
    expect(button.classList.contains('mpai-btn-lg')).toBe(true);
    expect(button.querySelector('.dashicons')).toBeTruthy();
    expect(button.getAttribute('aria-label')).toBe('Custom Button Label');
    expect(button.disabled).toBe(true);
    expect(button.getAttribute('data-test')).toBe('test-value');
    
    // Test click handler
    button.click();
    expect(clickHandler).toHaveBeenCalled();
  });
  
  test('should create a button group with multiple buttons', () => {
    const button1 = window.MPAIButtonRenderer.createButton({ text: 'Button 1' });
    const button2 = window.MPAIButtonRenderer.createButton({ text: 'Button 2' });
    
    const buttonGroup = window.MPAIButtonRenderer.createButtonGroup([button1, button2], 'center');
    
    expect(buttonGroup).toBeInstanceOf(HTMLDivElement);
    expect(buttonGroup.classList.contains('mpai-btn-group')).toBe(true);
    expect(buttonGroup.style.justifyContent).toBe('center');
    expect(buttonGroup.children.length).toBe(2);
    expect(buttonGroup.children[0]).toBe(button1);
    expect(buttonGroup.children[1]).toBe(button2);
  });
  
  test('should render a button in a container', () => {
    const container = document.getElementById('test-container');
    
    const button = window.MPAIButtonRenderer.renderButton(container, {
      text: 'Rendered Button'
    });
    
    expect(container.children.length).toBe(1);
    expect(container.children[0]).toBe(button);
    expect(button.textContent).toBe('Rendered Button');
  });
  
  test('should render a button group in a container', () => {
    const container = document.getElementById('test-container');
    
    const buttonOptions = [
      { text: 'Button 1', type: 'primary' },
      { text: 'Button 2', type: 'secondary' }
    ];
    
    const buttonGroup = window.MPAIButtonRenderer.renderButtonGroup(container, buttonOptions, 'end');
    
    expect(container.children.length).toBe(1);
    expect(container.children[0]).toBe(buttonGroup);
    expect(buttonGroup.children.length).toBe(2);
    expect(buttonGroup.children[0].textContent).toBe('Button 1');
    expect(buttonGroup.children[1].textContent).toBe('Button 2');
    expect(buttonGroup.style.justifyContent).toBe('flex-end');
  });
  
  test('should update an existing button', () => {
    const clickHandler1 = jest.fn();
    const clickHandler2 = jest.fn();
    
    // Create initial button
    const button = window.MPAIButtonRenderer.createButton({
      text: 'Initial Text',
      type: 'primary',
      size: 'medium',
      onClick: clickHandler1
    });
    
    // Update the button
    const updatedButton = window.MPAIButtonRenderer.updateButton(button, {
      text: 'Updated Text',
      type: 'secondary',
      size: 'large',
      onClick: clickHandler2,
      disabled: true
    });
    
    expect(updatedButton.textContent).toBe('Updated Text');
    expect(updatedButton.classList.contains('mpai-btn-secondary')).toBe(true);
    expect(updatedButton.classList.contains('mpai-btn-lg')).toBe(true);
    expect(updatedButton.disabled).toBe(true);
    
    // Test that the old click handler is removed and new one is added
    updatedButton.click();
    expect(clickHandler1).not.toHaveBeenCalled();
    expect(clickHandler2).toHaveBeenCalled();
  });
  
  test('should handle buttons with icons only', () => {
    const button = window.MPAIButtonRenderer.createButton({
      icon: 'dashicons-admin-users'
    });
    
    expect(button.textContent.trim()).toBe('');
    expect(button.querySelector('.dashicons')).toBeTruthy();
    expect(button.querySelector('.dashicons').classList.contains('dashicons-admin-users')).toBe(true);
  });
  
  test('should handle buttons with icons and text', () => {
    const button = window.MPAIButtonRenderer.createButton({
      text: 'User Button',
      icon: 'dashicons-admin-users'
    });
    
    expect(button.textContent).toContain('User Button');
    expect(button.querySelector('.dashicons')).toBeTruthy();
    expect(button.querySelector('.dashicons').classList.contains('dashicons-admin-users')).toBe(true);
  });
  
  test('should add button styles to document', () => {
    // The styles should be added to the document head
    const styleElements = document.head.querySelectorAll('style');
    let buttonStyleFound = false;
    
    for (let i = 0; i < styleElements.length; i++) {
      if (styleElements[i].textContent.includes('.mpai-btn')) {
        buttonStyleFound = true;
        break;
      }
    }
    
    expect(buttonStyleFound).toBe(true);
  });
});