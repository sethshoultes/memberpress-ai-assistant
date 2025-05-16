/**
 * MemberPress AI Assistant XML Processor
 * 
 * This module provides functions to parse and process XML content from agent responses.
 * It handles different XML tags and attributes, extracts structured data from XML content,
 * and supports nested XML elements.
 */

(function() {
    'use strict';

    /**
     * XML tag types and their corresponding handlers
     */
    const XML_TAG_TYPES = {
        // Content formatting tags
        HEADING: 'heading',
        PARAGRAPH: 'p',
        BOLD: 'b',
        ITALIC: 'i',
        CODE: 'code',
        LINK: 'a',
        LIST: 'list',
        LIST_ITEM: 'item',
        BLOCKQUOTE: 'blockquote',
        
        // Structured data tags
        TABLE: 'table',
        ROW: 'row',
        CELL: 'cell',
        DATA: 'data',
        
        // UI component tags
        BUTTON: 'button',
        FORM: 'form',
        INPUT: 'input',
        
        // Special tags
        ERROR: 'error',
        WARNING: 'warning',
        SUCCESS: 'success',
        INFO: 'info'
    };

    /**
     * Parse XML content into a DOM structure
     * 
     * @param {string} xmlContent The XML content to parse
     * @returns {Document} The parsed XML document
     */
    function parseXML(xmlContent) {
        try {
            // Create a valid XML document by wrapping content in a root element
            const wrappedContent = `<root>${xmlContent}</root>`;
            const parser = new DOMParser();
            return parser.parseFromString(wrappedContent, 'text/xml');
        } catch (error) {
            console.error('Error parsing XML:', error);
            return null;
        }
    }

    /**
     * Check if a string contains XML content
     * 
     * @param {string} content The content to check
     * @returns {boolean} Whether the content contains XML
     */
    function containsXML(content) {
        // Simple check for XML-like content
        const xmlPattern = /<\/?[a-z][a-z0-9]*(?:\s+[a-z0-9-]+(?:=(?:"[^"]*"|'[^']*'|[^'">\s]+))?)*\s*\/?>/i;
        return xmlPattern.test(content);
    }

    /**
     * Extract XML tags from content
     * 
     * @param {string} content The content to extract tags from
     * @returns {Array} Array of extracted tag objects with name, attributes, and content
     */
    function extractTags(content) {
        if (!containsXML(content)) {
            return [];
        }

        const xmlDoc = parseXML(content);
        if (!xmlDoc) {
            return [];
        }

        return extractTagsFromNode(xmlDoc.documentElement);
    }

    /**
     * Recursively extract tags from a DOM node
     * 
     * @param {Node} node The DOM node to extract tags from
     * @returns {Array} Array of extracted tag objects
     */
    function extractTagsFromNode(node) {
        const tags = [];

        // Process child nodes
        for (let i = 0; i < node.childNodes.length; i++) {
            const childNode = node.childNodes[i];

            if (childNode.nodeType === Node.ELEMENT_NODE) {
                // Element node
                const tag = {
                    name: childNode.nodeName.toLowerCase(),
                    attributes: {},
                    content: childNode.textContent,
                    children: extractTagsFromNode(childNode)
                };

                // Extract attributes
                for (let j = 0; j < childNode.attributes.length; j++) {
                    const attr = childNode.attributes[j];
                    tag.attributes[attr.name] = attr.value;
                }

                tags.push(tag);
            }
        }

        return tags;
    }

    /**
     * Process XML content and convert it to HTML
     * 
     * @param {string} xmlContent The XML content to process
     * @returns {string} The processed HTML content
     */
    function processXMLToHTML(xmlContent) {
        if (!containsXML(xmlContent)) {
            return xmlContent;
        }

        const xmlDoc = parseXML(xmlContent);
        if (!xmlDoc) {
            return xmlContent;
        }

        return processNodeToHTML(xmlDoc.documentElement);
    }

    /**
     * Recursively process a DOM node to HTML
     * 
     * @param {Node} node The DOM node to process
     * @returns {string} The processed HTML content
     */
    function processNodeToHTML(node) {
        let html = '';

        // Process child nodes
        for (let i = 0; i < node.childNodes.length; i++) {
            const childNode = node.childNodes[i];

            if (childNode.nodeType === Node.TEXT_NODE) {
                // Text node
                html += childNode.textContent;
            } else if (childNode.nodeType === Node.ELEMENT_NODE) {
                // Element node
                const tagName = childNode.nodeName.toLowerCase();
                html += processTagToHTML(childNode, tagName);
            }
        }

        return html;
    }

    /**
     * Process a specific XML tag to HTML
     * 
     * @param {Node} node The DOM node representing the tag
     * @param {string} tagName The name of the tag
     * @returns {string} The processed HTML for the tag
     */
    function processTagToHTML(node, tagName) {
        // Get attributes as an object
        const attributes = {};
        for (let i = 0; i < node.attributes.length; i++) {
            const attr = node.attributes[i];
            attributes[attr.name] = attr.value;
        }

        // Process inner content
        const innerContent = processNodeToHTML(node);

        // Process based on tag type
        switch (tagName) {
            case XML_TAG_TYPES.HEADING:
                const level = attributes.level || '2';
                return `<h${level} class="mpai-heading">${innerContent}</h${level}>`;

            case XML_TAG_TYPES.PARAGRAPH:
                return `<p class="mpai-paragraph">${innerContent}</p>`;

            case XML_TAG_TYPES.BOLD:
                return `<strong>${innerContent}</strong>`;

            case XML_TAG_TYPES.ITALIC:
                return `<em>${innerContent}</em>`;

            case XML_TAG_TYPES.CODE:
                const language = attributes.language || '';
                if (attributes.inline === 'true') {
                    return `<code class="mpai-code-inline">${innerContent}</code>`;
                } else {
                    // Use the content preview module for code blocks if available
                    if (window.MPAIContentPreview && window.MPAIContentPreview.createCodePreview) {
                        const codeElement = window.MPAIContentPreview.createCodePreview(innerContent, {
                            language: language,
                            lineNumbers: true
                        });
                        
                        // Convert DOM element to HTML string
                        const tempContainer = document.createElement('div');
                        tempContainer.appendChild(codeElement);
                        return tempContainer.innerHTML;
                    } else {
                        return `<pre class="mpai-code-block" data-language="${language}"><code>${innerContent}</code></pre>`;
                    }
                }

            case XML_TAG_TYPES.LINK:
                const href = attributes.href || '#';
                const target = attributes.target || '_blank';
                return `<a href="${href}" target="${target}" class="mpai-link">${innerContent}</a>`;

            case XML_TAG_TYPES.LIST:
                const listType = attributes.type || 'ul';
                return `<${listType} class="mpai-list">${innerContent}</${listType}>`;

            case XML_TAG_TYPES.LIST_ITEM:
                return `<li class="mpai-list-item">${innerContent}</li>`;

            case XML_TAG_TYPES.BLOCKQUOTE:
                return `<blockquote class="mpai-blockquote">${innerContent}</blockquote>`;

            case XML_TAG_TYPES.TABLE:
                // Use the content preview module for tables if available
                if (window.MPAIContentPreview && window.MPAIContentPreview.createTablePreview) {
                    const tableData = extractTableData(node);
                    const tableElement = window.MPAIContentPreview.createTablePreview(tableData.data, {
                        headers: tableData.headers,
                        striped: true,
                        bordered: true
                    });
                    
                    // Convert DOM element to HTML string
                    const tempContainer = document.createElement('div');
                    tempContainer.appendChild(tableElement);
                    return tempContainer.innerHTML;
                } else {
                    return `<table class="mpai-table">${innerContent}</table>`;
                }

            case XML_TAG_TYPES.ROW:
                return `<tr class="mpai-table-row">${innerContent}</tr>`;

            case XML_TAG_TYPES.CELL:
                const isHeader = attributes.header === 'true';
                const cellTag = isHeader ? 'th' : 'td';
                return `<${cellTag} class="mpai-table-cell">${innerContent}</${cellTag}>`;

            case XML_TAG_TYPES.DATA:
                // Process structured data using data-handler.js if available
                if (window.MPAIDataHandler && window.MPAIDataHandler.processData) {
                    try {
                        const dataObj = JSON.parse(innerContent);
                        const dataElement = window.MPAIDataHandler.processData(dataObj, attributes);
                        
                        // Convert DOM element to HTML string
                        const tempContainer = document.createElement('div');
                        tempContainer.appendChild(dataElement);
                        return tempContainer.innerHTML;
                    } catch (e) {
                        console.error('Error processing data tag:', e);
                        return `<div class="mpai-data-error">Error processing data: ${e.message}</div>`;
                    }
                } else {
                    return `<pre class="mpai-data">${innerContent}</pre>`;
                }

            case XML_TAG_TYPES.BUTTON:
                // Use the button renderer module if available
                if (window.MPAIButtonRenderer && window.MPAIButtonRenderer.createButton) {
                    const buttonOptions = {
                        text: innerContent,
                        type: attributes.type || 'primary',
                        size: attributes.size || 'medium',
                        onClick: attributes.action ? new Function(attributes.action) : null
                    };
                    
                    const buttonElement = window.MPAIButtonRenderer.createButton(buttonOptions);
                    
                    // Convert DOM element to HTML string
                    const tempContainer = document.createElement('div');
                    tempContainer.appendChild(buttonElement);
                    return tempContainer.innerHTML;
                } else {
                    return `<button class="mpai-button mpai-button-${attributes.type || 'primary'}">${innerContent}</button>`;
                }

            case XML_TAG_TYPES.ERROR:
                return `<div class="mpai-message mpai-error-message">${innerContent}</div>`;

            case XML_TAG_TYPES.WARNING:
                return `<div class="mpai-message mpai-warning-message">${innerContent}</div>`;

            case XML_TAG_TYPES.SUCCESS:
                return `<div class="mpai-message mpai-success-message">${innerContent}</div>`;

            case XML_TAG_TYPES.INFO:
                return `<div class="mpai-message mpai-info-message">${innerContent}</div>`;

            default:
                // For unknown tags, just pass through the content
                return innerContent;
        }
    }

    /**
     * Extract table data from a table node
     * 
     * @param {Node} tableNode The table DOM node
     * @returns {Object} Object with headers and data arrays
     */
    function extractTableData(tableNode) {
        const headers = [];
        const data = [];
        let currentRow = [];
        let isHeaderRow = true;

        // Process child nodes (rows)
        for (let i = 0; i < tableNode.childNodes.length; i++) {
            const rowNode = tableNode.childNodes[i];
            
            if (rowNode.nodeType === Node.ELEMENT_NODE && rowNode.nodeName.toLowerCase() === XML_TAG_TYPES.ROW) {
                currentRow = [];
                
                // Process cells in the row
                for (let j = 0; j < rowNode.childNodes.length; j++) {
                    const cellNode = rowNode.childNodes[j];
                    
                    if (cellNode.nodeType === Node.ELEMENT_NODE && cellNode.nodeName.toLowerCase() === XML_TAG_TYPES.CELL) {
                        const isHeader = cellNode.getAttribute('header') === 'true';
                        
                        // If any cell in the first row is marked as a header, treat the whole row as headers
                        if (isHeader && isHeaderRow) {
                            headers.push(cellNode.textContent);
                        } else {
                            currentRow.push(cellNode.textContent);
                        }
                    }
                }
                
                // If this was the first row and we found headers, don't add it to data
                if (isHeaderRow && headers.length > 0) {
                    isHeaderRow = false;
                } else {
                    data.push(currentRow);
                    isHeaderRow = false;
                }
            }
        }

        return { headers, data };
    }

    /**
     * Process a message content that may contain XML
     * 
     * @param {string} content The message content to process
     * @returns {string} The processed content with XML converted to HTML
     */
    function processMessage(content) {
        if (!content || typeof content !== 'string') {
            return content;
        }

        // Check if content contains XML
        if (!containsXML(content)) {
            return content;
        }

        // Split content into XML and non-XML parts
        return splitAndProcessContent(content);
    }

    /**
     * Split content into XML and non-XML parts and process each accordingly
     * 
     * @param {string} content The content to split and process
     * @returns {string} The processed content
     */
    function splitAndProcessContent(content) {
        // This regex matches XML tags with their content
        const xmlRegex = /<([a-z][a-z0-9]*)((?:\s+[a-z0-9-]+(?:=(?:"[^"]*"|'[^']*'|[^'">\s]+))?)*)(?:\s*\/>|>([\s\S]*?)<\/\1>)/gi;
        
        let lastIndex = 0;
        let result = '';
        let match;
        
        // Find all XML tags and process them
        while ((match = xmlRegex.exec(content)) !== null) {
            // Add text before the XML tag
            result += content.substring(lastIndex, match.index);
            
            // Process the XML tag
            const xmlPart = match[0];
            result += processXMLToHTML(xmlPart);
            
            lastIndex = match.index + xmlPart.length;
        }
        
        // Add any remaining text after the last XML tag
        result += content.substring(lastIndex);
        
        return result;
    }

    /**
     * Add CSS styles for XML-processed content
     */
    function addXMLStyles() {
        const styleElement = document.createElement('style');
        styleElement.textContent = `
            /* XML Processed Content Styles */
            .mpai-heading {
                margin: 1em 0 0.5em;
                font-weight: 600;
                line-height: 1.3;
            }
            
            .mpai-paragraph {
                margin: 0.5em 0;
                line-height: 1.5;
            }
            
            .mpai-code-inline {
                background-color: #f5f5f5;
                padding: 0.2em 0.4em;
                border-radius: 3px;
                font-family: monospace;
                font-size: 0.9em;
            }
            
            .mpai-link {
                color: #0073aa;
                text-decoration: underline;
            }
            
            .mpai-link:hover {
                color: #005d8c;
            }
            
            .mpai-list {
                margin: 0.5em 0;
                padding-left: 2em;
            }
            
            .mpai-list-item {
                margin: 0.25em 0;
            }
            
            .mpai-blockquote {
                margin: 1em 0;
                padding: 0.5em 1em;
                border-left: 4px solid #0073aa;
                background-color: #f9f9f9;
                font-style: italic;
            }
            
            .mpai-message {
                margin: 1em 0;
                padding: 0.75em 1em;
                border-radius: 4px;
                border-left: 4px solid;
            }
            
            .mpai-error-message {
                background-color: #fdf2f2;
                border-color: #dc3232;
                color: #b32d2e;
            }
            
            .mpai-warning-message {
                background-color: #fff8e5;
                border-color: #ffb900;
                color: #996800;
            }
            
            .mpai-success-message {
                background-color: #ecf7ed;
                border-color: #46b450;
                color: #2a8a32;
            }
            
            .mpai-info-message {
                background-color: #f0f6fc;
                border-color: #0073aa;
                color: #005d8c;
            }
            
            /* Dark Mode Support */
            @media (prefers-color-scheme: dark) {
                .mpai-code-inline {
                    background-color: #2c3338;
                    color: #f0f0f1;
                }
                
                .mpai-link {
                    color: #3db2ff;
                }
                
                .mpai-link:hover {
                    color: #5ac8ff;
                }
                
                .mpai-blockquote {
                    background-color: #2c3338;
                    border-color: #3db2ff;
                }
                
                .mpai-error-message {
                    background-color: #362323;
                    color: #f5a3a3;
                }
                
                .mpai-warning-message {
                    background-color: #352e1f;
                    color: #f1cf8a;
                }
                
                .mpai-success-message {
                    background-color: #2a3a2c;
                    color: #a8e5af;
                }
                
                .mpai-info-message {
                    background-color: #2c3338;
                    color: #a8d3f2;
                }
            }
        `;
        document.head.appendChild(styleElement);
    }

    // Initialize the module
    function init() {
        // Add XML styles to the document
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', addXMLStyles);
        } else {
            addXMLStyles();
        }
    }

    // Initialize on load
    init();

    // Export public API
    window.MPAIXMLProcessor = {
        processMessage,
        parseXML,
        extractTags,
        containsXML,
        XML_TAG_TYPES
    };

})();