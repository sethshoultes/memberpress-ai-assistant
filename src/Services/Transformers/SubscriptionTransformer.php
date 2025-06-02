<?php
/**
 * Subscription Transformer
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Services\Transformers;

use MemberpressAiAssistant\Utilities\LoggingUtility;

/**
 * Transformer for MemberPress Subscriptions
 */
class SubscriptionTransformer {
    /**
     * Transform a MeprSubscription object to an array
     *
     * @param \MeprSubscription $subscription The subscription to transform
     * @return array The transformed subscription data
     */
    public function transform(\MeprSubscription $subscription): array {
        $data = [
            'id' => $subscription->id,
            'user_id' => $subscription->user_id,
            'product_id' => $subscription->product_id,
            'coupon_id' => $subscription->coupon_id,
            'gateway' => $subscription->gateway,
            'price' => $this->getPrice($subscription),
            'period' => $subscription->period,
            'period_type' => $subscription->period_type,
            'period_formatted' => $this->formatPeriod($subscription->period, $subscription->period_type),
            'limit_cycles' => $subscription->limit_cycles,
            'limit_cycles_num' => $subscription->limit_cycles_num,
            'limit_cycles_action' => $subscription->limit_cycles_action,
            'limit_cycles_expires_after' => $subscription->limit_cycles_expires_after,
            'limit_cycles_expires_type' => $subscription->limit_cycles_expires_type,
            'prorated' => $subscription->prorated,
            'status' => $subscription->status,
            'status_formatted' => $this->formatStatus($subscription->status),
            'created_at' => $subscription->created_at,
            'cc_last4' => $this->getCcLast4($subscription),
            'cc_exp_month' => $this->getCcExpMonth($subscription),
            'cc_exp_year' => $this->getCcExpYear($subscription),
            'trial' => $subscription->trial,
            'trial_days' => $subscription->trial_days,
            'trial_amount' => $subscription->trial_amount,
            'is_active' => $this->isActive($subscription),
            'is_cancelled' => $this->isCancelled($subscription),
            'is_suspended' => $this->isSuspended($subscription),
            'is_lifetime' => $this->isLifetime($subscription),
            'is_expired' => $this->isExpired($subscription),
            'expires_at' => $this->getExpiresAt($subscription),
            'next_billing_at' => $this->getNextBillingAt($subscription),
            'trial_expires_at' => $this->getTrialExpiresAt($subscription),
            'transactions' => $this->getTransactions($subscription),
            'product_name' => $this->getProductName($subscription),
            'user_name' => $this->getUserName($subscription),
            'user_email' => $this->getUserEmail($subscription),
            'payment_method' => $this->getPaymentMethod($subscription),
            'total_payments' => $this->getTotalPayments($subscription),
            'completed_payments' => $this->getCompletedPayments($subscription),
        ];

        return $data;
    }

    /**
     * Get the subscription price
     *
     * @param \MeprSubscription $subscription The subscription
     * @return float The subscription price
     */
    protected function getPrice(\MeprSubscription $subscription): float {
        return (float)$subscription->price;
    }

    /**
     * Format the period
     *
     * @param int $period The period
     * @param string $period_type The period type
     * @return string The formatted period
     */
    protected function formatPeriod(int $period, string $period_type): string {
        if ($period_type === 'lifetime') {
            return 'Lifetime';
        }

        if ($period == 1) {
            switch ($period_type) {
                case 'days':
                    return 'Daily';
                case 'weeks':
                    return 'Weekly';
                case 'months':
                    return 'Monthly';
                case 'years':
                    return 'Yearly';
                default:
                    return ucfirst($period_type);
            }
        } else {
            return $period . ' ' . ucfirst($period_type);
        }
    }

    /**
     * Format the status
     *
     * @param string $status The status
     * @return string The formatted status
     */
    protected function formatStatus(string $status): string {
        switch ($status) {
            case 'active':
                return 'Active';
            case 'cancelled':
                return 'Cancelled';
            case 'suspended':
                return 'Suspended';
            case 'pending':
                return 'Pending';
            case 'complete':
                return 'Complete';
            default:
                return ucfirst($status);
        }
    }

    /**
     * Get the subscription credit card last 4 digits
     *
     * @param \MeprSubscription $subscription The subscription
     * @return string The subscription credit card last 4 digits
     */
    protected function getCcLast4(\MeprSubscription $subscription): string {
        return $subscription->cc_last4 ?? '';
    }

    /**
     * Get the subscription credit card expiration month
     *
     * @param \MeprSubscription $subscription The subscription
     * @return string The subscription credit card expiration month
     */
    protected function getCcExpMonth(\MeprSubscription $subscription): string {
        return $subscription->cc_exp_month ?? '';
    }

    /**
     * Get the subscription credit card expiration year
     *
     * @param \MeprSubscription $subscription The subscription
     * @return string The subscription credit card expiration year
     */
    protected function getCcExpYear(\MeprSubscription $subscription): string {
        return $subscription->cc_exp_year ?? '';
    }

    /**
     * Check if the subscription is active
     *
     * @param \MeprSubscription $subscription The subscription
     * @return bool True if the subscription is active, false otherwise
     */
    protected function isActive(\MeprSubscription $subscription): bool {
        try {
            if (method_exists($subscription, 'is_active')) {
                return $subscription->is_active();
            }
            
            return $subscription->status === 'active';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if the subscription is cancelled
     *
     * @param \MeprSubscription $subscription The subscription
     * @return bool True if the subscription is cancelled, false otherwise
     */
    protected function isCancelled(\MeprSubscription $subscription): bool {
        try {
            if (method_exists($subscription, 'is_cancelled')) {
                return $subscription->is_cancelled();
            }
            
            return $subscription->status === 'cancelled';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if the subscription is suspended
     *
     * @param \MeprSubscription $subscription The subscription
     * @return bool True if the subscription is suspended, false otherwise
     */
    protected function isSuspended(\MeprSubscription $subscription): bool {
        try {
            if (method_exists($subscription, 'is_suspended')) {
                return $subscription->is_suspended();
            }
            
            return $subscription->status === 'suspended';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if the subscription is lifetime
     *
     * @param \MeprSubscription $subscription The subscription
     * @return bool True if the subscription is lifetime, false otherwise
     */
    protected function isLifetime(\MeprSubscription $subscription): bool {
        try {
            if (method_exists($subscription, 'is_lifetime')) {
                return $subscription->is_lifetime();
            }
            
            return $subscription->period_type === 'lifetime';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if the subscription is expired
     *
     * @param \MeprSubscription $subscription The subscription
     * @return bool True if the subscription is expired, false otherwise
     */
    protected function isExpired(\MeprSubscription $subscription): bool {
        try {
            if (method_exists($subscription, 'is_expired')) {
                return $subscription->is_expired();
            }
            
            $expires_at = $this->getExpiresAt($subscription);
            
            if (empty($expires_at)) {
                return false;
            }
            
            return strtotime($expires_at) < time();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the subscription expires at date
     *
     * @param \MeprSubscription $subscription The subscription
     * @return string|null The subscription expires at date
     */
    protected function getExpiresAt(\MeprSubscription $subscription) {
        try {
            if (method_exists($subscription, 'get_expires_at')) {
                return $subscription->get_expires_at();
            }
            
            return $subscription->expires_at ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get the subscription next billing at date
     *
     * @param \MeprSubscription $subscription The subscription
     * @return string|null The subscription next billing at date
     */
    protected function getNextBillingAt(\MeprSubscription $subscription) {
        try {
            if (method_exists($subscription, 'get_next_billing_at')) {
                return $subscription->get_next_billing_at();
            }
            
            return $subscription->next_billing_at ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get the subscription trial expires at date
     *
     * @param \MeprSubscription $subscription The subscription
     * @return string|null The subscription trial expires at date
     */
    protected function getTrialExpiresAt(\MeprSubscription $subscription) {
        try {
            if (method_exists($subscription, 'get_trial_expires_at')) {
                return $subscription->get_trial_expires_at();
            }
            
            if (!$subscription->trial) {
                return null;
            }
            
            $created_at = strtotime($subscription->created_at);
            $trial_days = (int)$subscription->trial_days;
            
            if ($created_at && $trial_days > 0) {
                return date('Y-m-d H:i:s', $created_at + ($trial_days * DAY_IN_SECONDS));
            }
            
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get the subscription transactions
     *
     * @param \MeprSubscription $subscription The subscription
     * @return array The subscription transactions
     */
    protected function getTransactions(\MeprSubscription $subscription): array {
        try {
            $transactions = $subscription->transactions();
            
            if (!is_array($transactions)) {
                return [];
            }
            
            $data = [];
            
            foreach ($transactions as $transaction) {
                $data[] = [
                    'id' => $transaction->id,
                    'status' => $transaction->status,
                    'amount' => $transaction->amount,
                    'total' => $transaction->total,
                    'tax_amount' => $transaction->tax_amount,
                    'tax_rate' => $transaction->tax_rate,
                    'created_at' => $transaction->created_at,
                    'expires_at' => $transaction->expires_at,
                ];
            }
            
            return $data;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get the subscription product name
     *
     * @param \MeprSubscription $subscription The subscription
     * @return string The subscription product name
     */
    protected function getProductName(\MeprSubscription $subscription): string {
        try {
            if (method_exists($subscription, 'product')) {
                $product = $subscription->product();
                
                if ($product && isset($product->post_title)) {
                    return $product->post_title;
                }
            }
            
            // Fallback to getting the product directly
            if (class_exists('\MeprProduct') && $subscription->product_id) {
                $product = new \MeprProduct($subscription->product_id);
                
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
     * Get the subscription user name
     *
     * @param \MeprSubscription $subscription The subscription
     * @return string The subscription user name
     */
    protected function getUserName(\MeprSubscription $subscription): string {
        try {
            if (method_exists($subscription, 'user')) {
                $user = $subscription->user();
                
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
            if (class_exists('\MeprUser') && $subscription->user_id) {
                // Log user creation attempt
                LoggingUtility::debug('SubscriptionTransformer creating MeprUser with ID: ' . $subscription->user_id);
                
                $user = new \MeprUser($subscription->user_id);
                
                // Check if user object has valid properties
                if ($user) {
                    LoggingUtility::debug('MeprUser created - ID: ' . ($user->ID ?? 'NULL') .
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
                    LoggingUtility::debug('MeprUser creation failed for user_id: ' . $subscription->user_id);
                }
            }
            
            return '';
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Get the subscription user email
     *
     * @param \MeprSubscription $subscription The subscription
     * @return string The subscription user email
     */
    protected function getUserEmail(\MeprSubscription $subscription): string {
        try {
            if (method_exists($subscription, 'user')) {
                $user = $subscription->user();
                
                if ($user && isset($user->user_email)) {
                    return $user->user_email;
                }
            }
            
            // Fallback to getting the user directly
            if (class_exists('\MeprUser') && $subscription->user_id) {
                // Log user creation attempt for email
                LoggingUtility::debug('SubscriptionTransformer creating MeprUser for email with ID: ' . $subscription->user_id);
                
                $user = new \MeprUser($subscription->user_id);
                
                // Check if user object has valid email
                if ($user) {
                    LoggingUtility::debug('MeprUser email check - ID: ' . ($user->ID ?? 'NULL') .
                             ', user_email: ' . ($user->user_email ?? 'NULL'));
                    
                    if (isset($user->user_email)) {
                        return $user->user_email;
                    }
                } else {
                    LoggingUtility::debug('MeprUser creation failed for email lookup, user_id: ' . $subscription->user_id);
                }
            }
            
            return '';
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Get the subscription payment method
     *
     * @param \MeprSubscription $subscription The subscription
     * @return string The subscription payment method
     */
    protected function getPaymentMethod(\MeprSubscription $subscription): string {
        try {
            if (method_exists($subscription, 'payment_method')) {
                $payment_method = $subscription->payment_method();
                
                if ($payment_method && isset($payment_method->name)) {
                    return $payment_method->name;
                }
            }
            
            // Fallback to gateway name
            return $subscription->gateway ?? '';
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Get the subscription total payments
     *
     * @param \MeprSubscription $subscription The subscription
     * @return int The subscription total payments
     */
    protected function getTotalPayments(\MeprSubscription $subscription): int {
        try {
            $transactions = $this->getTransactions($subscription);
            
            return count($transactions);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get the subscription completed payments
     *
     * @param \MeprSubscription $subscription The subscription
     * @return int The subscription completed payments
     */
    protected function getCompletedPayments(\MeprSubscription $subscription): int {
        try {
            $transactions = $this->getTransactions($subscription);
            $completed = 0;
            
            foreach ($transactions as $transaction) {
                if ($transaction['status'] === 'complete') {
                    $completed++;
                }
            }
            
            return $completed;
        } catch (\Exception $e) {
            return 0;
        }
    }
}