/**
 * MemberPress AI Assistant Data Handler
 * 
 * This module provides functions to process and display structured data (JSON, arrays, objects).
 * It supports different data formats (tables, lists, key-value pairs) and includes
 * sorting and filtering capabilities for tabular data.
 */

// Immediately log when the file loads
// Debug message removed - was appearing in admin interface

// Use a named IIFE to avoid scope issues
(function MPAIDataHandlerModule() {
    'use strict';
    
    // Log when the module initializes
    // Debug message removed - was appearing in admin interface

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
        const {
            type = detectVisualizationType(data),
            title = '',
            sortable = true,
            filterable = true,
            expandable = true,
            maxItems = 20,
            maxDepth = 3
        } = options;

        // Create container element
        const container = document.createElement('div');
        container.className = 'mpai-data-container';

        // Add title if provided
        if (title) {
            const titleElement = document.createElement('h3');
            titleElement.className = 'mpai-data-title';
            titleElement.textContent = title;
            container.appendChild(titleElement);
        }

        // Create visualization based on type
        let visualizationElement;
        switch (type) {
            case VISUALIZATION_TYPES.TABLE:
                visualizationElement = createTableVisualization(data, {
                    sortable,
                    filterable,
                    expandable,
                    maxItems
                });
                break;

            case VISUALIZATION_TYPES.LIST:
                visualizationElement = createListVisualization(data, {
                    expandable,
                    maxItems
                });
                break;

            case VISUALIZATION_TYPES.KEY_VALUE:
                visualizationElement = createKeyValueVisualization(data, {
                    expandable,
                    maxItems,
                    maxDepth
                });
                break;

            case VISUALIZATION_TYPES.JSON:
                visualizationElement = createJSONVisualization(data, {
                    expandable,
                    maxDepth
                });
                break;

            case VISUALIZATION_TYPES.TREE:
                visualizationElement = createTreeVisualization(data, {
                    expandable,
                    maxDepth
                });
                break;

            default:
                // Fallback to JSON visualization
                visualizationElement = createJSONVisualization(data, {
                    expandable,
                    maxDepth
                });
                break;
        }

        container.appendChild(visualizationElement);
        return container;
    }

    /**
     * Create a table visualization for array data
     *
     * @param {Array} data Array of objects to display as a table
     * @param {Object} options Configuration options
     * @returns {HTMLElement} The table element
     */
    function createTableVisualization(data, options = {}) {
        const {
            sortable = true,
            filterable = true,
            expandable = true,
            maxItems = 20
        } = options;

        if (!Array.isArray(data) || data.length === 0) {
            return createEmptyDataMessage('No data to display');
        }

        // Extract column headers from the first item
        const firstItem = data[0];
        const headers = Object.keys(firstItem);

        // Create container with controls
        const container = document.createElement('div');
        container.className = 'mpai-data-table-container';

        // Add filter input if enabled
        if (filterable && data.length > 1) {
            const filterContainer = document.createElement('div');
            filterContainer.className = 'mpai-data-filter-container';

            const filterInput = document.createElement('input');
            filterInput.type = 'text';
            filterInput.className = 'mpai-data-filter-input';
            filterInput.placeholder = 'Filter data...';
            
            filterInput.addEventListener('input', () => {
                const filterValue = filterInput.value.toLowerCase();
                filterTableRows(tableBody, data, filterValue);
                updateVisibleRowCount(container, tableBody, data.length);
            });

            filterContainer.appendChild(filterInput);
            container.appendChild(filterContainer);
        }

        // Create table element
        const table = document.createElement('table');
        table.className = 'mpai-data-table';

        // Create table header
        const thead = document.createElement('thead');
        const headerRow = document.createElement('tr');

        headers.forEach(header => {
            const th = document.createElement('th');
            th.textContent = formatHeaderText(header);
            
            // Add sort functionality if enabled
            if (sortable && data.length > 1) {
                th.className = 'mpai-data-sortable';
                th.addEventListener('click', () => {
                    sortTableByColumn(table, headers.indexOf(header));
                });
            }
            
            headerRow.appendChild(th);
        });

        thead.appendChild(headerRow);
        table.appendChild(thead);

        // Create table body
        const tableBody = document.createElement('tbody');
        
        // Determine if we need to limit the number of rows
        const isLargeDataset = data.length > maxItems && expandable;
        const initialData = isLargeDataset ? data.slice(0, maxItems) : data;

        // Add initial rows
        initialData.forEach(item => {
            const row = document.createElement('tr');
            
            headers.forEach(header => {
                const cell = document.createElement('td');
                cell.textContent = formatCellValue(item[header]);
                row.appendChild(cell);
            });
            
            tableBody.appendChild(row);
        });

        table.appendChild(tableBody);
        container.appendChild(table);

        // Add expand/collapse button for large datasets
        if (isLargeDataset) {
            const expandButton = document.createElement('button');
            expandButton.className = 'mpai-data-expand-button';
            expandButton.textContent = `Show all ${data.length} rows`;
            expandButton.setAttribute('aria-expanded', 'false');
            
            expandButton.addEventListener('click', () => {
                const isExpanded = expandButton.getAttribute('aria-expanded') === 'true';
                
                if (isExpanded) {
                    // Collapse: show only initial rows
                    while (tableBody.children.length > maxItems) {
                        tableBody.removeChild(tableBody.lastChild);
                    }
                    expandButton.textContent = `Show all ${data.length} rows`;
                    expandButton.setAttribute('aria-expanded', 'false');
                } else {
                    // Expand: show all rows
                    const remainingData = data.slice(maxItems);
                    remainingData.forEach(item => {
                        const row = document.createElement('tr');
                        
                        headers.forEach(header => {
                            const cell = document.createElement('td');
                            cell.textContent = formatCellValue(item[header]);
                            row.appendChild(cell);
                        });
                        
                        tableBody.appendChild(row);
                    });
                    expandButton.textContent = 'Show fewer rows';
                    expandButton.setAttribute('aria-expanded', 'true');
                }
            });
            
            container.appendChild(expandButton);
        }

        // Add row count indicator
        const rowCountElement = document.createElement('div');
        rowCountElement.className = 'mpai-data-row-count';
        rowCountElement.textContent = `Showing ${Math.min(initialData.length, data.length)} of ${data.length} rows`;
        container.appendChild(rowCountElement);

        return container;
    }

    /**
     * Filter table rows based on input value
     *
     * @param {HTMLElement} tableBody The table body element
     * @param {Array} data The original data array
     * @param {string} filterValue The filter value
     */
    function filterTableRows(tableBody, data, filterValue) {
        // Clear existing rows
        tableBody.innerHTML = '';
        
        // Filter data
        const filteredData = filterValue ? 
            data.filter(item => 
                Object.values(item).some(value => 
                    String(value).toLowerCase().includes(filterValue)
                )
            ) : data;
        
        // Add filtered rows
        filteredData.forEach(item => {
            const row = document.createElement('tr');
            
            Object.values(item).forEach(value => {
                const cell = document.createElement('td');
                cell.textContent = formatCellValue(value);
                row.appendChild(cell);
            });
            
            tableBody.appendChild(row);
        });
    }

    /**
     * Update the visible row count indicator
     * 
     * @param {HTMLElement} container The table container
     * @param {HTMLElement} tableBody The table body element
     * @param {number} totalRows The total number of rows in the dataset
     */
    function updateVisibleRowCount(container, tableBody, totalRows) {
        const rowCountElement = container.querySelector('.mpai-data-row-count');
        if (rowCountElement) {
            rowCountElement.textContent = `Showing ${tableBody.children.length} of ${totalRows} rows`;
        }
    }

    /**
     * Sort table by a specific column
     * 
     * @param {HTMLElement} table The table element
     * @param {number} columnIndex The index of the column to sort by
     */
    function sortTableByColumn(table, columnIndex) {
        const thead = table.querySelector('thead');
        const tbody = table.querySelector('tbody');
        const thList = thead.querySelectorAll('th');
        const th = thList[columnIndex];
        
        // Determine sort direction
        const currentDirection = th.getAttribute('data-sort-direction') || 'none';
        const newDirection = currentDirection === 'asc' ? 'desc' : 'asc';
        
        // Update sort direction attribute on all headers
        thList.forEach(header => {
            header.removeAttribute('data-sort-direction');
            header.classList.remove('mpai-data-sorted-asc', 'mpai-data-sorted-desc');
        });
        
        th.setAttribute('data-sort-direction', newDirection);
        th.classList.add(`mpai-data-sorted-${newDirection}`);
        
        // Get rows and convert to array for sorting
        const rows = Array.from(tbody.querySelectorAll('tr'));
        
        // Sort rows
        rows.sort((rowA, rowB) => {
            const cellA = rowA.querySelectorAll('td')[columnIndex].textContent;
            const cellB = rowB.querySelectorAll('td')[columnIndex].textContent;
            
            // Try to sort numerically if both values are numbers
            if (!isNaN(cellA) && !isNaN(cellB)) {
                return newDirection === 'asc' ? 
                    parseFloat(cellA) - parseFloat(cellB) : 
                    parseFloat(cellB) - parseFloat(cellA);
            }
            
            // Otherwise sort alphabetically
            return newDirection === 'asc' ? 
                cellA.localeCompare(cellB) : 
                cellB.localeCompare(cellA);
        });
        
        // Remove existing rows
        while (tbody.firstChild) {
            tbody.removeChild(tbody.firstChild);
        }
        
        // Add sorted rows
        rows.forEach(row => {
            tbody.appendChild(row);
        });
    }

    /**
     * Create a list visualization for array data
     * 
     * @param {Array} data Array to display as a list
     * @param {Object} options Configuration options
     * @returns {HTMLElement} The list element
     */
    function createListVisualization(data, options = {}) {
        const {
            expandable = true,
            maxItems = 20
        } = options;

        if (!Array.isArray(data) || data.length === 0) {
            return createEmptyDataMessage('No data to display');
        }

        const container = document.createElement('div');
        container.className = 'mpai-data-list-container';

        // Create list element
        const list = document.createElement('ul');
        list.className = 'mpai-data-list';

        // Determine if we need to limit the number of items
        const isLargeList = data.length > maxItems && expandable;
        const initialItems = isLargeList ? data.slice(0, maxItems) : data;

        // Add initial items
        initialItems.forEach(item => {
            const listItem = document.createElement('li');
            listItem.className = 'mpai-data-list-item';
            
            if (typeof item === 'object' && item !== null) {
                // For objects, create a nested visualization
                const nestedElement = processData(item, {
                    maxDepth: 1,
                    expandable: false
                });
                listItem.appendChild(nestedElement);
            } else {
                // For primitives, just display the value
                listItem.textContent = formatCellValue(item);
            }
            
            list.appendChild(listItem);
        });

        container.appendChild(list);

        // Add expand/collapse button for large lists
        if (isLargeList) {
            const expandButton = document.createElement('button');
            expandButton.className = 'mpai-data-expand-button';
            expandButton.textContent = `Show all ${data.length} items`;
            expandButton.setAttribute('aria-expanded', 'false');
            
            expandButton.addEventListener('click', () => {
                const isExpanded = expandButton.getAttribute('aria-expanded') === 'true';
                
                if (isExpanded) {
                    // Collapse: show only initial items
                    while (list.children.length > maxItems) {
                        list.removeChild(list.lastChild);
                    }
                    expandButton.textContent = `Show all ${data.length} items`;
                    expandButton.setAttribute('aria-expanded', 'false');
                } else {
                    // Expand: show all items
                    const remainingItems = data.slice(maxItems);
                    remainingItems.forEach(item => {
                        const listItem = document.createElement('li');
                        listItem.className = 'mpai-data-list-item';
                        
                        if (typeof item === 'object' && item !== null) {
                            // For objects, create a nested visualization
                            const nestedElement = processData(item, {
                                maxDepth: 1,
                                expandable: false
                            });
                            listItem.appendChild(nestedElement);
                        } else {
                            // For primitives, just display the value
                            listItem.textContent = formatCellValue(item);
                        }
                        
                        list.appendChild(listItem);
                    });
                    expandButton.textContent = 'Show fewer items';
                    expandButton.setAttribute('aria-expanded', 'true');
                }
            });
            
            container.appendChild(expandButton);
        }

        return container;
    }
/**
     * Create a key-value visualization for object data
     * 
     * @param {Object} data Object to display as key-value pairs
     * @param {Object} options Configuration options
     * @returns {HTMLElement} The key-value element
     */
    function createKeyValueVisualization(data, options = {}) {
        const {
            expandable = true,
            maxItems = 20,
            maxDepth = 3
        } = options;

        if (typeof data !== 'object' || data === null || Object.keys(data).length === 0) {
            return createEmptyDataMessage('No data to display');
        }

        const container = document.createElement('div');
        container.className = 'mpai-data-key-value-container';

        // Create definition list
        const dl = document.createElement('dl');
        dl.className = 'mpai-data-key-value';

        // Get all keys
        const keys = Object.keys(data);
        
        // Determine if we need to limit the number of items
        const isLargeObject = keys.length > maxItems && expandable;
        const initialKeys = isLargeObject ? keys.slice(0, maxItems) : keys;

        // Add initial key-value pairs
        initialKeys.forEach(key => {
            const dt = document.createElement('dt');
            dt.className = 'mpai-data-key';
            dt.textContent = formatHeaderText(key);
            dl.appendChild(dt);

            const dd = document.createElement('dd');
            dd.className = 'mpai-data-value';
            
            const value = data[key];
            if (typeof value === 'object' && value !== null && maxDepth > 1) {
                // For objects, create a nested visualization
                const nestedElement = processData(value, {
                    maxDepth: maxDepth - 1,
                    expandable: false
                });
                dd.appendChild(nestedElement);
            } else {
                // For primitives, just display the value
                dd.textContent = formatCellValue(value);
            }
            
            dl.appendChild(dd);
        });

        container.appendChild(dl);

        // Add expand/collapse button for large objects
        if (isLargeObject) {
            const expandButton = document.createElement('button');
            expandButton.className = 'mpai-data-expand-button';
            expandButton.textContent = `Show all ${keys.length} properties`;
            expandButton.setAttribute('aria-expanded', 'false');
            
            expandButton.addEventListener('click', () => {
                const isExpanded = expandButton.getAttribute('aria-expanded') === 'true';
                
                if (isExpanded) {
                    // Collapse: show only initial items
                    while (dl.children.length > maxItems * 2) {
                        dl.removeChild(dl.lastChild); // Remove dd
                        dl.removeChild(dl.lastChild); // Remove dt
                    }
                    expandButton.textContent = `Show all ${keys.length} properties`;
                    expandButton.setAttribute('aria-expanded', 'false');
                } else {
                    // Expand: show all items
                    const remainingKeys = keys.slice(maxItems);
                    remainingKeys.forEach(key => {
                        const dt = document.createElement('dt');
                        dt.className = 'mpai-data-key';
                        dt.textContent = formatHeaderText(key);
                        dl.appendChild(dt);

                        const dd = document.createElement('dd');
                        dd.className = 'mpai-data-value';
                        
                        const value = data[key];
                        if (typeof value === 'object' && value !== null && maxDepth > 1) {
                            // For objects, create a nested visualization
                            const nestedElement = processData(value, {
                                maxDepth: maxDepth - 1,
                                expandable: false
                            });
                            dd.appendChild(nestedElement);
                        } else {
                            // For primitives, just display the value
                            dd.textContent = formatCellValue(value);
                        }
                        
                        dl.appendChild(dd);
                    });
                    expandButton.textContent = 'Show fewer properties';
                    expandButton.setAttribute('aria-expanded', 'true');
                }
            });
            
            container.appendChild(expandButton);
        }

        return container;
    }

    /**
     * Create a JSON visualization for any data
     * 
     * @param {Object|Array} data Data to display as formatted JSON
     * @param {Object} options Configuration options
     * @returns {HTMLElement} The JSON element
     */
    function createJSONVisualization(data, options = {}) {
        const {
            expandable = true,
            maxDepth = 3
        } = options;

        const container = document.createElement('div');
        container.className = 'mpai-data-json-container';

        // Create pre element for JSON
        const pre = document.createElement('pre');
        pre.className = 'mpai-data-json';

        // Format JSON with indentation
        const formattedJSON = JSON.stringify(data, null, 2);
        pre.textContent = formattedJSON;

        container.appendChild(pre);

        // Add copy button
        const copyButton = document.createElement('button');
        copyButton.className = 'mpai-data-copy-button';
        copyButton.innerHTML = '<span class="dashicons dashicons-clipboard"></span>';
        copyButton.setAttribute('aria-label', 'Copy JSON');
        
        copyButton.addEventListener('click', () => {
            navigator.clipboard.writeText(formattedJSON).then(() => {
                copyButton.innerHTML = '<span class="dashicons dashicons-yes"></span>';
                setTimeout(() => {
                    copyButton.innerHTML = '<span class="dashicons dashicons-clipboard"></span>';
                }, 2000);
            }).catch(err => {
                console.error('Failed to copy JSON:', err);
                copyButton.innerHTML = '<span class="dashicons dashicons-no"></span>';
                setTimeout(() => {
                    copyButton.innerHTML = '<span class="dashicons dashicons-clipboard"></span>';
                }, 2000);
            });
        });
        
        container.appendChild(copyButton);

        return container;
    }

    /**
     * Create a tree visualization for nested object data
     * 
     * @param {Object} data Nested object to display as a tree
     * @param {Object} options Configuration options
     * @returns {HTMLElement} The tree element
     */
    function createTreeVisualization(data, options = {}) {
        const {
            expandable = true,
            maxDepth = 3
        } = options;

        if (typeof data !== 'object' || data === null) {
            return createEmptyDataMessage('No data to display');
        }

        const container = document.createElement('div');
        container.className = 'mpai-data-tree-container';

        // Create tree element
        const tree = document.createElement('div');
        tree.className = 'mpai-data-tree';

        // Build tree recursively
        buildTreeNode(tree, data, 0, maxDepth, expandable);

        container.appendChild(tree);
        return container;
    }
/**
     * Build a tree node recursively
     * 
     * @param {HTMLElement} parent The parent element to append to
     * @param {Object|Array} data The data to visualize
     * @param {number} depth Current depth level
     * @param {number} maxDepth Maximum depth to visualize
     * @param {boolean} expandable Whether nodes are expandable
     */
    function buildTreeNode(parent, data, depth, maxDepth, expandable) {
        if (depth >= maxDepth) {
            // Max depth reached, show placeholder
            const truncatedNode = document.createElement('div');
            truncatedNode.className = 'mpai-data-tree-truncated';
            truncatedNode.textContent = Array.isArray(data) ? 
                `Array(${data.length})` : 
                `Object(${Object.keys(data).length} properties)`;
            parent.appendChild(truncatedNode);
            return;
        }

        if (Array.isArray(data)) {
            // Array node
            data.forEach((item, index) => {
                const nodeContainer = document.createElement('div');
                nodeContainer.className = 'mpai-data-tree-node';
                
                const nodeLabel = document.createElement('div');
                nodeLabel.className = 'mpai-data-tree-label';
                
                if (typeof item === 'object' && item !== null) {
                    // Expandable object/array
                    const expandToggle = document.createElement('span');
                    expandToggle.className = 'mpai-data-tree-toggle';
                    expandToggle.textContent = '▶';
                    nodeLabel.appendChild(expandToggle);
                    
                    const labelText = document.createElement('span');
                    labelText.textContent = `[${index}]: ${Array.isArray(item) ? 
                        `Array(${item.length})` : 
                        `Object(${Object.keys(item).length} properties)`}`;
                    nodeLabel.appendChild(labelText);
                    
                    const childContainer = document.createElement('div');
                    childContainer.className = 'mpai-data-tree-children';
                    childContainer.style.display = 'none';
                    
                    // Add click handler for expansion
                    nodeLabel.addEventListener('click', () => {
                        const isExpanded = expandToggle.textContent === '▼';
                        expandToggle.textContent = isExpanded ? '▶' : '▼';
                        childContainer.style.display = isExpanded ? 'none' : 'block';
                        
                        // Lazy-load children if not already loaded
                        if (childContainer.children.length === 0) {
                            buildTreeNode(childContainer, item, depth + 1, maxDepth, expandable);
                        }
                    });
                    
                    nodeContainer.appendChild(nodeLabel);
                    nodeContainer.appendChild(childContainer);
                } else {
                    // Primitive value
                    nodeLabel.textContent = `[${index}]: ${formatCellValue(item)}`;
                    nodeContainer.appendChild(nodeLabel);
                }
                
                parent.appendChild(nodeContainer);
            });
        } else if (typeof data === 'object' && data !== null) {
            // Object node
            Object.keys(data).forEach(key => {
                const nodeContainer = document.createElement('div');
                nodeContainer.className = 'mpai-data-tree-node';
                
                const nodeLabel = document.createElement('div');
                nodeLabel.className = 'mpai-data-tree-label';
                
                const value = data[key];
                if (typeof value === 'object' && value !== null) {
                    // Expandable object/array
                    const expandToggle = document.createElement('span');
                    expandToggle.className = 'mpai-data-tree-toggle';
                    expandToggle.textContent = '▶';
                    nodeLabel.appendChild(expandToggle);
                    
                    const labelText = document.createElement('span');
                    labelText.textContent = `${formatHeaderText(key)}: ${Array.isArray(value) ? 
                        `Array(${value.length})` : 
                        `Object(${Object.keys(value).length} properties)`}`;
                    nodeLabel.appendChild(labelText);
                    
                    const childContainer = document.createElement('div');
                    childContainer.className = 'mpai-data-tree-children';
                    childContainer.style.display = 'none';
                    
                    // Add click handler for expansion
                    nodeLabel.addEventListener('click', () => {
                        const isExpanded = expandToggle.textContent === '▼';
                        expandToggle.textContent = isExpanded ? '▶' : '▼';
                        childContainer.style.display = isExpanded ? 'none' : 'block';
                        
                        // Lazy-load children if not already loaded
                        if (childContainer.children.length === 0) {
                            buildTreeNode(childContainer, value, depth + 1, maxDepth, expandable);
                        }
                    });
                    
                    nodeContainer.appendChild(nodeLabel);
                    nodeContainer.appendChild(childContainer);
                } else {
                    // Primitive value
                    nodeLabel.textContent = `${formatHeaderText(key)}: ${formatCellValue(value)}`;
                    nodeContainer.appendChild(nodeLabel);
                }
                
                parent.appendChild(nodeContainer);
            });
        }
    }

    /**
     * Create an empty data message element
     * 
     * @param {string} message The message to display
     * @returns {HTMLElement} The message element
     */
    function createEmptyDataMessage(message) {
        const container = document.createElement('div');
        container.className = 'mpai-data-empty';
        container.textContent = message;
        return container;
    }

    /**
     * Format a header text for display
     * 
     * @param {string} header The header text to format
     * @returns {string} The formatted header text
     */
    function formatHeaderText(header) {
        // Convert camelCase or snake_case to Title Case with spaces
        return header
            // Insert space before capital letters
            .replace(/([A-Z])/g, ' $1')
            // Replace underscores with spaces
            .replace(/_/g, ' ')
            // Capitalize first letter of each word
            .replace(/\b\w/g, c => c.toUpperCase())
            // Trim extra spaces
            .trim();
    }

    /**
     * Format a cell value for display
     * 
     * @param {any} value The value to format
     * @returns {string} The formatted value
     */
    function formatCellValue(value) {
        if (value === null) {
            return 'null';
        } else if (value === undefined) {
            return 'undefined';
        } else if (typeof value === 'boolean') {
            return value ? 'true' : 'false';
        } else if (typeof value === 'object') {
            return JSON.stringify(value);
        } else {
            return String(value);
        }
    }

    /**
     * Add CSS styles for data visualizations
     */
    function addDataStyles() {
        const styleElement = document.createElement('style');
        styleElement.textContent = `
            /* Data Container Styles */
            .mpai-data-container {
                margin: 1em 0;
                font-size: 14px;
                line-height: 1.4;
            }
            
            .mpai-data-title {
                margin: 0 0 0.5em;
                font-size: 16px;
                font-weight: 600;
            }
            
            /* Table Visualization */
            .mpai-data-table-container {
                overflow-x: auto;
                margin-bottom: 1em;
            }
            
            .mpai-data-filter-container {
                margin-bottom: 0.5em;
            }
            
            .mpai-data-filter-input {
                width: 100%;
                padding: 6px 10px;
                border: 1px solid #ddd;
                border-radius: 4px;
                font-size: 14px;
            }
            
            .mpai-data-table {
                width: 100%;
                border-collapse: collapse;
                border-spacing: 0;
            }
            
            .mpai-data-table th,
            .mpai-data-table td {
                padding: 8px 12px;
                text-align: left;
                border: 1px solid #ddd;
            }
            
            .mpai-data-table th {
                background-color: #f5f5f5;
                font-weight: 600;
                position: relative;
            }
            
            .mpai-data-table tr:nth-child(even) {
                background-color: #f9f9f9;
            }
            
            .mpai-data-sortable {
                cursor: pointer;
                user-select: none;
            }
            
            .mpai-data-sortable:hover {
                background-color: #e9e9e9;
            }
            
            .mpai-data-sortable::after {
                content: '⇅';
                display: inline-block;
                margin-left: 5px;
                opacity: 0.5;
            }
            
            .mpai-data-sorted-asc::after {
                content: '↑';
                opacity: 1;
            }
            
            .mpai-data-sorted-desc::after {
                content: '↓';
                opacity: 1;
            }
            
            .mpai-data-row-count {
                margin-top: 0.5em;
                font-size: 12px;
                color: #666;
                text-align: right;
            }
            
            /* List Visualization */
            .mpai-data-list {
                margin: 0;
                padding: 0 0 0 1.5em;
            }
            
            .mpai-data-list-item {
                margin: 0.25em 0;
                padding: 0.25em 0;
            }
            
            /* Key-Value Visualization */
            .mpai-data-key-value {
                margin: 0;
                display: grid;
                grid-template-columns: minmax(150px, 30%) 1fr;
                gap: 0.5em;
                align-items: start;
            }
            
            .mpai-data-key {
                font-weight: 600;
                padding: 0.25em 0;
            }
            
            .mpai-data-value {
                margin: 0;
                padding: 0.25em 0;
            }
            
            /* JSON Visualization */
            .mpai-data-json-container {
                position: relative;
            }
            
            .mpai-data-json {
                margin: 0;
                padding: 1em;
                background-color: #f5f5f5;
                border-radius: 4px;
                overflow-x: auto;
                font-family: monospace;
                font-size: 13px;
                line-height: 1.5;
                white-space: pre;
            }
            
            .mpai-data-copy-button {
                position: absolute;
                top: 0.5em;
                right: 0.5em;
                background: transparent;
                border: none;
                cursor: pointer;
                padding: 0.25em;
                color: #555;
            }
            
            .mpai-data-copy-button:hover {
                color: #000;
            }
            
            /* Tree Visualization */
            .mpai-data-tree {
                margin: 0;
                padding: 0;
                font-family: monospace;
                font-size: 13px;
            }
            
            .mpai-data-tree-node {
                margin: 0.25em 0;
                padding: 0;
            }
            
            .mpai-data-tree-label {
                cursor: pointer;
                padding: 0.25em 0;
            }
            
            .mpai-data-tree-toggle {
                display: inline-block;
                width: 1em;
                margin-right: 0.5em;
                text-align: center;
                font-size: 10px;
            }
            
            .mpai-data-tree-children {
                margin-left: 1.5em;
                border-left: 1px dotted #ccc;
                padding-left: 0.5em;
            }
            
            .mpai-data-tree-truncated {
                color: #999;
                font-style: italic;
                margin-left: 1.5em;
            }
            
            /* Common Elements */
            .mpai-data-expand-button {
                display: block;
                margin: 0.5em 0;
                padding: 0.25em 0.5em;
                background-color: transparent;
                border: 1px solid #ddd;
                border-radius: 4px;
                color: #0073aa;
                cursor: pointer;
                font-size: 12px;
            }
            
            .mpai-data-expand-button:hover {
                background-color: #f5f5f5;
                color: #005d8c;
            }
            
            .mpai-data-empty {
                padding: 1em;
                text-align: center;
                color: #666;
                font-style: italic;
                background-color: #f9f9f9;
                border-radius: 4px;
            }
            
            /* Dark Mode Support */
            @media (prefers-color-scheme: dark) {
                .mpai-data-filter-input {
                    background-color: #3c434a;
                    border-color: #4f5a65;
                    color: #f0f0f1;
                }
                
                .mpai-data-table th,
                .mpai-data-table td {
                    border-color: #4f5a65;
                }
                
                .mpai-data-table th {
                    background-color: #2c3338;
                }
                
                .mpai-data-table tr:nth-child(even) {
                    background-color: #32373c;
                }
                
                .mpai-data-sortable:hover {
                    background-color: #3c434a;
                }
                
                .mpai-data-row-count {
                    color: #bbb;
                }
                
                .mpai-data-json {
                    background-color: #2c3338;
                    color: #f0f0f1;
                }
                
                .mpai-data-copy-button {
                    color: #bbb;
                }
                
                .mpai-data-copy-button:hover {
                    color: #fff;
                }
                
                .mpai-data-tree-children {
                    border-left-color: #4f5a65;
                }
                
                .mpai-data-expand-button {
                    border-color: #4f5a65;
                    color: #3db2ff;
                }
                
                .mpai-data-expand-button:hover {
                    background-color: #3c434a;
                    color: #5ac8ff;
                }
                
                .mpai-data-empty {
                    background-color: #2c3338;
                    color: #bbb;
                }
            }
        `;
        document.head.appendChild(styleElement);
    }

    // Initialize the module
    function init() {
        // Add data styles to the document
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', addDataStyles);
        } else {
            addDataStyles();
        }
    }

    // Initialize on load
    init();

    // Export public API
    window.MPAIDataHandler = {
        processData,
        detectVisualizationType,
        createTableVisualization,
        createListVisualization,
        createKeyValueVisualization,
        createJSONVisualization,
        createTreeVisualization,
        VISUALIZATION_TYPES
    };
    
    // Add debugging to help identify the issue
    // Debug messages removed - were appearing in admin interface

})();