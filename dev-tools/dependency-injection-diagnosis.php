<?php
/**
 * Dependency Injection Diagnosis Script
 * 
 * This script validates the assumptions about the DI issues in settings services
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

echo "<h2>🔍 Dependency Injection Diagnosis</h2>\n";

// Test 1: Check if AbstractService has serviceLocator property
echo "<h3>Test 1: AbstractService Property Analysis</h3>\n";
$reflection = new ReflectionClass('MemberpressAiAssistant\Abstracts\AbstractService');
$properties = $reflection->getProperties();
$hasServiceLocator = false;

echo "<ul>\n";
foreach ($properties as $property) {
    echo "<li>Property: " . $property->getName() . " (visibility: " . 
         ($property->isPrivate() ? 'private' : ($property->isProtected() ? 'protected' : 'public')) . ")</li>\n";
    if ($property->getName() === 'serviceLocator') {
        $hasServiceLocator = true;
    }
}
echo "</ul>\n";

echo "<p><strong>Result:</strong> AbstractService " . ($hasServiceLocator ? "HAS" : "DOES NOT HAVE") . " serviceLocator property</p>\n";

// Test 2: Check service constructor signatures
echo "<h3>Test 2: Service Constructor Analysis</h3>\n";
$services = [
    'SettingsViewService' => 'MemberpressAiAssistant\Services\Settings\SettingsViewService',
    'SettingsModelService' => 'MemberpressAiAssistant\Services\Settings\SettingsModelService',
    'SettingsControllerService' => 'MemberpressAiAssistant\Services\Settings\SettingsControllerService'
];

foreach ($services as $name => $class) {
    if (class_exists($class)) {
        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();
        if ($constructor) {
            $params = $constructor->getParameters();
            echo "<p><strong>$name Constructor Parameters:</strong></p>\n";
            echo "<ul>\n";
            foreach ($params as $param) {
                echo "<li>" . $param->getName() . " (" . ($param->getType() ? $param->getType() : 'mixed') . ")</li>\n";
            }
            echo "</ul>\n";
        }
    }
}

// Test 3: Check if services have serviceLocator property
echo "<h3>Test 3: Service Property Analysis</h3>\n";
foreach ($services as $name => $class) {
    if (class_exists($class)) {
        $reflection = new ReflectionClass($class);
        $properties = $reflection->getProperties();
        $hasServiceLocator = false;
        
        foreach ($properties as $property) {
            if ($property->getName() === 'serviceLocator') {
                $hasServiceLocator = true;
                break;
            }
        }
        
        echo "<p><strong>$name:</strong> " . ($hasServiceLocator ? "HAS" : "DOES NOT HAVE") . " serviceLocator property</p>\n";
    }
}

// Test 4: Check service locator usage in validateDependencies methods
echo "<h3>Test 4: Service Locator Usage Analysis</h3>\n";
foreach ($services as $name => $class) {
    if (class_exists($class)) {
        $reflection = new ReflectionClass($class);
        if ($reflection->hasMethod('validateDependencies')) {
            $method = $reflection->getMethod('validateDependencies');
            $filename = $method->getFileName();
            $startLine = $method->getStartLine();
            $endLine = $method->getEndLine();
            
            if ($filename && $startLine && $endLine) {
                $lines = file($filename);
                $methodCode = implode('', array_slice($lines, $startLine - 1, $endLine - $startLine + 1));
                
                $usesServiceLocator = strpos($methodCode, '$this->serviceLocator') !== false;
                echo "<p><strong>$name validateDependencies:</strong> " . 
                     ($usesServiceLocator ? "USES" : "DOES NOT USE") . " \$this->serviceLocator</p>\n";
            }
        }
    }
}

// Test 5: Check if service provider properly injects service locator
echo "<h3>Test 5: Service Provider Analysis</h3>\n";
$providerClass = 'MemberpressAiAssistant\DI\Providers\SettingsServiceProvider';
if (class_exists($providerClass)) {
    $reflection = new ReflectionClass($providerClass);
    if ($reflection->hasMethod('register')) {
        $method = $reflection->getMethod('register');
        $filename = $method->getFileName();
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();
        
        if ($filename && $startLine && $endLine) {
            $lines = file($filename);
            $methodCode = implode('', array_slice($lines, $startLine - 1, $endLine - $startLine + 1));
            
            echo "<p><strong>Service Provider Register Method:</strong></p>\n";
            echo "<pre>" . htmlspecialchars($methodCode) . "</pre>\n";
            
            $injectsServiceLocator = strpos($methodCode, 'serviceLocator') !== false;
            echo "<p><strong>Result:</strong> Service provider " . 
                 ($injectsServiceLocator ? "MENTIONS" : "DOES NOT MENTION") . " serviceLocator injection</p>\n";
        }
    }
}

echo "<h3>🎯 Diagnosis Summary</h3>\n";
echo "<p><strong>Expected Issues:</strong></p>\n";
echo "<ul>\n";
echo "<li>AbstractService missing \$serviceLocator property declaration</li>\n";
echo "<li>Services trying to use \$this->serviceLocator without it being injected</li>\n";
echo "<li>Service provider creates services but doesn't inject service locator</li>\n";
echo "<li>Services call validateDependencies() which fails due to missing service locator</li>\n";
echo "</ul>\n";

echo "<p><strong>Next Steps:</strong> If diagnosis confirms these issues, we need to:</p>\n";
echo "<ol>\n";
echo "<li>Add \$serviceLocator property to AbstractService</li>\n";
echo "<li>Add service locator injection method to AbstractService</li>\n";
echo "<li>Update service provider to inject service locator after creation</li>\n";
echo "<li>Ensure proper initialization order</li>\n";
echo "</ol>\n";