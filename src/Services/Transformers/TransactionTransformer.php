<?php
/**
 * Transaction Transformer
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Services\Transformers;

/**
 * Transformer for MemberPress Transactions
 */
class TransactionTransformer {
    /**
     * Transform a MeprTransaction object to an array
     *
     * @param \MeprTransaction $transaction The transaction to transform
     * @return array The transformed transaction data
     */
    public function transform(\MeprTransaction $transaction): array {
        $data = [
            'id' => $transaction->id,
            'user_id' => $transaction->user_id,
            'product_id' => $transaction->product_id,
            'subscription_id' => $transaction->subscription_id,
            'amount' => $this->getAmount($transaction),
            'total' => $this->getTotal($transaction),
            'tax_amount' => $this->getTaxAmount($transaction),
            'tax_rate' => $this->getTaxRate($transaction),
            'tax_desc' => $transaction->tax_desc,
            'tax_class' => $transaction->tax_class,
            'gateway' => $transaction->gateway,
            'trans_num' => $transaction->trans_num,
            'status' => $transaction->status,
            'status_formatted' => $this->formatStatus($transaction->status),
            'created_at' => $transaction->created_at,
            'expires_at' => $transaction->expires_at,
            'corporate_account_id' => $transaction->corporate_account_id,
            'parent_transaction_id' => $transaction->parent_transaction_id,
            'txn_type' => $transaction->txn_type,
            'prorated' => $transaction->prorated,
            'is_primary' => $this->isPrimary($transaction),
            'is_expired' => $this->isExpired($transaction),
            'is_complete' => $this->isComplete($transaction),
            'is_refunded' => $this->isRefunded($transaction),
            'is_pending' => $this->isPending($transaction),
            'is_failed' => $this->isFailed($transaction),
            'product_name' => $this->getProductName($transaction),
            'user_name' => $this->getUserName($transaction),
            'user_email' => $this->getUserEmail($transaction),
            'payment_method' => $this->getPaymentMethod($transaction),
            'subscription' => $this->getSubscription($transaction),
            'coupon' => $this->getCoupon($transaction),
        ];

        return $data;
    }

    /**
     * Get the transaction amount
     *
     * @param \MeprTransaction $transaction The transaction
     * @return float The transaction amount
     */
    protected function getAmount(\MeprTransaction $transaction): float {
        return (float)$transaction->amount;
    }

    /**
     * Get the transaction total
     *
     * @param \MeprTransaction $transaction The transaction
     * @return float The transaction total
     */
    protected function getTotal(\MeprTransaction $transaction): float {
        return (float)$transaction->total;
    }

    /**
     * Get the transaction tax amount
     *
     * @param \MeprTransaction $transaction The transaction
     * @return float The transaction tax amount
     */
    protected function getTaxAmount(\MeprTransaction $transaction): float {
        return (float)$transaction->tax_amount;
    }

    /**
     * Get the transaction tax rate
     *
     * @param \MeprTransaction $transaction The transaction
     * @return float The transaction tax rate
     */
    protected function getTaxRate(\MeprTransaction $transaction): float {
        return (float)$transaction->tax_rate;
    }

    /**
     * Format the status
     *
     * @param string $status The status
     * @return string The formatted status
     */
    protected function formatStatus(string $status): string {
        switch ($status) {
            case 'complete':
                return 'Complete';
            case 'pending':
                return 'Pending';
            case 'failed':
                return 'Failed';
            case 'refunded':
                return 'Refunded';
            default:
                return ucfirst($status);
        }
    }

    /**
     * Check if the transaction is primary
     *
     * @param \MeprTransaction $transaction The transaction
     * @return bool True if the transaction is primary, false otherwise
     */
    protected function isPrimary(\MeprTransaction $transaction): bool {
        try {
            if (method_exists($transaction, 'is_primary')) {
                return $transaction->is_primary();
            }
            
            return empty($transaction->parent_transaction_id);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if the transaction is expired
     *
     * @param \MeprTransaction $transaction The transaction
     * @return bool True if the transaction is expired, false otherwise
     */
    protected function isExpired(\MeprTransaction $transaction): bool {
        try {
            if (method_exists($transaction, 'is_expired')) {
                return $transaction->is_expired();
            }
            
            if (empty($transaction->expires_at)) {
                return false;
            }
            
            return strtotime($transaction->expires_at) < time();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if the transaction is complete
     *
     * @param \MeprTransaction $transaction The transaction
     * @return bool True if the transaction is complete, false otherwise
     */
    protected function isComplete(\MeprTransaction $transaction): bool {
        try {
            if (method_exists($transaction, 'is_complete')) {
                return $transaction->is_complete();
            }
            
            return $transaction->status === 'complete';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if the transaction is refunded
     *
     * @param \MeprTransaction $transaction The transaction
     * @return bool True if the transaction is refunded, false otherwise
     */
    protected function isRefunded(\MeprTransaction $transaction): bool {
        try {
            if (method_exists($transaction, 'is_refunded')) {
                return $transaction->is_refunded();
            }
            
            return $transaction->status === 'refunded';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if the transaction is pending
     *
     * @param \MeprTransaction $transaction The transaction
     * @return bool True if the transaction is pending, false otherwise
     */
    protected function isPending(\MeprTransaction $transaction): bool {
        try {
            if (method_exists($transaction, 'is_pending')) {
                return $transaction->is_pending();
            }
            
            return $transaction->status === 'pending';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if the transaction is failed
     *
     * @param \MeprTransaction $transaction The transaction
     * @return bool True if the transaction is failed, false otherwise
     */
    protected function isFailed(\MeprTransaction $transaction): bool {
        try {
            if (method_exists($transaction, 'is_failed')) {
                return $transaction->is_failed();
            }
            
            return $transaction->status === 'failed';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the transaction product name
     *
     * @param \MeprTransaction $transaction The transaction
     * @return string The transaction product name
     */
    protected function getProductName(\MeprTransaction $transaction): string {
        try {
            if (method_exists($transaction, 'product')) {
                $product = $transaction->product();
                
                if ($product && isset($product->post_title)) {
                    return $product->post_title;
                }
            }
            
            // Fallback to getting the product directly
            if (class_exists('\MeprProduct') && $transaction->product_id) {
                $product = new \MeprProduct($transaction->product_id);
                
                if ($product && isset($product->post_title)) {
                    return $product->post_title;
                }
            }
            
            return '';
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Get the transaction user name
     *
     * @param \MeprTransaction $transaction The transaction
     * @return string The transaction user name
     */
    protected function getUserName(\MeprTransaction $transaction): string {
        try {
            if (method_exists($transaction, 'user')) {
                $user = $transaction->user();
                
                if ($user) {
                    $first_name = $user->first_name ?? '';
                    $last_name = $user->last_name ?? '';
                    $name = trim($first_name . ' ' . $last_name);
                    
                    if (!empty($name)) {
                        return $name;
                    }
                    
                    return $user->user_login ?? '';
                }
            }
            
            // Fallback to getting the user directly
            if (class_exists('\MeprUser') && $transaction->user_id) {
                // MPAI DEBUG: Log user creation attempt
                error_log('MPAI DEBUG: TransactionTransformer creating MeprUser with ID: ' . $transaction->user_id);
                
                $user = new \MeprUser($transaction->user_id);
                
                // MPAI DEBUG: Check if user object has valid properties
                if ($user) {
                    error_log('MPAI DEBUG: MeprUser created - ID: ' . ($user->ID ?? 'NULL') .
                             ', first_name: ' . ($user->first_name ?? 'NULL') .
                             ', last_name: ' . ($user->last_name ?? 'NULL') .
                             ', user_login: ' . ($user->user_login ?? 'NULL'));
                    
                    $first_name = $user->first_name ?? '';
                    $last_name = $user->last_name ?? '';
                    $name = trim($first_name . ' ' . $last_name);
                    
                    if (!empty($name)) {
                        return $name;
                    }
                    
                    return $user->user_login ?? '';
                } else {
                    error_log('MPAI DEBUG: MeprUser creation failed for user_id: ' . $transaction->user_id);
                }
            }
            
            return '';
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Get the transaction user email
     *
     * @param \MeprTransaction $transaction The transaction
     * @return string The transaction user email
     */
    protected function getUserEmail(\MeprTransaction $transaction): string {
        try {
            if (method_exists($transaction, 'user')) {
                $user = $transaction->user();
                
                if ($user && isset($user->user_email)) {
                    return $user->user_email;
                }
            }
            
            // Fallback to getting the user directly
            if (class_exists('\MeprUser') && $transaction->user_id) {
                // MPAI DEBUG: Log user creation attempt for email
                error_log('MPAI DEBUG: TransactionTransformer creating MeprUser for email with ID: ' . $transaction->user_id);
                
                $user = new \MeprUser($transaction->user_id);
                
                // MPAI DEBUG: Check if user object has valid email
                if ($user) {
                    error_log('MPAI DEBUG: MeprUser email check - ID: ' . ($user->ID ?? 'NULL') .
                             ', user_email: ' . ($user->user_email ?? 'NULL'));
                    
                    if (isset($user->user_email)) {
                        return $user->user_email;
                    }
                } else {
                    error_log('MPAI DEBUG: MeprUser creation failed for email lookup, user_id: ' . $transaction->user_id);
                }
            }
            
            return '';
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Get the transaction payment method
     *
     * @param \MeprTransaction $transaction The transaction
     * @return string The transaction payment method
     */
    protected function getPaymentMethod(\MeprTransaction $transaction): string {
        try {
            if (method_exists($transaction, 'payment_method')) {
                $payment_method = $transaction->payment_method();
                
                if ($payment_method && isset($payment_method->name)) {
                    return $payment_method->name;
                }
            }
            
            // Fallback to gateway name
            return $transaction->gateway ?? '';
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Get the transaction subscription
     *
     * @param \MeprTransaction $transaction The transaction
     * @return array|null The transaction subscription
     */
    protected function getSubscription(\MeprTransaction $transaction) {
        try {
            if (!$transaction->subscription_id) {
                return null;
            }
            
            if (method_exists($transaction, 'subscription')) {
                $subscription = $transaction->subscription();
                
                if ($subscription) {
                    return [
                        'id' => $subscription->id,
                        'status' => $subscription->status,
                        'created_at' => $subscription->created_at,
                    ];
                }
            }
            
            // Fallback to getting the subscription directly
            if (class_exists('\MeprSubscription')) {
                $subscription = new \MeprSubscription($transaction->subscription_id);
                
                if ($subscription && $subscription->id) {
                    return [
                        'id' => $subscription->id,
                        'status' => $subscription->status,
                        'created_at' => $subscription->created_at,
                    ];
                }
            }
            
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get the transaction coupon
     *
     * @param \MeprTransaction $transaction The transaction
     * @return array|null The transaction coupon
     */
    protected function getCoupon(\MeprTransaction $transaction) {
        try {
            if (method_exists($transaction, 'coupon')) {
                $coupon = $transaction->coupon();
                
                if ($coupon) {
                    return [
                        'id' => $coupon->ID,
                        'code' => $coupon->post_title,
                        'discount' => $this->getCouponDiscount($coupon),
                        'discount_type' => $this->getCouponDiscountType($coupon),
                    ];
                }
            }
            
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get the coupon discount
     *
     * @param \MeprCoupon $coupon The coupon
     * @return float The coupon discount
     */
    protected function getCouponDiscount(\MeprCoupon $coupon): float {
        return (float)$coupon->discount;
    }

    /**
     * Get the coupon discount type
     *
     * @param \MeprCoupon $coupon The coupon
     * @return string The coupon discount type
     */
    protected function getCouponDiscountType(\MeprCoupon $coupon): string {
        return $coupon->discount_type;
    }
}