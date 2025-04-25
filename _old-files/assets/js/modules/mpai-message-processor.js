/**
 * MemberPress AI Assistant - Message Processor Module
 * 
 * This module specifically handles the problem of raw JSON tool calls showing
 * in the UI instead of being processed. It scans message content after rendering
 * and replaces any detected raw JSON tool calls with proper tool call UI widgets.
 */

var MPAI_MessageProcessor = (function($) {
    'use strict';
    
    // Logger reference
    let logger = null;
    
    /**
     * Initialize the module
     * 
     * @param {Object} options - Configuration options
     */
    function init(options = {}) {
        if (options.logger) {
            logger = options.logger;
            log('Message Processor initialized');
        } else if (window.mpaiLogger) {
            logger = window.mpaiLogger;
            log('Message Processor initialized with global logger');
        } else {
            console.log('MPAI Message Processor - Initialized without logger');
        }
        
        // Process any existing messages
        processExistingMessages();
        
        // Set up a mutation observer to watch for new messages
        if (typeof MutationObserver !== 'undefined') {
            setupMutationObserver();
        }
    }
    
    /**
     * Log a message to the logger
     * 
     * @param {string} message - Message to log
     * @param {string} level - Log level
     */
    function log(message, level = 'info') {
        if (logger && typeof logger[level] === 'function') {
            logger[level](message, 'message_processor');
        } else {
            console.log('MPAI Message Processor - ' + message);
        }
    }
    
    /**
     * Process all existing messages in the DOM
     */
    function processExistingMessages() {
        $('.mpai-message-content').each(function() {
            processMessageContent($(this));
        });
    }
    
    /**
     * Set up a mutation observer to watch for new messages
     */
    function setupMutationObserver() {
        const config = { childList: true, subtree: true, characterData: true };
        
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length) {
                    // Check if any added nodes are messages or contain messages
                    Array.from(mutation.addedNodes).forEach(function(node) {
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            if (node.classList.contains('mpai-message-content')) {
                                processMessageContent($(node));
                            } else {
                                const messageContents = $(node).find('.mpai-message-content');
                                if (messageContents.length) {
                                    messageContents.each(function() {
                                        processMessageContent($(this));
                                    });
                                }
                            }
                        }
                    });
                } else if (mutation.type === 'characterData' && 
                          mutation.target.parentNode && 
                          $(mutation.target.parentNode).hasClass('mpai-message-content')) {
                    processMessageContent($(mutation.target.parentNode));
                }
            });
        });
        
        // Start observing the chat messages container
        const chatMessages = $('.mpai-messages');
        if (chatMessages.length) {
            observer.observe(chatMessages[0], config);
            log('Mutation observer started for message content');
        }
    }
    
    /**
     * Process a message content element
     * 
     * @param {jQuery} $messageContent - The message content element
     */
    function processMessageContent($messageContent) {
        const content = $messageContent.html();
        
        // Skip if no content
        if (!content) {
            return;
        }
        
        // Check for blog post XML content first
        if (window.MPAI_BlogFormatter &&
            typeof window.MPAI_BlogFormatter.processAssistantMessage === 'function' &&
            (content.includes('<wp-post>') ||
             content.includes('</wp-post>') ||
             content.includes('<post-title>') ||
             content.includes('</post-content>'))) {
            
            log('Found XML blog post content, processing with BlogFormatter');
            // Get the parent message element
            const $message = $messageContent.closest('.mpai-chat-message-assistant');
            if ($message.length > 0) {
                window.MPAI_BlogFormatter.processAssistantMessage($message, content);
            }
        }
        
        // Skip already processed tool call content
        if (content.includes('mpai-tool-call') || !content.includes('memberpress_info')) {
            return;
        }
        
        log('Processing message content for tool calls');
        
        // Check if tool call detector and tools are available
        if (!window.MPAI_ToolCallDetector || !window.MPAI_Tools) {
            log('Tool detector or MPAI_Tools not available yet, waiting...', 'warn');
            
            // Wait and try again
            setTimeout(function() {
                processMessageContent($messageContent);
            }, 200);
            
            return;
        }
        
        // Look for direct JSON tool calls in the content
        const replacements = findJSONToolCalls(content);
        
        // If replacements are found, update the content
        if (replacements.length > 0) {
            // Store original content for rollback if needed
            $messageContent.data('original-content', content);
            
            let updatedContent = content;
            
            // Apply replacements
            for (const replacement of replacements) {
                log('Replacing raw JSON tool call with widget: ' + replacement.match.substring(0, 50) + '...');
                updatedContent = updatedContent.replace(replacement.match, replacement.replacement);
            }
            
            // Update the content
            $messageContent.html(updatedContent);
            
            // Execute the tools
            try {
                for (const replacement of replacements) {
                    executeToolCall(replacement.toolCall);
                }
            } catch (error) {
                // If execution fails, roll back to original content
                log('Tool execution failed, rolling back UI: ' + error.message, 'error');
                $messageContent.html(content);
            }
        }
    }
    
    /**
     * Find JSON tool calls in a string
     * 
     * @param {string} content - The content to search
     * @return {Array} Array of objects with match and replacement properties
     */
    function findJSONToolCalls(content) {
        const replacements = [];
        
        // CRITICAL FIX: Multiple patterns to match different JSON formats
        const jsonPatterns = [
            // Original pattern - exact format from example
            /{"tool":\s*"memberpress_info",\s*"parameters":\s*{[^}]*"type":\s*"create"[^}]*}}/g,
            
            // Variation with spaces and newlines
            /{\s*"tool"\s*:\s*"memberpress_info"\s*,\s*"parameters"\s*:\s*{\s*"type"\s*:\s*"create"[^}]*}}/g,
            
            // Variation with name instead of tool
            /{\s*"name"\s*:\s*"memberpress_info"\s*,\s*"parameters"\s*:\s*{\s*"type"\s*:\s*"create"[^}]*}}/g,
            
            // Variation with single quotes
            /{\s*['"]tool['"]\s*:\s*['"]memberpress_info['"]\s*,\s*['"]parameters['"]\s*:\s*{\s*['"]type['"]\s*:\s*['"]create['"]\s*[^}]*}}/g
        ];
        
        // CRITICAL FIX: First check for code blocks with JSON
        const codeBlockPattern = /```json\s*([\s\S]*?)\s*```/g;
        let codeBlockMatch;
        
        while ((codeBlockMatch = codeBlockPattern.exec(content)) !== null) {
            if (codeBlockMatch[1] &&
                codeBlockMatch[1].includes('memberpress_info') &&
                codeBlockMatch[1].includes('"type":"create"')) {
                
                try {
                    // Clean up the JSON string
                    const jsonStr = codeBlockMatch[1].trim();
                    
                    log('CRITICAL FIX - Found JSON code block with potential tool call: ' + jsonStr.substring(0, 50) + '...');
                    
                    // Parse the JSON
                    const jsonData = JSON.parse(jsonStr);
                    
                    if ((jsonData.tool === 'memberpress_info' || jsonData.name === 'memberpress_info') &&
                        jsonData.parameters &&
                        jsonData.parameters.type === 'create') {
                        
                        // Ensure price is a number
                        if (typeof jsonData.parameters.price === 'string' && !isNaN(parseFloat(jsonData.parameters.price))) {
                            jsonData.parameters.price = parseFloat(jsonData.parameters.price);
                            log('CRITICAL FIX - Converted price from string to number: ' + jsonData.parameters.price);
                        }
                        
                        const toolCall = {
                            name: 'memberpress_info',
                            parameters: jsonData.parameters
                        };
                        
                        // Create a formatted tool call HTML
                        let replacementHtml = formatToolCall(toolCall);
                        
                        log('CRITICAL FIX - Found memberpress_info tool call in code block: ' + JSON.stringify(toolCall.parameters));
                        
                        replacements.push({
                            match: codeBlockMatch[0], // Replace the entire code block
                            replacement: replacementHtml,
                            toolCall: toolCall
                        });
                    }
                } catch (e) {
                    log('Error parsing JSON code block: ' + e.message, 'error');
                }
            }
        }
        
        // Process each pattern for raw JSON
        for (const pattern of jsonPatterns) {
            let match;
            while ((match = pattern.exec(content)) !== null) {
                try {
                    const jsonStr = match[0];
                    
                    // Skip if this exact string has already been processed
                    if (replacements.some(r => r.match === jsonStr)) {
                        continue;
                    }
                    
                    // Clean the JSON string - handle single quotes, etc.
                    let cleanJsonStr = jsonStr.replace(/'/g, '"');
                    
                    // Parse the JSON
                    const jsonData = JSON.parse(cleanJsonStr);
                    
                    // Check if it's a valid memberpress_info tool call
                    const toolName = jsonData.tool || jsonData.name;
                    if (toolName === 'memberpress_info' &&
                        jsonData.parameters &&
                        jsonData.parameters.type === 'create') {
                        
                        // Ensure price is a number
                        if (typeof jsonData.parameters.price === 'string' && !isNaN(parseFloat(jsonData.parameters.price))) {
                            jsonData.parameters.price = parseFloat(jsonData.parameters.price);
                            log('CRITICAL FIX - Converted price from string to number: ' + jsonData.parameters.price);
                        }
                        
                        const toolCall = {
                            name: 'memberpress_info',
                            parameters: jsonData.parameters
                        };
                        
                        // Create a formatted tool call HTML
                        let replacementHtml = formatToolCall(toolCall);
                        
                        log('CRITICAL FIX - Found memberpress_info tool call: ' + JSON.stringify(toolCall.parameters));
                        
                        replacements.push({
                            match: jsonStr,
                            replacement: replacementHtml,
                            toolCall: toolCall
                        });
                    }
                } catch (e) {
                    log('Error parsing JSON tool call: ' + e.message, 'error');
                }
            }
        }
        
        return replacements;
    }
    
    /**
     * Format a tool call as HTML
     * 
     * @param {Object} toolCall - The tool call object
     * @return {string} HTML representation
     */
    function formatToolCall(toolCall) {
        if (window.MPAI_ToolCallDetector && typeof window.MPAI_ToolCallDetector.formatToolCall === 'function') {
            return window.MPAI_ToolCallDetector.formatToolCall(toolCall);
        }
        
        // Fallback formatting
        const toolId = 'mpai-tool-' + Date.now() + '-' + Math.floor(Math.random() * 1000);
        
        return `
        <div id="${toolId}" class="mpai-tool-call" data-tool-name="${toolCall.name}">
            <div class="mpai-tool-call-header">
                <span class="mpai-tool-call-name">${toolCall.name}</span>
                <span class="mpai-tool-call-status mpai-tool-call-processing">Processing</span>
            </div>
            <div class="mpai-tool-call-result"></div>
        </div>
        `;
    }
    
    /**
     * Execute a tool call
     * 
     * @param {Object} toolCall - The tool call to execute
     */
    function executeToolCall(toolCall) {
        // CRITICAL FIX: Ensure parameters are properly formatted before execution
        if (toolCall && toolCall.parameters) {
            // Use parameter validator if available
            if (window.MPAI_ParameterValidator &&
                typeof window.MPAI_ParameterValidator.extractMembershipParameters === 'function') {
                
                log('CRITICAL FIX - Using parameter validator to extract and validate parameters');
                
                // Extract parameters using the validator
                const extractedParams = window.MPAI_ParameterValidator.extractMembershipParameters(toolCall);
                
                // Update the tool call with the extracted parameters
                toolCall.parameters = extractedParams;
                
                log('CRITICAL FIX - Parameters after extraction: ' + JSON.stringify(toolCall.parameters));
            } else {
                // Manual parameter formatting
                // Ensure price is a number
                if (typeof toolCall.parameters.price === 'string' && !isNaN(parseFloat(toolCall.parameters.price))) {
                    toolCall.parameters.price = parseFloat(toolCall.parameters.price);
                    log('CRITICAL FIX - Converted price from string to number before execution: ' + toolCall.parameters.price);
                }
                
                // Ensure type is set
                if (!toolCall.parameters.type) {
                    toolCall.parameters.type = 'create';
                    log('CRITICAL FIX - Added missing type parameter');
                }
                
                // Ensure period_type is valid
                if (toolCall.parameters.period_type &&
                    !['month', 'year', 'lifetime'].includes(toolCall.parameters.period_type)) {
                    log('CRITICAL FIX - Invalid period_type: ' + toolCall.parameters.period_type);
                    toolCall.parameters.period_type = 'month'; // Default to month
                }
            }
            
            log('CRITICAL FIX - Executing tool call with parameters: ' + JSON.stringify(toolCall.parameters));
        }
        
        // Try to execute the tool call
        let executed = false;
        
        if (window.MPAI_Tools && typeof window.MPAI_Tools.executeToolCalls === 'function') {
            try {
                window.MPAI_Tools.executeToolCalls([toolCall], '');
                executed = true;
            } catch (e) {
                log('Error executing tool call: ' + e.message, 'error');
            }
        }
        
        // If execution failed or tools not available, retry after a delay
        if (!executed) {
            log('Tool execution failed or MPAI_Tools not available, will retry', 'warn');
            
            // Try again after a short delay
            setTimeout(function() {
                if (window.MPAI_Tools && typeof window.MPAI_Tools.executeToolCalls === 'function') {
                    log('CRITICAL FIX - Retrying tool execution after delay');
                    try {
                        window.MPAI_Tools.executeToolCalls([toolCall], '');
                        log('CRITICAL FIX - Retry successful');
                    } catch (e) {
                        log('CRITICAL FIX - Retry failed: ' + e.message, 'error');
                        
                        // Last resort - try one more time with a longer delay
                        setTimeout(function() {
                            if (window.MPAI_Tools && typeof window.MPAI_Tools.executeToolCalls === 'function') {
                                log('CRITICAL FIX - Final retry attempt');
                                try {
                                    window.MPAI_Tools.executeToolCalls([toolCall], '');
                                } catch (e) {
                                    log('CRITICAL FIX - Final retry failed', 'error');
                                }
                            }
                        }, 1000);
                    }
                }
            }, 500);
        }
    }
    
    // Public API
    return {
        init: init,
        processExistingMessages: processExistingMessages
    };
})(jQuery);

// Initialize the module when the DOM is ready
jQuery(document).ready(function() {
    // Make sure all required components are loaded before initializing
    var checkDependencies = function() {
        if (window.MPAI_MessageProcessor && 
            window.MPAI_ToolCallDetector && 
            window.MPAI_Tools) {
            
            window.MPAI_MessageProcessor.init({
                logger: window.mpaiLogger
            });
            
            console.log('MPAI Message Processor initialized with all dependencies');
        } else {
            console.log('Waiting for MPAI dependencies to load...');
            setTimeout(checkDependencies, 100);
        }
    };
    
    // Start checking for dependencies
    checkDependencies();
});