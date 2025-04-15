<?php
/**
 * MemberPress AI Assistant - Database Logger
 *
 * Logger implementation that stores logs in the database
 *
 * @package MemberPress AI Assistant
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Logger implementation that stores logs in the database
 */
class MPAI_DB_Logger extends MPAI_Abstract_Logger {

    /**
     * Database table name
     *
     * @var string
     */
    protected $table_name;

    /**
     * Whether the table exists
     *
     * @var bool
     */
    protected $table_exists = false;

    /**
     * Component name for filtering logs
     *
     * @var string
     */
    protected $component;

    /**
     * Constructor
     *
     * @param string $component     Component name for filtering logs.
     * @param string $minimum_level Minimum log level to record.
     */
    public function __construct( $component = 'core', $minimum_level = 'debug' ) {
        parent::__construct( $minimum_level );
        
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'mpai_logs';
        $this->component = sanitize_text_field( $component );
        
        // Check if table exists
        $this->check_table();
    }

    /**
     * Check if the log table exists, try to create it if not
     *
     * @return bool Whether the table exists.
     */
    protected function check_table() {
        global $wpdb;
        
        try {
            $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$this->table_name}'" ) === $this->table_name;
            
            if ( $table_exists ) {
                $this->table_exists = true;
                return true;
            }
            
            // Table doesn't exist, try to create it
            return $this->create_table();
        } catch ( \Exception $e ) {
            error_log( 'MPAI: Error checking log table: ' . $e->getMessage() );
            return false;
        }
    }

    /**
     * Create the log table
     *
     * @return bool Whether the table was created successfully.
     */
    protected function create_table() {
        global $wpdb;
        
        try {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE {$this->table_name} (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                timestamp datetime NOT NULL,
                level varchar(20) NOT NULL,
                component varchar(100) NOT NULL,
                message text NOT NULL,
                context longtext,
                user_id bigint(20),
                PRIMARY KEY  (id),
                KEY level (level),
                KEY component (component),
                KEY timestamp (timestamp)
            ) $charset_collate;";
            
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            $result = dbDelta( $sql );
            
            // Check if table was created
            $this->table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$this->table_name}'" ) === $this->table_name;
            
            return $this->table_exists;
        } catch ( \Exception $e ) {
            error_log( 'MPAI: Error creating log table: ' . $e->getMessage() );
            return false;
        }
    }

    /**
     * Log a message at a specific level
     *
     * @param string $level   Log level.
     * @param string $message The log message.
     * @param array  $context Additional context data.
     * @return void
     */
    public function log( $level, $message, array $context = array() ) {
        if ( ! $this->should_log( $level ) ) {
            return;
        }
        
        // If table doesn't exist, fallback to error_log
        if ( ! $this->table_exists ) {
            $formatted_message = $this->format_message( $level, $message, $context );
            error_log( 'MPAI: ' . $formatted_message );
            return;
        }
        
        global $wpdb;
        
        try {
            // Get current user ID if available
            $user_id = is_user_logged_in() ? get_current_user_id() : null;
            
            // Insert log entry
            $result = $wpdb->insert(
                $this->table_name,
                array(
                    'timestamp' => current_time( 'mysql' ),
                    'level'     => $level,
                    'component' => $this->component,
                    'message'   => $message,
                    'context'   => wp_json_encode( $context ),
                    'user_id'   => $user_id,
                ),
                array( '%s', '%s', '%s', '%s', '%s', '%d' )
            );
            
            // If insert failed, fallback to error_log
            if ( false === $result ) {
                $formatted_message = $this->format_message( $level, $message, $context );
                error_log( 'MPAI: DB Log insert failed. ' . $formatted_message );
                error_log( 'MPAI: DB error: ' . $wpdb->last_error );
            }
        } catch ( \Exception $e ) {
            $formatted_message = $this->format_message( $level, $message, $context );
            error_log( 'MPAI: Exception in DB logger: ' . $e->getMessage() );
            error_log( 'MPAI: ' . $formatted_message );
        }
    }

    /**
     * Get logs from the database
     *
     * @param array $args Query arguments.
     * @return array Array of log entries.
     */
    public function get_logs( $args = array() ) {
        if ( ! $this->table_exists ) {
            return array();
        }
        
        global $wpdb;
        
        $defaults = array(
            'level'       => '',
            'component'   => '',
            'date_from'   => '',
            'date_to'     => '',
            'user_id'     => 0,
            'search'      => '',
            'limit'       => 100,
            'offset'      => 0,
            'orderby'     => 'timestamp',
            'order'       => 'DESC',
        );
        
        $args = wp_parse_args( $args, $defaults );
        
        try {
            $where = 'WHERE 1=1';
            $prepare_args = array();
            
            // Add filters to query
            if ( ! empty( $args['level'] ) ) {
                $where .= ' AND level = %s';
                $prepare_args[] = $args['level'];
            }
            
            if ( ! empty( $args['component'] ) ) {
                $where .= ' AND component = %s';
                $prepare_args[] = $args['component'];
            }
            
            if ( ! empty( $args['date_from'] ) ) {
                $where .= ' AND timestamp >= %s';
                $prepare_args[] = $args['date_from'];
            }
            
            if ( ! empty( $args['date_to'] ) ) {
                $where .= ' AND timestamp <= %s';
                $prepare_args[] = $args['date_to'];
            }
            
            if ( ! empty( $args['user_id'] ) ) {
                $where .= ' AND user_id = %d';
                $prepare_args[] = $args['user_id'];
            }
            
            if ( ! empty( $args['search'] ) ) {
                $where .= ' AND message LIKE %s';
                $prepare_args[] = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            }
            
            // Sanitize orderby field
            $allowed_fields = array( 'id', 'timestamp', 'level', 'component', 'user_id' );
            $orderby = in_array( $args['orderby'], $allowed_fields ) ? $args['orderby'] : 'timestamp';
            
            // Sanitize order direction
            $order = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';
            
            // Add limit clause
            $limit = '';
            if ( $args['limit'] > 0 ) {
                $limit = 'LIMIT %d';
                $prepare_args[] = $args['limit'];
                
                if ( $args['offset'] > 0 ) {
                    $limit .= ' OFFSET %d';
                    $prepare_args[] = $args['offset'];
                }
            }
            
            // Prepare final query
            $query = "SELECT * FROM {$this->table_name} {$where} ORDER BY {$orderby} {$order} {$limit}";
            
            if ( ! empty( $prepare_args ) ) {
                $query = $wpdb->prepare( $query, $prepare_args );
            }
            
            // Execute query
            $results = $wpdb->get_results( $query, ARRAY_A );
            
            if ( empty( $results ) ) {
                return array();
            }
            
            // Process results
            foreach ( $results as &$result ) {
                if ( ! empty( $result['context'] ) ) {
                    $result['context'] = json_decode( $result['context'], true );
                }
                
                // Add user info
                if ( ! empty( $result['user_id'] ) ) {
                    $user = get_userdata( $result['user_id'] );
                    if ( $user ) {
                        $result['user_info'] = array(
                            'display_name' => $user->display_name,
                            'user_email'   => $user->user_email,
                            'user_login'   => $user->user_login,
                        );
                    }
                }
            }
            
            return $results;
        } catch ( \Exception $e ) {
            error_log( 'MPAI: Error retrieving logs: ' . $e->getMessage() );
            return array();
        }
    }

    /**
     * Count logs in the database
     *
     * @param array $args Query arguments.
     * @return int Number of matching logs.
     */
    public function count_logs( $args = array() ) {
        if ( ! $this->table_exists ) {
            return 0;
        }
        
        global $wpdb;
        
        $defaults = array(
            'level'       => '',
            'component'   => '',
            'date_from'   => '',
            'date_to'     => '',
            'user_id'     => 0,
            'search'      => '',
        );
        
        $args = wp_parse_args( $args, $defaults );
        
        try {
            $where = 'WHERE 1=1';
            $prepare_args = array();
            
            // Add filters to query
            if ( ! empty( $args['level'] ) ) {
                $where .= ' AND level = %s';
                $prepare_args[] = $args['level'];
            }
            
            if ( ! empty( $args['component'] ) ) {
                $where .= ' AND component = %s';
                $prepare_args[] = $args['component'];
            }
            
            if ( ! empty( $args['date_from'] ) ) {
                $where .= ' AND timestamp >= %s';
                $prepare_args[] = $args['date_from'];
            }
            
            if ( ! empty( $args['date_to'] ) ) {
                $where .= ' AND timestamp <= %s';
                $prepare_args[] = $args['date_to'];
            }
            
            if ( ! empty( $args['user_id'] ) ) {
                $where .= ' AND user_id = %d';
                $prepare_args[] = $args['user_id'];
            }
            
            if ( ! empty( $args['search'] ) ) {
                $where .= ' AND message LIKE %s';
                $prepare_args[] = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            }
            
            // Prepare final query
            $query = "SELECT COUNT(*) FROM {$this->table_name} {$where}";
            
            if ( ! empty( $prepare_args ) ) {
                $query = $wpdb->prepare( $query, $prepare_args );
            }
            
            // Execute query
            $count = $wpdb->get_var( $query );
            
            return (int) $count;
        } catch ( \Exception $e ) {
            error_log( 'MPAI: Error counting logs: ' . $e->getMessage() );
            return 0;
        }
    }

    /**
     * Delete old logs
     *
     * @param int $days Number of days to keep logs. Default is 30 days.
     * @return int|false Number of rows deleted or false on error.
     */
    public function delete_old_logs( $days = 30 ) {
        if ( ! $this->table_exists ) {
            return false;
        }
        
        if ( $days <= 0 ) {
            return 0; // Keep all logs
        }
        
        global $wpdb;
        
        try {
            $date = date( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
            
            $result = $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$this->table_name} WHERE timestamp < %s",
                    $date
                )
            );
            
            return $result;
        } catch ( \Exception $e ) {
            error_log( 'MPAI: Error deleting old logs: ' . $e->getMessage() );
            return false;
        }
    }
}