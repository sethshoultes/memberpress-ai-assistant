/**
 * MemberPress AI Assistant Button Renderer
 * 
 * This module provides functions to dynamically generate and render buttons
 * for various actions in the chat interface.
 */

(function() {
    'use strict';

    /**
     * Button types and their corresponding styles
     */
    const BUTTON_TYPES = {
        primary: {
            className: 'mpai-btn-primary',
            backgroundColor: '#0073aa', // WordPress blue
            hoverColor: '#005d8c',
            textColor: '#ffffff'
        },
        secondary: {
            className: 'mpai-btn-secondary',
            backgroundColor: '#f1f1f1',
            hoverColor: '#e2e2e2',
            textColor: '#333333'
        },
        danger: {
            className: 'mpai-btn-danger',
            backgroundColor: '#dc3232', // WordPress red
            hoverColor: '#b32d2e',
            textColor: '#ffffff'
        }
    };

    /**
     * Button sizes and their corresponding styles
     */
    const BUTTON_SIZES = {
        small: {
            className: 'mpai-btn-sm',
            padding: '4px 8px',
            fontSize: '12px'
        },
        medium: {
            className: 'mpai-btn-md',
            padding: '8px 12px',
            fontSize: '14px'
        },
        large: {
            className: 'mpai-btn-lg',
            padding: '10px 16px',
            fontSize: '16px'
        }
    };

    /**
     * Create a button element with specified options
     * 
     * @param {Object} options Button configuration options
     * @param {string} options.text Button text content
     * @param {string} options.type Button type (primary, secondary, danger)
     * @param {string} options.size Button size (small, medium, large)
     * @param {string} options.icon Optional dashicon class name
     * @param {Function} options.onClick Click event handler
     * @param {string} options.ariaLabel Accessibility label
     * @param {boolean} options.disabled Whether the button is disabled
     * @param {Object} options.attributes Additional HTML attributes
     * @returns {HTMLButtonElement} The created button element
     */
    function createButton(options) {
        const {
            text = '',
            type = 'primary',
            size = 'medium',
            icon = null,
            onClick = null,
            ariaLabel = null,
            disabled = false,
            attributes = {}
        } = options;

        // Create button element
        const button = document.createElement('button');
        
        // Add button type class
        const buttonType = BUTTON_TYPES[type] || BUTTON_TYPES.primary;
        button.classList.add('mpai-btn', buttonType.className);
        
        // Add button size class
        const buttonSize = BUTTON_SIZES[size] || BUTTON_SIZES.medium;
        button.classList.add(buttonSize.className);
        
        // Set button content
        if (icon) {
            const iconSpan = document.createElement('span');
            iconSpan.className = `dashicons ${icon}`;
            button.appendChild(iconSpan);
            
            if (text) {
                // Add space between icon and text
                button.appendChild(document.createTextNode(' '));
            }
        }
        
        if (text) {
            const textSpan = document.createElement('span');
            textSpan.textContent = text;
            button.appendChild(textSpan);
        }
        
        // Set accessibility attributes
        if (ariaLabel) {
            button.setAttribute('aria-label', ariaLabel);
        }
        
        // Set disabled state
        if (disabled) {
            button.disabled = true;
            button.setAttribute('aria-disabled', 'true');
        }
        
        // Add click handler
        if (onClick && typeof onClick === 'function') {
            button.addEventListener('click', onClick);
        }
        
        // Add any additional attributes
        for (const [key, value] of Object.entries(attributes)) {
            button.setAttribute(key, value);
        }
        
        return button;
    }

    /**
     * Create a button group container for related buttons
     * 
     * @param {HTMLButtonElement[]} buttons Array of button elements to include in the group
     * @param {string} alignment Alignment of buttons (start, center, end, space-between)
     * @returns {HTMLDivElement} Button group container element
     */
    function createButtonGroup(buttons, alignment = 'start') {
        const container = document.createElement('div');
        container.className = 'mpai-btn-group';
        container.style.display = 'flex';
        container.style.gap = '8px';
        
        // Set alignment
        switch (alignment) {
            case 'center':
                container.style.justifyContent = 'center';
                break;
            case 'end':
                container.style.justifyContent = 'flex-end';
                break;
            case 'space-between':
                container.style.justifyContent = 'space-between';
                break;
            case 'start':
            default:
                container.style.justifyContent = 'flex-start';
                break;
        }
        
        // Add buttons to container
        buttons.forEach(button => {
            container.appendChild(button);
        });
        
        return container;
    }

    /**
     * Render a button in the specified container
     * 
     * @param {HTMLElement} container Container element to render the button in
     * @param {Object} options Button configuration options
     * @returns {HTMLButtonElement} The rendered button element
     */
    function renderButton(container, options) {
        const button = createButton(options);
        container.appendChild(button);
        return button;
    }

    /**
     * Render a button group in the specified container
     * 
     * @param {HTMLElement} container Container element to render the button group in
     * @param {Object[]} buttonsOptions Array of button configuration options
     * @param {string} alignment Alignment of buttons (start, center, end, space-between)
     * @returns {HTMLDivElement} The rendered button group element
     */
    function renderButtonGroup(container, buttonsOptions, alignment = 'start') {
        const buttons = buttonsOptions.map(options => createButton(options));
        const buttonGroup = createButtonGroup(buttons, alignment);
        container.appendChild(buttonGroup);
        return buttonGroup;
    }

    /**
     * Update an existing button's properties
     * 
     * @param {HTMLButtonElement} button Button element to update
     * @param {Object} options New button configuration options
     * @returns {HTMLButtonElement} The updated button element
     */
    function updateButton(button, options) {
        const {
            text,
            type,
            size,
            icon,
            onClick,
            ariaLabel,
            disabled,
            attributes
        } = options;
        
        // Update button type
        if (type) {
            // Remove existing type classes
            Object.values(BUTTON_TYPES).forEach(typeObj => {
                button.classList.remove(typeObj.className);
            });
            
            // Add new type class
            const buttonType = BUTTON_TYPES[type] || BUTTON_TYPES.primary;
            button.classList.add(buttonType.className);
        }
        
        // Update button size
        if (size) {
            // Remove existing size classes
            Object.values(BUTTON_SIZES).forEach(sizeObj => {
                button.classList.remove(sizeObj.className);
            });
            
            // Add new size class
            const buttonSize = BUTTON_SIZES[size] || BUTTON_SIZES.medium;
            button.classList.add(buttonSize.className);
        }
        
        // Update button content
        if (text !== undefined || icon !== undefined) {
            // Clear existing content
            button.innerHTML = '';
            
            // Add icon if specified
            if (icon) {
                const iconSpan = document.createElement('span');
                iconSpan.className = `dashicons ${icon}`;
                button.appendChild(iconSpan);
                
                if (text) {
                    // Add space between icon and text
                    button.appendChild(document.createTextNode(' '));
                }
            }
            
            // Add text if specified
            if (text) {
                const textSpan = document.createElement('span');
                textSpan.textContent = text;
                button.appendChild(textSpan);
            }
        }
        
        // Update accessibility attributes
        if (ariaLabel !== undefined) {
            button.setAttribute('aria-label', ariaLabel);
        }
        
        // Update disabled state
        if (disabled !== undefined) {
            button.disabled = disabled;
            button.setAttribute('aria-disabled', disabled.toString());
        }
        
        // Update click handler
        if (onClick && typeof onClick === 'function') {
            // Remove existing click handlers
            const newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);
            newButton.addEventListener('click', onClick);
            button = newButton;
        }
        
        // Update additional attributes
        if (attributes) {
            for (const [key, value] of Object.entries(attributes)) {
                button.setAttribute(key, value);
            }
        }
        
        return button;
    }

    // Add CSS styles for buttons
    function addButtonStyles() {
        const styleElement = document.createElement('style');
        styleElement.textContent = `
            /* Button Base Styles */
            .mpai-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-weight: 500;
                transition: background-color 0.2s ease, transform 0.1s ease;
                text-decoration: none;
                line-height: 1.4;
            }
            
            .mpai-btn:focus {
                outline: 2px solid rgba(0, 115, 170, 0.5);
                outline-offset: 2px;
            }
            
            .mpai-btn:active {
                transform: translateY(1px);
            }
            
            .mpai-btn:disabled {
                opacity: 0.6;
                cursor: not-allowed;
            }
            
            /* Button Types */
            .mpai-btn-primary {
                background-color: #0073aa;
                color: #ffffff;
            }
            
            .mpai-btn-primary:hover:not(:disabled) {
                background-color: #005d8c;
            }
            
            .mpai-btn-secondary {
                background-color: #f1f1f1;
                color: #333333;
            }
            
            .mpai-btn-secondary:hover:not(:disabled) {
                background-color: #e2e2e2;
            }
            
            .mpai-btn-danger {
                background-color: #dc3232;
                color: #ffffff;
            }
            
            .mpai-btn-danger:hover:not(:disabled) {
                background-color: #b32d2e;
            }
            
            /* Button Sizes */
            .mpai-btn-sm {
                padding: 4px 8px;
                font-size: 12px;
            }
            
            .mpai-btn-md {
                padding: 8px 12px;
                font-size: 14px;
            }
            
            .mpai-btn-lg {
                padding: 10px 16px;
                font-size: 16px;
            }
            
            /* Button Group */
            .mpai-btn-group {
                display: flex;
                gap: 8px;
            }
            
            /* Dark Mode Support */
            @media (prefers-color-scheme: dark) {
                .mpai-btn-secondary {
                    background-color: #3c434a;
                    color: #f0f0f1;
                }
                
                .mpai-btn-secondary:hover:not(:disabled) {
                    background-color: #4f5a65;
                }
            }
        `;
        document.head.appendChild(styleElement);
    }

    // Initialize the module
    function init() {
        // Add button styles to the document
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', addButtonStyles);
        } else {
            addButtonStyles();
        }
    }

    // Initialize on load
    init();

    // Export public API
    window.MPAIButtonRenderer = {
        createButton,
        createButtonGroup,
        renderButton,
        renderButtonGroup,
        updateButton,
        BUTTON_TYPES,
        BUTTON_SIZES
    };

})();