# MemberPress AI Assistant - Error Catalog System

## Overview

This document outlines the comprehensive error typing and catalog system for the MemberPress AI Assistant plugin. The system provides a structured approach to error identification, reporting, and resolution to improve debugging and user experience.

## Error Code Structure

### Format

All error codes follow this format:

```
MPAI-[CATEGORY]-[COMPONENT]-[CODE]
```

For example: `MPAI-API-OPENAI-001`

### Categories

- **API**: API connection and response errors
- **DB**: Database operation errors
- **TOOL**: Tool execution errors
- **AGENT**: Agent system errors
- **UI**: User interface errors
- **AUTH**: Authentication and permission errors
- **CONFIG**: Configuration errors
- **CONTENT**: Content processing errors
- **SYSTEM**: Core system errors

### Component Codes

Each category has specific components identified by short codes:
- API: `OPENAI`, `ANTHROPIC`, `WP`, `MEPR`
- DB: `QUERY`, `CONNECT`, `SCHEMA`
- TOOL: `WPCLI`, `DIAGNOSTIC`, `LOGS`, `WPAPI`
- AGENT: `ORCHESTR`, `MEMBPR`, `CMDVAL`, `SDKADAPT`
- UI: `CHAT`, `ADMIN`, `FORM`
- AUTH: `NONCE`, `PERM`, `TOKEN`
- CONFIG: `SETTING`, `OPTION`
- CONTENT: `PARSE`, `FORMAT`, `XML`, `JSON`
- SYSTEM: `INIT`, `LOAD`, `COMPAT`, `DEPEND`

### Severity Levels

Each error has a severity level indicated in logs and error messages:

1. **CRITICAL**: System cannot function, immediate attention required
2. **ERROR**: Functionality broken, requires attention
3. **WARNING**: Potential issue that might lead to problems
4. **NOTICE**: Informational message about unusual behavior

## Error Message Format

Standard format for all error messages:

```
[ERROR_CODE] [SEVERITY]: [Human-readable message] - [Context details]
```

Example:
```
MPAI-API-OPENAI-001 ERROR: Failed to connect to OpenAI API - Timeout after 30 seconds
```

## Implementation Guidelines

### PHP Implementation

Add to class-mpai-plugin-logger.php:

```php
/**
 * Log an error with type classification
 *
 * @param string $code Error code following MPAI-[CATEGORY]-[COMPONENT]-[CODE] format
 * @param string $message Human-readable error message
 * @param string $severity Error severity (CRITICAL, ERROR, WARNING, NOTICE)
 * @param array $context Additional context data for debugging
 */
public function log_typed_error($code, $message, $severity = 'ERROR', $context = []) {
    // Format the error message
    $formatted_message = sprintf(
        '%s %s: %s',
        $code,
        strtoupper($severity),
        $message
    );
    
    // Add context if available
    if (!empty($context)) {
        $formatted_message .= ' - ' . json_encode($context);
    }
    
    // Log to WordPress error log
    error_log('MPAI: ' . $formatted_message);
    
    // Store in internal log
    $this->store_error_log($code, $message, $severity, $context);
}

/**
 * Store error in the database for reference
 *
 * @param string $code Error code
 * @param string $message Error message
 * @param string $severity Error severity
 * @param array $context Error context
 */
private function store_error_log($code, $message, $severity, $context) {
    global $wpdb;
    
    // Create error log table if it doesn't exist
    $this->maybe_create_error_table();
    
    // Insert the error log
    $wpdb->insert(
        $wpdb->prefix . 'mpai_error_logs',
        [
            'error_code' => $code,
            'message' => $message,
            'severity' => $severity,
            'context' => json_encode($context),
            'date_time' => current_time('mysql'),
            'user_id' => get_current_user_id()
        ],
        ['%s', '%s', '%s', '%s', '%s', '%d']
    );
}

/**
 * Create error log table if it doesn't exist
 */
private function maybe_create_error_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mpai_error_logs';
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            error_code varchar(50) NOT NULL,
            message text NOT NULL,
            severity varchar(20) NOT NULL,
            context longtext,
            date_time datetime NOT NULL,
            user_id bigint(20),
            PRIMARY KEY (id),
            KEY error_code (error_code),
            KEY severity (severity),
            KEY date_time (date_time)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
```

### JavaScript Implementation

Add to mpai-logger.js:

```javascript
/**
 * Log a typed error with standardized format
 * 
 * @param {string} code Error code in MPAI-[CATEGORY]-[COMPONENT]-[CODE] format
 * @param {string} message Human-readable error message
 * @param {string} severity Error severity (CRITICAL, ERROR, WARNING, NOTICE)
 * @param {object} context Additional context data for debugging
 * @param {string} category Logging category
 */
MpaiLogger.prototype.logTypedError = function(code, message, severity = 'ERROR', context = {}, category = 'system') {
    // Default to error log level for CRITICAL and ERROR, warn for WARNING, info for NOTICE
    let logMethod = 'error';
    if (severity === 'WARNING') logMethod = 'warn';
    if (severity === 'NOTICE') logMethod = 'info';
    
    // Format the message
    const formattedMessage = `${code} ${severity}: ${message}`;
    
    // Log using the appropriate method
    this[logMethod](formattedMessage, category, context);
    
    // If it's a critical or error, also send to server
    if (severity === 'CRITICAL' || severity === 'ERROR') {
        this.sendErrorToServer(code, message, severity, context);
    }
};

/**
 * Send error to server for logging in database
 */
MpaiLogger.prototype.sendErrorToServer = function(code, message, severity, context) {
    // Only send if enabled and in a browser environment
    if (!this.enabled || typeof jQuery === 'undefined') return;
    
    jQuery.ajax({
        url: mpai_data.ajax_url,
        type: 'POST',
        data: {
            action: 'mpai_log_client_error',
            code: code,
            message: message,
            severity: severity,
            context: JSON.stringify(context),
            nonce: mpai_data.nonce
        }
    });
};
```

## Performance Optimization

The current logging system has been identified as a significant performance bottleneck, particularly with AI and agent responses. The new error typing system will address these issues through:

### Performance Improvement Strategies

1. **Conditional Logging**: Only log what's absolutely necessary based on environment
   - Production: Log only CRITICAL and ERROR levels
   - Development: Enable full logging
   - Debug: Comprehensive logging with detailed context

2. **Batch Processing**:
   - Collect multiple log entries in memory
   - Flush logs in batches on low-priority hooks
   - Use a dedicated background process for database writes

3. **Memory Management**:
   - Limit context object size
   - Implement circular buffers for high-volume logs
   - Auto-prune old logs from memory

4. **Async Logging**:
   - Use non-blocking AJAX for JavaScript logging
   - Implement deferred database writes in PHP
   - Utilize WordPress cron for batch processing

5. **Log Filtering**:
   - Implement smart filtering to avoid duplicate errors
   - Rate limit similar errors
   - Collapse repeated errors with counters

### JavaScript Optimization

```javascript
// Add to mpai-logger.js
MpaiLogger.prototype.batchedLogs = [];
MpaiLogger.prototype.isFlushing = false;
MpaiLogger.prototype.maxBatchSize = 10;
MpaiLogger.prototype.flushInterval = 5000; // 5 seconds

// Replace direct logging with batched logging
MpaiLogger.prototype.queueLog = function(level, message, category, data) {
    // Only add to queue if logging is enabled
    if (!this.shouldLog(level, category)) {
        return;
    }
    
    this.batchedLogs.push({
        level: level,
        message: message,
        category: category,
        data: this.limitObjectSize(data),
        timestamp: new Date().getTime()
    });
    
    // If batch is full or it's a critical error, flush immediately
    if (this.batchedLogs.length >= this.maxBatchSize || level === 'critical') {
        this.flushLogs();
    } else if (!this.isFlushing) {
        // Set up delayed flush if not already scheduled
        this.isFlushing = true;
        setTimeout(() => this.flushLogs(), this.flushInterval);
    }
};

// Limit object size to prevent memory issues
MpaiLogger.prototype.limitObjectSize = function(obj, maxDepth = 3, currentDepth = 0) {
    if (!obj || typeof obj !== 'object') return obj;
    if (currentDepth >= maxDepth) return '[Object depth limit]';
    
    const result = Array.isArray(obj) ? [] : {};
    
    // Limit number of properties
    const keys = Object.keys(obj).slice(0, 20);
    
    for (const key of keys) {
        if (typeof obj[key] === 'object' && obj[key] !== null) {
            result[key] = this.limitObjectSize(obj[key], maxDepth, currentDepth + 1);
        } else {
            // Limit string length
            if (typeof obj[key] === 'string' && obj[key].length > 500) {
                result[key] = obj[key].substring(0, 500) + '... [truncated]';
            } else {
                result[key] = obj[key];
            }
        }
    }
    
    if (Object.keys(obj).length > 20) {
        result['__limited'] = `${Object.keys(obj).length - 20} more properties omitted`;
    }
    
    return result;
};

// Process batched logs
MpaiLogger.prototype.flushLogs = function() {
    if (this.batchedLogs.length === 0) {
        this.isFlushing = false;
        return;
    }
    
    const batchToProcess = [...this.batchedLogs];
    this.batchedLogs = [];
    this.isFlushing = false;
    
    // Process logs in batch
    for (const logEntry of batchToProcess) {
        const formattedMessage = this.formatMessage(logEntry.message, logEntry.category);
        
        // Use console methods based on level
        switch (logEntry.level) {
            case 'error':
                console.error(formattedMessage, logEntry.data);
                break;
            case 'warning':
                console.warn(formattedMessage, logEntry.data);
                break;
            case 'info':
                console.info(formattedMessage, logEntry.data);
                break;
            default:
                console.log(formattedMessage, logEntry.data);
        }
    }
    
    // Send batch to server if needed (only for errors and critical)
    const serverBatch = batchToProcess.filter(log => 
        log.level === 'error' || log.level === 'critical'
    );
    
    if (serverBatch.length > 0) {
        this.sendBatchToServer(serverBatch);
    }
};
```

### PHP Optimization

```php
/**
 * Optimized error logging with batching
 */
class MPAI_Optimized_Logger extends MPAI_Plugin_Logger {
    /**
     * Store for batched logs
     */
    private $log_batch = array();
    
    /**
     * Maximum batch size before forced flush
     */
    private $max_batch_size = 20;
    
    /**
     * Whether a scheduled flush is pending
     */
    private $flush_scheduled = false;
    
    /**
     * Log an error with batching for performance
     */
    public function log_typed_error($code, $message, $severity = 'ERROR', $context = []) {
        // For CRITICAL errors, log immediately
        if ($severity === 'CRITICAL') {
            $this->immediate_log($code, $message, $severity, $context);
            return;
        }
        
        // Add to batch
        $this->log_batch[] = array(
            'code' => $code,
            'message' => $message,
            'severity' => $severity,
            'context' => $this->limit_context_size($context),
            'time' => microtime(true)
        );
        
        // If batch is full, flush immediately
        if (count($this->log_batch) >= $this->max_batch_size) {
            $this->flush_log_batch();
        } 
        // Otherwise schedule a delayed flush
        elseif (!$this->flush_scheduled) {
            $this->schedule_flush();
        }
    }
    
    /**
     * Limit the size of context data to prevent memory issues
     */
    private function limit_context_size($context, $max_depth = 3, $current_depth = 0) {
        if (!is_array($context) && !is_object($context)) {
            return $context;
        }
        
        if ($current_depth >= $max_depth) {
            return '[Depth limit reached]';
        }
        
        $result = array();
        $i = 0;
        
        foreach ((array)$context as $key => $value) {
            // Limit number of elements
            if ($i++ > 20) {
                $result['__limited'] = 'Additional items omitted';
                break;
            }
            
            if (is_array($value) || is_object($value)) {
                $result[$key] = $this->limit_context_size($value, $max_depth, $current_depth + 1);
            } else if (is_string($value) && strlen($value) > 1000) {
                $result[$key] = substr($value, 0, 1000) . '... [truncated]';
            } else {
                $result[$key] = $value;
            }
        }
        
        return $result;
    }
    
    /**
     * Schedule a delayed flush using WordPress hooks
     */
    private function schedule_flush() {
        $this->flush_scheduled = true;
        add_action('shutdown', array($this, 'flush_log_batch'), 999);
    }
    
    /**
     * Flush the log batch to storage
     */
    public function flush_log_batch() {
        if (empty($this->log_batch)) {
            $this->flush_scheduled = false;
            return;
        }
        
        foreach ($this->log_batch as $log) {
            // Format the message
            $formatted_message = sprintf(
                '%s %s: %s',
                $log['code'],
                strtoupper($log['severity']),
                $log['message']
            );
            
            // Log to WordPress error log
            error_log('MPAI: ' . $formatted_message);
            
            // Only ERROR and CRITICAL go to database
            if ($log['severity'] === 'ERROR' || $log['severity'] === 'CRITICAL') {
                $this->store_error_log(
                    $log['code'],
                    $log['message'],
                    $log['severity'],
                    $log['context']
                );
            }
        }
        
        // Clear the batch
        $this->log_batch = array();
        $this->flush_scheduled = false;
    }
    
    /**
     * Log immediately for critical errors
     */
    private function immediate_log($code, $message, $severity, $context) {
        // Format the message
        $formatted_message = sprintf(
            '%s %s: %s',
            $code,
            strtoupper($severity),
            $message
        );
        
        // Add context if available
        if (!empty($context)) {
            $formatted_message .= ' - ' . json_encode($this->limit_context_size($context));
        }
        
        // Log to WordPress error log
        error_log('MPAI: ' . $formatted_message);
        
        // Store in database
        $this->store_error_log($code, $message, $severity, $context);
    }
}
```

## Log Management Interface

The error catalog system will include a comprehensive log management interface integrated into the existing System Diagnostics page.

### Admin UI Components

1. **Error Log Viewer**:
   - Tabbed interface within the System Diagnostics page
   - Filterable table with columns for:
     - Error Code
     - Severity
     - Message
     - Time/Date
     - User
   - Expandable rows showing detailed context data
   - Color-coded severity levels for visual identification
   - Search functionality across all fields

2. **Filtering Options**:
   - Filter by category (API, DB, TOOL, etc.)
   - Filter by severity level
   - Filter by date range
   - Filter by component
   - Filter by user

3. **Log Management**:
   - Manual log clearing with options:
     - Clear all logs
     - Clear logs by severity
     - Clear logs older than X days
     - Clear logs by category
   - Export options:
     - CSV format
     - JSON format
     - Filtered exports based on current view

4. **Dashboard Overview**:
   - Error count by severity
   - Error trends over time
   - Most frequent error types
   - Recent critical errors highlighted

### Log Retention Settings

The system will include configurable retention settings:

1. **Automatic Cleanup Settings**:
   - Retention period slider (1-365 days)
   - Option to keep critical errors longer
   - Maximum log size limit
   - Automatic cleanup scheduling options:
     - Daily
     - Weekly
     - Monthly

2. **Cron Implementation**:
   - WordPress cron job for automatic cleanup
   - Custom schedule based on user settings
   - Failsafe to prevent database overflow
   - Email notifications for large log purges

```php
/**
 * Register log retention settings
 */
public function register_retention_settings() {
    // Add to existing settings page
    add_settings_section(
        'mpai_error_log_settings',
        'Error Log Management',
        array($this, 'error_log_settings_section'),
        'mpai_settings'
    );
    
    // Retention period setting
    register_setting('mpai_settings', 'mpai_error_log_retention_days', array(
        'type' => 'integer',
        'default' => 30,
        'sanitize_callback' => 'absint',
    ));
    
    add_settings_field(
        'mpai_error_log_retention_days',
        'Log Retention Period (Days)',
        array($this, 'render_retention_days_field'),
        'mpai_settings',
        'mpai_error_log_settings'
    );
    
    // Critical error extended retention
    register_setting('mpai_settings', 'mpai_keep_critical_errors_longer', array(
        'type' => 'boolean',
        'default' => true,
    ));
    
    add_settings_field(
        'mpai_keep_critical_errors_longer',
        'Keep Critical Errors Longer',
        array($this, 'render_critical_retention_field'),
        'mpai_settings',
        'mpai_error_log_settings'
    );
    
    // Cleanup schedule
    register_setting('mpai_settings', 'mpai_error_log_cleanup_schedule', array(
        'type' => 'string',
        'default' => 'daily',
    ));
    
    add_settings_field(
        'mpai_error_log_cleanup_schedule',
        'Automatic Cleanup Schedule',
        array($this, 'render_cleanup_schedule_field'),
        'mpai_settings',
        'mpai_error_log_settings'
    );
}

/**
 * Setup error log cleanup cron job
 */
public function setup_cleanup_cron() {
    $schedule = get_option('mpai_error_log_cleanup_schedule', 'daily');
    
    // Clear any existing scheduled events
    wp_clear_scheduled_hook('mpai_error_log_cleanup');
    
    // Schedule new event
    if (!wp_next_scheduled('mpai_error_log_cleanup')) {
        wp_schedule_event(time(), $schedule, 'mpai_error_log_cleanup');
    }
    
    // Add cron action
    add_action('mpai_error_log_cleanup', array($this, 'cleanup_old_logs'));
}

/**
 * Clean up old logs based on retention settings
 */
public function cleanup_old_logs() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mpai_error_logs';
    
    // Get retention period
    $retention_days = get_option('mpai_error_log_retention_days', 30);
    $keep_critical = get_option('mpai_keep_critical_errors_longer', true);
    
    // Default query for normal cleanup
    $where = "WHERE date_time < DATE_SUB(NOW(), INTERVAL %d DAY)";
    $params = array($retention_days);
    
    // If keeping critical errors longer, modify the query
    if ($keep_critical) {
        $where .= " AND severity != 'CRITICAL'";
    }
    
    // Execute cleanup
    $query = $wpdb->prepare("DELETE FROM $table_name $where", $params);
    $deleted = $wpdb->query($query);
    
    // Log the cleanup
    error_log(sprintf('MPAI: Cleaned up %d error log entries (older than %d days)', $deleted, $retention_days));
    
    // If keeping critical errors, clean them with extended period
    if ($keep_critical) {
        // Keep critical errors 3x longer
        $critical_retention = $retention_days * 3;
        $critical_query = $wpdb->prepare(
            "DELETE FROM $table_name WHERE severity = 'CRITICAL' AND date_time < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $critical_retention
        );
        $critical_deleted = $wpdb->query($critical_query);
        
        error_log(sprintf('MPAI: Cleaned up %d critical error log entries (older than %d days)', $critical_deleted, $critical_retention));
    }
    
    return $deleted;
}

/**
 * AJAX handler for manual log clearing
 */
public function handle_manual_log_clear() {
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
    }
    
    // Verify nonce
    check_admin_referer('mpai_clear_logs', 'nonce');
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'mpai_error_logs';
    
    $mode = isset($_POST['mode']) ? sanitize_text_field($_POST['mode']) : 'all';
    
    switch ($mode) {
        case 'all':
            $result = $wpdb->query("TRUNCATE TABLE $table_name");
            $message = 'All logs cleared successfully';
            break;
            
        case 'severity':
            $severity = isset($_POST['severity']) ? sanitize_text_field($_POST['severity']) : '';
            if (!empty($severity)) {
                $result = $wpdb->delete($table_name, array('severity' => $severity));
                $message = sprintf('%d logs with severity %s cleared', $result, $severity);
            } else {
                wp_send_json_error('No severity specified');
            }
            break;
            
        case 'older_than':
            $days = isset($_POST['days']) ? absint($_POST['days']) : 0;
            if ($days > 0) {
                $result = $wpdb->query($wpdb->prepare(
                    "DELETE FROM $table_name WHERE date_time < DATE_SUB(NOW(), INTERVAL %d DAY)",
                    $days
                ));
                $message = sprintf('%d logs older than %d days cleared', $result, $days);
            } else {
                wp_send_json_error('Invalid days value');
            }
            break;
            
        case 'category':
            $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
            if (!empty($category)) {
                $result = $wpdb->query($wpdb->prepare(
                    "DELETE FROM $table_name WHERE error_code LIKE %s",
                    'MPAI-' . $category . '-%'
                ));
                $message = sprintf('%d logs in category %s cleared', $result, $category);
            } else {
                wp_send_json_error('No category specified');
            }
            break;
            
        default:
            wp_send_json_error('Invalid clear mode');
    }
    
    if ($result !== false) {
        wp_send_json_success($message);
    } else {
        wp_send_json_error('Failed to clear logs: ' . $wpdb->last_error);
    }
}
```

## Migration Strategy

### Replacing Existing Error Logs

1. **PHP Code Refactoring**:
   - Create search patterns to identify current `error_log()` calls
   - Replace with typed error logging using appropriate categories
   - Example: `error_log('MPAI: Database error')` → `$logger->log_typed_error('MPAI-DB-QUERY-001', 'Database error', 'ERROR', $context)`

2. **JavaScript Refactoring**:
   - Replace `console.log/error/warn` calls with typed error logging
   - Replace existing `mpaiLogger` calls with new optimized methods
   - Add error codes to all logging calls

3. **Transition Period**:
   - Implement backward compatibility layer for existing code
   - Create helper functions that map old logging to new system
   - Gradually replace all occurrences in phased approach

### Implementation Phases

#### Phase 1: Core System Implementation (Weeks 1-2)
- Implement optimized logger classes (PHP and JS)
- Create database tables for structured error storage
- Add admin settings for log level and retention

#### Phase 2: Code Migration (Weeks 3-4)
- Replace existing error_log calls in critical components
- Update JavaScript console logging in key UI components
- Implement auto-detection of repeated error patterns

#### Phase 3: Usability Features (Weeks 5-6)
- Create admin interface for error browsing and filtering
- Add error export capabilities
- Implement notification system for critical errors
- Build log management controls in System Diagnostics
- Implement manual log clearing functionality

#### Phase 4: Performance Tuning (Weeks 7-8)
- Fine-tune batching parameters based on real-world usage
- Optimize database queries and indexes
- Implement intelligent log rotation and archiving
- Set up automated cleanup cron jobs
- Test and optimize log retention settings

## Error Catalog

### API Errors (API)

| Code | Description | Likely Causes | Resolution Steps |
|------|-------------|--------------|-----------------|
| MPAI-API-OPENAI-001 | OpenAI connection failed | API key invalid, network issue | Check API key, network connection |
| MPAI-API-OPENAI-002 | OpenAI returned error response | Invalid parameters, rate limiting | Review request parameters, check quotas |
| MPAI-API-ANTHROPIC-001 | Anthropic connection failed | API key invalid, network issue | Check API key, network connection |
| MPAI-API-ANTHROPIC-002 | Anthropic returned error response | Invalid parameters, rate limiting | Review request parameters, check quotas |
| MPAI-API-WP-001 | WordPress API request failed | Invalid endpoint, permissions issue | Check endpoint URL, verify permissions |
| MPAI-API-MEPR-001 | MemberPress API connection failed | MemberPress not active, incompatible version | Verify MemberPress is active and compatible |

### Database Errors (DB)

| Code | Description | Likely Causes | Resolution Steps |
|------|-------------|--------------|-----------------|
| MPAI-DB-CONNECT-001 | Database connection failed | Database credentials issue | Check wp-config.php database settings |
| MPAI-DB-QUERY-001 | Database query error | SQL syntax error, missing table | Check query syntax, verify table exists |
| MPAI-DB-SCHEMA-001 | Table creation failed | Permissions issue, incompatible MySQL version | Check database permissions, MySQL version |

### Tool Errors (TOOL)

| Code | Description | Likely Causes | Resolution Steps |
|------|-------------|--------------|-----------------|
| MPAI-TOOL-WPCLI-001 | WP-CLI command execution failed | Invalid command, permissions issue | Verify command syntax, check permissions |
| MPAI-TOOL-DIAGNOSTIC-001 | Diagnostic tool error | Missing system information | Check WordPress environment |
| MPAI-TOOL-LOGS-001 | Log retrieval failed | Log file missing or inaccessible | Check log file permissions |
| MPAI-TOOL-WPAPI-001 | WordPress API tool execution failed | Invalid endpoint, authentication issue | Verify endpoint, check authentication |

### Agent Errors (AGENT)

| Code | Description | Likely Causes | Resolution Steps |
|------|-------------|--------------|-----------------|
| MPAI-AGENT-ORCHESTR-001 | Agent orchestration failed | Agent initialization error | Check agent configuration |
| MPAI-AGENT-MEMBPR-001 | MemberPress agent execution error | MemberPress data access issue | Verify MemberPress installation |
| MPAI-AGENT-CMDVAL-001 | Command validation failed | Invalid command format | Check command syntax |
| MPAI-AGENT-SDKADAPT-001 | SDK adapter initialization failed | Missing SDK dependencies | Install required dependencies |

### UI Errors (UI)

| Code | Description | Likely Causes | Resolution Steps |
|------|-------------|--------------|-----------------|
| MPAI-UI-CHAT-001 | Chat interface rendering failed | JavaScript error, DOM manipulation issue | Check browser console, verify JavaScript |
| MPAI-UI-ADMIN-001 | Admin page rendering error | WordPress hook conflict | Disable conflicting plugins |
| MPAI-UI-FORM-001 | Form submission failed | Invalid form data, AJAX error | Validate form data, check AJAX handler |

### Authentication Errors (AUTH)

| Code | Description | Likely Causes | Resolution Steps |
|------|-------------|--------------|-----------------|
| MPAI-AUTH-NONCE-001 | Nonce verification failed | Expired nonce, security breach attempt | Refresh page, verify user session |
| MPAI-AUTH-PERM-001 | Permission check failed | Insufficient user capabilities | Check user role and capabilities |
| MPAI-AUTH-TOKEN-001 | API token validation failed | Expired token, invalid token | Regenerate API token |

### Configuration Errors (CONFIG)

| Code | Description | Likely Causes | Resolution Steps |
|------|-------------|--------------|-----------------|
| MPAI-CONFIG-SETTING-001 | Invalid setting value | User input error, option corruption | Reset to default settings |
| MPAI-CONFIG-OPTION-001 | Option retrieval failed | Option not set, database corruption | Verify option exists, repair database |

### Content Errors (CONTENT)

| Code | Description | Likely Causes | Resolution Steps |
|------|-------------|--------------|-----------------|
| MPAI-CONTENT-PARSE-001 | Content parsing failed | Malformed content structure | Check content format |
| MPAI-CONTENT-FORMAT-001 | Content formatting error | Invalid format parameters | Verify formatting options |
| MPAI-CONTENT-XML-001 | XML parsing failed | Malformed XML | Check XML structure |
| MPAI-CONTENT-JSON-001 | JSON parsing failed | Invalid JSON | Validate JSON structure |

### System Errors (SYSTEM)

| Code | Description | Likely Causes | Resolution Steps |
|------|-------------|--------------|-----------------|
| MPAI-SYSTEM-INIT-001 | Plugin initialization failed | Hook execution error, conflicting plugin | Check activation hooks, disable conflicting plugins |
| MPAI-SYSTEM-LOAD-001 | Resource loading failed | Missing file, path error | Verify file exists, check path |
| MPAI-SYSTEM-COMPAT-001 | Compatibility check failed | Incompatible WordPress version | Update WordPress or plugin |
| MPAI-SYSTEM-DEPEND-001 | Dependency check failed | Missing required plugin or PHP extension | Install required dependencies |

## Usage Guidelines

### When to Log Errors

- Log CRITICAL errors for any condition that prevents core plugin functionality
- Log ERROR level for specific feature failures that impact user experience
- Log WARNING level for potential issues that don't immediately impact functionality
- Log NOTICE level for unusual but non-problematic conditions

### Error Reporting Best Practices

1. Always include specific error codes in user-facing error messages
2. Provide user-friendly explanations without technical details in UI
3. Log detailed technical context for debugging purposes
4. Include steps to reproduce and fix common errors in documentation

### Error Resolution Flow

1. User encounters error and sees error code
2. Admin checks error logs in WordPress admin or system logs
3. Admin looks up error code in documentation
4. Admin follows resolution steps for that specific error
5. If unresolved, admin provides error code and logs in support requests

## Conclusion

This error catalog system provides a structured approach to error handling, improving both developer debugging capabilities and end-user support experience. By standardizing error codes, messages, and resolution steps while optimizing performance, we create a more robust and maintainable plugin that maintains responsiveness even during error conditions. The integrated log management UI and automated cleanup features ensure that logs remain useful without becoming overwhelming or impacting system performance.