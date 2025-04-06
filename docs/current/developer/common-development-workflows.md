# Common Development Workflows

**Version:** 1.0.0  
**Last Updated:** 2025-04-06  
**Status:** ✅ Stable  
**Owner:** Developer Documentation Team

## Overview

This document outlines common development workflows for the MemberPress AI Assistant plugin. Following these patterns will help ensure consistency and quality across all development efforts.

## Table of Contents

1. [Development Environment Setup](#development-environment-setup)
2. [Adding a New Tool](#adding-a-new-tool)
3. [Creating a Specialized Agent](#creating-a-specialized-agent)
4. [Extending the Context System](#extending-the-context-system)
5. [Adding UI Components](#adding-ui-components)
6. [Working with the API Router](#working-with-the-api-router)
7. [Error Handling and Recovery](#error-handling-and-recovery)
8. [Testing Workflows](#testing-workflows)
9. [Release Process](#release-process)

## Development Environment Setup

### Initial Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/memberpress/memberpress-ai-assistant.git
   cd memberpress-ai-assistant
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Set up configuration**
   ```bash
   cp config-sample.php config.php
   # Edit config.php with your API keys and settings
   ```

4. **Build assets**
   ```bash
   npm run build
   ```

### Development Mode

For active development with automatic rebuilding:

```bash
npm run dev
```

### Local Testing

For testing with WordPress:

1. Install the plugin in your local WordPress environment
2. Activate the plugin through the WordPress admin interface
3. Configure the plugin settings with your API keys

## Adding a New Tool

Tools provide ways for the AI assistant to interact with WordPress and MemberPress. Follow these steps to add a new tool:

### 1. Create the Tool Class

Create a new file in `includes/tools/implementations/` named `class-mpai-{tool-name}-tool.php`:

```php
<?php
/**
 * MemberPress AI Assistant: New Tool Name Tool
 *
 * @package memberpress-ai-assistant
 */

/**
 * Class for the New Tool Name Tool
 */
class MPAI_New_Tool_Name_Tool extends MPAI_Base_Tool {
    /**
     * Get tool name
     *
     * @return string
     */
    public function get_name() {
        return 'new_tool_name';
    }
    
    /**
     * Get tool description
     *
     * @return string
     */
    public function get_description() {
        return 'Description of what the tool does';
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
                'description' => 'Description of parameter 1',
                'required' => true,
            ),
            'param2' => array(
                'type' => 'integer',
                'description' => 'Description of parameter 2',
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
        $param1 = isset( $parameters['param1'] ) ? sanitize_text_field( $parameters['param1'] ) : '';
        $param2 = isset( $parameters['param2'] ) ? intval( $parameters['param2'] ) : 0;
        
        if ( empty( $param1 ) ) {
            return new WP_Error( 'invalid_parameter', 'Param1 is required' );
        }
        
        // Tool implementation logic
        $result = $this->process_tool_action( $param1, $param2 );
        
        if ( is_wp_error( $result ) ) {
            return $result;
        }
        
        return array(
            'success' => true,
            'data' => $result,
        );
    }
    
    /**
     * Process the tool action
     *
     * @param string $param1 First parameter.
     * @param int    $param2 Second parameter.
     * @return mixed
     */
    private function process_tool_action( $param1, $param2 ) {
        // Implement the actual tool functionality
        // ...
        
        return $result;
    }
}
```

### 2. Register the Tool

Add your tool to the tool registry in `includes/class-mpai-tool-registry.php`:

```php
/**
 * Register all available tools
 */
private function register_tools() {
    // Existing tools...
    
    // Register your new tool
    $this->register_tool( new MPAI_New_Tool_Name_Tool() );
}
```

### 3. Update System Prompt

Update the AI system prompt to include information about your new tool in `includes/class-mpai-chat.php`:

```php
/**
 * Get system message for chat context
 *
 * @return string
 */
public function get_system_message() {
    $system_message = "You are an AI assistant for MemberPress with access to the following tools:\n\n";
    
    // Existing tools descriptions...
    
    // Add your new tool description
    $system_message .= "- NewToolName: Description of what the tool does. Use this when...\n";
    
    return $system_message;
}
```

### 4. Test the Tool

Create a test case in `tests/tools/test-new-tool-name-tool.php`:

```php
class MPAI_New_Tool_Name_Tool_Test extends WP_UnitTestCase {
    public function test_execute_with_valid_parameters() {
        $tool = new MPAI_New_Tool_Name_Tool();
        $result = $tool->execute(array(
            'param1' => 'test value',
            'param2' => 42,
        ));
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
    }
    
    public function test_execute_with_invalid_parameters() {
        $tool = new MPAI_New_Tool_Name_Tool();
        $result = $tool->execute(array(
            'param1' => '',
        ));
        
        $this->assertWPError($result);
    }
}
```

## Creating a Specialized Agent

Specialized agents handle specific types of user requests. Follow these steps to create a new agent:

### 1. Create the Agent Class

Create a new file in `includes/agents/specialized/` named `class-mpai-{agent-name}-agent.php`:

```php
<?php
/**
 * MemberPress AI Assistant: New Agent Name Agent
 *
 * @package memberpress-ai-assistant
 */

/**
 * Class for the New Agent Name Agent
 */
class MPAI_New_Agent_Name_Agent extends MPAI_Base_Agent {
    /**
     * Get agent name
     *
     * @return string
     */
    public function get_name() {
        return 'new_agent_name';
    }
    
    /**
     * Get agent description
     *
     * @return string
     */
    public function get_description() {
        return 'Description of what the agent does';
    }
    
    /**
     * Check if this agent can handle the given request
     *
     * @param string $request User request.
     * @return float Score between 0 and 1 indicating confidence.
     */
    public function can_handle( $request ) {
        // Determine if this agent can handle the request
        $keywords = array( 'keyword1', 'keyword2', 'keyword3' );
        $score = 0;
        
        foreach ( $keywords as $keyword ) {
            if ( stripos( $request, $keyword ) !== false ) {
                $score += 0.2; // Increase score for each matching keyword
            }
        }
        
        return min( $score, 1.0 ); // Cap at 1.0
    }
    
    /**
     * Process the request
     *
     * @param string $request User request.
     * @param array  $context Request context.
     * @return array
     */
    public function process( $request, $context = array() ) {
        // Agent-specific logic to process the request
        
        // Get data needed for this agent
        $data = $this->get_agent_data( $request, $context );
        
        // Generate prompt enhancement for this agent
        $prompt = $this->enhance_prompt( $request, $data );
        
        // Use the AI provider to generate a response
        $response = MPAI_API_Router::get_instance()->generate_completion(
            $prompt,
            array(
                'temperature' => 0.7,
                'max_tokens' => 500,
                // Additional parameters as needed
            )
        );
        
        // Process the response if needed
        $processed_response = $this->process_response( $response, $context );
        
        return array(
            'response' => $processed_response,
            'source' => $this->get_name(),
            'metadata' => array(
                'confidence' => $this->can_handle( $request ),
                'processed_by' => $this->get_name(),
            ),
        );
    }
    
    /**
     * Get data needed for this agent
     *
     * @param string $request User request.
     * @param array  $context Request context.
     * @return array
     */
    private function get_agent_data( $request, $context ) {
        // Fetch any data needed for processing
        // ...
        
        return $data;
    }
    
    /**
     * Enhance the prompt with agent-specific context
     *
     * @param string $request User request.
     * @param array  $data Agent data.
     * @return string
     */
    private function enhance_prompt( $request, $data ) {
        // Create a specialized prompt for this agent
        $prompt = "As a specialized agent for dealing with [specific domain], ";
        $prompt .= "please address the following request: {$request}\n\n";
        $prompt .= "Use the following information to help formulate your response:\n\n";
        
        // Add relevant data to the prompt
        foreach ( $data as $key => $value ) {
            $prompt .= "- {$key}: {$value}\n";
        }
        
        return $prompt;
    }
    
    /**
     * Process the API response if needed
     *
     * @param string $response API response.
     * @param array  $context Request context.
     * @return string
     */
    private function process_response( $response, $context ) {
        // Process the response if needed
        // ...
        
        return $response;
    }
}
```

### 2. Register the Agent

Add your agent to the agent orchestrator in `includes/class-mpai-agent-orchestrator.php`:

```php
/**
 * Register all available agents
 */
private function register_agents() {
    // Existing agents...
    
    // Register your new agent
    $this->register_agent( new MPAI_New_Agent_Name_Agent() );
}
```

### 3. Test the Agent

Create a test case in `tests/agents/test-new-agent-name-agent.php`:

```php
class MPAI_New_Agent_Name_Agent_Test extends WP_UnitTestCase {
    public function test_can_handle() {
        $agent = new MPAI_New_Agent_Name_Agent();
        
        // Test with relevant request
        $score1 = $agent->can_handle( 'A request with keyword1 and keyword2' );
        $this->assertGreaterThan( 0.3, $score1 );
        
        // Test with irrelevant request
        $score2 = $agent->can_handle( 'A completely unrelated request' );
        $this->assertLessThan( 0.2, $score2 );
    }
    
    public function test_process() {
        $agent = new MPAI_New_Agent_Name_Agent();
        $result = $agent->process( 'A test request with keyword1', array() );
        
        $this->assertArrayHasKey( 'response', $result );
        $this->assertArrayHasKey( 'source', $result );
        $this->assertEquals( 'new_agent_name', $result['source'] );
    }
}
```

## Extending the Context System

The context system provides relevant information to the AI assistant. Follow these steps to extend it:

### 1. Add a Context Provider

Create a new context provider method in `includes/class-mpai-context-manager.php`:

```php
/**
 * Get new context type data
 *
 * @param int $user_id User ID.
 * @return array
 */
private function get_new_context_type_data( $user_id = 0 ) {
    if ( empty( $user_id ) ) {
        $user_id = get_current_user_id();
    }
    
    // Fetch the relevant data
    $data = array(
        'key1' => 'value1',
        'key2' => 'value2',
        // Additional data...
    );
    
    // Apply filters to allow extensions
    $data = apply_filters( 'mpai_new_context_type_data', $data, $user_id );
    
    return $data;
}
```

### 2. Add to Main Context Collection

Update the `get_chat_context` method in the same file:

```php
/**
 * Get chat context
 *
 * @param int    $user_id User ID.
 * @param string $request_type Request type.
 * @return array
 */
public function get_chat_context( $user_id = 0, $request_type = '' ) {
    $context = array(
        'system_info' => $this->get_system_info(),
        'user_info' => $this->get_user_info( $user_id ),
        'memberpress_info' => $this->get_memberpress_info( $user_id ),
        // Existing context types...
        
        // Add your new context type
        'new_context_type' => $this->get_new_context_type_data( $user_id ),
    );
    
    // Apply filters to the entire context
    $context = apply_filters( 'mpai_chat_context', $context, $user_id, $request_type );
    
    return $context;
}
```

### 3. Test the Context Provider

Add a test case in `tests/test-context-manager.php`:

```php
public function test_get_new_context_type_data() {
    $context_manager = MPAI_Context_Manager::get_instance();
    $reflection = new ReflectionClass( $context_manager );
    $method = $reflection->getMethod( 'get_new_context_type_data' );
    $method->setAccessible( true );
    
    $result = $method->invoke( $context_manager, 1 ); // User ID 1
    
    $this->assertIsArray( $result );
    $this->assertArrayHasKey( 'key1', $result );
    $this->assertArrayHasKey( 'key2', $result );
}
```

## Adding UI Components

To enhance the user interface with new components:

### 1. Create Template File

Create a new template file in `includes/templates/` named `new-component-template.php`:

```php
<?php
/**
 * Template for the new component
 *
 * @package memberpress-ai-assistant
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="mpai-new-component" data-component-id="<?php echo esc_attr( $component_id ); ?>">
    <h3><?php echo esc_html( $title ); ?></h3>
    
    <div class="mpai-new-component-content">
        <?php echo wp_kses_post( $content ); ?>
    </div>
    
    <div class="mpai-new-component-actions">
        <button class="mpai-button mpai-primary-button" data-action="primary-action">
            <?php echo esc_html( $primary_button_text ); ?>
        </button>
        
        <button class="mpai-button mpai-secondary-button" data-action="secondary-action">
            <?php echo esc_html( $secondary_button_text ); ?>
        </button>
    </div>
</div>
```

### 2. Create Renderer Method

Add a renderer method to the appropriate class (often `class-mpai-admin.php` or `class-mpai-chat-interface.php`):

```php
/**
 * Render new component
 *
 * @param array $args Component arguments.
 * @return string
 */
public function render_new_component( $args = array() ) {
    $defaults = array(
        'component_id' => 'mpai-new-component-' . uniqid(),
        'title' => 'New Component',
        'content' => '',
        'primary_button_text' => 'Primary Action',
        'secondary_button_text' => 'Secondary Action',
    );
    
    $args = wp_parse_args( $args, $defaults );
    
    // Extract variables for template
    extract( $args );
    
    // Start output buffering
    ob_start();
    
    // Include the template
    include MPAI_PLUGIN_DIR . 'includes/templates/new-component-template.php';
    
    // Get the buffer contents and end buffering
    $output = ob_get_clean();
    
    return $output;
}
```

### 3. Add JavaScript Handlers

Create a JavaScript file in `assets/js/` named `new-component.js`:

```javascript
/**
 * New Component JavaScript
 */
(function($) {
    'use strict';
    
    const MPAINewComponent = {
        /**
         * Initialize the component
         */
        init: function() {
            this.bindEvents();
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Primary action button
            $(document).on('click', '.mpai-new-component .mpai-primary-button', this.handlePrimaryAction);
            
            // Secondary action button
            $(document).on('click', '.mpai-new-component .mpai-secondary-button', this.handleSecondaryAction);
        },
        
        /**
         * Handle primary action button click
         * 
         * @param {Event} e Click event
         */
        handlePrimaryAction: function(e) {
            e.preventDefault();
            
            const $component = $(this).closest('.mpai-new-component');
            const componentId = $component.data('component-id');
            
            // Perform the primary action
            console.log('Primary action for component:', componentId);
            
            // Make an AJAX request if needed
            $.ajax({
                url: mpai_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'mpai_new_component_primary_action',
                    component_id: componentId,
                    _wpnonce: mpai_vars.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Handle successful response
                        console.log('Primary action successful:', response.data);
                    } else {
                        // Handle error
                        console.error('Primary action failed:', response.data);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                }
            });
        },
        
        /**
         * Handle secondary action button click
         * 
         * @param {Event} e Click event
         */
        handleSecondaryAction: function(e) {
            e.preventDefault();
            
            const $component = $(this).closest('.mpai-new-component');
            const componentId = $component.data('component-id');
            
            // Perform the secondary action
            console.log('Secondary action for component:', componentId);
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        MPAINewComponent.init();
    });
})(jQuery);
```

### 4. Enqueue Scripts and Styles

Update the script and style registration in `includes/class-mpai-admin.php` or the appropriate class:

```php
/**
 * Enqueue admin scripts
 */
public function enqueue_scripts() {
    // Existing scripts...
    
    // Add your new component script
    wp_enqueue_script(
        'mpai-new-component',
        MPAI_PLUGIN_URL . 'assets/js/new-component.js',
        array( 'jquery' ),
        MPAI_VERSION,
        true
    );
    
    // Add any additional script data
    wp_localize_script(
        'mpai-new-component',
        'mpai_new_component_vars',
        array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'mpai_new_component_nonce' ),
            // Additional data...
        )
    );
}
```

### 5. Add AJAX Handler

Add an AJAX handler for any component actions in `includes/class-mpai-admin.php` or a dedicated handler class:

```php
/**
 * Handle primary action AJAX request
 */
public function handle_primary_action_ajax() {
    // Check nonce
    check_ajax_referer( 'mpai_new_component_nonce', '_wpnonce' );
    
    // Check user capabilities
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Permission denied', 403 );
    }
    
    // Get component ID
    $component_id = isset( $_POST['component_id'] ) ? sanitize_text_field( $_POST['component_id'] ) : '';
    
    if ( empty( $component_id ) ) {
        wp_send_json_error( 'Missing component ID', 400 );
    }
    
    // Process the action
    $result = $this->process_primary_action( $component_id );
    
    if ( is_wp_error( $result ) ) {
        wp_send_json_error( $result->get_error_message(), 500 );
    }
    
    wp_send_json_success( $result );
}

/**
 * Process the primary action
 *
 * @param string $component_id Component ID.
 * @return mixed
 */
private function process_primary_action( $component_id ) {
    // Implement action processing logic
    // ...
    
    return array(
        'processed' => true,
        'component_id' => $component_id,
        'message' => 'Action processed successfully',
    );
}

/**
 * Register AJAX handlers
 */
public function register_ajax_handlers() {
    add_action( 'wp_ajax_mpai_new_component_primary_action', array( $this, 'handle_primary_action_ajax' ) );
}
```

## Working with the API Router

The API Router manages communication with AI service providers. Here's how to extend or modify it:

### 1. Add a New Provider

Create a new provider class in `includes/` named `class-mpai-new-provider.php`:

```php
<?php
/**
 * MemberPress AI Assistant: New Provider API
 *
 * @package memberpress-ai-assistant
 */

/**
 * Class for the New Provider API
 */
class MPAI_New_Provider {
    /**
     * API Key
     *
     * @var string
     */
    private $api_key;
    
    /**
     * API Endpoint
     *
     * @var string
     */
    private $api_endpoint;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->api_key = get_option( 'mpai_new_provider_api_key', '' );
        $this->api_endpoint = 'https://api.newprovider.com/v1/completions';
    }
    
    /**
     * Check if the provider is configured
     *
     * @return bool
     */
    public function is_configured() {
        return ! empty( $this->api_key );
    }
    
    /**
     * Generate completion
     *
     * @param string $prompt The prompt to generate completion for.
     * @param array  $args Additional arguments.
     * @return string|WP_Error
     */
    public function generate_completion( $prompt, $args = array() ) {
        if ( ! $this->is_configured() ) {
            return new WP_Error( 'not_configured', 'New Provider API is not configured' );
        }
        
        $defaults = array(
            'temperature' => 0.7,
            'max_tokens' => 500,
        );
        
        $args = wp_parse_args( $args, $defaults );
        
        $request_data = array(
            'prompt' => $prompt,
            'temperature' => $args['temperature'],
            'max_tokens' => $args['max_tokens'],
        );
        
        $response = wp_remote_post(
            $this->api_endpoint,
            array(
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->api_key,
                ),
                'body' => json_encode( $request_data ),
                'timeout' => 30,
            )
        );
        
        if ( is_wp_error( $response ) ) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );
        $response_data = json_decode( $response_body, true );
        
        if ( $response_code !== 200 ) {
            $error_message = isset( $response_data['error']['message'] ) ? $response_data['error']['message'] : 'Unknown error';
            return new WP_Error( 'api_error', $error_message, array( 'status' => $response_code ) );
        }
        
        return isset( $response_data['choices'][0]['text'] ) ? $response_data['choices'][0]['text'] : '';
    }
}
```

### 2. Update the API Router

Modify `includes/class-mpai-api-router.php` to include your new provider:

```php
/**
 * Get provider instance
 *
 * @return object
 */
private function get_provider() {
    $provider_name = get_option( 'mpai_ai_provider', 'openai' );
    
    // Get provider instance
    switch ( $provider_name ) {
        case 'openai':
            return new MPAI_OpenAI();
        
        case 'anthropic':
            return new MPAI_Anthropic();
        
        case 'new_provider':
            return new MPAI_New_Provider();
        
        default:
            return new MPAI_OpenAI(); // Default fallback
    }
}
```

### 3. Add Provider Settings

Update the settings page in `includes/settings-page.php` to include your new provider:

```php
/**
 * Render AI provider settings
 */
public function render_ai_provider_settings() {
    $provider = get_option( 'mpai_ai_provider', 'openai' );
    ?>
    <tr>
        <th scope="row"><?php esc_html_e( 'AI Provider', 'memberpress-ai-assistant' ); ?></th>
        <td>
            <select name="mpai_ai_provider" id="mpai_ai_provider">
                <option value="openai" <?php selected( $provider, 'openai' ); ?>>
                    <?php esc_html_e( 'OpenAI', 'memberpress-ai-assistant' ); ?>
                </option>
                <option value="anthropic" <?php selected( $provider, 'anthropic' ); ?>>
                    <?php esc_html_e( 'Anthropic', 'memberpress-ai-assistant' ); ?>
                </option>
                <option value="new_provider" <?php selected( $provider, 'new_provider' ); ?>>
                    <?php esc_html_e( 'New Provider', 'memberpress-ai-assistant' ); ?>
                </option>
            </select>
        </td>
    </tr>
    
    <!-- New Provider API Key -->
    <tr class="mpai-new-provider-settings" <?php echo $provider === 'new_provider' ? '' : 'style="display:none;"'; ?>>
        <th scope="row"><?php esc_html_e( 'New Provider API Key', 'memberpress-ai-assistant' ); ?></th>
        <td>
            <input type="password" name="mpai_new_provider_api_key" id="mpai_new_provider_api_key" 
                   value="<?php echo esc_attr( get_option( 'mpai_new_provider_api_key', '' ) ); ?>" 
                   class="regular-text" />
            <p class="description">
                <?php esc_html_e( 'Enter your New Provider API key', 'memberpress-ai-assistant' ); ?>
                <a href="https://newprovider.com/api-keys" target="_blank"><?php esc_html_e( 'Get a key', 'memberpress-ai-assistant' ); ?></a>
            </p>
        </td>
    </tr>
    <?php
}
```

### 4. Add JavaScript to Toggle Provider Settings

Add JavaScript to show/hide provider-specific settings:

```javascript
// Show/hide provider-specific settings
$('#mpai_ai_provider').on('change', function() {
    const provider = $(this).val();
    
    // Hide all provider-specific settings
    $('.mpai-openai-settings, .mpai-anthropic-settings, .mpai-new-provider-settings').hide();
    
    // Show settings for the selected provider
    $(`.mpai-${provider}-settings`).show();
});
```

## Error Handling and Recovery

Proper error handling is crucial for plugin stability. Follow these patterns:

### 1. Using the Error Recovery System

```php
/**
 * Process a potentially error-prone operation
 *
 * @param array $data Input data.
 * @return mixed
 */
public function process_operation( $data ) {
    // Get the error recovery system
    $error_recovery = MPAI_Error_Recovery::get_instance();
    
    // Define the operation with retry capability
    $operation = function() use ( $data ) {
        // The actual operation that might fail
        $result = $this->make_external_api_call( $data );
        
        if ( is_wp_error( $result ) ) {
            // Throw an exception to trigger retry
            throw new Exception( $result->get_error_message() );
        }
        
        return $result;
    };
    
    // Define a fallback strategy
    $fallback = function() use ( $data ) {
        // Simplified operation or alternative path
        return $this->get_cached_result( $data );
    };
    
    try {
        // Execute the operation with retry capability
        return $error_recovery->execute_with_retry(
            $operation,
            array(
                'max_retries' => 3,
                'base_delay' => 1000, // ms
                'fallback' => $fallback,
            )
        );
    } catch ( Exception $e ) {
        // Log the error
        error_log( 'MPAI: Operation failed after retries: ' . $e->getMessage() );
        
        // Return a descriptive error
        return new WP_Error(
            'operation_failed',
            'The operation failed: ' . $e->getMessage(),
            array( 'data' => $data )
        );
    }
}
```

### 2. Implementing Graceful Degradation

```php
/**
 * Get user recommendations with graceful degradation
 *
 * @param int $user_id User ID.
 * @return array
 */
public function get_user_recommendations( $user_id ) {
    // Try the primary method first
    $recommendations = $this->generate_ai_recommendations( $user_id );
    
    // Check if we got a valid result
    if ( is_wp_error( $recommendations ) || empty( $recommendations ) ) {
        // Log the issue
        if ( is_wp_error( $recommendations ) ) {
            error_log( 'MPAI: AI recommendations failed: ' . $recommendations->get_error_message() );
        }
        
        // Fall back to rule-based recommendations
        $recommendations = $this->get_rule_based_recommendations( $user_id );
        
        // If that also fails, use default recommendations
        if ( is_wp_error( $recommendations ) || empty( $recommendations ) ) {
            $recommendations = $this->get_default_recommendations();
        }
    }
    
    return $recommendations;
}
```

### 3. Detailed Error Reporting

```php
/**
 * Handle API errors with detailed reporting
 *
 * @param WP_Error|string $error The error.
 * @param array          $context Error context.
 * @return WP_Error
 */
private function handle_api_error( $error, $context = array() ) {
    $error_message = is_wp_error( $error ) ? $error->get_error_message() : $error;
    $error_code = is_wp_error( $error ) ? $error->get_error_code() : 'unknown_error';
    
    // Add additional context information
    $error_data = array(
        'timestamp' => current_time( 'timestamp' ),
        'user_id' => get_current_user_id(),
        'context' => $context,
    );
    
    // Log detailed error information
    error_log( sprintf(
        'MPAI: API Error [%s]: %s | Context: %s',
        $error_code,
        $error_message,
        json_encode( $context )
    ) );
    
    // Report error to monitoring system
    do_action( 'mpai_report_error', $error_code, $error_message, $error_data );
    
    // Return an error with appropriate user-facing message
    return new WP_Error(
        $error_code,
        __( 'We encountered a problem. Please try again or contact support if the issue persists.', 'memberpress-ai-assistant' ),
        $error_data
    );
}
```

## Testing Workflows

Testing ensures plugin quality and stability. Follow these testing workflows:

### 1. Unit Testing

For testing individual classes and methods:

```php
class MPAI_MyClass_Test extends WP_UnitTestCase {
    public function setUp() {
        parent::setUp();
        // Setup test environment
    }
    
    public function tearDown() {
        // Clean up test environment
        parent::tearDown();
    }
    
    public function test_my_method() {
        $instance = new MPAI_MyClass();
        $result = $instance->my_method( 'test_input' );
        
        $this->assertEquals( 'expected_output', $result );
    }
    
    public function test_error_handling() {
        $instance = new MPAI_MyClass();
        $result = $instance->my_method( '' ); // Invalid input
        
        $this->assertWPError( $result );
        $this->assertEquals( 'invalid_input', $result->get_error_code() );
    }
}
```

### 2. Integration Testing

For testing interactions between components:

```php
class MPAI_Integration_Test extends WP_UnitTestCase {
    public function test_context_with_tools() {
        // Get instances
        $context_manager = MPAI_Context_Manager::get_instance();
        $tool_registry = MPAI_Tool_Registry::get_instance();
        
        // Register a test tool
        $tool_registry->register_tool( new MPAI_Test_Tool() );
        
        // Get context with tools
        $context = $context_manager->get_chat_context();
        
        // Verify tools are included in context
        $this->assertArrayHasKey( 'available_tools', $context );
        $this->assertContains( 'test_tool', array_column( $context['available_tools'], 'name' ) );
    }
}
```

### 3. Mocking External Services

For testing code that depends on external APIs:

```php
class MPAI_API_Test extends WP_UnitTestCase {
    public function test_api_with_mock() {
        // Create a mock API response
        $mock_response = array(
            'response' => array( 'code' => 200 ),
            'body' => json_encode( array(
                'choices' => array(
                    array( 'text' => 'Mock response text' )
                )
            ) )
        );
        
        // Set up the mock
        add_filter( 'pre_http_request', function( $preempt, $args, $url ) use ( $mock_response ) {
            if ( strpos( $url, 'api.openai.com' ) !== false ) {
                return $mock_response;
            }
            return $preempt;
        }, 10, 3 );
        
        // Test the API call
        $api = new MPAI_OpenAI();
        $result = $api->generate_completion( 'Test prompt' );
        
        // Verify the result
        $this->assertEquals( 'Mock response text', $result );
        
        // Remove the mock
        remove_all_filters( 'pre_http_request' );
    }
}
```

## Release Process

Follow these steps when releasing a new version:

### 1. Version Preparation

1. Update version numbers in:
   - `memberpress-ai-assistant.php` (main plugin file)
   - `README.txt`
   - `package.json`

2. Update changelog in `README.txt`:
   ```
   = 2.5.0 =
   * Added: New feature description
   * Improved: Enhancement description
   * Fixed: Bug fix description
   ```

3. Run final tests:
   ```bash
   npm run test
   ```

### 2. Build Process

1. Build production assets:
   ```bash
   npm run build
   ```

2. Generate release package:
   ```bash
   npm run package
   ```

### 3. Deployment

1. Commit changes with version tag:
   ```bash
   git add .
   git commit -m "Release version 2.5.0"
   git tag -a v2.5.0 -m "Version 2.5.0"
   git push origin master --tags
   ```

2. Upload to WordPress.org if applicable:
   ```bash
   svn co https://plugins.svn.wordpress.org/memberpress-ai-assistant/ svn
   # Copy files to SVN trunk
   # Update SVN tags
   svn ci -m "Release version 2.5.0"
   ```

3. Deploy to internal systems:
   ```bash
   scp memberpress-ai-assistant.zip user@deploy-server:/path/to/deployments/
   ```

## Document Revision History

| Date | Version | Changes |
|------|---------|---------|
| 2025-04-06 | 1.0.0 | Initial document creation |