<?php
/**
 * Test Input Validator for MemberPress AI Assistant
 *
 * @package MemberPress AI Assistant
 */

// Initialize WordPress
require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/wp-load.php';

// Ensure admin environment
require_once ABSPATH . 'wp-admin/includes/admin.php';

// Load the Input Validator class
require_once dirname(__FILE__) . '/../includes/class-mpai-input-validator.php';

// Define test function
function mpai_test_input_validator() {
    $output = '<div class="wrap">';
    $output .= '<h1>Input Validator Test</h1>';
    
    // Run various tests
    $output .= '<h2>Basic Validation Tests</h2>';
    $output .= run_basic_validation_tests();
    
    $output .= '<h2>Schema Loading Tests</h2>';
    $output .= run_schema_loading_tests();
    
    $output .= '<h2>Sanitization Tests</h2>';
    $output .= run_sanitization_tests();
    
    $output .= '<h2>Error Handling Tests</h2>';
    $output .= run_error_handling_tests();
    
    $output .= '</div>';
    
    echo $output;
}

/**
 * Run basic validation tests
 */
function run_basic_validation_tests() {
    $output = '<table class="widefat">';
    $output .= '<thead><tr><th>Test Name</th><th>Input</th><th>Expected Result</th><th>Actual Result</th><th>Status</th></tr></thead>';
    $output .= '<tbody>';
    
    // Test string validation
    $validator = new MPAI_Input_Validator();
    $validator->add_rule('name', ['type' => 'string', 'required' => true]);
    
    $output .= test_case(
        'String Required Field - Valid',
        ['name' => 'John'],
        'Valid = true',
        $validator->validate(['name' => 'John']),
        true
    );
    
    $output .= test_case(
        'String Required Field - Missing',
        ['something_else' => 'value'],
        'Valid = false, Error for name',
        $validator->validate(['something_else' => 'value']),
        false
    );
    
    // Test number validation
    $validator = new MPAI_Input_Validator();
    $validator->add_rule('age', ['type' => 'integer', 'min' => 18, 'max' => 100]);
    
    $output .= test_case(
        'Integer Field - Valid',
        ['age' => 25],
        'Valid = true',
        $validator->validate(['age' => 25]),
        true
    );
    
    $output .= test_case(
        'Integer Field - Below Min',
        ['age' => 15],
        'Valid = false, Error for age',
        $validator->validate(['age' => 15]),
        false
    );
    
    $output .= test_case(
        'Integer Field - Above Max',
        ['age' => 120],
        'Valid = false, Error for age',
        $validator->validate(['age' => 120]),
        false
    );
    
    // Test enum validation
    $validator = new MPAI_Input_Validator();
    $validator->add_rule('color', ['type' => 'string', 'enum' => ['red', 'green', 'blue']]);
    
    $output .= test_case(
        'Enum Field - Valid',
        ['color' => 'red'],
        'Valid = true',
        $validator->validate(['color' => 'red']),
        true
    );
    
    $output .= test_case(
        'Enum Field - Invalid Value',
        ['color' => 'yellow'],
        'Valid = false, Error for color',
        $validator->validate(['color' => 'yellow']),
        false
    );
    
    $output .= '</tbody></table>';
    
    return $output;
}

/**
 * Run schema loading tests
 */
function run_schema_loading_tests() {
    $output = '<table class="widefat">';
    $output .= '<thead><tr><th>Test Name</th><th>Input</th><th>Expected Result</th><th>Actual Result</th><th>Status</th></tr></thead>';
    $output .= '<tbody>';
    
    // Create test schema
    $schema = [
        'properties' => [
            'name' => [
                'type' => 'string',
                'description' => 'The user name',
                'minLength' => 3
            ],
            'age' => [
                'type' => 'integer',
                'description' => 'The user age',
                'minimum' => 18
            ],
            'role' => [
                'type' => 'string',
                'enum' => ['admin', 'editor', 'user'],
                'default' => 'user'
            ]
        ],
        'required' => ['name', 'age']
    ];
    
    // Test schema loading
    $validator = new MPAI_Input_Validator();
    $validator->load_from_schema($schema);
    
    $output .= test_case(
        'Schema Loading - Valid Data',
        ['name' => 'John Doe', 'age' => 25, 'role' => 'admin'],
        'Valid = true',
        $validator->validate(['name' => 'John Doe', 'age' => 25, 'role' => 'admin']),
        true
    );
    
    $output .= test_case(
        'Schema Loading - Missing Required Field',
        ['name' => 'John Doe'],
        'Valid = false, Error for age',
        $validator->validate(['name' => 'John Doe']),
        false
    );
    
    $output .= test_case(
        'Schema Loading - Default Value Applied',
        ['name' => 'John Doe', 'age' => 25],
        'Valid = true, role = user',
        $validator->validate(['name' => 'John Doe', 'age' => 25]),
        true
    );
    
    $output .= test_case(
        'Schema Loading - String Too Short',
        ['name' => 'Jo', 'age' => 25],
        'Valid = false, Error for name',
        $validator->validate(['name' => 'Jo', 'age' => 25]),
        false
    );
    
    $output .= '</tbody></table>';
    
    return $output;
}

/**
 * Run sanitization tests
 */
function run_sanitization_tests() {
    $output = '<table class="widefat">';
    $output .= '<thead><tr><th>Test Name</th><th>Input</th><th>Expected Result</th><th>Actual Result</th><th>Status</th></tr></thead>';
    $output .= '<tbody>';
    
    $validator = new MPAI_Input_Validator();
    
    // Test string sanitization
    $output .= test_case(
        'String Sanitization',
        '<script>alert("XSS");</script>Hello World',
        'Sanitized string without script tags',
        $validator->sanitize('<script>alert("XSS");</script>Hello World', 'string'),
        true
    );
    
    // Test integer sanitization
    $output .= test_case(
        'Integer Sanitization',
        '42abc',
        '42',
        $validator->sanitize('42abc', 'integer'),
        true
    );
    
    // Test float sanitization
    $output .= test_case(
        'Float Sanitization',
        '3.14abc',
        '3.14',
        $validator->sanitize('3.14abc', 'number'),
        true
    );
    
    // Test boolean sanitization
    $output .= test_case(
        'Boolean Sanitization - String "true"',
        'true',
        'true',
        $validator->sanitize('true', 'boolean'),
        true
    );
    
    $output .= test_case(
        'Boolean Sanitization - String "false"',
        'false',
        'false',
        $validator->sanitize('false', 'boolean'),
        true
    );
    
    // Test array sanitization
    $input_array = [
        'name' => '<script>alert("XSS");</script>John',
        'age' => '42abc',
        'nested' => [
            'key' => '<b>Hello</b>'
        ]
    ];
    
    $output .= test_case(
        'Array Sanitization',
        json_encode($input_array),
        'Sanitized array with all values properly sanitized',
        json_encode($validator->sanitize($input_array)),
        true
    );
    
    $output .= '</tbody></table>';
    
    return $output;
}

/**
 * Run error handling tests
 */
function run_error_handling_tests() {
    $output = '<table class="widefat">';
    $output .= '<thead><tr><th>Test Name</th><th>Input</th><th>Expected Result</th><th>Actual Result</th><th>Status</th></tr></thead>';
    $output .= '<tbody>';
    
    // Test custom error messages
    $validator = new MPAI_Input_Validator();
    $validator->add_rule('email', ['type' => 'string', 'format' => 'email', 'required' => true], 'Please enter a valid email address');
    
    $output .= test_case(
        'Custom Error Message',
        ['email' => 'not_an_email'],
        'Valid = false, Error = "Please enter a valid email address"',
        $validator->validate(['email' => 'not_an_email']),
        false
    );
    
    // Test multiple validation errors
    $validator = new MPAI_Input_Validator();
    $validator->add_rule('username', ['type' => 'string', 'required' => true, 'min_length' => 5]);
    $validator->add_rule('password', ['type' => 'string', 'required' => true, 'min_length' => 8]);
    
    $output .= test_case(
        'Multiple Validation Errors',
        ['username' => 'user', 'password' => 'pass'],
        'Valid = false, Errors for both username and password',
        $validator->validate(['username' => 'user', 'password' => 'pass']),
        false
    );
    
    $output .= '</tbody></table>';
    
    return $output;
}

/**
 * Helper function to format a test case row
 */
function test_case($name, $input, $expected, $actual, $expectSuccess) {
    if (is_array($input)) {
        $input = json_encode($input);
    }
    
    if (is_array($actual)) {
        $actualSuccess = isset($actual['valid']) ? $actual['valid'] : false;
        $actual = json_encode($actual);
    } else {
        $actualSuccess = (bool)$actual;
    }
    
    $success = ($expectSuccess === $actualSuccess);
    
    return sprintf(
        '<tr class="%s">
            <td><strong>%s</strong></td>
            <td><pre>%s</pre></td>
            <td><pre>%s</pre></td>
            <td><pre>%s</pre></td>
            <td>%s</td>
        </tr>',
        $success ? 'success' : 'error',
        esc_html($name),
        esc_html($input),
        esc_html($expected),
        esc_html($actual),
        $success ? '✅ PASS' : '❌ FAIL'
    );
}

// Execute the test
mpai_test_input_validator();