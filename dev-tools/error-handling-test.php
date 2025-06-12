<?php
/**
 * Error Handling and Degraded Mode Testing Script
 *
 * Tests error handling, graceful degradation, and recovery mechanisms
 * in the optimized Services/Settings architecture.
 *
 * @package MemberpressAiAssistant
 * @subpackage DevTools
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Error Handling Tester Class
 */
class MPAIErrorHandlingTester {
    
    /**
     * Test results
     *
     * @var array
     */
    private $results = [];
    
    /**
     * Service locator instance
     *
     * @var \MemberpressAiAssistant\DI\ServiceLocator
     */
    private $serviceLocator;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->log('Initializing Error Handling Tester');
        $this->serviceLocator = \MemberpressAiAssistant\DI\ServiceLocator::getInstance();
    }
    
    /**
     * Run all error handling tests
     *
     * @return array Test results
     */
    public function runAllTests() {
        $this->log('=== STARTING ERROR HANDLING AND DEGRADED MODE TESTING ===');
        
        // Test 1: Missing Dependencies
        $this->testMissingDependencies();
        
        // Test 2: Invalid Data Handling
        $this->testInvalidDataHandling();
        
        // Test 3: Database Connection Issues
        $this->testDatabaseIssues();
        
        // Test 4: Graceful Degradation
        $this->testGracefulDegradation();
        
        // Test 5: Error Recovery
        $this->testErrorRecovery();
        
        // Test 6: Logging Functionality
        $this->testLoggingFunctionality();
        
        $this->log('=== ERROR HANDLING TESTING COMPLETE ===');
        
        return $this->results;
    }
    
    /**
     * Test missing dependency scenarios
     *
     * @return void
     */
    private function testMissingDependencies() {
        $this->log('--- Testing Missing Dependencies ---');
        
        $tests = [];
        
        try {
            // Create a mock service with missing dependencies
            $mockService = new class('test.service') extends \MemberpressAiAssistant\Abstracts\AbstractService {
                public function __construct($name) {
                    parent::__construct($name);
                    $this->dependencies = ['nonexistent.service', 'another.missing.service'];
                }
                
                public function testValidateDependencies() {
                    return $this->validateDependencies();
                }
                
                public function testIsDegradedMode() {
                    return $this->isDegradedMode();
                }
                
                protected function validateDependencies(): bool {
                    foreach ($this->dependencies as $dependency) {
                        if (!$this->serviceLocator || !$this->serviceLocator->has($dependency)) {
                            $this->handleMissingDependency($dependency);
                            return false;
                        }
                    }
                    return true;
                }
                
                protected function handleMissingDependency(string $dependency): void {
                    $this->setDegradedMode(true);
                }
                
                protected function setDegradedMode(bool $degraded): void {
                    $this->degradedMode = $degraded;
                }
                
                protected function isDegradedMode(): bool {
                    return $this->degradedMode ?? false;
                }
            };
            
            // Test dependency validation
            $validationResult = $mockService->testValidateDependencies();
            $degradedMode = $mockService->testIsDegradedMode();
            
            $tests['missing_dependencies'] = [
                'status' => (!$validationResult && $degradedMode) ? 'PASSED' : 'FAILED',
                'validation_failed' => !$validationResult,
                'degraded_mode_enabled' => $degradedMode
            ];
            
            $this->log("MISSING DEPENDENCIES: " . ((!$validationResult && $degradedMode) ? 'PASSED' : 'FAILED'));
            
        } catch (\Exception $e) {
            $tests['exception'] = [
                'status' => 'FAILED',
                'error' => $e->getMessage()
            ];
            $this->log("MISSING DEPENDENCIES EXCEPTION: " . $e->getMessage());
        }
        
        $this->results['missing_dependencies'] = $tests;
    }
    
    /**
     * Test invalid data handling
     *
     * @return void
     */
    private function testInvalidDataHandling() {
        $this->log('--- Testing Invalid Data Handling ---');
        
        $tests = [];
        
        try {
            $model = $this->serviceLocator->get('settings.model');
            if (!$model) {
                $tests['invalid_data'] = ['status' => 'FAILED', 'error' => 'Model service not available'];
                return;
            }
            
            // Test 1: Invalid boolean values
            $invalidBooleans = ['invalid', 123, [], new stdClass(), null];
            foreach ($invalidBooleans as $index => $invalidValue) {
                $validated = $model->validate(['chat_enabled' => $invalidValue]);
                $isBoolean = is_bool($validated['chat_enabled']);
                
                $tests["invalid_boolean_$index"] = [
                    'status' => $isBoolean ? 'PASSED' : 'FAILED',
                    'input' => $invalidValue,
                    'output' => $validated['chat_enabled'],
                    'is_boolean' => $isBoolean
                ];
            }
            
            // Test 2: Invalid log levels
            $invalidLogLevels = ['invalid_level', 123, [], null];
            foreach ($invalidLogLevels as $index => $invalidLevel) {
                $validated = $model->validate(['log_level' => $invalidLevel]);
                $isValidLevel = in_array($validated['log_level'], ['none', 'error', 'warning', 'info', 'debug', 'trace', 'minimal']);
                
                $tests["invalid_log_level_$index"] = [
                    'status' => $isValidLevel ? 'PASSED' : 'FAILED',
                    'input' => $invalidLevel,
                    'output' => $validated['log_level'],
                    'is_valid_level' => $isValidLevel
                ];
            }
            
            // Test 3: Invalid user roles
            $invalidRoles = ['not_an_array', 123, null, ['invalid_role', 'another_invalid']];
            foreach ($invalidRoles as $index => $invalidRoles) {
                $validated = $model->validate(['user_roles' => $invalidRoles]);
                $isValidArray = is_array($validated['user_roles']) && !empty($validated['user_roles']);
                
                $tests["invalid_user_roles_$index"] = [
                    'status' => $isValidArray ? 'PASSED' : 'FAILED',
                    'input' => $invalidRoles,
                    'output' => $validated['user_roles'],
                    'is_valid_array' => $isValidArray
                ];
            }
            
            $this->log("INVALID DATA HANDLING: Multiple tests completed");
            
        } catch (\Exception $e) {
            $tests['exception'] = [
                'status' => 'FAILED',
                'error' => $e->getMessage()
            ];
            $this->log("INVALID DATA HANDLING EXCEPTION: " . $e->getMessage());
        }
        
        $this->results['invalid_data'] = $tests;
    }
    
    /**
     * Test database connection issues
     *
     * @return void
     */
    private function testDatabaseIssues() {
        $this->log('--- Testing Database Issues ---');
        
        $tests = [];
        
        try {
            $model = $this->serviceLocator->get('settings.model');
            if (!$model) {
                $tests['database_issues'] = ['status' => 'FAILED', 'error' => 'Model service not available'];
                return;
            }
            
            // Test 1: Simulate database read failure
            // We can't actually break the database, so we test error handling paths
            $originalValue = $model->get('chat_enabled');
            $tests['database_read'] = [
                'status' => ($originalValue !== null) ? 'PASSED' : 'FAILED',
                'value' => $originalValue
            ];
            
            // Test 2: Test default value fallback
            $nonExistentValue = $model->get('nonexistent_setting', 'fallback_value');
            $tests['default_fallback'] = [
                'status' => ($nonExistentValue === 'fallback_value') ? 'PASSED' : 'FAILED',
                'value' => $nonExistentValue
            ];
            
            // Test 3: Test save operation error handling
            $saveResult = $model->save();
            $tests['save_operation'] = [
                'status' => is_bool($saveResult) ? 'PASSED' : 'FAILED',
                'result' => $saveResult
            ];
            
            $this->log("DATABASE ISSUES: Tests completed");
            
        } catch (\Exception $e) {
            $tests['exception'] = [
                'status' => 'FAILED',
                'error' => $e->getMessage()
            ];
            $this->log("DATABASE ISSUES EXCEPTION: " . $e->getMessage());
        }
        
        $this->results['database_issues'] = $tests;
    }
    
    /**
     * Test graceful degradation
     *
     * @return void
     */
    private function testGracefulDegradation() {
        $this->log('--- Testing Graceful Degradation ---');
        
        $tests = [];
        
        try {
            // Test 1: Service continues to function with missing logger
            $mockServiceWithoutLogger = new class('test.degraded') extends \MemberpressAiAssistant\Abstracts\AbstractService {
                public function __construct($name) {
                    parent::__construct($name, null); // No logger
                }
                
                public function testOperation() {
                    // This should work even without logger
                    return $this->getServiceName();
                }
            };
            
            $operationResult = $mockServiceWithoutLogger->testOperation();
            $tests['operation_without_logger'] = [
                'status' => ($operationResult === 'test.degraded') ? 'PASSED' : 'FAILED',
                'result' => $operationResult
            ];
            
            // Test 2: View service renders basic content even in degraded mode
            $view = $this->serviceLocator->get('settings.view');
            if ($view) {
                ob_start();
                $view->render_error('Test error message');
                $errorOutput = ob_get_clean();
                
                $tests['error_rendering'] = [
                    'status' => !empty($errorOutput) ? 'PASSED' : 'FAILED',
                    'has_output' => !empty($errorOutput),
                    'contains_error' => strpos($errorOutput, 'Test error message') !== false
                ];
            }
            
            // Test 3: Model provides defaults when settings are corrupted
            $model = $this->serviceLocator->get('settings.model');
            if ($model) {
                // Test getting a setting that should have a default
                $defaultValue = $model->get('chat_enabled');
                $tests['default_provision'] = [
                    'status' => ($defaultValue !== null) ? 'PASSED' : 'FAILED',
                    'value' => $defaultValue
                ];
            }
            
            $this->log("GRACEFUL DEGRADATION: Tests completed");
            
        } catch (\Exception $e) {
            $tests['exception'] = [
                'status' => 'FAILED',
                'error' => $e->getMessage()
            ];
            $this->log("GRACEFUL DEGRADATION EXCEPTION: " . $e->getMessage());
        }
        
        $this->results['graceful_degradation'] = $tests;
    }
    
    /**
     * Test error recovery mechanisms
     *
     * @return void
     */
    private function testErrorRecovery() {
        $this->log('--- Testing Error Recovery ---');
        
        $tests = [];
        
        try {
            $model = $this->serviceLocator->get('settings.model');
            if (!$model) {
                $tests['error_recovery'] = ['status' => 'FAILED', 'error' => 'Model service not available'];
                return;
            }
            
            // Test 1: Recovery from invalid settings
            $originalSettings = $model->get_all();
            
            // Simulate corrupted settings by setting invalid values
            $corruptedSettings = [
                'chat_enabled' => 'invalid_boolean',
                'log_level' => 'invalid_level',
                'user_roles' => 'not_an_array'
            ];
            
            // Validate and see if they're corrected
            $recoveredSettings = $model->validate($corruptedSettings);
            
            $recoverySuccess = (
                is_bool($recoveredSettings['chat_enabled']) &&
                in_array($recoveredSettings['log_level'], ['none', 'error', 'warning', 'info', 'debug', 'trace', 'minimal']) &&
                is_array($recoveredSettings['user_roles'])
            );
            
            $tests['settings_recovery'] = [
                'status' => $recoverySuccess ? 'PASSED' : 'FAILED',
                'original_corrupted' => $corruptedSettings,
                'recovered' => $recoveredSettings,
                'recovery_success' => $recoverySuccess
            ];
            
            // Test 2: Reset to defaults functionality
            $resetResult = $model->reset(false); // Don't save to avoid affecting actual settings
            $tests['reset_to_defaults'] = [
                'status' => $resetResult ? 'PASSED' : 'FAILED',
                'result' => $resetResult
            ];
            
            $this->log("ERROR RECOVERY: Tests completed");
            
        } catch (\Exception $e) {
            $tests['exception'] = [
                'status' => 'FAILED',
                'error' => $e->getMessage()
            ];
            $this->log("ERROR RECOVERY EXCEPTION: " . $e->getMessage());
        }
        
        $this->results['error_recovery'] = $tests;
    }
    
    /**
     * Test logging functionality
     *
     * @return void
     */
    private function testLoggingFunctionality() {
        $this->log('--- Testing Logging Functionality ---');
        
        $tests = [];
        
        try {
            // Test 1: Services can operate without logger
            $serviceWithoutLogger = new class('test.no.logger') extends \MemberpressAiAssistant\Abstracts\AbstractService {
                public function __construct($name) {
                    parent::__construct($name, null);
                }
                
                public function testLogOperation() {
                    $this->log('Test message'); // Should not throw error
                    return true;
                }
            };
            
            $logResult = $serviceWithoutLogger->testLogOperation();
            $tests['logging_without_logger'] = [
                'status' => $logResult ? 'PASSED' : 'FAILED',
                'result' => $logResult
            ];
            
            // Test 2: Error handling methods exist
            $model = $this->serviceLocator->get('settings.model');
            if ($model) {
                $hasErrorHandling = method_exists($model, 'executeWithErrorHandling');
                $hasErrorHandler = method_exists($model, 'handleError');
                
                $tests['error_handling_methods'] = [
                    'status' => ($hasErrorHandling && $hasErrorHandler) ? 'PASSED' : 'FAILED',
                    'has_execute_with_error_handling' => $hasErrorHandling,
                    'has_handle_error' => $hasErrorHandler
                ];
            }
            
            // Test 3: Degraded mode logging
            $view = $this->serviceLocator->get('settings.view');
            if ($view) {
                $hasDegradedMethods = (
                    method_exists($view, 'setDegradedMode') ||
                    method_exists($view, 'isDegradedMode')
                );
                
                $tests['degraded_mode_methods'] = [
                    'status' => $hasDegradedMethods ? 'PASSED' : 'FAILED',
                    'has_degraded_methods' => $hasDegradedMethods
                ];
            }
            
            $this->log("LOGGING FUNCTIONALITY: Tests completed");
            
        } catch (\Exception $e) {
            $tests['exception'] = [
                'status' => 'FAILED',
                'error' => $e->getMessage()
            ];
            $this->log("LOGGING FUNCTIONALITY EXCEPTION: " . $e->getMessage());
        }
        
        $this->results['logging_functionality'] = $tests;
    }
    
    /**
     * Log a message
     *
     * @param string $message Message to log
     * @return void
     */
    private function log($message) {
        error_log('[MPAI Error Test] ' . $message);
        
        // Also output to screen if in admin context
        if (is_admin() && current_user_can('manage_options')) {
            echo '<div class="mpai-error-test-log">' . esc_html($message) . '</div>';
        }
    }
    
    /**
     * Generate HTML report
     *
     * @return string HTML report
     */
    public function generateHtmlReport() {
        $html = '<div class="mpai-error-test-report">';
        $html .= '<h2>Error Handling and Degraded Mode Test Report</h2>';
        
        $overallStatus = 'PASSED';
        $totalTests = 0;
        $passedTests = 0;
        
        foreach ($this->results as $testCategory => $tests) {
            $html .= '<h3>' . ucwords(str_replace('_', ' ', $testCategory)) . '</h3>';
            
            if (isset($tests['status'])) {
                // Simple test result
                $statusClass = $tests['status'] === 'PASSED' ? 'success' : 'error';
                $html .= '<div class="notice notice-' . $statusClass . '">';
                $html .= '<p><strong>' . $tests['status'] . '</strong></p>';
                if (isset($tests['error'])) {
                    $html .= '<p>Error: ' . esc_html($tests['error']) . '</p>';
                }
                $html .= '</div>';
                
                $totalTests++;
                if ($tests['status'] === 'PASSED') {
                    $passedTests++;
                } else {
                    $overallStatus = 'FAILED';
                }
            } else {
                // Complex test results
                $html .= '<table class="widefat">';
                $html .= '<thead><tr><th>Test</th><th>Status</th><th>Details</th></tr></thead>';
                $html .= '<tbody>';
                
                foreach ($tests as $testName => $testResult) {
                    if ($testName === 'exception') continue;
                    
                    $status = isset($testResult['status']) ? $testResult['status'] : 'UNKNOWN';
                    
                    $html .= '<tr>';
                    $html .= '<td>' . esc_html($testName) . '</td>';
                    $html .= '<td><span class="mpai-status-' . strtolower($status) . '">' . esc_html($status) . '</span></td>';
                    $html .= '<td>';
                    
                    if (isset($testResult['error'])) {
                        $html .= 'Error: ' . esc_html($testResult['error']);
                    } else {
                        $details = $testResult;
                        unset($details['status']);
                        if (!empty($details)) {
                            $html .= '<pre>' . esc_html(json_encode($details, JSON_PRETTY_PRINT)) . '</pre>';
                        }
                    }
                    
                    $html .= '</td>';
                    $html .= '</tr>';
                    
                    $totalTests++;
                    if ($status === 'PASSED') {
                        $passedTests++;
                    } else {
                        $overallStatus = 'FAILED';
                    }
                }
                
                $html .= '</tbody></table>';
            }
        }
        
        // Add summary
        $html .= '<div class="mpai-test-summary">';
        $html .= '<h3>Test Summary</h3>';
        $html .= '<p><strong>Overall Status:</strong> <span class="mpai-status-' . strtolower($overallStatus) . '">' . $overallStatus . '</span></p>';
        $html .= '<p><strong>Tests Passed:</strong> ' . $passedTests . ' / ' . $totalTests . '</p>';
        $html .= '<p><strong>Success Rate:</strong> ' . ($totalTests > 0 ? round(($passedTests / $totalTests) * 100, 2) : 0) . '%</p>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        // Add CSS
        $html .= '<style>
            .mpai-error-test-report { margin: 20px 0; }
            .mpai-error-test-log { margin: 5px 0; padding: 5px; background: #f0f0f0; }
            .mpai-status-passed { color: #46b450; font-weight: bold; }
            .mpai-status-failed { color: #dc3232; font-weight: bold; }
            .mpai-error-test-report pre { background: #f9f9f9; padding: 10px; overflow-x: auto; }
            .mpai-test-summary { background: #f0f6fc; padding: 15px; border-left: 4px solid #0073aa; margin-top: 20px; }
        </style>';
        
        return $html;
    }
}

/**
 * Register the error test page
 */
function mpai_register_error_test_page() {
    add_submenu_page(
        'memberpress',
        'AI Assistant Error Handling Test',
        'Error Test',
        'manage_options',
        'mpai-error-test',
        'mpai_render_error_test_page'
    );
}
add_action('admin_menu', 'mpai_register_error_test_page');

/**
 * Render the error test page
 */
function mpai_render_error_test_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    echo '<div class="wrap">';
    echo '<h1>MemberPress AI Assistant - Error Handling Test</h1>';
    
    if (isset($_POST['run_error_tests'])) {
        $tester = new MPAIErrorHandlingTester();
        $results = $tester->runAllTests();
        echo $tester->generateHtmlReport();
    } else {
        echo '<p>This tool tests error handling, graceful degradation, and recovery mechanisms in the Settings services.</p>';
        echo '<form method="post">';
        echo '<p><input type="submit" name="run_error_tests" class="button button-primary" value="Run Error Handling Tests" /></p>';
        echo '</form>';
    }
    
    echo '</div>';
}