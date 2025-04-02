<?php
/**
 * MemberPress AI Assistant - Plugin Logger
 *
 * Tracks plugin installation, activation, deactivation, and deletion events.
 *
 * @package MemberPress AI Assistant
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Class for tracking plugin installation and management activities.
 */
class MPAI_Plugin_Logger {

    /**
     * Database table name for storing logs
     *
     * @var string
     */
    private $table_name;

    /**
     * Instance of this class
     *
     * @var MPAI_Plugin_Logger
     */
    private static $instance = null;

    /**
     * Plugin data storage for deletion tracking
     *
     * @var array
     */
    private $plugins_data = array();

    /**
     * Initialize the logger
     */
    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'mpai_plugin_logs';
        
        // Create database table if it doesn't exist
        $this->maybe_create_table();
        
        // Register action hooks
        add_filter( 'upgrader_process_complete', array( $this, 'log_plugin_installation' ), 10, 2 );
        add_action( 'activated_plugin', array( $this, 'log_plugin_activation' ), 10, 2 );
        add_action( 'deactivated_plugin', array( $this, 'log_plugin_deactivation' ), 10, 1 );
        add_action( 'delete_plugin', array( $this, 'store_plugin_data_before_deletion' ), 10, 1 );
        add_action( 'deleted_plugin', array( $this, 'log_plugin_deletion' ), 10, 2 );
        
        // Add settings
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        
        // Add cleanup scheduled event
        if ( ! wp_next_scheduled( 'mpai_plugin_logs_cleanup' ) ) {
            wp_schedule_event( time(), 'daily', 'mpai_plugin_logs_cleanup' );
        }
        add_action( 'mpai_plugin_logs_cleanup', array( $this, 'cleanup_old_logs' ) );
    }

    /**
     * Return an instance of this class
     *
     * @return MPAI_Plugin_Logger A single instance of this class.
     */
    public static function get_instance() {
        if ( null == self::$instance ) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting( 'mpai_options', 'mpai_enable_plugin_logging', array(
            'type'    => 'boolean',
            'default' => true,
        ) );
        
        register_setting( 'mpai_options', 'mpai_plugin_logs_retention_days', array(
            'type'              => 'integer',
            'default'           => 90,
            'sanitize_callback' => 'absint',
        ) );
    }

    /**
     * Create the database table if it doesn't exist
     *
     * @return bool Success status
     */
    private function maybe_create_table() {
        global $wpdb;
        
        $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$this->table_name}'" ) === $this->table_name;
        
        if ( $table_exists ) {
            return true;
        }
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            plugin_slug varchar(255) NOT NULL,
            plugin_name varchar(255) NOT NULL,
            plugin_version varchar(100),
            plugin_prev_version varchar(100),
            action varchar(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            user_login varchar(60),
            date_time datetime NOT NULL,
            additional_data longtext,
            PRIMARY KEY  (id),
            KEY plugin_slug (plugin_slug),
            KEY action (action),
            KEY user_id (user_id),
            KEY date_time (date_time)
        ) $charset_collate;";
        
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        
        // Execute the SQL with dbDelta
        $result = dbDelta( $sql );
        
        // Check if table was created successfully
        $table_created = $wpdb->get_var( "SHOW TABLES LIKE '{$this->table_name}'" ) === $this->table_name;
        
        if ( $table_created ) {
            error_log( 'MPAI: Plugin logger table created successfully' );
            return true;
        } else {
            error_log( 'MPAI: Error creating plugin logger table: ' . json_encode( $result ) );
            return false;
        }
    }

    /**
     * Log plugin installation or update
     *
     * @param \WP_Upgrader $upgrader WP_Upgrader instance.
     * @param array        $hook_extra Extra arguments passed to hooked filters.
     */
    public function log_plugin_installation( $upgrader, $hook_extra ) {
        // Check if plugin logging is enabled
        if ( ! $this->is_logging_enabled() ) {
            return;
        }
        
        // Only process plugin actions
        if ( ! isset( $hook_extra['type'] ) || $hook_extra['type'] !== 'plugin' ) {
            return;
        }
        
        // Determine if this is an installation or update
        $action = isset( $hook_extra['action'] ) ? $hook_extra['action'] : '';
        
        if ( $action === 'install' ) {
            $this->log_plugin_install( $upgrader, $hook_extra );
        } elseif ( $action === 'update' ) {
            $this->log_plugin_update( $upgrader, $hook_extra );
        }
    }

    /**
     * Log plugin installation
     *
     * @param \WP_Upgrader $upgrader WP_Upgrader instance.
     * @param array        $hook_extra Extra arguments passed to hooked filters.
     */
    private function log_plugin_install( $upgrader, $hook_extra ) {
        // Single plugin installation
        if ( ! isset( $upgrader->skin->result['destination_name'] ) ) {
            return;
        }
        
        $plugin_slug = $upgrader->skin->result['destination_name'];
        $plugin_path = $upgrader->plugin_info();
        
        if ( ! $plugin_path ) {
            return;
        }
        
        // If plugin_path isn't set properly, try to get it another way
        if ( ! file_exists( WP_PLUGIN_DIR . '/' . $plugin_path ) ) {
            $plugin_path = $hook_extra['plugin'] ?? '';
        }
        
        if ( ! file_exists( WP_PLUGIN_DIR . '/' . $plugin_path ) ) {
            return;
        }
        
        $plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_path, true, false );
        
        $this->insert_log(
            $plugin_slug,
            $plugin_data['Name'],
            $plugin_data['Version'],
            null,
            'installed',
            array(
                'source'      => isset( $upgrader->skin->options['type'] ) ? $upgrader->skin->options['type'] : 'unknown',
                'author'      => $plugin_data['Author'],
                'description' => $plugin_data['Description'],
                'url'         => $plugin_data['PluginURI']
            )
        );
    }

    /**
     * Log plugin update
     *
     * @param \WP_Upgrader $upgrader WP_Upgrader instance.
     * @param array        $hook_extra Extra arguments passed to hooked filters.
     */
    private function log_plugin_update( $upgrader, $hook_extra ) {
        // Bulk updates
        if ( isset( $hook_extra['bulk'] ) && $hook_extra['bulk'] && isset( $hook_extra['plugins'] ) ) {
            $plugins = $hook_extra['plugins'];
            foreach ( $plugins as $plugin_file ) {
                $plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file, true, false );
                $plugin_slug = dirname( $plugin_file );
                
                // Get previous version if available
                $prev_version = $this->get_plugin_prev_version( $plugin_file );
                
                $this->insert_log(
                    $plugin_slug,
                    $plugin_data['Name'],
                    $plugin_data['Version'],
                    $prev_version,
                    'updated',
                    array(
                        'plugin_file' => $plugin_file,
                        'author'      => $plugin_data['Author'],
                        'url'         => $plugin_data['PluginURI']
                    )
                );
            }
        }
        // Single plugin update
        elseif ( ! isset( $hook_extra['bulk'] ) && isset( $hook_extra['plugin'] ) ) {
            $plugin_file = $hook_extra['plugin'];
            $plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file, true, false );
            $plugin_slug = dirname( $plugin_file );
            
            // Get previous version if available
            $prev_version = $this->get_plugin_prev_version( $plugin_file );
            
            $this->insert_log(
                $plugin_slug,
                $plugin_data['Name'],
                $plugin_data['Version'],
                $prev_version,
                'updated',
                array(
                    'plugin_file' => $plugin_file,
                    'author'      => $plugin_data['Author'],
                    'url'         => $plugin_data['PluginURI']
                )
            );
        }
    }

    /**
     * Get previous version of a plugin
     *
     * @param string $plugin_file Plugin file path.
     * @return string|null Previous version.
     */
    private function get_plugin_prev_version( $plugin_file ) {
        global $wpdb;
        
        // Get the previous version from our log
        $plugin_slug = dirname( $plugin_file );
        
        $query = $wpdb->prepare(
            "SELECT plugin_version FROM {$this->table_name} 
            WHERE plugin_slug = %s AND action IN ('installed', 'updated') 
            ORDER BY date_time DESC LIMIT 1",
            $plugin_slug
        );
        
        $prev_version = $wpdb->get_var( $query );
        return $prev_version;
    }

    /**
     * Log plugin activation
     *
     * @param string $plugin_file Plugin file path.
     * @param bool   $network_wide Whether the activation was network-wide.
     */
    public function log_plugin_activation( $plugin_file, $network_wide ) {
        // Check if plugin logging is enabled
        if ( ! $this->is_logging_enabled() ) {
            return;
        }
        
        if ( ! file_exists( WP_PLUGIN_DIR . '/' . $plugin_file ) ) {
            return;
        }
        
        $plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file, true, false );
        $plugin_slug = dirname( $plugin_file );
        
        $this->insert_log(
            $plugin_slug,
            $plugin_data['Name'],
            $plugin_data['Version'],
            null,
            'activated',
            array(
                'plugin_file'  => $plugin_file,
                'network_wide' => $network_wide,
                'author'       => $plugin_data['Author'],
                'url'          => $plugin_data['PluginURI']
            )
        );
    }

    /**
     * Log plugin deactivation
     *
     * @param string $plugin_file Plugin file path.
     */
    public function log_plugin_deactivation( $plugin_file ) {
        // Check if plugin logging is enabled
        if ( ! $this->is_logging_enabled() ) {
            return;
        }
        
        if ( ! file_exists( WP_PLUGIN_DIR . '/' . $plugin_file ) ) {
            return;
        }
        
        $plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file, true, false );
        $plugin_slug = dirname( $plugin_file );
        
        $this->insert_log(
            $plugin_slug,
            $plugin_data['Name'],
            $plugin_data['Version'],
            null,
            'deactivated',
            array(
                'plugin_file' => $plugin_file,
                'author'      => $plugin_data['Author'],
                'url'         => $plugin_data['PluginURI']
            )
        );
    }

    /**
     * Store plugin data before deletion
     *
     * @param string $plugin_file Plugin file path.
     */
    public function store_plugin_data_before_deletion( $plugin_file ) {
        // Check if plugin logging is enabled
        if ( ! $this->is_logging_enabled() ) {
            return;
        }
        
        if ( ! file_exists( WP_PLUGIN_DIR . '/' . $plugin_file ) ) {
            return;
        }
        
        $this->plugins_data[ $plugin_file ] = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file, true, false );
    }

    /**
     * Log plugin deletion
     *
     * @param string $plugin_file Plugin file path.
     * @param bool   $deleted Whether the plugin was deleted.
     */
    public function log_plugin_deletion( $plugin_file, $deleted ) {
        // Check if plugin logging is enabled
        if ( ! $this->is_logging_enabled() ) {
            return;
        }
        
        if ( ! $deleted || empty( $this->plugins_data[ $plugin_file ] ) ) {
            return;
        }
        
        $plugin_data = $this->plugins_data[ $plugin_file ];
        $plugin_slug = dirname( $plugin_file );
        
        $this->insert_log(
            $plugin_slug,
            $plugin_data['Name'],
            $plugin_data['Version'],
            null,
            'deleted',
            array(
                'plugin_file' => $plugin_file,
                'author'      => $plugin_data['Author'],
                'url'         => $plugin_data['PluginURI']
            )
        );
    }

    /**
     * Insert a log entry into the database
     *
     * @param string $plugin_slug Plugin slug.
     * @param string $plugin_name Plugin name.
     * @param string $plugin_version Plugin version.
     * @param string $plugin_prev_version Plugin previous version.
     * @param string $action Action performed.
     * @param array  $additional_data Additional data about the plugin.
     * @return int|false The number of rows inserted, or false on error.
     */
    private function insert_log( $plugin_slug, $plugin_name, $plugin_version, $plugin_prev_version, $action, $additional_data = array() ) {
        global $wpdb;
        
        $user_id = get_current_user_id();
        $user = get_userdata( $user_id );
        $user_login = $user ? $user->user_login : '';
        
        // JavaScript console logging for debugging
        if ( class_exists( 'MpaiLogger' ) && function_exists( 'wp_json_encode' ) ) {
            // Generate log message for developer console via mpaiLogger
            $js_log = "
            if (window.mpaiLogger) {
                mpaiLogger.info('Plugin " . esc_js( $action ) . ": " . esc_js( $plugin_name ) . " v" . esc_js( $plugin_version ) . "', 'plugin_manager', " . wp_json_encode( $additional_data ) . ");
            }
            ";
            
            // Add to admin footer scripts
            add_action( 'admin_footer', function () use ( $js_log ) {
                echo '<script>' . $js_log . '</script>';
            }, 99 );
        }
        
        // Write to PHP error log as well
        error_log( sprintf( 
            'MPAI: Plugin %s: %s v%s by user %s', 
            $action, 
            $plugin_name, 
            $plugin_version, 
            $user_login 
        ));
        
        return $wpdb->insert(
            $this->table_name,
            array(
                'plugin_slug'        => $plugin_slug,
                'plugin_name'        => $plugin_name,
                'plugin_version'     => $plugin_version,
                'plugin_prev_version' => $plugin_prev_version,
                'action'             => $action,
                'user_id'            => $user_id,
                'user_login'         => $user_login,
                'date_time'          => current_time( 'mysql' ),
                'additional_data'    => wp_json_encode( $additional_data )
            ),
            array( '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s' )
        );
    }

    /**
     * Get logs from the database
     *
     * @param array $args Query arguments.
     * @return array Log entries.
     */
    public function get_logs( $args = array() ) {
        global $wpdb;
        
        $defaults = array(
            'plugin_slug'  => '',
            'plugin_name'  => '',
            'action'       => '',
            'user_id'      => 0,
            'date_from'    => '',
            'date_to'      => '',
            'limit'        => 100,
            'offset'       => 0,
            'orderby'      => 'date_time',
            'order'        => 'DESC',
        );
        
        $args = wp_parse_args( $args, $defaults );
        
        $where = 'WHERE 1=1';
        $prepare_args = array();
        
        // Add filters to query
        if ( ! empty( $args['plugin_slug'] ) ) {
            $where .= ' AND plugin_slug = %s';
            $prepare_args[] = $args['plugin_slug'];
        }
        
        if ( ! empty( $args['plugin_name'] ) ) {
            $where .= ' AND plugin_name LIKE %s';
            $prepare_args[] = '%' . $wpdb->esc_like( $args['plugin_name'] ) . '%';
        }
        
        if ( ! empty( $args['action'] ) ) {
            $where .= ' AND action = %s';
            $prepare_args[] = $args['action'];
        }
        
        if ( ! empty( $args['user_id'] ) ) {
            $where .= ' AND user_id = %d';
            $prepare_args[] = $args['user_id'];
        }
        
        if ( ! empty( $args['date_from'] ) ) {
            $where .= ' AND date_time >= %s';
            $prepare_args[] = $args['date_from'];
        }
        
        if ( ! empty( $args['date_to'] ) ) {
            $where .= ' AND date_time <= %s';
            $prepare_args[] = $args['date_to'];
        }
        
        // Sanitize orderby field
        $allowed_fields = array( 'id', 'plugin_name', 'plugin_slug', 'plugin_version', 'action', 'user_id', 'date_time' );
        $orderby = in_array( $args['orderby'], $allowed_fields ) ? $args['orderby'] : 'date_time';
        
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
        
        // Process results
        foreach ( $results as &$result ) {
            if ( ! empty( $result['additional_data'] ) ) {
                $result['additional_data'] = json_decode( $result['additional_data'], true );
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
    }

    /**
     * Count the number of logs matching criteria
     *
     * @param array $args Query arguments.
     * @return int Number of matching logs.
     */
    public function count_logs( $args = array() ) {
        global $wpdb;
        
        $defaults = array(
            'plugin_slug' => '',
            'plugin_name' => '',
            'action'      => '',
            'user_id'     => 0,
            'date_from'   => '',
            'date_to'     => '',
        );
        
        $args = wp_parse_args( $args, $defaults );
        
        $where = 'WHERE 1=1';
        $prepare_args = array();
        
        // Add filters to query
        if ( ! empty( $args['plugin_slug'] ) ) {
            $where .= ' AND plugin_slug = %s';
            $prepare_args[] = $args['plugin_slug'];
        }
        
        if ( ! empty( $args['plugin_name'] ) ) {
            $where .= ' AND plugin_name LIKE %s';
            $prepare_args[] = '%' . $wpdb->esc_like( $args['plugin_name'] ) . '%';
        }
        
        if ( ! empty( $args['action'] ) ) {
            $where .= ' AND action = %s';
            $prepare_args[] = $args['action'];
        }
        
        if ( ! empty( $args['user_id'] ) ) {
            $where .= ' AND user_id = %d';
            $prepare_args[] = $args['user_id'];
        }
        
        if ( ! empty( $args['date_from'] ) ) {
            $where .= ' AND date_time >= %s';
            $prepare_args[] = $args['date_from'];
        }
        
        if ( ! empty( $args['date_to'] ) ) {
            $where .= ' AND date_time <= %s';
            $prepare_args[] = $args['date_to'];
        }
        
        // Prepare final query
        $query = "SELECT COUNT(*) FROM {$this->table_name} {$where}";
        
        if ( ! empty( $prepare_args ) ) {
            $query = $wpdb->prepare( $query, $prepare_args );
        }
        
        // Execute query
        return (int) $wpdb->get_var( $query );
    }

    /**
     * Clean up old logs
     *
     * @return int|false Number of deleted rows or false on error.
     */
    public function cleanup_old_logs() {
        global $wpdb;
        
        $days_to_keep = get_option( 'mpai_plugin_logs_retention_days', 90 );
        
        // Don't delete logs if retention is set to 0 (keep forever)
        if ( $days_to_keep <= 0 ) {
            return 0;
        }
        
        $date = date( 'Y-m-d H:i:s', strtotime( "-{$days_to_keep} days" ) );
        
        $result = $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$this->table_name} WHERE date_time < %s",
            $date
        ) );
        
        if ( $result !== false ) {
            error_log( "MPAI: Cleaned up {$result} old plugin log entries (older than {$days_to_keep} days)" );
        } else {
            error_log( "MPAI: Error cleaning up old plugin logs" );
        }
        
        return $result;
    }

    /**
     * Check if plugin logging is enabled
     *
     * @return bool Whether logging is enabled.
     */
    private function is_logging_enabled() {
        return get_option( 'mpai_enable_plugin_logging', true );
    }

    /**
     * Get all unique plugin names in the log
     *
     * @return array List of plugin names.
     */
    public function get_unique_plugin_names() {
        global $wpdb;
        
        return $wpdb->get_col( "SELECT DISTINCT plugin_name FROM {$this->table_name} ORDER BY plugin_name ASC" );
    }

    /**
     * Get a summary of plugin activity
     *
     * @param int $days Number of days to include in summary.
     * @return array Summary data.
     */
    public function get_activity_summary( $days = 30 ) {
        global $wpdb;
        
        $date = date( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
        
        // Get count by action type
        $action_counts = $wpdb->get_results( $wpdb->prepare(
            "SELECT action, COUNT(*) as count 
            FROM {$this->table_name} 
            WHERE date_time >= %s 
            GROUP BY action",
            $date
        ), ARRAY_A );
        
        // Get count by day
        $daily_counts = $wpdb->get_results( $wpdb->prepare(
            "SELECT DATE(date_time) as date, COUNT(*) as count 
            FROM {$this->table_name} 
            WHERE date_time >= %s 
            GROUP BY DATE(date_time) 
            ORDER BY date ASC",
            $date
        ), ARRAY_A );
        
        // Get most active plugins
        $most_active_plugins = $wpdb->get_results( $wpdb->prepare(
            "SELECT plugin_name, COUNT(*) as count 
            FROM {$this->table_name} 
            WHERE date_time >= %s 
            GROUP BY plugin_name 
            ORDER BY count DESC 
            LIMIT 10",
            $date
        ), ARRAY_A );
        
        // Get most recent activity
        $recent_activity = $this->get_logs( array(
            'limit'     => 10,
            'date_from' => $date,
            'orderby'   => 'date_time',
            'order'     => 'DESC',
        ) );
        
        return array(
            'action_counts'        => $action_counts,
            'daily_counts'         => $daily_counts,
            'most_active_plugins'  => $most_active_plugins,
            'recent_activity'      => $recent_activity,
        );
    }

    /**
     * Export logs to CSV
     *
     * @param array $args Query arguments.
     * @return string CSV data.
     */
    public function export_csv( $args = array() ) {
        $logs = $this->get_logs( $args );
        
        if ( empty( $logs ) ) {
            return 'No logs found matching your criteria.';
        }
        
        // Start output buffering to capture CSV data
        ob_start();
        
        // Create CSV header
        $csv_headers = array(
            'ID',
            'Date/Time',
            'Action',
            'Plugin Name',
            'Plugin Slug',
            'Version',
            'Previous Version',
            'User',
        );
        
        $output = fopen( 'php://output', 'w' );
        fputcsv( $output, $csv_headers );
        
        // Add data rows
        foreach ( $logs as $log ) {
            $user_name = '';
            if ( isset( $log['user_info'] ) && ! empty( $log['user_info']['display_name'] ) ) {
                $user_name = $log['user_info']['display_name'] . ' (' . $log['user_login'] . ')';
            } else {
                $user_name = $log['user_login'];
            }
            
            $row = array(
                $log['id'],
                $log['date_time'],
                ucfirst( $log['action'] ),
                $log['plugin_name'],
                $log['plugin_slug'],
                $log['plugin_version'],
                $log['plugin_prev_version'],
                $user_name,
            );
            
            fputcsv( $output, $row );
        }
        
        fclose( $output );
        
        // Get and return the CSV data
        return ob_get_clean();
    }
}

// Initialize the plugin logger
function mpai_init_plugin_logger() {
    return MPAI_Plugin_Logger::get_instance();
}