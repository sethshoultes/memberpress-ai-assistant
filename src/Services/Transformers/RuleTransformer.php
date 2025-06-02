<?php
/**
 * Rule Transformer
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Services\Transformers;

/**
 * Transformer for MemberPress Rules (Access Rules)
 */
class RuleTransformer {
    /**
     * Transform a MeprRule object to an array
     *
     * @param \MeprRule $rule The rule to transform
     * @return array The transformed rule data
     */
    public function transform(\MeprRule $rule): array {
        $data = [
            'id' => $rule->ID,
            'title' => $rule->post_title,
            'description' => $rule->post_content,
            'excerpt' => $rule->post_excerpt,
            'status' => $rule->post_status,
            'created_at' => $rule->post_date,
            'updated_at' => $rule->post_modified,
            'product_id' => $this->getProductId($rule),
            'content_type' => $this->getContentType($rule),
            'content_ids' => $this->getContentIds($rule),
            'rule_type' => $this->getRuleType($rule),
            'drip_enabled' => $this->isDripEnabled($rule),
            'drip_amount' => $this->getDripAmount($rule),
            'drip_unit' => $this->getDripUnit($rule),
            'drip_after' => $this->getDripAfter($rule),
            'expires_enabled' => $this->isExpiresEnabled($rule),
            'expires_amount' => $this->getExpiresAmount($rule),
            'expires_unit' => $this->getExpiresUnit($rule),
            'expires_after' => $this->getExpiresAfter($rule),
            'unauth_excerpt_type' => $this->getUnauthExcerptType($rule),
            'unauth_excerpt_size' => $this->getUnauthExcerptSize($rule),
            'unauth_message' => $this->getUnauthMessage($rule),
            'unauth_login' => $this->isUnauthLogin($rule),
            'product_name' => $this->getProductName($rule),
            'content_items' => $this->getContentItems($rule),
        ];

        return $data;
    }

    /**
     * Get the rule product ID
     *
     * @param \MeprRule $rule The rule
     * @return int The rule product ID
     */
    protected function getProductId(\MeprRule $rule): int {
        $product_id = get_post_meta($rule->ID, '_mepr_product_id', true);
        
        return (int)$product_id;
    }

    /**
     * Get the rule content type
     *
     * @param \MeprRule $rule The rule
     * @return string The rule content type
     */
    protected function getContentType(\MeprRule $rule): string {
        $content_type = get_post_meta($rule->ID, '_mepr_content_type', true);
        
        return $content_type;
    }

    /**
     * Get the rule content IDs
     *
     * @param \MeprRule $rule The rule
     * @return array The rule content IDs
     */
    protected function getContentIds(\MeprRule $rule): array {
        $content_ids = get_post_meta($rule->ID, '_mepr_content_ids', true);
        
        return is_array($content_ids) ? $content_ids : [];
    }

    /**
     * Get the rule type
     *
     * @param \MeprRule $rule The rule
     * @return string The rule type
     */
    protected function getRuleType(\MeprRule $rule): string {
        $rule_type = get_post_meta($rule->ID, '_mepr_rule_type', true);
        
        return $rule_type;
    }

    /**
     * Check if the rule has drip enabled
     *
     * @param \MeprRule $rule The rule
     * @return bool True if the rule has drip enabled, false otherwise
     */
    protected function isDripEnabled(\MeprRule $rule): bool {
        $drip_enabled = get_post_meta($rule->ID, '_mepr_drip_enabled', true);
        
        return (bool)$drip_enabled;
    }

    /**
     * Get the rule drip amount
     *
     * @param \MeprRule $rule The rule
     * @return int The rule drip amount
     */
    protected function getDripAmount(\MeprRule $rule): int {
        $drip_amount = get_post_meta($rule->ID, '_mepr_drip_amount', true);
        
        return (int)$drip_amount;
    }

    /**
     * Get the rule drip unit
     *
     * @param \MeprRule $rule The rule
     * @return string The rule drip unit
     */
    protected function getDripUnit(\MeprRule $rule): string {
        $drip_unit = get_post_meta($rule->ID, '_mepr_drip_unit', true);
        
        return $drip_unit;
    }

    /**
     * Get the rule drip after
     *
     * @param \MeprRule $rule The rule
     * @return string The rule drip after
     */
    protected function getDripAfter(\MeprRule $rule): string {
        $drip_after = get_post_meta($rule->ID, '_mepr_drip_after', true);
        
        return $drip_after;
    }

    /**
     * Check if the rule has expires enabled
     *
     * @param \MeprRule $rule The rule
     * @return bool True if the rule has expires enabled, false otherwise
     */
    protected function isExpiresEnabled(\MeprRule $rule): bool {
        $expires_enabled = get_post_meta($rule->ID, '_mepr_expires_enabled', true);
        
        return (bool)$expires_enabled;
    }

    /**
     * Get the rule expires amount
     *
     * @param \MeprRule $rule The rule
     * @return int The rule expires amount
     */
    protected function getExpiresAmount(\MeprRule $rule): int {
        $expires_amount = get_post_meta($rule->ID, '_mepr_expires_amount', true);
        
        return (int)$expires_amount;
    }

    /**
     * Get the rule expires unit
     *
     * @param \MeprRule $rule The rule
     * @return string The rule expires unit
     */
    protected function getExpiresUnit(\MeprRule $rule): string {
        $expires_unit = get_post_meta($rule->ID, '_mepr_expires_unit', true);
        
        return $expires_unit;
    }

    /**
     * Get the rule expires after
     *
     * @param \MeprRule $rule The rule
     * @return string The rule expires after
     */
    protected function getExpiresAfter(\MeprRule $rule): string {
        $expires_after = get_post_meta($rule->ID, '_mepr_expires_after', true);
        
        return $expires_after;
    }

    /**
     * Get the rule unauthorized excerpt type
     *
     * @param \MeprRule $rule The rule
     * @return string The rule unauthorized excerpt type
     */
    protected function getUnauthExcerptType(\MeprRule $rule): string {
        $unauth_excerpt_type = get_post_meta($rule->ID, '_mepr_unauth_excerpt_type', true);
        
        return $unauth_excerpt_type;
    }

    /**
     * Get the rule unauthorized excerpt size
     *
     * @param \MeprRule $rule The rule
     * @return int The rule unauthorized excerpt size
     */
    protected function getUnauthExcerptSize(\MeprRule $rule): int {
        $unauth_excerpt_size = get_post_meta($rule->ID, '_mepr_unauth_excerpt_size', true);
        
        return (int)$unauth_excerpt_size;
    }

    /**
     * Get the rule unauthorized message
     *
     * @param \MeprRule $rule The rule
     * @return string The rule unauthorized message
     */
    protected function getUnauthMessage(\MeprRule $rule): string {
        $unauth_message = get_post_meta($rule->ID, '_mepr_unauth_message', true);
        
        return $unauth_message;
    }

    /**
     * Check if the rule has unauthorized login
     *
     * @param \MeprRule $rule The rule
     * @return bool True if the rule has unauthorized login, false otherwise
     */
    protected function isUnauthLogin(\MeprRule $rule): bool {
        $unauth_login = get_post_meta($rule->ID, '_mepr_unauth_login', true);
        
        return (bool)$unauth_login;
    }

    /**
     * Get the rule product name
     *
     * @param \MeprRule $rule The rule
     * @return string The rule product name
     */
    protected function getProductName(\MeprRule $rule): string {
        try {
            $product_id = $this->getProductId($rule);
            
            if (!$product_id) {
                return '';
            }
            
            if (class_exists('\MeprProduct')) {
                $product = new \MeprProduct($product_id);
                
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
     * Get the rule content items
     *
     * @param \MeprRule $rule The rule
     * @return array The rule content items
     */
    protected function getContentItems(\MeprRule $rule): array {
        try {
            $content_type = $this->getContentType($rule);
            $content_ids = $this->getContentIds($rule);
            $items = [];
            
            if (empty($content_type) || empty($content_ids)) {
                return [];
            }
            
            foreach ($content_ids as $content_id) {
                $item = [
                    'id' => $content_id,
                    'type' => $content_type,
                    'title' => $this->getContentTitle($content_type, $content_id),
                    'url' => $this->getContentUrl($content_type, $content_id),
                ];
                
                $items[] = $item;
            }
            
            return $items;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get the content title
     *
     * @param string $content_type The content type
     * @param int $content_id The content ID
     * @return string The content title
     */
    protected function getContentTitle(string $content_type, int $content_id): string {
        try {
            switch ($content_type) {
                case 'post':
                case 'page':
                case 'custom_post_type':
                    $post = get_post($content_id);
                    
                    if ($post) {
                        return $post->post_title;
                    }
                    break;
                
                case 'category':
                    $category = get_category($content_id);
                    
                    if ($category) {
                        return $category->name;
                    }
                    break;
                
                case 'tag':
                    $tag = get_tag($content_id);
                    
                    if ($tag) {
                        return $tag->name;
                    }
                    break;
            }
            
            return '';
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Get the content URL
     *
     * @param string $content_type The content type
     * @param int $content_id The content ID
     * @return string The content URL
     */
    protected function getContentUrl(string $content_type, int $content_id): string {
        try {
            switch ($content_type) {
                case 'post':
                case 'page':
                case 'custom_post_type':
                    return get_permalink($content_id);
                
                case 'category':
                    return get_category_link($content_id);
                
                case 'tag':
                    return get_tag_link($content_id);
            }
            
            return '';
        } catch (\Exception $e) {
            return '';
        }
    }
}