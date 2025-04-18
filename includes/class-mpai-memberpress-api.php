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
     * Whether MemberPress is available
     *
     * @var bool
     */
    private $has_memberpress;

    /**
     * Constructor
     */
    public function __construct() {
        $this->api_key = get_option('mpai_memberpress_api_key', '');
        $this->base_url = site_url('/wp-json/mp/v1/');
        $this->has_memberpress = mpai_is_memberpress_active();
    }
    
    /**
     * Check if MemberPress is available
     *
     * @return bool
     */
    public function is_memberpress_available() {
        // Always use the central detection system
        return mpai_is_memberpress_active();
    }
    
    /**
     * Generate upsell response for when MemberPress is not available
     *
     * @param string $feature The MemberPress feature being requested
     * @return array Response with upsell message
     */
    private function get_upsell_response($feature) {
        return array(
            'status' => 'memberpress_not_available',
            'message' => sprintf(
                __('This feature requires MemberPress to be installed and activated. %sLearn more about MemberPress%s', 'memberpress-ai-assistant'),
                '<a href="https://memberpress.com/plans/?utm_source=ai_assistant&utm_medium=api&utm_campaign=upsell" target="_blank">',
                '</a>'
            ),
            'feature' => $feature,
            'memberpress_url' => 'https://memberpress.com/plans/?utm_source=ai_assistant&utm_medium=api&utm_campaign=upsell'
        );
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
        // Check if MemberPress is available using the central detector
        if (!mpai_is_memberpress_active()) {
            return $this->get_upsell_response($endpoint);
        }
        
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
            mpai_log_warning('Failed to get new members from API, using database fallback', 'memberpress-api');
            
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
     * @param bool $formatted Whether to return formatted tabular data
     * @return array|WP_Error|string The subscriptions or error
     */
    public function get_subscriptions($params = array(), $formatted = false) {
        $result = $this->get_subscriptions_from_db($params);
        
        if ($formatted && !is_wp_error($result)) {
            return $this->format_subscriptions_as_table($result);
        }
        
        return $result;
    }
    
    /**
     * Get active subscriptions
     *
     * @param array $params Query parameters
     * @param bool $formatted Whether to return formatted tabular data
     * @return array|WP_Error|string The active subscriptions or error
     */
    public function get_active_subscriptions($params = array(), $formatted = false) {
        $params['status'] = 'active';
        return $this->get_subscriptions($params, $formatted);
    }
    
    /**
     * Format subscriptions data as a tab-separated table
     *
     * @param array $subscriptions The subscriptions data
     * @return string Formatted tabular data
     */
    private function format_subscriptions_as_table($subscriptions) {
        if (empty($subscriptions) || !is_array($subscriptions)) {
            return "ID\tUser\tMembership\tPrice\tStatus\tCreated Date\nNo subscriptions found.";
        }
        
        $output = "ID\tUser\tMembership\tPrice\tStatus\tCreated Date\n";
        
        foreach ($subscriptions as $sub) {
            $id = isset($sub['id']) ? $sub['id'] : 'N/A';
            $user = isset($sub['username']) ? $sub['username'] : 'N/A';
            $membership = isset($sub['product_title']) ? $sub['product_title'] : 'N/A';
            $price = isset($sub['price']) ? '$' . $sub['price'] : 'N/A';
            $status = isset($sub['status']) ? $sub['status'] : 'N/A';
            $created = isset($sub['created_at']) ? date('Y-m-d', strtotime($sub['created_at'])) : 'N/A';
            
            $output .= "$id\t$user\t$membership\t$price\t$status\t$created\n";
        }
        
        return $output;
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
     * Get the best-selling membership
     * 
     * @param array $params Optional parameters (e.g., date range)
     * @param bool $formatted Whether to return formatted tabular data
     * @return array|string The best-selling membership data or formatted string
     */
    public function get_best_selling_membership($params = array(), $formatted = false) {
        // Check if MemberPress is available
        if (!$this->has_memberpress) {
            if ($formatted) {
                return __("MemberPress is not installed. Install MemberPress to access best-selling membership data and analytics.", 'memberpress-ai-assistant');
            }
            return $this->get_upsell_response('best_selling_membership');
        }
        
        global $wpdb;
        
        try {
            // Get the transactions table name
            $table_name = $wpdb->prefix . 'mepr_transactions';
            
            // Check if the table exists
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
            if (!$table_exists) {
                mpai_log_warning('mepr_transactions table does not exist', 'memberpress-api');
                return $this->get_fallback_membership_data($formatted);
            }
            
            // Check if the table has any records
            $record_count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} LIMIT 1");
            if (empty($record_count) || $record_count == 0) {
                mpai_log_debug('mepr_transactions table exists but is empty', 'memberpress-api');
                return $this->get_fallback_membership_data($formatted);
            }
            
            // Build query to count sales by product
            $query = "SELECT product_id, COUNT(*) as sale_count 
                      FROM {$table_name} 
                      WHERE status IN ('complete', 'confirmed')";
                      
            $query_args = array();
                      
            // Add date range filtering if provided
            if (!empty($params['start_date'])) {
                $query .= " AND created_at >= %s";
                $query_args[] = $params['start_date'];
            }
            
            if (!empty($params['end_date'])) {
                $query .= " AND created_at <= %s";
                $query_args[] = $params['end_date'];
            }
            
            $query .= " GROUP BY product_id ORDER BY sale_count DESC LIMIT 5";
            
            // Execute the query
            if (!empty($query_args)) {
                $best_sellers = $wpdb->get_results($wpdb->prepare($query, $query_args));
            } else {
                $best_sellers = $wpdb->get_results($query);
            }
            
            if (empty($best_sellers)) {
                mpai_log_debug('No completed transactions found in mepr_transactions table', 'memberpress-api');
                return $this->get_fallback_membership_data($formatted);
            }
            
            // Format the results
            $results = array();
            foreach ($best_sellers as $index => $seller) {
                // Get product details
                $product = get_post($seller->product_id);
                $product_title = $product ? $product->post_title : "Product #{$seller->product_id}";
                
                // Get price
                $price = get_post_meta($seller->product_id, '_mepr_product_price', true);
                
                $results[] = array(
                    'rank' => $index + 1,
                    'product_id' => $seller->product_id,
                    'product_title' => $product_title,
                    'sale_count' => $seller->sale_count,
                    'price' => $price
                );
            }
            
            // If formatted output is requested
            if ($formatted) {
                $output = "Best-Selling Memberships:\n\n";
                $output .= "Rank\tTitle\tSales\tPrice\n";
                
                foreach ($results as $result) {
                    $rank = $result['rank'];
                    $title = $result['product_title'];
                    $sales = $result['sale_count'];
                    $price = isset($result['price']) ? '$' . $result['price'] : 'N/A';
                    
                    $output .= "{$rank}\t{$title}\t{$sales}\t{$price}\n";
                }
                
                return $output;
            }
            
            return $results;
        } catch (Exception $e) {
            mpai_log_error('Error in get_best_selling_membership: ' . $e->getMessage(), 'memberpress-api', array(
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ));
            return $this->get_fallback_membership_data($formatted);
        }
    }
    
    /**
     * Get fallback membership data for best-selling when transaction data is unavailable
     * 
     * @param bool $formatted Whether to return formatted data
     * @return array|string Fallback data
     */
    private function get_fallback_membership_data($formatted = false) {
        // Get all membership products
        $args = array(
            'post_type' => 'memberpressproduct',
            'posts_per_page' => 10,  // Increased to get more memberships
            'post_status' => 'publish'
        );
        
        $memberships = get_posts($args);
        
        if (empty($memberships)) {
            if ($formatted) {
                return "No membership data available. Transaction history and membership products could not be found.";
            }
            return array();
        }
        
        // Create sample data with more realistic random numbers
        $results = array();
        foreach ($memberships as $index => $membership) {
            // Get price
            $price = get_post_meta($membership->ID, '_mepr_product_price', true);
            
            // Generate a random number for sample data with wider range and more variation
            // Generate more spread out numbers to better differentiate best sellers
            $sample_sales = rand(10, 500); 
            
            // Use post date to influence randomness - newer products might have fewer sales
            $post_date = strtotime($membership->post_date);
            $days_old = (time() - $post_date) / (60 * 60 * 24);
            // Adjust sales based on age - newer products might have lower sales numbers
            $sales_adjustment = min($days_old / 30, 5); // Up to 5x multiplier for older products
            $sample_sales = intval($sample_sales * (1 + $sales_adjustment / 10));
            
            $results[] = array(
                'rank' => $index + 1,
                'product_id' => $membership->ID,
                'product_title' => $membership->post_title,
                'sale_count' => $sample_sales . ' (sample data)',  // Indicate that this is sample data
                'price' => $price,
                '_raw_sales' => $sample_sales // Hidden value for sorting
            );
        }
        
        // Sort by the sample sales
        usort($results, function($a, $b) {
            $a_sales = isset($a['_raw_sales']) ? $a['_raw_sales'] : intval($a['sale_count']);
            $b_sales = isset($b['_raw_sales']) ? $b['_raw_sales'] : intval($b['sale_count']);
            return $b_sales - $a_sales;
        });
        
        // Update ranks after sorting and remove temporary _raw_sales field
        foreach ($results as $index => $result) {
            $results[$index]['rank'] = $index + 1;
            if (isset($results[$index]['_raw_sales'])) {
                unset($results[$index]['_raw_sales']);
            }
        }
        
        // Limit to top 5 best sellers after sorting
        $results = array_slice($results, 0, 5);
        
        // If formatted output is requested
        if ($formatted) {
            $output = "Best-Selling Membership Products (Sample Data - No Transaction History):\n\n";
            $output .= "Rank\tTitle\tSales\tPrice\n";
            
            foreach ($results as $result) {
                $rank = $result['rank'];
                $title = $result['product_title'];
                $sales = $result['sale_count'];
                $price = isset($result['price']) ? '$' . $result['price'] : 'N/A';
                
                $output .= "{$rank}\t{$title}\t{$sales}\t{$price}\n";
            }
            
            return $output;
        }
        
        return $results;
    }

    /**
     * Get summary of MemberPress data for AI context
     *
     * @return array MemberPress data summary
     */
    public function get_data_summary($force_refresh = false) {
        try {
            // Getting MemberPress data summary
            
            // Clear any cached data if forcing refresh
            if ($force_refresh) {
                // Clearing any cached MemberPress data
                global $wpdb;
                wp_cache_flush();
                
                // Clear internal WordPress cache for plugins
                wp_cache_delete('plugins', 'plugins');
                
                // Force get_plugins to reload
                if (function_exists('get_plugins')) {
                    get_plugins('', true); // Use true to refresh the cache
                }
                
                // Clear MemberPress detection cache
                mpai_memberpress_detector()->clear_detection_cache();
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
            
            // Check if MemberPress is active using the central detector
            if (!mpai_is_memberpress_active($force_refresh)) {
                // MemberPress is not active
                $summary['status'] = 'MemberPress is not active';
                return $summary;
            }
            
            // Check if API key is configured
            if (empty($this->api_key)) {
                // MemberPress API key is not configured, using fallback methods
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
                    
                    // Found members and memberships using fallback method
                } else {
                    // Cannot access MemberPress classes for fallback method
                }
                
                return $summary;
            }
            
            // Using MemberPress API to fetch data
            
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
                // Successfully retrieved members data
            } else {
                mpai_log_warning('Failed to retrieve members data', 'memberpress-api');
                if (is_wp_error($members)) {
                    mpai_log_error('Error retrieving members: ' . $members->get_error_message(), 'memberpress-api');
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
                // Successfully retrieved memberships data
            } else {
                mpai_log_warning('Failed to retrieve memberships data', 'memberpress-api');
                if (is_wp_error($memberships)) {
                    mpai_log_error('Error retrieving memberships: ' . $memberships->get_error_message(), 'memberpress-api');
                }
            }
    
            // Get summary of transactions
            $transactions = $this->get_transactions(array('per_page' => 5));
            if (!is_wp_error($transactions) && is_array($transactions)) {
                $summary['total_transactions'] = count($transactions);
                $summary['transaction_count'] = $summary['total_transactions'];
                // Successfully retrieved transactions data
            } else {
                mpai_log_warning('Failed to retrieve transactions data', 'memberpress-api');
                if (is_wp_error($transactions)) {
                    mpai_log_error('Error retrieving transactions: ' . $transactions->get_error_message(), 'memberpress-api');
                }
            }
    
            // Get summary of subscriptions
            $subscriptions = $this->get_subscriptions(array('per_page' => 5));
            if (!is_wp_error($subscriptions) && is_array($subscriptions)) {
                $summary['total_subscriptions'] = count($subscriptions);
                $summary['subscription_count'] = $summary['total_subscriptions'];
                // Successfully retrieved subscriptions data
            } else {
                mpai_log_warning('Failed to retrieve subscriptions data', 'memberpress-api');
                if (is_wp_error($subscriptions)) {
                    mpai_log_error('Error retrieving subscriptions: ' . $subscriptions->get_error_message(), 'memberpress-api');
                }
            }
    
            return $summary;
        } catch (Exception $e) {
            mpai_log_error('Error in MemberPress API get_data_summary: ' . $e->getMessage(), 'memberpress-api', array(
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ));
            
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