# MemberPress AI Assistant: Pattern Library

**Version:** 1.0.0  
**Last Updated:** 2025-04-06  
**Status:** ✅ Stable  
**Owner:** Developer Documentation Team

## Overview

This pattern library provides standardized code patterns for common development tasks in the MemberPress AI Assistant plugin. Following these patterns ensures consistency, maintainability, and best practices across the codebase.

## Table of Contents

1. [Class Structure Patterns](#class-structure-patterns)
2. [Tool Implementation Patterns](#tool-implementation-patterns)
3. [Agent Implementation Patterns](#agent-implementation-patterns)
4. [Data Provider Patterns](#data-provider-patterns)
5. [Template Rendering Patterns](#template-rendering-patterns)
6. [Error Handling Patterns](#error-handling-patterns)
7. [Settings Handling Patterns](#settings-handling-patterns)
8. [AJAX Handling Patterns](#ajax-handling-patterns)
9. [Context Management Patterns](#context-management-patterns)
10. [Cache Implementation Patterns](#cache-implementation-patterns)

## Class Structure Patterns

### Singleton Pattern

Use this pattern for service classes that should only have one instance.

```php
class MPAI_Service_Class {
    /**
     * Instance of this class
     *
     * @var self
     */
    private static $instance = null;
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        // Initialize the class
    }
    
    /**
     * Get instance of this class
     *
     * @return self
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Initialize class hooks
     */
    public function init() {
        // Add hooks and filters
        add_action( 'init', array( $this, 'register_custom_post_types' ) );
        add_filter( 'mpai_get_data', array( $this, 'filter_data' ) );
    }
    
    /**
     * Callback method example
     */
    public function register_custom_post_types() {
        // Method implementation
    }
}

// Usage
add_action( 'plugins_loaded', function() {
    $service = MPAI_Service_Class::get_instance();
    $service->init();
});
```

### Interface Implementation Pattern

Use interfaces to define contracts for implementation classes.

```php
interface MPAI_Provider_Interface {
    /**
     * Check if provider is configured
     *
     * @return bool
     */
    public function is_configured();
    
    /**
     * Generate completion from AI provider
     *
     * @param string $prompt Prompt text.
     * @param array  $args Additional arguments.
     * @return string|WP_Error
     */
    public function generate_completion( $prompt, $args = array() );
}

class MPAI_Specific_Provider implements MPAI_Provider_Interface {
    /**
     * Check if provider is configured
     *
     * @return bool
     */
    public function is_configured() {
        return ! empty( get_option( 'mpai_api_key' ) );
    }
    
    /**
     * Generate completion from AI provider
     *
     * @param string $prompt Prompt text.
     * @param array  $args Additional arguments.
     * @return string|WP_Error
     */
    public function generate_completion( $prompt, $args = array() ) {
        // Implementation
    }
}
```

### Abstract Base Class Pattern

Use abstract base classes to share common functionality between similar classes.

```php
abstract class MPAI_Base_Feature {
    /**
     * Feature name
     *
     * @var string
     */
    protected $name;
    
    /**
     * Get feature name
     *
     * @return string
     */
    public function get_name() {
        return $this->name;
    }
    
    /**
     * Common functionality for all features
     *
     * @return mixed
     */
    public function get_common_data() {
        // Common implementation
    }
    
    /**
     * Method that must be implemented by child classes
     *
     * @return mixed
     */
    abstract public function process();
}

class MPAI_Specific_Feature extends MPAI_Base_Feature {
    /**
     * Constructor
     */
    public function __construct() {
        $this->name = 'specific_feature';
    }
    
    /**
     * Implementation of abstract method
     *
     * @return mixed
     */
    public function process() {
        // Specific implementation
    }
}
```

## Tool Implementation Patterns

### Basic Tool Implementation

Use this pattern for creating tools that the AI can use:

```php
class MPAI_Example_Tool extends MPAI_Base_Tool {
    /**
     * Get tool name
     *
     * @return string
     */
    public function get_name() {
        return 'example_tool';
    }
    
    /**
     * Get tool description
     *
     * @return string
     */
    public function get_description() {
        return 'This tool provides example functionality';
    }
    
    /**
     * Get tool parameters schema
     *
     * @return array
     */
    public function get_parameters_schema() {
        return array(
            'param1' => array(
                'type' => 'string',
                'description' => 'First parameter',
                'required' => true,
            ),
            'param2' => array(
                'type' => 'integer',
                'description' => 'Second parameter',
                'required' => false,
            ),
        );
    }
    
    /**
     * Execute the tool
     *
     * @param array $parameters Tool parameters.
     * @return mixed
     */
    public function execute( $parameters ) {
        // Validate parameters
        if ( empty( $parameters['param1'] ) ) {
            return new WP_Error( 'missing_parameter', 'Param1 is required' );
        }
        
        $param1 = sanitize_text_field( $parameters['param1'] );
        $param2 = isset( $parameters['param2'] ) ? intval( $parameters['param2'] ) : 0;
        
        // Business logic
        $result = $this->process_data( $param1, $param2 );
        
        return array(
            'success' => true,
            'data' => $result,
        );
    }
    
    /**
     * Process data for the tool
     *
     * @param string $param1 First parameter.
     * @param int    $param2 Second parameter.
     * @return mixed
     */
    private function process_data( $param1, $param2 ) {
        // Tool-specific logic
        return array(
            'processed_param1' => $param1,
            'calculated_value' => $param2 * 2,
        );
    }
}
```

### Tool with WordPress Integration

For tools that interact with WordPress functionality:

```php
class MPAI_WordPress_Tool extends MPAI_Base_Tool {
    /**
     * Get tool name
     *
     * @return string
     */
    public function get_name() {
        return 'wordpress_tool';
    }
    
    /**
     * Get tool description
     *
     * @return string
     */
    public function get_description() {
        return 'This tool interacts with WordPress content';
    }
    
    /**
     * Get tool parameters schema
     *
     * @return array
     */
    public function get_parameters_schema() {
        return array(
            'post_type' => array(
                'type' => 'string',
                'description' => 'Post type to query',
                'required' => true,
            ),
            'limit' => array(
                'type' => 'integer',
                'description' => 'Number of posts to retrieve',
                'required' => false,
            ),
        );
    }
    
    /**
     * Execute the tool
     *
     * @param array $parameters Tool parameters.
     * @return mixed
     */
    public function execute( $parameters ) {
        // Validate parameters
        if ( empty( $parameters['post_type'] ) ) {
            return new WP_Error( 'missing_parameter', 'Post type is required' );
        }
        
        $post_type = sanitize_text_field( $parameters['post_type'] );
        $limit = isset( $parameters['limit'] ) ? intval( $parameters['limit'] ) : 10;
        
        // Verify post type exists
        if ( ! post_type_exists( $post_type ) ) {
            return new WP_Error( 'invalid_post_type', 'The specified post type does not exist' );
        }
        
        // Query posts
        $posts = get_posts( array(
            'post_type' => $post_type,
            'numberposts' => $limit,
            'post_status' => 'publish',
        ) );
        
        // Format response
        $formatted_posts = array();
        foreach ( $posts as $post ) {
            $formatted_posts[] = array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'date' => $post->post_date,
                'excerpt' => get_the_excerpt( $post ),
                'url' => get_permalink( $post->ID ),
            );
        }
        
        return array(
            'success' => true,
            'count' => count( $formatted_posts ),
            'posts' => $formatted_posts,
        );
    }
}
```

## Agent Implementation Patterns

### Basic Agent Implementation

Use this pattern for creating specialized AI agents:

```php
class MPAI_Example_Agent extends MPAI_Base_Agent {
    /**
     * Get agent name
     *
     * @return string
     */
    public function get_name() {
        return 'example_agent';
    }
    
    /**
     * Get agent description
     *
     * @return string
     */
    public function get_description() {
        return 'This agent handles example-specific requests';
    }
    
    /**
     * Check if this agent can handle the given request
     *
     * @param string $request User request.
     * @return float Score between 0 and 1 indicating confidence.
     */
    public function can_handle( $request ) {
        $keywords = array( 'example', 'sample', 'test' );
        $score = 0;
        
        foreach ( $keywords as $keyword ) {
            if ( stripos( $request, $keyword ) !== false ) {
                $score += 0.2;
            }
        }
        
        return min( $score, 1.0 );
    }
    
    /**
     * Process the request
     *
     * @param string $request User request.
     * @param array  $context Request context.
     * @return array
     */
    public function process( $request, $context = array() ) {
        // Get agent-specific data
        $data = $this->get_example_data( $request );
        
        // Build prompt enhancement
        $prompt = $this->build_prompt( $request, $data );
        
        // Get AI response
        $response = MPAI_API_Router::get_instance()->generate_completion(
            $prompt,
            array(
                'temperature' => 0.7,
                'max_tokens' => 500,
            )
        );
        
        if ( is_wp_error( $response ) ) {
            return array(
                'response' => 'I encountered an issue processing your request. Please try again.',
                'source' => $this->get_name(),
                'error' => $response,
            );
        }
        
        return array(
            'response' => $response,
            'source' => $this->get_name(),
            'confidence' => $this->can_handle( $request ),
        );
    }
    
    /**
     * Get example data for the agent
     *
     * @param string $request User request.
     * @return array
     */
    private function get_example_data( $request ) {
        // Agent-specific data gathering
        return array(
            'sample_data' => 'This is sample data for the example agent',
            'timestamp' => current_time( 'timestamp' ),
        );
    }
    
    /**
     * Build prompt for the AI
     *
     * @param string $request User request.
     * @param array  $data Agent data.
     * @return string
     */
    private function build_prompt( $request, $data ) {
        $prompt = "You are an example agent specialized in handling example-related queries. ";
        $prompt .= "Please respond to this request: {$request}\n\n";
        $prompt .= "Use this information in your response:\n";
        $prompt .= "- Sample data: {$data['sample_data']}\n";
        
        return $prompt;
    }
}
```

### Domain-Specific Agent Implementation

For agents that handle specialized domains:

```php
class MPAI_Membership_Agent extends MPAI_Base_Agent {
    /**
     * Get agent name
     *
     * @return string
     */
    public function get_name() {
        return 'membership_agent';
    }
    
    /**
     * Get agent description
     *
     * @return string
     */
    public function get_description() {
        return 'This agent handles membership and subscription related queries';
    }
    
    /**
     * Check if this agent can handle the given request
     *
     * @param string $request User request.
     * @return float Score between 0 and 1 indicating confidence.
     */
    public function can_handle( $request ) {
        $keywords = array(
            'membership', 'subscribe', 'subscription', 'plan', 'payment',
            'renew', 'billing', 'upgrade', 'downgrade', 'cancel'
        );
        
        $score = 0;
        
        // Check for primary keywords
        foreach ( $keywords as $keyword ) {
            if ( stripos( $request, $keyword ) !== false ) {
                $score += 0.15;
            }
        }
        
        // Additional context-based scoring
        if ( stripos( $request, 'how do I' ) !== false && $score > 0 ) {
            $score += 0.1;
        }
        
        if ( stripos( $request, 'help with' ) !== false && $score > 0 ) {
            $score += 0.1;
        }
        
        return min( $score, 1.0 );
    }
    
    /**
     * Process the request
     *
     * @param string $request User request.
     * @param array  $context Request context.
     * @return array
     */
    public function process( $request, $context = array() ) {
        // Get membership data for the current user
        $user_id = isset( $context['user_id'] ) ? $context['user_id'] : get_current_user_id();
        $membership_data = $this->get_membership_data( $user_id );
        
        // Get general membership information
        $membership_plans = $this->get_membership_plans();
        
        // Build domain-specific prompt
        $prompt = $this->build_prompt( $request, $membership_data, $membership_plans );
        
        // Get AI response
        $response = MPAI_API_Router::get_instance()->generate_completion(
            $prompt,
            array(
                'temperature' => 0.7,
                'max_tokens' => 800,
            )
        );
        
        if ( is_wp_error( $response ) ) {
            return array(
                'response' => 'I encountered an issue retrieving your membership information. Please try again or contact support.',
                'source' => $this->get_name(),
                'error' => $response,
            );
        }
        
        // Post-process the response
        $processed_response = $this->post_process_response( $response, $membership_data );
        
        return array(
            'response' => $processed_response,
            'source' => $this->get_name(),
            'confidence' => $this->can_handle( $request ),
            'user_data' => array(
                'has_membership' => ! empty( $membership_data['memberships'] ),
                'membership_count' => count( $membership_data['memberships'] ),
            ),
        );
    }
    
    /**
     * Get membership data for a user
     *
     * @param int $user_id User ID.
     * @return array
     */
    private function get_membership_data( $user_id ) {
        // Implement membership data retrieval
        // This would typically use MemberPress API methods
        
        // Example placeholder implementation
        return array(
            'memberships' => array(
                array(
                    'id' => 123,
                    'name' => 'Pro Plan',
                    'status' => 'active',
                    'expiration' => '2025-12-31',
                    'recurring' => true,
                ),
            ),
            'transactions' => array(
                array(
                    'id' => 456,
                    'amount' => 99.00,
                    'date' => '2025-01-15',
                    'status' => 'complete',
                ),
            ),
        );
    }
    
    /**
     * Get available membership plans
     *
     * @return array
     */
    private function get_membership_plans() {
        // Implement membership plans retrieval
        // This would typically use MemberPress API methods
        
        // Example placeholder implementation
        return array(
            array(
                'id' => 1,
                'name' => 'Basic Plan',
                'price' => 49.00,
                'billing_frequency' => 'monthly',
                'features' => array('Feature 1', 'Feature 2'),
            ),
            array(
                'id' => 2,
                'name' => 'Pro Plan',
                'price' => 99.00,
                'billing_frequency' => 'monthly',
                'features' => array('Feature 1', 'Feature 2', 'Feature 3', 'Feature 4'),
            ),
        );
    }
    
    /**
     * Build domain-specific prompt
     *
     * @param string $request User request.
     * @param array  $membership_data User's membership data.
     * @param array  $membership_plans Available membership plans.
     * @return string
     */
    private function build_prompt( $request, $membership_data, $membership_plans ) {
        $prompt = "You are a membership assistant specialized in helping with membership and subscription queries. ";
        $prompt .= "Please respond to this request: {$request}\n\n";
        
        $prompt .= "User Membership Information:\n";
        if ( empty( $membership_data['memberships'] ) ) {
            $prompt .= "- The user does not have any active memberships.\n";
        } else {
            foreach ( $membership_data['memberships'] as $membership ) {
                $prompt .= "- Plan: {$membership['name']}\n";
                $prompt .= "  Status: {$membership['status']}\n";
                $prompt .= "  Expires: {$membership['expiration']}\n";
                $prompt .= "  Recurring: " . ( $membership['recurring'] ? 'Yes' : 'No' ) . "\n";
            }
        }
        
        $prompt .= "\nAvailable Membership Plans:\n";
        foreach ( $membership_plans as $plan ) {
            $prompt .= "- {$plan['name']}: \${$plan['price']} {$plan['billing_frequency']}\n";
            $prompt .= "  Features: " . implode(', ', $plan['features']) . "\n";
        }
        
        $prompt .= "\nProvide a helpful, personalized response that addresses the user's query about their membership or subscriptions.";
        
        return $prompt;
    }
    
    /**
     * Post-process the AI response
     *
     * @param string $response Original response.
     * @param array  $membership_data User's membership data.
     * @return string
     */
    private function post_process_response( $response, $membership_data ) {
        // Example post-processing logic
        // This could add dynamic data, format responses, etc.
        
        // Replace placeholders with actual data
        $response = str_replace( '[MEMBERSHIP_PLAN]', $membership_data['memberships'][0]['name'] ?? 'your plan', $response );
        $response = str_replace( '[EXPIRATION_DATE]', $membership_data['memberships'][0]['expiration'] ?? 'your expiration date', $response );
        
        return $response;
    }
}
```

## Data Provider Patterns

### Base Data Provider

Use this pattern to create standardized data providers:

```php
interface MPAI_Data_Provider_Interface {
    /**
     * Get data type
     *
     * @return string
     */
    public function get_data_type();
    
    /**
     * Get data
     *
     * @param array $args Arguments.
     * @return array
     */
    public function get_data( $args = array() );
    
    /**
     * Register the provider
     */
    public function register();
}

class MPAI_Example_Data_Provider implements MPAI_Data_Provider_Interface {
    /**
     * Get data type
     *
     * @return string
     */
    public function get_data_type() {
        return 'example_data';
    }
    
    /**
     * Get data
     *
     * @param array $args Arguments.
     * @return array
     */
    public function get_data( $args = array() ) {
        // Default arguments
        $defaults = array(
            'limit' => 10,
            'user_id' => get_current_user_id(),
        );
        
        $args = wp_parse_args( $args, $defaults );
        
        // Fetch data
        $data = array(
            'items' => $this->fetch_items( $args['limit'], $args['user_id'] ),
            'meta' => array(
                'total' => $this->get_total_count(),
                'timestamp' => current_time( 'timestamp' ),
            ),
        );
        
        // Allow filtering
        return apply_filters( 'mpai_example_data', $data, $args );
    }
    
    /**
     * Register the provider
     */
    public function register() {
        add_filter( 'mpai_data_providers', array( $this, 'register_provider' ) );
    }
    
    /**
     * Register provider callback
     *
     * @param array $providers Existing providers.
     * @return array
     */
    public function register_provider( $providers ) {
        $providers[] = $this;
        return $providers;
    }
    
    /**
     * Fetch items
     *
     * @param int $limit Number of items to fetch.
     * @param int $user_id User ID.
     * @return array
     */
    private function fetch_items( $limit, $user_id ) {
        // Implementation to fetch items
        return array(
            array(
                'id' => 1,
                'name' => 'Item 1',
                'value' => 42,
            ),
            array(
                'id' => 2,
                'name' => 'Item 2',
                'value' => 84,
            ),
        );
    }
    
    /**
     * Get total count
     *
     * @return int
     */
    private function get_total_count() {
        // Implementation to get total count
        return 42;
    }
}

// Usage
add_action( 'init', function() {
    $provider = new MPAI_Example_Data_Provider();
    $provider->register();
});
```

### Cached Data Provider

For data providers with caching support:

```php
class MPAI_Cached_Data_Provider implements MPAI_Data_Provider_Interface {
    /**
     * Get data type
     *
     * @return string
     */
    public function get_data_type() {
        return 'cached_data';
    }
    
    /**
     * Get data
     *
     * @param array $args Arguments.
     * @return array
     */
    public function get_data( $args = array() ) {
        // Default arguments
        $defaults = array(
            'limit' => 10,
            'user_id' => get_current_user_id(),
            'skip_cache' => false,
        );
        
        $args = wp_parse_args( $args, $defaults );
        
        // Generate cache key
        $cache_key = 'mpai_cached_data_' . md5( serialize( $args ) );
        
        // Check cache first
        if ( ! $args['skip_cache'] ) {
            $cached_data = get_transient( $cache_key );
            if ( false !== $cached_data ) {
                return $cached_data;
            }
        }
        
        // Fetch fresh data
        $data = array(
            'items' => $this->fetch_items( $args['limit'], $args['user_id'] ),
            'meta' => array(
                'total' => $this->get_total_count(),
                'timestamp' => current_time( 'timestamp' ),
                'cached' => false,
            ),
        );
        
        // Allow filtering
        $data = apply_filters( 'mpai_cached_data', $data, $args );
        
        // Store in cache
        set_transient( $cache_key, $data, HOUR_IN_SECONDS );
        
        return $data;
    }
    
    /**
     * Register the provider
     */
    public function register() {
        add_filter( 'mpai_data_providers', array( $this, 'register_provider' ) );
        
        // Register cache invalidation hooks
        add_action( 'save_post', array( $this, 'invalidate_cache' ) );
        add_action( 'deleted_post', array( $this, 'invalidate_cache' ) );
    }
    
    /**
     * Register provider callback
     *
     * @param array $providers Existing providers.
     * @return array
     */
    public function register_provider( $providers ) {
        $providers[] = $this;
        return $providers;
    }
    
    /**
     * Invalidate cache
     */
    public function invalidate_cache() {
        global $wpdb;
        
        // Delete all transients for this provider
        $wpdb->query( 
            $wpdb->prepare( 
                "DELETE FROM $wpdb->options WHERE option_name LIKE %s",
                '_transient_mpai_cached_data_%'
            )
        );
        
        // Also delete timeout transients
        $wpdb->query( 
            $wpdb->prepare( 
                "DELETE FROM $wpdb->options WHERE option_name LIKE %s",
                '_transient_timeout_mpai_cached_data_%'
            )
        );
    }
    
    /**
     * Fetch items
     *
     * @param int $limit Number of items to fetch.
     * @param int $user_id User ID.
     * @return array
     */
    private function fetch_items( $limit, $user_id ) {
        // Implementation to fetch items
        return array(
            array(
                'id' => 1,
                'name' => 'Cached Item 1',
                'value' => 42,
            ),
            array(
                'id' => 2,
                'name' => 'Cached Item 2',
                'value' => 84,
            ),
        );
    }
    
    /**
     * Get total count
     *
     * @return int
     */
    private function get_total_count() {
        // Implementation to get total count
        return 42;
    }
}
```

## Template Rendering Patterns

### Basic Template Rendering

Use this pattern for rendering templates with data:

```php
class MPAI_Template_Renderer {
    /**
     * Render a template
     *
     * @param string $template_name Template name.
     * @param array  $args Template arguments.
     * @return string
     */
    public function render( $template_name, $args = array() ) {
        $template_path = $this->get_template_path( $template_name );
        
        if ( ! file_exists( $template_path ) ) {
            return '';
        }
        
        // Extract variables for the template
        if ( ! empty( $args ) && is_array( $args ) ) {
            extract( $args );
        }
        
        ob_start();
        include $template_path;
        return ob_get_clean();
    }
    
    /**
     * Get template path
     *
     * @param string $template_name Template name.
     * @return string
     */
    private function get_template_path( $template_name ) {
        $template = $template_name;
        
        // Add .php extension if not present
        if ( ! preg_match( '/\.php$/', $template ) ) {
            $template .= '.php';
        }
        
        // Check theme override
        $theme_template = locate_template( 'memberpress-ai-assistant/' . $template );
        
        if ( $theme_template ) {
            return $theme_template;
        }
        
        // Use plugin template
        return MPAI_PLUGIN_DIR . 'includes/templates/' . $template;
    }
}

// Usage
$renderer = new MPAI_Template_Renderer();
$html = $renderer->render( 'chat-interface', array(
    'title' => 'AI Assistant',
    'placeholder' => 'Ask a question...',
    'button_text' => 'Send',
) );
```

### Component Rendering Pattern

For reusable UI components:

```php
class MPAI_Component_Renderer {
    /**
     * Render a button component
     *
     * @param array $args Button arguments.
     * @return string
     */
    public function render_button( $args = array() ) {
        $defaults = array(
            'id' => 'mpai-button-' . uniqid(),
            'class' => 'mpai-button',
            'text' => 'Button',
            'type' => 'button',
            'disabled' => false,
            'data' => array(),
        );
        
        $args = wp_parse_args( $args, $defaults );
        
        // Build data attributes
        $data_attrs = '';
        foreach ( $args['data'] as $key => $value ) {
            $data_attrs .= ' data-' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
        }
        
        $disabled = $args['disabled'] ? ' disabled' : '';
        
        $html = sprintf( 
            '<button id="%s" class="%s" type="%s"%s%s>%s</button>',
            esc_attr( $args['id'] ),
            esc_attr( $args['class'] ),
            esc_attr( $args['type'] ),
            $disabled,
            $data_attrs,
            esc_html( $args['text'] )
        );
        
        return $html;
    }
    
    /**
     * Render a card component
     *
     * @param array $args Card arguments.
     * @return string
     */
    public function render_card( $args = array() ) {
        $defaults = array(
            'id' => 'mpai-card-' . uniqid(),
            'class' => 'mpai-card',
            'title' => '',
            'content' => '',
            'footer' => '',
        );
        
        $args = wp_parse_args( $args, $defaults );
        
        ob_start();
        ?>
        <div id="<?php echo esc_attr( $args['id'] ); ?>" class="<?php echo esc_attr( $args['class'] ); ?>">
            <?php if ( ! empty( $args['title'] ) ) : ?>
                <div class="mpai-card-header">
                    <h3 class="mpai-card-title"><?php echo esc_html( $args['title'] ); ?></h3>
                </div>
            <?php endif; ?>
            
            <div class="mpai-card-body">
                <?php echo wp_kses_post( $args['content'] ); ?>
            </div>
            
            <?php if ( ! empty( $args['footer'] ) ) : ?>
                <div class="mpai-card-footer">
                    <?php echo wp_kses_post( $args['footer'] ); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Usage
$renderer = new MPAI_Component_Renderer();

// Render a button
$button = $renderer->render_button( array(
    'text' => 'Submit',
    'class' => 'mpai-button mpai-primary-button',
    'data' => array(
        'action' => 'submit',
        'target' => 'form-1',
    ),
) );

// Render a card with the button in the footer
$card = $renderer->render_card( array(
    'title' => 'Example Card',
    'content' => '<p>This is the card content.</p>',
    'footer' => $button,
) );
```

## Error Handling Patterns

### Structured Error Handling

Use this pattern for consistent error handling:

```php
class MPAI_Error_Handler {
    /**
     * Handle an error
     *
     * @param string $code Error code.
     * @param string $message Error message.
     * @param array  $data Additional error data.
     * @return WP_Error
     */
    public function handle_error( $code, $message, $data = array() ) {
        // Log the error
        $this->log_error( $code, $message, $data );
        
        // Create WP_Error object
        return new WP_Error( $code, $message, $data );
    }
    
    /**
     * Log an error
     *
     * @param string $code Error code.
     * @param string $message Error message.
     * @param array  $data Additional error data.
     */
    private function log_error( $code, $message, $data = array() ) {
        $error_data = array(
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'timestamp' => current_time( 'timestamp' ),
            'user_id' => get_current_user_id(),
        );
        
        // Log to error log
        error_log( sprintf( 
            'MPAI Error [%s]: %s | %s',
            $code,
            $message,
            json_encode( $data )
        ) );
        
        // Store in database for error reporting
        $this->store_error( $error_data );
        
        // Trigger action for additional error handling
        do_action( 'mpai_error', $code, $message, $data );
    }
    
    /**
     * Store error in database
     *
     * @param array $error_data Error data.
     */
    private function store_error( $error_data ) {
        // Get existing errors
        $errors = get_option( 'mpai_error_log', array() );
        
        // Add new error
        $errors[] = $error_data;
        
        // Limit to most recent 100 errors
        if ( count( $errors ) > 100 ) {
            $errors = array_slice( $errors, -100 );
        }
        
        // Update option
        update_option( 'mpai_error_log', $errors );
    }
    
    /**
     * Get user-friendly error message
     *
     * @param WP_Error $error Error object.
     * @return string
     */
    public function get_user_message( $error ) {
        $code = $error->get_error_code();
        
        // Define user-friendly messages for known error codes
        $messages = array(
            'api_connection_failed' => 'We couldn\'t connect to the AI service. Please try again later.',
            'api_rate_limit' => 'The AI service is currently busy. Please try again in a few minutes.',
            'invalid_input' => 'The input provided was invalid. Please check your request and try again.',
            'permission_denied' => 'You don\'t have permission to perform this action.',
            'unknown_error' => 'An unexpected error occurred. Please try again or contact support.',
        );
        
        // Return known message or generic message
        return isset( $messages[ $code ] ) ? $messages[ $code ] : 'An error occurred. Please try again later.';
    }
}

// Usage
$error_handler = new MPAI_Error_Handler();

try {
    // Some operation that might fail
    $result = $this->perform_operation();
    
    if ( ! $result ) {
        // Handle failure
        return $error_handler->handle_error(
            'operation_failed',
            'The operation failed to complete',
            array( 'operation' => 'example_operation' )
        );
    }
    
    return $result;
} catch ( Exception $e ) {
    // Handle exception
    return $error_handler->handle_error(
        'exception',
        $e->getMessage(),
        array( 'exception' => get_class( $e ), 'trace' => $e->getTraceAsString() )
    );
}
```

### Retry Pattern

For operations that should be retried on failure:

```php
class MPAI_Retry_Handler {
    /**
     * Execute operation with retry
     *
     * @param callable $operation Operation to execute.
     * @param array    $options Retry options.
     * @return mixed
     */
    public function execute_with_retry( $operation, $options = array() ) {
        $defaults = array(
            'max_retries' => 3,
            'base_delay' => 1000, // milliseconds
            'factor' => 2, // exponential factor
            'jitter' => 0.25, // random jitter factor
            'retryable_errors' => array( 'timeout', 'server_error', 'rate_limit' ),
        );
        
        $options = wp_parse_args( $options, $defaults );
        
        $attempt = 0;
        
        while ( $attempt <= $options['max_retries'] ) {
            try {
                // Attempt the operation
                return call_user_func( $operation );
            } catch ( Exception $e ) {
                $attempt++;
                
                // Get error code
                $error_code = $this->get_error_code( $e );
                
                // Check if error is retryable
                if ( ! in_array( $error_code, $options['retryable_errors'] ) ) {
                    // Non-retryable error, rethrow
                    throw $e;
                }
                
                if ( $attempt > $options['max_retries'] ) {
                    // Max retries exceeded, rethrow
                    throw $e;
                }
                
                // Calculate delay with exponential backoff and jitter
                $delay = $options['base_delay'] * pow( $options['factor'], $attempt - 1 );
                $jitter = $delay * $options['jitter'] * ( lcg_value() - 0.5 ) * 2;
                $delay = $delay + $jitter;
                
                // Log retry attempt
                error_log( sprintf( 
                    'MPAI Retry: Attempt %d/%d for operation (Error: %s). Retrying in %d ms',
                    $attempt,
                    $options['max_retries'],
                    $error_code,
                    $delay
                ) );
                
                // Sleep before retry
                usleep( $delay * 1000 );
            }
        }
        
        // This should not be reached, but just in case
        throw new Exception( 'Max retries exceeded and no exception was thrown.' );
    }
    
    /**
     * Get error code from exception
     *
     * @param Exception $e Exception.
     * @return string
     */
    private function get_error_code( $e ) {
        if ( method_exists( $e, 'get_error_code' ) ) {
            return $e->get_error_code();
        }
        
        if ( $e instanceof WP_Error ) {
            return $e->get_error_code();
        }
        
        // Parse message for known error patterns
        $message = $e->getMessage();
        
        if ( strpos( $message, 'timeout' ) !== false ) {
            return 'timeout';
        }
        
        if ( strpos( $message, 'rate limit' ) !== false ) {
            return 'rate_limit';
        }
        
        if ( strpos( $message, '5xx' ) !== false || strpos( $message, 'server error' ) !== false ) {
            return 'server_error';
        }
        
        return 'unknown';
    }
}

// Usage
$retry_handler = new MPAI_Retry_Handler();

try {
    $result = $retry_handler->execute_with_retry(
        function() {
            // Operation that might fail
            $response = wp_remote_get( 'https://api.example.com/data' );
            
            if ( is_wp_error( $response ) ) {
                throw new Exception( 'API request failed: ' . $response->get_error_message() );
            }
            
            $body = wp_remote_retrieve_body( $response );
            $data = json_decode( $body, true );
            
            if ( json_last_error() !== JSON_ERROR_NONE ) {
                throw new Exception( 'Invalid JSON response' );
            }
            
            return $data;
        },
        array(
            'max_retries' => 5,
            'base_delay' => 500,
        )
    );
    
    return $result;
} catch ( Exception $e ) {
    // Handle final failure
    error_log( 'MPAI: Operation failed after maximum retries: ' . $e->getMessage() );
    return new WP_Error( 'operation_failed', 'The operation failed after multiple attempts.' );
}
```

## Settings Handling Patterns

### Settings Registration Pattern

Use this pattern for plugin settings:

```php
class MPAI_Settings_Manager {
    /**
     * Initialize settings
     */
    public function init() {
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        // Register setting group
        register_setting(
            'mpai_settings',
            'mpai_api_key',
            array(
                'type' => 'string',
                'sanitize_callback' => array( $this, 'sanitize_api_key' ),
                'default' => '',
            )
        );
        
        register_setting(
            'mpai_settings',
            'mpai_model',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'gpt-4',
            )
        );
        
        register_setting(
            'mpai_settings',
            'mpai_temperature',
            array(
                'type' => 'number',
                'sanitize_callback' => array( $this, 'sanitize_temperature' ),
                'default' => 0.7,
            )
        );
        
        register_setting(
            'mpai_settings',
            'mpai_max_tokens',
            array(
                'type' => 'integer',
                'sanitize_callback' => 'absint',
                'default' => 1000,
            )
        );
        
        register_setting(
            'mpai_settings',
            'mpai_enable_logging',
            array(
                'type' => 'boolean',
                'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
                'default' => false,
            )
        );
        
        // Add settings section
        add_settings_section(
            'mpai_api_settings',
            'API Settings',
            array( $this, 'render_api_settings_description' ),
            'mpai_settings_page'
        );
        
        // Add settings fields
        add_settings_field(
            'mpai_api_key',
            'API Key',
            array( $this, 'render_api_key_field' ),
            'mpai_settings_page',
            'mpai_api_settings'
        );
        
        add_settings_field(
            'mpai_model',
            'AI Model',
            array( $this, 'render_model_field' ),
            'mpai_settings_page',
            'mpai_api_settings'
        );
        
        add_settings_field(
            'mpai_temperature',
            'Temperature',
            array( $this, 'render_temperature_field' ),
            'mpai_settings_page',
            'mpai_api_settings'
        );
        
        add_settings_field(
            'mpai_max_tokens',
            'Max Tokens',
            array( $this, 'render_max_tokens_field' ),
            'mpai_settings_page',
            'mpai_api_settings'
        );
        
        add_settings_field(
            'mpai_enable_logging',
            'Enable Logging',
            array( $this, 'render_enable_logging_field' ),
            'mpai_settings_page',
            'mpai_api_settings'
        );
    }
    
    /**
     * Render API settings description
     */
    public function render_api_settings_description() {
        echo '<p>Configure the AI API settings for MemberPress AI Assistant.</p>';
    }
    
    /**
     * Render API key field
     */
    public function render_api_key_field() {
        $api_key = get_option( 'mpai_api_key', '' );
        $placeholder = empty( $api_key ) ? '' : '••••••••••••••••••••••••••';
        ?>
        <input type="password" id="mpai_api_key" name="mpai_api_key" value="<?php echo esc_attr( $api_key ); ?>" 
               class="regular-text" placeholder="<?php echo esc_attr( $placeholder ); ?>" autocomplete="off" />
        <p class="description">Enter your API key for the AI service.</p>
        <?php
    }
    
    /**
     * Render model field
     */
    public function render_model_field() {
        $model = get_option( 'mpai_model', 'gpt-4' );
        $models = array(
            'gpt-4' => 'GPT-4',
            'gpt-4o' => 'GPT-4o',
            'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
            'claude-3-opus' => 'Claude 3 Opus',
            'claude-3-sonnet' => 'Claude 3 Sonnet',
        );
        ?>
        <select id="mpai_model" name="mpai_model">
            <?php foreach ( $models as $key => $label ) : ?>
                <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $model, $key ); ?>>
                    <?php echo esc_html( $label ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">Select the AI model to use for generation.</p>
        <?php
    }
    
    /**
     * Render temperature field
     */
    public function render_temperature_field() {
        $temperature = get_option( 'mpai_temperature', 0.7 );
        ?>
        <input type="range" id="mpai_temperature" name="mpai_temperature" 
               value="<?php echo esc_attr( $temperature ); ?>" min="0" max="1" step="0.1" />
        <span id="mpai_temperature_value"><?php echo esc_html( $temperature ); ?></span>
        <p class="description">Controls the randomness of the responses (0 = deterministic, 1 = maximum creativity).</p>
        <script>
            document.getElementById('mpai_temperature').addEventListener('input', function(e) {
                document.getElementById('mpai_temperature_value').textContent = e.target.value;
            });
        </script>
        <?php
    }
    
    /**
     * Render max tokens field
     */
    public function render_max_tokens_field() {
        $max_tokens = get_option( 'mpai_max_tokens', 1000 );
        ?>
        <input type="number" id="mpai_max_tokens" name="mpai_max_tokens" 
               value="<?php echo esc_attr( $max_tokens ); ?>" min="1" max="4000" step="1" />
        <p class="description">Maximum number of tokens in the generated response.</p>
        <?php
    }
    
    /**
     * Render enable logging field
     */
    public function render_enable_logging_field() {
        $enable_logging = get_option( 'mpai_enable_logging', false );
        ?>
        <label for="mpai_enable_logging">
            <input type="checkbox" id="mpai_enable_logging" name="mpai_enable_logging" 
                   value="1" <?php checked( $enable_logging, true ); ?> />
            Enable detailed logging for debugging
        </label>
        <p class="description">Warning: This may include sensitive information in the logs.</p>
        <?php
    }
    
    /**
     * Sanitize API key
     *
     * @param string $value Value to sanitize.
     * @return string
     */
    public function sanitize_api_key( $value ) {
        // If the value is empty or just contains asterisks, keep the existing value
        if ( empty( $value ) || preg_match( '/^\*+$/', $value ) ) {
            return get_option( 'mpai_api_key', '' );
        }
        
        return sanitize_text_field( $value );
    }
    
    /**
     * Sanitize temperature
     *
     * @param float $value Value to sanitize.
     * @return float
     */
    public function sanitize_temperature( $value ) {
        $value = (float) $value;
        return min( max( $value, 0 ), 1 );
    }
    
    /**
     * Sanitize checkbox
     *
     * @param mixed $value Value to sanitize.
     * @return bool
     */
    public function sanitize_checkbox( $value ) {
        return ! empty( $value );
    }
    
    /**
     * Get setting value
     *
     * @param string $key Setting key.
     * @param mixed  $default Default value.
     * @return mixed
     */
    public function get_setting( $key, $default = null ) {
        return get_option( 'mpai_' . $key, $default );
    }
}

// Usage
$settings_manager = new MPAI_Settings_Manager();
$settings_manager->init();

// Get a setting
$api_key = $settings_manager->get_setting( 'api_key', '' );
$model = $settings_manager->get_setting( 'model', 'gpt-4' );
```

## AJAX Handling Patterns

### Standard AJAX Handler

Use this pattern for AJAX requests:

```php
class MPAI_AJAX_Handler {
    /**
     * Initialize AJAX handlers
     */
    public function init() {
        add_action( 'wp_ajax_mpai_process_request', array( $this, 'handle_process_request' ) );
        add_action( 'wp_ajax_mpai_get_data', array( $this, 'handle_get_data' ) );
        add_action( 'wp_ajax_mpai_save_settings', array( $this, 'handle_save_settings' ) );
        
        // Non-authenticated actions
        add_action( 'wp_ajax_nopriv_mpai_public_data', array( $this, 'handle_public_data' ) );
    }
    
    /**
     * Handle process request
     */
    public function handle_process_request() {
        // Check nonce
        $this->verify_nonce( 'mpai_process_nonce', 'mpai_process_request' );
        
        // Check capabilities
        if ( ! current_user_can( 'edit_posts' ) ) {
            $this->send_error( 'permission_denied', 'You do not have permission to perform this action.', 403 );
        }
        
        // Get parameters
        $prompt = isset( $_POST['prompt'] ) ? sanitize_textarea_field( wp_unslash( $_POST['prompt'] ) ) : '';
        $context = isset( $_POST['context'] ) ? sanitize_text_field( wp_unslash( $_POST['context'] ) ) : '';
        
        if ( empty( $prompt ) ) {
            $this->send_error( 'missing_prompt', 'Prompt is required.' );
        }
        
        // Process the request
        try {
            $result = $this->process_ai_request( $prompt, $context );
            
            if ( is_wp_error( $result ) ) {
                $this->send_error( $result->get_error_code(), $result->get_error_message() );
            }
            
            $this->send_success( array(
                'response' => $result,
                'context' => $context,
            ) );
        } catch ( Exception $e ) {
            $this->send_error( 'process_failed', $e->getMessage() );
        }
    }
    
    /**
     * Handle get data
     */
    public function handle_get_data() {
        // Check nonce
        $this->verify_nonce( 'mpai_data_nonce', 'mpai_get_data' );
        
        // Get parameters
        $data_type = isset( $_GET['data_type'] ) ? sanitize_text_field( wp_unslash( $_GET['data_type'] ) ) : '';
        
        if ( empty( $data_type ) ) {
            $this->send_error( 'missing_data_type', 'Data type is required.' );
        }
        
        // Get the data
        $data = $this->get_data( $data_type );
        
        if ( is_wp_error( $data ) ) {
            $this->send_error( $data->get_error_code(), $data->get_error_message() );
        }
        
        $this->send_success( array(
            'data' => $data,
            'timestamp' => current_time( 'timestamp' ),
        ) );
    }
    
    /**
     * Handle save settings
     */
    public function handle_save_settings() {
        // Check nonce
        $this->verify_nonce( 'mpai_settings_nonce', 'mpai_save_settings' );
        
        // Check capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            $this->send_error( 'permission_denied', 'You do not have permission to perform this action.', 403 );
        }
        
        // Get and validate settings
        $settings = isset( $_POST['settings'] ) ? $this->validate_settings( $_POST['settings'] ) : array();
        
        if ( empty( $settings ) ) {
            $this->send_error( 'invalid_settings', 'Invalid settings data.' );
        }
        
        // Save settings
        foreach ( $settings as $key => $value ) {
            update_option( 'mpai_' . $key, $value );
        }
        
        $this->send_success( array(
            'message' => 'Settings saved successfully.',
        ) );
    }
    
    /**
     * Handle public data
     */
    public function handle_public_data() {
        // Check nonce for public requests
        $this->verify_nonce( 'mpai_public_nonce', 'mpai_public_data' );
        
        // Get public data
        $data = $this->get_public_data();
        
        $this->send_success( array(
            'data' => $data,
        ) );
    }
    
    /**
     * Verify nonce
     *
     * @param string $nonce_name Nonce name.
     * @param string $action Action name.
     */
    private function verify_nonce( $nonce_name, $action ) {
        $nonce = isset( $_REQUEST[ $nonce_name ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ $nonce_name ] ) ) : '';
        
        if ( ! wp_verify_nonce( $nonce, $action ) ) {
            $this->send_error( 'invalid_nonce', 'Security check failed.', 403 );
        }
    }
    
    /**
     * Send error response
     *
     * @param string $code Error code.
     * @param string $message Error message.
     * @param int    $status HTTP status code.
     */
    private function send_error( $code, $message, $status = 400 ) {
        wp_send_json_error( array(
            'code' => $code,
            'message' => $message,
        ), $status );
    }
    
    /**
     * Send success response
     *
     * @param array $data Response data.
     */
    private function send_success( $data ) {
        wp_send_json_success( $data );
    }
    
    /**
     * Process AI request
     *
     * @param string $prompt Prompt text.
     * @param string $context Context identifier.
     * @return string|WP_Error
     */
    private function process_ai_request( $prompt, $context ) {
        // Implementation
        return 'AI response to prompt: ' . $prompt;
    }
    
    /**
     * Get data by type
     *
     * @param string $data_type Data type.
     * @return array|WP_Error
     */
    private function get_data( $data_type ) {
        // Implementation
        return array(
            'items' => array(
                array( 'id' => 1, 'name' => 'Item 1' ),
                array( 'id' => 2, 'name' => 'Item 2' ),
            ),
        );
    }
    
    /**
     * Get public data
     *
     * @return array
     */
    private function get_public_data() {
        // Implementation
        return array(
            'version' => '1.0.0',
            'status' => 'active',
        );
    }
    
    /**
     * Validate settings
     *
     * @param array $settings Settings to validate.
     * @return array
     */
    private function validate_settings( $settings ) {
        if ( ! is_array( $settings ) ) {
            return array();
        }
        
        $validated = array();
        
        // Validate each setting
        foreach ( $settings as $key => $value ) {
            // Only allow known settings
            if ( ! in_array( $key, array( 'api_key', 'model', 'temperature', 'max_tokens', 'enable_logging' ) ) ) {
                continue;
            }
            
            // Validate and sanitize based on type
            switch ( $key ) {
                case 'api_key':
                    $validated[ $key ] = sanitize_text_field( $value );
                    break;
                
                case 'model':
                    $valid_models = array( 'gpt-4', 'gpt-4o', 'gpt-3.5-turbo', 'claude-3-opus', 'claude-3-sonnet' );
                    $validated[ $key ] = in_array( $value, $valid_models ) ? $value : 'gpt-4';
                    break;
                
                case 'temperature':
                    $validated[ $key ] = min( max( (float) $value, 0 ), 1 );
                    break;
                
                case 'max_tokens':
                    $validated[ $key ] = absint( $value );
                    break;
                
                case 'enable_logging':
                    $validated[ $key ] = ! empty( $value );
                    break;
            }
        }
        
        return $validated;
    }
}

// Usage
$ajax_handler = new MPAI_AJAX_Handler();
$ajax_handler->init();
```

## Context Management Patterns

### Context Manager Pattern

Use this pattern for managing AI context:

```php
class MPAI_Context_Manager {
    /**
     * Get chat context
     *
     * @param int    $user_id User ID.
     * @param string $request_type Request type.
     * @return array
     */
    public function get_chat_context( $user_id = 0, $request_type = '' ) {
        if ( empty( $user_id ) ) {
            $user_id = get_current_user_id();
        }
        
        // Base context
        $context = array(
            'system_info' => $this->get_system_info(),
            'user_info' => $this->get_user_info( $user_id ),
        );
        
        // Add MemberPress info if available
        if ( function_exists( 'memberpress' ) ) {
            $context['memberpress_info'] = $this->get_memberpress_info( $user_id );
        }
        
        // Add request type specific context
        switch ( $request_type ) {
            case 'content_generation':
                $context['content_templates'] = $this->get_content_templates();
                break;
                
            case 'analytics':
                $context['analytics_data'] = $this->get_analytics_data();
                break;
                
            case 'support':
                $context['support_info'] = $this->get_support_info( $user_id );
                break;
        }
        
        // Get tool context
        $context['available_tools'] = $this->get_available_tools();
        
        // Apply filters to allow customization
        $context = apply_filters( 'mpai_chat_context', $context, $user_id, $request_type );
        
        return $context;
    }
    
    /**
     * Get system information
     *
     * @return array
     */
    private function get_system_info() {
        return array(
            'plugin_version' => MPAI_VERSION,
            'wordpress_version' => get_bloginfo( 'version' ),
            'php_version' => phpversion(),
            'site_url' => get_site_url(),
            'timezone' => wp_timezone_string(),
            'current_time' => current_time( 'mysql' ),
        );
    }
    
    /**
     * Get user information
     *
     * @param int $user_id User ID.
     * @return array
     */
    private function get_user_info( $user_id ) {
        $user = get_userdata( $user_id );
        
        if ( ! $user ) {
            return array();
        }
        
        return array(
            'id' => $user->ID,
            'login' => $user->user_login,
            'email' => $user->user_email,
            'display_name' => $user->display_name,
            'registered' => $user->user_registered,
            'roles' => $user->roles,
        );
    }
    
    /**
     * Get MemberPress information
     *
     * @param int $user_id User ID.
     * @return array
     */
    private function get_memberpress_info( $user_id ) {
        // Example for MemberPress integration
        return array(
            'is_member' => true,
            'memberships' => array(
                array(
                    'id' => 123,
                    'name' => 'Pro Plan',
                    'status' => 'active',
                    'expiration' => '2025-12-31',
                ),
            ),
            'total_spent' => 299.00,
            'member_since' => '2024-01-15',
        );
    }
    
    /**
     * Get content templates
     *
     * @return array
     */
    private function get_content_templates() {
        return array(
            array(
                'id' => 'blog_post',
                'name' => 'Blog Post',
                'prompt_template' => 'Create a comprehensive blog post about {topic} with the following sections: Introduction, {sections}, and Conclusion.',
            ),
            array(
                'id' => 'newsletter',
                'name' => 'Newsletter',
                'prompt_template' => 'Write a newsletter about {topic} for {audience}. Include a catchy subject line, introduction, main content, and call-to-action.',
            ),
        );
    }
    
    /**
     * Get analytics data
     *
     * @return array
     */
    private function get_analytics_data() {
        return array(
            'total_members' => 1250,
            'active_members' => 950,
            'subscriptions' => array(
                'basic' => 450,
                'pro' => 350,
                'premium' => 150,
            ),
            'monthly_revenue' => 15000.00,
            'conversion_rate' => 3.2,
        );
    }
    
    /**
     * Get support information
     *
     * @param int $user_id User ID.
     * @return array
     */
    private function get_support_info( $user_id ) {
        return array(
            'recent_tickets' => array(
                array(
                    'id' => 1001,
                    'title' => 'Payment failed',
                    'status' => 'resolved',
                    'date' => '2025-03-15',
                ),
                array(
                    'id' => 1002,
                    'title' => 'Cannot access course',
                    'status' => 'open',
                    'date' => '2025-04-02',
                ),
            ),
            'common_issues' => array(
                'payment_issues' => 'Contact support with your transaction ID',
                'access_issues' => 'Try logging out and back in, then clear browser cache',
                'download_issues' => 'Ensure your membership is active and you have the correct permissions',
            ),
        );
    }
    
    /**
     * Get available tools
     *
     * @return array
     */
    private function get_available_tools() {
        // Get tools from registry
        $tools = MPAI_Tool_Registry::get_instance()->get_tools();
        
        $tool_data = array();
        foreach ( $tools as $tool ) {
            $tool_data[] = array(
                'name' => $tool->get_name(),
                'description' => $tool->get_description(),
                'parameters' => $tool->get_parameters_schema(),
            );
        }
        
        return $tool_data;
    }
}

// Usage
$context_manager = MPAI_Context_Manager::get_instance();
$context = $context_manager->get_chat_context( get_current_user_id(), 'support' );

// Apply custom context modifications
add_filter( 'mpai_chat_context', function( $context, $user_id, $request_type ) {
    // Add custom data
    $context['custom_data'] = array(
        'example' => 'Custom context value',
    );
    
    return $context;
}, 10, 3 );
```

## Cache Implementation Patterns

### Cache Manager Pattern

Use this pattern for implementing caching:

```php
class MPAI_Cache_Manager {
    /**
     * Cache group
     *
     * @var string
     */
    private $group = 'mpai_cache';
    
    /**
     * Default cache expiration in seconds
     *
     * @var int
     */
    private $default_expiration = 3600; // 1 hour
    
    /**
     * Get cached data
     *
     * @param string $key Cache key.
     * @param mixed  $default Default value if cache miss.
     * @return mixed
     */
    public function get( $key ) {
        $key = $this->sanitize_key( $key );
        
        // Check object cache first
        $data = wp_cache_get( $key, $this->group );
        
        if ( false !== $data ) {
            return $data;
        }
        
        // Check transient fallback
        $transient_key = 'mpai_' . $key;
        $data = get_transient( $transient_key );
        
        if ( false !== $data ) {
            // Store in object cache for future gets
            wp_cache_set( $key, $data, $this->group );
            return $data;
        }
        
        return false;
    }
    
    /**
     * Set cached data
     *
     * @param string $key Cache key.
     * @param mixed  $data Data to cache.
     * @param int    $expiration Expiration in seconds.
     * @return bool
     */
    public function set( $key, $data, $expiration = null ) {
        $key = $this->sanitize_key( $key );
        
        if ( null === $expiration ) {
            $expiration = $this->default_expiration;
        }
        
        // Store in object cache
        wp_cache_set( $key, $data, $this->group, $expiration );
        
        // Store in transient as fallback
        $transient_key = 'mpai_' . $key;
        return set_transient( $transient_key, $data, $expiration );
    }
    
    /**
     * Delete cached data
     *
     * @param string $key Cache key.
     * @return bool
     */
    public function delete( $key ) {
        $key = $this->sanitize_key( $key );
        
        // Delete from object cache
        wp_cache_delete( $key, $this->group );
        
        // Delete from transient
        $transient_key = 'mpai_' . $key;
        return delete_transient( $transient_key );
    }
    
    /**
     * Get cached data with callback on miss
     *
     * @param string   $key Cache key.
     * @param callable $callback Callback to generate data on cache miss.
     * @param int      $expiration Expiration in seconds.
     * @return mixed
     */
    public function remember( $key, $callback, $expiration = null ) {
        $data = $this->get( $key );
        
        if ( false === $data ) {
            $data = call_user_func( $callback );
            
            if ( ! empty( $data ) ) {
                $this->set( $key, $data, $expiration );
            }
        }
        
        return $data;
    }
    
    /**
     * Flush cache group
     *
     * @return bool
     */
    public function flush() {
        global $wpdb;
        
        // Clear object cache group
        wp_cache_delete_group( $this->group );
        
        // Clear transients
        $result = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $wpdb->options WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_mpai_%',
                '_transient_timeout_mpai_%'
            )
        );
        
        return false !== $result;
    }
    
    /**
     * Sanitize cache key
     *
     * @param string $key Cache key.
     * @return string
     */
    private function sanitize_key( $key ) {
        // Convert to string
        $key = (string) $key;
        
        // Remove special characters
        $key = preg_replace( '/[^a-z0-9_]/i', '_', $key );
        
        // Limit length
        if ( strlen( $key ) > 45 ) {
            $key = substr( $key, 0, 40 ) . '_' . md5( $key );
        }
        
        return $key;
    }
}

// Usage
$cache = new MPAI_Cache_Manager();

// Basic get/set
$cache->set( 'my_data', array( 'example' => 'cached data' ), 1800 );
$data = $cache->get( 'my_data' );

// Using the remember pattern
$expensive_data = $cache->remember( 'expensive_calculation', function() {
    // Expensive operation
    return array(
        'result' => 42,
        'calculated_at' => current_time( 'timestamp' ),
    );
}, 3600 );

// Caching API responses
$api_response = $cache->remember( 'api_response_' . md5( $api_url ), function() use ( $api_url ) {
    $response = wp_remote_get( $api_url );
    
    if ( is_wp_error( $response ) ) {
        return null;
    }
    
    $body = wp_remote_retrieve_body( $response );
    return json_decode( $body, true );
}, 900 ); // 15 minutes

// Flush cache when needed
add_action( 'save_post', function( $post_id ) {
    $cache = new MPAI_Cache_Manager();
    $cache->flush();
});
```

## Document Revision History

| Date | Version | Changes |
|------|---------|---------|
| 2025-04-06 | 1.0.0 | Initial document creation |