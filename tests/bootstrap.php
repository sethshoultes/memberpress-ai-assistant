<?php
/**
 * PHPUnit bootstrap file
 *
 * @package MemberpressAiAssistant
 */

// Load Composer autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Define constants needed for testing
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__) . '/');
}

if (!defined('MPAI_PLUGIN_DIR')) {
    define('MPAI_PLUGIN_DIR', dirname(__DIR__) . '/');
}

if (!defined('MPAI_PLUGIN_URL')) {
    define('MPAI_PLUGIN_URL', 'http://example.org/wp-content/plugins/memberpress-ai-assistant/');
}

// Create a base TestCase class that all test cases will extend
require_once __DIR__ . '/TestCase.php';

// Load mock factory for creating test fixtures
require_once __DIR__ . '/Fixtures/MockFactory.php';