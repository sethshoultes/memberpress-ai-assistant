<?php
/**
 * Product Transformer
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Services\Transformers;

/**
 * Transformer for MemberPress Products (Memberships)
 */
class ProductTransformer {
    /**
     * Transform a MeprProduct object to an array
     *
     * @param \MeprProduct $product The product to transform
     * @return array The transformed product data
     */
    public function transform(\MeprProduct $product): array {
        $data = [
            'id' => $product->ID,
            'title' => $product->post_title,
            'description' => $product->post_content,
            'excerpt' => $product->post_excerpt,
            'status' => $product->post_status,
            'created_at' => $product->post_date,
            'updated_at' => $product->post_modified,
            'price' => $this->getPrice($product),
            'terms' => $this->getTerms($product),
            'url' => get_permalink($product->ID),
            'is_one_time_payment' => $this->isOneTimePayment($product),
            'is_recurring' => $this->isRecurring($product),
            'has_trial' => $this->hasTrial($product),
            'trial_days' => $this->getTrialDays($product),
            'trial_amount' => $this->getTrialAmount($product),
            'access_url' => $this->getAccessUrl($product),
            'thank_you_page_enabled' => $this->isThankYouPageEnabled($product),
            'thank_you_page_type' => $this->getThankYouPageType($product),
            'thank_you_message' => $this->getThankYouMessage($product),
            'thank_you_page_id' => $this->getThankYouPageId($product),
            'pricing_display' => $this->getPricingDisplay($product),
            'pricing_display_text' => $this->getPricingDisplayText($product),
            'who_can_purchase' => $this->getWhoCanPurchase($product),
            'is_highlighted' => $this->isHighlighted($product),
            'register_price_action' => $this->getRegisterPriceAction($product),
            'register_price' => $this->getRegisterPrice($product),
            'allow_renewal' => $this->allowsRenewal($product),
            'tax_exempt' => $this->isTaxExempt($product),
            'tax_class' => $this->getTaxClass($product),
            'expires' => $this->getExpiration($product),
        ];

        return $data;
    }

    /**
     * Get the product price
     *
     * @param \MeprProduct $product The product
     * @return float The product price
     */
    protected function getPrice(\MeprProduct $product): float {
        return (float)$product->price;
    }

    /**
     * Get the product terms
     *
     * @param \MeprProduct $product The product
     * @return array The product terms
     */
    protected function getTerms(\MeprProduct $product): array {
        return [
            'period' => $product->period,
            'period_type' => $product->period_type,
            'period_formatted' => $this->formatPeriod($product->period, $product->period_type),
            'limit_cycles' => $product->limit_cycles,
            'limit_cycles_num' => $product->limit_cycles_num,
            'limit_cycles_action' => $product->limit_cycles_action,
            'limit_cycles_expires_after' => $product->limit_cycles_expires_after,
            'limit_cycles_expires_type' => $product->limit_cycles_expires_type,
        ];
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
     * Check if the product is a one-time payment
     *
     * @param \MeprProduct $product The product
     * @return bool True if the product is a one-time payment, false otherwise
     */
    protected function isOneTimePayment(\MeprProduct $product): bool {
        return $product->period_type === 'lifetime';
    }

    /**
     * Check if the product is recurring
     *
     * @param \MeprProduct $product The product
     * @return bool True if the product is recurring, false otherwise
     */
    protected function isRecurring(\MeprProduct $product): bool {
        return $product->period_type !== 'lifetime';
    }

    /**
     * Check if the product has a trial
     *
     * @param \MeprProduct $product The product
     * @return bool True if the product has a trial, false otherwise
     */
    protected function hasTrial(\MeprProduct $product): bool {
        return (bool)$product->trial;
    }

    /**
     * Get the product trial days
     *
     * @param \MeprProduct $product The product
     * @return int The product trial days
     */
    protected function getTrialDays(\MeprProduct $product): int {
        return (int)$product->trial_days;
    }

    /**
     * Get the product trial amount
     *
     * @param \MeprProduct $product The product
     * @return float The product trial amount
     */
    protected function getTrialAmount(\MeprProduct $product): float {
        return (float)$product->trial_amount;
    }

    /**
     * Get the product access URL
     *
     * @param \MeprProduct $product The product
     * @return string The product access URL
     */
    protected function getAccessUrl(\MeprProduct $product): string {
        return $product->access_url;
    }

    /**
     * Check if the product has a thank you page enabled
     *
     * @param \MeprProduct $product The product
     * @return bool True if the product has a thank you page enabled, false otherwise
     */
    protected function isThankYouPageEnabled(\MeprProduct $product): bool {
        return (bool)$product->thank_you_page_enabled;
    }

    /**
     * Get the product thank you page type
     *
     * @param \MeprProduct $product The product
     * @return string The product thank you page type
     */
    protected function getThankYouPageType(\MeprProduct $product): string {
        return $product->thank_you_page_type;
    }

    /**
     * Get the product thank you message
     *
     * @param \MeprProduct $product The product
     * @return string The product thank you message
     */
    protected function getThankYouMessage(\MeprProduct $product): string {
        return $product->thank_you_message;
    }

    /**
     * Get the product thank you page ID
     *
     * @param \MeprProduct $product The product
     * @return int The product thank you page ID
     */
    protected function getThankYouPageId(\MeprProduct $product): int {
        return (int)$product->thank_you_page_id;
    }

    /**
     * Get the product pricing display
     *
     * @param \MeprProduct $product The product
     * @return string The product pricing display
     */
    protected function getPricingDisplay(\MeprProduct $product): string {
        return $product->pricing_display;
    }

    /**
     * Get the product pricing display text
     *
     * @param \MeprProduct $product The product
     * @return string The product pricing display text
     */
    protected function getPricingDisplayText(\MeprProduct $product): string {
        return $product->pricing_display_text;
    }

    /**
     * Get who can purchase the product
     *
     * @param \MeprProduct $product The product
     * @return string Who can purchase the product
     */
    protected function getWhoCanPurchase(\MeprProduct $product): string {
        return $product->who_can_purchase;
    }

    /**
     * Check if the product is highlighted
     *
     * @param \MeprProduct $product The product
     * @return bool True if the product is highlighted, false otherwise
     */
    protected function isHighlighted(\MeprProduct $product): bool {
        return (bool)get_post_meta($product->ID, '_mepr_product_is_highlighted', true);
    }

    /**
     * Get the product register price action
     *
     * @param \MeprProduct $product The product
     * @return string The product register price action
     */
    protected function getRegisterPriceAction(\MeprProduct $product): string {
        return $product->register_price_action;
    }

    /**
     * Get the product register price
     *
     * @param \MeprProduct $product The product
     * @return string The product register price
     */
    protected function getRegisterPrice(\MeprProduct $product): string {
        return $product->register_price;
    }

    /**
     * Check if the product allows renewal
     *
     * @param \MeprProduct $product The product
     * @return bool True if the product allows renewal, false otherwise
     */
    protected function allowsRenewal(\MeprProduct $product): bool {
        return (bool)$product->allow_renewal;
    }

    /**
     * Check if the product is tax exempt
     *
     * @param \MeprProduct $product The product
     * @return bool True if the product is tax exempt, false otherwise
     */
    protected function isTaxExempt(\MeprProduct $product): bool {
        return (bool)$product->tax_exempt;
    }

    /**
     * Get the product tax class
     *
     * @param \MeprProduct $product The product
     * @return string The product tax class
     */
    protected function getTaxClass(\MeprProduct $product): string {
        return $product->tax_class;
    }

    /**
     * Get the product expiration
     *
     * @param \MeprProduct $product The product
     * @return array The product expiration
     */
    protected function getExpiration(\MeprProduct $product): array {
        return [
            'expire_type' => $product->expire_type,
            'expire_after' => $product->expire_after,
            'expire_unit' => $product->expire_unit,
            'expire_fixed' => $product->expire_fixed,
        ];
    }
}