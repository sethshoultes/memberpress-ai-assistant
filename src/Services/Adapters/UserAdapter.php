<?php
/**
 * User Adapter
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Services\Adapters;

/**
 * Adapter for MemberPress Users (Members)
 */
class UserAdapter {
    /**
     * Logger instance
     *
     * @var mixed
     */
    protected $logger;

    /**
     * Constructor
     *
     * @param mixed $logger Logger instance
     */
    public function __construct($logger = null) {
        $this->logger = $logger;
    }

    /**
     * Get a user by ID
     *
     * @param int $id The user ID
     * @return \MeprUser|null The user or null if not found
     */
    public function get(int $id) {
        try {
            if (!class_exists('\MeprUser')) {
                throw new \Exception('MemberPress is not active');
            }

            $user = new \MeprUser($id);
            
            // Check if the user exists
            if (!$user->ID || $user->ID == 0) {
                return null;
            }
            
            return $user;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error getting user: ' . $e->getMessage(), [
                    'user_id' => $id,
                    'exception' => $e
                ]);
            }
            return null;
        }
    }

    /**
     * Get all users
     *
     * @param array $args Optional arguments
     * @return array Array of users
     */
    public function getAll(array $args = []): array {
        try {
            if (!class_exists('\MeprUser')) {
                throw new \Exception('MemberPress is not active');
            }

            // Default arguments
            $defaults = [
                'number' => -1,
                'offset' => 0,
                'orderby' => 'ID',
                'order' => 'DESC',
                'has_subscription' => false,
                'has_transaction' => false,
            ];

            // Merge with provided arguments
            $args = array_merge($defaults, $args);

            // Get WordPress users
            $wp_user_query = new \WP_User_Query([
                'number' => $args['number'],
                'offset' => $args['offset'],
                'orderby' => $args['orderby'],
                'order' => $args['order'],
            ]);

            $wp_users = $wp_user_query->get_results();
            $mepr_users = [];

            // Convert WP_User objects to MeprUser objects
            foreach ($wp_users as $wp_user) {
                $mepr_user = new \MeprUser($wp_user->ID);
                
                // Filter by subscription status if requested
                if ($args['has_subscription'] && !$this->hasSubscription($mepr_user)) {
                    continue;
                }
                
                // Filter by transaction status if requested
                if ($args['has_transaction'] && !$this->hasTransaction($mepr_user)) {
                    continue;
                }
                
                $mepr_users[] = $mepr_user;
            }

            return $mepr_users;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error getting users: ' . $e->getMessage(), [
                    'args' => $args,
                    'exception' => $e
                ]);
            }
            return [];
        }
    }

    /**
     * Create a new user
     *
     * @param array $data The user data
     * @return \MeprUser|null The created user or null on failure
     */
    public function create(array $data) {
        try {
            if (!class_exists('\MeprUser')) {
                throw new \Exception('MemberPress is not active');
            }

            // Required fields
            if (empty($data['email']) || empty($data['password'])) {
                throw new \Exception('Email and password are required');
            }

            // Check if user already exists
            if (email_exists($data['email'])) {
                throw new \Exception('User with this email already exists');
            }

            // Create WordPress user
            $user_id = wp_create_user(
                $data['username'] ?? $data['email'],
                $data['password'],
                $data['email']
            );

            if (is_wp_error($user_id)) {
                throw new \Exception($user_id->get_error_message());
            }

            // Create MeprUser
            $user = new \MeprUser($user_id);
            
            // Set user properties
            if (isset($data['first_name'])) {
                $user->first_name = sanitize_text_field($data['first_name']);
                update_user_meta($user_id, 'first_name', $user->first_name);
            }
            
            if (isset($data['last_name'])) {
                $user->last_name = sanitize_text_field($data['last_name']);
                update_user_meta($user_id, 'last_name', $user->last_name);
            }
            
            // Set custom fields if provided
            if (isset($data['custom_fields']) && is_array($data['custom_fields'])) {
                foreach ($data['custom_fields'] as $key => $value) {
                    // Use WordPress update_user_meta instead of set_meta
                    update_user_meta($user->ID, $key, $value);
                }
            }
            
            // Save the user
            $user->store();
            
            return $user;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error creating user: ' . $e->getMessage(), [
                    'data' => array_diff_key($data, ['password' => '']), // Don't log password
                    'exception' => $e
                ]);
            }
            return null;
        }
    }

    /**
     * Update a user
     *
     * @param int $id The user ID
     * @param array $data The user data
     * @return \MeprUser|null The updated user or null on failure
     */
    public function update(int $id, array $data) {
        try {
            // Get the user
            $user = $this->get($id);
            if (!$user) {
                throw new \Exception('User not found');
            }
            
            // Update user properties
            if (isset($data['email']) && $data['email'] !== $user->user_email) {
                // Check if email is already in use
                if (email_exists($data['email']) && email_exists($data['email']) != $id) {
                    throw new \Exception('Email is already in use');
                }
                
                $user->user_email = sanitize_email($data['email']);
            }
            
            if (isset($data['first_name'])) {
                $user->first_name = sanitize_text_field($data['first_name']);
                update_user_meta($id, 'first_name', $user->first_name);
            }
            
            if (isset($data['last_name'])) {
                $user->last_name = sanitize_text_field($data['last_name']);
                update_user_meta($id, 'last_name', $user->last_name);
            }
            
            // Update password if provided
            if (isset($data['password']) && !empty($data['password'])) {
                wp_set_password($data['password'], $id);
            }
            
            // Update custom fields if provided
            if (isset($data['custom_fields']) && is_array($data['custom_fields'])) {
                foreach ($data['custom_fields'] as $key => $value) {
                    // Use WordPress update_user_meta instead of set_meta
                    update_user_meta($user->ID, $key, $value);
                }
            }
            
            // Save the user
            $user->store();
            
            return $user;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error updating user: ' . $e->getMessage(), [
                    'user_id' => $id,
                    'data' => array_diff_key($data, ['password' => '']), // Don't log password
                    'exception' => $e
                ]);
            }
            return null;
        }
    }

    /**
     * Delete a user
     *
     * @param int $id The user ID
     * @return bool True on success, false on failure
     */
    public function delete(int $id): bool {
        try {
            // Get the user
            $user = $this->get($id);
            if (!$user) {
                throw new \Exception('User not found');
            }
            
            // Delete the user
            $result = wp_delete_user($id);
            
            return $result !== false;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error deleting user: ' . $e->getMessage(), [
                    'user_id' => $id,
                    'exception' => $e
                ]);
            }
            return false;
        }
    }

    /**
     * Check if a user has access to a membership
     *
     * @param int $user_id The user ID
     * @param int $product_id The product ID
     * @return bool True if the user has access, false otherwise
     */
    public function hasAccess(int $user_id, int $product_id): bool {
        try {
            // Get the user
            $user = $this->get($user_id);
            if (!$user) {
                return false;
            }
            
            // Check if the user has access to the membership
            try {
                // Try different method names that might exist in MemberPress
                if (method_exists($user, 'has_access_to_product')) {
                    return $user->has_access_to_product($product_id);
                }
                
                if (method_exists($user, 'has_product_access')) {
                    return $user->has_product_access($product_id);
                }
                
                // Fallback implementation - check if user has an active subscription to this product
                $subscriptions = $this->getSubscriptions($user);
                foreach ($subscriptions as $subscription) {
                    if ($subscription->product_id == $product_id &&
                        (method_exists($subscription, 'is_active') ? $subscription->is_active() : $subscription->status == 'active')) {
                        return true;
                    }
                }
                
                return false;
            } catch (\Exception $e) {
                if ($this->logger) {
                    $this->logger->error('Error checking access: ' . $e->getMessage(), [
                        'user_id' => $user_id,
                        'product_id' => $product_id,
                        'exception' => $e
                    ]);
                }
                return false;
            }
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error checking user access: ' . $e->getMessage(), [
                    'user_id' => $user_id,
                    'product_id' => $product_id,
                    'exception' => $e
                ]);
            }
            return false;
        }
    }

    /**
     * Get user subscriptions
     *
     * @param \MeprUser $user The user
     * @return array The user subscriptions
     */
    public function getSubscriptions(\MeprUser $user): array {
        try {
            // Get the user subscriptions
            $subscriptions = $user->subscriptions();
            
            return $subscriptions;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error getting user subscriptions: ' . $e->getMessage(), [
                    'user_id' => $user->ID,
                    'exception' => $e
                ]);
            }
            return [];
        }
    }

    /**
     * Get user transactions
     *
     * @param \MeprUser $user The user
     * @return array The user transactions
     */
    public function getTransactions(\MeprUser $user): array {
        try {
            // Get the user transactions
            $transactions = $user->transactions();
            
            return $transactions;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error getting user transactions: ' . $e->getMessage(), [
                    'user_id' => $user->ID,
                    'exception' => $e
                ]);
            }
            return [];
        }
    }

    /**
     * Check if a user has any subscriptions
     *
     * @param \MeprUser $user The user
     * @return bool True if the user has subscriptions, false otherwise
     */
    public function hasSubscription(\MeprUser $user): bool {
        try {
            $subscriptions = $this->getSubscriptions($user);
            
            return !empty($subscriptions);
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error checking if user has subscriptions: ' . $e->getMessage(), [
                    'user_id' => $user->ID,
                    'exception' => $e
                ]);
            }
            return false;
        }
    }

    /**
     * Check if a user has any transactions
     *
     * @param \MeprUser $user The user
     * @return bool True if the user has transactions, false otherwise
     */
    public function hasTransaction(\MeprUser $user): bool {
        try {
            $transactions = $this->getTransactions($user);
            
            return !empty($transactions);
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error checking if user has transactions: ' . $e->getMessage(), [
                    'user_id' => $user->ID,
                    'exception' => $e
                ]);
            }
            return false;
        }
    }

    /**
     * Get user memberships
     *
     * @param \MeprUser $user The user
     * @return array The user memberships
     */
    public function getMemberships(\MeprUser $user): array {
        try {
            // Get the user memberships
            $memberships = $user->active_product_subscriptions('ids');
            
            return $memberships;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error getting user memberships: ' . $e->getMessage(), [
                    'user_id' => $user->ID,
                    'exception' => $e
                ]);
            }
            return [];
        }
    }
    /**
     * Add a user to a membership
     *
     * @param int $user_id The user ID
     * @param int $membership_id The membership ID
     * @param array $args Optional arguments (transaction_data, subscription_data)
     * @return bool True on success, false on failure
     */
    public function addToMembership(int $user_id, int $membership_id, array $args = []): bool {
        try {
            // Get the user
            $user = $this->get($user_id);
            if (!$user) {
                throw new \Exception('User not found');
            }
            
            // Check if MemberPress product exists
            if (!class_exists('\MeprProduct')) {
                throw new \Exception('MemberPress is not active');
            }
            
            $product = new \MeprProduct($membership_id);
            if (!$product->ID || $product->ID == 0) {
                throw new \Exception('Membership not found');
            }
            
            // Check if user already has this membership
            if ($this->hasAccess($user_id, $membership_id)) {
                // User already has access, consider this a success
                return true;
            }
            
            // Determine if we should create a transaction, subscription, or both
            $create_transaction = true;
            $create_subscription = $product->is_recurring();
            
            // Override with args if provided
            if (isset($args['create_transaction'])) {
                $create_transaction = (bool)$args['create_transaction'];
            }
            
            if (isset($args['create_subscription'])) {
                $create_subscription = (bool)$args['create_subscription'];
            }
            
            // Create a transaction if needed
            if ($create_transaction) {
                $txn = new \MeprTransaction();
                $txn->user_id = $user_id;
                $txn->product_id = $membership_id;
                $txn->status = \MeprTransaction::$complete_str;
                $txn->txn_type = \MeprTransaction::$payment_str;
                
                // Set transaction amount from product or args
                $txn->amount = $product->price;
                if (isset($args['transaction_data']['amount'])) {
                    $txn->amount = floatval($args['transaction_data']['amount']);
                }
                
                // Set total (same as amount by default)
                $txn->total = $txn->amount;
                if (isset($args['transaction_data']['total'])) {
                    $txn->total = floatval($args['transaction_data']['total']);
                }
                
                // Set expiration if product has one
                if ($product->period_type !== 'lifetime') {
                    $expires_at = \MeprUtils::ts_to_mysql_date(
                        \MeprUtils::calculate_expires_at(
                            time(),
                            $product->period,
                            $product->period_type
                        )
                    );
                    $txn->expires_at = $expires_at;
                }
                
                // Set additional transaction data if provided
                if (isset($args['transaction_data']) && is_array($args['transaction_data'])) {
                    foreach ($args['transaction_data'] as $key => $value) {
                        if (property_exists($txn, $key)) {
                            $txn->$key = $value;
                        }
                    }
                }
                
                // Save the transaction
                $txn->store();
                
                // Process transaction-related hooks
                do_action('mepr-txn-status-complete', $txn);
            }
            
            // Create a subscription if needed
            if ($create_subscription) {
                $sub = new \MeprSubscription();
                $sub->user_id = $user_id;
                $sub->product_id = $membership_id;
                $sub->status = \MeprSubscription::$active_str;
                
                // Set subscription amount from product or args
                $sub->price = $product->price;
                if (isset($args['subscription_data']['price'])) {
                    $sub->price = floatval($args['subscription_data']['price']);
                }
                
                // Set period and period type from product
                $sub->period = $product->period;
                $sub->period_type = $product->period_type;
                
                // Set additional subscription data if provided
                if (isset($args['subscription_data']) && is_array($args['subscription_data'])) {
                    foreach ($args['subscription_data'] as $key => $value) {
                        if (property_exists($sub, $key)) {
                            $sub->$key = $value;
                        }
                    }
                }
                
                // Save the subscription
                $sub->store();
                
                // Process subscription-related hooks
                do_action('mepr-signup', $sub);
                do_action('mepr-subscription-status-active', $sub);
            }
            
            return true;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error adding user to membership: ' . $e->getMessage(), [
                    'user_id' => $user_id,
                    'membership_id' => $membership_id,
                    'args' => $args,
                    'exception' => $e
                ]);
            }
            return false;
        }
    }
    
    /**
     * Remove a user from a membership
     *
     * @param int $user_id The user ID
     * @param int $membership_id The membership ID
     * @return bool True on success, false on failure
     */
    public function removeFromMembership(int $user_id, int $membership_id): bool {
        try {
            // Get the user
            $user = $this->get($user_id);
            if (!$user) {
                throw new \Exception('User not found');
            }
            
            // Check if MemberPress product exists
            if (!class_exists('\MeprProduct')) {
                throw new \Exception('MemberPress is not active');
            }
            
            $product = new \MeprProduct($membership_id);
            if (!$product->ID || $product->ID == 0) {
                throw new \Exception('Membership not found');
            }
            
            // Check if user has this membership
            if (!$this->hasAccess($user_id, $membership_id)) {
                // User doesn't have access, consider this a success
                return true;
            }
            
            // Find and expire active transactions for this membership
            $transactions = $user->transactions();
            foreach ($transactions as $transaction) {
                if ($transaction->product_id == $membership_id && $transaction->status == \MeprTransaction::$complete_str) {
                    // Expire the transaction
                    $transaction->status = \MeprTransaction::$expired_str;
                    $transaction->store();
                    
                    // Process transaction-related hooks
                    do_action('mepr-txn-status-expired', $transaction);
                }
            }
            
            // Find and cancel active subscriptions for this membership
            $subscriptions = $user->subscriptions();
            foreach ($subscriptions as $subscription) {
                if ($subscription->product_id == $membership_id && $subscription->status == \MeprSubscription::$active_str) {
                    // Cancel the subscription
                    $subscription->status = \MeprSubscription::$cancelled_str;
                    $subscription->store();
                    
                    // Process subscription-related hooks
                    do_action('mepr-subscription-status-cancelled', $subscription, $subscription->status);
                }
            }
            
            return true;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error removing user from membership: ' . $e->getMessage(), [
                    'user_id' => $user_id,
                    'membership_id' => $membership_id,
                    'exception' => $e
                ]);
            }
            return false;
        }
    }
    
    /**
     * Set a user's WordPress role
     *
     * @param int $user_id The user ID
     * @param string $role The role to set
     * @return bool True on success, false on failure
     */
    public function setRole(int $user_id, string $role): bool {
        try {
            // Get the WordPress user
            $wp_user = get_user_by('id', $user_id);
            if (!$wp_user) {
                throw new \Exception('User not found');
            }
            
            // Check if the role exists
            $roles = wp_roles()->get_names();
            if (!array_key_exists($role, $roles)) {
                throw new \Exception('Role does not exist: ' . $role);
            }
            
            // Set the user's role
            $wp_user->set_role($role);
            
            return true;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error setting user role: ' . $e->getMessage(), [
                    'user_id' => $user_id,
                    'role' => $role,
                    'exception' => $e
                ]);
            }
            return false;
        }
    }
    
    /**
     * Add a role to a user (in addition to existing roles)
     *
     * @param int $user_id The user ID
     * @param string $role The role to add
     * @return bool True on success, false on failure
     */
    public function addRole(int $user_id, string $role): bool {
        try {
            // Get the WordPress user
            $wp_user = get_user_by('id', $user_id);
            if (!$wp_user) {
                throw new \Exception('User not found');
            }
            
            // Check if the role exists
            $roles = wp_roles()->get_names();
            if (!array_key_exists($role, $roles)) {
                throw new \Exception('Role does not exist: ' . $role);
            }
            
            // Add the role to the user
            $wp_user->add_role($role);
            
            return true;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error adding user role: ' . $e->getMessage(), [
                    'user_id' => $user_id,
                    'role' => $role,
                    'exception' => $e
                ]);
            }
            return false;
        }
    }
    
    /**
     * Remove a role from a user
     *
     * @param int $user_id The user ID
     * @param string $role The role to remove
     * @return bool True on success, false on failure
     */
    public function removeRole(int $user_id, string $role): bool {
        try {
            // Get the WordPress user
            $wp_user = get_user_by('id', $user_id);
            if (!$wp_user) {
                throw new \Exception('User not found');
            }
            
            // Remove the role from the user
            $wp_user->remove_role($role);
            
            return true;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error removing user role: ' . $e->getMessage(), [
                    'user_id' => $user_id,
                    'role' => $role,
                    'exception' => $e
                ]);
            }
            return false;
        }
    }
    
    /**
     * Get a user's WordPress roles
     *
     * @param int $user_id The user ID
     * @return array The user's roles
     */
    public function getRoles(int $user_id): array {
        try {
            // Get the WordPress user
            $wp_user = get_user_by('id', $user_id);
            if (!$wp_user) {
                throw new \Exception('User not found');
            }
            
            // Get the user's roles
            $roles = $wp_user->roles;
            
            // Get role names
            $role_names = [];
            $wp_roles = wp_roles();
            
            foreach ($roles as $role) {
                $role_names[$role] = $wp_roles->roles[$role]['name'] ?? $role;
            }
            
            return $role_names;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error getting user roles: ' . $e->getMessage(), [
                    'user_id' => $user_id,
                    'exception' => $e
                ]);
            }
            return [];
        }
    }
    
    /**
     * Get a user's capabilities
     *
     * @param int $user_id The user ID
     * @return array The user's capabilities
     */
    public function getCapabilities(int $user_id): array {
        try {
            // Get the WordPress user
            $wp_user = get_user_by('id', $user_id);
            if (!$wp_user) {
                throw new \Exception('User not found');
            }
            
            // Get all capabilities
            $all_caps = $wp_user->allcaps;
            
            // Filter to only true capabilities
            $caps = array_filter($all_caps);
            
            return array_keys($caps);
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error getting user capabilities: ' . $e->getMessage(), [
                    'user_id' => $user_id,
                    'exception' => $e
                ]);
            }
            return [];
        }
    }
}