<?php
/**
 * MemberPress AI Assistant - Logging Functions
 *
 * Global functions for working with the unified logging system
 *
 * @package MemberPress AI Assistant
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Get the logger manager instance
 *
 * @return MPAI_Logger_Manager
 */
function mpai_logger_manager() {
    return MPAI_Logger_Manager::get_instance();
}

/**
 * Get a logger by key
 *
 * @param string $key Logger key.
 * @return MPAI_Logger|null
 */
function mpai_get_logger( $key = null ) {
    return mpai_logger_manager()->get_logger( $key );
}

/**
 * Log an emergency message
 *
 * @param string $message The log message.
 * @param array  $context Additional context data.
 * @param string $logger  Optional logger key to use.
 * @return void
 */
function mpai_log_emergency( $message, $context = [], $logger = null ) {
    // Handle case where parameters are passed in the wrong order
    // (logger as second parameter and context as third)
    if (is_string($context) && (is_array($logger) || is_null($logger))) {
        $temp = $context;
        $context = $logger ?: []; // Use logger as context, or empty array if null
        $logger = $temp;
    }
    
    // Ensure context is always an array
    if (!is_array($context)) {
        $context = [];
    }
    
    mpai_logger_manager()->emergency( $message, $context, $logger );
}

/**
 * Log an alert message
 *
 * @param string $message The log message.
 * @param array  $context Additional context data.
 * @param string $logger  Optional logger key to use.
 * @return void
 */
function mpai_log_alert( $message, $context = [], $logger = null ) {
    // Handle case where parameters are passed in the wrong order
    // (logger as second parameter and context as third)
    if (is_string($context) && (is_array($logger) || is_null($logger))) {
        $temp = $context;
        $context = $logger ?: []; // Use logger as context, or empty array if null
        $logger = $temp;
    }
    
    // Ensure context is always an array
    if (!is_array($context)) {
        $context = [];
    }
    
    mpai_logger_manager()->alert( $message, $context, $logger );
}

/**
 * Log a critical message
 *
 * @param string $message The log message.
 * @param array  $context Additional context data.
 * @param string $logger  Optional logger key to use.
 * @return void
 */
function mpai_log_critical( $message, $context = [], $logger = null ) {
    // Handle case where parameters are passed in the wrong order
    // (logger as second parameter and context as third)
    if (is_string($context) && (is_array($logger) || is_null($logger))) {
        $temp = $context;
        $context = $logger ?: []; // Use logger as context, or empty array if null
        $logger = $temp;
    }
    
    // Ensure context is always an array
    if (!is_array($context)) {
        $context = [];
    }
    
    mpai_logger_manager()->critical( $message, $context, $logger );
}

/**
 * Log an error message
 *
 * @param string $message The log message.
 * @param array  $context Additional context data.
 * @param string $logger  Optional logger key to use.
 * @return void
 */
function mpai_log_error( $message, $context = [], $logger = null ) {
    // Handle case where parameters are passed in the wrong order
    // (logger as second parameter and context as third)
    if (is_string($context) && (is_array($logger) || is_null($logger))) {
        $temp = $context;
        $context = $logger ?: []; // Use logger as context, or empty array if null
        $logger = $temp;
    }
    
    // Ensure context is always an array
    if (!is_array($context)) {
        $context = [];
    }
    
    mpai_logger_manager()->error( $message, $context, $logger );
}

/**
 * Log a warning message
 *
 * @param string $message The log message.
 * @param array  $context Additional context data.
 * @param string $logger  Optional logger key to use.
 * @return void
 */
function mpai_log_warning( $message, $context = [], $logger = null ) {
    // Handle case where parameters are passed in the wrong order
    // (logger as second parameter and context as third)
    if (is_string($context) && (is_array($logger) || is_null($logger))) {
        $temp = $context;
        $context = $logger ?: []; // Use logger as context, or empty array if null
        $logger = $temp;
    }
    
    // Ensure context is always an array
    if (!is_array($context)) {
        $context = [];
    }
    
    mpai_logger_manager()->warning( $message, $context, $logger );
}

/**
 * Log a notice message
 *
 * @param string $message The log message.
 * @param array  $context Additional context data.
 * @param string $logger  Optional logger key to use.
 * @return void
 */
function mpai_log_notice( $message, $context = [], $logger = null ) {
    // Handle case where parameters are passed in the wrong order
    // (logger as second parameter and context as third)
    if (is_string($context) && (is_array($logger) || is_null($logger))) {
        $temp = $context;
        $context = $logger ?: []; // Use logger as context, or empty array if null
        $logger = $temp;
    }
    
    // Ensure context is always an array
    if (!is_array($context)) {
        $context = [];
    }
    
    mpai_logger_manager()->notice( $message, $context, $logger );
}

/**
 * Log an informational message
 *
 * @param string $message The log message.
 * @param array  $context Additional context data.
 * @param string $logger  Optional logger key to use.
 * @return void
 */
function mpai_log_info( $message, $context = [], $logger = null ) {
    // Handle case where parameters are passed in the wrong order
    // (logger as second parameter and context as third)
    if (is_string($context) && (is_array($logger) || is_null($logger))) {
        $temp = $context;
        $context = $logger ?: []; // Use logger as context, or empty array if null
        $logger = $temp;
    }
    
    // Ensure context is always an array
    if (!is_array($context)) {
        $context = [];
    }
    
    mpai_logger_manager()->info( $message, $context, $logger );
}

/**
 * Log a debug message
 *
 * @param string $message The log message.
 * @param array  $context Additional context data.
 * @param string $logger  Optional logger key to use.
 * @return void
 */
function mpai_log_debug( $message, $context = [], $logger = null ) {
    // Handle case where parameters are passed in the wrong order
    // (logger as second parameter and context as third)
    if (is_string($context) && (is_array($logger) || is_null($logger))) {
        $temp = $context;
        $context = $logger ?: []; // Use logger as context, or empty array if null
        $logger = $temp;
    }
    
    // Ensure context is always an array
    if (!is_array($context)) {
        $context = [];
    }
    
    mpai_logger_manager()->debug( $message, $context, $logger );
}

/**
 * Log a message at a specific level
 *
 * @param string $level   Log level.
 * @param string $message The log message.
 * @param array  $context Additional context data.
 * @param string $logger  Optional logger key to use.
 * @return void
 */
function mpai_log( $level, $message, $context = [], $logger = null ) {
    // Handle case where parameters are passed in the wrong order
    // (logger as second parameter and context as third)
    if (is_string($context) && (is_array($logger) || is_null($logger))) {
        $temp = $context;
        $context = $logger ?: []; // Use logger as context, or empty array if null
        $logger = $temp;
    }
    
    // Ensure context is always an array
    if (!is_array($context)) {
        $context = [];
    }
    
    mpai_logger_manager()->log( $level, $message, $context, $logger );
}

/**
 * Add a settings section for logging - using the legacy settings
 *
 * @return void
 */
function mpai_register_logging_settings() {
    // Register settings on the admin_init hook
    add_action('admin_init', 'mpai_register_logging_settings_init');
}

/**
 * Register logging settings
 *
 * @return void
 */
function mpai_register_logging_settings_init() {
    // Register individual settings
    
    // Log level setting
    register_setting(
        'mpai_logging_settings',
        'mpai_log_level',
        array(
            'type'              => 'string',
            'description'       => __( 'Minimum log level', 'memberpress-ai-assistant' ),
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'info',
        )
    );
    
    // Database logging setting
    register_setting(
        'mpai_logging_settings',
        'mpai_use_db_logger',
        array(
            'type'              => 'boolean',
            'description'       => __( 'Enable database logging', 'memberpress-ai-assistant' ),
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default'           => false,
        )
    );
    
    // File logging setting
    register_setting(
        'mpai_logging_settings',
        'mpai_use_file_logger',
        array(
            'type'              => 'boolean',
            'description'       => __( 'Enable file logging', 'memberpress-ai-assistant' ),
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default'           => false,
        )
    );
    
    // PHP Error logging setting
    register_setting(
        'mpai_logging_settings',
        'mpai_log_php_errors',
        array(
            'type'              => 'boolean',
            'description'       => __( 'Capture PHP errors', 'memberpress-ai-assistant' ),
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default'           => false,
        )
    );
    
    // Log retention setting
    register_setting(
        'mpai_logging_settings',
        'mpai_log_retention_days',
        array(
            'type'              => 'integer',
            'description'       => __( 'Days to keep logs', 'memberpress-ai-assistant' ),
            'sanitize_callback' => 'absint',
            'default'           => 30,
        )
    );
}

/**
 * Callback for the log level field
 *
 * @return void
 */
function mpai_log_level_field_callback() {
    $log_level = get_option( 'mpai_log_level', 'info' );
    
    $levels = [
        'emergency' => __( 'Emergency', 'memberpress-ai-assistant' ),
        'alert'     => __( 'Alert', 'memberpress-ai-assistant' ),
        'critical'  => __( 'Critical', 'memberpress-ai-assistant' ),
        'error'     => __( 'Error', 'memberpress-ai-assistant' ),
        'warning'   => __( 'Warning', 'memberpress-ai-assistant' ),
        'notice'    => __( 'Notice', 'memberpress-ai-assistant' ),
        'info'      => __( 'Info', 'memberpress-ai-assistant' ),
        'debug'     => __( 'Debug', 'memberpress-ai-assistant' ),
    ];
    
    echo '<select name="mpai_log_level" id="mpai_log_level">';
    
    foreach ( $levels as $level => $label ) {
        echo '<option value="' . esc_attr( $level ) . '" ' . selected( $log_level, $level, false ) . '>' . esc_html( $label ) . '</option>';
    }
    
    echo '</select>';
    echo '<p class="description">' . esc_html__( 'Messages at or above this level will be logged.', 'memberpress-ai-assistant' ) . '</p>';
}

/**
 * Callback for the database logger field
 *
 * @return void
 */
function mpai_use_db_logger_field_callback() {
    $use_db_logger = get_option( 'mpai_use_db_logger', false );
    
    echo '<label for="mpai_use_db_logger">';
    echo '<input type="checkbox" name="mpai_use_db_logger" id="mpai_use_db_logger" value="1" ' . checked( $use_db_logger, true, false ) . '>';
    echo esc_html__( 'Store logs in the database', 'memberpress-ai-assistant' );
    echo '</label>';
    echo '<p class="description">' . esc_html__( 'Enables storing logs in a dedicated database table.', 'memberpress-ai-assistant' ) . '</p>';
}

/**
 * Callback for the file logger field
 *
 * @return void
 */
function mpai_use_file_logger_field_callback() {
    $use_file_logger = get_option( 'mpai_use_file_logger', false );
    
    echo '<label for="mpai_use_file_logger">';
    echo '<input type="checkbox" name="mpai_use_file_logger" id="mpai_use_file_logger" value="1" ' . checked( $use_file_logger, true, false ) . '>';
    echo esc_html__( 'Write logs to a file', 'memberpress-ai-assistant' );
    echo '</label>';
    
    $log_dir = WP_CONTENT_DIR . '/mpai-logs';
    $log_file = $log_dir . '/mpai.log';
    
    echo '<p class="description">' . sprintf(
        /* translators: %s: log file path */
        esc_html__( 'Logs will be written to %s', 'memberpress-ai-assistant' ),
        '<code>' . esc_html( $log_file ) . '</code>'
    ) . '</p>';
}

/**
 * Callback for the PHP error logging field
 *
 * @return void
 */
function mpai_log_php_errors_field_callback() {
    $log_php_errors = get_option( 'mpai_log_php_errors', false );
    
    echo '<label for="mpai_log_php_errors">';
    echo '<input type="checkbox" name="mpai_log_php_errors" id="mpai_log_php_errors" value="1" ' . checked( $log_php_errors, true, false ) . '>';
    echo esc_html__( 'Capture PHP errors in logs', 'memberpress-ai-assistant' );
    echo '</label>';
    echo '<p class="description">' . esc_html__( 'Logs PHP errors from plugin files. This is helpful for debugging.', 'memberpress-ai-assistant' ) . '</p>';
}

/**
 * Callback for the log retention field
 *
 * @return void
 */
function mpai_log_retention_days_field_callback() {
    $retention_days = get_option( 'mpai_log_retention_days', 30 );
    
    echo '<input type="number" name="mpai_log_retention_days" id="mpai_log_retention_days" value="' . esc_attr( $retention_days ) . '" min="1" max="365" step="1">';
    echo '<p class="description">' . esc_html__( 'Number of days to keep logs before automatic cleanup. Set to 0 to keep logs indefinitely.', 'memberpress-ai-assistant' ) . '</p>';
}

/**
 * Returns a formatted backtrace
 *
 * @param int  $limit      Maximum number of stack frames to return.
 * @param bool $get_args   Whether to include function arguments.
 * @param bool $skip_first Whether to skip the first frame (the calling function).
 * @return array Formatted backtrace.
 */
function mpai_get_backtrace( $limit = 5, $get_args = false, $skip_first = true ) {
    $backtrace = debug_backtrace( $get_args ? 0 : DEBUG_BACKTRACE_IGNORE_ARGS, $limit + 1 );
    
    // Skip the first entry (this function)
    if ( $skip_first ) {
        array_shift( $backtrace );
    }
    
    // Limit the backtrace
    $backtrace = array_slice( $backtrace, 0, $limit );
    
    // Format the backtrace
    $formatted = array();
    
    foreach ( $backtrace as $trace ) {
        $entry = [
            'function' => isset( $trace['class'] ) ? $trace['class'] . $trace['type'] . $trace['function'] : $trace['function'],
            'file'     => isset( $trace['file'] ) ? $trace['file'] : 'unknown',
            'line'     => isset( $trace['line'] ) ? $trace['line'] : 0,
        ];
        
        if ( $get_args && isset( $trace['args'] ) ) {
            // Format arguments safely
            $args = [];
            
            foreach ( $trace['args'] as $arg ) {
                if ( is_object( $arg ) ) {
                    $args[] = get_class( $arg );
                } elseif ( is_array( $arg ) ) {
                    $args[] = 'Array(' . count( $arg ) . ')';
                } elseif ( is_string( $arg ) && strlen( $arg ) > 50 ) {
                    $args[] = "'" . substr( $arg, 0, 50 ) . "...'";
                } else {
                    $args[] = var_export( $arg, true );
                }
            }
            
            $entry['args'] = $args;
        }
        
        $formatted[] = $entry;
    }
    
    return $formatted;
}

/**
 * Add a "View Logs" link to the plugin action links
 *
 * @param array  $links      Plugin action links.
 * @param string $plugin_file Plugin file path.
 * @return array Modified plugin action links.
 */
function mpai_add_logs_action_link( $links, $plugin_file ) {
    if ( MPAI_PLUGIN_BASENAME === $plugin_file ) {
        $logs_url = admin_url( 'admin.php?page=mpai-settings&tab=logging' );
        $logs_link = sprintf( '<a href="%s">%s</a>', esc_url( $logs_url ), esc_html__( 'View Logs', 'memberpress-ai-assistant' ) );
        
        $links[] = $logs_link;
    }
    
    return $links;
}

/**
 * Register the logs table in the WP REST API
 *
 * @return void
 */
function mpai_register_logs_rest_routes() {
    register_rest_route(
        'mpai/v1',
        '/logs',
        [
            'methods'             => 'GET',
            'callback'            => 'mpai_rest_get_logs',
            'permission_callback' => function() {
                return current_user_can( 'manage_options' );
            },
            'args'                => array(
                'level'       => array(
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'component'   => array(
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'date_from'   => array(
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'date_to'     => array(
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'user_id'     => array(
                    'type'              => 'integer',
                    'sanitize_callback' => 'absint',
                ),
                'search'      => array(
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'limit'       => array(
                    'type'              => 'integer',
                    'sanitize_callback' => 'absint',
                    'default'           => 100,
                ),
                'offset'      => array(
                    'type'              => 'integer',
                    'sanitize_callback' => 'absint',
                    'default'           => 0,
                ),
                'orderby'     => array(
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'default'           => 'timestamp',
                ),
                'order'       => array(
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'default'           => 'DESC',
                ),
            ),
        ]
    );
    
    register_rest_route(
        'mpai/v1',
        '/logs/count',
        [
            'methods'             => 'GET',
            'callback'            => 'mpai_rest_count_logs',
            'permission_callback' => function() {
                return current_user_can( 'manage_options' );
            },
            'args'                => array(
                'level'       => array(
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'component'   => array(
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'date_from'   => array(
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'date_to'     => array(
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'user_id'     => array(
                    'type'              => 'integer',
                    'sanitize_callback' => 'absint',
                ),
                'search'      => array(
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ]
    );
    
    register_rest_route(
        'mpai/v1',
        '/logs/cleanup',
        [
            'methods'             => 'POST',
            'callback'            => 'mpai_rest_cleanup_logs',
            'permission_callback' => function() {
                return current_user_can( 'manage_options' );
            },
            'args'                => [
                'days' => [
                    'type'              => 'integer',
                    'sanitize_callback' => 'absint',
                    'default'           => 30,
                ],
            ],
        ]
    );
}

/**
 * REST API callback for getting logs
 *
 * @param WP_REST_Request $request REST API request.
 * @return WP_REST_Response REST API response.
 */
function mpai_rest_get_logs( $request ) {
    $logger = mpai_get_logger( 'database' );
    
    if ( ! $logger || ! method_exists( $logger, 'get_logs' ) ) {
        return new WP_REST_Response(
            [
                'success' => false,
                'message' => __( 'Database logger is not enabled', 'memberpress-ai-assistant' ),
            ],
            404
        );
    }
    
    $args = [
        'level'       => $request->get_param( 'level' ),
        'component'   => $request->get_param( 'component' ),
        'date_from'   => $request->get_param( 'date_from' ),
        'date_to'     => $request->get_param( 'date_to' ),
        'user_id'     => $request->get_param( 'user_id' ),
        'search'      => $request->get_param( 'search' ),
        'limit'       => $request->get_param( 'limit' ),
        'offset'      => $request->get_param( 'offset' ),
        'orderby'     => $request->get_param( 'orderby' ),
        'order'       => $request->get_param( 'order' ),
    ];
    
    $logs = $logger->get_logs( $args );
    
    return new WP_REST_Response(
        [
            'success' => true,
            'logs'    => $logs,
        ],
        200
    );
}

/**
 * REST API callback for counting logs
 *
 * @param WP_REST_Request $request REST API request.
 * @return WP_REST_Response REST API response.
 */
function mpai_rest_count_logs( $request ) {
    $logger = mpai_get_logger( 'database' );
    
    if ( ! $logger || ! method_exists( $logger, 'count_logs' ) ) {
        return new WP_REST_Response(
            [
                'success' => false,
                'message' => __( 'Database logger is not enabled', 'memberpress-ai-assistant' ),
            ],
            404
        );
    }
    
    $args = [
        'level'       => $request->get_param( 'level' ),
        'component'   => $request->get_param( 'component' ),
        'date_from'   => $request->get_param( 'date_from' ),
        'date_to'     => $request->get_param( 'date_to' ),
        'user_id'     => $request->get_param( 'user_id' ),
        'search'      => $request->get_param( 'search' ),
    ];
    
    $count = $logger->count_logs( $args );
    
    return new WP_REST_Response(
        [
            'success' => true,
            'count'   => $count,
        ],
        200
    );
}

/**
 * REST API callback for cleaning up logs
 *
 * @param WP_REST_Request $request REST API request.
 * @return WP_REST_Response REST API response.
 */
function mpai_rest_cleanup_logs( $request ) {
    $logger = mpai_get_logger( 'database' );
    
    if ( ! $logger || ! method_exists( $logger, 'delete_old_logs' ) ) {
        return new WP_REST_Response(
            [
                'success' => false,
                'message' => __( 'Database logger is not enabled', 'memberpress-ai-assistant' ),
            ],
            404
        );
    }
    
    $days = $request->get_param( 'days' );
    $deleted = $logger->delete_old_logs( $days );
    
    return new WP_REST_Response(
        [
            'success' => true,
            'deleted' => $deleted,
        ],
        200
    );
}

// Add hooks for plugin action links and REST API routes
add_filter( 'plugin_action_links', 'mpai_add_logs_action_link', 10, 2 );
add_action( 'rest_api_init', 'mpai_register_logs_rest_routes' );