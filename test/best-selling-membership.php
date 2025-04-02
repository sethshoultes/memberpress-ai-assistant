<?php
/**
 * Best-Selling Membership Functionality
 *
 * This file contains the function to implement in MPAI_MemberPress_API to find the best-selling membership
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Implementation of get_best_selling_membership for MPAI_MemberPress_API class
 * 
 * To use this function, add it to the MPAI_MemberPress_API class:
 * 
 * ```php
 * /**
 *  * Get the best-selling membership
 *  * 
 *  * @param array $params Optional parameters (e.g., date range)
 *  * @param bool $formatted Whether to return formatted tabular data
 *  * @return array|string The best-selling membership data or formatted string
 *  *\/
 * public function get_best_selling_membership($params = array(), $formatted = false) {
 *     global $wpdb;
 *     
 *     // Get the transactions table name
 *     $table_name = $wpdb->prefix . 'mepr_transactions';
 *     
 *     // Check if the table exists
 *     $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
 *     if (!$table_exists) {
 *         return $formatted ? "No transaction data found. Table does not exist." : array();
 *     }
 *     
 *     // Build query to count sales by product
 *     $query = "SELECT product_id, COUNT(*) as sale_count 
 *               FROM {$table_name} 
 *               WHERE status IN ('complete', 'confirmed')";
 *               
 *     $query_args = array();
 *               
 *     // Add date range filtering if provided
 *     if (!empty($params['start_date'])) {
 *         $query .= " AND created_at >= %s";
 *         $query_args[] = $params['start_date'];
 *     }
 *     
 *     if (!empty($params['end_date'])) {
 *         $query .= " AND created_at <= %s";
 *         $query_args[] = $params['end_date'];
 *     }
 *     
 *     $query .= " GROUP BY product_id ORDER BY sale_count DESC LIMIT 5";
 *     
 *     // Execute the query
 *     if (!empty($query_args)) {
 *         $best_sellers = $wpdb->get_results($wpdb->prepare($query, $query_args));
 *     } else {
 *         $best_sellers = $wpdb->get_results($query);
 *     }
 *     
 *     if (empty($best_sellers)) {
 *         return $formatted ? "No completed transactions found." : array();
 *     }
 *     
 *     // Format the results
 *     $results = array();
 *     foreach ($best_sellers as $index => $seller) {
 *         // Get product details
 *         $product = get_post($seller->product_id);
 *         $product_title = $product ? $product->post_title : "Product #{$seller->product_id}";
 *         
 *         // Get price
 *         $price = get_post_meta($seller->product_id, '_mepr_product_price', true);
 *         
 *         $results[] = array(
 *             'rank' => $index + 1,
 *             'product_id' => $seller->product_id,
 *             'product_title' => $product_title,
 *             'sale_count' => $seller->sale_count,
 *             'price' => $price
 *         );
 *     }
 *     
 *     // If formatted output is requested
 *     if ($formatted) {
 *         $output = "Best-Selling Memberships:\n\n";
 *         $output .= "Rank\tTitle\tSales\tPrice\n";
 *         
 *         foreach ($results as $result) {
 *             $rank = $result['rank'];
 *             $title = $result['product_title'];
 *             $sales = $result['sale_count'];
 *             $price = isset($result['price']) ? '$' . $result['price'] : 'N/A';
 *             
 *             $output .= "{$rank}\t{$title}\t{$sales}\t{$price}\n";
 *         }
 *         
 *         return $output;
 *     }
 *     
 *     return $results;
 * }
 * ```
 * 
 * To update the Context Manager to support this, add "best_selling" to the available types in the memberpress_info tool definition.
 * 
 * In `/includes/class-mpai-context-manager.php`, find the memberpress_info tool definition and update it:
 * 
 * ```php
 * 'memberpress_info' => array(
 *     'name' => 'memberpress_info',
 *     'description' => 'Get information about MemberPress data and system settings',
 *     'parameters' => array(
 *         'type' => array(
 *             'type' => 'string',
 *             'description' => 'Type of information (memberships, members, transactions, subscriptions, summary, new_members_this_month, system_info, best_selling)',
 *             'enum' => array('memberships', 'members', 'transactions', 'subscriptions', 'summary', 'new_members_this_month', 'system_info', 'best_selling', 'all')
 *         ),
 *         'include_system_info' => array(
 *             'type' => 'boolean',
 *             'description' => 'Whether to include system information in the response',
 *             'default' => false
 *         )
 *     ),
 *     'callback' => array($this, 'get_memberpress_info')
 * )
 * ```
 * 
 * Then update the `get_memberpress_info` method to handle the new "best_selling" type:
 * 
 * ```php
 * case 'best_selling':
 *     // Get formatted best-selling memberships as table
 *     $best_selling = $this->memberpress_api->get_best_selling_membership(array(), true);
 *     
 *     $response = array(
 *         'success' => true,
 *         'tool' => 'memberpress_info',
 *         'command_type' => 'best_selling',
 *         'result' => $best_selling
 *     );
 *     return json_encode($response);
 * ```
 * 
 * Finally, update the system prompt in `/includes/class-mpai-chat.php` to include information about the best_selling type:
 * 
 * ```php
 * $system_prompt .= "   - For best-selling memberships: {\"tool\": \"memberpress_info\", \"parameters\": {\"type\": \"best_selling\"}}\n";
 * ```
 */