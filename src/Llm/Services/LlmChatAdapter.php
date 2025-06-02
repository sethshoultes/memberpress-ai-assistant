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
use MemberpressAiAssistant\Utilities\TableFormatter;

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
            \MemberpressAiAssistant\Utilities\LoggingUtility::warning('Tool registry not available, cannot initialize WordPress tools');
            return;
        }
        
        try {
            // Get all available tools from the registry
            $allTools = $this->toolRegistry->getAllTools();
            
            if (empty($allTools)) {
                \MemberpressAiAssistant\Utilities\LoggingUtility::debug('No tools found in registry');
                
                // Try to get tools from the registry using reflection
                $reflection = new \ReflectionClass($this->toolRegistry);
                $toolsProperty = $reflection->getProperty('tools');
                $toolsProperty->setAccessible(true);
                $allTools = $toolsProperty->getValue($this->toolRegistry);
                
                \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Tools found using reflection: ' . count($allTools));
            }
            
            // Filter for WordPress-related tools
            foreach ($allTools as $toolId => $tool) {
                // Check if the tool is related to WordPress
                $isWpTool = false;
                
                // Check by ID
                if (stripos($toolId, 'WordPress') !== false ||
                    stripos($toolId, 'MemberPress') !== false ||
                    stripos($toolId, 'memberpress') !== false ||
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
                    \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[MEMBERSHIP DEBUG] Added tool to wpTools: ' . $toolId . ' (' . $className . ')');
                }
            }
            
            // If no WordPress tools found, add all tools as a fallback
            if (empty($this->wpTools) && !empty($allTools)) {
                $this->wpTools = $allTools;
                \MemberpressAiAssistant\Utilities\LoggingUtility::debug('No WordPress-specific tools found, using all available tools');
            }
            
            \MemberpressAiAssistant\Utilities\LoggingUtility::info('Initialized WordPress tools: ' . implode(', ', array_keys($this->wpTools)));
        } catch (\Exception $e) {
            \MemberpressAiAssistant\Utilities\LoggingUtility::error('Error initializing WordPress tools: ' . $e->getMessage());
            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Error trace: ' . $e->getTraceAsString());
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
        \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Processing request: ' . $message);
        \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Conversation ID: ' . ($conversationId ?? 'new conversation'));
        
        // Store the conversation ID locally, don't pass it to the LLM provider
        $localConversationId = $conversationId ?? $this->generateConversationId();
        
        // Get conversation history if context manager is available
        $conversationHistory = [];
        if ($this->contextManager) {
            $conversationHistory = $this->contextManager->getConversationHistory($localConversationId) ?? [];
            
            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Retrieved conversation history: ' . count($conversationHistory) . ' messages');
        } else {
            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('No context manager available');
        }
        
        // Convert conversation history to LLM messages format
        $messages = $this->convertHistoryToMessages($conversationHistory);
        
        // Add the current user message
        $messages[] = ['role' => 'user', 'content' => $message];
        
        // Prepare tools for the LLM
        $tools = $this->prepareToolsForLlm();
        
        \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[TOOL SELECTION DEBUG] Final LLM request preparation', [
            'message_content' => $message,
            'total_tools_available' => count($tools),
            'membership_tools' => array_filter(array_column($tools, 'name'), function($name) {
                return strpos($name, 'membership') !== false || strpos($name, 'list') !== false;
            }),
            'conversation_id' => $localConversationId
        ]);
        
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
            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Sending request to LlmOrchestrator');
            
            // Process the request with the orchestrator
            $response = $this->orchestrator->processRequest($request);
            
            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Received response from LlmOrchestrator');
            
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
            \MemberpressAiAssistant\Utilities\LoggingUtility::error('LLM Chat Error: ' . $e->getMessage());
            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Error Details: ' . $e->getTraceAsString());

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
        
        \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[TOOL SELECTION DEBUG] Processing tool calls from LLM', [
            'total_calls' => count($toolCalls),
            'tool_names' => array_column($toolCalls, 'name')
        ]);
        
        foreach ($toolCalls as $toolCall) {
            $toolName = $toolCall['name'];
            $arguments = $toolCall['arguments'];
            
            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[TOOL SELECTION DEBUG] Processing individual tool call', [
                'tool_name' => $toolName,
                'arguments' => $arguments,
                'is_wordpress_tool' => strpos($toolName, 'wordpress_') === 0,
                'is_memberpress_tool' => in_array($toolName, ['create_membership', 'list_memberships']),
                'is_membership_related' => strpos($toolName, 'membership') !== false
            ]);
            
            // Check if this is a WordPress tool operation
            if (strpos($toolName, 'wordpress_') === 0) {
                $this->processWordPressToolOperation($toolName, $arguments, $results);
                continue;
            }
            
            // Check if this is a MemberPress tool operation
            if ($toolName === 'create_membership' || $toolName === 'list_memberships') {
                $this->processMemberPressToolOperation($toolName, $arguments, $results);
                continue;
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
                    
                    \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Tool execution result: ' . json_encode($result));
                } catch (\Exception $e) {
                    $results[] = [
                        'tool' => $toolName,
                        'error' => $e->getMessage()
                    ];
                    
                    \MemberpressAiAssistant\Utilities\LoggingUtility::warning('Tool execution error: ' . $e->getMessage());
                }
            } else {
                $results[] = [
                    'tool' => $toolName,
                    'error' => 'Tool not found'
                ];
                
                \MemberpressAiAssistant\Utilities\LoggingUtility::warning('Tool not found: ' . $toolName);
            }
        }
        
        // Format the results as a message
        $resultMessage = "I've processed your request using WordPress tools:\n\n";
        foreach ($results as $result) {
            $resultMessage .= "**" . $result['tool'] . "**: ";
            if (isset($result['error'])) {
                $resultMessage .= "Error: " . $result['error'] . "\n\n";
            } else {
                // Check if this is a list_plugins result
                if ($result['tool'] === 'wordpress_list_plugins' && isset($result['result']['data']['plugins'])) {
                    $plugins = $result['result']['data']['plugins'];
                    $summary = [
                        'total' => $result['result']['data']['total'] ?? count($plugins),
                        'active' => array_sum(array_column($plugins, 'active')),
                        'inactive' => count($plugins) - array_sum(array_column($plugins, 'active')),
                        'update_available' => isset($result['result']['data']['update_available']) ? $result['result']['data']['update_available'] : 0
                    ];
                    
                    $resultMessage .= "\n" . TableFormatter::formatPluginList($plugins, [
                        'format' => TableFormatter::FORMAT_HTML,
                        'summary' => $summary
                    ]);
                }
                // Check if this is a list_posts result
                else if ($result['tool'] === 'wordpress_list_posts' && isset($result['result']['data']['posts'])) {
                    $posts = $result['result']['data']['posts'];
                    $summary = [
                        'total' => $result['result']['data']['total'] ?? count($posts),
                        'limit' => $result['result']['data']['limit'] ?? 10,
                        'offset' => $result['result']['data']['offset'] ?? 0,
                        'max_pages' => $result['result']['data']['max_pages'] ?? 1
                    ];
                    
                    $resultMessage .= "\n" . TableFormatter::formatPostList($posts, [
                        'format' => TableFormatter::FORMAT_HTML,
                        'summary' => $summary
                    ]);
                }
                // Check if this is a list_pages result
                else if ($result['tool'] === 'wordpress_list_pages' && isset($result['result']['data']['pages'])) {
                    $pages = $result['result']['data']['pages'];
                    $summary = [
                        'total' => $result['result']['data']['total'] ?? count($pages),
                        'limit' => $result['result']['data']['limit'] ?? 10,
                        'offset' => $result['result']['data']['offset'] ?? 0,
                        'max_pages' => $result['result']['data']['max_pages'] ?? 1
                    ];
                    
                    $resultMessage .= "\n" . TableFormatter::formatPageList($pages, [
                        'format' => TableFormatter::FORMAT_HTML,
                        'summary' => $summary
                    ]);
                }
                // Check if this is a list_comments result
                else if ($result['tool'] === 'wordpress_list_comments' && isset($result['result']['data']['comments'])) {
                    $comments = $result['result']['data']['comments'];
                    $summary = [
                        'total' => $result['result']['data']['total'] ?? count($comments),
                        'limit' => $result['result']['data']['limit'] ?? 10,
                        'offset' => $result['result']['data']['offset'] ?? 0,
                        'max_pages' => $result['result']['data']['max_pages'] ?? 1
                    ];
                    
                    $resultMessage .= "\n" . TableFormatter::formatCommentList($comments, [
                        'format' => TableFormatter::FORMAT_HTML,
                        'summary' => $summary
                    ]);
                }
                // Check if this is a list_users result
                else if ($result['tool'] === 'wordpress_list_users' && isset($result['result']['data']['users'])) {
                    $users = $result['result']['data']['users'];
                    $summary = [
                        'total' => $result['result']['data']['total'] ?? count($users),
                        'limit' => $result['result']['data']['limit'] ?? 10,
                        'offset' => $result['result']['data']['offset'] ?? 0
                    ];
                    
                    $resultMessage .= "\n" . TableFormatter::formatUserList($users, [
                        'format' => TableFormatter::FORMAT_HTML,
                        'summary' => $summary
                    ]);
                }
                // Check if this is a list_memberships result
                else if ($result['tool'] === 'wordpress_memberpress_list_memberships' && isset($result['result']['data']['memberships'])) {
                    $memberships = $result['result']['data']['memberships'];
                    $summary = [
                        'total' => $result['result']['data']['total'] ?? count($memberships),
                        'limit' => $result['result']['data']['limit'] ?? 10,
                        'offset' => $result['result']['data']['offset'] ?? 0,
                        'message' => $result['result']['message'] ?? 'MemberPress memberships'
                    ];
                    
                    $resultMessage .= "\n" . TableFormatter::formatMembershipList($memberships, [
                        'format' => TableFormatter::FORMAT_HTML,
                        'summary' => $summary,
                        'title' => 'MemberPress Memberships'
                    ]);
                }
                // Check if this is a list_membership_levels result
                else if ($result['tool'] === 'wordpress_memberpress_list_membership_levels' && isset($result['result']['data']['levels'])) {
                    $levels = $result['result']['data']['levels'];
                    $summary = [
                        'total' => $result['result']['data']['total'] ?? count($levels),
                        'limit' => $result['result']['data']['limit'] ?? 10,
                        'offset' => $result['result']['data']['offset'] ?? 0
                    ];
                    
                    $resultMessage .= "\n" . TableFormatter::formatMembershipLevelList($levels, [
                        'format' => TableFormatter::FORMAT_HTML,
                        'summary' => $summary,
                        'title' => 'MemberPress Membership Levels'
                    ]);
                }
                // Check if this is a list_memberships result
                else if ($result['tool'] === 'wordpress_list_memberships' && isset($result['result']['data']['memberships'])) {
                    $memberships = $result['result']['data']['memberships'];
                    $summary = [
                        'total' => $result['result']['data']['total'] ?? count($memberships),
                        'limit' => $result['result']['data']['limit'] ?? 10,
                        'offset' => $result['result']['data']['offset'] ?? 0,
                        'max_pages' => $result['result']['data']['max_pages'] ?? 1
                    ];
                    
                    $resultMessage .= "\n" . TableFormatter::formatMembershipList($memberships, [
                        'format' => TableFormatter::FORMAT_HTML,
                        'summary' => $summary
                    ]);
                }
                // Check if this is a list_memberships result (direct MemberPress tool call)
                else if ($result['tool'] === 'list_memberships' && isset($result['result']['data']['memberships'])) {
                    $memberships = $result['result']['data']['memberships'];
                    $summary = [
                        'total' => $result['result']['data']['total'] ?? count($memberships),
                        'limit' => $result['result']['data']['limit'] ?? 10,
                        'offset' => $result['result']['data']['offset'] ?? 0,
                        'message' => $result['result']['message'] ?? 'MemberPress memberships'
                    ];
                    
                    $resultMessage .= "\n" . TableFormatter::formatMembershipLevelList($memberships, [
                        'format' => TableFormatter::FORMAT_HTML,
                        'summary' => $summary,
                        'title' => 'MemberPress Memberships'
                    ]);
                }
                else {
                    $resultMessage .= json_encode($result['result'], JSON_PRETTY_PRINT) . "\n\n";
                }
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
     * Process WordPress tool operation
     *
     * @param string $toolName The tool name
     * @param array $arguments The tool arguments
     * @param array &$results The results array to add to
     * @return void
     */
    private function processWordPressToolOperation(string $toolName, array $arguments, array &$results): void {
        // Extract the operation name from the tool name
        $operation = str_replace('wordpress_', '', $toolName);
        
        \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Processing WordPress tool operation: ' . $operation);
        
        // Find the WordPress tool
        $wordpressTool = null;
        foreach ($this->wpTools as $wpTool) {
            if ($wpTool instanceof \MemberpressAiAssistant\Tools\WordPressTool) {
                $wordpressTool = $wpTool;
                break;
            }
        }
        
        if (!$wordpressTool) {
            $results[] = [
                'tool' => $toolName,
                'error' => 'WordPress tool not found'
            ];
            
            \MemberpressAiAssistant\Utilities\LoggingUtility::warning('WordPress tool not found');
            return;
        }
        
        try {
            // Make sure the operation is included in the arguments
            if (!isset($arguments['operation'])) {
                $arguments['operation'] = $operation;
            }
            
            // Execute the WordPress tool with the operation
            $result = $wordpressTool->execute($arguments);
            $results[] = [
                'tool' => $toolName,
                'result' => $result
            ];
            
            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('WordPress tool operation result: ' . json_encode($result));
        } catch (\Exception $e) {
            $results[] = [
                'tool' => $toolName,
                'error' => $e->getMessage()
            ];
            
            \MemberpressAiAssistant\Utilities\LoggingUtility::warning('WordPress tool operation error: ' . $e->getMessage());
        }
    }
    
    /**
     * Process MemberPress tool operation
     *
     * @param string $toolName The tool name
     * @param array $arguments The tool arguments
     * @param array &$results The results array to add to
     * @return void
     */
    private function processMemberPressToolOperation(string $toolName, array $arguments, array &$results): void {
        \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[MEMBERSHIP DEBUG] Processing MemberPress tool operation: ' . $toolName);
        \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[MEMBERSHIP DEBUG] Tool arguments: ' . json_encode($arguments));
        
        // Find the MemberPress tool
        $memberPressTool = null;
        foreach ($this->wpTools as $wpTool) {
            if ($wpTool instanceof \MemberpressAiAssistant\Tools\MemberPressTool) {
                $memberPressTool = $wpTool;
                break;
            }
        }
        
        if (!$memberPressTool) {
            $results[] = [
                'tool' => $toolName,
                'error' => 'MemberPress tool not found'
            ];
            
            \MemberpressAiAssistant\Utilities\LoggingUtility::warning('[MEMBERSHIP DEBUG] MemberPress tool not found');
            return;
        }
        
        try {
            // Execute the MemberPress tool with the operation
            if ($toolName === 'create_membership') {
                $arguments['operation'] = 'create_membership';
                $result = $memberPressTool->execute($arguments);
            } elseif ($toolName === 'list_memberships') {
                // Use the standard execute method with proper operation parameter
                $arguments['operation'] = 'list_memberships';
                $result = $memberPressTool->execute($arguments);
            } else {
                throw new \Exception('Unknown MemberPress operation: ' . $toolName);
            }
            
            $results[] = [
                'tool' => $toolName,
                'result' => $result
            ];
            
            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[MEMBERSHIP DEBUG] MemberPress tool operation result: ' . json_encode($result));
        } catch (\Exception $e) {
            $results[] = [
                'tool' => $toolName,
                'error' => $e->getMessage()
            ];
            
            \MemberpressAiAssistant\Utilities\LoggingUtility::warning('[MEMBERSHIP DEBUG] MemberPress tool operation error: ' . $e->getMessage());
        }
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
        
        \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Stored conversation in context manager for ID: ' . $conversationId);
    }
    
    /**
     * Prepare WordPress tools for LLM
     *
     * @return array The tools in LLM format
     */
    private function prepareToolsForLlm(): array {
        if (empty($this->wpTools)) {
            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[TOOL SELECTION DEBUG] No WordPress tools available');
            return [];
        }
        
        $llmTools = [];
        
        \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[TOOL SELECTION DEBUG] Starting tool preparation for LLM', [
            'total_tools' => count($this->wpTools),
            'tool_ids' => array_keys($this->wpTools)
        ]);
        
        foreach ($this->wpTools as $toolId => $tool) {
            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[TOOL SELECTION DEBUG] Processing tool for LLM: ' . $toolId . ' (' . get_class($tool) . ')');
            
            // Special handling for WordPressTool
            if ($tool instanceof \MemberpressAiAssistant\Tools\WordPressTool) {
                $this->prepareWordPressToolOperations($tool, $llmTools);
                continue;
            }
            
            // Special handling for MemberPressTool
            if ($tool instanceof \MemberpressAiAssistant\Tools\MemberPressTool) {
                $this->prepareMemberPressToolOperations($tool, $llmTools);
                continue;
            }
            
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
                
                \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[MEMBERSHIP DEBUG] Added method to LLM tools: ' . $method->getName());
            }
        }
        
        // Log final tool list for debugging
        $toolNames = array_column($llmTools, 'name');
        $membershipTools = array_filter($toolNames, function($name) {
            return strpos($name, 'membership') !== false || strpos($name, 'list') !== false;
        });
        
        \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[TOOL SELECTION DEBUG] Prepared tools for LLM', [
            'total_tools' => count($llmTools),
            'all_tool_names' => $toolNames,
            'membership_related_tools' => $membershipTools
        ]);
        
        return $llmTools;
    }
    
    /**
     * Prepare WordPress tool operations
     *
     * @param \MemberpressAiAssistant\Tools\WordPressTool $tool The WordPress tool
     * @param array &$llmTools The LLM tools array to add to
     * @return void
     */
    private function prepareWordPressToolOperations(\MemberpressAiAssistant\Tools\WordPressTool $tool, array &$llmTools): void {
        // Get the valid operations using reflection
        $reflection = new \ReflectionClass($tool);
        
        try {
            // Get the validOperations property
            $validOperationsProperty = $reflection->getProperty('validOperations');
            $validOperationsProperty->setAccessible(true);
            $validOperations = $validOperationsProperty->getValue($tool);
            
            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[TOOL SELECTION DEBUG] WordPress tool valid operations', [
                'operations' => $validOperations
            ]);
            
            // Add each operation as a tool
            foreach ($validOperations as $operation) {
                // Skip membership-related operations to avoid conflicts
                if (strpos($operation, 'membership') !== false) {
                    \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[TOOL SELECTION DEBUG] Skipping WordPress membership operation to avoid conflicts', [
                        'skipped_operation' => $operation
                    ]);
                    continue;
                }
                
                // Create tool definition for each operation
                $toolDef = [
                    'name' => 'wordpress_' . $operation,
                    'description' => 'WordPress tool: ' . $operation,
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            // Add common parameters for all operations
                            'operation' => [
                                'type' => 'string',
                                'description' => 'The operation to perform',
                                'enum' => [$operation]
                            ]
                        ],
                        'required' => ['operation']
                    ]
                ];
                $llmTools[] = $toolDef;
                
                \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[TOOL SELECTION DEBUG] Added WordPress tool operation', [
                    'operation' => $operation,
                    'tool_name' => $toolDef['name']
                ]);
            }
            
            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[TOOL SELECTION DEBUG] Completed WordPress tool operations preparation');
        } catch (\Exception $e) {
            \MemberpressAiAssistant\Utilities\LoggingUtility::error('Error preparing WordPress tool operations: ' . $e->getMessage());
        }
    }
    
    /**
     * Prepare MemberPress tool operations
     *
     * @param \MemberpressAiAssistant\Tools\MemberPressTool $tool The MemberPress tool
     * @param array &$llmTools The LLM tools array to add to
     * @return void
     */
    private function prepareMemberPressToolOperations(\MemberpressAiAssistant\Tools\MemberPressTool $tool, array &$llmTools): void {
        \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[TOOL SELECTION DEBUG] Preparing MemberPress tool operations');
        
        // Add create_membership operation
        $createTool = [
            'name' => 'create_membership',
            'description' => 'Create a new MemberPress membership with specified title, price, and billing terms',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'title' => [
                        'type' => 'string',
                        'description' => 'The title/name of the membership'
                    ],
                    'price' => [
                        'type' => 'number',
                        'description' => 'The price of the membership'
                    ],
                    'period' => [
                        'type' => 'string',
                        'description' => 'The billing period (weekly, monthly, yearly, lifetime)',
                        'enum' => ['weekly', 'monthly', 'yearly', 'lifetime']
                    ]
                ],
                'required' => ['title', 'price', 'period']
            ]
        ];
        $llmTools[] = $createTool;
        
        // Add list_memberships operation
        $listTool = [
            'name' => 'list_memberships',
            'description' => 'List all MemberPress memberships - DETERMINISTIC TOOL for membership data summary',
            'parameters' => [
                'type' => 'object',
                'properties' => (object)[],  // Empty object, not empty array
                'required' => []
            ]
        ];
        $llmTools[] = $listTool;
        
        \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[TOOL SELECTION DEBUG] Added MemberPress tool operations', [
            'operations_added' => ['create_membership', 'list_memberships'],
            'create_tool_name' => $createTool['name'],
            'list_tool_name' => $listTool['name'],
            'list_tool_description' => $listTool['description']
        ]);
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