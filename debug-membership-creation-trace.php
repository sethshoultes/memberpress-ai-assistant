<?php
/**
 * Debug script to trace membership creation requests
 * This will help identify where the blog post content is coming from
 */

// Add comprehensive logging to trace the entire membership creation flow
add_action('init', function() {
    // Hook into the chat interface to log AI requests and responses
    add_filter('mpai_before_ai_request', function($request_data) {
        error_log('[MEMBERSHIP DEBUG] AI Request Data: ' . json_encode($request_data, JSON_PRETTY_PRINT));
        return $request_data;
    });
    
    // Hook into AI responses to see what the AI is actually returning
    add_filter('mpai_after_ai_response', function($response_data) {
        error_log('[MEMBERSHIP DEBUG] AI Response Data: ' . json_encode($response_data, JSON_PRETTY_PRINT));
        
        // Check if response contains tool calls
        if (isset($response_data['tool_calls'])) {
            error_log('[MEMBERSHIP DEBUG] Tool calls found in AI response: ' . json_encode($response_data['tool_calls'], JSON_PRETTY_PRINT));
        } else {
            error_log('[MEMBERSHIP DEBUG] NO TOOL CALLS in AI response - this might be the problem!');
        }
        
        // Check if response contains blog post content
        if (isset($response_data['content']) && stripos($response_data['content'], 'cat') !== false) {
            error_log('[MEMBERSHIP DEBUG] BLOG POST CONTENT DETECTED in AI response: ' . substr($response_data['content'], 0, 200) . '...');
        }
        
        return $response_data;
    });
    
    // Hook into tool registry to verify memberpress tool is registered
    add_action('mpai_tools_registered', function() {
        $registry = \MemberpressAiAssistant\Registry\ToolRegistry::getInstance();
        $tools = $registry->getAllTools();
        
        error_log('[MEMBERSHIP DEBUG] Registered tools: ' . implode(', ', array_keys($tools)));
        
        if (isset($tools['memberpress'])) {
            error_log('[MEMBERSHIP DEBUG] MemberPress tool IS registered');
        } else {
            error_log('[MEMBERSHIP DEBUG] MemberPress tool NOT registered - this is the problem!');
        }
    });
    
    // Hook into agent orchestrator to trace agent selection
    add_filter('mpai_agent_selected', function($agent_data, $request) {
        error_log('[MEMBERSHIP DEBUG] Agent selected: ' . json_encode([
            'agent_name' => $agent_data['agent']->getAgentName(),
            'score' => $agent_data['score'],
            'request_intent' => $request['intent'] ?? 'unknown'
        ], JSON_PRETTY_PRINT));
        return $agent_data;
    }, 10, 2);
    
    // Hook into MemberPress agent to trace tool execution
    add_action('mpai_memberpress_tool_called', function($operation, $parameters, $result) {
        error_log('[MEMBERSHIP DEBUG] MemberPress tool called: ' . json_encode([
            'operation' => $operation,
            'parameters' => $parameters,
            'result' => $result
        ], JSON_PRETTY_PRINT));
    }, 10, 3);
    
    // Hook into blog post processing to see if it's being triggered
    add_action('mpai_blog_post_processed', function($content) {
        error_log('[MEMBERSHIP DEBUG] BLOG POST PROCESSING TRIGGERED - this should NOT happen for membership requests!');
        error_log('[MEMBERSHIP DEBUG] Blog post content: ' . substr($content, 0, 200) . '...');
    });
    
    // Hook into chat interface response processing
    add_filter('mpai_chat_response', function($response, $request) {
        error_log('[MEMBERSHIP DEBUG] Final chat response: ' . json_encode([
            'status' => $response['status'] ?? 'unknown',
            'message_preview' => substr($response['message'] ?? '', 0, 100),
            'has_tool_result' => isset($response['tool_result']),
            'request_intent' => $request['intent'] ?? 'unknown'
        ], JSON_PRETTY_PRINT));
        return $response;
    }, 10, 2);
});

// Add a test function to simulate membership creation
function test_membership_creation_debug() {
    error_log('[MEMBERSHIP DEBUG] ========== STARTING MEMBERSHIP CREATION TEST ==========');
    
    // Simulate the request that's causing issues
    $test_request = [
        'message' => 'create a membership called Silver for $20 per week',
        'user_id' => 1,
        'timestamp' => time()
    ];
    
    error_log('[MEMBERSHIP DEBUG] Test request: ' . json_encode($test_request, JSON_PRETTY_PRINT));
    
    // Try to process through the orchestrator
    try {
        global $mpai_container;
        if ($mpai_container && $mpai_container->bound('orchestrator')) {
            $orchestrator = $mpai_container->make('orchestrator');
            $result = $orchestrator->processUserRequest($test_request);
            
            error_log('[MEMBERSHIP DEBUG] Orchestrator result: ' . json_encode($result, JSON_PRETTY_PRINT));
        } else {
            error_log('[MEMBERSHIP DEBUG] Orchestrator not available in container');
        }
    } catch (Exception $e) {
        error_log('[MEMBERSHIP DEBUG] Error in orchestrator: ' . $e->getMessage());
        error_log('[MEMBERSHIP DEBUG] Stack trace: ' . $e->getTraceAsString());
    }
    
    error_log('[MEMBERSHIP DEBUG] ========== MEMBERSHIP CREATION TEST COMPLETE ==========');
}

// Add admin action to trigger the test
add_action('wp_ajax_test_membership_debug', 'test_membership_creation_debug');
add_action('wp_ajax_nopriv_test_membership_debug', 'test_membership_creation_debug');

// Add a simple way to trigger the test via URL parameter
if (isset($_GET['test_membership_debug']) && current_user_can('manage_options')) {
    add_action('init', 'test_membership_creation_debug');
}

error_log('[MEMBERSHIP DEBUG] Debug script loaded. Add ?test_membership_debug=1 to any admin URL to trigger test.');