<?php
/**
 * Simple Consent Form Duplication Fix Validation
 * 
 * This script validates the code changes without requiring WordPress to be loaded.
 */

echo "MemberPress AI Assistant - Consent Form Duplication Fix Validation\n";
echo "================================================================\n\n";

// Test 1: Check if the static flag exists in the class file
echo "Test 1: Static Flag Implementation\n";
echo "-----------------------------------\n";

$consent_manager_file = dirname(__DIR__) . '/src/Admin/MPAIConsentManager.php';
if (file_exists($consent_manager_file)) {
    $content = file_get_contents($consent_manager_file);
    
    if (strpos($content, 'private static $form_rendered = false;') !== false) {
        echo "✅ Static flag \$form_rendered is properly declared\n";
    } else {
        echo "❌ Static flag \$form_rendered not found or incorrectly declared\n";
    }
} else {
    echo "❌ MPAIConsentManager.php file not found\n";
}

// Test 2: Check renderConsentForm method implementation
echo "\nTest 2: renderConsentForm Method Implementation\n";
echo "-----------------------------------------------\n";

if (file_exists($consent_manager_file)) {
    $content = file_get_contents($consent_manager_file);
    
    // Check for duplicate prevention logic
    if (strpos($content, 'if (self::$form_rendered)') !== false) {
        echo "✅ Method has duplicate prevention check\n";
    } else {
        echo "❌ Method missing duplicate prevention check\n";
    }
    
    if (strpos($content, 'self::$form_rendered = true;') !== false) {
        echo "✅ Method sets the flag to prevent future renders\n";
    } else {
        echo "❌ Method does not set the flag\n";
    }
    
    if (strpos($content, 'Form already rendered, skipping duplicate render') !== false) {
        echo "✅ Method has proper logging for duplicate prevention\n";
    } else {
        echo "❌ Method missing duplicate prevention logging\n";
    }
    
    if (strpos($content, 'return;') !== false && strpos($content, 'if (self::$form_rendered)') !== false) {
        echo "✅ Method has early return for duplicate prevention\n";
    } else {
        echo "❌ Method missing early return for duplicate prevention\n";
    }
}

// Test 3: Check welcome page template
echo "\nTest 3: Welcome Page Template Analysis\n";
echo "--------------------------------------\n";

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
    
    // Check for leftover consent-related content that could cause duplication
    $leftover_patterns = [
        'mpai-consent-form-container' => 'consent form container',
        'mpai-consent-checkbox' => 'consent checkbox',
        'mpai-submit-consent' => 'submit consent button',
        'Review Full Terms' => 'review terms button'
    ];
    
    $leftover_found = false;
    foreach ($leftover_patterns as $pattern => $description) {
        $pattern_count = substr_count($content, $pattern);
        if ($pattern_count > 0) {
            // Check if this pattern appears outside of the renderConsentForm call
            $lines = explode("\n", $content);
            $in_render_call = false;
            $pattern_outside_render = false;
            
            foreach ($lines as $line) {
                if (strpos($line, 'renderConsentForm') !== false) {
                    $in_render_call = true;
                    continue;
                }
                
                if ($in_render_call && strpos($line, $pattern) !== false) {
                    // Pattern found after renderConsentForm call - this is expected
                    continue;
                }
                
                if (!$in_render_call && strpos($line, $pattern) !== false) {
                    echo "⚠️ Found potential leftover content: {$description} ({$pattern})\n";
                    $pattern_outside_render = true;
                    $leftover_found = true;
                }
            }
        }
    }
    
    if (!$leftover_found) {
        echo "✅ No leftover consent-related content found in welcome template\n";
    }
} else {
    echo "❌ Welcome page template not found\n";
}

// Test 4: Analyze the fix implementation
echo "\nTest 4: Fix Implementation Analysis\n";
echo "-----------------------------------\n";

if (file_exists($consent_manager_file)) {
    $lines = file($consent_manager_file);
    $in_render_method = false;
    $method_lines = [];
    
    foreach ($lines as $line_num => $line) {
        if (strpos($line, 'public function renderConsentForm()') !== false) {
            $in_render_method = true;
        }
        
        if ($in_render_method) {
            $method_lines[] = trim($line);
            
            // End of method
            if (strpos($line, '}') !== false && count($method_lines) > 5) {
                break;
            }
        }
    }
    
    $method_content = implode(' ', $method_lines);
    
    // Check the order of operations
    $flag_check_pos = strpos($method_content, 'if (self::$form_rendered)');
    $flag_set_pos = strpos($method_content, 'self::$form_rendered = true');
    $include_pos = strpos($method_content, 'include $template_path');
    
    if ($flag_check_pos !== false && $flag_set_pos !== false && $include_pos !== false) {
        if ($flag_check_pos < $flag_set_pos && $flag_set_pos < $include_pos) {
            echo "✅ Fix implementation has correct order: check flag → set flag → include template\n";
        } else {
            echo "⚠️ Fix implementation order may be incorrect\n";
        }
    }
}

echo "\nSummary\n";
echo "=======\n";
echo "The consent form duplication fix has been implemented with the following changes:\n";
echo "1. Added static flag \$form_rendered to prevent multiple renders\n";
echo "2. Modified renderConsentForm() to check the flag before rendering\n";
echo "3. Set the flag to true after the first render\n";
echo "4. Added early return to prevent duplicate template inclusion\n";
echo "5. Added debug logging for duplicate prevention\n\n";

echo "This fix should resolve the issue where two identical consent forms were appearing\n";
echo "by ensuring the consent form template is only included once per page load.\n\n";

echo "Next steps:\n";
echo "- Test the fix in the WordPress admin interface\n";
echo "- Verify that only one consent form appears on the welcome page\n";
echo "- Confirm that the chat interface appears after consent is given\n";