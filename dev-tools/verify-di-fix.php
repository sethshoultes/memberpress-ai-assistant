<?php
/**
 * Verification script for dependency injection fixes
 */

// Load WordPress
require_once('../../../wp-load.php');

echo "<h2>🔧 Dependency Injection Fix Verification</h2>\n";

// Test 1: Check if AbstractService now has serviceLocator property
echo "<h3>Test 1: AbstractService Property Check</h3>\n";
$reflection = new ReflectionClass('MemberpressAiAssistant\Abstracts\AbstractService');
$properties = $reflection->getProperties();
$hasServiceLocator = false;

foreach ($properties as $property) {
    if ($property->getName() === 'serviceLocator') {
        $hasServiceLocator = true;
        echo "<p>✅ AbstractService now has \$serviceLocator property (visibility: " . 
             ($property->isPrivate() ? 'private' : ($property->isProtected() ? 'protected' : 'public')) . ")</p>\n";
        break;
    }
}

if (!$hasServiceLocator) {
    echo "<p>❌ AbstractService still missing \$serviceLocator property</p>\n";
}

// Test 2: Check if AbstractService has setServiceLocator method
echo "<h3>Test 2: AbstractService Method Check</h3>\n";
if ($reflection->hasMethod('setServiceLocator')) {
    echo "<p>✅ AbstractService has setServiceLocator method</p>\n";
} else {
    echo "<p>❌ AbstractService missing setServiceLocator method</p>\n";
}

if ($reflection->hasMethod('getServiceLocator')) {
    echo "<p>✅ AbstractService has getServiceLocator method</p>\n";
} else {
    echo "<p>❌ AbstractService missing getServiceLocator method</p>\n";
}

// Test 3: Test service creation and dependency injection
echo "<h3>Test 3: Service Creation Test</h3>\n";
try {
    // Create a mock service locator
    $serviceLocator = new MemberpressAiAssistant\DI\ServiceLocator();
    
    // Register a mock logger
    $serviceLocator->register('logger', function() {
        return new class {
            public function info($message, $context = []) {}
            public function error($message, $context = []) {}
            public function warning($message, $context = []) {}
        };
    });
    
    // Test creating settings model service
    $modelService = new MemberpressAiAssistant\Services\Settings\SettingsModelService('settings.model', $serviceLocator->get('logger'));
    $modelService->setServiceLocator($serviceLocator);
    
    echo "<p>✅ SettingsModelService created successfully</p>\n";
    
    // Test if service locator is accessible
    $retrievedLocator = $modelService->getServiceLocator();
    if ($retrievedLocator === $serviceLocator) {
        echo "<p>✅ Service locator properly injected and retrievable</p>\n";
    } else {
        echo "<p>❌ Service locator injection failed</p>\n";
    }
    
    // Test creating settings view service
    $viewService = new MemberpressAiAssistant\Services\Settings\SettingsViewService('settings.view', $serviceLocator->get('logger'));
    $viewService->setServiceLocator($serviceLocator);
    
    echo "<p>✅ SettingsViewService created successfully</p>\n";
    
    // Test creating settings controller service
    $controllerService = new MemberpressAiAssistant\Services\Settings\SettingsControllerService('settings.controller', $serviceLocator->get('logger'));
    $controllerService->setServiceLocator($serviceLocator);
    
    echo "<p>✅ SettingsControllerService created successfully</p>\n";
    
} catch (Exception $e) {
    echo "<p>❌ Service creation failed: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

// Test 4: Test dependency validation
echo "<h3>Test 4: Dependency Validation Test</h3>\n";
try {
    // Register the services with the service locator
    $serviceLocator->register('settings.model', function() use ($serviceLocator) {
        $service = new MemberpressAiAssistant\Services\Settings\SettingsModelService('settings.model', $serviceLocator->get('logger'));
        $service->setServiceLocator($serviceLocator);
        return $service;
    });
    
    $serviceLocator->register('settings.view', function() use ($serviceLocator) {
        $service = new MemberpressAiAssistant\Services\Settings\SettingsViewService('settings.view', $serviceLocator->get('logger'));
        $service->setServiceLocator($serviceLocator);
        return $service;
    });
    
    $serviceLocator->register('settings.controller', function() use ($serviceLocator) {
        $service = new MemberpressAiAssistant\Services\Settings\SettingsControllerService('settings.controller', $serviceLocator->get('logger'));
        $service->setServiceLocator($serviceLocator);
        return $service;
    });
    
    // Test getting services
    $model = $serviceLocator->get('settings.model');
    $view = $serviceLocator->get('settings.view');
    $controller = $serviceLocator->get('settings.controller');
    
    echo "<p>✅ All services retrieved from service locator successfully</p>\n";
    
    // Test model get_all method (this was causing the fatal error)
    $allSettings = $model->get_all();
    echo "<p>✅ Model get_all() method works - returned " . count($allSettings) . " settings</p>\n";
    
} catch (Exception $e) {
    echo "<p>❌ Dependency validation test failed: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

echo "<h3>🎯 Fix Verification Summary</h3>\n";
echo "<p><strong>The dependency injection fixes should resolve:</strong></p>\n";
echo "<ul>\n";
echo "<li>✅ Undefined property \$serviceLocator errors in SettingsViewService (lines 465, 490)</li>\n";
echo "<li>✅ Undefined property \$serviceLocator errors in SettingsModelService (lines 482, 507)</li>\n";
echo "<li>✅ Fatal error calling get_all() on null in SettingsControllerService (line 182)</li>\n";
echo "<li>✅ Services can now properly validate their dependencies</li>\n";
echo "<li>✅ Service locator is properly injected during service creation</li>\n";
echo "</ul>\n";

echo "<p><strong>Next step:</strong> Test the actual settings page to confirm everything works in the WordPress environment.</p>\n";