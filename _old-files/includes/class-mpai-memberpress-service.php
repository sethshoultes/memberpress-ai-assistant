<?php
/**
 * MemberPress Service
 *
 * Provides direct integration with MemberPress core classes
 * for membership management, transactions, and more.
 *
 * @package MemberPress AI Assistant
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * MemberPress Service class
 * 
 * Handles all direct interactions with MemberPress core classes
 */
class MPAI_MemberPress_Service {
    /**
     * Check if MemberPress is available
     * 
     * @return bool
     */
    public function is_memberpress_available() {
        return mpai_is_memberpress_active();
    }
    
    /**
     * Create a new membership level with strict parameter validation
     * 
     * @param array $args Membership arguments
     * @return array|WP_Error Result with success/error status and message
     */
    public function create_membership($args) {
        if (!class_exists('MeprProduct')) {
            mpai_log_error('MemberPress is not available for membership creation', 'memberpress-service');
            return [
                'error' => true,
                'message' => 'MemberPress is not available'
            ];
        }
        
        // Log incoming parameters
        mpai_log_debug('MEMBERSHIP CREATE - Raw arguments: ' . json_encode($args), 'memberpress-service');
        
        // PHASE 1: Parameter extraction from different formats
        $this->check_and_extract_json_parameters($args);
        
        // Check for tool_request parameter that may contain actual parameters
        if (isset($args['tool_request'])) {
            mpai_log_debug('MEMBERSHIP CREATE - Found tool_request parameter, extracting parameters', 'memberpress-service');
            $this->extract_parameters_from_tool_request($args);
        }
        
        // Handle nested parameters array
        if (isset($args['parameters']) && is_array($args['parameters']) && isset($args['parameters']['type'])) {
            mpai_log_debug('MEMBERSHIP CREATE - Found nested parameters array, extracting values', 'memberpress-service');
            foreach ($args['parameters'] as $key => $value) {
                $args[$key] = $value;
            }
        }
        
        // PHASE 2: Parameter validation
        $validation_errors = [];
        
        // Validate name - reject default or empty
        if (!isset($args['name']) || empty($args['name']) || $args['name'] === 'New Membership') {
            $validation_errors[] = "Missing or invalid membership name. Default 'New Membership' is not allowed.";
            mpai_log_error('MEMBERSHIP CREATE - Missing or invalid name parameter: ' . (isset($args['name']) ? $args['name'] : 'NOT SET'), 'memberpress-service');
        }
        
        // Validate price - must be positive number
        if (!isset($args['price']) || 
            (is_string($args['price']) && !is_numeric($args['price'])) || 
            floatval($args['price']) <= 0) {
            $validation_errors[] = "Missing or invalid membership price. Price must be a positive number.";
            mpai_log_error('MEMBERSHIP CREATE - Missing or invalid price parameter: ' . (isset($args['price']) ? var_export($args['price'], true) : 'NOT SET'), 'memberpress-service');
        }
        
        // Validate period_type
        if (!isset($args['period_type']) || 
            !in_array($args['period_type'], ['month', 'year', 'lifetime'])) {
            $validation_errors[] = "Missing or invalid period_type. Must be 'month', 'year', or 'lifetime'.";
            mpai_log_error('MEMBERSHIP CREATE - Missing or invalid period_type: ' . (isset($args['period_type']) ? $args['period_type'] : 'NOT SET'), 'memberpress-service');
        }
        
        // Return early if validation fails
        if (!empty($validation_errors)) {
            mpai_log_error('MEMBERSHIP CREATE - Validation failed: ' . implode(' ', $validation_errors), 'memberpress-service');
            return [
                'error' => true,
                'message' => 'Validation failed: ' . implode(' ', $validation_errors),
                'validation_errors' => $validation_errors
            ];
        }
        
        // PHASE 3: Create membership object with validated parameters
        try {
            // Create a new product/membership
            $membership = new MeprProduct();
            
            // Set basic properties with validated parameters
            $membership->post_title = sanitize_text_field($args['name']);
            $membership->post_status = 'publish';
            
            // Set description if provided
            $membership->post_content = isset($args['description']) ? wp_kses_post($args['description']) : '';
            
            // Ensure price is numeric and positive
            $raw_price = $args['price'];
            if (is_string($raw_price) && is_numeric($raw_price)) {
                $raw_price = floatval($raw_price);
            }
            $membership->price = $raw_price;
            
            // Set period type and period
            $membership->period_type = sanitize_text_field($args['period_type']);
            $membership->period = isset($args['period']) ? intval($args['period']) : 1;
            
            // Set trial parameters if provided
            if (isset($args['trial']) && $args['trial']) {
                $membership->trial = true;
                $membership->trial_days = isset($args['trial_days']) ? intval($args['trial_days']) : 14;
                $membership->trial_amount = isset($args['trial_amount']) ? floatval($args['trial_amount']) : 0.00;
            }
            
            // Log final values before saving
            mpai_log_debug('MEMBERSHIP CREATE - Final values before save: ' . json_encode([
                'title' => $membership->post_title,
                'price' => $membership->price,
                'period_type' => $membership->period_type,
                'period' => $membership->period,
                'price_type' => gettype($membership->price)
            ]), 'memberpress-service');
            
            // Save the membership
            $result = $membership->save();
            
            if ($result) {
                mpai_log_info('MEMBERSHIP CREATE - Successfully created membership ID: ' . $membership->ID, 'memberpress-service');
                return [
                    'success' => true,
                    'message' => 'Membership created successfully',
                    'membership_id' => $membership->ID,
                    'name' => $membership->post_title,
                    'price' => $membership->price,
                    'period_type' => $membership->period_type
                ];
            } else {
                mpai_log_error('MEMBERSHIP CREATE - Failed to save membership', 'memberpress-service');
                return [
                    'error' => true,
                    'message' => 'Failed to save membership'
                ];
            }
        } catch (Exception $e) {
            mpai_log_error('MEMBERSHIP CREATE - Exception: ' . $e->getMessage(), 'memberpress-service');
            return [
                'error' => true,
                'message' => 'Exception: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Check for JSON string in parameters and extract
     * 
     * @param array &$args Parameters passed to create_membership
     */
    private function check_and_extract_json_parameters(&$args) {
        // Check each parameter to see if it's a JSON string
        foreach ($args as $key => $value) {
            if (is_string($value) && 
                (strpos($value, '{') === 0 || strpos($value, '[') === 0) && 
                (strpos($value, '"type":"create"') !== false || strpos($value, '"name":') !== false)) {
                
                error_log('MEMBERSHIP CREATE - Found potential JSON string in parameter: ' . $key);
                
                try {
                    $json_data = json_decode($value, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($json_data)) {
                        error_log('MEMBERSHIP CREATE - Successfully parsed JSON in ' . $key . ': ' . json_encode($json_data));
                        
                        // Extract parameters based on different formats
                        if (isset($json_data['parameters']) && is_array($json_data['parameters'])) {
                            error_log('MEMBERSHIP CREATE - Found parameters object in JSON');
                            foreach ($json_data['parameters'] as $param_key => $param_value) {
                                $args[$param_key] = $param_value;
                                error_log('MEMBERSHIP CREATE - Extracted ' . $param_key . '=' . var_export($param_value, true));
                            }
                        } elseif (isset($json_data['name'])) {
                            error_log('MEMBERSHIP CREATE - Found name in JSON data');
                            $args['name'] = $json_data['name'];
                        }
                        
                        // Check for price as a direct property
                        if (isset($json_data['price'])) {
                            error_log('MEMBERSHIP CREATE - Found price in JSON data: ' . var_export($json_data['price'], true));
                            $args['price'] = is_numeric($json_data['price']) ? $json_data['price'] : floatval($json_data['price']);
                        }
                    }
                } catch (Exception $e) {
                    error_log('MEMBERSHIP CREATE - Error parsing JSON in parameter ' . $key . ': ' . $e->getMessage());
                }
            }
        }
    }
    
    /**
     * Extract parameters from tool_request
     * 
     * @param array &$args Parameters passed to create_membership
     */
    private function extract_parameters_from_tool_request(&$args) {
        if (!isset($args['tool_request']) || !is_string($args['tool_request'])) {
            return;
        }
        
        try {
            $tool_request = json_decode($args['tool_request'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($tool_request)) {
                error_log('MEMBERSHIP CREATE - Parsed tool_request: ' . json_encode($tool_request));
                
                if (isset($tool_request['parameters']) && is_array($tool_request['parameters'])) {
                    error_log('MEMBERSHIP CREATE - Found parameters in tool_request');
                    
                    // Important: price must be a number
                    if (isset($tool_request['parameters']['price'])) {
                        if (is_string($tool_request['parameters']['price'])) {
                            $tool_request['parameters']['price'] = floatval($tool_request['parameters']['price']);
                            error_log('MEMBERSHIP CREATE - Converted price from string to number: ' . $tool_request['parameters']['price']);
                        }
                    }
                    
                    // Copy all parameters to main args array
                    foreach ($tool_request['parameters'] as $key => $value) {
                        $args[$key] = $value;
                        error_log('MEMBERSHIP CREATE - Extracted from tool_request: ' . $key . '=' . var_export($value, true));
                    }
                }
            }
        } catch (Exception $e) {
            error_log('MEMBERSHIP CREATE - Error processing tool_request: ' . $e->getMessage());
        }
    }
    
    /**
     * Add a user to a membership (create subscription)
     * 
     * @param int $user_id User ID
     * @param int $membership_id Membership ID
     * @param array $args Additional arguments
     * @return MeprSubscription|WP_Error New subscription or error
     */
    public function add_user_to_membership($user_id, $membership_id, $args = []) {
        if (!class_exists('MeprSubscription') || !class_exists('MeprUser') || !class_exists('MeprProduct')) {
            return new WP_Error('memberpress_missing', 'MemberPress is not available');
        }
        
        // Get the user
        $user = new MeprUser($user_id);
        if (empty($user->ID)) {
            return new WP_Error('invalid_user', 'Invalid user ID');
        }
        
        // Get the membership
        $membership = new MeprProduct($membership_id);
        if (empty($membership->ID)) {
            return new WP_Error('invalid_membership', 'Invalid membership ID');
        }
        
        // Create a new subscription
        $subscription = new MeprSubscription();
        $subscription->user_id = $user_id;
        $subscription->product_id = $membership_id;
        
        // Set gateway if provided, otherwise use manual
        $subscription->gateway = isset($args['gateway']) ? sanitize_text_field($args['gateway']) : 'manual';
        
        // Set subscription status
        $subscription->status = isset($args['status']) ? sanitize_text_field($args['status']) : 'active';
        
        // Set trial if membership has trial
        if ($membership->trial) {
            $subscription->trial = true;
            $subscription->trial_days = $membership->trial_days;
            $subscription->trial_amount = $membership->trial_amount;
        }
        
        // Save the subscription
        $result = $subscription->save();
        
        if ($result) {
            // Create an initial transaction for this subscription
            $txn = new MeprTransaction();
            $txn->user_id = $user_id;
            $txn->product_id = $membership_id;
            $txn->status = 'complete';
            $txn->txn_type = 'subscription_confirmation';
            $txn->gateway = $subscription->gateway;
            $txn->subscription_id = $subscription->id;
            $txn->save();
            
            return $subscription;
        } else {
            return new WP_Error('save_failed', 'Failed to save subscription');
        }
    }
    
    /**
     * Process a transaction
     * 
     * @param array $args Transaction arguments
     * @return MeprTransaction|WP_Error New transaction or error
     */
    public function create_transaction($args) {
        if (!class_exists('MeprTransaction')) {
            return new WP_Error('memberpress_missing', 'MemberPress is not available');
        }
        
        // Check required fields
        if (empty($args['user_id']) || empty($args['product_id'])) {
            return new WP_Error('missing_fields', 'User ID and Product ID are required');
        }
        
        // Create a new transaction
        $txn = new MeprTransaction();
        $txn->user_id = intval($args['user_id']);
        $txn->product_id = intval($args['product_id']);
        
        // Set other fields
        $txn->status = isset($args['status']) ? sanitize_text_field($args['status']) : 'complete';
        $txn->txn_type = isset($args['txn_type']) ? sanitize_text_field($args['txn_type']) : 'payment';
        $txn->gateway = isset($args['gateway']) ? sanitize_text_field($args['gateway']) : 'manual';
        
        // Set amount
        if (isset($args['amount'])) {
            $txn->amount = floatval($args['amount']);
        } else {
            // Get amount from product
            $product = new MeprProduct($txn->product_id);
            $txn->amount = $product->price;
        }
        
        // Set subscription ID if provided
        if (!empty($args['subscription_id'])) {
            $txn->subscription_id = intval($args['subscription_id']);
        }
        
        // Save the transaction
        $result = $txn->save();
        
        if ($result) {
            // If this is a successful payment, make sure user has access
            if ($txn->status === 'complete') {
                // This will ensure the user has the proper role for this membership
                $product = new MeprProduct($txn->product_id);
                $user = new MeprUser($txn->user_id);
                $user->add_product($product->ID);
            }
            
            return $txn;
        } else {
            return new WP_Error('save_failed', 'Failed to save transaction');
        }
    }
    
    /**
     * Create a coupon
     * 
     * @param array $args Coupon arguments
     * @return MeprCoupon|WP_Error New coupon or error
     */
    public function create_coupon($args) {
        if (!class_exists('MeprCoupon')) {
            return new WP_Error('memberpress_missing', 'MemberPress is not available');
        }
        
        // Create a new coupon
        $coupon = new MeprCoupon();
        
        // Set basic properties
        $coupon->post_title = isset($args['code']) ? strtoupper(sanitize_text_field($args['code'])) : 'COUPON' . rand(1000, 9999);
        $coupon->post_content = isset($args['description']) ? wp_kses_post($args['description']) : '';
        
        // Set discount type and amount
        $coupon->discount_type = isset($args['discount_type']) ? sanitize_text_field($args['discount_type']) : 'percent';
        $coupon->discount_amount = isset($args['discount_amount']) ? floatval($args['discount_amount']) : 10.00;
        
        // Set expiration if provided
        if (isset($args['expires_on'])) {
            $coupon->expires_on = sanitize_text_field($args['expires_on']);
            $coupon->should_expire = true;
        } else {
            $coupon->should_expire = false;
        }
        
        // Set usage limit if provided
        if (isset($args['usage_limit'])) {
            $coupon->usage_amount = intval($args['usage_limit']);
            $coupon->usage_limit = true;
        } else {
            $coupon->usage_limit = false;
        }
        
        // Save the coupon
        $result = $coupon->save();
        
        if ($result) {
            return $coupon;
        } else {
            return new WP_Error('save_failed', 'Failed to save coupon');
        }
    }
    
    /**
     * Apply a coupon to a subscription
     * 
     * @param int $coupon_id Coupon ID
     * @param int $subscription_id Subscription ID
     * @return bool|WP_Error Success or error
     */
    public function apply_coupon_to_subscription($coupon_id, $subscription_id) {
        if (!class_exists('MeprCoupon') || !class_exists('MeprSubscription')) {
            return new WP_Error('memberpress_missing', 'MemberPress is not available');
        }
        
        // Get the coupon
        $coupon = new MeprCoupon($coupon_id);
        if (empty($coupon->ID)) {
            return new WP_Error('invalid_coupon', 'Invalid coupon ID');
        }
        
        // Get the subscription
        $subscription = new MeprSubscription($subscription_id);
        if (empty($subscription->id)) {
            return new WP_Error('invalid_subscription', 'Invalid subscription ID');
        }
        
        // Apply the coupon
        $subscription->coupon_id = $coupon->ID;
        $result = $subscription->save();
        
        if ($result) {
            return true;
        } else {
            return new WP_Error('save_failed', 'Failed to apply coupon');
        }
    }
    
    /**
     * Get a user's memberships
     * 
     * @param int $user_id User ID
     * @return array Array of memberships
     */
    public function get_user_memberships($user_id) {
        if (!class_exists('MeprUser')) {
            return array();
        }
        
        $user = new MeprUser($user_id);
        return $user->active_products();
    }
    
    /**
     * Get members with optional filtering
     * 
     * @param array $params Query parameters
     * @param bool $formatted Whether to return formatted data
     * @return array Members data
     */
    public function get_members($params = array(), $formatted = false) {
        if (!class_exists('MeprUser')) {
            return array();
        }
        
        $args = array();
        $limit = isset($params['per_page']) ? intval($params['per_page']) : 20;
        
        // Add date filtering
        if (!empty($params['start_date'])) {
            $args['date_query']['after'] = $params['start_date'];
        }
        
        if (!empty($params['end_date'])) {
            $args['date_query']['before'] = $params['end_date']; 
        }
        
        // Get members using core MeprUser class
        $members = MeprUser::all('objects', $args, '', $limit);
        
        // Format results to match expected structure
        $result = array();
        foreach ($members as $member) {
            $result[] = array(
                'id' => $member->ID,
                'username' => $member->user_login,
                'email' => $member->user_email,
                'display_name' => $member->display_name,
                'registered' => $member->user_registered
            );
        }
        
        if ($formatted && !empty($result)) {
            return $this->format_members_as_table($result);
        }
        
        return $result;
    }
    
    /**
     * Format members data as a table
     * 
     * @param array $members Members data
     * @return string Formatted table
     */
    private function format_members_as_table($members) {
        if (empty($members)) {
            return "ID\tUsername\tEmail\tJoin Date\nNo members found.";
        }
        
        $output = "ID\tUsername\tEmail\tJoin Date\n";
        
        foreach ($members as $member) {
            $id = isset($member['id']) ? $member['id'] : 'N/A';
            $username = isset($member['username']) ? $member['username'] : 'N/A';
            $email = isset($member['email']) ? $member['email'] : 'N/A';
            $join_date = isset($member['registered']) ? date('Y-m-d', strtotime($member['registered'])) : 'N/A';
            
            $output .= "$id\t$username\t$email\t$join_date\n";
        }
        
        return $output;
    }
    
    /**
     * Get memberships
     * 
     * @param array $params Query parameters
     * @param bool $formatted Whether to return formatted data
     * @return array Memberships data
     */
    public function get_memberships($params = array(), $formatted = false) {
        if (!class_exists('MeprProduct')) {
            return array();
        }
        
        $limit = isset($params['per_page']) ? intval($params['per_page']) : -1;
        
        // Get all membership products
        $products = MeprProduct::all('objects', array(), '', $limit);
        
        $result = array();
        foreach ($products as $product) {
            $result[] = array(
                'id' => $product->ID,
                'title' => $product->post_title,
                'description' => $product->post_excerpt,
                'price' => $product->price,
                'period' => $product->period,
                'period_type' => $product->period_type,
                'status' => $product->post_status,
                'created_at' => $product->post_date
            );
        }
        
        if ($formatted && !empty($result)) {
            return $this->format_memberships_as_table($result);
        }
        
        return $result;
    }
    
    /**
     * Format memberships data as a table
     * 
     * @param array $memberships Memberships data
     * @return string Formatted table
     */
    private function format_memberships_as_table($memberships) {
        if (empty($memberships)) {
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
}