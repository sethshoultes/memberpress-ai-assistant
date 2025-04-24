/**
 * MemberPress AI Assistant - Tool Call Detector Module
 * 
 * Provides comprehensive detection of tool calls in AI responses
 * with special handling for membership creation parameters
 */

var MPAI_ToolCallDetector = (function($) {
    'use strict';
    
    // Set of already processed tool calls
    let processedToolCalls = new Set();
    
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
            logInfo('Tool Call Detector initialized');
        } else if (window.mpaiLogger) {
            logger = window.mpaiLogger;
            logInfo('Tool Call Detector initialized with global logger');
        } else {
            console.log('MPAI Tool Call Detector - Initialized without logger');
        }
        
        // Reset processed tool calls
        processedToolCalls.clear();
    }
    
    /**
     * Log an info message if logger is available
     * 
     * @param {string} message - The message to log
     * @param {Object} data - Additional data to log
     */
    function logInfo(message, data) {
        if (logger && typeof logger.info === 'function') {
            logger.info(message, 'tool_detection', data);
        } else {
            console.log('MPAI Tool Call Detector - ' + message, data);
        }
    }
    
    /**
     * Log a debug message if logger is available
     * 
     * @param {string} message - The message to log
     * @param {Object} data - Additional data to log
     */
    function logDebug(message, data) {
        if (logger && typeof logger.debug === 'function') {
            logger.debug(message, 'tool_detection', data);
        } else {
            console.log('MPAI Tool Call Detector - ' + message, data);
        }
    }
    
    /**
     * Log an error message if logger is available
     * 
     * @param {string} message - The message to log
     * @param {Object} data - Additional data to log
     */
    function logError(message, data) {
        if (logger && typeof logger.error === 'function') {
            logger.error(message, 'tool_detection', data);
        } else {
            console.error('MPAI Tool Call Detector - ' + message, data);
        }
    }
    
    /**
     * Detect membership creation tool calls in AI response text
     * 
     * @param {string} response - The AI response text
     * @return {Object|null} Extracted parameters or null if not found
     */
    function detectMembershipCreation(response) {
        if (!response) {
            return null;
        }
        
        logDebug('Detecting membership creation in response', {
            responseLength: response.length,
            containsMemberpress: response.includes('memberpress_info'),
            containsCreate: response.includes('"type":"create"') || response.includes('"type": "create"')
        });
        
        // Skip detection if the response doesn't contain relevant keywords
        if (!response.includes('memberpress_info') || 
            !(response.includes('"type":"create"') || response.includes('"type": "create"'))) {
            return null;
        }
        
        // Detection patterns for different formats
        const detectionPatterns = [
            // ENHANCED: More flexible pattern for code blocks with JSON
            /```(?:json)?\s*({[\s\n]*"(?:tool|name)"[\s\n]*:[\s\n]*"memberpress_info"[\s\n]*,[\s\n]*"parameters"[\s\n]*:[\s\n]*{[\s\n]*"type"[\s\n]*:[\s\n]*"create"[\s\S]*?}[\s\n]*})\s*```/m,
            
            // ENHANCED: More flexible pattern for code blocks with JSON and spaces
            /```(?:json)?\s*({[\s\n]*"(?:tool|name)"[\s\n]*:\s*"memberpress_info"[\s\n]*,[\s\n]*"parameters"[\s\n]*:\s*{[\s\n]*"type"[\s\n]*:\s*"create"[\s\S]*?}[\s\n]*})\s*```/m,
            
            // CRITICAL FIX: Exact format from example
            /{"tool":\s*"memberpress_info",\s*"parameters":\s*{"type":\s*"create",\s*"name":\s*"[^"]+",\s*"price":\s*\d+(?:\.\d+)?,\s*"period_type":\s*"[^"]+"(?:,\s*"[^"]+":\s*"[^"]+")*\s*}}/m,
            
            // CRITICAL FIX: Exact format with spaces
            /{\s*"tool":\s*"memberpress_info",\s*"parameters":\s*{\s*"type":\s*"create",\s*"name":\s*"[^"]+",\s*"price":\s*\d+(?:\.\d+)?,\s*"period_type":\s*"[^"]+"(?:,\s*"[^"]+":\s*"[^"]+")*\s*}\s*}/m,
            
            // Raw JSON format - compact
            /{[\s\n]*"(?:tool|name)"[\s\n]*:[\s\n]*"memberpress_info"[\s\n]*,[\s\n]*"parameters"[\s\n]*:[\s\n]*{[\s\n]*"type"[\s\n]*:[\s\n]*"create"[\s\S]*?}[\s\n]*}/m,
            
            // Raw JSON format - formatted with spaces
            /{[\s\n]*"(?:tool|name)"[\s\n]*:\s*"memberpress_info"[\s\n]*,[\s\n]*"parameters"[\s\n]*:\s*{[\s\n]*"type"[\s\n]*:\s*"create"[\s\S]*?}[\s\n]*}/m,
            
            // XML-style Anthropic Claude format
            /<(?:tool|function)>[\s\n]*memberpress_info[\s\n]*<\/(?:tool|function)>[\s\n]*<(?:parameters|arguments)>[\s\n]*({[\s\n]*"type"[\s\n]*:[\s\n]*"create"[\s\S]*?})[\s\n]*<\/(?:parameters|arguments)>/m,
            
            // Function call format with quotes
            /memberpress_info\s*\(\s*{\s*"type"\s*:\s*"create"[\s\S]*?}\s*\)/m,
            
            // Function call format without quotes
            /memberpress_info\s*\(\s*{\s*type\s*:\s*["']{1}create["']{1}[\s\S]*?}\s*\)/m,
            
            // OpenAI function calling format
            /{\s*"role"\s*:\s*"assistant"[\s\S]*?"function_call"\s*:\s*{\s*"name"\s*:\s*"memberpress_info"[\s\S]*?"arguments"\s*:\s*"({[\s\S]*?\"type\"\s*:\s*\"create\"[\s\S]*?})"/m,
            
            // ENHANCED: Partial JSON with just parameters
            /{\s*"type":\s*"create",\s*"name":\s*"[^"]+",\s*"price":\s*\d+(?:\.\d+)?,\s*"period_type":\s*"[^"]+"(?:,\s*"[^"]+":\s*"[^"]+")*\s*}/m
        ];
        
        // Try each pattern until we find a match
        for (const pattern of detectionPatterns) {
            const matches = response.match(pattern);
            
            if (matches && matches[1]) {
                const jsonMatch = matches[1];
                
                // Skip if already processed this exact match
                if (processedToolCalls.has(jsonMatch)) {
                    logDebug('Skipping already processed tool call match', { match: jsonMatch.substring(0, 50) });
                    continue;
                }
                
                // Mark as processed
                processedToolCalls.add(jsonMatch);
                
                logDebug('Found potential membership creation match', { 
                    matchLength: jsonMatch.length,
                    matchPreview: jsonMatch.substring(0, 100)
                });
                
                try {
                    // Extract and clean the JSON string
                    let jsonStr = cleanJsonString(jsonMatch);
                    
                    // Special handling for function call format
                    if (jsonMatch.includes('memberpress_info(')) {
                        jsonStr = parseFunctionCallFormat(jsonMatch);
                    }
                    
                    // Parse the clean JSON
                    const jsonData = JSON.parse(jsonStr);
                    
                    // Extract core parameters based on format
                    const extractedParams = extractParametersFromFormat(jsonData);
                    
                    if (extractedParams && extractedParams.type === 'create') {
                        logInfo('Successfully extracted membership creation parameters', extractedParams);
                        return extractedParams;
                    }
                } catch (error) {
                    logError('Error parsing JSON from detected pattern', { 
                        error: error.toString(),
                        match: jsonMatch.substring(0, 100)
                    });
                }
            }
        }
        
        // If we didn't find a match with any pattern, look for code blocks that might contain JSON
        try {
            const codeBlocks = response.match(/```(?:json)?\s*([\s\S]*?)```/gm);
            
            if (codeBlocks) {
                for (const block of codeBlocks) {
                    // Skip if already processed
                    if (processedToolCalls.has(block)) {
                        continue;
                    }
                    
                    // Mark as processed
                    processedToolCalls.add(block);
                    
                    // Extract the content between the code block markers
                    const content = block.replace(/```(?:json)?\s*/, '').replace(/\s*```$/, '');
                    
                    try {
                        const jsonData = JSON.parse(content);
                        
                        // Check if it's a membership creation call
                        if (jsonData.tool === 'memberpress_info' || jsonData.name === 'memberpress_info') {
                            const params = jsonData.parameters;
                            
                            if (params && params.type === 'create') {
                                logInfo('Found membership creation in JSON code block', params);
                                return params;
                            }
                        }
                    } catch (e) {
                        // Not valid JSON, skip
                    }
                }
            }
        } catch (error) {
            logError('Error processing JSON code blocks', { error: error.toString() });
        }
        
        return null;
    }
    
    /**
     * Clean and normalize a JSON string
     * 
     * @param {string} jsonStr - The JSON string to clean
     * @return {string} Cleaned JSON string
     */
    function cleanJsonString(jsonStr) {
        // Replace escaped quotes
        let cleaned = jsonStr.replace(/\\"/g, '"');
        
        // Remove escaped newlines
        cleaned = cleaned.replace(/\\n/g, ' ');
        
        // Handle doubly encoded strings (OpenAI sometimes does this)
        if (cleaned.startsWith('"') && cleaned.endsWith('"')) {
            try {
                // Try to parse as JSON string first
                cleaned = JSON.parse(cleaned);
            } catch (e) {
                // Not a valid JSON string, leave as is
            }
        }
        
        return cleaned;
    }
    
    /**
     * Parse a function call format string into JSON
     * 
     * @param {string} functionStr - The function call string
     * @return {string} JSON string representation
     */
    function parseFunctionCallFormat(functionStr) {
        // Extract the parameters object from the function call
        const paramsMatch = functionStr.match(/memberpress_info\s*\(\s*({[\s\S]*?})\s*\)/);
        
        if (paramsMatch && paramsMatch[1]) {
            let paramsStr = paramsMatch[1];
            
            // Convert JavaScript object syntax to JSON
            // Replace single quotes with double quotes
            paramsStr = paramsStr.replace(/'/g, '"');
            
            // Add quotes around unquoted property names
            paramsStr = paramsStr.replace(/([{,]\s*)([a-zA-Z0-9_]+)\s*:/g, '$1"$2":');
            
            return paramsStr;
        }
        
        throw new Error('Invalid function call format');
    }
    
    /**
     * Extract parameters from different JSON formats
     * 
     * @param {Object} jsonData - The parsed JSON data
     * @return {Object|null} Extracted parameters or null if invalid
     */
    function extractParametersFromFormat(jsonData) {
        // Log the input data for debugging
        logDebug('Extracting parameters from format', {
            dataType: typeof jsonData,
            hasToolProperty: jsonData && typeof jsonData === 'object' && 'tool' in jsonData,
            hasParametersProperty: jsonData && typeof jsonData === 'object' && 'parameters' in jsonData,
            jsonPreview: jsonData ? JSON.stringify(jsonData).substring(0, 100) : 'null'
        });
        
        // ENHANCED: Create a parameters object that we'll populate
        let extractedParams = {
            type: 'create'  // Default type
        };
        
        // CRITICAL FIX: Handle the exact format from the example
        if (jsonData && typeof jsonData === 'object') {
            // Direct parameters with type
            if (jsonData.type === 'create') {
                logInfo('PARAMETER EXTRACTION - Found direct parameters with type=create');
                
                // Copy all properties from jsonData to extractedParams
                Object.assign(extractedParams, jsonData);
                
                // Ensure price is a number
                if (typeof extractedParams.price === 'string' && !isNaN(parseFloat(extractedParams.price))) {
                    extractedParams.price = parseFloat(extractedParams.price);
                }
                
                return extractedParams;
            }
            
            // Nested parameters in tool or name object (EXACT FORMAT FROM EXAMPLE)
            if ((jsonData.tool === 'memberpress_info' || jsonData.name === 'memberpress_info') &&
                jsonData.parameters && jsonData.parameters.type === 'create') {
                
                // Copy all properties from jsonData.parameters to extractedParams
                Object.assign(extractedParams, jsonData.parameters);
                
                // Ensure price is a number
                if (typeof extractedParams.price === 'string' && !isNaN(parseFloat(extractedParams.price))) {
                    extractedParams.price = parseFloat(extractedParams.price);
                }
                
                logInfo('PARAMETER EXTRACTION - Found nested parameters in tool object', extractedParams);
                return extractedParams;
            }
            
            // OpenAI function_call format
            if (jsonData.function_call &&
                jsonData.function_call.name === 'memberpress_info' &&
                jsonData.function_call.arguments) {
                
                let args = jsonData.function_call.arguments;
                
                // If arguments is a string, parse it
                if (typeof args === 'string') {
                    try {
                        args = JSON.parse(args);
                    } catch (e) {
                        logError('Error parsing function call arguments', {
                            error: e.toString(),
                            args: args
                        });
                    }
                }
                
                if (args && typeof args === 'object') {
                    // Copy all properties from args to extractedParams
                    Object.assign(extractedParams, args);
                    
                    // Ensure price is a number
                    if (typeof extractedParams.price === 'string' && !isNaN(parseFloat(extractedParams.price))) {
                        extractedParams.price = parseFloat(extractedParams.price);
                    }
                    
                    logInfo('PARAMETER EXTRACTION - Found parameters in function_call arguments', extractedParams);
                    return extractedParams;
                }
            }
            
            // Anthropic Claude format in XML
            if (jsonData.tool_use &&
                jsonData.tool_use.name === 'memberpress_info' &&
                jsonData.tool_use.parameters &&
                jsonData.tool_use.parameters.type === 'create') {
                
                // Copy all properties from jsonData.tool_use.parameters to extractedParams
                Object.assign(extractedParams, jsonData.tool_use.parameters);
                
                // Ensure price is a number
                if (typeof extractedParams.price === 'string' && !isNaN(parseFloat(extractedParams.price))) {
                    extractedParams.price = parseFloat(extractedParams.price);
                }
                
                logInfo('PARAMETER EXTRACTION - Found parameters in tool_use format', extractedParams);
                return extractedParams;
            }
            
            // ENHANCED: Check for any properties that might be useful
            if (jsonData.name !== undefined) extractedParams.name = jsonData.name;
            if (jsonData.price !== undefined) {
                extractedParams.price = typeof jsonData.price === 'string' ?
                    parseFloat(jsonData.price) : jsonData.price;
            }
            if (jsonData.period_type !== undefined) extractedParams.period_type = jsonData.period_type;
            
            // If we have at least name and price, return the parameters
            if (extractedParams.name && extractedParams.price) {
                // Set default period_type if not provided
                if (!extractedParams.period_type) extractedParams.period_type = 'month';
                
                logInfo('PARAMETER EXTRACTION - Extracted partial parameters', extractedParams);
                return extractedParams;
            }
        }
        
        logError('PARAMETER EXTRACTION - Failed to extract parameters from format', {
            jsonPreview: jsonData ? JSON.stringify(jsonData).substring(0, 100) : 'null'
        });
        return null;
    }
    
    /**
     * Detect general tool calls in AI response
     * 
     * @param {string} response - The AI response text
     * @return {Array} Array of detected tool calls
     */
    function detectToolCalls(response) {
        const toolCalls = [];
        
        if (!response) {
            return toolCalls;
        }
        
        logDebug('Detecting tool calls in response', {
            responseLength: response.length
        });
        
        // First check for membership creation as a special case
        const membershipParams = detectMembershipCreation(response);
        if (membershipParams) {
            toolCalls.push({
                name: 'memberpress_info',
                parameters: membershipParams
            });
        }
        
        // Look for JSON code blocks containing tool calls
        try {
            const codeBlocks = response.match(/```(?:json)?\s*([\s\S]*?)```/gm);
            
            if (codeBlocks) {
                for (const block of codeBlocks) {
                    // Skip if already processed
                    if (processedToolCalls.has(block)) {
                        continue;
                    }
                    
                    // Mark as processed
                    processedToolCalls.add(block);
                    
                    // Extract the content between the code block markers
                    const content = block.replace(/```(?:json)?\s*/, '').replace(/\s*```$/, '');
                    
                    try {
                        const jsonData = JSON.parse(content);
                        
                        // Process standard tool call format
                        if ((jsonData.tool || jsonData.name) && jsonData.parameters) {
                            const toolName = jsonData.tool || jsonData.name;
                            
                            // Skip if it's a membership creation that we already handled
                            if (toolName === 'memberpress_info' && 
                                jsonData.parameters.type === 'create' && 
                                membershipParams) {
                                continue;
                            }
                            
                            toolCalls.push({
                                name: toolName,
                                parameters: jsonData.parameters
                            });
                            
                            logInfo('Found tool call in JSON code block', { 
                                tool: toolName, 
                                parameters: jsonData.parameters 
                            });
                        }
                    } catch (e) {
                        // Not valid JSON, skip
                    }
                }
            }
        } catch (error) {
            logError('Error processing JSON code blocks for tool calls', { error: error.toString() });
        }
        
        // Look for XML-style tool calls (Anthropic Claude format)
        try {
            const xmlMatches = response.match(/<(?:tool|function)>([\s\S]*?)<\/(?:tool|function)>[\s\S]*?<(?:parameters|arguments)>([\s\S]*?)<\/(?:parameters|arguments)>/gm);
            
            if (xmlMatches) {
                for (const match of xmlMatches) {
                    // Skip if already processed
                    if (processedToolCalls.has(match)) {
                        continue;
                    }
                    
                    // Mark as processed
                    processedToolCalls.add(match);
                    
                    // Extract tool name and parameters
                    const toolMatch = match.match(/<(?:tool|function)>([\s\S]*?)<\/(?:tool|function)>/);
                    const paramsMatch = match.match(/<(?:parameters|arguments)>([\s\S]*?)<\/(?:parameters|arguments)>/);
                    
                    if (toolMatch && toolMatch[1] && paramsMatch && paramsMatch[1]) {
                        const toolName = toolMatch[1].trim();
                        const paramsStr = paramsMatch[1].trim();
                        
                        try {
                            const params = JSON.parse(paramsStr);
                            
                            // Skip if it's a membership creation that we already handled
                            if (toolName === 'memberpress_info' && 
                                params.type === 'create' && 
                                membershipParams) {
                                continue;
                            }
                            
                            toolCalls.push({
                                name: toolName,
                                parameters: params
                            });
                            
                            logInfo('Found tool call in XML format', { 
                                tool: toolName, 
                                parameters: params 
                            });
                        } catch (e) {
                            logError('Error parsing parameters from XML tool call', { 
                                error: e.toString(),
                                paramsStr: paramsStr
                            });
                        }
                    }
                }
            }
        } catch (error) {
            logError('Error processing XML-style tool calls', { error: error.toString() });
        }
        
        return toolCalls;
    }
    
    /**
     * Process an AI response for tool calls
     * 
     * @param {string} response The AI response to process
     * @return {boolean} Whether tool calls were detected and processed
     */
    function processResponse(response) {
        // CRITICAL FIX: Direct detection for the specific format
        if (response && response.includes('memberpress_info') && response.includes('"type":"create"')) {
            logInfo('CRITICAL FIX - Checking for direct membership creation format');
            
            // Multiple patterns to match different JSON formats
            const jsonPatterns = [
                // Exact format from the example
                /{"tool":\s*"memberpress_info",\s*"parameters":\s*{"type":\s*"create"[^}]*}}/g,
                
                // Variation with spaces and newlines
                /{\s*"tool"\s*:\s*"memberpress_info"\s*,\s*"parameters"\s*:\s*{\s*"type"\s*:\s*"create"[^}]*}}/g,
                
                // Variation with name instead of tool
                /{\s*"name"\s*:\s*"memberpress_info"\s*,\s*"parameters"\s*:\s*{\s*"type"\s*:\s*"create"[^}]*}}/g,
                
                // Variation with single quotes
                /{\s*['"]tool['"]\s*:\s*['"]memberpress_info['"]\s*,\s*['"]parameters['"]\s*:\s*{\s*['"]type['"]\s*:\s*['"]create['"]\s*[^}]*}}/g
            ];
            
            // Try each pattern
            for (const pattern of jsonPatterns) {
                const matches = response.match(pattern);
                
                if (matches && matches.length > 0) {
                    logInfo('CRITICAL FIX - Found direct membership creation format', { match: matches[0] });
                    
                    try {
                        // Clean the JSON string - handle single quotes, etc.
                        let jsonStr = matches[0].replace(/'/g, '"');
                        
                        // Parse the JSON
                        const jsonData = JSON.parse(jsonStr);
                        
                        if ((jsonData.tool === 'memberpress_info' || jsonData.name === 'memberpress_info') &&
                            jsonData.parameters &&
                            jsonData.parameters.type === 'create') {
                            
                            // Ensure price is a number
                            if (typeof jsonData.parameters.price === 'string' && !isNaN(parseFloat(jsonData.parameters.price))) {
                                jsonData.parameters.price = parseFloat(jsonData.parameters.price);
                                logInfo('CRITICAL FIX - Converted price from string to number: ' + jsonData.parameters.price);
                            }
                            
                            // Create a tool call object
                            const toolCalls = [{
                                name: 'memberpress_info',
                                parameters: jsonData.parameters
                            }];
                            
                            // Execute the tool call
                            if (window.MPAI_Tools && typeof window.MPAI_Tools.executeToolCalls === 'function') {
                                window.MPAI_Tools.executeToolCalls(toolCalls, response);
                                return true;
                            }
                        }
                    } catch (e) {
                        logError('CRITICAL FIX - Error parsing direct format JSON', { error: e.toString() });
                    }
                }
            }
            
            // Check for JSON code blocks
            if (response.includes('```json')) {
                logInfo('CRITICAL FIX - Checking for JSON code blocks');
                
                const codeBlockPattern = /```json\s*([\s\S]*?)\s*```/g;
                let codeBlockMatch;
                
                while ((codeBlockMatch = codeBlockPattern.exec(response)) !== null) {
                    if (codeBlockMatch[1] &&
                        codeBlockMatch[1].includes('memberpress_info') &&
                        codeBlockMatch[1].includes('"type":"create"')) {
                        
                        try {
                            // Clean up the JSON string
                            const jsonStr = codeBlockMatch[1].trim();
                            
                            logInfo('CRITICAL FIX - Found JSON code block with potential tool call: ' + jsonStr.substring(0, 50) + '...');
                            
                            // Parse the JSON
                            const jsonData = JSON.parse(jsonStr);
                            
                            if ((jsonData.tool === 'memberpress_info' || jsonData.name === 'memberpress_info') &&
                                jsonData.parameters &&
                                jsonData.parameters.type === 'create') {
                                
                                // Ensure price is a number
                                if (typeof jsonData.parameters.price === 'string' && !isNaN(parseFloat(jsonData.parameters.price))) {
                                    jsonData.parameters.price = parseFloat(jsonData.parameters.price);
                                    logInfo('CRITICAL FIX - Converted price from string to number: ' + jsonData.parameters.price);
                                }
                                
                                // Create a tool call object
                                const toolCalls = [{
                                    name: 'memberpress_info',
                                    parameters: jsonData.parameters
                                }];
                                
                                // Execute the tool call
                                if (window.MPAI_Tools && typeof window.MPAI_Tools.executeToolCalls === 'function') {
                                    window.MPAI_Tools.executeToolCalls(toolCalls, response);
                                    return true;
                                }
                            }
                        } catch (e) {
                            logError('CRITICAL FIX - Error parsing JSON code block', { error: e.toString() });
                        }
                    }
                }
            }
        }
        
        // Standard detection flow
        const toolCalls = detectToolCalls(response);
        
        // If no tool calls were detected, return false
        if (toolCalls.length === 0) {
            return false;
        }
        
        // Execute tool calls - pass to a specialized handler
        if (window.MPAI_Tools && typeof window.MPAI_Tools.executeToolCalls === 'function') {
            window.MPAI_Tools.executeToolCalls(toolCalls, response);
        } else {
            logError('Cannot execute tool calls - MPAI_Tools not available');
        }
        
        // Return true to indicate that tool calls were detected
        return true;
    }
    
    /**
     * Reset the processed tool calls set
     */
    function resetProcessed() {
        processedToolCalls.clear();
        logDebug('Reset processed tool calls');
    }
    
    /**
     * Format a tool call for display
     * 
     * @param {Object} toolCall - The tool call object
     * @return {string} HTML representation of the tool call
     */
    function formatToolCall(toolCall) {
        if (!toolCall) {
            return '';
        }
        
        const toolId = 'mpai-tool-' + Date.now() + '-' + Math.floor(Math.random() * 1000);
        
        return `
        <div id="${toolId}" class="mpai-tool-call" data-tool-name="${toolCall.name}" data-tool-parameters='${JSON.stringify(toolCall.parameters)}'>
            <div class="mpai-tool-call-header">
                <span class="mpai-tool-call-name">${toolCall.name}</span>
                <span class="mpai-tool-call-status mpai-tool-call-processing">Processing</span>
            </div>
            <div class="mpai-tool-call-parameters">
                <pre>${JSON.stringify(toolCall.parameters, null, 2)}</pre>
            </div>
            <div class="mpai-tool-call-result"></div>
        </div>
        `;
    }
    
    // Public API
    return {
        init: init,
        detectMembershipCreation: detectMembershipCreation,
        detectToolCalls: detectToolCalls,
        processResponse: processResponse,
        resetProcessed: resetProcessed,
        formatToolCall: formatToolCall
    };
})(jQuery);

// Expose the module globally
window.MPAI_ToolCallDetector = MPAI_ToolCallDetector;