<?php
/**
 * MemberPress API Integration Class
 *
 * Handles integration with MemberPress REST API
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

class MPAI_MemberPress_API {
    /**
     * MemberPress API key
     *
     * @var string
     */
    private $api_key;

    /**
     * Base URL for API requests
     *
     * @var string
     */
    private $base_url;

    /**
     * Constructor
     */
    public function __construct() {
        $this->api_key = get_option('mpai_memberpress_api_key', '');
        $this->base_url = site_url('/wp-json/mp/v1/');
    }
    
    /**
     * Get the base URL for API requests
     *
     * @return string The base URL
     */
    public function get_base_url() {
        return $this->base_url;
    }

    /**
     * Make a request to the MemberPress data directly (not using REST API)
     *
     * @param string $endpoint The API endpoint concept (members, memberships, etc.)
     * @param string $method HTTP method concept (GET, POST, etc.) - used to determine action
     * @param array $data Data for filtering or creating
     * @return array|WP_Error The data response or error
     */
    public function request($endpoint, $method = 'GET', $data = array()) {
        // We don't use the REST API anymore - direct database access instead
        
        // Determine what data to fetch based on the endpoint
        switch ($endpoint) {
            case 'members':
            case 'users':
                return $this->get_members_from_db($data);
                
            case 'memberships':
            case 'products':
                return $this->get_memberships_from_db($data);
                
            case 'transactions':
                return $this->get_transactions_from_db($data);
                
            case 'subscriptions':
                return $this->get_subscriptions_from_db($data);
                
            default:
                return new WP_Error(
                    'invalid_endpoint',
                    'Invalid endpoint: ' . $endpoint,
                    array('endpoint' => $endpoint)
                );
        }
    }
    
    /**
     * Get members from the database directly
     *
     * @param array $params Query parameters
     * @return array Members data
     */
    private function get_members_from_db($params = array()) {
        global $wpdb;
        
        // Build query
        $query = "SELECT u.ID, u.user_login, u.user_email, u.display_name, u.user_registered 
                 FROM {$wpdb->users} u";
        
        $where_clauses = array();
        $query_args = array();
        
        // Filter by date if provided
        if (!empty($params['start_date'])) {
            $where_clauses[] = "u.user_registered >= %s";
            $query_args[] = $params['start_date'];
        }
        
        if (!empty($params['end_date'])) {
            $where_clauses[] = "u.user_registered <= %s";
            $query_args[] = $params['end_date'];
        }
        
        // Add where clauses if any
        if (!empty($where_clauses)) {
            $query .= " WHERE " . implode(" AND ", $where_clauses);
        }
        
        // Add ordering
        $query .= " ORDER BY u.user_registered DESC";
        
        // Add limit
        $limit = isset($params['per_page']) ? intval($params['per_page']) : 20;
        $query .= " LIMIT %d";
        $query_args[] = $limit;
        
        // Execute the query
        $users = $wpdb->get_results($wpdb->prepare($query, $query_args));
        
        // Format the results similar to the API response
        $members = array();
        foreach ($users as $user) {
            $members[] = array(
                'id' => $user->ID,
                'username' => $user->user_login,
                'email' => $user->user_email,
                'display_name' => $user->display_name,
                'registered' => $user->user_registered
            );
        }
        
        return $members;
    }

    /**
     * Get members directly from WordPress database
     *
     * @param array $params Query parameters
     * @param bool $formatted Whether to return formatted tabular data
     * @return array|WP_Error|string The members or error
     */
    public function get_members($params = array(), $formatted = false) {
        $result = $this->get_members_from_db($params);
        
        if ($formatted && !is_wp_error($result)) {
            return $this->format_members_as_table($result);
        }
        
        return $result;
    }
    
    /**
     * Get memberships from the database directly
     *
     * @param array $params Query parameters
     * @return array Memberships data
     */
    private function get_memberships_from_db($params = array()) {
        // Use standard WordPress functions to get MemberPress products
        $args = array(
            'post_type' => 'memberpressproduct',
            'posts_per_page' => isset($params['per_page']) ? intval($params['per_page']) : -1,
            'post_status' => 'publish'
        );
        
        $products = get_posts($args);
        $memberships = array();
        
        foreach ($products as $product) {
            // Get MemberPress specific data if possible
            if (class_exists('MeprProduct')) {
                $mepr_product = new MeprProduct($product->ID);
                $memberships[] = array(
                    'id' => $product->ID,
                    'title' => $product->post_title,
                    'description' => $product->post_excerpt,
                    'price' => $mepr_product->price,
                    'period' => $mepr_product->period,
                    'period_type' => $mepr_product->period_type,
                    'status' => $product->post_status,
                    'created_at' => $product->post_date
                );
            } else {
                // Fallback to post meta
                $price = get_post_meta($product->ID, '_mepr_product_price', true);
                $period = get_post_meta($product->ID, '_mepr_product_period', true);
                $period_type = get_post_meta($product->ID, '_mepr_product_period_type', true);
                
                $memberships[] = array(
                    'id' => $product->ID,
                    'title' => $product->post_title,
                    'description' => $product->post_excerpt,
                    'price' => $price,
                    'period' => $period,
                    'period_type' => $period_type,
                    'status' => $product->post_status,
                    'created_at' => $product->post_date
                );
            }
        }
        
        return $memberships;
    }
    
    /**
     * Get transactions from the database directly
     *
     * @param array $params Query parameters
     * @return array Transactions data
     */
    private function get_transactions_from_db($params = array()) {
        global $wpdb;
        
        // Check if MemberPress is active and we can access its tables
        if (!class_exists('MeprDb')) {
            // Try to include MemberPress files
            if (file_exists(WP_PLUGIN_DIR . '/memberpress/memberpress.php')) {
                include_once(WP_PLUGIN_DIR . '/memberpress/app/lib/MeprDb.php');
            }
        }
        
        // Get table name directly if MeprDb is not available
        $table_name = $wpdb->prefix . 'mepr_transactions';
        
        // Check if the table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
        
        if (!$table_exists) {
            return array(); // Return empty array if table doesn't exist
        }
        
        // Build query
        $limit = isset($params['per_page']) ? intval($params['per_page']) : 20;
        
        $query = "SELECT * FROM {$table_name} ORDER BY created_at DESC LIMIT %d";
        $transactions = $wpdb->get_results($wpdb->prepare($query, $limit));
        
        $results = array();
        foreach ($transactions as $txn) {
            // Get user info
            $user = get_userdata($txn->user_id);
            $username = $user ? $user->user_email : "User #{$txn->user_id}";
            
            // Get product info
            $product = get_post($txn->product_id);
            $product_title = $product ? $product->post_title : "Product #{$txn->product_id}";
            
            $results[] = array(
                'id' => $txn->id,
                'user_id' => $txn->user_id,
                'username' => $username,
                'product_id' => $txn->product_id,
                'product_title' => $product_title,
                'amount' => $txn->amount,
                'total' => $txn->total,
                'status' => $txn->status,
                'created_at' => $txn->created_at
            );
        }
        
        return $results;
    }
    
    /**
     * Get subscriptions from the database directly
     *
     * @param array $params Query parameters
     * @return array Subscriptions data
     */
    private function get_subscriptions_from_db($params = array()) {
        global $wpdb;
        
        // Get table name
        $table_name = $wpdb->prefix . 'mepr_subscriptions';
        
        // Check if the table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
        
        if (!$table_exists) {
            return array(); // Return empty array if table doesn't exist
        }
        
        // Build query
        $limit = isset($params['per_page']) ? intval($params['per_page']) : 20;
        $status = isset($params['status']) && $params['status'] !== 'all' ? $params['status'] : null;
        
        $query = "SELECT * FROM {$table_name}";
        $query_args = array();
        
        if ($status) {
            $query .= " WHERE status = %s";
            $query_args[] = $status;
        }
        
        $query .= " ORDER BY created_at DESC LIMIT %d";
        $query_args[] = $limit;
        
        $subscriptions = $wpdb->get_results($wpdb->prepare($query, $query_args));
        
        $results = array();
        foreach ($subscriptions as $sub) {
            // Get user info
            $user = get_userdata($sub->user_id);
            $username = $user ? $user->user_email : "User #{$sub->user_id}";
            
            // Get product info
            $product = get_post($sub->product_id);
            $product_title = $product ? $product->post_title : "Product #{$sub->product_id}";
            
            $results[] = array(
                'id' => $sub->id,
                'user_id' => $sub->user_id,
                'username' => $username,
                'product_id' => $sub->product_id,
                'product_title' => $product_title,
                'status' => $sub->status,
                'created_at' => $sub->created_at
            );
        }
        
        return $results;
    }
    
    /**
     * Format members data as a tab-separated table
     *
     * @param array $members The members data
     * @return string Formatted tabular data
     */
    private function format_members_as_table($members) {
        if (empty($members) || !is_array($members)) {
            return "ID\tUsername\tEmail\tJoin Date\tStatus\nNo members found.";
        }
        
        $output = "ID\tUsername\tEmail\tJoin Date\tStatus\n";
        
        foreach ($members as $member) {
            $id = isset($member['id']) ? $member['id'] : 'N/A';
            $username = isset($member['username']) ? $member['username'] : 'N/A';
            $email = isset($member['email']) ? $member['email'] : 'N/A';
            $join_date = isset($member['registered']) ? date('Y-m-d', strtotime($member['registered'])) : 'N/A';
            $status = isset($member['status']) ? $member['status'] : 'active';
            
            $output .= "$id\t$username\t$email\t$join_date\t$status\n";
        }
        
        return $output;
    }
    
    /**
     * Get new members who joined in the current month
     *
     * @param bool $formatted Whether to return formatted tabular data
     * @return array|string The new members or formatted table
     */
    public function get_new_members_this_month($formatted = true) {
        // Calculate the first day of the current month
        $first_day_of_month = date('Y-m-01');
        
        // Set the params to filter by registration date
        $params = array(
            'start_date' => $first_day_of_month,
            'end_date' => date('Y-m-d'),
            'per_page' => 100 // Increase to get more members if needed
        );
        
        // Try to use the API to get members
        $new_members = $this->get_members($params);
        
        // If API fails, try direct database query as fallback
        if (is_wp_error($new_members) || !is_array($new_members)) {
            error_log('MPAI: Failed to get new members from API, using database fallback');
            
            global $wpdb;
            
            // Get all users registered this month
            $query = $wpdb->prepare(
                "SELECT ID, user_login, user_email, user_registered 
                FROM {$wpdb->users} 
                WHERE user_registered >= %s 
                ORDER BY user_registered DESC",
                $first_day_of_month
            );
            
            $users = $wpdb->get_results($query);
            
            // Format the result
            $new_members = array();
            foreach ($users as $user) {
                $new_members[] = array(
                    'id' => $user->ID,
                    'username' => $user->user_login,
                    'email' => $user->user_email,
                    'registered' => $user->user_registered,
                    'status' => 'active'
                );
            }
        }
        
        // If we want formatted output
        if ($formatted) {
            if (empty($new_members) || !is_array($new_members)) {
                return "No new members joined this month.";
            }
            
            $output = "New Members This Month (" . date('F Y') . "):\n\n";
            $output .= "ID\tUsername\tEmail\tJoin Date\n";
            
            foreach ($new_members as $member) {
                $id = isset($member['id']) ? $member['id'] : 'N/A';
                $username = isset($member['username']) ? $member['username'] : 'N/A';
                $email = isset($member['email']) ? $member['email'] : 'N/A';
                $join_date = isset($member['registered']) ? date('Y-m-d', strtotime($member['registered'])) : 'N/A';
                
                $output .= "$id\t$username\t$email\t$join_date\n";
            }
            
            $output .= "\nTotal New Members: " . count($new_members);
            return $output;
        }
        
        return array(
            'count' => count($new_members),
            'members' => $new_members,
            'period' => array(
                'start' => $first_day_of_month,
                'end' => date('Y-m-d'),
                'month' => date('F Y')
            )
        );
    }

    /**
     * Get a specific member
     *
     * @param int $member_id The member ID
     * @return array|WP_Error The member or error
     */
    public function get_member($member_id) {
        // Get user data directly
        $user = get_userdata($member_id);
        
        if (!$user) {
            return new WP_Error('member_not_found', 'Member not found');
        }
        
        return array(
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'display_name' => $user->display_name,
            'registered' => $user->user_registered
        );
    }

    /**
     * Create a member
     *
     * @param array $data Member data
     * @return array|WP_Error The created member or error
     */
    public function create_member($data) {
        return $this->request('members', 'POST', $data);
    }

    /**
     * Update a member
     *
     * @param int $member_id The member ID
     * @param array $data Member data
     * @return array|WP_Error The updated member or error
     */
    public function update_member($member_id, $data) {
        return $this->request("members/{$member_id}", 'PUT', $data);
    }

    /**
     * Get memberships directly from database 
     *
     * @param array $params Query parameters
     * @param bool $formatted Whether to return formatted tabular data
     * @return array|WP_Error|string The memberships or error
     */
    public function get_memberships($params = array(), $formatted = false) {
        $result = $this->get_memberships_from_db($params);
        
        if ($formatted && !is_wp_error($result)) {
            return $this->format_memberships_as_table($result);
        }
        
        return $result;
    }
    
    /**
     * Format memberships data as a tab-separated table
     *
     * @param array $memberships The memberships data
     * @return string Formatted tabular data
     */
    private function format_memberships_as_table($memberships) {
        if (empty($memberships) || !is_array($memberships)) {
            return "ID\tTitle\tPrice\tPeriod\nNo memberships found.";
        }
        
        $output = "ID\tTitle\tPrice\tPeriod\tStatus\n";
        
        foreach ($memberships as $membership) {
            $id = isset($membership['id']) ? $membership['id'] : 'N/A';
            $title = isset($membership['title']) ? $membership['title'] : 'Untitled';
            $price = isset($membership['price']) ? $membership['price'] : 'N/A';
            $period = '';
            if (isset($membership['period']) && isset($membership['period_type'])) {
                $period = $membership['period'] . ' ' . $membership['period_type'];
            } else if (isset($membership['period_type'])) {
                $period = $membership['period_type'];
            } else {
                $period = 'N/A';
            }
            $status = isset($membership['status']) ? $membership['status'] : 'active';
            
            $output .= "$id\t$title\t$price\t$period\t$status\n";
        }
        
        return $output;
    }

    /**
     * Get a specific membership
     *
     * @param int $membership_id The membership ID
     * @return array|WP_Error The membership or error
     */
    public function get_membership($membership_id) {
        // Get product directly
        $product = get_post($membership_id);
        
        if (!$product || $product->post_type !== 'memberpressproduct') {
            return new WP_Error('membership_not_found', 'Membership not found');
        }
        
        // Try to get MemberPress specific data
        if (class_exists('MeprProduct')) {
            $mepr_product = new MeprProduct($membership_id);
            return array(
                'id' => $product->ID,
                'title' => $product->post_title,
                'description' => $product->post_excerpt,
                'price' => $mepr_product->price,
                'period' => $mepr_product->period,
                'period_type' => $mepr_product->period_type,
                'status' => $product->post_status,
                'created_at' => $product->post_date
            );
        } else {
            // Fallback to post meta
            $price = get_post_meta($membership_id, '_mepr_product_price', true);
            $period = get_post_meta($membership_id, '_mepr_product_period', true);
            $period_type = get_post_meta($membership_id, '_mepr_product_period_type', true);
            
            return array(
                'id' => $product->ID,
                'title' => $product->post_title,
                'description' => $product->post_excerpt,
                'price' => $price,
                'period' => $period,
                'period_type' => $period_type,
                'status' => $product->post_status,
                'created_at' => $product->post_date
            );
        }
    }

    /**
     * Get transactions directly from database
     *
     * @param array $params Query parameters
     * @param bool $formatted Whether to return formatted tabular data
     * @return array|WP_Error|string The transactions or error
     */
    public function get_transactions($params = array(), $formatted = false) {
        $result = $this->get_transactions_from_db($params);
        
        if ($formatted && !is_wp_error($result)) {
            return $this->format_transactions_as_table($result);
        }
        
        return $result;
    }
    
    /**
     * Format transactions data as a tab-separated table
     *
     * @param array $transactions The transactions data
     * @return string Formatted tabular data
     */
    private function format_transactions_as_table($transactions) {
        if (empty($transactions) || !is_array($transactions)) {
            return "ID\tAmount\tStatus\tDate\tMembership\tUser\nNo transactions found.";
        }
        
        $output = "ID\tAmount\tStatus\tDate\tMembership\tUser\n";
        
        foreach ($transactions as $transaction) {
            $id = isset($transaction['id']) ? $transaction['id'] : 'N/A';
            $amount = isset($transaction['amount']) ? '$' . $transaction['amount'] : 'N/A';
            $status = isset($transaction['status']) ? $transaction['status'] : 'N/A';
            $date = isset($transaction['created_at']) ? date('Y-m-d', strtotime($transaction['created_at'])) : 'N/A';
            
            // Extract membership name
            $membership = 'N/A';
            if (isset($transaction['product']) && isset($transaction['product']['title'])) {
                $membership = $transaction['product']['title'];
            } elseif (isset($transaction['product_id'])) {
                $membership = 'ID: ' . $transaction['product_id'];
            }
            
            // Extract user info
            $user = 'N/A';
            if (isset($transaction['user']) && isset($transaction['user']['email'])) {
                $user = $transaction['user']['email'];
            } elseif (isset($transaction['user_id'])) {
                $user = 'ID: ' . $transaction['user_id'];
            }
            
            $output .= "$id\t$amount\t$status\t$date\t$membership\t$user\n";
        }
        
        return $output;
    }

    /**
     * Get a specific transaction
     *
     * @param int $transaction_id The transaction ID
     * @return array|WP_Error The transaction or error
     */
    public function get_transaction($transaction_id) {
        global $wpdb;
        
        // Get table name
        $table_name = $wpdb->prefix . 'mepr_transactions';
        
        // Get transaction
        $txn = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $transaction_id));
        
        if (!$txn) {
            return new WP_Error('transaction_not_found', 'Transaction not found');
        }
        
        // Get user info
        $user = get_userdata($txn->user_id);
        $username = $user ? $user->user_email : "User #{$txn->user_id}";
        
        // Get product info
        $product = get_post($txn->product_id);
        $product_title = $product ? $product->post_title : "Product #{$txn->product_id}";
        
        return array(
            'id' => $txn->id,
            'user_id' => $txn->user_id,
            'username' => $username,
            'product_id' => $txn->product_id,
            'product_title' => $product_title,
            'amount' => $txn->amount,
            'total' => $txn->total,
            'status' => $txn->status,
            'created_at' => $txn->created_at
        );
    }

    /**
     * Get subscriptions directly from database
     *
     * @param array $params Query parameters
     * @return array|WP_Error The subscriptions or error
     */
    public function get_subscriptions($params = array()) {
        return $this->get_subscriptions_from_db($params);
    }

    /**
     * Get a specific subscription
     *
     * @param int $subscription_id The subscription ID
     * @return array|WP_Error The subscription or error
     */
    public function get_subscription($subscription_id) {
        global $wpdb;
        
        // Get table name
        $table_name = $wpdb->prefix . 'mepr_subscriptions';
        
        // Get subscription
        $sub = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $subscription_id));
        
        if (!$sub) {
            return new WP_Error('subscription_not_found', 'Subscription not found');
        }
        
        // Get user info
        $user = get_userdata($sub->user_id);
        $username = $user ? $user->user_email : "User #{$sub->user_id}";
        
        // Get product info
        $product = get_post($sub->product_id);
        $product_title = $product ? $product->post_title : "Product #{$sub->product_id}";
        
        return array(
            'id' => $sub->id,
            'user_id' => $sub->user_id,
            'username' => $username,
            'product_id' => $sub->product_id,
            'product_title' => $product_title,
            'status' => $sub->status,
            'created_at' => $sub->created_at
        );
    }

    /**
     * Get events from the database
     *
     * @param array $params Query parameters
     * @return array|WP_Error The events or error
     */
    public function get_events($params = array()) {
        // Since events may not have a dedicated table, we combine recent actions
        $events = array();
        
        // Get recent transactions
        $transactions = $this->get_transactions_from_db(array('per_page' => 10));
        foreach ($transactions as $txn) {
            $events[] = array(
                'type' => 'transaction',
                'id' => $txn['id'],
                'date' => $txn['created_at'],
                'user' => $txn['username'],
                'product' => $txn['product_title'],
                'amount' => $txn['amount'],
                'status' => $txn['status']
            );
        }
        
        // Get recent subscriptions
        $subscriptions = $this->get_subscriptions_from_db(array('per_page' => 10));
        foreach ($subscriptions as $sub) {
            $events[] = array(
                'type' => 'subscription',
                'id' => $sub['id'],
                'date' => $sub['created_at'],
                'user' => $sub['username'],
                'product' => $sub['product_title'],
                'status' => $sub['status']
            );
        }
        
        // Sort by date (most recent first)
        usort($events, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        // Apply limit if specified
        if (!empty($params['per_page'])) {
            $events = array_slice($events, 0, intval($params['per_page']));
        }
        
        return $events;
    }

    /**
     * Get summary of MemberPress data for AI context
     *
     * @return array MemberPress data summary
     */
    public function get_data_summary($force_refresh = false) {
        try {
            error_log('MPAI: Getting MemberPress data summary' . ($force_refresh ? ' (forced refresh)' : ''));
            
            // Clear any cached data if forcing refresh
            if ($force_refresh) {
                error_log('MPAI: Clearing any cached MemberPress data');
                global $wpdb;
                wp_cache_flush();
                
                // Clear internal WordPress cache for plugins
                wp_cache_delete('plugins', 'plugins');
                
                // Force get_plugins to reload
                if (function_exists('get_plugins')) {
                    get_plugins('', true); // Use true to refresh the cache
                }
            }
            
            // Initialize with default values
            $summary = array(
                'members' => array(),
                'memberships' => array(),
                'transactions' => array(),
                'subscriptions' => array(),
                'total_members' => 0,
                'total_memberships' => 0,
                'transaction_count' => 0,
                'subscription_count' => 0
            );
            
            // Check if MemberPress is active
            if (!class_exists('MeprAppCtrl')) {
                error_log('MPAI: MemberPress is not active');
                $summary['status'] = 'MemberPress is not active';
                return $summary;
            }
            
            // Check if API key is configured
            if (empty($this->api_key)) {
                error_log('MPAI: MemberPress API key is not configured, using fallback methods');
                $summary['status'] = 'MemberPress API key is not configured';
                
                // Try to use direct database queries as fallback
                if (class_exists('MeprUser') && class_exists('MeprProduct')) {
                    // Get member count from database
                    global $wpdb;
                    $user_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}");
                    $summary['total_members'] = $user_count;
                    
                    // Get membership count
                    $products = get_posts(array(
                        'post_type' => 'memberpressproduct',
                        'numberposts' => -1,
                        'post_status' => 'publish'
                    ));
                    $summary['total_memberships'] = count($products);
                    
                    // Get some basic membership info
                    foreach ($products as $product) {
                        $summary['memberships'][] = array(
                            'id' => $product->ID,
                            'title' => $product->post_title,
                            'price' => get_post_meta($product->ID, '_mepr_product_price', true),
                        );
                    }
                    
                    error_log('MPAI: Found ' . $summary['total_members'] . ' members and ' . $summary['total_memberships'] . ' memberships using fallback method');
                } else {
                    error_log('MPAI: Cannot access MemberPress classes for fallback method');
                }
                
                return $summary;
            }
            
            error_log('MPAI: Using MemberPress API to fetch data');
            
            // Get summary of members
            $members = $this->get_members(array('per_page' => 5));
            if (!is_wp_error($members) && is_array($members)) {
                $summary['total_members'] = count($members);
                foreach ($members as $member) {
                    $summary['members'][] = array(
                        'id' => isset($member['id']) ? $member['id'] : 0,
                        'email' => isset($member['email']) ? $member['email'] : '',
                        'name' => (isset($member['first_name']) ? $member['first_name'] : '') . ' ' . 
                                 (isset($member['last_name']) ? $member['last_name'] : ''),
                    );
                }
                error_log('MPAI: Successfully retrieved members data');
            } else {
                error_log('MPAI: Failed to retrieve members data');
                if (is_wp_error($members)) {
                    error_log('MPAI: Error: ' . $members->get_error_message());
                }
            }
    
            // Get summary of memberships
            $memberships = $this->get_memberships(array('per_page' => 10));
            if (!is_wp_error($memberships) && is_array($memberships)) {
                $summary['total_memberships'] = count($memberships);
                foreach ($memberships as $membership) {
                    $summary['memberships'][] = array(
                        'id' => isset($membership['id']) ? $membership['id'] : 0,
                        'title' => isset($membership['title']) ? $membership['title'] : '',
                        'price' => isset($membership['price']) ? $membership['price'] : '',
                        'period_type' => isset($membership['period_type']) ? $membership['period_type'] : '',
                    );
                }
                error_log('MPAI: Successfully retrieved memberships data');
            } else {
                error_log('MPAI: Failed to retrieve memberships data');
                if (is_wp_error($memberships)) {
                    error_log('MPAI: Error: ' . $memberships->get_error_message());
                }
            }
    
            // Get summary of transactions
            $transactions = $this->get_transactions(array('per_page' => 5));
            if (!is_wp_error($transactions) && is_array($transactions)) {
                $summary['total_transactions'] = count($transactions);
                $summary['transaction_count'] = $summary['total_transactions'];
                error_log('MPAI: Successfully retrieved transactions data');
            } else {
                error_log('MPAI: Failed to retrieve transactions data');
                if (is_wp_error($transactions)) {
                    error_log('MPAI: Error: ' . $transactions->get_error_message());
                }
            }
    
            // Get summary of subscriptions
            $subscriptions = $this->get_subscriptions(array('per_page' => 5));
            if (!is_wp_error($subscriptions) && is_array($subscriptions)) {
                $summary['total_subscriptions'] = count($subscriptions);
                $summary['subscription_count'] = $summary['total_subscriptions'];
                error_log('MPAI: Successfully retrieved subscriptions data');
            } else {
                error_log('MPAI: Failed to retrieve subscriptions data');
                if (is_wp_error($subscriptions)) {
                    error_log('MPAI: Error: ' . $subscriptions->get_error_message());
                }
            }
    
            return $summary;
        } catch (Exception $e) {
            error_log('MPAI: Error in MemberPress API get_data_summary: ' . $e->getMessage());
            
            // Return minimal data summary on error
            return array(
                'members' => array(),
                'memberships' => array(),
                'transactions' => array(),
                'subscriptions' => array(),
                'total_members' => 0,
                'total_memberships' => 0,
                'transaction_count' => 0,
                'subscription_count' => 0,
                'status' => 'Error getting MemberPress data',
                'error' => $e->getMessage()
            );
        }
    }
}