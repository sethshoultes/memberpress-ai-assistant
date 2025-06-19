<?php
/**
 * Bundle Registration Helper
 * WordPress script registration for generated bundles
 * 
 * @generated 2025-06-19 18:31:10
 * @package MemberpressAiAssistant
 */

/**
 * Register all generated bundles with WordPress
 * 
 * Add this code to your ChatInterface::registerAssets() method
 */

// Bundle registration code
$bundle_scripts = [
    'mpai-core-bundle' => 'assets/js/bundles/core-bundle.js',
    'mpai-ui-bundle' => 'assets/js/bundles/ui-bundle.js',
    'mpai-messaging-bundle' => 'assets/js/bundles/messaging-bundle.js',
    'mpai-message-handlers-bundle' => 'assets/js/bundles/message-handlers-bundle.js',
];

foreach ($bundle_scripts as $handle => $path) {
    wp_register_script(
        $handle,
        MPAI_PLUGIN_URL . $path,
        [], // Dependencies handled by ES6 imports
        MPAI_VERSION,
        true
    );
    wp_script_add_data($handle, 'type', 'module');
}

// Enqueue core bundles (non-lazy)
wp_enqueue_script('mpai-core-bundle');
wp_enqueue_script('mpai-messaging-bundle');
