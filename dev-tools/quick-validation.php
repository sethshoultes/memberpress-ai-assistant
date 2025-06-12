<?php
/**
 * Quick Settings Validation Script
 *
 * Simple validation script to test core functionality
 * without requiring WordPress admin interface.
 *
 * @package MemberpressAiAssistant
 * @subpackage DevTools
 * @since 1.0.0
 */

// This script can be run via WP-CLI or included in WordPress context

/**
 * Quick validation function
 */
function mpai_quick_settings_validation() {
    $results = [];
    $results['timestamp'] = date('Y-m-d H:i:s');
    $results['test_type'] = 'Quick Settings Validation';
    
    try {
        // Test 1: Check if classes exist
        $classes = [
            'MemberpressAiAssistant\Services\Settings\SettingsModelService',
            'MemberpressAiAssistant\Services\Settings\SettingsViewService',
            'MemberpressAiAssistant\Services\Settings\SettingsControllerService'
        ];
        
        $results['class_existence'] = [];
        foreach ($classes as $class) {
            $exists = class_exists($class);
            $results['class_existence'][$class] = $exists ? 'EXISTS' : 'MISSING';
            
            if ($exists) {
                // Check if class implements expected interfaces
                $interfaces = class_implements($class);
                $results['class_existence'][$class . '_interfaces'] = array_keys($interfaces);
            }
        }
        
        // Test 2: Check service locator
        if (class_exists('MemberpressAiAssistant\DI\ServiceLocator')) {
            try {
                $serviceLocator = \MemberpressAiAssistant\DI\ServiceLocator::getInstance();
                $results['service_locator'] = 'AVAILABLE';
                
                // Test 3: Check if services are registered
                $services = ['settings.model', 'settings.view', 'settings.controller'];
                $results['service_registration'] = [];
                
                foreach ($services as $service) {
                    $hasService = $serviceLocator->has($service);
                    $results['service_registration'][$service] = $hasService ? 'REGISTERED' : 'NOT_REGISTERED';
                    
                    if ($hasService) {
                        try {
                            $serviceInstance = $serviceLocator->get($service);
                            $results['service_registration'][$service . '_instance'] = $serviceInstance ? 'INSTANTIATED' : 'FAILED_TO_INSTANTIATE';
                        } catch (\Exception $e) {
                            $results['service_registration'][$service . '_error'] = $e->getMessage();
                        }
                    }
                }
            } catch (\Exception $e) {
                $results['service_locator_error'] = $e->getMessage();
            }
        } else {
            $results['service_locator'] = 'NOT_AVAILABLE';
        }
        
        // Test 4: Basic functionality test
        if (isset($serviceLocator) && $serviceLocator->has('settings.model')) {
            try {
                $model = $serviceLocator->get('settings.model');
                if ($model) {
                    // Test basic CRUD operations
                    $testValue = $model->get('chat_enabled');
                    $results['basic_functionality']['get_operation'] = $testValue !== null ? 'SUCCESS' : 'FAILED';
                    
                    $allSettings = $model->get_all();
                    $results['basic_functionality']['get_all_operation'] = !empty($allSettings) ? 'SUCCESS' : 'FAILED';
                    $results['basic_functionality']['settings_count'] = count($allSettings);
                    
                    // Test validation
                    $validated = $model->validate(['chat_enabled' => 'invalid_value']);
                    $results['basic_functionality']['validation'] = isset($validated['chat_enabled']) ? 'SUCCESS' : 'FAILED';
                }
            } catch (\Exception $e) {
                $results['basic_functionality_error'] = $e->getMessage();
            }
        }
        
        // Test 5: Check for orphaned methods in view service
        if (isset($serviceLocator) && $serviceLocator->has('settings.view')) {
            try {
                $view = $serviceLocator->get('settings.view');
                if ($view) {
                    $orphanedMethods = [
                        'render_api_section',
                        'render_consent_section',
                        'render_openai_api_key_field',
                        'render_anthropic_api_key_field'
                    ];
                    
                    $results['orphaned_methods'] = [];
                    foreach ($orphanedMethods as $method) {
                        $results['orphaned_methods'][$method] = method_exists($view, $method) ? 'EXISTS_BUT_ORPHANED' : 'NOT_EXISTS';
                    }
                }
            } catch (\Exception $e) {
                $results['orphaned_methods_error'] = $e->getMessage();
            }
        }
        
        // Test 6: Check active tabs configuration
        if (isset($serviceLocator) && $serviceLocator->has('settings.controller')) {
            try {
                $controller = $serviceLocator->get('settings.controller');
                if ($controller && method_exists($controller, 'get_tabs')) {
                    $tabs = $controller->get_tabs();
                    $results['active_tabs'] = array_keys($tabs);
                    $results['expected_tabs'] = ['general', 'chat', 'access'];
                    $results['tabs_match_expected'] = (array_keys($tabs) === ['general', 'chat', 'access']) ? 'YES' : 'NO';
                }
            } catch (\Exception $e) {
                $results['active_tabs_error'] = $e->getMessage();
            }
        }
        
        $results['overall_status'] = 'COMPLETED';
        
    } catch (\Exception $e) {
        $results['fatal_error'] = $e->getMessage();
        $results['overall_status'] = 'FAILED';
    }
    
    return $results;
}

/**
 * Format results for display
 */
function mpai_format_validation_results($results) {
    $output = "=== MemberPress AI Assistant Settings Validation ===\n";
    $output .= "Timestamp: " . $results['timestamp'] . "\n";
    $output .= "Test Type: " . $results['test_type'] . "\n\n";
    
    foreach ($results as $key => $value) {
        if (in_array($key, ['timestamp', 'test_type', 'overall_status'])) {
            continue;
        }
        
        $output .= strtoupper(str_replace('_', ' ', $key)) . ":\n";
        
        if (is_array($value)) {
            foreach ($value as $subKey => $subValue) {
                if (is_array($subValue)) {
                    $output .= "  " . $subKey . ": " . implode(', ', $subValue) . "\n";
                } else {
                    $output .= "  " . $subKey . ": " . $subValue . "\n";
                }
            }
        } else {
            $output .= "  " . $value . "\n";
        }
        $output .= "\n";
    }
    
    $output .= "OVERALL STATUS: " . $results['overall_status'] . "\n";
    $output .= "=== End Validation ===\n";
    
    return $output;
}

// If running via WP-CLI or direct inclusion
if (defined('WP_CLI') && WP_CLI) {
    $results = mpai_quick_settings_validation();
    WP_CLI::log(mpai_format_validation_results($results));
} elseif (defined('ABSPATH')) {
    // Running in WordPress context
    $results = mpai_quick_settings_validation();
    error_log('[MPAI Quick Validation] ' . mpai_format_validation_results($results));
    
    // Also output to screen if in admin
    if (is_admin() && current_user_can('manage_options')) {
        echo '<pre>' . esc_html(mpai_format_validation_results($results)) . '</pre>';
    }
}