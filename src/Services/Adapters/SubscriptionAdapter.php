<?php
/**
 * Subscription Adapter
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Services\Adapters;

/**
 * Adapter for MemberPress Subscriptions
 */
class SubscriptionAdapter {
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
     * Get a subscription by ID
     *
     * @param int $id The subscription ID
     * @return \MeprSubscription|null The subscription or null if not found
     */
    public function get(int $id) {
        try {
            if (!class_exists('\MeprSubscription')) {
                throw new \Exception('MemberPress is not active');
            }

            $subscription = new \MeprSubscription($id);
            
            // Check if the subscription exists
            if (!$subscription->id || $subscription->id == 0) {
                return null;
            }
            
            return $subscription;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error getting subscription: ' . $e->getMessage(), [
                    'subscription_id' => $id,
                    'exception' => $e
                ]);
            }
            return null;
        }
    }

    /**
     * Get all subscriptions
     *
     * @param array $args Optional arguments
     * @return array Array of subscriptions
     */
    public function getAll(array $args = []): array {
        try {
            if (!class_exists('\MeprSubscription')) {
                throw new \Exception('MemberPress is not active');
            }

            // Default arguments
            $defaults = [
                'limit' => -1,
                'offset' => 0,
                'orderby' => 'id',
                'order' => 'DESC',
                'status' => null,
                'user_id' => null,
                'product_id' => null,
            ];

            // Merge with provided arguments
            $args = array_merge($defaults, $args);

            // Build query conditions
            $conditions = [];
            $query_params = [];

            // Base query
            $query = "SELECT * FROM {$GLOBALS['wpdb']->prefix}mepr_subscriptions WHERE 1=1";

            // Add status condition if provided
            if (!empty($args['status'])) {
                $conditions[] = "status = %s";
                $query_params[] = $args['status'];
            }

            // Add user_id condition if provided
            if (!empty($args['user_id'])) {
                $conditions[] = "user_id = %d";
                $query_params[] = $args['user_id'];
            }

            // Add product_id condition if provided
            if (!empty($args['product_id'])) {
                $conditions[] = "product_id = %d";
                $query_params[] = $args['product_id'];
            }

            // Add conditions to query
            if (!empty($conditions)) {
                $query .= " AND " . implode(" AND ", $conditions);
            }

            // Add order by
            $query .= " ORDER BY {$args['orderby']} {$args['order']}";

            // Add limit and offset
            if ($args['limit'] > 0) {
                $query .= " LIMIT %d OFFSET %d";
                $query_params[] = $args['limit'];
                $query_params[] = $args['offset'];
            }

            // Prepare the query
            if (!empty($query_params)) {
                $query = $GLOBALS['wpdb']->prepare($query, $query_params);
            }

            // Execute the query
            $results = $GLOBALS['wpdb']->get_results($query);
            $subscriptions = [];

            // Convert results to MeprSubscription objects
            foreach ($results as $result) {
                $subscriptions[] = new \MeprSubscription($result->id);
            }

            return $subscriptions;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error getting subscriptions: ' . $e->getMessage(), [
                    'args' => $args,
                    'exception' => $e
                ]);
            }
            return [];
        }
    }

    /**
     * Create a new subscription
     *
     * @param array $data The subscription data
     * @return \MeprSubscription|null The created subscription or null on failure
     */
    public function create(array $data) {
        try {
            if (!class_exists('\MeprSubscription')) {
                throw new \Exception('MemberPress is not active');
            }

            // Required fields
            if (empty($data['user_id']) || empty($data['product_id'])) {
                throw new \Exception('User ID and Product ID are required');
            }

            // Create a new subscription
            $subscription = new \MeprSubscription();
            
            // Set subscription properties
            if (isset($data['user_id'])) {
                $subscription->user_id = intval($data['user_id']);
            }
            
            if (isset($data['product_id'])) {
                $subscription->product_id = intval($data['product_id']);
            }
            
            if (isset($data['coupon_id'])) {
                $subscription->coupon_id = intval($data['coupon_id']);
            }
            
            if (isset($data['price'])) {
                $subscription->price = floatval($data['price']);
            }
            
            if (isset($data['period'])) {
                $subscription->period = intval($data['period']);
            }
            
            if (isset($data['period_type'])) {
                $subscription->period_type = sanitize_text_field($data['period_type']);
            }
            
            if (isset($data['limit_cycles'])) {
                $subscription->limit_cycles = (bool)$data['limit_cycles'];
            }
            
            if (isset($data['limit_cycles_num'])) {
                $subscription->limit_cycles_num = intval($data['limit_cycles_num']);
            }
            
            if (isset($data['limit_cycles_action'])) {
                $subscription->limit_cycles_action = sanitize_text_field($data['limit_cycles_action']);
            }
            
            if (isset($data['trial'])) {
                $subscription->trial = (bool)$data['trial'];
            }
            
            if (isset($data['trial_days'])) {
                $subscription->trial_days = intval($data['trial_days']);
            }
            
            if (isset($data['trial_amount'])) {
                $subscription->trial_amount = floatval($data['trial_amount']);
            }
            
            if (isset($data['status'])) {
                $subscription->status = sanitize_text_field($data['status']);
            } else {
                $subscription->status = 'pending';
            }
            
            if (isset($data['gateway'])) {
                $subscription->gateway = sanitize_text_field($data['gateway']);
            }
            
            if (isset($data['created_at'])) {
                $subscription->created_at = sanitize_text_field($data['created_at']);
            } else {
                $subscription->created_at = date('Y-m-d H:i:s');
            }
            
            // Save the subscription
            $subscription->store();
            
            return $subscription;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error creating subscription: ' . $e->getMessage(), [
                    'data' => $data,
                    'exception' => $e
                ]);
            }
            return null;
        }
    }

    /**
     * Update a subscription
     *
     * @param int $id The subscription ID
     * @param array $data The subscription data
     * @return \MeprSubscription|null The updated subscription or null on failure
     */
    public function update(int $id, array $data) {
        try {
            // Get the subscription
            $subscription = $this->get($id);
            if (!$subscription) {
                throw new \Exception('Subscription not found');
            }
            
            // Update subscription properties
            if (isset($data['user_id'])) {
                $subscription->user_id = intval($data['user_id']);
            }
            
            if (isset($data['product_id'])) {
                $subscription->product_id = intval($data['product_id']);
            }
            
            if (isset($data['coupon_id'])) {
                $subscription->coupon_id = intval($data['coupon_id']);
            }
            
            if (isset($data['price'])) {
                $subscription->price = floatval($data['price']);
            }
            
            if (isset($data['period'])) {
                $subscription->period = intval($data['period']);
            }
            
            if (isset($data['period_type'])) {
                $subscription->period_type = sanitize_text_field($data['period_type']);
            }
            
            if (isset($data['limit_cycles'])) {
                $subscription->limit_cycles = (bool)$data['limit_cycles'];
            }
            
            if (isset($data['limit_cycles_num'])) {
                $subscription->limit_cycles_num = intval($data['limit_cycles_num']);
            }
            
            if (isset($data['limit_cycles_action'])) {
                $subscription->limit_cycles_action = sanitize_text_field($data['limit_cycles_action']);
            }
            
            if (isset($data['trial'])) {
                $subscription->trial = (bool)$data['trial'];
            }
            
            if (isset($data['trial_days'])) {
                $subscription->trial_days = intval($data['trial_days']);
            }
            
            if (isset($data['trial_amount'])) {
                $subscription->trial_amount = floatval($data['trial_amount']);
            }
            
            if (isset($data['status'])) {
                $subscription->status = sanitize_text_field($data['status']);
            }
            
            if (isset($data['gateway'])) {
                $subscription->gateway = sanitize_text_field($data['gateway']);
            }
            
            // Save the subscription
            $subscription->store();
            
            return $subscription;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error updating subscription: ' . $e->getMessage(), [
                    'subscription_id' => $id,
                    'data' => $data,
                    'exception' => $e
                ]);
            }
            return null;
        }
    }

    /**
     * Delete a subscription
     *
     * @param int $id The subscription ID
     * @return bool True on success, false on failure
     */
    public function delete(int $id): bool {
        try {
            // Get the subscription
            $subscription = $this->get($id);
            if (!$subscription) {
                throw new \Exception('Subscription not found');
            }
            
            // Delete the subscription
            $result = $subscription->destroy();
            
            return $result !== false;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error deleting subscription: ' . $e->getMessage(), [
                    'subscription_id' => $id,
                    'exception' => $e
                ]);
            }
            return false;
        }
    }

    /**
     * Cancel a subscription
     *
     * @param int $id The subscription ID
     * @return bool True on success, false on failure
     */
    public function cancel(int $id): bool {
        try {
            // Get the subscription
            $subscription = $this->get($id);
            if (!$subscription) {
                throw new \Exception('Subscription not found');
            }
            
            // Cancel the subscription
            $result = $subscription->cancel();
            
            return $result !== false;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error cancelling subscription: ' . $e->getMessage(), [
                    'subscription_id' => $id,
                    'exception' => $e
                ]);
            }
            return false;
        }
    }

    /**
     * Pause a subscription
     *
     * @param int $id The subscription ID
     * @return bool True on success, false on failure
     */
    public function pause(int $id): bool {
        try {
            // Get the subscription
            $subscription = $this->get($id);
            if (!$subscription) {
                throw new \Exception('Subscription not found');
            }
            
            // Pause the subscription
            // Check if the method exists
            if (method_exists($subscription, 'pause')) {
                $result = $subscription->pause();
            } else {
                // Fallback implementation
                $subscription->status = 'paused';
                $result = $subscription->store();
            }
            
            return $result !== false;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error pausing subscription: ' . $e->getMessage(), [
                    'subscription_id' => $id,
                    'exception' => $e
                ]);
            }
            return false;
        }
    }

    /**
     * Resume a subscription
     *
     * @param int $id The subscription ID
     * @return bool True on success, false on failure
     */
    public function resume(int $id): bool {
        try {
            // Get the subscription
            $subscription = $this->get($id);
            if (!$subscription) {
                throw new \Exception('Subscription not found');
            }
            
            // Resume the subscription
            // Check if the method exists
            if (method_exists($subscription, 'resume')) {
                $result = $subscription->resume();
            } else {
                // Fallback implementation
                $subscription->status = 'active';
                $result = $subscription->store();
            }
            
            return $result !== false;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error resuming subscription: ' . $e->getMessage(), [
                    'subscription_id' => $id,
                    'exception' => $e
                ]);
            }
            return false;
        }
    }

    /**
     * Get subscription transactions
     *
     * @param \MeprSubscription $subscription The subscription
     * @return array The subscription transactions
     */
    public function getTransactions(\MeprSubscription $subscription): array {
        try {
            // Get the subscription transactions
            $transactions = $subscription->transactions();
            
            return $transactions;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error getting subscription transactions: ' . $e->getMessage(), [
                    'subscription_id' => $subscription->id,
                    'exception' => $e
                ]);
            }
            return [];
        }
    }

    /**
     * Check if a subscription is active
     *
     * @param \MeprSubscription $subscription The subscription
     * @return bool True if the subscription is active, false otherwise
     */
    public function isActive(\MeprSubscription $subscription): bool {
        try {
            // Check if the subscription is active
            if (method_exists($subscription, 'is_active')) {
                return $subscription->is_active();
            }
            
            // Fallback to checking status
            return $subscription->status == 'active';
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error checking if subscription is active: ' . $e->getMessage(), [
                    'subscription_id' => $subscription->id,
                    'exception' => $e
                ]);
            }
            return false;
        }
    }
}