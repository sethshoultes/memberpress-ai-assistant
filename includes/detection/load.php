<?php
/**
 * MemberPress AI Assistant - Detection System Loader
 *
 * Loads the unified detection system components
 *
 * @package MemberPress AI Assistant
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Load the MemberPress detector
require_once dirname( __FILE__ ) . '/class-mpai-memberpress-detector.php';

// Initialize the detection system
function mpai_init_detection_system() {
    // Get the detector instance to ensure it's initialized
    mpai_memberpress_detector();
    
    // Log initialization
    mpai_log_debug('MemberPress detection system initialized', [
        'detection_info' => mpai_memberpress_detector()->get_detection_info()
    ]);
}

// Initialize the detection system
mpai_init_detection_system();