<?php
/**
 * Logging Utility
 *
 * Provides centralized logging functionality with configurable log levels.
 *
 * @package MemberpressAiAssistant\Utilities
 */

namespace MemberpressAiAssistant\Utilities;

/**
 * Class LoggingUtility
 *
 * Handles logging with different severity levels and configurable verbosity.
 */
class LoggingUtility {
    /**
     * Log levels
     */
    const LEVEL_NONE = 'none';     // No logging at all
    const LEVEL_ERROR = 'error';   // Only errors
    const LEVEL_WARNING = 'warning';
    const LEVEL_INFO = 'info';
    const LEVEL_DEBUG = 'debug';
    const LEVEL_TRACE = 'trace';
    
    /**
     * Current log level
     *
     * @var string
     */
    private static $logLevel = self::LEVEL_NONE; // Default to NONE to prevent any logging until settings are loaded
    
    /**
     * Flag to indicate whether debug logs should be suppressed
     * This is used to prevent debug logs from being written when log level is NONE
     *
     * @var bool
     */
    public static $suppressDebugLogs = false;
    
    /**
     * Whether detailed logging is enabled
     *
     * @var bool
     */
    private static $detailedLogging = false;
    
    /**
     * Log level priorities (higher number = more verbose)
     *
     * @var array
     */
    private static $levelPriorities = [
        self::LEVEL_NONE => 0,     // Priority 0 - no logging at all
        self::LEVEL_ERROR => 1,    // Priority 1 - only errors
        self::LEVEL_WARNING => 2,  // Priority 2 - warnings and errors
        self::LEVEL_INFO => 3,     // Priority 3 - info, warnings, and errors
        self::LEVEL_DEBUG => 4,    // Priority 4 - debug, info, warnings, and errors
        self::LEVEL_TRACE => 5     // Priority 5 - all messages
    ];
    
    /**
     * Store logged messages to prevent duplicates
     * This is a static array that persists for the duration of the request
     *
     * @var array
     */
    private static $loggedMessages = [];
    
    /**
     * Flag to indicate whether the static variables have been initialized
     * This is used to prevent multiple initializations
     *
     * @var bool
     */
    private static $initialized = false;
    
    /**
     * Request ID to uniquely identify this request
     *
     * @var string
     */
    private static $requestId = '';
    
    /**
     * Initialize the logging utility
     *
     * @param string $logLevel The log level to use
     * @param bool $detailedLogging Whether to enable detailed logging
     * @return void
     */
    public static function init($logLevel = self::LEVEL_INFO, $detailedLogging = false) {
        // Check if already initialized
        if (self::$initialized) {
            // Only update the log level if it's different
            if (self::$logLevel !== $logLevel) {
                self::$logLevel = $logLevel;
                self::$suppressDebugLogs = ($logLevel === self::LEVEL_NONE);
            }
            return;
        }
        
        // Set the log level from the parameter - don't let WP_DEBUG override it
        self::$logLevel = $logLevel;
        self::$detailedLogging = $detailedLogging;
        
        // Set the suppressDebugLogs flag when log level is NONE
        self::$suppressDebugLogs = ($logLevel === self::LEVEL_NONE);
        
        // Generate a unique request ID if not already set
        if (empty(self::$requestId)) {
            self::$requestId = uniqid('mpai_', true);
        }
        
        // Reset logged messages for this request
        self::$loggedMessages = [];
        
        // Mark as initialized
        self::$initialized = true;
    }
    
    /**
     * Get the current log level
     *
     * @return string The current log level
     */
    public static function getLogLevel() {
        return self::$logLevel;
    }
    
    /**
     * Log a message if the current log level allows it
     *
     * @param string $message The message to log
     * @param string $level The log level of this message
     * @param array $context Additional context data
     * @return void
     */
    public static function log($message, $level = self::LEVEL_INFO, $context = []) {
        // Check if this message should be logged based on current log level
        if (!self::shouldLog($level)) {
            return;
        }
        
        // Special handling for initialization logs
        if (strpos($message, 'Plugin initialized with log level') !== false) {
            // Use WordPress transient to track if this message has been logged recently
            $transient_key = 'mpai_init_log_' . md5($message);
            if (get_transient($transient_key)) {
                // This message was logged recently, skip it
                return;
            }
            
            // Set a transient to prevent duplicate logs for 60 seconds
            set_transient($transient_key, true, 60);
        } else {
            // For other logs, use the in-memory array to prevent duplicates in the same request
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
            $caller = isset($backtrace[2]) ? $backtrace[2]['file'] . ':' . $backtrace[2]['line'] : '';
            $messageKey = md5($level . $message . $caller . json_encode($context));
            
            // Skip if this exact message has already been logged in this request
            if (isset(self::$loggedMessages[$messageKey])) {
                return;
            }
            
            // Mark this message as logged
            self::$loggedMessages[$messageKey] = true;
        }
        
        // Format the message
        $formattedMessage = self::formatMessage($message, $level, $context);
        
        // Log using WordPress error_log function
        if (function_exists('error_log')) {
            error_log($formattedMessage);
        }
    }
    
    /**
     * Log an error message
     *
     * @param string $message The message to log
     * @param array $context Additional context data
     * @return void
     */
    public static function error($message, $context = []) {
        self::log($message, self::LEVEL_ERROR, $context);
    }
    
    /**
     * Log a warning message
     *
     * @param string $message The message to log
     * @param array $context Additional context data
     * @return void
     */
    public static function warning($message, $context = []) {
        self::log($message, self::LEVEL_WARNING, $context);
    }
    
    /**
     * Log an info message
     *
     * @param string $message The message to log
     * @param array $context Additional context data
     * @return void
     */
    public static function info($message, $context = []) {
        self::log($message, self::LEVEL_INFO, $context);
    }
    
    /**
     * Log a debug message
     *
     * @param string $message The message to log
     * @param array $context Additional context data
     * @return void
     */
    public static function debug($message, $context = []) {
        self::log($message, self::LEVEL_DEBUG, $context);
    }
    
    /**
     * Log a trace message (most verbose)
     *
     * @param string $message The message to log
     * @param array $context Additional context data
     * @return void
     */
    public static function trace($message, $context = []) {
        self::log($message, self::LEVEL_TRACE, $context);
    }
    
    /**
     * Check if debug logs should be suppressed
     * This is used by the debug_log function to determine if a debug log should be written
     *
     * @return bool Whether debug logs should be suppressed
     */
    public static function shouldSuppressDebugLogs() {
        return self::$logLevel === self::LEVEL_NONE;
    }
    
    /**
     * Check if a message with the given level should be logged
     *
     * @param string $level The log level to check
     * @return bool Whether the message should be logged
     */
    private static function shouldLog($level) {
        // Special case: if log level is NONE, don't log anything
        if (self::$logLevel === self::LEVEL_NONE) {
            return false;
        }
        
        // Get the priority of the current log level
        $currentPriority = self::$levelPriorities[self::$logLevel] ?? 3; // Default to INFO
        
        // Get the priority of the message level
        $messagePriority = self::$levelPriorities[$level] ?? 3;
        
        // Only log if the message priority is less than or equal to the current log level priority
        // For example, if log level is WARNING (2), only log ERROR (1) and WARNING (2) messages
        return $messagePriority <= $currentPriority;
    }
    
    /**
     * Format a log message
     *
     * @param string $message The message to format
     * @param string $level The log level
     * @param array $context Additional context data
     * @return string The formatted message
     */
    private static function formatMessage($message, $level, $context = []) {
        $prefix = 'MPAI';
        
        // Add level to prefix for non-info levels
        if ($level !== self::LEVEL_INFO) {
            $prefix .= ' ' . strtoupper($level);
        }
        
        // Add request ID to help identify related log entries
        $requestIdSuffix = !empty(self::$requestId) ? ' [Request-ID: ' . substr(self::$requestId, 0, 8) . ']' : '';
        
        // Format the basic message
        $formattedMessage = "{$prefix}{$requestIdSuffix}: {$message}";
        
        // Add context data if detailed logging is enabled
        if (self::$detailedLogging && !empty($context)) {
            $contextStr = json_encode($context);
            $formattedMessage .= " | Context: {$contextStr}";
        }
        
        return $formattedMessage;
    }
}