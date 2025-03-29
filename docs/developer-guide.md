# MemberPress AI Assistant - Developer Guide

This guide provides technical information for developers who want to extend, customize, or integrate with the MemberPress AI Assistant plugin.

## Architecture Overview

The plugin follows WordPress coding standards and is organized into several key components:

1. **Core Plugin Class** (`MemberPress_AI_Assistant`) - Handles initialization, hooks, and basic functionality
2. **OpenAI Integration** (`MPAI_OpenAI`) - Manages communication with the OpenAI API
3. **MemberPress API Integration** (`MPAI_MemberPress_API`) - Retrieves and formats MemberPress data
4. **Chat Management** (`MPAI_Chat`) - Handles conversation processing and storage
5. **Context Management** (`MPAI_Context_Manager`) - Manages CLI command execution and context
6. **Admin Interface** (`MPAI_Admin`) - Handles admin UI and settings
7. **CLI Commands** (`MPAI_CLI_Commands`) - Provides WP-CLI integration

## Database Schema

The plugin creates two database tables:

### Conversations Table

```sql
CREATE TABLE {prefix}mpai_conversations (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    user_id bigint(20) NOT NULL,
    conversation_id varchar(36) NOT NULL,
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY  (id),
    KEY user_id (user_id),
    KEY conversation_id (conversation_id)
) {charset_collate};
```

### Messages Table

```sql
CREATE TABLE {prefix}mpai_messages (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    conversation_id varchar(36) NOT NULL,
    message text NOT NULL,
    response text NOT NULL,
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY  (id),
    KEY conversation_id (conversation_id)
) {charset_collate};
```

## Available Hooks

### Filters

```php
/**
 * Filter the OpenAI API request parameters
 *
 * @param array $params The parameters being sent to OpenAI
 * @param array $messages The messages being processed
 * @return array Modified parameters
 */
apply_filters('mpai_openai_request_params', $params, $messages);

/**
 * Filter the MemberPress data summary before sending to OpenAI
 *
 * @param array $data_summary The MemberPress data summary
 * @return array Modified data summary
 */
apply_filters('mpai_memberpress_data_summary', $data_summary);

/**
 * Filter the allowed CLI commands
 *
 * @param array $allowed_commands The list of allowed commands
 * @return array Modified list of allowed commands
 */
apply_filters('mpai_allowed_cli_commands', $allowed_commands);

/**
 * Filter the system message sent to OpenAI
 *
 * @param string $system_message The system message
 * @param string $context_type The context type (chat, command, etc.)
 * @return string Modified system message
 */
apply_filters('mpai_system_message', $system_message, $context_type);
```

### Actions

```php
/**
 * Fired before sending a request to OpenAI
 *
 * @param array $messages The messages being sent
 * @param array $params The parameters being sent
 */
do_action('mpai_before_openai_request', $messages, $params);

/**
 * Fired after receiving a response from OpenAI
 *
 * @param array $response The response from OpenAI
 * @param array $messages The messages that were sent
 */
do_action('mpai_after_openai_request', $response, $messages);

/**
 * Fired after a CLI command is executed
 *
 * @param string $command The command that was executed
 * @param string $output The command output
 * @param string $insights The AI-generated insights
 */
do_action('mpai_after_command_execution', $command, $output, $insights);

/**
 * Fired after a new conversation is created
 *
 * @param string $conversation_id The new conversation ID
 * @param int $user_id The user ID
 */
do_action('mpai_new_conversation', $conversation_id, $user_id);
```

## Integration Examples

### Adding Custom MemberPress Data to the Context

This example adds additional MemberPress data to the context sent to OpenAI:

```php
add_filter('mpai_memberpress_data_summary', 'my_custom_memberpress_data');

function my_custom_memberpress_data($data_summary) {
    // Add custom coupon data
    if (class_exists('MeprCoupon')) {
        $coupons = MeprCoupon::get_all();
        $coupon_data = array();
        
        foreach ($coupons as $coupon) {
            $coupon_data[] = array(
                'id' => $coupon->id,
                'code' => $coupon->post_title,
                'discount_type' => $coupon->discount_type,
                'discount_amount' => $coupon->discount_amount,
                'usage_count' => $coupon->usage_count,
            );
        }
        
        $data_summary['coupons'] = $coupon_data;
    }
    
    return $data_summary;
}
```

### Customizing the OpenAI Request

This example modifies the OpenAI API request parameters:

```php
add_filter('mpai_openai_request_params', 'my_custom_openai_params', 10, 2);

function my_custom_openai_params($params, $messages) {
    // Increase temperature for more creative responses
    $params['temperature'] = 0.9;
    
    // Add additional system message context
    if (!empty($messages) && $messages[0]['role'] === 'system') {
        $messages[0]['content'] .= "\n\nAdditional context: This site uses custom membership levels.";
        $params['messages'] = $messages;
    }
    
    return $params;
}
```

### Adding Custom CLI Commands to Whitelist

This example adds additional commands to the allowed CLI commands list:

```php
add_filter('mpai_allowed_cli_commands', 'my_custom_allowed_commands');

function my_custom_allowed_commands($allowed_commands) {
    // Add custom commands to whitelist
    $additional_commands = array(
        'wp mepr-membership list',
        'wp mepr-subscription list',
        'wp mepr-transaction list',
    );
    
    return array_merge($allowed_commands, $additional_commands);
}
```

### Logging OpenAI API Usage

This example logs API usage for monitoring purposes:

```php
add_action('mpai_after_openai_request', 'my_log_openai_usage', 10, 2);

function my_log_openai_usage($response, $messages) {
    // Only log if we have a successful response with usage data
    if (isset($response['usage'])) {
        $usage = $response['usage'];
        
        // Format log message
        $log_message = sprintf(
            '[%s] OpenAI API Usage - Prompt tokens: %d, Completion tokens: %d, Total tokens: %d',
            current_time('mysql'),
            $usage['prompt_tokens'],
            $usage['completion_tokens'],
            $usage['total_tokens']
        );
        
        // Log to a custom file
        $log_file = WP_CONTENT_DIR . '/openai-usage.log';
        error_log($log_message . PHP_EOL, 3, $log_file);
    }
}
```

## Extending the Plugin

### Adding a New WP-CLI Command

To add a new WP-CLI command to the plugin:

```php
// Hook into WP-CLI
add_action('cli_init', 'register_my_custom_commands');

function register_my_custom_commands() {
    // Ensure the base class is available
    if (!class_exists('MPAI_CLI_Commands')) {
        return;
    }
    
    // Create a new command class extending the base class
    class My_Custom_MPAI_Commands extends MPAI_CLI_Commands {
        /**
         * Analyze membership trends over time.
         *
         * ## OPTIONS
         *
         * [--months=<months>]
         * : Number of months to analyze. Default: 3
         *
         * ## EXAMPLES
         *
         * wp mpai trend-analysis
         * wp mpai trend-analysis --months=6
         */
        public function trend_analysis($args, $assoc_args) {
            $months = WP_CLI\Utils\get_flag_value($assoc_args, 'months', 3);
            
            WP_CLI::log("Analyzing membership trends for the past {$months} months...");
            
            // Get MemberPress data
            $memberpress_api = new MPAI_MemberPress_API();
            
            // Generate trend analysis prompt
            $prompt = "Analyze MemberPress membership trends over the past {$months} months. " .
                      "Identify patterns, growth rates, and provide insights on member retention.";
            
            // Generate insights using OpenAI
            $openai = new MPAI_OpenAI();
            $insights = $openai->generate_memberpress_completion($prompt, $memberpress_api->get_data_summary());
            
            if (is_wp_error($insights)) {
                WP_CLI::error($insights->get_error_message());
                return;
            }
            
            WP_CLI::log("\n" . $insights);
            WP_CLI::success('Trend analysis completed successfully.');
        }
    }
    
    // Register the command
    WP_CLI::add_command('mpai trend-analysis', array('My_Custom_MPAI_Commands', 'trend_analysis'));
}
```

### Creating a Custom Chat Interface

This example creates a frontend chat widget for members:

```php
// Register scripts and styles
add_action('wp_enqueue_scripts', 'mpai_frontend_assets');

function mpai_frontend_assets() {
    wp_enqueue_style(
        'mpai-frontend-css',
        plugin_dir_url(__FILE__) . 'assets/css/frontend.css',
        array(),
        '1.0.0'
    );
    
    wp_enqueue_script(
        'mpai-frontend-js',
        plugin_dir_url(__FILE__) . 'assets/js/frontend.js',
        array('jquery'),
        '1.0.0',
        true
    );
    
    wp_localize_script(
        'mpai-frontend-js',
        'mpai_frontend',
        array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mpai_frontend_nonce'),
        )
    );
}

// Create shortcode for chat widget
add_shortcode('mpai_chat_widget', 'mpai_chat_widget_shortcode');

function mpai_chat_widget_shortcode($atts) {
    // Only show for logged-in users
    if (!is_user_logged_in()) {
        return '<p>Please log in to use the AI assistant.</p>';
    }
    
    $output = '<div class="mpai-chat-widget">';
    $output .= '<div class="mpai-chat-messages"></div>';
    $output .= '<div class="mpai-chat-input">';
    $output .= '<textarea placeholder="Ask a question about your membership..."></textarea>';
    $output .= '<button class="mpai-send-btn">Send</button>';
    $output .= '</div>';
    $output .= '</div>';
    
    return $output;
}

// Handle AJAX request
add_action('wp_ajax_mpai_frontend_chat', 'mpai_handle_frontend_chat');

function mpai_handle_frontend_chat() {
    // Verify nonce
    if (!check_ajax_referer('mpai_frontend_nonce', 'nonce', false)) {
        wp_send_json_error('Invalid security token');
    }
    
    $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
    
    if (empty($message)) {
        wp_send_json_error('Message is required');
    }
    
    // Process the message
    $chat = new MPAI_Chat();
    $response = $chat->process_message($message);
    
    wp_send_json_success($response);
}
```

## Accessing Plugin Data Programmatically

### Working with the OpenAI Integration

```php
// Initialize the OpenAI client
$openai = new MPAI_OpenAI();

// Generate a response
$messages = array(
    array('role' => 'system', 'content' => 'You are a helpful assistant.'),
    array('role' => 'user', 'content' => 'What are the key MemberPress features?')
);

$response = $openai->send_request($messages);

if (!is_wp_error($response)) {
    $content = $response['choices'][0]['message']['content'];
    echo $content;
}
```

### Accessing MemberPress Data

```php
// Initialize the MemberPress API client
$mp_api = new MPAI_MemberPress_API();

// Get MemberPress data summary
$data_summary = $mp_api->get_data_summary();

// Access specific data
$total_members = isset($data_summary['total_members']) ? $data_summary['total_members'] : 0;
$active_subscriptions = isset($data_summary['active_subscriptions']) ? $data_summary['active_subscriptions'] : 0;

echo "Total Members: {$total_members}<br>";
echo "Active Subscriptions: {$active_subscriptions}<br>";
```

### Working with Conversations

```php
// Initialize the Chat handler
$chat = new MPAI_Chat();

// Get user's conversation history
$user_id = get_current_user_id();
$conversations = $chat->get_user_conversations($user_id);

// Display conversation history
foreach ($conversations as $conversation) {
    echo "<h3>Conversation #{$conversation->id}</h3>";
    echo "<p>Started: {$conversation->created_at}</p>";
    
    $messages = $chat->get_conversation_messages($conversation->conversation_id);
    
    echo "<ul>";
    foreach ($messages as $message) {
        echo "<li><strong>User:</strong> {$message->message}</li>";
        echo "<li><strong>AI:</strong> {$message->response}</li>";
    }
    echo "</ul>";
}
```

## Security Considerations

### API Key Security

For enhanced API key security:

```php
// Define API key in wp-config.php instead of storing in database
define('MPAI_OPENAI_API_KEY', 'your-api-key-here');

// Then modify the MPAI_OpenAI class constructor
public function __construct() {
    // Use constant if defined, otherwise use option
    if (defined('MPAI_OPENAI_API_KEY')) {
        $this->api_key = MPAI_OPENAI_API_KEY;
    } else {
        $this->api_key = get_option('mpai_api_key', '');
    }
    
    // ...rest of constructor...
}
```

### Command Execution Security

Implement additional command validation:

```php
/**
 * Enhanced command validation
 * 
 * @param string $command The command to validate
 * @return bool Whether the command is safe to execute
 */
private function validate_command($command) {
    // Check whitelist first
    if (!$this->is_command_allowed($command)) {
        return false;
    }
    
    // Additional security checks
    
    // No shell execution
    if (preg_match('/(shell_exec|exec|system|passthru|eval)/', $command)) {
        return false;
    }
    
    // No piping or chaining with &&, ||, ;
    if (preg_match('/(\||&|;)/', $command)) {
        return false;
    }
    
    // No file operations outside of safe commands
    if (preg_match('/(rm|mv|cp|chmod|chown)/', $command) && 
        !preg_match('/^wp (plugin|theme|core)/', $command)) {
        return false;
    }
    
    return true;
}
```

## Performance Optimization

### Implement Caching for API Responses

```php
/**
 * Get cached completion or generate new one
 * 
 * @param string $prompt User prompt
 * @param array $memberpress_data MemberPress data
 * @return string Generated completion
 */
public function get_cached_completion($prompt, $memberpress_data) {
    // Generate cache key
    $cache_key = 'mpai_completion_' . md5($prompt . serialize($memberpress_data));
    
    // Check cache first
    $cached_response = get_transient($cache_key);
    if ($cached_response !== false) {
        return $cached_response;
    }
    
    // Generate new completion
    $completion = $this->generate_memberpress_completion($prompt, $memberpress_data);
    
    // Cache if not an error
    if (!is_wp_error($completion)) {
        set_transient($cache_key, $completion, HOUR_IN_SECONDS * 6);
    }
    
    return $completion;
}
```

### Batch Process MemberPress Data

```php
/**
 * Get MemberPress data in batches to avoid memory issues
 * 
 * @param string $type Data type to retrieve
 * @param int $batch_size Batch size
 * @param int $page Page number
 * @return array Data batch
 */
public function get_data_batch($type, $batch_size = 100, $page = 1) {
    $offset = ($page - 1) * $batch_size;
    $results = array();
    
    switch ($type) {
        case 'members':
            // Get members in batches
            $args = array(
                'number' => $batch_size,
                'offset' => $offset,
                'fields' => array('ID', 'user_email', 'display_name', 'user_registered')
            );
            $users = get_users($args);
            
            foreach ($users as $user) {
                $member = new MeprUser($user->ID);
                $results[] = array(
                    'id' => $user->ID,
                    'email' => $user->user_email,
                    'name' => $user->display_name,
                    'registered' => $user->user_registered,
                    'active_memberships' => $member->active_product_subscriptions('ids')
                );
            }
            break;
            
        case 'transactions':
            // Get transactions in batches
            global $wpdb;
            $mepr_db = new MeprDb();
            $table = $mepr_db->transactions;
            
            $query = "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT {$offset}, {$batch_size}";
            $transactions = $wpdb->get_results($query);
            
            foreach ($transactions as $txn) {
                $results[] = array(
                    'id' => $txn->id,
                    'user_id' => $txn->user_id,
                    'product_id' => $txn->product_id,
                    'amount' => $txn->amount,
                    'status' => $txn->status,
                    'created_at' => $txn->created_at
                );
            }
            break;
    }
    
    return $results;
}
```

## Troubleshooting for Developers

### Debugging OpenAI Requests

Add this code to your theme's functions.php or a custom plugin:

```php
// Enable logging of OpenAI requests
add_action('mpai_before_openai_request', 'debug_openai_requests', 10, 2);

function debug_openai_requests($messages, $params) {
    // Only log if WP_DEBUG is enabled
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        return;
    }
    
    // Create a log entry
    $log = array(
        'time' => current_time('mysql'),
        'messages' => $messages,
        'params' => $params
    );
    
    // Write to debug log
    error_log('OpenAI Request: ' . print_r($log, true));
}

// Also log responses
add_action('mpai_after_openai_request', 'debug_openai_responses', 10, 2);

function debug_openai_responses($response, $messages) {
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        return;
    }
    
    // Log usage data if available
    $usage = isset($response['usage']) ? $response['usage'] : 'No usage data';
    
    // Write to debug log
    error_log('OpenAI Response - Usage: ' . print_r($usage, true));
}
```

### Error Handling Best Practices

```php
/**
 * Handle API errors gracefully
 * 
 * @param mixed $response API response or WP_Error
 * @return string Formatted error message or original response
 */
function mpai_handle_api_errors($response) {
    if (is_wp_error($response)) {
        $error_code = $response->get_error_code();
        $error_message = $response->get_error_message();
        
        // Log detailed error
        error_log("MemberPress AI Assistant Error ({$error_code}): {$error_message}");
        
        // Return user-friendly message based on error type
        switch ($error_code) {
            case 'openai_error':
                return "The AI service encountered an error. Please try again later.";
                
            case 'missing_api_key':
                return "The plugin is not properly configured. Please contact the administrator.";
                
            case 'http_request_failed':
                return "Could not connect to the AI service. Please check your internet connection.";
                
            default:
                return "An unexpected error occurred. Please try again later.";
        }
    }
    
    // Return original response if not an error
    return $response;
}
```

## Support and Contribution

If you want to contribute to the development of this plugin:

1. Fork the repository on GitHub
2. Create a feature branch (`git checkout -b feature/your-feature-name`)
3. Make your changes following WordPress coding standards
4. Write/update tests as needed
5. Submit a pull request

For bug reports and feature requests, please use the GitHub issue tracker.

## Changelog

See CHANGELOG.md for a detailed list of changes between versions.