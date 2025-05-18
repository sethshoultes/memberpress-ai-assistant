<?php
/**
 * LLM Chat Adapter
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Llm\Services;

use MemberpressAiAssistant\Llm\ValueObjects\LlmRequest;
use MemberpressAiAssistant\Llm\ValueObjects\LlmResponse;
use MemberpressAiAssistant\Registry\ToolRegistry;
use MemberpressAiAssistant\Orchestration\ContextManager;
use MemberpressAiAssistant\Orchestration\MessageProtocol;

/**
 * Adapter for integrating LLM services with the chat interface
 */
class LlmChatAdapter {
    /**
     * LLM Orchestrator
     *
     * @var LlmOrchestrator
     */
    private $orchestrator;
    
    /**
     * Tool Registry
     *
     * @var ToolRegistry
     */
    private $toolRegistry;
    
    /**
     * Context Manager
     *
     * @var ContextManager
     */
    private $contextManager;
    
    /**
     * Available WordPress tools
     *
     * @var array
     */
    private $wpTools = [];

    /**
     * Constructor
     *
     * @param LlmOrchestrator $orchestrator The LLM orchestrator
     * @param ToolRegistry|null $toolRegistry The tool registry
     * @param ContextManager|null $contextManager The context manager
     */
    public function __construct(
        LlmOrchestrator $orchestrator,
        ?ToolRegistry $toolRegistry = null,
        ?ContextManager $contextManager = null
    ) {
        $this->orchestrator = $orchestrator;
        $this->toolRegistry = $toolRegistry;
        $this->contextManager = $contextManager;
        
        // Initialize WordPress tools if tool registry is available
        if ($this->toolRegistry) {
            $this->initializeWpTools();
        }
    }

    /**
     * Process a chat request
     *
     * @param string $message The user message
     * @param string|null $conversationId The conversation ID
     * @return array The response data
     */
    /**
     * Initialize WordPress tools
     *
     * @return void
     */
    private function initializeWpTools(): void {
        if (!$this->toolRegistry) {
            if (function_exists('error_log')) {
                error_log('MPAI Debug - Tool registry not available, cannot initialize WordPress tools');
            }
            return;
        }
        
        try {
            // Get all available tools from the registry
            $allTools = $this->toolRegistry->getAllTools();
            
            if (empty($allTools)) {
                if (function_exists('error_log')) {
                    error_log('MPAI Debug - No tools found in registry');
                }
                
                // Try to get tools from the registry using reflection
                $reflection = new \ReflectionClass($this->toolRegistry);
                $toolsProperty = $reflection->getProperty('tools');
                $toolsProperty->setAccessible(true);
                $allTools = $toolsProperty->getValue($this->toolRegistry);
                
                if (function_exists('error_log')) {
                    error_log('MPAI Debug - Tools found using reflection: ' . count($allTools));
                }
            }
            
            // Filter for WordPress-related tools
            foreach ($allTools as $toolId => $tool) {
                // Check if the tool is related to WordPress
                $isWpTool = false;
                
                // Check by ID
                if (stripos($toolId, 'WordPress') !== false ||
                    stripos($toolId, 'MemberPress') !== false ||
                    stripos($toolId, 'Content') !== false ||
                    stripos($toolId, 'WP') !== false) {
                    $isWpTool = true;
                }
                
                // Check by class name
                $className = get_class($tool);
                if (stripos($className, 'WordPress') !== false ||
                    stripos($className, 'MemberPress') !== false ||
                    stripos($className, 'Content') !== false ||
                    stripos($className, 'WP') !== false) {
                    $isWpTool = true;
                }
                
                if ($isWpTool) {
                    $this->wpTools[$toolId] = $tool;
                }
            }
            
            // If no WordPress tools found, add all tools as a fallback
            if (empty($this->wpTools) && !empty($allTools)) {
                $this->wpTools = $allTools;
                if (function_exists('error_log')) {
                    error_log('MPAI Debug - No WordPress-specific tools found, using all available tools');
                }
            }
            
            if (function_exists('error_log')) {
                error_log('MPAI Debug - Initialized WordPress tools: ' . implode(', ', array_keys($this->wpTools)));
            }
        } catch (\Exception $e) {
            if (function_exists('error_log')) {
                error_log('MPAI Debug - Error initializing WordPress tools: ' . $e->getMessage());
                error_log('MPAI Debug - Error trace: ' . $e->getTraceAsString());
            }
        }
    }

    /**
     * Process a chat request
     *
     * @param string $message The user message
     * @param string|null $conversationId The conversation ID
     * @return array The response data
     */
    public function processRequest(string $message, ?string $conversationId = null): array {
        // Store the conversation ID locally, don't pass it to the LLM provider
        $localConversationId = $conversationId ?? $this->generateConversationId();
        
        // Get conversation history if context manager is available
        $conversationHistory = [];
        if ($this->contextManager) {
            $conversationHistory = $this->contextManager->getConversationHistory($localConversationId) ?? [];
            
            if (function_exists('error_log')) {
                error_log('MPAI Debug - Retrieved conversation history: ' . count($conversationHistory) . ' messages');
            }
        }
        
        // Convert conversation history to LLM messages format
        $messages = $this->convertHistoryToMessages($conversationHistory);
        
        // Add the current user message
        $messages[] = ['role' => 'user', 'content' => $message];
        
        // Prepare tools for the LLM
        $tools = $this->prepareToolsForLlm();
        
        // Create a new LlmRequest
        $request = new LlmRequest(
            $messages,
            $tools,
            [
                'temperature' => 0.7,
                'max_tokens' => 2000
            ]
        );

        try {
            // Process the request with the orchestrator
            $response = $this->orchestrator->processRequest($request);
            
            // Check for tool calls in the response
            if ($response->hasToolCalls()) {
                $processedResponse = $this->processToolCalls($response, $localConversationId);
                
                // Store the conversation in context manager
                $this->storeConversation($message, $processedResponse['message'], $localConversationId);
                
                return $processedResponse;
            }
            
            // Store the conversation in context manager
            $this->storeConversation($message, $response->getContent(), $localConversationId);
            
            // Format the response for the chat interface
            return [
                'status' => 'success',
                'message' => $response->getContent(),
                'conversation_id' => $localConversationId,
                'timestamp' => time(),
            ];
        } catch (\Exception $e) {
            // Log the error with detailed information
            if (function_exists('error_log')) {
                error_log('MPAI LLM Chat Error: ' . $e->getMessage());
                error_log('MPAI LLM Chat Error Details: ' . $e->getTraceAsString());
            }

            // Return error response with more user-friendly message
            return [
                'status' => 'error',
                'message' => 'I apologize, but I encountered an error processing your request. The technical team has been notified.',
                'debug_message' => $e->getMessage(), // Include for debugging but not shown to user
                'conversation_id' => $localConversationId,
                'timestamp' => time(),
            ];
        }
    }

    /**
     * Process tool calls from the LLM response
     *
     * @param LlmResponse $response The LLM response
     * @param string $conversationId The conversation ID
     * @return array The processed response
     */
    private function processToolCalls(LlmResponse $response, string $conversationId): array {
        $toolCalls = $response->getToolCalls();
        $results = [];
        
        foreach ($toolCalls as $toolCall) {
            $toolName = $toolCall['name'];
            $arguments = $toolCall['arguments'];
            
            if (function_exists('error_log')) {
                error_log('MPAI Debug - Processing tool call: ' . $toolName);
                error_log('MPAI Debug - Tool arguments: ' . json_encode($arguments));
            }
            
            // Find the tool in our registry
            $tool = null;
            foreach ($this->wpTools as $wpTool) {
                if (method_exists($wpTool, $toolName)) {
                    $tool = $wpTool;
                    break;
                }
            }
            
            if ($tool) {
                try {
                    // Execute the tool
                    $result = call_user_func_array([$tool, $toolName], $arguments);
                    $results[] = [
                        'tool' => $toolName,
                        'result' => $result
                    ];
                    
                    if (function_exists('error_log')) {
                        error_log('MPAI Debug - Tool execution result: ' . json_encode($result));
                    }
                } catch (\Exception $e) {
                    $results[] = [
                        'tool' => $toolName,
                        'error' => $e->getMessage()
                    ];
                    
                    if (function_exists('error_log')) {
                        error_log('MPAI Debug - Tool execution error: ' . $e->getMessage());
                    }
                }
            } else {
                $results[] = [
                    'tool' => $toolName,
                    'error' => 'Tool not found'
                ];
                
                if (function_exists('error_log')) {
                    error_log('MPAI Debug - Tool not found: ' . $toolName);
                }
            }
        }
        
        // Format the results as a message
        $resultMessage = "I've processed your request using WordPress tools:\n\n";
        foreach ($results as $result) {
            $resultMessage .= "**" . $result['tool'] . "**: ";
            if (isset($result['error'])) {
                $resultMessage .= "Error: " . $result['error'] . "\n\n";
            } else {
                $resultMessage .= json_encode($result['result'], JSON_PRETTY_PRINT) . "\n\n";
            }
        }
        
        return [
            'status' => 'success',
            'message' => $resultMessage,
            'conversation_id' => $conversationId,
            'timestamp' => time(),
            'tool_results' => $results
        ];
    }
    
    /**
     * Convert conversation history to LLM messages format
     *
     * @param array $history The conversation history
     * @return array The messages in LLM format
     */
    private function convertHistoryToMessages(array $history): array {
        $messages = [];
        
        foreach ($history as $entry) {
            if (isset($entry['from']) && isset($entry['content'])) {
                $role = $entry['from'] === 'user' ? 'user' : 'assistant';
                $messages[] = [
                    'role' => $role,
                    'content' => $entry['content']
                ];
            }
        }
        
        return $messages;
    }
    
    /**
     * Store conversation in context manager
     *
     * @param string $userMessage The user message
     * @param string $assistantMessage The assistant message
     * @param string $conversationId The conversation ID
     * @return void
     */
    private function storeConversation(string $userMessage, string $assistantMessage, string $conversationId): void {
        if (!$this->contextManager) {
            return;
        }
        
        // Create user message using MessageProtocol
        $userMessageObj = MessageProtocol::createRequest(
            'user',
            'assistant',
            $userMessage,
            ['timestamp' => time()]
        );
        
        // Create assistant message using MessageProtocol
        $assistantMessageObj = MessageProtocol::createResponse(
            'assistant',
            'user',
            $assistantMessage,
            $userMessageObj->getId(),
            ['timestamp' => time()]
        );
        
        // Add messages to history
        $this->contextManager->addMessageToHistory($userMessageObj, $conversationId);
        $this->contextManager->addMessageToHistory($assistantMessageObj, $conversationId);
        
        if (function_exists('error_log')) {
            error_log('MPAI Debug - Stored conversation in context manager for ID: ' . $conversationId);
        }
    }
    
    /**
     * Prepare WordPress tools for LLM
     *
     * @return array The tools in LLM format
     */
    private function prepareToolsForLlm(): array {
        if (empty($this->wpTools)) {
            return [];
        }
        
        $llmTools = [];
        
        foreach ($this->wpTools as $toolId => $tool) {
            // Get tool methods using reflection
            $reflection = new \ReflectionClass($tool);
            $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
            
            foreach ($methods as $method) {
                // Skip constructor and inherited methods
                if ($method->getName() === '__construct' || $method->getDeclaringClass()->getName() !== get_class($tool)) {
                    continue;
                }
                
                // Get method parameters
                $parameters = [];
                $required = [];
                
                foreach ($method->getParameters() as $param) {
                    $parameters[$param->getName()] = [
                        'type' => 'string',
                        'description' => 'Parameter ' . $param->getName()
                    ];
                    
                    if (!$param->isOptional()) {
                        $required[] = $param->getName();
                    }
                }
                
                // Create tool definition
                $llmTools[] = [
                    'name' => $method->getName(),
                    'description' => 'WordPress tool: ' . $method->getName(),
                    'parameters' => [
                        'type' => 'object',
                        'properties' => $parameters,
                        'required' => $required
                    ]
                ];
            }
        }
        
        if (function_exists('error_log')) {
            error_log('MPAI Debug - Prepared ' . count($llmTools) . ' WordPress tools for LLM');
        }
        
        return $llmTools;
    }
    
    /**
     * Generate a unique conversation ID
     *
     * @return string Unique conversation ID
     */
    private function generateConversationId(): string {
        return 'conv_' . uniqid('', true);
    }
}