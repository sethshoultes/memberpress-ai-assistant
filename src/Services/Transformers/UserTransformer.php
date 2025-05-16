<?php
/**
 * User Transformer
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Services\Transformers;

/**
 * Transformer for MemberPress Users (Members)
 */
class UserTransformer {
    /**
     * Transform a MeprUser object to an array
     *
     * @param \MeprUser $user The user to transform
     * @return array The transformed user data
     */
    public function transform(\MeprUser $user): array {
        $data = [
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'first_name' => $this->getFirstName($user),
            'last_name' => $this->getLastName($user),
            'display_name' => $user->display_name,
            'registered_date' => $user->user_registered,
            'status' => $this->getStatus($user),
            'active_memberships' => $this->getActiveMemberships($user),
            'expired_memberships' => $this->getExpiredMemberships($user),
            'subscriptions' => $this->getSubscriptions($user),
            'transactions' => $this->getTransactions($user),
            'address' => $this->getAddress($user),
            'custom_fields' => $this->getCustomFields($user),
            'login_count' => $this->getLoginCount($user),
            'last_login' => $this->getLastLogin($user),
            'is_active' => $this->isActive($user),
        ];

        return $data;
    }

    /**
     * Get the user's first name
     *
     * @param \MeprUser $user The user
     * @return string The user's first name
     */
    protected function getFirstName(\MeprUser $user): string {
        return $user->first_name;
    }

    /**
     * Get the user's last name
     *
     * @param \MeprUser $user The user
     * @return string The user's last name
     */
    protected function getLastName(\MeprUser $user): string {
        return $user->last_name;
    }

    /**
     * Get the user's status
     *
     * @param \MeprUser $user The user
     * @return string The user's status
     */
    protected function getStatus(\MeprUser $user): string {
        if (!function_exists('get_user_meta')) {
            return 'unknown';
        }
        
        $status = get_user_meta($user->ID, '_mepr_user_status', true);
        
        return !empty($status) ? $status : 'active';
    }

    /**
     * Get the user's active memberships
     *
     * @param \MeprUser $user The user
     * @return array The user's active memberships
     */
    protected function getActiveMemberships(\MeprUser $user): array {
        try {
            if (method_exists($user, 'active_product_subscriptions')) {
                $memberships = $user->active_product_subscriptions('ids');
                return is_array($memberships) ? $memberships : [];
            }
            
            return [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get the user's expired memberships
     *
     * @param \MeprUser $user The user
     * @return array The user's expired memberships
     */
    protected function getExpiredMemberships(\MeprUser $user): array {
        try {
            if (method_exists($user, 'expired_product_subscriptions')) {
                $memberships = $user->expired_product_subscriptions('ids');
                return is_array($memberships) ? $memberships : [];
            }
            
            return [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get the user's subscriptions
     *
     * @param \MeprUser $user The user
     * @return array The user's subscriptions
     */
    protected function getSubscriptions(\MeprUser $user): array {
        try {
            $subscriptions = $user->subscriptions();
            
            if (!is_array($subscriptions)) {
                return [];
            }
            
            $data = [];
            
            foreach ($subscriptions as $subscription) {
                $data[] = [
                    'id' => $subscription->id,
                    'status' => $subscription->status,
                    'product_id' => $subscription->product_id,
                    'created_at' => $subscription->created_at,
                    'expires_at' => $this->getSubscriptionExpiresAt($subscription),
                    'active' => $this->isSubscriptionActive($subscription),
                ];
            }
            
            return $data;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get the subscription expires at date
     *
     * @param \MeprSubscription $subscription The subscription
     * @return string|null The subscription expires at date
     */
    protected function getSubscriptionExpiresAt(\MeprSubscription $subscription) {
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
     * Check if the subscription is active
     *
     * @param \MeprSubscription $subscription The subscription
     * @return bool True if the subscription is active, false otherwise
     */
    protected function isSubscriptionActive(\MeprSubscription $subscription): bool {
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
     * Get the user's transactions
     *
     * @param \MeprUser $user The user
     * @return array The user's transactions
     */
    protected function getTransactions(\MeprUser $user): array {
        try {
            $transactions = $user->transactions();
            
            if (!is_array($transactions)) {
                return [];
            }
            
            $data = [];
            
            foreach ($transactions as $transaction) {
                $data[] = [
                    'id' => $transaction->id,
                    'status' => $transaction->status,
                    'product_id' => $transaction->product_id,
                    'amount' => $transaction->amount,
                    'total' => $transaction->total,
                    'tax_amount' => $transaction->tax_amount,
                    'tax_rate' => $transaction->tax_rate,
                    'created_at' => $transaction->created_at,
                    'expires_at' => $transaction->expires_at,
                    'subscription_id' => $transaction->subscription_id,
                ];
            }
            
            return $data;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get the user's address
     *
     * @param \MeprUser $user The user
     * @return array The user's address
     */
    protected function getAddress(\MeprUser $user): array {
        if (!function_exists('get_user_meta')) {
            return [];
        }
        
        return [
            'address_1' => get_user_meta($user->ID, 'mepr-address-one', true),
            'address_2' => get_user_meta($user->ID, 'mepr-address-two', true),
            'city' => get_user_meta($user->ID, 'mepr-address-city', true),
            'state' => get_user_meta($user->ID, 'mepr-address-state', true),
            'zip' => get_user_meta($user->ID, 'mepr-address-zip', true),
            'country' => get_user_meta($user->ID, 'mepr-address-country', true),
        ];
    }

    /**
     * Get the user's custom fields
     *
     * @param \MeprUser $user The user
     * @return array The user's custom fields
     */
    protected function getCustomFields(\MeprUser $user): array {
        if (!function_exists('get_user_meta')) {
            return [];
        }
        
        // Get all user meta
        $user_meta = get_user_meta($user->ID);
        
        // Filter out standard fields
        $standard_fields = [
            'first_name',
            'last_name',
            'nickname',
            'description',
            'rich_editing',
            'syntax_highlighting',
            'comment_shortcuts',
            'admin_color',
            'use_ssl',
            'show_admin_bar_front',
            'locale',
            'wp_capabilities',
            'wp_user_level',
            'dismissed_wp_pointers',
            'show_welcome_panel',
            'session_tokens',
            'wp_dashboard_quick_press_last_post_id',
            'wp_user-settings',
            'wp_user-settings-time',
            'mepr-address-one',
            'mepr-address-two',
            'mepr-address-city',
            'mepr-address-state',
            'mepr-address-zip',
            'mepr-address-country',
            '_mepr_user_status',
        ];
        
        $custom_fields = [];
        
        foreach ($user_meta as $key => $value) {
            // Skip standard fields and MemberPress internal fields
            if (in_array($key, $standard_fields) || strpos($key, '_mepr_') === 0) {
                continue;
            }
            
            // Add to custom fields
            $custom_fields[$key] = $value[0];
        }
        
        return $custom_fields;
    }

    /**
     * Get the user's login count
     *
     * @param \MeprUser $user The user
     * @return int The user's login count
     */
    protected function getLoginCount(\MeprUser $user): int {
        if (!function_exists('get_user_meta')) {
            return 0;
        }
        
        $login_count = get_user_meta($user->ID, '_mepr_login_count', true);
        
        return !empty($login_count) ? (int)$login_count : 0;
    }

    /**
     * Get the user's last login
     *
     * @param \MeprUser $user The user
     * @return string|null The user's last login
     */
    protected function getLastLogin(\MeprUser $user) {
        if (!function_exists('get_user_meta')) {
            return null;
        }
        
        $last_login = get_user_meta($user->ID, '_mepr_last_login_date', true);
        
        return !empty($last_login) ? $last_login : null;
    }

    /**
     * Check if the user is active
     *
     * @param \MeprUser $user The user
     * @return bool True if the user is active, false otherwise
     */
    protected function isActive(\MeprUser $user): bool {
        $status = $this->getStatus($user);
        
        return $status === 'active';
    }
}