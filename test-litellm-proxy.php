<?php
/**
 * Test LiteLLM Proxy Connection
 * 
 * This script tests the connection to the LiteLLM proxy
 * Run this file to verify the proxy is working correctly
 */

// Include WordPress
require_once dirname(__FILE__) . '/../../../wp-config.php';

// Include the plugin classes
require_once dirname(__FILE__) . '/src/Llm/Providers/OpenAiClient.php';
require_once dirname(__FILE__) . '/src/Llm/Providers/AbstractLlmClient.php';
require_once dirname(__FILE__) . '/src/Llm/ValueObjects/LlmRequest.php';
require_once dirname(__FILE__) . '/src/Llm/ValueObjects/LlmResponse.php';
require_once dirname(__FILE__) . '/src/Llm/ValueObjects/LlmProviderConfig.php';

use MemberpressAiAssistant\Llm\Providers\OpenAiClient;
use MemberpressAiAssistant\Llm\ValueObjects\LlmRequest;

echo "<h1>Testing LiteLLM Proxy Connection</h1>\n";

try {
    // Create OpenAI client (which now points to LiteLLM proxy)
    $client = new OpenAiClient('3d82afe47512fcb1faba41cc1c9c796d3dbe8624b0a5c62fa68e6d38f0bf6d72');
    
    echo "<h2>✅ OpenAI Client Created Successfully</h2>\n";
    echo "<p>Using LiteLLM proxy at: https://64.23.251.16.nip.io</p>\n";
    
    // Create a test request
    $messages = [
        ['role' => 'user', 'content' => 'Hello! This is a test from MemberPress AI Assistant via LiteLLM proxy.']
    ];
    
    $request = new LlmRequest($messages, [], ['model' => 'gpt-3.5-turbo']);
    
    echo "<h2>🚀 Sending Test Request...</h2>\n";
    echo "<p>Model: gpt-3.5-turbo</p>\n";
    echo "<p>Message: " . htmlspecialchars($messages[0]['content']) . "</p>\n";
    
    // Send the request
    $response = $client->sendMessage($request);
    
    if ($response->getContent()) {
        echo "<h2>✅ SUCCESS! Response Received:</h2>\n";
        echo "<div style='background: #f0f0f0; padding: 15px; border-radius: 5px;'>\n";
        echo "<strong>Response:</strong> " . htmlspecialchars($response->getContent()) . "\n";
        echo "</div>\n";
        
        echo "<h3>📊 Response Details:</h3>\n";
        echo "<ul>\n";
        echo "<li><strong>Provider:</strong> " . htmlspecialchars($response->getProvider()) . "</li>\n";
        echo "<li><strong>Model:</strong> " . htmlspecialchars($response->getModel()) . "</li>\n";
        echo "<li><strong>Usage:</strong> " . json_encode($response->getUsage()) . "</li>\n";
        echo "</ul>\n";
        
    } else {
        echo "<h2>❌ Error: No content in response</h2>\n";
        echo "<pre>" . print_r($response, true) . "</pre>\n";
    }
    
} catch (Exception $e) {
    echo "<h2>❌ ERROR:</h2>\n";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<h3>Stack Trace:</h3>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
}

echo "<hr>\n";
echo "<h2>🔧 Configuration Summary:</h2>\n";
echo "<ul>\n";
echo "<li><strong>LiteLLM Proxy URL:</strong> https://64.23.251.16.nip.io</li>\n";
echo "<li><strong>API Key:</strong> 3d82afe47512fcb1faba41cc1c9c796d3dbe8624b0a5c62fa68e6d38f0bf6d72</li>\n";
echo "<li><strong>Available Models:</strong> gpt-3.5-turbo (and others via proxy)</li>\n";
echo "<li><strong>API Keys Removed:</strong> ✅ No longer stored in WordPress</li>\n";
echo "</ul>\n";

echo "<p><em>This test validates that your MemberPress AI Assistant plugin can successfully communicate with the LiteLLM proxy, eliminating the need for users to provide their own API keys.</em></p>\n";
?>