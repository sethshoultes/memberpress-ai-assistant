/**
 * MemberPress AI Assistant Content Preview
 * 
 * This module provides components to preview different content types
 * in the chat interface, including text, images, tables, and code.
 */

(function() {
    'use strict';

    /**
     * Content type constants
     */
    const CONTENT_TYPES = {
        TEXT: 'text',
        IMAGE: 'image',
        TABLE: 'table',
        CODE: 'code'
    };

    /**
     * Supported code languages and their syntax highlighting rules
     */
    const CODE_LANGUAGES = {
        javascript: {
            name: 'JavaScript',
            keywords: ['const', 'let', 'var', 'function', 'return', 'if', 'else', 'for', 'while', 'class', 'extends', 'import', 'export', 'async', 'await', 'try', 'catch', 'new', 'this', 'super', 'static'],
            operators: ['=>', '===', '!==', '==', '!=', '>=', '<=', '>', '<', '+', '-', '*', '/', '%', '&&', '||', '!', '??', '?.', '.', '='],
            delimiters: ['(', ')', '{', '}', '[', ']', ';', ',', ':'],
            strings: ['"', "'", '`'],
            comments: ['//', '/*', '*/'],
            regex: /\/(?![*+?])(?:[^\r\n\[/\\]|\\.|\[(?:[^\r\n\]\\]|\\.)*\])+\/(?:(?:\b(?:g(?:im?|mi?)?|i(?:gm?|mg?)?|m(?:gi?|ig?)?)|\B))/g
        },
        php: {
            name: 'PHP',
            keywords: ['function', 'return', 'if', 'else', 'for', 'foreach', 'while', 'class', 'extends', 'implements', 'public', 'private', 'protected', 'static', 'namespace', 'use', 'try', 'catch', 'throw', 'new', 'this', 'parent', 'self'],
            operators: ['=>', '===', '!==', '==', '!=', '>=', '<=', '>', '<', '+', '-', '*', '/', '%', '&&', '||', '!', '.', '=', '::'],
            delimiters: ['(', ')', '{', '}', '[', ']', ';', ',', ':'],
            strings: ['"', "'"],
            comments: ['//', '#', '/*', '*/'],
            variables: /\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/g
        },
        html: {
            name: 'HTML',
            tags: /<\/?[a-zA-Z0-9]+(?:\s+[a-zA-Z0-9-]+(?:=(?:"[^"]*"|'[^']*'|[^'">\s]+))?)*\s*\/?>/g,
            attributes: /\s+([a-zA-Z0-9-]+)(?:=(?:"[^"]*"|'[^']*'|[^'">\s]+))?/g,
            comments: ['<!--', '-->']
        },
        css: {
            name: 'CSS',
            selectors: /[a-zA-Z0-9_\-\s\.:,#\[\]="']+\s*\{/g,
            properties: /\b([a-zA-Z\-]+)\s*:/g,
            values: /:\s*([^;]+);/g,
            comments: ['/*', '*/']
        },
        sql: {
            name: 'SQL',
            keywords: ['SELECT', 'FROM', 'WHERE', 'INSERT', 'UPDATE', 'DELETE', 'CREATE', 'ALTER', 'DROP', 'TABLE', 'DATABASE', 'JOIN', 'LEFT', 'RIGHT', 'INNER', 'OUTER', 'GROUP', 'ORDER', 'BY', 'HAVING', 'AS', 'ON', 'AND', 'OR', 'NOT', 'NULL', 'IS', 'LIKE', 'IN', 'BETWEEN', 'DISTINCT', 'CASE', 'WHEN', 'THEN', 'ELSE', 'END'],
            operators: ['=', '<>', '!=', '>', '<', '>=', '<=', '+', '-', '*', '/', '%'],
            delimiters: ['(', ')', ';', ','],
            strings: ["'"],
            comments: ['--', '/*', '*/']
        },
        json: {
            name: 'JSON',
            keys: /"([^"]+)":/g,
            strings: ['"'],
            values: /:\s*"([^"]+)"/g,
            numbers: /:\s*(-?\d+\.?\d*)/g,
            booleans: /:\s*(true|false)/g,
            nulls: /:\s*(null)/g,
            delimiters: ['{', '}', '[', ']', ',', ':']
        },
        plaintext: {
            name: 'Plain Text'
        }
    };
/**
     * Create a text preview element
     * 
     * @param {string} content Text content to preview
     * @param {Object} options Configuration options
     * @returns {HTMLElement} The text preview element
     */
    function createTextPreview(content, options = {}) {
        const {
            maxLength = 300,
            expandable = true
        } = options;
        
        const container = document.createElement('div');
        container.className = 'mpai-preview mpai-text-preview';
        
        // Check if content exceeds max length
        const isLongContent = content.length > maxLength && expandable;
        
        if (isLongContent) {
            // Create collapsed view
            const collapsedContent = document.createElement('div');
            collapsedContent.className = 'mpai-preview-content mpai-preview-collapsed';
            collapsedContent.textContent = content.substring(0, maxLength) + '...';
            container.appendChild(collapsedContent);
            
            // Create expanded view (hidden initially)
            const expandedContent = document.createElement('div');
            expandedContent.className = 'mpai-preview-content mpai-preview-expanded';
            expandedContent.textContent = content;
            expandedContent.style.display = 'none';
            container.appendChild(expandedContent);
            
            // Add toggle button
            const toggleButton = document.createElement('button');
            toggleButton.className = 'mpai-preview-toggle';
            toggleButton.textContent = 'Show more';
            toggleButton.setAttribute('aria-expanded', 'false');
            toggleButton.addEventListener('click', () => {
                const isExpanded = toggleButton.getAttribute('aria-expanded') === 'true';
                
                if (isExpanded) {
                    collapsedContent.style.display = 'block';
                    expandedContent.style.display = 'none';
                    toggleButton.textContent = 'Show more';
                    toggleButton.setAttribute('aria-expanded', 'false');
                } else {
                    collapsedContent.style.display = 'none';
                    expandedContent.style.display = 'block';
                    toggleButton.textContent = 'Show less';
                    toggleButton.setAttribute('aria-expanded', 'true');
                }
            });
            
            container.appendChild(toggleButton);
        } else {
            // Just show the content directly
            container.textContent = content;
        }
        
        return container;
    }

    /**
     * Create an image preview element
     * 
     * @param {string} src Image source URL
     * @param {Object} options Configuration options
     * @returns {HTMLElement} The image preview element
     */
    function createImagePreview(src, options = {}) {
        const {
            alt = '',
            maxWidth = '100%',
            maxHeight = '300px',
            expandable = true
        } = options;
        
        const container = document.createElement('div');
        container.className = 'mpai-preview mpai-image-preview';
        
        // Create image element
        const image = document.createElement('img');
        image.src = src;
        image.alt = alt;
        image.style.maxWidth = maxWidth;
        image.style.maxHeight = maxHeight;
        image.style.cursor = expandable ? 'pointer' : 'default';
        
        // Add click handler for expandable images
        if (expandable) {
            image.addEventListener('click', () => {
                // Create modal for expanded view
                const modal = document.createElement('div');
                modal.className = 'mpai-image-modal';
                modal.style.position = 'fixed';
                modal.style.top = '0';
                modal.style.left = '0';
                modal.style.width = '100%';
                modal.style.height = '100%';
                modal.style.backgroundColor = 'rgba(0, 0, 0, 0.8)';
                modal.style.display = 'flex';
                modal.style.alignItems = 'center';
                modal.style.justifyContent = 'center';
                modal.style.zIndex = '100000';
                
                // Create expanded image
                const expandedImage = document.createElement('img');
                expandedImage.src = src;
                expandedImage.alt = alt;
                expandedImage.style.maxWidth = '90%';
                expandedImage.style.maxHeight = '90%';
                expandedImage.style.objectFit = 'contain';
                
                // Add close button
                const closeButton = document.createElement('button');
                closeButton.textContent = '×';
                closeButton.setAttribute('aria-label', 'Close image preview');
                closeButton.style.position = 'absolute';
                closeButton.style.top = '20px';
                closeButton.style.right = '20px';
                closeButton.style.backgroundColor = 'transparent';
                closeButton.style.border = 'none';
                closeButton.style.color = 'white';
                closeButton.style.fontSize = '30px';
                closeButton.style.cursor = 'pointer';
                
                // Close modal on button click or background click
                closeButton.addEventListener('click', () => {
                    document.body.removeChild(modal);
                });
                
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) {
                        document.body.removeChild(modal);
                    }
                });
                
                // Add elements to modal
                modal.appendChild(expandedImage);
                modal.appendChild(closeButton);
                
                // Add modal to body
                document.body.appendChild(modal);
            });
        }
        
        container.appendChild(image);
        
        return container;
    }
/**
     * Create a table preview element
     * 
     * @param {Array<Array<string>>} data Table data (array of rows, each row is an array of cells)
     * @param {Object} options Configuration options
     * @returns {HTMLElement} The table preview element
     */
    function createTablePreview(data, options = {}) {
        const {
            headers = [],
            maxRows = 10,
            expandable = true,
            striped = true,
            bordered = true,
            responsive = true
        } = options;
        
        const container = document.createElement('div');
        container.className = 'mpai-preview mpai-table-preview';
        
        if (responsive) {
            container.style.overflowX = 'auto';
        }
        
        // Create table element
        const table = document.createElement('table');
        table.className = 'mpai-table';
        
        if (striped) {
            table.classList.add('mpai-table-striped');
        }
        
        if (bordered) {
            table.classList.add('mpai-table-bordered');
        }
        
        // Add headers if provided
        if (headers.length > 0) {
            const thead = document.createElement('thead');
            const headerRow = document.createElement('tr');
            
            headers.forEach(header => {
                const th = document.createElement('th');
                th.textContent = header;
                headerRow.appendChild(th);
            });
            
            thead.appendChild(headerRow);
            table.appendChild(thead);
        }
        
        // Create table body
        const tbody = document.createElement('tbody');
        
        // Check if data exceeds max rows
        const isLargeTable = data.length > maxRows && expandable;
        const visibleRows = isLargeTable ? data.slice(0, maxRows) : data;
        
        // Add visible rows
        visibleRows.forEach(rowData => {
            const row = document.createElement('tr');
            
            rowData.forEach(cellData => {
                const cell = document.createElement('td');
                cell.textContent = cellData;
                row.appendChild(cell);
            });
            
            tbody.appendChild(row);
        });
        
        table.appendChild(tbody);
        container.appendChild(table);
        
        // Add toggle button for large tables
        if (isLargeTable) {
            const hiddenRows = data.slice(maxRows);
            const toggleButton = document.createElement('button');
            toggleButton.className = 'mpai-preview-toggle';
            toggleButton.textContent = `Show ${hiddenRows.length} more rows`;
            toggleButton.setAttribute('aria-expanded', 'false');
            
            toggleButton.addEventListener('click', () => {
                const isExpanded = toggleButton.getAttribute('aria-expanded') === 'true';
                
                if (isExpanded) {
                    // Remove the additional rows
                    const rowsToRemove = Array.from(tbody.querySelectorAll('tr')).slice(maxRows);
                    rowsToRemove.forEach(row => tbody.removeChild(row));
                    
                    toggleButton.textContent = `Show ${hiddenRows.length} more rows`;
                    toggleButton.setAttribute('aria-expanded', 'false');
                } else {
                    // Add the hidden rows
                    hiddenRows.forEach(rowData => {
                        const row = document.createElement('tr');
                        
                        rowData.forEach(cellData => {
                            const cell = document.createElement('td');
                            cell.textContent = cellData;
                            row.appendChild(cell);
                        });
                        
                        tbody.appendChild(row);
                    });
                    
/**
     * Detect the language of code based on content
     * 
     * @param {string} code The code content
     * @param {string} hint Optional language hint
     * @returns {string} Detected language key
     */
    function detectCodeLanguage(code, hint = null) {
        // If hint is provided and valid, use it
        if (hint && CODE_LANGUAGES[hint.toLowerCase()]) {
            return hint.toLowerCase();
        }
        
        // Simple language detection based on patterns
        if (code.includes('<?php') || code.includes('<?=')) {
            return 'php';
        } else if (code.includes('<html') || code.includes('<!DOCTYPE') || 
                  (code.includes('<') && code.includes('>') && code.includes('</') && !code.includes('<?'))) {
            return 'html';
        } else if (code.includes('{') && code.includes('}') && 
                  (code.includes('.class') || code.includes('#id') || 
                   code.includes('@media') || code.includes(':hover'))) {
            return 'css';
        } else if (code.includes('function') || code.includes('const ') || 
                  code.includes('var ') || code.includes('let ') || 
                  code.includes('=>') || code.includes('document.')) {
            return 'javascript';
        } else if (code.includes('SELECT') && code.includes('FROM') || 
                  code.includes('INSERT INTO') || code.includes('CREATE TABLE')) {
            return 'sql';
        } else if ((code.includes('{') && code.includes('}') && code.includes(':') && 
                   code.includes('"') && !code.includes('function'))) {
            return 'json';
        }
        
        // Default to plaintext if no patterns match
        return 'plaintext';
    }

    /**
     * Escape HTML special characters
     * 
     * @param {string} text Text to escape
     * @returns {string} Escaped text
     */
    function escapeHtml(text) {
        return text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    /**
     * Apply syntax highlighting to code
     * 
     * @param {string} code The code content
     * @param {string} language The code language
     * @returns {string} HTML with syntax highlighting
     */
    function applySyntaxHighlighting(code, language) {
        // Get language rules
        const lang = CODE_LANGUAGES[language] || CODE_LANGUAGES.plaintext;
        
        // If plaintext, just escape HTML and return
        if (language === 'plaintext') {
            return escapeHtml(code);
        }
        
        // Escape HTML in the code
        let html = escapeHtml(code);
        
        // Apply language-specific highlighting
        switch (language) {
            case 'html':
                // Highlight tags
                html = html.replace(lang.tags, match => {
                    return `<span class="mpai-code-tag">${match}</span>`;
                });
                
                // Highlight attributes
                html = html.replace(lang.attributes, (match, attr) => {
                    return ` <span class="mpai-code-attr">${attr}</span>=`;
                });
                break;
                
            case 'css':
                // Highlight selectors
                html = html.replace(lang.selectors, match => {
                    return `<span class="mpai-code-selector">${match}</span>`;
                });
                
                // Highlight properties
                html = html.replace(lang.properties, (match, prop) => {
                    return `<span class="mpai-code-property">${prop}</span>:`;
                });
                
                // Highlight values
                html = html.replace(lang.values, (match, value) => {
                    return `: <span class="mpai-code-value">${value}</span>;`;
                });
                break;
                
            case 'json':
                // Highlight keys
                html = html.replace(lang.keys, (match, key) => {
                    return `"<span class="mpai-code-key">${key}</span>":`;
                });
                
                // Highlight string values
                html = html.replace(lang.values, (match, value) => {
                    return `: "<span class="mpai-code-string">${value}</span>"`;
                });
                
                // Highlight numeric values
                html = html.replace(lang.numbers, (match, value) => {
                    return `: <span class="mpai-code-number">${value}</span>`;
                });
                
                // Highlight boolean values
                html = html.replace(lang.booleans, (match, value) => {
                    return `: <span class="mpai-code-boolean">${value}</span>`;
                });
                
                // Highlight null values
                html = html.replace(lang.nulls, (match, value) => {
                    return `: <span class="mpai-code-null">${value}</span>`;
                });
                break;
                
            default:
                // For other languages (JavaScript, PHP, SQL)
                if (lang.keywords) {
                    // Highlight keywords
                    lang.keywords.forEach(keyword => {
                        const regex = new RegExp(`\\b${keyword}\\b`, 'g');
                        html = html.replace(regex, `<span class="mpai-code-keyword">${keyword}</span>`);
                    });
                }
                
                if (lang.operators) {
                    // Highlight operators
                    lang.operators.forEach(operator => {
                        // Escape special regex characters
                        const escapedOperator = operator.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                        const regex = new RegExp(escapedOperator, 'g');
                        html = html.replace(regex, `<span class="mpai-code-operator">${operator}</span>`);
                    });
                }
                
                // Highlight strings
                if (lang.strings) {
                    lang.strings.forEach(stringChar => {
                        const escapedChar = stringChar.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                        const regex = new RegExp(`${escapedChar}[^${escapedChar}\\\\]*(?:\\\\.[^${escapedChar}\\\\]*)*${escapedChar}`, 'g');
                        html = html.replace(regex, match => {
                            return `<span class="mpai-code-string">${match}</span>`;
                        });
                    });
                }
                
                // Highlight PHP variables
                if (language === 'php' && lang.variables) {
                    html = html.replace(lang.variables, match => {
                        return `<span class="mpai-code-variable">${match}</span>`;
                    });
                }
                
                // Highlight regex patterns in JavaScript
                if (language === 'javascript' && lang.regex) {
                    html = html.replace(lang.regex, match => {
                        return `<span class="mpai-code-regex">${match}</span>`;
                    });
                }
                break;
        }
        
        // Highlight comments for all languages
        if (lang.comments) {
            if (lang.comments.includes('//')) {
                // Single-line comments
                html = html.replace(/\/\/.*$/gm, match => {
                    return `<span class="mpai-code-comment">${match}</span>`;
                });
            }
            
            if (lang.comments.includes('#')) {
                // Hash comments (PHP, SQL)
                html = html.replace(/#.*$/gm, match => {
                    return `<span class="mpai-code-comment">${match}</span>`;
                });
            }
            
            if (lang.comments.includes('/*') && lang.comments.includes('*/')) {
                // Multi-line comments
                html = html.replace(/\/\*[\s\S]*?\*\//g, match => {
                    return `<span class="mpai-code-comment">${match}</span>`;
                });
            }
/**
     * Create a code preview element
     * 
     * @param {string} code The code content
     * @param {Object} options Configuration options
     * @returns {HTMLElement} The code preview element
     */
    function createCodePreview(code, options = {}) {
        const {
            language = null,
            lineNumbers = true,
            maxHeight = '300px',
            expandable = true,
            copyButton = true
        } = options;
        
        // Detect language if not provided
        const detectedLanguage = language || detectCodeLanguage(code);
        const languageInfo = CODE_LANGUAGES[detectedLanguage] || CODE_LANGUAGES.plaintext;
        
        const container = document.createElement('div');
        container.className = 'mpai-preview mpai-code-preview';
        
        // Create code header with language name and copy button
        const header = document.createElement('div');
        header.className = 'mpai-code-header';
        
        // Add language name
        const languageName = document.createElement('span');
        languageName.className = 'mpai-code-language';
        languageName.textContent = languageInfo.name || 'Code';
        header.appendChild(languageName);
        
        // Add copy button if enabled
        if (copyButton) {
            const copyBtn = document.createElement('button');
            copyBtn.className = 'mpai-code-copy';
            copyBtn.setAttribute('aria-label', 'Copy code');
            copyBtn.innerHTML = '<span class="dashicons dashicons-clipboard"></span>';
            
            copyBtn.addEventListener('click', () => {
                // Copy code to clipboard
                navigator.clipboard.writeText(code).then(() => {
                    // Show success feedback
                    copyBtn.innerHTML = '<span class="dashicons dashicons-yes"></span>';
                    setTimeout(() => {
                        copyBtn.innerHTML = '<span class="dashicons dashicons-clipboard"></span>';
                    }, 2000);
                }).catch(err => {
                    console.error('Failed to copy code:', err);
                    // Show error feedback
                    copyBtn.innerHTML = '<span class="dashicons dashicons-no"></span>';
                    setTimeout(() => {
                        copyBtn.innerHTML = '<span class="dashicons dashicons-clipboard"></span>';
                    }, 2000);
                });
            });
            
            header.appendChild(copyBtn);
        }
        
        container.appendChild(header);
        
        // Create code content wrapper
        const codeWrapper = document.createElement('div');
        codeWrapper.className = 'mpai-code-wrapper';
        
        if (maxHeight) {
            codeWrapper.style.maxHeight = maxHeight;
            codeWrapper.style.overflow = 'auto';
        }
        
        // Apply syntax highlighting
        const highlightedCode = applySyntaxHighlighting(code, detectedLanguage);
        
        // Create code element
        const codeElement = document.createElement('pre');
        codeElement.className = 'mpai-code';
        
        // Add line numbers if enabled
        if (lineNumbers) {
            codeElement.classList.add('mpai-code-line-numbers');
            
            const lines = code.split('\n');
            let codeHtml = '';
            
            lines.forEach((line, index) => {
                const lineNumber = index + 1;
                const highlightedLine = applySyntaxHighlighting(line, detectedLanguage);
                codeHtml += `<div class="mpai-code-line"><span class="mpai-code-line-number">${lineNumber}</span><span class="mpai-code-line-content">${highlightedLine}</span></div>`;
            });
            
            codeElement.innerHTML = codeHtml;
        } else {
            codeElement.innerHTML = highlightedCode;
        }
        
        codeWrapper.appendChild(codeElement);
        container.appendChild(codeWrapper);
        
/**
     * Detect content type based on content
     * 
     * @param {string} content The content to analyze
     * @returns {string} Detected content type
     */
    function detectContentType(content) {
        // Check if content is an image URL
        if (/^https?:\/\/.*\.(jpg|jpeg|png|gif|svg|webp)(\?.*)?$/i.test(content)) {
            return CONTENT_TYPES.IMAGE;
        }
        
        // Check if content is a code block
        if (/^```[\s\S]*```$/m.test(content) || 
            content.includes('function') || 
            content.includes('class') || 
            content.includes('<html') || 
            content.includes('<?php')) {
            return CONTENT_TYPES.CODE;
        }
        
        // Check if content might be a table
        if (content.includes('|') && content.includes('\n') && 
            content.split('\n').filter(line => line.includes('|')).length > 2) {
            return CONTENT_TYPES.TABLE;
        }
        
        // Default to text
        return CONTENT_TYPES.TEXT;
    }

    /**
     * Parse markdown table into data array
     * 
     * @param {string} tableContent Markdown table content
     * @returns {Object} Parsed table data and headers
     */
    function parseMarkdownTable(tableContent) {
        const lines = tableContent.trim().split('\n');
        const headers = [];
        const data = [];
        
        // Process each line
        lines.forEach((line, index) => {
            // Skip separator line (contains only |, -, and spaces)
            if (/^\s*\|[-|\s]+\|\s*$/.test(line)) {
                return;
            }
            
            // Remove leading/trailing | and split by |
            const cells = line.trim()
                .replace(/^\||\|$/g, '')
                .split('|')
                .map(cell => cell.trim());
            
            if (index === 0) {
                // First line is headers
                headers.push(...cells);
            } else {
                // Data rows
                data.push(cells);
            }
        });
        
        return { headers, data };
    }

    /**
     * Create a preview element based on content type
     * 
     * @param {string} content The content to preview
     * @param {Object} options Configuration options
     * @returns {HTMLElement} The preview element
     */
    function createPreview(content, options = {}) {
        const contentType = options.type || detectContentType(content);
        
        switch (contentType) {
            case CONTENT_TYPES.IMAGE:
                return createImagePreview(content, options);
                
            case CONTENT_TYPES.CODE:
                // Extract code from markdown code blocks
                if (/^```(\w+)?\n([\s\S]*?)```$/m.test(content)) {
                    const match = content.match(/^```(\w+)?\n([\s\S]*?)```$/m);
                    const language = match[1] || null;
                    const code = match[2];
                    return createCodePreview(code, { ...options, language });
                }
                return createCodePreview(content, options);
                
            case CONTENT_TYPES.TABLE:
                // Parse markdown table
                if (content.includes('|')) {
                    const { headers, data } = parseMarkdownTable(content);
                    return createTablePreview(data, { ...options, headers });
                }
                return createTablePreview([], options);
                
            case CONTENT_TYPES.TEXT:
            default:
                return createTextPreview(content, options);
        }
    }

    /**
     * Add CSS styles for previews
     */
    function addPreviewStyles() {
        const styleElement = document.createElement('style');
        styleElement.textContent = `
            /* Preview Base Styles */
            .mpai-preview {
                margin: 10px 0;
                border-radius: 6px;
                overflow: hidden;
            }
            
            /* Text Preview */
            .mpai-text-preview {
                line-height: 1.5;
            }
            
            .mpai-preview-toggle {
                background-color: transparent;
                border: none;
                color: #0073aa;
                cursor: pointer;
                padding: 5px 0;
                font-size: 13px;
                text-decoration: underline;
                display: block;
                margin-top: 5px;
            }
            
            .mpai-preview-toggle:hover {
                color: #005d8c;
            }
            
            /* Image Preview */
            .mpai-image-preview img {
                display: block;
                border-radius: 6px;
            }
            
            /* Table Preview */
            .mpai-table {
                width: 100%;
                border-collapse: collapse;
                font-size: 14px;
            }
            
            .mpai-table th,
            .mpai-table td {
                padding: 8px 12px;
                text-align: left;
            }
            
            .mpai-table th {
                background-color: #f1f1f1;
                font-weight: 600;
            }
            
            .mpai-table-striped tr:nth-child(even) {
                background-color: #f9f9f9;
            }
            
            .mpai-table-bordered th,
            .mpai-table-bordered td {
                border: 1px solid #e0e0e0;
            }
            
            /* Code Preview */
            .mpai-code-preview {
                background-color: #f5f5f5;
                border-radius: 6px;
                overflow: hidden;
            }
            
            .mpai-code-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 8px 12px;
                background-color: #e0e0e0;
                font-size: 13px;
            }
            
            .mpai-code-language {
                font-weight: 600;
            }
            
            .mpai-code-copy {
                background: transparent;
                border: none;
                cursor: pointer;
                padding: 2px;
                color: #555;
            }
            
            .mpai-code-copy:hover {
                color: #000;
            }
            
            .mpai-code-wrapper {
                padding: 12px;
                overflow: auto;
            }
            
            .mpai-code-collapsed {
                max-height: 300px;
            }
            
            .mpai-code {
                margin: 0;
                font-family: monospace;
                font-size: 13px;
                line-height: 1.5;
                white-space: pre;
            }
            
            .mpai-code-line {
                display: flex;
            }
            
            .mpai-code-line-number {
                color: #999;
                text-align: right;
                padding-right: 12px;
                user-select: none;
                min-width: 40px;
            }
            
            .mpai-code-line-content {
                flex: 1;
            }
            
            /* Syntax Highlighting */
            .mpai-code-keyword { color: #07a; }
            .mpai-code-operator { color: #a67f59; }
            .mpai-code-string { color: #690; }
            .mpai-code-comment { color: #999; }
            .mpai-code-tag { color: #07a; }
            .mpai-code-attr { color: #905; }
            .mpai-code-selector { color: #07a; }
            .mpai-code-property { color: #905; }
            .mpai-code-value { color: #690; }
            .mpai-code-key { color: #905; }
            .mpai-code-number { color: #905; }
            .mpai-code-boolean { color: #07a; }
            .mpai-code-null { color: #07a; }
            .mpai-code-variable { color: #e90; }
            .mpai-code-regex { color: #e90; }
            
            /* Dark Mode Support */
            @media (prefers-color-scheme: dark) {
                .mpai-preview-toggle {
                    color: #3db2ff;
                }
                
                .mpai-preview-toggle:hover {
                    color: #5ac8ff;
                }
                
                .mpai-table th {
                    background-color: #3c434a;
                }
                
                .mpai-table-striped tr:nth-child(even) {
                    background-color: #32373c;
                }
                
                .mpai-table-bordered th,
                .mpai-table-bordered td {
                    border-color: #4f5a65;
                }
                
                .mpai-code-preview {
                    background-color: #2c3338;
                }
                
                .mpai-code-header {
                    background-color: #1d2327;
                }
                
                .mpai-code-copy {
                    color: #bbb;
                }
                
                .mpai-code-copy:hover {
                    color: #fff;
                }
                
                .mpai-code-line-number {
                    color: #777;
                }
                
                /* Dark Mode Syntax Highlighting */
                .mpai-code-keyword { color: #569cd6; }
                .mpai-code-operator { color: #d4d4d4; }
                .mpai-code-string { color: #ce9178; }
                .mpai-code-comment { color: #6a9955; }
                .mpai-code-tag { color: #569cd6; }
                .mpai-code-attr { color: #9cdcfe; }
                .mpai-code-selector { color: #d7ba7d; }
                .mpai-code-property { color: #9cdcfe; }
                .mpai-code-value { color: #ce9178; }
                .mpai-code-key { color: #9cdcfe; }
                .mpai-code-number { color: #b5cea8; }
                .mpai-code-boolean { color: #569cd6; }
                .mpai-code-null { color: #569cd6; }
                .mpai-code-variable { color: #9cdcfe; }
                .mpai-code-regex { color: #d16969; }
            }
        `;
        document.head.appendChild(styleElement);
    }

    // Initialize the module
    function init() {
        // Add preview styles to the document
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', addPreviewStyles);
        } else {
            addPreviewStyles();
        }
    }

    // Initialize on load
    init();

    // Export public API
    window.MPAIContentPreview = {
        createTextPreview,
        createImagePreview,
        createTablePreview,
        createCodePreview,
        createPreview,
        detectContentType,
        parseMarkdownTable,
        CONTENT_TYPES
    };

})();
        // Add expand/collapse functionality if enabled and code is large
        if (expandable && code.split('\n').length > 10) {
            codeWrapper.classList.add('mpai-code-collapsed');
            
            const toggleButton = document.createElement('button');
            toggleButton.className = 'mpai-preview-toggle';
            toggleButton.textContent = 'Show more';
            toggleButton.setAttribute('aria-expanded', 'false');
            
            toggleButton.addEventListener('click', () => {
                const isExpanded = toggleButton.getAttribute('aria-expanded') === 'true';
                
                if (isExpanded) {
                    codeWrapper.classList.add('mpai-code-collapsed');
                    toggleButton.textContent = 'Show more';
                    toggleButton.setAttribute('aria-expanded', 'false');
                } else {
                    codeWrapper.classList.remove('mpai-code-collapsed');
                    toggleButton.textContent = 'Show less';
                    toggleButton.setAttribute('aria-expanded', 'true');
                }
            });
            
            container.appendChild(toggleButton);
        }
        
        return container;
    }
            
            if (lang.comments.includes('<!--') && lang.comments.includes('-->')) {
                // HTML comments
                html = html.replace(/<!--[\s\S]*?-->/g, match => {
                    return `<span class="mpai-code-comment">${match}</span>`;
                });
            }
            
            if (language === 'sql' && lang.comments.includes('--')) {
                // SQL comments
                html = html.replace(/--.*$/gm, match => {
                    return `<span class="mpai-code-comment">${match}</span>`;
                });
            }
        }
        
        return html;
    }
                    toggleButton.textContent = 'Show less';
                    toggleButton.setAttribute('aria-expanded', 'true');
                }
            });
            
            container.appendChild(toggleButton);
        }
        
        return container;
    }