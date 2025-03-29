<?php
/**
 * MemberPress Tool Class
 *
 * Provides access to MemberPress functionality.
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * MemberPress Tool for MemberPress AI Assistant
 */
class MPAI_MemberPress_Tool extends MPAI_Base_Tool {
    /**
     * Constructor
     */
    public function __construct() {
        $this->name = 'MemberPress Tool';
        $this->description = 'Provides access to MemberPress functionality';
    }
    
    /**
     * Execute the tool
     *
     * @param array $parameters Tool parameters
     * @return mixed Execution result
     */
    public function execute($parameters) {
        // Check for required parameters
        if (!isset($parameters['action'])) {
            throw new Exception('The action parameter is required');
        }
        
        // Check if MemberPress is active
        if (!class_exists('MeprAppCtrl')) {
            throw new Exception('MemberPress is not active or installed');
        }
        
        // Only administrators can access MemberPress data
        if (!current_user_can('manage_options')) {
            throw new Exception('Insufficient permissions to access MemberPress data');
        }
        
        $action = $parameters['action'];
        
        switch ($action) {
            case 'get_memberships':
                return $this->get_memberships($parameters);
            case 'get_transactions':
                return $this->get_transactions($parameters);
            case 'get_subscriptions':
                return $this->get_subscriptions($parameters);
            case 'get_members':
                return $this->get_members($parameters);
            case 'get_stats':
                return $this->get_stats($parameters);
            case 'get_settings':
                return $this->get_settings();
            default:
                throw new Exception("Unknown action: {$action}");
        }
    }
    
    /**
     * Get memberships (products)
     *
     * @param array $parameters Parameters
     * @return array Memberships
     */
    private function get_memberships($parameters) {
        $limit = isset($parameters['limit']) ? intval($parameters['limit']) : 10;
        $products = MeprProduct::get_all();
        $memberships = [];
        
        // Limit the number of products if requested
        $products = array_slice($products, 0, $limit);
        
        foreach ($products as $product) {
            $membership = [
                'id' => $product->ID,
                'title' => $product->post_title,
                'price' => $product->price,
                'period' => $product->period,
                'period_type' => $product->period_type,
                'trial' => $product->trial,
                'trial_days' => $product->trial_days,
                'trial_amount' => $product->trial_amount,
                'limit_cycles' => $product->limit_cycles,
                'limit_cycles_num' => $product->limit_cycles_num,
                'limit_cycles_action' => $product->limit_cycles_action,
                'limit_cycles_expires_after' => $product->limit_cycles_expires_after,
                'limit_cycles_expires_type' => $product->limit_cycles_expires_type,
                'url' => get_permalink($product->ID),
                'created_at' => $product->post_date,
                'modified_at' => $product->post_modified,
            ];
            
            $memberships[] = $membership;
        }
        
        return [
            'memberships' => $memberships,
            'total' => count(MeprProduct::get_all())
        ];
    }
    
    /**
     * Get transactions
     *
     * @param array $parameters Parameters
     * @return array Transactions
     */
    private function get_transactions($parameters) {
        $limit = isset($parameters['limit']) ? intval($parameters['limit']) : 10;
        $page = isset($parameters['page']) ? intval($parameters['page']) : 1;
        $search = isset($parameters['search']) ? $parameters['search'] : '';
        
        $args = [
            'limit' => $limit,
            'page' => $page,
        ];
        
        if (!empty($search)) {
            $args['search'] = $search;
        }
        
        if (isset($parameters['status'])) {
            $args['status'] = $parameters['status'];
        }
        
        if (isset($parameters['member_id'])) {
            $args['member'] = intval($parameters['member_id']);
        }
        
        if (isset($parameters['membership_id'])) {
            $args['product'] = intval($parameters['membership_id']);
        }
        
        $transactions_db = new MeprTransaction();
        $total = $transactions_db->get_count($args);
        $transactions_data = $transactions_db->get_all($args);
        
        $transactions = [];
        
        foreach ($transactions_data as $txn) {
            $user = get_userdata($txn->user_id);
            $product = get_post($txn->product_id);
            
            $transaction = [
                'id' => $txn->id,
                'amount' => $txn->amount,
                'total' => $txn->total,
                'tax_amount' => $txn->tax_amount,
                'tax_rate' => $txn->tax_rate,
                'status' => $txn->status,
                'txn_type' => $txn->txn_type,
                'gateway' => $txn->gateway,
                'subscription_id' => $txn->subscription_id,
                'user_id' => $txn->user_id,
                'user_email' => $user ? $user->user_email : '',
                'user_name' => $user ? $user->display_name : '',
                'product_id' => $txn->product_id,
                'product_name' => $product ? $product->post_title : '',
                'created_at' => $txn->created_at,
                'modified_at' => $txn->modified_at
            ];
            
            $transactions[] = $transaction;
        }
        
        return [
            'transactions' => $transactions,
            'total' => $total,
            'page' => $page,
            'per_page' => $limit,
            'pages' => ceil($total / $limit)
        ];
    }
    
    /**
     * Get subscriptions
     *
     * @param array $parameters Parameters
     * @return array Subscriptions
     */
    private function get_subscriptions($parameters) {
        $limit = isset($parameters['limit']) ? intval($parameters['limit']) : 10;
        $page = isset($parameters['page']) ? intval($parameters['page']) : 1;
        $search = isset($parameters['search']) ? $parameters['search'] : '';
        
        $args = [
            'limit' => $limit,
            'page' => $page,
        ];
        
        if (!empty($search)) {
            $args['search'] = $search;
        }
        
        if (isset($parameters['status'])) {
            $args['status'] = $parameters['status'];
        }
        
        if (isset($parameters['member_id'])) {
            $args['member'] = intval($parameters['member_id']);
        }
        
        if (isset($parameters['membership_id'])) {
            $args['product'] = intval($parameters['membership_id']);
        }
        
        $subscriptions_db = new MeprSubscription();
        $total = $subscriptions_db->get_count($args);
        $subscriptions_data = $subscriptions_db->get_all($args);
        
        $subscriptions = [];
        
        foreach ($subscriptions_data as $sub) {
            $user = get_userdata($sub->user_id);
            $product = get_post($sub->product_id);
            
            $subscription = [
                'id' => $sub->id,
                'subscr_id' => $sub->subscr_id,
                'gateway' => $sub->gateway,
                'user_id' => $sub->user_id,
                'user_email' => $user ? $user->user_email : '',
                'user_name' => $user ? $user->display_name : '',
                'product_id' => $sub->product_id,
                'product_name' => $product ? $product->post_title : '',
                'status' => $sub->status,
                'period' => $sub->period,
                'period_type' => $sub->period_type,
                'limit_cycles' => $sub->limit_cycles,
                'limit_cycles_num' => $sub->limit_cycles_num,
                'limit_cycles_action' => $sub->limit_cycles_action,
                'trial' => $sub->trial,
                'trial_days' => $sub->trial_days,
                'trial_amount' => $sub->trial_amount,
                'total' => $sub->total,
                'created_at' => $sub->created_at,
                'expires_at' => $sub->expires_at,
                'active' => $sub->is_active()
            ];
            
            $subscriptions[] = $subscription;
        }
        
        return [
            'subscriptions' => $subscriptions,
            'total' => $total,
            'page' => $page,
            'per_page' => $limit,
            'pages' => ceil($total / $limit)
        ];
    }
    
    /**
     * Get members
     *
     * @param array $parameters Parameters
     * @return array Members
     */
    private function get_members($parameters) {
        $limit = isset($parameters['limit']) ? intval($parameters['limit']) : 10;
        $args = [
            'number' => $limit,
            'orderby' => 'registered',
            'order' => 'DESC',
            'meta_query' => [
                [
                    'key' => 'mepr_active_subscriptions',
                    'compare' => 'EXISTS'
                ]
            ]
        ];
        
        if (isset($parameters['search'])) {
            $args['search'] = '*' . $parameters['search'] . '*';
        }
        
        if (isset($parameters['membership_id'])) {
            $product_id = intval($parameters['membership_id']);
            $args['meta_query'][] = [
                'key' => 'mepr_active_subscriptions',
                'value' => sprintf(':"%d";', $product_id),
                'compare' => 'LIKE'
            ];
        }
        
        $users = get_users($args);
        $members = [];
        
        foreach ($users as $user) {
            // Get active memberships
            $active_subscriptions = get_user_meta($user->ID, 'mepr_active_subscriptions', true);
            $active_memberships = [];
            
            if (!empty($active_subscriptions)) {
                foreach ($active_subscriptions as $sub_id) {
                    $subscription = new MeprSubscription($sub_id);
                    if ($subscription->id) {
                        $product = get_post($subscription->product_id);
                        $active_memberships[] = [
                            'id' => $subscription->product_id,
                            'name' => $product ? $product->post_title : 'Unknown',
                            'subscription_id' => $subscription->id,
                            'status' => $subscription->status,
                            'expires_at' => $subscription->expires_at
                        ];
                    }
                }
            }
            
            // Get transactions
            $txn_count = MeprUser::get_transactions_count($user->ID);
            
            $member = [
                'id' => $user->ID,
                'username' => $user->user_login,
                'email' => $user->user_email,
                'name' => $user->display_name,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'registered' => $user->user_registered,
                'active_memberships' => $active_memberships,
                'transaction_count' => $txn_count,
                'lifetime_value' => MeprUser::get_lifetime_value($user->ID)
            ];
            
            $members[] = $member;
        }
        
        return [
            'members' => $members,
            'total' => count($users)
        ];
    }
    
    /**
     * Get MemberPress stats
     *
     * @param array $parameters Parameters
     * @return array Stats
     */
    private function get_stats($parameters) {
        $period = isset($parameters['period']) ? $parameters['period'] : '30days';
        $start_date = '';
        $end_date = date('Y-m-d');
        
        // Calculate start date based on period
        switch ($period) {
            case '7days':
                $start_date = date('Y-m-d', strtotime('-7 days'));
                break;
            case '30days':
                $start_date = date('Y-m-d', strtotime('-30 days'));
                break;
            case '90days':
                $start_date = date('Y-m-d', strtotime('-90 days'));
                break;
            case 'year':
                $start_date = date('Y-m-d', strtotime('-1 year'));
                break;
            case 'month':
                $start_date = date('Y-m-01');
                break;
            case 'last_month':
                $start_date = date('Y-m-01', strtotime('-1 month'));
                $end_date = date('Y-m-t', strtotime('-1 month'));
                break;
            default:
                if (isset($parameters['start_date'])) {
                    $start_date = $parameters['start_date'];
                } else {
                    $start_date = date('Y-m-d', strtotime('-30 days'));
                }
                
                if (isset($parameters['end_date'])) {
                    $end_date = $parameters['end_date'];
                }
        }
        
        // Get transaction stats
        $transactions_db = new MeprTransaction();
        $transactions_args = [
            'created_since' => $start_date,
            'created_until' => $end_date,
            'status' => MeprTransaction::$complete_str
        ];
        
        $transactions = $transactions_db->get_all($transactions_args);
        $total_revenue = 0;
        $transaction_count = count($transactions);
        
        foreach ($transactions as $txn) {
            $total_revenue += $txn->total;
        }
        
        // Get subscription stats
        $subscriptions_db = new MeprSubscription();
        $subscriptions_args = [
            'created_since' => $start_date,
            'created_until' => $end_date
        ];
        
        $new_subscriptions = $subscriptions_db->get_count($subscriptions_args);
        
        // Get member stats
        $members_args = [
            'date_query' => [
                [
                    'after' => $start_date,
                    'before' => $end_date,
                    'inclusive' => true
                ]
            ],
            'meta_query' => [
                [
                    'key' => 'mepr_active_subscriptions',
                    'compare' => 'EXISTS'
                ]
            ]
        ];
        
        $new_members = count(get_users($members_args));
        
        // Get total members and active subscriptions
        $total_members_args = [
            'meta_query' => [
                [
                    'key' => 'mepr_active_subscriptions',
                    'compare' => 'EXISTS'
                ]
            ]
        ];
        
        $total_members = count(get_users($total_members_args));
        
        $active_subscriptions_args = [
            'status' => MeprSubscription::$active_str
        ];
        
        $active_subscriptions = $subscriptions_db->get_count($active_subscriptions_args);
        
        return [
            'period' => [
                'start_date' => $start_date,
                'end_date' => $end_date,
                'period_name' => $period
            ],
            'revenue' => [
                'total' => $total_revenue,
                'transaction_count' => $transaction_count,
                'average_transaction' => $transaction_count > 0 ? $total_revenue / $transaction_count : 0
            ],
            'subscriptions' => [
                'new' => $new_subscriptions,
                'active' => $active_subscriptions
            ],
            'members' => [
                'new' => $new_members,
                'total' => $total_members
            ]
        ];
    }
    
    /**
     * Get MemberPress settings
     *
     * @return array Settings
     */
    private function get_settings() {
        $mepr_options = MeprOptions::fetch();
        
        // Get payment methods
        $payment_methods = [];
        foreach ($mepr_options->payment_methods() as $method) {
            $payment_methods[] = [
                'id' => $method->id,
                'name' => $method->name,
                'gateway' => $method->gateway,
                'enabled' => $method->enabled()
            ];
        }
        
        // Get currency info
        $currency = [
            'code' => $mepr_options->currency_code,
            'symbol' => $mepr_options->currency_symbol,
            'symbol_position' => $mepr_options->currency_symbol_after ? 'after' : 'before',
            'decimal_places' => $mepr_options->decimal_places
        ];
        
        // Get email settings
        $emails = [
            'from_name' => $mepr_options->mail_from_name,
            'from_email' => $mepr_options->mail_from_email
        ];
        
        // Get general settings
        $general = [
            'account_page_id' => $mepr_options->account_page_id,
            'login_page_id' => $mepr_options->login_page_id,
            'thankyou_page_id' => $mepr_options->thankyou_page_id,
            'force_login_page' => $mepr_options->force_login_page,
            'disable_wp_admin_bar' => $mepr_options->disable_wp_admin_bar,
            'enable_sso' => $mepr_options->enable_sso,
            'disable_checkout_password_fields' => $mepr_options->disable_checkout_password_fields
        ];
        
        return [
            'general' => $general,
            'currency' => $currency,
            'payment_methods' => $payment_methods,
            'emails' => $emails,
            'tax_enabled' => $mepr_options->tax_enabled,
            'tax_rates_count' => count($mepr_options->tax_rates),
            'coupon_count' => $this->get_coupon_count(),
            'integrations' => $this->get_integrations()
        ];
    }
    
    /**
     * Get coupon count
     *
     * @return int Number of coupons
     */
    private function get_coupon_count() {
        global $wpdb;
        $mepr_db = MeprDb::fetch();
        
        return $wpdb->get_var("SELECT COUNT(*) FROM {$mepr_db->coupons}");
    }
    
    /**
     * Get active integrations
     *
     * @return array Active integrations
     */
    private function get_integrations() {
        $mepr_options = MeprOptions::fetch();
        $integrations = [];
        
        // Check email integrations
        if (class_exists('MeprMailChimpIntegration') && $mepr_options->integrations['mailchimp']['enabled']) {
            $integrations[] = 'MailChimp';
        }
        
        if (class_exists('MeprAweberIntegration') && $mepr_options->integrations['aweber']['enabled']) {
            $integrations[] = 'AWeber';
        }
        
        if (class_exists('MeprMailPouchIntegration') && $mepr_options->integrations['mailpouch']['enabled']) {
            $integrations[] = 'MailPouch';
        }
        
        if (class_exists('MeprActiveCampaignIntegration') && $mepr_options->integrations['activecampaign']['enabled']) {
            $integrations[] = 'ActiveCampaign';
        }
        
        if (class_exists('MeprGetResponseIntegration') && $mepr_options->integrations['getresponse']['enabled']) {
            $integrations[] = 'GetResponse';
        }
        
        if (class_exists('MeprMailerliteIntegration') && $mepr_options->integrations['mailerlite']['enabled']) {
            $integrations[] = 'MailerLite';
        }
        
        if (class_exists('MeprDripIntegration') && $mepr_options->integrations['drip']['enabled']) {
            $integrations[] = 'Drip';
        }
        
        if (class_exists('MeprConvertKitIntegration') && $mepr_options->integrations['convertkit']['enabled']) {
            $integrations[] = 'ConvertKit';
        }
        
        // Check other integrations
        if (class_exists('MeprCE4WPIntegration') && $mepr_options->integrations['ce4wp']['enabled']) {
            $integrations[] = 'Creative Mail';
        }
        
        return $integrations;
    }
    
    /**
     * Get parameters schema
     *
     * @return array Parameters schema
     */
    public function get_parameters_schema() {
        return [
            'type' => 'object',
            'properties' => [
                'action' => [
                    'type' => 'string',
                    'description' => 'The MemberPress action to perform',
                    'enum' => ['get_memberships', 'get_transactions', 'get_subscriptions', 'get_members', 'get_stats', 'get_settings']
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Maximum number of items to retrieve',
                    'minimum' => 1
                ],
                'page' => [
                    'type' => 'integer',
                    'description' => 'Page number for paginated results',
                    'minimum' => 1
                ],
                'search' => [
                    'type' => 'string',
                    'description' => 'Search term for filtering results'
                ],
                'status' => [
                    'type' => 'string',
                    'description' => 'Status filter (e.g., complete, pending, refunded for transactions)'
                ],
                'member_id' => [
                    'type' => 'integer',
                    'description' => 'Filter by member ID'
                ],
                'membership_id' => [
                    'type' => 'integer',
                    'description' => 'Filter by membership ID'
                ],
                'period' => [
                    'type' => 'string',
                    'description' => 'Time period for stats (7days, 30days, 90days, year, month, last_month)',
                    'enum' => ['7days', '30days', '90days', 'year', 'month', 'last_month']
                ],
                'start_date' => [
                    'type' => 'string',
                    'description' => 'Start date for custom period (YYYY-MM-DD)'
                ],
                'end_date' => [
                    'type' => 'string',
                    'description' => 'End date for custom period (YYYY-MM-DD)'
                ]
            ],
            'required' => ['action']
        ];
    }
}