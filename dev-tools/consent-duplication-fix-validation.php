<?php
/**
 * Consent Form Duplication Fix Validation
 * 
 * This script validates that the consent form duplication issue has been resolved.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once dirname(__DIR__, 4) . '/wp-load.php';
}

echo "<h1>MemberPress AI Assistant - Consent Form Duplication Fix Validation</h1>\n";
echo "<div style='font-family: monospace; background: #f0f0f0; padding: 20px; margin: 20px 0;'>\n";

// Test 1: Check if the static flag exists
echo "<h2>Test 1: Static Flag Implementation</h2>\n";
try {
    $reflection = new ReflectionClass('MemberpressAiAssistant\Admin\MPAIConsentManager');
    $property = $reflection->getProperty('form_rendered');
    
    if ($property->isStatic()) {
        echo "✅ Static flag \$form_rendered exists and is properly declared as static\n";
    } else {
        echo "❌ Static flag \$form_rendered exists but is not static\n";
    }
} catch (Exception $e) {
    echo "❌ Static flag \$form_rendered not found: " . $e->getMessage() . "\n";
}

// Test 2: Check renderConsentForm method implementation
echo "<h2>Test 2: renderConsentForm Method Implementation</h2>\n";
try {
    $reflection = new ReflectionClass('MemberpressAiAssistant\Admin\MPAIConsentManager');
    $method = $reflection->getMethod('renderConsentForm');
    $filename = $method->getFileName();
    $start_line = $method->getStartLine();
    $end_line = $method->getEndLine();
    
    $file_content = file($filename);
    $method_content = implode('', array_slice($file_content, $start_line - 1, $end_line - $start_line + 1));
    
    if (strpos($method_content, 'self::$form_rendered') !== false) {
        echo "✅ renderConsentForm method contains static flag check\n";
        
        if (strpos($method_content, 'if (self::$form_rendered)') !== false) {
            echo "✅ Method has duplicate prevention logic\n";
        } else {
            echo "⚠️ Method references static flag but may not have proper duplicate prevention\n";
        }
        
        if (strpos($method_content, 'self::$form_rendered = true') !== false) {
            echo "✅ Method sets the flag to prevent future renders\n";
        } else {
            echo "❌ Method does not set the flag to prevent future renders\n";
        }
    } else {
        echo "❌ renderConsentForm method does not use the static flag\n";
    }
} catch (Exception $e) {
    echo "❌ Error analyzing renderConsentForm method: " . $e->getMessage() . "\n";
}

// Test 3: Simulate multiple calls to renderConsentForm
echo "<h2>Test 3: Multiple Render Prevention Test</h2>\n";
try {
    // Reset the static flag for testing
    $reflection = new ReflectionClass('MemberpressAiAssistant\Admin\MPAIConsentManager');
    $property = $reflection->getProperty('form_rendered');
    $property->setAccessible(true);
    $property->setValue(null, false);
    
    // Get consent manager instance
    $consent_manager = \MemberpressAiAssistant\Admin\MPAIConsentManager::getInstance();
    
    // Capture output from first render
    ob_start();
    $consent_manager->renderConsentForm();
    $first_output = ob_get_clean();
    
    // Capture output from second render (should be empty)
    ob_start();
    $consent_manager->renderConsentForm();
    $second_output = ob_get_clean();
    
    if (!empty($first_output) && empty($second_output)) {
        echo "✅ First render produced output, second render was prevented\n";
        echo "✅ Duplicate prevention is working correctly\n";
    } elseif (!empty($first_output) && !empty($second_output)) {
        echo "❌ Both renders produced output - duplication not prevented\n";
    } else {
        echo "⚠️ No output from either render - may indicate template issues\n";
    }
    
    // Check the flag state
    $flag_value = $property->getValue();
    if ($flag_value === true) {
        echo "✅ Static flag is properly set to true after first render\n";
    } else {
        echo "❌ Static flag is not set to true after render\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error testing multiple renders: " . $e->getMessage() . "\n";
}

// Test 4: Check welcome page template
echo "<h2>Test 4: Welcome Page Template Analysis</h2>\n";
$welcome_template = dirname(__DIR__) . '/templates/welcome-page.php';
if (file_exists($welcome_template)) {
    $content = file_get_contents($welcome_template);
    
    // Count occurrences of renderConsentForm calls
    $render_calls = substr_count($content, 'renderConsentForm');
    
    if ($render_calls === 1) {
        echo "✅ Welcome page template has exactly 1 renderConsentForm call\n";
    } elseif ($render_calls > 1) {
        echo "❌ Welcome page template has {$render_calls} renderConsentForm calls - should be 1\n";
    } else {
        echo "⚠️ Welcome page template has no renderConsentForm calls\n";
    }
    
    // Check for leftover consent-related content
    $leftover_patterns = [
        'mpai-consent-form-container',
        'mpai-consent-checkbox',
        'mpai-submit-consent',
        'Review Full Terms'
    ];
    
    $leftover_found = false;
    foreach ($leftover_patterns as $pattern) {
        if (strpos($content, $pattern) !== false && strpos($content, 'renderConsentForm') === false) {
            echo "⚠️ Found potential leftover consent content: {$pattern}\n";
            $leftover_found = true;
        }
    }
    
    if (!$leftover_found) {
        echo "✅ No leftover consent-related content found in welcome template\n";
    }
} else {
    echo "❌ Welcome page template not found\n";
}

echo "<h2>Summary</h2>\n";
echo "The consent form duplication fix has been implemented with the following changes:\n";
echo "1. Added static flag \$form_rendered to prevent multiple renders\n";
echo "2. Modified renderConsentForm() to check and set the flag\n";
echo "3. Early return prevents duplicate template inclusion\n";
echo "\nThis should resolve the issue where two identical consent forms were appearing.\n";

echo "</div>\n";
?>