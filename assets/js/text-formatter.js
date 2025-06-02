/**
 * MemberPress AI Assistant Text Formatter
 * 
 * This module provides functions to format text with markdown-like syntax.
 * It supports common formatting options (bold, italic, headings, lists, links),
 * syntax highlighting for code blocks, and proper escaping for HTML content.
 */

(function() {
    'use strict';

    /**
     * Markdown token types
     */
    const TOKEN_TYPES = {
        HEADING: 'heading',
        PARAGRAPH: 'paragraph',
        BOLD: 'bold',
        ITALIC: 'italic',
        CODE: 'code',
        CODE_BLOCK: 'code_block',
        LINK: 'link',
        LIST_ITEM: 'list_item',
        ORDERED_LIST: 'ordered_list',
        UNORDERED_LIST: 'unordered_list',
        BLOCKQUOTE: 'blockquote',
        HORIZONTAL_RULE: 'horizontal_rule',
        TEXT: 'text'
    };

    /**
     * Format markdown text to HTML
     * 
     * @param {string} text The markdown text to format
     * @param {Object} options Configuration options
     * @returns {string} The formatted HTML
     */
    function formatText(text, options = {}) {
        const {
            allowHtml = false,
            syntaxHighlighting = true,
            linkTarget = '_blank',
            headingBaseLevel = 2
        } = options;

        if (!text) {
            return '';
        }

        // Escape HTML if not allowed
        let processedText = allowHtml ? text : escapeHtml(text);

        // Process markdown
        processedText = processMarkdown(processedText, {
            syntaxHighlighting,
            linkTarget,
            headingBaseLevel
        });

        return processedText;
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
     * Process markdown syntax and convert to HTML
     * 
     * @param {string} text The markdown text to process
     * @param {Object} options Configuration options
     * @returns {string} The processed HTML
     */
    function processMarkdown(text, options) {
        const {
            syntaxHighlighting,
            linkTarget,
            headingBaseLevel
        } = options;

        // Split text into lines for block-level processing
        const lines = text.split('\n');
        let html = '';
        let inCodeBlock = false;
        let codeBlockContent = '';
        let codeBlockLanguage = '';
        let inOrderedList = false;
        let inUnorderedList = false;
        let inBlockquote = false;
        let blockquoteContent = '';

        // Process each line
        for (let i = 0; i < lines.length; i++) {
            let line = lines[i];

            // Handle code blocks
            if (line.trim().startsWith('```')) {
                if (inCodeBlock) {
                    // End of code block
                    html += formatCodeBlock(codeBlockContent, codeBlockLanguage, syntaxHighlighting);
                    inCodeBlock = false;
                    codeBlockContent = '';
                    codeBlockLanguage = '';
                } else {
                    // Start of code block
                    inCodeBlock = true;
                    // Extract language if specified
                    codeBlockLanguage = line.trim().substring(3).trim();
                }
                continue;
            }

            if (inCodeBlock) {
                // Inside code block, collect content
                codeBlockContent += line + '\n';
                continue;
            }

            // Handle blockquotes
            if (line.trim().startsWith('>')) {
                if (!inBlockquote) {
                    inBlockquote = true;
                }
                // Remove '>' and collect content
                blockquoteContent += line.replace(/^>\s?/, '') + '\n';
                continue;
            } else if (inBlockquote && line.trim() === '') {
                // End of blockquote
                html += `<blockquote class="mpai-blockquote">${processInlineMarkdown(blockquoteContent.trim())}</blockquote>`;
                inBlockquote = false;
                blockquoteContent = '';
            }

            // Handle lists
            if (line.trim().match(/^[0-9]+\.\s/)) {
                // Ordered list item
                if (!inOrderedList) {
                    // Start new ordered list
                    html += '<ol class="mpai-ordered-list">';
                    inOrderedList = true;
                }
                // Extract list item content
                const content = line.replace(/^[0-9]+\.\s/, '');
                html += `<li class="mpai-list-item">${processInlineMarkdown(content)}</li>`;
                continue;
            } else if (inOrderedList && (line.trim() === '' || !line.trim().match(/^[0-9]+\.\s/))) {
                // End of ordered list
                html += '</ol>';
                inOrderedList = false;
            }

            if (line.trim().match(/^[-*]\s/)) {
                // Unordered list item
                if (!inUnorderedList) {
                    // Start new unordered list
                    html += '<ul class="mpai-unordered-list">';
                    inUnorderedList = true;
                }
                // Extract list item content
                const content = line.replace(/^[-*]\s/, '');
                html += `<li class="mpai-list-item">${processInlineMarkdown(content)}</li>`;
                continue;
            } else if (inUnorderedList && (line.trim() === '' || !line.trim().match(/^[-*]\s/))) {
                // End of unordered list
                html += '</ul>';
                inUnorderedList = false;
            }

            // Handle headings
            if (line.trim().startsWith('#')) {
                const match = line.trim().match(/^(#{1,6})\s+(.+)$/);
                if (match) {
                    const level = Math.min(match[1].length + headingBaseLevel - 1, 6);
                    const content = match[2];
                    html += `<h${level} class="mpai-heading">${processInlineMarkdown(content)}</h${level}>`;
                    continue;
                }
            }

            // Handle horizontal rules
            if (line.trim().match(/^([-*_])\1{2,}$/)) {
                html += '<hr class="mpai-hr">';
                continue;
            }

            // Handle paragraphs
            if (line.trim() !== '') {
                html += `<p class="mpai-paragraph">${processInlineMarkdown(line)}</p>`;
            }
        }

        // Close any open blocks
        if (inOrderedList) {
            html += '</ol>';
        }
        if (inUnorderedList) {
            html += '</ul>';
        }
        if (inBlockquote) {
            html += `<blockquote class="mpai-blockquote">${processInlineMarkdown(blockquoteContent.trim())}</blockquote>`;
        }
        if (inCodeBlock) {
            html += formatCodeBlock(codeBlockContent, codeBlockLanguage, syntaxHighlighting);
        }

        return html;
    }

    /**
     * Process inline markdown syntax
     * 
     * @param {string} text The text to process
     * @returns {string} The processed HTML
     */
    function processInlineMarkdown(text) {
        // Process inline code
        text = text.replace(/`([^`]+)`/g, '<code class="mpai-code-inline">$1</code>');

        // Process bold (both ** and __ syntax)
        text = text.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
        text = text.replace(/__([^_]+)__/g, '<strong>$1</strong>');

        // Process italic (both * and _ syntax)
        text = text.replace(/\*([^*]+)\*/g, '<em>$1</em>');
        text = text.replace(/_([^_]+)_/g, '<em>$1</em>');

        // Process links
        text = text.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" class="mpai-link" target="_blank">$1</a>');

        return text;
    }

    /**
     * Format a code block with optional syntax highlighting
     * 
     * @param {string} code The code content
     * @param {string} language The code language
     * @param {boolean} syntaxHighlighting Whether to apply syntax highlighting
     * @returns {string} The formatted HTML
     */
    function formatCodeBlock(code, language, syntaxHighlighting) {
        // Trim trailing newline
        code = code.trim();

        if (syntaxHighlighting && window.MPAIContentPreview && window.MPAIContentPreview.createCodePreview) {
            // Use the content preview module for syntax highlighting if available
            const codeElement = window.MPAIContentPreview.createCodePreview(code, {
                language: language || null,
                lineNumbers: true
            });
            
            // Convert DOM element to HTML string
            const tempContainer = document.createElement('div');
            tempContainer.appendChild(codeElement);
            return tempContainer.innerHTML;
        } else {
            // Basic code block without syntax highlighting
            const languageClass = language ? ` class="language-${language}"` : '';
            return `<pre class="mpai-code-block"><code${languageClass}>${code}</code></pre>`;
        }
    }

    /**
     * Format a text string with auto-linking of URLs and email addresses
     * 
     * @param {string} text The text to format
     * @param {Object} options Configuration options
     * @returns {string} The formatted text
     */
    function autoLink(text, options = {}) {
        const {
            target = '_blank',
            rel = 'noopener noreferrer'
        } = options;

        if (!text) {
            return '';
        }

        // URL pattern
        const urlPattern = /(\b(https?|ftp):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/gim;
        
        // Email pattern
        const emailPattern = /(([a-zA-Z0-9\-\_\.])+@[a-zA-Z\_]+?(\.[a-zA-Z]{2,6})+)/gim;
        
        // Replace URLs with links
        text = text.replace(urlPattern, `<a href="$1" class="mpai-link" target="${target}" rel="${rel}">$1</a>`);
        
        // Replace email addresses with mailto links
        text = text.replace(emailPattern, `<a href="mailto:$1" class="mpai-link">$1</a>`);
        
        return text;
    }

    /**
     * Format plain text with line breaks preserved
     * 
     * @param {string} text The text to format
     * @param {boolean} autoLinkUrls Whether to auto-link URLs
     * @returns {string} The formatted text
     */
    function formatPlainText(text, autoLinkUrls = true) {
        if (!text) {
            return '';
        }

        // Escape HTML
        let formattedText = escapeHtml(text);
        
        // Auto-link URLs if enabled
        if (autoLinkUrls) {
            formattedText = autoLink(formattedText);
        }
        
        // Convert line breaks to <br> tags
        formattedText = formattedText.replace(/\n/g, '<br>');
        
        return formattedText;
    }

    /**
     * Truncate text to a specified length with ellipsis
     * 
     * @param {string} text The text to truncate
     * @param {number} length Maximum length
     * @param {string} ellipsis The ellipsis string
     * @returns {string} The truncated text
     */
    function truncateText(text, length = 100, ellipsis = '...') {
        if (!text || text.length <= length) {
            return text;
        }
        
        return text.substring(0, length - ellipsis.length) + ellipsis;
    }

    /**
     * Add CSS styles for formatted text
     */
    function addFormattingStyles() {
        const styleElement = document.createElement('style');
        styleElement.textContent = `
            /* Text Formatting Styles */
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
            
            .mpai-code-block {
                margin: 1em 0;
                padding: 1em;
                background-color: #f5f5f5;
                border-radius: 4px;
                overflow-x: auto;
                font-family: monospace;
                font-size: 13px;
                line-height: 1.5;
            }
            
            .mpai-link {
                color: #0073aa;
                text-decoration: underline;
            }
            
            .mpai-link:hover {
                color: #005d8c;
            }
            
            .mpai-ordered-list,
            .mpai-unordered-list {
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
            
            .mpai-hr {
                margin: 1.5em 0;
                border: 0;
                border-top: 1px solid #ddd;
            }
            
            /* Dark Mode Support */
            @media (prefers-color-scheme: dark) {
                .mpai-code-inline {
                    background-color: #2c3338;
                    color: #f0f0f1;
                }
                
                .mpai-code-block {
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
                
                .mpai-hr {
                    border-color: #4f5a65;
                }
            }
        `;
        document.head.appendChild(styleElement);
    }

    // Initialize the module
    function init() {
        // Add formatting styles to the document
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', addFormattingStyles);
        } else {
            addFormattingStyles();
        }
    }

    // Initialize on load
    init();

    // Export public API
    window.MPAITextFormatter = {
        formatText,
        formatPlainText,
        autoLink,
        truncateText,
        escapeHtml,
        TOKEN_TYPES
    };

})();