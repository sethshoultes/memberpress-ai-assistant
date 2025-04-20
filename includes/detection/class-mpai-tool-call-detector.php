<?php
/**
 * Tool Call Detector Class
 *
 * Handles detection of tool calls in AI responses
 * 
 * @package MemberPress AI Assistant
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Tool Call Detector Class
 * 
 * This class provides a unified approach to tool call detection that works consistently
 * across both JavaScript and PHP. It detects tool calls in AI responses and extracts
 * the tool name and parameters.
 */
class MPAI_Tool_Call_Detector {
    /**
     * Singleton instance
     *
     * @var MPAI_Tool_Call_Detector
     */
    private static $instance = null;

    /**
     * Tool registry instance
     *
     * @var MPAI_Tool_Registry
     */
    private $tool_registry = null;

    /**
     * Get the singleton instance
     *
     * @return MPAI_Tool_Call_Detector The singleton instance
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Initialize tool registry if available
        if (class_exists('MPAI_Tool_Registry')) {
            $this->tool_registry = new MPAI_Tool_Registry();
        }
        
        mpai_log_debug('Tool Call Detector initialized', 'tool-call-detector');
    }

    /**
     * Detect tool calls in a response
     *
     * @param string $response The AI response to check for tool calls
     * @return array Array of detected tool calls
     */
    public function detect_tool_calls($response) {
        mpai_log_debug('Detecting tool calls in response', 'tool-call-detector');
        
        $tool_calls = [];
        
        // XML-style format: <tool:tool_name>{"param1": "value1", "param2": "value2"}</tool>
        $this->detect_xml_style_tool_calls($response, $tool_calls);
        
        // JSON format: {"name": "tool_name", "parameters": {"param1": "value1", "param2": "value2"}}
        $this->detect_json_tool_calls($response, $tool_calls);
        
        // HTML format (DOM-based)
        $this->detect_html_tool_calls($response, $tool_calls);
        
        mpai_log_debug('Detected ' . count($tool_calls) . ' tool calls', 'tool-call-detector');
        
        return $tool_calls;
    }
    
    /**
     * Detect XML-style tool calls
     *
     * @param string $response The AI response
     * @param array &$tool_calls Array to store detected tool calls
     */
    private function detect_xml_style_tool_calls($response, &$tool_calls) {
        // Pattern for XML-style tool calls: <tool:tool_name>{"param1": "value1", "param2": "value2"}</tool>
        $pattern = '/<tool:([^>]+)>([\s\S]*?)<\/tool>/';
        
        // Find all matches
        preg_match_all($pattern, $response, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $tool_name = $match[1];
            $parameters_str = $match[2];
            
            // Parse parameters
            $parameters = json_decode($parameters_str, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                // Log error
                mpai_log_error('Failed to parse tool parameters: ' . json_last_error_msg(), 'tool-call-detector');
                continue;
            }
            
            // Add to tool calls
            $tool_calls[] = [
                'name' => $tool_name,
                'parameters' => $parameters,
                'original' => $match[0],
                'format' => 'xml'
            ];
        }
    }
    
    /**
     * Detect JSON format tool calls
     *
     * @param string $response The AI response
     * @param array &$tool_calls Array to store detected tool calls
     */
    private function detect_json_tool_calls($response, &$tool_calls) {
        // Pattern for JSON tool calls: {"name": "tool_name", "parameters": {"param1": "value1", "param2": "value2"}}
        $pattern = '/```(?:json)?\s*(\{[\s\S]*?\})\s*```/';
        
        // Find all matches
        preg_match_all($pattern, $response, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $json_str = $match[1];
            
            // Parse JSON
            $json_data = json_decode($json_str, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                // Log error
                mpai_log_error('Failed to parse JSON tool call: ' . json_last_error_msg(), 'tool-call-detector');
                continue;
            }
            
            // Check if this is a tool call
            if (isset($json_data['name']) && isset($json_data['parameters'])) {
                // Add to tool calls
                $tool_calls[] = [
                    'name' => $json_data['name'],
                    'parameters' => $json_data['parameters'],
                    'original' => $match[0],
                    'format' => 'json'
                ];
            } else if (isset($json_data['tool']) && isset($json_data['parameters'])) {
                // Legacy format with 'tool' instead of 'name'
                $tool_calls[] = [
                    'name' => $json_data['tool'],
                    'parameters' => $json_data['parameters'],
                    'original' => $match[0],
                    'format' => 'json_legacy'
                ];
            }
        }
    }
    
    /**
     * Detect HTML format tool calls
     *
     * @param string $response The AI response
     * @param array &$tool_calls Array to store detected tool calls
     */
    private function detect_html_tool_calls($response, &$tool_calls) {
        // This is primarily for JavaScript DOM-based detection
        // In PHP, we'll use a simple regex approach to detect HTML tool calls
        
        // Pattern for HTML tool calls
        $pattern = '/<div[^>]*class="[^"]*mpai-tool-call[^"]*"[^>]*data-tool-name="([^"]*)"[^>]*data-tool-parameters=\'([^\']*)\'/';
        
        // Find all matches
        preg_match_all($pattern, $response, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $tool_name = $match[1];
            $parameters_str = $match[2];
            
            // Parse parameters
            $parameters = json_decode($parameters_str, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                // Log error
                mpai_log_error('Failed to parse HTML tool parameters: ' . json_last_error_msg(), 'tool-call-detector');
                continue;
            }
            
            // Add to tool calls
            $tool_calls[] = [
                'name' => $tool_name,
                'parameters' => $parameters,
                'original' => $match[0],
                'format' => 'html'
            ];
        }
    }
    
    /**
     * Execute detected tool calls
     *
     * @param array $tool_calls Array of detected tool calls
     * @param string $response Original response
     * @return string Updated response with tool call results
     */
    public function execute_tool_calls($tool_calls, $response) {
        mpai_log_debug('Executing ' . count($tool_calls) . ' tool calls', 'tool-call-detector');
        
        // Check if tool registry is available
        if (!$this->tool_registry) {
            if (class_exists('MPAI_Tool_Registry')) {
                $this->tool_registry = new MPAI_Tool_Registry();
            } else {
                mpai_log_error('Tool registry not available', 'tool-call-detector');
                return $response;
            }
        }
        
        foreach ($tool_calls as $tool_call) {
            $tool_name = $tool_call['name'];
            $parameters = $tool_call['parameters'];
            $original = $tool_call['original'];
            
            // Only the standardized 'wpcli' tool ID is supported
            // Legacy tool IDs have been completely removed
            
            // Get the tool
            $tool = $this->tool_registry->get_tool($tool_name);
            
            if (!$tool) {
                // Tool not found
                mpai_log_error('Tool not found: ' . $tool_name, 'tool-call-detector');
                $error_message = "Tool '{$tool_name}' not found.";
                $response = str_replace($original, $this->format_error_result($tool_name, $error_message), $response);
                continue;
            }
            
            try {
                // Execute the tool
                mpai_log_debug('Executing tool: ' . $tool_name, 'tool-call-detector');
                $result = $tool->execute($parameters);
                
                // Format the result
                $result_html = $this->format_success_result($tool_name, $result);
                
                // Update the response
                $response = str_replace($original, $result_html, $response);
                
                mpai_log_debug('Tool execution successful', 'tool-call-detector');
            } catch (Exception $e) {
                // Log error
                mpai_log_error('Error executing tool: ' . $e->getMessage(), 'tool-call-detector', array(
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ));
                
                // Format error
                $error_html = $this->format_error_result($tool_name, $e->getMessage());
                
                // Update the response
                $response = str_replace($original, $error_html, $response);
            }
        }
        
        return $response;
    }
    
    /**
     * Format a successful tool execution result
     *
     * @param string $tool_name The name of the tool
     * @param mixed $result The result of the tool execution
     * @return string Formatted result
     */
    private function format_success_result($tool_name, $result) {
        // Convert result to string if it's not already
        if (is_array($result) || is_object($result)) {
            $result = json_encode($result, JSON_PRETTY_PRINT);
        }
        
        // Create a formatted result
        $formatted_result = "<div class=\"mpai-tool-result mpai-tool-success\">\n";
        $formatted_result .= "  <div class=\"mpai-tool-header\">\n";
        $formatted_result .= "    <span class=\"mpai-tool-name\">{$tool_name}</span>\n";
        $formatted_result .= "    <span class=\"mpai-tool-status mpai-tool-success\">Success</span>\n";
        $formatted_result .= "  </div>\n";
        $formatted_result .= "  <div class=\"mpai-tool-content\">\n";
        $formatted_result .= "    <pre>{$result}</pre>\n";
        $formatted_result .= "  </div>\n";
        $formatted_result .= "</div>";
        
        return $formatted_result;
    }
    
    /**
     * Format an error result
     *
     * @param string $tool_name The name of the tool
     * @param string $error_message The error message
     * @return string Formatted error
     */
    private function format_error_result($tool_name, $error_message) {
        // Create a formatted error
        $formatted_error = "<div class=\"mpai-tool-result mpai-tool-error\">\n";
        $formatted_error .= "  <div class=\"mpai-tool-header\">\n";
        $formatted_error .= "    <span class=\"mpai-tool-name\">{$tool_name}</span>\n";
        $formatted_error .= "    <span class=\"mpai-tool-status mpai-tool-error\">Error</span>\n";
        $formatted_error .= "  </div>\n";
        $formatted_error .= "  <div class=\"mpai-tool-content\">\n";
        $formatted_error .= "    <pre>{$error_message}</pre>\n";
        $formatted_error .= "  </div>\n";
        $formatted_error .= "</div>";
        
        return $formatted_error;
    }
    
    /**
     * Create JavaScript code for tool call detection
     *
     * @return string JavaScript code for tool call detection
     */
    public function get_js_detection_code() {
        // This function returns JavaScript code that can be used in the frontend
        // to detect tool calls in AI responses
        
        $js_code = <<<'EOT'
/**
 * Detect tool calls in an AI response
 * 
 * @param {string} response The AI response to check for tool calls
 * @return {Array} Array of detected tool calls
 */
function detectToolCalls(response) {
    console.log('Detecting tool calls in response');
    
    const toolCalls = [];
    
    // XML-style format: <tool:tool_name>{"param1": "value1", "param2": "value2"}</tool>
    detectXmlStyleToolCalls(response, toolCalls);
    
    // JSON format: {"name": "tool_name", "parameters": {"param1": "value1", "param2": "value2"}}
    detectJsonToolCalls(response, toolCalls);
    
    // HTML format (DOM-based)
    detectHtmlToolCalls(response, toolCalls);
    
    console.log(`Detected ${toolCalls.length} tool calls`);
    
    return toolCalls;
}

/**
 * Detect XML-style tool calls
 * 
 * @param {string} response The AI response
 * @param {Array} toolCalls Array to store detected tool calls
 */
function detectXmlStyleToolCalls(response, toolCalls) {
    // Pattern for XML-style tool calls: <tool:tool_name>{"param1": "value1", "param2": "value2"}</tool>
    const pattern = /<tool:([^>]+)>([\s\S]*?)<\/tool>/g;
    
    let match;
    while ((match = pattern.exec(response)) !== null) {
        const toolName = match[1];
        const parametersStr = match[2];
        
        try {
            // Parse parameters
            const parameters = JSON.parse(parametersStr);
            
            // Add to tool calls
            toolCalls.push({
                name: toolName,
                parameters: parameters,
                original: match[0],
                format: 'xml'
            });
        } catch (e) {
            console.error('Failed to parse tool parameters:', e);
        }
    }
}

/**
 * Detect JSON format tool calls
 * 
 * @param {string} response The AI response
 * @param {Array} toolCalls Array to store detected tool calls
 */
function detectJsonToolCalls(response, toolCalls) {
    // Pattern for JSON tool calls: {"name": "tool_name", "parameters": {"param1": "value1", "param2": "value2"}}
    const pattern = /```(?:json)?\s*(\{[\s\S]*?\})\s*```/g;
    
    let match;
    while ((match = pattern.exec(response)) !== null) {
        const jsonStr = match[1];
        
        try {
            // Parse JSON
            const jsonData = JSON.parse(jsonStr);
            
            // Check if this is a tool call
            if (jsonData.name && jsonData.parameters) {
                // Add to tool calls
                toolCalls.push({
                    name: jsonData.name,
                    parameters: jsonData.parameters,
                    original: match[0],
                    format: 'json'
                });
            } else if (jsonData.tool && jsonData.parameters) {
                // Legacy format with 'tool' instead of 'name'
                toolCalls.push({
                    name: jsonData.tool,
                    parameters: jsonData.parameters,
                    original: match[0],
                    format: 'json_legacy'
                });
            }
        } catch (e) {
            console.error('Failed to parse JSON tool call:', e);
        }
    }
}

/**
 * Detect HTML format tool calls
 * 
 * @param {string} response The AI response
 * @param {Array} toolCalls Array to store detected tool calls
 */
function detectHtmlToolCalls(response, toolCalls) {
    // Create a temporary div to parse HTML
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = response;
    
    // Find all tool call elements
    const toolCallElements = tempDiv.querySelectorAll('.mpai-tool-call');
    
    for (const element of toolCallElements) {
        const toolName = element.getAttribute('data-tool-name');
        const parametersStr = element.getAttribute('data-tool-parameters');
        
        if (toolName && parametersStr) {
            try {
                // Parse parameters
                const parameters = JSON.parse(parametersStr);
                
                // Add to tool calls
                toolCalls.push({
                    name: toolName,
                    parameters: parameters,
                    original: element.outerHTML,
                    format: 'html',
                    element: element
                });
            } catch (e) {
                console.error('Failed to parse HTML tool parameters:', e);
            }
        }
    }
}

/**
 * Execute detected tool calls
 * 
 * @param {Array} toolCalls Array of detected tool calls
 * @param {string} response Original response
 * @param {Function} executeToolCallback Callback function to execute a tool
 * @return {string} Updated response with tool call results
 */
function executeToolCalls(toolCalls, response, executeToolCallback) {
    console.log(`Executing ${toolCalls.length} tool calls`);
    
    // Create a copy of the response
    let updatedResponse = response;
    
    for (const toolCall of toolCalls) {
        const toolName = toolCall.name;
        const parameters = toolCall.parameters;
        const original = toolCall.original;
        
        // Only the standardized 'wpcli' tool ID is supported
        // Legacy tool IDs have been completely removed
        
        // Execute the tool using the callback
        executeToolCallback(toolName, parameters, function(result, error) {
            if (error) {
                // Format error
                const errorHtml = formatErrorResult(toolName, error);
                
                // Update the response
                updatedResponse = updatedResponse.replace(original, errorHtml);
            } else {
                // Format the result
                const resultHtml = formatSuccessResult(toolName, result);
                
                // Update the response
                updatedResponse = updatedResponse.replace(original, resultHtml);
            }
        });
    }
    
    return updatedResponse;
}

/**
 * Format a successful tool execution result
 * 
 * @param {string} toolName The name of the tool
 * @param {*} result The result of the tool execution
 * @return {string} Formatted result
 */
function formatSuccessResult(toolName, result) {
    // Convert result to string if it's not already
    if (typeof result === 'object') {
        result = JSON.stringify(result, null, 2);
    }
    
    // Create a formatted result
    const formattedResult = `<div class="mpai-tool-result mpai-tool-success">
  <div class="mpai-tool-header">
    <span class="mpai-tool-name">${toolName}</span>
    <span class="mpai-tool-status mpai-tool-success">Success</span>
  </div>
  <div class="mpai-tool-content">
    <pre>${result}</pre>
  </div>
</div>`;
    
    return formattedResult;
}

/**
 * Format an error result
 * 
 * @param {string} toolName The name of the tool
 * @param {string} errorMessage The error message
 * @return {string} Formatted error
 */
function formatErrorResult(toolName, errorMessage) {
    // Create a formatted error
    const formattedError = `<div class="mpai-tool-result mpai-tool-error">
  <div class="mpai-tool-header">
    <span class="mpai-tool-name">${toolName}</span>
    <span class="mpai-tool-status mpai-tool-error">Error</span>
  </div>
  <div class="mpai-tool-content">
    <pre>${errorMessage}</pre>
  </div>
</div>`;
    
    return formattedError;
}
EOT;
        
        return $js_code;
    }
}

/**
 * Get the tool call detector instance
 *
 * @return MPAI_Tool_Call_Detector The tool call detector instance
 */
function mpai_tool_call_detector() {
    return MPAI_Tool_Call_Detector::get_instance();
}