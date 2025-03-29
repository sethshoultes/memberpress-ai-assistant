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
     * Make a request to the MemberPress API
     *
     * @param string $endpoint The API endpoint
     * @param string $method HTTP method (GET, POST, etc.)
     * @param array $data Data to send with the request
     * @return array|WP_Error The API response or error
     */
    public function request($endpoint, $method = 'GET', $data = array()) {
        if (empty($this->api_key)) {
            return new WP_Error('missing_api_key', 'MemberPress API key is not configured.');
        }

        $url = $this->base_url . ltrim($endpoint, '/');

        $args = array(
            'method' => $method,
            'headers' => array(
                'Authorization' => $this->api_key,
                'Content-Type' => 'application/json',
            ),
            'timeout' => 30,
        );

        if (!empty($data) && in_array($method, array('POST', 'PUT'))) {
            $args['body'] = json_encode($data);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error(
                'json_error',
                'Failed to parse MemberPress API response.',
                array('response' => $body)
            );
        }

        return $data;
    }

    /**
     * Get members from the API
     *
     * @param array $params Query parameters
     * @return array|WP_Error The members or error
     */
    public function get_members($params = array()) {
        return $this->request('members', 'GET', $params);
    }

    /**
     * Get a specific member
     *
     * @param int $member_id The member ID
     * @return array|WP_Error The member or error
     */
    public function get_member($member_id) {
        return $this->request("members/{$member_id}");
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
     * Get memberships from the API
     *
     * @param array $params Query parameters
     * @return array|WP_Error The memberships or error
     */
    public function get_memberships($params = array()) {
        return $this->request('memberships', 'GET', $params);
    }

    /**
     * Get a specific membership
     *
     * @param int $membership_id The membership ID
     * @return array|WP_Error The membership or error
     */
    public function get_membership($membership_id) {
        return $this->request("memberships/{$membership_id}");
    }

    /**
     * Get transactions from the API
     *
     * @param array $params Query parameters
     * @return array|WP_Error The transactions or error
     */
    public function get_transactions($params = array()) {
        return $this->request('transactions', 'GET', $params);
    }

    /**
     * Get a specific transaction
     *
     * @param int $transaction_id The transaction ID
     * @return array|WP_Error The transaction or error
     */
    public function get_transaction($transaction_id) {
        return $this->request("transactions/{$transaction_id}");
    }

    /**
     * Get subscriptions from the API
     *
     * @param array $params Query parameters
     * @return array|WP_Error The subscriptions or error
     */
    public function get_subscriptions($params = array()) {
        return $this->request('subscriptions', 'GET', $params);
    }

    /**
     * Get a specific subscription
     *
     * @param int $subscription_id The subscription ID
     * @return array|WP_Error The subscription or error
     */
    public function get_subscription($subscription_id) {
        return $this->request("subscriptions/{$subscription_id}");
    }

    /**
     * Get events from the API
     *
     * @param array $params Query parameters
     * @return array|WP_Error The events or error
     */
    public function get_events($params = array()) {
        return $this->request('events', 'GET', $params);
    }

    /**
     * Get summary of MemberPress data for AI context
     *
     * @return array MemberPress data summary
     */
    public function get_data_summary() {
        try {
            error_log('MPAI: Getting MemberPress data summary');
            
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