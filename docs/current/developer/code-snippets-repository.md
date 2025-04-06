# MemberPress AI Assistant: Code Snippets Repository

**Version:** 1.0.0  
**Last Updated:** 2025-04-06  
**Status:** 🚧 In Progress  
**Audience:** 👩‍💻 Developers  
**Difficulty:** 🟡 Intermediate  
**Reading Time:** ⏱️ 20 minutes

## Overview

This repository contains reusable code snippets for extending and customizing the MemberPress AI Assistant. Each snippet is designed to address a specific customization need and includes detailed explanations and implementation instructions.

## Table of Contents

1. [How to Use These Snippets](#how-to-use-these-snippets)
2. [UI Customization](#ui-customization)
3. [Response Modification](#response-modification)
4. [Custom Data Integration](#custom-data-integration)
5. [Advanced Prompting](#advanced-prompting)
6. [Logging and Analytics](#logging-and-analytics)
7. [Performance Optimization](#performance-optimization)
8. [Integration With Other Plugins](#integration-with-other-plugins)
9. [Permission Management](#permission-management)
10. [Contributing New Snippets](#contributing-new-snippets)

## How to Use These Snippets

### Implementation Methods

There are several ways to implement these code snippets:

1. **Site-Specific Plugin**: Create a simple plugin containing your chosen snippets (recommended)
2. **Theme functions.php**: Add snippets to your theme's functions.php file
3. **Code Snippets Plugin**: Use a plugin like "Code Snippets" to add these without creating a custom plugin

### Implementation Best Practices

1. **Always test in a staging environment first**
2. **Use conditional loading** to ensure snippets only run when the MemberPress AI Assistant is active
3. **Add proper documentation comments** to help future maintenance
4. **Keep track of which snippets you've implemented** for easier troubleshooting

### Example Implementation as a Simple Plugin

```php
<?php
/**
 * Plugin Name: My MemberPress AI Assistant Customizations
 * Description: Custom modifications for MemberPress AI Assistant
 * Version: 1.0.0
 * Author: Your Name
 */

// Ensure the plugin doesn't load directly
if (!defined('ABSPATH')) {
    exit;
}

// Only load when MemberPress AI Assistant is active
add_action('plugins_loaded', function() {
    if (!function_exists('is_plugin_active')) {
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');
    }
    
    if (is_plugin_active('memberpress-ai-assistant/memberpress-ai-assistant.php')) {
        // Add your chosen snippets here
        require_once(plugin_dir_path(__FILE__) . 'snippets/custom-ui-theme.php');
        require_once(plugin_dir_path(__FILE__) . 'snippets/response-formatter.php');
        // etc.
    }
});
```

## UI Customization

### Custom Chat Interface Theme

This snippet applies a custom theme to the AI Assistant chat interface.

```php
/**
 * Apply a custom theme to the AI Assistant chat interface
 */
add_filter('memberpress_ai_interface_settings', 'custom_ai_interface_theme');

function custom_ai_interface_theme($settings) {
    // Custom theme colors
    $settings['colors'] = [
        'primary' => '#4A90E2',       // Primary color (buttons, active elements)
        'secondary' => '#F5A623',     // Secondary color (highlights, accents)
        'background' => '#FFFFFF',    // Chat background
        'text' => '#333333',          // Main text color
        'secondaryText' => '#666666', // Secondary text color
        'border' => '#E0E0E0',        // Border color
        'shadow' => 'rgba(0,0,0,0.1)', // Shadow color
    ];
    
    // Custom typography
    $settings['typography'] = [
        'fontFamily' => 'Roboto, -apple-system, BlinkMacSystemFont, sans-serif',
        'fontSize' => '14px',
        'lineHeight' => '1.5',
    ];
    
    // Chat window settings
    $settings['window'] = [
        'width' => '350px',
        'height' => '500px',
        'borderRadius' => '12px',
        'position' => 'bottom-right', // Options: bottom-right, bottom-left, top-right, top-left
    ];
    
    return $settings;
}
```

### Custom AI Assistant Welcome Message

This snippet customizes the welcome message shown when a user first opens the AI Assistant.

```php
/**
 * Customize the AI Assistant welcome message
 */
add_filter('memberpress_ai_welcome_message', 'custom_ai_welcome_message', 10, 2);

function custom_ai_welcome_message($message, $user_id) {
    // Get user info
    $user = get_userdata($user_id);
    
    if (!$user) {
        return $message;
    }
    
    // Check time of day
    $hour = current_time('G');
    $greeting = '';
    
    if ($hour < 12) {
        $greeting = 'Good morning';
    } elseif ($hour < 18) {
        $greeting = 'Good afternoon';
    } else {
        $greeting = 'Good evening';
    }
    
    // User's first name
    $first_name = $user->first_name ? $user->first_name : $user->display_name;
    
    // Customize the message
    $custom_message = "$greeting, $first_name! 👋 I'm your MemberPress AI Assistant. ";
    $custom_message .= "I can help you with membership questions, provide analytics, or assist with content ideas. ";
    $custom_message .= "What can I help you with today?";
    
    return $custom_message;
}
```

### Add Custom Action Buttons to Chat

This snippet adds custom action buttons to the AI Assistant chat interface.

```php
/**
 * Add custom action buttons to the AI Assistant interface
 */
add_filter('memberpress_ai_action_buttons', 'add_custom_ai_action_buttons');

function add_custom_ai_action_buttons($buttons) {
    // Add a button to analyze membership metrics
    $buttons['analyze_metrics'] = [
        'label' => 'Analyze Metrics',
        'icon' => 'chart-line', // FontAwesome icon name
        'prompt' => 'Please analyze my membership metrics for the past 30 days.',
        'position' => 'top', // Options: top, bottom
    ];
    
    // Add a button to generate content ideas
    $buttons['content_ideas'] = [
        'label' => 'Content Ideas',
        'icon' => 'lightbulb',
        'prompt' => 'Suggest 5 content ideas for my membership site based on member interests.',
        'position' => 'top',
    ];
    
    // Add a help button
    $buttons['help_guide'] = [
        'label' => 'Help Guide',
        'icon' => 'question-circle',
        'prompt' => 'Show me what you can do and how to use you effectively.',
        'position' => 'bottom',
    ];
    
    return $buttons;
}
```

## Response Modification

### Format AI Responses with Custom Styling

This snippet enhances AI responses with custom formatting and styling.

```php
/**
 * Apply custom formatting to AI responses
 */
add_filter('memberpress_ai_modify_response', 'format_ai_response', 10, 3);

function format_ai_response($response, $query, $service) {
    // Don't modify if response is empty or an error message
    if (empty($response) || strpos($response, 'Error:') === 0) {
        return $response;
    }
    
    // Convert markdown headings to styled HTML headings
    $response = preg_replace('/^### (.*?)$/m', '<h3 class="ai-response-heading">$1</h3>', $response);
    $response = preg_replace('/^## (.*?)$/m', '<h2 class="ai-response-heading">$1</h2>', $response);
    $response = preg_replace('/^# (.*?)$/m', '<h1 class="ai-response-heading">$1</h1>', $response);
    
    // Style lists
    $response = preg_replace('/^\* (.*?)$/m', '<li class="ai-list-item">$1</li>', $response);
    $response = preg_replace('/^\d+\. (.*?)$/m', '<li class="ai-numbered-item">$1</li>', $response);
    
    // Wrap list items in list containers
    $response = preg_replace('/((?:<li class="ai-list-item">.*?<\/li>\n?)+)/', '<ul class="ai-list">$1</ul>', $response);
    $response = preg_replace('/((?:<li class="ai-numbered-item">.*?<\/li>\n?)+)/', '<ol class="ai-numbered-list">$1</ol>', $response);
    
    // Highlight important information
    $response = preg_replace('/\*\*(.*?)\*\*/', '<strong class="ai-highlight">$1</strong>', $response);
    
    // Format code sections
    $response = preg_replace('/```(.*?)```/s', '<pre class="ai-code-block">$1</pre>', $response);
    $response = preg_replace('/`(.*?)`/', '<code class="ai-inline-code">$1</code>', $response);
    
    // Add a branded footer
    $response .= '<div class="ai-response-footer">Powered by MemberPress AI Assistant</div>';
    
    return $response;
}
```

### Add Custom Data to AI Responses

This snippet adds custom data to AI responses based on the query topic.

```php
/**
 * Add supplemental information to AI responses based on topic
 */
add_filter('memberpress_ai_modify_response', 'add_supplemental_data_to_response', 20, 3);

function add_supplemental_data_to_response($response, $query, $service) {
    // Check for different topic categories
    $topic_categories = [
        'pricing' => ['price', 'cost', 'subscription', 'payment', 'discount'],
        'technical' => ['error', 'problem', 'bug', 'not working', 'failed'],
        'content' => ['content', 'article', 'post', 'lesson', 'course'],
    ];
    
    $detected_category = null;
    
    foreach ($topic_categories as $category => $keywords) {
        foreach ($keywords as $keyword) {
            if (stripos($query, $keyword) !== false) {
                $detected_category = $category;
                break 2;
            }
        }
    }
    
    // Add supplemental content based on category
    if ($detected_category === 'pricing') {
        $response .= "\n\n---\n\n";
        $response .= "**Additional Pricing Resources:**\n\n";
        $response .= "* [Pricing Strategy Guide](https://example.com/pricing-guide)\n";
        $response .= "* [How to Set Up Coupons](https://example.com/coupon-setup)\n";
        $response .= "* Contact our pricing specialist at pricing@example.com\n";
    } 
    else if ($detected_category === 'technical') {
        $response .= "\n\n---\n\n";
        $response .= "**Need Technical Help?**\n\n";
        $response .= "* [Troubleshooting Guide](https://example.com/troubleshooting)\n";
        $response .= "* [Technical Support Portal](https://example.com/support)\n";
        $response .= "* Our support team is available weekdays 9am-5pm EST\n";
    }
    else if ($detected_category === 'content') {
        $response .= "\n\n---\n\n";
        $response .= "**Content Development Resources:**\n\n";
        $response .= "* [Content Strategy Template](https://example.com/content-strategy)\n";
        $response .= "* [Member Engagement Guide](https://example.com/engagement)\n";
        $response .= "* Schedule a content consultation: content@example.com\n";
    }
    
    return $response;
}
```

### Response Sentiment Analysis and Enhancement

This snippet analyzes the sentiment of AI responses and ensures they maintain a positive, helpful tone.

```php
/**
 * Analyze and enhance the sentiment of AI responses
 * Requires the Text Analysis API plugin or similar sentiment analysis tool
 */
add_filter('memberpress_ai_modify_response', 'enhance_response_sentiment', 15, 3);

function enhance_response_sentiment($response, $query, $service) {
    // Skip if no sentiment analysis capability is available
    if (!function_exists('text_analysis_get_sentiment')) {
        return $response;
    }
    
    // Get sentiment score (-1 to 1, where -1 is negative, 1 is positive)
    $sentiment = text_analysis_get_sentiment($response);
    
    // If sentiment is neutral or negative, add a positive closing
    if ($sentiment < 0.3) {
        // Response currently has a neutral or negative tone
        
        // Add a positive closing based on query type
        if (stripos($query, 'problem') !== false || stripos($query, 'error') !== false || 
            stripos($query, 'issue') !== false || stripos($query, 'help') !== false) {
            // For problem-related queries
            $response .= "\n\nI hope this helps resolve your issue! If you need any clarification or have further questions, please don't hesitate to ask. We're here to make your experience with MemberPress as smooth as possible.";
        } 
        else if (stripos($query, 'how') === 0 || stripos($query, 'what') === 0) {
            // For informational queries
            $response .= "\n\nI hope this information is helpful! Let me know if you'd like more details or have any other questions about this topic.";
        }
        else {
            // Default positive closing
            $response .= "\n\nIs there anything else I can help you with today? I'm here to make your MemberPress experience successful!";
        }
    }
    
    return $response;
}
```

## Custom Data Integration

### Add Custom Member Data to AI Context

This snippet adds custom member data fields to the AI context for more personalized responses.

```php
/**
 * Add custom member data fields to AI context
 */
add_filter('memberpress_ai_query_context', 'add_custom_member_data', 10, 3);

function add_custom_member_data($context, $query, $user_id) {
    // Skip if no user ID
    if (!$user_id) {
        return $context;
    }
    
    // Get custom member fields (example - adjust to your actual custom fields)
    $industry = get_user_meta($user_id, 'member_industry', true);
    $interests = get_user_meta($user_id, 'member_interests', true);
    $experience_level = get_user_meta($user_id, 'member_experience_level', true);
    $content_preferences = get_user_meta($user_id, 'content_preferences', true);
    
    // Get engagement data (this is a custom function example)
    $engagement_data = get_member_engagement_metrics($user_id);
    
    // Add to context if available
    if ($industry) {
        $context['member_data']['industry'] = $industry;
    }
    
    if ($interests) {
        $context['member_data']['interests'] = $interests;
    }
    
    if ($experience_level) {
        $context['member_data']['experience_level'] = $experience_level;
    }
    
    if ($content_preferences) {
        $context['member_data']['content_preferences'] = $content_preferences;
    }
    
    if ($engagement_data) {
        $context['member_data']['engagement'] = [
            'login_frequency' => $engagement_data['login_frequency'],
            'content_completion_rate' => $engagement_data['completion_rate'],
            'favorite_content_types' => $engagement_data['favorite_types'],
            'last_active' => $engagement_data['last_active'],
        ];
    }
    
    return $context;
}

/**
 * Example function to get member engagement metrics
 * Replace with your actual implementation
 */
function get_member_engagement_metrics($user_id) {
    // This is a placeholder function
    // Replace with your actual engagement tracking implementation
    
    // Example - retrieving data from custom tables or analytics
    global $wpdb;
    $table_name = $wpdb->prefix . 'member_engagement';
    
    $data = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d", $user_id),
        ARRAY_A
    );
    
    return $data ?: [
        'login_frequency' => 'unknown',
        'completion_rate' => 0,
        'favorite_types' => [],
        'last_active' => 'unknown',
    ];
}
```

### Integrate Third-Party CRM Data

This snippet integrates third-party CRM data into the AI context.

```php
/**
 * Integrate third-party CRM data into the AI Assistant context
 * Example shown for HubSpot - modify API calls for your specific CRM
 */
add_filter('memberpress_ai_query_context', 'integrate_crm_data', 20, 3);

function integrate_crm_data($context, $query, $user_id) {
    // Skip if no user ID or if query doesn't seem to need CRM data
    if (!$user_id || (!stripos($query, 'account') && !stripos($query, 'subscription') && 
                      !stripos($query, 'billing') && !stripos($query, 'contact'))) {
        return $context;
    }
    
    // Get user email
    $user = get_userdata($user_id);
    if (!$user || !$user->user_email) {
        return $context;
    }
    
    // Get CRM data - this is a custom function that you'd implement
    $crm_data = get_crm_data_for_email($user->user_email);
    
    // If CRM data available, add to context
    if ($crm_data && !empty($crm_data)) {
        $context['crm_data'] = [
            'customer_status' => $crm_data['status'] ?? 'unknown',
            'lifetime_value' => $crm_data['lifetime_value'] ?? 0,
            'support_tickets' => $crm_data['support_ticket_count'] ?? 0,
            'account_manager' => $crm_data['account_manager'] ?? null,
            'last_contact' => $crm_data['last_contact_date'] ?? null,
            'notes' => $crm_data['notes'] ?? [],
        ];
    }
    
    return $context;
}

/**
 * Example function to get CRM data for a user email
 * Replace with your actual CRM integration
 */
function get_crm_data_for_email($email) {
    // This is a placeholder function
    // Replace with your actual CRM API implementation
    
    // Example - HubSpot API request
    $api_key = get_option('hubspot_api_key');
    if (!$api_key) {
        return null;
    }
    
    // Cache key for this request
    $cache_key = 'crm_data_' . md5($email);
    
    // Try to get from cache first
    $cached_data = get_transient($cache_key);
    if ($cached_data !== false) {
        return $cached_data;
    }
    
    // Make API request to HubSpot (this is a simplified example)
    $response = wp_remote_get(
        'https://api.hubapi.com/crm/v3/objects/contacts/search',
        [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
                'filterGroups' => [
                    [
                        'filters' => [
                            [
                                'propertyName' => 'email',
                                'operator' => 'EQ',
                                'value' => $email,
                            ],
                        ],
                    ],
                ],
                'properties' => ['status', 'lifecyclestage', 'total_revenue', 'last_contacted', 'owner'],
                'limit' => 1,
            ]),
        ]
    );
    
    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        return null;
    }
    
    $body = json_decode(wp_remote_retrieve_body($response), true);
    $contact = $body['results'][0] ?? null;
    
    if (!$contact) {
        return null;
    }
    
    // Format data for our needs
    $data = [
        'status' => $contact['properties']['lifecyclestage'] ?? 'unknown',
        'lifetime_value' => $contact['properties']['total_revenue'] ?? 0,
        'last_contact_date' => $contact['properties']['last_contacted'] ?? null,
        'account_manager' => $contact['properties']['owner'] ?? null,
        // Additional data would come from other API endpoints
    ];
    
    // Cache for 1 hour
    set_transient($cache_key, $data, HOUR_IN_SECONDS);
    
    return $data;
}
```

### Add Content Usage Analytics

This snippet adds content usage analytics data to help the AI provide better content recommendations.

```php
/**
 * Add content usage analytics to AI context
 */
add_filter('memberpress_ai_query_context', 'add_content_analytics', 10, 3);

function add_content_analytics($context, $query, $user_id) {
    // Check if query is related to content or recommendations
    $content_related = stripos($query, 'content') !== false || 
                        stripos($query, 'recommend') !== false || 
                        stripos($query, 'suggest') !== false || 
                        stripos($query, 'course') !== false || 
                        stripos($query, 'popular') !== false;
    
    if (!$content_related) {
        return $context;
    }
    
    // Get global content analytics
    $popular_content = get_option('memberpress_popular_content', []);
    $trending_content = get_option('memberpress_trending_content', []);
    $content_engagement = get_option('memberpress_content_engagement_rates', []);
    
    // Get user-specific content history if user is logged in
    $user_content_history = [];
    if ($user_id) {
        $user_content_history = get_user_meta($user_id, 'memberpress_content_history', true);
        if (!is_array($user_content_history)) {
            $user_content_history = [];
        }
    }
    
    // Add analytics data to context
    $context['content_analytics'] = [
        'popular_content' => array_slice($popular_content, 0, 5), // Top 5 most popular
        'trending_content' => array_slice($trending_content, 0, 5), // Top 5 trending
        'highest_engagement' => array_slice($content_engagement, 0, 5, true), // Top 5 by engagement
    ];
    
    // Add user history if available
    if (!empty($user_content_history)) {
        $context['content_analytics']['user_history'] = array_slice($user_content_history, 0, 10); // Last 10 items
    }
    
    return $context;
}
```

## Advanced Prompting

### Custom System Instructions by Query Type

This snippet modifies the AI system instructions based on the type of query detected.

```php
/**
 * Customize system instructions based on query type
 */
add_filter('memberpress_ai_system_instructions', 'customize_system_instructions', 10, 2);

function customize_system_instructions($instructions, $query) {
    // Detect query intent/type
    $query_type = detect_query_type($query);
    
    // Customize instructions based on query type
    switch ($query_type) {
        case 'technical_support':
            $instructions = "You are a technical support specialist for MemberPress. Provide clear, step-by-step instructions to solve technical problems. Include specific WordPress paths, function names, and code examples when appropriate. If a solution requires code, provide complete snippets that can be copied and pasted. For complex issues, outline troubleshooting steps in a logical order.";
            break;
            
        case 'marketing_advice':
            $instructions = "You are a membership marketing expert. Provide actionable marketing advice specific to membership sites. Focus on proven strategies for member acquisition, engagement, and retention. Include specific examples, metrics to track, and tools to consider. Make recommendations that are both practical and data-driven.";
            break;
            
        case 'content_strategy':
            $instructions = "You are a content strategy specialist for membership sites. Provide detailed advice on content creation, organization, and delivery. Recommend content types that drive engagement, optimize for member retention, and encourage upgrades. Consider the membership business model in all recommendations.";
            break;
            
        case 'analytics_interpretation':
            $instructions = "You are a data analyst specializing in membership analytics. Interpret data in a way that highlights actionable insights. Explain metrics in plain language while maintaining accuracy. Identify patterns, anomalies, and opportunities. Recommend specific actions based on the data presented.";
            break;
            
        case 'general_question':
            // Use default instructions for general questions
            break;
    }
    
    return $instructions;
}

/**
 * Helper function to detect query type based on keywords and patterns
 */
function detect_query_type($query) {
    // Default type
    $type = 'general_question';
    
    // Technical support keywords
    $technical_keywords = ['error', 'problem', 'bug', 'not working', 'failed', 'broken', 'fix', 'code', 'plugin', 'conflict'];
    
    // Marketing keywords
    $marketing_keywords = ['marketing', 'campaign', 'conversion', 'acquire', 'promote', 'advertise', 'leads', 'sales', 'funnel'];
    
    // Content strategy keywords
    $content_keywords = ['content', 'article', 'post', 'course', 'lesson', 'drip', 'create', 'engagement', 'schedule'];
    
    // Analytics keywords
    $analytics_keywords = ['analytics', 'data', 'metrics', 'stats', 'report', 'numbers', 'tracking', 'performance', 'results'];
    
    // Check for technical support questions
    foreach ($technical_keywords as $keyword) {
        if (stripos($query, $keyword) !== false) {
            return 'technical_support';
        }
    }
    
    // Check for marketing questions
    foreach ($marketing_keywords as $keyword) {
        if (stripos($query, $keyword) !== false) {
            return 'marketing_advice';
        }
    }
    
    // Check for content strategy questions
    foreach ($content_keywords as $keyword) {
        if (stripos($query, $keyword) !== false) {
            return 'content_strategy';
        }
    }
    
    // Check for analytics questions
    foreach ($analytics_keywords as $keyword) {
        if (stripos($query, $keyword) !== false) {
            return 'analytics_interpretation';
        }
    }
    
    return $type;
}
```

### Context-Aware Prompt Enhancement

This snippet enhances AI prompts with contextual information based on the current WordPress page.

```php
/**
 * Add contextual information to AI prompts based on current page
 */
add_filter('memberpress_ai_modify_prompt', 'enhance_prompt_with_page_context', 10, 3);

function enhance_prompt_with_page_context($prompt, $context, $service) {
    // Get current screen in admin
    $current_screen = function_exists('get_current_screen') ? get_current_screen() : null;
    
    // Add page-specific context to the prompt
    if ($current_screen) {
        // In WordPress admin
        switch ($current_screen->id) {
            case 'mepr-members':
                $prompt = "The user is currently on the MemberPress Members page. " . $prompt;
                break;
                
            case 'mepr-subscriptions':
                $prompt = "The user is currently viewing subscription data in MemberPress. " . $prompt;
                break;
                
            case 'mepr-options':
                $prompt = "The user is currently in the MemberPress settings page. " . $prompt;
                break;
                
            case 'mepr-reports':
                $prompt = "The user is currently viewing MemberPress reports and analytics. " . $prompt;
                break;
                
            default:
                // If in another admin page
                if ($current_screen->post_type) {
                    $prompt = "The user is currently working with {$current_screen->post_type} content. " . $prompt;
                }
        }
    } else {
        // On front-end
        global $post;
        
        if (isset($post) && $post instanceof WP_Post) {
            $post_title = $post->post_title;
            $post_type = $post->post_type;
            
            $prompt = "The user is currently viewing a $post_type titled \"$post_title\". " . $prompt;
            
            // Check if it's a membership-protected content
            if (function_exists('mepr_is_protected_by_rule') && mepr_is_protected_by_rule($post->ID)) {
                $prompt = "The user is viewing protected membership content. " . $prompt;
            }
        }
    }
    
    return $prompt;
}
```

### Multi-Modal Prompt Enhancement for AI

This snippet enhances prompts with multi-modal information when available.

```php
/**
 * Add multi-modal support to AI prompts where applicable
 * This is advanced functionality that requires a compatible AI service
 */
add_filter('memberpress_ai_modify_prompt', 'enhance_prompt_with_multimodal', 20, 3);

function enhance_prompt_with_multimodal($prompt, $context, $service) {
    // Check if the service supports multi-modal input
    $supports_multimodal = in_array($service, ['anthropic_claude3', 'openai_vision', 'custom_multimodal']);
    
    if (!$supports_multimodal) {
        return $prompt;
    }
    
    // Check if we have any images or data visualizations to include
    if (isset($context['visualization_data']) && !empty($context['visualization_data'])) {
        // This is a multimodal-supporting service, so we can include image descriptions
        
        foreach ($context['visualization_data'] as $visualization) {
            // Add reference to the visual data
            $prompt .= "\n\nPlease refer to the " . $visualization['type'] . " showing " . 
                      $visualization['title'] . " when answering this question. " .
                      "The visualization displays " . $visualization['description'] . ".";
        }
    }
    
    // Check if we have any analyzed screenshots to include
    if (isset($context['screenshots']) && !empty($context['screenshots'])) {
        foreach ($context['screenshots'] as $screenshot) {
            $prompt .= "\n\nI am also providing a screenshot of " . $screenshot['description'] . 
                      ". Please reference this visual information in your response when relevant.";
        }
    }
    
    return $prompt;
}
```

## Logging and Analytics

### Comprehensive AI Assistant Usage Logging

This snippet creates a comprehensive logging system for the AI Assistant interactions.

```php
/**
 * Log detailed information about AI Assistant usage
 */
add_action('memberpress_ai_after_response_generation', 'log_ai_assistant_usage', 10, 3);

function log_ai_assistant_usage($response, $query, $service) {
    global $wpdb;
    
    // Create a log table if it doesn't exist (preferably do this on plugin activation)
    $table_name = $wpdb->prefix . 'mepr_ai_usage_logs';
    
    // Skip if table doesn't exist
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        error_log('MemberPress AI Assistant logging table does not exist');
        return;
    }
    
    // Get basic query metrics
    $query_length = strlen($query);
    $response_length = strlen($response);
    $token_estimate = estimate_token_count($query) + estimate_token_count($response);
    $query_type = categorize_query($query);
    
    // Get user information
    $user_id = get_current_user_id();
    $user_role = $user_id ? implode(', ', get_userdata($user_id)->roles) : 'guest';
    
    // Get request metadata
    $request_page = isset($_SERVER['HTTP_REFERER']) ? sanitize_text_field($_SERVER['HTTP_REFERER']) : '';
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '';
    
    // Log the interaction
    $wpdb->insert(
        $table_name,
        [
            'user_id' => $user_id,
            'user_role' => $user_role,
            'query' => $query,
            'query_type' => $query_type,
            'query_length' => $query_length,
            'response_length' => $response_length,
            'token_estimate' => $token_estimate,
            'service' => $service,
            'request_page' => $request_page,
            'user_agent' => $user_agent,
            'timestamp' => current_time('mysql'),
            'session_id' => get_ai_session_id(),
        ],
        [
            '%d', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s'
        ]
    );
}

/**
 * Helper function to estimate token count
 * This is a simplified approximation since actual tokenization varies by model
 */
function estimate_token_count($text) {
    // Average tokens per character (approximate)
    $avg_tokens_per_char = 0.25;
    
    // Estimate token count
    return ceil(strlen($text) * $avg_tokens_per_char);
}

/**
 * Helper function to categorize queries
 */
function categorize_query($query) {
    $categories = [
        'account' => ['account', 'subscription', 'payment', 'invoice', 'billing'],
        'technical' => ['error', 'problem', 'not working', 'help', 'how to'],
        'content' => ['content', 'article', 'post', 'course', 'lesson'],
        'analytics' => ['analytics', 'stats', 'metrics', 'report', 'numbers'],
        'marketing' => ['marketing', 'promotion', 'campaign', 'advertise', 'conversion'],
    ];
    
    foreach ($categories as $category => $keywords) {
        foreach ($keywords as $keyword) {
            if (stripos($query, $keyword) !== false) {
                return $category;
            }
        }
    }
    
    return 'general';
}

/**
 * Helper function to get or create an AI session ID
 */
function get_ai_session_id() {
    $session_id = isset($_COOKIE['mepr_ai_session']) ? sanitize_text_field($_COOKIE['mepr_ai_session']) : null;
    
    if (!$session_id) {
        // Create a new session ID
        $session_id = 'sess_' . uniqid() . '_' . time();
        
        // Set a cookie that expires in 30 minutes
        setcookie('mepr_ai_session', $session_id, time() + 1800, COOKIEPATH, COOKIE_DOMAIN);
    }
    
    return $session_id;
}
```

### User Satisfaction Tracking

This snippet implements user satisfaction tracking for AI Assistant responses.

```php
/**
 * Add user satisfaction tracking for AI responses
 */
add_filter('memberpress_ai_render_response', 'add_satisfaction_tracking', 10, 3);

function add_satisfaction_tracking($html, $response, $query) {
    // Generate a unique ID for this response
    $response_id = 'air_' . uniqid();
    
    // Add satisfaction tracking UI
    $tracking_html = '<div class="ai-satisfaction-tracker" data-response-id="' . esc_attr($response_id) . '">';
    $tracking_html .= '<p class="satisfaction-question">Was this response helpful?</p>';
    $tracking_html .= '<div class="satisfaction-buttons">';
    $tracking_html .= '<button type="button" class="satisfaction-button positive" data-value="positive" aria-label="Yes, this was helpful">';
    $tracking_html .= '<span class="dashicons dashicons-yes"></span> Yes';
    $tracking_html .= '</button>';
    $tracking_html .= '<button type="button" class="satisfaction-button negative" data-value="negative" aria-label="No, this was not helpful">';
    $tracking_html .= '<span class="dashicons dashicons-no"></span> No';
    $tracking_html .= '</button>';
    $tracking_html .= '</div>';
    $tracking_html .= '<div class="feedback-form" style="display: none;">';
    $tracking_html .= '<textarea class="feedback-text" placeholder="Please tell us how we could improve this response"></textarea>';
    $tracking_html .= '<button type="button" class="send-feedback-button">Send Feedback</button>';
    $tracking_html .= '</div>';
    $tracking_html .= '<div class="feedback-thanks" style="display: none;">Thank you for your feedback!</div>';
    $tracking_html .= '</div>';
    
    // Add the tracking script (using jQuery which is loaded by WordPress)
    $tracking_html .= '<script type="text/javascript">
        jQuery(document).ready(function($) {
            // Handle satisfaction button clicks
            $(".ai-satisfaction-tracker[data-response-id=\'' . $response_id . '\'] .satisfaction-button").on("click", function() {
                var value = $(this).data("value");
                var $tracker = $(this).closest(".ai-satisfaction-tracker");
                var responseId = $tracker.data("response-id");
                
                // Log the basic satisfaction
                logSatisfaction(responseId, value, "");
                
                // If negative, show feedback form
                if (value === "negative") {
                    $tracker.find(".satisfaction-buttons").hide();
                    $tracker.find(".feedback-form").show();
                } else {
                    // If positive, just show thanks
                    $tracker.find(".satisfaction-buttons").hide();
                    $tracker.find(".feedback-thanks").show();
                }
            });
            
            // Handle feedback submission
            $(".ai-satisfaction-tracker[data-response-id=\'' . $response_id . '\'] .send-feedback-button").on("click", function() {
                var $tracker = $(this).closest(".ai-satisfaction-tracker");
                var responseId = $tracker.data("response-id");
                var feedback = $tracker.find(".feedback-text").val();
                
                // Update the satisfaction log with feedback
                logSatisfaction(responseId, "negative", feedback);
                
                // Show thanks
                $tracker.find(".feedback-form").hide();
                $tracker.find(".feedback-thanks").show();
            });
            
            // Function to log satisfaction
            function logSatisfaction(responseId, value, feedback) {
                $.ajax({
                    url: ajaxurl,
                    type: "POST",
                    data: {
                        action: "log_ai_satisfaction",
                        response_id: responseId,
                        value: value,
                        feedback: feedback,
                        nonce: "' . wp_create_nonce('ai_satisfaction_nonce') . '"
                    }
                });
            }
        });
    </script>';
    
    // Add the tracking HTML after the response
    return $html . $tracking_html;
}

/**
 * Handle AJAX request to log satisfaction
 */
add_action('wp_ajax_log_ai_satisfaction', 'handle_ai_satisfaction_logging');
add_action('wp_ajax_nopriv_log_ai_satisfaction', 'handle_ai_satisfaction_logging');

function handle_ai_satisfaction_logging() {
    // Verify nonce
    check_ajax_referer('ai_satisfaction_nonce', 'nonce');
    
    // Get data
    $response_id = isset($_POST['response_id']) ? sanitize_text_field($_POST['response_id']) : '';
    $value = isset($_POST['value']) ? sanitize_text_field($_POST['value']) : '';
    $feedback = isset($_POST['feedback']) ? sanitize_textarea_field($_POST['feedback']) : '';
    
    // Log to database
    global $wpdb;
    $table_name = $wpdb->prefix . 'mepr_ai_satisfaction';
    
    $wpdb->insert(
        $table_name,
        [
            'response_id' => $response_id,
            'user_id' => get_current_user_id(),
            'satisfaction' => $value,
            'feedback' => $feedback,
            'timestamp' => current_time('mysql'),
            'page_url' => isset($_SERVER['HTTP_REFERER']) ? esc_url_raw($_SERVER['HTTP_REFERER']) : '',
        ],
        [
            '%s', '%d', '%s', '%s', '%s', '%s'
        ]
    );
    
    wp_send_json_success();
}
```

## Performance Optimization

### Optimize AI Assistant Cache Management

This snippet improves caching for the AI Assistant to reduce API costs and improve response times.

```php
/**
 * Optimize cache management for the AI Assistant
 */
add_filter('memberpress_ai_cache_expiration', 'optimize_ai_cache_duration', 10, 2);

function optimize_ai_cache_duration($duration, $query_type) {
    // Set different cache durations based on query type
    switch ($query_type) {
        // Static information rarely changes
        case 'documentation':
        case 'general_info':
        case 'how_to':
            return 7 * DAY_IN_SECONDS; // 7 days
            
        // Member data may change but not very frequently
        case 'member_stats':
        case 'subscription_overview':
            return DAY_IN_SECONDS; // 1 day
            
        // Financial data is more sensitive to be current
        case 'revenue':
        case 'transactions':
            return 6 * HOUR_IN_SECONDS; // 6 hours
            
        // Real-time data should be cached briefly
        case 'current_users':
        case 'live_metrics':
            return 15 * MINUTE_IN_SECONDS; // 15 minutes
            
        // Default for unspecified types
        default:
            return 3 * HOUR_IN_SECONDS; // 3 hours
    }
}

/**
 * Implement cache key refinement to improve cache hit rates
 */
add_filter('memberpress_ai_cache_key', 'refine_ai_cache_key', 10, 2);

function refine_ai_cache_key($cache_key, $query) {
    // Strip irrelevant variations that shouldn't affect caching
    
    // Normalize whitespace
    $normalized = trim(preg_replace('/\s+/', ' ', $query));
    
    // Remove common polite phrases that don't change the meaning
    $normalized = preg_replace('/^(please|could you|can you|i need|i want|help me)\s+/i', '', $normalized);
    $normalized = preg_replace('/\s+(please|thank you|thanks)\.?$/i', '', $normalized);
    
    // Remove question marks and other punctuation that doesn't affect meaning
    $normalized = str_replace(['?', '!', '.', ','], '', $normalized);
    
    // Generate the refined cache key
    return 'ai_response_' . md5($normalized);
}

/**
 * Smart cache invalidation based on related data changes
 */
function invalidate_related_ai_caches($trigger_type, $object_id = null) {
    // This should be called from appropriate hooks that indicate data changes
    
    // Get cache keys matching patterns related to the trigger
    $pattern = '';
    
    switch ($trigger_type) {
        case 'member_update':
            $pattern = 'ai_response_*member*';
            break;
            
        case 'transaction':
            $pattern = 'ai_response_*transaction*|ai_response_*revenue*|ai_response_*payment*';
            break;
            
        case 'content_update':
            $pattern = 'ai_response_*content*';
            break;
            
        case 'settings_update':
            $pattern = 'ai_response_*settings*|ai_response_*configuration*';
            break;
    }
    
    if (empty($pattern)) {
        return;
    }
    
    // Find matching cache keys (this would need to be implemented based on your caching solution)
    $matching_keys = find_matching_cache_keys($pattern);
    
    // Delete the matching caches
    foreach ($matching_keys as $key) {
        delete_transient($key);
    }
}

/**
 * Helper function to find matching cache keys
 * This is a simplified example - actual implementation depends on your caching setup
 */
function find_matching_cache_keys($pattern) {
    global $wpdb;
    
    // For transient-based caching
    $transient_keys = $wpdb->get_results(
        "SELECT option_name FROM {$wpdb->options} 
         WHERE option_name LIKE '%_transient_ai_response_%'"
    );
    
    $matching_keys = [];
    
    if ($transient_keys) {
        foreach ($transient_keys as $key_obj) {
            $key = str_replace('_transient_', '', $key_obj->option_name);
            
            // Check if the key matches the pattern
            if (preg_match('/' . $pattern . '/i', $key)) {
                $matching_keys[] = $key;
            }
        }
    }
    
    return $matching_keys;
}

// Example hooks to trigger cache invalidation
add_action('mepr_update_transaction', function($txn) {
    invalidate_related_ai_caches('transaction', $txn->id);
});

add_action('mepr_updated_user', function($user) {
    invalidate_related_ai_caches('member_update', $user->ID);
});

add_action('save_post', function($post_id) {
    if (get_post_type($post_id) === 'memberpressproduct') {
        invalidate_related_ai_caches('content_update', $post_id);
    }
});
```

## Integration With Other Plugins

### WooCommerce Integration for AI Context

This snippet integrates WooCommerce data into the AI Assistant context.

```php
/**
 * Add WooCommerce data to AI Assistant context
 * Requires WooCommerce plugin to be active
 */
add_filter('memberpress_ai_query_context', 'add_woocommerce_data_to_ai', 10, 3);

function add_woocommerce_data_to_ai($context, $query, $user_id) {
    // Check if WooCommerce is active
    if (!function_exists('WC') || !$user_id) {
        return $context;
    }
    
    // Check if query relates to purchases or products
    $purchase_related = stripos($query, 'purchase') !== false || 
                        stripos($query, 'order') !== false || 
                        stripos($query, 'buy') !== false || 
                        stripos($query, 'product') !== false;
    
    if (!$purchase_related) {
        return $context;
    }
    
    // Get customer data
    $customer = new WC_Customer($user_id);
    
    // Get recent orders
    $orders = wc_get_orders([
        'customer_id' => $user_id,
        'limit' => 5,
        'orderby' => 'date',
        'order' => 'DESC',
    ]);
    
    // Get purchased products
    $purchased_products = [];
    if (!empty($orders)) {
        foreach ($orders as $order) {
            foreach ($order->get_items() as $item) {
                $product_id = $item->get_product_id();
                $product = wc_get_product($product_id);
                
                if ($product) {
                    $purchased_products[] = [
                        'name' => $product->get_name(),
                        'id' => $product_id,
                        'purchase_date' => $order->get_date_created()->date('Y-m-d H:i:s'),
                        'categories' => wp_get_post_terms($product_id, 'product_cat', ['fields' => 'names']),
                    ];
                }
            }
        }
    }
    
    // Add WooCommerce data to context
    $context['woocommerce_data'] = [
        'total_spent' => $customer->get_total_spent(),
        'order_count' => $customer->get_order_count(),
        'recent_purchases' => $purchased_products,
        'last_order_date' => !empty($orders) ? $orders[0]->get_date_created()->date('Y-m-d H:i:s') : null,
    ];
    
    return $context;
}
```

### LearnDash LMS Integration

This snippet integrates LearnDash LMS data into the AI Assistant context.

```php
/**
 * Add LearnDash LMS data to AI Assistant context
 * Requires LearnDash plugin to be active
 */
add_filter('memberpress_ai_query_context', 'add_learndash_data_to_ai', 10, 3);

function add_learndash_data_to_ai($context, $query, $user_id) {
    // Check if LearnDash is active
    if (!function_exists('learndash_get_user_courses') || !$user_id) {
        return $context;
    }
    
    // Check if query relates to courses or learning
    $learning_related = stripos($query, 'course') !== false || 
                        stripos($query, 'learn') !== false || 
                        stripos($query, 'lesson') !== false || 
                        stripos($query, 'quiz') !== false || 
                        stripos($query, 'progress') !== false;
    
    if (!$learning_related) {
        return $context;
    }
    
    // Get user's courses
    $user_courses = learndash_get_user_courses($user_id);
    
    $course_data = [];
    if (!empty($user_courses)) {
        foreach ($user_courses as $course_id) {
            // Get course progress
            $progress = learndash_course_progress([
                'user_id' => $user_id,
                'course_id' => $course_id,
                'array' => true,
            ]);
            
            // Get course completion date
            $completed = learndash_course_completed($user_id, $course_id);
            $completion_date = $completed ? get_user_meta($user_id, 'course_completed_' . $course_id, true) : null;
            
            // Add to course data
            $course_data[] = [
                'title' => get_the_title($course_id),
                'id' => $course_id,
                'progress_percentage' => $progress['percentage'],
                'completed' => $completed,
                'completion_date' => $completion_date,
                'last_activity' => learndash_get_user_activity_by_course($user_id, $course_id),
            ];
        }
    }
    
    // Get quiz attempts
    $quiz_attempts = get_user_meta($user_id, '_sfwd-quizzes', true);
    
    // Add LearnDash data to context
    $context['learndash_data'] = [
        'enrolled_courses' => count($user_courses),
        'course_details' => $course_data,
        'quiz_attempts' => $quiz_attempts ? count($quiz_attempts) : 0,
        'certificates' => learndash_get_certificates_count($user_id),
    ];
    
    return $context;
}

/**
 * Helper function to get certificate count
 */
function learndash_get_certificates_count($user_id) {
    global $wpdb;
    
    $count = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->usermeta} 
             WHERE user_id = %d 
             AND meta_key LIKE %s",
            $user_id,
            'learndash_course_certificate_%'
        )
    );
    
    return (int) $count;
}
```

## Permission Management

### Role-Based AI Feature Restrictions

This snippet implements role-based restrictions for AI Assistant features.

```php
/**
 * Implement role-based restrictions for AI Assistant features
 */
add_filter('memberpress_ai_feature_access', 'role_based_ai_feature_access', 10, 3);

function role_based_ai_feature_access($has_access, $feature, $user_id) {
    // Default feature access configuration
    $feature_access = [
        // Feature => allowed roles
        'content_generation' => ['administrator', 'editor', 'mepr-admin'],
        'analytics' => ['administrator', 'editor', 'mepr-admin'],
        'member_management' => ['administrator', 'editor', 'mepr-admin'],
        'system_config' => ['administrator', 'mepr-admin'],
        'payment_data' => ['administrator', 'mepr-admin'],
        'general_help' => ['administrator', 'editor', 'author', 'contributor', 'subscriber', 'mepr-admin', 'mepr-editor'],
    ];
    
    // Allow customization of feature access configuration via filter
    $feature_access = apply_filters('memberpress_ai_feature_access_config', $feature_access);
    
    // If user is not logged in, they have no access
    if (!$user_id) {
        return false;
    }
    
    // If feature doesn't exist in configuration, default to admin-only
    if (!isset($feature_access[$feature])) {
        // Only administrators have access to undefined features
        return user_can($user_id, 'administrator');
    }
    
    // Get user roles
    $user = get_userdata($user_id);
    if (!$user) {
        return false;
    }
    
    $user_roles = $user->roles;
    
    // Check if user has any of the allowed roles
    foreach ($user_roles as $role) {
        if (in_array($role, $feature_access[$feature])) {
            return true;
        }
    }
    
    // No matching roles found
    return false;
}

/**
 * Determine which AI feature is being accessed based on the query
 */
add_filter('memberpress_ai_determine_feature', 'determine_ai_feature_from_query');

function determine_ai_feature_from_query($query) {
    // Set default feature
    $feature = 'general_help';
    
    // Feature detection rules
    $feature_patterns = [
        'content_generation' => [
            '/create content/i',
            '/generate (a |an )?(post|article|page)/i',
            '/write (a |an )?(post|article|page)/i',
            '/content ideas/i',
        ],
        
        'analytics' => [
            '/analytics/i',
            '/report/i',
            '/statistics/i',
            '/metrics/i',
            '/how many/i',
            '/performance/i',
        ],
        
        'member_management' => [
            '/manage member/i',
            '/user account/i',
            '/subscription status/i',
            '/member details/i',
        ],
        
        'system_config' => [
            '/change setting/i',
            '/configure/i',
            '/setup/i',
            '/install/i',
            '/system/i',
        ],
        
        'payment_data' => [
            '/payment/i',
            '/transaction/i',
            '/revenue/i',
            '/income/i',
            '/refund/i',
        ],
    ];
    
    // Check each pattern
    foreach ($feature_patterns as $feature_name => $patterns) {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $query)) {
                $feature = $feature_name;
                break 2;
            }
        }
    }
    
    return $feature;
}

/**
 * Block unauthorized feature access and provide explanation
 */
add_filter('memberpress_ai_process_query', 'block_unauthorized_ai_features', 5, 2);

function block_unauthorized_ai_features($query, $user_id) {
    // Skip for empty queries
    if (empty($query)) {
        return $query;
    }
    
    // Determine which feature this query is trying to access
    $feature = apply_filters('memberpress_ai_determine_feature', $query);
    
    // Check if user has access to this feature
    $has_access = apply_filters('memberpress_ai_feature_access', true, $feature, $user_id);
    
    if (!$has_access) {
        // Return access denied message instead of processing the query
        return "ACCESS_DENIED: I'm sorry, but you don't have permission to access the '$feature' feature. Please contact your administrator if you believe this is an error.";
    }
    
    // User has access, proceed with original query
    return $query;
}
```

## Contributing New Snippets

If you've created a useful code snippet for the MemberPress AI Assistant, please consider contributing it to this repository. To do so, follow these steps:

1. Ensure your snippet follows the format used in this document
2. Include detailed comments explaining what the snippet does
3. Test your snippet thoroughly
4. Submit your snippet via a pull request to our GitHub repository or email it to snippets@memberpress.com

For more information on contributing, please see our [Contribution Guidelines](../contribution-guidelines.md).

---

*This repository is regularly updated with new snippets. Last updated: April 6, 2025.*