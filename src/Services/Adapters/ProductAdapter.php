<?php
/**
 * Product Adapter
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Services\Adapters;

/**
 * Adapter for MemberPress Products (Memberships)
 */
class ProductAdapter {
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
     * Get a product by ID
     *
     * @param int $id The product ID
     * @return \MeprProduct|null The product or null if not found
     */
    public function get(int $id) {
        try {
            if (!class_exists('\MeprProduct')) {
                throw new \Exception('MemberPress is not active');
            }

            $product = new \MeprProduct($id);
            
            // Check if the product exists
            if (!$product->ID || $product->ID == 0) {
                return null;
            }
            
            return $product;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error getting product: ' . $e->getMessage(), [
                    'product_id' => $id,
                    'exception' => $e
                ]);
            }
            return null;
        }
    }

    /**
     * Get all products
     *
     * @param array $args Optional arguments
     * @return array Array of products
     */
    public function getAll(array $args = []): array {
        try {
            if (!class_exists('\MeprProduct')) {
                throw new \Exception('MemberPress is not active');
            }

            // Default arguments
            $defaults = [
                'limit' => -1,
                'offset' => 0,
                'orderby' => 'ID',
                'order' => 'DESC',
            ];

            // Merge with provided arguments
            $args = array_merge($defaults, $args);

            // Convert to WP_Query arguments
            $query_args = [
                'post_type' => 'memberpressproduct',
                'posts_per_page' => $args['limit'],
                'offset' => $args['offset'],
                'orderby' => $args['orderby'],
                'order' => $args['order'],
                'post_status' => 'publish',
            ];

            // Run the query
            $query = new \WP_Query($query_args);
            $products = [];

            // Convert posts to MeprProduct objects
            if ($query->have_posts()) {
                foreach ($query->posts as $post) {
                    $products[] = new \MeprProduct($post->ID);
                }
            }

            return $products;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error getting products: ' . $e->getMessage(), [
                    'args' => $args,
                    'exception' => $e
                ]);
            }
            return [];
        }
    }

    /**
     * Create a new product
     *
     * @param array $data The product data
     * @return \MeprProduct|null The created product or null on failure
     */
    public function create(array $data) {
        try {
            if (!class_exists('\MeprProduct')) {
                throw new \Exception('MemberPress is not active');
            }

            // Create a new product
            $product = new \MeprProduct();
            
            // Set product properties
            if (isset($data['title'])) {
                $product->post_title = sanitize_text_field($data['title']);
            }
            
            if (isset($data['description'])) {
                $product->post_content = wp_kses_post($data['description']);
            }
            
            if (isset($data['price'])) {
                $product->price = floatval($data['price']);
            }
            
            if (isset($data['period'])) {
                $product->period = intval($data['period']);
            }
            
            if (isset($data['period_type'])) {
                $product->period_type = sanitize_text_field($data['period_type']);
            }
            
            if (isset($data['trial'])) {
                $product->trial = (bool)$data['trial'];
            }
            
            if (isset($data['trial_days'])) {
                $product->trial_days = intval($data['trial_days']);
            }
            
            if (isset($data['trial_amount'])) {
                $product->trial_amount = floatval($data['trial_amount']);
            }
            
            if (isset($data['limit_cycles'])) {
                $product->limit_cycles = (bool)$data['limit_cycles'];
            }
            
            if (isset($data['limit_cycles_num'])) {
                $product->limit_cycles_num = intval($data['limit_cycles_num']);
            }
            
            if (isset($data['limit_cycles_action'])) {
                $product->limit_cycles_action = sanitize_text_field($data['limit_cycles_action']);
            }
            
            if (isset($data['limit_cycles_expires_after'])) {
                $product->limit_cycles_expires_after = intval($data['limit_cycles_expires_after']);
            }
            
            if (isset($data['limit_cycles_expires_type'])) {
                $product->limit_cycles_expires_type = sanitize_text_field($data['limit_cycles_expires_type']);
            }
            
            if (isset($data['who_can_purchase'])) {
                $product->who_can_purchase = sanitize_text_field($data['who_can_purchase']);
            }
            
            if (isset($data['custom_login_urls'])) {
                $product->custom_login_urls = (array)$data['custom_login_urls'];
            }
            
            if (isset($data['custom_login_urls_enabled'])) {
                $product->custom_login_urls_enabled = (bool)$data['custom_login_urls_enabled'];
            }
            
            if (isset($data['expire_type'])) {
                $product->expire_type = sanitize_text_field($data['expire_type']);
            }
            
            if (isset($data['expire_after'])) {
                $product->expire_after = intval($data['expire_after']);
            }
            
            if (isset($data['expire_unit'])) {
                $product->expire_unit = sanitize_text_field($data['expire_unit']);
            }
            
            if (isset($data['expire_fixed'])) {
                $product->expire_fixed = sanitize_text_field($data['expire_fixed']);
            }
            
            if (isset($data['tax_exempt'])) {
                $product->tax_exempt = (bool)$data['tax_exempt'];
            }
            
            if (isset($data['tax_class'])) {
                $product->tax_class = sanitize_text_field($data['tax_class']);
            }
            
            if (isset($data['allow_renewal'])) {
                $product->allow_renewal = (bool)$data['allow_renewal'];
            }
            
            if (isset($data['access_url'])) {
                $product->access_url = esc_url_raw($data['access_url']);
            }
            
            if (isset($data['thank_you_page_enabled'])) {
                $product->thank_you_page_enabled = (bool)$data['thank_you_page_enabled'];
            }
            
            if (isset($data['thank_you_page_type'])) {
                $product->thank_you_page_type = sanitize_text_field($data['thank_you_page_type']);
            }
            
            if (isset($data['thank_you_message'])) {
                $product->thank_you_message = wp_kses_post($data['thank_you_message']);
            }
            
            if (isset($data['thank_you_page_id'])) {
                $product->thank_you_page_id = intval($data['thank_you_page_id']);
            }
            
            if (isset($data['custom_thank_you_page_enabled'])) {
                $product->custom_thank_you_page_enabled = (bool)$data['custom_thank_you_page_enabled'];
            }
            
            if (isset($data['custom_thank_you_urls'])) {
                $product->custom_thank_you_urls = (array)$data['custom_thank_you_urls'];
            }
            
            if (isset($data['pricing_display'])) {
                $product->pricing_display = sanitize_text_field($data['pricing_display']);
            }
            
            if (isset($data['pricing_display_text'])) {
                $product->pricing_display_text = sanitize_text_field($data['pricing_display_text']);
            }
            
            if (isset($data['register_price_action'])) {
                $product->register_price_action = sanitize_text_field($data['register_price_action']);
            }
            
            if (isset($data['register_price'])) {
                $product->register_price = sanitize_text_field($data['register_price']);
            }
            
            if (isset($data['thank_you_page_url'])) {
                $product->thank_you_page_url = esc_url_raw($data['thank_you_page_url']);
            }
            
            // Save the product
            $product->store();
            
            return $product;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error creating product: ' . $e->getMessage(), [
                    'data' => $data,
                    'exception' => $e
                ]);
            }
            return null;
        }
    }

    /**
     * Update a product
     *
     * @param int $id The product ID
     * @param array $data The product data
     * @return \MeprProduct|null The updated product or null on failure
     */
    public function update(int $id, array $data) {
        try {
            // Get the product
            $product = $this->get($id);
            if (!$product) {
                throw new \Exception('Product not found');
            }
            
            // Update product properties
            if (isset($data['title'])) {
                $product->post_title = sanitize_text_field($data['title']);
            }
            
            if (isset($data['description'])) {
                $product->post_content = wp_kses_post($data['description']);
            }
            
            if (isset($data['price'])) {
                $product->price = floatval($data['price']);
            }
            
            if (isset($data['period'])) {
                $product->period = intval($data['period']);
            }
            
            if (isset($data['period_type'])) {
                $product->period_type = sanitize_text_field($data['period_type']);
            }
            
            if (isset($data['trial'])) {
                $product->trial = (bool)$data['trial'];
            }
            
            if (isset($data['trial_days'])) {
                $product->trial_days = intval($data['trial_days']);
            }
            
            if (isset($data['trial_amount'])) {
                $product->trial_amount = floatval($data['trial_amount']);
            }
            
            if (isset($data['limit_cycles'])) {
                $product->limit_cycles = (bool)$data['limit_cycles'];
            }
            
            if (isset($data['limit_cycles_num'])) {
                $product->limit_cycles_num = intval($data['limit_cycles_num']);
            }
            
            if (isset($data['limit_cycles_action'])) {
                $product->limit_cycles_action = sanitize_text_field($data['limit_cycles_action']);
            }
            
            if (isset($data['limit_cycles_expires_after'])) {
                $product->limit_cycles_expires_after = intval($data['limit_cycles_expires_after']);
            }
            
            if (isset($data['limit_cycles_expires_type'])) {
                $product->limit_cycles_expires_type = sanitize_text_field($data['limit_cycles_expires_type']);
            }
            
            if (isset($data['who_can_purchase'])) {
                $product->who_can_purchase = sanitize_text_field($data['who_can_purchase']);
            }
            
            if (isset($data['custom_login_urls'])) {
                $product->custom_login_urls = (array)$data['custom_login_urls'];
            }
            
            if (isset($data['custom_login_urls_enabled'])) {
                $product->custom_login_urls_enabled = (bool)$data['custom_login_urls_enabled'];
            }
            
            if (isset($data['expire_type'])) {
                $product->expire_type = sanitize_text_field($data['expire_type']);
            }
            
            if (isset($data['expire_after'])) {
                $product->expire_after = intval($data['expire_after']);
            }
            
            if (isset($data['expire_unit'])) {
                $product->expire_unit = sanitize_text_field($data['expire_unit']);
            }
            
            if (isset($data['expire_fixed'])) {
                $product->expire_fixed = sanitize_text_field($data['expire_fixed']);
            }
            
            if (isset($data['tax_exempt'])) {
                $product->tax_exempt = (bool)$data['tax_exempt'];
            }
            
            if (isset($data['tax_class'])) {
                $product->tax_class = sanitize_text_field($data['tax_class']);
            }
            
            if (isset($data['allow_renewal'])) {
                $product->allow_renewal = (bool)$data['allow_renewal'];
            }
            
            if (isset($data['access_url'])) {
                $product->access_url = esc_url_raw($data['access_url']);
            }
            
            if (isset($data['thank_you_page_enabled'])) {
                $product->thank_you_page_enabled = (bool)$data['thank_you_page_enabled'];
            }
            
            if (isset($data['thank_you_page_type'])) {
                $product->thank_you_page_type = sanitize_text_field($data['thank_you_page_type']);
            }
            
            if (isset($data['thank_you_message'])) {
                $product->thank_you_message = wp_kses_post($data['thank_you_message']);
            }
            
            if (isset($data['thank_you_page_id'])) {
                $product->thank_you_page_id = intval($data['thank_you_page_id']);
            }
            
            if (isset($data['custom_thank_you_page_enabled'])) {
                $product->custom_thank_you_page_enabled = (bool)$data['custom_thank_you_page_enabled'];
            }
            
            if (isset($data['custom_thank_you_urls'])) {
                $product->custom_thank_you_urls = (array)$data['custom_thank_you_urls'];
            }
            
            if (isset($data['pricing_display'])) {
                $product->pricing_display = sanitize_text_field($data['pricing_display']);
            }
            
            if (isset($data['pricing_display_text'])) {
                $product->pricing_display_text = sanitize_text_field($data['pricing_display_text']);
            }
            
            if (isset($data['register_price_action'])) {
                $product->register_price_action = sanitize_text_field($data['register_price_action']);
            }
            
            if (isset($data['register_price'])) {
                $product->register_price = sanitize_text_field($data['register_price']);
            }
            
            if (isset($data['thank_you_page_url'])) {
                $product->thank_you_page_url = esc_url_raw($data['thank_you_page_url']);
            }
            
            // Save the product
            $product->store();
            
            return $product;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error updating product: ' . $e->getMessage(), [
                    'product_id' => $id,
                    'data' => $data,
                    'exception' => $e
                ]);
            }
            return null;
        }
    }

    /**
     * Delete a product
     *
     * @param int $id The product ID
     * @return bool True on success, false on failure
     */
    public function delete(int $id): bool {
        try {
            // Get the product
            $product = $this->get($id);
            if (!$product) {
                throw new \Exception('Product not found');
            }
            
            // Delete the product
            $result = wp_delete_post($id, true);
            
            return $result !== false;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error deleting product: ' . $e->getMessage(), [
                    'product_id' => $id,
                    'exception' => $e
                ]);
            }
            return false;
        }
    }

    /**
     * Get product terms
     *
     * @param \MeprProduct $product The product
     * @return array The product terms
     */
    public function getTerms(\MeprProduct $product): array {
        try {
            $terms = [];
            
            // Get the product terms
            $terms['price'] = $product->price;
            $terms['period'] = $product->period;
            $terms['period_type'] = $product->period_type;
            $terms['trial'] = $product->trial;
            $terms['trial_days'] = $product->trial_days;
            $terms['trial_amount'] = $product->trial_amount;
            $terms['limit_cycles'] = $product->limit_cycles;
            $terms['limit_cycles_num'] = $product->limit_cycles_num;
            $terms['limit_cycles_action'] = $product->limit_cycles_action;
            $terms['limit_cycles_expires_after'] = $product->limit_cycles_expires_after;
            $terms['limit_cycles_expires_type'] = $product->limit_cycles_expires_type;
            $terms['expire_type'] = $product->expire_type;
            $terms['expire_after'] = $product->expire_after;
            $terms['expire_unit'] = $product->expire_unit;
            $terms['expire_fixed'] = $product->expire_fixed;
            
            return $terms;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error getting product terms: ' . $e->getMessage(), [
                    'product_id' => $product->ID,
                    'exception' => $e
                ]);
            }
            return [];
        }
    }

    /**
     * Get product access rules
     *
     * @param \MeprProduct $product The product
     * @return array The product access rules
     */
    public function getAccessRules(\MeprProduct $product): array {
        try {
            // This is a placeholder implementation
            // In a real implementation, we would use the appropriate MemberPress method
            // to get rules associated with a product
            $rules = [];
            
            try {
                // Try to get rules using MemberPress API
                // Different versions of MemberPress might have different methods
                if (class_exists('\MeprRule')) {
                    // Query for rules that apply to this product
                    $query_args = [
                        'post_type' => 'memberpressrule',
                        'posts_per_page' => -1,
                        'meta_query' => [
                            [
                                'key' => '_mepr_product_id',
                                'value' => $product->ID,
                                'compare' => '='
                            ]
                        ]
                    ];
                    
                    $rule_posts = get_posts($query_args);
                    
                    foreach ($rule_posts as $rule_post) {
                        $rules[] = new \MeprRule($rule_post->ID);
                    }
                }
            } catch (\Exception $e) {
                if ($this->logger) {
                    $this->logger->error('Error getting product rules: ' . $e->getMessage(), [
                        'product_id' => $product->ID,
                        'exception' => $e
                    ]);
                }
            }
            
            return $rules;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error getting product access rules: ' . $e->getMessage(), [
                    'product_id' => $product->ID,
                    'exception' => $e
                ]);
            }
            return [];
        }
    }
}