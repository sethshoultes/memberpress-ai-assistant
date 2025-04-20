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

// Load the detection classes
require_once dirname( __FILE__ ) . '/class-mpai-memberpress-detector.php';
require_once dirname( __FILE__ ) . '/class-mpai-tool-call-detector.php';

// Initialize the detection system
function mpai_init_detection_system() {
    // Get the detector instances to ensure they're initialized
    mpai_memberpress_detector();
    mpai_tool_call_detector();
    
    // Log initialization
    mpai_log_debug('MemberPress detection system initialized', [
        'detection_info' => mpai_memberpress_detector()->get_detection_info(),
        'tool_call_detector' => 'Initialized'
    ]);
}

// Initialize the detection system
mpai_init_detection_system();