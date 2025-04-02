# Best-Selling Membership Feature

This document provides detailed information about the Best-Selling Membership feature implemented in MemberPress AI Assistant.

## Overview

The Best-Selling Membership feature allows users to quickly identify their top-performing membership products based on transaction data. This provides valuable business intelligence by showing which membership options are most popular with customers.

## Implementation

The feature is implemented in two main components:

1. A data retrieval and analysis function in the `MPAI_MemberPress_API` class
2. Integration with the Context Manager to expose the functionality through the AI assistant

### Data Retrieval

The `get_best_selling_membership()` method in the `MPAI_MemberPress_API` class:

- Queries the MemberPress transactions database table to count sales by product
- Ranks memberships based on completed transaction counts
- Formats the data with product details, sale counts, and prices
- Provides tabular output for easy viewing

### Fallback System

For sites without transaction data, a fallback mechanism is implemented:

- Retrieves available membership products from the database
- Generates realistic sample data based on product age and other factors
- Clearly indicates when sample data is being used instead of actual transactions
- Provides a consistent interface regardless of data source

### Context Manager Integration

The feature is exposed to the AI assistant through the `memberpress_info` tool:

- Added "best_selling" to the type parameter enum
- Implemented a case handler in the `get_memberpress_info()` method
- Formats output as a tabular display for easy reading
- Returns JSON-encoded response for consistent tool output processing

## Usage

### AI Assistant Usage

Users can ask the AI assistant questions like:

- "What are my best-selling memberships?"
- "Which membership product is selling the most?"
- "Show me the top 5 memberships by sales"

The AI assistant will use the appropriate tool call to retrieve and display the information:

```json
{
  "tool": "memberpress_info",
  "parameters": {
    "type": "best_selling"
  }
}
```

### API Usage

Developers can directly use the feature through the API:

```php
// Get MemberPress API instance
$mepr_api = new MPAI_MemberPress_API();

// Get best-selling memberships with formatted output
$best_selling = $mepr_api->get_best_selling_membership(array(), true);

// Get raw data for custom processing
$best_selling_data = $mepr_api->get_best_selling_membership();

// Get best-selling for a specific date range
$date_params = array(
    'start_date' => '2025-01-01',
    'end_date' => '2025-03-31'
);
$quarterly_best_selling = $mepr_api->get_best_selling_membership($date_params, true);
```

## Data Format

### Formatted Output

The formatted output provides a tabular display:

```
Best-Selling Memberships:

Rank	Title	Sales	Price
1	Pro Membership	157	$199
2	Basic Membership	93	$49
3	Premium Membership	75	$149
4	Lifetime Access	42	$999
5	Monthly Membership	31	$19
```

### Raw Data Structure

The raw data is returned as an array of objects with the following structure:

```php
[
    [
        'rank' => 1,
        'product_id' => 123,
        'product_title' => 'Pro Membership',
        'sale_count' => 157,
        'price' => '199'
    ],
    // Additional membership products...
]
```

## Testing

The `test-best-selling.php` file provides a standalone testing script that:

- Connects to the WordPress database
- Demonstrates the functionality without full plugin dependencies
- Shows implementation examples for integration into other components
- Provides a visual interface for reviewing the feature output

## Integration Notes

When integrating this feature into other components:

1. Ensure the database tables `{$wpdb->prefix}mepr_transactions` is accessible
2. Use the `get_best_selling_membership()` method with appropriate parameters
3. Handle both real data and fallback data scenarios
4. Process the formatted output or raw data based on your needs

## Future Enhancements

Planned enhancements for this feature include:

- Graphical representation of membership sales data
- Time-based trending to show sales growth over time
- Filtering options for specific date ranges or product categories
- Revenue-based analysis in addition to unit sales
- Export functionality for reports