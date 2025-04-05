<?php
/**
 * Test script for State Validation System
 *
 * This script demonstrates and tests the State Validation System functionality.
 */

// Load WordPress
// Calculate the path to wp-load.php
$wp_load_path = dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . '/wp-load.php';

// Verify path exists
if (!file_exists($wp_load_path)) {
    echo "Error: wp-load.php not found at {$wp_load_path}<br>";
    // Try alternative relative path
    $wp_load_path = '../../../../wp-load.php';
    echo "Trying alternative path: {$wp_load_path}<br>";
}

require_once($wp_load_path);

// Check if user is logged in and is admin
if (!current_user_can('manage_options')) {
    wp_die('You do not have sufficient permissions to access this page.');
}

// Initialize the State Validator if it's not already loaded
if (!function_exists('mpai_init_state_validator')) {
    require_once(dirname(__DIR__) . '/includes/class-mpai-state-validator.php');
}

/**
 * Run tests for the State Validation System
 *
 * @return array Test results
 */
function run_state_validation_tests() {
    $results = [
        'tests' => [],
        'passed' => 0,
        'failed' => 0,
        'total' => 0
    ];
    
    // Get state validator instance
    $state_validator = mpai_init_state_validator();
    
    // Test 1: System Invariants Verification
    $test_name = 'System Invariants Verification';
    $invariants_result = $state_validator->verify_invariants('system');
    add_test_result($results, $test_name, $invariants_result, 'System invariants should be satisfied');
    
    // Test 2: API Invariants Verification
    $test_name = 'API Invariants Verification';
    $api_invariants_result = $state_validator->verify_invariants('api');
    add_test_result($results, $test_name, $api_invariants_result, 'API invariants should be satisfied');
    
    // Test 3: Custom Invariant Registration
    $test_name = 'Custom Invariant Registration';
    $custom_invariant_result = $state_validator->register_invariant(
        'test_component',
        'always_true',
        function() {
            return true;
        }
    );
    add_test_result($results, $test_name, $custom_invariant_result, 'Should be able to register custom invariant');
    
    // Test 4: Custom Invariant Verification
    $test_name = 'Custom Invariant Verification';
    $custom_verify_result = $state_validator->verify_invariants('test_component');
    add_test_result($results, $test_name, $custom_verify_result, 'Custom invariant should be satisfied');
    
    // Test 5: Component State Monitoring
    $test_name = 'Component State Monitoring';
    $initial_state = [
        'instance_id' => '123456',
        'version' => MPAI_VERSION,
        'count' => 5
    ];
    $monitoring_result = $state_validator->monitor_component_state('test_component', $initial_state);
    add_test_result($results, $test_name, $monitoring_result, 'Should be able to monitor component state');
    
    // Test 6: Component State Consistency Check
    $test_name = 'Component State Consistency Check';
    $updated_state = [
        'instance_id' => '123456', // Same as before
        'version' => MPAI_VERSION, // Same as before
        'count' => 6              // Only this value changed
    ];
    $consistency_result = $state_validator->monitor_component_state('test_component', $updated_state);
    add_test_result($results, $test_name, $consistency_result, 'State consistency check should pass for mutable properties');
    
    // Test 7: Assertion Test
    $test_name = 'Assertion Test';
    $assertion_result = $state_validator->assert(true, 'Test assertion');
    add_test_result($results, $test_name, $assertion_result, 'Assertion should pass for true condition');
    
    // Test 8: Validation Rule Registration
    $test_name = 'Validation Rule Registration';
    $rule_result = $state_validator->register_validation_rule(
        'test_component',
        'test_rule',
        function($component) {
            return true;
        }
    );
    add_test_result($results, $test_name, $rule_result, 'Should be able to register validation rule');
    
    // Test 9: Component Validation
    $test_name = 'Component Validation';
    $test_component = new stdClass();
    $validation_result = $state_validator->validate_component('test_component', $test_component);
    $valid = $validation_result === true || !is_wp_error($validation_result);
    add_test_result($results, $test_name, $valid, 'Component validation should pass for test component');
    
    // Test 10: Pre-condition Registration
    $test_name = 'Pre-condition Registration';
    $precondition_result = $state_validator->register_precondition(
        'test_component',
        'test_operation',
        function($args) {
            return true;
        }
    );
    add_test_result($results, $test_name, $precondition_result, 'Should be able to register pre-condition');
    
    // Test 11: Post-condition Registration
    $test_name = 'Post-condition Registration';
    $postcondition_result = $state_validator->register_postcondition(
        'test_component',
        'test_operation',
        function($result, $args) {
            return true;
        }
    );
    add_test_result($results, $test_name, $postcondition_result, 'Should be able to register post-condition');
    
    // Test 12: Pre-condition Check
    $test_name = 'Pre-condition Check';
    $precondition_check = $state_validator->check_preconditions('test_component', 'test_operation', []);
    $valid = $precondition_check === true || !is_wp_error($precondition_check);
    add_test_result($results, $test_name, $valid, 'Pre-condition check should pass');
    
    // Test 13: Post-condition Check
    $test_name = 'Post-condition Check';
    $postcondition_check = $state_validator->check_postconditions('test_component', 'test_operation', 'result', []);
    $valid = $postcondition_check === true || !is_wp_error($postcondition_check);
    add_test_result($results, $test_name, $valid, 'Post-condition check should pass');
    
    // Test 14: Operation Verification
    $test_name = 'Operation Verification';
    $operation_result = $state_validator->verify_operation(
        'test_component',
        'test_operation',
        function() {
            return 'success';
        },
        []
    );
    $valid = $operation_result === 'success';
    add_test_result($results, $test_name, $valid, 'Operation should execute and return result');
    
    // Test 15: Get Component State
    $test_name = 'Get Component State';
    $state = $state_validator->get_component_state('test_component');
    $valid = $state !== null && isset($state['instance_id']) && $state['instance_id'] === '123456';
    add_test_result($results, $test_name, $valid, 'Should retrieve previously stored component state');
    
    return $results;
}

/**
 * Add a test result to the results array
 *
 * @param array &$results Results array to update
 * @param string $test_name Test name
 * @param bool $result Test result
 * @param string $description Test description
 */
function add_test_result(&$results, $test_name, $result, $description) {
    $results['total']++;
    
    if ($result) {
        $results['passed']++;
        $status = 'PASS';
    } else {
        $results['failed']++;
        $status = 'FAIL';
    }
    
    $results['tests'][] = [
        'name' => $test_name,
        'status' => $status,
        'description' => $description
    ];
}

// Run the tests
$test_results = run_state_validation_tests();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>State Validation System Test</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            margin: 20px;
            background: #f0f0f1;
            line-height: 1.5;
            color: #3c434a;
        }
        .container {
            max-width: 960px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border: 1px solid #c3c4c7;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
            border-radius: 4px;
        }
        h1 {
            color: #1d2327;
            font-size: 23px;
            font-weight: 400;
            margin-top: 0;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        h2 {
            font-size: 18px;
            color: #1d2327;
            margin-top: 30px;
            margin-bottom: 15px;
        }
        pre {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-family: monospace;
            white-space: pre-wrap;
        }
        .instructions {
            background: #fff8e5;
            padding: 15px;
            border-left: 4px solid #ffb900;
            margin-bottom: 20px;
        }
        .code-block {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-family: monospace;
            white-space: pre-wrap;
            margin-bottom: 20px;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 20px;
        }
        th, td {
            text-align: left;
            padding: 8px;
            border: 1px solid #ddd;
        }
        th {
            background-color: #f0f0f1;
            font-weight: 600;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .pass {
            color: #008000;
            font-weight: bold;
        }
        .fail {
            color: #ff0000;
            font-weight: bold;
        }
        .summary {
            margin-top: 20px;
            font-size: 16px;
            font-weight: bold;
        }
        .pass-rate {
            font-size: 18px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>State Validation System Test</h1>
        
        <div class="instructions">
            <p><strong>About this test:</strong> This page tests the State Validation System functionality to ensure it correctly validates system state, monitors component state changes, and handles pre/post conditions for operations.</p>
            <p>The State Validation System provides consistency checking and state corruption detection throughout the plugin.</p>
        </div>
        
        <h2>Test Results</h2>
        
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Test</th>
                    <th>Status</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($test_results['tests'] as $index => $test): ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td><?php echo esc_html($test['name']); ?></td>
                    <td class="<?php echo strtolower($test['status']); ?>"><?php echo esc_html($test['status']); ?></td>
                    <td><?php echo esc_html($test['description']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="summary">
            <p>Summary: <?php echo $test_results['passed']; ?> passed, <?php echo $test_results['failed']; ?> failed, <?php echo $test_results['total']; ?> total</p>
            
            <p class="pass-rate">Pass Rate: <?php echo round(($test_results['passed'] / $test_results['total']) * 100); ?>%</p>
        </div>
        
        <h2>How to Use the State Validation System</h2>
        
        <p>To use the State Validation System in your code, follow these examples:</p>
        
        <h3>1. Verify System Invariants</h3>
        <div class="code-block">// Get state validator instance
$state_validator = mpai_init_state_validator();

// Verify all invariants
$invariants_valid = $state_validator->verify_invariants();

// Verify specific component invariants
$api_invariants_valid = $state_validator->verify_invariants('api');</div>
        
        <h3>2. Validate Component State</h3>
        <div class="code-block">// Validate an API client
$api_client = MPAI_API_Router::get_instance()->get_primary_api();
$result = $state_validator->validate_component('api_client', $api_client);

if (is_wp_error($result)) {
    // Handle validation failure
    error_log('API client validation failed: ' . $result->get_error_message());
}</div>
        
        <h3>3. Monitor Component State</h3>
        <div class="code-block">// Monitor agent orchestrator state
$orchestrator = MPAI_Agent_Orchestrator::get_instance();
$state = [
    'instance_id' => spl_object_hash($orchestrator),
    'agent_count' => count($orchestrator->get_available_agents()),
    'version' => MPAI_VERSION
];

$state_validator->monitor_component_state('agent_orchestrator', $state);</div>
        
        <h3>4. Wrap Operations with Pre/Post Conditions</h3>
        <div class="code-block">// Register pre-condition
$state_validator->register_precondition(
    'api_router',
    'process_request',
    function($args) {
        // Check if request has required fields
        return isset($args['message']) && !empty($args['message']);
    }
);

// Register post-condition
$state_validator->register_postcondition(
    'api_router',
    'process_request',
    function($result, $args) {
        // Check if result is valid
        return is_array($result) && isset($result['success']);
    }
);

// Wrap operation with verification
$result = $state_validator->verify_operation(
    'api_router',
    'process_request',
    function($request_data) use ($api_router) {
        // Process the request
        return $api_router->process_request($request_data);
    },
    [$request_data]
);</div>
        
        <hr>
        <p><a href="<?php echo admin_url('admin.php?page=memberpress-ai-assistant-settings'); ?>" class="button">Back to Plugin Settings</a></p>
    </div>
</body>
</html>