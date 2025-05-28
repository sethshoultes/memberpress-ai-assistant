<?php
/**
 * Transaction Adapter
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Services\Adapters;

/**
 * Adapter for MemberPress Transactions
 */
class TransactionAdapter {
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
     * Get a transaction by ID
     *
     * @param int $id The transaction ID
     * @return \MeprTransaction|null The transaction or null if not found
     */
    public function get(int $id) {
        try {
            if (!class_exists('\MeprTransaction')) {
                throw new \Exception('MemberPress is not active');
            }

            // MPAI DEBUG: Log transaction creation attempt
            error_log('MPAI DEBUG: TransactionAdapter creating MeprTransaction with ID: ' . $id);
            
            $transaction = new \MeprTransaction($id);
            
            // MPAI DEBUG: Check transaction object properties
            error_log('MPAI DEBUG: TransactionAdapter MeprTransaction created - ID: ' . ($transaction->id ?? 'NULL') .
                     ', user_id: ' . ($transaction->user_id ?? 'NULL') .
                     ', product_id: ' . ($transaction->product_id ?? 'NULL'));
            
            // Check if the transaction exists
            if (!$transaction->id || $transaction->id == 0) {
                error_log('MPAI DEBUG: TransactionAdapter - Invalid transaction ID, returning null');
                return null;
            }
            
            return $transaction;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error getting transaction: ' . $e->getMessage(), [
                    'transaction_id' => $id,
                    'exception' => $e
                ]);
            }
            return null;
        }
    }

    /**
     * Get all transactions
     *
     * @param array $args Optional arguments
     * @return array Array of transactions
     */
    public function getAll(array $args = []): array {
        try {
            if (!class_exists('\MeprTransaction')) {
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
                'subscription_id' => null,
                'date_from' => null,
                'date_to' => null,
            ];

            // Merge with provided arguments
            $args = array_merge($defaults, $args);

            // Build query conditions
            $conditions = [];
            $query_params = [];

            // Base query
            $query = "SELECT * FROM {$GLOBALS['wpdb']->prefix}mepr_transactions WHERE 1=1";

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

            // Add subscription_id condition if provided
            if (!empty($args['subscription_id'])) {
                $conditions[] = "subscription_id = %d";
                $query_params[] = $args['subscription_id'];
            }

            // Add date_from condition if provided
            if (!empty($args['date_from'])) {
                $conditions[] = "created_at >= %s";
                $query_params[] = $args['date_from'];
            }

            // Add date_to condition if provided
            if (!empty($args['date_to'])) {
                $conditions[] = "created_at <= %s";
                $query_params[] = $args['date_to'];
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
            $transactions = [];

            // Convert results to MeprTransaction objects
            foreach ($results as $result) {
                // MPAI DEBUG: Log bulk transaction creation
                error_log('MPAI DEBUG: TransactionAdapter bulk creating MeprTransaction with ID: ' . $result->id);
                
                $transaction = new \MeprTransaction($result->id);
                
                // MPAI DEBUG: Check bulk transaction object
                error_log('MPAI DEBUG: TransactionAdapter bulk MeprTransaction - ID: ' . ($transaction->id ?? 'NULL') .
                         ', user_id: ' . ($transaction->user_id ?? 'NULL'));
                
                $transactions[] = $transaction;
            }

            return $transactions;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error getting transactions: ' . $e->getMessage(), [
                    'args' => $args,
                    'exception' => $e
                ]);
            }
            return [];
        }
    }

    /**
     * Create a new transaction
     *
     * @param array $data The transaction data
     * @return \MeprTransaction|null The created transaction or null on failure
     */
    public function create(array $data) {
        try {
            if (!class_exists('\MeprTransaction')) {
                throw new \Exception('MemberPress is not active');
            }

            // Required fields
            if (empty($data['user_id']) || empty($data['product_id'])) {
                throw new \Exception('User ID and Product ID are required');
            }

            // MPAI DEBUG: Log new transaction creation
            error_log('MPAI DEBUG: TransactionAdapter creating new empty MeprTransaction');
            
            // Create a new transaction
            $transaction = new \MeprTransaction();
            
            // MPAI DEBUG: Check new transaction object
            error_log('MPAI DEBUG: TransactionAdapter new MeprTransaction created - ID: ' . ($transaction->id ?? 'NULL'));
            
            // Set transaction properties
            if (isset($data['user_id'])) {
                $transaction->user_id = intval($data['user_id']);
            }
            
            if (isset($data['product_id'])) {
                $transaction->product_id = intval($data['product_id']);
            }
            
            if (isset($data['subscription_id'])) {
                $transaction->subscription_id = intval($data['subscription_id']);
            }
            
            if (isset($data['amount'])) {
                $transaction->amount = floatval($data['amount']);
            }
            
            if (isset($data['total'])) {
                $transaction->total = floatval($data['total']);
            }
            
            if (isset($data['tax_amount'])) {
                $transaction->tax_amount = floatval($data['tax_amount']);
            }
            
            if (isset($data['tax_rate'])) {
                $transaction->tax_rate = floatval($data['tax_rate']);
            }
            
            if (isset($data['tax_desc'])) {
                $transaction->tax_desc = sanitize_text_field($data['tax_desc']);
            }
            
            if (isset($data['tax_class'])) {
                $transaction->tax_class = sanitize_text_field($data['tax_class']);
            }
            
            if (isset($data['trans_num'])) {
                $transaction->trans_num = sanitize_text_field($data['trans_num']);
            } else {
                $transaction->trans_num = uniqid('mp-txn-');
            }
            
            if (isset($data['status'])) {
                $transaction->status = sanitize_text_field($data['status']);
            } else {
                $transaction->status = 'pending';
            }
            
            if (isset($data['gateway'])) {
                $transaction->gateway = sanitize_text_field($data['gateway']);
            }
            
            if (isset($data['created_at'])) {
                $transaction->created_at = sanitize_text_field($data['created_at']);
            } else {
                $transaction->created_at = date('Y-m-d H:i:s');
            }
            
            if (isset($data['expires_at'])) {
                $transaction->expires_at = sanitize_text_field($data['expires_at']);
            }
            
            // Save the transaction
            $transaction->store();
            
            return $transaction;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error creating transaction: ' . $e->getMessage(), [
                    'data' => $data,
                    'exception' => $e
                ]);
            }
            return null;
        }
    }

    /**
     * Update a transaction
     *
     * @param int $id The transaction ID
     * @param array $data The transaction data
     * @return \MeprTransaction|null The updated transaction or null on failure
     */
    public function update(int $id, array $data) {
        try {
            // Get the transaction
            $transaction = $this->get($id);
            if (!$transaction) {
                throw new \Exception('Transaction not found');
            }
            
            // Update transaction properties
            if (isset($data['user_id'])) {
                $transaction->user_id = intval($data['user_id']);
            }
            
            if (isset($data['product_id'])) {
                $transaction->product_id = intval($data['product_id']);
            }
            
            if (isset($data['subscription_id'])) {
                $transaction->subscription_id = intval($data['subscription_id']);
            }
            
            if (isset($data['amount'])) {
                $transaction->amount = floatval($data['amount']);
            }
            
            if (isset($data['total'])) {
                $transaction->total = floatval($data['total']);
            }
            
            if (isset($data['tax_amount'])) {
                $transaction->tax_amount = floatval($data['tax_amount']);
            }
            
            if (isset($data['tax_rate'])) {
                $transaction->tax_rate = floatval($data['tax_rate']);
            }
            
            if (isset($data['tax_desc'])) {
                $transaction->tax_desc = sanitize_text_field($data['tax_desc']);
            }
            
            if (isset($data['tax_class'])) {
                $transaction->tax_class = sanitize_text_field($data['tax_class']);
            }
            
            if (isset($data['trans_num'])) {
                $transaction->trans_num = sanitize_text_field($data['trans_num']);
            }
            
            if (isset($data['status'])) {
                $transaction->status = sanitize_text_field($data['status']);
            }
            
            if (isset($data['gateway'])) {
                $transaction->gateway = sanitize_text_field($data['gateway']);
            }
            
            if (isset($data['expires_at'])) {
                $transaction->expires_at = sanitize_text_field($data['expires_at']);
            }
            
            // Save the transaction
            $transaction->store();
            
            return $transaction;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error updating transaction: ' . $e->getMessage(), [
                    'transaction_id' => $id,
                    'data' => $data,
                    'exception' => $e
                ]);
            }
            return null;
        }
    }

    /**
     * Delete a transaction
     *
     * @param int $id The transaction ID
     * @return bool True on success, false on failure
     */
    public function delete(int $id): bool {
        try {
            // Get the transaction
            $transaction = $this->get($id);
            if (!$transaction) {
                throw new \Exception('Transaction not found');
            }
            
            // Delete the transaction
            $result = $transaction->destroy();
            
            return $result !== false;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error deleting transaction: ' . $e->getMessage(), [
                    'transaction_id' => $id,
                    'exception' => $e
                ]);
            }
            return false;
        }
    }

    /**
     * Complete a transaction
     *
     * @param int $id The transaction ID
     * @return bool True on success, false on failure
     */
    public function complete(int $id): bool {
        try {
            // Get the transaction
            $transaction = $this->get($id);
            if (!$transaction) {
                throw new \Exception('Transaction not found');
            }
            
            // Complete the transaction
            if (method_exists($transaction, 'complete')) {
                $result = $transaction->complete();
            } else {
                // Fallback implementation
                $transaction->status = 'complete';
                $result = $transaction->store();
            }
            
            return $result !== false;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error completing transaction: ' . $e->getMessage(), [
                    'transaction_id' => $id,
                    'exception' => $e
                ]);
            }
            return false;
        }
    }

    /**
     * Refund a transaction
     *
     * @param int $id The transaction ID
     * @return bool True on success, false on failure
     */
    public function refund(int $id): bool {
        try {
            // Get the transaction
            $transaction = $this->get($id);
            if (!$transaction) {
                throw new \Exception('Transaction not found');
            }
            
            // Refund the transaction
            if (method_exists($transaction, 'refund')) {
                $result = $transaction->refund();
            } else {
                // Fallback implementation
                $transaction->status = 'refunded';
                $result = $transaction->store();
            }
            
            return $result !== false;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error refunding transaction: ' . $e->getMessage(), [
                    'transaction_id' => $id,
                    'exception' => $e
                ]);
            }
            return false;
        }
    }

    /**
     * Get transaction subscription
     *
     * @param \MeprTransaction $transaction The transaction
     * @return \MeprSubscription|null The subscription or null if not found
     */
    public function getSubscription(\MeprTransaction $transaction) {
        try {
            if (!$transaction->subscription_id) {
                return null;
            }
            
            // MPAI DEBUG: Log subscription creation in transaction context
            error_log('MPAI DEBUG: TransactionAdapter creating MeprSubscription with ID: ' . $transaction->subscription_id);
            
            // Get the subscription
            $subscription = new \MeprSubscription($transaction->subscription_id);
            
            // MPAI DEBUG: Check subscription object in transaction context
            error_log('MPAI DEBUG: TransactionAdapter MeprSubscription created - ID: ' . ($subscription->id ?? 'NULL') .
                     ', user_id: ' . ($subscription->user_id ?? 'NULL'));
            
            // Check if the subscription exists
            if (!$subscription->id || $subscription->id == 0) {
                return null;
            }
            
            return $subscription;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error getting transaction subscription: ' . $e->getMessage(), [
                    'transaction_id' => $transaction->id,
                    'subscription_id' => $transaction->subscription_id,
                    'exception' => $e
                ]);
            }
            return null;
        }
    }

    /**
     * Get transaction product
     *
     * @param \MeprTransaction $transaction The transaction
     * @return \MeprProduct|null The product or null if not found
     */
    public function getProduct(\MeprTransaction $transaction) {
        try {
            if (!$transaction->product_id) {
                return null;
            }
            
            // Get the product
            $product = new \MeprProduct($transaction->product_id);
            
            // Check if the product exists
            if (!$product->ID || $product->ID == 0) {
                return null;
            }
            
            return $product;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error getting transaction product: ' . $e->getMessage(), [
                    'transaction_id' => $transaction->id,
                    'product_id' => $transaction->product_id,
                    'exception' => $e
                ]);
            }
            return null;
        }
    }

    /**
     * Get transaction user
     *
     * @param \MeprTransaction $transaction The transaction
     * @return \MeprUser|null The user or null if not found
     */
    public function getUser(\MeprTransaction $transaction) {
        try {
            if (!$transaction->user_id) {
                return null;
            }
            
            // MPAI DEBUG: Log user creation in transaction context
            error_log('MPAI DEBUG: TransactionAdapter creating MeprUser with ID: ' . $transaction->user_id);
            
            // Get the user
            $user = new \MeprUser($transaction->user_id);
            
            // MPAI DEBUG: Check user object in transaction context
            error_log('MPAI DEBUG: TransactionAdapter MeprUser created - ID: ' . ($user->ID ?? 'NULL') .
                     ', user_login: ' . ($user->user_login ?? 'NULL'));
            
            // Check if the user exists
            if (!$user->ID || $user->ID == 0) {
                return null;
            }
            
            return $user;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error getting transaction user: ' . $e->getMessage(), [
                    'transaction_id' => $transaction->id,
                    'user_id' => $transaction->user_id,
                    'exception' => $e
                ]);
            }
            return null;
        }
    }
}