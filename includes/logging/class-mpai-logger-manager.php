<?php
/**
 * MemberPress AI Assistant - Logger Manager
 *
 * Centralizes and manages all logging in the plugin
 *
 * @package MemberPress AI Assistant
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Manages all loggers and provides a unified logging API
 */
class MPAI_Logger_Manager {

    /**
     * Singleton instance
     *
     * @var MPAI_Logger_Manager
     */
    private static $instance = null;

    /**
     * Map of logger instances by key
     *
     * @var array
     */
    private $loggers = array();

    /**
     * Default logger key
     *
     * @var string
     */
    private $default_logger = 'default';

    /**
     * Minimum log level from settings
     *
     * @var string
     */
    private $minimum_level = 'info';

    /**
     * Available log levels
     *
     * @var array
     */
    protected $levels = array(
        'emergency' => 0,
        'alert'     => 1,
        'critical'  => 2,
        'error'     => 3,
        'warning'   => 4,
        'notice'    => 5,
        'info'      => 6,
        'debug'     => 7,
    );

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        // Initialize the logging system
        $this->init();
    }

    /**
     * Initialize the logging system
     *
     * @return void
     */
    private function init() {
        // Load minimum log level from settings
        $this->load_settings();
        
        // Register default loggers
        $this->register_default_loggers();
        
        // Setup runtime logging
        $this->setup_runtime_logging();
        
        // Register shutdown function to capture fatal errors
        register_shutdown_function( array( $this, 'handle_shutdown' ) );
    }

    /**
     * Load logging settings
     *
     * @return void
     */
    private function load_settings() {
        // Direct option retrieval
        $this->minimum_level = get_option( 'mpai_log_level', 'info' );
        
        // Validate level
        if ( ! array_key_exists( $this->minimum_level, $this->levels ) ) {
            $this->minimum_level = 'info';
        }
    }

    /**
     * Register default loggers
     *
     * @return void
     */
    private function register_default_loggers() {
        // Register error_log logger as the default
        $error_logger = new MPAI_Error_Log_Logger( $this->minimum_level );
        $this->register_logger( 'error_log', $error_logger );
        $this->set_default_logger( 'error_log' );
        
        // Register a DB logger if enabled
        $use_db_logger = get_option( 'mpai_use_db_logger', false );
        if ( $use_db_logger ) {
            $db_logger = new MPAI_DB_Logger( 'core', $this->minimum_level );
            $this->register_logger( 'database', $db_logger );
        }
        
        // Register a file logger if enabled
        $use_file_logger = get_option( 'mpai_use_file_logger', false );
        if ( $use_file_logger ) {
            $log_dir = WP_CONTENT_DIR . '/mpai-logs';
            if ( ! file_exists( $log_dir ) ) {
                wp_mkdir_p( $log_dir );
            }
            
            $log_file = $log_dir . '/mpai.log';
            $file_logger = new MPAI_File_Logger( $log_file, $this->minimum_level );
            $this->register_logger( 'file', $file_logger );
        }
        
        // Create a multi-logger if we have more than one logger
        if ( count( $this->loggers ) > 1 ) {
            $multi_logger = new MPAI_Multi_Logger( array_values( $this->loggers ) );
            $this->register_logger( 'multi', $multi_logger );
            $this->set_default_logger( 'multi' );
        }
    }

    /**
     * Setup runtime error logging
     *
     * @return void
     */
    private function setup_runtime_logging() {
        // Set error handler to capture PHP errors if enabled
        $log_php_errors = get_option( 'mpai_log_php_errors', false );
        if ( $log_php_errors ) {
            set_error_handler( array( $this, 'handle_error' ) );
        }
    }

    /**
     * Get the singleton instance
     *
     * @return MPAI_Logger_Manager
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }

    /**
     * Register a logger
     *
     * @param string      $key    Unique identifier for the logger.
     * @param MPAI_Logger $logger Logger instance.
     * @return void
     */
    public function register_logger( $key, MPAI_Logger $logger ) {
        $this->loggers[ $key ] = $logger;
    }

    /**
     * Unregister a logger
     *
     * @param string $key Unique identifier for the logger.
     * @return void
     */
    public function unregister_logger( $key ) {
        if ( isset( $this->loggers[ $key ] ) ) {
            unset( $this->loggers[ $key ] );
        }
        
        // If we removed the default logger, set a new default
        if ( $key === $this->default_logger && ! empty( $this->loggers ) ) {
            $this->default_logger = key( $this->loggers );
        }
    }

    /**
     * Set the default logger
     *
     * @param string $key Unique identifier for the logger.
     * @return bool Whether the default logger was set.
     */
    public function set_default_logger( $key ) {
        if ( isset( $this->loggers[ $key ] ) ) {
            $this->default_logger = $key;
            return true;
        }
        
        return false;
    }

    /**
     * Get a logger by key
     *
     * @param string $key Unique identifier for the logger.
     * @return MPAI_Logger|null Logger instance or null if not found.
     */
    public function get_logger( $key = null ) {
        if ( null === $key ) {
            $key = $this->default_logger;
        }
        
        return isset( $this->loggers[ $key ] ) ? $this->loggers[ $key ] : null;
    }

    /**
     * Check if a level should be logged
     *
     * @param string $level Log level to check.
     * @return bool Whether the level should be logged.
     */
    public function should_log( $level ) {
        return $this->levels[ $level ] <= $this->levels[ $this->minimum_level ];
    }

    /**
     * Log an emergency message
     *
     * @param string $message The log message.
     * @param array  $context Additional context data.
     * @param string $logger  Optional logger key to use.
     * @return void
     */
    public function emergency( $message, array $context = array(), $logger = null ) {
        $this->log( 'emergency', $message, $context, $logger );
    }

    /**
     * Log an alert message
     *
     * @param string $message The log message.
     * @param array  $context Additional context data.
     * @param string $logger  Optional logger key to use.
     * @return void
     */
    public function alert( $message, array $context = array(), $logger = null ) {
        $this->log( 'alert', $message, $context, $logger );
    }

    /**
     * Log a critical message
     *
     * @param string $message The log message.
     * @param array  $context Additional context data.
     * @param string $logger  Optional logger key to use.
     * @return void
     */
    public function critical( $message, array $context = array(), $logger = null ) {
        $this->log( 'critical', $message, $context, $logger );
    }

    /**
     * Log an error message
     *
     * @param string $message The log message.
     * @param array  $context Additional context data.
     * @param string $logger  Optional logger key to use.
     * @return void
     */
    public function error( $message, array $context = array(), $logger = null ) {
        $this->log( 'error', $message, $context, $logger );
    }

    /**
     * Log a warning message
     *
     * @param string $message The log message.
     * @param array  $context Additional context data.
     * @param string $logger  Optional logger key to use.
     * @return void
     */
    public function warning( $message, array $context = array(), $logger = null ) {
        $this->log( 'warning', $message, $context, $logger );
    }

    /**
     * Log a notice message
     *
     * @param string $message The log message.
     * @param array  $context Additional context data.
     * @param string $logger  Optional logger key to use.
     * @return void
     */
    public function notice( $message, array $context = array(), $logger = null ) {
        $this->log( 'notice', $message, $context, $logger );
    }

    /**
     * Log an informational message
     *
     * @param string $message The log message.
     * @param array  $context Additional context data.
     * @param string $logger  Optional logger key to use.
     * @return void
     */
    public function info( $message, array $context = array(), $logger = null ) {
        $this->log( 'info', $message, $context, $logger );
    }

    /**
     * Log a debug message
     *
     * @param string $message The log message.
     * @param array  $context Additional context data.
     * @param string $logger  Optional logger key to use.
     * @return void
     */
    public function debug( $message, array $context = array(), $logger = null ) {
        $this->log( 'debug', $message, $context, $logger );
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
    public function log( $level, $message, array $context = array(), $logger = null ) {
        if ( ! $this->should_log( $level ) ) {
            return;
        }
        
        $logger_instance = $this->get_logger( $logger );
        
        if ( $logger_instance ) {
            $logger_instance->log( $level, $message, $context );
        } else {
            // Fallback to error_log if no logger found
            $formatted_message = $this->format_message( $level, $message, $context );
            error_log( 'MPAI: ' . $formatted_message );
        }
    }

    /**
     * Format the log message
     *
     * @param string $level   Log level.
     * @param string $message Log message.
     * @param array  $context Additional context data.
     * @return string Formatted log message.
     */
    private function format_message( $level, $message, array $context = array() ) {
        $timestamp = current_time( 'mysql' );
        $formatted_context = $this->format_context( $context );
        
        $log_message = "[$timestamp][$level] $message";
        
        if ( ! empty( $formatted_context ) ) {
            $log_message .= " Context: $formatted_context";
        }
        
        return $log_message;
    }

    /**
     * Format context data for logging
     *
     * @param array $context Context data to format.
     * @return string Formatted context string.
     */
    private function format_context( array $context ) {
        if ( empty( $context ) ) {
            return '';
        }

        // Special handling for exceptions
        if ( isset( $context['exception'] ) && $context['exception'] instanceof \Exception ) {
            $exception = $context['exception'];
            $context['exception'] = array(
                'class'   => get_class( $exception ),
                'message' => $exception->getMessage(),
                'code'    => $exception->getCode(),
                'file'    => $exception->getFile(),
                'line'    => $exception->getLine(),
                'trace'   => $exception->getTraceAsString(),
            );
        }

        return wp_json_encode( $context );
    }

    /**
     * Handle PHP errors
     *
     * @param int    $errno   Error level.
     * @param string $errstr  Error message.
     * @param string $errfile File where the error occurred.
     * @param int    $errline Line where the error occurred.
     * @return bool Whether the error was handled.
     */
    public function handle_error( $errno, $errstr, $errfile, $errline ) {
        // Don't log if error reporting is disabled
        if ( ! ( error_reporting() & $errno ) ) {
            return false;
        }
        
        // Map PHP error constants to log levels
        $levels = array(
            E_ERROR             => 'error',
            E_WARNING           => 'warning',
            E_PARSE             => 'critical',
            E_NOTICE            => 'notice',
            E_CORE_ERROR        => 'critical',
            E_CORE_WARNING      => 'warning',
            E_COMPILE_ERROR     => 'critical',
            E_COMPILE_WARNING   => 'warning',
            E_USER_ERROR        => 'error',
            E_USER_WARNING      => 'warning',
            E_USER_NOTICE       => 'notice',
            E_STRICT            => 'notice',
            E_RECOVERABLE_ERROR => 'error',
            E_DEPRECATED        => 'notice',
            E_USER_DEPRECATED   => 'notice',
        );
        
        // Get the appropriate log level
        $level = isset( $levels[ $errno ] ) ? $levels[ $errno ] : 'error';
        
        // Only log errors from our plugin files
        $is_plugin_file = strpos( $errfile, 'memberpress-ai-assistant' ) !== false;
        
        if ( $is_plugin_file ) {
            $this->log(
                $level,
                $errstr,
                array(
                    'errno'    => $errno,
                    'errfile'  => $errfile,
                    'errline'  => $errline,
                    'backtrace' => $this->get_backtrace(),
                )
            );
        }
        
        // Always return false to let PHP handle the error too
        return false;
    }

    /**
     * Handle fatal errors on shutdown
     *
     * @return void
     */
    public function handle_shutdown() {
        $error = error_get_last();
        
        if ( $error && in_array( $error['type'], array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR ) ) ) {
            // Only log fatal errors from our plugin files
            $is_plugin_file = strpos( $error['file'], 'memberpress-ai-assistant' ) !== false;
            
            if ( $is_plugin_file ) {
                $this->critical(
                    $error['message'],
                    array(
                        'type'     => $error['type'],
                        'file'     => $error['file'],
                        'line'     => $error['line'],
                        'shutdown' => true,
                    )
                );
            }
        }
    }

    /**
     * Get a backtrace for debugging
     *
     * @param int $limit Maximum number of stack frames to return.
     * @return array Formatted backtrace.
     */
    private function get_backtrace( $limit = 10 ) {
        $backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, $limit + 3 );
        
        // Remove the first few entries which are the logger functions
        $backtrace = array_slice( $backtrace, 3 );
        
        // Format and limit the backtrace
        $formatted = array();
        $count = 0;
        
        foreach ( $backtrace as $trace ) {
            if ( $count >= $limit ) {
                break;
            }
            
            $formatted[] = array(
                'function' => isset( $trace['class'] ) ? $trace['class'] . $trace['type'] . $trace['function'] : $trace['function'],
                'file'     => isset( $trace['file'] ) ? $trace['file'] : 'unknown',
                'line'     => isset( $trace['line'] ) ? $trace['line'] : 0,
            );
            
            $count++;
        }
        
        return $formatted;
    }
}