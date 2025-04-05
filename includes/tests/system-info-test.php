<?php
/**
 * System Information Test
 * 
 * Provides detailed information about the WordPress and PHP environment
 * 
 * @package MemberPress AI Assistant
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Run system information test
 * 
 * @param array $params Test parameters
 * @return array Test results
 */
function mpai_run_system_info_test($params = []) {
    $start_time = microtime(true);
    
    // WordPress Information
    $wp_info = [
        'version' => get_bloginfo('version'),
        'site_url' => get_bloginfo('url'),
        'home_url' => get_home_url(),
        'is_multisite' => is_multisite() ? 'Yes' : 'No',
        'memory_limit' => WP_MEMORY_LIMIT,
        'debug_mode' => defined('WP_DEBUG') && WP_DEBUG ? 'Enabled' : 'Disabled',
        'timezone' => get_option('timezone_string') ?: get_option('gmt_offset'),
        'language' => get_locale(),
        'permalink_structure' => get_option('permalink_structure') ? get_option('permalink_structure') : 'Default',
    ];
    
    // PHP Information
    $php_info = [
        'version' => phpversion(),
        'os' => PHP_OS,
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time'),
        'post_max_size' => ini_get('post_max_size'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'max_input_vars' => ini_get('max_input_vars'),
        'safe_mode' => ini_get('safe_mode') ? 'Enabled' : 'Disabled',
        'extensions' => get_loaded_extensions(),
    ];
    
    // Plugin Information
    $active_plugins = get_option('active_plugins');
    $plugin_info = [];
    
    foreach ($active_plugins as $plugin) {
        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
        $plugin_info[] = [
            'name' => $plugin_data['Name'],
            'version' => $plugin_data['Version'],
            'author' => $plugin_data['Author'],
        ];
    }
    
    // MemberPress Specific Information
    $memberpress_info = [
        'active' => class_exists('MeprAppCtrl') ? 'Yes' : 'No'
    ];
    
    if (class_exists('MeprAppCtrl')) {
        if (defined('MEPR_VERSION')) {
            $memberpress_info['version'] = MEPR_VERSION;
        }
        
        // Get MemberPress options
        $mepr_options = get_option('mepr_options');
        if (is_array($mepr_options)) {
            $memberpress_info['payment_methods'] = isset($mepr_options['payments_enabled']) ? count($mepr_options['payments_enabled']) : 0;
            $memberpress_info['account_page'] = isset($mepr_options['account_page_id']) ? get_the_title($mepr_options['account_page_id']) : 'Not set';
            $memberpress_info['login_page'] = isset($mepr_options['login_page_id']) ? get_the_title($mepr_options['login_page_id']) : 'Not set';
            $memberpress_info['thank_you_page'] = isset($mepr_options['thankyou_page_id']) ? get_the_title($mepr_options['thankyou_page_id']) : 'Not set';
        }
    }
    
    // MemberPress AI Assistant Information
    $mpai_info = [
        'version' => defined('MPAI_VERSION') ? MPAI_VERSION : 'Unknown',
        'primary_api' => get_option('mpai_primary_api', 'openai'),
        'has_openai_api_key' => !empty(get_option('mpai_api_key', '')),
        'has_anthropic_api_key' => !empty(get_option('mpai_anthropic_api_key', '')),
        'console_logging' => get_option('mpai_enable_console_logging', '0') === '1' ? 'Enabled' : 'Disabled',
    ];
    
    // Run sub-tests
    $sub_tests = [
        'wp_version' => [
            'success' => version_compare(get_bloginfo('version'), '5.0', '>='),
            'message' => 'WordPress version: ' . get_bloginfo('version') . (version_compare(get_bloginfo('version'), '5.0', '>=') ? ' (Compatible)' : ' (Not compatible - requires 5.0+)')
        ],
        'php_version' => [
            'success' => version_compare(phpversion(), '7.0', '>='),
            'message' => 'PHP version: ' . phpversion() . (version_compare(phpversion(), '7.0', '>=') ? ' (Compatible)' : ' (Not compatible - requires 7.0+)')
        ],
        'memory_limit' => [
            'success' => strpos(ini_get('memory_limit'), 'M') !== false && (int)ini_get('memory_limit') >= 64,
            'message' => 'Memory limit: ' . ini_get('memory_limit') . ((int)ini_get('memory_limit') >= 64 ? ' (Sufficient)' : ' (May be insufficient - 64M+ recommended)')
        ],
        'max_execution_time' => [
            'success' => ini_get('max_execution_time') >= 30 || ini_get('max_execution_time') == 0,
            'message' => 'Max execution time: ' . ini_get('max_execution_time') . (ini_get('max_execution_time') >= 30 || ini_get('max_execution_time') == 0 ? ' (Sufficient)' : ' (May be insufficient - 30+ seconds recommended)')
        ],
        'memberpress_integration' => [
            'success' => class_exists('MeprAppCtrl'),
            'message' => 'MemberPress: ' . (class_exists('MeprAppCtrl') ? 'Detected' : 'Not detected')
        ],
        'api_configurations' => [
            'success' => !empty(get_option('mpai_api_key', '')) || !empty(get_option('mpai_anthropic_api_key', '')),
            'message' => 'API Configuration: ' . (!empty(get_option('mpai_api_key', '')) || !empty(get_option('mpai_anthropic_api_key', '')) ? 'At least one API key is configured' : 'No API keys configured')
        ]
    ];
    
    // Calculate overall success based on critical tests
    $critical_tests = ['wp_version', 'php_version', 'api_configurations'];
    $critical_success = true;
    
    foreach ($critical_tests as $test_id) {
        if (!$sub_tests[$test_id]['success']) {
            $critical_success = false;
            break;
        }
    }
    
    // Count test results
    $total_tests = count($sub_tests);
    $passed_tests = 0;
    
    foreach ($sub_tests as $test) {
        if ($test['success']) {
            $passed_tests++;
        }
    }
    
    $end_time = microtime(true);
    
    return [
        'success' => $critical_success,
        'message' => $critical_success 
            ? 'System information test completed successfully. ' . $passed_tests . '/' . $total_tests . ' tests passed.'
            : 'System information test completed with some issues. ' . $passed_tests . '/' . $total_tests . ' tests passed.',
        'data' => [
            'wp' => $wp_info,
            'php' => $php_info,
            'plugins' => $plugin_info,
            'memberpress' => $memberpress_info,
            'mpai' => $mpai_info
        ],
        'tests' => $sub_tests,
        'timing' => [
            'start' => $start_time,
            'end' => $end_time,
            'total' => $end_time - $start_time
        ]
    ];
}

// Register the test if called via MPAI_Diagnostics
if (class_exists('MPAI_Diagnostics')) {
    MPAI_Diagnostics::register_test([
        'id' => 'system-info',
        'category' => 'core',
        'name' => __('System Information', 'memberpress-ai-assistant'),
        'description' => __('Get detailed information about your WordPress and PHP environment', 'memberpress-ai-assistant'),
        'test_callback' => 'mpai_run_system_info_test',
        'doc_url' => 'system-information.md'
    ]);
}