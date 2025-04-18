<?php
/**
 * Test script for Best-Selling Membership functionality
 * 
 * This script demonstrates how to use the best-selling membership functionality.
 */

// Load WordPress
// Calculate the path to wp-load.php
$wp_load_path = dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . '/wp-load.php';

// Verify path exists
if (!file_exists($wp_load_path)) {
    echo "Error: wp-load.php not found at {$wp_load_path}<br>";
    // Try alternative relative path
    $wp_load_path = '../../../../wp-load.php';
    echo "Trying alternative path: {$wp_load_path}<br>";
}

require_once($wp_load_path);

// Check if user is logged in and is admin
if (!current_user_can('manage_options')) {
    wp_die('You do not have sufficient permissions to access this page.');
}

// Initialize the MemberPress API class
if (!class_exists('MPAI_MemberPress_API')) {
    require_once(dirname(__DIR__) . '/includes/class-mpai-memberpress-api.php');
}

/**
 * Get the best-selling membership
 * 
 * @param array $params Optional parameters (e.g., date range)
 * @param bool $formatted Whether to return formatted tabular data
 * @return array|string The best-selling membership data or formatted string
 */
function get_best_selling_membership($params = array(), $formatted = false) {
    global $wpdb;
    
    try {
        // Get the transactions table name
        $table_name = $wpdb->prefix . 'mepr_transactions';
        
        // Check if the table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
        if (!$table_exists) {
            error_log('MPAI TEST: mepr_transactions table does not exist');
            return get_fallback_membership_data($formatted);
        }
        
        // Check if the table has any records
        $record_count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} LIMIT 1");
        if (empty($record_count) || $record_count == 0) {
            error_log('MPAI TEST: mepr_transactions table exists but is empty');
            return get_fallback_membership_data($formatted);
        }
        
        // Build query to count sales by product
        $query = "SELECT product_id, COUNT(*) as sale_count 
                  FROM {$table_name} 
                  WHERE status IN ('complete', 'confirmed')";
                  
        $query_args = array();
                  
        // Add date range filtering if provided
        if (!empty($params['start_date'])) {
            $query .= " AND created_at >= %s";
            $query_args[] = $params['start_date'];
        }
        
        if (!empty($params['end_date'])) {
            $query .= " AND created_at <= %s";
            $query_args[] = $params['end_date'];
        }
        
        $query .= " GROUP BY product_id ORDER BY sale_count DESC LIMIT 5";
        
        // Execute the query
        if (!empty($query_args)) {
            $best_sellers = $wpdb->get_results($wpdb->prepare($query, $query_args));
        } else {
            $best_sellers = $wpdb->get_results($query);
        }
        
        if (empty($best_sellers)) {
            error_log('MPAI TEST: No completed transactions found in mepr_transactions table');
            return get_fallback_membership_data($formatted);
        }
        
        // Format the results
        $results = array();
        foreach ($best_sellers as $index => $seller) {
            // Get product details
            $product = get_post($seller->product_id);
            $product_title = $product ? $product->post_title : "Product #{$seller->product_id}";
            
            // Get price
            $price = get_post_meta($seller->product_id, '_mepr_product_price', true);
            
            $results[] = array(
                'rank' => $index + 1,
                'product_id' => $seller->product_id,
                'product_title' => $product_title,
                'sale_count' => $seller->sale_count,
                'price' => $price
            );
        }
        
        // If formatted output is requested
        if ($formatted) {
            $output = "Best-Selling Memberships:\n\n";
            $output .= "Rank\tTitle\tSales\tPrice\n";
            
            foreach ($results as $result) {
                $rank = $result['rank'];
                $title = $result['product_title'];
                $sales = $result['sale_count'];
                $price = isset($result['price']) ? '$' . $result['price'] : 'N/A';
                
                $output .= "{$rank}\t{$title}\t{$sales}\t{$price}\n";
            }
            
            return $output;
        }
        
        return $results;
    } catch (Exception $e) {
        error_log('MPAI TEST: Error in get_best_selling_membership: ' . $e->getMessage());
        return get_fallback_membership_data($formatted);
    }
}

/**
 * Get fallback membership data for best-selling when transaction data is unavailable
 * 
 * @param bool $formatted Whether to return formatted data
 * @return array|string Fallback data
 */
function get_fallback_membership_data($formatted = false) {
    // Get all membership products
    $args = array(
        'post_type' => 'memberpressproduct',
        'posts_per_page' => 10,  // Increased to get more memberships
        'post_status' => 'publish'
    );
    
    $memberships = get_posts($args);
    
    if (empty($memberships)) {
        if ($formatted) {
            return "No membership data available. Transaction history and membership products could not be found.";
        }
        return array();
    }
    
    // Create sample data with more realistic random numbers
    $results = array();
    foreach ($memberships as $index => $membership) {
        // Get price
        $price = get_post_meta($membership->ID, '_mepr_product_price', true);
        
        // Generate a random number for sample data with wider range and more variation
        // Generate more spread out numbers to better differentiate best sellers
        $sample_sales = rand(10, 500); 
        
        // Use post date to influence randomness - newer products might have fewer sales
        $post_date = strtotime($membership->post_date);
        $days_old = (time() - $post_date) / (60 * 60 * 24);
        // Adjust sales based on age - newer products might have lower sales numbers
        $sales_adjustment = min($days_old / 30, 5); // Up to 5x multiplier for older products
        $sample_sales = intval($sample_sales * (1 + $sales_adjustment / 10));
        
        $results[] = array(
            'rank' => $index + 1,
            'product_id' => $membership->ID,
            'product_title' => $membership->post_title,
            'sale_count' => $sample_sales . ' (sample data)',  // Indicate that this is sample data
            'price' => $price,
            '_raw_sales' => $sample_sales // Hidden value for sorting
        );
    }
    
    // Sort by the sample sales
    usort($results, function($a, $b) {
        $a_sales = isset($a['_raw_sales']) ? $a['_raw_sales'] : intval($a['sale_count']);
        $b_sales = isset($b['_raw_sales']) ? $b['_raw_sales'] : intval($b['sale_count']);
        return $b_sales - $a_sales;
    });
    
    // Update ranks after sorting and remove temporary _raw_sales field
    foreach ($results as $index => $result) {
        $results[$index]['rank'] = $index + 1;
        if (isset($results[$index]['_raw_sales'])) {
            unset($results[$index]['_raw_sales']);
        }
    }
    
    // Limit to top 5 best sellers after sorting
    $results = array_slice($results, 0, 5);
    
    // If formatted output is requested
    if ($formatted) {
        $output = "Best-Selling Membership Products (Sample Data - No Transaction History):\n\n";
        $output .= "Rank\tTitle\tSales\tPrice\n";
        
        foreach ($results as $result) {
            $rank = $result['rank'];
            $title = $result['product_title'];
            $sales = $result['sale_count'];
            $price = isset($result['price']) ? '$' . $result['price'] : 'N/A';
            
            $output .= "{$rank}\t{$title}\t{$sales}\t{$price}\n";
        }
        
        return $output;
    }
    
    return $results;
}

// Run the test
$best_sellers = get_best_selling_membership(array(), true);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Best-Selling Membership Test</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            margin: 20px;
            background: #f0f0f1;
            line-height: 1.5;
            color: #3c434a;
        }
        .container {
            max-width: 960px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border: 1px solid #c3c4c7;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
            border-radius: 4px;
        }
        h1 {
            color: #1d2327;
            font-size: 23px;
            font-weight: 400;
            margin-top: 0;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        h2 {
            font-size: 18px;
            color: #1d2327;
            margin-top: 30px;
            margin-bottom: 15px;
        }
        pre {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-family: monospace;
            white-space: pre-wrap;
        }
        .instructions {
            background: #fff8e5;
            padding: 15px;
            border-left: 4px solid #ffb900;
            margin-bottom: 20px;
        }
        .code-block {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-family: monospace;
            white-space: pre-wrap;
            margin-bottom: 20px;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 20px;
        }
        th, td {
            text-align: left;
            padding: 8px;
            border: 1px solid #ddd;
        }
        th {
            background-color: #f0f0f1;
            font-weight: 600;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Best-Selling Membership Test</h1>
        
        <div class="instructions">
            <p><strong>About this test:</strong> This page demonstrates how to query the MemberPress database to find the best-selling memberships based on transaction counts.</p>
            <p>The script will try to query the MemberPress transactions table. If the table doesn't exist or is empty, it will fall back to showing sample data using available membership products.</p>
        </div>
        
        <h2>Results</h2>
        <pre><?php echo is_string($best_sellers) ? esc_html($best_sellers) : "No results found. Check if MemberPress transactions exist."; ?></pre>
        
        <h2>How to Implement in MemberPress AI Assistant</h2>
        
        <p>To add this functionality to the MemberPress AI Assistant plugin, follow these steps:</p>
        
        <h3>1. Add the function to MPAI_MemberPress_API class</h3>
        <div class="code-block">// In class-mpai-memberpress-api.php
public function get_best_selling_membership($params = array(), $formatted = false) {
    // Implementation as shown in this test file
}</div>
        
        <h3>2. Update the Context Manager</h3>
        <div class="code-block">// In class-mpai-context-manager.php, update memberpress_info tool definition
'memberpress_info' => array(
    'name' => 'memberpress_info',
    'description' => 'Get information about MemberPress data and system settings',
    'parameters' => array(
        'type' => array(
            'type' => 'string',
            'description' => 'Type of information',
            'enum' => array(..., 'best_selling', ...)
        ),
        ...
    ),
    ...
)

// Then in the get_memberpress_info method, add a case for 'best_selling'
case 'best_selling':
    $best_selling = $this->memberpress_api->get_best_selling_membership(array(), true);
    $response = array(
        'success' => true,
        'tool' => 'memberpress_info',
        'command_type' => 'best_selling',
        'result' => $best_selling
    );
    return json_encode($response);</div>
        
        <h3>3. Update the System Prompt</h3>
        <div class="code-block">// In class-mpai-chat.php
$system_prompt .= "   - For best-selling memberships: {\"tool\": \"memberpress_info\", \"parameters\": {\"type\": \"best_selling\"}}\n";</div>
        
        <p>Once these changes are implemented, the AI assistant will be able to answer questions about the best-selling memberships using the MemberPress transaction data or sample data if transactions aren't available.</p>
        
        <hr>
        <p><a href="<?php echo admin_url('admin.php?page=memberpress-ai-assistant-settings'); ?>" class="button">Back to Plugin Settings</a></p>
    </div>
</body>
</html>