<?php
/**
 * Settings Page Template
 * 
 * Simple wrapper to load the Settings Page class
 *
 * @package MemberPress AI Assistant
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Load the settings page class
if (!class_exists('MPAI_Settings_Page')) {
    require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-settings-page.php';
}

// Create settings page object
global $mpai_settings_page;
if (!isset($mpai_settings_page) || !is_object($mpai_settings_page)) {
    $mpai_settings_page = new MPAI_Settings_Page();
}

// Render the settings page
$mpai_settings_page->render_page();