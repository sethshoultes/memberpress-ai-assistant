# Comprehensive Testing Guide

**Version:** 1.0.0  
**Last Updated:** 2025-04-06  
**Status:** ✅ Stable  
**Owner:** Developer Documentation Team

## Overview

This guide provides a comprehensive approach to testing the MemberPress AI Assistant plugin. It covers unit testing, integration testing, API testing, UI testing, and performance testing methodologies specific to this plugin.

## Table of Contents

1. [Testing Environment Setup](#testing-environment-setup)
2. [Test Organization](#test-organization)
3. [Unit Testing](#unit-testing)
4. [Integration Testing](#integration-testing)
5. [API Testing](#api-testing)
6. [UI Testing](#ui-testing)
7. [Performance Testing](#performance-testing)
8. [Regression Testing](#regression-testing)
9. [Security Testing](#security-testing)
10. [Continuous Integration](#continuous-integration)
11. [Test Coverage Reports](#test-coverage-reports)
12. [Troubleshooting Tests](#troubleshooting-tests)

## Testing Environment Setup

### Prerequisites

Ensure you have the following installed:

- PHP 8.0+
- WordPress 6.0+
- PHPUnit 9.0+
- WP-CLI
- Node.js & npm

### Setting Up the Test Environment

1. **Clone the repository and install dependencies**:
   ```bash
   git clone https://github.com/memberpress/memberpress-ai-assistant.git
   cd memberpress-ai-assistant
   composer install
   npm install
   ```

2. **Set up the test WordPress environment**:
   ```bash
   composer setup-tests
   ```

3. **Configure Test API Keys**:

   Create a `phpunit-config.php` file in the `tests` directory with your test API credentials:
   ```php
   <?php
   // Test API keys - DO NOT USE PRODUCTION KEYS
   define('MPAI_TEST_OPENAI_API_KEY', 'sk-test-xxxxxx');
   define('MPAI_TEST_ANTHROPIC_API_KEY', 'sk-ant-test-xxxxxx');
   ```

### Test Command Reference

Run these commands from the plugin root directory:

- **Run all tests**:
  ```bash
  composer test
  ```

- **Run specific test suite**:
  ```bash
  composer test:unit    # Unit tests only
  composer test:int     # Integration tests only
  composer test:api     # API tests only
  ```

- **Run a specific test file**:
  ```bash
  vendor/bin/phpunit tests/unit/test-class-mpai-chat.php
  ```

- **Run with code coverage report**:
  ```bash
  composer test:coverage
  ```

## Test Organization

Tests are organized into several categories:

### Directory Structure

```
tests/
├── bootstrap.php                  # Test bootstrap file
├── phpunit.xml                    # PHPUnit configuration
├── unit/                          # Unit tests
│   ├── test-class-mpai-chat.php
│   ├── test-class-mpai-context-manager.php
│   └── ...
├── integration/                   # Integration tests
│   ├── test-api-router.php
│   ├── test-tool-system.php
│   └── ...
├── api/                           # API tests
│   ├── test-openai-integration.php
│   ├── test-anthropic-integration.php
│   └── ...
├── performance/                   # Performance tests
│   ├── test-caching.php
│   ├── test-api-response-time.php
│   └── ...
├── mocks/                         # Mock classes and data
│   ├── mock-api-responses.php
│   ├── class-mock-ai-provider.php
│   └── ...
└── data/                          # Test fixtures and data
    ├── sample-prompts.php
    ├── sample-responses.json
    └── ...
```

### Test Naming Conventions

- Test class names should follow the format: `Test_{Class_Name}` or `{Class_Name}_Test`
- Test method names should follow the format: `test_{method_name}_{scenario}` or `test_{scenario}`
- Test files should be named: `test-{file-name}.php` or `{class-name}-test.php`

## Unit Testing

Unit tests focus on testing individual components in isolation.

### Writing Unit Tests

Basic unit test structure:

```php
class MPAI_Chat_Test extends WP_UnitTestCase {
    public function setUp(): void {
        parent::setUp();
        // Set up test environment
        $this->chat = new MPAI_Chat();
    }

    public function tearDown(): void {
        // Clean up test environment
        parent::tearDown();
    }

    public function test_get_system_message() {
        $system_message = $this->chat->get_system_message();
        $this->assertNotEmpty($system_message);
        $this->assertStringContainsString('You are an AI assistant', $system_message);
    }

    public function test_process_chat_request_with_valid_input() {
        $request = 'Hello, how are you?';
        $context = array('test' => true);
        
        // Mock the API response
        add_filter('mpai_generate_completion', function() {
            return 'I am doing well, thank you for asking!';
        });
        
        $response = $this->chat->process_chat_request($request, $context);
        
        $this->assertIsArray($response);
        $this->assertArrayHasKey('response', $response);
        $this->assertStringContainsString('well', $response['response']);
        
        // Clean up
        remove_all_filters('mpai_generate_completion');
    }

    public function test_process_chat_request_with_invalid_input() {
        $response = $this->chat->process_chat_request('', array());
        
        $this->assertWPError($response);
        $this->assertEquals('empty_request', $response->get_error_code());
    }
}
```

### Testing with Mocks

For components that depend on external services or other components, use mocks:

```php
class MPAI_API_Router_Test extends WP_UnitTestCase {
    public function setUp(): void {
        parent::setUp();
        $this->api_router = new MPAI_API_Router();
    }

    public function test_generate_completion_with_openai() {
        // Create a mock OpenAI provider
        $mock_provider = $this->createMock(MPAI_OpenAI::class);
        $mock_provider->method('generate_completion')
            ->willReturn('This is a mock response from OpenAI');
            
        // Inject the mock
        $reflection = new ReflectionClass($this->api_router);
        $provider_property = $reflection->getProperty('provider');
        $provider_property->setAccessible(true);
        $provider_property->setValue($this->api_router, $mock_provider);
        
        // Test the method
        $result = $this->api_router->generate_completion('Test prompt');
        
        $this->assertEquals('This is a mock response from OpenAI', $result);
    }
}
```

### Testing Private Methods

To test private or protected methods:

```php
class MPAI_Context_Manager_Test extends WP_UnitTestCase {
    public function test_get_system_info() {
        $context_manager = new MPAI_Context_Manager();
        
        // Access the private method using reflection
        $reflection = new ReflectionClass($context_manager);
        $method = $reflection->getMethod('get_system_info');
        $method->setAccessible(true);
        
        // Call the method
        $system_info = $method->invoke($context_manager);
        
        // Assertions
        $this->assertIsArray($system_info);
        $this->assertArrayHasKey('plugin_version', $system_info);
        $this->assertArrayHasKey('wordpress_version', $system_info);
    }
}
```

## Integration Testing

Integration tests focus on how components work together.

### Testing Component Interactions

```php
class MPAI_Tool_Integration_Test extends WP_UnitTestCase {
    public function setUp(): void {
        parent::setUp();
        $this->tool_registry = MPAI_Tool_Registry::get_instance();
        $this->context_manager = MPAI_Context_Manager::get_instance();
        $this->chat = new MPAI_Chat();
    }
    
    public function test_tool_execution_in_chat_flow() {
        // Register a test tool
        $test_tool = new MPAI_Test_Tool();
        $this->tool_registry->register_tool($test_tool);
        
        // Create a request that should trigger tool execution
        $request = 'Use the test tool with param1=test';
        
        // Mock the API to return a tool execution command
        add_filter('mpai_generate_completion', function() {
            return json_encode([
                'tool_calls' => [
                    [
                        'name' => 'test_tool',
                        'parameters' => [
                            'param1' => 'test'
                        ]
                    ]
                ]
            ]);
        });
        
        // Process the request
        $response = $this->chat->process_chat_request($request, []);
        
        // Verify tool was executed
        $this->assertIsArray($response);
        $this->assertArrayHasKey('tool_executions', $response);
        $this->assertCount(1, $response['tool_executions']);
        $this->assertEquals('test_tool', $response['tool_executions'][0]['name']);
        
        // Clean up
        remove_all_filters('mpai_generate_completion');
    }
}
```

### Testing WordPress Hooks

```php
class MPAI_Hooks_Test extends WP_UnitTestCase {
    public function test_filters_are_applied() {
        // Set up test data
        $original_data = array('test' => 'value');
        
        // Add filter
        add_filter('mpai_test_filter', function($data) {
            $data['test'] = 'modified';
            $data['added'] = 'new value';
            return $data;
        });
        
        // Apply filter
        $modified_data = apply_filters('mpai_test_filter', $original_data);
        
        // Assertions
        $this->assertNotEquals($original_data, $modified_data);
        $this->assertEquals('modified', $modified_data['test']);
        $this->assertEquals('new value', $modified_data['added']);
        
        // Clean up
        remove_all_filters('mpai_test_filter');
    }
    
    public function test_actions_are_executed() {
        // Set up test flag
        $test_flag = false;
        
        // Add action
        add_action('mpai_test_action', function() use (&$test_flag) {
            $test_flag = true;
        });
        
        // Trigger action
        do_action('mpai_test_action');
        
        // Assertion
        $this->assertTrue($test_flag);
        
        // Clean up
        remove_all_actions('mpai_test_action');
    }
}
```

## API Testing

Test interactions with external AI APIs:

### Mock API Testing

```php
class MPAI_OpenAI_API_Test extends WP_UnitTestCase {
    public function setUp(): void {
        parent::setUp();
        $this->openai = new MPAI_OpenAI();
    }
    
    public function test_generate_completion_with_mock() {
        // Mock the HTTP request
        add_filter('pre_http_request', function($preempt, $args, $url) {
            if (strpos($url, 'api.openai.com') !== false) {
                return array(
                    'response' => array('code' => 200),
                    'body' => json_encode(array(
                        'choices' => array(
                            array(
                                'message' => array(
                                    'content' => 'This is a mock response from the OpenAI API'
                                )
                            )
                        )
                    ))
                );
            }
            return $preempt;
        }, 10, 3);
        
        // Test the API call
        $result = $this->openai->generate_completion('Test prompt');
        
        // Assertions
        $this->assertEquals('This is a mock response from the OpenAI API', $result);
        
        // Clean up
        remove_all_filters('pre_http_request');
    }
    
    public function test_generate_completion_with_error() {
        // Mock an API error
        add_filter('pre_http_request', function($preempt, $args, $url) {
            if (strpos($url, 'api.openai.com') !== false) {
                return array(
                    'response' => array('code' => 429),
                    'body' => json_encode(array(
                        'error' => array(
                            'message' => 'Rate limit exceeded',
                            'type' => 'rate_limit_error'
                        )
                    ))
                );
            }
            return $preempt;
        }, 10, 3);
        
        // Test the API call
        $result = $this->openai->generate_completion('Test prompt');
        
        // Assertions
        $this->assertWPError($result);
        $this->assertEquals('rate_limit_error', $result->get_error_code());
        
        // Clean up
        remove_all_filters('pre_http_request');
    }
}
```

### Live API Testing

For occasional tests against the real API (use sparingly):

```php
class MPAI_Live_API_Test extends WP_UnitTestCase {
    protected $skip_live_tests = true;
    
    public function setUp(): void {
        parent::setUp();
        
        // Skip tests if no API key or if skip flag is set
        if (!defined('MPAI_TEST_OPENAI_API_KEY') || $this->skip_live_tests) {
            $this->markTestSkipped('Skipping live API tests');
        }
        
        $this->openai = new MPAI_OpenAI();
        
        // Set the test API key
        add_filter('option_mpai_openai_api_key', function() {
            return MPAI_TEST_OPENAI_API_KEY;
        });
    }
    
    public function tearDown(): void {
        remove_all_filters('option_mpai_openai_api_key');
        parent::tearDown();
    }
    
    public function test_live_api_connection() {
        $prompt = 'Say "This is a test" and nothing else.';
        $result = $this->openai->generate_completion($prompt);
        
        $this->assertNotWPError($result);
        $this->assertStringContainsString('This is a test', $result);
    }
}
```

## UI Testing

For testing the plugin's user interface:

### Admin Page Testing

```php
class MPAI_Admin_UI_Test extends WP_UnitTestCase {
    public function setUp(): void {
        parent::setUp();
        
        // Create an admin user
        $this->admin_id = $this->factory->user->create(array(
            'role' => 'administrator'
        ));
        
        wp_set_current_user($this->admin_id);
        
        // Initialize the admin class
        $this->admin = new MPAI_Admin();
    }
    
    public function tearDown(): void {
        wp_set_current_user(0);
        parent::tearDown();
    }
    
    public function test_admin_page_renders() {
        // Start output buffering
        ob_start();
        
        // Render the admin page
        $this->admin->render_admin_page();
        
        // Get the output
        $output = ob_get_clean();
        
        // Assertions
        $this->assertNotEmpty($output);
        $this->assertStringContainsString('MemberPress AI Assistant', $output);
        $this->assertStringContainsString('class="mpai-admin-page"', $output);
    }
    
    public function test_settings_page_has_required_fields() {
        // Start output buffering
        ob_start();
        
        // Render the settings page
        $this->admin->render_settings_page();
        
        // Get the output
        $output = ob_get_clean();
        
        // Assertions
        $this->assertStringContainsString('name="mpai_api_key"', $output);
        $this->assertStringContainsString('name="mpai_model"', $output);
        $this->assertStringContainsString('type="submit"', $output);
    }
}
```

### Ajax Testing

```php
class MPAI_Ajax_Test extends WP_UnitTestCase {
    public function setUp(): void {
        parent::setUp();
        
        // Create an admin user
        $this->admin_id = $this->factory->user->create(array(
            'role' => 'administrator'
        ));
        
        wp_set_current_user($this->admin_id);
        
        // Initialize the ajax handler
        $this->ajax_handler = new MPAI_AJAX_Handler();
        $this->ajax_handler->init();
    }
    
    public function tearDown(): void {
        wp_set_current_user(0);
        parent::tearDown();
    }
    
    public function test_process_chat_request_ajax() {
        // Prepare the request
        $_POST['prompt'] = 'Test prompt';
        $_POST['context'] = 'test';
        $_POST['mpai_nonce'] = wp_create_nonce('mpai_chat');
        
        // Mock the chat processing
        add_filter('mpai_process_chat_request', function($response, $prompt, $context) {
            return array(
                'response' => 'This is a test response',
                'source' => 'test'
            );
        }, 10, 3);
        
        // Simulate the AJAX request
        try {
            $this->_handleAjax('mpai_process_chat');
        } catch (WPAjaxDieContinueException $e) {
            // Expected, do nothing
        }
        
        // Get the response
        $response = json_decode($this->_last_response, true);
        
        // Assertions
        $this->assertTrue($response['success']);
        $this->assertEquals('This is a test response', $response['data']['response']);
        
        // Clean up
        remove_all_filters('mpai_process_chat_request');
    }
    
    public function test_invalid_nonce_fails() {
        // Prepare the request with invalid nonce
        $_POST['prompt'] = 'Test prompt';
        $_POST['context'] = 'test';
        $_POST['mpai_nonce'] = 'invalid_nonce';
        
        // Simulate the AJAX request
        try {
            $this->_handleAjax('mpai_process_chat');
        } catch (WPAjaxDieStopException $e) {
            // Expected, do nothing
        }
        
        // Get the response
        $response = json_decode($this->_last_response, true);
        
        // Assertions
        $this->assertFalse($response['success']);
        $this->assertEquals('invalid_nonce', $response['data']['code']);
    }
}
```

## Performance Testing

Test the performance of key plugin components:

### Response Time Testing

```php
class MPAI_Performance_Test extends WP_UnitTestCase {
    public function test_context_manager_performance() {
        $context_manager = MPAI_Context_Manager::get_instance();
        
        // Measure execution time
        $start_time = microtime(true);
        
        // Run the operation multiple times
        for ($i = 0; $i < 10; $i++) {
            $context = $context_manager->get_chat_context();
        }
        
        $end_time = microtime(true);
        $execution_time = $end_time - $start_time;
        
        // Assert execution time is within acceptable limits
        $this->assertLessThan(0.5, $execution_time, 'Context generation is too slow');
    }
    
    public function test_system_cache_performance() {
        $system_cache = MPAI_System_Cache::get_instance();
        
        // Test with a computationally expensive operation
        $expensive_operation = function() {
            // Simulate expensive calculation
            $result = 0;
            for ($i = 0; $i < 10000; $i++) {
                $result += sin($i) * cos($i);
            }
            return $result;
        };
        
        // First run (uncached)
        $start_time = microtime(true);
        $result1 = $system_cache->remember('test_expensive_operation', $expensive_operation);
        $uncached_time = microtime(true) - $start_time;
        
        // Second run (should be cached)
        $start_time = microtime(true);
        $result2 = $system_cache->remember('test_expensive_operation', $expensive_operation);
        $cached_time = microtime(true) - $start_time;
        
        // Assertions
        $this->assertEquals($result1, $result2, 'Cached result should match original');
        $this->assertLessThan($uncached_time * 0.1, $cached_time, 'Cached operation should be at least 10x faster');
    }
}
```

### Memory Usage Testing

```php
class MPAI_Memory_Test extends WP_UnitTestCase {
    public function test_memory_usage() {
        // Measure initial memory
        $initial_memory = memory_get_usage();
        
        // Run operation
        $chat = new MPAI_Chat();
        $context = MPAI_Context_Manager::get_instance()->get_chat_context();
        
        // Mock API to prevent actual external calls
        add_filter('mpai_generate_completion', function() {
            return 'Test response';
        });
        
        $response = $chat->process_chat_request('Test request', $context);
        
        // Measure final memory
        $final_memory = memory_get_usage();
        $memory_used = $final_memory - $initial_memory;
        
        // Assert memory usage is within limits
        $this->assertLessThan(1024 * 1024 * 5, $memory_used, 'Memory usage exceeds 5MB limit');
        
        // Clean up
        remove_all_filters('mpai_generate_completion');
    }
}
```

## Regression Testing

Test for previously fixed issues:

```php
class MPAI_Regression_Test extends WP_UnitTestCase {
    /**
     * @ticket 123
     */
    public function test_context_data_validation_issue() {
        // This test verifies that issue #123 is fixed
        
        $context_manager = MPAI_Context_Manager::get_instance();
        
        // The bug was related to invalid context data causing errors
        $invalid_context = array(
            'malformed_data' => array('unclosed' => array('nested' => true),
            'missing_bracket' => 'test'
        );
        
        // In the bug, this would cause a PHP error
        $result = $context_manager->validate_context($invalid_context);
        
        // Now it should return a clean, valid context instead of failing
        $this->assertIsArray($result);
        $this->assertArrayNotHasKey('malformed_data', $result);
    }
    
    /**
     * @ticket 456
     */
    public function test_api_error_handling_regression() {
        // This test verifies that issue #456 is fixed
        
        $api_router = MPAI_API_Router::get_instance();
        
        // Mock a specific API error that was not handled correctly
        add_filter('pre_http_request', function($preempt, $args, $url) {
            if (strpos($url, 'api.openai.com') !== false) {
                return new WP_Error('http_request_failed', 'Connection timed out');
            }
            return $preempt;
        }, 10, 3);
        
        // The bug was that this would return a generic error without details
        $result = $api_router->generate_completion('Test prompt');
        
        // Now it should return a specific error with the connection details
        $this->assertWPError($result);
        $this->assertEquals('api_connection_error', $result->get_error_code());
        $this->assertStringContainsString('Connection timed out', $result->get_error_message());
        
        // Clean up
        remove_all_filters('pre_http_request');
    }
}
```

## Security Testing

Test security aspects of the plugin:

```php
class MPAI_Security_Test extends WP_UnitTestCase {
    public function setUp(): void {
        parent::setUp();
        
        // Create users with different roles
        $this->admin_id = $this->factory->user->create(array('role' => 'administrator'));
        $this->editor_id = $this->factory->user->create(array('role' => 'editor'));
        $this->subscriber_id = $this->factory->user->create(array('role' => 'subscriber'));
    }
    
    public function test_admin_page_permissions() {
        // Test that non-admins cannot access the admin page
        
        // First as editor
        wp_set_current_user($this->editor_id);
        $this->assertFalse(MPAI_Admin::current_user_can_access());
        
        // Then as subscriber
        wp_set_current_user($this->subscriber_id);
        $this->assertFalse(MPAI_Admin::current_user_can_access());
        
        // Finally as admin
        wp_set_current_user($this->admin_id);
        $this->assertTrue(MPAI_Admin::current_user_can_access());
    }
    
    public function test_nonce_validation() {
        $ajax_handler = new MPAI_AJAX_Handler();
        
        // Set current user
        wp_set_current_user($this->admin_id);
        
        // Test with invalid nonce
        $_REQUEST['mpai_nonce'] = 'invalid_nonce';
        
        // This should throw an exception with a 403 status
        try {
            $reflection = new ReflectionClass($ajax_handler);
            $method = $reflection->getMethod('verify_nonce');
            $method->setAccessible(true);
            $method->invoke($ajax_handler, 'mpai_nonce', 'test_action');
            
            $this->fail('Invalid nonce was accepted');
        } catch (Exception $e) {
            $this->assertEquals(403, $e->getCode());
        }
        
        // Test with valid nonce
        $_REQUEST['mpai_nonce'] = wp_create_nonce('test_action');
        
        try {
            $reflection = new ReflectionClass($ajax_handler);
            $method = $reflection->getMethod('verify_nonce');
            $method->setAccessible(true);
            $result = $method->invoke($ajax_handler, 'mpai_nonce', 'test_action');
            
            // If we got here, the nonce was accepted
            $this->assertTrue(true);
        } catch (Exception $e) {
            $this->fail('Valid nonce was rejected: ' . $e->getMessage());
        }
    }
    
    public function test_input_sanitization() {
        $input_processor = new MPAI_Input_Processor();
        
        // Test various types of potentially malicious input
        $inputs = array(
            '<script>alert("XSS")</script>' => '',
            'normal text' => 'normal text',
            'https://example.com/?a=1&b=2' => 'https://example.com/?a=1&b=2',
            "Line 1\r\nLine 2" => "Line 1\nLine 2",
            '   Padded   ' => 'Padded',
        );
        
        foreach ($inputs as $input => $expected) {
            $sanitized = $input_processor->sanitize_text($input);
            $this->assertEquals($expected, $sanitized);
        }
    }
}
```

## Continuous Integration

### GitHub Actions Workflow

Create a `.github/workflows/tests.yml` file:

```yaml
name: Run Tests

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main, develop ]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:5.7
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: wordpress_test
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3
    
    strategy:
      matrix:
        php-versions: ['7.4', '8.0', '8.1']
        wordpress-versions: ['5.9', '6.0', 'latest']
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: ${{ matrix.php-versions }}
        extensions: mbstring, intl, mysqli
        coverage: xdebug
    
    - name: Install dependencies
      run: composer install --prefer-dist --no-progress
    
    - name: Setup WordPress Test Environment
      run: |
        bash bin/install-wp-tests.sh wordpress_test root password localhost ${{ matrix.wordpress-versions }}
    
    - name: Run tests
      run: composer test
      env:
        MPAI_TEST_OPENAI_API_KEY: ${{ secrets.MPAI_TEST_OPENAI_API_KEY }}
        MPAI_TEST_ANTHROPIC_API_KEY: ${{ secrets.MPAI_TEST_ANTHROPIC_API_KEY }}
    
    - name: Upload coverage to Codecov
      uses: codecov/codecov-action@v3
      with:
        files: ./coverage.xml
```

## Test Coverage Reports

### Generating Coverage Reports

Create coverage reports with PHPUnit:

```bash
vendor/bin/phpunit --coverage-html ./coverage-report --coverage-clover ./coverage.xml
```

### Coverage Thresholds

Set up coverage thresholds in `phpunit.xml`:

```xml
<phpunit>
    <!-- ... -->
    <coverage>
        <include>
            <directory suffix=".php">includes</directory>
        </include>
        <report>
            <clover outputFile="coverage.xml"/>
            <html outputDirectory="coverage-report"/>
            <text outputFile="php://stdout" showUncoveredFiles="true"/>
        </report>
    </coverage>
    <!-- ... -->
</phpunit>
```

## Troubleshooting Tests

### Common Test Issues

1. **Database Reset Issues**
   - Symptoms: Tests that change the database fail inconsistently
   - Solution: Ensure `tearDown()` methods clean up all created data

2. **Mock Objects Not Working**
   - Symptoms: Tests fail because real methods are called instead of mocks
   - Solution: Check that you're injecting mocks correctly and using the right method signatures

3. **WordPress Functions Not Available**
   - Symptoms: PHP errors about undefined WordPress functions
   - Solution: Ensure the WordPress testing environment is properly set up

4. **Hook/Filter Issues**
   - Symptoms: Hooks not firing or filters not applied in tests
   - Solution: Make sure to remove all hooks/filters in `tearDown()` to avoid test pollution

### Debugging Tests

For complex test issues:

```php
// Add temporary debug code to test methods
public function test_problematic_feature() {
    // Enable debug mode for this test
    define('MPAI_DEBUG', true);
    
    // Add debug logging
    error_log('Test start: ' . __METHOD__);
    
    // Inspect variables
    $result = $this->some_method();
    error_log('Result: ' . print_r($result, true));
    
    // Force-dump variable to browser/console
    var_dump($result); // Will appear in PHPUnit output
    
    // Continue with assertions
    $this->assertNotEmpty($result);
}
```

## Best Practices Summary

1. **Keep Tests Focused**
   - Each test should verify one specific behavior
   - Use descriptive test method names that explain what's being tested

2. **Isolate Tests**
   - Tests should not depend on each other
   - Each test should set up its own test environment and clean up afterward

3. **Mock External Dependencies**
   - Use mocks for API calls, database access, and other external services
   - Test the integration with real dependencies separately

4. **Test Edge Cases**
   - Include tests for error conditions, empty inputs, and boundary values
   - Don't just test the "happy path"

5. **Maintain Good Coverage**
   - Aim for 80%+ code coverage
   - Prioritize testing complex logic and error-prone code

6. **Keep Tests Fast**
   - Unit tests should run quickly
   - Use integration and end-to-end tests sparingly

7. **Maintain Test Documentation**
   - Keep this guide updated with new testing patterns
   - Document complex test setups with comments

## Document Revision History

| Date | Version | Changes |
|------|---------|---------|
| 2025-04-06 | 1.0.0 | Initial document creation |