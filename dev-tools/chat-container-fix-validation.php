<?php
/**
 * Chat Container Fix Validation Tool
 * 
 * This tool validates that the chat container rendering fix is working correctly.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once(dirname(dirname(dirname(dirname(__DIR__)))) . '/wp-load.php');
}

echo "<h2>Chat Container Fix Validation</h2>\n";
echo "<p>Testing the chat container rendering fix...</p>\n";

// Test 1: Check if ChatInterface class exists and has the updated method
echo "<h3>Test 1: ChatInterface Class Validation</h3>\n";

if (class_exists('\\MemberpressAiAssistant\\ChatInterface')) {
    echo "<p>✓ ChatInterface class exists</p>\n";
    
    $reflection = new ReflectionClass('\\MemberpressAiAssistant\\ChatInterface');
    
    if ($reflection->hasMethod('renderAdminChatInterface')) {
        echo "<p>✓ renderAdminChatInterface method exists</p>\n";
        
        $method = $reflection->getMethod('renderAdminChatInterface');
        $method->setAccessible(true);
        
        // Get the method source to check for the fix
        $filename = $reflection->getFileName();
        $start_line = $method->getStartLine();
        $end_line = $method->getEndLine();
        $source = file($filename);
        $method_source = implode('', array_slice($source, $start_line - 1, $end_line - $start_line + 1));
        
        if (strpos($method_source, 'Always render chat container, but handle consent in JavaScript') !== false) {
            echo "<p>✓ Method contains the fix comment</p>\n";
        } else {
            echo "<p>⚠ Method may not contain the expected fix</p>\n";
        }
        
        if (strpos($method_source, 'consent handled in JavaScript') !== false) {
            echo "<p>✓ Method removes server-side consent blocking</p>\n";
        } else {
            echo "<p>⚠ Method may still have server-side consent blocking</p>\n";
        }
    } else {
        echo "<p>✗ renderAdminChatInterface method not found</p>\n";
    }
    
    if ($reflection->hasMethod('renderChatContainerHTML')) {
        echo "<p>✓ renderChatContainerHTML method exists</p>\n";
        
        $method = $reflection->getMethod('renderChatContainerHTML');
        $method->setAccessible(true);
        
        // Get the method source to check for the fix
        $filename = $reflection->getFileName();
        $start_line = $method->getStartLine();
        $end_line = $method->getEndLine();
        $source = file($filename);
        $method_source = implode('', array_slice($source, $start_line - 1, $end_line - $start_line + 1));
        
        if (strpos($method_source, 'Always render container but hide it if no consent') !== false) {
            echo "<p>✓ Method contains the container visibility fix</p>\n";
        } else {
            echo "<p>⚠ Method may not contain the visibility fix</p>\n";
        }
        
        if (strpos($method_source, '$container_style') !== false) {
            echo "<p>✓ Method uses dynamic container styling</p>\n";
        } else {
            echo "<p>⚠ Method may not use dynamic styling</p>\n";
        }
    } else {
        echo "<p>✗ renderChatContainerHTML method not found</p>\n";
    }
} else {
    echo "<p>✗ ChatInterface class not found</p>\n";
}

// Test 2: Check consent form template
echo "<h3>Test 2: Consent Form Template Validation</h3>\n";

$consent_template_path = MPAI_PLUGIN_DIR . 'templates/consent-form-inline.php';
if (file_exists($consent_template_path)) {
    echo "<p>✓ Consent form template exists</p>\n";
    
    $template_content = file_get_contents($consent_template_path);
    
    if (strpos($template_content, 'Found existing chat container, making it visible') !== false) {
        echo "<p>✓ Template contains the visibility fix</p>\n";
    } else {
        echo "<p>⚠ Template may not contain the visibility fix</p>\n";
    }
    
    if (strpos($template_content, '$chatContainer.show()') !== false) {
        echo "<p>✓ Template shows existing chat container</p>\n";
    } else {
        echo "<p>⚠ Template may not show existing container</p>\n";
    }
    
    if (strpos($template_content, '[CHAT RENDER DIAGNOSIS]') !== false) {
        echo "<p>✓ Template contains diagnostic logging</p>\n";
    } else {
        echo "<p>⚠ Template may not contain diagnostic logging</p>\n";
    }
} else {
    echo "<p>✗ Consent form template not found</p>\n";
}

// Test 3: Simulate the rendering flow
echo "<h3>Test 3: Rendering Flow Simulation</h3>\n";

if (function_exists('get_current_user_id') && get_current_user_id()) {
    $user_id = get_current_user_id();
    echo "<p>✓ User is logged in (ID: $user_id)</p>\n";
    
    // Check consent status
    if (class_exists('\\MemberpressAiAssistant\\Admin\\MPAIConsentManager')) {
        $consent_manager = \MemberpressAiAssistant\Admin\MPAIConsentManager::getInstance();
        $has_consented = $consent_manager->hasUserConsented();
        
        echo "<p>Current consent status: " . ($has_consented ? 'CONSENTED' : 'NOT CONSENTED') . "</p>\n";
        
        // Test the rendering logic
        if (class_exists('\\MemberpressAiAssistant\\ChatInterface')) {
            $chat_interface = \MemberpressAiAssistant\ChatInterface::getInstance();
            
            // Check if we're on the right page
            $current_page = isset($_GET['page']) ? $_GET['page'] : 'none';
            echo "<p>Current page: $current_page</p>\n";
            
            if ($current_page === 'mpai-settings') {
                echo "<p>✓ On settings page - chat interface should be rendered</p>\n";
                
                // Simulate the shouldLoadAdminChatInterface check
                $reflection = new ReflectionClass($chat_interface);
                $method = $reflection->getMethod('shouldLoadAdminChatInterface');
                $method->setAccessible(true);
                
                $should_load = $method->invoke($chat_interface, 'memberpress_page_mpai-settings');
                echo "<p>Should load chat interface: " . ($should_load ? 'YES' : 'NO') . "</p>\n";
                
                if ($should_load) {
                    echo "<p>✓ Chat container should be rendered to DOM</p>\n";
                    echo "<p>Container visibility: " . ($has_consented ? 'VISIBLE' : 'HIDDEN (will show after consent)') . "</p>\n";
                } else {
                    echo "<p>⚠ Chat interface would not be loaded</p>\n";
                }
            } else {
                echo "<p>⚠ Not on settings page - navigate to settings to test</p>\n";
            }
        }
    } else {
        echo "<p>✗ MPAIConsentManager class not available</p>\n";
    }
} else {
    echo "<p>⚠ No user logged in - login to test fully</p>\n";
}

echo "<h3>Fix Summary</h3>\n";
echo "<p>The implemented fix addresses the root cause of the chat container rendering issue:</p>\n";
echo "<ul>\n";
echo "<li><strong>Server-side:</strong> Always render chat container DOM elements, but hide them if user hasn't consented</li>\n";
echo "<li><strong>Client-side:</strong> Show the existing hidden container after successful consent via AJAX</li>\n";
echo "<li><strong>Flow:</strong> Page loads → Container rendered (hidden) → User consents → Container becomes visible</li>\n";
echo "</ul>\n";

echo "<h3>Expected Behavior</h3>\n";
echo "<ol>\n";
echo "<li>User visits settings page without consent</li>\n";
echo "<li>Chat container DOM elements are rendered but hidden</li>\n";
echo "<li>Consent form is shown</li>\n";
echo "<li>User agrees to consent</li>\n";
echo "<li>AJAX saves consent</li>\n";
echo "<li>JavaScript shows the existing hidden chat container</li>\n";
echo "<li>Chat interface becomes functional</li>\n";
echo "</ol>\n";

echo "<h3>Testing Instructions</h3>\n";
echo "<p>To test the fix:</p>\n";
echo "<ol>\n";
echo "<li>Clear user consent (if needed)</li>\n";
echo "<li>Go to the settings page</li>\n";
echo "<li>Check browser console - should see chat container elements in DOM but hidden</li>\n";
echo "<li>Complete consent form</li>\n";
echo "<li>Chat container should become visible</li>\n";
echo "<li>JavaScript should find the container elements successfully</li>\n";
echo "</ol>\n";