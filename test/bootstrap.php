<?php
/**
 * PHPUnit bootstrap file
 *
 * @package MemberPress AI Assistant
 */

// Define constants needed for WordPress testing.
define('ABSPATH', true);
define('WPINC', true);
define('WP_PLUGIN_DIR', dirname(dirname(__FILE__)));

// Include necessary files for testing
require_once dirname(dirname(__FILE__)) . '/includes/agents/interfaces/interface-mpai-agent.php';
require_once dirname(dirname(__FILE__)) . '/includes/agents/class-mpai-base-agent.php';
require_once dirname(dirname(__FILE__)) . '/includes/agents/class-mpai-agent-orchestrator.php';
require_once dirname(dirname(__FILE__)) . '/includes/tools/class-mpai-tool-registry.php';
require_once dirname(dirname(__FILE__)) . '/includes/class-mpai-agent-message.php';
require_once dirname(dirname(__FILE__)) . '/includes/class-mpai-response-cache.php';
require_once dirname(dirname(__FILE__)) . '/includes/class-mpai-anthropic.php';

// Mock WordPress functions
if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file) {
        return dirname($file) . '/';
    }
}

if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        return $default;
    }
}

if (!function_exists('error_log')) {
    function error_log($message) {
        // Suppress error logs during testing
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters($tag, $value) {
        return $value;
    }
}

if (!function_exists('current_time')) {
    function current_time($type) {
        return date('Y-m-d H:i:s');
    }
}

if (!function_exists('wp_upload_dir')) {
    function wp_upload_dir() {
        return array(
            'basedir' => sys_get_temp_dir()
        );
    }
}

if (!function_exists('wp_mkdir_p')) {
    function wp_mkdir_p($dir) {
        return @mkdir($dir, 0777, true);
    }
}

// Create a basic WP_UnitTestCase class if it doesn't exist
if (!class_exists('WP_UnitTestCase')) {
    class WP_UnitTestCase extends PHPUnit\Framework\TestCase {
        // Basic functionality that would be in WP_UnitTestCase
    }
}

// Create a basic WP_Error class if it doesn't exist
if (!class_exists('WP_Error')) {
    class WP_Error {
        private $code;
        private $message;
        private $data;

        public function __construct($code = '', $message = '', $data = '') {
            $this->code = $code;
            $this->message = $message;
            $this->data = $data;
        }

        public function get_error_message() {
            return $this->message;
        }

        public function get_error_code() {
            return $this->code;
        }

        public function get_error_data() {
            return $this->data;
        }
    }
}

// Create a basic setup for PHPUnit
if (!class_exists('TestCase')) {
    class TestCase extends PHPUnit\Framework\TestCase {
        // Any common test functionality can go here
    }
}