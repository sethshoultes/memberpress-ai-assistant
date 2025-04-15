<?php
/**
 * MemberPress AI Assistant - Logger Loader
 *
 * Loads and initializes the logging system
 *
 * @package MemberPress AI Assistant
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Load logger components
require_once dirname( __FILE__ ) . '/interface-mpai-logger.php';
require_once dirname( __FILE__ ) . '/class-mpai-abstract-logger.php';
require_once dirname( __FILE__ ) . '/class-mpai-error-log-logger.php';
require_once dirname( __FILE__ ) . '/class-mpai-file-logger.php';
require_once dirname( __FILE__ ) . '/class-mpai-db-logger.php';
require_once dirname( __FILE__ ) . '/class-mpai-null-logger.php';
require_once dirname( __FILE__ ) . '/class-mpai-multi-logger.php';
require_once dirname( __FILE__ ) . '/class-mpai-logger-manager.php';
require_once dirname( __FILE__ ) . '/mpai-logging-functions.php';
require_once dirname( __FILE__ ) . '/replace-error-log.php';

// Initialize the logger manager (singleton)
function mpai_init_logger() {
    return MPAI_Logger_Manager::get_instance();
}

// Initialize the logging system
mpai_init_logger();

// Register logging settings when the unified settings manager is available
add_action( 'mpai_after_settings_manager_init', 'mpai_register_logging_settings' );

// Add logging cleanup hook
add_action( 'mpai_daily_maintenance', function() {
    // Get retention days from settings
    $retention_days = get_option( 'mpai_log_retention_days', 30 );
    
    // Clean up database logs if available
    $db_logger = mpai_get_logger( 'database' );
    if ( $db_logger && method_exists( $db_logger, 'delete_old_logs' ) ) {
        $db_logger->delete_old_logs( $retention_days );
    }
    
    // Log maintenance event
    mpai_log_info( 'Performed log maintenance with retention period: ' . $retention_days . ' days' );
} );