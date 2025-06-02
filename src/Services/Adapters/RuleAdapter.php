<?php
/**
 * Rule Adapter
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Services\Adapters;

/**
 * Adapter for MemberPress Rules (Access Rules)
 */
class RuleAdapter {
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
     * Get a rule by ID
     *
     * @param int $id The rule ID
     * @return \MeprRule|null The rule or null if not found
     */
    public function get(int $id) {
        try {
            if (!class_exists('\MeprRule')) {
                throw new \Exception('MemberPress is not active');
            }

            $rule = new \MeprRule($id);
            
            // Check if the rule exists
            if (!$rule->ID || $rule->ID == 0) {
                return null;
            }
            
            return $rule;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error getting rule: ' . $e->getMessage(), [
                    'rule_id' => $id,
                    'exception' => $e
                ]);
            }
            return null;
        }
    }

    /**
     * Get all rules
     *
     * @param array $args Optional arguments
     * @return array Array of rules
     */
    public function getAll(array $args = []): array {
        try {
            if (!class_exists('\MeprRule')) {
                throw new \Exception('MemberPress is not active');
            }

            // Default arguments
            $defaults = [
                'limit' => -1,
                'offset' => 0,
                'orderby' => 'ID',
                'order' => 'DESC',
                'product_id' => null,
                'content_type' => null,
            ];

            // Merge with provided arguments
            $args = array_merge($defaults, $args);

            // Convert to WP_Query arguments
            $query_args = [
                'post_type' => 'memberpressrule',
                'posts_per_page' => $args['limit'],
                'offset' => $args['offset'],
                'orderby' => $args['orderby'],
                'order' => $args['order'],
                'post_status' => 'publish',
            ];

            // Add meta query for product_id if provided
            if (!empty($args['product_id'])) {
                $query_args['meta_query'][] = [
                    'key' => '_mepr_product_id',
                    'value' => $args['product_id'],
                    'compare' => '=',
                ];
            }

            // Add meta query for content_type if provided
            if (!empty($args['content_type'])) {
                $query_args['meta_query'][] = [
                    'key' => '_mepr_content_type',
                    'value' => $args['content_type'],
                    'compare' => '=',
                ];
            }

            // Run the query
            $query = new \WP_Query($query_args);
            $rules = [];

            // Convert posts to MeprRule objects
            if ($query->have_posts()) {
                foreach ($query->posts as $post) {
                    $rules[] = new \MeprRule($post->ID);
                }
            }

            return $rules;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error getting rules: ' . $e->getMessage(), [
                    'args' => $args,
                    'exception' => $e
                ]);
            }
            return [];
        }
    }

    /**
     * Create a new rule
     *
     * @param array $data The rule data
     * @return \MeprRule|null The created rule or null on failure
     */
    public function create(array $data) {
        try {
            if (!class_exists('\MeprRule')) {
                throw new \Exception('MemberPress is not active');
            }

            // Required fields
            if (empty($data['product_id']) || empty($data['content_type'])) {
                throw new \Exception('Product ID and Content Type are required');
            }

            // Create a new rule
            $rule = new \MeprRule();
            
            // Set rule properties
            $rule->post_title = isset($data['title']) ? sanitize_text_field($data['title']) : 'Access Rule';
            $rule->post_status = 'publish';
            
            // Save the rule to get an ID
            $rule->store();
            
            // Set rule meta
            if (isset($data['product_id'])) {
                update_post_meta($rule->ID, '_mepr_product_id', intval($data['product_id']));
            }
            
            if (isset($data['content_type'])) {
                update_post_meta($rule->ID, '_mepr_content_type', sanitize_text_field($data['content_type']));
            }
            
            if (isset($data['content_ids']) && is_array($data['content_ids'])) {
                update_post_meta($rule->ID, '_mepr_content_ids', array_map('intval', $data['content_ids']));
            }
            
            if (isset($data['rule_type'])) {
                update_post_meta($rule->ID, '_mepr_rule_type', sanitize_text_field($data['rule_type']));
            } else {
                update_post_meta($rule->ID, '_mepr_rule_type', 'include');
            }
            
            if (isset($data['drip_enabled'])) {
                update_post_meta($rule->ID, '_mepr_drip_enabled', (bool)$data['drip_enabled']);
            }
            
            if (isset($data['drip_amount'])) {
                update_post_meta($rule->ID, '_mepr_drip_amount', intval($data['drip_amount']));
            }
            
            if (isset($data['drip_unit'])) {
                update_post_meta($rule->ID, '_mepr_drip_unit', sanitize_text_field($data['drip_unit']));
            }
            
            if (isset($data['drip_after'])) {
                update_post_meta($rule->ID, '_mepr_drip_after', sanitize_text_field($data['drip_after']));
            }
            
            if (isset($data['expires_enabled'])) {
                update_post_meta($rule->ID, '_mepr_expires_enabled', (bool)$data['expires_enabled']);
            }
            
            if (isset($data['expires_amount'])) {
                update_post_meta($rule->ID, '_mepr_expires_amount', intval($data['expires_amount']));
            }
            
            if (isset($data['expires_unit'])) {
                update_post_meta($rule->ID, '_mepr_expires_unit', sanitize_text_field($data['expires_unit']));
            }
            
            if (isset($data['expires_after'])) {
                update_post_meta($rule->ID, '_mepr_expires_after', sanitize_text_field($data['expires_after']));
            }
            
            if (isset($data['unauth_excerpt_type'])) {
                update_post_meta($rule->ID, '_mepr_unauth_excerpt_type', sanitize_text_field($data['unauth_excerpt_type']));
            }
            
            if (isset($data['unauth_excerpt_size'])) {
                update_post_meta($rule->ID, '_mepr_unauth_excerpt_size', intval($data['unauth_excerpt_size']));
            }
            
            if (isset($data['unauth_message'])) {
                update_post_meta($rule->ID, '_mepr_unauth_message', wp_kses_post($data['unauth_message']));
            }
            
            if (isset($data['unauth_login'])) {
                update_post_meta($rule->ID, '_mepr_unauth_login', (bool)$data['unauth_login']);
            }
            
            // Save the rule again to update meta
            $rule->store();
            
            return $rule;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error creating rule: ' . $e->getMessage(), [
                    'data' => $data,
                    'exception' => $e
                ]);
            }
            return null;
        }
    }

    /**
     * Update a rule
     *
     * @param int $id The rule ID
     * @param array $data The rule data
     * @return \MeprRule|null The updated rule or null on failure
     */
    public function update(int $id, array $data) {
        try {
            // Get the rule
            $rule = $this->get($id);
            if (!$rule) {
                throw new \Exception('Rule not found');
            }
            
            // Update rule properties
            if (isset($data['title'])) {
                $rule->post_title = sanitize_text_field($data['title']);
            }
            
            // Update rule meta
            if (isset($data['product_id'])) {
                update_post_meta($rule->ID, '_mepr_product_id', intval($data['product_id']));
            }
            
            if (isset($data['content_type'])) {
                update_post_meta($rule->ID, '_mepr_content_type', sanitize_text_field($data['content_type']));
            }
            
            if (isset($data['content_ids']) && is_array($data['content_ids'])) {
                update_post_meta($rule->ID, '_mepr_content_ids', array_map('intval', $data['content_ids']));
            }
            
            if (isset($data['rule_type'])) {
                update_post_meta($rule->ID, '_mepr_rule_type', sanitize_text_field($data['rule_type']));
            }
            
            if (isset($data['drip_enabled'])) {
                update_post_meta($rule->ID, '_mepr_drip_enabled', (bool)$data['drip_enabled']);
            }
            
            if (isset($data['drip_amount'])) {
                update_post_meta($rule->ID, '_mepr_drip_amount', intval($data['drip_amount']));
            }
            
            if (isset($data['drip_unit'])) {
                update_post_meta($rule->ID, '_mepr_drip_unit', sanitize_text_field($data['drip_unit']));
            }
            
            if (isset($data['drip_after'])) {
                update_post_meta($rule->ID, '_mepr_drip_after', sanitize_text_field($data['drip_after']));
            }
            
            if (isset($data['expires_enabled'])) {
                update_post_meta($rule->ID, '_mepr_expires_enabled', (bool)$data['expires_enabled']);
            }
            
            if (isset($data['expires_amount'])) {
                update_post_meta($rule->ID, '_mepr_expires_amount', intval($data['expires_amount']));
            }
            
            if (isset($data['expires_unit'])) {
                update_post_meta($rule->ID, '_mepr_expires_unit', sanitize_text_field($data['expires_unit']));
            }
            
            if (isset($data['expires_after'])) {
                update_post_meta($rule->ID, '_mepr_expires_after', sanitize_text_field($data['expires_after']));
            }
            
            if (isset($data['unauth_excerpt_type'])) {
                update_post_meta($rule->ID, '_mepr_unauth_excerpt_type', sanitize_text_field($data['unauth_excerpt_type']));
            }
            
            if (isset($data['unauth_excerpt_size'])) {
                update_post_meta($rule->ID, '_mepr_unauth_excerpt_size', intval($data['unauth_excerpt_size']));
            }
            
            if (isset($data['unauth_message'])) {
                update_post_meta($rule->ID, '_mepr_unauth_message', wp_kses_post($data['unauth_message']));
            }
            
            if (isset($data['unauth_login'])) {
                update_post_meta($rule->ID, '_mepr_unauth_login', (bool)$data['unauth_login']);
            }
            
            // Save the rule
            $rule->store();
            
            return $rule;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error updating rule: ' . $e->getMessage(), [
                    'rule_id' => $id,
                    'data' => $data,
                    'exception' => $e
                ]);
            }
            return null;
        }
    }

    /**
     * Delete a rule
     *
     * @param int $id The rule ID
     * @return bool True on success, false on failure
     */
    public function delete(int $id): bool {
        try {
            // Get the rule
            $rule = $this->get($id);
            if (!$rule) {
                throw new \Exception('Rule not found');
            }
            
            // Delete the rule
            $result = wp_delete_post($id, true);
            
            return $result !== false;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error deleting rule: ' . $e->getMessage(), [
                    'rule_id' => $id,
                    'exception' => $e
                ]);
            }
            return false;
        }
    }

    /**
     * Get rule product
     *
     * @param \MeprRule $rule The rule
     * @return \MeprProduct|null The product or null if not found
     */
    public function getProduct(\MeprRule $rule) {
        try {
            // Get the product ID
            $product_id = get_post_meta($rule->ID, '_mepr_product_id', true);
            if (!$product_id) {
                return null;
            }
            
            // Get the product
            $product = new \MeprProduct($product_id);
            
            // Check if the product exists
            if (!$product->ID || $product->ID == 0) {
                return null;
            }
            
            return $product;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error getting rule product: ' . $e->getMessage(), [
                    'rule_id' => $rule->ID,
                    'exception' => $e
                ]);
            }
            return null;
        }
    }

    /**
     * Get rule content type
     *
     * @param \MeprRule $rule The rule
     * @return string|null The content type or null if not found
     */
    public function getContentType(\MeprRule $rule) {
        try {
            // Get the content type
            $content_type = get_post_meta($rule->ID, '_mepr_content_type', true);
            
            return $content_type;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error getting rule content type: ' . $e->getMessage(), [
                    'rule_id' => $rule->ID,
                    'exception' => $e
                ]);
            }
            return null;
        }
    }

    /**
     * Get rule content IDs
     *
     * @param \MeprRule $rule The rule
     * @return array The content IDs
     */
    public function getContentIds(\MeprRule $rule): array {
        try {
            // Get the content IDs
            $content_ids = get_post_meta($rule->ID, '_mepr_content_ids', true);
            
            return is_array($content_ids) ? $content_ids : [];
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error getting rule content IDs: ' . $e->getMessage(), [
                    'rule_id' => $rule->ID,
                    'exception' => $e
                ]);
            }
            return [];
        }
    }

    /**
     * Get rule type
     *
     * @param \MeprRule $rule The rule
     * @return string|null The rule type or null if not found
     */
    public function getRuleType(\MeprRule $rule) {
        try {
            // Get the rule type
            $rule_type = get_post_meta($rule->ID, '_mepr_rule_type', true);
            
            return $rule_type;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error getting rule type: ' . $e->getMessage(), [
                    'rule_id' => $rule->ID,
                    'exception' => $e
                ]);
            }
            return null;
        }
    }

    /**
     * Check if a rule applies to a specific content
     *
     * @param \MeprRule $rule The rule
     * @param string $content_type The content type
     * @param int $content_id The content ID
     * @return bool True if the rule applies, false otherwise
     */
    public function appliesTo(\MeprRule $rule, string $content_type, int $content_id): bool {
        try {
            // Get the rule content type and IDs
            $rule_content_type = $this->getContentType($rule);
            $rule_content_ids = $this->getContentIds($rule);
            $rule_type = $this->getRuleType($rule);
            
            // Check if the content type matches
            if ($rule_content_type !== $content_type) {
                return false;
            }
            
            // Check if the content ID is in the rule content IDs
            $is_in_list = in_array($content_id, $rule_content_ids);
            
            // If rule type is 'include', return true if content ID is in the list
            // If rule type is 'exclude', return true if content ID is NOT in the list
            return ($rule_type === 'include') ? $is_in_list : !$is_in_list;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error checking if rule applies to content: ' . $e->getMessage(), [
                    'rule_id' => $rule->ID,
                    'content_type' => $content_type,
                    'content_id' => $content_id,
                    'exception' => $e
                ]);
            }
            return false;
        }
    }
}