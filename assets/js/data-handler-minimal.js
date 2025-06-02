/**
 * MemberPress AI Assistant Data Handler (Minimal Version)
 * 
 * This is a minimal version of the data handler that only includes the essential functions
 * to fix the "detectVisualizationType is not defined" error.
 */

// Add a visible notification to the page
function addDebugNotification(message) {
    // Create notification element
    const notification = document.createElement('div');
    notification.style.position = 'fixed';
    notification.style.bottom = '10px';
    notification.style.right = '10px';
    notification.style.backgroundColor = '#4CAF50';
    notification.style.color = 'white';
    notification.style.padding = '10px';
    notification.style.borderRadius = '5px';
    notification.style.zIndex = '9999';
    notification.style.maxWidth = '300px';
    notification.textContent = message;
    
    // Add to document
    document.body.appendChild(notification);
    
    // Remove after 5 seconds
    setTimeout(() => {
        notification.remove();
    }, 5000);
    
    // Also log to console
    console.log(message);
}

(function() {
    'use strict';
    
    // Try different console logging methods
    console.log('Loading minimal data handler...');
    console.info('INFO: Data handler initializing');
    console.warn('WARNING: This is a test warning to check if console is working');
    
    // Add visible notification
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            addDebugNotification('Data handler loaded successfully!');
        });
    } else {
        addDebugNotification('Data handler loaded successfully!');
    }

    /**
     * Data visualization types
     */
    const VISUALIZATION_TYPES = {
        TABLE: 'table',
        LIST: 'list',
        KEY_VALUE: 'key-value',
        JSON: 'json',
        TREE: 'tree'
    };

    /**
     * Detect the most appropriate visualization type for the data
     * 
     * @param {Object|Array} data The data to analyze
     * @returns {string} The detected visualization type
     */
    function detectVisualizationType(data) {
        console.log('detectVisualizationType called with:', typeof data);
        
        if (Array.isArray(data)) {
            // Check if array contains objects with consistent keys (table)
            if (data.length > 0 && typeof data[0] === 'object' && data[0] !== null) {
                const firstItemKeys = Object.keys(data[0]);
                const allHaveSameKeys = data.every(item => {
                    if (typeof item !== 'object' || item === null) return false;
                    const itemKeys = Object.keys(item);
                    return itemKeys.length === firstItemKeys.length &&
                           firstItemKeys.every(key => itemKeys.includes(key));
                });

                if (allHaveSameKeys) {
                    return VISUALIZATION_TYPES.TABLE;
                }
            }

            // Simple array of primitives or mixed objects
            return VISUALIZATION_TYPES.LIST;
        } else if (typeof data === 'object' && data !== null) {
            // Check if object has simple key-value pairs
            const hasComplexValues = Object.values(data).some(value => 
                typeof value === 'object' && value !== null
            );

            if (!hasComplexValues) {
                return VISUALIZATION_TYPES.KEY_VALUE;
            }

            // Complex nested object
            return VISUALIZATION_TYPES.TREE;
        }

        // Fallback to JSON for other types
        return VISUALIZATION_TYPES.JSON;
    }

    /**
     * Process structured data and create appropriate visualization
     * 
     * @param {Object|Array} data The structured data to process
     * @param {Object} options Configuration options
     * @returns {HTMLElement} The visualization element
     */
    function processData(data, options = {}) {
        console.log('processData called');
        
        const {
            type = detectVisualizationType(data),
            title = '',
            sortable = true,
            filterable = true,
            expandable = true,
            maxItems = 20,
            maxDepth = 3
        } = options;

        console.log('Visualization type:', type);
        
        // Create a simple div as placeholder
        const container = document.createElement('div');
        container.textContent = `Data visualization: ${type}`;
        return container;
    }

    // Function to test if detectVisualizationType is accessible
    function testDetectVisualizationType() {
        try {
            // Test with different data types
            const arrayType = detectVisualizationType([1, 2, 3]);
            const objectType = detectVisualizationType({a: 1, b: 2});
            
            // Show visible notification with results
            addDebugNotification(`Function test: detectVisualizationType works! Array: ${arrayType}, Object: ${objectType}`);
            return true;
        } catch (error) {
            addDebugNotification(`ERROR: ${error.message}`);
            console.error('Error testing detectVisualizationType:', error);
            return false;
        }
    }

    // Export public API
    window.MPAIDataHandler = {
        processData,
        detectVisualizationType,
        VISUALIZATION_TYPES,
        testDetectVisualizationType // Add test function to public API
    };
    
    console.log('MPAIDataHandler initialized with functions:',
        Object.keys(window.MPAIDataHandler).join(', '));
    console.log('detectVisualizationType defined:', typeof detectVisualizationType);
    
    // Run test after a short delay to ensure DOM is ready
    setTimeout(() => {
        testDetectVisualizationType();
    }, 1000);
})();